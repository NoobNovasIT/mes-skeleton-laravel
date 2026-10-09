<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\MesController;
use App\Http\Dialogs\ClientAfterSuccess;
use App\Services\Permissions\PermissionManagementService;
use App\Services\Roles\RoleListItemDto;
use App\Services\Roles\RoleService;
use App\Support\Enums\AccessLevel;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** = Areas/System/Controllers/RolePermissionController.cs (System > Role Permissions). */
final class RolePermissionController extends MesController
{
    public function __construct(
        private readonly RoleService $roleService,
        private readonly PermissionManagementService $permissionManagementService,
    ) {
    }

    public function index(Request $request): mixed
    {
        $roles = $this->roleService->list();
        $roleId = $request->integer('roleId') ?: null;

        $selectedRole = collect($roles)->first(fn (RoleListItemDto $role) => $role->roleId === $roleId)
            ?? collect($roles)->first(fn (RoleListItemDto $role) => ! $role->isSystemRole)
            ?? ($roles[0] ?? null);

        $permissionTree = $selectedRole === null
            ? []
            : ($this->permissionManagementService->getRolePermissionTree($selectedRole->roleId, $this->currentLanguageCode())->value ?? []);

        return view('system.role-permission.index', [
            'roles' => $roles,
            'selectedRole' => $selectedRole,
            'permissionTree' => $permissionTree,
            'title' => $this->isDialogRequest() && $selectedRole !== null ? localize('Role permissions: {0}', $selectedRole->roleName) : null,
        ]);
    }

    /** Form gửi accessLevels[menuId] = 0 | 1 | 2 (radio của từng Page). */
    public function save(Request $request, int $roleId): Response
    {
        $accessLevelByMenuId = [];
        foreach ((array) $request->input('accessLevels', []) as $menuId => $accessLevelValue) {
            $accessLevel = AccessLevel::parse($accessLevelValue);
            if ($accessLevel !== null) {
                $accessLevelByMenuId[(int) $menuId] = $accessLevel;
            }
        }

        $saveResult = $this->permissionManagementService->saveRolePermissions($roleId, $accessLevelByMenuId);

        return $this->operationResponse(
            $saveResult,
            'Saved successfully.',
            route('system.role-permission.index', ['roleId' => $roleId]),
            $this->isDialogRequest() ? ClientAfterSuccess::CloseDialog : ClientAfterSuccess::None);
    }
}
