<?php
class DashboardModel extends Model {

    public function getKpisEjecutivo(): array {
        $db  = self::db();
        $hoy = date('Y-m-d');

        // Fuentes reales de cada módulo integrado (cross-DB, misma instancia
        // SQL Server): Talento_Humano.th_empleados, inventario.inv_inventario,
        // PortuariaDemo.bit_visitas. Antes esto leía tablas TH_Empleados/
        // BIENES_Activos/BIT_Eventos locales a PORTAL_APM que eran copias
        // muertas de la prueba nativa inicial — ya no existen esos datos ahí.
        $stmt = $db->query("
            SELECT
                (SELECT COUNT(*) FROM Talento_Humano.dbo.th_empleados WHERE estado=1) AS total_empleados,
                (SELECT COUNT(*) FROM inventario.dbo.inv_inventario WHERE activo=1)                        AS total_bienes,
                (SELECT COUNT(*) FROM inventario.dbo.inv_inventario WHERE activo=1 AND estado_id=111)      AS bienes_activos,
                (SELECT COUNT(*) FROM inventario.dbo.inv_inventario WHERE activo=1 AND estado_id=112)      AS bienes_mantenimiento,
                (SELECT COUNT(*) FROM PortuariaDemo.dbo.bit_visitas WHERE MONTH(fecha_visita)=MONTH(GETDATE()) AND YEAR(fecha_visita)=YEAR(GETDATE())) AS eventos_mes,
                (SELECT COUNT(*) FROM PortuariaDemo.dbo.bit_visitas WHERE hora_salida IS NULL)              AS eventos_pendientes,
                (SELECT COUNT(*) FROM ACCESO_Registros WHERE tipo_acceso='Entrada' AND CAST(fecha_hora AS DATE)=?) AS ingresos_hoy
        ", [[$hoy, SQLSRV_PARAM_IN]]);

        $row = $db->fetch($stmt) ?? [];

        // 7-day bitácora series
        $semana = $this->getBitacoraSemana();

        return [
            'th' => [
                'total_empleados'     => (int)($row['total_empleados']    ?? 0),
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

        // NOTA: el filtro por departamento solo aplica a ACCESO_Registros
        // (vive en PORTAL_APM, con id_departamento real de CORE_Departamentos).
        // Los conteos de Talento Humano/Bienes/Bitácoras son cross-DB y esas
        // BDs no comparten el mismo esquema de departamento — filtrar por
        // depto ahí requeriría mapear unidad_id/zona_id vía TH_Unidad_Map,
        // pendiente si se necesita a futuro. Por ahora muestran el total global.
        $stmt = $db->query("
            SELECT
                (SELECT COUNT(*) FROM Talento_Humano.dbo.th_empleados WHERE estado=1)                 AS total_empleados,
                (SELECT COUNT(*) FROM inventario.dbo.inv_inventario WHERE activo=1 AND estado_id=111)  AS bienes_activos,
                (SELECT COUNT(*) FROM inventario.dbo.inv_inventario WHERE activo=1 AND estado_id=112)  AS bienes_mantenimiento,
                (SELECT COUNT(*) FROM PortuariaDemo.dbo.bit_visitas WHERE MONTH(fecha_visita)=MONTH(GETDATE()) AND YEAR(fecha_visita)=YEAR(GETDATE())) AS eventos_mes,
                (SELECT COUNT(*) FROM PortuariaDemo.dbo.bit_visitas WHERE hora_salida IS NULL)          AS eventos_pendientes,
                (SELECT COUNT(*) FROM ACCESO_Registros WHERE tipo_acceso='Entrada' AND CAST(fecha_hora AS DATE)=?
                    AND (? = 0 OR id_departamento = ? OR id_departamento IS NULL))                       AS ingresos_hoy
        ", [[$hoy, SQLSRV_PARAM_IN], [$idDepto, SQLSRV_PARAM_IN], [$idDepto, SQLSRV_PARAM_IN]]);

        $row    = $db->fetch($stmt) ?? [];
        $semana = $this->getBitacoraSemana();

        return [
            'th' => [
                'total_empleados'    => (int)($row['total_empleados']    ?? 0),
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
            SELECT CAST(fecha_visita AS DATE) AS dia, COUNT(*) AS total
            FROM PortuariaDemo.dbo.bit_visitas
            WHERE fecha_visita >= DATEADD(day, -6, CAST(GETDATE() AS DATE))
            GROUP BY CAST(fecha_visita AS DATE)
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
