/* =============================================================================
   002_Create_Table_UserAccount
   Tài khoản người dùng. Tên bảng là UserAccount (không dùng "User" vì là từ khóa SQL).

   AccountStatus : 1 = PendingApproval (đã đăng ký, chờ Admin duyệt)
                   2 = Active
                   3 = Locked          (Admin khóa thủ công)
                   4 = Disabled        (ngừng sử dụng)
   Khóa tạm do đăng nhập sai nhiều lần dùng LockoutEndAt, không đổi AccountStatus.
   SecurityStamp : đổi giá trị mỗi khi mật khẩu / quyền thay đổi -> cookie cũ hết hiệu lực,
                   cache quyền bị làm mới.
   ============================================================================= */
SET ANSI_NULLS ON;
SET QUOTED_IDENTIFIER ON;
GO
IF OBJECT_ID(N'dbo.UserAccount', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.UserAccount
    (
        Id                      INT IDENTITY(1, 1)  NOT NULL,
        UserName                NVARCHAR(50)        NOT NULL,
        Email                   NVARCHAR(256)       NOT NULL,
        FullName                NVARCHAR(150)       NOT NULL,
        PasswordHash            VARCHAR(100)        NOT NULL,
        IsEmailVerified         BIT                 NOT NULL CONSTRAINT DF_UserAccount_IsEmailVerified DEFAULT (0),
        AccountStatus           TINYINT             NOT NULL CONSTRAINT DF_UserAccount_AccountStatus DEFAULT (1),
        FailedLoginCount        INT                 NOT NULL CONSTRAINT DF_UserAccount_FailedLoginCount DEFAULT (0),
        LockoutEndAt            DATETIME2(3)        NULL,
        MustChangePassword      BIT                 NOT NULL CONSTRAINT DF_UserAccount_MustChangePassword DEFAULT (0),
        PreferredLanguageCode   VARCHAR(10)         NULL,
        LastLoginAt             DATETIME2(3)        NULL,
        SecurityStamp           UNIQUEIDENTIFIER    NOT NULL CONSTRAINT DF_UserAccount_SecurityStamp DEFAULT (NEWID()),

        CreatedAt               DATETIME2(3)        NOT NULL CONSTRAINT DF_UserAccount_CreatedAt DEFAULT (SYSDATETIME()),
        CreatedByUserAccountId  INT                 NULL,
        UpdatedAt               DATETIME2(3)        NULL,
        UpdatedByUserAccountId  INT                 NULL,
        IsDeleted               BIT                 NOT NULL CONSTRAINT DF_UserAccount_IsDeleted DEFAULT (0),

        CONSTRAINT PK_UserAccount PRIMARY KEY CLUSTERED (Id),
        CONSTRAINT CK_UserAccount_AccountStatus CHECK (AccountStatus IN (1, 2, 3, 4)),
        CONSTRAINT FK_UserAccount_Language FOREIGN KEY (PreferredLanguageCode) REFERENCES dbo.Language (LanguageCode)
    );

    /* Unique chỉ trên bản ghi chưa xóa mềm -> xóa mềm xong có thể đăng ký lại cùng email */
    CREATE UNIQUE NONCLUSTERED INDEX UQ_UserAccount_UserName ON dbo.UserAccount (UserName) WHERE IsDeleted = 0;
    CREATE UNIQUE NONCLUSTERED INDEX UQ_UserAccount_Email    ON dbo.UserAccount (Email)    WHERE IsDeleted = 0;
END
GO
