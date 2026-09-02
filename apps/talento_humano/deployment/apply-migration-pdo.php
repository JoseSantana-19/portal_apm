<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Este comando solo puede ejecutarse desde consola.\n");
    exit(1);
}

define('ROOT', dirname(__DIR__));
require ROOT.'/core/Config.php';
require ROOT.'/core/Database.php';

$argument = (string)($argv[1] ?? '');
$databaseRoot = realpath(ROOT.'/database');
$migration = realpath(ROOT.'/'.str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $argument));
if ($databaseRoot === false || $migration === false || !is_file($migration)
    || !str_starts_with(strtolower($migration), strtolower($databaseRoot.DIRECTORY_SEPARATOR))) {
    fwrite(STDERR, "Indique una migración válida perteneciente al directorio database.\n");
    exit(1);
}
$migrationBase = basename($migration);

try {
    $config = Config::database();
    if (empty($config['encrypt']) || !empty($config['trust_server_certificate'])) {
        throw new RuntimeException('La compuerta exige SQL cifrado y validación del certificado.');
    }
    if (strcasecmp((string)$config['user'], 'portal_app') === 0) {
        throw new RuntimeException('Use credenciales DBA temporales mediante PORTAL_DB_USER y PORTAL_DB_PASSWORD.');
    }

    $db = Conexion::conectar();
    $security = $db->query("SELECT IS_SRVROLEMEMBER('sysadmin') sysadmin,encrypt_option FROM sys.dm_exec_connections WHERE session_id=@@SPID")->fetch(PDO::FETCH_ASSOC);
    if ((int)($security['sysadmin'] ?? 0) !== 1 || strtoupper((string)($security['encrypt_option'] ?? '')) !== 'TRUE') {
        throw new RuntimeException('La conexión no es sysadmin o no está cifrada.');
    }

    $backupDirectory = rtrim((string)$db->query("SELECT CONVERT(nvarchar(4000),SERVERPROPERTY('InstanceDefaultBackupPath'))")->fetchColumn(), '/\\');
    if ($backupDirectory === '') throw new RuntimeException('SQL Server no informó su directorio de respaldo.');
    $slug = preg_replace('/[^A-Za-z0-9_-]+/', '_', pathinfo($migration, PATHINFO_FILENAME));
    $backup = $backupDirectory.DIRECTORY_SEPARATOR.'Talento_Humano_pre_'.$slug.'_'.date('Ymd_His').'.bak';
    $stmt = $db->prepare('BACKUP DATABASE [Talento_Humano] TO DISK=:file WITH COPY_ONLY,CHECKSUM,COMPRESSION,INIT');
    $stmt->execute([':file'=>$backup]);
    while ($stmt->nextRowset()) {}
    $stmt = $db->prepare('RESTORE VERIFYONLY FROM DISK=:file WITH CHECKSUM');
    $stmt->execute([':file'=>$backup]);
    while ($stmt->nextRowset()) {}
    echo "[OK] Respaldo previo verificado.\n";

    $sql = (string)file_get_contents($migration);
    $batches = preg_split('/^\s*GO\s*;?\s*$/mi', $sql) ?: [];
    $executed = 0;
    foreach ($batches as $batch) {
        $batch = trim($batch);
        if ($batch === '') continue;
        $db->exec($batch);
        $executed++;
    }
    echo "[OK] {$executed} lotes SQL ejecutados.\n";

    if ($db->query("SELECT OBJECT_ID('dbo.th_schema_migrations','U')")->fetchColumn()) {
        $checksum = $db->prepare('UPDATE dbo.th_schema_migrations SET checksum_sha256=:sha WHERE nombre_archivo=:file');
        foreach (glob($databaseRoot.DIRECTORY_SEPARATOR.'*.sql') ?: [] as $sqlFile) {
            $checksum->execute([':sha'=>strtolower(hash_file('sha256', $sqlFile)), ':file'=>basename($sqlFile)]);
        }
    }

    $isLaborRegime = $migrationBase === 'migracion_regimen_laboral_20260829.sql';
    $isAssistantRole = $migrationBase === 'migracion_rol_asistente_talento_20260827.sql';
    $isPeriodIntegrity = $migrationBase === 'migracion_integridad_periodos_20260830.sql';
    $required = $isPeriodIntegrity ? [
        ['dbo.tr_th_empleados_crear_periodo_inicial','TR'],
        ['dbo.sp_th_actualizar_borrador_accion_personal','P'],
        ['dbo.th_periodos_vinculacion','U'],
    ] : ($isLaborRegime ? [
        ['dbo.th_secuencias_documentos','U'],
        ['dbo.sp_th_asignar_regimen_empleado','P'],
        ['dbo.tr_th_acciones_asignar_serie','TR'],
        ['dbo.vw_th_situacion_laboral_efectiva','V'],
    ] : [
        ['dbo.th_documentos_firmados','U'],
        ['dbo.vw_th_documentos_firmados','V'],
        ['dbo.vw_th_eventos_laborales','V'],
        ['dbo.sp_th_registrar_documento_firmado','P'],
        ['dbo.sp_th_consultar_documentos_firmados','P'],
        ['dbo.sp_th_consultar_eventos_laborales','P'],
    ]);
    $object = $db->prepare('SELECT OBJECT_ID(:name,:type)');
    foreach ($required as [$name,$type]) {
        $object->execute([':name'=>$name, ':type'=>$type]);
        if (!$object->fetchColumn()) throw new RuntimeException("La verificación no encontró {$name}.");
    }
    $ledgerVersion = $isPeriodIntegrity ? '2026.08.30.1' : ($isLaborRegime ? '2026.08.29.1' : ($isAssistantRole ? '2026.08.27.2' : '2026.08.27.1'));
    $ledgerStmt = $db->prepare(
        'SELECT COUNT_BIG(*) FROM dbo.th_schema_migrations WHERE version=:version AND LEN(checksum_sha256)=64'
    );
    $ledgerStmt->execute([':version' => $ledgerVersion]);
    if ((int)$ledgerStmt->fetchColumn() !== 1) {
        throw new RuntimeException('La migración no quedó registrada con su checksum.');
    }

    if ($isPeriodIntegrity) {
        $missing = (int)$db->query(
            'SELECT COUNT_BIG(*) FROM dbo.th_empleados e
             WHERE NOT EXISTS(SELECT 1 FROM dbo.th_periodos_vinculacion p WHERE p.empleado_id=e.empleado_id)'
        )->fetchColumn();
        if ($missing > 0) throw new RuntimeException("Persisten {$missing} funcionarios sin período de vinculación.");
    } elseif ($isLaborRegime) {
        $sequence = (int)$db->query(
            "SELECT COUNT_BIG(*) FROM dbo.th_secuencias_documentos
             WHERE regimen_laboral='CODIGO_TRABAJO' AND tipo_documento='FORMULARIO_ABREVIADO' AND activo=1"
        )->fetchColumn();
        if ($sequence < 1) throw new RuntimeException('No se parametrizó la serie CdgT del Formulario Abreviado.');
    } elseif ($isAssistantRole) {
        $roleId = (int)$db->query(
            "SELECT ISNULL(MAX(rol_id),0) FROM dbo.th_roles
             WHERE nombre_rol=N'Asistente de Talento Humano' AND estado=1"
        )->fetchColumn();
        if ($roleId <= 0) throw new RuntimeException('No se creó el rol Asistente de Talento Humano.');
        $mapped = (int)$db->query(
            'SELECT COUNT_BIG(*) FROM dbo.th_puesto_rol_mapa WHERE rol_id='.$roleId
        )->fetchColumn();
        if ($mapped < 1) throw new RuntimeException('El nuevo rol no quedó asociado a cargos institucionales.');
    }
    echo "[OK] Migración, objetos y checksum verificados.\n";
    echo 'BACKUP='.basename($backup).PHP_EOL;
} catch (Throwable $error) {
    fwrite(STDERR, '[FAIL] '.$error->getMessage().PHP_EOL);
    exit(1);
}
