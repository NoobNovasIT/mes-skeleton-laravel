<?php

namespace App\Services\SystemSettings;

final class BrandingSettingDto
{
    /** @param list<string> $loginBackgroundVideoUrls */
    public function __construct(
        public readonly string $applicationName,
        public readonly string $applicationNameHighlight,
        public readonly string $companyName,
        public readonly string $logoUrl,
        public readonly string $primaryColor,
        public readonly array $loginBackgroundVideoUrls,
    ) {
    }
}
