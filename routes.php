<?php
/** @var Router $router */

/* ── Auth ───────────────────────────────────────── */
$router->get('/login',              'AuthController@showLogin');
$router->post('/login',             'AuthController@login');
$router->get('/login/verificar',    'AuthController@showMfaChallenge');
$router->post('/login/verificar',   'AuthController@verifyMfaChallenge');
$router->get('/logout',             'AuthController@logout');
$router->post('/api/keepalive',     'AuthController@keepalive');
$router->post('/set-theme',         'AuthController@setTheme');

/* ── Home (public landing) ──────────────────────── */
$router->get('/',                   'HomeController@index');

/* ── Dashboard ──────────────────────────────────── */
$router->get('/dashboard',              'DashboardController@index');
$router->get('/dashboard/ejecutivo',    'DashboardController@executive');
$router->get('/dashboard/operativo',    'DashboardController@operational');
$router->get('/dashboard/exportar-excel','DashboardController@exportarExcel');
$router->get('/api/dashboard/ejecutivo','DashboardController@apiEjecutivo');
$router->get('/api/dashboard/operativo','DashboardController@apiOperativo');
$router->get('/api/dashboard/drilldown','DashboardController@apiDrilldown');
$router->get('/reportes',               'DashboardController@reportes');

/* ── Paneles nativos de módulos integrados (KPIs en vivo, cross-DB) ── */
$router->get('/panel/talento-humano', 'PanelController@talentoHumano');
$router->get('/panel/bienes',         'PanelController@bienes');

/* ── Sistemas origen embebidos: redirección robusta /apps/{app} → /apps/{app}/ ── */
$router->get('/apps/{app}',                     'AppsController@abrir');

/* ── Gate de MFA al cambiar de módulo integrado (ver ModuleGateController) ── */
$router->get('/ir',                             'ModuleGateController@abrir');
$router->post('/ir/verificar',                  'ModuleGateController@verificar');

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
$router->get('/perfil/seguridad',               'AuthController@showSeguridad');
$router->post('/perfil/seguridad/preparar',     'AuthController@prepararMfa');
$router->post('/perfil/seguridad/activar',      'AuthController@activarMfa');
$router->post('/perfil/seguridad/desactivar',   'AuthController@desactivarMfa');

/* ── Admin (nivel_jerarquia >= 3) ───────────────── */
$router->get('/admin/usuarios',                 'AdminController@usuarios');
$router->get('/admin/usuarios/export/excel',    'AdminController@exportarUsuariosExcel');
$router->get('/admin/usuarios/export/pdf',      'AdminController@exportarUsuariosPdf');
$router->get('/admin/usuarios/{id}/export/excel','AdminController@exportarUsuarioExcel');
$router->get('/admin/usuarios/{id}/export/pdf', 'AdminController@exportarUsuarioPdf');
// "Nuevo Usuario" = SOLO desde Talento Humano (sin creación manual).
$router->get('/admin/usuarios/nuevo',                 'AdminController@empleadosTh');
$router->get('/admin/usuarios/desde-th',              'AdminController@empleadosTh');
$router->get('/admin/usuarios/desde-th/{id}/nuevo',    'AdminController@nuevoUsuarioDesdeEmpleado');
$router->post('/admin/usuarios/desde-th',              'AdminController@crearUsuarioDesdeEmpleado');
$router->get('/admin/usuarios/{id}/editar',     'AdminController@editarUsuario');
$router->post('/admin/usuarios/{id}',           'AdminController@actualizarUsuario');
$router->post('/admin/usuarios/{id}/eliminar',  'AdminController@eliminarUsuario');
$router->post('/admin/usuarios/{id}/activar',   'AdminController@activarUsuario');
$router->post('/admin/usuarios/{id}/permisos',  'AdminController@guardarPermisosUsuario');
$router->post('/admin/usuarios/{id}/completo',  'AdminController@guardarUsuarioCompleto');
$router->get('/admin/departamentos',                'AdminController@departamentos');
$router->get('/admin/departamentos/{id}/editar',    'AdminController@editarDepartamento');
$router->post('/admin/departamentos/{id}',          'AdminController@actualizarDepartamento');
$router->get('/admin/roles',                        'AdminController@roles');
$router->get('/admin/roles/matriz',                 'AdminController@permisosMatriz');

/* ── Inactividad de sesión (solo Administrador general) ── */
$router->get('/admin/inactividad',                       'AdminController@inactividad');
$router->post('/admin/inactividad/global',                'AdminController@actualizarInactividadGlobal');
$router->post('/admin/inactividad/modulo/{modulo}',       'AdminController@actualizarInactividadModulo');
$router->post('/admin/inactividad/usuario/{id}',           'AdminController@actualizarInactividadUsuario');
$router->get('/admin/roles/nuevo',                  'AdminController@nuevoRol');
$router->post('/admin/roles',                       'AdminController@crearRol');
$router->get('/admin/roles/{id}/editar',            'AdminController@editarRol');
$router->post('/admin/roles/{id}',                  'AdminController@actualizarRol');
$router->post('/admin/roles/{id}/eliminar',         'AdminController@eliminarRol');
$router->post('/admin/roles/{id}/activar',          'AdminController@activarRol');
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
$router->post('/admin/menu/guardar-lote',    'MenuController@guardarLote');
$router->get('/admin/menu/sidebar-fragmento','MenuController@sidebarFragmento');

/* ── Admin Módulos (nivel_jerarquia >= 3) — registro de módulos del portal ── */
$router->get('/admin/modulos',                  'ModuloController@index');
$router->get('/admin/modulos/nuevo',            'ModuloController@nuevo');
$router->post('/admin/modulos',                 'ModuloController@crear');
$router->get('/admin/modulos/{id}/editar',      'ModuloController@editar');
$router->post('/admin/modulos/{id}',            'ModuloController@actualizar');
$router->post('/admin/modulos/{id}/toggle',     'ModuloController@toggle');

/* ── Admin: Contenido del Portal (carrusel de fondos + ticker de noticias) ── */
$router->get('/admin/landing',                        'LandingController@index');
$router->post('/admin/landing/imagenes',               'LandingController@subirImagen');
$router->post('/admin/landing/imagenes/{id}/mover',    'LandingController@moverImagen');
$router->post('/admin/landing/imagenes/{id}/toggle',   'LandingController@toggleImagen');
$router->post('/admin/landing/imagenes/{id}/eliminar', 'LandingController@eliminarImagen');
$router->post('/admin/landing/noticias',                'LandingController@crearNoticia');
$router->post('/admin/landing/noticias/{id}',            'LandingController@actualizarNoticia');
$router->post('/admin/landing/noticias/{id}/mover',      'LandingController@moverNoticia');
$router->post('/admin/landing/noticias/{id}/toggle',     'LandingController@toggleNoticia');
$router->post('/admin/landing/noticias/{id}/eliminar',   'LandingController@eliminarNoticia');
$router->post('/admin/landing/consejos',                 'LandingController@crearConsejo');
$router->post('/admin/landing/consejos/{id}',             'LandingController@actualizarConsejo');
$router->post('/admin/landing/consejos/{id}/mover',       'LandingController@moverConsejo');
$router->post('/admin/landing/consejos/{id}/toggle',      'LandingController@toggleConsejo');
$router->post('/admin/landing/consejos/{id}/eliminar',    'LandingController@eliminarConsejo');

// Bitácoras migrado a apps/bitacoras/ (Patrón B, app independiente) — ver
// docs/superpowers/plans/2026-08-03-actualizacion-th-bienes-bitacoras-apps.md
