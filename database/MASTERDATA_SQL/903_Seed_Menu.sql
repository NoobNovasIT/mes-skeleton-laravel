/* =============================================================================
   903_Seed_Menu
   Menu hệ thống của skeleton (module SYSTEM). MenuCode phải khớp với
   Mes.Common.Constants.MenuCodes. Menu nghiệp vụ của từng công ty tạo trên giao
   diện Admin > Menu Management hoặc thêm script seed riêng.

   Cây:
     SYSTEM                        (Module)
       SYSTEM_SECURITY             (Group)
         SYSTEM_USER_ACCOUNT       (Page)  /System/UserAccount
         SYSTEM_ROLE               (Page)  /System/Role
         SYSTEM_ROLE_PERMISSION    (Page)  /System/RolePermission
       SYSTEM_CONFIGURATION        (Group)
         SYSTEM_MENU               (Page)  /System/Menu
         SYSTEM_SETTING            (Page)  /System/SystemSetting
       SYSTEM_MONITORING           (Group)
         SYSTEM_AUDIT_LOG          (Page)  /System/AuditLog
   ============================================================================= */
SET ANSI_NULLS ON;
SET QUOTED_IDENTIFIER ON;
GO
SET NOCOUNT ON;

DECLARE @MenuSeed TABLE
(
    MenuCode        VARCHAR(100),
    ParentMenuCode  VARCHAR(100),
    MenuType        TINYINT,
    DefaultName     NVARCHAR(100),
    VietnameseName  NVARCHAR(100),
    Url             NVARCHAR(300),
    IconClass       VARCHAR(100),
    SortOrder       INT
);

INSERT INTO @MenuSeed VALUES
('SYSTEM',                 NULL,                   1, N'System',          N'Hệ thống',          NULL,                    'fa-solid fa-gear',             900),
('SYSTEM_SECURITY',        'SYSTEM',               2, N'Security',        N'Bảo mật',           NULL,                    'fa-solid fa-shield-halved',    10),
('SYSTEM_USER_ACCOUNT',    'SYSTEM_SECURITY',      3, N'User Accounts',   N'Tài khoản',         N'/System/UserAccount',  'fa-solid fa-users',            10),
('SYSTEM_ROLE',            'SYSTEM_SECURITY',      3, N'Roles',           N'Nhóm quyền',        N'/System/Role',         'fa-solid fa-user-tag',         20),
('SYSTEM_ROLE_PERMISSION', 'SYSTEM_SECURITY',      3, N'Role Permissions',N'Phân quyền',        N'/System/RolePermission','fa-solid fa-key',             30),
('SYSTEM_CONFIGURATION',   'SYSTEM',               2, N'Configuration',   N'Cấu hình',          NULL,                    'fa-solid fa-sliders',          20),
('SYSTEM_MENU',            'SYSTEM_CONFIGURATION', 3, N'Menus',           N'Menu',              N'/System/Menu',         'fa-solid fa-bars',             10),
('SYSTEM_SETTING',         'SYSTEM_CONFIGURATION', 3, N'System Settings', N'Thiết lập hệ thống',N'/System/SystemSetting','fa-solid fa-gears',            20),
('SYSTEM_MONITORING',      'SYSTEM',               2, N'Monitoring',      N'Giám sát',          NULL,                    'fa-solid fa-display',          30),
('SYSTEM_AUDIT_LOG',       'SYSTEM_MONITORING',    3, N'Audit Log',       N'Nhật ký thao tác',  N'/System/AuditLog',     'fa-solid fa-clipboard-list',   10);

/* Thêm menu chưa có (không ghi đè menu Admin đã chỉnh trên giao diện) */
INSERT INTO dbo.Menu (MenuCode, MenuType, DefaultName, Url, IconClass, SortOrder)
SELECT      MenuSeed.MenuCode, MenuSeed.MenuType, MenuSeed.DefaultName, MenuSeed.Url, MenuSeed.IconClass, MenuSeed.SortOrder
FROM        @MenuSeed AS MenuSeed
WHERE       NOT EXISTS (SELECT 1 FROM dbo.Menu WHERE Menu.MenuCode = MenuSeed.MenuCode AND Menu.IsDeleted = 0);

/* Gắn cha cho menu vừa thêm */
UPDATE      ChildMenu
SET         ParentMenuId = ParentMenu.Id
FROM        dbo.Menu AS ChildMenu
INNER JOIN  @MenuSeed AS MenuSeed ON MenuSeed.MenuCode = ChildMenu.MenuCode
INNER JOIN  dbo.Menu AS ParentMenu ON ParentMenu.MenuCode = MenuSeed.ParentMenuCode AND ParentMenu.IsDeleted = 0
WHERE       ChildMenu.IsDeleted = 0
  AND       ChildMenu.ParentMenuId IS NULL;

/* Bản dịch */
INSERT INTO dbo.MenuTranslation (MenuId, LanguageCode, DisplayName)
SELECT      Menu.Id, Translation.LanguageCode, Translation.DisplayName
FROM        @MenuSeed AS MenuSeed
INNER JOIN  dbo.Menu ON Menu.MenuCode = MenuSeed.MenuCode AND Menu.IsDeleted = 0
CROSS APPLY (VALUES ('en', MenuSeed.DefaultName), ('vi', MenuSeed.VietnameseName)) AS Translation (LanguageCode, DisplayName)
WHERE       NOT EXISTS (SELECT 1 FROM dbo.MenuTranslation
                        WHERE MenuTranslation.MenuId = Menu.Id AND MenuTranslation.LanguageCode = Translation.LanguageCode);
GO
