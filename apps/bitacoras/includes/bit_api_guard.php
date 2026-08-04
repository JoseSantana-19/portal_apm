<?php
// includes/bit_api_guard.php (Portal APM — integración portuaria_demoV4)
//
// Guard de sesión para las APIs legacy del módulo Portuaria. En el proyecto
// origen estos endpoints eran públicos; en el portal integrador exigen sesión
// activa del portal (cookie compartida) y responden 401 JSON si no la hay.
require_once __DIR__ . '/bit_auth_session.php';
require_once __DIR__ . '/bit_dev_auto_login.php';

if (empty($_SESSION['apm_auth']['id_usuario'])) {
    http_response_code(401);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'message' => 'Sesión no activa.'], JSON_UNESCAPED_UNICODE);
    exit;
}
