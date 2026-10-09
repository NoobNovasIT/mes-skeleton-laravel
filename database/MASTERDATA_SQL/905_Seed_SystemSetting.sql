/* =============================================================================
   905_Seed_SystemSetting
   SettingKey phải khớp Mes.Common.Constants.SystemSettingKeys.
   Chỉ thêm key còn thiếu, KHÔNG ghi đè SettingValue Admin đã chỉnh.

   Description viết bằng tiếng Anh vì Mes.Web dùng nó làm khóa localization
   (bản dịch tiếng Việt nằm trong Resources/SharedResource.vi.resx).
   ============================================================================= */
SET ANSI_NULLS ON;
SET QUOTED_IDENTIFIER ON;
GO
MERGE dbo.SystemSetting AS Target
USING (VALUES
    ('Branding.ApplicationName',              N'MES',       'Branding',     N'Application name shown on the sidebar and browser title'),
    ('Branding.ApplicationNameHighlight',     N'',          'Branding',     N'Highlighted text after the application name (leave empty if not used)'),
    ('Branding.CompanyName',                  N'',          'Branding',     N'Company name shown on the sign in page and in emails'),
    ('Branding.LogoUrl',                      N'',          'Branding',     N'Logo URL, for example /images/branding/logo.png (empty = text brand)'),
    ('Branding.PrimaryColor',                 N'#3b5bdb',   'Branding',     N'Primary color'),
    ('Branding.LoginBackgroundVideoUrls',     N'/media/login/login_video_1.mp4;/media/login/login_video_2.mp4',
                                                            'Branding',     N'Sign in page background videos, separated by semicolons (empty = plain background)'),
    ('Security.LockoutMaxFailedAttempt',      N'5',         'Security',     N'Failed sign in attempts before temporary lockout'),
    ('Security.LockoutDurationMinute',        N'15',        'Security',     N'Temporary lockout duration (minutes)'),
    ('Security.EmailVerificationExpiryHour',  N'24',        'Security',     N'Email verification link lifetime (hours)'),
    ('Security.PasswordResetExpiryMinute',    N'30',        'Security',     N'Password reset link lifetime (minutes)'),
    ('Security.PasswordMinimumLength',        N'8',         'Security',     N'Minimum password length'),
    ('Registration.IsSelfRegistrationEnabled',N'true',      'Registration', N'Allow users to register an account by themselves')
) AS Source (SettingKey, SettingValue, SettingGroup, Description)
ON Target.SettingKey = Source.SettingKey
WHEN MATCHED THEN
    UPDATE SET SettingGroup = Source.SettingGroup, Description = Source.Description
WHEN NOT MATCHED THEN
    INSERT (SettingKey, SettingValue, SettingGroup, Description)
    VALUES (Source.SettingKey, Source.SettingValue, Source.SettingGroup, Source.Description);
GO
