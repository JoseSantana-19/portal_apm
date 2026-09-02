# Espejo local IIS y SQL cifrado

## Estado validado

- Host local: `https://portal-apm-preprod.local/`.
- IIS con FastCGI y URL Rewrite 2.1.
- PHP 8.5.9 x64 Non-Thread-Safe en `C:\php85-nts`.
- Microsoft Drivers for PHP for SQL Server 5.13.1 y ODBC Driver 18.
- SQL Server con `ForceEncryption=1`; el cliente usa `Encrypt=true` y valida el certificado (`TrustServerCertificate=false`).
- HTTP redirige permanentemente a HTTPS.
- Cookie de sesión `Secure`, `HttpOnly` y `SameSite=Strict`; HSTS activo.
- Carpetas internas, logs, fuentes SQL, Git y documentos de configuración no son descargables.

Los módulos declarados como prototipo continúan fuera del alcance funcional. La hoja socioeconómica de ubicación sí forma parte del alcance y requiere acceso saliente HTTPS al proveedor de mosaicos configurado.

## Componentes locales

1. Ejecutar como administrador `deployment/iis/enable-iis-features.ps1`.
2. Descargar PHP 8.5 NTS x64 desde PHP.net. Para 8.5.9, el SHA-256 oficial es `516c2d72231bd035c8a910120834add0ad208098b790b4909b2cbeb93ce135fc`.
3. Copiar a la carpeta `ext` del paquete las DLL NTS x64 `php_sqlsrv_85_nts_x64.dll` y `php_pdo_sqlsrv_85_nts_x64.dll` de Microsoft Drivers 5.13.1.
4. Descargar URL Rewrite 2.1 x64 desde IIS.net y comprobar que la firma Authenticode pertenezca a Microsoft Corporation.
5. Instalar los certificados web y SQL en `Cert:\LocalMachine\My`, con sus copias públicas confiables según la política institucional.
6. Otorgar a la cuenta del servicio SQL únicamente lectura, ejecución y escritura sobre su contenedor CSP, sin eliminación, cambio de propietario ni control total, y reiniciar el servicio. Esta combinación fue validada con el proveedor `Microsoft RSA SChannel Cryptographic Provider` de este equipo:

```powershell
.\deployment\sql\configure-sql-certificate-acl.ps1 `
  -CertificateThumbprint 'HUELLA_DEL_CERTIFICADO_SQL' `
  -RestartService
```

7. Ejecutar como administrador:

```powershell
.\deployment\iis\configure-local-iis.ps1 `
  -PhpSourcePath 'C:\ruta\php-8.5.9-nts' `
  -UrlRewriteInstaller 'C:\ruta\rewrite_amd64_es-ES.msi' `
  -CertificateThumbprint 'HUELLA_DEL_CERTIFICADO_WEB'
```

El script configura el sitio, pool aislado, handler FastCGI, enlace SNI HTTPS, DNS en `hosts` y ACL de mínimo privilegio. Los secretos permanecen fuera del repositorio en `.portal-portuario-private`.

`deployment/sql/replace-local-sql-certificate.ps1` se reserva para recuperación. Requiere `-ConfirmReplacement`, crea una clave no exportable y nunca elimina automáticamente el certificado anterior: primero se valida el nuevo y luego se retira manualmente la huella obsoleta de los almacenes `My` y `Root`.

Si `certutil` muestra el certificado pero CryptoAPI informa que el conjunto de claves no existe, no se debe reutilizar esa huella. La recuperación completa y auditable es:

```powershell
# Consola elevada. Sustituye, confía y vuelve a enlazar SQL e IIS.
.\deployment\repair-local-tls.ps1 -ConfirmRepair

# Debe aprobar claves privadas, confianza, ForceEncryption y enlace HTTPS.
.\deployment\test-local-certificates.ps1

# Pruebas funcionales posteriores al reemplazo.
C:\php85-nts\php.exe scripts\preflight.php
C:\php85-nts\php.exe tests\run_sql_smoke.php
C:\php85-nts\php.exe tests\security_db_smoke.php
.\deployment\iis\test-local-iis.ps1
```

La limpieza se realiza solamente después de esas validaciones, indicando las dos huellas activas obtenidas por el reparador:

```powershell
.\deployment\cleanup-obsolete-local-certificates.ps1 `
  -ActiveSqlThumbprint 'HUELLA_SQL_ACTIVA' `
  -ActiveWebThumbprint 'HUELLA_WEB_ACTIVA' `
  -ConfirmCleanup
```

La limpieza limita su alcance a certificados obsoletos con los nombres descriptivos del Portal y se niega a continuar si las huellas indicadas no coinciden con SQL Server e IIS.

Si una política local requiere declarar TLS 1.2 explícitamente, `deployment/enable-local-tls12.ps1 -ConfirmChange` crea primero un respaldo JSON de los valores anteriores en `C:\ProgramData\PortalAPM` y configura únicamente TLS 1.2 cliente/servidor y criptografía fuerte de .NET. Requiere reiniciar Windows. No debe ejecutarse como sustituto de un diagnóstico ni habilita protocolos heredados.

Las causas y correcciones de los fallos encontrados están documentadas en [Incidencias y soluciones](INCIDENCIAS_Y_SOLUCIONES.md).

## Validación obligatoria

```powershell
C:\php85-nts\php.exe scripts\preflight.php
C:\php85-nts\php.exe tests\environment_static.php
C:\php85-nts\php.exe tests\run_sql_smoke.php
C:\php85-nts\php.exe tests\security_db_smoke.php
powershell -ExecutionPolicy Bypass -File deployment\test-local-certificates.ps1
powershell -ExecutionPolicy Bypass -File deployment\iis\test-local-iis.ps1
```

Para ejecutar toda la validación sensible en el contexto correcto, abra PowerShell como administrador y use:

```powershell
.\deployment\run-local-validation-gate.ps1
```

El resultado final esperado es `VALIDATION_GATE=OK`. El registro queda fuera del repositorio en `C:\ProgramData\PortalAPM\local-validation-gate.log`.

Para que la consola use exactamente el mismo binario NTS que FastCGI, ejecute
una sola vez en PowerShell como administrador:

```powershell
.\deployment\iis\align-php-cli.ps1
```

El script conserva el PATH anterior fuera de Git en
`.portal-portuario-private/environment/` y admite reversión con
`align-php-cli.ps1 -Restore`. Abra una consola nueva y confirme después
`where.exe php` y `php -v`.

El preflight debe terminar con `Fallos: 0`, `Cifrado SQL activado`, `Certificado SQL validado` y `Conexión SQL`. El verificador de IIS debe informar redirección 301, login 200, cabeceras seguras y rutas internas en 404.

## Paso al servidor definitivo

El código y la configuración son reproducibles, pero no se deben exportar las claves privadas ni los secretos locales. En producción se emiten certificados nuevos para el DNS real, se crea una cuenta SQL propia del ambiente y se ejecutan nuevamente preflight, pruebas SQL, verificaciones RBAC, PDF y respaldo/restauración.
