<?php

namespace App\Providers;

use App\Http\Middleware\ValidateUserSession;
use App\Http\Navigation\PageNavigationContextProvider;
use App\Http\Permissions\PagePermission;
use App\Services\Caching\UserScopedCache;
use App\Services\Languages\LanguageService;
use App\Services\SystemSettings\SystemSettingService;
use App\Support\Enums\AccessLevel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

/**
 * = Program.cs + AddMesServices / AddMesWebInfrastructure bên ASP.NET.
 *
 * Laravel tự khởi tạo (auto-wiring) mọi class cụ thể qua constructor, nên KHÔNG cần đăng ký từng
 * Service như services.AddScoped<IRoleService, RoleService>(). Chỉ đăng ký những thứ đặc biệt:
 */
class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Singleton: không giữ trạng thái theo request (giống AddSingleton).
        $this->app->singleton(UserScopedCache::class);
        $this->app->singleton(SystemSettingService::class);
        $this->app->singleton(LanguageService::class);

        // Scoped: 1 instance / request (giống AddScoped) - sidebar, tab Module, breadcrumb dùng chung kết quả.
        $this->app->scoped(PageNavigationContextProvider::class);
    }

    public function boot(): void
    {
        $this->registerRequestMacros();
        $this->registerBladeDirectives();
        $this->registerViewComposers();
    }

    /** = HttpRequestExtensions.cs: request()->isDialogRequest(), request()->isAjaxRequest(). */
    private function registerRequestMacros(): void
    {
        Request::macro('isDialogRequest', function (): bool {
            /** @var Request $this */
            return strcasecmp((string) $this->header('X-Mes-Dialog'), 'true') === 0;
        });

        Request::macro('isAjaxRequest', function (): bool {
            /** @var Request $this */
            return $this->ajax() || str_contains(strtolower((string) $this->header('Accept')), 'application/json');
        });
    }

    /**
     * = TagHelper mes-require-access. Không render phần bên trong nếu thiếu quyền trên page hiện tại:
     *
     *   @requireAccess('FullAccess')
     *       <button type="submit" class="btn btn-primary">Save</button>
     *   @endrequireAccess
     *
     * CHỈ là ẩn giao diện. Middleware mes.permission vẫn chặn request ghi ở server.
     */
    private function registerBladeDirectives(): void
    {
        Blade::if('requireAccess', function (string $requiredAccessLevel = 'FullAccess'): bool {
            $requiredLevel = AccessLevel::parse($requiredAccessLevel) ?? AccessLevel::FullAccess;

            return PagePermission::current()->accessLevel->value >= $requiredLevel->value;
        });
    }

    /** Dữ liệu dùng chung cho layout (= @inject trong _Layout.cshtml). */
    private function registerViewComposers(): void
    {
        View::composer(['layouts.app', 'layouts.auth'], function ($view) {
            $view->with('branding', app(SystemSettingService::class)->getBranding());
        });

        View::composer('layouts.app', function ($view) {
            $view->with('userSessionState', ValidateUserSession::current());
            $view->with('pageNavigationContext', app(PageNavigationContextProvider::class)->get());
        });

        View::composer('partials.language-switcher', function ($view) {
            $view->with('languages', app(LanguageService::class)->listActive());
        });
    }
}
