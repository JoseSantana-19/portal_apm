<?php
/**
 * ROUTES.PHP - Mapa de Rutas del Framework MVC Modular
 * Define el mapeo de cada clave GET "route" a su respectivo módulo, controlador y acción por defecto.
 */

return [
    // --- Módulo Control de Bines (Proyecto Principal del Usuario) ---
    'inventario' => [
        'module'     => 'Control_Bines',
        'controller' => 'BinController',
        'action'     => 'index'
    ],
    'items' => [
        'module'     => 'Control_Bines',
        'controller' => 'BinController',
        'action'     => 'catalogo'
    ],
    'inv_items_sistema' => [
        'module'     => 'Control_Bines',
        'controller' => 'BinController',
        'action'     => 'itemsSistema'
    ],
    'cabeceras' => [
        'module'     => 'Control_Bines',
        'controller' => 'EstacionController',
        'action'     => 'index'
    ],
    'inv_maestros' => [
        'module'     => 'Control_Bines',
        'controller' => 'EstacionController',
        'action'     => 'maestros'
    ],
    'ingresos' => [
        'module'     => 'Control_Bines',
        'controller' => 'MonitoreoController',
        'action'     => 'ingresos'
    ],
    'requisiciones' => [
        'module'     => 'Control_Bines',
        'controller' => 'MonitoreoController',
        'action'     => 'requisiciones'
    ],
    'ordenes_compra' => [
        'module'     => 'Control_Bines',
        'controller' => 'MonitoreoController',
        'action'     => 'ordenesCompra'
    ],
    'egresos' => [
        'module'     => 'Control_Bines',
        'controller' => 'MonitoreoController',
        'action'     => 'egresos'
    ],

    // --- Módulo Talento Humano ---
    'talento' => [
        'module'     => 'Talento_Humano',
        'controller' => 'EmpleadoController',
        'action'     => 'index'
    ],
    'talento_directorio' => [
        'module'     => 'Talento_Humano',
        'controller' => 'EmpleadoController',
        'action'     => 'directorio'
    ],
    'talento_crear' => [
        'module'     => 'Talento_Humano',
        'controller' => 'EmpleadoController',
        'action'     => 'crear'
    ],
    'talento_guardar' => [
        'module'     => 'Talento_Humano',
        'controller' => 'EmpleadoController',
        'action'     => 'guardar'
    ],
    'talento_editar' => [
        'module'     => 'Talento_Humano',
        'controller' => 'EmpleadoController',
        'action'     => 'editar'
    ],
    'talento_borrar' => [
        'module'     => 'Talento_Humano',
        'controller' => 'EmpleadoController',
        'action'     => 'borrar'
    ],
    'talento_eliminar' => [
        'module'     => 'Talento_Humano',
        'controller' => 'EmpleadoController',
        'action'     => 'eliminar'
    ],
    'talento_imprimir_ficha' => [
        'module'     => 'Talento_Humano',
        'controller' => 'EmpleadoController',
        'action'     => 'imprimirFicha'
    ],

    // --- Módulo Bitácoras ---
    'inv_bitacora' => [
        'module'     => 'Bitacoras',
        'controller' => 'EventoController',
        'action'     => 'index'
    ],
    'reportes' => [
        'module'     => 'Bitacoras',
        'controller' => 'ReporteController',
        'action'     => 'index'
    ],

    // --- Módulo Central ---
    'inv_lookup' => [
        'module'     => 'Central',
        'controller' => 'LookupController',
        'action'     => 'buscar'
    ],
    'dashboard' => [
        'module'     => 'Central',
        'controller' => 'DashboardController',
        'action'     => 'index'
    ],
    'inv_periodos' => [
        'module'     => 'Central',
        'controller' => 'ConfigController',
        'action'     => 'periodos'
    ],
    'inv_secuenciales' => [
        'module'     => 'Central',
        'controller' => 'ConfigController',
        'action'     => 'secuenciales'
    ],
    'inv_parametros' => [
        'module'     => 'Central',
        'controller' => 'ConfigController',
        'action'     => 'parametros'
    ],
    'notificaciones_marcar_leidas' => [
        'module'     => 'Central',
        'controller' => 'NotificacionesController',
        'action'     => 'marcarLeidas'
    ],
    'notificaciones_vaciar' => [
        'module'     => 'Central',
        'controller' => 'NotificacionesController',
        'action'     => 'vaciar'
    ],

    // --- Módulo Credenciales ---
    'inv_login' => [
        'module'     => 'Credenciales',
        'controller' => 'AuthController',
        'action'     => 'login'
    ],
    'login_post' => [
        'module'     => 'Credenciales',
        'controller' => 'AuthController',
        'action'     => 'loginPost'
    ],
    'logout' => [
        'module'     => 'Credenciales',
        'controller' => 'AuthController',
        'action'     => 'logout'
    ],
    'mantener_sesion' => [
        'module'     => 'Credenciales',
        'controller' => 'AuthController',
        'action'     => 'mantenerSesion'
    ],
    'perfil_foto' => [
        'module'     => 'Credenciales',
        'controller' => 'UsuarioController',
        'action'     => 'actualizarFotoPerfil'
    ],
    'usuarios' => [
        'module'     => 'Credenciales',
        'controller' => 'UsuarioController',
        'action'     => 'index'
    ],
    'inv_permisos' => [
        'module'     => 'Credenciales',
        'controller' => 'PermisoController',
        'action'     => 'index'
    ]
];
