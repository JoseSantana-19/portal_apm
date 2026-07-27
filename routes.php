<?php
/** @var Router $router */

/* ── Auth ───────────────────────────────────────── */
$router->get('/login',              'AuthController@showLogin');
$router->post('/login',             'AuthController@login');
$router->get('/logout',             'AuthController@logout');
$router->post('/set-theme',         'AuthController@setTheme');

/* ── Home (public landing) ──────────────────────── */
$router->get('/',                   'HomeController@index');
$router->get('/api/demo-sso',       'HomeController@demoSso');

/* ── Dashboard ──────────────────────────────────── */
$router->get('/dashboard',          'DashboardController@index');
$router->get('/dashboard/ejecutivo','DashboardController@executive');
$router->get('/dashboard/operativo','DashboardController@operational');
$router->get('/reportes',           'DashboardController@reportes');

/* ── Paneles nativos de módulos integrados (KPIs en vivo, cross-DB) ── */
$router->get('/panel/talento-humano', 'PanelController@talentoHumano');
$router->get('/panel/bienes',         'PanelController@bienes');

/* ── Control de Acceso ──────────────────────────── */
$router->get('/acceso',                         'AccesoController@index');
$router->get('/acceso/ingresar',                'AccesoController@ingresar');
$router->post('/acceso/ingresar',               'AccesoController@registrarIngreso');
$router->post('/acceso/salida',                 'AccesoController@registrarSalida');
$router->get('/acceso/reporte',                 'AccesoController@reporte');
$router->get('/acceso/visitantes',              'VisitanteController@index');
$router->get('/acceso/visitantes/nuevo',        'VisitanteController@create');
$router->post('/acceso/visitantes',             'VisitanteController@store');
$router->get('/acceso/visitantes/{id}',         'VisitanteController@show');

/* ── Sistemas origen embebidos: redirección robusta /apps/{app} → /apps/{app}/ ── */
$router->get('/apps/{app}',                     'AppsController@abrir');

/* ── SSO central para módulos (server-to-server, ver db/sso_module_login.sql) ── */
$router->post('/api/sso/login',                 'ApiSsoController@login');
$router->post('/api/sso/validate',              'ApiSsoController@validate');
$router->post('/api/sso/logout',                'ApiSsoController@logout');

/* ── Notificaciones (API) ───────────────────────── */
$router->get('/notificaciones',                 'NotificacionesController@index');
$router->get('/notificaciones/recientes',       'NotificacionesController@recientes');
$router->post('/notificaciones/marcar-leidas',  'NotificacionesController@marcarLeidas');

/* ── Perfil ─────────────────────────────────────── */
$router->get('/perfil',                         'AuthController@perfil');
$router->post('/perfil',                        'AuthController@actualizarPerfil');
$router->get('/cambiar-contrasena',             'AuthController@showCambiarContrasena');
$router->post('/cambiar-contrasena',            'AuthController@cambiarContrasena');

/* ── Admin (nivel_jerarquia >= 3) ───────────────── */
$router->get('/admin/usuarios',                 'AdminController@usuarios');
$router->get('/admin/usuarios/export/excel',    'AdminController@exportarUsuariosExcel');
$router->get('/admin/usuarios/export/pdf',      'AdminController@exportarUsuariosPdf');
$router->get('/admin/usuarios/{id}/export/excel','AdminController@exportarUsuarioExcel');
$router->get('/admin/usuarios/{id}/export/pdf', 'AdminController@exportarUsuarioPdf');
$router->get('/admin/usuarios/nuevo',           'AdminController@nuevoUsuario');
$router->get('/admin/usuarios/desde-th',              'AdminController@empleadosTh');
$router->get('/admin/usuarios/desde-th/{id}/nuevo',    'AdminController@nuevoUsuarioDesdeEmpleado');
$router->post('/admin/usuarios/desde-th',              'AdminController@crearUsuarioDesdeEmpleado');
$router->post('/admin/usuarios',                'AdminController@crearUsuario');
$router->get('/admin/usuarios/{id}/editar',     'AdminController@editarUsuario');
$router->post('/admin/usuarios/{id}',           'AdminController@actualizarUsuario');
$router->post('/admin/usuarios/{id}/eliminar',  'AdminController@eliminarUsuario');
$router->get('/admin/roles',                        'AdminController@roles');
$router->get('/admin/roles/nuevo',                  'AdminController@nuevoRol');
$router->post('/admin/roles',                       'AdminController@crearRol');
$router->get('/admin/roles/{id}/editar',            'AdminController@editarRol');
$router->post('/admin/roles/{id}',                  'AdminController@actualizarRol');
$router->post('/admin/roles/{id}/eliminar',         'AdminController@eliminarRol');
$router->get('/admin/roles/{id}/permisos',          'AdminController@rolPermisos');
$router->post('/admin/roles/{id}/permisos',         'AdminController@guardarPermisos');
$router->get('/admin/auditoria',                    'AdminController@auditoria');
$router->get('/admin/auditoria/export/pdf',         'AdminController@exportarAuditoriaPdf');
$router->get('/admin/auditoria/export/excel',       'AdminController@exportarAuditoriaExcel');

/* ── Admin Menu (nivel_jerarquia >= 3) ──────────── */
$router->get('/admin/menu',                  'MenuController@index');
$router->get('/admin/menu/nuevo',            'MenuController@nuevo');
$router->post('/admin/menu',                 'MenuController@crear');
$router->get('/admin/menu/{id}/editar',      'MenuController@editar');
$router->post('/admin/menu/{id}',            'MenuController@actualizar');
$router->post('/admin/menu/{id}/toggle',     'MenuController@toggle');
$router->post('/admin/menu/{id}/eliminar',   'MenuController@eliminar');

/* ── Portuaria (Bitácoras CCTV/Visitas/Rondas — integrado de portuaria_demoV4) ──
 * Los paths replican los alias del proyecto origen para que el JS portado
 * (listado_visitas.js, bitacora_rondas.js, bit_camaras.js, catalogos.js…)
 * funcione sin reescritura. BDs: PortuariaDemo / PortuariaExterna.       */

// Vistas nativas en el shell del portal (hub + vistas rápidas)
$router->get('/portuaria',                        'PortalPortuariaController@hub');
$router->get('/portuaria/visitas-resumen',        'PortalPortuariaController@visitasResumen');
$router->get('/portuaria/actividad',              'PortalPortuariaController@actividad');

// Dashboards del módulo (layout propio Bootstrap)
$router->get('/portuaria/dashboard',              'PortDashboardController@index');
$router->get('/dashboard-jefe',                   'PortDashboardController@jefe');
$router->get('/dashboard-ejecutivo',              'PortDashboardController@ejecutivo');

// Visitas (Bitácoras)
$router->get('/visitas',                          'PortVisitaController@listado');
$router->get('/visitas/registrar',                'PortVisitaController@registrar');
$router->post('/bitacoras/visita/guardar',        'PortVisitaController@guardar');
$router->get('/bitacoras/visita/listado',         'PortVisitaController@listado');
$router->post('/bitacoras/visita/registrarSalida','PortVisitaController@registrarSalida');
$router->post('/bitacoras/visita/actualizarHoras','PortVisitaController@actualizarHoras');
$router->post('/bitacoras/visita/actualizar',     'PortVisitaController@actualizar');
$router->get('/bitacoras/visita/detalle',         'PortVisitaController@detalle');

// Bitácora de rondas
$router->get('/rondas',                           'PortRondaController@index');
$router->get('/bitacoras/ronda/api',              'PortRondaController@api');
$router->post('/bitacoras/ronda/api',             'PortRondaController@api');

// CCTV Cámaras (bitácora + maestro + motivos)
$router->get('/camaras',                          'PortCamaraController@index');
$router->get('/camaras/motivos',                  'PortCamaraController@motivos');
$router->get('/camaras/inventario',               'PortCamaraController@inventario');
$router->get('/bitacoras/camara/api',             'PortCamaraController@api');
$router->post('/bitacoras/camara/api',            'PortCamaraController@api');
$router->get('/bitacoras/camara/apiMotivos',      'PortCamaraController@apiMotivos');
$router->post('/bitacoras/camara/apiMotivos',     'PortCamaraController@apiMotivos');
$router->get('/bitacoras/camara/apiInventario',   'PortCamaraController@apiInventario');
$router->post('/bitacoras/camara/apiInventario',  'PortCamaraController@apiInventario');

// Catálogos maestros
$router->get('/catalogos',                        'PortCatalogoController@index');
$router->get('/catalogos/personas',               'PortCatalogoController@personas');
$router->get('/catalogos/empresas',               'PortCatalogoController@empresas');
$router->get('/catalogos/destinos',               'PortCatalogoController@destinos');
$router->get('/catalogos/motivos',                'PortCatalogoController@motivos');
$router->get('/catalogos/funcionarios',           'PortCatalogoController@funcionarios');
$router->get('/catalogos/niveles-incidente',      'PortCatalogoController@nivelesIncidente');
$router->get('/importar-funcionarios',            'PortCatalogoController@importarFuncionarios');
$router->get('/bitacoras/catalogo/api',           'PortCatalogoController@api');
$router->post('/bitacoras/catalogo/api',          'PortCatalogoController@api');
$router->get('/bitacoras/catalogo/apiPersonas',   'PortCatalogoController@apiPersonas');
$router->post('/bitacoras/catalogo/apiPersonas',  'PortCatalogoController@apiPersonas');
