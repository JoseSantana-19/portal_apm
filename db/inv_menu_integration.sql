/* ============================================================
   Integración del menú: módulo Inventario + nodos admin faltantes
   - Agrega el módulo 12 "Control de Bienes" (3 áreas, 8 ítems)
   - Agrega nodos admin /admin/menu y /admin/auditoria al módulo 2
   - Otorga permisos al rol ADMIN (id_rol=1)
   - Deja el sidebar 100% controlado por CORE_Menu_Nodos + permisos
   Idempotente: se puede re-ejecutar.
   ============================================================ */
USE PORTAL_APM;
GO
SET QUOTED_IDENTIFIER ON;
SET ANSI_NULLS ON;
SET NOCOUNT ON;

/* 1. Deshabilitar nodo basura de prueba (no se elimina por FK de permisos) */
UPDATE CORE_Menu_Nodos SET estado = 0
WHERE id_modulo = 1 AND opcion = 1 AND items = 1 AND subitems = 1
  AND (url_ruta = '/MODLUO/GGGG' OR descripcion LIKE 'NUEVO FORMULARIO%');

/* 2. Limpiar módulo 12 (permisos primero por FK lógica), para re-ejecución limpia */
DELETE FROM CORE_Permisos_Nodo WHERE id_modulo = 12;
DELETE FROM CORE_Menu_Nodos    WHERE id_modulo = 12;

/* 3. Insertar nodos del módulo 12 — Control de Bienes (Inventario) */
INSERT INTO CORE_Menu_Nodos (id_modulo, opcion, items, subitems, descripcion, url_ruta, icono, orden, requiere_mfa, target_spa, estado) VALUES
(12,0,0,0, N'Control de Bienes',      NULL,                       N'fa-boxes-stacked',          12, 0, 1, 1),
(12,1,0,0, N'Inventario',             NULL,                       N'fa-warehouse',               1, 0, 1, 1),
(12,1,1,0, N'Inventario General',     N'/inventario',             N'fa-boxes-stacked',           1, 0, 1, 1),
(12,1,2,0, N'Catálogo',               N'/inventario/catalogo',    N'fa-grip',                    2, 0, 1, 1),
(12,1,3,0, N'Ítems del Sistema',      N'/inventario/items',       N'fa-cubes',                   3, 0, 1, 1),
(12,2,0,0, N'Bodega',                 NULL,                       N'fa-truck-ramp-box',          2, 0, 1, 1),
(12,2,1,0, N'Ingresos',               N'/inventario/ingresos',    N'fa-arrow-right-to-bracket',  1, 0, 1, 1),
(12,2,2,0, N'Egresos',                N'/inventario/egresos',     N'fa-arrow-right-from-bracket',2, 0, 1, 1),
(12,3,0,0, N'Configuración',          NULL,                       N'fa-sliders',                 3, 0, 1, 1),
(12,3,1,0, N'Maestros',               N'/inventario/maestros',    N'fa-database',                1, 0, 1, 1),
(12,3,2,0, N'Períodos e IVA',         N'/inventario/periodos',    N'fa-calendar-days',           2, 0, 1, 1),
(12,3,3,0, N'Secuenciales',           N'/inventario/secuenciales',N'fa-list-ol',                 3, 0, 1, 1);

/* 4. Insertar nodos admin faltantes en módulo 2, área 2 (Administrador TICS) */
IF NOT EXISTS (SELECT 1 FROM CORE_Menu_Nodos WHERE id_modulo=2 AND opcion=2 AND items=3 AND subitems=0)
    INSERT INTO CORE_Menu_Nodos (id_modulo, opcion, items, subitems, descripcion, url_ruta, icono, orden, requiere_mfa, target_spa, estado)
    VALUES (2,2,3,0, N'Estructura del Menú', N'/admin/menu', N'fa-sitemap', 3, 0, 1, 1);

IF NOT EXISTS (SELECT 1 FROM CORE_Menu_Nodos WHERE id_modulo=2 AND opcion=2 AND items=4 AND subitems=0)
    INSERT INTO CORE_Menu_Nodos (id_modulo, opcion, items, subitems, descripcion, url_ruta, icono, orden, requiere_mfa, target_spa, estado)
    VALUES (2,2,4,0, N'Auditoría del Sistema', N'/admin/auditoria', N'fa-diagram-project', 4, 0, 1, 1);

/* 5. Otorgar permisos al rol ADMIN (id_rol=1) en TODOS los nodos nuevos
      (módulo 12 completo + los 2 nodos admin nuevos). nivel_crud=4 (Total). */
INSERT INTO CORE_Permisos_Nodo (id_rol, id_modulo, opcion, items, subitems, nivel_crud, acceso, estado, fecha_asignacion, asignado_por)
SELECT 1, mn.id_modulo, mn.opcion, mn.items, mn.subitems, 4, 1, 1, GETDATE(), 1
FROM CORE_Menu_Nodos mn
WHERE (mn.id_modulo = 12
       OR (mn.id_modulo = 2 AND mn.opcion = 2 AND mn.items IN (3,4) AND mn.subitems = 0))
  AND NOT EXISTS (
    SELECT 1 FROM CORE_Permisos_Nodo pn
    WHERE pn.id_rol = 1 AND pn.id_modulo = mn.id_modulo AND pn.opcion = mn.opcion
      AND pn.items = mn.items AND pn.subitems = mn.subitems
);

PRINT 'Migración de menú completada.';
SELECT 'Nodos modulo 12' AS info, COUNT(*) AS n FROM CORE_Menu_Nodos WHERE id_modulo=12
UNION ALL SELECT 'Permisos ADMIN modulo 12', COUNT(*) FROM CORE_Permisos_Nodo WHERE id_rol=1 AND id_modulo=12
UNION ALL SELECT 'Nodos admin nuevos (2,2,3/4,0)', COUNT(*) FROM CORE_Menu_Nodos WHERE id_modulo=2 AND opcion=2 AND items IN (3,4) AND subitems=0;
GO
