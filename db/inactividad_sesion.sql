-- Sistema centralizado de tiempo de inactividad: global -> por modulo -> por
-- usuario (el mas especifico gana). Una sola fuente de verdad en PORTAL_APM
-- que Portal nativo, Talento Humano, Control de Bienes y Bitacoras consultan
-- (las 3 apps externas via cross-DB, mismo patron ya usado en el resto del
-- sistema para cedula/puestos/departamentos).
USE PORTAL_APM;
GO

-- 1) Overrides por usuario individual (NULL = hereda modulo/global)
IF NOT EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('dbo.CORE_Usuarios') AND name = 'inactividad_segundos_override')
    ALTER TABLE dbo.CORE_Usuarios ADD inactividad_segundos_override INT NULL;
GO
IF NOT EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('dbo.CORE_Usuarios') AND name = 'inactividad_aviso_segundos_override')
    ALTER TABLE dbo.CORE_Usuarios ADD inactividad_aviso_segundos_override INT NULL;
GO

-- 2) Default global (modulo='CORE'). Los overrides por modulo se insertan
-- solo cuando el admin fija uno especifico (su ausencia = hereda el global).
IF NOT EXISTS (SELECT 1 FROM CORE_Config WHERE modulo='CORE' AND clave='INACTIVIDAD_SEGUNDOS')
    INSERT INTO CORE_Config (modulo, clave, valor, tipo, descripcion, fecha_mod, estado)
    VALUES ('CORE', 'INACTIVIDAD_SEGUNDOS', '1800', 'int', 'Segundos de inactividad antes de cerrar sesion (valor global por defecto)', GETDATE(), 1);
GO
IF NOT EXISTS (SELECT 1 FROM CORE_Config WHERE modulo='CORE' AND clave='INACTIVIDAD_AVISO_SEGUNDOS')
    INSERT INTO CORE_Config (modulo, clave, valor, tipo, descripcion, fecha_mod, estado)
    VALUES ('CORE', 'INACTIVIDAD_AVISO_SEGUNDOS', '60', 'int', 'Segundos antes de expirar en que se muestra el aviso de cierre de sesion', GETDATE(), 1);
GO

-- 3) Funciones de resolucion — usuario > modulo > global > fallback duro.
-- @modulo: 'CENTRAL' | 'TALENTO_HUMANO' | 'CONTROL_BIENES' | 'BITACORAS'
CREATE OR ALTER FUNCTION dbo.fn_InactividadSegundos(@id_usuario INT, @modulo NVARCHAR(30))
RETURNS INT
AS
BEGIN
    DECLARE @valor INT;

    SELECT @valor = inactividad_segundos_override FROM CORE_Usuarios WHERE id_usuario = @id_usuario;
    IF @valor IS NOT NULL RETURN @valor;

    SELECT @valor = TRY_CAST(valor AS INT) FROM CORE_Config WHERE modulo = @modulo AND clave = 'INACTIVIDAD_SEGUNDOS' AND estado = 1;
    IF @valor IS NOT NULL RETURN @valor;

    SELECT @valor = TRY_CAST(valor AS INT) FROM CORE_Config WHERE modulo = 'CORE' AND clave = 'INACTIVIDAD_SEGUNDOS' AND estado = 1;
    IF @valor IS NOT NULL RETURN @valor;

    RETURN 1800;
END;
GO

CREATE OR ALTER FUNCTION dbo.fn_InactividadAvisoSegundos(@id_usuario INT, @modulo NVARCHAR(30))
RETURNS INT
AS
BEGIN
    DECLARE @valor INT;

    SELECT @valor = inactividad_aviso_segundos_override FROM CORE_Usuarios WHERE id_usuario = @id_usuario;
    IF @valor IS NOT NULL RETURN @valor;

    SELECT @valor = TRY_CAST(valor AS INT) FROM CORE_Config WHERE modulo = @modulo AND clave = 'INACTIVIDAD_AVISO_SEGUNDOS' AND estado = 1;
    IF @valor IS NOT NULL RETURN @valor;

    SELECT @valor = TRY_CAST(valor AS INT) FROM CORE_Config WHERE modulo = 'CORE' AND clave = 'INACTIVIDAD_AVISO_SEGUNDOS' AND estado = 1;
    IF @valor IS NOT NULL RETURN @valor;

    RETURN 60;
END;
GO

-- 4) Item de menu (solo Administrador general = nivel_jerarquia 4) + permiso
-- para los roles que ya tienen ese nivel. items=7: el 2/4/6 estan libres/usados
-- por otras cosas, se usa el siguiente correlativo real segun CORE_Menu_Nodos.
IF NOT EXISTS (SELECT 1 FROM CORE_Menu_Nodos WHERE id_modulo=1 AND opcion=2 AND items=7 AND subitems=0)
    INSERT INTO CORE_Menu_Nodos (id_modulo, opcion, items, subitems, descripcion, url_ruta, icono, orden, target_spa, estado)
    VALUES (1, 2, 7, 0, 'Inactividad de Sesión', '/admin/inactividad', 'fa-hourglass-half', 7, 1, 1);
GO

INSERT INTO CORE_Permisos_Nodo (id_rol, id_modulo, opcion, items, subitems, nivel_crud, estado)
SELECT r.id_rol, 1, 2, 7, 0, 4, 1
FROM CORE_Roles r
WHERE r.nivel_jerarquia = 4 AND r.estado = 1
AND NOT EXISTS (
    SELECT 1 FROM CORE_Permisos_Nodo p
    WHERE p.id_rol = r.id_rol AND p.id_modulo=1 AND p.opcion=2 AND p.items=7 AND p.subitems=0
);
GO
