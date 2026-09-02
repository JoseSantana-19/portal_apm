<?php
/**
 * CONFIG ROUTES – Constantes globales de rutas y assets.
 * Importar al inicio de cualquier archivo que necesite rutas absolutas.
 *
 * IMPORTANTE: Todas las constantes usan el patrón  defined('X') || define('X', ...)
 * para evitar el error "Constant already defined" si este archivo se carga más de
 * una vez en la misma petición (ej. desde index.php y desde un controlador).
 */

// URL base del proyecto en el servidor (sin barra final)
// index.php la define primero teniendo en cuenta php_sapi_name(); aquí es el fallback.
defined('BASE_URL')   || define('BASE_URL',   Config::baseUrl());

// Única ruta de assets utilizada como constante por las vistas compartidas.
defined('IMG_URL') || define('IMG_URL', BASE_URL . '/public/img');

// Entorno de ejecución
// Cambiar a 'production' al desplegar en el servidor real de la APM
defined('ENTORNO')    || define('ENTORNO',    Config::environment());
