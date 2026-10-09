/* =============================================================================
   007_Create_Table_RoleMenuPermission
   Quyền của Role trên từng Menu (chỉ áp dụng cho MenuType = 3 Page).
   AccessLevel : 0 = NoAccess   (menu ẩn, vào URL bị chặn)
                 1 = ReadOnly   (vào xem được, mọi thao tác ghi bị chặn)
                 2 = FullAccess (xem, thêm, sửa, xóa)
   Không có dòng = NoAccess.
   ============================================================================= */
SET ANSI_NULLS ON;
SET QUOTED_IDENTIFIER ON;
GO
IF OBJECT_ID(N'dbo.RoleMenuPermission', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.RoleMenuPermission
    (
        RoleId                  INT             NOT NULL,
        MenuId                  INT             NOT NULL,
        AccessLevel             TINYINT         NOT NULL,
        UpdatedAt               DATETIME2(3)    NOT NULL CONSTRAINT DF_RoleMenuPermission_UpdatedAt DEFAULT (SYSDATETIME()),
        UpdatedByUserAccountId  INT             NULL,

        CONSTRAINT PK_RoleMenuPermission PRIMARY KEY CLUSTERED (RoleId, MenuId),
        CONSTRAINT FK_RoleMenuPermission_Role FOREIGN KEY (RoleId) REFERENCES dbo.Role (Id),
        CONSTRAINT FK_RoleMenuPermission_Menu FOREIGN KEY (MenuId) REFERENCES dbo.Menu (Id),
        CONSTRAINT CK_RoleMenuPermission_AccessLevel CHECK (AccessLevel IN (0, 1, 2))
    );

    CREATE NONCLUSTERED INDEX IX_RoleMenuPermission_MenuId ON dbo.RoleMenuPermission (MenuId);
END
GO
