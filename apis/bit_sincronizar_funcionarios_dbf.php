<?php
/**
 * Sincroniza funcionarios activos desde rolmaes.DBF a la tabla local funcionarios
 * e devuelve la lista para poblar el select del formulario de registro de visitas.
 * Solo se consideran activos los que no tienen fecha de salida en el DBF.
 */

require_once __DIR__ . '/bit_lector_rolmaes_dbf.php';

/**
 * Comprueba si la tabla funcionarios tiene la columna cedula.
 */
function _funcionarios_tiene_cedula($conn) {
    static $tiene = null;
    if ($tiene !== null) return $tiene;
    $rs = @sqlsrv_query($conn, "SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = N'dbo' AND TABLE_NAME = N'bit_funcionarios' AND COLUMN_NAME = N'cedula'");
    $tiene = ($rs && sqlsrv_fetch_array($rs));
    if ($rs) sqlsrv_free_stmt($rs);
    return $tiene;
}

function _funcionarios_tiene_columna($conn, $columna) {
    static $cache = [];
    $key = strtoupper((string)$columna);
    if (array_key_exists($key, $cache)) return $cache[$key];
    $rs = @sqlsrv_query($conn, "SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = N'dbo' AND TABLE_NAME = N'bit_funcionarios' AND COLUMN_NAME = ?", [$columna]);
    $cache[$key] = ($rs && sqlsrv_fetch_array($rs));
    if ($rs) sqlsrv_free_stmt($rs);
    return $cache[$key];
}

function _departamentos_existe_tabla($conn) {
    static $existe = null;
    if ($existe !== null) return $existe;
    $rs = @sqlsrv_query($conn, "SELECT CASE WHEN OBJECT_ID(N'dbo.bit_departamentos', N'U') IS NULL THEN 0 ELSE 1 END AS ok");
    $row = $rs ? sqlsrv_fetch_array($rs, SQLSRV_FETCH_ASSOC) : null;
    if ($rs) sqlsrv_free_stmt($rs);
    $existe = ($row && (int)$row['ok'] === 1);
    return $existe;
}

function _departamento_id_por_nombre($conn, $nombreDepartamento) {
    if (!_departamentos_existe_tabla($conn)) return null;
    $nombreDepartamento = trim((string)$nombreDepartamento);
    if ($nombreDepartamento === '') return null;
    $st = @sqlsrv_query(
        $conn,
        "SELECT TOP 1 iddepart FROM dbo.bit_departamentos WHERE ISNULL(estado,1)=1 AND LTRIM(RTRIM(nom_departa)) = LTRIM(RTRIM(?))",
        [$nombreDepartamento]
    );
    $row = $st ? sqlsrv_fetch_array($st, SQLSRV_FETCH_ASSOC) : null;
    if ($st) sqlsrv_free_stmt($st);
    return $row ? (int)$row['iddepart'] : null;
}

function _departamento_id_por_codigo($conn, $codDepartamento) {
    if (!_departamentos_existe_tabla($conn)) return null;
    if (!_departamentos_tiene_columna($conn, 'nota')) return null;
    $codDepartamento = trim((string)$codDepartamento);
    if ($codDepartamento === '') return null;
    $st = @sqlsrv_query(
        $conn,
        "SELECT TOP 1 iddepart
         FROM dbo.bit_departamentos
         WHERE ISNULL(estado,1)=1
           AND UPPER(LTRIM(RTRIM(CONVERT(NVARCHAR(20), nota)))) = UPPER(LTRIM(RTRIM(?)))",
        [$codDepartamento]
    );
    $row = $st ? sqlsrv_fetch_array($st, SQLSRV_FETCH_ASSOC) : null;
    if ($st) sqlsrv_free_stmt($st);
    return $row ? (int)$row['iddepart'] : null;
}

function _departamentos_tiene_columna($conn, $columna) {
    static $cache = [];
    $key = strtoupper((string)$columna);
    if (array_key_exists($key, $cache)) return $cache[$key];
    $rs = @sqlsrv_query($conn, "SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = N'dbo' AND TABLE_NAME = N'bit_departamentos' AND COLUMN_NAME = ?", [$columna]);
    $cache[$key] = ($rs && sqlsrv_fetch_array($rs));
    if ($rs) sqlsrv_free_stmt($rs);
    return $cache[$key];
}

function _upsert_departamento_desde_dbf($conn, $codDepartamento, $seccion) {
    if (!_departamentos_existe_tabla($conn)) return null;
    $codDepartamento = trim((string)$codDepartamento);
    $seccion = trim((string)$seccion);
    if ($codDepartamento === '') return null;
    // 1) Deduplicación principal por código normalizado
    $id = _departamento_id_por_codigo($conn, $codDepartamento);
    if ($id !== null) return $id;
    // 2) Respaldo por nombre
    $id = _departamento_id_por_nombre($conn, $codDepartamento);
    if ($id !== null) return $id;
    $columnas = ['nom_departa'];
    $valoresSql = ['?'];
    $params = [$codDepartamento];
    if (_departamentos_tiene_columna($conn, 'nota')) {
        $columnas[] = 'nota';
        $valoresSql[] = '?';
        $params[] = $codDepartamento;
    }
    if (_departamentos_tiene_columna($conn, 'estado')) {
        $columnas[] = 'estado';
        $valoresSql[] = '1';
    }
    $sqlIns = "INSERT INTO dbo.bit_departamentos (" . implode(', ', $columnas) . ") OUTPUT INSERTED.iddepart VALUES (" . implode(', ', $valoresSql) . ")";
    $stIns = @sqlsrv_query($conn, $sqlIns, $params);
    $rowIns = $stIns ? sqlsrv_fetch_array($stIns, SQLSRV_FETCH_ASSOC) : null;
    if ($stIns) sqlsrv_free_stmt($stIns);
    return $rowIns ? (int)$rowIns['iddepart'] : null;
}

/**
 * Comprueba si la tabla funcionarios tiene columna FEC_SALIDA (si se importó desde DBF).
 */
function _funcionarios_tiene_fec_salida($conn) {
    return _funcionarios_tiene_columna($conn, 'FEC_SALIDA');
}

/**
 * Condición SQL para "activo" basada en FEC_SALIDA (si existe).
 */
function _funcionarios_sql_filtro_activos_por_fec_salida($conn) {
    if (!_funcionarios_tiene_fec_salida($conn)) return '1=1';
    return "(dbo.bit_funcionarios.FEC_SALIDA IS NULL
        OR LTRIM(RTRIM(CONVERT(NVARCHAR(50), dbo.bit_funcionarios.FEC_SALIDA))) = N''
        OR TRY_CONVERT(date, dbo.bit_funcionarios.FEC_SALIDA) IS NULL
        OR TRY_CONVERT(date, dbo.bit_funcionarios.FEC_SALIDA) IN (CONVERT(date,'1900-01-01'), CONVERT(date,'1899-12-30'))
    )";
}

/**
 * Obtiene los funcionarios activos del DBF, los inserta en local si no existen (por cédula),
 * y devuelve la lista para el dropdown: array de ['id_funcionario' => int, 'nombre' => string, 'cargo' => string].
 * Si la tabla no tiene columna cedula, se cargan todos los funcionarios desde la BD local (comportamiento de respaldo).
 *
 * @param resource $conn Conexión SQL Server
 * @return array
 */
function obtener_funcionarios_activos_sincronizados($conn) {
    $tieneCedula = _funcionarios_tiene_cedula($conn);
    $tieneCodDepartamento = _funcionarios_tiene_columna($conn, 'cod_departamento');
    $tieneSeccion = _funcionarios_tiene_columna($conn, 'seccion');
    $tieneIdDepartamento = _funcionarios_tiene_columna($conn, 'id_departamento');
    $activosDbf = obtener_funcionarios_activos_dbf(null);
    $inactivasDbf = obtener_cedulas_inactivas_dbf(null);
    $filtroActivoSql = _funcionarios_sql_filtro_activos_por_fec_salida($conn);

    if (count($activosDbf) > 0 && $tieneCedula) {
        $sqlCheck = "SELECT id_funcionario FROM dbo.bit_funcionarios WHERE estado = 1 AND $filtroActivoSql AND cedula = ?";
        $cedulasActivas = [];

        foreach ($activosDbf as $item) {
            $cedula = $item['cedula'];
            $nombre = $item['nombre'];
            $cargo  = $item['cargo'];
            $codDepartamento = trim((string)($item['DEPARTAMEN'] ?? ''));
            $seccion = trim((string)($item['seccion'] ?? ''));
            $idDepartamento = _upsert_departamento_desde_dbf($conn, $codDepartamento, $seccion);
            $cedulasActivas[] = $cedula;

            $stmt = sqlsrv_query($conn, $sqlCheck, [$cedula]);
            if ($stmt && sqlsrv_fetch_array($stmt)) {
                if ($stmt) sqlsrv_free_stmt($stmt);
                continue;
            }
            if ($stmt) sqlsrv_free_stmt($stmt);

            $columnas = ['cedula', 'nombre', 'cargo'];
            $valoresSql = ['?', '?', '?'];
            $paramsInsert = [$cedula, $nombre, $cargo];
            if ($tieneCodDepartamento) {
                $columnas[] = 'cod_departamento';
                $valoresSql[] = '?';
                $paramsInsert[] = ($codDepartamento !== '' ? $codDepartamento : null);
            }
            if ($tieneIdDepartamento) {
                $columnas[] = 'id_departamento';
                $valoresSql[] = '?';
                $paramsInsert[] = $idDepartamento;
            }
            if ($tieneSeccion) {
                $columnas[] = 'seccion';
                $valoresSql[] = '?';
                $paramsInsert[] = ($seccion !== '' ? $seccion : null);
            }
            $columnas[] = 'estado';
            $valoresSql[] = '1';
            $sqlInsert = "INSERT INTO dbo.bit_funcionarios (" . implode(', ', $columnas) . ") OUTPUT INSERTED.id_funcionario VALUES (" . implode(', ', $valoresSql) . ")";
            $stmtIns = sqlsrv_query($conn, $sqlInsert, $paramsInsert);
            if ($stmtIns) sqlsrv_free_stmt($stmtIns);
        }

        // Desactivar en local los que ya tienen fecha de salida en el DBF.
        if (count($inactivasDbf) > 0) {
            $sqlDes = "UPDATE dbo.bit_funcionarios SET estado = 0 WHERE cedula = ?";
            foreach ($inactivasDbf as $ced) {
                @sqlsrv_query($conn, $sqlDes, [$ced]);
            }
        }

        if (count($cedulasActivas) === 0) {
            return _query_funcionarios_ordenados($conn, true);
        }

        $resultado = [];
        $placeholders = implode(',', array_fill(0, count($cedulasActivas), '?'));
        $selectCols = "id_funcionario, nombre, cargo, cedula";
        if ($tieneCodDepartamento) $selectCols .= ", cod_departamento";
        if ($tieneSeccion) $selectCols .= ", seccion";
        $sqlSelect = "SELECT $selectCols FROM dbo.bit_funcionarios WHERE estado = 1 AND $filtroActivoSql AND cedula IN ($placeholders) ORDER BY nombre";
        $stmt = sqlsrv_query($conn, $sqlSelect, $cedulasActivas);
        if ($stmt) {
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $resultado[] = [
                    'id_funcionario' => (int)$row['id_funcionario'],
                    'nombre'         => $row['nombre'],
                    'cargo'          => $row['cargo'],
                    'cedula'         => isset($row['cedula']) ? trim((string)$row['cedula']) : '',
                    'cod_departamento' => isset($row['cod_departamento']) ? trim((string)$row['cod_departamento']) : '',
                    'seccion'        => isset($row['seccion']) ? trim((string)$row['seccion']) : ''
                ];
            }
            sqlsrv_free_stmt($stmt);
        }
        return $resultado;
    }

    if (count($activosDbf) > 0 && !$tieneCedula) {
        $sqlCheck = "SELECT 1 FROM dbo.bit_funcionarios WHERE estado = 1 AND nombre = ? AND cargo = ?";
        $sqlInsert = "INSERT INTO dbo.bit_funcionarios (nombre, cargo, estado) VALUES (?, ?, 1)";
        foreach ($activosDbf as $item) {
            $nombre = $item['nombre'];
            $cargo  = $item['cargo'];
            $stmt = sqlsrv_query($conn, $sqlCheck, [$nombre, $cargo]);
            if ($stmt && sqlsrv_fetch_array($stmt)) {
                if ($stmt) sqlsrv_free_stmt($stmt);
                continue;
            }
            if ($stmt) sqlsrv_free_stmt($stmt);
            sqlsrv_query($conn, $sqlInsert, [$nombre, $cargo]);
        }
    }

    return _query_funcionarios_ordenados($conn, $tieneCedula);
}

/**
 * Devuelve todos los funcionarios de la tabla local ordenados por nombre.
 * @param bool $incluirCedula Si la tabla tiene columna cedula, incluirla para búsqueda en Select2.
 */
function _query_funcionarios_ordenados($conn, $incluirCedula = false) {
    $resultado = [];
    $filtroActivoSql = _funcionarios_sql_filtro_activos_por_fec_salida($conn);
    $tieneCodDepartamento = _funcionarios_tiene_columna($conn, 'cod_departamento');
    $tieneSeccion = _funcionarios_tiene_columna($conn, 'seccion');
    $selectCols = "id_funcionario, nombre, cargo";
    if ($incluirCedula) $selectCols .= ", cedula";
    if ($tieneCodDepartamento) $selectCols .= ", cod_departamento";
    if ($tieneSeccion) $selectCols .= ", seccion";
    $sql = $incluirCedula
        ? "SELECT $selectCols FROM dbo.bit_funcionarios WHERE estado = 1 AND $filtroActivoSql ORDER BY nombre"
        : "SELECT $selectCols FROM dbo.bit_funcionarios WHERE estado = 1 AND $filtroActivoSql ORDER BY nombre";
    $stmt = sqlsrv_query($conn, $sql);
    if ($stmt) {
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $item = [
                'id_funcionario' => (int)$row['id_funcionario'],
                'nombre'         => $row['nombre'],
                'cargo'          => $row['cargo']
            ];
            if ($incluirCedula && isset($row['cedula'])) {
                $item['cedula'] = trim((string)$row['cedula']);
            } else {
                $item['cedula'] = '';
            }
            $item['cod_departamento'] = isset($row['cod_departamento']) ? trim((string)$row['cod_departamento']) : '';
            $item['seccion'] = isset($row['seccion']) ? trim((string)$row['seccion']) : '';
            $resultado[] = $item;
        }
        sqlsrv_free_stmt($stmt);
    }
    return $resultado;
}
