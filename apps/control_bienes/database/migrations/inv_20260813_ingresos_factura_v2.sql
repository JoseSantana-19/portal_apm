/* Ingresos a bodega con factura v2 - SQL Server 2014. */
SET XACT_ABORT ON;
SET ANSI_NULLS ON;
SET QUOTED_IDENTIFIER ON;
BEGIN TRANSACTION;

IF COL_LENGTH('dbo.inv_facturas', 'descripcion') IS NULL
    ALTER TABLE dbo.inv_facturas ADD descripcion NVARCHAR(1000) NULL;
IF COL_LENGTH('dbo.inv_facturas', 'fecha_actualizacion') IS NULL
    ALTER TABLE dbo.inv_facturas ADD fecha_actualizacion DATETIME2(0) NULL;
IF COL_LENGTH('dbo.inv_facturas', 'actualizado_por') IS NULL
    ALTER TABLE dbo.inv_facturas ADD actualizado_por NVARCHAR(255) NULL;
IF COL_LENGTH('dbo.inv_facturas', 'fecha_anulacion') IS NULL
    ALTER TABLE dbo.inv_facturas ADD fecha_anulacion DATETIME2(0) NULL;
IF COL_LENGTH('dbo.inv_facturas', 'anulado_por') IS NULL
    ALTER TABLE dbo.inv_facturas ADD anulado_por NVARCHAR(255) NULL;
IF COL_LENGTH('dbo.inv_facturas', 'motivo_anulacion') IS NULL
    ALTER TABLE dbo.inv_facturas ADD motivo_anulacion NVARCHAR(1000) NULL;

IF COL_LENGTH('dbo.inv_facturas_detalles', 'pedido') IS NULL
    ALTER TABLE dbo.inv_facturas_detalles ADD pedido NVARCHAR(100) NULL;
IF COL_LENGTH('dbo.inv_facturas_detalles', 'requisicion') IS NULL
    ALTER TABLE dbo.inv_facturas_detalles ADD requisicion NVARCHAR(100) NULL;
IF COL_LENGTH('dbo.inv_facturas_detalles', 'referencia') IS NULL
    ALTER TABLE dbo.inv_facturas_detalles ADD referencia NVARCHAR(255) NULL;
IF COL_LENGTH('dbo.inv_facturas_detalles', 'iva_tipo_id') IS NULL
    ALTER TABLE dbo.inv_facturas_detalles ADD iva_tipo_id INT NULL;
IF COL_LENGTH('dbo.inv_facturas_detalles', 'iva_porcentaje') IS NULL
    ALTER TABLE dbo.inv_facturas_detalles ADD iva_porcentaje DECIMAL(7,4) NOT NULL
        CONSTRAINT df_inv_fac_det_iva_porcentaje DEFAULT (0);
IF COL_LENGTH('dbo.inv_facturas_detalles', 'subtotal') IS NULL
    ALTER TABLE dbo.inv_facturas_detalles ADD subtotal DECIMAL(19,4) NOT NULL
        CONSTRAINT df_inv_fac_det_subtotal DEFAULT (0);
IF COL_LENGTH('dbo.inv_facturas_detalles', 'valor_iva') IS NULL
    ALTER TABLE dbo.inv_facturas_detalles ADD valor_iva DECIMAL(19,4) NOT NULL
        CONSTRAINT df_inv_fac_det_valor_iva DEFAULT (0);
IF COL_LENGTH('dbo.inv_facturas_detalles', 'total') IS NULL
    ALTER TABLE dbo.inv_facturas_detalles ADD total DECIMAL(19,4) NOT NULL
        CONSTRAINT df_inv_fac_det_total DEFAULT (0);

COMMIT TRANSACTION;
GO

BEGIN TRANSACTION;
IF NOT EXISTS (SELECT 1 FROM sys.foreign_keys WHERE name = 'fk_inv_fac_det_iva_tipo')
    ALTER TABLE dbo.inv_facturas_detalles WITH CHECK ADD CONSTRAINT fk_inv_fac_det_iva_tipo
        FOREIGN KEY (iva_tipo_id) REFERENCES dbo.inv_tipos_iva(id);

UPDATE dbo.inv_facturas_detalles
SET subtotal = cantidad * precio_unitario,
    iva_porcentaje = CASE WHEN grava_iva = 1 THEN
        COALESCE((SELECT TOP 1 f.iva_porcentaje FROM dbo.inv_facturas f WHERE f.id_factura = factura_id), 0)
        ELSE 0 END,
    valor_iva = CASE WHEN grava_iva = 1 THEN ROUND(cantidad * precio_unitario *
        COALESCE((SELECT TOP 1 f.iva_porcentaje FROM dbo.inv_facturas f WHERE f.id_factura = factura_id), 0) / 100, 2)
        ELSE 0 END,
    total = cantidad * precio_unitario + CASE WHEN grava_iva = 1 THEN ROUND(cantidad * precio_unitario *
        COALESCE((SELECT TOP 1 f.iva_porcentaje FROM dbo.inv_facturas f WHERE f.id_factura = factura_id), 0) / 100, 2)
        ELSE 0 END
WHERE subtotal = 0 AND cantidad * precio_unitario <> 0;

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'ix_inv_facturas_fecha_estado' AND object_id = OBJECT_ID('dbo.inv_facturas'))
    CREATE INDEX ix_inv_facturas_fecha_estado ON dbo.inv_facturas(fecha_factura DESC, estado) INCLUDE (proveedor_id, total);
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'ix_inv_facturas_detalles_factura' AND object_id = OBJECT_ID('dbo.inv_facturas_detalles'))
    CREATE INDEX ix_inv_facturas_detalles_factura ON dbo.inv_facturas_detalles(factura_id) INCLUDE (item_id, cantidad, total);

COMMIT TRANSACTION;
GO
