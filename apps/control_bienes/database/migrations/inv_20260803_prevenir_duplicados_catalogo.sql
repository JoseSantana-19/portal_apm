/* Protecciones contra duplicados en los maestros de inventario.
   Compatible con SQL Server 2014 e idempotente. */
USE [inventario];
GO
SET ANSI_NULLS ON;
SET QUOTED_IDENTIFIER ON;
SET ANSI_PADDING ON;
SET ANSI_WARNINGS ON;
SET CONCAT_NULL_YIELDS_NULL ON;
SET ARITHABORT ON;
SET NUMERIC_ROUNDABORT OFF;
GO

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE object_id = OBJECT_ID('dbo.inv_categorias') AND name = 'UX_inv_categorias_codigo')
    CREATE UNIQUE INDEX UX_inv_categorias_codigo
        ON dbo.inv_categorias(codigo)
        WHERE codigo IS NOT NULL;
GO

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE object_id = OBJECT_ID('dbo.inv_productos') AND name = 'UX_inv_productos_codigo')
    CREATE UNIQUE INDEX UX_inv_productos_codigo
        ON dbo.inv_productos(codigo)
        WHERE codigo IS NOT NULL;
GO

/* Ya existen restricciones únicas para inv_productos.nombre e inv_inventario.secuencial. */
