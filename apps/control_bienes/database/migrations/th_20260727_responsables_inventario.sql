/*
  Integración de responsables entre Talento_Humano e inventario.
  Ejecutado el 2026-07-27 después de restaurar Talento_Humano.bak.
  Es idempotente: puede volver a ejecutarse sin borrar información.
*/

USE [inventario];
GO

IF COL_LENGTH('dbo.inv_talento_personal', 'cargo') IS NULL
    ALTER TABLE dbo.inv_talento_personal ADD cargo NVARCHAR(150) NULL;
IF COL_LENGTH('dbo.inv_talento_personal', 'area') IS NULL
    ALTER TABLE dbo.inv_talento_personal ADD area NVARCHAR(150) NULL;
IF COL_LENGTH('dbo.inv_talento_personal', 'correo') IS NULL
    ALTER TABLE dbo.inv_talento_personal ADD correo NVARCHAR(150) NULL;
IF COL_LENGTH('dbo.inv_talento_personal', 'estado') IS NULL
    ALTER TABLE dbo.inv_talento_personal ADD estado BIT NOT NULL
        CONSTRAINT DF_inv_talento_personal_estado DEFAULT (1);
IF COL_LENGTH('dbo.inv_talento_personal', 'fecha_sincronizacion') IS NULL
    ALTER TABLE dbo.inv_talento_personal ADD fecha_sincronizacion DATETIME2 NULL;
GO

UPDATE destino
SET destino.nombre = LTRIM(RTRIM(COALESCE(origen.apellidos, '') + ' ' + COALESCE(origen.nombres, ''))),
    destino.identificacion = origen.identificacion,
    destino.cargo = COALESCE(puesto.nombre_puesto, ''),
    destino.area = COALESCE(unidad.nombre_unidad, ''),
    destino.correo = COALESCE(origen.correo_institucional, ''),
    destino.estado = CONVERT(BIT, COALESCE(origen.estado, 0)),
    destino.fecha_sincronizacion = SYSDATETIME()
FROM dbo.inv_talento_personal destino
JOIN Talento_Humano.dbo.th_empleados origen ON origen.empleado_id = destino.id
LEFT JOIN Talento_Humano.dbo.th_puestos puesto ON puesto.puesto_id = origen.puesto_id
LEFT JOIN Talento_Humano.dbo.th_unidades_organizacionales unidad ON unidad.unidad_id = origen.unidad_id;

SET IDENTITY_INSERT dbo.inv_talento_personal ON;
INSERT INTO dbo.inv_talento_personal
    (id, nombre, identificacion, cargo, area, correo, estado, fecha_sincronizacion)
SELECT origen.empleado_id,
       LTRIM(RTRIM(COALESCE(origen.apellidos, '') + ' ' + COALESCE(origen.nombres, ''))),
       origen.identificacion,
       COALESCE(puesto.nombre_puesto, ''),
       COALESCE(unidad.nombre_unidad, ''),
       COALESCE(origen.correo_institucional, ''),
       CONVERT(BIT, COALESCE(origen.estado, 0)),
       SYSDATETIME()
FROM Talento_Humano.dbo.th_empleados origen
LEFT JOIN Talento_Humano.dbo.th_puestos puesto ON puesto.puesto_id = origen.puesto_id
LEFT JOIN Talento_Humano.dbo.th_unidades_organizacionales unidad ON unidad.unidad_id = origen.unidad_id
WHERE NOT EXISTS (
    SELECT 1 FROM dbo.inv_talento_personal destino WHERE destino.id = origen.empleado_id
);
SET IDENTITY_INSERT dbo.inv_talento_personal OFF;

UPDATE destino
SET destino.estado = 0,
    destino.fecha_sincronizacion = SYSDATETIME()
FROM dbo.inv_talento_personal destino
WHERE NOT EXISTS (
    SELECT 1 FROM Talento_Humano.dbo.th_empleados origen
    WHERE origen.empleado_id = destino.id
);
GO

USE [Talento_Humano];
GO

CREATE OR ALTER PROCEDURE dbo.sp_th_buscar_responsables
    @termino NVARCHAR(150) = NULL,
    @identificacion VARCHAR(20) = NULL,
    @solo_activos BIT = 1
AS
BEGIN
    SET NOCOUNT ON;

    SELECT e.empleado_id AS id_responsable,
           e.identificacion,
           LTRIM(RTRIM(COALESCE(e.apellidos, '') + ' ' + COALESCE(e.nombres, ''))) AS nombre_completo,
           e.apellidos,
           e.nombres,
           e.unidad_id,
           COALESCE(u.nombre_unidad, '') AS unidad,
           e.puesto_id,
           COALESCE(p.nombre_puesto, '') AS cargo,
           COALESCE(e.correo_institucional, '') AS correo_institucional,
           e.estado,
           COALESCE(e.cargas_familiares, 0) AS cargas_familiares,
           e.tipo_cuenta_bancaria,
           e.numero_cuenta_bancaria,
           e.institucion_bancaria
    FROM dbo.th_empleados e
    LEFT JOIN dbo.th_unidades_organizacionales u ON u.unidad_id = e.unidad_id
    LEFT JOIN dbo.th_puestos p ON p.puesto_id = e.puesto_id
    WHERE (@solo_activos = 0 OR e.estado = 1)
      AND (@identificacion IS NULL OR e.identificacion = @identificacion)
      AND (@termino IS NULL
           OR e.identificacion LIKE '%' + @termino + '%'
           OR e.nombres LIKE '%' + @termino + '%'
           OR e.apellidos LIKE '%' + @termino + '%')
    ORDER BY e.apellidos, e.nombres;
END;
GO

CREATE OR ALTER TRIGGER dbo.trg_sync_th_empleados_to_inventario
ON dbo.th_empleados
AFTER INSERT, UPDATE, DELETE
AS
BEGIN
    SET NOCOUNT ON;

    UPDATE destino
    SET destino.nombre = LTRIM(RTRIM(COALESCE(origen.apellidos, '') + ' ' + COALESCE(origen.nombres, ''))),
        destino.identificacion = origen.identificacion,
        destino.cargo = COALESCE(puesto.nombre_puesto, ''),
        destino.area = COALESCE(unidad.nombre_unidad, ''),
        destino.correo = COALESCE(origen.correo_institucional, ''),
        destino.estado = CONVERT(BIT, COALESCE(origen.estado, 0)),
        destino.fecha_sincronizacion = SYSDATETIME()
    FROM inventario.dbo.inv_talento_personal destino
    JOIN inserted origen ON origen.empleado_id = destino.id
    LEFT JOIN dbo.th_puestos puesto ON puesto.puesto_id = origen.puesto_id
    LEFT JOIN dbo.th_unidades_organizacionales unidad ON unidad.unidad_id = origen.unidad_id;

    SET IDENTITY_INSERT inventario.dbo.inv_talento_personal ON;
    INSERT INTO inventario.dbo.inv_talento_personal
        (id, nombre, identificacion, cargo, area, correo, estado, fecha_sincronizacion)
    SELECT origen.empleado_id,
           LTRIM(RTRIM(COALESCE(origen.apellidos, '') + ' ' + COALESCE(origen.nombres, ''))),
           origen.identificacion,
           COALESCE(puesto.nombre_puesto, ''),
           COALESCE(unidad.nombre_unidad, ''),
           COALESCE(origen.correo_institucional, ''),
           CONVERT(BIT, COALESCE(origen.estado, 0)),
           SYSDATETIME()
    FROM inserted origen
    LEFT JOIN dbo.th_puestos puesto ON puesto.puesto_id = origen.puesto_id
    LEFT JOIN dbo.th_unidades_organizacionales unidad ON unidad.unidad_id = origen.unidad_id
    WHERE NOT EXISTS (
        SELECT 1 FROM inventario.dbo.inv_talento_personal destino
        WHERE destino.id = origen.empleado_id
    );
    SET IDENTITY_INSERT inventario.dbo.inv_talento_personal OFF;

    UPDATE destino
    SET destino.estado = 0,
        destino.fecha_sincronizacion = SYSDATETIME()
    FROM inventario.dbo.inv_talento_personal destino
    JOIN deleted eliminado ON eliminado.empleado_id = destino.id
    WHERE NOT EXISTS (
        SELECT 1 FROM inserted origen WHERE origen.empleado_id = eliminado.empleado_id
    );
END;
GO
