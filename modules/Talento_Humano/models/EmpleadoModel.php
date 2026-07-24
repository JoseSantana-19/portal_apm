<?php
class EmpleadoModel extends Model {

    public function getAll(int $page = 1, int $perPage = 20, array $filters = []): array {
        $offset = ($page - 1) * $perPage;
        $where  = 'WHERE 1=1';
        $params = [];

        if (!empty($filters['nombre'])) {
            $where   .= ' AND (e.nombres LIKE ? OR e.apellidos LIKE ?)';
            $like     = '%' . $filters['nombre'] . '%';
            $params[] = [$like, SQLSRV_PARAM_IN];
            $params[] = [$like, SQLSRV_PARAM_IN];
        }
        if (!empty($filters['id_departamento'])) {
            $where   .= ' AND e.id_departamento = ?';
            $params[] = [(int)$filters['id_departamento'], SQLSRV_PARAM_IN];
        }
        if (isset($filters['activo'])) {
            $where   .= ' AND e.estado = ?';
            $params[] = [(int)$filters['activo'], SQLSRV_PARAM_IN];
        }

        $sql = "SELECT e.id_empleado, e.cedula, e.nombres, e.apellidos,
                       e.correo, e.cargo, e.estado,
                       d.nombre AS departamento, e.fecha_ingreso
                FROM TH_Empleados e
                LEFT JOIN CORE_Departamentos d ON d.id_departamento = e.id_departamento
                {$where}
                ORDER BY e.apellidos, e.nombres
                OFFSET ? ROWS FETCH NEXT ? ROWS ONLY";

        $params[] = [$offset, SQLSRV_PARAM_IN];
        $params[] = [$perPage, SQLSRV_PARAM_IN];

        $stmt = $this->query($sql, $params);
        return $this->fetchAll($stmt);
    }

    public function count(array $filters = []): int {
        $where  = 'WHERE 1=1';
        $params = [];
        if (!empty($filters['nombre'])) {
            $like = '%' . $filters['nombre'] . '%';
            $where   .= ' AND (nombres LIKE ? OR apellidos LIKE ?)';
            $params[] = [$like, SQLSRV_PARAM_IN];
            $params[] = [$like, SQLSRV_PARAM_IN];
        }
        $stmt = $this->query("SELECT COUNT(*) AS total FROM TH_Empleados {$where}", $params);
        $row  = $this->fetch($stmt);
        return (int)($row['total'] ?? 0);
    }

    public function findById(int $id): ?array {
        // Tabla directa (no vw_FichaEmpleado): la vista no expone id_departamento
        // ni nombres/apellidos separados, que el formulario de edicion necesita.
        $stmt = $this->query(
            'SELECT e.*, d.nombre AS departamento
             FROM TH_Empleados e
             LEFT JOIN CORE_Departamentos d ON d.id_departamento = e.id_departamento
             WHERE e.id_empleado = ?',
            [[$id, SQLSRV_PARAM_IN]]
        );
        return $this->fetch($stmt);
    }

    public function create(array $data): int {
        $sql = "INSERT INTO TH_Empleados
                    (cedula, nombres, apellidos, correo,
                     telefono, cargo, id_departamento, fecha_ingreso)
                OUTPUT INSERTED.id_empleado
                VALUES (?,?,?,?,?,?,?,?)";

        $stmt = $this->query($sql, [
            [$data['cedula'],                                        SQLSRV_PARAM_IN],
            [$data['nombres'],                                       SQLSRV_PARAM_IN],
            [$data['apellidos'],                                     SQLSRV_PARAM_IN],
            [$data['correo_institucional'] ?? $data['correo'] ?? null, SQLSRV_PARAM_IN],
            [$data['telefono']             ?? null,                  SQLSRV_PARAM_IN],
            [$data['cargo'],                                         SQLSRV_PARAM_IN],
            [(int)$data['id_departamento'],                          SQLSRV_PARAM_IN],
            [$data['fecha_ingreso'],                                 SQLSRV_PARAM_IN],
        ]);
        $row = $this->fetch($stmt);
        return (int)($row['id_empleado'] ?? 0);
    }

    public function update(int $id, array $data): bool {
        $stmt = $this->query(
            "UPDATE TH_Empleados SET
                cedula = ?, nombres = ?, apellidos = ?,
                correo = ?,
                telefono = ?, cargo = ?, id_departamento = ?
             WHERE id_empleado = ?",
            [
                [$data['cedula'],                                          SQLSRV_PARAM_IN],
                [$data['nombres'],                                         SQLSRV_PARAM_IN],
                [$data['apellidos'],                                       SQLSRV_PARAM_IN],
                [$data['correo_institucional'] ?? $data['correo'] ?? null, SQLSRV_PARAM_IN],
                [$data['telefono'] ?? null,                                SQLSRV_PARAM_IN],
                [$data['cargo'],                                           SQLSRV_PARAM_IN],
                [(int)$data['id_departamento'],                            SQLSRV_PARAM_IN],
                [$id,                                                      SQLSRV_PARAM_IN],
            ]
        );
        return $this->rowsAffected($stmt) > 0;
    }

    public function deactivate(int $id): bool {
        $stmt = $this->query(
            'UPDATE TH_Empleados SET estado = 0 WHERE id_empleado = ?',
            [[$id, SQLSRV_PARAM_IN]]
        );
        return $this->rowsAffected($stmt) > 0;
    }

    public function getDepartamentos(): array {
        $stmt = $this->query('SELECT id_departamento, nombre FROM CORE_Departamentos WHERE estado=1 ORDER BY nombre');
        return $this->fetchAll($stmt);
    }
}
