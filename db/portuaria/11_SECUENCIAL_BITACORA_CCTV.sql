/* =========================================================
   11_SECUENCIAL_BITACORA_CCTV.sql
   Proyecto: Portal Portuario - CCTV Cámaras
   Objetivo:
   - Agregar secuencial controlado a la bitácora de cámaras.
   - Evitar depender visualmente del IDENTITY interno de SQL Server.
   - Registrar el control en dbo.bit_acc_secuenciales con prefijo acc_.

   Ejecutar en SQL Server Management Studio sobre la BD PortuariaDemo.
========================================================= */

USE PortuariaDemo;
GO

/* =========================================================
   1. Asegurar tabla acc_secuenciales
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
   2. Validar tabla principal de bitácora
========================================================= */
IF OBJECT_ID(N'dbo.bit_camaras', N'U') IS NULL
BEGIN
    RAISERROR('No existe dbo.bit_camaras. Ejecute primero los scripts anteriores del módulo CCTV.', 16, 1);
    RETURN;
END;
GO

/* =========================================================
   3. Agregar columnas de secuencial visible
========================================================= */
IF COL_LENGTH('dbo.bit_camaras', 'sec_bitacora') IS NULL
BEGIN
    ALTER TABLE dbo.bit_camaras ADD sec_bitacora INT NULL;
END;
GO

IF COL_LENGTH('dbo.bit_camaras', 'codigo_bitacora') IS NULL
BEGIN
    ALTER TABLE dbo.bit_camaras ADD codigo_bitacora NVARCHAR(30) NULL;
END;
GO

/* =========================================================
   4. Completar secuenciales para registros ya existentes
   Esto NO cambia el id interno, solo crea un código visible.
========================================================= */
;WITH orden AS (
    SELECT
        id_bitacora_camara,
        ROW_NUMBER() OVER (ORDER BY fecha, hora_registro, id_bitacora_camara) AS rn
    FROM dbo.bit_camaras
    WHERE sec_bitacora IS NULL
)
UPDATE bc
SET
    sec_bitacora = orden.rn,
    codigo_bitacora = ISNULL(
        bc.codigo_bitacora,
        'BIT-CCTV-' + RIGHT(REPLICATE('0', 6) + CAST(orden.rn AS VARCHAR(20)), 6)
    )
FROM dbo.bit_camaras bc
INNER JOIN orden
    ON bc.id_bitacora_camara = orden.id_bitacora_camara;
GO

/* =========================================================
   5. Índices de apoyo
========================================================= */
IF NOT EXISTS (
    SELECT 1
    FROM sys.indexes
    WHERE name = 'IX_bitacora_camaras_sec_bitacora'
      AND object_id = OBJECT_ID('dbo.bit_camaras')
)
BEGIN
    CREATE INDEX IX_bitacora_camaras_sec_bitacora
    ON dbo.bit_camaras(sec_bitacora);
END;
GO

IF NOT EXISTS (
    SELECT 1
    FROM sys.indexes
    WHERE name = 'IX_bitacora_camaras_codigo_bitacora'
      AND object_id = OBJECT_ID('dbo.bit_camaras')
)
BEGIN
    CREATE INDEX IX_bitacora_camaras_codigo_bitacora
    ON dbo.bit_camaras(codigo_bitacora);
END;
GO

/* =========================================================
   6. Registrar control secuencial para nuevos registros
========================================================= */
IF NOT EXISTS (SELECT 1 FROM dbo.bit_acc_secuenciales WHERE tabla = 'bitacora_camaras')
BEGIN
    INSERT INTO dbo.bit_acc_secuenciales (
        modulo,
        tabla,
        descripcion,
        prefijo_codigo,
        ultimo_numero,
        longitud
    )
    SELECT
        'Bitácoras CCTV',
        'bitacora_camaras',
        'Secuencial controlado para registros de bitácora de cámaras CCTV',
        'BIT-CCTV-',
        ISNULL(MAX(sec_bitacora), 0),
        6
    FROM dbo.bit_camaras;
END
ELSE
BEGIN
    UPDATE dbo.bit_acc_secuenciales
    SET ultimo_numero = CASE
            WHEN ultimo_numero < (SELECT ISNULL(MAX(sec_bitacora), 0) FROM dbo.bit_camaras)
            THEN (SELECT ISNULL(MAX(sec_bitacora), 0) FROM dbo.bit_camaras)
            ELSE ultimo_numero
        END,
        prefijo_codigo = 'BIT-CCTV-',
        longitud = 6,
        estado = 1,
        fecha_actualizacion = GETDATE()
    WHERE tabla = 'bitacora_camaras';
END;
GO

/* =========================================================
   7. Verificación final
========================================================= */
SELECT *
FROM dbo.bit_acc_secuenciales
WHERE tabla IN ('inv_camaras', 'bit_motivos_camaras', 'bitacora_camaras');

SELECT TOP 20
    id_bitacora_camara,
    sec_bitacora,
    codigo_bitacora,
    fecha,
    hora_registro,
    estado_camara,
    nivel_alerta
FROM dbo.bit_camaras
ORDER BY sec_bitacora DESC, id_bitacora_camara DESC;
GO
