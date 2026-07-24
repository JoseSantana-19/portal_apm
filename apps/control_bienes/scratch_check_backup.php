<?php
require_once 'db/connection.php';
try {
    $dsn = "sqlsrv:Server=" . DB_SERVER . ";TrustServerCertificate=1";
    $pdo = new PDO($dsn, DB_USER, DB_PASSWORD, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    echo "INVENTARIO.BAK:\n";
    $stmt = $pdo->prepare("RESTORE FILELISTONLY FROM DISK = 'c:/xampp/htdocs/Control_bines/inventario.bak'");
    $stmt->execute();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "- LogicalName: {$row['LogicalName']}, PhysicalName: {$row['PhysicalName']}, Type: {$row['Type']}\n";
    }
    
    echo "\nTALENTO_HUMANO.BAK:\n";
    $stmt = $pdo->prepare("RESTORE FILELISTONLY FROM DISK = 'c:/xampp/htdocs/Control_bines/talento_humano/base de datos/Talento_Humano.bak'");
    $stmt->execute();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "- LogicalName: {$row['LogicalName']}, PhysicalName: {$row['PhysicalName']}, Type: {$row['Type']}\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
