<?php
require_once __DIR__ . '/../includes/bit_api_guard.php'; // Portal APM: sesion obligatoria

/**
 * API para consultar funcionarios en el archivo Visual FoxPro rolmaes.DBF.
 * GET ?cedula=... → busca por NUM_CEDULA y devuelve JSON con cedula, nombre, cargo, DEPARTAMEN, seccion.
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/bit_lector_rolmaes_dbf.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['ok' => false, 'message' => 'Método no permitido']);
    exit;
}

$cedula = isset($_GET['cedula']) ? trim($_GET['cedula']) : '';
if ($cedula === '') {
    echo json_encode(['ok' => false, 'message' => 'Parámetro cedula requerido']);
    exit;
}

$datos = leer_funcionario_por_cedula_dbf($cedula, null);

if ($datos === null) {
    echo json_encode([
        'ok'    => true,
        'found' => false,
        'message' => 'No se encontró el funcionario con esa cédula en el archivo DBF.'
    ]);
    exit;
}

echo json_encode([
    'ok'    => true,
    'found' => true,
    'data'  => [
        'cedula' => $datos['cedula'],
        'nombre' => $datos['nombre'],
        'cargo'  => $datos['cargo'],
        'DEPARTAMEN' => isset($datos['DEPARTAMEN']) ? $datos['DEPARTAMEN'] : '',
        'seccion' => isset($datos['seccion']) ? $datos['seccion'] : ''
    ]
]);
