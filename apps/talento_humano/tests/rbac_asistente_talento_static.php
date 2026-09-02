<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$migration = (string)file_get_contents($root.'/database/migracion_rol_asistente_talento_20260827.sql');
$menu = (string)file_get_contents($root.'/shared/menu.php');
$usersView = (string)file_get_contents($root.'/modules/admin/Vistas/usuarios_reales.php');
$model = (string)file_get_contents($root.'/modules/admin/Modelos/AdminModel.php');
$failures = [];
$assert = static function (bool $condition, string $message) use (&$failures): void {
    if (!$condition) $failures[] = $message;
};

$assert(str_contains($migration, "N'Asistente de Talento Humano'"), 'La migración no declara el nuevo rol.');
$assert(str_contains($migration, "'2026.08.27.2'"), 'La migración no registra su versión.');
foreach (['dashboard','directorio','acciones','movimientos','socioeconomico','vacaciones','paz_salvo','biblioteca','maestros','reportes'] as $module) {
    $assert(str_contains($migration, "'{$module}'"), "La matriz no incluye {$module}.");
    $assert(str_contains($menu, "Auth::can('{$module}','visualizar')") || $module === 'dashboard', "El menú no controla {$module} mediante RBAC.");
}
foreach (['usuarios','roles','politicas','auditoria','prototipos'] as $excluded) {
    $assert(!preg_match("/'{$excluded}'\\s*,?\\s*$/m", $migration), "El rol incorpora por error {$excluded}.");
}
$assert(str_contains($migration, "'empleados','documentos_firmados'"), 'Faltan permisos contextuales de expediente firmado.');
$assert(str_contains($migration, 'puede_eliminar = 0'), 'El rol no bloquea explícitamente eliminaciones.');
$assert(str_contains($usersView, 'opt.disabled'), 'La selección de cuenta no deshabilita roles incompatibles.');
$assert(str_contains($usersView, "if (!(bool)\$r['estado']) continue"), 'El alta de cuentas permite seleccionar roles inactivos.');
$assert(str_contains($model, 'EXEC dbo.sp_th_crear_usuario_sistema'), 'El alta no conserva la validación definitiva de SQL Server.');
$assert(str_contains($model, 'EXEC dbo.sp_th_mapa_roles_puestos'), 'La vista no obtiene el mapa institucional de puestos y roles.');

if ($failures) {
    fwrite(STDERR, "RBAC_ASISTENTE_STATIC_FAIL\n- ".implode("\n- ", $failures)."\n");
    exit(1);
}

echo "RBAC_ASISTENTE_STATIC_OK\n";
