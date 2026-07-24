/* ============================================================
   Paneles (hubs) por módulo en el menú lateral
   - Módulo 11 (Talento Humano):   "Panel de Talento Humano" → /th
   - Módulo 12 (Control de Bienes):"Panel de Bienes"        → /inventario/panel
   (El módulo 13 Portuaria ya tiene su Panel Portuario → /portuaria)

   Cada panel se agrega como PRIMER ítem del área 1 (orden 0) y hereda
   permisos de todos los roles que ya tienen acceso a esa área.
   Idempotente: se puede re-ejecutar.
   ============================================================ */
USE PORTAL_APM;
GO
SET QUOTED_IDENTIFIER ON;
SET ANSI_NULLS ON;
SET NOCOUNT ON;

/* 1. Nodo Panel TH (11,1,4,0) */
IF NOT EXISTS (SELECT 1 FROM CORE_Menu_Nodos WHERE id_modulo=11 AND opcion=1 AND items=4 AND subitems=0)
    INSERT INTO CORE_Menu_Nodos (id_modulo, opcion, items, subitems, descripcion, url_ruta, icono, orden, requiere_mfa, target_spa, estado)
    VALUES (11,1,4,0, N'Panel de Talento Humano', N'/th', N'fa-gauge-high', 0, 0, 1, 1);
ELSE
    UPDATE CORE_Menu_Nodos SET descripcion=N'Panel de Talento Humano', url_ruta=N'/th',
           icono=N'fa-gauge-high', orden=0, target_spa=1, estado=1
    WHERE id_modulo=11 AND opcion=1 AND items=4 AND subitems=0;

/* 2. Nodo Panel Bienes (12,1,4,0) */
IF NOT EXISTS (SELECT 1 FROM CORE_Menu_Nodos WHERE id_modulo=12 AND opcion=1 AND items=4 AND subitems=0)
    INSERT INTO CORE_Menu_Nodos (id_modulo, opcion, items, subitems, descripcion, url_ruta, icono, orden, requiere_mfa, target_spa, estado)
    VALUES (12,1,4,0, N'Panel de Bienes', N'/inventario/panel', N'fa-gauge-high', 0, 0, 1, 1);
ELSE
    UPDATE CORE_Menu_Nodos SET descripcion=N'Panel de Bienes', url_ruta=N'/inventario/panel',
           icono=N'fa-gauge-high', orden=0, target_spa=1, estado=1
    WHERE id_modulo=12 AND opcion=1 AND items=4 AND subitems=0;

/* 3. Permisos: cada rol con acceso al área (X,1,0,0) recibe el panel de ese módulo */
INSERT INTO CORE_Permisos_Nodo (id_rol, id_modulo, opcion, items, subitems, nivel_crud, acceso, estado, fecha_asignacion, asignado_por)
SELECT pn.id_rol, pn.id_modulo, 1, 4, 0, pn.nivel_crud, 1, 1, GETDATE(), 1
FROM CORE_Permisos_Nodo pn
WHERE pn.id_modulo IN (11,12) AND pn.opcion = 1 AND pn.items = 0 AND pn.subitems = 0
  AND pn.acceso = 1 AND pn.estado = 1
  AND NOT EXISTS (
    SELECT 1 FROM CORE_Permisos_Nodo x
    WHERE x.id_rol = pn.id_rol AND x.id_modulo = pn.id_modulo
      AND x.opcion = 1 AND x.items = 4 AND x.subitems = 0
  );

PRINT 'Paneles de módulo integrados al menú.';
SELECT 'Nodos panel' AS info, COUNT(*) AS n FROM CORE_Menu_Nodos WHERE opcion=1 AND items=4 AND id_modulo IN (11,12)
UNION ALL
SELECT 'Permisos panel', COUNT(*) FROM CORE_Permisos_Nodo WHERE opcion=1 AND items=4 AND id_modulo IN (11,12);
GO
