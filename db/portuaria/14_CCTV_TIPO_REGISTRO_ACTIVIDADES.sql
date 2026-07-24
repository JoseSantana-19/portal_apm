/* =========================================================
   14_CCTV_TIPO_REGISTRO_ACTIVIDADES.sql
   Proyecto: Portal Portuario - CCTV Cámaras
   Objetivo:
   - Permitir que la Bitácora de Cámaras CCTV registre:
     1) Actividades diarias del turno.
     2) Novedades específicas de cámaras, cuando existan.
   - Mantener PHP puro y la tabla existente dbo.bit_camaras.

   Ejecutar en SQL Server Management Studio sobre la BD PortuariaDemo.
========================================================= */

USE PortuariaDemo;
GO

IF OBJECT_ID(N'dbo.bit_camaras', N'U') IS NULL
BEGIN
    RAISERROR('No existe dbo.bit_camaras. Ejecute primero los scripts anteriores del módulo CCTV.', 16, 1);
    RETURN;
END;
GO

/* =========================================================
   1. Tipo de registro
   ACTIVIDAD_DIARIA: actividades normales del turno.
   NOVEDAD_CAMARA: novedad/falla asociada a una cámara.
========================================================= */
IF COL_LENGTH('dbo.bit_camaras', 'tipo_registro') IS NULL
BEGIN
    ALTER TABLE dbo.bit_camaras
    ADD tipo_registro NVARCHAR(30) NOT NULL
        CONSTRAINT DF_bitacora_camaras_tipo_registro
        DEFAULT ('NOVEDAD_CAMARA');
END;
GO

/* =========================================================
   2. Rol del responsable
   Consolista / Inspector / Supervisor.
========================================================= */
IF COL_LENGTH('dbo.bit_camaras', 'rol_responsable') IS NULL
BEGIN
    ALTER TABLE dbo.bit_camaras
    ADD rol_responsable NVARCHAR(50) NULL;
END;
GO

/* Completar registros anteriores para que no queden vacíos. */
UPDATE dbo.bit_camaras
SET tipo_registro = 'NOVEDAD_CAMARA'
WHERE tipo_registro IS NULL
   OR LTRIM(RTRIM(tipo_registro)) = '';
GO

UPDATE dbo.bit_camaras
SET rol_responsable = 'Consolista'
WHERE rol_responsable IS NULL
   OR LTRIM(RTRIM(rol_responsable)) = '';
GO

/* =========================================================
   3. Restricciones de valores permitidos
========================================================= */
IF NOT EXISTS (
    SELECT 1
    FROM sys.check_constraints
    WHERE name = 'CK_bitacora_camaras_tipo_registro'
      AND parent_object_id = OBJECT_ID('dbo.bit_camaras')
)
BEGIN
    ALTER TABLE dbo.bit_camaras
    ADD CONSTRAINT CK_bitacora_camaras_tipo_registro
    CHECK (tipo_registro IN ('ACTIVIDAD_DIARIA', 'NOVEDAD_CAMARA'));
END;
GO

IF NOT EXISTS (
    SELECT 1
    FROM sys.check_constraints
    WHERE name = 'CK_bitacora_camaras_rol_responsable'
      AND parent_object_id = OBJECT_ID('dbo.bit_camaras')
)
BEGIN
    ALTER TABLE dbo.bit_camaras
    ADD CONSTRAINT CK_bitacora_camaras_rol_responsable
    CHECK (rol_responsable IS NULL OR rol_responsable IN ('Consolista', 'Inspector', 'Supervisor'));
END;
GO

/* =========================================================
   4. Índices de apoyo para consulta
========================================================= */
IF NOT EXISTS (
    SELECT 1
    FROM sys.indexes
    WHERE name = 'IX_bitacora_camaras_tipo_registro'
      AND object_id = OBJECT_ID('dbo.bit_camaras')
)
BEGIN
    CREATE INDEX IX_bitacora_camaras_tipo_registro
    ON dbo.bit_camaras (tipo_registro, fecha, turno);
END;
GO

SELECT
    'OK' AS resultado,
    'Bitácora CCTV actualizada para Actividad diaria y Novedad de cámara.' AS mensaje;
GO
