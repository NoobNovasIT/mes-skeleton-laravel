/* =============================================================================
   011_Create_Table_SystemSetting
   Cấu hình vận hành chỉnh được trên giao diện (branding, chính sách khóa tài khoản...).
   SettingKey là hằng trong Mes.Common.Constants.SystemSettingKeys.
   Cấu hình hạ tầng (connection string, SMTP password, JWT key) KHÔNG để ở đây
   mà để trong appsettings / user-secrets.
   ============================================================================= */
SET ANSI_NULLS ON;
SET QUOTED_IDENTIFIER ON;
GO
IF OBJECT_ID(N'dbo.SystemSetting', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.SystemSetting
    (
        SettingKey              VARCHAR(100)    NOT NULL,
        SettingValue            NVARCHAR(1000)  NULL,
        SettingGroup            VARCHAR(50)     NOT NULL,
        Description             NVARCHAR(300)   NULL,
        UpdatedAt               DATETIME2(3)    NOT NULL CONSTRAINT DF_SystemSetting_UpdatedAt DEFAULT (SYSDATETIME()),
        UpdatedByUserAccountId  INT             NULL,

        CONSTRAINT PK_SystemSetting PRIMARY KEY CLUSTERED (SettingKey)
    );
END
GO
