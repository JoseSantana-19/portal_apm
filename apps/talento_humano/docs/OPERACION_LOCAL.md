# Operación y monitoreo local

## Salud diaria

`deployment/monitor-local-health.ps1` comprueba servicios SQL Server, SQL Agent e IIS, login HTTPS, certificado Web, conexión SQL cifrada mediante preflight y espacio de disco. Conserva el último resultado en:

```text
C:\ProgramData\PortalAPM\health-latest.json
```

La tarea `Portal APM - Salud diaria` se instala a las 07:00 con privilegio **limitado**, asociada al usuario local y solo cuando hay sesión interactiva. No utiliza `SYSTEM` ni elevación futura:

```powershell
.\deployment\install-local-monitoring-task.ps1
```

Un código distinto de cero queda reflejado en el historial del Programador de tareas. La versión actual registra la constancia de notificación y sus múltiples destinatarios, pero no envía correo. El envío SMTP se mantiene deshabilitado hasta recibir un relay institucional, remitente autorizado, destinatarios, política de secretos y reglas de reintento/retención.

## Retención

`deployment/cleanup-local-logs.ps1` solo inspecciona `storage/logs` y `C:\inetpub\logs\PortalPortuario`. Sin parámetros hace una simulación. La eliminación exige `-ConfirmCleanup`, admite `-WhatIf` y nunca acepta menos de 30 días para aplicación ni 14 para IIS:

```powershell
.\deployment\cleanup-local-logs.ps1
.\deployment\cleanup-local-logs.ps1 -ConfirmCleanup -WhatIf
```

La cuenta limitada puede no leer logs IIS; esa limpieza debe ejecutarse manualmente desde una consola administrativa después de revisar la lista. Los respaldos SQL no se borran desde este script: su retención definitiva requiere política institucional y copia externa cifrada.

## Estado comprobado

La última ejecución local informó los tres servicios activos, HTTPS 200, certificado Web vigente, preflight SQL cifrado correcto y salud general `OK`. La unidad del sistema debe vigilarse porque se aproxima al umbral preventivo del 10% libre.
