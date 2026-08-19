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
        
        $pagina = max(1, (int)($_GET['pagina'] ?? 1));
        $porPagina = (int)($_GET['por_pagina'] ?? 25);
        $paginacion = $this->usuarioModel->obtenerPagina($pagina, $porPagina);
        $usuarios = $paginacion['items'];
        $tiempoInactividad = $this->paramModel->obtener('tiempo_inactividad', '600');
        $tiempoGraciaSesion = $this->paramModel->obtener('tiempo_gracia_sesion', '300');
        $tiempoVigenciaInventario = $this->paramModel->obtener('tiempo_vigencia_inventario', '600');

        $this->render('credenciales/usuarios', [
            'usuarios' => $usuarios,
            'paginacion' => $paginacion,
            'tiempoInactividad' => $tiempoInactividad,
            'tiempoGraciaSesion' => $tiempoGraciaSesion,
            'tiempoVigenciaInventario' => $tiempoVigenciaInventario,
            'esAdmin' => $this->esAdministrador()
        ], 'Gestión de Usuarios - Sistema Portuario');
    }

    /**
     * Guarda o edita un usuario
     */
    public function guardar() {
        $this->verificarAdministrador();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('usuarios', 'Método no permitido', 'error');
        }

        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        if ($id <= 0) {
            $this->redirect('usuarios', 'Las cuentas se crean automaticamente desde Talento Humano.', 'info');
        }

        $datos = [
            'nombre' => trim($_POST['nombre']),
            'usuario' => trim($_POST['usuario']),
            'contrasena' => isset($_POST['contrasena']) ? $_POST['contrasena'] : '',
            'rol' => trim($_POST['rol']),
            'activo' => isset($_POST['activo']) ? (int)$_POST['activo'] : 1,
            'tiempo_inactividad' => isset($_POST['tiempo_inactividad_usuario']) && $_POST['tiempo_inactividad_usuario'] !== ''
                ? (int)$_POST['tiempo_inactividad_usuario']
                : null
        ];

        $cuentaActual = $this->usuarioModel->buscarPorId($id);
        if ($cuentaActual && strpos((string)$cuentaActual['secuencial'], 'TH-') === 0) {
            // El nombre y la cedula pertenecen a Talento Humano y no se editan aqui.
            $datos['nombre'] = $cuentaActual['nombre'];
            $datos['usuario'] = $cuentaActual['usuario'];
        }

        if ($datos['tiempo_inactividad'] !== null
            && ($datos['tiempo_inactividad'] < 60 || $datos['tiempo_inactividad'] > 14400)) {
            $this->redirect('usuarios', 'El tiempo individual debe estar entre 1 minuto y 4 horas.', 'warning');
        }

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
        $this->verificarAdministrador();
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
        $this->verificarAdministrador();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('usuarios', 'Método no permitido', 'error');
        }

        $tiempo = isset($_POST['tiempo_inactividad']) ? (int)$_POST['tiempo_inactividad'] : 600;
        $tiempoGracia = 300;
        $tiempoInventario = isset($_POST['tiempo_vigencia_inventario']) ? (int)$_POST['tiempo_vigencia_inventario'] : 600;
        
        if ($tiempo < 60 || $tiempo > 14400) {
            $this->redirect('usuarios', 'El tiempo de inactividad global debe estar entre 1 minuto y 4 horas.', 'warning');
        }

        if ($tiempoInventario < 60 || $tiempoInventario > 3600) {
            $this->redirect('usuarios', 'El tiempo fuera de Inventario General debe estar entre 1 y 60 minutos.', 'warning');
        }
        
        try {
            $guardado = $this->paramModel->guardar('tiempo_inactividad', (string)$tiempo, 'Tiempo sin actividad antes de mostrar el aviso de sesión (segundos)');
            $guardado = $this->paramModel->guardar('tiempo_gracia_sesion', '300', 'Tolerancia fija para responder el aviso antes de cerrar sesión (segundos)') && $guardado;
            $guardado = $this->paramModel->guardar('tiempo_vigencia_inventario', (string)$tiempoInventario, 'Tiempo fuera de Inventario General antes de liberar la consulta (segundos)') && $guardado;
            if (!$guardado) {
                throw new Exception('No fue posible guardar todos los parámetros.');
            }
            $this->registrarAuditoria('CONFIGURACION', 'acc', "Inactividad: {$tiempo}s; tolerancia: {$tiempoGracia}s; vigencia de inventario: {$tiempoInventario}s");
            $this->logger->info("CONFIGURACION: inactividad={$tiempo}s, gracia={$tiempoGracia}s, inventario={$tiempoInventario}s", 'guardarParametro');
            $this->redirect('usuarios', 'Configuración de sesión e inventario actualizada correctamente.', 'success');
        } catch (Exception $e) {
            $this->logger->inv_error('Error al guardar parámetro tiempo_inactividad', $e, 'guardarParametro');
            $this->redirect('usuarios', 'Error al guardar la configuración', 'error');
        }
    }

    /**
     * Actualiza la foto del usuario autenticado. La imagen llega ya recortada
     * por el navegador para evitar procesado pesado en PHP y ahorrar espacio.
     */
    public function actualizarFotoPerfil(): void {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $responder = function (bool $ok, string $mensaje, int $estado = 200, array $extra = []): void {
            $esJson = strpos((string)($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json') !== false;
            if ($esJson) {
                $this->jsonResponse(array_merge(['success' => $ok, 'message' => $mensaje], $extra), $estado);
            }
            $this->redirect('inventario', $mensaje, $ok ? 'success' : 'warning');
        };

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $responder(false, 'Método no permitido.', 405);
        }

        $tokenSesion = (string)($_SESSION['perfil_foto_csrf'] ?? '');
        $tokenRecibido = (string)($_POST['csrf'] ?? '');
        if ($tokenSesion === '' || !hash_equals($tokenSesion, $tokenRecibido)) {
            $responder(false, 'La sesión del formulario expiró. Recarga la página e inténtalo nuevamente.', 403);
        }

        $usuarioId = (int)($_SESSION['usuario']['id'] ?? $_SESSION['usuario_id'] ?? 0);
        if ($usuarioId <= 0 || empty($_FILES['foto'])) {
            $responder(false, 'No se recibió una foto válida.', 400);
        }

        $foto = $_FILES['foto'];
        if (($foto['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK
            || !is_uploaded_file((string)($foto['tmp_name'] ?? ''))) {
            $responder(false, 'No fue posible recibir la foto.', 400);
        }

        // El archivo optimizado suele pesar menos de 50 KB; 512 KB deja margen
        // sin permitir fotografías originales innecesariamente grandes.
        if ((int)($foto['size'] ?? 0) <= 0 || (int)$foto['size'] > 524288) {
            $responder(false, 'La foto optimizada no puede superar 512 KB.', 413);
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = (string)$finfo->file($foto['tmp_name']);
        $extensiones = [
            'image/webp' => 'webp',
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
        ];
        if (!isset($extensiones[$mime])) {
            $responder(false, 'Formato no permitido. Usa JPG, PNG o WEBP.', 415);
        }

        $dimensiones = @getimagesize($foto['tmp_name']);
        if ($dimensiones === false || $dimensiones[0] < 32 || $dimensiones[1] < 32
            || $dimensiones[0] > 512 || $dimensiones[1] > 512) {
            $responder(false, 'La imagen debe medir entre 32 y 512 píxeles por lado.', 422);
        }

        $directorio = ROOT_PATH . 'public/uploads/perfiles/';
        if (!is_dir($directorio) && !mkdir($directorio, 0755, true) && !is_dir($directorio)) {
            $responder(false, 'No fue posible preparar el almacenamiento de la foto.', 500);
        }

        $extension = $extensiones[$mime];
        $nombreBase = 'usuario-' . $usuarioId;
        $destino = $directorio . $nombreBase . '.' . $extension;
        if (!move_uploaded_file($foto['tmp_name'], $destino)) {
            $responder(false, 'No fue posible guardar la foto.', 500);
        }

        foreach (['webp', 'jpg', 'png'] as $extensionAnterior) {
            $anterior = $directorio . $nombreBase . '.' . $extensionAnterior;
            if ($anterior !== $destino && is_file($anterior)) {
                @unlink($anterior);
            }
        }

        $_SESSION['perfil_foto_version'] = time();
        $this->registrarAuditoria('ACTUALIZAR', 'acc', 'Foto de perfil actualizada por el usuario autenticado');
        $responder(true, 'Foto de perfil actualizada.', 200, [
            'url' => 'public/uploads/perfiles/' . $nombreBase . '.' . $extension . '?v=' . $_SESSION['perfil_foto_version']
        ]);
    }

    private function esAdministrador(): bool {
        if (session_status() === PHP_SESSION_NONE) session_start();
        return strtolower((string)($_SESSION['rol'] ?? '')) === 'administrador';
    }

    /** Protección de servidor independiente de los controles visuales. */
    private function verificarAdministrador(): void {
        if ($this->esAdministrador()) return;
        $this->registrarAuditoria('DENEGADO', 'acc', 'Intento no autorizado de modificar usuarios o tiempos del sistema');
        $this->redirect('usuarios', 'Solo el Administrador puede modificar usuarios y tiempos del sistema.', 'error');
        exit;
    }
}
class_alias('UsuarioController', 'InvUsuarioController');
