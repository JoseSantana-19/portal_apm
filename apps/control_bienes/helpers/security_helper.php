<?php
/**
 * SECURITY_HELPER.PHP - Funciones de ayuda para seguridad y criptografía
 */

/**
 * Genera un token CSRF y lo guarda en la sesión si no existe
 */
function csrf_token(): string {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verifica si el token CSRF provisto coincide con el guardado en la sesión
 */
function verify_csrf_token($token): bool {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * PASSWORD_PEPPER compartido con TODO el sistema (portal, Talento Humano,
 * Bitácoras, cualquier módulo futuro) -- mismo secreto único, guardado en
 * PORTAL_APM.CORE_Config (cross-DB, esta app corre sobre `inventario`),
 * autogenerado con random_bytes(32) real la primera vez que hace falta.
 * Ver helpers/security_helper.php del portal para el detalle completo del
 * esquema (hash_hmac('sha256',...) antes de bcrypt, prefijo 'peppered:'
 * para distinguir de un hash bcrypt viejo sin pepper).
 */
function password_pepper(): string {
    static $pepper = null;
    if ($pepper !== null) {
        return $pepper;
    }
    $db  = Database::getInstance()->getConnection();
    $row = $db->query("SELECT valor FROM PORTAL_APM.dbo.CORE_Config WHERE modulo='CORE' AND clave='PASSWORD_PEPPER' AND estado=1")->fetch();
    if ($row && !empty($row['valor'])) {
        $pepper = $row['valor'];
        return $pepper;
    }
    $nuevo = bin2hex(random_bytes(32));
    $db->exec(
        "IF NOT EXISTS (SELECT 1 FROM PORTAL_APM.dbo.CORE_Config WHERE modulo='CORE' AND clave='PASSWORD_PEPPER')
         INSERT INTO PORTAL_APM.dbo.CORE_Config (modulo, clave, valor, descripcion, estado)
         VALUES ('CORE', 'PASSWORD_PEPPER', '{$nuevo}', 'Secreto compartido (hex) para peppering de contraseñas en TODO el sistema -- autogenerado, no editar a mano.', 1)"
    );
    $pepper = $nuevo;
    return $pepper;
}

const PASSWORD_PEPPER_PREFIX = 'peppered:';

/** Hash password: HMAC-SHA256 con pepper compartido, luego bcrypt. */
function hash_password_secure(string $password): string {
    $peppered = hash_hmac('sha256', $password, password_pepper());
    return PASSWORD_PEPPER_PREFIX . password_hash($peppered, PASSWORD_BCRYPT, ['cost' => 12]);
}

/**
 * Verify password. Acepta esquema nuevo ('peppered:' + bcrypt sobre HMAC)
 * Y el esquema viejo (bcrypt directo, sin pepper) para no romper ninguna
 * cuenta existente -- usar password_needs_rehash_secure() después de un
 * true para saber si conviene regrabar con el nuevo.
 */
function verify_password_secure(string $password, ?string $hash): bool {
    if ($hash === null || $hash === '') {
        return false;
    }
    if (str_starts_with($hash, PASSWORD_PEPPER_PREFIX)) {
        $peppered = hash_hmac('sha256', $password, password_pepper());
        return password_verify($peppered, substr($hash, strlen(PASSWORD_PEPPER_PREFIX)));
    }
    return password_verify($password, $hash);
}

/** true si el hash guardado todavía usa el esquema viejo (sin pepper). */
function password_needs_rehash_secure(string $hash): bool {
    return !str_starts_with($hash, PASSWORD_PEPPER_PREFIX);
}
