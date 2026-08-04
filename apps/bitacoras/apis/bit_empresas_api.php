<?php
require_once __DIR__ . '/../includes/bit_api_guard.php'; // Portal APM: sesion obligatoria

// apis/bit_empresas_api.php - Búsqueda en BD local (empresas) y externa (reg_empresas)

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../conexion/conexion_externa.php';
require_once __DIR__ . '/../conexion/conexion.php';
require_once __DIR__ . '/../includes/bit_validaciones_ecuador.php';

$method = $_SERVER['REQUEST_METHOD'];

/**
 * Busca empresas por nombre comercial, razón social o RUC.
 * @param bool $externo true → tabla dbo.reg_empresas (PortuariaExterna)
 */
function buscarEmpresasEnConexion($conn, $q, $limit = 30, $externo = false)
{
    if (!$conn || $q === '') {
        return [];
    }
    $term = '%' . str_replace(['%', '_'], ['[%]', '[_]'], $q) . '%';
    $estadoCond = $externo ? '(CAST(ISNULL(estado,1) AS TINYINT) = 1)' : 'estado = 1';
    $tabla = $externo ? 'reg_empresas' : 'bit_empresas';
    $idExpr = $externo ? 'idempresa AS id_empresa' : 'id_empresa';
    // Compatibilidad APM externa: reg_empresas + alias idempresa->id_empresa.
    // COLLATE Latin1_General_CI_AI permite buscar sin importar mayúsculas, minúsculas o tildes.
    // Ejemplo: al escribir "paci" también puede encontrar "Pacífico".
    $sql = "SELECT TOP " . (int) $limit . " $idExpr, empresa, razonsocial, ruc FROM dbo.$tabla WHERE " . $estadoCond
        . " AND (empresa COLLATE Latin1_General_CI_AI LIKE ? OR ISNULL(razonsocial,'') COLLATE Latin1_General_CI_AI LIKE ? OR ISNULL(ruc,'') LIKE ?) ORDER BY empresa";
    $stmt = sqlsrv_query($conn, $sql, [$term, $term, $term]);
    if ($stmt === false) {
        return [];
    }
    $rows = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $rows[] = $row;
    }
    return $rows;
}

function asegurarEmpresaLocal($conn, $empresa, $ruc, $razonsocial = null)
{
    $razon = ($razonsocial !== null && $razonsocial !== '') ? $razonsocial : $empresa;
    if ($ruc !== '') {
        $stCheck = sqlsrv_query($conn, "SELECT id_empresa FROM dbo.bit_empresas WHERE ruc = ?", [$ruc]);
        if ($stCheck && $r = sqlsrv_fetch_array($stCheck, SQLSRV_FETCH_ASSOC)) {
            return (int) $r['id_empresa'];
        }
    }
    sqlsrv_query($conn, "INSERT INTO dbo.bit_empresas (empresa, razonsocial, ruc, estado) VALUES (?, ?, ?, 1)", [$empresa, $razon, $ruc]);
    $stId = sqlsrv_query($conn, "SELECT SCOPE_IDENTITY() AS id");
    if ($stId && $rId = sqlsrv_fetch_array($stId, SQLSRV_FETCH_ASSOC)) {
        return (int) $rId['id'];
    }
    return null;
}

function fila_empresa_a_resultado(array $row, $idLocal = null)
{
    $id = $idLocal !== null ? (int) $idLocal : (int) $row['id_empresa'];
    $empresa = $row['empresa'] ?? '';
    $ruc = $row['ruc'] ?? '';
    $label = $empresa;
    if ($ruc !== '') {
        $label .= ' (' . $ruc . ')';
    }
    return [
        'id_empresa' => $id,
        'empresa'    => $empresa,
        'razonsocial'=> $row['razonsocial'] ?? null,
        'ruc'        => $ruc,
        'label'      => $label,
    ];
}

if ($method === 'GET') {
    $q = isset($_GET['q']) ? trim($_GET['q']) : '';
    if ($q === '') {
        echo json_encode(['ok' => false, 'message' => 'Indique nombre o RUC para buscar']);
        exit;
    }

    $results = [];
    $rucsVistos = [];

    foreach (buscarEmpresasEnConexion($conn, $q, 30, false) as $row) {
        $ruc = $row['ruc'] ?? '';
        $rucsVistos[$ruc] = true;
        $results[] = fila_empresa_a_resultado($row);
    }

    if ($connExterna !== null) {
        foreach (buscarEmpresasEnConexion($connExterna, $q, 30, true) as $rowExt) {
            $rucExt = $rowExt['ruc'] ?? '';
            if ($rucExt !== '' && !empty($rucsVistos[$rucExt])) {
                continue;
            }
            $nombreExt = $rowExt['empresa'] ?? '';
            $razonExt = $rowExt['razonsocial'] ?? null;
            $idLocal = asegurarEmpresaLocal($conn, $nombreExt, $rucExt, $razonExt);
            if ($idLocal !== null) {
                if ($rucExt !== '') {
                    $rucsVistos[$rucExt] = true;
                }
                $results[] = fila_empresa_a_resultado($rowExt, $idLocal);
            }
        }
    }

    echo json_encode([
        'ok'      => true,
        'found'   => count($results) > 0,
        'results' => $results,
        'message' => count($results) > 0 ? null : 'No se encontró este registro en ninguna de las bases de datos.',
    ]);
    exit;
}

if ($method === 'POST') {
    $empresa = isset($_POST['empresa']) ? trim($_POST['empresa']) : '';
    if ($empresa === '' && isset($_POST['nombre'])) {
        $empresa = trim($_POST['nombre']);
    }
    $ruc = isset($_POST['ruc']) ? trim($_POST['ruc']) : '';
    $razonsocial = isset($_POST['razonsocial']) ? trim($_POST['razonsocial']) : '';

    if ($empresa === '' || $ruc === '' || $razonsocial === '') {
        echo json_encode(['ok' => false, 'message' => 'Empresa, razón social y RUC son obligatorios']);
        exit;
    }

    $ruc = preg_replace('/\D/', '', $ruc);
    if (strlen($ruc) !== 13 || !ec_validar_ruc_ecuador($ruc)) {
        echo json_encode(['ok' => false, 'message' => apm_mensaje_identificacion_invalida()]);
        exit;
    }

    $sqlInsert = "INSERT INTO dbo.bit_empresas (empresa, razonsocial, ruc, estado) VALUES (?, ?, ?, 1)";
    $stmt = sqlsrv_query($conn, $sqlInsert, [$empresa, $razonsocial, $ruc]);

    if ($stmt === false) {
        echo json_encode(['ok' => false, 'message' => 'Error al guardar la empresa']);
        exit;
    }

    $stId = sqlsrv_query($conn, "SELECT SCOPE_IDENTITY() AS id");
    $row = $stId ? sqlsrv_fetch_array($stId, SQLSRV_FETCH_ASSOC) : null;
    $idEmpresa = $row ? (int) $row['id'] : 0;

    $label = $empresa . ' (' . $ruc . ')';
    echo json_encode([
        'ok' => true,
        'data' => [
            'id_empresa'  => $idEmpresa,
            'empresa'     => $empresa,
            'razonsocial' => $razonsocial,
            'ruc'         => $ruc,
            'label'       => $label,
        ],
    ]);
    exit;
}

echo json_encode(['ok' => false, 'message' => 'Método no permitido']);
exit;
