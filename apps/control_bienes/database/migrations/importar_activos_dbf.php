<?php
/**
 * Importador idempotente de activos.DBF.
 * Vista previa: php importar_activos_dbf.php
 * Ejecucion:    php importar_activos_dbf.php --execute
 */

$archivo = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'bases' . DIRECTORY_SEPARATOR . 'activos.DBF';
$archivoGrupos = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'bases' . DIRECTORY_SEPARATOR . 'grupos.DBF';
$archivoCategorias = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'bases' . DIRECTORY_SEPARATOR . 'GACTI.DBF';
$ejecutar = PHP_SAPI === 'cli' && in_array('--execute', $argv ?? [], true);

if (!is_file($archivo) || !is_file($archivoGrupos) || !is_file($archivoCategorias)) {
    exit("No se encontraron activos.DBF, grupos.DBF y GACTI.DBF.\n");
}

function inv_leer_dbf(string $archivo): Generator {
    $fh = fopen($archivo, 'rb');
    if (!$fh) throw new RuntimeException('No se pudo abrir activos.DBF');
    $cabecera = fread($fh, 32);
    $total = unpack('V', substr($cabecera, 4, 4))[1];
    $largoCabecera = unpack('v', substr($cabecera, 8, 2))[1];
    $largoRegistro = unpack('v', substr($cabecera, 10, 2))[1];
    $campos = [];

    while (ftell($fh) < $largoCabecera) {
        $descriptor = fread($fh, 32);
        if ($descriptor === false || $descriptor === '' || ord($descriptor[0]) === 13) break;
        $nombre = rtrim(substr($descriptor, 0, 11), "\0 ");
        $campos[] = ['nombre' => $nombre, 'largo' => ord($descriptor[16])];
    }

    fseek($fh, $largoCabecera);
    for ($i = 0; $i < $total; $i++) {
        $registro = fread($fh, $largoRegistro);
        if ($registro === false || strlen($registro) < $largoRegistro) break;
        if ($registro[0] === '*') continue;
        $fila = [];
        $pos = 1;
        foreach ($campos as $campo) {
            $valor = substr($registro, $pos, $campo['largo']);
            $pos += $campo['largo'];
            $fila[$campo['nombre']] = trim(iconv('Windows-1252', 'UTF-8//IGNORE', $valor));
        }
        yield $fila;
    }
    fclose($fh);
}

function inv_numero($valor): float {
    return is_numeric(trim((string)$valor)) ? (float)$valor : 0.0;
}

function inv_fecha_dbf($valor): ?string {
    $valor = trim((string)$valor);
    if (!preg_match('/^(\d{4})(\d{2})(\d{2})$/', $valor, $m)) return null;
    if (!checkdate((int)$m[2], (int)$m[3], (int)$m[1])) return null;
    return $m[1] . '-' . $m[2] . '-' . $m[3];
}

$activos = [];
$resumen = ['af_total' => 0, 'af_vigentes' => 0, 'af_baja' => 0, 'omitidos' => 0];
foreach (inv_leer_dbf($archivo) as $fila) {
    if (($fila['TIPO_BIEN'] ?? '') !== 'AF') continue;
    $codigo = trim($fila['CODIGO'] ?? '');
    $descripcion = trim($fila['IDES'] ?? '');
    if ($codigo === '') {
        $resumen['omitidos']++;
        continue;
    }
    if ($descripcion === '') {
        $fila['IDES'] = 'Activo sin descripción ' . $codigo;
    }
    $baja = in_array(strtoupper($fila['DADO_BAJA'] ?? ''), ['T', 'Y', '1'], true);
    $resumen['af_total']++;
    $resumen[$baja ? 'af_baja' : 'af_vigentes']++;
    $fila['_baja'] = $baja;
    $activos[] = $fila;
}

// Maestros contables: la relacion confiable es
// activos.IDGRUPO -> grupos.IDGACTI -> GACTI.G_COD/G_NOM.
$gruposMaestros = [];
foreach (inv_leer_dbf($archivoGrupos) as $fila) {
    $idGrupo = trim($fila['IDGRUPO'] ?? '');
    if ($idGrupo !== '') $gruposMaestros[$idGrupo] = $fila;
}
$categoriasMaestras = [];
foreach (inv_leer_dbf($archivoCategorias) as $fila) {
    $codigoCategoria = trim($fila['G_COD'] ?? '');
    if ($codigoCategoria !== '') {
        $categoriasMaestras[$codigoCategoria] = trim($fila['G_NOM'] ?? '') ?: $codigoCategoria;
    }
}

echo json_encode($resumen, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
if (!$ejecutar) {
    exit("Vista previa completada. Use --execute para importar.\n");
}

require_once dirname(__DIR__, 2) . '/db/connection.php';
$db = getPDOConnection();
$db->beginTransaction();

try {
    if (!$db->query("SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='inv_inventario' AND COLUMN_NAME='tipo_bien'")->fetchColumn()) {
        $db->exec("ALTER TABLE inv_inventario ADD tipo_bien CHAR(2) NOT NULL DEFAULT 'CC'");
    }

    $zonaId = (int)$db->query("SELECT TOP 1 id FROM inv_zonas ORDER BY id")->fetchColumn();
    $estadoId = (int)$db->query("SELECT TOP 1 idestado FROM inv_estados WHERE estado=1 ORDER BY idestado")->fetchColumn();
    if (!$zonaId || !$estadoId) throw new RuntimeException('Se requiere al menos una zona y un estado activo.');

    $buscar = $db->prepare("SELECT id FROM inv_inventario WHERE secuencial=:secuencial");
    $buscarCategoria = $db->prepare("SELECT TOP 1 id FROM inv_categorias WHERE codigo=:codigo");
    $crearCategoria = $db->prepare("INSERT INTO inv_categorias(nombre,codigo,extra) VALUES(:nombre,:codigo,:extra)");
    $insertar = $db->prepare(
        "INSERT INTO inv_inventario
         (secuencial,nombre,marca,categoria_id,zona_id,estado_id,responsable_id,valor,fecha_registro,observaciones,activo,cantidad,producto_id,tipo_bien)
         VALUES (:secuencial,:nombre,:marca,:categoria,:zona,:estado,NULL,:valor,:fecha,:observaciones,:activo,1,NULL,'AF')"
    );

    $categorias = [];
    $insertados = 0;
    $existentes = 0;
    foreach ($activos as $activo) {
        $secuencial = 'AF-' . $activo['CODIGO'];
        $buscar->execute([':secuencial' => $secuencial]);
        if ($buscar->fetchColumn()) {
            $existentes++;
            continue;
        }

        $idGrupo = trim($activo['IDGRUPO'] ?? '');
        $codigoGrupo = isset($gruposMaestros[$idGrupo])
            ? trim($gruposMaestros[$idGrupo]['IDGACTI'] ?? '')
            : trim($activo['G_COD'] ?? '');
        if ($codigoGrupo === '' || !isset($categoriasMaestras[$codigoGrupo])) {
            throw new RuntimeException("Activo {$activo['CODIGO']} sin categoria contable valida.");
        }
        if (!isset($categorias[$codigoGrupo])) {
            $buscarCategoria->execute([':codigo' => $codigoGrupo]);
            $categoriaId = (int)$buscarCategoria->fetchColumn();
            if (!$categoriaId) {
                $crearCategoria->execute([
                    ':nombre' => $categoriasMaestras[$codigoGrupo] . ' (' . $codigoGrupo . ')',
                    ':codigo' => $codigoGrupo,
                    ':extra' => 'Categoría creada durante la migración de activos.DBF'
                ]);
                $categoriaId = (int)$db->lastInsertId();
            }
            $categorias[$codigoGrupo] = $categoriaId;
        }

        $valor = inv_numero($activo['P_UNITARIO'] ?? 0);
        if ($valor <= 0) $valor = inv_numero($activo['ISAC_ACTA'] ?? 0);
        if ($valor <= 0) $valor = inv_numero($activo['TOTAL_PRO'] ?? 0);
        $fecha = inv_fecha_dbf($activo['A_FADQ'] ?? '') ?: date('Y-m-d');
        $observaciones = sprintf(
            'Migrado de activos.DBF; codigo legacy=%s; grupo=%s; idgrupo=%s; centro/departamento=%s; tipo original=AF%s',
            $activo['CODIGO'], $codigoGrupo, $activo['IDGRUPO'] ?? '', $activo['DCOD'] ?? '',
            $activo['_baja'] ? '; dado de baja en sistema anterior' : ''
        );
        $insertar->execute([
            ':secuencial' => $secuencial,
            ':nombre' => $activo['IDES'],
            ':marca' => trim($activo['A_MOD'] ?? '') ?: 'Sin especificar',
            ':categoria' => $categorias[$codigoGrupo],
            ':zona' => $zonaId,
            ':estado' => $estadoId,
            ':valor' => $valor,
            ':fecha' => $fecha,
            ':observaciones' => $observaciones,
            ':activo' => $activo['_baja'] ? 0 : 1
        ]);
        $insertados++;
    }

    $db->commit();
    echo "Importacion terminada. Insertados={$insertados}; existentes={$existentes}; vigentes esperados={$resumen['af_vigentes']}.\n";
} catch (Throwable $e) {
    if ($db->inTransaction()) $db->rollBack();
    fwrite(STDERR, "Importacion cancelada: {$e->getMessage()}\n");
    exit(1);
}
