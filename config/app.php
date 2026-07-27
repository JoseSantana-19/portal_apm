<?php
/**
 * ╔══════════════════════════════════════════════════════════╗
 *  PORTAL APM — Configuración Única del Sistema
 *  Edita SOLO este archivo para cambiar entorno de pruebas.
 * ╚══════════════════════════════════════════════════════════╝
 */

// ─── Aplicación ──────────────────────────────────────────────
define('ROOT', dirname(__DIR__));
define('APP_NAME', 'Portal APM');
define('APP_SHORT_NAME', 'APM');
define('APP_VERSION', '2.0.0');
define('APP_ENV', 'development');   // 'development' | 'production'
define('DEBUG_MODE', true);            // false en producción

// ─── URL del servidor ─────────────────────────────────────────
// 'auto'  = detecta protocolo + host + puerto + subcarpeta automaticamente.
//           Funciona igual en php -S, XAMPP (http://localhost/portal_apm),
//           WampServer e IIS — sin tocar nada al cambiar de entorno.
// O escribe una URL fija, ej: 'http://localhost:8080'
$__appUrl = 'auto';

if ($__appUrl === 'auto') {
    $__https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['SERVER_PORT'] ?? '') == 443);
    $__scheme = $__https ? 'https' : 'http';
    $__host = $_SERVER['HTTP_HOST'] ?? 'localhost';   // incluye :puerto si aplica
    $__base = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    if ($__base === '/' || $__base === '.')
        $__base = '';
    define('APP_URL', $__scheme . '://' . $__host . rtrim($__base, '/'));
} else {
    define('APP_URL', $__appUrl);
}

// ─── Zona horaria ────────────────────────────────────────────
define('DEFAULT_TIMEZONE', 'America/Guayaquil');

// ─── Sesiones ────────────────────────────────────────────────
define('SESSION_TIMEOUT', 1800);   // segundos inactividad → cerrar sesión (30 min)
define('SESSION_HOURS_EXPIRA', 8);      // horas vida del token en CORE_Sesiones

// ─── Base de Datos (SQL Server) ──────────────────────────────
// Única fuente real: config/connections.php. NO se sube a git (depende de
// la máquina) — copiá config/connections.example.php la primera vez.
// Para cambiar de servidor, credenciales o nombre de alguna BD, editar
// SOLO connections.php — estas constantes son un espejo para el resto del
// código nativo del portal.
if (!file_exists(ROOT . '/config/connections.php')) {
    die('Falta config/connections.php — copiá config/connections.example.php a config/connections.php y ajustalo a tu servidor SQL Server.');
}
$__conn = require ROOT . '/config/connections.php';

define('DB_SERVER', $__conn['server_default']);
define('DB_NAME', $__conn['databases']['portal']['name']);
define('DB_TH_NAME', $__conn['databases']['talento']['name']);
define('DB_USER', $__conn['credentials']['user']);
define('DB_PASS', $__conn['credentials']['pass']);
define('DB_TRUST_CERT', $__conn['options']['trust_cert']);
define('DB_ENCRYPT', $__conn['options']['encrypt']);

// ─── Módulo Portuaria (Bitácoras CCTV/Visitas/Rondas — integrado de portuaria_demoV4) ──
define('DB_PORTUARIA_NAME', $__conn['databases']['portuaria']['name']);
define('DB_PORTUARIA_EXT_NAME', $__conn['databases']['portuaria_ext']['name']);

// Constantes de rutas compat demoV4 (usadas por código portado del módulo Portuaria)
define('MODULES_PATH', ROOT . '/modules');
if (!defined('ROOT_PATH'))
    define('ROOT_PATH', ROOT);
if (!defined('PUBLIC_PATH'))
    define('PUBLIC_PATH', ROOT . '/public');
if (!defined('VIEWS_LAYOUT_PATH'))
    define('VIEWS_LAYOUT_PATH', ROOT . '/views/layouts');

// Constantes de negocio del módulo Portuaria (antes includes/bit_config_constants.php)
if (!defined('ID_DEPARTAMENTO_ADMIN'))
    define('ID_DEPARTAMENTO_ADMIN', 1);
if (!defined('ID_EDIFICIO_ADMIN'))
    define('ID_EDIFICIO_ADMIN', 8);
if (!defined('ID_DEPARTAMENTO_GERENCIA'))
    define('ID_DEPARTAMENTO_GERENCIA', 5);

// URL del Dashboard Ejecutivo en Python (Streamlit) — analytics/dashboard.py
if (!defined('APM_DASHBOARD_EJECUTIVO_URL')) {
    define('APM_DASHBOARD_EJECUTIVO_URL', 'http://localhost:8501/?embed=true');
}
