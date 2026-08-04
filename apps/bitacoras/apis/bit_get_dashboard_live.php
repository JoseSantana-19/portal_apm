<?php
/**
 * JSON para dashboard jefe (polling ~10s).
 */
ob_start();
require_once __DIR__ . '/../includes/bit_auth_guard.php';
require_once __DIR__ . '/../includes/bit_auth_permissions.php';
require_once __DIR__ . '/../includes/bit_dashboard_jefe_data.php';
require_once __DIR__ . '/../conexion/conexion.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

if (!apm_can_acceder_dashboard_jefe()) {
    http_response_code(403);
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    echo json_encode(['ok' => false, 'message' => 'Acceso denegado.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$kpis = apm_dashboard_jefe_kpis($conn);
$charts = apm_dashboard_jefe_series_semana($conn);
$movimientos = apm_dashboard_jefe_movimientos($conn, 10);

while (ob_get_level() > 0) {
    ob_end_clean();
}
echo json_encode([
    'ok' => true,
    'kpis' => $kpis,
    'charts' => $charts,
    'movimientos' => $movimientos,
    'server_time' => date('c'),
], JSON_UNESCAPED_UNICODE);
