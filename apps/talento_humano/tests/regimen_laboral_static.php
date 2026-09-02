<?php
declare(strict_types=1);

$root=dirname(__DIR__);
$files=[
    'migration'=>(string)file_get_contents($root.'/database/migracion_regimen_laboral_20260829.sql'),
    'form'=>(string)file_get_contents($root.'/modules/talento-humano/Vistas/formulario.php'),
    'employee'=>(string)file_get_contents($root.'/modules/talento-humano/Controladores/EmpleadoController.php'),
    'action'=>(string)file_get_contents($root.'/modules/talento-humano/Controladores/AccionPersonalController.php'),
    'action_form'=>(string)file_get_contents($root.'/modules/talento-humano/Vistas/accion_personal.php'),
    'service'=>(string)file_get_contents($root.'/modules/talento-humano/Servicios/PdfFormularioAbreviadoLaboral.php'),
];
$fail=[];
$assert=static function(bool $ok,string $message)use(&$fail):void{if(!$ok)$fail[]=$message;};

$assert(str_contains($files['migration'],'regimen_laboral'), 'La migración no incorpora régimen laboral.');
$assert(str_contains($files['migration'],'th_secuencias_documentos'), 'Falta el maestro de secuencias documentales.');
$assert(str_contains($files['migration'],"'CdgT'"), 'Falta el prefijo parametrizado CdgT.');
$assert(str_contains($files['migration'],'sp_getapplock'), 'La secuencia CdgT no está protegida ante concurrencia.');
$assert(str_contains($files['form'],'name="regimen_laboral"'), 'El formulario no permite elegir el régimen laboral.');
$assert(str_contains($files['form'],"CODIGO_TRABAJO: ['Contrato Indefinido']"), 'Código del Trabajo no fuerza Contrato Indefinido.');
$assert(str_contains($files['form'],'No — no se genera Acción de Personal'), 'El formulario no informa correctamente la restricción documental del Código del Trabajo.');
$assert(str_contains($files['employee'],'$regimen===\'CODIGO_TRABAJO\''), 'Falta validación de régimen en backend.');
$assert(str_contains($files['action'],'PdfFormularioAbreviadoLaboral'), 'La impresión no selecciona el formulario abreviado.');
$assert(str_contains($files['action'],'Formulario Abreviado Laboral generado para personal sujeto al Código del Trabajo.') && str_contains($files['action'],'$payload[\'notificacion_electronica\']=0'), 'Código del Trabajo no conserva la referencia técnica requerida o todavía permite notificación LOSEP.');
$assert((bool)preg_match('/id="resumenVigenciaItem".{0,250}>Vigencia<\/span>/s', $files['action_form']), 'El resumen no oculta específicamente la vigencia para Código del Trabajo.');
$assert((bool)preg_match('/<span class="document-summary__label">Código previsto<\/span>/', $files['action_form']), 'El resumen abreviado perdió el código documental previsto.');
$assert(str_contains($files['service'],'RESPONSABLE DE ELABORACIÓN'), 'Falta la firma de elaboración.');
$assert(str_contains($files['service'],'RESPONSABLE DE REGISTRO Y CONTROL'), 'Falta la firma de registro y control.');
$assert(!str_contains($files['service'],'RESPONSABLE DE REVISIÓN'), 'El formulario abreviado no debe incluir Responsable de Revisión.');
$assert(!str_contains($files['service'],'AUTORIDAD NOMINADORA'), 'El formulario abreviado no debe incluir Autoridad Nominadora.');
$assert(!str_contains($files['service'],'MOTIVACIÓN / JUSTIFICACIÓN') && !str_contains($files['service'],'REGISTRO DE NOTIFICACIÓN'), 'El PDF abreviado todavía contiene bloques exclusivos de LOSEP.');

if($fail){fwrite(STDERR,"REGIMEN_LABORAL_STATIC_FAIL\n- ".implode("\n- ",$fail)."\n");exit(1);}
echo "REGIMEN_LABORAL_STATIC_OK\n";
