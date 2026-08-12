<?php
/**
 * PermisoController.php - Controlador de Gestión de Permisos (por Rol y por Usuario)
 */

require_once ROOT_PATH . 'modules/Credenciales/models/PermisoModel.php';
require_once ROOT_PATH . 'modules/Credenciales/models/UsuarioModel.php';
require_once ROOT_PATH . 'modules/Bitacoras/models/LogModel.php';

class PermisoController extends Controller {
    private $permisoModel;
    private $usuarioModel;
    private $logger;

    public static $rutasDisponibles = [
        'operaciones' => [
            'titulo' => 'Operaciones de Terminal',
            'items' => [
                'inventario'   => ['label' => 'Inventario General',      'icon' => 'fa-ship'],
                'items'        => ['label' => 'Catálogo de Ítems',       'icon' => 'fa-box'],
                'inv_items_sistema'=> ['label' => 'Ítems del Sistema',       'icon' => 'fa-cubes'],
                'ingresos'     => ['label' => 'Ingresos de Bodega',      'icon' => 'fa-truck-ramp-box'],
                'egresos'      => ['label' => 'Egresos de Bodega',       'icon' => 'fa-truck-arrow-right'],
            ]
        ],
        'datos' => [
            'titulo' => 'Arquitectura de Datos',
            'items' => [
                'inv_maestros'     => ['label' => 'Maestros',                 'icon' => 'fa-layer-group'],
                'cabeceras'    => ['label' => 'Tablas de Cabecera',       'icon' => 'fa-table-columns'],
                'inv_periodos'     => ['label' => 'Períodos e IVA',           'icon' => 'fa-calendar-days'],
                'talento_directorio' => ['label' => 'Talento Humano',     'icon' => 'fa-users-gear'],
                'inv_secuenciales' => ['label' => 'Secuenciales de Índice',   'icon' => 'fa-list-ol'],
            ]
        ],
        'sistema' => [
            'titulo' => 'Sistema y Logs',
            'items' => [
                'inv_bitacora'     => ['label' => 'Bitácora del Sistema',     'icon' => 'fa-clock-rotate-left'],
                'reportes'     => ['label' => 'Reportes Varios',          'icon' => 'fa-chart-pie'],
                'usuarios'     => ['label' => 'Gestión de Usuarios',      'icon' => 'fa-user-shield'],
                'inv_permisos'     => ['label' => 'Gestión de Permisos',      'icon' => 'fa-key'],
            ]
        ],
    ];

    public function __construct() {
        parent::__construct();
        $this->logger = new Logger('inv_permisos');
        $this->permisoModel = new PermisoModel();
        $this->usuarioModel = new UsuarioModel();
    }

    /**
     * Vista principal (pestañas: Permisos por Rol / Permisos por Usuario)
     */
    public function index() {
        $this->verificarAdmin();
        $this->registrarAuditoria('ACCESO', 'inv_permisos', 'Acceso al módulo de Gestión de Permisos');

        $usuarios  = $this->usuarioModel->obtenerTodos();
        $rutas     = self::$rutasDisponibles;
        $roles     = $this->permisoModel->listarRoles();

        $nivelesPorUsuario = [];
        foreach ($usuarios as $usr) {
            $nivelesPorUsuario[$usr['id']] = $this->permisoModel->obtenerNivelesUsuario($usr['id']);
        }

        $nivelesPorRol = [];
        foreach ($roles as $rol) {
            $nivelesPorRol[$rol['id']] = $this->permisoModel->nivelesPorRol($rol['id']);
        }

        $this->render('credenciales/permisos', [
            'usuarios'           => $usuarios,
            'rutas'              => $rutas,
            'roles'              => $roles,
            'nivelesPorUsuario'  => $nivelesPorUsuario,
            'nivelesPorRol'      => $nivelesPorRol,
        ], 'Gestión de Permisos - Sistema Portuario');
    }

    /**
     * AJAX: Obtiene los niveles actuales de un usuario
     */
    public function obtenerPermisos() {
        $this->verificarAdmin();
        $usuarioId = isset($_GET['usuario_id']) ? (int)$_GET['usuario_id'] : 0;
        if (!$usuarioId) {
            $this->jsonResponse(['error' => 'Usuario no especificado'], 400);
        }
        $niveles = $this->permisoModel->obtenerNivelesUsuario($usuarioId);
        $this->jsonResponse(['niveles' => $niveles]);
    }

    /**
     * Guarda/actualiza los permisos individuales de un usuario nativo (POST)
     */
    public function guardar() {
        $this->verificarAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Método no permitido'], 405);
        }

        $usuarioId = isset($_POST['usuario_id']) ? (int)$_POST['usuario_id'] : 0;
        $niveles   = isset($_POST['niveles']) && is_array($_POST['niveles']) ? $_POST['niveles'] : [];

        if (!$usuarioId) {
            $this->jsonResponse(['error' => 'Usuario no especificado'], 400);
        }

        try {
            $this->permisoModel->actualizarPermisos($usuarioId, $niveles);
            $usuario = $this->usuarioModel->buscarPorId($usuarioId);
            $nombreUsr = $usuario ? $usuario['nombre'] : "ID {$usuarioId}";
            $this->registrarAuditoria('ACTUALIZAR', 'inv_permisos', "Permisos individuales actualizados para: {$nombreUsr}");

            if (isset($_POST['is_ajax'])) {
                $this->jsonResponse(['success' => true, 'mensaje' => 'Permisos actualizados correctamente']);
            }
            $this->redirect('inv_permisos', 'Permisos actualizados exitosamente', 'success');
        } catch (Exception $e) {
            $this->logger->inv_error('Error al guardar permisos', $e, 'guardar');
            if (isset($_POST['is_ajax'])) {
                $this->jsonResponse(['success' => false, 'mensaje' => $e->getMessage()], 500);
            }
            $this->redirect('inv_permisos', 'Error al guardar permisos: ' . $e->getMessage(), 'error');
        }
    }

    /**
     * Guarda/actualiza los permisos por rol nativo (POST) — sincroniza al central.
     */
    public function guardarRol() {
        $this->verificarAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Método no permitido'], 405);
        }

        $rolId   = isset($_POST['rol_id']) ? (int)$_POST['rol_id'] : 0;
        $niveles = isset($_POST['niveles']) && is_array($_POST['niveles']) ? $_POST['niveles'] : [];

        if (!$rolId) {
            $this->jsonResponse(['error' => 'Rol no especificado'], 400);
        }

        try {
            $this->permisoModel->guardarPermisosRol($rolId, $niveles);
            $this->registrarAuditoria('ACTUALIZAR', 'inv_permisos', "Permisos de rol actualizados para rol_id={$rolId}");

            if (isset($_POST['is_ajax'])) {
                $this->jsonResponse(['success' => true, 'mensaje' => 'Permisos de rol actualizados correctamente']);
            }
            $this->redirect('inv_permisos', 'Permisos de rol actualizados exitosamente', 'success');
        } catch (Exception $e) {
            $this->logger->inv_error('Error al guardar permisos de rol', $e, 'guardarRol');
            if (isset($_POST['is_ajax'])) {
                $this->jsonResponse(['success' => false, 'mensaje' => $e->getMessage()], 500);
            }
            $this->redirect('inv_permisos', 'Error al guardar permisos de rol: ' . $e->getMessage(), 'error');
        }
    }

    /**
     * Verifica que el usuario actual sea Administrador
     */
    private function verificarAdmin() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $rol = isset($_SESSION['rol']) ? $_SESSION['rol'] : '';
        if (strtolower($rol) !== 'administrador') {
            if (isset($_POST['is_ajax']) || isset($_GET['is_ajax'])) {
                $this->jsonResponse(['error' => 'Acceso denegado: se requiere rol Administrador'], 403);
            }
            $this->redirect('inventario', 'Acceso denegado: se requiere rol Administrador', 'error');
            exit;
        }
    }
}
class_alias('PermisoController', 'InvPermisosController');
