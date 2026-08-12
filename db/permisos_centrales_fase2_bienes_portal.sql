/* db/permisos_centrales_fase2_bienes_portal.sql
   Fase 2: Control de Bienes, lado portal. Idempotente. */
USE [PORTAL_APM];
GO
SET ANSI_NULLS ON;
SET QUOTED_IDENTIFIER ON;
GO

-- 3 roles portal nuevos para los roles nativos de Bienes que no tienen
-- equivalente hoy (Administrador sí lo tiene: ADMIN, id_rol=1).
IF NOT EXISTS (SELECT 1 FROM dbo.CORE_Roles WHERE codigo = 'BIENES_SUPERVISOR')
BEGIN
    INSERT dbo.CORE_Roles (codigo, nombre, id_departamento, nivel_jerarquia, estado, fecha_creacion) VALUES
    ('BIENES_SUPERVISOR', 'Supervisor de Bienes', 23, 2, 1, SYSDATETIME()),
    ('BIENES_OPERADOR',   'Operador de Bienes',   23, 1, 1, SYSDATETIME()),
    ('BIENES_AUDITOR',    'Auditor de Bienes',    23, 1, 1, SYSDATETIME());
END;
GO

IF NOT EXISTS (SELECT 1 FROM dbo.CORE_Roles_Modulo_Map WHERE id_modulo = 12)
BEGIN
    INSERT dbo.CORE_Roles_Modulo_Map (id_modulo, id_rol_portal, id_rol_externo)
    SELECT 12, r.id_rol, x.id_rol_externo
    FROM (VALUES ('ADMIN', 1), ('BIENES_SUPERVISOR', 2), ('BIENES_OPERADOR', 3), ('BIENES_AUDITOR', 4)) AS x(codigo, id_rol_externo)
    JOIN dbo.CORE_Roles r ON r.codigo = x.codigo;
END;
GO

-- Retirar los nodos-esqueleto de Bienes antes de sembrar el árbol real
-- (protegido: solo corre si el árbol real, identificado por el nodo
-- "Dashboard", todavía no existe). Los nodos viejos ya tienen permisos
-- reales de 6 roles -- se guardan primero y se re-otorgan sobre "Dashboard".
IF EXISTS (SELECT 1 FROM dbo.CORE_Menu_Nodos WHERE id_modulo=12 AND opcion>=1)
   AND NOT EXISTS (SELECT 1 FROM dbo.CORE_Menu_Nodos WHERE id_modulo=12 AND descripcion='Dashboard')
BEGIN
    SELECT id_rol, MAX(nivel_crud) AS nivel_crud
    INTO #PermisosBienesViejos
    FROM dbo.CORE_Permisos_Nodo
    WHERE id_modulo=12
    GROUP BY id_rol;

    DELETE pn FROM dbo.CORE_Permisos_Nodo pn
        JOIN dbo.CORE_Menu_Nodos mn ON mn.id_modulo=pn.id_modulo AND mn.opcion=pn.opcion AND mn.items=pn.items AND mn.subitems=pn.subitems
        WHERE mn.id_modulo=12;
    DELETE FROM dbo.CORE_Menu_Nodos WHERE id_modulo=12 AND opcion>=1;
END;
GO

IF NOT EXISTS (SELECT 1 FROM dbo.CORE_Menu_Nodos WHERE id_modulo=12 AND descripcion='Dashboard')
BEGIN
    INSERT dbo.CORE_Menu_Nodos (id_modulo, opcion, items, subitems, descripcion, url_ruta, icono, orden, requiere_mfa, target_spa, estado) VALUES
    (12, 1, 0, 0, 'Dashboard',                 'apps/control_bienes/index.php?route=dashboard',           'fa-gauge-high',     1,  0, 0, 1),
    (12, 2, 0, 0, 'Inventario General',        'apps/control_bienes/index.php?route=inventario',          'fa-ship',           2,  0, 0, 1),
    (12, 3, 0, 0, 'Catálogo de Ítems',         'apps/control_bienes/index.php?route=items',               'fa-box',            3,  0, 0, 1),
    (12, 4, 0, 0, 'Ítems del Sistema',         'apps/control_bienes/index.php?route=inv_items_sistema',   'fa-cubes',          4,  0, 0, 1),
    (12, 5, 0, 0, 'Tablas de Cabecera',        'apps/control_bienes/index.php?route=cabeceras',           'fa-table-columns',  5,  0, 0, 1),
    (12, 6, 0, 0, 'Maestros',                  'apps/control_bienes/index.php?route=inv_maestros',        'fa-layer-group',    6,  0, 0, 1),
    (12, 7, 0, 0, 'Ingresos de Bodega',        'apps/control_bienes/index.php?route=ingresos',             'fa-truck-ramp-box', 7,  0, 0, 1),
    (12, 8, 0, 0, 'Egresos de Bodega',         'apps/control_bienes/index.php?route=egresos',              'fa-truck-arrow-right', 8, 0, 0, 1),
    (12, 9, 0, 0, 'Directorio de Personal',    'apps/control_bienes/index.php?route=talento_directorio',   'fa-users',          9,  0, 0, 1),
    (12, 10,0, 0, 'Bitácora del Sistema',      'apps/control_bienes/index.php?route=inv_bitacora',         'fa-clock-rotate-left', 10, 1, 0, 1),
    (12, 11,0, 0, 'Reportes Varios',           'apps/control_bienes/index.php?route=reportes',             'fa-chart-pie',      11, 0, 0, 1),
    (12, 12,0, 0, 'Períodos e IVA',            'apps/control_bienes/index.php?route=inv_periodos',         'fa-calendar-days',  12, 1, 0, 1),
    (12, 13,0, 0, 'Secuenciales de Índice',    'apps/control_bienes/index.php?route=inv_secuenciales',     'fa-list-ol',        13, 1, 0, 1),
    (12, 14,0, 0, 'Gestión de Usuarios',       'apps/control_bienes/index.php?route=usuarios',             'fa-user-gear',      14, 1, 0, 1),
    (12, 15,0, 0, 'Gestión de Permisos',       'apps/control_bienes/index.php?route=inv_permisos',         'fa-key',            15, 1, 0, 1);
END;
GO

-- Re-otorgar sobre "Dashboard" (opcion=1) Y sobre el nodo raíz del módulo
-- (opcion=0). opcion=0 es necesario además de opcion=1: sp_GetMenuUsuario/
-- Menu.php (sidebar del portal) resuelve el NOMBRE del módulo a mostrar
-- leyendo esa fila -- sin ella el sidebar cae al label genérico
-- "Módulo 12" (bug real encontrado en producción, 2026-08-11).
IF OBJECT_ID('tempdb..#PermisosBienesViejos') IS NOT NULL
BEGIN
    INSERT dbo.CORE_Permisos_Nodo (id_rol, id_modulo, opcion, items, subitems, nivel_crud, acceso, estado, fecha_asignacion)
    SELECT v.id_rol, 12, x.opcion, 0, 0, v.nivel_crud, 1, 1, SYSDATETIME()
    FROM #PermisosBienesViejos v
    CROSS JOIN (VALUES (0), (1)) AS x(opcion)
    WHERE NOT EXISTS (
        SELECT 1 FROM dbo.CORE_Permisos_Nodo p
        WHERE p.id_rol=v.id_rol AND p.id_modulo=12 AND p.opcion=x.opcion AND p.items=0 AND p.subitems=0
    );
    DROP TABLE #PermisosBienesViejos;
END;
GO
