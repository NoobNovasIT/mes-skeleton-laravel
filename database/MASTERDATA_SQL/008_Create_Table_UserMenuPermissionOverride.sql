/* =============================================================================
   008_Create_Table_UserMenuPermissionOverride
   Ghi đè quyền cho riêng 1 user trên 1 menu. Nếu có dòng ở đây thì dùng
   AccessLevel này, bỏ qua quyền tổng hợp từ Role (kể cả khi thấp hơn).
   ============================================================================= */
SET ANSI_NULLS ON;
SET QUOTED_IDENTIFIER ON;
GO
IF OBJECT_ID(N'dbo.UserMenuPermissionOverride', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.UserMenuPermissionOverride
    (
        UserAccountId           INT             NOT NULL,
        MenuId                  INT             NOT NULL,
        AccessLevel             TINYINT         NOT NULL,
        Reason                  NVARCHAR(300)   NULL,
        UpdatedAt               DATETIME2(3)    NOT NULL CONSTRAINT DF_UserMenuPermissionOverride_UpdatedAt DEFAULT (SYSDATETIME()),
        UpdatedByUserAccountId  INT             NULL,

        CONSTRAINT PK_UserMenuPermissionOverride PRIMARY KEY CLUSTERED (UserAccountId, MenuId),
        CONSTRAINT FK_UserMenuPermissionOverride_UserAccount FOREIGN KEY (UserAccountId) REFERENCES dbo.UserAccount (Id),
        CONSTRAINT FK_UserMenuPermissionOverride_Menu        FOREIGN KEY (MenuId)        REFERENCES dbo.Menu (Id),
        CONSTRAINT CK_UserMenuPermissionOverride_AccessLevel CHECK (AccessLevel IN (0, 1, 2))
    );
END
GO
