/* db/permisos_centrales_fase1_th.sql
   Fase 1: piloto Talento Humano. Idempotente. */
USE [PORTAL_APM];
GO
SET ANSI_NULLS ON;
SET QUOTED_IDENTIFIER ON;
GO

IF OBJECT_ID(N'dbo.CORE_Roles_Modulo_Map', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.CORE_Roles_Modulo_Map (
        id_map         INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
        id_modulo      TINYINT NOT NULL,
        id_rol_portal  INT NOT NULL,
        id_rol_externo INT NOT NULL,
        CONSTRAINT FK_CRMM_rol_portal FOREIGN KEY (id_rol_portal) REFERENCES dbo.CORE_Roles(id_rol),
        CONSTRAINT FK_CRMM_modulo FOREIGN KEY (id_modulo) REFERENCES dbo.CORE_Modulos(id_modulo),
        CONSTRAINT UQ_CRMM_portal UNIQUE (id_modulo, id_rol_portal),
        CONSTRAINT UQ_CRMM_externo UNIQUE (id_modulo, id_rol_externo)
    );
END;
GO

IF NOT EXISTS (SELECT 1 FROM dbo.CORE_Roles_Modulo_Map WHERE id_modulo = 11)
BEGIN
    INSERT dbo.CORE_Roles_Modulo_Map (id_modulo, id_rol_portal, id_rol_externo) VALUES
    (11, 1,  1), -- ADMIN <-> Super Administrador
    (11, 11, 2), -- DIR_TH <-> Director de Talento Humano
    (11, 12, 3), -- ANALISTA_TH <-> Analista de Nómina
    (11, 21, 4); -- LECTOR <-> Funcionario (Lectura)
END;
GO

-- Retirar los 3 nodos-esqueleto de TH antes de sembrar el árbol real
-- (protegido: solo corre si el árbol real, identificado por el nodo
-- "Inicio", todavía no existe). Los nodos viejos YA tenían permisos reales
-- asignados a varios roles (no solo a los 4 mapeados en Fase 1) -- se
-- guardan primero en una tabla temporal y se re-otorgan sobre el nuevo
-- nodo "Inicio" (opcion=1) para no dejar a esos roles sin acceso.
IF EXISTS (SELECT 1 FROM dbo.CORE_Menu_Nodos WHERE id_modulo=11 AND opcion<=3)
   AND NOT EXISTS (SELECT 1 FROM dbo.CORE_Menu_Nodos WHERE id_modulo=11 AND descripcion='Inicio')
BEGIN
    SELECT id_rol, MAX(nivel_crud) AS nivel_crud
    INTO #PermisosThViejos
    FROM dbo.CORE_Permisos_Nodo
    WHERE id_modulo=11
    GROUP BY id_rol;

    DELETE pn FROM dbo.CORE_Permisos_Nodo pn
        JOIN dbo.CORE_Menu_Nodos mn ON mn.id_modulo=pn.id_modulo AND mn.opcion=pn.opcion AND mn.items=pn.items AND mn.subitems=pn.subitems
        WHERE mn.id_modulo=11;
    DELETE FROM dbo.CORE_Menu_Nodos WHERE id_modulo=11;
END;
GO

IF NOT EXISTS (SELECT 1 FROM dbo.CORE_Menu_Nodos WHERE id_modulo=11 AND descripcion='Inicio')
BEGIN
    INSERT dbo.CORE_Menu_Nodos (id_modulo, opcion, items, subitems, descripcion, url_ruta, icono, orden, requiere_mfa, target_spa, estado) VALUES
    (11, 0, 0, 0, 'Talento Humano',                'apps/talento_humano/',                         'fa-users',              0,  0, 0, 1),
    (11, 1, 0, 0, 'Inicio',                        'apps/talento_humano/talento-humano/inicio',    'fa-house',              1,  0, 0, 1),
    (11, 2, 0, 0, 'Directorio de Personal',        'apps/talento_humano/talento-humano/directorio','fa-address-book',       2,  0, 0, 1),
    (11, 3, 0, 0, 'Formulario de Ingreso',         'apps/talento_humano/talento-humano/empleado/crear', 'fa-user-plus',     3,  0, 0, 1),
    (11, 4, 0, 0, 'Acción de Personal',            'apps/talento_humano/talento-humano/accion-personal','fa-file-signature',4,  0, 0, 1),
    (11, 5, 0, 0, 'Movimientos internos',          'apps/talento_humano/talento-humano/directorio?modo=movimiento','fa-arrows-turn-right', 5, 0, 0, 1),
    (11, 6, 0, 0, 'Estudio Socioeconómico',        'apps/talento_humano/talento-humano/estudio-seguridad','fa-house-chimney-user', 6, 0, 0, 1),
    (11, 7, 0, 0, 'Biblioteca de Formularios',     'apps/talento_humano/talento-humano/biblioteca','fa-book',               7,  0, 0, 1),
    (11, 8, 0, 0, 'Estructura y cargos',           'apps/talento_humano/admin/maestros',           'fa-sitemap',            8,  0, 0, 1),
    (11, 9, 0, 0, 'Administración de Usuarios',    'apps/talento_humano/admin/usuarios',           'fa-user-gear',          9,  1, 0, 1),
    (11, 10,0, 0, 'Roles y Permisos',              'apps/talento_humano/admin/roles',              'fa-shield-halved',      10, 1, 0, 1),
    (11, 11,0, 0, 'Políticas y Normativas',        'apps/talento_humano/admin/politicas',          'fa-scroll',             11, 0, 0, 1),
    (11, 12,0, 0, 'Auditoría y Control',           'apps/talento_humano/auditoria/logs',           'fa-clipboard-list',     12, 1, 0, 1),
    (11, 13,0, 0, 'Reportes Generales',            'apps/talento_humano/reportes',                 'fa-chart-column',       13, 0, 0, 1),
    (11, 14,0, 0, 'Prototipos (Asistencia/Vacaciones/Desempeño/Capacitación)', NULL, 'fa-flask',    14, 0, 0, 1);
END;
GO

-- Re-otorgar sobre el nuevo nodo "Inicio" (opcion=1) Y sobre el nodo raíz
-- del módulo (opcion=0) el nivel que cada rol ya tenía en el árbol viejo
-- -- ningún rol pierde acceso por la reestructuración del árbol. opcion=0
-- es necesario además de opcion=1: sp_GetMenuUsuario/Menu.php (sidebar del
-- portal) resuelve el NOMBRE del módulo a mostrar leyendo esa fila -- sin
-- ella el sidebar cae al label genérico "Módulo 11" (bug real encontrado
-- en producción, 2026-08-11, ver permisos_centrales_fase2_bienes_portal.sql
-- para el mismo fix del lado de Bienes).
IF OBJECT_ID('tempdb..#PermisosThViejos') IS NOT NULL
BEGIN
    INSERT dbo.CORE_Permisos_Nodo (id_rol, id_modulo, opcion, items, subitems, nivel_crud, acceso, estado, fecha_asignacion)
    SELECT v.id_rol, 11, x.opcion, 0, 0, v.nivel_crud, 1, 1, SYSDATETIME()
    FROM #PermisosThViejos v
    CROSS JOIN (VALUES (0), (1)) AS x(opcion)
    WHERE NOT EXISTS (
        SELECT 1 FROM dbo.CORE_Permisos_Nodo p
        WHERE p.id_rol=v.id_rol AND p.id_modulo=11 AND p.opcion=x.opcion AND p.items=0 AND p.subitems=0
    );
    DROP TABLE #PermisosThViejos;
END;
GO
