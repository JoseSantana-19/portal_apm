/* ============================================================================
   Migración: PORTAL_APM — Bitácoras (módulo 13) pasa de nativo/mezclado
   (Patrón C, modules/Portuaria + archivos sueltos en el árbol del portal)
   a app independiente Patrón B (apps/bitacoras/), igual que Talento Humano
   (11) y Control de Bienes (12).

   - El nodo "Panel Portuario" (opcion=1,items=1) apuntaba a /portuaria
     (vista SPA nativa embebida en el shell del portal, target_spa=1) — esa
     ruta ya no existe, el módulo es standalone ahora. Se borra: no hay
     panel nativo equivalente para Bitácoras (a diferencia de TH/Bienes,
     que sí tienen /panel/talento-humano y /panel/bienes vía PanelController
     — construir uno para Bitácoras queda como mejora futura, no bloquea
     esta migración).
   - El nodo "Sistema de Bitácoras" (opcion=1,items=7) pasa de /visitas
     (ruta nativa del router del portal) a /apps/bitacoras/ (entrada de la
     app independiente).

   Idempotente. Ejecutar sobre PORTAL_APM.
   ============================================================================ */

USE PORTAL_APM;
GO
SET NOCOUNT ON;

-- Borrar el nodo "Panel Portuario" (SPA nativo, ruta /portuaria ya no existe)
DELETE FROM CORE_Permisos_Nodo WHERE id_modulo = 13 AND opcion = 1 AND items = 1 AND subitems = 0;
DELETE FROM CORE_Menu_Nodos    WHERE id_modulo = 13 AND opcion = 1 AND items = 1 AND subitems = 0;
GO

-- Repuntar "Sistema de Bitácoras" a la app independiente
UPDATE CORE_Menu_Nodos
   SET url_ruta = N'/apps/bitacoras/', target_spa = 0, estado = 1
 WHERE id_modulo = 13 AND opcion = 1 AND items = 7 AND subitems = 0;

IF @@ROWCOUNT = 0
    INSERT INTO CORE_Menu_Nodos (id_modulo, opcion, items, subitems, descripcion, url_ruta, icono, orden, requiere_mfa, target_spa, estado)
    VALUES (13, 1, 7, 0, N'Sistema de Bitácoras', N'/apps/bitacoras/', N'fa-window-restore', 7, 0, 0, 1);
GO

PRINT 'Bitácoras migrado a apps/bitacoras/ en el menú.';
SELECT opcion,items,subitems,descripcion,url_ruta,target_spa,estado FROM CORE_Menu_Nodos WHERE id_modulo=13 ORDER BY opcion,items;
GO
