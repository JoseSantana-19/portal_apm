<?php
declare(strict_types=1);

define('ROOT', dirname(__DIR__));
require ROOT.'/core/Config.php';
date_default_timezone_set(Config::timezone());
require ROOT.'/core/InstitutionalClock.php';
require ROOT.'/core/Database.php';

/** @return array<string,mixed> */
function registerTemporaryAction(PDO $db, int $employeeId, int $unitId, string $from, string $to, string $label): array
{
    $statement=$db->prepare(
        "EXEC dbo.sp_th_registrar_accion_personal_v3
          @numero_accion='VISTA-PREVIA',@empleado_id=:employee,@tipo_accion='CAMBIO ADMINISTRATIVO',
          @modalidad_vigencia='TEMPORAL',@fecha_rige_desde=:from_date,@fecha_rige_hasta=:to_date,
          @explicacion_legal=:reason,@propuesta_unidad_id=:unit,@usuario='QA_VIGENCIAS',@ip='127.0.0.1'"
    );
    $statement->execute([
        ':employee'=>$employeeId, ':from_date'=>$from, ':to_date'=>$to,
        ':reason'=>'QA rollback '.$label, ':unit'=>$unitId,
    ]);
    $result=$statement->fetch(PDO::FETCH_ASSOC) ?: [];
    $statement->closeCursor();
    if((int)($result['exito']??0)!==1)throw new RuntimeException('No se registró '.$label.': '.($result['mensaje']??'sin detalle'));
    return $result;
}

$db=Conexion::conectar();
$db->beginTransaction();
try {
    $version=(int)$db->query("SELECT COUNT(*) FROM dbo.th_schema_migrations WHERE version='2026.08.20.2'")->fetchColumn();
    if($version!==1)throw new RuntimeException('La migración de vigencias no consta en el ledger.');

    $employee=$db->query(
        "SELECT TOP 1 e.empleado_id,e.unidad_id,e.puesto_id
         FROM dbo.th_empleados e
         WHERE e.estado=1 AND e.unidad_id IS NOT NULL AND e.puesto_id IS NOT NULL
           AND NOT EXISTS(SELECT 1 FROM dbo.th_vigencias_laborales v WHERE v.empleado_id=e.empleado_id AND v.estado NOT IN('FINALIZADA','CANCELADA','ERROR'))
         ORDER BY e.empleado_id"
    )->fetch(PDO::FETCH_ASSOC);
    if(!$employee)throw new RuntimeException('No existe funcionario apto para probar una vigencia temporal.');
    $parentalEmployees=$db->query(
        "SELECT TOP 2 e.empleado_id
         FROM dbo.th_empleados e
         WHERE e.estado=1 AND e.unidad_id IS NOT NULL AND e.puesto_id IS NOT NULL
           AND NOT EXISTS(SELECT 1 FROM dbo.th_jornadas_especiales j WHERE j.empleado_id=e.empleado_id AND j.estado NOT IN('FINALIZADA','CANCELADA'))
         ORDER BY e.empleado_id"
    )->fetchAll(PDO::FETCH_COLUMN);
    if(count($parentalEmployees)<2)throw new RuntimeException('Se requieren dos funcionarios disponibles para probar ambas licencias parentales.');
    $destination=$db->prepare('SELECT TOP 1 unidad_id FROM dbo.th_unidades_organizacionales WHERE activo=1 AND unidad_id<>:current ORDER BY unidad_id');
    $destination->execute([':current'=>$employee['unidad_id']]);
    $unit=(int)$destination->fetchColumn();
    $destination->closeCursor();
    if($unit<1)throw new RuntimeException('No existe una unidad alternativa para la prueba.');

    $today=InstitutionalClock::today();
    $yesterday=$today->modify('-1 day')->format('Y-m-d');
    $todayIso=$today->format('Y-m-d');

    $expired=registerTemporaryAction($db,(int)$employee['empleado_id'],$unit,$yesterday,$yesterday,'vigencia vencida');
    $approve=$db->prepare('EXEC dbo.sp_th_aprobar_accion_personal_v3 :id,:actor,:ip');
    $approve->execute([':id'=>(int)$expired['accion_id'],':actor'=>'QA_VIGENCIAS',':ip'=>'127.0.0.1']);
    $approved=$approve->fetch(PDO::FETCH_ASSOC) ?: [];
    $approve->closeCursor();
    if((int)($approved['exito']??0)!==1)throw new RuntimeException('No se aprobó la vigencia vencida: '.($approved['mensaje']??'sin detalle'));

    $refresh=$db->query("EXEC dbo.sp_th_refrescar_vigencias_laborales @usuario='QA_VIGENCIAS',@ip='127.0.0.1'");
    $refreshResult=$refresh->fetch(PDO::FETCH_ASSOC) ?: [];
    $refresh->closeCursor();
    if((int)($refreshResult['exito']??0)!==1)throw new RuntimeException('El refresco de vigencias falló.');
    $expiredState=$db->query('SELECT estado FROM dbo.th_vigencias_laborales WHERE accion_id='.(int)$expired['accion_id'])->fetchColumn();
    if($expiredState!=='FINALIZADA')throw new RuntimeException('La vigencia vencida no finalizó automáticamente.');

    $current=registerTemporaryAction($db,(int)$employee['empleado_id'],$unit,$todayIso,$todayIso,'vigencia vigente');
    $approve=$db->prepare('EXEC dbo.sp_th_aprobar_accion_personal_v3 :id,:actor,:ip');
    $approve->execute([':id'=>(int)$current['accion_id'],':actor'=>'QA_VIGENCIAS',':ip'=>'127.0.0.1']);
    $approved=$approve->fetch(PDO::FETCH_ASSOC) ?: [];
    $approve->closeCursor();
    if((int)($approved['exito']??0)!==1)throw new RuntimeException('No se aprobó la vigencia vigente: '.($approved['mensaje']??'sin detalle'));

    $effective=$db->query('SELECT unidad_id,situacion_temporal FROM dbo.vw_th_situacion_laboral_efectiva WHERE empleado_id='.(int)$employee['empleado_id'])->fetch(PDO::FETCH_ASSOC) ?: [];
    $base=$db->query('SELECT unidad_id,puesto_id FROM dbo.th_empleados WHERE empleado_id='.(int)$employee['empleado_id'])->fetch(PDO::FETCH_ASSOC) ?: [];
    if((int)($effective['unidad_id']??0)!==$unit || (int)($effective['situacion_temporal']??0)!==1)throw new RuntimeException('La vista efectiva no aplicó la vigencia temporal.');
    if((int)($base['unidad_id']??0)!==(int)$employee['unidad_id'] || (int)($base['puesto_id']??0)!==(int)$employee['puesto_id']) {
        throw new RuntimeException('La asignación temporal modificó indebidamente la situación base.');
    }

    foreach(['MATERNIDAD','PATERNIDAD'] as $index=>$parentalType){
        $parentalEmployee=(int)$parentalEmployees[$index];
        $parental=$db->prepare(
            "EXEC dbo.sp_th_registrar_accion_personal_v3
              @numero_accion='VISTA-PARENTAL',@empleado_id=:employee,@tipo_accion='LICENCIA',
              @modalidad_vigencia='TEMPORAL',@fecha_rige_desde=:from_date,@fecha_rige_hasta=:to_date,
              @explicacion_legal='QA licencia parental temporal con retorno automatico',
              @propuesta_jornada='Licencia',@propuesta_horas_jornada=0,@tipo_novedad_jornada=:parental_type,
              @usuario='QA_VIGENCIAS',@ip='127.0.0.1'"
        );
        $parental->execute([':employee'=>$parentalEmployee,':from_date'=>$todayIso,':to_date'=>$todayIso,':parental_type'=>$parentalType]);
        $parentalAction=$parental->fetch(PDO::FETCH_ASSOC) ?: [];
        $parental->closeCursor();
        if((int)($parentalAction['exito']??0)!==1)throw new RuntimeException("No se registró {$parentalType}: ".($parentalAction['mensaje']??'sin detalle'));
        $approve=$db->prepare('EXEC dbo.sp_th_aprobar_accion_personal_v3 :id,:actor,:ip');
        $approve->execute([':id'=>(int)$parentalAction['accion_id'],':actor'=>'QA_VIGENCIAS',':ip'=>'127.0.0.1']);
        $approved=$approve->fetch(PDO::FETCH_ASSOC) ?: [];
        $approve->closeCursor();
        if((int)($approved['exito']??0)!==1)throw new RuntimeException("No se aprobó {$parentalType}: ".($approved['mensaje']??'sin detalle'));
        $schedule=$db->query('SELECT jornada_especial_id,tipo_novedad,jornada_temporal,horas_diarias FROM dbo.th_jornadas_especiales WHERE accion_id='.(int)$parentalAction['accion_id'])->fetch(PDO::FETCH_ASSOC) ?: [];
        if(($schedule['tipo_novedad']??'')!==$parentalType || ($schedule['jornada_temporal']??'')!=='Licencia' || abs((float)($schedule['horas_diarias']??-1))>0.001) {
            throw new RuntimeException("{$parentalType} no conservó la licencia de cero horas.");
        }
        $effectiveSchedule=$db->query('SELECT jornada,horas_jornada FROM dbo.vw_th_situacion_laboral_efectiva WHERE empleado_id='.$parentalEmployee)->fetch(PDO::FETCH_ASSOC) ?: [];
        if(($effectiveSchedule['jornada']??'')!=='Licencia' || abs((float)($effectiveSchedule['horas_jornada']??-1))>0.001) {
            throw new RuntimeException("La vista efectiva no aplicó la licencia {$parentalType}.");
        }
    }

    $db->rollBack();
    echo "TEMPORAL_VIGENCY_DB_SMOKE_OK\n";
}catch(Throwable $error){
    if($db->inTransaction())$db->rollBack();
    fwrite(STDERR,'TEMPORAL_VIGENCY_DB_SMOKE_FAIL: '.$error->getMessage().PHP_EOL);
    exit(1);
}
