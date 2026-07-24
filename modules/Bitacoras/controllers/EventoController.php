<?php
class EventoController extends Controller {

    private BitacoraModel $model;

    public function __construct() {
        parent::__construct();
        $this->model = new BitacoraModel();
    }

    public function index(): void {
        $this->requireAuth();
        $page    = max(1, (int)($_GET['pagina'] ?? 1));
        $filters = [
            'estado'    => $this->input('estado',    'get'),
            'categoria' => $this->input('categoria', 'get'),
        ];
        $total    = $this->model->count($filters);
        $eventos  = $this->model->getAll($page, 20, $filters);
        $categorias = $this->model->getCategorias();

        $this->render('Bitacoras/eventos/index', [
            'pageTitle'  => 'Bitácoras',
            'eventos'    => $eventos,
            'categorias' => $categorias,
            'total'      => $total,
            'page'       => $page,
            'filters'    => $filters,
        ]);
    }

    public function create(): void {
        $this->requireAuth();
        $categorias = $this->model->getCategorias();
        $this->render('Bitacoras/eventos/form', [
            'pageTitle'  => 'Nueva Bitácora',
            'evento'     => null,
            'categorias' => $categorias,
            'csrf'       => $this->csrfToken(),
        ]);
    }

    public function store(): void {
        $this->requireAuth();
        $this->verifyCsrf();

        $rules = [
            'titulo'      => 'required|min:3|max:200',
            'descripcion' => 'required|min:5',
            'id_categoria'=> 'required|numeric',
            'fecha_evento'=> 'required',
        ];
        if (!FormHelper::validate($_POST, $rules)) {
            FormHelper::flashOld($_POST);
            SessionHelper::flash('form_errors', FormHelper::errors());
            $this->redirect('/bitacoras/nuevo');
        }

        $id = $this->model->create($_POST);
        SessionHelper::flash('success', 'Evento registrado en bitácora.');
        $this->redirect('/bitacoras/' . $id);
    }

    public function show(int $id): void {
        $this->requireAuth();
        $evento = $this->model->findById($id);
        if (!$evento) { http_response_code(404); exit; }

        $this->render('Bitacoras/eventos/show', [
            'pageTitle' => 'Detalle Bitácora',
            'evento'    => $evento,
            'csrf'      => $this->csrfToken(),
        ]);
    }

    public function edit(int $id): void {
        $this->requireAuth();
        $evento     = $this->model->findById($id);
        if (!$evento) { http_response_code(404); exit; }
        $categorias = $this->model->getCategorias();

        $this->render('Bitacoras/eventos/form', [
            'pageTitle'  => 'Editar Bitácora',
            'evento'     => $evento,
            'categorias' => $categorias,
            'csrf'       => $this->csrfToken(),
        ]);
    }

    public function update(int $id): void {
        $this->requireAuth();
        $this->verifyCsrf();

        $evento = $this->model->findById($id);
        if (!$evento) { http_response_code(404); exit; }

        $this->model->update($id, $_POST);
        SessionHelper::flash('success', 'Bitácora actualizada.');
        $this->redirect('/bitacoras/' . $id);
    }

    public function close(int $id): void {
        $this->requireAuth();
        $this->verifyCsrf();

        $observaciones = trim($_POST['observaciones'] ?? '');
        if ($observaciones === '') {
            SessionHelper::flash('error', 'Ingrese la resolución para cerrar la bitácora.');
            $this->redirect('/bitacoras/' . $id);
        }

        $this->model->close($id, $observaciones);
        SessionHelper::flash('success', 'Bitácora cerrada correctamente.');
        $this->redirect('/bitacoras/' . $id);
    }
}
