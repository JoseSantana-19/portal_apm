<?php
require_once 'db/connection.php';
try {
    $dsn = "sqlsrv:Server=" . DB_SERVER . ";TrustServerCertificate=1";
    $pdo = new PDO($dsn, DB_USER, DB_PASSWORD, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    $stmt = $pdo->query("SELECT SERVERPROPERTY('InstanceDefaultDataPath') AS DefaultData, SERVERPROPERTY('InstanceDefaultLogPath') AS DefaultLog");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "DefaultData: " . $row['DefaultData'] . "\n";
    echo "DefaultLog: " . $row['DefaultLog'] . "\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
