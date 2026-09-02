<?php

class PazSalvoController extends Controller
{
    private PazSalvoModel $modelo;
    public function __construct(){require_once ROOT.'/modules/talento-humano/Modelos/PazSalvoModel.php';$this->modelo=new PazSalvoModel();}
    public function index(): void{$this->cargarVista('talento-humano','paz_salvo',['documentos'=>$this->modelo->listar()]);}
    public function crear(): void{$this->cargarVista('talento-humano','paz_salvo_form',['documento'=>null,'acciones'=>$this->modelo->accionesSalidaDisponibles()]);}
    public function ver(): void{$id=(int)($_GET['id']??0);$d=$this->modelo->obtener($id);if(!$d){$this->volver('Documento no encontrado.',false);}$this->cargarVista('talento-humano','paz_salvo_form',['documento'=>$d,'acciones'=>[]]);}
    public function guardar(): void
    {
        $this->post();Auth::requireCsrf($_POST['_csrf']??null);$accion=(int)($_POST['accion_salida_id']??0);$empleado=(int)($_POST['empleado_id']??0);
        $emision=$this->fecha((string)($_POST['fecha_emision']??''));$salida=$this->fecha((string)($_POST['fecha_salida']??''));
        if($accion<=0||$empleado<=0||!$emision||!$salida){$this->volver('Complete la acción de salida y las fechas válidas.',false,'/talento-humano/paz-salvo/crear');}
        $r=$this->modelo->crear(['empleado_id'=>$empleado,'accion_salida_id'=>$accion,'fecha_emision'=>$emision,'fecha_salida'=>$salida,'lugar'=>trim((string)($_POST['lugar']??'Manta')),'observaciones_generales'=>trim((string)($_POST['observaciones_generales']??''))]);
        if((int)($r['exito']??0)===1){header('Location: '.BASE_URL.'/talento-humano/paz-salvo/ver?id='.(int)$r['paz_salvo_id'].'&ok=1&msg='.urlencode((string)$r['mensaje']));exit;}
        $this->volver((string)($r['mensaje']??'No fue posible crear el documento.'),false,'/talento-humano/paz-salvo/crear');
    }
    public function guardarSeccion(): void
    {
        $this->post();Auth::requireCsrf($_POST['_csrf']??null);$id=(int)($_POST['paz_salvo_id']??0);$codigo=strtoupper(trim((string)($_POST['codigo_seccion']??'')));
        if(!in_array($codigo,PazSalvoModel::SECCIONES,true)||$id<=0)$this->volver('Sección no válida.',false);
        $datos=(array)($_POST['datos']??[]);foreach($datos as $k=>$v)$datos[$k]=is_string($v)?trim($v):$v;
        $r=$this->modelo->guardarSeccion(['paz_salvo_id'=>$id,'codigo_seccion'=>$codigo,'estado'=>strtoupper(trim((string)($_POST['estado']??'PENDIENTE'))),'datos'=>$datos,'observaciones'=>trim((string)($_POST['observaciones']??'')),'responsable_nombre'=>trim((string)($_POST['responsable_nombre']??'')),'responsable_puesto'=>trim((string)($_POST['responsable_puesto']??'')),'sumilla'=>trim((string)($_POST['sumilla']??''))]);
        header('Location: '.BASE_URL.'/talento-humano/paz-salvo/ver?id='.$id.'&ok='.((int)($r['exito']??0)===1?'1':'0').'&msg='.urlencode((string)($r['mensaje']??'')));exit;
    }
    public function cerrar(): void{$this->post();Auth::requireCsrf($_POST['_csrf']??null);$id=(int)($_POST['id']??0);$r=$this->modelo->cerrar($id);header('Location: '.BASE_URL.'/talento-humano/paz-salvo/ver?id='.$id.'&ok='.((int)($r['exito']??0)===1?'1':'0').'&msg='.urlencode((string)($r['mensaje']??'')));exit;}
    public function imprimir(): void{$id=(int)($_GET['id']??0);$d=$this->modelo->obtener($id);if(!$d)$this->volver('Documento no encontrado.',false);$this->modelo->auditarImpresion($id);require_once ROOT.'/modules/talento-humano/Servicios/PdfPazSalvo.php';(new PdfPazSalvo($d))->render();}
    public function formatoBlanco(): void{$this->modelo->auditarImpresion(0,true);require_once ROOT.'/modules/talento-humano/Servicios/PdfPazSalvo.php';(new PdfPazSalvo(null))->render(true);}
    private function post():void{if(($_SERVER['REQUEST_METHOD']??'GET')!=='POST'){http_response_code(405);exit('Método no permitido.');}}
    private function fecha(string $v):?string{$d=DateTimeImmutable::createFromFormat('Y-m-d',$v);return $d&&$d->format('Y-m-d')===$v?$v:null;}
    private function volver(string $msg,bool $ok,string $ruta='/talento-humano/paz-salvo'):never{header('Location: '.BASE_URL.$ruta.'?ok='.($ok?'1':'0').'&msg='.urlencode($msg));exit;}
}
