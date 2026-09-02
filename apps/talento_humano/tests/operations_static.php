<?php
$root=dirname(__DIR__);$fail=[];
foreach(['deployment/monitor-local-health.ps1','deployment/install-local-monitoring-task.ps1','deployment/cleanup-local-logs.ps1','deployment/restore-drill.php'] as $file){if(!is_file($root.'/'.$file))$fail[]="Falta $file";}
$cleanup=file_get_contents($root.'/deployment/cleanup-local-logs.ps1')?:'';
if(!str_contains($cleanup,'ConfirmCleanup')||!str_contains($cleanup,'RETENTION_DRY_RUN'))$fail[]='La limpieza no exige confirmación o no ofrece simulación';
$monitor=file_get_contents($root.'/deployment/monitor-local-health.ps1')?:'';
foreach(['MSSQLSERVER','SQLSERVERAGENT','W3SVC','CertificateWarningDays','health-latest.json','scripts\\preflight.php'] as $needle){if(!str_contains($monitor,$needle))$fail[]="Monitor incompleto: $needle";}
foreach($fail as $item)fwrite(STDERR,"[FAIL] $item\n");echo $fail?'OPERATIONS_STATIC=FAIL'.PHP_EOL:'OPERATIONS_STATIC=OK'.PHP_EOL;exit($fail?1:0);
