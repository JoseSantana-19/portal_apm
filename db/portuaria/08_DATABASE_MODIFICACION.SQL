/* =========================================================
   ACTUALIZACIÓN BITÁCORA DE CÁMARAS CCTV
   Estado: OPERATIVA / NO OPERATIVA
   Nuevo campo: nivel_alerta
========================================================= */

USE PortuariaDemo;
GO

/* 1. Ampliar el campo estado_camara para guardar OPERATIVA / NO OPERATIVA */
ALTER TABLE dbo.bit_camaras
ALTER COLUMN estado_camara NVARCHAR(20) NULL;
GO

/* 2. Agregar nivel_alerta si todavía no existe */
IF COL_LENGTH('dbo.bit_camaras', 'nivel_alerta') IS NULL
BEGIN
    ALTER TABLE dbo.bit_camaras
    ADD nivel_alerta NVARCHAR(20) NOT NULL
        CONSTRAINT DF_bitacora_camaras_nivel_alerta DEFAULT ('Normal');
END;
GO

/* 3. Actualizar registros antiguos */
UPDATE dbo.bit_camaras
SET estado_camara = CASE
    WHEN UPPER(LTRIM(RTRIM(estado_camara))) = 'OPER' THEN 'OPERATIVA'
    WHEN UPPER(LTRIM(RTRIM(estado_camara))) = 'NO OPER' THEN 'NO OPERATIVA'
    WHEN UPPER(LTRIM(RTRIM(estado_camara))) = 'OPERATIVA' THEN 'OPERATIVA'
    WHEN UPPER(LTRIM(RTRIM(estado_camara))) = 'NO OPERATIVA' THEN 'NO OPERATIVA'
    ELSE estado_camara
END;
GO

/* 4. Revisar */
SELECT TOP 10
    id_bitacora_camara,
    fecha,
    hora_registro,
    estado_camara,
    nivel_alerta,
    novedad,
    observaciones
FROM dbo.bit_camaras
ORDER BY id_bitacora_camara DESC;
GO

USE PortuariaDemo;
GO

/* =========================================================
   1. ELIMINAR CHECK ANTERIOR DEL ESTADO DE CÁMARA
========================================================= */
IF EXISTS (
    SELECT 1
    FROM sys.check_constraints
    WHERE name = 'CK_bitacora_camaras_estado_camara'
      AND parent_object_id = OBJECT_ID('dbo.bit_camaras')
)
BEGIN
    ALTER TABLE dbo.bit_camaras
    DROP CONSTRAINT CK_bitacora_camaras_estado_camara;
END;
GO

/* =========================================================
   2. ASEGURAR QUE EL CAMPO TENGA TAMAÑO SUFICIENTE
========================================================= */
ALTER TABLE dbo.bit_camaras
ALTER COLUMN estado_camara NVARCHAR(20) NULL;
GO

/* =========================================================
   3. AGREGAR NIVEL DE ALERTA SI NO EXISTE
========================================================= */
IF COL_LENGTH('dbo.bit_camaras', 'nivel_alerta') IS NULL
BEGIN
    ALTER TABLE dbo.bit_camaras
    ADD nivel_alerta NVARCHAR(20) NOT NULL
        CONSTRAINT DF_bitacora_camaras_nivel_alerta DEFAULT ('Normal');
END;
GO

/* =========================================================
   4. ACTUALIZAR DATOS ANTIGUOS
========================================================= */
UPDATE dbo.bit_camaras
SET estado_camara = CASE
    WHEN UPPER(LTRIM(RTRIM(estado_camara))) = 'OPER' THEN 'OPERATIVA'
    WHEN UPPER(LTRIM(RTRIM(estado_camara))) = 'NO OPER' THEN 'NO OPERATIVA'
    WHEN UPPER(LTRIM(RTRIM(estado_camara))) = 'OPERATIVA' THEN 'OPERATIVA'
    WHEN UPPER(LTRIM(RTRIM(estado_camara))) = 'NO OPERATIVA' THEN 'NO OPERATIVA'
    ELSE estado_camara
END;
GO

/* =========================================================
   5. CREAR NUEVO CHECK COMPATIBLE
   Dejo también OPER y NO OPER por seguridad hasta cambiar el código.
========================================================= */
ALTER TABLE dbo.bit_camaras
ADD CONSTRAINT CK_bitacora_camaras_estado_camara
CHECK (
    estado_camara IN ('OPER', 'NO OPER', 'OPERATIVA', 'NO OPERATIVA')
);
GO

/* =========================================================
   6. CREAR CHECK PARA NIVEL DE ALERTA SI NO EXISTE
========================================================= */
IF NOT EXISTS (
    SELECT 1
    FROM sys.check_constraints
    WHERE name = 'CK_bitacora_camaras_nivel_alerta'
      AND parent_object_id = OBJECT_ID('dbo.bit_camaras')
)
BEGIN
    ALTER TABLE dbo.bit_camaras
    ADD CONSTRAINT CK_bitacora_camaras_nivel_alerta
    CHECK (nivel_alerta IN ('Normal', 'Medio', 'Crítico'));
END;
GO

/* =========================================================
   7. REVISAR RESULTADO
========================================================= */
SELECT TOP 10
    id_bitacora_camara,
    fecha,
    hora_registro,
    estado_camara,
    nivel_alerta,
    novedad,
    observaciones
FROM dbo.bit_camaras
ORDER BY id_bitacora_camara DESC;
GO