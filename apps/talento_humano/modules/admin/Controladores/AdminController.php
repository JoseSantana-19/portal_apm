<?php

final class AdminController extends Controller
{
    private AdminModel $modelo;

    public function __construct()
    {
        require_once ROOT.'/modules/admin/Modelos/AdminModel.php';
        $this->modelo=new AdminModel();
    }

    public function usuarios(): void
    {
        $usuarios=$this->modelo->usuarios();
        $this->cargarVista('admin','usuarios_reales',[
            'usuarios'=>$usuarios,'roles'=>$this->modelo->roles(),'empleados'=>$this->modelo->empleadosDisponibles(),
            'total'=>count($usuarios),'activos'=>count(array_filter($usuarios,fn($u)=>(bool)$u['estado'])),
            'inactivos'=>count(array_filter($usuarios,fn($u)=>!(bool)$u['estado'])),
            'claveTemporal'=>$_SESSION['flash_secret']??null,
        ]);
        unset($_SESSION['flash_secret']);
    }

    public function crearUsuario(): void
    {
        $this->post();
        $this->redirect('admin/usuarios',$this->modelo->crearUsuario($_POST));
    }

    public function estadoUsuario(): void
    {
        $this->post();
        $this->redirect('admin/usuarios',$this->modelo->cambiarEstadoUsuario((int)($_POST['usuario_id']??0),(int)($_POST['estado']??0)===1));
    }

    public function resetearClave(): void
    {
        $this->post();
        $resultado=$this->modelo->restablecerClave((int)($_POST['usuario_id']??0));
        if(!empty($resultado['clave_temporal']))$_SESSION['flash_secret']=$resultado['clave_temporal'];
        unset($resultado['clave_temporal']);
        $this->redirect('admin/usuarios',$resultado);
    }

    public function resetearMfa(): void
    {
        $this->post();$this->redirect('admin/usuarios',$this->modelo->restablecerMfa((int)($_POST['usuario_id']??0)));
    }

    public function roles(): void
    {
        $filas=$this->modelo->modulosPermisos();$matriz=[];$modulos=[];
        foreach($filas as $f){$modulos[$f['modulo_id']]=['id'=>$f['modulo_id'],'codigo'=>$f['codigo_modulo'],'nombre'=>$f['nombre_modulo']];$matriz[$f['rol_id']][$f['modulo_id']]=$f;}
        $this->cargarVista('admin','roles_reales',['roles'=>$this->modelo->roles(),'modulos'=>$modulos,'matriz'=>$matriz]);
    }

    public function crearRol(): void
    {
        $this->post();
        $this->redirect('admin/roles',$this->modelo->crearRol((string)($_POST['nombre_rol']??'')));
    }

    public function estadoRol(): void
    {
        $this->post();$rolId=(int)($_POST['rol_id']??0);
        $this->redirect('admin/roles',$this->modelo->cambiarEstadoRol($rolId,(int)($_POST['estado']??0)===1),['rol'=>$rolId]);
    }

    public function guardarPermisos(): void
    {
        $this->post();$rolId=(int)($_POST['rol_id']??0);
        $this->redirect('admin/roles',$this->modelo->guardarPermisos($rolId,$_POST['permisos']??[]),['rol'=>$rolId]);
    }

    public function politicas(): void
    {
        $docs=$this->modelo->documentos();
        $this->cargarVista('admin','politicas_reales',['documentos'=>$docs,'total'=>count($docs),'vigentes'=>count(array_filter($docs,fn($d)=>(bool)$d['vigente']))]);
    }

    public function subirDocumento(): void
    {
        $this->post();
        $archivo=$_FILES['archivo']??null;
        if(!$archivo || ($archivo['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK || ($archivo['size']??0)>20*1024*1024){
            $this->redirect('admin/politicas',['exito'=>0,'mensaje'=>'Seleccione un archivo válido de máximo 20 MB.']);
        }
        $mime=(new finfo(FILEINFO_MIME_TYPE))->file($archivo['tmp_name']);
        $permitidos=['application/pdf'=>'pdf','application/vnd.openxmlformats-officedocument.wordprocessingml.document'=>'docx'];
        if(!isset($permitidos[$mime]))$this->redirect('admin/politicas',['exito'=>0,'mensaje'=>'Solo se permiten documentos PDF o DOCX.']);
        $dir=Config::privateDirectory().DIRECTORY_SEPARATOR.'documents';
        if(!is_dir($dir) && !mkdir($dir,0700,true) && !is_dir($dir))$this->redirect('admin/politicas',['exito'=>0,'mensaje'=>'No fue posible preparar el repositorio privado.']);
        $nombre=bin2hex(random_bytes(16)).'.'.$permitidos[$mime];$ruta=$dir.DIRECTORY_SEPARATOR.$nombre;
        if(!move_uploaded_file($archivo['tmp_name'],$ruta))$this->redirect('admin/politicas',['exito'=>0,'mensaje'=>'No fue posible almacenar el documento.']);
        $titulo=trim((string)($_POST['titulo']??''));$categoria=trim((string)($_POST['categoria']??''));$version=trim((string)($_POST['version']??''));
        if($titulo===''||$categoria===''||$version===''){
            @unlink($ruta);
            $this->redirect('admin/politicas',['exito'=>0,'mensaje'=>'Título, categoría y versión son obligatorios.']);
        }
        try {
            $this->modelo->registrarDocumento([
            ':titulo'=>$titulo,':categoria'=>$categoria,
            ':version'=>$version,':descripcion'=>trim((string)($_POST['descripcion']??'')),
            ':nombre'=>basename((string)$archivo['name']),':ruta'=>$ruta,':mime'=>$mime,':tamano'=>(int)$archivo['size'],':usuario'=>Auth::username(),
            ]);
        } catch(Throwable $e) {
            @unlink($ruta);
            Conexion::registrarErrorLog($e,'admin',false);
            $this->redirect('admin/politicas',['exito'=>0,'mensaje'=>'No fue posible registrar el documento.']);
        }
        $this->redirect('admin/politicas',['exito'=>1,'mensaje'=>'Documento publicado en el repositorio privado.']);
    }

    public function retirarDocumento(): void
    {
        $this->post();
        $this->redirect('admin/politicas',$this->modelo->retirarDocumento((int)($_POST['documento_id']??0)));
    }

    public function descargarDocumento(): void
    {
        $id=(int)($_GET['id']??0);$doc=$this->modelo->documento($id);
        if(!$doc || !is_file($doc['ruta_privada'])){http_response_code(404);exit('Documento no encontrado.');}
        $this->modelo->registrarDescarga($id);
        header('Content-Type: '.$doc['mime_type']);header('Content-Length: '.filesize($doc['ruta_privada']));
        header('Content-Disposition: attachment; filename="'.str_replace('"','',basename($doc['nombre_archivo'])).'"');
        header('Cache-Control: private, no-store');readfile($doc['ruta_privada']);exit;
    }

    private function post(): void
    {
        if(($_SERVER['REQUEST_METHOD']??'GET')!=='POST'){http_response_code(405);exit('Metodo no permitido.');}
        Auth::requireCsrf($_POST['_csrf']??null);
    }

    private function redirect(string $ruta,array $resultado,array $extra=[]): never
    {
        $ok=(int)($resultado['exito']??0)===1;$msg=(string)($resultado['mensaje']??'Operación finalizada.');
        $query=http_build_query(array_merge(['ok'=>$ok?'1':'0','msg'=>$msg],$extra));
        header('Location: '.BASE_URL.'/'.$ruta.'?'.$query);exit;
    }
}
