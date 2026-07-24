USE PortuariaDemo;
GO

/* =========================================================
   CAMBIO tipo_registro A NUMÉRICO EN dbo.bit_camaras

   200 = ACTIVIDAD_DIARIA
   201 = NOVEDAD_CAMARA

   Objetivo:
   - La página seguirá mostrando: Actividad diaria / Novedad de cámara.
   - La base guardará códigos numéricos para normalizar la tabla.
========================================================= */

/* 1. Eliminar restricciones CHECK antiguas relacionadas con tipo_registro */
DECLARE @sql NVARCHAR(MAX) = N'';

SELECT @sql = @sql +
    N'ALTER TABLE dbo.bit_camaras DROP CONSTRAINT [' + cc.name + N'];' + CHAR(13)
FROM sys.check_constraints cc
INNER JOIN sys.tables t ON cc.parent_object_id = t.object_id
WHERE t.name = N'bitacora_camaras'
  AND cc.definition LIKE N'%tipo_registro%';

IF (@sql <> N'')
BEGIN
    EXEC sp_executesql @sql;
END
GO

/* 2. Eliminar DEFAULT antiguo de tipo_registro, si existe */
DECLARE @sql NVARCHAR(MAX) = N'';

SELECT @sql = @sql +
    N'ALTER TABLE dbo.bit_camaras DROP CONSTRAINT [' + dc.name + N'];' + CHAR(13)
FROM sys.default_constraints dc
INNER JOIN sys.columns c ON dc.parent_object_id = c.object_id AND dc.parent_column_id = c.column_id
INNER JOIN sys.tables t ON c.object_id = t.object_id
WHERE t.name = N'bitacora_camaras'
  AND c.name = N'tipo_registro';

IF (@sql <> N'')
BEGIN
    EXEC sp_executesql @sql;
END
GO

/* 3. Si la columna todavía es texto, convertir valores existentes a códigos numéricos en texto */
IF EXISTS (
    SELECT 1
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_NAME = 'bitacora_camaras'
      AND COLUMN_NAME = 'tipo_registro'
      AND DATA_TYPE IN ('nvarchar', 'varchar', 'nchar', 'char')
)
BEGIN
    UPDATE dbo.bit_camaras
    SET tipo_registro = CASE
        WHEN UPPER(LTRIM(RTRIM(CONVERT(NVARCHAR(50), tipo_registro)))) IN ('201', 'NOVEDAD_CAMARA', 'NOVEDAD DE CÁMARA', 'NOVEDAD DE CAMARA') THEN '201'
        WHEN UPPER(LTRIM(RTRIM(CONVERT(NVARCHAR(50), tipo_registro)))) IN ('200', 'ACTIVIDAD_DIARIA', 'ACTIVIDAD DIARIA') THEN '200'
        WHEN tipo_registro IS NULL OR LTRIM(RTRIM(CONVERT(NVARCHAR(50), tipo_registro))) = '' THEN '200'
        ELSE '200'
    END;

    ALTER TABLE dbo.bit_camaras
    ALTER COLUMN tipo_registro INT NOT NULL;
END
GO

/* 4. Si ya es INT, normalizar cualquier valor fuera del catálogo */
IF EXISTS (
    SELECT 1
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_NAME = 'bitacora_camaras'
      AND COLUMN_NAME = 'tipo_registro'
      AND DATA_TYPE = 'int'
)
BEGIN
    UPDATE dbo.bit_camaras
    SET tipo_registro = 200
    WHERE tipo_registro IS NULL
       OR tipo_registro NOT IN (200, 201);
END
GO

/* 5. Crear DEFAULT numérico */
IF NOT EXISTS (
    SELECT 1
    FROM sys.default_constraints dc
    INNER JOIN sys.columns c ON dc.parent_object_id = c.object_id AND dc.parent_column_id = c.column_id
    INNER JOIN sys.tables t ON c.object_id = t.object_id
    WHERE t.name = N'bitacora_camaras'
      AND c.name = N'tipo_registro'
)
BEGIN
    ALTER TABLE dbo.bit_camaras
    ADD CONSTRAINT DF_bitacora_camaras_tipo_registro_num
    DEFAULT (200) FOR tipo_registro;
END
GO

/* 6. Crear CHECK numérico */
IF NOT EXISTS (
    SELECT 1
    FROM sys.check_constraints
    WHERE name = N'CK_bitacora_camaras_tipo_registro_num'
)
BEGIN
    ALTER TABLE dbo.bit_camaras
    ADD CONSTRAINT CK_bitacora_camaras_tipo_registro_num
    CHECK (tipo_registro IN (200, 201));
END
GO

/* 7. Verificación final */
SELECT 
    COLUMN_NAME AS columna,
    DATA_TYPE AS tipo_dato,
    IS_NULLABLE AS permite_null
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_NAME = 'bitacora_camaras'
  AND COLUMN_NAME = 'tipo_registro';
GO

SELECT 
    tipo_registro,
    CASE 
        WHEN tipo_registro = 200 THEN 'ACTIVIDAD_DIARIA'
        WHEN tipo_registro = 201 THEN 'NOVEDAD_CAMARA'
        ELSE 'DESCONOCIDO'
    END AS tipo_registro_texto,
    COUNT(*) AS total
FROM dbo.bit_camaras
GROUP BY tipo_registro
ORDER BY tipo_registro;
GO
