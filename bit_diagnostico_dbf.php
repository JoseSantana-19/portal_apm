<?php
/**
 * Diagnóstico del archivo rolmaes.DBF. Abrir en el navegador para verificar ruta, extensión y lectura.
 * Eliminar o restringir acceso en producción.
 */
header('Content-Type: text/html; charset=utf-8');
require_once __DIR__ . '/apis/bit_lector_rolmaes_dbf.php';

echo '<h1>Diagnóstico rolmaes.DBF</h1><pre>';

$ruta = _dbf_ruta_rolmaes(null);
echo "Ruta del archivo: " . ($ruta ? $ruta : '(no encontrado)') . "\n";
echo "Extensión dbase cargada: " . (extension_loaded('dbase') ? 'Sí' : 'No') . "\n";

if (!$ruta || !extension_loaded('dbase')) {
    echo "\n</pre><p>Coloque rolmaes.DBF en la carpeta <strong>dbf/</strong> del proyecto y habilite la extensión dbase en php.ini.</p>";
    exit;
}

$db = @dbase_open($ruta, 0);
if ($db === false) {
    echo "Error al abrir el archivo con dbase_open().\n</pre>";
    exit;
}

$numRecords = dbase_numrecords($db);
echo "Total de registros en el DBF: $numRecords\n";

$map = null;
for ($i = 1; $i <= $numRecords; $i++) {
    $row = dbase_get_record_with_names($db, $i);
    if (is_array($row) && empty($row['deleted'])) {
        $map = _dbf_detectar_campos($row);
        echo "\nColumnas detectadas (primer registro no borrado):\n";
        echo "  cedula:       " . ($map['cedula'] ? "'{$map['cedula']}'" : 'no') . "\n";
        echo "  nombre:       " . ($map['nombre'] ? "'{$map['nombre']}'" : 'no') . "\n";
        echo "  cargo:        " . ($map['cargo'] ? "'{$map['cargo']}'" : 'no') . "\n";
        echo "  fecha_salida: " . ($map['fecha_salida'] ? "'{$map['fecha_salida']}'" : 'no') . "\n";
        break;
    }
}
dbase_close($db);

$activos = obtener_funcionarios_activos_dbf(null);
echo "\nFuncionarios activos (sin fecha de salida real) leídos: " . count($activos) . "\n";
if (count($activos) > 0) {
    echo "Primeros 5:\n";
    foreach (array_slice($activos, 0, 5) as $i => $f) {
        echo "  " . ($i + 1) . ". {$f['nombre']} - {$f['cargo']} (cedula: {$f['cedula']})\n";
    }
}

echo "\n</pre><p><a href=\"bit_registrar_visita.php\">Ir a Registrar visita</a></p>";
