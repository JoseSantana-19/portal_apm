<?php
/**
 * BINCONTROLLER.PHP - Controlador del Módulo Control de Bines (Inventario)
 * Une el inventario general y el catálogo de ítems del sistema.
 */

require_once ROOT_PATH . 'modules/Control_Bines/models/BinModel.php';
require_once ROOT_PATH . 'modules/Control_Bines/models/EstacionModel.php';
require_once ROOT_PATH . 'modules/Talento_Humano/models/EmpleadoModel.php';
require_once ROOT_PATH . 'modules/Central/models/InvPeriodo.php';
require_once ROOT_PATH . 'modules/Control_Bines/models/InvItemSistema.php';
require_once ROOT_PATH . 'modules/Bitacoras/models/LogModel.php';

class BinController extends Controller {
    private $inventarioModel;
    private $cabeceraModel;
    private $talentoModel;
    private $periodoModel;
    private $itemModel;
    private $logger;

    /**
     * Devuelve solo categorias finales. Los nodos contables padre sirven para
     * ordenar el catalogo, pero no son grupos validos para asignar bienes.
     */
    private function categoriasAsignables(array $categorias): array {
        return array_values(array_filter($categorias, function ($categoria) use ($categorias) {
            $codigo = trim((string)($categoria['codigo'] ?? ''));
            if ($codigo === '') return true;
            foreach ($categorias as $posibleHija) {
                $codigoHija = trim((string)($posibleHija['codigo'] ?? ''));
                if ($codigoHija !== $codigo && strpos($codigoHija, $codigo) === 0) {
                    return false;
                }
            }
            return true;
        }));
    }

    public function __construct() {
        parent::__construct();
        $this->logger = new Logger('inv');
        $this->inventarioModel = new BinModel();
        $this->cabeceraModel = new EstacionModel();
        $this->talentoModel = new EmpleadoModel();
        $this->periodoModel = new InvPeriodo();
        $this->itemModel = new InvItemSistema();
    }

    /**
     * Dashboard Principal de Inventario (Tabla y Stats)
     */
    public function index() {
        $this->registrarAuditoria('ACCESO', 'inv', 'Acceso al módulo de Inventario General');
        
        $filtros = [
            'categoria' => isset($_GET['categoria']) ? $_GET['categoria'] : '',
            'unidad_id' => isset($_GET['unidad_id']) ? $_GET['unidad_id'] : '',
            'estado'    => isset($_GET['estado'])    ? $_GET['estado']    : '',
            'termino'   => isset($_GET['termino'])   ? $_GET['termino']   : '',
            'sort_by'   => isset($_GET['sort_by'])   ? $_GET['sort_by']   : '',
            'sort_dir'  => isset($_GET['sort_dir'])  ? $_GET['sort_dir']  : ''
        ];

        $stats         = $this->inventarioModel->obtenerEstadisticas();
        $periodoActivo = $this->periodoModel->obtenerPeriodoActivo();
        $tasaIva       = $periodoActivo ? $periodoActivo['tasa_iva'] : 15.0;

        $categorias = $this->categoriasAsignables($this->cabeceraModel->obtenerTodos('categorias'));
        $zonas      = $this->cabeceraModel->obtenerTodos('zonas');
        $estados    = $this->cabeceraModel->obtenerTodos('estados');
        $personal   = $this->talentoModel->obtenerPersonal();
        $unidades   = $this->cabeceraModel->obtenerTodos('unidades');

        // Obtener tipos de IVA
        $db = Database::getInstance()->getConnection();
        $ivasStmt = $db->query("SELECT * FROM inv_tipos_iva ORDER BY tasa_iva DESC");
        $tiposIva = $ivasStmt->fetchAll();

        // Obtener catálogo para el modal
        $productosStmt = $db->query(
            "SELECT p.id, p.nombre, p.codigo, p.grupo_id, p.precio_promedio,
                    c.nombre as grupo_nombre, c.id as categoria_id
             FROM inv_productos p
             JOIN inv_categorias c ON p.grupo_id = c.id
             ORDER BY c.nombre ASC, p.nombre ASC"
        );
        $catalogoProductos = $productosStmt->fetchAll();

        $this->render('bines/listar', [
            'stats'             => $stats,
            'tasaIva'           => $tasaIva,
            'categorias'        => $categorias,
            'zonas'             => $zonas,
            'estados'           => $estados,
            'personal'          => $personal,
            'unidades'          => $unidades,
            'filtros'           => $filtros,
            'periodoActivo'     => $periodoActivo,
            'catalogoProductos' => $catalogoProductos,
            'tiposIva'          => $tiposIva,
        ], 'Inventario General - Sistema Portuario');
    }

    /**
     * Catálogo en Formato Grid de Tarjetas (Cards)
     */
    public function catalogo() {
        $this->registrarAuditoria('ACCESO', 'inv', 'Acceso al Catálogo de Ítems');

        $filtros = [
            'termino' => isset($_GET['termino']) ? trim($_GET['termino']) : ''
        ];

        $resumenCategorias = $this->inventarioModel->obtenerResumenPorCategoria($filtros['termino']);
        $categorias = $this->categoriasAsignables($this->cabeceraModel->obtenerTodos('categorias'));
        $zonas = $this->cabeceraModel->obtenerTodos('zonas');
        $estados = $this->cabeceraModel->obtenerTodos('estados');
        $personal = $this->talentoModel->obtenerPersonal();

        $this->render('bines/catalogo', [
            'resumenCategorias' => $resumenCategorias,
            'categorias' => $categorias,
            'zonas' => $zonas,
            'estados' => $estados,
            'personal' => $personal,
            'filtros' => $filtros
        ], 'Catálogo de Ítems - Sistema Portuario');
    }

    /**
     * Lista General compatible con DataTables (Server-Side)
     * Soporta tanto el formato tradicional de DataTables como el Lazy-Load heredado.
     */
    public function listarAjax() {
        // Soporte de DataTables Server-side
        $isDatatable = isset($_GET['draw']);
        
        $filtros = [
            'categoria' => isset($_GET['categoria']) ? $_GET['categoria'] : '',
            'unidad_id' => isset($_GET['unidad_id']) ? $_GET['unidad_id'] : '',
            'estado'    => isset($_GET['estado'])    ? $_GET['estado']    : '',
            'termino'   => isset($_GET['termino'])   ? $_GET['termino']   : '',
            'sort_by'   => isset($_GET['sort_by'])   ? $_GET['sort_by']   : '',
            'sort_dir'  => isset($_GET['sort_dir'])  ? $_GET['sort_dir']  : ''
        ];

        // Mapear parámetros de ordenación de DataTables
        if ($isDatatable && isset($_GET['order'][0])) {
            $colIdx = (int)$_GET['order'][0]['column'];
            $dir = $_GET['order'][0]['dir'];
            $colNameMap = ['secuencial', 'nombre', 'categoria', 'unidad', 'valor', 'iva', 'total', 'estado'];
            if (isset($colNameMap[$colIdx])) {
                $filtros['sort_by'] = $colNameMap[$colIdx];
                $filtros['sort_dir'] = $dir;
            }
            if (isset($_GET['search']['value']) && $_GET['search']['value'] !== '') {
                $filtros['termino'] = $_GET['search']['value'];
            }
        }

        $db = Database::getInstance()->getConnection();
        $periodoActivo = $this->periodoModel->obtenerPeriodoActivo();
        $tasaIvaVigente = $periodoActivo ? (float)$periodoActivo['tasa_iva'] : 0.0;

        // Paginación
        $limit = 50;
        $offset = 0;
        
        if ($isDatatable) {
            $limit = isset($_GET['length']) ? (int)$_GET['length'] : 50;
            $offset = isset($_GET['start']) ? (int)$_GET['start'] : 0;
            $page = ($limit > 0) ? floor($offset / $limit) + 1 : 1;
        } else {
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            if ($page < 1) $page = 1;
            $offset = ($page - 1) * $limit;
        }

        // Obtener totales
        $all_items = $this->inventarioModel->filtrar($filtros);
        $total = count($all_items);

        // Obtener página activa
        $items = $this->inventarioModel->filtrar($filtros, $limit, $offset);

        $paleta = [
            'Maquinaria Pesada' => ['color' => '#ef4444', 'icono' => 'fa-truck-monster'],
            'Contenedores'      => ['color' => '#3b82f6', 'icono' => 'fa-box-open'],
            'Equipos de Muelle' => ['color' => '#10b981', 'icono' => 'fa-life-ring'],
            'Vehículos'         => ['color' => '#f59e0b', 'icono' => 'fa-truck-pickup'],
            'Herramientas'      => ['color' => '#8b5cf6', 'icono' => 'fa-wrench'],
        ];

        if ($isDatatable) {
            $data = [];
            foreach ($items as $item) {
                $valorBase = (float)$item['valor'];
                $aplicaIva = isset($item['producto_aplica_iva']) && (int)$item['producto_aplica_iva'] === 1;
                $ivaCalc = $aplicaIva ? $valorBase * ($tasaIvaVigente / 100) : 0.0;
                $ivaNombre = $aplicaIva ? number_format($tasaIvaVigente, 2) . '%' : 'No aplica';
                $valorTotal = $valorBase + $ivaCalc;
                
                $isApplied = $aplicaIva;
                $ivaClass = $isApplied ? 'active' : 'inactive';
                $ivaHtml = '<span class="status-badge ' . $ivaClass . '" style="font-size:11px;">' . $ivaNombre . '</span>';

                $cat = $item['categoria'] ?? '';
                $catColor = $paleta[$cat]['color'] ?? '#64748b';
                $icono = $paleta[$cat]['icono'] ?? 'fa-box';

                $itemJson = htmlspecialchars(json_encode($item), ENT_QUOTES, 'UTF-8');
                $unidad = !empty($item['unidad_abrev']) ? $item['unidad_abrev'] : (!empty($item['unidad_nombre']) ? $item['unidad_nombre'] : 'u.');

                $nombreHtml = '<div class="item-name">'
                       . '<div class="item-img" style="color:' . $catColor . '; background: ' . $catColor . '15;"><i class="fa-solid ' . $icono . '"></i></div>'
                       . '<div class="item-info"><strong>' . htmlspecialchars($item['nombre']) . '</strong>'
                       . '<span>Marca: ' . htmlspecialchars($item['marca']) . '</span></div></div>';

                $accionesHtml = '<div class="acciones-cell">'
                       . '<button class="btn-accion btn-ver" onclick="verDetallesInventario(' . $item['id'] . ')" title="Ver Detalle"><i class="fa-solid fa-eye"></i></button>'
                       . '<button class="btn-accion btn-editar" onclick="editarRegistroInventario(' . $itemJson . ')" title="Editar"><i class="fa-solid fa-pen"></i></button>'
                       . '</div>';

                $data[] = [
                    'secuencial' => '<span class="secuencial-cell">' . htmlspecialchars($item['secuencial']) . '</span>',
                    'nombre'     => $nombreHtml,
                    'categoria'  => '<span class="cat-badge" style="--cat-color:' . $catColor . '">' . htmlspecialchars($cat) . '</span>',
                    'unidad'     => '<span style="font-weight:600;color:var(--text-muted);font-size:12.5px;">' . htmlspecialchars($unidad) . '</span>',
                    'valor'      => '<span style="font-weight:600; font-size:12.5px;">$' . number_format($valorBase, 2) . '</span>',
                    'iva'        => $ivaHtml,
                    'total'      => '<strong style="color:var(--primary); font-size:13px;">$' . number_format($valorTotal, 2) . '</strong>',
                    'estado'     => '<span class="status-badge ' . htmlspecialchars((string)($item['estadoClase'] ?? 'inactive')) . '">' . htmlspecialchars((string)($item['estado'] ?? 'Desconocido')) . '</span>',
                    'acciones'   => $accionesHtml
                ];
            }

            $this->jsonResponse([
                'draw'            => (int)$_GET['draw'],
                'recordsTotal'    => $total,
                'recordsFiltered' => $total,
                'data'            => $data
            ]);
        } else {
            // Lazy Load heredado (filas HTML)
            $html = '';
            foreach ($items as $item) {
                $valorBase = (float)$item['valor'];
                $aplicaIva = isset($item['producto_aplica_iva']) && (int)$item['producto_aplica_iva'] === 1;
                $ivaCalc = $aplicaIva ? $valorBase * ($tasaIvaVigente / 100) : 0.0;
                $ivaNombre = $aplicaIva ? number_format($tasaIvaVigente, 2) . '%' : 'No aplica';
                $valorTotal = $valorBase + $ivaCalc;
                
                $isApplied = $aplicaIva;
                $ivaClass = $isApplied ? 'active' : 'inactive';
                $ivaCellsHtml = '<td style="text-align:center;"><span class="status-badge ' . $ivaClass . '" style="font-size:11px;">' . $ivaNombre . '</span></td>';

                $cat = $item['categoria'] ?? '';
                $catColor = $paleta[$cat]['color'] ?? '#64748b';
                $icono = $paleta[$cat]['icono'] ?? 'fa-box';

                $itemJson = htmlspecialchars(json_encode($item), ENT_QUOTES, 'UTF-8');
                $unidad = !empty($item['unidad_abrev']) ? $item['unidad_abrev'] : (!empty($item['unidad_nombre']) ? $item['unidad_nombre'] : 'u.');

                $html .= '<tr>';
                $html .= '<td class="secuencial-cell">' . htmlspecialchars($item['secuencial']) . '</td>';
                $html .= '<td><div class="item-name">'
                       . '<div class="item-img" style="color:' . $catColor . '; background: ' . $catColor . '15;"><i class="fa-solid ' . $icono . '"></i></div>'
                       . '<div class="item-info"><strong>' . htmlspecialchars($item['nombre']) . '</strong>'
                       . '<span>Marca: ' . htmlspecialchars($item['marca']) . '</span></div></div></td>';
                $html .= '<td><span class="cat-badge" style="--cat-color:' . $catColor . '">' . htmlspecialchars($cat) . '</span></td>';
                $html .= '<td style="font-weight:600;color:var(--text-muted);font-size:12.5px;">' . htmlspecialchars($unidad) . '</td>';
                $html .= '<td style="font-weight:600; font-size:12.5px;">$' . number_format($valorBase, 2) . '</td>';
                $html .= $ivaCellsHtml;
                $html .= '<td><strong style="color:var(--primary); font-size:13px;">$' . number_format($valorTotal, 2) . '</strong></td>';
                $html .= '<td><span class="status-badge ' . htmlspecialchars((string)($item['estadoClase'] ?? 'inactive')) . '">' . htmlspecialchars((string)($item['estado'] ?? 'Desconocido')) . '</span></td>';
                $html .= '<td class="acciones-cell">'
                       . '<button class="btn-accion btn-ver" onclick="verDetallesInventario(' . $item['id'] . ')" title="Ver Detalle"><i class="fa-solid fa-eye"></i></button>'
                       . '<button class="btn-accion btn-editar" onclick="editarRegistroInventario(' . $itemJson . ')" title="Editar"><i class="fa-solid fa-pen"></i></button>'
                       . '</td>';
                $html .= '</tr>';
            }

            $this->jsonResponse(['rows' => $html, 'total' => $total, 'page' => $page, 'limit' => $limit]);
        }
    }

    /**
     * Búsqueda y Filtrado AJAX (Retorna JSON)
     */
    public function filtrarAjax() {
        $filtros = [
            'categoria' => isset($_GET['categoria']) ? $_GET['categoria'] : '',
            'unidad_id' => isset($_GET['unidad_id']) ? $_GET['unidad_id'] : '',
            'estado'    => isset($_GET['estado'])    ? $_GET['estado']    : '',
            'termino'   => isset($_GET['termino'])   ? $_GET['termino']   : ''
        ];
        $items = $this->inventarioModel->filtrar($filtros);
        $this->jsonResponse($items);
    }

    public function obtenerItemsCatalogo() {
        $categoriaId = isset($_GET['categoria_id']) ? (int)$_GET['categoria_id'] : 0;
        $termino     = isset($_GET['termino'])      ? trim($_GET['termino'])      : '';

        $filtros = [
            'categoria' => $categoriaId,
            'termino'   => $termino
        ];

        $items = $this->inventarioModel->filtrar($filtros);
        
        foreach ($items as &$item) {
            $valorBase = (float)$item['valor'];
            $item['valor_formateado'] = number_format($valorBase, 2);
            $item['cantidad'] = max(1, (int)($item['cantidad'] ?? 1));
            $item['total_formateado'] = number_format($valorBase * $item['cantidad'], 2);
        }

        $this->jsonResponse($items);
    }

    /**
     * Obtiene el detalle de un equipo específico en JSON
     */
    public function verDetalle() {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $item = $this->inventarioModel->buscarPorId($id);

        if ($item) {
            $this->registrarAuditoria('CONSULTA', 'inv', 'Detalle consultado del bien ID: ' . $item['secuencial']);

            $periodoActivo = $this->periodoModel->obtenerPeriodoActivo();
            $aplicaIva = isset($item['producto_aplica_iva']) && (int)$item['producto_aplica_iva'] === 1;
            $tasaIva = $aplicaIva && $periodoActivo ? (float)$periodoActivo['tasa_iva'] : 0.0;

            $item['tasa_iva']       = $tasaIva;
            $item['aplica_iva']     = $aplicaIva ? 1 : 0;
            $item['iva_calculado']  = $item['valor'] * ($tasaIva / 100);
            $item['valor_total']    = $item['valor'] + $item['iva_calculado'];
            $item['cantidad']       = max(1, (int)($item['cantidad'] ?? 1));

            $this->jsonResponse($item);
        } else {
            $this->jsonResponse(['error' => 'Registro no encontrado'], 404);
        }
    }

    /**
     * Guarda o edita un equipo (POST) - Rutea según el módulo de procedencia
     */
    public function guardar() {
        // Si proviene del catálogo de ítems del sistema
        if (isset($_GET['route']) && $_GET['route'] === 'inv_items_sistema') {
            return $this->guardarItemSistema();
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('inventario', 'Método no permitido', 'error');
        }

        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

        $datos = [
            'nombre'         => trim($_POST['nombre']),
            'marca'          => trim($_POST['marca']),
            'categoria_id'   => (int)$_POST['categoria_id'],
            'zona_id'        => (int)$_POST['zona_id'],
            'estado_id'      => (int)$_POST['estado_id'],
            'responsable_id' => !empty($_POST['responsable_id']) ? (int)$_POST['responsable_id'] : null,
            'valor'          => (float)$_POST['valor'],
            'observaciones'  => trim($_POST['observaciones']),
            'fecha_registro' => !empty($_POST['fecha_registro']) ? $_POST['fecha_registro'] : date('Y-m-d')
        ];

        try {
            if ($id > 0) {
                $item = $this->inventarioModel->actualizar($id, $datos);
                $this->registrarAuditoria('ACTUALIZAR', 'inv', "Registro actualizado: {$item['secuencial']} - {$datos['nombre']}");
                $this->logger->info("EDITAR_ITEM: {$item['secuencial']} - {$datos['nombre']}", 'guardar');

                if (isset($_POST['is_ajax'])) {
                    $this->jsonResponse(['success' => true, 'mensaje' => 'Registro actualizado exitosamente', 'item' => $item]);
                }
                $this->redirect('inventario', 'Registro actualizado exitosamente', 'success');
            } else {
                $item = $this->inventarioModel->crear($datos);
                $this->registrarAuditoria('CREAR', 'inv', "Nuevo registro creado: {$item['secuencial']} - {$datos['nombre']}");
                $this->logger->info("CREAR_ITEM: {$item['secuencial']} - {$datos['nombre']}", 'guardar');

                if (isset($_POST['is_ajax'])) {
                    $this->jsonResponse(['success' => true, 'mensaje' => 'Registro creado exitosamente', 'item' => $item]);
                }
                $this->redirect('inventario', 'Registro creado exitosamente', 'success');
            }
        } catch (Exception $e) {
            $this->logger->inv_error('Error al guardar ítem en inventario', $e, 'guardar');
            if (isset($_POST['is_ajax'])) {
                $this->jsonResponse(['success' => false, 'mensaje' => 'Error al guardar: ' . $e->getMessage()], 500);
            }
            $this->redirect('inventario', 'Error al guardar el registro', 'error');
        }
    }

    /**
     * Elimina (desactiva) un equipo
     */
    public function eliminar() {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $item = $this->inventarioModel->buscarPorId($id);

        if ($item) {
            $this->inventarioModel->eliminar($id);
            $this->registrarAuditoria('ELIMINAR', 'inv', "Registro eliminado: {$item['secuencial']} - {$item['nombre']}");
            
            if (isset($_GET['is_ajax'])) {
                $this->jsonResponse(['success' => true, 'mensaje' => 'Registro eliminado exitosamente']);
            }
            $this->redirect('inventario', 'Registro eliminado exitosamente', 'warning');
        } else {
            if (isset($_GET['is_ajax'])) {
                $this->jsonResponse(['success' => false, 'mensaje' => 'Registro no encontrado'], 404);
            }
            $this->redirect('inventario', 'Registro no encontrado', 'error');
        }
    }

    /**
     * Exporta los datos del inventario a un archivo CSV descargable
     */
    public function exportar() {
        $filtros = [
            'categoria' => isset($_GET['categoria']) ? $_GET['categoria'] : '',
            'unidad_id' => isset($_GET['unidad_id']) ? $_GET['unidad_id'] : '',
            'estado'    => isset($_GET['estado'])    ? $_GET['estado']    : '',
            'termino'   => isset($_GET['termino'])   ? $_GET['termino']   : ''
        ];

        $csv = $this->inventarioModel->exportarCSV($filtros);
        $this->registrarAuditoria('EXPORTAR', 'inv', 'Reporte CSV de inventario exportado');

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=inv_reporte_' . date('Y-m-d') . '.csv');
        echo $csv;
        exit;
    }

    /**
     * Exporta los datos filtrados en PDF
     */
    public function exportarPdf() {
        $filtros = [
            'categoria' => isset($_GET['categoria']) ? $_GET['categoria'] : '',
            'unidad_id' => isset($_GET['unidad_id']) ? $_GET['unidad_id'] : '',
            'estado'    => isset($_GET['estado'])    ? $_GET['estado']    : '',
            'termino'   => isset($_GET['termino'])   ? $_GET['termino']   : ''
        ];

        $items = $this->inventarioModel->filtrar($filtros);
        $stats = $this->inventarioModel->obtenerEstadisticas();
        $periodoActivo = $this->periodoModel->obtenerPeriodoActivo();
        $tasaIva = $periodoActivo ? (float)$periodoActivo['tasa_iva'] : 15.0;

        $this->registrarAuditoria('EXPORTAR', 'inv', 'Reporte PDF de inventario general generado');

        $this->renderActa('inv_reporte_inventario_pdf', [
            'items'         => $items,
            'stats'         => $stats,
            'filtros'       => $filtros,
            'periodoActivo' => $periodoActivo,
            'tasaIva'       => $tasaIva
        ]);
    }

    /* ==================================================================
       MÉTODOS IMPORTADOS DE ITEMSISTEMACONTROLLER (CATÁLOGO DE PRODUCTOS)
       ================================================================== */

    /**
     * Listado maestro de Ítems del Sistema (Productos)
     */
    public function itemsSistema() {
        $this->registrarAuditoria('ACCESO', 'inv_items_sistema', 'Acceso al módulo Ítems del Sistema');

        $grupoId = isset($_GET['grupo_id']) ? (int)$_GET['grupo_id'] : 0;
        $termino = isset($_GET['termino'])  ? trim($_GET['termino'])  : '';

        if ($termino !== '') {
            $items = $this->itemModel->buscar($termino, $grupoId);
        } else {
            $items = $this->itemModel->obtenerTodos($grupoId);
        }

        // La lista puede estar filtrada, pero las plantillas para crear un
        // registro nuevo deben seguir disponibles desde cualquier grupo.
        $plantillas = ($grupoId === 0 && $termino === '')
            ? $items
            : $this->itemModel->obtenerTodos();

        $grupos   = $this->categoriasAsignables($this->cabeceraModel->obtenerTodos('categorias'));
        $unidades = $this->cabeceraModel->obtenerTodos('unidades');
        $personal = $this->talentoModel->obtenerPersonal();
        $periodoActivo = $this->periodoModel->obtenerPeriodoActivo();
        $tasaIvaVigente = $periodoActivo ? (float)$periodoActivo['tasa_iva'] : 0.0;

        // Cargar tipos de IVA
        $db = Database::getInstance()->getConnection();
        $ivasStmt = $db->query("SELECT * FROM inv_tipos_iva ORDER BY tasa_iva DESC");
        $tiposIva = $ivasStmt->fetchAll();

        // Agrupar ítems por grupo para la vista
        $itemsPorGrupo = [];
        foreach ($items as $it) {
            $itemsPorGrupo[$it['grupo_nombre']][] = $it;
        }

        $this->render('bines/items_sistema', [
            'items'         => $items,
            'plantillas'    => $plantillas,
            'itemsPorGrupo' => $itemsPorGrupo,
            'grupos'        => $grupos,
            'unidades'      => $unidades,
            'personal'      => $personal,
            'periodoActivo' => $periodoActivo,
            'tasaIvaVigente'=> $tasaIvaVigente,
            'grupoId'       => $grupoId,
            'termino'       => $termino,
            'tiposIva'      => $tiposIva,
        ], 'Ítems del Sistema de Inventarios - SysPort');
    }

    /**
     * Ver un ítem del sistema en formato JSON
     */
    public function ver() {
        $id   = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $item = $this->itemModel->buscarPorId($id);
        if ($item) {
            $this->jsonResponse($item);
        } else {
            $this->jsonResponse(['error' => 'Ítem no encontrado'], 404);
        }
    }

    /**
     * Guarda o edita un ítem del sistema
     */
    private function guardarItemSistema() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('inv_items_sistema', 'Método no permitido', 'error');
        }

        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

        $datos = [
            'nombre'           => trim($_POST['nombre']),
            'grupo_id'         => (int)$_POST['grupo_id'],
            'unidad_id'        => (int)$_POST['unidad_id'],
            'aplica_iva'       => (isset($_POST['aplica_iva']) && (string)$_POST['aplica_iva'] === '1') ? 1 : 0,
            'codigo'           => trim($_POST['codigo'] ?? ''),
            'descripcion'      => trim($_POST['descripcion'] ?? ''),
            'ubicacion'        => trim($_POST['ubicacion'] ?? ''),
            'existencia_min'   => (float)($_POST['existencia_min'] ?? 0),
            'existencia_max'   => (float)($_POST['existencia_max'] ?? 0),
            'precio_promedio'  => (float)($_POST['precio_promedio'] ?? 0),
            'existencia_actual'=> (float)($_POST['existencia_actual'] ?? 0),
            'tipo_bien'        => 'CC', // El modelo lo determina desde el código de la categoría.
            'responsable_id'   => !empty($_POST['responsable_id']) ? (int)$_POST['responsable_id'] : null,
            'copiar_desde_id'  => isset($_POST['copiar_desde_id']) ? (int)$_POST['copiar_desde_id'] : 0,
        ];

        try {
            if ($id > 0) {
                $item = $this->itemModel->actualizar($id, $datos);
                $this->registrarAuditoria('ACTUALIZAR', 'inv_items_sistema', "Ítem actualizado: {$datos['codigo']} - {$datos['nombre']}");
                if (isset($_POST['is_ajax'])) {
                    $this->jsonResponse(['success' => true, 'mensaje' => 'Ítem actualizado', 'item' => $item]);
                }
                $this->redirect('inv_items_sistema', 'Ítem actualizado exitosamente', 'success');
            } else {
                $item = $this->itemModel->crear($datos);
                $this->registrarAuditoria('CREAR', 'inv_items_sistema', "Nuevo ítem creado: {$item['codigo']} - {$datos['nombre']}");
                if (isset($_POST['is_ajax'])) {
                    $this->jsonResponse(['success' => true, 'mensaje' => 'Ítem creado', 'item' => $item]);
                }
                $this->redirect('inv_items_sistema', 'Ítem creado exitosamente', 'success');
            }
        } catch (Exception $e) {
            $this->logger->inv_error('Error al guardar ítem del sistema', $e, 'guardar');
            if (isset($_POST['is_ajax'])) {
                $this->jsonResponse(['success' => false, 'mensaje' => $e->getMessage()], 500);
            }
            $this->redirect('inv_items_sistema', 'Error al guardar: ' . $e->getMessage(), 'error');
        }
    }
}
