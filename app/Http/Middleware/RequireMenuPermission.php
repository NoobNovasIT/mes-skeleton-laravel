<?php

namespace App\Http\Middleware;

use App\Http\Permissions\PagePermission;
use App\Services\Permissions\PermissionService;
use App\Support\Enums\AccessLevel;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * = [RequireMenuPermission(MenuCodes.X)] + RequireMenuPermissionFilter.cs.
 *
 * Gắn trong routes/web.php:   ->middleware('mes.permission:'.MenuCodes::SystemRole)
 *   GET / HEAD            -> cần ReadOnly
 *   POST / PUT / DELETE   -> cần FullAccess
 *   Tham số thứ 2 ghi đè mức tối thiểu, ví dụ POST chỉ đọc (form tìm kiếm):
 *       ->middleware('mes.permission:'.MenuCodes::X.',ReadOnly')   (= [AllowReadOnlySubmit])
 */
final class RequireMenuPermission
{
    public function __construct(
        private readonly PermissionService $permissionService,
    ) {
    }

    public function handle(Request $request, Closure $next, string $menuCode, ?string $minimumAccessLevel = null): Response
    {
        $userAccountId = Auth::id();
        if ($userAccountId === null) {
            return redirect()->guest(route('account.login'));
        }

        $accessLevel = $this->permissionService->getAccessLevel((int) $userAccountId, $menuCode);
        PagePermission::setCurrent(new PagePermission($menuCode, $accessLevel));

        $requiredAccessLevel = AccessLevel::parse($minimumAccessLevel)
            ?? ($request->isMethodSafe() ? AccessLevel::ReadOnly : AccessLevel::FullAccess);

        if ($accessLevel->value >= $requiredAccessLevel->value) {
            return $next($request);
        }

        // Mọi lần bị chặn đều ghi log (storage/logs/laravel.log) để dò lỗi MenuCode sai chính tả.
        Log::warning('Access denied: user {userAccountId} has {accessLevel} on {menuCode}, requires {requiredAccessLevel} for {method} {path}', [
            'userAccountId' => $userAccountId,
            'accessLevel' => $accessLevel->name,
            'menuCode' => $menuCode,
            'requiredAccessLevel' => $requiredAccessLevel->name,
            'method' => $request->method(),
            'path' => $request->path(),
        ]);

        if ($request->isAjaxRequest()) {
            return response()->json(['isSuccess' => false, 'message' => localize('You do not have permission to perform this action.')], 403);
        }

        return redirect()->route('home.access-denied');
    }
}
