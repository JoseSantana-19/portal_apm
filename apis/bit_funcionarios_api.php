<?php
require_once __DIR__ . '/../includes/bit_api_guard.php'; // Portal APM: sesion obligatoria

// apis/bit_funcionarios_api.php - Búsqueda (local + DBF FoxPro) y registro rápido de funcionarios

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../conexion/conexion.php';
require_once __DIR__ . '/bit_lector_rolmaes_dbf.php';
require_once __DIR__ . '/../includes/bit_validaciones_ecuador.php';

/**
 * Comprueba si la tabla funcionarios tiene la columna cedula.
 */
function funcionarios_tiene_cedula($conn) {
    static $tiene = null;
    if ($tiene !== null) return $tiene;
    $rs = @sqlsrv_query($conn, "SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = N'dbo' AND TABLE_NAME = N'bit_funcionarios' AND COLUMN_NAME = N'cedula'");
    $tiene = ($rs && sqlsrv_fetch_array($rs));
    if ($rs) sqlsrv_free_stmt($rs);
    return $tiene;
}

function funcionarios_tiene_columna($conn, $columna) {
    static $cache = [];
    $key = strtoupper((string)$columna);
    if (array_key_exists($key, $cache)) return $cache[$key];
    $rs = @sqlsrv_query($conn, "SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = N'dbo' AND TABLE_NAME = N'bit_funcionarios' AND COLUMN_NAME = ?", [$columna]);
    $cache[$key] = ($rs && sqlsrv_fetch_array($rs));
    if ($rs) sqlsrv_free_stmt($rs);
    return $cache[$key];
}

function departamentos_existe_tabla($conn) {
    static $existe = null;
    if ($existe !== null) return $existe;
    $rs = @sqlsrv_query($conn, "SELECT CASE WHEN OBJECT_ID(N'dbo.bit_departamentos', N'U') IS NULL THEN 0 ELSE 1 END AS ok");
    $row = $rs ? sqlsrv_fetch_array($rs, SQLSRV_FETCH_ASSOC) : null;
    if ($rs) sqlsrv_free_stmt($rs);
    $existe = ($row && (int)$row['ok'] === 1);
    return $existe;
}

function departamentos_id_por_nombre($conn, $nombreDepartamento) {
    if (!departamentos_existe_tabla($conn)) return null;
    $nombreDepartamento = trim((string)$nombreDepartamento);
    if ($nombreDepartamento === '') return null;
    $sql = "SELECT TOP 1 iddepart FROM dbo.bit_departamentos WHERE ISNULL(estado,1)=1 AND LTRIM(RTRIM(nom_departa)) = LTRIM(RTRIM(?))";
    $st = sqlsrv_query($conn, $sql, [$nombreDepartamento]);
    $row = $st ? sqlsrv_fetch_array($st, SQLSRV_FETCH_ASSOC) : null;
    if ($st) sqlsrv_free_stmt($st);
    return $row ? (int)$row['iddepart'] : null;
}

function departamentos_id_por_codigo($conn, $codDepartamento) {
    if (!departamentos_existe_tabla($conn)) return null;
    $codDepartamento = trim((string)$codDepartamento);
    if ($codDepartamento === '') return null;
    $rsCol = @sqlsrv_query($conn, "SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=N'dbo' AND TABLE_NAME=N'bit_departamentos' AND COLUMN_NAME=N'nota'");
    $tieneCol = ($rsCol && sqlsrv_fetch_array($rsCol));
    if ($rsCol) sqlsrv_free_stmt($rsCol);
    if (!$tieneCol) return null;
    $sql = "SELECT TOP 1 iddepart
            FROM dbo.bit_departamentos
            WHERE ISNULL(estado,1)=1
              AND UPPER(LTRIM(RTRIM(CONVERT(NVARCHAR(20), nota)))) = UPPER(LTRIM(RTRIM(?)))";
    $st = sqlsrv_query($conn, $sql, [$codDepartamento]);
    $row = $st ? sqlsrv_fetch_array($st, SQLSRV_FETCH_ASSOC) : null;
    if ($st) sqlsrv_free_stmt($st);
    return $row ? (int)$row['iddepart'] : null;
}

/**
 * Comprueba si la tabla funcionarios tiene la columna FEC_SALIDA (o similar importada).
 */
function funcionarios_tiene_fec_salida($conn) {
    return funcionarios_tiene_columna($conn, 'FEC_SALIDA');
}

/**
 * Condición SQL para "funcionario activo" según FEC_SALIDA (si existe).
 * Soporta importaciones como DATE/DATETIME o VARCHAR con placeholders.
 */
function funcionarios_sql_filtro_activos_por_fec_salida($conn) {
    if (!funcionarios_tiene_fec_salida($conn)) return '1=1';
    // Activo si FEC_SALIDA es NULL / vacío / no-convertible / o una fecha placeholder típica.
    return "(dbo.bit_funcionarios.FEC_SALIDA IS NULL
        OR LTRIM(RTRIM(CONVERT(NVARCHAR(50), dbo.bit_funcionarios.FEC_SALIDA))) = N''
        OR TRY_CONVERT(date, dbo.bit_funcionarios.FEC_SALIDA) IS NULL
        OR TRY_CONVERT(date, dbo.bit_funcionarios.FEC_SALIDA) IN (CONVERT(date,'1900-01-01'), CONVERT(date,'1899-12-30'))
    )";
}

// --- GET: buscar funcionario por cédula (primero local, luego DBF; si viene de DBF se inserta en local) ---
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $cedula = isset($_GET['cedula']) ? trim($_GET['cedula']) : '';
    if ($cedula === '') {
        echo json_encode(['ok' => false, 'message' => 'Parámetro cedula requerido']);
        exit;
    }
    $cedulaNorm = preg_replace('/[^0-9]/', '', $cedula);
    if ($cedulaNorm === '') {
        echo json_encode(['ok' => false, 'message' => 'Cédula no válida']);
        exit;
    }

    $tieneCedula = funcionarios_tiene_cedula($conn);
    $tieneCodDepartamento = funcionarios_tiene_columna($conn, 'cod_departamento');
    $tieneSeccion = funcionarios_tiene_columna($conn, 'seccion');
    $tieneIdDepartamento = funcionarios_tiene_columna($conn, 'id_departamento');
    $filtroActivoSql = funcionarios_sql_filtro_activos_por_fec_salida($conn);

    // 1) Buscar en tabla local (si tiene columna cedula)
    if ($tieneCedula) {
        $sqlLocal = "SELECT id_funcionario, nombre, cargo FROM dbo.bit_funcionarios WHERE estado = 1 AND $filtroActivoSql AND cedula = ?";
        $stmtLocal = sqlsrv_query($conn, $sqlLocal, [$cedulaNorm]);
        if ($stmtLocal !== false) {
            $row = sqlsrv_fetch_array($stmtLocal, SQLSRV_FETCH_ASSOC);
            if ($row) {
                echo json_encode([
                    'ok'   => true,
                    'found' => true,
                    'data' => [
                        'id_funcionario' => (int)$row['id_funcionario'],
                        'cedula'         => $cedulaNorm,
                        'nombre'         => $row['nombre'],
                        'cargo'          => $row['cargo']
                    ]
                ]);
                exit;
            }
        }
    }

    // 2) Buscar en archivo DBF (rolmaes.DBF)
    $datoDbf = leer_funcionario_por_cedula_dbf($cedulaNorm, null);
    if ($datoDbf !== null) {
        $nombre = $datoDbf['nombre'];
        $cargo  = $datoDbf['cargo'];
        $codDepartamento = trim((string)($datoDbf['DEPARTAMEN'] ?? ''));
        $seccion = trim((string)($datoDbf['seccion'] ?? ''));
        $idDepartamento = null;
        if ($codDepartamento !== '') {
            $idDepartamento = departamentos_id_por_codigo($conn, $codDepartamento);
            if ($idDepartamento === null) {
                $idDepartamento = departamentos_id_por_nombre($conn, $codDepartamento);
            }
        }

        // Evitar duplicado: si la tabla tiene cedula, comprobar de nuevo por si otro proceso lo insertó
        if ($tieneCedula) {
            $sqlCheck = "SELECT id_funcionario, nombre, cargo FROM dbo.bit_funcionarios WHERE estado = 1 AND $filtroActivoSql AND cedula = ?";
            $stCheck = sqlsrv_query($conn, $sqlCheck, [$cedulaNorm]);
            if ($stCheck) {
                $existe = sqlsrv_fetch_array($stCheck, SQLSRV_FETCH_ASSOC);
                if ($existe) {
                    echo json_encode([
                        'ok'   => true,
                        'found' => true,
                        'data' => [
                            'id_funcionario' => (int)$existe['id_funcionario'],
                            'cedula'         => $cedulaNorm,
                            'nombre'         => $existe['nombre'],
                            'cargo'          => $existe['cargo']
                        ]
                    ]);
                    exit;
                }
            }
        }

        // Insertar en local (consultas preparadas)
        $columnas = [];
        $valoresSql = [];
        $paramsInsert = [];
        if ($tieneCedula) {
            $columnas[] = 'cedula';
            $valoresSql[] = '?';
            $paramsInsert[] = $cedulaNorm;
        }
        $columnas[] = 'nombre';
        $valoresSql[] = '?';
        $paramsInsert[] = $nombre;
        $columnas[] = 'cargo';
        $valoresSql[] = '?';
        $paramsInsert[] = $cargo;
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
        if ($stmtIns !== false) {
            $rowIns = sqlsrv_fetch_array($stmtIns, SQLSRV_FETCH_ASSOC);
            $idFuncionario = $rowIns ? (int)$rowIns['id_funcionario'] : 0;
            if ($idFuncionario > 0) {
                echo json_encode([
                    'ok'   => true,
                    'found' => true,
                    'data' => [
                        'id_funcionario' => $idFuncionario,
                        'cedula'         => $cedulaNorm,
                        'nombre'         => $nombre,
                        'cargo'          => $cargo,
                        'DEPARTAMEN' => $codDepartamento,
                        'seccion'        => $seccion,
                        'id_departamento' => $idDepartamento
                    ]
                ]);
                exit;
            }
        }
    }

    echo json_encode([
        'ok'    => true,
        'found' => false,
        'message' => 'No se encontró el funcionario con esa cédula en la base local ni en el archivo DBF.'
    ]);
    exit;
}

// --- POST: alta rápida de funcionario (nombre, cargo; opcional cedula si la tabla lo tiene) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre  = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
    $cargo   = isset($_POST['cargo']) ? trim($_POST['cargo']) : '';
    $cedula  = isset($_POST['cedula']) ? trim(preg_replace('/[^0-9]/', '', $_POST['cedula'])) : '';

    if ($nombre === '' || $cargo === '') {
        echo json_encode(['ok' => false, 'message' => 'Nombre y cargo son obligatorios']);
        exit;
    }

    $tieneCedula = funcionarios_tiene_cedula($conn);
    $tieneCodDepartamento = funcionarios_tiene_columna($conn, 'cod_departamento');
    $tieneSeccion = funcionarios_tiene_columna($conn, 'seccion');
    $tieneIdDepartamento = funcionarios_tiene_columna($conn, 'id_departamento');
    $codDepartamento = isset($_POST['DEPARTAMEN']) ? trim((string)$_POST['DEPARTAMEN']) : (isset($_POST['cod_departamento']) ? trim((string)$_POST['cod_departamento']) : '');
    $seccion = isset($_POST['seccion']) ? trim((string)$_POST['seccion']) : '';
    $idDepartamento = null;
    if ($codDepartamento !== '') {
        $idDepartamento = departamentos_id_por_codigo($conn, $codDepartamento);
        if ($idDepartamento === null) {
            $idDepartamento = departamentos_id_por_nombre($conn, $codDepartamento);
        }
        if ($idDepartamento === null) {
            echo json_encode(['ok' => false, 'message' => 'El departamento no coincide con el catálogo externo (rolmaes.DBF).']);
            exit;
        }
    }
    if ($tieneCedula && $cedula === '') {
        echo json_encode(['ok' => false, 'message' => 'La cédula es obligatoria']);
        exit;
    }

    if ($tieneCedula && $cedula !== '') {
        if (strlen($cedula) !== 10 || !ec_validar_cedula_ecuador($cedula)) {
            echo json_encode(['ok' => false, 'message' => apm_mensaje_identificacion_invalida()]);
            exit;
        }
        $filtroActivoSql = funcionarios_sql_filtro_activos_por_fec_salida($conn);
        $sqlCheck = "SELECT id_funcionario, nombre, cargo FROM dbo.bit_funcionarios WHERE estado = 1 AND $filtroActivoSql AND cedula = ?";
        $stCheck = sqlsrv_query($conn, $sqlCheck, [$cedula]);
        if ($stCheck && $r = sqlsrv_fetch_array($stCheck, SQLSRV_FETCH_ASSOC)) {
            echo json_encode([
                'ok'   => true,
                'data' => [
                    'id_funcionario' => (int)$r['id_funcionario'],
                    'nombre'         => $r['nombre'],
                    'cargo'          => $r['cargo']
                ]
            ]);
            exit;
        }
    }

    $columnas = [];
    $valoresSql = [];
    $params = [];
    if ($tieneCedula) {
        $columnas[] = 'cedula';
        $valoresSql[] = '?';
        $params[] = ($cedula !== '' ? $cedula : null);
    }
    $columnas[] = 'nombre';
    $valoresSql[] = '?';
    $params[] = $nombre;
    $columnas[] = 'cargo';
    $valoresSql[] = '?';
    $params[] = $cargo;
    if ($tieneCodDepartamento) {
        $columnas[] = 'cod_departamento';
        $valoresSql[] = '?';
        $params[] = ($codDepartamento !== '' ? $codDepartamento : null);
    }
    if ($tieneIdDepartamento) {
        $columnas[] = 'id_departamento';
        $valoresSql[] = '?';
        $params[] = $idDepartamento;
    }
    if ($tieneSeccion) {
        $columnas[] = 'seccion';
        $valoresSql[] = '?';
        $params[] = ($seccion !== '' ? $seccion : null);
    }
    $columnas[] = 'estado';
    $valoresSql[] = '1';
    $sqlInsert = "INSERT INTO dbo.bit_funcionarios (" . implode(', ', $columnas) . ") OUTPUT INSERTED.id_funcionario VALUES (" . implode(', ', $valoresSql) . ")";
    $stmt = sqlsrv_query($conn, $sqlInsert, $params);

    if ($stmt === false) {
        echo json_encode(['ok' => false, 'message' => 'Error al guardar el funcionario']);
        exit;
    }

    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    $idFuncionario = $row ? (int)$row['id_funcionario'] : 0;

    echo json_encode([
        'ok' => true,
        'data' => [
            'id_funcionario' => $idFuncionario,
            'cedula'         => $cedula !== '' ? $cedula : null,
            'nombre'         => $nombre,
            'cargo'          => $cargo,
            'DEPARTAMEN' => $codDepartamento !== '' ? $codDepartamento : null,
            'seccion'        => $seccion !== '' ? $seccion : null,
            'id_departamento' => $idDepartamento
        ]
    ]);
    exit;
}

echo json_encode(['ok' => false, 'message' => 'Método no permitido']);
exit;
