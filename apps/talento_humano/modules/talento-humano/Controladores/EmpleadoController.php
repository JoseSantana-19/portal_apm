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

        // ── Procesar foto subida ──────────────────────────────────────────
        $_POST['ruta_foto'] = $this->_procesarFoto($_POST['empId'] ?? null);

        $id    = !empty($_POST['empId']) ? (int)$_POST['empId'] : null;
        $exito = $id
            ? $this->modelo->modificar($id, $_POST)
            : $this->modelo->insertar($_POST);

        $msg = $exito
            ? ($id ? 'Expediente actualizado correctamente.' : 'Funcionario registrado con éxito.')
            : 'Error al guardar. La incidencia ha sido registrada por el sistema.';

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
    private function _procesarFoto(?string $empId): string
    {
        $rutaDefault = 'public/img/default_avatar.png';

        // Sin archivo subido
        if (empty($_FILES['foto']['tmp_name'])) {
            // En edición: conservar la foto que ya tiene el empleado
            if (!empty($empId)) {
                $actual = $this->modelo->obtenerPorId((int)$empId);
                if ($actual && !empty($actual['ruta_foto'])) {
                    return $actual['ruta_foto'];
                }
            }
            return $rutaDefault;
        }

        $tmp   = $_FILES['foto']['tmp_name'];
        $orig  = $_FILES['foto']['name'];
        $size  = $_FILES['foto']['size'];
        $error = $_FILES['foto']['error'];

        if ($error !== UPLOAD_ERR_OK) return $rutaDefault;

        // Validar tamaño: máx 2 MB
        if ($size > 2 * 1024 * 1024) return $rutaDefault;

        // Validar tipo MIME real (no solo extensión)
        $mime = mime_content_type($tmp);
        $tiposPermitidos = ['image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($mime, $tiposPermitidos, true)) return $rutaDefault;

        // Determinar extensión segura
        $ext = match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
            default      => 'jpg',
        };

        // Nombre único para evitar colisiones
        $nombreArchivo = 'emp_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $dirDestino    = ROOT . '/public/img/empleados/';
        $rutaDestino   = $dirDestino . $nombreArchivo;

        if (!is_dir($dirDestino)) {
            mkdir($dirDestino, 0755, true);
        }

        if (!move_uploaded_file($tmp, $rutaDestino)) {
            return $rutaDefault;
        }

        return 'public/img/empleados/' . $nombreArchivo;
    }

    /** POST /talento-humano/empleado/eliminar – Baja atómica vía SP */
    public function eliminar(): void
    {
        $id    = (int)($_POST['id'] ?? 0);
        $exito = $this->modelo->eliminar($id);

        $msg = $exito ? 'Registro eliminado del sistema.' : 'No se pudo eliminar el registro.';
        header('Location: ' . BASE_URL . '/talento-humano?msg=' . urlencode($msg) . '&ok=' . ($exito ? '1' : '0'));
        exit;
    }

    /** GET (enlace directo) /talento-humano/empleado/borrar?id=X – Alias de eliminar via GET */
    public function borrar(): void
    {
        $id    = (int)($_GET['id'] ?? 0);
        $exito = $this->modelo->eliminar($id);

        $msg = $exito ? 'Registro eliminado del sistema.' : 'No se pudo eliminar el registro.';
        header('Location: ' . BASE_URL . '/talento-humano?msg=' . urlencode($msg) . '&ok=' . ($exito ? '1' : '0'));
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
            'ID',
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

        foreach ($empleados as $emp) {
            fputcsv($output, [
                $emp['empleado_id']          ?? '',
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
    public function imprimirFicha(): void
    {
        // 1. Validar que llegue un ID numérico válido
        if (empty($_GET['id']) || !ctype_digit((string)$_GET['id'])) {
            http_response_code(400);
            die('<p style="font-family:sans-serif;color:#c00;">Error: ID de funcionario no especificado o inválido.</p>');
        }

        $empleadoId = (int)$_GET['id'];

        // 2. Obtener datos completos desde la vista SQL mediante el Modelo
        $datosEmpleado = $this->modelo->obtenerDetalleCompleto($empleadoId);

        if (!$datosEmpleado) {
            http_response_code(404);
            die('<p style="font-family:sans-serif;color:#c00;">Error: El funcionario no existe o no está disponible en el sistema.</p>');
        }

        // 3. Cargar la librería FPDF desde la carpeta de librerías del proyecto
        require_once ROOT . '/libs/fpdf/fpdf.php';

        // 4. Construir y enviar el PDF al navegador
        $this->generarPdfFicha($datosEmpleado);
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
        $utf = function(string $str): string {
            return utf8_decode((string)($str ?? ''));
        };

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
