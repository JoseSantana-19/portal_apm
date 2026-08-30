# Despliegue seguro — Portal Portuario APM

## Alcance actual

Asistencia, Desempeño y Capacitación permanecen como prototipos por decisión funcional. La página 4 socioeconómica de ubicación es operativa y sus mapas/QR requieren que `PORTAL_PRIVATE_DIR` sea persistente y no público.

## Orden de instalación

1. Publicar la raíz del proyecto, porque el controlador frontal actual es `index.php`. En Apache se debe habilitar `AllowOverride All` y comprobar las reglas de bloqueo de `.htaccess`; en IIS/Nginx deben replicarse expresamente. No apuntar la raíz web a `public/` mientras no exista allí un controlador frontal.
2. Copiar `config/database.example.php` al directorio indicado por `PORTAL_PRIVATE_DIR`, con nombre `database.php`. No copiarlo dentro del repositorio.
3. Aplicar exactamente las migraciones vigentes y en el orden documentado en [`database/README.md`](../database/README.md). La última vigente es `database/migracion_seguridad_auditoria_20260810.sql`.
4. Con una conexión DBA temporal ejecutar `php scripts/sincronizar_nacionalidades.php`; comprobar que informe al menos 200 nacionalidades. Retirar inmediatamente las variables DBA del entorno.
5. Si fuera necesaria una nueva conciliación histórica, colocar temporalmente el CSV fuera del repositorio y del servidor web, ejecutar primero el modo dry-run y eliminarlo inmediatamente después de verificar el resultado. Nunca copiar fuentes con datos personales dentro de `database/`, `public/` o Git.
6. Crear un login SQL propio y asociarlo al rol `portal_app_role`. No ejecutar la aplicación como `sa`.
7. Instalar un certificado confiable en SQL Server y configurar `PORTAL_DB_ENCRYPT=true` y `PORTAL_DB_TRUST_CERT=false`.
8. Configurar `PORTAL_ENV=production`, una clave `PORTAL_TOKEN_KEY` aleatoria y HTTPS obligatorio.
9. Ejecutar `php scripts/preflight.php`, todas las pruebas estáticas y `php tests/run_sql_smoke.php`. No desplegar si cualquiera devuelve un código distinto de cero.

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

## Infraestructura local validada

El espejo local de preproducción utiliza IIS, PHP 8.5 NTS por FastCGI, HTTPS obligatorio y SQL Server con cifrado forzado. `scripts/preflight.php` valida `Encrypt=true`, la cadena del certificado y una conexión efectiva con el usuario de mínimo privilegio. La instalación reproducible está documentada en [Entorno local IIS](ENTORNO_LOCAL_IIS.md).

En el servidor definitivo se deben sustituir los certificados autofirmados locales por certificados emitidos por la PKI institucional o una autoridad confiable, manteniendo `PORTAL_DB_ENCRYPT=true` y `PORTAL_DB_TRUST_CERT=false`.

## Pendiente de validación organizacional

La normalización asignó área y cargo a los 620 expedientes. Al cierre del 06-08-2026 no quedan funcionarios activos en unidades genéricas; la unidad histórica `GENERICO` fue desactivada sin borrar la referencia del expediente inactivo que la utilizó.

Consulte también el [estado vigente del proyecto](ESTADO_PROYECTO.md) y las [incidencias resueltas](INCIDENCIAS_Y_SOLUCIONES.md).
