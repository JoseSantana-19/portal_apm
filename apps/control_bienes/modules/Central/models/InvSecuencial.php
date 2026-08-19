<?php
require_once ROOT_PATH . 'core/Model.php';

class InvSecuencial extends Model {
    public function obtenerTodos() {
        $stmt = $this->db->query("SELECT * FROM inv_secuenciales ORDER BY modulo");
        return $stmt->fetchAll();
    }

    public function generarSiguiente($modulo) {
        $numero = $this->generarNumero($modulo);
        $seq = $this->buscarPorModulo($modulo);
        $prefijo = $seq ? $seq['prefijo'] : 'GEN-';

        switch ($modulo) {
            case 'inv':
            case 'ing':
            case 'egr':
            case 'npe':
            case 'npa':
            case 'ocp':
                $longitud = 5;
                break;
            case 'bit':
            case 'itm':
                $longitud = 6;
                break;
            default:
                $longitud = 4;
        }

        return $prefijo . str_pad($numero, $longitud, '0', STR_PAD_LEFT);
    }

    /**
     * Reserva un numero correlativo mediante una operacion atomica.
     * Si el llamador ya abrio una transaccion, el numero queda ligado a ella.
     */
    public function generarNumero($modulo) {
        $modulo = strtolower(trim((string)$modulo));
        if (!preg_match('/^[a-z0-9_]{1,50}$/', $modulo)) {
            throw new InvalidArgumentException('Modulo de secuencial no valido.');
        }

        $prefijos = [
            'inv' => 'INV-', 'th' => 'TH-', 'bit' => 'BIT-',
            'acc' => 'ACC-', 'ing' => 'ING-', 'egr' => 'EGR-', 'npe' => 'NPE-',
            'npa' => 'NPA-', 'ocp' => 'OCP-', 'itm' => 'ITM-'
        ];
        $prefijo = isset($prefijos[$modulo]) ? $prefijos[$modulo] : 'GEN-';
        $driver = defined('DB_DRIVER') ? DB_DRIVER : 'sqlite';
        $inTransaction = $this->db->inTransaction();

        try {
            if (!$inTransaction) {
                $this->db->beginTransaction();
            }

            if ($driver === 'sqlsrv') {
                $ensure = $this->db->prepare(
                    "IF NOT EXISTS (SELECT 1 FROM inv_secuenciales WITH (UPDLOCK, HOLDLOCK) WHERE modulo = :modulo)
                     INSERT INTO inv_secuenciales (modulo, prefijo, ultimo_numero)
                     VALUES (:modulo_insert, :prefijo, 0)"
                );
                $ensure->execute([
                    ':modulo' => $modulo,
                    ':modulo_insert' => $modulo,
                    ':prefijo' => $prefijo
                ]);
                $stmt = $this->db->prepare(
                    "UPDATE inv_secuenciales WITH (UPDLOCK, HOLDLOCK)
                     SET ultimo_numero = ultimo_numero + 1
                     OUTPUT INSERTED.ultimo_numero
                     WHERE modulo = :modulo"
                );
                $stmt->execute([':modulo' => $modulo]);
                $numero = (int)$stmt->fetchColumn();
            } elseif ($driver === 'pgsql') {
                $ensure = $this->db->prepare(
                    "INSERT INTO inv_secuenciales (modulo, prefijo, ultimo_numero)
                     VALUES (:modulo, :prefijo, 0) ON CONFLICT (modulo) DO NOTHING"
                );
                $ensure->execute([':modulo' => $modulo, ':prefijo' => $prefijo]);
                $stmt = $this->db->prepare(
                    "UPDATE inv_secuenciales SET ultimo_numero = ultimo_numero + 1
                     WHERE modulo = :modulo RETURNING ultimo_numero"
                );
                $stmt->execute([':modulo' => $modulo]);
                $numero = (int)$stmt->fetchColumn();
            } else {
                $ensure = $this->db->prepare(
                    "INSERT OR IGNORE INTO inv_secuenciales (modulo, prefijo, ultimo_numero)
                     VALUES (:modulo, :prefijo, 0)"
                );
                $ensure->execute([':modulo' => $modulo, ':prefijo' => $prefijo]);
                $stmt = $this->db->prepare(
                    "UPDATE inv_secuenciales SET ultimo_numero = ultimo_numero + 1 WHERE modulo = :modulo"
                );
                $stmt->execute([':modulo' => $modulo]);
                $read = $this->db->prepare("SELECT ultimo_numero FROM inv_secuenciales WHERE modulo = :modulo");
                $read->execute([':modulo' => $modulo]);
                $numero = (int)$read->fetchColumn();
            }

            if (!$inTransaction) {
                $this->db->commit();
            }
            return $numero;
        } catch (Exception $e) {
            if (!$inTransaction && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    private function buscarPorModulo($modulo) {
        $stmt = $this->db->prepare("SELECT * FROM inv_secuenciales WHERE modulo = :modulo");
        $stmt->execute([':modulo' => $modulo]);
        return $stmt->fetch();
    }

    public function reiniciar($modulo) {
        $stmt = $this->db->prepare("UPDATE inv_secuenciales SET ultimo_numero = 0 WHERE modulo = :modulo");
        return $stmt->execute([':modulo' => $modulo]);
    }
}
