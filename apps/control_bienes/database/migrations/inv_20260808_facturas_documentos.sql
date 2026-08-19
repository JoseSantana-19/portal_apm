SET NOCOUNT ON;
SET XACT_ABORT ON;
BEGIN TRANSACTION;

IF COL_LENGTH('dbo.inv_facturas', 'archivo_nombre_original') IS NULL
    ALTER TABLE dbo.inv_facturas ADD archivo_nombre_original NVARCHAR(255) NULL;
IF COL_LENGTH('dbo.inv_facturas', 'archivo_ruta') IS NULL
    ALTER TABLE dbo.inv_facturas ADD archivo_ruta NVARCHAR(500) NULL;
IF COL_LENGTH('dbo.inv_facturas', 'archivo_mime') IS NULL
    ALTER TABLE dbo.inv_facturas ADD archivo_mime NVARCHAR(100) NULL;
IF COL_LENGTH('dbo.inv_facturas', 'ocr_texto') IS NULL
    ALTER TABLE dbo.inv_facturas ADD ocr_texto NVARCHAR(MAX) NULL;
IF COL_LENGTH('dbo.inv_facturas', 'fecha_escaneo') IS NULL
    ALTER TABLE dbo.inv_facturas ADD fecha_escaneo DATETIME2(0) NULL;

COMMIT TRANSACTION;
