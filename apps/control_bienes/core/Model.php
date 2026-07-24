<?php
/**
 * MODEL.PHP - Clase Modelo Base
 * Carga la conexión PDO unificada y provee funciones básicas de acceso a datos.
 */

require_once ROOT_PATH . 'core/Database.php';

class Model {
    /** @var DatabaseConnection Instancia de conexión a la base de datos */
    protected $db;

    public function __construct() {
        // Obtener la conexión Singleton unificada
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Retorna el último ID insertado
     */
    protected function lastInsertId() {
        return $this->db->lastInsertId();
    }

    /**
     * Helper para iniciar transacciones PDO
     */
    protected function beginTransaction() {
        if (!$this->db->inTransaction()) {
            $this->db->beginTransaction();
        }
    }

    /**
     * Helper para confirmar transacciones
     */
    protected function commit() {
        if ($this->db->inTransaction()) {
            $this->db->commit();
        }
    }

    /**
     * Helper para revertir transacciones
     */
    protected function rollBack() {
        if ($this->db->inTransaction()) {
            $this->db->rollBack();
        }
    }
}
