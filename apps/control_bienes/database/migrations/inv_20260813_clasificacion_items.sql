-- Normalizacion de items de inventario por tipo de bien.
-- Compatible con SQL Server 2014 o posterior.
-- Mantiene dbo.inv_inventario como tabla general y separa los codigos
-- de consumo corriente (CC) y activos fijos (AF) en tablas uno-a-uno.

USE [inventario];
GO

SET NOCOUNT ON;
SET XACT_ABORT ON;
SET ANSI_NULLS ON;
SET QUOTED_IDENTIFIER ON;
SET ANSI_PADDING ON;
SET ANSI_WARNINGS ON;
SET CONCAT_NULL_YIELDS_NULL ON;
SET ARITHABORT ON;
SET NUMERIC_ROUNDABORT OFF;
GO

IF OBJECT_ID(N'dbo.inv_consumo_corriente', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.inv_consumo_corriente (
        item_id INT NOT NULL,
        producto_id INT NULL,
        codigo NVARCHAR(50) NOT NULL,
        created_at DATETIME2(0) NOT NULL
            CONSTRAINT DF_inv_consumo_corriente_created_at DEFAULT SYSDATETIME(),
        updated_at DATETIME2(0) NOT NULL
            CONSTRAINT DF_inv_consumo_corriente_updated_at DEFAULT SYSDATETIME(),
        CONSTRAINT PK_inv_consumo_corriente PRIMARY KEY (item_id),
        CONSTRAINT FK_inv_consumo_corriente_item
            FOREIGN KEY (item_id) REFERENCES dbo.inv_inventario(id) ON DELETE CASCADE,
        CONSTRAINT FK_inv_consumo_corriente_producto
            FOREIGN KEY (producto_id) REFERENCES dbo.inv_productos(id)
    );
END;
GO

IF OBJECT_ID(N'dbo.inv_activos_fijos', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.inv_activos_fijos (
        item_id INT NOT NULL,
        codigo NVARCHAR(50) NOT NULL,
        created_at DATETIME2(0) NOT NULL
            CONSTRAINT DF_inv_activos_fijos_created_at DEFAULT SYSDATETIME(),
        updated_at DATETIME2(0) NOT NULL
            CONSTRAINT DF_inv_activos_fijos_updated_at DEFAULT SYSDATETIME(),
        CONSTRAINT PK_inv_activos_fijos PRIMARY KEY (item_id),
        CONSTRAINT FK_inv_activos_fijos_item
            FOREIGN KEY (item_id) REFERENCES dbo.inv_inventario(id) ON DELETE CASCADE
    );
END;
GO

-- Sincroniza los registros existentes sin duplicarlos.
UPDATE cc
SET cc.producto_id = i.producto_id,
    cc.codigo = COALESCE(NULLIF(LTRIM(RTRIM(p.codigo)), N''), i.secuencial),
    cc.updated_at = SYSDATETIME()
FROM dbo.inv_consumo_corriente cc
INNER JOIN dbo.inv_inventario i ON i.id = cc.item_id
LEFT JOIN dbo.inv_productos p ON p.id = i.producto_id
WHERE i.tipo_bien = 'CC';

INSERT INTO dbo.inv_consumo_corriente (item_id, producto_id, codigo)
SELECT i.id,
       i.producto_id,
       COALESCE(NULLIF(LTRIM(RTRIM(p.codigo)), N''), i.secuencial)
FROM dbo.inv_inventario i
LEFT JOIN dbo.inv_productos p ON p.id = i.producto_id
WHERE i.tipo_bien = 'CC'
  AND NOT EXISTS (
      SELECT 1 FROM dbo.inv_consumo_corriente cc WHERE cc.item_id = i.id
  );

UPDATE af
SET af.codigo = i.secuencial,
    af.updated_at = SYSDATETIME()
FROM dbo.inv_activos_fijos af
INNER JOIN dbo.inv_inventario i ON i.id = af.item_id
WHERE i.tipo_bien = 'AF';

INSERT INTO dbo.inv_activos_fijos (item_id, codigo)
SELECT i.id, i.secuencial
FROM dbo.inv_inventario i
WHERE i.tipo_bien = 'AF'
  AND NOT EXISTS (
      SELECT 1 FROM dbo.inv_activos_fijos af WHERE af.item_id = i.id
  );

DELETE cc
FROM dbo.inv_consumo_corriente cc
INNER JOIN dbo.inv_inventario i ON i.id = cc.item_id
WHERE i.tipo_bien <> 'CC';

DELETE af
FROM dbo.inv_activos_fijos af
INNER JOIN dbo.inv_inventario i ON i.id = af.item_id
WHERE i.tipo_bien <> 'AF';
GO

IF NOT EXISTS (
    SELECT 1 FROM sys.indexes
    WHERE object_id = OBJECT_ID(N'dbo.inv_consumo_corriente')
      AND name = N'IX_inv_consumo_corriente_codigo'
)
    CREATE INDEX IX_inv_consumo_corriente_codigo
        ON dbo.inv_consumo_corriente(codigo)
        INCLUDE (producto_id);
GO

IF EXISTS (
    SELECT 1 FROM sys.indexes
    WHERE object_id = OBJECT_ID(N'dbo.inv_consumo_corriente')
      AND name = N'IX_inv_consumo_corriente_producto'
      AND has_filter = 1
)
    DROP INDEX IX_inv_consumo_corriente_producto
        ON dbo.inv_consumo_corriente;
GO

IF NOT EXISTS (
    SELECT 1 FROM sys.indexes
    WHERE object_id = OBJECT_ID(N'dbo.inv_consumo_corriente')
      AND name = N'IX_inv_consumo_corriente_producto'
)
    CREATE INDEX IX_inv_consumo_corriente_producto
        ON dbo.inv_consumo_corriente(producto_id);
GO

IF NOT EXISTS (
    SELECT 1 FROM sys.indexes
    WHERE object_id = OBJECT_ID(N'dbo.inv_activos_fijos')
      AND name = N'IX_inv_activos_fijos_codigo'
)
    CREATE INDEX IX_inv_activos_fijos_codigo
        ON dbo.inv_activos_fijos(codigo);
GO

IF NOT EXISTS (
    SELECT 1 FROM sys.indexes
    WHERE object_id = OBJECT_ID(N'dbo.inv_inventario')
      AND name = N'IX_inv_inventario_tipo_bien_secuencial'
)
    CREATE INDEX IX_inv_inventario_tipo_bien_secuencial
        ON dbo.inv_inventario(tipo_bien, secuencial)
        INCLUDE (producto_id, nombre, categoria_id, activo);
GO

IF NOT EXISTS (
    SELECT 1 FROM sys.indexes
    WHERE object_id = OBJECT_ID(N'dbo.inv_productos')
      AND name = N'IX_inv_productos_tipo_bien_codigo'
)
    CREATE INDEX IX_inv_productos_tipo_bien_codigo
        ON dbo.inv_productos(tipo_bien, codigo)
        INCLUDE (nombre, grupo_id, unidad_id);
GO

IF OBJECT_ID(N'dbo.vw_inv_items_clasificados', N'V') IS NOT NULL
    DROP VIEW dbo.vw_inv_items_clasificados;
GO

CREATE VIEW dbo.vw_inv_items_clasificados
AS
    SELECT i.*,
           CASE
               WHEN i.tipo_bien = 'AF' THEN af.codigo
               ELSE cc.codigo
           END AS codigo_clasificacion,
           CASE
               WHEN i.tipo_bien = 'AF' THEN N'inv_activos_fijos'
               ELSE N'inv_consumo_corriente'
           END AS tabla_clasificacion
    FROM dbo.inv_inventario i
    LEFT JOIN dbo.inv_consumo_corriente cc
        ON cc.item_id = i.id AND i.tipo_bien = 'CC'
    LEFT JOIN dbo.inv_activos_fijos af
        ON af.item_id = i.id AND i.tipo_bien = 'AF';
GO

IF OBJECT_ID(N'dbo.trg_inv_inventario_clasificacion', N'TR') IS NOT NULL
    DROP TRIGGER dbo.trg_inv_inventario_clasificacion;
GO

CREATE TRIGGER dbo.trg_inv_inventario_clasificacion
ON dbo.inv_inventario
AFTER INSERT, UPDATE
AS
BEGIN
    SET NOCOUNT ON;

    UPDATE cc
    SET cc.producto_id = i.producto_id,
        cc.codigo = COALESCE(NULLIF(LTRIM(RTRIM(p.codigo)), N''), i.secuencial),
        cc.updated_at = SYSDATETIME()
    FROM dbo.inv_consumo_corriente cc
    INNER JOIN inserted i ON i.id = cc.item_id
    LEFT JOIN dbo.inv_productos p ON p.id = i.producto_id
    WHERE i.tipo_bien = 'CC';

    INSERT INTO dbo.inv_consumo_corriente (item_id, producto_id, codigo)
    SELECT i.id,
           i.producto_id,
           COALESCE(NULLIF(LTRIM(RTRIM(p.codigo)), N''), i.secuencial)
    FROM inserted i
    LEFT JOIN dbo.inv_productos p ON p.id = i.producto_id
    WHERE i.tipo_bien = 'CC'
      AND NOT EXISTS (
          SELECT 1 FROM dbo.inv_consumo_corriente cc WHERE cc.item_id = i.id
      );

    UPDATE af
    SET af.codigo = i.secuencial,
        af.updated_at = SYSDATETIME()
    FROM dbo.inv_activos_fijos af
    INNER JOIN inserted i ON i.id = af.item_id
    WHERE i.tipo_bien = 'AF';

    INSERT INTO dbo.inv_activos_fijos (item_id, codigo)
    SELECT i.id, i.secuencial
    FROM inserted i
    WHERE i.tipo_bien = 'AF'
      AND NOT EXISTS (
          SELECT 1 FROM dbo.inv_activos_fijos af WHERE af.item_id = i.id
      );

    DELETE cc
    FROM dbo.inv_consumo_corriente cc
    INNER JOIN inserted i ON i.id = cc.item_id
    WHERE i.tipo_bien <> 'CC';

    DELETE af
    FROM dbo.inv_activos_fijos af
    INNER JOIN inserted i ON i.id = af.item_id
    WHERE i.tipo_bien <> 'AF';
END;
GO

IF OBJECT_ID(N'dbo.trg_inv_productos_clasificacion', N'TR') IS NOT NULL
    DROP TRIGGER dbo.trg_inv_productos_clasificacion;
GO

CREATE TRIGGER dbo.trg_inv_productos_clasificacion
ON dbo.inv_productos
AFTER UPDATE
AS
BEGIN
    SET NOCOUNT ON;

    UPDATE cc
    SET cc.producto_id = p.id,
        cc.codigo = COALESCE(NULLIF(LTRIM(RTRIM(p.codigo)), N''), i.secuencial),
        cc.updated_at = SYSDATETIME()
    FROM dbo.inv_consumo_corriente cc
    INNER JOIN dbo.inv_inventario i ON i.id = cc.item_id
    INNER JOIN inserted p ON p.id = i.producto_id
    WHERE i.tipo_bien = 'CC';
END;
GO

-- Verificacion de cobertura: cada item debe aparecer en una sola tabla de detalle.
IF EXISTS (
    SELECT 1
    FROM dbo.inv_inventario i
    LEFT JOIN dbo.inv_consumo_corriente cc ON cc.item_id = i.id
    LEFT JOIN dbo.inv_activos_fijos af ON af.item_id = i.id
    WHERE (i.tipo_bien = 'CC' AND (cc.item_id IS NULL OR af.item_id IS NOT NULL))
       OR (i.tipo_bien = 'AF' AND (af.item_id IS NULL OR cc.item_id IS NOT NULL))
)
    THROW 51000, 'La clasificacion de items no cubre correctamente el inventario general.', 1;
GO

EXEC sys.sp_updatestats;
GO

SELECT tipo_bien, COUNT(*) AS total
FROM dbo.vw_inv_items_clasificados
GROUP BY tipo_bien
ORDER BY tipo_bien;
GO
