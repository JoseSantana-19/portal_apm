<?php
declare(strict_types=1);
define('ROOT',dirname(__DIR__));
require ROOT.'/core/Controller.php';
foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator(ROOT)) as $file){
    if($file->isFile()&&str_ends_with($file->getFilename(),'Controller.php')&&!str_contains($file->getPathname(),'tests'))require_once $file->getPathname();
}
$source=(string)file_get_contents(ROOT.'/index.php');
preg_match_all("/\\\$router->add\\('([^']*)',\\s*'([^']+)',\\s*'([^']+)'\\)/",$source,$matches,PREG_SET_ORDER);
$errors=[];foreach($matches as $m){if(!class_exists($m[2]))$errors[]="{$m[1]}: no existe {$m[2]}";elseif(!method_exists($m[2],$m[3]))$errors[]="{$m[1]}: no existe {$m[2]}::{$m[3]}";}
if(count($matches)<50)$errors[]='Se detectaron menos rutas de las esperadas.';
if($errors){foreach($errors as $e)fwrite(STDERR,"[FAIL] {$e}\n");exit(1);}echo '[OK] '.count($matches).' rutas con destinos válidos'.PHP_EOL;
