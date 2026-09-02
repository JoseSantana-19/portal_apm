<?php
declare(strict_types=1);

$root=dirname(__DIR__);
$fail=[];
$mustContain=[
    'index.php'=>['reportes/exportar-excel','borradores/guardar','DraftController'],
    'core/Config.php'=>['America/Guayaquil'],
    'core/InstitutionalClock.php'=>['nextBirthday','PORTAL_TEST_TODAY'],
    'core/DraftService.php'=>['aes-256-gcm','sp_th_guardar_borrador','sp_th_eliminar_borrador'],
    'modules/talento-humano/Vistas/formulario.php'=>['Sustituto por cuidado de persona con discapacidad','Jornada base contractual','Horas base diarias','data-searchable-select','data-draft-context'],
    'modules/talento-humano/Vistas/accion_personal.php'=>['LACTANCIA','MATERNIDAD','PATERNIDAD','SUSTITUTO','modalidad_vigencia','propuesta_horas_jornada','compact-schedule-current','data-searchable-select'],
    'modules/talento-humano/Vistas/movimiento.php'=>['se conservará','data-draft-context'],
    'modules/talento-humano/Vistas/movimiento_grupal.php'=>['conservará el cargo','data-draft-context'],
    'modules/talento-humano/Vistas/historial.php'=>['history-modal','jornadas_especiales','Ver detalle completo del periodo'],
    'modules/auditoria/Controladores/AuditoriaController.php'=>['exportarExcel','MultiCell','Vigencias laborales','XlsxWriter'],
    'core/XlsxWriter.php'=>['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet','0x04034b50','sheetData'],
    'database/migracion_gestion_laboral_20260820.sql'=>["'BORRADOR'",'th_jornadas_especiales','sp_th_guardar_borrador','sp_th_aprobar_accion_personal_v2','cargo fue conservado'],
    'database/migracion_vigencias_temporales_20260820.sql'=>['th_vigencias_laborales','vw_th_situacion_laboral_efectiva','sp_th_aprobar_accion_personal_v3','APM - Vigencias laborales'],
    'database/migracion_licencias_parentales_20260820.sql'=>['PATERNIDAD','CK_th_jornada_esp_horas','2026.08.20.2'],
];

foreach($mustContain as $relative=>$needles){
    $path=$root.DIRECTORY_SEPARATOR.str_replace('/',DIRECTORY_SEPARATOR,$relative);
    $content=is_file($path)?(string)file_get_contents($path):'';
    if($content===''){$fail[]="Falta {$relative}";continue;}
    foreach($needles as $needle)if(!str_contains($content,$needle))$fail[]="{$relative}: falta {$needle}";
}

$movement=(string)file_get_contents($root.'/modules/talento-humano/Vistas/movimiento.php');
$group=(string)file_get_contents($root.'/modules/talento-humano/Vistas/movimiento_grupal.php');
if(str_contains($movement,'name="puesto_destino_id"'))$fail[]='Movimiento individual aun solicita cargo de destino.';
if(str_contains($group,'name="puesto_destino_id"'))$fail[]='Movimiento grupal aun solicita cargo de destino.';

if($fail){fwrite(STDERR,"WORKFORCE_IMPROVEMENTS_STATIC_FAIL\n- ".implode("\n- ",$fail)."\n");exit(1);}
echo "WORKFORCE_IMPROVEMENTS_STATIC_OK\n";
