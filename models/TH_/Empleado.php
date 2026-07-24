<?php
/**
 * Empleado Model. Manages Talent Human (TH) operations, contract histories, and medical events.
 * Integrates directly with vw_FichaEmpleado and database transactions.
 * Uses the native sqlsrv driver via ThDatabase.
 */

require_once dirname(__DIR__, 2) . '/core/ThDatabase.php';

class Empleado {

    /**
     * Execute a parameterized query on the Talento Humano database.
     *
     * @param string $sql    SQL query with '?' placeholders.
     * @param array  $params Values for placeholders.
     * @return SqlSrvStatement
     */
    private function query(string $sql, array $params = []): SqlSrvStatement {
        return ThDatabase::query($sql, $params);
    }

    /**
     * Get paginated and filtered list of employees.
     * Uses vw_FichaEmpleado for rapid execution.
     */
    public function getList(array $filters = [], int $page = 1, int $limit = 10): array {
        $offset = ($page - 1) * $limit;
        $params = [];
        
        $where = ["1 = 1"];
        
        // Apply text filter (matches name, cedula, email)
        if (!empty($filters['search'])) {
            $where[] = "(nombre_completo LIKE ? OR cedula LIKE ? OR correo LIKE ? OR cargo_nominal LIKE ?)";
            $searchVal = "%" . $filters['search'] . "%";
            $params[] = $searchVal;
            $params[] = $searchVal;
            $params[] = $searchVal;
            $params[] = $searchVal;
        }

        // Apply department filter (matches the department code or any of its child area prefixes)
        if (!empty($filters['dept'])) {
            $where[] = "(codigo_depto = ? OR codigo_depto LIKE ?)";
            $params[] = $filters['dept'];
            $params[] = $filters['dept'] . "_%";
        }

        // Apply active status filter
        if (isset($filters['active']) && $filters['active'] !== '') {
            $where[] = "activo = ?";
            $params[] = (int)$filters['active'];
        }

        $whereClause = implode(" AND ", $where);

        // Fetch total count for pagination
        $countSql = "SELECT COUNT(*) as total FROM dbo.vw_FichaEmpleado WHERE {$whereClause}";
        $countStmt = $this->query($countSql, $params);
        $total = $countStmt->fetch()['total'] ?? 0;
        $countStmt->closeCursor();

        // Fetch records with SQL Server OFFSET FETCH (standard in SQL Server 2012+)
        $sql = "SELECT id_empleado, id_usuario, nombre_completo, correo, cargo_institucional, cedula, 
                       edad, genero, fecha_ingreso, meses_servicio, nombre_departamento, codigo_depto,
                       contrato_tipo_vigente, remuneracion, novedades_activas, activo
                FROM dbo.vw_FichaEmpleado 
                WHERE {$whereClause}
                ORDER BY nombre_completo
                OFFSET ? ROWS FETCH NEXT ? ROWS ONLY";
        
        // Agregar offset y limit como enteros al arreglo de parámetros
        $params[] = $offset;
        $params[] = $limit;

        $stmt = $this->query($sql, $params);
        $employees = $stmt->fetchAll();
        $stmt->closeCursor();

        return [
            'data' => $employees,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'pages' => ceil($total / $limit)
        ];
    }

    /**
     * Get complete details of an employee for the executive Ficha view.
     */
    public function getDetails(int $empId): array {
        // 1. Core Profile
        $sql = "SELECT * FROM dbo.vw_FichaEmpleado WHERE id_empleado = ?";
        $stmt = $this->query($sql, [$empId]);
        $profile = $stmt->fetch();
        $stmt->closeCursor();

        if (!$profile) return [];

        // 2. Full History of Contracts
        $contractSql = "SELECT id_contrato, tipo_contrato, fecha_inicio, fecha_fin, remuneracion, 
                               documento_path, observaciones, estado, fecha_registro
                        FROM dbo.TH_Contratos 
                        WHERE id_empleado = ? 
                        ORDER BY fecha_inicio DESC";
        $cStmt = $this->query($contractSql, [$empId]);
        $contracts = $cStmt->fetchAll();
        $cStmt->closeCursor();

        // 3. History of Salary Adendas
        $adendaSql = "SELECT A.id_adenda, A.id_contrato, A.descripcion, A.fecha_adenda, A.tipo_modificacion, 
                             A.valor_anterior, A.valor_nuevo, A.documento_path, U.nombre_completo as registrado_por, 
                             A.fecha_registro
                      FROM dbo.TH_Adendas A
                      INNER JOIN dbo.TH_Contratos C ON A.id_contrato = C.id_contrato
                      LEFT JOIN dbo.Usuarios U ON A.registrado_por = U.id_usuario
                      WHERE C.id_empleado = ?
                      ORDER BY A.fecha_adenda DESC";
        $aStmt = $this->query($adendaSql, [$empId]);
        $adendas = $aStmt->fetchAll();
        $aStmt->closeCursor();

        // 4. Medical Novedades history
        $medSql = "SELECT NM.id_novedad, NM.tipo_novedad, NM.fecha_inicio, NM.fecha_fin, NM.descripcion, 
                          NM.documento_path, U.nombre_completo as registrado_por, NM.fecha_registro
                   FROM dbo.TH_NovedadesMedicas NM
                   LEFT JOIN dbo.Usuarios U ON NM.registrado_por = U.id_usuario
                   WHERE NM.id_empleado = ?
                   ORDER BY NM.fecha_inicio DESC";
        $mStmt = $this->query($medSql, [$empId]);
        $medicals = $mStmt->fetchAll();
        $mStmt->closeCursor();

        return [
            'profile' => $profile,
            'contracts' => $contracts,
            'adendas' => $adendas,
            'medicals' => $medicals
        ];
    }

    /**
     * Register a new medical event for an employee.
     * Uses ThDatabase transaction support.
     */
    public function addMedicalEvent(array $data): bool {
        ThDatabase::beginTransaction();
        try {
            $sql = "INSERT INTO dbo.TH_NovedadesMedicas 
                        (id_empleado, tipo_novedad, fecha_inicio, fecha_fin, descripcion, registrado_por)
                    VALUES (?, ?, ?, ?, ?, ?)";
            
            ThDatabase::query($sql, [
                $data['id_empleado'],
                $data['tipo_novedad'],
                $data['fecha_inicio'],
                !empty($data['fecha_fin']) ? $data['fecha_fin'] : null,
                $data['descripcion'],
                $data['registrado_por']
            ]);

            ThDatabase::commit();
            return true;
        } catch (Exception $e) {
            ThDatabase::rollback();
            throw $e;
        }
    }

    /**
     * Get a list of all active departments for filters.
     */
    public function getDepartments(): array {
        $sql = "SELECT id_dep_modulo, nombre_departamento, codigo_depto 
                FROM dbo.Departamentos_Modulos 
                WHERE habilitado = 1 AND tipo_nodo = 'DEPARTAMENTO'
                ORDER BY nombre_departamento";
        $stmt = $this->query($sql);
        $data = $stmt->fetchAll();
        $stmt->closeCursor();
        return $data;
    }
}
