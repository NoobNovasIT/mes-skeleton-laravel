/* =============================================================================
   003_Create_Table_Role
   Nhóm quyền. IsSystemRole = 1: không được xóa/đổi RoleCode, tự động nhận
   FullAccess cho mọi menu mới tạo (ví dụ ADMIN).
   ============================================================================= */
SET ANSI_NULLS ON;
SET QUOTED_IDENTIFIER ON;
GO
IF OBJECT_ID(N'dbo.Role', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.Role
    (
        Id                      INT IDENTITY(1, 1)  NOT NULL,
        RoleCode                VARCHAR(50)         NOT NULL,
        RoleName                NVARCHAR(100)       NOT NULL,
        Description             NVARCHAR(500)       NULL,
        IsSystemRole            BIT                 NOT NULL CONSTRAINT DF_Role_IsSystemRole DEFAULT (0),

        CreatedAt               DATETIME2(3)        NOT NULL CONSTRAINT DF_Role_CreatedAt DEFAULT (SYSDATETIME()),
        CreatedByUserAccountId  INT                 NULL,
        UpdatedAt               DATETIME2(3)        NULL,
        UpdatedByUserAccountId  INT                 NULL,
        IsDeleted               BIT                 NOT NULL CONSTRAINT DF_Role_IsDeleted DEFAULT (0),

        CONSTRAINT PK_Role PRIMARY KEY CLUSTERED (Id)
    );

    CREATE UNIQUE NONCLUSTERED INDEX UQ_Role_RoleCode ON dbo.Role (RoleCode) WHERE IsDeleted = 0;
END
GO
