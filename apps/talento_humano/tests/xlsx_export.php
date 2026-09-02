<?php
declare(strict_types=1);

define('ROOT',dirname(__DIR__));
require ROOT.'/core/XlsxWriter.php';

$output=$argv[1]??sys_get_temp_dir().'/portal_apm_xlsx_test.xlsx';
$directory=dirname($output);
if(!is_dir($directory) && !mkdir($directory,0775,true) && !is_dir($directory))throw new RuntimeException('No se pudo crear el directorio de salida XLSX.');
$book=new XlsxWriter();
$book->addSheet('Funcionarios',[
    ['empleado_id'=>1,'cedula'=>'0926047184','apellidos'=>'ACUÑA','nombres'=>'MARÍA JOSÉ','fecha_ingreso'=>'2026-08-20','sueldo_rmu'=>986.50,'estado'=>1],
    ['empleado_id'=>2,'cedula'=>'0012345678','apellidos'=>'PALMA','nombres'=>'MICHAEL','fecha_ingreso'=>'2025-01-15','sueldo_rmu'=>1200,'estado'=>1],
]);
$book->addSheet('Jornadas especiales',[
    ['accion_id'=>10,'tipo_novedad'=>'LACTANCIA','fecha_desde'=>'2026-08-20','fecha_hasta'=>'2026-11-20','horas_diarias'=>6],
]);
$book->save($output);

if(!is_file($output)||filesize($output)<1000)throw new RuntimeException('No se generó un XLSX válido.');
$signature=file_get_contents($output,false,null,0,4);
if($signature!=="PK\x03\x04")throw new RuntimeException('El archivo no es un contenedor ZIP OOXML.');
echo "XLSX_EXPORT_OK={$output}\n";
