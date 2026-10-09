/* =============================================================================
   009_Create_Table_UserAccountToken
   Token 1 lần dùng cho xác thực email và đặt lại mật khẩu.
   TokenPurpose : 1 = EmailVerification, 2 = PasswordReset
   Chỉ lưu SHA-256 của token (TokenHash). Token gốc chỉ nằm trong link email,
   lộ database cũng không dùng lại được.
   ============================================================================= */
SET ANSI_NULLS ON;
SET QUOTED_IDENTIFIER ON;
GO
IF OBJECT_ID(N'dbo.UserAccountToken', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.UserAccountToken
    (
        Id              BIGINT IDENTITY(1, 1)   NOT NULL,
        UserAccountId   INT                     NOT NULL,
        TokenPurpose    TINYINT                 NOT NULL,
        TokenHash       CHAR(64)                NOT NULL,
        ExpiresAt       DATETIME2(3)            NOT NULL,
        UsedAt          DATETIME2(3)            NULL,
        CreatedAt       DATETIME2(3)            NOT NULL CONSTRAINT DF_UserAccountToken_CreatedAt DEFAULT (SYSDATETIME()),
        CreatedIpAddress VARCHAR(45)            NULL,

        CONSTRAINT PK_UserAccountToken PRIMARY KEY CLUSTERED (Id),
        CONSTRAINT FK_UserAccountToken_UserAccount FOREIGN KEY (UserAccountId) REFERENCES dbo.UserAccount (Id),
        CONSTRAINT CK_UserAccountToken_TokenPurpose CHECK (TokenPurpose IN (1, 2))
    );

    CREATE UNIQUE NONCLUSTERED INDEX UQ_UserAccountToken_TokenHash ON dbo.UserAccountToken (TokenHash);
    CREATE NONCLUSTERED INDEX IX_UserAccountToken_UserAccountId ON dbo.UserAccountToken (UserAccountId, TokenPurpose);
END
GO
