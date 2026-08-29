<?php
/**
 * Base Controller — Auth guard, render helpers, JSON, CSRF.
 */
abstract class Controller {

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        SecurityHelper::setSecurityHeaders();
    }

    protected function isAjax(): bool {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    protected function render(string $view, array $data = [], bool $useLayout = true): void {
        View::render($view, $data, $useLayout);
    }

    protected function json($data, int $status = 200): void {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($status);
        echo json_encode($data);
        exit;
    }

    protected function redirect(string $path): void {
        // Rutas internas ('/login') se anclan a APP_URL para que funcionen
        // igual en raiz (php -S) y en subcarpeta (XAMPP/Wamp: /portal_apm)
        if (!preg_match('#^https?://#i', $path)) {
            $path = APP_URL . '/' . ltrim($path, '/');
        }
        header('Location: ' . $path);
        exit;
    }

    /**
     * Enforce authentication. Redirects to /login on failure.
     * Pass required permission: 'TH', 'BIENES', 'ACCESO', 'BIT', or null for any auth.
     */
    protected function requireAuth(?string $module = null): void {
        if (empty($_SESSION['user_id'])) {
            if ($this->isAjax()) {
                $this->json(['error' => 'Unauthenticated', 'redirect' => '/login'], 401);
            }
            $this->redirect('/login');
        }

        // Session timeout — resuelto vía fn_InactividadSegundos (cascada
        // usuario > módulo > global, configurable en /admin/inactividad del
        // portal). DB_NAME de esta app ya apunta a PORTAL_APM directamente.
        [$timeout,] = $this->resolveInactividad();
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout) {
            $this->destroySession();
            if ($this->isAjax()) {
                $this->json(['error' => 'Session expired', 'redirect' => '/login'], 401);
            }
            $this->redirect('/login?timeout=1');
        }
        $_SESSION['last_activity'] = time();
    }

    protected function resolveInactividad(): array {
        $now = time();
        if (isset($_SESSION['_inactividad_segundos']) && ($now - ($_SESSION['_inactividad_resuelto_en'] ?? 0)) < 300) {
            return [(int)$_SESSION['_inactividad_segundos'], (int)($_SESSION['_inactividad_aviso'] ?? 60)];
        }
        $timeout = 1800;
        $aviso   = 60;
        try {
            $db        = Database::getInstance();
            $idUsuario = (int)($_SESSION['user_id'] ?? 0);
            $stmt = sqlsrv_query($db->getConn(),
                'SELECT dbo.fn_InactividadSegundos(?, ?) AS s, dbo.fn_InactividadAvisoSegundos(?, ?) AS a',
                [
                    [$idUsuario, SQLSRV_PARAM_IN], ['BITACORAS', SQLSRV_PARAM_IN],
                    [$idUsuario, SQLSRV_PARAM_IN], ['BITACORAS', SQLSRV_PARAM_IN],
                ]
            );
            if ($stmt !== false) {
                $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
                sqlsrv_free_stmt($stmt);
                if ($row) { $timeout = (int)$row['s']; $aviso = (int)$row['a']; }
            }
        } catch (Exception $e) {
            // BD no disponible: se mantiene el valor por defecto (1800/60).
        }
        $_SESSION['_inactividad_segundos']    = $timeout;
        $_SESSION['_inactividad_aviso']       = $aviso;
        $_SESSION['_inactividad_resuelto_en'] = $now;
        return [$timeout, $aviso];
    }

    /**
     * @param array{0:int,1:int,2:int,3:int,4:int}|null $nodo Tupla MOIS opcional
     *   [id_modulo, opcion, items, subitems, nivel_crud_minimo] para exigir,
     *   ADEMÁS de $minLevel, el permiso granular real configurado en
     *   /admin/roles/{id}/permisos (CORE_Permisos_Nodo vía fn_TienePermisoNodo).
     *   nivel_crud: 1=Ver, 2=Crear, 3=Editar, 4=Total. Sin este parámetro el
     *   comportamiento es el de siempre (solo nivel_jerarquia).
     */
    protected function requireLevel(int $minLevel, ?array $nodo = null): void {
        $this->requireAuth();
        if (($_SESSION['nivel_jerarquia'] ?? 0) < $minLevel) {
            $this->denyAccess();
        }
        if ($nodo !== null && !$this->tienePermisoNodo(...$nodo)) {
            $this->denyAccess();
        }
    }

    private function denyAccess(): void {
        if ($this->isAjax()) {
            $this->json(['error' => 'Acceso denegado'], 403);
        }
        $this->redirect('/dashboard');
    }

    /** Consulta fn_TienePermisoNodo — permiso real del rol sobre un nodo MOIS. */
    private function tienePermisoNodo(int $idModulo, int $opcion, int $items, int $subitems, int $nivelCrudMin): bool {
        try {
            $db   = Database::getInstance();
            $stmt = sqlsrv_query(
                $db->getConn(),
                'SELECT dbo.fn_TienePermisoNodo(?,?,?,?,?,?,1) AS ok',
                [
                    [(int)($_SESSION['user_id'] ?? 0), SQLSRV_PARAM_IN],
                    [$idModulo,     SQLSRV_PARAM_IN],
                    [$opcion,       SQLSRV_PARAM_IN],
                    [$items,        SQLSRV_PARAM_IN],
                    [$subitems,     SQLSRV_PARAM_IN],
                    [$nivelCrudMin, SQLSRV_PARAM_IN],
                ]
            );
            if ($stmt === false) return false;
            $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            sqlsrv_free_stmt($stmt);
            return (bool)($row['ok'] ?? false);
        } catch (Exception $e) {
            return false;
        }
    }

    protected function destroySession(): void {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }

    protected function csrfToken(): string {
        return SecurityHelper::csrfToken();
    }

    protected function verifyCsrf(): void {
        SecurityHelper::verifyCsrf();
    }

    /**
     * @param string $source  'post' | 'get' | 'both' (default both)
     */
    protected function input(string $key, string $source = 'both', $default = null) {
        if ($source === 'get') {
            $val = $_GET[$key] ?? $default;
        } elseif ($source === 'post') {
            $val = $_POST[$key] ?? $default;
        } else {
            $val = $_POST[$key] ?? $_GET[$key] ?? $default;
        }
        return is_string($val) ? trim($val) : $val;
    }

    protected function sanitize(string $val): string {
        return htmlspecialchars(trim($val), ENT_QUOTES, 'UTF-8');
    }

    protected function currentUser(): array {
        return [
            'id'               => $_SESSION['user_id'] ?? null,
            'nombre'           => $_SESSION['nombre_completo'] ?? '',
            'usuario'          => $_SESSION['nombre_usuario'] ?? '',
            'nivel_jerarquia'  => $_SESSION['nivel_jerarquia'] ?? 0,
            'id_departamento'  => $_SESSION['id_departamento'] ?? null,
            'tema'             => $_SESSION['tema'] ?? 'light',
        ];
    }
}
