<?php
/**
 * NotificacionesController.php - Controlador de Alertas y Notificaciones del Header
 */

require_once ROOT_PATH . 'modules/Central/models/NotificacionModel.php';
require_once ROOT_PATH . 'modules/Bitacoras/models/LogModel.php';

class NotificacionesController extends Controller {
    private $notifModel;

    public function __construct() {
        parent::__construct();
        $this->notifModel = new NotificacionModel();
    }

    /**
     * Marca todas las notificaciones como leídas (AJAX)
     */
    public function marcarLeidas() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $this->notifModel->marcarTodasComoVistas();

        $persistentes = $this->notifModel->obtenerRecientes(30);
        $notificacionesIds = [];
        foreach ($persistentes as $n) {
            $notificacionesIds[] = 'evt_' . $n['id'];
        }

        $dbConnection = Database::getInstance()->getConnection();
        try {
            $driver = defined('DB_DRIVER') ? DB_DRIVER : 'sqlite';
            if ($driver === 'sqlsrv') {
                $sqlStock = "SELECT TOP 8 id FROM inv_inventario WHERE activo = 1 AND cantidad <= 5";
            } else {
                $sqlStock = "SELECT id FROM inv_inventario WHERE activo = 1 AND cantidad <= 5 LIMIT 8";
            }
            $itemsStock = $dbConnection->query($sqlStock)->fetchAll();
            foreach ($itemsStock as $item) {
                $notificacionesIds[] = 'stk_' . $item['id'];
            }

            if ($driver === 'sqlsrv') {
                $sqlMaint = "SELECT TOP 8 i.id FROM inv_inventario i
                             JOIN inv_estados est ON i.estado_id = est.idestado
                             WHERE i.activo = 1
                               AND (LOWER(est.descripcion) LIKE '%mantenimiento%'
                                    OR LOWER(est.descripcion) LIKE '%fuera de servicio%'
                                    OR LOWER(est.descripcion) LIKE '%inactivo%')";
            } else {
                $sqlMaint = "SELECT i.id FROM inv_inventario i
                             JOIN inv_estados est ON i.estado_id = est.idestado
                             WHERE i.activo = 1
                               AND (LOWER(est.descripcion) LIKE '%mantenimiento%'
                                    OR LOWER(est.descripcion) LIKE '%fuera de servicio%'
                                    OR LOWER(est.descripcion) LIKE '%inactivo%')
                             LIMIT 8";
            }
            $itemsMaint = $dbConnection->query($sqlMaint)->fetchAll();
            foreach ($itemsMaint as $item) {
                $notificacionesIds[] = 'mnt_' . $item['id'];
            }
        } catch (Exception $e) {}

        $_SESSION['notificaciones_vistas'] = $notificacionesIds;
        $this->jsonResponse(['success' => true]);
    }

    /**
     * Vacía todas las notificaciones persistentes (AJAX)
     */
    public function vaciar() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $persistentes = $this->notifModel->obtenerRecientes(100);
        $eliminadasIds = isset($_SESSION['notificaciones_eliminadas']) ? $_SESSION['notificaciones_eliminadas'] : [];
        foreach ($persistentes as $n) {
            $eliminadasIds[] = 'evt_' . $n['id'];
        }

        try {
            $dbConnection = Database::getInstance()->getConnection();
            $sqlStock = "SELECT id FROM inv_inventario WHERE activo = 1 AND cantidad <= 5";
            $itemsStock = $dbConnection->query($sqlStock)->fetchAll();
            foreach ($itemsStock as $item) {
                $eliminadasIds[] = 'stk_' . $item['id'];
            }

            $sqlMaint = "SELECT i.id FROM inv_inventario i
                         JOIN inv_estados est ON i.estado_id = est.idestado
                         WHERE i.activo = 1
                           AND (LOWER(est.descripcion) LIKE '%mantenimiento%'
                                OR LOWER(est.descripcion) LIKE '%fuera de servicio%'
                                OR LOWER(est.descripcion) LIKE '%inactivo%')";
            $itemsMaint = $dbConnection->query($sqlMaint)->fetchAll();
            foreach ($itemsMaint as $item) {
                $eliminadasIds[] = 'mnt_' . $item['id'];
            }
        } catch (Exception $e) {}

        $_SESSION['notificaciones_eliminadas'] = array_unique($eliminadasIds);
        $_SESSION['notificaciones_vistas'] = array_unique($eliminadasIds);

        $this->notifModel->vaciarTodas();
        $this->jsonResponse(['success' => true]);
    }
}
