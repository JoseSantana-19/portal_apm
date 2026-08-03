<?php
/**
 * Corrige nombres y asignaciones contables de activos importados desde DBF.
 *
 * Fuente autoritativa:
 *   activos.IDGRUPO -> grupos.IDGRUPO -> grupos.IDGACTI -> gacti.G_COD/G_NOM
 * Respaldo (solo cuando IDGRUPO esta vacio): activos.G_COD -> gacti.G_COD
 *
 * Vista previa: php corregir_categorias_activos_dbf.php
 * Ejecucion:    php corregir_categorias_activos_dbf.php --execute
 */

$baseDir = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'bases' . DIRECTORY_SEPARATOR;
$ejecutar = PHP_SAPI === 'cli' && in_array('--execute', $argv ?? [], true);

function cat_leer_dbf(string $archivo): Generator {
    $fh = fopen($archivo, 'rb');
    if (!$fh) throw new RuntimeException("No se pudo abrir {$archivo}");

    $cabecera = fread($fh, 32);
    $total = unpack('V', substr($cabecera, 4, 4))[1];
    $largoCabecera = unpack('v', substr($cabecera, 8, 2))[1];
    $largoRegistro = unpack('v', substr($cabecera, 10, 2))[1];
    $campos = [];

    while (ftell($fh) < $largoCabecera) {
        $descriptor = fread($fh, 32);
        if (!$descriptor || ord($descriptor[0]) === 13) break;
        $campos[] = [
            'nombre' => rtrim(substr($descriptor, 0, 11), "\0 "),
            'largo' => ord($descriptor[16]),
        ];
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

foreach (['GACTI.DBF', 'grupos.DBF', 'activos.DBF'] as $nombreArchivo) {
    if (!is_file($baseDir . $nombreArchivo)) {
        exit("No se encontro {$baseDir}{$nombreArchivo}\n");
    }
}

$categoriasMaestras = [];
foreach (cat_leer_dbf($baseDir . 'GACTI.DBF') as $fila) {
    $codigo = trim($fila['G_COD'] ?? '');
    if ($codigo !== '') {
        $categoriasMaestras[$codigo] = trim($fila['G_NOM'] ?? '') ?: $codigo;
    }
}

$grupos = [];
foreach (cat_leer_dbf($baseDir . 'grupos.DBF') as $fila) {
    $idGrupo = trim($fila['IDGRUPO'] ?? '');
    if ($idGrupo !== '') $grupos[$idGrupo] = $fila;
}

$activos = [];
$resumen = [
    'activos_fijos' => 0,
    'relacion_por_idgrupo' => 0,
    'respaldo_por_g_cod' => 0,
    'sin_relacion' => 0,
    'codigo_corregido' => 0,
];

foreach (cat_leer_dbf($baseDir . 'activos.DBF') as $fila) {
    if (trim($fila['TIPO_BIEN'] ?? '') !== 'AF') continue;
    $codigoActivo = trim($fila['CODIGO'] ?? '');
    if ($codigoActivo === '') continue;

    $resumen['activos_fijos']++;
    $idGrupo = trim($fila['IDGRUPO'] ?? '');
    $codigoOriginal = trim($fila['G_COD'] ?? '');
    $codigoCorrecto = '';
    $criterio = '';

    if ($idGrupo !== '' && isset($grupos[$idGrupo])) {
        $codigoCorrecto = trim($grupos[$idGrupo]['IDGACTI'] ?? '');
        $criterio = 'IDGRUPO';
        $resumen['relacion_por_idgrupo']++;
    } elseif ($codigoOriginal !== '' && isset($categoriasMaestras[$codigoOriginal])) {
        $codigoCorrecto = $codigoOriginal;
        $criterio = 'G_COD';
        $resumen['respaldo_por_g_cod']++;
    }

    if ($codigoCorrecto === '' || !isset($categoriasMaestras[$codigoCorrecto])) {
        $resumen['sin_relacion']++;
        continue;
    }
    if ($codigoCorrecto !== $codigoOriginal) $resumen['codigo_corregido']++;

    $activos[] = [
        'secuencial' => 'AF-' . $codigoActivo,
        'codigo_activo' => $codigoActivo,
        'codigo_categoria' => $codigoCorrecto,
        'nombre_categoria' => $categoriasMaestras[$codigoCorrecto],
        'criterio' => $criterio,
    ];
}

echo json_encode($resumen, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
if (!$ejecutar) {
    exit("Vista previa completada. Use --execute para aplicar la correccion.\n");
}

require_once dirname(__DIR__, 2) . '/db/connection.php';
$db = getPDOConnection();
$db->beginTransaction();

try {
    $buscarCategoria = $db->prepare("SELECT TOP 1 id, nombre FROM inv_categorias WHERE codigo = :codigo ORDER BY id");
    $crearCategoria = $db->prepare(
        "INSERT INTO inv_categorias(nombre, codigo, extra)
         OUTPUT INSERTED.id
         VALUES(:nombre, :codigo, :extra)"
    );
    $renombrarCategoria = $db->prepare(
        "UPDATE inv_categorias SET nombre = :nombre, extra = :extra WHERE id = :id"
    );
    $actualizarActivo = $db->prepare(
        "UPDATE inv_inventario
         SET categoria_id = :categoria_id, tipo_bien = 'AF'
         WHERE secuencial = :secuencial"
    );

    $idsCategoria = [];
    $categoriasCreadas = 0;
    $categoriasRenombradas = 0;
    $codigosUsados = [];
    foreach ($activos as $activo) $codigosUsados[$activo['codigo_categoria']] = true;

    // Renombrar tambien las categorias padre que ya existen para que nunca
    // vuelvan a mostrarse como "Activos fijos + codigo". Solo se crean las
    // categorias finales que realmente son utilizadas por algun activo.
    foreach ($categoriasMaestras as $codigo => $nombreMaestro) {

        // El codigo se conserva al final para distinguir nombres contables
        // repetidos (por ejemplo, bienes propios y concesionados).
        $nombreVisible = $nombreMaestro . ' (' . $codigo . ')';
        $extra = 'Categoria validada desde GACTI.DBF y GRUPOS.DBF';
        $buscarCategoria->execute([':codigo' => $codigo]);
        $categoria = $buscarCategoria->fetch();

        if ($categoria) {
            $idsCategoria[$codigo] = (int)$categoria['id'];
            if ((string)$categoria['nombre'] !== $nombreVisible) {
                $renombrarCategoria->execute([
                    ':nombre' => $nombreVisible,
                    ':extra' => $extra,
                    ':id' => (int)$categoria['id'],
                ]);
                $categoriasRenombradas++;
            }
        } elseif (isset($codigosUsados[$codigo])) {
            $crearCategoria->execute([
                ':nombre' => $nombreVisible,
                ':codigo' => $codigo,
                ':extra' => $extra,
            ]);
            $idsCategoria[$codigo] = (int)$crearCategoria->fetchColumn();
            $categoriasCreadas++;
        }
    }

    $activosActualizados = 0;
    $activosNoEncontrados = 0;
    foreach ($activos as $activo) {
        $actualizarActivo->execute([
            ':categoria_id' => $idsCategoria[$activo['codigo_categoria']],
            ':secuencial' => $activo['secuencial'],
        ]);
        if ($actualizarActivo->rowCount() > 0) $activosActualizados++;
        else $activosNoEncontrados++;
    }

    $db->commit();
    $categoriasGenericas = (int)$db->query(
        "SELECT COUNT(*) FROM inv_categorias
         WHERE codigo LIKE '1.4.%' AND nombre LIKE 'Activos fijos %'"
    )->fetchColumn();
    $totalAfBase = (int)$db->query(
        "SELECT COUNT(*) FROM inv_inventario WHERE tipo_bien = 'AF'"
    )->fetchColumn();
    $productosEnGrupoPadre = (int)$db->query(
        "SELECT COUNT(*)
         FROM inv_productos p
         JOIN inv_categorias c ON c.id = p.grupo_id
         WHERE EXISTS (
             SELECT 1 FROM inv_categorias hija
             WHERE hija.id <> c.id AND hija.codigo LIKE c.codigo + '%'
         )"
    )->fetchColumn();
    $vinculosInconsistentes = (int)$db->query(
        "SELECT COUNT(*)
         FROM inv_inventario i
         JOIN inv_productos p ON p.id = i.producto_id
         WHERE i.categoria_id <> p.grupo_id"
    )->fetchColumn();
    echo json_encode([
        'categorias_creadas' => $categoriasCreadas,
        'categorias_renombradas' => $categoriasRenombradas,
        'activos_actualizados' => $activosActualizados,
        'activos_no_encontrados' => $activosNoEncontrados,
        'categorias_genericas_restantes' => $categoriasGenericas,
        'total_activos_fijos_en_bd' => $totalAfBase,
        'productos_en_grupo_padre' => $productosEnGrupoPadre,
        'vinculos_producto_inventario_inconsistentes' => $vinculosInconsistentes,
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
} catch (Throwable $e) {
    if ($db->inTransaction()) $db->rollBack();
    fwrite(STDERR, "Correccion cancelada: {$e->getMessage()}\n");
    exit(1);
}
