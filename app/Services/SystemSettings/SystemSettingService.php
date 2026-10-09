<?php

namespace App\Services\SystemSettings;

use App\Models\SystemSetting;
use App\Support\Constants\SystemSettingKeys;
use App\Support\Results\OperationResult;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/** = Mes.Service/SystemSettings/SystemSettingService.cs. Cache 10 phút, lưu trên giao diện thì xóa cache ngay. */
final class SystemSettingService
{
    private const SettingValueByKeyCacheKey = 'mes:system-setting:value-by-key';
    private const CacheDurationMinute = 10;

    private const PositiveIntegerSettingKeys = [
        SystemSettingKeys::SecurityLockoutMaxFailedAttempt,
        SystemSettingKeys::SecurityLockoutDurationMinute,
        SystemSettingKeys::SecurityEmailVerificationExpiryHour,
        SystemSettingKeys::SecurityPasswordResetExpiryMinute,
        SystemSettingKeys::SecurityPasswordMinimumLength,
    ];

    public function getBranding(): BrandingSettingDto
    {
        $settingValueByKey = $this->getCachedSettingValueByKey();

        return new BrandingSettingDto(
            applicationName: $this->getText($settingValueByKey, SystemSettingKeys::BrandingApplicationName, 'MES'),
            applicationNameHighlight: $this->getText($settingValueByKey, SystemSettingKeys::BrandingApplicationNameHighlight, ''),
            companyName: $this->getText($settingValueByKey, SystemSettingKeys::BrandingCompanyName, ''),
            logoUrl: $this->getText($settingValueByKey, SystemSettingKeys::BrandingLogoUrl, ''),
            primaryColor: $this->getText($settingValueByKey, SystemSettingKeys::BrandingPrimaryColor, '#3b5bdb'),
            loginBackgroundVideoUrls: $this->splitLocalUrls($this->getText($settingValueByKey, SystemSettingKeys::BrandingLoginBackgroundVideoUrls, '')),
        );
    }

    public function getSecurityPolicy(): SecurityPolicySettingDto
    {
        $settingValueByKey = $this->getCachedSettingValueByKey();

        return new SecurityPolicySettingDto(
            lockoutMaxFailedAttempt: $this->getInteger($settingValueByKey, SystemSettingKeys::SecurityLockoutMaxFailedAttempt, 5),
            lockoutDurationMinute: $this->getInteger($settingValueByKey, SystemSettingKeys::SecurityLockoutDurationMinute, 15),
            passwordMinimumLength: $this->getInteger($settingValueByKey, SystemSettingKeys::SecurityPasswordMinimumLength, 8),
            isSelfRegistrationEnabled: ($settingValueByKey[SystemSettingKeys::RegistrationIsSelfRegistrationEnabled] ?? 'true') === 'true',
        );
    }

    /** @return list<SystemSettingItemDto> */
    public function list(): array
    {
        return SystemSetting::query()
            ->orderBy('SettingGroup')
            ->orderBy('SettingKey')
            ->get()
            ->map(fn (SystemSetting $systemSetting) => new SystemSettingItemDto(
                $systemSetting->SettingKey,
                $systemSetting->SettingValue,
                $systemSetting->SettingGroup,
                $systemSetting->Description,
                $systemSetting->UpdatedAt,
            ))
            ->all();
    }

    /** Giá trị sai định dạng (số âm, màu không phải #RRGGBB) bị bỏ qua, giữ giá trị cũ. */
    public function update(array $settingValueByKey): OperationResult
    {
        DB::transaction(function () use ($settingValueByKey) {
            $systemSettings = SystemSetting::query()->whereIn('SettingKey', array_keys($settingValueByKey))->get();
            foreach ($systemSettings as $systemSetting) {
                $newValue = trim((string) ($settingValueByKey[$systemSetting->SettingKey] ?? ''));
                if ($this->isValidSettingValue($systemSetting->SettingKey, $newValue) && $systemSetting->SettingValue !== $newValue) {
                    $systemSetting->SettingValue = $newValue;
                    $systemSetting->save();
                }
            }
        });

        Cache::forget(self::SettingValueByKeyCacheKey);

        return OperationResult::success();
    }

    private function isValidSettingValue(string $settingKey, string $settingValue): bool
    {
        if (in_array($settingKey, self::PositiveIntegerSettingKeys, true)) {
            return ctype_digit($settingValue) && (int) $settingValue > 0;
        }

        return match ($settingKey) {
            SystemSettingKeys::BrandingPrimaryColor => preg_match('/^#[0-9a-fA-F]{6}$/', $settingValue) === 1,
            SystemSettingKeys::RegistrationIsSelfRegistrationEnabled => in_array($settingValue, ['true', 'false'], true),
            SystemSettingKeys::BrandingApplicationName => $settingValue !== '',
            default => true,
        };
    }

    /** @return array<string, ?string> */
    private function getCachedSettingValueByKey(): array
    {
        return Cache::remember(
            self::SettingValueByKeyCacheKey,
            now()->addMinutes(self::CacheDurationMinute),
            fn () => SystemSetting::query()->pluck('SettingValue', 'SettingKey')->all());
    }

    private function getText(array $settingValueByKey, string $settingKey, string $defaultValue): string
    {
        return $settingValueByKey[$settingKey] ?? $defaultValue;
    }

    private function getInteger(array $settingValueByKey, string $settingKey, int $defaultValue): int
    {
        $settingValue = (string) ($settingValueByKey[$settingKey] ?? '');

        return ctype_digit($settingValue) && (int) $settingValue > 0 ? (int) $settingValue : $defaultValue;
    }

    /** Chỉ nhận URL nội bộ bắt đầu bằng "/" (không nhận "//host"). @return list<string> */
    private function splitLocalUrls(string $urlListText): array
    {
        $urls = array_map('trim', explode(';', $urlListText));

        return array_values(array_filter($urls, fn (string $url) => str_starts_with($url, '/') && ! str_starts_with($url, '//')));
    }
}
