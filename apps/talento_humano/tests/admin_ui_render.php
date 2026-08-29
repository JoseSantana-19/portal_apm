<?php
declare(strict_types=1);
require dirname(__DIR__,2) . '/helpers/polyfills_php74.php';

define('ROOT', dirname(__DIR__));
define('BASE_URL', '');
define('IMG_URL', '/public/img');

final class Auth
{
    public static function can(string $module, string $action='visualizar'): bool { return true; }
    public static function user(): array { return ['name'=>'QA APM','role'=>'Administrador']; }
    public static function csrfToken(): string { return 'csrf-test'; }
}

$_SERVER['REQUEST_URI']='/admin/roles';
$roles=[
    ['id'=>1,'nombre'=>'Super Administrador','estado'=>1,'usuarios'=>1],
    ['id'=>2,'nombre'=>'Analista','estado'=>1,'usuarios'=>4],
    ['id'=>3,'nombre'=>'Consulta','estado'=>0,'usuarios'=>2],
];
$modulos=[
    1=>['id'=>1,'codigo'=>'dashboard','nombre'=>'Dashboard Principal'],
    2=>['id'=>2,'codigo'=>'usuarios','nombre'=>'Administración de Usuarios'],
    3=>['id'=>3,'codigo'=>'auditoria','nombre'=>'Auditoría y Bitácora'],
    4=>['id'=>4,'codigo'=>'maestros','nombre'=>'Maestro de Cargos'],
];
$matriz=[];
foreach($roles as $role){foreach($modulos as $module){$matriz[$role['id']][$module['id']]=[
    'puede_visualizar'=>1,'puede_crear'=>$role['id']===1?1:0,'puede_editar'=>$role['id']===1?1:0,'puede_eliminar'=>$role['id']===1?1:0,
];}}

ob_start();
require ROOT.'/modules/admin/Vistas/roles_reales.php';
$html=(string)ob_get_clean();

if(PHP_SAPI!=='cli' && isset($_GET['preview'])){echo $html;return;}

$failures=[];
$assert=static function(bool $condition,string $message)use(&$failures):void{if(!$condition)$failures[]=$message;};
$assert(substr_count($html,'data-role-card')===3,'Cada rol no se renderiza como tarjeta colapsable.');
$assert(substr_count($html,'data-role-toggle aria-expanded="false"')===3,'Los roles no empiezan cerrados.');
$assert(str_contains($html,'Módulos operativos')&&str_contains($html,'Administración y seguridad')&&str_contains($html,'Control y cumplimiento')&&str_contains($html,'Tablas maestras'),'Falta la agrupación lógica de permisos.');
$assert(substr_count($html,'data-row-toggle')===12,'Falta selección total por fila.');
$assert(str_contains($html,'data-category-toggle')&&str_contains($html,'rbac-check'),'Falta selección por categoría o checkbox institucional.');
$assert(str_contains($html,'rbac-create')&&str_contains($html,'/public/js/rbac_roles.js'),'Alta compacta o interacción RBAC ausente.');

$users=(string)file_get_contents(ROOT.'/modules/admin/Vistas/usuarios_reales.php');
$policies=(string)file_get_contents(ROOT.'/modules/admin/Vistas/politicas_reales.php');
$masters=(string)file_get_contents(ROOT.'/modules/admin/Vistas/maestros.php');
$assert(str_contains($users,'admin-disclosure')&&str_contains($users,'admin-table-scroll'),'Usuarios no fue compactado.');
$assert(str_contains($policies,'admin-disclosure')&&str_contains($policies,'admin-table-scroll'),'Políticas no fue compactado.');
$assert(str_contains($masters,'admin_compact.css')&&str_contains($masters,'admin-page'),'Maestros no utiliza la densidad administrativa mejorada.');

if($failures){foreach($failures as $failure)fwrite(STDERR,"[FAIL] {$failure}\n");exit(1);}
echo "[OK] administración compacta y matriz RBAC colapsable\n";
