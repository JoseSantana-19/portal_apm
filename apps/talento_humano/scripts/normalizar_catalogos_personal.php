<?php
declare(strict_types=1);

define('ROOT',dirname(__DIR__));
require dirname(ROOT, 2).'/helpers/polyfills_php74.php';
require ROOT.'/core/Config.php';
require ROOT.'/core/Database.php';

$apply=in_array('--apply',$argv,true);
$csv=ROOT.'/database/archive/legacy_import_rolmaes/rolmaes.DBF.csv';
if(!is_file($csv)){fwrite(STDERR,"No existe {$csv}\n");exit(1);}

function keyText(?string $value): string {
    $value=trim((string)$value);
    $ascii=iconv('UTF-8','ASCII//TRANSLIT//IGNORE',$value);
    return preg_replace('/[^A-Z0-9]+/',' ',strtoupper($ascii!==false?$ascii:$value))?:'';
}
function contractType(?string $value): string {
    $k=keyText($value);
    if($k===''||$k==='NULL'||$k==='NO ESPECIFICADO')return 'NO ESPECIFICADO';
    if(str_contains($k,'OCAS')||str_contains($k,'OCAC'))return 'CONTRATO OCASIONAL';
    if(str_contains($k,'CODIGO')&&str_contains($k,'TRABAJO')||str_contains($k,'INDEFINIDO'))return 'CÓDIGO DEL TRABAJO';
    if(str_contains($k,'PROVIS'))return 'NOMBRAMIENTO PROVISIONAL';
    if(str_contains($k,'LIBRE')||str_contains($k,'REMOC'))return 'NOMBRAMIENTO DE LIBRE NOMBRAMIENTO Y REMOCIÓN';
    if(str_contains($k,'PERMAN'))return 'NOMBRAMIENTO PERMANENTE';
    if($k==='NOMBRAMIENTO')return 'NOMBRAMIENTO';
    return mb_strtoupper(trim((string)$value),'UTF-8');
}

$handle=fopen($csv,'rb');$headers=fgetcsv($handle,0,';');$source=[];
while(($row=fgetcsv($handle,0,';'))!==false){if(count($row)!==count($headers))continue;$d=array_combine($headers,$row);$id=preg_replace('/\D+/','',(string)($d['NUM_CEDULA']??''));if($id!=='')$source[$id]=$d;}
fclose($handle);

$db=Conexion::conectar();
$employees=$db->query('SELECT empleado_id,identificacion,unidad_id,puesto_id,tipo_contrato,titulo,nivel_estudio,fecha_ingreso,sueldo_rmu FROM dbo.th_empleados')->fetchAll(PDO::FETCH_ASSOC);
$units=$db->query('SELECT unidad_id,nombre_unidad FROM dbo.th_unidades_organizacionales')->fetchAll(PDO::FETCH_ASSOC);
$positions=$db->query('SELECT puesto_id,nombre_puesto FROM dbo.th_puestos')->fetchAll(PDO::FETCH_ASSOC);
$unitMap=[];foreach($units as $u)$unitMap[keyText($u['nombre_unidad'])]=(int)$u['unidad_id'];
$positionMap=[];foreach($positions as $p)$positionMap[keyText($p['nombre_puesto'])]=(int)$p['puesto_id'];

$stats=['empleados'=>count($employees),'fuente'=>count($source),'coinciden'=>0,'sin_fuente'=>0,'unidades_nuevas'=>0,'puestos_nuevos'=>0,'asignaciones'=>0,'contratos'=>0,'historiales'=>0,'titulos'=>0];
if(!$apply){
    foreach($employees as $e){$id=preg_replace('/\D+/','',(string)$e['identificacion']);if(isset($source[$id]))$stats['coinciden']++;else $stats['sin_fuente']++;}
    echo json_encode(['modo'=>'dry-run','estadisticas'=>$stats],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE),PHP_EOL;exit;
}

$db->beginTransaction();
try{
    $insertUnit=$db->prepare("INSERT dbo.th_unidades_organizacionales(codigo_uorg,nombre_unidad,tipo_proceso,unidad_padre_id,activo,fecha_inicio) VALUES(:codigo,:nombre,'IMPORTADO - PENDIENTE CLASIFICAR',NULL,1,CONVERT(date,GETDATE()))");
    $insertPosition=$db->prepare('INSERT dbo.th_puestos(codigo_puesto,nombre_puesto,remuneracion_unificada,activo) VALUES(:codigo,:nombre,:rmu,1)');
    $update=$db->prepare('UPDATE dbo.th_empleados SET unidad_id=COALESCE(unidad_id,:unidad),puesto_id=COALESCE(puesto_id,:puesto),tipo_contrato=:contrato WHERE empleado_id=:id');
    $history=$db->prepare('IF NOT EXISTS(SELECT 1 FROM dbo.th_historial_laboral WHERE empleado_id=:id_buscar) INSERT dbo.th_historial_laboral(empleado_id,puesto_id,unidad_id,fecha_desde,observaciones,usuario_crea,fecha_creacion) VALUES(:id_insertar,:puesto,:unidad,:fecha,\'Registro inicial normalizado desde ROLMAES\',\'MIGRACION\',GETDATE())');
    $title=$db->prepare("IF NOT EXISTS(SELECT 1 FROM dbo.th_titulos WHERE empleado_id=:id_buscar AND nombre_titulo=:titulo_buscar) INSERT dbo.th_titulos(empleado_id,nivel_instruccion,nombre_titulo,institucion_educativa,estado) VALUES(:id_insertar,:nivel,:titulo_insertar,'NO REGISTRADA',1)");
    foreach($employees as $e){
        $identity=preg_replace('/\D+/','',(string)$e['identificacion']);$s=$source[$identity]??null;
        if(!$s){$stats['sin_fuente']++;continue;}$stats['coinciden']++;
        $unitName=trim((string)($s['DIR_AREA']??''));if($unitName==='')$unitName=trim((string)($s['DEPARTAMEN']??''));
        $positionName=trim((string)($s['CARGO']??''));$unitId=$e['unidad_id']?(int)$e['unidad_id']:null;$positionId=$e['puesto_id']?(int)$e['puesto_id']:null;
        if(!$unitId && $unitName!==''){$k=keyText($unitName);if(!isset($unitMap[$k])){$insertUnit->execute([':codigo'=>'IMP-'.substr(hash('sha256',$k),0,10),':nombre'=>mb_strtoupper($unitName,'UTF-8')]);$unitMap[$k]=(int)$db->lastInsertId();$stats['unidades_nuevas']++;}$unitId=$unitMap[$k];}
        if(!$positionId && $positionName!==''){$k=keyText($positionName);if(!isset($positionMap[$k])){$insertPosition->execute([':codigo'=>'IMP-'.substr(hash('sha256',$k),0,10),':nombre'=>mb_strtoupper($positionName,'UTF-8'),':rmu'=>(float)$e['sueldo_rmu']]);$positionMap[$k]=(int)$db->lastInsertId();$stats['puestos_nuevos']++;}$positionId=$positionMap[$k];}
        $normalizedContract=contractType($s['T_CONTRATO']??$e['tipo_contrato']);
        $update->execute([':unidad'=>$unitId,':puesto'=>$positionId,':contrato'=>$normalizedContract,':id'=>$e['empleado_id']]);$stats['asignaciones']+=($unitId&&$positionId)?1:0;$stats['contratos']++;
        if($unitId&&$positionId){$history->execute([':id_buscar'=>$e['empleado_id'],':id_insertar'=>$e['empleado_id'],':puesto'=>$positionId,':unidad'=>$unitId,':fecha'=>$e['fecha_ingreso']?:date('Y-m-d')]);$stats['historiales']+=$history->rowCount()>0?1:0;}
        $legacyTitle=trim((string)($s['TIT_PROFES']??$e['titulo']));if($legacyTitle!==''&&keyText($legacyTitle)!=='NULL'){$tituloNormalizado=mb_strtoupper($legacyTitle,'UTF-8');$title->execute([':id_buscar'=>$e['empleado_id'],':titulo_buscar'=>$tituloNormalizado,':id_insertar'=>$e['empleado_id'],':nivel'=>trim((string)$e['nivel_estudio'])?:'NO REGISTRADO',':titulo_insertar'=>$tituloNormalizado]);$stats['titulos']+=$title->rowCount()>0?1:0;}
    }
    $audit=$db->prepare("EXEC dbo.sp_th_registrar_auditoria 'MIGRACION','Maestros','NORMALIZAR_DATOS',:detalle,'127.0.0.1'");$audit->execute([':detalle'=>substr(json_encode($stats,JSON_UNESCAPED_UNICODE),0,500)]);while($audit->nextRowset()){}
    $db->commit();echo json_encode(['modo'=>'apply','estadisticas'=>$stats],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE),PHP_EOL;
}catch(Throwable $e){if($db->inTransaction())$db->rollBack();fwrite(STDERR,$e->getMessage().PHP_EOL);exit(1);}
