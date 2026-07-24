<?php
// modules/talento-humano/Controladores/EstudioSeguridadController.php
// Módulo: Formato Estudio de Seguridad Socioeconómico – APM-BASC-TH-FO-002

class EstudioSeguridadController extends Controller
{
    /** GET /talento-humano/estudio-seguridad – Formulario vacío o con datos de empleado */
    public function index(): void
    {
        $empleadoId = $_GET['id'] ?? null;
        $empleado   = [];

        // Si se proporciona ID, intentar cargar datos del empleado
        if ($empleadoId) {
            // Aquí se conectaría al modelo cuando exista la tabla; por ahora mock
            $empleado = ['id' => $empleadoId];
        }

        $datos = [
            'empleado'      => $empleado,
            'usuarioNombre' => $_SESSION['nombre'] ?? 'USUARIO APM',
            'usuarioRol'    => $_SESSION['rol']    ?? 'Administrador TH',
            'codigoFormato' => 'APM-BASC-TH-FO-002',
            'fechaFormato'  => '01/04/2019',
        ];
        $this->cargarVista('talento-humano', 'estudio_seguridad', $datos);
    }

    /** POST /talento-humano/estudio-seguridad/guardar */
    public function guardar(): void
    {
        // Placeholder: aquí se procesaría el guardado del formulario
        header('Location: ' . BASE_URL . '/talento-humano/biblioteca');
        exit;
    }

    /** GET /talento-humano/estudio-seguridad/imprimir – Vista previa para impresión */
    public function imprimir(): void
    {
        $empleadoId = $_GET['id'] ?? null;
        $empleado   = ['id' => $empleadoId];

        $datos = [
            'empleado'      => $empleado,
            'modoImpresion' => true,
            'codigoFormato' => 'APM-BASC-TH-FO-002',
            'fechaFormato'  => '01/04/2019',
        ];
        $this->cargarVista('talento-humano', 'estudio_seguridad', $datos);
    }
}
