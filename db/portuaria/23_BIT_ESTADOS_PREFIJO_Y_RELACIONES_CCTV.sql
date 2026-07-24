USE PortuariaDemo;
GO

/* =========================================================
   23 - TABLA bit_estados PARA MÓDULO BITÁCORAS

   Indicaciones aplicadas:
   - Tabla con prefijo: dbo.bit_estados
   - Campos: idestado, descripcion, detalle, estado
   - Reutiliza estados base 0 al 22
   - Estados nuevos para Bitácoras dentro del rango 100 a 110

   Códigos usados en CCTV:
   100 = NO OPERATIVA
   101 = OPERATIVA
   102 = ACTIVIDAD_DIARIA
   103 = NOVEDAD_CAMARA
   104 = NORMAL
   105 = MEDIO
   106 = CRITICO

   Campo estado:
   1 = Registro activo del catálogo
   0 = Registro anulado/inactivo del catálogo
========================================================= */

/* =========================================================
   1. CREAR TABLA dbo.bit_estados
========================================================= */
IF OBJECT_ID('dbo.bit_estados', 'U') IS NULL
BEGIN
    CREATE TABLE dbo.bit_estados (
        idestado INT NOT NULL,
        descripcion NVARCHAR(100) NOT NULL,
        detalle NVARCHAR(255) NULL,
        estado BIT NOT NULL CONSTRAINT DF_bit_estados_estado DEFAULT (1),
        CONSTRAINT PK_bit_estados PRIMARY KEY (idestado),
        CONSTRAINT CK_bit_estados_estado CHECK (estado IN (0, 1))
    );
END;
GO

/* Si por alguna razón la tabla existía incompleta, asegurar campos básicos */
IF COL_LENGTH('dbo.bit_estados', 'descripcion') IS NULL
BEGIN
    ALTER TABLE dbo.bit_estados ADD descripcion NVARCHAR(100) NULL;
END;
GO

IF COL_LENGTH('dbo.bit_estados', 'detalle') IS NULL
BEGIN
    ALTER TABLE dbo.bit_estados ADD detalle NVARCHAR(255) NULL;
END;
GO

IF COL_LENGTH('dbo.bit_estados', 'estado') IS NULL
BEGIN
    ALTER TABLE dbo.bit_estados ADD estado BIT NOT NULL CONSTRAINT DF_bit_estados_estado_auto DEFAULT (1);
END;
GO

/* =========================================================
   2. INSERTAR / ACTUALIZAR ESTADOS BASE Y ESTADOS DE BITÁCORAS
========================================================= */
MERGE dbo.bit_estados AS destino
USING (
    VALUES
        (0,   N'ANULADO',          N'REG. ANULADO',                                      1),
        (1,   N'TODOS',            N'REGISTRO ACTIVO',                                   1),
        (2,   N'EN TRAMITE',       N'SOLICITUD EN TRAMITE',                              1),
        (3,   N'APROBADO',         N'SOLICITUD A SIDO ATENDIDA/APROBADA',                1),
        (4,   N'SOLICITADO',       N'NO SE HA EMPEZADO A ATENDER POR APM',               1),
        (5,   N'AUTORIZADO',       N'autorizacion',                                      1),
        (6,   N'VIGENTE',          N'desautorizado algo autorizado',                     1),
        (7,   N'NO AUTORIZADO',    N'PERMISO NEGADO',                                    1),
        (8,   N'VERIFICADO',       N'VERIFICACIÓN DE DCTOS EN ACCESOS OK',               1),
        (9,   N'ATENDIDO',         N'CERRADO - YA ATENDIDO',                             1),
        (10,  N'NO APROBADO',      N'TRAMITE NO APROBADO',                               1),
        (11,  N'NO VIGENTES',      N'OPERADORES PERMISO CADUCADOS',                      1),
        (12,  N'REGISTRADO',       N'REGISTRADO',                                        1),
        (13,  N'NO REGISTRADO',    N'NO REGISTRADO',                                     1),
        (14,  N'ACEPTADO',         N'SE REGISTRA SI EN EL PROCESO DE VALID. PROCEDE',    1),
        (15,  N'RECHAZADO',        N'POLIZA NO VALIDA',                                  1),
        (16,  N'CORRECTO',         N'DATOS VERIFICADO CON SRI SPTMF CORRECTOS',          1),
        (17,  N'INCORRECTO',       N'DATOS REVISADOS ESTAN INCORRECTOS',                 1),
        (18,  N'FAVORABLE',        N'INFORME FAVORABLE',                                 1),
        (19,  N'NO FAVORABLE',     N'INFORME NO FAVORABLE',                              1),
        (20,  N'REVISADO',         N'DCTO REVISADO',                                     1),
        (21,  N'PENDIENTE',        N'DCTO PENDIENTE DE REVISION',                        1),
        (22,  N'DEVOLVER',         N'DEVOLVER POLIZA',                                   1),

        /* Estados propios del módulo Bitácoras CCTV: rango 100 a 110 */
        (100, N'NO OPERATIVA',     N'Cámara CCTV no operativa',                          1),
        (101, N'OPERATIVA',        N'Cámara CCTV operativa',                             1),
        (102, N'ACTIVIDAD_DIARIA', N'Registro de actividad diaria del turno CCTV',       1),
        (103, N'NOVEDAD_CAMARA',   N'Registro de novedad asociada a cámara CCTV',        1),
        (104, N'NORMAL',           N'Nivel de alerta normal',                            1),
        (105, N'MEDIO',            N'Nivel de alerta medio',                             1),
        (106, N'CRITICO',          N'Nivel de alerta crítico',                           1)
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

/* =========================================================
   3. ELIMINAR FK ANTERIORES DE bitacora_camaras HACIA estados
      PARA tipo_registro, estado_camara y nivel_alerta
========================================================= */
DECLARE @sqlFK NVARCHAR(MAX) = N'';

SELECT @sqlFK = @sqlFK +
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
  AND c.name IN ('tipo_registro', 'estado_camara', 'nivel_alerta');

IF (@sqlFK <> '')
BEGIN
    EXEC sp_executesql @sqlFK;
END;
GO

/* =========================================================
   4. CREAR FK HACIA dbo.bit_estados
========================================================= */
IF NOT EXISTS (
    SELECT 1 FROM sys.foreign_keys
    WHERE name = 'FK_bitacora_camaras_tipo_registro_bit_estados'
)
BEGIN
    ALTER TABLE dbo.bit_camaras WITH CHECK
    ADD CONSTRAINT FK_bitacora_camaras_tipo_registro_bit_estados
    FOREIGN KEY (tipo_registro)
    REFERENCES dbo.bit_estados(idestado);
END;
GO

IF NOT EXISTS (
    SELECT 1 FROM sys.foreign_keys
    WHERE name = 'FK_bitacora_camaras_estado_camara_bit_estados'
)
BEGIN
    ALTER TABLE dbo.bit_camaras WITH CHECK
    ADD CONSTRAINT FK_bitacora_camaras_estado_camara_bit_estados
    FOREIGN KEY (estado_camara)
    REFERENCES dbo.bit_estados(idestado);
END;
GO

IF NOT EXISTS (
    SELECT 1 FROM sys.foreign_keys
    WHERE name = 'FK_bitacora_camaras_nivel_alerta_bit_estados'
)
BEGIN
    ALTER TABLE dbo.bit_camaras WITH CHECK
    ADD CONSTRAINT FK_bitacora_camaras_nivel_alerta_bit_estados
    FOREIGN KEY (nivel_alerta)
    REFERENCES dbo.bit_estados(idestado);
END;
GO

/* =========================================================
   5. VERIFICACIÓN DE TABLA Y DATOS
========================================================= */
SELECT
    COLUMN_NAME AS columna,
    DATA_TYPE AS tipo_dato,
    IS_NULLABLE AS permite_null
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_NAME = 'bit_estados'
ORDER BY ORDINAL_POSITION;
GO

SELECT
    idestado,
    descripcion,
    detalle,
    estado
FROM dbo.bit_estados
ORDER BY idestado;
GO

SELECT
    fk.name AS nombre_fk,
    OBJECT_NAME(fk.parent_object_id) AS tabla_origen,
    COL_NAME(fkc.parent_object_id, fkc.parent_column_id) AS columna_origen,
    OBJECT_NAME(fk.referenced_object_id) AS tabla_referencia,
    COL_NAME(fkc.referenced_object_id, fkc.referenced_column_id) AS columna_referencia
FROM sys.foreign_keys fk
INNER JOIN sys.foreign_key_columns fkc
    ON fk.object_id = fkc.constraint_object_id
WHERE OBJECT_NAME(fk.parent_object_id) = 'bitacora_camaras'
  AND COL_NAME(fkc.parent_object_id, fkc.parent_column_id) IN ('tipo_registro', 'estado_camara', 'nivel_alerta')
ORDER BY columna_origen;
GO

/* Confirmar que las tablas principales tienen campo estado */
SELECT
    t.name AS tabla,
    c.name AS campo_estado,
    ty.name AS tipo_dato
FROM sys.tables t
INNER JOIN sys.columns c ON t.object_id = c.object_id
INNER JOIN sys.types ty ON c.user_type_id = ty.user_type_id
WHERE t.name IN ('bit_estados', 'bitacora_camaras', 'bit_motivos_camaras', 'inv_camaras')
  AND c.name = 'estado'
ORDER BY t.name;
GO
