<?php
/**
 * INDEX.PHP - Punto de Entrada Principal (Router)
 * Inicializa la sesión, carga constantes globales, inicializa la BD y despacha las rutas modulares.
 * Compatible con PHP 7.4, 8.3 y 8.5
 */

require_once dirname(__DIR__, 2) . '/helpers/polyfills_php74.php';

// Iniciar sesión global
if (session_status() === PHP_SESSION_NONE) {
    // La expiración funcional se controla en Router según la actividad de cada
    // usuario. Evitamos que el recolector de PHP elimine antes una sesión válida.
    ini_set('session.gc_maxlifetime', '43200');
    ini_set('session.use_strict_mode', '1');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

/* ── PUENTE DE SESIÓN PORTAL APM ─────────────────────────────────────────
   App embebida en el Portal APM: si el usuario ya inició sesión en el
   portal (login central CORE_Usuarios + sp_Login), se puebla la sesión
   propia de esta app y NO se pide un segundo login. El rol se mapea desde
   el nivel de jerarquía del portal. Sin sesión del portal, la app sigue
   funcionando con su login propio (inv_usuarios).                        */
if (empty($_SESSION['usuario']) && !empty($_SESSION['user_id'])) {
    $nivelPortal = (int)($_SESSION['nivel_jerarquia'] ?? 0);
    $rolApp = $nivelPortal >= 3 ? 'Administrador' : ($nivelPortal === 2 ? 'Supervisor' : 'Operador');
    $_SESSION['usuario'] = [
        'id'         => (int)$_SESSION['user_id'],
        'secuencial' => 'APM-' . (int)$_SESSION['user_id'],
        'nombre'     => (string)($_SESSION['nombre_completo'] ?? $_SESSION['nombre_usuario'] ?? 'Usuario Portal'),
        'rol'        => $rolApp,
    ];
    $_SESSION['id_usuario']    = (int)$_SESSION['user_id'];
    $_SESSION['usuario_id']    = (int)$_SESSION['user_id'];
    $_SESSION['rol']           = $rolApp;
}
// Con sesión del portal activa, refrescar SIEMPRE el marcador de actividad:
// el control de inactividad de esta app hace $_SESSION = [] (borraría también
// la sesión del portal) si ve ultimo_acceso viejo.
if (!empty($_SESSION['user_id'])) {
    $_SESSION['ultimo_acceso'] = time();
    $_SESSION['last_activity'] = time();   // contador del portal, coherente
}

// Cargar configuración y constantes globales del sistema
require_once __DIR__ . '/config/globals.php';

// Asegurar la inicialización completa de la Base de Datos
require_once __DIR__ . '/core/Database.php';
Database::getInstance(); 

// Importar y ejecutar el Enrutador Modular Centralizado
require_once __DIR__ . '/core/Router.php';
$router = new Router();
$router->dispatch();
