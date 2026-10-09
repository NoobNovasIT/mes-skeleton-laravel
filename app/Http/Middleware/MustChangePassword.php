<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * = MustChangePasswordFilter.cs: Admin đầu tiên / bị Admin reset mật khẩu -> mọi request bị chuyển
 * tới trang đổi mật khẩu. Các route /Account/* không gắn middleware này.
 */
final class MustChangePassword
{
    public function handle(Request $request, Closure $next): Response
    {
        $userSessionState = ValidateUserSession::current();
        if ($userSessionState === null || ! $userSessionState->mustChangePassword) {
            return $next($request);
        }

        return $request->isAjaxRequest()
            ? response()->json(['isSuccess' => false, 'message' => localize('You must change your password before continuing.')], 403)
            : redirect()->route('account.change-password');
    }
}
