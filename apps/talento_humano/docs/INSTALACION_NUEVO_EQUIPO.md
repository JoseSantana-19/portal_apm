# Instalación del Portal APM en un equipo nuevo

Esta guía reproduce el alcance productivo actual. Asistencia, Desempeño y Capacitación permanecen como prototipos y no son requisito de aceptación.

## 1. Software que se debe descargar

| Componente | Versión/requisito | Origen recomendado | Motivo |
|---|---|---|---|
| Windows | Windows 11 x64 o Windows Server vigente compatible con IIS | Medio institucional de Microsoft | IIS, certificados y tareas de operación. Windows 10 no forma parte de la matriz vigente del driver PHP 5.13. |
| Git | Versión estable x64 | [Git for Windows](https://git-scm.com/download/win) o distribución institucional | Obtener y actualizar el repositorio privado. |
| Visual C++ Redistributable | Microsoft Visual C++ 2015–2022 x64 | [Microsoft](https://learn.microsoft.com/cpp/windows/latest-supported-vc-redist) | Runtime requerido por PHP para Windows. |
| PHP | 8.5.9 NTS x64 validado; no usar paquetes QA/RC | [PHP para Windows](https://windows.php.net/download/) | Runtime de la aplicación y FastCGI. |
| Microsoft Drivers for PHP for SQL Server | 5.13.1, DLL NTS x64 para PHP 8.5 | [Microsoft Learn](https://learn.microsoft.com/sql/connect/php/download-drivers-php-sql-server) | Extensiones `sqlsrv` y `pdo_sqlsrv`. |
| Microsoft ODBC Driver for SQL Server | Última versión estable de ODBC Driver 18 x64 | [Microsoft Learn](https://learn.microsoft.com/sql/connect/odbc/download-odbc-driver-for-sql-server) | Conexión TLS a SQL Server. |
| IIS URL Rewrite | 2.1 x64 | [IIS/Microsoft](https://www.iis.net/downloads/microsoft/url-rewrite) | Rutas amigables y bloqueo/redirección configurados en `web.config`. |
| SQL Server | Edición con SQL Server Agent para producción | Medio institucional de Microsoft | Base `Talento_Humano`, trabajos de vigencia, respaldos e integridad. |
| Herramientas SQL | `sqlcmd` y, opcionalmente, SSMS | Microsoft | Aplicar migraciones, administrar y verificar respaldos. |
| Navegador | Chrome, Edge o Firefox vigente | Proveedor oficial | Uso del portal, mapas, impresión y descarga. |

No se requiere Composer, Node.js, npm, jQuery ni conexión a CDN para ejecutar el portal. FPDF, DataTables, iconos y fuentes necesarias están versionados localmente. El proveedor de mosaicos del mapa sí necesita salida HTTPS desde el navegador/servidor según la configuración institucional.

Antes de instalar, verifique la firma digital de los instaladores. El paquete SQLSRV debe coincidir simultáneamente con PHP 8.5, x64 y NTS. Para la versión PHP de referencia, valide también el SHA-256 publicado en [Entorno local IIS](ENTORNO_LOCAL_IIS.md).

## 2. Información que debe entregar la institución

- DNS definitivo o nombre del espejo local.
- Certificado HTTPS con clave privada y cadena confiable.
- Certificado de SQL Server emitido para el nombre usado en `PORTAL_DB_SERVER`.
- Instancia SQL y base `Talento_Humano`.
- Cuenta DBA temporal para migraciones y cuenta `portal_app` de mínimo privilegio.
- Ruta privada persistente para configuración, claves, borradores, mapas y documentos firmados.
- Política de respaldos, retención, copia cifrada externa y restauración.
- Cuentas nominativas de los operadores y matriz de roles autorizada.
- Para correo real: relay SMTP, puerto/TLS, remitente autorizado, destinatarios y política de secretos. Sin esos datos el portal conserva únicamente la constancia de notificación.

Nunca copie claves privadas, contraseñas, respaldos ni archivos con datos personales desde otro ambiente. Cada equipo/ambiente debe tener secretos y certificados propios.

## 3. Preparar Windows e IIS

Abra PowerShell como administrador en la raíz del proyecto:

```powershell
.\deployment\iis\enable-iis-features.ps1
```

Reinicie si Windows lo solicita. Descargue PHP NTS x64, las DLL `php_sqlsrv_85_nts_x64.dll` y `php_pdo_sqlsrv_85_nts_x64.dll`, y URL Rewrite 2.1. El script de IIS copia PHP, configura FastCGI, instala Rewrite, crea el pool, asigna permisos mínimos y enlaza HTTPS:

```powershell
.\deployment\iis\configure-local-iis.ps1 `
  -PhpSourcePath 'C:\Descargas\php-8.5.9-nts-Win32-vs17-x64' `
  -UrlRewriteInstaller 'C:\Descargas\rewrite_amd64_es-ES.msi' `
  -CertificateThumbprint 'HUELLA_CERTIFICADO_WEB'
```

La referencia completa de certificados, ACL y recuperación TLS está en [Entorno local IIS](ENTORNO_LOCAL_IIS.md).

## 4. Configurar PHP

Use como base `deployment/iis/php.ini`. Deben estar habilitadas al menos:

- `pdo_sqlsrv`;
- `sqlsrv`;
- `openssl`;
- `fileinfo`;
- `mbstring`.

Confirme:

```powershell
C:\php85-nts\php.exe -v
C:\php85-nts\php.exe -m
```

La aplicación debe usar PHP NTS x64 tanto en FastCGI como en consola. Puede alinear el CLI mediante:

```powershell
.\deployment\iis\align-php-cli.ps1
```

## 5. Crear la configuración privada

Cree fuera de la raíz web el directorio indicado por `PORTAL_PRIVATE_DIR`. Copie `config/database.example.php` como `database.php` dentro de esa ruta o configure variables de entorno basándose en `.env.example`.

Variables obligatorias/recomendadas:

- `PORTAL_ENV=production`;
- `PORTAL_TIMEZONE=America/Guayaquil`;
- `PORTAL_BASE_URL=https://DNS_REAL`;
- `PORTAL_PRIVATE_DIR`;
- `PORTAL_DB_SERVER`, `PORTAL_DB_DRIVER`, `PORTAL_DB_NAME`, `PORTAL_DB_USER` y `PORTAL_DB_PASSWORD`;
- `PORTAL_DB_ENCRYPT=true`;
- `PORTAL_DB_TRUST_CERT=false`;
- `PORTAL_TOKEN_KEY` y `PORTAL_DRAFT_KEY`, diferentes entre sí, de 32 bytes aleatorios expresados como 64 caracteres hexadecimales o Base64.
- `PORTAL_SMTP_ENABLED`, `PORTAL_SMTP_HOST`, `PORTAL_SMTP_PORT`, `PORTAL_SMTP_ENCRYPTION`, `PORTAL_SMTP_USER`, `PORTAL_SMTP_PASSWORD`, `PORTAL_SMTP_FROM_ADDRESS`, `PORTAL_SMTP_FROM_NAME`, `PORTAL_SMTP_TIMEOUT` y `PORTAL_SMTP_VERIFY_PEER` cuando se habilite la entrega real.

No coloque un archivo `.env` con secretos dentro del repositorio. Las variables deben configurarse en IIS/FastCGI o en el almacén institucional; `database.php` permanece privado.

## 6. Preparar SQL Server

1. Instale el certificado SQL y habilite cifrado forzado.
2. Restaure o cree `Talento_Humano` con recuperación `FULL`, `PAGE_VERIFY CHECKSUM`, `AUTO_CLOSE OFF` y `AUTO_SHRINK OFF`.
3. Realice un respaldo previo con checksum.
4. Aplique exactamente las 20 migraciones en el orden de [database/README.md](../database/README.md).
5. Ejecute una vez `scripts/sincronizar_nacionalidades.php` con credenciales DBA temporales.
6. Cree `portal_app`, agréguelo a `portal_app_role` y retire las credenciales DBA del proceso.
7. Ejecute `database/administracion/configurar_respaldos.sql` y confirme que SQL Server Agent quede automático.
8. En producción, configure el certificado de cifrado, Database Mail y ejecute `database/administracion/configurar_respaldo_externo_alertas.sql` con una ruta UNC externa.

Las migraciones deben aplicarse con `deployment/apply-local-migration.ps1` o `deployment/apply-migration-pdo.php`; ambos verifican TLS, privilegios, respaldo y checksum. No edite una migración después de haberla aplicado: cree una migración correctiva nueva.

## 7. Directorios y permisos

La identidad del pool IIS necesita:

- lectura/ejecución sobre el código;
- modificación en `storage/logs`;
- modificación en `public/img/empleados`;
- modificación en `PORTAL_PRIVATE_DIR` para borradores cifrados, mapas y documentos firmados;
- escritura en los directorios de logs/temporales definidos para IIS.

Los directorios `database`, `core`, `docs`, `deployment`, `scripts`, `tests`, `.git`, configuración privada y respaldos no deben ser descargables por HTTP.

## 8. Validación antes de habilitar usuarios

Desde PowerShell como administrador:

```powershell
C:\php85-nts\php.exe scripts\preflight.php
C:\php85-nts\php.exe tests\migration_ledger.php
powershell -ExecutionPolicy Bypass -File deployment\run-local-validation-gate.ps1
```

El resultado debe incluir:

- `Fallos: 0`;
- `MIGRATION_LEDGER_OK total=20 hashes=20`;
- `VALIDATION_GATE=OK`;
- conexión SQL cifrada y certificado validado;
- HTTPS 200/301, cabeceras seguras y rutas internas bloqueadas.

Después complete el UAT descrito en [Pruebas y aceptación](PRUEBAS_Y_ACEPTACION.md), revise visualmente todos los PDF y pruebe una restauración real de respaldo.

## 9. Habilitación y entrega

1. Cree cuentas nominativas; no entregue la cuenta administrativa compartida.
2. Obligue cambio de clave y activación MFA.
3. Compruebe el rol Asistente de Talento Humano y las denegaciones administrativas.
4. Configure salud diaria, retención y alertas de SQL Agent.
5. Registre versión/commit desplegado, fecha, certificados, migraciones y responsables en el acta de entrega.
6. Capacite a operadores con el [Manual de usuario](MANUAL_USUARIO.md).

No se considera instalado al 100 % si falta cualquiera de estas evidencias: ledger de 20 migraciones, preflight, compuerta local, restauración, UAT, aceptación de PDF y acta institucional.
