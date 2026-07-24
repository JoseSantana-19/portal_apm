<?php
class VisitanteController extends Controller {

    private VisitanteModel $model;

    public function __construct() {
        parent::__construct();
        $this->model = new VisitanteModel();
    }

    public function index(): void {
        $this->requireAuth();
        $page      = max(1, (int)($_GET['pagina'] ?? 1));
        $visitantes = $this->model->getAll($page);

        $this->render('Control_Acceso/visitantes/index', [
            'pageTitle'  => 'Registro de Visitantes',
            'visitantes' => $visitantes,
            'page'       => $page,
        ]);
    }

    public function create(): void {
        $this->requireAuth();
        $this->render('Control_Acceso/visitantes/form', [
            'pageTitle' => 'Nuevo Visitante',
            'visitante' => null,
            'csrf'      => $this->csrfToken(),
        ]);
    }

    public function store(): void {
        $this->requireAuth();
        $this->verifyCsrf();

        $rules = [
            'nombres'   => 'required|min:2|max:100',
            'apellidos' => 'required|min:2|max:100',
            'cedula'    => 'required|min:5|max:20',
        ];
        if (!FormHelper::validate($_POST, $rules)) {
            FormHelper::flashOld($_POST);
            SessionHelper::flash('form_errors', FormHelper::errors());
            $this->redirect('/acceso/visitantes/nuevo');
        }

        // Prevent duplicates
        $existing = $this->model->findByDocumento($_POST['cedula']);
        if ($existing) {
            SessionHelper::flash('error', 'Ya existe un visitante con ese documento.');
            $this->redirect('/acceso/visitantes/nuevo');
        }

        $id = $this->model->create($_POST);
        SessionHelper::flash('success', 'Visitante registrado.');
        $this->redirect('/acceso/visitantes/' . $id);
    }

    public function show(int $id): void {
        $this->requireAuth();
        $visitante = $this->model->findById($id);
        if (!$visitante) { http_response_code(404); exit; }

        $this->render('Control_Acceso/visitantes/show', [
            'pageTitle' => 'Ficha Visitante',
            'visitante' => $visitante,
        ]);
    }
}
