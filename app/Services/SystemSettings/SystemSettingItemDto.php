<?php

namespace App\Services\SystemSettings;

use DateTimeInterface;

final class SystemSettingItemDto
{
    public function __construct(
        public readonly string $settingKey,
        public readonly ?string $settingValue,
        public readonly string $settingGroup,
        public readonly ?string $description,
        public readonly ?DateTimeInterface $updatedAt,
    ) {
    }
}
