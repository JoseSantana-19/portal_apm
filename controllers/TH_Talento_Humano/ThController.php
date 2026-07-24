<?php
/**
 * ThController. Handles Talent Human (TH) actions, lists, detail cards, and medical events.
 */
class ThController extends Controller {

    private Empleado $empleadoModel;

    public function __construct() {
        parent::__construct();
        $this->requireAuth();
        $this->empleadoModel = new Empleado();
    }

    /**
     * Display paginated, searchable and filterable talent human list.
     */
    public function index(): void {
        $userId = $_SESSION['user_id'];
        $menuObj = new Menu();
        $userMenu = $menuObj->getUserMenu($userId);

        // Fetch inputs
        $search = $this->sanitize($_GET['search'] ?? '');
        $dept = $this->sanitize($_GET['dept'] ?? '');
        $active = isset($_GET['active']) ? $this->sanitize($_GET['active']) : '1';
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        if ($page < 1) $page = 1;

        $filters = [
            'search' => $search,
            'dept' => $dept,
            'active' => $active
        ];

        // Retrieve lists
        $employees = $this->empleadoModel->getList($filters, $page, 8); // 8 items per page looks excellent visually
        $departments = $this->empleadoModel->getDepartments();

        $this->render('TH_Talento_Humano/index', [
            'title' => 'Talento Humano — Portal APM',
            'userMenu' => $userMenu,
            'employees' => $employees['data'],
            'total' => $employees['total'],
            'page' => $employees['page'],
            'pages' => $employees['pages'],
            'limit' => $employees['limit'],
            'departments' => $departments,
            'filters' => $filters
        ]);
    }

    /**
     * Display a complete employee file (Ficha de Empleado)
     */
    public function ficha(): void {
        $userId = $_SESSION['user_id'];
        $menuObj = new Menu();
        $userMenu = $menuObj->getUserMenu($userId);

        $empId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($empId <= 0) {
            $this->redirect('talento-humano');
        }

        $details = $this->empleadoModel->getDetails($empId);
        if (empty($details)) {
            $_SESSION['error'] = 'Empleado no encontrado o no autorizado.';
            $this->redirect('talento-humano');
        }

        $this->render('TH_Talento_Humano/ficha', [
            'title' => 'Ficha de Personal — ' . $details['profile']['nombre_completo'],
            'userMenu' => $userMenu,
            'profile' => $details['profile'],
            'contracts' => $details['contracts'],
            'adendas' => $details['adendas'],
            'medicals' => $details['medicals']
        ]);
    }

    /**
     * Add a medical novelty event via POST
     */
    public function addMedical(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('talento-humano');
        }

        $empId = (int)($_POST['id_empleado'] ?? 0);
        $tipo = $this->sanitize($_POST['tipo_novedad'] ?? '');
        $inicio = $this->sanitize($_POST['fecha_inicio'] ?? '');
        $fin = $this->sanitize($_POST['fecha_fin'] ?? '');
        $desc = $this->sanitize($_POST['descripcion'] ?? '');

        if ($empId <= 0 || empty($tipo) || empty($inicio)) {
            $_SESSION['error'] = 'Todos los campos marcados como obligatorios deben ser llenados.';
            $this->redirect('ficha?id=' . $empId);
        }

        try {
            $success = $this->empleadoModel->addMedicalEvent([
                'id_empleado' => $empId,
                'tipo_novedad' => $tipo,
                'fecha_inicio' => $inicio,
                'fecha_fin' => $fin,
                'descripcion' => $desc,
                'registrado_por' => $_SESSION['user_id']
            ]);

            if ($success) {
                $_SESSION['success'] = 'Novedad médica registrada correctamente en el sistema de manera transaccional.';
            } else {
                $_SESSION['error'] = 'No se pudo guardar la novedad médica por un error inesperado.';
            }
        } catch (Exception $e) {
            $_SESSION['error'] = 'Error en base de datos: ' . $e->getMessage();
        }

        $this->redirect('ficha?id=' . $empId);
    }

    /**
     * Export paginated/filtered employee list as CSV
     */
    public function exportCsv(): void {
        $search = $this->sanitize($_GET['search'] ?? '');
        $dept = $this->sanitize($_GET['dept'] ?? '');
        $active = isset($_GET['active']) ? $this->sanitize($_GET['active']) : '1';

        $filters = [
            'search' => $search,
            'dept' => $dept,
            'active' => $active
        ];

        // Fetch up to 1000 records for the report
        $employees = $this->empleadoModel->getList($filters, 1, 1000);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=reporte_empleados_apm_' . date('Ymd_His') . '.csv');
        
        $output = fopen('php://output', 'w');
        
        // Add UTF-8 BOM for Excel compatibility
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Headers
        fputcsv($output, [
            'Cédula', 
            'Nombre Completo', 
            'Correo', 
            'Departamento', 
            'Cargo Institucional', 
            'Tipo Contrato Vigente', 
            'Remuneración Mensual', 
            'Novedades Médicas Activas', 
            'Estado'
        ], ';');

        foreach ($employees['data'] as $emp) {
            fputcsv($output, [
                $emp['cedula'],
                $emp['nombre_completo'],
                $emp['correo'],
                $emp['nombre_departamento'],
                $emp['cargo_institucional'],
                $emp['contrato_tipo_vigente'] ?? 'N/A',
                '$' . number_format($emp['remuneracion'] ?? 0, 2),
                $emp['novedades_activas'],
                $emp['activo'] ? 'ACTIVO' : 'INACTIVO'
            ], ';');
        }

        fclose($output);
        exit;
    }
}
