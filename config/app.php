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
// Instancia local:   '.\VICTUS' | '.\SQLEXPRESS' | '.\MSSQLSERVER' | 'localhost'
// Instancia remota:  '192.168.1.10\SQLEXPRESS,1433'
define('DB_SERVER', 'DESKTOP-S82QFJK\\SQLEXPRESS');
define('DB_NAME', 'PORTAL_APM');
define('DB_TH_NAME', 'Talento_Humano');  // BD separada del módulo Talento Humano (patrón Inventario)
define('DB_USER', 'sa');        // vacío = Windows Authentication
define('DB_PASS', '123');        // vacío = Windows Authentication
define('DB_TRUST_CERT', true);      // true para dev (cert autofirmado)
define('DB_ENCRYPT', false);     // true solo si el servidor usa SSL

// ─── Módulo Portuaria (Bitácoras CCTV/Visitas/Rondas — integrado de portuaria_demoV4) ──
define('DB_PORTUARIA_NAME', 'PortuariaDemo');     // BD principal del módulo (tablas bit_*)
define('DB_PORTUARIA_EXT_NAME', 'PortuariaExterna');  // BD externa APM (funcionarios/departamentos)

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
