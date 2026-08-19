/* Datos de contacto estructurados para el maestro de proveedores. */
SET XACT_ABORT ON;
SET ANSI_NULLS ON;
SET QUOTED_IDENTIFIER ON;
SET ANSI_PADDING ON;
SET ANSI_WARNINGS ON;
SET CONCAT_NULL_YIELDS_NULL ON;
SET ARITHABORT ON;
SET NUMERIC_ROUNDABORT OFF;
BEGIN TRANSACTION;

IF COL_LENGTH('dbo.inv_proveedores', 'codigo') IS NULL
    ALTER TABLE dbo.inv_proveedores ADD codigo NVARCHAR(30) NULL;
IF COL_LENGTH('dbo.inv_proveedores', 'representante') IS NULL
    ALTER TABLE dbo.inv_proveedores ADD representante NVARCHAR(255) NULL;
IF COL_LENGTH('dbo.inv_proveedores', 'direccion') IS NULL
    ALTER TABLE dbo.inv_proveedores ADD direccion NVARCHAR(500) NULL;
IF COL_LENGTH('dbo.inv_proveedores', 'ciudad') IS NULL
    ALTER TABLE dbo.inv_proveedores ADD ciudad NVARCHAR(150) NULL;
IF COL_LENGTH('dbo.inv_proveedores', 'email') IS NULL
    ALTER TABLE dbo.inv_proveedores ADD email NVARCHAR(180) NULL;
IF COL_LENGTH('dbo.inv_proveedores', 'telefono1') IS NULL
    ALTER TABLE dbo.inv_proveedores ADD telefono1 NVARCHAR(50) NULL;
IF COL_LENGTH('dbo.inv_proveedores', 'telefono2') IS NULL
    ALTER TABLE dbo.inv_proveedores ADD telefono2 NVARCHAR(50) NULL;
IF COL_LENGTH('dbo.inv_proveedores', 'fax') IS NULL
    ALTER TABLE dbo.inv_proveedores ADD fax NVARCHAR(50) NULL;
IF COL_LENGTH('dbo.inv_proveedores', 'referencia') IS NULL
    ALTER TABLE dbo.inv_proveedores ADD referencia NVARCHAR(500) NULL;

COMMIT TRANSACTION;
GO

UPDATE dbo.inv_proveedores
SET codigo = 'PRV-' + RIGHT('00000' + CAST(id AS VARCHAR(5)), 5)
WHERE codigo IS NULL OR LTRIM(RTRIM(codigo)) = '';
GO

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'ux_inv_proveedores_codigo' AND object_id = OBJECT_ID('dbo.inv_proveedores'))
    CREATE UNIQUE INDEX ux_inv_proveedores_codigo ON dbo.inv_proveedores(codigo) WHERE codigo IS NOT NULL;
GO
