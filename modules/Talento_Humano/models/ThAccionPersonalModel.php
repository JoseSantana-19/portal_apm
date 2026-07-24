<?php
/**
 * ThAccionPersonalModel — Documento legal "Acción de Personal" (LOSEP Art. 21).
 * BD Talento_Humano, tabla th_acciones_personal. sqlsrv nativo, `?` posicional.
 *
 * Reconciliado con el ESQUEMA REAL:
 *   - La tabla NO tiene columna usuario_crea → se omite del INSERT.
 *   - El join usa e.identificacion (la columna real; el código origen usaba e.cedula).
 *   - estado_documento se guarda como 'Aprobado'.
 */
class ThAccionPersonalModel extends ThHrBaseModel
{
    /** Siguiente correlativo del año: APM-TH-YYYY-NNN. */
    public function generarSiguienteSecuencial(): string
    {
        $anio    = date('Y');
        $prefijo = "APM-TH-{$anio}-";

        $row = $this->fetch(
            "SELECT TOP 1 numero_accion
             FROM th_acciones_personal
             WHERE numero_accion LIKE ?
             ORDER BY accion_id DESC",
            [$prefijo . '%']
        );

        if ($row && !empty($row['numero_accion'])) {
            $partes   = explode('-', $row['numero_accion']);
            $siguiente = (int) end($partes) + 1;
        } else {
            $siguiente = 1;
        }
        return $prefijo . str_pad((string)$siguiente, 3, '0', STR_PAD_LEFT);
    }

    /** Inserta una Acción de Personal. */
    public function registrarAccion(array $d): bool
    {
        $sql = "INSERT INTO th_acciones_personal
                (numero_accion, fecha_elaboracion, empleado_id, tipo_accion,
                 fecha_rige_desde, fecha_rige_hasta, explicacion_legal,
                 actual_unidad_id, actual_puesto_id, actual_lugar_trabajo,
                 actual_remuneracion, actual_partida_presupuestaria,
                 propuesta_unidad_id, propuesta_puesto_id, propuesta_lugar_trabajo,
                 propuesta_remuneracion, propuesta_partida_presupuestaria,
                 estado_documento)
                VALUES (?, GETDATE(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Aprobado')";

        $nz = fn($v) => (isset($v) && $v !== '') ? $v : null;
        $params = [
            trim((string)($d['numero_accion'] ?? '')),
            (int)($d['empleado_id'] ?? 0),
            trim((string)($d['tipo_accion'] ?? '')),
            $nz($d['fecha_rige_desde'] ?? date('Y-m-d')) ?? date('Y-m-d'),
            $nz($d['fecha_rige_hasta'] ?? null),
            trim((string)($d['explicacion_legal'] ?? '')),
            $nz($d['actual_unidad_id'] ?? null) !== null ? (int)$d['actual_unidad_id'] : null,
            $nz($d['actual_puesto_id'] ?? null) !== null ? (int)$d['actual_puesto_id'] : null,
            $nz($d['actual_lugar_trabajo'] ?? 'Manta - Instalaciones APM'),
            (float)($d['actual_remuneracion'] ?? 0),
            $nz($d['actual_partida_presupuestaria'] ?? null),
            $nz($d['propuesta_unidad_id'] ?? null) !== null ? (int)$d['propuesta_unidad_id'] : null,
            $nz($d['propuesta_puesto_id'] ?? null) !== null ? (int)$d['propuesta_puesto_id'] : null,
            $nz($d['propuesta_lugar_trabajo'] ?? 'Manta - Instalaciones APM'),
            (float)($d['propuesta_remuneracion'] ?? 0),
            $nz($d['propuesta_partida_presupuestaria'] ?? null),
        ];
        return $this->exec($sql, $params) > 0;
    }

    /** Documento por ID. */
    public function obtenerPorId(int $id): ?array
    {
        return $this->fetch(
            "SELECT * FROM th_acciones_personal WHERE accion_id = ?",
            [[$id, SQLSRV_PARAM_IN]]
        );
    }

    /** Documento cruzado con empleado + catálogos (para el PDF oficial). */
    public function obtenerAccionCruzada(int $accionId): ?array
    {
        return $this->fetch(
            "SELECT
                a.*,
                e.identificacion       AS identificacion,
                e.nombres              AS nombres,
                e.apellidos            AS apellidos,
                u_act.nombre_unidad    AS actual_area,
                p_act.nombre_puesto    AS actual_cargo,
                u_prop.nombre_unidad   AS propuesta_area,
                p_prop.nombre_puesto   AS propuesta_cargo
             FROM th_acciones_personal a
             INNER JOIN th_empleados e                       ON a.empleado_id        = e.empleado_id
             LEFT JOIN th_unidades_organizacionales u_act    ON a.actual_unidad_id   = u_act.unidad_id
             LEFT JOIN th_puestos p_act                      ON a.actual_puesto_id   = p_act.puesto_id
             LEFT JOIN th_unidades_organizacionales u_prop   ON a.propuesta_unidad_id= u_prop.unidad_id
             LEFT JOIN th_puestos p_prop                     ON a.propuesta_puesto_id= p_prop.puesto_id
             WHERE a.accion_id = ?",
            [[$accionId, SQLSRV_PARAM_IN]]
        );
    }

    /** Listado de acciones (para historial/listado). */
    public function listar(): array
    {
        return $this->fetchAll(
            "SELECT a.accion_id, a.numero_accion, a.fecha_elaboracion, a.tipo_accion,
                    a.estado_documento, a.fecha_rige_desde, a.fecha_rige_hasta,
                    e.identificacion AS cedula, e.apellidos + ' ' + e.nombres AS funcionario
             FROM th_acciones_personal a
             INNER JOIN th_empleados e ON a.empleado_id = e.empleado_id
             ORDER BY a.accion_id DESC"
        );
    }
}
