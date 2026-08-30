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
        $nuevosArchivos = [];
        try {
            $entrada = $_POST;
            $estudioId = (int)($entrada['estudio_id'] ?? 0);
            $anteriores = $estudioId > 0 ? $this->modelo->obtenerArchivosUbicacion($estudioId) : [];
            $cambioCoordenadas = $this->coordenadasCambiaron($entrada, $anteriores);
            foreach (['mapa'=>'mapa_imagen','qr'=>'qr_imagen'] as $tipo=>$campo) {
                $data = (string)($entrada[$tipo.'_png_data'] ?? '');
                $anterior = $cambioCoordenadas ? null : (string)($anteriores[$campo] ?? '');
                $entrada[$campo] = $this->guardarImagenUbicacion($data,$tipo,$anterior,$nuevosArchivos);
            }
            $tieneCoordenadas = trim((string)($entrada['latitud'] ?? '')) !== ''
                && trim((string)($entrada['longitud'] ?? '')) !== '';
            if ($tieneCoordenadas && trim((string)($entrada['qr_imagen'] ?? '')) === '') {
                throw new InvalidArgumentException(
                    'No fue posible generar el código QR de la ubicación. Espere a que aparezca la vista previa e intente guardar nuevamente.'
                );
            }
            unset($entrada['mapa_png_data'],$entrada['qr_png_data']);
            $id = $this->modelo->guardar($entrada,Auth::username(),Auth::clientIp());
            foreach (['mapa_imagen','qr_imagen'] as $campo) {
                $anterior=(string)($anteriores[$campo]??'');$actual=(string)($entrada[$campo]??'');
                if ($anterior !== '' && $anterior !== $actual) $this->eliminarImagenUbicacion($anterior);
            }
            DraftService::deleteCurrent((string)($_POST['_draft_context'] ?? ''));
            $msg = 'Estudio socioeconómico guardado y auditado correctamente.';
            header('Location: '.BASE_URL.'/talento-humano/estudio-seguridad?estudio_id='.$id.'&ok=1&msg='.urlencode($msg));
        } catch (Throwable $e) {
            foreach ($nuevosArchivos as $archivo) $this->eliminarImagenUbicacion($archivo);
            Conexion::registrarErrorLog($e,'talento-humano',false);
            $mensaje = $e instanceof InvalidArgumentException
                ? $e->getMessage()
                : 'No fue posible guardar el estudio socioeconómico. La incidencia fue registrada.';
            $datosFlash=$_POST;unset($datosFlash['mapa_png_data'],$datosFlash['qr_png_data']);
            $_SESSION['socio_flash'] = ['datos'=>$datosFlash,'error'=>$mensaje];
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
                ErrorHandler::abort(400, 'Debe indicar el estudio que desea imprimir.');
            }
            $datos = $this->modelo->obtenerPorId($id) ?: [];
            if (!$datos) {
                ErrorHandler::abort(404, 'El estudio socioeconómico no existe.');
            }
            $this->modelo->auditarImpresion($id,Auth::username(),Auth::clientIp());
        }
        require_once ROOT . '/modules/talento-humano/Servicios/PdfEstudioSocioeconomico.php';
        (new PdfEstudioSocioeconomico())->generar($datos,$vacio,'I');
        exit;
    }

    public function resolverMapa(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
                throw new InvalidArgumentException('Método de consulta no permitido.');
            }
            Auth::requireCsrf($_POST['_csrf'] ?? null);
            $url = trim((string)($_POST['url'] ?? ''));
            $this->validarUrlMapa($url);
            $coordenadas = $this->extraerCoordenadas($url);
            $final = $url;
            for ($salto=0; !$coordenadas && $salto<5; $salto++) {
                $this->validarDestinoPublico($final);
                $contexto = stream_context_create(['http'=>[
                    'method'=>'HEAD','follow_location'=>0,'ignore_errors'=>true,'timeout'=>4,
                    'user_agent'=>'PortalAPM-Geolocation/1.0'
                ]]);
                $cabeceras = @get_headers($final,true,$contexto);
                if (!is_array($cabeceras)) throw new RuntimeException('No fue posible resolver el enlace corto de Google Maps.');
                $location = $cabeceras['Location'] ?? $cabeceras['location'] ?? null;
                if (is_array($location)) $location=end($location);
                if (!is_string($location) || $location==='') break;
                $final=$this->resolverUrlRelativa($final,$location);
                $this->validarUrlMapa($final);
                $coordenadas=$this->extraerCoordenadas($final);
            }
            if (!$coordenadas) throw new InvalidArgumentException('El enlace no contiene coordenadas. Seleccione la ubicación directamente en el mapa.');
            echo json_encode(['success'=>true,'final_url'=>$final,'latitud'=>$coordenadas[0],'longitud'=>$coordenadas[1]],JSON_UNESCAPED_SLASHES);
        } catch (Throwable $e) {
            http_response_code(422);
            echo json_encode(['success'=>false,'message'=>$e instanceof InvalidArgumentException?$e->getMessage():'No fue posible resolver el enlace de Maps.']);
        }
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

    private function coordenadasCambiaron(array $entrada,array $anterior): bool
    {
        $normalizar=static fn($v):?string=>trim((string)$v)===''?null:number_format((float)str_replace(',','.',(string)$v),6,'.','');
        return $normalizar($entrada['latitud']??null)!==$normalizar($anterior['latitud']??null)
            || $normalizar($entrada['longitud']??null)!==$normalizar($anterior['longitud']??null);
    }

    private function guardarImagenUbicacion(string $data,string $tipo,?string $anterior,array &$nuevos): ?string
    {
        if ($data==='') return $anterior!==null&&$anterior!==''?$anterior:null;
        if (!preg_match('#^data:image/png;base64,([A-Za-z0-9+/=]+)$#D',$data,$m)) {
            throw new InvalidArgumentException('La imagen de ubicación no tiene un formato PNG válido.');
        }
        $binario=base64_decode($m[1],true);
        if ($binario===false || strlen($binario)>2500000) throw new InvalidArgumentException('La imagen de ubicación supera el tamaño permitido.');
        $info=@getimagesizefromstring($binario);
        if (!$info || ($info['mime']??'')!=='image/png') throw new InvalidArgumentException('La imagen de ubicación no es un PNG verificable.');
        [$ancho,$alto]=[$info[0],$info[1]];
        if ($tipo==='qr' && ($ancho<96 || $ancho>1024 || $alto<96 || $alto>1024 || abs($ancho-$alto)>4)) {
            throw new InvalidArgumentException('El código QR no cumple las dimensiones permitidas.');
        }
        if ($tipo==='mapa' && ($ancho<280 || $ancho>2200 || $alto<180 || $alto>1400)) {
            throw new InvalidArgumentException('La captura del mapa no cumple las dimensiones permitidas.');
        }
        $directorio=Config::privateDirectory().DIRECTORY_SEPARATOR.'socio-geolocation';
        if (!is_dir($directorio) && !mkdir($directorio,0700,true) && !is_dir($directorio)) throw new RuntimeException('No fue posible crear el repositorio privado de mapas.');
        $nombre='socio-'.$tipo.'-'.bin2hex(random_bytes(16)).'.png';
        $ruta=$directorio.DIRECTORY_SEPARATOR.$nombre;
        if (file_put_contents($ruta,$binario,LOCK_EX)===false) throw new RuntimeException('No fue posible guardar la imagen de ubicación.');
        @chmod($ruta,0600);$nuevos[]=$nombre;
        return $nombre;
    }

    private function eliminarImagenUbicacion(string $nombre): void
    {
        if (!preg_match('/^socio-(?:mapa|qr)-[a-f0-9]{32}\.png$/',$nombre)) return;
        $base=realpath(Config::privateDirectory().DIRECTORY_SEPARATOR.'socio-geolocation');
        if ($base===false) return;
        $ruta=realpath($base.DIRECTORY_SEPARATOR.$nombre);
        if ($ruta!==false && str_starts_with($ruta,$base.DIRECTORY_SEPARATOR) && is_file($ruta)) @unlink($ruta);
    }

    private function validarUrlMapa(string $url): void
    {
        if ($url==='' || strlen($url)>2048) throw new InvalidArgumentException('Ingrese un enlace válido de Google Maps.');
        $p=parse_url($url);$host=strtolower((string)($p['host']??''));
        $permitido=$host==='google.com'||str_ends_with($host,'.google.com')||$host==='maps.app.goo.gl'||$host==='goo.gl';
        if (($p['scheme']??'')!=='https'||!$permitido||isset($p['user'])||isset($p['pass'])||(isset($p['port'])&&(int)$p['port']!==443)) {
            throw new InvalidArgumentException('Solo se permiten enlaces HTTPS oficiales de Google Maps.');
        }
    }

    private function extraerCoordenadas(string $url): ?array
    {
        $texto=urldecode($url);
        foreach ([
            '#@(-?\d{1,2}(?:\.\d+)?),(-?\d{1,3}(?:\.\d+)?)#',
            '#[?&](?:q|query|ll)=(-?\d{1,2}(?:\.\d+)?),(-?\d{1,3}(?:\.\d+)?)#i',
            '#/(?:maps/)?(?:search|place)/(-?\d{1,2}(?:\.\d+)?),[+\s]*(-?\d{1,3}(?:\.\d+)?)#i',
        ] as $patron) {
            if (preg_match($patron,$texto,$m)) {
                $lat=(float)$m[1];$lng=(float)$m[2];
                if ($lat>=-90&&$lat<=90&&$lng>=-180&&$lng<=180) return [$lat,$lng];
            }
        }
        return null;
    }

    private function validarDestinoPublico(string $url): void
    {
        $host=(string)(parse_url($url,PHP_URL_HOST)??'');$ips=gethostbynamel($host)?:[];
        foreach (@dns_get_record($host,DNS_AAAA)?:[] as $fila) if (!empty($fila['ipv6'])) $ips[]=$fila['ipv6'];
        if (!$ips) throw new RuntimeException('El dominio de Maps no pudo resolverse.');
        foreach ($ips as $ip) if (!filter_var($ip,FILTER_VALIDATE_IP,FILTER_FLAG_NO_PRIV_RANGE|FILTER_FLAG_NO_RES_RANGE)) {
            throw new InvalidArgumentException('El enlace intenta acceder a una dirección no permitida.');
        }
    }

    private function resolverUrlRelativa(string $base,string $location): string
    {
        if (str_starts_with($location,'https://')) return $location;
        if (str_starts_with($location,'//')) return 'https:'.$location;
        $p=parse_url($base);$origen='https://'.($p['host']??'');
        return $origen.'/'.ltrim($location,'/');
    }
}
