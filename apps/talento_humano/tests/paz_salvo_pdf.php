<?php
declare(strict_types=1);

define('ROOT', dirname(__DIR__));
require ROOT.'/modules/talento-humano/Servicios/PdfPazSalvo.php';

$output=$argv[1]??sys_get_temp_dir().'/Paz_y_Salvo_APM_QA.pdf';
$documento=[
    'numero_documento'=>'PS-2026-0001','identificacion'=>'1316312766','apellidos'=>'PALMA TEJENA','nombres'=>'MICHAEL JAVIER',
    'cargo'=>'DIRECTOR DE TALENTO HUMANO','area'=>'DIRECCIÓN DE ADMINISTRACIÓN DE TALENTO HUMANO','tipo_contrato'=>'NOMBRAMIENTO',
    'numero_accion'=>'MP-001-2026','fecha_ingreso'=>'2020-01-15','fecha_salida'=>'2026-08-25','fecha_emision'=>'2026-08-25',
    'lugar'=>'Manta','estado'=>'COMPLETO','observaciones_generales'=>'Documento de control generado para validación visual.',
    'secciones'=>[
        'JEFE_INMEDIATO'=>['estado'=>'CONFORME','datos'=>['informe_gestion'=>'Entregado','documentos_entregados'=>'Conforme','receptor_nombre'=>'Ana Responsable','receptor_cargo'=>'Directora','fecha_entrega'=>'2026-08-25'],'responsable_nombre'=>'Ana Responsable','responsable_puesto'=>'Directora','observaciones'=>'Sin novedades.'],
        'TALENTO_HUMANO'=>['estado'=>'CONFORME','datos'=>['credencial_entregada'=>'SI','declaracion_bienes'=>'SI','vacaciones_no_gozadas'=>'Sin saldo'],'responsable_nombre'=>'Talento Humano','responsable_puesto'=>'Analista','observaciones'=>'Conforme.'],
        'FINANCIERO'=>['estado'=>'CONFORME','datos'=>['anticipo_sueldo'=>'NO','valor_saldo'=>'0.00','otros_saldos'=>'Ninguno'],'responsable_nombre'=>'Dirección Financiera','responsable_puesto'=>'Analista','observaciones'=>'Sin obligaciones.'],
        'ADMINISTRATIVO'=>['estado'=>'CONFORME','datos'=>['acta_bienes'=>'Acta entregada'],'responsable_nombre'=>'Dirección Administrativa','responsable_puesto'=>'Guardalmacén','observaciones'=>'Bienes recibidos.'],
        'TIC'=>['estado'=>'CONFORME','datos'=>['correo_deshabilitado'=>'SI','quipux_deshabilitado'=>'SI'],'responsable_nombre'=>'Tecnologías de la Información','responsable_puesto'=>'Administrador','observaciones'=>'Accesos cerrados.'],
    ],
];

(new PdfPazSalvo($documento))->render(false,'F',$output);
