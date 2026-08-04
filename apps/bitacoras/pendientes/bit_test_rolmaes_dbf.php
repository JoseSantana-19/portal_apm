<?php
/**
 * Script de prueba: lectura directa del archivo rolmaes.DBF
 * Usa dbase_open(), dbase_numrecords(), dbase_get_record_with_names()
 * para verificar que el archivo se lee y mostrar nombres de columnas y primeros registros.
 * Ejecutar desde el navegador: http://localhost/portuaria_demo/test_rolmaes_dbf.php
 * ELIMINAR o restringir en producción.
 */
header('Content-Type: text/html; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Prueba de lectura: rolmaes.DBF</h1>\n<pre>\n";

// --- 1. Ruta del archivo (misma lógica que el lector) ---
$bases = [
    __DIR__ . DIRECTORY_SEPARATOR . 'dbf' . DIRECTORY_SEPARATOR,
    (function_exists('getcwd') ? getcwd() . DIRECTORY_SEPARATOR : '') . 'dbf' . DIRECTORY_SEPARATOR,
];
$candidatos = ['rolmaes.DBF', 'rolmaes.dbf', 'ROLMAES.DBF'];
$rutaDbf = null;
foreach ($bases as $base) {
    if ($base === '' || !is_dir($base)) continue;
    foreach ($candidatos as $nombre) {
        $ruta = $base . $nombre;
        if (is_file($ruta)) {
            $rutaDbf = $ruta;
            break 2;
        }
    }
}

echo "=== 1. UBICACIÓN DEL ARCHIVO ===\n";
if ($rutaDbf === null) {
    echo "ERROR: No se encontró rolmaes.DBF.\n";
    echo "Carpetas probadas:\n";
    foreach ($bases as $b) echo "  - " . ($b ?: '(getcwd vacío)') . "\n";
    echo "\nColoque el archivo en: " . __DIR__ . DIRECTORY_SEPARATOR . "dbf" . DIRECTORY_SEPARATOR . "rolmaes.DBF\n";
    echo "</pre>";
    exit;
}
echo "Archivo encontrado: " . $rutaDbf . "\n\n";

// --- 2. Extensión dbase ---
echo "=== 2. EXTENSIÓN PHP dbase ===\n";
if (!extension_loaded('dbase')) {
    echo "ERROR: La extensión 'dbase' no está cargada.\n";
    echo "PHP version: " . PHP_VERSION . " | Thread Safety: " . (ZEND_THREAD_SAFE ? 'Sí (TS)' : 'No (NTS)') . "\n";
    echo "Habilítela en php.ini (extension=dbase) y reinicie el servidor.\n";
    echo "Instrucciones: vea el archivo dbf/INSTALAR_EXTENSION_DBASE.md\n</pre>";
    exit;
}
echo "Extensión dbase: cargada.\n\n";

// --- 3. Abrir archivo y total de registros ---
echo "=== 3. APERTURA Y TOTAL DE REGISTROS ===\n";
$db = @dbase_open($rutaDbf, 0);
if ($db === false) {
    echo "ERROR: dbase_open() falló. No se pudo abrir el archivo.\n";
    if (function_exists('dbase_get_header_info')) {
        echo " (Posible formato no soportado o archivo corrupto)\n";
    }
    echo "</pre>";
    exit;
}
$numRecords = dbase_numrecords($db);
echo "dbase_open(): OK\n";
echo "dbase_numrecords(): $numRecords registros\n\n";

// --- 4. Nombres de columnas (del primer registro no borrado) ---
echo "=== 4. NOMBRES DE COLUMNAS (claves del primer registro) ===\n";
$primerRow = null;
$numMostrar = 10;
for ($i = 1; $i <= min($numRecords, 50); $i++) {
    $row = dbase_get_record_with_names($db, $i);
    if (!is_array($row)) continue;
    $primerRow = $row;
    if (!empty($row['deleted'])) continue;
    break;
}
if ($primerRow === null) {
    echo "No se pudo leer ningún registro.\n";
    dbase_close($db);
    echo "</pre>";
    exit;
}
$columnas = array_keys($primerRow);
echo "Columnas encontradas (" . count($columnas) . "):\n";
foreach ($columnas as $idx => $col) {
    $valor = isset($primerRow[$col]) ? $primerRow[$col] : '';
    $tipo = gettype($valor);
    $preview = is_string($valor) ? '"' . substr(trim($valor), 0, 40) . '"' : json_encode($valor);
    echo "  [" . ($idx + 1) . "] '" . $col . "' => " . $preview . " (tipo: $tipo)\n";
}
echo "\n";

// --- 5. Primeros 10 registros (valores crudos) ---
echo "=== 5. PRIMEROS 10 REGISTROS (valores crudos) ===\n";
$contador = 0;
for ($i = 1; $i <= $numRecords && $contador < $numMostrar; $i++) {
    $row = dbase_get_record_with_names($db, $i);
    if (!is_array($row)) continue;
    if (!empty($row['deleted'])) continue;
    $contador++;
    echo "--- Registro #$i (registro lógico $contador) ---\n";
    foreach ($row as $key => $value) {
        if ($key === 'deleted') continue;
        $raw = $value;
        if (is_string($value)) {
            $trimmed = trim($value);
            $preview = $trimmed === '' ? '(vacío)' : "'" . $trimmed . "'";
        } else {
            $preview = json_encode($value);
        }
        echo "  $key = $preview\n";
    }
    echo "\n";
}

// --- 6. Columnas que parecen "fecha de salida" y valores únicos en primeros 20 ---
echo "=== 6. POSIBLE CAMPO FECHA DE SALIDA (valores en primeros 20 registros) ===\n";
$clavesPosiblesFecha = [];
foreach ($primerRow as $k => $v) {
    if ($k === 'deleted') continue;
    $u = strtoupper(trim($k));
    if (strpos($u, 'SALIDA') !== false || strpos($u, 'BAJA') !== false || strpos($u, 'FEC') !== false && strpos($u, 'SAL') !== false) {
        $clavesPosiblesFecha[] = $k;
    }
}
if (count($clavesPosiblesFecha) === 0) {
    echo "No se encontró ninguna columna con nombre parecido a FEC_SALIDA/FECHA_SALIDA/BAJA.\n";
    echo "Lista de todas las columnas por si el nombre es distinto:\n";
    foreach ($columnas as $c) {
        if ($c !== 'deleted') echo "  - $c\n";
    }
} else {
    $valoresVistos = [];
    for ($idx = 0; $idx < count($clavesPosiblesFecha); $idx++) {
        $valoresVistos[$clavesPosiblesFecha[$idx]] = [];
    }
    for ($i = 1; $i <= min($numRecords, 20); $i++) {
        $row = dbase_get_record_with_names($db, $i);
        if (!is_array($row) || !empty($row['deleted'])) continue;
        foreach ($clavesPosiblesFecha as $clave) {
            $val = isset($row[$clave]) ? trim((string)$row[$clave]) : '';
            $key = $val === '' ? '(vacío)' : $val;
            if (!isset($valoresVistos[$clave][$key])) $valoresVistos[$clave][$key] = 0;
            $valoresVistos[$clave][$key]++;
        }
    }
    foreach ($clavesPosiblesFecha as $clave) {
        echo "Columna: '$clave'\n  Valores distintos en primeros 20 registros:\n";
        foreach ($valoresVistos[$clave] as $val => $count) {
            echo "    - " . (strlen($val) > 60 ? substr($val, 0, 60) . '...' : $val) . " (aparece $count veces)\n";
        }
        echo "\n";
    }
}

dbase_close($db);

// --- 7. Prueba con el lector del sistema ---
echo "=== 7. PRUEBA CON EL LECTOR DEL SISTEMA (obtener_funcionarios_activos_dbf) ===\n";
require_once __DIR__ . '/apis/bit_lector_rolmaes_dbf.php';
$activos = obtener_funcionarios_activos_dbf(null);
echo "Funcionarios considerados ACTIVOS (sin fecha de salida): " . count($activos) . "\n";
if (count($activos) > 0) {
    echo "Primeros 5:\n";
    foreach (array_slice($activos, 0, 5) as $j => $f) {
        echo "  " . ($j + 1) . ". cedula={$f['cedula']} | nombre={$f['nombre']} | cargo={$f['cargo']}\n";
    }
} else {
    echo "Si sale 0, revise el filtro de fecha de salida (valores vacíos, ####, 0000-00-00, etc.).\n";
}

// --- 8. Verificación: inserción y lista para el select ---
echo "\n=== 8. VERIFICACIÓN: SINCRONIZACIÓN CON SQL SERVER Y LISTA PARA EL SELECT ===\n";
$conn = null;
if (is_file(__DIR__ . '/conexion/conexion.php')) {
    try {
        require_once __DIR__ . '/conexion/conexion.php';
        if (!empty($conn)) {
            require_once __DIR__ . '/apis/bit_sincronizar_funcionarios_dbf.php';
            $listaSelect = obtener_funcionarios_activos_sincronizados($conn);
            echo "Cantidad de funcionarios que vería el select 'Funcionario que lo solicita': " . count($listaSelect) . "\n";
            if (count($listaSelect) > 0) {
                echo "Primeros 3 que aparecerían en el dropdown:\n";
                foreach (array_slice($listaSelect, 0, 3) as $j => $f) {
                    $ced = isset($f['cedula']) && $f['cedula'] !== '' ? " | cedula={$f['cedula']}" : '';
                    echo "  " . ($j + 1) . ". id={$f['id_funcionario']} | {$f['nombre']} - {$f['cargo']}{$ced}\n";
                }
            } else {
                echo "El select quedaría vacío (solo la opción 'Buscar funcionario...').\n";
                echo "Posibles causas: tabla funcionarios sin columna cedula, o ningún registro del DBF pasó el filtro.\n";
            }
        } else {
            echo "No se pudo conectar a SQL Server (conexión no disponible).\n";
        }
    } catch (Throwable $e) {
        echo "Error al verificar sincronización: " . $e->getMessage() . "\n";
    }
} else {
    echo "No se encontró conexion/conexion.php; se omite la verificación con la BD.\n";
}

echo "\n</pre>";
echo "<p><a href=\"bit_registrar_visita.php\">Ir a Registrar visita</a> | <a href=\"bit_diagnostico_dbf.php\">Diagnóstico DBF</a></p>";
