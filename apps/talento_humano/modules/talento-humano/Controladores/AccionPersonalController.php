<?php
/**
 * AccionPersonalController.php
 * Módulo: Talento Humano – Fase 2: Acción de Personal (documento legal)
 *
 * Rutas registradas en index.php:
 *   GET  talento-humano/accion-personal                    → index()            (formulario)
 *   POST talento-humano/accion-personal/guardar            → guardar()          (persiste en BD)
 *   GET  talento-humano/accion-personal/ver                → ver()              (muestra documento)
 *   GET  talento-humano/accion-personal/imprimir-accion    → imprimirAccion()   (genera PDF FPDF)
 *   GET  talento-humano/accion-personal/buscar-servidor    → buscarServidor()   (AJAX por ID)
 *   GET  talento-humano/accion-personal/buscar-por-cedula  → buscarPorCedula()  (AJAX por cédula)
 */

class AccionPersonalController extends Controller
{
    private AccionPersonalModel $modelo;
    private EmpleadoModel       $modeloEmp;

    public function __construct()
    {
        require_once ROOT . '/modules/talento-humano/Modelos/AccionPersonalModel.php';
        require_once ROOT . '/modules/talento-humano/Modelos/EmpleadoModel.php';

        $this->modelo    = new AccionPersonalModel();
        $this->modeloEmp = new EmpleadoModel();
    }

    // ─────────────────────────────────────────────────────────────────────────
    /**
     * GET /talento-humano/accion-personal
     * Carga el formulario oficial.
     * Si recibe ?id=X pre-carga la situación actual del empleado desde la BD.
     * El número de acción correlativo se genera automáticamente desde el modelo.
     */
    public function index(): void
    {
        $id     = isset($_GET['id'])     ? (int)$_GET['id']     : null;
        $cedula = isset($_GET['cedula']) ? trim($_GET['cedula']) : null;

        $empleado    = null;
        $historialLab = null;

        if ($id) {
            // Expediente completo (incluye campos financieros/bancarios)
            $empleado = $this->modeloEmp->obtenerDetalleCompleto($id);

            // Historial laboral para la columna "Situación Actual"
            $historialRaw = $this->modeloEmp->obtenerReporteFiltrado(null, $id);
            $historialLab = !empty($historialRaw) ? $historialRaw[0] : null;
        }

        // Secuencial real desde la BD (APM-TH-YYYY-NNN)
        $tipoPreseleccionado=mb_strtoupper(trim((string)($_GET['tipo']??'INGRESO')),'UTF-8');
        $nroAccion = $this->modelo->generarSiguienteSecuencial($tipoPreseleccionado);

        // Catálogos para los <select> de situación propuesta
        $areas   = $this->modeloEmp->listarAreas();
        $cargos  = $this->modeloEmp->listarCargos();

        $datos = [
            'empleado'      => $empleado,
            'historialLab'  => $historialLab,
            'nroAccion'     => $nroAccion,
            'preselCedula'  => $cedula,
            'areas'         => $areas,
            'cargos'        => $cargos,
            'selectorPersonal' => $this->modeloEmp->listarSelectorPersonal(),
            'tipoPreseleccionado' => $tipoPreseleccionado,
        ];

        $this->cargarVista('talento-humano', 'accion_personal', $datos);
    }

    // ─────────────────────────────────────────────────────────────────────────
    /**
     * GET /talento-humano/accion-personal/buscar-servidor?id=X
     * Endpoint JSON consumido por AJAX desde la vista para autocompletar la
     * columna gris "Situación Actual" cuando el operador selecciona un empleado.
     *
     * Responde siempre con Content-Type: application/json.
     */
    public function buscarServidor(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if (empty($_GET['id']) || !ctype_digit((string)$_GET['id'])) {
            echo json_encode(['success' => false, 'message' => 'ID no proporcionado o inválido.']);
            exit;
        }

        $idEmpleado = (int)$_GET['id'];
        $data       = $this->modeloEmp->obtenerDetalleCompleto($idEmpleado);

        if ($data) {
            echo json_encode(['success' => true, 'data' => $data]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Servidor público no encontrado en el sistema.']);
        }
        exit;
    }

    // ─────────────────────────────────────────────────────────────────────────
    /**
     * GET /talento-humano/accion-personal/buscar-por-cedula?cedula=XXXXXXXXXX
     * Endpoint JSON para el buscador de cédula de la vista.
     * Los analistas de RRHH conocen la cédula del funcionario, no el ID interno.
     *
     * Sanea la entrada: solo acepta dígitos (cédula ecuatoriana: 10 dígitos,
     * pasaporte puede ser alfanumérico pero en la APM son cédulas nacionales).
     */
    public function buscarPorCedula(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $cedula = trim($_GET['cedula'] ?? '');

        // Sanear: solo dígitos, longitud entre 5 y 13 caracteres
        if (empty($cedula) || !preg_match('/^\d{5,13}$/', $cedula)) {
            echo json_encode(['success' => false, 'message' => 'Cédula no válida. Ingrese solo dígitos (mínimo 5, máximo 13).']);
            exit;
        }

        $data = $this->modeloEmp->obtenerPorCedula($cedula);

        if ($data) {
            echo json_encode(['success' => true, 'data' => $data]);
        } else {
            echo json_encode(['success' => false, 'message' => "No se encontró ningún funcionario con cédula {$cedula}."]);
        }
        exit;
    }

    // ─────────────────────────────────────────────────────────────────────────
    /**
     * POST /talento-humano/accion-personal/guardar
     * Recibe el formulario, sanea los datos y los persiste mediante el modelo.
     */
    public function guardar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/talento-humano/accion-personal');
            exit;
        }

        Auth::requireCsrf($_POST['_csrf'] ?? null);

        // ── Saneamiento del payload ───────────────────────────────────────
        $payload = [
            // Cabecera del documento
            'numero_accion'            => trim($_POST['numero_accion']      ?? ''),
            'empleado_id'              => (int)($_POST['empleado_id']       ?? 0),
            'tipo_accion'              => trim($_POST['tipo_accion']         ?? ''),
            'modo_captura'             => strtoupper(trim($_POST['modo_captura'] ?? 'CAMBIO_LABORAL')),
            'modalidad_vigencia'       => strtoupper(trim($_POST['modalidad_vigencia'] ?? 'PERMANENTE')),
            'fecha_rige_desde'         => $_POST['rige_desde']              ?? InstitutionalClock::todayIso(),
            'fecha_rige_hasta'         => !empty($_POST['rige_hasta'])
                                            ? $_POST['rige_hasta'] : null,

            // Motivación: si es tipo "OTRO" añade el detalle extra
            'explicacion_legal'        => trim($_POST['motivacion_texto'] ?? ''),
            'detalle_otro'             => trim($_POST['explicacion_otro'] ?? ''),
            'presento_declaracion'     => trim($_POST['presento_declaracion'] ?? 'NO APLICA'),

            // Situación Actual — leídos desde los campos hidden llenados por AJAX
            'actual_unidad_id'         => (int)($_POST['actual_unidad_id_hidden']  ?? $_POST['actual_unidad_id']  ?? 0),
            'actual_puesto_id'         => (int)($_POST['actual_puesto_id_hidden']  ?? $_POST['actual_puesto_id']  ?? 0),
            'actual_lugar_trabajo'     => trim($_POST['actual_lugar_trabajo'] ?? 'Manta'),
            'actual_remuneracion'      => number_format(
                                            (float)($_POST['actual_remuneracion_hidden'] ?? $_POST['actual_remuneracion'] ?? 0),
                                            2, '.', ''),
            'actual_proceso'           => trim($_POST['actual_proceso'] ?? ''),
            'actual_nivel_gestion'     => trim($_POST['actual_nivel_gestion'] ?? ''),
            'actual_grupo_ocupacional' => trim($_POST['actual_grupo_ocupacional'] ?? ''),
            'actual_grado'             => trim($_POST['actual_grado'] ?? ''),
            'actual_partida_presupuestaria' => trim($_POST['actual_partida_presupuestaria'] ?? ''),
            'actual_jornada'           => trim($_POST['actual_jornada'] ?? ''),
            'actual_horas_jornada'     => (float)($_POST['actual_horas_jornada'] ?? 0),
            'actual_tipo_contrato'     => trim($_POST['actual_tipo_contrato'] ?? ''),

            // Situación Propuesta — elegida por el analista en los <select>
            'propuesta_unidad_id'      => (int)($_POST['propuesta_unidad_id'] ?? 0),
            'propuesta_puesto_id'      => (int)($_POST['propuesta_puesto_id'] ?? 0),
            'propuesta_lugar_trabajo'  => trim($_POST['propuesta_lugar_trabajo'] ?? 'Manta'),
            'propuesta_remuneracion'   => number_format(
                                            (float)($_POST['propuesta_remuneracion'] ?? 0), 2, '.', ''),
            'propuesta_proceso'        => trim($_POST['propuesta_proceso'] ?? ''),
            'propuesta_nivel_gestion'  => trim($_POST['propuesta_nivel_gestion'] ?? ''),
            'propuesta_grupo_ocupacional' => trim($_POST['propuesta_grupo_ocupacional'] ?? ''),
            'propuesta_grado'          => trim($_POST['propuesta_grado'] ?? ''),
            'propuesta_partida_presupuestaria' => trim($_POST['propuesta_partida_presupuestaria'] ?? ''),
            'propuesta_jornada'        => trim($_POST['propuesta_jornada'] ?? ''),
            'propuesta_horas_jornada'  => (float)($_POST['propuesta_horas_jornada'] ?? 0),
            'tipo_novedad_jornada'     => trim($_POST['tipo_novedad_jornada'] ?? ''),
            'hora_entrada_propuesta'   => trim($_POST['hora_entrada_propuesta'] ?? ''),
            'hora_salida_propuesta'    => trim($_POST['hora_salida_propuesta'] ?? ''),
            'dias_jornada_propuesta'   => trim($_POST['dias_jornada_propuesta'] ?? ''),
            'documento_jornada'        => trim($_POST['documento_jornada'] ?? ''),
            'propuesta_tipo_contrato'  => trim($_POST['propuesta_contrato'] ?? ''),

            'notificacion_electronica' => isset($_POST['notificacion_electronica']) ? 1 : 0,
            'correo_notificacion'      => trim($_POST['correo_notificacion'] ?? ''),
            'medio_notificacion'       => trim($_POST['medio_notificacion'] ?? ''),
            'documento_notificacion'   => trim($_POST['documento_notificacion'] ?? ''),
            'fecha_notificacion'       => !empty($_POST['fecha_notificacion']) ? str_replace('T',' ',$_POST['fecha_notificacion']) : null,
            'responsable_th_nombre'    => trim($_POST['responsable_th_nombre'] ?? ''),
            'responsable_th_puesto'    => trim($_POST['responsable_th_puesto'] ?? ''),
            'autoridad_nombre'         => trim($_POST['autoridad_nombre'] ?? ''),
            'autoridad_puesto'         => trim($_POST['autoridad_puesto'] ?? ''),
            'elaborador_nombre'        => trim($_POST['elaborador_nombre'] ?? ''),
            'elaborador_puesto'        => trim($_POST['elaborador_puesto'] ?? ''),
            'revisor_nombre'           => trim($_POST['revisor_nombre'] ?? ''),
            'revisor_puesto'           => trim($_POST['revisor_puesto'] ?? ''),
            'registrador_nombre'       => trim($_POST['registrador_nombre'] ?? ''),
            'registrador_puesto'       => trim($_POST['registrador_puesto'] ?? ''),
            'notificador_nombre'       => trim($_POST['notificador_nombre'] ?? ''),
            'notificador_puesto'       => trim($_POST['notificador_puesto'] ?? ''),

            // Auditoría — reemplazar con sesión real cuando esté disponible
            'usuario_crea'             => Auth::username(),
            'direccion_ip'             => Auth::clientIp(),
        ];

        $tiposOficiales = [
            'INGRESO','REINGRESO','RESTITUCIÓN','REINTEGRO','ASCENSO','TRASLADO','SANCIONES','TRASPASO',
            'CAMBIO ADMINISTRATIVO','INTERCAMBIO VOLUNTARIO','LICENCIA','COMISIÓN DE SERVICIOS','INCREMENTO RMU',
            'SUBROGACIÓN','ENCARGO','CESACIÓN DE FUNCIONES','DESTITUCIÓN','VACACIONES','REVISIÓN CLASIFICACIÓN PUESTO','OTRO (DETALLAR)'
        ];
        $empleadoActual = $this->modeloEmp->obtenerDetalleCompleto($payload['empleado_id']);
        if ($empleadoActual) {
            $payload['actual_unidad_id'] = (int)($empleadoActual['unidad_id'] ?? 0);
            $payload['actual_puesto_id'] = (int)($empleadoActual['puesto_id'] ?? 0);
            $payload['actual_remuneracion'] = number_format((float)($empleadoActual['remuneracion_mensual'] ?? 0),2,'.','');
            $payload['actual_proceso'] = trim((string)($empleadoActual['proceso_institucional'] ?? ''));
            $payload['actual_nivel_gestion'] = trim((string)($empleadoActual['nivel_gestion'] ?? ''));
            $payload['actual_lugar_trabajo'] = trim((string)($empleadoActual['lugar_trabajo'] ?? 'Manta'));
            $payload['actual_grupo_ocupacional'] = trim((string)($empleadoActual['grupo_ocupacional'] ?? ''));
            $payload['actual_grado'] = trim((string)($empleadoActual['grado_laboral'] ?? ''));
            $payload['actual_partida_presupuestaria'] = trim((string)($empleadoActual['partida_individual'] ?? ''));
            $payload['actual_jornada'] = trim((string)($empleadoActual['jornada'] ?? 'Completa'));
            $payload['actual_horas_jornada'] = (float)($empleadoActual['horas_jornada'] ?? 8);
            $payload['actual_tipo_contrato'] = trim((string)($empleadoActual['tipo_contrato'] ?? ''));
        }

        // ── Validación mínima antes de persistir ──────────────────────────
        $fechaDesde = DateTimeImmutable::createFromFormat('Y-m-d',(string)$payload['fecha_rige_desde']);
        $fechaHasta = $payload['fecha_rige_hasta'] ? DateTimeImmutable::createFromFormat('Y-m-d',(string)$payload['fecha_rige_hasta']) : null;
        $modalidadValida = in_array($payload['modalidad_vigencia'],['PERMANENTE','TEMPORAL'],true);
        $modoValido = in_array($payload['modo_captura'],['CAMBIO_LABORAL','JORNADA_TEMPORAL'],true);
        $vigenciaValida = $fechaDesde && ($payload['modalidad_vigencia']==='PERMANENTE'
            ? $fechaHasta===null
            : ($fechaHasta!==null && $fechaHasta >= $fechaDesde));
        $esLicenciaParental = in_array(strtoupper($payload['tipo_novedad_jornada']),['MATERNIDAD','PATERNIDAD'],true);
        $jornadaValida = $payload['tipo_novedad_jornada']===''
            || ($payload['modalidad_vigencia']==='TEMPORAL' && $fechaHasta
                && (($esLicenciaParental && $payload['propuesta_jornada']==='Licencia' && abs($payload['propuesta_horas_jornada'])<0.001)
                    || (!$esLicenciaParental && $payload['propuesta_horas_jornada']>0 && $payload['propuesta_horas_jornada']<=24)));
        $capturaValida = $payload['modo_captura']!=='JORNADA_TEMPORAL'
            || ($payload['modalidad_vigencia']==='TEMPORAL' && $payload['tipo_novedad_jornada']!=='');
        $vacacionValida=$payload['tipo_accion']!=='VACACIONES' || ($payload['modalidad_vigencia']==='TEMPORAL' && $fechaHasta!==null);
        if (!$empleadoActual || !in_array($payload['tipo_accion'],$tiposOficiales,true) || empty($payload['explicacion_legal'])
            || !$fechaDesde || !$modalidadValida || !$modoValido || !$vigenciaValida || !$capturaValida
            || (str_starts_with($payload['tipo_accion'],'OTRO') && empty($payload['detalle_otro']))
            || !$jornadaValida || !$vacacionValida) {
            $msg = 'Datos incompletos: revise empleado, modalidad, fechas, tipo de acción, jornada y motivación legal.';
            header('Location: ' . BASE_URL . '/talento-humano/accion-personal?msg=' . urlencode($msg) . '&ok=0');
            exit;
        }

        // ── Persistencia en BD ────────────────────────────────────────────
        $nroAsignado = $this->modelo->registrarAccion($payload);
        $exito = $nroAsignado !== null;
        if ($exito) DraftService::deleteCurrent((string)($_POST['_draft_context'] ?? ''));
        $nro   = $nroAsignado ?? '';

        $msg = $exito
            ? "Acción de Personal {$nro} guardada como borrador para revisión."
            : 'Error al guardar la Acción de Personal. La incidencia fue registrada en el log del sistema.';

        header('Location: ' . BASE_URL . '/talento-humano/directorio?msg=' . urlencode($msg) . '&ok=' . ($exito ? '1' : '0'));
        exit;
    }

    public function aprobar(): void
    {
        $this->resolverAccion('aprobar');
    }

    public function crearUnidadRapida(): void
    {
        $this->crearCatalogoRapido('unidad');
    }

    public function crearPuestoRapido(): void
    {
        $this->crearCatalogoRapido('puesto');
    }

    private function crearCatalogoRapido(string $tipo): void
    {
        Auth::requirePermission('maestros', 'crear');
        header('Content-Type: application/json; charset=utf-8');
        if(($_SERVER['REQUEST_METHOD']??'GET')!=='POST'){http_response_code(405);echo json_encode(['success'=>false,'message'=>'Metodo no permitido.']);exit;}
        Auth::requireCsrf($_POST['_csrf']??null);
        $nombre=trim((string)($_POST['nombre']??''));
        if(mb_strlen($nombre)<3||mb_strlen($nombre)>150){http_response_code(422);echo json_encode(['success'=>false,'message'=>'Ingrese una denominacion entre 3 y 150 caracteres.']);exit;}
        require_once ROOT.'/modules/admin/Modelos/MaestroModel.php';
        $maestro=new MaestroModel();
        if($tipo==='unidad'){
            $procesosPermitidos=Catalogos::TIPOS_PROCESO;
            $proceso=trim((string)($_POST['tipo_proceso']??'Apoyo'));
            if(!in_array($proceso,$procesosPermitidos,true)){http_response_code(422);echo json_encode(['success'=>false,'message'=>'Seleccione un tipo de proceso valido.']);exit;}
            $result=$maestro->guardarUnidad(['nombre_unidad'=>$nombre,'tipo_proceso'=>$proceso,'unidad_padre_id'=>(int)($_POST['unidad_padre_id']??0),'activo'=>1]);
        }else{
            $rmu=(float)($_POST['remuneracion_unificada']??0);
            if($rmu<0||$rmu>9999999.99){http_response_code(422);echo json_encode(['success'=>false,'message'=>'La RMU debe estar entre 0 y 9.999.999,99.']);exit;}
            $result=$maestro->guardarPuesto(['nombre_puesto'=>$nombre,'remuneracion_unificada'=>$rmu,'activo'=>1]);
        }
        $ok=(int)($result['exito']??0)===1;if(!$ok)http_response_code(422);
        echo json_encode(['success'=>$ok,'id'=>(int)($result['id']??0),'nombre'=>mb_strtoupper($nombre,'UTF-8'),'rmu'=>$tipo==='puesto'?($rmu??0):null,'message'=>(string)($result['mensaje']??'Operacion finalizada.')],JSON_UNESCAPED_UNICODE);exit;
    }

    public function anular(): void
    {
        $this->resolverAccion('anular');
    }

    private function resolverAccion(string $accion): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            ErrorHandler::abort(405);
        }
        Auth::requireCsrf($_POST['_csrf'] ?? null);
        $id = (int)($_POST['accion_id'] ?? 0);
        $resultado = $accion === 'aprobar'
            ? $this->modelo->aprobar($id)
            : $this->modelo->anular($id, trim((string)($_POST['motivo'] ?? '')));
        $ok = (int)($resultado['exito'] ?? 0) === 1;
        $msg = (string)($resultado['mensaje'] ?? 'No fue posible procesar la accion.');
        header('Location: '.BASE_URL.'/talento-humano/biblioteca?ok='.($ok?'1':'0').'&msg='.urlencode($msg));
        exit;
    }

    // ─────────────────────────────────────────────────────────────────────────
    /**
     * GET /talento-humano/accion-personal/ver?id=X
     * Muestra el documento guardado de una Acción de Personal.
     */
    public function ver(): void
    {
        $id    = (int)($_GET['id'] ?? 0);

        if ($id <= 0) {
            header('Location: ' . BASE_URL . '/talento-humano/directorio?msg=' . urlencode('ID de acción inválido') . '&ok=0');
            exit;
        }

        // Obtener el documento cruzado (incluye datos del empleado y puestos)
        $accion = $this->modelo->obtenerAccionCruzada($id);

        if (!$accion) {
            header('Location: ' . BASE_URL . '/talento-humano/directorio?msg=' . urlencode('La Acción de Personal no fue encontrada en el sistema') . '&ok=0');
            exit;
        }

        $datos = [
            'accion_id' => $id,
            'accion'    => $accion,
        ];

        // Cargar vista de SOLO LECTURA del documento (no el formulario de creación)
        $this->cargarVista('talento-humano', 'ver_accion', $datos);
    }

    // ─────────────────────────────────────────────────────────────────────────
    /**
     * GET /talento-humano/accion-personal/imprimir-accion?id=X
     * Descarga el PDF oficial de la Acción de Personal usando FPDF.
     * Abre en una nueva pestaña (target="_blank") sin interrumpir la navegación.
     */
    public function imprimirAccion(): void
    {
        $id = (int)($_GET['id'] ?? 0);

        if ($id <= 0) {
            ErrorHandler::abort(400, 'Debe especificar un número de acción válido.');
        }

        $datos = $this->modelo->obtenerAccionCruzada($id);

        if (!$datos) {
            ErrorHandler::abort(404, 'El documento de Acción de Personal no existe o fue eliminado.');
        }

        $this->modelo->auditarImpresion($id);
        require_once ROOT . '/libs/fpdf/fpdf.php';
        $this->generarPdfAccionOficial($datos);
    }

    public function formatoBlanco(): void
    {
        require_once ROOT . '/libs/fpdf/fpdf.php';
        $this->generarPdfAccionOficial(['_blank'=>true]);
    }


    // ─────────────────────────────────────────────────────────────────────────
    /**
     * Motor FPDF – formato oficial de 2 páginas del MRL.
     * Usa coordenadas absolutas (SetXY) y celdas con bordes perimetrales
     * para garantizar que las cajas queden unidas y los casilleros alineados.
     *
     * @param array $d  Fila cruzada devuelta por obtenerAccionCruzada()
     */
    private function generarPdfAccionOficial(array $d, string $destino='I', ?string $archivo=null): void
    {
        // ── Helpers ────────────────────────────────────────────────────────
        $utf = static fn($s): string => mb_convert_encoding((string)($s ?? ''), 'Windows-1252', 'UTF-8');

        // Traducción de meses al español para date()
        $meses = [
            'January'=>'ENERO','February'=>'FEBRERO','March'=>'MARZO',
            'April'=>'ABRIL','May'=>'MAYO','June'=>'JUNIO',
            'July'=>'JULIO','August'=>'AGOSTO','September'=>'SEPTIEMBRE',
            'October'=>'OCTUBRE','November'=>'NOVIEMBRE','December'=>'DICIEMBRE',
        ];
        $fmtFecha = function(?string $ts) use ($meses): string {
            if (empty($ts)) return '';
            $en = date('d \D\E F \D\E\L Y', strtotime($ts));
            return str_replace(array_keys($meses), array_values($meses), $en);
        };
        $fmtDMY = fn(?string $ts) => empty($ts) ? '' : date('d-m-Y', strtotime($ts));

        // ── Datos ──────────────────────────────────────────────────────────
        $logoPath  = ROOT . '/public/img/logoapm.png';
        $vacio = !empty($d['_blank']);
        $nroAccion = $d['numero_accion']  ?? '';
        $cedula    = $d['identificacion'] ?? '';
        $apellidos = $utf(strtoupper($d['apellidos'] ?? ''));
        $nombres   = $utf(strtoupper($d['nombres']   ?? ''));
        $servidor  = $utf(strtoupper(($d['apellidos'] ?? '') . ' ' . ($d['nombres'] ?? '')));
        $tipoRaw   = strtoupper(trim($d['tipo_accion'] ?? ''));

        $fechaElab  = $fmtFecha($d['fecha_elaboracion'] ?? null);
        $fechaDesde = $fmtDMY($d['fecha_rige_desde']    ?? null);
        $fechaHasta = !empty($d['fecha_rige_hasta'])
            ? $fmtDMY($d['fecha_rige_hasta'])
            : ($vacio ? '' : 'PERMANENTE');

        // Helper checkbox: dibuja una celda con borde y 'X' si corresponde al tipo
        $chk = function(FPDF $p, string $etiqueta) use ($tipoRaw, $utf): void {
            // Normalización sin tildes
            $norm = strtr(strtoupper($etiqueta),
                          ['Á'=>'A','É'=>'E','Í'=>'I','Ó'=>'O','Ú'=>'U','Ñ'=>'N']);
            $t2   = strtr($tipoRaw,
                          ['Á'=>'A','É'=>'E','Í'=>'I','Ó'=>'O','Ú'=>'U','Ñ'=>'N']);
            // Coincidencia exacta O que el tipo guardado empiece igual (7 chars)
            $marcado = ($norm === $t2 ||
                        ($norm !== '' && strpos($t2, substr($norm, 0, min(7, strlen($norm)))) !== false));
            $p->SetFont('Arial', 'B', 8);
            $p->Cell(5, 4, $marcado ? 'X' : '', 1, 0, 'C');
        };

        // ════════════════════════════════════════════════════════════════════
        // PÁGINA 1
        // ════════════════════════════════════════════════════════════════════
        $pdf = new FPDF('P', 'mm', 'A4');
        $pdf->SetMargins(10, 10, 10);
        $pdf->SetAutoPageBreak(false);
        $pdf->AddPage();

        // ── CABECERA ─────────────────────────────────────────────────────────
        // Rectángulo del logo/nombre (izquierda)
        $pdf->Rect(10, 10, 95, 18);
        if (file_exists($logoPath)) {
            $pdf->Image($logoPath, 12, 11, 22);
        }
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->SetXY(35, 12);
        $pdf->Cell(68, 5, 'AUTORIDAD', 0, 2, 'L');
        $pdf->SetX(35);
        $pdf->Cell(68, 5, 'PORTUARIA', 0, 2, 'L');
        $pdf->SetX(35);
        $pdf->Cell(68, 5, 'DE MANTA', 0, 2, 'L');

        // Caja título (derecha)
        $pdf->SetXY(105, 10);
        $pdf->SetFont('Arial', 'B', 13);
        $pdf->Cell(95, 8, $utf('ACCIÓN DE PERSONAL'), 1, 1, 'C');

        // Nro
        $pdf->SetXY(105, 18);
        $pdf->SetFont('Arial', '', 8);
        $pdf->Cell(20, 5, 'Nro.', 1, 0, 'C');
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->Cell(75, 5, $nroAccion, 1, 1, 'C');

        // Fecha elaboración
        $pdf->SetX(105);
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell(95, 5, $utf('FECHA DE ELABORACIÓN'), 1, 1, 'C');
        $pdf->SetX(105);
        $pdf->SetFont('Arial', '', 9);
        $pdf->Cell(95, 5, $utf($fechaElab), 1, 1, 'C');

        // ── FILA: APELLIDOS / NOMBRES / RIGE ────────────────────────────────
        $pdf->SetXY(10, 33);
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell(95, 5, 'APELLIDOS', 1, 0, 'C');
        $pdf->Cell(95, 5, 'NOMBRES',   1, 1, 'C');

        $pdf->SetFont('Arial', '', 9);
        $pdf->Cell(95, 6, $apellidos, 1, 0, 'C');
        $pdf->Cell(95, 6, $nombres,   1, 1, 'C');

        // Sub-fila doc + nro + RIGE (etiquetas)
        $pdf->SetFont('Arial', 'B', 5);
        $pdf->Cell(47.5, 5, $utf('DOCUMENTO DE IDENTIFICACIÓN'), 1, 0, 'C');
        $pdf->Cell(47.5, 5, $utf('NRO. DE IDENTIFICACIÓN'),      1, 0, 'C');
        $pdf->Cell(47.5, 5, 'DESDE (dd-mm-aaaa)',                 1, 0, 'C');
        $pdf->Cell(47.5, 5, 'HASTA (dd-mm-aaaa) (cuando aplica)', 1, 1, 'C');

        // Valores
        $pdf->SetFont('Arial', '', 8);
        $pdf->Cell(47.5, 6, $utf('CÉDULA'),  1, 0, 'C');
        $pdf->Cell(47.5, 6, $cedula,         1, 0, 'C');
        $pdf->Cell(47.5, 6, $fechaDesde,     1, 0, 'C');
        $pdf->Cell(47.5, 6, $fechaHasta,     1, 1, 'C');

        // ── GRID CHECKBOXES 21 tipos de acción ──────────────────────────────
        $pdf->SetXY(10, 65);
        $pdf->SetFont('Arial', 'B', 5);
        $pdf->Cell(190, 5,
            $utf('Escoja una opción (según lo estipulado en el artículo 21 del Reglamento General a la Ley Orgánica del Servicio Público)'),
            'LTR', 1, 'L');

        // 4 columnas × 7 filas
        $col1 = ['INGRESO','REINGRESO','RESTITUCIÓN','REINTEGRO','ASCENSO','TRASLADO','SANCIONES'];
        $col2 = ['TRASPASO','CAMBIO ADMINISTRATIVO','INTERCAMBIO VOLUNTARIO','LICENCIA','COMISIÓN DE SERVICIOS','',''];
        $col3 = ['INCREMENTO RMU','SUBROGACIÓN','ENCARGO','CESACIÓN DE FUNCIONES','DESTITUCIÓN','VACACIONES',''];
        $col4 = ['REVISIÓN CLASI. PUESTO','OTRO (DETALLAR)','','','','',''];

        $startChk = $pdf->GetY();
        $rowH = 4;
        $colW = 47.5;   // 190 / 4

        for ($r = 0; $r < 7; $r++) {
            $items = [
                ['x' => 10,             'label' => $col1[$r]],
                ['x' => 10 + $colW,     'label' => $col2[$r]],
                ['x' => 10 + $colW * 2, 'label' => $col3[$r]],
                ['x' => 10 + $colW * 3, 'label' => $col4[$r]],
            ];
            foreach ($items as $it) {
                $pdf->SetXY($it['x'], $startChk + $r * $rowH);
                $pdf->SetFont('Arial', '', 7);
                $pdf->Cell($colW - 6, $rowH, $utf($it['label']), 0, 0, 'L');
                if ($it['label'] !== '') {
                    $chk($pdf, $it['label']);
                }
            }
        }
        // Rectángulo + líneas divisorias del grid
        $gridH = 7 * $rowH;
        $pdf->Rect(10, $startChk, 190, $gridH);
        for ($c = 1; $c < 4; $c++) {
            $pdf->Line(10 + $colW * $c, $startChk, 10 + $colW * $c, $startChk + $gridH);
        }

        // ── FILA DECLARACIÓN JURADA ──────────────────────────────────────────
        $yDJ = $startChk + $gridH;
        $pdf->SetXY(10, $yDJ);
        $pdf->SetFont('Arial', 'B', 5);
        $pdf->Cell(80, 5,
            $utf('EN CASO DE REQUERIR ESPECIFICACIÓN: ') . $utf($d['tipo_accion'] ?? ''),
            'LTB', 0, 'L');
        $pdf->Cell(75, 5,
            $utf('* PRESENTÓ LA DECLARACIÓN JURADA (número 2 del art. 3 RLOSEP)'),
            'LTB', 0, 'L');
        $pdf->Cell(8,  5, 'SI',       'LTB', 0, 'C');
        $declaracion = strtoupper((string)($d['presento_declaracion'] ?? ''));
        $pdf->Cell(5,  5, $declaracion === 'SI' ? 'X' : '', 1, 0, 'C');
        $pdf->Cell(15, 5, 'NO APLICA','TB', 0, 'C');
        $pdf->Cell(5,  5, $declaracion === 'NO APLICA' ? 'X' : '', 1, 0, 'C');
        $pdf->Cell(2,  5, '',         'RTB', 1);

        // ── MOTIVACIÓN ───────────────────────────────────────────────────────
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell(190, 5, $utf('MOTIVACIÓN: (adjuntar anexo si lo posee)'), 'LTR', 1, 'L');
        $pdf->SetFont('Arial', '', 8);
        $yMotStart = $pdf->GetY();
        $motivacion=(string)($d['explicacion_legal'] ?? '');
        if(!empty($d['tipo_novedad_jornada'])){
            $motivacion.="\nJornada temporal: {$d['tipo_novedad_jornada']} | ".($d['propuesta_horas_jornada']??'').' horas diarias | '.($d['fecha_rige_desde']??'').' a '.($d['fecha_rige_hasta']??'').'. Horario: '.($d['hora_entrada_propuesta']??'').' - '.($d['hora_salida_propuesta']??'').'. Documento: '.($d['documento_jornada']??'').'.';
        }
        $pdf->MultiCell(190, 4, $utf($motivacion), 'LR', 'J');
        $yMotEnd = $pdf->GetY();
        // Mínimo 20 mm de caja
        if (($yMotEnd - $yMotStart) < 20) {
            $pdf->Cell(190, 20 - ($yMotEnd - $yMotStart), '', 'LR', 1);
            $yMotEnd = $pdf->GetY();
        }
        $pdf->SetXY(10, $yMotEnd);
        $pdf->Cell(190, 0, '', 'T', 1);

        // ── TABLA DOBLE COLUMNA ──────────────────────────────────────────────
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell(95, 5, 'SITUACION ACTUAL',    1, 0, 'C');
        $pdf->Cell(95, 5, 'SITUACION PROPUESTA',  1, 1, 'C');

        $dataY = $pdf->GetY();
        $lX    = 10;
        $rX    = 105;

        $filas = [
            ['lab' => 'PROCESO INSTITUCIONAL:',
             'act'  => $utf($d['actual_proceso'] ?? ''),
             'prop' => $utf($d['propuesta_proceso'] ?? '')],
            ['lab' => 'NIVEL DE GESTION:',
             'act'  => $utf($d['actual_nivel_gestion'] ?? ''), 'prop' => $utf($d['propuesta_nivel_gestion'] ?? '')],
            ['lab' => 'UNIDAD ADMINISTRATIVA:',
             'act'  => $utf($d['actual_area'] ?? ''),
             'prop' => $utf($d['propuesta_area'] ?? '')],
            ['lab' => 'LUGAR DE TRABAJO:',
             'act'  => $utf($d['actual_lugar_trabajo'] ?? 'MANTA'),
             'prop' => $utf($d['propuesta_lugar_trabajo'] ?? 'MANTA')],
            ['lab' => 'DENOMINACION DEL PUESTO:',
             'act'  => $utf($d['actual_cargo'] ?? ''),
             'prop' => $utf($d['propuesta_cargo'] ?? '')],
            ['lab' => 'GRUPO OCUPACIONAL:',
             'act'  => $utf($d['actual_grupo_ocupacional'] ?? ''), 'prop' => $utf($d['propuesta_grupo_ocupacional'] ?? '')],
            ['lab' => 'GRADO:',
             'act'  => $utf($d['actual_grado'] ?? ''), 'prop' => $utf($d['propuesta_grado'] ?? '')],
            ['lab' => 'REMUNERACION MENSUAL:',
             'act'  => '$ ' . number_format((float)($d['actual_remuneracion']    ?? 0), 2),
             'prop' => '$ ' . number_format((float)($d['propuesta_remuneracion'] ?? 0), 2)],
            ['lab' => 'PARTIDA INDIVIDUAL:',
             'act'  => $utf($d['actual_partida_presupuestaria'] ?? ''), 'prop' => $utf($d['propuesta_partida_presupuestaria'] ?? '')],
        ];

        // Columna Izquierda — flujo normal (ln=1)
        foreach ($filas as $f) {
            $pdf->SetFont('Arial', 'B', 5.8);
            $pdf->Cell(95, 2.8, $f['lab'], 'LR', 1, 'L');
            $pdf->SetFont('Arial', '', 5.8);
            $pdf->Cell(95, 3.5, $f['act'],  'LR', 1, 'L');
        }
        $pdf->SetXY($lX, $pdf->GetY());
        $pdf->Cell(95, 0, '', 'T', 0);
        $endYLeft = $pdf->GetY();

        // Columna Derecha — SetXY + ln=2 para avanzar solo el cursor vertical
        $pdf->SetXY($rX, $dataY);
        foreach ($filas as $f) {
            $pdf->SetFont('Arial', 'B', 5.8);
            $pdf->Cell(95, 2.8, $f['lab'],  'LR', 2, 'L');
            $pdf->SetX($rX);
            $pdf->SetFont('Arial', '', 5.8);
            $pdf->Cell(95, 3.5, $f['prop'], 'LR', 2, 'L');
            $pdf->SetX($rX);
        }
        $pdf->SetXY($rX, $pdf->GetY());
        $pdf->Cell(95, 0, '', 'T', 1);

        // Sincronizar Y
        $pdf->SetY(max($endYLeft, $pdf->GetY()));

        // ── POSESIÓN DEL PUESTO ──────────────────────────────────────────────
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell(190, 5, $utf('POSESIÓN DEL PUESTO'), 1, 1, 'C');
        $yPosInicio = $pdf->GetY();
        $pdf->Cell(190, 25, '', 1, 0);
        $yPosBox = $yPosInicio + 1;
        $pdf->SetXY(12, $yPosBox);
        $pdf->SetFont('Arial', '', 7);
        $pdf->Cell(125, 5, 'YO, ' . $servidor, 0, 2);
        $pdf->SetX(12);
        $pdf->Cell(125, 5, 'JURO LEALTAD AL ESTADO ECUATORIANO.', 0, 2);
        $pdf->SetX(12);
        $pdf->Cell(125, 5, $utf('LUGAR: MANTA          FECHA: ') . $fechaDesde, 0, 2);
        $pdf->SetXY(140, $yPosBox);
        $pdf->Cell(60, 5, 'CON NRO. DOC.: ' . $cedula, 0, 1);

        $pdf->SetXY(10, $yPosInicio + 25);
        $pdf->SetFont('Arial', '', 7);
        $pdf->Cell(95, 5, 'NRO. ACTA FINAL', 1, 0, 'C');
        $pdf->Cell(95, 5, $utf('FIRMA SERVIDOR PÚBLICO'), 1, 1, 'C');
        $pdf->Cell(95, 5, '', 1, 0, 'C');
        $pdf->Cell(95, 5, 'FECHA', 1, 1, 'C');

        // ── RESPONSABLES DE APROBACIÓN ───────────────────────────────────────
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell(190, 5, $utf('RESPONSABLES DE APROBACIÓN'), 1, 1, 'C');
        $pdf->Cell(95, 5, $utf('DIRECTOR (A) O RESPONSABLE DE TALENTO HUMANO'), 1, 0, 'C');
        $pdf->Cell(95, 5, 'AUTORIDAD NOMINADORA O SU DELEGADO', 1, 1, 'C');
        $pdf->Cell(95, 20, '', 1, 0);
        $pdf->Cell(95, 20, '', 1, 1);
        $pdf->SetFont('Arial', '', 7);
        $pdf->Cell(95, 4, 'FIRMA: _____________________________', 1, 0, 'L');
        $pdf->Cell(95, 4, 'FIRMA: _____________________________', 1, 1, 'L');
        $pdf->Cell(95, 4, $utf('NOMBRE: '.($d['responsable_th_nombre'] ?? '')), 1, 0, 'L');
        $pdf->Cell(95, 4, $utf('NOMBRE: '.($d['autoridad_nombre'] ?? '')), 1, 1, 'L');
        $pdf->Cell(95, 4, $utf('PUESTO: '.($d['responsable_th_puesto'] ?? '')), 1, 0, 'L');
        $pdf->Cell(95, 4, $utf('PUESTO: '.($d['autoridad_puesto'] ?? '')), 1, 1, 'L');

        // Pie página 1
        $pdf->SetFont('Arial', 'I', 6);
        $pdf->SetXY(10, 282);
        $pdf->Cell(95,  4, 'Elaborado por el Ministerio del Trabajo', 0, 0, 'L');
        $pdf->Cell(95,  4, $utf('Fecha actualizacion: 2024-08-23  /  Versión 01.1  /  Página 1 de 2'), 0, 1, 'R');

        // ════════════════════════════════════════════════════════════════════
        // PÁGINA 2
        // ════════════════════════════════════════════════════════════════════
        $pdf->AddPage();
        $pdf->SetMargins(10, 10, 10);

        // ── RESPONSABLES DE FIRMAS ───────────────────────────────────────────
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell(190, 5, 'RESPONSABLES DE FIRMAS', 1, 1, 'C');

        $pdf->Cell(107, 5, $utf('ACEPTACIÓN Y/O RECEPCIÓN DEL SERVIDOR PÚBLICO'), 1, 0, 'C');
        $pdf->Cell(83,  5, $utf('EN CASO DE NEGATIVA DE LA RECEPCIÓN (TESTIGO)'),  1, 1, 'C');
        $pdf->Cell(107, 30, '', 1, 0);
        $pdf->Cell(83,  30, '', 1, 1);

        $pdf->SetFont('Arial', 'B', 7);
        $pdf->Cell(10, 4, 'FIRMA:',  1, 0, 'L'); $pdf->SetFont('Arial','',7);
        $pdf->Cell(97, 4, '',        1, 0);
        $pdf->SetFont('Arial', 'B', 7);
        $pdf->Cell(10, 4, 'FIRMA:',  1, 0, 'L');
        $pdf->Cell(73, 4, '',        1, 1);

        $pdf->SetFont('Arial', 'B', 7);
        $pdf->Cell(12, 4, 'NOMBRE:', 1, 0, 'L'); $pdf->SetFont('Arial','',7);
        $pdf->Cell(95, 4, $servidor, 1, 0, 'L');
        $pdf->SetFont('Arial', 'B', 7);
        $pdf->Cell(12, 4, 'NOMBRE:', 1, 0, 'L');
        $pdf->Cell(71, 4, '',        1, 1);

        $pdf->SetFont('Arial', 'B', 7);
        $pdf->Cell(10, 4, 'FECHA:',  1, 0, 'L'); $pdf->SetFont('Arial','',7);
        $pdf->Cell(97, 4, $utf($fechaElab), 1, 0, 'C');
        $pdf->SetFont('Arial', 'B', 7);
        $pdf->Cell(10, 4, 'FECHA:',  1, 0, 'L');
        $pdf->Cell(73, 4, '',        1, 1);

        $pdf->SetFont('Arial', 'B', 7);
        $pdf->Cell(10, 8, 'HORA:',   1, 0, 'L'); $pdf->SetFont('Arial','',7);
        $pdf->Cell(97, 8, '',        1, 0);
        $pdf->SetFont('Arial', 'B', 7);
        $pdf->Cell(12, 8, $utf('RAZÓN:'), 1, 0, 'L');
        $xRazon=$pdf->GetX();$yRazon=$pdf->GetY();
        $pdf->Cell(71,8,'',1,1);
        $pdf->SetXY($xRazon+1,$yRazon+.8);$pdf->SetFont('Arial','',4.4);
        $pdf->MultiCell(69,2.1,$utf('En presencia del testigo se deja constancia de que la o el servidor público tiene la negativa de recibir la comunicación de registro de esta acción de personal.'),0,'C');
        $pdf->SetY($yRazon+8);

        // ── ELABORACIÓN / REVISIÓN / CONTROL ─────────────────────────────────
        $colF = 190 / 3;
        $pdf->SetFont('Arial', 'B', 7);
        $pdf->Cell($colF, 5, $utf('RESPONSABLE DE ELABORACIÓN'),   1, 0, 'C');
        $pdf->Cell($colF, 5, $utf('RESPONSABLE DE REVISIÓN'),      1, 0, 'C');
        $pdf->Cell($colF, 5, $utf('RESPONSABLE DE REGISTRO Y CONTROL'), 1, 1, 'C');
        $pdf->Cell($colF, 25, '', 1, 0);
        $pdf->Cell($colF, 25, '', 1, 0);
        $pdf->Cell($colF, 25, '', 1, 1);

        $resp3 = [
            ['nb' => $d['elaborador_nombre'] ?? '', 'pu' => $d['elaborador_puesto'] ?? ''],
            ['nb' => $d['revisor_nombre'] ?? '', 'pu' => $d['revisor_puesto'] ?? ''],
            ['nb' => $d['registrador_nombre'] ?? '', 'pu' => $d['registrador_puesto'] ?? ''],
        ];
        // Fila FIRMA
        $pdf->SetFont('Arial', 'B', 7);
        foreach ($resp3 as $r) {
            $pdf->Cell(10,       4, 'FIRMA:',  1, 0, 'L');
            $pdf->SetFont('Arial','',7);
            $pdf->Cell($colF-10, 4, '',        1, 0);
            $pdf->SetFont('Arial','B',7);
        }
        $pdf->Ln();
        // Fila NOMBRE
        foreach ($resp3 as $r) {
            $pdf->Cell(12,       4, 'NOMBRE:', 1, 0, 'L');
            $pdf->SetFont('Arial','',6);
            $pdf->Cell($colF-12, 4, $utf($r['nb']), 1, 0, 'C');
            $pdf->SetFont('Arial','B',7);
        }
        $pdf->Ln();
        // Fila PUESTO
        foreach ($resp3 as $r) {
            $pdf->Cell(12,       4, 'PUESTO:', 1, 0, 'L');
            $pdf->SetFont('Arial','',6);
            $pdf->Cell($colF-12, 4, $utf($r['pu']), 1, 0, 'C');
            $pdf->SetFont('Arial','B',7);
        }
        $pdf->Ln(8);

        // ── SEPARADOR USO EXCLUSIVO TH ───────────────────────────────────────
        $pdf->SetLineWidth(0.5);
        $yD = $pdf->GetY();
        for ($xi = 10; $xi < 200; $xi += 5) {
            $pdf->Line($xi, $yD, min($xi + 2.5, 200), $yD);
        }
        $pdf->Ln(2);
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->Cell(190, 7, '** USO EXCLUSIVO PARA TALENTO HUMANO', 0, 1, 'L');
        $yD2 = $pdf->GetY();
        for ($xi = 10; $xi < 200; $xi += 5) {
            $pdf->Line($xi, $yD2, min($xi + 2.5, 200), $yD2);
        }
        $pdf->Ln(4);

        // ── REGISTRO DE NOTIFICACIÓN ─────────────────────────────────────────
        $pdf->SetFont('Arial', 'B', 7);
        $pdf->Cell(190, 4,
            $utf('REGISTRO DE NOTIFICACIÓN AL SERVIDOR PÚBLICO DE LA ACCIÓN DE PERSONAL (art. 22 RGLOSEP, art. 101 COA, art. 66 y 126 ERJAFE)'),
            1, 1, 'L');

        $pdf->SetFont('Arial', '', 7);
        $pdf->Cell(35, 5, $utf('COMUNICACIÓN ELECTRÓNICA:'), 1, 0, 'L');
        $pdf->Cell(5,  5, !empty($d['notificacion_electronica']) ? 'X' : '', 1, 0, 'C');
        $pdf->Cell(150,5, $utf($d['correo_notificacion'] ?? ''), 1, 1);

        $pdf->SetFont('Arial','B',7); $pdf->Cell(15, 5, 'FECHA:', 1, 0, 'L');
        $fechaNotif = $fmtDMY($d['fecha_notificacion'] ?? null);
        $horaNotif = !empty($d['fecha_notificacion']) ? date('H:i',strtotime($d['fecha_notificacion'])) : '';
        $pdf->SetFont('Arial','',7);  $pdf->Cell(75, 5, $utf($fechaNotif), 1, 0, 'C');
        $pdf->SetFont('Arial','B',7); $pdf->Cell(15, 5, 'HORA:', 1, 0, 'L');
        $pdf->SetFont('Arial','',7);  $pdf->Cell(85, 5, $horaNotif, 1, 1);

        $pdf->SetFont('Arial','B',7); $pdf->Cell(20, 5, '** MEDIO:', 1, 0, 'L');
        $pdf->SetFont('Arial','',7);  $pdf->Cell(75, 5, $utf($d['medio_notificacion'] ?? ''), 1, 0, 'C');
        $pdf->Cell(95, 5, $utf($d['documento_notificacion'] ?? ''), 1, 1);

        $pdf->Cell(190, 5,  '', 1, 1);
        $pdf->Cell(190, 5,  '', 1, 1);
        $pdf->Cell(190, 15, '', 1, 1);

        $pdf->SetFont('Arial','B',7);
        $pdf->Cell(190, 5, $utf('FIRMA DEL RESPONSABLE QUE NOTIFICÓ'), 1, 1, 'C');
        $pdf->Cell(15,  4, 'NOMBRE:', 1, 0, 'L');
        $pdf->SetFont('Arial','',7);
        $pdf->Cell(175, 4, $utf($d['notificador_nombre'] ?? ''), 1, 1, 'C');
        $pdf->SetFont('Arial','B',7); $pdf->Cell(15, 4, '', 1, 0);
        $pdf->SetFont('Arial','',7);
        $pdf->Cell(175, 4, $utf($d['notificador_puesto'] ?? ''), 1, 1, 'C');
        $pdf->SetFont('Arial','B',7); $pdf->Cell(15, 4, 'PUESTO:', 1, 0, 'L');
        $pdf->Cell(175, 4, '', 1, 1);

        $pdf->SetFont('Arial','I',6); $pdf->Ln(2);
        $pdf->Cell(190, 4,
            $utf('** Si la comunicación fue electrónica se deberá colocar el medio por el cual se notificó al servidor, así como el número del documento.'),
            0, 1, 'L');

        // Pie página 2
        $pdf->SetFont('Arial','I',6);
        $pdf->SetXY(10, 282);
        $pdf->Cell(95,  4, 'Elaborado por el Ministerio del Trabajo', 0, 0, 'L');
        $pdf->Cell(95,  4, $utf('Fecha actualizacion: 2024-08-23  /  Versión 01.1  /  Página 2 de 2'), 0, 1, 'R');

        // ── Salida ───────────────────────────────────────────────────────────
        $nombreArchivo = $archivo ?: 'Accion_Personal_' . ($nroAccion ?: ($d['accion_id'] ?? 'Formato')) . '.pdf';
        $pdf->Output($destino, $nombreArchivo);
    }
}
