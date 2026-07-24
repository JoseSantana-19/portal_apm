<?php
/**
 * SESSION_HELPER.PHP - Funciones de ayuda para gestión de sesiones y notificaciones flash (toasts)
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Agrega o lee un mensaje flash/toast de la sesión
 */
function flash($key = '', $message = '', $type = 'info') {
    if (!empty($key)) {
        if (!empty($message)) {
            $_SESSION['toast'] = [
                'mensaje' => $message,
                'tipo' => $type
            ];
        } else {
            if (isset($_SESSION['toast'])) {
                $toast = $_SESSION['toast'];
                unset($_SESSION['toast']);
                return $toast;
            }
        }
    }
    return null;
}

/**
 * Verifica si el usuario ha iniciado sesión
 */
function isLoggedIn(): bool {
    return isset($_SESSION['usuario']);
}
