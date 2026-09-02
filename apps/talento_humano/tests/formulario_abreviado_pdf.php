<?php
declare(strict_types=1);

define('ROOT',dirname(__DIR__));
require ROOT.'/modules/talento-humano/Servicios/PdfFormularioAbreviadoLaboral.php';
$target=$argv[1]??(sys_get_temp_dir().'/formulario_abreviado_codigo_trabajo.pdf');
$directory=dirname($target);
if(!is_dir($directory)&&!mkdir($directory,0775,true)&&!is_dir($directory))throw new RuntimeException('No fue posible crear el directorio del PDF.');
$fixture=[
    'numero_accion'=>'CdgT-001-2026','fecha_elaboracion'=>'2026-08-29','fecha_rige_desde'=>'2026-09-01','fecha_rige_hasta'=>null,
    'identificacion'=>'1316312766','apellidos'=>'PALMA TEJENA','nombres'=>'MICHAEL JAVIER','tipo_accion'=>'CAMBIO ADMINISTRATIVO',
    'explicacion_legal'=>'Actualización laboral para personal sujeto al Código del Trabajo.',
    'actual_proceso'=>'Adjetivo de apoyo','actual_nivel_gestion'=>'Operativo','actual_area'=>'Dirección Administrativa',
    'actual_lugar_trabajo'=>'Manta','actual_cargo'=>'Asistente Administrativo','actual_grupo_ocupacional'=>'Servidor 1',
    'actual_grado'=>'3','actual_remuneracion'=>'986.00','actual_partida_presupuestaria'=>'51.01.05',
    'propuesta_proceso'=>'Adjetivo de apoyo','propuesta_nivel_gestion'=>'Operativo','propuesta_area'=>'Dirección Financiera',
    'propuesta_lugar_trabajo'=>'Manta','propuesta_cargo'=>'Asistente de Contabilidad','propuesta_grupo_ocupacional'=>'Servidor 1',
    'propuesta_grado'=>'3','propuesta_remuneracion'=>'986.00','propuesta_partida_presupuestaria'=>'51.01.05',
    'elaborador_nombre'=>'Analista de Talento Humano','elaborador_puesto'=>'Analista','registrador_nombre'=>'Responsable de Registro','registrador_puesto'=>'Técnico de Talento Humano',
    'notificacion_electronica'=>1,'correo_notificacion'=>'talento@apm.gob.ec; archivo@apm.gob.ec','fecha_notificacion'=>'2026-08-29 10:30:00',
    'medio_notificacion'=>'Correo institucional','documento_notificacion'=>'Memorando CdgT-001-2026','notificador_nombre'=>'Asistente de Talento Humano','notificador_puesto'=>'Asistente',
];
(new PdfFormularioAbreviadoLaboral())->render($fixture,'F',$target);
if(!is_file($target)||filesize($target)<1000)throw new RuntimeException('El PDF abreviado no fue generado correctamente.');
echo 'FORMULARIO_ABREVIADO_PDF_OK '.$target.PHP_EOL;
