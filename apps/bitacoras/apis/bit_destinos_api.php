<?php
require_once __DIR__ . '/../includes/bit_api_guard.php'; // Portal APM: sesion obligatoria

// apis/bit_destinos_api.php - Registro rápido de destinos

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../conexion/conexion.php';

function destinos_tiene_estado($conn)
{
    static $hasEstado = null;
    if ($hasEstado !== null) return $hasEstado;
    $st = @sqlsrv_query($conn, "SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = N'dbo' AND TABLE_NAME = N'bit_destinos' AND COLUMN_NAME = N'estado'");
    $hasEstado = ($st && sqlsrv_fetch_array($st, SQLSRV_FETCH_ASSOC));
    return $hasEstado;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'message' => 'Método no permitido']);
    exit;
}

$nombre = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';

if ($nombre === '') {
    echo json_encode(['ok' => false, 'message' => 'El nombre del destino es obligatorio']);
    exit;
}

$sqlInsert = destinos_tiene_estado($conn)
    ? "INSERT INTO dbo.bit_destinos (nombre, estado) OUTPUT INSERTED.id_destino VALUES (?, 1)"
    : "INSERT INTO dbo.bit_destinos (nombre) OUTPUT INSERTED.id_destino VALUES (?)";
$params = [$nombre];
$stmt = sqlsrv_query($conn, $sqlInsert, $params);

if ($stmt === false) {
    echo json_encode(['ok' => false, 'message' => 'Error al guardar el destino']);
    exit;
}

$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
$idDestino = $row ? (int)$row['id_destino'] : 0;

echo json_encode([
    'ok' => true,
    'data' => [
        'id_destino' => $idDestino,
        'nombre'     => $nombre
    ]
]);
exit;
