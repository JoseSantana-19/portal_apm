<?php
/**
 * ROUTER.PHP - Enrutador Centralizado y Middleware de Sesión/Permisos
 * Administra la resolución de rutas modulares, control de inactividad de sesión y permisos.
 */

class Router {
    public function dispatch() {
        // 1. Cargar mapa de rutas
        $routes = require ROOT_PATH . 'config/routes.php';

        // 2. Obtener ruta y acción solicitadas
        $route = isset($_GET['route']) ? $_GET['route'] : 'inventario';
        $action = isset($_GET['action']) ? $_GET['action'] : 'index';

        // Validar ruta en el mapa
        if (!isset($routes[$route])) {
            if ($route === 'error_sistema') {
                require_once ROOT_PATH . 'config/globals.php';
                $vistaPath = ROOT_PATH . 'modules/Central/views/error.php';
                if (!file_exists($vistaPath)) {
                    $vistaPath = ROOT_PATH . 'views/inv_error.php';
                }
                require $vistaPath;
                exit;
            }
            $route = 'inventario';
        }

        // 3. Middleware de Seguridad e Inactividad
        $this->checkSessionAndInactivity($route);

        // 4. Middleware de Permisos
        $rutasPublicas = ['inv_login', 'login_post', 'logout', 'error_sistema', 'notificaciones_marcar_leidas', 'notificaciones_vaciar'];
        if (!in_array($route, $rutasPublicas)) {
            $this->checkPermisos($route);
        }

        $routeInfo = $routes[$route];
        $module = $routeInfo['module'];
        $controllerName = $routeInfo['controller'];
        $defaultAction = $routeInfo['action'] ?? 'index';
        
        $actionName = isset($_GET['action']) ? $_GET['action'] : $defaultAction;

        // 5. Resolver controlador y archivo
        $controllerFile = ROOT_PATH . 'modules/' . $module . '/controllers/' . $controllerName . '.php';

        if (file_exists($controllerFile)) {
            require_once ROOT_PATH . 'core/Controller.php';
            require_once ROOT_PATH . 'core/Model.php';
            require_once $controllerFile;

            $controllerInstance = new $controllerName();
            $controllerInstance->setModule($module);
            $controllerInstance->setAction($actionName);

            // Mapeos especiales de acciones por compatibilidad
            if ($route === 'items' && $actionName === 'index') {
                $actionName = 'catalogo';
            } elseif ($route === 'inv_maestros' && $actionName === 'index') {
                $actionName = 'maestros';
            }

            // Ejecutar acción
            if (method_exists($controllerInstance, $actionName)) {
                $controllerInstance->$actionName();
            } else {
                $controllerInstance->index();
            }
        } else {
            // Registrar error de enrutamiento crítico
            require_once ROOT_PATH . 'modules/Bitacoras/models/LogModel.php';
            try {
                $logger = new Logger('sys');
                $logger->warning("Controlador '{$controllerName}' no encontrado en: {$controllerFile}", 'Router::dispatch');
            } catch (\Throwable $e) {
                error_log("Controlador '{$controllerName}' no encontrado en: {$controllerFile}");
            }
            
            if (APP_ENV === 'production') {
                header('Location: index.php?route=error_sistema');
                exit;
            }
            die("Error crítico de enrutamiento: No se encontró el controlador '{$controllerName}' en {$controllerFile}.");
        }
    }

    /**
     * Middleware de Sesión y Control de Inactividad
     */
    private function checkSessionAndInactivity(string $route) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $publicRoutes = ['inv_login', 'login_post', 'error_sistema'];
        if (in_array($route, $publicRoutes)) {
            return;
        }

        // Si NO está logueado, redirigir a Login
        if (!isset($_SESSION['usuario'])) {
            header("Location: index.php?route=inv_login");
            exit;
        }

        // Validar tiempo de inactividad — centralizado en PORTAL_APM (cascada
        // usuario > módulo > global, ver /admin/inactividad del portal).
        // Cacheado en sesión 5 min para no consultar la BD en cada request.
        require_once ROOT_PATH . 'modules/Central/models/ConfigModel.php';
        $configModel = new ConfigModel();
        $idUsuarioActual = (int)($_SESSION['usuario']['id'] ?? $_SESSION['id_usuario'] ?? 0);
        if (!isset($_SESSION['_inactividad_segundos']) || (time() - ($_SESSION['_inactividad_resuelto_en'] ?? 0)) >= 300) {
            $_SESSION['_inactividad_segundos']    = $configModel->obtenerInactividadSegundos($idUsuarioActual);
            $_SESSION['_inactividad_aviso']       = $configModel->obtenerInactividadAvisoSegundos($idUsuarioActual);
            $_SESSION['_inactividad_resuelto_en'] = time();
        }
        $tiempoPermitido = (int)$_SESSION['_inactividad_segundos'];

        if (isset($_SESSION['ultimo_acceso'])) {
            $tiempoInactivo = time() - $_SESSION['ultimo_acceso'];

            if ($tiempoInactivo > $tiempoPermitido) {
                $_SESSION = [];
                if (ini_get("session.use_cookies")) {
                    $params = session_get_cookie_params();
                    setcookie(session_name(), '', time() - 42000,
                        $params["path"], $params["domain"],
                        $params["secure"], $params["httponly"]
                    );
                }
                session_destroy();

                session_start();
                $_SESSION['toast'] = [
                    'mensaje' => 'Tu sesión ha expirado por inactividad. Por favor ingresa de nuevo.',
                    'tipo' => 'warning'
                ];

                header("Location: index.php?route=inv_login&timeout=1");
                exit;
            }
        }

        // Actualizar marca de último acceso
        $_SESSION['ultimo_acceso'] = time();
    }

    /**
     * Tabla de políticas (route, action) -> (opción MOIS bajo id_modulo=12,
     * nivel_crud mínimo). Las acciones no listadas para una route caen al
     * 'default' de esa route (típicamente 1 = solo Ver). Sin entrada para
     * la route completa -> no gateada aquí (ver $rutasPublicas en dispatch()).
     */
    private const POLITICAS = [
        'dashboard'          => ['opcion' => 1,  'default' => 1, 'acciones' => []],
        'inventario'         => ['opcion' => 2,  'default' => 1, 'acciones' => ['guardar' => 'crud', 'eliminar' => 4]],
        'items'              => ['opcion' => 3,  'default' => 1, 'acciones' => ['guardar' => 'crud', 'eliminar' => 4]],
        'inv_items_sistema'  => ['opcion' => 4,  'default' => 1, 'acciones' => ['guardar' => 'crud']],
        'cabeceras'          => ['opcion' => 5,  'default' => 1, 'acciones' => ['guardar' => 'crud', 'eliminar' => 4]],
        'inv_maestros'       => ['opcion' => 6,  'default' => 1, 'acciones' => ['guardar' => 'crud', 'eliminar' => 4]],
        'ingresos'           => ['opcion' => 7,  'default' => 1, 'acciones' => ['guardar' => 2]],
        'egresos'            => ['opcion' => 8,  'default' => 1, 'acciones' => ['guardar' => 2]],
        'talento'            => ['opcion' => 9,  'default' => 1, 'acciones' => []],
        'talento_directorio' => ['opcion' => 9,  'default' => 1, 'acciones' => []],
        'talento_crear'      => ['opcion' => 9,  'default' => 2, 'acciones' => []],
        'talento_guardar'    => ['opcion' => 9,  'default' => 1, 'acciones' => ['guardar' => 'crud']],
        'talento_editar'     => ['opcion' => 9,  'default' => 3, 'acciones' => []],
        'talento_borrar'     => ['opcion' => 9,  'default' => 4, 'acciones' => []],
        'talento_eliminar'   => ['opcion' => 9,  'default' => 4, 'acciones' => []],
        'talento_imprimir_ficha' => ['opcion' => 9, 'default' => 1, 'acciones' => []],
        'inv_bitacora'       => ['opcion' => 10, 'default' => 1, 'acciones' => []],
        'reportes'           => ['opcion' => 11, 'default' => 1, 'acciones' => []],
        'inv_periodos'       => ['opcion' => 12, 'default' => 1, 'acciones' => ['guardar' => 2, 'ejecutarCorte' => 3]],
        'inv_secuenciales'   => ['opcion' => 13, 'default' => 1, 'acciones' => ['test' => 3, 'reiniciar' => 3]],
        'usuarios'           => ['opcion' => 14, 'default' => 1, 'acciones' => ['guardar' => 'crud', 'eliminar' => 4, 'guardarParametro' => 4]],
        'inv_permisos'       => ['opcion' => 15, 'default' => 1, 'acciones' => ['guardar' => 3, 'obtenerPermisos' => 1]],
        'inv_permisos_rol'   => ['opcion' => 15, 'default' => 3, 'acciones' => []],
    ];

    /**
     * Middleware de Permisos: rama dual segun el tipo de cuenta.
     * - Puenteada desde el portal (tiene user_id del portal en sesion):
     *   fn_TienePermisoNodo cross-DB, ya resuelve rol + override de usuario.
     * - Nativa de Bienes (inv_usuarios, sin cedula real en el portal):
     *   cascada local usuario > rol contra inv_permisos / inv_permisos_rol.
     */
    private function checkPermisos(string $route) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $rol = isset($_SESSION['rol']) ? $_SESSION['rol'] : '';
        if (strtolower($rol) === 'administrador') {
            return;
        }

        $politica = self::POLITICAS[$route] ?? null;
        if ($politica === null) {
            // Route sin politica declarada (ej. inv_lookup): sin gating adicional.
            return;
        }

        $action = isset($_GET['action']) ? $_GET['action'] : '';
        $nivelMin = $politica['default'];
        if ($action !== '' && array_key_exists($action, $politica['acciones'])) {
            $spec = $politica['acciones'][$action];
            if ($spec === 'crud') {
                $tieneId = !empty($_POST['id']) || !empty($_GET['id']);
                $nivelMin = $tieneId ? 3 : 2;
            } else {
                $nivelMin = (int)$spec;
            }
        }

        $puenteada = !empty($_SESSION['user_id']);
        $ok = false;

        if ($puenteada) {
            require_once ROOT_PATH . 'core/Controller.php';
            require_once ROOT_PATH . 'core/Model.php';
            $probe = new class extends Controller {
                public function check(int $idUsuario, int $opcion, int $nivelMin): bool {
                    return $this->tienePermisoPortal($idUsuario, $opcion, $nivelMin);
                }
            };
            $ok = $probe->check((int)$_SESSION['user_id'], $politica['opcion'], $nivelMin);
        } else {
            $usuarioId = isset($_SESSION['usuario_id']) ? (int)$_SESSION['usuario_id'] : 0;
            $rolId = isset($_SESSION['usuario']['rol_id']) ? (int)$_SESSION['usuario']['rol_id'] : 0;
            if ($usuarioId > 0) {
                require_once ROOT_PATH . 'modules/Credenciales/models/PermisoModel.php';
                $permisoModel = new PermisoModel();
                $ok = $permisoModel->tieneNivelNativo($usuarioId, $rolId, $rol, $route, $nivelMin);
            }
        }

        if (!$ok) {
            $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH'])
                      || isset($_GET['is_ajax'])
                      || isset($_POST['is_ajax'])
                      || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

            if ($isAjax) {
                header('Content-Type: application/json');
                http_response_code(403);
                echo json_encode(['error' => 'Acceso denegado', 'route' => $route]);
                exit;
            }

            $_SESSION['toast'] = [
                'mensaje' => 'No tienes permiso para realizar esta acción. Contacta al Administrador.',
                'tipo'    => 'error'
            ];
            header('Location: index.php?route=inventario');
            exit;
        }
    }
}
