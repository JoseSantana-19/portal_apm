<?php
require_once 'db/connection.php';
try {
    $dsn = "sqlsrv:Server=" . DB_SERVER . ";TrustServerCertificate=1";
    $pdo = new PDO($dsn, DB_USER, DB_PASSWORD, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    $stmt = $pdo->query("SELECT name FROM sys.databases");
    $databases = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "DATABASES:\n";
    foreach ($databases as $db) {
        echo "- " . $db . "\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
