<?php
/**
 * Modelo de Dashboard.
 * Extrae queries de bit_index.php y de includes/bit_dashboard_jefe_data.php
 */
class PortDashboardModel extends PortBaseModel
{
    /**
     * Totales del día (antes en bit_index.php).
     */
    public function totalesHoy(): array
    {
        $hoy = date('Y-m-d');
        $row = $this->fetchOne(
            "SELECT total_visitas, total_activas FROM dbo.bit_totales_visitas WHERE fecha = ?",
            [$hoy]
        );

        if ($row) {
            return [
                'total_hoy' => (int)$row['total_visitas'],
                'activas'   => (int)$row['total_activas'],
            ];
        }

        // Fallback
        $totalHoy = $this->count("SELECT COUNT(*) AS c FROM dbo.bit_visitas WHERE fecha_visita = CONVERT(date, GETDATE())");
        $activas = $this->count("SELECT COUNT(*) AS c FROM dbo.bit_visitas WHERE hora_salida IS NULL");

        return ['total_hoy' => $totalHoy, 'activas' => $activas];
    }

    /**
     * Últimas N visitas (antes en bit_index.php).
     */
    public function ultimasVisitas(int $limit = 5): array
    {
        return $this->fetchAll(
            "SELECT TOP ({$limit})
                v.id_visita,
                p.nombres + ' ' + p.apellidos AS nombre_visitante,
                p.nidentificacion,
                v.tipo_visitante,
                e.empresa AS empresa,
                e.ruc AS empresa_ruc,
                v.fecha_visita,
                v.hora_entrada,
                v.hora_salida
            FROM dbo.bit_visitas v
            INNER JOIN dbo.bit_personas p ON v.id_persona = p.id_persona
            LEFT JOIN dbo.bit_empresas e ON v.id_empresa = e.id_empresa
            ORDER BY v.fecha_visita DESC, v.hora_entrada DESC"
        );
    }

    /**
     * KPIs para dashboard jefe (antes en includes/bit_dashboard_jefe_data.php).
     */
    public function kpisJefe(): array
    {
        $visitasActivas = $this->count("SELECT COUNT(1) AS c FROM dbo.bit_visitas WHERE hora_salida IS NULL");

        $rondasHoy = $this->count(
            "SELECT COUNT(1) AS c FROM dbo.bit_rondas_detalles d
             INNER JOIN dbo.bit_rondas_cabecera c ON c.id_ronda = d.id_ronda
             WHERE c.fecha = CAST(GETDATE() AS DATE)"
        );

        $alertas = $this->count(
            "SELECT COUNT(1) AS c FROM dbo.bit_rondas_detalles d
             WHERE d.id_alerta = 3 AND d.hora_registro >= DATEADD(HOUR, -24, GETDATE())"
        );

        return [
            'visitas_activas'     => $visitasActivas,
            'rondas_hoy'          => $rondasHoy,
            'alertas_criticas_24h' => $alertas,
        ];
    }

    /* ================================================================
       Métodos para las vistas NATIVAS del shell del portal
       (hub /portuaria, /portuaria/visitas-resumen, /portuaria/actividad)
       ================================================================ */

    /** KPIs del hub del portal. */
    public function statsHub(): array
    {
        $tot = $this->totalesHoy();
        $kpi = $this->kpisJefe();

        return [
            'visitas_hoy'      => $tot['total_hoy'],
            'visitas_activas'  => $kpi['visitas_activas'],
            'rondas_hoy'       => $kpi['rondas_hoy'],
            'alertas_24h'      => $kpi['alertas_criticas_24h'],
            'camaras_activas'  => $this->count("SELECT COUNT(1) AS c FROM dbo.bit_inv_camaras WHERE estado = 1"),
            'cctv_hoy'         => $this->count("SELECT COUNT(1) AS c FROM dbo.bit_camaras WHERE fecha = CAST(GETDATE() AS DATE) AND estado = 1"),
            'personas'         => $this->count("SELECT COUNT(1) AS c FROM dbo.bit_personas WHERE estado = 1"),
            'empresas'         => $this->count("SELECT COUNT(1) AS c FROM dbo.bit_empresas WHERE estado = 1"),
        ];
    }

    /** Visitas por día de los últimos N días (para gráfico del hub). */
    public function visitasPorDia(int $dias = 7): array
    {
        $rows = $this->fetchAll(
            "SELECT CONVERT(varchar(10), v.fecha_visita, 120) AS d, COUNT(*) AS value
             FROM dbo.bit_visitas v
             WHERE v.fecha_visita >= DATEADD(DAY, -" . ((int)$dias - 1) . ", CONVERT(date, GETDATE()))
             GROUP BY v.fecha_visita"
        );
        $porDia = [];
        foreach ($rows as $r) { $porDia[(string)$r['d']] = (int)$r['value']; }

        $out = [];
        $abrevDia = ['Mon'=>'Lun','Tue'=>'Mar','Wed'=>'Mié','Thu'=>'Jue','Fri'=>'Vie','Sat'=>'Sáb','Sun'=>'Dom'];
        for ($i = $dias - 1; $i >= 0; $i--) {
            $ts = strtotime("-{$i} day");
            $out[] = [
                'label' => $abrevDia[date('D', $ts)] . ' ' . date('d', $ts),
                'value' => $porDia[date('Y-m-d', $ts)] ?? 0,
            ];
        }
        return $out;
    }

    /** Visitas por destino de los últimos 30 días (top N, para gráfico). */
    public function visitasPorDestino(int $top = 6): array
    {
        return $this->fetchAll(
            "SELECT TOP (" . (int)$top . ")
                    d.nombre AS label, COUNT(*) AS value
             FROM dbo.bit_visitas v
             INNER JOIN dbo.bit_destinos d ON d.id_destino = v.id_destino
             WHERE v.fecha_visita >= DATEADD(DAY, -30, CONVERT(date, GETDATE()))
             GROUP BY d.nombre
             ORDER BY COUNT(*) DESC"
        );
    }

    /** Visitas con filtros para la vista rápida del portal. */
    public function visitasFiltradas(?string $fecha, string $q = '', int $limit = 100): array
    {
        $where  = [];
        $params = [];

        if ($fecha !== null && $fecha !== '') {
            $where[]  = 'v.fecha_visita = ?';
            $params[] = $fecha;
        }
        if ($q !== '') {
            $where[]  = "(p.nombres + ' ' + p.apellidos LIKE ? OR p.nidentificacion LIKE ? OR e.empresa LIKE ? OR d.nombre LIKE ?)";
            $like     = '%' . $q . '%';
            array_push($params, $like, $like, $like, $like);
        }

        $sql = "SELECT TOP (" . (int)$limit . ")
                    v.id_visita,
                    p.nombres + ' ' + p.apellidos AS visitante,
                    p.nidentificacion,
                    v.tipo_visitante,
                    e.empresa,
                    d.nombre AS destino,
                    m.descripcion AS motivo,
                    v.fecha_visita, v.hora_entrada, v.hora_salida
                FROM dbo.bit_visitas v
                INNER JOIN dbo.bit_personas p ON p.id_persona = v.id_persona
                LEFT JOIN dbo.bit_empresas e ON e.id_empresa = v.id_empresa
                INNER JOIN dbo.bit_destinos d ON d.id_destino = v.id_destino
                INNER JOIN dbo.bit_motivos m ON m.id_motivo = v.id_motivo"
            . (empty($where) ? '' : ' WHERE ' . implode(' AND ', $where))
            . " ORDER BY v.fecha_visita DESC, v.hora_entrada DESC";

        return $this->fetchAll($sql, $params);
    }

    /** Rondas (detalles) de una fecha, con turno y nivel de alerta. */
    public function rondasDelDia(?string $fecha = null): array
    {
        $fecha = ($fecha !== null && $fecha !== '') ? $fecha : date('Y-m-d');

        return $this->fetchAll(
            "SELECT d.id_detalle, d.hora_registro, d.actividad,
                    c.turno, c.fecha,
                    ISNULL(na.descripcion, N'—') AS alerta,
                    ISNULL(na.color_hex, N'#6c757d') AS alerta_color,
                    ISNULL(u.nombres, N'—') AS guardia
             FROM dbo.bit_rondas_detalles d
             INNER JOIN dbo.bit_rondas_cabecera c ON c.id_ronda = d.id_ronda
             LEFT JOIN dbo.bit_niveles_alerta na ON na.id_alerta = d.id_alerta
             LEFT JOIN dbo.bit_usuarios_apm u ON u.id_usuario = c.id_usuario
             WHERE c.fecha = ?
             ORDER BY d.hora_registro DESC",
            [$fecha]
        );
    }

    /** Últimos registros de la bitácora CCTV. */
    public function actividadCamaras(int $limit = 15): array
    {
        return $this->fetchAll(
            "SELECT TOP (" . (int)$limit . ")
                    bc.codigo_bitacora, bc.fecha, bc.hora_registro, bc.turno,
                    bc.novedad, bc.ubicacion, bc.consolista,
                    ISNULL(ic.codigo, bc.camara_ip) AS camara,
                    ISNULL(na.descripcion, N'—') AS alerta,
                    ISNULL(na.color_hex, N'#6c757d') AS alerta_color
             FROM dbo.bit_camaras bc
             LEFT JOIN dbo.bit_inv_camaras ic ON ic.id_camara = bc.id_camara
             LEFT JOIN dbo.bit_niveles_alerta na ON na.id_alerta = bc.nivel_alerta
             WHERE bc.estado = 1
             ORDER BY bc.fecha DESC, bc.hora_registro DESC",
        );
    }
}
