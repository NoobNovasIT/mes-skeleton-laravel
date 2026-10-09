/* =============================================================================
   102_Create_Procedure_usp_Security_Permission_GetMatrixByRoleId
   Ma trận quyền của 1 Role: toàn bộ menu (kể cả IsActive = 0) kèm AccessLevel
   hiện tại của Role trên từng Page. Dùng cho trang Admin > Role Permission.
   ============================================================================= */
SET ANSI_NULLS ON;
SET QUOTED_IDENTIFIER ON;
GO
CREATE OR ALTER PROCEDURE dbo.usp_Security_Permission_GetMatrixByRoleId
    @RoleId         INT,
    @LanguageCode   VARCHAR(10)
AS
BEGIN
    SET NOCOUNT ON;

    SELECT      Menu.Id                 AS MenuId,
                Menu.ParentMenuId,
                Menu.MenuCode,
                Menu.MenuType,
                COALESCE(MenuTranslation.DisplayName, Menu.DefaultName) AS DisplayName,
                Menu.IconClass,
                Menu.SortOrder,
                Menu.IsActive,
                CAST(COALESCE(RoleMenuPermission.AccessLevel, 0) AS TINYINT) AS AccessLevel
    FROM        dbo.Menu
    LEFT JOIN   dbo.MenuTranslation
           ON   MenuTranslation.MenuId = Menu.Id AND MenuTranslation.LanguageCode = @LanguageCode
    LEFT JOIN   dbo.RoleMenuPermission
           ON   RoleMenuPermission.MenuId = Menu.Id AND RoleMenuPermission.RoleId = @RoleId
    WHERE       Menu.IsDeleted = 0
    ORDER BY    Menu.SortOrder, Menu.Id;
END
GO
