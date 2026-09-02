<?php
declare(strict_types=1);

define('ROOT', dirname(__DIR__));
require ROOT.'/core/Config.php';
date_default_timezone_set(Config::timezone());
require ROOT.'/core/InstitutionalClock.php';
require ROOT.'/core/Database.php';

$db=Conexion::conectar();
$db->beginTransaction();
$stage='inicio';
try {
    $stage='ledger';
    $version=$db->query("SELECT COUNT(*) FROM dbo.th_schema_migrations WHERE version='2026.08.20'")->fetchColumn();
    if((int)$version!==1)throw new RuntimeException('La migracion laboral no consta en el ledger.');

    $userId=(int)$db->query('SELECT TOP 1 usuario_id FROM dbo.th_usuarios_sistema ORDER BY usuario_id')->fetchColumn();
    if($userId<1)throw new RuntimeException('No existe usuario para probar borradores.');
    $context='qa:rollback:'.bin2hex(random_bytes(5));
    $save=$db->prepare('EXEC dbo.sp_th_guardar_borrador :u,:c,:p,:iv,:tag');
    $save->execute([':u'=>$userId,':c'=>$context,':p'=>'ciphertext-qa',':iv'=>'iv-qa',':tag'=>'tag-qa']);
    while($save->nextRowset()){}
    $load=$db->prepare('EXEC dbo.sp_th_obtener_borrador :u,:c');$load->execute([':u'=>$userId,':c'=>$context]);
    $draft=$load->fetch(PDO::FETCH_ASSOC);$load->closeCursor();
    if(($draft['payload_cifrado']??'')!=='ciphertext-qa')throw new RuntimeException('El repositorio de borradores no respondio.');

    $employee=$db->query("SELECT TOP 1 e.empleado_id,e.unidad_id,e.puesto_id
        FROM dbo.th_empleados e JOIN dbo.th_historial_laboral h ON h.empleado_id=e.empleado_id AND h.fecha_hasta IS NULL
        WHERE e.estado=1 AND e.unidad_id IS NOT NULL AND e.puesto_id IS NOT NULL AND h.fecha_desde<=CONVERT(date,SYSDATETIME())
        ORDER BY e.empleado_id")->fetch(PDO::FETCH_ASSOC);
    if(!$employee)throw new RuntimeException('No existe funcionario apto para la prueba laboral.');
    $destination=$db->prepare('SELECT TOP 1 unidad_id FROM dbo.th_unidades_organizacionales WHERE activo=1 AND unidad_id<>:actual ORDER BY unidad_id');
    $destination->execute([':actual'=>$employee['unidad_id']]);$unit=(int)$destination->fetchColumn();$destination->closeCursor();
    if($unit<1)throw new RuntimeException('No existe area alternativa para la prueba.');

    $today=InstitutionalClock::todayIso();
    $stage='movimiento individual';
    $move=$db->prepare('EXEC dbo.sp_th_mover_empleado :e,:u,:f,:m,:actor,:ip');
    $move->execute([':e'=>$employee['empleado_id'],':u'=>$unit,':f'=>$today,':m'=>'QA rollback: conservar cargo',':actor'=>'QA',':ip'=>'127.0.0.1']);
    $moveResult=$move->fetch(PDO::FETCH_ASSOC);$move->closeCursor();
    if((int)($moveResult['exito']??0)!==1)throw new RuntimeException('Fallo movimiento: '.($moveResult['mensaje']??'sin detalle'));
    $verify=$db->prepare('SELECT unidad_id,puesto_id FROM dbo.th_empleados WHERE empleado_id=:id');$verify->execute([':id'=>$employee['empleado_id']]);$moved=$verify->fetch(PDO::FETCH_ASSOC);
    if((int)$moved['unidad_id']!==$unit||(int)$moved['puesto_id']!==(int)$employee['puesto_id'])throw new RuntimeException('El movimiento no conservo el cargo.');

    $historyBefore=(int)$db->query('SELECT COUNT(*) FROM dbo.th_historial_laboral WHERE empleado_id='.(int)$employee['empleado_id'])->fetchColumn();
    $stage='registro acción v3';
    $register=$db->prepare("EXEC dbo.sp_th_registrar_accion_personal_v3
        @numero_accion='VISTA-PREVIA',@empleado_id=:e,@tipo_accion='CAMBIO ADMINISTRATIVO',
        @modalidad_vigencia='TEMPORAL',
        @fecha_rige_desde=:desde,@fecha_rige_hasta=:hasta,@explicacion_legal='QA jornada temporal con rollback',
        @actual_jornada='Completa',@actual_horas_jornada=8,@propuesta_jornada='Especial',@propuesta_horas_jornada=6,
        @tipo_novedad_jornada='LACTANCIA',@usuario='QA',@ip='127.0.0.1'");
    $register->execute([':e'=>$employee['empleado_id'],':desde'=>$today,':hasta'=>InstitutionalClock::today()->modify('+30 days')->format('Y-m-d')]);
    $action=$register->fetch(PDO::FETCH_ASSOC);$register->closeCursor();
    if((int)($action['exito']??0)!==1)throw new RuntimeException('Fallo registro de accion: '.($action['mensaje']??'sin detalle'));
    $actionId=(int)$action['accion_id'];
    $assignedNumber=(string)$db->query('SELECT numero_accion FROM dbo.th_acciones_personal WHERE accion_id='.$actionId)->fetchColumn();
    if(!preg_match('/^CA-\\d{3,}-\\d{4}$/',$assignedNumber))throw new RuntimeException('La serie CA no se asignó correctamente: '.$assignedNumber);
    $state=$db->query('SELECT estado_documento FROM dbo.th_acciones_personal WHERE accion_id='.$actionId)->fetchColumn();
    if(strtoupper((string)$state)!=='BORRADOR')throw new RuntimeException('La accion no inicio como BORRADOR.');
    $stage='aprobación acción v3';
    $approve=$db->prepare('EXEC dbo.sp_th_aprobar_accion_personal_v3 :id,:actor,:ip');
    $approve->execute([':id'=>$actionId,':actor'=>'QA',':ip'=>'127.0.0.1']);$approved=$approve->fetch(PDO::FETCH_ASSOC);$approve->closeCursor();
    if((int)($approved['exito']??0)!==1)throw new RuntimeException('Fallo aprobacion: '.($approved['mensaje']??'sin detalle'));
    $schedule=(int)$db->query('SELECT COUNT(*) FROM dbo.th_jornadas_especiales WHERE accion_id='.$actionId)->fetchColumn();
    $historyAfter=(int)$db->query('SELECT COUNT(*) FROM dbo.th_historial_laboral WHERE empleado_id='.(int)$employee['empleado_id'])->fetchColumn();
    if($schedule!==1||$historyAfter!==$historyBefore)throw new RuntimeException('La jornada temporal no respeto el historial jerarquico.');

    $db->rollBack();
    echo "WORKFORCE_DB_SMOKE_OK\n";
}catch(Throwable $e){
    if($db->inTransaction())$db->rollBack();
    fwrite(STDERR,'WORKFORCE_DB_SMOKE_FAIL ['.$stage.']: '.$e->getMessage().PHP_EOL);
    exit(1);
}
