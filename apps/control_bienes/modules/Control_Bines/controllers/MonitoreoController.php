<?php
/**
 * MONITOREOCONTROLLER.PHP - Controlador del Módulo de Bodega (Ingresos y Egresos)
 */

require_once ROOT_PATH . 'modules/Control_Bines/models/InvIngreso.php';
require_once ROOT_PATH . 'modules/Control_Bines/models/InvEgreso.php';
require_once ROOT_PATH . 'modules/Control_Bines/models/BinModel.php';
require_once ROOT_PATH . 'modules/Talento_Humano/models/EmpleadoModel.php';
require_once ROOT_PATH . 'modules/Central/models/InvPeriodo.php';
require_once ROOT_PATH . 'modules/Central/models/NotificacionModel.php';
require_once ROOT_PATH . 'modules/Bitacoras/models/LogModel.php';

class MonitoreoController extends Controller {
    private $ingresoModel;
    private $egresoModel;
    private $inventarioModel;
    private $talentoModel;
    private $periodoModel;
    private $logger;

    public function __construct() {
        parent::__construct();
        $this->logger = new Logger('bod');
        $this->ingresoModel = new InvIngreso();
        $this->egresoModel = new InvEgreso();
        $this->inventarioModel = new BinModel();
        $this->talentoModel = new EmpleadoModel();
        $this->periodoModel = new InvPeriodo();
    }

    /**
     * Historial de Ingresos a Bodega
     */
    public function ingresos() {
        $this->registrarAuditoria('ACCESO', 'bod', 'Acceso al módulo de Ingresos a Bodega');

        $filtros = [
            'fecha_inicio'   => isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : '',
            'fecha_fin'      => isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : '',
            'proveedor'      => isset($_GET['proveedor']) ? $_GET['proveedor'] : '',
            'responsable_id' => isset($_GET['responsable_id']) ? $_GET['responsable_id'] : '',
            'termino'        => isset($_GET['termino']) ? $_GET['termino'] : ''
        ];

        $ingresos = $this->ingresoModel->filtrar($filtros);
        $periodoActivo = $this->periodoModel->obtenerPeriodoActivo();
        $personal = $this->talentoModel->obtenerPersonal();
        $itemsInventario = $this->inventarioModel->obtenerActivos();

        $db = Database::getInstance()->getConnection();
        $proveedores = $db->query("SELECT * FROM inv_proveedores ORDER BY nombre ASC")->fetchAll();

        $this->render('monitoreo/ingresos', [
            'ingresos'        => $ingresos,
            'personal'        => $personal,
            'itemsInventario' => $itemsInventario,
            'proveedores'     => $proveedores,
            'filtros'         => $filtros,
            'periodoActivo'   => $periodoActivo
        ], 'Ingresos a Bodega - Sistema Portuario');
    }

    /**
     * Historial de Egresos de Bodega
     */
    public function egresos() {
        $this->registrarAuditoria('ACCESO', 'bod', 'Acceso al módulo de Egresos de Bodega');

        $filtros = [
            'fecha_inicio'   => isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : '',
            'fecha_fin'      => isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : '',
            'area_id'        => isset($_GET['area_id']) ? $_GET['area_id'] : '',
            'responsable_id' => isset($_GET['responsable_id']) ? $_GET['responsable_id'] : '',
            'termino'        => isset($_GET['termino']) ? $_GET['termino'] : ''
        ];

        $egresos = $this->egresoModel->filtrar($filtros);
        $periodoActivo = $this->periodoModel->obtenerPeriodoActivo();
        $personal = $this->talentoModel->obtenerPersonal();
        $areas = $this->talentoModel->obtenerAreas();

        $db = Database::getInstance()->getConnection();
        $centrosConsumo = $db->query("SELECT cc.*, gcc.nombre as grupo_nombre 
                                      FROM inv_centros_consumo cc 
                                      JOIN inv_grupo_centros_consumo gcc ON cc.grupo_id = gcc.id 
                                      ORDER BY cc.nombre ASC")->fetchAll();
        
        $itemsInventario = $this->inventarioModel->obtenerActivos();

        $this->render('monitoreo/egresos', [
            'egresos'         => $egresos,
            'personal'        => $personal,
            'areas'           => $areas,
            'centrosConsumo'  => $centrosConsumo,
            'itemsInventario' => $itemsInventario,
            'filtros'         => $filtros,
            'periodoActivo'   => $periodoActivo
        ], 'Egresos de Bodega - Sistema Portuario');
    }

    /**
     * Guarda un registro (Ingreso o Egreso) según la ruta
     */
    public function guardar() {
        if (isset($_GET['route']) && $_GET['route'] === 'ingresos') {
            return $this->guardarIngreso();
        } else {
            return $this->guardarEgreso();
        }
    }

    private function guardarIngreso() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('ingresos', 'Método no permitido', 'error');
        }

        $datosCabecera = [
            'proveedor'      => trim($_POST['proveedor']),
            'fecha'          => !empty($_POST['fecha']) ? $_POST['fecha'] : date('Y-m-d'),
            'responsable_id' => (int)$_POST['responsable_id'],
            'observaciones'  => trim($_POST['observaciones']),
            'creado_por'     => $_SESSION['usuario']['nombre'] ?? 'Admin Terminal'
        ];

        $detalles = [];
        if (isset($_POST['items']) && is_array($_POST['items'])) {
            foreach ($_POST['items'] as $item) {
                if (!empty($item['item_id']) && isset($item['cantidad'])) {
                    $detalles[] = [
                        'item_id'        => (int)$item['item_id'],
                        'cantidad'       => (int)$item['cantidad'],
                        'valor_unitario' => (float)$item['valor_unitario']
                    ];
                }
            }
        }

        if (empty($detalles)) {
            $this->redirect('ingresos', 'Debe agregar al menos un insumo con cantidad válida.', 'error');
        }

        try {
            $ingresoId = $this->ingresoModel->crear($datosCabecera, $detalles);
            $this->logger->info('CREAR_INGRESO: Ingreso de bodega registrado', 'guardar');
            
            $ingresoGuardado = $this->ingresoModel->buscarPorId($ingresoId);
            if ($ingresoGuardado) {
                $_notifModel = new NotificacionModel();
                $_notifModel->crear('info', 'ingreso', 'Ingreso Registrado', "Se ha registrado el ingreso Nro. <strong>{$ingresoGuardado['secuencial']}</strong> de la bodega por compra al proveedor <strong>{$datosCabecera['proveedor']}</strong>.", $ingresoGuardado['secuencial']);
            }

            $this->redirect('ingresos', 'Ingreso de bodega registrado exitosamente', 'success');
        } catch (Exception $e) {
            $this->logger->inv_error('Error al guardar ingreso de bodega', $e, 'guardar');
            $this->redirect('ingresos', 'Error al guardar el ingreso', 'error');
        }
    }

    private function guardarEgreso() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('egresos', 'Método no permitido', 'error');
        }

        $datosCabecera = [
            'area_id'        => (int)$_POST['area_id'],
            'responsable_id' => (int)$_POST['responsable_id'],
            'fecha'          => !empty($_POST['fecha']) ? $_POST['fecha'] : date('Y-m-d'),
            'motivo'         => trim($_POST['motivo']),
            'observaciones'  => trim($_POST['observaciones']),
            'creado_por'     => $_SESSION['usuario']['nombre'] ?? 'Admin Terminal'
        ];

        $detalles = [];
        if (isset($_POST['items']) && is_array($_POST['items'])) {
            foreach ($_POST['items'] as $item) {
                if (!empty($item['item_id']) && isset($item['cantidad'])) {
                    $detalles[] = [
                        'item_id'  => (int)$item['item_id'],
                        'cantidad' => (int)$item['cantidad']
                    ];
                }
            }
        }

        if (empty($detalles)) {
            $this->redirect('egresos', 'Debe agregar al menos un insumo con cantidad válida.', 'error');
        }

        try {
            $egresoId = $this->egresoModel->crear($datosCabecera, $detalles);
            $this->logger->info('CREAR_EGRESO: Egreso de bodega registrado', 'guardar');
            
            $egresoGuardado = $this->egresoModel->buscarPorId($egresoId);
            if ($egresoGuardado) {
                $_notifModel = new NotificacionModel();
                $_notifModel->crear('info', 'egreso', 'Egreso Registrado', "Se ha registrado el egreso Nro. <strong>{$egresoGuardado['secuencial']}</strong> con destino al Centro de Consumo <strong>{$egresoGuardado['area_destino']}</strong>.", $egresoGuardado['secuencial']);
            }

            $this->redirect('egresos', 'Egreso de bodega registrado exitosamente', 'success');
        } catch (Exception $e) {
            $this->logger->inv_error('Error al procesar egreso de bodega', $e, 'guardar');
            $this->redirect('egresos', 'Error al procesar el egreso', 'error');
        }
    }

    /**
     * Consulta detalle en JSON
     */
    public function verDetalle() {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if (isset($_GET['route']) && $_GET['route'] === 'ingresos') {
            $ingreso = $this->ingresoModel->buscarPorId($id);
            if ($ingreso) {
                $this->registrarAuditoria('CONSULTA', 'bod', 'Detalle consultado de Ingreso: ' . $ingreso['secuencial']);
                $this->jsonResponse($ingreso);
            } else {
                $this->jsonResponse(['error' => 'Registro no encontrado'], 404);
            }
        } else {
            $egreso = $this->egresoModel->buscarPorId($id);
            if ($egreso) {
                $this->registrarAuditoria('CONSULTA', 'bod', 'Detalle consultado de Egreso: ' . $egreso['secuencial']);
                $this->jsonResponse($egreso);
            } else {
                $this->jsonResponse(['error' => 'Registro no encontrado'], 404);
            }
        }
    }

    /**
     * Genera Acta imprimible
     */
    public function acta() {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if (isset($_GET['route']) && $_GET['route'] === 'ingresos') {
            $ingreso = $this->ingresoModel->buscarPorId($id);
            if (!$ingreso) {
                die("Error: El ingreso a bodega solicitado no existe.");
            }
            $this->registrarAuditoria('EXPORTAR', 'bod', 'Generado documento impreso Acta de Ingreso: ' . $ingreso['secuencial']);
            $this->renderActa('inv_acta_ingreso', ['ingreso' => $ingreso]);
        } else {
            $egreso = $this->egresoModel->buscarPorId($id);
            if (!$egreso) {
                die("Error: El egreso de bodega solicitado no existe.");
            }
            $this->registrarAuditoria('EXPORTAR', 'bod', 'Generado documento impreso Acta de Egreso: ' . $egreso['secuencial']);
            $this->renderActa('inv_acta_egreso', ['egreso' => $egreso]);
        }
    }
}
class_alias('MonitoreoController', 'InvIngresosController');
class_alias('MonitoreoController', 'InvEgresosController');
