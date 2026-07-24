<?php
/**
 * AccesoController. Handles Control de Acceso Module.
 * Full-featured administration of users, roles, menu options and form permissions.
 * Integrates error daily logging (hidden in production) and Windows authentication support.
 */

require_once dirname(__DIR__, 2) . '/models/Acceso_/AccesoModel.php';
require_once dirname(__DIR__, 2) . '/models/Acceso_/Menu.php';

class AccesoController extends Controller {

    private AccesoModel $model;

    public function __construct() {
        parent::__construct();
        $this->requireAuth();
        $this->model = new AccesoModel();
    }

    /**
     * Helper to log exceptions to daily log files (stored in controllers/Control_acceso/log/)
     */
    private function logError(Throwable $e): void {
        $logDir = __DIR__ . '/log';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }
        $logFile = $logDir . '/log_' . date('Y-m-d') . '.txt';
        
        $timestamp = date('Y-m-d H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $userId = $_SESSION['user_id'] ?? 'Guest';
        $message = $e->getMessage();
        $file = $e->getFile();
        $line = $e->getLine();
        $trace = $e->getTraceAsString();
        
        $logContent = "================================================================\n";
        $logContent .= "[{$timestamp}] [IP: {$ip}] [USER_ID: {$userId}]\n";
        $logContent .= "File: {$file} (Line: {$line})\n";
        $logContent .= "Error: {$message}\n";
        $logContent .= "Trace:\n{$trace}\n";
        $logContent .= "================================================================\n\n";
        
        file_put_contents($logFile, $logContent, FILE_APPEND);
    }

    /**
     * Check if debug mode is active in app config
     */
    private function isDebug(): bool {
        $configFile = dirname(__DIR__, 2) . '/config/app.php';
        if (file_exists($configFile)) {
            $config = require $configFile;
            return (bool)($config['debug'] ?? true);
        }
        return true;
    }

    /**
     * Helper to handle response errors securely based on environment
     */
    private function handleError(Throwable $e, string $customMsg = 'Error al procesar la solicitud.'): void {
        $this->logError($e);
        
        $errorMsg = $customMsg;
        if ($this->isDebug()) {
            $errorMsg .= ' Detalle: ' . $e->getMessage() . ' en ' . $e->getFile() . ':' . $e->getLine();
        } else {
            $errorMsg .= ' El incidente ha sido registrado en la bitácora de errores del módulo.';
        }

        if ($this->isAjax()) {
            $this->json(['error' => $errorMsg], 500);
        } else {
            $_SESSION['error_flash'] = $errorMsg;
            $this->redirect('/control-acceso');
        }
    }

    /**
     * Main index view
     */
    public function index(): void {
        try {
            $userId = $_SESSION['user_id'];
            $menuObj = new Menu();
            $userMenu = $menuObj->getUserMenu($userId);

            // Fetch administrative list data from DB
            $usuarios = $this->model->getUsuarios();
            $roles = $this->model->getGruposRoles();
            $departamentos = $this->model->getDepartamentosModulos();
            $menuOpciones = $this->model->getMenuOpciones();
            $formularios = $this->model->getFormularios();

            // Stats from DB Mocked
            $dbStats = [
                'entries_today' => count($usuarios),
                'active_visits' => count($roles),
                'security_alerts' => 0,
                'avg_in_out' => '8.2 hrs'
            ];

            // Render view with data
            $this->render('Acceso_Control_acceso/index', [
                'title' => 'Control de Acceso & Seguridad — Portal APM',
                'userMenu' => $userMenu,
                'usuarios' => $usuarios,
                'roles' => $roles,
                'departamentos' => $departamentos,
                'menuOpciones' => $menuOpciones,
                'formularios' => $formularios,
                'stats' => $dbStats
            ]);
        } catch (Throwable $e) {
            $this->handleError($e, 'No se pudo cargar el panel de Control de Acceso.');
        }
    }

    /**
     * Save or Edit a User
     */
    public function guardarUsuario(): void {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método de solicitud no válido.');
            }
            
            $data = $_POST;
            $this->model->saveUsuario($data);
            
            if ($this->isAjax()) {
                $this->json(['success' => 'Usuario guardado exitosamente.']);
            } else {
                $_SESSION['success_flash'] = 'Usuario guardado exitosamente.';
                $this->redirect('/control-acceso');
            }
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al guardar el usuario.');
        }
    }

    /**
     * Save or Edit a Role
     */
    public function guardarRol(): void {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método de solicitud no válido.');
            }

            $data = $_POST;
            $this->model->saveRol($data);

            if ($this->isAjax()) {
                $this->json(['success' => 'Perfil/Rol guardado exitosamente.']);
            } else {
                $_SESSION['success_flash'] = 'Perfil/Rol guardado exitosamente.';
                $this->redirect('/control-acceso');
            }
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al guardar el perfil/rol.');
        }
    }

    /**
     * Get menu permissions for a specific role (AJAX GET)
     */
    public function getPermisosMenu(): void {
        try {
            $rolId = isset($_GET['id_grupo_rol']) ? (int)$_GET['id_grupo_rol'] : 0;
            if (!$rolId) {
                throw new Exception('ID de rol no especificado.');
            }

            $permisos = $this->model->getPermisosMenuPorRol($rolId);
            $this->json(['success' => true, 'permisos' => $permisos]);
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al recuperar permisos de menú.');
        }
    }

    /**
     * Save menu permissions for a role
     */
    public function guardarPermisosMenu(): void {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método de solicitud no válido.');
            }

            $rolId = isset($_POST['id_grupo_rol']) ? (int)$_POST['id_grupo_rol'] : 0;
            if (!$rolId) {
                throw new Exception('ID de rol no especificado.');
            }

            $permisos = $_POST['permisos'] ?? []; // Array mapping [menu_op_id] => [1 or 2 or 0]
            $this->model->savePermisosMenu($rolId, $permisos);

            if ($this->isAjax()) {
                $this->json(['success' => 'Permisos de menú guardados exitosamente.']);
            } else {
                $_SESSION['success_flash'] = 'Permisos de menú guardados exitosamente.';
                $this->redirect('/control-acceso');
            }
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al guardar permisos de menú.');
        }
    }

    /**
     * Get form permissions for a specific role (AJAX GET)
     */
    public function getPermisosFormulario(): void {
        try {
            $rolId = isset($_GET['id_grupo_rol']) ? (int)$_GET['id_grupo_rol'] : 0;
            if (!$rolId) {
                throw new Exception('ID de rol no especificado.');
            }

            $permisos = $this->model->getPermisosFormularioPorRol($rolId);
            $this->json(['success' => true, 'permisos' => $permisos]);
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al recuperar permisos de formulario.');
        }
    }

    /**
     * Save form permissions for a role
     */
    public function guardarPermisosFormulario(): void {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método de solicitud no válido.');
            }

            $rolId = isset($_POST['id_grupo_rol']) ? (int)$_POST['id_grupo_rol'] : 0;
            if (!$rolId) {
                throw new Exception('ID de rol no especificado.');
            }

            $permisos = $_POST['permisos'] ?? []; // Array mapping [form_id] => [0 to 4]
            $this->model->savePermisosFormulario($rolId, $permisos);

            if ($this->isAjax()) {
                $this->json(['success' => 'Permisos de formulario guardados exitosamente.']);
            } else {
                $_SESSION['success_flash'] = 'Permisos de formulario guardados exitosamente.';
                $this->redirect('/control-acceso');
            }
        } catch (Throwable $e) {
            $this->handleError($e, 'Error al guardar permisos de formulario.');
        }
    }
}
