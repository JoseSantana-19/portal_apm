<?php
declare(strict_types=1);

require dirname(__DIR__) . '/config/globals.php';

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Este importador solo puede ejecutarse desde consola.\n");
    exit(1);
}

$aplicar = in_array('--apply', $argv, true);
$verificar = in_array('--verify', $argv, true);
$rutaArgumento = null;
foreach (array_slice($argv, 1) as $argumento) {
    if (!in_array($argumento, ['--apply', '--verify'], true)) $rutaArgumento = $argumento;
}
$rutaDbf = $rutaArgumento ?: dirname(rtrim(ROOT_PATH, '/\\')) . DIRECTORY_SEPARATOR . 'bases' . DIRECTORY_SEPARATOR . 'provee.DBF';

/** @return array<int,array<string,string>> */
function inv_leer_proveedores_dbf(string $ruta): array
{
    if (!is_file($ruta)) throw new RuntimeException("No existe el archivo DBF: {$ruta}");
    $archivo = fopen($ruta, 'rb');
    if (!$archivo) throw new RuntimeException('No fue posible abrir el archivo DBF.');

    try {
        $cabecera = fread($archivo, 32);
        if (strlen($cabecera) !== 32) throw new RuntimeException('La cabecera del DBF está incompleta.');
        $meta = unpack('Vregistros/vlongitud_cabecera/vlongitud_registro', substr($cabecera, 4, 8));
        $campos = [];
        while (ftell($archivo) < (int)$meta['longitud_cabecera']) {
            $descriptor = fread($archivo, 32);
            if ($descriptor === '' || ord($descriptor[0]) === 0x0D) break;
            $nombre = strtoupper(rtrim(substr($descriptor, 0, 11), "\0 "));
            $longitud = ord($descriptor[16]);
            if ($nombre === '' || $longitud <= 0) break;
            $campos[] = ['nombre' => $nombre, 'longitud' => $longitud];
        }

        $esperados = ['PCOD','PNOM','PREP','PDIR','PTEL1','PTEL2','PFAX','PRUC','PREF','PCIUDAD','PCOD_OLD','GPCOD','EMAIL'];
        if (array_column($campos, 'nombre') !== $esperados) {
            throw new RuntimeException('La estructura de provee.DBF no coincide con la versión esperada.');
        }

        fseek($archivo, (int)$meta['longitud_cabecera']);
        $registros = [];
        for ($indice = 0; $indice < (int)$meta['registros']; $indice++) {
            $registro = fread($archivo, (int)$meta['longitud_registro']);
            if (strlen($registro) < (int)$meta['longitud_registro']) break;
            if ($registro[0] === '*') continue;
            $posicion = 1;
            $fila = [];
            foreach ($campos as $campo) {
                $valor = rtrim(substr($registro, $posicion, $campo['longitud']), " \0");
                $posicion += $campo['longitud'];
                $fila[$campo['nombre']] = mb_convert_encoding($valor, 'UTF-8', 'Windows-1252');
            }
            $registros[] = $fila;
        }
        return $registros;
    } finally {
        fclose($archivo);
    }
}

try {
    $proveedores = inv_leer_proveedores_dbf($rutaDbf);
    $resumen = [
        'archivo' => $rutaDbf,
        'registros_activos' => count($proveedores),
        'con_ruc' => count(array_filter($proveedores, static fn(array $fila): bool => trim($fila['PRUC']) !== '')),
        'sin_nombre' => count(array_filter($proveedores, static fn(array $fila): bool => trim($fila['PNOM']) === '')),
        'modo' => $aplicar ? 'APLICAR' : ($verificar ? 'VERIFICAR' : 'SOLO_LECTURA'),
    ];

    if (!$aplicar && !$verificar) {
        echo json_encode($resumen, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
        exit(0);
    }

    require ROOT_PATH . 'core/Database.php';
    require_once ROOT_PATH . 'modules/Bitacoras/models/BitacoraModel.php';
    $db = Database::getInstance()->getConnection();

    if ($verificar) {
        $filasDb = $db->query(
            "SELECT codigo, nombre, representante, direccion, telefono1, telefono2, fax, ruc, referencia,
                    ciudad, email, codigo_anterior, grupo_codigo_origen, extra
             FROM inv_proveedores WHERE origen_datos = 'DBF_PROVEE'"
        )->fetchAll();
        $porCodigo = [];
        foreach ($filasDb as $filaDb) $porCodigo[(string)$filaDb['codigo']] = $filaDb;
        $diferencias = [];
        $mapa = [
            'PNOM'=>'nombre','PREP'=>'representante','PDIR'=>'direccion','PTEL1'=>'telefono1','PTEL2'=>'telefono2',
            'PFAX'=>'fax','PRUC'=>'ruc','PREF'=>'referencia','PCIUDAD'=>'ciudad','EMAIL'=>'email',
            'PCOD_OLD'=>'codigo_anterior','GPCOD'=>'grupo_codigo_origen',
        ];
        foreach ($proveedores as $fila) {
            $codigo = $fila['PCOD'];
            if (!isset($porCodigo[$codigo])) { $diferencias[] = $codigo . ': no existe en la base'; continue; }
            foreach ($mapa as $campoDbf => $campoDb) {
                if ((string)($porCodigo[$codigo][$campoDb] ?? '') !== (string)$fila[$campoDbf]) {
                    $diferencias[] = $codigo . ': diferencia en ' . $campoDbf;
                }
            }
            $copiaOrigen = json_decode((string)($porCodigo[$codigo]['extra'] ?? ''), true);
            if (!is_array($copiaOrigen) || $copiaOrigen !== $fila) $diferencias[] = $codigo . ': copia de origen incompleta';
            if (count($diferencias) >= 20) break;
        }
        $resumen['registros_en_base'] = count($filasDb);
        $resumen['diferencias'] = $diferencias;
        $resumen['resultado'] = !$diferencias && count($filasDb) === count($proveedores) ? 'OK' : 'ERROR';
        echo json_encode($resumen, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
        exit($resumen['resultado'] === 'OK' ? 0 : 1);
    }

    foreach (['codigo_anterior', 'grupo_codigo_origen', 'origen_datos', 'fecha_importacion'] as $columna) {
        $stmtColumna = $db->prepare(
            "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'inv_proveedores' AND COLUMN_NAME = :columna"
        );
        $stmtColumna->execute([':columna' => $columna]);
        if ((int)$stmtColumna->fetchColumn() === 0) {
            throw new RuntimeException('Ejecute primero la migración inv_20260824_proveedores_historicos_dbf.sql.');
        }
    }

    $buscar = $db->prepare('SELECT id FROM inv_proveedores WHERE codigo = :codigo');
    $insertar = $db->prepare(
        "INSERT INTO inv_proveedores
            (codigo, nombre, representante, direccion, telefono1, telefono2, fax, ruc, referencia, ciudad, email,
             codigo_anterior, grupo_codigo_origen, origen_datos, fecha_importacion, extra)
         VALUES
            (:codigo, :nombre, :representante, :direccion, :telefono1, :telefono2, :fax, :ruc, :referencia, :ciudad, :email,
             :codigo_anterior, :grupo_codigo, 'DBF_PROVEE', SYSDATETIME(), :extra)"
    );
    $actualizar = $db->prepare(
        "UPDATE inv_proveedores SET
            nombre=:nombre, representante=:representante, direccion=:direccion, telefono1=:telefono1,
            telefono2=:telefono2, fax=:fax, ruc=:ruc, referencia=:referencia, ciudad=:ciudad, email=:email,
            codigo_anterior=:codigo_anterior, grupo_codigo_origen=:grupo_codigo,
            origen_datos='DBF_PROVEE', fecha_importacion=SYSDATETIME(), extra=:extra
         WHERE id=:id"
    );

    $creados = 0;
    $actualizados = 0;
    $db->beginTransaction();
    foreach ($proveedores as $fila) {
        $parametros = [
            ':codigo' => $fila['PCOD'], ':nombre' => $fila['PNOM'], ':representante' => $fila['PREP'],
            ':direccion' => $fila['PDIR'], ':telefono1' => $fila['PTEL1'], ':telefono2' => $fila['PTEL2'],
            ':fax' => $fila['PFAX'], ':ruc' => $fila['PRUC'], ':referencia' => $fila['PREF'],
            ':ciudad' => $fila['PCIUDAD'], ':email' => $fila['EMAIL'], ':codigo_anterior' => $fila['PCOD_OLD'],
            ':grupo_codigo' => $fila['GPCOD'],
            ':extra' => json_encode($fila, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ];
        $buscar->execute([':codigo' => $fila['PCOD']]);
        $id = (int)$buscar->fetchColumn();
        if ($id > 0) {
            $parametrosActualizar = $parametros;
            unset($parametrosActualizar[':codigo']);
            $parametrosActualizar[':id'] = $id;
            $actualizar->execute($parametrosActualizar);
            $actualizados++;
        } else {
            $insertar->execute($parametros);
            $creados++;
        }
    }
    $db->commit();
    (new InvBitacora())->registrar('IMPORTAR', 'inv', "Proveedores importados desde provee.DBF: {$creados} creados y {$actualizados} actualizados.");

    $resumen['creados'] = $creados;
    $resumen['actualizados'] = $actualizados;
    $resumen['total_tabla'] = (int)$db->query('SELECT COUNT(*) FROM inv_proveedores')->fetchColumn();
    echo json_encode($resumen, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
} catch (Throwable $e) {
    if (isset($db) && $db->inTransaction()) $db->rollBack();
    fwrite(STDERR, $e->getMessage() . PHP_EOL);
    exit(1);
}
