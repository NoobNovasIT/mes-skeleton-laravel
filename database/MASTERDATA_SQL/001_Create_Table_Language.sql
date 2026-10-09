/* =============================================================================
   001_Create_Table_Language
   Danh sách ngôn ngữ giao diện được hỗ trợ. Thêm ngôn ngữ mới = thêm 1 dòng
   + thêm file SharedResource.{LanguageCode}.resx trong Mes.Web.
   ============================================================================= */
SET ANSI_NULLS ON;
SET QUOTED_IDENTIFIER ON;
GO
IF OBJECT_ID(N'dbo.Language', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.Language
    (
        LanguageCode    VARCHAR(10)     NOT NULL,
        LanguageName    NVARCHAR(50)    NOT NULL,
        SortOrder       INT             NOT NULL CONSTRAINT DF_Language_SortOrder DEFAULT (0),
        IsDefault       BIT             NOT NULL CONSTRAINT DF_Language_IsDefault DEFAULT (0),
        IsActive        BIT             NOT NULL CONSTRAINT DF_Language_IsActive DEFAULT (1),

        CONSTRAINT PK_Language PRIMARY KEY CLUSTERED (LanguageCode)
    );
END
GO
