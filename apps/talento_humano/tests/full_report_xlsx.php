<?php
declare(strict_types=1);

define('ROOT', dirname(__DIR__));
require ROOT.'/core/Config.php';
date_default_timezone_set(Config::timezone());
require ROOT.'/core/InstitutionalClock.php';
require ROOT.'/core/Database.php';
require ROOT.'/core/XlsxWriter.php';

$output=$argv[1]??sys_get_temp_dir().'/Reporte_Completo_Funcionarios_APM_QA.xlsx';
$directory=dirname($output);
if(!is_dir($directory) && !mkdir($directory,0775,true) && !is_dir($directory))throw new RuntimeException('No se pudo crear el directorio de salida.');

$db=Conexion::conectar();
$queries=[
    'Funcionarios'=>'SELECT * FROM dbo.vw_th_directorio_empleados ORDER BY apellidos,nombres,empleado_id',
    'Historial laboral'=>'SELECT * FROM dbo.vw_th_reporte_historial_jerarquico ORDER BY empleado_id,fecha_desde',
    'Acciones de personal'=>'SELECT * FROM dbo.th_acciones_personal ORDER BY accion_id',
    'Estudios socioeconomicos'=>'SELECT * FROM dbo.th_estudios_socioeconomicos ORDER BY estudio_id',
    'Jornadas especiales'=>'SELECT * FROM dbo.th_jornadas_especiales ORDER BY empleado_id,fecha_desde',
    'Vigencias laborales'=>'SELECT * FROM dbo.th_vigencias_laborales ORDER BY empleado_id,fecha_desde',
    'Periodos vinculacion'=>'SELECT * FROM dbo.th_periodos_vinculacion ORDER BY empleado_id,fecha_desde',
    'Vacaciones'=>'SELECT * FROM dbo.vw_th_vacaciones_acciones ORDER BY fecha_inicio',
    'Hitos de servicio'=>'SELECT * FROM dbo.vw_th_hitos_servicio ORDER BY fecha_hito',
    'Paz y Salvo'=>'SELECT * FROM dbo.th_paz_salvo ORDER BY paz_salvo_id',
];
$book=new XlsxWriter();
foreach($queries as $sheet=>$query)$book->addSheet($sheet,$db->query($query)->fetchAll(PDO::FETCH_ASSOC));
$book->save($output);
if(!is_file($output)||filesize($output)<5000||file_get_contents($output,false,null,0,4)!=="PK\x03\x04")throw new RuntimeException('El reporte completo no es un XLSX OOXML válido.');
echo 'FULL_REPORT_XLSX_OK='.$output.PHP_EOL;
