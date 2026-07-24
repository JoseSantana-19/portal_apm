<?php
/**
 * MaestrosController — Arquitectura de datos del inventario (tablas maestras).
 * Estructura MVC nativa de portal_apm. Datos vía sqlsrv nativo (sin PDO).
 */
class MaestrosController extends Controller
{
    private MaestroModel $maestro;

    public function __construct()
    {
        $this->maestro = new MaestroModel();
    }

    public function index(): void
    {
        $this->requireAuth();
        $tabla = $_GET['tabla'] ?? 'categorias';
        if (!in_array($tabla, $this->maestro->tablasDisponibles(), true)) {
            $tabla = 'categorias';
        }

        $this->render('Inventario/maestros', [
            'pageTitle' => 'Maestros del Inventario',
            'tablaActiva' => $tabla,
            'registros'   => $this->maestro->obtenerTodos($tabla),
            'conteos'     => $this->maestro->obtenerConteos(),
            'categorias'  => $this->maestro->obtenerTodos('categorias'),
            'unidades'    => $this->maestro->obtenerTodos('unidades'),
            'csrf'        => $this->csrfToken(),
        ]);
    }

    public function guardar(): void
    {
        $this->requireAuth();
        $this->verifyCsrf();

        $tabla = $_POST['tabla'] ?? 'categorias';
        $id    = (int)($_POST['id'] ?? 0);
        $d     = [
            'nombre'   => trim($_POST['nombre'] ?? ''),
            'extra'    => trim($_POST['extra'] ?? ''),
            'codigo'   => trim($_POST['codigo'] ?? ''),
            'ruc'      => trim($_POST['ruc'] ?? ''),
            'tasa_iva' => $_POST['tasa_iva'] ?? 0,
            'grupo_id' => (int)($_POST['grupo_id'] ?? 0),
            'unidad_id'=> (int)($_POST['unidad_id'] ?? 0),
            'aplica_iva'=> (int)($_POST['aplica_iva'] ?? 1),
        ];

        try {
            if ($id > 0) {
                $this->maestro->actualizar($tabla, $id, $d);
                SessionHelper::flash('success', 'Registro maestro actualizado.');
            } else {
                $this->maestro->crear($tabla, $d);
                SessionHelper::flash('success', 'Registro maestro creado.');
            }
        } catch (Throwable $e) {
            SessionHelper::flash('error', 'No se pudo guardar: ' . $e->getMessage());
        }
        $this->redirect('/inventario/maestros?tabla=' . urlencode($tabla));
    }

    public function eliminar(): void
    {
        $this->requireAuth();
        $this->verifyCsrf();
        $tabla = $_POST['tabla'] ?? '';
        $id    = (int)($_POST['id'] ?? 0);
        try {
            $this->maestro->eliminar($tabla, $id);
            SessionHelper::flash('success', 'Registro eliminado.');
        } catch (Throwable $e) {
            SessionHelper::flash('error', 'No se pudo eliminar (puede estar en uso).');
        }
        $this->redirect('/inventario/maestros?tabla=' . urlencode($tabla));
    }
}
