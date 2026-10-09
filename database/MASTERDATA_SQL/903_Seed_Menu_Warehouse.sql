/* =============================================================================
   903_Seed_Menu_Warehouse
   Module WAREHOUSE của bản demo (page Receive Material - bảng 013_Create_Table_MaterialReceipt).
   Đặt số 903_ để chạy TRƯỚC 904_Seed_RoleMenuPermission (Role ADMIN tự nhận FullAccess).

   Cây:
     WAREHOUSE                     (Module)
       WAREHOUSE_RAW               (Group)
         WAREHOUSE_RAW_RECEIVE     (Page)  /Warehouse/MaterialReceipt
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
('WAREHOUSE',             NULL,             1, N'Warehouse',        N'Kho',          NULL,                         'fa-solid fa-warehouse',      20),
('WAREHOUSE_RAW',         'WAREHOUSE',      2, N'Raw Warehouse',    N'Kho vật tư',   NULL,                         'fa-solid fa-boxes-stacked',  10),
('WAREHOUSE_RAW_RECEIVE', 'WAREHOUSE_RAW',  3, N'Receive Material', N'Nhận vật tư',  N'/Warehouse/MaterialReceipt', 'fa-solid fa-truck-ramp-box', 10);

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
