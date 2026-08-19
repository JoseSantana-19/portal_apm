<?php
/**
 * GLOBALS.PHP - Variables globales y constantes de rutas
 * Carga las variables desde el archivo .env y define funciones de ayuda para URLs y redirecciones.
 */

// Definición de ROOT_PATH (siempre con barra diagonal final)
define('ROOT_PATH', str_replace('\\', '/', dirname(__DIR__) . '/'));

// Cargador simple de .env
function loadEnv() {
    $envPath = ROOT_PATH . '.env';
    if (file_exists($envPath)) {
        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            // Ignorar comentarios
            if (strpos(trim($line), '#') === 0) continue;

            // Dividir por el primer signo =
            $parts = explode('=', $line, 2);
            if (count($parts) === 2) {
                $name = trim($parts[0]);
                $value = trim($parts[1]);

                // Limpiar comillas si las hay
                $value = trim($value, "\"'");

                if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
                    putenv("{$name}={$value}");
                    $_ENV[$name] = $value;
                    $_SERVER[$name] = $value;
                }
            }
        }
    }
}

// Cargar las variables del .env
loadEnv();

// Definir constantes de base de datos desde el entorno con fallbacks seguros
define('APP_ENV',           getenv('APP_ENV') ?: 'development');
define('APP_DEBUG',         (getenv('APP_DEBUG') === 'true' || getenv('APP_DEBUG') === '1'));
define('APP_VERSION',       '4.0.0');
date_default_timezone_set(getenv('APP_TIMEZONE') ?: 'America/Guayaquil');

// Base URL (Autodetectar si no está definida en .env)
$baseUrl = getenv('APP_URL');
if (!$baseUrl) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || ($_SERVER['SERVER_PORT'] ?? 80) == 443) ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    $projectPath = str_replace('\\', '/', dirname($scriptName));
    if (substr($projectPath, -1) !== '/') {
        $projectPath .= '/';
    }
    $baseUrl = $protocol . $host . $projectPath;
}
define('BASE_URL', rtrim($baseUrl, '/') . '/');

// Raíz del PORTAL (no de esta app) — para /login, /logout, /api/keepalive
// y para reusar assets vendorizados ahí (ej. SweetAlert2). Mismo cálculo
// que BASE_URL arriba, recortando el sufijo "/apps/control_bienes".
$__portalBase = preg_replace('#/apps/control_bienes(/.*)?$#', '', rtrim($baseUrl, '/'));
define('PORTAL_ROOT_URL', $__portalBase);

// Ruta de Logs y Almacenamiento
define('APP_LOGS_PATH', ROOT_PATH . 'logs/');
define('STORAGE_PATH',  ROOT_PATH . 'storage/');

// Config central del sistema (config/connections.php en la raíz de
// portal_apm) — el .env de este módulo solo hace falta si querés pisar
// algún valor puntual; si no está, todo cae en la config central.
$__connPath = ROOT_PATH . '../../config/connections.php';
$__conn     = file_exists($__connPath) ? require $__connPath : null;

// Definir constantes de Base de Datos
define('DB_DRIVER',       getenv('DB_DRIVER') ?: 'sqlsrv');
define('DB_HOST',         getenv('DB_HOST') ?: ($__conn['server_default'] ?? 'localhost'));
define('DB_PORT',         getenv('DB_PORT') ?: '1433');
define('DB_NAME',         getenv('DB_NAME') ?: 'inventario');
define('DB_USER',         getenv('DB_USER') ?: ($__conn['credentials']['user'] ?? ''));
define('DB_PASS',         getenv('DB_PASS') ?: ($__conn['credentials']['pass'] ?? ''));
define('DB_TRUST_CERT',   (getenv('DB_TRUST_CERT') === 'true' || getenv('DB_TRUST_CERT') === '1' || ($__conn['options']['trust_cert'] ?? false)));
define('DB_SQLITE_PATH',  getenv('DB_SQLITE_PATH') ? (ROOT_PATH . getenv('DB_SQLITE_PATH')) : (ROOT_PATH . 'database.sqlite'));

// Configuración de PHP según entorno
if (APP_ENV === 'production') {
    ini_set('display_errors',         '0');
    ini_set('display_startup_errors', '0');
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
} else {
    ini_set('display_errors',         '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
}
ini_set('log_errors', '1');

/**
 * Retorna la URL absoluta para un recurso o ruta del sistema
 */
function url($path = '') {
    return BASE_URL . ltrim($path, '/');
}

/**
 * Retorna la URL formateada para un enrutamiento en particular
 */
function route($route, $action = '', $params = []) {
    $url = 'index.php?route=' . urlencode($route);
    if ($action) {
        $url .= '&action=' . urlencode($action);
    }
    if (!empty($params)) {
        $url .= '&' . http_build_query($params);
    }
    return url($url);
}

// Cargar automáticamente todos los helpers globales de la carpeta helpers/
foreach (glob(ROOT_PATH . 'helpers/*.php') as $filename) {
    require_once $filename;
}
