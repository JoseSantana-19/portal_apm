<?php
class BitacoraModel extends Model {

    public function getAll(int $page = 1, int $perPage = 20, array $filters = []): array {
        $offset = ($page - 1) * $perPage;
        $where  = 'WHERE 1=1';
        $params = [];

        if (!empty($filters['estado_evento'])) {
            $where   .= ' AND b.estado_evento = ?';
            $params[] = [$filters['estado_evento'], SQLSRV_PARAM_IN];
        }
        if (!empty($filters['categoria'])) {
            $where   .= ' AND b.id_categoria = ?';
            $params[] = [(int)$filters['categoria'], SQLSRV_PARAM_IN];
        }

        $stmt = $this->query(
            "SELECT b.id_evento, b.titulo, b.descripcion, b.estado_evento,
                    b.prioridad, b.fecha_evento, b.fecha_creacion,
                    c.nombre AS categoria,
                    u.nombre_completo AS creado_por
             FROM BIT_Eventos b
             LEFT JOIN BIT_Categorias c ON c.id_categoria = b.id_categoria
             LEFT JOIN CORE_Usuarios u  ON u.id_usuario  = b.id_usuario
             {$where}
             ORDER BY b.fecha_creacion DESC
             OFFSET ? ROWS FETCH NEXT ? ROWS ONLY",
            array_merge($params, [[$offset, SQLSRV_PARAM_IN], [$perPage, SQLSRV_PARAM_IN]])
        );
        return $this->fetchAll($stmt);
    }

    public function count(array $filters = []): int {
        $where  = 'WHERE 1=1';
        $params = [];
        if (!empty($filters['estado_evento'])) {
            $where   .= ' AND estado_evento = ?';
            $params[] = [$filters['estado_evento'], SQLSRV_PARAM_IN];
        }
        $stmt = $this->query("SELECT COUNT(*) AS total FROM BIT_Eventos {$where}", $params);
        $row  = $this->fetch($stmt);
        return (int)($row['total'] ?? 0);
    }

    public function findById(int $id): ?array {
        $stmt = $this->query(
            "SELECT b.*, c.nombre AS categoria,
                    u.nombre_completo AS creado_por
             FROM BIT_Eventos b
             LEFT JOIN BIT_Categorias c ON c.id_categoria = b.id_categoria
             LEFT JOIN CORE_Usuarios u  ON u.id_usuario  = b.id_usuario
             WHERE b.id_evento = ?",
            [[$id, SQLSRV_PARAM_IN]]
        );
        return $this->fetch($stmt);
    }

    public function create(array $data): int {
        $stmt = $this->query(
            "INSERT INTO BIT_Eventos
                (titulo, descripcion, id_categoria, prioridad, estado_evento,
                 fecha_evento, id_usuario)
             OUTPUT INSERTED.id_evento
             VALUES (?,?,?,?,?,?,?)",
            [
                [$data['titulo'],                  SQLSRV_PARAM_IN],
                [$data['descripcion'] ?? null,     SQLSRV_PARAM_IN],
                [(int)$data['id_categoria'],       SQLSRV_PARAM_IN],
                [(int)($data['prioridad'] ?? 2),   SQLSRV_PARAM_IN],
                ['Pendiente',                      SQLSRV_PARAM_IN],
                [$data['fecha_evento'],            SQLSRV_PARAM_IN],
                [(int)($_SESSION['user_id'] ?? 0), SQLSRV_PARAM_IN],
            ]
        );
        $row = $this->fetch($stmt);
        return (int)($row['id_evento'] ?? 0);
    }

    public function update(int $id, array $data): bool {
        $stmt = $this->query(
            "UPDATE BIT_Eventos SET
                titulo = ?, descripcion = ?,
                id_categoria = ?, prioridad = ?, fecha_evento = ?
             WHERE id_evento = ?",
            [
                [$data['titulo'],                SQLSRV_PARAM_IN],
                [$data['descripcion'] ?? null,   SQLSRV_PARAM_IN],
                [(int)$data['id_categoria'],     SQLSRV_PARAM_IN],
                [(int)($data['prioridad'] ?? 2), SQLSRV_PARAM_IN],
                [$data['fecha_evento'],          SQLSRV_PARAM_IN],
                [$id,                            SQLSRV_PARAM_IN],
            ]
        );
        return $this->rowsAffected($stmt) > 0;
    }

    public function close(int $id, string $observaciones): bool {
        $stmt = $this->query(
            "UPDATE BIT_Eventos SET estado_evento = 'Cerrado', observaciones = ?, fecha_cierre = GETDATE()
             WHERE id_evento = ?",
            [[$observaciones, SQLSRV_PARAM_IN], [$id, SQLSRV_PARAM_IN]]
        );
        return $this->rowsAffected($stmt) > 0;
    }

    public function getCategorias(): array {
        $stmt = $this->query('SELECT id_categoria, nombre FROM BIT_Categorias ORDER BY nombre');
        return $this->fetchAll($stmt);
    }
}
