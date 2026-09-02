<?php
declare(strict_types=1);

define('ROOT',dirname(__DIR__));define('BASE_URL','');define('IMG_URL','/public/img');require ROOT.'/core/Config.php';require ROOT.'/core/InstitutionalClock.php';
final class Auth{public static function can(string $module,string $action='visualizar'):bool{return true;}public static function user():array{return ['name'=>'QA APM','role'=>'Administrador'];}public static function csrfToken():string{return 'csrf-qa';}}

$_SERVER['REQUEST_URI']='/talento-humano/vacaciones';
$vacaciones=[['accion_id'=>10,'numero_accion'=>'VAC-001-2026','identificacion'=>'1300000001','apellidos'=>'PRUEBA','nombres'=>'FUNCIONARIO','area'=>'TALENTO HUMANO','cargo'=>'ANALISTA','fecha_inicio'=>'2026-08-25','fecha_fin'=>'2026-09-05','dias_calendario'=>12,'estado_vacacion'=>'VIGENTE']];
$resumen=['total'=>1,'programadas'=>0,'vigentes'=>1,'finalizadas'=>0];$estadoFiltro='VIGENTE';
ob_start();require ROOT.'/modules/talento-humano/Vistas/vacaciones.php';$vacHtml=(string)ob_get_clean();

$_SERVER['REQUEST_URI']='/talento-humano/paz-salvo';$documentos=[['paz_salvo_id'=>1,'numero_documento'=>'PS-2026-0001','apellidos'=>'PRUEBA','nombres'=>'FUNCIONARIO','identificacion'=>'1300000001','cargo'=>'ANALISTA','numero_accion'=>'MP-001-2026','fecha_emision'=>'2026-08-25','estado'=>'BORRADOR']];
ob_start();require ROOT.'/modules/talento-humano/Vistas/paz_salvo.php';$psList=(string)ob_get_clean();

$_SERVER['REQUEST_URI']='/talento-humano/paz-salvo/crear';$documento=null;$acciones=[['accion_id'=>7,'empleado_id'=>1,'fecha_rige_desde'=>'2026-08-25','numero_accion'=>'MP-001-2026','identificacion'=>'1300000001','apellidos'=>'PRUEBA','nombres'=>'FUNCIONARIO']];
ob_start();require ROOT.'/modules/talento-humano/Vistas/paz_salvo_form.php';$psForm=(string)ob_get_clean();

$_SERVER['REQUEST_URI']='/reportes';
$grupos=[['tipo'=>'SUSTANTIVO','color'=>'success','icono'=>'bi-diagram-3','areas'=>[['nombre'=>'TALENTO HUMANO','empleados'=>620,'activos'=>218,'contratos'=>['Nombramiento'=>500,'Contrato'=>120]]]]];
$totales=['empleados'=>620,'activos'=>218];
$estadisticasGenero=[['genero'=>'Femenino','total'=>207,'activos'=>96],['genero'=>'Masculino','total'=>412,'activos'=>121],['genero'=>'No registrado','total'=>1,'activos'=>1]];
$hitosServicio=[];$erroresEstadisticas=[];
ob_start();require ROOT.'/modules/auditoria/Vistas/reportes.php';$reportHtml=(string)ob_get_clean();

$fail=[];$assert=static function(bool $ok,string $message)use(&$fail):void{if(!$ok)$fail[]=$message;};
$assert(str_contains($vacHtml,'VAC-001-2026')&&str_contains($vacHtml,'Registrar Acción de Vacaciones'),'Vacaciones no renderiza serie y acceso de creación.');
$assert(str_contains($vacHtml,'VIGENTE')&&str_contains($vacHtml,'12'),'Vacaciones no muestra vigencia y días.');
$assert(str_contains($vacHtml,'vac-list-toolbar')&&str_contains($vacHtml,'vac-filter-field'),'Vacaciones no conserva la barra compacta de listado y filtros.');
$assert(str_contains($vacHtml,'flex-direction: row')&&str_contains($vacHtml,'Vacaciones registradas'),'El filtro de vacaciones puede heredar nuevamente el formulario vertical global.');
$assert(str_contains($psList,'PS-2026-0001')&&str_contains($psList,'Gestionar'),'Listado Paz y Salvo incompleto.');
$assert(str_contains($psForm,'Acción de salida aprobada')&&str_contains($psForm,'name="fecha_salida"'),'Alta Paz y Salvo incompleta.');
$assert(!str_contains($reportHtml,'id="btn-imprimir"')&&!str_contains($reportHtml,'window.print()'),'Reportes conserva el botón de impresión redundante.');
$assert(str_contains($reportHtml,'Femenino')&&str_contains($reportHtml,'207 · 33.4%')&&str_contains($reportHtml,'96 activos'),'Reportes no renderiza la distribución por género completa.');
$assert(str_contains($reportHtml,'Masculino')&&str_contains($reportHtml,'No registrado'),'Reportes omite categorías de género válidas.');
if($fail){fwrite(STDERR,"TALENT_OPERATION_UI_RENDER_FAIL\n- ".implode("\n- ",$fail)."\n");exit(1);}echo "TALENT_OPERATION_UI_RENDER_OK\n";
