<?php
/**
 * BINCONTROLLER.PHP - Controlador del Módulo Control de Bines (Inventario)
 * Une el inventario general y el catálogo de ítems del sistema.
 */

require_once ROOT_PATH . 'modules/Control_Bines/models/BinModel.php';
require_once ROOT_PATH . 'modules/Control_Bines/models/EstacionModel.php';
require_once ROOT_PATH . 'modules/Talento_Humano/models/EmpleadoModel.php';
require_once ROOT_PATH . 'modules/Central/models/InvPeriodo.php';
require_once ROOT_PATH . 'modules/Central/models/InvParametro.php';
require_once ROOT_PATH . 'modules/Control_Bines/models/InvItemSistema.php';
require_once ROOT_PATH . 'modules/Bitacoras/models/LogModel.php';
require_once ROOT_PATH . 'helpers/excel_export_helper.php';

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
     * Dashboard general de inventario y operación.
     */
    public function index() {
        $this->registrarAuditoria('ACCESO', 'inv', 'Acceso al Dashboard General');

        $stats         = $this->inventarioModel->obtenerEstadisticas();
        $resumenOperativo = $this->inventarioModel->obtenerResumenOperativo();
        $usuarioId = (int)($_SESSION['usuario_id'] ?? $_SESSION['usuario']['id'] ?? 0);
        $usoPorRuta = $this->inventarioModel->obtenerRutasFrecuentesUsuario($usuarioId);
        $catalogoAcciones = [
            'ingresos' => ['url' => 'index.php?route=ingresos', 'icono' => 'fa-file-invoice-dollar', 'titulo' => 'Ingresos con factura', 'detalle' => 'Consultar o registrar compras'],
            'egresos' => ['url' => 'index.php?route=egresos', 'icono' => 'fa-dolly', 'titulo' => 'Egresos de bodega', 'detalle' => 'Despachar solicitudes pendientes'],
            'requisiciones' => ['url' => 'index.php?route=requisiciones', 'icono' => 'fa-clipboard-list', 'titulo' => 'Requisiciones', 'detalle' => 'Gestionar solicitudes internas'],
            'ordenes_compra' => ['url' => 'index.php?route=ordenes_compra', 'icono' => 'fa-cart-shopping', 'titulo' => 'Órdenes de compra', 'detalle' => 'Preparar y aprobar órdenes'],
            'items' => ['url' => 'index.php?route=items', 'icono' => 'fa-box', 'titulo' => 'Catálogo de ítems', 'detalle' => 'Consultar productos por categoría'],
            'inv_items_sistema' => ['url' => 'index.php?route=inv_items_sistema', 'icono' => 'fa-cubes', 'titulo' => 'Maestro de ítems', 'detalle' => 'Crear, copiar o editar productos'],
            'inv_maestros' => ['url' => 'index.php?route=inv_maestros', 'icono' => 'fa-layer-group', 'titulo' => 'Maestros', 'detalle' => 'Administrar datos principales'],
            'reportes' => ['url' => 'index.php?route=reportes', 'icono' => 'fa-chart-pie', 'titulo' => 'Reportes', 'detalle' => 'Consultar e imprimir reportes'],
            'busqueda_global' => ['url' => 'index.php?route=busqueda_global', 'icono' => 'fa-magnifying-glass', 'titulo' => 'Búsqueda global', 'detalle' => 'Encontrar información del sistema'],
            'talento_directorio' => ['url' => 'index.php?route=talento_directorio', 'icono' => 'fa-users', 'titulo' => 'Directorio de personal', 'detalle' => 'Consultar funcionarios'],
        ];
        $accionesFrecuentes = [];
        foreach (array_keys($usoPorRuta) as $rutaUsada) {
            if (isset($catalogoAcciones[$rutaUsada])) {
                $accionesFrecuentes[] = $catalogoAcciones[$rutaUsada];
                unset($catalogoAcciones[$rutaUsada]);
            }
            if (count($accionesFrecuentes) === 4) break;
        }
        foreach ($catalogoAcciones as $accionAlterna) {
            if (count($accionesFrecuentes) === 4) break;
            $accionesFrecuentes[] = $accionAlterna;
        }
        $periodoActivo = $this->periodoModel->obtenerPeriodoActivo();
        $tasaIva       = $periodoActivo ? $periodoActivo['tasa_iva'] : 15.0;

        $this->render('bines/listar', [
            'stats'             => $stats,
            'resumenOperativo'  => $resumenOperativo,
            'accionesFrecuentes'=> $accionesFrecuentes,
            'tasaIva'           => $tasaIva,
            'periodoActivo'     => $periodoActivo,
        ], 'Dashboard General - Sistema Portuario');
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
            'segmento'  => isset($_GET['segmento'])  ? $_GET['segmento']  : '',
            'termino'   => isset($_GET['termino'])   ? $_GET['termino']   : '',
            'sort_by'   => isset($_GET['sort_by'])   ? $_GET['sort_by']   : '',
            'sort_dir'  => isset($_GET['sort_dir'])  ? $_GET['sort_dir']  : ''
        ];

        // Mapear parámetros de ordenación de DataTables
        if ($isDatatable && isset($_GET['order'][0])) {
            $colIdx = (int)$_GET['order'][0]['column'];
            $dir = $_GET['order'][0]['dir'];
            $colNameMap = ['secuencial', 'nombre', 'categoria', 'unidad', 'existencia', 'valor', 'iva', 'total', 'estado'];
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

        // Conteos optimizados: DataTables no necesita descargar todos los registros.
        $total = $this->inventarioModel->contarFiltrados($filtros);
        $totalGeneral = $this->inventarioModel->contarFiltrados();

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

                $accionesHtml = '<div class="acciones-cell columna-acciones">'
                       . '<button class="btn-accion btn-ver" onclick="verDetallesInventario(' . $item['id'] . ')" title="Ver Detalle"><i class="fa-solid fa-eye"></i></button>'
                       . '<button class="btn-accion btn-editar" onclick="editarRegistroInventario(' . $itemJson . ')" title="Editar"><i class="fa-solid fa-pen"></i></button>'
                       . '</div>';

                $data[] = [
                    'secuencial' => '<code class="secuencial-cell">' . htmlspecialchars($item['producto_codigo'] ?? $item['secuencial']) . '</code>',
                    'nombre'     => $nombreHtml,
                    'categoria'  => '<span class="cat-badge" style="--cat-color:' . $catColor . '">' . htmlspecialchars($cat) . '</span>',
                    'unidad'     => '<span style="font-weight:600;color:var(--text-muted);font-size:12.5px;">' . htmlspecialchars($unidad) . '</span>',
                    'existencia' => '<strong class="stock-value">' . number_format((float)($item['cantidad'] ?? 0), 2) . '</strong>',
                    'valor'      => '<span style="font-weight:600; font-size:12.5px;">' . CommonHelper::formatearPrecio($valorBase) . '</span>',
                    'iva'        => $ivaHtml,
                    'total'      => '<strong style="color:var(--primary); font-size:13px;">' . CommonHelper::formatearImporte($valorTotal) . '</strong>',
                    'estado'     => '<span class="status-badge ' . htmlspecialchars((string)($item['estadoClase'] ?? 'inactive')) . '">' . htmlspecialchars((string)($item['estado'] ?? 'Desconocido')) . '</span>',
                    'acciones'   => $accionesHtml
                ];
            }

            $this->jsonResponse([
                'draw'            => (int)$_GET['draw'],
                'recordsTotal'    => $totalGeneral,
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
                $html .= '<td><code class="secuencial-cell">' . htmlspecialchars($item['producto_codigo'] ?? $item['secuencial']) . '</code></td>';
                $html .= '<td><div class="item-name">'
                       . '<div class="item-img" style="color:' . $catColor . '; background: ' . $catColor . '15;"><i class="fa-solid ' . $icono . '"></i></div>'
                       . '<div class="item-info"><strong>' . htmlspecialchars($item['nombre']) . '</strong>'
                       . '<span>Marca: ' . htmlspecialchars($item['marca']) . '</span></div></div></td>';
                $html .= '<td><span class="cat-badge" style="--cat-color:' . $catColor . '">' . htmlspecialchars($cat) . '</span></td>';
                $html .= '<td style="font-weight:600;color:var(--text-muted);font-size:12.5px;">' . htmlspecialchars($unidad) . '</td>';
                $html .= '<td><strong class="stock-value">' . number_format((float)($item['cantidad'] ?? 0), 2) . '</strong></td>';
                $html .= '<td style="font-weight:600; font-size:12.5px;">' . CommonHelper::formatearPrecio($valorBase) . '</td>';
                $html .= $ivaCellsHtml;
                $html .= '<td><strong style="color:var(--primary); font-size:13px;">' . CommonHelper::formatearImporte($valorTotal) . '</strong></td>';
                $html .= '<td><span class="status-badge ' . htmlspecialchars((string)($item['estadoClase'] ?? 'inactive')) . '">' . htmlspecialchars((string)($item['estado'] ?? 'Desconocido')) . '</span></td>';
                $html .= '<td class="acciones-cell columna-acciones">'
                       . '<button class="btn-accion btn-ver" onclick="verDetallesInventario(' . $item['id'] . ')" title="Ver Detalle"><i class="fa-solid fa-eye"></i></button>'
                       . '<button class="btn-accion btn-editar" onclick="editarRegistroInventario(' . $itemJson . ')" title="Editar"><i class="fa-solid fa-pen"></i></button>'
                       . '</td>';
                $html .= '</tr>';
            }

            $this->jsonResponse(['rows' => $html, 'total' => $total, 'page' => $page, 'limit' => $limit]);
        }
    }

    /** Exportación completa o resumida compatible con Microsoft Excel. */
    public function exportarInventarioExcel(): void {
        $modo = (isset($_GET['modo']) && $_GET['modo'] === 'resumido') ? 'resumido' : 'completo';
        $filtros = [
            'categoria' => trim((string)($_GET['categoria'] ?? '')),
            'unidad_id' => trim((string)($_GET['unidad_id'] ?? '')),
            'estado' => trim((string)($_GET['estado'] ?? '')),
            'segmento' => trim((string)($_GET['segmento'] ?? '')),
            'termino' => trim((string)($_GET['termino'] ?? '')),
            'sort_by' => 'nombre',
            'sort_dir' => 'ASC'
        ];
        $items = $this->inventarioModel->filtrar($filtros, null, null);
        $periodo = $this->periodoModel->obtenerPeriodoActivo();
        $tasaIva = $periodo ? (float)$periodo['tasa_iva'] : 0.0;
        $archivo = 'inventario_' . $modo . '_' . date('Ymd_His') . '.xls';
        $columnas = $modo === 'resumido' ? [
            ['titulo'=>'Código','tipo'=>'text','ancho'=>15], ['titulo'=>'Producto','tipo'=>'text','ancho'=>34],
            ['titulo'=>'Categoría','tipo'=>'text','ancho'=>28], ['titulo'=>'Unidad','tipo'=>'text','ancho'=>14],
            ['titulo'=>'Existencia','tipo'=>'decimal','ancho'=>13], ['titulo'=>'Valor total','tipo'=>'currency','ancho'=>16]
        ] : [
            ['titulo'=>'ID','tipo'=>'integer','ancho'=>9], ['titulo'=>'Código','tipo'=>'text','ancho'=>15],
            ['titulo'=>'Código clasificación','tipo'=>'text','ancho'=>20], ['titulo'=>'Producto','tipo'=>'text','ancho'=>34],
            ['titulo'=>'Marca','tipo'=>'text','ancho'=>20], ['titulo'=>'Categoría','tipo'=>'text','ancho'=>28],
            ['titulo'=>'Tipo','tipo'=>'text','ancho'=>10], ['titulo'=>'Unidad','tipo'=>'text','ancho'=>14],
            ['titulo'=>'Existencia','tipo'=>'decimal','ancho'=>13], ['titulo'=>'Precio unitario','tipo'=>'price','ancho'=>20],
            ['titulo'=>'IVA','tipo'=>'text','ancho'=>12], ['titulo'=>'Valor total','tipo'=>'currency','ancho'=>16],
            ['titulo'=>'Zona','tipo'=>'text','ancho'=>20], ['titulo'=>'Estado','tipo'=>'text','ancho'=>16],
            ['titulo'=>'Responsable','tipo'=>'text','ancho'=>28], ['titulo'=>'Fecha de registro','tipo'=>'date','ancho'=>16],
            ['titulo'=>'Observaciones','tipo'=>'text','ancho'=>42]
        ];
        $filas = [];
        foreach ($items as $item) {
            $valor = (float)($item['valor'] ?? 0);
            $cantidad = (float)($item['cantidad'] ?? 0);
            $aplicaIva = (int)($item['producto_aplica_iva'] ?? 0) === 1;
            $total = $valor + ($aplicaIva ? $valor * $tasaIva / 100 : 0);
            $unidad = $item['unidad_abrev'] ?? ($item['unidad_nombre'] ?? 'u.');
            $filas[] = $modo === 'resumido'
                ? [$item['producto_codigo'] ?? $item['secuencial'] ?? '', $item['nombre'] ?? '', $item['categoria'] ?? '', $unidad, $cantidad, $total]
                : [$item['id'] ?? '', $item['secuencial'] ?? '', $item['producto_codigo'] ?? '', $item['nombre'] ?? '', $item['marca'] ?? '', $item['categoria'] ?? '', $item['tipo_bien'] ?? '', $unidad, $cantidad, $valor, $aplicaIva ? $tasaIva . '%' : 'No aplica', $total, $item['zona'] ?? '', $item['estado'] ?? '', $item['responsable'] ?? '', $item['fecha_registro'] ?? '', $item['observaciones'] ?? ''];
        }
        $filtrosAplicados = array_filter([$filtros['categoria'], $filtros['estado'], $filtros['segmento'], $filtros['termino']], static fn($v) => $v !== '');
        $subtitulo = ucfirst($modo) . ' · ' . ($filtrosAplicados ? 'Filtros: ' . implode(' · ', $filtrosAplicados) : 'Inventario general sin filtros');
        exportarExcelEstilizado($archivo, 'Inventario General', $subtitulo, $columnas, $filas);
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
            $item['valor_formateado'] = CommonHelper::formatearPrecio($valorBase, false);
            $item['cantidad'] = max(1, (int)($item['cantidad'] ?? 1));
            $item['total_formateado'] = CommonHelper::formatearImporte($valorBase * $item['cantidad'], false);
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
            'valor'          => CommonHelper::redondearPrecio($_POST['valor'] ?? 0),
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
        $this->registrarAuditoria('ACCESO', 'inv_items_sistema', 'Acceso al módulo Maestro de Ítems');

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
        ], 'Maestro de Ítems de Inventarios - SysPort');
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
        $copiarDesdeId = isset($_POST['copiar_desde_id']) ? (int)$_POST['copiar_desde_id'] : 0;
        // Copiar es siempre una creación. Aunque el navegador envíe por error
        // el ID del registro cargado, nunca se permite actualizar la plantilla.
        if ($copiarDesdeId > 0) $id = 0;

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
            'precio_promedio'  => CommonHelper::redondearPrecio($_POST['precio_promedio'] ?? 0),
            'existencia_actual'=> (float)($_POST['existencia_actual'] ?? 0),
            'tipo_bien'        => 'CC', // El modelo lo determina desde el código de la categoría.
            'responsable_id'   => !empty($_POST['responsable_id']) ? (int)$_POST['responsable_id'] : null,
            'copiar_desde_id'  => $copiarDesdeId,
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
