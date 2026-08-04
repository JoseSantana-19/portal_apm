<?php
/**
 * apps/bitacoras — Front Controller
 * App embebida Patrón B (igual que apps/talento_humano y apps/control_bienes):
 * proyecto MVC propio, sesión compartida con el portal. Sin login propio —
 * PortController::__construct() exige sesión del portal (requireAuth) y
 * traduce esa sesión a $_SESSION['apm_auth'] vía Auth::hydrateFromPortal().
 */

if (PHP_SAPI === 'cli-server') {
    $__file = __DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if ($__file !== __DIR__ . '/' && is_file($__file)) {
        return false;
    }
}

require_once __DIR__ . '/config/app.php';

date_default_timezone_set(DEFAULT_TIMEZONE);
if (DEBUG_MODE) {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(0);
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Core class autoloader
require_once ROOT . '/core/Database.php';
require_once ROOT . '/core/Model.php';
require_once ROOT . '/core/View.php';
require_once ROOT . '/core/Router.php';
require_once ROOT . '/core/Controller.php';

// Module class autoloader
spl_autoload_register(function (string $class): void {
    $searchDirs = array_merge(
        glob(ROOT . '/modules/*/controllers', GLOB_ONLYDIR) ?: [],
        glob(ROOT . '/modules/*/models',      GLOB_ONLYDIR) ?: []
    );
    foreach ($searchDirs as $dir) {
        $file = "{$dir}/{$class}.php";
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

require_once ROOT . '/helpers/security_helper.php';
require_once ROOT . '/helpers/hub_charts_helper.php';

// Sin sesión del portal: redirigir al login central antes de tocar nada más.
if (empty($_SESSION['user_id'])) {
    header('Location: ' . APP_URL . '/login');
    exit;
}

$router = new Router();
require ROOT . '/routes.php';

try {
    $uri    = $_SERVER['REQUEST_URI'] ?? '/';
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    // Recortar el base path (subcarpeta XAMPP: /portal_apm/apps/bitacoras)
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    if ($scriptDir !== '/' && $scriptDir !== '' && str_starts_with($uri, $scriptDir)) {
        $uri = substr($uri, strlen($scriptDir));
    }
    if ($uri === '' || $uri[0] !== '/') $uri = '/' . $uri;

    $router->resolve($uri, $method);
} catch (Throwable $e) {
    if (DEBUG_MODE) {
        http_response_code(500);
        echo '<pre>' . htmlspecialchars($e->getMessage() . "\n" . $e->getTraceAsString()) . '</pre>';
    } else {
        http_response_code(500);
        echo 'Error interno.';
    }
}
