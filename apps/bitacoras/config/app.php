<?php
/**
 * apps/bitacoras/config/app.php — Configuración de la app embebida Bitácoras.
 * Edita SOLO este archivo para cambiar entorno de pruebas de este módulo.
 *
 * Patrón B (igual que apps/talento_humano y apps/control_bienes): proyecto
 * MVC propio, sesión compartida con el portal (sin login propio). BDs
 * PortuariaDemo/PortuariaExterna, credenciales SIEMPRE desde
 * config/connections.php central del portal — nunca hardcodeadas acá.
 */

define('ROOT', dirname(__DIR__));
define('APP_NAME', 'Bitácoras Portuarias');
define('APP_ENV', 'development');   // 'development' | 'production'
define('DEBUG_MODE', true);         // false en producción

// ─── URL del PORTAL (no de esta app) ──────────────────────────
// Controller::redirect()/requireAuth() usan APP_URL para volver al login
// central. Se detecta igual que el portal, pero recortando el sufijo
// "/apps/bitacoras" para quedar en la raíz del portal.
$__https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['SERVER_PORT'] ?? '') == 443);
$__scheme = $__https ? 'https' : 'http';
$__host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$__base = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$__base = preg_replace('#/apps/bitacoras(/.*)?$#', '', $__base);
if ($__base === '/' || $__base === '.') $__base = '';
define('APP_URL', $__scheme . '://' . $__host . rtrim($__base, '/'));

// ─── Zona horaria / sesión ─────────────────────────────────────
define('DEFAULT_TIMEZONE', 'America/Guayaquil');
define('SESSION_TIMEOUT', 1800);

// ─── Base de datos — única fuente real: config/connections.php ────────────
$__connFile = ROOT . '/../../config/connections.php';
if (!file_exists($__connFile)) {
    die('Falta config/connections.php en la raíz del portal — copiá config/connections.example.php y ajustalo.');
}
$__conn = require $__connFile;

// Database (core/Database.php) — usada para leer CORE_Usuarios/CORE_Departamentos
// del portal (puente de identidad, ver modules/Portuaria/models/Auth.php).
define('DB_SERVER', $__conn['server_default']);
define('DB_NAME', $__conn['databases']['portal']['name']);
define('DB_USER', $__conn['credentials']['user']);
define('DB_PASS', $__conn['credentials']['pass']);
define('DB_TRUST_CERT', $__conn['options']['trust_cert']);
define('DB_ENCRYPT', $__conn['options']['encrypt']);

// PortDatabase (modules/Portuaria/models/PortDatabase.php) — BDs propias del módulo.
define('DB_PORTUARIA_NAME', $__conn['databases']['portuaria']['name']);
define('DB_PORTUARIA_EXT_NAME', $__conn['databases']['portuaria_ext']['name']);

// ─── Constantes de rutas compat demoV4 (código portado del módulo) ────────
define('MODULES_PATH', ROOT . '/modules');
if (!defined('ROOT_PATH')) define('ROOT_PATH', ROOT);
if (!defined('PUBLIC_PATH')) define('PUBLIC_PATH', ROOT . '/public');
if (!defined('VIEWS_LAYOUT_PATH')) define('VIEWS_LAYOUT_PATH', ROOT . '/modules/Portuaria/views/layouts');

// ─── Constantes de negocio del módulo (antes includes/bit_config_constants.php) ──
if (!defined('ID_DEPARTAMENTO_ADMIN')) define('ID_DEPARTAMENTO_ADMIN', 1);
if (!defined('ID_EDIFICIO_ADMIN')) define('ID_EDIFICIO_ADMIN', 8);
if (!defined('ID_DEPARTAMENTO_GERENCIA')) define('ID_DEPARTAMENTO_GERENCIA', 5);

// URL del Dashboard Ejecutivo en Python (Streamlit) — analytics/dashboard.py
if (!defined('APM_DASHBOARD_EJECUTIVO_URL')) {
    define('APM_DASHBOARD_EJECUTIVO_URL', 'http://localhost:8501/?embed=true');
}
