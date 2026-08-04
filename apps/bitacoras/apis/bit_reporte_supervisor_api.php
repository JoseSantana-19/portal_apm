<?php
require_once __DIR__ . '/../includes/bit_api_guard.php'; // Portal APM: sesion obligatoria

// apis/bit_reporte_supervisor_api.php - Reporte diario del supervisor (AJAX)

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../conexion/conexion.php';
require_once __DIR__ . '/../includes/bit_apm_fecha_iso.php';

function json_fail($msg, $code = 200)
{
    http_response_code($code);
    echo json_encode(['ok' => false, 'message' => $msg], JSON_UNESCAPED_UNICODE);
    exit;
}

function json_ok($data)
{
    echo json_encode(array_merge(['ok' => true], $data), JSON_UNESCAPED_UNICODE);
    exit;
}

/** @return string */
function fecha_sql($fecha)
{
    if ($fecha instanceof DateTimeInterface) {
        return $fecha->format('Y-m-d');
    }
    return (string) $fecha;
}

function asegurar_reporte($conn, $fechaStr, $usuario)
{
    $usuario = trim($usuario);
    if ($usuario === '') {
        json_fail('Indique el usuario que genera el reporte.');
    }

    $sqlSel = "SELECT id_reporte, fecha_reporte, usuario_genera, creado_en FROM dbo.bit_reporte_supervisor WHERE fecha_reporte = ?";
    $stmt = sqlsrv_query($conn, $sqlSel, [$fechaStr]);
    if ($stmt === false) {
        json_fail('Error al consultar el reporte.');
    }
    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    if ($row) {
        return [
            'id_reporte' => (int) $row['id_reporte'],
            'numero_reporte' => (int) $row['id_reporte'],
            'fecha_reporte' => fecha_sql($row['fecha_reporte']),
            'usuario_genera' => $row['usuario_genera'],
            'creado_en' => $row['creado_en'] instanceof DateTimeInterface ? $row['creado_en']->format('c') : (string) $row['creado_en'],
        ];
    }

    $sqlIns = "INSERT INTO dbo.bit_reporte_supervisor (fecha_reporte, usuario_genera) OUTPUT INSERTED.id_reporte VALUES (?, ?)";
    $stmtIns = sqlsrv_query($conn, $sqlIns, [$fechaStr, $usuario]);
    if ($stmtIns === false) {
        json_fail('Error al crear el reporte del día.');
    }
    $ins = sqlsrv_fetch_array($stmtIns, SQLSRV_FETCH_ASSOC);
    $id = $ins ? (int) $ins['id_reporte'] : 0;

    return [
        'id_reporte' => $id,
        'numero_reporte' => $id,
        'fecha_reporte' => $fechaStr,
        'usuario_genera' => $usuario,
        'creado_en' => gmdate('c'),
    ];
}

function resumen_visitas_dia($conn, $fechaStr)
{
    // Prioriza la tabla de totales para evitar escanear visitas en cada consulta.
    $sqlTotales = "SELECT total_visitas, total_activas, total_proveedores FROM dbo.bit_totales_visitas WHERE fecha = ?";
    $stmtTot = sqlsrv_query($conn, $sqlTotales, [$fechaStr]);
    if ($stmtTot && ($rt = sqlsrv_fetch_array($stmtTot, SQLSRV_FETCH_ASSOC))) {
        return [
            'total_visitas' => (int) $rt['total_visitas'],
            'visitas_activas' => (int) $rt['total_activas'],
            'proveedores' => (int) $rt['total_proveedores'],
        ];
    }

    // Fallback temporal por compatibilidad si aún no se aplicó la migración SQL de totales/trigger.
    $sqlTotal = "SELECT COUNT(*) AS n FROM dbo.bit_visitas WHERE CONVERT(date, fecha_visita) = CONVERT(date, ?)";
    $stmt = sqlsrv_query($conn, $sqlTotal, [$fechaStr]);
    $total = 0;
    if ($stmt && ($r = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC))) {
        $total = (int) $r['n'];
    }

    $sqlActivas = "SELECT COUNT(*) AS n FROM dbo.bit_visitas WHERE CONVERT(date, fecha_visita) = CONVERT(date, ?) AND hora_salida IS NULL";
    $stmt2 = sqlsrv_query($conn, $sqlActivas, [$fechaStr]);
    $activas = 0;
    if ($stmt2 && ($r2 = sqlsrv_fetch_array($stmt2, SQLSRV_FETCH_ASSOC))) {
        $activas = (int) $r2['n'];
    }

    $sqlProv = "SELECT COUNT(*) AS n FROM dbo.bit_visitas WHERE CONVERT(date, fecha_visita) = CONVERT(date, ?) AND tipo_visitante = N'Empresa'";
    $stmt3 = sqlsrv_query($conn, $sqlProv, [$fechaStr]);
    $prov = 0;
    if ($stmt3 && ($r3 = sqlsrv_fetch_array($stmt3, SQLSRV_FETCH_ASSOC))) {
        $prov = (int) $r3['n'];
    }

    return [
        'total_visitas' => $total,
        'visitas_activas' => $activas,
        'proveedores' => $prov,
    ];
}

function resumen_visitas_general($conn)
{
    $sqlTot = "SELECT SUM(total_visitas) AS total_visitas, SUM(total_activas) AS total_activas, SUM(total_proveedores) AS total_proveedores FROM dbo.bit_totales_visitas";
    $stmtTot = sqlsrv_query($conn, $sqlTot);
    if ($stmtTot && ($rt = sqlsrv_fetch_array($stmtTot, SQLSRV_FETCH_ASSOC))) {
        return [
            'total_visitas' => (int) ($rt['total_visitas'] ?? 0),
            'visitas_activas' => (int) ($rt['total_activas'] ?? 0),
            'proveedores' => (int) ($rt['total_proveedores'] ?? 0),
        ];
    }

    $sqlTotal = "SELECT COUNT(*) AS n FROM dbo.bit_visitas";
    $stmt = sqlsrv_query($conn, $sqlTotal);
    $total = 0;
    if ($stmt && ($r = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC))) {
        $total = (int) $r['n'];
    }

    $sqlActivas = "SELECT COUNT(*) AS n FROM dbo.bit_visitas WHERE hora_salida IS NULL";
    $stmt2 = sqlsrv_query($conn, $sqlActivas);
    $activas = 0;
    if ($stmt2 && ($r2 = sqlsrv_fetch_array($stmt2, SQLSRV_FETCH_ASSOC))) {
        $activas = (int) $r2['n'];
    }

    $sqlProv = "SELECT COUNT(*) AS n FROM dbo.bit_visitas WHERE tipo_visitante = N'Empresa'";
    $stmt3 = sqlsrv_query($conn, $sqlProv);
    $prov = 0;
    if ($stmt3 && ($r3 = sqlsrv_fetch_array($stmt3, SQLSRV_FETCH_ASSOC))) {
        $prov = (int) $r3['n'];
    }

    return [
        'total_visitas' => $total,
        'visitas_activas' => $activas,
        'proveedores' => $prov,
    ];
}

function chart_por_hora($conn, $fechaStr)
{
    $sql = "
        SELECT DATEPART(HOUR, hora_entrada) AS h, COUNT(*) AS n
        FROM dbo.bit_visitas
        WHERE CONVERT(date, fecha_visita) = CONVERT(date, ?)
        GROUP BY DATEPART(HOUR, hora_entrada)
        ORDER BY h
    ";
    $stmt = sqlsrv_query($conn, $sql, [$fechaStr]);
    $map = [];
    if ($stmt !== false) {
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $map[(int) $row['h']] = (int) $row['n'];
        }
    }
    $labels = [];
    $values = [];
    for ($h = 0; $h < 24; $h++) {
        $labels[] = sprintf('%02d:00', $h);
        $values[] = isset($map[$h]) ? $map[$h] : 0;
    }
    return ['labels' => $labels, 'values' => $values];
}

function chart_por_hora_general($conn)
{
    $sql = "
        SELECT DATEPART(HOUR, hora_entrada) AS h, COUNT(*) AS n
        FROM dbo.bit_visitas
        GROUP BY DATEPART(HOUR, hora_entrada)
        ORDER BY h
    ";
    $stmt = sqlsrv_query($conn, $sql);
    $map = [];
    if ($stmt !== false) {
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $map[(int) $row['h']] = (int) $row['n'];
        }
    }
    $labels = [];
    $values = [];
    for ($h = 0; $h < 24; $h++) {
        $labels[] = sprintf('%02d:00', $h);
        $values[] = isset($map[$h]) ? $map[$h] : 0;
    }
    return ['labels' => $labels, 'values' => $values];
}

function chart_por_tipo($conn, $fechaStr)
{
    $sql = "
        SELECT tipo_visitante, COUNT(*) AS n
        FROM dbo.bit_visitas
        WHERE CONVERT(date, fecha_visita) = CONVERT(date, ?)
        GROUP BY tipo_visitante
    ";
    $stmt = sqlsrv_query($conn, $sql, [$fechaStr]);
    $labels = [];
    $values = [];
    if ($stmt !== false) {
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $labels[] = $row['tipo_visitante'];
            $values[] = (int) $row['n'];
        }
    }
    return ['labels' => $labels, 'values' => $values];
}

function chart_por_tipo_general($conn)
{
    $sql = "
        SELECT tipo_visitante, COUNT(*) AS n
        FROM dbo.bit_visitas
        GROUP BY tipo_visitante
    ";
    $stmt = sqlsrv_query($conn, $sql);
    $labels = [];
    $values = [];
    if ($stmt !== false) {
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $labels[] = $row['tipo_visitante'];
            $values[] = (int) $row['n'];
        }
    }
    return ['labels' => $labels, 'values' => $values];
}

function calcular_contexto_datos($conn, $fecha)
{
    $resumen = resumen_visitas_dia($conn, $fecha);
    $chartHora = chart_por_hora($conn, $fecha);
    $chartTipo = chart_por_tipo($conn, $fecha);
    $contextoDatos = [
        'modo' => 'fecha',
        'mensaje' => '',
    ];

    if ((int) $resumen['total_visitas'] === 0) {
        $resumenGeneral = resumen_visitas_general($conn);
        if ((int) $resumenGeneral['total_visitas'] > 0) {
            $resumen = $resumenGeneral;
            $chartHora = chart_por_hora_general($conn);
            $chartTipo = chart_por_tipo_general($conn);
            $contextoDatos = [
                'modo' => 'general',
                'mensaje' => 'No hay visitas en la fecha seleccionada. Se muestran datos acumulados para que el panel no quede vacío.',
            ];
        }
    }

    return [
        'resumen' => $resumen,
        'chart_por_hora' => $chartHora,
        'chart_por_tipo' => $chartTipo,
        'contexto_datos' => $contextoDatos,
    ];
}

/** Normaliza hora a HH:MM:SS para SQL Server TIME; false si inválida */
function normalizar_hora_post($horaIn, $permitir_vacio_default_servidor = false)
{
    $horaIn = trim((string) $horaIn);
    if ($horaIn === '') {
        return $permitir_vacio_default_servidor ? null : false;
    }
    if (preg_match('/^([01]?\d|2[0-3]):[0-5]\d$/', $horaIn)) {
        return $horaIn . ':00';
    }
    if (preg_match('/^([01]?\d|2[0-3]):[0-5]\d:[0-5]\d$/', $horaIn)) {
        return $horaIn;
    }
    return false;
}

/** Detecta la PK real de dbo.bit_novedades (idnovedad o id_novedades). */
function columna_pk_novedades($conn)
{
    static $pk = null;
    if ($pk !== null) {
        return $pk;
    }

    $sql = "
        SELECT TOP 1 c.name
        FROM sys.columns c
        INNER JOIN sys.tables t ON c.object_id = t.object_id
        INNER JOIN sys.schemas s ON t.schema_id = s.schema_id
        WHERE s.name = N'dbo'
          AND t.name = N'bit_novedades'
          AND c.name IN (N'id_novedades', N'idnovedad', N'idnovedades')
        ORDER BY CASE c.name
            WHEN N'id_novedades' THEN 1
            WHEN N'idnovedad' THEN 2
            WHEN N'idnovedades' THEN 3
            ELSE 9
        END
    ";
    $stmt = sqlsrv_query($conn, $sql);
    if ($stmt === false) {
        json_fail('Error al detectar la estructura de dbo.bit_novedades.');
    }
    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    if (!$row || empty($row['name'])) {
        json_fail('No se detectó la columna PK de dbo.bit_novedades (idnovedad/id_novedades).');
    }
    $pk = (string) $row['name'];
    return $pk;
}

function listar_novedades($conn, $idReporte, $fechaStr)
{
    $pkCol = columna_pk_novedades($conn);
    $sql = "
        SELECT {$pkCol} AS idnovedad, id_reporte, fecha, hora, descripcion, estado
        FROM dbo.bit_novedades
        WHERE id_reporte = ? AND fecha = ?
        ORDER BY hora ASC, {$pkCol} ASC
    ";
    $stmt = sqlsrv_query($conn, $sql, [$idReporte, $fechaStr]);
    $out = [];
    if ($stmt === false) {
        return $out;
    }
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $hora = $row['hora'];
        $horaStr = $hora instanceof DateTimeInterface ? $hora->format('H:i') : (string) $hora;
        $fecha = $row['fecha'];
        $fechaStr = $fecha instanceof DateTimeInterface ? $fecha->format('Y-m-d') : (string) $fecha;
        $out[] = [
            'idnovedad' => (int) $row['idnovedad'],
            'id_reporte' => (int) $row['id_reporte'],
            'fecha' => $fechaStr,
            'hora' => $horaStr,
            'descripcion' => $row['descripcion'],
            'estado' => $row['estado'],
        ];
    }
    return $out;
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $action = isset($_GET['action']) ? trim($_GET['action']) : 'datos';
    if ($action !== 'datos') {
        json_fail('Acción no válida.');
    }

    $fechaRaw = isset($_GET['fecha']) ? trim($_GET['fecha']) : '';
    $fecha = apm_post_fecha_a_iso($fechaRaw);
    if ($fecha === null) {
        json_fail('Fecha inválida. Use YYYY-MM-DD o DD/MM/YYYY.');
    }

    $usuario = isset($_GET['usuario']) ? trim($_GET['usuario']) : '';
    $reporte = asegurar_reporte($conn, $fecha, $usuario);

    $ctxDatos = calcular_contexto_datos($conn, $fecha);

    json_ok([
        'reporte' => $reporte,
        'novedades' => listar_novedades($conn, $reporte['id_reporte'], $fecha),
        'resumen' => $ctxDatos['resumen'],
        'chart_por_hora' => $ctxDatos['chart_por_hora'],
        'chart_por_tipo' => $ctxDatos['chart_por_tipo'],
        'contexto_datos' => $ctxDatos['contexto_datos'],
    ]);
}

if ($method === 'POST') {
    $action = isset($_POST['action']) ? trim($_POST['action']) : '';

    if ($action === 'novedad_crear') {
        $fechaRaw = isset($_POST['fecha']) ? trim($_POST['fecha']) : '';
        $fecha = apm_post_fecha_a_iso($fechaRaw);
        $usuario = isset($_POST['usuario']) ? trim($_POST['usuario']) : '';
        $descripcion = isset($_POST['descripcion']) ? trim($_POST['descripcion']) : '';
        $estado = isset($_POST['estado']) ? trim($_POST['estado']) : 'Registrada';

        if ($fecha === null) {
            json_fail('Fecha inválida. Use YYYY-MM-DD o DD/MM/YYYY.');
        }
        if ($descripcion === '') {
            json_fail('La descripción de la novedad no puede estar vacía.');
        }
        if (strlen($descripcion) > 2000) {
            json_fail('La descripción es demasiado larga (máx. 2000 caracteres).');
        }
        if ($estado === '') {
            $estado = 'Registrada';
        }

        $reporte = asegurar_reporte($conn, $fecha, $usuario);

        $horaNorm = normalizar_hora_post(isset($_POST['hora']) ? $_POST['hora'] : '', true);
        if ($horaNorm === false) {
            json_fail('Hora inválida. Use formato HH:MM.');
        }
        $horaStr = $horaNorm === null ? date('H:i:s') : $horaNorm;

        $pkCol = columna_pk_novedades($conn);
        // Nota: dbo.bit_novedades tiene trigger (trg_novedades_turno). En SQL Server,
        // INSERT ... OUTPUT sin INTO puede fallar cuando hay triggers activos.
        // Usamos SCOPE_IDENTITY() para recuperar el ID insertado de forma compatible.
        $sqlIns = "INSERT INTO dbo.bit_novedades (id_reporte, fecha, hora, descripcion, estado)
                   VALUES (?, ?, CAST(? AS TIME(0)), ?, ?);
                   SELECT CAST(SCOPE_IDENTITY() AS INT) AS idnovedad;";
        $stmt = sqlsrv_query($conn, $sqlIns, [$reporte['id_reporte'], $fecha, $horaStr, $descripcion, $estado]);
        if ($stmt === false) {
            json_fail('Error al guardar la novedad.');
        }
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        if (!$row && sqlsrv_next_result($stmt) === true) {
            $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        }
        $id = $row ? (int) $row['idnovedad'] : 0;

        $ctxDatos = calcular_contexto_datos($conn, $fecha);
        json_ok([
            'message' => 'Novedad registrada correctamente.',
            'novedad' => [
                'idnovedad' => $id,
                'id_reporte' => $reporte['id_reporte'],
                'fecha' => $fecha,
                'hora' => substr($horaStr, 0, 5),
                'descripcion' => $descripcion,
                'estado' => $estado,
            ],
            'novedades' => listar_novedades($conn, $reporte['id_reporte'], $fecha),
            'resumen' => $ctxDatos['resumen'],
            'chart_por_hora' => $ctxDatos['chart_por_hora'],
            'chart_por_tipo' => $ctxDatos['chart_por_tipo'],
            'contexto_datos' => $ctxDatos['contexto_datos'],
        ]);
    }

    if ($action === 'novedad_actualizar') {
        $idnovedad = isset($_POST['idnovedad']) ? (int) $_POST['idnovedad'] : 0;
        $descripcion = isset($_POST['descripcion']) ? trim($_POST['descripcion']) : '';
        $estado = isset($_POST['estado']) ? trim($_POST['estado']) : '';
        $horaNorm = normalizar_hora_post(isset($_POST['hora']) ? $_POST['hora'] : '', false);

        if ($idnovedad <= 0) {
            json_fail('ID de novedad inválido.');
        }
        if ($descripcion === '') {
            json_fail('La descripción no puede estar vacía.');
        }
        if ($estado === '') {
            json_fail('Indique el estado.');
        }
        if ($horaNorm === false) {
            json_fail('Indique una hora válida (HH:MM). La fecha de la novedad no se modifica.');
        }

        $pkCol = columna_pk_novedades($conn);
        $sqlUp = "UPDATE dbo.bit_novedades SET descripcion = ?, estado = ?, hora = CAST(? AS TIME(0)) WHERE {$pkCol} = ?";
        $stmt = sqlsrv_query($conn, $sqlUp, [$descripcion, $estado, $horaNorm, $idnovedad]);
        if ($stmt === false) {
            json_fail('Error al actualizar la novedad.');
        }

        $sqlR = "SELECT id_reporte, fecha FROM dbo.bit_novedades WHERE {$pkCol} = ?";
        $stmtR = sqlsrv_query($conn, $sqlR, [$idnovedad]);
        $fr = $stmtR ? sqlsrv_fetch_array($stmtR, SQLSRV_FETCH_ASSOC) : null;
        if (!$fr) {
            json_ok(['message' => 'Novedad actualizada.', 'novedades' => []]);
        }
        $idRep = (int) $fr['id_reporte'];
        $fecha = $fr['fecha'] instanceof DateTimeInterface ? $fr['fecha']->format('Y-m-d') : (string) $fr['fecha'];

        $ctxDatos = calcular_contexto_datos($conn, $fecha);
        json_ok([
            'message' => 'Novedad actualizada correctamente.',
            'novedades' => listar_novedades($conn, $idRep, $fecha),
            'resumen' => $ctxDatos['resumen'],
            'chart_por_hora' => $ctxDatos['chart_por_hora'],
            'chart_por_tipo' => $ctxDatos['chart_por_tipo'],
            'contexto_datos' => $ctxDatos['contexto_datos'],
        ]);
    }

    if ($action === 'reporte_actualizar_supervisor') {
        $fechaRaw = isset($_POST['fecha']) ? trim($_POST['fecha']) : '';
        $fecha = apm_post_fecha_a_iso($fechaRaw);
        $usuario = isset($_POST['usuario']) ? trim($_POST['usuario']) : '';

        if ($fecha === null) {
            json_fail('Fecha inválida. Use YYYY-MM-DD o DD/MM/YYYY.');
        }
        if ($usuario === '') {
            json_fail('Indique el nombre o responsable del reporte.');
        }
        if (strlen($usuario) > 150) {
            json_fail('El texto es demasiado largo (máx. 150 caracteres).');
        }

        $sqlSel = "SELECT id_reporte, fecha_reporte, usuario_genera, creado_en FROM dbo.bit_reporte_supervisor WHERE fecha_reporte = ?";
        $stmt = sqlsrv_query($conn, $sqlSel, [$fecha]);
        if ($stmt === false) {
            json_fail('Error al consultar el reporte.');
        }
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        if (!$row) {
            json_fail('No hay reporte para esa fecha. Pulse «Actualizar» para crearlo primero.');
        }

        $sqlUp = "UPDATE dbo.bit_reporte_supervisor SET usuario_genera = ? WHERE fecha_reporte = ?";
        $stmtUp = sqlsrv_query($conn, $sqlUp, [$usuario, $fecha]);
        if ($stmtUp === false) {
            json_fail('Error al actualizar el responsable del reporte.');
        }

        $idRep = (int) $row['id_reporte'];
        json_ok([
            'message' => 'Responsable del reporte actualizado.',
            'reporte' => [
                'id_reporte' => $idRep,
                'numero_reporte' => $idRep,
                'fecha_reporte' => fecha_sql($row['fecha_reporte']),
                'usuario_genera' => $usuario,
                'creado_en' => $row['creado_en'] instanceof DateTimeInterface ? $row['creado_en']->format('c') : (string) $row['creado_en'],
            ],
        ]);
    }

    if ($action === 'novedad_eliminar') {
        $idnovedad = isset($_POST['idnovedad']) ? (int) $_POST['idnovedad'] : 0;
        if ($idnovedad <= 0) {
            json_fail('ID de novedad inválido.');
        }

        $pkCol = columna_pk_novedades($conn);
        $sqlR = "SELECT id_reporte, fecha FROM dbo.bit_novedades WHERE {$pkCol} = ?";
        $stmtR = sqlsrv_query($conn, $sqlR, [$idnovedad]);
        $fr = $stmtR ? sqlsrv_fetch_array($stmtR, SQLSRV_FETCH_ASSOC) : null;
        if (!$fr) {
            json_fail('La novedad no existe.');
        }
        $idRep = (int) $fr['id_reporte'];
        $fecha = $fr['fecha'] instanceof DateTimeInterface ? $fr['fecha']->format('Y-m-d') : (string) $fr['fecha'];

        $sqlDel = "DELETE FROM dbo.bit_novedades WHERE {$pkCol} = ?";
        $stmt = sqlsrv_query($conn, $sqlDel, [$idnovedad]);
        if ($stmt === false) {
            json_fail('Error al eliminar la novedad.');
        }

        $ctxDatos = calcular_contexto_datos($conn, $fecha);
        json_ok([
            'message' => 'Novedad eliminada.',
            'novedades' => listar_novedades($conn, $idRep, $fecha),
            'resumen' => $ctxDatos['resumen'],
            'chart_por_hora' => $ctxDatos['chart_por_hora'],
            'chart_por_tipo' => $ctxDatos['chart_por_tipo'],
            'contexto_datos' => $ctxDatos['contexto_datos'],
        ]);
    }

    json_fail('Acción no válida.');
}

json_fail('Método no permitido.', 405);
