<?php
/**
 * ReporteController.php - Controlador del Módulo de Reportes Varios
 */

require_once ROOT_PATH . 'modules/Bitacoras/models/LogModel.php';

class ReporteController extends Controller {
    private $db;
    private $logger;

    public function __construct() {
        parent::__construct();
        $this->logger = new Logger('rep');
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Vista Principal del Módulo de Reportes Varios
     */
    public function index() {
        $this->registrarAuditoria('ACCESO', 'rep', 'Acceso al módulo de Reportes Varios');

        $tabActivo = isset($_GET['tab']) ? trim($_GET['tab']) : 'proveedores';
        $tablasValidas = ['proveedores', 'centros_consumo', 'items', 'compras', 'mensual'];
        if (!in_array($tabActivo, $tablasValidas)) {
            $tabActivo = 'proveedores';
        }

        // No aplicar fechas implícitas: impedían encontrar compras antiguas por ID.
        $fechaInicio = $this->normalizarFecha(isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : '');
        $fechaFin    = $this->normalizarFecha(isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : '');
        $proveedor   = isset($_GET['proveedor'])   ? trim($_GET['proveedor']) : '';
        $termino     = isset($_GET['termino'])     ? trim($_GET['termino']) : '';
        $idExacto    = $this->normalizarId(isset($_GET['id_exacto']) ? $_GET['id_exacto'] : '');
        $idInicio    = $this->normalizarId(isset($_GET['id_inicio']) ? $_GET['id_inicio'] : '');
        $idFin       = $this->normalizarId(isset($_GET['id_fin']) ? $_GET['id_fin'] : '');
        $this->normalizarRangos($idExacto, $idInicio, $idFin, $fechaInicio, $fechaFin);
        if ($tabActivo === 'mensual') {
            $idExacto = '';
        }

        $generarReporte = isset($_GET['generar']) && (int)$_GET['generar'] === 1;

        $datosReporte = [];
        $proveedoresList = $this->db->query("SELECT * FROM inv_proveedores ORDER BY nombre ASC")->fetchAll();

        if ($generarReporte) {
            try {
                switch ($tabActivo) {
                    case 'proveedores':
                        $sql = "SELECT * FROM inv_proveedores WHERE 1=1";
                        $params = [];
                        if ($idExacto !== '') {
                            $sql .= " AND id = :id_exacto";
                            $params[':id_exacto'] = (int)$idExacto;
                        }
                        if (!empty($idInicio)) {
                            $sql .= " AND id >= :id_ini";
                            $params[':id_ini'] = (int)$idInicio;
                        }
                        if (!empty($idFin)) {
                            $sql .= " AND id <= :id_fin";
                            $params[':id_fin'] = (int)$idFin;
                        }
                        if (!empty($termino)) {
                            $sql .= " AND (CAST(id AS VARCHAR(20)) LIKE :term OR nombre LIKE :term OR ruc LIKE :term OR extra LIKE :term)";
                            $params[':term'] = '%' . $termino . '%';
                        }
                        $sql .= " ORDER BY id ASC";
                        $stmt = $this->db->prepare($sql);
                        $stmt->execute($params);
                        $datosReporte = $stmt->fetchAll();
                        break;

                    case 'centros_consumo':
                        $sql = "SELECT cc.*, COALESCE(p.nombre, cc.funcionario) AS funcionario_actual, gcc.nombre as grupo_nombre, gcc.codigo as grupo_codigo,
                                       (SELECT COUNT(*) FROM inv_bod_egresos e WHERE e.area_id = cc.id) as total_egresos,
                                       (SELECT COALESCE(SUM(ed.cantidad), 0) FROM inv_bod_egresos_detalles ed JOIN inv_bod_egresos e ON ed.egreso_id = e.id WHERE e.area_id = cc.id) as total_items
                                FROM inv_centros_consumo cc
                                JOIN inv_grupo_centros_consumo gcc ON cc.grupo_id = gcc.id
                                LEFT JOIN inv_talento_personal p ON p.id = cc.funcionario_id
                                WHERE 1=1";
                        $params = [];
                        if ($idExacto !== '') {
                            $sql .= " AND cc.id = :id_exacto";
                            $params[':id_exacto'] = (int)$idExacto;
                        }
                        if (!empty($idInicio)) {
                            $sql .= " AND cc.id >= :id_ini";
                            $params[':id_ini'] = (int)$idInicio;
                        }
                        if (!empty($idFin)) {
                            $sql .= " AND cc.id <= :id_fin";
                            $params[':id_fin'] = (int)$idFin;
                        }
                        if (!empty($termino)) {
                            $sql .= " AND (CAST(cc.id AS VARCHAR(20)) LIKE :term OR cc.nombre LIKE :term OR cc.codigo LIKE :term OR COALESCE(p.nombre, cc.funcionario) LIKE :term OR gcc.nombre LIKE :term OR gcc.codigo LIKE :term)";
                            $params[':term'] = '%' . $termino . '%';
                        }
                        $sql .= " ORDER BY cc.codigo ASC";
                        $stmt = $this->db->prepare($sql);
                        $stmt->execute($params);
                        $datosReporte = $stmt->fetchAll();
                        break;

                    case 'items':
                        $sql = "SELECT i.*, cat.nombre as categoria_nombre, 
                                       p.codigo as producto_codigo, u.extra as unidad_abreviatura
                                FROM inv_inventario i
                                JOIN inv_categorias cat ON i.categoria_id = cat.id
                                LEFT JOIN inv_productos p ON i.producto_id = p.id
                                LEFT JOIN inv_unidades u ON p.unidad_id = u.id
                                WHERE i.activo = 1";
                        $params = [];
                        if ($idExacto !== '') {
                            $sql .= " AND i.id = :id_exacto";
                            $params[':id_exacto'] = (int)$idExacto;
                        }
                        if (!empty($idInicio)) {
                            $sql .= " AND i.id >= :id_ini";
                            $params[':id_ini'] = (int)$idInicio;
                        }
                        if (!empty($idFin)) {
                            $sql .= " AND i.id <= :id_fin";
                            $params[':id_fin'] = (int)$idFin;
                        }
                        if (!empty($fechaInicio)) {
                            $sql .= " AND i.fecha_registro >= :fecha_ini";
                            $params[':fecha_ini'] = $fechaInicio;
                        }
                        if (!empty($fechaFin)) {
                            $sql .= " AND i.fecha_registro <= :fecha_fin";
                            $params[':fecha_fin'] = $fechaFin;
                        }
                        if (!empty($termino)) {
                            $sql .= " AND (CAST(i.id AS VARCHAR(20)) LIKE :term OR CAST(i.producto_id AS VARCHAR(20)) LIKE :term OR i.nombre LIKE :term OR i.secuencial LIKE :term OR p.codigo LIKE :term OR i.marca LIKE :term OR cat.nombre LIKE :term)";
                            $params[':term'] = '%' . $termino . '%';
                        }
                        $sql .= " ORDER BY i.secuencial ASC";
                        $stmt = $this->db->prepare($sql);
                        $stmt->execute($params);
                        $datosReporte = $stmt->fetchAll();
                        break;

                    case 'compras':
                        $sql = "SELECT ing.id as ingreso_id, det.id as detalle_id, det.item_id,
                                       ing.secuencial as ingreso_codigo, ing.proveedor, ing.fecha, 
                                       det.cantidad, det.valor_unitario, (det.cantidad * det.valor_unitario) as subtotal,
                                       inv.nombre as item_nombre, inv.secuencial as item_secuencial,
                                       p.codigo as producto_codigo, u.extra as unidad
                                FROM inv_bod_ingresos ing
                                JOIN inv_bod_ingresos_detalles det ON ing.id = det.ingreso_id
                                JOIN inv_inventario inv ON det.item_id = inv.id
                                LEFT JOIN inv_productos p ON inv.producto_id = p.id
                                LEFT JOIN inv_unidades u ON p.unidad_id = u.id
                                WHERE 1=1";
                        $params = [];

                        if ($idExacto !== '') {
                            $sql .= " AND ing.id = :id_exacto";
                            $params[':id_exacto'] = (int)$idExacto;
                        }

                        if (!empty($proveedor)) {
                            $sql .= " AND ing.proveedor = :proveedor";
                            $params[':proveedor'] = $proveedor;
                        }
                        if (!empty($fechaInicio)) {
                            $sql .= " AND ing.fecha >= :fecha_ini";
                            $params[':fecha_ini'] = $fechaInicio;
                        }
                        if (!empty($fechaFin)) {
                            $sql .= " AND ing.fecha <= :fecha_fin";
                            $params[':fecha_fin'] = $fechaFin;
                        }
                        if (!empty($idInicio)) {
                            $sql .= " AND ing.id >= :id_ini";
                            $params[':id_ini'] = (int)$idInicio;
                        }
                        if (!empty($idFin)) {
                            $sql .= " AND ing.id <= :id_fin";
                            $params[':id_fin'] = (int)$idFin;
                        }
                        if (!empty($termino)) {
                            $sql .= " AND (CAST(ing.id AS VARCHAR(20)) LIKE :term OR CAST(det.id AS VARCHAR(20)) LIKE :term OR CAST(det.item_id AS VARCHAR(20)) LIKE :term OR ing.secuencial LIKE :term OR inv.nombre LIKE :term OR inv.secuencial LIKE :term OR p.codigo LIKE :term OR ing.proveedor LIKE :term)";
                            $params[':term'] = '%' . $termino . '%';
                        }

                        $sql .= " ORDER BY ing.fecha DESC, ing.id DESC";
                        $stmt = $this->db->prepare($sql);
                        $stmt->execute($params);
                        $datosReporte = $stmt->fetchAll();
                        break;

                    case 'mensual':
                        $driver = defined('DB_DRIVER') ? DB_DRIVER : 'sqlite';
                        if ($driver === 'sqlsrv') {
                            $formatFunc = "FORMAT(ing.fecha, 'yyyy-MM')";
                        } elseif ($driver === 'pgsql') {
                            $formatFunc = "TO_CHAR(ing.fecha, 'YYYY-MM')";
                        } else {
                            $formatFunc = "strftime('%Y-%m', ing.fecha)";
                        }

                        $sql = "SELECT {$formatFunc} as mes, 
                                       COUNT(DISTINCT ing.id) as total_ordenes, 
                                       SUM(det.cantidad) as total_items, 
                                       SUM(det.cantidad * det.valor_unitario) as total_valor
                                FROM inv_bod_ingresos ing
                                JOIN inv_bod_ingresos_detalles det ON ing.id = det.ingreso_id
                                WHERE 1=1";
                        $params = [];

                        if (!empty($fechaInicio)) {
                            $sql .= " AND ing.fecha >= :fecha_ini";
                            $params[':fecha_ini'] = $fechaInicio;
                        }
                        if (!empty($fechaFin)) {
                            $sql .= " AND ing.fecha <= :fecha_fin";
                            $params[':fecha_fin'] = $fechaFin;
                        }
                        if (!empty($termino)) {
                            $sql .= " AND (CAST(ing.id AS VARCHAR(20)) LIKE :term OR ing.secuencial LIKE :term OR ing.proveedor LIKE :term)";
                            $params[':term'] = '%' . $termino . '%';
                        }

                        $sql .= " GROUP BY {$formatFunc} ORDER BY {$formatFunc} DESC";
                        $stmt = $this->db->prepare($sql);
                        $stmt->execute($params);
                        $datosReporte = $stmt->fetchAll();
                        break;
                }
            } catch (Exception $e) {
                $this->logger->inv_error("Error al cargar datos del reporte tab={$tabActivo}", $e, 'index');
            }
        }

        $this->render('bitacoras/reportes_varios', [
            'tabActivo'      => $tabActivo,
            'fechaInicio'    => $fechaInicio,
            'fechaFin'       => $fechaFin,
            'proveedor'      => $proveedor,
            'termino'        => $termino,
            'idExacto'       => $idExacto,
            'idInicio'       => $idInicio,
            'idFin'          => $idFin,
            'datosReporte'   => $datosReporte,
            'proveedores'    => $proveedoresList,
            'generarReporte' => $generarReporte
        ], 'Reportes Varios - Sistema Portuario');
    }

    /**
     * Imprime el reporte seleccionado
     */
    public function imprimir() {
        $tabActivo = isset($_GET['tab']) ? trim($_GET['tab']) : 'proveedores';

        $fechaInicio = $this->normalizarFecha(isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : '');
        $fechaFin    = $this->normalizarFecha(isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : '');
        $proveedor   = isset($_GET['proveedor'])   ? trim($_GET['proveedor']) : '';
        $termino     = isset($_GET['termino'])     ? trim($_GET['termino']) : '';
        $idExacto    = $this->normalizarId(isset($_GET['id_exacto']) ? $_GET['id_exacto'] : '');
        $idInicio    = $this->normalizarId(isset($_GET['id_inicio']) ? $_GET['id_inicio'] : '');
        $idFin       = $this->normalizarId(isset($_GET['id_fin']) ? $_GET['id_fin'] : '');
        $this->normalizarRangos($idExacto, $idInicio, $idFin, $fechaInicio, $fechaFin);
        if ($tabActivo === 'mensual') {
            $idExacto = '';
        }

        $datosReporte = [];

        try {
            switch ($tabActivo) {
                case 'proveedores':
                    $sql = "SELECT * FROM inv_proveedores WHERE 1=1";
                    $params = [];
                    if ($idExacto !== '') {
                        $sql .= " AND id = :id_exacto";
                        $params[':id_exacto'] = (int)$idExacto;
                    }
                    if (!empty($idInicio)) {
                        $sql .= " AND id >= :id_ini";
                        $params[':id_ini'] = (int)$idInicio;
                    }
                    if (!empty($idFin)) {
                        $sql .= " AND id <= :id_fin";
                        $params[':id_fin'] = (int)$idFin;
                    }
                    if (!empty($termino)) {
                        $sql .= " AND (CAST(id AS VARCHAR(20)) LIKE :term OR nombre LIKE :term OR ruc LIKE :term OR extra LIKE :term)";
                        $params[':term'] = '%' . $termino . '%';
                    }
                    $sql .= " ORDER BY id ASC";
                    $stmt = $this->db->prepare($sql);
                    $stmt->execute($params);
                    $datosReporte = $stmt->fetchAll();
                    break;

                case 'centros_consumo':
                    $sql = "SELECT cc.*, COALESCE(p.nombre, cc.funcionario) AS funcionario_actual, gcc.nombre as grupo_nombre, gcc.codigo as grupo_codigo,
                                   (SELECT COUNT(*) FROM inv_bod_egresos e WHERE e.area_id = cc.id) as total_egresos,
                                   (SELECT COALESCE(SUM(ed.cantidad), 0) FROM inv_bod_egresos_detalles ed JOIN inv_bod_egresos e ON ed.egreso_id = e.id WHERE e.area_id = cc.id) as total_items
                            FROM inv_centros_consumo cc
                            JOIN inv_grupo_centros_consumo gcc ON cc.grupo_id = gcc.id
                            LEFT JOIN inv_talento_personal p ON p.id = cc.funcionario_id
                            WHERE 1=1";
                    $params = [];
                    if ($idExacto !== '') {
                        $sql .= " AND cc.id = :id_exacto";
                        $params[':id_exacto'] = (int)$idExacto;
                    }
                    if (!empty($idInicio)) {
                        $sql .= " AND cc.id >= :id_ini";
                        $params[':id_ini'] = (int)$idInicio;
                    }
                    if (!empty($idFin)) {
                        $sql .= " AND cc.id <= :id_fin";
                        $params[':id_fin'] = (int)$idFin;
                    }
                    if (!empty($termino)) {
                        $sql .= " AND (CAST(cc.id AS VARCHAR(20)) LIKE :term OR cc.nombre LIKE :term OR cc.codigo LIKE :term OR COALESCE(p.nombre, cc.funcionario) LIKE :term OR gcc.nombre LIKE :term OR gcc.codigo LIKE :term)";
                        $params[':term'] = '%' . $termino . '%';
                    }
                    $sql .= " ORDER BY cc.codigo ASC";
                    $stmt = $this->db->prepare($sql);
                    $stmt->execute($params);
                    $datosReporte = $stmt->fetchAll();
                    break;

                case 'items':
                    $sql = "SELECT i.*, cat.nombre as categoria_nombre, 
                                   p.codigo as producto_codigo, u.extra as unidad_abreviatura
                            FROM inv_inventario i
                            JOIN inv_categorias cat ON i.categoria_id = cat.id
                            LEFT JOIN inv_productos p ON i.producto_id = p.id
                            LEFT JOIN inv_unidades u ON p.unidad_id = u.id
                            WHERE i.activo = 1";
                    $params = [];
                    if ($idExacto !== '') {
                        $sql .= " AND i.id = :id_exacto";
                        $params[':id_exacto'] = (int)$idExacto;
                    }
                    if (!empty($idInicio)) {
                        $sql .= " AND i.id >= :id_ini";
                        $params[':id_ini'] = (int)$idInicio;
                    }
                    if (!empty($idFin)) {
                        $sql .= " AND i.id <= :id_fin";
                        $params[':id_fin'] = (int)$idFin;
                    }
                    if (!empty($fechaInicio)) {
                        $sql .= " AND i.fecha_registro >= :fecha_ini";
                        $params[':fecha_ini'] = $fechaInicio;
                    }
                    if (!empty($fechaFin)) {
                        $sql .= " AND i.fecha_registro <= :fecha_fin";
                        $params[':fecha_fin'] = $fechaFin;
                    }
                    if (!empty($termino)) {
                        $sql .= " AND (CAST(i.id AS VARCHAR(20)) LIKE :term OR CAST(i.producto_id AS VARCHAR(20)) LIKE :term OR i.nombre LIKE :term OR i.secuencial LIKE :term OR p.codigo LIKE :term OR i.marca LIKE :term OR cat.nombre LIKE :term)";
                        $params[':term'] = '%' . $termino . '%';
                    }
                    $sql .= " ORDER BY i.secuencial ASC";
                    $stmt = $this->db->prepare($sql);
                    $stmt->execute($params);
                    $datosReporte = $stmt->fetchAll();
                    break;

                case 'compras':
                    $sql = "SELECT ing.id as ingreso_id, det.id as detalle_id, det.item_id,
                                   ing.secuencial as ingreso_codigo, ing.proveedor, ing.fecha, 
                                   det.cantidad, det.valor_unitario, (det.cantidad * det.valor_unitario) as subtotal,
                                   inv.nombre as item_nombre, inv.secuencial as item_secuencial,
                                   p.codigo as producto_codigo, u.extra as unidad
                            FROM inv_bod_ingresos ing
                            JOIN inv_bod_ingresos_detalles det ON ing.id = det.ingreso_id
                            JOIN inv_inventario inv ON det.item_id = inv.id
                            LEFT JOIN inv_productos p ON inv.producto_id = p.id
                            LEFT JOIN inv_unidades u ON p.unidad_id = u.id
                            WHERE 1=1";
                    $params = [];

                    if ($idExacto !== '') {
                        $sql .= " AND ing.id = :id_exacto";
                        $params[':id_exacto'] = (int)$idExacto;
                    }

                    if (!empty($proveedor)) {
                        $sql .= " AND ing.proveedor = :proveedor";
                        $params[':proveedor'] = $proveedor;
                    }
                    if (!empty($fechaInicio)) {
                        $sql .= " AND ing.fecha >= :fecha_ini";
                        $params[':fecha_ini'] = $fechaInicio;
                    }
                    if (!empty($fechaFin)) {
                        $sql .= " AND ing.fecha <= :fecha_fin";
                        $params[':fecha_fin'] = $fechaFin;
                    }
                    if (!empty($idInicio)) {
                        $sql .= " AND ing.id >= :id_ini";
                        $params[':id_ini'] = (int)$idInicio;
                    }
                    if (!empty($idFin)) {
                        $sql .= " AND ing.id <= :id_fin";
                        $params[':id_fin'] = (int)$idFin;
                    }
                    if (!empty($termino)) {
                        $sql .= " AND (CAST(ing.id AS VARCHAR(20)) LIKE :term OR CAST(det.id AS VARCHAR(20)) LIKE :term OR CAST(det.item_id AS VARCHAR(20)) LIKE :term OR ing.secuencial LIKE :term OR inv.nombre LIKE :term OR inv.secuencial LIKE :term OR p.codigo LIKE :term OR ing.proveedor LIKE :term)";
                        $params[':term'] = '%' . $termino . '%';
                    }

                    $sql .= " ORDER BY ing.fecha DESC, ing.id DESC";
                    $stmt = $this->db->prepare($sql);
                    $stmt->execute($params);
                    $datosReporte = $stmt->fetchAll();
                    break;

                case 'mensual':
                    $driver = defined('DB_DRIVER') ? DB_DRIVER : 'sqlite';
                    if ($driver === 'sqlsrv') {
                        $formatFunc = "FORMAT(ing.fecha, 'yyyy-MM')";
                    } elseif ($driver === 'pgsql') {
                        $formatFunc = "TO_CHAR(ing.fecha, 'YYYY-MM')";
                    } else {
                        $formatFunc = "strftime('%Y-%m', ing.fecha)";
                    }

                    $sql = "SELECT {$formatFunc} as mes, 
                                   COUNT(DISTINCT ing.id) as total_ordenes, 
                                   SUM(det.cantidad) as total_items, 
                                   SUM(det.cantidad * det.valor_unitario) as total_valor
                            FROM inv_bod_ingresos ing
                            JOIN inv_bod_ingresos_detalles det ON ing.id = det.ingreso_id
                            WHERE 1=1";
                    $params = [];

                    if (!empty($fechaInicio)) {
                        $sql .= " AND ing.fecha >= :fecha_ini";
                        $params[':fecha_ini'] = $fechaInicio;
                    }
                    if (!empty($fechaFin)) {
                        $sql .= " AND ing.fecha <= :fecha_fin";
                        $params[':fecha_fin'] = $fechaFin;
                    }
                    if (!empty($termino)) {
                        $sql .= " AND (CAST(ing.id AS VARCHAR(20)) LIKE :term OR ing.secuencial LIKE :term OR ing.proveedor LIKE :term)";
                        $params[':term'] = '%' . $termino . '%';
                    }

                    $sql .= " GROUP BY {$formatFunc} ORDER BY {$formatFunc} DESC";
                    $stmt = $this->db->prepare($sql);
                    $stmt->execute($params);
                    $datosReporte = $stmt->fetchAll();
                    break;
            }
        } catch (Exception $e) {
            $this->logger->inv_error("Error al imprimir reporte tab={$tabActivo}", $e, 'imprimir');
        }

        $this->renderActa('bitacoras/inv_imprimir_reporte', [
            'tabActivo'    => $tabActivo,
            'fechaInicio'  => $fechaInicio,
            'fechaFin'     => $fechaFin,
            'proveedor'    => $proveedor,
            'termino'      => $termino,
            'idExacto'     => $idExacto,
            'idInicio'     => $idInicio,
            'idFin'        => $idFin,
            'datosReporte' => $datosReporte
        ]);
    }

    private function normalizarId($valor) {
        $valor = trim((string)$valor);
        return ctype_digit($valor) && (int)$valor > 0 ? (string)(int)$valor : '';
    }

    private function normalizarFecha($valor) {
        $valor = trim((string)$valor);
        if ($valor === '') {
            return '';
        }
        $fecha = DateTime::createFromFormat('!Y-m-d', $valor);
        return ($fecha && $fecha->format('Y-m-d') === $valor) ? $valor : '';
    }

    private function normalizarRangos(&$idExacto, &$idInicio, &$idFin, &$fechaInicio, &$fechaFin) {
        if ($idExacto !== '') {
            $idInicio = '';
            $idFin = '';
        } elseif ($idInicio !== '' && $idFin !== '' && (int)$idInicio > (int)$idFin) {
            $temporal = $idInicio;
            $idInicio = $idFin;
            $idFin = $temporal;
        }

        if ($fechaInicio !== '' && $fechaFin !== '' && $fechaInicio > $fechaFin) {
            $temporal = $fechaInicio;
            $fechaInicio = $fechaFin;
            $fechaFin = $temporal;
        }
    }
}
class_alias('ReporteController', 'InvReporteController');
