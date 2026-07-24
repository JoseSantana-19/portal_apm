USE PortuariaDemo;
GO

/* =========================================================
   19 - NIVEL DE ALERTA NUMÉRICO CCTV
   104 = NORMAL
   105 = MEDIO
   106 = CRITICO

   Requiere que ya exista dbo.estados.
========================================================= */

/* 1. Asegurar tabla dbo.estados */
IF OBJECT_ID('dbo.estados', 'U') IS NULL
BEGIN
    CREATE TABLE dbo.estados (
        idestado INT NOT NULL,
        descripcion NVARCHAR(100) NOT NULL,
        detalle NVARCHAR(255) NULL,
        estado BIT NOT NULL CONSTRAINT DF_estados_estado DEFAULT (1),
        CONSTRAINT PK_estados PRIMARY KEY (idestado),
        CONSTRAINT CK_estados_estado CHECK (estado IN (0, 1))
    );
END;
GO

/* 2. Insertar / actualizar niveles CCTV */
MERGE dbo.estados AS destino
USING (
    VALUES
        (104, N'NORMAL',  N'Nivel de alerta normal para bitácora CCTV', 1),
        (105, N'MEDIO',   N'Nivel de alerta medio para bitácora CCTV', 1),
        (106, N'CRITICO', N'Nivel de alerta crítico para bitácora CCTV', 1)
) AS origen (idestado, descripcion, detalle, estado)
ON destino.idestado = origen.idestado
WHEN MATCHED THEN
    UPDATE SET
        destino.descripcion = origen.descripcion,
        destino.detalle = origen.detalle,
        destino.estado = origen.estado
WHEN NOT MATCHED THEN
    INSERT (idestado, descripcion, detalle, estado)
    VALUES (origen.idestado, origen.descripcion, origen.detalle, origen.estado);
GO

/* 3. Eliminar FK existentes relacionadas con nivel_alerta */
DECLARE @sqlFKNivel NVARCHAR(MAX) = N'';

SELECT @sqlFKNivel = @sqlFKNivel +
    'ALTER TABLE dbo.bit_camaras DROP CONSTRAINT [' + fk.name + '];' + CHAR(13)
FROM sys.foreign_keys fk
INNER JOIN sys.foreign_key_columns fkc
    ON fk.object_id = fkc.constraint_object_id
INNER JOIN sys.tables t
    ON fk.parent_object_id = t.object_id
INNER JOIN sys.columns c
    ON fkc.parent_object_id = c.object_id
   AND fkc.parent_column_id = c.column_id
WHERE t.name = 'bitacora_camaras'
  AND c.name = 'nivel_alerta';

IF (@sqlFKNivel <> '')
BEGIN
    EXEC sp_executesql @sqlFKNivel;
END;
GO

/* 4. Eliminar CHECK de nivel_alerta */
DECLARE @sqlChecks NVARCHAR(MAX) = N'';

SELECT @sqlChecks = @sqlChecks +
    'ALTER TABLE dbo.bit_camaras DROP CONSTRAINT [' + cc.name + '];' + CHAR(13)
FROM sys.check_constraints cc
INNER JOIN sys.tables t
    ON cc.parent_object_id = t.object_id
WHERE t.name = 'bitacora_camaras'
  AND cc.definition LIKE '%nivel_alerta%';

IF (@sqlChecks <> '')
BEGIN
    EXEC sp_executesql @sqlChecks;
END;
GO

/* 5. Eliminar DEFAULT de nivel_alerta */
DECLARE @sqlDefaults NVARCHAR(MAX) = N'';

SELECT @sqlDefaults = @sqlDefaults +
    'ALTER TABLE dbo.bit_camaras DROP CONSTRAINT [' + dc.name + '];' + CHAR(13)
FROM sys.default_constraints dc
INNER JOIN sys.columns c
    ON dc.parent_object_id = c.object_id
   AND dc.parent_column_id = c.column_id
INNER JOIN sys.tables t
    ON c.object_id = t.object_id
WHERE t.name = 'bitacora_camaras'
  AND c.name = 'nivel_alerta';

IF (@sqlDefaults <> '')
BEGIN
    EXEC sp_executesql @sqlDefaults;
END;
GO

/* 6. Eliminar índices que dependan de nivel_alerta */
DECLARE @sqlIndexes NVARCHAR(MAX) = N'';

SELECT @sqlIndexes = @sqlIndexes +
    'DROP INDEX [' + i.name + '] ON dbo.bit_camaras;' + CHAR(13)
FROM sys.indexes i
INNER JOIN sys.index_columns ic
    ON i.object_id = ic.object_id
   AND i.index_id = ic.index_id
INNER JOIN sys.columns c
    ON ic.object_id = c.object_id
   AND ic.column_id = c.column_id
INNER JOIN sys.tables t
    ON i.object_id = t.object_id
WHERE t.name = 'bitacora_camaras'
  AND c.name = 'nivel_alerta'
  AND i.is_primary_key = 0
  AND i.is_unique_constraint = 0;

IF (@sqlIndexes <> '')
BEGIN
    EXEC sp_executesql @sqlIndexes;
END;
GO

/* 7. Migrar nivel_alerta a valores numéricos en texto */
UPDATE dbo.bit_camaras
SET nivel_alerta = CASE
    WHEN UPPER(LTRIM(RTRIM(CONVERT(NVARCHAR(50), nivel_alerta)))) IN
        (N'CRITICO', N'CRÍTICO', N'106') THEN N'106'

    WHEN UPPER(LTRIM(RTRIM(CONVERT(NVARCHAR(50), nivel_alerta)))) IN
        (N'MEDIO', N'105') THEN N'105'

    WHEN UPPER(LTRIM(RTRIM(CONVERT(NVARCHAR(50), nivel_alerta)))) IN
        (N'NORMAL', N'104') THEN N'104'

    ELSE N'104'
END;
GO

/* 8. Cambiar columna a INT */
ALTER TABLE dbo.bit_camaras
ALTER COLUMN nivel_alerta INT NOT NULL;
GO

/* 9. Crear DEFAULT, CHECK, ÍNDICE y FK */
ALTER TABLE dbo.bit_camaras
ADD CONSTRAINT DF_bitacora_camaras_nivel_alerta
DEFAULT (104) FOR nivel_alerta;
GO

ALTER TABLE dbo.bit_camaras
ADD CONSTRAINT CK_bitacora_camaras_nivel_alerta
CHECK (nivel_alerta IN (104, 105, 106));
GO

CREATE INDEX IX_bitacora_camaras_nivel_alerta
ON dbo.bit_camaras(nivel_alerta);
GO

ALTER TABLE dbo.bit_camaras WITH CHECK
ADD CONSTRAINT FK_bitacora_camaras_nivel_alerta_estados
FOREIGN KEY (nivel_alerta)
REFERENCES dbo.estados(idestado);
GO

/* 10. Verificación final */
SELECT *
FROM dbo.estados
WHERE idestado IN (100, 101, 102, 103, 104, 105, 106)
ORDER BY idestado;
GO

SELECT
    COLUMN_NAME AS columna,
    DATA_TYPE AS tipo_dato,
    IS_NULLABLE AS permite_null
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_NAME = 'bitacora_camaras'
  AND COLUMN_NAME IN ('estado_camara', 'tipo_registro', 'nivel_alerta');
GO

SELECT
    nivel_alerta,
    CASE
        WHEN nivel_alerta = 104 THEN 'NORMAL'
        WHEN nivel_alerta = 105 THEN 'MEDIO'
        WHEN nivel_alerta = 106 THEN 'CRITICO'
        ELSE 'DESCONOCIDO'
    END AS nivel_alerta_texto,
    COUNT(*) AS total
FROM dbo.bit_camaras
GROUP BY nivel_alerta
ORDER BY nivel_alerta;
GO
