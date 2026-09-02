<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$failures = [];
$assert = static function (bool $condition, string $message) use (&$failures): void {
    if (!$condition) $failures[] = $message;
};
$source = static fn(string $relative): string => (string)file_get_contents($root.'/'.$relative);

$head = $source('shared/head_assets.php');
$styles = $source('public/css/visual-polish.css');
$directory = $source('modules/talento-humano/Vistas/directorio.php');
$vacations = $source('modules/talento-humano/Vistas/vacaciones.php');
$clearance = $source('modules/talento-humano/Vistas/paz_salvo.php');

$assert(str_contains($head, '/public/css/visual-polish.css'), 'La capa de cierre visual no está cargada globalmente.');
$assert(str_contains($styles, 'select:not([multiple]):not([size])'), 'Los selectores nativos no tienen indicador visual consistente.');
$assert(str_contains($styles, '.topbar-date-chip') && str_contains($styles, 'white-space: nowrap'), 'La fecha del banner puede volver a fragmentarse.');
$assert(str_contains($styles, '.apm-datatable-shell .dt-search input') && str_contains($styles, 'padding: 9px 13px 9px 40px !important'), 'La búsqueda de DataTables puede solapar la lupa y el texto.');
$assert(str_contains($styles, '.directory-page .status-cell') && str_contains($styles, 'position: static'), 'Nómina conserva columnas fijas capaces de superponer Estado y Acciones.');
$assert(str_contains($styles, 'html[data-theme="dark"] select') && str_contains($styles, 'html[data-theme="dark"] .status-inactive'), 'El cierre visual no cubre selects y estados en modo oscuro.');
$assert(str_contains($styles, 'html[data-theme="dark"] .global-search-option-main strong'), 'Los nombres del buscador global no tienen contraste oscuro explícito.');
$assert(str_contains($styles, 'html[data-theme="dark"] .seg-section-body') && str_contains($styles, 'html[data-theme="dark"] .stats-card') && str_contains($styles, 'html[data-theme="dark"] .audit-metric'), 'El tema oscuro no cubre formularios socioeconómicos y reportes.');
$assert(str_contains($directory, 'id="statusFilter"') && str_contains($directory, 'class="status-pill'), 'Nómina perdió su filtro o indicador de estado.');
$assert(str_contains($vacations, 'class="vac-list-toolbar"') && str_contains($vacations, 'vacacionesTable'), 'Vacaciones no conserva su cabecera y tabla organizadas.');
$assert(str_contains($clearance, 'class="ps-list-toolbar"') && str_contains($clearance, 'id="pazSalvoEstado"'), 'Paz y Salvo no usa la cabecera compacta con filtro visible.');

foreach ($failures as $failure) fwrite(STDERR, "[FAIL] {$failure}\n");
echo $failures ? "VISUAL_POLISH_STATIC=FAIL\n" : "VISUAL_POLISH_STATIC=OK\n";
exit($failures ? 1 : 0);
