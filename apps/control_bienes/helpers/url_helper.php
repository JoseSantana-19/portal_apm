<?php
/**
 * URL_HELPER.PHP - Funciones de ayuda para redirección y manejo de URLs
 */

/**
 * Redirecciona a una URL o ruta específica del sistema
 */
function redirect($route, $message = null, $type = 'info') {
    // Si ya existe la función de ayuda en Controller.php o ControllerBase.php, se gestionará allí,
    // pero proveemos este helper global para redirección simple
    if ($message !== null && session_status() !== PHP_SESSION_NONE) {
        $_SESSION['toast'] = [
            'mensaje' => $message,
            'tipo' => $type
        ];
    }
    
    $url = route($route);
    header("Location: " . $url);
    exit;
}
