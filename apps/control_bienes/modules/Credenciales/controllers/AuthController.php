<?php
/**
 * AuthController.php - Controlador de Autenticación y Cierre de Sesión
 */

require_once ROOT_PATH . 'modules/Credenciales/models/UsuarioModel.php';
require_once ROOT_PATH . 'modules/Central/models/NotificacionModel.php';
require_once ROOT_PATH . 'modules/Bitacoras/models/LogModel.php';

class AuthController extends Controller {
    private $usuarioModel;
    private $logger;

    public function __construct() {
        parent::__construct();
        $this->logger = new Logger('acc');
        $this->usuarioModel = new UsuarioModel();
    }

    /**
     * Pantalla de Login / Bloqueo
     */
    public function login() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (isset($_SESSION['usuario'])) {
            $this->redirect('inventario');
        }

        // Cargar vista directamente sin layout global
        $vistaPath = ROOT_PATH . 'modules/Credenciales/views/auth/login.php';
        if (!file_exists($vistaPath)) {
            $vistaPath = ROOT_PATH . 'views/inv_login.php';
        }

        if (file_exists($vistaPath)) {
            require $vistaPath;
        } else {
            echo "<h2>Error: La vista de Login no existe.</h2>";
        }
        exit;
    }

    /**
     * Procesa la autenticación del usuario
     */
    public function loginPost() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('inv_login', 'Método no permitido', 'error');
        }

        $usuarioInput = isset($_POST['usuario']) ? trim($_POST['usuario']) : '';
        $contrasenaInput = isset($_POST['contrasena']) ? $_POST['contrasena'] : '';

        $usuario = $this->usuarioModel->buscarPorUsuario($usuarioInput);

        if ($usuario && (int)$usuario['activo'] === 1 && password_verify($contrasenaInput, $usuario['contrasena'])) {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            $_SESSION['usuario'] = [
                'id'         => $usuario['id'],
                'secuencial' => $usuario['secuencial'],
                'nombre'     => $usuario['nombre'],
                'rol'        => $usuario['rol']
            ];
            $_SESSION['id_usuario']   = $usuario['id'];
            $_SESSION['usuario_id']   = $usuario['id'];
            $_SESSION['rol']          = $usuario['rol'];
            $_SESSION['ultimo_acceso'] = time();

            $this->registrarAuditoria('LOGIN', 'acc', "Sesión iniciada correctamente por {$usuario['nombre']} ({$usuario['rol']})");
            $this->logger->info("LOGIN_EXITOSO: {$usuario['nombre']} ({$usuario['rol']})", 'loginPost');
            
            $_notifModel = new NotificacionModel();
            $_notifModel->crear('info', 'seguridad', 'Acceso de Usuario', "El usuario <strong>{$usuario['nombre']}</strong> ({$usuario['rol']}) ha iniciado sesión.");

            $this->redirect('inventario', "¡Bienvenido de vuelta, {$usuario['nombre']}!", 'success');

        } else {
            $this->logger->warning("LOGIN_FALLIDO: intento con usuario '{$usuarioInput}'", 'loginPost');
            $this->redirect('inv_login', 'Usuario o contraseña incorrectos, o cuenta suspendida.', 'error');
        }
    }

    /**
     * Cierra la sesión activa
     */
    public function logout() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (isset($_SESSION['usuario'])) {
            $this->registrarAuditoria('LOGOUT', 'acc', "Sesión cerrada voluntariamente por " . $_SESSION['usuario']['nombre']);
        }

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
        $this->redirect('inv_login', 'Sesión cerrada correctamente.', 'info');
    }
}
