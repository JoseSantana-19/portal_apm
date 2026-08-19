USE PORTAL_APM;
GO

-- MFA/TOTP real para el Portal Central. requiere_mfa y mfa_secreto ya
-- existian en el schema (columnas scaffold de una propuesta anterior nunca
-- implementada, ver INDICACIONES/PROPUESTA_SEGURIDAD_2FA_TOKENS_RESET.md) --
-- mfa_secreto era nvarchar(32), tamaño exacto de un secreto TOTP en texto
-- PLANO (base32 de 20 bytes) -- se amplia para guardar el secreto CIFRADO
-- (AES-256-GCM, mismo patron ya probado en apps/talento_humano/core/Auth.php)
-- en vez de texto plano. Se agregan mfa_activado_en (cuando se activo) y
-- mfa_ultimo_paso (anti-replay: no reutilizar el mismo codigo TOTP 2 veces).

IF COL_LENGTH('dbo.CORE_Usuarios', 'mfa_secreto') IS NOT NULL
BEGIN
    ALTER TABLE dbo.CORE_Usuarios ALTER COLUMN mfa_secreto NVARCHAR(255) NULL;
END
GO

IF COL_LENGTH('dbo.CORE_Usuarios', 'mfa_activado_en') IS NULL
BEGIN
    ALTER TABLE dbo.CORE_Usuarios ADD mfa_activado_en DATETIME2(0) NULL;
END
GO

IF COL_LENGTH('dbo.CORE_Usuarios', 'mfa_ultimo_paso') IS NULL
BEGIN
    ALTER TABLE dbo.CORE_Usuarios ADD mfa_ultimo_paso BIGINT NULL;
END
GO

-- Clave de cifrado del secreto MFA -- mismo patron ya usado para SSO_SECRET
-- (ModuleSecurity::getSecretKey(), CORE_Config), auto-generada la primera
-- vez que se necesita si todavia no existe (ver MfaHelper::encryptionKey()).
-- No se inserta un valor default acá a proposito: MfaHelper la genera con
-- random_bytes(32) real en el primer uso, nunca un secreto predecible.

PRINT 'OK: MFA (Portal Central) — columnas listas.';
