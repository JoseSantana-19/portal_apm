/* ============================================================================
   Migración: PORTAL_APM — panel nativo para Talento Humano y Bienes +
   quitar la duplicación "Dashboard"/"Dashboard Ejecutivo".

   Contexto: /dashboard es un redirect inteligente (DashboardController@index):
   si nivel_jerarquia>=2 muestra exactamente lo mismo que /dashboard/ejecutivo,
   si no, lo mismo que /dashboard/operativo. Tener las dos entradas en el menú
   de Central es 100% redundante para cualquier Director+ — se deja solo
   "Dashboard" (se autoadapta).

   Portuaria ya tiene su propio panel nativo (/portuaria, con KPIs en vivo)
   antes de abrir el sistema completo — TH y Bienes no lo tenían (solo el
   link "Sistema Completo (Origen)"). Se agrega /panel/talento-humano y
   /panel/bienes con el mismo rol.

   Idempotente. Ejecutar sobre PORTAL_APM.
   ============================================================================ */

USE PORTAL_APM;
GO

-- 1. Quitar "Dashboard Ejecutivo" de Central (redundante con /dashboard).
DELETE FROM CORE_Permisos_Nodo WHERE id_modulo=1 AND opcion=1 AND items=2 AND subitems=0;
DELETE FROM CORE_Menu_Nodos    WHERE id_modulo=1 AND opcion=1 AND items=2 AND subitems=0;

-- Renombrar "Dashboard Operativo" a "Dashboard" (ya no hace falta distinguir
-- de un "Ejecutivo" separado — sigue siendo la vista operativa cruda para
-- quien quiera verla explícitamente, además de la que /dashboard autoadapta).
UPDATE CORE_Menu_Nodos SET orden=2 WHERE id_modulo=1 AND opcion=1 AND items=3 AND subitems=0;
GO

-- 2. Panel nativo para Talento Humano (item 1, antes del "Sistema Completo" item 5).
IF NOT EXISTS (SELECT 1 FROM CORE_Menu_Nodos WHERE id_modulo=11 AND opcion=1 AND items=1 AND subitems=0)
    INSERT INTO CORE_Menu_Nodos (id_modulo,opcion,items,subitems,descripcion,url_ruta,icono,orden,requiere_mfa,target_spa,estado)
    VALUES (11,1,1,0,N'Panel',N'/panel/talento-humano',N'fa-chart-pie',1,0,1,1);
GO

-- 3. Panel nativo para Control de Bienes.
IF NOT EXISTS (SELECT 1 FROM CORE_Menu_Nodos WHERE id_modulo=12 AND opcion=1 AND items=1 AND subitems=0)
    INSERT INTO CORE_Menu_Nodos (id_modulo,opcion,items,subitems,descripcion,url_ruta,icono,orden,requiere_mfa,target_spa,estado)
    VALUES (12,1,1,0,N'Panel',N'/panel/bienes',N'fa-chart-pie',1,0,1,1);
GO

-- 4. Permisos del nuevo "Panel": mismo rol/nivel_crud que ya tiene el item 5
--    "Sistema Completo (Origen)" de cada módulo (quien puede abrir el sistema
--    completo, puede ver su panel de un vistazo).
INSERT INTO CORE_Permisos_Nodo (id_rol,id_modulo,opcion,items,subitems,nivel_crud,acceso,estado,fecha_asignacion,asignado_por)
SELECT pn.id_rol, pn.id_modulo, 1, 1, 0, pn.nivel_crud, 1, 1, GETDATE(), 1
FROM CORE_Permisos_Nodo pn
WHERE pn.id_modulo IN (11,12) AND pn.opcion=1 AND pn.items=5 AND pn.subitems=0
  AND pn.acceso=1 AND pn.estado=1
  AND NOT EXISTS (
    SELECT 1 FROM CORE_Permisos_Nodo x
    WHERE x.id_rol=pn.id_rol AND x.id_modulo=pn.id_modulo AND x.opcion=1 AND x.items=1 AND x.subitems=0
  );
GO

PRINT 'Panel nativo TH/Bienes agregado. Dashboard Ejecutivo duplicado removido de Central.';
GO
