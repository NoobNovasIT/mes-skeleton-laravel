/* =============================================================================
   005_Create_Table_Menu
   Menu dạng cây, không giới hạn cấp.
     ParentMenuId = NULL  -> cấp 1 (Module) hiển thị thành tab trên topbar
     Cấp 2 trở xuống      -> hiển thị trong sidebar

   MenuType : 1 = Module, 2 = Group (chỉ để gom nhóm, không có Url), 3 = Page (có Url)
   MenuCode : khóa nghiệp vụ ổn định, code C# tham chiếu bằng MenuCode
              (Mes.Common.Constants.MenuCodes), KHÔNG dùng Id hay Url để kiểm quyền.
   IsVisibleInNavigation = 0 : page vẫn phân quyền được nhưng không hiện trên menu
              (ví dụ trang chi tiết mở từ trang khác).
   ============================================================================= */
SET ANSI_NULLS ON;
SET QUOTED_IDENTIFIER ON;
GO
IF OBJECT_ID(N'dbo.Menu', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.Menu
    (
        Id                      INT IDENTITY(1, 1)  NOT NULL,
        ParentMenuId            INT                 NULL,
        MenuCode                VARCHAR(100)        NOT NULL,
        MenuType                TINYINT             NOT NULL,
        DefaultName             NVARCHAR(100)       NOT NULL,
        Url                     NVARCHAR(300)       NULL,
        IconClass               VARCHAR(100)        NULL,
        SortOrder               INT                 NOT NULL CONSTRAINT DF_Menu_SortOrder DEFAULT (0),
        IsActive                BIT                 NOT NULL CONSTRAINT DF_Menu_IsActive DEFAULT (1),
        IsVisibleInNavigation   BIT                 NOT NULL CONSTRAINT DF_Menu_IsVisibleInNavigation DEFAULT (1),

        CreatedAt               DATETIME2(3)        NOT NULL CONSTRAINT DF_Menu_CreatedAt DEFAULT (SYSDATETIME()),
        CreatedByUserAccountId  INT                 NULL,
        UpdatedAt               DATETIME2(3)        NULL,
        UpdatedByUserAccountId  INT                 NULL,
        IsDeleted               BIT                 NOT NULL CONSTRAINT DF_Menu_IsDeleted DEFAULT (0),

        CONSTRAINT PK_Menu PRIMARY KEY CLUSTERED (Id),
        CONSTRAINT FK_Menu_Menu FOREIGN KEY (ParentMenuId) REFERENCES dbo.Menu (Id),
        CONSTRAINT CK_Menu_MenuType CHECK (MenuType IN (1, 2, 3))
    );

    CREATE UNIQUE NONCLUSTERED INDEX UQ_Menu_MenuCode ON dbo.Menu (MenuCode) WHERE IsDeleted = 0;
    CREATE NONCLUSTERED INDEX IX_Menu_ParentMenuId ON dbo.Menu (ParentMenuId, SortOrder);
END
GO
