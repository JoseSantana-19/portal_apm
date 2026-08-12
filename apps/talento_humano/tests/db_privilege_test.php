<?php
declare(strict_types=1);
define('ROOT',dirname(__DIR__));
require ROOT.'/core/Config.php';
require ROOT.'/core/Database.php';
$db=Conexion::conectar();
$login=(string)$db->query('SELECT SUSER_SNAME()')->fetchColumn();
$sysadmin=(int)$db->query("SELECT IS_SRVROLEMEMBER('sysadmin')")->fetchColumn();
$employees=(int)$db->query('SELECT COUNT_BIG(*) FROM dbo.th_empleados')->fetchColumn();
echo "LOGIN={$login}\nSYSADMIN={$sysadmin}\nEMPLOYEES={$employees}\n";
if($login!=='portal_app'||$sysadmin!==0||$employees<1)exit(1);
