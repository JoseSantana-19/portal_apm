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

    protected function json(mixed $data, int $status = 200): never {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($status);
        echo json_encode($data);
        exit;
    }

    protected function redirect(string $path): never {
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

        // Session timeout
        $timeout = defined('SESSION_TIMEOUT') ? SESSION_TIMEOUT : 1800;
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout) {
            $this->destroySession();
            if ($this->isAjax()) {
                $this->json(['error' => 'Session expired', 'redirect' => '/login'], 401);
            }
            $this->redirect('/login?timeout=1');
        }
        $_SESSION['last_activity'] = time();
    }

    protected function requireLevel(int $minLevel): void {
        $this->requireAuth();
        if (($_SESSION['nivel_jerarquia'] ?? 0) < $minLevel) {
            if ($this->isAjax()) {
                $this->json(['error' => 'Acceso denegado'], 403);
            }
            $this->redirect('/dashboard');
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
    protected function input(string $key, string $source = 'both', mixed $default = null): mixed {
        $val = match ($source) {
            'get'  => $_GET[$key]  ?? $default,
            'post' => $_POST[$key] ?? $default,
            default => $_POST[$key] ?? $_GET[$key] ?? $default,
        };
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
