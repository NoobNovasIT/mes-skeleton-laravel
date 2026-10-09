<?php

namespace App\Services\Permissions;

use App\Models\Menu;
use App\Models\Role;
use App\Models\RoleMenuPermission;
use App\Models\UserAccount;
use App\Models\UserMenuPermissionOverride;
use App\Services\AuditLogs\AuditLogWriter;
use App\Services\Caching\UserScopedCache;
use App\Services\Database\StoredProcedureExecutor;
use App\Services\Menus\TreeBuilder;
use App\Support\Constants\AuditActionTypes;
use App\Support\Constants\ErrorMessageKeys;
use App\Support\Enums\AccessLevel;
use App\Support\Enums\MenuType;
use App\Support\Results\OperationResult;
use Illuminate\Support\Facades\DB;

/** = Mes.Service/Permissions/PermissionManagementService.cs - quản trị quyền Role × Page và override theo user. */
final class PermissionManagementService
{
    private const OverrideReasonMaxLength = 300;

    public function __construct(
        private readonly UserScopedCache $userScopedCache,
    ) {
    }

    /** value = list<PermissionTreeNodeDto> */
    public function getRolePermissionTree(int $roleId, string $languageCode): OperationResult
    {
        if (Role::query()->find($roleId) === null) {
            return OperationResult::failure(ErrorMessageKeys::RecordNotFound);
        }

        $matrixRecords = StoredProcedureExecutor::query(
            StoredProcedureExecutor::SecurityPermissionGetMatrixByRoleId,
            ['RoleId' => $roleId, 'LanguageCode' => $languageCode]);

        $flatNodes = array_map(function (\stdClass $record) {
            $menuType = MenuType::from((int) $record->MenuType);

            return new PermissionTreeNodeDto(
                menuId: (int) $record->MenuId,
                parentMenuId: $record->ParentMenuId === null ? null : (int) $record->ParentMenuId,
                menuCode: $record->MenuCode,
                menuType: $menuType,
                displayName: $record->DisplayName,
                iconClass: $record->IconClass,
                isActive: (bool) $record->IsActive,
                roleAccessLevel: $menuType === MenuType::Page ? AccessLevel::from((int) $record->AccessLevel) : AccessLevel::NoAccess,
            );
        }, $matrixRecords);

        return OperationResult::success(TreeBuilder::build($flatNodes));
    }

    /** @param array<int, AccessLevel> $accessLevelByMenuId */
    public function saveRolePermissions(int $roleId, array $accessLevelByMenuId): OperationResult
    {
        $role = Role::query()->find($roleId);
        if ($role === null) {
            return OperationResult::failure(ErrorMessageKeys::RecordNotFound);
        }

        if ($role->IsSystemRole) {
            return OperationResult::failure(ErrorMessageKeys::SystemRoleCannotBeModified);
        }

        $pageMenuIds = $this->listPageMenuIds();
        $pageAccessLevelByMenuId = array_filter(
            $accessLevelByMenuId,
            fn (AccessLevel $accessLevel, int $menuId) => in_array($menuId, $pageMenuIds, true),
            ARRAY_FILTER_USE_BOTH);

        DB::transaction(function () use ($role, $pageAccessLevelByMenuId) {
            $existingPermissions = RoleMenuPermission::query()->where('RoleId', $role->Id)->get()->keyBy('MenuId');

            // Không có dòng = NoAccess -> NoAccess thì xóa dòng, còn lại thêm / sửa.
            foreach ($existingPermissions as $menuId => $existingPermission) {
                $newAccessLevel = $pageAccessLevelByMenuId[$menuId] ?? AccessLevel::NoAccess;
                if ($newAccessLevel === AccessLevel::NoAccess) {
                    $existingPermission->delete();
                } elseif ($existingPermission->AccessLevel !== $newAccessLevel) {
                    $existingPermission->AccessLevel = $newAccessLevel;
                    $existingPermission->save();
                }
            }

            foreach ($pageAccessLevelByMenuId as $menuId => $accessLevel) {
                if ($accessLevel !== AccessLevel::NoAccess && ! $existingPermissions->has($menuId)) {
                    RoleMenuPermission::query()->create(['RoleId' => $role->Id, 'MenuId' => $menuId, 'AccessLevel' => $accessLevel]);
                }
            }

            $grantedPageCount = count(array_filter($pageAccessLevelByMenuId, fn (AccessLevel $accessLevel) => $accessLevel !== AccessLevel::NoAccess));
            AuditLogWriter::add(AuditActionTypes::PermissionChange, "Role {$role->RoleCode}: {$grantedPageCount} page(s) granted", 'Role', (string) $role->Id);
        });
        $this->userScopedCache->invalidateAllUsers();

        return OperationResult::success();
    }

    /** value = list<PermissionTreeNodeDto> */
    public function getUserPermissionTree(int $userAccountId, string $languageCode): OperationResult
    {
        if (UserAccount::query()->find($userAccountId) === null) {
            return OperationResult::failure(ErrorMessageKeys::RecordNotFound);
        }

        // MAX(AccessLevel) trên các Role (chưa xóa) của user, theo từng Page.
        $roleAccessLevelByMenuId = DB::table('UserAccountRole')
            ->join('Role', fn ($join) => $join->on('Role.Id', '=', 'UserAccountRole.RoleId')->where('Role.IsDeleted', false))
            ->join('RoleMenuPermission', 'RoleMenuPermission.RoleId', '=', 'UserAccountRole.RoleId')
            ->where('UserAccountRole.UserAccountId', $userAccountId)
            ->groupBy('RoleMenuPermission.MenuId')
            ->selectRaw('RoleMenuPermission.MenuId, MAX(RoleMenuPermission.AccessLevel) AS AccessLevel')
            ->pluck('AccessLevel', 'MenuId')
            ->all();

        $permissionOverrideByMenuId = UserMenuPermissionOverride::query()->where('UserAccountId', $userAccountId)->get()->keyBy('MenuId');

        $flatNodes = Menu::query()->with('translations')->orderBy('SortOrder')->orderBy('Id')->get()
            ->map(function (Menu $menu) use ($languageCode, $roleAccessLevelByMenuId, $permissionOverrideByMenuId) {
                $isPage = $menu->MenuType === MenuType::Page;
                $permissionOverride = $permissionOverrideByMenuId->get($menu->Id);

                return new PermissionTreeNodeDto(
                    menuId: $menu->Id,
                    parentMenuId: $menu->ParentMenuId,
                    menuCode: $menu->MenuCode,
                    menuType: $menu->MenuType,
                    displayName: $menu->displayName($languageCode),
                    iconClass: $menu->IconClass,
                    isActive: $menu->IsActive,
                    roleAccessLevel: $isPage ? AccessLevel::from((int) ($roleAccessLevelByMenuId[$menu->Id] ?? 0)) : AccessLevel::NoAccess,
                    overrideAccessLevel: $isPage ? $permissionOverride?->AccessLevel : null,
                    overrideReason: $isPage ? $permissionOverride?->Reason : null,
                );
            })
            ->all();

        return OperationResult::success(TreeBuilder::build($flatNodes));
    }

    /** @param list<array{menuId: int, accessLevel: AccessLevel, reason: ?string}> $overrideItems */
    public function saveUserPermissionOverrides(int $userAccountId, array $overrideItems): OperationResult
    {
        $userAccount = UserAccount::query()->find($userAccountId);
        if ($userAccount === null) {
            return OperationResult::failure(ErrorMessageKeys::RecordNotFound);
        }

        $pageMenuIds = $this->listPageMenuIds();
        $newOverrideByMenuId = [];
        foreach ($overrideItems as $overrideItem) {
            if (in_array($overrideItem['menuId'], $pageMenuIds, true)) {
                $newOverrideByMenuId[$overrideItem['menuId']] = $overrideItem;
            }
        }

        DB::transaction(function () use ($userAccount, $newOverrideByMenuId) {
            $existingOverrides = UserMenuPermissionOverride::query()->where('UserAccountId', $userAccount->Id)->get();

            foreach ($existingOverrides as $existingOverride) {
                $newOverride = $newOverrideByMenuId[$existingOverride->MenuId] ?? null;
                if ($newOverride === null) {
                    $existingOverride->delete();
                    continue;
                }

                $existingOverride->AccessLevel = $newOverride['accessLevel'];
                $existingOverride->Reason = $this->truncateReason($newOverride['reason']);
                $existingOverride->save();
                unset($newOverrideByMenuId[$existingOverride->MenuId]);
            }

            foreach ($newOverrideByMenuId as $menuId => $newOverride) {
                UserMenuPermissionOverride::query()->create([
                    'UserAccountId' => $userAccount->Id,
                    'MenuId' => $menuId,
                    'AccessLevel' => $newOverride['accessLevel'],
                    'Reason' => $this->truncateReason($newOverride['reason']),
                ]);
            }
        });

        $overrideCount = UserMenuPermissionOverride::query()->where('UserAccountId', $userAccount->Id)->count();
        AuditLogWriter::add(AuditActionTypes::PermissionChange, "User {$userAccount->UserName}: {$overrideCount} override(s)", 'UserAccount', (string) $userAccount->Id);
        $this->userScopedCache->invalidateUser($userAccountId);

        return OperationResult::success();
    }

    /** @return list<int> */
    private function listPageMenuIds(): array
    {
        return Menu::query()->where('MenuType', MenuType::Page->value)->pluck('Id')->map(fn ($menuId) => (int) $menuId)->all();
    }

    private function truncateReason(?string $reason): ?string
    {
        $trimmedReason = trim((string) $reason);

        return $trimmedReason === '' ? null : mb_substr($trimmedReason, 0, self::OverrideReasonMaxLength);
    }
}
