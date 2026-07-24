/*
  02_DATABASE_ESTRUCTURA.sql
  Estructura core + módulos base.
*/

USE PortuariaDemo;
GO
SET ANSI_NULLS ON;
SET QUOTED_IDENTIFIER ON;
SET NOCOUNT ON;
GO

IF OBJECT_ID(N'dbo.trg_visitas_sync_totales', N'TR') IS NOT NULL
    DROP TRIGGER dbo.trg_visitas_sync_totales;
GO
IF OBJECT_ID(N'dbo.trg_novedades_turno', N'TR') IS NOT NULL
    DROP TRIGGER dbo.trg_novedades_turno;
GO
IF OBJECT_ID(N'dbo.fn_turno_por_hora', N'FN') IS NOT NULL
    DROP FUNCTION dbo.fn_turno_por_hora;
GO
IF OBJECT_ID(N'dbo.apm_aplicar_passwords_demo', N'P') IS NOT NULL
    DROP PROCEDURE dbo.apm_aplicar_passwords_demo;
GO
IF OBJECT_ID(N'dbo.bit_visitas', N'U') IS NOT NULL DROP TABLE dbo.bit_visitas;
GO
IF OBJECT_ID(N'dbo.bit_movimientos', N'U') IS NOT NULL DROP TABLE dbo.bit_movimientos;
GO
IF OBJECT_ID(N'dbo.totales_visitas', N'U') IS NOT NULL DROP TABLE dbo.bit_totales_visitas;
GO
IF OBJECT_ID(N'dbo.bit_novedades', N'U') IS NOT NULL DROP TABLE dbo.bit_novedades;
GO
IF OBJECT_ID(N'dbo.bit_reporte_supervisor', N'U') IS NOT NULL DROP TABLE dbo.bit_reporte_supervisor;
GO
IF OBJECT_ID(N'dbo.bit_niveles_incidente', N'U') IS NOT NULL DROP TABLE dbo.bit_niveles_incidente;
GO
IF OBJECT_ID(N'dbo.bit_usuarios_apm', N'U') IS NOT NULL DROP TABLE dbo.bit_usuarios_apm;
GO
IF OBJECT_ID(N'dbo.bit_departamentos', N'U') IS NOT NULL DROP TABLE dbo.bit_departamentos;
GO
IF OBJECT_ID(N'dbo.bit_motivos', N'U') IS NOT NULL DROP TABLE dbo.bit_motivos;
GO
IF OBJECT_ID(N'dbo.bit_destinos', N'U') IS NOT NULL DROP TABLE dbo.bit_destinos;
GO
IF OBJECT_ID(N'dbo.bit_funcionarios', N'U') IS NOT NULL DROP TABLE dbo.bit_funcionarios;
GO
IF OBJECT_ID(N'dbo.bit_empresas', N'U') IS NOT NULL DROP TABLE dbo.bit_empresas;
GO
IF OBJECT_ID(N'dbo.bit_personas', N'U') IS NOT NULL DROP TABLE dbo.bit_personas;
GO

IF OBJECT_ID(N'dbo.bit_empresas', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.bit_empresas (
        id_empresa      INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
        empresa         NVARCHAR(150) NOT NULL,
        razonsocial     NVARCHAR(200) NULL,
        ruc             NVARCHAR(20) NULL,
        estado          BIT NOT NULL CONSTRAINT DF_empresas_estado DEFAULT (1)
    );
END
GO

IF OBJECT_ID(N'dbo.bit_funcionarios', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.bit_funcionarios (
        id_funcionario  INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
        nombre          NVARCHAR(150) NOT NULL,
        cargo           NVARCHAR(100) NOT NULL,
        cedula          NVARCHAR(20) NULL,
        estado          BIT NOT NULL CONSTRAINT DF_funcionarios_estado DEFAULT (1)
    );
END
GO

IF NOT EXISTS (
    SELECT 1 FROM sys.indexes
    WHERE object_id = OBJECT_ID(N'dbo.bit_funcionarios') AND name = N'IX_funcionarios_cedula'
)
BEGIN
    CREATE NONCLUSTERED INDEX IX_funcionarios_cedula
        ON dbo.bit_funcionarios(cedula)
        WHERE cedula IS NOT NULL;
END
GO

IF OBJECT_ID(N'dbo.bit_destinos', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.bit_destinos (
        id_destino      INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
        nombre          NVARCHAR(150) NOT NULL,
        estado          BIT NOT NULL CONSTRAINT DF_destinos_estado DEFAULT (1)
    );
END
GO

IF OBJECT_ID(N'dbo.bit_motivos', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.bit_motivos (
        id_motivo       INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
        descripcion     NVARCHAR(200) NOT NULL,
        estado          BIT NOT NULL CONSTRAINT DF_motivos_estado DEFAULT (1)
    );
END
GO

IF OBJECT_ID(N'dbo.bit_personas', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.bit_personas (
        id_persona       INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
        nidentificacion  NVARCHAR(20) NOT NULL,
        tidentif         NVARCHAR(20) NOT NULL CONSTRAINT DF_personas_tidentif DEFAULT (N'Cédula'),
        nombres          NVARCHAR(100) NOT NULL,
        apellidos        NVARCHAR(100) NOT NULL,
        estado           BIT NOT NULL CONSTRAINT DF_personas_estado DEFAULT (1)
    );
END
GO

IF NOT EXISTS (
    SELECT 1 FROM sys.indexes
    WHERE object_id = OBJECT_ID(N'dbo.bit_personas') AND name = N'UQ_personas_nidentificacion_real'
)
BEGIN
    CREATE UNIQUE NONCLUSTERED INDEX UQ_personas_nidentificacion_real
        ON dbo.bit_personas (nidentificacion)
        WHERE nidentificacion <> N'9999999999';
END
GO

IF OBJECT_ID(N'dbo.bit_niveles_incidente', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.bit_niveles_incidente (
        id_incidentes INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
        nivel        TINYINT NOT NULL,
        descripcion  NVARCHAR(50) NOT NULL,
        estado       BIT NOT NULL CONSTRAINT DF_niveles_incidente_estado DEFAULT (1),
        CONSTRAINT UQ_niveles_incidente_nivel UNIQUE (nivel),
        CONSTRAINT CK_niveles_incidente_nivel CHECK (nivel BETWEEN 1 AND 3)
    );
END
GO

IF OBJECT_ID(N'dbo.bit_reporte_supervisor', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.bit_reporte_supervisor (
        id_reporte       INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
        fecha_reporte    DATE NOT NULL,
        usuario_genera   NVARCHAR(150) NOT NULL,
        creado_en        DATETIME2(0) NOT NULL CONSTRAINT DF_reporte_supervisor_creado DEFAULT (SYSUTCDATETIME()),
        CONSTRAINT UQ_reporte_supervisor_fecha UNIQUE (fecha_reporte)
    );
END
GO

IF OBJECT_ID(N'dbo.bit_novedades', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.bit_novedades (
        idnovedad     INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
        id_reporte    INT NOT NULL,
        fecha         DATE NOT NULL,
        hora          TIME(0) NOT NULL,
        descripcion   NVARCHAR(2000) NOT NULL,
        estado        NVARCHAR(50) NOT NULL CONSTRAINT DF_novedades_estado DEFAULT (N'Registrada'),
        CONSTRAINT FK_novedades_reporte FOREIGN KEY (id_reporte)
            REFERENCES dbo.bit_reporte_supervisor(id_reporte) ON DELETE CASCADE
    );

    CREATE NONCLUSTERED INDEX IX_novedades_reporte_fecha ON dbo.bit_novedades(id_reporte, fecha);
END
GO

IF COL_LENGTH(N'dbo.bit_novedades', N'turno') IS NULL
BEGIN
    ALTER TABLE dbo.bit_novedades
    ADD turno NVARCHAR(10) NULL;
END
GO

PRINT '02_DATABASE_ESTRUCTURA.sql ejecutado correctamente.';
GO

