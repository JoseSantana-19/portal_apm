<?php
/**
 * ESTACIONCONTROLLER.PHP - Controlador de Cabeceras Maestras (Zonas, Categorías, Unidades, etc.)
 */

require_once ROOT_PATH . 'modules/Control_Bines/models/EstacionModel.php';
require_once ROOT_PATH . 'modules/Central/models/NotificacionModel.php';
require_once ROOT_PATH . 'modules/Bitacoras/models/LogModel.php';

class EstacionController extends Controller {
    private $cabeceraModel;
    private $logger;

    public function __construct() {
        parent::__construct();
        $this->logger = new Logger('cab');
        $this->cabeceraModel = new EstacionModel();
    }

    /**
     * Vista principal de Tablas Maestras
     */
    public function index() {
        $this->registrarAuditoria('ACCESO', 'th', 'Acceso al módulo de Tablas de Cabecera');
        
        $tablaActiva = isset($_GET['tabla']) ? $_GET['tabla'] : 'categorias';
        
        try {
            $items = $this->cabeceraModel->obtenerTodos($tablaActiva);
            $conteos = $this->cabeceraModel->obtenerConteos();
            
            $categoriasList = [];
            $unidadesList = [];
            if ($tablaActiva === 'productos') {
                $categoriasList = $this->cabeceraModel->obtenerTodos('categorias');
                $unidadesList = $this->cabeceraModel->obtenerTodos('unidades');
            }
        } catch (Exception $e) {
            $this->redirect('cabeceras', 'Error al cargar: ' . $e->getMessage(), 'error');
        }

        $this->render('estaciones/listar', [
            'items' => $items,
            'conteos' => $conteos,
            'tablaActiva' => $tablaActiva,
            'categoriasList' => $categoriasList,
            'unidadesList' => $unidadesList
        ], 'Tablas de Cabecera - Sistema Portuario');
    }

    /**
     * Vista de Maestros
     */
    public function maestros() {
        $this->registrarAuditoria('ACCESO', 'inv_maestros', 'Acceso al módulo de Maestros');

        $tablaActiva = isset($_GET['tabla']) ? $_GET['tabla'] : 'categorias';

        $tablasPermitidas = ['categorias', 'productos', 'unidades', 'tipos_iva', 'proveedores', 'grupo_centros_consumo', 'centros_consumo', 'busqueda_global'];
        if (!in_array($tablaActiva, $tablasPermitidas)) {
            $tablaActiva = 'categorias';
        }

        $items = [];
        $categoriasList = [];
        $unidadesList   = [];
        $grupoCentrosList = [];
        $tiposIvaList = [];
        $resultados = [];
        
        $q = isset($_GET['q']) ? trim($_GET['q']) : '';
        $modulosSeleccionados = isset($_GET['modulos']) && is_array($_GET['modulos']) ? $_GET['modulos'] : [];
        if (empty($modulosSeleccionados)) {
            $modulosSeleccionados = ['inventario', 'bodega', 'inv_maestros', 'usuarios', 'inv_bitacora'];
        }
        
        $campoBusqueda = isset($_GET['campo']) ? trim($_GET['campo']) : 'todos';
        $limiteResultados = isset($_GET['limite']) ? (int)$_GET['limite'] : 50;

        try {
            $conteos = $this->cabeceraModel->obtenerConteos();

            if ($tablaActiva !== 'busqueda_global') {
                $items = $this->cabeceraModel->obtenerTodos($tablaActiva);
                
                $db = Database::getInstance()->getConnection();
                $ivasStmt = $db->query("SELECT * FROM inv_tipos_iva ORDER BY tasa_iva DESC");
                $tiposIvaList = $ivasStmt->fetchAll();

                if ($tablaActiva === 'productos') {
                    $categoriasList = $this->cabeceraModel->obtenerTodos('categorias');
                    $unidadesList   = $this->cabeceraModel->obtenerTodos('unidades');
                } elseif ($tablaActiva === 'centros_consumo') {
                    $grupoCentrosList = $this->cabeceraModel->obtenerTodos('grupo_centros_consumo');
                }
            } else {
                if (strlen($q) >= 2) {
                    require_once ROOT_PATH . 'modules/Central/models/BusquedaGlobalModel.php';
                    $globalSearchModel = new BusquedaGlobalModel();
                    $resultados = $globalSearchModel->buscarConFiltros($q, $modulosSeleccionados, $campoBusqueda, $limiteResultados);
                    $this->registrarAuditoria('CONSULTA', 'inv_maestros', "Búsqueda global desde Maestros: '{$q}' en " . implode(', ', $modulosSeleccionados));
                }
            }
        } catch (Exception $e) {
            $this->redirect('inv_maestros', 'Error al cargar: ' . $e->getMessage(), 'error');
        }

        $this->render('estaciones/maestros', [
            'items'            => $items,
            'conteos'          => $conteos,
            'tablaActiva'      => $tablaActiva,
            'categoriasList'   => $categoriasList,
            'unidadesList'     => $unidadesList,
            'tiposIvaList'     => $tiposIvaList,
            'grupoCentrosList' => $grupoCentrosList,
            'q'                => $q,
            'modulosSeleccionados' => $modulosSeleccionados,
            'resultados'       => $resultados,
            'campoBusqueda'    => $campoBusqueda,
            'limiteResultados' => $limiteResultados
        ], 'Maestros - Sistema Portuario');
    }

    /**
     * Filtra una tabla específica y retorna JSON (usado por AJAX)
     */
    public function filtrarAjax() {
        $tabla = isset($_GET['tabla']) ? $_GET['tabla'] : 'categorias';
        
        try {
            $items = $this->cabeceraModel->obtenerTodos($tabla);
            $this->jsonResponse($items);
        } catch (Exception $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Guarda o edita un registro maestro (POST)
     */
    public function guardar() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('cabeceras', 'Método no permitido', 'error');
        }

        $tabla = isset($_POST['tabla']) ? trim($_POST['tabla']) : '';
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        
        $datos = [
            'nombre' => trim($_POST['nombre']),
            'extra' => isset($_POST['extra']) ? trim($_POST['extra']) : ''
        ];

        if ($tabla === 'estados') {
            $datos['clase'] = isset($_POST['clase']) ? trim($_POST['clase']) : 'active';
        } elseif ($tabla === 'categorias') {
            $datos['codigo'] = isset($_POST['codigo']) ? trim($_POST['codigo']) : '';
        } elseif ($tabla === 'productos') {
            $datos['grupo_id'] = isset($_POST['grupo_id']) ? (int)$_POST['grupo_id'] : 0;
            $datos['unidad_id'] = isset($_POST['unidad_id']) ? (int)$_POST['unidad_id'] : 0;
            $datos['aplica_iva'] = isset($_POST['aplica_iva']) ? (int)$_POST['aplica_iva'] : 1;
        } elseif ($tabla === 'tipos_iva') {
            $datos['tasa_iva'] = isset($_POST['tasa_iva']) ? (float)$_POST['tasa_iva'] : 0.0;
        } elseif ($tabla === 'proveedores') {
            $datos['ruc'] = isset($_POST['ruc']) ? trim($_POST['ruc']) : '';
        } elseif ($tabla === 'grupo_centros_consumo') {
            $datos['codigo'] = isset($_POST['codigo']) ? trim($_POST['codigo']) : '';
            $datos['representante'] = isset($_POST['representante']) ? trim($_POST['representante']) : '';
        } elseif ($tabla === 'centros_consumo') {
            $datos['grupo_id'] = isset($_POST['grupo_id']) ? (int)$_POST['grupo_id'] : 0;
            $datos['codigo'] = isset($_POST['codigo']) ? trim($_POST['codigo']) : '';
            $datos['funcionario'] = isset($_POST['funcionario']) ? trim($_POST['funcionario']) : '';
        }

        $referrer = $_SERVER['HTTP_REFERER'] ?? '';
        $redirectRoute = (strpos($referrer, 'route=inv_maestros') !== false) ? 'inv_maestros' : 'cabeceras';

        try {
            if ($id > 0) {
                $registro = $this->cabeceraModel->actualizar($tabla, $id, $datos);
                $this->registrarAuditoria('ACTUALIZAR', 'th', "Registro actualizado en {$tabla}: ID {$id} - {$datos['nombre']}");
                
                if (isset($_POST['is_ajax'])) {
                    $this->jsonResponse(['success' => true, 'mensaje' => 'Registro actualizado', 'registro' => $registro]);
                }
                $this->redirect($redirectRoute . '&tabla=' . $tabla, 'Registro actualizado exitosamente', 'success');
            } else {
                $registro = $this->cabeceraModel->crear($tabla, $datos);
                $this->registrarAuditoria('CREAR', 'th', "Nuevo registro creado en {$tabla}: {$datos['nombre']}");
                
                $_notifModel = new NotificacionModel();
                if ($tabla === 'proveedores') {
                    $_notifModel->crear('info', 'maestro', 'Nuevo Proveedor', "Se registró al proveedor oficial <strong>{$datos['nombre']}</strong> con RUC <strong>{$datos['ruc']}</strong>.");
                } elseif ($tabla === 'productos') {
                    $_notifModel->crear('info', 'maestro', 'Nuevo Producto', "Se ha creado el ítem <strong>{$datos['nombre']}</strong> en el catálogo de productos.");
                } elseif ($tabla === 'centros_consumo') {
                    $_notifModel->crear('info', 'maestro', 'Nuevo Centro Consumo', "Se ha creado el centro de consumo <strong>{$datos['nombre']}</strong> asignado a <strong>{$datos['funcionario']}</strong>.");
                } elseif ($tabla === 'grupo_centros_consumo') {
                    $_notifModel->crear('info', 'maestro', 'Nuevo Grupo Consumo', "Se ha creado el departamento <strong>{$datos['nombre']}</strong> con código <strong>{$datos['codigo']}</strong>.");
                } else {
                    $_notifModel->crear('info', 'maestro', 'Maestro Registrado', "Se ha añadido el registro <strong>{$datos['nombre']}</strong> en la tabla maestra '{$tabla}'.");
                }

                if (isset($_POST['is_ajax'])) {
                    $this->jsonResponse(['success' => true, 'mensaje' => 'Registro creado', 'registro' => $registro]);
                }
                $this->redirect($redirectRoute . '&tabla=' . $tabla, 'Registro creado exitosamente', 'success');
            }
        } catch (Exception $e) {
            $this->logger->inv_error("Error en guardar cabecera tabla={$tabla}", $e, 'guardar');
            if (isset($_POST['is_ajax'])) {
                $this->jsonResponse(['success' => false, 'mensaje' => $e->getMessage()], 500);
            }
            $this->redirect($redirectRoute . '&tabla=' . $tabla, 'Error al guardar: ' . $e->getMessage(), 'error');
        }
    }

    /**
     * Elimina un registro de una tabla maestra
     */
    public function eliminar() {
        $tabla = isset($_GET['tabla']) ? trim($_GET['tabla']) : '';
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

        try {
            $registro = $this->cabeceraModel->buscarPorId($tabla, $id);
            if ($registro) {
                $this->cabeceraModel->eliminar($tabla, $id);
                $this->registrarAuditoria('ELIMINAR', 'th', "Registro eliminado de {$tabla}: {$registro['nombre']}");
                
                if (isset($_GET['is_ajax'])) {
                    $this->jsonResponse(['success' => true, 'mensaje' => 'Registro eliminado']);
                }
                $this->redirect('cabeceras&tabla=' . $tabla, 'Registro eliminado exitosamente', 'warning');
            } else {
                throw new Exception("Registro no encontrado");
            }
        } catch (Exception $e) {
            $this->logger->inv_error("Error al eliminar de tabla={$tabla} id={$id}", $e, 'eliminar');
            if (isset($_GET['is_ajax'])) {
                $this->jsonResponse(['success' => false, 'mensaje' => $e->getMessage()], 404);
            }
            $this->redirect('cabeceras&tabla=' . $tabla, 'Error al eliminar', 'error');
        }
    }

    /**
     * Endpoint AJAX para realizar búsquedas globales en las tablas maestras
     */
    public function buscarGeneralAjax() {
        $q = isset($_GET['q']) ? trim($_GET['q']) : '';
        
        if (strlen($q) < 2) {
            $this->jsonResponse(['error' => 'La búsqueda debe tener al menos 2 caracteres'], 400);
        }

        try {
            $resultados = $this->cabeceraModel->buscarGeneral($q);
            $this->jsonResponse($resultados);
        } catch (Exception $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }
}
class_alias('EstacionController', 'InvCabeceraController');
