<?php
declare(strict_types=1);

define('ROOT', dirname(__DIR__));
require ROOT.'/core/Config.php';
require ROOT.'/core/InstitutionalClock.php';

$errors=[];
$assert=static function(bool $ok,string $message) use (&$errors):void { if(!$ok)$errors[]=$message; };

putenv('PORTAL_TIMEZONE=America/Guayaquil');
putenv('PORTAL_TEST_TODAY=2026-08-17');
$tomorrow=InstitutionalClock::nextBirthday('1990-08-18');
$assert($tomorrow['days']===1 && $tomorrow['label']==='MAÑANA','El 18/08 debe ser manana cuando la fecha institucional es 17/08.');

putenv('PORTAL_TEST_TODAY=2026-08-18');
$today=InstitutionalClock::nextBirthday('1990-08-18');
$assert($today['days']===0 && $today['label']==='HOY','El cumpleanos del 18/08 debe ser hoy el 18/08.');

putenv('PORTAL_TEST_TODAY=2026-12-31');
$newYear=InstitutionalClock::nextBirthday('1990-01-01');
$assert($newYear['days']===1,'El cambio de ano debe calcular un dia.');

putenv('PORTAL_TEST_TODAY=2027-02-28');
$leap=InstitutionalClock::nextBirthday('2000-02-29');
$assert($leap['days']===0,'El 29/02 debe notificarse el 28/02 en anos no bisiestos.');

putenv('PORTAL_TEST_TODAY');
if($errors){fwrite(STDERR,implode(PHP_EOL,$errors).PHP_EOL);exit(1);}
echo "[OK] Reloj institucional y cumpleanos\n";
