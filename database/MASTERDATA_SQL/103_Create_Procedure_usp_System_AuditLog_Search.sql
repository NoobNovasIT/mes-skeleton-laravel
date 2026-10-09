/* =============================================================================
   103_Create_Procedure_usp_System_AuditLog_Search
   Tìm kiếm nhật ký có phân trang. Mọi tham số lọc đều tùy chọn (NULL = bỏ qua).
   Cột TotalCount lặp lại trên mỗi dòng để tầng Repository đọc được tổng số bản ghi
   trong 1 lần gọi.
   ============================================================================= */
SET ANSI_NULLS ON;
SET QUOTED_IDENTIFIER ON;
GO
CREATE OR ALTER PROCEDURE dbo.usp_System_AuditLog_Search
    @FromDate       DATETIME2(3)    = NULL,
    @ToDate         DATETIME2(3)    = NULL,
    @ActionType     VARCHAR(50)     = NULL,
    @Keyword        NVARCHAR(100)   = NULL,
    @PageNumber     INT             = 1,
    @PageSize       INT             = 50
AS
BEGIN
    SET NOCOUNT ON;

    IF @PageNumber < 1 SET @PageNumber = 1;
    IF @PageSize   < 1 OR @PageSize > 500 SET @PageSize = 50;

    DECLARE @KeywordPattern NVARCHAR(102) = CASE WHEN NULLIF(LTRIM(RTRIM(@Keyword)), N'') IS NULL
                                                 THEN NULL
                                                 ELSE N'%' + LTRIM(RTRIM(@Keyword)) + N'%' END;

    SELECT      Id,
                UserAccountId,
                UserName,
                ActionType,
                EntityName,
                EntityId,
                Description,
                OldValueJson,
                NewValueJson,
                IpAddress,
                CreatedAt,
                COUNT(*) OVER () AS TotalCount
    FROM        dbo.AuditLog
    WHERE       (@FromDate   IS NULL OR CreatedAt >= @FromDate)
      AND       (@ToDate     IS NULL OR CreatedAt <  @ToDate)
      AND       (@ActionType IS NULL OR ActionType = @ActionType)
      AND       (@KeywordPattern IS NULL
                 OR UserName    LIKE @KeywordPattern
                 OR EntityName  LIKE @KeywordPattern
                 OR EntityId    LIKE @KeywordPattern
                 OR Description LIKE @KeywordPattern)
    ORDER BY    CreatedAt DESC, Id DESC
    OFFSET      (@PageNumber - 1) * @PageSize ROWS
    FETCH NEXT  @PageSize ROWS ONLY
    OPTION      (RECOMPILE);
END
GO
