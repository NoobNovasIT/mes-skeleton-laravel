/* =============================================================================
   006_Create_Table_MenuTranslation
   Tên hiển thị của menu theo ngôn ngữ. Không có bản dịch -> dùng Menu.DefaultName.
   ============================================================================= */
SET ANSI_NULLS ON;
SET QUOTED_IDENTIFIER ON;
GO
IF OBJECT_ID(N'dbo.MenuTranslation', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.MenuTranslation
    (
        MenuId          INT             NOT NULL,
        LanguageCode    VARCHAR(10)     NOT NULL,
        DisplayName     NVARCHAR(100)   NOT NULL,

        CONSTRAINT PK_MenuTranslation PRIMARY KEY CLUSTERED (MenuId, LanguageCode),
        CONSTRAINT FK_MenuTranslation_Menu     FOREIGN KEY (MenuId)       REFERENCES dbo.Menu (Id),
        CONSTRAINT FK_MenuTranslation_Language FOREIGN KEY (LanguageCode) REFERENCES dbo.Language (LanguageCode)
    );
END
GO
