<?php
declare(strict_types=1);

$mode = $argv[1] ?? 'normal';
if (!in_array($mode, ['normal', 'movimiento'], true)) {
    fwrite(STDERR, "Modo de prueba inválido.\n");
    exit(2);
}

define('ROOT', dirname(__DIR__));
define('BASE_URL', '');
define('IMG_URL', '/public/img');

final class Auth
{
    public static function can(string $module, string $action = 'visualizar'): bool { return true; }
    public static function user(): array { return ['name'=>'QA APM','role'=>'Administrador']; }
    public static function csrfToken(): string { return 'csrf-test'; }
}

$_GET = $mode === 'movimiento' ? ['modo'=>'movimiento'] : [];
$_SERVER['REQUEST_URI'] = $mode === 'movimiento'
    ? '/talento-humano/directorio?modo=movimiento'
    : '/talento-humano/directorio';

$base = [
    'cargo'=>'ANALISTA', 'direccion_area'=>'TALENTO HUMANO', 'tipo_contrato'=>'NOMBRAMIENTO',
    'correo_institucional'=>'qa@apm.test', 'ruta_foto'=>'', 'estado_fecha_efectiva'=>'2026-07-29',
    'estado_motivo'=>'Prueba automatizada',
];
$empleados = [
    $base + ['id'=>1,'empleado_id'=>1,'cedula'=>'1300000001','nombres'=>'ANA','apellidos'=>'ACTIVA','estado'=>1],
    $base + ['id'=>2,'empleado_id'=>2,'cedula'=>'1300000002','nombres'=>'INES','apellidos'=>'INACTIVA','estado'=>0],
];

ob_start();
require ROOT . '/modules/talento-humano/Vistas/directorio.php';
$html = (string)ob_get_clean();
$failures = [];
$assert = static function (bool $condition, string $message) use (&$failures): void {
    if (!$condition) $failures[] = $message;
};

if ($mode === 'normal') {
    $assert(!str_contains($html, 'class="empleado-check"'), 'El Directorio normal muestra selección de movimientos.');
    $assert(!str_contains($html, 'row-action-movement-primary'), 'El Directorio normal muestra traslado interno.');
    $assert(str_contains($html, 'row-action-history'), 'El Directorio normal perdió las acciones del expediente.');
    $assert(str_contains($html, 'INES INACTIVA'), 'El Directorio normal no conserva empleados inactivos.');
} else {
    $assert(str_contains($html, 'class="empleado-check"'), 'Movimiento no permite seleccionar funcionarios.');
    $assert(str_contains($html, 'row-action-movement-primary'), 'Movimiento no ofrece traslado individual.');
    $assert(!str_contains($html, 'row-action-history'), 'Movimiento mezcla acciones propias del Directorio.');
    $assert(!str_contains($html, 'INES INACTIVA'), 'Movimiento expone funcionarios inactivos.');
    $assert(str_contains($html, 'id="selectionToolbar"') && str_contains($html, 'id="btnMovimientoGrupal"'), 'Movimiento no contiene selección grupal contextual.');
}

if ($failures) {
    foreach ($failures as $failure) fwrite(STDERR, "[FAIL] {$failure}\n");
    exit(1);
}

echo "[OK] Directorio renderizado en modo {$mode}\n";
