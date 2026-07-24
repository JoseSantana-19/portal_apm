/*
  01_DATABASE_BASE.sql
  Preparación de bases para instalación final.
  Ejecutar primero.
*/

IF DB_ID(N'PortuariaDemo') IS NULL
BEGIN
    CREATE DATABASE PortuariaDemo;
END
GO

IF DB_ID(N'PortuariaExterna') IS NULL
BEGIN
    CREATE DATABASE PortuariaExterna;
END
GO

USE PortuariaDemo;
GO
SET ANSI_NULLS ON;
SET QUOTED_IDENTIFIER ON;
SET NOCOUNT ON;
GO

USE PortuariaExterna;
GO
SET ANSI_NULLS ON;
SET QUOTED_IDENTIFIER ON;
SET NOCOUNT ON;
GO

IF OBJECT_ID(N'dbo.bit_personas', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.bit_personas (
        id_persona       INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
        nidentificacion  NVARCHAR(20) NOT NULL,
        tidentif         NVARCHAR(20) NOT NULL CONSTRAINT DF_ext_personas_tidentif DEFAULT (N'Cédula'),
        nombres          NVARCHAR(100) NOT NULL,
        apellidos        NVARCHAR(100) NOT NULL,
        estado           BIT NOT NULL CONSTRAINT DF_ext_personas_estado DEFAULT (1)
    );
END
GO

IF NOT EXISTS (
    SELECT 1 FROM sys.indexes
    WHERE object_id = OBJECT_ID(N'dbo.bit_personas') AND name = N'UQ_ext_personas_nidentificacion_real'
)
BEGIN
    CREATE UNIQUE NONCLUSTERED INDEX UQ_ext_personas_nidentificacion_real
        ON dbo.bit_personas (nidentificacion)
        WHERE nidentificacion <> N'9999999999';
END
GO

IF NOT EXISTS (SELECT 1 FROM dbo.bit_personas)
BEGIN
    INSERT INTO dbo.bit_personas (nidentificacion, tidentif, nombres, apellidos, estado) VALUES
        (N'1722002001', N'Cédula', N'Diana',      N'Fuentes Cedeño', 1),
        (N'1722002002', N'Cédula', N'Marco',      N'Vallejo Pincay', 1),
        (N'1722002003', N'Cédula', N'Ruth',       N'Orrala Macías', 1),
        (N'1722002004', N'Cédula', N'Héctor',     N'Zambrano Vera', 1),
        (N'1722002005', N'Cédula', N'Patricia',   N'Arcentales Ruiz', 1),
        (N'1722002006', N'Cédula', N'Felipe',     N'Morán Delgado', 1),
        (N'1722002007', N'Cédula', N'Silvia',     N'Baque Intriago', 1),
        (N'1722002008', N'Cédula', N'Óscar',      N'Cedeño Mendoza', 1),
        (N'1722002009', N'Cédula', N'Valeria',    N'Plúa Zambrano', 1),
        (N'1722002010', N'Cédula', N'Ramiro',     N'Vera Delgado', 1);
END
GO

PRINT '01_DATABASE_BASE.sql ejecutado correctamente.';
GO

