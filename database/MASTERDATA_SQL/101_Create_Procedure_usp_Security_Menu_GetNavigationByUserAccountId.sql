/* =============================================================================
   101_Create_Procedure_usp_Security_Menu_GetNavigationByUserAccountId
   Trả toàn bộ menu đang hoạt động kèm quyền hiệu lực của user trên từng Page.

   Quy tắc quyền hiệu lực (EffectiveAccessLevel) cho Page:
     1. Có UserMenuPermissionOverride  -> dùng giá trị override
     2. Không có override              -> MAX(AccessLevel) của các Role user đang có
     3. Không có dòng nào              -> 0 (NoAccess)
   Module / Group trả EffectiveAccessLevel = 0; tầng Service tự ẩn Module/Group
   không còn Page con nào được phép xem.

   User không ở trạng thái Active (AccountStatus = 2) hoặc đã xóa -> không trả dòng nào.
   ============================================================================= */
SET ANSI_NULLS ON;
SET QUOTED_IDENTIFIER ON;
GO
CREATE OR ALTER PROCEDURE dbo.usp_Security_Menu_GetNavigationByUserAccountId
    @UserAccountId  INT,
    @LanguageCode   VARCHAR(10)
AS
BEGIN
    SET NOCOUNT ON;

    IF NOT EXISTS (SELECT 1 FROM dbo.UserAccount
                   WHERE Id = @UserAccountId AND AccountStatus = 2 AND IsDeleted = 0)
        RETURN;

    ;WITH RolePermission AS
    (
        SELECT      RoleMenuPermission.MenuId,
                    MAX(RoleMenuPermission.AccessLevel) AS AccessLevel
        FROM        dbo.UserAccountRole
        INNER JOIN  dbo.Role
                ON  Role.Id = UserAccountRole.RoleId AND Role.IsDeleted = 0
        INNER JOIN  dbo.RoleMenuPermission
                ON  RoleMenuPermission.RoleId = UserAccountRole.RoleId
        WHERE       UserAccountRole.UserAccountId = @UserAccountId
        GROUP BY    RoleMenuPermission.MenuId
    )
    SELECT      Menu.Id,
                Menu.ParentMenuId,
                Menu.MenuCode,
                Menu.MenuType,
                COALESCE(MenuTranslation.DisplayName, Menu.DefaultName) AS DisplayName,
                Menu.Url,
                Menu.IconClass,
                Menu.SortOrder,
                Menu.IsVisibleInNavigation,
                CAST(CASE WHEN Menu.MenuType = 3
                          THEN COALESCE(UserMenuPermissionOverride.AccessLevel, RolePermission.AccessLevel, 0)
                          ELSE 0
                     END AS TINYINT) AS EffectiveAccessLevel
    FROM        dbo.Menu
    LEFT JOIN   dbo.MenuTranslation
           ON   MenuTranslation.MenuId = Menu.Id AND MenuTranslation.LanguageCode = @LanguageCode
    LEFT JOIN   RolePermission
           ON   RolePermission.MenuId = Menu.Id
    LEFT JOIN   dbo.UserMenuPermissionOverride
           ON   UserMenuPermissionOverride.MenuId = Menu.Id
          AND   UserMenuPermissionOverride.UserAccountId = @UserAccountId
    WHERE       Menu.IsActive = 1
      AND       Menu.IsDeleted = 0
    ORDER BY    Menu.SortOrder, Menu.Id;
END
GO
