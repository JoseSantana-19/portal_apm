<?php
// conexion/conexion.php (versión Portal APM — integración portuaria_demoV4)
//
// Compatibilidad con las APIs/páginas legacy del módulo Portuaria: expone la
// variable global $conn (recurso sqlsrv) conectada a la BD principal del
// módulo (PortuariaDemo), usando el servidor/credenciales del portal
// (config/app.php) en lugar de valores quemados.
require_once __DIR__ . '/zona_horaria.php';

if (!defined('DB_SERVER')) {
    require_once __DIR__ . '/../config/app.php';
}

if (!isset($conn) || !$conn) {
    $__dbName = defined('DB_PORTUARIA_NAME') ? DB_PORTUARIA_NAME : 'PortuariaDemo';
    $__opts = [
        'Database'               => $__dbName,
        'CharacterSet'           => 'UTF-8',
        'Encrypt'                => defined('DB_ENCRYPT') ? DB_ENCRYPT : false,
        'TrustServerCertificate' => true,
    ];
    if (DB_USER !== '') { $__opts['UID'] = DB_USER; $__opts['PWD'] = DB_PASS; }

    @sqlsrv_configure("WarningsReturnAsErrors", 0);
    $conn = @sqlsrv_connect(DB_SERVER, $__opts);

    // Si la BD no existe aún, crearla y reconectar (robustez de instalación).
    if ($conn === false) {
        $__master = @sqlsrv_connect(DB_SERVER, ['Database' => 'master'] + $__opts);
        if ($__master !== false) {
            sqlsrv_query($__master, "IF DB_ID(N'{$__dbName}') IS NULL CREATE DATABASE [{$__dbName}];");
            sqlsrv_close($__master);
            $conn = @sqlsrv_connect(DB_SERVER, $__opts);
        }
    }

    if ($conn === false) {
        $detalle = sqlsrv_errors();
        die(
            'Error de conexión a SQL Server (Portuaria).'
            . ($detalle ? '<br><pre>' . htmlspecialchars(print_r($detalle, true), ENT_QUOTES, 'UTF-8') . '</pre>' : '')
        );
    }
}

/**
 * LOG de consola solo en páginas HTML — nunca en /apis/ ni *_api.php (JSON).
 */
$scriptPath = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
$uriPath = str_replace('\\', '/', (string) ($_SERVER['REQUEST_URI'] ?? ''));
$esPeticionApi =
    strpos($scriptPath, '/apis/') !== false
    || strpos($uriPath, '/apis/') !== false
    || strpos($scriptPath, '_api.php') !== false
    || strpos($uriPath, '_api.php') !== false
    || isset($_GET['action']);

if (!$esPeticionApi) {
    $__db = defined('DB_PORTUARIA_NAME') ? DB_PORTUARIA_NAME : 'PortuariaDemo';
    echo "<script>console.log('%c🔌 DB Portuaria: Conectado a {$__db}', 'color: #00ff00; font-weight: bold;');</script>";
}
