<?php

namespace App\Services\Roles;

use App\Models\Role;
use App\Models\RoleMenuPermission;
use App\Models\UserAccountRole;
use App\Services\Caching\UserScopedCache;
use App\Support\Constants\ErrorMessageKeys;
use App\Support\Results\OperationResult;
use Illuminate\Support\Facades\DB;

/**
 * = Mes.Service/Roles/RoleService.cs.
 * Thân mỗi method: chuẩn hóa -> validate -> kiểm tra database (trùng mã) -> ghi -> commit một lần (DB::transaction).
 */
final class RoleService
{
    public const RoleCodeFormatMessage = 'Role code must be 2-50 characters: uppercase letters, digits or underscore.';
    public const RoleNameRequiredMessage = 'Role name is required (maximum 100 characters).';
    public const DescriptionTooLongMessage = 'Description must not exceed 500 characters.';

    public function __construct(
        private readonly UserScopedCache $userScopedCache,
    ) {
    }

    /** @return list<RoleListItemDto> */
    public function list(): array
    {
        return Role::query()
            ->withCount(['userAccountRoles as UserCount' => fn ($query) => $query
                ->whereHas('userAccount')])
            ->orderByDesc('IsSystemRole')
            ->orderBy('RoleName')
            ->get()
            ->map(fn (Role $role) => new RoleListItemDto(
                $role->Id, $role->RoleCode, $role->RoleName, $role->Description, $role->IsSystemRole, (int) $role->UserCount))
            ->all();
    }

    public function get(int $roleId): ?Role
    {
        return Role::query()->find($roleId);
    }

    /** @param array{RoleCode: string, RoleName: string, Description: ?string} $saveRoleRequest */
    public function create(array $saveRoleRequest): OperationResult
    {
        $normalizedRequest = $this->normalize($saveRoleRequest);
        $validationFailure = $this->validate($normalizedRequest, null);
        if ($validationFailure !== null) {
            return OperationResult::failure($validationFailure);
        }

        $role = DB::transaction(fn () => Role::query()->create([
            'RoleCode' => $normalizedRequest['RoleCode'],
            'RoleName' => $normalizedRequest['RoleName'],
            'Description' => $normalizedRequest['Description'],
            'IsSystemRole' => false,
        ]));

        return OperationResult::success($role->Id);
    }

    public function update(int $roleId, array $saveRoleRequest): OperationResult
    {
        $role = Role::query()->find($roleId);
        if ($role === null) {
            return OperationResult::failure(ErrorMessageKeys::RecordNotFound);
        }

        $normalizedRequest = $this->normalize($saveRoleRequest);
        if ($role->IsSystemRole && $normalizedRequest['RoleCode'] !== $role->RoleCode) {
            return OperationResult::failure(ErrorMessageKeys::SystemRoleCannotBeModified);
        }

        $validationFailure = $this->validate($normalizedRequest, $roleId);
        if ($validationFailure !== null) {
            return OperationResult::failure($validationFailure);
        }

        $role->fill($normalizedRequest);
        DB::transaction(fn () => $role->save());
        $this->userScopedCache->invalidateAllUsers();

        return OperationResult::success();
    }

    public function delete(int $roleId): OperationResult
    {
        $role = Role::query()->find($roleId);
        if ($role === null) {
            return OperationResult::failure(ErrorMessageKeys::RecordNotFound);
        }

        if ($role->IsSystemRole) {
            return OperationResult::failure(ErrorMessageKeys::SystemRoleCannotBeModified);
        }

        DB::transaction(function () use ($role) {
            // Bảng nối xóa cứng từng dòng (qua model) để AuditLog ghi đủ, rồi xóa mềm Role.
            UserAccountRole::query()->where('RoleId', $role->Id)->get()->each->delete();
            RoleMenuPermission::query()->where('RoleId', $role->Id)->get()->each->delete();
            $role->delete();
        });
        $this->userScopedCache->invalidateAllUsers();

        return OperationResult::success();
    }

    private function normalize(array $saveRoleRequest): array
    {
        $description = trim((string) ($saveRoleRequest['Description'] ?? ''));

        return [
            'RoleCode' => strtoupper(trim((string) ($saveRoleRequest['RoleCode'] ?? ''))),
            'RoleName' => trim((string) ($saveRoleRequest['RoleName'] ?? '')),
            'Description' => $description === '' ? null : $description,
        ];
    }

    /** = SaveRoleRequestValidator (FluentValidation) + kiểm tra trùng mã trong database. */
    private function validate(array $normalizedRequest, ?int $excludedRoleId): ?string
    {
        if (preg_match('/^[A-Z0-9_]{2,50}$/', $normalizedRequest['RoleCode']) !== 1) {
            return self::RoleCodeFormatMessage;
        }

        if ($normalizedRequest['RoleName'] === '' || mb_strlen($normalizedRequest['RoleName']) > 100) {
            return self::RoleNameRequiredMessage;
        }

        if (mb_strlen((string) $normalizedRequest['Description']) > 500) {
            return self::DescriptionTooLongMessage;
        }

        $isRoleCodeTaken = Role::query()
            ->where('RoleCode', $normalizedRequest['RoleCode'])
            ->when($excludedRoleId !== null, fn ($query) => $query->where('Id', '!=', $excludedRoleId))
            ->exists();

        return $isRoleCodeTaken ? ErrorMessageKeys::RoleCodeAlreadyTaken : null;
    }
}
