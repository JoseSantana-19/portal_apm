<?php
/**
 * ThHubModel — Datos del Panel de Talento Humano (hub nativo del portal).
 * Fuente: BD Talento_Humano (tablas th_*).
 */
class ThHubModel extends ThHrBaseModel
{
    public function stats(): array
    {
        return [
            'empleados_activos' => (int)$this->fetchColumn("SELECT COUNT(*) FROM th_empleados WHERE estado = 1"),
            'unidades'          => (int)$this->fetchColumn("SELECT COUNT(*) FROM th_unidades_organizacionales WHERE activo = 1"),
            'puestos'           => (int)$this->fetchColumn("SELECT COUNT(*) FROM th_puestos WHERE activo = 1"),
            'acciones'          => (int)$this->fetchColumn("SELECT COUNT(*) FROM th_acciones_personal"),
        ];
    }

    /** Últimos empleados ingresados (para la tabla del hub). */
    public function ultimosEmpleados(int $limit = 6): array
    {
        return $this->fetchAll(
            "SELECT TOP (" . (int)$limit . ")
                    e.empleado_id, e.nombres, e.apellidos, e.identificacion,
                    e.fecha_ingreso,
                    ISNULL(u.nombre_unidad, N'—') AS unidad,
                    ISNULL(p.nombre_puesto, N'—') AS puesto
             FROM th_empleados e
             LEFT JOIN th_unidades_organizacionales u ON u.unidad_id = e.unidad_id
             LEFT JOIN th_puestos p ON p.puesto_id = e.puesto_id
             WHERE e.estado = 1
             ORDER BY e.fecha_creacion DESC, e.empleado_id DESC"
        );
    }

    /** Empleados activos por unidad organizacional (top N, para gráfico). */
    public function empleadosPorUnidad(int $top = 6): array
    {
        return $this->fetchAll(
            "SELECT TOP (" . (int)$top . ")
                    ISNULL(u.nombre_unidad, N'Sin unidad') AS label,
                    COUNT(*) AS value
             FROM th_empleados e
             LEFT JOIN th_unidades_organizacionales u ON u.unidad_id = e.unidad_id
             WHERE e.estado = 1
             GROUP BY u.nombre_unidad
             ORDER BY COUNT(*) DESC"
        );
    }

    /** Acciones de personal por mes (últimos N meses, para gráfico). */
    public function accionesPorMes(int $meses = 6): array
    {
        $rows = $this->fetchAll(
            "SELECT FORMAT(a.fecha_elaboracion, 'yyyy-MM') AS ym, COUNT(*) AS value
             FROM th_acciones_personal a
             WHERE a.fecha_elaboracion >= DATEADD(MONTH, -" . ((int)$meses - 1) . ", DATEADD(DAY, 1 - DAY(GETDATE()), CAST(GETDATE() AS date)))
             GROUP BY FORMAT(a.fecha_elaboracion, 'yyyy-MM')"
        );
        $porMes = [];
        foreach ($rows as $r) { $porMes[(string)$r['ym']] = (int)$r['value']; }

        $out = [];
        $abrev = [1=>'Ene',2=>'Feb',3=>'Mar',4=>'Abr',5=>'May',6=>'Jun',7=>'Jul',8=>'Ago',9=>'Sep',10=>'Oct',11=>'Nov',12=>'Dic'];
        for ($i = $meses - 1; $i >= 0; $i--) {
            $ts = strtotime("first day of -{$i} month");
            $out[] = [
                'label' => $abrev[(int)date('n', $ts)],
                'value' => $porMes[date('Y-m', $ts)] ?? 0,
            ];
        }
        return $out;
    }

    /** Últimas acciones de personal registradas. */
    public function ultimasAcciones(int $limit = 5): array
    {
        return $this->fetchAll(
            "SELECT TOP (" . (int)$limit . ")
                    a.numero_accion, a.tipo_accion, a.fecha_elaboracion, a.estado_documento,
                    e.nombres + ' ' + e.apellidos AS empleado
             FROM th_acciones_personal a
             LEFT JOIN th_empleados e ON e.empleado_id = a.empleado_id
             ORDER BY a.accion_id DESC"
        );
    }
}
