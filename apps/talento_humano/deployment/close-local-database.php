<?php
declare(strict_types=1);

function fail(string $message): never
{
    fwrite(STDERR, "[FAIL] {$message}".PHP_EOL);
    exit(1);
}

function fetchAll(PDO $pdo, string $sql): array
{
    return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}

function runSqlFile(PDO $pdo, string $path): void
{
    $sql = file_get_contents($path);
    if ($sql === false) fail("No se pudo leer {$path}.");
    $batches = preg_split('/^\s*GO\s*;?\s*$/mi', $sql) ?: [];
    foreach ($batches as $index => $batch) {
        if (trim($batch) === '') continue;
        try {
            $statement = $pdo->query($batch);
            if ($statement !== false) {
                do {
                    if ($statement->columnCount() > 0) $statement->fetchAll(PDO::FETCH_ASSOC);
                } while ($statement->nextRowset());
            }
        } catch (Throwable $exception) {
            fail(sprintf('Falló el lote %d de %s: %s', $index + 1, basename($path), $exception->getMessage()));
        }
    }
}

$root = dirname(__DIR__);
$reconcile = in_array('--reconcile', $argv, true);
$server = getenv('PORTAL_DB_SERVER') ?: 'tcp:portal-apm-preprod.local,1433';
$driver = getenv('PORTAL_DB_DRIVER') ?: 'ODBC Driver 18 for SQL Server';
$user = getenv('PORTAL_DBA_USER') ?: '';
$password = getenv('PORTAL_DBA_PASSWORD') ?: '';
if ($user === '' || $password === '') fail('Faltan las credenciales DBA temporales.');

$dsn = sprintf(
    'sqlsrv:Driver=%s;Server=%s;Database=Talento_Humano;Encrypt=true;TrustServerCertificate=false',
    $driver,
    $server
);
try {
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (Throwable $exception) {
    fail('No se pudo establecer la conexión DBA cifrada: '.$exception->getMessage());
}

$connection = fetchAll($pdo,
    "SELECT ORIGINAL_LOGIN() login_name, IS_SRVROLEMEMBER('sysadmin') sysadmin, encrypt_option
     FROM sys.dm_exec_connections WHERE session_id=@@SPID"
)[0] ?? [];
if ((int)($connection['sysadmin'] ?? 0) !== 1) fail('La cuenta temporal no es sysadmin.');
if (strtoupper((string)($connection['encrypt_option'] ?? '')) !== 'TRUE') fail('La conexión DBA no está cifrada.');
echo '[OK] conexión DBA cifrada y privilegios verificados'.PHP_EOL;

$database = fetchAll($pdo,
    "SELECT recovery_model_desc,state_desc,page_verify_option_desc,is_auto_close_on,is_auto_shrink_on
     FROM sys.databases WHERE name=N'Talento_Humano'"
)[0] ?? [];
if (($database['recovery_model_desc'] ?? '') !== 'FULL' || ($database['state_desc'] ?? '') !== 'ONLINE') {
    fail('Talento_Humano no está ONLINE con recuperación FULL.');
}
if (($database['page_verify_option_desc'] ?? '') !== 'CHECKSUM' || (int)($database['is_auto_close_on'] ?? 1) !== 0 || (int)($database['is_auto_shrink_on'] ?? 1) !== 0) {
    fail('La configuración de integridad de Talento_Humano no es la esperada.');
}
echo '[OK] base ONLINE, FULL, PAGE_VERIFY CHECKSUM, AUTO_CLOSE/OFF y AUTO_SHRINK/OFF'.PHP_EOL;

$jobs = fetchAll($pdo,
    "SELECT j.name,j.enabled,
            (SELECT TOP 1 h.run_status FROM msdb.dbo.sysjobhistory h
             WHERE h.job_id=j.job_id AND h.step_id=0 ORDER BY h.instance_id DESC) last_status
     FROM msdb.dbo.sysjobs j WHERE j.name IN(
       N'APM - Respaldo completo semanal',N'APM - Respaldo diferencial diario',
       N'APM - Respaldo log 15 minutos',N'APM - Integridad semanal',N'APM - Vigencias laborales')"
);
if (count($jobs) !== 5 || array_filter($jobs, static fn(array $job): bool => (int)$job['enabled'] !== 1)) {
    fail('No están instalados y habilitados los cinco trabajos APM.');
}
foreach ($jobs as $job) {
    $status = $job['last_status'];
    if ($status !== null && (int)$status === 0) fail('El último resultado falló para '.$job['name'].'.');
}
echo '[OK] cinco trabajos SQL Agent instalados, habilitados y sin último fallo'.PHP_EOL;

$backups = fetchAll($pdo,
    "SELECT type,MAX(backup_finish_date) backup_finish_date
     FROM msdb.dbo.backupset
     WHERE database_name=N'Talento_Humano' AND has_backup_checksums=1
     GROUP BY type"
);
$hasFull = false;
$hasLog = false;
foreach ($backups as $backup) {
    if ($backup['type'] === 'D') $hasFull = true;
    if ($backup['type'] === 'L') $hasLog = true;
    echo sprintf('[INFO] último respaldo %s con checksum: %s', $backup['type'], $backup['backup_finish_date']).PHP_EOL;
}
if (!$hasFull || !$hasLog) fail('No hay evidencia reciente de respaldos FULL y LOG con checksum.');
echo '[OK] respaldos FULL y LOG con checksum registrados'.PHP_EOL;

if ($reconcile) {
    runSqlFile($pdo, $root.'/database/administracion/reconciliar_historial_migraciones.sql');
    echo '[OK] historial de migraciones conciliado'.PHP_EOL;
}

$migrations = fetchAll($pdo,
    "SELECT version,nombre_archivo,checksum_sha256
     FROM dbo.th_schema_migrations ORDER BY migration_id"
);
if (count($migrations) !== 20) {
    echo '[INFO] migraciones registradas: '.count($migrations).'/20'.PHP_EOL;
    fail('El historial debe contener exactamente las veinte migraciones vigentes.');
} elseif (array_filter($migrations, static fn(array $row): bool => strlen((string)$row['checksum_sha256']) !== 64)) {
    fail('Una migración no contiene checksum SHA-256.');
} else {
    echo '[OK] '.count($migrations).' migraciones registradas con SHA-256'.PHP_EOL;
}

echo 'DATABASE_CLOSURE='.($reconcile ? 'RECONCILED' : 'AUDITED').PHP_EOL;
