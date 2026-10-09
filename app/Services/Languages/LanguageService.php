<?php

namespace App\Services\Languages;

use App\Models\Language;
use Illuminate\Support\Facades\Cache;

/** = Mes.Service/Languages/LanguageService.cs. */
final class LanguageService
{
    /** @return list<LanguageDto> */
    public function listActive(): array
    {
        return Cache::remember('mes:language:active', now()->addMinutes(10), fn () => Language::query()
            ->where('IsActive', true)
            ->whereIn('LanguageCode', self::supportedLanguageCodes())
            ->orderBy('SortOrder')
            ->get()
            ->map(fn (Language $language) => new LanguageDto($language->LanguageCode, $language->LanguageName))
            ->all());
    }

    /** = Localization:SupportedLanguageCodes trong appsettings (config/mes.php). @return list<string> */
    public static function supportedLanguageCodes(): array
    {
        return config('mes.supported_language_codes');
    }

    public static function defaultLanguageCode(): string
    {
        return config('mes.default_language_code');
    }

    public static function isSupported(?string $languageCode): bool
    {
        return $languageCode !== null && in_array($languageCode, self::supportedLanguageCodes(), true);
    }
}
