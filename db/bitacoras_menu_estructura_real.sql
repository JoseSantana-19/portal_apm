/* db/bitacoras_menu_estructura_real.sql
   El árbol MOIS de Bitácoras (id_modulo=13) representaba "Registros Base"
   y "Cámaras CCTV" como una sola pantalla cada uno, pero el sidebar REAL
   (apps/bitacoras/modules/Portuaria/views/layouts/bit_sidebar.php) tiene:
     - Grupo "Registros Base": además del link a Registros Base (catalogos)
       en sí, 6 sub-links reales -> Maestro Personas, Maestro Empresas,
       Maestro Destinos, Maestro Motivos, Talento Humano, Niveles de
       importancia (todos bajo el mismo gate apm_can_gestionar_maestros_acceso()).
     - Grupo "CCTV Cámaras": además de la Bitácora de Cámaras (camaras, ya
       cubierta por el nodo raíz), 2 sub-links reales -> Maestro de Cámaras
       (camaras/inventario) y Motivos CCTV (camaras/motivos).
   Ninguno de los 8 sub-links era seleccionable en Estructura del Menú ni
   en Roles y Permisos -- por eso el árbol de permisos no coincidía con el
   menú lateral real del módulo.

   Agrega los items faltantes bajo opcion=5 y opcion=7 (los nodos raíz
   conservan su URL propia -- "Registros Base" y "Bitácora de Cámaras"
   siguen siendo pantallas navegables en sí mismas, no solo cabeceras).
   Preserva el acceso ya otorgado: cada rol que tenía nivel_crud en el nodo
   plano original lo conserva en todos los items nuevos. Idempotente. */
USE [PORTAL_APM];
GO
SET ANSI_NULLS ON;
SET QUOTED_IDENTIFIER ON;
GO

INSERT dbo.CORE_Menu_Nodos (id_modulo, opcion, items, subitems, descripcion, url_ruta, icono, orden, requiere_mfa, target_spa, estado)
SELECT x.id_modulo, x.opcion, x.items, 0, x.descripcion, x.url_ruta, 'fa-circle', x.items, 0, 0, 1
FROM (VALUES
    (13, 5, 1, 'Maestro Personas',           'apps/bitacoras/catalogos/personas'),
    (13, 5, 2, 'Maestro Empresas',           'apps/bitacoras/catalogos/empresas'),
    (13, 5, 3, 'Maestro Destinos',           'apps/bitacoras/catalogos/destinos'),
    (13, 5, 4, 'Maestro Motivos',            'apps/bitacoras/catalogos/motivos'),
    (13, 5, 5, 'Talento Humano (catálogo)',  'apps/bitacoras/catalogos/funcionarios'),
    (13, 5, 6, 'Niveles de importancia',     'apps/bitacoras/catalogos/niveles-incidente'),
    (13, 7, 1, 'Maestro de Cámaras',         'apps/bitacoras/camaras/inventario'),
    (13, 7, 2, 'Motivos CCTV',               'apps/bitacoras/camaras/motivos')
) AS x(id_modulo, opcion, items, descripcion, url_ruta)
WHERE NOT EXISTS (
    SELECT 1 FROM dbo.CORE_Menu_Nodos n
    WHERE n.id_modulo=x.id_modulo AND n.opcion=x.opcion AND n.items=x.items AND n.subitems=0
);
GO

UPDATE dbo.CORE_Menu_Nodos SET icono = 'fa-address-book' WHERE id_modulo=13 AND opcion=5 AND items IN (1,2) AND subitems=0;
UPDATE dbo.CORE_Menu_Nodos SET icono = 'fa-signs-post'    WHERE id_modulo=13 AND opcion=5 AND items IN (3,4) AND subitems=0;
UPDATE dbo.CORE_Menu_Nodos SET icono = 'fa-user-tie'      WHERE id_modulo=13 AND opcion=5 AND items=5 AND subitems=0;
UPDATE dbo.CORE_Menu_Nodos SET icono = 'fa-triangle-exclamation' WHERE id_modulo=13 AND opcion=5 AND items=6 AND subitems=0;
UPDATE dbo.CORE_Menu_Nodos SET icono = 'fa-hdd'           WHERE id_modulo=13 AND opcion=7 AND items=1 AND subitems=0;
UPDATE dbo.CORE_Menu_Nodos SET icono = 'fa-triangle-exclamation' WHERE id_modulo=13 AND opcion=7 AND items=2 AND subitems=0;
GO

-- Preservar acceso: el nivel que cada rol ya tenía en el nodo plano (opcion,0,0)
-- se copia a cada item nuevo de esa misma opción.
INSERT dbo.CORE_Permisos_Nodo (id_rol, id_modulo, opcion, items, subitems, nivel_crud, acceso, estado, fecha_asignacion)
SELECT p.id_rol, 13, p.opcion, x.items, 0, p.nivel_crud, 1, 1, SYSDATETIME()
FROM dbo.CORE_Permisos_Nodo p
CROSS JOIN (VALUES (5,1),(5,2),(5,3),(5,4),(5,5),(5,6),(7,1),(7,2)) AS x(opcion, items)
WHERE p.id_modulo = 13 AND p.opcion = x.opcion AND p.items = 0 AND p.subitems = 0
  AND NOT EXISTS (
      SELECT 1 FROM dbo.CORE_Permisos_Nodo q
      WHERE q.id_rol=p.id_rol AND q.id_modulo=13 AND q.opcion=x.opcion AND q.items=x.items AND q.subitems=0
  );
GO
