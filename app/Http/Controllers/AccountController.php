<?php

namespace App\Http\Controllers;

use App\Http\Dialogs\ClientAfterSuccess;
use App\Http\Middleware\ValidateUserSession;
use App\Http\ViewModels\Account\ChangePasswordViewModel;
use App\Http\ViewModels\Account\LoginViewModel;
use App\Models\UserAccount;
use App\Services\Accounts\AccountService;
use App\Services\Languages\LanguageService;
use App\Services\SystemSettings\SystemSettingService;
use App\Support\Constants\ErrorMessageKeys;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\Response;

/**
 * = Mes.Web/Controllers/AccountController.cs - phần Login / Logout / ChangePassword / Profile.
 * Đăng nhập dùng Auth::login() (session của Laravel) thay cho Cookie Authentication của ASP.NET.
 */
final class AccountController extends MesController
{
    public function __construct(
        private readonly AccountService $accountService,
        private readonly SystemSettingService $systemSettingService,
        private readonly LanguageService $languageService,
    ) {
    }

    // ------------------------------------------------------------------ Login / Logout

    public function showLogin(Request $request): mixed
    {
        if (Auth::check()) {
            return $this->redirectToLocalOrHome($request->query('returnUrl'));
        }

        return view('account.login', ['loginViewModel' => LoginViewModel::fromInput(['ReturnUrl' => $request->query('returnUrl')])]);
    }

    public function login(Request $request): mixed
    {
        $loginViewModel = LoginViewModel::fromInput($request->all());
        $validator = LoginViewModel::validator($request->all());
        if ($validator->fails()) {
            return $this->viewWithValidationErrors(view('account.login', compact('loginViewModel')), $validator);
        }

        $validationResult = $this->accountService->validateCredential($loginViewModel->LoginName, (string) $request->input('Password'));
        if ($validationResult->isFailure()) {
            $loginViewModel->IsEmailVerificationRequired = $validationResult->errorMessageKey === ErrorMessageKeys::EmailNotVerified;

            return $this->viewWithOperationError(view('account.login', compact('loginViewModel')), $validationResult);
        }

        /** @var UserAccount $userAccount */
        $userAccount = $validationResult->value;
        $this->signIn($request, $userAccount);

        if (LanguageService::isSupported($userAccount->PreferredLanguageCode)) {
            $this->writeLanguageCookie($userAccount->PreferredLanguageCode);
        }

        return $this->redirectToLocalOrHome($loginViewModel->ReturnUrl);
    }

    public function logout(Request $request): Response
    {
        $this->accountService->recordLogout();
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('account.login');
    }

    // ------------------------------------------------------------------ Change password

    public function showChangePassword(): mixed
    {
        return view('account.change-password', ['changePasswordViewModel' => $this->buildChangePasswordViewModel()]);
    }

    public function changePassword(Request $request): mixed
    {
        $changePasswordViewModel = $this->buildChangePasswordViewModel();
        $validator = ChangePasswordViewModel::validator($request->all());
        if ($validator->fails()) {
            return $this->viewWithValidationErrors(view('account.change-password', compact('changePasswordViewModel')), $validator);
        }

        $changeResult = $this->accountService->changePassword(
            $this->currentUserAccountId(), (string) $request->input('CurrentPassword'), (string) $request->input('NewPassword'));
        if ($changeResult->isFailure()) {
            return $this->viewWithOperationError(view('account.change-password', compact('changePasswordViewModel')), $changeResult);
        }

        // SecurityStamp đã đổi -> ghi lại vào phiên hiện tại, nếu không chính phiên này cũng bị đăng xuất.
        $request->session()->put(ValidateUserSession::SecurityStampSessionKey, strtoupper((string) $changeResult->value->SecurityStamp));

        return $this->operationResponse($changeResult, 'Your password has been changed.', route('home'));
    }

    private function buildChangePasswordViewModel(): ChangePasswordViewModel
    {
        $changePasswordViewModel = new ChangePasswordViewModel();
        $changePasswordViewModel->PasswordMinimumLength = $this->systemSettingService->getSecurityPolicy()->passwordMinimumLength;
        $changePasswordViewModel->IsPasswordChangeRequired = ValidateUserSession::current()?->mustChangePassword === true;

        return $changePasswordViewModel;
    }

    // ------------------------------------------------------------------ Profile

    public function showProfile(): mixed
    {
        $userAccount = $this->accountService->getProfile($this->currentUserAccountId());
        abort_if($userAccount === null, 404);

        return view('account.profile', [
            'profile' => [
                'UserName' => $userAccount->UserName,
                'Email' => $userAccount->Email,
                'FullName' => $userAccount->FullName,
                'PreferredLanguageCode' => $userAccount->PreferredLanguageCode,
                'RoleNames' => $userAccount->roleNames(),
                'CreatedAt' => $userAccount->CreatedAt,
                'LastLoginAt' => $userAccount->LastLoginAt,
            ],
            'languages' => $this->languageService->listActive(),
        ]);
    }

    public function profile(Request $request): Response
    {
        $preferredLanguageCode = $request->input('PreferredLanguageCode');
        $preferredLanguageCode = LanguageService::isSupported($preferredLanguageCode) ? $preferredLanguageCode : null;

        $updateResult = $this->accountService->updateProfile(
            $this->currentUserAccountId(), (string) $request->input('FullName'), $preferredLanguageCode);
        if ($updateResult->isSuccess && $preferredLanguageCode !== null) {
            $this->writeLanguageCookie($preferredLanguageCode);
        }

        // Họ tên (sidebar) và ngôn ngữ hiển thị trên Layout -> tải lại cả trang.
        return $this->operationResponse($updateResult, 'Your profile has been updated.', route('account.profile'), ClientAfterSuccess::ReloadPage);
    }

    // ------------------------------------------------------------------ Helpers

    private function signIn(Request $request, UserAccount $userAccount): void
    {
        Auth::login($userAccount);
        $request->session()->regenerate();     // chống session fixation
        $request->session()->put(ValidateUserSession::SecurityStampSessionKey, strtoupper((string) $userAccount->SecurityStamp));
    }

    private function writeLanguageCookie(string $languageCode): void
    {
        Cookie::queue(config('mes.language_cookie_name'), $languageCode, 60 * 24 * 365);
    }
}
