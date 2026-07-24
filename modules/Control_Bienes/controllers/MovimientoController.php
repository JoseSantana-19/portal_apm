<?php
class MovimientoController extends Controller {

    private MovimientoModel $model;

    public function __construct() {
        parent::__construct();
        $this->model = new MovimientoModel();
    }

    public function index(): void {
        $this->requireAuth();
        $page       = max(1, (int)($_GET['pagina'] ?? 1));
        $movimientos = $this->model->getAll($page);

        $this->render('Control_Bienes/movimientos/index', [
            'pageTitle'   => 'Movimientos de Bienes',
            'movimientos' => $movimientos,
            'page'        => $page,
        ]);
    }

    public function create(): void {
        $this->requireAuth();
        $bienes  = (new BienModel())->getAll(1, 200, ['estado' => 'Activo']);
        $deptos  = (new EmpleadoModel())->getDepartamentos();

        $this->render('Control_Bienes/movimientos/form', [
            'pageTitle' => 'Registrar Movimiento',
            'bienes'    => $bienes,
            'deptos'    => $deptos,
            'csrf'      => $this->csrfToken(),
        ]);
    }

    public function store(): void {
        $this->requireAuth();
        $this->verifyCsrf();

        $rules = [
            'id_activo'       => 'required|numeric',
            'tipo_movimiento' => 'required',
        ];
        if (!FormHelper::validate($_POST, $rules)) {
            FormHelper::flashOld($_POST);
            SessionHelper::flash('form_errors', FormHelper::errors());
            $this->redirect('/bienes/movimientos/nuevo');
        }

        $this->model->create($_POST);
        SessionHelper::flash('success', 'Movimiento registrado.');
        $this->redirect('/bienes/movimientos');
    }
}
