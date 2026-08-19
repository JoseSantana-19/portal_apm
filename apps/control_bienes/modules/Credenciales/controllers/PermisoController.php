<?php
require_once ROOT_PATH . 'modules/Credenciales/models/PermisoModel.php';
require_once ROOT_PATH . 'modules/Credenciales/models/UsuarioModel.php';
require_once ROOT_PATH . 'modules/Bitacoras/models/LogModel.php';

class PermisoController extends Controller {
    private $permisoModel;
    private $usuarioModel;
    private $logger;
    private $catalogoPermisos = [];

    public static $rutasDisponibles = [
        'operaciones' => ['titulo' => 'Operaciones de Terminal', 'items' => [
            'busqueda_global' => ['label' => 'Búsqueda Global', 'icon' => 'fa-magnifying-glass', 'secciones' => ['general' => 'Consulta general']],
            'inventario' => ['label' => 'Inventario General', 'icon' => 'fa-ship', 'secciones' => ['general' => 'Inventario y existencias']],
            'items' => ['label' => 'Catálogo de Ítems', 'icon' => 'fa-box', 'secciones' => ['general' => 'Catálogo de productos']],
            'inv_items_sistema' => ['label' => 'Maestro de Ítems', 'icon' => 'fa-cubes', 'secciones' => ['general' => 'Registros maestros']],
        ]],
        'consultas' => ['titulo' => 'Consulta Reportes', 'items' => [
            'reportes' => ['label' => 'Reportes Varios', 'icon' => 'fa-chart-pie', 'secciones' => ['general' => 'Consulta e impresión de reportes']],
        ]],
        'bodega' => ['titulo' => 'Bodega', 'items' => [
            'egresos' => ['label' => 'Abastecimiento de Bodega', 'icon' => 'fa-warehouse', 'secciones' => [
                'ordenes' => 'Órdenes de compra', 'facturas' => 'Facturas de proveedores',
                'ingresos' => 'Ingresos a bodega', 'kardex' => 'Kardex y movimientos',
            ]],
        ]],
        'datos' => ['titulo' => 'Arquitectura de Datos', 'items' => [
            'inv_maestros' => ['label' => 'Maestros', 'icon' => 'fa-layer-group', 'secciones' => [
                'categorias' => 'Grupos y categorías', 'productos' => 'Catálogo de productos',
                'proveedores' => 'Proveedores', 'unidades' => 'Unidades de medida',
                'tipos_iva' => 'Tipos de IVA', 'grupo_centros_consumo' => 'Grupos de centros de consumo',
                'centros_consumo' => 'Centros de consumo',
            ]],
            'inv_periodos' => ['label' => 'Períodos e IVA', 'icon' => 'fa-calendar-days', 'secciones' => ['general' => 'Períodos contables']],
            'inv_secuenciales' => ['label' => 'Secuenciales de Índice', 'icon' => 'fa-list-ol', 'secciones' => ['general' => 'Contadores automáticos']],
        ]],
        'rrhh' => ['titulo' => 'Gestión de Personal', 'items' => [
            'talento_directorio' => ['label' => 'Directorio de Personal', 'icon' => 'fa-users', 'secciones' => ['general' => 'Funcionarios y fichas']],
        ]],
        'sistema' => ['titulo' => 'Sistema y Logs', 'items' => [
            'inv_bitacora' => ['label' => 'Bitácora del Sistema', 'icon' => 'fa-clock-rotate-left', 'secciones' => ['general' => 'Auditoría y eventos']],
            'usuarios' => ['label' => 'Gestión de Usuarios', 'icon' => 'fa-user-shield', 'secciones' => ['general' => 'Usuarios y parámetros']],
            'inv_permisos' => ['label' => 'Gestión de Permisos', 'icon' => 'fa-key', 'secciones' => ['general' => 'Matriz de permisos'], 'solo_admin' => true],
        ]],
    ];

    public function __construct() {
        parent::__construct();
        $this->logger = new Logger('inv_permisos');
        $this->permisoModel = new PermisoModel();
        $this->usuarioModel = new UsuarioModel();
        $this->catalogoPermisos = $this->descubrirCatalogoPermisos();
    }

    public function index() {
        $this->verificarAdmin();
        $this->registrarAuditoria('ACCESO', 'inv_permisos', 'Acceso a Gestión de Permisos');
        $usuarios = $this->usuarioModel->obtenerTodos();
        $matrices = [];
        foreach ($usuarios as $usuario) $matrices[$usuario['id']] = $this->permisoModel->obtenerMatrizUsuario((int)$usuario['id']);
        $this->render('credenciales/permisos', [
            'usuarios' => $usuarios, 'rutas' => $this->catalogoPermisos, 'permisosPorUsuario' => $matrices,
        ], 'Gestión de Permisos - Sistema Portuario');
    }

    public function obtenerPermisos() {
        $this->verificarAdmin();
        $usuarioId = (int)($_GET['usuario_id'] ?? 0);
        if (!$usuarioId) $this->jsonResponse(['error' => 'Usuario no especificado'], 400);
        $this->jsonResponse(['permisos' => $this->permisoModel->obtenerMatrizUsuario($usuarioId)]);
    }

    public function guardar() {
        $this->verificarAdmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') $this->jsonResponse(['error' => 'Método no permitido'], 405);
        $usuarioId = (int)($_POST['usuario_id'] ?? 0);
        if (!$usuarioId) $this->jsonResponse(['error' => 'Usuario no especificado'], 400);
        $usuario = $this->usuarioModel->buscarPorId($usuarioId);
        if (!$usuario) $this->jsonResponse(['error' => 'Usuario no encontrado'], 404);
        if (strtolower((string)$usuario['rol']) === 'administrador') $this->jsonResponse(['error' => 'Los permisos del administrador no se modifican'], 400);
        try {
            $recibidas = isset($_POST['reglas']) && is_array($_POST['reglas']) ? $_POST['reglas'] : [];
            $reglas = $this->normalizarReglas($recibidas);
            $this->permisoModel->actualizarMatriz($usuarioId, $reglas);
            $this->registrarAuditoria('ACTUALIZAR', 'inv_permisos', 'Matriz actualizada para '.$usuario['nombre'].' — '.count($reglas).' secciones');
            $this->jsonResponse(['success' => true, 'mensaje' => 'Permisos actualizados correctamente', 'matriz' => $this->permisoModel->obtenerMatrizUsuario($usuarioId)]);
        } catch (Throwable $e) {
            $this->logger->inv_error('Error al guardar permisos', $e, 'guardar');
            $this->jsonResponse(['success' => false, 'mensaje' => $e->getMessage()], 500);
        }
    }

    private function normalizarReglas(array $recibidas): array {
        $permitidas = [];
        foreach ($this->catalogoPermisos as $grupo) foreach ($grupo['items'] as $route => $info) {
            if (!empty($info['solo_admin'])) continue;
            foreach ($info['secciones'] as $scope => $titulo) $permitidas[$route][$scope] = true;
        }
        $reglas = [];
        foreach ($recibidas as $route => $scopes) {
            if (!isset($permitidas[$route]) || !is_array($scopes)) continue;
            foreach ($scopes as $scope => $acciones) {
                if (!isset($permitidas[$route][$scope]) || !is_array($acciones)) continue;
                $reglas[] = ['route' => $route, 'scope' => $scope, 'read' => !empty($acciones['read']), 'create' => !empty($acciones['create']), 'edit' => !empty($acciones['edit']), 'full' => !empty($acciones['full'])];
            }
        }
        return $reglas;
    }

    /**
     * Convierte automáticamente el catálogo central de navegación en opciones
     * administrables. Las rutas antiguas que todavía no son visibles en el menú
     * se conservan desde el catálogo legado hasta completar su migración.
     */
    private function descubrirCatalogoPermisos(): array {
        $navegacion = require ROOT_PATH . 'config/navigation.php';
        $catalogo = [];
        $rutasRegistradas = [];

        foreach ($navegacion as $grupoKey => $grupo) {
            $catalogo[$grupoKey] = [
                'titulo' => (string)($grupo['titulo_seccion'] ?? $grupoKey),
                'items' => [],
            ];
            foreach (($grupo['items'] ?? []) as $route => $item) {
                $secciones = $item['permission_sections'] ?? [
                    'general' => (string)($item['permission_label'] ?? $item['label'] ?? $route),
                ];
                $catalogo[$grupoKey]['items'][$route] = [
                    'label' => (string)($item['label'] ?? $route),
                    'icon' => (string)($item['icon'] ?? 'fa-puzzle-piece'),
                    'secciones' => $secciones,
                    'solo_admin' => !empty($item['solo_admin']),
                ];
                $rutasRegistradas[$route] = true;
            }
        }

        foreach (self::$rutasDisponibles as $grupoKey => $grupoLegado) {
            if (!isset($catalogo[$grupoKey])) {
                $catalogo[$grupoKey] = ['titulo' => $grupoLegado['titulo'], 'items' => []];
            }
            foreach ($grupoLegado['items'] as $route => $item) {
                if (!isset($rutasRegistradas[$route])) {
                    $catalogo[$grupoKey]['items'][$route] = $item;
                    $rutasRegistradas[$route] = true;
                }
            }
        }

        return array_filter($catalogo, static function (array $grupo): bool {
            return !empty($grupo['items']);
        });
    }

    private function verificarAdmin() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (strtolower((string)($_SESSION['rol'] ?? '')) !== 'administrador') {
            if (isset($_POST['is_ajax']) || isset($_GET['is_ajax'])) $this->jsonResponse(['error' => 'Acceso exclusivo para Administradores'], 403);
            $this->redirect('inventario', 'Acceso exclusivo para Administradores', 'error');
            exit;
        }
    }
}
class_alias('PermisoController', 'InvPermisosController');
