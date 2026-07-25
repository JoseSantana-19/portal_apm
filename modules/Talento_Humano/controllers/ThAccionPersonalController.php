<?php
/**
 * ThAccionPersonalController — Acción de Personal (documento legal LOSEP Art. 21).
 * MVC nativo portal_apm. Datos vía ThAccionPersonalModel / ThEmpleadoModel.
 */
class ThAccionPersonalController extends Controller
{
    private ThAccionPersonalModel $modelo;
    private ThEmpleadoModel       $modeloEmp;

    public function __construct()
    {
        parent::__construct();
        $this->modelo    = new ThAccionPersonalModel();
        $this->modeloEmp = new ThEmpleadoModel();
    }

    /** GET /th/accion-personal — formulario oficial. */
    public function index(): void
    {
        $this->requireAuth();
        $id     = isset($_GET['id']) ? (int)$_GET['id'] : null;
        $cedula = isset($_GET['cedula']) ? trim($_GET['cedula']) : null;

        // Preselección del servidor por ID o por cédula (round-trip servidor, sin JS).
        $empleado = null;
        if ($id) {
            $empleado = $this->modeloEmp->obtenerDetalleCompleto($id);
        } elseif ($cedula !== null && $cedula !== '') {
            $empleado = $this->modeloEmp->obtenerPorCedula(preg_replace('/[^0-9A-Za-z]/', '', $cedula));
        }

        $this->render('Talento_Humano/accion_personal', [
            'pageTitle'    => 'Acción de Personal',
            'empleado'     => $empleado,
            'nroAccion'    => $this->modelo->generarSiguienteSecuencial(),
            'preselCedula' => isset($_GET['cedula']) ? trim($_GET['cedula']) : null,
            'areas'        => $this->modeloEmp->listarAreas(),
            'cargos'       => $this->modeloEmp->listarCargos(),
            'acciones'     => $this->modelo->listar(),
            'csrf'         => $this->csrfToken(),
        ]);
    }

    /** GET /th/accion-personal/buscar-servidor?id=X — JSON. */
    public function buscarServidor(): void
    {
        $this->requireAuth();
        if (empty($_GET['id']) || !ctype_digit((string)$_GET['id'])) {
            $this->json(['success' => false, 'message' => 'ID no proporcionado o inválido.']);
        }
        $data = $this->modeloEmp->obtenerDetalleCompleto((int)$_GET['id']);
        $this->json($data ? ['success' => true, 'data' => $data]
                          : ['success' => false, 'message' => 'Servidor público no encontrado.']);
    }

    /** GET /th/accion-personal/buscar-por-cedula?cedula=X — JSON. */
    public function buscarPorCedula(): void
    {
        $this->requireAuth();
        $cedula = trim($_GET['cedula'] ?? '');
        if ($cedula === '' || !preg_match('/^\d{5,13}$/', $cedula)) {
            $this->json(['success' => false, 'message' => 'Cédula no válida (5 a 13 dígitos).']);
        }
        $data = $this->modeloEmp->obtenerPorCedula($cedula);
        $this->json($data ? ['success' => true, 'data' => $data]
                          : ['success' => false, 'message' => "No se encontró funcionario con cédula {$cedula}."]);
    }

    /** POST /th/accion-personal/guardar. */
    public function guardar(): void
    {
        $this->requireAuth();
        $this->verifyCsrf();

        $explicacion = trim($_POST['motivacion_texto'] ?? '')
            . (!empty($_POST['explicacion_otro']) ? ' — ' . trim($_POST['explicacion_otro']) : '');

        $payload = [
            'numero_accion'           => trim($_POST['numero_accion'] ?? ''),
            'empleado_id'             => (int)($_POST['empleado_id'] ?? 0),
            'tipo_accion'             => trim($_POST['tipo_accion'] ?? ''),
            'fecha_rige_desde'        => $_POST['rige_desde'] ?? date('Y-m-d'),
            'fecha_rige_hasta'        => !empty($_POST['rige_hasta']) ? $_POST['rige_hasta'] : null,
            'explicacion_legal'       => $explicacion,
            'actual_unidad_id'        => (int)($_POST['actual_unidad_id'] ?? 0),
            'actual_puesto_id'        => (int)($_POST['actual_puesto_id'] ?? 0),
            'actual_lugar_trabajo'    => 'Manta - Instalaciones APM',
            'actual_remuneracion'     => (float)($_POST['actual_remuneracion'] ?? 0),
            'propuesta_unidad_id'     => (int)($_POST['propuesta_unidad_id'] ?? 0),
            'propuesta_puesto_id'     => (int)($_POST['propuesta_puesto_id'] ?? 0),
            'propuesta_lugar_trabajo' => 'Manta - Instalaciones APM',
            'propuesta_remuneracion'  => (float)($_POST['propuesta_remuneracion'] ?? 0),
        ];

        if ($payload['empleado_id'] === 0 || $payload['tipo_accion'] === '' || trim($payload['explicacion_legal']) === '') {
            SessionHelper::flash('error', 'Verifique el empleado, el tipo de acción y la motivación legal.');
            $this->redirect('/th/accion-personal');
        }

        $exito = $this->modelo->registrarAccion($payload);
        SessionHelper::flash($exito ? 'success' : 'error', $exito
            ? "Acción de Personal {$payload['numero_accion']} registrada correctamente."
            : 'No se pudo guardar la Acción de Personal.');
        $this->redirect('/th/directorio');
    }

    /** GET /th/accion-personal/ver?id=X. */
    public function ver(): void
    {
        $this->requireAuth();
        $id = (int)($_GET['id'] ?? 0);
        $this->render('Talento_Humano/accion_personal', [
            'pageTitle' => 'Acción de Personal',
            'accion_id' => $id,
            'accion'    => $id ? $this->modelo->obtenerPorId($id) : null,
            'empleado'  => null,
            'nroAccion' => $this->modelo->generarSiguienteSecuencial(),
            'areas'     => $this->modeloEmp->listarAreas(),
            'cargos'    => $this->modeloEmp->listarCargos(),
            'acciones'  => $this->modelo->listar(),
            'csrf'      => $this->csrfToken(),
        ]);
    }

    /** GET /th/accion-personal/imprimir?id=X — PDF oficial LOSEP (2 páginas). */
    public function imprimirAccion(): void
    {
        $this->requireAuth();
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) { die('Error: número de acción inválido (?id=X).'); }
        $datos = $this->modelo->obtenerAccionCruzada($id);
        if (!$datos) { die('Error: el documento de Acción de Personal no existe.'); }
        require_once ROOT . '/libs/fpdf/fpdf.php';
        $this->generarPdfAccionOficial($datos);
    }

    private static function fmt(?string $f): string
    {
        return (!empty($f)) ? date('d/m/Y', strtotime($f)) : '';
    }

    /** GET /th/accion-personal/export/excel — listado de acciones. */
    public function exportarAccionesExcel(): void
    {
        $this->requireAuth();
        require_once ROOT . '/libs/XlsxWriter.php';
        $x = new XlsxWriter('Acciones de Personal');
        $x->setColumns([
            ['N° Acción', 20], ['Fecha elaboración', 18], ['Funcionario', 32], ['Cédula', 14],
            ['Tipo de acción', 24], ['Rige desde', 14], ['Rige hasta', 14], ['Estado', 14],
        ]);
        foreach ($this->modelo->listar() as $a) {
            $x->addRow([
                $a['numero_accion'] ?? '', self::fmt($a['fecha_elaboracion'] ?? null),
                $a['funcionario'] ?? '', $a['cedula'] ?? '', $a['tipo_accion'] ?? '',
                self::fmt($a['fecha_rige_desde'] ?? null),
                !empty($a['fecha_rige_hasta']) ? self::fmt($a['fecha_rige_hasta']) : 'Permanente',
                $a['estado_documento'] ?? '',
            ]);
        }
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="acciones_personal_' . date('Ymd_His') . '.xlsx"');
        header('Cache-Control: max-age=0');
        echo $x->build();
        exit;
    }

    /** GET /th/accion-personal/export/pdf — listado de acciones. */
    public function exportarAccionesPdf(): void
    {
        $this->requireAuth();
        require_once ROOT . '/libs/fpdf/fpdf.php';
        require_once ROOT . '/libs/ReportPdf.php';
        $cols = [
            ['N° Acción', 34], ['Fecha elab.', 26], ['Funcionario', 60], ['Cédula', 26],
            ['Tipo', 40], ['Rige desde', 24], ['Rige hasta', 24], ['Estado', 43],
        ];
        $rows = [];
        foreach ($this->modelo->listar() as $a) {
            $rows[] = [
                $a['numero_accion'] ?? '', self::fmt($a['fecha_elaboracion'] ?? null),
                $a['funcionario'] ?? '', $a['cedula'] ?? '', $a['tipo_accion'] ?? '',
                self::fmt($a['fecha_rige_desde'] ?? null),
                !empty($a['fecha_rige_hasta']) ? self::fmt($a['fecha_rige_hasta']) : 'Permanente',
                $a['estado_documento'] ?? '',
            ];
        }
        ReportPdf::tabla('L', 'Registro de Acciones de Personal', '', $cols, $rows, 'acciones_personal_' . date('Ymd') . '.pdf');
    }

    /** Motor FPDF — formato oficial de 2 páginas del MRL. */
    private function generarPdfAccionOficial(array $d): void
    {
        $utf = fn($s) => (string) (@iconv('UTF-8', 'ISO-8859-1//TRANSLIT//IGNORE', (string)($s ?? '')) ?: '');

        $meses = ['January'=>'ENERO','February'=>'FEBRERO','March'=>'MARZO','April'=>'ABRIL',
                  'May'=>'MAYO','June'=>'JUNIO','July'=>'JULIO','August'=>'AGOSTO',
                  'September'=>'SEPTIEMBRE','October'=>'OCTUBRE','November'=>'NOVIEMBRE','December'=>'DICIEMBRE'];
        $fmtFecha = function(?string $ts) use ($meses): string {
            if (empty($ts)) return '';
            return str_replace(array_keys($meses), array_values($meses), date('d \D\E F \D\E\L Y', strtotime($ts)));
        };
        $fmtDMY = fn(?string $ts) => empty($ts) ? '' : date('d-m-Y', strtotime($ts));

        $logoPath  = ROOT . '/imgs/logoapm.png';
        $nroAccion = $d['numero_accion']  ?? '';
        $cedula    = $d['identificacion'] ?? '';
        $apellidos = $utf(strtoupper($d['apellidos'] ?? ''));
        $nombres   = $utf(strtoupper($d['nombres']   ?? ''));
        $servidor  = $utf(strtoupper(($d['apellidos'] ?? '') . ' ' . ($d['nombres'] ?? '')));
        $tipoRaw   = strtoupper(trim($d['tipo_accion'] ?? ''));
        $fechaElab  = $fmtFecha($d['fecha_elaboracion'] ?? null) ?: $fmtFecha(date('Y-m-d'));
        $fechaDesde = $fmtDMY($d['fecha_rige_desde'] ?? null);
        $fechaHasta = !empty($d['fecha_rige_hasta']) ? $fmtDMY($d['fecha_rige_hasta']) : 'PERMANENTE';

        $chk = function(FPDF $p, string $etiqueta) use ($tipoRaw): void {
            $norm = strtr(strtoupper($etiqueta), ['Á'=>'A','É'=>'E','Í'=>'I','Ó'=>'O','Ú'=>'U','Ñ'=>'N']);
            $t2   = strtr($tipoRaw, ['Á'=>'A','É'=>'E','Í'=>'I','Ó'=>'O','Ú'=>'U','Ñ'=>'N']);
            $marcado = ($norm === $t2 || ($norm !== '' && strpos($t2, substr($norm, 0, min(7, strlen($norm)))) !== false));
            $p->SetFont('Arial', 'B', 8);
            $p->Cell(5, 4, $marcado ? 'X' : '', 1, 0, 'C');
        };

        // ── PÁGINA 1 ──────────────────────────────────────────────────────────
        $pdf = new FPDF('P', 'mm', 'A4');
        $pdf->SetMargins(10, 10, 10);
        $pdf->SetAutoPageBreak(false);
        $pdf->AddPage();

        $pdf->Rect(10, 10, 95, 18);
        if (file_exists($logoPath)) { $pdf->Image($logoPath, 12, 11, 22); }
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->SetXY(35, 12); $pdf->Cell(68, 5, 'AUTORIDAD', 0, 2, 'L');
        $pdf->SetX(35);      $pdf->Cell(68, 5, 'PORTUARIA', 0, 2, 'L');
        $pdf->SetX(35);      $pdf->Cell(68, 5, 'DE MANTA', 0, 2, 'L');

        $pdf->SetXY(105, 10); $pdf->SetFont('Arial', 'B', 13);
        $pdf->Cell(95, 8, $utf('ACCIÓN DE PERSONAL'), 1, 1, 'C');
        $pdf->SetXY(105, 18); $pdf->SetFont('Arial', '', 8);
        $pdf->Cell(20, 5, 'Nro.', 1, 0, 'C');
        $pdf->SetFont('Arial', 'B', 9); $pdf->Cell(75, 5, $nroAccion, 1, 1, 'C');
        $pdf->SetX(105); $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell(95, 5, $utf('FECHA DE ELABORACIÓN'), 1, 1, 'C');
        $pdf->SetX(105); $pdf->SetFont('Arial', '', 9);
        $pdf->Cell(95, 5, $utf($fechaElab), 1, 1, 'C');

        $pdf->SetXY(10, 33); $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell(95, 5, 'APELLIDOS', 1, 0, 'C'); $pdf->Cell(95, 5, 'NOMBRES', 1, 1, 'C');
        $pdf->SetFont('Arial', '', 9);
        $pdf->Cell(95, 6, $apellidos, 1, 0, 'C'); $pdf->Cell(95, 6, $nombres, 1, 1, 'C');

        $pdf->SetFont('Arial', 'B', 7);
        $pdf->Cell(47.5, 5, $utf('DOCUMENTO DE IDENTIFICACIÓN'), 1, 0, 'C');
        $pdf->Cell(47.5, 5, $utf('NRO. DE IDENTIFICACIÓN'), 1, 0, 'C');
        $pdf->Cell(47.5, 5, 'DESDE (dd-mm-aaaa)', 1, 0, 'C');
        $pdf->Cell(47.5, 5, 'HASTA (dd-mm-aaaa) (cuando aplica)', 1, 1, 'C');
        $pdf->SetFont('Arial', '', 8);
        $pdf->Cell(47.5, 6, $utf('CÉDULA'), 1, 0, 'C'); $pdf->Cell(47.5, 6, $cedula, 1, 0, 'C');
        $pdf->Cell(47.5, 6, $fechaDesde, 1, 0, 'C'); $pdf->Cell(47.5, 6, $fechaHasta, 1, 1, 'C');

        $pdf->SetXY(10, 65); $pdf->SetFont('Arial', 'B', 7);
        $pdf->Cell(190, 5, $utf('Escoja una opción (art. 21 del Reglamento General a la LOSEP)'), 'LTR', 1, 'L');
        $col1 = ['INGRESO','REINGRESO','RESTITUCIÓN','REINTEGRO','ASCENSO','TRASLADO','SANCIONES'];
        $col2 = ['TRASPASO','CAMBIO ADMINISTRATIVO','INTERCAMBIO VOLUNTARIO','LICENCIA','COMISIÓN DE SERVICIOS','',''];
        $col3 = ['INCREMENTO RMU','SUBROGACIÓN','ENCARGO','CESACIÓN DE FUNCIONES','DESTITUCIÓN','VACACIONES',''];
        $col4 = ['REVISIÓN CLASI. PUESTO','OTRO (DETALLAR)','','','','',''];
        $startChk = $pdf->GetY(); $rowH = 4; $colW = 47.5;
        for ($r = 0; $r < 7; $r++) {
            foreach ([[10,$col1[$r]],[10+$colW,$col2[$r]],[10+$colW*2,$col3[$r]],[10+$colW*3,$col4[$r]]] as $it) {
                $pdf->SetXY($it[0], $startChk + $r * $rowH);
                $pdf->SetFont('Arial', '', 7); $pdf->Cell($colW - 6, $rowH, $utf($it[1]), 0, 0, 'L');
                if ($it[1] !== '') { $chk($pdf, $it[1]); }
            }
        }
        $gridH = 7 * $rowH; $pdf->Rect(10, $startChk, 190, $gridH);
        for ($c = 1; $c < 4; $c++) { $pdf->Line(10 + $colW * $c, $startChk, 10 + $colW * $c, $startChk + $gridH); }

        $yDJ = $startChk + $gridH; $pdf->SetXY(10, $yDJ); $pdf->SetFont('Arial', 'B', 7);
        $pdf->Cell(80, 5, $utf('EN CASO DE REQUERIR ESPECIFICACIÓN: ') . $utf($d['tipo_accion'] ?? ''), 'LTB', 0, 'L');
        $pdf->Cell(75, 5, $utf('* PRESENTÓ LA DECLARACIÓN JURADA (num. 2 art. 3 RLOSEP)'), 'LTB', 0, 'L');
        $pdf->Cell(8, 5, 'SI', 'LTB', 0, 'C'); $pdf->Cell(5, 5, '', 1, 0, 'C');
        $pdf->Cell(15, 5, 'NO APLICA', 'TB', 0, 'C'); $pdf->Cell(5, 5, 'X', 1, 0, 'C');
        $pdf->Cell(2, 5, '', 'RTB', 1);

        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell(190, 5, $utf('MOTIVACIÓN: (adjuntar anexo si lo posee)'), 'LTR', 1, 'L');
        $pdf->SetFont('Arial', '', 8);
        $yMotStart = $pdf->GetY();
        $pdf->MultiCell(190, 4, $utf($d['explicacion_legal'] ?? ''), 'LR', 'J');
        $yMotEnd = $pdf->GetY();
        if (($yMotEnd - $yMotStart) < 20) { $pdf->Cell(190, 20 - ($yMotEnd - $yMotStart), '', 'LR', 1); $yMotEnd = $pdf->GetY(); }
        $pdf->SetXY(10, $yMotEnd); $pdf->Cell(190, 0, '', 'T', 1);

        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell(95, 5, 'SITUACION ACTUAL', 1, 0, 'C'); $pdf->Cell(95, 5, 'SITUACION PROPUESTA', 1, 1, 'C');
        $dataY = $pdf->GetY(); $rX = 105;
        $filas = [
            ['lab'=>'PROCESO INSTITUCIONAL:','act'=>'','prop'=>''],
            ['lab'=>'ADJETIVO:','act'=>'','prop'=>''],
            ['lab'=>'NIVEL DE GESTION:','act'=>'','prop'=>''],
            ['lab'=>'UNIDAD ADMINISTRATIVA:','act'=>$utf($d['actual_area'] ?? ''),'prop'=>$utf($d['propuesta_area'] ?? '')],
            ['lab'=>'LUGAR DE TRABAJO:','act'=>$utf($d['actual_lugar_trabajo'] ?? 'MANTA'),'prop'=>$utf($d['propuesta_lugar_trabajo'] ?? 'MANTA')],
            ['lab'=>'DENOMINACION DEL PUESTO:','act'=>$utf($d['actual_cargo'] ?? ''),'prop'=>$utf($d['propuesta_cargo'] ?? '')],
            ['lab'=>'GRUPO OCUPACIONAL:','act'=>'','prop'=>''],
            ['lab'=>'GRADO:','act'=>'','prop'=>''],
            ['lab'=>'REMUNERACION MENSUAL:','act'=>'$ '.number_format((float)($d['actual_remuneracion'] ?? 0),2),'prop'=>'$ '.number_format((float)($d['propuesta_remuneracion'] ?? 0),2)],
            ['lab'=>'PARTIDA INDIVIDUAL:','act'=>$utf($d['actual_partida_presupuestaria'] ?? ''),'prop'=>$utf($d['propuesta_partida_presupuestaria'] ?? '')],
        ];
        foreach ($filas as $f) {
            $pdf->SetFont('Arial', 'B', 7); $pdf->Cell(95, 4, $f['lab'], 'LR', 1, 'L');
            $pdf->SetFont('Arial', '', 7); $pdf->Cell(95, 5, $f['act'], 'LR', 1, 'L');
        }
        $pdf->Cell(95, 0, '', 'T', 0); $endYLeft = $pdf->GetY();
        $pdf->SetXY($rX, $dataY);
        foreach ($filas as $f) {
            $pdf->SetFont('Arial', 'B', 7); $pdf->Cell(95, 4, $f['lab'], 'LR', 2, 'L'); $pdf->SetX($rX);
            $pdf->SetFont('Arial', '', 7); $pdf->Cell(95, 5, $f['prop'], 'LR', 2, 'L'); $pdf->SetX($rX);
        }
        $pdf->SetXY($rX, $pdf->GetY()); $pdf->Cell(95, 0, '', 'T', 1);
        $pdf->SetY(max($endYLeft, $pdf->GetY()));

        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell(190, 5, $utf('POSESIÓN DEL PUESTO'), 1, 1, 'C');
        $pdf->Cell(190, 25, '', 1, 0); $yPosBox = $pdf->GetY() - 24;
        $pdf->SetXY(12, $yPosBox); $pdf->SetFont('Arial', '', 8);
        $pdf->Cell(130, 5, 'YO, ' . $servidor . ', JURO LEALTAD AL ESTADO ECUATORIANO.', 0, 2);
        $pdf->SetX(12); $pdf->Cell(70, 5, $utf('LUGAR: MANTA          FECHA: ') . $fechaDesde, 0, 2);
        $pdf->SetXY(140, $yPosBox); $pdf->Cell(60, 5, 'CON NRO. DOC.: ' . $cedula, 0, 1);
        $pdf->SetXY(10, $pdf->GetY()); $pdf->SetFont('Arial', '', 7);
        $pdf->Cell(95, 5, 'NRO. ACTA FINAL', 1, 0, 'C'); $pdf->Cell(95, 5, 'FIRMA SERVIDOR PÚBLICO', 1, 1, 'C');
        $pdf->Cell(95, 5, '', 1, 0, 'C'); $pdf->Cell(95, 5, 'FECHA', 1, 1, 'C');

        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell(190, 5, $utf('RESPONSABLES DE APROBACIÓN'), 1, 1, 'C');
        $pdf->Cell(95, 5, $utf('DIRECTOR (A) O RESPONSABLE DE TALENTO HUMANO'), 1, 0, 'C');
        $pdf->Cell(95, 5, 'AUTORIDAD NOMINADORA O SU DELEGADO', 1, 1, 'C');
        $pdf->Cell(95, 20, '', 1, 0); $pdf->Cell(95, 20, '', 1, 1);
        $pdf->SetFont('Arial', '', 7);
        $pdf->Cell(95, 4, 'FIRMA: _____________________________', 1, 0, 'L');
        $pdf->Cell(95, 4, 'FIRMA: _____________________________', 1, 1, 'L');
        $pdf->Cell(95, 4, 'NOMBRE: _____________________________', 1, 0, 'L');
        $pdf->Cell(95, 4, 'NOMBRE: _____________________________', 1, 1, 'L');
        $pdf->Cell(95, 4, $utf('PUESTO: DIRECTOR ADMIN. TALENTO HUMANO'), 1, 0, 'L');
        $pdf->Cell(95, 4, 'PUESTO: GERENTE', 1, 1, 'L');
        $pdf->SetFont('Arial', 'I', 6); $pdf->SetXY(10, 285);
        $pdf->Cell(95, 4, 'Elaborado por el Ministerio del Trabajo', 0, 0, 'L');
        $pdf->Cell(95, 4, $utf('Versión 01.1  /  Página 1 de 2'), 0, 1, 'R');

        // ── PÁGINA 2 ──────────────────────────────────────────────────────────
        $pdf->AddPage(); $pdf->SetMargins(10, 10, 10);
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell(190, 5, 'RESPONSABLES DE FIRMAS', 1, 1, 'C');
        $pdf->Cell(107, 5, $utf('ACEPTACIÓN Y/O RECEPCIÓN DEL SERVIDOR PÚBLICO'), 1, 0, 'C');
        $pdf->Cell(83, 5, $utf('EN CASO DE NEGATIVA DE LA RECEPCIÓN (TESTIGO)'), 1, 1, 'C');
        $pdf->Cell(107, 30, '', 1, 0); $pdf->Cell(83, 30, '', 1, 1);
        $pdf->SetFont('Arial', 'B', 7);
        $pdf->Cell(12, 4, 'NOMBRE:', 1, 0, 'L'); $pdf->SetFont('Arial', '', 7);
        $pdf->Cell(95, 4, $servidor, 1, 0, 'L');
        $pdf->SetFont('Arial', 'B', 7); $pdf->Cell(12, 4, 'NOMBRE:', 1, 0, 'L'); $pdf->Cell(71, 4, '', 1, 1);
        $pdf->SetFont('Arial', 'B', 7); $pdf->Cell(10, 4, 'FECHA:', 1, 0, 'L'); $pdf->SetFont('Arial', '', 7);
        $pdf->Cell(97, 4, $utf($fechaElab), 1, 0, 'C');
        $pdf->SetFont('Arial', 'B', 7); $pdf->Cell(10, 4, 'FECHA:', 1, 0, 'L'); $pdf->Cell(73, 4, '', 1, 1);

        $colF = 190 / 3; $pdf->SetFont('Arial', 'B', 7);
        $pdf->Cell($colF, 5, $utf('RESPONSABLE DE ELABORACIÓN'), 1, 0, 'C');
        $pdf->Cell($colF, 5, $utf('RESPONSABLE DE REVISIÓN'), 1, 0, 'C');
        $pdf->Cell($colF, 5, $utf('RESPONSABLE DE REGISTRO Y CONTROL'), 1, 1, 'C');
        $pdf->Cell($colF, 25, '', 1, 0); $pdf->Cell($colF, 25, '', 1, 0); $pdf->Cell($colF, 25, '', 1, 1);

        $pdf->Ln(4);
        $pdf->SetFont('Arial', 'B', 7);
        $pdf->Cell(190, 4, $utf('REGISTRO DE NOTIFICACIÓN AL SERVIDOR PÚBLICO (art. 22 RGLOSEP)'), 1, 1, 'L');
        $pdf->SetFont('Arial', '', 7);
        $pdf->Cell(35, 5, $utf('COMUNICACIÓN ELECTRÓNICA:'), 1, 0, 'L');
        $pdf->Cell(5, 5, '', 1, 0, 'C'); $pdf->Cell(150, 5, '', 1, 1);
        $pdf->SetFont('Arial', 'B', 7); $pdf->Cell(15, 5, 'FECHA:', 1, 0, 'L');
        $pdf->SetFont('Arial', '', 7); $pdf->Cell(75, 5, $utf($fechaElab), 1, 0, 'C');
        $pdf->SetFont('Arial', 'B', 7); $pdf->Cell(15, 5, 'HORA:', 1, 0, 'L');
        $pdf->SetFont('Arial', '', 7); $pdf->Cell(85, 5, '', 1, 1);
        $pdf->SetFont('Arial', 'B', 7); $pdf->Cell(20, 5, '** MEDIO:', 1, 0, 'L');
        $pdf->SetFont('Arial', '', 7); $pdf->Cell(75, 5, 'QUIPUX', 1, 0, 'C'); $pdf->Cell(95, 5, '', 1, 1);
        $pdf->SetFont('Arial', 'I', 6); $pdf->SetXY(10, 285);
        $pdf->Cell(95, 4, 'Elaborado por el Ministerio del Trabajo', 0, 0, 'L');
        $pdf->Cell(95, 4, $utf('Versión 01.1  /  Página 2 de 2'), 0, 1, 'R');

        $pdf->Output('I', 'Accion_Personal_' . ($nroAccion ?: ($d['accion_id'] ?? 'APM')) . '.pdf');
        exit;
    }
}
