<?php
/**
 * Diagnóstico de conexiones a PortuariaDemo y PortuariaExterna.
 * Ejecutar desde el navegador para verificar que ambas bases estén accesibles.
 */
header('Content-Type: text/html; charset=UTF-8');

echo "<h2>Diagnóstico de conexiones</h2>\n";

// 1) Conexión local
require_once __DIR__ . '/conexion/conexion.php';
echo "<p><strong>PortuariaDemo (local):</strong> " . ($conn ? "OK" : "Error") . "</p>\n";
if (!$conn) {
    echo "<pre>" . print_r(sqlsrv_errors(), true) . "</pre>\n";
    exit;
}

// 2) Conexión externa (no usar @ para ver el error)
$connExterna = null;
$serverNameExterna = "VICTUS\VICTUS";
$connectionOptionsExterna = [
    "Database" => "PortuariaExterna",
    "CharacterSet" => "UTF-8",
    "Encrypt" => true,
    "TrustServerCertificate" => true
];
$connExterna = sqlsrv_connect($serverNameExterna, $connectionOptionsExterna);

if ($connExterna === false) {
    echo "<p><strong>PortuariaExterna (externa):</strong> Error de conexión.</p>\n";
    echo "<pre>Errores sqlsrv: " . print_r(sqlsrv_errors(), true) . "</pre>\n";
    echo "<p>Compruebe que la base de datos <code>PortuariaExterna</code> exista en SQL Server y que el usuario tenga acceso.</p>\n";
} else {
    echo "<p><strong>PortuariaExterna (externa):</strong> OK</p>\n";
    $sql = "SELECT TOP 1 id_empresa, empresa, ruc FROM dbo.reg_empresas WHERE estado = 1";
    $stmt = sqlsrv_query($connExterna, $sql);
    if ($stmt) {
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        echo "<p>Ejemplo de empresa en externa: " . ($row ? "id={$row['id_empresa']}, empresa={$row['empresa']}, ruc={$row['ruc']}" : "sin datos") . "</p>\n";
    }
    sqlsrv_close($connExterna);
}
?>
