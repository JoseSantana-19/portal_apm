<?php
/**
 * PERMISOMODEL.PHP - Modelo de Permisos por Usuario
 * Gestiona la tabla inv_permisos que controla qué rutas puede ver cada usuario
 * Compatible con PHP 7.4, 8.3 y 8.4
 */

require_once ROOT_PATH . 'core/Model.php';

class PermisoModel extends Model {

    public function __construct() {
        parent::__construct();
        $this->crearTablasSiNoExisten();
    }

    private function crearTablasSiNoExisten() {
        $driver = defined('DB_DRIVER') ? DB_DRIVER : 'sqlite';
        if ($driver === 'pgsql') {
            $this->db->exec("CREATE TABLE IF NOT EXISTS inv_permisos (
                id         SERIAL PRIMARY KEY,
                usuario_id INT NOT NULL,
                route_key  VARCHAR(255) NOT NULL,
                UNIQUE(usuario_id, route_key),
                FOREIGN KEY (usuario_id) REFERENCES inv_usuarios(id) ON DELETE CASCADE
            );");
        } elseif ($driver === 'sqlsrv') {
            $check = $this->db->query("SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = 'inv_permisos'");
            if ($check === false || $check->fetchColumn() === false) {
                $this->db->exec("CREATE TABLE inv_permisos (
                    id         INT IDENTITY(1,1) PRIMARY KEY,
                    usuario_id INT NOT NULL,
                    route_key  NVARCHAR(255) NOT NULL,
                    UNIQUE(usuario_id, route_key),
                    FOREIGN KEY (usuario_id) REFERENCES inv_usuarios(id) ON DELETE CASCADE
                );");
            }
        } else {
            $this->db->exec("CREATE TABLE IF NOT EXISTS inv_permisos (
                id         INTEGER PRIMARY KEY AUTOINCREMENT,
                usuario_id INTEGER NOT NULL,
                route_key  TEXT    NOT NULL,
                UNIQUE(usuario_id, route_key),
                FOREIGN KEY (usuario_id) REFERENCES inv_usuarios(id) ON DELETE CASCADE
            );");
        }
    }

    /**
     * Obtiene array de route_keys permitidas para un usuario
     */
    public function obtenerPermisosUsuario(int $usuarioId): array {
        $stmt = $this->db->prepare(
            "SELECT route_key FROM inv_permisos WHERE usuario_id = :uid ORDER BY route_key ASC"
        );
        $stmt->execute([':uid' => $usuarioId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    }

    /**
     * Reemplaza los permisos completos de un usuario con el nuevo array
     */
    public function actualizarPermisos(int $usuarioId, array $permisos): void {
        $del = $this->db->prepare("DELETE FROM inv_permisos WHERE usuario_id = :uid");
        $del->execute([':uid' => $usuarioId]);

        if (!empty($permisos)) {
            $ins = $this->db->prepare(
                "INSERT INTO inv_permisos (usuario_id, route_key) VALUES (:uid, :rk)"
            );
            foreach ($permisos as $rk) {
                $rk = trim($rk);
                if ($rk !== '') {
                    $ins->execute([':uid' => $usuarioId, ':rk' => $rk]);
                }
            }
        }
    }

    /**
     * Verifica si un usuario tiene acceso a una ruta específica
     */
    public function tienePermiso(int $usuarioId, string $routeKey, string $rol = ''): bool {
        if (strtolower($rol) === 'administrador') {
            return true;
        }

        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM inv_permisos WHERE usuario_id = :uid AND route_key = :rk"
        );
        $stmt->execute([':uid' => $usuarioId, ':rk' => $routeKey]);
        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Obtiene los permisos de todos los usuarios (para la pantalla de admin)
     */
    public function obtenerTodosLosPermisos(): array {
        $stmt = $this->db->query(
            "SELECT p.usuario_id, p.route_key, u.nombre
             FROM inv_permisos p
             JOIN inv_usuarios u ON p.usuario_id = u.id
             ORDER BY u.nombre, p.route_key"
        );
        return $stmt->fetchAll();
    }
}

class InvPermiso extends PermisoModel {}
