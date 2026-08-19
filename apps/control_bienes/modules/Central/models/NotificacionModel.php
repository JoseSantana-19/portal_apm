<?php
/** Notificaciones personales con lectura independiente por usuario. */
require_once ROOT_PATH . 'core/Model.php';

class NotificacionModel extends Model {
    private static $esquemaPreparado = false;

    public function __construct() {
        parent::__construct();
        $this->prepararEsquema();
    }

    public function crear($tipo, $categoria, $titulo, $mensaje, $secuencial = null, $usuarioId = null) {
        try {
            if ($usuarioId === null) $usuarioId = $this->usuarioActualId();
            $stmt = $this->db->prepare("INSERT INTO inv_notificaciones (tipo, categoria, titulo, mensaje, secuencial, usuario_id, creado_por_id) VALUES (:tipo, :categoria, :titulo, :mensaje, :secuencial, :usuario, :creado_por)");
            return $stmt->execute([
                ':tipo' => $tipo, ':categoria' => $categoria, ':titulo' => $titulo,
                ':mensaje' => $mensaje, ':secuencial' => $secuencial,
                ':usuario' => $usuarioId ?: null, ':creado_por' => $this->usuarioActualId() ?: null,
            ]);
        } catch (Throwable $e) {
            error_log('Error al crear notificación: ' . $e->getMessage());
            return false;
        }
    }

    public function obtenerNoVistas($usuarioId = null, $esAdmin = null) {
        $this->limpiarAntiguas(15);
        return array_values(array_filter($this->obtenerRecientes(30, $usuarioId, $esAdmin), static function ($n) {
            return (int)($n['visto'] ?? 0) === 0;
        }));
    }

    public function obtenerRecientes($limit = 30, $usuarioId = null, $esAdmin = null) {
        try {
            $usuarioId = $usuarioId === null ? $this->usuarioActualId() : (int)$usuarioId;
            $esAdmin = $esAdmin === null ? $this->esAdministradorActual() : (bool)$esAdmin;
            if (!$esAdmin && $usuarioId <= 0) return [];
            $limit = max(1, min(200, (int)$limit));
            $driver = defined('DB_DRIVER') ? DB_DRIVER : 'sqlite';
            $top = $driver === 'sqlsrv' ? 'TOP ' . $limit . ' ' : '';
            $final = $driver === 'sqlsrv' ? '' : ' LIMIT ' . $limit;
            $sql = "SELECT {$top}n.id, n.tipo, n.categoria, n.titulo, n.mensaje, n.secuencial,
                           n.created_at, n.usuario_id, n.creado_por_id, COALESCE(l.visto, 0) visto
                    FROM inv_notificaciones n
                    LEFT JOIN inv_notificaciones_lecturas l ON l.notificacion_id = n.id AND l.usuario_id = :lector
                    WHERE COALESCE(l.eliminada, 0) = 0";
            if (!$esAdmin) $sql .= ' AND n.usuario_id = :destinatario';
            $sql .= ' ORDER BY n.id DESC' . $final;
            $stmt = $this->db->prepare($sql);
            $params = [':lector' => $usuarioId];
            if (!$esAdmin) $params[':destinatario'] = $usuarioId;
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            error_log('Error al consultar notificaciones: ' . $e->getMessage());
            return [];
        }
    }

    public function marcarTodasComoVistas($usuarioId = null, $esAdmin = null) {
        return $this->actualizarEstadoVisible('visto', 1, $usuarioId, $esAdmin);
    }

    public function vaciarVisibles($usuarioId = null, $esAdmin = null) {
        return $this->actualizarEstadoVisible('eliminada', 1, $usuarioId, $esAdmin);
    }

    private function actualizarEstadoVisible(string $campo, int $valor, $usuarioId, $esAdmin) {
        $usuarioId = $usuarioId === null ? $this->usuarioActualId() : (int)$usuarioId;
        $esAdmin = $esAdmin === null ? $this->esAdministradorActual() : (bool)$esAdmin;
        if ($usuarioId <= 0 || !in_array($campo, ['visto', 'eliminada'], true)) return false;
        try {
            $notificaciones = $this->obtenerRecientes(200, $usuarioId, $esAdmin);
            $buscar = $this->db->prepare('SELECT id FROM inv_notificaciones_lecturas WHERE notificacion_id = :notificacion AND usuario_id = :usuario');
            $insertar = $this->db->prepare('INSERT INTO inv_notificaciones_lecturas (notificacion_id, usuario_id, visto, eliminada, fecha_lectura) VALUES (:notificacion, :usuario, :visto, :eliminada, CURRENT_TIMESTAMP)');
            $actualizar = $this->db->prepare("UPDATE inv_notificaciones_lecturas SET {$campo} = :valor, fecha_lectura = CURRENT_TIMESTAMP WHERE notificacion_id = :notificacion AND usuario_id = :usuario");
            foreach ($notificaciones as $notificacion) {
                $id = (int)$notificacion['id'];
                $buscar->execute([':notificacion' => $id, ':usuario' => $usuarioId]);
                if ($buscar->fetchColumn()) {
                    $actualizar->execute([':valor' => $valor, ':notificacion' => $id, ':usuario' => $usuarioId]);
                } else {
                    $insertar->execute([
                        ':notificacion' => $id, ':usuario' => $usuarioId,
                        ':visto' => $campo === 'visto' ? $valor : 0,
                        ':eliminada' => $campo === 'eliminada' ? $valor : 0,
                    ]);
                }
            }
            return true;
        } catch (Throwable $e) {
            error_log('Error al actualizar notificaciones: ' . $e->getMessage());
            return false;
        }
    }

    public function limpiarAntiguas($dias = 15) {
        try {
            $driver = defined('DB_DRIVER') ? DB_DRIVER : 'sqlite';
            if ($driver === 'sqlsrv') $sql = 'DELETE FROM inv_notificaciones WHERE created_at < DATEADD(day, -' . (int)$dias . ', GETDATE())';
            elseif ($driver === 'pgsql') $sql = "DELETE FROM inv_notificaciones WHERE created_at < NOW() - INTERVAL '" . (int)$dias . " days'";
            else $sql = "DELETE FROM inv_notificaciones WHERE created_at < datetime('now', '-" . (int)$dias . " days')";
            return $this->db->exec($sql);
        } catch (Throwable $e) { return false; }
    }

    public function vaciarTodas() {
        return $this->vaciarVisibles();
    }

    private function usuarioActualId(): int {
        if (session_status() === PHP_SESSION_NONE) @session_start();
        return (int)($_SESSION['usuario_id'] ?? $_SESSION['usuario']['id'] ?? 0);
    }

    private function esAdministradorActual(): bool {
        if (session_status() === PHP_SESSION_NONE) @session_start();
        return strtolower((string)($_SESSION['rol'] ?? $_SESSION['usuario']['rol'] ?? '')) === 'administrador';
    }

    private function prepararEsquema(): void {
        if (self::$esquemaPreparado) return;
        self::$esquemaPreparado = true;
        try {
            $driver = defined('DB_DRIVER') ? DB_DRIVER : 'sqlite';
            if ($driver === 'sqlsrv') {
                $columnas = $this->db->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'inv_notificaciones'")->fetchAll(PDO::FETCH_COLUMN);
                if (!in_array('usuario_id', $columnas, true)) $this->db->exec('ALTER TABLE inv_notificaciones ADD usuario_id INT NULL');
                if (!in_array('creado_por_id', $columnas, true)) $this->db->exec('ALTER TABLE inv_notificaciones ADD creado_por_id INT NULL');
                $this->db->exec("IF OBJECT_ID('inv_notificaciones_lecturas','U') IS NULL CREATE TABLE inv_notificaciones_lecturas (id INT IDENTITY(1,1) PRIMARY KEY, notificacion_id INT NOT NULL, usuario_id INT NOT NULL, visto TINYINT NOT NULL DEFAULT 0, eliminada TINYINT NOT NULL DEFAULT 0, fecha_lectura DATETIME2 NULL, CONSTRAINT uq_notificacion_lector UNIQUE(notificacion_id, usuario_id), CONSTRAINT fk_notif_lectura_notif FOREIGN KEY(notificacion_id) REFERENCES inv_notificaciones(id) ON DELETE CASCADE)");
            } elseif ($driver === 'pgsql') {
                $this->db->exec('ALTER TABLE inv_notificaciones ADD COLUMN IF NOT EXISTS usuario_id INT NULL');
                $this->db->exec('ALTER TABLE inv_notificaciones ADD COLUMN IF NOT EXISTS creado_por_id INT NULL');
                $this->db->exec('CREATE TABLE IF NOT EXISTS inv_notificaciones_lecturas (id SERIAL PRIMARY KEY, notificacion_id INT NOT NULL REFERENCES inv_notificaciones(id) ON DELETE CASCADE, usuario_id INT NOT NULL, visto SMALLINT NOT NULL DEFAULT 0, eliminada SMALLINT NOT NULL DEFAULT 0, fecha_lectura TIMESTAMP NULL, UNIQUE(notificacion_id, usuario_id))');
            } else {
                $columnas = $this->db->query('PRAGMA table_info(inv_notificaciones)')->fetchAll(PDO::FETCH_COLUMN, 1);
                if (!in_array('usuario_id', $columnas, true)) $this->db->exec('ALTER TABLE inv_notificaciones ADD COLUMN usuario_id INTEGER NULL');
                if (!in_array('creado_por_id', $columnas, true)) $this->db->exec('ALTER TABLE inv_notificaciones ADD COLUMN creado_por_id INTEGER NULL');
                $this->db->exec('CREATE TABLE IF NOT EXISTS inv_notificaciones_lecturas (id INTEGER PRIMARY KEY AUTOINCREMENT, notificacion_id INTEGER NOT NULL, usuario_id INTEGER NOT NULL, visto INTEGER NOT NULL DEFAULT 0, eliminada INTEGER NOT NULL DEFAULT 0, fecha_lectura DATETIME NULL, UNIQUE(notificacion_id, usuario_id), FOREIGN KEY(notificacion_id) REFERENCES inv_notificaciones(id) ON DELETE CASCADE)');
            }
        } catch (Throwable $e) {
            error_log('No se pudo preparar el esquema de notificaciones: ' . $e->getMessage());
        }
    }
}

class InvNotificacion extends NotificacionModel {}
