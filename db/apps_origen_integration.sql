/* ============================================================
   Apps ORIGEN embebidas (paridad 100% con los proyectos fuente)
   - apps/talento_humano  → PortalPortuario (TH) completo
   - apps/control_bienes  → Control_bines1 completo
   (Bitácoras ya corre con su origen completo como módulo)

   1) Tabla th_parametros que el código origen TH necesita
      (BD Talento_Humano venía del .bak sin ella) + RBU vigente.
   2) Nodos de menú "Sistema Completo (Origen)" en módulos 11 y 12
      con permisos heredados del área.
   Idempotente.
   ============================================================ */

/* ── 1. th_parametros en BD Talento_Humano ─────────────────── */
USE Talento_Humano;
GO
IF OBJECT_ID(N'dbo.th_parametros', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.th_parametros (
        parametro_id  NVARCHAR(50)  NOT NULL PRIMARY KEY,
        valor         NVARCHAR(255) NOT NULL,
        descripcion   NVARCHAR(255) NULL,
        fecha_mod     DATETIME2     NOT NULL DEFAULT GETDATE()
    );
END
GO
IF NOT EXISTS (SELECT 1 FROM dbo.th_parametros WHERE parametro_id = N'RBU_2026')
    INSERT INTO dbo.th_parametros (parametro_id, valor, descripcion)
    VALUES (N'RBU_2026', N'470.00', N'Remuneración Básica Unificada 2026 (Ecuador)');
GO

/* ── 2. Nodos de menú en PORTAL_APM ────────────────────────── */
USE PORTAL_APM;
GO
SET NOCOUNT ON;

IF NOT EXISTS (SELECT 1 FROM CORE_Menu_Nodos WHERE id_modulo=11 AND opcion=1 AND items=5 AND subitems=0)
    INSERT INTO CORE_Menu_Nodos (id_modulo, opcion, items, subitems, descripcion, url_ruta, icono, orden, requiere_mfa, target_spa, estado)
    VALUES (11,1,5,0, N'Sistema Completo (Origen)', N'/apps/talento_humano/', N'fa-window-restore', 9, 0, 0, 1);
ELSE
    UPDATE CORE_Menu_Nodos SET url_ruta=N'/apps/talento_humano/', target_spa=0, estado=1
    WHERE id_modulo=11 AND opcion=1 AND items=5 AND subitems=0;

IF NOT EXISTS (SELECT 1 FROM CORE_Menu_Nodos WHERE id_modulo=12 AND opcion=1 AND items=5 AND subitems=0)
    INSERT INTO CORE_Menu_Nodos (id_modulo, opcion, items, subitems, descripcion, url_ruta, icono, orden, requiere_mfa, target_spa, estado)
    VALUES (12,1,5,0, N'Sistema Completo (Origen)', N'/apps/control_bienes/', N'fa-window-restore', 9, 0, 0, 1);
ELSE
    UPDATE CORE_Menu_Nodos SET url_ruta=N'/apps/control_bienes/', target_spa=0, estado=1
    WHERE id_modulo=12 AND opcion=1 AND items=5 AND subitems=0;

/* Permisos heredados de los roles con acceso al área 1 de cada módulo */
INSERT INTO CORE_Permisos_Nodo (id_rol, id_modulo, opcion, items, subitems, nivel_crud, acceso, estado, fecha_asignacion, asignado_por)
SELECT pn.id_rol, pn.id_modulo, 1, 5, 0, pn.nivel_crud, 1, 1, GETDATE(), 1
FROM CORE_Permisos_Nodo pn
WHERE pn.id_modulo IN (11,12) AND pn.opcion = 1 AND pn.items = 0 AND pn.subitems = 0
  AND pn.acceso = 1 AND pn.estado = 1
  AND NOT EXISTS (
    SELECT 1 FROM CORE_Permisos_Nodo x
    WHERE x.id_rol = pn.id_rol AND x.id_modulo = pn.id_modulo
      AND x.opcion = 1 AND x.items = 5 AND x.subitems = 0
  );

PRINT 'Apps origen integradas al menú.';
SELECT 'th_parametros filas' AS info, (SELECT COUNT(*) FROM Talento_Humano.dbo.th_parametros) AS n
UNION ALL
SELECT 'Nodos apps origen', COUNT(*) FROM CORE_Menu_Nodos WHERE opcion=1 AND items=5 AND id_modulo IN (11,12);
GO
