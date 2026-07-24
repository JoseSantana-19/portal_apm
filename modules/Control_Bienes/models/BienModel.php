<?php
class BienModel extends Model {

    public function getAll(int $page = 1, int $perPage = 20, array $filters = []): array {
        $offset = ($page - 1) * $perPage;
        $where  = 'WHERE 1=1';
        $params = [];

        if (!empty($filters['codigo'])) {
            $where   .= ' AND b.codigo LIKE ?';
            $params[] = ['%' . $filters['codigo'] . '%', SQLSRV_PARAM_IN];
        }
        if (!empty($filters['estado_bien'])) {
            $where   .= ' AND b.estado_bien = ?';
            $params[] = [$filters['estado_bien'], SQLSRV_PARAM_IN];
        }
        if (!empty($filters['categoria'])) {
            $where   .= ' AND b.id_categoria = ?';
            $params[] = [(int)$filters['categoria'], SQLSRV_PARAM_IN];
        }

        $stmt = $this->query(
            "SELECT b.id_activo, b.codigo, b.nombre, b.descripcion,
                    b.estado_bien, b.valor_adquisicion, b.fecha_adquisicion,
                    c.nombre AS categoria,
                    d.nombre AS departamento
             FROM BIENES_Activos b
             LEFT JOIN BIENES_Categorias  c ON c.id_categoria   = b.id_categoria
             LEFT JOIN CORE_Departamentos d ON d.id_departamento = b.id_departamento
             {$where}
             ORDER BY b.codigo
             OFFSET ? ROWS FETCH NEXT ? ROWS ONLY",
            array_merge($params, [[$offset, SQLSRV_PARAM_IN], [$perPage, SQLSRV_PARAM_IN]])
        );
        return $this->fetchAll($stmt);
    }

    public function count(array $filters = []): int {
        $where  = 'WHERE 1=1';
        $params = [];
        if (!empty($filters['estado_bien'])) {
            $where   .= ' AND estado_bien = ?';
            $params[] = [$filters['estado_bien'], SQLSRV_PARAM_IN];
        }
        $stmt = $this->query("SELECT COUNT(*) AS total FROM BIENES_Activos {$where}", $params);
        $row  = $this->fetch($stmt);
        return (int)($row['total'] ?? 0);
    }

    public function findById(int $id): ?array {
        $stmt = $this->query(
            "SELECT b.*, c.nombre AS categoria, d.nombre AS departamento
             FROM BIENES_Activos b
             LEFT JOIN BIENES_Categorias  c ON c.id_categoria   = b.id_categoria
             LEFT JOIN CORE_Departamentos d ON d.id_departamento = b.id_departamento
             WHERE b.id_activo = ?",
            [[$id, SQLSRV_PARAM_IN]]
        );
        return $this->fetch($stmt);
    }

    public function create(array $data): int {
        $stmt = $this->query(
            "INSERT INTO BIENES_Activos
                (codigo, nombre, descripcion, id_categoria,
                 id_departamento, valor_adquisicion, fecha_adquisicion, estado_bien)
             OUTPUT INSERTED.id_activo
             VALUES (?,?,?,?,?,?,?,?)",
            [
                [$data['codigo'],                        SQLSRV_PARAM_IN],
                [$data['nombre'],                        SQLSRV_PARAM_IN],
                [$data['descripcion'] ?? null,           SQLSRV_PARAM_IN],
                [(int)$data['id_categoria'],             SQLSRV_PARAM_IN],
                [(int)($data['id_departamento'] ?? 0),   SQLSRV_PARAM_IN],
                [(float)$data['valor_adquisicion'],      SQLSRV_PARAM_IN],
                [$data['fecha_adquisicion'],             SQLSRV_PARAM_IN],
                [$data['estado_bien'] ?? 'Activo',       SQLSRV_PARAM_IN],
            ]
        );
        $row = $this->fetch($stmt);
        return (int)($row['id_activo'] ?? 0);
    }

    public function update(int $id, array $data): bool {
        $stmt = $this->query(
            "UPDATE BIENES_Activos SET
                nombre = ?, descripcion = ?, id_categoria = ?,
                id_departamento = ?, estado_bien = ?
             WHERE id_activo = ?",
            [
                [$data['nombre'],                       SQLSRV_PARAM_IN],
                [$data['descripcion'] ?? null,          SQLSRV_PARAM_IN],
                [(int)$data['id_categoria'],            SQLSRV_PARAM_IN],
                [(int)($data['id_departamento'] ?? 0),  SQLSRV_PARAM_IN],
                [$data['estado_bien'],                  SQLSRV_PARAM_IN],
                [$id,                                   SQLSRV_PARAM_IN],
            ]
        );
        return $this->rowsAffected($stmt) > 0;
    }

    public function darBaja(int $id, string $motivo): bool {
        $stmt = $this->query(
            "UPDATE BIENES_Activos SET estado_bien = 'Baja' WHERE id_activo = ?",
            [[$id, SQLSRV_PARAM_IN]]
        );
        return $this->rowsAffected($stmt) > 0;
    }

    public function getCategorias(): array {
        $stmt = $this->query('SELECT id_categoria, nombre FROM BIENES_Categorias ORDER BY nombre');
        return $this->fetchAll($stmt);
    }
}
