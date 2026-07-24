<?php
class MovimientoModel extends Model {

    public function getByBien(int $idActivo): array {
        $stmt = $this->query(
            "SELECT m.*, u.nombre_completo AS responsable
             FROM BIENES_Movimientos m
             LEFT JOIN CORE_Usuarios u ON u.id_usuario = m.creado_por
             WHERE m.id_activo = ?
             ORDER BY m.fecha_movimiento DESC",
            [[$idActivo, SQLSRV_PARAM_IN]]
        );
        return $this->fetchAll($stmt);
    }

    public function getAll(int $page = 1, int $perPage = 20): array {
        $offset = ($page - 1) * $perPage;
        $stmt = $this->query(
            "SELECT m.id_movimiento, m.tipo_movimiento, m.fecha_movimiento,
                    m.observaciones, m.id_activo,
                    b.codigo, b.nombre AS bien_nombre,
                    u.nombre_completo AS responsable
             FROM BIENES_Movimientos m
             JOIN BIENES_Activos b ON b.id_activo = m.id_activo
             LEFT JOIN CORE_Usuarios u ON u.id_usuario = m.creado_por
             ORDER BY m.fecha_movimiento DESC
             OFFSET ? ROWS FETCH NEXT ? ROWS ONLY",
            [[$offset, SQLSRV_PARAM_IN], [$perPage, SQLSRV_PARAM_IN]]
        );
        return $this->fetchAll($stmt);
    }

    public function create(array $data): int {
        $idActivo = (int)($data['id_activo'] ?? 0);
        $stmt = $this->query(
            "INSERT INTO BIENES_Movimientos
                (id_activo, tipo_movimiento, id_depto_origen,
                 id_depto_destino, observaciones, creado_por)
             OUTPUT INSERTED.id_movimiento
             VALUES (?,?,?,?,?,?)",
            [
                [$idActivo,                              SQLSRV_PARAM_IN],
                [$data['tipo_movimiento'],               SQLSRV_PARAM_IN],
                [$data['id_depto_origen']  ?? null,      SQLSRV_PARAM_IN],
                [$data['id_depto_destino'] ?? null,      SQLSRV_PARAM_IN],
                [$data['observaciones']    ?? null,      SQLSRV_PARAM_IN],
                [(int)($_SESSION['user_id'] ?? 0),       SQLSRV_PARAM_IN],
            ]
        );
        if (($data['tipo_movimiento'] ?? '') === 'Transferencia' && !empty($data['id_depto_destino'])) {
            $this->query(
                'UPDATE BIENES_Activos SET id_departamento = ? WHERE id_activo = ?',
                [[(int)$data['id_depto_destino'], SQLSRV_PARAM_IN], [$idActivo, SQLSRV_PARAM_IN]]
            );
        }
        $row = $this->fetch($stmt);
        return (int)($row['id_movimiento'] ?? 0);
    }
}
