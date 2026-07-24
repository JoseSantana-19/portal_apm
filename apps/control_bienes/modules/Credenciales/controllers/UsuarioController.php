<?php
/**
 * UsuarioController.php - Controlador de Gestión de Usuarios y Parámetros
 */

require_once ROOT_PATH . 'modules/Credenciales/models/UsuarioModel.php';
require_once ROOT_PATH . 'modules/Central/models/InvParametro.php';
require_once ROOT_PATH . 'modules/Central/models/NotificacionModel.php';
require_once ROOT_PATH . 'modules/Bitacoras/models/LogModel.php';

class UsuarioController extends Controller {
    private $usuarioModel;
    private $paramModel;
    private $logger;

    public function __construct() {
        parent::__construct();
        $this->logger = new Logger('acc');
        $this->usuarioModel = new UsuarioModel();
        $this->paramModel = new InvParametro();
    }

    /**
     * Listado de Usuarios
     */
    public function index() {
        $this->registrarAuditoria('ACCESO', 'acc', 'Acceso al listado de usuarios');
        
        $usuarios = $this->usuarioModel->obtenerTodos();
        $tiempoInactividad = $this->paramModel->obtener('tiempo_inactividad', '600');

        $this->render('credenciales/usuarios', [
            'usuarios' => $usuarios,
            'tiempoInactividad' => $tiempoInactividad
        ], 'Gestión de Usuarios - Sistema Portuario');
    }

    /**
     * Guarda o edita un usuario
     */
    public function guardar() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('usuarios', 'Método no permitido', 'error');
        }

        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $datos = [
            'nombre' => trim($_POST['nombre']),
            'usuario' => trim($_POST['usuario']),
            'contrasena' => isset($_POST['contrasena']) ? $_POST['contrasena'] : '',
            'rol' => trim($_POST['rol']),
            'activo' => isset($_POST['activo']) ? (int)$_POST['activo'] : 1
        ];

        try {
            $_notifModel = new NotificacionModel();

            if ($id > 0) {
                $usr = $this->usuarioModel->actualizar($id, $datos);
                $this->registrarAuditoria('ACTUALIZAR', 'acc', "Usuario actualizado: {$usr['secuencial']} - {$datos['nombre']} ({$datos['rol']})");
                $this->logger->info("EDITAR_USUARIO: {$datos['nombre']} ({$datos['rol']})", 'guardar');
                $_notifModel->crear('info', 'seguridad', 'Usuario Actualizado', "Se actualizó la cuenta de usuario <strong>{$datos['nombre']}</strong> ({$datos['rol']}).");
                $this->redirect('usuarios', 'Usuario actualizado con éxito', 'success');
            } else {
                if (empty($datos['contrasena'])) {
                    $this->logger->warning('Intento de crear usuario sin contraseña', 'guardar');
                    throw new Exception("La contraseña es requerida para nuevos usuarios.");
                }
                $usr = $this->usuarioModel->crear($datos);
                $this->registrarAuditoria('CREAR', 'acc', "Nuevo usuario creado: {$usr['secuencial']} - {$datos['nombre']} ({$datos['rol']})");
                $this->logger->info("CREAR_USUARIO: {$datos['nombre']} ({$datos['rol']})", 'guardar');
                $_notifModel->crear('info', 'seguridad', 'Usuario Creado', "Se ha creado la cuenta del usuario <strong>{$datos['nombre']}</strong> ({$datos['rol']}).");
                $this->redirect('usuarios', 'Usuario creado con éxito', 'success');
            }
        } catch (Exception $e) {
            $this->logger->inv_error('Error al guardar usuario', $e, 'guardar');
            $this->redirect('usuarios', 'Error al guardar usuario: ' . $e->getMessage(), 'error');
        }
    }

    /**
     * Elimina un usuario
     */
    public function eliminar() {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $usr = $this->usuarioModel->buscarPorId($id);

        if ($usr) {
            $this->usuarioModel->eliminar($id);
            $this->registrarAuditoria('ELIMINAR', 'acc', "Usuario eliminado: {$usr['secuencial']} - {$usr['nombre']}");
            
            $_notifModel = new NotificacionModel();
            $_notifModel->crear('advertencia', 'seguridad', 'Usuario Eliminado', "Se ha eliminado la cuenta de usuario <strong>{$usr['nombre']}</strong> ({$usr['rol']}).");

            $this->redirect('usuarios', 'Usuario eliminado con éxito', 'warning');
        } else {
            $this->redirect('usuarios', 'Usuario no encontrado', 'error');
        }
    }

    /**
     * Configura el parámetro de inactividad de sesión
     */
    public function guardarParametro() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('usuarios', 'Método no permitido', 'error');
        }

        $tiempo = isset($_POST['tiempo_inactividad']) ? (int)$_POST['tiempo_inactividad'] : 600;
        
        if ($tiempo < 10) {
            $this->redirect('usuarios', 'El tiempo de inactividad mínimo permitido es 10 segundos.', 'warning');
        }
        
        try {
            $this->paramModel->guardar('tiempo_inactividad', (string)$tiempo, 'Tiempo de inactividad permitido antes de relogearse (en segundos)');
            $this->registrarAuditoria('CONFIGURACION', 'acc', "Tiempo de inactividad de sesión modificado a {$tiempo} segundos");
            $this->logger->info("CONFIGURACION: tiempo_inactividad actualizado a {$tiempo}s", 'guardarParametro');
            $this->redirect('usuarios', 'Tiempo de inactividad actualizado correctamente.', 'success');
        } catch (Exception $e) {
            $this->logger->inv_error('Error al guardar parámetro tiempo_inactividad', $e, 'guardarParametro');
            $this->redirect('usuarios', 'Error al guardar la configuración', 'error');
        }
    }
}
class_alias('UsuarioController', 'InvUsuarioController');
