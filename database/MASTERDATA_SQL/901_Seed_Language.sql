/* =============================================================================
   901_Seed_Language
   ============================================================================= */
SET ANSI_NULLS ON;
SET QUOTED_IDENTIFIER ON;
GO
MERGE dbo.Language AS Target
USING (VALUES
    ('en', N'English',    1, 1),
    ('vi', N'Tiếng Việt', 2, 0)
) AS Source (LanguageCode, LanguageName, SortOrder, IsDefault)
ON Target.LanguageCode = Source.LanguageCode
WHEN MATCHED THEN
    UPDATE SET LanguageName = Source.LanguageName, SortOrder = Source.SortOrder, IsDefault = Source.IsDefault
WHEN NOT MATCHED THEN
    INSERT (LanguageCode, LanguageName, SortOrder, IsDefault, IsActive)
    VALUES (Source.LanguageCode, Source.LanguageName, Source.SortOrder, Source.IsDefault, 1);
GO
