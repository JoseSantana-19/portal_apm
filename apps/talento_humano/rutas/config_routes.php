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
defined('BASE_URL')   || define('BASE_URL',   '/PortalPortuario');

// Rutas a los assets en /public
defined('CSS_URL')    || define('CSS_URL',    BASE_URL . '/public/css');
defined('JS_URL')     || define('JS_URL',     BASE_URL . '/public/js');
defined('IMG_URL')    || define('IMG_URL',    BASE_URL . '/public/img');

// Logo institucional APM — imagen única compartida por todos los módulos
// del portal (imgs/ en la raíz de portal_apm), no una copia local del módulo.
defined('LOGO_URL')   || define('LOGO_URL',   BASE_URL . '/../../imgs');

// Rutas a librerías locales
defined('ICONS_CSS')  || define('ICONS_CSS',  CSS_URL . '/bootstrap-icons.css');
defined('FONTS_URL')  || define('FONTS_URL',  CSS_URL . '/fonts.css');

// Entorno de ejecución
// Cambiar a 'production' al desplegar en el servidor real de la APM
defined('ENTORNO')    || define('ENTORNO',    'development');
