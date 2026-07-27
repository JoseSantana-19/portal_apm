USE [master]
GO
/****** Objeto: Database [PortuariaExterna] Fecha de script: 27/7/2026 08:57:38 ******/
-- Sin ruta de archivo fija (la de MSSQL16.VICTUS era de una sola máquina y
-- rompía "CREATE DATABASE" en cualquier otra instancia) — usa las rutas de
-- datos por defecto configuradas en ESE SQL Server. Si PORTAL_APM_COMPLETO.sql
-- ya corrió antes y creó esta base vacía (stub para resolver dependencias
-- cross-DB), no reintenta el CREATE DATABASE — sigue directo a poblarla
-- con las tablas reales de abajo.
IF DB_ID(N'PortuariaExterna') IS NULL
BEGIN
    EXEC(N'CREATE DATABASE [PortuariaExterna] COLLATE Modern_Spanish_CI_AS;');
END
GO
ALTER DATABASE [PortuariaExterna] SET COMPATIBILITY_LEVEL = 120
GO
IF (1 = FULLTEXTSERVICEPROPERTY('IsFullTextInstalled'))
begin
EXEC [PortuariaExterna].[dbo].[sp_fulltext_database] @action = 'enable'
end
GO
ALTER DATABASE [PortuariaExterna] SET ANSI_NULL_DEFAULT OFF 
GO
ALTER DATABASE [PortuariaExterna] SET ANSI_NULLS OFF 
GO
ALTER DATABASE [PortuariaExterna] SET ANSI_PADDING OFF 
GO
ALTER DATABASE [PortuariaExterna] SET ANSI_WARNINGS OFF 
GO
ALTER DATABASE [PortuariaExterna] SET ARITHABORT OFF 
GO
ALTER DATABASE [PortuariaExterna] SET AUTO_CLOSE ON 
GO
ALTER DATABASE [PortuariaExterna] SET AUTO_SHRINK OFF 
GO
ALTER DATABASE [PortuariaExterna] SET AUTO_UPDATE_STATISTICS ON 
GO
ALTER DATABASE [PortuariaExterna] SET CURSOR_CLOSE_ON_COMMIT OFF 
GO
ALTER DATABASE [PortuariaExterna] SET CURSOR_DEFAULT  GLOBAL 
GO
ALTER DATABASE [PortuariaExterna] SET CONCAT_NULL_YIELDS_NULL OFF 
GO
ALTER DATABASE [PortuariaExterna] SET NUMERIC_ROUNDABORT OFF 
GO
ALTER DATABASE [PortuariaExterna] SET QUOTED_IDENTIFIER OFF 
GO
ALTER DATABASE [PortuariaExterna] SET RECURSIVE_TRIGGERS OFF 
GO
ALTER DATABASE [PortuariaExterna] SET  ENABLE_BROKER 
GO
ALTER DATABASE [PortuariaExterna] SET AUTO_UPDATE_STATISTICS_ASYNC OFF 
GO
ALTER DATABASE [PortuariaExterna] SET DATE_CORRELATION_OPTIMIZATION OFF 
GO
ALTER DATABASE [PortuariaExterna] SET TRUSTWORTHY OFF 
GO
ALTER DATABASE [PortuariaExterna] SET ALLOW_SNAPSHOT_ISOLATION OFF 
GO
ALTER DATABASE [PortuariaExterna] SET PARAMETERIZATION SIMPLE 
GO
ALTER DATABASE [PortuariaExterna] SET READ_COMMITTED_SNAPSHOT OFF 
GO
ALTER DATABASE [PortuariaExterna] SET HONOR_BROKER_PRIORITY OFF 
GO
ALTER DATABASE [PortuariaExterna] SET RECOVERY SIMPLE 
GO
ALTER DATABASE [PortuariaExterna] SET  MULTI_USER 
GO
ALTER DATABASE [PortuariaExterna] SET PAGE_VERIFY CHECKSUM  
GO
ALTER DATABASE [PortuariaExterna] SET DB_CHAINING OFF 
GO
ALTER DATABASE [PortuariaExterna] SET FILESTREAM( NON_TRANSACTED_ACCESS = OFF ) 
GO
ALTER DATABASE [PortuariaExterna] SET TARGET_RECOVERY_TIME = 60 SECONDS 
GO
ALTER DATABASE [PortuariaExterna] SET DELAYED_DURABILITY = DISABLED
GO
USE [PortuariaExterna]
GO
/****** Objeto: Table [dbo].[bit_personas] Fecha de script: 27/7/2026 08:57:38 ******/
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
PRIMARY KEY CLUSTERED 
(
	[id_persona] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY]
GO
SET ANSI_PADDING ON
GO
/****** Objeto: Index [UQ_ext_personas_nidentificacion_real] Fecha de script: 27/7/2026 08:57:38 ******/
CREATE UNIQUE NONCLUSTERED INDEX [UQ_ext_personas_nidentificacion_real] ON [dbo].[bit_personas]
(
	[nidentificacion] ASC
)
WHERE ([nidentificacion]<>N'9999999999')
WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, SORT_IN_TEMPDB = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
GO
ALTER TABLE [dbo].[bit_personas] ADD  CONSTRAINT [DF_ext_personas_tidentif]  DEFAULT (N'Cédula') FOR [tidentif]
GO
ALTER TABLE [dbo].[bit_personas] ADD  CONSTRAINT [DF_ext_personas_estado]  DEFAULT ((1)) FOR [estado]
GO
USE [master]
GO
ALTER DATABASE [PortuariaExterna] SET  READ_WRITE 
GO
