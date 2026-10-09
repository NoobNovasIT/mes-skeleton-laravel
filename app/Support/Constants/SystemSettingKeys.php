<?php

namespace App\Support\Constants;

/** = Mes.Common.Constants.SystemSettingKeys. Khớp cột SystemSetting.SettingKey (905_Seed_SystemSetting.sql). */
final class SystemSettingKeys
{
    public const BrandingApplicationName = 'Branding.ApplicationName';
    public const BrandingApplicationNameHighlight = 'Branding.ApplicationNameHighlight';
    public const BrandingCompanyName = 'Branding.CompanyName';
    public const BrandingLogoUrl = 'Branding.LogoUrl';
    public const BrandingPrimaryColor = 'Branding.PrimaryColor';
    public const BrandingLoginBackgroundVideoUrls = 'Branding.LoginBackgroundVideoUrls';

    public const SecurityLockoutMaxFailedAttempt = 'Security.LockoutMaxFailedAttempt';
    public const SecurityLockoutDurationMinute = 'Security.LockoutDurationMinute';
    public const SecurityEmailVerificationExpiryHour = 'Security.EmailVerificationExpiryHour';
    public const SecurityPasswordResetExpiryMinute = 'Security.PasswordResetExpiryMinute';
    public const SecurityPasswordMinimumLength = 'Security.PasswordMinimumLength';

    public const RegistrationIsSelfRegistrationEnabled = 'Registration.IsSelfRegistrationEnabled';
}
