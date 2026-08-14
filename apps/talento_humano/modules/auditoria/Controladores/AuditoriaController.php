<?php
// modules/auditoria/Controladores/AuditoriaController.php
// Módulo: Auditoría y Control – Reportes generales y Logs de actividad

class AuditoriaController extends Controller
{
    /** GET /reportes – Reportes generales jerárquicos con exportación */
    public function reportes(): void
    {
        [$grupos,$totales]=$this->datosOrganizacionales();
        $this->auditar('CONSULTAR_REPORTES','Consulta del reporte organizacional real.');
        $this->cargarVista('auditoria','reportes',['grupos'=>$grupos,'totales'=>$totales]);
    }

    public function exportarCsv(): void
    {
        [$grupos]=$this->datosOrganizacionales();$this->auditar('EXPORTAR_CSV','Exporto el reporte organizacional.');
        header('Content-Type: text/csv; charset=UTF-8');header('Content-Disposition: attachment; filename="Reporte_Organizacional_APM_'.date('Ymd_His').'.csv"');
        $o=fopen('php://output','w');fprintf($o,"\xEF\xBB\xBF");fputcsv($o,['Proceso','Área','Total','Activos','Nombramientos','Contratos'],';');
        foreach($grupos as $g)foreach($g['areas'] as $a)fputcsv($o,[$g['tipo'],$a['nombre'],$a['empleados'],$a['activos'],$a['contratos']['Nombramiento']??0,$a['contratos']['Contrato']??0],';');
        fclose($o);exit;
    }

    public function exportarPdf(): void
    {
        [$grupos,$totales]=$this->datosOrganizacionales();$this->auditar('EXPORTAR_PDF','Exporto el reporte organizacional.');
        require_once ROOT.'/libs/fpdf/fpdf.php';$pdf=new FPDF();$pdf->AddPage();$pdf->SetFont('Arial','B',14);$pdf->Cell(0,9,utf8_decode('AUTORIDAD PORTUARIA DE MANTA'),0,1,'C');$pdf->SetFont('Arial','',10);$pdf->Cell(0,7,utf8_decode('Reporte organizacional de Talento Humano - '.date('d/m/Y H:i')),0,1,'C');$pdf->Ln(4);
        foreach($grupos as $g){$pdf->SetFont('Arial','B',10);$pdf->SetFillColor(220,238,245);$pdf->Cell(0,7,utf8_decode($g['tipo']),1,1,'L',true);foreach($g['areas'] as $a){$pdf->SetFont('Arial','',8);$pdf->Cell(115,6,utf8_decode($a['nombre']),1);$pdf->Cell(25,6,'Total: '.$a['empleados'],1);$pdf->Cell(25,6,'Activos: '.$a['activos'],1,1);}}
        $pdf->SetFont('Arial','B',9);$pdf->Cell(0,8,'TOTAL: '.$totales['empleados'].' / ACTIVOS: '.$totales['activos'],1,1);$pdf->Output('I','Reporte_Organizacional_APM.pdf');exit;
    }

    private function datosOrganizacionales(): array
    {
        $db=Conexion::conectar();$sql="SELECT COALESCE(NULLIF(u.tipo_proceso,''),'SIN ASIGNAR') proceso,
            COALESCE(p.nombre_unidad,u.nombre_unidad,'SIN ASIGNACION ORGANIZACIONAL') area,
            COALESCE(e.tipo_contrato,'NO ESPECIFICADO') contrato,COUNT_BIG(*) empleados,
            SUM(CASE WHEN e.estado=1 THEN 1 ELSE 0 END) activos
            FROM dbo.th_empleados e LEFT JOIN dbo.th_unidades_organizacionales u ON u.unidad_id=e.unidad_id
            LEFT JOIN dbo.th_unidades_organizacionales p ON p.unidad_id=u.unidad_padre_id
            GROUP BY COALESCE(NULLIF(u.tipo_proceso,''),'SIN ASIGNAR'),COALESCE(p.nombre_unidad,u.nombre_unidad,'SIN ASIGNACION ORGANIZACIONAL'),COALESCE(e.tipo_contrato,'NO ESPECIFICADO')
            ORDER BY proceso,area";
        $map=[];foreach($db->query($sql)->fetchAll(PDO::FETCH_ASSOC) as $r){$tipo=$r['proceso'];$area=$r['area'];if(!isset($map[$tipo][$area]))$map[$tipo][$area]=['nombre'=>$area,'empleados'=>0,'activos'=>0,'contratos'=>['Nombramiento'=>0,'Contrato'=>0]];$map[$tipo][$area]['empleados']+=(int)$r['empleados'];$map[$tipo][$area]['activos']+=(int)$r['activos'];$clase=str_contains(strtoupper((string)$r['contrato']),'NOMBRAMIENTO')?'Nombramiento':'Contrato';$map[$tipo][$area]['contratos'][$clase]+=(int)$r['empleados'];}
        $colores=['GOBERNANTE'=>'primary','SUSTANTIVO'=>'success','ADJETIVO'=>'info'];$grupos=[];foreach($map as $tipo=>$areas){$color='warning';foreach($colores as $k=>$v)if(str_contains(strtoupper($tipo),$k))$color=$v;$grupos[]=['tipo'=>$tipo,'color'=>$color,'icono'=>'bi-diagram-3','areas'=>array_values($areas)];}
        $totales=['empleados'=>array_sum(array_map(fn($g)=>array_sum(array_column($g['areas'],'empleados')),$grupos)),'activos'=>array_sum(array_map(fn($g)=>array_sum(array_column($g['areas'],'activos')),$grupos))];return[$grupos,$totales];
    }

    private function auditar(string $accion,string $detalle): void
    {
        $s=Conexion::conectar()->prepare('EXEC dbo.sp_th_registrar_auditoria :usuario,:modulo,:accion,:detalle,:ip');$s->execute([':usuario'=>Auth::username(),':modulo'=>'Reportes',':accion'=>$accion,':detalle'=>$detalle,':ip'=>Auth::clientIp()]);while($s->nextRowset()){}
    }

    /** GET /auditoria/logs – Logs de actividad del sistema (solo lectura, Admin) */
    public function logs(): void
    {
        $db = Conexion::conectar();
        $audit = $db->prepare('EXEC dbo.sp_th_registrar_auditoria :usuario,:modulo,:accion,:descripcion,:ip');
        $audit->execute([
            ':usuario' => Auth::username(), ':modulo' => 'Auditoria',
            ':accion' => 'CONSULTAR_LOGS', ':descripcion' => 'Consulta de la bitacora de auditoria.',
            ':ip' => Auth::clientIp(),
        ]);
        $audit->closeCursor();

        $pagina=max(1,(int)($_GET['pagina']??1));$tamano=50;
        $termino=trim((string)($_GET['q']??''));$modulo=trim((string)($_GET['modulo']??''));
        $desde=trim((string)($_GET['desde']??''));$hasta=trim((string)($_GET['hasta']??''));
        $where=[];$params=[];
        if($termino!==''){
            $where[]='(usuario LIKE :q_usuario OR accion LIKE :q_accion OR descripcion_detalle LIKE :q_detalle OR direccion_ip LIKE :q_ip)';
            $busqueda='%'.$termino.'%';
            $params[':q_usuario']=$busqueda;$params[':q_accion']=$busqueda;
            $params[':q_detalle']=$busqueda;$params[':q_ip']=$busqueda;
        }
        if($modulo!==''){$where[]='modulo=:modulo';$params[':modulo']=$modulo;}
        if($desde!==''){$where[]='fecha_hora>=CONVERT(date,:desde)';$params[':desde']=$desde;}
        if($hasta!==''){$where[]='fecha_hora<DATEADD(day,1,CONVERT(date,:hasta))';$params[':hasta']=$hasta;}
        $filtro=$where?' WHERE '.implode(' AND ',$where):'';
        $count=$db->prepare('SELECT COUNT_BIG(*) FROM dbo.th_logs_auditoria'.$filtro);$count->execute($params);$total=(int)$count->fetchColumn();
        $paginas=max(1,(int)ceil($total/$tamano));$pagina=min($pagina,$paginas);$offset=($pagina-1)*$tamano;
        $sql='SELECT log_id,fecha_hora,usuario,modulo,accion,descripcion_detalle,direccion_ip FROM dbo.th_logs_auditoria'.$filtro.' ORDER BY fecha_hora DESC,log_id DESC OFFSET '.$offset.' ROWS FETCH NEXT '.$tamano.' ROWS ONLY';
        $consulta=$db->prepare($sql);$consulta->execute($params);$rows=$consulta->fetchAll(PDO::FETCH_ASSOC);
        $registros = array_map(static function (array $r): array {
            $accion = strtoupper((string)$r['accion']);
            $nivel = str_contains($accion, 'FALLIDO') ? 'danger'
                : (in_array($accion, ['DAR_DE_BAJA', 'MOVER'], true) ? 'warning'
                : (in_array($accion, ['LOGIN', 'CREAR'], true) ? 'success' : 'info'));
            return [
                'id' => $r['log_id'], 'fecha' => $r['fecha_hora'], 'usuario' => $r['usuario'],
                'ip' => $r['direccion_ip'], 'accion' => $r['accion'], 'modulo' => $r['modulo'],
                'descripcion' => $r['descripcion_detalle'], 'nivel' => $nivel,
            ];
        }, $rows);

        $resumen=$db->query("SELECT SUM(CASE WHEN fecha_hora>=CONVERT(date,GETDATE()) THEN 1 ELSE 0 END) total_hoy,SUM(CASE WHEN accion LIKE '%FALLIDO%' OR accion IN ('DAR_DE_BAJA','MOVER') THEN 1 ELSE 0 END) alertas,COUNT(DISTINCT usuario) usuarios_activos FROM dbo.th_logs_auditoria")->fetch(PDO::FETCH_ASSOC);

        $datos = [
            'registros' => $registros,
            'resumen'   => $resumen,
            'paginacion'=>['pagina'=>$pagina,'paginas'=>$paginas,'total'=>$total,'tamano'=>$tamano],
            'filtros'=>['q'=>$termino,'modulo'=>$modulo,'desde'=>$desde,'hasta'=>$hasta],
            'modulos'=>$db->query('SELECT DISTINCT modulo FROM dbo.th_logs_auditoria ORDER BY modulo')->fetchAll(PDO::FETCH_COLUMN),
        ];
        $this->cargarVista('auditoria', 'logs', $datos);
    }

    public function exportarLogs(): void
    {
        $this->auditar('EXPORTAR_LOGS','Exportó la bitácora institucional.');
        $rows=Conexion::conectar()->query('SELECT log_id,fecha_hora,usuario,modulo,accion,descripcion_detalle,direccion_ip FROM dbo.th_logs_auditoria ORDER BY fecha_hora DESC,log_id DESC');
        header('Content-Type: text/csv; charset=UTF-8');header('Content-Disposition: attachment; filename="Auditoria_APM_'.date('Ymd_His').'.csv"');header('Cache-Control: private, no-store');
        $o=fopen('php://output','w');fprintf($o,"\xEF\xBB\xBF");fputcsv($o,['ID','Fecha y hora','Usuario','Módulo','Acción','Detalle','IP'],';');while($r=$rows->fetch(PDO::FETCH_NUM))fputcsv($o,$r,';');fclose($o);exit;
    }

    public function reporteAuditoria(): void
    {
        $db=Conexion::conectar();$hasta=$this->fechaSegura((string)($_GET['hasta']??date('Y-m-d')),date('Y-m-d'));
        $desde=$this->fechaSegura((string)($_GET['desde']??date('Y-m-d',strtotime('-29 days'))),date('Y-m-d',strtotime('-29 days')));
        if($desde>$hasta)[$desde,$hasta]=[$hasta,$desde];$usuario=trim((string)($_GET['usuario']??''));
        $usuarios=$db->query("SELECT DISTINCT usuario FROM dbo.th_logs_auditoria WHERE usuario<>'' ORDER BY usuario")->fetchAll(PDO::FETCH_COLUMN);
        if($usuario!==''&&!in_array($usuario,$usuarios,true))$usuario='';
        $where='fecha_hora>=CONVERT(date,:desde) AND fecha_hora<DATEADD(day,1,CONVERT(date,:hasta))';$params=[':desde'=>$desde,':hasta'=>$hasta];
        if($usuario!==''){$where.=' AND usuario=:usuario';$params[':usuario']=$usuario;}
        $summary=$db->prepare("SELECT COUNT_BIG(*) total_eventos,COUNT(DISTINCT usuario) usuarios,
            SUM(CASE WHEN accion IN ('LOGIN','MFA_VALIDADO') THEN 1 ELSE 0 END) accesos,
            SUM(CASE WHEN accion LIKE '%FALLIDO%' OR accion='ACCESO_DENEGADO' THEN 1 ELSE 0 END) alertas,
            COUNT(DISTINCT modulo) modulos FROM dbo.th_logs_auditoria WHERE {$where}");
        $summary->execute($params);$resumen=$summary->fetch(PDO::FETCH_ASSOC)?:[];
        $byUser=$db->prepare("SELECT TOP 100 usuario,COUNT_BIG(*) total_eventos,MAX(fecha_hora) ultima_actividad,
            SUM(CASE WHEN accion IN ('LOGIN','MFA_VALIDADO') THEN 1 ELSE 0 END) accesos,
            SUM(CASE WHEN accion LIKE '%FALLIDO%' OR accion='ACCESO_DENEGADO' THEN 1 ELSE 0 END) alertas,
            COUNT(DISTINCT modulo) modulos FROM dbo.th_logs_auditoria WHERE {$where} GROUP BY usuario ORDER BY total_eventos DESC,usuario");
        $byUser->execute($params);$porUsuario=$byUser->fetchAll(PDO::FETCH_ASSOC);
        $actions=$db->prepare("SELECT TOP 10 accion,COUNT_BIG(*) cantidad FROM dbo.th_logs_auditoria WHERE {$where} GROUP BY accion ORDER BY cantidad DESC");
        $actions->execute($params);$acciones=$actions->fetchAll(PDO::FETCH_ASSOC);
        $recent=$db->prepare("SELECT TOP 100 log_id,fecha_hora,usuario,modulo,accion,descripcion_detalle,direccion_ip FROM dbo.th_logs_auditoria WHERE {$where} ORDER BY fecha_hora DESC,log_id DESC");
        $recent->execute($params);$eventos=$recent->fetchAll(PDO::FETCH_ASSOC);
        $this->auditar('CONSULTAR_AUDITORIA',$usuario!==''?"Consultó reporte de auditoría del usuario {$usuario}.":'Consultó reporte general de auditoría.');
        $this->cargarVista('auditoria','reporte_auditoria',compact('desde','hasta','usuario','usuarios','resumen','porUsuario','acciones','eventos'));
    }

    public function exportarReporteAuditoria(): void
    {
        $db=Conexion::conectar();$desde=$this->fechaSegura((string)($_GET['desde']??date('Y-m-d',strtotime('-29 days'))),date('Y-m-d',strtotime('-29 days')));$hasta=$this->fechaSegura((string)($_GET['hasta']??date('Y-m-d')),date('Y-m-d'));$usuario=trim((string)($_GET['usuario']??''));
        $sql='SELECT log_id,fecha_hora,usuario,modulo,accion,descripcion_detalle,direccion_ip FROM dbo.th_logs_auditoria WHERE fecha_hora>=CONVERT(date,:desde) AND fecha_hora<DATEADD(day,1,CONVERT(date,:hasta))';$params=[':desde'=>$desde,':hasta'=>$hasta];
        if($usuario!==''){$sql.=' AND usuario=:usuario';$params[':usuario']=$usuario;}$sql.=' ORDER BY fecha_hora DESC,log_id DESC';
        $stmt=$db->prepare($sql);$stmt->execute($params);$this->auditar('EXPORTAR_AUDITORIA',$usuario!==''?"Exportó auditoría del usuario {$usuario}.":'Exportó auditoría general.');
        $suffix=$usuario!==''?'_'.$usuario:'';header('Content-Type: text/csv; charset=UTF-8');header('Content-Disposition: attachment; filename="Auditoria'.$suffix.'_'.date('Ymd_His').'.csv"');header('Cache-Control: private, no-store');
        $o=fopen('php://output','w');fprintf($o,"\xEF\xBB\xBF");fputcsv($o,['ID','Fecha y hora','Usuario','Módulo','Acción','Detalle','IP'],';');while($row=$stmt->fetch(PDO::FETCH_NUM))fputcsv($o,$row,';');fclose($o);exit;
    }

    private function fechaSegura(string $fecha,string $fallback): string
    {
        $d=DateTimeImmutable::createFromFormat('Y-m-d',$fecha);return $d&&$d->format('Y-m-d')===$fecha?$fecha:$fallback;
    }
}
