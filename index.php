<?php
/**
 * Portal APM — Front Controller
 * PHP 8.0+ | sqlsrv native | NO PDO | NO Composer required
 */

// php -S (servidor embebido): servir archivos reales directamente (apis/*.php,
// páginas legacy bit_*.php del módulo Portuaria, assets). En Apache esto lo
// resuelve .htaccess (RewriteCond !-f); acá se replica ese comportamiento.
if (PHP_SAPI === 'cli-server') {
    $__file = __DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if ($__file !== __DIR__ . '/' && is_file($__file)) {
        return false; // el servidor embebido ejecuta/sirve el archivo
    }
}

// 1. Core bootstrap — editar config/app.php para cambiar entorno
require_once __DIR__ . '/core/Env.php';   // se mantiene por compat con scripts PS1
require_once __DIR__ . '/config/app.php';

date_default_timezone_set(DEFAULT_TIMEZONE);
if (DEBUG_MODE) {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(0);
}

ini_set('session.gc_maxlifetime', SESSION_TIMEOUT);
ini_set('session.cookie_lifetime', 0);
session_start();

// 2. Core class autoloader
require_once ROOT . '/core/Database.php';
require_once ROOT . '/core/Model.php';
require_once ROOT . '/core/View.php';
require_once ROOT . '/core/Router.php';
require_once ROOT . '/core/Controller.php';
require_once ROOT . '/core/ModuleSecurity.php';
require_once ROOT . '/core/CatalogoModulos.php';
require_once ROOT . '/core/SyncPermisosModulo.php';

// 3. Module class autoloader (modules/ take priority; legacy controllers/ as fallback)
spl_autoload_register(function (string $class): void {
    $searchDirs = array_merge(
        glob(ROOT . '/modules/*/controllers', GLOB_ONLYDIR) ?: [],
        glob(ROOT . '/modules/*/models',      GLOB_ONLYDIR) ?: [],
        glob(ROOT . '/controllers/*',         GLOB_ONLYDIR) ?: [],
        glob(ROOT . '/models/*',              GLOB_ONLYDIR) ?: []
    );
    foreach ($searchDirs as $dir) {
        $file = "{$dir}/{$class}.php";
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// 4. Helpers (always loaded)
require_once ROOT . '/helpers/security_helper.php';
require_once ROOT . '/helpers/session_helper.php';
require_once ROOT . '/helpers/url_helper.php';
require_once ROOT . '/helpers/form_helper.php';
require_once ROOT . '/helpers/hub_charts_helper.php';

// 5. Routes + dispatch
$router = new Router();
require ROOT . '/routes.php';

try {
    $uri    = $_SERVER['REQUEST_URI'] ?? '/';
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    // Strip base path when running in a subdirectory (e.g. /portal_apm)
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    if ($scriptDir !== '/' && $scriptDir !== '' && str_starts_with($uri, $scriptDir)) {
        $uri = substr($uri, strlen($scriptDir));
    }
    if ($uri === '' || $uri[0] !== '/') $uri = '/' . $uri;

    $router->resolve($uri, $method);

} catch (Throwable $e) {
    if (DEBUG_MODE) {
        http_response_code(500);
        echo '<pre style="font-family:monospace;padding:20px;background:#fdf;border:2px solid #c00;color:#300;">';
        echo htmlspecialchars($e->getMessage()) . "\n\n";
        echo htmlspecialchars($e->getTraceAsString());
        echo '</pre>';
    } else {
        http_response_code(500);
        echo '<h1>500 — Error interno</h1><p>Contacte al Administrador de TI.</p>';
    }
}
