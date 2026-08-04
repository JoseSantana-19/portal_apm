<?php
/**
 * apps/bitacoras/routes.php — Rutas del módulo Bitácoras (portado 1:1 desde
 * routes.php del portal, sección Portuaria). Los paths se mantienen
 * IDÉNTICOS a los del origen para que el JS portado (listado_visitas.js,
 * bitacora_rondas.js, bit_camaras.js, catalogos.js…) funcione sin
 * reescritura. BDs: PortuariaDemo / PortuariaExterna.
 */

// Home de la app: dashboard propio (layout Bootstrap del módulo). El hub
// SPA original (PortalPortuariaController@hub) requería el shell del
// portal — no aplica acá, la app es standalone (Patrón B).
$router->get('/',                                 'PortDashboardController@index');

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
