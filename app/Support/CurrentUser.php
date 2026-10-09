<?php

namespace App\Support;

use App\Models\UserAccount;
use Illuminate\Support\Facades\Auth;

/**
 * = ICurrentUserAccessor: người đang thao tác + IP, dùng cho cột audit và AuditLog.
 * Laravel không cần DI cho việc này vì Auth / request() là global theo request.
 */
final class CurrentUser
{
    public static function userAccountId(): ?int
    {
        $userAccountId = Auth::id();

        return $userAccountId === null ? null : (int) $userAccountId;
    }

    public static function userName(): ?string
    {
        $userAccount = Auth::user();

        return $userAccount instanceof UserAccount ? $userAccount->UserName : null;
    }

    public static function ipAddress(): ?string
    {
        return app()->runningInConsole() ? null : request()->ip();
    }
}
