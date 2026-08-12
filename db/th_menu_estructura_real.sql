/* db/th_menu_estructura_real.sql
   El árbol MOIS de TH (id_modulo=11) representaba "Auditoría y Control" y
   "Prototipos" como una sola pantalla cada uno, pero el sidebar REAL de TH
   (apps/talento_humano/shared/menu.php) tiene:
     - Grupo "Auditoría y Control": 2 links reales -> Logs de Actividad
       (auditoria/logs) y Reportes de Auditoría (auditoria/reportes), ambos
       bajo el mismo gate Auth::can('auditoria'). Antes solo existía el nodo
       de "logs" -- "Reportes de Auditoría" no era seleccionable en
       Estructura del Menú ni en Roles y Permisos.
     - Grupo "Prototipos": 4 links reales -> Asistencia, Vacaciones,
       Evaluación y Desempeño, Capacitación (Auth::can('prototipos') es un
       solo gate que envuelve las 4). Antes era un único nodo combinado sin
       URL propia.

   Convierte opcion=12 en cabecera pura (items reales debajo, igual que ya
   era opcion=14) y agrega los items faltantes en ambas. Preserva el acceso
   ya otorgado: cada rol que tenía nivel_crud en el nodo plano original lo
   conserva en TODOS los items nuevos (nadie pierde acceso a lo que ya
   podía ver; certain of a role que veía "Auditoría y Control" ahora ve
   ambos sub-informes, coherente con que antes era una sola pantalla que
   agrupaba ambos). Idempotente. */
USE [PORTAL_APM];
GO
SET ANSI_NULLS ON;
SET QUOTED_IDENTIFIER ON;
GO

-- opcion=12 pasa a ser cabecera de área (sin URL propia), igual que opcion=14
UPDATE dbo.CORE_Menu_Nodos SET url_ruta = NULL
WHERE id_modulo = 11 AND opcion = 12 AND items = 0 AND subitems = 0;
GO

-- Nodos de menú nuevos (items reales bajo opcion=12 y opcion=14)
INSERT dbo.CORE_Menu_Nodos (id_modulo, opcion, items, subitems, descripcion, url_ruta, icono, orden, requiere_mfa, target_spa, estado)
SELECT x.id_modulo, x.opcion, x.items, 0, x.descripcion, x.url_ruta, x.icono, x.orden, x.requiere_mfa, 0, 1
FROM (VALUES
    (11, 12, 1, 'Logs de Actividad',          'apps/talento_humano/auditoria/logs',                    'fa-clipboard-list', 1, 1),
    (11, 12, 2, 'Reportes de Auditoría',      'apps/talento_humano/auditoria/reportes',                'fa-file-contract',  2, 1),
    (11, 14, 1, 'Asistencia y Turnos',        'apps/talento_humano/talento-humano/asistencia',         'fa-calendar-check', 1, 0),
    (11, 14, 2, 'Vacaciones y Ausencias',     'apps/talento_humano/talento-humano/vacaciones',         'fa-umbrella-beach', 2, 0),
    (11, 14, 3, 'Evaluación y Desempeño',     'apps/talento_humano/talento-humano/desempeno',          'fa-chart-line',     3, 0),
    (11, 14, 4, 'Capacitación y Desarrollo',  'apps/talento_humano/talento-humano/capacitacion',       'fa-graduation-cap', 4, 0)
) AS x(id_modulo, opcion, items, descripcion, url_ruta, icono, orden, requiere_mfa)
WHERE NOT EXISTS (
    SELECT 1 FROM dbo.CORE_Menu_Nodos n
    WHERE n.id_modulo=x.id_modulo AND n.opcion=x.opcion AND n.items=x.items AND n.subitems=0
);
GO

-- Preservar acceso: el nivel que cada rol ya tenía en el nodo plano (opcion,0,0)
-- se copia a cada item nuevo de esa misma opción.
INSERT dbo.CORE_Permisos_Nodo (id_rol, id_modulo, opcion, items, subitems, nivel_crud, acceso, estado, fecha_asignacion)
SELECT p.id_rol, 11, p.opcion, x.items, 0, p.nivel_crud, 1, 1, SYSDATETIME()
FROM dbo.CORE_Permisos_Nodo p
CROSS JOIN (VALUES (12,1),(12,2),(14,1),(14,2),(14,3),(14,4)) AS x(opcion, items)
WHERE p.id_modulo = 11 AND p.opcion = x.opcion AND p.items = 0 AND p.subitems = 0
  AND NOT EXISTS (
      SELECT 1 FROM dbo.CORE_Permisos_Nodo q
      WHERE q.id_rol=p.id_rol AND q.id_modulo=11 AND q.opcion=x.opcion AND q.items=x.items AND q.subitems=0
  );
GO
