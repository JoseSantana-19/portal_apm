<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$failures = [];
$assert = static function (bool $condition, string $message) use (&$failures): void {
    if (!$condition) $failures[] = $message;
};

$sqlSmoke = (string)file_get_contents($root.'/tests/run_sql_smoke.php');
$databaseCore = (string)file_get_contents($root.'/core/Database.php');
$iisConfig = (string)file_get_contents($root.'/deployment/iis/configure-local-iis.ps1');
$iisTest = (string)file_get_contents($root.'/deployment/iis/test-local-iis.ps1');
$sqlCertificate = (string)file_get_contents($root.'/deployment/sql/replace-local-sql-certificate.ps1');
$webCertificate = (string)file_get_contents($root.'/deployment/iis/replace-local-web-certificate.ps1');
$repairTls = (string)file_get_contents($root.'/deployment/repair-local-tls.ps1');
$enableTls = (string)file_get_contents($root.'/deployment/enable-local-tls12.ps1');
$cleanupTls = (string)file_get_contents($root.'/deployment/cleanup-obsolete-local-certificates.ps1');
$databaseClosure = (string)file_get_contents($root.'/deployment/close-local-database.php');
$restoreDrill = (string)file_get_contents($root.'/deployment/restore-drill.php');
$migrationHistory = (string)file_get_contents($root.'/database/administracion/reconciliar_historial_migraciones.sql');
$backupAudit = (string)file_get_contents($root.'/database/administracion/verificar_respaldos.sql');
$rbacIntegrity = (string)file_get_contents($root.'/database/migracion_integridad_rbac_20260813.sql');
$preflight = (string)file_get_contents($root.'/scripts/preflight.php');

$assert(str_contains($sqlSmoke, "Driver=%s"), 'La prueba SQL no usa el driver configurado por la aplicacion.');
$assert(str_contains($databaseCore, 'Driver=$driver'), 'La conexión principal no selecciona explícitamente el driver ODBC configurado.');
$assert(str_contains($iisConfig, '[Parameter(Mandatory = $true)]') && !preg_match("/CertificateThumbprint\s*=\s*'[A-F0-9]{40}'/i", $iisConfig), 'IIS conserva una huella de certificado fija.');
$assert(str_contains($iisTest, 'LOCAL_CSS=200') && str_contains($iisTest, '/public/css/login.css'), 'La prueba IIS no detecta recursos CSS ausentes.');
foreach ([$sqlCertificate, $webCertificate] as $script) {
    $assert(str_contains($script, 'GetRSAPrivateKey'), 'Un reemplazo de certificado no valida la clave privada real.');
    $assert(str_contains($script, '(Get-Date).AddYears(3)'), 'Un reemplazo de certificado conserva una fecha de vencimiento fija.');
}
$assert(str_contains($repairTls, 'ConfirmRepair'), 'La reparación TLS no exige confirmación explícita.');
$assert(str_contains($enableTls, 'tls-registry-before-') && str_contains($enableTls, 'ConfirmChange'), 'La activación TLS no respalda el registro o no exige confirmación.');
$assert(str_contains($cleanupTls, 'ActiveSqlThumbprint') && str_contains($cleanupTls, 'ActiveWebThumbprint') && str_contains($cleanupTls, 'ConfirmCleanup'), 'La limpieza de certificados no valida huellas activas y confirmación.');
$assert(str_contains($databaseClosure, 'PORTAL_DBA_PASSWORD') && !str_contains($databaseClosure, '123456'), 'El cierre DBA no usa credenciales temporales o contiene una clave fija.');
$assert(
    str_contains($restoreDrill, '--confirm-target')
    && str_contains($restoreDrill, 'Talento_Humano_RestoreDrill_')
    && str_contains($restoreDrill, 'DBCC CHECKDB')
    && str_contains($restoreDrill, 'CLEANUP_VERIFIED=')
    && !str_contains($restoreDrill, '123456'),
    'El simulacro de restauracion no protege el destino, no valida integridad o contiene una clave fija.'
);
$assert(str_contains($migrationHistory, 'checksum_sha256') && str_contains($migrationHistory, 'firma_valida'), 'La reconciliacion de migraciones no valida objetos y checksums.');
$assert(str_contains($backupAudit, 'has_backup_checksums') && str_contains($backupAudit, 'sysjobhistory'), 'La verificacion de respaldos no comprueba checksums o ejecuciones de SQL Agent.');
$assert(str_contains($rbacIntegrity, 'UX_th_modulos_codigo') && str_contains($rbacIntegrity, 'CONSOLIDAR_MODULOS'), 'La migracion RBAC no garantiza codigos unicos o auditoria.');
$assert(str_contains($preflight, "Config::smtp()") && str_contains($preflight, "Configuración SMTP"), 'El preflight no valida la configuración SMTP.');

if ($failures) {
    foreach ($failures as $failure) fwrite(STDERR, "[FAIL] {$failure}\n");
    exit(1);
}
echo "[OK] entorno reproducible, certificados y controles operativos\n";
