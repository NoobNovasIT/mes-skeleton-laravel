/* =============================================================================
   013_Create_Table_MaterialReceipt
   Phiếu nhận vật tư vào kho vật tư (Warehouse > Raw Warehouse > Receive Material).
   Theo mẫu AddPageWalkthrough.md. Chạy lại nhiều lần không lỗi (idempotent).

   Chạy: php artisan mes:run-sql database/MASTERDATA_SQL/013_Create_Table_MaterialReceipt.sql
   ============================================================================= */
SET ANSI_NULLS ON;
SET QUOTED_IDENTIFIER ON;
GO
IF OBJECT_ID(N'dbo.MaterialReceipt', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.MaterialReceipt
    (
        Id                      INT IDENTITY(1, 1)  NOT NULL,
        ReceiptCode             VARCHAR(30)         NOT NULL,
        MaterialCode            VARCHAR(50)         NOT NULL,
        MaterialName            NVARCHAR(200)       NOT NULL,
        ReceivedQuantity        DECIMAL(18, 3)      NOT NULL,
        ReceivedAt              DATETIME2(3)        NOT NULL,
        Note                    NVARCHAR(500)       NULL,

        CreatedAt               DATETIME2(3)        NOT NULL CONSTRAINT DF_MaterialReceipt_CreatedAt DEFAULT (SYSDATETIME()),
        CreatedByUserAccountId  INT                 NULL,
        UpdatedAt               DATETIME2(3)        NULL,
        UpdatedByUserAccountId  INT                 NULL,
        IsDeleted               BIT                 NOT NULL CONSTRAINT DF_MaterialReceipt_IsDeleted DEFAULT (0),

        CONSTRAINT PK_MaterialReceipt PRIMARY KEY CLUSTERED (Id)
    );

    CREATE UNIQUE NONCLUSTERED INDEX UQ_MaterialReceipt_ReceiptCode ON dbo.MaterialReceipt (ReceiptCode) WHERE IsDeleted = 0;
    CREATE NONCLUSTERED INDEX IX_MaterialReceipt_ReceivedAt ON dbo.MaterialReceipt (ReceivedAt DESC);
END
GO
