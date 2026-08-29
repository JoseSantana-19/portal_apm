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
    public static function e($val): string {
        return htmlspecialchars((string)$val, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * PASSWORD_PEPPER — secreto de aplicación compartido por TODO el
     * sistema (portal + apps/talento_humano + apps/control_bienes +
     * apps/bitacoras + cualquier módulo futuro vía libs/SsoClient.php),
     * autogenerado con random_bytes(32) real y persistido en
     * PORTAL_APM.CORE_Config -- mismo mecanismo ya establecido para
     * SSO_SECRET (ModuleSecurity) y MFA_ENCRYPTION_KEY (MfaHelper), NUNCA
     * un valor hardcodeado en el código fuente.
     *
     * Uso: hash_hmac('sha256', $password, PEPPER) ANTES de password_hash()
     * -- un "pepper" real (RFC/OWASP: server-side secret adicional a la
     * sal por-hash que ya trae bcrypt). Aunque la BD de contraseñas se
     * filtre completa, un atacante sin este secreto no puede ni verificar
     * ni fuerza-bruta las contraseñas reales, porque el valor que bcrypt
     * termina hasheando ni siquiera es la contraseña en texto plano.
     *
     * Prefijo 'peppered:' en el hash guardado: marca inequívoca de qué
     * esquema produjo ese valor, sin ambigüedad con un hash bcrypt viejo
     * (pre-peppering) que luce idéntico en formato ($2y$12$...). Permite
     * migración perezosa: verifyPassword() acepta AMBOS esquemas, y
     * passwordNeedsRehash() le dice al caller cuándo regrabar con el
     * nuevo tras un login exitoso -- ningún usuario existente queda
     * bloqueado por este cambio.
     */
    private static ?string $pepper = null;

    private static function passwordPepper(): string {
        if (self::$pepper !== null) {
            return self::$pepper;
        }
        try {
            $db   = Database::getInstance();
            $stmt = sqlsrv_query(
                $db->getConn(),
                "SELECT valor FROM CORE_Config WHERE modulo='CORE' AND clave='PASSWORD_PEPPER' AND estado=1"
            );
            if ($stmt !== false) {
                $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
                sqlsrv_free_stmt($stmt);
                if (!empty($row['valor'])) {
                    self::$pepper = $row['valor'];
                    return self::$pepper;
                }
            }
            $nuevo = bin2hex(random_bytes(32));
            sqlsrv_query(
                $db->getConn(),
                "IF NOT EXISTS (SELECT 1 FROM CORE_Config WHERE modulo='CORE' AND clave='PASSWORD_PEPPER')
                 INSERT INTO CORE_Config (modulo, clave, valor, descripcion, estado)
                 VALUES ('CORE', 'PASSWORD_PEPPER', ?, 'Secreto compartido (hex) para peppering de contraseñas en TODO el sistema -- autogenerado, no editar a mano.', 1)",
                [[$nuevo, SQLSRV_PARAM_IN]]
            );
            self::$pepper = $nuevo;
            return self::$pepper;
        } catch (Exception $e) {
            // BD no disponible: sin pepper no se puede ni hashear ni verificar
            // -- fallar aquí es más seguro que degradar silenciosamente a un
            // esquema sin pepper.
            throw new RuntimeException('No se pudo resolver PASSWORD_PEPPER: ' . $e->getMessage());
        }
    }

    private const PEPPER_PREFIX = 'peppered:';

    /** Hash password: HMAC-SHA256 con pepper compartido, luego bcrypt. */
    public static function hashPassword(string $password): string {
        $peppered = hash_hmac('sha256', $password, self::passwordPepper());
        return self::PEPPER_PREFIX . password_hash($peppered, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    /**
     * Verify password. Acepta el esquema nuevo (prefijo 'peppered:') Y el
     * esquema viejo (bcrypt directo sobre la contraseña, sin pepper) para
     * no romper ninguna cuenta existente -- usar passwordNeedsRehash()
     * después de un true para saber si conviene regrabar con el nuevo.
     */
    public static function verifyPassword(string $password, string $hash): bool {
        if (str_starts_with($hash, self::PEPPER_PREFIX)) {
            $peppered = hash_hmac('sha256', $password, self::passwordPepper());
            return password_verify($peppered, substr($hash, strlen(self::PEPPER_PREFIX)));
        }
        return password_verify($password, $hash);
    }

    /** true si el hash guardado todavía usa el esquema viejo (sin pepper). */
    public static function passwordNeedsRehash(string $hash): bool {
        return !str_starts_with($hash, self::PEPPER_PREFIX);
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
