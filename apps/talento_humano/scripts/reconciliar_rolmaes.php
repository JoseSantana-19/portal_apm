<?php
declare(strict_types=1);

/**
 * Concilia el respaldo rolmaes.DBF.csv con empleados ya existentes.
 *
 * Seguridad:
 * - El modo predeterminado es simulación.
 * - No inserta ni elimina empleados y nunca modifica empleado_id.
 * - Solo corrige nombres con señales verificables de codificación dañada y
 *   completa teléfono, sueldo, IESS o código de empleado cuando están vacíos.
 * - Requiere el respaldo lógico creado por migracion_calidad_busqueda_2026.sql
 *   antes de aceptar --apply.
 *
 * Uso:
 * php scripts/reconciliar_rolmaes.php --source="C:/ruta/rolmaes.DBF.csv"
 * php scripts/reconciliar_rolmaes.php --source="C:/ruta/rolmaes.DBF.csv" --apply
 */

define('ROOT', dirname(__DIR__));
require ROOT . '/core/Config.php';
require ROOT . '/core/Database.php';

$opciones = getopt('', ['source:', 'apply']);
$fuente = (string)($opciones['source'] ?? '');
$aplicar = array_key_exists('apply', $opciones);

if ($fuente === '' || !is_file($fuente) || !is_readable($fuente)) {
    fwrite(STDERR, "Indique un CSV legible con --source=\"ruta/rolmaes.DBF.csv\".\n");
    exit(2);
}

function repararCaracteresLegados(string $valor): string
{
    return str_replace(
        ["\xC3\x90", "\xC3\x8B", "\xE2\x95\x94", "\xE2\x94\xB4", "\xE2\x95\x90"],
        ["\xC3\x91", "\xC3\x93", "\xC3\x89", "\xC3\x81", "\xC3\x8D"],
        $valor
    );
}

function repararMojibake(string $valor): string
{
    $valor = trim(preg_replace('/\s+/u', ' ', $valor) ?? $valor);
    if (str_contains($valor, "\xC3\x83") || str_contains($valor, "\xC3\x82")) {
        $convertido = mb_convert_encoding($valor, 'Windows-1252', 'UTF-8');
        if ($convertido !== '' && mb_check_encoding($convertido, 'UTF-8')) {
            $valor = $convertido;
        }
    }
    return repararCaracteresLegados($valor);
}

function tieneDanoVerificable(string $bd, string $fuente): bool
{
    foreach (["\xC3\x83", "\xC3\x82"] as $marca) {
        if (str_contains($bd, $marca)) return true;
    }
    foreach (["\xC3\x90", "\xC3\x8B", "\xE2\x95\x94", "\xE2\x94\xB4", "\xE2\x95\x90"] as $marca) {
        if (str_contains($fuente, $marca)) return true;
    }
    return false;
}

function dividirNombreLegado(string $nombre): array
{
    $partes = preg_split('/\s+/u', trim(repararCaracteresLegados($nombre))) ?: [];
    $corte = min(2, count($partes));
    return [implode(' ', array_slice($partes, 0, $corte)), implode(' ', array_slice($partes, $corte))];
}

function decimalCsv(string $valor): ?string
{
    $normalizado = str_replace(',', '.', trim($valor));
    if ($normalizado === '' || !is_numeric($normalizado)) return null;
    return number_format((float)$normalizado, 2, '.', '');
}

$archivo = fopen($fuente, 'rb');
if (!$archivo) throw new RuntimeException('No fue posible abrir el CSV.');
$cabecera = fgetcsv($archivo, 0, ';');
if (!$cabecera || count($cabecera) !== 90) throw new RuntimeException('El CSV no contiene las 90 columnas esperadas.');
$cabecera[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string)$cabecera[0]);

$registros = [];
$filas = 0;
$invalidas = 0;
$duplicadas = 0;
while (($fila = fgetcsv($archivo, 0, ';')) !== false) {
    $filas++;
    if (count($fila) !== count($cabecera)) { $invalidas++; continue; }
    $dato = array_combine($cabecera, $fila);
    $cedula = preg_replace('/\D/', '', (string)($dato['NUM_CEDULA'] ?? ''));
    if (strlen($cedula) < 8 || strlen($cedula) > 13 || trim((string)($dato['NOMBRE'] ?? '')) === '') {
        $invalidas++;
        continue;
    }
    if (isset($registros[$cedula])) $duplicadas++;
    $sinSalida = trim((string)($dato['FEC_SALIDA'] ?? '')) === '';
    if (!isset($registros[$cedula]) || $sinSalida) $registros[$cedula] = $dato;
}
fclose($archivo);

$db = Conexion::conectar();
$empleados = $db->query(
    'SELECT empleado_id,identificacion,apellidos,nombres,telefono_movil,sueldo_rmu,num_iess,codigo_iess,cod_emplea '
  . 'FROM dbo.th_empleados'
)->fetchAll(PDO::FETCH_ASSOC);

$cambios = [];
foreach ($empleados as $empleado) {
    $cedula = preg_replace('/\D/', '', (string)$empleado['identificacion']);
    if (!isset($registros[$cedula])) continue;
    $origen = $registros[$cedula];
    [$apellidosFuente, $nombresFuente] = dividirNombreLegado((string)$origen['NOMBRE']);
    $nombreBd = trim((string)$empleado['apellidos'] . ' ' . (string)$empleado['nombres']);
    $corregirNombre = tieneDanoVerificable($nombreBd, (string)$origen['NOMBRE']);

    $nuevo = [
        'apellidos' => $corregirNombre ? $apellidosFuente : repararMojibake((string)$empleado['apellidos']),
        'nombres' => $corregirNombre ? $nombresFuente : repararMojibake((string)$empleado['nombres']),
        'telefono' => trim((string)$empleado['telefono_movil']) !== ''
            ? (string)$empleado['telefono_movil'] : mb_substr(trim((string)$origen['TELEFONO']), 0, 20),
        'sueldo' => (float)$empleado['sueldo_rmu'] > 0
            ? number_format((float)$empleado['sueldo_rmu'], 2, '.', '') : decimalCsv((string)$origen['SUELDO']),
        'num_iess' => trim((string)$empleado['num_iess']) !== ''
            ? (string)$empleado['num_iess'] : mb_substr(trim((string)$origen['NUM_IESS']), 0, 30),
        'codigo_iess' => trim((string)$empleado['codigo_iess']) !== ''
            ? (string)$empleado['codigo_iess'] : mb_substr(trim((string)$origen['NUM_IESS']), 0, 30),
        'cod_emplea' => trim((string)$empleado['cod_emplea']) !== ''
            ? (string)$empleado['cod_emplea'] : mb_substr(trim((string)$origen['COD_EMPLEA']), 0, 20),
    ];
    $antes = [
        'apellidos'=>(string)$empleado['apellidos'],'nombres'=>(string)$empleado['nombres'],
        'telefono'=>(string)$empleado['telefono_movil'],
        'sueldo'=>number_format((float)$empleado['sueldo_rmu'],2,'.',''),
        'num_iess'=>(string)$empleado['num_iess'],'codigo_iess'=>(string)$empleado['codigo_iess'],
        'cod_emplea'=>(string)$empleado['cod_emplea'],
    ];
    if ($nuevo !== $antes) {
        $cambios[] = ['id'=>(int)$empleado['empleado_id'],'cedula'=>$cedula,'antes'=>$antes,'despues'=>$nuevo];
    }
}

$resultado = [
    'modo' => $aplicar ? 'APLICAR' : 'SIMULACION',
    'sha256' => hash_file('sha256', $fuente),
    'filas_csv' => $filas,
    'filas_invalidas' => $invalidas,
    'duplicados_descartados' => $duplicadas,
    'cedulas_unicas_validas' => count($registros),
    'empleados_a_corregir' => count($cambios),
    'muestra' => array_slice(array_map(static fn(array $c): array => [
        'cedula'=>$c['cedula'],
        'antes'=>trim($c['antes']['apellidos'].' '.$c['antes']['nombres']),
        'despues'=>trim($c['despues']['apellidos'].' '.$c['despues']['nombres']),
    ], $cambios), 0, 12),
];

if (!$aplicar) {
    echo json_encode($resultado, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT), PHP_EOL;
    exit(0);
}

$respaldo = (int)$db->query("SELECT CASE WHEN OBJECT_ID('dbo.th_respaldo_empleados_calidad_20260729','U') IS NULL THEN 0 ELSE 1 END")->fetchColumn();
if ($respaldo !== 1) throw new RuntimeException('Falta el respaldo lógico requerido. Ejecute primero migracion_calidad_busqueda_2026.sql.');

$db->beginTransaction();
try {
    $actualizar = $db->prepare(
        'EXEC dbo.sp_th_reconciliar_empleado_rolmaes :id,:apellidos,:nombres,:telefono,:sueldo,:num_iess,:codigo_iess,:cod_emplea'
    );
    foreach ($cambios as $cambio) {
        $d = $cambio['despues'];
        $actualizar->execute([
            ':apellidos'=>$d['apellidos'],':nombres'=>$d['nombres'],':telefono'=>$d['telefono'] ?: null,
            ':sueldo'=>$d['sueldo'],':num_iess'=>$d['num_iess'] ?: null,':codigo_iess'=>$d['codigo_iess'] ?: null,
            ':cod_emplea'=>$d['cod_emplea'] ?: null,':id'=>$cambio['id'],
        ]);
        while ($actualizar->nextRowset()) {}
    }
    $auditoria = $db->prepare("EXEC dbo.sp_th_registrar_auditoria 'MIGRACION','Directorio de Personal','RECONCILIAR_ROLMAES',:detalle,'127.0.0.1'");
    $auditoria->execute([':detalle'=>'CSV conciliado por cédula. Empleados corregidos: '.count($cambios).'. Sin renumerar claves primarias.']);
    while ($auditoria->nextRowset()) {}
    $db->commit();
    $resultado['aplicados'] = count($cambios);
} catch (Throwable $e) {
    if ($db->inTransaction()) $db->rollBack();
    throw $e;
}

echo json_encode($resultado, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT), PHP_EOL;
