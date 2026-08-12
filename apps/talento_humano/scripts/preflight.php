<?php
declare(strict_types=1);
define('ROOT',dirname(__DIR__));require ROOT.'/core/Config.php';require ROOT.'/core/Database.php';
$checks=[];$add=function(string $name,bool $ok,string $detail='')use(&$checks){$checks[]=[$name,$ok,$detail];};
foreach(['pdo_sqlsrv','openssl','fileinfo','mbstring'] as $ext)$add("Extensión {$ext}",extension_loaded($ext));
$add('Entorno production',Config::isProduction(),Config::environment());$cfg=Config::database();
$add('Usuario SQL distinto de sa',strtolower((string)$cfg['user'])!=='sa',(string)$cfg['user']);
$add('Cifrado SQL activado',!empty($cfg['encrypt']));$add('Certificado SQL validado',empty($cfg['trust_server_certificate']));
try{$db=Conexion::conectar();$add('Conexión SQL',true);$objects=['th_usuarios_sistema','th_permisos_rol','th_politicas_documentos','sp_th_aprobar_accion_personal','sp_th_auditar_lectura'];foreach($objects as $o){$s=$db->prepare('SELECT CASE WHEN OBJECT_ID(:obj) IS NULL THEN 0 ELSE 1 END');$s->execute([':obj'=>'dbo.'.$o]);$add("Objeto dbo.{$o}",(bool)$s->fetchColumn());}}catch(Throwable $e){$add('Conexión SQL',false,$e->getMessage());}
$failed=0;foreach($checks as [$name,$ok,$detail]){echo ($ok?'[OK]  ':'[FAIL] ').$name.($detail!==''?' — '.$detail:'').PHP_EOL;if(!$ok)$failed++;}echo "Fallos: {$failed}".PHP_EOL;exit($failed?1:0);
