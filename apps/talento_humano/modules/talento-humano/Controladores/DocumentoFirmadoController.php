<?php

class DocumentoFirmadoController extends Controller
{
    private DocumentoFirmadoModel $modelo;
    private DocumentoFirmadoService $archivos;

    public function __construct()
    {
        require_once ROOT.'/modules/talento-humano/Modelos/DocumentoFirmadoModel.php';
        require_once ROOT.'/modules/talento-humano/Servicios/DocumentoFirmadoService.php';
        $this->modelo=new DocumentoFirmadoModel();$this->archivos=new DocumentoFirmadoService();
    }

    public function index(): void
    {
        [$tipo,$origenId]=$this->origenSolicitud($_GET);
        $origen=$this->modelo->resolverOrigen($tipo,$origenId);
        if(!$origen) ErrorHandler::abort(404,'El formulario de origen no existe.');
        $this->cargarVista('talento-humano','documentos_firmados',[
            'origen'=>$origen,'documentos'=>$this->modelo->listar($tipo,$origenId,(int)$origen['empleado_id'])
        ]);
    }

    public function subir(): void
    {
        if(($_SERVER['REQUEST_METHOD']??'GET')!=='POST') ErrorHandler::abort(405);
        Auth::requireCsrf($_POST['_csrf']??null);
        [$tipo,$origenId]=$this->origenSolicitud($_POST);
        $origen=$this->modelo->resolverOrigen($tipo,$origenId);
        if(!$origen) ErrorHandler::abort(404,'El formulario de origen no existe.');
        if(empty($origen['legalizable'])) {
            $this->volver($tipo,$origenId,'El formulario debe completar su aprobación o cierre antes de incorporar el PDF firmado.',false);
        }
        $guardado=null;
        try {
            $guardado=$this->archivos->guardar($_FILES['documento_firmado']??[]);
            $resultado=$this->modelo->registrar(array_merge($guardado,[
                'empleado_id'=>(int)$origen['empleado_id'],'tipo_documento'=>$tipo,'origen_id'=>$origenId,
                'observaciones'=>mb_substr(trim((string)($_POST['observaciones']??'')),0,500),
            ]));
            if((int)($resultado['exito']??0)!==1) throw new RuntimeException((string)($resultado['mensaje']??'No fue posible registrar el documento.'));
            $this->volver($tipo,$origenId,(string)$resultado['mensaje'],true);
        } catch(Throwable $e) {
            $this->archivos->eliminarSiExiste($guardado['ruta_absoluta']??null);
            Conexion::registrarErrorLog($e,'talento-humano',false);
            $mensaje=$e instanceof InvalidArgumentException||$e instanceof RuntimeException?$e->getMessage():'No fue posible incorporar el documento firmado.';
            $this->volver($tipo,$origenId,$mensaje,false);
        }
    }

    public function descargar(): void
    {
        $id=(int)($_GET['id']??0);$documento=$this->modelo->obtener($id);
        if(!$documento) ErrorHandler::abort(404,'El documento firmado no existe.');
        try {$ruta=$this->archivos->resolverRuta((string)$documento['ruta_privada']);}
        catch(Throwable $e){Conexion::registrarErrorLog($e,'talento-humano',false);ErrorHandler::abort(404,'El archivo firmado no está disponible.');}
        $hash=hash_file('sha256',$ruta);
        if(!hash_equals(strtolower((string)$documento['sha256']),strtolower($hash))){
            Conexion::registrarErrorLog(new RuntimeException("Integridad inválida del documento firmado #{$id}."),'talento-humano',false);
            ErrorHandler::abort(500,'La verificación de integridad del documento falló.');
        }
        $this->modelo->auditarDescarga($documento);
        $nombre=preg_replace('/[^A-Za-z0-9._-]+/','_',pathinfo((string)$documento['nombre_original'],PATHINFO_FILENAME)).'_v'.(int)$documento['version_documento'].'.pdf';
        header('Content-Type: application/pdf');header('Content-Length: '.filesize($ruta));
        header('Content-Disposition: inline; filename="'.$nombre.'"');header('Cache-Control: private, no-store, max-age=0');
        readfile($ruta);exit;
    }

    private function origenSolicitud(array $entrada): array
    {
        $tipo=strtoupper(trim((string)($entrada['tipo']??'')));$id=(int)($entrada['origen_id']??0);
        if(!in_array($tipo,DocumentoFirmadoModel::TIPOS,true)||$id<=0) ErrorHandler::abort(400,'Origen documental no válido.');
        return [$tipo,$id];
    }

    private function volver(string $tipo,int $origenId,string $mensaje,bool $ok): never
    {
        header('Location: '.BASE_URL.'/talento-humano/documentos-firmados?'.http_build_query(['tipo'=>$tipo,'origen_id'=>$origenId,'ok'=>$ok?'1':'0','msg'=>$mensaje]));exit;
    }
}
