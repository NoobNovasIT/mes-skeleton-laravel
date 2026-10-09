<?php

namespace App\Http\Middleware;

use App\Services\Languages\LanguageService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * = UseRequestLocalization + CookieRequestCultureProvider: đọc cookie ngôn ngữ, đặt locale cho request.
 * Sau đó localize('...') / __('...') đọc bản dịch trong lang/{locale}.json.
 */
final class SetLanguage
{
    public function handle(Request $request, Closure $next): Response
    {
        $languageCode = $request->cookie(config('mes.language_cookie_name'));

        App::setLocale(LanguageService::isSupported($languageCode) ? $languageCode : LanguageService::defaultLanguageCode());

        return $next($request);
    }
}
