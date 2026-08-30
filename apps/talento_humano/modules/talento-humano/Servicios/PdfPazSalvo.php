<?php

require_once ROOT . '/libs/fpdf/fpdf.php';

class PdfPazSalvo extends FPDF
{
    private ?array $documento;
    private array $secciones;

    public function __construct(?array $documento)
    {
        parent::__construct('P','mm','A4');$this->documento=$documento;$this->secciones=$documento['secciones']??[];
        $this->SetMargins(12,12,12);$this->SetAutoPageBreak(true,13);
    }

    public function Header(): void
    {
        $logo=ROOT.'/public/img/logoapm.png';if(is_file($logo))$this->Image($logo,14,11,22);
        $this->SetFont('Arial','B',10);$this->SetXY(39,12);$this->Cell(98,9,$this->t('AUTORIDAD PORTUARIA DE MANTA'),1,0,'C');
        $this->SetFont('Arial','B',12);$this->Cell(59,9,$this->t('DOCUMENTO PAZ Y SALVO'),1,1,'C');
        $this->SetX(39);$this->SetFont('Arial','',8);$this->Cell(98,8,$this->t('DIRECCIÓN DE ADMINISTRACIÓN DE TALENTO HUMANO'),1,0,'C');
        $this->Cell(59,8,$this->t('Nro. '.($this->documento['numero_documento']??'________________')),1,1,'C');$this->Ln(4);
    }

    public function Footer(): void{$this->SetY(-10);$this->SetFont('Arial','',7);$this->Cell(0,5,$this->t('Portal Portuario APM · Documento auditable · Página '.$this->PageNo()),0,0,'C');}

    public function render(bool $blanco=false,string $destino='I',?string $rutaSalida=null): void
    {
        $this->AddPage();$d=$blanco?[]:($this->documento??[]);
        $this->section('DATOS DEL FUNCIONARIO Y DESVINCULACIÓN');
        $this->pair('Apellidos y nombres',$this->full($d),'Cédula',$d['identificacion']??'');
        $this->pair('Cargo',$d['cargo']??'','Unidad administrativa',$d['area']??'');
        $this->pair('Tipo de relación',$d['tipo_contrato']??'','Acción de salida',$d['numero_accion']??'');
        $this->pair('Fecha de ingreso',$this->date($d['fecha_ingreso']??null),'Fecha de salida',$this->date($d['fecha_salida']??null));
        $this->pair('Lugar',$d['lugar']??'Manta','Fecha de emisión',$this->date($d['fecha_emision']??null));
        $this->Ln(3);$this->SetFont('Arial','',8);$this->MultiCell(0,5,$this->t('La persona servidora declara haber entregado los bienes, documentos, credenciales e información bajo su responsabilidad. Las áreas certifican su conformidad en las secciones siguientes.'),1,'J');$this->Ln(3);
        $this->block('JEFE INMEDIATO','JEFE_INMEDIATO',['informe_gestion'=>'Informe de gestión/trabajo','documentos_entregados'=>'Documentos, archivos e información','receptor_nombre'=>'Recibe','receptor_cargo'=>'Cargo de quien recibe','fecha_entrega'=>'Fecha de entrega']);
        $this->block('DIRECCIÓN DE ADMINISTRACIÓN DE TALENTO HUMANO','TALENTO_HUMANO',['credencial_entregada'=>'Credencial institucional','declaracion_bienes'=>'Declaración jurada de bienes','vacaciones_no_gozadas'=>'Vacaciones no gozadas']);
        $this->AddPage();
        $this->block('DIRECCIÓN FINANCIERA','FINANCIERO',['anticipo_sueldo'=>'Saldo de anticipo de sueldo','valor_saldo'=>'Valor ($)','otros_saldos'=>'Otros saldos u obligaciones']);
        $this->block('DIRECCIÓN ADMINISTRATIVA','ADMINISTRATIVO',['acta_bienes'=>'Acta de entrega de bienes y custodios']);
        $this->block('TECNOLOGÍAS DE LA INFORMACIÓN','TIC',['correo_deshabilitado'=>'Correo institucional deshabilitado','quipux_deshabilitado'=>'Usuario Quipux deshabilitado']);
        $this->section('CIERRE DEL DOCUMENTO');$this->SetFont('Arial','',8);$this->MultiCell(0,5,$this->t('Estado general: '.($d['estado']??'________________').'   Observaciones: '.($d['observaciones_generales']??'')),1,'L');$this->Ln(12);
        $this->SetFont('Arial','B',8);$this->Cell(86,5,$this->t('FIRMA DEL SERVIDOR'),0,0,'C');$this->Cell(86,5,$this->t('DIRECTOR/A DE TALENTO HUMANO'),0,1,'C');
        $name='Paz_y_Salvo_'.($d['numero_documento']??'Blanco').'.pdf';$this->Output($destino,$rutaSalida??$name);exit;
    }

    private function block(string $title,string $code,array $fields): void
    {
        if($this->GetY()>215)$this->AddPage();$s=$this->secciones[$code]??[];$data=$s['datos']??[];$this->section($title);
        foreach($fields as $key=>$label){$this->SetFont('Arial','B',7.5);$this->Cell(62,6,$this->t($label),1,0);$this->SetFont('Arial','',7.5);$this->Cell(124,6,$this->t($data[$key]??''),1,1);}
        $this->SetFont('Arial','B',7.5);$this->Cell(31,6,$this->t('Resultado'),1,0);$this->SetFont('Arial','',7.5);$this->Cell(31,6,$this->t($s['estado']??''),1,0);$this->SetFont('Arial','B',7.5);$this->Cell(31,6,$this->t('Responsable'),1,0);$this->SetFont('Arial','',7.5);$this->Cell(93,6,$this->t(trim(($s['responsable_nombre']??'').' · '.($s['responsable_puesto']??''),' ·')),1,1);
        $this->SetFont('Arial','B',7.5);$this->Cell(31,12,$this->t('Observaciones'),1,0);$this->SetFont('Arial','',7.5);$x=$this->GetX();$y=$this->GetY();$this->MultiCell(155,6,$this->t($s['observaciones']??''),1,'L');if($this->GetY()<$y+12)$this->SetY($y+12);$this->Ln(2);
    }
    private function section(string $title):void{$this->SetFillColor(222,239,247);$this->SetFont('Arial','B',8.5);$this->Cell(0,7,$this->t($title),1,1,'C',true);}
    private function pair(string $l1,string $v1,string $l2,string $v2):void{$this->SetFont('Arial','B',7.5);$this->Cell(31,6,$this->t($l1),1,0);$this->valueCell(62,6,$v1,0);$this->SetFont('Arial','B',7.5);$this->Cell(31,6,$this->t($l2),1,0);$this->valueCell(62,6,$v2,1);}
    private function valueCell(float $width,float $height,string $value,int $line):void
    {
        $text=$this->t($value);$size=7.5;$this->SetFont('Arial','',$size);
        while($size>4.8&&$this->GetStringWidth($text)>$width-2){$size-=.25;$this->SetFont('Arial','',$size);}
        $this->Cell($width,$height,$text,1,$line);
    }
    private function full(array $d):string{return trim(($d['apellidos']??'').' '.($d['nombres']??''));}
    private function date(?string $v):string{return $v?date('d/m/Y',strtotime($v)):'';}
    private function t($v):string{return mb_convert_encoding((string)$v,'Windows-1252','UTF-8');}
}
