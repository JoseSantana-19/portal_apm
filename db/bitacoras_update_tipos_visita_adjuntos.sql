/* db/bitacoras_update_tipos_visita_adjuntos.sql
   Delta desde el export mas reciente de PortuariaDemo (portuaria_demoV4,
   2026-08-06/08): tabla de tipos de visita + adjuntos de novedades/rondas.
   Idempotente. */
USE [PortuariaDemo];
GO
SET ANSI_NULLS ON;
SET QUOTED_IDENTIFIER ON;
GO

IF OBJECT_ID(N'dbo.bit_tipos_visita', N'U') IS NULL
BEGIN
    CREATE TABLE [dbo].[bit_tipos_visita](
        [id_tipo_visita] [int] IDENTITY(1,1) NOT NULL,
        [nombre] [nvarchar](150) NOT NULL,
        [estado] [bit] NOT NULL,
        PRIMARY KEY CLUSTERED ([id_tipo_visita] ASC)
    );
    ALTER TABLE [dbo].[bit_tipos_visita] ADD CONSTRAINT [DF_tipos_visita_estado] DEFAULT ((1)) FOR [estado];
    INSERT [dbo].[bit_tipos_visita] ([nombre], [estado]) VALUES
        (N'USUARIO DEL PUERTO - GENERAL', 1),
        (N'AUTORIDADES', 1);
END;
GO

IF OBJECT_ID(N'dbo.bit_novedades_adjuntos', N'U') IS NULL
BEGIN
    CREATE TABLE [dbo].[bit_novedades_adjuntos](
        [id_adjunto] [int] IDENTITY(1,1) NOT NULL,
        [idnovedad] [int] NOT NULL,
        [nombre_original] [nvarchar](255) NOT NULL,
        [nombre_archivo] [nvarchar](255) NOT NULL,
        [ruta_relativa] [nvarchar](400) NOT NULL,
        [tipo_mime] [nvarchar](150) NULL,
        [tamano_bytes] [int] NULL,
        [subido_por] [nvarchar](150) NULL,
        [fecha_subida] [datetime] NOT NULL,
        PRIMARY KEY CLUSTERED ([id_adjunto] ASC)
    );
    CREATE NONCLUSTERED INDEX [IX_novedades_adjuntos_novedad] ON [dbo].[bit_novedades_adjuntos] ([idnovedad] ASC);
    ALTER TABLE [dbo].[bit_novedades_adjuntos] ADD CONSTRAINT [DF_novedades_adjuntos_fecha] DEFAULT (getdate()) FOR [fecha_subida];
    ALTER TABLE [dbo].[bit_novedades_adjuntos] WITH CHECK ADD CONSTRAINT [FK_novedades_adjuntos_novedad]
        FOREIGN KEY([idnovedad]) REFERENCES [dbo].[bit_novedades] ([idnovedad]) ON DELETE CASCADE;
    ALTER TABLE [dbo].[bit_novedades_adjuntos] CHECK CONSTRAINT [FK_novedades_adjuntos_novedad];
END;
GO

IF OBJECT_ID(N'dbo.bit_rondas_adjuntos', N'U') IS NULL
BEGIN
    CREATE TABLE [dbo].[bit_rondas_adjuntos](
        [id_adjunto] [int] IDENTITY(1,1) NOT NULL,
        [id_detalle] [int] NOT NULL,
        [nombre_original] [nvarchar](255) NOT NULL,
        [nombre_archivo] [nvarchar](255) NOT NULL,
        [ruta_relativa] [nvarchar](400) NOT NULL,
        [tipo_mime] [nvarchar](150) NULL,
        [tamano_bytes] [int] NULL,
        [subido_por] [nvarchar](150) NULL,
        [fecha_subida] [datetime] NOT NULL,
        PRIMARY KEY CLUSTERED ([id_adjunto] ASC)
    );
    CREATE NONCLUSTERED INDEX [IX_rondas_adjuntos_detalle] ON [dbo].[bit_rondas_adjuntos] ([id_detalle] ASC);
    ALTER TABLE [dbo].[bit_rondas_adjuntos] ADD CONSTRAINT [DF_rondas_adjuntos_fecha] DEFAULT (getdate()) FOR [fecha_subida];
    ALTER TABLE [dbo].[bit_rondas_adjuntos] WITH CHECK ADD CONSTRAINT [FK_rondas_adjuntos_detalle]
        FOREIGN KEY([id_detalle]) REFERENCES [dbo].[bit_rondas_detalles] ([id_detalle]) ON DELETE CASCADE;
    ALTER TABLE [dbo].[bit_rondas_adjuntos] CHECK CONSTRAINT [FK_rondas_adjuntos_detalle];
END;
GO

IF NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='bit_motivos' AND COLUMN_NAME='id_tipo_visita')
BEGIN
    ALTER TABLE [dbo].[bit_motivos] ADD [id_tipo_visita] [int] NULL;
END;
GO
IF NOT EXISTS (SELECT 1 FROM sys.foreign_keys WHERE name = 'FK_motivos_tipos_visita')
BEGIN
    ALTER TABLE [dbo].[bit_motivos] WITH CHECK ADD CONSTRAINT [FK_motivos_tipos_visita]
        FOREIGN KEY([id_tipo_visita]) REFERENCES [dbo].[bit_tipos_visita] ([id_tipo_visita]);
    ALTER TABLE [dbo].[bit_motivos] CHECK CONSTRAINT [FK_motivos_tipos_visita];
END;
GO

IF NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='bit_rondas_detalles' AND COLUMN_NAME='id_tipo_visita')
BEGIN
    ALTER TABLE [dbo].[bit_rondas_detalles] ADD [id_tipo_visita] [int] NULL;
END;
GO
IF NOT EXISTS (SELECT 1 FROM sys.foreign_keys WHERE name = 'FK_rondas_detalles_tipos_visita')
BEGIN
    ALTER TABLE [dbo].[bit_rondas_detalles] WITH CHECK ADD CONSTRAINT [FK_rondas_detalles_tipos_visita]
        FOREIGN KEY([id_tipo_visita]) REFERENCES [dbo].[bit_tipos_visita] ([id_tipo_visita]);
    ALTER TABLE [dbo].[bit_rondas_detalles] CHECK CONSTRAINT [FK_rondas_detalles_tipos_visita];
END;
GO

IF NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='bit_visitas' AND COLUMN_NAME='id_tipo_visita')
BEGIN
    ALTER TABLE [dbo].[bit_visitas] ADD [id_tipo_visita] [int] NULL;
END;
GO
IF NOT EXISTS (SELECT 1 FROM sys.foreign_keys WHERE name = 'FK_visitas_tipos_visita')
BEGIN
    ALTER TABLE [dbo].[bit_visitas] WITH CHECK ADD CONSTRAINT [FK_visitas_tipos_visita]
        FOREIGN KEY([id_tipo_visita]) REFERENCES [dbo].[bit_tipos_visita] ([id_tipo_visita]);
    ALTER TABLE [dbo].[bit_visitas] CHECK CONSTRAINT [FK_visitas_tipos_visita];
END;
GO
