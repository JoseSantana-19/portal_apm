-- =============================================================================
-- Migración: PORTAL_APM — integración del módulo Talento Humano (módulo MOIS 11)
-- Idempotente. Ejecutar sobre la BD `PORTAL_APM` (instancia .\VICTUS).
--   * RBU vigente en CORE_Config
--   * Reconstruye el árbol de menú del módulo 11 apuntando a las nuevas rutas /th
--   * Re-otorga permisos a los roles que ya tenían acceso al módulo 11
-- =============================================================================
SET QUOTED_IDENTIFIER ON;
SET ANSI_NULLS ON;
GO
USE [PORTAL_APM];
GO

-- -----------------------------------------------------------------------------
-- 1. RBU vigente (lo lee ThEmpleadoModel::obtenerRbuVigente)
-- -----------------------------------------------------------------------------
IF NOT EXISTS (SELECT 1 FROM CORE_Config WHERE modulo = 'TH' AND clave = 'RBU_VIGENTE')
    INSERT INTO CORE_Config (modulo, clave, valor, tipo, descripcion)
    VALUES ('TH', 'RBU_VIGENTE', '460.00', 'string', 'Remuneración Básica Unificada vigente');
GO

-- -----------------------------------------------------------------------------
-- 2. Capturar los roles que hoy tienen acceso al módulo 11 y su nivel_crud máx.
--    (Para no perder el acceso al reconstruir el árbol.)
-- -----------------------------------------------------------------------------
IF OBJECT_ID('tempdb..#th_roles') IS NOT NULL DROP TABLE #th_roles;
SELECT id_rol, MAX(nivel_crud) AS nivel_crud
INTO #th_roles
FROM CORE_Permisos_Nodo
WHERE id_modulo = 11
GROUP BY id_rol;

-- Si el módulo estaba vacío, asegurar al menos al rol ADMIN (id_rol = 1).
IF NOT EXISTS (SELECT 1 FROM #th_roles)
    INSERT INTO #th_roles (id_rol, nivel_crud) VALUES (1, 4);
GO

-- -----------------------------------------------------------------------------
-- 3. Limpiar el árbol y los permisos del módulo 11.
-- -----------------------------------------------------------------------------
DELETE FROM CORE_Permisos_Nodo WHERE id_modulo = 11;
DELETE FROM CORE_Menu_Nodos    WHERE id_modulo = 11;
GO

-- -----------------------------------------------------------------------------
-- 4. Nueva estructura MOIS del módulo 11.
-- -----------------------------------------------------------------------------
INSERT INTO CORE_Menu_Nodos (id_modulo,opcion,items,subitems,descripcion,url_ruta,icono,orden,requiere_mfa,target_spa,estado) VALUES
(11,0,0,0,N'Talento Humano',              NULL,                    'fa-users',           11,0,1,1),
(11,1,0,0,N'Personal',                    NULL,                    'fa-id-card',          1,0,1,1),
(11,1,1,0,N'Directorio de Personal',      '/th/directorio',        'fa-address-book',     1,0,1,1),
(11,1,2,0,N'Acción de Personal',          '/th/accion-personal',   'fa-file-signature',   2,0,1,1),
(11,1,3,0,N'Historial / Reportes',        '/th/reporte',           'fa-timeline',         3,0,1,1),
(11,2,0,0,N'Gestión Operativa',           NULL,                    'fa-briefcase',        2,0,1,1),
(11,2,1,0,N'Asistencia',                  '/th/asistencia',        'fa-user-clock',       1,0,1,1),
(11,2,2,0,N'Vacaciones',                  '/th/vacaciones',        'fa-umbrella-beach',   2,0,1,1),
(11,2,3,0,N'Desempeño',                   '/th/desempeno',         'fa-chart-line',       3,0,1,1),
(11,2,4,0,N'Capacitación',                '/th/capacitacion',      'fa-chalkboard-user',  4,0,1,1);
GO

-- -----------------------------------------------------------------------------
-- 5. Re-otorgar permisos a cada rol capturado sobre todos los nodos nuevos.
-- -----------------------------------------------------------------------------
INSERT INTO CORE_Permisos_Nodo (id_rol,id_modulo,opcion,items,subitems,nivel_crud,acceso,estado,asignado_por)
SELECT r.id_rol, n.id_modulo, n.opcion, n.items, n.subitems, r.nivel_crud, 1, 1, 1
FROM #th_roles r
CROSS JOIN CORE_Menu_Nodos n
WHERE n.id_modulo = 11;

DROP TABLE #th_roles;
GO
