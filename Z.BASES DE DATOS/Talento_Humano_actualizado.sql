USE [master]
GO
/****** Objeto: Database [Talento_Humano] Fecha de script: 5/8/2026 09:52:36 ******/
CREATE DATABASE [Talento_Humano]
 CONTAINMENT = NONE
 ON  PRIMARY 
( NAME = N'Talento_Humano', FILENAME = N'C:\Program Files\Microsoft SQL Server\MSSQL16.VICTUS\MSSQL\DATA\Talento_Humano.mdf' , SIZE = 73728KB , MAXSIZE = UNLIMITED, FILEGROWTH = 65536KB )
 LOG ON 
( NAME = N'Talento_Humano_log', FILENAME = N'C:\Program Files\Microsoft SQL Server\MSSQL16.VICTUS\MSSQL\DATA\Talento_Humano_log.ldf' , SIZE = 73728KB , MAXSIZE = 2048GB , FILEGROWTH = 65536KB )
 WITH CATALOG_COLLATION = DATABASE_DEFAULT, LEDGER = OFF
GO
ALTER DATABASE [Talento_Humano] SET COMPATIBILITY_LEVEL = 160
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
ALTER DATABASE [Talento_Humano] SET ACCELERATED_DATABASE_RECOVERY = OFF  
GO
ALTER DATABASE [Talento_Humano] SET QUERY_STORE = ON
GO
ALTER DATABASE [Talento_Humano] SET QUERY_STORE (OPERATION_MODE = READ_WRITE, CLEANUP_POLICY = (STALE_QUERY_THRESHOLD_DAYS = 30), DATA_FLUSH_INTERVAL_SECONDS = 900, INTERVAL_LENGTH_MINUTES = 60, MAX_STORAGE_SIZE_MB = 1000, QUERY_CAPTURE_MODE = AUTO, SIZE_BASED_CLEANUP_MODE = AUTO, MAX_PLANS_PER_QUERY = 200, WAIT_STATS_CAPTURE_MODE = ON)
GO
USE [Talento_Humano]
GO
/****** Objeto: User [portal_app] Fecha de script: 5/8/2026 09:52:37 ******/
CREATE USER [portal_app] WITHOUT LOGIN WITH DEFAULT_SCHEMA=[dbo]
GO
/****** Objeto: DatabaseRole [portal_app_role] Fecha de script: 5/8/2026 09:52:37 ******/
CREATE ROLE [portal_app_role]
GO
ALTER ROLE [portal_app_role] ADD MEMBER [portal_app]
GO
/****** Objeto: Table [dbo].[th_empleados] Fecha de script: 5/8/2026 09:52:37 ******/
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
	[estado_fecha_efectiva] [date] NULL,
	[estado_motivo] [nvarchar](500) NULL,
	[estado_origen] [varchar](40) NULL,
	[estado_accion_id] [int] NULL,
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
/****** Objeto: View [dbo].[view_th_empleados] Fecha de script: 5/8/2026 09:52:37 ******/
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
/****** Objeto: View [dbo].[view_th_idempleados] Fecha de script: 5/8/2026 09:52:37 ******/
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
/****** Objeto: View [dbo].[view_th_cedempleados] Fecha de script: 5/8/2026 09:52:37 ******/
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
/****** Objeto: Table [dbo].[th_puestos] Fecha de script: 5/8/2026 09:52:37 ******/
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
/****** Objeto: Table [dbo].[th_unidades_organizacionales] Fecha de script: 5/8/2026 09:52:37 ******/
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
	[nombre_busqueda]  AS (CONVERT([varchar](180),(upper(ltrim(rtrim([nombre_unidad])))) collate Modern_Spanish_CI_AI)) PERSISTED,
PRIMARY KEY CLUSTERED 
(
	[unidad_id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
) ON [PRIMARY]
GO
/****** Objeto: View [dbo].[vw_th_directorio_empleados] Fecha de script: 5/8/2026 09:52:37 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE   VIEW [dbo].[vw_th_directorio_empleados]
AS
SELECT
    e.empleado_id AS id,e.empleado_id,
    ROW_NUMBER() OVER(ORDER BY e.apellidos,e.nombres,e.empleado_id) AS numero_registro,
    e.tipo_identificacion,e.identificacion AS cedula,e.apellidos,e.nombres,
    LTRIM(RTRIM(CONCAT(e.apellidos,' ',e.nombres))) AS apellidos_nombres,
    e.unidad_id,e.puesto_id,ISNULL(p.nombre_puesto,'') AS cargo,ISNULL(u.nombre_unidad,'') AS direccion_area,
    e.correo_institucional,e.correo_personal,e.estado,e.estado_fecha_efectiva,e.estado_motivo,e.estado_origen,e.estado_accion_id,
    ISNULL(e.cargas_familiares,0) AS cargas_familiares,e.tipo_cuenta_bancaria,e.numero_cuenta_bancaria,e.institucion_bancaria,
    e.tipo_contrato,e.sueldo_rmu AS remuneracion_mensual,e.sueldo_rmu,e.fecha_ingreso,e.fecha_salida,e.fecha_nacimiento,
    e.sexo,e.estado_civil,e.nacionalidad,e.tipo_sangre,e.telefono_movil,e.telefono_convencional,e.ciudad_residencia,
    e.direccion_domiciliaria,e.contacto_emergencia,e.emergencia_relacion,e.tel_emergencia,e.nivel_estudio,e.titulo,
    e.jornada,e.condicion_especial,e.tipo_discapacidad,e.porcentaje_discapacidad,e.cuenta_bancaria,e.codigo_iess,
    e.cod_emplea,e.num_iess,e.ruta_foto,e.observaciones
FROM dbo.th_empleados e
LEFT JOIN dbo.th_puestos p ON p.puesto_id=e.puesto_id
LEFT JOIN dbo.th_unidades_organizacionales u ON u.unidad_id=e.unidad_id;

GO
/****** Objeto: View [dbo].[view_th_iddatosempledo] Fecha de script: 5/8/2026 09:52:37 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE   VIEW [dbo].[view_th_iddatosempledo] AS SELECT * FROM dbo.vw_th_directorio_empleados;

GO
/****** Objeto: Table [dbo].[th_estudios_socioeconomicos] Fecha de script: 5/8/2026 09:52:37 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[th_estudios_socioeconomicos](
	[estudio_id] [int] IDENTITY(1,1) NOT NULL,
	[empleado_id] [int] NOT NULL,
	[codigo_formato] [varchar](30) NOT NULL,
	[fecha_formato] [date] NOT NULL,
	[version_formato] [varchar](20) NOT NULL,
	[fecha_vinculacion] [date] NULL,
	[cargo_cabecera] [nvarchar](180) NULL,
	[nombre_cabecera] [nvarchar](220) NULL,
	[tipo_doc_ident] [nvarchar](50) NULL,
	[nro_documento] [nvarchar](30) NULL,
	[nacionalidad] [nvarchar](80) NULL,
	[anios_residencia] [nvarchar](30) NULL,
	[libreta_militar] [nvarchar](30) NULL,
	[nro_libreta_militar] [nvarchar](40) NULL,
	[tipo_relacion] [nvarchar](80) NULL,
	[apellidos] [nvarchar](150) NULL,
	[nombres] [nvarchar](150) NULL,
	[fecha_nacimiento] [date] NULL,
	[edad] [nvarchar](20) NULL,
	[lugar_nacimiento] [nvarchar](120) NULL,
	[provincia_ciudad_nac] [nvarchar](150) NULL,
	[genero] [nvarchar](40) NULL,
	[tipo_sangre] [nvarchar](20) NULL,
	[estado_civil] [nvarchar](40) NULL,
	[discapacidad] [nvarchar](20) NULL,
	[tipo_discapacidad] [nvarchar](100) NULL,
	[nro_carnet_conadis] [nvarchar](40) NULL,
	[servidor_carrera] [nvarchar](30) NULL,
	[nro_servidor_carrera] [nvarchar](50) NULL,
	[auto_identificacion] [nvarchar](80) NULL,
	[nacionalidad_indigena] [nvarchar](100) NULL,
	[dir_calle_principal] [nvarchar](150) NULL,
	[numero_domicilio] [nvarchar](30) NULL,
	[calle_secundaria] [nvarchar](150) NULL,
	[parroquia] [nvarchar](100) NULL,
	[canton] [nvarchar](100) NULL,
	[provincia_dom] [nvarchar](100) NULL,
	[referencia_domiciliaria] [nvarchar](250) NULL,
	[tel_domicilio] [nvarchar](40) NULL,
	[tel_celular] [nvarchar](40) NULL,
	[tel_trabajo] [nvarchar](40) NULL,
	[extension] [nvarchar](20) NULL,
	[correo_institucional] [nvarchar](150) NULL,
	[correo_alternativo] [nvarchar](150) NULL,
	[contacto_nombre] [nvarchar](180) NULL,
	[contacto_parentesco] [nvarchar](80) NULL,
	[contacto_tel_conv] [nvarchar](40) NULL,
	[contacto_tel_cel] [nvarchar](40) NULL,
	[nro_otorgamiento] [nvarchar](80) NULL,
	[fecha_ingreso_bienes] [date] NULL,
	[banco] [nvarchar](120) NULL,
	[tipo_cuenta] [nvarchar](50) NULL,
	[nro_cuenta] [nvarchar](60) NULL,
	[conyuge_nombres] [nvarchar](180) NULL,
	[conyuge_tipo_doc] [nvarchar](50) NULL,
	[conyuge_nro_doc] [nvarchar](40) NULL,
	[conyuge_fecha_nac] [date] NULL,
	[conyuge_tipo_relacion] [nvarchar](80) NULL,
	[conyuge_nivel_instruccion] [nvarchar](100) NULL,
	[conyuge_ocupacion] [nvarchar](120) NULL,
	[nivel_instruccion] [nvarchar](100) NULL,
	[institucion_educativa] [nvarchar](180) NULL,
	[tipo_periodo] [nvarchar](80) NULL,
	[area_conocimiento] [nvarchar](150) NULL,
	[egresado] [nvarchar](20) NULL,
	[titulo_academico] [nvarchar](200) NULL,
	[vivienda_tipo] [nvarchar](30) NULL,
	[vehiculo_marca] [nvarchar](80) NULL,
	[vehiculo_modelo] [nvarchar](80) NULL,
	[vehiculo_placa] [nvarchar](30) NULL,
	[vehiculo_valor] [decimal](12, 2) NULL,
	[estado] [bit] NOT NULL,
	[usuario_crea] [varchar](50) NOT NULL,
	[fecha_creacion] [datetime2](0) NOT NULL,
	[usuario_modifica] [varchar](50) NULL,
	[fecha_modificacion] [datetime2](0) NULL,
	[direccion_ip] [varchar](45) NULL,
 CONSTRAINT [PK_th_estudios_socioeconomicos] PRIMARY KEY CLUSTERED 
(
	[estudio_id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
) ON [PRIMARY]
GO
/****** Objeto: View [dbo].[vw_th_estudios_socioeconomicos] Fecha de script: 5/8/2026 09:52:37 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER OFF
GO

CREATE   VIEW [dbo].[vw_th_estudios_socioeconomicos]
AS
SELECT s.estudio_id,s.empleado_id,s.codigo_formato,s.fecha_formato,s.version_formato,
       s.fecha_vinculacion,s.cargo_cabecera,s.nro_documento,s.nombres,s.apellidos,
       e.identificacion,e.nombres AS nombres_empleado,e.apellidos AS apellidos_empleado,
       s.estado,s.usuario_crea,s.fecha_creacion,s.usuario_modifica,s.fecha_modificacion
FROM dbo.th_estudios_socioeconomicos s
JOIN dbo.th_empleados e ON e.empleado_id=s.empleado_id;

GO
/****** Objeto: View [dbo].[view_th_ceddatosempledo] Fecha de script: 5/8/2026 09:52:37 ******/
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
/****** Objeto: Table [dbo].[th_historial_laboral] Fecha de script: 5/8/2026 09:52:37 ******/
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
/****** Objeto: View [dbo].[vw_th_reporte_historial_jerarquico] Fecha de script: 5/8/2026 09:52:37 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER OFF
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
/****** Objeto: Table [dbo].[th_acciones_personal] Fecha de script: 5/8/2026 09:52:37 ******/
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
	[detalle_otro] [nvarchar](255) NULL,
	[presento_declaracion] [varchar](20) NULL,
	[actual_proceso] [nvarchar](150) NULL,
	[actual_nivel_gestion] [nvarchar](150) NULL,
	[actual_grupo_ocupacional] [nvarchar](150) NULL,
	[actual_grado] [nvarchar](50) NULL,
	[propuesta_proceso] [nvarchar](150) NULL,
	[propuesta_nivel_gestion] [nvarchar](150) NULL,
	[propuesta_grupo_ocupacional] [nvarchar](150) NULL,
	[propuesta_grado] [nvarchar](50) NULL,
	[notificacion_electronica] [bit] NULL,
	[correo_notificacion] [nvarchar](150) NULL,
	[medio_notificacion] [nvarchar](100) NULL,
	[documento_notificacion] [nvarchar](100) NULL,
	[fecha_notificacion] [datetime2](0) NULL,
	[responsable_th_nombre] [nvarchar](150) NULL,
	[responsable_th_puesto] [nvarchar](150) NULL,
	[autoridad_nombre] [nvarchar](150) NULL,
	[autoridad_puesto] [nvarchar](150) NULL,
	[elaborador_nombre] [nvarchar](150) NULL,
	[elaborador_puesto] [nvarchar](150) NULL,
	[revisor_nombre] [nvarchar](150) NULL,
	[revisor_puesto] [nvarchar](150) NULL,
	[registrador_nombre] [nvarchar](150) NULL,
	[registrador_puesto] [nvarchar](150) NULL,
	[notificador_nombre] [nvarchar](150) NULL,
	[notificador_puesto] [nvarchar](150) NULL,
	[usuario_aprueba] [varchar](50) NULL,
	[fecha_aprobacion] [datetime2](3) NULL,
	[motivo_anulacion] [nvarchar](500) NULL,
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
/****** Objeto: View [dbo].[vw_th_acciones_resumen] Fecha de script: 5/8/2026 09:52:37 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER OFF
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
/****** Objeto: View [dbo].[vw_th_maestros_organizacionales] Fecha de script: 5/8/2026 09:52:37 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO

CREATE   VIEW [dbo].[vw_th_maestros_organizacionales]
AS
SELECT
    u.unidad_id,
    u.codigo_uorg,
    u.nombre_unidad,
    CASE WHEN u.unidad_padre_id IS NULL THEN 'Direccion' ELSE 'Area' END AS tipo_unidad,
    u.tipo_proceso,
    u.unidad_padre_id,
    padre.nombre_unidad AS direccion_padre,
    CONVERT(BIT, ISNULL(u.activo, 0)) AS activo
FROM dbo.th_unidades_organizacionales u
LEFT JOIN dbo.th_unidades_organizacionales padre ON padre.unidad_id = u.unidad_padre_id;

GO
/****** Objeto: Table [dbo].[th_movimientos_personal] Fecha de script: 5/8/2026 09:52:37 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[th_movimientos_personal](
	[movimiento_id] [int] IDENTITY(1,1) NOT NULL,
	[empleado_id] [int] NOT NULL,
	[unidad_origen_id] [int] NULL,
	[puesto_origen_id] [int] NULL,
	[unidad_destino_id] [int] NOT NULL,
	[puesto_destino_id] [int] NOT NULL,
	[fecha_movimiento] [date] NOT NULL,
	[motivo] [varchar](500) NOT NULL,
	[usuario_crea] [varchar](50) NOT NULL,
	[direccion_ip] [varchar](45) NOT NULL,
	[fecha_creacion] [datetime2](3) NOT NULL,
	[lote_id] [int] NULL,
 CONSTRAINT [PK_th_movimientos_personal] PRIMARY KEY CLUSTERED 
(
	[movimiento_id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
) ON [PRIMARY]
GO
/****** Objeto: View [dbo].[vw_th_movimientos_personal] Fecha de script: 5/8/2026 09:52:37 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO

CREATE   VIEW [dbo].[vw_th_movimientos_personal]
AS
SELECT
    m.movimiento_id,
    m.empleado_id,
    e.identificacion AS cedula,
    LTRIM(RTRIM(CONCAT(e.apellidos, ' ', e.nombres))) AS funcionario,
    m.fecha_movimiento,
    uo.nombre_unidad AS unidad_origen,
    po.nombre_puesto AS puesto_origen,
    ud.nombre_unidad AS unidad_destino,
    pd.nombre_puesto AS puesto_destino,
    m.motivo,
    m.usuario_crea,
    m.direccion_ip,
    m.fecha_creacion
FROM dbo.th_movimientos_personal m
JOIN dbo.th_empleados e ON e.empleado_id = m.empleado_id
LEFT JOIN dbo.th_unidades_organizacionales uo ON uo.unidad_id = m.unidad_origen_id
LEFT JOIN dbo.th_puestos po ON po.puesto_id = m.puesto_origen_id
JOIN dbo.th_unidades_organizacionales ud ON ud.unidad_id = m.unidad_destino_id
JOIN dbo.th_puestos pd ON pd.puesto_id = m.puesto_destino_id;

GO
/****** Objeto: Table [dbo].[th_acciones_personal_old] Fecha de script: 5/8/2026 09:52:37 ******/
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
/****** Objeto: Table [dbo].[th_empleado_nacionalidades] Fecha de script: 5/8/2026 09:52:37 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[th_empleado_nacionalidades](
	[empleado_id] [int] NOT NULL,
	[nacionalidad_id] [int] NOT NULL,
	[es_principal] [bit] NOT NULL,
	[orden] [tinyint] NOT NULL,
	[usuario_crea] [varchar](50) NOT NULL,
	[fecha_creacion] [datetime2](3) NOT NULL,
 CONSTRAINT [PK_th_empleado_nacionalidades] PRIMARY KEY CLUSTERED 
(
	[empleado_id] ASC,
	[nacionalidad_id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
) ON [PRIMARY]
GO
/****** Objeto: Table [dbo].[th_estudio_capacitaciones] Fecha de script: 5/8/2026 09:52:37 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[th_estudio_capacitaciones](
	[capacitacion_id] [int] IDENTITY(1,1) NOT NULL,
	[estudio_id] [int] NOT NULL,
	[orden] [tinyint] NOT NULL,
	[evento] [nvarchar](250) NULL,
	[tipo_evento] [nvarchar](100) NULL,
	[auspiciante] [nvarchar](180) NULL,
	[tipo_certificado] [nvarchar](100) NULL,
	[certificado_por] [nvarchar](180) NULL,
	[fecha_inicio] [date] NULL,
 CONSTRAINT [PK_th_estudio_capacitaciones] PRIMARY KEY CLUSTERED 
(
	[capacitacion_id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY],
 CONSTRAINT [UQ_th_estudio_capacitacion_orden] UNIQUE NONCLUSTERED 
(
	[estudio_id] ASC,
	[orden] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
) ON [PRIMARY]
GO
/****** Objeto: Table [dbo].[th_estudio_experiencias] Fecha de script: 5/8/2026 09:52:37 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[th_estudio_experiencias](
	[experiencia_id] [int] IDENTITY(1,1) NOT NULL,
	[estudio_id] [int] NOT NULL,
	[orden] [tinyint] NOT NULL,
	[institucion] [nvarchar](180) NULL,
	[tipo_institucion] [nvarchar](80) NULL,
	[unidad_administrativa] [nvarchar](150) NULL,
	[cargo] [nvarchar](150) NULL,
	[antiguedad] [nvarchar](50) NULL,
	[jefe_inmediato] [nvarchar](150) NULL,
	[telefono] [nvarchar](40) NULL,
	[fecha_ingreso] [date] NULL,
	[motivo_ingreso] [nvarchar](180) NULL,
	[fecha_retiro] [date] NULL,
	[motivo_retiro] [nvarchar](180) NULL,
 CONSTRAINT [PK_th_estudio_experiencias] PRIMARY KEY CLUSTERED 
(
	[experiencia_id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY],
 CONSTRAINT [UQ_th_estudio_experiencia_orden] UNIQUE NONCLUSTERED 
(
	[estudio_id] ASC,
	[orden] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
) ON [PRIMARY]
GO
/****** Objeto: Table [dbo].[th_estudio_hijos] Fecha de script: 5/8/2026 09:52:37 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[th_estudio_hijos](
	[hijo_id] [int] IDENTITY(1,1) NOT NULL,
	[estudio_id] [int] NOT NULL,
	[orden] [tinyint] NOT NULL,
	[nombres_apellidos] [nvarchar](180) NULL,
	[fecha_nacimiento] [date] NULL,
	[tipo_documento] [nvarchar](50) NULL,
	[numero_documento] [nvarchar](40) NULL,
	[edad] [nvarchar](20) NULL,
	[nivel_instruccion] [nvarchar](100) NULL,
	[ocupacion] [nvarchar](120) NULL,
 CONSTRAINT [PK_th_estudio_hijos] PRIMARY KEY CLUSTERED 
(
	[hijo_id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY],
 CONSTRAINT [UQ_th_estudio_hijo_orden] UNIQUE NONCLUSTERED 
(
	[estudio_id] ASC,
	[orden] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
) ON [PRIMARY]
GO
/****** Objeto: Table [dbo].[th_logs_auditoria] Fecha de script: 5/8/2026 09:52:37 ******/
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
/****** Objeto: Table [dbo].[th_modulos] Fecha de script: 5/8/2026 09:52:37 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[th_modulos](
	[modulo_id] [int] IDENTITY(1,1) NOT NULL,
	[nombre_modulo] [varchar](100) NOT NULL,
	[ruta_frontend] [varchar](100) NULL,
	[codigo_modulo] [varchar](50) NULL,
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
/****** Objeto: Table [dbo].[th_movimientos_lote] Fecha de script: 5/8/2026 09:52:37 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[th_movimientos_lote](
	[lote_id] [int] IDENTITY(1,1) NOT NULL,
	[unidad_destino_id] [int] NOT NULL,
	[puesto_destino_id] [int] NOT NULL,
	[fecha_movimiento] [date] NOT NULL,
	[motivo] [varchar](500) NOT NULL,
	[cantidad] [int] NOT NULL,
	[estado] [varchar](20) NOT NULL,
	[usuario_crea] [varchar](50) NOT NULL,
	[direccion_ip] [varchar](45) NOT NULL,
	[fecha_creacion] [datetime2](3) NOT NULL,
 CONSTRAINT [PK_th_movimientos_lote] PRIMARY KEY CLUSTERED 
(
	[lote_id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
) ON [PRIMARY]
GO
/****** Objeto: Table [dbo].[th_nacionalidades] Fecha de script: 5/8/2026 09:52:37 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[th_nacionalidades](
	[nacionalidad_id] [int] IDENTITY(1,1) NOT NULL,
	[codigo_iso] [char](2) NOT NULL,
	[pais] [nvarchar](100) NOT NULL,
	[nombre] [nvarchar](100) NOT NULL,
	[aliases] [nvarchar](300) NULL,
	[activo] [bit] NOT NULL,
	[fecha_actualizacion] [datetime2](3) NOT NULL,
 CONSTRAINT [PK_th_nacionalidades] PRIMARY KEY CLUSTERED 
(
	[nacionalidad_id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY],
 CONSTRAINT [UX_th_nacionalidades_iso] UNIQUE NONCLUSTERED 
(
	[codigo_iso] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
) ON [PRIMARY]
GO
/****** Objeto: Table [dbo].[th_parametros] Fecha de script: 5/8/2026 09:52:37 ******/
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
/****** Objeto: Table [dbo].[th_permisos_rol] Fecha de script: 5/8/2026 09:52:37 ******/
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
/****** Objeto: Table [dbo].[th_politicas_documentos] Fecha de script: 5/8/2026 09:52:37 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[th_politicas_documentos](
	[documento_id] [int] IDENTITY(1,1) NOT NULL,
	[titulo] [nvarchar](200) NOT NULL,
	[categoria] [nvarchar](80) NOT NULL,
	[version] [nvarchar](30) NOT NULL,
	[descripcion] [nvarchar](500) NULL,
	[nombre_archivo] [nvarchar](255) NOT NULL,
	[ruta_privada] [nvarchar](500) NOT NULL,
	[mime_type] [varchar](100) NOT NULL,
	[tamano_bytes] [bigint] NOT NULL,
	[vigente] [bit] NOT NULL,
	[descargas] [int] NOT NULL,
	[usuario_crea] [varchar](50) NOT NULL,
	[fecha_creacion] [datetime2](3) NOT NULL,
PRIMARY KEY CLUSTERED 
(
	[documento_id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
) ON [PRIMARY]
GO
/****** Objeto: Table [dbo].[th_respaldo_empleados_calidad_20260729] Fecha de script: 5/8/2026 09:52:37 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[th_respaldo_empleados_calidad_20260729](
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
	[num_iess] [varchar](30) NULL
) ON [PRIMARY] TEXTIMAGE_ON [PRIMARY]
GO
/****** Objeto: Table [dbo].[th_respaldo_estado_empleados_2026] Fecha de script: 5/8/2026 09:52:37 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[th_respaldo_estado_empleados_2026](
	[empleado_id] [int] IDENTITY(1,1) NOT NULL,
	[estado] [int] NULL,
	[fecha_ingreso] [date] NULL,
	[fecha_salida] [date] NULL,
	[unidad_id] [int] NULL,
	[puesto_id] [int] NULL,
	[fecha_respaldo] [datetime2](7) NOT NULL
) ON [PRIMARY]
GO
/****** Objeto: Table [dbo].[th_respaldo_normalizacion_20260729] Fecha de script: 5/8/2026 09:52:37 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[th_respaldo_normalizacion_20260729](
	[empleado_id] [int] IDENTITY(1,1) NOT NULL,
	[unidad_id] [int] NULL,
	[puesto_id] [int] NULL,
	[tipo_contrato] [varchar](100) NULL,
	[titulo] [varchar](150) NULL,
	[fecha_respaldo] [datetime2](7) NOT NULL,
 CONSTRAINT [PK_th_respaldo_normalizacion_20260729] PRIMARY KEY CLUSTERED 
(
	[empleado_id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
) ON [PRIMARY]
GO
/****** Objeto: Table [dbo].[th_respaldo_unidades_calidad_20260729] Fecha de script: 5/8/2026 09:52:37 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[th_respaldo_unidades_calidad_20260729](
	[unidad_id] [int] IDENTITY(1,1) NOT NULL,
	[codigo_uorg] [varchar](20) NOT NULL,
	[nombre_unidad] [varchar](150) NOT NULL,
	[tipo_proceso] [varchar](50) NOT NULL,
	[unidad_padre_id] [int] NULL,
	[activo] [bit] NULL,
	[sucedido_por_id] [int] NULL,
	[fecha_fin] [date] NULL,
	[fecha_inicio] [date] NULL
) ON [PRIMARY]
GO
/****** Objeto: Table [dbo].[th_roles] Fecha de script: 5/8/2026 09:52:37 ******/
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
/****** Objeto: Table [dbo].[th_titulos] Fecha de script: 5/8/2026 09:52:37 ******/
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
/****** Objeto: Table [dbo].[th_usuarios_sistema] Fecha de script: 5/8/2026 09:52:37 ******/
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
	[token_version] [int] NOT NULL,
	[intentos_fallidos] [int] NOT NULL,
	[bloqueado_hasta] [datetime2](0) NULL,
	[debe_cambiar_clave] [bit] NOT NULL,
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
SET ANSI_PADDING ON
GO
/****** Objeto: Index [UX_th_acciones_numero] Fecha de script: 5/8/2026 09:52:37 ******/
CREATE UNIQUE NONCLUSTERED INDEX [UX_th_acciones_numero] ON [dbo].[th_acciones_personal]
(
	[numero_accion] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, SORT_IN_TEMPDB = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
GO
SET ANSI_PADDING ON
GO
/****** Objeto: Index [IX_th_empleados_identificacion_busqueda] Fecha de script: 5/8/2026 09:52:37 ******/
CREATE NONCLUSTERED INDEX [IX_th_empleados_identificacion_busqueda] ON [dbo].[th_empleados]
(
	[identificacion] ASC
)
INCLUDE([apellidos],[nombres],[unidad_id],[puesto_id],[estado],[tipo_contrato]) WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, SORT_IN_TEMPDB = OFF, DROP_EXISTING = OFF, ONLINE = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
GO
/****** Objeto: Index [IX_th_estudio_empleado_fecha] Fecha de script: 5/8/2026 09:52:37 ******/
CREATE NONCLUSTERED INDEX [IX_th_estudio_empleado_fecha] ON [dbo].[th_estudios_socioeconomicos]
(
	[empleado_id] ASC,
	[fecha_creacion] DESC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, SORT_IN_TEMPDB = OFF, DROP_EXISTING = OFF, ONLINE = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
GO
/****** Objeto: Index [IX_Historial_Empleado] Fecha de script: 5/8/2026 09:52:37 ******/
CREATE NONCLUSTERED INDEX [IX_Historial_Empleado] ON [dbo].[th_historial_laboral]
(
	[empleado_id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, SORT_IN_TEMPDB = OFF, DROP_EXISTING = OFF, ONLINE = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
GO
/****** Objeto: Index [IX_Historial_Fechas] Fecha de script: 5/8/2026 09:52:37 ******/
CREATE NONCLUSTERED INDEX [IX_Historial_Fechas] ON [dbo].[th_historial_laboral]
(
	[fecha_desde] ASC,
	[fecha_hasta] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, SORT_IN_TEMPDB = OFF, DROP_EXISTING = OFF, ONLINE = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
GO
/****** Objeto: Index [IX_Historial_Unidad] Fecha de script: 5/8/2026 09:52:37 ******/
CREATE NONCLUSTERED INDEX [IX_Historial_Unidad] ON [dbo].[th_historial_laboral]
(
	[unidad_id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, SORT_IN_TEMPDB = OFF, DROP_EXISTING = OFF, ONLINE = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
GO
/****** Objeto: Index [IX_Logs_FechaHora] Fecha de script: 5/8/2026 09:52:37 ******/
CREATE NONCLUSTERED INDEX [IX_Logs_FechaHora] ON [dbo].[th_logs_auditoria]
(
	[fecha_hora] DESC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, SORT_IN_TEMPDB = OFF, DROP_EXISTING = OFF, ONLINE = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
GO
SET ANSI_PADDING ON
GO
/****** Objeto: Index [IX_Logs_Modulo] Fecha de script: 5/8/2026 09:52:37 ******/
CREATE NONCLUSTERED INDEX [IX_Logs_Modulo] ON [dbo].[th_logs_auditoria]
(
	[modulo] ASC,
	[accion] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, SORT_IN_TEMPDB = OFF, DROP_EXISTING = OFF, ONLINE = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
GO
SET ANSI_PADDING ON
GO
/****** Objeto: Index [IX_Logs_Usuario] Fecha de script: 5/8/2026 09:52:37 ******/
CREATE NONCLUSTERED INDEX [IX_Logs_Usuario] ON [dbo].[th_logs_auditoria]
(
	[usuario] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, SORT_IN_TEMPDB = OFF, DROP_EXISTING = OFF, ONLINE = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
GO
SET ANSI_PADDING ON
GO
/****** Objeto: Index [IX_th_modulos_codigo] Fecha de script: 5/8/2026 09:52:37 ******/
CREATE NONCLUSTERED INDEX [IX_th_modulos_codigo] ON [dbo].[th_modulos]
(
	[codigo_modulo] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, SORT_IN_TEMPDB = OFF, DROP_EXISTING = OFF, ONLINE = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
GO
/****** Objeto: Index [IX_th_mov_empleado_fecha] Fecha de script: 5/8/2026 09:52:37 ******/
CREATE NONCLUSTERED INDEX [IX_th_mov_empleado_fecha] ON [dbo].[th_movimientos_personal]
(
	[empleado_id] ASC,
	[fecha_movimiento] DESC,
	[movimiento_id] DESC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, SORT_IN_TEMPDB = OFF, DROP_EXISTING = OFF, ONLINE = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
GO
/****** Objeto: Index [UX_th_permisos_rol_modulo] Fecha de script: 5/8/2026 09:52:37 ******/
CREATE UNIQUE NONCLUSTERED INDEX [UX_th_permisos_rol_modulo] ON [dbo].[th_permisos_rol]
(
	[rol_id] ASC,
	[modulo_id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, SORT_IN_TEMPDB = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
GO
SET ARITHABORT ON
SET CONCAT_NULL_YIELDS_NULL ON
SET QUOTED_IDENTIFIER ON
SET ANSI_NULLS ON
SET ANSI_PADDING ON
SET ANSI_WARNINGS ON
SET NUMERIC_ROUNDABORT OFF
GO
/****** Objeto: Index [UX_th_unidades_nombre_activo] Fecha de script: 5/8/2026 09:52:37 ******/
CREATE UNIQUE NONCLUSTERED INDEX [UX_th_unidades_nombre_activo] ON [dbo].[th_unidades_organizacionales]
(
	[nombre_busqueda] ASC
)
WHERE ([activo]=(1))
WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, SORT_IN_TEMPDB = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
GO
ALTER TABLE [dbo].[th_acciones_personal] ADD  DEFAULT ('Borrador') FOR [estado_documento]
GO
ALTER TABLE [dbo].[th_acciones_personal] ADD  DEFAULT (getdate()) FOR [fecha_creacion]
GO
ALTER TABLE [dbo].[th_acciones_personal_old] ADD  DEFAULT ('Generada') FOR [estado]
GO
ALTER TABLE [dbo].[th_empleado_nacionalidades] ADD  CONSTRAINT [DF_th_emp_nac_principal]  DEFAULT ((0)) FOR [es_principal]
GO
ALTER TABLE [dbo].[th_empleado_nacionalidades] ADD  CONSTRAINT [DF_th_emp_nac_orden]  DEFAULT ((1)) FOR [orden]
GO
ALTER TABLE [dbo].[th_empleado_nacionalidades] ADD  CONSTRAINT [DF_th_emp_nac_fecha]  DEFAULT (sysdatetime()) FOR [fecha_creacion]
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
ALTER TABLE [dbo].[th_estudios_socioeconomicos] ADD  CONSTRAINT [DF_th_estudio_codigo]  DEFAULT ('APM-BASC-TH-FO-002') FOR [codigo_formato]
GO
ALTER TABLE [dbo].[th_estudios_socioeconomicos] ADD  CONSTRAINT [DF_th_estudio_fecha_formato]  DEFAULT ('20190401') FOR [fecha_formato]
GO
ALTER TABLE [dbo].[th_estudios_socioeconomicos] ADD  CONSTRAINT [DF_th_estudio_version]  DEFAULT ('01') FOR [version_formato]
GO
ALTER TABLE [dbo].[th_estudios_socioeconomicos] ADD  CONSTRAINT [DF_th_estudio_estado]  DEFAULT ((1)) FOR [estado]
GO
ALTER TABLE [dbo].[th_estudios_socioeconomicos] ADD  CONSTRAINT [DF_th_estudio_creacion]  DEFAULT (sysdatetime()) FOR [fecha_creacion]
GO
ALTER TABLE [dbo].[th_historial_laboral] ADD  DEFAULT (getdate()) FOR [fecha_creacion]
GO
ALTER TABLE [dbo].[th_logs_auditoria] ADD  DEFAULT (getdate()) FOR [fecha_hora]
GO
ALTER TABLE [dbo].[th_logs_auditoria] ADD  DEFAULT ('0.0.0.0') FOR [direccion_ip]
GO
ALTER TABLE [dbo].[th_movimientos_lote] ADD  CONSTRAINT [DF_th_mov_lote_estado]  DEFAULT ('APLICADO') FOR [estado]
GO
ALTER TABLE [dbo].[th_movimientos_lote] ADD  CONSTRAINT [DF_th_mov_lote_fecha]  DEFAULT (sysdatetime()) FOR [fecha_creacion]
GO
ALTER TABLE [dbo].[th_movimientos_personal] ADD  CONSTRAINT [DF_th_mov_ip]  DEFAULT ('0.0.0.0') FOR [direccion_ip]
GO
ALTER TABLE [dbo].[th_movimientos_personal] ADD  CONSTRAINT [DF_th_mov_fecha]  DEFAULT (sysdatetime()) FOR [fecha_creacion]
GO
ALTER TABLE [dbo].[th_nacionalidades] ADD  CONSTRAINT [DF_th_nacionalidades_activo]  DEFAULT ((1)) FOR [activo]
GO
ALTER TABLE [dbo].[th_nacionalidades] ADD  CONSTRAINT [DF_th_nacionalidades_fecha]  DEFAULT (sysdatetime()) FOR [fecha_actualizacion]
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
ALTER TABLE [dbo].[th_politicas_documentos] ADD  CONSTRAINT [DF_th_politicas_vigente]  DEFAULT ((1)) FOR [vigente]
GO
ALTER TABLE [dbo].[th_politicas_documentos] ADD  CONSTRAINT [DF_th_politicas_descargas]  DEFAULT ((0)) FOR [descargas]
GO
ALTER TABLE [dbo].[th_politicas_documentos] ADD  CONSTRAINT [DF_th_politicas_fecha]  DEFAULT (sysdatetime()) FOR [fecha_creacion]
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
ALTER TABLE [dbo].[th_usuarios_sistema] ADD  CONSTRAINT [DF_th_usuarios_token_version]  DEFAULT ((1)) FOR [token_version]
GO
ALTER TABLE [dbo].[th_usuarios_sistema] ADD  CONSTRAINT [DF_th_usuarios_intentos]  DEFAULT ((0)) FOR [intentos_fallidos]
GO
ALTER TABLE [dbo].[th_usuarios_sistema] ADD  CONSTRAINT [DF_th_usuarios_cambiar]  DEFAULT ((1)) FOR [debe_cambiar_clave]
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
ALTER TABLE [dbo].[th_empleado_nacionalidades]  WITH CHECK ADD  CONSTRAINT [FK_th_emp_nac_empleado] FOREIGN KEY([empleado_id])
REFERENCES [dbo].[th_empleados] ([empleado_id])
GO
ALTER TABLE [dbo].[th_empleado_nacionalidades] CHECK CONSTRAINT [FK_th_emp_nac_empleado]
GO
ALTER TABLE [dbo].[th_empleado_nacionalidades]  WITH CHECK ADD  CONSTRAINT [FK_th_emp_nac_nacionalidad] FOREIGN KEY([nacionalidad_id])
REFERENCES [dbo].[th_nacionalidades] ([nacionalidad_id])
GO
ALTER TABLE [dbo].[th_empleado_nacionalidades] CHECK CONSTRAINT [FK_th_emp_nac_nacionalidad]
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
ALTER TABLE [dbo].[th_estudio_capacitaciones]  WITH CHECK ADD  CONSTRAINT [FK_th_estudio_capacitacion] FOREIGN KEY([estudio_id])
REFERENCES [dbo].[th_estudios_socioeconomicos] ([estudio_id])
ON DELETE CASCADE
GO
ALTER TABLE [dbo].[th_estudio_capacitaciones] CHECK CONSTRAINT [FK_th_estudio_capacitacion]
GO
ALTER TABLE [dbo].[th_estudio_experiencias]  WITH CHECK ADD  CONSTRAINT [FK_th_estudio_experiencia] FOREIGN KEY([estudio_id])
REFERENCES [dbo].[th_estudios_socioeconomicos] ([estudio_id])
ON DELETE CASCADE
GO
ALTER TABLE [dbo].[th_estudio_experiencias] CHECK CONSTRAINT [FK_th_estudio_experiencia]
GO
ALTER TABLE [dbo].[th_estudio_hijos]  WITH CHECK ADD  CONSTRAINT [FK_th_estudio_hijo] FOREIGN KEY([estudio_id])
REFERENCES [dbo].[th_estudios_socioeconomicos] ([estudio_id])
ON DELETE CASCADE
GO
ALTER TABLE [dbo].[th_estudio_hijos] CHECK CONSTRAINT [FK_th_estudio_hijo]
GO
ALTER TABLE [dbo].[th_estudios_socioeconomicos]  WITH CHECK ADD  CONSTRAINT [FK_th_estudio_empleado] FOREIGN KEY([empleado_id])
REFERENCES [dbo].[th_empleados] ([empleado_id])
GO
ALTER TABLE [dbo].[th_estudios_socioeconomicos] CHECK CONSTRAINT [FK_th_estudio_empleado]
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
ALTER TABLE [dbo].[th_movimientos_lote]  WITH CHECK ADD  CONSTRAINT [FK_th_mov_lote_puesto] FOREIGN KEY([puesto_destino_id])
REFERENCES [dbo].[th_puestos] ([puesto_id])
GO
ALTER TABLE [dbo].[th_movimientos_lote] CHECK CONSTRAINT [FK_th_mov_lote_puesto]
GO
ALTER TABLE [dbo].[th_movimientos_lote]  WITH CHECK ADD  CONSTRAINT [FK_th_mov_lote_unidad] FOREIGN KEY([unidad_destino_id])
REFERENCES [dbo].[th_unidades_organizacionales] ([unidad_id])
GO
ALTER TABLE [dbo].[th_movimientos_lote] CHECK CONSTRAINT [FK_th_mov_lote_unidad]
GO
ALTER TABLE [dbo].[th_movimientos_personal]  WITH CHECK ADD  CONSTRAINT [FK_th_mov_empleado] FOREIGN KEY([empleado_id])
REFERENCES [dbo].[th_empleados] ([empleado_id])
GO
ALTER TABLE [dbo].[th_movimientos_personal] CHECK CONSTRAINT [FK_th_mov_empleado]
GO
ALTER TABLE [dbo].[th_movimientos_personal]  WITH CHECK ADD  CONSTRAINT [FK_th_mov_puesto_destino] FOREIGN KEY([puesto_destino_id])
REFERENCES [dbo].[th_puestos] ([puesto_id])
GO
ALTER TABLE [dbo].[th_movimientos_personal] CHECK CONSTRAINT [FK_th_mov_puesto_destino]
GO
ALTER TABLE [dbo].[th_movimientos_personal]  WITH CHECK ADD  CONSTRAINT [FK_th_mov_puesto_origen] FOREIGN KEY([puesto_origen_id])
REFERENCES [dbo].[th_puestos] ([puesto_id])
GO
ALTER TABLE [dbo].[th_movimientos_personal] CHECK CONSTRAINT [FK_th_mov_puesto_origen]
GO
ALTER TABLE [dbo].[th_movimientos_personal]  WITH CHECK ADD  CONSTRAINT [FK_th_mov_unidad_destino] FOREIGN KEY([unidad_destino_id])
REFERENCES [dbo].[th_unidades_organizacionales] ([unidad_id])
GO
ALTER TABLE [dbo].[th_movimientos_personal] CHECK CONSTRAINT [FK_th_mov_unidad_destino]
GO
ALTER TABLE [dbo].[th_movimientos_personal]  WITH CHECK ADD  CONSTRAINT [FK_th_mov_unidad_origen] FOREIGN KEY([unidad_origen_id])
REFERENCES [dbo].[th_unidades_organizacionales] ([unidad_id])
GO
ALTER TABLE [dbo].[th_movimientos_personal] CHECK CONSTRAINT [FK_th_mov_unidad_origen]
GO
ALTER TABLE [dbo].[th_movimientos_personal]  WITH CHECK ADD  CONSTRAINT [FK_th_movimientos_personal_lote] FOREIGN KEY([lote_id])
REFERENCES [dbo].[th_movimientos_lote] ([lote_id])
GO
ALTER TABLE [dbo].[th_movimientos_personal] CHECK CONSTRAINT [FK_th_movimientos_personal_lote]
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
ALTER TABLE [dbo].[th_politicas_documentos]  WITH CHECK ADD  CONSTRAINT [CK_th_politicas_tamano] CHECK  (([tamano_bytes]>=(1) AND [tamano_bytes]<=(20971520)))
GO
ALTER TABLE [dbo].[th_politicas_documentos] CHECK CONSTRAINT [CK_th_politicas_tamano]
GO
/****** Objeto: StoredProcedure [dbo].[sp_th_anular_accion_personal] Fecha de script: 5/8/2026 09:52:37 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO

CREATE   PROCEDURE [dbo].[sp_th_anular_accion_personal]
    @accion_id INT,@motivo NVARCHAR(500),@usuario VARCHAR(50),@ip VARCHAR(45)='0.0.0.0'
AS
BEGIN
    SET NOCOUNT ON; SET XACT_ABORT ON;
    BEGIN TRY
        BEGIN TRAN;
        IF NULLIF(LTRIM(RTRIM(@motivo)),'') IS NULL THROW 51101,'Debe registrar el motivo de anulacion.',1;
        DECLARE @numero VARCHAR(50);
        SELECT @numero=numero_accion FROM dbo.th_acciones_personal WITH(UPDLOCK,HOLDLOCK)
        WHERE accion_id=@accion_id AND UPPER(estado_documento) IN ('BORRADOR','PENDIENTE');
        IF @numero IS NULL THROW 51102,'La accion no existe o ya fue resuelta.',1;
        UPDATE dbo.th_acciones_personal SET estado_documento='ANULADO',motivo_anulacion=@motivo WHERE accion_id=@accion_id;
        DECLARE @detalle_anulacion VARCHAR(500)=CONCAT('Anulo ',@numero,'. Motivo: ',@motivo);
        EXEC dbo.sp_th_registrar_auditoria @usuario,'Accion de Personal','ANULAR',@detalle_anulacion,@ip;
        COMMIT;
        SELECT 1 exito,'Accion anulada.' mensaje;
    END TRY
    BEGIN CATCH
        IF @@TRANCOUNT>0 ROLLBACK;
        SELECT 0 exito,ERROR_MESSAGE() mensaje;
    END CATCH
END;

GO
/****** Objeto: StoredProcedure [dbo].[sp_th_aprobar_accion_personal] Fecha de script: 5/8/2026 09:52:37 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE   PROCEDURE [dbo].[sp_th_aprobar_accion_personal]
    @accion_id INT,@usuario VARCHAR(50),@ip VARCHAR(45)='0.0.0.0'
AS
BEGIN
    SET NOCOUNT ON; SET XACT_ABORT ON;
    BEGIN TRY
        BEGIN TRAN;
        DECLARE @empleado INT,@unidad INT,@puesto INT,@fecha DATE,@numero VARCHAR(50),
                @rmu DECIMAL(10,2),@tipo NVARCHAR(100),@tipo_norm NVARCHAR(100),@estado_actual BIT;
        SELECT @empleado=empleado_id,@unidad=COALESCE(propuesta_unidad_id,actual_unidad_id),
               @puesto=COALESCE(propuesta_puesto_id,actual_puesto_id),@fecha=fecha_rige_desde,
               @numero=numero_accion,@rmu=propuesta_remuneracion,@tipo=tipo_accion
        FROM dbo.th_acciones_personal WITH(UPDLOCK,HOLDLOCK)
        WHERE accion_id=@accion_id AND UPPER(estado_documento) IN ('BORRADOR','PENDIENTE');
        IF @empleado IS NULL THROW 51510,'La acción no existe o ya fue resuelta.',1;
        SELECT @estado_actual=estado FROM dbo.th_empleados WITH(UPDLOCK,HOLDLOCK) WHERE empleado_id=@empleado;
        SET @tipo_norm=UPPER(LTRIM(RTRIM(ISNULL(@tipo,'')))) COLLATE Modern_Spanish_CI_AI;

        DECLARE @es_cese BIT=CASE WHEN @tipo_norm IN ('CESACION DE FUNCIONES','DESTITUCION') THEN 1 ELSE 0 END;
        DECLARE @es_reingreso BIT=CASE WHEN @tipo_norm IN ('INGRESO','REINGRESO','RESTITUCION','REINTEGRO') THEN 1 ELSE 0 END;

        IF @es_cese=1
            EXEC dbo.sp_th_cambiar_estado_empleado @empleado,0,@fecha,
                 N'Cesación aplicada mediante Acción de Personal.','ACCION_PERSONAL',@accion_id,@usuario,@ip,1,0;
        ELSE IF @es_reingreso=1
            EXEC dbo.sp_th_cambiar_estado_empleado @empleado,1,@fecha,
                 N'Ingreso o reingreso aplicado mediante Acción de Personal.','ACCION_PERSONAL',@accion_id,@usuario,@ip,0,0;
        ELSE IF @estado_actual=0
            THROW 51511,'Un funcionario inactivo solo puede reactivarse mediante ingreso, reingreso, restitución o reintegro.',1;

        IF @es_cese=0 AND @unidad IS NOT NULL AND @puesto IS NOT NULL
        BEGIN
            UPDATE dbo.th_historial_laboral
            SET fecha_hasta=CASE WHEN fecha_desde<@fecha THEN DATEADD(DAY,-1,@fecha) ELSE @fecha END
            WHERE empleado_id=@empleado AND fecha_hasta IS NULL;
            INSERT dbo.th_historial_laboral
                (empleado_id,puesto_id,unidad_id,fecha_desde,fecha_hasta,observaciones,usuario_crea,fecha_creacion)
            VALUES(@empleado,@puesto,@unidad,@fecha,NULL,CONCAT('Aplicado por Acción de Personal ',@numero),@usuario,SYSDATETIME());
            UPDATE dbo.th_empleados SET unidad_id=@unidad,puesto_id=@puesto,
                sueldo_rmu=COALESCE(NULLIF(@rmu,0),sueldo_rmu) WHERE empleado_id=@empleado;
        END;

        UPDATE dbo.th_acciones_personal SET estado_documento='APROBADO',usuario_aprueba=@usuario,
            fecha_aprobacion=SYSDATETIME() WHERE accion_id=@accion_id;
        DECLARE @detalle VARCHAR(500)=CONCAT('Aprobó ',@numero,' (',@tipo,') y aplicó la situación laboral.');
        EXEC dbo.sp_th_registrar_auditoria @usuario,'Accion de Personal','APROBAR',@detalle,@ip;
        COMMIT;
        SELECT 1 exito,'Acción aprobada; estado e historial laboral sincronizados.' mensaje;
    END TRY
    BEGIN CATCH
        IF @@TRANCOUNT>0 ROLLBACK;
        SELECT 0 exito,ERROR_MESSAGE() mensaje;
    END CATCH
END;

GO
/****** Objeto: StoredProcedure [dbo].[sp_th_auditar_lectura] Fecha de script: 5/8/2026 09:52:37 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO

/* Se conserva DATETIME para no invalidar el indice y el default existentes. */

CREATE   PROCEDURE [dbo].[sp_th_auditar_lectura]
    @usuario VARCHAR(50),@modulo VARCHAR(50),@recurso VARCHAR(200),@ip VARCHAR(45)='0.0.0.0'
AS
BEGIN
    SET NOCOUNT ON;
    EXEC dbo.sp_th_registrar_auditoria @usuario,@modulo,'CONSULTAR',@recurso,@ip;
END;

GO
/****** Objeto: StoredProcedure [dbo].[sp_th_buscar_personal] Fecha de script: 5/8/2026 09:52:37 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO

CREATE   PROCEDURE [dbo].[sp_th_buscar_personal]
    @termino NVARCHAR(200)=NULL,@unidad_id INT=NULL,@contrato NVARCHAR(100)=NULL,@estado INT=NULL,
    @pagina INT=1,@tamano INT=1000,@usuario VARCHAR(50),@ip VARCHAR(45)='0.0.0.0'
AS
BEGIN
    SET NOCOUNT ON;
    SET @pagina=CASE WHEN @pagina<1 THEN 1 ELSE @pagina END;
    SET @tamano=CASE WHEN @tamano<1 THEN 25 WHEN @tamano>1000 THEN 1000 ELSE @tamano END;
    SET @termino=NULLIF(LTRIM(RTRIM(@termino)),'');
    DECLARE @detalle_busqueda VARCHAR(500)=CONCAT('Busqueda compuesta: ',COALESCE(@termino,'(sin termino)'));
    EXEC dbo.sp_th_registrar_auditoria @usuario,'Directorio de Personal','BUSCAR',@detalle_busqueda,@ip;
    ;WITH Base AS(
        SELECT e.empleado_id,e.identificacion,e.apellidos,e.nombres,e.unidad_id,e.puesto_id,e.estado,e.tipo_contrato,
               u.nombre_unidad,p.nombre_puesto,
               CONCAT(e.identificacion,' ',e.apellidos,' ',e.nombres,' ',u.nombre_unidad,' ',p.nombre_puesto,' ',e.tipo_contrato) texto
        FROM dbo.th_empleados e
        LEFT JOIN dbo.th_unidades_organizacionales u ON u.unidad_id=e.unidad_id
        LEFT JOIN dbo.th_puestos p ON p.puesto_id=e.puesto_id
        WHERE (@unidad_id IS NULL OR e.unidad_id=@unidad_id)
          AND (@estado IS NULL OR e.estado=@estado)
          AND (@contrato IS NULL OR e.tipo_contrato COLLATE Modern_Spanish_CI_AI LIKE '%'+@contrato+'%')
          AND (@termino IS NULL OR NOT EXISTS(
              SELECT 1 FROM STRING_SPLIT(@termino,' ') token
              WHERE NULLIF(LTRIM(RTRIM(token.value)),'') IS NOT NULL
                AND CONCAT(e.identificacion,' ',e.apellidos,' ',e.nombres,' ',u.nombre_unidad,' ',p.nombre_puesto,' ',e.tipo_contrato)
                    COLLATE Modern_Spanish_CI_AI NOT LIKE '%'+LTRIM(RTRIM(token.value))+'%'))
    ),Paginada AS(
        SELECT *,COUNT(*) OVER() total_resultados,ROW_NUMBER() OVER(ORDER BY apellidos,nombres,empleado_id) fila FROM Base
    )
    SELECT empleado_id,total_resultados FROM Paginada
    WHERE fila BETWEEN ((@pagina-1)*@tamano)+1 AND @pagina*@tamano ORDER BY fila;
END;

GO
/****** Objeto: StoredProcedure [dbo].[sp_th_cambiar_estado_empleado] Fecha de script: 5/8/2026 09:52:37 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE   PROCEDURE [dbo].[sp_th_cambiar_estado_empleado]
    @empleado_id INT,
    @estado BIT,
    @fecha_efectiva DATE,
    @motivo NVARCHAR(500),
    @origen VARCHAR(40),
    @accion_id INT = NULL,
    @usuario VARCHAR(50) = 'SISTEMA',
    @ip VARCHAR(45) = '0.0.0.0',
    @gestionar_historial BIT = 1,
    @emitir_resultado BIT = 1
AS
BEGIN
    SET NOCOUNT ON; SET XACT_ABORT ON;
    BEGIN TRY
        BEGIN TRAN;
        IF @empleado_id<=0 OR @fecha_efectiva IS NULL
            THROW 51500,'Empleado y fecha efectiva son obligatorios.',1;
        IF NULLIF(LTRIM(RTRIM(@motivo)),'') IS NULL
            THROW 51501,'El motivo del cambio de estado es obligatorio.',1;
        IF NULLIF(LTRIM(RTRIM(@origen)),'') IS NULL
            THROW 51502,'El origen del cambio de estado es obligatorio.',1;

        DECLARE @estado_anterior BIT,@unidad INT,@puesto INT,@nombre NVARCHAR(220);
        SELECT @estado_anterior=estado,@unidad=unidad_id,@puesto=puesto_id,
               @nombre=LTRIM(RTRIM(CONCAT(apellidos,' ',nombres)))
        FROM dbo.th_empleados WITH(UPDLOCK,HOLDLOCK) WHERE empleado_id=@empleado_id;
        IF @estado_anterior IS NULL THROW 51503,'El empleado indicado no existe.',1;

        UPDATE dbo.th_empleados
        SET estado=@estado,
            fecha_salida=CASE WHEN @estado=0 THEN @fecha_efectiva ELSE NULL END,
            estado_fecha_efectiva=@fecha_efectiva,
            estado_motivo=LTRIM(RTRIM(@motivo)),
            estado_origen=LEFT(UPPER(LTRIM(RTRIM(@origen))),40),
            estado_accion_id=@accion_id
        WHERE empleado_id=@empleado_id;

        IF @gestionar_historial=1 AND @estado=0
            UPDATE dbo.th_historial_laboral
            SET fecha_hasta=CASE WHEN fecha_desde<@fecha_efectiva THEN DATEADD(DAY,-1,@fecha_efectiva) ELSE @fecha_efectiva END
            WHERE empleado_id=@empleado_id AND fecha_hasta IS NULL;

        IF @gestionar_historial=1 AND @estado=1
           AND @unidad IS NOT NULL AND @puesto IS NOT NULL
           AND NOT EXISTS(SELECT 1 FROM dbo.th_historial_laboral WHERE empleado_id=@empleado_id AND fecha_hasta IS NULL)
            INSERT dbo.th_historial_laboral
                (empleado_id,puesto_id,unidad_id,fecha_desde,fecha_hasta,observaciones,usuario_crea,fecha_creacion)
            VALUES
                (@empleado_id,@puesto,@unidad,@fecha_efectiva,NULL,N'Reactivación del ciclo laboral.',@usuario,SYSDATETIME());

        DECLARE @detalle VARCHAR(500)=CONCAT('Empleado #',@empleado_id,' ',@nombre,
            '; estado ',@estado_anterior,'->',@estado,'; fecha=',CONVERT(VARCHAR(10),@fecha_efectiva,23),
            '; origen=',@origen,'; accion=',COALESCE(CONVERT(VARCHAR(20),@accion_id),'N/A'),'; motivo=',LEFT(@motivo,220));
        EXEC dbo.sp_th_registrar_auditoria @usuario,'Ciclo Laboral','CAMBIAR_ESTADO',@detalle,@ip;
        COMMIT;
        IF @emitir_resultado=1 SELECT 1 exito,@estado estado,N'Estado laboral actualizado correctamente.' mensaje;
    END TRY
    BEGIN CATCH
        IF @@TRANCOUNT>0 ROLLBACK;
        IF @emitir_resultado=1 SELECT 0 exito,ERROR_MESSAGE() mensaje;
        ELSE THROW;
    END CATCH
END;

GO
/****** Objeto: StoredProcedure [dbo].[sp_th_consultar_acciones] Fecha de script: 5/8/2026 09:52:37 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO

CREATE   PROCEDURE [dbo].[sp_th_consultar_acciones]
    @usuario VARCHAR(50),
    @ip      VARCHAR(45) = '0.0.0.0'
AS
BEGIN
    SET NOCOUNT ON;
    EXEC dbo.sp_th_registrar_auditoria
        @usuario, 'Accion de Personal', 'CONSULTAR_VISTA',
        'Consulta de vw_th_acciones_resumen', @ip;

    SELECT * FROM dbo.vw_th_acciones_resumen ORDER BY accion_id DESC;
END;

GO
/****** Objeto: StoredProcedure [dbo].[sp_th_consultar_directorio] Fecha de script: 5/8/2026 09:52:37 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO

CREATE   PROCEDURE [dbo].[sp_th_consultar_directorio]
    @usuario VARCHAR(50),
    @ip      VARCHAR(45) = '0.0.0.0',
    @estado  INT = NULL
AS
BEGIN
    SET NOCOUNT ON;
    EXEC dbo.sp_th_registrar_auditoria
        @usuario, 'Directorio de Personal', 'CONSULTAR_VISTA',
        'Consulta de vw_th_directorio_empleados', @ip;

    SELECT *
    FROM dbo.vw_th_directorio_empleados
    WHERE @estado IS NULL OR estado = @estado
    ORDER BY apellidos, nombres;
END;

GO
/****** Objeto: StoredProcedure [dbo].[sp_th_consultar_estudios_socioeconomicos] Fecha de script: 5/8/2026 09:52:37 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER OFF
GO

CREATE   PROCEDURE [dbo].[sp_th_consultar_estudios_socioeconomicos]
    @usuario VARCHAR(50), @ip VARCHAR(45)='0.0.0.0', @estudio_id INT=NULL
AS
BEGIN
    SET NOCOUNT ON;
    EXEC dbo.sp_th_registrar_auditoria @usuario,'Estudio Socioeconomico','CONSULTAR_VISTA',
         'Consulta de vw_th_estudios_socioeconomicos',@ip;
    SELECT * FROM dbo.vw_th_estudios_socioeconomicos
    WHERE (@estudio_id IS NULL OR estudio_id=@estudio_id)
    ORDER BY fecha_creacion DESC;
END;

GO
/****** Objeto: StoredProcedure [dbo].[sp_th_consultar_historial] Fecha de script: 5/8/2026 09:52:37 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO

CREATE   PROCEDURE [dbo].[sp_th_consultar_historial]
    @usuario     VARCHAR(50),
    @ip          VARCHAR(45) = '0.0.0.0',
    @cargo       VARCHAR(150) = NULL,
    @empleado_id INT = NULL
AS
BEGIN
    SET NOCOUNT ON;
    EXEC dbo.sp_th_registrar_auditoria
        @usuario, 'Historial Laboral', 'CONSULTAR_VISTA',
        'Consulta de vw_th_reporte_historial_jerarquico', @ip;

    SELECT *
    FROM dbo.vw_th_reporte_historial_jerarquico
    WHERE (@cargo IS NULL OR nombre_puesto LIKE '%' + @cargo + '%')
      AND (@empleado_id IS NULL OR empleado_id = @empleado_id)
    ORDER BY tipo_proceso DESC, direccion_actual_unificada, funcionario, fecha_desde;
END;

GO
/****** Objeto: StoredProcedure [dbo].[sp_th_consultar_nacionalidades] Fecha de script: 5/8/2026 09:52:37 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO

CREATE   PROCEDURE [dbo].[sp_th_consultar_nacionalidades]
    @usuario VARCHAR(50),@ip VARCHAR(45)='0.0.0.0'
AS
BEGIN
    SET NOCOUNT ON;
    EXEC dbo.sp_th_registrar_auditoria @usuario,'Nacionalidades','CONSULTAR_CATALOGO','Consulta del catalogo de nacionalidades.',@ip;
    SELECT nacionalidad_id,codigo_iso,pais,nombre,aliases
    FROM dbo.th_nacionalidades WHERE activo=1 ORDER BY nombre,pais;
END;

GO
/****** Objeto: StoredProcedure [dbo].[sp_th_consultar_puestos] Fecha de script: 5/8/2026 09:52:37 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO

CREATE   PROCEDURE [dbo].[sp_th_consultar_puestos]
    @usuario VARCHAR(50), @ip VARCHAR(45) = '0.0.0.0'
AS
BEGIN
    SET NOCOUNT ON;
    EXEC dbo.sp_th_registrar_auditoria @usuario, 'Denominaciones de Puestos', 'CONSULTAR_VISTA',
         'Consulta de th_puestos', @ip;
    SELECT puesto_id,codigo_puesto,nombre_puesto,remuneracion_unificada,CONVERT(BIT,ISNULL(activo,0)) activo
    FROM dbo.th_puestos ORDER BY nombre_puesto;
END;

GO
/****** Objeto: StoredProcedure [dbo].[sp_th_consultar_unidades] Fecha de script: 5/8/2026 09:52:37 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO

CREATE   PROCEDURE [dbo].[sp_th_consultar_unidades]
    @usuario VARCHAR(50), @ip VARCHAR(45) = '0.0.0.0'
AS
BEGIN
    SET NOCOUNT ON;
    EXEC dbo.sp_th_registrar_auditoria @usuario, 'Maestros Organizacionales', 'CONSULTAR_VISTA',
         'Consulta de vw_th_maestros_organizacionales', @ip;
    SELECT * FROM dbo.vw_th_maestros_organizacionales
    ORDER BY CASE WHEN unidad_padre_id IS NULL THEN unidad_id ELSE unidad_padre_id END,
             CASE WHEN unidad_padre_id IS NULL THEN 0 ELSE 1 END, nombre_unidad;
END;

GO
/****** Objeto: StoredProcedure [dbo].[sp_th_eliminar_empleado] Fecha de script: 5/8/2026 09:52:37 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE   PROCEDURE [dbo].[sp_th_eliminar_empleado]
    @id INT,@usuario VARCHAR(50)='SISTEMA',@ip VARCHAR(45)='0.0.0.0'
AS
BEGIN
    SET NOCOUNT ON;
    DECLARE @estado BIT;
    SELECT @estado=estado FROM dbo.th_empleados WHERE empleado_id=@id;
    IF @estado IS NULL BEGIN SELECT 0 exito,'El empleado no existe.' mensaje; RETURN; END;
    IF @estado=0 BEGIN SELECT 0 exito,'El empleado ya se encuentra inactivo.' mensaje; RETURN; END;
    DECLARE @fecha DATE=CONVERT(date,GETDATE());
    EXEC dbo.sp_th_cambiar_estado_empleado @empleado_id=@id,@estado=0,@fecha_efectiva=@fecha,
         @motivo=N'Baja lógica registrada desde el Directorio.',@origen='DIRECTORIO',@accion_id=NULL,
         @usuario=@usuario,@ip=@ip,@gestionar_historial=1,@emitir_resultado=0;
    SELECT 1 exito,'Funcionario marcado inactivo y ciclo laboral cerrado.' mensaje;
END;

GO
/****** Objeto: StoredProcedure [dbo].[sp_th_guardar_empleado] Fecha de script: 5/8/2026 09:52:37 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO

/* --------------------------------------------------------------------------
   5. Empleados: nombres/apellidos correctos y auditoria de escritura
   -------------------------------------------------------------------------- */
CREATE   PROCEDURE [dbo].[sp_th_guardar_empleado]
    @cedula VARCHAR(20), @apellidos VARCHAR(100), @nombres VARCHAR(100),
    @fecha_nac DATE = NULL, @condicion VARCHAR(50) = 'Ninguna',
    @tipo_disc VARCHAR(80) = NULL, @porcentaje_disc DECIMAL(5,2) = NULL,
    @sexo CHAR(1) = NULL, @estado_civil VARCHAR(30) = NULL,
    @nacionalidad VARCHAR(50) = NULL, @tipo_sangre VARCHAR(10) = NULL,
    @depto INT = NULL, @puesto INT = NULL, @tipo_contrato VARCHAR(100) = NULL,
    @fecha_ing DATE = NULL, @sueldo DECIMAL(10,2) = NULL,
    @jornada VARCHAR(30) = 'Completa', @correo VARCHAR(100) = NULL,
    @celular VARCHAR(20) = NULL, @convencional VARCHAR(20) = NULL,
    @ciudad VARCHAR(50) = NULL, @direccion VARCHAR(MAX) = NULL,
    @contacto_emerg VARCHAR(150) = NULL, @parentesco VARCHAR(50) = NULL,
    @tel_emerg VARCHAR(20) = NULL, @nivel_estudio VARCHAR(80) = NULL,
    @titulo VARCHAR(150) = NULL, @iess VARCHAR(30) = NULL,
    @foto VARCHAR(300) = 'public/img/default_avatar.png', @obs VARCHAR(MAX) = NULL,
    @usuario VARCHAR(50) = 'SISTEMA', @ip VARCHAR(45) = '0.0.0.0'
AS
BEGIN
    SET NOCOUNT ON;
    SET XACT_ABORT ON;
    BEGIN TRY
        BEGIN TRAN;
        IF NULLIF(LTRIM(RTRIM(@apellidos)), '') IS NULL OR NULLIF(LTRIM(RTRIM(@nombres)), '') IS NULL
            THROW 51021, 'Nombres y apellidos son obligatorios y deben enviarse por separado.', 1;
        IF EXISTS (SELECT 1 FROM dbo.th_empleados WHERE identificacion = @cedula)
            THROW 51022, 'Ya existe un empleado con esa identificacion.', 1;

        INSERT dbo.th_empleados (
            identificacion, apellidos, nombres, fecha_nacimiento, sexo, estado_civil, nacionalidad,
            unidad_id, puesto_id, fecha_ingreso, sueldo_rmu, correo_institucional, telefono_movil,
            telefono_convencional, ciudad_residencia, direccion_domiciliaria, codigo_iess, ruta_foto,
            observaciones, tipo_contrato, jornada, condicion_especial, tipo_discapacidad,
            porcentaje_discapacidad, tipo_sangre, contacto_emergencia, emergencia_relacion,
            tel_emergencia, nivel_estudio, titulo, estado, cargas_familiares, fecha_creacion
        ) VALUES (
            @cedula, UPPER(LTRIM(RTRIM(@apellidos))), UPPER(LTRIM(RTRIM(@nombres))), @fecha_nac,
            @sexo, @estado_civil, @nacionalidad, @depto, @puesto, @fecha_ing, @sueldo,
            ISNULL(@correo, ''), ISNULL(@celular, ''), @convencional, ISNULL(@ciudad, ''),
            ISNULL(@direccion, ''), @iess, ISNULL(@foto, 'public/img/default_avatar.png'), @obs,
            @tipo_contrato, @jornada, @condicion, @tipo_disc, @porcentaje_disc, @tipo_sangre,
            @contacto_emerg, @parentesco, @tel_emerg, @nivel_estudio, @titulo, 1, 0, GETDATE()
        );
        DECLARE @nuevo_id INT = CONVERT(INT, SCOPE_IDENTITY());
        DECLARE @detalle_auditoria VARCHAR(500) = CONCAT('Empleado #', @nuevo_id, ' C.I. ', @cedula, ' registrado.');
        EXEC dbo.sp_th_registrar_auditoria @usuario, 'Directorio de Personal', 'CREAR',
             @detalle_auditoria, @ip;
        COMMIT;
        SELECT @nuevo_id AS nuevo_id, 1 AS exito, 'Empleado guardado correctamente.' AS mensaje;
    END TRY
    BEGIN CATCH
        IF @@TRANCOUNT > 0 ROLLBACK;
        SELECT 0 AS nuevo_id, 0 AS exito, ERROR_MESSAGE() AS mensaje;
    END CATCH;
END;

GO
/****** Objeto: StoredProcedure [dbo].[sp_th_guardar_puesto] Fecha de script: 5/8/2026 09:52:37 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO

CREATE   PROCEDURE [dbo].[sp_th_guardar_puesto]
    @puesto_id     INT = NULL,
    @nombre        VARCHAR(150),
    @remuneracion  DECIMAL(10,2) = 0,
    @activo        BIT = 1,
    @usuario       VARCHAR(50),
    @ip            VARCHAR(45) = '0.0.0.0'
AS
BEGIN
    SET NOCOUNT ON;
    SET XACT_ABORT ON;
    BEGIN TRY
        BEGIN TRAN;
        SET @nombre = UPPER(LTRIM(RTRIM(@nombre)));
        IF @nombre = '' THROW 51011, 'La denominacion del puesto es obligatoria.', 1;
        IF @remuneracion < 0 THROW 51012, 'La remuneracion no puede ser negativa.', 1;
        IF EXISTS (SELECT 1 FROM dbo.th_puestos
                   WHERE UPPER(LTRIM(RTRIM(nombre_puesto))) = @nombre
                     AND puesto_id <> ISNULL(@puesto_id, 0))
            THROW 51013, 'Ya existe una denominacion con ese nombre.', 1;

        DECLARE @accion VARCHAR(20);
        IF @puesto_id IS NULL OR @puesto_id = 0
        BEGIN
            INSERT dbo.th_puestos(codigo_puesto, nombre_puesto, remuneracion_unificada, activo)
            VALUES (CONCAT('TMP-', RIGHT(REPLACE(CONVERT(VARCHAR(36), NEWID()), '-', ''), 16)), @nombre, @remuneracion, @activo);
            SET @puesto_id = CONVERT(INT, SCOPE_IDENTITY());
            UPDATE dbo.th_puestos
            SET codigo_puesto = CONCAT('PST-', RIGHT('000000' + CONVERT(VARCHAR(10), @puesto_id), 6))
            WHERE puesto_id = @puesto_id;
            SET @accion = 'CREAR';
        END
        ELSE
        BEGIN
            UPDATE dbo.th_puestos
            SET nombre_puesto = @nombre,
                remuneracion_unificada = @remuneracion,
                activo = @activo
            WHERE puesto_id = @puesto_id;
            IF @@ROWCOUNT = 0 THROW 51014, 'El puesto indicado no existe.', 1;
            SET @accion = 'ACTUALIZAR';
        END;

        DECLARE @detalle_auditoria VARCHAR(500) = CONCAT('Puesto #', @puesto_id, ': ', @nombre, '; estado=', @activo);
        EXEC dbo.sp_th_registrar_auditoria @usuario, 'Denominaciones de Puestos', @accion,
             @detalle_auditoria, @ip;
        COMMIT;
        SELECT 1 AS exito, @puesto_id AS id, 'Denominacion guardada correctamente.' AS mensaje;
    END TRY
    BEGIN CATCH
        IF @@TRANCOUNT > 0 ROLLBACK;
        SELECT 0 AS exito, ISNULL(@puesto_id, 0) AS id, ERROR_MESSAGE() AS mensaje;
    END CATCH;
END;

GO
/****** Objeto: StoredProcedure [dbo].[sp_th_guardar_unidad] Fecha de script: 5/8/2026 09:52:37 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO

/* --------------------------------------------------------------------------
   4. CRUD de Direcciones, Areas y denominaciones de puestos
   -------------------------------------------------------------------------- */
CREATE   PROCEDURE [dbo].[sp_th_guardar_unidad]
    @unidad_id       INT = NULL,
    @nombre          VARCHAR(150),
    @tipo_proceso    VARCHAR(50),
    @unidad_padre_id INT = NULL,
    @activo          BIT = 1,
    @usuario         VARCHAR(50),
    @ip              VARCHAR(45) = '0.0.0.0'
AS
BEGIN
    SET NOCOUNT ON;
    SET XACT_ABORT ON;

    BEGIN TRY
        BEGIN TRAN;

        SET @nombre = UPPER(LTRIM(RTRIM(@nombre)));
        IF @nombre = '' THROW 51001, 'El nombre de la unidad es obligatorio.', 1;
        IF @unidad_padre_id = @unidad_id THROW 51002, 'Una unidad no puede ser su propio padre.', 1;
        IF @unidad_padre_id IS NOT NULL
           AND NOT EXISTS (SELECT 1 FROM dbo.th_unidades_organizacionales WHERE unidad_id = @unidad_padre_id AND activo = 1)
            THROW 51003, 'La direccion padre no existe o esta inactiva.', 1;
        IF EXISTS (
            SELECT 1 FROM dbo.th_unidades_organizacionales
            WHERE UPPER(LTRIM(RTRIM(nombre_unidad))) = @nombre
              AND ISNULL(unidad_padre_id, 0) = ISNULL(@unidad_padre_id, 0)
              AND unidad_id <> ISNULL(@unidad_id, 0)
        ) THROW 51004, 'Ya existe una unidad con ese nombre en el mismo nivel.', 1;

        DECLARE @accion VARCHAR(20);
        IF @unidad_id IS NULL OR @unidad_id = 0
        BEGIN
            INSERT dbo.th_unidades_organizacionales
                (codigo_uorg, nombre_unidad, tipo_proceso, unidad_padre_id, activo, fecha_inicio)
            VALUES
                (CONCAT('TMP-', RIGHT(REPLACE(CONVERT(VARCHAR(36), NEWID()), '-', ''), 16)), @nombre, @tipo_proceso, @unidad_padre_id, @activo, CONVERT(DATE, GETDATE()));
            SET @unidad_id = CONVERT(INT, SCOPE_IDENTITY());
            UPDATE dbo.th_unidades_organizacionales
            SET codigo_uorg = CONCAT(CASE WHEN @unidad_padre_id IS NULL THEN 'DIR-' ELSE 'ARE-' END,
                                     RIGHT('000000' + CONVERT(VARCHAR(10), @unidad_id), 6))
            WHERE unidad_id = @unidad_id;
            SET @accion = 'CREAR';
        END
        ELSE
        BEGIN
            UPDATE dbo.th_unidades_organizacionales
            SET nombre_unidad = @nombre,
                tipo_proceso = @tipo_proceso,
                unidad_padre_id = @unidad_padre_id,
                activo = @activo,
                fecha_fin = CASE WHEN @activo = 0 THEN COALESCE(fecha_fin, CONVERT(DATE, GETDATE())) ELSE NULL END
            WHERE unidad_id = @unidad_id;
            IF @@ROWCOUNT = 0 THROW 51005, 'La unidad indicada no existe.', 1;
            SET @accion = 'ACTUALIZAR';
        END;

        DECLARE @detalle_auditoria VARCHAR(500) = CONCAT('Unidad #', @unidad_id, ': ', @nombre, '; estado=', @activo);
        EXEC dbo.sp_th_registrar_auditoria @usuario, 'Maestros Organizacionales', @accion,
             @detalle_auditoria, @ip;
        COMMIT;
        SELECT 1 AS exito, @unidad_id AS id, 'Unidad guardada correctamente.' AS mensaje;
    END TRY
    BEGIN CATCH
        IF @@TRANCOUNT > 0 ROLLBACK;
        SELECT 0 AS exito, ISNULL(@unidad_id, 0) AS id, ERROR_MESSAGE() AS mensaje;
    END CATCH;
END;

GO
/****** Objeto: StoredProcedure [dbo].[sp_th_modificar_empleado] Fecha de script: 5/8/2026 09:52:37 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO

CREATE   PROCEDURE [dbo].[sp_th_modificar_empleado]
    @id INT, @cedula VARCHAR(20), @apellidos VARCHAR(100), @nombres VARCHAR(100),
    @fecha_nac DATE = NULL, @condicion VARCHAR(50) = 'Ninguna',
    @tipo_disc VARCHAR(80) = NULL, @porcentaje_disc DECIMAL(5,2) = NULL,
    @sexo CHAR(1) = NULL, @estado_civil VARCHAR(30) = NULL,
    @nacionalidad VARCHAR(50) = NULL, @tipo_sangre VARCHAR(10) = NULL,
    @depto INT = NULL, @puesto INT = NULL, @tipo_contrato VARCHAR(100) = NULL,
    @fecha_ing DATE = NULL, @sueldo DECIMAL(10,2) = NULL,
    @jornada VARCHAR(30) = 'Completa', @correo VARCHAR(100) = NULL,
    @celular VARCHAR(20) = NULL, @convencional VARCHAR(20) = NULL,
    @ciudad VARCHAR(50) = NULL, @direccion VARCHAR(MAX) = NULL,
    @contacto_emerg VARCHAR(150) = NULL, @parentesco VARCHAR(50) = NULL,
    @tel_emerg VARCHAR(20) = NULL, @nivel_estudio VARCHAR(80) = NULL,
    @titulo VARCHAR(150) = NULL, @iess VARCHAR(30) = NULL,
    @foto VARCHAR(300) = NULL, @obs VARCHAR(MAX) = NULL,
    @usuario VARCHAR(50) = 'SISTEMA', @ip VARCHAR(45) = '0.0.0.0'
AS
BEGIN
    SET NOCOUNT ON;
    SET XACT_ABORT ON;
    BEGIN TRY
        BEGIN TRAN;
        IF NULLIF(LTRIM(RTRIM(@apellidos)), '') IS NULL OR NULLIF(LTRIM(RTRIM(@nombres)), '') IS NULL
            THROW 51023, 'Nombres y apellidos son obligatorios.', 1;
        IF EXISTS (SELECT 1 FROM dbo.th_empleados WHERE identificacion = @cedula AND empleado_id <> @id)
            THROW 51024, 'La identificacion pertenece a otro empleado.', 1;

        UPDATE dbo.th_empleados SET
            identificacion=@cedula, apellidos=UPPER(LTRIM(RTRIM(@apellidos))), nombres=UPPER(LTRIM(RTRIM(@nombres))),
            fecha_nacimiento=@fecha_nac, sexo=@sexo, estado_civil=@estado_civil, nacionalidad=@nacionalidad,
            unidad_id=@depto, puesto_id=@puesto, fecha_ingreso=@fecha_ing, sueldo_rmu=@sueldo,
            correo_institucional=ISNULL(@correo, correo_institucional), telefono_movil=ISNULL(@celular, telefono_movil),
            telefono_convencional=@convencional, ciudad_residencia=ISNULL(@ciudad, ciudad_residencia),
            direccion_domiciliaria=ISNULL(@direccion, direccion_domiciliaria), codigo_iess=@iess,
            ruta_foto=ISNULL(@foto, ruta_foto), observaciones=@obs, tipo_contrato=@tipo_contrato,
            jornada=@jornada, condicion_especial=@condicion, tipo_discapacidad=@tipo_disc,
            porcentaje_discapacidad=@porcentaje_disc, tipo_sangre=@tipo_sangre,
            contacto_emergencia=@contacto_emerg, emergencia_relacion=@parentesco,
            tel_emergencia=@tel_emerg, nivel_estudio=@nivel_estudio, titulo=@titulo
        WHERE empleado_id=@id;
        IF @@ROWCOUNT = 0 THROW 51025, 'El empleado indicado no existe.', 1;
        DECLARE @detalle_auditoria VARCHAR(500) = CONCAT('Empleado #', @id, ' C.I. ', @cedula, ' actualizado.');
        EXEC dbo.sp_th_registrar_auditoria @usuario, 'Directorio de Personal', 'ACTUALIZAR',
             @detalle_auditoria, @ip;
        COMMIT;
        SELECT 1 AS filas_afectadas, 1 AS exito, 'Empleado actualizado.' AS mensaje;
    END TRY
    BEGIN CATCH
        IF @@TRANCOUNT > 0 ROLLBACK;
        SELECT 0 AS filas_afectadas, 0 AS exito, ERROR_MESSAGE() AS mensaje;
    END CATCH;
END;

GO
/****** Objeto: StoredProcedure [dbo].[sp_th_mover_empleado] Fecha de script: 5/8/2026 09:52:37 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO

/* --------------------------------------------------------------------------
   7. Movimiento interno: cambia asignacion sin documento legal
   -------------------------------------------------------------------------- */
CREATE   PROCEDURE [dbo].[sp_th_mover_empleado]
    @empleado_id INT, @unidad_destino_id INT, @puesto_destino_id INT,
    @fecha_movimiento DATE, @motivo VARCHAR(500),
    @usuario VARCHAR(50), @ip VARCHAR(45) = '0.0.0.0'
AS
BEGIN
    SET NOCOUNT ON;
    SET XACT_ABORT ON;
    BEGIN TRY
        BEGIN TRAN;
        DECLARE @unidad_origen_id INT, @puesto_origen_id INT;
        SELECT @unidad_origen_id=unidad_id, @puesto_origen_id=puesto_id
        FROM dbo.th_empleados WITH (UPDLOCK, HOLDLOCK)
        WHERE empleado_id=@empleado_id AND estado=1;
        IF @unidad_origen_id IS NULL OR @puesto_origen_id IS NULL
            THROW 51041, 'El empleado no existe, esta inactivo o no tiene asignacion actual.', 1;
        IF NOT EXISTS (SELECT 1 FROM dbo.th_unidades_organizacionales WHERE unidad_id=@unidad_destino_id AND activo=1)
            THROW 51042, 'El area de destino no existe o esta inactiva.', 1;
        IF NOT EXISTS (SELECT 1 FROM dbo.th_puestos WHERE puesto_id=@puesto_destino_id AND activo=1)
            THROW 51043, 'El puesto de destino no existe o esta inactivo.', 1;
        IF @unidad_origen_id=@unidad_destino_id AND @puesto_origen_id=@puesto_destino_id
            THROW 51044, 'El destino coincide con la asignacion actual.', 1;
        IF NULLIF(LTRIM(RTRIM(@motivo)), '') IS NULL
            THROW 51045, 'El motivo del movimiento es obligatorio.', 1;

        UPDATE dbo.th_historial_laboral
        SET fecha_hasta=DATEADD(DAY,-1,@fecha_movimiento)
        WHERE empleado_id=@empleado_id AND fecha_hasta IS NULL;

        INSERT dbo.th_historial_laboral
            (empleado_id, puesto_id, unidad_id, fecha_desde, fecha_hasta, observaciones, usuario_crea, fecha_creacion)
        VALUES
            (@empleado_id, @puesto_destino_id, @unidad_destino_id, @fecha_movimiento, NULL,
             CONCAT('Movimiento interno sin Accion de Personal. ', LTRIM(RTRIM(@motivo))), @usuario, GETDATE());

        UPDATE dbo.th_empleados
        SET unidad_id=@unidad_destino_id, puesto_id=@puesto_destino_id
        WHERE empleado_id=@empleado_id;

        INSERT dbo.th_movimientos_personal
            (empleado_id, unidad_origen_id, puesto_origen_id, unidad_destino_id,
             puesto_destino_id, fecha_movimiento, motivo, usuario_crea, direccion_ip)
        VALUES
            (@empleado_id, @unidad_origen_id, @puesto_origen_id, @unidad_destino_id,
             @puesto_destino_id, @fecha_movimiento, LTRIM(RTRIM(@motivo)), @usuario, @ip);
        DECLARE @movimiento_id INT = CONVERT(INT, SCOPE_IDENTITY());

        DECLARE @detalle_auditoria VARCHAR(500) = CONCAT('Movimiento #', @movimiento_id, ' empleado #', @empleado_id,
                    ': unidad ', @unidad_origen_id, ' -> ', @unidad_destino_id,
                    '; puesto ', @puesto_origen_id, ' -> ', @puesto_destino_id, '.');
        EXEC dbo.sp_th_registrar_auditoria @usuario, 'Movimiento de Personal', 'MOVER',
             @detalle_auditoria, @ip;
        COMMIT;
        SELECT 1 AS exito, @movimiento_id AS movimiento_id, 'Movimiento interno registrado.' AS mensaje;
    END TRY
    BEGIN CATCH
        IF @@TRANCOUNT > 0 ROLLBACK;
        SELECT 0 AS exito, 0 AS movimiento_id, ERROR_MESSAGE() AS mensaje;
    END CATCH;
END;

GO
/****** Objeto: StoredProcedure [dbo].[sp_th_mover_empleados_lote] Fecha de script: 5/8/2026 09:52:37 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO

CREATE   PROCEDURE [dbo].[sp_th_mover_empleados_lote]
    @empleados_json NVARCHAR(MAX),@unidad_destino_id INT,@puesto_destino_id INT,
    @fecha_movimiento DATE,@motivo VARCHAR(500),@usuario VARCHAR(50),@ip VARCHAR(45)='0.0.0.0'
AS
BEGIN
    SET NOCOUNT ON; SET XACT_ABORT ON;
    BEGIN TRY
        BEGIN TRAN;
        IF ISJSON(@empleados_json)<>1 THROW 51400,'La seleccion de empleados no es valida.',1;
        DECLARE @seleccion TABLE(empleado_id INT PRIMARY KEY,unidad_origen_id INT,puesto_origen_id INT);
        INSERT @seleccion(empleado_id)
        SELECT DISTINCT TRY_CONVERT(INT,[value]) FROM OPENJSON(@empleados_json) WHERE TRY_CONVERT(INT,[value]) IS NOT NULL;
        IF (SELECT COUNT(*) FROM @seleccion)<2 THROW 51401,'Seleccione al menos dos empleados para un movimiento grupal.',1;
        IF NOT EXISTS(SELECT 1 FROM dbo.th_unidades_organizacionales WHERE unidad_id=@unidad_destino_id AND activo=1)
            THROW 51402,'La unidad de destino no existe o esta inactiva.',1;
        IF NOT EXISTS(SELECT 1 FROM dbo.th_puestos WHERE puesto_id=@puesto_destino_id AND activo=1)
            THROW 51403,'El cargo de destino no existe o esta inactivo.',1;
        IF NULLIF(LTRIM(RTRIM(@motivo)),'') IS NULL THROW 51404,'El motivo es obligatorio.',1;
        UPDATE s SET unidad_origen_id=e.unidad_id,puesto_origen_id=e.puesto_id
        FROM @seleccion s JOIN dbo.th_empleados e WITH(UPDLOCK,HOLDLOCK) ON e.empleado_id=s.empleado_id AND e.estado=1;
        IF EXISTS(SELECT 1 FROM @seleccion WHERE unidad_origen_id IS NULL OR puesto_origen_id IS NULL)
            THROW 51405,'La seleccion contiene empleados inexistentes, inactivos o sin asignacion.',1;
        IF EXISTS(SELECT 1 FROM @seleccion WHERE unidad_origen_id=@unidad_destino_id AND puesto_origen_id=@puesto_destino_id)
            THROW 51406,'Al menos un empleado ya posee la asignacion de destino.',1;
        IF EXISTS(SELECT 1 FROM @seleccion s JOIN dbo.th_historial_laboral h ON h.empleado_id=s.empleado_id AND h.fecha_hasta IS NULL WHERE @fecha_movimiento<h.fecha_desde)
            THROW 51407,'La fecha efectiva es anterior al inicio de un historial vigente.',1;

        INSERT dbo.th_movimientos_lote(unidad_destino_id,puesto_destino_id,fecha_movimiento,motivo,cantidad,usuario_crea,direccion_ip)
        VALUES(@unidad_destino_id,@puesto_destino_id,@fecha_movimiento,LTRIM(RTRIM(@motivo)),(SELECT COUNT(*) FROM @seleccion),@usuario,@ip);
        DECLARE @lote_id INT=CONVERT(INT,SCOPE_IDENTITY());
        UPDATE h SET fecha_hasta=DATEADD(DAY,-1,@fecha_movimiento)
        FROM dbo.th_historial_laboral h JOIN @seleccion s ON s.empleado_id=h.empleado_id WHERE h.fecha_hasta IS NULL;
        INSERT dbo.th_historial_laboral(empleado_id,puesto_id,unidad_id,fecha_desde,fecha_hasta,observaciones,usuario_crea,fecha_creacion)
        SELECT empleado_id,@puesto_destino_id,@unidad_destino_id,@fecha_movimiento,NULL,
               CONCAT('Movimiento interno grupal #',@lote_id,'. ',LTRIM(RTRIM(@motivo))),@usuario,GETDATE() FROM @seleccion;
        INSERT dbo.th_movimientos_personal(empleado_id,unidad_origen_id,puesto_origen_id,unidad_destino_id,puesto_destino_id,fecha_movimiento,motivo,usuario_crea,direccion_ip,lote_id)
        SELECT empleado_id,unidad_origen_id,puesto_origen_id,@unidad_destino_id,@puesto_destino_id,@fecha_movimiento,LTRIM(RTRIM(@motivo)),@usuario,@ip,@lote_id FROM @seleccion;
        UPDATE e SET unidad_id=@unidad_destino_id,puesto_id=@puesto_destino_id
        FROM dbo.th_empleados e JOIN @seleccion s ON s.empleado_id=e.empleado_id;
        DECLARE @detalle VARCHAR(500)=CONCAT('Movimiento grupal #',@lote_id,'. Empleados=',(SELECT COUNT(*) FROM @seleccion),'; unidad=',@unidad_destino_id,'; puesto=',@puesto_destino_id,'.');
        EXEC dbo.sp_th_registrar_auditoria @usuario,'Movimiento de Personal','MOVER_LOTE',@detalle,@ip;
        COMMIT;
        SELECT 1 exito,@lote_id lote_id,(SELECT COUNT(*) FROM @seleccion) cantidad,'Movimiento grupal aplicado correctamente.' mensaje;
    END TRY
    BEGIN CATCH
        IF @@TRANCOUNT>0 ROLLBACK;
        SELECT 0 exito,0 lote_id,0 cantidad,ERROR_MESSAGE() mensaje;
    END CATCH
END;

GO
/****** Objeto: StoredProcedure [dbo].[sp_th_obtener_expediente_impresion] Fecha de script: 5/8/2026 09:52:37 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO

/* Expediente completo para el primer formulario de Biblioteca. */
CREATE   PROCEDURE [dbo].[sp_th_obtener_expediente_impresion]
    @empleado_id INT,@usuario VARCHAR(50),@ip VARCHAR(45)='0.0.0.0'
AS
BEGIN
    SET NOCOUNT ON;
    EXEC dbo.sp_th_registrar_auditoria @usuario,'Formulario Principal','IMPRIMIR','Consulta de expediente completo para PDF.',@ip;
    SELECT e.*,u.nombre_unidad direccion_area,u.codigo_uorg,p.nombre_puesto cargo,p.codigo_puesto,
           p.remuneracion_unificada rmu_catalogo,padre.nombre_unidad direccion_padre,
           (SELECT STRING_AGG(n.nombre,', ') WITHIN GROUP(ORDER BY en.orden)
            FROM dbo.th_empleado_nacionalidades en JOIN dbo.th_nacionalidades n ON n.nacionalidad_id=en.nacionalidad_id
            WHERE en.empleado_id=e.empleado_id) nacionalidades
    FROM dbo.th_empleados e
    LEFT JOIN dbo.th_unidades_organizacionales u ON u.unidad_id=e.unidad_id
    LEFT JOIN dbo.th_unidades_organizacionales padre ON padre.unidad_id=u.unidad_padre_id
    LEFT JOIN dbo.th_puestos p ON p.puesto_id=e.puesto_id
    WHERE e.empleado_id=@empleado_id;
END;

GO
/****** Objeto: StoredProcedure [dbo].[sp_th_obtener_siguiente_id] Fecha de script: 5/8/2026 09:52:37 ******/
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
/****** Objeto: StoredProcedure [dbo].[sp_th_reconciliar_empleado_rolmaes] Fecha de script: 5/8/2026 09:52:37 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO

/* Permite corregir únicamente los campos conciliables del respaldo legado.
   La cuenta de la aplicación conserva el principio de mínimo privilegio: se
   concede EXECUTE sobre el procedimiento, no UPDATE directo sobre la tabla. */
CREATE   PROCEDURE [dbo].[sp_th_reconciliar_empleado_rolmaes]
    @empleado_id INT,
    @apellidos VARCHAR(100),
    @nombres VARCHAR(100),
    @telefono VARCHAR(20)=NULL,
    @sueldo DECIMAL(10,2)=NULL,
    @num_iess VARCHAR(30)=NULL,
    @codigo_iess VARCHAR(30)=NULL,
    @cod_emplea VARCHAR(20)=NULL
AS
BEGIN
    SET NOCOUNT ON;
    IF NOT EXISTS(SELECT 1 FROM dbo.th_empleados WHERE empleado_id=@empleado_id)
        THROW 51530,'El empleado indicado no existe.',1;
    IF NULLIF(LTRIM(RTRIM(@apellidos)),'') IS NULL OR NULLIF(LTRIM(RTRIM(@nombres)),'') IS NULL
        THROW 51531,'Los apellidos y nombres son obligatorios.',1;

    UPDATE dbo.th_empleados
    SET apellidos=UPPER(LTRIM(RTRIM(@apellidos))),
        nombres=UPPER(LTRIM(RTRIM(@nombres))),
        telefono_movil=NULLIF(LTRIM(RTRIM(@telefono)),''),
        sueldo_rmu=@sueldo,
        num_iess=NULLIF(LTRIM(RTRIM(@num_iess)),''),
        codigo_iess=NULLIF(LTRIM(RTRIM(@codigo_iess)),''),
        cod_emplea=NULLIF(LTRIM(RTRIM(@cod_emplea)),'')
    WHERE empleado_id=@empleado_id;

    SELECT 1 exito;
END;

GO
/****** Objeto: StoredProcedure [dbo].[sp_th_registrar_accion_personal] Fecha de script: 5/8/2026 09:52:37 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER OFF
GO

CREATE   PROCEDURE [dbo].[sp_th_registrar_accion_personal]
    @numero_accion VARCHAR(50), @empleado_id INT, @tipo_accion VARCHAR(100),
    @fecha_rige_desde DATE, @fecha_rige_hasta DATE=NULL, @explicacion_legal VARCHAR(MAX),
    @detalle_otro NVARCHAR(255)=NULL, @presento_declaracion VARCHAR(20)=NULL,
    @actual_unidad_id INT=NULL, @actual_puesto_id INT=NULL, @actual_lugar_trabajo NVARCHAR(150)=NULL,
    @actual_remuneracion DECIMAL(10,2)=NULL, @actual_proceso NVARCHAR(150)=NULL,
    @actual_nivel_gestion NVARCHAR(150)=NULL, @actual_grupo_ocupacional NVARCHAR(150)=NULL,
    @actual_grado NVARCHAR(50)=NULL, @actual_partida_presupuestaria NVARCHAR(100)=NULL,
    @propuesta_unidad_id INT=NULL, @propuesta_puesto_id INT=NULL, @propuesta_lugar_trabajo NVARCHAR(150)=NULL,
    @propuesta_remuneracion DECIMAL(10,2)=NULL, @propuesta_proceso NVARCHAR(150)=NULL,
    @propuesta_nivel_gestion NVARCHAR(150)=NULL, @propuesta_grupo_ocupacional NVARCHAR(150)=NULL,
    @propuesta_grado NVARCHAR(50)=NULL, @propuesta_partida_presupuestaria NVARCHAR(100)=NULL,
    @notificacion_electronica BIT=0, @correo_notificacion NVARCHAR(150)=NULL,
    @medio_notificacion NVARCHAR(100)=NULL, @documento_notificacion NVARCHAR(100)=NULL,
    @fecha_notificacion DATETIME2(0)=NULL,
    @responsable_th_nombre NVARCHAR(150)=NULL, @responsable_th_puesto NVARCHAR(150)=NULL,
    @autoridad_nombre NVARCHAR(150)=NULL, @autoridad_puesto NVARCHAR(150)=NULL,
    @elaborador_nombre NVARCHAR(150)=NULL, @elaborador_puesto NVARCHAR(150)=NULL,
    @revisor_nombre NVARCHAR(150)=NULL, @revisor_puesto NVARCHAR(150)=NULL,
    @registrador_nombre NVARCHAR(150)=NULL, @registrador_puesto NVARCHAR(150)=NULL,
    @notificador_nombre NVARCHAR(150)=NULL, @notificador_puesto NVARCHAR(150)=NULL,
    @usuario VARCHAR(50), @ip VARCHAR(45)='0.0.0.0'
AS
BEGIN
    SET NOCOUNT ON; SET XACT_ABORT ON;
    BEGIN TRY
        BEGIN TRAN;
        IF NOT EXISTS (SELECT 1 FROM dbo.th_empleados WHERE empleado_id=@empleado_id)
            THROW 51031,'El empleado indicado no existe.',1;

        /* El correlativo se calcula dentro de la misma transaccion y no se confia
           en el valor enviado por el navegador. El application lock evita que dos
           solicitudes concurrentes reciban el mismo numero. */
        DECLARE @anio CHAR(4)=CONVERT(CHAR(4),YEAR(GETDATE()));
        DECLARE @prefijo VARCHAR(30)=CONCAT('APM-TH-',@anio,'-');
        DECLARE @resultado_lock INT;
        DECLARE @recurso_lock NVARCHAR(255)=CONCAT('th_acciones_personal_secuencial_',@anio);
        EXEC @resultado_lock=sys.sp_getapplock
            @Resource=@recurso_lock,
            @LockMode='Exclusive',@LockOwner='Transaction',@LockTimeout=10000;
        IF @resultado_lock<0 THROW 51032,'No fue posible reservar el numero de Accion de Personal.',1;

        DECLARE @siguiente INT=COALESCE((
            SELECT MAX(TRY_CONVERT(INT,SUBSTRING(numero_accion,LEN(@prefijo)+1,20)))
            FROM dbo.th_acciones_personal WITH(UPDLOCK,HOLDLOCK)
            WHERE numero_accion LIKE @prefijo+'%'
        ),0)+1;
        SET @numero_accion=CONCAT(@prefijo,
            CASE WHEN @siguiente<1000 THEN RIGHT(CONCAT('000',@siguiente),3)
                 ELSE CONVERT(VARCHAR(20),@siguiente) END);

        INSERT dbo.th_acciones_personal (
            numero_accion,fecha_elaboracion,empleado_id,tipo_accion,fecha_rige_desde,fecha_rige_hasta,
            explicacion_legal,detalle_otro,presento_declaracion,
            actual_unidad_id,actual_puesto_id,actual_lugar_trabajo,actual_remuneracion,actual_proceso,
            actual_nivel_gestion,actual_grupo_ocupacional,actual_grado,actual_partida_presupuestaria,
            propuesta_unidad_id,propuesta_puesto_id,propuesta_lugar_trabajo,propuesta_remuneracion,propuesta_proceso,
            propuesta_nivel_gestion,propuesta_grupo_ocupacional,propuesta_grado,propuesta_partida_presupuestaria,
            notificacion_electronica,correo_notificacion,medio_notificacion,documento_notificacion,fecha_notificacion,
            responsable_th_nombre,responsable_th_puesto,autoridad_nombre,autoridad_puesto,
            elaborador_nombre,elaborador_puesto,revisor_nombre,revisor_puesto,
            registrador_nombre,registrador_puesto,notificador_nombre,notificador_puesto,
            estado_documento,usuario_crea,fecha_creacion
        ) VALUES (
            @numero_accion,CONVERT(DATE,GETDATE()),@empleado_id,@tipo_accion,@fecha_rige_desde,@fecha_rige_hasta,
            @explicacion_legal,@detalle_otro,@presento_declaracion,
            NULLIF(@actual_unidad_id,0),NULLIF(@actual_puesto_id,0),@actual_lugar_trabajo,@actual_remuneracion,@actual_proceso,
            @actual_nivel_gestion,@actual_grupo_ocupacional,@actual_grado,@actual_partida_presupuestaria,
            NULLIF(@propuesta_unidad_id,0),NULLIF(@propuesta_puesto_id,0),@propuesta_lugar_trabajo,@propuesta_remuneracion,@propuesta_proceso,
            @propuesta_nivel_gestion,@propuesta_grupo_ocupacional,@propuesta_grado,@propuesta_partida_presupuestaria,
            @notificacion_electronica,@correo_notificacion,@medio_notificacion,@documento_notificacion,@fecha_notificacion,
            @responsable_th_nombre,@responsable_th_puesto,@autoridad_nombre,@autoridad_puesto,
            @elaborador_nombre,@elaborador_puesto,@revisor_nombre,@revisor_puesto,
            @registrador_nombre,@registrador_puesto,@notificador_nombre,@notificador_puesto,
            'Aprobado',@usuario,SYSDATETIME()
        );
        DECLARE @accion_id INT=CONVERT(INT,SCOPE_IDENTITY());
        DECLARE @detalle_auditoria VARCHAR(500)=CONCAT('Genero ',@numero_accion,' para empleado #',@empleado_id,'.');
        EXEC dbo.sp_th_registrar_auditoria @usuario,'Accion de Personal','CREAR',@detalle_auditoria,@ip;
        COMMIT;
        SELECT 1 exito,@accion_id accion_id,@numero_accion numero_accion,'Accion registrada y auditada.' mensaje;
    END TRY
    BEGIN CATCH
        IF @@TRANCOUNT>0 ROLLBACK;
        SELECT 0 exito,0 accion_id,ERROR_MESSAGE() mensaje;
    END CATCH;
END;

GO
/****** Objeto: StoredProcedure [dbo].[sp_th_registrar_auditoria] Fecha de script: 5/8/2026 09:52:37 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO

/* --------------------------------------------------------------------------
   3. Auditoria central y consultas auditadas
   -------------------------------------------------------------------------- */
CREATE   PROCEDURE [dbo].[sp_th_registrar_auditoria]
    @usuario     VARCHAR(50),
    @modulo      VARCHAR(50),
    @accion      VARCHAR(50),
    @descripcion VARCHAR(500),
    @ip          VARCHAR(45) = '0.0.0.0'
AS
BEGIN
    SET NOCOUNT ON;
    INSERT dbo.th_logs_auditoria
        (fecha_hora, usuario, modulo, accion, descripcion_detalle, direccion_ip)
    VALUES
        (GETDATE(), LEFT(COALESCE(NULLIF(@usuario, ''), 'ANONIMO'), 50),
         LEFT(@modulo, 50), LEFT(@accion, 50), LEFT(@descripcion, 500),
         LEFT(COALESCE(NULLIF(@ip, ''), '0.0.0.0'), 45));
END;

GO
/****** Objeto: StoredProcedure [dbo].[sp_th_sincronizar_nacionalidades_empleado] Fecha de script: 5/8/2026 09:52:37 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO

CREATE   PROCEDURE [dbo].[sp_th_sincronizar_nacionalidades_empleado]
    @empleado_id INT,@nacionalidades_json NVARCHAR(MAX),@usuario VARCHAR(50),@ip VARCHAR(45)='0.0.0.0'
AS
BEGIN
    SET NOCOUNT ON; SET XACT_ABORT ON;
    BEGIN TRY
        BEGIN TRAN;
        IF NOT EXISTS(SELECT 1 FROM dbo.th_empleados WHERE empleado_id=@empleado_id)
            THROW 51300,'El empleado indicado no existe.',1;
        IF ISJSON(@nacionalidades_json)<>1 THROW 51301,'La lista de nacionalidades no es valida.',1;
        DECLARE @ids TABLE(id INT PRIMARY KEY,orden INT);
        INSERT @ids(id,orden)
        SELECT CONVERT(INT,[value]),CONVERT(INT,[key])+1 FROM OPENJSON(@nacionalidades_json)
        WHERE TRY_CONVERT(INT,[value]) IS NOT NULL;
        IF EXISTS(SELECT 1 FROM @ids i LEFT JOIN dbo.th_nacionalidades n ON n.nacionalidad_id=i.id AND n.activo=1 WHERE n.nacionalidad_id IS NULL)
            THROW 51302,'Una nacionalidad seleccionada no existe o esta inactiva.',1;
        DELETE FROM dbo.th_empleado_nacionalidades WHERE empleado_id=@empleado_id;
        INSERT dbo.th_empleado_nacionalidades(empleado_id,nacionalidad_id,es_principal,orden,usuario_crea)
        SELECT @empleado_id,id,CASE WHEN orden=1 THEN 1 ELSE 0 END,orden,@usuario FROM @ids;
        UPDATE e SET nacionalidad=(SELECT TOP 1 n.nombre FROM @ids i JOIN dbo.th_nacionalidades n ON n.nacionalidad_id=i.id ORDER BY i.orden)
        FROM dbo.th_empleados e WHERE e.empleado_id=@empleado_id;
        DECLARE @detalle VARCHAR(500)=CONCAT('Actualizo nacionalidades del empleado #',@empleado_id,'. Total=',(SELECT COUNT(*) FROM @ids));
        EXEC dbo.sp_th_registrar_auditoria @usuario,'Directorio de Personal','ACTUALIZAR_NACIONALIDADES',@detalle,@ip;
        COMMIT;
        SELECT 1 exito,'Nacionalidades actualizadas.' mensaje;
    END TRY
    BEGIN CATCH
        IF @@TRANCOUNT>0 ROLLBACK;
        SELECT 0 exito,ERROR_MESSAGE() mensaje;
    END CATCH
END;

GO
USE [master]
GO
ALTER DATABASE [Talento_Humano] SET  READ_WRITE 
GO
