<?php
declare(strict_types=1);

define('ROOT',dirname(__DIR__));
require ROOT.'/core/Config.php';
require ROOT.'/core/Database.php';

$db=Conexion::conectar();
$fail=[];
$assert=static function(bool $condition,string $message)use(&$fail):void{if(!$condition)$fail[]=$message;};

$ledger=(int)$db->query("SELECT COUNT(*) FROM dbo.th_schema_migrations WHERE version='2026.08.26' AND LEN(checksum_sha256)=64")->fetchColumn();
$assert($ledger===1,'La migración 2026.08.26 no consta con checksum.');
foreach(['th_puesto_rol_mapa'=>'U','sp_th_rol_sugerido_por_empleado'=>'P','sp_th_mapa_roles_puestos'=>'P'] as $object=>$type){
    $stmt=$db->prepare('SELECT OBJECT_ID(:object,:type)');
    $stmt->execute([':object'=>'dbo.'.$object,':type'=>$type]);
    $assert((bool)$stmt->fetchColumn(),'Falta dbo.'.$object.'.');
}
if($fail){fwrite(STDERR,"ROLE_POSITION_DB_SMOKE_FAIL\n- ".implode("\n- ",$fail)."\n");exit(1);}

$map=$db->query('EXEC dbo.sp_th_mapa_roles_puestos')->fetchAll(PDO::FETCH_ASSOC);
$activePosts=(int)$db->query('SELECT COUNT(*) FROM dbo.th_puestos WHERE activo=1')->fetchColumn();
$mappedPosts=count(array_unique(array_map(static fn(array $row):int=>(int)$row['puesto_id'],$map)));
$assert($mappedPosts===$activePosts,"El mapa cubre {$mappedPosts} de {$activePosts} puestos activos.");

$employee=$db->query(
    'SELECT TOP 1 e.empleado_id,e.identificacion
     FROM dbo.th_empleados e
     LEFT JOIN dbo.th_usuarios_sistema u ON u.empleado_id=e.empleado_id
     WHERE e.estado=1 AND e.puesto_id IS NOT NULL AND u.usuario_id IS NULL
     ORDER BY e.empleado_id'
)->fetch(PDO::FETCH_ASSOC);
$assert(is_array($employee),'No existe un funcionario disponible para probar el alta controlada.');
if($fail){fwrite(STDERR,"ROLE_POSITION_DB_SMOKE_FAIL\n- ".implode("\n- ",$fail)."\n");exit(1);}

$suggested=$db->prepare('EXEC dbo.sp_th_rol_sugerido_por_empleado :id');
$suggested->execute([':id'=>$employee['empleado_id']]);
$validRoles=$suggested->fetchAll(PDO::FETCH_ASSOC);
$suggested->closeCursor();
$validRole=(int)($validRoles[0]['rol_id']??0);
$assert($validRole>0,'El funcionario no recibió un rol sugerido.');
$validRoleIds=array_map(static fn(array $row):int=>(int)$row['rol_id'],$validRoles);
$placeholders=implode(',',array_fill(0,count($validRoleIds),'?'));
$invalid=$db->prepare("SELECT TOP 1 rol_id FROM dbo.th_roles WHERE estado=1 AND rol_id NOT IN ({$placeholders}) ORDER BY rol_id");
$invalid->execute($validRoleIds);
$invalidRole=(int)$invalid->fetchColumn();
$assert($invalidRole>0,'No existe un rol alternativo para comprobar el rechazo.');

$suffix=bin2hex(random_bytes(4));
$rejected=false;
try{
    $stmt=$db->prepare('EXEC dbo.sp_th_crear_usuario_sistema :usuario,:hash,:correo,:nombre,:empleado,:rol');
    $stmt->execute([
        ':usuario'=>'qa_role_bad_'.$suffix,':hash'=>password_hash('Temporal!12345',PASSWORD_DEFAULT),
        ':correo'=>'qa_role_bad_'.$suffix.'@apm.test',':nombre'=>'QA rol incompatible',
        ':empleado'=>$employee['empleado_id'],':rol'=>$invalidRole,
    ]);
}catch(PDOException){$rejected=true;}
$assert($rejected,'El procedimiento aceptó un rol incompatible con el puesto.');

$db->beginTransaction();
try{
    $stmt=$db->prepare('EXEC dbo.sp_th_crear_usuario_sistema :usuario,:hash,:correo,:nombre,:empleado,:rol');
    $stmt->execute([
        ':usuario'=>'qa_role_ok_'.$suffix,':hash'=>password_hash('Temporal!12345',PASSWORD_DEFAULT),
        ':correo'=>'qa_role_ok_'.$suffix.'@apm.test',':nombre'=>'QA rol compatible',
        ':empleado'=>$employee['empleado_id'],':rol'=>$validRole,
    ]);
    $created=(int)$stmt->fetchColumn();
    $assert($created>0,'El procedimiento rechazó el rol compatible.');
}catch(Throwable $error){$fail[]='El alta compatible falló: '.$error->getMessage();}
finally{if($db->inTransaction())$db->rollBack();}

if($fail){fwrite(STDERR,"ROLE_POSITION_DB_SMOKE_FAIL\n- ".implode("\n- ",$fail)."\n");exit(1);}
echo "ROLE_POSITION_DB_SMOKE_OK posts={$activePosts} employee={$employee['empleado_id']}\n";
