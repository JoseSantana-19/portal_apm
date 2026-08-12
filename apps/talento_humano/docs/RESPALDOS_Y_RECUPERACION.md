# Respaldos y recuperación

La base `Talento_Humano` usa el modelo de recuperación `FULL`. Los trabajos se crean o actualizan mediante `database/administracion/configurar_respaldos.sql`:

- `APM - Respaldo completo semanal`: domingo 02:00, `CHECKSUM`, compresión y `RESTORE VERIFYONLY`;
- `APM - Respaldo diferencial diario`: lunes a sábado 02:00, `CHECKSUM`, compresión y verificación;
- `APM - Respaldo log 15 minutos`: todos los días, cada 15 minutos;
- `APM - Integridad semanal`: domingo 04:00 mediante `DBCC CHECKDB` sin reparación automática.

## Estado del cierre del 06-08-2026

Se crearon y verificaron los siguientes respaldos en la carpeta oficial de SQL Server:

- `Talento_Humano_pre_cierre_20260806_154059.bak` (`COPY_ONLY`, previo a la migración);
- `Talento_Humano_FULL_20260806.bak` (respaldo completo que establece la base diferencial);
- `Talento_Humano_LOG_20260806_inicial.trn` (primer respaldo del log del cierre).

Los cuatro trabajos de SQL Server Agent están instalados y habilitados. En el equipo actual, Windows exige privilegios de administrador para iniciar y cambiar el inicio del servicio. Un administrador debe ejecutar una sola vez en PowerShell elevado:

```powershell
Set-Service -Name SQLSERVERAGENT -StartupType Automatic
Start-Service -Name SQLSERVERAGENT
Get-Service -Name SQLSERVERAGENT
```

El resultado esperado es `Status: Running` y `StartType: Automatic`. Mientras no se complete este paso, los trabajos existen pero no se ejecutan automáticamente.

## Controles operativos

- configurar alertas por correo para fallos de los trabajos o de `DBCC CHECKDB`;
- copiar los respaldos a un medio o repositorio cifrado fuera del servidor principal;
- definir retención según la política institucional;
- probar mensualmente una restauración completa, diferencial y de logs en un servidor aislado;
- revisar semanalmente el historial de los cuatro trabajos;
- nunca incorporar credenciales ni archivos `.bak`/`.trn` al repositorio Git.

La tabla `th_respaldo_normalizacion_20260729` conserva valores previos de área, cargo, contrato y título por empleado. Es un respaldo lógico de conciliación y no reemplaza los respaldos de SQL Server.
