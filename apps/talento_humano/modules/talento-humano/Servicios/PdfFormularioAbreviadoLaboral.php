<?php
declare(strict_types=1);

require_once ROOT.'/libs/fpdf/fpdf.php';

/** PDF institucional para personal sujeto al Código del Trabajo.
 *  Conserva los bloques operativos del formato autorizado y excluye las
 *  declaraciones, posesión y aprobaciones exclusivas de LOSEP.
 */
final class PdfFormularioAbreviadoLaboral
{
    public function render(array $d, string $destino='I', ?string $archivo=null): void
    {
        $pdf=new FPDF('P','mm','A4');
        $pdf->SetAutoPageBreak(false);
        $pdf->SetMargins(10,10,10);
        $u=static fn(mixed $v):string=>mb_convert_encoding((string)($v??''),'Windows-1252','UTF-8');
        $fecha=static fn(mixed $v):string=>empty($v)?'':date('d-m-Y',strtotime((string)$v));
        $valor=static fn(array $a,string $k,string $alterno=''):string=>(string)($a[$k]??($alterno!==''?($a[$alterno]??''):'')??'');
        $blank=!empty($d['_blank']);

        $tituloSeccion=function(string $titulo)use($pdf,$u):void{
            $pdf->SetFillColor(225,235,243);$pdf->SetFont('Arial','B',8);
            $pdf->Cell(190,7,$u($titulo),1,1,'C',true);
        };
        $campo=function(float $x,float $y,float $w,string $etiqueta,string $contenido,float $h=11)use($pdf,$u):void{
            $pdf->SetXY($x,$y);$pdf->SetFont('Arial','B',6);$pdf->Cell($w,4,$u($etiqueta),1,2,'C');
            $pdf->SetFont('Arial','',8);$pdf->Cell($w,$h-4,$u($contenido),1,0,'C');
        };
        $linea=function(float $x,float $y,float $w,string $etiqueta,string $contenido)use($pdf,$u):void{
            $pdf->SetXY($x,$y);$pdf->SetFont('Arial','B',6.5);$pdf->Cell(42,6,$u($etiqueta),0,0,'L');
            $pdf->SetFont('Arial','',7);$pdf->Cell($w-42,6,$u($contenido),0,0,'L');
        };

        // Página 1: encabezado, datos, tipificación y situación laboral.
        $pdf->AddPage();
        $logo=ROOT.'/public/img/logoapm.png';
        if(is_file($logo))$pdf->Image($logo,14,13,25);
        $pdf->Rect(10,10,190,32);
        $pdf->Line(48,10,48,42);$pdf->Line(132,10,132,42);
        $pdf->SetXY(50,15);$pdf->SetFont('Arial','B',11);$pdf->MultiCell(80,6,$u("AUTORIDAD\nPORTUARIA DE MANTA"),0,'L');
        $pdf->SetXY(134,13);$pdf->SetFont('Arial','B',13);$pdf->Cell(64,8,$u('FORMULARIO LABORAL'),0,2,'C');
        $pdf->SetFont('Arial','B',8);$pdf->Cell(64,6,$u('CÓDIGO DEL TRABAJO'),0,2,'C');
        $pdf->SetFont('Arial','',7);$pdf->Cell(64,6,$u('Nro. '.($blank?'':$valor($d,'numero_accion'))),1,2,'C');
        $pdf->Cell(64,6,$u('Fecha: '.($blank?'':$fecha($valor($d,'fecha_elaboracion')))),1,0,'C');

        $campo(10,45,95,'APELLIDOS',$blank?'':strtoupper($valor($d,'apellidos')));
        $campo(105,45,95,'NOMBRES',$blank?'':strtoupper($valor($d,'nombres')));
        $campo(10,56,47.5,'DOCUMENTO DE IDENTIFICACIÓN',$blank?'':'CÉDULA');
        $campo(57.5,56,47.5,'NRO. DE IDENTIFICACIÓN',$blank?'':$valor($d,'identificacion'));
        $campo(105,56,47.5,'DESDE (dd-mm-aaaa)',$blank?'':$fecha($valor($d,'fecha_rige_desde')));
        $campo(152.5,56,47.5,'HASTA (cuando aplica)',$blank?'':$fecha($valor($d,'fecha_rige_hasta')));

        $pdf->SetXY(10,70);$tituloSeccion('TIPO DE MOVIMIENTO LABORAL');
        $tipos=['INGRESO','REINGRESO','RESTITUCIÓN','REINTEGRO','ASCENSO','TRASLADO','SANCIONES','TRASPASO','CAMBIO ADMINISTRATIVO','INTERCAMBIO VOLUNTARIO','LICENCIA','COMISIÓN DE SERVICIOS','INCREMENTO RMU','SUBROGACIÓN','ENCARGO','CESACIÓN DE FUNCIONES','DESTITUCIÓN','VACACIONES','REVISIÓN CLASIFICACIÓN PUESTO','OTRO (DETALLAR)'];
        $seleccion=mb_strtoupper($valor($d,'tipo_accion'),'UTF-8');
        $cols=4;$colW=47.5;$rowH=5.2;$y=77;
        foreach($tipos as $i=>$tipo){
            $col=$i%$cols;$row=intdiv($i,$cols);$x=10+$col*$colW;$yy=$y+$row*$rowH;
            $pdf->SetXY($x,$yy);$pdf->SetFont('Arial','',6.5);$pdf->Cell($colW-6,$rowH,$u($tipo),1,0,'L');
            $pdf->SetFont('Arial','B',8);$pdf->Cell(6,$rowH,(!$blank&&$seleccion===mb_strtoupper($tipo,'UTF-8'))?'X':'',1,0,'C');
        }

        // Código del Trabajo no incorpora motivación legal LOSEP ni registro
        // de notificación. La comparativa continúa inmediatamente después del
        // tipo de movimiento para conservar un documento breve de una página.
        $sitY=$y+ceil(count($tipos)/$cols)*$rowH+3;$pdf->SetXY(10,$sitY);$pdf->SetFont('Arial','B',8);
        $pdf->Cell(95,7,$u('SITUACIÓN ACTUAL'),1,0,'C',true);$pdf->Cell(95,7,$u('SITUACIÓN PROPUESTA'),1,1,'C',true);
        $actual=[
            'PROCESO INSTITUCIONAL'=>$valor($d,'actual_proceso'),'NIVEL DE GESTIÓN'=>$valor($d,'actual_nivel_gestion'),
            'UNIDAD ADMINISTRATIVA'=>$valor($d,'actual_area'),'LUGAR DE TRABAJO'=>$valor($d,'actual_lugar_trabajo'),
            'DENOMINACIÓN DEL PUESTO'=>$valor($d,'actual_cargo'),'GRUPO OCUPACIONAL'=>$valor($d,'actual_grupo_ocupacional'),
            'GRADO'=>$valor($d,'actual_grado'),'REMUNERACIÓN MENSUAL'=>'$ '.number_format((float)$valor($d,'actual_remuneracion'),2),
            'PARTIDA INDIVIDUAL'=>$valor($d,'actual_partida_presupuestaria')];
        $propuesta=[
            'PROCESO INSTITUCIONAL'=>$valor($d,'propuesta_proceso'),'NIVEL DE GESTIÓN'=>$valor($d,'propuesta_nivel_gestion'),
            'UNIDAD ADMINISTRATIVA'=>$valor($d,'propuesta_area'),'LUGAR DE TRABAJO'=>$valor($d,'propuesta_lugar_trabajo'),
            'DENOMINACIÓN DEL PUESTO'=>$valor($d,'propuesta_cargo'),'GRUPO OCUPACIONAL'=>$valor($d,'propuesta_grupo_ocupacional'),
            'GRADO'=>$valor($d,'propuesta_grado'),'REMUNERACIÓN MENSUAL'=>'$ '.number_format((float)$valor($d,'propuesta_remuneracion'),2),
            'PARTIDA INDIVIDUAL'=>$valor($d,'propuesta_partida_presupuestaria')];
        $bodyY=$sitY+7;$bodyH=76;$pdf->Rect(10,$bodyY,95,$bodyH);$pdf->Rect(105,$bodyY,95,$bodyH);
        $n=0;foreach($actual as $et=>$contenido){$linea(13,$bodyY+3+$n*7.8,89,$et.':',$blank?'':$contenido);$n++;}
        $n=0;foreach($propuesta as $et=>$contenido){$linea(108,$bodyY+3+$n*7.8,89,$et.':',$blank?'':$contenido);$n++;}
        // Responsabilidades internas del documento abreviado. No incluye
        // revisión ni autoridad nominadora, que pertenecen al formato LOSEP.
        $firmY=$bodyY+$bodyH+10;$pdf->SetXY(10,$firmY-7);$tituloSeccion('RESPONSABLES INTERNOS');
        $firmH=68;$pdf->Rect(10,$firmY,95,$firmH);$pdf->Rect(105,$firmY,95,$firmH);
        $pdf->SetFont('Arial','B',8);$pdf->SetXY(10,$firmY);$pdf->Cell(95,8,$u('RESPONSABLE DE ELABORACIÓN'),1,0,'C');
        $pdf->SetXY(105,$firmY);$pdf->Cell(95,8,$u('RESPONSABLE DE REGISTRO Y CONTROL'),1,0,'C');
        $firmas=[
            [10,$valor($d,'elaborador_nombre'),$valor($d,'elaborador_puesto')],
            [105,$valor($d,'registrador_nombre'),$valor($d,'registrador_puesto')]
        ];
        foreach($firmas as [$x,$nombre,$puesto]){
            $pdf->Line($x+16,$firmY+41,$x+79,$firmY+41);$pdf->SetFont('Arial','B',7);
            $pdf->SetXY($x+5,$firmY+44);$pdf->Cell(18,6,$u('FIRMA:'),0,0);$pdf->Cell(67,6,'',0,1);
            $pdf->SetX($x+5);$pdf->Cell(18,6,$u('NOMBRE:'),0,0);$pdf->SetFont('Arial','',7);$pdf->Cell(67,6,$u($blank?'':$nombre),0,1);
            $pdf->SetX($x+5);$pdf->SetFont('Arial','B',7);$pdf->Cell(18,6,$u('PUESTO:'),0,0);$pdf->SetFont('Arial','',7);$pdf->Cell(67,6,$u($blank?'':$puesto),0,1);
        }

        $pdf->SetXY(10,275);$pdf->SetFont('Arial','I',6);$pdf->Cell(190,6,$u('Formulario abreviado · Código del Trabajo · Autoridad Portuaria de Manta'),0,0,'C');

        $nombre=$archivo?:('Formulario_Abreviado_'.preg_replace('/[^A-Za-z0-9_-]/','_',($valor($d,'numero_accion')?:'CdgT')).'.pdf');
        $pdf->Output($destino,$nombre);
    }
}
