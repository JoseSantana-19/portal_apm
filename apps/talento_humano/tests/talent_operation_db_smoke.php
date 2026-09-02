<?php
declare(strict_types=1);

define('ROOT',dirname(__DIR__));require ROOT.'/core/Config.php';require ROOT.'/core/Database.php';
$db=Conexion::conectar();$fail=[];
$objects=['th_catalogo_series_accion'=>'U','th_contadores_series_accion'=>'U','th_periodos_vinculacion'=>'U','tr_th_empleados_crear_periodo_inicial'=>'TR','th_paz_salvo'=>'U','th_paz_salvo_secciones'=>'U','vw_th_vacaciones_acciones'=>'V','vw_th_hitos_servicio'=>'V','vw_th_estadisticas_genero'=>'V','sp_th_crear_paz_salvo'=>'P','sp_th_guardar_seccion_paz_salvo'=>'P','sp_th_cerrar_paz_salvo'=>'P'];
foreach($objects as $name=>$type){$s=$db->prepare("SELECT OBJECT_ID(:name,:type)");$s->execute([':name'=>'dbo.'.$name,':type'=>$type]);if(!$s->fetchColumn())$fail[]='Falta '.$name;}
$series=['CAMBIO ADMINISTRATIVO'=>'CA','LICENCIA'=>'LI','SANCIONES'=>'RD','VACACIONES'=>'VAC','REINGRESO'=>'MP'];
foreach($series as $type=>$expected){$s=$db->prepare('SELECT dbo.fn_th_serie_accion(:tipo)');$s->execute([':tipo'=>$type]);if((string)$s->fetchColumn()!==$expected)$fail[]="Serie {$type} incorrecta";}
$periods=(int)$db->query('SELECT COUNT_BIG(*) FROM dbo.th_periodos_vinculacion')->fetchColumn();$employees=(int)$db->query('SELECT COUNT_BIG(*) FROM dbo.th_empleados')->fetchColumn();$missingPeriods=(int)$db->query('SELECT COUNT_BIG(*) FROM dbo.th_empleados e WHERE NOT EXISTS(SELECT 1 FROM dbo.th_periodos_vinculacion p WHERE p.empleado_id=e.empleado_id)')->fetchColumn();if($missingPeriods>0)$fail[]="Existen {$missingPeriods} funcionarios sin período conciliado.";
$db->query('SELECT TOP 1 * FROM dbo.vw_th_vacaciones_acciones')->fetchAll();$db->query('SELECT TOP 1 * FROM dbo.vw_th_hitos_servicio')->fetchAll();
$generos=$db->query('SELECT genero,total,activos FROM dbo.vw_th_estadisticas_genero')->fetchAll(PDO::FETCH_ASSOC);
$totalGenero=array_sum(array_map(static fn(array $r):int=>(int)$r['total'],$generos));
if($totalGenero!==$employees)$fail[]="La distribución por género suma {$totalGenero} de {$employees} funcionarios.";
foreach($generos as $g)if(!in_array((string)$g['genero'],['Masculino','Femenino','No registrado'],true))$fail[]='Categoría de género no reconocida: '.$g['genero'];
$ledger=(int)$db->query("SELECT COUNT(*) FROM dbo.th_schema_migrations WHERE version='2026.08.25.1' AND checksum_sha256 IS NOT NULL")->fetchColumn();if($ledger!==1)$fail[]='Ledger/checksum 2026.08.25.1 ausente.';
if($fail){fwrite(STDERR,"TALENT_OPERATION_DB_SMOKE_FAIL\n- ".implode("\n- ",$fail)."\n");exit(1);}echo "TALENT_OPERATION_DB_SMOKE_OK employees={$employees} periods={$periods} missing={$missingPeriods}\n";
