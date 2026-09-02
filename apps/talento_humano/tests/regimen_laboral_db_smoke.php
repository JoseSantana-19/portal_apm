<?php
declare(strict_types=1);

define('ROOT', dirname(__DIR__));
require ROOT.'/core/Config.php';
require ROOT.'/core/Database.php';

$db = Conexion::conectar();
$fail = [];
$objects = [
    'th_secuencias_documentos' => 'U',
    'sp_th_asignar_regimen_empleado' => 'P',
    'tr_th_acciones_asignar_serie' => 'TR',
    'vw_th_situacion_laboral_efectiva' => 'V',
];
foreach ($objects as $name => $type) {
    $statement = $db->prepare('SELECT OBJECT_ID(:name,:type)');
    $statement->execute([':name'=>'dbo.'.$name, ':type'=>$type]);
    if (!$statement->fetchColumn()) $fail[] = 'Falta '.$name.'.';
}

if ($db->query("SELECT COL_LENGTH('dbo.th_empleados','regimen_laboral')")->fetchColumn() === null) {
    $fail[] = 'Falta th_empleados.regimen_laboral.';
}
$sequence = (int)$db->query(
    "SELECT COUNT_BIG(*) FROM dbo.th_secuencias_documentos
     WHERE regimen_laboral='CODIGO_TRABAJO' AND tipo_documento='FORMULARIO_ABREVIADO'
       AND prefijo='CdgT' AND activo=1"
)->fetchColumn();
if ($sequence < 1) $fail[] = 'No existe la secuencia activa CdgT.';

$invalidEmployees = (int)$db->query(
    "SELECT COUNT_BIG(*) FROM dbo.th_empleados
     WHERE regimen_laboral NOT IN ('LOSEP','CODIGO_TRABAJO')
        OR (regimen_laboral='CODIGO_TRABAJO' AND tipo_contrato<>'Contrato Indefinido')"
)->fetchColumn();
if ($invalidEmployees > 0) $fail[] = "Existen {$invalidEmployees} funcionarios con régimen/contrato inconsistente.";

$ledger = $db->prepare(
    'SELECT nombre_archivo,checksum_sha256 FROM dbo.th_schema_migrations WHERE version=:version'
);
$ledger->execute([':version'=>'2026.08.29.1']);
$row = $ledger->fetch(PDO::FETCH_ASSOC) ?: [];
$file = ROOT.'/database/'.basename((string)($row['nombre_archivo'] ?? ''));
$stored = strtolower(trim((string)($row['checksum_sha256'] ?? '')));
if (!is_file($file) || strlen($stored) !== 64 || !hash_equals($stored, strtolower((string)hash_file('sha256', $file)))) {
    $fail[] = 'El ledger/checksum 2026.08.29.1 no coincide con el archivo versionado.';
}

if ($fail) {
    fwrite(STDERR, "REGIMEN_LABORAL_DB_SMOKE_FAIL\n- ".implode("\n- ", $fail)."\n");
    exit(1);
}
echo "REGIMEN_LABORAL_DB_SMOKE_OK\n";
