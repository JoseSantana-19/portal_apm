<?php
declare(strict_types=1);

function fail(string $message): never
{
    fwrite(STDERR, "[FAIL] {$message}".PHP_EOL);
    exit(1);
}

function quoteIdentifier(string $value): string
{
    return '['.str_replace(']', ']]', $value).']';
}

function quoteLiteral(string $value): string
{
    return "N'".str_replace("'", "''", $value)."'";
}

function fetchAll(PDO $pdo, string $sql, array $params = []): array
{
    $statement = $pdo->prepare($sql);
    $statement->execute($params);
    return $statement->fetchAll(PDO::FETCH_ASSOC);
}

function executeSql(PDO $pdo, string $sql): void
{
    $statement = $pdo->query($sql);
    if ($statement === false) return;
    do {
        if ($statement->columnCount() > 0) $statement->fetchAll(PDO::FETCH_ASSOC);
    } while ($statement->nextRowset());
}

function backupDevices(PDO $pdo, int $backupSetId): array
{
    $rows = fetchAll($pdo,
        'SELECT bmf.physical_device_name
         FROM msdb.dbo.backupmediafamily bmf
         JOIN msdb.dbo.backupset bs ON bs.media_set_id=bmf.media_set_id
         WHERE bs.backup_set_id=:id ORDER BY bmf.family_sequence_number',
        [':id' => $backupSetId]
    );
    $devices = array_values(array_filter(array_map(
        static fn(array $row): string => trim((string)$row['physical_device_name']),
        $rows
    )));
    if (!$devices) fail("El respaldo {$backupSetId} no tiene dispositivos registrados.");
    return $devices;
}

function deviceClause(array $paths): string
{
    return implode(',', array_map(static fn(string $path): string => 'DISK='.quoteLiteral($path), $paths));
}

function volumeRoot(string $path): string
{
    if (preg_match('/^[A-Za-z]:[\\\\\/]/', $path) === 1) {
        return strtoupper(substr($path, 0, 2)).'\\\\';
    }
    return $path;
}

$options = getopt('', ['execute', 'target:', 'confirm-target:']);
$execute = array_key_exists('execute', $options);
$target = trim((string)($options['target'] ?? ('Talento_Humano_RestoreDrill_'.date('Ymd_His'))));
$confirmedTarget = trim((string)($options['confirm-target'] ?? ''));
if (!preg_match('/^Talento_Humano_RestoreDrill_\d{8}_\d{6}$/', $target)) {
    fail('El nombre temporal no cumple el patrón seguro Talento_Humano_RestoreDrill_AAAAMMDD_HHMMSS.');
}
if ($execute && $confirmedTarget !== $target) {
    fail('Para ejecutar debe repetir el nombre exacto mediante --confirm-target.');
}

$server = getenv('PORTAL_DB_SERVER') ?: 'tcp:portal-apm-preprod.local,1433';
$driver = getenv('PORTAL_DB_DRIVER') ?: 'ODBC Driver 18 for SQL Server';
$user = getenv('PORTAL_DBA_USER') ?: '';
$password = getenv('PORTAL_DBA_PASSWORD') ?: '';
if ($user === '' || $password === '') fail('Faltan las credenciales DBA temporales.');

$dsn = sprintf(
    'sqlsrv:Driver=%s;Server=%s;Database=master;Encrypt=true;TrustServerCertificate=false',
    $driver,
    $server
);
$attributes = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];
if (defined('PDO::SQLSRV_ATTR_QUERY_TIMEOUT')) $attributes[PDO::SQLSRV_ATTR_QUERY_TIMEOUT] = 0;
try {
    $pdo = new PDO($dsn, $user, $password, $attributes);
} catch (Throwable $exception) {
    fail('No se pudo abrir la conexión DBA cifrada: '.$exception->getMessage());
}

$connection = fetchAll($pdo,
    "SELECT IS_SRVROLEMEMBER('sysadmin') sysadmin,encrypt_option
     FROM sys.dm_exec_connections WHERE session_id=@@SPID"
)[0] ?? [];
if ((int)($connection['sysadmin'] ?? 0) !== 1 || strtoupper((string)($connection['encrypt_option'] ?? '')) !== 'TRUE') {
    fail('La restauración exige sysadmin y conexión cifrada.');
}
$exists = (int)(fetchAll($pdo, 'SELECT COUNT(*) total FROM sys.databases WHERE name=:name', [':name' => $target])[0]['total'] ?? 0);
if ($exists !== 0) fail("La base temporal ya existe: {$target}.");

$differential = fetchAll($pdo,
    "SELECT TOP 1 backup_set_id,backup_finish_date,database_backup_lsn,last_lsn,backup_size
     FROM msdb.dbo.backupset
     WHERE database_name=N'Talento_Humano' AND type='I' AND has_backup_checksums=1
     ORDER BY backup_finish_date DESC"
)[0] ?? null;

if ($differential) {
    $full = fetchAll($pdo,
        "SELECT TOP 1 backup_set_id,backup_finish_date,checkpoint_lsn,last_lsn,backup_size
         FROM msdb.dbo.backupset
         WHERE database_name=N'Talento_Humano' AND type='D' AND has_backup_checksums=1
           AND checkpoint_lsn=:base_lsn AND backup_finish_date<=:diff_finish
         ORDER BY backup_finish_date DESC",
        [':base_lsn' => $differential['database_backup_lsn'], ':diff_finish' => $differential['backup_finish_date']]
    )[0] ?? null;
} else {
    $full = fetchAll($pdo,
        "SELECT TOP 1 backup_set_id,backup_finish_date,checkpoint_lsn,last_lsn,backup_size
         FROM msdb.dbo.backupset
         WHERE database_name=N'Talento_Humano' AND type='D' AND has_backup_checksums=1 AND is_copy_only=0
         ORDER BY backup_finish_date DESC"
    )[0] ?? null;
}
if (!$full) fail('No se encontró un FULL con checksum compatible con el diferencial.');

$baseLastLsn = $differential['last_lsn'] ?? $full['last_lsn'];
$logs = fetchAll($pdo,
    "SELECT backup_set_id,backup_start_date,backup_finish_date,first_lsn,last_lsn,backup_size
     FROM msdb.dbo.backupset
     WHERE database_name=N'Talento_Humano' AND type='L' AND has_backup_checksums=1
       AND last_lsn>:base_lsn
     ORDER BY first_lsn,backup_start_date",
    [':base_lsn' => $baseLastLsn]
);
if (!$logs) fail('No existen logs con checksum posteriores a la base seleccionada.');

$fullDevices = backupDevices($pdo, (int)$full['backup_set_id']);
$diffDevices = $differential ? backupDevices($pdo, (int)$differential['backup_set_id']) : [];
$logDevices = [];
foreach ($logs as $log) $logDevices[(int)$log['backup_set_id']] = backupDevices($pdo, (int)$log['backup_set_id']);

$fileList = fetchAll($pdo, 'RESTORE FILELISTONLY FROM '.deviceClause($fullDevices));
if (!$fileList) fail('No se pudo obtener FILELISTONLY del FULL seleccionado.');

$paths = fetchAll($pdo,
    "SELECT CONVERT(nvarchar(4000),SERVERPROPERTY('InstanceDefaultDataPath')) data_path,
            CONVERT(nvarchar(4000),SERVERPROPERTY('InstanceDefaultLogPath')) log_path"
)[0] ?? [];
$dataPath = rtrim((string)($paths['data_path'] ?? ''), '\\/').'\\';
$logPath = rtrim((string)($paths['log_path'] ?? ''), '\\/').'\\';
if ($dataPath === '\\' || $logPath === '\\') fail('SQL Server no informó sus rutas predeterminadas.');

$moves = [];
$restoredFiles = [];
$dataBytes = 0;
$logBytes = 0;
foreach ($fileList as $index => $file) {
    $type = strtoupper((string)$file['Type']);
    $extension = $type === 'L' ? '.ldf' : ($index === 0 ? '.mdf' : '.ndf');
    $destination = ($type === 'L' ? $logPath : $dataPath).$target.'_'.($index + 1).$extension;
    $moves[] = 'MOVE '.quoteLiteral((string)$file['LogicalName']).' TO '.quoteLiteral($destination);
    $restoredFiles[] = $destination;
    if ($type === 'L') $logBytes += (int)$file['Size']; else $dataBytes += (int)$file['Size'];
}

$dataVolume = volumeRoot($dataPath);
$logVolume = volumeRoot($logPath);
$dataFree = disk_free_space($dataVolume);
$logFree = disk_free_space($logVolume);
if ($dataFree === false || $logFree === false) fail('No se pudo comprobar el espacio libre de las rutas SQL.');
$sameVolume = strcasecmp($dataVolume, $logVolume) === 0;
$requiredTotal = (int)ceil(($dataBytes + $logBytes) * 1.15);
if (($sameVolume && $dataFree < $requiredTotal) || (!$sameVolume && ($dataFree < $dataBytes * 1.15 || $logFree < $logBytes * 1.15))) {
    fail('No existe espacio libre suficiente para restaurar con un margen del 15 %.');
}

echo 'RESTORE_TARGET='.$target.PHP_EOL;
echo 'FULL='.$full['backup_finish_date'].' | '.implode(' + ', $fullDevices).PHP_EOL;
echo 'DIFF='.($differential ? $differential['backup_finish_date'].' | '.implode(' + ', $diffDevices) : 'NO APLICA').PHP_EOL;
echo 'LOG_COUNT='.count($logs).PHP_EOL;
echo 'LAST_LOG='.$logs[array_key_last($logs)]['backup_finish_date'].PHP_EOL;
echo 'DATA_PATH='.$dataPath.PHP_EOL;
echo 'LOG_PATH='.$logPath.PHP_EOL;
echo 'RESTORE_SIZE_BYTES='.($dataBytes + $logBytes).PHP_EOL;
echo 'DATA_FREE_BYTES='.(int)$dataFree.PHP_EOL;
echo 'LOG_FREE_BYTES='.(int)$logFree.PHP_EOL;
foreach ($restoredFiles as $path) echo 'RESTORED_FILE='.$path.PHP_EOL;

executeSql($pdo, 'RESTORE VERIFYONLY FROM '.deviceClause($fullDevices).' WITH CHECKSUM');
if ($differential) executeSql($pdo, 'RESTORE VERIFYONLY FROM '.deviceClause($diffDevices).' WITH CHECKSUM');
foreach ($logs as $log) {
    executeSql($pdo, 'RESTORE VERIFYONLY FROM '.deviceClause($logDevices[(int)$log['backup_set_id']]).' WITH CHECKSUM');
}
echo '[OK] VERIFYONLY y checksum de toda la cadena'.PHP_EOL;

if (!$execute) {
    echo 'RESTORE_DRILL=DRY_RUN_OK'.PHP_EOL;
    exit(0);
}

$targetSql = quoteIdentifier($target);
$restored = false;
$validated = false;
try {
    executeSql($pdo,
        'RESTORE DATABASE '.$targetSql.' FROM '.deviceClause($fullDevices).
        ' WITH '.implode(',', $moves).',NORECOVERY,CHECKSUM,STATS=5'
    );
    $restored = true;
    if ($differential) {
        executeSql($pdo, 'RESTORE DATABASE '.$targetSql.' FROM '.deviceClause($diffDevices).' WITH NORECOVERY,CHECKSUM,STATS=5');
    }
    foreach ($logs as $index => $log) {
        $isLast = $index === array_key_last($logs);
        executeSql($pdo,
            'RESTORE LOG '.$targetSql.' FROM '.deviceClause($logDevices[(int)$log['backup_set_id']]).
            ' WITH '.($isLast ? 'RECOVERY' : 'NORECOVERY').',CHECKSUM,STATS=5'
        );
    }

    executeSql($pdo, 'DBCC CHECKDB('.quoteLiteral($target).') WITH NO_INFOMSGS,ALL_ERRORMSGS');
    $sourceCounts = fetchAll($pdo,
        "SELECT
          (SELECT COUNT_BIG(*) FROM Talento_Humano.dbo.th_empleados) empleados,
          (SELECT COUNT_BIG(*) FROM Talento_Humano.dbo.th_schema_migrations) migraciones"
    )[0] ?? [];
    $targetCounts = fetchAll($pdo,
        'SELECT
          (SELECT COUNT_BIG(*) FROM '.$targetSql.'.dbo.th_empleados) empleados,
          (SELECT COUNT_BIG(*) FROM '.$targetSql.'.dbo.th_schema_migrations) migraciones,
          (SELECT COUNT_BIG(*) FROM '.$targetSql.'.dbo.th_logs_auditoria) auditoria'
    )[0] ?? [];
    foreach (['empleados','migraciones'] as $key) {
        if ((string)($sourceCounts[$key] ?? '') !== (string)($targetCounts[$key] ?? '')) {
            throw new RuntimeException("El conteo restaurado no coincide para {$key}.");
        }
    }
    if ((int)$targetCounts['migraciones'] < 18) throw new RuntimeException('El ledger restaurado no contiene las dieciocho migraciones mínimas esperadas.');
    if ((int)$targetCounts['auditoria'] < 1) throw new RuntimeException('La bitácora restaurada está vacía.');
    $validated = true;
    echo '[OK] DBCC CHECKDB sin errores'.PHP_EOL;
    echo '[OK] empleados y migraciones coinciden; la auditoría restaurada contiene registros'.PHP_EOL;
} catch (Throwable $exception) {
    fwrite(STDERR, '[FAIL] Restauración o validación: '.$exception->getMessage().PHP_EOL);
} finally {
    $databaseExists = (int)(fetchAll($pdo, 'SELECT COUNT(*) total FROM sys.databases WHERE name=:name', [':name' => $target])[0]['total'] ?? 0);
    if ($databaseExists === 1) {
        executeSql($pdo, 'ALTER DATABASE '.$targetSql.' SET SINGLE_USER WITH ROLLBACK IMMEDIATE');
        executeSql($pdo, 'DROP DATABASE '.$targetSql);
        echo 'CLEANUP_DATABASE='.$target.PHP_EOL;
    }
}

if (!$restored || !$validated) fail('La prueba de restauración no terminó correctamente.');
$remaining = (int)(fetchAll(
    $pdo,
    'SELECT COUNT(*) total FROM sys.databases WHERE name=:name',
    [':name' => $target]
)[0]['total'] ?? 0);
if ($remaining !== 0) fail('La base temporal no fue eliminada al terminar la prueba.');
echo 'CLEANUP_VERIFIED='.$target.PHP_EOL;
echo 'RESTORE_DRILL=OK'.PHP_EOL;
