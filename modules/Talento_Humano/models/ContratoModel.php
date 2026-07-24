<?php
class ContratoModel extends Model {

    public function getByEmpleado(int $idEmpleado): array {
        $stmt = $this->query(
            'SELECT c.*, e.nombres + \' \' + e.apellidos AS empleado_nombre
             FROM TH_Contratos c
             JOIN TH_Empleados e ON e.id_empleado = c.id_empleado
             WHERE c.id_empleado = ?
             ORDER BY c.fecha_inicio DESC',
            [[$idEmpleado, SQLSRV_PARAM_IN]]
        );
        return $this->fetchAll($stmt);
    }

    public function getAll(int $page = 1, int $perPage = 20): array {
        $offset = ($page - 1) * $perPage;
        $stmt = $this->query(
            "SELECT c.id_contrato,
                    'CONT-' + CAST(YEAR(c.fecha_inicio) AS NVARCHAR(4)) + '-' + RIGHT('0000' + CAST(c.id_contrato AS NVARCHAR(5)), 4) AS numero_contrato,
                    c.tipo_contrato, c.fecha_inicio, c.fecha_fin, c.estado_contrato, c.salario, c.cargo,
                    e.nombres + ' ' + e.apellidos AS empleado_nombre,
                    d.nombre AS departamento
             FROM TH_Contratos c
             JOIN TH_Empleados e ON e.id_empleado = c.id_empleado
             LEFT JOIN CORE_Departamentos d ON d.id_departamento = c.id_departamento
             ORDER BY c.fecha_fin ASC
             OFFSET ? ROWS FETCH NEXT ? ROWS ONLY",
            [[$offset, SQLSRV_PARAM_IN], [$perPage, SQLSRV_PARAM_IN]]
        );
        return $this->fetchAll($stmt);
    }

    public function getProximosVencer(int $days = 90): array {
        $stmt = $this->query(
            "SELECT c.id_contrato,
                    'CONT-' + CAST(YEAR(c.fecha_inicio) AS NVARCHAR(4)) + '-' + RIGHT('0000' + CAST(c.id_contrato AS NVARCHAR(5)), 4) AS numero_contrato,
                    c.fecha_fin, c.estado_contrato,
                    e.nombres + ' ' + e.apellidos AS empleado_nombre,
                    DATEDIFF(DAY, GETDATE(), c.fecha_fin) AS dias_restantes
             FROM TH_Contratos c
             JOIN TH_Empleados e ON e.id_empleado = c.id_empleado
             WHERE c.estado_contrato = 'Vigente'
               AND c.fecha_fin BETWEEN GETDATE() AND DATEADD(DAY, ?, GETDATE())
             ORDER BY c.fecha_fin ASC",
            [[$days, SQLSRV_PARAM_IN]]
        );
        return $this->fetchAll($stmt);
    }

    public function findById(int $id): ?array {
        $stmt = $this->query(
            "SELECT c.id_contrato,
                    'CONT-' + CAST(YEAR(c.fecha_inicio) AS NVARCHAR(4)) + '-' + RIGHT('0000' + CAST(c.id_contrato AS NVARCHAR(5)), 4) AS numero_contrato,
                    c.id_empleado, c.tipo_contrato, c.fecha_inicio, c.fecha_fin,
                    c.salario, c.cargo, c.id_departamento, c.estado_contrato, c.observaciones, c.fecha_creacion,
                    e.nombres + ' ' + e.apellidos AS empleado_nombre
             FROM TH_Contratos c
             JOIN TH_Empleados e ON e.id_empleado = c.id_empleado
             WHERE c.id_contrato = ?",
            [[$id, SQLSRV_PARAM_IN]]
        );
        return $this->fetch($stmt);
    }

    public function create(array $data): int {
        $stmt = $this->query(
            "INSERT INTO TH_Contratos
                (id_empleado, tipo_contrato, cargo, salario,
                 fecha_inicio, fecha_fin, estado_contrato, observaciones)
             OUTPUT INSERTED.id_contrato
             VALUES (?,?,?,?,?,?,?,?)",
            [
                [(int)$data['id_empleado'],      SQLSRV_PARAM_IN],
                [$data['tipo_contrato'],          SQLSRV_PARAM_IN],
                [$data['cargo'],                  SQLSRV_PARAM_IN],
                [(float)$data['salario'],         SQLSRV_PARAM_IN],
                [$data['fecha_inicio'],           SQLSRV_PARAM_IN],
                [$data['fecha_fin'] ?? null,      SQLSRV_PARAM_IN],
                [$data['estado_contrato'] ?? 'Vigente',    SQLSRV_PARAM_IN],
                [$data['observaciones'] ?? null,  SQLSRV_PARAM_IN],
            ]
        );
        $row = $this->fetch($stmt);
        return (int)($row['id_contrato'] ?? 0);
    }

    public function update(int $id, array $data): bool {
        $stmt = $this->query(
            "UPDATE TH_Contratos SET
                tipo_contrato = ?, cargo = ?, salario = ?,
                fecha_inicio = ?, fecha_fin = ?, estado_contrato = ?, observaciones = ?
             WHERE id_contrato = ?",
            [
                [$data['tipo_contrato'],         SQLSRV_PARAM_IN],
                [$data['cargo'],                 SQLSRV_PARAM_IN],
                [(float)$data['salario'],        SQLSRV_PARAM_IN],
                [$data['fecha_inicio'],          SQLSRV_PARAM_IN],
                [$data['fecha_fin'] ?? null,     SQLSRV_PARAM_IN],
                [$data['estado_contrato'],                SQLSRV_PARAM_IN],
                [$data['observaciones'] ?? null, SQLSRV_PARAM_IN],
                [$id,                            SQLSRV_PARAM_IN],
            ]
        );
        return $this->rowsAffected($stmt) > 0;
    }
}
