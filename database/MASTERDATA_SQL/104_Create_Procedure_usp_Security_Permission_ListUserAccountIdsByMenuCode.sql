/* =============================================================================
   104_Create_Procedure_usp_Security_Permission_ListUserAccountIdsByMenuCode
   Danh sách user Active có quyền hiệu lực >= @MinimumAccessLevel trên một Page.
   Cùng quy tắc với usp_Security_Menu_GetNavigationByUserAccountId:
   override của user thắng, không có override thì lấy MAX quyền các Role.

   Dùng để chọn người nhận thông báo, ví dụ: thông báo "có tài khoản chờ duyệt"
   gửi tới mọi user có FullAccess trên SYSTEM_USER_ACCOUNT.
   ============================================================================= */
SET ANSI_NULLS ON;
SET QUOTED_IDENTIFIER ON;
GO
CREATE OR ALTER PROCEDURE dbo.usp_Security_Permission_ListUserAccountIdsByMenuCode
    @MenuCode           VARCHAR(100),
    @MinimumAccessLevel TINYINT
AS
BEGIN
    SET NOCOUNT ON;

    DECLARE @MenuId INT =
    (
        SELECT  Id
        FROM    dbo.Menu
        WHERE   MenuCode = @MenuCode AND MenuType = 3 AND IsActive = 1 AND IsDeleted = 0
    );

    IF @MenuId IS NULL
        RETURN;

    ;WITH RolePermission AS
    (
        SELECT      UserAccountRole.UserAccountId,
                    MAX(RoleMenuPermission.AccessLevel) AS AccessLevel
        FROM        dbo.UserAccountRole
        INNER JOIN  dbo.Role
                ON  Role.Id = UserAccountRole.RoleId AND Role.IsDeleted = 0
        INNER JOIN  dbo.RoleMenuPermission
                ON  RoleMenuPermission.RoleId = UserAccountRole.RoleId AND RoleMenuPermission.MenuId = @MenuId
        GROUP BY    UserAccountRole.UserAccountId
    )
    SELECT      UserAccount.Id
    FROM        dbo.UserAccount
    LEFT JOIN   RolePermission
           ON   RolePermission.UserAccountId = UserAccount.Id
    LEFT JOIN   dbo.UserMenuPermissionOverride
           ON   UserMenuPermissionOverride.UserAccountId = UserAccount.Id
          AND   UserMenuPermissionOverride.MenuId = @MenuId
    WHERE       UserAccount.AccountStatus = 2
      AND       UserAccount.IsDeleted = 0
      AND       COALESCE(UserMenuPermissionOverride.AccessLevel, RolePermission.AccessLevel, 0) >= @MinimumAccessLevel;
END
GO
