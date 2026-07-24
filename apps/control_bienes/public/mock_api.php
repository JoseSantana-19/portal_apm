<?php
// public/mock_api.php
if (!headers_sent()) {
    header('Content-Type: application/json');
}

$ruc = isset($_GET['ruc']) ? trim($_GET['ruc']) : '';
if (empty($ruc)) {
    echo json_encode(['success' => false, 'mensaje' => 'RUC/Cédula requerido']);
    exit;
}

// Datos de demostración simulados para el microservicio del SRI/Registro Civil
$mockData = [
    '1301234567' => [
        'nombre' => 'JUAN PEREZ (Microservicio Persona)',
        'ruc' => '1301234567',
        'extra' => 'Persona Natural - Registro Civil'
    ],
    '1799999999001' => [
        'nombre' => 'TECNOLOGIAS GLOBALES S.A. (Microservicio Empresa)',
        'ruc' => '1799999999001',
        'extra' => 'Sociedad Anónima - SRI Activo'
    ]
];

if (isset($mockData[$ruc])) {
    $res = $mockData[$ruc];
    $res['success'] = true;
    echo json_encode($res);
} else {
    // Generar dinámicamente si no está en la lista de ejemplos
    $tipo = (strlen($ruc) === 13) ? 'Empresa Externa' : 'Usuario Externo';
    echo json_encode([
        'success' => true,
        'nombre' => 'ENTIDAD EXTERNA DEMO ' . $ruc . ' (' . $tipo . ')',
        'ruc' => $ruc,
        'extra' => 'Microservicio Externo - SRI / RegCivil'
    ]);
}
