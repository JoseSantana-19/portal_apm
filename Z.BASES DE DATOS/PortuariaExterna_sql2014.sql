/*
  PortuariaExterna -- script portable para SQL Server 2014 (compatibilidad 120).
  Contiene esquema completo (tablas, indices, PK/FK/UNIQUE/CHECK, disparadores)
  y todos los datos. Generado: 2026-09-03 22:33:00 -05:00
*/
SET NOCOUNT ON;
SET XACT_ABORT ON;
GO
IF DB_ID(N'PortuariaExterna') IS NOT NULL
BEGIN
    RAISERROR(N'La base PortuariaExterna ya existe en este servidor -- el script se detuvo para no sobrescribirla.', 16, 1);
    SET NOEXEC ON;
END
GO
CREATE DATABASE [PortuariaExterna];
GO
ALTER DATABASE [PortuariaExterna] SET COMPATIBILITY_LEVEL = 120;
GO
USE [PortuariaExterna];
GO
SET ANSI_NULLS ON
GO
SET ANSI_NULLS ON;
SET QUOTED_IDENTIFIER ON;
SET ANSI_PADDING ON;
SET ANSI_WARNINGS ON;
SET ARITHABORT ON;
SET CONCAT_NULL_YIELDS_NULL ON;
SET NUMERIC_ROUNDABORT OFF;
GO
SET QUOTED_IDENTIFIER ON
GO
SET ANSI_NULLS ON;
SET QUOTED_IDENTIFIER ON;
SET ANSI_PADDING ON;
SET ANSI_WARNINGS ON;
SET ARITHABORT ON;
SET CONCAT_NULL_YIELDS_NULL ON;
SET NUMERIC_ROUNDABORT OFF;
GO
CREATE TABLE [dbo].[bit_personas](
	[id_persona] [int] IDENTITY(1,1) NOT NULL,
	[nidentificacion] [nvarchar](20) COLLATE Modern_Spanish_CI_AS NOT NULL,
	[tidentif] [nvarchar](20) COLLATE Modern_Spanish_CI_AS NOT NULL,
	[nombres] [nvarchar](100) COLLATE Modern_Spanish_CI_AS NOT NULL,
	[apellidos] [nvarchar](100) COLLATE Modern_Spanish_CI_AS NOT NULL,
	[estado] [bit] NOT NULL,
 CONSTRAINT [PK__bit_pers__228148B02E64CB90] PRIMARY KEY CLUSTERED 
(
	[id_persona] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON)
)
GO
SET ANSI_NULLS ON;
SET QUOTED_IDENTIFIER ON;
SET ANSI_PADDING ON;
SET ANSI_WARNINGS ON;
SET ARITHABORT ON;
SET CONCAT_NULL_YIELDS_NULL ON;
SET NUMERIC_ROUNDABORT OFF;
GO
SET ANSI_NULLS ON
GO
SET ANSI_NULLS ON;
SET QUOTED_IDENTIFIER ON;
SET ANSI_PADDING ON;
SET ANSI_WARNINGS ON;
SET ARITHABORT ON;
SET CONCAT_NULL_YIELDS_NULL ON;
SET NUMERIC_ROUNDABORT OFF;
GO
SET QUOTED_IDENTIFIER ON
GO
SET ANSI_NULLS ON;
SET QUOTED_IDENTIFIER ON;
SET ANSI_PADDING ON;
SET ANSI_WARNINGS ON;
SET ARITHABORT ON;
SET CONCAT_NULL_YIELDS_NULL ON;
SET NUMERIC_ROUNDABORT OFF;
GO
CREATE TABLE [dbo].[reg_empresas](
	[idempresa] [numeric](18, 0) IDENTITY(1,1) NOT NULL,
	[sc_empresa] [numeric](18, 0) NULL,
	[ruc] [nvarchar](13) COLLATE Modern_Spanish_CI_AS NOT NULL,
	[nmatricula] [nvarchar](50) COLLATE Modern_Spanish_CI_AS NULL,
	[empresa] [nvarchar](150) COLLATE Modern_Spanish_CI_AS NOT NULL,
	[razonsocial] [nvarchar](150) COLLATE Modern_Spanish_CI_AS NOT NULL,
	[idreplegal] [nvarchar](13) COLLATE Modern_Spanish_CI_AS NOT NULL,
	[nomreprelegal] [nvarchar](150) COLLATE Modern_Spanish_CI_AS NOT NULL,
	[direccion] [nvarchar](150) COLLATE Modern_Spanish_CI_AS NOT NULL,
	[telefono] [nvarchar](25) COLLATE Modern_Spanish_CI_AS NOT NULL,
	[email1] [nvarchar](75) COLLATE Modern_Spanish_CI_AS NOT NULL,
	[email2] [nvarchar](75) COLLATE Modern_Spanish_CI_AS NOT NULL,
	[actividad] [nvarchar](250) COLLATE Modern_Spanish_CI_AS NOT NULL,
	[estado] [int] NOT NULL,
	[fregistro] [datetime] NULL,
	[tipoempresa] [int] NOT NULL,
 CONSTRAINT [PK_reg_empresas] PRIMARY KEY CLUSTERED 
(
	[idempresa] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON)
)
GO
SET ANSI_NULLS ON;
SET QUOTED_IDENTIFIER ON;
SET ANSI_PADDING ON;
SET ANSI_WARNINGS ON;
SET ARITHABORT ON;
SET CONCAT_NULL_YIELDS_NULL ON;
SET NUMERIC_ROUNDABORT OFF;
GO
SET ANSI_PADDING ON
GO
SET ANSI_NULLS ON;
SET QUOTED_IDENTIFIER ON;
SET ANSI_PADDING ON;
SET ANSI_WARNINGS ON;
SET ARITHABORT ON;
SET CONCAT_NULL_YIELDS_NULL ON;
SET NUMERIC_ROUNDABORT OFF;
GO
CREATE UNIQUE NONCLUSTERED INDEX [UQ_ext_personas_nidentificacion_real] ON [dbo].[bit_personas]
(
	[nidentificacion] ASC
)
WHERE ([nidentificacion]<>N'9999999999')
WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, SORT_IN_TEMPDB = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON)
GO
SET ANSI_NULLS ON;
SET QUOTED_IDENTIFIER ON;
SET ANSI_PADDING ON;
SET ANSI_WARNINGS ON;
SET ARITHABORT ON;
SET CONCAT_NULL_YIELDS_NULL ON;
SET NUMERIC_ROUNDABORT OFF;
GO
ALTER TABLE [dbo].[bit_personas] ADD  CONSTRAINT [DF_ext_personas_tidentif]  DEFAULT (N'Cédula') FOR [tidentif]
GO
SET ANSI_NULLS ON;
SET QUOTED_IDENTIFIER ON;
SET ANSI_PADDING ON;
SET ANSI_WARNINGS ON;
SET ARITHABORT ON;
SET CONCAT_NULL_YIELDS_NULL ON;
SET NUMERIC_ROUNDABORT OFF;
GO
ALTER TABLE [dbo].[bit_personas] ADD  CONSTRAINT [DF_ext_personas_estado]  DEFAULT ((1)) FOR [estado]
GO
SET ANSI_NULLS ON;
SET QUOTED_IDENTIFIER ON;
SET ANSI_PADDING ON;
SET ANSI_WARNINGS ON;
SET ARITHABORT ON;
SET CONCAT_NULL_YIELDS_NULL ON;
SET NUMERIC_ROUNDABORT OFF;
GO
/* Desactivar temporalmente relaciones y disparadores durante la carga de datos. */
ALTER TABLE [dbo].[bit_personas] NOCHECK CONSTRAINT ALL;
DISABLE TRIGGER ALL ON [dbo].[bit_personas];
ALTER TABLE [dbo].[reg_empresas] NOCHECK CONSTRAINT ALL;
DISABLE TRIGGER ALL ON [dbo].[reg_empresas];
GO
SET IDENTITY_INSERT [dbo].[bit_personas] ON
GO
INSERT [dbo].[bit_personas] ([id_persona], [nidentificacion], [tidentif], [nombres], [apellidos], [estado]) VALUES (1, N'1722002001', N'Cédula', N'Diana', N'Fuentes Cedeño', 1)
GO
INSERT [dbo].[bit_personas] ([id_persona], [nidentificacion], [tidentif], [nombres], [apellidos], [estado]) VALUES (2, N'1722002002', N'Cédula', N'Marco', N'Vallejo Pincay', 1)
GO
INSERT [dbo].[bit_personas] ([id_persona], [nidentificacion], [tidentif], [nombres], [apellidos], [estado]) VALUES (3, N'1722002003', N'Cédula', N'Ruth', N'Orrala Macías', 1)
GO
INSERT [dbo].[bit_personas] ([id_persona], [nidentificacion], [tidentif], [nombres], [apellidos], [estado]) VALUES (4, N'1722002004', N'Cédula', N'Héctor', N'Zambrano Vera', 1)
GO
INSERT [dbo].[bit_personas] ([id_persona], [nidentificacion], [tidentif], [nombres], [apellidos], [estado]) VALUES (5, N'1722002005', N'Cédula', N'Patricia', N'Arcentales Ruiz', 1)
GO
INSERT [dbo].[bit_personas] ([id_persona], [nidentificacion], [tidentif], [nombres], [apellidos], [estado]) VALUES (6, N'1722002006', N'Cédula', N'Felipe', N'Morán Delgado', 1)
GO
INSERT [dbo].[bit_personas] ([id_persona], [nidentificacion], [tidentif], [nombres], [apellidos], [estado]) VALUES (7, N'1722002007', N'Cédula', N'Silvia', N'Baque Intriago', 1)
GO
INSERT [dbo].[bit_personas] ([id_persona], [nidentificacion], [tidentif], [nombres], [apellidos], [estado]) VALUES (8, N'1722002008', N'Cédula', N'Óscar', N'Cedeño Mendoza', 1)
GO
INSERT [dbo].[bit_personas] ([id_persona], [nidentificacion], [tidentif], [nombres], [apellidos], [estado]) VALUES (9, N'1722002009', N'Cédula', N'Valeria', N'Plúa Zambrano', 1)
GO
INSERT [dbo].[bit_personas] ([id_persona], [nidentificacion], [tidentif], [nombres], [apellidos], [estado]) VALUES (10, N'1722002010', N'Cédula', N'Ramiro', N'Vera Delgado', 1)
GO
SET IDENTITY_INSERT [dbo].[bit_personas] OFF
GO
SET IDENTITY_INSERT [dbo].[reg_empresas] ON
GO
INSERT [dbo].[reg_empresas] ([idempresa], [sc_empresa], [ruc], [nmatricula], [empresa], [razonsocial], [idreplegal], [nomreprelegal], [direccion], [telefono], [email1], [email2], [actividad], [estado], [fregistro], [tipoempresa]) VALUES (CAST(26 AS Numeric(18, 0)), CAST(26 AS Numeric(18, 0)), N'1306663517001', N'11111111111111', N'razon social', N'razon social', N'1308126646', N'dsfasdfsdfasdfasd', N'34234234', N'324234324', N'fzambran22o@apm.gob.ec', N'NO REGISTRADO', N'dfasdfasdfasdf', 4, CAST(N'2017-02-06T15:05:47.563' AS DateTime), 3)
GO
INSERT [dbo].[reg_empresas] ([idempresa], [sc_empresa], [ruc], [nmatricula], [empresa], [razonsocial], [idreplegal], [nomreprelegal], [direccion], [telefono], [email1], [email2], [actividad], [estado], [fregistro], [tipoempresa]) VALUES (CAST(27 AS Numeric(18, 0)), CAST(27 AS Numeric(18, 0)), N'1312149485001', N'', N'razon social', N'razon social', N'1308126646', N'dsfasdfsdfasdfasd', N'34234234', N'324234324', N'fzambran22o@apm.gob.ec', N'NO REGISTRADO', N'dfasdfasdfasdf', 4, CAST(N'2017-02-06T15:19:09.980' AS DateTime), 3)
GO
INSERT [dbo].[reg_empresas] ([idempresa], [sc_empresa], [ruc], [nmatricula], [empresa], [razonsocial], [idreplegal], [nomreprelegal], [direccion], [telefono], [email1], [email2], [actividad], [estado], [fregistro], [tipoempresa]) VALUES (CAST(62 AS Numeric(18, 0)), CAST(62 AS Numeric(18, 0)), N'1391829442001', N'20160013', N'NAVIERA ECUATORIANA AUSTRAL MARITIMA AUSMAR S.A', N'NAVIERA ECUATORIANA AUSTRAL MARITIMA AUSMAR S.A', N'1312844820', N'Karen Elizabeth Lopez Velez', N'Calle M1 entre Av 23 y 24', N'052621916', N'xxx@apm.gob.ec', N'NO REGISTRADO', N'AGENCIA NAVIERA', 4, CAST(N'2017-02-12T10:12:08.013' AS DateTime), 1)
GO
INSERT [dbo].[reg_empresas] ([idempresa], [sc_empresa], [ruc], [nmatricula], [empresa], [razonsocial], [idreplegal], [nomreprelegal], [direccion], [telefono], [email1], [email2], [actividad], [estado], [fregistro], [tipoempresa]) VALUES (CAST(83 AS Numeric(18, 0)), CAST(83 AS Numeric(18, 0)), N'1390005713001', N'20100150', N'INDUSTRIA ECUATORIANA PRODUCTORA DE ALIMENTOS C.A.', N'INDUSTRIA ECUATORIANA PRODUCTORA DE ALIMENTOS C.A.', N'1704215811', N'ALEXANDRA BRAVO CEDEÑO', N'MALECON S/N', N'2620304', N'xxx@apm.gob.ec', N'NO REGISTRADO', N'PROCESAMIENTO DE CONSERVAS DE ATUN Y SARDINA', 4, CAST(N'2018-02-01T09:53:19.020' AS DateTime), 2)
GO
INSERT [dbo].[reg_empresas] ([idempresa], [sc_empresa], [ruc], [nmatricula], [empresa], [razonsocial], [idreplegal], [nomreprelegal], [direccion], [telefono], [email1], [email2], [actividad], [estado], [fregistro], [tipoempresa]) VALUES (CAST(87 AS Numeric(18, 0)), CAST(87 AS Numeric(18, 0)), N'1302188618001', N'P-04-00584', N'DANIEL ROBERTO BUEHS BOWEN', N'DANIEL ROBERTO BUEHS BOWEN', N'1302188618', N'DANIEL ROBERTO BUEHS BOWEN', N'AV. 113 S/N CALLE 304', N'052921004', N'xxx@apm.gob.ec', N'NO REGISTRADO', N'PESCA MARITIMA Y CONTINENTAL DE PECES, CRUSTACEOS Y MOLUSCOS.', 4, CAST(N'2018-02-01T10:24:30.417' AS DateTime), 2)
GO
INSERT [dbo].[reg_empresas] ([idempresa], [sc_empresa], [ruc], [nmatricula], [empresa], [razonsocial], [idreplegal], [nomreprelegal], [direccion], [telefono], [email1], [email2], [actividad], [estado], [fregistro], [tipoempresa]) VALUES (CAST(88 AS Numeric(18, 0)), CAST(88 AS Numeric(18, 0)), N'1391701020001', N'20100231', N'BOWSA', N'BOWSA', N'1305480319', N'DIEGO JAVIER REYES VERA', N'CALLE 20 CON AV. FLAVIO Y MALECON', N'626918 ', N'xxx@apm.gob.ec', N'NO REGISTRADO', N'NAVIERA', 4, CAST(N'2018-02-01T11:06:34.910' AS DateTime), 1)
GO
INSERT [dbo].[reg_empresas] ([idempresa], [sc_empresa], [ruc], [nmatricula], [empresa], [razonsocial], [idreplegal], [nomreprelegal], [direccion], [telefono], [email1], [email2], [actividad], [estado], [fregistro], [tipoempresa]) VALUES (CAST(93 AS Numeric(18, 0)), CAST(93 AS Numeric(18, 0)), N'1309150991001', N'20150169', N'LUIS CESAR SANCHEZ MERA ', N'LUIS CESAR SANCHEZ MERA ', N'1309150991', N'LUIS CESAR SANCHEZ MERA ', N'CUBA ', N'0997387878', N'xxx@apm.gob.ec', N'NO REGISTRADO', N'PESCA', 4, CAST(N'2018-03-01T08:42:40.910' AS DateTime), 2)
GO
INSERT [dbo].[reg_empresas] ([idempresa], [sc_empresa], [ruc], [nmatricula], [empresa], [razonsocial], [idreplegal], [nomreprelegal], [direccion], [telefono], [email1], [email2], [actividad], [estado], [fregistro], [tipoempresa]) VALUES (CAST(94 AS Numeric(18, 0)), CAST(94 AS Numeric(18, 0)), N'1390116280001', N'20100239', N'NAVIERA-JCP  COMPAÑIA LIMITADA ', N'NAVIERA-JCP  COMPAÑIA LIMITADA ', N'1301110134', N'JORGE CEDEÑO PARRALES ', N'AV 6 ENTRE CALLE 15 Y 16', N'0997195452', N'xxx@apm.gob.ec', N'NO REGISTRADO', N'AGENCIAMIENTO DE BARCOS NACIONALES Y EXTRANGEROS ', 4, CAST(N'2018-04-01T11:06:18.563' AS DateTime), 1)
GO
INSERT [dbo].[reg_empresas] ([idempresa], [sc_empresa], [ruc], [nmatricula], [empresa], [razonsocial], [idreplegal], [nomreprelegal], [direccion], [telefono], [email1], [email2], [actividad], [estado], [fregistro], [tipoempresa]) VALUES (CAST(95 AS Numeric(18, 0)), CAST(95 AS Numeric(18, 0)), N'1390149154001', N'P-04-00232', N'SHELLFISH S.A.', N'SHELLFISH S.A.', N'1307853653', N'PABLO MARCIAL ZAMBRANO CEDEÑO', N'ENTRE CALLE 15 Y 16 AV. 6TA', N'052623769-052629899', N'xxx@apm.gob.ec', N'NO REGISTRADO', N'PESCA MARITIMA', 4, CAST(N'2018-04-01T11:28:15.940' AS DateTime), 2)
GO
INSERT [dbo].[reg_empresas] ([idempresa], [sc_empresa], [ruc], [nmatricula], [empresa], [razonsocial], [idreplegal], [nomreprelegal], [direccion], [telefono], [email1], [email2], [actividad], [estado], [fregistro], [tipoempresa]) VALUES (CAST(96 AS Numeric(18, 0)), CAST(96 AS Numeric(18, 0)), N'1301350649001', N'20120095', N'ARCENTALES MERO NANCI EULALIA', N'ARCENTALES MERO NANCI EULALIA', N'1301350649', N'ARCENTALES MERO NANCI EULALIA', N'CDLA. URSA CALLE A-2 B-2', N'052381631', N'xxx@apm.gob.ec', N'NO REGISTRADO', N'pesca maritima y continental de peces, crustaceos y moluscos', 4, CAST(N'2018-04-01T12:24:53.400' AS DateTime), 2)
GO
INSERT [dbo].[reg_empresas] ([idempresa], [sc_empresa], [ruc], [nmatricula], [empresa], [razonsocial], [idreplegal], [nomreprelegal], [direccion], [telefono], [email1], [email2], [actividad], [estado], [fregistro], [tipoempresa]) VALUES (CAST(97 AS Numeric(18, 0)), CAST(97 AS Numeric(18, 0)), N'1306278894001', N'20100053', N'PEDRO CORNELIO PILLIGUA IDUARTE ', N'PEDRO CORNELIO PILLIGUA IDUARTE ', N'1306278894', N'PEDRO CONERLIO PILLIGUA IDUARTE', N'CDLA 24 DE MAYO CALLE 1ERO  DE ENERO ', N'052924464', N'xxx@apm.gob.ec', N'NO REGISTRADO', N'PESCA ARTESANAL ', 4, CAST(N'2018-05-01T09:47:42.383' AS DateTime), 2)
GO
INSERT [dbo].[reg_empresas] ([idempresa], [sc_empresa], [ruc], [nmatricula], [empresa], [razonsocial], [idreplegal], [nomreprelegal], [direccion], [telefono], [email1], [email2], [actividad], [estado], [fregistro], [tipoempresa]) VALUES (CAST(99 AS Numeric(18, 0)), CAST(99 AS Numeric(18, 0)), N'1308277409001', N'20140549', N'PATRICIO RICHARD CHAVEZ CHAVEZ', N'PATRICIO RICHARD CHAVEZ CHAVEZ', N'1306277409', N'PATRICIO RICHARD CHAVEZ CHAVEZ', N'CDLA. URSA A-2 S/N CALLE B-2', N'052381631', N'xxx@apm.gob.ec', N'NO REGISTRADO', N'pesca maritima y continental de peces', 4, CAST(N'2018-09-01T12:18:40.193' AS DateTime), 2)
GO
INSERT [dbo].[reg_empresas] ([idempresa], [sc_empresa], [ruc], [nmatricula], [empresa], [razonsocial], [idreplegal], [nomreprelegal], [direccion], [telefono], [email1], [email2], [actividad], [estado], [fregistro], [tipoempresa]) VALUES (CAST(100 AS Numeric(18, 0)), CAST(100 AS Numeric(18, 0)), N'1306277409001', N'20140549', N'PATRICIO RICHARD CHAVEZ CHAVEZ', N'PATRICIO RICHARD CHAVEZ CHAVEZ', N'1306277409', N'PATRICIO RICHARD CHAVEZ CHAVEZ', N'CDLA. URSA A-2 S/N CALLE B-2', N'052381631', N'xxx@apm.gob.ec', N'NO REGISTRADO', N'pesca maritima y continental de peces.', 4, CAST(N'2018-09-01T12:26:50.553' AS DateTime), 2)
GO
INSERT [dbo].[reg_empresas] ([idempresa], [sc_empresa], [ruc], [nmatricula], [empresa], [razonsocial], [idreplegal], [nomreprelegal], [direccion], [telefono], [email1], [email2], [actividad], [estado], [fregistro], [tipoempresa]) VALUES (CAST(101 AS Numeric(18, 0)), CAST(101 AS Numeric(18, 0)), N'1301855431001', N'P-04-00261', N'VELEZ ESCOBAR CAMILA TRINIDAD ', N'VELEZ ESCOBAR CAMILA TRINIDAD ', N'1306998715', N'VELEZ ESCOBAR CAMILA TRINIDAD', N'Barrio Cordova , calle flavio reyes y avenida 15', N'055003180', N'xxx@apm.gob.ec', N'NO REGISTRADO', N'pesca maritima y continental de peces ', 4, CAST(N'2018-11-01T10:09:13.317' AS DateTime), 2)
GO
INSERT [dbo].[reg_empresas] ([idempresa], [sc_empresa], [ruc], [nmatricula], [empresa], [razonsocial], [idreplegal], [nomreprelegal], [direccion], [telefono], [email1], [email2], [actividad], [estado], [fregistro], [tipoempresa]) VALUES (CAST(102 AS Numeric(18, 0)), CAST(102 AS Numeric(18, 0)), N'1308294626001', N'20130303', N'MERO ARCENTALES GEANINE', N'MERO ARCENTALES GEANINE', N'1308294626', N'GEANINE MERO ARCENTALES', N'CDLA. URSA A-2 S/N CALLE B-2', N'052381631', N'xxx@apm.gob.ec', N'NO REGISTRADO', N'pesca maritima y continental de peces, crustaceos y moluscos', 4, CAST(N'2018-11-01T11:46:52.057' AS DateTime), 2)
GO
INSERT [dbo].[reg_empresas] ([idempresa], [sc_empresa], [ruc], [nmatricula], [empresa], [razonsocial], [idreplegal], [nomreprelegal], [direccion], [telefono], [email1], [email2], [actividad], [estado], [fregistro], [tipoempresa]) VALUES (CAST(103 AS Numeric(18, 0)), CAST(103 AS Numeric(18, 0)), N'1300092499001', N'20110365', N'ALFONSO MERO MERO', N'ALFONSO MERO MERO', N'1300092499', N'ALFONSO MERO MERO', N'CDLA. URSA A-2 S/N CALLE B-2', N'052381631', N'xxx@apm.gob.ec', N'NO REGISTRADO', N'PESCA DE ALTURA Y COSTANERA', 4, CAST(N'2018-11-01T12:07:55.800' AS DateTime), 1)
GO
INSERT [dbo].[reg_empresas] ([idempresa], [sc_empresa], [ruc], [nmatricula], [empresa], [razonsocial], [idreplegal], [nomreprelegal], [direccion], [telefono], [email1], [email2], [actividad], [estado], [fregistro], [tipoempresa]) VALUES (CAST(118 AS Numeric(18, 0)), CAST(117 AS Numeric(18, 0)), N'1308983368001', N'20170185', N'pedro miguel falconi bravo', N'pedro miguel falconi bravo', N'1308983368', N'pedro miguel falconi bravo', N'barrio san agustin trans mies', N'926719  ', N'xxx@apm.gob.ec', N'NO REGISTRADO', N'actividades de pesca de altura y costera', 4, CAST(N'2018-01-02T09:49:08.403' AS DateTime), 2)
GO
INSERT [dbo].[reg_empresas] ([idempresa], [sc_empresa], [ruc], [nmatricula], [empresa], [razonsocial], [idreplegal], [nomreprelegal], [direccion], [telefono], [email1], [email2], [actividad], [estado], [fregistro], [tipoempresa]) VALUES (CAST(120 AS Numeric(18, 0)), CAST(119 AS Numeric(18, 0)), N'1305600106001', N'P-04-00845', N'ALARCÓN RIVERA CARLOS ALBERTO', N'ALARCÓN RIVERA CARLOS ALBERTO', N'1305600106', N'ALARCON RIVERA CARLOS ALBERTO', N'SECTOR LAS CUMBRES, AV. INTERBARRIAL', N'0981198712', N'xxx@apm.gob.ec', N'NO REGISTRADO', N'PESCA MARITIMA Y CONTINENTAL DE PECES, CRUSTACEOS Y MOLUSCOS', 4, CAST(N'2018-05-02T10:54:35.453' AS DateTime), 2)
GO
INSERT [dbo].[reg_empresas] ([idempresa], [sc_empresa], [ruc], [nmatricula], [empresa], [razonsocial], [idreplegal], [nomreprelegal], [direccion], [telefono], [email1], [email2], [actividad], [estado], [fregistro], [tipoempresa]) VALUES (CAST(121 AS Numeric(18, 0)), CAST(120 AS Numeric(18, 0)), N'1304041526001', N'p_04_00618', N'FRANK FERNANDO ALVIA ALONZO', N'FRANK FERNANDO ALVIA ALONZO', N'1304041526', N'FRANK FERNANDO ALVIA ALONZO', N'calle 12 avenida 45', N'0980354571     0979345383', N'xxx@apm.gob.ec', N'NO REGISTRADO', N'VENTA AL POR MAYOR Y MENOR DE PRODUCTOS DE PESCA', 4, CAST(N'2018-05-02T19:11:14.557' AS DateTime), 2)
GO
INSERT [dbo].[reg_empresas] ([idempresa], [sc_empresa], [ruc], [nmatricula], [empresa], [razonsocial], [idreplegal], [nomreprelegal], [direccion], [telefono], [email1], [email2], [actividad], [estado], [fregistro], [tipoempresa]) VALUES (CAST(136 AS Numeric(18, 0)), CAST(135 AS Numeric(18, 0)), N'1391830068001', N'20160109', N'PESQUERA TUNAQUICK S.A', N'PESQUERA TUNAQUICK S.A', N'1304028929', N'BEBERLY ISABEL BONILLA ', N'KM 8.5 VIA MANTA MONTECRISTI', N'052318588', N'xxx@apm.gob.ec', N'NO REGISTRADO', N'ACTIVIDADES DE PESCA DE ALTURA Y COSTERA', 4, CAST(N'2018-06-03T10:53:38.860' AS DateTime), 2)
GO
INSERT [dbo].[reg_empresas] ([idempresa], [sc_empresa], [ruc], [nmatricula], [empresa], [razonsocial], [idreplegal], [nomreprelegal], [direccion], [telefono], [email1], [email2], [actividad], [estado], [fregistro], [tipoempresa]) VALUES (CAST(137 AS Numeric(18, 0)), CAST(136 AS Numeric(18, 0)), N'1305603456001', N'P-04-010001', N'ESPINOZA GARCÍA HERACLITO DELFÍN', N'ESPINOZA GARCÍA HERACLITO DELFÍN', N'1305603456', N'Heráclito Delfín Espinoza García', N'El Paraíso Av. 108 entre Calles 124 y 125 ', N'052384530', N'xxx@apm.gob.ec', N'NO REGISTRADO', N'Pesca', 4, CAST(N'2018-12-03T15:16:04.503' AS DateTime), 2)
GO
INSERT [dbo].[reg_empresas] ([idempresa], [sc_empresa], [ruc], [nmatricula], [empresa], [razonsocial], [idreplegal], [nomreprelegal], [direccion], [telefono], [email1], [email2], [actividad], [estado], [fregistro], [tipoempresa]) VALUES (CAST(145 AS Numeric(18, 0)), CAST(144 AS Numeric(18, 0)), N'1312523192001', N'39432018', N'INTRIAGO LINO OSCAR MIGUEL', N'INTRIAGO LINO OSCAR MIGUEL', N'1312523192', N'INTRIAGO LINO OSCAR MIGUEL', N'PORVENIR ALTO SECTOR 20 DE MAYO CERCA DEL TANQUE', N'0968014331/0968014183', N'xxx@apm.gob.ec', N'NO REGISTRADO', N'ACTIVIDADES DE PESCA DE ALTURA Y COSTERA.', 4, CAST(N'2018-03-04T15:27:03.337' AS DateTime), 2)
GO
INSERT [dbo].[reg_empresas] ([idempresa], [sc_empresa], [ruc], [nmatricula], [empresa], [razonsocial], [idreplegal], [nomreprelegal], [direccion], [telefono], [email1], [email2], [actividad], [estado], [fregistro], [tipoempresa]) VALUES (CAST(147 AS Numeric(18, 0)), CAST(146 AS Numeric(18, 0)), N'1305909325001', N'p-04-00411', N'FREDDY ALEJANDRO ANCHUNDIA PILLIGUA', N'FREDDY ALEJANDRO ANCHUNDIA PILLIGUA', N'1305909325', N'Anchundia  Pilligua Freddy Alejandro', N'CALLE 17  AV  24 MANTA', N'0997982279', N'xxx@apm.gob.ec', N'NO REGISTRADO', N'Pesca', 4, CAST(N'2018-05-04T22:54:40.383' AS DateTime), 2)
GO
INSERT [dbo].[reg_empresas] ([idempresa], [sc_empresa], [ruc], [nmatricula], [empresa], [razonsocial], [idreplegal], [nomreprelegal], [direccion], [telefono], [email1], [email2], [actividad], [estado], [fregistro], [tipoempresa]) VALUES (CAST(157 AS Numeric(18, 0)), CAST(156 AS Numeric(18, 0)), N'1304645102001', N'P-04-00578', N'LOPEZ LUCAS JOSE GONZALO', N'LOPEZ LUCAS JOSE GONZALO', N'1304645102', N'LOPEZ LUCAS JOSE GONZALO', N'San Mateo, Calle San Gregorio ', N'0993466734', N'xxx@apm.gob.ec', N'NO REGISTRADO', N'ACTIVIDADES DE PESCA DE ALTURA Y COSTERA', 4, CAST(N'2018-06-05T07:44:40.373' AS DateTime), 2)
GO
INSERT [dbo].[reg_empresas] ([idempresa], [sc_empresa], [ruc], [nmatricula], [empresa], [razonsocial], [idreplegal], [nomreprelegal], [direccion], [telefono], [email1], [email2], [actividad], [estado], [fregistro], [tipoempresa]) VALUES (CAST(169 AS Numeric(18, 0)), CAST(168 AS Numeric(18, 0)), N'1308174802001', N'P-04-00823', N'PANTA GUTIERREZ JHON ARMANDO', N'PANTA GUTIERREZ JHON ARMANDO', N'1308174802', N'PANTA GUTIERREZ JHON ARMANDO', N'LOS ESTEROS CALLE PRINCIPAL 312 ', N'0984505401', N'xxx@apm.gob.ec', N'NO REGISTRADO', N'ACTIVIDAD DE PESCA DE ALTURA Y COSTERA', 4, CAST(N'2018-05-06T06:57:08.507' AS DateTime), 2)
GO
INSERT [dbo].[reg_empresas] ([idempresa], [sc_empresa], [ruc], [nmatricula], [empresa], [razonsocial], [idreplegal], [nomreprelegal], [direccion], [telefono], [email1], [email2], [actividad], [estado], [fregistro], [tipoempresa]) VALUES (CAST(170 AS Numeric(18, 0)), CAST(169 AS Numeric(18, 0)), N'1304636507001', N'P-04-00736', N'BAILON CRUZ DIANA DEL ROCIO', N'BAILON CRUZ DIANA DEL ROCIO', N'1304636507', N'BAILON CRUZ DIANA DEL ROCIO', N'CALLE 124 AVENIDA 108', N'0980234594', N'xxx@apm.gob.ec', N'NO REGISTRADO', N'PESCA DE ALTURA Y COSTERA', 4, CAST(N'2018-07-06T14:43:51.330' AS DateTime), 2)
GO
INSERT [dbo].[reg_empresas] ([idempresa], [sc_empresa], [ruc], [nmatricula], [empresa], [razonsocial], [idreplegal], [nomreprelegal], [direccion], [telefono], [email1], [email2], [actividad], [estado], [fregistro], [tipoempresa]) VALUES (CAST(171 AS Numeric(18, 0)), CAST(170 AS Numeric(18, 0)), N'1391716702001', N'20100270', N'MEGAOCEAN S.A.', N'MEGAOCEAN S.A.', N'1305687145', N'SUGAHARA ZAMBRANO PAUL KEN', N'CALLE J8 S/N Y AV 4 DE NOVIEMBRE', N'052921317', N'xxx@apm.gob.ec', N'NO REGISTRADO', N'Pesca Maritima y Continental de Peces, Crustaceos, Molucos', 4, CAST(N'2018-12-06T11:29:58.107' AS DateTime), 2)
GO
INSERT [dbo].[reg_empresas] ([idempresa], [sc_empresa], [ruc], [nmatricula], [empresa], [razonsocial], [idreplegal], [nomreprelegal], [direccion], [telefono], [email1], [email2], [actividad], [estado], [fregistro], [tipoempresa]) VALUES (CAST(177 AS Numeric(18, 0)), CAST(176 AS Numeric(18, 0)), N'1309536165001', N'6082-2018', N'ROJAS CANTOS JOSE OMAR', N'ROJAS CANTOS JOSE OMAR', N'1309536165', N'ROJAS CANTOS JOSE OMAR', N'URB, CEIBOS REAL MZ4', N'09899115994', N'xxx@apm.gob.ec', N'NO REGISTRADO', N'VENTA AL POR MAYOR DE PESCADO Y PRODUCTOS DE LA PESCA', 4, CAST(N'2018-03-07T17:04:29.880' AS DateTime), 2)
GO
INSERT [dbo].[reg_empresas] ([idempresa], [sc_empresa], [ruc], [nmatricula], [empresa], [razonsocial], [idreplegal], [nomreprelegal], [direccion], [telefono], [email1], [email2], [actividad], [estado], [fregistro], [tipoempresa]) VALUES (CAST(180 AS Numeric(18, 0)), CAST(179 AS Numeric(18, 0)), N'1305764480001', N'39642018', N'REYES ZAMBRANO JUAN FERMIN', N'REYES ZAMBRANO JUAN FERMIN', N'1305764480', N'REYES ZAMBRANO JUAN FERMIN', N'PARROQUIA SANTA MARIANITA AL FRENTE A LA CANCHA CINTETICA ', N'0967171802', N'xxx@apm.gob.ec', N'NO REGISTRADO', N'ACTIVIDADES DE PESCA DE ALTURA Y COSTERA: EXTRACCION DE PECES, CRUSTACEOS Y MOLUSCOS MARINOS, TORTUGAS, ERIZOS DE MAR, ASCIDIAS Y OTROS TUNICADOS, ETCETERA.', 4, CAST(N'2018-09-07T14:41:45.190' AS DateTime), 2)
GO
INSERT [dbo].[reg_empresas] ([idempresa], [sc_empresa], [ruc], [nmatricula], [empresa], [razonsocial], [idreplegal], [nomreprelegal], [direccion], [telefono], [email1], [email2], [actividad], [estado], [fregistro], [tipoempresa]) VALUES (CAST(181 AS Numeric(18, 0)), CAST(180 AS Numeric(18, 0)), N'1304507773001', N'39812018', N'SORNOZA ALAVA FRANCISCO CEFERINO', N'SORNOZA ALAVA FRANCISCO CEFERINO', N'1304507773', N'SORNOZA ALAVA FRANCISCO CEFERINO', N'BARRIO EL PARAISO CALLE 117 B AV 105', N'2382262', N'xxx@apm.gob.ec', N'NO REGISTRADO', N'ACTIVIDADES DE PESCA DE ALTURA Y COSTERA: EXTRACCION DE PECES, CRUSTACEOS Y MOLUSCOS MARINOS, TORTUGAS, ERIZOS DE MAR, ASCIDIAS Y OTROS TUNICADOS, ETCETERA.', 4, CAST(N'2018-09-07T17:21:50.627' AS DateTime), 2)
GO
INSERT [dbo].[reg_empresas] ([idempresa], [sc_empresa], [ruc], [nmatricula], [empresa], [razonsocial], [idreplegal], [nomreprelegal], [direccion], [telefono], [email1], [email2], [actividad], [estado], [fregistro], [tipoempresa]) VALUES (CAST(182 AS Numeric(18, 0)), CAST(181 AS Numeric(18, 0)), N'1307026615001', N'0547685', N'SORNOZA ALAVA JOSE JACINTO', N'SORNOZA ALAVA JOSE JACINTO', N'1307026615', N'SORNOZA ALAVA JOSE JACINTO', N'LOS ESTEROS CALLE 116 AV 103', N'0994323907', N'xxx@apm.gob.ec', N'NO REGISTRADO', N'PESCA MARITIMA Y CONTINENTAL DE PECES ', 4, CAST(N'2018-11-08T13:22:00.343' AS DateTime), 2)
GO
INSERT [dbo].[reg_empresas] ([idempresa], [sc_empresa], [ruc], [nmatricula], [empresa], [razonsocial], [idreplegal], [nomreprelegal], [direccion], [telefono], [email1], [email2], [actividad], [estado], [fregistro], [tipoempresa]) VALUES (CAST(186 AS Numeric(18, 0)), CAST(185 AS Numeric(18, 0)), N'1300039623001', N'000000', N'PILLIGUA ANCHUNDIA PEDRO RAMÓN', N'PILLIGUA ANCHUNDIA PEDRO RAMÓN', N'1300039623', N'PEDRO RAMÓN PILLIGUA ANCHUNDIA', N'ARROYO AZUL ', N'2578952', N'xxx@apm.gob.ec', N'NO REGISTRADO', N'ACTIVIDADES DE PESCA DE ALTURA Y COSTERA.', 4, CAST(N'2018-05-09T10:07:03.873' AS DateTime), 2)
GO
INSERT [dbo].[reg_empresas] ([idempresa], [sc_empresa], [ruc], [nmatricula], [empresa], [razonsocial], [idreplegal], [nomreprelegal], [direccion], [telefono], [email1], [email2], [actividad], [estado], [fregistro], [tipoempresa]) VALUES (CAST(188 AS Numeric(18, 0)), CAST(187 AS Numeric(18, 0)), N'1792246725001', N'NO TENEMOS MATRÍCULA', N'PRODUCTIVIDAD ASESORAPLUS C.L.', N'PRODUCTIVIDAD ASESORAPLUS C.L.', N'1706478730', N'CÉSAR EDISON SUÁREZ TORRES', N'MANABÍ S1- 131 Y GARCÍA MORENO', N'023514242   / 0995809497', N'xxx@apm.gob.ec', N'NO REGISTRADO', N'INSPECCIONES DE SEGURIDAD Y CERTIFICACIÓN HACCP', 4, CAST(N'2018-09-10T22:58:02.127' AS DateTime), 3)
GO
INSERT [dbo].[reg_empresas] ([idempresa], [sc_empresa], [ruc], [nmatricula], [empresa], [razonsocial], [idreplegal], [nomreprelegal], [direccion], [telefono], [email1], [email2], [actividad], [estado], [fregistro], [tipoempresa]) VALUES (CAST(189 AS Numeric(18, 0)), CAST(188 AS Numeric(18, 0)), N'1305733337001', N'P-04-01045', N'LAURITA', N'LAURITA', N'1305733337', N'FELIPE ABRAHAM ANGULO JARA', N'CALLE 13 AVENIDA 30 ', N'0980003130', N'xxx@apm.gob.ec', N'NO REGISTRADO', N'ACTIVIDAD DE PESCA DE ALTURA Y COSTERA EXTRACCIÓN DE PECES, CRUSTÁCEOS Y MOLUSCOS MARINOS, TORTUGAS, ERIZOS DE MAR, ASCIDIAS Y OTROS TUNICADOS, ETC.', 4, CAST(N'2018-12-10T14:08:33.353' AS DateTime), 2)
GO
INSERT [dbo].[reg_empresas] ([idempresa], [sc_empresa], [ruc], [nmatricula], [empresa], [razonsocial], [idreplegal], [nomreprelegal], [direccion], [telefono], [email1], [email2], [actividad], [estado], [fregistro], [tipoempresa]) VALUES (CAST(198 AS Numeric(18, 0)), CAST(197 AS Numeric(18, 0)), N'1311968307001', N'0400465', N'ANCHUNDIA PILLIGUA JORGE ALEXI', N'ANCHUNDIA PILLIGUA JORGE ALEXI', N'1311968307', N'ANCHUNDIA PILLIGUA JORGE ALEXI', N'BARRIO HUGO MAYO', N'0994338331', N'xxx@apm.gob.ec', N'NO REGISTRADO', N'VENTA AL POR MAYOR DE PESCADO, CRUSTÁCEOS, MOLUSCOS Y PRODUCTOS DE LA PESCA', 4, CAST(N'2018-07-11T12:08:23.020' AS DateTime), 2)
GO
INSERT [dbo].[reg_empresas] ([idempresa], [sc_empresa], [ruc], [nmatricula], [empresa], [razonsocial], [idreplegal], [nomreprelegal], [direccion], [telefono], [email1], [email2], [actividad], [estado], [fregistro], [tipoempresa]) VALUES (CAST(199 AS Numeric(18, 0)), CAST(198 AS Numeric(18, 0)), N'1391785402001', N'20130672', N'PESQUERA MONTECRISTI S.A.', N'PESQUERA MONTECRISTI S.A.', N'1306232859', N'RICARDO OLIVER APPENZAUSER VOIGT', N'KM 3 1/2 VIA BARRANCO PRIETO', N'0994556439', N'xxx@apm.gob.ec', N'NO REGISTRADO', N'PESCA MARITIMA Y CONTINENTAL DE PECES', 4, CAST(N'2018-08-11T16:12:01.447' AS DateTime), 2)
GO
INSERT [dbo].[reg_empresas] ([idempresa], [sc_empresa], [ruc], [nmatricula], [empresa], [razonsocial], [idreplegal], [nomreprelegal], [direccion], [telefono], [email1], [email2], [actividad], [estado], [fregistro], [tipoempresa]) VALUES (CAST(205 AS Numeric(18, 0)), CAST(204 AS Numeric(18, 0)), N'1310139371001', N'P0400767', N'CEDEÑO CEDEÑO EDISON GABRIEL', N'CEDEÑO CEDEÑO EDISON GABRIEL', N'1310139371', N'CEDEÑO CEDEÑO EDISON GABRIEL', N'CALLE 314 AVENIDA 204 SANTA CLARA', N'0988983940', N'xxx@apm.gob.ec', N'NO REGISTRADO', N'VENTA AL POR MAYOR DE PESCADO Y PRODUCTOS DE LA PESCA.', 4, CAST(N'2019-06-02T09:19:51.620' AS DateTime), 2)
GO
INSERT [dbo].[reg_empresas] ([idempresa], [sc_empresa], [ruc], [nmatricula], [empresa], [razonsocial], [idreplegal], [nomreprelegal], [direccion], [telefono], [email1], [email2], [actividad], [estado], [fregistro], [tipoempresa]) VALUES (CAST(206 AS Numeric(18, 0)), CAST(205 AS Numeric(18, 0)), N'1309890471001', N'P0400539', N'MANTUANO HOLGUIN MARÍA CAROLINA', N'MANTUANO HOLGUIN MARÍA CAROLINA', N'1309890471', N'MANTUANO HOLGUIN MARÍA CAROLINA', N'ENTRADA DE SOLCA-CALLE 311 AVENIDA 204', N'0960868629', N'xxx@apm.gob.ec', N'NO REGISTRADO', N'ACTIVIDADES DE PESCA DEL ALTURA COSTERA.', 4, CAST(N'2019-06-02T09:46:36.270' AS DateTime), 2)
GO
INSERT [dbo].[reg_empresas] ([idempresa], [sc_empresa], [ruc], [nmatricula], [empresa], [razonsocial], [idreplegal], [nomreprelegal], [direccion], [telefono], [email1], [email2], [actividad], [estado], [fregistro], [tipoempresa]) VALUES (CAST(208 AS Numeric(18, 0)), CAST(207 AS Numeric(18, 0)), N'1304938226001', N'P0400875', N'ALVIA MEDRANDA MANUEL GREGORIO', N'ALVIA MEDRANDA MANUEL GREGORIO', N'1304938226', N'ALVIA MEDRANDA MANUEL GREGORIO', N'SANTA MARIANITA Y BARRIO EL PARAISO', N'0990967229', N'xxx@apm.gob.ec', N'NO REGISTRADO', N'ACTIVIDADES DE PESCA DE ALTURA: EXTRACIÓN DE PECES CRUSTACEOS Y MOLUSCOS MARINOS.', 4, CAST(N'2019-06-02T15:41:06.063' AS DateTime), 2)
GO
INSERT [dbo].[reg_empresas] ([idempresa], [sc_empresa], [ruc], [nmatricula], [empresa], [razonsocial], [idreplegal], [nomreprelegal], [direccion], [telefono], [email1], [email2], [actividad], [estado], [fregistro], [tipoempresa]) VALUES (CAST(209 AS Numeric(18, 0)), CAST(208 AS Numeric(18, 0)), N'1791823036001', N'1791823036001', N'Comisión Interamericana del Atún Tropical', N'Comisión Interamericana del Atún Tropical', N'1304032483', N'Erick Danton Largacha Delgado', N'Avenida 7 #1843 y calle 19', N'2620039', N'xxx@apm.gob.ec', N'NO REGISTRADO', N'Investigación pesquera', 4, CAST(N'2019-11-02T11:13:29.533' AS DateTime), 3)
GO
INSERT [dbo].[reg_empresas] ([idempresa], [sc_empresa], [ruc], [nmatricula], [empresa], [razonsocial], [idreplegal], [nomreprelegal], [direccion], [telefono], [email1], [email2], [actividad], [estado], [fregistro], [tipoempresa]) VALUES (CAST(219 AS Numeric(18, 0)), CAST(218 AS Numeric(18, 0)), N'1308253697001', N'1308253697', N'ARIZALA REYES JAIME GUILLERMO', N'ARIZALA REYES JAIME GUILLERMO', N'1308253697', N'ARIZALA REYES JAIME GUILLERMO', N'BARRIO JOCAY CALLE J6 Y J19', N'0991713651', N'xxx@apm.gob.ec', N'NO REGISTRADO', N'MANTENIMIENTO, REACONDICIONAMIENTO Y REPARACION DE EMBARCACIONES', 4, CAST(N'2019-04-04T20:10:22.257' AS DateTime), 2)
GO
INSERT [dbo].[reg_empresas] ([idempresa], [sc_empresa], [ruc], [nmatricula], [empresa], [razonsocial], [idreplegal], [nomreprelegal], [direccion], [telefono], [email1], [email2], [actividad], [estado], [fregistro], [tipoempresa]) VALUES (CAST(224 AS Numeric(18, 0)), CAST(223 AS Numeric(18, 0)), N'1391818246001', N'20150001', N'PATOMARSA S.A.', N'PATOMARSA S.A.', N'1307858744', N'BRAVO PARALES BYRON PATRICIO', N'AV 10 S/N ENTRE CALLES 16 Y 17', N'2611571', N'xxx@apm.gob.ec', N'NO REGISTRADO', N'ACTIVIDADES NAVIERAS Y AGENCIAMIENTOS MARITIMOS', 4, CAST(N'2019-09-05T14:19:24.043' AS DateTime), 1)
GO
INSERT [dbo].[reg_empresas] ([idempresa], [sc_empresa], [ruc], [nmatricula], [empresa], [razonsocial], [idreplegal], [nomreprelegal], [direccion], [telefono], [email1], [email2], [actividad], [estado], [fregistro], [tipoempresa]) VALUES (CAST(233 AS Numeric(18, 0)), CAST(232 AS Numeric(18, 0)), N'0992905239001', N'201901519', N'ELECTRONICA NAVAL NAVELEC S.A.', N'ELECTRONICA NAVAL NAVELEC S.A.', N'0923214795', N'PARRALES BARRETO JHONNY SANTIAGO', N'Edificio San Pedro. Piso 1 Oficina #14. Calle: Nahim Isaias y Luis Orrantia Solar 27 manzana 110 Kennedy Norte.', N'0989511113 ', N'xxx@apm.gob.ec', N'NO REGISTRADO', N'servicio de reparación y mantenimiento corriente de buques, en las áreas de electricidad, electrónica marina y soldadura naval', 4, CAST(N'2019-05-07T10:52:29.360' AS DateTime), 3)
GO
INSERT [dbo].[reg_empresas] ([idempresa], [sc_empresa], [ruc], [nmatricula], [empresa], [razonsocial], [idreplegal], [nomreprelegal], [direccion], [telefono], [email1], [email2], [actividad], [estado], [fregistro], [tipoempresa]) VALUES (CAST(234 AS Numeric(18, 0)), CAST(233 AS Numeric(18, 0)), N'0993037818001', N'201901502', N'RCD INSPECTORA DE RIESGOS S.A.', N'RCD INSPECTORA DE RIESGOS S.A.', N'0925355380', N'MARCOS ANDRES ESTRADA IRRAZABAL', N'Ciudadela Kennedy Norte, Calle Justino Cornejo ', N'042118414', N'xxx@apm.gob.ec', N'NO REGISTRADO', N'INSPECCIONES A EMBARCACIONES', 4, CAST(N'2019-11-07T09:43:25.427' AS DateTime), 3)
GO
INSERT [dbo].[reg_empresas] ([idempresa], [sc_empresa], [ruc], [nmatricula], [empresa], [razonsocial], [idreplegal], [nomreprelegal], [direccion], [telefono], [email1], [email2], [actividad], [estado], [fregistro], [tipoempresa]) VALUES (CAST(273 AS Numeric(18, 0)), CAST(47 AS Numeric(18, 0)), N'1308126646003', N'00000', N'DPTO. TECNOLOGIA', N'DPTO. TECNOLOGIA', N'1308126646', N'FERANDO ZAMBRANO', N'CALLE 12 AV. 18', N'0997475611', N'xxx@apm.gob.ec', N'NO REGISTRADO', N'prueba de sistemas', 4, CAST(N'2021-01-02T12:36:22.423' AS DateTime), 1)
GO
INSERT [dbo].[reg_empresas] ([idempresa], [sc_empresa], [ruc], [nmatricula], [empresa], [razonsocial], [idreplegal], [nomreprelegal], [direccion], [telefono], [email1], [email2], [actividad], [estado], [fregistro], [tipoempresa]) VALUES (CAST(274 AS Numeric(18, 0)), CAST(48 AS Numeric(18, 0)), N'1308126646003', N'111', N'DPTO. TECNOLOGIA', N'DPTO. TECNOLOGIA', N'1308126646', N'FERANDO ZAMBRANO', N'CALLE 12 AV. 18', N'0997475611', N'xxx@apm.gob.ec', N'NO REGISTRADO', N'prueba de sistemas', 4, CAST(N'2021-01-02T16:32:48.790' AS DateTime), 1)
GO
INSERT [dbo].[reg_empresas] ([idempresa], [sc_empresa], [ruc], [nmatricula], [empresa], [razonsocial], [idreplegal], [nomreprelegal], [direccion], [telefono], [email1], [email2], [actividad], [estado], [fregistro], [tipoempresa]) VALUES (CAST(275 AS Numeric(18, 0)), CAST(49 AS Numeric(18, 0)), N'1308126646004', N'1111', N'RAZON SOCIAL', N'RAZON SOCIAL', N'130812646', N'NOMRBE DEL REPRESENTANTE', N'DIRECCION', N'0997475611', N'xxx@apm.gob.ec', N'NO REGISTRADO', N'ACTIVIDAD', 4, CAST(N'2021-03-02T17:20:33.087' AS DateTime), 1)
GO
INSERT [dbo].[reg_empresas] ([idempresa], [sc_empresa], [ruc], [nmatricula], [empresa], [razonsocial], [idreplegal], [nomreprelegal], [direccion], [telefono], [email1], [email2], [actividad], [estado], [fregistro], [tipoempresa]) VALUES (CAST(276 AS Numeric(18, 0)), CAST(50 AS Numeric(18, 0)), N'1308126646005', N'1111', N'RAZON SOCIAL', N'RAZON SOCIAL', N'130812646', N'NOMRBE DEL REPRESENTANTE', N'DIRECCION', N'0997475611', N'xxx@apm.gob.ec', N'NO REGISTRADO', N'ACTIVIDAD', 4, CAST(N'2021-03-02T17:30:32.397' AS DateTime), 1)
GO
INSERT [dbo].[reg_empresas] ([idempresa], [sc_empresa], [ruc], [nmatricula], [empresa], [razonsocial], [idreplegal], [nomreprelegal], [direccion], [telefono], [email1], [email2], [actividad], [estado], [fregistro], [tipoempresa]) VALUES (CAST(277 AS Numeric(18, 0)), CAST(51 AS Numeric(18, 0)), N'1308126646006', N'1111', N'RAZON SOCIAL', N'RAZON SOCIAL', N'130812646', N'NOMRBE DEL REPRESENTANTE', N'DIRECCION', N'0997475611', N'xxx@apm.gob.ec', N'NO REGISTRADO', N'ACTIVIDAD', 4, CAST(N'2021-03-02T17:31:22.113' AS DateTime), 1)
GO
INSERT [dbo].[reg_empresas] ([idempresa], [sc_empresa], [ruc], [nmatricula], [empresa], [razonsocial], [idreplegal], [nomreprelegal], [direccion], [telefono], [email1], [email2], [actividad], [estado], [fregistro], [tipoempresa]) VALUES (CAST(278 AS Numeric(18, 0)), CAST(52 AS Numeric(18, 0)), N'1308126646007', N'1111', N'RAZON SOCIAL', N'RAZON SOCIAL', N'130812646', N'NOMRBE DEL REPRESENTANTE', N'DIRECCION', N'0997475611', N'xxx@apm.gob.ec', N'NO REGISTRADO', N'ACTIVIDAD', 4, CAST(N'2021-03-02T19:52:24.173' AS DateTime), 1)
GO
INSERT [dbo].[reg_empresas] ([idempresa], [sc_empresa], [ruc], [nmatricula], [empresa], [razonsocial], [idreplegal], [nomreprelegal], [direccion], [telefono], [email1], [email2], [actividad], [estado], [fregistro], [tipoempresa]) VALUES (CAST(279 AS Numeric(18, 0)), CAST(53 AS Numeric(18, 0)), N'1308126646008', N'1111', N'RAZON SOCIAL', N'RAZON SOCIAL', N'130812646', N'NOMRBE DEL REPRESENTANTE', N'DIRECCION', N'0997475611', N'xxx@apm.gob.ec', N'NO REGISTRADO', N'ACTIVIDAD', 4, CAST(N'2021-03-02T19:58:31.640' AS DateTime), 1)
GO
INSERT [dbo].[reg_empresas] ([idempresa], [sc_empresa], [ruc], [nmatricula], [empresa], [razonsocial], [idreplegal], [nomreprelegal], [direccion], [telefono], [email1], [email2], [actividad], [estado], [fregistro], [tipoempresa]) VALUES (CAST(280 AS Numeric(18, 0)), CAST(54 AS Numeric(18, 0)), N'1308126646008', N'1111', N'RAZON SOCIAL', N'RAZON SOCIAL', N'130812646', N'NOMRBE DEL REPRESENTANTE', N'DIRECCION', N'0997475611', N'xxx@apm.gob.ec', N'NO REGISTRADO', N'ACTIVIDAD', 4, CAST(N'2021-03-02T20:06:32.670' AS DateTime), 1)
GO
INSERT [dbo].[reg_empresas] ([idempresa], [sc_empresa], [ruc], [nmatricula], [empresa], [razonsocial], [idreplegal], [nomreprelegal], [direccion], [telefono], [email1], [email2], [actividad], [estado], [fregistro], [tipoempresa]) VALUES (CAST(281 AS Numeric(18, 0)), CAST(55 AS Numeric(18, 0)), N'1308126646009', N'1111', N'RAZON SOCIAL', N'RAZON SOCIAL', N'130812646', N'NOMRBE DEL REPRESENTANTE', N'DIRECCION', N'0997475611', N'xxx@apm.gob.ec', N'NO REGISTRADO', N'ACTIVIDAD', 4, CAST(N'2021-03-02T20:13:23.900' AS DateTime), 1)
GO
INSERT [dbo].[reg_empresas] ([idempresa], [sc_empresa], [ruc], [nmatricula], [empresa], [razonsocial], [idreplegal], [nomreprelegal], [direccion], [telefono], [email1], [email2], [actividad], [estado], [fregistro], [tipoempresa]) VALUES (CAST(282 AS Numeric(18, 0)), CAST(56 AS Numeric(18, 0)), N'1308126646010', N'1111', N'RAZON SOCIAL', N'RAZON SOCIAL', N'130812646', N'NOMRBE DEL REPRESENTANTE', N'DIRECCION', N'0997475611', N'xxx@apm.gob.ec', N'NO REGISTRADO', N'ACTIVIDAD', 4, CAST(N'2021-03-02T20:14:08.087' AS DateTime), 1)
GO
INSERT [dbo].[reg_empresas] ([idempresa], [sc_empresa], [ruc], [nmatricula], [empresa], [razonsocial], [idreplegal], [nomreprelegal], [direccion], [telefono], [email1], [email2], [actividad], [estado], [fregistro], [tipoempresa]) VALUES (CAST(283 AS Numeric(18, 0)), CAST(57 AS Numeric(18, 0)), N'1308126646011', N'1111', N'RAZON SOCIAL', N'RAZON SOCIAL', N'130812646', N'NOMRBE DEL REPRESENTANTE', N'DIRECCION', N'0997475611', N'xxx@apm.gob.ec', N'NO REGISTRADO', N'ACTIVIDAD', 4, CAST(N'2021-03-02T20:23:05.830' AS DateTime), 1)
GO
INSERT [dbo].[reg_empresas] ([idempresa], [sc_empresa], [ruc], [nmatricula], [empresa], [razonsocial], [idreplegal], [nomreprelegal], [direccion], [telefono], [email1], [email2], [actividad], [estado], [fregistro], [tipoempresa]) VALUES (CAST(284 AS Numeric(18, 0)), CAST(58 AS Numeric(18, 0)), N'1308126646012', N'1111', N'RAZON SOCIAL', N'RAZON SOCIAL', N'130812646', N'NOMRBE DEL REPRESENTANTE', N'DIRECCION', N'0997475611', N'xxx@apm.gob.ec', N'NO REGISTRADO', N'ACTIVIDAD', 4, CAST(N'2021-03-02T20:28:36.140' AS DateTime), 1)
GO
INSERT [dbo].[reg_empresas] ([idempresa], [sc_empresa], [ruc], [nmatricula], [empresa], [razonsocial], [idreplegal], [nomreprelegal], [direccion], [telefono], [email1], [email2], [actividad], [estado], [fregistro], [tipoempresa]) VALUES (CAST(285 AS Numeric(18, 0)), CAST(59 AS Numeric(18, 0)), N'1308126646013', N'1111', N'RAZON SOCIAL', N'RAZON SOCIAL', N'130812646', N'NOMRBE DEL REPRESENTANTE', N'DIRECCION', N'0997475611', N'xxx@apm.gob.ec', N'NO REGISTRADO', N'ACTIVIDAD', 4, CAST(N'2021-03-02T20:40:24.870' AS DateTime), 1)
GO
INSERT [dbo].[reg_empresas] ([idempresa], [sc_empresa], [ruc], [nmatricula], [empresa], [razonsocial], [idreplegal], [nomreprelegal], [direccion], [telefono], [email1], [email2], [actividad], [estado], [fregistro], [tipoempresa]) VALUES (CAST(286 AS Numeric(18, 0)), CAST(60 AS Numeric(18, 0)), N'1308126646013', N'1111', N'RAZON SOCIAL', N'RAZON SOCIAL', N'130812646', N'NOMRBE DEL REPRESENTANTE', N'DIRECCION', N'0997475611', N'xxx@apm.gob.ec', N'NO REGISTRADO', N'ACTIVIDAD', 4, CAST(N'2021-04-02T11:03:28.650' AS DateTime), 1)
GO
INSERT [dbo].[reg_empresas] ([idempresa], [sc_empresa], [ruc], [nmatricula], [empresa], [razonsocial], [idreplegal], [nomreprelegal], [direccion], [telefono], [email1], [email2], [actividad], [estado], [fregistro], [tipoempresa]) VALUES (CAST(287 AS Numeric(18, 0)), CAST(61 AS Numeric(18, 0)), N'1308126646014', N'1111', N'RAZON SOCIAL', N'RAZON SOCIAL', N'130812646', N'NOMRBE DEL REPRESENTANTE', N'DIRECCION', N'0997475611', N'xxx@apm.gob.ec', N'NO REGISTRADO', N'ACTIVIDAD', 4, CAST(N'2021-04-02T11:12:37.850' AS DateTime), 1)
GO
INSERT [dbo].[reg_empresas] ([idempresa], [sc_empresa], [ruc], [nmatricula], [empresa], [razonsocial], [idreplegal], [nomreprelegal], [direccion], [telefono], [email1], [email2], [actividad], [estado], [fregistro], [tipoempresa]) VALUES (CAST(288 AS Numeric(18, 0)), CAST(62 AS Numeric(18, 0)), N'1308126646015', N'1111', N'RAZON SOCIAL', N'RAZON SOCIAL', N'130812646', N'NOMRBE DEL REPRESENTANTE', N'DIRECCION', N'0997475611', N'xxx@apm.gob.ec', N'NO REGISTRADO', N'ACTIVIDAD', 4, CAST(N'2021-04-02T16:33:53.413' AS DateTime), 1)
GO
INSERT [dbo].[reg_empresas] ([idempresa], [sc_empresa], [ruc], [nmatricula], [empresa], [razonsocial], [idreplegal], [nomreprelegal], [direccion], [telefono], [email1], [email2], [actividad], [estado], [fregistro], [tipoempresa]) VALUES (CAST(289 AS Numeric(18, 0)), CAST(63 AS Numeric(18, 0)), N'1308126646016', N'1111', N'RAZON SOCIAL', N'RAZON SOCIAL', N'130812646', N'NOMRBE DEL REPRESENTANTE', N'DIRECCION', N'0997475611', N'xxx@apm.gob.ec', N'NO REGISTRADO', N'ACTIVIDAD', 4, CAST(N'2021-04-02T16:45:07.560' AS DateTime), 1)
GO
INSERT [dbo].[reg_empresas] ([idempresa], [sc_empresa], [ruc], [nmatricula], [empresa], [razonsocial], [idreplegal], [nomreprelegal], [direccion], [telefono], [email1], [email2], [actividad], [estado], [fregistro], [tipoempresa]) VALUES (CAST(290 AS Numeric(18, 0)), CAST(64 AS Numeric(18, 0)), N'1308126646017', N'1111', N'RAZON SOCIAL', N'RAZON SOCIAL', N'130812646', N'NOMRBE DEL REPRESENTANTE', N'DIRECCION', N'0997475611', N'xxx@apm.gob.ec', N'NO REGISTRADO', N'ACTIVIDAD', 4, CAST(N'2021-06-02T19:08:36.123' AS DateTime), 1)
GO
INSERT [dbo].[reg_empresas] ([idempresa], [sc_empresa], [ruc], [nmatricula], [empresa], [razonsocial], [idreplegal], [nomreprelegal], [direccion], [telefono], [email1], [email2], [actividad], [estado], [fregistro], [tipoempresa]) VALUES (CAST(291 AS Numeric(18, 0)), CAST(65 AS Numeric(18, 0)), N'1308126646017', N'1111', N'RAZON SOCIAL', N'RAZON SOCIAL', N'130812646', N'NOMRBE DEL REPRESENTANTE', N'DIRECCION', N'0997475611', N'xxx@apm.gob.ec', N'NO REGISTRADO', N'ACTIVIDAD', 4, CAST(N'2021-06-02T19:11:19.093' AS DateTime), 1)
GO
INSERT [dbo].[reg_empresas] ([idempresa], [sc_empresa], [ruc], [nmatricula], [empresa], [razonsocial], [idreplegal], [nomreprelegal], [direccion], [telefono], [email1], [email2], [actividad], [estado], [fregistro], [tipoempresa]) VALUES (CAST(292 AS Numeric(18, 0)), CAST(66 AS Numeric(18, 0)), N'1308126646018', N'1111', N'RAZON SOCIAL', N'RAZON SOCIAL', N'130812646', N'NOMRBE DEL REPRESENTANTE', N'DIRECCION', N'0997475611', N'xxx@apm.gob.ec', N'NO REGISTRADO', N'ACTIVIDAD', 4, CAST(N'2021-06-02T19:14:22.137' AS DateTime), 1)
GO
INSERT [dbo].[reg_empresas] ([idempresa], [sc_empresa], [ruc], [nmatricula], [empresa], [razonsocial], [idreplegal], [nomreprelegal], [direccion], [telefono], [email1], [email2], [actividad], [estado], [fregistro], [tipoempresa]) VALUES (CAST(293 AS Numeric(18, 0)), CAST(67 AS Numeric(18, 0)), N'130812664600z', N'1111111111111aa', N'RAZON SOCIAL', N'RAZON SOCIAL', N'1308126646', N'NOMRBE DEL REPRESENTANTE', N'DIRECCION', N'0997475611', N'xxx@apm.gob.ec', N'NO REGISTRADO', N'ACTIVIDAD', 0, CAST(N'2021-06-02T19:22:12.813' AS DateTime), 3)
GO
INSERT [dbo].[reg_empresas] ([idempresa], [sc_empresa], [ruc], [nmatricula], [empresa], [razonsocial], [idreplegal], [nomreprelegal], [direccion], [telefono], [email1], [email2], [actividad], [estado], [fregistro], [tipoempresa]) VALUES (CAST(294 AS Numeric(18, 0)), CAST(68 AS Numeric(18, 0)), N'1308126646zde', N'0000000000z', N'RAZON SOCIAL', N'RAZON SOCIAL', N'1308126646', N'REPRESENTANTE LEGAL', N'DIRECCION', N'0997475611', N'xxx@apm.gob.ec', N'NO REGISTRADO', N'AMBIENTE DE DESARROLLO', 4, CAST(N'2021-05-03T10:13:21.380' AS DateTime), 1)
GO
INSERT [dbo].[reg_empresas] ([idempresa], [sc_empresa], [ruc], [nmatricula], [empresa], [razonsocial], [idreplegal], [nomreprelegal], [direccion], [telefono], [email1], [email2], [actividad], [estado], [fregistro], [tipoempresa]) VALUES (CAST(295 AS Numeric(18, 0)), CAST(69 AS Numeric(18, 0)), N'13081266460dd', N'0000000000z', N'RAZON SOCIAL', N'RAZON SOCIAL', N'1308126646', N'REPRESENTANTE LEGAL', N'DIRECCION', N'0997475611', N'xxx@apm.gob.ec', N'NO REGISTRADO', N'AMBIENTE DE DESARROLLO', 4, CAST(N'2021-05-03T10:15:39.713' AS DateTime), 1)
GO
INSERT [dbo].[reg_empresas] ([idempresa], [sc_empresa], [ruc], [nmatricula], [empresa], [razonsocial], [idreplegal], [nomreprelegal], [direccion], [telefono], [email1], [email2], [actividad], [estado], [fregistro], [tipoempresa]) VALUES (CAST(296 AS Numeric(18, 0)), CAST(70 AS Numeric(18, 0)), N'130812664600f', N'0000000000z', N'RAZON SOCIAL', N'RAZON SOCIAL', N'1308126646', N'REPRESENTANTE LEGAL', N'DIRECCION', N'0997475611', N'xxx@apm.gob.ec', N'NO REGISTRADO', N'AMBIENTE DE DESARROLLO', 4, CAST(N'2021-05-03T10:25:09.143' AS DateTime), 2)
GO
INSERT [dbo].[reg_empresas] ([idempresa], [sc_empresa], [ruc], [nmatricula], [empresa], [razonsocial], [idreplegal], [nomreprelegal], [direccion], [telefono], [email1], [email2], [actividad], [estado], [fregistro], [tipoempresa]) VALUES (CAST(297 AS Numeric(18, 0)), CAST(71 AS Numeric(18, 0)), N'130812664600k', N'0000000000z', N'RAZON SOCIAL', N'RAZON SOCIAL', N'1308126646', N'REPRESENTANTE LEGAL', N'DIRECCION', N'0997475611', N'xxx@apm.gob.ec', N'NO REGISTRADO', N'AMBIENTE DE DESARROLLO', 4, CAST(N'2021-05-03T10:39:45.443' AS DateTime), 2)
GO
INSERT [dbo].[reg_empresas] ([idempresa], [sc_empresa], [ruc], [nmatricula], [empresa], [razonsocial], [idreplegal], [nomreprelegal], [direccion], [telefono], [email1], [email2], [actividad], [estado], [fregistro], [tipoempresa]) VALUES (CAST(298 AS Numeric(18, 0)), CAST(72 AS Numeric(18, 0)), N'130812664600f', N'0000000000z', N'RAZON SOCIAL', N'RAZON SOCIAL', N'1308126646', N'REPRESENTANTE LEGAL', N'DIRECCION', N'0997475611', N'xxx@apm.gob.ec', N'NO REGISTRADO', N'AMBIENTE DE DESARROLLO', 4, CAST(N'2021-05-03T10:43:13.980' AS DateTime), 2)
GO
INSERT [dbo].[reg_empresas] ([idempresa], [sc_empresa], [ruc], [nmatricula], [empresa], [razonsocial], [idreplegal], [nomreprelegal], [direccion], [telefono], [email1], [email2], [actividad], [estado], [fregistro], [tipoempresa]) VALUES (CAST(299 AS Numeric(18, 0)), CAST(73 AS Numeric(18, 0)), N'130812664600h', N'0000000000z', N'RAZON SOCIAL', N'RAZON SOCIAL', N'1308126646', N'REPRESENTANTE LEGAL', N'DIRECCION', N'0997475611', N'xxx@apm.gob.ec', N'NO REGISTRADO', N'AMBIENTE DE DESARROLLO', 4, CAST(N'2021-05-03T10:44:06.437' AS DateTime), 2)
GO
INSERT [dbo].[reg_empresas] ([idempresa], [sc_empresa], [ruc], [nmatricula], [empresa], [razonsocial], [idreplegal], [nomreprelegal], [direccion], [telefono], [email1], [email2], [actividad], [estado], [fregistro], [tipoempresa]) VALUES (CAST(300 AS Numeric(18, 0)), CAST(74 AS Numeric(18, 0)), N'130812664600p', N'0000000000z', N'RAZON SOCIAL', N'RAZON SOCIAL', N'1308126646', N'REPRESENTANTE LEGAL', N'DIRECCION', N'0997475611', N'xxx@apm.gob.ec', N'NO REGISTRADO', N'AMBIENTE DE DESARROLLO', 4, CAST(N'2021-05-03T10:45:50.680' AS DateTime), 2)
GO
INSERT [dbo].[reg_empresas] ([idempresa], [sc_empresa], [ruc], [nmatricula], [empresa], [razonsocial], [idreplegal], [nomreprelegal], [direccion], [telefono], [email1], [email2], [actividad], [estado], [fregistro], [tipoempresa]) VALUES (CAST(301 AS Numeric(18, 0)), CAST(75 AS Numeric(18, 0)), N'130812664600i', N'0000000000z', N'RAZON SOCIAL', N'RAZON SOCIAL', N'1308126646', N'REPRESENTANTE LEGAL', N'DIRECCION', N'0997475611', N'xxx@apm.gob.ec', N'NO REGISTRADO', N'AMBIENTE DE DESARROLLO', 4, CAST(N'2021-05-03T10:48:33.317' AS DateTime), 2)
GO
INSERT [dbo].[reg_empresas] ([idempresa], [sc_empresa], [ruc], [nmatricula], [empresa], [razonsocial], [idreplegal], [nomreprelegal], [direccion], [telefono], [email1], [email2], [actividad], [estado], [fregistro], [tipoempresa]) VALUES (CAST(302 AS Numeric(18, 0)), CAST(76 AS Numeric(18, 0)), N'13081266460gc', N'0000000000z', N'RAZON SOCIAL', N'RAZON SOCIAL', N'1308126646', N'REPRESENTANTE LEGAL', N'DIRECCION', N'0997475611', N'xxx@apm.gob.ec', N'NO REGISTRADO', N'AMBIENTE DE DESARROLLO', 4, CAST(N'2021-05-03T10:57:59.993' AS DateTime), 2)
GO
INSERT [dbo].[reg_empresas] ([idempresa], [sc_empresa], [ruc], [nmatricula], [empresa], [razonsocial], [idreplegal], [nomreprelegal], [direccion], [telefono], [email1], [email2], [actividad], [estado], [fregistro], [tipoempresa]) VALUES (CAST(303 AS Numeric(18, 0)), CAST(77 AS Numeric(18, 0)), N'130812664600j', N'0000000000z', N'RAZON SOCIAL', N'RAZON SOCIAL', N'1308126646', N'REPRESENTANTE LEGAL', N'DIRECCION', N'0997475611', N'xxx@apm.gob.ec', N'NO REGISTRADO', N'AMBIENTE DE DESARROLLO', 4, CAST(N'2021-05-03T11:09:11.430' AS DateTime), 2)
GO
INSERT [dbo].[reg_empresas] ([idempresa], [sc_empresa], [ruc], [nmatricula], [empresa], [razonsocial], [idreplegal], [nomreprelegal], [direccion], [telefono], [email1], [email2], [actividad], [estado], [fregistro], [tipoempresa]) VALUES (CAST(304 AS Numeric(18, 0)), CAST(78 AS Numeric(18, 0)), N'130812664600l', N'0000000000z', N'RAZON SOCIAL', N'RAZON SOCIAL', N'1308126646', N'REPRESENTANTE LEGAL', N'DIRECCION', N'0997475611', N'xxx@apm.gob.ec', N'NO REGISTRADO', N'AMBIENTE DE DESARROLLO', 4, CAST(N'2021-05-03T11:13:13.983' AS DateTime), 2)
GO
INSERT [dbo].[reg_empresas] ([idempresa], [sc_empresa], [ruc], [nmatricula], [empresa], [razonsocial], [idreplegal], [nomreprelegal], [direccion], [telefono], [email1], [email2], [actividad], [estado], [fregistro], [tipoempresa]) VALUES (CAST(305 AS Numeric(18, 0)), CAST(79 AS Numeric(18, 0)), N'130812664600a', N'0000000000z', N'RAZON SOCIAL', N'RAZON SOCIAL', N'1308126646', N'REPRESENTANTE LEGAL', N'DIRECCION', N'0997475611', N'xxx@apm.gob.ec', N'NO REGISTRADO', N'AMBIENTE DE DESARROLLO', 4, CAST(N'2021-05-03T11:18:16.210' AS DateTime), 2)
GO
INSERT [dbo].[reg_empresas] ([idempresa], [sc_empresa], [ruc], [nmatricula], [empresa], [razonsocial], [idreplegal], [nomreprelegal], [direccion], [telefono], [email1], [email2], [actividad], [estado], [fregistro], [tipoempresa]) VALUES (CAST(306 AS Numeric(18, 0)), CAST(80 AS Numeric(18, 0)), N'130812664603', N'0000000000z', N'RAZON SOCIAL', N'RAZON SOCIAL', N'1308126646', N'REPRESENTANTE LEGAL', N'DIRECCION', N'0997475611', N'xxx@apm.gob.ec', N'NO REGISTRADO', N'AMBIENTE DE DESARROLLO', 4, CAST(N'2021-05-03T11:19:23.210' AS DateTime), 2)
GO
INSERT [dbo].[reg_empresas] ([idempresa], [sc_empresa], [ruc], [nmatricula], [empresa], [razonsocial], [idreplegal], [nomreprelegal], [direccion], [telefono], [email1], [email2], [actividad], [estado], [fregistro], [tipoempresa]) VALUES (CAST(307 AS Numeric(18, 0)), CAST(81 AS Numeric(18, 0)), N'1308126646003', N'0000000000z', N'RAZON SOCIAL', N'RAZON SOCIAL', N'1308126646', N'REPRESENTANTE LEGAL', N'DIRECCION', N'0997475611', N'xxx@apm.gob.ec', N'NO REGISTRADO', N'AMBIENTE DE DESARROLLO', 4, CAST(N'2021-05-03T11:22:26.590' AS DateTime), 2)
GO
INSERT [dbo].[reg_empresas] ([idempresa], [sc_empresa], [ruc], [nmatricula], [empresa], [razonsocial], [idreplegal], [nomreprelegal], [direccion], [telefono], [email1], [email2], [actividad], [estado], [fregistro], [tipoempresa]) VALUES (CAST(308 AS Numeric(18, 0)), CAST(82 AS Numeric(18, 0)), N'13081266460se', N'0000000000z', N'RAZON SOCIAL', N'RAZON SOCIAL', N'1308126646', N'REPRESENTANTE LEGAL', N'DIRECCION', N'0997475611', N'xxx@apm.gob.ec', N'NO REGISTRADO', N'AMBIENTE DE DESARROLLO', 4, CAST(N'2021-05-03T11:26:22.913' AS DateTime), 2)
GO
INSERT [dbo].[reg_empresas] ([idempresa], [sc_empresa], [ruc], [nmatricula], [empresa], [razonsocial], [idreplegal], [nomreprelegal], [direccion], [telefono], [email1], [email2], [actividad], [estado], [fregistro], [tipoempresa]) VALUES (CAST(309 AS Numeric(18, 0)), CAST(83 AS Numeric(18, 0)), N'130812664602', N'0000000000z', N'RAZON SOCIAL', N'RAZON SOCIAL', N'1308126646', N'REPRESENTANTE LEGAL', N'DIRECCION', N'0997475611', N'xxx@apm.gob.ec', N'NO REGISTRADO', N'AMBIENTE DE DESARROLLO', 4, CAST(N'2021-05-03T11:27:37.203' AS DateTime), 2)
GO
INSERT [dbo].[reg_empresas] ([idempresa], [sc_empresa], [ruc], [nmatricula], [empresa], [razonsocial], [idreplegal], [nomreprelegal], [direccion], [telefono], [email1], [email2], [actividad], [estado], [fregistro], [tipoempresa]) VALUES (CAST(310 AS Numeric(18, 0)), CAST(84 AS Numeric(18, 0)), N'1308126646005', N'0000000000z', N'RAZON SOCIAL', N'RAZON SOCIAL', N'1308126646', N'REPRESENTANTE LEGALcred', N'DIRECCION', N'0997475611', N'xxx@apm.gob.ec', N'NO REGISTRADO', N'AMBIENTE DE DESARROLLO', 0, CAST(N'2021-05-03T11:56:25.867' AS DateTime), 0)
GO
INSERT [dbo].[reg_empresas] ([idempresa], [sc_empresa], [ruc], [nmatricula], [empresa], [razonsocial], [idreplegal], [nomreprelegal], [direccion], [telefono], [email1], [email2], [actividad], [estado], [fregistro], [tipoempresa]) VALUES (CAST(10311 AS Numeric(18, 0)), CAST(86 AS Numeric(18, 0)), N'13081266460xs', N'1234567890', N'FRESH FISH DEL ECUADOR CÍA. LTDA.', N'FRESH FISH DEL ECUADOR CÍA. LTDA.', N'1308126646', N'FRESH FISH DEL ECUADOR CÍA. LTDA.', N'FRESH FISH DEL ECUADOR CÍA. LTDA.', N'0997475611', N'tcfernando1z@yahoo.es', N'NO REGISTRADO', N'FRESH FISH DEL ECUADOR CÍA. LTDA.', 4, CAST(N'2021-01-05T13:15:29.497' AS DateTime), 3)
GO
INSERT [dbo].[reg_empresas] ([idempresa], [sc_empresa], [ruc], [nmatricula], [empresa], [razonsocial], [idreplegal], [nomreprelegal], [direccion], [telefono], [email1], [email2], [actividad], [estado], [fregistro], [tipoempresa]) VALUES (CAST(20317 AS Numeric(18, 0)), CAST(90 AS Numeric(18, 0)), N'1310063803', N'9999999999', N'CEDEÑO HOLGUÍN CRISTHIAN JOSÉ', N'CEDEÑO HOLGUÍN CRISTHIAN JOSÉ', N'1310063803', N'CEDEÑO HOLGUÍN CRISTHIAN JOSÉ', N'DIRECCIÓN', N'0997475611', N'crcedeno@apm.gob.ec', N'NO REGISTRADO', N'ADMINISTRADOR', 14, CAST(N'2021-05-08T21:11:40.943' AS DateTime), 4)
GO
INSERT [dbo].[reg_empresas] ([idempresa], [sc_empresa], [ruc], [nmatricula], [empresa], [razonsocial], [idreplegal], [nomreprelegal], [direccion], [telefono], [email1], [email2], [actividad], [estado], [fregistro], [tipoempresa]) VALUES (CAST(20318 AS Numeric(18, 0)), CAST(91 AS Numeric(18, 0)), N'1307235257', N'9999999999', N'ZAMBRANO PANTA HOLGER GREGORIO', N'ZAMBRANO PANTA HOLGER GREGORIO', N'1307235257', N'ZAMBRANO PANTA HOLGER GREGORIO', N'VIA SAN ISIDRO KM. 23', N'0986392203', N'xxx@apm.gob.ec', N'NO REGISTRADO', N'cargador de hielo ', 14, CAST(N'2021-06-08T09:56:03.643' AS DateTime), 4)
GO
INSERT [dbo].[reg_empresas] ([idempresa], [sc_empresa], [ruc], [nmatricula], [empresa], [razonsocial], [idreplegal], [nomreprelegal], [direccion], [telefono], [email1], [email2], [actividad], [estado], [fregistro], [tipoempresa]) VALUES (CAST(20319 AS Numeric(18, 0)), CAST(92 AS Numeric(18, 0)), N'1308129376', N'9999999999', N'ZAMBRANO TORROMORENO MARIANIELLA DEL ROCIO', N'ZAMBRANO TORROMORENO MARIANIELLA DEL ROCIO', N'1308129376', N'ZAMBRANO TORROMORENO MARIANIELLA DEL ROCIO', N'CALLE 11 AV. 27 ', N'0958833298', N'notiene@xx.com', N'NO REGISTRADO', N'ESTIBADORES/SECTOR ARTESANAL', 14, CAST(N'2022-07-02T10:06:30.917' AS DateTime), 4)
GO
INSERT [dbo].[reg_empresas] ([idempresa], [sc_empresa], [ruc], [nmatricula], [empresa], [razonsocial], [idreplegal], [nomreprelegal], [direccion], [telefono], [email1], [email2], [actividad], [estado], [fregistro], [tipoempresa]) VALUES (CAST(40325 AS Numeric(18, 0)), CAST(112 AS Numeric(18, 0)), N'1309010062009', N'0000000000', N'NOMBRE DE LA RAZON SOCIAL', N'NOMBRE DE LA RAZON SOCIAL', N'1308126646', N'NOMBRE DE LA RAZON SOCIAL', N'DIRECCION NOMBRE DE LA RAZON SOCIAL', N'0997475611', N'tcfernand222oz@yahoo.es', N'NO REGISTRADO', N'NO REGISTRADA', 4, CAST(N'2024-06-05T10:14:32.670' AS DateTime), 1)
GO
INSERT [dbo].[reg_empresas] ([idempresa], [sc_empresa], [ruc], [nmatricula], [empresa], [razonsocial], [idreplegal], [nomreprelegal], [direccion], [telefono], [email1], [email2], [actividad], [estado], [fregistro], [tipoempresa]) VALUES (CAST(40326 AS Numeric(18, 0)), CAST(113 AS Numeric(18, 0)), N'1309010062009', N'0000000000', N'NOMBRE DE LA EMPRESA', N'NOMBRE DE LA EMPRESA', N'1308126646', N'NOMBRE DEL REPRESENTANTE LEGAL', N'DIRECCION DE LA EMPRESA 1309010062009', N'0997475611', N'tcfernand222oz@yahoo.es', N'NO REGISTRADO', N'NO REGISTRADA', 4, CAST(N'2024-06-05T10:25:36.850' AS DateTime), 1)
GO
INSERT [dbo].[reg_empresas] ([idempresa], [sc_empresa], [ruc], [nmatricula], [empresa], [razonsocial], [idreplegal], [nomreprelegal], [direccion], [telefono], [email1], [email2], [actividad], [estado], [fregistro], [tipoempresa]) VALUES (CAST(40327 AS Numeric(18, 0)), CAST(114 AS Numeric(18, 0)), N'1309010062009', N'000000000', N'FDFASDF DSF SDFSD FASDF SDFSD FDSF SDF S', N'fdfasdf dsf sdfsd fasdf sdfsd fdsf sdf s', N'1308126646', N'dsf asdf sdfsd fasd asdf sdfasd fsd f', N'ds fasd fsdaf sdf df sdfsdf sdf sdfds fdsf sdf sdf', N'0997475611', N'tcfernan222doz@yahoo.es', N'NO REGISTRADO', N'NO REGISTRADA', 4, CAST(N'2024-06-05T10:28:27.347' AS DateTime), 1)
GO
INSERT [dbo].[reg_empresas] ([idempresa], [sc_empresa], [ruc], [nmatricula], [empresa], [razonsocial], [idreplegal], [nomreprelegal], [direccion], [telefono], [email1], [email2], [actividad], [estado], [fregistro], [tipoempresa]) VALUES (CAST(40328 AS Numeric(18, 0)), CAST(115 AS Numeric(18, 0)), N'1309010062009', N'0000000000', N'D SAFSD FASDF SDFSD FSDF SDF SDF SDF', N'd safsd fasdf sdfsd fsdf sdf sdf sdf', N'1308126646', N'dsfasd fsdf sdfdsf sdfsd fsdfsdfsdf', N'df asdfdsfsdasdfsdfadsf sdf sdfsdfsd fsd', N'0997475611', N'tcfernan22doz@yahoo.es', N'NO REGISTRADO', N'NO REGISTRADA', 4, CAST(N'2024-06-05T11:10:20.650' AS DateTime), 1)
GO
INSERT [dbo].[reg_empresas] ([idempresa], [sc_empresa], [ruc], [nmatricula], [empresa], [razonsocial], [idreplegal], [nomreprelegal], [direccion], [telefono], [email1], [email2], [actividad], [estado], [fregistro], [tipoempresa]) VALUES (CAST(40329 AS Numeric(18, 0)), CAST(116 AS Numeric(18, 0)), N'1309010062009', N'0000000000', N'DSFASD FSDF SDFSDFSD', N'dsfasd fsdf sdfsdfsd', N'1308126646', N'dsf adf sdfsadfsdf sdfsd fasdf sdf sdfsdf sdfds', N' dafsd fsdfdsfdsf ds fadsf sdf', N'0997475611', N'tcfernand22oz@yahoo.es', N'NO REGISTRADO', N'NO REGISTRADA', 4, CAST(N'2024-06-05T11:13:48.190' AS DateTime), 7)
GO
INSERT [dbo].[reg_empresas] ([idempresa], [sc_empresa], [ruc], [nmatricula], [empresa], [razonsocial], [idreplegal], [nomreprelegal], [direccion], [telefono], [email1], [email2], [actividad], [estado], [fregistro], [tipoempresa]) VALUES (CAST(40330 AS Numeric(18, 0)), CAST(117 AS Numeric(18, 0)), N'1309010062009', N'000000000', N'SDFASD FSDF SDF SDF SDFSDF SDF SDF SDF DFASDF ', N'sdfasd fsdf sdf sdf sdfsdf sdf sdf sdf dfasdf ', N'1308126646', N'REPRESENTANATE LEGAL', N' fasdf asdf sdfsdf sdf sdf asdfsd fsdafsdfasdf', N'0997475611', N'tcferna223ndoz@yahoo.es', N'NO REGISTRADO', N'NO REGISTRADA', 4, CAST(N'2024-06-05T11:27:46.497' AS DateTime), 1)
GO
INSERT [dbo].[reg_empresas] ([idempresa], [sc_empresa], [ruc], [nmatricula], [empresa], [razonsocial], [idreplegal], [nomreprelegal], [direccion], [telefono], [email1], [email2], [actividad], [estado], [fregistro], [tipoempresa]) VALUES (CAST(40331 AS Numeric(18, 0)), CAST(118 AS Numeric(18, 0)), N'1309010062009', N'00000000000', N'NOMBRE DE RAZON SOCIAL PRUEBA EN PORTAIL', N'nombre de razon social prueba en portail', N'1308126646', N'nombre de presentante legal', N'dfasd sdf asdfsd  nombre de razon social', N'0997474611', N'tcfernand34oz@yahoo.es', N'NO REGISTRADO', N'NO REGISTRADA', 4, CAST(N'2024-06-05T11:52:46.447' AS DateTime), 1)
GO
INSERT [dbo].[reg_empresas] ([idempresa], [sc_empresa], [ruc], [nmatricula], [empresa], [razonsocial], [idreplegal], [nomreprelegal], [direccion], [telefono], [email1], [email2], [actividad], [estado], [fregistro], [tipoempresa]) VALUES (CAST(40332 AS Numeric(18, 0)), CAST(119 AS Numeric(18, 0)), N'1309010062009', N'0000000000', N'NOMBRE DE RAZON SOCIAL', N'nombre de razon social', N'1308126646', N'nombre de presentante legal', N'dfasd sdf asdfsd  nombre de razon social', N'0997475611', N'tcfernand3oz@yahoo.es', N'NO REGISTRADO', N'NO REGISTRADA', 4, CAST(N'2024-06-05T11:55:00.350' AS DateTime), 1)
GO
INSERT [dbo].[reg_empresas] ([idempresa], [sc_empresa], [ruc], [nmatricula], [empresa], [razonsocial], [idreplegal], [nomreprelegal], [direccion], [telefono], [email1], [email2], [actividad], [estado], [fregistro], [tipoempresa]) VALUES (CAST(40333 AS Numeric(18, 0)), CAST(120 AS Numeric(18, 0)), N'1309010062009', N'000000000', N'NOMBRE DE RAZON SOCIAL', N'nombre de razon social', N'1308126646', N'nombre de presentante legal', N'dfasd sdf asdfsd  nombre de razon social', N'0997467544', N'tcfernand32oz@yahoo.es', N'NO REGISTRADO', N'NO REGISTRADA', 4, CAST(N'2024-06-05T12:56:19.680' AS DateTime), 1)
GO
INSERT [dbo].[reg_empresas] ([idempresa], [sc_empresa], [ruc], [nmatricula], [empresa], [razonsocial], [idreplegal], [nomreprelegal], [direccion], [telefono], [email1], [email2], [actividad], [estado], [fregistro], [tipoempresa]) VALUES (CAST(40334 AS Numeric(18, 0)), CAST(121 AS Numeric(18, 0)), N'1309010062009', N'000000000', N'NOMBRE DE RAZON SOCIAL', N'nombre de razon social', N'1308126646', N'nombre de presentante legal', N'dfasd sdf asdfsd  nombre de razon social', N'0997475611', N'tcfernan32doz@yahoo.es', N'NO REGISTRADO', N'NO REGISTRADA', 4, CAST(N'2024-06-05T15:11:58.333' AS DateTime), 1)
GO
INSERT [dbo].[reg_empresas] ([idempresa], [sc_empresa], [ruc], [nmatricula], [empresa], [razonsocial], [idreplegal], [nomreprelegal], [direccion], [telefono], [email1], [email2], [actividad], [estado], [fregistro], [tipoempresa]) VALUES (CAST(40335 AS Numeric(18, 0)), CAST(122 AS Numeric(18, 0)), N'1309010062009', N'000000000', N'NOMBRE DE RAZON SOCIAL', N'nombre de razon social', N'1308126646', N'nombre de presentante legal', N'dfasd sdf asdfsd  nombre de razon social', N'0997475611', N'tcfernan23doz@yahoo.es', N'NO REGISTRADO', N'NO REGISTRADA', 4, CAST(N'2024-06-05T15:32:58.157' AS DateTime), 1)
GO
INSERT [dbo].[reg_empresas] ([idempresa], [sc_empresa], [ruc], [nmatricula], [empresa], [razonsocial], [idreplegal], [nomreprelegal], [direccion], [telefono], [email1], [email2], [actividad], [estado], [fregistro], [tipoempresa]) VALUES (CAST(40336 AS Numeric(18, 0)), CAST(123 AS Numeric(18, 0)), N'1309010062009', N'000000000', N'NOMBRE DE RAZON SOCIAL DE LA EMPRESA', N'NOMBRE DE RAZON SOCIAL DE LA EMPRESA', N'1308126646', N'REPRESENTANTE LEGAL', N'DIRECCON DE LA EMPREA', N'0997475611', N'tcfernan23doz@yahoo.es', N'NO REGISTRADO', N'NO REGISTRADA', 4, CAST(N'2024-06-05T17:30:12.573' AS DateTime), 1)
GO
INSERT [dbo].[reg_empresas] ([idempresa], [sc_empresa], [ruc], [nmatricula], [empresa], [razonsocial], [idreplegal], [nomreprelegal], [direccion], [telefono], [email1], [email2], [actividad], [estado], [fregistro], [tipoempresa]) VALUES (CAST(50337 AS Numeric(18, 0)), CAST(126 AS Numeric(18, 0)), N'1310820079001', N'0000000000', N'ENTIDADES PUBLICAS DEL ECUADOR', N'ENTIDADES PUBLICAS DEL ECUADOR', N'1308126646', N'representante ENTIDADES PUBLICAS DEL ECUADOR', N'DIRECCON ENTIDADES PUBLICAS DEL ECUADOR', N'0997475611', N'tcfernan232doz@yahoo.es', N'NO REGISTRADO', N'gestion y control', 4, CAST(N'2024-03-06T09:39:08.340' AS DateTime), 5)
GO
INSERT [dbo].[reg_empresas] ([idempresa], [sc_empresa], [ruc], [nmatricula], [empresa], [razonsocial], [idreplegal], [nomreprelegal], [direccion], [telefono], [email1], [email2], [actividad], [estado], [fregistro], [tipoempresa]) VALUES (CAST(50338 AS Numeric(18, 0)), CAST(127 AS Numeric(18, 0)), N'1309700167001', N'0000000000', N'DSFASDFSD FSDFD SASD FASDF SD FASDF SDFAS', N'dsfasdfsd fsdfd sasd fasdf sd fasdf sdfas', N'1308126646', N'dsfas dfs dfasdfsdafsadfasdfsdfsdafsdf', N' dfsasdf dsf sdfas sdfasdf dsf sfsd fasd', N'0997475611', N'tcferna232ndoz@yahoo.es', N'NO REGISTRADO', N'afsdfsdf sdfds fadsf sdf asdfsd fasd fasdf', 4, CAST(N'2024-10-06T14:38:57.850' AS DateTime), 1)
GO
INSERT [dbo].[reg_empresas] ([idempresa], [sc_empresa], [ruc], [nmatricula], [empresa], [razonsocial], [idreplegal], [nomreprelegal], [direccion], [telefono], [email1], [email2], [actividad], [estado], [fregistro], [tipoempresa]) VALUES (CAST(60339 AS Numeric(18, 0)), CAST(129 AS Numeric(18, 0)), N'1315550622001', N'0000000000', N'TERWTERTER WERTWERWERT ERTER', N'terwterter wertwerwert erter', N'1308126646', N' fgdgfdgdfg fgdf dfgdfg df gdfg dgdfgdf', N'ert werterwt wertwer twert ertewrtewrte', N'0997475611', N'tcfernandozfalso@yahoo.es', N'NO REGISTRADO', N'dsfjasdlñfjslkfjasdlfjsdlfjsdlfadfsdf jkjfsñldkfjñskljf ksld fjsldksdfsd dsfa sd dfsd f', 4, CAST(N'2024-08-07T12:58:34.143' AS DateTime), 1)
GO
INSERT [dbo].[reg_empresas] ([idempresa], [sc_empresa], [ruc], [nmatricula], [empresa], [razonsocial], [idreplegal], [nomreprelegal], [direccion], [telefono], [email1], [email2], [actividad], [estado], [fregistro], [tipoempresa]) VALUES (CAST(60358 AS Numeric(18, 0)), CAST(147 AS Numeric(18, 0)), N'1391935685091', N'324342423424', N'SDFASDF SDSD SDFSDF', N'sdfasdf sdsd sdfsdf', N'1308126646', N'sd asdf sdfsdfsdfasdfsd fsdf', N'sd sd fsdsd sdfsdfsdf', N'2342342342342342341', N'correo@apm.gob.ec', N'NO REGISTRADO', N'sd fsd sdfsd fsdf sdds fasd f', 4, CAST(N'2024-04-12T15:08:20.727' AS DateTime), 1)
GO
INSERT [dbo].[reg_empresas] ([idempresa], [sc_empresa], [ruc], [nmatricula], [empresa], [razonsocial], [idreplegal], [nomreprelegal], [direccion], [telefono], [email1], [email2], [actividad], [estado], [fregistro], [tipoempresa]) VALUES (CAST(60359 AS Numeric(18, 0)), CAST(148 AS Numeric(18, 0)), N'1391935685009', N'324342423424', N'SDFASD FSDFSDFSD FSDFSD FSDF SD', N'sdfasd fsdfsdfsd fsdfsd fsdf sd', N'1308126646', N'ds fasd fsdfsdf sdsd fsd', N'sd fsd fsdf sdf sdsd f', N'2342342342342342341', N'correo11@apm.gob.ec', N'NO REGISTRADO', N'sd fsd sdfsd fsdf sdds fasd f', 4, CAST(N'2024-05-12T11:08:39.323' AS DateTime), 1)
GO
INSERT [dbo].[reg_empresas] ([idempresa], [sc_empresa], [ruc], [nmatricula], [empresa], [razonsocial], [idreplegal], [nomreprelegal], [direccion], [telefono], [email1], [email2], [actividad], [estado], [fregistro], [tipoempresa]) VALUES (CAST(60360 AS Numeric(18, 0)), CAST(149 AS Numeric(18, 0)), N'1391935685008', N'324342423424', N'SDFASD FSDFSDFSD FSDFSD FSDF SD', N'sdfasd fsdfsdfsd fsdfsd fsdf sd', N'1308126646', N'ds fasd fsdfsdf sdsd fsd', N'sd fsd fsdf sdf sdsd f', N'2342342342342342341', N'correo@apm.gob.ec', N'NO REGISTRADO', N'sd fsd sdfsd fsdf sdds fasd f', 4, CAST(N'2024-05-12T11:14:05.203' AS DateTime), 1)
GO
INSERT [dbo].[reg_empresas] ([idempresa], [sc_empresa], [ruc], [nmatricula], [empresa], [razonsocial], [idreplegal], [nomreprelegal], [direccion], [telefono], [email1], [email2], [actividad], [estado], [fregistro], [tipoempresa]) VALUES (CAST(60361 AS Numeric(18, 0)), CAST(150 AS Numeric(18, 0)), N'1391935685007', N'324342423424', N'SDFASD FSDFSDFSD FSDFSD FSDF SD', N'sdfasd fsdfsdfsd fsdfsd fsdf sd', N'1308126646', N'ds fasd fsdfsdf sdsd fsd', N'sd fsd fsdf sdf sdsd f', N'2342342342342342341', N'correoa12@apm.gob.ec', N'NO REGISTRADO', N'sd fsd sdfsd fsdf sdds fasd f', 4, CAST(N'2024-05-12T11:45:30.980' AS DateTime), 1)
GO
INSERT [dbo].[reg_empresas] ([idempresa], [sc_empresa], [ruc], [nmatricula], [empresa], [razonsocial], [idreplegal], [nomreprelegal], [direccion], [telefono], [email1], [email2], [actividad], [estado], [fregistro], [tipoempresa]) VALUES (CAST(60362 AS Numeric(18, 0)), CAST(151 AS Numeric(18, 0)), N'1391935685000', N'324342423424', N'SDFASD FSDFSDFSD FSDFSD FSDF SD', N'sdfasd fsdfsdfsd fsdfsd fsdf sd', N'1308126646', N'ds fasd fsdfsdf sdsd fsd', N'sd fsd fsdf sdf sdsd f', N'2342342342342342341', N'correods42@apm.gob.ec', N'NO REGISTRADO', N'sd fsd sdfsd fsdf sdds fasd f', 4, CAST(N'2024-05-12T12:40:07.240' AS DateTime), 1)
GO
INSERT [dbo].[reg_empresas] ([idempresa], [sc_empresa], [ruc], [nmatricula], [empresa], [razonsocial], [idreplegal], [nomreprelegal], [direccion], [telefono], [email1], [email2], [actividad], [estado], [fregistro], [tipoempresa]) VALUES (CAST(60364 AS Numeric(18, 0)), CAST(152 AS Numeric(18, 0)), N'1391935685901', N'324342423424', N'SDFASD FSDFSDFSD FSDFSD FSDF SD', N'sdfasd fsdfsdfsd fsdfsd fsdf sd', N'1308126646', N'ds fasd fsdfsdf sdsd fsd', N'sd fsd fsdf sdf sdsd f', N'2342342342342342341', N'correo112dcf@apm.gob.ec', N'NO REGISTRADO', N'sd fsd sdfsd fsdf sdds fasd f', 4, CAST(N'2024-06-12T11:24:11.083' AS DateTime), 1)
GO
INSERT [dbo].[reg_empresas] ([idempresa], [sc_empresa], [ruc], [nmatricula], [empresa], [razonsocial], [idreplegal], [nomreprelegal], [direccion], [telefono], [email1], [email2], [actividad], [estado], [fregistro], [tipoempresa]) VALUES (CAST(60365 AS Numeric(18, 0)), CAST(153 AS Numeric(18, 0)), N'1391935685051', N'324342423424', N'SDFASD FSDFSDFSD FSDFSD FSDF SD', N'sdfasd fsdfsdfsd fsdfsd fsdf sd', N'1308126646', N'ds fasd fsdfsdf sdsd fsd', N'sd fsd fsdf sdf sdsd f', N'2342342342342342341', N'fwfsdaf@yahoo.es', N'NO REGISTRADO', N'sd fsd sdfsd fsdf sdds fasd f', 4, CAST(N'2024-06-12T12:10:32.390' AS DateTime), 1)
GO
INSERT [dbo].[reg_empresas] ([idempresa], [sc_empresa], [ruc], [nmatricula], [empresa], [razonsocial], [idreplegal], [nomreprelegal], [direccion], [telefono], [email1], [email2], [actividad], [estado], [fregistro], [tipoempresa]) VALUES (CAST(60366 AS Numeric(18, 0)), CAST(154 AS Numeric(18, 0)), N'1391935685061', N'324342423424', N'SDFASD FSDFSDFSD FSDFSD FSDF SD', N'sdfasd fsdfsdfsd fsdfsd fsdf sd', N'1308126646', N'ds fasd fsdfsdf sdsd fsd', N'sd fsd fsdf sdf sdsd f', N'2342342342342342341', N'fdfgd@sdfasd.net', N'NO REGISTRADO', N'sd fsd sdfsd fsdf sdds fasd f', 4, CAST(N'2024-06-12T12:19:03.050' AS DateTime), 1)
GO
INSERT [dbo].[reg_empresas] ([idempresa], [sc_empresa], [ruc], [nmatricula], [empresa], [razonsocial], [idreplegal], [nomreprelegal], [direccion], [telefono], [email1], [email2], [actividad], [estado], [fregistro], [tipoempresa]) VALUES (CAST(60367 AS Numeric(18, 0)), CAST(155 AS Numeric(18, 0)), N'1391935685031', N'324342423424', N'SDFASD FSDFSDFSD FSDFSD FSDF SD', N'sdfasd fsdfsdfsd fsdfsd fsdf sd', N'1308126646', N'ds fasd fsdfsdf sdsd fsd', N'sd fsd fsdf sdf sdsd f', N'2342342342342342341', N'correo@yahoo.es', N'NO REGISTRADO', N'sd fsd sdfsd fsdf sdds fasd f', 4, CAST(N'2024-06-12T12:27:31.160' AS DateTime), 1)
GO
INSERT [dbo].[reg_empresas] ([idempresa], [sc_empresa], [ruc], [nmatricula], [empresa], [razonsocial], [idreplegal], [nomreprelegal], [direccion], [telefono], [email1], [email2], [actividad], [estado], [fregistro], [tipoempresa]) VALUES (CAST(60368 AS Numeric(18, 0)), CAST(156 AS Numeric(18, 0)), N'1391935685002', N'324342423424', N'SDFASD FSDFSDFSD FSDFSD FSDF SD', N'sdfasd fsdfsdfsd fsdfsd fsdf sd', N'1308126646', N'ds fasd fsdfsdf sdsd fsd', N'sd fsd fsdf sdf sdsd f', N'2342342342342342341', N'correodsfasd@yahoo.es', N'NO REGISTRADO', N'sd fsd sdfsd fsdf sdds fasd f', 4, CAST(N'2024-07-12T08:44:25.320' AS DateTime), 1)
GO
INSERT [dbo].[reg_empresas] ([idempresa], [sc_empresa], [ruc], [nmatricula], [empresa], [razonsocial], [idreplegal], [nomreprelegal], [direccion], [telefono], [email1], [email2], [actividad], [estado], [fregistro], [tipoempresa]) VALUES (CAST(60369 AS Numeric(18, 0)), CAST(157 AS Numeric(18, 0)), N'1391935685003', N'23423434234234234', N'DSF ASDFAS DFDSFSDFSD FSDF SDFSFSDFSD', N'dsf asdfas dfdsfsdfsd fsdf sdfsfsdfsd', N'1308126646', N'd fasdsd fsdf sd sdfsdfsd fdsf sdfsdf sdfasd ', N'ds fsdafsdf sdfsdfsdf sdfsdfdsfsdf af', N'23232323232', N'3coreea@apm.gob.ec', N'NO REGISTRADO', N'dsadsf sdf sdafdsfasdfasdfsdfsdfasdfsdfs', 4, CAST(N'2024-07-12T09:28:25.760' AS DateTime), 1)
GO
INSERT [dbo].[reg_empresas] ([idempresa], [sc_empresa], [ruc], [nmatricula], [empresa], [razonsocial], [idreplegal], [nomreprelegal], [direccion], [telefono], [email1], [email2], [actividad], [estado], [fregistro], [tipoempresa]) VALUES (CAST(60370 AS Numeric(18, 0)), CAST(158 AS Numeric(18, 0)), N'1391935685101', N'342423423432432', N'FDSA SDFSD FDSF SDFDSFSDFSDFSDFSDFSDF SFSDF', N'fdsa sdfsd fdsf sdfdsfsdfsdfsdfsdfsdf sfsdf', N'1308126646', N'ds fsdf dsfdsafsad fsdfsdf asfsda', N' fdsafsdfasdf sdfsdfsdfasdf sdf sdf sdfsdfasdf', N'24234343243424234', N'coreelec@apm.gob.ec', N'NO REGISTRADO', N'fdsfsdfasd sdf sdfasdfsdf dsfdsfsdfs dfdsf sdfsad fsadfsdfsdaf', 4, CAST(N'2024-07-12T09:44:01.750' AS DateTime), 1)
GO
INSERT [dbo].[reg_empresas] ([idempresa], [sc_empresa], [ruc], [nmatricula], [empresa], [razonsocial], [idreplegal], [nomreprelegal], [direccion], [telefono], [email1], [email2], [actividad], [estado], [fregistro], [tipoempresa]) VALUES (CAST(60371 AS Numeric(18, 0)), CAST(159 AS Numeric(18, 0)), N'1391935685401', N'13024124234', N'343241234423FASDFSD FSADFSDFSDAFAS', N'343241234423fasdfsd fsadfsdfsdafas', N'1308126646', N'fa fdsfdsf dasf fddfadsfds fdsfsfdsfsfdsfdsafdafdf', N' fasd fds fdsfsdfdsf dsfdsfdsfdssssssssdsfa', N'34234324324324', N'correoasdf@apm.gob.ec', N'NO REGISTRADO', N'fdasdf343242143fgfdgs dfgfd gfdgdfsgsdfgsdf gsfd', 4, CAST(N'2024-07-12T09:49:34.503' AS DateTime), 1)
GO
INSERT [dbo].[reg_empresas] ([idempresa], [sc_empresa], [ruc], [nmatricula], [empresa], [razonsocial], [idreplegal], [nomreprelegal], [direccion], [telefono], [email1], [email2], [actividad], [estado], [fregistro], [tipoempresa]) VALUES (CAST(60372 AS Numeric(18, 0)), CAST(160 AS Numeric(18, 0)), N'1391935685041', N'342342341234', N'DFASDFSD ASDF SD SDSDFSDF SDF SDF', N'dfasdfsd asdf sd sdsdfsdf sdf sdf', N'1308126646', N'ds fsd fsdf sdfsd sdfsd fsd sdf ', N'sd fsd fsdfsd sdf sd f', N'423412342342341', N'afsdsdfafsdfasd@yahoo.es', N'NO REGISTRADO', N'sdfasdf sdfsd fsd fsdfsd fsdf sdf sdfsd fsdf sdaf', 4, CAST(N'2024-07-12T10:08:45.493' AS DateTime), 1)
GO
INSERT [dbo].[reg_empresas] ([idempresa], [sc_empresa], [ruc], [nmatricula], [empresa], [razonsocial], [idreplegal], [nomreprelegal], [direccion], [telefono], [email1], [email2], [actividad], [estado], [fregistro], [tipoempresa]) VALUES (CAST(60373 AS Numeric(18, 0)), CAST(161 AS Numeric(18, 0)), N'1391935685701', N'1308126646', N'D SFDSF SDFSDF SDFSDFSDFSDFSD ', N'd sfdsf sdfsdf sdfsdfsdfsdfsd ', N'1308126646', N'd sfasdf dsfsdf sdfasfsdf sdfsadfasd', N' sdfsdf sdfsdfsdf sdfs', N'cvzxcvxcvzxcv@yah.es', N'fsdfsdfasd@yahoo.es', N'NO REGISTRADO', N'd fasdf sdfas fsdfsdfaa sdf fasd f', 4, CAST(N'2024-07-12T10:58:15.377' AS DateTime), 1)
GO
INSERT [dbo].[reg_empresas] ([idempresa], [sc_empresa], [ruc], [nmatricula], [empresa], [razonsocial], [idreplegal], [nomreprelegal], [direccion], [telefono], [email1], [email2], [actividad], [estado], [fregistro], [tipoempresa]) VALUES (CAST(60374 AS Numeric(18, 0)), CAST(162 AS Numeric(18, 0)), N'1391935685071', N'3424234234', N'F SADFASDFSDAFSDFSADFSDFSD FDSFSDF', N'f sadfasdfsdafsdfsadfsdfsd fdsfsdf', N'1308126646', N'd sfasdfasdfsdfadsfasdf', N'd sfasdfsdfsdfsdfsdfsdfsda', N'324123234123432432', N'dfsadfsdfsadf@yahoo.es', N'NO REGISTRADO', N' fasdfsd fdsf sdafsdf sdfsdfsdfsdf sdfadsfsd', 4, CAST(N'2024-07-12T11:02:43.103' AS DateTime), 1)
GO
INSERT [dbo].[reg_empresas] ([idempresa], [sc_empresa], [ruc], [nmatricula], [empresa], [razonsocial], [idreplegal], [nomreprelegal], [direccion], [telefono], [email1], [email2], [actividad], [estado], [fregistro], [tipoempresa]) VALUES (CAST(60375 AS Numeric(18, 0)), CAST(163 AS Numeric(18, 0)), N'1391935687001', N'234234123423412342', N'DFSDFASD DS FASDFSDSDFSDFSDFSDF SDF SDF SD', N'dfsdfasd ds fasdfsdsdfsdfsdfsdf sdf sdf sd', N'1308126646', N'sd fasdfsdf sdfsd fsd sdf sdafasd fsdafas ', N'sd fsd fsdfsdfsdf sdfsdf sd fsdfsdf sd', N'234234234234123', N'corree@apm.gob.ec', N'NO REGISTRADO', N'sdfas dfsdf sdafsd fsdf sdf sd', 4, CAST(N'2024-10-12T16:43:08.257' AS DateTime), 1)
GO
INSERT [dbo].[reg_empresas] ([idempresa], [sc_empresa], [ruc], [nmatricula], [empresa], [razonsocial], [idreplegal], [nomreprelegal], [direccion], [telefono], [email1], [email2], [actividad], [estado], [fregistro], [tipoempresa]) VALUES (CAST(60376 AS Numeric(18, 0)), CAST(164 AS Numeric(18, 0)), N'1391935687001', N'3123123123123', N'SD FSD SDFSDFSD SDF SDFSD A', N'sd fsd sdfsdfsd sdf sdfsd a', N'1308126646', N'sd fasdfsd asd fsd f', N'sd fasdf sdf sdafsd fsdfa sdf', N'4324123423423', N'coreadasdf@dsfa.net', N'NO REGISTRADO', N' sfasd fsdf sdfsdfasd fsdf dsfasdf sadfsd fsdf', 4, CAST(N'2024-10-12T16:46:11.940' AS DateTime), 1)
GO
INSERT [dbo].[reg_empresas] ([idempresa], [sc_empresa], [ruc], [nmatricula], [empresa], [razonsocial], [idreplegal], [nomreprelegal], [direccion], [telefono], [email1], [email2], [actividad], [estado], [fregistro], [tipoempresa]) VALUES (CAST(60377 AS Numeric(18, 0)), CAST(165 AS Numeric(18, 0)), N'1391935685031', N'3131231º3123', N'FSDSDFSDAF SD FSD FSDFSD FSD FASD', N'fsdsdfsdaf sd fsd fsdfsd fsd fasd', N'1308126646', N'sd fasd fsd fsdfsd fsdf', N'sd fsadfsdf sdfasd sd sdf', N'23412342342134', N'dsfsdfsfas@dsfasdf.net', N'NO REGISTRADO', N'dsfasdfsd sd fsdfsd fsd fsd', 4, CAST(N'2024-10-12T16:55:08.630' AS DateTime), 1)
GO
INSERT [dbo].[reg_empresas] ([idempresa], [sc_empresa], [ruc], [nmatricula], [empresa], [razonsocial], [idreplegal], [nomreprelegal], [direccion], [telefono], [email1], [email2], [actividad], [estado], [fregistro], [tipoempresa]) VALUES (CAST(70368 AS Numeric(18, 0)), CAST(166 AS Numeric(18, 0)), N'1391935685801', N'9999999999', N'FSDFSD FAS FASDFSD FSDFSD F', N'fsdfsd fas fasdfsd fsdfsd f', N'1308126646', N'dsaf sdfsd fasd f fasf sd fsdf asd', N'sd fsdf sdfs fs sf fas', N'999999999', N'coreadsfsdf@yahoo.es', N'NO REGISTRADO', N' fsd fsdf sdfs dsdfsdsdf sf sdf sdfsd fasd ', 4, CAST(N'2024-11-12T10:43:28.470' AS DateTime), 1)
GO
INSERT [dbo].[reg_empresas] ([idempresa], [sc_empresa], [ruc], [nmatricula], [empresa], [razonsocial], [idreplegal], [nomreprelegal], [direccion], [telefono], [email1], [email2], [actividad], [estado], [fregistro], [tipoempresa]) VALUES (CAST(70369 AS Numeric(18, 0)), CAST(167 AS Numeric(18, 0)), N'1391935685801', N'341234234234', N' SDFASD FSDFASD FSD SDFSDF SDAF', N' sdfasd fsdfasd fsd sdfsdf sdaf', N'1308126646', N' fasfs dfssd fsd fsd fasfsdf', N'sdfsdfsd fsd fasd fsfsd fsdfsdf sdfsdfsd', N'34123423', N'eqweqeqeq@dfaf.net', N'NO REGISTRADO', N' fasdsfsdfsdfsd fsdf sfsd fsdf sdfsd fsdf sdfs', 4, CAST(N'2024-11-12T11:57:34.210' AS DateTime), 1)
GO
INSERT [dbo].[reg_empresas] ([idempresa], [sc_empresa], [ruc], [nmatricula], [empresa], [razonsocial], [idreplegal], [nomreprelegal], [direccion], [telefono], [email1], [email2], [actividad], [estado], [fregistro], [tipoempresa]) VALUES (CAST(70370 AS Numeric(18, 0)), CAST(168 AS Numeric(18, 0)), N'1391935685061', N'32434234234', N'DFA SD SDF SDSD SDFSD SDF SDF', N'dfa sd sdf sdsd sdfsd sdf sdf', N'1308126646', N'sd fasdf sdf sdfsd fsdf sdaf', N'sd fasdf sd fsdfs sdf sdf', N'41342342342341234', N'sdfasfsdfas@dsfa.net', N'NO REGISTRADO', N' fasdfsdfs dfsd fsdfsd fsdf sdfsd asd asdf asfsd f', 4, CAST(N'2024-11-12T12:43:06.140' AS DateTime), 1)
GO
INSERT [dbo].[reg_empresas] ([idempresa], [sc_empresa], [ruc], [nmatricula], [empresa], [razonsocial], [idreplegal], [nomreprelegal], [direccion], [telefono], [email1], [email2], [actividad], [estado], [fregistro], [tipoempresa]) VALUES (CAST(70371 AS Numeric(18, 0)), CAST(169 AS Numeric(18, 0)), N'1391935685401', N'2344234234', N'SDFADFSDFSDFSDFSDFSDFASDFS', N'sdfadfsdfsdfsdfsdfsdfasdfs', N'1308126646', N'sdfasd fsdfsdfsd fsdf asdfsdfasd', N'sd fasdfsdfsdfsdfsdfasdf', N'242342314234234234', N'dfasfas342343@yah.es', N'NO REGISTRADO', N'af fsdf sdf asdfsadfsdfsadf asdfsdf asdfasd sd', 4, CAST(N'2024-11-12T14:18:26.810' AS DateTime), 1)
GO
INSERT [dbo].[reg_empresas] ([idempresa], [sc_empresa], [ruc], [nmatricula], [empresa], [razonsocial], [idreplegal], [nomreprelegal], [direccion], [telefono], [email1], [email2], [actividad], [estado], [fregistro], [tipoempresa]) VALUES (CAST(70373 AS Numeric(18, 0)), CAST(171 AS Numeric(18, 0)), N'1391935685701', N'999999999', N'EURURTTRYTCRURUTRTURTRTY  RTYRTURRTRU', N'eururttrytcrurutrturtrty  rtyrturrtru', N'1308126646', N' ftfhtfgjdrtrtrt rtyrtrtur tyrrtyryuruyruty', N'dfsfdsfds hgfgh  fdsgdfdfgdfg', N'7868678678678678', N'tyetyrtyty67@iii.net', N'NO REGISTRADO', N'yuyiuylyyyuyll yuyly  u yuyuyoyyu', 4, CAST(N'2024-11-12T15:04:58.920' AS DateTime), 1)
GO
INSERT [dbo].[reg_empresas] ([idempresa], [sc_empresa], [ruc], [nmatricula], [empresa], [razonsocial], [idreplegal], [nomreprelegal], [direccion], [telefono], [email1], [email2], [actividad], [estado], [fregistro], [tipoempresa]) VALUES (CAST(70374 AS Numeric(18, 0)), CAST(172 AS Numeric(18, 0)), N'1391935685801', N'999999999', N'DSSDFSSDFSDFSDF DFGDFGD FGFH', N'dssdfssdfsdfsdf dfgdfgd fgfh', N'1308126646', N'rereteyreeyre54444675   ghdfgfd fg hfg', N' jffgffg fg jffgfjgfjhfjh fghhhh', N'yyilukjkjkl@yy.net', N'tyrytryrtyry555@yr.es', N'NO REGISTRADO', N'erwerwt fdfgdfgdhf   trjrtryrtyr yyoyy', 4, CAST(N'2024-11-12T15:09:37.883' AS DateTime), 1)
GO
INSERT [dbo].[reg_empresas] ([idempresa], [sc_empresa], [ruc], [nmatricula], [empresa], [razonsocial], [idreplegal], [nomreprelegal], [direccion], [telefono], [email1], [email2], [actividad], [estado], [fregistro], [tipoempresa]) VALUES (CAST(70375 AS Numeric(18, 0)), CAST(173 AS Numeric(18, 0)), N'1391934685001', N'879770708707', N'FSDFSDFSDFAS FSDF ASDFS D FSF SDFSDFSDFSD ', N'fsdfsdfsdfas fsdf asdfs d fsf sdfsdfsdfsd ', N'1308126646', N'sdafdf sdfasd sd fsd fasdfasdfasd', N'sddasAS da dA ASD ASD  a a', N'234123412341234df', N'fsfsdfasdfa@sdfa.net', N'NO REGISTRADO', N'ds fasd fsdf sdfsd f sdfsd sd fsd ', 4, CAST(N'2024-11-12T15:36:02.883' AS DateTime), 1)
GO
INSERT [dbo].[reg_empresas] ([idempresa], [sc_empresa], [ruc], [nmatricula], [empresa], [razonsocial], [idreplegal], [nomreprelegal], [direccion], [telefono], [email1], [email2], [actividad], [estado], [fregistro], [tipoempresa]) VALUES (CAST(80375 AS Numeric(18, 0)), CAST(174 AS Numeric(18, 0)), N'0391935685601', N'99999999', N'SDAFSFSDFASDFASDFAS SD ASD FSD FA DS AFSDFSD FSD', N'sdafsfsdfasdfasdfas sd asd fsd fa ds afsdfsd fsd', N'1308126646', N'fsdas dfsd fsdf sdf asdfsd fsdf', N'ds fasdfsdfsdfda fsf sadfs ds dfasd fsdf asd', N'42342342342342341234', N'sfsdfsdfasd@yahoo.es', N'NO REGISTRADO', N'dfasdfsd fsdf sd fsd fsdfsd fsdfs fsd fsd fasd', 4, CAST(N'2024-12-12T16:25:47.033' AS DateTime), 1)
GO
SET IDENTITY_INSERT [dbo].[reg_empresas] OFF
GO
/* Reactivar y validar relaciones y disparadores despues de cargar los datos. */
ALTER TABLE [dbo].[bit_personas] WITH CHECK CHECK CONSTRAINT ALL;
ENABLE TRIGGER ALL ON [dbo].[bit_personas];
ALTER TABLE [dbo].[reg_empresas] WITH CHECK CHECK CONSTRAINT ALL;
ENABLE TRIGGER ALL ON [dbo].[reg_empresas];
GO
PRINT N'PortuariaExterna restaurada correctamente en SQL Server 2014.';
GO
