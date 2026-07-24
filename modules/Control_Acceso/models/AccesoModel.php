<?php
class AccesoModel extends Model {

    public function getHoy(): array {
        $stmt = $this->query(
            "SELECT r.id_registro, r.tipo_acceso, r.persona_visita,
                    r.motivo, r.estado_registro, r.fecha_hora
             FROM ACCESO_Registros r
             WHERE CAST(r.fecha_hora AS DATE) = CAST(GETDATE() AS DATE)
               AND r.tipo_acceso = 'Entrada'
             ORDER BY r.fecha_hora DESC"
        );
        return $this->fetchAll($stmt);
    }

    public function getAll(int $page = 1, int $perPage = 30): array {
        $offset = ($page - 1) * $perPage;
        $stmt = $this->query(
            "SELECT r.id_registro, r.tipo_acceso, r.persona_visita,
                    r.motivo, r.estado_registro, r.fecha_hora
             FROM ACCESO_Registros r
             ORDER BY r.fecha_hora DESC
             OFFSET ? ROWS FETCH NEXT ? ROWS ONLY",
            [[$offset, SQLSRV_PARAM_IN], [$perPage, SQLSRV_PARAM_IN]]
        );
        return $this->fetchAll($stmt);
    }

    public function registrarIngreso(array $data): int {
        $stmt = $this->query(
            "INSERT INTO ACCESO_Registros
                (tipo_acceso, persona_visita, motivo, id_operador)
             OUTPUT INSERTED.id_registro
             VALUES (?,?,?,?)",
            [
                ['Entrada',                              SQLSRV_PARAM_IN],
                [$data['persona_visita'] ?? null,        SQLSRV_PARAM_IN],
                [$data['motivo']         ?? null,        SQLSRV_PARAM_IN],
                [(int)($_SESSION['user_id'] ?? 0),       SQLSRV_PARAM_IN],
            ]
        );
        $row = $this->fetch($stmt);
        return (int)($row['id_registro'] ?? 0);
    }

    public function registrarSalida(int $idRegistro): bool {
        $stmt = $this->query(
            "UPDATE ACCESO_Registros SET estado_registro = 'Finalizado'
             WHERE id_registro = ? AND tipo_acceso = 'Entrada' AND estado_registro = 'Activo'",
            [[$idRegistro, SQLSRV_PARAM_IN]]
        );
        return $this->rowsAffected($stmt) > 0;
    }

    public function getEmpleados(): array {
        $stmt = $this->query(
            'SELECT id_empleado, nombres + \' \' + apellidos AS nombre_completo
             FROM TH_Empleados WHERE estado=1 ORDER BY apellidos'
        );
        return $this->fetchAll($stmt);
    }
}
