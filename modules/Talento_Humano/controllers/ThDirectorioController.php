<?php
/**
 * ThDirectorioController — Directorio de Personal (BD Talento_Humano).
 * MVC nativo portal_apm: requireAuth/verifyCsrf/render(shell+SPA)/redirect + flash.
 * Datos vía ThEmpleadoModel (sqlsrv nativo, sin PDO).
 */
class ThDirectorioController extends Controller
{
    private ThEmpleadoModel $modelo;

    public function __construct()
    {
        parent::__construct();
        $this->modelo = new ThEmpleadoModel();
    }

    /** GET /th y GET /th/directorio — listado de funcionarios. */
    public function index(): void
    {
        $this->requireAuth();
        $esAdmin = (int)($_SESSION['nivel_jerarquia'] ?? 0) >= 3;
        $this->render('Talento_Humano/directorio', [
            'pageTitle'   => 'Directorio de Personal',
            'empleados'   => $this->modelo->listarDirectorio(),
            'rbu_vigente' => $this->modelo->obtenerRbuVigente(),
            'esAdmin'     => $esAdmin,
            // Cédulas que ya tienen cuenta de acceso (para marcar el directorio).
            'conCuenta'   => $esAdmin ? array_flip(ThCuentaController::cedulasConCuenta()) : [],
        ]);
    }

    /** Alias explícito. */
    public function directorio(): void { $this->index(); }

    /** GET /th/empleado/nuevo — formulario de alta. */
    public function crear(): void
    {
        $this->requireAuth();
        $this->render('Talento_Humano/formulario', [
            'pageTitle'   => 'Nuevo Funcionario',
            'empleado'    => null,
            'modoEdicion' => false,
            'areas'       => $this->modelo->listarAreas(),
            'cargos'      => $this->modelo->listarCargos(),
            'rbu_vigente' => $this->modelo->obtenerRbuVigente(),
            'csrf'        => $this->csrfToken(),
        ]);
    }

    /** GET /th/empleado/{id}/editar — formulario de edición. */
    public function editar(int $id): void
    {
        $this->requireAuth();
        $empleado = $this->modelo->obtenerPorId($id);
        if (!$empleado) {
            SessionHelper::flash('error', 'Empleado no encontrado.');
            $this->redirect('/th/directorio');
        }
        $this->render('Talento_Humano/formulario', [
            'pageTitle'   => 'Editar Funcionario',
            'empleado'    => $empleado,
            'modoEdicion' => true,
            'areas'       => $this->modelo->listarAreas(),
            'cargos'      => $this->modelo->listarCargos(),
            'rbu_vigente' => $this->modelo->obtenerRbuVigente(),
            'csrf'        => $this->csrfToken(),
        ]);
    }

    /** POST /th/empleado/guardar — crea o actualiza. */
    public function guardar(): void
    {
        $this->requireAuth();
        $this->verifyCsrf();

        $id     = !empty($_POST['empleado_id']) ? (int)$_POST['empleado_id'] : null;
        $cedula = trim($_POST['identificacion'] ?? '');

        if ($cedula === '' || trim($_POST['nombres'] ?? '') === '' || trim($_POST['apellidos'] ?? '') === '') {
            SessionHelper::flash('error', 'Cédula, nombres y apellidos son obligatorios.');
            $this->redirect($id ? "/th/empleado/{$id}/editar" : '/th/empleado/nuevo');
        }

        if ($this->modelo->existeCedula($cedula, $id)) {
            SessionHelper::flash('error', "Ya existe un funcionario con la cédula {$cedula}.");
            $this->redirect($id ? "/th/empleado/{$id}/editar" : '/th/empleado/nuevo');
        }

        $exito = $id ? $this->modelo->modificar($id, $_POST) : $this->modelo->insertar($_POST);

        if ($exito) {
            SessionHelper::flash('success', $id ? 'Expediente actualizado correctamente.' : 'Funcionario registrado con éxito.');
        } else {
            SessionHelper::flash('error', 'No se pudo guardar el expediente.');
        }
        $this->redirect('/th/directorio');
    }

    /** POST /th/empleado/eliminar — baja lógica. */
    public function eliminar(): void
    {
        $this->requireAuth();
        $this->verifyCsrf();
        $id    = (int)($_POST['empleado_id'] ?? $_POST['id'] ?? 0);
        $exito = $id > 0 && $this->modelo->eliminar($id);
        SessionHelper::flash($exito ? 'success' : 'error',
            $exito ? 'Funcionario dado de baja.' : 'No se pudo dar de baja al funcionario.');
        $this->redirect('/th/directorio');
    }

    /** GET /th/empleado/{id}/perfil — expediente digital (por ID: robusto ante cédulas con cero inicial). */
    public function perfil(int $id): void
    {
        $this->requireAuth();
        $this->render('Talento_Humano/perfil', [
            'pageTitle' => 'Expediente del Funcionario',
            'empleado'  => $this->modelo->obtenerDetalleCompleto($id),
        ]);
    }

    /** GET /th/reporte — historial laboral jerárquico. */
    public function reporte(): void
    {
        $this->requireAuth();
        $tipoCargo  = isset($_GET['cargo']) ? trim($_GET['cargo']) : null;
        $empleadoId = isset($_GET['empleado_id']) ? (int)$_GET['empleado_id'] : null;
        $this->render('Talento_Humano/historial', [
            'pageTitle'    => 'Historial / Reportes',
            'historial'    => $this->modelo->obtenerReporteFiltrado($tipoCargo, $empleadoId),
            'filtro_cargo' => $tipoCargo,
        ]);
    }

    /** Filas del reporte según filtros GET (compartido export). */
    private function reporteFilas(): array
    {
        $tipoCargo  = isset($_GET['cargo']) ? trim($_GET['cargo']) : null;
        $empleadoId = isset($_GET['empleado_id']) ? (int)$_GET['empleado_id'] : null;
        return $this->modelo->obtenerReporteFiltrado($tipoCargo, $empleadoId);
    }

    private static function fmt(?string $f, string $fallback = ''): string
    {
        return (!empty($f)) ? date('d/m/Y', strtotime($f)) : $fallback;
    }

    /** GET /th/reporte/export/excel */
    public function exportarReporteExcel(): void
    {
        $this->requireAuth();
        require_once ROOT . '/libs/XlsxWriter.php';
        $x = new XlsxWriter('Historial laboral');
        $x->setColumns([
            ['Cédula', 14], ['Funcionario', 32], ['Puesto', 28], ['Depto. histórico', 32],
            ['Sub-área', 26], ['Unificada en', 32], ['Tipo proceso', 22], ['Desde', 12], ['Hasta', 12], ['Años', 8],
        ]);
        foreach ($this->reporteFilas() as $r) {
            $x->addRow([
                $r['cedula'] ?? '', $r['funcionario'] ?? '', $r['nombre_puesto'] ?? '',
                $r['departamento_historico'] ?? '', $r['sub_area'] ?? '', $r['direccion_actual_unificada'] ?? '',
                $r['tipo_proceso'] ?? '', self::fmt($r['fecha_desde'] ?? null),
                self::fmt($r['fecha_hasta'] ?? null, 'Actual'), (string)($r['anios_permanencia'] ?? ''),
            ]);
        }
        $this->descargarExcel($x, 'historial_laboral');
    }

    /** GET /th/reporte/export/pdf */
    public function exportarReportePdf(): void
    {
        $this->requireAuth();
        require_once ROOT . '/libs/fpdf/fpdf.php';
        require_once ROOT . '/libs/ReportPdf.php';
        $cols = [
            ['Cédula', 24], ['Funcionario', 45], ['Puesto', 40], ['Depto. histórico', 42],
            ['Sub-área', 34], ['Unificada en', 42], ['Desde', 18], ['Hasta', 18], ['Años', 14],
        ];
        $rows = [];
        foreach ($this->reporteFilas() as $r) {
            $rows[] = [
                $r['cedula'] ?? '', $r['funcionario'] ?? '', $r['nombre_puesto'] ?? '',
                $r['departamento_historico'] ?? '', $r['sub_area'] ?? '', $r['direccion_actual_unificada'] ?? '',
                self::fmt($r['fecha_desde'] ?? null), self::fmt($r['fecha_hasta'] ?? null, 'Actual'),
                (string)($r['anios_permanencia'] ?? ''),
            ];
        }
        $sub = isset($_GET['cargo']) && trim($_GET['cargo']) !== '' ? 'Filtro de cargo: ' . trim($_GET['cargo']) : '';
        ReportPdf::tabla('L', 'Reporte de Historial Laboral Jerárquico', $sub, $cols, $rows, 'historial_laboral_' . date('Ymd') . '.pdf');
    }

    /** Envía un XlsxWriter como descarga .xlsx. */
    private function descargarExcel(XlsxWriter $x, string $base): never
    {
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $base . '_' . date('Ymd_His') . '.xlsx"');
        header('Cache-Control: max-age=0');
        echo $x->build();
        exit;
    }

    /** GET /th/empleado/ficha?id=X — Ficha integral en PDF (FPDF). */
    public function imprimirFicha(): void
    {
        $this->requireAuth();
        if (empty($_GET['id']) || !ctype_digit((string)$_GET['id'])) {
            http_response_code(400);
            die('<p style="font-family:sans-serif;color:#c00;">ID de funcionario inválido.</p>');
        }
        $emp = $this->modelo->obtenerDetalleCompleto((int)$_GET['id']);
        if (!$emp) {
            http_response_code(404);
            die('<p style="font-family:sans-serif;color:#c00;">El funcionario no existe.</p>');
        }
        require_once ROOT . '/libs/fpdf/fpdf.php';
        $this->generarPdfFicha($emp);
    }

    /** Dibuja la Ficha Integral del Funcionario con FPDF. */
    private function generarPdfFicha(array $emp): void
    {
        // iconv en lugar de utf8_decode() (deprecado en PHP 8.2 → corrompería el PDF).
        $utf = fn($s) => (string) (@iconv('UTF-8', 'ISO-8859-1//TRANSLIT//IGNORE', (string)($s ?? '')) ?: '');

        $pdf = new FPDF('P', 'mm', 'A4');
        $pdf->AddPage();
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(true, 15);

        $logoPath = ROOT . '/imgs/logoapm.png';
        if (file_exists($logoPath)) { @$pdf->Image($logoPath, 15, 12, 25); }

        $pdf->SetFont('Arial', 'B', 13);
        $pdf->Cell(0, 7, $utf('AUTORIDAD PORTUARIA DE MANTA'), 0, 1, 'C');
        $pdf->SetFont('Arial', '', 9);
        $pdf->Cell(0, 5, $utf('DIRECCIÓN DE ADMINISTRACIÓN DE TALENTO HUMANO'), 0, 1, 'C');
        $pdf->SetDrawColor(0, 51, 102); $pdf->SetLineWidth(0.8);
        $pdf->Line(15, $pdf->GetY() + 3, 195, $pdf->GetY() + 3);
        $pdf->Ln(8);

        $pdf->SetFillColor(0, 51, 102); $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('Arial', 'B', 13);
        $pdf->Cell(180, 9, $utf('FICHA INTEGRAL DEL FUNCIONARIO'), 1, 1, 'C', true);
        $pdf->SetTextColor(0, 0, 0); $pdf->Ln(3);
        $pdf->SetFont('Arial', 'I', 8);
        $pdf->Cell(180, 5, $utf('Fecha de Impresión: ' . date('d/m/Y H:i')), 0, 1, 'R');
        $pdf->Ln(3);

        $bloque = function(string $titulo) use ($pdf, $utf) {
            $pdf->SetFont('Arial', 'B', 10); $pdf->SetFillColor(220, 230, 241);
            $pdf->Cell(180, 6, $utf('  ' . $titulo), 0, 1, 'L', true); $pdf->Ln(2);
        };
        $par = function(string $l, string $v1, string $l2 = '', string $v2 = '') use ($pdf, $utf) {
            $pdf->SetFont('Arial', 'B', 9); $pdf->Cell(40, 6, $utf($l), 0, 0);
            $pdf->SetFont('Arial', '', 9);  $pdf->Cell($l2 ? 50 : 140, 6, $utf($v1), 0, $l2 ? 0 : 1);
            if ($l2) { $pdf->SetFont('Arial', 'B', 9); $pdf->Cell(40, 6, $utf($l2), 0, 0);
                       $pdf->SetFont('Arial', '', 9); $pdf->Cell(50, 6, $utf($v2), 0, 1); }
        };

        $bloque('1. INFORMACIÓN PERSONAL');
        $par('Identificación:', $emp['cedula'] ?? '', 'Cargas Familiares:', (string)($emp['cargas_familiares'] ?? '0'));
        $par('Apellidos:', $emp['apellidos'] ?? '', 'Nombres:', $emp['nombres'] ?? '');
        $pdf->Ln(4);

        $bloque('2. UBICACIÓN INSTITUCIONAL');
        $par('Dirección / Área:', $emp['direccion_area'] ?? '');
        $par('Puesto / Cargo:', $emp['cargo'] ?? '');
        $pdf->SetFont('Arial', 'B', 9); $pdf->Cell(40, 6, $utf('Correo Inst.:'), 0, 0);
        $pdf->SetFont('Arial', '', 9);  $pdf->Cell(50, 6, $utf($emp['correo_institucional'] ?? ''), 0, 0);
        $pdf->SetFont('Arial', 'B', 9); $pdf->Cell(40, 6, $utf('Estado Laboral:'), 0, 0);
        $activo = ((int)($emp['estado'] ?? 0) === 1);
        $pdf->SetTextColor($activo ? 0 : 180, $activo ? 120 : 0, 0);
        $pdf->Cell(50, 6, $activo ? 'ACTIVO' : 'INACTIVO', 0, 1);
        $pdf->SetTextColor(0, 0, 0); $pdf->Ln(4);

        $bloque('3. INFORMACIÓN FINANCIERA Y CRÉDITO DE NÓMINA');
        $par('Banco / Entidad:', $emp['institucion_bancaria'] ?? 'NO REGISTRADO');
        $par('Tipo de Cuenta:', $emp['tipo_cuenta_bancaria'] ?? 'NO REGISTRADO',
             'N°. de Cuenta:', $emp['numero_cuenta_bancaria'] ?? 'NO REGISTRADO');
        $pdf->Ln(20);

        $pdf->SetDrawColor(0, 0, 0); $pdf->SetLineWidth(0.3); $pdf->SetFont('Arial', 'B', 9);
        $pdf->Cell(90, 5, '__________________________________', 0, 0, 'C');
        $pdf->Cell(90, 5, '__________________________________', 0, 1, 'C');
        $pdf->Cell(90, 5, $utf('Firma del Funcionario'), 0, 0, 'C');
        $pdf->Cell(90, 5, $utf('Responsable de Talento Humano'), 0, 1, 'C');
        $pdf->SetFont('Arial', '', 8);
        $pdf->Cell(90, 4, $utf('C.I: ' . ($emp['cedula'] ?? '')), 0, 0, 'C');
        $pdf->Cell(90, 4, $utf('Validación de Expediente'), 0, 1, 'C');
        $pdf->Ln(8);
        $pdf->SetDrawColor(0, 51, 102); $pdf->SetLineWidth(0.5);
        $pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY()); $pdf->Ln(2);
        $pdf->SetFont('Arial', 'I', 7); $pdf->SetTextColor(100, 100, 100);
        $pdf->Cell(180, 4, $utf('Documento generado electrónicamente — APM © ' . date('Y')), 0, 1, 'C');

        $pdf->Output('I', 'Ficha_' . ($emp['cedula'] ?? 'APM') . '_' . date('Ymd') . '.pdf');
        exit;
    }
}
