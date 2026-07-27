<?php
/**
 * Ejecutor universal de scripts T-SQL via extension sqlsrv (sin sqlcmd).
 *
 * Uso:
 *   php db/run_sql.php <archivo.sql> <servidor> [usuario] [contrasena]
 *
 * - Conecta a la BD master (el script puede crear su propia base con CREATE DATABASE)
 * - Divide el archivo por separadores GO (igual que SSMS / sqlcmd)
 * - Aborta al primer error (equivalente a sqlcmd -b) y muestra el detalle
 *
 * Codigos de salida:
 *   0 = OK | 1 = error SQL | 2 = uso incorrecto | 3 = sin sqlsrv | 4 = archivo ilegible | 5 = sin conexion
 */

$file   = $argv[1] ?? null;
$server = $argv[2] ?? null;
$user   = $argv[3] ?? '';
$pass   = $argv[4] ?? '';

if (!$file || !$server) {
    fwrite(STDERR, "Uso: php db/run_sql.php <archivo.sql> <servidor> [usuario] [contrasena]\n");
    exit(2);
}
if (!extension_loaded('sqlsrv')) {
    fwrite(STDERR, "ERROR: la extension sqlsrv no esta cargada en este PHP.\n");
    exit(3);
}

$sql = @file_get_contents($file);
if ($sql === false) {
    fwrite(STDERR, "ERROR: no se pudo leer el archivo: {$file}\n");
    exit(4);
}
// Quitar BOM UTF-8 si existe
$sql = preg_replace('/^\xEF\xBB\xBF/', '', $sql);

$opt = [
    'Database'               => 'master',
    'TrustServerCertificate' => true,
    'Encrypt'                => false,
    'CharacterSet'           => 'UTF-8',
];
if ($user !== '') {
    $opt['UID'] = $user;
    $opt['PWD'] = $pass;
}

$conn = sqlsrv_connect($server, $opt);
if (!$conn) {
    fwrite(STDERR, "ERROR de conexion a {$server}:\n" . print_r(sqlsrv_errors(), true) . "\n");
    exit(5);
}
// Sin esto, sqlsrv trata mensajes informativos (PRINT, RAISERROR de baja
// severidad) como si la consulta hubiera fallado — un simple PRINT dentro
// del script (ej. avisos de dependencias cross-DB opcionales) abortaría
// la ejecucion aunque el batch en si se haya ejecutado bien.
sqlsrv_configure('WarningsReturnAsErrors', 0);
echo "Conectado a {$server}. Ejecutando " . basename($file) . " ...\n";

// Dividir por lineas que contienen solo GO (case-insensitive), como hace sqlcmd
$batches = preg_split('/^\s*GO\s*;?\s*$/mi', $sql);
$total = 0;
$fallo = false;

foreach ($batches as $i => $batch) {
    $batch = trim($batch);
    if ($batch === '') continue;
    $total++;

    $stmt = sqlsrv_query($conn, $batch);
    if ($stmt === false) {
        $fallo = true;
        $preview = substr(preg_replace('/\s+/', ' ', $batch), 0, 120);
        fwrite(STDERR, "\nERROR en el batch #" . ($i + 1) . " ({$preview}...):\n");
        fwrite(STDERR, print_r(sqlsrv_errors(), true) . "\n");
        break; // abortar al primer error, como sqlcmd -b
    }
    // Consumir todos los result sets del batch antes de liberar
    while (sqlsrv_next_result($stmt)) { /* drain */ }
    sqlsrv_free_stmt($stmt);

    if ($total % 25 === 0) {
        echo "  ... {$total} batches ejecutados\n";
    }
}

sqlsrv_close($conn);

if ($fallo) {
    fwrite(STDERR, "FALLO tras {$total} batch(es).\n");
    exit(1);
}
echo "OK: {$total} batches ejecutados correctamente.\n";
exit(0);
