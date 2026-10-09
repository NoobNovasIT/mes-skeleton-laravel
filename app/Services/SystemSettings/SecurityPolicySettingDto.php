<?php

namespace App\Services\SystemSettings;

final class SecurityPolicySettingDto
{
    public function __construct(
        public readonly int $lockoutMaxFailedAttempt,
        public readonly int $lockoutDurationMinute,
        public readonly int $passwordMinimumLength,
        public readonly bool $isSelfRegistrationEnabled,
    ) {
    }
}
