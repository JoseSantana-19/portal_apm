<?php
class DashboardModel extends Model {

    public function getKpisEjecutivo(): array {
        $db  = self::db();
        $hoy = date('Y-m-d');

        $stmt = $db->query("
            SELECT
                (SELECT COUNT(*) FROM TH_Empleados  WHERE estado=1)                                          AS total_empleados,
                (SELECT COUNT(*) FROM TH_Contratos  WHERE estado_contrato='Vigente')                                  AS contratos_vigentes,
                (SELECT COUNT(*) FROM TH_Contratos  WHERE estado_contrato='Vigente' AND fecha_fin IS NOT NULL AND DATEDIFF(day,GETDATE(),fecha_fin) BETWEEN 0 AND 30)  AS contratos_30d,
                (SELECT COUNT(*) FROM TH_Contratos  WHERE estado_contrato='Vigente' AND fecha_fin IS NOT NULL AND DATEDIFF(day,GETDATE(),fecha_fin) BETWEEN 0 AND 60)  AS contratos_60d,
                (SELECT COUNT(*) FROM TH_Contratos  WHERE estado_contrato='Vigente' AND fecha_fin IS NOT NULL AND DATEDIFF(day,GETDATE(),fecha_fin) BETWEEN 0 AND 90)  AS contratos_90d,
                (SELECT COUNT(*) FROM BIENES_Activos)                                                        AS total_bienes,
                (SELECT COUNT(*) FROM BIENES_Activos WHERE estado_bien='Activo')                                  AS bienes_activos,
                (SELECT COUNT(*) FROM BIENES_Activos WHERE estado_bien='En Reparacion')                           AS bienes_mantenimiento,
                (SELECT COUNT(*) FROM BIT_Eventos   WHERE MONTH(fecha_evento)=MONTH(GETDATE()) AND YEAR(fecha_evento)=YEAR(GETDATE())) AS eventos_mes,
                (SELECT COUNT(*) FROM BIT_Eventos   WHERE estado_evento NOT IN ('Cerrado','CERRADO'))                AS eventos_pendientes,
                (SELECT COUNT(*) FROM ACCESO_Registros WHERE tipo_acceso='Entrada' AND CAST(fecha_hora AS DATE)=?) AS ingresos_hoy
        ", [[$hoy, SQLSRV_PARAM_IN]]);

        $row = $db->fetch($stmt) ?? [];

        // 7-day bitácora series
        $semana = $this->getBitacoraSemana();

        return [
            'th' => [
                'total_empleados'     => (int)($row['total_empleados']    ?? 0),
                'contratos_vigentes'  => (int)($row['contratos_vigentes'] ?? 0),
                'contratos_30d'       => (int)($row['contratos_30d']      ?? 0),
                'contratos_60d'       => (int)($row['contratos_60d']      ?? 0),
                'contratos_90d'       => (int)($row['contratos_90d']      ?? 0),
            ],
            'bienes' => [
                'total_bienes'          => (int)($row['total_bienes']          ?? 0),
                'bienes_activos'        => (int)($row['bienes_activos']        ?? 0),
                'bienes_mantenimiento'  => (int)($row['bienes_mantenimiento']  ?? 0),
            ],
            'bitacora' => [
                'eventos_mes'       => (int)($row['eventos_mes']       ?? 0),
                'eventos_pendientes'=> (int)($row['eventos_pendientes'] ?? 0),
            ],
            'acceso' => [
                'ingresos_hoy'       => (int)($row['ingresos_hoy'] ?? 0),
                'visitantes_activos' => (int)($row['ingresos_hoy'] ?? 0),
                'empleados_hoy'      => (int)($row['ingresos_hoy'] ?? 0),
                'visitantes_hoy'     => 0,
                'proveedores_hoy'    => 0,
            ],
            'bitacora_semana' => $semana,
        ];
    }

    public function getKpisOperativo(): array {
        $idUsuario  = (int)($_SESSION['user_id'] ?? 0);
        $idDepto    = (int)($_SESSION['id_departamento'] ?? 0);
        $hoy        = date('Y-m-d');
        $db         = self::db();

        $deptoFilter = $idDepto > 0
            ? "AND (id_departamento = $idDepto OR id_departamento IS NULL)"
            : '';

        $stmt = $db->query("
            SELECT
                (SELECT COUNT(*) FROM TH_Empleados WHERE estado=1 $deptoFilter)                              AS total_empleados,
                (SELECT COUNT(*) FROM TH_Contratos WHERE estado_contrato='Vigente')                                   AS contratos_vigentes,
                (SELECT COUNT(*) FROM TH_Contratos WHERE estado_contrato='Vigente' AND fecha_fin IS NOT NULL AND DATEDIFF(day,GETDATE(),fecha_fin) BETWEEN 0 AND 30) AS contratos_30d,
                (SELECT COUNT(*) FROM BIENES_Activos WHERE estado_bien='Activo' $deptoFilter)                     AS bienes_activos,
                (SELECT COUNT(*) FROM BIENES_Activos WHERE estado_bien='En Reparacion' $deptoFilter)              AS bienes_mantenimiento,
                (SELECT COUNT(*) FROM BIT_Eventos WHERE MONTH(fecha_evento)=MONTH(GETDATE()) AND YEAR(fecha_evento)=YEAR(GETDATE()) $deptoFilter) AS eventos_mes,
                (SELECT COUNT(*) FROM BIT_Eventos WHERE estado_evento NOT IN ('Cerrado','CERRADO') $deptoFilter)    AS eventos_pendientes,
                (SELECT COUNT(*) FROM ACCESO_Registros WHERE tipo_acceso='Entrada' AND CAST(fecha_hora AS DATE)=?) AS ingresos_hoy
        ", [[$hoy, SQLSRV_PARAM_IN]]);

        $row    = $db->fetch($stmt) ?? [];
        $semana = $this->getBitacoraSemana();

        return [
            'th' => [
                'total_empleados'    => (int)($row['total_empleados']    ?? 0),
                'contratos_vigentes' => (int)($row['contratos_vigentes'] ?? 0),
                'contratos_30d'      => (int)($row['contratos_30d']      ?? 0),
            ],
            'bienes' => [
                'bienes_activos'       => (int)($row['bienes_activos']       ?? 0),
                'bienes_mantenimiento' => (int)($row['bienes_mantenimiento'] ?? 0),
            ],
            'bitacora' => [
                'eventos_mes'        => (int)($row['eventos_mes']        ?? 0),
                'eventos_pendientes' => (int)($row['eventos_pendientes'] ?? 0),
            ],
            'acceso' => [
                'ingresos_hoy'       => (int)($row['ingresos_hoy'] ?? 0),
                'empleados_hoy'      => (int)($row['ingresos_hoy'] ?? 0),
                'visitantes_hoy'     => 0,
                'proveedores_hoy'    => 0,
            ],
            'bitacora_semana' => $semana,
        ];
    }

    private function getBitacoraSemana(): array {
        $db   = self::db();
        $stmt = $db->query("
            SELECT CAST(fecha_evento AS DATE) AS dia, COUNT(*) AS total
            FROM BIT_Eventos
            WHERE fecha_evento >= DATEADD(day, -6, CAST(GETDATE() AS DATE))
            GROUP BY CAST(fecha_evento AS DATE)
            ORDER BY dia ASC
        ");
        $rows = $db->fetchAll($stmt);

        // Build Mon–Sun map for last 7 days
        $map = [];
        for ($i = 6; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime("-{$i} days"));
            $map[$d] = 0;
        }
        foreach ($rows as $r) {
            $d = is_string($r['dia']) ? $r['dia'] : (($r['dia'] instanceof DateTime) ? $r['dia']->format('Y-m-d') : '');
            if (isset($map[$d])) $map[$d] = (int)$r['total'];
        }
        return array_values($map);
    }

    public function getAuditRecent(int $limit = 10): array {
        $stmt = $this->query(
            'SELECT TOP(?) modulo, operacion,
                    nombre_usuario AS nombre_completo,
                    ip_address, resultado,
                    fecha_registro AS fecha_creacion
             FROM vw_AuditoriaGlobal
             ORDER BY fecha_registro DESC',
            [[$limit, SQLSRV_PARAM_IN]]
        );
        return $this->fetchAll($stmt);
    }

    public function getAlertasPendientes(int $limit = 8): array {
        $idUsuario = (int)($_SESSION['user_id'] ?? 0);
        $stmt = $this->query(
            "SELECT TOP(?) n.id_notif, n.titulo, n.mensaje, n.tipo, n.prioridad,
                    n.url_accion, n.fecha_creacion
             FROM CORE_Notificaciones n
             WHERE n.leida = 0
               AND (n.id_usuario IS NULL OR n.id_usuario = ?)
             ORDER BY n.prioridad DESC, n.fecha_creacion DESC",
            [[$limit, SQLSRV_PARAM_IN], [$idUsuario, SQLSRV_PARAM_IN]]
        );
        return $this->fetchAll($stmt);
    }

    public function getActividadReciente(int $limit = 15): array {
        $stmt = $this->query(
            'SELECT TOP(?) modulo, operacion,
                    detalle AS descripcion,
                    nombre_usuario AS nombre_completo,
                    ip_address, resultado,
                    fecha_registro AS fecha_creacion
             FROM vw_AuditoriaGlobal
             ORDER BY fecha_registro DESC',
            [[$limit, SQLSRV_PARAM_IN]]
        );
        return $this->fetchAll($stmt);
    }
}
