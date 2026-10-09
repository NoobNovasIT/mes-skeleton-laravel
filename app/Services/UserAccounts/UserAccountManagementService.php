<?php

namespace App\Services\UserAccounts;

use App\Models\Role;
use App\Models\UserAccount;
use App\Models\UserAccountRole;
use App\Services\Accounts\AccountService;
use App\Services\AuditLogs\AuditLogWriter;
use App\Services\Caching\UserScopedCache;
use App\Services\Security\PasswordPolicy;
use App\Services\SystemSettings\SystemSettingService;
use App\Support\Constants\AuditActionTypes;
use App\Support\Constants\ErrorMessageKeys;
use App\Support\Constants\RoleCodes;
use App\Support\CurrentUser;
use App\Support\Enums\AccountStatus;
use App\Support\Results\OperationResult;
use App\Support\Results\PagedResult;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * = Mes.Service/UserAccounts/UserAccountManagementService.cs (System > User Accounts).
 * Bản port chưa gửi email / thông báo realtime khi duyệt tài khoản.
 */
final class UserAccountManagementService
{
    public function __construct(
        private readonly AccountService $accountService,
        private readonly SystemSettingService $systemSettingService,
        private readonly UserScopedCache $userScopedCache,
    ) {
    }

    public function search(?string $keyword, ?AccountStatus $accountStatus, ?int $roleId, int $pageNumber, int $pageSize): PagedResult
    {
        $pageNumber = max(1, $pageNumber);
        $pageSize = min(max(1, $pageSize), 500);
        $keyword = trim((string) $keyword);

        $query = UserAccount::query()
            ->when($keyword !== '', fn ($query) => $query->where(fn ($keywordQuery) => $keywordQuery
                ->where('UserName', 'like', "%{$keyword}%")
                ->orWhere('Email', 'like', "%{$keyword}%")
                ->orWhere('FullName', 'like', "%{$keyword}%")))
            ->when($accountStatus !== null, fn ($query) => $query->where('AccountStatus', $accountStatus->value))
            ->when($roleId !== null, fn ($query) => $query->whereHas('userAccountRoles', fn ($roleQuery) => $roleQuery->where('RoleId', $roleId)));

        $totalCount = (clone $query)->count();
        $userAccounts = $query->with('roles')
            ->orderBy('AccountStatus')
            ->orderBy('UserName')
            ->skip(($pageNumber - 1) * $pageSize)
            ->take($pageSize)
            ->get()
            ->all();

        return new PagedResult($userAccounts, $totalCount, $pageNumber, $pageSize);
    }

    public function getDetail(int $userAccountId): ?UserAccount
    {
        return UserAccount::query()->with('roles')->find($userAccountId);
    }

    public function countPendingApproval(): int
    {
        return UserAccount::query()->where('AccountStatus', AccountStatus::PendingApproval->value)->count();
    }

    /** @param list<int> $roleIds */
    public function update(int $userAccountId, string $fullName, array $roleIds): OperationResult
    {
        $fullName = trim($fullName);
        if ($fullName === '' || mb_strlen($fullName) > 150) {
            return OperationResult::failure(ErrorMessageKeys::FullNameRequired);
        }

        $userAccount = UserAccount::query()->with('userAccountRoles.role')->find($userAccountId);
        if ($userAccount === null) {
            return OperationResult::failure(ErrorMessageKeys::RecordNotFound);
        }

        return DB::transaction(function () use ($userAccount, $fullName, $roleIds) {
            $roleAssignmentResult = $this->replaceRoles($userAccount, $roleIds);
            if ($roleAssignmentResult->isFailure()) {
                return $roleAssignmentResult;
            }

            $userAccount->FullName = $fullName;
            $userAccount->save();
            $this->userScopedCache->invalidateUser($userAccount->Id);

            return OperationResult::success();
        });
    }

    /** @param list<int> $roleIds */
    public function approve(int $userAccountId, array $roleIds): OperationResult
    {
        $userAccount = UserAccount::query()->with('userAccountRoles.role')->find($userAccountId);
        if ($userAccount === null) {
            return OperationResult::failure(ErrorMessageKeys::RecordNotFound);
        }

        if ($userAccount->AccountStatus !== AccountStatus::PendingApproval) {
            return OperationResult::success();
        }

        return DB::transaction(function () use ($userAccount, $roleIds) {
            $roleAssignmentResult = $this->replaceRoles($userAccount, $roleIds);
            if ($roleAssignmentResult->isFailure()) {
                return $roleAssignmentResult;
            }

            $userAccount->AccountStatus = AccountStatus::Active;
            $userAccount->save();
            AuditLogWriter::add(AuditActionTypes::AccountApproved, $userAccount->UserName, 'UserAccount', (string) $userAccount->Id);
            $this->userScopedCache->invalidateUser($userAccount->Id);

            return OperationResult::success();
        });
    }

    public function changeAccountStatus(int $userAccountId, AccountStatus $newAccountStatus): OperationResult
    {
        if ($userAccountId === CurrentUser::userAccountId()) {
            return OperationResult::failure(ErrorMessageKeys::CannotLockOwnAccount);
        }

        $auditActionType = match ($newAccountStatus) {
            AccountStatus::Active => AuditActionTypes::AccountUnlocked,
            AccountStatus::Locked => AuditActionTypes::AccountLocked,
            AccountStatus::Disabled => AuditActionTypes::AccountDisabled,
            default => null,
        };
        $userAccount = UserAccount::query()->find($userAccountId);
        if ($auditActionType === null || $userAccount === null) {
            return OperationResult::failure(ErrorMessageKeys::RecordNotFound);
        }

        $userAccount->AccountStatus = $newAccountStatus;
        if ($newAccountStatus === AccountStatus::Active) {
            $userAccount->LockoutEndAt = null;
            $userAccount->FailedLoginCount = 0;
        } else {
            // Đẩy user đang đăng nhập ra ngay lập tức.
            $userAccount->SecurityStamp = strtoupper((string) Str::uuid());
        }

        DB::transaction(function () use ($userAccount, $auditActionType) {
            $userAccount->save();
            AuditLogWriter::add($auditActionType, $userAccount->UserName, 'UserAccount', (string) $userAccount->Id);
        });
        $this->userScopedCache->invalidateUser($userAccountId);

        return OperationResult::success();
    }

    public function resetPassword(int $userAccountId, string $temporaryPassword): OperationResult
    {
        $passwordPolicyResult = PasswordPolicy::validate($temporaryPassword, $this->systemSettingService->getSecurityPolicy()->passwordMinimumLength);
        if ($passwordPolicyResult->isFailure()) {
            return $passwordPolicyResult;
        }

        $userAccount = UserAccount::query()->find($userAccountId);
        if ($userAccount === null) {
            return OperationResult::failure(ErrorMessageKeys::RecordNotFound);
        }

        DB::transaction(function () use ($userAccount, $temporaryPassword) {
            $this->accountService->setNewPassword($userAccount, $temporaryPassword);
            $userAccount->MustChangePassword = true;
            $userAccount->save();
            AuditLogWriter::add(AuditActionTypes::PasswordReset, "By administrator: {$userAccount->UserName}", 'UserAccount', (string) $userAccount->Id);
        });
        $this->userScopedCache->invalidateUser($userAccountId);

        return OperationResult::success();
    }

    public function delete(int $userAccountId): OperationResult
    {
        if ($userAccountId === CurrentUser::userAccountId()) {
            return OperationResult::failure(ErrorMessageKeys::CannotLockOwnAccount);
        }

        $userAccount = UserAccount::query()->find($userAccountId);
        if ($userAccount === null) {
            return OperationResult::failure(ErrorMessageKeys::RecordNotFound);
        }

        DB::transaction(function () use ($userAccount) {
            $userAccount->SecurityStamp = strtoupper((string) Str::uuid());
            $userAccount->save();
            $userAccount->delete();
        });
        $this->userScopedCache->invalidateUser($userAccountId);

        return OperationResult::success();
    }

    /**
     * Thay toàn bộ Role của user. Không cho Admin tự gỡ Role ADMIN của chính mình.
     *
     * @param list<int> $roleIds
     */
    private function replaceRoles(UserAccount $userAccount, array $roleIds): OperationResult
    {
        $validRoles = Role::query()->whereIn('Id', array_unique($roleIds))->get();
        $validRoleIds = $validRoles->pluck('Id')->map(fn ($roleId) => (int) $roleId)->all();

        $isEditingOwnAccount = $userAccount->Id === CurrentUser::userAccountId();
        $isRemovingOwnAdministratorRole = $isEditingOwnAccount && $userAccount->userAccountRoles->contains(
            fn (UserAccountRole $userAccountRole) => $userAccountRole->role?->RoleCode === RoleCodes::Administrator
                && ! in_array((int) $userAccountRole->RoleId, $validRoleIds, true));
        if ($isRemovingOwnAdministratorRole) {
            return OperationResult::failure(ErrorMessageKeys::CannotRemoveOwnAdministratorRole);
        }

        $existingRoleIds = $userAccount->userAccountRoles->pluck('RoleId')->map(fn ($roleId) => (int) $roleId)->all();
        $removedUserAccountRoles = $userAccount->userAccountRoles
            ->filter(fn (UserAccountRole $userAccountRole) => ! in_array((int) $userAccountRole->RoleId, $validRoleIds, true));

        foreach ($removedUserAccountRoles as $removedUserAccountRole) {
            $removedUserAccountRole->delete();
        }

        $addedRoleIds = array_diff($validRoleIds, $existingRoleIds);
        foreach ($addedRoleIds as $addedRoleId) {
            UserAccountRole::query()->create(['UserAccountId' => $userAccount->Id, 'RoleId' => $addedRoleId]);
        }

        if ($removedUserAccountRoles->isNotEmpty() || $addedRoleIds !== []) {
            $roleCodes = $validRoles->pluck('RoleCode')->sort()->implode(', ');
            AuditLogWriter::add(AuditActionTypes::PermissionChange, "User {$userAccount->UserName} roles: {$roleCodes}", 'UserAccount', (string) $userAccount->Id);
        }

        return OperationResult::success();
    }
}
