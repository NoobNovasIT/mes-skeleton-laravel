<?php

namespace App\Http\Middleware;

use App\Services\Accounts\AccountService;
use App\Support\Enums\AccountStatus;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * = CookieSessionValidator.cs. Phiên chỉ lưu UserAccountId + SecurityStamp; mỗi request đối chiếu lại
 * với database (có cache): user bị xóa / khóa / đổi mật khẩu ở nơi khác -> đăng xuất ngay request kế tiếp.
 */
final class ValidateUserSession
{
    public const SecurityStampSessionKey = 'mes.security-stamp';
    private const UserSessionStateAttributeKey = 'mes.user-session-state';

    public function __construct(
        private readonly AccountService $accountService,
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $userAccountId = Auth::id();
        if ($userAccountId === null) {
            return $next($request);
        }

        $userSessionState = $this->accountService->getSessionState((int) $userAccountId);
        $isSessionValid = $userSessionState !== null
            && $userSessionState->securityStamp === $request->session()->get(self::SecurityStampSessionKey)
            && ! in_array($userSessionState->accountStatus, [AccountStatus::Locked, AccountStatus::Disabled], true);

        if (! $isSessionValid) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return $request->isAjaxRequest()
                ? response()->json(['isSuccess' => false, 'message' => ''], 401)
                : redirect()->route('account.login');
        }

        $request->attributes->set(self::UserSessionStateAttributeKey, $userSessionState);

        return $next($request);
    }

    /** Layout / controller đọc trạng thái phiên đã kiểm tra (CookieSessionValidator.GetUserSessionState). */
    public static function current(): ?\App\Services\Accounts\UserSessionStateDto
    {
        return request()->attributes->get(self::UserSessionStateAttributeKey);
    }
}
