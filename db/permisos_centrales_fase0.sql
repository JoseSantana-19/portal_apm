/* db/permisos_centrales_fase0.sql
   Fase 0 del sistema central de menú/permisos: registro de módulos +
   override de permiso por usuario individual. Idempotente. */
USE [PORTAL_APM];
GO
SET ANSI_NULLS ON;
SET QUOTED_IDENTIFIER ON;
GO

IF OBJECT_ID(N'dbo.CORE_Modulos', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.CORE_Modulos (
        id_modulo      TINYINT NOT NULL PRIMARY KEY,
        codigo         NVARCHAR(30)  NOT NULL,
        nombre         NVARCHAR(150) NOT NULL,
        icono          NVARCHAR(50)  NOT NULL CONSTRAINT DF_CORE_Modulos_icono DEFAULT ('fa-folder'),
        color          NVARCHAR(10)  NOT NULL CONSTRAINT DF_CORE_Modulos_color DEFAULT ('#6c757d'),
        tipo           NVARCHAR(20)  NOT NULL CONSTRAINT DF_CORE_Modulos_tipo DEFAULT ('nativo'),
        base_url       NVARCHAR(200) NULL,
        conexion_bd    NVARCHAR(50)  NULL,
        orden          SMALLINT NOT NULL CONSTRAINT DF_CORE_Modulos_orden DEFAULT (0),
        estado         TINYINT NOT NULL CONSTRAINT DF_CORE_Modulos_estado DEFAULT (1),
        fecha_creacion DATETIME2(3) NOT NULL CONSTRAINT DF_CORE_Modulos_fecha DEFAULT (SYSDATETIME()),
        CONSTRAINT UQ_CORE_Modulos_codigo UNIQUE (codigo),
        CONSTRAINT CK_CORE_Modulos_tipo CHECK (tipo IN ('nativo','embebido'))
    );
END;
GO

IF NOT EXISTS (SELECT 1 FROM dbo.CORE_Modulos)
BEGIN
    INSERT dbo.CORE_Modulos (id_modulo, codigo, nombre, icono, color, tipo, base_url, conexion_bd, orden) VALUES
    (1,  'PLANIFICACION', 'Dirección de Planificación Estratégica',  'fa-chart-gantt',    '#6f42c1', 'nativo',   NULL, NULL, 1),
    (2,  'TICS',          'Gestión de Tecnología de la Información', 'fa-server',         '#0056b3', 'nativo',   NULL, NULL, 2),
    (3,  'JURIDICA',      'Dirección de Asesoría Jurídica',          'fa-scale-balanced', '#dc3545', 'nativo',   NULL, NULL, 3),
    (4,  'INFRAESTRUCTURA','Dirección de Infraestructura Portuaria', 'fa-hard-hat',       '#fd7e14', 'nativo',   NULL, NULL, 4),
    (5,  'GARITA',        'Garita de Acceso / Control de Acceso',    'fa-door-open',      '#20c997', 'nativo',   NULL, NULL, 5),
    (6,  'OPERACIONES',   'Dirección de Operaciones',                'fa-ship',           '#17a2b8', 'nativo',   NULL, NULL, 6),
    (7,  'GERENCIA',      'Gerencia General',                        'fa-building',       '#343a40', 'nativo',   NULL, NULL, 7),
    (8,  'DELEGACION',    'Delegación de Servicios Portuarios',      'fa-landmark',       '#6f42c1', 'nativo',   NULL, NULL, 8),
    (9,  'ADMINISTRATIVA','Dirección Administrativa',                'fa-briefcase',      '#0056b3', 'nativo',   NULL, NULL, 9),
    (10, 'FINANCIERA',    'Dirección Financiera',                    'fa-wallet',         '#28a745', 'nativo',   NULL, NULL, 10),
    (11, 'TH',            'Dirección de Talento Humano',             'fa-users',          '#e83e8c', 'embebido', '/apps/talento_humano',  'talento',    11),
    (12, 'BIENES',        'Control de Bienes (Inventario)',          'fa-boxes-stacked',  '#fd7e14', 'embebido', '/apps/control_bienes',  'inventario', 12),
    (13, 'BITACORAS',     'Bitácoras Portuarias (CCTV/Visitas)',     'fa-anchor',         '#0891b2', 'embebido', '/apps/bitacoras',       'portuaria',  13);
END;
GO

IF OBJECT_ID(N'dbo.CORE_Permisos_Nodo_Usuario', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.CORE_Permisos_Nodo_Usuario (
        id_perm_usuario  INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
        id_usuario       INT NOT NULL,
        id_modulo        TINYINT NOT NULL,
        opcion           TINYINT NOT NULL,
        items            TINYINT NOT NULL,
        subitems         SMALLINT NOT NULL,
        nivel_crud       TINYINT NOT NULL,
        estado           TINYINT NOT NULL CONSTRAINT DF_CORE_PNU_estado DEFAULT (1),
        fecha_asignacion DATETIME2(3) NOT NULL CONSTRAINT DF_CORE_PNU_fecha DEFAULT (SYSDATETIME()),
        asignado_por     INT NULL,
        CONSTRAINT FK_CORE_PNU_usuario FOREIGN KEY (id_usuario) REFERENCES dbo.CORE_Usuarios(id_usuario),
        CONSTRAINT UQ_CORE_PNU UNIQUE (id_usuario, id_modulo, opcion, items, subitems),
        CONSTRAINT CK_CORE_PNU_nivel CHECK (nivel_crud BETWEEN 0 AND 4)
    );
END;
GO

CREATE OR ALTER FUNCTION [dbo].[fn_TienePermisoNodo](
    @id_usuario INT,
    @id_modulo  TINYINT,
    @opcion     TINYINT,
    @items      TINYINT,
    @subitems   SMALLINT,
    @nivel_min  TINYINT,
    @mfa_ok     BIT = 1
) RETURNS BIT AS
BEGIN
    DECLARE @nivelUsuario TINYINT = NULL;

    SELECT TOP 1 @nivelUsuario = pu.nivel_crud
    FROM dbo.CORE_Permisos_Nodo_Usuario pu
    WHERE pu.id_usuario = @id_usuario AND pu.estado = 1
      AND pu.id_modulo = @id_modulo AND pu.opcion = @opcion
      AND pu.items = @items AND pu.subitems = @subitems;

    IF @nivelUsuario IS NOT NULL
    BEGIN
        IF @nivelUsuario >= @nivel_min RETURN 1;
        RETURN 0;
    END;

    IF EXISTS (
        SELECT 1 FROM CORE_Usuarios u
        JOIN CORE_Usuarios_Roles ur ON ur.id_usuario=u.id_usuario AND ur.estado=1
        JOIN CORE_Roles r           ON r.id_rol=ur.id_rol AND r.estado=1
        JOIN CORE_Permisos_Nodo pn  ON pn.id_rol=r.id_rol AND pn.acceso=1 AND pn.estado=1
                                   AND pn.id_modulo=@id_modulo AND pn.opcion=@opcion
                                   AND pn.items=@items AND pn.subitems=@subitems
        JOIN CORE_Menu_Nodos mn     ON mn.id_modulo=pn.id_modulo AND mn.opcion=pn.opcion
                                   AND mn.items=pn.items AND mn.subitems=pn.subitems
                                   AND mn.estado=1
        WHERE u.id_usuario=@id_usuario AND u.estado=1
          AND pn.nivel_crud>=@nivel_min AND (mn.requiere_mfa=0 OR @mfa_ok=1)
    ) RETURN 1; RETURN 0;
END;
GO
