<?php
/**
 * ThEmpleadoModel — Expediente de personal (BD Talento_Humano, tabla th_empleados).
 *
 * Reescrito contra el ESQUEMA REAL de la BD (verificado por introspección):
 *   - sqlsrv nativo con parámetros posicionales `?` (NO PDO / named params).
 *   - Maestro de empleados = th_empleados; deduplicación por `identificacion` (UNIQUE).
 *   - Lecturas de catálogo desde view_th_iddatosempledo, th_puestos, th_unidades_*.
 *   - El RBU vigente se lee de PORTAL_APM.CORE_Config (fuente única de configuración).
 *   - "Eliminar" = baja lógica (estado=0), siguiendo la convención de portal_apm.
 */
class ThEmpleadoModel extends ThHrBaseModel
{
    /* ── LECTURA ──────────────────────────────────────────────────────────── */

    /** Directorio completo (vista normalizada). */
    public function listarDirectorio(): array
    {
        return $this->fetchAll(
            "SELECT * FROM view_th_iddatosempledo ORDER BY apellidos ASC, nombres ASC"
        );
    }

    /** RBU vigente desde PORTAL_APM.CORE_Config (no desde la BD de TH). */
    public function obtenerRbuVigente(): string
    {
        try {
            $db  = Database::getInstance();
            $row = $db->fetch($db->query(
                "SELECT valor FROM CORE_Config WHERE modulo = ? AND clave = ?",
                ['TH', 'RBU_VIGENTE']
            ));
            return ($row && $row['valor'] !== null && $row['valor'] !== '') ? (string)$row['valor'] : '460.00';
        } catch (\Throwable $e) {
            return '460.00';
        }
    }

    /** Expediente crudo por ID (para el formulario de edición). */
    public function obtenerPorId(int $id): ?array
    {
        return $this->fetch(
            "SELECT * FROM th_empleados WHERE empleado_id = ?",
            [[$id, SQLSRV_PARAM_IN]]
        );
    }

    /** Expediente cruzado por cédula (autocompletar Acción de Personal). */
    public function obtenerPorCedula(string $cedula): ?array
    {
        return $this->fetch($this->sqlDetalle() . " WHERE e.identificacion = ?", [$cedula]);
    }

    /** Expediente cruzado completo por ID (ficha PDF + situación actual de la Acción). */
    public function obtenerDetalleCompleto(int $id): ?array
    {
        return $this->fetch($this->sqlDetalle() . " WHERE e.empleado_id = ?", [[$id, SQLSRV_PARAM_IN]]);
    }

    /** SELECT base con cargo/área/remuneración resueltos. */
    private function sqlDetalle(): string
    {
        return "SELECT
                    e.empleado_id            AS id,
                    e.empleado_id,
                    e.identificacion         AS cedula,
                    e.identificacion,
                    e.tipo_identificacion,
                    e.apellidos,
                    e.nombres,
                    e.fecha_nacimiento,
                    e.sexo,
                    e.estado_civil,
                    e.nacionalidad,
                    e.unidad_id,
                    e.puesto_id,
                    e.fecha_ingreso,
                    e.sueldo_rmu,
                    e.correo_institucional,
                    e.correo_personal,
                    e.telefono_movil,
                    e.ciudad_residencia,
                    e.direccion_domiciliaria,
                    e.cuenta_bancaria,
                    e.codigo_iess,
                    e.estado,
                    e.cargas_familiares,
                    e.tipo_cuenta_bancaria,
                    e.numero_cuenta_bancaria,
                    e.institucion_bancaria,
                    p.nombre_puesto          AS cargo,
                    u.nombre_unidad          AS direccion_area,
                    u.codigo_uorg            AS codigo_uorg
                FROM th_empleados e
                LEFT JOIN th_puestos p                   ON e.puesto_id = p.puesto_id
                LEFT JOIN th_unidades_organizacionales u ON e.unidad_id = u.unidad_id";
    }

    /** Unidades organizacionales activas (para los <select>). */
    public function listarAreas(): array
    {
        return $this->fetchAll(
            "SELECT unidad_id, nombre_unidad, tipo_proceso, unidad_padre_id
             FROM th_unidades_organizacionales
             WHERE activo = 1 ORDER BY nombre_unidad"
        );
    }

    /** Puestos activos (para los <select>), con su remuneración de referencia. */
    public function listarCargos(): array
    {
        return $this->fetchAll(
            "SELECT puesto_id, codigo_puesto, nombre_puesto, remuneracion_unificada
             FROM th_puestos WHERE activo = 1 ORDER BY nombre_puesto"
        );
    }

    /** Reporte jerárquico (historial laboral con fusiones). */
    public function obtenerReporteFiltrado(?string $tipoCargo = null, ?int $empleadoId = null): array
    {
        $sql = "SELECT * FROM vw_th_reporte_historial_jerarquico WHERE 1 = 1";
        $params = [];
        if ($tipoCargo !== null && $tipoCargo !== '') {
            $sql .= " AND nombre_puesto LIKE ?";
            $params[] = $this->likeParam($tipoCargo);
        }
        if ($empleadoId !== null) {
            $sql .= " AND empleado_id = ?";
            $params[] = [$empleadoId, SQLSRV_PARAM_IN];
        }
        $sql .= " ORDER BY tipo_proceso DESC, direccion_actual_unificada, funcionario, fecha_desde ASC";
        return $this->fetchAll($sql, $params);
    }

    /** ¿Existe ya un empleado con esa cédula? (dedup). */
    public function existeCedula(string $cedula, ?int $exceptId = null): bool
    {
        $sql = "SELECT COUNT(*) FROM th_empleados WHERE identificacion = ?";
        $params = [$cedula];
        if ($exceptId !== null) { $sql .= " AND empleado_id <> ?"; $params[] = [$exceptId, SQLSRV_PARAM_IN]; }
        return (int)$this->fetchColumn($sql, $params) > 0;
    }

    /* ── ESCRITURA ────────────────────────────────────────────────────────── */

    /** INSERT directo cubriendo las 24 columnas de datos reales. */
    public function insertar(array $d): bool
    {
        $sql = "INSERT INTO th_empleados
                (tipo_identificacion, identificacion, nombres, apellidos, fecha_nacimiento,
                 sexo, estado_civil, nacionalidad, unidad_id, puesto_id, fecha_ingreso,
                 sueldo_rmu, correo_institucional, correo_personal, telefono_movil,
                 ciudad_residencia, direccion_domiciliaria, cuenta_bancaria, codigo_iess,
                 estado, cargas_familiares, tipo_cuenta_bancaria, numero_cuenta_bancaria,
                 institucion_bancaria, fecha_creacion)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?, GETDATE())";
        return $this->exec($sql, $this->mapParams($d)) > 0;
    }

    /** UPDATE directo por ID. */
    public function modificar(int $id, array $d): bool
    {
        $sql = "UPDATE th_empleados SET
                    tipo_identificacion = ?, identificacion = ?, nombres = ?, apellidos = ?,
                    fecha_nacimiento = ?, sexo = ?, estado_civil = ?, nacionalidad = ?,
                    unidad_id = ?, puesto_id = ?, fecha_ingreso = ?, sueldo_rmu = ?,
                    correo_institucional = ?, correo_personal = ?, telefono_movil = ?,
                    ciudad_residencia = ?, direccion_domiciliaria = ?, cuenta_bancaria = ?,
                    codigo_iess = ?, estado = ?, cargas_familiares = ?, tipo_cuenta_bancaria = ?,
                    numero_cuenta_bancaria = ?, institucion_bancaria = ?
                WHERE empleado_id = ?";
        $params = $this->mapParams($d);
        $params[] = [$id, SQLSRV_PARAM_IN];
        return $this->exec($sql, $params) > 0;
    }

    /** Baja lógica (estado = 0). No borra físicamente (convención portal_apm). */
    public function eliminar(int $id): bool
    {
        return $this->exec(
            "UPDATE th_empleados SET estado = 0 WHERE empleado_id = ?",
            [[$id, SQLSRV_PARAM_IN]]
        ) > 0;
    }

    /* ── HELPERS ──────────────────────────────────────────────────────────── */

    /** Ordena el payload del formulario según el orden de columnas del INSERT/UPDATE. */
    private function mapParams(array $d): array
    {
        $nz = fn($v) => (isset($v) && $v !== '') ? $v : null;   // '' → NULL
        return [
            $nz($d['tipo_identificacion'] ?? 'CEDULA') ?? 'CEDULA',
            trim((string)($d['identificacion'] ?? '')),
            mb_strtoupper(trim((string)($d['nombres'] ?? ''))),
            mb_strtoupper(trim((string)($d['apellidos'] ?? ''))),
            $nz($d['fecha_nacimiento'] ?? null),
            substr((string)($d['sexo'] ?? 'M'), 0, 1),
            $nz($d['estado_civil'] ?? null),
            $nz($d['nacionalidad'] ?? null),
            $nz($d['unidad_id'] ?? null) !== null ? (int)$d['unidad_id'] : null,
            $nz($d['puesto_id'] ?? null) !== null ? (int)$d['puesto_id'] : null,
            $nz($d['fecha_ingreso'] ?? null),
            (float)($d['sueldo_rmu'] ?? 0),
            trim((string)($d['correo_institucional'] ?? '')),
            $nz($d['correo_personal'] ?? null),
            trim((string)($d['telefono_movil'] ?? '')),
            trim((string)($d['ciudad_residencia'] ?? '')),
            trim((string)($d['direccion_domiciliaria'] ?? '')),
            $nz($d['cuenta_bancaria'] ?? null),
            $nz($d['codigo_iess'] ?? null),
            (int)($d['estado'] ?? 1),
            (int)($d['cargas_familiares'] ?? 0),
            $nz($d['tipo_cuenta_bancaria'] ?? null),
            $nz($d['numero_cuenta_bancaria'] ?? null),
            $nz($d['institucion_bancaria'] ?? null),
        ];
    }
}
