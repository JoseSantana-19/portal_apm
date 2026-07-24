<?php
/**
 * FORM_HELPER.PHP - Funciones de ayuda para manejo de formularios, limpieza y sanitización de datos
 */

/**
 * Sanitiza valores de entrada para evitar XSS
 */
function sanitize($data) {
    if (is_array($data)) {
        foreach ($data as $key => $value) {
            $data[$key] = sanitize($value);
        }
    } else {
        $data = htmlspecialchars(trim((string)$data), ENT_QUOTES, 'UTF-8');
    }
    return $data;
}

/**
 * Devuelve el valor anterior de un campo de formulario para persistencia (Old Value)
 */
function old($fieldName, $default = '') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST[$fieldName])) {
        return sanitize($_POST[$fieldName]);
    } elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET[$fieldName])) {
        return sanitize($_GET[$fieldName]);
    }
    return sanitize($default);
}
