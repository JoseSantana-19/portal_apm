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
        // ?timeout=1 lo agregan requireAuth() y SecurityHelper::verifyCsrf()
        // al mandar de vuelta acá por sesión vencida -- sin este mensaje el
        // usuario solo veía un login en blanco, sin explicación de por qué
        // se cerró la sesión anterior.
        $error = SessionHelper::getFlash('login_error');
        if ($error === null && ($_GET['timeout'] ?? '') === '1') {
            $error = 'Tu sesión se cerró por inactividad. Ingresa nuevamente.';
        }
        $this->render('Credenciales/login/index', [
            'error'    => $error,
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

        if (!empty($result['mfa_required'])) {
            // Credenciales correctas, falta el segundo factor -- NO se llama
            // a SessionHelper::login() todavía (eso es lo que hace que el
            // usuario "no esté adentro" hasta confirmar el código). Estado
            // pendiente expira solo en 5 min, con límite de intentos.
            $_SESSION['_mfa_pending'] = [
                'id_usuario'       => $result['id_usuario'],
                'nombre_usuario'   => $result['nombre_usuario'],
                'nombre_completo'  => $result['nombre_completo'],
                'nivel_jerarquia'  => $result['nivel_jerarquia'],
                'id_departamento'  => $result['id_departamento'],
                'tema_preferido'   => $result['tema_preferido'],
                'requiere_cambio'  => $result['requiere_cambio'],
                'intentos'         => 0,
                'expira'           => time() + 300,
            ];
            $this->redirect('/login/verificar');
        }

        SessionHelper::login($result);
        $_SESSION['session_token']       = $result['session_token'];
        // Marca de tiempo de "MFA recién confirmado" -- la usa el gate de
        // cambio de módulo (ver requireMfaFresco() en Controller.php). Un
        // usuario sin MFA activo nunca la necesita, pero fijarla acá no
        // hace daño y evita tener que distinguir el caso en ese gate.
        $_SESSION['_mfa_verificado_en']  = time();

        if ($result['requiere_cambio']) {
            $this->redirect('/cambiar-contrasena');
        }

        $this->redirect('/dashboard');
    }

    /** GET /login/verificar — pantalla del código de 6 dígitos (2do factor). */
    public function showMfaChallenge(): void {
        $pending = $this->pendingMfaOrRedirect();
        $this->render('Credenciales/login/mfa', [
            'nombre' => $pending['nombre_completo'],
            'csrf'   => $this->csrfToken(),
            'error'  => SessionHelper::getFlash('mfa_error'),
        ], false);
    }

    /** POST /login/verificar — valida el código y recién ahí completa el login. */
    public function verifyMfaChallenge(): void {
        $this->verifyCsrf();
        $pending = $this->pendingMfaOrRedirect();

        $codigo = trim($_POST['codigo'] ?? '');
        $db     = Database::getInstance();
        $row    = $db->fetch($db->query(
            'SELECT mfa_secreto, mfa_ultimo_paso FROM CORE_Usuarios WHERE id_usuario=?',
            [[$pending['id_usuario'], SQLSRV_PARAM_IN]]
        ));

        $matched = null;
        $ok = false;
        if ($row && !empty($row['mfa_secreto'])) {
            try {
                $secret  = MfaHelper::decryptSecret($row['mfa_secreto']);
                $lastStep = $row['mfa_ultimo_paso'] !== null ? (int)$row['mfa_ultimo_paso'] : null;
                $ok = MfaHelper::verify($secret, $codigo, $lastStep, $matched);
            } catch (Throwable $e) {
                $ok = false;
            }
        }

        if (!$ok) {
            $_SESSION['_mfa_pending']['intentos'] = ((int)($pending['intentos'] ?? 0)) + 1;
            if ($_SESSION['_mfa_pending']['intentos'] >= 5) {
                unset($_SESSION['_mfa_pending']);
                SessionHelper::flash('login_error', 'Demasiados intentos. Ingresa de nuevo.');
                $this->redirect('/login');
            }
            SessionHelper::flash('mfa_error', 'Código incorrecto. Verifica la hora de tu dispositivo e intenta de nuevo.');
            $this->redirect('/login/verificar');
        }

        $db->query('UPDATE CORE_Usuarios SET mfa_ultimo_paso=? WHERE id_usuario=?', [
            [$matched, SQLSRV_PARAM_IN], [$pending['id_usuario'], SQLSRV_PARAM_IN],
        ]);

        $result = $this->model->completeLogin(
            (int)$pending['id_usuario'], $pending['nombre_usuario'], $pending['nombre_completo'],
            (int)$pending['nivel_jerarquia'], $pending['id_departamento'], $pending['tema_preferido'],
            (bool)$pending['requiere_cambio'], true
        );
        unset($_SESSION['_mfa_pending']);

        SessionHelper::login($result);
        $_SESSION['session_token']      = $result['session_token'];
        $_SESSION['_mfa_verificado_en'] = time();

        if ($result['requiere_cambio']) {
            $this->redirect('/cambiar-contrasena');
        }
        $this->redirect('/dashboard');
    }

    private function pendingMfaOrRedirect(): array {
        $pending = $_SESSION['_mfa_pending'] ?? null;
        if (!$pending || (int)$pending['expira'] < time()) {
            unset($_SESSION['_mfa_pending']);
            SessionHelper::flash('login_error', 'La verificación expiró. Ingresa de nuevo.');
            $this->redirect('/login');
        }
        return $pending;
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
        $id = SessionHelper::userId();
        $db = Database::getInstance();

        $usuario = $db->fetch($db->query(
            'SELECT u.id_usuario, u.nombre_usuario, u.correo, u.nombre_completo, u.cedula,
                    u.nivel_jerarquia, u.estado, u.requiere_mfa, u.mfa_activado_en,
                    u.fecha_creacion, u.tema_preferido, d.nombre AS departamento
             FROM CORE_Usuarios u
             LEFT JOIN CORE_Departamentos d ON d.id_departamento = u.id_departamento
             WHERE u.id_usuario=?',
            [[$id, SQLSRV_PARAM_IN]]
        ));

        $roles = $db->fetchAll($db->query(
            'SELECT r.nombre, r.codigo FROM CORE_Usuarios_Roles ur
             JOIN CORE_Roles r ON r.id_rol = ur.id_rol
             WHERE ur.id_usuario=? AND ur.estado=1 ORDER BY r.nombre',
            [[$id, SQLSRV_PARAM_IN]]
        ));

        $ultimoAcceso = $db->fetch($db->query(
            "SELECT fecha_registro FROM CORE_Auditoria
             WHERE id_usuario=? AND operacion='LOGIN' AND resultado='EXITO'
             ORDER BY fecha_registro DESC OFFSET 1 ROWS FETCH NEXT 1 ROWS ONLY",
            [[$id, SQLSRV_PARAM_IN]]
        ));

        $this->render('Credenciales/perfil/index', [
            'pageTitle'     => 'Mi Perfil',
            'usuario'       => $usuario,
            'roles'         => $roles,
            'ultimoAcceso'  => $ultimoAcceso['fecha_registro'] ?? null,
            'csrf'          => $this->csrfToken(),
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

        if ($nueva !== $confirm) {
            SessionHelper::flash('pass_error', 'Las contraseñas no coinciden.');
            $this->redirect('/cambiar-contrasena');
        }
        // Mismos requisitos que el checklist visual del formulario -- si se
        // cambia uno, cambiar el otro (ver cambiar_contrasena.php).
        $cumpleRequisitos = strlen($nueva) >= 8
            && preg_match('/[a-z]/', $nueva)
            && preg_match('/[A-Z]/', $nueva)
            && preg_match('/[0-9]/', $nueva)
            && preg_match('/[^a-zA-Z0-9]/', $nueva);
        if (!$cumpleRequisitos) {
            SessionHelper::flash('pass_error', 'La contraseña no cumple los requisitos mínimos de seguridad.');
            $this->redirect('/cambiar-contrasena');
        }

        $id = SessionHelper::userId();
        $db = Database::getInstance();

        $row = $db->fetch($db->query('SELECT hash_contrasena FROM CORE_Usuarios WHERE id_usuario=?', [[$id, SQLSRV_PARAM_IN]]));
        if (!$row || !SecurityHelper::verifyPassword($actual, $row['hash_contrasena'])) {
            SessionHelper::flash('pass_error', 'Contraseña actual incorrecta.');
            $this->redirect('/cambiar-contrasena');
        }

        // Rechazar reuso de una contraseña reciente -- password_verify() no
        // es determinístico en SQL (el propio sp_CambiarContrasena lo avisa
        // en su comentario), así que se compara acá, en PHP, ANTES de llamar
        // al SP. CORE_Contrasenas_Hist ya archivaba hashes viejos pero nunca
        // se usaba para esto -- el checkeo real faltaba, ahora existe.
        if (SecurityHelper::verifyPassword($nueva, $row['hash_contrasena'])) {
            SessionHelper::flash('pass_error', 'La nueva contraseña no puede ser igual a la actual.');
            $this->redirect('/cambiar-contrasena');
        }
        $historial = $db->fetchAll($db->query(
            'SELECT TOP 5 hash_contrasena FROM CORE_Contrasenas_Hist WHERE id_usuario=? ORDER BY fecha_cambio DESC',
            [[$id, SQLSRV_PARAM_IN]]
        ));
        foreach ($historial as $h) {
            if (SecurityHelper::verifyPassword($nueva, $h['hash_contrasena'])) {
                SessionHelper::flash('pass_error', 'No puedes reutilizar una contraseña usada recientemente.');
                $this->redirect('/cambiar-contrasena');
            }
        }

        $hash = SecurityHelper::hashPassword($nueva);
        $db->query(
            '{CALL sp_CambiarContrasena(?,?,?,?)}',
            [[$id, SQLSRV_PARAM_IN], [$hash, SQLSRV_PARAM_IN], ['', SQLSRV_PARAM_IN], [5, SQLSRV_PARAM_IN]]
        );

        SessionHelper::flash('success', 'Contraseña actualizada correctamente.');
        $this->redirect('/perfil');
    }

    // ─── Seguridad / MFA ────────────────────────────────────────────────────

    /** GET /perfil/seguridad */
    public function showSeguridad(): void {
        $this->requireAuth();
        $id = SessionHelper::userId();
        $db = Database::getInstance();
        $row = $db->fetch($db->query('SELECT requiere_mfa, mfa_activado_en FROM CORE_Usuarios WHERE id_usuario=?', [[$id, SQLSRV_PARAM_IN]]));

        $enrollment = $_SESSION['_mfa_enrollment'] ?? null;
        if ($enrollment && (int)$enrollment['expira'] < time()) {
            unset($_SESSION['_mfa_enrollment']);
            $enrollment = null;
        }

        $this->render('Credenciales/perfil/seguridad', [
            'pageTitle'  => 'Seguridad de la Cuenta',
            'mfaActivo'  => (bool)($row['requiere_mfa'] ?? false),
            'activadoEn' => $row['mfa_activado_en'] ?? null,
            'enrollment' => $enrollment,
            'csrf'       => $this->csrfToken(),
            'error'      => SessionHelper::getFlash('mfa_setup_error'),
            'success'    => SessionHelper::getFlash('mfa_setup_success'),
        ]);
    }

    /** POST /perfil/seguridad/preparar — genera secreto nuevo, pendiente de confirmar. */
    public function prepararMfa(): void {
        $this->requireAuth();
        $this->verifyCsrf();

        $secret = MfaHelper::generateSecret();
        $_SESSION['_mfa_enrollment'] = [
            'secret' => $secret,
            'uri'    => MfaHelper::otpAuthUri($secret, $_SESSION['nombre_usuario'] ?? ''),
            'expira' => time() + 600,
        ];
        $this->redirect('/perfil/seguridad');
    }

    /** POST /perfil/seguridad/activar — confirma el código y activa MFA de verdad. */
    public function activarMfa(): void {
        $this->requireAuth();
        $this->verifyCsrf();

        $enrollment = $_SESSION['_mfa_enrollment'] ?? null;
        if (!$enrollment || (int)$enrollment['expira'] < time()) {
            unset($_SESSION['_mfa_enrollment']);
            SessionHelper::flash('mfa_setup_error', 'La configuración expiró. Genera una clave nueva.');
            $this->redirect('/perfil/seguridad');
        }

        $codigo  = trim($_POST['codigo'] ?? '');
        $matched = null;
        if (!MfaHelper::verify($enrollment['secret'], $codigo, null, $matched)) {
            SessionHelper::flash('mfa_setup_error', 'El código no coincide. Revisa la hora de tu dispositivo e intenta de nuevo.');
            $this->redirect('/perfil/seguridad');
        }

        $id  = SessionHelper::userId();
        $db  = Database::getInstance();
        $enc = MfaHelper::encryptSecret($enrollment['secret']);
        $db->query(
            'UPDATE CORE_Usuarios SET requiere_mfa=1, mfa_secreto=?, mfa_activado_en=SYSUTCDATETIME(), mfa_ultimo_paso=? WHERE id_usuario=?',
            [[$enc, SQLSRV_PARAM_IN], [$matched, SQLSRV_PARAM_IN], [$id, SQLSRV_PARAM_IN]]
        );
        ModuleSecurity::audit('CORE', 'ACTUALIZAR', 'CORE_Usuarios', (string)$id, null, ['mfa' => 'activado'], 'EXITO', 'El usuario activó verificación en dos pasos.');

        unset($_SESSION['_mfa_enrollment']);
        $_SESSION['_requiere_mfa']      = true;
        $_SESSION['_mfa_verificado_en'] = time(); // ya probó posesión del secreto, no hace falta repetirlo ahora

        SessionHelper::flash('mfa_setup_success', 'Verificación en dos pasos activada correctamente.');
        $this->redirect('/perfil/seguridad');
    }

    /** POST /perfil/seguridad/desactivar — pide contraseña actual + código vigente. */
    public function desactivarMfa(): void {
        $this->requireAuth();
        $this->verifyCsrf();

        $id = SessionHelper::userId();
        $db = Database::getInstance();
        $row = $db->fetch($db->query('SELECT hash_contrasena, requiere_mfa, mfa_secreto, mfa_ultimo_paso FROM CORE_Usuarios WHERE id_usuario=?', [[$id, SQLSRV_PARAM_IN]]));

        $clave  = $_POST['clave']  ?? '';
        $codigo = trim($_POST['codigo'] ?? '');

        if (!$row || !SecurityHelper::verifyPassword($clave, $row['hash_contrasena'])) {
            SessionHelper::flash('mfa_setup_error', 'La contraseña actual no es correcta.');
            $this->redirect('/perfil/seguridad');
        }

        $ok = false;
        if ((bool)$row['requiere_mfa'] && !empty($row['mfa_secreto'])) {
            try {
                $secret   = MfaHelper::decryptSecret($row['mfa_secreto']);
                $lastStep = $row['mfa_ultimo_paso'] !== null ? (int)$row['mfa_ultimo_paso'] : null;
                $matched  = null;
                $ok = MfaHelper::verify($secret, $codigo, $lastStep, $matched);
            } catch (Throwable $e) {
                $ok = false;
            }
        }
        if (!$ok) {
            SessionHelper::flash('mfa_setup_error', 'El código de verificación no es correcto.');
            $this->redirect('/perfil/seguridad');
        }

        $db->query('UPDATE CORE_Usuarios SET requiere_mfa=0, mfa_secreto=NULL, mfa_activado_en=NULL, mfa_ultimo_paso=NULL WHERE id_usuario=?', [[$id, SQLSRV_PARAM_IN]]);
        ModuleSecurity::audit('CORE', 'ACTUALIZAR', 'CORE_Usuarios', (string)$id, ['mfa' => 'activado'], ['mfa' => 'desactivado'], 'EXITO', 'El usuario desactivó verificación en dos pasos.');

        $_SESSION['_requiere_mfa'] = false;

        SessionHelper::flash('mfa_setup_success', 'Verificación en dos pasos desactivada.');
        $this->redirect('/perfil/seguridad');
    }
}
