<?php
declare(strict_types=1);

set_time_limit(0);
require_once dirname(__DIR__) . '/config/globals.php';

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Este script solo puede ejecutarse desde la consola.\n");
    exit(1);
}
if (DB_DRIVER !== 'sqlsrv' || !extension_loaded('pdo_sqlsrv')) {
    fwrite(STDERR, "Se requiere DB_DRIVER=sqlsrv y la extension pdo_sqlsrv.\n");
    exit(1);
}
if (!preg_match('/^[A-Za-z0-9_]+$/', DB_NAME)) {
    fwrite(STDERR, "El nombre de la base de datos no es valido.\n");
    exit(1);
}

$directorio = dirname(__DIR__) . '/backup';
if (!is_dir($directorio) && !mkdir($directorio, 0775, true) && !is_dir($directorio)) {
    fwrite(STDERR, "No se pudo crear la carpeta de respaldos.\n");
    exit(1);
}

$archivo = $directorio . '/' . DB_NAME . '_' . date('Ymd_His') . '.bak';
$archivoSql = str_replace("'", "''", str_replace('/', '\\', $archivo));
$base = '[' . str_replace(']', ']]', DB_NAME) . ']';

try {
    $dsn = 'sqlsrv:Server=' . DB_HOST . ';Database=master;TrustServerCertificate=1';
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    echo "Creando respaldo de " . DB_NAME . "...\n";
    $pdo->exec("BACKUP DATABASE {$base} TO DISK = N'{$archivoSql}' WITH COPY_ONLY, INIT, CHECKSUM");
    $pdo->exec("RESTORE VERIFYONLY FROM DISK = N'{$archivoSql}' WITH CHECKSUM");
    clearstatcache(true, $archivo);
    if (!is_file($archivo) || filesize($archivo) <= 0) {
        throw new RuntimeException('SQL Server termino el proceso, pero el archivo no es visible o esta vacio.');
    }
    echo "Respaldo verificado: {$archivo}\n";
    echo "Tamano: " . number_format((float)filesize($archivo) / 1048576, 2) . " MB\n";
} catch (Throwable $e) {
    if (is_file($archivo) && filesize($archivo) === 0) @unlink($archivo);
    fwrite(STDERR, "ERROR: " . $e->getMessage() . "\n");
    exit(1);
}
