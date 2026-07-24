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
}

class InvParametro extends ParametroModel {}
