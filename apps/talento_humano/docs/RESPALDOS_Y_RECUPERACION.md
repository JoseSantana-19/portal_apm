# Respaldos y recuperación

La base `Talento_Humano` usa el modelo de recuperación `FULL`. Los trabajos se crean o actualizan mediante `database/administracion/configurar_respaldos.sql`:

- `APM - Respaldo completo semanal`: domingo 02:00, `CHECKSUM`, compresión y `RESTORE VERIFYONLY`;
- `APM - Respaldo diferencial diario`: lunes a sábado 02:00, `CHECKSUM`, compresión y verificación;
- `APM - Respaldo log 15 minutos`: todos los días, cada 15 minutos;
- `APM - Integridad semanal`: domingo 04:00 mediante `DBCC CHECKDB` sin reparación automática.

## Evidencia histórica del cierre del 06-08-2026

Se crearon y verificaron los siguientes respaldos en la carpeta oficial de SQL Server:

- `Talento_Humano_pre_cierre_20260806_154059.bak` (`COPY_ONLY`, previo a la migración);
- `Talento_Humano_FULL_20260806.bak` (respaldo completo que establece la base diferencial);
- `Talento_Humano_LOG_20260806_inicial.trn` (primer respaldo del log del cierre).

Los cuatro trabajos de SQL Server Agent están instalados y habilitados. En el equipo local actual, `SQLSERVERAGENT` quedó en ejecución e inicio automático. Para verificarlo en cualquier ambiente, use una consola elevada:

```powershell
Get-Service -Name SQLSERVERAGENT
```

El resultado esperado es `Status: Running` y `StartType: Automatic`. En un servidor nuevo se debe configurar ese inicio antes de confiar en los horarios.

La comprobación de base, trabajos, últimas ejecuciones y archivos con checksum se centraliza en:

```powershell
sqlcmd -S portal-apm-preprod.local -E -b -i database\administracion\verificar_respaldos.sql
```

Esta consulta no crea respaldos ni altera trabajos. Una revisión válida debe mostrar `FULL`, los cuatro trabajos habilitados sin último estado `FALLO` y respaldos recientes con `has_backup_checksums=1`.

## Controles operativos

- configurar alertas por correo para fallos de los trabajos o de `DBCC CHECKDB`;
- copiar los respaldos a un medio o repositorio cifrado fuera del servidor principal;
- definir retención según la política institucional;
- probar mensualmente una restauración completa, diferencial y de logs en un servidor aislado;
- revisar semanalmente el historial de los cuatro trabajos;
- nunca incorporar credenciales ni archivos `.bak`/`.trn` al repositorio Git.

El script `database/administracion/configurar_respaldo_externo_alertas.sql` instala un respaldo diario `COPY_ONLY` cifrado con AES-256 hacia una ruta UNC, ejecuta `RESTORE VERIFYONLY WITH CHECKSUM`, notifica fallos de los trabajos APM y crea alertas para los errores SQL 823, 824 y 825. Debe ejecutarse únicamente después de:

- crear en `master` el certificado `APM_BackupEncryptionCert`;
- exportar la clave privada del certificado a una custodia diferente del repositorio de respaldos;
- conceder a la cuenta del servicio SQL acceso mínimo a la ruta UNC;
- configurar y probar un perfil de Database Mail;
- definir un operador institucional que reciba las alertas.

Ejemplo sin guardar secretos en el repositorio:

```powershell
sqlcmd -S SQL-PRODUCCION -E -b `
  -i database\administracion\configurar_respaldo_externo_alertas.sql `
  -v ExternalBackupPath="\\servidor-respaldo\portal-apm" `
     OperatorEmail="infraestructura@institucion.gob.ec" `
     MailProfile="APM Database Mail"
```

El archivo externo no es recuperable sin el certificado y su clave privada; por eso ambos deben respaldarse por separado conforme a la política institucional.

La tabla `th_respaldo_normalizacion_20260729` conserva valores previos de área, cargo, contrato y título por empleado. Es un respaldo lógico de conciliación y no reemplaza los respaldos de SQL Server.

## Simulacro integral de restauración

El 13-08-2026 se restauró correctamente una cadena formada por un respaldo FULL,
un diferencial y cinco respaldos de log en la base temporal
`Talento_Humano_RestoreDrill_20260813_034500`. La prueba comprobó:

- `RESTORE VERIFYONLY WITH CHECKSUM` para cada medio;
- restauración `NORECOVERY`/`RECOVERY` de la cadena completa;
- `DBCC CHECKDB` sin errores;
- coincidencia de empleados y las migraciones registradas con la base principal;
- existencia de registros de auditoría restaurados;
- eliminación comprobada de la base temporal al terminar;
- permanencia de `Talento_Humano` en estado `ONLINE`, recuperación `FULL` y
  `PAGE_VERIFY CHECKSUM`.

El simulacro reutilizable es `deployment/restore-drill.php`. Primero debe ejecutarse
sin `--execute`; ese modo solo valida medios, capacidad y cadena. La restauración
real exige un nombre temporal con el patrón seguro y repetirlo exactamente:

```powershell
$env:PORTAL_DBA_USER = '<usuario DBA temporal>'
$env:PORTAL_DBA_PASSWORD = '<clave DBA temporal>'
C:\php85\php.exe deployment\restore-drill.php `
  --execute `
  --target=Talento_Humano_RestoreDrill_AAAAMMDD_HHMMSS `
  --confirm-target=Talento_Humano_RestoreDrill_AAAAMMDD_HHMMSS
Remove-Item Env:PORTAL_DBA_USER, Env:PORTAL_DBA_PASSWORD
```

Las credenciales son exclusivamente variables transitorias de la consola. No se
guardan en el script, la documentación ni Git. La prueba se debe repetir al menos
mensualmente y conservar su resultado en el registro operativo institucional.
