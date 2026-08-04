<?php
require_once __DIR__ . '/bit_auth_session.php';
require_once __DIR__ . '/bit_dev_auto_login.php';

if (empty($_SESSION['apm_auth']) || empty($_SESSION['apm_auth']['id_usuario'])) {
    // Login centralizado del Portal APM.
    if (!defined('APP_URL')) {
        require_once __DIR__ . '/../config/app.php';
    }
    header('Location: ' . APP_URL . '/login');
    exit;
}
