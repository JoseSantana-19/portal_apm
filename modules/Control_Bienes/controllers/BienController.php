<?php
class BienController extends Controller {

    private BienModel $model;

    public function __construct() {
        parent::__construct();
        $this->model = new BienModel();
    }

    public function index(): void {
        $this->requireAuth();
        $page    = max(1, (int)($_GET['pagina'] ?? 1));
        $filters = [
            'codigo'    => $this->input('codigo',    'get'),
            'estado'    => $this->input('estado',    'get'),
            'categoria' => $this->input('categoria', 'get'),
        ];
        $total    = $this->model->count($filters);
        $bienes   = $this->model->getAll($page, 20, $filters);
        $cats     = $this->model->getCategorias();

        $this->render('Control_Bienes/bienes/index', [
            'pageTitle'  => 'Inventario de Bienes',
            'bienes'     => $bienes,
            'categorias' => $cats,
            'total'      => $total,
            'page'       => $page,
            'filters'    => $filters,
        ]);
    }

    public function create(): void {
        $this->requireAuth();
        $deptos = (new EmpleadoModel())->getDepartamentos();
        $cats   = $this->model->getCategorias();
        $this->render('Control_Bienes/bienes/form', [
            'pageTitle'  => 'Registrar Bien',
            'bien'       => null,
            'categorias' => $cats,
            'deptos'     => $deptos,
            'csrf'       => $this->csrfToken(),
        ]);
    }

    public function store(): void {
        $this->requireAuth();
        $this->verifyCsrf();

        $rules = [
            'codigo'           => 'required|min:3|max:50',
            'nombre'           => 'required|min:2|max:200',
            'id_categoria'     => 'required|numeric',
            'id_departamento'  => 'required|numeric',
            'valor_adquisicion'=> 'required|numeric',
            'fecha_adquisicion'=> 'required',
        ];
        if (!FormHelper::validate($_POST, $rules)) {
            FormHelper::flashOld($_POST);
            SessionHelper::flash('form_errors', FormHelper::errors());
            $this->redirect('/bienes/nuevo');
        }

        $id = $this->model->create($_POST);
        SessionHelper::flash('success', 'Bien registrado en inventario.');
        $this->redirect('/bienes/' . $id);
    }

    public function show(int $id): void {
        $this->requireAuth();
        $bien = $this->model->findById($id);
        if (!$bien) { http_response_code(404); exit; }

        $movimientos = (new MovimientoModel())->getByBien($id);

        $this->render('Control_Bienes/bienes/show', [
            'pageTitle'   => 'Detalle del Bien',
            'bien'        => $bien,
            'movimientos' => $movimientos,
            'csrf'        => $this->csrfToken(),
        ]);
    }

    public function edit(int $id): void {
        $this->requireAuth();
        $bien = $this->model->findById($id);
        if (!$bien) { http_response_code(404); exit; }

        $cats   = $this->model->getCategorias();
        $deptos = (new EmpleadoModel())->getDepartamentos();

        $this->render('Control_Bienes/bienes/form', [
            'pageTitle'  => 'Editar Bien',
            'bien'       => $bien,
            'categorias' => $cats,
            'deptos'     => $deptos,
            'csrf'       => $this->csrfToken(),
        ]);
    }

    public function update(int $id): void {
        $this->requireAuth();
        $this->verifyCsrf();

        $this->model->update($id, $_POST);
        SessionHelper::flash('success', 'Bien actualizado.');
        $this->redirect('/bienes/' . $id);
    }

    public function darBaja(int $id): void {
        $this->requireAuth();
        $this->requireLevel(2);
        $this->verifyCsrf();

        $motivo = trim($_POST['motivo'] ?? '');
        if ($motivo === '') {
            SessionHelper::flash('error', 'Ingrese el motivo de baja.');
            $this->redirect('/bienes/' . $id);
        }

        $this->model->darBaja($id, $motivo);
        SessionHelper::flash('success', 'Bien dado de baja.');
        $this->redirect('/bienes');
    }
}
