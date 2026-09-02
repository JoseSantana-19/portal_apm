# Incidencias y soluciones verificadas

Este documento registra problemas reproducibles del entorno y su corrección. No contiene secretos ni huellas activas.

## SQL: `Encryption not supported on the client`

**Síntoma:** después de habilitar `ForceEncryption`, PDO/ODBC no podía negociar TLS y el preflight fallaba en `Conexión SQL`.

**Diagnóstico:** se comprobó el puerto 1433, ODBC Driver 18, suites TLS, registro de SQL Server y `ERRORLOG`. SQL cargaba el certificado, pero el contenedor CSP no tenía el acceso efectivo requerido por la cuenta virtual del servicio. Un certificado anterior también conservaba una asociación de clave inconsistente.

**Corrección:** se generó un certificado SQL con autenticación de servidor, proveedor `Microsoft RSA SChannel Cryptographic Provider`, RSA 2048, SHA-256 y `KeySpec=AT_KEYEXCHANGE`; se confió su parte pública, se enlazó a SQL Server y se mantuvo `ForceEncryption=1`. A `NT SERVICE\MSSQLSERVER` se le otorgó exclusivamente lectura, ejecución y escritura sobre esa clave, sin eliminación, control total, cambio de propietario ni cambio de ACL. El certificado anterior, su copia de confianza y su clave huérfana se eliminaron después de validar el reemplazo.

**Validación:** `preflight.php` terminó con cero fallos; las pruebas SQL aprobaron y `portal_app` devolvió `SYSADMIN=0`.

Herramientas: `deployment/sql/configure-sql-certificate-acl.ps1` y, solo para recuperación autorizada, `deployment/sql/replace-local-sql-certificate.ps1`.

## IIS/FastCGI: errores 500 al iniciar PHP

**Síntoma:** IIS devolvía error 500 aunque PHP funcionaba desde consola.

**Causas encontradas:** handler FastCGI definido en una sección bloqueada, autenticación anónima usando una identidad distinta del pool, valor `monitorChangesTo` guardado con comillas literales y carga duplicada de OPcache en PHP 8.5.

**Corrección:** el handler se registra a nivel del sitio mediante AppHost, la autenticación anónima utiliza la identidad del pool, `monitorChangesTo` se configura sin comillas y no se fuerza una extensión OPcache adicional.

**Validación:** el login HTTPS responde 200 y el script `deployment/iis/test-local-iis.ps1` comprueba redirección, cabeceras y rutas bloqueadas.

## IIS: rutas y recursos con prefijo incorrecto

**Síntoma:** al publicar en la raíz del host, algunos recursos podían conservar el prefijo histórico `/PortalPortuario`.

**Corrección:** `Config::baseUrl()` deriva la base desde `SCRIPT_NAME` cuando `PORTAL_BASE_URL` no está definido. En el espejo IIS la variable queda vacía; en un subdirectorio puede establecerse explícitamente.

## Interfaz sin estilos o iconos

**Síntoma:** el navegador mostraba HTML sin diseño cuando CSS/JS se dirigían al front controller o cuando las fuentes e iconos externos no estaban disponibles.

**Corrección:** el router integrado entrega solamente archivos reales de `public/`; IIS conserva tipos MIME y reglas de reescritura; Manrope, Space Grotesk y Bootstrap Icons están versionados en `public/vendor` con sus licencias. `head_assets.php` ya no utiliza CDN y la CSP admite únicamente recursos propios.

**Validación:** `tests/frontend_assets_static.php` comprueba archivos, rutas y ausencia de orígenes remotos; el recorrido real del login confirmó dos hojas vendorizadas, cero hojas externas y diseño completo.

## PHP CLI distinto de FastCGI

**Síntoma:** `php` resolvía a la compilación ZTS de `C:\php85`, mientras IIS operaba correctamente con PHP 8.5 NTS en `C:\php85-nts`.

**Corrección:** `deployment/iis/align-php-cli.ps1` respalda el PATH de máquina fuera de Git, retira únicamente las entradas PHP conflictivas y prioriza `C:\php85-nts`. Puede revertirse con `-Restore`.

**Validación:** el primer elemento del PATH de máquina es `C:\php85-nts` y el binario informa PHP 8.5.9 NTS.

## HTTPS: archivos internos potencialmente descargables

**Riesgo:** al publicar la raíz del proyecto, directorios de código, SQL, logs o Git podrían quedar accesibles.

**Corrección:** `web.config` oculta segmentos internos, bloquea extensiones sensibles, desactiva listado y obliga HTTPS. La aplicación añade HSTS, CSP, protección de marcos y cookies seguras.

**Validación:** `/core/Config.php`, `/README.md`, `/.git/config` y `/storage/logs/portal.log` responden 404.

## Preflight bajo cuentas de automatización restringidas

**Síntoma:** una cuenta aislada de automatización puede fallar al consultar Schannel aunque el pool de IIS y el usuario operativo funcionen.

**Resolución:** ejecutar el preflight desde una consola administrativa real y confirmar además la conexión desde el pool de IIS. No se debe debilitar `TrustServerCertificate` ni otorgar `sysadmin` para hacer pasar una cuenta de diagnóstico.

**Comprobación consolidada:** `deployment/run-local-validation-gate.ps1` ejecuta en ese contexto la clave privada real, `ForceEncryption`, preflight, SQL funcional, privilegios mínimos e IIS/recursos CSS. El 13-08-2026 terminó con `VALIDATION_GATE=OK`; el navegador cargó las seis hojas locales, el logo y la fuente calculada sin errores de consola.

## Certificados duplicados

**Prevención:** el script de sustitución exige `-ConfirmReplacement` y nunca elimina el certificado anterior automáticamente. Primero se prueba SQL e IIS; luego se retira únicamente la huella obsoleta y su clave, verificando que no esté enlazada a SQL Server ni IIS.

**Clave privada huérfana:** `HasPrivateKey=True` no demuestra que la clave sea utilizable. Si `certutil -store My` informa `Falta el conjunto de claves almacenado`, o `GetRSAPrivateKey()` falla, el certificado no es válido para operación aunque aún aparezca en la consola. `deployment/repair-local-tls.ps1` reemplaza ambos certificados y registra las huellas anteriores y nuevas; `deployment/test-local-certificates.ps1` comprueba la clave real, confianza, enlace y cifrado antes de permitir la limpieza controlada.

## Comandos de cierre

```powershell
C:\php85-nts\php.exe scripts\preflight.php
C:\php85-nts\php.exe tests\environment_static.php
C:\php85-nts\php.exe tests\run_sql_smoke.php
C:\php85-nts\php.exe tests\security_db_smoke.php
C:\php85-nts\php.exe tests\db_privilege_test.php
powershell -ExecutionPolicy Bypass -File deployment\test-local-certificates.ps1
powershell -ExecutionPolicy Bypass -File deployment\iis\test-local-iis.ps1
```

## Cumpleaños desplazado un día

**Síntoma:** un funcionario con cumpleaños el día 18 podía mostrarse como “hoy” desde el día 17 cuando el navegador, PHP y SQL Server resolvían fechas en zonas horarias distintas.

**Corrección:** toda decisión de calendario se centraliza en `InstitutionalClock` con la zona `America/Guayaquil`. El servidor calcula la fecha institucional y el próximo aniversario sin depender del reloj JavaScript del navegador. Para el 29 de febrero se aplica explícitamente el 28 de febrero en años no bisiestos.

**Validación:** `php tests/institutional_clock.php` cubre hoy, mañana, cambio de año y año bisiesto mediante `PORTAL_TEST_TODAY`, sin modificar el reloj del equipo.

## Pérdida de formularios por corte o cierre accidental

**Riesgo:** los formularios extensos podían perder la información todavía no enviada.

**Corrección:** los formularios de expediente, Acción de Personal, estudio socioeconómico y movimientos guardan automáticamente un borrador por usuario y contexto. El contenido se cifra en PHP con AES-256-GCM y una clave distinta de la utilizada para las sesiones; SQL Server recibe exclusivamente texto cifrado, IV y etiqueta de autenticación. El borrador expira a los 15 días y se elimina solo después de confirmar la transacción principal.

**Dependencia:** ejecutar `database/migracion_gestion_laboral_20260820.sql` antes de habilitar esta función.

## Acción de Personal e historial laboral

La Acción se registra primero como `BORRADOR`; la aprobación es una segunda transacción auditada. Los cambios permanentes cierran el periodo laboral vigente, crean su fotografía histórica y sincronizan el expediente. Lactancia, maternidad, sustitución u otra jornada temporal se registran con vigencia propia sin fragmentar el cargo jerárquico. Los movimientos internos ordinarios cambian solamente el área y conservan el puesto.

### Vigencia permanente y temporal

**Regla:** una acción permanente no admite fecha final. Una acción temporal exige `rige hasta`, conserva una fotografía completa de la situación original y presenta la propuesta solamente durante el rango institucional. Al día siguiente de la fecha final, la vista efectiva vuelve a la situación base sin generar una segunda Acción de Personal.

**Jornadas breves:** el expediente identifica expresamente la jornada y las horas base contractuales. El modo “solo jornada temporal” muestra esa base como lectura y solicita únicamente la novedad, la jornada temporal y sus fechas; no permite guardar “Sin cambio / No aplica”. Maternidad y paternidad se registran como licencias de cero horas; la condición de sustituto propone seis horas. Estas novedades quedan en el historial detallado sin reemplazar cargo, área ni jornada base.

**Operación:** `dbo.sp_th_refrescar_vigencias_laborales` actualiza estados y auditoría; el trabajo SQL Agent `APM - Vigencias laborales` lo ejecuta cada cinco minutos. La restitución visual depende de la fecha institucional de la vista, por lo que sigue funcionando aunque el trabajo se retrase.

**Validación:** `tests/temporal_vigency_db_smoke.php` registra acciones dentro de una transacción exterior, comprueba finalización, situación efectiva y conservación de la base, y finalmente revierte todos los datos de prueba.

## Exportación Excel descargaba XML

**Síntoma:** el botón “Exportar Excel” entregaba SpreadsheetML 2003 con extensión `.xml`; el navegador lo abría como código y no como libro moderno.

**Corrección:** `core/XlsxWriter.php` crea un contenedor ZIP/OOXML real, con extensión `.xlsx`, MIME oficial, varias hojas, cabecera congelada, filtros y tipos adecuados para fechas, importes e identificadores. No depende de Composer ni de `ZipArchive`.

**Validación:** `tests/xlsx_export.php` comprueba firma ZIP y estructura; `tests/full_report_xlsx.php` genera el reporte real de funcionarios. El resultado fue importado, inspeccionado, renderizado y reexportado por un segundo motor de hojas de cálculo, conservando seis hojas y 620 funcionarios.

## Migración de geolocalización quedaba parcial

**Síntoma:** las columnas y restricciones de la página 4 aparecían en SQL Server, pero faltaban el índice filtrado y el registro `2026.08.25.2` del ledger.

**Causa:** la sesión de `sqlcmd` tenía `QUOTED_IDENTIFIER` desactivado; SQL Server exige opciones `SET` específicas al crear índices filtrados.

**Corrección:** `migracion_geolocalizacion_socioeconomica_20260825.sql` declara explícitamente `ANSI_NULLS`, `QUOTED_IDENTIFIER`, `ANSI_PADDING`, `ANSI_WARNINGS`, `CONCAT_NULL_YIELDS_NULL` y `ARITHABORT` en `ON`, con `NUMERIC_ROUNDABORT OFF`. La migración idempotente se reaplicó y su SHA-256 se registró en el ledger.

**Validación:** `tests/socio_geolocation_db_smoke.php` confirmó siete columnas, cuatro restricciones, índice filtrado y ledger con checksum; `tests/migration_ledger.php` confirmó quince migraciones con quince hashes.

## Mapa socioeconómico mostraba un fondo vacío

**Causa:** la política CSP solo autorizaba imágenes locales y bloqueaba silenciosamente los mosaicos externos. Además, el proveedor público inicial podía responder con limitaciones de uso aun cuando devolvía HTTP 200.

**Corrección:** el origen HTTPS configurado para los mosaicos se incorpora de forma controlada a `img-src`; se adoptó como valor predeterminado World Street Map de Esri con CORS, atribución visible y un aviso de conectividad si ningún mosaico logra cargarse. El mapa, marcador, coordenadas y QR continúan funcionando sin exponer archivos privados del expediente.

## Migraciones históricas modificadas después de aplicarse

**Riesgo:** editar una migración ya registrada hace que el archivo deje de representar exactamente el cambio aplicado en SQL Server y vuelve no reproducible una restauración futura.

**Corrección:** se restauraron las cinco migraciones históricas afectadas a sus contenidos ya aplicados. `tests/migration_ledger.php` ahora compara el SHA-256 real de cada archivo con `dbo.th_schema_migrations`; ya no se limita a comprobar que exista una cadena de 64 caracteres. Los cambios nuevos se incorporan únicamente mediante una versión adicional.

**Validación:** el ledger informa diecisiete versiones y diecisiete hashes coincidentes. La migración `2026.08.27.1`, que incorpora el expediente documental firmado y el historial laboral integral, se instaló después de crear y verificar un respaldo `COPY_ONLY` con checksum.

## Campo de género incompatible en el socioeconómico

**Síntoma:** una sustitución mecánica cambió a `sexo` el nombre enviado por el estudio socioeconómico, aunque `dbo.th_estudios_socioeconomicos` conserva la columna `genero`. Esto podía provocar un error al guardar o dejar el dato fuera del PDF.

**Corrección:** el expediente principal continúa convirtiendo `genero` a la columna institucional `th_empleados.sexo`; la precarga del estudio convierte ese valor al contrato `genero`, que se mantiene en su formulario, modelo, tabla y PDF.

**Validación:** `tests/socioeconomico_static.php` comprueba explícitamente el contrato entre expediente, estudio y PDF; las pruebas estáticas y la conexión SQL cifrada terminaron sin fallos.

## Asignación de cuentas sin relación con el puesto

**Riesgo:** un administrador podía seleccionar un rol activo que no correspondiera al cargo registrado del funcionario.

**Corrección:** `th_puesto_rol_mapa` define las excepciones de Director y Analista de Talento Humano. Los demás puestos activos reciben `Funcionario (Lectura)` como alternativa de mínimo privilegio. El procedimiento de alta rechaza funcionarios inactivos, cuentas repetidas y combinaciones puesto/rol incompatibles.

**Validación:** `tests/role_position_db_smoke.php` confirmó cobertura de los 308 puestos activos, rechazo de un rol incompatible y creación compatible dentro de una transacción revertida.

## Perfil operativo de Asistente de Talento Humano

**Necesidad:** el personal asistente debía trabajar sobre expedientes y formatos sin recibir privilegios de gestión de cuentas, matriz RBAC, políticas, bitácora administrativa o prototipos.

**Corrección:** `migracion_rol_asistente_talento_20260827.sql` crea el rol idempotente, define una matriz explícita de mínimo privilegio y lo asocia a `ASISTENTE DE TALENTO HUMANO` y a la denominación histórica `ASITENTE DE TTHH`. La pantalla de alta deshabilita roles incompatibles después de elegir al funcionario y mantiene la validación definitiva dentro de SQL Server.

**Validación:** `tests/rbac_asistente_talento_db_smoke.php` comprueba permisos visibles y contextuales, ausencia de privilegios administrativos, asociación por puesto y control total del Super Administrador.
