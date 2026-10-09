<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\MesController;
use App\Http\Dialogs\ClientAfterSuccess;
use App\Services\Permissions\PermissionManagementService;
use App\Services\Roles\RoleService;
use App\Services\SystemSettings\SystemSettingService;
use App\Services\UserAccounts\UserAccountManagementService;
use App\Support\Constants\ErrorMessageKeys;
use App\Support\Enums\AccessLevel;
use App\Support\Enums\AccountStatus;
use App\Support\Results\OperationResult;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** = Areas/System/Controllers/UserAccountController.cs (System > User Accounts). */
final class UserAccountController extends MesController
{
    private const PageSize = 30;

    public function __construct(
        private readonly UserAccountManagementService $userAccountManagementService,
        private readonly RoleService $roleService,
        private readonly PermissionManagementService $permissionManagementService,
        private readonly SystemSettingService $systemSettingService,
    ) {
    }

    public function index(Request $request): mixed
    {
        $keyword = $request->query('keyword');
        $accountStatus = AccountStatus::parse($request->query('accountStatus'));
        $roleId = $request->integer('roleId') ?: null;

        return view('system.user-account.index', [
            'keyword' => $keyword,
            'accountStatus' => $accountStatus,
            'roleId' => $roleId,
            'searchResult' => $this->userAccountManagementService->search($keyword, $accountStatus, $roleId, $request->integer('pageNumber', 1), self::PageSize),
            'roles' => $this->roleService->list(),
        ]);
    }

    public function detail(int $id): mixed
    {
        $userAccount = $this->userAccountManagementService->getDetail($id);
        abort_if($userAccount === null, 404);

        return view('system.user-account.detail', [
            'userAccount' => $userAccount,
            'roleIds' => $userAccount->roles->pluck('Id')->map(fn ($roleId) => (int) $roleId)->all(),
            'roles' => $this->roleService->list(),
            'passwordMinimumLength' => $this->systemSettingService->getSecurityPolicy()->passwordMinimumLength,
            'isCurrentUser' => $userAccount->Id === $this->currentUserAccountId(),
            'title' => "{$userAccount->FullName} ({$userAccount->UserName})",
        ]);
    }

    public function update(Request $request, int $id): Response
    {
        $roleIds = array_map('intval', (array) $request->input('RoleIds', []));
        $updateResult = $this->userAccountManagementService->update($id, (string) $request->input('FullName'), $roleIds);

        return $this->operationResponse($updateResult, 'Saved successfully.', route('system.user-account.detail', $id), ClientAfterSuccess::ReloadDialog);
    }

    public function approve(Request $request, int $id): Response
    {
        $roleIds = array_map('intval', (array) $request->input('roleIds', []));
        $approveResult = $this->userAccountManagementService->approve($id, $roleIds);

        return $this->operationResponse($approveResult, 'The account has been approved.', route('system.user-account.detail', $id), ClientAfterSuccess::ReloadDialog);
    }

    public function changeStatus(Request $request, int $id): Response
    {
        $newAccountStatus = AccountStatus::parse($request->input('newAccountStatus'));
        $changeResult = $newAccountStatus === null
            ? OperationResult::failure(ErrorMessageKeys::RecordNotFound)
            : $this->userAccountManagementService->changeAccountStatus($id, $newAccountStatus);

        return $this->operationResponse($changeResult, 'The account status has been updated.', route('system.user-account.detail', $id), ClientAfterSuccess::ReloadDialog);
    }

    public function resetPassword(Request $request, int $id): Response
    {
        $resetResult = $this->userAccountManagementService->resetPassword($id, (string) $request->input('temporaryPassword'));

        return $this->operationResponse(
            $resetResult,
            'The temporary password has been set. The user must change it at the next sign in.',
            route('system.user-account.detail', $id),
            ClientAfterSuccess::ReloadDialog);
    }

    public function destroy(int $id): Response
    {
        $deleteResult = $this->userAccountManagementService->delete($id);

        return $this->operationResponse(
            $deleteResult,
            'Deleted successfully.',
            $deleteResult->isSuccess ? route('system.user-account.index') : route('system.user-account.detail', $id),
            ClientAfterSuccess::CloseDialog);
    }

    public function permissions(int $id): mixed
    {
        $userAccount = $this->userAccountManagementService->getDetail($id);
        $permissionTreeResult = $this->permissionManagementService->getUserPermissionTree($id, $this->currentLanguageCode());
        abort_if($userAccount === null || $permissionTreeResult->isFailure(), 404);

        return view('system.user-account.permissions', [
            'userAccount' => $userAccount,
            'permissionTree' => $permissionTreeResult->value,
            'title' => localize('Permission overrides: {0}', $userAccount->UserName),
        ]);
    }

    /** Form gửi overrideAccessLevels[menuId] = "" (theo Role) | 0 | 1 | 2 và overrideReasons[menuId]. */
    public function savePermissions(Request $request, int $id): Response
    {
        $overrideReasons = (array) $request->input('overrideReasons', []);
        $overrideItems = [];
        foreach ((array) $request->input('overrideAccessLevels', []) as $menuId => $accessLevelValue) {
            $accessLevel = AccessLevel::parse($accessLevelValue);
            if ($accessLevel !== null) {
                $overrideItems[] = ['menuId' => (int) $menuId, 'accessLevel' => $accessLevel, 'reason' => $overrideReasons[$menuId] ?? null];
            }
        }

        $saveResult = $this->permissionManagementService->saveUserPermissionOverrides($id, $overrideItems);

        return $this->operationResponse($saveResult, 'Saved successfully.', route('system.user-account.permissions', $id), ClientAfterSuccess::CloseDialog);
    }
}
