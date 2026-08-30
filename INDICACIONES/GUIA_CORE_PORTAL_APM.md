# Guía técnica: el CORE nativo de Portal APM

**Última actualización:** 2026-08-29 · Cubre el framework propio del portal — no los módulos embebidos (TH/Bienes/Bitácoras, que tienen su propio `core/` independiente, ver `GUIA_MODULOS_NUEVOS_Y_ACTUALIZACIONES.md`) ni las funciones de negocio del módulo Central (ver `GUIA_CENTRAL_FUNCIONES.html`).

## 0. Qué es "el CORE" en este proyecto

Portal APM **no tiene lógica de negocio propia** — es un hub: login, sesión, permisos por rol, menú, y el framework mínimo que hace correr los controladores de `modules/Central/`. Ese framework mínimo — más la infraestructura compartida que usan TODAS las apps del sistema (portal + TH + Bienes + Bitácoras) — es el CORE. Vive en 6 lugares:

```
portal_apm/
├── .htaccess / web.config     ← enrutamiento a nivel de servidor, ver §1
├── index.php                  ← front controller (bootstrap completo)
├── routes.php                 ← ~100 rutas planas GET/POST
├── config/
│   ├── app.php                ← constantes de entorno (edita SOLO este)
│   ├── connections.php        ← fuente única de conexión SQL (cross-app, NO se sube a git)
│   ├── connections.example.php
│   ├── header.php              ← legacy, cabecera del módulo Portuaria pre-migración (ver §9)
│   └── export_analytics_env.php ← script CLI, ver §9
├── core/                      ← 11 clases, ~45 KB en total
│   ├── Router.php
│   ├── Controller.php         ← clase base de todos los controladores
│   ├── Model.php               ← clase base de todos los modelos
│   ├── View.php
│   ├── Database.php            ← singleton sqlsrv
│   ├── ModuleSecurity.php      ← SSO tokens (muerto), permisos MOIS (muerto), auditoría (real)
│   ├── MfaHelper.php           ← TOTP
│   ├── CatalogoModulos.php     ← caché de CORE_Modulos
│   ├── SyncPermisosModulo.php  ← replica permisos hacia TH/Bienes
│   ├── Env.php                 ← vestigial, ver §7
│   └── SqlSrvStatement.php     ← vestigial, ver §7
├── helpers/                   ← funciones estáticas, cargadas siempre
│   ├── security_helper.php     ← CSRF, peppering de contraseñas, headers HTTP
│   ├── session_helper.php
│   ├── url_helper.php
│   ├── form_helper.php
│   ├── hub_charts_helper.php
│   ├── module_stats_helper.php
│   └── polyfills_php74.php     ← ver GUIA de compatibilidad PHP en DOCUMENTACION_SISTEMA.md §2
├── libs/                      ← infraestructura PHP compartida entre TODAS las apps, ver §6-bis
│   ├── SsoClient.php            ← el SSO REAL y activo (ModuleSecurity trae uno muerto, no confundir)
│   ├── XlsxWriter.php
│   ├── ReportPdf.php
│   └── fpdf/
├── js/                         ← infraestructura de cliente compartida, ver §8-bis
│   ├── password-hash.js         ← SHA-256 en navegador antes de cualquier submit con contraseña
│   ├── inactivity-warning.js    ← el aviso de inactividad "centralizado" real (Portal/TH/Bienes/Bitácoras)
│   └── alerts.js                ← window.PortalAlert, envoltorio de SweetAlert2
├── db/                        ← scripts .sql sueltos + utilidades PHP de operación (§9-bis) — NO es "core" de runtime
├── apis/                      ← vacío hoy, reservado (mencionado en el bootstrap de index.php)
├── includes/                  ← vacío hoy, sin uso
└── controllers/ · models/ · views/  ← legacy pre-reorganización a modules/, casi vacíos hoy (1, 1 y 9 archivos), fallback de compatibilidad en Router/View §1b/§5
```

Nada de esto usa Composer ni PDO. La conexión a SQL Server es **sqlsrv nativo** en todo el árbol — es una decisión de arquitectura, no una limitación temporal (ver §4).

---

## 1. Ciclo de vida de un request — paso a paso real

### 1a. Antes de que `index.php` corra: enrutamiento a nivel de servidor

Dos archivos de configuración de servidor web, uno por plataforma soportada, **con una discrepancia real entre ellos**:

- **`.htaccess`** (Apache/XAMPP/WampServer): `RewriteCond %{REQUEST_FILENAME} !-d`, `!-f`, y si no es ni directorio ni archivo real, reescribe TODO a `index.php` — **sin** parámetro de query string. `index.php` resuelve la ruta leyendo `$_SERVER['REQUEST_URI']` directo, así que esto calza perfecto.
- **`web.config`** (IIS): la regla reescribe a `index.php?url={R:1}` — **con** un parámetro `url` en la query string. Pero `index.php` **nunca lee `$_GET['url']`** en ningún punto del bootstrap (confirmado leyendo el archivo completo) — arma la ruta exclusivamente desde `REQUEST_URI`. Si el sistema corriera de verdad bajo IIS hoy, el parámetro `url` que manda `web.config` sería simplemente ignorado (no rompe nada porque `REQUEST_URI` de todos modos incluye el path completo), pero es evidencia de que `web.config` quedó desactualizado respecto a una versión anterior del router que sí usaba `$_GET['url']` (patrón que sí sigue vivo en `apps/control_bienes`, que router por `?route=`). No se ha verificado un despliegue real bajo IIS en este proyecto — si se llega a necesitar, revisar esto primero.

### 1b. `index.php` — orden exacto de ejecución

`index.php` es el único punto de entrada real (una vez que el servidor ya reescribió la URL hacia acá). Orden exacto:

1. **Polyfills primero** (`helpers/polyfills_php74.php`) — antes que cualquier otra cosa, porque el resto del código usa `str_starts_with()`/`str_contains()`/`str_ends_with()` sin guardas.
2. **Modo `php -S`** (servidor embebido de desarrollo): si la URL pedida es un archivo real en disco, `return false` y deja que el servidor lo sirva tal cual (assets, páginas legacy, y en teoría `apis/*.php` — aunque hoy `apis/` está vacío). En Apache esto lo hace `.htaccess` con `RewriteCond %{REQUEST_FILENAME} !-f`; el bloque en `index.php` replica el mismo comportamiento para quien prueba sin XAMPP.
3. **Bootstrap de configuración**: `core/Env.php` (se carga pero no se usa, ver §7) → `config/app.php`, que define `ROOT`, `APP_URL` (autodetectado o fijo), `DEFAULT_TIMEZONE`, `SESSION_TIMEOUT`, y lee `config/connections.php` para espejar `DB_SERVER`/`DB_NAME`/`DB_USER`/`DB_PASS`/etc. como constantes.
4. **`error_reporting`** según `DEBUG_MODE` (constante en `app.php`, `true` en desarrollo).
5. **Sesión PHP**: `session.gc_maxlifetime` = `SESSION_TIMEOUT`, `session.cookie_lifetime = 0` (cookie de sesión, no persistente), `session_start()`.
6. **Autoload de las 9 clases core** vía `require_once` explícito (no hay autoloader para `core/` — es una lista fija, a propósito: son pocas clases y cargarlas todas siempre es más simple que un autoloader para 9 archivos).
7. **Autoloader real** (`spl_autoload_register`) — solo para clases de **módulos**: busca en `modules/*/controllers`, `modules/*/models`, y los directorios legacy `controllers/*`/`models/*` (pre-reorganización a `modules/`). Primera coincidencia gana.
8. **Helpers** cargados siempre (no autoload — son 5 archivos, se listan a mano en `index.php`). `libs/*` (§6-bis) NO se carga acá — cada controlador que necesita `SsoClient`/`XlsxWriter`/`ReportPdf` hace su propio `require` puntual.
9. **Rutas + dispatch**: `new Router()`, `require routes.php` (puebla `$router` con `->get()`/`->post()`), luego `$router->resolve($uri, $method)` dentro de un `try/catch (Throwable)` que en producción muestra un 500 genérico y en `DEBUG_MODE` vuelca el stack trace completo.
10. **Normalización de URI**: antes de `resolve()`, se le quita a `$uri` el subdirectorio del script (`dirname($_SERVER['SCRIPT_NAME'])`) — así `/portal_apm/dashboard` y `/dashboard` resuelven igual sin importar si corrés en la raíz de un dominio o en una subcarpeta de XAMPP/Wamp.

**No hay contenedor de dependencias, no hay middleware pipeline.** Cada `Controller` resuelve sus propias dependencias llamando a `Database::getInstance()` cuando las necesita — es deliberadamente simple.

---

## 2. `Router` — mapeo de URLs

`core/Router.php`, 62 líneas. Dos métodos de registro:

```php
$router->get('/dashboard', 'DashboardController@index');
$router->post('/login',    'AuthController@login');
```

- **Match exacto primero.** Si no hay, recorre las rutas con `{param}` y las convierte a regex (`{id}` → `([^/]+)`) — sin caché de compilación, se recompila cada request (irrelevante en la práctica: son ~100 rutas).
- **Cast automático de parámetros numéricos**: un `{id}` que matchea solo dígitos (`ctype_digit`) se castea a `int` antes de pasarlo al método — así un controlador puede declarar `public function ver(int $id)` sin `(int)` manual.
- **`dispatch()`** instancia el controlador (`new $class()`, sin inyección de nada — el constructor de `Controller` no recibe argumentos) y llama al método con `...$castParams` (spread).
- **404**: si nada matchea, responde JSON `{"error":"Not found"}` para requests AJAX (detectado por `X-Requested-With: XMLHttpRequest`) o renderiza `modules/Central/views/errors/404.php` para el resto.

No hay grupos de rutas, prefijos, ni "middleware" declarativo — el control de acceso vive **dentro de cada controlador**, llamando a `$this->requireAuth()` / `$this->requireLevel()` como primera línea del método (ver §3).

---

## 3. `Controller` — la clase base real (todo pasa por acá)

`core/Controller.php`, clase abstracta, 195 líneas. Todo controlador de `modules/Central/controllers/` extiende de esta.

### Constructor
```php
public function __construct() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    SecurityHelper::setSecurityHeaders();
}
```
Se ejecuta en **cada** instanciación (o sea, en cada request que matchea una ruta) — `session_start()` es defensivo (ya se llamó en `index.php`, pero un controlador podría instanciarse fuera del flujo normal en tests) y los headers de seguridad (`X-Frame-Options`, CSP, etc.) se fijan siempre, autenticado o no.

### `requireAuth(?string $module = null)`
El guard de autenticación básico. Si no hay `$_SESSION['user_id']`, redirige a `/login` (o responde 401 JSON si es AJAX). El parámetro `$module` está declarado pero **no se usa dentro del método** — es vestigial de un diseño anterior; el control por módulo real pasa por `requireLevel()` con el parámetro `$nodo`.

**Timeout de inactividad real**: no es un valor fijo. Llama a `resolveInactividad()`, que:
1. Si hay un valor cacheado en sesión hace menos de 5 minutos, lo usa (evita golpear la BD en cada request).
2. Si no, consulta `dbo.fn_InactividadSegundos(id_usuario, 'CENTRAL')` y `fn_InactividadAvisoSegundos(...)` — funciones SQL Server que resuelven la **cascada usuario → módulo → global** configurable desde `/admin/inactividad` (mismas funciones que usan TH, Bienes y Bitácoras — es el mecanismo real detrás del "sistema de inactividad centralizado" documentado en memoria del proyecto; el front-end de ese aviso es `js/inactivity-warning.js`, ver §8-bis).
3. Si la BD no responde, cae a 1800s/60s por defecto — nunca deja al usuario sin timeout por un error transitorio.

Si `time() - $_SESSION['last_activity'] > $timeout`, destruye la sesión y redirige con `?timeout=1`. Si no venció, **actualiza `last_activity` en cada request** — es un timeout deslizante, no absoluto.

### `requireLevel(int $minLevel, ?array $nodo = null)` — el control de acceso real

```php
protected function requireLevel(int $minLevel, ?array $nodo = null): void {
    $this->requireAuth();
    if (($_SESSION['nivel_jerarquia'] ?? 0) < $minLevel) $this->denyAccess();
    if ($nodo !== null && !$this->tienePermisoNodo(...$nodo)) $this->denyAccess();
}
```

Dos capas de control, ambas opcionales de combinar:

1. **Nivel de jerarquía** (`$_SESSION['nivel_jerarquia']`, entero 1-4, poblado en login desde `CORE_Usuarios`) — el check "grueso": ¿este usuario tiene AL MENOS este nivel jerárquico? No distingue módulo ni pantalla.
2. **Nodo MOIS granular** (opcional, la tupla `$nodo`) — el check fino: `[id_modulo, opcion, items, subitems, nivel_crud_minimo]`, consultado contra `dbo.fn_TienePermisoNodo(...)` (la misma función SQL que intenta replicar `ModuleSecurity::checkAccess()`, ver §6 — pero esa versión está muerta, la real es esta). `nivel_crud`: 1=Ver, 2=Crear, 3=Editar, 4=Total. Un controlador que llama `requireLevel(2, [11, 3, 0, 0, 2])` exige nivel jerárquico ≥2 **y** permiso de "Crear" sobre el nodo (módulo TH, opción 3) configurado para el rol de ese usuario específico en `/admin/roles/{id}/permisos`.

**Sin el parámetro `$nodo`**, el comportamiento es el histórico (solo nivel jerárquico) — así es explícito en el código qué pantallas ya migraron a permisos granulares y cuáles siguen en el modelo viejo.

### CSRF, JSON, redirect, sanitización
- `csrfToken()`/`verifyCsrf()` delegan 100% a `SecurityHelper` (§8) — el Controller no reimplementa nada, solo expone atajos `protected`.
- `json($data, $status=200)`: siempre `exit` después de imprimir — nunca sigue ejecutando código del controlador tras un `json()`.
- `redirect($path)`: si `$path` no empieza con `http(s)://`, lo ancla a `APP_URL` — así funciona igual corriendo en la raíz de un dominio (`php -S`) o en `/portal_apm` (XAMPP/Wamp).
- `input($key, $source='both', $default=null)`: lee de `$_POST`/`$_GET`/ambos y hace `trim()` automático si es string. **No sanitiza contra XSS** — para eso está `sanitize()` aparte (llamar explícito al momento de mostrar, no al leer).
- `currentUser()`: array plano armado desde `$_SESSION` — es la forma estándar de leer "quién es el usuario actual" dentro de un controlador o vista (`$this->currentUser()` desde el controlador; las vistas reciben los mismos datos si el controlador los pasa en `$data`).

---

## 4. `Database` — singleton sqlsrv, sin PDO

`core/Database.php`, 109 líneas. Decisión de arquitectura explícita en el comentario de cabecera: **NO PDO, exclusivamente el driver nativo `sqlsrv_*`**.

- **Singleton real** (`self::$instance`, constructor `private`) — una sola conexión por request, reutilizada por todos los `Model`.
- Configura `sqlsrv_configure("WarningsReturnAsErrors", 0)` **antes** de conectar — sin esto, un `PRINT` o un warning SQLSTATE clase 01 dentro de un stored procedure hace que `sqlsrv_query()` devuelva `false` como si la query hubiera fallado de verdad (gotcha real, documentado también en `db/run_sql.php` y en memoria del proyecto — aparece cada vez que se toca un SP con mensajes informativos).
- `query($sql, $params=[])`: sin parámetros usa `sqlsrv_query()` directo; con parámetros usa `sqlsrv_prepare()` + `sqlsrv_execute()`. Cada valor del array `$params` se normaliza — si ya es un array (para `OUTPUT`/`INOUT` params) se respeta tal cual, si es un valor simple se envuelve automático como `[$valor, SQLSRV_PARAM_IN]`.
- `fetch()`/`fetchAll()` devuelven arrays asociativos (`SQLSRV_FETCH_ASSOC`) — nunca objetos.
- `lastInsertId()` usa `SELECT SCOPE_IDENTITY()` en una query aparte, **no** un parámetro `OUTPUT` del INSERT original — ver el gotcha real documentado en memoria (`SCOPE_IDENTITY() NULL tras INSERT parametrizado con sqlsrv`): si el INSERT y el `SELECT SCOPE_IDENTITY()` no van en el mismo *scope* de conexión/transacción, puede devolver `NULL`. Cuando un modelo necesita el ID recién insertado de forma confiable, la alternativa usada en el resto del proyecto es `OUTPUT INSERTED.id` directo en el INSERT.
- `beginTransaction()`/`commit()`/`rollback()` son pasamanos directos a `sqlsrv_begin_transaction/commit/rollback` — sin anidamiento ni *savepoints*.
- `Database::reset()` (estático, cierra y limpia la instancia) existe **solo para tests** — nada del flujo normal de request lo llama.

**Conexión a otras bases** (`Talento_Humano`, `inventario`, `PortuariaDemo`, etc., para queries cross-DB) **no pasa por esta clase** — cada lugar que necesita otra BD abre su propia `sqlsrv_connect()` ad-hoc leyendo `config/connections.php` directo (así lo hace `SyncPermisosModulo`, ver §10, `SsoClient`, ver §6-bis, y `NotificacionGeneradorModel`). `Database` es exclusivamente la conexión a `PORTAL_APM`.

---

## 5. `Model` y `View` — minimalismo deliberado

### `Model` (55 líneas)
Clase abstracta, puro *thin wrapper* sobre `Database::getInstance()`: `query()`, `fetch()`, `fetchAll()`, `rowsAffected()`, `lastInsertId()`, transacciones, y `outParam()` (arma el array `[&$var, SQLSRV_PARAM_INOUT, ...]` que pide `sqlsrv_prepare()` para parámetros de salida de un stored procedure). No hay ORM, no hay mapeo de columnas a propiedades, no hay validación — cada modelo de `modules/Central/models/` escribe su SQL a mano.

### `View` (66 líneas)
- `render($view, $data, $useLayout=true)`: `extract($data, EXTR_SKIP)` puebla variables locales para la vista, luego decide si envolver en el *shell* (`modules/Central/views/layouts/shell.php`) o no. **Requests AJAX nunca reciben el shell** — se detecta el mismo header `X-Requested-With` que usa `Controller::isAjax()`, así una vista puede servir tanto la carga completa de página como un fragmento parcial vía fetch sin duplicar código.
- **Resolución de rutas de vista**: `"Central/dashboard/index"` → `modules/Central/views/dashboard/index.php`. Si no existe, cae a un directorio legacy `views/{ruta}.php` (pre-reorganización a `modules/`) — compatibilidad hacia atrás, no algo a usar en código nuevo.
- `partial($view, $data)` es azúcar sintáctico para `render(..., useLayout: false)`.

---

## 6. `ModuleSecurity` — SSO tokens (muerto), permisos MOIS (muerto), auditoría (real)

`core/ModuleSecurity.php`, 193 líneas. Tres responsabilidades independientes en una sola clase, con un estado de uso muy distinto entre ellas:

### SSO token (JWT-like casero) — **verificado sin caller real, código muerto**
`generateSSOToken()`/`verifySSOToken()` — payload `{id, cedula, ip, exp}` en base64url + firma `HMAC-SHA256` separada por un punto (`base64.firma`, no JWT estándar de 3 partes). Expira a los 1800s. **Atado a IP** (`payload['ip'] !== $ip` rechaza, salvo `127.0.0.1` como excepción para desarrollo local). El secreto (`SSO_SECRET`) se resuelve de `CORE_Config`, con un fallback hardcodeado (`'SysPort_APM_Manta_Secret_SSO_Key_2026!'`) solo si la BD no responde. Búsqueda directa en todo el repo: **ningún archivo fuera de `ModuleSecurity.php` llama a estos dos métodos** — el SSO real y activo es `libs/SsoClient.php` (§6-bis). Este queda como código muerto; candidato a eliminar si se confirma que nada externo lo invoca tampoco.

### `checkAccess()` — permisos MOIS desde fuera de un Controller, **también sin caller real**
Mismo mecanismo que `Controller::requireLevel()` con `$nodo` (§3), pero como método estático standalone — para código que no es un controlador (helpers, scripts, otros módulos vía API). Trae su propio mapa `$moduleCode → id_modulo` (`'TH'`→11, `'BIENES'`→9, `'ACCESO'`→5, etc.) — **este mapa está duplicado** respecto al que usa `CatalogoModulos`/`CORE_Modulos` en BD; si se agrega un módulo nuevo hay que actualizar ambos lados a mano. Igual que `generateSSOToken`/`verifySSOToken`, búsqueda directa confirma **cero callers** en todo el repo — el control de acceso real siempre pasa por `Controller::requireLevel()`, no por acá.

### `audit()` — auditoría real vía `sp_RegistrarAuditoria`, **este sí tiene caller real, muchos**
Escribe a la tabla de auditoría central llamando al SP con 11 parámetros posicionales. El comentario en el código documenta un bug real ya corregido: la firma anterior de este método no calzaba con el orden de parámetros del SP (el user-agent terminaba en la columna `@resultado`) — no se detectó en producción porque en ese momento nada lo llamaba todavía. Eso ya cambió: verificado con búsqueda directa, hoy `AdminController.php` (17 puntos: usuarios, roles, permisos individuales, departamentos, config), `ModuloController.php` (alta/edición de módulos) y `Credenciales/AuthController.php` (activar/desactivar MFA) llaman a `audit()` activamente — es la ruta de auditoría real del portal nativo, a diferencia de los dos métodos anteriores.

---

## 6-bis. `libs/` — infraestructura PHP compartida entre TODAS las apps

A diferencia de `core/`, que solo carga el portal nativo, `libs/` está pensado para que **cualquier módulo/app del sistema** lo consuma con un `require` puntual (no autoload, no carga en `index.php`).

### `SsoClient.php` — el SSO real y activo del sistema (9.8 KB)
Este es el mecanismo que hay que usar cuando se piensa en "SSO del Portal APM" — no el de `ModuleSecurity` (muerto, §6). Documentado en detalle en `GUIA_SSO_LOGIN_ENTRE_MODULOS.md`; acá solo el resumen de cómo encaja con el resto del CORE:

- Clase standalone, se conecta a `PORTAL_APM` con su propia `sqlsrv_connect()` (no reutiliza `Database`, porque apps externas que la usan pueden no tener el resto del framework del portal cargado).
- `login()`/`validate()`/`logout()` llaman a `sp_SSO_Login`/`sp_SSO_ConfirmarLogin`/`sp_SSO_ValidarToken`/`sp_SSO_Logout`/`sp_SSO_RegistrarFallo` (definidos en `db/sso_module_login.sql`) — nunca hace `SELECT` directo a `CORE_Usuarios`.
- **Verificación de contraseña replicada, no reusada**: `SsoClient::verifyPassword()` (privado) reimplementa el mismo esquema que `SecurityHelper::verifyPassword()` (SHA-256 simulando el paso del navegador + `HMAC-SHA256` con `PASSWORD_PEPPER` + bcrypt, prefijo `'peppered:'`) en vez de llamar a `SecurityHelper` — a propósito, porque esta clase la usan apps externas que solo tienen acceso SQL al portal, sin el resto del código PHP cargado.
- **Superficie HTTP**: `modules/Credenciales/controllers/ApiSsoController.php` expone `POST /api/sso/{login,validate,logout}` para módulos que no tienen acceso SQL directo — internamente instancia `SsoClient` con las credenciales `app`/`api_key` que vienen en el body de la request. Confirmado con `require_once ROOT.'/libs/SsoClient.php'` en ese controlador — es un caller real, la cadena completa (`routes.php` → `ApiSsoController` → `SsoClient` → `sp_SSO_*`) está activa.

### `XlsxWriter.php` — generador de .xlsx sin dependencias
Construye el ZIP a mano (método "stored", sin compresión, + CRC32) — no requiere Composer ni la extensión `ZipArchive`. Caller real confirmado: `AdminController.php` (exportaciones desde el panel de administración). **No confundir con** `apps/talento_humano/core/XlsxWriter.php` — son dos implementaciones independientes con el mismo propósito, una por cada base de código (la de TH usa DEFLATE real vía `gzdeflate()`, la del portal usa "stored" sin comprimir; no comparten código ni se debe intentar unificarlas sin revisar ambos callers primero).

### `ReportPdf.php` + `fpdf/`
`ReportPdf::tabla()` (reporte tabular con membrete, encabezado repetido, zebra) y `ReportPdf::ficha()` (documento clave/valor por secciones) sobre la librería `fpdf` vendorizada en `libs/fpdf/`. Ambos métodos envían el PDF inline y hacen `exit` — quien llama debe haber hecho `require libs/fpdf/fpdf.php` antes. Caller real confirmado: `AdminController.php`.

---

## 7. Vestigios reales — código cargado pero no usado

Casos confirmados en este árbol, documentados acá para que nadie pierda tiempo tratando de entender "para qué se usa esto":

- **`core/Env.php`**: se hace `require_once` en `index.php` (comentario: *"se mantiene por compat con scripts PS1"*) pero `Env::load()` **nunca se llama** — `config/app.php` lee `config/connections.php` con un `require` directo, no a través de `Env`. Si algún script `.ps1` de instalación/despliegue todavía depende de esta clase, confirmarlo antes de borrarla; si no, es candidata segura a eliminar.
- **`core/SqlSrvStatement.php`**: wrapper OOP completo sobre un statement `sqlsrv` (`fetch()`, `fetchAll()`, `fetchColumn()`, `execute()`, `rowCount()`, `nextResult()`, destructor que libera el recurso). Se carga en `index.php` pero **no hay un solo `new SqlSrvStatement(...)` en todo el repo** (verificado por búsqueda directa) — `Database`/`Model` devuelven el resource `sqlsrv` crudo, sin envolverlo en esta clase. Probablemente un diseño más limpio que se empezó a construir y no se terminó de adoptar.
- **`ModuleSecurity::generateSSOToken()`/`verifySSOToken()`/`checkAccess()`** — ver §6, cero callers.
- **`web.config`** — regla de reescritura desactualizada, ver §1a.
- **`config/header.php`** (raíz del portal) — config del encabezado del módulo Portuaria (`'modo' => 'logo'|'imagen'`, rutas a `imgs/logoapm*.png`). Verificado con búsqueda directa: **cero callers** — Bitácoras/Portuaria migró a `apps/bitacoras` (Patrón B) y esa app tiene su PROPIA copia (`apps/bitacoras/config/header.php`, sí referenciada desde `apps/bitacoras/modules/Portuaria/views/layouts/bit_navbar.php`). El archivo de la raíz quedó huérfano tras la migración — candidato seguro a eliminar.

También el parámetro `$module` de `Controller::requireAuth()` (§3) está declarado pero no se usa dentro del método — vestigio menor del mismo tipo.

---

## 8. Helpers nativos que se cargan siempre

### `SecurityHelper` (`helpers/security_helper.php`, 177 líneas) — el más importante
- **CSRF**: `csrfToken()` genera/reutiliza `$_SESSION['_csrf_token']` (32 bytes random). `verifyCsrf()` compara con `hash_equals()`; si falla, **ya no muere con un JSON crudo en pantalla** — AJAX real recibe JSON con `redirect`, cualquier otro POST se manda directo a `/login?timeout=1` (mismo bug real que documenta el comentario del código: un mismatch de CSRF casi siempre es una sesión ya vencida mientras el formulario seguía abierto, no un ataque).
- **Peppering de contraseñas** — el esquema híbrido documentado en `GUIA_SEGURIDAD_CONTRASENAS.html` y en memoria del proyecto vive acá: `hashPassword()` hace `HMAC-SHA256(password, PASSWORD_PEPPER)` y **después** `password_hash(..., PASSWORD_BCRYPT)`, con prefijo `'peppered:'` en el hash final. `verifyPassword()` acepta el esquema nuevo Y el viejo (bcrypt directo sin pepper) para no romper cuentas existentes; `passwordNeedsRehash()` le dice al caller cuándo regrabar tras un login exitoso — migración perezosa, ningún usuario queda bloqueado. `PASSWORD_PEPPER` se autogenera con `random_bytes(32)` la primera vez y se persiste en `CORE_Config` — mismo mecanismo que `SSO_SECRET` (`ModuleSecurity`, aunque ese SSO esté muerto) y `MFA_ENCRYPTION_KEY` (`MfaHelper`), nunca un valor hardcodeado real (el fallback hardcodeado de SSO es la única excepción histórica, y solo entra si la BD está caída). El paso "SHA-256 en el navegador" que este esquema asume ya hecho antes de llegar al servidor lo hace `js/password-hash.js` (§8-bis) — sin ese script cargado en la vista, un formulario mandaría la contraseña real sin hashear del lado cliente y el servidor la re-hashearía como si fuera el valor ya hasheado (el hash final seguiría siendo válido para verificar esa MISMA contraseña, pero rompería la garantía de "la contraseña real nunca sale del navegador").
- **Headers de seguridad** (`setSecurityHeaders()`, llamado en cada `Controller::__construct()`): `X-Frame-Options`, `X-Content-Type-Options`, `X-XSS-Protection`, `Referrer-Policy`, y un `Content-Security-Policy` que arma dinámicamente `frame-src` para permitir embeber el Dashboard Ejecutivo (Streamlit) del módulo Portuaria si `APM_DASHBOARD_EJECUTIVO_URL` está definida.
- `getClientIp()`: revisa `CF-Connecting-IP` → `X-Forwarded-For` → `X-Real-IP` → `REMOTE_ADDR`, valida con `filter_var(..., FILTER_VALIDATE_IP)`.

### `SessionHelper` (71 líneas)
Wrapper delgado sobre `$_SESSION` (`get`/`set`/`has`/`remove`/`flash`) más los 2 métodos con lógica real: `login(array $user)` (regenera el ID de sesión, puebla todas las claves estándar, cachea `_requiere_mfa` para no consultar `CORE_Usuarios` en cada cambio de módulo) y `logout()` (limpia `$_SESSION`, borra la cookie, `session_destroy()`).

### `UrlHelper` (33 líneas)
`base()`/`asset()`/`current()`/`isActive()`/`redirect()`/`back()` — todo anclado a `APP_URL` (la constante autodetectada en `config/app.php`), así los links funcionan igual sin importar el entorno.

*(`form_helper.php`, `hub_charts_helper.php`, `module_stats_helper.php` son utilidades de más alto nivel para vistas/dashboards del módulo Central — no son parte del framework core en sentido estricto, se listan en §0 por completitud pero no se documentan acá función por función.)*

---

## 8-bis. `js/` — infraestructura de cliente compartida entre TODAS las apps

Igual que `libs/` es la contraparte PHP compartida, `js/` en la raíz del portal es la contraparte de navegador — cada app embebida (TH, Bienes, Bitácoras) la referencia vía `PORTAL_ROOT_URL` (o su equivalente por app) en vez de tener su propia copia. Tres archivos con lógica real:

### `password-hash.js` — el otro lado del peppering de contraseñas
`window.hashPasswordFieldsBeforeSubmit(form, fieldNames)`: SHA-256 (vía `crypto.subtle`) sobre cada campo indicado, in-place, justo antes del submit real — la contraseña en texto plano nunca sale del navegador. Requiere contexto seguro (HTTPS o `localhost`); si `crypto.subtle` no está disponible, la función queda `undefined` y cada formulario debe revisar `if (window.hashPasswordFieldsBeforeSubmit)` antes de usarla (fallback: el navegador manda la contraseña sin hashear, el servidor la sigue procesando bien porque `verifyPassword()`/`SsoClient::verifyPassword()` no exigen el formato — pero se pierde la garantía de "nunca sale del navegador"). Campos vacíos no se tocan (necesario para "editar usuario" donde dejar la clave en blanco significa "no cambiarla"). Un solo archivo físico, reusado por todos los módulos.

### `inactivity-warning.js` — el aviso de inactividad centralizado real
Este archivo (no el timeout del lado servidor de `Controller::resolveInactividad()`, que es un mecanismo aparte aunque relacionado) es la UI real detrás del "sistema de inactividad centralizado" que usan Portal, TH, Bienes y Bitácoras por igual — SweetAlert2 con anillo SVG de cuenta regresiva. Requiere que la vista defina `window.APP_INACTIVIDAD = {timeoutSegundos, avisoSegundos, keepaliveUrl, logoutUrl}` antes de cargar este script (cada app arma ese objeto con sus propias URLs — TH además soporta `csrfToken`/`logoutViaPost` porque sus endpoints exigen CSRF y solo aceptan POST, a diferencia del resto).

**Dos bugs reales ya corregidos, documentados en el propio código como comentarios**:
1. `doLogout()` tenía 3 caminos que podían dispararse casi simultáneos (el `setInterval` del countdown, el `.then()` de SweetAlert2 al cerrarse sin confirmar, y el `logoutTimer` de `schedule()` sin cancelar mientras el aviso estaba abierto) — 2-3 `GET /logout` casi a la vez recreaban la sesión más de una vez, dejando el `_csrf_token` del HTML desincronizado del real. Fix: guardia `loggedOut` de una sola disparada.
2. El *ping* de keepalive originalmente cerraba sesión ante CUALQUIER fallo del `fetch` (incluida una desconexión de red momentánea) — se cambió a solo cerrar cuando el servidor responde explícitamente que la sesión expiró, tolerando fallos de red transitorios sin destruir el trabajo del usuario.

### `alerts.js` — `window.PortalAlert`
Envoltorio delgado sobre SweetAlert2 (`success`/`warning`/`error`/etc., toasts en `top-end`) que reemplaza `confirm()`/`alert()` nativos y los `<div class="alert">` estáticos viejos — estandariza el look de mensajes flash y confirmaciones en todo el panel. Ver el gotcha documentado repetidas veces en memoria del proyecto: declarar esto con `const PortalAlert = ...` en vez de `window.PortalAlert = ...` lo rompe en cualquier vista que re-ejecute sus `<script>` inline por navegación SPA sin reload completo (varias vistas de admin lo hacen) — por eso el patrón `window.X` explícito se repite en los 3 archivos de esta sección.

*(`main.js`, `datatables-init.js` son utilidades de UI de más alto nivel — no se documentan acá función por función, mismo criterio que `form_helper.php` en §8.)*

---

## 9. `config/` — una sola fuente de verdad para credenciales

`config/connections.php` (NO versionado en git — copiar de `connections.example.php`) es el archivo del que **todo el sistema** lee credenciales de SQL Server: el portal nativo (vía `config/app.php`), `apps/talento_humano` y `apps/control_bienes` (cada uno con su propio fallback a este archivo, ver `GUIA_MODULOS_NUEVOS_Y_ACTUALIZACIONES.md`), `libs/SsoClient.php`/`core/SyncPermisosModulo.php` (leído directo, sin pasar por `Database`), y `config/export_analytics_env.php`. Es un archivo de **datos puro** (`return [...]`, sin `define()` ni efectos secundarios) a propósito, para que cualquier módulo lo pueda `require` sin chocar con sus propias constantes.

Estructura real: `server_default`, `credentials` (vacío = autenticación de Windows), `options` (`trust_cert`, `encrypt`, `charset`), y `databases` (mapa nombre-lógico → nombre-real-de-BD + servidor opcional distinto al default) — `portal`, `talento`, `portuaria`, `portuaria_ext`, `inventario`.

`config/app.php` es el único archivo que se edita para cambiar entorno (`APP_ENV`, `DEBUG_MODE`, zona horaria, timeout de sesión) — el resto de constantes (`DB_*`) son un espejo de lo que ya dice `connections.php`, no se tocan directo.

**`config/export_analytics_env.php`** no es parte del bootstrap web — es un script CLI (`php config/export_analytics_env.php`) que regenera `analytics/.env` a partir de `connections.php`, para que un dashboard Python externo (que no puede leer un array PHP) tenga sus credenciales derivadas de la misma fuente en vez de mantenerlas a mano en paralelo. Correr manualmente después de tocar `connections.php` si ese dashboard está en uso.

**`config/header.php`** — ver §7, estado real sin confirmar tras la migración de Bitácoras.

---

## 9-bis. `db/` — scripts de operación, NO es core de runtime

`db/` es una colección de archivos `.sql` sueltos (migraciones puntuales ya aplicadas, una por cada feature/fix histórico — `bloqueo_login_escalado.sql`, `mfa_portal_central.sql`, `notificaciones_reales.sql`, `permisos_centrales_fase*.sql`, etc.) más un puñado de utilidades PHP de línea de comandos: `run_sql.php` (ejecutor universal de scripts T-SQL vía sqlsrv, divide por `GO`, ver `module_update_pattern` en memoria), `list_tables.php`, `alter_sp.php`. **Nada de esto se carga en el bootstrap de `index.php` ni corre como parte de un request real** — es la caja de herramientas de quien administra la base de datos, no del framework en ejecución. Se documenta acá solo para que quede claro que existe y no es "core" en el sentido de este documento.

---

## 10. `SyncPermisosModulo` — el puente de permisos hacia módulos embebidos

`core/SyncPermisosModulo.php`, 130 líneas. Cuando un administrador cambia la matriz de permisos de un rol del portal en `/admin/roles/{id}/permisos`, este código **replica** ese cambio hacia el RBAC nativo de Talento Humano y Control de Bienes, si ese rol del portal tiene una contraparte mapeada en `CORE_Roles_Modulo_Map`. Caller real confirmado: `AdminController.php`, en el flujo de guardado de permisos.

- `centralHaciaTh()`: abre su propia conexión sqlsrv a `Talento_Humano` (lee `config/connections.php` directo, no usa `Database`), y por cada nodo MOIS cambiado bajo `id_modulo=11` traduce la opción a un `codigo_modulo` real de `th_modulos` (mapa fijo `NODOS_TH`, 14 entradas) y hace `UPDATE th_permisos_rol`. Además registra la sincronización vía `sp_th_registrar_auditoria` — auditoría en la BD destino, no en `PORTAL_APM`.
- `centralHaciaBienes()`: mismo patrón contra `inventario`, con `NODOS_BIENES` (15 entradas) y un `MERGE` sobre `inv_permisos_rol` en vez de `UPDATE` (porque Bienes puede no tener la fila todavía).
- Si el rol no tiene mapeo (`mapaRolExterno()` devuelve `null`), la función retorna sin hacer nada — silenciosamente, no es un error.
- Si la conexión al módulo destino falla, se registra el fallo vía `ModuleSecurity::audit()` **en `PORTAL_APM`** (no en el módulo destino, porque justamente no se pudo conectar ahí).

**Solo cubre nodos de "opción"** (`items===0 && subitems===0` en la tupla de 4) — la granularidad de sub-ítem del portal no tiene forma de reflejarse en el RBAC más simple (por rol×módulo, sin sub-ítem) de TH/Bienes. Es un límite consciente, no un bug.

---

## 11. `MfaHelper` — TOTP nativo del portal

`core/MfaHelper.php`, 161 líneas. Mismo algoritmo RFC 6238 que ya usa `apps/talento_humano/core/Auth.php` — no se reinventó la matemática, se replicó el patrón validado en producción de ese módulo. Diferencia real: la clave de cifrado del secreto TOTP (`MfaHelper::encryptionKey()`) vive en `CORE_Config` (mismo mecanismo que `SSO_SECRET`/`PASSWORD_PEPPER`), no en un archivo privado aparte. Caller real confirmado: `ModuleGateController.php` y `Credenciales/AuthController.php`.

- `generateSecret()`: 20 bytes random en base32.
- `encryptSecret()`/`decryptSecret()`: AES-256-GCM, IV de 12 bytes + tag de 16 + AAD fijo (`'portal-apm-mfa-v1'`) — el secreto TOTP nunca se guarda en claro en `CORE_Usuarios`.
- `verify($secret, $code, $lastStep, &$matchedStep)`: ventana ±1 paso de 30s (tolera desface de reloj del celular) con **anti-replay real** — un `$lastStep` ya usado (o anterior) nunca vuelve a aceptarse, y el step que sí matcheó se devuelve por referencia para que el caller lo persista como el nuevo `mfa_ultimo_paso`.

---

## 12. Gotchas reales a tener en cuenta si se toca este código

- **`sqlsrv_configure("WarningsReturnAsErrors", 0)`** tiene que ejecutarse ANTES de cualquier conexión — si se agrega una conexión sqlsrv nueva en algún lugar del proyecto sin este flag, un stored procedure con `PRINT` puede reportarse como fallido sin haberlo hecho.
- **`Database::getInstance()` es un singleton por PROCESO PHP**, no por request en el sentido de "se reinicia solo" — bajo `php -S` (un proceso persistente sirviendo múltiples requests) esto puede importar si algo cambia credenciales en caliente; en Apache/mod_php cada request es un proceso/hilo nuevo, no es un problema real ahí.
- **`lastInsertId()` vía `SCOPE_IDENTITY()` en query separada** puede devolver `NULL` si el driver sqlsrv no mantiene el mismo *scope* — usar `OUTPUT INSERTED.id` en el INSERT mismo cuando el ID importa de verdad (ver memoria `db_schema_notes`).
- **El mapa de módulos de `ModuleSecurity::checkAccess()` está duplicado** respecto a `CORE_Modulos`/`CatalogoModulos` — pero como ese método está muerto (§6), en la práctica no hay dos lugares que mantener sincronizados hoy; si algún día se revive, revisar esto primero.
- **`SyncPermisosModulo`/`SsoClient` abren conexiones sqlsrv nuevas cada vez que corren**, no reutilizan `Database::getInstance()` (porque apuntan a una BD distinta) — costo de abrir/cerrar conexión por cada llamada, irrelevante para el volumen actual pero a tener en cuenta si se llaman en loop.
- **Nada en `core/`/`libs/` valida que `CORE_Config` exista** antes de intentar leer `SSO_SECRET`/`MFA_ENCRYPTION_KEY`/`PASSWORD_PEPPER` — si la tabla no existe todavía (instalación nueva sin correr el script de esquema completo), estos métodos van a fallar con una excepción de SQL, no con un mensaje claro de "falta la tabla".
- **`web.config` reescribe con `?url=...` pero `index.php` nunca lee `$_GET['url']`** (§1a) — no confiar en ese parámetro si algún día se toca el soporte IIS.
- **`libs/XlsxWriter.php` (portal) y `apps/talento_humano/core/XlsxWriter.php` son implementaciones DISTINTAS** — no asumir que un fix en una aplica a la otra.

---

## 13. Qué NO cubre este documento

- Lógica de negocio de `modules/Central/` (dashboards, notificaciones, usuarios, roles) → `GUIA_CENTRAL_FUNCIONES.html`.
- SSO real entre módulos en detalle (los 5 stored procedures `sp_SSO_*`, formato de API key, cómo registrar una app nueva) → `GUIA_SSO_LOGIN_ENTRE_MODULOS.md`.
- Esquema híbrido de contraseñas en detalle (por qué, demo interactiva, línea de tiempo) → `GUIA_SEGURIDAD_CONTRASENAS.html`.
- Cómo integrar/actualizar un módulo embebido → `GUIA_MODULOS_NUEVOS_Y_ACTUALIZACIONES.md`.
- El core PROPIO de cada módulo embebido (`apps/talento_humano/core/`, `apps/control_bienes/core/`) — cada uno es independiente de este, documentado en sus propias secciones de `DOCUMENTACION_SISTEMA.md`.
- Los scripts `.sql` individuales de `db/` — son historial de cambios de esquema ya aplicados, no arquitectura viva.
