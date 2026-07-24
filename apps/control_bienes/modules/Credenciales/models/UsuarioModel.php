<?php
/**
 * USUARIOMODEL.PHP - Modelo de Control de Acceso (Usuarios)
 * Compatible con PHP 7.4, 8.3 y 8.4
 */

require_once ROOT_PATH . 'core/Model.php';
require_once ROOT_PATH . 'modules/Central/models/InvSecuencial.php';

class UsuarioModel extends Model {

    public function obtenerTodos() {
        $stmt = $this->db->query("SELECT * FROM inv_usuarios ORDER BY id ASC");
        return $stmt->fetchAll();
    }

    public function obtenerActivos() {
        $stmt = $this->db->query("SELECT * FROM inv_usuarios WHERE activo = 1 ORDER BY id ASC");
        return $stmt->fetchAll();
    }

    public function buscarPorId($id) {
        $stmt = $this->db->prepare("SELECT * FROM inv_usuarios WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    public function buscarPorUsuario($usuario) {
        $driver = defined('DB_DRIVER') ? DB_DRIVER : 'sqlite';
        $sql = ($driver === 'sqlsrv') ? 
               "SELECT TOP 1 * FROM inv_usuarios WHERE usuario = :usr AND activo = 1" : 
               "SELECT * FROM inv_usuarios WHERE usuario = :usr AND activo = 1 LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':usr' => $usuario]);
        return $stmt->fetch();
    }

    public function crear($datos) {
        $secuencialObj = new InvSecuencial();
        $secuencial = $secuencialObj->generarSiguiente('acc');

        $hash = password_hash($datos['contrasena'], PASSWORD_DEFAULT);

        $stmt = $this->db->prepare("INSERT INTO inv_usuarios (secuencial, nombre, usuario, contrasena, rol, activo) VALUES (:sec, :nombre, :usuario, :pass, :rol, 1)");
        $stmt->execute([
            ':sec' => $secuencial,
            ':nombre' => $datos['nombre'],
            ':usuario' => $datos['usuario'],
            ':pass' => $hash,
            ':rol' => $datos['rol']
        ]);
        
        return $this->buscarPorId($this->db->lastInsertId());
    }

    public function actualizar($id, $datos) {
        if (!empty($datos['contrasena'])) {
            $hash = password_hash($datos['contrasena'], PASSWORD_DEFAULT);
            $stmt = $this->db->prepare("UPDATE inv_usuarios SET nombre = :nombre, usuario = :usuario, contrasena = :pass, rol = :rol, activo = :activo WHERE id = :id");
            $stmt->execute([
                ':nombre' => $datos['nombre'],
                ':usuario' => $datos['usuario'],
                ':pass' => $hash,
                ':rol' => $datos['rol'],
                ':activo' => isset($datos['activo']) ? (int)$datos['activo'] : 1,
                ':id' => $id
            ]);
        } else {
            $stmt = $this->db->prepare("UPDATE inv_usuarios SET nombre = :nombre, usuario = :usuario, rol = :rol, activo = :activo WHERE id = :id");
            $stmt->execute([
                ':nombre' => $datos['nombre'],
                ':usuario' => $datos['usuario'],
                ':rol' => $datos['rol'],
                ':activo' => isset($datos['activo']) ? (int)$datos['activo'] : 1,
                ':id' => $id
            ]);
        }
        return $this->buscarPorId($id);
    }

    public function eliminar($id) {
        $stmt = $this->db->prepare("DELETE FROM inv_usuarios WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }
}

class InvUsuario extends UsuarioModel {}
