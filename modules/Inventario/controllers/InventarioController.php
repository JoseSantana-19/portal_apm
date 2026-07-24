<?php
/**
 * InventarioController — Módulo Inventario (Control de Bienes Portuarios).
 * Estructura MVC nativa de portal_apm. Datos vía InvDatabase (sqlsrv nativo, sin PDO).
 */
class InventarioController extends Controller
{
    private InventarioModel $inv;
    private MaestroModel $maestro;
    private PeriodoModel $periodo;
    private SecuencialModel $sec;
    private ItemSistemaModel $items;

    public function __construct()
    {
        $this->inv     = new InventarioModel();
        $this->maestro = new MaestroModel();
        $this->periodo = new PeriodoModel();
        $this->sec     = new SecuencialModel();
        $this->items   = new ItemSistemaModel();
    }

    /** Paleta visual por categoría (reutilizada en vistas). */
    public static function paleta(): array
    {
        return [
            'Maquinaria Pesada' => ['color' => '#ef4444', 'icono' => 'fa-truck-monster'],
            'Contenedores'      => ['color' => '#3b82f6', 'icono' => 'fa-box-open'],
            'Equipos de Muelle' => ['color' => '#10b981', 'icono' => 'fa-life-ring'],
            'Vehículos'         => ['color' => '#f59e0b', 'icono' => 'fa-truck-pickup'],
            'Herramientas'      => ['color' => '#8b5cf6', 'icono' => 'fa-wrench'],
        ];
    }

    /** Listado general de bienes + estadísticas. */
    public function index(): void
    {
        $this->requireAuth();

        $filtros = [
            'categoria' => $_GET['categoria'] ?? '',
            'estado'    => $_GET['estado']    ?? '',
            'termino'   => trim($_GET['termino'] ?? ''),
            'sort_by'   => $_GET['sort_by']   ?? '',
            'sort_dir'  => $_GET['sort_dir']  ?? '',
        ];

        $periodoActivo = $this->periodo->obtenerPeriodoActivo();
        $tasaIva       = $periodoActivo ? (float)$periodoActivo['tasa_iva'] : 15.0;

        $this->render('Inventario/listar', [
            'pageTitle'     => 'Inventario General',
            'items'         => $this->inv->filtrar($filtros),
            'stats'         => $this->inv->obtenerEstadisticas(),
            'tasaIva'       => $tasaIva,
            'periodoActivo' => $periodoActivo,
            'categorias'    => $this->maestro->obtenerTodos('categorias'),
            'zonas'         => $this->maestro->obtenerTodos('zonas'),
            'estados'       => $this->maestro->obtenerTodos('estados'),
            'personal'      => $this->inv->obtenerPersonal(),
            'tiposIva'      => $this->maestro->obtenerTodos('tipos_iva'),
            'filtros'       => $filtros,
            'csrf'          => $this->csrfToken(),
        ]);
    }

    /** Catálogo en tarjetas agrupado por categoría. */
    public function catalogo(): void
    {
        $this->requireAuth();
        $termino = trim($_GET['termino'] ?? '');
        $this->render('Inventario/catalogo', [
            'pageTitle'         => 'Catálogo de Ítems',
            'resumenCategorias' => $this->inv->obtenerResumenPorCategoria($termino),
            'termino'           => $termino,
        ]);
    }

    /** Ítems del sistema (catálogo de productos). */
    public function items(): void
    {
        $this->requireAuth();
        $grupoId = (int)($_GET['grupo_id'] ?? 0);
        $termino = trim($_GET['termino'] ?? '');

        $lista = $termino !== '' ? $this->items->buscar($termino) : $this->items->obtenerTodos($grupoId);
        $porGrupo = [];
        foreach ($lista as $it) { $porGrupo[$it['grupo_nombre']][] = $it; }

        $this->render('Inventario/items', [
            'pageTitle'     => 'Ítems del Sistema',
            'items'         => $lista,
            'itemsPorGrupo' => $porGrupo,
            'grupos'        => $this->maestro->obtenerTodos('categorias'),
            'unidades'      => $this->maestro->obtenerTodos('unidades'),
            'grupoId'       => $grupoId,
            'termino'       => $termino,
            'csrf'          => $this->csrfToken(),
        ]);
    }

    /** Detalle de un bien (JSON, para el modal). */
    public function verDetalle(int $id): void
    {
        $this->requireAuth();
        $item = $this->inv->buscarPorId($id);
        if (!$item) { $this->json(['error' => 'Registro no encontrado'], 404); }

        $aplica  = (int)($item['producto_aplica_iva'] ?? 1);
        $tasa    = $this->inv->tasaIvaParaProducto($aplica) ?: 15.0;
        $item['tasa_iva']      = $tasa;
        $item['iva_calculado'] = (float)$item['valor'] * ($tasa / 100);
        $item['valor_total']   = (float)$item['valor'] + $item['iva_calculado'];
        $this->json($item);
    }

    /** Crear o actualizar un bien. */
    public function guardar(): void
    {
        $this->requireAuth();
        $this->verifyCsrf();

        $id = (int)($_POST['id'] ?? 0);
        $d  = [
            'nombre'         => trim($_POST['nombre'] ?? ''),
            'marca'          => trim($_POST['marca'] ?? ''),
            'categoria_id'   => (int)($_POST['categoria_id'] ?? 0),
            'zona_id'        => (int)($_POST['zona_id'] ?? 0),
            'estado_id'      => (int)($_POST['estado_id'] ?? 0),
            'responsable_id' => !empty($_POST['responsable_id']) ? (int)$_POST['responsable_id'] : null,
            'valor'          => (float)($_POST['valor'] ?? 0),
            'observaciones'  => trim($_POST['observaciones'] ?? ''),
            'fecha_registro' => !empty($_POST['fecha_registro']) ? $_POST['fecha_registro'] : date('Y-m-d'),
        ];

        if ($id > 0) {
            $this->inv->actualizar($id, $d);
            SessionHelper::flash('success', 'Bien actualizado correctamente.');
        } else {
            $secuencial = $this->sec->generarSiguiente('inv');
            $this->inv->crear($d, $secuencial);
            SessionHelper::flash('success', 'Bien registrado correctamente.');
        }
        $this->redirect('/inventario');
    }

    /** Desactivar (eliminar lógico) un bien. */
    public function eliminar(int $id): void
    {
        $this->requireAuth();
        $this->verifyCsrf();
        $this->inv->eliminar($id);
        SessionHelper::flash('success', 'Bien dado de baja correctamente.');
        $this->redirect('/inventario');
    }

    /** Exportar inventario a CSV. */
    public function exportar(): void
    {
        $this->requireAuth();
        $filtros = [
            'categoria' => $_GET['categoria'] ?? '',
            'estado'    => $_GET['estado']    ?? '',
            'termino'   => trim($_GET['termino'] ?? ''),
        ];
        $periodoActivo = $this->periodo->obtenerPeriodoActivo();
        $tasaIva = $periodoActivo ? (float)$periodoActivo['tasa_iva'] : 15.0;

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=inv_reporte_' . date('Y-m-d') . '.csv');
        echo $this->inv->exportarCSV($filtros, $tasaIva);
        exit;
    }
}
