/* ============================================================================
   SSO DE MÓDULOS — Login centralizado del Portal APM para módulos integrados
   ============================================================================
   BD destino: PORTAL_APM (la BD central del integrador).

   Objetivo: que los OTROS módulos/aplicaciones (Portuaria, Inventario, TH,
   futuros sistemas satélite) puedan "logonear" contra el directorio central
   CORE_Usuarios SIN acceso directo a las tablas, únicamente a través de
   procedimientos almacenados con seguridad:

     · Autenticación de la APLICACIÓN llamante (código + api key, guardada
       solo como hash SHA2-256 — nunca en claro).
     · Lista opcional de IPs permitidas por aplicación y fecha de expiración.
     · Reutiliza el control de cuenta del portal: estado, intentos fallidos,
       bloqueo temporal (sp_Login / sp_RegistrarFalloLogin).
     · La contraseña del usuario NUNCA viaja ni se compara en SQL: el hash
       bcrypt se entrega SOLO a aplicaciones autenticadas, que verifican con
       password_verify() (mismo modelo two-step del sp_Login del portal).
     · Sesiones centralizadas en CORE_Sesiones con token aleatorio
       criptográfico (CRYPT_GEN_RANDOM) y expiración.
     · Todo queda auditado en CORE_Auditoria (origen = SSO:<app>).
     · Rol de BD `rol_sso_modulos` con EXECUTE únicamente sobre los SP SSO
       (mínimo privilegio: sin SELECT directo a ninguna tabla).

   Flujo de un módulo:
     1) sp_SSO_Login(app, key, usuario)      → estado de cuenta + hash bcrypt
     2) password_verify() en el módulo (PHP)
     3a) OK    → sp_SSO_ConfirmarLogin(...)  → token de sesión + expiración
     3b) FALLO → sp_SSO_RegistrarFallo(...)  → cuenta intentos / bloquea
     4) Peticiones siguientes: sp_SSO_ValidarToken(app, key, token)
     5) Salida: sp_SSO_Logout(app, key, token)

   Idempotente: se puede ejecutar múltiples veces.
   ============================================================================ */

USE PORTAL_APM;
GO
SET ANSI_NULLS ON;
SET QUOTED_IDENTIFIER ON;
GO

/* ── 1. Registro de aplicaciones autorizadas ─────────────────────────────── */
IF OBJECT_ID(N'dbo.CORE_Aplicaciones', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.CORE_Aplicaciones (
        id_app         INT            IDENTITY(1,1) PRIMARY KEY,
        codigo         NVARCHAR(30)   NOT NULL CONSTRAINT UQ_CoreApp_Codigo UNIQUE,
        nombre         NVARCHAR(100)  NOT NULL,
        api_key_hash   VARBINARY(32)  NOT NULL,               -- SHA2_256(api_key)
        ip_permitidas  NVARCHAR(500)  NULL,                   -- CSV opcional; NULL = cualquiera
        estado         TINYINT        NOT NULL CONSTRAINT DF_CoreApp_Estado DEFAULT 1
                                      CONSTRAINT CK_CoreApp_Estado CHECK (estado IN (0,1)),
        fecha_creacion DATETIME2      NOT NULL CONSTRAINT DF_CoreApp_Creacion DEFAULT GETDATE(),
        fecha_expira   DATETIME2      NULL,                   -- NULL = sin expiración
        creado_por     NVARCHAR(50)   NULL
    );
END
GO

/* ── 2. Función interna: valida credencial de aplicación ─────────────────── */
CREATE OR ALTER FUNCTION dbo.fn_SSO_AppValida
(
    @codigo_app NVARCHAR(30),
    @api_key    NVARCHAR(128),
    @ip         NVARCHAR(45)
)
RETURNS INT
AS
BEGIN
    DECLARE @id_app INT;

    SELECT @id_app = a.id_app
    FROM dbo.CORE_Aplicaciones a
    WHERE a.codigo = @codigo_app
      AND a.estado = 1
      AND (a.fecha_expira IS NULL OR a.fecha_expira > GETDATE())
      AND a.api_key_hash = HASHBYTES('SHA2_256', @api_key)
      AND (
            a.ip_permitidas IS NULL
            OR @ip IS NULL                                   -- llamada local confiable
            OR CHARINDEX(',' + @ip + ',', ',' + REPLACE(a.ip_permitidas, ' ', '') + ',') > 0
          );

    RETURN ISNULL(@id_app, 0);
END;
GO

/* ── 3. Alta / rotación de aplicaciones (solo administradores de BD) ─────── */
CREATE OR ALTER PROCEDURE dbo.sp_SSO_RegistrarApp
    @codigo        NVARCHAR(30),
    @nombre        NVARCHAR(100),
    @api_key       NVARCHAR(128),      -- entregar al módulo por canal seguro; aquí solo se guarda el hash
    @ip_permitidas NVARCHAR(500) = NULL,
    @fecha_expira  DATETIME2     = NULL,
    @creado_por    NVARCHAR(50)  = NULL
AS
BEGIN
    SET NOCOUNT ON;

    IF LEN(ISNULL(@api_key, N'')) < 32
    BEGIN
        RAISERROR(N'La api_key debe tener al menos 32 caracteres.', 16, 1);
        RETURN;
    END

    IF EXISTS (SELECT 1 FROM dbo.CORE_Aplicaciones WHERE codigo = @codigo)
        UPDATE dbo.CORE_Aplicaciones
           SET nombre        = @nombre,
               api_key_hash  = HASHBYTES('SHA2_256', @api_key),
               ip_permitidas = @ip_permitidas,
               fecha_expira  = @fecha_expira,
               estado        = 1
         WHERE codigo = @codigo;
    ELSE
        INSERT INTO dbo.CORE_Aplicaciones (codigo, nombre, api_key_hash, ip_permitidas, fecha_expira, creado_por)
        VALUES (@codigo, @nombre, HASHBYTES('SHA2_256', @api_key), @ip_permitidas, @fecha_expira, @creado_por);

    INSERT INTO dbo.CORE_Auditoria (id_usuario, modulo, operacion, ip_address, resultado, detalle)
    VALUES (NULL, 'CORE', 'SSO_APP_REG', NULL, 'EXITO', N'Alta/rotación de aplicación SSO: ' + @codigo);
END;
GO

/* ── 4. Paso 1: validar cuenta y obtener hash (solo apps autenticadas) ───── */
CREATE OR ALTER PROCEDURE dbo.sp_SSO_Login
    @codigo_app      NVARCHAR(30),
    @api_key         NVARCHAR(128),
    @nombre_usuario  NVARCHAR(50),
    @ip_address      NVARCHAR(45)  = NULL,
    @resultado       NVARCHAR(30)  OUTPUT,   -- APP_INVALIDA | NO_ENCONTRADO | INACTIVO | BLOQUEADO | OK
    @id_usuario      INT           OUTPUT,
    @hash_contrasena NVARCHAR(512) OUTPUT,   -- para password_verify() en el módulo
    @nivel_jerarquia TINYINT       OUTPUT,
    @req_cambio_pass BIT           OUTPUT,
    @nombre_completo NVARCHAR(150) OUTPUT,
    @id_departamento INT           OUTPUT
AS
BEGIN
    SET NOCOUNT ON;
    SET @resultado = NULL; SET @id_usuario = NULL; SET @hash_contrasena = NULL;
    SET @nivel_jerarquia = NULL; SET @req_cambio_pass = NULL;
    SET @nombre_completo = NULL; SET @id_departamento = NULL;

    IF dbo.fn_SSO_AppValida(@codigo_app, @api_key, @ip_address) = 0
    BEGIN
        SET @resultado = 'APP_INVALIDA';
        INSERT INTO dbo.CORE_Auditoria (modulo, operacion, ip_address, resultado, detalle)
        VALUES ('CORE', 'SSO_LOGIN', @ip_address, 'ERROR',
                N'Credencial de aplicación inválida (' + ISNULL(@codigo_app, N'?') + N')');
        RETURN;
    END

    DECLARE @tema NVARCHAR(20);
    EXEC dbo.sp_Login
        @nombre_usuario  = @nombre_usuario,
        @resultado       = @resultado       OUTPUT,
        @id_usuario      = @id_usuario      OUTPUT,
        @hash_contrasena = @hash_contrasena OUTPUT,
        @nivel_jerarquia = @nivel_jerarquia OUTPUT,
        @req_cambio_pass = @req_cambio_pass OUTPUT,
        @nombre_completo = @nombre_completo OUTPUT,
        @tema_preferido  = @tema            OUTPUT,
        @id_departamento = @id_departamento OUTPUT;

    -- El hash solo sale si la cuenta está operativa
    IF @resultado <> 'OK'
        SET @hash_contrasena = NULL;
END;
GO

/* ── 5. Paso 3a: emitir sesión central tras verificación bcrypt exitosa ──── */
CREATE OR ALTER PROCEDURE dbo.sp_SSO_ConfirmarLogin
    @codigo_app   NVARCHAR(30),
    @api_key      NVARCHAR(128),
    @id_usuario   INT,
    @ip_address   NVARCHAR(45)  = NULL,
    @user_agent   NVARCHAR(512) = NULL,
    @horas_vida   SMALLINT      = NULL,     -- NULL → CORE_Config SESSION_HOURS o 8
    @token        NVARCHAR(128) OUTPUT,
    @fecha_expira DATETIME2     OUTPUT
AS
BEGIN
    SET NOCOUNT ON;
    SET @token = NULL; SET @fecha_expira = NULL;

    IF dbo.fn_SSO_AppValida(@codigo_app, @api_key, @ip_address) = 0
    BEGIN
        INSERT INTO dbo.CORE_Auditoria (modulo, operacion, ip_address, resultado, detalle)
        VALUES ('CORE', 'SSO_CONFIRM', @ip_address, 'ERROR',
                N'Credencial de aplicación inválida (' + ISNULL(@codigo_app, N'?') + N')');
        RETURN;
    END

    IF NOT EXISTS (SELECT 1 FROM dbo.CORE_Usuarios WHERE id_usuario = @id_usuario AND estado = 1)
        RETURN;

    IF @horas_vida IS NULL
        SELECT @horas_vida = TRY_CAST(valor AS SMALLINT)
        FROM dbo.CORE_Config WHERE modulo = 'CORE' AND clave = 'SESSION_HOURS' AND estado = 1;
    IF @horas_vida IS NULL OR @horas_vida <= 0 SET @horas_vida = 8;

    SET @token        = CONVERT(NVARCHAR(128), CRYPT_GEN_RANDOM(64), 2); -- 128 hex chars
    SET @fecha_expira = DATEADD(HOUR, @horas_vida, GETDATE());

    INSERT INTO dbo.CORE_Sesiones (id_usuario, token, ip_address, user_agent, fecha_expira, estado)
    VALUES (@id_usuario, @token, @ip_address,
            LEFT(N'SSO:' + @codigo_app + N' ' + ISNULL(@user_agent, N''), 512),
            @fecha_expira, 1);

    UPDATE dbo.CORE_Usuarios
       SET intentos_fallidos = 0, fecha_bloqueo = NULL
     WHERE id_usuario = @id_usuario;

    INSERT INTO dbo.CORE_Auditoria (id_usuario, modulo, operacion, ip_address, resultado, detalle)
    VALUES (@id_usuario, 'CORE', 'LOGIN', @ip_address, 'EXITO', N'Login vía SSO:' + @codigo_app);
END;
GO

/* ── 6. Paso 3b: registrar fallo (cuenta intentos / bloquea) ─────────────── */
CREATE OR ALTER PROCEDURE dbo.sp_SSO_RegistrarFallo
    @codigo_app     NVARCHAR(30),
    @api_key        NVARCHAR(128),
    @nombre_usuario NVARCHAR(50),
    @ip_address     NVARCHAR(45) = NULL
AS
BEGIN
    SET NOCOUNT ON;

    IF dbo.fn_SSO_AppValida(@codigo_app, @api_key, @ip_address) = 0
        RETURN;

    EXEC dbo.sp_RegistrarFalloLogin @nombre_usuario = @nombre_usuario, @ip_address = @ip_address;
END;
GO

/* ── 7. Validación de token para peticiones posteriores (SSO real) ───────── */
CREATE OR ALTER PROCEDURE dbo.sp_SSO_ValidarToken
    @codigo_app      NVARCHAR(30),
    @api_key         NVARCHAR(128),
    @token           NVARCHAR(128),
    @ip_address      NVARCHAR(45)  = NULL,
    @resultado       NVARCHAR(30)  OUTPUT,  -- APP_INVALIDA | TOKEN_INVALIDO | EXPIRADO | OK
    @id_usuario      INT           OUTPUT,
    @nombre_usuario  NVARCHAR(50)  OUTPUT,
    @nombre_completo NVARCHAR(150) OUTPUT,
    @nivel_jerarquia TINYINT       OUTPUT,
    @id_departamento INT           OUTPUT
AS
BEGIN
    SET NOCOUNT ON;
    SET @resultado = NULL; SET @id_usuario = NULL; SET @nombre_usuario = NULL;
    SET @nombre_completo = NULL; SET @nivel_jerarquia = NULL; SET @id_departamento = NULL;

    IF dbo.fn_SSO_AppValida(@codigo_app, @api_key, @ip_address) = 0
    BEGIN
        SET @resultado = 'APP_INVALIDA';
        RETURN;
    END

    DECLARE @expira DATETIME2, @estadoSes TINYINT;

    SELECT @id_usuario = s.id_usuario, @expira = s.fecha_expira, @estadoSes = s.estado
    FROM dbo.CORE_Sesiones s
    WHERE s.token = @token;

    IF @id_usuario IS NULL OR @estadoSes = 0
    BEGIN
        SET @resultado = 'TOKEN_INVALIDO'; SET @id_usuario = NULL; RETURN;
    END

    IF @expira <= GETDATE()
    BEGIN
        UPDATE dbo.CORE_Sesiones SET estado = 0, fecha_revocacion = GETDATE() WHERE token = @token;
        SET @resultado = 'EXPIRADO'; SET @id_usuario = NULL; RETURN;
    END

    SELECT @nombre_usuario  = u.nombre_usuario,
           @nombre_completo = u.nombre_completo,
           @nivel_jerarquia = u.nivel_jerarquia,
           @id_departamento = u.id_departamento
    FROM dbo.CORE_Usuarios u
    WHERE u.id_usuario = @id_usuario AND u.estado = 1;

    IF @nombre_usuario IS NULL
    BEGIN
        SET @resultado = 'TOKEN_INVALIDO'; SET @id_usuario = NULL; RETURN;
    END

    UPDATE dbo.CORE_Sesiones SET fecha_ultima_actividad = GETDATE() WHERE token = @token;
    SET @resultado = 'OK';
END;
GO

/* ── 8. Cierre de sesión desde un módulo ─────────────────────────────────── */
CREATE OR ALTER PROCEDURE dbo.sp_SSO_Logout
    @codigo_app NVARCHAR(30),
    @api_key    NVARCHAR(128),
    @token      NVARCHAR(128),
    @ip_address NVARCHAR(45) = NULL
AS
BEGIN
    SET NOCOUNT ON;

    IF dbo.fn_SSO_AppValida(@codigo_app, @api_key, @ip_address) = 0
        RETURN;

    EXEC dbo.sp_Logout @token = @token, @ip_address = @ip_address;
END;
GO

/* ── 9. Mínimo privilegio: rol de BD para los módulos ────────────────────── */
IF NOT EXISTS (SELECT 1 FROM sys.database_principals WHERE name = N'rol_sso_modulos' AND type = 'R')
    CREATE ROLE rol_sso_modulos;
GO
GRANT EXECUTE ON OBJECT::dbo.sp_SSO_Login          TO rol_sso_modulos;
GRANT EXECUTE ON OBJECT::dbo.sp_SSO_ConfirmarLogin TO rol_sso_modulos;
GRANT EXECUTE ON OBJECT::dbo.sp_SSO_RegistrarFallo TO rol_sso_modulos;
GRANT EXECUTE ON OBJECT::dbo.sp_SSO_ValidarToken   TO rol_sso_modulos;
GRANT EXECUTE ON OBJECT::dbo.sp_SSO_Logout         TO rol_sso_modulos;
-- NOTA: sp_SSO_RegistrarApp queda fuera del rol a propósito (solo db_owner/admin).
GO

/* ── 10. Ejemplo de uso (comentado) ──────────────────────────────────────────
-- a) Crear un login SQL mínimo para un módulo y asociarlo al rol:
--    CREATE LOGIN mod_portuaria WITH PASSWORD = '<contraseña-fuerte>';
--    USE PORTAL_APM; CREATE USER mod_portuaria FOR LOGIN mod_portuaria;
--    ALTER ROLE rol_sso_modulos ADD MEMBER mod_portuaria;
--
-- b) Registrar la aplicación (una vez, como administrador):
--    EXEC dbo.sp_SSO_RegistrarApp
--         @codigo = N'PORTUARIA', @nombre = N'Bitácoras Portuarias',
--         @api_key = N'<clave-de-64-caracteres-generada-aleatoriamente>',
--         @ip_permitidas = NULL, @creado_por = N'admin';
--
-- c) Desde el módulo (paso 1):
--    DECLARE @res NVARCHAR(30), @id INT, @hash NVARCHAR(512), @niv TINYINT,
--            @req BIT, @nom NVARCHAR(150), @dep INT;
--    EXEC dbo.sp_SSO_Login N'PORTUARIA', N'<api-key>', N'admin', N'127.0.0.1',
--         @res OUTPUT, @id OUTPUT, @hash OUTPUT, @niv OUTPUT, @req OUTPUT,
--         @nom OUTPUT, @dep OUTPUT;
--    -- verificar bcrypt en la app; si OK:
--    DECLARE @tok NVARCHAR(128), @exp DATETIME2;
--    EXEC dbo.sp_SSO_ConfirmarLogin N'PORTUARIA', N'<api-key>', @id, N'127.0.0.1',
--         NULL, NULL, @tok OUTPUT, @exp OUTPUT;
--────────────────────────────────────────────────────────────────────────── */

PRINT 'sso_module_login.sql ejecutado correctamente.';
GO
