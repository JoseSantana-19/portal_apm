/* db/bitacoras_menu_items_raiz_reales.sql
   Menu.php auto-registra cada área plana como un "item[0]" que se
   auto-referencia (mismo label que el área) para que no desaparezca del
   sidebar cuando no tiene hijos. Pero "Registros Base" y "Cámaras CCTV"
   AHORA tienen hijos reales (db/bitacoras_menu_estructura_real.sql) -- el
   auto-registro quedó como una fila duplicada con el mismo nombre que el
   propio encabezado del área.

   Se agrega un ítem real con la MISMA url pero su nombre funcional
   correcto -- Menu.php (fix de esta sesión) detecta la url duplicada y
   descarta el auto-registro genérico, dejando solo este ítem bien
   nombrado. "Registros Base" (catalogos) es realmente la vista combinada
   de los 6 catálogos -> "Ver todo (catálogos)". "Cámaras CCTV" (camaras)
   es en realidad la Bitácora de Cámaras, una pantalla propia distinta del
   grupo que la contiene -> "Bitácora de Cámaras".

   Hereda el mismo nivel_crud que cada rol ya tiene en el nodo de área
   (opcion,0,0) -- mismo acceso, sin apto de renombre. Idempotente. */
USE [PORTAL_APM];
GO
SET ANSI_NULLS ON;
SET QUOTED_IDENTIFIER ON;
GO

INSERT dbo.CORE_Menu_Nodos (id_modulo, opcion, items, subitems, descripcion, url_ruta, icono, orden, requiere_mfa, target_spa, estado)
SELECT x.id_modulo, x.opcion, x.items, 0, x.descripcion, x.url_ruta, x.icono, x.orden, 0, 0, 1
FROM (VALUES
    (13, 5, 7, 'Ver todo (catálogos)',   'apps/bitacoras/catalogos', 'fa-list-check', 0),
    (13, 7, 3, 'Bitácora de Cámaras',    'apps/bitacoras/camaras',   'fa-camera-retro', 0)
) AS x(id_modulo, opcion, items, descripcion, url_ruta, icono, orden)
WHERE NOT EXISTS (
    SELECT 1 FROM dbo.CORE_Menu_Nodos n
    WHERE n.id_modulo=x.id_modulo AND n.opcion=x.opcion AND n.items=x.items AND n.subitems=0
);
GO

INSERT dbo.CORE_Permisos_Nodo (id_rol, id_modulo, opcion, items, subitems, nivel_crud, acceso, estado, fecha_asignacion)
SELECT p.id_rol, 13, p.opcion, x.items, 0, p.nivel_crud, 1, 1, SYSDATETIME()
FROM dbo.CORE_Permisos_Nodo p
CROSS JOIN (VALUES (5,7),(7,3)) AS x(opcion, items)
WHERE p.id_modulo = 13 AND p.opcion = x.opcion AND p.items = 0 AND p.subitems = 0
  AND NOT EXISTS (
      SELECT 1 FROM dbo.CORE_Permisos_Nodo q
      WHERE q.id_rol=p.id_rol AND q.id_modulo=13 AND q.opcion=x.opcion AND q.items=x.items AND q.subitems=0
  );
GO
