<?php
// modules/Talento_Humano/controllers/EmpleadoController.php
// RECTIFICADO v2.0 – Auditoría Técnica Autoridad Portuaria de Manta

require_once ROOT_PATH . 'modules/Talento_Humano/models/EmpleadoModel.php';
require_once ROOT_PATH . 'modules/Bitacoras/models/LogModel.php';

class EmpleadoController extends Controller
{
    private $modelo;
    private $logger;

    public function __construct()
    {
        parent::__construct();
        $this->logger = new Logger('th');
        $this->modelo = new EmpleadoModel();
    }

    /** GET /talento – Pantalla de Inicio (Dashboard RRHH) */
    public function index()
    {
        $this->registrarAuditoria('ACCESO', 'th', 'Acceso al Dashboard de Talento Humano');
        
        $datos = [
            'empleados'   => $this->modelo->listarDirectorio(),
            'rbu_vigente' => $this->modelo->obtenerRbuVigente(),
        ];
        
        $this->render('inicio', $datos, 'Inicio | Talento Humano - SysPort');
    }

    /** GET /talento_inicio – Alias explícito del Dashboard */
    public function inicio()
    {
        $this->index();
    }

    /** GET /talento_directorio – Vista de listado de funcionarios */
    public function directorio()
    {
        $this->registrarAuditoria('ACCESO', 'th', 'Acceso al Directorio de Personal');
        
        $datos = [
            'empleados'   => $this->modelo->listarDirectorio(),
            'rbu_vigente' => $this->modelo->obtenerRbuVigente(),
        ];
        $this->render('empleados/listar', $datos, 'Directorio de Personal - SysPort');
    }

    /** GET /talento_crear – Formulario vacío (modo creación) */
    public function crear()
    {
        $datos = [
            'funcionarios' => $this->modelo->listarDirectorio(),
            'rbu_vigente'  => $this->modelo->obtenerRbuVigente(),
            'empleado'     => null,
            'modoEdicion'  => false,
            'areas'        => $this->modelo->listarAreas(),
            'cargos'       => $this->modelo->listarCargos(),
        ];
        $this->render('empleados/agregar', $datos, 'Registrar Funcionario - SysPort');
    }

    /** GET /talento_editar?id=X – Formulario pre-cargado (modo edición) */
    public function editar()
    {
        $id       = (int)($_GET['id'] ?? 0);
        $empleado = $this->modelo->obtenerPorId($id);

        if (!$empleado) {
            $this->redirect('talento_directorio', 'Empleado no encontrado.', 'error');
        }

        $datos = [
            'funcionarios' => $this->modelo->listarDirectorio(),
            'rbu_vigente'  => $this->modelo->obtenerRbuVigente(),
            'empleado'     => $empleado,
            'modoEdicion'  => true,
            'areas'        => $this->modelo->listarAreas(),
            'cargos'       => $this->modelo->listarCargos(),
        ];
        $this->render('empleados/editar', $datos, 'Editar Funcionario - SysPort');
    }

    /** POST /talento_guardar – Crea o actualiza según empId */
    public function guardar()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('talento');
        }

        $id = !empty($_POST['empId']) ? (int)$_POST['empId'] : null;

        // Procesar foto subida
        $_POST['ruta_foto'] = $this->_procesarFoto($id ? (string)$id : null);

        try {
            $exito = $id
                ? $this->modelo->modificar($id, $_POST)
                : $this->modelo->insertar($_POST);

            $msg = $exito
                ? ($id ? 'Expediente actualizado correctamente.' : 'Funcionario registrado con éxito.')
                : 'Error al guardar. La incidencia ha sido registrada por el sistema.';

            $tipo = $exito ? 'success' : 'error';
            
            if ($exito) {
                $this->registrarAuditoria($id ? 'ACTUALIZAR' : 'CREAR', 'th', ($id ? "Datos personales actualizados: ID {$id}" : "Nuevo personal registrado: " . ($_POST['nombres'] ?? '')));
            }

            $this->redirect('talento_directorio', $msg, $tipo);
        } catch (Exception $e) {
            $this->logger->inv_error("Error al guardar personal", $e, 'guardar');
            $this->redirect('talento_directorio', 'Error crítico al guardar los datos del empleado.', 'error');
        }
    }

    /** Procesa el archivo de foto subido (si existe). */
    private function _procesarFoto(?string $empId): string
    {
        $rutaDefault = 'public/img/default_avatar.png';

        if (empty($_FILES['foto']['tmp_name'])) {
            if (!empty($empId)) {
                $actual = $this->modelo->obtenerPorId((int)$empId);
                if ($actual && !empty($actual['ruta_foto'])) {
                    return $actual['ruta_foto'];
                }
            }
            return $rutaDefault;
        }

        $tmp   = $_FILES['foto']['tmp_name'];
        $size  = $_FILES['foto']['size'];
        $error = $_FILES['foto']['error'];

        if ($error !== UPLOAD_ERR_OK) return $rutaDefault;
        if ($size > 2 * 1024 * 1024) return $rutaDefault;

        $mime = mime_content_type($tmp);
        $tiposPermitidos = ['image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($mime, $tiposPermitidos, true)) return $rutaDefault;

        $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$mime] ?? 'jpg';

        $nombreArchivo = 'emp_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $dirDestino    = ROOT_PATH . 'public/img/empleados/';
        $rutaDestino   = $dirDestino . $nombreArchivo;

        if (!is_dir($dirDestino)) {
            mkdir($dirDestino, 0755, true);
        }

        if (!move_uploaded_file($tmp, $rutaDestino)) {
            return $rutaDefault;
        }

        return 'public/img/empleados/' . $nombreArchivo;
    }

    /** POST /talento_eliminar – Baja atómica */
    public function eliminar()
    {
        $id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
        
        $empleado = $this->modelo->obtenerPorId($id);
        $exito = $this->modelo->eliminar($id);

        $msg = $exito ? 'Registro eliminado del sistema.' : 'No se pudo eliminar el registro.';
        $tipo = $exito ? 'warning' : 'error';
        
        if ($exito && $empleado) {
            $this->registrarAuditoria('ELIMINAR', 'th', "Empleado eliminado: " . ($empleado['apellidos'] ?? '') . " " . ($empleado['nombres'] ?? ''));
        }

        $this->redirect('talento_directorio', $msg, $tipo);
    }

    /** GET /talento_borrar – Alias de eliminar via GET */
    public function borrar()
    {
        $this->eliminar();
    }

    /** GET /talento_reporte – Historial laboral jerárquico con fusiones */
    public function reporte()
    {
        $this->registrarAuditoria('ACCESO', 'th', 'Acceso al Historial Jerárquico de Personal');
        
        $tipoCargo  = isset($_GET['cargo'])       ? trim($_GET['cargo'])       : null;
        $empleadoId = isset($_GET['empleado_id']) ? (int)$_GET['empleado_id'] : null;

        $datos = [
            'historial'    => $this->modelo->obtenerReporteFiltrado($tipoCargo, $empleadoId),
            'filtro_cargo' => $tipoCargo,
        ];
        $this->render('historial', $datos, 'Historial Laboral - SysPort');
    }

    /** GET /talento_perfil?cedula=X – Expediente digital de un funcionario */
    public function perfil()
    {
        $cedula = isset($_GET['cedula']) ? trim($_GET['cedula']) : '';
        $cedula = preg_replace('/[^0-9]/', '', $cedula);

        $empleado = $this->modelo->obtenerPorCedula($cedula);

        if (!$empleado) {
            $this->redirect('talento_directorio', 'Empleado con cédula ' . $cedula . ' no encontrado.', 'error');
        }

        $datos = [
            'cedula'   => $cedula,
            'empleado' => $empleado,
        ];
        $this->render('perfil', $datos, 'Expediente del Funcionario - SysPort');
    }

    /** GET /talento_imprimir_ficha?id=X – Ficha PDF FPDF */
    public function imprimirFicha()
    {
        if (empty($_GET['id']) || !ctype_digit((string)$_GET['id'])) {
            http_response_code(400);
            die('<p style="font-family:sans-serif;color:#c00;">Error: ID de funcionario no especificado o inválido.</p>');
        }

        $empleadoId = (int)$_GET['id'];
        $datosEmpleado = $this->modelo->obtenerDetalleCompleto($empleadoId);

        if (!$datosEmpleado) {
            http_response_code(404);
            die('<p style="font-family:sans-serif;color:#c00;">Error: El funcionario no existe o no está disponible en el sistema.</p>');
        }

        require_once ROOT_PATH . 'libs/fpdf/fpdf.php';
        $this->generarPdfFicha($datosEmpleado);
    }

    private function generarPdfFicha(array $emp): void
    {
        $utf = function(string $str): string {
            $str = (string)($str ?? '');
            if (function_exists('mb_convert_encoding')) {
                return mb_convert_encoding($str, 'ISO-8859-1', 'UTF-8');
            }
            if (function_exists('iconv')) {
                return (string)@iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $str);
            }
            return function_exists('utf8_decode') ? @utf8_decode($str) : $str;
        };

        $pdf = new FPDF('P', 'mm', 'A4');
        $pdf->AddPage();
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(true, 15);

        // Membrete
        $logoPath = ROOT_PATH . 'logoapm.png';
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

        // Bloque 1 - Información Personal
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

        // Bloque 2 - Ubicación
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

        // Bloque 3 - Financiera
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

        // Firmas
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

        // Pie de página
        $pdf->Ln(8);
        $pdf->SetDrawColor(0, 51, 102);
        $pdf->SetLineWidth(0.5);
        $pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
        $pdf->Ln(2);
        $pdf->SetFont('Arial', 'I', 7);
        $pdf->SetTextColor(100, 100, 100);
        $pdf->Cell(180, 4, $utf('Documento generado electrónicamente por el Sistema de Gestión de Talento Humano, APM © ' . date('Y')), 0, 1, 'C');

        $nombreArchivo = 'Ficha_' . ($emp['cedula'] ?? 'APM') . '_' . date('Ymd') . '.pdf';
        $pdf->Output('I', $nombreArchivo);
        exit;
    }
}
class_alias('EmpleadoController', 'InvAsignacionTalentoController');
