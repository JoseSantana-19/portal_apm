<?php
require_once __DIR__ . '/../includes/bit_api_guard.php'; // Portal APM: sesion obligatoria

// apis/bit_motivos_api.php - Registro rápido de motivos (igual patrón que bit_destinos_api.php)

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../conexion/conexion.php';

function motivos_tiene_estado($conn)
{
    static $hasEstado = null;
    if ($hasEstado !== null) return $hasEstado;
    $st = @sqlsrv_query($conn, "SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = N'dbo' AND TABLE_NAME = N'motivos' AND COLUMN_NAME = N'estado'");
    $hasEstado = ($st && sqlsrv_fetch_array($st, SQLSRV_FETCH_ASSOC));
    return $hasEstado;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'message' => 'Método no permitido']);
    exit;
}

$descripcion = isset($_POST['descripcion']) ? trim($_POST['descripcion']) : '';

if ($descripcion === '') {
    echo json_encode(['ok' => false, 'message' => 'La descripción del motivo es obligatoria']);
    exit;
}

$sqlInsert = motivos_tiene_estado($conn)
    ? "INSERT INTO dbo.bit_motivos (descripcion, estado) OUTPUT INSERTED.id_motivo VALUES (?, 1)"
    : "INSERT INTO dbo.bit_motivos (descripcion) OUTPUT INSERTED.id_motivo VALUES (?)";
$stmt = sqlsrv_query($conn, $sqlInsert, [$descripcion]);

if ($stmt === false) {
    echo json_encode(['ok' => false, 'message' => 'Error al guardar el motivo']);
    exit;
}

$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
$idMotivo = $row ? (int)$row['id_motivo'] : 0;

echo json_encode([
    'ok' => true,
    'data' => [
        'id_motivo'   => $idMotivo,
        'descripcion' => $descripcion
    ]
]);
exit;
