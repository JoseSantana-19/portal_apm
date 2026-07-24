<?php
/**
 * FRONT CONTROLLER – Punto de entrada único del Portal Portuario APM
 * Toda petición HTTP pasa por aquí antes de ser despachada al controlador.
 */

// Iniciar sesión PHP (necesario para $_SESSION en todos los módulos)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('ROOT', __DIR__);

// BASE_URL autodetectada — app embebida en el Portal APM
// (subcarpeta /portal_apm/apps/talento_humano en Apache; vacía en php -S)
$_isDevServer = php_sapi_name() === 'cli-server';
$_basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
define('BASE_URL', $_isDevServer ? '' : $_basePath);

/* ── PUENTE DE SESIÓN PORTAL APM ─────────────────────────────────────────
   Esta app no traía login propio: al embeberse en el portal exige la
   sesión central (CORE_Usuarios + sp_Login). Sin sesión → login del portal. */
if (empty($_SESSION['user_id'])) {
    $portalLogin = preg_replace('#/apps/talento_humano$#', '', $_basePath) . '/login';
    header('Location: ' . ($portalLogin ?: '/login'));
    exit;
}

// ── Carga de configuración y núcleo ──────────────────────────────────────────
require_once ROOT . '/rutas/config_routes.php';
require_once ROOT . '/core/Router.php';
require_once ROOT . '/core/Controller.php';
require_once ROOT . '/core/Model.php';
require_once ROOT . '/core/Database.php';

// ── Controladores del módulo Talento Humano ───────────────────────────────────
require_once ROOT . '/modules/talento-humano/Controladores/EmpleadoController.php';
require_once ROOT . '/modules/talento-humano/Controladores/AsistenciaController.php';
require_once ROOT . '/modules/talento-humano/Controladores/VacacionesController.php';
require_once ROOT . '/modules/talento-humano/Controladores/DesempenoController.php';
require_once ROOT . '/modules/talento-humano/Controladores/CapacitacionController.php';
require_once ROOT . '/modules/talento-humano/Controladores/AccionPersonalController.php';
require_once ROOT . '/modules/talento-humano/Modelos/AccionPersonalModel.php';
require_once ROOT . '/modules/talento-humano/Controladores/BibliotecaController.php';
require_once ROOT . '/modules/talento-humano/Controladores/EstudioSeguridadController.php';

// ── Controladores del módulo Administración ───────────────────────────────────
require_once ROOT . '/modules/admin/Controladores/AdminController.php';

// ── Controladores del módulo Auditoría ───────────────────────────────────────
require_once ROOT . '/modules/auditoria/Controladores/AuditoriaController.php';

// ── Despachar la URL ──────────────────────────────────────────────────────────
$router = new Router();

// Rutas del módulo Talento Humano – Principal
$router->add('talento-humano',                    'EmpleadoController', 'index');
$router->add('talento-humano/inicio',             'EmpleadoController', 'inicio');
$router->add('talento-humano/directorio',         'EmpleadoController', 'directorio');
$router->add('talento-humano/empleado/crear',     'EmpleadoController', 'crear');
$router->add('talento-humano/empleado/guardar',   'EmpleadoController', 'guardar');
$router->add('talento-humano/empleado/editar',    'EmpleadoController', 'editar');
$router->add('talento-humano/empleado/borrar',    'EmpleadoController', 'borrar');
$router->add('talento-humano/empleado/eliminar',  'EmpleadoController', 'eliminar');
$router->add('talento-humano/empleado/perfil/{cedula}', 'EmpleadoController', 'perfil');
$router->add('talento-humano/empleado/imprimir-ficha',  'EmpleadoController', 'imprimirFicha');
$router->add('talento-humano/empleado/exportar',         'EmpleadoController', 'exportarCsv');
$router->add('talento-humano/reporte',            'EmpleadoController', 'reporte');

// Rutas del módulo Talento Humano – Gestión Operativa
$router->add('talento-humano/biblioteca',          'BibliotecaController',  'index');
$router->add('talento-humano/asistencia',          'AsistenciaController',  'index');
$router->add('talento-humano/vacaciones',          'VacacionesController',  'index');
$router->add('talento-humano/desempeno',           'DesempenoController',   'index');
$router->add('talento-humano/capacitacion',        'CapacitacionController','index');

// Rutas del módulo Talento Humano – Estudio de Seguridad Socioeconómico
$router->add('talento-humano/estudio-seguridad',          'EstudioSeguridadController', 'index');
$router->add('talento-humano/estudio-seguridad/guardar',  'EstudioSeguridadController', 'guardar');
$router->add('talento-humano/estudio-seguridad/imprimir', 'EstudioSeguridadController', 'imprimir');

// Rutas del módulo Talento Humano – Fase 2: Acción de Personal
$router->add('talento-humano/accion-personal',                  'AccionPersonalController', 'index');
$router->add('talento-humano/accion-personal/guardar',          'AccionPersonalController', 'guardar');
$router->add('talento-humano/accion-personal/ver',              'AccionPersonalController', 'ver');
$router->add('talento-humano/accion-personal/imprimir-accion',  'AccionPersonalController', 'imprimirAccion');
$router->add('talento-humano/accion-personal/buscar-servidor',  'AccionPersonalController', 'buscarServidor');
$router->add('talento-humano/accion-personal/buscar-por-cedula','AccionPersonalController', 'buscarPorCedula');

// Rutas del módulo Administración y Seguridad
$router->add('admin/usuarios',                    'AdminController', 'usuarios');
$router->add('admin/roles',                       'AdminController', 'roles');
$router->add('admin/politicas',                   'AdminController', 'politicas');

// Rutas del módulo Auditoría y Control
$router->add('reportes',                          'AuditoriaController', 'reportes');
$router->add('auditoria/logs',                    'AuditoriaController', 'logs');

// Ruta por defecto
$router->add('', 'EmpleadoController', 'index');

if ($router->dispatch() === false) {
    return false;
}
