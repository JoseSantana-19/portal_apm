<?php

/**
 * Datos para dashboard jefe (KPIs, gráficos semanales, feed de movimientos).
 * Usado por bit_dashboard_jefe.php y apis/bit_get_dashboard_live.php.
 */

/** @return array{visitas_activas:int,rondas_hoy:int,alertas_criticas_24h:int} */
function apm_dashboard_jefe_kpis($conn): array
{
    $visitasActivas = 0;
    $st = sqlsrv_query($conn, 'SELECT COUNT(1) AS c FROM dbo.bit_visitas WHERE hora_salida IS NULL');
    if ($st && ($r = sqlsrv_fetch_array($st, SQLSRV_FETCH_ASSOC))) {
        $visitasActivas = (int) $r['c'];
    }

    $rondasHoy = 0;
    $st2 = sqlsrv_query(
        $conn,
        'SELECT COUNT(1) AS c FROM dbo.bit_rondas_detalles d '
        . 'INNER JOIN dbo.bit_rondas_cabecera c ON c.id_ronda = d.id_ronda '
        . 'WHERE c.fecha = CAST(GETDATE() AS DATE)'
    );
    if ($st2 && ($r2 = sqlsrv_fetch_array($st2, SQLSRV_FETCH_ASSOC))) {
        $rondasHoy = (int) $r2['c'];
    }

    $alertas = 0;
    $st3 = sqlsrv_query(
        $conn,
        'SELECT COUNT(1) AS c FROM dbo.bit_rondas_detalles d '
        . 'WHERE d.id_alerta = 3 AND d.hora_registro >= DATEADD(HOUR, -24, GETDATE())'
    );
    if ($st3 && ($r3 = sqlsrv_fetch_array($st3, SQLSRV_FETCH_ASSOC))) {
        $alertas += (int) $r3['c'];
    }

    $sqlCritVisitas = 'SELECT COUNT(1) AS c FROM dbo.bit_visitas v '
        . 'WHERE v.id_nivel_incidente = 3 '
        . 'AND DATEADD(SECOND, DATEDIFF(SECOND, 0, v.hora_entrada), CAST(v.fecha_visita AS DATETIME)) >= DATEADD(HOUR, -24, GETDATE())';
    $st4 = @sqlsrv_query($conn, $sqlCritVisitas);
    if ($st4 && ($r4 = sqlsrv_fetch_array($st4, SQLSRV_FETCH_ASSOC))) {
        $alertas += (int) $r4['c'];
    }

    return [
        'visitas_activas' => $visitasActivas,
        'rondas_hoy' => $rondasHoy,
        'alertas_criticas_24h' => $alertas,
    ];
}

/**
 * Últimos 7 días (hoy inclusive): etiquetas y series alineadas.
 *
 * @return array{labels:list<string>,visitas:list<int>,rondas:list<int>}
 */
function apm_dashboard_jefe_series_semana($conn): array
{
    $end = new DateTime('today');
    $start = (clone $end)->modify('-6 days');
    $labels = [];
    $visitas = [];
    $rondas = [];

    $desde = $start->format('Y-m-d');
    $hasta = $end->format('Y-m-d');

    // Serie de visitas por fecha (totales diarias, informativo de 7 días).
    $mapV = [];
    $st = sqlsrv_query(
        $conn,
        'SELECT CONVERT(date, v.fecha_visita) AS fecha, COUNT(1) AS total '
        . 'FROM dbo.bit_visitas v '
        . 'WHERE v.fecha_visita >= ? AND v.fecha_visita <= ? '
        . 'GROUP BY CONVERT(date, v.fecha_visita) '
        . 'ORDER BY CONVERT(date, v.fecha_visita)',
        [$desde, $hasta]
    );
    if ($st) {
        while ($row = sqlsrv_fetch_array($st, SQLSRV_FETCH_ASSOC)) {
            $fd = $row['fecha'];
            $key = $fd instanceof DateTimeInterface ? $fd->format('Y-m-d') : (string) $fd;
            $mapV[$key] = (int) $row['total'];
        }
    }

    // Serie de rondas por fecha operativa (consulta directa desde rondas_detalles/cabecera).
    $mapR = [];
    $st2 = sqlsrv_query(
        $conn,
        'SELECT c.fecha AS fecha, COUNT(1) AS total '
        . 'FROM dbo.bit_rondas_detalles d '
        . 'INNER JOIN dbo.bit_rondas_cabecera c ON c.id_ronda = d.id_ronda '
        . 'WHERE c.fecha >= ? AND c.fecha <= ? '
        . 'GROUP BY c.fecha '
        . 'ORDER BY c.fecha',
        [$desde, $hasta]
    );
    if ($st2) {
        while ($row = sqlsrv_fetch_array($st2, SQLSRV_FETCH_ASSOC)) {
            $fd = $row['fecha'];
            $key = $fd instanceof DateTimeInterface ? $fd->format('Y-m-d') : (string) $fd;
            $mapR[$key] = (int) $row['total'];
        }
    }

    for ($d = clone $start; $d <= $end; $d->modify('+1 day')) {
        $key = $d->format('Y-m-d');
        $labels[] = $d->format('d/m');
        $visitas[] = $mapV[$key] ?? 0;
        $rondas[] = $mapR[$key] ?? 0;
    }

    return ['labels' => $labels, 'visitas' => $visitas, 'rondas' => $rondas];
}

/**
 * Extrae referencia embebida en descripción de movimiento ([REF:Vid] visita, [REF:Did] detalle ronda).
 *
 * @return array{tipo:string,id:int}|null
 */
function apm_movimiento_extraer_ref(string $descripcion): ?array
{
    if (preg_match('/\[REF:V(\d+)\]/u', $descripcion, $m)) {
        return ['tipo' => 'visita', 'id' => (int) $m[1]];
    }
    if (preg_match('/\[REF:D(\d+)\]/u', $descripcion, $m)) {
        return ['tipo' => 'ronda', 'id' => (int) $m[1]];
    }
    return null;
}

/**
 * @return list<array<string,mixed>>
 */
function apm_dashboard_jefe_movimientos($conn, int $limit = 10): array
{
    $limit = max(1, min(50, $limit));
    $sql = 'SELECT TOP (' . (int) $limit . ') '
        . 'm.id_movimiento, m.tipo_evento, m.descripcion, m.turno, m.fecha_hora, u.nombres AS usuario_nombre '
        . 'FROM dbo.bit_movimientos m '
        . 'LEFT JOIN dbo.bit_usuarios_apm u ON u.id_usuario = m.id_usuario '
        . 'ORDER BY m.id_movimiento DESC';
    $st = sqlsrv_query($conn, $sql);
    $rows = [];
    if (!$st) {
        return $rows;
    }
    while ($row = sqlsrv_fetch_array($st, SQLSRV_FETCH_ASSOC)) {
        $idM = isset($row['id_movimiento']) ? (string) $row['id_movimiento'] : '';
        $desc = (string) ($row['descripcion'] ?? '');
        $tipoEv = strtoupper(trim((string) ($row['tipo_evento'] ?? '')));
        $ref = apm_movimiento_extraer_ref($desc);
        $linkTipo = null;
        $linkId = null;
        if ($ref !== null) {
            $linkTipo = $ref['tipo'];
            $linkId = $ref['id'];
        } elseif ($tipoEv === 'INGRESO' || $tipoEv === 'SALIDA') {
            $linkTipo = 'visita';
            $linkId = null;
        } elseif ($tipoEv === 'RONDA') {
            $linkTipo = 'ronda';
            $linkId = null;
        }

        $fh = $row['fecha_hora'] ?? null;
        $fhIso = null;
        if ($fh instanceof DateTimeInterface) {
            $fhIso = $fh->format('c');
        } elseif ($fh !== null) {
            $fhIso = (string) $fh;
        }

        $nomU = $row['usuario_nombre'] ?? null;
        $nomU = ($nomU !== null && trim((string) $nomU) !== '') ? trim((string) $nomU) : null;

        $rows[] = [
            'id_movimiento' => $idM,
            'tipo_evento' => $row['tipo_evento'] ?? '',
            'descripcion' => $desc,
            'turno' => $row['turno'] ?? '',
            'fecha_hora' => $fhIso,
            'usuario_nombre' => $nomU,
            'link_tipo' => $linkTipo,
            'link_id' => $linkId,
        ];
    }

    return $rows;
}
