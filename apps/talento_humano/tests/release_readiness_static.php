<?php

$root = dirname(__DIR__);
$workflow = file_get_contents($root.'/.github/workflows/preproduction-gate.yml');
$backup = file_get_contents($root.'/database/administracion/configurar_respaldo_externo_alertas.sql');
$fail = [];
$assert = static function(bool $condition,string $message) use (&$fail):void { if(!$condition)$fail[]=$message; };

$assert(str_contains($workflow,'workflow_dispatch:') && str_contains($workflow,'self-hosted, windows, portal-apm-preprod'),'El gate SQL/IIS no está limitado a un runner Windows de preproducción.');
$assert(str_contains($workflow,'run-local-validation-gate.ps1'),'El workflow de preproducción no ejecuta la compuerta oficial.');
$assert(str_contains($backup,'ENCRYPTION(ALGORITHM=AES_256'),'El respaldo externo no exige cifrado AES-256.');
$assert(str_contains($backup,'RESTORE VERIFYONLY') && str_contains($backup,'WITH CHECKSUM'),'El respaldo externo no verifica integridad.');
$assert(str_contains($backup,"VALUES(823),(824),(825)"),'Faltan alertas de corrupción o E/S SQL Server.');
$assert(str_contains($backup,'@notify_level_email=2'),'Los trabajos SQL Agent no notifican fallos por correo.');

if($fail){fwrite(STDERR,"RELEASE_READINESS_STATIC_FAIL\n- ".implode("\n- ",$fail)."\n");exit(1);}echo "RELEASE_READINESS_STATIC_OK\n";
