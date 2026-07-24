<?php
require_once ROOT_PATH . 'core/Model.php';

class InvSecuencial extends Model {
    // Conexión PDO heredada en $this->db


    /**
     * Obtiene todos los inv_secuenciales configurados en el sistema
     */
    public function obtenerTodos() {
        $stmt = $this->db->query("SELECT * FROM inv_secuenciales");
        return $stmt->fetchAll();
    }

    /**
     * Genera el siguiente secuencial para un módulo específico
     * @param string $modulo ('inv', 'th', 'bit', 'acc')
     * @return string InvSecuencial formateado
     */
    public function generarSiguiente($modulo) {
        try {
            $inTransaction = $this->db->inTransaction();
            if (!$inTransaction) {
                $this->db->beginTransaction();
            }

            $stmt = $this->db->prepare("SELECT * FROM inv_secuenciales WHERE modulo = :modulo");
            $stmt->execute([':modulo' => $modulo]);
            $seq = $stmt->fetch();

            if (!$seq) {
                // Si no existe, crear registro por defecto
                $prefijos = [
                    'inv' => 'INV-',
                    'th' => 'TH-',
                    'bit' => 'BIT-',
                    'acc' => 'ACC-',
                    'ing' => 'ING-',
                    'egr' => 'EGR-'
                ];
                $prefijo = isset($prefijos[$modulo]) ? $prefijos[$modulo] : 'GEN-';
                
                $stmtInsert = $this->db->prepare("INSERT INTO inv_secuenciales (modulo, prefijo, ultimo_numero) VALUES (:modulo, :prefijo, 0)");
                $stmtInsert->execute([':modulo' => $modulo, ':prefijo' => $prefijo]);
                
                $ultimoNumero = 1;
                $prefijoFinal = $prefijo;
            } else {
                $ultimoNumero = (int)$seq['ultimo_numero'] + 1;
                $prefijoFinal = $seq['prefijo'];
            }

            // Actualizar en base de datos
            $stmtUpdate = $this->db->prepare("UPDATE inv_secuenciales SET ultimo_numero = :num WHERE modulo = :modulo");
            $stmtUpdate->execute([':num' => $ultimoNumero, ':modulo' => $modulo]);

            if (!$inTransaction) {
                $this->db->commit();
            }

            // Formatear según el módulo
            switch ($modulo) {
                case 'inv':
                    return $prefijoFinal . str_pad($ultimoNumero, 5, '0', STR_PAD_LEFT); // INV-00001
                case 'bit':
                    return $prefijoFinal . str_pad($ultimoNumero, 6, '0', STR_PAD_LEFT); // BIT-000001
                case 'ing':
                case 'egr':
                    return $prefijoFinal . str_pad($ultimoNumero, 5, '0', STR_PAD_LEFT); // ING-00001, EGR-00001
                default:
                    return $prefijoFinal . str_pad($ultimoNumero, 4, '0', STR_PAD_LEFT); // TH-0001, ACC-0001
            }
        } catch (Exception $e) {
            if (!$inTransaction && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Reinicia el secuencial de un módulo a cero
     */
    public function reiniciar($modulo) {
        $stmt = $this->db->prepare("UPDATE inv_secuenciales SET ultimo_numero = 0 WHERE modulo = :modulo");
        return $stmt->execute([':modulo' => $modulo]);
    }
}
