<?php
/**
 * Prueba directa: PortuariaExterna.dbo.reg_empresas (empresa, razonsocial, ruc).
 */
header('Content-Type: text/html; charset=UTF-8');

require_once __DIR__ . '/conexion/conexion_externa.php';
require_once __DIR__ . '/conexion/conexion.php';

$q = isset($_GET['q']) ? trim($_GET['q']) : '1790099900004';

echo "<h2>Prueba búsqueda empresa en PortuariaExterna (reg_empresas)</h2>\n";
echo "<p>Parámetro q = " . htmlspecialchars($q) . "</p>\n";

echo "<p><strong>\$connExterna:</strong> " . ($connExterna !== null ? "conectado" : "NULL (falló)") . "</p>\n";

if ($connExterna === null) {
    echo "<p>No se puede probar: conexión externa no disponible.</p>\n";
    exit;
}

$term = '%' . str_replace(['%', '_'], ['[%]', '[_]'], $q) . '%';
$sql = "SELECT TOP 30 id_empresa, empresa, razonsocial, ruc FROM dbo.reg_empresas WHERE (CAST(ISNULL(estado,1) AS TINYINT) = 1) AND (empresa LIKE ? OR ISNULL(razonsocial,'') LIKE ? OR ISNULL(ruc,'') LIKE ?) ORDER BY empresa";
$stmt = sqlsrv_query($connExterna, $sql, [$term, $term, $term]);

if ($stmt === false) {
    echo "<p><strong>Error sqlsrv_query:</strong></p><pre>" . print_r(sqlsrv_errors(), true) . "</pre>\n";
    exit;
}

$rows = [];
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $rows[] = $row;
}

echo "<p><strong>Filas encontradas:</strong> " . count($rows) . "</p>\n";
if (count($rows) > 0) {
    echo "<ul>\n";
    foreach ($rows as $r) {
        echo "<li>id=" . $r['id_empresa'] . ", empresa=" . htmlspecialchars($r['empresa']) . ", ruc=" . htmlspecialchars($r['ruc'] ?? '') . "</li>\n";
    }
    echo "</ul>\n";
} else {
    echo "<p>No se encontraron filas. Ejecute sql/refactor_db_externa_final.sql y verifique dbo.reg_empresas.</p>\n";
}
