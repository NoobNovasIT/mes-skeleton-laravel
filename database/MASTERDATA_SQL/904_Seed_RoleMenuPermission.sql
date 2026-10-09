/* =============================================================================
   904_Seed_RoleMenuPermission
   Role hệ thống (IsSystemRole = 1) nhận FullAccess (2) cho mọi Page.
   Chạy lại an toàn: chỉ thêm quyền còn thiếu và nâng quyền về FullAccess.
   ============================================================================= */
SET ANSI_NULLS ON;
SET QUOTED_IDENTIFIER ON;
GO
MERGE dbo.RoleMenuPermission AS Target
USING (
    SELECT      Role.Id AS RoleId, Menu.Id AS MenuId
    FROM        dbo.Role
    CROSS JOIN  dbo.Menu
    WHERE       Role.IsSystemRole = 1 AND Role.IsDeleted = 0
      AND       Menu.MenuType = 3     AND Menu.IsDeleted = 0
) AS Source
ON Target.RoleId = Source.RoleId AND Target.MenuId = Source.MenuId
WHEN MATCHED AND Target.AccessLevel <> 2 THEN
    UPDATE SET AccessLevel = 2, UpdatedAt = SYSDATETIME()
WHEN NOT MATCHED THEN
    INSERT (RoleId, MenuId, AccessLevel) VALUES (Source.RoleId, Source.MenuId, 2);
GO
