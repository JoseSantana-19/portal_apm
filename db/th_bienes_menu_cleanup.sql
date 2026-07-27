/* ============================================================================
   Migración: PORTAL_APM — limpieza de menú de Talento Humano (módulo 11) y
   Bienes (módulo 12).

   Contexto: esos dos módulos se reescribieron en su momento nativamente
   dentro de portal_apm (modules/Talento_Humano, modules/Inventario,
   modules/Control_Bienes) como prueba inicial — th_integration.sql e
   inv_menu_integration.sql registraron en su momento un submenú (items 1..4)
   apuntando a esas rutas nativas (/th/*, /inventario/*, /bienes/*).

   Esa reescritura nativa se dio de baja: la integración real de ambos
   módulos es la app embebida completa (apps/talento_humano,
   apps/control_bienes), ya registrada por db/apps_origen_integration.sql
   como el nodo "Sistema Completo (Origen)" (item 5) de cada módulo.

   Este script borra los nodos de menú y permisos de los items 1..4 de los
   módulos 11 y 12 (las rutas nativas que ya no existen), y CONSERVA/RESTAURA
   dos cosas por módulo: el header de nivel 1 (opcion=0,items=0,subitems=0 —
   el nombre del módulo, "Talento Humano"/"Control de Bienes", el que hace
   que el sidebar muestre el nombre real y no el fallback "Módulo 11/12") y
   el item 5 "Sistema Completo (Origen)".

   Idempotente y auto-reparador — se puede correr más de una vez, y si ya
   corriste una versión anterior de este mismo script que había borrado el
   header por error, esta versión lo vuelve a insertar.

   Ejecutar sobre la BD `PORTAL_APM` (instancia configurada en
   config/connections.php), DESPUÉS de haber corrido
   db/apps_origen_integration.sql al menos una vez.
   ============================================================================ */

USE PORTAL_APM;
GO
SET NOCOUNT ON;

-- 1. Borrar todo lo que NO sea el header (opcion=0) ni el item 5 (Origen).
DELETE FROM CORE_Permisos_Nodo
WHERE id_modulo IN (11, 12)
  AND NOT (opcion = 0 AND items = 0 AND subitems = 0)
  AND NOT (opcion = 1 AND items = 5 AND subitems = 0);

DELETE FROM CORE_Menu_Nodos
WHERE id_modulo IN (11, 12)
  AND NOT (opcion = 0 AND items = 0 AND subitems = 0)
  AND NOT (opcion = 1 AND items = 5 AND subitems = 0);

-- 2. Restaurar el header de nivel 1 si no existe (por ejemplo, si ya
--    corriste una versión anterior de este script que lo había borrado).
IF NOT EXISTS (SELECT 1 FROM CORE_Menu_Nodos WHERE id_modulo = 11 AND opcion = 0 AND items = 0 AND subitems = 0)
    INSERT INTO CORE_Menu_Nodos (id_modulo, opcion, items, subitems, descripcion, url_ruta, icono, orden, requiere_mfa, target_spa, estado)
    VALUES (11, 0, 0, 0, N'Talento Humano', NULL, N'fa-users', 11, 0, 1, 1);

IF NOT EXISTS (SELECT 1 FROM CORE_Menu_Nodos WHERE id_modulo = 12 AND opcion = 0 AND items = 0 AND subitems = 0)
    INSERT INTO CORE_Menu_Nodos (id_modulo, opcion, items, subitems, descripcion, url_ruta, icono, orden, requiere_mfa, target_spa, estado)
    VALUES (12, 0, 0, 0, N'Control de Bienes', NULL, N'fa-boxes-stacked', 12, 0, 1, 1);

-- 3. Re-otorgar permisos del header a todo rol que ya pueda ver el item 5
--    "Sistema Completo (Origen)" de ese módulo (mismo nivel_crud).
INSERT INTO CORE_Permisos_Nodo (id_rol, id_modulo, opcion, items, subitems, nivel_crud, acceso, estado, fecha_asignacion, asignado_por)
SELECT pn.id_rol, pn.id_modulo, 0, 0, 0, pn.nivel_crud, 1, 1, GETDATE(), 1
FROM CORE_Permisos_Nodo pn
WHERE pn.id_modulo IN (11, 12) AND pn.opcion = 1 AND pn.items = 5 AND pn.subitems = 0
  AND pn.acceso = 1 AND pn.estado = 1
  AND NOT EXISTS (
    SELECT 1 FROM CORE_Permisos_Nodo x
    WHERE x.id_rol = pn.id_rol AND x.id_modulo = pn.id_modulo
      AND x.opcion = 0 AND x.items = 0 AND x.subitems = 0
  );

PRINT 'Menú de Talento Humano y Bienes: header + "Sistema Completo (Origen)" únicamente.';
SELECT 'Nodos restantes módulo 11 (TH)'     AS info, COUNT(*) AS n FROM CORE_Menu_Nodos WHERE id_modulo = 11
UNION ALL
SELECT 'Nodos restantes módulo 12 (Bienes)', COUNT(*) FROM CORE_Menu_Nodos WHERE id_modulo = 12;
GO
