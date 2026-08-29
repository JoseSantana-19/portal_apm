<?php

require_once ROOT . '/libs/fpdf/fpdf.php';

final class PdfFormularioPrincipal
{
    private FPDF $pdf;
    private array $e;
    private bool $blanco = false;
    private float $x = 14.0;
    private float $w = 182.0;

    public function generar(array $empleado, string $destino='I', ?string $archivo=null): void
    {
        $this->e=$empleado;
        $this->blanco=(bool)($empleado['_blank']??false);
        $this->pdf=new FPDF('P','mm','A4');
        $this->pdf->SetMargins($this->x,12,$this->x);
        $this->pdf->SetAutoPageBreak(false);
        $this->pagina1();
        $this->pagina2();
        $nombre=$archivo ?: 'Formulario_Principal_'.$this->v('identificacion').'_'.date('Ymd').'.pdf';
        $this->pdf->Output($destino,$nombre);
    }

    private function cabecera(int $pagina): void
    {
        $p=$this->pdf;$p->AddPage();$p->SetDrawColor(32,62,89);$p->SetLineWidth(.35);
        $p->Rect($this->x,12,$this->w,25);
        $logo=ROOT.'/public/img/logoapm.png';if(is_file($logo))$p->Image($logo,$this->x+5,14,17,20);
        $p->Line($this->x+28,12,$this->x+28,37);$p->Line($this->x+145,12,$this->x+145,37);
        $p->SetXY($this->x+28,16);$p->SetFont('Arial','B',11);$p->MultiCell(117,5,$this->t("FORMULARIO PRINCIPAL DE REGISTRO\nEXPEDIENTE DEL SERVIDOR"),0,'C');
        $p->SetXY($this->x+147,14);$p->SetFont('Arial','B',6.8);$p->MultiCell(33,4,$this->t("Código: APM-TH-FO-001\nPágina {$pagina} de 2\nImpresión: ".date('d/m/Y')),0,'L');
        $p->SetY(42);
    }

    private function pagina1(): void
    {
        $this->cabecera(1);$p=$this->pdf;
        $this->titulo('1. INFORMACION PERSONAL');
        $foto=$this->v('ruta_foto');$fotoAbs=$foto!==''?ROOT.'/'.ltrim($foto,'/'):'';
        $y=$p->GetY();$p->Rect($this->x,$y,28,34);
        $fotoImpresa=false;
        if($fotoAbs!==''&&is_file($fotoAbs)){
            try{$p->Image($fotoAbs,$this->x+2,$y+2,24,30);$fotoImpresa=true;}catch(Throwable $ignore){}
        }
        if(!$fotoImpresa){$p->SetXY($this->x+2,$y+14);$p->SetFont('Arial','I',6);$p->MultiCell(24,4,$this->t($this->blanco?'FOTO':'FOTO NO REGISTRADA'),0,'C');}
        $p->SetXY($this->x+30,$y);
        $this->filaDoble('TIPO DE IDENTIFICACION',$this->v('tipo_identificacion','CEDULA'),'CEDULA / PASAPORTE',$this->v('identificacion'),$this->x+30,$this->w-30);
        $p->SetX($this->x+30);$this->filaDoble('APELLIDOS',$this->v('apellidos'),'NOMBRES',$this->v('nombres'),$this->x+30,$this->w-30);
        $p->SetX($this->x+30);$this->filaDoble('FECHA DE NACIMIENTO',$this->fecha('fecha_nacimiento'),'GENERO',$this->sexo(),$this->x+30,$this->w-30);
        $p->SetX($this->x+30);$this->filaDoble('ESTADO CIVIL',$this->v('estado_civil'),'TIPO DE SANGRE',$this->v('tipo_sangre'),$this->x+30,$this->w-30);
        $p->SetXY($this->x,$y+36);
        $this->filaCompleta('NACIONALIDAD(ES)',$this->v('nacionalidades',$this->v('nacionalidad')));
        $this->filaDoble('CONDICION ESPECIAL',$this->v('condicion_especial','NINGUNA'),'CARGAS FAMILIARES',$this->v('cargas_familiares','0'));
        $this->filaDoble('TIPO DE DISCAPACIDAD',$this->v('tipo_discapacidad'),'PORCENTAJE',$this->porcentaje());
        $p->Ln(5);

        $this->titulo('2. INFORMACION LABORAL');
        $this->filaCompleta('DIRECCION PADRE',$this->v('direccion_padre'));
        $this->filaCompleta('UNIDAD / AREA',$this->v('direccion_area'));
        $this->filaCompleta('DENOMINACION DEL PUESTO',$this->v('cargo'));
        $this->filaDoble('CODIGO DE PUESTO',$this->v('codigo_puesto'),'TIPO DE CONTRATO',$this->v('tipo_contrato'));
        $this->filaDoble('FECHA DE INGRESO',$this->fecha('fecha_ingreso'),'JORNADA',$this->v('jornada'));
        $this->filaDoble('REMUNERACION MENSUAL',$this->dinero('sueldo_rmu'),'NUMERO IESS',$this->v('codigo_iess',$this->v('num_iess')));
        $estadoLaboral=$this->blanco?'':((int)($this->e['estado']??0)===1?'ACTIVO':'INACTIVO');
        $this->filaDoble('ESTADO LABORAL',$estadoLaboral,'FECHA DE SALIDA',$this->fecha('fecha_salida'));
        $p->Ln(5);

        $this->titulo('3. INFORMACION DE CONTACTO');
        $this->filaDoble('CORREO INSTITUCIONAL',$this->v('correo_institucional'),'CORREO PERSONAL',$this->v('correo_personal'));
        $this->filaDoble('TELEFONO MOVIL',$this->v('telefono_movil'),'TELEFONO CONVENCIONAL',$this->v('telefono_convencional'));
        $this->filaCompleta('CIUDAD DE RESIDENCIA',$this->v('ciudad_residencia'));
        $this->filaCompleta('DIRECCION DOMICILIARIA',$this->v('direccion_domiciliaria'));
        $this->filaDoble('CONTACTO DE EMERGENCIA',$this->v('contacto_emergencia'),'PARENTESCO',$this->v('emergencia_relacion'));
        $this->filaCompleta('TELEFONO DE EMERGENCIA',$this->v('tel_emergencia'));
        $this->pie(1);
    }

    private function pagina2(): void
    {
        $this->cabecera(2);$p=$this->pdf;
        $this->titulo('4. FORMACION ACADEMICA');
        $this->filaCompleta('NIVEL DE ESTUDIOS',$this->v('nivel_estudio'));
        $this->filaCompleta('TITULO REGISTRADO',$this->v('titulo'));
        $p->Ln(6);
        $this->titulo('5. NOTAS Y OBSERVACIONES');
        $p->SetFont('Arial','',8);$p->MultiCell($this->w,6,$this->t($this->v('observaciones','SIN OBSERVACIONES')),1,'L');
        $p->Ln(10);
        $this->titulo('DECLARACION Y RESPONSABILIDAD');
        $p->SetFont('Arial','',8);
        $p->MultiCell($this->w,5,$this->t('El servidor declara que la informacion registrada en este formulario es verdadera y autoriza su verificacion para fines institucionales. El documento se incorpora al expediente de Talento Humano.'),1,'J');
        $p->Ln(18);
        $p->SetFont('Arial','B',8);
        $p->Cell($this->w/2,5,'__________________________________',0,0,'C');$p->Cell($this->w/2,5,'__________________________________',0,1,'C');
        $p->Cell($this->w/2,5,$this->t('FIRMA DEL SERVIDOR'),0,0,'C');$p->Cell($this->w/2,5,$this->t('RESPONSABLE DE TALENTO HUMANO'),0,1,'C');
        $p->SetFont('Arial','',7);
        $p->Cell($this->w/2,4,$this->t($this->v('apellidos').' '.$this->v('nombres')),0,0,'C');$p->Cell($this->w/2,4,$this->t('NOMBRE Y FIRMA'),0,1,'C');
        $p->Cell($this->w/2,4,$this->t('C.I. '.$this->v('identificacion')),0,0,'C');$p->Cell($this->w/2,4,$this->t('FECHA: ____ / ____ / ______'),0,1,'C');
        $this->pie(2);
    }

    private function titulo(string $texto): void
    {
        $this->pdf->SetFillColor(218,232,242);$this->pdf->SetTextColor(13,54,78);$this->pdf->SetFont('Arial','B',9);
        $this->pdf->Cell($this->w,7,$this->t($texto),1,1,'L',true);$this->pdf->SetTextColor(0,0,0);
    }

    private function filaCompleta(string $label,string $valor): void
    {
        $p=$this->pdf;$p->SetFont('Arial','B',6.5);$p->Cell(48,6,$this->t($label),1,0,'L');
        $this->celda($this->w-48,6,$valor,1);
    }

    private function filaDoble(string $l1,string $v1,string $l2,string $v2,?float $x=null,?float $w=null): void
    {
        $p=$this->pdf;if($x!==null)$p->SetX($x);$total=$w??$this->w;$lw=33;$vw=($total-($lw*2))/2;
        $p->SetFont('Arial','B',6.2);$p->Cell($lw,6,$this->t($l1),1,0,'L');$this->celda($vw,6,$v1,0);
        $p->SetFont('Arial','B',6.2);$p->Cell($lw,6,$this->t($l2),1,0,'L');$this->celda($vw,6,$v2,1);
    }

    private function celda(float $w,float $h,string $texto,int $salto): void
    {
        $vacio=$this->blanco?'':'NO REGISTRADO';
        $p=$this->pdf;$texto=$this->t($texto!==''?$texto:$vacio);$size=7;
        do{$p->SetFont('Arial','',$size);$size-=.2;}while($size>5&&$p->GetStringWidth($texto)>$w-2);
        $p->Cell($w,$h,$texto,1,$salto,'L');
    }

    private function pie(int $pagina): void
    {
        $p=$this->pdf;$p->SetXY($this->x,282);$p->SetFont('Arial','I',6);$p->SetTextColor(90,90,90);
        $p->Cell($this->w/2,4,$this->t('Documento generado por Portal Portuario APM'),0,0,'L');
        $p->Cell($this->w/2,4,$this->t("APM-TH-FO-001 / Página {$pagina} de 2"),0,1,'R');$p->SetTextColor(0,0,0);
    }

    private function v(string $campo,string $defecto=''): string
    {
        if($this->blanco){return '';}
        return trim((string)($this->e[$campo]??$defecto));
    }
    private function fecha(string $campo): string{$v=$this->v($campo);return $v!==''&&strtotime($v)?date('d/m/Y',strtotime($v)):'';}
    private function dinero(string $campo): string{$v=$this->v($campo);return $v===''?'':'$ '.number_format((float)$v,2,'.',',');}
    private function porcentaje(): string{$v=$this->v('porcentaje_discapacidad');return $v===''?'':number_format((float)$v,2).'%';}
    private function sexo(): string{$s=strtoupper($this->v('sexo'));return ['M'=>'MASCULINO','F'=>'FEMENINO'][$s]??$this->v('sexo');}
    private function t(string $texto): string{return iconv('UTF-8','windows-1252//TRANSLIT',$texto)?:$texto;}
}
