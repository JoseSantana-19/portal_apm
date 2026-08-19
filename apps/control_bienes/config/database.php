<?php
/**
 * DATABASE.PHP - Establece y retorna la conexión PDO unificada a la Base de Datos.
 * Lee las variables globales definidas en config/globals.php.
 */
require_once __DIR__ . '/globals.php';

function getDatabaseConnection() {
    static $pdo = null;
    
    if ($pdo !== null) {
        return $pdo;
    }
    
    $driver = DB_DRIVER;
    
    try {
        if ($driver === 'pgsql') {
            $port = DB_PORT ?: '5432';
            $dsn = "pgsql:host=" . DB_HOST . ";port=" . $port . ";dbname=" . DB_NAME;
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
        } elseif ($driver === 'sqlsrv') {
            $dsn = "sqlsrv:Server=" . DB_HOST;
            // Las instancias con nombre resuelven su propio puerto. Agregar
            // ",1433" a "servidor\\instancia" impide la conexion local.
            if (DB_PORT && DB_PORT !== '' && strpos(DB_HOST, '\\') === false) {
                $dsn .= "," . DB_PORT;
            }
            $dsn .= ";Database=" . DB_NAME;
            if (DB_TRUST_CERT) {
                $dsn .= ";TrustServerCertificate=1";
            }
            
            if (empty(DB_USER)) {
                $pdo = new PDO($dsn);
            } else {
                $pdo = new PDO($dsn, DB_USER, DB_PASS);
            }
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } else {
            // SQLite fallback
            $dbPath = DB_SQLITE_PATH;
            $pdo = new PDO("sqlite:" . $dbPath);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $pdo->exec("PRAGMA foreign_keys = ON;");
        }
        
        return $pdo;
    } catch (PDOException $e) {
        error_log("Error de conexión a la base de datos ({$driver}): " . $e->getMessage());
        die("Error crítico de base de datos: No se pudo conectar a la base de datos ({$driver}). Detalle: " . $e->getMessage());
    }
}
