<?php

final class MaestroController extends Controller
{
    private MaestroModel $model;

    public function __construct()
    {
        require_once ROOT . '/modules/admin/Modelos/MaestroModel.php';
        $this->model = new MaestroModel();
    }

    public function index(): void
    {
        $unidades = $this->model->unidades();
        $puestos = $this->model->puestos();
        $unidadEditar = $this->buscar($unidades, 'unidad_id', (int)($_GET['unidad_id'] ?? 0));
        $puestoEditar = $this->buscar($puestos, 'puesto_id', (int)($_GET['puesto_id'] ?? 0));
        $this->cargarVista('admin', 'maestros', compact('unidades', 'puestos', 'unidadEditar', 'puestoEditar'));
    }

    public function guardarUnidad(): void
    {
        $this->validarPost();
        $result = $this->model->guardarUnidad($_POST);
        $this->redirect($result);
    }

    public function guardarPuesto(): void
    {
        $this->validarPost();
        $result = $this->model->guardarPuesto($_POST);
        $this->redirect($result);
    }

    private function validarPost(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            ErrorHandler::abort(405);
        }
        Auth::requireCsrf($_POST['_csrf'] ?? null);
    }

    private function redirect(array $result): void
    {
        $ok = (int)($result['exito'] ?? 0) === 1;
        header('Location: ' . BASE_URL . '/admin/maestros?msg=' . urlencode((string)($result['mensaje'] ?? 'Operacion finalizada.')) . '&ok=' . ($ok ? '1' : '0'));
        exit;
    }

    private function buscar(array $rows, string $key, int $id): ?array
    {
        foreach ($rows as $row) {
            if ((int)$row[$key] === $id) return $row;
        }
        return null;
    }
}
