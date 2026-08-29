<?php
declare(strict_types=1);
require dirname(__DIR__,2) . '/helpers/polyfills_php74.php';
$root=dirname(__DIR__);$fail=[];
$assert=function(bool $ok,string $message)use(&$fail){if(!$ok)$fail[]=$message;};
$ht=(string)file_get_contents($root.'/.htaccess');foreach(['database','SQL','core','tmp','output','.git'] as $path)$assert(str_contains($ht,$path),".htaccess no bloquea {$path}");
$index=(string)file_get_contents($root.'/index.php');$assert(str_contains($index,'Auth::requirePermission'),'No existe autorización central.');
$auth=(string)file_get_contents($root.'/core/Auth.php');foreach(['aes-256-gcm','IDLE_TTL','MAX_LOGIN_ATTEMPTS','token_version'] as $token)$assert(str_contains($auth,$token),"Falta control de autenticación: {$token}");
$profile=(string)file_get_contents($root.'/modules/talento-humano/Vistas/perfil.php');$assert(!str_contains($profile,'perfilesMock'),'El perfil aún contiene datos simulados.');
$dashboard=(string)file_get_contents($root.'/modules/talento-humano/Vistas/inicio.php');$assert(!str_contains($dashboard,'cedulas_mock'),'El dashboard aún contiene cédulas simuladas.');
$all='';foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root.'/modules')) as $f){if($f->isFile()&&$f->getExtension()==='php'&&!preg_match('~(?:Asistencia|Vacaciones|Desempeno|Capacitacion)Controller\.php$~',$f->getPathname()))$all.=file_get_contents($f->getPathname());}
$assert(!str_contains($all,'Cambiar#APM'),'Existe una clave temporal versionada.');
if($fail){foreach($fail as $f)fwrite(STDERR,"[FAIL] {$f}\n");exit(1);}echo "[OK] controles estáticos de seguridad\n";
