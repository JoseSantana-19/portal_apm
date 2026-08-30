<?php

define('ROOT', dirname(__DIR__));
require_once ROOT . '/core/Controller.php';
require_once ROOT . '/modules/talento-humano/Controladores/AccionPersonalController.php';
require_once ROOT . '/modules/talento-humano/Servicios/PdfEstudioSocioeconomico.php';
require_once ROOT . '/modules/talento-humano/Servicios/PdfFormularioPrincipal.php';

$salida = ROOT . '/output/pdf';
if (!is_dir($salida)) {
    mkdir($salida, 0775, true);
}

$accion = [
    'accion_id'=>1,'numero_accion'=>'APM-TH-2026-001','fecha_elaboracion'=>'2026-07-29',
    'identificacion'=>'1312345678','apellidos'=>'MARRASQUIN MALDONADO','nombres'=>'MARIA GABRIELA',
    'tipo_accion'=>'TRASLADO','fecha_rige_desde'=>'2026-08-01','fecha_rige_hasta'=>null,
    'presento_declaracion'=>'NO APLICA','explicacion_legal'=>'Reorganización interna aprobada conforme a las necesidades institucionales y normativa vigente.',
    'actual_proceso'=>'PROCESOS ADJETIVOS','actual_nivel_gestion'=>'DIRECCION','actual_area'=>'DIRECCION ADMINISTRATIVA',
    'actual_lugar_trabajo'=>'MANTA','actual_cargo'=>'ANALISTA DE TALENTO HUMANO','actual_grupo_ocupacional'=>'SERVIDOR PUBLICO 5',
    'actual_grado'=>'11','actual_remuneracion'=>1676.00,'actual_partida_presupuestaria'=>'510105',
    'propuesta_proceso'=>'PROCESOS SUSTANTIVOS','propuesta_nivel_gestion'=>'DIRECCION','propuesta_area'=>'DIRECCION DE OPERACIONES',
    'propuesta_lugar_trabajo'=>'MANTA','propuesta_cargo'=>'ESPECIALISTA PORTUARIO','propuesta_grupo_ocupacional'=>'SERVIDOR PUBLICO 7',
    'propuesta_grado'=>'13','propuesta_remuneracion'=>2034.00,'propuesta_partida_presupuestaria'=>'510105',
    'responsable_th_nombre'=>'ALEXANDER MEDRANDA ALCIVAR','responsable_th_puesto'=>'DIRECTOR DE TALENTO HUMANO',
    'autoridad_nombre'=>'AUTORIDAD NOMINADORA','autoridad_puesto'=>'GERENTE',
    'elaborador_nombre'=>'SYLVIA CANDIDO GILCES','elaborador_puesto'=>'ANALISTA DE RRHH 3',
    'revisor_nombre'=>'ALEXANDER MEDRANDA ALCIVAR','revisor_puesto'=>'DIRECTOR DE TALENTO HUMANO',
    'registrador_nombre'=>'SYLVIA CANDIDO GILCES','registrador_puesto'=>'ANALISTA DE RRHH 3',
    'notificacion_electronica'=>1,'correo_notificacion'=>'servidor@apm.gob.ec','medio_notificacion'=>'CORREO INSTITUCIONAL',
    'documento_notificacion'=>'APM-TH-NOT-001','fecha_notificacion'=>'2026-07-29 10:30:00',
    'notificador_nombre'=>'ALEXANDER MEDRANDA ALCIVAR','notificador_puesto'=>'DIRECTOR DE TALENTO HUMANO'
];

$ref = new ReflectionClass(AccionPersonalController::class);
$controller = $ref->newInstanceWithoutConstructor();
$metodo = $ref->getMethod('generarPdfAccionOficial');
$metodo->invoke($controller,$accion,'F',$salida.'/accion_personal_muestra.pdf');
$metodo->invoke($controller,['_blank'=>true],'F',$salida.'/accion_personal_formato_blanco.pdf');

$principal=[
    'tipo_identificacion'=>'CEDULA','identificacion'=>'1312345678','apellidos'=>'MARRASQUIN MALDONADO','nombres'=>'MARIA GABRIELA',
    'fecha_nacimiento'=>'1992-04-14','sexo'=>'F','estado_civil'=>'Divorciado/a','nacionalidades'=>'Ecuatoriana, Española','tipo_sangre'=>'O+',
    'condicion_especial'=>'Ninguna','cargas_familiares'=>2,'direccion_padre'=>'DIRECCION ADMINISTRATIVA','direccion_area'=>'TALENTO HUMANO',
    'cargo'=>'ANALISTA DE TALENTO HUMANO','codigo_puesto'=>'PST-000120','tipo_contrato'=>'Nombramiento Permanente','fecha_ingreso'=>'2022-06-01',
    'jornada'=>'Completa','sueldo_rmu'=>1676,'codigo_iess'=>'0912345678','estado'=>1,'correo_institucional'=>'servidor@apm.gob.ec',
    'correo_personal'=>'servidor@example.com','telefono_movil'=>'0987654321','telefono_convencional'=>'052600000','ciudad_residencia'=>'MANTA',
    'direccion_domiciliaria'=>'AVENIDA 114 Y CALLE 17','contacto_emergencia'=>'PATRICIA MALDONADO','emergencia_relacion'=>'MADRE',
    'tel_emergencia'=>'0984814336','nivel_estudio'=>'TERCER NIVEL','titulo'=>'INGENIERA EN COMERCIO EXTERIOR','observaciones'=>'EXPEDIENTE VALIDADO POR TALENTO HUMANO.',
    'ruta_foto'=>'public/img/default_avatar.png'
];
(new PdfFormularioPrincipal())->generar($principal,'F',$salida.'/formulario_principal_muestra.pdf');
(new PdfFormularioPrincipal())->generar(['_blank'=>true],'F',$salida.'/formulario_principal_formato_blanco.pdf');

$socio = [
    'fecha_vinculacion'=>'2022-06-01','cargo_cabecera'=>'ANALISTA DE COMERCIALIZACION','nombre_cabecera'=>'MARIA GABRIELA MARRASQUIN MALDONADO',
    'tipo_doc_ident'=>'CEDULA','nro_documento'=>'131224734-7','nacionalidad'=>'ECUATORIANA','anios_residencia'=>'32',
    'libreta_militar'=>'NO APLICA','tipo_relacion'=>'SERVIDOR','apellidos'=>'MARRASQUIN MALDONADO','nombres'=>'MARIA GABRIELA',
    'fecha_nacimiento'=>'1992-04-14','edad'=>'32 AÑOS','lugar_nacimiento'=>'MANTA','provincia_ciudad_nac'=>'MANABI - MANTA',
    'sexo'=>'FEMENINO','tipo_sangre'=>'O+','estado_civil'=>'DIVORCIADO','discapacidad'=>'NO',
    'dir_calle_principal'=>'AVENIDA 114','numero_domicilio'=>'S/N','calle_secundaria'=>'CALLE 17','parroquia'=>'TARQUI',
    'canton'=>'MANTA','provincia_dom'=>'MANABI','referencia_domiciliaria'=>'CERCA DE LA ESCUELA MARIA CEDEÑO DE DELGADO',
    'tel_domicilio'=>'S/N','tel_celular'=>'0984977425','tel_trabajo'=>'(593) 53700345','extension'=>'1206',
    'correo_institucional'=>'mgmarrasquin@apm.gob.ec','correo_alternativo'=>'gabymarr32@hotmail.com',
    'contacto_nombre'=>'PATRICIA ELENA MALDONADO ALARCON','contacto_parentesco'=>'MADRE','contacto_tel_conv'=>'S/N','contacto_tel_cel'=>'0984814336',
    'nro_otorgamiento'=>'9703123','fecha_ingreso_bienes'=>'2024-07-15','banco'=>'BANCO DEL PICHINCHA','tipo_cuenta'=>'AHORRO','nro_cuenta'=>'2205074061',
    'nivel_instruccion'=>'TERCER NIVEL','institucion_educativa'=>'UNIVERSIDAD LAICA ELOY ALFARO DE MANABI','tipo_periodo'=>'SEMESTRAL',
    'area_conocimiento'=>'NEGOCIOS INTERNACIONALES','egresado'=>'SI','titulo_academico'=>'INGENIERA EN COMERCIO EXTERIOR Y NEGOCIOS INTERNACIONALES',
    'hijos'=>[
        ['orden'=>1,'nombres_apellidos'=>'GIANLUCCA PONCE MARRASQUIN','fecha_nacimiento'=>'2011-05-20','tipo_documento'=>'CEDULA','numero_documento'=>'1351701212','edad'=>'13 AÑOS','nivel_instruccion'=>'EN CURSO','ocupacion'=>'ESTUDIANTE'],
        ['orden'=>2,'nombres_apellidos'=>'BIANCA PONCE MARRASQUIN','fecha_nacimiento'=>'2012-07-28','tipo_documento'=>'CEDULA','numero_documento'=>'1317036158','edad'=>'12 AÑOS','nivel_instruccion'=>'EN CURSO','ocupacion'=>'ESTUDIANTE']
    ],
    'capacitaciones'=>[
        ['orden'=>1,'evento'=>'CURSO VIRTUAL: ETICA, INTEGRIDAD Y TRANSPARENCIA EN LA GESTION PUBLICA','tipo_evento'=>'VIRTUAL','auspiciante'=>'P.N.U.D. ECUADOR','tipo_certificado'=>'DIGITAL','certificado_por'=>'SECRETARIA DE POLITICA PUBLICA ANTICORRUPCION','fecha_inicio'=>'2023-05-15'],
        ['orden'=>2,'evento'=>'OPERADOR DEL SISTEMA NACIONAL DE CONTRATACION PUBLICA','tipo_evento'=>'VIRTUAL','auspiciante'=>'SERVICIO NACIONAL DE CONTRATACION PUBLICA','tipo_certificado'=>'DIGITAL','certificado_por'=>'SERVICIO NACIONAL DE CONTRATACION PUBLICA','fecha_inicio'=>'2024-06-22'],
        ['orden'=>3,'evento'=>'REDACCION EMPRESARIAL EFICAZ','tipo_evento'=>'VIRTUAL','auspiciante'=>'UNIVERSIDAD ESPIRITU SANTO','tipo_certificado'=>'DIGITAL','certificado_por'=>'UNIVERSIDAD ESPIRITU SANTO','fecha_inicio'=>'2024-06-24']
    ],
    'experiencias'=>[
        ['orden'=>1,'institucion'=>'FERRETERIA JMD','tipo_institucion'=>'PRIVADA','unidad_administrativa'=>'ATENCION AL CLIENTE','cargo'=>'VENDEDORA','antiguedad'=>'9 AÑOS','jefe_inmediato'=>'JAIME MARRASQUIN','telefono'=>'0999532095','fecha_ingreso'=>'2015-06-01'],
        ['orden'=>2,'institucion'=>'NOTARIA 1','tipo_institucion'=>'PUBLICO-PRIVADA','unidad_administrativa'=>'ATENCION AL CLIENTE','cargo'=>'AUXILIAR DIGITADORA','antiguedad'=>'5 MESES','jefe_inmediato'=>'ABG. CARMEN SEGOVIA','fecha_ingreso'=>'2019-10-16','motivo_ingreso'=>'PERIODO DE MATERNIDAD','fecha_retiro'=>'2020-03-16','motivo_retiro'=>'PANDEMIA']
    ],
    'vivienda_tipo'=>'PROPIA','vehiculo_marca'=>'KIA','vehiculo_modelo'=>'RIO','vehiculo_placa'=>'MAB-1234','vehiculo_valor'=>'14500.00',
    'mapa_url_original'=>'https://www.google.com/maps/search/?api=1&query=-0.967653,-80.708910',
    'latitud'=>'-0.967653','longitud'=>'-80.708910','origen_geolocalizacion'=>'MANUAL',
    'indicaciones_llegada'=>'DESDE LA AVENIDA PRINCIPAL, AVANZAR DOS CUADRAS HASTA EL CENTRO DE SALUD; EL DOMICILIO SE ENCUENTRA EN LA ESQUINA DERECHA.'
];

(new PdfEstudioSocioeconomico())->generar($socio,false,'F',$salida.'/estudio_socioeconomico_muestra.pdf');
(new PdfEstudioSocioeconomico())->generar([],true,'F',$salida.'/estudio_socioeconomico_formato_blanco.pdf');
echo "Muestras generadas en {$salida}\n";
