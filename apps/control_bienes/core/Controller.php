<?php
/**
 * CONTROLLER.PHP - Controlador Base del Núcleo MVC
 * Provee utilidades comunes para renderizado, redirecciones, logs de auditoría y notificaciones del header.
 */

class Controller {
    protected string $module = 'Central';
    protected string $action = 'index';
    private bool $auditoriaRegistrada = false;
    private float $inicioSolicitud = 0.0;

    public function setModule(string $module) {
        $this->module = $module;
    }

    public function setAction(string $action) {
        $this->action = $action;
    }

    public function __construct() {
        $this->inicioSolicitud = microtime(true);
        // Registrar handlers de errores
        $this->registrarHandlersError();
        register_shutdown_function(function (): void {
            if ($this->auditoriaRegistrada) return;
            $route = (string)($_GET['route'] ?? 'inventario');
            if (in_array($route, ['mantener_sesion', 'notificaciones_marcar_leidas'], true)) return;
            $accion = (string)($_GET['action'] ?? $this->action ?? 'index');
            $metodo = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'CLI'));
            $estado = http_response_code();
            $resultado = $estado >= 400 ? 'ERROR_' . $estado : 'OK';
            require_once ROOT_PATH . 'modules/Bitacoras/models/BitacoraModel.php';
            (new BitacoraModel())->registrar(
                $metodo === 'GET' ? 'ACCESO' : 'ACCION',
                strtolower((string)$this->module),
                "Solicitud {$metodo} ejecutada en {$route} / {$accion}",
                ['route' => $route, 'action' => $accion, 'resultado' => $resultado, 'duracion_ms' => (microtime(true) - $this->inicioSolicitud) * 1000]
            );
        });
    }

    private function registrarHandlersError() {
        set_error_handler(function (int $errno, string $errstr, string $errfile, int $errline): bool {
            if (!(error_reporting() & $errno)) {
                return false;
            }
            $nivel = ($errno === E_WARNING || $errno === E_USER_WARNING) ? 'WARNING' : 'ERROR';
            
            // Cargar logger si es necesario
            require_once ROOT_PATH . 'modules/Bitacoras/models/LogModel.php';
            try {
                $logger = new Logger('sys');
                $logger->warning("[PHP {$errno}] {$errstr} en {$errfile}:{$errline}", 'set_error_handler');
            } catch (\Throwable $e) {
                error_log("[sys] WARNING: [PHP {$errno}] {$errstr} en {$errfile}:{$errline}");
            }
            
            $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH'])
                      || (isset($_GET['action']) && strpos($_GET['action'], 'Ajax') !== false)
                      || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);
            return APP_ENV === 'production' || $isAjax;
        });

        set_exception_handler(function (\Throwable $excepcion): void {
            require_once ROOT_PATH . 'modules/Bitacoras/models/LogModel.php';
            try {
                $logger = new Logger('sys');
                $logger->warning(
                    'Excepción no capturada: ' . $excepcion->getMessage(),
                    'set_exception_handler',
                    $excepcion
                );
            } catch (\Throwable $e) {
                error_log('[Logger] Excepción no capturada: ' . $excepcion->getMessage());
            }

            if (APP_ENV === 'production') {
                if (!headers_sent()) {
                    header('Location: index.php?route=error_sistema');
                }
                exit;
            }
        });
    }

    /**
     * Renderiza una vista dentro de la plantilla principal (layout)
     */
    protected function render($vista, $datos = [], $titulo = 'Sistema Portuario') {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $datos['_permisoVista'] = $this->obtenerPermisoVistaActual();
        extract($datos);

        // Compilar vista específica
        ob_start();
        
        // Intentar ubicar en módulos primero
        $vistaPath = ROOT_PATH . 'modules/' . $this->module . '/views/' . $vista . '.php';
        if (!file_exists($vistaPath)) {
            // Fallback a ruta global de vistas
            $vistaPath = ROOT_PATH . 'views/' . $vista . '.php';
        }

        if (file_exists($vistaPath)) {
            require $vistaPath;
        } else {
            echo "<div class='panel'><h3 style='color:var(--danger);'>Error: La vista '{$vista}' no existe en {$vistaPath}.</h3></div>";
        }
        $contenido = ob_get_clean();

        // Cargar alertas del Header
        $notificaciones = $this->cargarNotificacionesHeader();
        
        $vistasIds = isset($_SESSION['notificaciones_vistas']) ? $_SESSION['notificaciones_vistas'] : [];
        $notificacionesNoLeidas = [];
        foreach ($notificaciones as $n) {
            if (!in_array($n['id'], $vistasIds) && (!isset($n['visto']) || (int)$n['visto'] === 0)) {
                $notificacionesNoLeidas[] = $n['id'];
            }
        }

        // Renderizar el layout global (Central)
        $layoutPath = ROOT_PATH . 'modules/Central/views/layout.php';
        if (!file_exists($layoutPath)) {
            $layoutPath = ROOT_PATH . 'views/layout.php';
        }
        
        if (file_exists($layoutPath)) {
            require $layoutPath;
        } else {
            echo $contenido;
        }
    }

    private function obtenerPermisoVistaActual(): array {
        $rol = strtolower((string)($_SESSION['rol'] ?? $_SESSION['usuario']['rol'] ?? ''));
        if ($rol === 'administrador') {
            return ['read' => true, 'create' => true, 'edit' => true, 'full' => true, 'readonly' => false];
        }

        $usuarioId = (int)($_SESSION['usuario_id'] ?? $_SESSION['usuario']['id'] ?? 0);
        $route = (string)($_GET['route'] ?? 'inventario');
        $aliases = [
            'talento' => 'talento_directorio', 'talento_crear' => 'talento_directorio',
            'talento_guardar' => 'talento_directorio', 'talento_editar' => 'talento_directorio',
            'talento_borrar' => 'talento_directorio', 'talento_eliminar' => 'talento_directorio',
            'talento_imprimir_ficha' => 'talento_directorio', 'cabeceras' => 'inv_maestros',
        ];
        $route = $aliases[$route] ?? $route;
        $scope = 'general';
        if ($route === 'inv_maestros') {
            $scope = (string)($_GET['tabla'] ?? $_POST['tabla'] ?? 'categorias');
            if ($scope === 'busqueda_global') { $route = 'busqueda_global'; $scope = 'general'; }
        } elseif ($route === 'egresos') {
            $scope = (string)($_GET['vista'] ?? $_POST['vista'] ?? 'ordenes');
            if (!in_array($scope, ['ordenes', 'facturas', 'ingresos', 'kardex'], true)) $scope = 'ordenes';
        }

        $regla = [];
        if ($usuarioId > 0) {
            require_once ROOT_PATH . 'modules/Credenciales/models/PermisoModel.php';
            $matriz = (new PermisoModel())->obtenerMatrizUsuario($usuarioId);
            $regla = $matriz[$route][$scope] ?? $matriz[$route]['*'] ?? [];
        }
        $full = !empty($regla['full']);
        $read = $full || !empty($regla['read']);
        $create = $full || !empty($regla['create']);
        $edit = $full || !empty($regla['edit']);
        return ['read' => $read, 'create' => $create, 'edit' => $edit, 'full' => $full, 'readonly' => $read && !$create && !$edit];
    }

    /**
     * Renderiza una vista de acta de forma aislada
     */
    protected function renderActa($vista, $datos = []) {
        extract($datos);
        
        $vistaPath = ROOT_PATH . 'modules/' . $this->module . '/views/' . $vista . '.php';
        if (!file_exists($vistaPath)) {
            $vistaPath = ROOT_PATH . 'views/' . $vista . '.php';
        }

        if (file_exists($vistaPath)) {
            require $vistaPath;
        } else {
            echo "<h3 style='color:var(--danger);text-align:center;margin-top:50px;'>Error: El acta/reporte '{$vista}' no existe.</h3>";
        }
        exit;
    }

    /**
     * Retorna una respuesta JSON estructurada y finaliza la ejecución
     */
    protected function jsonResponse($data, $statusCode = 200) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($statusCode);
        echo json_encode($data);
        exit;
    }

    /**
     * Redirecciona a una ruta específica con un mensaje toast
     */
    protected function redirect($route, $mensaje = null, $tipoMensaje = 'info') {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if ($mensaje !== null) {
            $_SESSION['toast'] = [
                'mensaje' => $mensaje,
                'tipo' => $tipoMensaje
            ];
        }

        header("Location: " . route($route));
        exit;
    }

    /**
     * Registra una auditoría en la bitácora
     */
    protected function registrarAuditoria($tipo, $modulo, $desc) {
        $this->auditoriaRegistrada = true;
        require_once ROOT_PATH . 'modules/Bitacoras/models/BitacoraModel.php';
        $bitacora = new BitacoraModel();
        return $bitacora->registrar($tipo, $modulo, $desc, [
            'route' => (string)($_GET['route'] ?? 'inventario'),
            'action' => (string)($_GET['action'] ?? $this->action ?? 'index'),
            'resultado' => 'OK',
            'duracion_ms' => (microtime(true) - $this->inicioSolicitud) * 1000,
        ]);
    }

    /**
     * Carga las alertas para la campanita del header
     */
    private function cargarNotificacionesHeader(): array {
        $notificaciones = [];
        require_once ROOT_PATH . 'core/Database.php';
        
        try {
            $dbConnection = Database::getInstance()->getConnection();
            $eliminadasIds = isset($_SESSION['notificaciones_eliminadas']) ? $_SESSION['notificaciones_eliminadas'] : [];
            $driver = DB_DRIVER;
            $usuarioNotificaciones = (int)($_SESSION['usuario_id'] ?? $_SESSION['usuario']['id'] ?? 0);
            $adminNotificaciones = strtolower((string)($_SESSION['rol'] ?? $_SESSION['usuario']['rol'] ?? '')) === 'administrador';
            if ($adminNotificaciones) {

            // 1. Alertas de stock crítico (cantidad = 0) o bajo (cantidad <= 5)
            $sqlLimit = ($driver === 'sqlsrv') ? "TOP 8" : "";
            $sqlLimitEnd = ($driver !== 'sqlsrv') ? "LIMIT 8" : "";
            
            $sqlStock = "SELECT {$sqlLimit} i.id, i.secuencial, i.nombre, i.cantidad, cat.nombre AS categoria
                         FROM inv_inventario i
                         JOIN inv_categorias cat ON i.categoria_id = cat.id
                         WHERE i.activo = 1 AND i.cantidad <= 5
                         ORDER BY i.cantidad ASC, i.id DESC
                         {$sqlLimitEnd}";
                         
            $itemsStock = $dbConnection->query($sqlStock)->fetchAll();

            foreach ($itemsStock as $item) {
                $notifId = 'stk_' . $item['id'];
                if (in_array($notifId, $eliminadasIds)) continue;
                $esCritico = ((int)$item['cantidad'] === 0);
                $notificaciones[] = [
                    'id'         => $notifId,
                    'item_id'    => $item['id'],
                    'tipo'       => $esCritico ? 'critica' : 'advertencia',
                    'categoria'  => 'stock',
                    'titulo'     => $esCritico ? 'STOCK AGOTADO' : 'Stock Crítico',
                    'mensaje'    => $esCritico
                        ? "El bien <strong>{$item['nombre']}</strong> no tiene existencias disponibles."
                        : "Existencias bajas de <strong>{$item['nombre']}</strong>: solo quedan <strong>{$item['cantidad']}</strong> unidades.",
                    'secuencial' => $item['secuencial'],
                    'badge'      => $esCritico ? 'Agotado' : "Quedan {$item['cantidad']}",
                    'visto'      => 0
                ];
            }

            // 2. Alertas de equipos fuera de servicio o en mantenimiento
            $sqlMaint = "SELECT {$sqlLimit} i.id, i.secuencial, i.nombre, est.descripcion AS estado
                         FROM inv_inventario i
                         JOIN inv_estados est ON i.estado_id = est.idestado
                         WHERE i.activo = 1
                           AND (LOWER(est.descripcion) LIKE '%mantenimiento%'
                                OR LOWER(est.descripcion) LIKE '%fuera de servicio%'
                                OR LOWER(est.descripcion) LIKE '%inactivo%')
                         ORDER BY i.id DESC
                         {$sqlLimitEnd}";
            $itemsMaint = $dbConnection->query($sqlMaint)->fetchAll();

            foreach ($itemsMaint as $item) {
                $notifId = 'mnt_' . $item['id'];
                if (in_array($notifId, $eliminadasIds)) continue;
                $esCritico = (stripos($item['estado'], 'fuera') !== false || stripos($item['estado'], 'inactivo') !== false);
                $notificaciones[] = [
                    'id'         => $notifId,
                    'item_id'    => $item['id'],
                    'tipo'       => $esCritico ? 'critica' : 'advertencia',
                    'categoria'  => 'mantenimiento',
                    'titulo'     => strtoupper($item['estado']),
                    'mensaje'    => "El bien <strong>{$item['nombre']}</strong> ({$item['secuencial']}) está reportado como <em>{$item['estado']}</em>.",
                    'secuencial' => $item['secuencial'],
                    'badge'      => $item['estado'],
                    'visto'      => 0
                ];
            }

            }

            // 3. Alertas transaccionales persistentes
            require_once ROOT_PATH . 'modules/Central/models/NotificacionModel.php';
            $notifModel = new NotificacionModel();
            $recentEvents = $notifModel->obtenerRecientes(15, $usuarioNotificaciones, $adminNotificaciones);
            foreach ($recentEvents as $ev) {
                $notifId = 'evt_' . $ev['id'];
                if (in_array($notifId, $eliminadasIds)) continue;
                $notificaciones[] = [
                    'id'         => $notifId,
                    'item_id'    => null,
                    'tipo'       => $ev['tipo'],
                    'categoria'  => $ev['categoria'],
                    'titulo'     => $ev['titulo'],
                    'mensaje'    => $ev['mensaje'],
                    'secuencial' => $ev['secuencial'],
                    'badge'      => ucfirst($ev['categoria']),
                    'visto'      => (int)$ev['visto']
                ];
            }

            // Ordenar: críticas primero, luego por ID descendente
            usort($notificaciones, function($a, $b) {
                if ($a['tipo'] === 'critica' && $b['tipo'] !== 'critica') return -1;
                if ($a['tipo'] !== 'critica' && $b['tipo'] === 'critica') return 1;
                return strcmp($b['id'], $a['id']);
            });

        } catch (Exception $e) {
            // Silenciar errores de BD para no romper el renderizado
        }

        return $notificaciones;
    }

    /**
     * Consulta fn_TienePermisoNodo del portal (PORTAL_APM.dbo) en cross-DB
     * same-instance (nombre de 3 partes, sin linked server) para una sesión
     * puenteada desde el portal. id_modulo=12 fijo (Control de Bienes).
     */
    protected function tienePermisoPortal(int $idUsuarioPortal, int $opcion, int $nivelMin): bool {
        try {
            $conexionesPath = dirname(ROOT_PATH, 2) . '/config/connections.php';
            if (!is_file($conexionesPath)) return false;
            $conn = require $conexionesPath;
            $dbPortal = $conn['databases']['portal']['name'] ?? 'PORTAL_APM';

            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare(
                "SELECT {$dbPortal}.dbo.fn_TienePermisoNodo(?,12,?,0,0,?,1) AS ok"
            );
            $stmt->execute([$idUsuarioPortal, $opcion, $nivelMin]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row !== false && (bool)$row['ok'];
        } catch (Exception $e) {
            return false;
        }
    }
}
