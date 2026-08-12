<?php

class EstudioSeguridadController extends Controller
{
    private EstudioSeguridadModel $modelo;
    private EmpleadoModel $empleados;

    public function __construct()
    {
        require_once ROOT . '/modules/talento-humano/Modelos/EstudioSeguridadModel.php';
        require_once ROOT . '/modules/talento-humano/Modelos/EmpleadoModel.php';
        $this->modelo = new EstudioSeguridadModel();
        $this->empleados = new EmpleadoModel();
    }

    public function index(): void
    {
        $estudioId = (int)($_GET['estudio_id'] ?? 0);
        $empleadoId = (int)($_GET['id'] ?? 0);
        $estudio = $estudioId > 0 ? $this->modelo->obtenerPorId($estudioId) : null;
        if (!$estudio && $empleadoId > 0 && ($_GET['nuevo'] ?? '') !== '1') {
            $estudio = $this->modelo->ultimoPorEmpleado($empleadoId);
        }
        if ($estudio) {
            $empleadoId = (int)$estudio['empleado_id'];
            $estudio = $this->expandirColecciones($estudio);
        }

        $empleado = $empleadoId > 0 ? ($this->empleados->obtenerDetalleCompleto($empleadoId) ?: []) : [];
        $base = $this->prellenarDesdeEmpleado($empleado);
        $formulario = array_merge($base, $estudio ?: []);
        $flash = $_SESSION['socio_flash'] ?? null;
        unset($_SESSION['socio_flash']);
        if (is_array($flash) && is_array($flash['datos'] ?? null)) {
            $formulario = array_merge($formulario, $flash['datos']);
            $empleadoId = (int)($formulario['empleado_id'] ?? $empleadoId);
        }
        $formulario['id'] = $empleadoId;
        $formulario['empleado_id'] = $empleadoId;

        $this->cargarVista('talento-humano','estudio_seguridad',[
            'empleado'=>$formulario,
            'estudio'=>$formulario,
            'usuarioNombre'=>Auth::username(),
            'usuarioRol'=>$_SESSION['rol'] ?? 'Administrador TH',
            'codigoFormato'=>'APM-BASC-TH-FO-002',
            'fechaFormato'=>'01/04/2019',
            'selectorPersonal'=>$this->empleados->listarSelectorPersonal(),
            'errorFormulario'=>(string)($flash['error'] ?? ''),
        ]);
    }

    public function guardar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: '.BASE_URL.'/talento-humano/estudio-seguridad');
            exit;
        }
        Auth::requireCsrf($_POST['_csrf'] ?? null);
        try {
            $id = $this->modelo->guardar($_POST,Auth::username(),Auth::clientIp());
            $msg = 'Estudio socioeconómico guardado y auditado correctamente.';
            header('Location: '.BASE_URL.'/talento-humano/estudio-seguridad?estudio_id='.$id.'&ok=1&msg='.urlencode($msg));
        } catch (Throwable $e) {
            Conexion::registrarErrorLog($e,'talento-humano',false);
            $mensaje = $e instanceof InvalidArgumentException
                ? $e->getMessage()
                : 'No fue posible guardar el estudio socioeconómico. La incidencia fue registrada.';
            $_SESSION['socio_flash'] = ['datos'=>$_POST,'error'=>$mensaje];
            $params = ['ok'=>'0','msg'=>$mensaje];
            if ((int)($_POST['empleado_id'] ?? 0) > 0) $params['id']=(int)$_POST['empleado_id'];
            if ((int)($_POST['estudio_id'] ?? 0) > 0) $params['estudio_id']=(int)$_POST['estudio_id'];
            header('Location: '.BASE_URL.'/talento-humano/estudio-seguridad?'.http_build_query($params));
        }
        exit;
    }

    public function imprimir(): void
    {
        $vacio = ($_GET['blank'] ?? '') === '1';
        $id = (int)($_GET['estudio_id'] ?? 0);
        $datos = [];
        if (!$vacio) {
            if ($id <= 0) {
                http_response_code(400);
                exit('Debe indicar el estudio que desea imprimir.');
            }
            $datos = $this->modelo->obtenerPorId($id) ?: [];
            if (!$datos) {
                http_response_code(404);
                exit('El estudio socioeconómico no existe.');
            }
            $this->modelo->auditarImpresion($id,Auth::username(),Auth::clientIp());
        }
        require_once ROOT . '/modules/talento-humano/Servicios/PdfEstudioSocioeconomico.php';
        (new PdfEstudioSocioeconomico())->generar($datos,$vacio,'I');
        exit;
    }

    private function prellenarDesdeEmpleado(array $e): array
    {
        $nombres = trim((string)($e['nombres'] ?? ''));
        $apellidos = trim((string)($e['apellidos'] ?? ''));
        return [
            'fecha_vinculacion'=>$e['fecha_ingreso'] ?? '',
            'cargo_cabecera'=>$e['cargo'] ?? $e['denominacion_puesto'] ?? '',
            'nombre_cabecera'=>trim($nombres.' '.$apellidos),
            'tipo_doc_ident'=>'CEDULA',
            'nro_documento'=>$e['cedula'] ?? $e['identificacion'] ?? '',
            'nacionalidad'=>$e['nacionalidad'] ?? 'ECUATORIANA',
            'apellidos'=>$apellidos,
            'nombres'=>$nombres,
            'fecha_nacimiento'=>$e['fecha_nacimiento'] ?? '',
            'genero'=>$e['genero'] ?? $e['sexo'] ?? '',
            'estado_civil'=>$e['estado_civil'] ?? '',
            'tipo_sangre'=>$e['tipo_sangre'] ?? '',
            'dir_calle_principal'=>$e['direccion_domiciliaria'] ?? '',
            'canton'=>$e['ciudad_residencia'] ?? '',
            'tel_domicilio'=>$e['telefono_convencional'] ?? '',
            'tel_celular'=>$e['telefono_movil'] ?? $e['telefono'] ?? '',
            'correo_institucional'=>$e['correo'] ?? $e['correo_institucional'] ?? '',
            'correo_alternativo'=>$e['correo_personal'] ?? '',
            'contacto_nombre'=>$e['contacto_emergencia'] ?? '',
            'contacto_parentesco'=>$e['emergencia_relacion'] ?? '',
            'contacto_tel_conv'=>$e['telefono_convencional'] ?? '',
            'contacto_tel_cel'=>$e['tel_emergencia'] ?? '',
            'banco'=>$e['institucion_bancaria'] ?? '',
            'tipo_cuenta'=>$e['tipo_cuenta'] ?? '',
            'nro_cuenta'=>$e['numero_cuenta'] ?? '',
        ];
    }

    private function expandirColecciones(array $d): array
    {
        foreach (($d['hijos'] ?? []) as $fila) {
            $i=(int)$fila['orden'];
            $d["hijo_nombre_{$i}"]=$fila['nombres_apellidos']??'';
            $d["hijo_fnac_{$i}"]=$fila['fecha_nacimiento']??'';
            $d["hijo_tipo_doc_{$i}"]=$fila['tipo_documento']??'';
            $d["hijo_nro_doc_{$i}"]=$fila['numero_documento']??'';
            $d["hijo_edad_{$i}"]=$fila['edad']??'';
            $d["hijo_instruccion_{$i}"]=$fila['nivel_instruccion']??'';
            $d["hijo_ocupacion_{$i}"]=$fila['ocupacion']??'';
        }
        foreach (($d['capacitaciones'] ?? []) as $fila) {
            $i=(int)$fila['orden'];
            $d["cap{$i}_evento"]=$fila['evento']??'';$d["cap{$i}_tipo"]=$fila['tipo_evento']??'';
            $d["cap{$i}_auspiciante"]=$fila['auspiciante']??'';$d["cap{$i}_tipo_cert"]=$fila['tipo_certificado']??'';
            $d["cap{$i}_certificado_por"]=$fila['certificado_por']??'';$d["cap{$i}_fecha_inicio"]=$fila['fecha_inicio']??'';
        }
        foreach (($d['experiencias'] ?? []) as $fila) {
            $i=(int)$fila['orden'];$map=['institucion'=>'institucion','tipo'=>'tipo_institucion','unidad'=>'unidad_administrativa','cargo'=>'cargo','antiguedad'=>'antiguedad','jefe'=>'jefe_inmediato','tel'=>'telefono','fecha_ingreso'=>'fecha_ingreso','motivo_ingreso'=>'motivo_ingreso','fecha_retiro'=>'fecha_retiro','motivo_retiro'=>'motivo_retiro'];
            foreach($map as $dest=>$origen)$d["exp_{$dest}_{$i}"]=$fila[$origen]??'';
        }
        return $d;
    }
}
