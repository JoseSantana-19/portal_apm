/* =========================================================
   09_CCTV_MAESTROS_SECUENCIALES.sql
   Proyecto: Portal Portuario - CCTV Cámaras
   Objetivo:
   - Crear tabla de secuenciales con prefijo acc_
   - Ajustar maestro de cámaras inv_camaras
   - Crear maestro de motivos CCTV bit_motivos_camaras
   - Preparar integración futura con bitácora de cámaras

   Ejecutar en SQL Server Management Studio sobre la BD PortuariaDemo.
========================================================= */

USE PortuariaDemo;
GO

/* =========================================================
   1. TABLA GENERAL DE SECUENCIALES
   Prefijo acc_ porque controla numeración/acceso administrativo
========================================================= */
IF OBJECT_ID(N'dbo.acc_secuenciales', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.bit_acc_secuenciales (
        id_secuencial INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
        modulo NVARCHAR(80) NOT NULL,
        tabla NVARCHAR(128) NOT NULL,
        descripcion NVARCHAR(250) NULL,
        prefijo_codigo NVARCHAR(30) NOT NULL,
        ultimo_numero INT NOT NULL CONSTRAINT DF_acc_secuenciales_ultimo DEFAULT (0),
        longitud INT NOT NULL CONSTRAINT DF_acc_secuenciales_longitud DEFAULT (6),
        estado BIT NOT NULL CONSTRAINT DF_acc_secuenciales_estado DEFAULT (1),
        fecha_creacion DATETIME NOT NULL CONSTRAINT DF_acc_secuenciales_fecha_creacion DEFAULT (GETDATE()),
        fecha_actualizacion DATETIME NULL,
        CONSTRAINT UQ_acc_secuenciales_tabla UNIQUE (tabla)
    );
END;
GO

/* =========================================================
   2. AJUSTES A INVENTARIO DE CÁMARAS
   Tabla ya existente: dbo.bit_inv_camaras
========================================================= */
IF OBJECT_ID(N'dbo.bit_inv_camaras', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.bit_inv_camaras (
        id_camara INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
        sec_camara INT NULL,
        codigo_secuencial NVARCHAR(30) NULL,
        cod_old NVARCHAR(30) NULL,
        codigo NVARCHAR(30) NULL,
        tipo NVARCHAR(80) NULL,
        marca NVARCHAR(80) NULL,
        modelo NVARCHAR(120) NULL,
        tecnologia NVARCHAR(50) NULL,
        caracteristica NVARCHAR(250) NULL,
        ip NVARCHAR(50) NULL,
        mac NVARCHAR(80) NULL,
        serie NVARCHAR(120) NULL,
        ubicacion NVARCHAR(150) NULL,
        detalle NVARCHAR(200) NULL,
        grabador NVARCHAR(120) NULL,
        estado BIT NOT NULL CONSTRAINT DF_inv_camaras_estado DEFAULT (1),
        fecha_creacion DATETIME NOT NULL CONSTRAINT DF_inv_camaras_fecha_creacion DEFAULT (GETDATE()),
        fecha_actualizacion DATETIME NULL
    );
END;
GO

IF COL_LENGTH('dbo.bit_inv_camaras', 'sec_camara') IS NULL
BEGIN
    ALTER TABLE dbo.bit_inv_camaras ADD sec_camara INT NULL;
END;
GO

IF COL_LENGTH('dbo.bit_inv_camaras', 'codigo_secuencial') IS NULL
BEGIN
    ALTER TABLE dbo.bit_inv_camaras ADD codigo_secuencial NVARCHAR(30) NULL;
END;
GO

/* Completar secuenciales para cámaras existentes sin alterar el ID interno */
;WITH cams AS (
    SELECT
        id_camara,
        ROW_NUMBER() OVER (ORDER BY id_camara) AS rn
    FROM dbo.bit_inv_camaras
    WHERE sec_camara IS NULL
)
UPDATE ic
SET
    sec_camara = cams.rn,
    codigo_secuencial = ISNULL(ic.codigo_secuencial, 'CAM-' + RIGHT(REPLICATE('0', 6) + CAST(cams.rn AS VARCHAR(20)), 6))
FROM dbo.bit_inv_camaras ic
INNER JOIN cams ON ic.id_camara = cams.id_camara;
GO

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_inv_camaras_codigo_secuencial' AND object_id = OBJECT_ID('dbo.bit_inv_camaras'))
BEGIN
    CREATE INDEX IX_inv_camaras_codigo_secuencial
    ON dbo.bit_inv_camaras(codigo_secuencial);
END;
GO

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_inv_camaras_sec_camara' AND object_id = OBJECT_ID('dbo.bit_inv_camaras'))
BEGIN
    CREATE INDEX IX_inv_camaras_sec_camara
    ON dbo.bit_inv_camaras(sec_camara);
END;
GO

/* Registrar control secuencial para inv_camaras */
IF NOT EXISTS (SELECT 1 FROM dbo.bit_acc_secuenciales WHERE tabla = 'inv_camaras')
BEGIN
    INSERT INTO dbo.bit_acc_secuenciales (modulo, tabla, descripcion, prefijo_codigo, ultimo_numero, longitud)
    SELECT
        'Inventario CCTV',
        'inv_camaras',
        'Secuencial controlado para maestro de cámaras CCTV',
        'CAM-',
        ISNULL(MAX(sec_camara), 0),
        6
    FROM dbo.bit_inv_camaras;
END
ELSE
BEGIN
    UPDATE dbo.bit_acc_secuenciales
    SET ultimo_numero = CASE
            WHEN ultimo_numero < (SELECT ISNULL(MAX(sec_camara), 0) FROM dbo.bit_inv_camaras)
            THEN (SELECT ISNULL(MAX(sec_camara), 0) FROM dbo.bit_inv_camaras)
            ELSE ultimo_numero
        END,
        fecha_actualizacion = GETDATE()
    WHERE tabla = 'inv_camaras';
END;
GO

/* =========================================================
   3. MAESTRO DE MOTIVOS CCTV
   Prefijo bit_ porque alimenta la bitácora de cámaras
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

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_bit_motivos_camaras_descripcion' AND object_id = OBJECT_ID('dbo.bit_motivos_camaras'))
BEGIN
    CREATE INDEX IX_bit_motivos_camaras_descripcion
    ON dbo.bit_motivos_camaras(descripcion);
END;
GO

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_bit_motivos_camaras_codigo' AND object_id = OBJECT_ID('dbo.bit_motivos_camaras'))
BEGIN
    CREATE INDEX IX_bit_motivos_camaras_codigo
    ON dbo.bit_motivos_camaras(codigo_motivo);
END;
GO

/* Motivos iniciales recomendados para CCTV */
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

/* Completar secuenciales en motivos existentes */
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

/* Registrar control secuencial para bit_motivos_camaras */
IF NOT EXISTS (SELECT 1 FROM dbo.bit_acc_secuenciales WHERE tabla = 'bit_motivos_camaras')
BEGIN
    INSERT INTO dbo.bit_acc_secuenciales (modulo, tabla, descripcion, prefijo_codigo, ultimo_numero, longitud)
    SELECT
        'Bitácoras CCTV',
        'bit_motivos_camaras',
        'Secuencial controlado para motivos de novedades de cámaras CCTV',
        'MOT-CCTV-',
        ISNULL(MAX(sec_motivo), 0),
        6
    FROM dbo.bit_motivos_camaras;
END
ELSE
BEGIN
    UPDATE dbo.bit_acc_secuenciales
    SET ultimo_numero = CASE
            WHEN ultimo_numero < (SELECT ISNULL(MAX(sec_motivo), 0) FROM dbo.bit_motivos_camaras)
            THEN (SELECT ISNULL(MAX(sec_motivo), 0) FROM dbo.bit_motivos_camaras)
            ELSE ultimo_numero
        END,
        fecha_actualizacion = GETDATE()
    WHERE tabla = 'bit_motivos_camaras';
END;
GO

/* =========================================================
   4. PREPARAR BITÁCORA PARA INTEGRAR MOTIVOS MÁS ADELANTE
   No rompe registros actuales porque queda NULL.
========================================================= */
IF OBJECT_ID(N'dbo.bit_camaras', N'U') IS NOT NULL
BEGIN
    IF COL_LENGTH('dbo.bit_camaras', 'id_motivo_camara') IS NULL
    BEGIN
        ALTER TABLE dbo.bit_camaras ADD id_motivo_camara INT NULL;
    END;
END;
GO

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
   5. VERIFICACIÓN FINAL
========================================================= */
SELECT * FROM dbo.bit_acc_secuenciales WHERE tabla IN ('inv_camaras', 'bit_motivos_camaras');
SELECT TOP 10 id_camara, sec_camara, codigo_secuencial, cod_old, codigo, ip, ubicacion, detalle FROM dbo.bit_inv_camaras ORDER BY id_camara;
SELECT TOP 20 id_motivo_camara, sec_motivo, codigo_motivo, descripcion, nivel_sugerido, requiere_observacion, estado FROM dbo.bit_motivos_camaras ORDER BY sec_motivo;
GO
