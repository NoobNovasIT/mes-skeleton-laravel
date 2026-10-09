/* =============================================================================
   012_Create_Table_Notification
   Thông báo trong ứng dụng (chuông trên topbar), mỗi người nhận một dòng.

   TitleKey / MessageKey : câu tiếng Anh = khóa localization, dịch lúc hiển thị theo ngôn ngữ người xem.
   MessageArgumentsJson  : mảng JSON giá trị cho placeholder {0}, {1} của MessageKey.
   NotificationType      : hằng trong Mes.Common.Constants.NotificationTypes (quyết định icon).
   LinkUrl               : URL nội bộ (bắt đầu bằng "/") mở ra khi bấm vào thông báo.
   ============================================================================= */
SET ANSI_NULLS ON;
SET QUOTED_IDENTIFIER ON;
GO
IF OBJECT_ID(N'dbo.Notification', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.Notification
    (
        Id                      BIGINT IDENTITY(1, 1)   NOT NULL,
        RecipientUserAccountId  INT                     NOT NULL,
        NotificationType        VARCHAR(50)             NOT NULL,
        TitleKey                NVARCHAR(200)           NOT NULL,
        MessageKey              NVARCHAR(500)           NOT NULL,
        MessageArgumentsJson    NVARCHAR(1000)          NULL,
        LinkUrl                 NVARCHAR(300)           NULL,
        IsRead                  BIT                     NOT NULL CONSTRAINT DF_Notification_IsRead DEFAULT (0),
        ReadAt                  DATETIME2(3)            NULL,
        CreatedAt               DATETIME2(3)            NOT NULL CONSTRAINT DF_Notification_CreatedAt DEFAULT (SYSDATETIME()),

        CONSTRAINT PK_Notification PRIMARY KEY CLUSTERED (Id),
        CONSTRAINT FK_Notification_UserAccount FOREIGN KEY (RecipientUserAccountId) REFERENCES dbo.UserAccount (Id)
    );

    CREATE NONCLUSTERED INDEX IX_Notification_RecipientUserAccountId
        ON dbo.Notification (RecipientUserAccountId, IsRead, CreatedAt DESC);
END
GO
