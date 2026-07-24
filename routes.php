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

/* ── Talento Humano (módulo nativo, BD Talento_Humano) ─────────────── */
// Panel del módulo (hub con KPIs de la BD Talento_Humano)
$router->get('/th',                             'ThHubController@index');
// Directorio de Personal
$router->get('/th/directorio',                  'ThDirectorioController@directorio');
$router->get('/th/empleado/nuevo',              'ThDirectorioController@crear');
$router->post('/th/empleado/guardar',           'ThDirectorioController@guardar');
$router->post('/th/empleado/eliminar',          'ThDirectorioController@eliminar');
$router->get('/th/empleado/ficha',              'ThDirectorioController@imprimirFicha');
$router->get('/th/empleado/{id}/editar',        'ThDirectorioController@editar');
$router->get('/th/empleado/{id}/perfil',        'ThDirectorioController@perfil');
$router->get('/th/reporte',                     'ThDirectorioController@reporte');
$router->get('/th/reporte/export/excel',        'ThDirectorioController@exportarReporteExcel');
$router->get('/th/reporte/export/pdf',          'ThDirectorioController@exportarReportePdf');

// Crear cuenta de acceso del portal desde un empleado TH (solo admin)
$router->get('/th/empleado/{id}/cuenta',        'ThCuentaController@crear');
$router->post('/th/empleado/cuenta',            'ThCuentaController@guardar');

// Acción de Personal (LOSEP Art. 21)
$router->get('/th/accion-personal',                    'ThAccionPersonalController@index');
$router->post('/th/accion-personal/guardar',           'ThAccionPersonalController@guardar');
$router->get('/th/accion-personal/ver',                'ThAccionPersonalController@ver');
$router->get('/th/accion-personal/imprimir',           'ThAccionPersonalController@imprimirAccion');
$router->get('/th/accion-personal/buscar-servidor',    'ThAccionPersonalController@buscarServidor');
$router->get('/th/accion-personal/buscar-por-cedula',  'ThAccionPersonalController@buscarPorCedula');
$router->get('/th/accion-personal/export/excel',       'ThAccionPersonalController@exportarAccionesExcel');
$router->get('/th/accion-personal/export/pdf',         'ThAccionPersonalController@exportarAccionesPdf');

// Gestión Operativa
$router->get('/th/asistencia',                  'ThAsistenciaController@index');
$router->get('/th/vacaciones',                  'ThVacacionesController@index');
$router->get('/th/desempeno',                   'ThDesempenoController@index');
$router->get('/th/capacitacion',                'ThCapacitacionController@index');

/* ── Bitácoras ──────────────────────────────────── */
$router->get('/bitacoras',                      'EventoController@index');
$router->get('/bitacoras/nuevo',                'EventoController@create');
$router->post('/bitacoras',                     'EventoController@store');
$router->get('/bitacoras/{id}',                 'EventoController@show');
$router->get('/bitacoras/{id}/editar',          'EventoController@edit');
$router->post('/bitacoras/{id}',                'EventoController@update');
$router->post('/bitacoras/{id}/cerrar',         'EventoController@close');
$router->get('/bitacoras/reportes',             'ReporteController@index');

/* ── Control de Bienes ──────────────────────────── */
$router->get('/bienes',                         'BienController@index');
$router->get('/bienes/nuevo',                   'BienController@create');
$router->post('/bienes',                        'BienController@store');
$router->get('/bienes/{id}',                    'BienController@show');
$router->get('/bienes/{id}/editar',             'BienController@edit');
$router->post('/bienes/{id}',                   'BienController@update');
$router->post('/bienes/{id}/dar-baja',          'BienController@darBaja');
$router->get('/bienes/movimientos',             'MovimientoController@index');
$router->get('/bienes/movimientos/nuevo',       'MovimientoController@create');
$router->post('/bienes/movimientos',            'MovimientoController@store');

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

/* ── Inventario (Control de Bienes — módulo independiente, BD `inventario`) ── */
$router->get('/inventario/panel',                 'InvPanelController@index');
$router->get('/inventario',                       'InventarioController@index');
$router->get('/inventario/catalogo',              'InventarioController@catalogo');
$router->get('/inventario/items',                 'InventarioController@items');
$router->get('/inventario/exportar',              'InventarioController@exportar');
$router->post('/inventario/guardar',              'InventarioController@guardar');
$router->get('/inventario/maestros',              'MaestrosController@index');
$router->post('/inventario/maestros/guardar',     'MaestrosController@guardar');
$router->post('/inventario/maestros/eliminar',    'MaestrosController@eliminar');
$router->get('/inventario/ingresos',              'MonitoreoController@ingresos');
$router->get('/inventario/egresos',               'MonitoreoController@egresos');
$router->get('/inventario/periodos',              'ConfigInventarioController@periodos');
$router->post('/inventario/periodos',             'ConfigInventarioController@crearPeriodo');
$router->get('/inventario/secuenciales',          'ConfigInventarioController@secuenciales');
$router->post('/inventario/secuenciales/reiniciar','ConfigInventarioController@reiniciarSecuencial');
$router->get('/inventario/{id}/detalle',          'InventarioController@verDetalle');
$router->post('/inventario/{id}/eliminar',        'InventarioController@eliminar');

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
