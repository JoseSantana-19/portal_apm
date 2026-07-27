USE [master]
GO
/****** Objeto: Database [PortuariaDemo] Fecha de script: 26/7/2026 22:40:59 ******/
CREATE DATABASE [PortuariaDemo]
 CONTAINMENT = NONE
 ON  PRIMARY 
( NAME = N'PortuariaDemo', FILENAME = N'C:\Program Files\Microsoft SQL Server\MSSQL16.VICTUS\MSSQL\DATA\PortuariaDemo.mdf' , SIZE = 8192KB , MAXSIZE = UNLIMITED, FILEGROWTH = 1024KB )
 LOG ON 
( NAME = N'PortuariaDemo_log', FILENAME = N'C:\Program Files\Microsoft SQL Server\MSSQL16.VICTUS\MSSQL\DATA\PortuariaDemo_log.ldf' , SIZE = 4288KB , MAXSIZE = 2048GB , FILEGROWTH = 10%)
GO
ALTER DATABASE [PortuariaDemo] SET COMPATIBILITY_LEVEL = 120
GO
IF (1 = FULLTEXTSERVICEPROPERTY('IsFullTextInstalled'))
begin
EXEC [PortuariaDemo].[dbo].[sp_fulltext_database] @action = 'enable'
end
GO
ALTER DATABASE [PortuariaDemo] SET ANSI_NULL_DEFAULT OFF 
GO
ALTER DATABASE [PortuariaDemo] SET ANSI_NULLS OFF 
GO
ALTER DATABASE [PortuariaDemo] SET ANSI_PADDING OFF 
GO
ALTER DATABASE [PortuariaDemo] SET ANSI_WARNINGS OFF 
GO
ALTER DATABASE [PortuariaDemo] SET ARITHABORT OFF 
GO
ALTER DATABASE [PortuariaDemo] SET AUTO_CLOSE ON 
GO
ALTER DATABASE [PortuariaDemo] SET AUTO_SHRINK OFF 
GO
ALTER DATABASE [PortuariaDemo] SET AUTO_UPDATE_STATISTICS ON 
GO
ALTER DATABASE [PortuariaDemo] SET CURSOR_CLOSE_ON_COMMIT OFF 
GO
ALTER DATABASE [PortuariaDemo] SET CURSOR_DEFAULT  GLOBAL 
GO
ALTER DATABASE [PortuariaDemo] SET CONCAT_NULL_YIELDS_NULL OFF 
GO
ALTER DATABASE [PortuariaDemo] SET NUMERIC_ROUNDABORT OFF 
GO
ALTER DATABASE [PortuariaDemo] SET QUOTED_IDENTIFIER OFF 
GO
ALTER DATABASE [PortuariaDemo] SET RECURSIVE_TRIGGERS OFF 
GO
ALTER DATABASE [PortuariaDemo] SET  DISABLE_BROKER 
GO
ALTER DATABASE [PortuariaDemo] SET AUTO_UPDATE_STATISTICS_ASYNC OFF 
GO
ALTER DATABASE [PortuariaDemo] SET DATE_CORRELATION_OPTIMIZATION OFF 
GO
ALTER DATABASE [PortuariaDemo] SET TRUSTWORTHY OFF 
GO
ALTER DATABASE [PortuariaDemo] SET ALLOW_SNAPSHOT_ISOLATION OFF 
GO
ALTER DATABASE [PortuariaDemo] SET PARAMETERIZATION SIMPLE 
GO
ALTER DATABASE [PortuariaDemo] SET READ_COMMITTED_SNAPSHOT OFF 
GO
ALTER DATABASE [PortuariaDemo] SET HONOR_BROKER_PRIORITY OFF 
GO
ALTER DATABASE [PortuariaDemo] SET RECOVERY SIMPLE 
GO
ALTER DATABASE [PortuariaDemo] SET  MULTI_USER 
GO
ALTER DATABASE [PortuariaDemo] SET PAGE_VERIFY CHECKSUM  
GO
ALTER DATABASE [PortuariaDemo] SET DB_CHAINING OFF 
GO
ALTER DATABASE [PortuariaDemo] SET FILESTREAM( NON_TRANSACTED_ACCESS = OFF ) 
GO
ALTER DATABASE [PortuariaDemo] SET TARGET_RECOVERY_TIME = 0 SECONDS 
GO
ALTER DATABASE [PortuariaDemo] SET DELAYED_DURABILITY = DISABLED
GO
USE [PortuariaDemo]
GO
/****** Objeto: UserDefinedFunction [dbo].[fn_turno_por_hora] Fecha de script: 26/7/2026 22:40:59 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE FUNCTION [dbo].[fn_turno_por_hora] (@hora TIME)
RETURNS NVARCHAR(10)
AS
BEGIN
    RETURN (
        CASE
            WHEN @hora >= '07:00:00' AND @hora < '15:00:00' THEN N'Mañana'
            WHEN @hora >= '15:00:00' AND @hora < '23:00:00' THEN N'Tarde'
            ELSE N'Noche'
        END
    );
END;

GO
/****** Objeto: Table [dbo].[bit_departamentos] Fecha de script: 26/7/2026 22:40:59 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[bit_departamentos](
	[iddepartamento] [int] IDENTITY(1,1) NOT NULL,
	[nombre] [nvarchar](120) NOT NULL,
	[detalle] [nvarchar](120) NULL,
	[estado] [bit] NOT NULL,
PRIMARY KEY CLUSTERED 
(
	[iddepartamento] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
) ON [PRIMARY]
GO
/****** Objeto: View [dbo].[vw_th_departamentos_activos] Fecha de script: 26/7/2026 22:40:59 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO

CREATE VIEW [dbo].[vw_th_departamentos_activos] AS
SELECT 
    iddepartamento,
    nombre,
    detalle,
    estado
FROM dbo.bit_departamentos
WHERE estado = 1;
GO
/****** Objeto: Table [dbo].[bit_acc_secuenciales] Fecha de script: 26/7/2026 22:40:59 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[bit_acc_secuenciales](
	[id_secuencial] [int] IDENTITY(1,1) NOT NULL,
	[modulo] [nvarchar](80) NOT NULL,
	[tabla] [nvarchar](128) NOT NULL,
	[descripcion] [nvarchar](250) NULL,
	[prefijo_codigo] [nvarchar](30) NOT NULL,
	[ultimo_numero] [int] NOT NULL,
	[longitud] [int] NOT NULL,
	[estado] [bit] NOT NULL,
	[fecha_creacion] [datetime] NOT NULL,
	[fecha_actualizacion] [datetime] NULL,
PRIMARY KEY CLUSTERED 
(
	[id_secuencial] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY],
 CONSTRAINT [UQ_acc_secuenciales_tabla] UNIQUE NONCLUSTERED 
(
	[tabla] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
) ON [PRIMARY]
GO
/****** Objeto: Table [dbo].[bit_camaras] Fecha de script: 26/7/2026 22:40:59 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[bit_camaras](
	[id_bitacora_camara] [int] IDENTITY(1,1) NOT NULL,
	[fecha] [date] NOT NULL,
	[secuencia] [nvarchar](100) NULL,
	[turno] [nvarchar](50) NULL,
	[hora_inicio] [time](7) NULL,
	[hora_fin] [time](7) NULL,
	[consolista] [nvarchar](150) NULL,
	[hora_registro] [time](7) NOT NULL,
	[novedad] [nvarchar](max) NULL,
	[camara_ip] [nvarchar](50) NULL,
	[ubicacion] [nvarchar](150) NULL,
	[sitio] [nvarchar](80) NULL,
	[estado_camara] [int] NULL,
	[observaciones] [nvarchar](max) NULL,
	[usuario_registro] [nvarchar](150) NULL,
	[estado] [bit] NOT NULL,
	[fecha_creacion] [datetime] NOT NULL,
	[fecha_actualizacion] [datetime] NULL,
	[id_camara] [int] NULL,
	[nivel_alerta] [int] NOT NULL,
	[id_motivo_camara] [int] NULL,
	[sec_bitacora] [int] NULL,
	[codigo_bitacora] [nvarchar](30) NULL,
	[tipo_registro] [int] NOT NULL,
	[rol_responsable] [nvarchar](50) NULL,
PRIMARY KEY CLUSTERED 
(
	[id_bitacora_camara] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
) ON [PRIMARY] TEXTIMAGE_ON [PRIMARY]
GO
/****** Objeto: Table [dbo].[bit_configuracion] Fecha de script: 26/7/2026 22:40:59 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[bit_configuracion](
	[clave] [nvarchar](50) NOT NULL,
	[valor] [nvarchar](max) NULL,
PRIMARY KEY CLUSTERED 
(
	[clave] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
) ON [PRIMARY] TEXTIMAGE_ON [PRIMARY]
GO
/****** Objeto: Table [dbo].[bit_destinos] Fecha de script: 26/7/2026 22:40:59 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[bit_destinos](
	[id_destino] [int] IDENTITY(1,1) NOT NULL,
	[nombre] [nvarchar](150) NOT NULL,
	[estado] [bit] NOT NULL,
PRIMARY KEY CLUSTERED 
(
	[id_destino] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
) ON [PRIMARY]
GO
/****** Objeto: Table [dbo].[bit_empresas] Fecha de script: 26/7/2026 22:40:59 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[bit_empresas](
	[id_empresa] [int] IDENTITY(1,1) NOT NULL,
	[empresa] [nvarchar](150) NOT NULL,
	[razonsocial] [nvarchar](200) NULL,
	[ruc] [nvarchar](20) NULL,
	[estado] [bit] NOT NULL,
PRIMARY KEY CLUSTERED 
(
	[id_empresa] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
) ON [PRIMARY]
GO
/****** Objeto: Table [dbo].[bit_estados] Fecha de script: 26/7/2026 22:40:59 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[bit_estados](
	[idestado] [int] NOT NULL,
	[descripcion] [nvarchar](100) NOT NULL,
	[detalle] [nvarchar](255) NULL,
	[estado] [bit] NOT NULL,
 CONSTRAINT [PK_bit_estados] PRIMARY KEY CLUSTERED 
(
	[idestado] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
) ON [PRIMARY]
GO
/****** Objeto: Table [dbo].[bit_funcionarios] Fecha de script: 26/7/2026 22:40:59 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[bit_funcionarios](
	[id_funcionario] [int] IDENTITY(1,1) NOT NULL,
	[nombre] [nvarchar](150) NOT NULL,
	[cargo] [nvarchar](100) NOT NULL,
	[cedula] [nvarchar](20) NULL,
	[estado] [bit] NOT NULL,
PRIMARY KEY CLUSTERED 
(
	[id_funcionario] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
) ON [PRIMARY]
GO
/****** Objeto: Table [dbo].[bit_inv_camaras] Fecha de script: 26/7/2026 22:40:59 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[bit_inv_camaras](
	[id_camara] [int] IDENTITY(1,1) NOT NULL,
	[cod_old] [nvarchar](30) NULL,
	[codigo] [nvarchar](30) NULL,
	[tipo] [nvarchar](80) NULL,
	[marca] [nvarchar](80) NULL,
	[modelo] [nvarchar](120) NULL,
	[tecnologia] [nvarchar](50) NULL,
	[caracteristica] [nvarchar](250) NULL,
	[ip] [nvarchar](50) NULL,
	[mac] [nvarchar](80) NULL,
	[serie] [nvarchar](120) NULL,
	[ubicacion] [nvarchar](150) NULL,
	[detalle] [nvarchar](200) NULL,
	[grabador] [nvarchar](120) NULL,
	[estado] [bit] NOT NULL,
	[fecha_creacion] [datetime] NOT NULL,
	[fecha_actualizacion] [datetime] NULL,
	[sec_camara] [int] NULL,
	[codigo_secuencial] [nvarchar](30) NULL,
PRIMARY KEY CLUSTERED 
(
	[id_camara] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
) ON [PRIMARY]
GO
/****** Objeto: Table [dbo].[bit_motivos] Fecha de script: 26/7/2026 22:40:59 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[bit_motivos](
	[id_motivo] [int] IDENTITY(1,1) NOT NULL,
	[descripcion] [nvarchar](200) NOT NULL,
	[estado] [bit] NOT NULL,
PRIMARY KEY CLUSTERED 
(
	[id_motivo] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
) ON [PRIMARY]
GO
/****** Objeto: Table [dbo].[bit_motivos_camaras] Fecha de script: 26/7/2026 22:40:59 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[bit_motivos_camaras](
	[id_motivo_camara] [int] IDENTITY(1,1) NOT NULL,
	[sec_motivo] [int] NULL,
	[codigo_motivo] [nvarchar](30) NULL,
	[descripcion] [nvarchar](180) NOT NULL,
	[nivel_sugerido] [nvarchar](20) NOT NULL,
	[requiere_observacion] [bit] NOT NULL,
	[estado] [bit] NOT NULL,
	[fecha_creacion] [datetime] NOT NULL,
	[fecha_actualizacion] [datetime] NULL,
PRIMARY KEY CLUSTERED 
(
	[id_motivo_camara] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
) ON [PRIMARY]
GO
/****** Objeto: Table [dbo].[bit_movimientos] Fecha de script: 26/7/2026 22:40:59 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[bit_movimientos](
	[id_movimiento] [bigint] IDENTITY(1,1) NOT NULL,
	[id_usuario] [int] NULL,
	[tipo_evento] [nvarchar](20) NOT NULL,
	[descripcion] [nvarchar](500) NOT NULL,
	[turno] [nvarchar](10) NOT NULL,
	[fecha_hora] [datetime] NOT NULL,
PRIMARY KEY CLUSTERED 
(
	[id_movimiento] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
) ON [PRIMARY]
GO
/****** Objeto: Table [dbo].[bit_niveles_alerta] Fecha de script: 26/7/2026 22:40:59 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[bit_niveles_alerta](
	[id_alerta] [int] NOT NULL,
	[descripcion] [nvarchar](80) NOT NULL,
	[color_hex] [nvarchar](7) NOT NULL,
	[estado] [bit] NOT NULL,
PRIMARY KEY CLUSTERED 
(
	[id_alerta] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
) ON [PRIMARY]
GO
/****** Objeto: Table [dbo].[bit_niveles_incidente] Fecha de script: 26/7/2026 22:40:59 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[bit_niveles_incidente](
	[id_incidentes] [int] IDENTITY(1,1) NOT NULL,
	[nivel] [tinyint] NOT NULL,
	[descripcion] [nvarchar](50) NOT NULL,
	[estado] [bit] NOT NULL,
PRIMARY KEY CLUSTERED 
(
	[id_incidentes] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY],
 CONSTRAINT [UQ_niveles_incidente_nivel] UNIQUE NONCLUSTERED 
(
	[nivel] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
) ON [PRIMARY]
GO
/****** Objeto: Table [dbo].[bit_novedades] Fecha de script: 26/7/2026 22:40:59 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[bit_novedades](
	[idnovedad] [int] IDENTITY(1,1) NOT NULL,
	[id_reporte] [int] NOT NULL,
	[fecha] [date] NOT NULL,
	[hora] [time](0) NOT NULL,
	[descripcion] [nvarchar](2000) NOT NULL,
	[estado] [nvarchar](50) NOT NULL,
	[turno] [nvarchar](10) NULL,
PRIMARY KEY CLUSTERED 
(
	[idnovedad] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
) ON [PRIMARY]
GO
/****** Objeto: Table [dbo].[bit_parametro] Fecha de script: 26/7/2026 22:40:59 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[bit_parametro](
	[nombre] [varchar](100) NOT NULL,
	[valor] [varchar](255) NOT NULL,
PRIMARY KEY CLUSTERED 
(
	[nombre] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
) ON [PRIMARY]
GO
/****** Objeto: Table [dbo].[bit_personas] Fecha de script: 26/7/2026 22:40:59 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[bit_personas](
	[id_persona] [int] IDENTITY(1,1) NOT NULL,
	[nidentificacion] [nvarchar](20) NOT NULL,
	[tidentif] [nvarchar](20) NOT NULL,
	[nombres] [nvarchar](100) NOT NULL,
	[apellidos] [nvarchar](100) NOT NULL,
	[estado] [bit] NOT NULL,
	[genero] [char](1) NULL,
PRIMARY KEY CLUSTERED 
(
	[id_persona] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
) ON [PRIMARY]
GO
/****** Objeto: Table [dbo].[bit_reporte_supervisor] Fecha de script: 26/7/2026 22:40:59 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[bit_reporte_supervisor](
	[id_reporte] [int] IDENTITY(1,1) NOT NULL,
	[fecha_reporte] [date] NOT NULL,
	[usuario_genera] [nvarchar](150) NOT NULL,
	[creado_en] [datetime2](0) NOT NULL,
PRIMARY KEY CLUSTERED 
(
	[id_reporte] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY],
 CONSTRAINT [UQ_reporte_supervisor_fecha] UNIQUE NONCLUSTERED 
(
	[fecha_reporte] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
) ON [PRIMARY]
GO
/****** Objeto: Table [dbo].[bit_rondas_cabecera] Fecha de script: 26/7/2026 22:40:59 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[bit_rondas_cabecera](
	[id_ronda] [int] IDENTITY(1,1) NOT NULL,
	[id_usuario] [int] NOT NULL,
	[fecha] [date] NOT NULL,
	[turno] [nvarchar](20) NOT NULL,
	[hora_inicio] [time](7) NULL,
	[hora_fin] [time](7) NULL,
	[estado] [bit] NOT NULL,
PRIMARY KEY CLUSTERED 
(
	[id_ronda] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
) ON [PRIMARY]
GO
/****** Objeto: Table [dbo].[bit_rondas_detalles] Fecha de script: 26/7/2026 22:40:59 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[bit_rondas_detalles](
	[id_detalle] [int] IDENTITY(1,1) NOT NULL,
	[id_ronda] [int] NOT NULL,
	[hora_registro] [datetime] NOT NULL,
	[actividad] [nvarchar](max) NOT NULL,
	[id_alerta] [int] NOT NULL,
PRIMARY KEY CLUSTERED 
(
	[id_detalle] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
) ON [PRIMARY] TEXTIMAGE_ON [PRIMARY]
GO
/****** Objeto: Table [dbo].[bit_totales_actividades] Fecha de script: 26/7/2026 22:40:59 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[bit_totales_actividades](
	[id_actividades] [int] IDENTITY(1,1) NOT NULL,
	[fecha] [date] NOT NULL,
	[total_detalles] [int] NOT NULL,
PRIMARY KEY CLUSTERED 
(
	[id_actividades] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY],
 CONSTRAINT [UQ_totales_actividades_fecha] UNIQUE NONCLUSTERED 
(
	[fecha] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
) ON [PRIMARY]
GO
/****** Objeto: Table [dbo].[bit_totales_visitas] Fecha de script: 26/7/2026 22:40:59 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[bit_totales_visitas](
	[idtotal] [int] IDENTITY(1,1) NOT NULL,
	[fecha] [date] NOT NULL,
	[total_visitas] [int] NOT NULL,
	[total_activas] [int] NOT NULL,
	[total_proveedores] [int] NOT NULL,
PRIMARY KEY CLUSTERED 
(
	[idtotal] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY],
 CONSTRAINT [UQ_totales_visitas_fecha] UNIQUE NONCLUSTERED 
(
	[fecha] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
) ON [PRIMARY]
GO
/****** Objeto: Table [dbo].[bit_usuarios_apm] Fecha de script: 26/7/2026 22:40:59 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[bit_usuarios_apm](
	[id_usuario] [int] IDENTITY(1,1) NOT NULL,
	[cedula] [nvarchar](20) NOT NULL,
	[nombres] [nvarchar](150) NOT NULL,
	[id_departamento] [int] NOT NULL,
	[estado] [bit] NOT NULL,
	[password_hash] [nvarchar](255) NULL,
	[ultimo_ingreso] [datetime2](0) NULL,
	[creado_en] [datetime2](0) NOT NULL,
PRIMARY KEY CLUSTERED 
(
	[id_usuario] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
) ON [PRIMARY]
GO
/****** Objeto: Table [dbo].[bit_visitas] Fecha de script: 26/7/2026 22:40:59 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[bit_visitas](
	[id_visita] [int] IDENTITY(1,1) NOT NULL,
	[id_persona] [int] NOT NULL,
	[tipo_visitante] [nvarchar](20) NOT NULL,
	[id_empresa] [int] NULL,
	[id_funcionario] [int] NULL,
	[id_destino] [int] NOT NULL,
	[id_motivo] [int] NOT NULL,
	[detalle_motivo] [nvarchar](max) NULL,
	[id_nivel_incidente] [int] NOT NULL,
	[fecha_visita] [date] NOT NULL,
	[hora_entrada] [time](7) NOT NULL,
	[hora_salida] [time](7) NULL,
	[estado] [bit] NOT NULL,
PRIMARY KEY CLUSTERED 
(
	[id_visita] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
) ON [PRIMARY] TEXTIMAGE_ON [PRIMARY]
GO
/****** Objeto: Table [dbo].[estados] Fecha de script: 26/7/2026 22:40:59 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[estados](
	[idestado] [int] NOT NULL,
	[descripcion] [nvarchar](100) NOT NULL,
	[detalle] [nvarchar](255) NULL,
	[estado] [bit] NOT NULL,
 CONSTRAINT [PK_estados] PRIMARY KEY CLUSTERED 
(
	[idestado] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
) ON [PRIMARY]
GO
SET ANSI_PADDING ON
GO
/****** Objeto: Index [IX_bitacora_camaras_codigo_bitacora] Fecha de script: 26/7/2026 22:40:59 ******/
CREATE NONCLUSTERED INDEX [IX_bitacora_camaras_codigo_bitacora] ON [dbo].[bit_camaras]
(
	[codigo_bitacora] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, SORT_IN_TEMPDB = OFF, DROP_EXISTING = OFF, ONLINE = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
GO
/****** Objeto: Index [IX_bitacora_camaras_id_camara] Fecha de script: 26/7/2026 22:40:59 ******/
CREATE NONCLUSTERED INDEX [IX_bitacora_camaras_id_camara] ON [dbo].[bit_camaras]
(
	[id_camara] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, SORT_IN_TEMPDB = OFF, DROP_EXISTING = OFF, ONLINE = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
GO
/****** Objeto: Index [IX_bitacora_camaras_id_motivo_camara] Fecha de script: 26/7/2026 22:40:59 ******/
CREATE NONCLUSTERED INDEX [IX_bitacora_camaras_id_motivo_camara] ON [dbo].[bit_camaras]
(
	[id_motivo_camara] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, SORT_IN_TEMPDB = OFF, DROP_EXISTING = OFF, ONLINE = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
GO
/****** Objeto: Index [IX_bitacora_camaras_nivel_alerta] Fecha de script: 26/7/2026 22:40:59 ******/
CREATE NONCLUSTERED INDEX [IX_bitacora_camaras_nivel_alerta] ON [dbo].[bit_camaras]
(
	[nivel_alerta] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, SORT_IN_TEMPDB = OFF, DROP_EXISTING = OFF, ONLINE = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
GO
/****** Objeto: Index [IX_bitacora_camaras_sec_bitacora] Fecha de script: 26/7/2026 22:40:59 ******/
CREATE NONCLUSTERED INDEX [IX_bitacora_camaras_sec_bitacora] ON [dbo].[bit_camaras]
(
	[sec_bitacora] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, SORT_IN_TEMPDB = OFF, DROP_EXISTING = OFF, ONLINE = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
GO
/****** Objeto: Index [IX_bitacora_camaras_tipo_registro] Fecha de script: 26/7/2026 22:40:59 ******/
CREATE NONCLUSTERED INDEX [IX_bitacora_camaras_tipo_registro] ON [dbo].[bit_camaras]
(
	[tipo_registro] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, SORT_IN_TEMPDB = OFF, DROP_EXISTING = OFF, ONLINE = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
GO
SET ANSI_PADDING ON
GO
/****** Objeto: Index [UQ_departamentos_nom_departa] Fecha de script: 26/7/2026 22:40:59 ******/
CREATE UNIQUE NONCLUSTERED INDEX [UQ_departamentos_nom_departa] ON [dbo].[bit_departamentos]
(
	[nombre] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, SORT_IN_TEMPDB = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
GO
SET ANSI_PADDING ON
GO
/****** Objeto: Index [IX_funcionarios_cedula] Fecha de script: 26/7/2026 22:40:59 ******/
CREATE NONCLUSTERED INDEX [IX_funcionarios_cedula] ON [dbo].[bit_funcionarios]
(
	[cedula] ASC
)
WHERE ([cedula] IS NOT NULL)
WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, SORT_IN_TEMPDB = OFF, DROP_EXISTING = OFF, ONLINE = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
GO
SET ANSI_PADDING ON
GO
/****** Objeto: Index [IX_inv_camaras_codigo] Fecha de script: 26/7/2026 22:40:59 ******/
CREATE NONCLUSTERED INDEX [IX_inv_camaras_codigo] ON [dbo].[bit_inv_camaras]
(
	[codigo] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, SORT_IN_TEMPDB = OFF, DROP_EXISTING = OFF, ONLINE = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
GO
SET ANSI_PADDING ON
GO
/****** Objeto: Index [IX_inv_camaras_codigo_secuencial] Fecha de script: 26/7/2026 22:40:59 ******/
CREATE NONCLUSTERED INDEX [IX_inv_camaras_codigo_secuencial] ON [dbo].[bit_inv_camaras]
(
	[codigo_secuencial] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, SORT_IN_TEMPDB = OFF, DROP_EXISTING = OFF, ONLINE = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
GO
SET ANSI_PADDING ON
GO
/****** Objeto: Index [IX_inv_camaras_detalle] Fecha de script: 26/7/2026 22:40:59 ******/
CREATE NONCLUSTERED INDEX [IX_inv_camaras_detalle] ON [dbo].[bit_inv_camaras]
(
	[detalle] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, SORT_IN_TEMPDB = OFF, DROP_EXISTING = OFF, ONLINE = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
GO
SET ANSI_PADDING ON
GO
/****** Objeto: Index [IX_inv_camaras_ip] Fecha de script: 26/7/2026 22:40:59 ******/
CREATE NONCLUSTERED INDEX [IX_inv_camaras_ip] ON [dbo].[bit_inv_camaras]
(
	[ip] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, SORT_IN_TEMPDB = OFF, DROP_EXISTING = OFF, ONLINE = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
GO
/****** Objeto: Index [IX_inv_camaras_sec_camara] Fecha de script: 26/7/2026 22:40:59 ******/
CREATE NONCLUSTERED INDEX [IX_inv_camaras_sec_camara] ON [dbo].[bit_inv_camaras]
(
	[sec_camara] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, SORT_IN_TEMPDB = OFF, DROP_EXISTING = OFF, ONLINE = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
GO
SET ANSI_PADDING ON
GO
/****** Objeto: Index [IX_bit_motivos_camaras_codigo] Fecha de script: 26/7/2026 22:40:59 ******/
CREATE NONCLUSTERED INDEX [IX_bit_motivos_camaras_codigo] ON [dbo].[bit_motivos_camaras]
(
	[codigo_motivo] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, SORT_IN_TEMPDB = OFF, DROP_EXISTING = OFF, ONLINE = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
GO
SET ANSI_PADDING ON
GO
/****** Objeto: Index [IX_bit_motivos_camaras_descripcion] Fecha de script: 26/7/2026 22:40:59 ******/
CREATE NONCLUSTERED INDEX [IX_bit_motivos_camaras_descripcion] ON [dbo].[bit_motivos_camaras]
(
	[descripcion] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, SORT_IN_TEMPDB = OFF, DROP_EXISTING = OFF, ONLINE = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
GO
/****** Objeto: Index [IX_novedades_reporte_fecha] Fecha de script: 26/7/2026 22:40:59 ******/
CREATE NONCLUSTERED INDEX [IX_novedades_reporte_fecha] ON [dbo].[bit_novedades]
(
	[id_reporte] ASC,
	[fecha] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, SORT_IN_TEMPDB = OFF, DROP_EXISTING = OFF, ONLINE = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
GO
SET ANSI_PADDING ON
GO
/****** Objeto: Index [UQ_personas_nidentificacion_real] Fecha de script: 26/7/2026 22:40:59 ******/
CREATE UNIQUE NONCLUSTERED INDEX [UQ_personas_nidentificacion_real] ON [dbo].[bit_personas]
(
	[nidentificacion] ASC
)
WHERE ([nidentificacion]<>N'9999999999')
WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, SORT_IN_TEMPDB = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
GO
SET ANSI_PADDING ON
GO
/****** Objeto: Index [IX_rondas_cabecera_usuario_fecha] Fecha de script: 26/7/2026 22:40:59 ******/
CREATE NONCLUSTERED INDEX [IX_rondas_cabecera_usuario_fecha] ON [dbo].[bit_rondas_cabecera]
(
	[id_usuario] ASC,
	[fecha] ASC,
	[turno] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, SORT_IN_TEMPDB = OFF, DROP_EXISTING = OFF, ONLINE = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
GO
/****** Objeto: Index [IX_rondas_detalles_ronda] Fecha de script: 26/7/2026 22:40:59 ******/
CREATE NONCLUSTERED INDEX [IX_rondas_detalles_ronda] ON [dbo].[bit_rondas_detalles]
(
	[id_ronda] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, SORT_IN_TEMPDB = OFF, DROP_EXISTING = OFF, ONLINE = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
GO
SET ANSI_PADDING ON
GO
/****** Objeto: Index [UQ_usuarios_apm_cedula_departamento] Fecha de script: 26/7/2026 22:40:59 ******/
CREATE UNIQUE NONCLUSTERED INDEX [UQ_usuarios_apm_cedula_departamento] ON [dbo].[bit_usuarios_apm]
(
	[cedula] ASC,
	[id_departamento] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, SORT_IN_TEMPDB = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
GO
ALTER TABLE [dbo].[bit_acc_secuenciales] ADD  CONSTRAINT [DF_acc_secuenciales_ultimo]  DEFAULT ((0)) FOR [ultimo_numero]
GO
ALTER TABLE [dbo].[bit_acc_secuenciales] ADD  CONSTRAINT [DF_acc_secuenciales_longitud]  DEFAULT ((6)) FOR [longitud]
GO
ALTER TABLE [dbo].[bit_acc_secuenciales] ADD  CONSTRAINT [DF_acc_secuenciales_estado]  DEFAULT ((1)) FOR [estado]
GO
ALTER TABLE [dbo].[bit_acc_secuenciales] ADD  CONSTRAINT [DF_acc_secuenciales_fecha_creacion]  DEFAULT (getdate()) FOR [fecha_creacion]
GO
ALTER TABLE [dbo].[bit_camaras] ADD  DEFAULT ((1)) FOR [estado]
GO
ALTER TABLE [dbo].[bit_camaras] ADD  DEFAULT (getdate()) FOR [fecha_creacion]
GO
ALTER TABLE [dbo].[bit_camaras] ADD  CONSTRAINT [DF_bitacora_camaras_nivel_alerta]  DEFAULT ((104)) FOR [nivel_alerta]
GO
ALTER TABLE [dbo].[bit_camaras] ADD  CONSTRAINT [DF_bitacora_camaras_tipo_registro]  DEFAULT ((102)) FOR [tipo_registro]
GO
ALTER TABLE [dbo].[bit_departamentos] ADD  CONSTRAINT [DF_departamentos_estado]  DEFAULT ((1)) FOR [estado]
GO
ALTER TABLE [dbo].[bit_destinos] ADD  CONSTRAINT [DF_destinos_estado]  DEFAULT ((1)) FOR [estado]
GO
ALTER TABLE [dbo].[bit_empresas] ADD  CONSTRAINT [DF_empresas_estado]  DEFAULT ((1)) FOR [estado]
GO
ALTER TABLE [dbo].[bit_estados] ADD  CONSTRAINT [DF_bit_estados_estado]  DEFAULT ((1)) FOR [estado]
GO
ALTER TABLE [dbo].[bit_funcionarios] ADD  CONSTRAINT [DF_funcionarios_estado]  DEFAULT ((1)) FOR [estado]
GO
ALTER TABLE [dbo].[bit_inv_camaras] ADD  DEFAULT ((1)) FOR [estado]
GO
ALTER TABLE [dbo].[bit_inv_camaras] ADD  DEFAULT (getdate()) FOR [fecha_creacion]
GO
ALTER TABLE [dbo].[bit_motivos] ADD  CONSTRAINT [DF_motivos_estado]  DEFAULT ((1)) FOR [estado]
GO
ALTER TABLE [dbo].[bit_motivos_camaras] ADD  CONSTRAINT [DF_bit_motivos_camaras_nivel]  DEFAULT ('Medio') FOR [nivel_sugerido]
GO
ALTER TABLE [dbo].[bit_motivos_camaras] ADD  CONSTRAINT [DF_bit_motivos_camaras_obs]  DEFAULT ((1)) FOR [requiere_observacion]
GO
ALTER TABLE [dbo].[bit_motivos_camaras] ADD  CONSTRAINT [DF_bit_motivos_camaras_estado]  DEFAULT ((1)) FOR [estado]
GO
ALTER TABLE [dbo].[bit_motivos_camaras] ADD  CONSTRAINT [DF_bit_motivos_camaras_fecha_creacion]  DEFAULT (getdate()) FOR [fecha_creacion]
GO
ALTER TABLE [dbo].[bit_movimientos] ADD  CONSTRAINT [DF_movimientos_fecha_hora]  DEFAULT (getdate()) FOR [fecha_hora]
GO
ALTER TABLE [dbo].[bit_niveles_alerta] ADD  CONSTRAINT [DF_niveles_alerta_estado]  DEFAULT ((1)) FOR [estado]
GO
ALTER TABLE [dbo].[bit_niveles_incidente] ADD  CONSTRAINT [DF_niveles_incidente_estado]  DEFAULT ((1)) FOR [estado]
GO
ALTER TABLE [dbo].[bit_novedades] ADD  CONSTRAINT [DF_novedades_estado]  DEFAULT (N'Registrada') FOR [estado]
GO
ALTER TABLE [dbo].[bit_personas] ADD  CONSTRAINT [DF_personas_tidentif]  DEFAULT (N'Cédula') FOR [tidentif]
GO
ALTER TABLE [dbo].[bit_personas] ADD  CONSTRAINT [DF_personas_estado]  DEFAULT ((1)) FOR [estado]
GO
ALTER TABLE [dbo].[bit_reporte_supervisor] ADD  CONSTRAINT [DF_reporte_supervisor_creado]  DEFAULT (sysutcdatetime()) FOR [creado_en]
GO
ALTER TABLE [dbo].[bit_rondas_cabecera] ADD  CONSTRAINT [DF_rondas_cabecera_estado]  DEFAULT ((1)) FOR [estado]
GO
ALTER TABLE [dbo].[bit_rondas_detalles] ADD  CONSTRAINT [DF_rondas_detalles_hora]  DEFAULT (getdate()) FOR [hora_registro]
GO
ALTER TABLE [dbo].[bit_totales_actividades] ADD  CONSTRAINT [DF_totales_actividades_total]  DEFAULT ((0)) FOR [total_detalles]
GO
ALTER TABLE [dbo].[bit_totales_visitas] ADD  CONSTRAINT [DF_totales_visitas_total_visitas]  DEFAULT ((0)) FOR [total_visitas]
GO
ALTER TABLE [dbo].[bit_totales_visitas] ADD  CONSTRAINT [DF_totales_visitas_total_activas]  DEFAULT ((0)) FOR [total_activas]
GO
ALTER TABLE [dbo].[bit_totales_visitas] ADD  CONSTRAINT [DF_totales_visitas_total_proveedores]  DEFAULT ((0)) FOR [total_proveedores]
GO
ALTER TABLE [dbo].[bit_usuarios_apm] ADD  CONSTRAINT [DF_usuarios_apm_estado]  DEFAULT ((1)) FOR [estado]
GO
ALTER TABLE [dbo].[bit_usuarios_apm] ADD  CONSTRAINT [DF_usuarios_apm_creado_en]  DEFAULT (sysutcdatetime()) FOR [creado_en]
GO
ALTER TABLE [dbo].[bit_visitas] ADD  CONSTRAINT [DF_visitas_detalle_motivo]  DEFAULT (N'') FOR [detalle_motivo]
GO
ALTER TABLE [dbo].[bit_visitas] ADD  CONSTRAINT [DF_visitas_id_nivel]  DEFAULT ((1)) FOR [id_nivel_incidente]
GO
ALTER TABLE [dbo].[bit_visitas] ADD  CONSTRAINT [DF_visitas_fecha]  DEFAULT (CONVERT([date],getdate())) FOR [fecha_visita]
GO
ALTER TABLE [dbo].[bit_visitas] ADD  CONSTRAINT [DF_visitas_hora_entrada]  DEFAULT (CONVERT([time],getdate())) FOR [hora_entrada]
GO
ALTER TABLE [dbo].[bit_visitas] ADD  CONSTRAINT [DF_visitas_estado]  DEFAULT ((1)) FOR [estado]
GO
ALTER TABLE [dbo].[estados] ADD  CONSTRAINT [DF_estados_estado]  DEFAULT ((1)) FOR [estado]
GO
ALTER TABLE [dbo].[bit_camaras]  WITH CHECK ADD  CONSTRAINT [FK_bitacora_camaras_bit_motivos_camaras] FOREIGN KEY([id_motivo_camara])
REFERENCES [dbo].[bit_motivos_camaras] ([id_motivo_camara])
GO
ALTER TABLE [dbo].[bit_camaras] CHECK CONSTRAINT [FK_bitacora_camaras_bit_motivos_camaras]
GO
ALTER TABLE [dbo].[bit_camaras]  WITH CHECK ADD  CONSTRAINT [FK_bitacora_camaras_estado_camara_bit_estados] FOREIGN KEY([estado_camara])
REFERENCES [dbo].[bit_estados] ([idestado])
GO
ALTER TABLE [dbo].[bit_camaras] CHECK CONSTRAINT [FK_bitacora_camaras_estado_camara_bit_estados]
GO
ALTER TABLE [dbo].[bit_camaras]  WITH CHECK ADD  CONSTRAINT [FK_bitacora_camaras_inv_camaras] FOREIGN KEY([id_camara])
REFERENCES [dbo].[bit_inv_camaras] ([id_camara])
GO
ALTER TABLE [dbo].[bit_camaras] CHECK CONSTRAINT [FK_bitacora_camaras_inv_camaras]
GO
ALTER TABLE [dbo].[bit_camaras]  WITH CHECK ADD  CONSTRAINT [FK_bitacora_camaras_nivel_alerta_bit_estados] FOREIGN KEY([nivel_alerta])
REFERENCES [dbo].[bit_estados] ([idestado])
GO
ALTER TABLE [dbo].[bit_camaras] CHECK CONSTRAINT [FK_bitacora_camaras_nivel_alerta_bit_estados]
GO
ALTER TABLE [dbo].[bit_camaras]  WITH CHECK ADD  CONSTRAINT [FK_bitacora_camaras_tipo_registro_bit_estados] FOREIGN KEY([tipo_registro])
REFERENCES [dbo].[bit_estados] ([idestado])
GO
ALTER TABLE [dbo].[bit_camaras] CHECK CONSTRAINT [FK_bitacora_camaras_tipo_registro_bit_estados]
GO
ALTER TABLE [dbo].[bit_movimientos]  WITH CHECK ADD  CONSTRAINT [FK_movimientos_usuarios_apm] FOREIGN KEY([id_usuario])
REFERENCES [dbo].[bit_usuarios_apm] ([id_usuario])
GO
ALTER TABLE [dbo].[bit_movimientos] CHECK CONSTRAINT [FK_movimientos_usuarios_apm]
GO
ALTER TABLE [dbo].[bit_novedades]  WITH CHECK ADD  CONSTRAINT [FK_novedades_reporte] FOREIGN KEY([id_reporte])
REFERENCES [dbo].[bit_reporte_supervisor] ([id_reporte])
ON DELETE CASCADE
GO
ALTER TABLE [dbo].[bit_novedades] CHECK CONSTRAINT [FK_novedades_reporte]
GO
ALTER TABLE [dbo].[bit_rondas_cabecera]  WITH CHECK ADD  CONSTRAINT [FK_rondas_cabecera_usuario] FOREIGN KEY([id_usuario])
REFERENCES [dbo].[bit_usuarios_apm] ([id_usuario])
GO
ALTER TABLE [dbo].[bit_rondas_cabecera] CHECK CONSTRAINT [FK_rondas_cabecera_usuario]
GO
ALTER TABLE [dbo].[bit_rondas_detalles]  WITH CHECK ADD  CONSTRAINT [FK_rondas_detalles_alerta] FOREIGN KEY([id_alerta])
REFERENCES [dbo].[bit_niveles_alerta] ([id_alerta])
GO
ALTER TABLE [dbo].[bit_rondas_detalles] CHECK CONSTRAINT [FK_rondas_detalles_alerta]
GO
ALTER TABLE [dbo].[bit_rondas_detalles]  WITH CHECK ADD  CONSTRAINT [FK_rondas_detalles_ronda] FOREIGN KEY([id_ronda])
REFERENCES [dbo].[bit_rondas_cabecera] ([id_ronda])
ON DELETE CASCADE
GO
ALTER TABLE [dbo].[bit_rondas_detalles] CHECK CONSTRAINT [FK_rondas_detalles_ronda]
GO
ALTER TABLE [dbo].[bit_usuarios_apm]  WITH CHECK ADD  CONSTRAINT [FK_usuarios_apm_departamento] FOREIGN KEY([id_departamento])
REFERENCES [dbo].[bit_departamentos] ([iddepartamento])
GO
ALTER TABLE [dbo].[bit_usuarios_apm] CHECK CONSTRAINT [FK_usuarios_apm_departamento]
GO
ALTER TABLE [dbo].[bit_visitas]  WITH CHECK ADD  CONSTRAINT [FK_visitas_destinos] FOREIGN KEY([id_destino])
REFERENCES [dbo].[bit_destinos] ([id_destino])
GO
ALTER TABLE [dbo].[bit_visitas] CHECK CONSTRAINT [FK_visitas_destinos]
GO
ALTER TABLE [dbo].[bit_visitas]  WITH CHECK ADD  CONSTRAINT [FK_visitas_empresas] FOREIGN KEY([id_empresa])
REFERENCES [dbo].[bit_empresas] ([id_empresa])
GO
ALTER TABLE [dbo].[bit_visitas] CHECK CONSTRAINT [FK_visitas_empresas]
GO
ALTER TABLE [dbo].[bit_visitas]  WITH CHECK ADD  CONSTRAINT [FK_visitas_funcionarios] FOREIGN KEY([id_funcionario])
REFERENCES [dbo].[bit_funcionarios] ([id_funcionario])
GO
ALTER TABLE [dbo].[bit_visitas] CHECK CONSTRAINT [FK_visitas_funcionarios]
GO
ALTER TABLE [dbo].[bit_visitas]  WITH CHECK ADD  CONSTRAINT [FK_visitas_motivos] FOREIGN KEY([id_motivo])
REFERENCES [dbo].[bit_motivos] ([id_motivo])
GO
ALTER TABLE [dbo].[bit_visitas] CHECK CONSTRAINT [FK_visitas_motivos]
GO
ALTER TABLE [dbo].[bit_visitas]  WITH CHECK ADD  CONSTRAINT [FK_visitas_niveles] FOREIGN KEY([id_nivel_incidente])
REFERENCES [dbo].[bit_niveles_incidente] ([id_incidentes])
GO
ALTER TABLE [dbo].[bit_visitas] CHECK CONSTRAINT [FK_visitas_niveles]
GO
ALTER TABLE [dbo].[bit_visitas]  WITH CHECK ADD  CONSTRAINT [FK_visitas_personas] FOREIGN KEY([id_persona])
REFERENCES [dbo].[bit_personas] ([id_persona])
GO
ALTER TABLE [dbo].[bit_visitas] CHECK CONSTRAINT [FK_visitas_personas]
GO
ALTER TABLE [dbo].[bit_camaras]  WITH CHECK ADD  CONSTRAINT [CK_bitacora_camaras_estado_camara_num] CHECK  (([estado_camara] IS NULL OR ([estado_camara]=(101) OR [estado_camara]=(100))))
GO
ALTER TABLE [dbo].[bit_camaras] CHECK CONSTRAINT [CK_bitacora_camaras_estado_camara_num]
GO
ALTER TABLE [dbo].[bit_camaras]  WITH CHECK ADD  CONSTRAINT [CK_bitacora_camaras_nivel_alerta] CHECK  (([nivel_alerta]=(106) OR [nivel_alerta]=(105) OR [nivel_alerta]=(104)))
GO
ALTER TABLE [dbo].[bit_camaras] CHECK CONSTRAINT [CK_bitacora_camaras_nivel_alerta]
GO
ALTER TABLE [dbo].[bit_camaras]  WITH CHECK ADD  CONSTRAINT [CK_bitacora_camaras_rol_responsable] CHECK  (([rol_responsable] IS NULL OR ([rol_responsable]='Supervisor' OR [rol_responsable]='Inspector' OR [rol_responsable]='Consolista')))
GO
ALTER TABLE [dbo].[bit_camaras] CHECK CONSTRAINT [CK_bitacora_camaras_rol_responsable]
GO
ALTER TABLE [dbo].[bit_camaras]  WITH CHECK ADD  CONSTRAINT [CK_bitacora_camaras_tipo_registro] CHECK  (([tipo_registro]=(103) OR [tipo_registro]=(102)))
GO
ALTER TABLE [dbo].[bit_camaras] CHECK CONSTRAINT [CK_bitacora_camaras_tipo_registro]
GO
ALTER TABLE [dbo].[bit_estados]  WITH CHECK ADD  CONSTRAINT [CK_bit_estados_estado] CHECK  (([estado]=(1) OR [estado]=(0)))
GO
ALTER TABLE [dbo].[bit_estados] CHECK CONSTRAINT [CK_bit_estados_estado]
GO
ALTER TABLE [dbo].[bit_motivos_camaras]  WITH CHECK ADD  CONSTRAINT [CK_bit_motivos_camaras_nivel] CHECK  (([nivel_sugerido]='Crítico' OR [nivel_sugerido]='Medio' OR [nivel_sugerido]='Normal'))
GO
ALTER TABLE [dbo].[bit_motivos_camaras] CHECK CONSTRAINT [CK_bit_motivos_camaras_nivel]
GO
ALTER TABLE [dbo].[bit_niveles_incidente]  WITH CHECK ADD  CONSTRAINT [CK_niveles_incidente_nivel] CHECK  (([nivel]>=(1) AND [nivel]<=(3)))
GO
ALTER TABLE [dbo].[bit_niveles_incidente] CHECK CONSTRAINT [CK_niveles_incidente_nivel]
GO
ALTER TABLE [dbo].[estados]  WITH CHECK ADD  CONSTRAINT [CK_estados_estado] CHECK  (([estado]=(1) OR [estado]=(0)))
GO
ALTER TABLE [dbo].[estados] CHECK CONSTRAINT [CK_estados_estado]
GO
/****** Objeto: StoredProcedure [dbo].[apm_aplicar_passwords_demo] Fecha de script: 26/7/2026 22:40:59 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE PROCEDURE [dbo].[apm_aplicar_passwords_demo]
AS
BEGIN
    SET NOCOUNT ON;
    UPDATE u
    SET u.password_hash = CONVERT(VARCHAR(64), HASHBYTES('SHA2_256', N'Admin@123'), 2)
    FROM dbo.usuarios_apm u
    WHERE u.cedula = N'1301234567';

    UPDATE u
    SET u.password_hash = CONVERT(VARCHAR(64), HASHBYTES('SHA2_256', N'Talento@123'), 2)
    FROM dbo.usuarios_apm u
    WHERE u.cedula = N'1307654321';

    UPDATE u
    SET u.password_hash = CONVERT(VARCHAR(64), HASHBYTES('SHA2_256', N'Garita@123'), 2)
    FROM dbo.usuarios_apm u
    WHERE u.cedula = N'1302223344';
END

GO
USE [master]
GO
ALTER DATABASE [PortuariaDemo] SET  READ_WRITE 
GO
