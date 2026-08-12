/* db/permisos_centrales_fase2_bienes_inventario.sql
   Fase 2: Control de Bienes, lado nativo (BD inventario). Idempotente. */
USE [inventario];
GO
SET ANSI_NULLS ON;
SET QUOTED_IDENTIFIER ON;
GO

IF OBJECT_ID(N'dbo.inv_roles', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.inv_roles (
        id     INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
        nombre NVARCHAR(50) NOT NULL UNIQUE
    );
    INSERT dbo.inv_roles (nombre) VALUES ('Administrador'), ('Supervisor'), ('Operador'), ('Auditor');
END;
GO

IF OBJECT_ID(N'dbo.inv_permisos_rol', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.inv_permisos_rol (
        id                 INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
        rol_id             INT NOT NULL,
        route_key          NVARCHAR(255) NOT NULL,
        puede_visualizar   BIT NOT NULL DEFAULT 0,
        puede_crear        BIT NOT NULL DEFAULT 0,
        puede_editar       BIT NOT NULL DEFAULT 0,
        puede_eliminar     BIT NOT NULL DEFAULT 0,
        CONSTRAINT UQ_inv_permisos_rol UNIQUE (rol_id, route_key),
        CONSTRAINT FK_inv_permisos_rol_rol FOREIGN KEY (rol_id) REFERENCES dbo.inv_roles(id)
    );
END;
GO

IF NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='inv_permisos' AND COLUMN_NAME='nivel_crud')
BEGIN
    ALTER TABLE dbo.inv_permisos ADD nivel_crud TINYINT NOT NULL DEFAULT 1;
END;
GO

IF NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='inv_usuarios' AND COLUMN_NAME='rol_id')
BEGIN
    ALTER TABLE dbo.inv_usuarios ADD rol_id INT NULL;
    ALTER TABLE dbo.inv_usuarios ADD CONSTRAINT FK_inv_usuarios_rol FOREIGN KEY (rol_id) REFERENCES dbo.inv_roles(id);
END;
GO

IF EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='inv_usuarios' AND COLUMN_NAME='rol_id')
   AND EXISTS (SELECT 1 FROM dbo.inv_usuarios WHERE rol_id IS NULL)
BEGIN
    UPDATE u SET u.rol_id = r.id
    FROM dbo.inv_usuarios u
    JOIN dbo.inv_roles r ON r.nombre = u.rol;
END;
GO
