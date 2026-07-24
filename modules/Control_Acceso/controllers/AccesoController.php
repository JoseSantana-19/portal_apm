<?php
class AccesoController extends Controller {

    private AccesoModel $model;

    public function __construct() {
        parent::__construct();
        $this->model = new AccesoModel();
    }

    public function index(): void {
        $this->requireAuth();
        $registros = $this->model->getHoy();

        $this->render('Control_Acceso/acceso/index', [
            'pageTitle' => 'Control de Acceso — Hoy',
            'registros' => $registros,
            'csrf'      => $this->csrfToken(),
        ]);
    }

    public function ingresar(): void {
        $this->requireAuth();
        $empleados = $this->model->getEmpleados();

        $this->render('Control_Acceso/acceso/ingresar', [
            'pageTitle' => 'Registrar Ingreso',
            'empleados' => $empleados,
            'csrf'      => $this->csrfToken(),
        ]);
    }

    public function registrarIngreso(): void {
        $this->requireAuth();
        $this->verifyCsrf();

        $rules = [
            'persona_visita' => 'required|min:3|max:150',
        ];
        if (!FormHelper::validate($_POST, $rules)) {
            FormHelper::flashOld($_POST);
            SessionHelper::flash('form_errors', FormHelper::errors());
            $this->redirect('/acceso/ingresar');
        }

        $id = $this->model->registrarIngreso($_POST);
        SessionHelper::flash('success', 'Ingreso registrado. ID: ' . $id);
        $this->redirect('/acceso');
    }

    public function registrarSalida(): void {
        $this->requireAuth();
        $this->verifyCsrf();

        $idRegistro = (int)($_POST['id_registro'] ?? 0);
        if (!$idRegistro) {
            $this->json(['ok' => false, 'message' => 'ID de registro inválido.']);
        }

        $ok = $this->model->registrarSalida($idRegistro);
        $this->json(['ok' => $ok, 'message' => $ok ? 'Salida registrada.' : 'Registro no encontrado o ya cerrado.']);
    }

    public function reporte(): void {
        $this->requireAuth();
        $page      = max(1, (int)($_GET['pagina'] ?? 1));
        $registros = $this->model->getAll($page);

        $this->render('Control_Acceso/acceso/reporte', [
            'pageTitle' => 'Reporte de Acceso',
            'registros' => $registros,
            'page'      => $page,
        ]);
    }
}
