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
        $this->crearTablaPermisosDetalle();
        $this->migrarPermisosAnteriores();
    }

    private function crearTablaPermisosDetalle(): void {
        $driver = defined('DB_DRIVER') ? DB_DRIVER : 'sqlite';
        if ($driver === 'pgsql') {
            $this->db->exec("CREATE TABLE IF NOT EXISTS inv_permisos_detalle (
                id SERIAL PRIMARY KEY, usuario_id INT NOT NULL, route_key VARCHAR(255) NOT NULL,
                scope_key VARCHAR(255) NOT NULL DEFAULT '*', can_read SMALLINT NOT NULL DEFAULT 0,
                can_create SMALLINT NOT NULL DEFAULT 0, can_edit SMALLINT NOT NULL DEFAULT 0,
                full_control SMALLINT NOT NULL DEFAULT 0,
                UNIQUE(usuario_id, route_key, scope_key),
                FOREIGN KEY (usuario_id) REFERENCES inv_usuarios(id) ON DELETE CASCADE
            )");
        } elseif ($driver === 'sqlsrv') {
            $check = $this->db->query("SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = 'inv_permisos_detalle'");
            if ($check === false || $check->fetchColumn() === false) {
                $this->db->exec("CREATE TABLE inv_permisos_detalle (
                    id INT IDENTITY(1,1) PRIMARY KEY, usuario_id INT NOT NULL, route_key NVARCHAR(255) NOT NULL,
                    scope_key NVARCHAR(255) NOT NULL DEFAULT '*', can_read TINYINT NOT NULL DEFAULT 0,
                    can_create TINYINT NOT NULL DEFAULT 0, can_edit TINYINT NOT NULL DEFAULT 0,
                    full_control TINYINT NOT NULL DEFAULT 0,
                    CONSTRAINT uq_inv_perm_det UNIQUE(usuario_id, route_key, scope_key),
                    FOREIGN KEY (usuario_id) REFERENCES inv_usuarios(id) ON DELETE CASCADE
                )");
            }
        } else {
            $this->db->exec("CREATE TABLE IF NOT EXISTS inv_permisos_detalle (
                id INTEGER PRIMARY KEY AUTOINCREMENT, usuario_id INTEGER NOT NULL, route_key TEXT NOT NULL,
                scope_key TEXT NOT NULL DEFAULT '*', can_read INTEGER NOT NULL DEFAULT 0,
                can_create INTEGER NOT NULL DEFAULT 0, can_edit INTEGER NOT NULL DEFAULT 0,
                full_control INTEGER NOT NULL DEFAULT 0,
                UNIQUE(usuario_id, route_key, scope_key),
                FOREIGN KEY (usuario_id) REFERENCES inv_usuarios(id) ON DELETE CASCADE
            )");
        }
    }

    private function migrarPermisosAnteriores(): void {
        $anteriores = $this->db->query("SELECT usuario_id, route_key FROM inv_permisos")->fetchAll();
        $buscar = $this->db->prepare("SELECT COUNT(*) FROM inv_permisos_detalle WHERE usuario_id = :uid AND route_key = :rk");
        // Los permisos heredados solo indicaban visibilidad del menú; no deben
        // convertirse implícitamente en autorización para modificar información.
        $insertar = $this->db->prepare("INSERT INTO inv_permisos_detalle (usuario_id, route_key, scope_key, can_read, can_create, can_edit, full_control) VALUES (:uid, :rk, '*', 1, 0, 0, 0)");
        foreach ($anteriores as $permiso) {
            $buscar->execute([':uid' => (int)$permiso['usuario_id'], ':rk' => $permiso['route_key']]);
            if ((int)$buscar->fetchColumn() === 0) {
                $insertar->execute([':uid' => (int)$permiso['usuario_id'], ':rk' => $permiso['route_key']]);
            }
        }
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

    public function obtenerMatrizUsuario(int $usuarioId): array {
        $stmt = $this->db->prepare("SELECT route_key, scope_key, can_read, can_create, can_edit, full_control FROM inv_permisos_detalle WHERE usuario_id = :uid ORDER BY route_key, scope_key");
        $stmt->execute([':uid' => $usuarioId]);
        $matriz = [];
        foreach ($stmt->fetchAll() as $fila) {
            $matriz[$fila['route_key']][$fila['scope_key']] = [
                'read' => (int)$fila['can_read'] === 1,
                'create' => (int)$fila['can_create'] === 1,
                'edit' => (int)$fila['can_edit'] === 1,
                'full' => (int)$fila['full_control'] === 1,
            ];
        }
        return $matriz;
    }

    public function actualizarMatriz(int $usuarioId, array $reglas): void {
        $this->beginTransaction();
        try {
            $this->db->prepare("DELETE FROM inv_permisos_detalle WHERE usuario_id = :uid")->execute([':uid' => $usuarioId]);
            $this->db->prepare("DELETE FROM inv_permisos WHERE usuario_id = :uid")->execute([':uid' => $usuarioId]);
            $insertar = $this->db->prepare("INSERT INTO inv_permisos_detalle (usuario_id, route_key, scope_key, can_read, can_create, can_edit, full_control) VALUES (:uid, :route, :scope, :leer, :crear, :editar, :total)");
            $insertarMenu = $this->db->prepare("INSERT INTO inv_permisos (usuario_id, route_key) VALUES (:uid, :route)");
            $rutasVisibles = [];
            foreach ($reglas as $regla) {
                $leer = !empty($regla['read']) || !empty($regla['create']) || !empty($regla['edit']) || !empty($regla['full']);
                if (!$leer) continue;
                $route = trim((string)$regla['route']);
                $scope = trim((string)$regla['scope']);
                $total = !empty($regla['full']);
                $insertar->execute([
                    ':uid' => $usuarioId, ':route' => $route, ':scope' => $scope,
                    ':leer' => 1, ':crear' => ($total || !empty($regla['create'])) ? 1 : 0,
                    ':editar' => ($total || !empty($regla['edit'])) ? 1 : 0, ':total' => $total ? 1 : 0,
                ]);
                $rutasVisibles[$route] = true;
            }
            foreach (array_keys($rutasVisibles) as $route) {
                $insertarMenu->execute([':uid' => $usuarioId, ':route' => $route]);
            }
            $this->commit();
        } catch (Throwable $e) {
            $this->rollBack();
            throw $e;
        }
    }

    public function tienePermisoAccion(int $usuarioId, string $route, string $scope, string $accion, string $rol = ''): bool {
        if (strtolower($rol) === 'administrador') return true;
        $campo = ['read' => 'can_read', 'create' => 'can_create', 'edit' => 'can_edit'][$accion] ?? 'can_read';
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM inv_permisos_detalle WHERE usuario_id = :uid AND route_key = :route AND (scope_key = :scope OR scope_key = '*') AND (full_control = 1 OR {$campo} = 1)");
        $stmt->execute([':uid' => $usuarioId, ':route' => $route, ':scope' => $scope]);
        return (int)$stmt->fetchColumn() > 0;
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
