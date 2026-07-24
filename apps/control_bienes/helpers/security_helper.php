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
