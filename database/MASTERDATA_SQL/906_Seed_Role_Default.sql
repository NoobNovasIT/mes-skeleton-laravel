/* =============================================================================
   906_Seed_Role_Default
   Các Role nghiệp vụ mẫu (không phải Role hệ thống).
   Chỉ tạo Role còn thiếu theo RoleCode, không ghi đè tên / mô tả Admin đã sửa.
   Quyền của các Role này cấp trên trang System > Role Permissions.
   ============================================================================= */
SET ANSI_NULLS ON;
SET QUOTED_IDENTIFIER ON;
GO
SET NOCOUNT ON;

INSERT INTO dbo.Role (RoleCode, RoleName, Description, IsSystemRole)
SELECT      RoleSeed.RoleCode, RoleSeed.RoleName, RoleSeed.Description, 0
FROM        (VALUES
                ('MAINTENANCE',   N'Maintenance',   N'Maintenance Staff - Inspections & Anomalies'),
                ('INSPECTOR',     N'Inspector',     N'Inspector - View and Create Inspections'),
                ('CLIENT_VIEWER', N'Client Viewer', N'View Only - Read Only Access'),
                ('TECHNICIAN',    N'Technician',    N'Technician - Approvals and Reports'),
                ('SUPERVISOR',    N'Supervisor',    N'Supervisor Group Roles'),
                ('TA',            N'TA',            N'TA Roles'),
                ('TEST_ROLE',     N'Test Role',     N'Roles group for test.')
            ) AS RoleSeed (RoleCode, RoleName, Description)
WHERE       NOT EXISTS (SELECT 1 FROM dbo.Role WHERE Role.RoleCode = RoleSeed.RoleCode AND Role.IsDeleted = 0);
GO
