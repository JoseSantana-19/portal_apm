<?php
// conexion/conexion_externa.php (versión Portal APM — integración portuaria_demoV4)
//
// Expone $connExterna (recurso sqlsrv) hacia la BD externa del módulo
// Portuaria (PortuariaExterna — maestros APM: funcionarios/departamentos),
// con servidor/credenciales del portal.

if (!defined('DB_SERVER')) {
    require_once __DIR__ . '/../config/app.php';
}

$connExterna = $connExterna ?? null;
$mensajeConexionExterna = '';

if (!$connExterna) {
    $__dbExt = defined('DB_PORTUARIA_EXT_NAME') ? DB_PORTUARIA_EXT_NAME : 'PortuariaExterna';
    $__optsExt = [
        'Database'               => $__dbExt,
        'CharacterSet'           => 'UTF-8',
        'Encrypt'                => defined('DB_ENCRYPT') ? DB_ENCRYPT : false,
        'TrustServerCertificate' => true,
    ];
    if (DB_USER !== '') { $__optsExt['UID'] = DB_USER; $__optsExt['PWD'] = DB_PASS; }

    $connExterna = @sqlsrv_connect(DB_SERVER, $__optsExt);

    if ($connExterna === false) {
        $__masterExt = @sqlsrv_connect(DB_SERVER, ['Database' => 'master'] + $__optsExt);
        if ($__masterExt !== false) {
            sqlsrv_query($__masterExt, "IF DB_ID(N'{$__dbExt}') IS NULL CREATE DATABASE [{$__dbExt}];");
            sqlsrv_close($__masterExt);
            $connExterna = @sqlsrv_connect(DB_SERVER, $__optsExt);
        }
    }

    if ($connExterna !== false && $connExterna !== null) {
        $mensajeConexionExterna = "Conectado a {$__dbExt}";
    } else {
        $mensajeConexionExterna = 'ERROR: No se pudo conectar a la base externa';
        $connExterna = null;
    }
}

// LOG de consola solo fuera de /apis/ y *_api.php (JSON)
$scriptPath = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
$uriPath = str_replace('\\', '/', (string) ($_SERVER['REQUEST_URI'] ?? ''));
$esPeticionApi =
    strpos($scriptPath, '/apis/') !== false
    || strpos($uriPath, '/apis/') !== false
    || strpos($scriptPath, '_api.php') !== false
    || strpos($uriPath, '_api.php') !== false
    || isset($_GET['action']);

if (!$esPeticionApi) {
    $color = ($connExterna !== null) ? '#00bfff' : '#ff4500';
    echo "<script>console.log('%c🔌 DB Externa: $mensajeConexionExterna', 'color: $color; font-weight: bold;');</script>";
}
