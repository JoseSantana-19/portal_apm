<?php
/**
 * Rutas de librerías/assets del módulo Portuaria (antes rutas/config_rutas.php
 * de portuaria_demoV4). Normativa: sin CDN, todo local.
 *
 * Las rutas son RELATIVAS a la raíz del portal; el layout del módulo declara
 * <base href> con base_url('/'), igual que el proyecto origen, por lo que
 * resuelven bien en raíz (php -S) y en subcarpeta (XAMPP: /portal_apm).
 *
 * Devuelve un array ['url_*' => valor] que PortController inyecta a las vistas.
 */

if (!function_exists('base_url')) {
    /** Compat demoV4 — URL base del front controller del portal. */
    function base_url(string $path = ''): string
    {
        $scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '/');
        $base = rtrim(str_replace('\\', '/', $scriptDir), '/');
        return $base . '/' . ltrim($path, '/');
    }
}

return [
    // --- librerias/Css3 ---
    'url_bootstrap_css'  => 'public/librerias/Css3/bootstrap.min.css',
    'url_datatables_css' => 'public/librerias/Css3/dataTables.bootstrap5.min.css',

    // --- librerias/Otras_librerias (Select2, iconos, Chart.js, SweetAlert2) ---
    'url_icons_css'                    => 'public/librerias/Otras_librerias/bootstrap-icons/bootstrap-icons.min.css',
    'url_select2_css'                  => 'public/librerias/Otras_librerias/select2/select2.min.css',
    'url_select2_bootstrap_theme_css'  => 'public/librerias/Otras_librerias/select2-bootstrap-5-theme/select2-bootstrap-5-theme.min.css',
    'url_select2_js'                   => 'public/librerias/Otras_librerias/select2/select2.min.js',
    'url_chart_js'                     => 'public/librerias/Otras_librerias/chart.js/chart.umd.min.js',
    'url_sweetalert2_css'              => 'public/librerias/Otras_librerias/sweetalert2/sweetalert2.min.css',
    'url_sweetalert2_js'               => 'public/librerias/Otras_librerias/sweetalert2/sweetalert2.all.min.js',

    // CSS propio del módulo (copiado a public/css/portuaria/)
    'url_variables_css'   => 'public/css/portuaria/variables.css',
    'url_layout_css'      => 'public/css/portuaria/layout.css?v=20260712',
    'url_componentes_css' => 'public/css/portuaria/componentes.css?v=20260712',
    'url_toast_css'       => 'public/css/portuaria/toast.css',

    // --- librerias/Js ---
    'url_jquery_js'                => 'public/librerias/Js/jquery-3.7.1.min.js',
    'url_jquery_datatables'        => 'public/librerias/Js/jquery-3.7.1.min.js',
    'url_bootstrap_js'             => 'public/librerias/Js/bootstrap.bundle.min.js',
    'url_datatables_js'            => 'public/librerias/Js/jquery.dataTables.min.js',
    'url_datatables_bootstrap5_js' => 'public/librerias/Js/dataTables.bootstrap5.min.js',

    // JS propio del módulo (copiado a public/js/portuaria/)
    'url_toast_js'             => 'public/js/portuaria/toast.js',
    'url_js_visitas'           => 'public/js/portuaria/listado_visitas.js',
    'url_js_validaciones_ec'   => 'public/js/portuaria/validaciones_ecuador.js',
    'url_js_registro'          => 'public/js/portuaria/registrar_visita.js?v=20260712',
    'url_js_reporte'           => 'public/js/portuaria/reporte_diario_supervisor.js?v=20260712',
    'url_js_catalogos'         => 'public/js/portuaria/catalogos.js?v=20260712',
    'url_layout_sidebar_js'    => 'public/js/portuaria/layout_sidebar.js?v=20260712',
];
