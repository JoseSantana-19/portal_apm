<?php

class VacacionesController extends Controller
{
    private VacacionesModel $modelo;

    public function __construct()
    {
        require_once ROOT . '/modules/talento-humano/Modelos/VacacionesModel.php';
        $this->modelo = new VacacionesModel();
    }

    public function index(): void
    {
        $estado = strtoupper(trim((string)($_GET['estado'] ?? '')));
        $empleadoId = (int)($_GET['empleado_id'] ?? 0);
        $this->cargarVista('talento-humano', 'vacaciones', [
            'vacaciones' => $this->modelo->listar($estado ?: null, $empleadoId ?: null),
            'resumen' => $this->modelo->resumen(),
            'estadoFiltro' => $estado,
        ]);
    }
}
