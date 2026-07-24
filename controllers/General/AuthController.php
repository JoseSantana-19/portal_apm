<?php
/**
 * AuthController. Handles authentication, login forms, submissions, MFA, and logout.
 * Supports personalized department-specific login screens and automated SSO token generation.
 */
class AuthController extends Controller {

    private Usuario $usuarioModel;

    public function __construct() {
        parent::__construct();
        $this->usuarioModel = new Usuario();
    }

    /**
     * Display general login page.
     */
    public function showLogin(): void {
        if (isset($_SESSION['user_id'])) {
            $this->redirect('/');
        }
        $error = $_SESSION['login_error'] ?? null;
        unset($_SESSION['login_error']);
        $this->render('General/auth/login', ['error' => $error, 'module' => 'General'], false);
    }

    /**
     * Display Talento Humano login page.
     */
    public function showLoginTH(): void {
        if (isset($_SESSION['user_id'])) {
            $this->redirect('/talento-humano');
        }
        $error = $_SESSION['login_error'] ?? null;
        unset($_SESSION['login_error']);
        $this->render('General/auth/login', ['error' => $error, 'module' => 'TH'], false);
    }

    /**
     * Display Control de Bienes login page.
     */
    public function showLoginBienes(): void {
        if (isset($_SESSION['user_id'])) {
            $this->redirect('/control-bienes');
        }
        $error = $_SESSION['login_error'] ?? null;
        unset($_SESSION['login_error']);
        $this->render('General/auth/login', ['error' => $error, 'module' => 'Bienes'], false);
    }

    /**
     * Display Control de Acceso login page.
     */
    public function showLoginAcceso(): void {
        if (isset($_SESSION['user_id'])) {
            $this->redirect('/control-acceso');
        }
        $error = $_SESSION['login_error'] ?? null;
        unset($_SESSION['login_error']);
        $this->render('General/auth/login', ['error' => $error, 'module' => 'Acceso'], false);
    }

    /**
     * Display Bitacoras login page.
     */
    public function showLoginBitacoras(): void {
        if (isset($_SESSION['user_id'])) {
            $this->redirect('/bitacoras');
        }
        $error = $_SESSION['login_error'] ?? null;
        unset($_SESSION['login_error']);
        $this->render('General/auth/login', ['error' => $error, 'module' => 'Bitacoras'], false);
    }

    /**
     * Display Dirección Financiera login page.
     */
    public function showLoginFinanciero(): void {
        if (isset($_SESSION['user_id'])) {
            $this->redirect('/');
        }
        $error = $_SESSION['login_error'] ?? null;
        unset($_SESSION['login_error']);
        $this->render('General/auth/login', ['error' => $error, 'module' => 'Financiero'], false);
    }

    /**
     * Display Asesoría Jurídica login page.
     */
    public function showLoginJuridica(): void {
        if (isset($_SESSION['user_id'])) {
            $this->redirect('/');
        }
        $error = $_SESSION['login_error'] ?? null;
        unset($_SESSION['login_error']);
        $this->render('General/auth/login', ['error' => $error, 'module' => 'Juridica'], false);
    }

    /**
     * Display Infraestructuras Portuarias login page.
     */
    public function showLoginInfraestructura(): void {
        if (isset($_SESSION['user_id'])) {
            $this->redirect('/control-acceso');
        }
        $error = $_SESSION['login_error'] ?? null;
        unset($_SESSION['login_error']);
        $this->render('General/auth/login', ['error' => $error, 'module' => 'Infraestructura'], false);
    }

    /**
     * Display Gerencia General login page.
     */
    public function showLoginGerencia(): void {
        if (isset($_SESSION['user_id'])) {
            $this->redirect('/dashboard');
        }
        $error = $_SESSION['login_error'] ?? null;
        unset($_SESSION['login_error']);
        $this->render('General/auth/login', ['error' => $error, 'module' => 'Gerencia'], false);
    }

    /**
     * Display Dirección Administrativa login page.
     */
    public function showLoginAdmin(): void {
        if (isset($_SESSION['user_id'])) {
            $this->redirect('/control-bienes');
        }
        $error = $_SESSION['login_error'] ?? null;
        unset($_SESSION['login_error']);
        $this->render('General/auth/login', ['error' => $error, 'module' => 'Admin'], false);
    }

    /**
     * Display Database Admin login page.
     */
    public function showLoginDatabaseAdmin(): void {
        if (isset($_SESSION['user_id'])) {
            $this->redirect('/base-de-datos');
        }
        $error = $_SESSION['login_error'] ?? null;
        unset($_SESSION['login_error']);
        $this->render('General/auth/login', ['error' => $error, 'module' => 'DatabaseAdmin'], false);
    }

    /**
     * Core process login helper (handles pre-validation, tokens and auditing)
     */
    private function processLogin(string $module, string $successRedirect): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect($_SERVER['REQUEST_URI']);
        }

        $username = $this->sanitize($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            $_SESSION['login_error'] = 'Por favor, ingrese su usuario y contraseña.';
            $this->redirect($_SERVER['REQUEST_URI']);
        }

        $auth = $this->usuarioModel->authenticate($username, $password);

        if ($auth['success']) {
            $user = $auth['user'];
            
            // Security Pre-validation: Check if user has access to this specific department/module before logging in
            if ($module !== 'General') {
                $hasAccess = ModuleSecurity::checkAccess($user['id'], $module);
                if (!$hasAccess) {
                    $_SESSION['login_error'] = 'Acceso Denegado: Su perfil no tiene privilegios autorizados para este departamento.';
                    $this->redirect($_SERVER['REQUEST_URI']);
                }
            }

            // Fetch user's cedula from view
            $dbHelper = new class extends Model {
                public function getCedula(int $uid): ?string {
                    $stmt = $this->query("SELECT cedula FROM dbo.View_portal_apm_usuario WHERE id = ?", [$uid]);
                    $row = $stmt->fetch();
                    $stmt->closeCursor();
                    return $row ? $row['cedula'] : null;
                }
                public function updateSessionToken(int $uid, string $token, string $expiry): void {
                    $this->query("UPDATE dbo.Usuarios SET token_sesion = ?, fecha_expira_sesion = ? WHERE id_usuario = ?", [$token, $expiry, $uid]);
                }
            };
            
            $cedula = $dbHelper->getCedula($user['id']) ?? '1300000000';
            
            // Generate Secure Signed SSO Token
            $ssoToken = ModuleSecurity::generateSSOToken($user['id'], $cedula);
            
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_dept'] = $user['dept_id'];
            $_SESSION['sso_token'] = $ssoToken;
            $_SESSION['last_activity'] = time();

            // Store SSO Token in DB (utilizando formato ISO 8601 para evitar incompatibilidad regional en datetime de SQL Server)
            $expiryDate = date('Y-m-d\TH:i:s', time() + 1800);
            $dbHelper->updateSessionToken($user['id'], $ssoToken, $expiryDate);

            // Log access audit call using dynamic stored procedure routing
            $logModule = ($module === 'General') ? 'Acceso' : (($module === 'INFRA_ACCESO') ? 'Acceso' : $module);
            ModuleSecurity::audit($logModule, 'ACCESO', $user['id'], 'LOGIN');

            // Clear login failures if any
            $_SESSION['login_attempts'] = 0;

            if ($this->isAjax()) {
                $this->json(['success' => true, 'redirect' => $this->getBasePath() . $successRedirect]);
            } else {
                $this->redirect($successRedirect);
            }
        } else {
            $_SESSION['login_error'] = $auth['error'];
            
            if ($this->isAjax()) {
                $this->json(['success' => false, 'error' => $auth['error']]);
            } else {
                $this->redirect($_SERVER['REQUEST_URI']);
            }
        }
    }

    /**
     * Login submissions routing methods
     */
    public function login(): void {
        $this->processLogin('General', '/');
    }

    public function loginTH(): void {
        $this->processLogin('TH', '/talento-humano');
    }

    public function loginBienes(): void {
        $this->processLogin('ADMIN', '/control-bienes');
    }

    public function loginAcceso(): void {
        $this->processLogin('INFRA_ACCESO', '/control-acceso');
    }

    public function loginBitacoras(): void {
        $this->processLogin('INFRA_ACCESO', '/bitacoras');
    }

    public function loginFinanciero(): void {
        // Dashboard or specific landing
        $this->processLogin('FINANCIERO', '/');
    }

    public function loginJuridica(): void {
        $this->processLogin('JURIDICA', '/');
    }

    public function loginInfraestructura(): void {
        $this->processLogin('INFRA_ACCESO', '/control-acceso');
    }

    public function loginGerencia(): void {
        $this->processLogin('GERENCIA', '/dashboard');
    }

    public function loginAdmin(): void {
        $this->processLogin('ADMIN', '/control-bienes');
    }

    public function loginDatabaseAdmin(): void {
        $this->processLogin('PORTAL', '/base-de-datos');
    }

    /**
     * Terminate user session and redirect to login.
     */
    public function logout(): void {
        if (isset($_SESSION['user_id'])) {
            ModuleSecurity::audit('Acceso', 'ACCESO', $_SESSION['user_id'], 'LOGOUT');
            
            $dbHelper = new class extends Model {
                public function clearSessionToken(int $uid): void {
                    $this->query("UPDATE dbo.Usuarios SET token_sesion = NULL, fecha_expira_sesion = NULL WHERE id_usuario = ?", [$uid]);
                }
            };
            $dbHelper->clearSessionToken($_SESSION['user_id']);
        }
        $this->logoutSession();
        $this->redirect('/login');
    }
}
