USE [Talento_Humano];
GO
SET NOCOUNT ON;
SET XACT_ABORT ON;
GO

BEGIN TRY
    BEGIN TRAN;

    IF COL_LENGTH('dbo.th_usuarios_sistema','mfa_habilitado') IS NULL
        ALTER TABLE dbo.th_usuarios_sistema ADD mfa_habilitado BIT NOT NULL CONSTRAINT DF_th_usuarios_mfa DEFAULT(0);
    IF COL_LENGTH('dbo.th_usuarios_sistema','mfa_secreto_enc') IS NULL
        ALTER TABLE dbo.th_usuarios_sistema ADD mfa_secreto_enc NVARCHAR(700) NULL;
    IF COL_LENGTH('dbo.th_usuarios_sistema','mfa_activado_en') IS NULL
        ALTER TABLE dbo.th_usuarios_sistema ADD mfa_activado_en DATETIME2(0) NULL;
    IF COL_LENGTH('dbo.th_usuarios_sistema','mfa_ultimo_paso') IS NULL
        ALTER TABLE dbo.th_usuarios_sistema ADD mfa_ultimo_paso BIGINT NULL;

    IF NOT EXISTS(SELECT 1 FROM sys.indexes WHERE object_id=OBJECT_ID('dbo.th_logs_auditoria') AND name='IX_th_logs_usuario_fecha')
        CREATE INDEX IX_th_logs_usuario_fecha ON dbo.th_logs_auditoria(usuario,fecha_hora DESC)
        INCLUDE(modulo,accion,direccion_ip,descripcion_detalle);

    EXEC(N'CREATE OR ALTER VIEW dbo.vw_th_resumen_auditoria_usuarios AS
        SELECT usuario,
               COUNT_BIG(*) total_eventos,
               MAX(fecha_hora) ultima_actividad,
               SUM(CASE WHEN accion LIKE ''%FALLIDO%'' OR accion IN (''ACCESO_DENEGADO'',''MFA_FALLIDO'') THEN 1 ELSE 0 END) alertas,
               SUM(CASE WHEN accion IN (''LOGIN'',''MFA_VALIDADO'') THEN 1 ELSE 0 END) accesos_exitosos,
               COUNT(DISTINCT modulo) modulos_utilizados
        FROM dbo.th_logs_auditoria
        GROUP BY usuario;');

    EXEC(N'CREATE OR ALTER PROCEDURE dbo.sp_th_crear_usuario_sistema
        @usuario NVARCHAR(50),
        @password_hash NVARCHAR(255),
        @correo NVARCHAR(150),
        @nombre NVARCHAR(150),
        @empleado_id INT = NULL,
        @rol_id INT
    AS
    BEGIN
        SET NOCOUNT ON;
        SET XACT_ABORT ON;
        IF NOT EXISTS(SELECT 1 FROM dbo.th_roles WHERE rol_id=@rol_id AND estado=1)
            THROW 51001, ''El rol indicado no existe o se encuentra inactivo.'', 1;
        IF EXISTS(SELECT 1 FROM dbo.th_usuarios_sistema WHERE usuario=@usuario)
            THROW 51002, ''El usuario ya se encuentra registrado.'', 1;
        IF @empleado_id IS NOT NULL AND EXISTS(SELECT 1 FROM dbo.th_usuarios_sistema WHERE empleado_id=@empleado_id)
            THROW 51003, ''El funcionario ya tiene una cuenta de acceso.'', 1;

        INSERT dbo.th_usuarios_sistema
            (usuario,password_hash,correo,nombre,empleado_id,rol_id,estado,fecha_creacion,debe_cambiar_clave)
        VALUES
            (@usuario,@password_hash,@correo,@nombre,@empleado_id,@rol_id,1,SYSDATETIME(),1);
        SELECT CONVERT(INT,SCOPE_IDENTITY()) usuario_id;
    END;');

    IF DATABASE_PRINCIPAL_ID('portal_app_role') IS NOT NULL
    BEGIN
        GRANT SELECT ON dbo.vw_th_resumen_auditoria_usuarios TO portal_app_role;

        -- Endurecimiento: sustituye permisos generales heredados por los campos
        -- estrictamente utilizados por autenticación y administración de cuentas.
        REVOKE INSERT ON OBJECT::dbo.th_usuarios_sistema FROM portal_app_role;
        REVOKE UPDATE ON OBJECT::dbo.th_usuarios_sistema FROM portal_app_role;
        GRANT EXECUTE ON dbo.sp_th_crear_usuario_sistema TO portal_app_role;
        GRANT UPDATE (password_hash, debe_cambiar_clave, token_version,
                      intentos_fallidos, bloqueado_hasta, ultimo_acceso, estado)
            ON OBJECT::dbo.th_usuarios_sistema TO portal_app_role;
        GRANT UPDATE (mfa_habilitado, mfa_secreto_enc, mfa_activado_en, mfa_ultimo_paso)
            ON OBJECT::dbo.th_usuarios_sistema TO portal_app_role;
    END;

    IF NOT EXISTS(SELECT 1 FROM dbo.th_schema_migrations WHERE version='2026.08.10')
        INSERT dbo.th_schema_migrations(version,nombre_archivo)
        VALUES('2026.08.10','migracion_seguridad_auditoria_20260810.sql');

    COMMIT;
END TRY
BEGIN CATCH
    IF @@TRANCOUNT>0 ROLLBACK;
    THROW;
END CATCH;
GO

SELECT version,nombre_archivo,fecha_aplicacion,aplicado_por
FROM dbo.th_schema_migrations
WHERE version='2026.08.10';
GO
