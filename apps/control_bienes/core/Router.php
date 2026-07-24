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

        // Validar tiempo de inactividad
        require_once ROOT_PATH . 'modules/Central/models/ConfigModel.php';
        $configModel = new ConfigModel();
        $tiempoPermitido = (int)$configModel->obtener('tiempo_inactividad', 600); // 10 minutos por defecto

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
     * Middleware de Permisos por Rol
     */
    private function checkPermisos(string $route) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $usuarioId = isset($_SESSION['usuario_id']) ? (int)$_SESSION['usuario_id'] : 0;
        $rol       = isset($_SESSION['rol'])        ? $_SESSION['rol']              : '';

        // Administrador tiene acceso total a todo
        if (strtolower($rol) === 'administrador' || $usuarioId === 0) {
            return;
        }

        require_once ROOT_PATH . 'modules/Credenciales/models/PermisoModel.php';
        $permisoModel = new PermisoModel();
        if (!$permisoModel->tienePermiso($usuarioId, $route, $rol)) {
            $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH'])
                      || isset($_GET['is_ajax'])
                      || isset($_POST['is_ajax'])
                      || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Acceso denegado', 'route' => $route]);
                exit;
            }

            $_SESSION['toast'] = [
                'mensaje' => 'No tienes permiso para acceder a esta sección. Contacta al Administrador.',
                'tipo'    => 'error'
            ];
            header('Location: index.php?route=inventario');
            exit;
        }
    }
}
