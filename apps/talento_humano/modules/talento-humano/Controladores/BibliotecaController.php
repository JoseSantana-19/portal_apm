<?php
// modules/talento-humano/Controladores/BibliotecaController.php
// Módulo: Biblioteca de Formularios – Vista centralizada de todos los formularios del sistema

class BibliotecaController extends Controller
{
    /** GET /talento-humano/biblioteca – Vista de la biblioteca de formularios */
    public function index(): void
    {
        // Cargar lista de empleados para los modales de "Ver registros"
        require_once ROOT . '/modules/talento-humano/Modelos/EmpleadoModel.php';
        require_once ROOT . '/modules/talento-humano/Modelos/AccionPersonalModel.php';
        require_once ROOT . '/modules/talento-humano/Modelos/EstudioSeguridadModel.php';
        $modelo = new EmpleadoModel();
        $accionesModel = new AccionPersonalModel();
        $estudiosModel = new EstudioSeguridadModel();

        $datos = [
            'usuarioNombre' => $_SESSION['nombre'] ?? 'USUARIO APM',
            'usuarioRol'    => $_SESSION['rol']    ?? 'Administrador TH',
            'empleados'     => $modelo->listarDirectorio(),
            'acciones'      => $accionesModel->listar(),
            'estudios'      => $estudiosModel->listar(Auth::username(),Auth::clientIp()),
        ];
        $this->cargarVista('talento-humano', 'biblioteca', $datos);
    }
}
