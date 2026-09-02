<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$migration = (string)file_get_contents($root.'/database/migracion_integridad_periodos_20260830.sql');
$model = (string)file_get_contents($root.'/modules/talento-humano/Modelos/AccionPersonalModel.php');
$fail = [];
$assert = static function (bool $condition, string $message) use (&$fail): void {
    if (!$condition) $fail[] = $message;
};

$assert(str_contains($migration, 'tr_th_empleados_crear_periodo_inicial'), 'Falta el trigger para altas futuras.');
$assert(str_contains($migration, 'AFTER INSERT'), 'El trigger no se ejecuta después del alta.');
$assert(str_contains($migration, 'NOT EXISTS'), 'La conciliación no es idempotente.');
$assert(str_contains($migration, "'2026.08.30.1'"), 'Falta la versión de cierre de períodos.');
$assert(str_contains($migration, 'BEGIN TRAN') && str_contains($migration, 'ROLLBACK'), 'La conciliación no es transaccional.');
$assert(str_contains($migration, 'sp_th_actualizar_borrador_accion_personal'), 'Falta el procedimiento de edición segura.');
$assert(str_contains($migration, 'GRANT EXECUTE ON dbo.sp_th_actualizar_borrador_accion_personal TO portal_app_role'), 'Falta el permiso mínimo de edición.');
$assert(str_contains($model, 'EXEC dbo.sp_th_actualizar_borrador_accion_personal'), 'El modelo no usa el procedimiento de edición segura.');
$assert(!str_contains($model, 'UPDATE dbo.th_acciones_personal SET'), 'El modelo conserva un UPDATE directo incompatible con privilegios mínimos.');

if ($fail) {
    fwrite(STDERR, "PERIODOS_VINCULACION_STATIC_FAIL\n- ".implode("\n- ", $fail)."\n");
    exit(1);
}
echo "PERIODOS_VINCULACION_STATIC_OK\n";
