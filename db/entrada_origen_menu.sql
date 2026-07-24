/* ============================================================
   MENÚ FINAL: entrada única al sistema ORIGEN por módulo
   Cada módulo integrado queda con exactamente 2 ítems:
     · Panel de <módulo>   → hub de bienvenida (KPIs, en el shell)
     · Sistema de <módulo> → proyecto origen COMPLETO (pantalla
       completa, navegación con su propio menú)
   El detalle de opciones vive DENTRO de cada sistema origen.
   Idempotente.
   ============================================================ */
USE PORTAL_APM;
GO
SET NOCOUNT ON;

/* ── Módulo 11: Talento Humano ─────────────────────────────── */
-- Ítems visibles: Panel (11,1,4) + Sistema (11,1,5)
UPDATE CORE_Menu_Nodos SET estado = 0
WHERE id_modulo = 11 AND NOT (opcion = 0 AND items = 0)      -- raíz se conserva
  AND NOT (opcion = 1 AND items IN (0, 4, 5) AND subitems = 0);

UPDATE CORE_Menu_Nodos SET estado = 1
WHERE id_modulo = 11 AND opcion = 1 AND items IN (0, 4, 5) AND subitems = 0;

UPDATE CORE_Menu_Nodos
SET descripcion = N'Panel de Talento Humano', url_ruta = N'/th', icono = N'fa-gauge-high', orden = 1, target_spa = 1
WHERE id_modulo = 11 AND opcion = 1 AND items = 4 AND subitems = 0;

UPDATE CORE_Menu_Nodos
SET descripcion = N'Sistema de Talento Humano', url_ruta = N'/apps/talento_humano/', icono = N'fa-users-gear', orden = 2, target_spa = 0
WHERE id_modulo = 11 AND opcion = 1 AND items = 5 AND subitems = 0;

UPDATE CORE_Menu_Nodos SET descripcion = N'Talento Humano'
WHERE id_modulo = 11 AND opcion = 1 AND items = 0 AND subitems = 0;

/* ── Módulo 12: Control de Bienes ──────────────────────────── */
UPDATE CORE_Menu_Nodos SET estado = 0
WHERE id_modulo = 12 AND NOT (opcion = 0 AND items = 0)
  AND NOT (opcion = 1 AND items IN (0, 4, 5) AND subitems = 0);

UPDATE CORE_Menu_Nodos SET estado = 1
WHERE id_modulo = 12 AND opcion = 1 AND items IN (0, 4, 5) AND subitems = 0;

UPDATE CORE_Menu_Nodos
SET descripcion = N'Panel de Bienes', url_ruta = N'/inventario/panel', icono = N'fa-gauge-high', orden = 1, target_spa = 1
WHERE id_modulo = 12 AND opcion = 1 AND items = 4 AND subitems = 0;

UPDATE CORE_Menu_Nodos
SET descripcion = N'Sistema de Control de Bienes', url_ruta = N'/apps/control_bienes/', icono = N'fa-boxes-stacked', orden = 2, target_spa = 0
WHERE id_modulo = 12 AND opcion = 1 AND items = 5 AND subitems = 0;

UPDATE CORE_Menu_Nodos SET descripcion = N'Control de Bienes'
WHERE id_modulo = 12 AND opcion = 1 AND items = 0 AND subitems = 0;

/* ── Módulo 13: Bitácoras Portuarias ───────────────────────── */
-- Ítem nuevo: Sistema de Bitácoras (13,1,7)
IF NOT EXISTS (SELECT 1 FROM CORE_Menu_Nodos WHERE id_modulo=13 AND opcion=1 AND items=7 AND subitems=0)
    INSERT INTO CORE_Menu_Nodos (id_modulo, opcion, items, subitems, descripcion, url_ruta, icono, orden, requiere_mfa, target_spa, estado)
    VALUES (13,1,7,0, N'Sistema de Bitácoras', N'/visitas', N'fa-anchor', 2, 0, 0, 1);
ELSE
    UPDATE CORE_Menu_Nodos SET descripcion=N'Sistema de Bitácoras', url_ruta=N'/visitas', orden=2, target_spa=0, estado=1
    WHERE id_modulo=13 AND opcion=1 AND items=7 AND subitems=0;

-- Solo Panel (13,1,1) + Sistema (13,1,7) visibles
UPDATE CORE_Menu_Nodos SET estado = 0
WHERE id_modulo = 13 AND NOT (opcion = 0 AND items = 0)
  AND NOT (opcion = 1 AND items IN (0, 1, 7) AND subitems = 0);

UPDATE CORE_Menu_Nodos SET estado = 1
WHERE id_modulo = 13 AND opcion = 1 AND items IN (0, 1, 7) AND subitems = 0;

UPDATE CORE_Menu_Nodos
SET descripcion = N'Panel Portuario', orden = 1
WHERE id_modulo = 13 AND opcion = 1 AND items = 1 AND subitems = 0;

UPDATE CORE_Menu_Nodos SET descripcion = N'Bitácoras'
WHERE id_modulo = 13 AND opcion = 1 AND items = 0 AND subitems = 0;

/* ── Permisos del ítem nuevo (13,1,7): heredar del área (13,1,0) ── */
INSERT INTO CORE_Permisos_Nodo (id_rol, id_modulo, opcion, items, subitems, nivel_crud, acceso, estado, fecha_asignacion, asignado_por)
SELECT pn.id_rol, 13, 1, 7, 0, pn.nivel_crud, 1, 1, GETDATE(), 1
FROM CORE_Permisos_Nodo pn
WHERE pn.id_modulo = 13 AND pn.opcion = 1 AND pn.items = 0 AND pn.subitems = 0
  AND pn.acceso = 1 AND pn.estado = 1
  AND NOT EXISTS (SELECT 1 FROM CORE_Permisos_Nodo x
                  WHERE x.id_rol = pn.id_rol AND x.id_modulo = 13 AND x.opcion = 1 AND x.items = 7 AND x.subitems = 0);

PRINT 'Menú de entrada única al origen aplicado.';
SELECT id_modulo, COUNT(*) AS items_activos
FROM CORE_Menu_Nodos WHERE id_modulo IN (11,12,13) AND estado = 1 AND items > 0 AND subitems = 0
GROUP BY id_modulo;
GO
