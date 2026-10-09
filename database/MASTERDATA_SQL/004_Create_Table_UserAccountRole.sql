/* =============================================================================
   004_Create_Table_UserAccountRole
   Bảng nối User - Role (1 user có nhiều role). Xóa cứng khi gỡ role;
   lịch sử thay đổi được lưu ở AuditLog.
   ============================================================================= */
SET ANSI_NULLS ON;
SET QUOTED_IDENTIFIER ON;
GO
IF OBJECT_ID(N'dbo.UserAccountRole', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.UserAccountRole
    (
        UserAccountId           INT             NOT NULL,
        RoleId                  INT             NOT NULL,
        CreatedAt               DATETIME2(3)    NOT NULL CONSTRAINT DF_UserAccountRole_CreatedAt DEFAULT (SYSDATETIME()),
        CreatedByUserAccountId  INT             NULL,

        CONSTRAINT PK_UserAccountRole PRIMARY KEY CLUSTERED (UserAccountId, RoleId),
        CONSTRAINT FK_UserAccountRole_UserAccount FOREIGN KEY (UserAccountId) REFERENCES dbo.UserAccount (Id),
        CONSTRAINT FK_UserAccountRole_Role        FOREIGN KEY (RoleId)        REFERENCES dbo.Role (Id)
    );

    CREATE NONCLUSTERED INDEX IX_UserAccountRole_RoleId ON dbo.UserAccountRole (RoleId);
END
GO
