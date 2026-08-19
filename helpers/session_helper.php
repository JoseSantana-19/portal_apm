<?php
class SessionHelper {

    public static function set(string $key, mixed $value): void {
        $_SESSION[$key] = $value;
    }

    public static function get(string $key, mixed $default = null): mixed {
        return $_SESSION[$key] ?? $default;
    }

    public static function has(string $key): bool {
        return isset($_SESSION[$key]);
    }

    public static function remove(string $key): void {
        unset($_SESSION[$key]);
    }

    public static function flash(string $key, string $message): void {
        $_SESSION['_flash'][$key] = $message;
    }

    public static function getFlash(string $key): ?string {
        $msg = $_SESSION['_flash'][$key] ?? null;
        unset($_SESSION['_flash'][$key]);
        return $msg;
    }

    public static function isLoggedIn(): bool {
        return !empty($_SESSION['user_id']);
    }

    public static function userId(): ?int {
        return $_SESSION['user_id'] ?? null;
    }

    public static function nivel(): int {
        return (int)($_SESSION['nivel_jerarquia'] ?? 0);
    }

    /**
     * Populate session after successful login.
     */
    public static function login(array $user): void {
        session_regenerate_id(true);
        $_SESSION['user_id']         = (int)$user['id_usuario'];
        $_SESSION['nombre_usuario']  = $user['nombre_usuario'];
        $_SESSION['nombre_completo'] = $user['nombre_completo'];
        $_SESSION['nivel_jerarquia'] = (int)$user['nivel_jerarquia'];
        $_SESSION['id_departamento'] = $user['id_departamento'] ?? null;
        $_SESSION['tema']            = $user['tema_preferido'] ?? 'light';
        $_SESSION['last_activity']   = time();
        $_SESSION['_csrf_token']     = bin2hex(random_bytes(32));
        // Cacheado en sesión para no consultar CORE_Usuarios en cada cambio
        // de módulo (ver ModuleGateController) -- requiere_mfa no cambia
        // dentro de una sesión activa salvo que el propio usuario lo
        // desactive desde Mi Cuenta, y ese flujo ya limpia esta bandera.
        $_SESSION['_requiere_mfa']   = (bool)($user['requiere_mfa'] ?? false);
    }

    public static function logout(): void {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }
}
