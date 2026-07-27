/* ============================================================================
   PORTAL_APM_COMPLETO.sql — script único de creación desde cero.

   Portal APM no tiene módulos de negocio propios: es el hub central (login,
   permisos, menú, administración). Los módulos reales viven aparte —
   Talento_Humano (BD propia), inventario (BD propia, app embebida
   apps/control_bienes), PortuariaDemo/PortuariaExterna (BD propia, native
   modules/Portuaria) — este script SOLO crea PORTAL_APM.

   Cambios de esta revisión (ver GUIA_INTEGRACION_MODULOS.md y
   db/identidad_cross_db.sql / db/drop_tablas_muertas_modulos.sql para el
   detalle de cada migración):
     - CORE_Usuarios.id_empleado_th + vw_Usuarios_Identidad: el nombre/cédula
       de un usuario ligado a un empleado se lee EN VIVO desde
       Talento_Humano.dbo.th_empleados (cross-DB, misma instancia) — no se
       duplica a mano.
     - Se eliminaron TH_/BIENES_/BIT_ (prefijos, excepto TH_Unidad_Map): eran copias
       muertas de la reescritura nativa inicial de Talento Humano/Bienes/
       Bitácoras, ya reemplazada por las BDs reales de cada módulo integrado.
     - CORE_Menu_Nodos separa departamento (organigrama, sin menú propio) de
       módulo (Central + Talento Humano + Control de Bienes + Portuaria —
       los únicos con entrada real en el menú).

   ¡ATENCIÓN! Este script hace DROP + CREATE de la base PORTAL_APM — borra
   todos los datos existentes. Pensado para instalar desde cero en una
   máquina nueva; NO correr sobre una base en producción con datos que
   quieras conservar sin respaldo previo.
   ============================================================================ */

USE [master]
GO
IF EXISTS (SELECT name FROM sys.databases WHERE name = N'PORTAL_APM')
BEGIN
    ALTER DATABASE [PORTAL_APM] SET SINGLE_USER WITH ROLLBACK IMMEDIATE;
    DROP DATABASE [PORTAL_APM];
END
GO
CREATE DATABASE [PORTAL_APM] COLLATE Modern_Spanish_CI_AS;
GO
ALTER DATABASE [PORTAL_APM] SET COMPATIBILITY_LEVEL = 120
GO
ALTER DATABASE [PORTAL_APM] SET RECOVERY SIMPLE
GO
ALTER DATABASE [PORTAL_APM] SET ANSI_NULL_DEFAULT OFF 
GO
ALTER DATABASE [PORTAL_APM] SET ANSI_NULLS OFF 
GO
ALTER DATABASE [PORTAL_APM] SET ANSI_PADDING OFF 
GO
ALTER DATABASE [PORTAL_APM] SET ANSI_WARNINGS OFF 
GO
ALTER DATABASE [PORTAL_APM] SET ARITHABORT OFF 
GO
ALTER DATABASE [PORTAL_APM] SET AUTO_CLOSE ON 
GO
ALTER DATABASE [PORTAL_APM] SET AUTO_SHRINK OFF 
GO
ALTER DATABASE [PORTAL_APM] SET AUTO_UPDATE_STATISTICS ON 
GO
ALTER DATABASE [PORTAL_APM] SET CURSOR_CLOSE_ON_COMMIT OFF 
GO
ALTER DATABASE [PORTAL_APM] SET CURSOR_DEFAULT  GLOBAL 
GO
ALTER DATABASE [PORTAL_APM] SET CONCAT_NULL_YIELDS_NULL OFF 
GO
ALTER DATABASE [PORTAL_APM] SET NUMERIC_ROUNDABORT OFF 
GO
ALTER DATABASE [PORTAL_APM] SET QUOTED_IDENTIFIER OFF 
GO
ALTER DATABASE [PORTAL_APM] SET RECURSIVE_TRIGGERS OFF 
GO
ALTER DATABASE [PORTAL_APM] SET  ENABLE_BROKER 
GO
ALTER DATABASE [PORTAL_APM] SET AUTO_UPDATE_STATISTICS_ASYNC OFF 
GO
ALTER DATABASE [PORTAL_APM] SET DATE_CORRELATION_OPTIMIZATION OFF 
GO
ALTER DATABASE [PORTAL_APM] SET TRUSTWORTHY OFF 
GO
ALTER DATABASE [PORTAL_APM] SET ALLOW_SNAPSHOT_ISOLATION OFF 
GO
ALTER DATABASE [PORTAL_APM] SET PARAMETERIZATION SIMPLE 
GO
ALTER DATABASE [PORTAL_APM] SET READ_COMMITTED_SNAPSHOT OFF 
GO
ALTER DATABASE [PORTAL_APM] SET HONOR_BROKER_PRIORITY OFF 
GO
ALTER DATABASE [PORTAL_APM] SET RECOVERY SIMPLE 
GO
ALTER DATABASE [PORTAL_APM] SET  MULTI_USER 
GO
ALTER DATABASE [PORTAL_APM] SET PAGE_VERIFY CHECKSUM  
GO
ALTER DATABASE [PORTAL_APM] SET DB_CHAINING OFF 
GO
ALTER DATABASE [PORTAL_APM] SET FILESTREAM( NON_TRANSACTED_ACCESS = OFF ) 
GO
ALTER DATABASE [PORTAL_APM] SET TARGET_RECOVERY_TIME = 60 SECONDS 
GO
ALTER DATABASE [PORTAL_APM] SET DELAYED_DURABILITY = DISABLED 
GO
ALTER DATABASE [PORTAL_APM] SET ACCELERATED_DATABASE_RECOVERY = OFF  
GO
ALTER DATABASE [PORTAL_APM] SET QUERY_STORE = ON
GO
ALTER DATABASE [PORTAL_APM] SET QUERY_STORE (OPERATION_MODE = READ_WRITE, CLEANUP_POLICY = (STALE_QUERY_THRESHOLD_DAYS = 30), DATA_FLUSH_INTERVAL_SECONDS = 900, INTERVAL_LENGTH_MINUTES = 60, MAX_STORAGE_SIZE_MB = 1000, QUERY_CAPTURE_MODE = AUTO, SIZE_BASED_CLEANUP_MODE = AUTO, MAX_PLANS_PER_QUERY = 200, WAIT_STATS_CAPTURE_MODE = ON)
GO
USE [PORTAL_APM]
GO
/****** Objeto: DatabaseRole [rol_sso_modulos] Fecha de script: 26/7/2026 17:41:54 ******/
CREATE ROLE [rol_sso_modulos]
GO
/****** Objeto: UserDefinedFunction [dbo].[fn_SesionValida] Fecha de script: 26/7/2026 17:41:54 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO

-- Valida sesión activa por token
CREATE FUNCTION [dbo].[fn_SesionValida](@token NVARCHAR(128)) RETURNS BIT AS
BEGIN
    IF EXISTS (SELECT 1 FROM CORE_Sesiones WHERE token=@token AND estado=1 AND fecha_expira>GETDATE()) RETURN 1;
    RETURN 0;
END;

GO
/****** Objeto: UserDefinedFunction [dbo].[fn_SSO_AppValida] Fecha de script: 26/7/2026 17:41:54 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
/* ── 2. Función interna: valida credencial de aplicación ─────────────────── */
CREATE   FUNCTION [dbo].[fn_SSO_AppValida]
(
    @codigo_app NVARCHAR(30),
    @api_key    NVARCHAR(128),
    @ip         NVARCHAR(45)
)
RETURNS INT
AS
BEGIN
    DECLARE @id_app INT;

    SELECT @id_app = a.id_app
    FROM dbo.CORE_Aplicaciones a
    WHERE a.codigo = @codigo_app
      AND a.estado = 1
      AND (a.fecha_expira IS NULL OR a.fecha_expira > GETDATE())
      AND a.api_key_hash = HASHBYTES('SHA2_256', @api_key)
      AND (
            a.ip_permitidas IS NULL
            OR @ip IS NULL                                   -- llamada local confiable
            OR CHARINDEX(',' + @ip + ',', ',' + REPLACE(a.ip_permitidas, ' ', '') + ',') > 0
          );

    RETURN ISNULL(@id_app, 0);
END;
GO
/****** Objeto: UserDefinedFunction [dbo].[fn_TienePermisoFormulario] Fecha de script: 26/7/2026 17:41:54 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO

-- Verifica acceso a un formulario específico (permisos granulares)
CREATE FUNCTION [dbo].[fn_TienePermisoFormulario](
    @id_usuario    INT,
    @id_formulario INT,
    @nivel_minimo  TINYINT
) RETURNS BIT AS
BEGIN
    IF EXISTS (
        SELECT 1 FROM CORE_Usuarios u
        JOIN CORE_Usuarios_Roles ur       ON ur.id_usuario=u.id_usuario AND ur.estado=1
        JOIN CORE_Roles r                 ON r.id_rol=ur.id_rol AND r.estado=1
        JOIN CORE_Formularios_Permisos fp ON fp.id_rol=r.id_rol AND fp.id_formulario=@id_formulario AND fp.estado=1
        WHERE u.id_usuario=@id_usuario AND u.estado=1 AND fp.nivel_crud>=@nivel_minimo
    ) RETURN 1; RETURN 0;
END;

GO
/****** Objeto: UserDefinedFunction [dbo].[fn_TienePermisoNodo] Fecha de script: 26/7/2026 17:41:54 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO

-- ============================================================
-- SECCIÓN 8: FUNCIONES
-- ============================================================

-- Verifica acceso a un nodo MOIS específico
CREATE FUNCTION [dbo].[fn_TienePermisoNodo](
    @id_usuario INT,
    @id_modulo  TINYINT,
    @opcion     TINYINT,
    @items      TINYINT,
    @subitems   SMALLINT,
    @nivel_min  TINYINT,
    @mfa_ok     BIT = 1
) RETURNS BIT AS
BEGIN
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
/****** Objeto: Table [dbo].[CORE_Departamentos] Fecha de script: 26/7/2026 17:41:55 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[CORE_Departamentos](
	[id_departamento] [int] IDENTITY(1,1) NOT NULL,
	[codigo] [nvarchar](20) NOT NULL,
	[nombre] [nvarchar](100) NOT NULL,
	[descripcion] [nvarchar](255) NULL,
	[id_padre] [int] NULL,
	[nivel] [tinyint] NOT NULL,
	[estado] [tinyint] NOT NULL,
	[icono] [nvarchar](50) NULL,
	[color_badge] [nvarchar](7) NULL,
	[fecha_creacion] [datetime2](7) NOT NULL,
PRIMARY KEY CLUSTERED 
(
	[id_departamento] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY],
 CONSTRAINT [UQ_CoreDepto_Codigo] UNIQUE NONCLUSTERED 
(
	[codigo] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
) ON [PRIMARY]
GO
/****** Objeto: UserDefinedFunction [dbo].[fn_GetArbolDepartamento] Fecha de script: 26/7/2026 17:41:55 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO

-- Árbol de departamentos con recursión CTE (SQL Server 2014+)
CREATE FUNCTION [dbo].[fn_GetArbolDepartamento](@id_raiz INT)
RETURNS TABLE AS RETURN (
    WITH Arbol AS (
        SELECT id_departamento,codigo,nombre,id_padre,nivel,0 AS prof,
               CAST(nombre AS NVARCHAR(500)) AS breadcrumb
        FROM CORE_Departamentos WHERE id_departamento=@id_raiz
        UNION ALL
        SELECT d.id_departamento,d.codigo,d.nombre,d.id_padre,d.nivel,a.prof+1,
               CAST(a.breadcrumb+' > '+d.nombre AS NVARCHAR(500))
        FROM CORE_Departamentos d JOIN Arbol a ON a.id_departamento=d.id_padre
    )
    SELECT * FROM Arbol
);

GO
/****** Objeto: Table [dbo].[CORE_Usuarios] Fecha de script: 26/7/2026 17:41:55 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[CORE_Usuarios](
	[id_usuario] [int] IDENTITY(1,1) NOT NULL,
	[nombre_usuario] [nvarchar](50) NOT NULL,
	[correo] [nvarchar](150) NOT NULL,
	[nombre_completo] [nvarchar](150) NOT NULL,
	[hash_contrasena] [nvarchar](512) NOT NULL,
	[salt] [nvarchar](64) NOT NULL,
	[id_departamento] [int] NULL,
	[nivel_jerarquia] [tinyint] NOT NULL,
	[estado] [tinyint] NOT NULL,
	[requiere_mfa] [bit] NOT NULL,
	[mfa_secreto] [nvarchar](32) NULL,
	[requiere_cambio_pass] [bit] NOT NULL,
	[intentos_fallidos] [tinyint] NOT NULL,
	[fecha_bloqueo] [datetime2](7) NULL,
	[minutos_bloqueo] [smallint] NOT NULL,
	[tema_preferido] [nvarchar](20) NOT NULL,
	[cedula] [nvarchar](20) NULL,
	[foto] [nvarchar](255) NULL,
	[fecha_creacion] [datetime2](7) NOT NULL,
	[fecha_modificacion] [datetime2](7) NULL,
	[id_empleado_th] [int] NULL, -- FK lógica a Talento_Humano.dbo.th_empleados (otra BD, sin FK real)
PRIMARY KEY CLUSTERED
(
	[id_usuario] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY],
 CONSTRAINT [UQ_CoreU_Correo] UNIQUE NONCLUSTERED 
(
	[correo] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY],
 CONSTRAINT [UQ_CoreU_Usuario] UNIQUE NONCLUSTERED 
(
	[nombre_usuario] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
) ON [PRIMARY]
GO
/****** Objeto: Table [dbo].[CORE_Roles] Fecha de script: 26/7/2026 17:41:55 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[CORE_Roles](
	[id_rol] [int] IDENTITY(1,1) NOT NULL,
	[codigo] [nvarchar](30) NOT NULL,
	[nombre] [nvarchar](100) NOT NULL,
	[descripcion] [nvarchar](255) NULL,
	[id_departamento] [int] NULL,
	[nivel_jerarquia] [tinyint] NOT NULL,
	[estado] [tinyint] NOT NULL,
	[fecha_creacion] [datetime2](7) NOT NULL,
PRIMARY KEY CLUSTERED 
(
	[id_rol] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY],
 CONSTRAINT [UQ_CoreRol_Codigo] UNIQUE NONCLUSTERED 
(
	[codigo] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
) ON [PRIMARY]
GO
/****** Objeto: Table [dbo].[CORE_Usuarios_Roles] Fecha de script: 26/7/2026 17:41:55 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[CORE_Usuarios_Roles](
	[id_usr_rol] [int] IDENTITY(1,1) NOT NULL,
	[id_usuario] [int] NOT NULL,
	[id_rol] [int] NOT NULL,
	[fecha_asignacion] [datetime2](7) NOT NULL,
	[asignado_por] [int] NULL,
	[estado] [tinyint] NOT NULL,
PRIMARY KEY CLUSTERED 
(
	[id_usr_rol] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY],
 CONSTRAINT [UQ_CoreUR] UNIQUE NONCLUSTERED 
(
	[id_usuario] ASC,
	[id_rol] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
) ON [PRIMARY]
GO
/****** Objeto: Table [dbo].[CORE_Menu_Nodos] Fecha de script: 26/7/2026 17:41:55 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[CORE_Menu_Nodos](
	[id_nodo] [int] IDENTITY(1,1) NOT NULL,
	[id_modulo] [tinyint] NOT NULL,
	[opcion] [tinyint] NOT NULL,
	[items] [tinyint] NOT NULL,
	[subitems] [smallint] NOT NULL,
	[descripcion] [nvarchar](200) NOT NULL,
	[url_ruta] [nvarchar](150) NULL,
	[icono] [nvarchar](50) NULL,
	[orden] [smallint] NOT NULL,
	[requiere_mfa] [bit] NOT NULL,
	[target_spa] [bit] NOT NULL,
	[estado] [tinyint] NOT NULL,
	[fecha_creacion] [datetime2](7) NOT NULL,
PRIMARY KEY CLUSTERED 
(
	[id_nodo] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY],
 CONSTRAINT [UQ_MenuNodo] UNIQUE NONCLUSTERED 
(
	[id_modulo] ASC,
	[opcion] ASC,
	[items] ASC,
	[subitems] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
) ON [PRIMARY]
GO
/****** Objeto: Table [dbo].[CORE_Permisos_Nodo] Fecha de script: 26/7/2026 17:41:55 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[CORE_Permisos_Nodo](
	[id_perm_nodo] [int] IDENTITY(1,1) NOT NULL,
	[id_rol] [int] NOT NULL,
	[id_modulo] [tinyint] NOT NULL,
	[opcion] [tinyint] NOT NULL,
	[items] [tinyint] NOT NULL,
	[subitems] [smallint] NOT NULL,
	[nivel_crud] [tinyint] NOT NULL,
	[acceso] [tinyint] NOT NULL,
	[estado] [tinyint] NOT NULL,
	[fecha_asignacion] [datetime2](7) NOT NULL,
	[asignado_por] [int] NULL,
PRIMARY KEY CLUSTERED 
(
	[id_perm_nodo] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY],
 CONSTRAINT [UQ_PermNodo] UNIQUE NONCLUSTERED 
(
	[id_rol] ASC,
	[id_modulo] ASC,
	[opcion] ASC,
	[items] ASC,
	[subitems] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
) ON [PRIMARY]
GO
/****** Objeto: View [dbo].[vw_MenuPorUsuario] Fecha de script: 26/7/2026 17:41:55 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO

-- ============================================================
-- SECCIÓN 9: VISTAS
-- ============================================================

-- Menú por usuario via MOIS — sin IDs hardcodeados, JOIN correcto por 4-tupla
CREATE VIEW [dbo].[vw_MenuPorUsuario] AS
SELECT
    u.id_usuario,
    mn.id_nodo, mn.id_modulo, mn.opcion, mn.items, mn.subitems,
    mn.descripcion, mn.url_ruta, mn.icono, mn.orden, mn.target_spa,
    MAX(pn.nivel_crud) AS nivel_crud
FROM CORE_Usuarios u
INNER JOIN CORE_Usuarios_Roles ur ON ur.id_usuario = u.id_usuario AND ur.estado = 1
INNER JOIN CORE_Roles r           ON r.id_rol = ur.id_rol AND r.estado = 1
INNER JOIN CORE_Permisos_Nodo pn  ON pn.id_rol = r.id_rol AND pn.acceso = 1 AND pn.estado = 1
INNER JOIN CORE_Menu_Nodos mn     ON mn.id_modulo = pn.id_modulo
                                 AND mn.opcion    = pn.opcion
                                 AND mn.items     = pn.items
                                 AND mn.subitems  = pn.subitems
                                 AND mn.estado    = 1
WHERE u.estado = 1
GROUP BY u.id_usuario, mn.id_nodo, mn.id_modulo, mn.opcion, mn.items,
         mn.subitems, mn.descripcion, mn.url_ruta, mn.icono, mn.orden, mn.target_spa;

GO
/****** Objeto: Table [dbo].[CORE_Auditoria] Fecha de script: 26/7/2026 17:41:55 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[CORE_Auditoria](
	[id_auditoria] [bigint] IDENTITY(1,1) NOT NULL,
	[id_usuario] [int] NULL,
	[nombre_usuario] [nvarchar](50) NULL,
	[modulo] [nvarchar](30) NOT NULL,
	[operacion] [nvarchar](30) NOT NULL,
	[tabla_afectada] [nvarchar](100) NULL,
	[id_registro] [nvarchar](50) NULL,
	[datos_antes] [nvarchar](max) NULL,
	[datos_despues] [nvarchar](max) NULL,
	[ip_address] [nvarchar](45) NULL,
	[resultado] [nvarchar](20) NOT NULL,
	[detalle] [nvarchar](500) NULL,
	[fecha_registro] [datetime2](7) NOT NULL,
	[fecha_purga] [datetime2](7) NULL,
	[estado] [tinyint] NOT NULL,
PRIMARY KEY CLUSTERED 
(
	[id_auditoria] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
) ON [PRIMARY] TEXTIMAGE_ON [PRIMARY]
GO
/****** Objeto: View [dbo].[vw_AuditoriaGlobal] Fecha de script: 26/7/2026 17:41:55 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO

CREATE VIEW [dbo].[vw_AuditoriaGlobal] AS
SELECT a.id_auditoria, a.modulo, a.operacion, a.tabla_afectada, a.resultado,
       a.ip_address, a.detalle, a.fecha_registro,
       ISNULL(u.nombre_completo, a.nombre_usuario) AS nombre_usuario
FROM CORE_Auditoria a LEFT JOIN CORE_Usuarios u ON u.id_usuario = a.id_usuario;

GO
/****** Objeto: Table [dbo].[ACCESO_Registros] Fecha de script: 26/7/2026 17:41:55 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[ACCESO_Registros](
	[id_registro] [bigint] IDENTITY(1,1) NOT NULL,
	[id_visitante] [int] NULL,
	[id_vehiculo] [int] NULL,
	[tipo_acceso] [nvarchar](20) NOT NULL,
	[punto_control] [nvarchar](100) NULL,
	[id_departamento] [int] NULL,
	[persona_visita] [nvarchar](150) NULL,
	[motivo] [nvarchar](500) NULL,
	[estado_registro] [nvarchar](20) NOT NULL,
	[estado] [tinyint] NOT NULL,
	[id_operador] [int] NULL,
	[fecha_hora] [datetime2](7) NOT NULL,
	[observaciones] [nvarchar](500) NULL,
PRIMARY KEY CLUSTERED 
(
	[id_registro] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
) ON [PRIMARY]
GO
/****** Objeto: View [dbo].[vw_KPIs_Acceso] Fecha de script: 26/7/2026 17:41:55 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO

CREATE VIEW [dbo].[vw_KPIs_Acceso] AS
SELECT COUNT(*) AS registros_hoy,
       SUM(CASE WHEN tipo_acceso='Entrada' THEN 1 ELSE 0 END) AS entradas_hoy,
       SUM(CASE WHEN tipo_acceso='Salida'  THEN 1 ELSE 0 END) AS salidas_hoy,
       SUM(CASE WHEN estado_registro='Activo' AND estado=1 THEN 1 ELSE 0 END) AS en_instalaciones
FROM ACCESO_Registros
WHERE CAST(fecha_hora AS DATE)=CAST(GETDATE() AS DATE) AND estado=1;

GO
/****** Objeto: View [dbo].[vw_ResumenRoles] Fecha de script: 26/7/2026 17:41:55 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO

CREATE VIEW [dbo].[vw_ResumenRoles] AS
SELECT u.id_usuario, u.nombre_usuario, u.nombre_completo, u.nivel_jerarquia,
       (SELECT COUNT(*) FROM CORE_Usuarios_Roles ur2 WHERE ur2.id_usuario=u.id_usuario AND ur2.estado=1) AS total_roles,
       -- STUFF+FOR XML PATH para SQL Server 2014 (STRING_AGG requiere 2017+)
       STUFF((SELECT ', ' + r2.nombre
              FROM CORE_Usuarios_Roles ur2
              JOIN CORE_Roles r2 ON r2.id_rol=ur2.id_rol
              WHERE ur2.id_usuario=u.id_usuario AND ur2.estado=1 AND r2.estado=1
              ORDER BY r2.nombre
              FOR XML PATH(''), TYPE).value('.','NVARCHAR(MAX)'),1,2,'') AS roles
FROM CORE_Usuarios u;

GO
/****** Objeto: View [dbo].[vw_SSO_Usuarios] Fecha de script: 26/7/2026 17:41:55 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO

CREATE VIEW [dbo].[vw_SSO_Usuarios] AS
SELECT id_usuario, nombre_usuario, correo, nombre_completo, nivel_jerarquia, estado, tema_preferido, id_departamento
FROM CORE_Usuarios;

GO
/****** Objeto: View [dbo].[vw_Usuarios_Identidad] ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO

-- Identidad en vivo: si el usuario está ligado a un empleado de Talento
-- Humano (id_empleado_th), nombre/cédula se leen de ahí (cross-DB, misma
-- instancia) — nunca se duplican a mano. Si no, usa el respaldo local
-- (cuentas que no son empleado, ej. el superadmin de TI).
CREATE VIEW [dbo].[vw_Usuarios_Identidad] AS
SELECT
    u.id_usuario, u.nombre_usuario, u.correo, u.id_departamento,
    u.nivel_jerarquia, u.estado, u.id_empleado_th,
    COALESCE(NULLIF(LTRIM(RTRIM(e.nombres + ' ' + e.apellidos)), ''), u.nombre_completo) AS nombre_completo,
    COALESCE(e.cedula, u.cedula) AS cedula,
    COALESCE(e.ruta_foto, u.foto) AS foto
FROM CORE_Usuarios u
LEFT JOIN Talento_Humano.dbo.th_empleados e ON e.empleado_id = u.id_empleado_th;

GO
/****** Objeto: View [dbo].[vw_SSO_Menu] Fecha de script: 26/7/2026 17:41:55 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO

-- SSO menú via MOIS — JOIN correcto por 4-tupla, sin hardcode
CREATE VIEW [dbo].[vw_SSO_Menu] AS
SELECT DISTINCT
    u.id_usuario, mn.id_nodo, mn.id_modulo, mn.opcion, mn.items, mn.subitems,
    mn.descripcion, mn.url_ruta, mn.icono, mn.orden
FROM CORE_Usuarios u
JOIN CORE_Usuarios_Roles ur ON ur.id_usuario=u.id_usuario AND ur.estado=1
JOIN CORE_Roles r           ON r.id_rol=ur.id_rol AND r.estado=1
JOIN CORE_Permisos_Nodo pn  ON pn.id_rol=r.id_rol AND pn.acceso=1 AND pn.estado=1
JOIN CORE_Menu_Nodos mn     ON mn.id_modulo=pn.id_modulo AND mn.opcion=pn.opcion
                           AND mn.items=pn.items AND mn.subitems=pn.subitems AND mn.estado=1
WHERE u.estado=1;

GO
/****** Objeto: Table [dbo].[ACCESO_Auditoria] Fecha de script: 26/7/2026 17:41:55 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[ACCESO_Auditoria](
	[id_auditoria] [bigint] IDENTITY(1,1) NOT NULL,
	[id_usuario] [int] NULL,
	[operacion] [nvarchar](20) NOT NULL,
	[tabla] [nvarchar](100) NOT NULL,
	[id_registro] [bigint] NULL,
	[datos_antes] [nvarchar](max) NULL,
	[datos_despues] [nvarchar](max) NULL,
	[estado] [tinyint] NOT NULL,
	[fecha_registro] [datetime2](7) NOT NULL,
PRIMARY KEY CLUSTERED 
(
	[id_auditoria] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
) ON [PRIMARY] TEXTIMAGE_ON [PRIMARY]
GO
/****** Objeto: Table [dbo].[ACCESO_Vehiculos] Fecha de script: 26/7/2026 17:41:55 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[ACCESO_Vehiculos](
	[id_vehiculo] [int] IDENTITY(1,1) NOT NULL,
	[placa] [nvarchar](20) NOT NULL,
	[tipo] [nvarchar](50) NULL,
	[marca] [nvarchar](50) NULL,
	[modelo] [nvarchar](50) NULL,
	[color] [nvarchar](30) NULL,
	[id_visitante] [int] NULL,
	[estado] [tinyint] NOT NULL,
	[fecha_creacion] [datetime2](7) NOT NULL,
PRIMARY KEY CLUSTERED 
(
	[id_vehiculo] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY],
 CONSTRAINT [UQ_ACCESO_Veh_Placa] UNIQUE NONCLUSTERED 
(
	[placa] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
) ON [PRIMARY]
GO
/****** Objeto: Table [dbo].[ACCESO_Visitantes] Fecha de script: 26/7/2026 17:41:55 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[ACCESO_Visitantes](
	[id_visitante] [int] IDENTITY(1,1) NOT NULL,
	[cedula] [nvarchar](20) NOT NULL,
	[nombres] [nvarchar](100) NOT NULL,
	[apellidos] [nvarchar](100) NOT NULL,
	[empresa] [nvarchar](150) NULL,
	[telefono] [nvarchar](20) NULL,
	[correo] [nvarchar](150) NULL,
	[foto] [nvarchar](255) NULL,
	[estado] [tinyint] NOT NULL,
	[fecha_creacion] [datetime2](7) NOT NULL,
PRIMARY KEY CLUSTERED 
(
	[id_visitante] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY],
 CONSTRAINT [UQ_ACCESO_Visit_Cedula] UNIQUE NONCLUSTERED 
(
	[cedula] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
) ON [PRIMARY]
GO
/****** Objeto: Table [dbo].[CORE_Aplicaciones] Fecha de script: 26/7/2026 17:41:55 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[CORE_Aplicaciones](
	[id_app] [int] IDENTITY(1,1) NOT NULL,
	[codigo] [nvarchar](30) NOT NULL,
	[nombre] [nvarchar](100) NOT NULL,
	[api_key_hash] [varbinary](32) NOT NULL,
	[ip_permitidas] [nvarchar](500) NULL,
	[estado] [tinyint] NOT NULL,
	[fecha_creacion] [datetime2](7) NOT NULL,
	[fecha_expira] [datetime2](7) NULL,
	[creado_por] [nvarchar](50) NULL,
PRIMARY KEY CLUSTERED 
(
	[id_app] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY],
 CONSTRAINT [UQ_CoreApp_Codigo] UNIQUE NONCLUSTERED 
(
	[codigo] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
) ON [PRIMARY]
GO
/****** Objeto: Table [dbo].[CORE_Config] Fecha de script: 26/7/2026 17:41:55 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[CORE_Config](
	[id_config] [int] IDENTITY(1,1) NOT NULL,
	[modulo] [nvarchar](30) NOT NULL,
	[clave] [nvarchar](100) NOT NULL,
	[valor] [nvarchar](1000) NULL,
	[tipo] [nvarchar](20) NOT NULL,
	[descripcion] [nvarchar](255) NULL,
	[fecha_mod] [datetime2](7) NOT NULL,
	[estado] [tinyint] NOT NULL,
PRIMARY KEY CLUSTERED 
(
	[id_config] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY],
 CONSTRAINT [UQ_CoreConfig] UNIQUE NONCLUSTERED 
(
	[modulo] ASC,
	[clave] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
) ON [PRIMARY]
GO
/****** Objeto: Table [dbo].[CORE_Contrasenas_Hist] Fecha de script: 26/7/2026 17:41:55 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[CORE_Contrasenas_Hist](
	[id_hist] [int] IDENTITY(1,1) NOT NULL,
	[id_usuario] [int] NOT NULL,
	[hash_contrasena] [nvarchar](512) NOT NULL,
	[salt] [nvarchar](64) NOT NULL,
	[fecha_cambio] [datetime2](7) NOT NULL,
	[estado] [tinyint] NOT NULL,
PRIMARY KEY CLUSTERED 
(
	[id_hist] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
) ON [PRIMARY]
GO
/****** Objeto: Table [dbo].[CORE_Formularios] Fecha de script: 26/7/2026 17:41:55 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[CORE_Formularios](
	[id_formulario] [int] IDENTITY(1,1) NOT NULL,
	[codigo] [nvarchar](50) NOT NULL,
	[nombre] [nvarchar](150) NOT NULL,
	[modulo] [nvarchar](30) NOT NULL,
	[config_json] [nvarchar](max) NULL,
	[estado] [tinyint] NOT NULL,
	[fecha_creacion] [datetime2](7) NOT NULL,
PRIMARY KEY CLUSTERED 
(
	[id_formulario] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY],
 CONSTRAINT [UQ_CoreForm_Codigo] UNIQUE NONCLUSTERED 
(
	[codigo] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
) ON [PRIMARY] TEXTIMAGE_ON [PRIMARY]
GO
/****** Objeto: Table [dbo].[CORE_Formularios_Permisos] Fecha de script: 26/7/2026 17:41:55 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[CORE_Formularios_Permisos](
	[id_form_perm] [int] IDENTITY(1,1) NOT NULL,
	[id_rol] [int] NOT NULL,
	[id_formulario] [int] NOT NULL,
	[nivel_crud] [tinyint] NOT NULL,
	[estado] [tinyint] NOT NULL,
PRIMARY KEY CLUSTERED 
(
	[id_form_perm] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY],
 CONSTRAINT [UQ_CoreFormPerm] UNIQUE NONCLUSTERED 
(
	[id_rol] ASC,
	[id_formulario] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
) ON [PRIMARY]
GO
/****** Objeto: Table [dbo].[CORE_Notificaciones] Fecha de script: 26/7/2026 17:41:55 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[CORE_Notificaciones](
	[id_notif] [bigint] IDENTITY(1,1) NOT NULL,
	[id_usuario] [int] NOT NULL,
	[titulo] [nvarchar](150) NOT NULL,
	[mensaje] [nvarchar](500) NOT NULL,
	[tipo] [nvarchar](20) NOT NULL,
	[prioridad] [tinyint] NOT NULL,
	[leida] [bit] NOT NULL,
	[url_accion] [nvarchar](255) NULL,
	[fecha_creacion] [datetime2](7) NOT NULL,
	[fecha_lectura] [datetime2](7) NULL,
	[estado] [tinyint] NOT NULL,
PRIMARY KEY CLUSTERED 
(
	[id_notif] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
) ON [PRIMARY]
GO
/****** Objeto: Table [dbo].[CORE_Sesiones] Fecha de script: 26/7/2026 17:41:55 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[CORE_Sesiones](
	[id_sesion] [bigint] IDENTITY(1,1) NOT NULL,
	[id_usuario] [int] NOT NULL,
	[token] [nvarchar](128) NOT NULL,
	[ip_address] [nvarchar](45) NULL,
	[user_agent] [nvarchar](512) NULL,
	[fecha_inicio] [datetime2](7) NOT NULL,
	[fecha_expira] [datetime2](7) NOT NULL,
	[fecha_ultima_actividad] [datetime2](7) NOT NULL,
	[estado] [tinyint] NOT NULL,
	[fecha_revocacion] [datetime2](7) NULL,
PRIMARY KEY CLUSTERED 
(
	[id_sesion] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY],
 CONSTRAINT [UQ_CoreSes_Token] UNIQUE NONCLUSTERED 
(
	[token] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
) ON [PRIMARY]
GO
/****** Objeto: Table [dbo].[TH_Unidad_Map] Fecha de script: 26/7/2026 17:41:55 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[TH_Unidad_Map](
	[codigo_uorg] [varchar](20) NOT NULL,
	[id_departamento] [int] NOT NULL,
	[id_rol_director] [int] NOT NULL,
	[id_rol_analista] [int] NOT NULL,
PRIMARY KEY CLUSTERED 
(
	[codigo_uorg] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
) ON [PRIMARY]
GO
/****** Objeto: Index [IX_ACCESO_Reg_Fech] Fecha de script: 26/7/2026 17:41:55 ******/
CREATE NONCLUSTERED INDEX [IX_ACCESO_Reg_Fech] ON [dbo].[ACCESO_Registros]
(
	[fecha_hora] DESC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, SORT_IN_TEMPDB = OFF, DROP_EXISTING = OFF, ONLINE = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
GO
SET ANSI_PADDING ON
GO
/****** Objeto: Index [IX_ACCESO_Reg_Tipo] Fecha de script: 26/7/2026 17:41:55 ******/
CREATE NONCLUSTERED INDEX [IX_ACCESO_Reg_Tipo] ON [dbo].[ACCESO_Registros]
(
	[tipo_acceso] ASC,
	[fecha_hora] DESC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, SORT_IN_TEMPDB = OFF, DROP_EXISTING = OFF, ONLINE = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
GO
/****** Objeto: Index [IX_BIENES_Act_Dep] Fecha de script: 26/7/2026 17:41:55 ******/
/****** Objeto: Index [IX_BIENES_Act_Est] Fecha de script: 26/7/2026 17:41:55 ******/
/****** Objeto: Index [IX_BIT_Ev_Depto] Fecha de script: 26/7/2026 17:41:55 ******/
/****** Objeto: Index [IX_BIT_Ev_Estado] Fecha de script: 26/7/2026 17:41:55 ******/
/****** Objeto: Index [IX_CoreAud_Modulo] Fecha de script: 26/7/2026 17:41:55 ******/
CREATE NONCLUSTERED INDEX [IX_CoreAud_Modulo] ON [dbo].[CORE_Auditoria]
(
	[modulo] ASC,
	[fecha_registro] DESC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, SORT_IN_TEMPDB = OFF, DROP_EXISTING = OFF, ONLINE = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
GO
/****** Objeto: Index [IX_CoreAud_User] Fecha de script: 26/7/2026 17:41:55 ******/
CREATE NONCLUSTERED INDEX [IX_CoreAud_User] ON [dbo].[CORE_Auditoria]
(
	[id_usuario] ASC,
	[fecha_registro] DESC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, SORT_IN_TEMPDB = OFF, DROP_EXISTING = OFF, ONLINE = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
GO
/****** Objeto: Index [IX_CoreDepto_Padre] Fecha de script: 26/7/2026 17:41:55 ******/
CREATE NONCLUSTERED INDEX [IX_CoreDepto_Padre] ON [dbo].[CORE_Departamentos]
(
	[id_padre] ASC
)
WHERE ([id_padre] IS NOT NULL)
WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, SORT_IN_TEMPDB = OFF, DROP_EXISTING = OFF, ONLINE = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
GO
/****** Objeto: Index [IX_MN_Modulo] Fecha de script: 26/7/2026 17:41:55 ******/
CREATE NONCLUSTERED INDEX [IX_MN_Modulo] ON [dbo].[CORE_Menu_Nodos]
(
	[id_modulo] ASC,
	[opcion] ASC,
	[items] ASC,
	[subitems] ASC
)
WHERE ([estado]=(1))
WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, SORT_IN_TEMPDB = OFF, DROP_EXISTING = OFF, ONLINE = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
GO
/****** Objeto: Index [IX_CoreNotif_User] Fecha de script: 26/7/2026 17:41:55 ******/
CREATE NONCLUSTERED INDEX [IX_CoreNotif_User] ON [dbo].[CORE_Notificaciones]
(
	[id_usuario] ASC,
	[leida] ASC,
	[fecha_creacion] DESC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, SORT_IN_TEMPDB = OFF, DROP_EXISTING = OFF, ONLINE = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
GO
/****** Objeto: Index [IX_PN_Nodo] Fecha de script: 26/7/2026 17:41:55 ******/
CREATE NONCLUSTERED INDEX [IX_PN_Nodo] ON [dbo].[CORE_Permisos_Nodo]
(
	[id_modulo] ASC,
	[opcion] ASC,
	[items] ASC,
	[subitems] ASC
)
WHERE ([acceso]=(1) AND [estado]=(1))
WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, SORT_IN_TEMPDB = OFF, DROP_EXISTING = OFF, ONLINE = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
GO
/****** Objeto: Index [IX_PN_Rol] Fecha de script: 26/7/2026 17:41:55 ******/
CREATE NONCLUSTERED INDEX [IX_PN_Rol] ON [dbo].[CORE_Permisos_Nodo]
(
	[id_rol] ASC
)
WHERE ([acceso]=(1) AND [estado]=(1))
WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, SORT_IN_TEMPDB = OFF, DROP_EXISTING = OFF, ONLINE = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
GO
/****** Objeto: Index [IX_CoreSes_Expira] Fecha de script: 26/7/2026 17:41:55 ******/
CREATE NONCLUSTERED INDEX [IX_CoreSes_Expira] ON [dbo].[CORE_Sesiones]
(
	[fecha_expira] ASC
)
WHERE ([estado]=(1))
WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, SORT_IN_TEMPDB = OFF, DROP_EXISTING = OFF, ONLINE = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
GO
SET ANSI_PADDING ON
GO
/****** Objeto: Index [IX_CoreSes_Token] Fecha de script: 26/7/2026 17:41:55 ******/
CREATE NONCLUSTERED INDEX [IX_CoreSes_Token] ON [dbo].[CORE_Sesiones]
(
	[token] ASC
)
WHERE ([estado]=(1))
WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, SORT_IN_TEMPDB = OFF, DROP_EXISTING = OFF, ONLINE = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
GO
/****** Objeto: Index [IX_CoreSes_User] Fecha de script: 26/7/2026 17:41:55 ******/
CREATE NONCLUSTERED INDEX [IX_CoreSes_User] ON [dbo].[CORE_Sesiones]
(
	[id_usuario] ASC
)
WHERE ([estado]=(1))
WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, SORT_IN_TEMPDB = OFF, DROP_EXISTING = OFF, ONLINE = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
GO
/****** Objeto: Index [IX_CoreU_Bloqueo] Fecha de script: 26/7/2026 17:41:55 ******/
CREATE NONCLUSTERED INDEX [IX_CoreU_Bloqueo] ON [dbo].[CORE_Usuarios]
(
	[fecha_bloqueo] ASC
)
WHERE ([fecha_bloqueo] IS NOT NULL)
WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, SORT_IN_TEMPDB = OFF, DROP_EXISTING = OFF, ONLINE = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
GO
SET ANSI_PADDING ON
GO
/****** Objeto: Index [IX_CoreU_Correo] Fecha de script: 26/7/2026 17:41:55 ******/
CREATE NONCLUSTERED INDEX [IX_CoreU_Correo] ON [dbo].[CORE_Usuarios]
(
	[correo] ASC
)
WHERE ([estado]=(1))
WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, SORT_IN_TEMPDB = OFF, DROP_EXISTING = OFF, ONLINE = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
GO
/****** Objeto: Index [IX_CoreU_Depto] Fecha de script: 26/7/2026 17:41:55 ******/
CREATE NONCLUSTERED INDEX [IX_CoreU_Depto] ON [dbo].[CORE_Usuarios]
(
	[id_departamento] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, SORT_IN_TEMPDB = OFF, DROP_EXISTING = OFF, ONLINE = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
GO
SET ANSI_PADDING ON
GO
/****** Objeto: Index [UQ_CoreU_Cedula] Fecha de script: 26/7/2026 17:41:55 ******/
CREATE UNIQUE NONCLUSTERED INDEX [UQ_CoreU_Cedula] ON [dbo].[CORE_Usuarios]
(
	[cedula] ASC
)
WHERE ([cedula] IS NOT NULL)
WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, SORT_IN_TEMPDB = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
GO
SET ANSI_PADDING ON
GO
/****** Objeto: Index [IX_TH_Cont_Emp] Fecha de script: 26/7/2026 17:41:55 ******/
/****** Objeto: Index [IX_TH_Cont_Vencer] Fecha de script: 26/7/2026 17:41:55 ******/
/****** Objeto: Index [IX_TH_Emp_Depto] Fecha de script: 26/7/2026 17:41:55 ******/
/****** Objeto: Index [IX_TH_Emp_Estado] Fecha de script: 26/7/2026 17:41:55 ******/
ALTER TABLE [dbo].[ACCESO_Auditoria] ADD  DEFAULT ((1)) FOR [estado]
GO
ALTER TABLE [dbo].[ACCESO_Auditoria] ADD  DEFAULT (getdate()) FOR [fecha_registro]
GO
ALTER TABLE [dbo].[ACCESO_Registros] ADD  DEFAULT ('Activo') FOR [estado_registro]
GO
ALTER TABLE [dbo].[ACCESO_Registros] ADD  DEFAULT ((1)) FOR [estado]
GO
ALTER TABLE [dbo].[ACCESO_Registros] ADD  DEFAULT (getdate()) FOR [fecha_hora]
GO
ALTER TABLE [dbo].[ACCESO_Vehiculos] ADD  DEFAULT ((1)) FOR [estado]
GO
ALTER TABLE [dbo].[ACCESO_Vehiculos] ADD  DEFAULT (getdate()) FOR [fecha_creacion]
GO
ALTER TABLE [dbo].[ACCESO_Visitantes] ADD  DEFAULT ((1)) FOR [estado]
GO
ALTER TABLE [dbo].[ACCESO_Visitantes] ADD  DEFAULT (getdate()) FOR [fecha_creacion]
GO
ALTER TABLE [dbo].[CORE_Aplicaciones] ADD  CONSTRAINT [DF_CoreApp_Estado]  DEFAULT ((1)) FOR [estado]
GO
ALTER TABLE [dbo].[CORE_Aplicaciones] ADD  CONSTRAINT [DF_CoreApp_Creacion]  DEFAULT (getdate()) FOR [fecha_creacion]
GO
ALTER TABLE [dbo].[CORE_Auditoria] ADD  DEFAULT ('CORE') FOR [modulo]
GO
ALTER TABLE [dbo].[CORE_Auditoria] ADD  DEFAULT ('EXITO') FOR [resultado]
GO
ALTER TABLE [dbo].[CORE_Auditoria] ADD  DEFAULT (getdate()) FOR [fecha_registro]
GO
ALTER TABLE [dbo].[CORE_Auditoria] ADD  DEFAULT ((1)) FOR [estado]
GO
ALTER TABLE [dbo].[CORE_Config] ADD  DEFAULT ('CORE') FOR [modulo]
GO
ALTER TABLE [dbo].[CORE_Config] ADD  DEFAULT ('string') FOR [tipo]
GO
ALTER TABLE [dbo].[CORE_Config] ADD  DEFAULT (getdate()) FOR [fecha_mod]
GO
ALTER TABLE [dbo].[CORE_Config] ADD  DEFAULT ((1)) FOR [estado]
GO
ALTER TABLE [dbo].[CORE_Contrasenas_Hist] ADD  DEFAULT ('') FOR [salt]
GO
ALTER TABLE [dbo].[CORE_Contrasenas_Hist] ADD  DEFAULT (getdate()) FOR [fecha_cambio]
GO
ALTER TABLE [dbo].[CORE_Contrasenas_Hist] ADD  DEFAULT ((1)) FOR [estado]
GO
ALTER TABLE [dbo].[CORE_Departamentos] ADD  DEFAULT ((0)) FOR [nivel]
GO
ALTER TABLE [dbo].[CORE_Departamentos] ADD  DEFAULT ((1)) FOR [estado]
GO
ALTER TABLE [dbo].[CORE_Departamentos] ADD  DEFAULT ('fa-building') FOR [icono]
GO
ALTER TABLE [dbo].[CORE_Departamentos] ADD  DEFAULT ('#0056b3') FOR [color_badge]
GO
ALTER TABLE [dbo].[CORE_Departamentos] ADD  DEFAULT (getdate()) FOR [fecha_creacion]
GO
ALTER TABLE [dbo].[CORE_Formularios] ADD  DEFAULT ((1)) FOR [estado]
GO
ALTER TABLE [dbo].[CORE_Formularios] ADD  DEFAULT (getdate()) FOR [fecha_creacion]
GO
ALTER TABLE [dbo].[CORE_Formularios_Permisos] ADD  DEFAULT ((1)) FOR [nivel_crud]
GO
ALTER TABLE [dbo].[CORE_Formularios_Permisos] ADD  DEFAULT ((1)) FOR [estado]
GO
ALTER TABLE [dbo].[CORE_Menu_Nodos] ADD  DEFAULT ((0)) FOR [opcion]
GO
ALTER TABLE [dbo].[CORE_Menu_Nodos] ADD  DEFAULT ((0)) FOR [items]
GO
ALTER TABLE [dbo].[CORE_Menu_Nodos] ADD  DEFAULT ((0)) FOR [subitems]
GO
ALTER TABLE [dbo].[CORE_Menu_Nodos] ADD  DEFAULT ('fa-circle') FOR [icono]
GO
ALTER TABLE [dbo].[CORE_Menu_Nodos] ADD  DEFAULT ((0)) FOR [orden]
GO
ALTER TABLE [dbo].[CORE_Menu_Nodos] ADD  DEFAULT ((0)) FOR [requiere_mfa]
GO
ALTER TABLE [dbo].[CORE_Menu_Nodos] ADD  DEFAULT ((1)) FOR [target_spa]
GO
ALTER TABLE [dbo].[CORE_Menu_Nodos] ADD  DEFAULT ((1)) FOR [estado]
GO
ALTER TABLE [dbo].[CORE_Menu_Nodos] ADD  DEFAULT (getdate()) FOR [fecha_creacion]
GO
ALTER TABLE [dbo].[CORE_Notificaciones] ADD  DEFAULT ('info') FOR [tipo]
GO
ALTER TABLE [dbo].[CORE_Notificaciones] ADD  DEFAULT ((2)) FOR [prioridad]
GO
ALTER TABLE [dbo].[CORE_Notificaciones] ADD  DEFAULT ((0)) FOR [leida]
GO
ALTER TABLE [dbo].[CORE_Notificaciones] ADD  DEFAULT (getdate()) FOR [fecha_creacion]
GO
ALTER TABLE [dbo].[CORE_Notificaciones] ADD  DEFAULT ((1)) FOR [estado]
GO
ALTER TABLE [dbo].[CORE_Permisos_Nodo] ADD  DEFAULT ((0)) FOR [opcion]
GO
ALTER TABLE [dbo].[CORE_Permisos_Nodo] ADD  DEFAULT ((0)) FOR [items]
GO
ALTER TABLE [dbo].[CORE_Permisos_Nodo] ADD  DEFAULT ((0)) FOR [subitems]
GO
ALTER TABLE [dbo].[CORE_Permisos_Nodo] ADD  DEFAULT ((1)) FOR [nivel_crud]
GO
ALTER TABLE [dbo].[CORE_Permisos_Nodo] ADD  DEFAULT ((1)) FOR [acceso]
GO
ALTER TABLE [dbo].[CORE_Permisos_Nodo] ADD  DEFAULT ((1)) FOR [estado]
GO
ALTER TABLE [dbo].[CORE_Permisos_Nodo] ADD  DEFAULT (getdate()) FOR [fecha_asignacion]
GO
ALTER TABLE [dbo].[CORE_Roles] ADD  DEFAULT ((0)) FOR [nivel_jerarquia]
GO
ALTER TABLE [dbo].[CORE_Roles] ADD  DEFAULT ((1)) FOR [estado]
GO
ALTER TABLE [dbo].[CORE_Roles] ADD  DEFAULT (getdate()) FOR [fecha_creacion]
GO
ALTER TABLE [dbo].[CORE_Sesiones] ADD  DEFAULT (getdate()) FOR [fecha_inicio]
GO
ALTER TABLE [dbo].[CORE_Sesiones] ADD  DEFAULT (getdate()) FOR [fecha_ultima_actividad]
GO
ALTER TABLE [dbo].[CORE_Sesiones] ADD  DEFAULT ((1)) FOR [estado]
GO
ALTER TABLE [dbo].[CORE_Usuarios] ADD  DEFAULT ('') FOR [salt]
GO
ALTER TABLE [dbo].[CORE_Usuarios] ADD  DEFAULT ((0)) FOR [nivel_jerarquia]
GO
ALTER TABLE [dbo].[CORE_Usuarios] ADD  DEFAULT ((1)) FOR [estado]
GO
ALTER TABLE [dbo].[CORE_Usuarios] ADD  DEFAULT ((0)) FOR [requiere_mfa]
GO
ALTER TABLE [dbo].[CORE_Usuarios] ADD  DEFAULT ((0)) FOR [requiere_cambio_pass]
GO
ALTER TABLE [dbo].[CORE_Usuarios] ADD  DEFAULT ((0)) FOR [intentos_fallidos]
GO
ALTER TABLE [dbo].[CORE_Usuarios] ADD  DEFAULT ((30)) FOR [minutos_bloqueo]
GO
ALTER TABLE [dbo].[CORE_Usuarios] ADD  DEFAULT ('light') FOR [tema_preferido]
GO
ALTER TABLE [dbo].[CORE_Usuarios] ADD  DEFAULT (getdate()) FOR [fecha_creacion]
GO
ALTER TABLE [dbo].[CORE_Usuarios_Roles] ADD  DEFAULT (getdate()) FOR [fecha_asignacion]
GO
ALTER TABLE [dbo].[CORE_Usuarios_Roles] ADD  DEFAULT ((1)) FOR [estado]
GO
ALTER TABLE [dbo].[ACCESO_Auditoria]  WITH CHECK ADD  CONSTRAINT [FK_ACCESO_Aud_User] FOREIGN KEY([id_usuario])
REFERENCES [dbo].[CORE_Usuarios] ([id_usuario])
ON DELETE SET NULL
GO
ALTER TABLE [dbo].[ACCESO_Auditoria] CHECK CONSTRAINT [FK_ACCESO_Aud_User]
GO
ALTER TABLE [dbo].[ACCESO_Registros]  WITH CHECK ADD  CONSTRAINT [FK_ACCESO_Reg_Depto] FOREIGN KEY([id_departamento])
REFERENCES [dbo].[CORE_Departamentos] ([id_departamento])
ON DELETE SET NULL
GO
ALTER TABLE [dbo].[ACCESO_Registros] CHECK CONSTRAINT [FK_ACCESO_Reg_Depto]
GO
ALTER TABLE [dbo].[ACCESO_Registros]  WITH CHECK ADD  CONSTRAINT [FK_ACCESO_Reg_Oper] FOREIGN KEY([id_operador])
REFERENCES [dbo].[CORE_Usuarios] ([id_usuario])
ON DELETE SET NULL
GO
ALTER TABLE [dbo].[ACCESO_Registros] CHECK CONSTRAINT [FK_ACCESO_Reg_Oper]
GO
ALTER TABLE [dbo].[ACCESO_Registros]  WITH CHECK ADD  CONSTRAINT [FK_ACCESO_Reg_Veh] FOREIGN KEY([id_vehiculo])
REFERENCES [dbo].[ACCESO_Vehiculos] ([id_vehiculo])
ON DELETE SET NULL
GO
ALTER TABLE [dbo].[ACCESO_Registros] CHECK CONSTRAINT [FK_ACCESO_Reg_Veh]
GO
ALTER TABLE [dbo].[ACCESO_Registros]  WITH CHECK ADD  CONSTRAINT [FK_ACCESO_Reg_Visit] FOREIGN KEY([id_visitante])
REFERENCES [dbo].[ACCESO_Visitantes] ([id_visitante])
ON DELETE SET NULL
GO
ALTER TABLE [dbo].[ACCESO_Registros] CHECK CONSTRAINT [FK_ACCESO_Reg_Visit]
GO
ALTER TABLE [dbo].[ACCESO_Vehiculos]  WITH CHECK ADD  CONSTRAINT [FK_ACCESO_Veh_Visit] FOREIGN KEY([id_visitante])
REFERENCES [dbo].[ACCESO_Visitantes] ([id_visitante])
ON DELETE SET NULL
GO
ALTER TABLE [dbo].[ACCESO_Vehiculos] CHECK CONSTRAINT [FK_ACCESO_Veh_Visit]
GO
ALTER TABLE [dbo].[CORE_Auditoria]  WITH CHECK ADD  CONSTRAINT [FK_CoreAud_Usuario] FOREIGN KEY([id_usuario])
REFERENCES [dbo].[CORE_Usuarios] ([id_usuario])
ON DELETE SET NULL
GO
ALTER TABLE [dbo].[CORE_Auditoria] CHECK CONSTRAINT [FK_CoreAud_Usuario]
GO
ALTER TABLE [dbo].[CORE_Contrasenas_Hist]  WITH CHECK ADD  CONSTRAINT [FK_CorePassHist_Usuario] FOREIGN KEY([id_usuario])
REFERENCES [dbo].[CORE_Usuarios] ([id_usuario])
ON DELETE CASCADE
GO
ALTER TABLE [dbo].[CORE_Contrasenas_Hist] CHECK CONSTRAINT [FK_CorePassHist_Usuario]
GO
ALTER TABLE [dbo].[CORE_Departamentos]  WITH CHECK ADD  CONSTRAINT [FK_CoreDepto_Padre] FOREIGN KEY([id_padre])
REFERENCES [dbo].[CORE_Departamentos] ([id_departamento])
GO
ALTER TABLE [dbo].[CORE_Departamentos] CHECK CONSTRAINT [FK_CoreDepto_Padre]
GO
ALTER TABLE [dbo].[CORE_Formularios_Permisos]  WITH CHECK ADD  CONSTRAINT [FK_CoreFormPerm_Form] FOREIGN KEY([id_formulario])
REFERENCES [dbo].[CORE_Formularios] ([id_formulario])
ON DELETE CASCADE
GO
ALTER TABLE [dbo].[CORE_Formularios_Permisos] CHECK CONSTRAINT [FK_CoreFormPerm_Form]
GO
ALTER TABLE [dbo].[CORE_Formularios_Permisos]  WITH CHECK ADD  CONSTRAINT [FK_CoreFormPerm_Rol] FOREIGN KEY([id_rol])
REFERENCES [dbo].[CORE_Roles] ([id_rol])
ON DELETE CASCADE
GO
ALTER TABLE [dbo].[CORE_Formularios_Permisos] CHECK CONSTRAINT [FK_CoreFormPerm_Rol]
GO
ALTER TABLE [dbo].[CORE_Notificaciones]  WITH CHECK ADD  CONSTRAINT [FK_CoreNotif_Usuario] FOREIGN KEY([id_usuario])
REFERENCES [dbo].[CORE_Usuarios] ([id_usuario])
ON DELETE CASCADE
GO
ALTER TABLE [dbo].[CORE_Notificaciones] CHECK CONSTRAINT [FK_CoreNotif_Usuario]
GO
ALTER TABLE [dbo].[CORE_Permisos_Nodo]  WITH CHECK ADD  CONSTRAINT [FK_PN_AsigPor] FOREIGN KEY([asignado_por])
REFERENCES [dbo].[CORE_Usuarios] ([id_usuario])
GO
ALTER TABLE [dbo].[CORE_Permisos_Nodo] CHECK CONSTRAINT [FK_PN_AsigPor]
GO
ALTER TABLE [dbo].[CORE_Permisos_Nodo]  WITH CHECK ADD  CONSTRAINT [FK_PN_Nodo] FOREIGN KEY([id_modulo], [opcion], [items], [subitems])
REFERENCES [dbo].[CORE_Menu_Nodos] ([id_modulo], [opcion], [items], [subitems])
GO
ALTER TABLE [dbo].[CORE_Permisos_Nodo] CHECK CONSTRAINT [FK_PN_Nodo]
GO
ALTER TABLE [dbo].[CORE_Permisos_Nodo]  WITH CHECK ADD  CONSTRAINT [FK_PN_Rol] FOREIGN KEY([id_rol])
REFERENCES [dbo].[CORE_Roles] ([id_rol])
ON DELETE CASCADE
GO
ALTER TABLE [dbo].[CORE_Permisos_Nodo] CHECK CONSTRAINT [FK_PN_Rol]
GO
ALTER TABLE [dbo].[CORE_Roles]  WITH CHECK ADD  CONSTRAINT [FK_CoreRol_Depto] FOREIGN KEY([id_departamento])
REFERENCES [dbo].[CORE_Departamentos] ([id_departamento])
ON DELETE SET NULL
GO
ALTER TABLE [dbo].[CORE_Roles] CHECK CONSTRAINT [FK_CoreRol_Depto]
GO
ALTER TABLE [dbo].[CORE_Sesiones]  WITH CHECK ADD  CONSTRAINT [FK_CoreSes_Usuario] FOREIGN KEY([id_usuario])
REFERENCES [dbo].[CORE_Usuarios] ([id_usuario])
ON DELETE CASCADE
GO
ALTER TABLE [dbo].[CORE_Sesiones] CHECK CONSTRAINT [FK_CoreSes_Usuario]
GO
ALTER TABLE [dbo].[CORE_Usuarios]  WITH CHECK ADD  CONSTRAINT [FK_CoreU_Depto] FOREIGN KEY([id_departamento])
REFERENCES [dbo].[CORE_Departamentos] ([id_departamento])
ON DELETE SET NULL
GO
ALTER TABLE [dbo].[CORE_Usuarios] CHECK CONSTRAINT [FK_CoreU_Depto]
GO
ALTER TABLE [dbo].[CORE_Usuarios_Roles]  WITH CHECK ADD  CONSTRAINT [FK_CoreUR_AsigPor] FOREIGN KEY([asignado_por])
REFERENCES [dbo].[CORE_Usuarios] ([id_usuario])
GO
ALTER TABLE [dbo].[CORE_Usuarios_Roles] CHECK CONSTRAINT [FK_CoreUR_AsigPor]
GO
ALTER TABLE [dbo].[CORE_Usuarios_Roles]  WITH CHECK ADD  CONSTRAINT [FK_CoreUR_Rol] FOREIGN KEY([id_rol])
REFERENCES [dbo].[CORE_Roles] ([id_rol])
ON DELETE CASCADE
GO
ALTER TABLE [dbo].[CORE_Usuarios_Roles] CHECK CONSTRAINT [FK_CoreUR_Rol]
GO
ALTER TABLE [dbo].[CORE_Usuarios_Roles]  WITH CHECK ADD  CONSTRAINT [FK_CoreUR_Usuario] FOREIGN KEY([id_usuario])
REFERENCES [dbo].[CORE_Usuarios] ([id_usuario])
ON DELETE CASCADE
GO
ALTER TABLE [dbo].[CORE_Usuarios_Roles] CHECK CONSTRAINT [FK_CoreUR_Usuario]
GO
ALTER TABLE [dbo].[ACCESO_Auditoria]  WITH CHECK ADD  CONSTRAINT [CK_ACCESO_Aud_Estado] CHECK  (([estado]=(1) OR [estado]=(0)))
GO
ALTER TABLE [dbo].[ACCESO_Auditoria] CHECK CONSTRAINT [CK_ACCESO_Aud_Estado]
GO
ALTER TABLE [dbo].[ACCESO_Registros]  WITH CHECK ADD  CONSTRAINT [CK_ACCESO_Reg_Estado] CHECK  (([estado]=(1) OR [estado]=(0)))
GO
ALTER TABLE [dbo].[ACCESO_Registros] CHECK CONSTRAINT [CK_ACCESO_Reg_Estado]
GO
ALTER TABLE [dbo].[ACCESO_Registros]  WITH CHECK ADD  CONSTRAINT [CK_ACCESO_Reg_EstReg] CHECK  (([estado_registro]='Finalizado' OR [estado_registro]='Activo'))
GO
ALTER TABLE [dbo].[ACCESO_Registros] CHECK CONSTRAINT [CK_ACCESO_Reg_EstReg]
GO
ALTER TABLE [dbo].[ACCESO_Registros]  WITH CHECK ADD  CONSTRAINT [CK_ACCESO_Reg_Tipo] CHECK  (([tipo_acceso]='Salida' OR [tipo_acceso]='Entrada'))
GO
ALTER TABLE [dbo].[ACCESO_Registros] CHECK CONSTRAINT [CK_ACCESO_Reg_Tipo]
GO
ALTER TABLE [dbo].[ACCESO_Vehiculos]  WITH CHECK ADD  CONSTRAINT [CK_ACCESO_Veh_Estado] CHECK  (([estado]=(1) OR [estado]=(0)))
GO
ALTER TABLE [dbo].[ACCESO_Vehiculos] CHECK CONSTRAINT [CK_ACCESO_Veh_Estado]
GO
ALTER TABLE [dbo].[ACCESO_Visitantes]  WITH CHECK ADD  CONSTRAINT [CK_ACCESO_Visit_Estado] CHECK  (([estado]=(1) OR [estado]=(0)))
GO
ALTER TABLE [dbo].[ACCESO_Visitantes] CHECK CONSTRAINT [CK_ACCESO_Visit_Estado]
GO
ALTER TABLE [dbo].[CORE_Aplicaciones]  WITH CHECK ADD  CONSTRAINT [CK_CoreApp_Estado] CHECK  (([estado]=(1) OR [estado]=(0)))
GO
ALTER TABLE [dbo].[CORE_Aplicaciones] CHECK CONSTRAINT [CK_CoreApp_Estado]
GO
ALTER TABLE [dbo].[CORE_Auditoria]  WITH CHECK ADD  CONSTRAINT [CK_CoreAud_Estado] CHECK  (([estado]=(1) OR [estado]=(0)))
GO
ALTER TABLE [dbo].[CORE_Auditoria] CHECK CONSTRAINT [CK_CoreAud_Estado]
GO
ALTER TABLE [dbo].[CORE_Config]  WITH CHECK ADD  CONSTRAINT [CK_CoreCfg_Estado] CHECK  (([estado]=(1) OR [estado]=(0)))
GO
ALTER TABLE [dbo].[CORE_Config] CHECK CONSTRAINT [CK_CoreCfg_Estado]
GO
ALTER TABLE [dbo].[CORE_Config]  WITH CHECK ADD  CONSTRAINT [CK_CoreCfg_Tipo] CHECK  (([tipo]='json' OR [tipo]='bool' OR [tipo]='int' OR [tipo]='string'))
GO
ALTER TABLE [dbo].[CORE_Config] CHECK CONSTRAINT [CK_CoreCfg_Tipo]
GO
ALTER TABLE [dbo].[CORE_Contrasenas_Hist]  WITH CHECK ADD  CONSTRAINT [CK_CorePH_Estado] CHECK  (([estado]=(1) OR [estado]=(0)))
GO
ALTER TABLE [dbo].[CORE_Contrasenas_Hist] CHECK CONSTRAINT [CK_CorePH_Estado]
GO
ALTER TABLE [dbo].[CORE_Departamentos]  WITH CHECK ADD  CONSTRAINT [CK_CoreDep_Estado] CHECK  (([estado]=(1) OR [estado]=(0)))
GO
ALTER TABLE [dbo].[CORE_Departamentos] CHECK CONSTRAINT [CK_CoreDep_Estado]
GO
ALTER TABLE [dbo].[CORE_Departamentos]  WITH CHECK ADD  CONSTRAINT [CK_CoreDep_Nivel] CHECK  (([nivel]>=(0) AND [nivel]<=(3)))
GO
ALTER TABLE [dbo].[CORE_Departamentos] CHECK CONSTRAINT [CK_CoreDep_Nivel]
GO
ALTER TABLE [dbo].[CORE_Formularios]  WITH CHECK ADD  CONSTRAINT [CK_CoreFrm_Estado] CHECK  (([estado]=(1) OR [estado]=(0)))
GO
ALTER TABLE [dbo].[CORE_Formularios] CHECK CONSTRAINT [CK_CoreFrm_Estado]
GO
ALTER TABLE [dbo].[CORE_Formularios_Permisos]  WITH CHECK ADD  CONSTRAINT [CK_CoreFrmP_CRUD] CHECK  (([nivel_crud]>=(1) AND [nivel_crud]<=(4)))
GO
ALTER TABLE [dbo].[CORE_Formularios_Permisos] CHECK CONSTRAINT [CK_CoreFrmP_CRUD]
GO
ALTER TABLE [dbo].[CORE_Formularios_Permisos]  WITH CHECK ADD  CONSTRAINT [CK_CoreFrmP_Estado] CHECK  (([estado]=(1) OR [estado]=(0)))
GO
ALTER TABLE [dbo].[CORE_Formularios_Permisos] CHECK CONSTRAINT [CK_CoreFrmP_Estado]
GO
ALTER TABLE [dbo].[CORE_Menu_Nodos]  WITH CHECK ADD  CONSTRAINT [CK_MN_Estado] CHECK  (([estado]=(1) OR [estado]=(0)))
GO
ALTER TABLE [dbo].[CORE_Menu_Nodos] CHECK CONSTRAINT [CK_MN_Estado]
GO
ALTER TABLE [dbo].[CORE_Notificaciones]  WITH CHECK ADD  CONSTRAINT [CK_CoreNtf_Estado] CHECK  (([estado]=(1) OR [estado]=(0)))
GO
ALTER TABLE [dbo].[CORE_Notificaciones] CHECK CONSTRAINT [CK_CoreNtf_Estado]
GO
ALTER TABLE [dbo].[CORE_Notificaciones]  WITH CHECK ADD  CONSTRAINT [CK_CoreNtf_Prior] CHECK  (([prioridad]>=(1) AND [prioridad]<=(3)))
GO
ALTER TABLE [dbo].[CORE_Notificaciones] CHECK CONSTRAINT [CK_CoreNtf_Prior]
GO
ALTER TABLE [dbo].[CORE_Notificaciones]  WITH CHECK ADD  CONSTRAINT [CK_CoreNtf_Tipo] CHECK  (([tipo]='danger' OR [tipo]='warning' OR [tipo]='success' OR [tipo]='info'))
GO
ALTER TABLE [dbo].[CORE_Notificaciones] CHECK CONSTRAINT [CK_CoreNtf_Tipo]
GO
ALTER TABLE [dbo].[CORE_Permisos_Nodo]  WITH CHECK ADD  CONSTRAINT [CK_PN_Acceso] CHECK  (([acceso]=(1) OR [acceso]=(0)))
GO
ALTER TABLE [dbo].[CORE_Permisos_Nodo] CHECK CONSTRAINT [CK_PN_Acceso]
GO
ALTER TABLE [dbo].[CORE_Permisos_Nodo]  WITH CHECK ADD  CONSTRAINT [CK_PN_CRUD] CHECK  (([nivel_crud]>=(1) AND [nivel_crud]<=(4)))
GO
ALTER TABLE [dbo].[CORE_Permisos_Nodo] CHECK CONSTRAINT [CK_PN_CRUD]
GO
ALTER TABLE [dbo].[CORE_Permisos_Nodo]  WITH CHECK ADD  CONSTRAINT [CK_PN_Estado] CHECK  (([estado]=(1) OR [estado]=(0)))
GO
ALTER TABLE [dbo].[CORE_Permisos_Nodo] CHECK CONSTRAINT [CK_PN_Estado]
GO
ALTER TABLE [dbo].[CORE_Roles]  WITH CHECK ADD  CONSTRAINT [CK_CoreRol_Estado] CHECK  (([estado]=(1) OR [estado]=(0)))
GO
ALTER TABLE [dbo].[CORE_Roles] CHECK CONSTRAINT [CK_CoreRol_Estado]
GO
ALTER TABLE [dbo].[CORE_Sesiones]  WITH CHECK ADD  CONSTRAINT [CK_CoreSes_Estado] CHECK  (([estado]=(1) OR [estado]=(0)))
GO
ALTER TABLE [dbo].[CORE_Sesiones] CHECK CONSTRAINT [CK_CoreSes_Estado]
GO
ALTER TABLE [dbo].[CORE_Usuarios]  WITH CHECK ADD  CONSTRAINT [CK_CoreU_Estado] CHECK  (([estado]=(1) OR [estado]=(0)))
GO
ALTER TABLE [dbo].[CORE_Usuarios] CHECK CONSTRAINT [CK_CoreU_Estado]
GO
ALTER TABLE [dbo].[CORE_Usuarios]  WITH CHECK ADD  CONSTRAINT [CK_CoreU_Nivel] CHECK  (([nivel_jerarquia]>=(0) AND [nivel_jerarquia]<=(4)))
GO
ALTER TABLE [dbo].[CORE_Usuarios] CHECK CONSTRAINT [CK_CoreU_Nivel]
GO
ALTER TABLE [dbo].[CORE_Usuarios]  WITH CHECK ADD  CONSTRAINT [CK_CoreU_Tema] CHECK  (([tema_preferido]='corporate' OR [tema_preferido]='dark' OR [tema_preferido]='light'))
GO
ALTER TABLE [dbo].[CORE_Usuarios] CHECK CONSTRAINT [CK_CoreU_Tema]
GO
ALTER TABLE [dbo].[CORE_Usuarios_Roles]  WITH CHECK ADD  CONSTRAINT [CK_CoreUR_Estado] CHECK  (([estado]=(1) OR [estado]=(0)))
GO
ALTER TABLE [dbo].[CORE_Usuarios_Roles] CHECK CONSTRAINT [CK_CoreUR_Estado]
GO
/****** Objeto: StoredProcedure [dbo].[sp_CambiarContrasena] Fecha de script: 26/7/2026 17:41:55 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO

-- SP4: Cambiar contraseña con historial
-- Verificar reuso (password_verify vs historial) ANTES de llamar este SP — bcrypt no es determinístico en SQL
CREATE PROCEDURE [dbo].[sp_CambiarContrasena]
    @id_usuario    INT,
    @nuevo_hash    NVARCHAR(512),
    @nuevo_salt    NVARCHAR(64) = '',
    @max_historial TINYINT      = 5
AS
BEGIN
    SET NOCOUNT ON;
    DECLARE @hact NVARCHAR(512), @sact NVARCHAR(64);
    SELECT @hact=hash_contrasena, @sact=salt FROM CORE_Usuarios WHERE id_usuario=@id_usuario;
    IF @hact IS NOT NULL
        INSERT INTO CORE_Contrasenas_Hist(id_usuario,hash_contrasena,salt) VALUES(@id_usuario,@hact,@sact);
    DELETE FROM CORE_Contrasenas_Hist WHERE id_usuario=@id_usuario
    AND id_hist NOT IN (
        SELECT TOP (@max_historial) id_hist FROM CORE_Contrasenas_Hist
        WHERE id_usuario=@id_usuario ORDER BY fecha_cambio DESC
    );
    UPDATE CORE_Usuarios SET hash_contrasena=@nuevo_hash, salt=@nuevo_salt,
        requiere_cambio_pass=0, fecha_modificacion=GETDATE() WHERE id_usuario=@id_usuario;
    INSERT INTO CORE_Auditoria(id_usuario,modulo,operacion,resultado)
    VALUES(@id_usuario,'CORE','CAMBIO_PASS','EXITO');
END;

GO
/****** Objeto: StoredProcedure [dbo].[sp_GetKPIs_Ejecutivo] Fecha de script: 26/7/2026 17:41:55 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO

-- SP8: KPIs Dashboard Ejecutivo. Fuentes reales cross-DB (misma instancia):
-- Talento_Humano, inventario, PortuariaDemo — ver DashboardModel.php (mismo patrón).
CREATE PROCEDURE [dbo].[sp_GetKPIs_Ejecutivo]
AS
BEGIN
    SET NOCOUNT ON;
    SELECT
        (SELECT COUNT(*) FROM Talento_Humano.dbo.th_empleados WHERE estado=1)                                     AS empleados_activos,
        (SELECT COUNT(*) FROM inventario.dbo.inv_inventario WHERE activo=1 AND estado_id=111)                     AS bienes_activos,
        (SELECT COUNT(*) FROM ACCESO_Registros WHERE CAST(fecha_hora AS DATE)=CAST(GETDATE() AS DATE) AND estado=1) AS accesos_hoy,
        (SELECT COUNT(*) FROM PortuariaDemo.dbo.bit_visitas WHERE fecha_visita=CAST(GETDATE() AS DATE))           AS eventos_hoy,
        (SELECT COUNT(*) FROM CORE_Notificaciones WHERE leida=0 AND estado=1)                                     AS notif_pendientes,
        (SELECT COUNT(*) FROM CORE_Usuarios   WHERE estado=1)                                                     AS usuarios_activos,
        (SELECT COUNT(*) FROM inventario.dbo.inv_inventario WHERE activo=1)                                       AS total_bienes,
        (SELECT COUNT(*) FROM PortuariaDemo.dbo.bit_visitas WHERE hora_salida IS NULL)                            AS alertas_criticas,
        (SELECT COUNT(*) FROM ACCESO_Registros WHERE estado_registro='Activo' AND estado=1)                       AS en_instalaciones;
END;

GO
/****** Objeto: StoredProcedure [dbo].[sp_GetKPIs_Operativo] Fecha de script: 26/7/2026 17:41:55 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO

-- SP9: KPIs Dashboard Operativo. bit_visitas (PortuariaDemo) no tiene
-- id_departamento propio — el filtro por depto solo aplica a lo local.
CREATE PROCEDURE [dbo].[sp_GetKPIs_Operativo]
    @id_usuario INT,
    @modulo     NVARCHAR(30) = 'Central'
AS
BEGIN
    SET NOCOUNT ON;
    DECLARE @id_dep INT;
    SELECT @id_dep=id_departamento FROM CORE_Usuarios WHERE id_usuario=@id_usuario;
    SELECT
        (SELECT COUNT(*) FROM PortuariaDemo.dbo.bit_visitas WHERE hora_salida IS NULL)                            AS tareas_pendientes,
        (SELECT COUNT(*) FROM PortuariaDemo.dbo.bit_visitas WHERE hora_salida IS NULL)                            AS alertas_criticas,
        (SELECT COUNT(*) FROM CORE_Auditoria WHERE id_usuario=@id_usuario AND estado=1
            AND CAST(fecha_registro AS DATE)=CAST(GETDATE() AS DATE))                                             AS acciones_hoy,
        (SELECT COUNT(*) FROM CORE_Notificaciones WHERE id_usuario=@id_usuario AND leida=0 AND estado=1)          AS mis_notificaciones;
END;

GO
/****** Objeto: StoredProcedure [dbo].[sp_GetMenuUsuario] Fecha de script: 26/7/2026 17:41:55 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO

-- SP5: Menú autorizado del usuario vía jerarquía MOIS
-- Devuelve todos los nodos accesibles ordenados por id_modulo > opcion > items > subitems
-- La capa PHP construye el árbol colapsable a partir de este resultado plano
CREATE PROCEDURE [dbo].[sp_GetMenuUsuario]
    @id_usuario INT
AS
BEGIN
    SET NOCOUNT ON;
    SELECT
        mn.id_nodo, mn.id_modulo, mn.opcion, mn.items, mn.subitems,
        mn.descripcion, mn.url_ruta, mn.icono, mn.orden, mn.target_spa,
        MAX(pn.nivel_crud) AS nivel_crud
    FROM CORE_Usuarios u
    INNER JOIN CORE_Usuarios_Roles ur ON ur.id_usuario = u.id_usuario AND ur.estado = 1
    INNER JOIN CORE_Roles r           ON r.id_rol = ur.id_rol AND r.estado = 1
    INNER JOIN CORE_Permisos_Nodo pn  ON pn.id_rol = r.id_rol AND pn.acceso = 1 AND pn.estado = 1
    INNER JOIN CORE_Menu_Nodos mn     ON mn.id_modulo = pn.id_modulo
                                     AND mn.opcion   = pn.opcion
                                     AND mn.items    = pn.items
                                     AND mn.subitems = pn.subitems
                                     AND mn.estado   = 1
    WHERE u.id_usuario = @id_usuario AND u.estado = 1
    GROUP BY mn.id_nodo, mn.id_modulo, mn.opcion, mn.items, mn.subitems,
             mn.descripcion, mn.url_ruta, mn.icono, mn.orden, mn.target_spa
    ORDER BY mn.id_modulo, mn.opcion, mn.items, mn.subitems;
END;

GO
/****** Objeto: StoredProcedure [dbo].[sp_Login] Fecha de script: 26/7/2026 17:41:55 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO

-- ============================================================
-- SECCIÓN 7: STORED PROCEDURES
-- ============================================================

-- SP1: Autenticación — devuelve hash para password_verify() en PHP
CREATE PROCEDURE [dbo].[sp_Login]
    @nombre_usuario  NVARCHAR(50),
    @resultado       NVARCHAR(30)  OUTPUT,
    @id_usuario      INT           OUTPUT,
    @hash_contrasena NVARCHAR(512) OUTPUT,
    @nivel_jerarquia TINYINT       OUTPUT,
    @req_cambio_pass BIT           OUTPUT,
    @nombre_completo NVARCHAR(150) OUTPUT,
    @tema_preferido  NVARCHAR(20)  OUTPUT,
    @id_departamento INT           OUTPUT
AS
BEGIN
    SET NOCOUNT ON;
    SET @id_usuario = NULL;
    SELECT @id_usuario=id_usuario, @hash_contrasena=hash_contrasena,
           @nivel_jerarquia=nivel_jerarquia, @req_cambio_pass=requiere_cambio_pass,
           @nombre_completo=nombre_completo, @tema_preferido=tema_preferido,
           @id_departamento=id_departamento
    FROM CORE_Usuarios WHERE nombre_usuario=@nombre_usuario;

    IF @id_usuario IS NULL BEGIN SET @resultado='NO_ENCONTRADO'; RETURN; END

    DECLARE @est TINYINT, @fb DATETIME2, @min SMALLINT, @int TINYINT;
    SELECT @est=estado, @fb=fecha_bloqueo, @min=minutos_bloqueo, @int=intentos_fallidos
    FROM CORE_Usuarios WHERE id_usuario=@id_usuario;

    IF @est=0 BEGIN SET @resultado='INACTIVO'; RETURN; END
    IF @fb IS NOT NULL AND GETDATE() < DATEADD(MINUTE,@min,@fb) BEGIN SET @resultado='BLOQUEADO'; RETURN; END
    IF @fb IS NOT NULL AND GETDATE() >= DATEADD(MINUTE,@min,@fb)
        UPDATE CORE_Usuarios SET intentos_fallidos=0, fecha_bloqueo=NULL WHERE id_usuario=@id_usuario;

    SET @resultado='OK';
END;

GO
/****** Objeto: StoredProcedure [dbo].[sp_Logout] Fecha de script: 26/7/2026 17:41:55 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO

-- SP3: Cerrar sesión
CREATE PROCEDURE [dbo].[sp_Logout]
    @token      NVARCHAR(128),
    @ip_address NVARCHAR(45) = NULL
AS
BEGIN
    SET NOCOUNT ON;
    DECLARE @id INT;
    SELECT @id=id_usuario FROM CORE_Sesiones WHERE token=@token;
    UPDATE CORE_Sesiones SET estado=0, fecha_revocacion=GETDATE() WHERE token=@token;
    IF @id IS NOT NULL
        INSERT INTO CORE_Auditoria(id_usuario,modulo,operacion,ip_address,resultado)
        VALUES(@id,'CORE','LOGOUT',@ip_address,'EXITO');
END;

GO
/****** Objeto: StoredProcedure [dbo].[sp_PurgarAuditoria] Fecha de script: 26/7/2026 17:41:55 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO

-- SP7: Purgar auditorías antiguas
CREATE PROCEDURE [dbo].[sp_PurgarAuditoria]
AS
BEGIN
    SET NOCOUNT ON;
    DELETE FROM CORE_Auditoria   WHERE fecha_purga IS NOT NULL AND fecha_purga < GETDATE();
    DELETE FROM TH_Auditoria     WHERE fecha_registro < DATEADD(YEAR,-2,GETDATE());
    DELETE FROM BIT_Auditoria    WHERE fecha_registro < DATEADD(YEAR,-2,GETDATE());
    DELETE FROM BIENES_Auditoria WHERE fecha_registro < DATEADD(YEAR,-2,GETDATE());
    DELETE FROM ACCESO_Auditoria WHERE fecha_registro < DATEADD(YEAR,-2,GETDATE());
END;

GO
/****** Objeto: StoredProcedure [dbo].[sp_RegistrarAuditoria] Fecha de script: 26/7/2026 17:41:55 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO

-- SP6: Registrar auditoría centralizada
CREATE PROCEDURE [dbo].[sp_RegistrarAuditoria]
    @modulo         NVARCHAR(30),
    @id_usuario     INT           = NULL,
    @nombre_usuario NVARCHAR(50)  = NULL,
    @operacion      NVARCHAR(30),
    @tabla          NVARCHAR(100) = NULL,
    @id_registro    NVARCHAR(50)  = NULL,
    @datos_antes    NVARCHAR(MAX) = NULL,
    @datos_despues  NVARCHAR(MAX) = NULL,
    @ip_address     NVARCHAR(45)  = NULL,
    @resultado      NVARCHAR(20)  = 'EXITO',
    @detalle        NVARCHAR(500) = NULL
AS
BEGIN
    SET NOCOUNT ON;
    DECLARE @ret INT, @fp DATETIME2;
    SELECT @ret=CAST(valor AS INT) FROM CORE_Config WHERE modulo='CORE' AND clave='AUDIT_RETENTION_YEARS' AND estado=1;
    IF @ret IS NULL SET @ret=2;
    SET @fp=DATEADD(YEAR,@ret,GETDATE());
    IF @id_usuario IS NULL AND @nombre_usuario IS NOT NULL
        SELECT @id_usuario=id_usuario FROM CORE_Usuarios WHERE nombre_usuario=@nombre_usuario;
    INSERT INTO CORE_Auditoria(id_usuario,nombre_usuario,modulo,operacion,tabla_afectada,id_registro,
        datos_antes,datos_despues,ip_address,resultado,detalle,fecha_purga)
    VALUES(@id_usuario,@nombre_usuario,@modulo,@operacion,@tabla,@id_registro,
        @datos_antes,@datos_despues,@ip_address,@resultado,@detalle,@fp);
END;

GO
/****** Objeto: StoredProcedure [dbo].[sp_RegistrarFalloLogin] Fecha de script: 26/7/2026 17:41:55 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO

-- SP2: Registrar fallo de login
CREATE PROCEDURE [dbo].[sp_RegistrarFalloLogin]
    @nombre_usuario NVARCHAR(50),
    @ip_address     NVARCHAR(45) = NULL
AS
BEGIN
    SET NOCOUNT ON;
    DECLARE @id INT, @int TINYINT, @max INT;
    SELECT @id=id_usuario, @int=intentos_fallidos FROM CORE_Usuarios WHERE nombre_usuario=@nombre_usuario;
    IF @id IS NULL RETURN;
    SELECT @max=CAST(valor AS INT) FROM CORE_Config WHERE modulo='CORE' AND clave='LOGIN_MAX_INTENTOS' AND estado=1;
    IF @max IS NULL SET @max=5;
    SET @int=@int+1;
    IF @int>=@max
        UPDATE CORE_Usuarios SET intentos_fallidos=@int, fecha_bloqueo=GETDATE() WHERE id_usuario=@id;
    ELSE
        UPDATE CORE_Usuarios SET intentos_fallidos=@int WHERE id_usuario=@id;
    INSERT INTO CORE_Auditoria(id_usuario,modulo,operacion,ip_address,resultado,detalle)
    VALUES(@id,'CORE','LOGIN',@ip_address,'ERROR','Fallo intento '+CAST(@int AS NVARCHAR));
END;

GO
/****** Objeto: StoredProcedure [dbo].[sp_SSO_ConfirmarLogin] Fecha de script: 26/7/2026 17:41:55 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
/* ── 5. Paso 3a: emitir sesión central tras verificación bcrypt exitosa ──── */
CREATE   PROCEDURE [dbo].[sp_SSO_ConfirmarLogin]
    @codigo_app   NVARCHAR(30),
    @api_key      NVARCHAR(128),
    @id_usuario   INT,
    @ip_address   NVARCHAR(45)  = NULL,
    @user_agent   NVARCHAR(512) = NULL,
    @horas_vida   SMALLINT      = NULL,     -- NULL → CORE_Config SESSION_HOURS o 8
    @token        NVARCHAR(128) OUTPUT,
    @fecha_expira DATETIME2     OUTPUT
AS
BEGIN
    SET NOCOUNT ON;
    SET @token = NULL; SET @fecha_expira = NULL;

    IF dbo.fn_SSO_AppValida(@codigo_app, @api_key, @ip_address) = 0
    BEGIN
        INSERT INTO dbo.CORE_Auditoria (modulo, operacion, ip_address, resultado, detalle)
        VALUES ('CORE', 'SSO_CONFIRM', @ip_address, 'ERROR',
                N'Credencial de aplicación inválida (' + ISNULL(@codigo_app, N'?') + N')');
        RETURN;
    END

    IF NOT EXISTS (SELECT 1 FROM dbo.CORE_Usuarios WHERE id_usuario = @id_usuario AND estado = 1)
        RETURN;

    IF @horas_vida IS NULL
        SELECT @horas_vida = TRY_CAST(valor AS SMALLINT)
        FROM dbo.CORE_Config WHERE modulo = 'CORE' AND clave = 'SESSION_HOURS' AND estado = 1;
    IF @horas_vida IS NULL OR @horas_vida <= 0 SET @horas_vida = 8;

    SET @token        = CONVERT(NVARCHAR(128), CRYPT_GEN_RANDOM(64), 2); -- 128 hex chars
    SET @fecha_expira = DATEADD(HOUR, @horas_vida, GETDATE());

    INSERT INTO dbo.CORE_Sesiones (id_usuario, token, ip_address, user_agent, fecha_expira, estado)
    VALUES (@id_usuario, @token, @ip_address,
            LEFT(N'SSO:' + @codigo_app + N' ' + ISNULL(@user_agent, N''), 512),
            @fecha_expira, 1);

    UPDATE dbo.CORE_Usuarios
       SET intentos_fallidos = 0, fecha_bloqueo = NULL
     WHERE id_usuario = @id_usuario;

    INSERT INTO dbo.CORE_Auditoria (id_usuario, modulo, operacion, ip_address, resultado, detalle)
    VALUES (@id_usuario, 'CORE', 'LOGIN', @ip_address, 'EXITO', N'Login vía SSO:' + @codigo_app);
END;
GO
/****** Objeto: StoredProcedure [dbo].[sp_SSO_Login] Fecha de script: 26/7/2026 17:41:55 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
/* ── 4. Paso 1: validar cuenta y obtener hash (solo apps autenticadas) ───── */
CREATE   PROCEDURE [dbo].[sp_SSO_Login]
    @codigo_app      NVARCHAR(30),
    @api_key         NVARCHAR(128),
    @nombre_usuario  NVARCHAR(50),
    @ip_address      NVARCHAR(45)  = NULL,
    @resultado       NVARCHAR(30)  OUTPUT,   -- APP_INVALIDA | NO_ENCONTRADO | INACTIVO | BLOQUEADO | OK
    @id_usuario      INT           OUTPUT,
    @hash_contrasena NVARCHAR(512) OUTPUT,   -- para password_verify() en el módulo
    @nivel_jerarquia TINYINT       OUTPUT,
    @req_cambio_pass BIT           OUTPUT,
    @nombre_completo NVARCHAR(150) OUTPUT,
    @id_departamento INT           OUTPUT
AS
BEGIN
    SET NOCOUNT ON;
    SET @resultado = NULL; SET @id_usuario = NULL; SET @hash_contrasena = NULL;
    SET @nivel_jerarquia = NULL; SET @req_cambio_pass = NULL;
    SET @nombre_completo = NULL; SET @id_departamento = NULL;

    IF dbo.fn_SSO_AppValida(@codigo_app, @api_key, @ip_address) = 0
    BEGIN
        SET @resultado = 'APP_INVALIDA';
        INSERT INTO dbo.CORE_Auditoria (modulo, operacion, ip_address, resultado, detalle)
        VALUES ('CORE', 'SSO_LOGIN', @ip_address, 'ERROR',
                N'Credencial de aplicación inválida (' + ISNULL(@codigo_app, N'?') + N')');
        RETURN;
    END

    DECLARE @tema NVARCHAR(20);
    EXEC dbo.sp_Login
        @nombre_usuario  = @nombre_usuario,
        @resultado       = @resultado       OUTPUT,
        @id_usuario      = @id_usuario      OUTPUT,
        @hash_contrasena = @hash_contrasena OUTPUT,
        @nivel_jerarquia = @nivel_jerarquia OUTPUT,
        @req_cambio_pass = @req_cambio_pass OUTPUT,
        @nombre_completo = @nombre_completo OUTPUT,
        @tema_preferido  = @tema            OUTPUT,
        @id_departamento = @id_departamento OUTPUT;

    -- El hash solo sale si la cuenta está operativa
    IF @resultado <> 'OK'
        SET @hash_contrasena = NULL;
END;
GO
/****** Objeto: StoredProcedure [dbo].[sp_SSO_Logout] Fecha de script: 26/7/2026 17:41:55 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
/* ── 8. Cierre de sesión desde un módulo ─────────────────────────────────── */
CREATE   PROCEDURE [dbo].[sp_SSO_Logout]
    @codigo_app NVARCHAR(30),
    @api_key    NVARCHAR(128),
    @token      NVARCHAR(128),
    @ip_address NVARCHAR(45) = NULL
AS
BEGIN
    SET NOCOUNT ON;

    IF dbo.fn_SSO_AppValida(@codigo_app, @api_key, @ip_address) = 0
        RETURN;

    EXEC dbo.sp_Logout @token = @token, @ip_address = @ip_address;
END;
GO
/****** Objeto: StoredProcedure [dbo].[sp_SSO_RegistrarApp] Fecha de script: 26/7/2026 17:41:55 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
/* ── 3. Alta / rotación de aplicaciones (solo administradores de BD) ─────── */
CREATE   PROCEDURE [dbo].[sp_SSO_RegistrarApp]
    @codigo        NVARCHAR(30),
    @nombre        NVARCHAR(100),
    @api_key       NVARCHAR(128),      -- entregar al módulo por canal seguro; aquí solo se guarda el hash
    @ip_permitidas NVARCHAR(500) = NULL,
    @fecha_expira  DATETIME2     = NULL,
    @creado_por    NVARCHAR(50)  = NULL
AS
BEGIN
    SET NOCOUNT ON;

    IF LEN(ISNULL(@api_key, N'')) < 32
    BEGIN
        RAISERROR(N'La api_key debe tener al menos 32 caracteres.', 16, 1);
        RETURN;
    END

    IF EXISTS (SELECT 1 FROM dbo.CORE_Aplicaciones WHERE codigo = @codigo)
        UPDATE dbo.CORE_Aplicaciones
           SET nombre        = @nombre,
               api_key_hash  = HASHBYTES('SHA2_256', @api_key),
               ip_permitidas = @ip_permitidas,
               fecha_expira  = @fecha_expira,
               estado        = 1
         WHERE codigo = @codigo;
    ELSE
        INSERT INTO dbo.CORE_Aplicaciones (codigo, nombre, api_key_hash, ip_permitidas, fecha_expira, creado_por)
        VALUES (@codigo, @nombre, HASHBYTES('SHA2_256', @api_key), @ip_permitidas, @fecha_expira, @creado_por);

    INSERT INTO dbo.CORE_Auditoria (id_usuario, modulo, operacion, ip_address, resultado, detalle)
    VALUES (NULL, 'CORE', 'SSO_APP_REG', NULL, 'EXITO', N'Alta/rotación de aplicación SSO: ' + @codigo);
END;
GO
/****** Objeto: StoredProcedure [dbo].[sp_SSO_RegistrarFallo] Fecha de script: 26/7/2026 17:41:55 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
/* ── 6. Paso 3b: registrar fallo (cuenta intentos / bloquea) ─────────────── */
CREATE   PROCEDURE [dbo].[sp_SSO_RegistrarFallo]
    @codigo_app     NVARCHAR(30),
    @api_key        NVARCHAR(128),
    @nombre_usuario NVARCHAR(50),
    @ip_address     NVARCHAR(45) = NULL
AS
BEGIN
    SET NOCOUNT ON;

    IF dbo.fn_SSO_AppValida(@codigo_app, @api_key, @ip_address) = 0
        RETURN;

    EXEC dbo.sp_RegistrarFalloLogin @nombre_usuario = @nombre_usuario, @ip_address = @ip_address;
END;
GO
/****** Objeto: StoredProcedure [dbo].[sp_SSO_ValidarToken] Fecha de script: 26/7/2026 17:41:55 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
/* ── 7. Validación de token para peticiones posteriores (SSO real) ───────── */
CREATE   PROCEDURE [dbo].[sp_SSO_ValidarToken]
    @codigo_app      NVARCHAR(30),
    @api_key         NVARCHAR(128),
    @token           NVARCHAR(128),
    @ip_address      NVARCHAR(45)  = NULL,
    @resultado       NVARCHAR(30)  OUTPUT,  -- APP_INVALIDA | TOKEN_INVALIDO | EXPIRADO | OK
    @id_usuario      INT           OUTPUT,
    @nombre_usuario  NVARCHAR(50)  OUTPUT,
    @nombre_completo NVARCHAR(150) OUTPUT,
    @nivel_jerarquia TINYINT       OUTPUT,
    @id_departamento INT           OUTPUT
AS
BEGIN
    SET NOCOUNT ON;
    SET @resultado = NULL; SET @id_usuario = NULL; SET @nombre_usuario = NULL;
    SET @nombre_completo = NULL; SET @nivel_jerarquia = NULL; SET @id_departamento = NULL;

    IF dbo.fn_SSO_AppValida(@codigo_app, @api_key, @ip_address) = 0
    BEGIN
        SET @resultado = 'APP_INVALIDA';
        RETURN;
    END

    DECLARE @expira DATETIME2, @estadoSes TINYINT;

    SELECT @id_usuario = s.id_usuario, @expira = s.fecha_expira, @estadoSes = s.estado
    FROM dbo.CORE_Sesiones s
    WHERE s.token = @token;

    IF @id_usuario IS NULL OR @estadoSes = 0
    BEGIN
        SET @resultado = 'TOKEN_INVALIDO'; SET @id_usuario = NULL; RETURN;
    END

    IF @expira <= GETDATE()
    BEGIN
        UPDATE dbo.CORE_Sesiones SET estado = 0, fecha_revocacion = GETDATE() WHERE token = @token;
        SET @resultado = 'EXPIRADO'; SET @id_usuario = NULL; RETURN;
    END

    SELECT @nombre_usuario  = u.nombre_usuario,
           @nombre_completo = u.nombre_completo,
           @nivel_jerarquia = u.nivel_jerarquia,
           @id_departamento = u.id_departamento
    FROM dbo.CORE_Usuarios u
    WHERE u.id_usuario = @id_usuario AND u.estado = 1;

    IF @nombre_usuario IS NULL
    BEGIN
        SET @resultado = 'TOKEN_INVALIDO'; SET @id_usuario = NULL; RETURN;
    END

    UPDATE dbo.CORE_Sesiones SET fecha_ultima_actividad = GETDATE() WHERE token = @token;
    SET @resultado = 'OK';
END;
GO
USE [master]
GO
ALTER DATABASE [PORTAL_APM] SET  READ_WRITE 
GO

-- ============================================================
-- SECCIÓN: DATOS SEMILLA
-- Departamentos = organigrama real (20 direcciones/departamentos
-- APM), SIN menú propio. Módulos con menú real: 1=Central,
-- 11=Talento Humano, 12=Control de Bienes, 13=Portuaria (Bitácoras)
-- — los únicos 4 con entrada en CORE_Menu_Nodos.
-- ============================================================

USE [PORTAL_APM]
GO

SET IDENTITY_INSERT CORE_Departamentos ON;
INSERT INTO CORE_Departamentos(id_departamento,codigo,nombre,id_padre,nivel,icono,color_badge) VALUES
(1,N'PORTAL',N'Portal APM',NULL,0,N'fa-anchor',N'#0056b3'),
(2,N'GERENCIA',N'Gerencia General',N'1',1,N'fa-building',N'#1a2332'),
(3,N'PLANIFICACION',N'Dirección de Planificación',N'1',1,N'fa-chart-gantt',N'#0056b3'),
(4,N'JURIDICA',N'Dirección Jurídica',N'1',1,N'fa-scale-balanced',N'#6f42c1'),
(5,N'INFRA',N'Dirección de Infraestructura',N'1',1,N'fa-hard-hat',N'#fd7e14'),
(6,N'OPERACIONES',N'Dirección de Operaciones',N'1',1,N'fa-ship',N'#20c997'),
(7,N'DELEGACION',N'Delegación Provincial',N'1',1,N'fa-landmark',N'#6c757d'),
(8,N'ADMIN',N'Dirección Administrativa',N'1',1,N'fa-briefcase',N'#dc3545'),
(9,N'FINANCIERO',N'Dirección Financiera',N'1',1,N'fa-wallet',N'#198754'),
(10,N'TH',N'Dirección de Talento Humano',N'1',1,N'fa-users',N'#0dcaf0'),
(11,N'TI',N'Departamento TI',N'8',2,N'fa-server',N'#6c757d'),
(12,N'AUDITORIA',N'Auditoría Interna',N'2',2,N'fa-clipboard-check',N'#6f42c1'),
(13,N'JURIDICA_ABOG',N'Abogados',N'4',2,N'fa-gavel',N'#6f42c1'),
(14,N'CCTV',N'Vigilancia CCTV',N'6',2,N'fa-video',N'#495057'),
(15,N'GARITA',N'Garita de Acceso',N'6',2,N'fa-door-open',N'#20c997'),
(16,N'INSPECTORES',N'Inspectores Portuarios',N'5',2,N'fa-binoculars',N'#fd7e14'),
(17,N'TH_ANALISTAS',N'Analistas TH',N'10',2,N'fa-user-gear',N'#0dcaf0'),
(18,N'CONTABILIDAD',N'Contabilidad',N'9',2,N'fa-calculator',N'#198754'),
(19,N'PRESUPUESTO',N'Presupuesto',N'9',2,N'fa-coins',N'#198754'),
(20,N'SECRETARIA',N'Secretaría General',N'2',2,N'fa-envelope',N'#6c757d');
SET IDENTITY_INSERT CORE_Departamentos OFF;
GO

SET IDENTITY_INSERT CORE_Usuarios ON;
INSERT INTO CORE_Usuarios(id_usuario,nombre_usuario,correo,nombre_completo,hash_contrasena,salt,id_departamento,nivel_jerarquia,estado,tema_preferido,cedula,id_empleado_th) VALUES
(1,N'admin',N'admin@apm.gob.ec',N'Administrador TI',N'$2y$12$rdWFpTOPEQSoN8QUYf4A5uTczqz9ivTV7qpIaW473OMW.JjF2woYK',N'',N'11',4,1,N'dark',NULL,NULL),
(2,N'auditor',N'auditor@apm.gob.ec',N'Auditor Interno',N'$2y$12$rdWFpTOPEQSoN8QUYf4A5uTczqz9ivTV7qpIaW473OMW.JjF2woYK',N'',N'12',2,1,N'light',NULL,NULL),
(3,N'dir.juridica',N'djuridica@apm.gob.ec',N'Director Jurídico',N'$2y$12$rdWFpTOPEQSoN8QUYf4A5uTczqz9ivTV7qpIaW473OMW.JjF2woYK',N'',N'4',2,1,N'corporate',NULL,NULL),
(4,N'abogada1',N'abg1@apm.gob.ec',N'Abogada Primera',N'$2y$12$rdWFpTOPEQSoN8QUYf4A5uTczqz9ivTV7qpIaW473OMW.JjF2woYK',N'',N'13',1,1,N'light',NULL,NULL),
(5,N'abogado2',N'abg2@apm.gob.ec',N'Abogado Segundo',N'$2y$12$rdWFpTOPEQSoN8QUYf4A5uTczqz9ivTV7qpIaW473OMW.JjF2woYK',N'',N'13',1,1,N'light',NULL,NULL),
(6,N'sup.acceso',N'sacceso@apm.gob.ec',N'Supervisora Control Acceso',N'$2y$12$rdWFpTOPEQSoN8QUYf4A5uTczqz9ivTV7qpIaW473OMW.JjF2woYK',N'',N'15',1,1,N'light',NULL,NULL),
(7,N'garita1',N'garita1@apm.gob.ec',N'Operador Garita 1',N'$2y$12$rdWFpTOPEQSoN8QUYf4A5uTczqz9ivTV7qpIaW473OMW.JjF2woYK',N'',N'15',0,1,N'light',NULL,NULL),
(8,N'garita2',N'garita2@apm.gob.ec',N'Operador Garita 2',N'$2y$12$rdWFpTOPEQSoN8QUYf4A5uTczqz9ivTV7qpIaW473OMW.JjF2woYK',N'',N'15',0,1,N'light',NULL,NULL),
(9,N'cctv.op',N'cctv@apm.gob.ec',N'Operador CCTV',N'$2y$12$rdWFpTOPEQSoN8QUYf4A5uTczqz9ivTV7qpIaW473OMW.JjF2woYK',N'',N'14',0,1,N'dark',NULL,NULL),
(10,N'secretaria',N'secretaria@apm.gob.ec',N'Secretaria General',N'$2y$12$rdWFpTOPEQSoN8QUYf4A5uTczqz9ivTV7qpIaW473OMW.JjF2woYK',N'',N'20',1,1,N'light',NULL,NULL),
(11,N'dir.infra',N'dinfra@apm.gob.ec',N'Director Infraestructura',N'$2y$12$rdWFpTOPEQSoN8QUYf4A5uTczqz9ivTV7qpIaW473OMW.JjF2woYK',N'',N'5',2,1,N'corporate',NULL,NULL),
(12,N'inspector1',N'insp1@apm.gob.ec',N'Inspector Portuario 1',N'$2y$12$rdWFpTOPEQSoN8QUYf4A5uTczqz9ivTV7qpIaW473OMW.JjF2woYK',N'',N'16',0,1,N'light',NULL,NULL),
(13,N'inspector2',N'insp2@apm.gob.ec',N'Inspector Portuario 2',N'$2y$12$rdWFpTOPEQSoN8QUYf4A5uTczqz9ivTV7qpIaW473OMW.JjF2woYK',N'',N'16',0,1,N'light',NULL,NULL),
(14,N'dir.th',N'dth@apm.gob.ec',N'Directora Talento Humano',N'$2y$12$rdWFpTOPEQSoN8QUYf4A5uTczqz9ivTV7qpIaW473OMW.JjF2woYK',N'',N'10',2,1,N'light',NULL,NULL),
(15,N'analista.th',N'ath@apm.gob.ec',N'Analista Talento Humano',N'$2y$12$rdWFpTOPEQSoN8QUYf4A5uTczqz9ivTV7qpIaW473OMW.JjF2woYK',N'',N'17',1,1,N'light',NULL,NULL),
(16,N'gerente',N'gerente@apm.gob.ec',N'Gerente General',N'$2y$12$rdWFpTOPEQSoN8QUYf4A5uTczqz9ivTV7qpIaW473OMW.JjF2woYK',N'',N'2',3,1,N'corporate',NULL,NULL),
(17,N'asist.gcia',N'agcia@apm.gob.ec',N'Asistente Gerencia',N'$2y$12$rdWFpTOPEQSoN8QUYf4A5uTczqz9ivTV7qpIaW473OMW.JjF2woYK',N'',N'2',1,1,N'light',NULL,NULL),
(18,N'dir.admin',N'dadmin@apm.gob.ec',N'Directora Administrativa',N'$2y$12$rdWFpTOPEQSoN8QUYf4A5uTczqz9ivTV7qpIaW473OMW.JjF2woYK',N'',N'8',2,1,N'corporate',NULL,NULL),
(19,N'analista.admin',N'aadmin@apm.gob.ec',N'Analista Administrativo',N'$2y$12$rdWFpTOPEQSoN8QUYf4A5uTczqz9ivTV7qpIaW473OMW.JjF2woYK',N'',N'8',1,1,N'light',NULL,NULL),
(20,N'dir.fin',N'dfin@apm.gob.ec',N'Director Financiero',N'$2y$12$rdWFpTOPEQSoN8QUYf4A5uTczqz9ivTV7qpIaW473OMW.JjF2woYK',N'',N'9',2,1,N'corporate',NULL,NULL),
(21,N'analista.fin',N'afin@apm.gob.ec',N'Analista Financiero',N'$2y$12$rdWFpTOPEQSoN8QUYf4A5uTczqz9ivTV7qpIaW473OMW.JjF2woYK',N'',N'9',1,1,N'light',NULL,NULL);
SET IDENTITY_INSERT CORE_Usuarios OFF;
GO

SET IDENTITY_INSERT CORE_Roles ON;
INSERT INTO CORE_Roles(id_rol,codigo,nombre,id_departamento,nivel_jerarquia,estado) VALUES
(1,N'ADMIN',N'Administrador TI',N'11',4,1),
(2,N'AUDITOR',N'Auditor Interno',N'12',2,1),
(3,N'DIR_JURIDICA',N'Director Jurídico',N'4',2,1),
(4,N'ABOGADO',N'Abogado/a',N'13',1,0),
(5,N'SUP_ACCESO',N'Supervisor Acceso',N'15',1,1),
(6,N'OP_GARITA',N'Operador Garita',N'15',0,1),
(7,N'OP_CCTV',N'Operador CCTV',N'14',0,1),
(8,N'SECRETARIA',N'Secretaria',N'20',1,1),
(9,N'DIR_INFRA',N'Director Infraestructura',N'5',2,1),
(10,N'INSPECTOR',N'Inspector Portuario',N'16',0,1),
(11,N'DIR_TH',N'Director Talento Humano',N'10',2,1),
(12,N'ANALISTA_TH',N'Analista TH',N'17',1,1),
(13,N'GERENTE',N'Gerente General',N'2',3,1),
(14,N'ASIST_GCIA',N'Asistente Gerencia',N'2',1,1),
(15,N'DIR_ADMIN',N'Director Administrativo',N'8',2,1),
(16,N'ANALISTA_ADMIN',N'Analista Administrativo',N'8',1,1),
(17,N'DIR_FIN',N'Director Financiero',N'9',2,1),
(18,N'ANALISTA_FIN',N'Analista Financiero',N'9',1,1),
(19,N'PLANIFICACION',N'Planificación',N'3',2,1),
(20,N'DELEGACION',N'Delegación',N'7',2,1),
(21,N'LECTOR',N'Acceso Solo Lectura',N'1',0,0),
(22,N'DTI-AAA',N'PERMISO DE OPERA',NULL,1,1);
SET IDENTITY_INSERT CORE_Roles OFF;
GO

INSERT INTO CORE_Usuarios_Roles(id_usuario,id_rol,asignado_por,estado) VALUES
(1,1,N'1',1),
(2,2,N'1',1),
(3,3,N'1',1),
(4,4,N'1',1),
(5,4,N'1',1),
(6,5,N'1',1),
(7,6,N'1',1),
(8,6,N'1',1),
(9,7,N'1',1),
(10,8,N'1',1),
(11,9,N'1',1),
(12,10,N'1',1),
(13,10,N'1',1),
(14,11,N'1',1),
(15,12,N'1',1),
(16,13,N'1',1),
(17,14,N'1',1),
(18,15,N'1',1),
(19,16,N'1',1),
(20,17,N'1',1),
(21,18,N'1',1);
GO

INSERT INTO TH_Unidad_Map(codigo_uorg,id_departamento,id_rol_director,id_rol_analista) VALUES
(N'DEP-BS',10,11,12),
(N'DEP-CON',18,17,18),
(N'DEP-FAC',9,17,18),
(N'DEP-NOM',10,11,12),
(N'DEP-PLAN',3,19,19),
(N'DEP-PRE',19,17,18),
(N'DEP-SEL',10,11,12),
(N'DEP-TES',9,17,18),
(N'DIR-CON',18,17,18),
(N'DIR-FIN',9,17,18),
(N'DIR-JUR',4,3,4),
(N'DIR-PLAN',3,19,19),
(N'DIR-TES',9,17,18),
(N'DIR-TH',10,11,12),
(N'DIR-TICS',11,1,1);
GO

-- Módulo 1: Central (Portal APM) — lo único "propio" del portal:
-- dashboards, administración de cuentas/roles/menú, perfil, notificaciones.
-- ============================================================
INSERT INTO CORE_Menu_Nodos(id_modulo,opcion,items,subitems,descripcion,url_ruta,icono,orden,requiere_mfa,target_spa,estado) VALUES
(1,0,0,0,N'Central — Portal APM',NULL,N'fa-anchor',1,0,1,1),

(1,1,0,0,N'Panel Principal',NULL,N'fa-gauge',1,0,1,1),
(1,1,1,0,N'Dashboard',N'/dashboard',N'fa-gauge',1,0,1,1),
(1,1,3,0,N'Dashboard Operativo',N'/dashboard/operativo',N'fa-chart-bar',2,0,1,1),

(1,2,0,0,N'Administración',NULL,N'fa-sliders',2,0,1,1),
(1,2,1,0,N'Gestión de Usuarios',N'/admin/usuarios',N'fa-users-gear',1,0,1,1),
(1,2,2,0,N'Crear cuenta desde Talento Humano',N'/admin/usuarios/desde-th',N'fa-user-plus',2,0,1,1),
(1,2,3,0,N'Roles y Permisos',N'/admin/roles',N'fa-shield-halved',3,0,1,1),
(1,2,4,0,N'Estructura del Menú',N'/admin/menu',N'fa-sitemap',4,0,1,1),
(1,2,5,0,N'Auditoría del Sistema',N'/admin/auditoria',N'fa-diagram-project',5,0,1,1),

(1,3,0,0,N'Mi Cuenta',NULL,N'fa-user',3,0,1,1),
(1,3,1,0,N'Mi Perfil',N'/perfil',N'fa-id-badge',1,0,1,1),
(1,3,2,0,N'Notificaciones',N'/notificaciones',N'fa-bell',2,0,1,1);
GO

-- Modulo 11
INSERT INTO CORE_Menu_Nodos(id_modulo,opcion,items,subitems,descripcion,url_ruta,icono,orden,requiere_mfa,target_spa,estado) VALUES
(11,0,0,0,N'Talento Humano',NULL,N'fa-users',11,0,1,1),
(11,1,1,0,N'Panel',N'/panel/talento-humano',N'fa-chart-pie',1,0,1,1),
(11,1,5,0,N'Sistema de Talento Humano',N'/apps/talento_humano/',N'fa-users-gear',2,0,0,1);
GO

-- Modulo 12
INSERT INTO CORE_Menu_Nodos(id_modulo,opcion,items,subitems,descripcion,url_ruta,icono,orden,requiere_mfa,target_spa,estado) VALUES
(12,0,0,0,N'Control de Bienes',NULL,N'fa-boxes-stacked',12,0,1,1),
(12,1,1,0,N'Panel',N'/panel/bienes',N'fa-chart-pie',1,0,1,1),
(12,1,5,0,N'Sistema de Control de Bienes',N'/apps/control_bienes/',N'fa-boxes-stacked',2,0,0,1);
GO

-- Modulo 13
INSERT INTO CORE_Menu_Nodos(id_modulo,opcion,items,subitems,descripcion,url_ruta,icono,orden,requiere_mfa,target_spa,estado) VALUES
(13,0,0,0,N'Bitácoras Portuarias',NULL,N'fa-anchor',13,0,0,1),
(13,1,0,0,N'Bitácoras',NULL,N'fa-gauge-high',1,0,0,1),
(13,1,1,0,N'Panel Portuario',N'/portuaria',N'fa-anchor',1,0,1,1),
(13,1,2,0,N'Vista Rápida Visitas',N'/portuaria/visitas-resumen',N'fa-list-check',2,0,1,0),
(13,1,3,0,N'Actividad de Seguridad',N'/portuaria/actividad',N'fa-shield-halved',3,0,1,0),
(13,1,4,0,N'Dashboard del Módulo',N'/portuaria/dashboard',N'fa-gauge',4,0,0,0),
(13,1,5,0,N'Panel Jefatura',N'/bit_dashboard_jefe.php',N'fa-chart-line',5,0,0,0),
(13,1,6,0,N'Dashboard Ejecutivo (Py)',N'/dashboard-ejecutivo',N'fa-chart-pie',6,0,0,0),
(13,1,7,0,N'Sistema de Bitácoras',N'/visitas',N'fa-anchor',2,0,0,1),
(13,2,0,0,N'Visitas',NULL,N'fa-person-walking-arrow-right',2,0,0,0),
(13,2,1,0,N'Registrar Ingreso',N'/visitas/registrar',N'fa-person-circle-plus',1,0,0,0),
(13,2,2,0,N'Listado de Visitas',N'/visitas',N'fa-list-ul',2,0,0,0),
(13,3,0,0,N'Seguridad Operativa',NULL,N'fa-shield-halved',3,0,0,0),
(13,3,1,0,N'Bitácora de Rondas',N'/rondas',N'fa-clipboard-check',1,0,0,0),
(13,3,2,0,N'Reporte Supervisor',N'/bit_reporte_diario_supervisor.php',N'fa-file-lines',2,0,0,0),
(13,3,3,0,N'Consulta de Bitácoras',N'/bit_consulta.php',N'fa-magnifying-glass',3,0,0,0),
(13,4,0,0,N'CCTV Cámaras',NULL,N'fa-video',4,0,0,0),
(13,4,1,0,N'Bitácora de Cámaras',N'/camaras',N'fa-camera',1,0,0,0),
(13,4,2,0,N'Maestro de Cámaras',N'/camaras/inventario',N'fa-server',2,0,0,0),
(13,4,3,0,N'Motivos CCTV',N'/camaras/motivos',N'fa-triangle-exclamation',3,0,0,0),
(13,5,0,0,N'Catálogos',NULL,N'fa-database',5,0,0,0),
(13,5,1,0,N'Registros Base',N'/catalogos',N'fa-table-list',1,0,0,0),
(13,5,2,0,N'Maestro Personas',N'/catalogos/personas',N'fa-id-card',2,0,0,0),
(13,5,3,0,N'Maestro Empresas',N'/catalogos/empresas',N'fa-building',3,0,0,0),
(13,5,4,0,N'Maestro Destinos',N'/catalogos/destinos',N'fa-signs-post',4,0,0,0),
(13,5,5,0,N'Maestro Motivos',N'/catalogos/motivos',N'fa-comment-dots',5,0,0,0),
(13,5,6,0,N'Funcionarios (DBF)',N'/catalogos/funcionarios',N'fa-user-tie',6,0,0,0),
(13,5,7,0,N'Niveles de Importancia',N'/catalogos/niveles-incidente',N'fa-exclamation',7,0,0,0),
(13,5,8,0,N'Importar Funcionarios',N'/importar-funcionarios',N'fa-file-import',8,0,0,0);
GO

-- Todos los roles activos: Panel Principal + Mi Cuenta (ver, nivel_crud=1).
INSERT INTO CORE_Permisos_Nodo(id_rol,id_modulo,opcion,items,subitems,nivel_crud,acceso,estado,asignado_por)
SELECT r.id_rol, mn.id_modulo, mn.opcion, mn.items, mn.subitems, 1, 1, 1, 1
FROM CORE_Roles r CROSS JOIN CORE_Menu_Nodos mn
WHERE mn.id_modulo=1 AND mn.opcion IN (0,1,3) AND r.estado=1;
GO

-- Administración: solo jefatura+ (nivel_jerarquia>=3); ADMIN con nivel_crud=4.
-- (La página en sí ya está protegida en el controller con requireLevel(3) —
--  esto es para que el link aparezca en el menú de quien realmente la usa.)
INSERT INTO CORE_Permisos_Nodo(id_rol,id_modulo,opcion,items,subitems,nivel_crud,acceso,estado,asignado_por)
SELECT r.id_rol, mn.id_modulo, mn.opcion, mn.items, mn.subitems,
       CASE WHEN r.codigo='ADMIN' THEN 4 ELSE 2 END, 1, 1, 1
FROM CORE_Roles r CROSS JOIN CORE_Menu_Nodos mn
WHERE mn.id_modulo=1 AND mn.opcion=2 AND r.estado=1 AND r.nivel_jerarquia>=3;
GO

-- Permisos modulo 11
INSERT INTO CORE_Permisos_Nodo(id_rol,id_modulo,opcion,items,subitems,nivel_crud,acceso,estado,asignado_por) VALUES
(1,11,0,0,0,4,1,1,1),
(1,11,1,1,0,4,1,1,1),
(1,11,1,5,0,4,1,1,1),
(2,11,0,0,0,1,1,1,1),
(2,11,1,1,0,1,1,1,1),
(2,11,1,5,0,1,1,1,1),
(11,11,0,0,0,4,1,1,1),
(11,11,1,1,0,4,1,1,1),
(11,11,1,5,0,4,1,1,1),
(12,11,0,0,0,2,1,1,1),
(12,11,1,1,0,2,1,1,1),
(12,11,1,5,0,2,1,1,1),
(13,11,0,0,0,1,1,1,1),
(13,11,1,1,0,1,1,1,1),
(13,11,1,5,0,1,1,1,1),
(14,11,0,0,0,1,1,1,1),
(14,11,1,1,0,1,1,1,1),
(14,11,1,5,0,1,1,1,1);
GO

-- Permisos modulo 12
INSERT INTO CORE_Permisos_Nodo(id_rol,id_modulo,opcion,items,subitems,nivel_crud,acceso,estado,asignado_por) VALUES
(1,12,0,0,0,4,1,1,1),
(1,12,1,1,0,4,1,1,1),
(1,12,1,5,0,4,1,1,1);
GO

-- Permisos modulo 13
INSERT INTO CORE_Permisos_Nodo(id_rol,id_modulo,opcion,items,subitems,nivel_crud,acceso,estado,asignado_por) VALUES
(1,13,0,0,0,4,1,1,1),
(1,13,1,0,0,4,1,1,1),
(1,13,1,1,0,4,1,1,1),
(1,13,1,2,0,4,1,1,1),
(1,13,1,3,0,4,1,1,1),
(1,13,1,4,0,4,1,1,1),
(1,13,1,5,0,4,1,1,1),
(1,13,1,6,0,4,1,1,1),
(1,13,1,7,0,4,1,1,1),
(1,13,2,0,0,4,1,1,1),
(1,13,2,1,0,4,1,1,1),
(1,13,2,2,0,4,1,1,1),
(1,13,3,0,0,4,1,1,1),
(1,13,3,1,0,4,1,1,1),
(1,13,3,2,0,4,1,1,1),
(1,13,3,3,0,4,1,1,1),
(1,13,4,0,0,4,1,1,1),
(1,13,4,1,0,4,1,1,1),
(1,13,4,2,0,4,1,1,1),
(1,13,4,3,0,4,1,1,1),
(1,13,5,0,0,4,1,1,1),
(1,13,5,1,0,4,1,1,1),
(1,13,5,2,0,4,1,1,1),
(1,13,5,3,0,4,1,1,1),
(1,13,5,4,0,4,1,1,1),
(1,13,5,5,0,4,1,1,1),
(1,13,5,6,0,4,1,1,1),
(1,13,5,7,0,4,1,1,1),
(1,13,5,8,0,4,1,1,1),
(2,13,0,0,0,1,1,1,1),
(2,13,1,0,0,1,1,1,1),
(2,13,1,1,0,1,1,1,1),
(2,13,1,2,0,1,1,1,1),
(2,13,1,3,0,1,1,1,1),
(2,13,1,7,0,1,1,1,1),
(2,13,2,0,0,1,1,1,1),
(2,13,2,2,0,1,1,1,1),
(2,13,3,0,0,1,1,1,1),
(2,13,3,3,0,1,1,1,1),
(5,13,0,0,0,3,1,1,1),
(5,13,1,0,0,2,1,1,1),
(5,13,1,1,0,2,1,1,1),
(5,13,1,2,0,2,1,1,1),
(5,13,1,3,0,2,1,1,1),
(5,13,1,7,0,2,1,1,1),
(5,13,2,0,0,3,1,1,1),
(5,13,2,1,0,3,1,1,1),
(5,13,2,2,0,3,1,1,1),
(5,13,3,0,0,3,1,1,1),
(5,13,3,1,0,3,1,1,1),
(5,13,3,2,0,3,1,1,1),
(5,13,3,3,0,3,1,1,1),
(5,13,5,0,0,3,1,1,1),
(5,13,5,1,0,3,1,1,1),
(5,13,5,2,0,3,1,1,1),
(5,13,5,3,0,3,1,1,1),
(5,13,5,4,0,3,1,1,1),
(5,13,5,5,0,3,1,1,1),
(5,13,5,6,0,3,1,1,1),
(5,13,5,7,0,3,1,1,1),
(5,13,5,8,0,3,1,1,1),
(6,13,0,0,0,3,1,1,1),
(6,13,1,0,0,2,1,1,1),
(6,13,1,1,0,2,1,1,1),
(6,13,1,2,0,2,1,1,1),
(6,13,1,3,0,2,1,1,1),
(6,13,1,7,0,2,1,1,1),
(6,13,2,0,0,3,1,1,1),
(6,13,2,1,0,3,1,1,1),
(6,13,2,2,0,3,1,1,1),
(6,13,3,0,0,3,1,1,1),
(6,13,3,1,0,3,1,1,1),
(6,13,3,2,0,3,1,1,1),
(6,13,3,3,0,3,1,1,1),
(6,13,5,0,0,3,1,1,1),
(6,13,5,1,0,3,1,1,1),
(6,13,5,2,0,3,1,1,1),
(6,13,5,3,0,3,1,1,1),
(6,13,5,4,0,3,1,1,1),
(6,13,5,5,0,3,1,1,1),
(6,13,5,6,0,3,1,1,1),
(6,13,5,7,0,3,1,1,1),
(6,13,5,8,0,3,1,1,1),
(7,13,0,0,0,3,1,1,1),
(7,13,1,0,0,2,1,1,1),
(7,13,1,1,0,2,1,1,1),
(7,13,1,2,0,2,1,1,1),
(7,13,1,3,0,2,1,1,1),
(7,13,1,7,0,2,1,1,1),
(7,13,3,0,0,3,1,1,1),
(7,13,3,1,0,3,1,1,1),
(7,13,3,2,0,3,1,1,1),
(7,13,3,3,0,3,1,1,1),
(7,13,4,0,0,3,1,1,1),
(7,13,4,1,0,3,1,1,1),
(7,13,4,2,0,3,1,1,1),
(7,13,4,3,0,3,1,1,1),
(13,13,0,0,0,2,1,1,1),
(13,13,1,0,0,2,1,1,1),
(13,13,1,1,0,2,1,1,1),
(13,13,1,2,0,2,1,1,1),
(13,13,1,3,0,2,1,1,1),
(13,13,1,4,0,2,1,1,1),
(13,13,1,5,0,2,1,1,1),
(13,13,1,6,0,2,1,1,1),
(13,13,1,7,0,2,1,1,1),
(13,13,2,0,0,2,1,1,1),
(13,13,2,2,0,2,1,1,1),
(13,13,3,0,0,2,1,1,1),
(13,13,3,3,0,2,1,1,1);
GO

