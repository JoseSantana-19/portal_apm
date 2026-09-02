<?php
declare(strict_types=1);

$root=dirname(__DIR__);$fail=[];
$requirements=[
    'database/migracion_operacion_talento_20260825.sql'=>['th_catalogo_series_accion','tr_th_acciones_asignar_serie','th_periodos_vinculacion','vw_th_vacaciones_acciones','vw_th_hitos_servicio','vw_th_estadisticas_genero','sp_th_crear_paz_salvo','2026.08.25.1'],
    'index.php'=>['PazSalvoController','talento-humano/paz-salvo','talento-humano/vacaciones'],
    'shared/menu.php'=>['Vacaciones','Paz y Salvo'],
    'modules/talento-humano/Vistas/accion_personal.php'=>['VACACIONES',"'VACACIONES':'VAC'",'displayNroAccion'],
    'modules/talento-humano/Vistas/inicio.php'=>['hitos-servicio','vacaciones?estado=VIGENTE'],
    'modules/talento-humano/Servicios/PdfPazSalvo.php'=>['DOCUMENTO PAZ Y SALVO','DIRECCIÓN FINANCIERA','TECNOLOGÍAS DE LA INFORMACIÓN'],
    'modules/auditoria/Controladores/AuditoriaController.php'=>["'Periodos vinculacion'","'Vacaciones'","'Hitos de servicio'","'Paz y Salvo'",'vw_th_estadisticas_genero','erroresEstadisticas'],
];
foreach($requirements as $relative=>$needles){$content=@file_get_contents($root.'/'.$relative);if($content===false){$fail[]="Falta {$relative}";continue;}foreach($needles as $needle)if(!str_contains($content,$needle))$fail[]="{$relative}: falta {$needle}";}
if($fail){fwrite(STDERR,"TALENT_OPERATION_STATIC_FAIL\n- ".implode("\n- ",$fail)."\n");exit(1);}echo "TALENT_OPERATION_STATIC_OK\n";
