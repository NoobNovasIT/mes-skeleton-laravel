<?php

namespace App\Http\Controllers;

use App\Http\Middleware\ValidateUserSession;
use App\Http\Navigation\PageNavigationContextProvider;
use App\Services\Accounts\AccountService;
use App\Services\Languages\LanguageService;
use App\Services\Permissions\PermissionService;
use App\Services\UserAccounts\UserAccountManagementService;
use App\Support\Constants\MenuCodes;
use App\Support\Enums\AccessLevel;
use App\Support\Enums\AccountStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\Response;

/** = Mes.Web/Controllers/HomeController.cs. */
final class HomeController extends MesController
{
    public function __construct(
        private readonly PageNavigationContextProvider $pageNavigationContextProvider,
        private readonly PermissionService $permissionService,
        private readonly UserAccountManagementService $userAccountManagementService,
        private readonly AccountService $accountService,
    ) {
    }

    public function index(): mixed
    {
        $userSessionState = ValidateUserSession::current();
        $canManageUserAccounts = $this->permissionService
            ->getAccessLevel($this->currentUserAccountId(), MenuCodes::SystemUserAccount)->value >= AccessLevel::ReadOnly->value;

        return view('home.index', [
            'fullName' => $userSessionState?->fullName ?? '',
            'isPendingApproval' => $userSessionState?->accountStatus === AccountStatus::PendingApproval,
            'modules' => $this->pageNavigationContextProvider->get()->navigation->modules,
            'pendingApprovalUserCount' => $canManageUserAccounts ? $this->userAccountManagementService->countPendingApproval() : 0,
        ]);
    }

    public function accessDenied(): Response
    {
        return response()->view('home.access-denied', [], 403);
    }

    public function setLanguage(Request $request): Response
    {
        $languageCode = (string) $request->input('languageCode');
        if (LanguageService::isSupported($languageCode)) {
            Cookie::queue(config('mes.language_cookie_name'), $languageCode, 60 * 24 * 365);
            if (Auth::check()) {
                $this->accountService->updatePreferredLanguage($this->currentUserAccountId(), $languageCode);
            }
        }

        return $this->redirectToLocalOrHome($request->input('returnUrl'));
    }
}
