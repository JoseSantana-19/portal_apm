<?php
/**
 * CONFIGMODEL.PHP - Modelo de Configuración General
 * Hereda de ParametroModel para dar compatibilidad al Router.
 */

require_once ROOT_PATH . 'modules/Central/models/InvParametro.php';

class ConfigModel extends ParametroModel {
    /** Devuelve el tiempo particular del usuario o el valor global heredado. */
    public function obtenerTiempoInactividadUsuario(int $usuarioId, int $valorGlobal): int {
        if ($usuarioId <= 0) return $valorGlobal;

        try {
            $stmt = $this->db->prepare(
                "SELECT tiempo_inactividad FROM inv_usuarios WHERE id = :id"
            );
            $stmt->execute([':id' => $usuarioId]);
            $valor = $stmt->fetchColumn();
            return $valor !== false && $valor !== null
                ? max(60, min(14400, (int)$valor))
                : $valorGlobal;
        } catch (Exception $e) {
            // Compatibilidad durante una instalación anterior a la migración.
            return $valorGlobal;
        }
    }
}
