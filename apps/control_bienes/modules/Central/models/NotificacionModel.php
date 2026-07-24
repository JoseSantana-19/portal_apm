<?php
/**
 * NotificacionModel.php - Modelo para persistir y gestionar alertas y notificaciones del sistema.
 */
require_once ROOT_PATH . 'core/Model.php';

class NotificacionModel extends Model {

    /**
     * Registra una nueva notificación persistente en el sistema
     */
    public function crear($tipo, $categoria, $titulo, $mensaje, $secuencial = null) {
        try {
            $sql = "INSERT INTO inv_notificaciones (tipo, categoria, titulo, mensaje, secuencial) 
                    VALUES (:tipo, :categoria, :titulo, :mensaje, :secuencial)";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':tipo'       => $tipo,
                ':categoria'  => $categoria,
                ':titulo'     => $titulo,
                ':mensaje'    => $mensaje,
                ':secuencial' => $secuencial
            ]);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Obtiene todas las notificaciones no vistas
     */
    public function obtenerNoVistas() {
        try {
            $this->limpiarAntiguas(15); // Limpiar las mayores a 15 días

            $sql = "SELECT id, tipo, categoria, titulo, mensaje, secuencial, created_at, visto 
                    FROM inv_notificaciones 
                    WHERE visto = 0 
                    ORDER BY id DESC";
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Obtiene el listado de todas las notificaciones (vistas y no vistas) recientes
     */
    public function obtenerRecientes($limit = 30) {
        try {
            $driver = defined('DB_DRIVER') ? DB_DRIVER : 'sqlite';
            if ($driver === 'sqlsrv') {
                $sql = "SELECT TOP " . (int)$limit . " id, tipo, categoria, titulo, mensaje, secuencial, created_at, visto 
                        FROM inv_notificaciones 
                        ORDER BY id DESC";
                $stmt = $this->db->prepare($sql);
            } else {
                $sql = "SELECT id, tipo, categoria, titulo, mensaje, secuencial, created_at, visto 
                        FROM inv_notificaciones 
                        ORDER BY id DESC 
                        LIMIT :limit";
                $stmt = $this->db->prepare($sql);
                $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            }
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Marca todas las notificaciones como vistas
     */
    public function marcarTodasComoVistas() {
        try {
            $sql = "UPDATE inv_notificaciones SET visto = 1 WHERE visto = 0";
            return $this->db->exec($sql);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Sistema de limpieza automática de notificaciones antiguas
     */
    public function limpiarAntiguas($dias = 15) {
        try {
            $driver = defined('DB_DRIVER') ? DB_DRIVER : 'sqlite';
            if ($driver === 'sqlsrv') {
                $sql = "DELETE FROM inv_notificaciones WHERE created_at < DATEADD(day, -" . (int)$dias . ", GETDATE())";
            } elseif ($driver === 'pgsql') {
                $sql = "DELETE FROM inv_notificaciones WHERE created_at < NOW() - INTERVAL '" . (int)$dias . " days'";
            } else {
                $sql = "DELETE FROM inv_notificaciones WHERE created_at < datetime('now', '-" . (int)$dias . " days')";
            }
            return $this->db->exec($sql);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Elimina absolutamente todas las notificaciones de la base de datos (vaciar/limpiar)
     */
    public function vaciarTodas() {
        try {
            $sql = "DELETE FROM inv_notificaciones";
            return $this->db->exec($sql);
        } catch (Exception $e) {
            return false;
        }
    }
}

class InvNotificacion extends NotificacionModel {}
