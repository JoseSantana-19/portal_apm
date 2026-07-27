/* ============================================================================
   Migración: PORTAL_APM — identidad de usuario en vivo desde Talento_Humano
   (sin duplicar nombre_completo/cedula a mano).

   Contexto: CORE_Usuarios tenía su propia copia de nombre_completo/cedula,
   separada de Talento_Humano.dbo.th_empleados — si un empleado cambiaba de
   nombre en TH, la cuenta del portal quedaba desactualizada. Esta migración:

   1. Agrega CORE_Usuarios.id_empleado_th (nullable — no todas las cuentas
      son empleados TH, ej. el superadmin de TI).
   2. Crea vw_Usuarios_Identidad: trae nombre_completo/cedula EN VIVO desde
      Talento_Humano.dbo.th_empleados cuando id_empleado_th está seteado;
      si no, usa los campos locales de CORE_Usuarios como respaldo.

   Requiere que Talento_Humano y PORTAL_APM vivan en la misma instancia SQL
   Server (ya es el caso — mismo config/connections.php).

   Idempotente. Ejecutar sobre PORTAL_APM.
   ============================================================================ */

USE PORTAL_APM;
GO

IF NOT EXISTS (
    SELECT 1 FROM sys.columns
    WHERE object_id = OBJECT_ID('CORE_Usuarios') AND name = 'id_empleado_th'
)
    ALTER TABLE CORE_Usuarios ADD id_empleado_th INT NULL;
GO

IF OBJECT_ID('vw_Usuarios_Identidad', 'V') IS NOT NULL
    DROP VIEW vw_Usuarios_Identidad;
GO

CREATE VIEW vw_Usuarios_Identidad AS
SELECT
    u.id_usuario,
    u.nombre_usuario,
    u.correo,
    u.id_departamento,
    u.nivel_jerarquia,
    u.estado,
    u.id_empleado_th,
    COALESCE(NULLIF(LTRIM(RTRIM(e.nombres + ' ' + e.apellidos)), ''), u.nombre_completo) AS nombre_completo,
    COALESCE(e.cedula, u.cedula) AS cedula,
    COALESCE(e.ruta_foto, u.foto) AS foto
FROM CORE_Usuarios u
LEFT JOIN Talento_Humano.dbo.th_empleados e ON e.empleado_id = u.id_empleado_th;
GO

PRINT 'vw_Usuarios_Identidad creada. CORE_Usuarios.id_empleado_th agregada.';
GO
