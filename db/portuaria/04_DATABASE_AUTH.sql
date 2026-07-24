/*
  04_DATABASE_AUTH.sql
  Seguridad/autenticación: departamentos, usuarios APM, relaciones e índices.
*/

USE PortuariaDemo;
GO
SET NOCOUNT ON;
GO

IF OBJECT_ID(N'dbo.bit_departamentos', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.bit_departamentos (
        iddepart      INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
        nom_departa   NVARCHAR(120) NOT NULL,
        nota          NVARCHAR(120) NULL,
        estado        BIT NOT NULL CONSTRAINT DF_departamentos_estado DEFAULT (1)
    );
END
GO

IF NOT EXISTS (
    SELECT 1 FROM sys.indexes
    WHERE object_id = OBJECT_ID(N'dbo.bit_departamentos') AND name = N'UQ_departamentos_nom_departa'
)
BEGIN
    CREATE UNIQUE NONCLUSTERED INDEX UQ_departamentos_nom_departa ON dbo.bit_departamentos(nom_departa);
END
GO

IF OBJECT_ID(N'dbo.bit_usuarios_apm', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.bit_usuarios_apm (
        id_usuario      INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
        cedula          NVARCHAR(20) NOT NULL,
        nombres         NVARCHAR(150) NOT NULL,
        id_departamento INT NOT NULL,
        estado          BIT NOT NULL CONSTRAINT DF_usuarios_apm_estado DEFAULT (1),
        password_hash   NVARCHAR(255) NULL,
        ultimo_ingreso  DATETIME2(0) NULL,
        creado_en       DATETIME2(0) NOT NULL CONSTRAINT DF_usuarios_apm_creado_en DEFAULT (SYSUTCDATETIME())
    );
END
GO

IF NOT EXISTS (
    SELECT 1 FROM sys.indexes
    WHERE object_id = OBJECT_ID(N'dbo.bit_usuarios_apm') AND name = N'UQ_usuarios_apm_cedula_departamento'
)
BEGIN
    CREATE UNIQUE NONCLUSTERED INDEX UQ_usuarios_apm_cedula_departamento ON dbo.bit_usuarios_apm(cedula, id_departamento);
END
GO

IF NOT EXISTS (
    SELECT 1 FROM sys.foreign_keys
    WHERE name = N'FK_usuarios_apm_departamento' AND parent_object_id = OBJECT_ID(N'dbo.bit_usuarios_apm')
)
BEGIN
    ALTER TABLE dbo.bit_usuarios_apm
        ADD CONSTRAINT FK_usuarios_apm_departamento
        FOREIGN KEY (id_departamento) REFERENCES dbo.bit_departamentos(iddepart);
END
GO

IF OBJECT_ID(N'dbo.bit_movimientos', N'U') IS NOT NULL
   AND OBJECT_ID(N'dbo.bit_usuarios_apm', N'U') IS NOT NULL
   AND NOT EXISTS (
       SELECT 1
       FROM sys.foreign_keys
       WHERE name = N'FK_movimientos_usuarios_apm'
         AND parent_object_id = OBJECT_ID(N'dbo.bit_movimientos')
   )
BEGIN
    ALTER TABLE dbo.bit_movimientos
    ADD CONSTRAINT FK_movimientos_usuarios_apm
    FOREIGN KEY (id_usuario) REFERENCES dbo.bit_usuarios_apm(id_usuario);
END
GO

IF OBJECT_ID(N'dbo.apm_aplicar_passwords_demo', N'P') IS NOT NULL
    DROP PROCEDURE dbo.apm_aplicar_passwords_demo;
GO
CREATE PROCEDURE dbo.apm_aplicar_passwords_demo
AS
BEGIN
    SET NOCOUNT ON;
    UPDATE u
    SET u.password_hash = CONVERT(VARCHAR(64), HASHBYTES('SHA2_256', N'Admin@123'), 2)
    FROM dbo.bit_usuarios_apm u
    WHERE u.cedula = N'1301234567';

    UPDATE u
    SET u.password_hash = CONVERT(VARCHAR(64), HASHBYTES('SHA2_256', N'Talento@123'), 2)
    FROM dbo.bit_usuarios_apm u
    WHERE u.cedula = N'1307654321';

    UPDATE u
    SET u.password_hash = CONVERT(VARCHAR(64), HASHBYTES('SHA2_256', N'Garita@123'), 2)
    FROM dbo.bit_usuarios_apm u
    WHERE u.cedula = N'1302223344';
END
GO

IF EXISTS (
    SELECT 1 FROM sys.indexes
    WHERE object_id = OBJECT_ID(N'dbo.bit_departamentos') AND name = N'UQ_departamentos_nombre'
)
    DROP INDEX UQ_departamentos_nombre ON dbo.bit_departamentos;
GO
IF EXISTS (
    SELECT 1 FROM sys.indexes
    WHERE object_id = OBJECT_ID(N'dbo.bit_departamentos') AND name = N'UQ_departamentos_DEP_NOMBRE'
)
    DROP INDEX UQ_departamentos_DEP_NOMBRE ON dbo.bit_departamentos;
GO

IF COL_LENGTH(N'dbo.bit_departamentos', N'nombre_departamento') IS NOT NULL
    EXEC sp_rename N'dbo.bit_departamentos.nombre_departamento', N'nom_departa', N'COLUMN';
GO
IF COL_LENGTH(N'dbo.bit_departamentos', N'DEP_NOMBRE') IS NOT NULL
    EXEC sp_rename N'dbo.bit_departamentos.DEP_NOMBRE', N'nom_departa', N'COLUMN';
GO
IF COL_LENGTH(N'dbo.bit_departamentos', N'cod_departamento') IS NOT NULL
    EXEC sp_rename N'dbo.bit_departamentos.cod_departamento', N'nota', N'COLUMN';
GO
IF COL_LENGTH(N'dbo.bit_departamentos', N'DEPARTAMEN') IS NOT NULL
    EXEC sp_rename N'dbo.bit_departamentos.DEPARTAMEN', N'nota', N'COLUMN';
GO
IF COL_LENGTH(N'dbo.bit_departamentos', N'SECCION') IS NOT NULL
    ALTER TABLE dbo.bit_departamentos DROP COLUMN SECCION;
GO
IF COL_LENGTH(N'dbo.bit_departamentos', N'seccion') IS NOT NULL
    ALTER TABLE dbo.bit_departamentos DROP COLUMN seccion;
GO
IF COL_LENGTH(N'dbo.bit_departamentos', N'ESTADO') IS NOT NULL
    EXEC sp_rename N'dbo.bit_departamentos.ESTADO', N'estado', N'COLUMN';
GO
IF COL_LENGTH(N'dbo.bit_departamentos', N'id_departamento') IS NOT NULL
    EXEC sp_rename N'dbo.bit_departamentos.id_departamento', N'iddepart', N'COLUMN';
GO

IF COL_LENGTH(N'dbo.bit_usuarios_apm', N'id_departamento') IS NULL
   AND COL_LENGTH(N'dbo.bit_usuarios_apm', N'iddepart') IS NOT NULL
BEGIN
    EXEC sp_rename N'dbo.bit_usuarios_apm.iddepart', N'id_departamento', N'COLUMN';
END
GO

IF COL_LENGTH(N'dbo.bit_usuarios_apm', N'id_departamento') IS NOT NULL
   AND COL_LENGTH(N'dbo.bit_usuarios_apm', N'iddepart') IS NOT NULL
BEGIN
    UPDATE u
    SET u.id_departamento = COALESCE(u.id_departamento, u.iddepart)
    FROM dbo.bit_usuarios_apm u;
    ALTER TABLE dbo.bit_usuarios_apm DROP COLUMN iddepart;
END
GO

IF COL_LENGTH(N'dbo.bit_usuarios_apm', N'id_departamento') IS NULL
BEGIN
    ALTER TABLE dbo.bit_usuarios_apm ADD id_departamento INT NULL;
END
GO

IF EXISTS (SELECT 1 FROM dbo.bit_usuarios_apm WHERE id_departamento IS NULL)
BEGIN
    DECLARE @firstDept INT;
    SELECT TOP 1 @firstDept = iddepart FROM dbo.bit_departamentos ORDER BY iddepart;
    UPDATE dbo.bit_usuarios_apm SET id_departamento = @firstDept WHERE id_departamento IS NULL;
END
GO

IF EXISTS (
    SELECT 1
    FROM sys.columns
    WHERE object_id = OBJECT_ID(N'dbo.bit_usuarios_apm')
      AND name = N'id_departamento'
      AND is_nullable = 1
)
BEGIN
    ALTER TABLE dbo.bit_usuarios_apm ALTER COLUMN id_departamento INT NOT NULL;
END
GO

IF EXISTS (
    SELECT 1
    FROM sys.foreign_keys
    WHERE name = N'FK_usuarios_apm_departamento'
      AND parent_object_id = OBJECT_ID(N'dbo.bit_usuarios_apm')
)
BEGIN
    ALTER TABLE dbo.bit_usuarios_apm DROP CONSTRAINT FK_usuarios_apm_departamento;
END
GO

ALTER TABLE dbo.bit_usuarios_apm
ADD CONSTRAINT FK_usuarios_apm_departamento
    FOREIGN KEY (id_departamento) REFERENCES dbo.bit_departamentos(iddepart);
GO

PRINT '04_DATABASE_AUTH.sql ejecutado correctamente.';
GO

