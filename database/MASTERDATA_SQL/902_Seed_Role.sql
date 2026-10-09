/* =============================================================================
   902_Seed_Role
   ADMIN là Role hệ thống duy nhất của skeleton. Các Role nghiệp vụ
   (Operator, Engineer, Viewer...) do Admin tạo trên giao diện theo từng công ty.
   ============================================================================= */
SET ANSI_NULLS ON;
SET QUOTED_IDENTIFIER ON;
GO
IF NOT EXISTS (SELECT 1 FROM dbo.Role WHERE RoleCode = 'ADMIN' AND IsDeleted = 0)
BEGIN
    INSERT INTO dbo.Role (RoleCode, RoleName, Description, IsSystemRole)
    VALUES ('ADMIN', N'Administrator', N'System administrator - full access to every menu', 1);
END
GO
