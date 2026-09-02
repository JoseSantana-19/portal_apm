<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$failures = [];
$assert = static function (bool $condition, string $message) use (&$failures): void {
    if (!$condition) $failures[] = $message;
};
$source = static fn(string $relative): string => (string)file_get_contents($root.'/'.$relative);

foreach ([
    'public/vendor/datatables/dataTables.min.js',
    'public/vendor/datatables/dataTables.dataTables.min.css',
    'public/vendor/datatables/LICENSE',
    'public/js/apm_datatables.js',
    'public/css/apm_datatables.css',
] as $relative) {
    $assert(is_file($root.'/'.$relative) && filesize($root.'/'.$relative) > 0, "Falta el recurso DataTables {$relative}.");
}

$head = $source('shared/head_assets.php');
$footer = $source('shared/footer_scripts.php');
$integration = $source('public/js/apm_datatables.js');
$styles = $source('public/css/apm_datatables.css');
$assert(str_contains($head, '/public/vendor/datatables/dataTables.dataTables.min.css'), 'El CSS local no está centralizado en head_assets.php.');
$vendorPosition = strpos($footer, '/public/vendor/datatables/dataTables.min.js');
$integrationPosition = strpos($footer, '/public/js/apm_datatables.js');
$assert($vendorPosition !== false && $integrationPosition !== false && $vendorPosition < $integrationPosition, 'La biblioteca debe cargarse antes de la integración institucional.');
$assert(str_contains($integration, 'new window.DataTable') && str_contains($integration, "language: language(table)"), 'La inicialización nativa o el idioma institucional están incompletos.');
$assert(str_contains($integration, 'apm:modal-opened') && str_contains($integration, 'columns?.adjust'), 'Falta el reajuste de tablas dentro de modales.');
$assert(str_contains($integration, 'dtSearchControl') && str_contains($integration, "...(lengthChange ? ['pageLength'] : [])"), 'La búsqueda externa o el selector de cantidad al pie no están configurados.');
$assert(str_contains($styles, 'html[data-theme="dark"] .apm-datatable-shell'), 'DataTables no contempla el tema oscuro.');

$views = [
    'modules/talento-humano/Vistas/vacaciones.php' => 'vacacionesTable',
    'modules/talento-humano/Vistas/paz_salvo.php' => 'pazSalvoTable',
    'modules/talento-humano/Vistas/documentos_firmados.php' => 'documentosFirmadosTable',
    'modules/admin/Vistas/usuarios_reales.php' => 'usuariosTable',
    'modules/talento-humano/Vistas/directorio.php' => 'employeeTable',
    'modules/auditoria/Vistas/logs.php' => 'tablaLogs',
    'modules/admin/Vistas/maestros.php' => 'tablaUnidades',
];
foreach ($views as $relative => $tableId) {
    $view = $source($relative);
    $assert(str_contains($view, 'id="'.$tableId.'"') && str_contains($view, 'data-apm-datatable'), "{$relative} no fue integrado con DataTables.");
}

$library = $source('modules/talento-humano/Vistas/biblioteca.php');
foreach (['tabla-expediente', 'tabla-accion', 'tabla-seguridad'] as $tableId) {
    $assert(str_contains($library, 'id="'.$tableId.'" data-apm-datatable'), "La tabla {$tableId} del modal no fue integrada.");
}
$assert(str_contains($library, "new CustomEvent('apm:modal-opened'"), 'La biblioteca no notifica la apertura de sus modales.');

$directory = $source('modules/talento-humano/Vistas/directorio.php');
$assert(!str_contains($directory, 'id="tablePagination"'), 'El directorio conserva la paginación manual duplicada.');
$assert(str_contains($directory, "addEventListener('apm:datatable-ready'"), 'Los filtros del directorio no están conectados con DataTables.');
$assert(str_contains($directory, 'data-dt-searching="true"') && str_contains($directory, 'data-dt-search-control="false"'), 'Nómina necesita el motor de búsqueda activo aunque oculte el buscador interno.');
$assert(str_contains($directory, ".nodes().toArray().map"), 'Nómina intenta recorrer nodos DataTables sin convertir la API a un arreglo.');

$logs = $source('modules/auditoria/Vistas/logs.php');
$assert(str_contains($logs, 'data-dt-paging="false"') && str_contains($logs, 'table-pagination'), 'Auditoría debe conservar la paginación SQL y evitar una segunda paginación cliente.');

foreach ($failures as $failure) fwrite(STDERR, "[FAIL] {$failure}\n");
echo $failures ? "DATATABLES_STATIC=FAIL\n" : "DATATABLES_STATIC=OK\n";
exit($failures ? 1 : 0);
