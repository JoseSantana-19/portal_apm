<?php
declare(strict_types=1);
require dirname(__DIR__,2) . '/helpers/polyfills_php74.php';

$root = dirname(__DIR__);
$failures = [];
$assert = static function (bool $condition, string $message) use (&$failures): void {
    if (!$condition) $failures[] = $message;
};

$auth = (string)file_get_contents($root . '/core/Auth.php');
$controller = (string)file_get_contents($root . '/core/AuthController.php');
$routes = (string)file_get_contents($root . '/index.php');
$migration = (string)file_get_contents($root . '/database/migracion_seguridad_auditoria_20260810.sql');
$auditController = (string)file_get_contents($root . '/modules/auditoria/Controladores/AuditoriaController.php');
$auditView = (string)file_get_contents($root . '/modules/auditoria/Vistas/reporte_auditoria.php');
$logsView = (string)file_get_contents($root . '/modules/auditoria/Vistas/logs.php');
$sessionJs = (string)file_get_contents($root . '/public/js/session_guard.js');
$sessionView = (string)file_get_contents($root . '/shared/footer_scripts.php');
$loginView = (string)file_get_contents($root . '/core/Vistas/login.php');

foreach (['mfaPending', 'verifyMfa', 'activateMfa', 'disableMfa', 'encryptMfaSecret', 'MFA_MAX_ATTEMPTS'] as $token) {
    $assert(str_contains($auth, $token), "Falta el control MFA {$token}.");
}
$assert(str_contains($auth, "'mfa'=>\$mfaVerified"), 'La sesión cifrada no conserva el estado del segundo factor.');
$assert(str_contains($controller, 'requireCsrf') && str_contains($controller, 'verifyMfa'), 'La verificación MFA no está protegida por CSRF.');
$assert(str_contains($routes, "login/verificar-mfa") && str_contains($routes, "cuenta/seguridad/activar-mfa"), 'Faltan rutas del segundo factor.');

foreach (['mfa_habilitado', 'mfa_secreto_enc', 'mfa_ultimo_paso', 'IX_th_logs_usuario_fecha', 'vw_th_resumen_auditoria_usuarios'] as $token) {
    $assert(str_contains($migration, $token), "La migración no contiene {$token}.");
}
$assert(!preg_match('/GRANT\s+UPDATE\s+ON\s+(?:OBJECT::)?dbo\.th_usuarios_sistema\s+TO/i', $migration), 'La migración concede UPDATE global sobre usuarios.');
$assert(str_contains($migration, 'REVOKE UPDATE ON OBJECT::dbo.th_usuarios_sistema'), 'La migración no retira el UPDATE general heredado.');
$assert(str_contains($migration, 'REVOKE INSERT ON OBJECT::dbo.th_usuarios_sistema'), 'La migración no retira el INSERT general heredado.');
$assert(str_contains($migration, 'GRANT UPDATE (mfa_habilitado, mfa_secreto_enc, mfa_activado_en, mfa_ultimo_paso)'), 'Los permisos MFA no están limitados por columna.');
$assert(str_contains($migration, 'sp_th_crear_usuario_sistema') && str_contains($migration, 'GRANT EXECUTE ON dbo.sp_th_crear_usuario_sistema'), 'La creación de usuarios no está encapsulada en un procedimiento autorizado.');
$adminModel = (string)file_get_contents($root . '/modules/admin/Modelos/AdminModel.php');
$assert(str_contains($adminModel, 'EXEC dbo.sp_th_crear_usuario_sistema'), 'Administración todavía inserta cuentas directamente.');

$assert(str_contains($routes, "auditoria/reportes/exportar"), 'Falta la exportación del reporte de auditoría.');
$assert(str_contains($auditController, 'reporteAuditoria') && str_contains($auditController, 'exportarReporteAuditoria'), 'Falta reporte general o por usuario.');
$assert(str_contains($auditController, 'usuario=:usuario'), 'El reporte no filtra por usuario con parámetro enlazado.');
$assert(str_contains($auditView, 'Auditoría por usuario') && str_contains($auditView, 'Reporte general de auditoría'), 'La vista no diferencia reporte general y detalle por usuario.');
$assert(str_contains($logsView, 'class="logs-filters"') && str_contains($logsView, 'class="logs-filter-row"'), 'Los filtros de logs no conservan la distribución compacta y responsiva.');
$assert(str_contains($logsView, 'name="desde"') && str_contains($logsView, 'name="hasta"') && str_contains($logsView, 'name="modulo"'), 'Los filtros de fecha o módulo dejaron de enviarse al servidor.');

$assert(str_contains($sessionView, 'sessionWarning') && str_contains($sessionView, 'sessionCountdown'), 'Falta el aviso accesible de inactividad.');
$assert(str_contains($sessionJs, '/sesion/renovar') && str_contains($sessionView, '/sesion/expirar'), 'El aviso no renueva o cierra la sesión realmente.');
$assert(str_contains($auth, 'SESSION_RENOVADA') && str_contains($auth, 'SESSION_EXPIRADA'), 'La renovación o expiración no queda auditada.');
$assert(str_contains($loginView, 'Acceder al sistema') && str_contains($loginView, 'data-password-toggle'), 'El acceso no conserva el nuevo orden o control de contraseña.');

require_once $root . '/core/Auth.php';
try {
    $reflection = new ReflectionClass(Auth::class);
    $encode = $reflection->getMethod('base32Encode');
    $decode = $reflection->getMethod('base32Decode');
    $totpAt = $reflection->getMethod('totpAt');
    $verify = $reflection->getMethod('verifyTotp');
    $raw = random_bytes(20);
    $secret = $encode->invoke(null, $raw);
    $assert(hash_equals($raw, (string)$decode->invoke(null, $secret)), 'Base32 no conserva el secreto MFA.');
    $step = (int)floor(time() / 30);
    $code = (string)$totpAt->invoke(null, $secret, $step);
    $matchedStep = null;
    $valid = $verify->invokeArgs(null, [$secret, $code, &$matchedStep]);
    $assert($valid === true && $matchedStep !== null && preg_match('/^\d{6}$/', $code) === 1, 'TOTP no genera o valida seis dígitos correctamente.');
    $invalidStep = null;
    $invalid = $verify->invokeArgs(null, [$secret, '0000000', &$invalidStep]);
    $assert($invalid === false, 'TOTP acepta un código con longitud inválida.');
} catch (Throwable $error) {
    $failures[] = 'No fue posible probar el algoritmo TOTP: ' . $error->getMessage();
}

if ($failures) {
    foreach ($failures as $failure) fwrite(STDERR, "[FAIL] {$failure}\n");
    exit(1);
}

echo "[OK] doble autenticación, sesiones y auditoría reforzadas\n";
