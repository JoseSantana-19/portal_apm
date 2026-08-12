<?php

declare(strict_types=1);

define('ROOT', dirname(__DIR__));
require_once ROOT . '/core/Config.php';

$archivo = ROOT . '/tests/sql_mejoras_operativas_smoke.sql';
$sql = file_get_contents($archivo);
if ($sql === false) {
    fwrite(STDERR, "No se pudo leer {$archivo}.\n");
    exit(1);
}

try {
    $config = Config::database();
    $dsn = sprintf(
        'sqlsrv:Server=%s;Database=%s;Encrypt=%s;TrustServerCertificate=%s',
        $config['server'],
        $config['database'],
        !empty($config['encrypt']) ? 'true' : 'false',
        !empty($config['trust_server_certificate']) ? 'true' : 'false'
    );
    $pdo = new PDO($dsn, $config['user'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::SQLSRV_ATTR_ENCODING => PDO::SQLSRV_ENCODING_UTF8,
    ]);
    $lotes = preg_split('/^\s*GO\s*$/mi', $sql) ?: [];
    $resultado = null;
    foreach ($lotes as $lote) {
        if (trim($lote) === '') {
            continue;
        }
        $sentencia = $pdo->query($lote);
        do {
            if ($sentencia->columnCount() > 0) {
                $fila = $sentencia->fetch(PDO::FETCH_ASSOC);
                if (is_array($fila) && isset($fila['resultado'])) {
                    $resultado = (string)$fila['resultado'];
                }
            }
        } while ($sentencia->nextRowset());
        $sentencia->closeCursor();
    }
    if ($resultado !== 'SQL_MEJORAS_OPERATIVAS_OK') {
        throw new RuntimeException('La prueba SQL no devolvió la confirmación esperada.');
    }
    echo "[OK] {$resultado}\n";
} catch (Throwable $e) {
    fwrite(STDERR, '[ERROR] Prueba SQL: '.$e->getMessage()."\n");
    exit(1);
}
