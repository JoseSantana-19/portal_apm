<?php
require_once __DIR__ . '/../includes/bit_api_guard.php'; // Portal APM: sesion obligatoria

// apis/bit_personas_api.php - Búsqueda en BD local y externa; registro en local
// Columnas: nidentificacion, tidentif, nombres, apellidos

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../conexion/conexion.php';
require_once __DIR__ . '/../conexion/conexion_externa.php';
require_once __DIR__ . '/../includes/bit_validaciones_ecuador.php';

$method = $_SERVER['REQUEST_METHOD'];

/**
 * Clave estable para validar tipo de identificación: sin tildes + mayúsculas ASCII.
 * Evita fallos con strtoupper() sobre UTF-8 (p. ej. "Cédula" → "CéDULA", no coincide con "CÉDULA").
 */
function personas_clave_tipo_identificacion(string $raw): string
{
    $s = trim($raw);
    if ($s === '') {
        return '';
    }
    $sinTildes = strtr($s, [
        'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u',
        'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U', 'Ü' => 'U',
        'à' => 'a', 'è' => 'e', 'ì' => 'i', 'ò' => 'o', 'ù' => 'u',
        'À' => 'A', 'È' => 'E', 'Ì' => 'I', 'Ò' => 'O', 'Ù' => 'U',
        'ñ' => 'n', 'Ñ' => 'N',
    ]);

    return strtoupper($sinTildes);
}

/** Violación de unicidad en SQL Server (índice / restricción única). */
function personas_sqlsrv_es_duplicado($errors): bool
{
    if (!is_array($errors)) {
        return false;
    }
    foreach ($errors as $e) {
        $code = isset($e['code']) ? (int) $e['code'] : 0;
        if ($code === 2601 || $code === 2627) {
            return true;
        }
    }

    return false;
}

/** Fila persona para JSON (nombres alineados con BD). */
function persona_json_fila(array $row)
{
    return [
        'id_persona'       => isset($row['id_persona']) ? (int) $row['id_persona'] : 0,
        'nidentificacion' => $row['nidentificacion'],
        'nombres'         => $row['nombres'],
        'apellidos'       => $row['apellidos'],
        'tidentif'        => isset($row['tidentif']) ? $row['tidentif'] : 'Cédula',
    ];
}

/**
 * Resuelve nombres de columnas en la PERSONAS externa (APM) para mapear al esquema local.
 *
 * @return array{id:string,tidentif:string}
 */
function personas_ext_columnas($connExterna): array
{
    $cols = [];
    $sqlCols = "SELECT LOWER(COLUMN_NAME) AS c FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = 'dbo' AND TABLE_NAME = 'bit_personas'";
    $stCols = sqlsrv_query($connExterna, $sqlCols);
    if ($stCols !== false) {
        while ($r = sqlsrv_fetch_array($stCols, SQLSRV_FETCH_ASSOC)) {
            if (!empty($r['c'])) {
                $cols[(string) $r['c']] = true;
            }
        }
    }

    $idCol = isset($cols['idpersona']) ? 'idpersona' : 'id_persona';

    // Algunas bases APM usan variaciones de esta columna.
    if (isset($cols['tidentif'])) {
        $tidCol = 'tidentif';
    } elseif (isset($cols['tidenti'])) {
        $tidCol = 'tidenti';
    } elseif (isset($cols['tipoidentif'])) {
        $tidCol = 'tipoidentif';
    } else {
        $tidCol = '';
    }

    return ['id' => $idCol, 'tidentif' => $tidCol];
}

/**
 * Busca persona en base externa por identificación exacta.
 * Retorna fila ya mapeada a claves locales o null.
 */
function personas_buscar_externa_ident($connExterna, string $ident): ?array
{
    if ($connExterna === null) {
        return null;
    }

    $map = personas_ext_columnas($connExterna);
    $idExpr = $map['id'] . ' AS id_persona';
    if ($map['tidentif'] !== '') {
        $srcTid = "UPPER(LTRIM(RTRIM(CONVERT(NVARCHAR(20), " . $map['tidentif'] . "))))";
        $tidExpr =
            "CASE "
            . "WHEN " . $srcTid . " IN (N'1', N'C', N'CEDULA') THEN N'Cédula' "
            . "WHEN " . $srcTid . " IN (N'2', N'R', N'RUC') THEN N'RUC' "
            . "WHEN " . $srcTid . " IN (N'3', N'P', N'PASAPORTE') THEN N'Pasaporte' "
            . "ELSE N'Cédula' "
            . "END AS tidentif";
    } else {
        $tidExpr = "N'Cédula' AS tidentif";
    }
    $sqlExt = "SELECT $idExpr, nidentificacion, $tidExpr, nombres, apellidos FROM dbo.bit_personas WHERE ISNULL(estado,1) = 1 AND nidentificacion = ?";

    $stmtExt = sqlsrv_query($connExterna, $sqlExt, [$ident], ['Scrollable' => SQLSRV_CURSOR_KEYSET]);
    if ($stmtExt === false) {
        return null;
    }

    if (!sqlsrv_has_rows($stmtExt)) {
        return null;
    }

    $rowExt = sqlsrv_fetch_array($stmtExt, SQLSRV_FETCH_ASSOC);
    if (!$rowExt) {
        return null;
    }

    return $rowExt;
}

/**
 * Búsqueda externa por prefijo de identificación (TOP 10).
 *
 * @return array<int,array<string,mixed>>
 */
function personas_buscar_externa_prefijo($connExterna, string $prefijo, int $limite = 10): array
{
    if ($connExterna === null) {
        return [];
    }

    $map = personas_ext_columnas($connExterna);
    $idExpr = $map['id'] . ' AS id_persona';
    if ($map['tidentif'] !== '') {
        $srcTid = "UPPER(LTRIM(RTRIM(CONVERT(NVARCHAR(20), " . $map['tidentif'] . "))))";
        $tidExpr =
            "CASE "
            . "WHEN " . $srcTid . " IN (N'1', N'C', N'CEDULA') THEN N'Cédula' "
            . "WHEN " . $srcTid . " IN (N'2', N'R', N'RUC') THEN N'RUC' "
            . "WHEN " . $srcTid . " IN (N'3', N'P', N'PASAPORTE') THEN N'Pasaporte' "
            . "ELSE N'Cédula' "
            . "END AS tidentif";
    } else {
        $tidExpr = "N'Cédula' AS tidentif";
    }

    $top = max(1, min(50, $limite));
    $sqlExt = "SELECT TOP ($top) $idExpr, nidentificacion, $tidExpr, nombres, apellidos "
        . "FROM dbo.bit_personas "
        . "WHERE ISNULL(estado,1) = 1 AND nidentificacion LIKE ? "
        . "ORDER BY nidentificacion";
    $param = $prefijo . '%';
    $stmtExt = sqlsrv_query($connExterna, $sqlExt, [$param], ['Scrollable' => SQLSRV_CURSOR_KEYSET]);
    if ($stmtExt === false) {
        return [];
    }
    if (!sqlsrv_has_rows($stmtExt)) {
        return [];
    }

    $out = [];
    while ($row = sqlsrv_fetch_array($stmtExt, SQLSRV_FETCH_ASSOC)) {
        $out[] = $row;
    }

    return $out;
}

/**
 * Búsqueda externa por nombres/apellidos (TOP N).
 *
 * @return array<int,array<string,mixed>>
 */
function personas_buscar_externa_nombre($connExterna, string $term, int $limite = 10): array
{
    if ($connExterna === null) {
        return [];
    }

    $map = personas_ext_columnas($connExterna);
    $idExpr = $map['id'] . ' AS id_persona';
    if ($map['tidentif'] !== '') {
        $srcTid = "UPPER(LTRIM(RTRIM(CONVERT(NVARCHAR(20), " . $map['tidentif'] . "))))";
        $tidExpr =
            "CASE "
            . "WHEN " . $srcTid . " IN (N'1', N'C', N'CEDULA') THEN N'Cédula' "
            . "WHEN " . $srcTid . " IN (N'2', N'R', N'RUC') THEN N'RUC' "
            . "WHEN " . $srcTid . " IN (N'3', N'P', N'PASAPORTE') THEN N'Pasaporte' "
            . "ELSE N'Cédula' "
            . "END AS tidentif";
    } else {
        $tidExpr = "N'Cédula' AS tidentif";
    }

    $top = max(1, min(50, $limite));
    $sqlExt = "SELECT TOP ($top) $idExpr, nidentificacion, nombres, apellidos, $tidExpr, "
        . "(nombres + N' ' + apellidos) AS nombre_completo "
        . "FROM dbo.bit_personas "
        . "WHERE (nombres LIKE ? OR apellidos LIKE ?) AND ISNULL(estado,1) = 1 "
        . "ORDER BY nombres, apellidos";
    $param = '%' . str_replace(['%', '_'], ['[%]', '[_]'], $term) . '%';
    $stmtExt = sqlsrv_query($connExterna, $sqlExt, [$param, $param], ['Scrollable' => SQLSRV_CURSOR_KEYSET]);
    if ($stmtExt === false) {
        return [];
    }
    if (!sqlsrv_has_rows($stmtExt)) {
        return [];
    }

    $out = [];
    while ($row = sqlsrv_fetch_array($stmtExt, SQLSRV_FETCH_ASSOC)) {
        $out[] = $row;
    }

    return $out;
}

if ($method === 'GET') {
    $q = isset($_GET['cedula']) ? trim($_GET['cedula']) : '';
    if ($q === '' && isset($_GET['nidentificacion'])) {
        $q = trim($_GET['nidentificacion']);
    }
    if ($q === '') {
        echo json_encode(['ok' => false, 'message' => 'Indique número de identificación o nombre para buscar']);
        exit;
    }

    $esIdentExacta = (bool) preg_match('/^\d{10}$/', $q);
    $soloDigitos = (bool) preg_match('/^\d+$/', $q);

    if ($esIdentExacta) {
        $sql = "SELECT id_persona, nidentificacion, tidentif, nombres, apellidos FROM dbo.bit_personas WHERE estado = 1 AND nidentificacion = ?";
        $params = [$q];
        $stmt = sqlsrv_query($conn, $sql, $params, ['Scrollable' => SQLSRV_CURSOR_KEYSET]);
        if ($stmt === false) {
            echo json_encode(['ok' => false, 'message' => 'Error al buscar persona']);
            exit;
        }
        if (sqlsrv_has_rows($stmt)) {
            $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            echo json_encode(['ok' => true, 'found' => true, 'data' => persona_json_fila($row)]);
            exit;
        }
        // Solo si NO existe en local, consultar externa.
        if ($connExterna !== null) {
            $rowExt = personas_buscar_externa_ident($connExterna, $q);
            if ($rowExt) {
                echo json_encode(['ok' => true, 'found' => true, 'data' => persona_json_fila($rowExt)]);
                exit;
            }
        }
        echo json_encode(['ok' => true, 'found' => false, 'nidentificacion' => $q, 'message' => 'No se encontraron registros con esta identificación.']);
        exit;
    }

    if ($soloDigitos) {
        $termId = $q . '%';
        $sqlLike = "SELECT TOP 20 id_persona, nidentificacion, tidentif, nombres, apellidos FROM dbo.bit_personas WHERE estado = 1 AND nidentificacion LIKE ? ORDER BY nidentificacion";
        $params = [$termId];

        $results = [];
        $nidsVistos = [];
        $stmt = sqlsrv_query($conn, $sqlLike, $params);
        if ($stmt !== false) {
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $fila = persona_json_fila($row);
                $nid = (string) ($fila['nidentificacion'] ?? '');
                if ($nid !== '' && !isset($nidsVistos[$nid])) {
                    $nidsVistos[$nid] = true;
                    $results[] = $fila;
                }
            }
        }

        // Búsqueda fluida externa: desde 2 dígitos, refinando a medida que escribe.
        if ($connExterna !== null && strlen($q) >= 2) {
            $externas = personas_buscar_externa_prefijo($connExterna, $q, 10);
            foreach ($externas as $rowExt) {
                $fila = persona_json_fila($rowExt);
                $nid = (string) ($fila['nidentificacion'] ?? '');
                if ($nid !== '' && !isset($nidsVistos[$nid])) {
                    $nidsVistos[$nid] = true;
                    $results[] = $fila;
                }
            }
        }

        if (count($results) > 0) {
            echo json_encode(['ok' => true, 'found' => true, 'results' => $results]);
        } else {
            echo json_encode(['ok' => true, 'found' => false, 'nidentificacion' => $q, 'message' => 'No se encontraron registros con este prefijo.']);
        }
        exit;
    }

    $term = '%' . str_replace(['%', '_'], ['[%]', '[_]'], $q) . '%';
    $sqlNombre = "SELECT TOP 20 id_persona, nidentificacion, tidentif, nombres, apellidos FROM dbo.bit_personas WHERE estado = 1 AND (nombres LIKE ? OR apellidos LIKE ?) ORDER BY nombres, apellidos";
    $params = [$term, $term];
    $results = [];
    $nidsVistos = [];
    $stmt = sqlsrv_query($conn, $sqlNombre, $params);
    if ($stmt !== false) {
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $fila = persona_json_fila($row);
            $nid = (string) ($fila['nidentificacion'] ?? '');
            if ($nid !== '' && !isset($nidsVistos[$nid])) {
                $nidsVistos[$nid] = true;
                $results[] = $fila;
            }
        }
    }
    if ($connExterna !== null) {
        $externas = personas_buscar_externa_nombre($connExterna, $q, 10);
        foreach ($externas as $rowExt) {
            $fila = persona_json_fila($rowExt);
            $nid = (string) ($fila['nidentificacion'] ?? '');
            if ($nid !== '' && !isset($nidsVistos[$nid])) {
                $nidsVistos[$nid] = true;
                $results[] = $fila;
            }
        }
    }
    if (count($results) > 0) {
        echo json_encode(['ok' => true, 'found' => true, 'results' => $results]);
    } else {
        echo json_encode(['ok' => true, 'found' => false, 'message' => 'No se encontraron personas con ese nombre o apellido.']);
    }
    exit;
}

if ($method === 'POST') {
    $nidentificacion = isset($_POST['nidentificacion']) ? trim($_POST['nidentificacion']) : '';
    if ($nidentificacion === '' && isset($_POST['cedula'])) {
        $nidentificacion = trim($_POST['cedula']);
    }
    $nombres   = isset($_POST['nombres']) ? trim($_POST['nombres']) : '';
    if ($nombres === '' && isset($_POST['nombre'])) {
        $nombres = trim($_POST['nombre']);
    }
    $apellidos = isset($_POST['apellidos']) ? trim($_POST['apellidos']) : '';
    if ($apellidos === '' && isset($_POST['apellido'])) {
        $apellidos = trim($_POST['apellido']);
    }
    $tidentifRaw = isset($_POST['tidentif']) ? trim((string) $_POST['tidentif']) : '';
    if ($tidentifRaw === '' && preg_match('/^\d{10}$/', $nidentificacion)) {
        $tidentifRaw = 'CEDULA';
    }
    $mapTipos = [
        'C' => 'Cédula',
        'CEDULA' => 'Cédula',
        'P' => 'Pasaporte',
        'PASAPORTE' => 'Pasaporte',
        'R' => 'RUC',
        'RUC' => 'RUC',
    ];
    $tipoKey = personas_clave_tipo_identificacion($tidentifRaw);
    $tidentif = isset($mapTipos[$tipoKey]) ? $mapTipos[$tipoKey] : '';
    if ($tidentif === '') {
        echo json_encode(['ok' => false, 'message' => 'Tipo de identificación obligatorio (Cédula, Pasaporte o RUC)']);
        exit;
    }

    if ($nidentificacion === '' || $nombres === '' || $apellidos === '') {
        echo json_encode(['ok' => false, 'message' => 'Todos los campos son obligatorios']);
        exit;
    }

    $nidSolo = preg_replace('/\D/', '', $nidentificacion);
    if ($tidentif === 'Cédula') {
        if (strlen($nidSolo) !== 10 || !ec_validar_cedula_ecuador($nidSolo)) {
            echo json_encode(['ok' => false, 'message' => apm_mensaje_identificacion_invalida()]);
            exit;
        }
        $nidentificacion = $nidSolo;
    } elseif ($tidentif === 'RUC') {
        if (strlen($nidSolo) !== 13 || !ec_validar_ruc_ecuador($nidSolo)) {
            echo json_encode(['ok' => false, 'message' => apm_mensaje_identificacion_invalida()]);
            exit;
        }
        $nidentificacion = $nidSolo;
    }

    if ($nidentificacion !== '9999999999') {
        $sqlExist = 'SELECT estado FROM dbo.bit_personas WHERE nidentificacion = ?';
        $stmtExist = sqlsrv_query($conn, $sqlExist, [$nidentificacion]);
        if ($stmtExist === false) {
            echo json_encode(['ok' => false, 'message' => 'Error al validar la identificación.']);
            exit;
        }
        $rowEx = sqlsrv_fetch_array($stmtExist, SQLSRV_FETCH_ASSOC);
        if ($rowEx) {
            $estado = isset($rowEx['estado']) ? (int) $rowEx['estado'] : 0;
            if ($estado === 1) {
                echo json_encode([
                    'ok' => false,
                    'message' => 'Esta persona ya se encuentra registrada y activa',
                ]);
                exit;
            }
            if ($estado === 0) {
                $sqlReactivar = 'UPDATE dbo.bit_personas SET estado = 1, tidentif = ?, nombres = ?, apellidos = ? '
                    . 'WHERE nidentificacion = ? AND estado = 0';
                $stmtRe = sqlsrv_query($conn, $sqlReactivar, [$tidentif, $nombres, $apellidos, $nidentificacion]);
                if ($stmtRe === false) {
                    $errors = sqlsrv_errors();
                    $msgDup = personas_sqlsrv_es_duplicado($errors)
                        ? 'Error: La identificación ya pertenece a otro registro'
                        : 'Error al reactivar la persona';
                    echo json_encode([
                        'ok' => false,
                        'message' => $msgDup,
                        'sql_errors' => $errors,
                    ]);
                    exit;
                }
                $afectadas = sqlsrv_rows_affected($stmtRe);
                if (is_int($afectadas) && $afectadas === 0) {
                    echo json_encode([
                        'ok' => false,
                        'message' => 'No se pudo reactivar el registro. Intente nuevamente.',
                    ]);
                    exit;
                }
                echo json_encode([
                    'ok' => true,
                    'found' => true,
                    'reactivado' => true,
                    'message' => 'Registro reactivado correctamente',
                    'data' => [
                        'nidentificacion' => $nidentificacion,
                        'nombres'         => $nombres,
                        'apellidos'       => $apellidos,
                        'tidentif'        => $tidentif,
                    ],
                ]);
                exit;
            }
            echo json_encode([
                'ok' => false,
                'message' => 'Error: La identificación ya pertenece a otro registro',
            ]);
            exit;
        }
    }

    $sqlInsert = "INSERT INTO dbo.bit_personas (nidentificacion, tidentif, nombres, apellidos, estado) VALUES (?, ?, ?, ?, 1)";
    $params = [$nidentificacion, $tidentif, $nombres, $apellidos];
    $stmt = sqlsrv_query($conn, $sqlInsert, $params);

    if ($stmt === false) {
        $errors = sqlsrv_errors();
        $msg = personas_sqlsrv_es_duplicado($errors)
            ? 'Error: La identificación ya pertenece a otro registro'
            : 'Error al guardar la persona';
        echo json_encode([
            'ok' => false,
            'message' => $msg,
            'sql_errors' => $errors,
        ]);
        exit;
    }

    echo json_encode([
        'ok' => true,
        'found' => true,
        'message' => 'Persona registrada correctamente',
        'data' => [
            'nidentificacion' => $nidentificacion,
            'nombres'         => $nombres,
            'apellidos'       => $apellidos,
            'tidentif'        => $tidentif,
        ],
    ]);
    exit;
}

echo json_encode(['ok' => false, 'message' => 'Método no permitido']);
exit;
