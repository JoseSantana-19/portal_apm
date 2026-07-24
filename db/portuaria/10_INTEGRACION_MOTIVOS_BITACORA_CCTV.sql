/* =========================================================
   10_INTEGRACION_MOTIVOS_BITACORA_CCTV.sql
   Proyecto: Portal Portuario - CCTV Cámaras
   Objetivo:
   - Asegurar maestro bit_motivos_camaras
   - Asegurar columna id_motivo_camara en bitacora_camaras
   - Relacionar la bitácora con el maestro de motivos
   - Insertar motivos base si no existen

   Ejecutar en SQL Server Management Studio sobre la BD PortuariaDemo.
========================================================= */

USE PortuariaDemo;
GO

/* =========================================================
   1. Crear maestro de motivos CCTV si no existe
========================================================= */
IF OBJECT_ID(N'dbo.bit_motivos_camaras', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.bit_motivos_camaras (
        id_motivo_camara INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
        sec_motivo INT NULL,
        codigo_motivo NVARCHAR(30) NULL,
        descripcion NVARCHAR(180) NOT NULL,
        nivel_sugerido NVARCHAR(20) NOT NULL CONSTRAINT DF_bit_motivos_camaras_nivel DEFAULT ('Medio'),
        requiere_observacion BIT NOT NULL CONSTRAINT DF_bit_motivos_camaras_obs DEFAULT (1),
        estado BIT NOT NULL CONSTRAINT DF_bit_motivos_camaras_estado DEFAULT (1),
        fecha_creacion DATETIME NOT NULL CONSTRAINT DF_bit_motivos_camaras_fecha_creacion DEFAULT (GETDATE()),
        fecha_actualizacion DATETIME NULL,
        CONSTRAINT CK_bit_motivos_camaras_nivel CHECK (nivel_sugerido IN ('Normal', 'Medio', 'Crítico'))
    );
END;
GO

/* =========================================================
   2. Insertar motivos iniciales si no existen
========================================================= */
IF NOT EXISTS (SELECT 1 FROM dbo.bit_motivos_camaras WHERE descripcion = N'Sin señal')
BEGIN
    INSERT INTO dbo.bit_motivos_camaras (descripcion, nivel_sugerido, requiere_observacion)
    VALUES (N'Sin señal', N'Crítico', 1);
END;

IF NOT EXISTS (SELECT 1 FROM dbo.bit_motivos_camaras WHERE descripcion = N'Imagen borrosa')
BEGIN
    INSERT INTO dbo.bit_motivos_camaras (descripcion, nivel_sugerido, requiere_observacion)
    VALUES (N'Imagen borrosa', N'Medio', 1);
END;

IF NOT EXISTS (SELECT 1 FROM dbo.bit_motivos_camaras WHERE descripcion = N'Cámara fuera de servicio')
BEGIN
    INSERT INTO dbo.bit_motivos_camaras (descripcion, nivel_sugerido, requiere_observacion)
    VALUES (N'Cámara fuera de servicio', N'Crítico', 1);
END;

IF NOT EXISTS (SELECT 1 FROM dbo.bit_motivos_camaras WHERE descripcion = N'Problema de grabación')
BEGIN
    INSERT INTO dbo.bit_motivos_camaras (descripcion, nivel_sugerido, requiere_observacion)
    VALUES (N'Problema de grabación', N'Medio', 1);
END;

IF NOT EXISTS (SELECT 1 FROM dbo.bit_motivos_camaras WHERE descripcion = N'Cámara sin visualización')
BEGIN
    INSERT INTO dbo.bit_motivos_camaras (descripcion, nivel_sugerido, requiere_observacion)
    VALUES (N'Cámara sin visualización', N'Crítico', 1);
END;

IF NOT EXISTS (SELECT 1 FROM dbo.bit_motivos_camaras WHERE descripcion = N'Mantenimiento preventivo')
BEGIN
    INSERT INTO dbo.bit_motivos_camaras (descripcion, nivel_sugerido, requiere_observacion)
    VALUES (N'Mantenimiento preventivo', N'Normal', 0);
END;

IF NOT EXISTS (SELECT 1 FROM dbo.bit_motivos_camaras WHERE descripcion = N'Otro')
BEGIN
    INSERT INTO dbo.bit_motivos_camaras (descripcion, nivel_sugerido, requiere_observacion)
    VALUES (N'Otro', N'Medio', 1);
END;
GO

/* =========================================================
   3. Completar secuencial visible para motivos existentes
========================================================= */
;WITH motivos AS (
    SELECT
        id_motivo_camara,
        ROW_NUMBER() OVER (ORDER BY id_motivo_camara) AS rn
    FROM dbo.bit_motivos_camaras
    WHERE sec_motivo IS NULL
)
UPDATE bm
SET
    sec_motivo = motivos.rn,
    codigo_motivo = ISNULL(bm.codigo_motivo, 'MOT-CCTV-' + RIGHT(REPLICATE('0', 6) + CAST(motivos.rn AS VARCHAR(20)), 6))
FROM dbo.bit_motivos_camaras bm
INNER JOIN motivos ON bm.id_motivo_camara = motivos.id_motivo_camara;
GO

/* =========================================================
   4. Asegurar columna de relación en bitácora de cámaras
========================================================= */
IF OBJECT_ID(N'dbo.bit_camaras', N'U') IS NOT NULL
BEGIN
    IF COL_LENGTH('dbo.bit_camaras', 'id_motivo_camara') IS NULL
    BEGIN
        ALTER TABLE dbo.bit_camaras ADD id_motivo_camara INT NULL;
    END;
END;
GO

/* =========================================================
   5. Crear llave foránea si no existe
========================================================= */
IF OBJECT_ID(N'dbo.bit_camaras', N'U') IS NOT NULL
AND OBJECT_ID(N'dbo.bit_motivos_camaras', N'U') IS NOT NULL
AND NOT EXISTS (
    SELECT 1
    FROM sys.foreign_keys
    WHERE name = 'FK_bitacora_camaras_bit_motivos_camaras'
)
BEGIN
    ALTER TABLE dbo.bit_camaras
    ADD CONSTRAINT FK_bitacora_camaras_bit_motivos_camaras
    FOREIGN KEY (id_motivo_camara)
    REFERENCES dbo.bit_motivos_camaras(id_motivo_camara);
END;
GO

/* =========================================================
   6. Índices de apoyo
========================================================= */
IF OBJECT_ID(N'dbo.bit_camaras', N'U') IS NOT NULL
AND NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_bitacora_camaras_id_motivo_camara' AND object_id = OBJECT_ID('dbo.bit_camaras'))
BEGIN
    CREATE INDEX IX_bitacora_camaras_id_motivo_camara
    ON dbo.bit_camaras(id_motivo_camara);
END;
GO

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_bit_motivos_camaras_descripcion' AND object_id = OBJECT_ID('dbo.bit_motivos_camaras'))
BEGIN
    CREATE INDEX IX_bit_motivos_camaras_descripcion
    ON dbo.bit_motivos_camaras(descripcion);
END;
GO

/* =========================================================
   7. Verificación final
========================================================= */
SELECT TOP 20
    id_motivo_camara,
    sec_motivo,
    codigo_motivo,
    descripcion,
    nivel_sugerido,
    requiere_observacion,
    estado
FROM dbo.bit_motivos_camaras
ORDER BY sec_motivo;

SELECT
    COLUMN_NAME,
    DATA_TYPE,
    IS_NULLABLE
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_NAME = 'bitacora_camaras'
AND COLUMN_NAME = 'id_motivo_camara';
GO
