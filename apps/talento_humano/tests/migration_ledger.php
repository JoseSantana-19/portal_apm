<?php
declare(strict_types=1);

define('ROOT',dirname(__DIR__));
require ROOT.'/core/Config.php';
require ROOT.'/core/Database.php';

$db=Conexion::conectar();
$rows=$db->query('SELECT version,nombre_archivo,checksum_sha256 FROM dbo.th_schema_migrations ORDER BY migration_id')->fetchAll(PDO::FETCH_ASSOC);
$fail=[];
if(count($rows)!==20)$fail[]='El ledger debe contener exactamente 20 migraciones y contiene '.count($rows).'.';
if(count(array_filter($rows,static fn(array $row):bool=>(string)$row['version']==='2026.08.29.1'))!==1)$fail[]='Falta la versión 2026.08.29.1.';
if(count(array_filter($rows,static fn(array $row):bool=>(string)$row['version']==='2026.08.30.1'))!==1)$fail[]='Falta la versión 2026.08.30.1.';
foreach($rows as $row){
    $file=ROOT.'/database/'.basename((string)$row['nombre_archivo']);
    if(!is_file($file)){$fail[]='No existe '.basename($file).'.';continue;}
    $stored=strtolower(trim((string)$row['checksum_sha256']));
    $current=strtolower((string)hash_file('sha256',$file));
    if(strlen($stored)!==64)$fail[]='Checksum inválido para '.basename($file).'.';
    elseif(!hash_equals($stored,$current))$fail[]='La migración aplicada fue modificada: '.basename($file).'.';
}
if($fail){fwrite(STDERR,"MIGRATION_LEDGER_FAIL\n- ".implode("\n- ",$fail)."\n");exit(1);}
echo 'MIGRATION_LEDGER_OK total='.count($rows).' hashes='.count($rows).PHP_EOL;
