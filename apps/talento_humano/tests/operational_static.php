<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$fallos = [];
$assert = static function (bool $condicion, string $mensaje) use (&$fallos): void {
    if (!$condicion) $fallos[] = $mensaje;
};

$directorio = (string)file_get_contents($root . '/modules/talento-humano/Vistas/directorio.php');
$layout = (string)file_get_contents($root . '/public/css/layout.css');
$accion = (string)file_get_contents($root . '/modules/talento-humano/Vistas/accion_personal.php');
$socio = (string)file_get_contents($root . '/modules/talento-humano/Vistas/estudio_seguridad.php');
$controlSocio = (string)file_get_contents($root . '/modules/talento-humano/Controladores/EstudioSeguridadController.php');
$migracion = (string)file_get_contents($root . '/database/migracion_calidad_busqueda_2026.sql');
$reconciliacion = (string)file_get_contents($root . '/scripts/reconciliar_rolmaes.php');
$ciclo = (string)file_get_contents($root . '/database/migracion_ciclo_laboral_2026.sql');
$menu = (string)file_get_contents($root . '/shared/menu.php');
$topbar = (string)file_get_contents($root . '/shared/topbar.php');
$sidebarJs = (string)file_get_contents($root . '/public/js/layout_sidebar.js');
$empleadoController = (string)file_get_contents($root . '/modules/talento-humano/Controladores/EmpleadoController.php');

$assert(!str_contains($directorio, '$i * 0.04'), 'El directorio conserva la demora acumulativa por fila.');
$assert(str_contains($directorio, 'opacity:1;animation:none'), 'Las filas del directorio todavía esperan una animación antes de mostrarse.');
$assert(str_contains($directorio, 'class="row-actions"'), 'Las acciones del Directorio no están agrupadas dentro de una celda de tabla estable.');
$assert(str_contains($layout, '.directory-page .table-wrap') && str_contains($layout, 'overflow-x: auto'), 'El Directorio puede recortar los botones de acción en pantallas estrechas.');
$assert(str_contains($directorio, 'tablePagination') && str_contains($directorio, 'selectionToolbar'), 'El Directorio no tiene paginación o acciones contextuales.');
$assert(str_contains($directorio, '$puedeEliminar') && str_contains($directorio, '$puedeMover'), 'Los botones del Directorio no respetan permisos.');
$assert(str_contains($directorio, '$mostrarMovimiento') && str_contains($directorio, 'if(!$modoMovimiento)'), 'El Directorio normal todavía mezcla controles de movimiento interno.');
$assert(str_contains($directorio, 'btnMovimientoGrupal" onclick') && str_contains($directorio, "classList.toggle('hidden',seleccion.length<2)"), 'El movimiento grupal no se muestra de forma contextual.');
$assert(str_contains($empleadoController, "requirePermission('movimientos', 'visualizar')"), 'El modo Movimiento no exige su permiso específico.');
$assert(str_contains($layout, 'position: sticky') && str_contains($layout, 'right: 0') && str_contains($layout, '.directory-movement-mode'), 'La columna Acciones no permanece visible durante el desplazamiento.');
$assert(str_contains($directorio, 'class="status-heading"') && str_contains($directorio, 'class="status-cell"'), 'El estado laboral no tiene una columna visual protegida.');
$assert(str_contains($layout, 'right: var(--directory-actions-width)') && str_contains($layout, '--directory-status-width'), 'Estado y Acciones pueden superponerse horizontalmente.');
$assert(str_contains($layout, '.selection-toolbar.hidden'), 'La barra de selección puede reaparecer con cero seleccionados.');
$assert(str_contains($sidebarJs, 'sessionStorage') && str_contains($sidebarJs, 'SIDEBAR_SCROLL_KEY') && str_contains($sidebarJs, 'pagehide'), 'El menú lateral no conserva su posición entre páginas.');
$assert(str_contains($directorio, 'requestAnimationFrame'), 'El filtro inmediato del directorio no está activo.');
$assert(str_contains($directorio, 'searchIndex'), 'El directorio no precalcula el índice de búsqueda.');
$assert(str_contains($accion, 'PERSONAL_ACCION'), 'Acción de Personal no incluye autocompletado por nombre/cédula.');
$assert(str_contains($accion, "emp.unidad_id     ?? 0"), 'Acción de Personal asigna incorrectamente la unidad actual.');
$assert(str_contains($socio, 'busquedaPersonalSocio'), 'El socioeconómico no permite seleccionar servidor.');
$assert(str_contains($socio, 'PERSONAL_SOCIO'), 'El socioeconómico no tiene búsqueda inmediata.');
$assert(str_contains($controlSocio, "'selectorPersonal'"), 'El controlador socioeconómico no entrega el catálogo de personal.');
$assert(str_contains($migracion, 'UX_th_unidades_nombre_activo'), 'No existe protección contra unidades activas duplicadas.');
$assert(str_contains($reconciliacion, 'sp_th_reconciliar_empleado_rolmaes'), 'La conciliación no usa el procedimiento restringido.');
$assert(!str_contains($reconciliacion, 'UPDATE dbo.th_empleados SET'), 'La conciliación intenta UPDATE directo con la cuenta de aplicación.');
$assert(str_contains($ciclo, 'sp_th_cambiar_estado_empleado') && str_contains($ciclo, 'estado_fecha_efectiva'), 'El ciclo laboral no sincroniza estado y fecha efectiva.');
$assert(str_contains($ciclo, "'CESACION DE FUNCIONES','DESTITUCION'") && str_contains($ciclo, "'INGRESO','REINGRESO','RESTITUCION','REINTEGRO'"), 'La aprobación no distingue cesaciones y reingresos.');
$assert(str_contains($menu, 'Prototipos / Próximamente') && str_contains($menu, 'Documentos y Formatos'), 'El menú no refleja la organización funcional aprobada.');
$assert(str_contains($topbar, 'global-search-form'), 'La búsqueda global compartida no está disponible.');

if ($fallos) {
    foreach ($fallos as $fallo) fwrite(STDERR, "[FAIL] {$fallo}\n");
    exit(1);
}

echo "[OK] mejoras operativas de búsqueda y conciliación\n";
