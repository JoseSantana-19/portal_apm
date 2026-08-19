<?php
class SecurityHelper {

    /** Generate or return existing CSRF token for this session. */
    public static function csrfToken(): string {
        if (empty($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf_token'];
    }

    /**
     * Verify CSRF token from POST. Un mismatch casi siempre significa que
     * la sesión real ya venció (se regeneró/vació el token) mientras el
     * formulario seguía abierto -- antes esto tiraba un JSON crudo en
     * pantalla incluso para un POST normal de formulario (sin fetch/AJAX
     * detrás que lo maneje), dejando al usuario varado sin login. Ahora:
     * AJAX real sigue recibiendo JSON (con 'redirect' para que el cliente
     * navegue), cualquier otro POST se manda directo al login con el
     * mismo mensaje de "sesión vencida" que ya usa requireAuth().
     */
    public static function verifyCsrf(): void {
        $token = $_POST['_csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (hash_equals($_SESSION['_csrf_token'] ?? '', $token)) {
            return;
        }
        http_response_code(419);
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower((string)$_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            die(json_encode(['error' => 'La sesión venció.', 'redirect' => (defined('APP_URL') ? APP_URL : '') . '/login?timeout=1']));
        }
        header('Location: ' . (defined('APP_URL') ? APP_URL : '') . '/login?timeout=1');
        exit;
    }

    /** Hidden CSRF input field HTML. */
    public static function csrfField(): string {
        return '<input type="hidden" name="_csrf_token" value="' . self::csrfToken() . '">';
    }

    /** XSS-safe output. */
    public static function e(mixed $val): string {
        return htmlspecialchars((string)$val, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /** Hash password with bcrypt. */
    public static function hashPassword(string $password): string {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    /** Verify password against bcrypt hash. */
    public static function verifyPassword(string $password, string $hash): bool {
        return password_verify($password, $hash);
    }

    /** Generate cryptographically-secure random token. */
    public static function generateToken(int $bytes = 64): string {
        return bin2hex(random_bytes($bytes));
    }

    /** Set recommended HTTP security headers. */
    public static function setSecurityHeaders(): void {
        if (headers_sent()) return;
        header("X-Frame-Options: SAMEORIGIN");
        header("X-Content-Type-Options: nosniff");
        header("X-XSS-Protection: 1; mode=block");
        header("Referrer-Policy: strict-origin-when-cross-origin");
        // frame-src: permite embeber el Dashboard Ejecutivo (Streamlit) del módulo Portuaria
        $frameSrc = "'self'";
        if (defined('APM_DASHBOARD_EJECUTIVO_URL')) {
            $u = parse_url(APM_DASHBOARD_EJECUTIVO_URL);
            if (!empty($u['host'])) {
                $frameSrc .= ' ' . ($u['scheme'] ?? 'http') . '://' . $u['host'] . (isset($u['port']) ? ':' . $u['port'] : '');
            }
        }
        header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' cdn.jsdelivr.net cdnjs.cloudflare.com unpkg.com; style-src 'self' 'unsafe-inline' cdnjs.cloudflare.com fonts.googleapis.com; font-src 'self' cdnjs.cloudflare.com fonts.gstatic.com; img-src 'self' data:; frame-src {$frameSrc};");
    }

    /** Sanitize string for HTML output. */
    public static function sanitize(string $val): string {
        return htmlspecialchars(trim($val), ENT_QUOTES, 'UTF-8');
    }

    /** Get real client IP (handles proxies). */
    public static function getClientIp(): string {
        $keys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];
        foreach ($keys as $k) {
            if (!empty($_SERVER[$k])) {
                $ip = trim(explode(',', $_SERVER[$k])[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP)) return $ip;
            }
        }
        return '0.0.0.0';
    }
}
