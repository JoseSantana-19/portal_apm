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
        $rutasPublicas = ['inv_login', 'login_post', 'logout', 'mantener_sesion', 'perfil_foto', 'error_sistema', 'notificaciones_marcar_leidas', 'notificaciones_vaciar'];
        if (!in_array($route, $rutasPublicas)) {
            $this->checkPermisosGranular($route, $action);
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
        // usuario > módulo > global, ver /admin/inactividad del portal), con
        // override nativo propio de este módulo para cuentas no puenteadas
        // (ver InvParametro::obtenerInactividadSegundos). Cacheado en sesión
        // 5 min para no consultar la BD en cada request.
        require_once ROOT_PATH . 'modules/Central/models/ConfigModel.php';
        $configModel = new ConfigModel();
        $puenteada = !empty($_SESSION['user_id']);
        $idUsuarioActual = (int)($_SESSION['usuario']['id'] ?? $_SESSION['usuario_id'] ?? 0);
        if (!isset($_SESSION['_inactividad_segundos']) || (time() - ($_SESSION['_inactividad_resuelto_en'] ?? 0)) >= 300) {
            $_SESSION['_inactividad_segundos']    = $configModel->obtenerInactividadSegundos($idUsuarioActual, 600, $puenteada);
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
     * nivel_crud mínimo), usada SOLO para sesiones puenteadas desde el
     * portal (ver checkPermisosPuente). Ruta sin entrada -> sin gating
     * adicional acá (queda abierta a cualquier usuario puenteado
     * autenticado, igual que ya pasaba con rutas no mapeadas antes de esta
     * actualización). Pantallas nuevas de esta actualización (abastecimiento,
     * facturas, proveedores, notas de pedido, etc.) todavía no tienen nodo
     * MOIS propio — cuentas NATIVAS de Bienes sí quedan cubiertas al
     * granular por el sistema propio de origen (ver checkPermisosGranular).
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
    ];

    /**
     * Permisos para sesiones puenteadas desde el portal: cross-DB
     * fn_TienePermisoNodo (ya resuelve rol + override individual configurado
     * en /admin/usuarios del portal), a nivel de pantalla completa según
     * POLITICAS — no por sub-alcance granular como el sistema nativo.
     */
    private function checkPermisosPuente(string $route, string $action): void {
        $politica = self::POLITICAS[$route] ?? null;
        if ($politica === null) {
            return;
        }

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

        require_once ROOT_PATH . 'core/Controller.php';
        require_once ROOT_PATH . 'core/Model.php';
        $probe = new class extends Controller {
            public function check(int $idUsuario, int $opcion, int $nivelMin): bool {
                return $this->tienePermisoPortal($idUsuario, $opcion, $nivelMin);
            }
        };
        if (!$probe->check((int)$_SESSION['user_id'], $politica['opcion'], $nivelMin)) {
            $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) || isset($_GET['is_ajax']) || isset($_POST['is_ajax'])
                      || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);
            if ($isAjax) {
                header('Content-Type: application/json'); http_response_code(403);
                echo json_encode(['error' => 'Acceso denegado', 'route' => $route]); exit;
            }
            $_SESSION['toast'] = ['mensaje' => 'No tienes permiso para realizar esta acción. Contacta al Administrador.', 'tipo' => 'error'];
            header('Location: index.php?route=inventario');
            exit;
        }
    }

    /**
     * Middleware de Permisos por Rol
     */
    private function checkPermisosGranular(string $route, string $action = 'index') {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $rol = (string)($_SESSION['rol'] ?? $_SESSION['usuario']['rol'] ?? '');
        if (strtolower($rol) === 'administrador') return;

        // Sesión puenteada desde el portal: rama cross-DB propia, no el
        // sistema granular nativo de abajo (esa cuenta no existe en
        // inv_usuarios/inv_permisos_detalle).
        if (!empty($_SESSION['user_id'])) {
            $this->checkPermisosPuente($route, $action);
            return;
        }

        $usuarioId = (int)($_SESSION['usuario_id'] ?? $_SESSION['usuario']['id'] ?? 0);
        if ($usuarioId <= 0) $this->denegarPermiso($route, 'No fue posible validar los permisos de tu cuenta.');

        [$permisoRoute, $scope] = $this->resolverAlcancePermiso($route, $action);
        $operacion = $this->resolverOperacionPermiso($action, $route);
        if ($operacion === 'delete') $this->denegarPermiso($permisoRoute, 'La eliminación está reservada al Administrador.');

        require_once ROOT_PATH . 'modules/Credenciales/models/PermisoModel.php';
        $modelo = new PermisoModel();
        if (!$modelo->tienePermisoAccion($usuarioId, $permisoRoute, $scope, $operacion, $rol)) {
            $etiquetas = ['read' => 'consultar', 'create' => 'crear registros en', 'edit' => 'editar o procesar'];
            $this->denegarPermiso($permisoRoute, 'No tienes permiso para '.($etiquetas[$operacion] ?? 'acceder a').' esta sección.');
        }
    }

    private function resolverAlcancePermiso(string $route, string $action): array {
        $aliases = ['talento'=>'talento_directorio','talento_crear'=>'talento_directorio','talento_guardar'=>'talento_directorio','talento_editar'=>'talento_directorio','talento_borrar'=>'talento_directorio','talento_eliminar'=>'talento_directorio','talento_imprimir_ficha'=>'talento_directorio','cabeceras'=>'inv_maestros'];
        $routePermiso = $aliases[$route] ?? $route;
        $scope = 'general';
        if ($routePermiso === 'inv_maestros') {
            $scope = (string)($_POST['tabla'] ?? $_GET['tabla'] ?? 'categorias');
            if ($scope === 'busqueda_global') return ['busqueda_global', 'general'];
        } elseif ($routePermiso === 'egresos') {
            if (in_array($action, ['guardarOrdenCompra','editarOrdenCompra','aprobarOrdenCompra'], true)) {
                return ['ordenes_compra', 'general'];
            }
            if (in_array($action, ['guardarFacturaCompra','editarFacturaCompra','verDocumentoFactura','ingresarFacturaBodega'], true)) {
                return ['ingresos', 'general'];
            }
            if (in_array($action, ['despacharNota','marcarSinExistencias','obtenerNota'], true)) {
                return ['egresos', 'general'];
            }
            $porAccion = [
                'guardarOrdenCompra'=>'ordenes', 'editarOrdenCompra'=>'ordenes', 'aprobarOrdenCompra'=>'ordenes',
                'guardarFacturaCompra'=>'facturas', 'editarFacturaCompra'=>'facturas', 'verDocumentoFactura'=>'facturas',
                'ingresarFacturaBodega'=>'ingresos',
            ];
            $scope = $porAccion[$action] ?? (string)($_GET['vista'] ?? $_POST['vista'] ?? 'ordenes');
            if (!in_array($scope, ['ordenes','facturas','ingresos','kardex'], true)) $scope = 'ordenes';
        }
        return [$routePermiso, $scope];
    }

    private function resolverOperacionPermiso(string $action, string $route = ''): string {
        if ($action === 'guardarFacturaIngreso') return !empty($_POST['factura_id']) ? 'edit' : 'create';
        $accion = strtolower($action === 'index' ? $route : $action);
        if (strpos($accion, 'eliminar') !== false || strpos($accion, 'borrar') !== false) return 'delete';
        if ($accion === 'crear' || strpos($accion, '_crear') !== false) return 'create';
        if (strpos($accion, 'editar') !== false || in_array($accion, ['aprobarordencompra','ingresarfacturabodega','despacharnota','marcarsinexistencias','ejecutarcorte','reiniciar','test'], true)) return 'edit';
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            if (in_array($accion, ['guardarordencompra','guardarfacturacompra','guardarsolicitud'], true)) return 'create';
            if (strpos($accion, 'guardar') !== false || strpos($accion, 'registrar') !== false || strpos($accion, 'agregar') !== false) return (!empty($_POST['id']) || !empty($_POST['usuario_id']) || !empty($_POST['empId']) || !empty($_POST['periodo_id']) || !empty($_POST['secuencial_id'])) ? 'edit' : 'create';
            return 'edit';
        }
        return 'read';
    }

    private function denegarPermiso(string $route, string $mensaje): void {
        $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) || isset($_GET['is_ajax']) || isset($_POST['is_ajax']) || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);
        if ($isAjax) {
            header('Content-Type: application/json'); http_response_code(403);
            echo json_encode(['error'=>'Acceso denegado','mensaje'=>$mensaje,'route'=>$route]); exit;
        }
        $_SESSION['toast'] = ['mensaje'=>$mensaje.' Contacta al Administrador.','tipo'=>'error'];
        require_once ROOT_PATH . 'modules/Credenciales/models/PermisoModel.php';
        $modelo = new PermisoModel();
        $usuarioId = (int)($_SESSION['usuario_id'] ?? 0);
        $rutas = $modelo->obtenerPermisosUsuario($usuarioId);
        $matriz = $modelo->obtenerMatrizUsuario($usuarioId);
        $destino = '';
        foreach ($rutas as $rutaPermitida) {
            if ($rutaPermitida === 'busqueda_global') { $destino = 'index.php?route=inv_maestros&tabla=busqueda_global'; break; }
            if ($rutaPermitida === 'egresos') {
                foreach (['ordenes','facturas','ingresos','kardex'] as $scope) {
                    $regla = $matriz['egresos'][$scope] ?? $matriz['egresos']['*'] ?? [];
                    if (!empty($regla['read']) || !empty($regla['full'])) { $destino = 'index.php?route=egresos&vista='.$scope; break 2; }
                }
            }
            if ($rutaPermitida === 'inv_maestros') {
                foreach (['categorias','productos','proveedores','unidades','tipos_iva','grupo_centros_consumo','centros_consumo'] as $scope) {
                    $regla = $matriz['inv_maestros'][$scope] ?? $matriz['inv_maestros']['*'] ?? [];
                    if (!empty($regla['read']) || !empty($regla['full'])) { $destino = 'index.php?route=inv_maestros&tabla='.$scope; break 2; }
                }
            }
            $destino = 'index.php?route='.urlencode($rutaPermitida); break;
        }
        if ($destino !== '') { header('Location: '.$destino); exit; }
        http_response_code(403);
        echo '<h2>Acceso denegado</h2><p>'.htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8').'</p>';
        exit;
    }

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
