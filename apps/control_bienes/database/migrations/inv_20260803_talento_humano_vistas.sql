/* Integracion de Control de Bienes con Talento Humano mediante vistas.
   Compatible con SQL Server 2014 e idempotente. */
USE [inventario];
GO

UPDATE destino
SET destino.nombre = origen.apellidos_nombres,
    destino.identificacion = origen.cedula,
    destino.cargo = COALESCE(origen.cargo, ''),
    destino.area = COALESCE(origen.direccion_area, ''),
    destino.correo = COALESCE(origen.correo_institucional, ''),
    destino.estado = CONVERT(BIT, CASE WHEN origen.estado = 1 THEN 1 ELSE 0 END),
    destino.fecha_sincronizacion = GETDATE()
FROM dbo.inv_talento_personal destino
JOIN Talento_Humano.dbo.vw_th_directorio_empleados origen
  ON origen.empleado_id = destino.id;

SET IDENTITY_INSERT dbo.inv_talento_personal ON;
INSERT INTO dbo.inv_talento_personal
    (id, nombre, identificacion, cargo, area, correo, estado, fecha_sincronizacion)
SELECT origen.empleado_id, origen.apellidos_nombres, origen.cedula,
       COALESCE(origen.cargo, ''), COALESCE(origen.direccion_area, ''),
       COALESCE(origen.correo_institucional, ''),
       CONVERT(BIT, CASE WHEN origen.estado = 1 THEN 1 ELSE 0 END), GETDATE()
FROM Talento_Humano.dbo.vw_th_directorio_empleados origen
WHERE NOT EXISTS (SELECT 1 FROM dbo.inv_talento_personal destino WHERE destino.id = origen.empleado_id);
SET IDENTITY_INSERT dbo.inv_talento_personal OFF;
GO

IF OBJECT_ID('dbo.vw_inv_talento_personal', 'V') IS NOT NULL DROP VIEW dbo.vw_inv_talento_personal;
GO
CREATE VIEW dbo.vw_inv_talento_personal AS
SELECT origen.empleado_id AS id, origen.apellidos_nombres AS nombre,
       origen.cedula AS identificacion, origen.cargo,
       origen.direccion_area AS area, origen.correo_institucional AS correo,
       CONVERT(BIT, CASE WHEN origen.estado = 1 THEN 1 ELSE 0 END) AS estado
FROM Talento_Humano.dbo.vw_th_directorio_empleados origen
UNION ALL
SELECT legado.id, legado.nombre, legado.identificacion, legado.cargo,
       legado.area, legado.correo, legado.estado
FROM dbo.inv_talento_personal legado
WHERE NOT EXISTS (
    SELECT 1 FROM Talento_Humano.dbo.vw_th_directorio_empleados origen
    WHERE origen.empleado_id = legado.id
);
GO

IF OBJECT_ID('dbo.vw_inv_grupos_centros_personal', 'V') IS NOT NULL DROP VIEW dbo.vw_inv_grupos_centros_personal;
GO
CREATE VIEW dbo.vw_inv_grupos_centros_personal AS
SELECT gcc.id, gcc.codigo, gcc.nombre, gcc.representante_id,
       COALESCE(NULLIF(persona.nombre, ''), gcc.representante) AS representante,
       persona.identificacion AS representante_identificacion,
       persona.area AS representante_area, persona.estado AS representante_activo
FROM dbo.inv_grupo_centros_consumo gcc
LEFT JOIN dbo.vw_inv_talento_personal persona ON persona.id = gcc.representante_id;
GO

IF OBJECT_ID('dbo.vw_inv_centros_consumo_personal', 'V') IS NOT NULL DROP VIEW dbo.vw_inv_centros_consumo_personal;
GO
CREATE VIEW dbo.vw_inv_centros_consumo_personal AS
SELECT cc.id, cc.grupo_id, cc.codigo, cc.nombre, cc.funcionario_id,
       COALESCE(NULLIF(persona.nombre, ''), cc.funcionario) AS funcionario,
       persona.identificacion AS funcionario_identificacion,
       persona.area AS funcionario_area, persona.estado AS funcionario_activo,
       gcc.nombre AS grupo_nombre
FROM dbo.inv_centros_consumo cc
JOIN dbo.inv_grupo_centros_consumo gcc ON gcc.id = cc.grupo_id
LEFT JOIN dbo.vw_inv_talento_personal persona ON persona.id = cc.funcionario_id;
GO

USE [Talento_Humano];
GO
IF OBJECT_ID('dbo.trg_sync_th_empleados_to_inventario', 'TR') IS NOT NULL DROP TRIGGER dbo.trg_sync_th_empleados_to_inventario;
GO
CREATE TRIGGER dbo.trg_sync_th_empleados_to_inventario ON dbo.th_empleados
AFTER INSERT, UPDATE, DELETE AS
BEGIN
    SET NOCOUNT ON;
    UPDATE destino
    SET destino.nombre = LTRIM(RTRIM(COALESCE(origen.apellidos, '') + ' ' + COALESCE(origen.nombres, ''))),
        destino.identificacion = origen.identificacion,
        destino.cargo = COALESCE(puesto.nombre_puesto, ''),
        destino.area = COALESCE(unidad.nombre_unidad, ''),
        destino.correo = COALESCE(origen.correo_institucional, ''),
        destino.estado = CONVERT(BIT, CASE WHEN origen.estado = 1 THEN 1 ELSE 0 END),
        destino.fecha_sincronizacion = GETDATE()
    FROM inventario.dbo.inv_talento_personal destino
    JOIN inserted origen ON origen.empleado_id = destino.id
    LEFT JOIN dbo.th_puestos puesto ON puesto.puesto_id = origen.puesto_id
    LEFT JOIN dbo.th_unidades_organizacionales unidad ON unidad.unidad_id = origen.unidad_id;

    SET IDENTITY_INSERT inventario.dbo.inv_talento_personal ON;
    INSERT INTO inventario.dbo.inv_talento_personal
        (id, nombre, identificacion, cargo, area, correo, estado, fecha_sincronizacion)
    SELECT origen.empleado_id,
           LTRIM(RTRIM(COALESCE(origen.apellidos, '') + ' ' + COALESCE(origen.nombres, ''))),
           origen.identificacion, COALESCE(puesto.nombre_puesto, ''),
           COALESCE(unidad.nombre_unidad, ''), COALESCE(origen.correo_institucional, ''),
           CONVERT(BIT, CASE WHEN origen.estado = 1 THEN 1 ELSE 0 END), GETDATE()
    FROM inserted origen
    LEFT JOIN dbo.th_puestos puesto ON puesto.puesto_id = origen.puesto_id
    LEFT JOIN dbo.th_unidades_organizacionales unidad ON unidad.unidad_id = origen.unidad_id
    WHERE NOT EXISTS (SELECT 1 FROM inventario.dbo.inv_talento_personal destino WHERE destino.id = origen.empleado_id);
    SET IDENTITY_INSERT inventario.dbo.inv_talento_personal OFF;

    UPDATE destino SET destino.estado = 0, destino.fecha_sincronizacion = GETDATE()
    FROM inventario.dbo.inv_talento_personal destino
    JOIN deleted eliminado ON eliminado.empleado_id = destino.id
    WHERE NOT EXISTS (SELECT 1 FROM inserted origen WHERE origen.empleado_id = eliminado.empleado_id);
END;
GO
