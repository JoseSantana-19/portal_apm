/* ============================================================================
   DEMO / REVISIÓN DEL SSO DE MÓDULOS — para ejecutar en SSMS (F5 completo)
   ============================================================================
   Requiere haber ejecutado antes db/sso_module_login.sql (ya está aplicado).

   Este script demuestra el ciclo COMPLETO que hará cualquier módulo para
   "logonear" contra el portal, y las seguridades en acción:

     PASO 1  Registrar una aplicación de prueba (api_key se guarda como hash)
     PASO 2  sp_SSO_Login    → estado de la cuenta + hash bcrypt
             (la verificación de la contraseña la hace el MÓDULO con
              password_verify() en PHP — bcrypt no se verifica en T-SQL)
     PASO 3  sp_SSO_ConfirmarLogin → emite token de sesión central
     PASO 4  sp_SSO_ValidarToken   → cualquier módulo valida el token (SSO)
     PASO 5  Seguridad: api_key INCORRECTA → APP_INVALIDA y SIN hash
     PASO 6  sp_SSO_Logout → revoca el token; volver a validar → TOKEN_INVALIDO
     PASO 7  Evidencia: auditoría y sesiones registradas
     PASO 8  Limpieza de los datos de la demo

   Es seguro re-ejecutarlo: usa la app 'DEMO_SSMS' y la limpia al final.
   ============================================================================ */

USE PORTAL_APM;
GO
SET NOCOUNT ON;

DECLARE @app     NVARCHAR(30)  = N'DEMO_SSMS';
DECLARE @api_key NVARCHAR(128) = N'ClaveDemoSSMS-32-caracteres-minimo-2026!';
DECLARE @usuario NVARCHAR(50)  = N'admin';
DECLARE @ip      NVARCHAR(45)  = N'127.0.0.1';

/* ── PASO 1: registrar la aplicación de prueba ─────────────────────────── */
EXEC dbo.sp_SSO_RegistrarApp
     @codigo = @app, @nombre = N'Demo revisión SSMS',
     @api_key = @api_key, @creado_por = N'revision';

SELECT 'PASO 1 — App registrada (clave guardada SOLO como hash SHA2-256):' AS paso;
SELECT codigo, nombre, api_key_hash, ip_permitidas, estado, fecha_creacion
FROM dbo.CORE_Aplicaciones WHERE codigo = @app;

/* ── PASO 2: login (app válida) → estado + hash bcrypt ─────────────────── */
DECLARE @res NVARCHAR(30), @id INT, @hash NVARCHAR(512), @niv TINYINT,
        @req BIT, @nom NVARCHAR(150), @dep INT;

EXEC dbo.sp_SSO_Login @app, @api_key, @usuario, @ip,
     @res OUTPUT, @id OUTPUT, @hash OUTPUT, @niv OUTPUT,
     @req OUTPUT, @nom OUTPUT, @dep OUTPUT;

SELECT 'PASO 2 — sp_SSO_Login con app VÁLIDA:' AS paso;
SELECT @res AS resultado, @id AS id_usuario, @nom AS nombre_completo,
       @niv AS nivel, @dep AS id_departamento,
       LEFT(@hash, 20) + N'…' AS hash_bcrypt_para_password_verify;

/* ── PASO 3: el módulo verificó bcrypt en PHP → emitir sesión ──────────── */
DECLARE @token NVARCHAR(128), @expira DATETIME2;
EXEC dbo.sp_SSO_ConfirmarLogin @app, @api_key, @id, @ip, N'SSMS-demo', NULL,
     @token OUTPUT, @expira OUTPUT;

SELECT 'PASO 3 — Token de sesión central emitido:' AS paso;
SELECT LEFT(@token, 24) + N'…' AS token, @expira AS fecha_expira;

/* ── PASO 4: otro módulo valida el token (SSO real) ────────────────────── */
DECLARE @vres NVARCHAR(30), @vid INT, @vnu NVARCHAR(50),
        @vnc NVARCHAR(150), @vniv TINYINT, @vdep INT;
EXEC dbo.sp_SSO_ValidarToken @app, @api_key, @token, @ip,
     @vres OUTPUT, @vid OUTPUT, @vnu OUTPUT, @vnc OUTPUT, @vniv OUTPUT, @vdep OUTPUT;

SELECT 'PASO 4 — sp_SSO_ValidarToken:' AS paso;
SELECT @vres AS resultado, @vid AS id_usuario, @vnu AS usuario,
       @vnc AS nombre_completo, @vniv AS nivel;

/* ── PASO 5: SEGURIDAD — api_key incorrecta ────────────────────────────── */
DECLARE @bres NVARCHAR(30), @bid INT, @bhash NVARCHAR(512), @bniv TINYINT,
        @breq BIT, @bnom NVARCHAR(150), @bdep INT;
EXEC dbo.sp_SSO_Login @app, N'clave-equivocada-no-registrada-123456', @usuario, @ip,
     @bres OUTPUT, @bid OUTPUT, @bhash OUTPUT, @bniv OUTPUT,
     @breq OUTPUT, @bnom OUTPUT, @bdep OUTPUT;

SELECT 'PASO 5 — App con api_key INCORRECTA (no filtra nada):' AS paso;
SELECT @bres AS resultado, @bhash AS hash_entregado /* debe ser NULL */;

/* ── PASO 6: logout → token revocado ───────────────────────────────────── */
EXEC dbo.sp_SSO_Logout @app, @api_key, @token, @ip;
EXEC dbo.sp_SSO_ValidarToken @app, @api_key, @token, @ip,
     @vres OUTPUT, @vid OUTPUT, @vnu OUTPUT, @vnc OUTPUT, @vniv OUTPUT, @vdep OUTPUT;

SELECT 'PASO 6 — Validar token DESPUÉS de logout:' AS paso;
SELECT @vres AS resultado /* debe ser TOKEN_INVALIDO */;

/* ── PASO 7: evidencia — auditoría y sesión registradas ────────────────── */
SELECT 'PASO 7a — Auditoría del ciclo (CORE_Auditoria):' AS paso;
SELECT TOP 6 fecha_registro, operacion, resultado, detalle, ip_address
FROM dbo.CORE_Auditoria
WHERE operacion IN ('LOGIN','LOGOUT','SSO_LOGIN','SSO_APP_REG','SSO_CONFIRM')
ORDER BY id_auditoria DESC;

SELECT 'PASO 7b — Sesión creada por el SSO (CORE_Sesiones):' AS paso;
SELECT TOP 3 id_sesion, id_usuario, LEFT(token,16)+N'…' AS token, user_agent,
       fecha_inicio, fecha_expira, estado, fecha_revocacion
FROM dbo.CORE_Sesiones
WHERE user_agent LIKE N'SSO:%'
ORDER BY id_sesion DESC;

/* ── PASO 8: limpieza de la demo ───────────────────────────────────────── */
DELETE FROM dbo.CORE_Sesiones WHERE user_agent LIKE N'SSO:DEMO_SSMS%';
DELETE FROM dbo.CORE_Aplicaciones WHERE codigo = @app;
UPDATE dbo.CORE_Usuarios SET intentos_fallidos = 0, fecha_bloqueo = NULL
WHERE nombre_usuario = @usuario;

SELECT 'PASO 8 — Demo limpiada. Objetos permanentes del SSO:' AS paso;
SELECT name AS objeto, type_desc
FROM sys.objects
WHERE name LIKE 'sp_SSO%' OR name = 'fn_SSO_AppValida' OR name = 'CORE_Aplicaciones'
ORDER BY type_desc, name;
GO
