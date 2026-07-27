USE [master]
GO
/****** Objeto: Database [Talento_Humano] Fecha de script: 26/7/2026 22:38:25 ******/
CREATE DATABASE [Talento_Humano]
 CONTAINMENT = NONE
 ON  PRIMARY 
( NAME = N'Talento_Humano', FILENAME = N'C:\Program Files\Microsoft SQL Server\MSSQL16.VICTUS\MSSQL\DATA\Talento_Humano.mdf' , SIZE = 73728KB , MAXSIZE = UNLIMITED, FILEGROWTH = 65536KB )
 LOG ON 
( NAME = N'Talento_Humano_log', FILENAME = N'C:\Program Files\Microsoft SQL Server\MSSQL16.VICTUS\MSSQL\DATA\Talento_Humano_log.ldf' , SIZE = 73728KB , MAXSIZE = 2048GB , FILEGROWTH = 65536KB )
GO
ALTER DATABASE [Talento_Humano] SET COMPATIBILITY_LEVEL = 120
GO
IF (1 = FULLTEXTSERVICEPROPERTY('IsFullTextInstalled'))
begin
EXEC [Talento_Humano].[dbo].[sp_fulltext_database] @action = 'enable'
end
GO
ALTER DATABASE [Talento_Humano] SET ANSI_NULL_DEFAULT OFF 
GO
ALTER DATABASE [Talento_Humano] SET ANSI_NULLS OFF 
GO
ALTER DATABASE [Talento_Humano] SET ANSI_PADDING OFF 
GO
ALTER DATABASE [Talento_Humano] SET ANSI_WARNINGS OFF 
GO
ALTER DATABASE [Talento_Humano] SET ARITHABORT OFF 
GO
ALTER DATABASE [Talento_Humano] SET AUTO_CLOSE OFF 
GO
ALTER DATABASE [Talento_Humano] SET AUTO_SHRINK OFF 
GO
ALTER DATABASE [Talento_Humano] SET AUTO_UPDATE_STATISTICS ON 
GO
ALTER DATABASE [Talento_Humano] SET CURSOR_CLOSE_ON_COMMIT OFF 
GO
ALTER DATABASE [Talento_Humano] SET CURSOR_DEFAULT  GLOBAL 
GO
ALTER DATABASE [Talento_Humano] SET CONCAT_NULL_YIELDS_NULL OFF 
GO
ALTER DATABASE [Talento_Humano] SET NUMERIC_ROUNDABORT OFF 
GO
ALTER DATABASE [Talento_Humano] SET QUOTED_IDENTIFIER OFF 
GO
ALTER DATABASE [Talento_Humano] SET RECURSIVE_TRIGGERS OFF 
GO
ALTER DATABASE [Talento_Humano] SET  DISABLE_BROKER 
GO
ALTER DATABASE [Talento_Humano] SET AUTO_UPDATE_STATISTICS_ASYNC OFF 
GO
ALTER DATABASE [Talento_Humano] SET DATE_CORRELATION_OPTIMIZATION OFF 
GO
ALTER DATABASE [Talento_Humano] SET TRUSTWORTHY OFF 
GO
ALTER DATABASE [Talento_Humano] SET ALLOW_SNAPSHOT_ISOLATION OFF 
GO
ALTER DATABASE [Talento_Humano] SET PARAMETERIZATION SIMPLE 
GO
ALTER DATABASE [Talento_Humano] SET READ_COMMITTED_SNAPSHOT OFF 
GO
ALTER DATABASE [Talento_Humano] SET HONOR_BROKER_PRIORITY OFF 
GO
ALTER DATABASE [Talento_Humano] SET RECOVERY FULL 
GO
ALTER DATABASE [Talento_Humano] SET  MULTI_USER 
GO
ALTER DATABASE [Talento_Humano] SET PAGE_VERIFY CHECKSUM  
GO
ALTER DATABASE [Talento_Humano] SET DB_CHAINING OFF 
GO
ALTER DATABASE [Talento_Humano] SET FILESTREAM( NON_TRANSACTED_ACCESS = OFF ) 
GO
ALTER DATABASE [Talento_Humano] SET TARGET_RECOVERY_TIME = 60 SECONDS 
GO
ALTER DATABASE [Talento_Humano] SET DELAYED_DURABILITY = DISABLED
GO
USE [Talento_Humano]
GO
/****** Objeto: Table [dbo].[th_empleados] Fecha de script: 26/7/2026 22:38:25 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[th_empleados](
	[empleado_id] [int] IDENTITY(1,1) NOT NULL,
	[tipo_identificacion] [varchar](20) NULL,
	[identificacion] [varchar](20) NOT NULL,
	[nombres] [varchar](100) NULL,
	[apellidos] [varchar](100) NULL,
	[fecha_nacimiento] [date] NULL,
	[sexo] [char](1) NULL,
	[estado_civil] [varchar](30) NULL,
	[nacionalidad] [varchar](50) NULL,
	[unidad_id] [int] NULL,
	[puesto_id] [int] NULL,
	[fecha_ingreso] [date] NULL,
	[sueldo_rmu] [decimal](10, 2) NULL,
	[correo_institucional] [varchar](100) NULL,
	[correo_personal] [varchar](100) NULL,
	[telefono_movil] [varchar](20) NULL,
	[ciudad_residencia] [varchar](50) NULL,
	[direccion_domiciliaria] [varchar](max) NULL,
	[cuenta_bancaria] [varchar](50) NULL,
	[codigo_iess] [varchar](30) NULL,
	[estado] [int] NULL,
	[fecha_creacion] [datetime] NULL,
	[cargas_familiares] [int] NULL,
	[tipo_cuenta_bancaria] [varchar](50) NULL,
	[numero_cuenta_bancaria] [varchar](50) NULL,
	[institucion_bancaria] [varchar](100) NULL,
	[tipo_contrato] [varchar](100) NULL,
	[ruta_foto] [varchar](300) NULL,
	[observaciones] [varchar](max) NULL,
	[telefono_convencional] [varchar](20) NULL,
	[contacto_emergencia] [varchar](150) NULL,
	[emergencia_relacion] [varchar](50) NULL,
	[tel_emergencia] [varchar](20) NULL,
	[nivel_estudio] [varchar](80) NULL,
	[titulo] [varchar](150) NULL,
	[jornada] [varchar](30) NULL,
	[condicion_especial] [varchar](50) NULL,
	[tipo_discapacidad] [varchar](80) NULL,
	[porcentaje_discapacidad] [decimal](5, 2) NULL,
	[tipo_sangre] [varchar](10) NULL,
	[fecha_salida] [date] NULL,
	[cod_emplea] [varchar](20) NULL,
	[num_iess] [varchar](30) NULL,
	[cedula]  AS ([identificacion]) PERSISTED NOT NULL,
	[remuneracion_mensual]  AS ([sueldo_rmu]) PERSISTED,
PRIMARY KEY CLUSTERED 
(
	[empleado_id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY],
UNIQUE NONCLUSTERED 
(
	[identificacion] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
) ON [PRIMARY] TEXTIMAGE_ON [PRIMARY]
GO
/****** Objeto: View [dbo].[view_th_empleados] Fecha de script: 26/7/2026 22:38:25 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO

-- 1. Consultar todos los empleados básicos
CREATE VIEW [dbo].[view_th_empleados] AS
SELECT 
    empleado_id AS id, 
    identificacion AS cedula, 
    nombres,
    apellidos,
    correo_institucional,
    estado
FROM th_empleados;
GO
/****** Objeto: View [dbo].[view_th_idempleados] Fecha de script: 26/7/2026 22:38:25 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO

-- 2. Consulta básica para buscar por ID en PHP
CREATE VIEW [dbo].[view_th_idempleados] AS
SELECT 
    empleado_id AS id, 
    identificacion AS cedula, 
    nombres,
    apellidos,
    correo_institucional,
    estado
FROM th_empleados;
GO
/****** Objeto: View [dbo].[view_th_cedempleados] Fecha de script: 26/7/2026 22:38:25 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO

-- 3. Consulta básica para buscar por Cédula en PHP
CREATE VIEW [dbo].[view_th_cedempleados] AS
SELECT 
    empleado_id AS id, 
    identificacion AS cedula, 
    nombres,
    apellidos,
    correo_institucional,
    estado
FROM th_empleados;
GO
/****** Objeto: Table [dbo].[th_puestos] Fecha de script: 26/7/2026 22:38:25 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[th_puestos](
	[puesto_id] [int] IDENTITY(1,1) NOT NULL,
	[codigo_puesto] [varchar](20) NOT NULL,
	[nombre_puesto] [varchar](150) NOT NULL,
	[remuneracion_unificada] [decimal](10, 2) NOT NULL,
	[activo] [bit] NULL,
PRIMARY KEY CLUSTERED 
(
	[puesto_id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY],
UNIQUE NONCLUSTERED 
(
	[codigo_puesto] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
) ON [PRIMARY]
GO
/****** Objeto: Table [dbo].[th_unidades_organizacionales] Fecha de script: 26/7/2026 22:38:25 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[th_unidades_organizacionales](
	[unidad_id] [int] IDENTITY(1,1) NOT NULL,
	[codigo_uorg] [varchar](20) NOT NULL,
	[nombre_unidad] [varchar](150) NOT NULL,
	[tipo_proceso] [varchar](50) NOT NULL,
	[unidad_padre_id] [int] NULL,
	[activo] [bit] NULL,
	[sucedido_por_id] [int] NULL,
	[fecha_fin] [date] NULL,
	[fecha_inicio] [date] NULL,
PRIMARY KEY CLUSTERED 
(
	[unidad_id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
) ON [PRIMARY]
GO
/****** Objeto: View [dbo].[view_th_iddatosempledo] Fecha de script: 26/7/2026 22:38:25 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
-- =============================================================================
-- 7. VISTA view_th_iddatosempledo – ACTUALIZADA
--    Agrega las columnas requeridas por EmpleadoController::exportarCsv()
-- =============================================================================
CREATE   VIEW [dbo].[view_th_iddatosempledo] AS
SELECT
    e.empleado_id,
    e.empleado_id               AS id,
    e.identificacion            AS cedula,
    e.apellidos,
    e.nombres,
    ISNULL(p.nombre_puesto, '')  AS cargo,
    ISNULL(u.nombre_unidad, '')  AS direccion_area,
    e.correo_institucional,
    e.estado,
    ISNULL(e.cargas_familiares, 0)          AS cargas_familiares,
    e.tipo_cuenta_bancaria,
    e.numero_cuenta_bancaria,
    e.institucion_bancaria,
    -- Columnas adicionales para exportarCsv()
    e.tipo_contrato,
    e.sueldo_rmu                AS remuneracion_mensual,
    e.sueldo_rmu,
    e.fecha_ingreso,
    e.fecha_nacimiento,
    e.telefono_movil,
    e.ciudad_residencia,
    e.ruta_foto
FROM [dbo].[th_empleados] e
LEFT JOIN [dbo].[th_puestos] p
       ON e.puesto_id = p.puesto_id
LEFT JOIN [dbo].[th_unidades_organizacionales] u
       ON e.unidad_id = u.unidad_id;
GO
/****** Objeto: View [dbo].[view_th_ceddatosempledo] Fecha de script: 26/7/2026 22:38:25 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO

-- 5. Consulta Completa para buscar por Cédula en PHP
CREATE VIEW [dbo].[view_th_ceddatosempledo] AS
SELECT 
    e.empleado_id AS id,
    e.identificacion AS cedula,
    e.apellidos,
    e.nombres,
    p.nombre_puesto AS cargo,
    u.nombre_unidad AS direccion_area,
    e.correo_institucional,
    e.estado
FROM th_empleados e
LEFT JOIN th_puestos p ON e.puesto_id = p.puesto_id
LEFT JOIN th_unidades_organizacionales u ON e.unidad_id = u.unidad_id;
GO
/****** Objeto: Table [dbo].[th_historial_laboral] Fecha de script: 26/7/2026 22:38:25 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[th_historial_laboral](
	[historial_id] [int] IDENTITY(1,1) NOT NULL,
	[empleado_id] [int] NOT NULL,
	[puesto_id] [int] NOT NULL,
	[unidad_id] [int] NOT NULL,
	[fecha_desde] [date] NOT NULL,
	[fecha_hasta] [date] NULL,
	[observaciones] [varchar](500) NULL,
	[usuario_crea] [varchar](50) NULL,
	[fecha_creacion] [datetime] NULL,
PRIMARY KEY CLUSTERED 
(
	[historial_id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
) ON [PRIMARY]
GO
/****** Objeto: View [dbo].[vw_th_reporte_historial_jerarquico] Fecha de script: 26/7/2026 22:38:25 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
-- Vista vw_th_reporte_historial_jerarquico
CREATE   VIEW [dbo].[vw_th_reporte_historial_jerarquico] AS
SELECT
    e.empleado_id,
    e.identificacion AS cedula,
    e.apellidos + ' ' + e.nombres AS funcionario,
    p.codigo_puesto,
    p.nombre_puesto,
    u.nombre_unidad AS departamento_historico,
    CASE WHEN u_padre.unidad_id IS NOT NULL THEN u_padre.nombre_unidad ELSE u.nombre_unidad END AS direccion_padre,
    CASE WHEN u_padre.unidad_id IS NOT NULL THEN u.nombre_unidad ELSE NULL END AS sub_area,
    ISNULL(u_nueva.nombre_unidad, u.nombre_unidad) AS direccion_actual_unificada,
    ISNULL(u_nueva.tipo_proceso,  u.tipo_proceso)  AS tipo_proceso,
    h.fecha_desde,
    h.fecha_hasta,
    DATEDIFF(year, h.fecha_desde, ISNULL(h.fecha_hasta, GETDATE())) AS anios_permanencia,
    DATEDIFF(day, CAST(GETDATE() AS DATE),
        DATEFROMPARTS(
            YEAR(GETDATE()) + CASE
                WHEN DATEFROMPARTS(YEAR(GETDATE()), MONTH(e.fecha_nacimiento), DAY(e.fecha_nacimiento)) < CAST(GETDATE() AS DATE)
                THEN 1 ELSE 0 END,
            MONTH(e.fecha_nacimiento), DAY(e.fecha_nacimiento)
        )
    ) AS dias_para_cumpleanos
FROM [dbo].[th_historial_laboral] h
JOIN  [dbo].[th_empleados]                 e      ON h.empleado_id = e.empleado_id
JOIN  [dbo].[th_puestos]                   p      ON h.puesto_id   = p.puesto_id
JOIN  [dbo].[th_unidades_organizacionales] u      ON h.unidad_id   = u.unidad_id
LEFT JOIN [dbo].[th_unidades_organizacionales] u_padre ON u.unidad_padre_id = u_padre.unidad_id
LEFT JOIN [dbo].[th_unidades_organizacionales] u_nueva ON u.sucedido_por_id = u_nueva.unidad_id;
GO
/****** Objeto: Table [dbo].[th_acciones_personal] Fecha de script: 26/7/2026 22:38:25 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[th_acciones_personal](
	[accion_id] [int] IDENTITY(1,1) NOT NULL,
	[numero_accion] [varchar](50) NOT NULL,
	[fecha_elaboracion] [date] NOT NULL,
	[empleado_id] [int] NOT NULL,
	[tipo_accion] [varchar](100) NOT NULL,
	[fecha_rige_desde] [date] NOT NULL,
	[fecha_rige_hasta] [date] NULL,
	[explicacion_legal] [varchar](max) NOT NULL,
	[actual_unidad_id] [int] NULL,
	[actual_puesto_id] [int] NULL,
	[actual_lugar_trabajo] [varchar](150) NULL,
	[actual_remuneracion] [decimal](10, 2) NULL,
	[actual_partida_presupuestaria] [varchar](50) NULL,
	[propuesta_unidad_id] [int] NULL,
	[propuesta_puesto_id] [int] NULL,
	[propuesta_lugar_trabajo] [varchar](150) NULL,
	[propuesta_remuneracion] [decimal](10, 2) NULL,
	[propuesta_partida_presupuestaria] [varchar](50) NULL,
	[estado_documento] [varchar](20) NULL,
	[usuario_crea] [varchar](50) NULL,
	[fecha_creacion] [datetime] NULL,
PRIMARY KEY CLUSTERED 
(
	[accion_id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY],
UNIQUE NONCLUSTERED 
(
	[numero_accion] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
) ON [PRIMARY] TEXTIMAGE_ON [PRIMARY]
GO
/****** Objeto: View [dbo].[vw_th_acciones_resumen] Fecha de script: 26/7/2026 22:38:25 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
-- Vista vw_th_acciones_resumen
CREATE   VIEW [dbo].[vw_th_acciones_resumen] AS
SELECT
    ap.accion_id,
    ap.numero_accion,
    ap.fecha_elaboracion,
    ap.tipo_accion,
    ap.estado_documento,
    ap.fecha_rige_desde,
    ap.fecha_rige_hasta,
    e.identificacion               AS cedula_pasaporte,
    e.apellidos + ' ' + e.nombres AS apellidos_nombres,
    p_act.nombre_puesto            AS actual_puesto,
    ap.actual_remuneracion,
    p_prop.nombre_puesto           AS propuesta_puesto,
    ap.propuesta_remuneracion,
    (ISNULL(ap.propuesta_remuneracion, 0) - ISNULL(ap.actual_remuneracion, 0)) AS diferencia_remuneracion,
    ap.usuario_crea,
    ap.fecha_elaboracion           AS fecha_creacion
FROM [dbo].[th_acciones_personal] ap
JOIN  [dbo].[th_empleados]  e        ON e.empleado_id      = ap.empleado_id
LEFT JOIN [dbo].[th_puestos] p_act   ON ap.actual_puesto_id   = p_act.puesto_id
LEFT JOIN [dbo].[th_puestos] p_prop  ON ap.propuesta_puesto_id = p_prop.puesto_id;
GO
/****** Objeto: Table [dbo].[th_acciones_personal_old] Fecha de script: 26/7/2026 22:38:25 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[th_acciones_personal_old](
	[accion_id] [int] IDENTITY(1,1) NOT NULL,
	[empleado_id] [int] NOT NULL,
	[tipo_accion] [varchar](50) NOT NULL,
	[puesto_id] [int] NOT NULL,
	[unidad_id] [int] NOT NULL,
	[fecha_rige_desde] [date] NOT NULL,
	[fecha_rige_hasta] [date] NULL,
	[motivo] [varchar](max) NOT NULL,
	[estado] [varchar](20) NULL,
PRIMARY KEY CLUSTERED 
(
	[accion_id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
) ON [PRIMARY] TEXTIMAGE_ON [PRIMARY]
GO
/****** Objeto: Table [dbo].[th_logs_auditoria] Fecha de script: 26/7/2026 22:38:25 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[th_logs_auditoria](
	[log_id] [int] IDENTITY(1,1) NOT NULL,
	[fecha_hora] [datetime] NOT NULL,
	[usuario] [varchar](50) NOT NULL,
	[modulo] [varchar](50) NOT NULL,
	[accion] [varchar](50) NOT NULL,
	[descripcion_detalle] [varchar](500) NOT NULL,
	[direccion_ip] [varchar](45) NOT NULL,
PRIMARY KEY CLUSTERED 
(
	[log_id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
) ON [PRIMARY]
GO
/****** Objeto: Table [dbo].[th_modulos] Fecha de script: 26/7/2026 22:38:25 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[th_modulos](
	[modulo_id] [int] IDENTITY(1,1) NOT NULL,
	[nombre_modulo] [varchar](100) NOT NULL,
	[ruta_frontend] [varchar](100) NULL,
PRIMARY KEY CLUSTERED 
(
	[modulo_id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY],
UNIQUE NONCLUSTERED 
(
	[nombre_modulo] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
) ON [PRIMARY]
GO
/****** Objeto: Table [dbo].[th_parametros] Fecha de script: 26/7/2026 22:38:25 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[th_parametros](
	[parametro_id] [varchar](50) NOT NULL,
	[valor] [varchar](200) NOT NULL,
	[descripcion] [varchar](300) NULL,
	[fecha_vigencia] [date] NOT NULL,
	[usuario_crea] [varchar](50) NULL,
	[fecha_creacion] [datetime] NULL,
PRIMARY KEY CLUSTERED 
(
	[parametro_id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
) ON [PRIMARY]
GO
/****** Objeto: Table [dbo].[th_permisos_rol] Fecha de script: 26/7/2026 22:38:25 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[th_permisos_rol](
	[permiso_id] [int] IDENTITY(1,1) NOT NULL,
	[rol_id] [int] NOT NULL,
	[modulo_id] [int] NOT NULL,
	[puede_visualizar] [bit] NULL,
	[puede_crear] [bit] NULL,
	[puede_editar] [bit] NULL,
	[puede_eliminar] [bit] NULL,
PRIMARY KEY CLUSTERED 
(
	[permiso_id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
) ON [PRIMARY]
GO
/****** Objeto: Table [dbo].[th_roles] Fecha de script: 26/7/2026 22:38:25 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[th_roles](
	[rol_id] [int] IDENTITY(1,1) NOT NULL,
	[nombre_rol] [varchar](50) NOT NULL,
	[estado] [bit] NULL,
PRIMARY KEY CLUSTERED 
(
	[rol_id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY],
UNIQUE NONCLUSTERED 
(
	[nombre_rol] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
) ON [PRIMARY]
GO
/****** Objeto: Table [dbo].[th_titulos] Fecha de script: 26/7/2026 22:38:25 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[th_titulos](
	[titulo_id] [int] IDENTITY(1,1) NOT NULL,
	[empleado_id] [int] NOT NULL,
	[nivel_instruccion] [varchar](50) NOT NULL,
	[nombre_titulo] [varchar](150) NOT NULL,
	[institucion_educativa] [varchar](150) NOT NULL,
	[numero_senescyt] [varchar](50) NULL,
	[estado] [bit] NULL,
PRIMARY KEY CLUSTERED 
(
	[titulo_id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
) ON [PRIMARY]
GO
/****** Objeto: Table [dbo].[th_usuarios_sistema] Fecha de script: 26/7/2026 22:38:25 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[th_usuarios_sistema](
	[usuario_id] [int] IDENTITY(1,1) NOT NULL,
	[usuario] [varchar](50) NOT NULL,
	[password_hash] [varchar](255) NOT NULL,
	[correo] [varchar](100) NOT NULL,
	[nombre] [varchar](150) NOT NULL,
	[empleado_id] [int] NULL,
	[rol_id] [int] NOT NULL,
	[estado] [bit] NULL,
	[ultimo_acceso] [datetime] NULL,
	[fecha_creacion] [datetime] NULL,
PRIMARY KEY CLUSTERED 
(
	[usuario_id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY],
UNIQUE NONCLUSTERED 
(
	[usuario] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
) ON [PRIMARY]
GO
/****** Objeto: Index [IX_Historial_Empleado] Fecha de script: 26/7/2026 22:38:25 ******/
CREATE NONCLUSTERED INDEX [IX_Historial_Empleado] ON [dbo].[th_historial_laboral]
(
	[empleado_id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, SORT_IN_TEMPDB = OFF, DROP_EXISTING = OFF, ONLINE = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
GO
/****** Objeto: Index [IX_Historial_Fechas] Fecha de script: 26/7/2026 22:38:25 ******/
CREATE NONCLUSTERED INDEX [IX_Historial_Fechas] ON [dbo].[th_historial_laboral]
(
	[fecha_desde] ASC,
	[fecha_hasta] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, SORT_IN_TEMPDB = OFF, DROP_EXISTING = OFF, ONLINE = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
GO
/****** Objeto: Index [IX_Historial_Unidad] Fecha de script: 26/7/2026 22:38:25 ******/
CREATE NONCLUSTERED INDEX [IX_Historial_Unidad] ON [dbo].[th_historial_laboral]
(
	[unidad_id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, SORT_IN_TEMPDB = OFF, DROP_EXISTING = OFF, ONLINE = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
GO
/****** Objeto: Index [IX_Logs_FechaHora] Fecha de script: 26/7/2026 22:38:25 ******/
CREATE NONCLUSTERED INDEX [IX_Logs_FechaHora] ON [dbo].[th_logs_auditoria]
(
	[fecha_hora] DESC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, SORT_IN_TEMPDB = OFF, DROP_EXISTING = OFF, ONLINE = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
GO
SET ANSI_PADDING ON
GO
/****** Objeto: Index [IX_Logs_Modulo] Fecha de script: 26/7/2026 22:38:25 ******/
CREATE NONCLUSTERED INDEX [IX_Logs_Modulo] ON [dbo].[th_logs_auditoria]
(
	[modulo] ASC,
	[accion] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, SORT_IN_TEMPDB = OFF, DROP_EXISTING = OFF, ONLINE = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
GO
SET ANSI_PADDING ON
GO
/****** Objeto: Index [IX_Logs_Usuario] Fecha de script: 26/7/2026 22:38:25 ******/
CREATE NONCLUSTERED INDEX [IX_Logs_Usuario] ON [dbo].[th_logs_auditoria]
(
	[usuario] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, SORT_IN_TEMPDB = OFF, DROP_EXISTING = OFF, ONLINE = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
GO
ALTER TABLE [dbo].[th_acciones_personal] ADD  DEFAULT ('Borrador') FOR [estado_documento]
GO
ALTER TABLE [dbo].[th_acciones_personal] ADD  DEFAULT (getdate()) FOR [fecha_creacion]
GO
ALTER TABLE [dbo].[th_acciones_personal_old] ADD  DEFAULT ('Generada') FOR [estado]
GO
ALTER TABLE [dbo].[th_empleados] ADD  DEFAULT ('Cédula') FOR [tipo_identificacion]
GO
ALTER TABLE [dbo].[th_empleados] ADD  DEFAULT ((1)) FOR [estado]
GO
ALTER TABLE [dbo].[th_empleados] ADD  DEFAULT (getdate()) FOR [fecha_creacion]
GO
ALTER TABLE [dbo].[th_empleados] ADD  CONSTRAINT [DF_th_empleados_cargas]  DEFAULT ((0)) FOR [cargas_familiares]
GO
ALTER TABLE [dbo].[th_empleados] ADD  CONSTRAINT [DF_Empleado_Foto]  DEFAULT ('public/img/default_avatar.png') FOR [ruta_foto]
GO
ALTER TABLE [dbo].[th_historial_laboral] ADD  DEFAULT (getdate()) FOR [fecha_creacion]
GO
ALTER TABLE [dbo].[th_logs_auditoria] ADD  DEFAULT (getdate()) FOR [fecha_hora]
GO
ALTER TABLE [dbo].[th_logs_auditoria] ADD  DEFAULT ('0.0.0.0') FOR [direccion_ip]
GO
ALTER TABLE [dbo].[th_parametros] ADD  DEFAULT (getdate()) FOR [fecha_vigencia]
GO
ALTER TABLE [dbo].[th_parametros] ADD  DEFAULT (getdate()) FOR [fecha_creacion]
GO
ALTER TABLE [dbo].[th_permisos_rol] ADD  DEFAULT ((0)) FOR [puede_visualizar]
GO
ALTER TABLE [dbo].[th_permisos_rol] ADD  DEFAULT ((0)) FOR [puede_crear]
GO
ALTER TABLE [dbo].[th_permisos_rol] ADD  DEFAULT ((0)) FOR [puede_editar]
GO
ALTER TABLE [dbo].[th_permisos_rol] ADD  DEFAULT ((0)) FOR [puede_eliminar]
GO
ALTER TABLE [dbo].[th_puestos] ADD  DEFAULT ((1)) FOR [activo]
GO
ALTER TABLE [dbo].[th_roles] ADD  DEFAULT ((1)) FOR [estado]
GO
ALTER TABLE [dbo].[th_titulos] ADD  DEFAULT ((1)) FOR [estado]
GO
ALTER TABLE [dbo].[th_unidades_organizacionales] ADD  DEFAULT ((1)) FOR [activo]
GO
ALTER TABLE [dbo].[th_usuarios_sistema] ADD  DEFAULT ((1)) FOR [estado]
GO
ALTER TABLE [dbo].[th_usuarios_sistema] ADD  DEFAULT (getdate()) FOR [fecha_creacion]
GO
ALTER TABLE [dbo].[th_acciones_personal]  WITH CHECK ADD  CONSTRAINT [FK_Accion_Empleado] FOREIGN KEY([empleado_id])
REFERENCES [dbo].[th_empleados] ([empleado_id])
GO
ALTER TABLE [dbo].[th_acciones_personal] CHECK CONSTRAINT [FK_Accion_Empleado]
GO
ALTER TABLE [dbo].[th_acciones_personal]  WITH CHECK ADD  CONSTRAINT [FK_Accion_Puesto_Actual] FOREIGN KEY([actual_puesto_id])
REFERENCES [dbo].[th_puestos] ([puesto_id])
GO
ALTER TABLE [dbo].[th_acciones_personal] CHECK CONSTRAINT [FK_Accion_Puesto_Actual]
GO
ALTER TABLE [dbo].[th_acciones_personal]  WITH CHECK ADD  CONSTRAINT [FK_Accion_Puesto_Prop] FOREIGN KEY([propuesta_puesto_id])
REFERENCES [dbo].[th_puestos] ([puesto_id])
GO
ALTER TABLE [dbo].[th_acciones_personal] CHECK CONSTRAINT [FK_Accion_Puesto_Prop]
GO
ALTER TABLE [dbo].[th_acciones_personal]  WITH CHECK ADD  CONSTRAINT [FK_Accion_Unidad_Actual] FOREIGN KEY([actual_unidad_id])
REFERENCES [dbo].[th_unidades_organizacionales] ([unidad_id])
GO
ALTER TABLE [dbo].[th_acciones_personal] CHECK CONSTRAINT [FK_Accion_Unidad_Actual]
GO
ALTER TABLE [dbo].[th_acciones_personal]  WITH CHECK ADD  CONSTRAINT [FK_Accion_Unidad_Prop] FOREIGN KEY([propuesta_unidad_id])
REFERENCES [dbo].[th_unidades_organizacionales] ([unidad_id])
GO
ALTER TABLE [dbo].[th_acciones_personal] CHECK CONSTRAINT [FK_Accion_Unidad_Prop]
GO
ALTER TABLE [dbo].[th_acciones_personal_old]  WITH CHECK ADD  CONSTRAINT [FK_Acciones_Empleado] FOREIGN KEY([empleado_id])
REFERENCES [dbo].[th_empleados] ([empleado_id])
ON DELETE CASCADE
GO
ALTER TABLE [dbo].[th_acciones_personal_old] CHECK CONSTRAINT [FK_Acciones_Empleado]
GO
ALTER TABLE [dbo].[th_acciones_personal_old]  WITH CHECK ADD  CONSTRAINT [FK_Acciones_Puesto] FOREIGN KEY([puesto_id])
REFERENCES [dbo].[th_puestos] ([puesto_id])
GO
ALTER TABLE [dbo].[th_acciones_personal_old] CHECK CONSTRAINT [FK_Acciones_Puesto]
GO
ALTER TABLE [dbo].[th_acciones_personal_old]  WITH CHECK ADD  CONSTRAINT [FK_Acciones_Unidad] FOREIGN KEY([unidad_id])
REFERENCES [dbo].[th_unidades_organizacionales] ([unidad_id])
GO
ALTER TABLE [dbo].[th_acciones_personal_old] CHECK CONSTRAINT [FK_Acciones_Unidad]
GO
ALTER TABLE [dbo].[th_empleados]  WITH CHECK ADD  CONSTRAINT [FK_Empleado_Puesto] FOREIGN KEY([puesto_id])
REFERENCES [dbo].[th_puestos] ([puesto_id])
GO
ALTER TABLE [dbo].[th_empleados] CHECK CONSTRAINT [FK_Empleado_Puesto]
GO
ALTER TABLE [dbo].[th_empleados]  WITH CHECK ADD  CONSTRAINT [FK_Empleado_Unidad] FOREIGN KEY([unidad_id])
REFERENCES [dbo].[th_unidades_organizacionales] ([unidad_id])
GO
ALTER TABLE [dbo].[th_empleados] CHECK CONSTRAINT [FK_Empleado_Unidad]
GO
ALTER TABLE [dbo].[th_historial_laboral]  WITH CHECK ADD  CONSTRAINT [FK_Historial_Empleado] FOREIGN KEY([empleado_id])
REFERENCES [dbo].[th_empleados] ([empleado_id])
ON UPDATE CASCADE
GO
ALTER TABLE [dbo].[th_historial_laboral] CHECK CONSTRAINT [FK_Historial_Empleado]
GO
ALTER TABLE [dbo].[th_historial_laboral]  WITH CHECK ADD  CONSTRAINT [FK_Historial_Puesto] FOREIGN KEY([puesto_id])
REFERENCES [dbo].[th_puestos] ([puesto_id])
GO
ALTER TABLE [dbo].[th_historial_laboral] CHECK CONSTRAINT [FK_Historial_Puesto]
GO
ALTER TABLE [dbo].[th_historial_laboral]  WITH CHECK ADD  CONSTRAINT [FK_Historial_Unidad] FOREIGN KEY([unidad_id])
REFERENCES [dbo].[th_unidades_organizacionales] ([unidad_id])
GO
ALTER TABLE [dbo].[th_historial_laboral] CHECK CONSTRAINT [FK_Historial_Unidad]
GO
ALTER TABLE [dbo].[th_permisos_rol]  WITH CHECK ADD  CONSTRAINT [FK_Permiso_Modulo] FOREIGN KEY([modulo_id])
REFERENCES [dbo].[th_modulos] ([modulo_id])
GO
ALTER TABLE [dbo].[th_permisos_rol] CHECK CONSTRAINT [FK_Permiso_Modulo]
GO
ALTER TABLE [dbo].[th_permisos_rol]  WITH CHECK ADD  CONSTRAINT [FK_Permiso_Rol] FOREIGN KEY([rol_id])
REFERENCES [dbo].[th_roles] ([rol_id])
ON DELETE CASCADE
GO
ALTER TABLE [dbo].[th_permisos_rol] CHECK CONSTRAINT [FK_Permiso_Rol]
GO
ALTER TABLE [dbo].[th_titulos]  WITH CHECK ADD  CONSTRAINT [FK_Titulos_Empleado] FOREIGN KEY([empleado_id])
REFERENCES [dbo].[th_empleados] ([empleado_id])
ON DELETE CASCADE
GO
ALTER TABLE [dbo].[th_titulos] CHECK CONSTRAINT [FK_Titulos_Empleado]
GO
ALTER TABLE [dbo].[th_unidades_organizacionales]  WITH CHECK ADD  CONSTRAINT [FK_Unidad_Padre] FOREIGN KEY([unidad_padre_id])
REFERENCES [dbo].[th_unidades_organizacionales] ([unidad_id])
GO
ALTER TABLE [dbo].[th_unidades_organizacionales] CHECK CONSTRAINT [FK_Unidad_Padre]
GO
ALTER TABLE [dbo].[th_unidades_organizacionales]  WITH CHECK ADD  CONSTRAINT [FK_Unidad_Sucedida] FOREIGN KEY([sucedido_por_id])
REFERENCES [dbo].[th_unidades_organizacionales] ([unidad_id])
GO
ALTER TABLE [dbo].[th_unidades_organizacionales] CHECK CONSTRAINT [FK_Unidad_Sucedida]
GO
ALTER TABLE [dbo].[th_usuarios_sistema]  WITH CHECK ADD  CONSTRAINT [FK_Usuario_Empleado] FOREIGN KEY([empleado_id])
REFERENCES [dbo].[th_empleados] ([empleado_id])
GO
ALTER TABLE [dbo].[th_usuarios_sistema] CHECK CONSTRAINT [FK_Usuario_Empleado]
GO
ALTER TABLE [dbo].[th_usuarios_sistema]  WITH CHECK ADD  CONSTRAINT [FK_Usuario_Rol] FOREIGN KEY([rol_id])
REFERENCES [dbo].[th_roles] ([rol_id])
GO
ALTER TABLE [dbo].[th_usuarios_sistema] CHECK CONSTRAINT [FK_Usuario_Rol]
GO
ALTER TABLE [dbo].[th_empleados]  WITH CHECK ADD CHECK  (([sexo]='F' OR [sexo]='M'))
GO
/****** Objeto: StoredProcedure [dbo].[sp_th_eliminar_empleado] Fecha de script: 26/7/2026 22:38:25 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
-- SP sp_th_eliminar_empleado
CREATE   PROCEDURE [dbo].[sp_th_eliminar_empleado]
    @id INT
AS
BEGIN
    SET NOCOUNT ON;
    BEGIN TRY
        UPDATE [dbo].[th_empleados] SET estado = 0 WHERE empleado_id = @id;
        SELECT @@ROWCOUNT AS filas_afectadas, 1 AS exito, 'Empleado dado de baja.' AS mensaje;
    END TRY
    BEGIN CATCH
        SELECT 0 AS filas_afectadas, 0 AS exito, ERROR_MESSAGE() AS mensaje;
    END CATCH;
END;
GO
/****** Objeto: StoredProcedure [dbo].[sp_th_guardar_empleado] Fecha de script: 26/7/2026 22:38:25 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
-- SP sp_th_guardar_empleado
CREATE   PROCEDURE [dbo].[sp_th_guardar_empleado]
    @cedula             VARCHAR(20),
    @nombres            VARCHAR(100),
    @fecha_nac          DATE         = NULL,
    @condicion          VARCHAR(50)  = 'Ninguna',
    @tipo_disc          VARCHAR(80)  = NULL,
    @porcentaje_disc    DECIMAL(5,2) = NULL,
    @sexo               CHAR(1)      = NULL,
    @estado_civil       VARCHAR(30)  = NULL,
    @nacionalidad       VARCHAR(50)  = NULL,
    @tipo_sangre        VARCHAR(10)  = NULL,
    @depto              INT          = NULL,
    @puesto             INT          = NULL,
    @tipo_contrato      VARCHAR(100) = NULL,
    @fecha_ing          DATE         = NULL,
    @sueldo             DECIMAL(10,2)= NULL,
    @jornada            VARCHAR(30)  = 'Completa',
    @correo             VARCHAR(100) = NULL,
    @celular            VARCHAR(20)  = NULL,
    @convencional       VARCHAR(20)  = NULL,
    @ciudad             VARCHAR(50)  = NULL,
    @direccion          VARCHAR(MAX) = NULL,
    @contacto_emerg     VARCHAR(150) = NULL,
    @parentesco         VARCHAR(50)  = NULL,
    @tel_emerg          VARCHAR(20)  = NULL,
    @nivel_estudio      VARCHAR(80)  = NULL,
    @titulo             VARCHAR(150) = NULL,
    @iess               VARCHAR(30)  = NULL,
    @foto               VARCHAR(300) = 'public/img/default_avatar.png',
    @obs                VARCHAR(MAX) = NULL
AS
BEGIN
    SET NOCOUNT ON;
    BEGIN TRY
        INSERT INTO [dbo].[th_empleados] (
            identificacion, apellidos, nombres, fecha_nacimiento,
            sexo, estado_civil, nacionalidad,
            unidad_id, puesto_id, fecha_ingreso, sueldo_rmu,
            correo_institucional, telefono_movil, telefono_convencional,
            ciudad_residencia, direccion_domiciliaria,
            codigo_iess, ruta_foto, observaciones,
            tipo_contrato, jornada, condicion_especial,
            tipo_discapacidad, porcentaje_discapacidad, tipo_sangre,
            contacto_emergencia, emergencia_relacion, tel_emergencia,
            nivel_estudio, titulo, estado, cargas_familiares, fecha_creacion
        ) VALUES (
            @cedula, @nombres, '', @fecha_nac,
            @sexo, @estado_civil, @nacionalidad,
            @depto, @puesto, @fecha_ing, @sueldo,
            ISNULL(@correo,''), ISNULL(@celular,''), @convencional,
            ISNULL(@ciudad,''), ISNULL(@direccion,''),
            @iess, ISNULL(@foto,'public/img/default_avatar.png'), @obs,
            @tipo_contrato, @jornada, @condicion,
            @tipo_disc, @porcentaje_disc, @tipo_sangre,
            @contacto_emerg, @parentesco, @tel_emerg,
            @nivel_estudio, @titulo, 1, 0, GETDATE()
        );
        SELECT SCOPE_IDENTITY() AS nuevo_id, 1 AS exito, 'Empleado guardado correctamente.' AS mensaje;
    END TRY
    BEGIN CATCH
        SELECT 0 AS nuevo_id, 0 AS exito, ERROR_MESSAGE() AS mensaje;
    END CATCH;
END;
GO
/****** Objeto: StoredProcedure [dbo].[sp_th_modificar_empleado] Fecha de script: 26/7/2026 22:38:25 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
-- SP sp_th_modificar_empleado
CREATE   PROCEDURE [dbo].[sp_th_modificar_empleado]
    @id                 INT,
    @cedula             VARCHAR(20),
    @nombres            VARCHAR(100),
    @fecha_nac          DATE         = NULL,
    @condicion          VARCHAR(50)  = 'Ninguna',
    @tipo_disc          VARCHAR(80)  = NULL,
    @porcentaje_disc    DECIMAL(5,2) = NULL,
    @sexo               CHAR(1)      = NULL,
    @estado_civil       VARCHAR(30)  = NULL,
    @nacionalidad       VARCHAR(50)  = NULL,
    @tipo_sangre        VARCHAR(10)  = NULL,
    @depto              INT          = NULL,
    @puesto             INT          = NULL,
    @tipo_contrato      VARCHAR(100) = NULL,
    @fecha_ing          DATE         = NULL,
    @sueldo             DECIMAL(10,2)= NULL,
    @jornada            VARCHAR(30)  = 'Completa',
    @correo             VARCHAR(100) = NULL,
    @celular            VARCHAR(20)  = NULL,
    @convencional       VARCHAR(20)  = NULL,
    @ciudad             VARCHAR(50)  = NULL,
    @direccion          VARCHAR(MAX) = NULL,
    @contacto_emerg     VARCHAR(150) = NULL,
    @parentesco         VARCHAR(50)  = NULL,
    @tel_emerg          VARCHAR(20)  = NULL,
    @nivel_estudio      VARCHAR(80)  = NULL,
    @titulo             VARCHAR(150) = NULL,
    @iess               VARCHAR(30)  = NULL,
    @foto               VARCHAR(300) = NULL,
    @obs                VARCHAR(MAX) = NULL
AS
BEGIN
    SET NOCOUNT ON;
    BEGIN TRY
        UPDATE [dbo].[th_empleados] SET
            identificacion          = @cedula,
            fecha_nacimiento        = @fecha_nac,
            sexo                    = @sexo,
            estado_civil            = @estado_civil,
            nacionalidad            = @nacionalidad,
            unidad_id               = @depto,
            puesto_id               = @puesto,
            fecha_ingreso           = @fecha_ing,
            sueldo_rmu              = @sueldo,
            correo_institucional    = ISNULL(@correo, correo_institucional),
            telefono_movil          = ISNULL(@celular, telefono_movil),
            telefono_convencional   = @convencional,
            ciudad_residencia       = ISNULL(@ciudad, ciudad_residencia),
            direccion_domiciliaria  = ISNULL(@direccion, direccion_domiciliaria),
            codigo_iess             = @iess,
            ruta_foto               = ISNULL(@foto, ruta_foto),
            observaciones           = @obs,
            tipo_contrato           = @tipo_contrato,
            jornada                 = @jornada,
            condicion_especial      = @condicion,
            tipo_discapacidad       = @tipo_disc,
            porcentaje_discapacidad = @porcentaje_disc,
            tipo_sangre             = @tipo_sangre,
            contacto_emergencia     = @contacto_emerg,
            emergencia_relacion     = @parentesco,
            tel_emergencia          = @tel_emerg,
            nivel_estudio           = @nivel_estudio,
            titulo                  = @titulo
        WHERE empleado_id = @id;
        SELECT @@ROWCOUNT AS filas_afectadas, 1 AS exito, 'Empleado actualizado.' AS mensaje;
    END TRY
    BEGIN CATCH
        SELECT 0 AS filas_afectadas, 0 AS exito, ERROR_MESSAGE() AS mensaje;
    END CATCH;
END;
GO
/****** Objeto: StoredProcedure [dbo].[sp_th_obtener_siguiente_id] Fecha de script: 26/7/2026 22:38:25 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO

CREATE PROCEDURE [dbo].[sp_th_obtener_siguiente_id]
    @TablaNombre VARCHAR(100),
    @SiguienteID INT OUTPUT
AS
BEGIN
    SET NOCOUNT ON;
    
    SELECT @SiguienteID = ultimo_id + 1
    FROM th_secuenciales WITH (ROWLOCK, UPDLOCK)
    WHERE tabla_nombre = @TablaNombre;
    
    UPDATE th_secuenciales
    SET ultimo_id = @SiguienteID
    WHERE tabla_nombre = @TablaNombre;
END;

GO
USE [master]
GO
ALTER DATABASE [Talento_Humano] SET  READ_WRITE 
GO
