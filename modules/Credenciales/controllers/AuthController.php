<?php
class AuthController extends Controller {

    private UsuarioModel $model;

    public function __construct() {
        parent::__construct();
        $this->model = new UsuarioModel();
    }

    public function showLogin(): void {
        if (SessionHelper::isLoggedIn()) {
            $this->redirect('/dashboard');
        }
        $this->render('Credenciales/login/index', [
            'error'    => SessionHelper::getFlash('login_error'),
            'csrf'     => $this->csrfToken(),
        ], false);
    }

    public function login(): void {
        $this->verifyCsrf();

        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($username === '' || $password === '') {
            SessionHelper::flash('login_error', 'Ingrese usuario y contraseña.');
            $this->redirect('/login');
        }

        $result = $this->model->authenticate($username, $password);

        if (!empty($result['error'])) {
            SessionHelper::flash('login_error', $result['error']);
            $this->redirect('/login');
        }

        SessionHelper::login($result);
        $_SESSION['session_token'] = $result['session_token'];

        if ($result['requiere_cambio']) {
            $this->redirect('/cambiar-contrasena');
        }

        $this->redirect('/dashboard');
    }

    /**
     * POST /api/keepalive — llamado por el aviso de inactividad (botón
     * "Seguir conectado"). Refresca last_activity sin recargar la página y
     * devuelve el tiempo restante recalculado.
     */
    public function keepalive(): void {
        $this->requireAuth(); // ya refresca last_activity; si expiró, corta acá con 401 JSON (isAjax).
        [$timeout, $aviso] = $this->resolveInactividad();
        $this->json(['ok' => true, 'timeoutSegundos' => $timeout, 'avisoSegundos' => $aviso]);
    }

    public function logout(): void {
        if (!empty($_SESSION['session_token'])) {
            $this->model->revokeSession($_SESSION['session_token']);
        }
        $this->destroySession();
        $this->redirect('/login');
    }

    // API: cambiar tema (AJAX)
    public function setTheme(): void {
        $this->requireAuth();
        $tema = trim($_POST['tema'] ?? '');
        $this->model->updateTheme(SessionHelper::userId(), $tema);
        $_SESSION['tema'] = $tema;
        $this->json(['ok' => true]);
    }

    public function perfil(): void {
        $this->requireAuth();
        $usuario = $this->model->findById(SessionHelper::userId());
        $this->render('Credenciales/perfil/index', [
            'pageTitle' => 'Mi Perfil',
            'usuario'   => $usuario,
            'csrf'      => $this->csrfToken(),
        ]);
    }

    public function actualizarPerfil(): void {
        $this->requireAuth();
        $this->verifyCsrf();

        $id = SessionHelper::userId();
        $correo = trim($_POST['correo'] ?? '');
        if (filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            Database::getInstance()->query(
                'UPDATE CORE_Usuarios SET correo=? WHERE id_usuario=?',
                [[$correo, SQLSRV_PARAM_IN], [$id, SQLSRV_PARAM_IN]]
            );
        }
        SessionHelper::flash('success', 'Perfil actualizado.');
        $this->redirect('/perfil');
    }

    public function showCambiarContrasena(): void {
        $this->requireAuth();
        $this->render('Credenciales/perfil/cambiar_contrasena', [
            'pageTitle' => 'Cambiar Contraseña',
            'csrf'      => $this->csrfToken(),
            'error'     => SessionHelper::getFlash('pass_error'),
        ]);
    }

    public function cambiarContrasena(): void {
        $this->requireAuth();
        $this->verifyCsrf();

        $actual  = $_POST['contrasena_actual']  ?? '';
        $nueva   = $_POST['contrasena_nueva']   ?? '';
        $confirm = $_POST['contrasena_confirma'] ?? '';

        if ($nueva !== $confirm || strlen($nueva) < 8) {
            SessionHelper::flash('pass_error', 'La nueva contraseña debe tener al menos 8 caracteres y coincidir.');
            $this->redirect('/cambiar-contrasena');
        }

        $id   = SessionHelper::userId();
        $user = $this->model->findById($id);
        $db   = Database::getInstance();

        $stmt = $db->query('SELECT hash_contrasena FROM CORE_Usuarios WHERE id_usuario=?', [[$id, SQLSRV_PARAM_IN]]);
        $row  = $db->fetch($stmt);
        if (!$row || !SecurityHelper::verifyPassword($actual, $row['hash_contrasena'])) {
            SessionHelper::flash('pass_error', 'Contraseña actual incorrecta.');
            $this->redirect('/cambiar-contrasena');
        }

        $hash = SecurityHelper::hashPassword($nueva);
        $db->query(
            '{CALL sp_CambiarContrasena(?,?,?,?)}',
            [[$id, SQLSRV_PARAM_IN], [$hash, SQLSRV_PARAM_IN], ['', SQLSRV_PARAM_IN], [5, SQLSRV_PARAM_IN]]
        );

        SessionHelper::flash('success', 'Contraseña actualizada correctamente.');
        $this->redirect('/perfil');
    }
}
