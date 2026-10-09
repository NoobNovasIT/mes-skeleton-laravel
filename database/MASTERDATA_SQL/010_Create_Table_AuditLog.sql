/* =============================================================================
   010_Create_Table_AuditLog
   Nhật ký thao tác. Chỉ INSERT, không UPDATE / DELETE từ ứng dụng.
   ActionType : chuỗi hằng trong Mes.Common.Constants.AuditActionTypes
                (Login, LoginFailed, Logout, Create, Update, Delete, PermissionChange, ...)
   OldValueJson / NewValueJson : JSON các cột thay đổi (SQL Server 2019 không có kiểu JSON).
   ============================================================================= */
SET ANSI_NULLS ON;
SET QUOTED_IDENTIFIER ON;
GO
IF OBJECT_ID(N'dbo.AuditLog', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.AuditLog
    (
        Id              BIGINT IDENTITY(1, 1)   NOT NULL,
        UserAccountId   INT                     NULL,
        UserName        NVARCHAR(256)           NULL,
        ActionType      VARCHAR(50)             NOT NULL,
        EntityName      VARCHAR(100)            NULL,
        EntityId        VARCHAR(100)            NULL,
        Description     NVARCHAR(500)           NULL,
        OldValueJson    NVARCHAR(MAX)           NULL,
        NewValueJson    NVARCHAR(MAX)           NULL,
        IpAddress       VARCHAR(45)             NULL,
        CreatedAt       DATETIME2(3)            NOT NULL CONSTRAINT DF_AuditLog_CreatedAt DEFAULT (SYSDATETIME()),

        CONSTRAINT PK_AuditLog PRIMARY KEY CLUSTERED (Id)
    );

    CREATE NONCLUSTERED INDEX IX_AuditLog_CreatedAt     ON dbo.AuditLog (CreatedAt DESC);
    CREATE NONCLUSTERED INDEX IX_AuditLog_UserAccountId ON dbo.AuditLog (UserAccountId, CreatedAt DESC);
    CREATE NONCLUSTERED INDEX IX_AuditLog_EntityName    ON dbo.AuditLog (EntityName, EntityId);
END
GO
