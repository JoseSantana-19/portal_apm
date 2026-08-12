/* db/permisos_centrales_fase3_bitacoras.sql
   Fase 3: Bitácoras Portuarias. Idempotente. */
USE [PORTAL_APM];
GO
SET ANSI_NULLS ON;
SET QUOTED_IDENTIFIER ON;
GO

-- La investigación de código decía que no había grants reales sobre el
-- nodo-esqueleto -- FALSO, verificado por SQL: 6 roles (1,2,5,6,7,13)
-- tienen permisos reales sobre los 3 nodos-esqueleto (opcion=0 raíz,
-- opcion=1/items=0 "Bitácoras", opcion=1/items=7 "Sistema de Bitácoras").
-- Mismo patrón de captura-antes-de-borrar que TH Fase 1 / Bienes Fase 2:
-- se guarda el nivel máximo por rol y se re-otorga sobre el nuevo nodo
-- "Dashboard" (opcion=1). El nodo raíz (opcion=0, "Bitácoras Portuarias")
-- NO se toca -- mismo criterio que Bienes Fase 2, solo opcion>=1 es
-- "árbol de pantallas", opcion=0 es el nodo de agrupación del módulo.
IF EXISTS (SELECT 1 FROM dbo.CORE_Menu_Nodos WHERE id_modulo=13 AND opcion>=1)
   AND NOT EXISTS (SELECT 1 FROM dbo.CORE_Menu_Nodos WHERE id_modulo=13 AND descripcion='Dashboard')
BEGIN
    SELECT id_rol, MAX(nivel_crud) AS nivel_crud
    INTO #PermisosBitacorasViejos
    FROM dbo.CORE_Permisos_Nodo
    WHERE id_modulo=13 AND opcion>=1
    GROUP BY id_rol;

    DELETE pn FROM dbo.CORE_Permisos_Nodo pn
        JOIN dbo.CORE_Menu_Nodos mn ON mn.id_modulo=pn.id_modulo AND mn.opcion=pn.opcion AND mn.items=pn.items AND mn.subitems=pn.subitems
        WHERE mn.id_modulo=13 AND mn.opcion>=1;
    DELETE FROM dbo.CORE_Menu_Nodos WHERE id_modulo=13 AND opcion>=1;
END;
GO

IF NOT EXISTS (SELECT 1 FROM dbo.CORE_Menu_Nodos WHERE id_modulo=13 AND descripcion='Dashboard')
BEGIN
    INSERT dbo.CORE_Menu_Nodos (id_modulo, opcion, items, subitems, descripcion, url_ruta, icono, orden, requiere_mfa, target_spa, estado) VALUES
    (13, 1, 0, 0, 'Dashboard',                    'apps/bitacoras/portuaria/dashboard',   'fa-gauge-high',        1,  0, 0, 1),
    (13, 2, 0, 0, 'Dashboard de Jefatura',         'apps/bitacoras/dashboard-jefe',        'fa-chart-line',        2,  1, 0, 1),
    (13, 3, 0, 0, 'Registrar Ingreso',             'apps/bitacoras/visitas/registrar',     'fa-right-to-bracket',  3,  0, 0, 1),
    (13, 4, 0, 0, 'Listado de Visitas',            'apps/bitacoras/visitas',               'fa-list-check',        4,  0, 0, 1),
    (13, 5, 0, 0, 'Registros Base (Catálogos)',    'apps/bitacoras/catalogos',             'fa-address-book',      5,  0, 0, 1),
    (13, 6, 0, 0, 'Rondas de Vigilancia',          'apps/bitacoras/rondas',                'fa-person-walking-arrow-right', 6, 0, 0, 1),
    (13, 7, 0, 0, 'Cámaras CCTV',                  'apps/bitacoras/camaras',               'fa-video',             7,  0, 0, 1),
    (13, 8, 0, 0, 'Reporte de Supervisor',         'apps/bitacoras/bit_reporte_diario_supervisor.php', 'fa-clipboard-list', 8, 0, 0, 1),
    (13, 9, 0, 0, 'Importar Funcionarios',         'apps/bitacoras/importar-funcionarios', 'fa-file-csv',          9,  0, 0, 1),
    (13, 10,0, 0, 'Configurar Días de Bitácora',   NULL,                                                     'fa-calendar-days',     10, 0, 0, 1),
    (13, 11,0, 0, 'Gestión de Catálogos (escritura)', NULL,                                                  'fa-pen',               11, 0, 0, 1),
    (13, 12,0, 0, 'Gestión de Personas (escritura)',  NULL,                                                  'fa-user-pen',          12, 0, 0, 1),
    (13, 13,0, 0, 'Asignar Cédula Guest',          NULL,                                                     'fa-id-card',           13, 0, 0, 1);
END;
GO

-- Re-otorgar sobre el nuevo nodo "Dashboard" el nivel que cada rol ya
-- tenía en el árbol viejo -- ningún rol pierde acceso por la
-- reestructuración del árbol.
IF OBJECT_ID('tempdb..#PermisosBitacorasViejos') IS NOT NULL
BEGIN
    INSERT dbo.CORE_Permisos_Nodo (id_rol, id_modulo, opcion, items, subitems, nivel_crud, acceso, estado, fecha_asignacion)
    SELECT v.id_rol, 13, 1, 0, 0, v.nivel_crud, 1, 1, SYSDATETIME()
    FROM #PermisosBitacorasViejos v
    WHERE NOT EXISTS (
        SELECT 1 FROM dbo.CORE_Permisos_Nodo p
        WHERE p.id_rol=v.id_rol AND p.id_modulo=13 AND p.opcion=1 AND p.items=0 AND p.subitems=0
    );
    DROP TABLE #PermisosBitacorasViejos;
END;
GO
