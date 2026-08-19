<?php
require_once __DIR__ . '/../config/globals.php';

/**
 * Configuración de conexión a SQL Server
 * Servidor: JORDANYMB1\JORDANYMB1
 * Base de datos: inventario
 */

define('DB_SERVER',   getenv('DB_HOST') ?: 'JORDANYMB1\JORDANYMB1');
define('DB_DATABASE', getenv('DB_NAME') ?: 'inventario');
define('DB_USER',     getenv('DB_USER') ?: 'sa');
define('DB_PASSWORD', getenv('DB_PASS') ?: '');

// ============================================================
//  Conexión con sqlsrv (extensión nativa de Microsoft)
// ============================================================
function getConnection() {
    $connectionInfo = [
        "Database"               => DB_DATABASE,
        "UID"                    => DB_USER,
        "PWD"                    => DB_PASSWORD,
        "CharacterSet"           => "UTF-8",
        "TrustServerCertificate" => true
    ];

    $conn = sqlsrv_connect(DB_SERVER, $connectionInfo);

    if ($conn === false) {
        $errors = sqlsrv_errors();
        error_log("Error de conexión SQL Server: " . print_r($errors, true));
        die(json_encode([
            "error" => "No se pudo conectar a la base de datos.",
            "detalle" => $errors
        ]));
    }

    return $conn;
}

// ============================================================
//  Conexión con PDO (alternativa)
// ============================================================
function getPDOConnection() {
    try {
        $dsn = "sqlsrv:Server=" . DB_SERVER . ";Database=" . DB_DATABASE . ";TrustServerCertificate=1";
        $pdo = new PDO($dsn, DB_USER, DB_PASSWORD, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
        return $pdo;
    } catch (PDOException $e) {
        error_log("Error PDO: " . $e->getMessage());
        die(json_encode([
            "inv_error"   => "No se pudo conectar a la base de datos.",
            "detalle" => $e->getMessage()
        ]));
    }
}
