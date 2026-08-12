<?php

require_once ROOT . '/libs/fpdf/fpdf.php';

class PdfEstudioSocioeconomico
{
    private FPDF $pdf;
    private array $d = [];
    private bool $vacio = false;
    private float $x = 20.0;
    private float $w = 170.0;

    public function generar(array $datos = [], bool $vacio = false, string $destino = 'I', ?string $archivo = null): string
    {
        $this->d = $datos;
        $this->vacio = $vacio;
        $this->pdf = new FPDF('P', 'mm', 'A4');
        $this->pdf->SetMargins($this->x, 12, $this->x);
        $this->pdf->SetAutoPageBreak(false);
        $this->pagina1();
        $this->pagina2();
        $this->pagina3();
        $this->pagina4Reservada();
        $nombre = $archivo ?: 'Estudio_Socioeconomico_' . ($this->valor('nro_documento') ?: 'Formato') . '.pdf';
        return $this->pdf->Output($destino, $nombre);
    }

    private function cabecera(int $pagina): void
    {
        $p = $this->pdf;
        $p->AddPage();
        $p->SetDrawColor(45,45,45);
        $p->SetLineWidth(.25);
        $y=15;
        $p->Rect($this->x,$y,$this->w,24);
        $p->Line($this->x+30,$y,$this->x+30,$y+24);
        $p->Line($this->x+140,$y,$this->x+140,$y+24);
        $logo=ROOT . '/public/img/logoapm.png';
        if(is_file($logo)) $p->Image($logo,$this->x+8,$y+2.5,14,18);
        $p->SetXY($this->x+30,$y+5);
        $p->SetFont('Arial','B',10.5);
        $p->MultiCell(110,6,$this->t("FORMATO ESTUDIO DE SEGURIDAD - SOCIO\nECONOMICO"),0,'C');
        $p->SetXY($this->x+142,$y+2);
        $p->SetFont('Arial','B',6.8);
        $p->MultiCell(28,3.2,$this->t("Código:\nAPM-BASC-TH-\nFO-002\nFecha: 01/04/2019\nPágina {$pagina} de 4"),0,'L');
        $p->SetY(45);
    }

    private function pagina1(): void
    {
        $this->cabecera(1);
        $p=$this->pdf;
        $p->SetFont('Arial','',6.8);
        $p->MultiCell($this->w,3.4,$this->t('Autoridad Portuaria de Manta requiere la siguiente información para el estudio de seguridad de la institución. Estos datos serán clasificados como información confidencial y podrán ser presentados ante las autoridades de control cuando así lo requieran.'),0,'J');
        $p->Ln(1.5);
        $p->MultiCell($this->w,3.4,$this->t('La información registrada en el presente formato será confirmada por servidores de la entidad.'),0,'J');
        $p->Ln(2);
        $this->linea('Fecha de Vinculación',$this->fecha('fecha_vinculacion'));
        $this->linea('Cargo',$this->valor('cargo_cabecera'));
        $this->linea('Nombre',$this->valor('nombre_cabecera'));
        $p->Ln(2);
        $this->tituloSeccion('I.    INFORMACION GENERAL');
        $this->tituloTabla('INFORMACION DEL SERVIDOR');
        $this->filaDoble('TIPO DE DOCUMENTO IDENT.','tipo_doc_ident','Nº DOCUMENTO','nro_documento');
        $this->filaDoble('NACIONALIDAD','nacionalidad','AÑOS DE RESIDENCIA','anios_residencia');
        $this->filaDoble('LIBRETA MILITAR (SI - NO)','libreta_militar','Nº LIBRETA MILITAR','nro_libreta_militar');
        $this->filaCompleta('RELACION (SERVIDOR - PASANTE - CONVENIO)','tipo_relacion');
        $this->filaCompleta('APELLIDOS','apellidos');
        $this->filaCompleta('NOMBRES','nombres');
        $this->filaDoble('FECHA DE NACIMIENTO',$this->fecha('fecha_nacimiento'),'EDAD','edad',true);
        $this->filaDoble('LUGAR DE NACIMIENTO','lugar_nacimiento','PROVINCIA - CIUDAD NAC.','provincia_ciudad_nac');
        $this->filaDoble('GENERO','genero','TIPO DE SANGRE','tipo_sangre');
        $this->filaDoble('ESTADO CIVIL','estado_civil','DISCAPACIDAD','discapacidad');
        $this->filaDoble('TIPO DE DISCAPACIDAD','tipo_discapacidad','Nº CARNET CONADIS','nro_carnet_conadis');
        $this->filaDoble('SERVIDOR CARRERA','servidor_carrera','Nº','nro_servidor_carrera');
        $this->filaDoble('AUTO IDENTIFICACION ETNICA','auto_identificacion','NACIONALIDAD INDIGENA','nacionalidad_indigena');
        $this->filaDoble('DIRECCION CALLE PRINCIPAL','dir_calle_principal','NUMERO','numero_domicilio');
        $this->filaDoble('CALLE SECUNDARIA','calle_secundaria','PARROQUIA','parroquia');
        $this->filaDoble('CANTON','canton','PROVINCIA','provincia_dom');
        $this->filaCompleta('REFERENCIA DOMICILIARIA','referencia_domiciliaria');
        $this->filaDoble('TELEFONO DOMICILIO','tel_domicilio','TELEFONO CELULAR','tel_celular');
        $this->filaDoble('TELEFONO TRABAJO','tel_trabajo','NUMERO DE EXTENSION','extension');
        $this->filaCompleta('CORREO ELECTRONICO','correo_institucional');
        $this->filaCompleta('CORREO ELECTRONICO ALTERNATIVO','correo_alternativo');
        $p->Ln(4);
        $this->tituloTabla('DATOS DE CONTACTO');
        $this->filaCompleta('NOMBRES Y APELLIDOS','contacto_nombre');
        $this->filaCompleta('PARENTESCO CON SERVIDOR','contacto_parentesco');
        $this->filaDoble('TELEFONO CONVENCIONAL','contacto_tel_conv','TELEFONO CELULAR','contacto_tel_cel');
        $p->Ln(4);
        $this->tituloTabla('DECLARACION DE BIENES');
        $this->filaDoble('Nº DE OTORGAMIENTO','nro_otorgamiento','FECHA DE INGRESO',$this->fecha('fecha_ingreso_bienes'),false,true);
    }

    private function pagina2(): void
    {
        $this->cabecera(2);
        $this->tituloTabla('INFORMACION BANCARIA');
        $this->filaCompleta('INSTITUCION BANCARIA','banco');
        $this->filaDoble('TIPO DE CUENTA','tipo_cuenta','Nº DE CUENTA','nro_cuenta');
        $this->pdf->Ln(7);
        $this->tituloSeccion('II.    GRUPO FAMILIAR');
        $this->tituloTabla('INFORMACION DEL CONYUGE');
        $this->filaCompleta('NOMBRES Y APELLIDOS','conyuge_nombres');
        $this->filaDoble('TIPO DE DOCUMENTO IDENT.','conyuge_tipo_doc','Nº DOCUMENTO','conyuge_nro_doc');
        $this->filaCompleta('FECHA DE NACIMIENTO',$this->fecha('conyuge_fecha_nac'),true);
        $this->filaCompleta('TIPO DE RELACION','conyuge_tipo_relacion');
        $this->filaCompleta('NIVEL DE INSTRUCCION','conyuge_nivel_instruccion');
        $this->filaCompleta('OCUPACION','conyuge_ocupacion');
        $this->pdf->Ln(7);
        $this->tablaHijos();
        $this->pdf->Ln(7);
        $this->tituloSeccion('III.    INFORMACION ACADEMICA');
        $this->tituloTabla('INSTRUCCION');
        $this->filaCompleta('NIVEL DE INSTRUCCION','nivel_instruccion');
        $this->filaCompleta('INSTITUCION EDUCATIVA','institucion_educativa');
        $this->filaCompleta('TIPO DE PERIODO','tipo_periodo');
        $this->filaCompleta('AREA DE CONOCIMIENTO','area_conocimiento');
        $this->filaCompleta('EGRESADO (SI - NO)','egresado');
        $this->filaCompleta('TITULO','titulo_academico');
        $this->pdf->Ln(7);
        $this->tituloTabla('INFORMACION SOBRE CAPACITACIONES');
        $this->capacitacion(1);
    }

    private function pagina3(): void
    {
        $this->cabecera(3);
        $this->capacitacion(2);
        $this->pdf->Ln(2);
        $this->capacitacion(3);
        $this->pdf->Ln(7);
        $this->tituloSeccion('IV.    EXPERIENCIA LABORAL (3 últimos empleos si los hubiere tenido)');
        $this->tablaExperiencias();
        $this->pdf->Ln(8);
        $this->tablaViviendaVehiculo();
        $this->pdf->Ln(8);
        $this->pdf->SetFont('Arial','B',7);
        $this->pdf->Cell(12,4,'Nota:',0,0,'L');
        $this->pdf->SetFont('Arial','',7);
        $this->pdf->MultiCell($this->w-12,3.8,$this->t('Certifico que la información aquí suministrada es verdadera y podrá ser verificada en cualquier momento por la institución. Así mismo estoy dispuesto a brindar una ampliación de cualquier aspecto de los datos registrados.'),0,'J');
    }

    private function pagina4Reservada(): void
    {
        $this->cabecera(4);
        $p=$this->pdf;
        $p->SetY(62);
        $p->SetFont('Arial','B',9);
        $p->Cell($this->w,6,$this->t('HOJA RESERVADA DEL FORMATO OFICIAL'),0,1,'C');
        $p->SetFont('Arial','',7.5);
        $p->MultiCell($this->w,4,$this->t('Página conservada para completar el documento de cuatro páginas. No contiene campos añadidos ni sustituye la página oficial pendiente de incorporación.'),0,'C');
        $p->SetDrawColor(150,150,150);
        $p->Rect($this->x,82,$this->w,150);
        $p->SetXY($this->x,151);
        $p->SetTextColor(130,130,130);
        $p->SetFont('Arial','I',8);
        $p->Cell($this->w,5,$this->t('Espacio reservado — pendiente de la hoja autorizada'),0,1,'C');
        $p->SetTextColor(0,0,0);
    }

    private function tablaHijos(): void
    {
        $p=$this->pdf;$x=$this->x;$label=44;$cw=($this->w-$label)/3;
        $this->tituloTabla('INFORMACION DE HIJOS');
        $p->SetFont('Arial','B',5.5);$p->Cell($label,5,$this->t('Nº DE HIJOS DE MENOR A MAYOR'),1,0,'L');
        for($i=1;$i<=3;$i++)$p->Cell($cw,5,(string)$i,1,$i===3?1:0,'C');
        $map=[['NOMBRES Y APELLIDOS','nombres_apellidos'],['FECHA DE NACIMIENTO','fecha_nacimiento'],['TIPO DE DOCUMENTO','tipo_documento'],['NUMERO DE DOCUMENTO','numero_documento'],['EDAD','edad'],['NIVEL DE INSTRUCCION','nivel_instruccion'],['OCUPACION','ocupacion']];
        $hijos=$this->d['hijos']??[];
        foreach($map as [$lab,$key]){
            $p->SetX($x);$p->SetFont('Arial','B',5.5);$p->Cell($label,5,$this->t($lab),1,0,'L');
            for($i=0;$i<3;$i++){
                $val=$this->vacio?'':($hijos[$i][$key]??'');if($key==='fecha_nacimiento')$val=$this->fechaValor($val);
                $this->celdaAjustada($cw,5,$val,1,$i===2?1:0,'C',5.5,4.2);
            }
        }
    }

    private function capacitacion(int $n): void
    {
        $caps=$this->d['capacitaciones']??[];$c=[];
        foreach($caps as $fila) if((int)($fila['orden']??0)===$n){$c=$fila;break;}
        $pares=[['EVENTO','evento'],['TIPO DE EVENTO/CAPACIT.','tipo_evento'],['AUSPICIANTE','auspiciante'],['TIPO DE CERTIFICADO','tipo_certificado'],['CERTIFICADO POR','certificado_por'],['FECHA DE INICIO','fecha_inicio']];
        foreach($pares as [$lab,$key]){
            $valor=$this->vacio?'':($c[$key]??'');if($key==='fecha_inicio')$valor=$this->fechaValor($valor);
            $this->filaCompleta($lab,$valor,true);
        }
    }

    private function tablaExperiencias(): void
    {
        $p=$this->pdf;$cols=[18,14,18,14,13,16,12,15,16,15,19];$heads=['NOMBRE DE INSTITUCION','TIPO DE INST.','UNIDAD ADMINIST.','CARGO','ANTIGÜEDAD','JEFE INMEDIATO','TELEF.','FECHA INGRESO','MOTIVO INGRESO','FECHA RETIRO','MOTIVO RETIRO'];
        $x=$this->x;$y=$p->GetY();
        foreach($heads as $i=>$h){$this->celdaTablaMultilinea($x,$y,$cols[$i],11,$h,3.8,2.7);$x+=$cols[$i];}
        $p->SetXY($this->x,$y+11);
        $exp=$this->d['experiencias']??[];
        for($r=0;$r<3;$r++){
            $f=$exp[$r]??[];$vals=[$f['institucion']??'',$f['tipo_institucion']??'',$f['unidad_administrativa']??'',$f['cargo']??'',$f['antiguedad']??'',$f['jefe_inmediato']??'',$f['telefono']??'',$this->fechaValor($f['fecha_ingreso']??''),$f['motivo_ingreso']??'',$this->fechaValor($f['fecha_retiro']??''),$f['motivo_retiro']??''];
            $x=$this->x;$y=$p->GetY();
            foreach($vals as $i=>$v){$this->celdaTablaMultilinea($x,$y,$cols[$i],19,$this->vacio?'':$v,4.2,2.8);$x+=$cols[$i];}
            $p->SetXY($this->x,$y+19);
        }
    }

    private function celdaTablaMultilinea(float $x,float $y,float $w,float $h,$texto,float $max,float $min): void
    {
        $p=$this->pdf;$texto=$this->t(trim((string)$texto));$tam=$max;$lineas=[];$altoLinea=3.0;
        do {
            $p->SetFont('Arial','',$tam);
            $lineas=$this->envolverTexto($texto,$w-1.4);
            $altoLinea=max(2.4,$tam*.72);
            if(count($lineas)*$altoLinea<=$h-1.0)break;
            $tam-=.2;
        } while($tam>=$min);

        $maxLineas=max(1,(int)floor(($h-1.0)/$altoLinea));
        if(count($lineas)>$maxLineas){
            $lineas=array_slice($lineas,0,$maxLineas);
            $lineas[$maxLineas-1]=rtrim($lineas[$maxLineas-1]).'...';
        }
        $p->Rect($x,$y,$w,$h);
        $altoTexto=count($lineas)*$altoLinea;
        $p->SetXY($x+.7,$y+max(.5,($h-$altoTexto)/2));
        $p->SetFont('Arial','',$tam);
        $p->MultiCell($w-1.4,$altoLinea,implode("\n",$lineas),0,'C');
    }

    private function envolverTexto(string $texto,float $ancho): array
    {
        if($texto==='')return [''];
        $palabras=preg_split('/\s+/',trim($texto))?:[];$lineas=[];$linea='';
        foreach($palabras as $palabra){
            $candidata=$linea===''?$palabra:$linea.' '.$palabra;
            if($linea===''||$this->pdf->GetStringWidth($candidata)<=$ancho){$linea=$candidata;continue;}
            $lineas[]=$linea;$linea=$palabra;
        }
        if($linea!=='')$lineas[]=$linea;
        return $lineas?:[''];
    }

    private function tablaViviendaVehiculo(): void
    {
        $p=$this->pdf;$p->SetFont('Arial','B',6);
        $p->Cell(50,5,'VIVIENDA',1,0,'C');$p->Cell(120,5,'VEHICULO',1,1,'C');
        $tipo=strtoupper($this->valor('vivienda_tipo'));
        foreach(['PROPIA','ARRENDADA','OTROS'] as $op)$p->Cell(50/3,5,$this->t($op).' '.($tipo===$op?'X':''),1,0,'C');
        foreach([['MARCA','vehiculo_marca'],['MODELO','vehiculo_modelo'],['PLACA','vehiculo_placa'],['VALOR','vehiculo_valor']] as [$lab,$key])$this->celdaAjustada(30,5,$this->valor($key),1,$key==='vehiculo_valor'?1:0,'C',5,4);
    }

    private function tituloSeccion(string $texto): void { $this->pdf->SetFont('Arial','BU',8);$this->pdf->Cell($this->w,6,$this->t($texto),0,1,'L'); }
    private function tituloTabla(string $texto): void { $this->pdf->SetFillColor(235,235,235);$this->pdf->SetFont('Arial','B',6.5);$this->pdf->Cell($this->w,5,$this->t($texto),1,1,'C',true); }
    private function linea(string $label,string $valor): void { $this->pdf->SetFont('Arial','B',6.6);$this->pdf->Cell(36,3.8,$this->t($label.':'),0,0);$this->pdf->SetFont('Arial','',6.6);$this->pdf->Cell($this->w-36,3.8,$this->t($this->vacio?'':$valor),0,1); }

    private function filaCompleta(string $label,string $campo,bool $literal=false): void
    {
        $valor=$literal?$campo:$this->valor($campo);$lw=54;
        $this->pdf->SetFont('Arial','B',5.7);$this->pdf->Cell($lw,4.8,$this->t($label),1,0,'L');
        $this->celdaAjustada($this->w-$lw,4.8,$valor,1,1,'L',5.7,4.2);
    }

    private function filaDoble(string $l1,string $v1,string $l2,string $v2,bool $literal1=false,bool $literal2=false): void
    {
        $lw=43;$vw=42;$val1=$literal1?($this->vacio?'':$v1):$this->valor($v1);$val2=$literal2?($this->vacio?'':$v2):$this->valor($v2);
        $this->pdf->SetFont('Arial','B',5.4);$this->pdf->Cell($lw,4.8,$this->t($l1),1,0,'L');
        $this->celdaAjustada($vw,4.8,$val1,1,0,'L',5.4,4);
        $this->pdf->SetFont('Arial','B',5.4);$this->pdf->Cell($lw,4.8,$this->t($l2),1,0,'L');
        $this->celdaAjustada($vw,4.8,$val2,1,1,'L',5.4,4);
    }

    private function celdaAjustada(float $w,float $h,$texto,$borde,int $salto,string $alineacion,float $max,float $min): void
    {
        $texto=$this->t((string)$texto);$tam=$max;$this->pdf->SetFont('Arial','',$tam);
        while($tam>$min && $this->pdf->GetStringWidth($texto)>$w-2){$tam-=.2;$this->pdf->SetFont('Arial','',$tam);}
        $this->pdf->Cell($w,$h,$texto,$borde,$salto,$alineacion);
    }

    private function valor(string $campo): string { return $this->vacio?'':trim((string)($this->d[$campo]??'')); }
    private function fecha(string $campo): string { return $this->fechaValor($this->valor($campo)); }
    private function fechaValor($fecha): string { if(!$fecha)return ''; $ts=strtotime((string)$fecha);return $ts?date('d/m/Y',$ts):(string)$fecha; }
    private function t(string $s): string { return iconv('UTF-8','windows-1252//TRANSLIT',$s) ?: $s; }
}
