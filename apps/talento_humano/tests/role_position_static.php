<?php
declare(strict_types=1);

$root=dirname(__DIR__);
$migration=(string)file_get_contents($root.'/database/migracion_roles_por_puesto_20260826.sql');
$model=(string)file_get_contents($root.'/modules/admin/Modelos/AdminModel.php');
$view=(string)file_get_contents($root.'/modules/admin/Vistas/usuarios_reales.php');
$fail=[];
$assert=static function(bool $condition,string $message)use(&$fail):void{if(!$condition)$fail[]=$message;};

foreach([
    'th_puesto_rol_mapa',
    'sp_th_rol_sugerido_por_empleado',
    'sp_th_mapa_roles_puestos',
    "nombre_rol=''Funcionario (Lectura)''",
    '@rol_valido',
    "version = '2026.08.26'",
] as $needle)$assert(str_contains($migration,$needle),'La migración no contiene '.$needle.'.');

$assert(str_contains($model,'EXEC dbo.sp_th_mapa_roles_puestos'),'El administrador no carga el mapa de roles por puesto.');
$assert(str_contains($model,'EXEC dbo.sp_th_crear_usuario_sistema'),'El alta de cuentas no usa el procedimiento controlado.');
$assert(str_contains($view,'ROLES_POR_PUESTO'),'La vista no recibe el mapa de roles.');
$assert(str_contains($view,'aplicarSugerenciaRol'),'La vista no sugiere el rol al seleccionar funcionario.');
$assert(str_contains($view,'se asigna desde la cédula'),'La interfaz no informa el usuario derivado de la cédula.');

if($fail){fwrite(STDERR,"ROLE_POSITION_STATIC_FAIL\n- ".implode("\n- ",$fail)."\n");exit(1);}
echo "ROLE_POSITION_STATIC_OK\n";
