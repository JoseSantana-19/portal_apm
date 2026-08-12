<?php
/**
 * PARAMETRO.PHP - Modelo de Configuración y Parámetros del Sistema
 * Permite leer y escribir parámetros globales como el tiempo de inactividad de sesión.
 * Compatible con PHP 7.4, 8.3 y 8.4
 */

require_once ROOT_PATH . 'core/Model.php';

class ParametroModel extends Model {

    /**
     * Obtiene el valor de un parámetro por su clave
     * @param string $clave Clave única del parámetro
     * @param mixed $defecto Valor de retorno por defecto si no existe
     * @return string|null
     */
    public function obtener($clave, $defecto = null) {
        try {
            $stmt = $this->db->prepare("SELECT valor FROM inv_parametros WHERE clave = :clave");
            $stmt->execute([':clave' => $clave]);
            $resultado = $stmt->fetch();
            return $resultado ? $resultado['valor'] : $defecto;
        } catch (PDOException $e) {
            return $defecto;
        }
    }

    public function guardar($clave, $valor, $descripcion = '') {
        try {
            $stmt = $this->db->prepare("UPDATE inv_parametros SET valor = :valor, descripcion = :descripcion WHERE clave = :clave");
            $stmt->execute([
                ':clave' => $clave,
                ':valor' => $valor,
                ':descripcion' => $descripcion
            ]);
            
            if ($stmt->rowCount() === 0) {
                $stmtInsert = $this->db->prepare("INSERT INTO inv_parametros (clave, valor, descripcion) VALUES (:clave, :valor, :descripcion)");
                return $stmtInsert->execute([
                    ':clave' => $clave,
                    ':valor' => $valor,
                    ':descripcion' => $descripcion
                ]);
            }
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Obtiene todos los parámetros registrados
     * @return array
     */
    public function obtenerTodos() {
        $stmt = $this->db->query("SELECT * FROM inv_parametros ORDER BY clave ASC");
        return $stmt->fetchAll();
    }

    /**
     * Tiempo de inactividad centralizado — consulta PORTAL_APM (cascada
     * usuario > módulo 'CONTROL_BIENES' > global, configurable desde
     * /admin/inactividad del portal). Antes esta app solo miraba su propia
     * tabla `inv_parametros`, desconectada de cualquier otro módulo. Si la
     * conexión cruzada falla (BD del portal no disponible), cae al parámetro
     * local `tiempo_inactividad` para no dejar el sistema sin control.
     */
    public function obtenerInactividadSegundos(int $idUsuario, int $fallback = 600): int {
        try {
            $stmt = $this->db->prepare('SELECT PORTAL_APM.dbo.fn_InactividadSegundos(:id, :modulo) AS v');
            $stmt->execute([':id' => $idUsuario, ':modulo' => 'CONTROL_BIENES']);
            $row = $stmt->fetch();
            if ($row && $row['v'] !== null) return (int)$row['v'];
        } catch (\Throwable $e) {
            // sin conexión al portal: se usa el respaldo local de abajo.
        }
        return (int)$this->obtener('tiempo_inactividad', $fallback);
    }

    public function obtenerInactividadAvisoSegundos(int $idUsuario, int $fallback = 60): int {
        try {
            $stmt = $this->db->prepare('SELECT PORTAL_APM.dbo.fn_InactividadAvisoSegundos(:id, :modulo) AS v');
            $stmt->execute([':id' => $idUsuario, ':modulo' => 'CONTROL_BIENES']);
            $row = $stmt->fetch();
            if ($row && $row['v'] !== null) return (int)$row['v'];
        } catch (\Throwable $e) {
        }
        return $fallback;
    }
}

class InvParametro extends ParametroModel {}
