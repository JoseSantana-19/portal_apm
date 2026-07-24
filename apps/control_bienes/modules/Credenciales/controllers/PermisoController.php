<?php
/**
 * PermisoController.php - Controlador de Gestión de Permisos por Usuario
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
            ]
        ],
        'datos' => [
            'titulo' => 'Arquitectura de Datos',
            'items' => [
                'inv_maestros'     => ['label' => 'Maestros',                 'icon' => 'fa-layer-group'],
                'cabeceras'    => ['label' => 'Tablas de Cabecera',       'icon' => 'fa-table-columns'],
                'inv_periodos'     => ['label' => 'Períodos e IVA',           'icon' => 'fa-calendar-days'],
                'talento'      => ['label' => 'Talento Humano',           'icon' => 'fa-users-gear'],
                'inv_secuenciales' => ['label' => 'Secuenciales de Índice',   'icon' => 'fa-list-ol'],
            ]
        ],
        'sistema' => [
            'titulo' => 'Sistema y Logs',
            'items' => [
                'inv_bitacora'     => ['label' => 'Bitácora del Sistema',     'icon' => 'fa-clock-rotate-left'],
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
     * Vista principal
     */
    public function index() {
        $this->verificarAdmin();
        $this->registrarAuditoria('ACCESO', 'inv_permisos', 'Acceso al módulo de Gestión de Permisos');

        $usuarios  = $this->usuarioModel->obtenerTodos();
        $rutas     = self::$rutasDisponibles;

        $permisosPorUsuario = [];
        foreach ($usuarios as $usr) {
            $permisosPorUsuario[$usr['id']] = $this->permisoModel->obtenerPermisosUsuario($usr['id']);
        }

        $this->render('credenciales/permisos', [
            'usuarios'           => $usuarios,
            'rutas'              => $rutas,
            'permisosPorUsuario' => $permisosPorUsuario,
        ], 'Gestión de Permisos - Sistema Portuario');
    }

    /**
     * AJAX: Obtiene los permisos actuales de un usuario
     */
    public function obtenerPermisos() {
        $this->verificarAdmin();
        $usuarioId = isset($_GET['usuario_id']) ? (int)$_GET['usuario_id'] : 0;
        if (!$usuarioId) {
            $this->jsonResponse(['error' => 'Usuario no especificado'], 400);
        }
        $permisos = $this->permisoModel->obtenerPermisosUsuario($usuarioId);
        $this->jsonResponse(['inv_permisos' => $permisos]);
    }

    /**
     * Guarda/actualiza los permisos de un usuario (POST)
     */
    public function guardar() {
        $this->verificarAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Método no permitido'], 405);
        }

        $usuarioId      = isset($_POST['usuario_id']) ? (int)$_POST['usuario_id'] : 0;
        $permisosNuevos = isset($_POST['inv_permisos']) && is_array($_POST['inv_permisos']) ? $_POST['inv_permisos'] : [];

        if (!$usuarioId) {
            $this->jsonResponse(['error' => 'Usuario no especificado'], 400);
        }

        try {
            $this->permisoModel->actualizarPermisos($usuarioId, $permisosNuevos);
            $usuario = $this->usuarioModel->buscarPorId($usuarioId);
            $nombreUsr = $usuario ? $usuario['nombre'] : "ID {$usuarioId}";
            $this->registrarAuditoria('ACTUALIZAR', 'inv_permisos', "Permisos actualizados para usuario: {$nombreUsr} — Rutas: " . implode(', ', $permisosNuevos));

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
