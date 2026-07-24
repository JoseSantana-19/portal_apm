/* ============================================================
   Integración del menú: módulo 13 "Bitácoras Portuarias"
   (portuaria_demoV4 integrado en portal_apm)

   - Agrega el módulo 13 con 5 áreas (Panel, Visitas, Seguridad
     Operativa, CCTV Cámaras, Catálogos) → 19 ítems.
   - Otorga permisos al rol ADMIN (id_rol = 1), nivel_crud = 4.
   - target_spa = 0: el módulo usa layout propio (Bootstrap),
     navegación con recarga completa — NO SPA del shell.
   Idempotente: se puede re-ejecutar.
   ============================================================ */
USE PORTAL_APM;
GO
SET QUOTED_IDENTIFIER ON;
SET ANSI_NULLS ON;
SET NOCOUNT ON;

/* 1. Limpiar módulo 13 para re-ejecución limpia (permisos primero) */
DELETE FROM CORE_Permisos_Nodo WHERE id_modulo = 13;
DELETE FROM CORE_Menu_Nodos    WHERE id_modulo = 13;

/* 2. Nodos del módulo 13 — Bitácoras Portuarias */
INSERT INTO CORE_Menu_Nodos (id_modulo, opcion, items, subitems, descripcion, url_ruta, icono, orden, requiere_mfa, target_spa, estado) VALUES
(13,0,0,0, N'Bitácoras Portuarias',      NULL,                              N'fa-anchor',                13, 0, 0, 1),

(13,1,0,0, N'Panel',                     NULL,                              N'fa-gauge-high',             1, 0, 0, 1),
(13,1,1,0, N'Panel Portuario',           N'/portuaria',                     N'fa-anchor',                 1, 0, 1, 1),
(13,1,2,0, N'Vista Rápida Visitas',      N'/portuaria/visitas-resumen',     N'fa-list-check',             2, 0, 1, 1),
(13,1,3,0, N'Actividad de Seguridad',    N'/portuaria/actividad',           N'fa-shield-halved',          3, 0, 1, 1),
(13,1,4,0, N'Dashboard del Módulo',      N'/portuaria/dashboard',           N'fa-gauge',                  4, 0, 0, 1),
(13,1,5,0, N'Panel Jefatura',            N'/bit_dashboard_jefe.php',        N'fa-chart-line',             5, 0, 0, 1),
(13,1,6,0, N'Dashboard Ejecutivo (Py)',  N'/dashboard-ejecutivo',           N'fa-chart-pie',              6, 0, 0, 1),

(13,2,0,0, N'Visitas',                   NULL,                              N'fa-person-walking-arrow-right', 2, 0, 0, 1),
(13,2,1,0, N'Registrar Ingreso',         N'/visitas/registrar',             N'fa-person-circle-plus',     1, 0, 0, 1),
(13,2,2,0, N'Listado de Visitas',        N'/visitas',                       N'fa-list-ul',                2, 0, 0, 1),

(13,3,0,0, N'Seguridad Operativa',       NULL,                              N'fa-shield-halved',          3, 0, 0, 1),
(13,3,1,0, N'Bitácora de Rondas',        N'/rondas',                        N'fa-clipboard-check',        1, 0, 0, 1),
(13,3,2,0, N'Reporte Supervisor',        N'/bit_reporte_diario_supervisor.php', N'fa-file-lines',         2, 0, 0, 1),
(13,3,3,0, N'Consulta de Bitácoras',     N'/bit_consulta.php',              N'fa-magnifying-glass',       3, 0, 0, 1),

(13,4,0,0, N'CCTV Cámaras',              NULL,                              N'fa-video',                  4, 0, 0, 1),
(13,4,1,0, N'Bitácora de Cámaras',       N'/camaras',                       N'fa-camera',                 1, 0, 0, 1),
(13,4,2,0, N'Maestro de Cámaras',        N'/camaras/inventario',            N'fa-server',                 2, 0, 0, 1),
(13,4,3,0, N'Motivos CCTV',              N'/camaras/motivos',               N'fa-triangle-exclamation',   3, 0, 0, 1),

(13,5,0,0, N'Catálogos',                 NULL,                              N'fa-database',               5, 0, 0, 1),
(13,5,1,0, N'Registros Base',            N'/catalogos',                     N'fa-table-list',             1, 0, 0, 1),
(13,5,2,0, N'Maestro Personas',          N'/catalogos/personas',            N'fa-id-card',                2, 0, 0, 1),
(13,5,3,0, N'Maestro Empresas',          N'/catalogos/empresas',            N'fa-building',               3, 0, 0, 1),
(13,5,4,0, N'Maestro Destinos',          N'/catalogos/destinos',            N'fa-signs-post',             4, 0, 0, 1),
(13,5,5,0, N'Maestro Motivos',           N'/catalogos/motivos',             N'fa-comment-dots',           5, 0, 0, 1),
(13,5,6,0, N'Funcionarios (DBF)',        N'/catalogos/funcionarios',        N'fa-user-tie',               6, 0, 0, 1),
(13,5,7,0, N'Niveles de Importancia',    N'/catalogos/niveles-incidente',   N'fa-exclamation',            7, 0, 0, 1),
(13,5,8,0, N'Importar Funcionarios',     N'/importar-funcionarios',         N'fa-file-import',            8, 0, 0, 1);

/* 3. Permisos para rol ADMIN (id_rol = 1) en todo el módulo 13 */
INSERT INTO CORE_Permisos_Nodo (id_rol, id_modulo, opcion, items, subitems, nivel_crud, acceso, estado, fecha_asignacion, asignado_por)
SELECT 1, mn.id_modulo, mn.opcion, mn.items, mn.subitems, 4, 1, 1, GETDATE(), 1
FROM CORE_Menu_Nodos mn
WHERE mn.id_modulo = 13
  AND NOT EXISTS (
    SELECT 1 FROM CORE_Permisos_Nodo pn
    WHERE pn.id_rol = 1 AND pn.id_modulo = mn.id_modulo AND pn.opcion = mn.opcion
      AND pn.items = mn.items AND pn.subitems = mn.subitems
  );

/* 4. Permisos para roles operativos y de dirección (idempotente)
      - SUP_ACCESO (5) y OP_GARITA (6): todo lo operativo (áreas 2 Visitas,
        3 Seguridad Operativa, 5 Catálogos) — nivel_crud 3.
      - OP_CCTV (7): áreas 3 (rondas/reportes) y 4 (CCTV) — nivel_crud 3.
      - GERENTE (13): área 1 (paneles) + listado de visitas + consulta — lectura (2).
      - AUDITOR (2): listado de visitas + consulta — lectura (1).
      Cada rol recibe además el nodo raíz (13,0,0,0) y las áreas padre
      de sus ítems, para que el árbol del sidebar renderice. */
DECLARE @perms TABLE (id_rol INT, opcion INT, items INT, nivel TINYINT);

INSERT INTO @perms (id_rol, opcion, items, nivel)
-- SUP_ACCESO y OP_GARITA: áreas 2, 3 y 5 completas
SELECT r.id_rol, mn.opcion, mn.items, 3
FROM (VALUES (5),(6)) r(id_rol)
CROSS JOIN CORE_Menu_Nodos mn
WHERE mn.id_modulo = 13 AND (mn.opcion IN (2,3,5) OR (mn.opcion = 0 AND mn.items = 0))
UNION ALL
-- OP_CCTV: áreas 3 y 4 completas
SELECT 7, mn.opcion, mn.items, 3
FROM CORE_Menu_Nodos mn
WHERE mn.id_modulo = 13 AND (mn.opcion IN (3,4) OR (mn.opcion = 0 AND mn.items = 0))
UNION ALL
-- GERENTE: paneles completos + listado visitas + consulta (con áreas padre)
SELECT 13, v.opcion, v.items, 2
FROM (VALUES (0,0),(1,0),(1,1),(1,2),(1,3),(1,4),(1,5),(1,6),(2,0),(2,2),(3,0),(3,3)) v(opcion, items)
UNION ALL
-- SUP_ACCESO, OP_GARITA, OP_CCTV: además vistas rápidas nativas del panel
SELECT r.id_rol, v.opcion, v.items, 2
FROM (VALUES (5),(6),(7)) r(id_rol)
CROSS JOIN (VALUES (1,0),(1,1),(1,2),(1,3)) v(opcion, items)
UNION ALL
-- AUDITOR: panel + vistas rápidas + listado visitas + consulta (lectura)
SELECT 2, v.opcion, v.items, 1
FROM (VALUES (0,0),(1,0),(1,1),(1,2),(1,3),(2,0),(2,2),(3,0),(3,3)) v(opcion, items);

INSERT INTO CORE_Permisos_Nodo (id_rol, id_modulo, opcion, items, subitems, nivel_crud, acceso, estado, fecha_asignacion, asignado_por)
SELECT DISTINCT p.id_rol, 13, p.opcion, p.items, 0, p.nivel, 1, 1, GETDATE(), 1
FROM @perms p
WHERE EXISTS (SELECT 1 FROM CORE_Menu_Nodos mn
              WHERE mn.id_modulo = 13 AND mn.opcion = p.opcion AND mn.items = p.items AND mn.subitems = 0)
  AND NOT EXISTS (SELECT 1 FROM CORE_Permisos_Nodo pn
                  WHERE pn.id_rol = p.id_rol AND pn.id_modulo = 13
                    AND pn.opcion = p.opcion AND pn.items = p.items AND pn.subitems = 0);

PRINT 'Migración de menú Portuaria completada.';
SELECT 'Nodos modulo 13' AS info, COUNT(*) AS n FROM CORE_Menu_Nodos WHERE id_modulo = 13
UNION ALL
SELECT 'Permisos ADMIN modulo 13', COUNT(*) FROM CORE_Permisos_Nodo WHERE id_rol = 1 AND id_modulo = 13
UNION ALL
SELECT 'Permisos otros roles modulo 13', COUNT(*) FROM CORE_Permisos_Nodo WHERE id_rol <> 1 AND id_modulo = 13;
GO
