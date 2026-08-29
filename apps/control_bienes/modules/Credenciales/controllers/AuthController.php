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

        if ($usuario && (int)$usuario['activo'] === 1 && verify_password_secure($contrasenaInput, $usuario['contrasena'])) {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            // Migración perezosa al esquema con pepper -- transparente,
            // ninguna cuenta existente se bloquea por este cambio.
            if (password_needs_rehash_secure((string)$usuario['contrasena'])) {
                Database::getInstance()->getConnection()
                    ->prepare('UPDATE inv_usuarios SET contrasena = :hash WHERE id = :id')
                    ->execute([':hash' => hash_password_secure($contrasenaInput), ':id' => $usuario['id']]);
            }

            session_regenerate_id(true);

            $contextoPerfil = $this->usuarioModel->obtenerContextoPerfil($usuario);
            $_SESSION['usuario'] = [
                'id'         => $usuario['id'],
                'secuencial' => $usuario['secuencial'],
                'nombre'     => $usuario['nombre'],
                'usuario'    => $usuario['usuario'],
                'rol'        => $usuario['rol'],
                'departamento' => $contextoPerfil['departamento'],
                'cargo'      => $contextoPerfil['cargo']
            ];
            $_SESSION['id_usuario']   = $usuario['id'];
            $_SESSION['usuario_id']   = $usuario['id'];
            $_SESSION['rol']          = $usuario['rol'];
            $_SESSION['ultimo_acceso'] = time();
            $_SESSION['inventario_sesion_token'] = bin2hex(random_bytes(16));

            // Login propio de este módulo (no vino del puente SSO del portal):
            // si el "usuario" con el que entró coincide con una cédula real de
            // PORTAL_APM.CORE_Usuarios, esta identidad SÍ tiene acceso al
            // portal (ej. el admin compartido usando este login directo) y el
            // layout debe mostrar "Volver al Portal APM". Si no coincide con
            // ninguna cédula (cuenta exclusiva de este módulo), el layout lo
            // oculta — no hay portal al que volver.
            $usuarioPortal = $this->obtenerUsuarioPortal($usuarioInput);
            $_SESSION['tiene_acceso_portal'] = $usuarioPortal !== null;

            // Puente de sesión en sentido INVERSO: el puente portal->Bienes
            // (apps/control_bienes/index.php) ya puebla la sesión de esta app
            // cuando existe sesión del portal. Pero al entrar por ACÁ (login
            // propio de Bienes) nunca se poblaban las claves de sesión del
            // portal — el botón "Volver al Portal APM" llevaba a un portal
            // sin sesión, que mostraba su login otra vez. Mismas claves que
            // usa SessionHelper::login() del lado nativo del portal.
            if ($usuarioPortal !== null) {
                $_SESSION['user_id']         = (int)$usuarioPortal['id_usuario'];
                $_SESSION['nombre_usuario']  = $usuarioPortal['nombre_usuario'];
                $_SESSION['nombre_completo'] = $usuarioPortal['nombre_completo'];
                $_SESSION['nivel_jerarquia'] = (int)$usuarioPortal['nivel_jerarquia'];
                $_SESSION['id_departamento'] = $usuarioPortal['id_departamento'];
                $_SESSION['tema']            = $usuarioPortal['tema_preferido'] ?? 'light';
                $_SESSION['last_activity']   = time();
                if (empty($_SESSION['_csrf_token'])) {
                    $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
                }
            }

            $this->registrarAuditoria('LOGIN', 'acc', "Sesión iniciada correctamente por {$usuario['nombre']} ({$usuario['rol']})");
            $this->logger->info("LOGIN_EXITOSO: {$usuario['nombre']} ({$usuario['rol']})", 'loginPost');
            
            $_notifModel = new NotificacionModel();
            $_notifModel->crear('info', 'seguridad', 'Acceso de Usuario', "El usuario <strong>{$usuario['nombre']}</strong> ({$usuario['rol']}) ha iniciado sesión.");

            $this->redirect('inventario', "¡Bienvenido de vuelta, {$usuario['nombre']}!", 'success');

        } else {
            $this->logger->warning("LOGIN_FALLIDO: intento con usuario '{$usuarioInput}'", 'loginPost');
            $this->redirect('inv_login', 'Cédula o contraseña incorrectas, o cuenta suspendida.', 'error');
        }
    }

    /**
     * ¿La cédula/usuario con la que se logueó existe en PORTAL_APM.CORE_Usuarios?
     * Determina si el login propio de este módulo corresponde a una identidad
     * que también tiene cuenta en el portal (mostrar "Volver al Portal APM" +
     * poblar la sesión del portal para que navegue ya autenticado) o es
     * exclusiva de Control de Bienes (null — no hay portal al que volver).
     */
    private function obtenerUsuarioPortal(string $usuarioInput): ?array {
        $usuarioInput = trim($usuarioInput);
        if ($usuarioInput === '') {
            return null;
        }
        try {
            $connFile = ROOT_PATH . '../../config/connections.php';
            if (!file_exists($connFile)) {
                return null;
            }
            $conn = require $connFile;
            $opts = ['Database' => $conn['databases']['portal']['name'], 'CharacterSet' => 'UTF-8', 'TrustServerCertificate' => true];
            if (!empty($conn['credentials']['user'])) {
                $opts['UID'] = $conn['credentials']['user'];
                $opts['PWD'] = $conn['credentials']['pass'];
            }
            $c = @sqlsrv_connect($conn['server_default'], $opts);
            if ($c === false) {
                return null;
            }
            $stmt = sqlsrv_query($c,
                'SELECT TOP 1 id_usuario, nombre_usuario, nombre_completo, nivel_jerarquia, id_departamento, tema_preferido
                 FROM CORE_Usuarios WHERE cedula = ? AND estado = 1',
                [$usuarioInput]
            );
            $row = $stmt ? sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC) : false;
            sqlsrv_close($c);
            return ($row === false || $row === null) ? null : $row;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Cierra la sesión activa
     */
    public function mantenerSesion() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

        if (!isset($_SESSION['usuario'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'expired' => true]);
            exit;
        }

        $_SESSION['ultimo_acceso'] = time();
        echo json_encode([
            'success' => true,
            'timestamp' => $_SESSION['ultimo_acceso']
        ]);
        exit;
    }

    public function logout() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $porInactividad = isset($_GET['timeout']) && (string)$_GET['timeout'] === '1';

        if (isset($_SESSION['usuario'])) {
            $motivo = $porInactividad ? 'por inactividad' : 'voluntariamente';
            $this->registrarAuditoria('LOGOUT', 'acc', "Sesión cerrada {$motivo} por " . $_SESSION['usuario']['nombre']);
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
        if ($porInactividad) {
            $this->redirect('inv_login', 'Tu sesión se cerró por inactividad. Ingresa nuevamente para continuar.', 'warning');
        }
        $this->redirect('inv_login', 'Sesión cerrada correctamente.', 'info');
    }
}
