# Despliegue seguro — Portal Portuario APM

## Alcance actual

Asistencia, Desempeño y Capacitación permanecen como prototipos por decisión funcional. La página 4 socioeconómica de ubicación es operativa y sus mapas/QR requieren que `PORTAL_PRIVATE_DIR` sea persistente y no público.

## Orden de instalación

1. Publicar la raíz del proyecto, porque el controlador frontal actual es `index.php`. En Apache se debe habilitar `AllowOverride All` y comprobar las reglas de bloqueo de `.htaccess`; en IIS/Nginx deben replicarse expresamente. No apuntar la raíz web a `public/` mientras no exista allí un controlador frontal.
2. Copiar `config/database.example.php` al directorio indicado por `PORTAL_PRIVATE_DIR`, con nombre `database.php`. No copiarlo dentro del repositorio.
3. Aplicar exactamente las 20 migraciones vigentes y en el orden documentado en [`database/README.md`](../database/README.md). La última vigente es `database/migracion_integridad_periodos_20260830.sql` (`2026.08.30.1`).
4. Con una conexión DBA temporal ejecutar `php scripts/sincronizar_nacionalidades.php`; comprobar que informe al menos 200 nacionalidades. Retirar inmediatamente las variables DBA del entorno.
5. Si fuera necesaria una nueva conciliación histórica, colocar temporalmente el CSV fuera del repositorio y del servidor web, ejecutar primero el modo dry-run y eliminarlo inmediatamente después de verificar el resultado. Nunca copiar fuentes con datos personales dentro de `database/`, `public/` o Git.
6. Crear un login SQL propio y asociarlo al rol `portal_app_role`. No ejecutar la aplicación como `sa`.
7. Instalar un certificado confiable en SQL Server y configurar `PORTAL_DB_ENCRYPT=true` y `PORTAL_DB_TRUST_CERT=false`.
8. Configurar `PORTAL_ENV=production`, una clave `PORTAL_TOKEN_KEY` aleatoria y HTTPS obligatorio.
9. Ejecutar `php scripts/preflight.php`, todas las pruebas estáticas, `php tests/migration_ledger.php`, `php tests/run_sql_smoke.php` y la compuerta `deployment/run-local-validation-gate.ps1`. No desplegar si cualquiera devuelve un código distinto de cero.
10. Completar el recorrido UAT con cuentas nominativas y obtener la aceptación de Talento Humano y Seguridad de la Información.

## Login SQL de aplicación

El DBA debe generar una clave distinta por ambiente y ejecutar, sustituyendo los marcadores sin guardar el secreto en archivos versionados:

```sql
USE master;
CREATE LOGIN [portal_app] WITH PASSWORD = '<SECRETO_ALEATORIO>', CHECK_POLICY = ON, CHECK_EXPIRATION = ON;
USE Talento_Humano;
CREATE USER [portal_app] FOR LOGIN [portal_app];
ALTER ROLE portal_app_role ADD MEMBER [portal_app];
```

Después se actualiza la configuración privada y se comprueba que `IS_SRVROLEMEMBER('sysadmin')` devuelva `0` para esa conexión.

## Verificaciones obligatorias

- Un usuario de lectura recibe HTTP 403 al intentar editar, eliminar o abrir administración.
- Cambiar rol, estado o clave invalida las sesiones existentes.
- Los directorios `database`, `SQL`, `core`, `docs`, `tmp`, `output` y `.git` no son descargables.
- Las consultas de expedientes, formatos, reportes y exportaciones aparecen en `th_logs_auditoria`.
- Una Acción de Personal nace en `BORRADOR`; solo `APROBADO` actualiza empleado e historial.
- Los PDF oficiales conservan dos páginas para Acción de Personal y cuatro para socioeconómico; la cuarta debe mostrar mapa o reserva visual, coordenadas, referencia y QR cuando existan datos.
- El botón verde del Directorio entrega el Formulario Principal completo de dos páginas y no la ficha resumida anterior.
- La búsqueda encuentra cédulas parciales y combinaciones de nombres/apellidos sin diferenciar mayúsculas, minúsculas ni tildes.
- Los movimientos grupales rechazan selecciones inválidas y actualizan todos los empleados o ninguno.
- El ledger contiene exactamente 20 migraciones y cada checksum coincide con el archivo versionado.
- El régimen LOSEP genera la Acción de Personal completa; Código del Trabajo genera el formulario abreviado con serie `CdgT`.
- Los documentos impresos, firmados y escaneados se cargan como PDF privado y aparecen en el historial integral.

## Infraestructura local validada

El espejo local de preproducción utiliza IIS, PHP 8.5 NTS por FastCGI, HTTPS obligatorio y SQL Server con cifrado forzado. `scripts/preflight.php` valida `Encrypt=true`, la cadena del certificado y una conexión efectiva con el usuario de mínimo privilegio. La instalación reproducible está documentada en [Entorno local IIS](ENTORNO_LOCAL_IIS.md).

En el servidor definitivo se deben sustituir los certificados autofirmados locales por certificados emitidos por la PKI institucional o una autoridad confiable, manteniendo `PORTAL_DB_ENCRYPT=true` y `PORTAL_DB_TRUST_CERT=false`.

## Certificados para el servidor definitivo

El certificado local actual no debe copiarse a producción. Un certificado web identifica un DNS concreto y su clave privada debe originarse, preferentemente, en el servidor que la custodiará.

1. Definir primero el FQDN definitivo, por ejemplo `portal-talento.institucion.gob.ec`, y crear su registro DNS.
2. En el servidor IIS definitivo abrir **Administrador de IIS → Certificados de servidor → Crear solicitud de certificado**. La solicitud debe incluir el FQDN como nombre común y SAN, organización, localidad y país institucionales.
3. Enviar el archivo CSR a la PKI institucional o autoridad certificadora. La clave privada permanece en el servidor; el CSR puede trasladarse sin exponerla.
4. Completar la solicitud en el mismo IIS, instalar la cadena intermedia/raíz y enlazar el certificado al sitio HTTPS en el puerto 443.
5. Solicitar un certificado separado para SQL Server, con `Server Authentication`, SAN coincidente con `PORTAL_DB_SERVER` y clave accesible únicamente para la cuenta del servicio SQL.
6. Ejecutar `scripts/preflight.php`, las comprobaciones de certificados y la compuerta completa.

Solo tendría sentido generar la clave en este equipo y exportar un PFX si la institución ya definió el DNS, autoriza expresamente el traslado de la clave privada y dispone de un canal seguro y una contraseña de exportación independiente. La opción recomendada es generar el CSR directamente en el servidor definitivo.

## SMTP institucional

Configure mediante variables de IIS/FastCGI los campos `PORTAL_SMTP_*` documentados en `.env.example`. Producción exige `tls` o `ssl` y `PORTAL_SMTP_VERIFY_PEER=true`. Primero pruebe un relay de preproducción y destinatarios controlados; después habilite `PORTAL_SMTP_ENABLED=true`. Nunca guarde la contraseña SMTP en Git, `web.config` versionado ni capturas de pantalla.

## Pipeline de preproducción

El workflow `.github/workflows/preproduction-gate.yml` requiere un runner Windows autohospedado con las etiquetas `self-hosted`, `windows` y `portal-apm-preprod`. La cuenta del servicio del runner debe poder leer los certificados locales, ejecutar IIS/SQL y escribir evidencia en `C:\ProgramData\PortalAPM`. Proteja el environment de GitHub `preproduction` con aprobación y permita ejecutar el workflow únicamente a responsables autorizados.

## Pendiente de validación organizacional

La normalización asignó área y cargo a los 620 expedientes. Al cierre del 06-08-2026 no quedan funcionarios activos en unidades genéricas; la unidad histórica `GENERICO` fue desactivada sin borrar la referencia del expediente inactivo que la utilizó.

Consulte también el [estado vigente del proyecto](ESTADO_PROYECTO.md) y las [incidencias resueltas](INCIDENCIAS_Y_SOLUCIONES.md).

La lista completa de software, extensiones y archivos que deben descargarse o copiarse en otro equipo se encuentra en [Instalación en un equipo nuevo](INSTALACION_NUEVO_EQUIPO.md).
