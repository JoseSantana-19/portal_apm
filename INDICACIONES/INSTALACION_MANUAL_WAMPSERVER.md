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

Para que **Talento Humano**, **Control de Bienes** y **Portuaria** funcionen
con datos reales (no solo el hub de Central), hacen falta además las bases
`Talento_Humano`, `inventario` y `PortuariaDemo` — cada una las mantiene el
equipo dueño de ese módulo por separado.

En `Z.BASES DE DATOS/` hay un **esquema de referencia** de cada una
(`Talento_Humano.sql`, `inventario.sql`, `PortuariaDemo.sql`) — solo
estructura (tablas, vistas, SPs), **sin datos**. Sirven para levantar una
base vacía y consistente si no tenés un backup real con datos a mano, pero
no reemplazan el backup/datos reales del equipo de cada módulo. Se ejecutan
igual que en el paso 8 (SSMS o `db\run_sql.php`), una vez por cada archivo,
contra la misma instancia. Los 4 scripts (el de PORTAL_APM y estos 3) son
compatibles con SQL Server 2014 en adelante.

Si no las creás, el portal arranca igual — los paneles de esos módulos
muestran vacío/error al intentar leer esas bases, el resto de Central sigue
funcionando normal.

---

## 10. Probar

Abrir `http://localhost/portal_apm/login` (o `http://localhost:8080/portal_apm/login`
si tu Apache de Wamp escucha en otro puerto).

Credenciales iniciales:
- Usuario: `admin`
- Contraseña: `Apm2024*`

Cambiar la contraseña desde `/cambiar-contrasena` apenas entrás.

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
