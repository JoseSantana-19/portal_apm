<?php
/**
 * FRONT CONTROLLER – Punto de entrada único de Talento Humano
 * (embebido en el Portal APM). Toda petición HTTP pasa por aquí.
 */

define('ROOT', __DIR__);

require_once dirname(ROOT, 2) . '/helpers/polyfills_php74.php';

// BASE_URL autodetectada — app embebida en el Portal APM (subcarpeta
// /portal_apm/apps/talento_humano en Apache; vacía en php -S), misma
// técnica que Control de Bienes y Bitácoras.
$_isDevServer = php_sapi_name() === 'cli-server';
$_basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
define('BASE_URL', $_isDevServer ? '' : $_basePath);

// Raíz del portal (botón "Volver al Portal APM" + puente de identidad).
$__https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['SERVER_PORT'] ?? '') == 443);
$__scheme = $__https ? 'https' : 'http';
$__host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$__portalBase = preg_replace('#/apps/talento_humano$#', '', $_basePath);
define('PORTAL_ROOT_URL', $__scheme . '://' . $__host . $__portalBase);

// Nombre Y ruta de guardado de la sesión del portal (los que php.ini asigna
// por defecto, antes de que Auth::configureSession() los cambie a
// 'APMSESSID' + el directorio privado de esta app más abajo) — hacen falta
// los DOS para poder leer/escribir la sesión del portal desde dentro de
// esta app sin pisar la sesión propia de TH: session_save_path() es un
// ajuste global por request, cambiarle solo el nombre a la sesión no basta
// -- sin restaurar también la ruta, "la sesión del portal" se buscaría por
// error dentro del directorio privado de TH. Ver Auth::loginTrusted() y
// Auth::syncPortalSession() en core/Auth.php.
define('PORTAL_SESSION_NAME', session_name());
define('PORTAL_SESSION_SAVE_PATH', session_save_path());

require_once ROOT . '/core/Config.php';
date_default_timezone_set(Config::timezone());
require_once ROOT . '/core/InstitutionalClock.php';
// Red de seguridad global para excepciones/errores fatales NO capturados en
// ningún otro punto (equivalente PHP 7.4 -- sin match()/never de origen).
// Los caminos ya establecidos (Auth::requireCsrf/requirePermission,
// Conexion::registrarErrorLog) siguen manejando sus propios casos tal cual
// estaban -- este solo cubre lo que se escape de ahí.
require_once ROOT . '/core/ErrorHandler.php';
ErrorHandler::register();
require_once ROOT . '/config/Catalogos.php';
require_once ROOT . '/core/Database.php'; // Conexion -- Auth (loginTrusted/syncPortalSession) la necesita antes del puente
require_once ROOT . '/core/Auth.php';
Auth::configureSession();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* ── PUENTE DE IDENTIDAD PORTAL APM -> TALENTO HUMANO ───────────────────────
   Esta app tiene su PROPIO login/MFA/bloqueo/sesión (core/Auth.php) y se
   conserva tal cual -- no se reemplaza ni se debilita nada de eso. Se
   agrega solo un puente (misma idea que Control de Bienes): si quien
   navega ya inició sesión en el portal y existe una cuenta correspondiente
   en th_usuarios_sistema, entra directo sin pedir clave de nuevo. Solo se
   intenta cuando esta app AÚN no tiene sesión propia activa -- evita abrir
   la sesión del portal en cada request de alguien que ya está autenticado
   acá. */
if (!Auth::check()) {
    $__thSid = session_id();
    session_write_close();

    // Lectura pasiva de la sesión del portal: use_strict_mode=1 (activado
    // por Auth::configureSession() más arriba, ini_set() es global y NO se
    // revierte solo por cambiar session_name()) hace que un id sin sesión
    // real en disco se descarte y PHP emita un id NUEVO vía Set-Cookie --
    // exactamente lo que NO queremos en un simple vistazo de lectura: eso
    // pisaría la cookie de sesión real del portal con una vacía. Se apaga
    // solo para este vistazo puntual.
    ini_set('session.use_strict_mode', '0');
    session_save_path(PORTAL_SESSION_SAVE_PATH);
    session_name(PORTAL_SESSION_NAME);
    // session_id('') NO alcanza (fija el id a la cadena vacía, un valor
    // inválido -- no "resuélvelo de la cookie"). Sin resolver el id a mano,
    // session_start() reutiliza el id que ya tenía activo (el de TH, recién
    // cerrado) en vez de leer la cookie PHPSESSID real -- exactamente el
    // bug que pisaba la cookie del portal con el id de TH.
    if (isset($_COOKIE[PORTAL_SESSION_NAME]) && $_COOKIE[PORTAL_SESSION_NAME] !== '') {
        session_id($_COOKIE[PORTAL_SESSION_NAME]);
    }
    session_start();
    $__portalUserId = !empty($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
    $__portalNivel = (int)($_SESSION['nivel_jerarquia'] ?? 0);
    session_write_close();

    Auth::configureSession(); // restaura nombre + ruta + cookie + strict mode propios de TH
    session_id($__thSid);
    session_start();

    if ($__portalUserId !== null) {
        Auth::loginTrusted($__portalUserId, $__portalNivel);
    }
}

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: same-origin');
header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
$__mapTileOrigin = Config::mapTileOrigin();
header("Content-Security-Policy: default-src 'self'; base-uri 'self'; frame-ancestors 'none'; form-action 'self'; object-src 'none'; img-src 'self' data: {$__mapTileOrigin}; font-src 'self' https://fonts.gstatic.com https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net; script-src 'self' 'unsafe-inline'; connect-src 'self'");
if ((!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off')
    || (Config::trustProxyHeaders() && strtolower((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https')) {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

// ── Carga de configuración y núcleo ──────────────────────────────────────────
require_once ROOT . '/rutas/config_routes.php';
require_once ROOT . '/core/Router.php';
require_once ROOT . '/core/Controller.php';
require_once ROOT . '/core/Model.php';
require_once ROOT . '/core/Database.php';
require_once ROOT . '/core/XlsxWriter.php';
require_once ROOT . '/core/TopbarService.php';
require_once ROOT . '/core/DraftService.php';
require_once ROOT . '/core/DraftController.php';
require_once ROOT . '/core/AuthController.php';

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
require_once ROOT . '/modules/talento-humano/Controladores/PazSalvoController.php';

// ── Controladores del módulo Administración ───────────────────────────────────
require_once ROOT . '/modules/admin/Controladores/AdminController.php';
require_once ROOT . '/modules/admin/Controladores/MaestroController.php';

// ── Controladores del módulo Auditoría ───────────────────────────────────────
require_once ROOT . '/modules/auditoria/Controladores/AuditoriaController.php';

// ── Despachar la URL ──────────────────────────────────────────────────────────
$router = new Router();

$router->add('login',                             'AuthController', 'login');
$router->add('login/autenticar',                 'AuthController', 'authenticate');
$router->add('login/verificar',                   'AuthController', 'mfaForm');
$router->add('login/verificar-mfa',               'AuthController', 'verifyMfa');
$router->add('login/cancelar-mfa',                'AuthController', 'cancelMfa');
$router->add('logout',                           'AuthController', 'logout');
$router->add('cuenta/cambiar-clave',             'AuthController', 'changePasswordForm');
$router->add('cuenta/actualizar-clave',           'AuthController', 'changePassword');
$router->add('cuenta/seguridad',                  'AuthController', 'securityForm');
$router->add('cuenta/seguridad/preparar-mfa',     'AuthController', 'prepareMfa');
$router->add('cuenta/seguridad/activar-mfa',      'AuthController', 'activateMfa');
$router->add('cuenta/seguridad/desactivar-mfa',   'AuthController', 'disableMfa');
$router->add('sesion/renovar',                    'AuthController', 'renewSession');
$router->add('sesion/expirar',                    'AuthController', 'expireSession');

// Rutas del módulo Talento Humano – Principal
$router->add('talento-humano',                    'EmpleadoController', 'index');
$router->add('talento-humano/inicio',             'EmpleadoController', 'inicio');
$router->add('talento-humano/directorio',         'EmpleadoController', 'directorio');
$router->add('talento-humano/empleado/crear',     'EmpleadoController', 'crear');
$router->add('talento-humano/empleado/guardar',   'EmpleadoController', 'guardar');
$router->add('talento-humano/empleado/verificar-cedula', 'EmpleadoController', 'verificarCedula');
$router->add('talento-humano/empleado/editar',    'EmpleadoController', 'editar');
$router->add('talento-humano/empleado/borrar',    'EmpleadoController', 'borrar');
$router->add('talento-humano/empleado/eliminar',  'EmpleadoController', 'eliminar');
$router->add('talento-humano/empleado/perfil/{cedula}', 'EmpleadoController', 'perfil');
$router->add('talento-humano/empleado/imprimir-ficha',  'EmpleadoController', 'imprimirFicha');
$router->add('talento-humano/empleado/formato-principal-blanco', 'EmpleadoController', 'formatoPrincipalBlanco');
$router->add('talento-humano/empleado/exportar',         'EmpleadoController', 'exportarCsv');
$router->add('talento-humano/empleado/movimiento',      'EmpleadoController', 'movimiento');
$router->add('talento-humano/empleado/mover',           'EmpleadoController', 'mover');
$router->add('talento-humano/empleado/movimiento-grupal','EmpleadoController', 'movimientoGrupal');
$router->add('talento-humano/empleado/mover-lote',       'EmpleadoController', 'moverLote');
$router->add('talento-humano/empleado/buscar-personal',  'EmpleadoController', 'buscarPersonal');
$router->add('talento-humano/reporte',            'EmpleadoController', 'reporte');

// Rutas del módulo Talento Humano – Gestión Operativa
$router->add('talento-humano/biblioteca',          'BibliotecaController',  'index');
$router->add('talento-humano/asistencia',          'AsistenciaController',  'index');
$router->add('talento-humano/vacaciones',          'VacacionesController',  'index');
$router->add('talento-humano/desempeno',           'DesempenoController',   'index');
$router->add('talento-humano/capacitacion',        'CapacitacionController','index');
$router->add('talento-humano/paz-salvo',           'PazSalvoController','index');
$router->add('talento-humano/paz-salvo/crear',     'PazSalvoController','crear');
$router->add('talento-humano/paz-salvo/guardar',   'PazSalvoController','guardar');
$router->add('talento-humano/paz-salvo/ver',       'PazSalvoController','ver');
$router->add('talento-humano/paz-salvo/guardar-seccion','PazSalvoController','guardarSeccion');
$router->add('talento-humano/paz-salvo/cerrar',    'PazSalvoController','cerrar');
$router->add('talento-humano/paz-salvo/imprimir',  'PazSalvoController','imprimir');
$router->add('talento-humano/paz-salvo/formato-blanco','PazSalvoController','formatoBlanco');

// Rutas del módulo Talento Humano – Estudio de Seguridad Socioeconómico
$router->add('talento-humano/estudio-seguridad',          'EstudioSeguridadController', 'index');
$router->add('talento-humano/estudio-seguridad/guardar',  'EstudioSeguridadController', 'guardar');
$router->add('talento-humano/estudio-seguridad/imprimir', 'EstudioSeguridadController', 'imprimir');
$router->add('talento-humano/estudio-seguridad/resolver-mapa', 'EstudioSeguridadController', 'resolverMapa');

// Rutas del módulo Talento Humano – Fase 2: Acción de Personal
$router->add('talento-humano/accion-personal',                  'AccionPersonalController', 'index');
$router->add('talento-humano/accion-personal/guardar',          'AccionPersonalController', 'guardar');
$router->add('talento-humano/accion-personal/ver',              'AccionPersonalController', 'ver');
$router->add('talento-humano/accion-personal/imprimir-accion',  'AccionPersonalController', 'imprimirAccion');
$router->add('talento-humano/accion-personal/formato-blanco',   'AccionPersonalController', 'formatoBlanco');
$router->add('talento-humano/accion-personal/aprobar',          'AccionPersonalController', 'aprobar');
$router->add('talento-humano/accion-personal/anular',           'AccionPersonalController', 'anular');
$router->add('talento-humano/accion-personal/buscar-servidor',  'AccionPersonalController', 'buscarServidor');
$router->add('talento-humano/accion-personal/buscar-por-cedula','AccionPersonalController', 'buscarPorCedula');
$router->add('talento-humano/accion-personal/catalogo/unidad', 'AccionPersonalController', 'crearUnidadRapida');
$router->add('talento-humano/accion-personal/catalogo/puesto', 'AccionPersonalController', 'crearPuestoRapido');
$router->add('talento-humano/catalogo/unidad', 'AccionPersonalController', 'crearUnidadRapida');
$router->add('talento-humano/catalogo/puesto', 'AccionPersonalController', 'crearPuestoRapido');

// Rutas del módulo Administración y Seguridad
$router->add('admin/usuarios',                    'AdminController', 'usuarios');
$router->add('admin/usuarios/crear',              'AdminController', 'crearUsuario');
$router->add('admin/usuarios/estado',             'AdminController', 'estadoUsuario');
$router->add('admin/usuarios/resetear-clave',     'AdminController', 'resetearClave');
$router->add('admin/usuarios/resetear-mfa',       'AdminController', 'resetearMfa');
$router->add('admin/roles',                       'AdminController', 'roles');
$router->add('admin/roles/crear',                 'AdminController', 'crearRol');
$router->add('admin/roles/estado',                'AdminController', 'estadoRol');
$router->add('admin/roles/guardar-permisos',      'AdminController', 'guardarPermisos');
$router->add('admin/politicas',                   'AdminController', 'politicas');
$router->add('admin/politicas/subir',             'AdminController', 'subirDocumento');
$router->add('admin/politicas/retirar',           'AdminController', 'retirarDocumento');
$router->add('admin/politicas/descargar',         'AdminController', 'descargarDocumento');
$router->add('admin/maestros',                    'MaestroController', 'index');
$router->add('admin/maestros/unidad/guardar',     'MaestroController', 'guardarUnidad');
$router->add('admin/maestros/puesto/guardar',     'MaestroController', 'guardarPuesto');

// Rutas del módulo Auditoría y Control
$router->add('reportes',                          'AuditoriaController', 'reportes');
$router->add('reportes/exportar-csv',             'AuditoriaController', 'exportarCsv');
$router->add('reportes/exportar-pdf',             'AuditoriaController', 'exportarPdf');
$router->add('reportes/exportar-excel',           'AuditoriaController', 'exportarExcel');
$router->add('auditoria/logs',                    'AuditoriaController', 'logs');
$router->add('auditoria/logs/exportar',           'AuditoriaController', 'exportarLogs');
$router->add('auditoria/reportes',                'AuditoriaController', 'reporteAuditoria');
$router->add('auditoria/reportes/exportar',       'AuditoriaController', 'exportarReporteAuditoria');
$router->add('borradores/obtener',                'DraftController', 'load');
$router->add('borradores/guardar',                'DraftController', 'save');
$router->add('borradores/eliminar',               'DraftController', 'delete');

// Ruta por defecto
$router->add('', 'EmpleadoController', 'index');

$_rutaSolicitada = trim((string)($_GET['url'] ?? parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH)), '/');
if (BASE_URL !== '' && str_starts_with('/' . $_rutaSolicitada, BASE_URL . '/')) {
    $_rutaSolicitada = trim(substr('/' . $_rutaSolicitada, strlen(BASE_URL)), '/');
}
$esArchivoReal = $_rutaSolicitada !== '' && is_file(ROOT . '/' . $_rutaSolicitada);
$esRecursoEstatico = $esArchivoReal && str_starts_with($_rutaSolicitada, 'public/');
if ($esArchivoReal && !$esRecursoEstatico) {
    http_response_code(404);
    exit('Recurso no encontrado.');
}
$_rutasPublicas=['login','login/autenticar','login/verificar','login/verificar-mfa','login/cancelar-mfa','sesion/expirar'];
if (!$esRecursoEstatico && !in_array($_rutaSolicitada, $_rutasPublicas, true)) {
    Auth::requireAuthentication();

    if (!empty(Auth::user()['password_change_required'])
        && !in_array($_rutaSolicitada,['cuenta/cambiar-clave','cuenta/actualizar-clave','logout'],true)) {
        header('Location: '.BASE_URL.'/cuenta/cambiar-clave');
        exit;
    }

    $method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
    $routePolicies = [
        ['#^admin/usuarios/crear$#', 'usuarios', 'crear'],
        ['#^admin/usuarios/(?:estado|resetear-clave|resetear-mfa)$#', 'usuarios', 'editar'],
        ['#^admin/usuarios$#', 'usuarios', 'visualizar'],
        ['#^admin/roles/crear$#', 'roles', 'crear'],
        ['#^admin/roles/(?:estado|guardar-permisos)$#', 'roles', 'editar'],
        ['#^admin/roles$#', 'roles', 'visualizar'],
        ['#^admin/politicas/subir$#', 'politicas', 'crear'],
        ['#^admin/politicas/retirar$#', 'politicas', 'editar'],
        ['#^admin/politicas(?:/|$)#', 'politicas', 'visualizar'],
        ['#^admin/maestros/(?:unidad|puesto)/guardar$#', 'maestros', empty($_POST['unidad_id']) && empty($_POST['puesto_id']) ? 'crear' : 'editar'],
        ['#^admin/maestros$#', 'maestros', 'visualizar'],
        ['#^auditoria/(?:logs|reportes)(?:/|$)#', 'auditoria', 'visualizar'],
        ['#^reportes(?:/|$)#', 'reportes', 'visualizar'],
        ['#^talento-humano/empleado/eliminar$#', 'directorio', 'eliminar'],
        ['#^talento-humano/empleado/verificar-cedula$#', 'directorio', 'visualizar'],
        ['#^talento-humano/empleado/crear$#', 'empleados', 'crear'],
        ['#^talento-humano/empleado/editar$#', 'empleados', 'editar'],
        ['#^talento-humano/empleado/guardar$#', 'empleados', empty($_POST['empId']) ? 'crear' : 'editar'],
        ['#^talento-humano/empleado/(?:movimiento|mover|movimiento-grupal|mover-lote)$#', 'movimientos', $method === 'GET' ? 'visualizar' : 'crear'],
        ['#^talento-humano/empleado/buscar-personal$#', 'directorio', 'visualizar'],
        ['#^talento-humano/empleado/exportar$#', 'directorio', 'visualizar'],
        ['#^talento-humano/(?:directorio|empleado/perfil|empleado/imprimir-ficha)(?:/|$)#', 'directorio', 'visualizar'],
        ['#^talento-humano/empleado/formato-principal-blanco$#', 'biblioteca', 'visualizar'],
        ['#^talento-humano/reporte(?:/|$)#', 'directorio', 'visualizar'],
        ['#^talento-humano/accion-personal/(?:aprobar|anular)$#', 'acciones', 'editar'],
        ['#^talento-humano/catalogo/(?:unidad|puesto)$#', 'maestros', 'crear'],
        ['#^talento-humano/accion-personal/catalogo/(?:unidad|puesto)$#', 'maestros', 'crear'],
        ['#^talento-humano/accion-personal(?:/|$)#', 'acciones', $method === 'GET' ? 'visualizar' : 'crear'],
        ['#^talento-humano/estudio-seguridad/guardar$#', 'socioeconomico', empty($_POST['estudio_id']) ? 'crear' : 'editar'],
        ['#^talento-humano/estudio-seguridad/resolver-mapa$#', 'socioeconomico', empty($_POST['estudio_id']) ? 'crear' : 'editar'],
        ['#^talento-humano/estudio-seguridad(?:/|$)#', 'socioeconomico', 'visualizar'],
        ['#^talento-humano/biblioteca(?:/|$)#', 'biblioteca', 'visualizar'],
        ['#^talento-humano/vacaciones(?:/|$)#', 'vacaciones', 'visualizar'],
        ['#^talento-humano/paz-salvo/(?:guardar|guardar-seccion)$#', 'paz_salvo', 'crear'],
        ['#^talento-humano/paz-salvo/cerrar$#', 'paz_salvo', 'editar'],
        ['#^talento-humano/paz-salvo(?:/|$)#', 'paz_salvo', 'visualizar'],
        ['#^talento-humano/(?:asistencia|desempeno|capacitacion)(?:/|$)#', 'prototipos', 'visualizar'],
        ['#^(?:talento-humano(?:/inicio)?|)$#', 'dashboard', 'visualizar'],
    ];
    foreach ($routePolicies as [$pattern, $module, $action]) {
        if (preg_match($pattern, $_rutaSolicitada)) {
            Auth::requirePermission($module, $action);
            break;
        }
    }
}

if ($router->dispatch() === false) {
    return false;
}
