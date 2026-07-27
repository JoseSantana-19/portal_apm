USE [master]
GO
/****** Objeto: Database [inventario] Fecha de script: 26/7/2026 22:39:59 ******/
-- Si PORTAL_APM_COMPLETO.sql ya corrió antes y creó esta base vacía (stub
-- para resolver dependencias cross-DB), no reintenta el CREATE DATABASE —
-- sigue directo a poblarla con las tablas reales de abajo.
IF DB_ID(N'inventario') IS NULL
BEGIN
    EXEC(N'
    CREATE DATABASE [inventario]
     CONTAINMENT = NONE
     ON  PRIMARY
    ( NAME = N''inventario'', FILENAME = N''C:\Program Files\Microsoft SQL Server\MSSQL16.VICTUS\MSSQL\DATA\inventario.mdf'' , SIZE = 8192KB , MAXSIZE = UNLIMITED, FILEGROWTH = 65536KB )
     LOG ON
    ( NAME = N''inventario_log'', FILENAME = N''C:\Program Files\Microsoft SQL Server\MSSQL16.VICTUS\MSSQL\DATA\inventario_log.ldf'' , SIZE = 8192KB , MAXSIZE = 2048GB , FILEGROWTH = 65536KB )
    ');
END
GO
ALTER DATABASE [inventario] SET COMPATIBILITY_LEVEL = 120
GO
IF (1 = FULLTEXTSERVICEPROPERTY('IsFullTextInstalled'))
begin
EXEC [inventario].[dbo].[sp_fulltext_database] @action = 'enable'
end
GO
ALTER DATABASE [inventario] SET ANSI_NULL_DEFAULT OFF 
GO
ALTER DATABASE [inventario] SET ANSI_NULLS OFF 
GO
ALTER DATABASE [inventario] SET ANSI_PADDING OFF 
GO
ALTER DATABASE [inventario] SET ANSI_WARNINGS OFF 
GO
ALTER DATABASE [inventario] SET ARITHABORT OFF 
GO
ALTER DATABASE [inventario] SET AUTO_CLOSE ON 
GO
ALTER DATABASE [inventario] SET AUTO_SHRINK OFF 
GO
ALTER DATABASE [inventario] SET AUTO_UPDATE_STATISTICS ON 
GO
ALTER DATABASE [inventario] SET CURSOR_CLOSE_ON_COMMIT OFF 
GO
ALTER DATABASE [inventario] SET CURSOR_DEFAULT  GLOBAL 
GO
ALTER DATABASE [inventario] SET CONCAT_NULL_YIELDS_NULL OFF 
GO
ALTER DATABASE [inventario] SET NUMERIC_ROUNDABORT OFF 
GO
ALTER DATABASE [inventario] SET QUOTED_IDENTIFIER OFF 
GO
ALTER DATABASE [inventario] SET RECURSIVE_TRIGGERS OFF 
GO
ALTER DATABASE [inventario] SET  ENABLE_BROKER 
GO
ALTER DATABASE [inventario] SET AUTO_UPDATE_STATISTICS_ASYNC OFF 
GO
ALTER DATABASE [inventario] SET DATE_CORRELATION_OPTIMIZATION OFF 
GO
ALTER DATABASE [inventario] SET TRUSTWORTHY OFF 
GO
ALTER DATABASE [inventario] SET ALLOW_SNAPSHOT_ISOLATION OFF 
GO
ALTER DATABASE [inventario] SET PARAMETERIZATION SIMPLE 
GO
ALTER DATABASE [inventario] SET READ_COMMITTED_SNAPSHOT OFF 
GO
ALTER DATABASE [inventario] SET HONOR_BROKER_PRIORITY OFF 
GO
ALTER DATABASE [inventario] SET RECOVERY SIMPLE 
GO
ALTER DATABASE [inventario] SET  MULTI_USER 
GO
ALTER DATABASE [inventario] SET PAGE_VERIFY CHECKSUM  
GO
ALTER DATABASE [inventario] SET DB_CHAINING OFF 
GO
ALTER DATABASE [inventario] SET FILESTREAM( NON_TRANSACTED_ACCESS = OFF ) 
GO
ALTER DATABASE [inventario] SET TARGET_RECOVERY_TIME = 60 SECONDS 
GO
ALTER DATABASE [inventario] SET DELAYED_DURABILITY = DISABLED
GO
USE [inventario]
GO
/****** Objeto: Table [dbo].[inv_bitacora] Fecha de script: 26/7/2026 22:40:00 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[inv_bitacora](
	[id] [int] IDENTITY(1,1) NOT NULL,
	[secuencial] [nvarchar](50) NOT NULL,
	[tipo] [nvarchar](50) NOT NULL,
	[modulo] [nvarchar](50) NOT NULL,
	[descripcion] [nvarchar](max) NOT NULL,
	[fecha] [datetime] NULL,
PRIMARY KEY CLUSTERED 
(
	[id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY],
UNIQUE NONCLUSTERED 
(
	[secuencial] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY] TEXTIMAGE_ON [PRIMARY]
GO
/****** Objeto: Table [dbo].[inv_bod_egresos] Fecha de script: 26/7/2026 22:40:00 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[inv_bod_egresos](
	[id] [int] IDENTITY(1,1) NOT NULL,
	[secuencial] [nvarchar](50) NOT NULL,
	[area_id] [int] NOT NULL,
	[responsable_id] [int] NOT NULL,
	[fecha] [date] NOT NULL,
	[motivo] [nvarchar](max) NOT NULL,
	[observaciones] [nvarchar](max) NULL,
	[creado_por] [nvarchar](255) NOT NULL,
PRIMARY KEY CLUSTERED 
(
	[id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY],
UNIQUE NONCLUSTERED 
(
	[secuencial] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY] TEXTIMAGE_ON [PRIMARY]
GO
/****** Objeto: Table [dbo].[inv_bod_egresos_detalles] Fecha de script: 26/7/2026 22:40:00 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[inv_bod_egresos_detalles](
	[id] [int] IDENTITY(1,1) NOT NULL,
	[egreso_id] [int] NOT NULL,
	[item_id] [int] NOT NULL,
	[cantidad] [int] NOT NULL,
	[grupo_id_historico] [int] NULL,
PRIMARY KEY CLUSTERED 
(
	[id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY]
GO
/****** Objeto: Table [dbo].[inv_bod_ingresos] Fecha de script: 26/7/2026 22:40:00 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[inv_bod_ingresos](
	[id] [int] IDENTITY(1,1) NOT NULL,
	[secuencial] [nvarchar](50) NOT NULL,
	[proveedor] [nvarchar](255) NOT NULL,
	[fecha] [date] NOT NULL,
	[observaciones] [nvarchar](max) NULL,
	[responsable_id] [int] NOT NULL,
	[creado_por] [nvarchar](255) NOT NULL,
PRIMARY KEY CLUSTERED 
(
	[id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY],
UNIQUE NONCLUSTERED 
(
	[secuencial] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY] TEXTIMAGE_ON [PRIMARY]
GO
/****** Objeto: Table [dbo].[inv_bod_ingresos_detalles] Fecha de script: 26/7/2026 22:40:00 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[inv_bod_ingresos_detalles](
	[id] [int] IDENTITY(1,1) NOT NULL,
	[ingreso_id] [int] NOT NULL,
	[item_id] [int] NOT NULL,
	[cantidad] [int] NOT NULL,
	[valor_unitario] [float] NOT NULL,
	[grupo_id_historico] [int] NULL,
	[porcentaje_iva] [float] NULL,
	[valor_iva] [float] NULL,
PRIMARY KEY CLUSTERED 
(
	[id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY]
GO
/****** Objeto: Table [dbo].[inv_categorias] Fecha de script: 26/7/2026 22:40:00 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[inv_categorias](
	[id] [int] IDENTITY(1,1) NOT NULL,
	[nombre] [nvarchar](255) NOT NULL,
	[codigo] [nvarchar](50) NULL,
	[extra] [nvarchar](max) NULL,
PRIMARY KEY CLUSTERED 
(
	[id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY],
UNIQUE NONCLUSTERED 
(
	[nombre] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY] TEXTIMAGE_ON [PRIMARY]
GO
/****** Objeto: Table [dbo].[inv_centros_consumo] Fecha de script: 26/7/2026 22:40:00 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[inv_centros_consumo](
	[id] [int] IDENTITY(1,1) NOT NULL,
	[grupo_id] [int] NOT NULL,
	[codigo] [nvarchar](50) NOT NULL,
	[nombre] [nvarchar](255) NOT NULL,
	[funcionario] [nvarchar](255) NOT NULL,
PRIMARY KEY CLUSTERED 
(
	[id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY],
UNIQUE NONCLUSTERED 
(
	[codigo] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY]
GO
/****** Objeto: Table [dbo].[inv_estados] Fecha de script: 26/7/2026 22:40:00 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[inv_estados](
	[idestado] [int] NOT NULL,
	[descripcion] [nvarchar](255) NOT NULL,
	[detalle] [nvarchar](max) NULL,
	[estado] [int] NOT NULL,
PRIMARY KEY CLUSTERED 
(
	[idestado] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY],
UNIQUE NONCLUSTERED 
(
	[descripcion] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY] TEXTIMAGE_ON [PRIMARY]
GO
/****** Objeto: Table [dbo].[inv_grupo_centros_consumo] Fecha de script: 26/7/2026 22:40:00 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[inv_grupo_centros_consumo](
	[id] [int] IDENTITY(1,1) NOT NULL,
	[codigo] [nvarchar](50) NOT NULL,
	[nombre] [nvarchar](255) NOT NULL,
	[representante] [nvarchar](255) NULL,
PRIMARY KEY CLUSTERED 
(
	[id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY],
UNIQUE NONCLUSTERED 
(
	[codigo] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY],
UNIQUE NONCLUSTERED 
(
	[nombre] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY]
GO
/****** Objeto: Table [dbo].[inv_inventario] Fecha de script: 26/7/2026 22:40:00 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[inv_inventario](
	[id] [int] IDENTITY(1,1) NOT NULL,
	[secuencial] [nvarchar](50) NOT NULL,
	[nombre] [nvarchar](255) NOT NULL,
	[marca] [nvarchar](255) NOT NULL,
	[categoria_id] [int] NOT NULL,
	[zona_id] [int] NOT NULL,
	[estado_id] [int] NOT NULL,
	[responsable_id] [int] NULL,
	[valor] [float] NOT NULL,
	[fecha_registro] [date] NOT NULL,
	[observaciones] [nvarchar](max) NULL,
	[activo] [int] NOT NULL,
	[cantidad] [int] NOT NULL,
	[producto_id] [int] NULL,
PRIMARY KEY CLUSTERED 
(
	[id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY],
UNIQUE NONCLUSTERED 
(
	[secuencial] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY] TEXTIMAGE_ON [PRIMARY]
GO
/****** Objeto: Table [dbo].[inv_lineas] Fecha de script: 26/7/2026 22:40:00 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[inv_lineas](
	[id] [int] IDENTITY(1,1) NOT NULL,
	[nombre] [nvarchar](255) NOT NULL,
	[extra] [nvarchar](max) NULL,
PRIMARY KEY CLUSTERED 
(
	[id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY],
UNIQUE NONCLUSTERED 
(
	[nombre] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY] TEXTIMAGE_ON [PRIMARY]
GO
/****** Objeto: Table [dbo].[inv_log_errores] Fecha de script: 26/7/2026 22:40:00 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[inv_log_errores](
	[id_error] [int] IDENTITY(1,1) NOT NULL,
	[id_usuario] [int] NULL,
	[modulo] [nvarchar](50) NOT NULL,
	[accion] [nvarchar](255) NULL,
	[tipo_error] [nvarchar](50) NOT NULL,
	[descripcion] [nvarchar](max) NOT NULL,
	[archivo_origen] [nvarchar](255) NULL,
	[linea_origen] [int] NULL,
	[nivel] [nvarchar](50) NOT NULL,
	[entorno] [nvarchar](50) NOT NULL,
	[ip_cliente] [nvarchar](50) NULL,
	[fecha_registro] [datetime] NOT NULL,
PRIMARY KEY CLUSTERED 
(
	[id_error] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY] TEXTIMAGE_ON [PRIMARY]
GO
/****** Objeto: Table [dbo].[inv_log_eventos] Fecha de script: 26/7/2026 22:40:00 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[inv_log_eventos](
	[id_evento] [int] IDENTITY(1,1) NOT NULL,
	[id_usuario] [int] NULL,
	[modulo] [nvarchar](50) NOT NULL,
	[accion] [nvarchar](255) NOT NULL,
	[descripcion] [nvarchar](max) NULL,
	[resultado] [nvarchar](50) NOT NULL,
	[ip_cliente] [nvarchar](50) NULL,
	[fecha_registro] [datetime] NOT NULL,
PRIMARY KEY CLUSTERED 
(
	[id_evento] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY] TEXTIMAGE_ON [PRIMARY]
GO
/****** Objeto: Table [dbo].[inv_marcas] Fecha de script: 26/7/2026 22:40:00 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[inv_marcas](
	[id] [int] IDENTITY(1,1) NOT NULL,
	[nombre] [nvarchar](255) NOT NULL,
	[extra] [nvarchar](max) NULL,
PRIMARY KEY CLUSTERED 
(
	[id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY],
UNIQUE NONCLUSTERED 
(
	[nombre] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY] TEXTIMAGE_ON [PRIMARY]
GO
/****** Objeto: Table [dbo].[inv_notificaciones] Fecha de script: 26/7/2026 22:40:00 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[inv_notificaciones](
	[id] [int] IDENTITY(1,1) NOT NULL,
	[tipo] [nvarchar](50) NOT NULL,
	[categoria] [nvarchar](50) NOT NULL,
	[titulo] [nvarchar](255) NOT NULL,
	[mensaje] [nvarchar](max) NOT NULL,
	[secuencial] [nvarchar](50) NULL,
	[visto] [int] NULL,
	[created_at] [datetime] NULL,
PRIMARY KEY CLUSTERED 
(
	[id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY] TEXTIMAGE_ON [PRIMARY]
GO
/****** Objeto: Table [dbo].[inv_parametros] Fecha de script: 26/7/2026 22:40:00 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[inv_parametros](
	[clave] [nvarchar](255) NOT NULL,
	[valor] [nvarchar](max) NOT NULL,
	[descripcion] [nvarchar](max) NULL,
PRIMARY KEY CLUSTERED 
(
	[clave] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY] TEXTIMAGE_ON [PRIMARY]
GO
/****** Objeto: Table [dbo].[inv_periodos] Fecha de script: 26/7/2026 22:40:00 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[inv_periodos](
	[id] [int] IDENTITY(1,1) NOT NULL,
	[nombre] [nvarchar](255) NOT NULL,
	[fecha_inicio] [date] NOT NULL,
	[fecha_fin] [date] NOT NULL,
	[estado] [nvarchar](50) NOT NULL,
PRIMARY KEY CLUSTERED 
(
	[id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY],
UNIQUE NONCLUSTERED 
(
	[nombre] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY]
GO
/****** Objeto: Table [dbo].[inv_permisos] Fecha de script: 26/7/2026 22:40:00 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[inv_permisos](
	[id] [int] IDENTITY(1,1) NOT NULL,
	[usuario_id] [int] NOT NULL,
	[route_key] [nvarchar](255) NOT NULL,
PRIMARY KEY CLUSTERED 
(
	[id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY],
UNIQUE NONCLUSTERED 
(
	[usuario_id] ASC,
	[route_key] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY]
GO
/****** Objeto: Table [dbo].[inv_productos] Fecha de script: 26/7/2026 22:40:00 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[inv_productos](
	[id] [int] IDENTITY(1,1) NOT NULL,
	[nombre] [nvarchar](255) NOT NULL,
	[grupo_id] [int] NOT NULL,
	[unidad_id] [int] NOT NULL,
	[aplica_iva] [int] NOT NULL,
	[codigo] [nvarchar](50) NULL,
	[descripcion] [nvarchar](max) NULL,
	[ubicacion] [nvarchar](max) NULL,
	[existencia_min] [float] NULL,
	[existencia_max] [float] NULL,
	[precio_promedio] [float] NULL,
	[existencia_actual] [float] NULL,
PRIMARY KEY CLUSTERED 
(
	[id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY],
UNIQUE NONCLUSTERED 
(
	[nombre] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY] TEXTIMAGE_ON [PRIMARY]
GO
/****** Objeto: Table [dbo].[inv_productos_historial] Fecha de script: 26/7/2026 22:40:00 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[inv_productos_historial](
	[id] [int] IDENTITY(1,1) NOT NULL,
	[producto_id] [int] NOT NULL,
	[grupo_id] [int] NOT NULL,
	[costo] [float] NOT NULL,
	[precio] [float] NOT NULL,
	[aplica_iva] [int] NOT NULL,
	[fecha_inicio] [date] NOT NULL,
	[fecha_fin] [date] NULL,
	[es_actual] [int] NOT NULL,
PRIMARY KEY CLUSTERED 
(
	[id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY]
GO
/****** Objeto: Table [dbo].[inv_proveedores] Fecha de script: 26/7/2026 22:40:00 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[inv_proveedores](
	[id] [int] IDENTITY(1,1) NOT NULL,
	[nombre] [nvarchar](255) NOT NULL,
	[ruc] [nvarchar](50) NOT NULL,
	[extra] [nvarchar](max) NULL,
PRIMARY KEY CLUSTERED 
(
	[id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY],
UNIQUE NONCLUSTERED 
(
	[nombre] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY],
UNIQUE NONCLUSTERED 
(
	[ruc] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY] TEXTIMAGE_ON [PRIMARY]
GO
/****** Objeto: Table [dbo].[inv_respaldo_historico] Fecha de script: 26/7/2026 22:40:00 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[inv_respaldo_historico](
	[id] [int] IDENTITY(1,1) NOT NULL,
	[periodo_id] [int] NOT NULL,
	[item_id] [int] NOT NULL,
	[secuencial] [nvarchar](50) NOT NULL,
	[nombre_historico] [nvarchar](255) NOT NULL,
	[marca_historica] [nvarchar](255) NOT NULL,
	[categoria_historica] [nvarchar](255) NOT NULL,
	[zona_historica] [nvarchar](255) NOT NULL,
	[estado_historico] [nvarchar](255) NOT NULL,
	[responsable_historico] [nvarchar](255) NULL,
	[area_talento_historica] [nvarchar](255) NULL,
	[valor_historico] [float] NOT NULL,
	[iva_aplicado] [float] NOT NULL,
	[fecha_registro] [date] NOT NULL,
	[fecha_corte] [date] NOT NULL,
PRIMARY KEY CLUSTERED 
(
	[id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY]
GO
/****** Objeto: Table [dbo].[inv_secuenciales] Fecha de script: 26/7/2026 22:40:00 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[inv_secuenciales](
	[modulo] [nvarchar](50) NOT NULL,
	[prefijo] [nvarchar](50) NOT NULL,
	[ultimo_numero] [int] NOT NULL,
PRIMARY KEY CLUSTERED 
(
	[modulo] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY]
GO
/****** Objeto: Table [dbo].[inv_talento_areas] Fecha de script: 26/7/2026 22:40:00 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[inv_talento_areas](
	[id] [int] IDENTITY(1,1) NOT NULL,
	[nombre] [nvarchar](255) NOT NULL,
PRIMARY KEY CLUSTERED 
(
	[id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY],
UNIQUE NONCLUSTERED 
(
	[nombre] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY]
GO
/****** Objeto: Table [dbo].[inv_talento_asignaciones] Fecha de script: 26/7/2026 22:40:00 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[inv_talento_asignaciones](
	[id] [int] IDENTITY(1,1) NOT NULL,
	[personal_id] [int] NOT NULL,
	[area_id] [int] NOT NULL,
	[fecha_inicio] [date] NOT NULL,
	[fecha_fin] [date] NULL,
PRIMARY KEY CLUSTERED 
(
	[id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY]
GO
/****** Objeto: Table [dbo].[inv_talento_personal] Fecha de script: 26/7/2026 22:40:00 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[inv_talento_personal](
	[id] [int] IDENTITY(1,1) NOT NULL,
	[nombre] [nvarchar](255) NOT NULL,
	[identificacion] [nvarchar](50) NOT NULL,
PRIMARY KEY CLUSTERED 
(
	[id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY],
UNIQUE NONCLUSTERED 
(
	[identificacion] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY]
GO
/****** Objeto: Table [dbo].[inv_tipos_iva] Fecha de script: 26/7/2026 22:40:00 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[inv_tipos_iva](
	[id] [int] IDENTITY(1,1) NOT NULL,
	[nombre] [nvarchar](255) NOT NULL,
	[tasa_iva] [float] NOT NULL,
PRIMARY KEY CLUSTERED 
(
	[id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY],
UNIQUE NONCLUSTERED 
(
	[nombre] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY],
UNIQUE NONCLUSTERED 
(
	[tasa_iva] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY]
GO
/****** Objeto: Table [dbo].[inv_unidades] Fecha de script: 26/7/2026 22:40:00 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[inv_unidades](
	[id] [int] IDENTITY(1,1) NOT NULL,
	[nombre] [nvarchar](255) NOT NULL,
	[extra] [nvarchar](max) NULL,
PRIMARY KEY CLUSTERED 
(
	[id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY],
UNIQUE NONCLUSTERED 
(
	[nombre] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY] TEXTIMAGE_ON [PRIMARY]
GO
/****** Objeto: Table [dbo].[inv_usuarios] Fecha de script: 26/7/2026 22:40:00 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[inv_usuarios](
	[id] [int] IDENTITY(1,1) NOT NULL,
	[secuencial] [nvarchar](50) NOT NULL,
	[nombre] [nvarchar](255) NOT NULL,
	[usuario] [nvarchar](255) NOT NULL,
	[contrasena] [nvarchar](max) NOT NULL,
	[rol] [nvarchar](50) NOT NULL,
	[activo] [int] NOT NULL,
PRIMARY KEY CLUSTERED 
(
	[id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY],
UNIQUE NONCLUSTERED 
(
	[usuario] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY],
UNIQUE NONCLUSTERED 
(
	[secuencial] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY] TEXTIMAGE_ON [PRIMARY]
GO
/****** Objeto: Table [dbo].[inv_valores_iva] Fecha de script: 26/7/2026 22:40:00 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[inv_valores_iva](
	[id] [int] IDENTITY(1,1) NOT NULL,
	[tasa_iva] [float] NOT NULL,
	[periodo_id] [int] NOT NULL,
PRIMARY KEY CLUSTERED 
(
	[id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY]
GO
/****** Objeto: Table [dbo].[inv_zonas] Fecha de script: 26/7/2026 22:40:00 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[inv_zonas](
	[id] [int] IDENTITY(1,1) NOT NULL,
	[nombre] [nvarchar](255) NOT NULL,
	[extra] [nvarchar](max) NULL,
PRIMARY KEY CLUSTERED 
(
	[id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY],
UNIQUE NONCLUSTERED 
(
	[nombre] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY] TEXTIMAGE_ON [PRIMARY]
GO
/****** Objeto: Index [idx_prod_hist_vigencia] Fecha de script: 26/7/2026 22:40:00 ******/
CREATE NONCLUSTERED INDEX [idx_prod_hist_vigencia] ON [dbo].[inv_productos_historial]
(
	[producto_id] ASC,
	[fecha_inicio] ASC,
	[fecha_fin] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, SORT_IN_TEMPDB = OFF, DROP_EXISTING = OFF, ONLINE = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
GO
ALTER TABLE [dbo].[inv_bitacora] ADD  DEFAULT (getdate()) FOR [fecha]
GO
ALTER TABLE [dbo].[inv_bod_egresos] ADD  DEFAULT ('Admin Terminal') FOR [creado_por]
GO
ALTER TABLE [dbo].[inv_bod_ingresos] ADD  DEFAULT ('Admin Terminal') FOR [creado_por]
GO
ALTER TABLE [dbo].[inv_bod_ingresos_detalles] ADD  DEFAULT ((0.0)) FOR [porcentaje_iva]
GO
ALTER TABLE [dbo].[inv_bod_ingresos_detalles] ADD  DEFAULT ((0.0)) FOR [valor_iva]
GO
ALTER TABLE [dbo].[inv_estados] ADD  DEFAULT ((1)) FOR [estado]
GO
ALTER TABLE [dbo].[inv_inventario] ADD  DEFAULT ((0.0)) FOR [valor]
GO
ALTER TABLE [dbo].[inv_inventario] ADD  DEFAULT ((1)) FOR [activo]
GO
ALTER TABLE [dbo].[inv_inventario] ADD  DEFAULT ((1)) FOR [cantidad]
GO
ALTER TABLE [dbo].[inv_log_errores] ADD  DEFAULT ('sys') FOR [modulo]
GO
ALTER TABLE [dbo].[inv_log_errores] ADD  DEFAULT ('') FOR [accion]
GO
ALTER TABLE [dbo].[inv_log_errores] ADD  DEFAULT ('Error') FOR [tipo_error]
GO
ALTER TABLE [dbo].[inv_log_errores] ADD  DEFAULT ('') FOR [archivo_origen]
GO
ALTER TABLE [dbo].[inv_log_errores] ADD  DEFAULT ((0)) FOR [linea_origen]
GO
ALTER TABLE [dbo].[inv_log_errores] ADD  DEFAULT ('ERROR') FOR [nivel]
GO
ALTER TABLE [dbo].[inv_log_errores] ADD  DEFAULT ('development') FOR [entorno]
GO
ALTER TABLE [dbo].[inv_log_errores] ADD  DEFAULT ('') FOR [ip_cliente]
GO
ALTER TABLE [dbo].[inv_log_errores] ADD  DEFAULT (getdate()) FOR [fecha_registro]
GO
ALTER TABLE [dbo].[inv_log_eventos] ADD  DEFAULT ('sys') FOR [modulo]
GO
ALTER TABLE [dbo].[inv_log_eventos] ADD  DEFAULT ('') FOR [accion]
GO
ALTER TABLE [dbo].[inv_log_eventos] ADD  DEFAULT ('') FOR [descripcion]
GO
ALTER TABLE [dbo].[inv_log_eventos] ADD  DEFAULT ('OK') FOR [resultado]
GO
ALTER TABLE [dbo].[inv_log_eventos] ADD  DEFAULT ('') FOR [ip_cliente]
GO
ALTER TABLE [dbo].[inv_log_eventos] ADD  DEFAULT (getdate()) FOR [fecha_registro]
GO
ALTER TABLE [dbo].[inv_notificaciones] ADD  DEFAULT ((0)) FOR [visto]
GO
ALTER TABLE [dbo].[inv_notificaciones] ADD  DEFAULT (getdate()) FOR [created_at]
GO
ALTER TABLE [dbo].[inv_periodos] ADD  DEFAULT ('activo') FOR [estado]
GO
ALTER TABLE [dbo].[inv_productos] ADD  DEFAULT ((1)) FOR [aplica_iva]
GO
ALTER TABLE [dbo].[inv_productos] ADD  DEFAULT ('') FOR [codigo]
GO
ALTER TABLE [dbo].[inv_productos] ADD  DEFAULT ('') FOR [descripcion]
GO
ALTER TABLE [dbo].[inv_productos] ADD  DEFAULT ('') FOR [ubicacion]
GO
ALTER TABLE [dbo].[inv_productos] ADD  DEFAULT ((0)) FOR [existencia_min]
GO
ALTER TABLE [dbo].[inv_productos] ADD  DEFAULT ((0)) FOR [existencia_max]
GO
ALTER TABLE [dbo].[inv_productos] ADD  DEFAULT ((0)) FOR [precio_promedio]
GO
ALTER TABLE [dbo].[inv_productos] ADD  DEFAULT ((0)) FOR [existencia_actual]
GO
ALTER TABLE [dbo].[inv_productos_historial] ADD  DEFAULT ((1)) FOR [aplica_iva]
GO
ALTER TABLE [dbo].[inv_productos_historial] ADD  DEFAULT ((1)) FOR [es_actual]
GO
ALTER TABLE [dbo].[inv_secuenciales] ADD  DEFAULT ((0)) FOR [ultimo_numero]
GO
ALTER TABLE [dbo].[inv_usuarios] ADD  DEFAULT ((1)) FOR [activo]
GO
ALTER TABLE [dbo].[inv_bod_egresos]  WITH CHECK ADD FOREIGN KEY([area_id])
REFERENCES [dbo].[inv_talento_areas] ([id])
GO
ALTER TABLE [dbo].[inv_bod_egresos]  WITH CHECK ADD FOREIGN KEY([responsable_id])
REFERENCES [dbo].[inv_talento_personal] ([id])
GO
ALTER TABLE [dbo].[inv_bod_egresos_detalles]  WITH CHECK ADD FOREIGN KEY([egreso_id])
REFERENCES [dbo].[inv_bod_egresos] ([id])
ON DELETE CASCADE
GO
ALTER TABLE [dbo].[inv_bod_egresos_detalles]  WITH CHECK ADD FOREIGN KEY([grupo_id_historico])
REFERENCES [dbo].[inv_categorias] ([id])
GO
ALTER TABLE [dbo].[inv_bod_egresos_detalles]  WITH CHECK ADD FOREIGN KEY([item_id])
REFERENCES [dbo].[inv_inventario] ([id])
GO
ALTER TABLE [dbo].[inv_bod_ingresos]  WITH CHECK ADD FOREIGN KEY([responsable_id])
REFERENCES [dbo].[inv_talento_personal] ([id])
GO
ALTER TABLE [dbo].[inv_bod_ingresos_detalles]  WITH CHECK ADD FOREIGN KEY([grupo_id_historico])
REFERENCES [dbo].[inv_categorias] ([id])
GO
ALTER TABLE [dbo].[inv_bod_ingresos_detalles]  WITH CHECK ADD FOREIGN KEY([ingreso_id])
REFERENCES [dbo].[inv_bod_ingresos] ([id])
ON DELETE CASCADE
GO
ALTER TABLE [dbo].[inv_bod_ingresos_detalles]  WITH CHECK ADD FOREIGN KEY([item_id])
REFERENCES [dbo].[inv_inventario] ([id])
GO
ALTER TABLE [dbo].[inv_centros_consumo]  WITH CHECK ADD FOREIGN KEY([grupo_id])
REFERENCES [dbo].[inv_grupo_centros_consumo] ([id])
GO
ALTER TABLE [dbo].[inv_inventario]  WITH CHECK ADD FOREIGN KEY([categoria_id])
REFERENCES [dbo].[inv_categorias] ([id])
GO
ALTER TABLE [dbo].[inv_inventario]  WITH CHECK ADD FOREIGN KEY([estado_id])
REFERENCES [dbo].[inv_estados] ([idestado])
GO
ALTER TABLE [dbo].[inv_inventario]  WITH CHECK ADD FOREIGN KEY([producto_id])
REFERENCES [dbo].[inv_productos] ([id])
GO
ALTER TABLE [dbo].[inv_inventario]  WITH CHECK ADD FOREIGN KEY([responsable_id])
REFERENCES [dbo].[inv_talento_personal] ([id])
GO
ALTER TABLE [dbo].[inv_inventario]  WITH CHECK ADD FOREIGN KEY([zona_id])
REFERENCES [dbo].[inv_zonas] ([id])
GO
ALTER TABLE [dbo].[inv_log_errores]  WITH CHECK ADD FOREIGN KEY([id_usuario])
REFERENCES [dbo].[inv_usuarios] ([id])
ON DELETE SET NULL
GO
ALTER TABLE [dbo].[inv_log_eventos]  WITH CHECK ADD FOREIGN KEY([id_usuario])
REFERENCES [dbo].[inv_usuarios] ([id])
ON DELETE SET NULL
GO
ALTER TABLE [dbo].[inv_permisos]  WITH CHECK ADD FOREIGN KEY([usuario_id])
REFERENCES [dbo].[inv_usuarios] ([id])
ON DELETE CASCADE
GO
ALTER TABLE [dbo].[inv_productos]  WITH CHECK ADD FOREIGN KEY([grupo_id])
REFERENCES [dbo].[inv_categorias] ([id])
GO
ALTER TABLE [dbo].[inv_productos]  WITH CHECK ADD FOREIGN KEY([unidad_id])
REFERENCES [dbo].[inv_unidades] ([id])
GO
ALTER TABLE [dbo].[inv_productos_historial]  WITH CHECK ADD FOREIGN KEY([grupo_id])
REFERENCES [dbo].[inv_categorias] ([id])
GO
ALTER TABLE [dbo].[inv_productos_historial]  WITH CHECK ADD FOREIGN KEY([producto_id])
REFERENCES [dbo].[inv_productos] ([id])
GO
ALTER TABLE [dbo].[inv_respaldo_historico]  WITH CHECK ADD FOREIGN KEY([periodo_id])
REFERENCES [dbo].[inv_periodos] ([id])
ON DELETE CASCADE
GO
ALTER TABLE [dbo].[inv_talento_asignaciones]  WITH CHECK ADD FOREIGN KEY([area_id])
REFERENCES [dbo].[inv_talento_areas] ([id])
ON DELETE CASCADE
GO
ALTER TABLE [dbo].[inv_talento_asignaciones]  WITH CHECK ADD FOREIGN KEY([personal_id])
REFERENCES [dbo].[inv_talento_personal] ([id])
ON DELETE CASCADE
GO
ALTER TABLE [dbo].[inv_valores_iva]  WITH CHECK ADD FOREIGN KEY([periodo_id])
REFERENCES [dbo].[inv_periodos] ([id])
ON DELETE CASCADE
GO
USE [master]
GO
ALTER DATABASE [inventario] SET  READ_WRITE 
GO
