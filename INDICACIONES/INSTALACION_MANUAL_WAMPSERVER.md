# Instalación manual en WampServer — Portal APM

Guía paso a paso sin usar `SETUP_PROYECTO.bat`/`.ps1`. Pensada para clonar el
repositorio en una máquina nueva con WampServer y dejarlo corriendo sin
sorpresas.

Wamp da Apache + PHP + MySQL/MariaDB. **Este proyecto no usa MySQL** — la base
de datos es SQL Server (vía extensión `sqlsrv`, sin PDO en el portal nativo).
El MySQL que trae Wamp queda sin usar.

---

## 0. Requisitos antes de empezar

| Componente | Detalle |
|---|---|
| WampServer | 64 bits, con PHP 8.0 o superior seleccionado |
| SQL Server | Express o superior, instalado aparte (no lo trae Wamp) |
| SSMS | Recomendado para el paso de creación de base de datos (opcional si usás la alternativa por PHP) |
| ODBC Driver 17 o 18 para SQL Server | Se instala aparte, no viene con Wamp ni con la extensión PHP |
| Extensión `sqlsrv` + `pdo_sqlsrv` para PHP | Se agrega a mano — ver paso 4 |
| Extensión `dbase` para PHP | **Opcional.** Solo si vas a usar el desplegable de funcionarios en Bitácoras → Visitas → Registrar (lee `apps/bitacoras/dbf/rolmaes.DBF`). Ver paso 11. Sin esto el resto del portal funciona igual. |

---

## 1. Clonar el repositorio

Ubicalo directamente dentro de la carpeta `www` de Wamp:

```bash
cd C:\wamp64\www
git clone https://github.com/JoseSantana-19/portal_apm.git
```

Al final vas a tener `C:\wamp64\www\portal_apm`.

---

## 2. Verificar SQL Server

- Si no está instalado: instalar SQL Server Express (gratis) + SSMS.
- Anotar el **nombre de instancia** — lo vas a necesitar en el paso 6.
  Ejemplos: `.\SQLEXPRESS`, `.\MSSQLSERVER`, `localhost`, o un nombre
  personalizado si lo pusiste vos al instalar.
- Confirmar que el servicio "SQL Server (NOMBRE_INSTANCIA)" está iniciado
  (Servicios de Windows, o SQL Server Configuration Manager).

---

## 3. Instalar el ODBC Driver para SQL Server

Necesario para que la extensión `sqlsrv` de PHP pueda conectar. No lo trae
ni Wamp ni el paquete de la extensión — es un instalador aparte de Microsoft.

1. Buscar "ODBC Driver 17 for SQL Server download" (o 18) en el sitio de
   Microsoft (`learn.microsoft.com/sql/connect/odbc/download-odbc-driver-for-sql-server`).
2. Descargar el `.msi` para Windows x64 e instalarlo (siguiente, siguiente).
3. No requiere configuración adicional — la extensión `sqlsrv` lo usa por
   debajo automáticamente.

---

## 4. Activar la extensión `sqlsrv` en el PHP de Wamp

El paso donde más se traba la instalación. Con cuidado acá:

1. Click en el icono de Wampmanager (bandeja del sistema) → confirmar qué
   versión de PHP está activa (ej. PHP 8.2.x) y que sea **TS (Thread Safe)**
   — Wamp usa builds TS para el módulo de Apache.
2. Ir a `learn.microsoft.com/sql/connect/php/download-drivers-php-sql-server`
   y descargar el paquete de drivers de Microsoft para PHP + SQL Server.
3. Del zip descargado, identificar los 2 archivos que coincidan con tu PHP:
   arquitectura **x64**, build **TS** (thread safe), versión de PHP igual a
   la que tiene Wamp activa. Ejemplo para PHP 8.2: `php_sqlsrv_82_ts_x64.dll`
   y `php_pdo_sqlsrv_82_ts_x64.dll`.
4. Copiar esos 2 archivos a:
   `C:\wamp64\bin\php\phpX.Y.Z\ext\` (reemplazando `phpX.Y.Z` por tu versión
   real de PHP).
5. Editar el `php.ini` de esa misma versión de PHP
   (`C:\wamp64\bin\php\phpX.Y.Z\php.ini`, o desde Wampmanager → PHP →
   php.ini) y agregar, junto a las demás líneas `extension=`:
   ```ini
   extension=php_sqlsrv_82_ts_x64
   extension=php_pdo_sqlsrv_82_ts_x64
   ```
   (ajustar el nombre exacto al de los archivos que copiaste).
6. Reiniciar todos los servicios de Wamp (click izquierdo en Wampmanager →
   "Restart All Services").
7. Verificar desde una consola:
   ```bash
   C:\wamp64\bin\php\phpX.Y.Z\php.exe -m | findstr sqlsrv
   ```
   Tiene que listar `sqlsrv` y `pdo_sqlsrv`. Si no aparece nada, revisar que
   el `.ini` editado sea el que `php_ini_loaded_file()` reporta realmente
   cargado (Wamp a veces tiene más de un `php.ini` por versión — el que usa
   Apache puede diferir del que usa la CLI si los tocaste por separado).

---

## 5. Habilitar `mod_rewrite` en Apache

El portal depende de esto para que todas las rutas (`/login`, `/dashboard`,
etc.) funcionen — sin esto, solo la home carga y el resto da 404.

1. Wampmanager (icono) → Apache → Apache modules → marcar `rewrite_module`.
2. Wamp reinicia Apache solo al marcarlo.

---

## 6. Configurar la conexión a SQL Server

Desde la carpeta del proyecto:

```bash
cd C:\wamp64\www\portal_apm
copy config\connections.example.php config\connections.php
```

Editar `config\connections.php`:

```php
'server_default' => '.\SQLEXPRESS',   // tu instancia del paso 2

'credentials' => [
    'user' => '',   // vacío = Autenticación de Windows (recomendado)
    'pass' => '',
],
```

Si usás autenticación SQL (usuario/contraseña) en vez de Windows, completar
`user`/`pass` ahí.

No hace falta tocar `config/app.php` — `APP_URL` está en modo `'auto'` y
detecta Wamp solo (protocolo, host, puerto, subcarpeta).

---

## 7. Configurar el módulo Control de Bienes (app embebida)

```bash
copy apps\control_bienes\.env.example apps\control_bienes\.env
```

Dejar `DB_HOST` vacío en el `.env` — hereda el servidor de
`config/connections.php`. No hace falta tocar nada más ahí.

`apps/talento_humano` no necesita `.env` propio — ya lee directamente
`config/connections.php`.

---

## 8. Crear la base de datos principal (`PORTAL_APM`)

El script vive en `Z.BASES DE DATOS/PORTAL_APM_COMPLETO.sql`. Es compatible
con SQL Server 2014 en adelante. **Atención:** hace DROP + CREATE — si la
base `PORTAL_APM` ya existe en tu instancia, la recrea y borra sus datos.

**Opción A — con SSMS:**
1. Abrir SQL Server Management Studio, conectarse a tu instancia.
2. Abrir el archivo `Z.BASES DE DATOS\PORTAL_APM_COMPLETO.sql`.
3. Ejecutar (F5). Tarda 1-2 minutos.

**Opción B — sin SSMS, con el PHP de Wamp (ya tiene `sqlsrv` del paso 4):**
```bash
C:\wamp64\bin\php\phpX.Y.Z\php.exe "C:\wamp64\www\portal_apm\db\run_sql.php" "C:\wamp64\www\portal_apm\Z.BASES DE DATOS\PORTAL_APM_COMPLETO.sql" .\SQLEXPRESS
```
(cambiar `.\SQLEXPRESS` por tu instancia; agregar usuario/contraseña como
4to y 5to argumento si no usás Autenticación de Windows). Debe terminar con
`OK: N batches ejecutados correctamente.`

Con esto ya alcanza para entrar al portal y usar Central (login, permisos,
menú, administración, Control de Acceso).

---

## 9. Bases de datos de los módulos integrados (opcional)

Para que **Talento Humano**, **Control de Bienes** y **Bitácoras** funcionen
con datos reales (no solo el hub de Central), hacen falta además las bases
`Talento_Humano`, `inventario`, `PortuariaDemo` **y** `PortuariaExterna`
(esta última son los maestros de funcionarios/departamentos que usa
Bitácoras) — cada una las mantiene el equipo dueño de ese módulo por
separado.

En `Z.BASES DE DATOS/` hay un **esquema de referencia** de cada una
(`Talento_Humano.sql`, `inventario.sql`, `PortuariaDemo.sql`,
`PortuariaExterna.sql`) — se ejecutan igual que en el paso 8 (SSMS o
`db\run_sql.php`), una vez por cada archivo, contra la misma instancia. Los
5 scripts (el de PORTAL_APM y estos 4) son compatibles con SQL Server 2014
en adelante y no dependen del orden entre sí.

`Talento_Humano.sql` trae **datos reales completos** (dump de una instancia
de referencia). `inventario.sql`, `PortuariaDemo.sql` y `PortuariaExterna.sql`
son solo estructura (tablas, vistas, SPs), **sin datos** — sirven para
levantar una base vacía y consistente si no tenés un backup real a mano,
pero no reemplazan el backup/datos reales del equipo de cada módulo.

Si no las creás, el portal arranca igual — los paneles de esos módulos
muestran vacío/error al intentar leer esas bases, el resto de Central sigue
funcionando normal.

### 9.1. Migraciones pendientes de Talento Humano (obligatorio si creaste `Talento_Humano.sql`)

`Talento_Humano.sql` es una foto tomada en un momento dado — desde entonces
el módulo TH sumó login propio + bloqueo de cuenta escalado + MFA/TOTP +
auditoría inmutable, y esos cambios de esquema **todavía no están
incorporados al script base**. Si no corrés lo siguiente, TH abre pero el
login propio del módulo, el bloqueo por intentos fallidos y el MFA van a
fallar con "invalid column name" o similar.

Ejecutar, **en este orden**, contra la base `Talento_Humano` (mismo método
que el paso 8 — SSMS o `db\run_sql.php`; todos son idempotentes, no rompen
nada si se corren dos veces):

1. `apps/talento_humano/database/migracion_critica_2026.sql`
2. `apps/talento_humano/database/migracion_culminacion_critica_2026.sql`
3. `apps/talento_humano/database/migracion_ciclo_laboral_2026.sql` *(requiere el paso 2 antes — así lo indica su propio encabezado)*
4. `apps/talento_humano/database/migracion_calidad_busqueda_2026.sql`
5. `apps/talento_humano/database/migracion_formatos_oficiales_2026.sql`
6. `apps/talento_humano/database/migracion_mejoras_operativas_2026.sql`
7. `apps/talento_humano/database/migracion_cierre_produccion_20260806.sql` *(requiere el paso 5 antes — agrega constraints a tablas que crea ese archivo)*
8. `apps/talento_humano/database/migracion_seguridad_auditoria_20260810.sql`
9. `apps/talento_humano/database/patch_reconciliacion_rolmaes_2026.sql`

No hace falta tocar `migra_accion_personal.sql`, `patch_v3_objetos_faltantes.sql`,
`rectificacion_v2_APM.sql` ni `vistas_sps_restantes.sql` — esos 4 ya están
incorporados dentro de `Talento_Humano.sql`.

TH además **crea solo, la primera vez que se accede**, una carpeta privada
`apps/.portal-portuario-private/` con una clave de cifrado (`auth-token.key`,
usada para el MFA) — no requiere ningún `.env` para esto, solo que Apache
tenga permiso de escritura dentro de `apps/`. En Wamp esto funciona sin
tocar nada.

### 9.2. Migraciones pendientes de Control de Bienes (obligatorio si creaste `inventario.sql`)

Mismo caso: `inventario.sql` es anterior a 3 ajustes de esquema. Ejecutar
(orden entre ellos no importa, no dependen uno del otro) contra la base
`inventario`:

- `apps/control_bienes/database/migrations/inv_20260727_modelo_inventario.sql`
- `apps/control_bienes/database/migrations/inv_20260727_centros_consumo_personal.sql`
- `apps/control_bienes/database/migrations/th_20260727_responsables_inventario.sql`

(Los `.php` en esa misma carpeta — `importar_activos_dbf.php`,
`corregir_categorias_activos_dbf.php` — son utilitarios de importación de un
inventario legacy en formato DBF, no hacen falta en una instalación nueva.)

---

## 10. Probar

Abrir `http://localhost/portal_apm/login` (o `http://localhost:8080/portal_apm/login`
si tu Apache de Wamp escucha en otro puerto).

El login del portal es **solo por cédula** (no hay campo de usuario/nombre
de usuario). Credenciales iniciales del único usuario que trae el seed
(cuenta técnica `admin`, sin vínculo a un empleado real):
- Cédula: `1234567777`
- Contraseña: `Apm2024*`

Cambiar la contraseña desde `/cambiar-contrasena` apenas entrás. El resto de
las cuentas del portal se crean únicamente desde Talento Humano
(`/admin/usuarios/desde-th`), no hay alta manual de usuarios.

---

## 11. Extensión `dbase` para PHP (opcional — solo Bitácoras → Visitas → Registrar)

El desplegable de funcionarios en Bitácoras lee un archivo legacy Visual
FoxPro (`apps/bitacoras/dbf/rolmaes.DBF`, ya viene en el repo con datos)
usando la extensión PHP `dbase`. Sin esto el resto del portal — incluido el
resto de Bitácoras — funciona normal; solo ese desplegable específico queda
vacío.

1. Confirmar versión/arquitectura/TS del PHP de Wamp (mismo dato que
   usaste en el paso 4).
2. Descargar la extensión `dbase` para Windows desde PECL
   (`pecl.php.net/package/dbase`), build que coincida con esa versión de
   PHP (x64, TS). Si no hay build exacto para tu versión de PHP, probar la
   más cercana.
3. Copiar `php_dbase.dll` a `C:\wamp64\bin\php\phpX.Y.Z\ext\`.
4. Agregar en el `php.ini` de esa versión: `extension=dbase` (o
   `extension=php_dbase.dll` si el nombre corto no carga).
5. Wampmanager → "Restart All Services".
6. Verificar: `C:\wamp64\bin\php\phpX.Y.Z\php.exe -m | findstr dbase`.

Más detalle (con capturas para Laragon, mismo procedimiento en Wamp
cambiando las rutas) en `apps/bitacoras/dbf/INSTALAR_EXTENSION_DBASE.md`.

---

## Problemas comunes

| Síntoma | Causa probable | Solución |
|---|---|---|
| Página en blanco o error 500 | `sqlsrv` no cargó, o `mod_rewrite` no está activo | Revisar paso 4 y 5; ver el log de Apache en `C:\wamp64\logs\apache_error.log` |
| `/login` da 404, pero la home carga | Falta `rewrite_module` | Paso 5 |
| "Falta config/connections.php" | No se copió el `.example.php` | Paso 6 |
| Error de conexión SQL / "Login failed" | Instancia mal escrita en `connections.php`, o servicio de SQL Server detenido | Verificar paso 2 y 6; confirmar el nombre exacto de instancia en SQL Server Configuration Manager |
| "No se pudo cargar la extension sqlsrv" al correr `db\run_sql.php` | DLL copiada no coincide con la versión/arquitectura/TS de ese PHP | Repetir paso 4, prestar atención a TS vs NTS y al número de versión exacto |
| Ícono de Wamp queda naranja (no verde) | Algún servicio no arrancó (puerto 80 ocupado por otro programa, típicamente Skype/IIS) | Cambiar el puerto de Apache desde Wampmanager, o liberar el puerto 80 |
| Bienes/Talento Humano/Portuaria vacíos | Sus bases de datos no existen todavía | Paso 9, o pedir el backup real al equipo de ese módulo |
| TH abre pero el login propio del módulo, bloqueo por intentos fallidos o MFA fallan con "Invalid column name" | Faltan las migraciones de TH posteriores al script base | Paso 9.1 |
| Control de Bienes: error de columna al guardar centros de consumo/responsables/tipo de bien | Faltan las migraciones de Bienes posteriores al script base | Paso 9.2 |
| "credenciales incorrectas" al entrar con `admin`/`Apm2024*` | Se está probando con un "usuario" en vez de la cédula | El login es solo por cédula: `1234567777` |
