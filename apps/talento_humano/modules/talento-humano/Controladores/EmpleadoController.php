<?php
// modules/talento-humano/Controladores/EmpleadoController.php
// RECTIFICADO v2.0 – Auditoría Técnica Autoridad Portuaria de Manta

class EmpleadoController extends Controller
{
    private EmpleadoModel $modelo;

    public function __construct()
    {
        require_once ROOT . '/modules/talento-humano/Modelos/EmpleadoModel.php';
        $this->modelo = new EmpleadoModel();
    }

    /** GET / y GET /talento-humano – Pantalla de Inicio (Dashboard RRHH) */
    public function index(): void
    {
        $datos = [
            'empleados'   => $this->modelo->listarDirectorio(),
            'rbu_vigente' => $this->modelo->obtenerRbuVigente(),
            'resumenVacaciones' => $this->modelo->resumenVacaciones(),
            'hitosServicio' => $this->modelo->obtenerHitosServicio((int)InstitutionalClock::today()->format('Y')),
        ];
        $this->cargarVista('talento-humano', 'inicio', $datos);
    }

    /** GET /talento-humano/inicio – Alias explícito del Dashboard */
    public function inicio(): void
    {
        $this->index();
    }

    /** GET /talento-humano/directorio – Vista de listado de funcionarios */
    public function directorio(): void
    {
        if (($_GET['modo'] ?? '') === 'movimiento') {
            Auth::requirePermission('movimientos', 'visualizar');
        }
        $datos = [
            'empleados'   => $this->modelo->listarDirectorio(),
            'rbu_vigente' => $this->modelo->obtenerRbuVigente(),
        ];
        $this->cargarVista('talento-humano', 'directorio', $datos);
    }

    /** GET /talento-humano/empleado/crear – Formulario vacío (modo creación) */
    public function crear(): void
    {
        $datos = [
            'funcionarios' => $this->modelo->listarDirectorio(),
            'rbu_vigente'  => $this->modelo->obtenerRbuVigente(),
            'empleado'     => null,
            'modoEdicion'  => false,
            'areas'        => $this->modelo->listarAreas(),
            'cargos'       => $this->modelo->listarCargos(),
            'nacionalidades' => $this->modelo->listarNacionalidades(),
            'nacionalidadesEmpleado' => [],
        ];
        $this->cargarVista('talento-humano', 'formulario', $datos);
    }

    /** GET /talento-humano/empleado/editar?id=X – Formulario pre-cargado (modo edición) */
    public function editar(): void
    {
        $id       = (int)($_GET['id'] ?? 0);
        $empleado = $this->modelo->obtenerPorId($id);

        if (!$empleado) {
            header('Location: ' . BASE_URL . '/talento-humano?msg=' . urlencode('Empleado no encontrado') . '&ok=0');
            exit;
        }

        $datos = [
            'funcionarios' => $this->modelo->listarDirectorio(),
            'rbu_vigente'  => $this->modelo->obtenerRbuVigente(),
            'empleado'     => $empleado,
            'modoEdicion'  => true,
            'areas'        => $this->modelo->listarAreas(),
            'cargos'       => $this->modelo->listarCargos(),
            'nacionalidades' => $this->modelo->listarNacionalidades(),
            'nacionalidadesEmpleado' => $this->modelo->obtenerNacionalidadesEmpleado($id),
        ];
        $this->cargarVista('talento-humano', 'formulario', $datos);
    }

    /** POST /talento-humano/empleado/guardar – Crea o actualiza según empId */
    public function guardar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/talento-humano');
            exit;
        }

        Auth::requireCsrf($_POST['_csrf'] ?? null);
        if (!$this->normalizarNombres($_POST)) {
            header('Location: ' . BASE_URL . '/talento-humano/empleado/crear?msg=' . urlencode('Ingrese nombres y apellidos completos.') . '&ok=0');
            exit;
        }
        if (($errorValidacion=$this->validarEmpleado($_POST)) !== null) {
            $destino=!empty($_POST['empId'])?'/talento-humano/empleado/editar?id='.(int)$_POST['empId']:'/talento-humano/empleado/crear';
            $sep=str_contains($destino,'?')?'&':'?';
            header('Location: '.BASE_URL.$destino.$sep.'msg='.urlencode($errorValidacion).'&ok=0');
            exit;
        }

        // ── Procesar foto subida ──────────────────────────────────────────
        $id    = !empty($_POST['empId']) ? (int)$_POST['empId'] : null;
        $empleadoAnterior = $id ? $this->modelo->obtenerPorId($id) : null;
        $rutaAnterior = (string)($empleadoAnterior['ruta_foto'] ?? 'public/img/default_avatar.png');
        $_POST['ruta_foto'] = $this->_procesarFoto($id ? (string)$id : null);
        $rutaNueva = (string)$_POST['ruta_foto'];

        try {
            $resultado = $id
                ? $this->modelo->modificar($id, $_POST)
                : $this->modelo->insertar($_POST);
        } catch (Throwable $e) {
            error_log('[EmpleadoController::guardar] ' . $e->getMessage());
            $resultado = 'Error inesperado al procesar la solicitud.';
        }

        $exito = $resultado === true;

        if ($exito && $id && $rutaNueva !== $rutaAnterior) {
            $this->eliminarFotoGestionada($rutaAnterior);
        } elseif (!$exito && $rutaNueva !== $rutaAnterior) {
            $this->eliminarFotoGestionada($rutaNueva);
        }

        if ($exito) {
            DraftService::deleteCurrent((string)($_POST['_draft_context'] ?? ''));
            $msg = $id ? 'Expediente actualizado correctamente.' : 'Funcionario registrado con éxito.';
        } else {
            // Usar el mensaje específico del SP/BD si está disponible
            $msgBd = is_string($resultado) ? $resultado : '';
            // Simplificar mensajes técnicos de SQL Server en texto amigable
            if (str_contains($msgBd, 'identificacion') || str_contains($msgBd, 'Ya existe')) {
                $cedulaIngresada = trim((string)($_POST['cedula'] ?? ''));
                $msg = "La cédula {$cedulaIngresada} ya está registrada en el sistema. Verifique el directorio de funcionarios.";
            } elseif ($msgBd !== '') {
                $msg = $msgBd;
            } else {
                $msg = 'Error al guardar. La incidencia ha sido registrada por el sistema.';
            }
        }

        header('Location: ' . BASE_URL . '/talento-humano?msg=' . urlencode($msg) . '&ok=' . ($exito ? '1' : '0'));
        exit;
    }

    /**
     * Procesa el archivo de foto subido (si existe).
     * Valida tipo MIME real, tamaño y mueve el archivo a public/img/empleados/.
     * En edición, si no se sube nueva foto conserva la foto anterior de la BD.
     *
     * @param  string|null $empId  ID del empleado (vacío en creación)
     * @return string  Ruta relativa lista para guardar, o ruta por defecto
     */
    private function normalizarNombres(array &$data): bool
    {
        $apellidos = trim((string)($data['apellidos'] ?? ''));
        $nombres = trim((string)($data['nombres'] ?? ''));
        if ($apellidos !== '' && $nombres !== '') {
            $data['apellidos'] = preg_replace('/\s+/u', ' ', $apellidos);
            $data['nombres'] = preg_replace('/\s+/u', ' ', $nombres);
            return true;
        }

        $completo = trim((string)($data['nombre_completo'] ?? $nombres));
        $partes = preg_split('/\s+/u', $completo, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if (count($partes) < 2) {
            return false;
        }
        $cantidadApellidos = count($partes) >= 3 ? 2 : 1;
        $data['apellidos'] = implode(' ', array_slice($partes, 0, $cantidadApellidos));
        $data['nombres'] = implode(' ', array_slice($partes, $cantidadApellidos));
        return $data['nombres'] !== '';
    }

    private function validarEmpleado(array $data): ?string
    {
        $cedula=trim((string)($data['cedula']??''));
        if(!preg_match('/^[0-9A-Za-z]{10,15}$/',$cedula))return 'La identificación debe contener entre 10 y 15 caracteres alfanuméricos.';
        foreach(['apellidos'=>'apellidos','nombres'=>'nombres'] as $campo=>$etiqueta){
            $valor=trim((string)($data[$campo]??''));
            if(mb_strlen($valor)<2||!preg_match("/^[\\p{L} .'-]+$/u",$valor))return "Revise el campo {$etiqueta}.";
        }
        foreach(['fecha_nac'=>'fecha de nacimiento','fecha_ingreso'=>'fecha de ingreso'] as $campo=>$etiqueta){
            if(empty($data[$campo]))continue;$fecha=DateTimeImmutable::createFromFormat('Y-m-d',(string)$data[$campo]);
            if(!$fecha||$fecha->format('Y-m-d')!==$data[$campo]||$fecha>new DateTimeImmutable('today'))return "La {$etiqueta} no es válida.";
        }
        if(!empty($data['correo'])&&!filter_var($data['correo'],FILTER_VALIDATE_EMAIL))return 'El correo institucional no es válido.';
        if((int)($data['unidad_id']??0)<=0||(int)($data['puesto_id']??0)<=0)return 'Debe seleccionar un área y un cargo vigentes.';
        $horas=(float)($data['horas_jornada']??0);
        if($horas<=0||$horas>24)return 'Las horas de jornada deben estar entre 1 y 24.';
        if(($data['condicion_especial']??'')==='Sustituto' && (abs($horas-6)>0.001 || ($data['jornada']??'')!=='Especial'))return 'La condición Sustituto requiere jornada especial de 6 horas.';
        if(isset($data['sueldo'])&&(float)$data['sueldo']<0)return 'La remuneración no puede ser negativa.';
        $porcentaje=(float)($data['porcentaje_discapacidad']??0);if($porcentaje<0||$porcentaje>100)return 'El porcentaje de discapacidad debe estar entre 0 y 100.';
        if(!empty($_FILES['foto']['name'])){
            if(($_FILES['foto']['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK)return 'No fue posible recibir la fotografía.';
            if((int)($_FILES['foto']['size']??0)>2*1024*1024)return 'La fotografía supera el límite de 2 MB.';
            $mime=mime_content_type((string)$_FILES['foto']['tmp_name']);if(!in_array($mime,['image/jpeg','image/png','image/webp'],true))return 'La fotografía debe ser JPG, PNG o WEBP.';
        }
        return null;
    }

    private function _procesarFoto(?string $empId): string
    {
        $rutaDefault = 'public/img/default_avatar.png';
        $rutaAnterior = $rutaDefault;

        if (!empty($empId)) {
            $actual = $this->modelo->obtenerPorId((int)$empId);
            if ($actual && !empty($actual['ruta_foto'])) {
                $rutaAnterior = (string)$actual['ruta_foto'];
            }
        }

        // Sin archivo subido
        if (empty($_FILES['foto']['tmp_name'])) {
            return $rutaAnterior;
        }

        $tmp   = $_FILES['foto']['tmp_name'];
        $orig  = $_FILES['foto']['name'];
        $size  = $_FILES['foto']['size'];
        $error = $_FILES['foto']['error'];

        if ($error !== UPLOAD_ERR_OK) return $rutaAnterior;

        // Validar tamaño: máx 2 MB
        if ($size > 2 * 1024 * 1024) return $rutaAnterior;

        // Validar tipo MIME real (no solo extensión)
        $mime = mime_content_type($tmp);
        $tiposPermitidos = ['image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($mime, $tiposPermitidos, true)) return $rutaAnterior;

        // Determinar extensión segura
        $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$mime] ?? 'jpg';

        // Nombre único para evitar colisiones
        $nombreArchivo = 'emp_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $dirDestino    = ROOT . '/public/img/empleados/';
        $rutaDestino   = $dirDestino . $nombreArchivo;

        if (!is_dir($dirDestino)) {
            mkdir($dirDestino, 0755, true);
        }

        if (!move_uploaded_file($tmp, $rutaDestino)) {
            return $rutaAnterior;
        }

        return 'public/img/empleados/' . $nombreArchivo;
    }

    /** Elimina únicamente fotografías administradas por el portal. */
    private function eliminarFotoGestionada(string $rutaRelativa): void
    {
        $rutaNormalizada = str_replace('\\', '/', $rutaRelativa);
        if (!str_starts_with($rutaNormalizada, 'public/img/empleados/')) {
            return;
        }

        $directorio = realpath(ROOT . '/public/img/empleados');
        $archivo = realpath(ROOT . '/' . ltrim($rutaRelativa, '/\\'));
        if ($directorio === false || $archivo === false) {
            return;
        }

        $directorioNormalizado = rtrim(str_replace('\\', '/', $directorio), '/') . '/';
        $archivoNormalizado = str_replace('\\', '/', $archivo);
        if (str_starts_with($archivoNormalizado, $directorioNormalizado) && is_file($archivo)) {
            @unlink($archivo);
        }
    }

    /** POST /talento-humano/empleado/eliminar – Baja atómica vía SP */
    public function eliminar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            ErrorHandler::abort(405);
        }
        Auth::requireCsrf($_POST['_csrf'] ?? null);
        $id    = (int)($_POST['id'] ?? 0);
        $exito = $this->modelo->eliminar($id);

        $msg = $exito ? 'Funcionario dado de baja; estado e historial laboral actualizados.' : 'No se pudo registrar la baja del funcionario.';
        header('Location: ' . BASE_URL . '/talento-humano?msg=' . urlencode($msg) . '&ok=' . ($exito ? '1' : '0'));
        exit;
    }

    /** GET (enlace directo) /talento-humano/empleado/borrar?id=X – Alias de eliminar via GET */
    public function borrar(): void
    {
        ErrorHandler::abort(405);
        header('Allow: POST');
        $msg = 'La baja de empleados requiere una solicitud POST segura.';
        header('Location: ' . BASE_URL . '/talento-humano/directorio?msg=' . urlencode($msg) . '&ok=0');
        exit;
    }

    /**
     * NUEVO: GET /talento-humano/reporte – Historial laboral jerárquico con fusiones
     *
     * Acepta ?cargo=DIRECTOR  para filtrar dinámicamente por nombre de puesto (Anti-SQLi).
     * Acepta ?empleado_id=X   para mostrar el expediente individual de un funcionario.
     * Sin parámetros muestra el reporte completo de todos los funcionarios.
     */
    public function reporte(): void
    {
        $tipoCargo  = isset($_GET['cargo'])       ? trim($_GET['cargo'])       : null;
        $empleadoId = isset($_GET['empleado_id']) ? (int)$_GET['empleado_id'] : null;

        $datos = [
            'historial'    => $this->modelo->obtenerReporteFiltrado($tipoCargo, $empleadoId),
            'jornadasEspeciales' => $this->modelo->obtenerJornadasEspeciales($empleadoId),
            'vigenciasLaborales' => $this->modelo->obtenerVigenciasLaborales($empleadoId),
            'filtro_cargo' => $tipoCargo,
        ];
        // RECTIFICADO: carga la vista historial.php (línea de tiempo cronológica)
        $this->cargarVista('talento-humano', 'historial', $datos);
    }

    /** GET /talento-humano/empleado/perfil/{cedula} – Expediente digital de un funcionario */
    public function perfil(string $cedula): void
    {
        // Sanitizar la cédula: solo dígitos permitidos
        $cedula = preg_replace('/[^0-9]/', '', $cedula);

        if (empty($cedula)) {
            header('Location: ' . BASE_URL . '/talento-humano/directorio?msg=' . urlencode('Cédula inválida') . '&ok=0');
            exit;
        }

        // Buscar datos reales del empleado en la BD
        $empleado = $this->modelo->obtenerPorCedula($cedula);

        if (!$empleado) {
            // Si no se encuentra en BD, mostrar vista igualmente pero con flag de no encontrado
            $datos = [
                'cedula'   => $cedula,
                'empleado' => null,
                'noEncontrado' => true,
            ];
        } else {
            $datos = [
                'cedula'   => $cedula,
                'empleado' => $empleado,
                'historial' => $this->modelo->obtenerReporteFiltrado(null, (int)$empleado['empleado_id']),
                'periodosVinculacion' => $this->modelo->obtenerPeriodosVinculacion((int)$empleado['empleado_id']),
                'antiguedad' => $this->modelo->obtenerAntiguedad((int)$empleado['empleado_id'])[0] ?? null,
                'hitosServicio' => $this->modelo->obtenerHitosServicio((int)InstitutionalClock::today()->format('Y'), (int)$empleado['empleado_id']),
                'nacionalidadesEmpleado' => $this->modelo->obtenerNacionalidadesEmpleado((int)$empleado['empleado_id']),
                'noEncontrado' => false,
            ];
        }

        $this->cargarVista('talento-humano', 'perfil', $datos);
    }

    /**
     * GET /talento-humano/empleado/exportar
     * Exporta el directorio completo de funcionarios en formato CSV (UTF-8 + BOM).
     * El archivo se descarga directamente desde el servidor para garantizar
     * la codificación correcta de caracteres especiales.
     */
    public function exportarCsv(): void
    {
        $empleados = $this->modelo->listarDirectorio();
        $this->modelo->auditarExportacionDirectorio();

        // Headers HTTP para forzar descarga del archivo
        $filename = 'Directorio_Funcionarios_APM_' . date('Ymd_His') . '.csv';
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');

        // BOM UTF-8 para compatibilidad con Excel
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        // Cabecera del CSV
        fputcsv($output, [
            'N.º',
            'Cédula',
            'Apellidos',
            'Nombres',
            'Cargo',
            'Área / Dirección',
            'Tipo de Contrato',
            'Remuneración',
            'Estado',
            'Correo Institucional',
        ], ';');

        foreach ($empleados as $indice => $emp) {
            fputcsv($output, [
                $indice + 1,
                $emp['cedula']               ?? '',
                $emp['apellidos']            ?? '',
                $emp['nombres']              ?? '',
                $emp['cargo']                ?? '',
                $emp['direccion_area']       ?? '',
                $emp['tipo_contrato']        ?? '',
                $emp['remuneracion_mensual'] ?? '',
                ((int)($emp['estado'] ?? 0) === 1) ? 'Activo' : 'Inactivo',
                $emp['correo_institucional'] ?? '',
            ], ';');
        }

        fclose($output);
        exit;
    }


    /**
     * GET /talento-humano/empleado/imprimir-ficha?id=X
     * Valida el ID, consulta el modelo y dispara la generación del PDF.
     * Se abre en pestaña nueva (target="_blank") para no interrumpir la navegación.
     */
    public function movimiento(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $empleado = $this->modelo->obtenerDetalleCompleto($id);
        if (!$empleado || (int)($empleado['estado'] ?? 0) !== 1) {
            header('Location: ' . BASE_URL . '/talento-humano/directorio?modo=movimiento&msg=' . urlencode('El funcionario no está disponible para movimiento.') . '&ok=0');
            exit;
        }
        $this->cargarVista('talento-humano', 'movimiento', [
            'empleado' => $empleado,
            'areas' => $this->modelo->listarAreas(),
        ]);
    }

    public function mover(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            ErrorHandler::abort(405);
        }
        Auth::requireCsrf($_POST['_csrf'] ?? null);
        $resultado = $this->modelo->mover(
            (int)($_POST['empleado_id'] ?? 0),
            (int)($_POST['unidad_destino_id'] ?? 0),
            (string)($_POST['fecha_movimiento'] ?? date('Y-m-d')),
            trim((string)($_POST['motivo'] ?? ''))
        );
        $ok = (int)($resultado['exito'] ?? 0) === 1;
        if ($ok) DraftService::deleteCurrent((string)($_POST['_draft_context'] ?? ''));
        $msg = (string)($resultado['mensaje'] ?? 'No se pudo registrar el movimiento.');
        header('Location: ' . BASE_URL . '/talento-humano/directorio?modo=movimiento&msg=' . urlencode($msg) . '&ok=' . ($ok ? '1' : '0'));
        exit;
    }

    /**
     * GET /talento-humano/empleado/verificar-cedula?cedula=XXXX[&excluir=ID]
     * Endpoint AJAX: verifica en tiempo real si una cédula ya está registrada.
     * Responde JSON {existe, nombre, cargo, area} para validación en el formulario.
     */
    public function verificarCedula(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        $cedula = trim((string)($_GET['cedula'] ?? ''));
        $excluirId = isset($_GET['excluir']) && ctype_digit((string)$_GET['excluir'])
            ? (int)$_GET['excluir']
            : null;

        // Requiere al menos 10 caracteres para consultar (cédulas ecuatorianas = 10)
        if (mb_strlen($cedula) < 10 || !preg_match('/^[0-9A-Za-z]{10,15}$/', $cedula)) {
            echo json_encode(['existe' => false]);
            exit;
        }

        $encontrado = $this->modelo->verificarCedulaExistente($cedula, $excluirId);

        if ($encontrado) {
            echo json_encode([
                'existe' => true,
                'nombre' => trim($encontrado['apellidos'] . ' ' . $encontrado['nombres']),
                'cargo'  => $encontrado['cargo']  ?? '',
                'area'   => $encontrado['direccion_area'] ?? '',
            ], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode(['existe' => false]);
        }
        exit;
    }

    public function buscarPersonal(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        $termino=mb_substr(trim((string)($_GET['q']??'')),0,200);
        $unidad=isset($_GET['unidad'])&&ctype_digit((string)$_GET['unidad'])?(int)$_GET['unidad']:null;
        $contrato=trim((string)($_GET['contrato']??'')) ?: null;
        $estadoRaw=(string)($_GET['estado']??'');
        $estado=$estadoRaw===''?null:(in_array($estadoRaw,['0','1'],true)?(int)$estadoRaw:null);
        $rows=$this->modelo->buscarPersonal($termino,$unidad,$contrato,$estado);
        echo json_encode(['success'=>true,'ids'=>array_map(static fn($r)=>(int)$r['empleado_id'],$rows),'total'=>(int)($rows[0]['total_resultados']??0)],JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function movimientoGrupal(): void
    {
        $ids=array_values(array_unique(array_filter(array_map('intval',explode(',',(string)($_GET['ids']??''))))));
        if(count($ids)<2){header('Location: '.BASE_URL.'/talento-humano/directorio?modo=movimiento&msg='.urlencode('Seleccione al menos dos empleados para el movimiento grupal.').'&ok=0');exit;}
        $seleccion=array_values(array_filter($this->modelo->listarDirectorio(),static fn($e)=>
            in_array((int)($e['empleado_id']??0),$ids,true) && (int)($e['estado']??0)===1));
        if(count($seleccion)!==count($ids)){header('Location: '.BASE_URL.'/talento-humano/directorio?modo=movimiento&msg='.urlencode('La selección contiene empleados no disponibles.').'&ok=0');exit;}
        $this->cargarVista('talento-humano','movimiento_grupal',['seleccion'=>$seleccion,'areas'=>$this->modelo->listarAreas()]);
    }

    public function moverLote(): void
    {
        if(($_SERVER['REQUEST_METHOD']??'GET')!=='POST'){ErrorHandler::abort(405);}
        Auth::requireCsrf($_POST['_csrf']??null);
        $ids=array_values(array_unique(array_filter(array_map('intval',(array)($_POST['empleados']??[])))));
        $fecha=(string)($_POST['fecha_movimiento']??'');$valida=DateTimeImmutable::createFromFormat('Y-m-d',$fecha);
        if(count($ids)<2||!$valida||$valida->format('Y-m-d')!==$fecha){header('Location: '.BASE_URL.'/talento-humano/directorio?modo=movimiento&msg='.urlencode('Revise la selección y la fecha efectiva.').'&ok=0');exit;}
        $resultado=$this->modelo->moverLote($ids,(int)($_POST['unidad_destino_id']??0),$fecha,trim((string)($_POST['motivo']??'')));
        $ok=(int)($resultado['exito']??0)===1;$msg=(string)($resultado['mensaje']??'No se pudo registrar el movimiento grupal.');
        if($ok) DraftService::deleteCurrent((string)($_POST['_draft_context']??''));
        header('Location: '.BASE_URL.'/talento-humano/directorio?modo=movimiento&msg='.urlencode($msg).'&ok='.($ok?'1':'0'));exit;
    }

    public function imprimirFicha(): void
    {
        // 1. Validar que llegue un ID numérico válido
        if (empty($_GET['id']) || !ctype_digit((string)$_GET['id'])) {
            ErrorHandler::abort(400, 'Debe indicar un funcionario válido.');
        }

        $empleadoId = (int)$_GET['id'];

        // El boton verde imprime el primer formulario completo de Biblioteca.
        $datosEmpleado = $this->modelo->obtenerExpedienteImpresion($empleadoId);

        if (!$datosEmpleado) {
            ErrorHandler::abort(404, 'El funcionario no existe o no está disponible en el sistema.');
        }

        require_once ROOT . '/modules/talento-humano/Servicios/PdfFormularioPrincipal.php';
        (new PdfFormularioPrincipal())->generar($datosEmpleado);
        exit;
    }

    /** Entrega desde Biblioteca el mismo APM-TH-FO-001, sin datos. */
    public function formatoPrincipalBlanco(): void
    {
        require_once ROOT . '/modules/talento-humano/Servicios/PdfFormularioPrincipal.php';
        (new PdfFormularioPrincipal())->generar(
            ['_blank'=>true],
            'I',
            'Formulario_Principal_APM-TH-FO-001.pdf'
        );
        exit;
    }

    /**
     * Dibuja la Ficha Integral del Funcionario usando FPDF.
     * Incluye membrete institucional, datos personales, laborales,
     * financieros/bancarios y bloque de firmas para archivo físico.
     *
     * @param array $emp Arreglo asociativo con los datos del funcionario
     */
    private function generarPdfFicha(array $emp): void
    {
        // ── Escudo protector UTF-8 → ISO-8859-1 ──────────────────────────────
        // FPDF trabaja internamente con ISO-8859-1. Cualquier texto con tildes
        // o ñ proveniente de la BD debe ser decodificado antes de entregarse.
        $utf = static fn(string $str): string => mb_convert_encoding($str, 'Windows-1252', 'UTF-8');

        $pdf = new FPDF('P', 'mm', 'A4');
        $pdf->AddPage();
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(true, 15);

        // ======================================================================
        // ENCABEZADO / MEMBRETE INSTITUCIONAL
        // ======================================================================
        $logoPath = ROOT . '/public/img/logoapm.png';
        if (file_exists($logoPath)) {
            @$pdf->Image($logoPath, 15, 12, 25);
            $pdf->SetXY(43, 12);
        } else {
            $pdf->SetXY(15, 12);
        }

        $pdf->SetFont('Arial', 'B', 13);
        $pdf->Cell(0, 7, $utf('AUTORIDAD PORTUARIA DE MANTA'), 0, 1, 'C');
        if (file_exists($logoPath)) { $pdf->SetX(43); } else { $pdf->SetX(15); }
        $pdf->SetFont('Arial', '', 9);
        $pdf->Cell(0, 5, $utf('DIRECCIÓN DE ADMINISTRACIÓN DE TALENTO HUMANO'), 0, 1, 'C');
        $pdf->SetX(15);

        $pdf->SetDrawColor(0, 51, 102);
        $pdf->SetLineWidth(0.8);
        $pdf->Line(15, $pdf->GetY() + 3, 195, $pdf->GetY() + 3);
        $pdf->Ln(8);

        // Título del Reporte
        $pdf->SetFillColor(0, 51, 102);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('Arial', 'B', 13);
        $pdf->Cell(180, 9, $utf('FICHA INTEGRAL DEL FUNCIONARIO'), 1, 1, 'C', true);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Ln(3);

        $pdf->SetFont('Arial', 'I', 8);
        $pdf->Cell(180, 5, $utf('Fecha de Impresión: ' . date('d/m/Y H:i')), 0, 1, 'R');
        $pdf->Ln(3);

        // ======================================================================
        // BLOQUE 1 – INFORMACIÓN PERSONAL
        // ======================================================================
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetFillColor(220, 230, 241);
        $pdf->Cell(180, 6, $utf('  1. INFORMACIÓN PERSONAL'), 0, 1, 'L', true);
        $pdf->Ln(2);

        $pdf->SetFont('Arial', 'B', 9);  $pdf->Cell(40, 6, $utf('Identificación:'), 0, 0);
        $pdf->SetFont('Arial', '', 9);   $pdf->Cell(50, 6, $utf($emp['cedula'] ?? ''), 0, 0);
        $pdf->SetFont('Arial', 'B', 9);  $pdf->Cell(40, 6, $utf('Cargas Familiares:'), 0, 0);
        $pdf->SetFont('Arial', '', 9);   $pdf->Cell(50, 6, $utf($emp['cargas_familiares'] ?? '0'), 0, 1);

        $pdf->SetFont('Arial', 'B', 9);  $pdf->Cell(40, 6, $utf('Apellidos:'), 0, 0);
        $pdf->SetFont('Arial', '', 9);   $pdf->Cell(50, 6, $utf($emp['apellidos'] ?? ''), 0, 0);
        $pdf->SetFont('Arial', 'B', 9);  $pdf->Cell(40, 6, $utf('Nombres:'), 0, 0);
        $pdf->SetFont('Arial', '', 9);   $pdf->Cell(50, 6, $utf($emp['nombres'] ?? ''), 0, 1);
        $pdf->Ln(4);

        // ======================================================================
        // BLOQUE 2 – UBICACIÓN INSTITUCIONAL
        // ======================================================================
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetFillColor(220, 230, 241);
        $pdf->Cell(180, 6, $utf('  2. UBICACIÓN INSTITUCIONAL'), 0, 1, 'L', true);
        $pdf->Ln(2);

        $pdf->SetFont('Arial', 'B', 9);  $pdf->Cell(40, 6, $utf('Dirección / Área:'), 0, 0);
        $pdf->SetFont('Arial', '', 9);   $pdf->Cell(140, 6, $utf($emp['direccion_area'] ?? ''), 0, 1);

        $pdf->SetFont('Arial', 'B', 9);  $pdf->Cell(40, 6, $utf('Puesto / Cargo:'), 0, 0);
        $pdf->SetFont('Arial', '', 9);   $pdf->Cell(140, 6, $utf($emp['cargo'] ?? ''), 0, 1);

        $pdf->SetFont('Arial', 'B', 9);  $pdf->Cell(40, 6, $utf('Correo Inst.:'), 0, 0);
        $pdf->SetFont('Arial', '', 9);   $pdf->Cell(50, 6, $utf($emp['correo_institucional'] ?? ''), 0, 0);
        $pdf->SetFont('Arial', 'B', 9);  $pdf->Cell(40, 6, $utf('Estado Laboral:'), 0, 0);

        $estadoActivo = ((int)($emp['estado'] ?? 0) === 1);
        $estadoTexto  = $estadoActivo ? 'ACTIVO' : 'INACTIVO';
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->SetTextColor($estadoActivo ? 0 : 180, $estadoActivo ? 120 : 0, 0);
        $pdf->Cell(50, 6, $estadoTexto, 0, 1);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Ln(4);

        // ======================================================================
        // BLOQUE 3 – INFORMACIÓN FINANCIERA Y BANCARIA
        // ======================================================================
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetFillColor(220, 230, 241);
        $pdf->Cell(180, 6, $utf('  3. INFORMACIÓN FINANCIERA Y CRÉDITO DE NÓMINA'), 0, 1, 'L', true);
        $pdf->Ln(2);

        $pdf->SetFont('Arial', 'B', 9);  $pdf->Cell(40, 6, $utf('Banco / Entidad:'), 0, 0);
        $pdf->SetFont('Arial', '', 9);   $pdf->Cell(140, 6, $utf($emp['institucion_bancaria'] ?? 'NO REGISTRADO'), 0, 1);

        $pdf->SetFont('Arial', 'B', 9);  $pdf->Cell(40, 6, $utf('Tipo de Cuenta:'), 0, 0);
        $pdf->SetFont('Arial', '', 9);   $pdf->Cell(50, 6, $utf($emp['tipo_cuenta_bancaria'] ?? 'NO REGISTRADO'), 0, 0);
        $pdf->SetFont('Arial', 'B', 9);  $pdf->Cell(40, 6, $utf('N°. de Cuenta:'), 0, 0);
        $pdf->SetFont('Arial', '', 9);   $pdf->Cell(50, 6, $utf($emp['numero_cuenta_bancaria'] ?? 'NO REGISTRADO'), 0, 1);
        $pdf->Ln(20);

        // ======================================================================
        // BLOQUE DE FIRMAS (para archivo físico en expediente)
        // ======================================================================
        $pdf->SetDrawColor(0, 0, 0);
        $pdf->SetLineWidth(0.3);
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->Cell(90, 5, '__________________________________', 0, 0, 'C');
        $pdf->Cell(90, 5, '__________________________________', 0, 1, 'C');

        $pdf->Cell(90, 5, $utf('Firma del Funcionario'), 0, 0, 'C');
        $pdf->Cell(90, 5, $utf('Responsable de Talento Humano'), 0, 1, 'C');

        $pdf->SetFont('Arial', '', 8);
        $pdf->Cell(90, 4, $utf('C.I: ' . ($emp['cedula'] ?? '')), 0, 0, 'C');
        $pdf->Cell(90, 4, $utf('Validación de Expediente'), 0, 1, 'C');

        // Pie de página institucional
        $pdf->Ln(8);
        $pdf->SetDrawColor(0, 51, 102);
        $pdf->SetLineWidth(0.5);
        $pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
        $pdf->Ln(2);
        $pdf->SetFont('Arial', 'I', 7);
        $pdf->SetTextColor(100, 100, 100);
        $pdf->Cell(180, 4, $utf('Documento generado electrónicamente por el Sistema de Gestión de Talento Humano , APM © ' . date('Y')), 0, 1, 'C');

        // Enviar el PDF al navegador (modo inline: visualización directa)
        $nombreArchivo = 'Ficha_' . ($emp['cedula'] ?? 'APM') . '_' . date('Ymd') . '.pdf';
        $pdf->Output('I', $nombreArchivo);
        exit;
    }
}
