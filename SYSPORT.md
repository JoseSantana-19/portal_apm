# SYSPORT — Documentación Técnica Completa
## Portal APM · Autoridad Portuaria de Manta
**Versión:** 3.3.0 | **Fecha revisión:** 2026-06-12 | **Revisado por:** Claude Code (admin CRUD completo usuarios/roles/permisos, NUEVO gestor de Estructura de Menú MOIS, sidebar con sección ADMINISTRACIÓN, vistas admin 3 temas; verificado contra código: BUG #3/#5/#10 e INC #2/#3/#4 CORREGIDOS)

---

## ÍNDICE

1. [Resumen Ejecutivo](#1-resumen-ejecutivo)
2. [Stack Tecnológico](#2-stack-tecnológico)
3. [Estructura de Directorios](#3-estructura-de-directorios)
4. [Arquitectura de la Aplicación](#4-arquitectura-de-la-aplicación)
5. [Core Framework](#5-core-framework)
6. [Módulos Activos](#6-módulos-activos)
7. [Capa Legacy](#7-capa-legacy)
8. [Sistema de Rutas](#8-sistema-de-rutas)
9. [Helpers](#9-helpers)
10. [Base de Datos — Esquema Completo](#10-base-de-datos--esquema-completo)
11. [Stored Procedures](#11-stored-procedures)
12. [Funciones SQL](#12-funciones-sql)
13. [Vistas SQL](#13-vistas-sql)
14. [Datos Semilla](#14-datos-semilla)
15. [Seguridad](#15-seguridad)
16. [Configuración y Variables de Entorno](#16-configuración-y-variables-de-entorno)
17. [Frontend — CSS/JS/Temas](#17-frontend--cssjs-temas)
18. [Scripts de Instalación y Utilidades](#18-scripts-de-instalación-y-utilidades)
19. [Bugs y Observaciones Críticas](#19-bugs-y-observaciones-críticas)

---

## 1. Resumen Ejecutivo

**Portal APM** es un sistema de gestión interna para la Autoridad Portuaria de Manta (Ecuador). Desarrollado en PHP 8.0+ sobre un framework MVC propio (sin Composer, sin PDO), usando el driver nativo `sqlsrv_*` de Microsoft para SQL Server 2014+.

| Atributo | Valor |
|---|---|
| Nombre interno | SysPort / Portal APM |
| Organización | Autoridad Portuaria de Manta |
| Versión | 2.0.0 |
| Lenguaje backend | PHP 8.0+ |
| Base de datos | SQL Server 2014+ (PORTAL_APM) |
| Driver BD | sqlsrv nativo (NO PDO) |
| Dependencias externas | Ninguna (sin Composer, sin npm) |
| Credenciales por defecto | admin / Apm2024\* |
| Zona horaria | America/Guayaquil |
| Colación BD | Modern_Spanish_CI_AS |
| Compatibilidad BD | Level 120 (SQL Server 2014) |
| Recovery BD | SIMPLE |
| URL local desarrollo | http://localhost:8080 |

---

## 2. Stack Tecnológico

### Backend
| Componente | Versión / Detalle |
|---|---|
| PHP | 8.0+ (match, tipos union, str_starts_with, etc.) |
| Driver DB | `php_sqlsrv.dll` nativo Microsoft — NO `pdo_sqlsrv` |
| SQL Server | 2014+ (compat level 120, collation Modern_Spanish_CI_AS) |
| Sesiones | `$_SESSION` PHP nativo + tabla `CORE_Sesiones` |
| Hashing | `password_hash(PASSWORD_BCRYPT, ['cost' => 12])` |
| CSRF | `bin2hex(random_bytes(32))` |
| Tokens SSO | HMAC-SHA256, 30 min, IP-bound |

### Frontend
| Componente | Versión / CDN |
|---|---|
| ApexCharts | 3.45.1 — `cdn.jsdelivr.net` |
| Font Awesome | 6.5.0 — `cdnjs.cloudflare.com` |
| Lucide Icons | CDN (sidebar icons) |
| Google Fonts | Sora, Fira Code, JetBrains Mono, Outfit |
| CSS | Propio (variables.css + style.css ~3000 líneas, 3 temas) |
| JavaScript | Vanilla ES2020 (Fetch API, CustomEvent, history.pushState) |

**Sin** Composer, **sin** npm, **sin** paso de build.

---

## 3. Estructura de Directorios

```
portal_apm/
├── index.php                       ← Front Controller único
├── routes.php                      ← 79 rutas (movido desde config/routes.php)
├── .htaccess                       ← Apache rewrite (→ index.php)
├── web.config                      ← IIS rewrite (→ index.php?url={R:1})
├── PORTAL_APM_COMPLETO.sql         ← Esquema completo + seed + objetos SQL
├── UPDATE_PASSWORDS.sql            ← Actualiza hashes de contraseñas
├── SYSPORT.md                      ← Este documento
├── DOCUMENTACION_SISTEMA.md        ← Documentación técnica oficial v2.0
├── analisis_BD.md                  ← Análisis BD: grupos de tablas, jerarquía MOIS vs Excel organizacional
├── test_db_web.php                 ← Utilidad diagnóstico: prueba conexión sqlsrv vía navegador
├── INSTRUCCIONES_CONFIGURACION.md  ← Guía de configuración manual
├── INSTRUCCIONES_SETUP.md          ← Guía de instalación rápida automatizada
├── INICIAR_AUTOMATICO.bat          ← Lanzador .bat del PS1 de inicio
├── INICIAR_AUTOMATICO.ps1          ← PowerShell: detecta PHP, lanza servidor
├── SETUP_PROYECTO.bat              ← Lanzador .bat del PS1 de setup
├── SETUP_PROYECTO.ps1              ← PowerShell: detecta SQL Server, configura, importa SQL
├── check_dashboard.py              ← Utilidad Python: verifica existencia del archivo dashboard
├── extracted_scripts.txt           ← Scripts JS extraídos del HTML principal (para análisis)
├── extracted_styles.txt            ← Estilos CSS extraídos del HTML principal
├── extracted_theme_html.txt        ← HTML de temas extraído
├── query_log.txt                   ← Log histórico de queries (el código que lo escribía ya fue retirado)
│
├── config/
│   └── app.php                     ← ÚNICO archivo de configuración — constantes DB_*, APP_*, SESSION_*
│
├── core/
│   ├── Env.php                     ← Loader de .env (singleton, parse una vez)
│   ├── Database.php                ← Singleton sqlsrv, sin PDO
│   ├── ThDatabase.php              ← Wrapper TH (CORREGIDO: usa Database::getInstance()->getConn())
│   ├── Model.php                   ← Clase base abstract de modelos
│   ├── View.php                    ← Motor de renderizado (shell + SPA partial)
│   ├── Router.php                  ← Rutas GET/POST con placeholders {param}
│   ├── Controller.php              ← Clase base abstract de controladores
│   ├── ModuleSecurity.php          ← SSO tokens HMAC-SHA256 + checkAccess + audit
│   └── SqlSrvStatement.php         ← Wrapper OOP sobre resource sqlsrv
│
├── helpers/
│   ├── security_helper.php         ← CSRF, bcrypt, HTTP security headers
│   ├── session_helper.php          ← login(), flash messages
│   ├── url_helper.php              ← base(), asset(), redirect(), back()
│   └── form_helper.php             ← validate() con reglas: required/min/max/email/etc.
│
├── modules/                        ← CAPA ACTIVA (nueva arquitectura)
│   ├── Central/
│   │   ├── controllers/
│   │   │   ├── DashboardController.php
│   │   │   ├── AdminController.php     ← CRUD usuarios/roles + editor de permisos por nodo
│   │   │   ├── MenuController.php      ← NUEVO: CRUD de CORE_Menu_Nodos (Estructura del Menú)
│   │   │   └── NotificacionesController.php
│   │   ├── models/
│   │   │   └── DashboardModel.php
│   │   └── views/
│   │       ├── layouts/
│   │       │   ├── shell.php       ← HTML completo: CSS/JS/sidebar/topbar/main
│   │       │   ├── sidebar.php     ← Acordeón 4 niveles + flyout popover
│   │       │   └── topbar.php      ← Breadcrumb + temas + notificaciones + user menu
│   │       ├── dashboard/
│   │       │   ├── ejecutivo.php
│   │       │   ├── operativo.php
│   │       │   └── reportes.php
│   │       ├── admin/
│   │       │   ├── usuarios.php        ← stats bar, avatares por nivel, búsqueda client-side
│   │       │   ├── usuario_form.php    ← grid 2 cols, selector visual de nivel, chips de roles
│   │       │   ├── roles.php           ← stats bar, badges de código, búsqueda
│   │       │   ├── rol_form.php        ← selector visual de nivel, link a editor de permisos
│   │       │   ├── rol_permisos.php    ← árbol MOIS con selects CRUD coloreados por tema, cascada, progreso
│   │       │   ├── auditoria.php
│   │       │   └── menu/               ← NUEVO: gestor Estructura del Menú
│   │       │       ├── index.php       ← árbol MOIS glassmorphism 3 temas, toggle AJAX, ayuda integrada
│   │       │       └── form.php        ← asistente 3 pasos: tipo → ubicación (chips padres, nº sugerido, anti-duplicado) → apariencia
│   │       ├── notificaciones/
│   │       │   └── index.php
│   │       └── errors/
│   │           └── 404.php
│   ├── Credenciales/
│   │   ├── controllers/AuthController.php
│   │   ├── models/UsuarioModel.php
│   │   └── views/
│   │       ├── login/index.php     ← Standalone (sin shell): tema marinero animado
│   │       └── perfil/
│   │           ├── index.php
│   │           └── cambiar_contrasena.php
│   ├── Talento_Humano/
│   │   ├── controllers/
│   │   │   ├── EmpleadoController.php
│   │   │   └── ContratosController.php
│   │   ├── models/
│   │   │   ├── EmpleadoModel.php
│   │   │   └── ContratoModel.php
│   │   └── views/
│   │       ├── empleados/          ← index.php, form.php, show.php
│   │       └── contratos/          ← index.php, form.php, show.php
│   ├── Bitacoras/
│   │   ├── controllers/
│   │   │   ├── EventoController.php
│   │   │   └── ReporteController.php
│   │   ├── models/BitacoraModel.php
│   │   └── views/
│   │       ├── eventos/            ← index.php, form.php, show.php
│   │       └── reportes/           ← index.php
│   ├── Control_Bienes/
│   │   ├── controllers/
│   │   │   ├── BienController.php
│   │   │   └── MovimientoController.php
│   │   ├── models/
│   │   │   ├── BienModel.php
│   │   │   └── MovimientoModel.php
│   │   └── views/
│   │       ├── bienes/             ← index.php, form.php, show.php
│   │       └── movimientos/        ← index.php, form.php
│   └── Control_Acceso/
│       ├── controllers/
│       │   ├── AccesoController.php
│       │   └── VisitanteController.php
│       ├── models/
│       │   ├── VisitanteModel.php
│       │   └── (AccesoModel usa models/Acceso_/)
│       └── views/
│           ├── acceso/             ← index.php, ingresar.php, reporte.php
│           └── visitantes/         ← index.php, form.php, show.php
│
├── controllers/                    ← CAPA LEGACY
│   ├── General/
│   │   ├── HomeController.php      ← Landing pública + SSO demo + dynamic forms
│   │   ├── AuthController.php      ← Auth legacy multi-módulo (10+ variantes)
│   │   ├── DashboardController.php ← Dashboard legacy (schema diferente)
│   │   └── DatabaseController.php  ← ERD visualizer (sys.tables, etc.)
│   ├── TH_Talento_Humano/
│   │   └── ThController.php        ← TH legacy: list, ficha, medical, CSV export
│   ├── Acceso_Control_acceso/
│   │   ├── AccesoController.php    ← Admin de usuarios/roles/permisos (legacy)
│   │   └── log/                    ← Directorio de logs diarios (log_YYYY-MM-DD.txt)
│   ├── Bienes_Control_de_bienes/
│   │   └── BienesController.php    ← Bienes legacy (usa datos mock)
│   └── Bit_Bitacoras/
│       └── BitacorasController.php ← Bitácoras legacy (usa datos mock)
│
├── models/                         ← MODELOS LEGACY
│   ├── Acceso_/
│   │   ├── AccesoModel.php         ← CRUD usuarios/roles/permisos — legacy schema
│   │   ├── Menu.php                ← Menú dinámico via sp_GetMenuUsuario + jerarquía
│   │   └── Usuario.php             ← Autenticación con sp_Login + sp_RegistrarFalloLogin
│   └── TH_/
│       └── Empleado.php            ← Empleados via ThDatabase: getList, getDetails, etc.
│
├── views/                          ← VISTAS LEGACY
│   ├── layouts/
│   │   ├── header.php
│   │   ├── footer.php
│   │   ├── sidebar.php
│   │   └── topbar.php
│   ├── General/
│   │   ├── auth/login.php
│   │   ├── dashboard/index.php
│   │   ├── dashboard/erd.php
│   │   └── home/index.php + dynamic_form.php
│   ├── TH_Talento_Humano/
│   │   ├── index.php
│   │   └── ficha.php
│   ├── Acceso_Control_acceso/index.php
│   ├── Bienes_Control_de_bienes/index.php
│   └── Bit_Bitacoras/index.php
│
├── css/
│   ├── variables.css               ← Design tokens CSS + 3 temas (t1/t2/t3)
│   ├── style.css                   ← Design system UNIFICADO (~3000 líneas)
│   └── login.css                   ← Estilos inline del login standalone (también como <style>)
│
├── js/
│   └── (directorio vacío — main.js NO existe en disco)
│
├── public/
│   ├── js/
│   │   ├── app.js                  ← SPA orchestrator principal: sidebar accordion + notificaciones + tema
│   │   └── charts.js               ← ApexCharts wrapper (createChart, destroyChart, auto-init)
│   └── img/
│
├── db/
│   ├── alter_sp.php                ← Recrea sp_GetMenuUsuario via sqlsrv
│   ├── list_tables.php             ← Lista tablas via INFORMATION_SCHEMA
│   ├── run_migration.php           ← Ejecuta db/update_menu.sql por batches GO (ver INC #4: bug de scope $root)
│   └── run_seed.php                ← Ejecuta seed_large.sql en transacción
│
├── logs/
│   ├── sql_setup_output.txt        ← Output de sqlcmd al ejecutar PORTAL_APM_COMPLETO.sql
│   └── test_conn.php               ← Utilidad diagnóstico de conexión
│
└── docs/
    └── superpowers/specs/          ← Directorio de especificaciones del sistema
```

---

## 4. Arquitectura de la Aplicación

### Patrón MVC Modular (doble capa)

```
index.php  →  Router  →  Controller  →  Model  →  Database (sqlsrv)
                   ↓
                 View  →  shell.php  →  [sidebar + topbar + $content]
```

### Dual-Layer Architecture

El proyecto tiene **dos capas coexistentes**:

| Característica | Capa Activa (`modules/`) | Capa Legacy (`controllers/` + `models/` + `views/`) |
|---|---|---|
| ORM base | `core/Model.php` abstract | `core/Model.php` (mismo) o `ThDatabase` |
| Esquema DB | PORTAL_APM_COMPLETO.sql | Referencias a schema antiguo (dbo.Usuarios, dbo.Departamentos_Modulos) |
| Rutas | En `config/routes.php` | En `config/routes.php` (misma tabla) |
| Vista shell | `modules/Central/views/layouts/shell.php` | `views/layouts/header.php` + `footer.php` |
| Menú | `modules/Central/views/layouts/sidebar.php` | `views/layouts/sidebar.php` |

### SPA Híbrido

1. Carga inicial: PHP renderiza shell completo (sidebar + topbar + dashboard)
2. Navegación posterior:
   - Clic en link `[data-spa]` o dentro de `.sidebar-mods`/`.sidebar`
   - `app.js` intercepta → `fetch(url, {X-Requested-With: XMLHttpRequest})`
   - Servidor detecta header → renderiza **solo vista** (sin shell)
   - `app.js` inyecta HTML en `#main-spa-container`, ejecuta scripts secuencialmente
   - `history.pushState` actualiza URL
   - Se dispara `spa:loaded` custom event (escuchado por `charts.js`)
3. Soporte browser back/forward via `popstate`

### Autoloader

`index.php` registra `spl_autoload_register` que escanea:
- `modules/*/controllers/{ClassName}.php`
- `modules/*/models/{ClassName}.php`
- `controllers/*/{ClassName}.php`
- `models/*/{ClassName}.php`

No se requiere `require_once` explícito en controladores/modelos.

### Front Controller

`index.php` secuencia de arranque:
1. `require core/Env.php` (backward compat PS1) + `require config/app.php` — define TODAS las constantes (`ROOT`, `APP_*`, `DB_*`, `SESSION_*`, `DEBUG_MODE`)
2. `date_default_timezone_set(DEFAULT_TIMEZONE)`
3. `ini_set('display_errors', DEBUG_MODE ? 1 : 0)`
4. Carga `core/` (Database, Model, View, Router, Controller, ModuleSecurity)
5. `spl_autoload_register` → escanea directorios de módulos y legacy
6. Carga helpers (security, session, url, form)
7. `new Router()` + `require routes.php` (raíz del proyecto) — registra 79 rutas
8. `$router->resolve($uri, $method)` — despacha

### Rewrite Rules

**Apache** (`.htaccess`):
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]
```

**IIS** (`web.config`):
```xml
<rule name="Portal APM URL Rewrite">
  <match url="^(.*)$" />
  <action type="Rewrite" url="index.php?url={R:1}" appendQueryString="true" />
</rule>
```

---

## 5. Core Framework

### 5.1 `core/Env.php`

Carga `.env` una vez (singleton). Métodos:
```php
Env::get('DB_SERVER')              // string|null
Env::bool('DEBUG_MODE', false)     // bool con default
Env::int('SESSION_TIMEOUT', 1800)  // int con default
```

### 5.2 `core/Database.php`

Singleton sqlsrv, sin PDO.

```php
$db = Database::getInstance();
$stmt  = $db->query($sql, $params);    // ejecuta, retorna resource sqlsrv
$row   = $db->fetch($stmt);            // array asociativo primera fila
$rows  = $db->fetchAll($stmt);         // array de arrays asociativos
$n     = $db->rowsAffected($stmt);     // filas afectadas
$id    = $db->lastInsertId();          // SCOPE_IDENTITY()
$db->beginTransaction();
$db->commit();
$db->rollback();
$db->free($stmt);                      // sqlsrv_free_stmt
$db->reset();                          // destruye instancia singleton (tests)
```

**Formato de parámetros**: `$params` puede ser array simple (`[$val1, $val2]` → auto-wrap `SQLSRV_PARAM_IN`) o array complejo (`[[$val, SQLSRV_PARAM_IN, $phpType, $sqlType]]`) para OUTPUT params.

**Nota importante**: `Database::query()` retorna un **resource sqlsrv** (no un objeto `SqlSrvStatement`). `SqlSrvStatement` solo lo usa `ThDatabase`.

### 5.3 `core/ThDatabase.php`

Wrapper para módulo Talento Humano. **CORREGIDO** (BUG #2): Anteriormente llamaba `Database::getConnection('database_th')` que no existe. Ahora usa correctamente:
```php
self::$db = Database::getInstance()->getConn();
```
TH usa la misma conexión singleton que el resto del sistema (no BD separada). Retorna objetos `SqlSrvStatement` en sus métodos.

### 5.4 `core/SqlSrvStatement.php`

Wrapper OOP sobre resource sqlsrv. Usado **solo** por `ThDatabase`.

| Método | Descripción |
|---|---|
| `fetch()` | Primera fila como array asociativo |
| `fetchAll()` | Todas las filas |
| `fetchColumn(int $col=0)` | Valor de columna específica |
| `closeCursor()` | `sqlsrv_free_stmt` |
| `execute()` | `sqlsrv_execute` |
| `rowCount()` | `sqlsrv_rows_affected` |
| `nextResult()` | `sqlsrv_next_result` |
| `__destruct()` | Auto-cierra stmt |

### 5.5 `core/Model.php`

Clase base abstract. Todos los modelos activos la extienden.

Expone métodos de `Database` como protegidos de instancia: `query()`, `fetch()`, `fetchAll()`, `free()`, `beginTransaction()`, `commit()`, `rollback()`, `lastInsertId()`, `rowsAffected()`.

Método adicional:
```php
protected function outParam(mixed &$var, int $phpType, int $sqlType): array
// Construye array SQLSRV_PARAM_INOUT para SPs con OUTPUT
```

### 5.6 `core/View.php`

```php
View::render('Talento_Humano/empleados/index', ['data' => $data], $useLayout=true);
```

Resolución de ruta:
- `ModuleName/sub/path` → `modules/ModuleName/views/sub/path.php`
- Fallback: `views/sub/path.php` (legacy)

Si `X-Requested-With: XMLHttpRequest` → renderiza solo la vista (sin shell)
Si petición normal → `ob_start()` captura vista → `$content` → `shell.php`

Layout: `modules/Central/views/layouts/shell.php`

### 5.7 `core/Router.php`

Soporta rutas exactas y con placeholders `{param}`:
```php
$router->get('/bienes/{id}', 'BienController@show');
$router->post('/bienes/{id}', 'BienController@update');
```

- Parámetros numéricos auto-cast a `int`
- 404 AJAX → `{"error":"Not found","code":404}` con HTTP 404
- 404 normal → carga `modules/Central/views/errors/404.php`

### 5.8 `core/Controller.php`

Clase base abstract. Todos los controladores la extienden.

| Método | Comportamiento |
|---|---|
| `requireAuth()` | Redirige a /login si no hay sesión válida |
| `requireAuth('MODULO')` | + verifica permiso de módulo |
| `requireLevel(int $n)` | Verifica `nivel_jerarquia >= $n` |
| `render($vista, $data)` | Delega a `View::render()` |
| `json($data, $code=200)` | Responde JSON + exit |
| `redirect($path)` | Redirige + exit |
| `input($key, $method='both')` | Lee `$_POST`/`$_GET` |
| `sanitize($str)` | `htmlspecialchars(strip_tags($str))` |
| `csrfToken()` | Genera/retorna token CSRF de sesión |
| `verifyCsrf()` | Aborta 403 si no coincide |
| `currentUser()` | Array con datos del usuario logueado |
| `isAjax()` | Verifica `X-Requested-With: XMLHttpRequest` |

**Timeout de sesión**: `SESSION_TIMEOUT` en `.env` (default 1800 seg). Cada petición autenticada renueva `$_SESSION['last_activity']`.

### 5.9 `core/ModuleSecurity.php`

SSO tokens + permisos granulares MOIS. **COMPLETAMENTE REESCRITO** (BUG #1 + SEC #1 corregidos).

**Clave SSO** (carga lazy desde BD — CORREGIDO):
```php
// Lee CORE_Config WHERE modulo='CORE' AND clave='SSO_SECRET' AND estado=1
// Fallback: constante default solo si falla la BD
private static function getSecretKey(): string
```
Ya NO está hardcodeada en código fuente.

**SSO Token** (HMAC-SHA256):
```php
ModuleSecurity::generateSSOToken(int $userId, string $cedula): string
ModuleSecurity::verifySSOToken(string $token): array|false
```
- Expiración: 30 minutos
- IP-bound (rechaza si IP cambia)
- Ambos usan `self::getSecretKey()` — clave desde BD

**Verificación de acceso MOIS** (CORREGIDO):
```php
ModuleSecurity::checkAccess(int $userId, string $moduleCode, ?int $optionId, ?int $formId, string $reqPermission): bool
```
- Mapea `$moduleCode` a `id_modulo` via tabla estática (PE→1, TI→2, AJ→3, IP→4, GA→5, DO→6, GG→7, DSP→8, DA→9, DF→10, TH→11, etc.)
- Llama `dbo.fn_TienePermisoNodo(id_usuario, id_modulo, opcion, items, subitems, nivel_crud, activo)` → BIT
- Usa `$this->free($stmt)` (no `closeCursor()`)

**Auditoría** (CORREGIDO):
```php
ModuleSecurity::audit(int $userId, string $modulo, string $operacion, string $detalle, string $resultado, string $ip): void
```
- Llama `sp_RegistrarAuditoria` via clase anónima que extiende Model
- Usa `$this->free($stmt)` (no `closeCursor()`)

---

## 6. Módulos Activos

### 6.1 Central

Módulo del sistema — no tiene navegación propia en el menú.

#### DashboardController

```
GET  /dashboard            → index() → redirige a executive() (nivel>=2) u operational()
GET  /dashboard/ejecutivo  → executive() → getKpisEjecutivo() + getAlertasPendientes()
GET  /dashboard/operativo  → operational() → getKpisOperativo() + getActividadReciente()
GET  /dashboard/reportes   → reportes() → pista auditoría + links rápidos
```

#### AdminController (requireLevel(3))

CRUD completo de usuarios, roles y permisos por nodo. Columnas reales verificadas: `CORE_Usuarios.estado` (no `activo`), `CORE_Usuarios.hash_contrasena`, `CORE_Roles.codigo` (no `codigo_rol`), `CORE_Departamentos.nombre` (no `nombre_depto`).

```
GET  /admin/usuarios               → usuarios() — listado con stats y departamento
GET  /admin/usuarios/nuevo         → nuevoUsuario()
POST /admin/usuarios               → crearUsuario() — hash bcrypt + INSERT CORE_Usuarios
GET  /admin/usuarios/{id}/editar   → editarUsuario() — incluye roles asignados
POST /admin/usuarios/{id}          → actualizarUsuario() — sync CORE_Usuarios_Roles (DELETE+INSERT)
POST /admin/usuarios/{id}/eliminar → eliminarUsuario() — soft delete estado=0
GET  /admin/roles                  → roles()
GET  /admin/roles/nuevo            → nuevoRol()
POST /admin/roles                  → crearRol()
GET  /admin/roles/{id}/editar      → editarRol()
POST /admin/roles/{id}             → actualizarRol()
POST /admin/roles/{id}/eliminar    → eliminarRol()
GET  /admin/roles/{id}/permisos    → rolPermisos() — árbol MOIS + permisos actuales del rol
POST /admin/roles/{id}/permisos    → guardarPermisos() — DELETE+INSERT CORE_Permisos_Nodo,
                                     keys "mod-op-it-sub", nivel_crud 1–4
GET  /admin/auditoria              → auditoria() — vw_AuditoriaGlobal paginada
```

**Patrón de validación**: en fallo, `$_SESSION['_form_errors'] = FormHelper::errors()` + `$_SESSION['_old_input'] = $_POST` + redirect (SessionHelper::flash solo acepta strings).

#### MenuController (requireLevel(3); eliminar exige nivel 4) — NUEVO

Gestor de la **Estructura del Menú** (`CORE_Menu_Nodos`, jerarquía MOIS). Constante interna `MODULES` mapea los 11 módulos con label/icono/color.

```
GET  /admin/menu                → index() — árbol completo (todos los nodos), stats activos/inactivos
GET  /admin/menu/nuevo          → nuevo() — pasa TODOS los nodos (incl. inactivos) para
                                  sugerencia de coordenada libre y detección de duplicados
POST /admin/menu                → crear() — valida tupla única (id_modulo, opcion, items, subitems)
GET  /admin/menu/{id}/editar    → editar() — coordenadas MOIS inmutables
POST /admin/menu/{id}           → actualizar() — solo metadatos (descripcion, url_ruta, icono,
                                  orden, requiere_mfa, target_spa, estado)
POST /admin/menu/{id}/toggle    → toggle() — AJAX, responde {"ok":true,"estado":0|1}
POST /admin/menu/{id}/eliminar  → eliminar() — nivel 4; bloquea si CORE_Permisos_Nodo referencia la tupla
```

**Vistas** (`modules/Central/views/admin/menu/`):
- `index.php` — árbol glassmorphism con tokens locales por tema (`--g-bg`, `--g-blur`, `--l1..--l4`; corporate = plano con blur 0), rieles conectores, toggle AJAX optimista, búsqueda, filtros por nivel, panel de ayuda colapsable "¿Cómo funciona?", botones `+` contextuales que pre-cargan coordenadas vía query string.
- `form.php` — asistente de creación en 3 pasos: (1) tipo de elemento con explicación simple, (2) ubicación con chips clicables de padres existentes + número siguiente libre sugerido + aviso de duplicado en vivo que bloquea Guardar, (3) apariencia con preview de ícono FA. Checkboxes con hidden `value=0` para que desmarcar viaje al servidor.

#### NotificacionesController

```
GET  /notificaciones                → index() — vista completa (datos desde controller, ya no consulta en vista)
GET  /notificaciones/recientes      → recientes() → JSON últimas 20 de CORE_Notificaciones
POST /notificaciones/marcar-leidas  → marcarLeidas() — AJAX marca leida=1
```

#### DashboardModel

`getKpisEjecutivo()` — subconsultas inline (no invoca SPs):
- `TH_Empleados WHERE estado=1` → total_empleados
- `TH_Contratos WHERE estado_contrato='Vigente'` → contratos_vigentes / 30d / 60d / 90d
- `BIENES_Activos` → total_bienes / bienes_activos / bienes_mantenimiento
- `BIT_Eventos` → eventos_mes / eventos_pendientes
- `ACCESO_Registros WHERE tipo_acceso='Entrada' AND fecha=hoy` → ingresos_hoy
- `getBitacoraSemana()` → array de 7 días (fecha → COUNT)

`getKpisOperativo()` — igual pero filtra por `id_departamento` del usuario:
- **BUG**: `$deptoFilter` es string interpolada: `"AND (id_departamento = $idDepto OR id_departamento IS NULL)"` — funcional (idDepto es int casteado) pero no parametrizado.

`getAlertasPendientes(int $limit=8)`:
- `CORE_Notificaciones WHERE leida=0 AND (id_usuario IS NULL OR id_usuario=?)`
- Columnas: `id_notif, titulo, mensaje, tipo, prioridad, url_accion, fecha_creacion`

`getAuditRecent(int $limit=10)` y `getActividadReciente(int $limit=15)`:
- `SELECT TOP(?) … FROM vw_AuditoriaGlobal ORDER BY fecha_registro DESC`
- Alias: `nombre_usuario AS nombre_completo`, `fecha_registro AS fecha_creacion`

### 6.2 Credenciales

#### AuthController

| Método | Ruta | Descripción |
|---|---|---|
| `showLogin()` | `GET /login` | Vista login standalone |
| `login()` | `POST /login` | CSRF → `sp_Login` → `password_verify` → `login()` helper → redirect |
| `logout()` | `POST /logout` | Invalida token, destruye sesión |
| `setTheme()` | `POST /set-theme` | AJAX: guarda tema en `$_SESSION['tema']` + `CORE_Usuarios` |
| `perfil()` | `GET /perfil` | Vista perfil |
| `actualizarPerfil()` | `POST /perfil` | Actualiza correo en `CORE_Usuarios` |
| `showCambiarContrasena()` | `GET /cambiar-contrasena` | Vista form |
| `cambiarContrasena()` | `POST /cambiar-contrasena` | Verifica actual bcrypt → `sp_CambiarContrasena` |

**BUG #3 — CORREGIDO**: `cambiarContrasena()` pasaba `SecurityHelper::getClientIp()` como 3er param a `sp_CambiarContrasena` (mapeaba a `@nuevo_salt`). Ahora pasa `''` como salt.

#### UsuarioModel

- `authenticate(string $user, string $pass)` — usa `sp_Login` con OUTPUT params via `sqlsrv_prepare`
  - Resultado 0 = éxito, 1 = inactivo, 2 = bloqueado
  - Inserta en `CORE_Sesiones` al login exitoso
  - Resetea `intentos_fallidos`
  - **CORREGIDO** (BUG #4): INSERT auditoría ahora pasa 5 arrays `[$val, SQLSRV_PARAM_IN]` separados
- `findById(int $id)` — `CORE_Usuarios WHERE estado=1` (**CORREGIDO**: era `activo=1`, columna inexistente)
- `getMenuItems(int $userId)` — llama `sp_GetMenuUsuario`, retorna árbol jerárquico
- `updateCorreo(int $id, string $correo)` — UPDATE `CORE_Usuarios`

#### Login View

`modules/Credenciales/views/login/index.php` — página **standalone** (sin shell), Google Fonts Outfit + Fira Mono. Diseño split: panel izquierdo (animaciones estrellas, agua, barco SVG) + panel derecho (form). **Tema único**: dark navy con acentos azul eléctrico. Incluye script de demo-cuentas que rellena el form al hacer clic.

**Nota**: La login page usa fuentes Outfit/Fira Mono, mientras que el shell usa Sora/JetBrains Mono — ligera inconsistencia de tipografía entre páginas.

### 6.3 Talento Humano

#### EmpleadoController

```
GET  /empleados              → index() — paginado, filtros: search/departamento/activo
POST /empleados              → store() — INSERT + OUTPUT INSERTED.id_empleado
GET  /empleados/nuevo        → create()
GET  /empleados/{id}         → show() — ficha vw_FichaEmpleado
GET  /empleados/{id}/editar  → edit()
PUT  /empleados/{id}         → update()
DELETE /empleados/{id}       → destroy() — requireLevel(2), soft delete activo=0
```

#### ContratosController

```
GET  /contratos              → index() — lista + getProximosVencer(90)
GET  /contratos/nuevo        → create() — precarga empleado si ?empleado={id}
POST /contratos              → store()
GET  /contratos/{id}         → show()
GET  /contratos/{id}/editar  → edit()
PUT  /contratos/{id}         → update()
```

#### EmpleadoModel

- `getAll(int $page, int $perPage, array $filters)` — OFFSET/FETCH paginación
- `findById(int $id)` — usa `vw_FichaEmpleado`
- `create(array $data)` — `OUTPUT INSERTED.id_empleado`
- `getDepartamentos()` — `CORE_Departamentos WHERE estado=1`

Tabla: `TH_Empleados` | Vista: `vw_FichaEmpleado`

#### ContratoModel

- `getByEmpleado(int $id)` — contratos por empleado, DESC fecha_inicio
- `getProximosVencer(int $days=90)` — `DATEDIFF(day, GETDATE(), fecha_fin) BETWEEN 0 AND $days`
- Número contrato formato: `CONT-YYYY-NNNN`

### 6.4 Bitácoras

#### EventoController

```
GET  /bitacoras              → index() — filtros: estado_evento, categoria
POST /bitacoras              → store() — estado='Pendiente', id_usuario=$_SESSION['user_id']
GET  /bitacoras/nuevo        → create()
GET  /bitacoras/{id}         → show()
GET  /bitacoras/{id}/editar  → edit()
PUT  /bitacoras/{id}         → update()
POST /bitacoras/{id}/cerrar  → close() — requiere observaciones
```

#### ReporteController

```
GET /bitacoras/reportes → index()
```

**BUG #5 — CORREGIDO**: Consultaba `BIT_Eventos.estado` (TINYINT 0/1) en lugar de `estado_evento` (NVARCHAR). Ahora agrupa por `estado_evento` (workflow 'Pendiente'/'En Proceso'/'Cerrado').

#### BitacoraModel

- `create()` — sets `estado_evento='Pendiente'`, `id_usuario` from `$_SESSION`
- `close(int $id, string $obs)` — UPDATE `estado_evento='Cerrado'`, `fecha_cierre=GETDATE()`
- `getAll(array $filters)` — filtra por `estado_evento`, `id_categoria`

Tabla: `BIT_Eventos`

**Columnas reales vs esperadas**:
| Campo PHP | Campo BD real | Nota |
|---|---|---|
| `id_bitacora` | `id_evento` | PK |
| `estado` (TINYINT) | `estado` | validez, no workflow |
| `estado_evento` | `estado_evento` | workflow string |
| `prioridad` string | `prioridad` TINYINT 1–3 | 1=Baja, 2=Media, 3=Alta |

### 6.5 Control de Bienes

#### BienController

```
GET  /bienes              → index() — filtros: codigo, estado_bien, categoria
POST /bienes              → store()
GET  /bienes/nuevo        → create()
GET  /bienes/{id}         → show() — carga también MovimientoModel::getByBien()
GET  /bienes/{id}/editar  → edit()
PUT  /bienes/{id}         → update()
POST /bienes/{id}/baja    → darBaja() — requireLevel(2)
```

#### MovimientoController

```
GET  /bienes/movimientos       → index()
GET  /bienes/movimientos/nuevo → create() — carga bienes activos + departamentos
POST /bienes/movimientos       → store()
```

#### BienModel

- `darBaja(int $id, string $motivo)` — UPDATE `estado_bien='Baja'` (ignora `$motivo`, no hay columna observaciones en `BIENES_Activos`)
- Tabla: `BIENES_Activos`

#### MovimientoModel

- `create(array $data)` — INSERT `BIENES_Movimientos` + si `tipo_movimiento='Transferencia'`, UPDATE `BIENES_Activos.id_departamento`
- Tabla: `BIENES_Movimientos`

### 6.6 Control de Acceso (módulo activo)

#### AccesoController (módulo activo)

```
GET  /acceso              → index() — registros de hoy
GET  /acceso/ingresar     → ingresar() — formulario nuevo ingreso
POST /acceso/ingresar     → registrarIngreso() — valida persona_visita
POST /acceso/{id}/salida  → registrarSalida() → JSON (AJAX)
GET  /acceso/reporte      → reporte() — paginado todos
```

#### VisitanteController

```
GET  /acceso/visitantes      → index()
GET  /acceso/visitantes/nuevo → create()
POST /acceso/visitantes      → store() — verifica findByDocumento() para evitar cédulas duplicadas
GET  /acceso/visitantes/{id} → show()
```

#### VisitanteModel

- `getAll()`, `findById(int $id)`, `findByDocumento(string $doc)`, `create(array $data)`
- Tabla: `ACCESO_Visitantes`

---

## 7. Capa Legacy

La capa legacy coexiste con la capa activa. Usa un esquema de base de datos diferente (dbo.Usuarios, dbo.Departamentos_Modulos, dbo.Grupos_Roles, etc.) que **no coincide** con PORTAL_APM_COMPLETO.sql.

> **Importante — Prioridad del Autoloader**: El autoloader escanea `modules/*/controllers/` y `modules/*/models/` **antes** que `controllers/*/` y `models/*/`. Por tanto, cuando existe un controlador o modelo en ambas capas con el mismo nombre de clase, **la capa activa siempre gana**. Las clases legacy `AuthController`, `DashboardController` y `AccesoController` en `controllers/General/` y `models/Acceso_/Usuario.php` son **dead code** — nunca se instancian en producción.

### 7.1 Legacy AccesoController (`controllers/Acceso_Control_acceso/AccesoController.php`)

Administración completa de usuarios, roles y permisos del sistema legacy.

- `index()` — carga usuarios/roles/deptos/menuOpciones/formularios vía `AccesoModel`
- `guardarUsuario()` — crea o actualiza usuario (default pass: `Cambiar2026!`)
- `guardarRol()` — crea o actualiza rol en `dbo.Grupos_Roles`
- `getPermisosMenu()` / `guardarPermisosMenu()` — AJAX: permisos de menú por rol
- `getPermisosFormulario()` / `guardarPermisosFormulario()` — AJAX: permisos formulario por rol
- Sistema de log: escribe `log_YYYY-MM-DD.txt` en `controllers/Acceso_Control_acceso/log/`
- Método `isDebug()` lee `config/app.php` (distinto del `.env`)

#### AccesoModel (`models/Acceso_/AccesoModel.php`)

Tablas legacy: `dbo.Usuarios`, `dbo.Departamentos_Modulos`, `dbo.Grupos_Roles`, `dbo.Usuarios_Grupos_Roles`, `dbo.Menu_Opciones`, `dbo.Formularios`, `dbo.Permisos_Grupos_Roles`, `dbo.Per_Formulario`

Métodos:
- `getUsuarios()` — con STRING_AGG de roles
- `getGruposRoles()` — roles con depto
- `getDepartamentosModulos()` — WHERE habilitado=1
- `getMenuOpciones()` — WHERE activo=1 AND MO.visible no existe en consulta (solo activo)
- `getFormularios()` — WHERE activo=1
- `saveUsuario(array $data)` — transacción: INSERT/UPDATE + sync roles en `Usuarios_Grupos_Roles`
- `saveRol(array $data)` — INSERT/UPDATE `Grupos_Roles`
- `getPermisosMenuPorRol(int $rolId)` — `Permisos_Grupos_Roles` mapeado como `[id_menu_op => tipo_permiso]`
- `savePermisosMenu(int $rolId, array $permisos)` — DELETE + INSERT en transacción
- `getPermisosFormularioPorRol(int $rolId)` — `Per_Formulario` mapeado como `[id_form => permiso]`
- `savePermisosFormulario(int $rolId, array $permisos)` — DELETE + INSERT en transacción

**Nota**: `AccesoModel` llama `$stmt->fetchAll()` y `$stmt->closeCursor()` sobre recursos sqlsrv (mismo bug que `ModuleSecurity`).

#### Menu Model (`models/Acceso_/Menu.php`)

Construye árbol de menú dinámico:
- `getUserMenu(int $userId)` — llama `sp_GetMenuUsuario` → agrupa en módulos/áreas/items
- `hasPermission(int $userId, int $menuOpId, int $permType=1)` → llama `fn_TienePermiso`
- Módulos definidos: Central (PORTAL), Talento_Humano (TH), Bitacoras (BITACORAS), Control_Bienes (BIENES), Control_Acceso (ACCESO), Credenciales (ADMIN)
- Colores: #0ea5e9, #0dcaf0, #0056b3, #fd7e14, #20c997, #6f42c1

#### Usuario Model (`models/Acceso_/Usuario.php`)

- `authenticate(string $user, string $pass)` — sp_Login con SQLSRV_PARAM_INOUT (resultado/idUsuario/reqMfa/reqCambioPass)
- Verifica contraseña con `password_verify` + soporte mock: `$pass === str_replace('$2b$12$HASH_', '', $hash)` (hack de compatibilidad seed)
- Fallo → llama `sp_RegistrarFalloLogin(usuario, ip, maxIntentos=5)`
- `getProfile(int $id)` — usuario + departamento JOIN

### 7.2 Legacy BienesController y BitacorasController

Ambos usan **datos mock** (hardcodeados en el controlador, no consultan BD). Son stubs de presentación.

### 7.3 Legacy Empleado Model (`models/TH_/Empleado.php`)

Usa `ThDatabase` (que tiene el bug de `getConnection()`).

- `getList(array $filters, int $page, int $limit)` — paginación OFFSET/FETCH sobre `vw_FichaEmpleado`
  - Filters: `search` (nombre/cedula/correo/cargo), `dept` (codigo_depto LIKE), `active`
  - **CORREGIDO** (INC #3): ya no escribe `query_log.txt` — log de debug retirado
- `getDetails(int $empId)` — ficha completa: perfil + contratos + adendas + novedades médicas
- `addMedicalEvent(array $data)` — INSERT `TH_NovedadesMedicas` en transacción
- `getDepartments()` — `Departamentos_Modulos WHERE habilitado=1 AND tipo_nodo='DEPARTAMENTO'`

### 7.4 Legacy HomeController

- `index()` — landing pública, carga desde vistas legacy
- `demoSso()` — GET `/api/demo-sso`: consulta 5 vistas legacy (`View_portal_apm_usuario`, `view_portal_apm_modulos`, `view_portal_apm_menu`, `view_portal_apm_opciones`, `view_portal_apm_formulario`)
- `renderDynamicForm()` — consulta `Menu_Opciones` para formulario dinámico

### 7.5 Legacy AuthController

Maneja autenticación de 10+ variantes de módulo: TH, Bienes, Acceso, Bitacoras, Financiero, Juridica, Infraestructura, Gerencia, Admin, DatabaseAdmin.

- Usa `models/Acceso_/Usuario.php` (legacy)
- Llama `ModuleSecurity::checkAccess()` para verificar permisos
- Guarda variables de sesión distintas: `sso_token`, `user_dept` (diferente a capa activa)
- UPDATE `dbo.Usuarios.token_sesion` al login

### 7.6 Legacy DatabaseController

ERD visualizer:
- Consulta `sys.tables`, `sys.columns`, `sys.views`, `sys.procedures`
- Logs de errores en `controllers/Control_acceso/log/`

---

## 8. Sistema de Rutas

`routes.php` (raíz del proyecto) — 79 rutas totales.

### Rutas de Autenticación
```
GET  /login               → AuthController@showLogin
POST /login               → AuthController@login
POST /logout              → AuthController@logout
POST /set-theme           → AuthController@setTheme
GET  /perfil              → AuthController@perfil
POST /perfil              → AuthController@actualizarPerfil
GET  /cambiar-contrasena  → AuthController@showCambiarContrasena
POST /cambiar-contrasena  → AuthController@cambiarContrasena
```

### Dashboard
```
GET /dashboard            → DashboardController@index
GET /dashboard/ejecutivo  → DashboardController@executive
GET /dashboard/operativo  → DashboardController@operational
GET /dashboard/reportes   → DashboardController@reportes
```

### Talento Humano
```
GET    /empleados              → EmpleadoController@index
GET    /empleados/nuevo        → EmpleadoController@create
POST   /empleados              → EmpleadoController@store
GET    /empleados/{id}         → EmpleadoController@show
GET    /empleados/{id}/editar  → EmpleadoController@edit
PUT    /empleados/{id}         → EmpleadoController@update
DELETE /empleados/{id}         → EmpleadoController@destroy
GET    /contratos              → ContratosController@index
GET    /contratos/nuevo        → ContratosController@create
POST   /contratos              → ContratosController@store
GET    /contratos/{id}         → ContratosController@show
GET    /contratos/{id}/editar  → ContratosController@edit
PUT    /contratos/{id}         → ContratosController@update
```

### Bitácoras
```
GET    /bitacoras              → EventoController@index
GET    /bitacoras/nuevo        → EventoController@create
POST   /bitacoras              → EventoController@store
GET    /bitacoras/{id}         → EventoController@show
GET    /bitacoras/{id}/editar  → EventoController@edit
PUT    /bitacoras/{id}         → EventoController@update
POST   /bitacoras/{id}/cerrar  → EventoController@close
GET    /bitacoras/reportes     → ReporteController@index
```

### Control de Bienes
```
GET    /bienes                    → BienController@index
GET    /bienes/nuevo              → BienController@create
POST   /bienes                    → BienController@store
GET    /bienes/{id}               → BienController@show
GET    /bienes/{id}/editar        → BienController@edit
PUT    /bienes/{id}               → BienController@update
POST   /bienes/{id}/baja          → BienController@darBaja
GET    /bienes/movimientos        → MovimientoController@index
GET    /bienes/movimientos/nuevo  → MovimientoController@create
POST   /bienes/movimientos        → MovimientoController@store
```

### Control de Acceso
```
GET  /acceso              → AccesoController@index
GET  /acceso/ingresar     → AccesoController@ingresar
POST /acceso/ingresar     → AccesoController@registrarIngreso
POST /acceso/{id}/salida  → AccesoController@registrarSalida
GET  /acceso/reporte      → AccesoController@reporte
GET  /acceso/visitantes   → VisitanteController@index
GET  /acceso/visitantes/nuevo → VisitanteController@create
POST /acceso/visitantes   → VisitanteController@store
GET  /acceso/visitantes/{id} → VisitanteController@show
```

### Notificaciones y Admin
```
GET  /notificaciones                 → NotificacionesController@index
GET  /notificaciones/recientes       → NotificacionesController@recientes
POST /notificaciones/marcar-leidas   → NotificacionesController@marcarLeidas
GET  /admin/usuarios                 → AdminController@usuarios
GET  /admin/usuarios/nuevo           → AdminController@nuevoUsuario
POST /admin/usuarios                 → AdminController@crearUsuario
GET  /admin/usuarios/{id}/editar     → AdminController@editarUsuario
POST /admin/usuarios/{id}            → AdminController@actualizarUsuario
POST /admin/usuarios/{id}/eliminar   → AdminController@eliminarUsuario
GET  /admin/roles                    → AdminController@roles
GET  /admin/roles/nuevo              → AdminController@nuevoRol
POST /admin/roles                    → AdminController@crearRol
GET  /admin/roles/{id}/editar        → AdminController@editarRol
POST /admin/roles/{id}               → AdminController@actualizarRol
POST /admin/roles/{id}/eliminar      → AdminController@eliminarRol
GET  /admin/roles/{id}/permisos      → AdminController@rolPermisos
POST /admin/roles/{id}/permisos      → AdminController@guardarPermisos
GET  /admin/auditoria                → AdminController@auditoria
```

### Admin — Estructura del Menú (NUEVO)
```
GET  /admin/menu                → MenuController@index
GET  /admin/menu/nuevo          → MenuController@nuevo
POST /admin/menu                → MenuController@crear
GET  /admin/menu/{id}/editar    → MenuController@editar
POST /admin/menu/{id}           → MenuController@actualizar
POST /admin/menu/{id}/toggle    → MenuController@toggle      (AJAX JSON)
POST /admin/menu/{id}/eliminar  → MenuController@eliminar    (nivel 4)
```

### Rutas Legacy (siguen activas)
```
GET  /                     → HomeController@index
GET  /api/demo-sso         → HomeController@demoSso
GET  /home/dynamic-form    → HomeController@renderDynamicForm
GET  /control-acceso       → AccesoController@index (legacy)
POST /control-acceso/guardar-usuario     → AccesoController@guardarUsuario
POST /control-acceso/guardar-rol        → AccesoController@guardarRol
GET  /control-acceso/permisos-menu      → AccesoController@getPermisosMenu
POST /control-acceso/guardar-permisos-menu     → AccesoController@guardarPermisosMenu
GET  /control-acceso/permisos-formulario      → AccesoController@getPermisosFormulario
POST /control-acceso/guardar-permisos-formulario → AccesoController@guardarPermisosFormulario
```

---

## 9. Helpers

### `helpers/security_helper.php`

```php
SecurityHelper::csrfToken(): string          // genera bin2hex(random_bytes(32)), guarda en sesión
SecurityHelper::verifyCsrf(string $t): bool  // verifica POST._csrf_token o X-CSRF-TOKEN header
SecurityHelper::hashPassword(string $p): string  // bcrypt cost=12
SecurityHelper::verifyPassword(string $p, string $h): bool
SecurityHelper::setSecurityHeaders(): void   // X-Frame-Options DENY, CSP (unsafe-inline), X-Content-Type-Options, HSTS
SecurityHelper::getClientIp(): string        // CF-Connecting-IP > X-Forwarded-For > X-Real-IP > REMOTE_ADDR
```

**Nota**: CSP usa `'unsafe-inline'` — permite XSS via injección inline. No hay nonce.

### `helpers/session_helper.php`

```php
login(array $userData): void  // session_regenerate_id(true) + almacena en $_SESSION:
                               //   user_id, nombre_usuario, nombre_completo,
                               //   nivel_jerarquia, id_departamento, tema,
                               //   last_activity, _csrf_token
logout(): void  // session_destroy() + cookie cleanup
flashSet(string $key, mixed $val): void
flashGet(string $key): mixed  // retorna y borra
```

### `helpers/url_helper.php`

```php
base(string $path = ''): string     // APP_URL . '/' . ltrim($path, '/')
asset(string $path): string         // APP_URL . '/public/' . $path
current(): string                   // URL actual
isActive(string $path): bool        // compara con current()
redirect(string $path): void        // header(Location:) + exit
back(string $fallback = '/'): void  // Referer o fallback
```

### `helpers/form_helper.php`

```php
FormHelper::validate(array $data, array $rules): array  // retorna errores
```

Reglas soportadas: `required`, `min:N`, `max:N`, `email`, `numeric`, `in:a,b,c`, `confirmed` (campo + campo_confirmation), `alpha_num`

---

## 10. Base de Datos — Esquema Completo

Base de datos: **PORTAL_APM**
Script: `PORTAL_APM_COMPLETO.sql` v2.1

### Prefijos de Tablas

| Prefijo | Módulo | Tablas |
|---|---|---|
| `CORE_` | Framework / Sistema | 13 tablas |
| `TH_` | Talento Humano | 5 tablas |
| `BIT_` | Bitácoras | 4 tablas |
| `BIENES_` | Control de Bienes | 4 tablas |
| `ACCESO_` | Control de Acceso | 4 tablas |
| **Total** | | **30 tablas** |

### CORE_ (13 tablas)

#### CORE_Departamentos
```sql
id_departamento    INT IDENTITY PK
nombre_depto       NVARCHAR(100)
codigo_depto       NVARCHAR(20)
descripcion        NVARCHAR(250)
id_dep_padre       INT FK → CORE_Departamentos (autoref)
nivel              TINYINT DEFAULT 1
estado             TINYINT DEFAULT 1
fecha_creacion     DATETIME DEFAULT GETDATE()
```

#### CORE_Usuarios
```sql
id_usuario          INT IDENTITY PK
nombre_usuario      NVARCHAR(50) UNIQUE NOT NULL
correo              NVARCHAR(100) UNIQUE NOT NULL
nombre_completo     NVARCHAR(150)
hash_contrasena     NVARCHAR(512) NOT NULL          -- columna real (NO contrasena_hash)
salt                NVARCHAR(100) NULL
id_departamento     INT NULL FK → CORE_Departamentos
nivel_jerarquia     TINYINT DEFAULT 0               -- CHECK (0,1,2,3,4); 0=Operador…4=SuperAdmin
estado              TINYINT DEFAULT 1               -- CHECK (0,1) — NO "activo"
requiere_mfa        TINYINT DEFAULT 0
mfa_secreto         NVARCHAR(100) NULL
requiere_cambio_pass TINYINT DEFAULT 0
intentos_fallidos   TINYINT DEFAULT 0
fecha_bloqueo       DATETIME NULL
minutos_bloqueo     INT DEFAULT 30
tema_preferido      NVARCHAR(20) DEFAULT 'light'    -- CHECK ('light','dark','corporate')
cedula              NVARCHAR(20) NULL               -- puede ser NULL
foto                NVARCHAR(255) NULL
fecha_creacion      DATETIME DEFAULT GETDATE()
fecha_modificacion  DATETIME DEFAULT GETDATE()
```

> **Nota columnas eliminadas del schema antiguo**: `cargo`, `telefono`, `tema` (era NVARCHAR(10) '1'/'2'/'3'), `activo`, `req_cambio_pass`, `bloqueado_hasta`, `ultimo_acceso`.

#### CORE_Roles
```sql
id_rol             INT IDENTITY PK
codigo             NVARCHAR(30) NOT NULL UNIQUE    -- columna real (NO "codigo_rol")
nombre             NVARCHAR(100) NOT NULL
descripcion        NVARCHAR(250)
id_departamento    INT NULL FK → CORE_Departamentos
nivel_jerarquia    TINYINT DEFAULT 1
estado             TINYINT DEFAULT 1              -- columna real (NO "activo")
fecha_creacion     DATETIME DEFAULT GETDATE()
```

#### CORE_Usuarios_Roles
```sql
id_ur              INT IDENTITY PK
id_usuario         INT NOT NULL FK → CORE_Usuarios
id_rol             INT NOT NULL FK → CORE_Roles
fecha_asignacion   DATETIME DEFAULT GETDATE()
estado             TINYINT DEFAULT 1
```

#### CORE_Menu_Nodos  ← (reemplaza la antigua CORE_Menu)
```sql
id_nodo            INT IDENTITY PK
id_modulo          TINYINT NOT NULL               -- número de módulo organizacional (1=PE, 2=TI, 3=AJ…)
opcion             TINYINT NOT NULL DEFAULT 0     -- nivel 2 (0 = cabecera de módulo)
items              TINYINT NOT NULL DEFAULT 0     -- nivel 3 (0 = cabecera de área)
subitems           TINYINT NOT NULL DEFAULT 0     -- nivel 4 (0 = item directo)
descripcion        NVARCHAR(150) NOT NULL
url_ruta           NVARCHAR(200) NULL
icono              NVARCHAR(80) NULL
spa_habilitado     BIT DEFAULT 1
nivel_crud         TINYINT DEFAULT 1              -- 1=Ver, 2=Crear, 3=Editar, 4=Eliminar (BETWEEN 1-4)
estado             TINYINT DEFAULT 1
UNIQUE (id_modulo, opcion, items, subitems)       -- clave MOIS única
```

> La jerarquía MOIS: `(id_modulo, 0, 0, 0)` = módulo; `(id_modulo, N, 0, 0)` = área; `(id_modulo, N, M, 0)` = item; `(id_modulo, N, M, K)` = subitem.
> **134 nodos** cargados en seed para 11 módulos organizacionales.

#### CORE_Permisos_Nodo  ← (reemplaza la antigua CORE_Permisos)
```sql
id_permiso         INT IDENTITY PK
id_rol             INT NOT NULL FK → CORE_Roles
id_modulo          TINYINT NOT NULL
opcion             TINYINT NOT NULL DEFAULT 0
items              TINYINT NOT NULL DEFAULT 0
subitems           TINYINT NOT NULL DEFAULT 0
nivel_crud         TINYINT NOT NULL DEFAULT 1     -- CHECK BETWEEN 1 AND 4
estado             TINYINT DEFAULT 1
UNIQUE (id_rol, id_modulo, opcion, items, subitems)
```

#### CORE_Formularios
```sql
id_formulario      INT IDENTITY PK
nombre             NVARCHAR(100)
descripcion        NVARCHAR(250)
modulo             NVARCHAR(50)
estado             TINYINT DEFAULT 1
```

#### CORE_Formularios_Permisos
```sql
id_fp              INT IDENTITY PK
id_rol             INT FK → CORE_Roles
id_formulario      INT FK → CORE_Formularios
nivel_permiso      TINYINT DEFAULT 0  -- 0=ninguno, 1=lectura, 2=escritura, 3=admin, 4=total
estado             TINYINT DEFAULT 1
```

#### CORE_Sesiones
```sql
id_sesion          INT IDENTITY PK
id_usuario         INT NOT NULL FK → CORE_Usuarios
token              NVARCHAR(512) UNIQUE NOT NULL
ip_address         NVARCHAR(50)
user_agent         NVARCHAR(500)
fecha_inicio       DATETIME DEFAULT GETDATE()
fecha_expira       DATETIME NOT NULL              -- columna real (NO "activa" TINYINT)
estado             TINYINT DEFAULT 1              -- 0=cerrada, 1=activa
```

> La validez se comprueba via `fn_SesionValida(@token)` que evalúa `fecha_expira > GETDATE() AND estado=1`.

#### CORE_Auditoria
```sql
id_auditoria       INT IDENTITY PK
id_usuario         INT NULL FK → CORE_Usuarios
nombre_usuario     NVARCHAR(100) NULL             -- desnormalizado para consultas rápidas
modulo             NVARCHAR(50)
operacion          NVARCHAR(100)
detalle            NVARCHAR(MAX)
datos_antes        NVARCHAR(MAX) NULL
datos_despues      NVARCHAR(MAX) NULL
ip_address         NVARCHAR(50)
resultado          NVARCHAR(20)                   -- 'EXITO','ERROR','RECHAZADO'
fecha_registro     DATETIME DEFAULT GETDATE()
```

#### CORE_Notificaciones
```sql
id_notif           INT IDENTITY PK
id_usuario         INT NULL FK → CORE_Usuarios  -- NULL = broadcast
titulo             NVARCHAR(200)
mensaje            NVARCHAR(MAX)
tipo               NVARCHAR(50)  -- 'info','warning','error','success'
prioridad          TINYINT DEFAULT 1  -- 1=baja, 2=media, 3=alta
url_accion         NVARCHAR(300) NULL
leida              TINYINT DEFAULT 0
fecha_creacion     DATETIME DEFAULT GETDATE()
```

#### CORE_Contrasenas_Hist
```sql
id_hist            INT IDENTITY PK
id_usuario         INT FK → CORE_Usuarios
hash_contrasena    NVARCHAR(512)
fecha_cambio       DATETIME DEFAULT GETDATE()
```

#### CORE_Config
```sql
id_config          INT IDENTITY PK
modulo             NVARCHAR(50) NOT NULL           -- ej: 'CORE', 'TH', 'ACCESO'
clave              NVARCHAR(100) NOT NULL
valor              NVARCHAR(MAX)
tipo               NVARCHAR(20) DEFAULT 'string'  -- 'string','int','bool','json'
descripcion        NVARCHAR(250)
estado             TINYINT DEFAULT 1
UNIQUE (modulo, clave)
```

> **Configuraciones clave en seed**:
> - `(CORE, SSO_SECRET)` — clave HMAC-SHA256 para tokens SSO
> - `(CORE, LOGIN_MAX_INTENTOS)` — default 5
> - `(CORE, LOGIN_BLOQUEO_MINUTOS)` — default 30
> - `(CORE, SESSION_TIMEOUT_MINUTOS)` — default 480

### TH_ (5 tablas)

#### TH_Empleados
```sql
id_empleado        INT IDENTITY PK
id_usuario         INT NULL FK → CORE_Usuarios (UNIQUE — 1 usuario por empleado)
cedula             NVARCHAR(20) NOT NULL UNIQUE
nombres            NVARCHAR(100) NOT NULL   -- ⚠ SEPARADOS, no nombre_completo
apellidos          NVARCHAR(100) NOT NULL   -- ⚠ SEPARADOS, no nombre_completo
correo             NVARCHAR(150) NULL
telefono           NVARCHAR(20) NULL
fecha_nacimiento   DATE NULL
genero             CHAR(1) NULL  -- M/F/O
estado_civil       NVARCHAR(20) NULL  -- Soltero/Casado/Divorciado/Viudo/Union Libre
id_departamento    INT NULL FK → CORE_Departamentos
cargo              NVARCHAR(100) NULL
fecha_ingreso      DATE NULL
estado             TINYINT NOT NULL DEFAULT 1  -- CHECK (0,1)
foto               NVARCHAR(255) NULL
observaciones      NVARCHAR(1000) NULL
fecha_creacion     DATETIME2 NOT NULL DEFAULT GETDATE()
```

#### TH_Contratos
```sql
id_contrato        INT IDENTITY PK
id_empleado        INT NOT NULL FK → TH_Empleados (CASCADE)
tipo_contrato      NVARCHAR(30) NOT NULL  -- CHECK: Nombramiento/Contrato/Ocasional
fecha_inicio       DATE NOT NULL
fecha_fin          DATE NULL
salario            DECIMAL(10,2) NOT NULL  -- ⚠ campo es 'salario', no 'remuneracion'
cargo              NVARCHAR(100) NOT NULL
id_departamento    INT NULL FK → CORE_Departamentos
estado_contrato    NVARCHAR(20) NOT NULL DEFAULT 'Vigente'
                   -- CHECK: Vigente/Finalizado/Anulado  ⚠ NO es Terminado/Suspendido
estado             TINYINT NOT NULL DEFAULT 1  -- CHECK (0,1)
observaciones      NVARCHAR(1000) NULL
fecha_creacion     DATETIME2 NOT NULL DEFAULT GETDATE()
creado_por         INT NULL FK → CORE_Usuarios  -- ⚠ NO ACTION (no cascade)
```

#### TH_Adendas
```sql
id_adenda          INT IDENTITY PK
id_contrato        INT NOT NULL FK → TH_Contratos (CASCADE)
descripcion        NVARCHAR(255) NOT NULL
campo_modificado   NVARCHAR(100) NOT NULL
valor_anterior     NVARCHAR(500) NULL
valor_nuevo        NVARCHAR(500) NOT NULL
fecha_vigencia     DATE NOT NULL
estado             TINYINT NOT NULL DEFAULT 1  -- CHECK (0,1)
fecha_creacion     DATETIME2 NOT NULL DEFAULT GETDATE()
creado_por         INT NULL FK → CORE_Usuarios
```

#### TH_Novedades_Medicas
```sql
id_novedad         INT IDENTITY PK
id_empleado        INT FK → TH_Empleados
tipo_novedad       NVARCHAR(50)
fecha_inicio       DATE
fecha_fin          DATE NULL
descripcion        NVARCHAR(MAX)
documento_path     NVARCHAR(500) NULL
registrado_por     INT FK → CORE_Usuarios
fecha_registro     DATETIME DEFAULT GETDATE()
```

**Nota**: El modelo legacy usa `TH_NovedadesMedicas` (sin guión bajo), el esquema nuevo usa `TH_Novedades_Medicas`.

#### TH_Auditoria
```sql
id_audit_th        INT IDENTITY PK
id_usuario         INT FK → CORE_Usuarios
operacion          NVARCHAR(100)
detalle            NVARCHAR(MAX)
ip_address         NVARCHAR(50)
fecha_registro     DATETIME DEFAULT GETDATE()
```

### BIT_ (4 tablas)

#### BIT_Categorias
```sql
id_categoria       INT IDENTITY PK
nombre_categoria   NVARCHAR(100)
descripcion        NVARCHAR(250)
color              NVARCHAR(10) DEFAULT '#0ea5e9'
activo             TINYINT DEFAULT 1
```

#### BIT_Eventos
```sql
id_evento          INT IDENTITY PK
id_categoria       INT FK → BIT_Categorias
titulo             NVARCHAR(200)
descripcion        NVARCHAR(MAX)
fecha_evento       DATETIME DEFAULT GETDATE()
id_usuario         INT FK → CORE_Usuarios
id_departamento    INT NULL FK → CORE_Departamentos
prioridad          TINYINT DEFAULT 2   -- 1=Baja, 2=Media, 3=Alta
estado_evento      NVARCHAR(20) DEFAULT 'Pendiente'  -- Pendiente/En Proceso/Cerrado
observaciones      NVARCHAR(MAX) NULL
fecha_cierre       DATETIME NULL
estado             TINYINT DEFAULT 1   -- 0=anulado, 1=activo (distinto de estado_evento)
fecha_creacion     DATETIME DEFAULT GETDATE()
```

#### BIT_Archivos
```sql
id_archivo         INT IDENTITY PK
id_evento          INT FK → BIT_Eventos
nombre_archivo     NVARCHAR(255)
ruta_archivo       NVARCHAR(500)
tipo_mime          NVARCHAR(100)
tamanio_bytes      INT
id_usuario         INT FK → CORE_Usuarios
fecha_carga        DATETIME DEFAULT GETDATE()
```

#### BIT_Auditoria
```sql
id_audit_bit       INT IDENTITY PK
id_usuario         INT FK → CORE_Usuarios
operacion          NVARCHAR(100)
id_evento          INT NULL FK → BIT_Eventos
detalle            NVARCHAR(MAX)
ip_address         NVARCHAR(50)
fecha_registro     DATETIME DEFAULT GETDATE()
```

### BIENES_ (4 tablas)

#### BIENES_Categorias
```sql
id_categoria       INT IDENTITY PK
nombre_categoria   NVARCHAR(100)
descripcion        NVARCHAR(250)
activo             TINYINT DEFAULT 1
```

#### BIENES_Activos
```sql
id_activo          INT IDENTITY PK
codigo_bien        NVARCHAR(30) UNIQUE
nombre_bien        NVARCHAR(200)
descripcion        NVARCHAR(MAX) NULL
id_categoria       INT FK → BIENES_Categorias
id_departamento    INT FK → CORE_Departamentos
responsable        NVARCHAR(150) NULL
marca              NVARCHAR(100) NULL
modelo             NVARCHAR(100) NULL
serial             NVARCHAR(100) NULL
fecha_adquisicion  DATE NULL
valor_adquisicion  DECIMAL(12,2) NULL
estado_bien        NVARCHAR(30) DEFAULT 'Activo'  -- Activo/En Reparacion/Baja/Perdido
estado             TINYINT DEFAULT 1
fecha_registro     DATETIME DEFAULT GETDATE()
```

#### BIENES_Movimientos
```sql
id_movimiento      INT IDENTITY PK
id_activo          INT FK → BIENES_Activos
tipo_movimiento    NVARCHAR(30)  -- Asignacion/Transferencia/Devolucion/Mantenimiento/Baja
id_depto_origen    INT NULL FK → CORE_Departamentos
id_depto_destino   INT NULL FK → CORE_Departamentos
fecha_movimiento   DATETIME DEFAULT GETDATE()
observaciones      NVARCHAR(MAX) NULL
id_usuario         INT FK → CORE_Usuarios
```

#### BIENES_Auditoria
```sql
id_audit_bienes    INT IDENTITY PK
id_usuario         INT FK → CORE_Usuarios
operacion          NVARCHAR(100)
id_activo          INT NULL FK → BIENES_Activos
detalle            NVARCHAR(MAX)
ip_address         NVARCHAR(50)
fecha_registro     DATETIME DEFAULT GETDATE()
```

### ACCESO_ (4 tablas)

#### ACCESO_Visitantes
```sql
id_visitante       INT IDENTITY PK
cedula             NVARCHAR(20) NOT NULL UNIQUE
nombres            NVARCHAR(100) NOT NULL         -- separados (NO nombre_completo)
apellidos          NVARCHAR(100) NOT NULL         -- separados (NO nombre_completo)
empresa            NVARCHAR(150) NULL
telefono           NVARCHAR(30) NULL
correo             NVARCHAR(100) NULL
estado             TINYINT DEFAULT 1              -- (NO "activo")
fecha_registro     DATETIME2 DEFAULT GETDATE()
```

> `nombres + ' ' + apellidos` = nombre completo en PHP (ya corregido en view).
> `fecha_creacion` no existe — columna real es `fecha_registro`.

#### ACCESO_Vehiculos
```sql
id_vehiculo        INT IDENTITY PK
placa              NVARCHAR(20) UNIQUE
marca              NVARCHAR(50)
modelo             NVARCHAR(50)
color              NVARCHAR(30)
id_visitante       INT NULL FK → ACCESO_Visitantes
activo             TINYINT DEFAULT 1
```

#### ACCESO_Registros
```sql
id_registro        INT IDENTITY PK
id_visitante       INT NULL FK → ACCESO_Visitantes
tipo_persona       NVARCHAR(20)  -- Visitante/Empleado/Proveedor
persona_visita     NVARCHAR(150) NULL  -- a quién visita
id_vehiculo        INT NULL FK → ACCESO_Vehiculos
tipo_acceso        NVARCHAR(20)  -- Entrada/Salida
fecha_hora         DATETIME DEFAULT GETDATE()
observaciones      NVARCHAR(MAX) NULL
id_usuario_reg     INT FK → CORE_Usuarios
```

#### ACCESO_Auditoria
```sql
id_audit_acc       INT IDENTITY PK
id_usuario         INT FK → CORE_Usuarios
operacion          NVARCHAR(100)
id_registro        INT NULL FK → ACCESO_Registros
detalle            NVARCHAR(MAX)
ip_address         NVARCHAR(50)
fecha_registro     DATETIME DEFAULT GETDATE()
```

### Índices (26 total)

Índices clave:
- `UQ_CORE_Usuarios_username` — `CORE_Usuarios(nombre_usuario)` UNIQUE
- `UQ_CORE_Usuarios_correo` — `CORE_Usuarios(correo)` UNIQUE
- `IX_CORE_Auditoria_fecha` — `CORE_Auditoria(fecha_registro DESC)`
- `IX_CORE_Auditoria_usuario` — `CORE_Auditoria(id_usuario)`
- `UQ_TH_Empleados_cedula` — `TH_Empleados(cedula)` UNIQUE
- `UQ_TH_Contratos_numero` — `TH_Contratos(numero_contrato)` UNIQUE
- `IX_TH_Contratos_empleado` — `TH_Contratos(id_empleado)`
- `IX_TH_Contratos_estado` — `TH_Contratos(estado_contrato)`
- `IX_BIT_Eventos_fecha` — `BIT_Eventos(fecha_evento DESC)`
- `IX_BIT_Eventos_estado` — `BIT_Eventos(estado_evento)`
- `UQ_BIENES_codigo` — `BIENES_Activos(codigo_bien)` UNIQUE
- `IX_BIENES_estado` — `BIENES_Activos(estado_bien)`
- `UQ_ACCESO_cedula` — `ACCESO_Visitantes(cedula)` UNIQUE
- `IX_ACCESO_Registros_fecha` — `ACCESO_Registros(fecha_hora DESC)`

---

## 11. Stored Procedures

### sp_Login  ← 9 parámetros (1 IN + 8 OUT)
```sql
sp_Login(
    @nombre_usuario    NVARCHAR(50),           -- IN
    @resultado         NVARCHAR(30) OUTPUT,    -- 'EXITO'|'USUARIO_NO_EXISTE'|'INACTIVO'|'BLOQUEADO'
    @id_usuario        INT OUTPUT,
    @hash_contrasena   NVARCHAR(512) OUTPUT,   -- hash bcrypt para verificar en PHP
    @nivel_jerarquia   TINYINT OUTPUT,         -- 0–4
    @req_cambio_pass   BIT OUTPUT,
    @nombre_completo   NVARCHAR(150) OUTPUT,
    @tema_preferido    NVARCHAR(20) OUTPUT,    -- 'light'|'dark'|'corporate'
    @id_departamento   INT OUTPUT
)
```
- Lee `@max_intentos` y `@minutos_bloqueo` desde `CORE_Config` (no parámetro hardcodeado)
- Verifica estado=1 y desbloqueo por tiempo
- **No verifica contraseña** — retorna el hash para que PHP haga `password_verify`
- Crea registro en `CORE_Sesiones` con `fecha_expira`
- Si resultado='EXITO': resetea `intentos_fallidos`, actualiza `fecha_modificacion`

> PHP llama con `sqlsrv_prepare('{CALL sp_Login(?,?,?,?,?,?,?,?,?)}', $params)` — 9 `?` obligatorios.

### sp_RegistrarFalloLogin  ← 2 parámetros
```sql
sp_RegistrarFalloLogin(@nombre_usuario NVARCHAR(50), @ip_address NVARCHAR(50))
```
- Incrementa `intentos_fallidos`
- Lee límite y minutos desde `CORE_Config WHERE modulo='CORE' AND clave='LOGIN_MAX_INTENTOS'`
- Si supera límite → `fecha_bloqueo = GETDATE()`, registra en `CORE_Auditoria`

### sp_Logout  ← 2 parámetros
```sql
sp_Logout(@token NVARCHAR(512), @ip_address NVARCHAR(50))
```
- UPDATE `CORE_Sesiones SET estado=0` WHERE `token = @token`
- INSERT en `CORE_Auditoria` con operacion='LOGOUT'

### sp_CambiarContrasena  ← 4 parámetros
```sql
sp_CambiarContrasena(@id_usuario INT, @nuevo_hash NVARCHAR(512), @nuevo_salt NVARCHAR(100), @max_historial INT)
```
- Verifica que el nuevo hash no esté en `CORE_Contrasenas_Hist` (últimos `@max_historial` registros)
- UPDATE `CORE_Usuarios.hash_contrasena` y `requiere_cambio_pass=0`
- INSERT en `CORE_Contrasenas_Hist`
- **BUG #3** (sin corregir): `AuthController.cambiarContrasena()` pasa `SecurityHelper::getClientIp()` como tercer argumento, pero ese mapea a `@nuevo_salt`.

### sp_GetMenuUsuario  ← 1 parámetro
```sql
sp_GetMenuUsuario(@id_usuario INT)
```
Retorna filas MOIS con columnas: `id_nodo`, `id_modulo`, `opcion`, `items`, `subitems`, `descripcion`, `url_ruta`, `icono`, `spa_habilitado`, `nivel_crud`.
JOIN: `CORE_Usuarios_Roles → CORE_Permisos_Nodo → CORE_Menu_Nodos`.

> `models/Acceso_/Menu.php` consume este SP y construye el árbol de 4 niveles.

### sp_RegistrarAuditoria  ← 11 parámetros
```sql
sp_RegistrarAuditoria(
    @id_usuario INT, @nombre_usuario NVARCHAR(100), @modulo NVARCHAR(50),
    @operacion NVARCHAR(100), @detalle NVARCHAR(MAX),
    @datos_antes NVARCHAR(MAX), @datos_despues NVARCHAR(MAX),
    @ip_address NVARCHAR(50), @resultado NVARCHAR(20),
    @tabla_afectada NVARCHAR(100), @id_registro_afectado INT
)
```
INSERT en `CORE_Auditoria` con todos los campos.

### sp_PurgarAuditoria  ← sin parámetros
Lee `AUDIT_RETENTION_DAYS` desde `CORE_Config`.
DELETE de `CORE_Auditoria` donde `fecha_registro < DATEADD(day, -N, GETDATE())`.

### sp_GetKPIs_Ejecutivo / sp_GetKPIs_Operativo
Sin parámetros. Retornan recordsets de KPIs.
> La implementación real en `DashboardModel` usa **consultas inline directas**, no estos SPs.
> Los SPs existen en el schema pero no son invocados por el PHP actual.

---

## 12. Funciones SQL

### fn_TienePermisoNodo  ← (reemplaza la antigua fn_TienePermiso)
```sql
dbo.fn_TienePermisoNodo(
    @id_usuario INT, @id_modulo TINYINT,
    @opcion TINYINT, @items TINYINT, @subitems TINYINT,
    @nivel_crud TINYINT, @activo TINYINT
) → BIT
```
Retorna 1 si el usuario tiene acceso al nodo MOIS indicado con nivel CRUD mínimo.
JOIN: `CORE_Usuarios_Roles → CORE_Permisos_Nodo`.
Usado por `ModuleSecurity::checkAccess()` y `Menu::hasPermission()`.

### fn_TienePermisoFormulario
```sql
dbo.fn_TienePermisoFormulario(@id_usuario INT, @id_formulario INT, @nivel_minimo INT) → BIT
```
Retorna 1 si `nivel_permiso >= @nivel_minimo` en `CORE_Formularios_Permisos`.

### fn_SesionValida
```sql
dbo.fn_SesionValida(@token NVARCHAR(512)) → BIT
```
Retorna 1 si `CORE_Sesiones WHERE token=@token AND estado=1 AND fecha_expira > GETDATE()`.
> Firma actualizada: solo recibe `@token` (no `@id_usuario` como en el schema anterior).

### fn_GetArbolDepartamento
```sql
dbo.fn_GetArbolDepartamento(@id_departamento INT)
    → TABLE (id_departamento INT, nombre_depto NVARCHAR(100), nivel TINYINT, path NVARCHAR(500))
```
CTE recursiva: retorna el departamento dado + todos sus hijos recursivamente.

---

## 13. Vistas SQL

### vw_MenuPorUsuario  ← (reemplaza la antigua vw_MenuUsuario)
```sql
SELECT u.id_usuario, mn.*
FROM CORE_Usuarios u
JOIN CORE_Usuarios_Roles ur ON u.id_usuario=ur.id_usuario AND ur.estado=1
JOIN CORE_Permisos_Nodo pn ON ur.id_rol=pn.id_rol AND pn.estado=1
JOIN CORE_Menu_Nodos mn ON mn.id_modulo=pn.id_modulo AND mn.opcion=pn.opcion
                        AND mn.items=pn.items AND mn.subitems=pn.subitems AND mn.estado=1
WHERE u.estado=1
```
Usada por `sp_GetMenuUsuario` para filtrar nodos accesibles por usuario.

### vw_FichaEmpleado
Vista principal de Talento Humano:
```sql
SELECT e.*, u.nombre_usuario, d.nombre_depto AS nombre_departamento, d.codigo_depto,
       (subquery TH_Contratos vigente) AS tipo_contrato_vigente, salario,
       DATEDIFF(month, e.fecha_ingreso, GETDATE()) AS meses_servicio,
       DATEDIFF(year, e.fecha_nacimiento, GETDATE()) AS edad,
       (subquery COUNT TH_Novedades_Medicas) AS novedades_activas
```

### vw_AuditoriaGlobal
```sql
SELECT a.*, u.nombre_usuario
FROM CORE_Auditoria a
LEFT JOIN CORE_Usuarios u ON a.id_usuario = u.id_usuario
```
> **No es UNION** — es solo `CORE_Auditoria` con JOIN a `CORE_Usuarios`.
> Las auditorías de módulos (TH_, BIT_, etc.) se insertan en `CORE_Auditoria` directamente via `sp_RegistrarAuditoria`.
> Columna `nombre_usuario` está desnormalizada en `CORE_Auditoria` (también disponible via JOIN).

### vw_KPIs_TH
Conteos y promedios de empleados, contratos y adendas. Filtra `estado=1`.

### vw_KPIs_Bienes
Conteos por `estado_bien` ('Activo'/'En Reparacion'/'Baja'/'Perdido'), valor total de inventario.

### vw_KPIs_Acceso
Ingresos/salidas del día, semana y mes desde `ACCESO_Registros`.

### vw_KPIs_Bitacoras
Eventos por `estado_evento` y `prioridad`, tiempo promedio de cierre en días.

### vw_ResumenRoles
Usa `FOR XML PATH('')` para concatenar permisos por rol (SQL Server 2014 — sin STRING_AGG).

### vw_SSO_Usuarios
```sql
SELECT id_usuario, nombre_usuario, nombre_completo, correo, nivel_jerarquia, cedula, estado
FROM CORE_Usuarios WHERE estado=1
```

### vw_SSO_Menu
```sql
SELECT mn.*, pn.id_rol, pn.nivel_crud
FROM CORE_Menu_Nodos mn
JOIN CORE_Permisos_Nodo pn ON pn.id_modulo=mn.id_modulo AND pn.opcion=mn.opcion
                           AND pn.items=mn.items AND pn.subitems=mn.subitems
WHERE mn.estado=1 AND pn.estado=1
```

---

## 14. Datos Semilla

Ejecutado por `PORTAL_APM_COMPLETO.sql`. Confirmado por `logs/sql_setup_output.txt`:

```
30 tablas | 22 índices | 9 SPs | 4 funciones | 10 vistas
21 usuarios semilla | Contraseña por defecto: Apm2024*
```

### Usuarios Semilla (21 usuarios)

| Usuario | Nombre | Área |
|---|---|---|
| admin | Administrador | Sistema |
| aauditor | Ana Auditora | Auditoría |
| cmendoza | Director Jurídico | Asesoría Jurídica |
| pvasquez | Abogada Sr | Asesoría Jurídica |
| jperez | Abogado Jr | Asesoría Jurídica |
| ksuarez | Supervisora | Control de Acceso |
| ltorres | Operador | Control de Acceso |
| apalma | Tramitador | Trámites |
| rchavez | Analista CCTV | Videovigilancia |
| mlema | Secretaria | Secretaría |
| fmora | Jefe Infraestructura | Infraestructuras |
| esalazar | Supervisora | Inspectores Portuarios |
| dalvarado | Inspector | Inspectores Portuarios |
| mquintero | Jefa TH | Talento Humano |
| glopez | Analista TH | Talento Humano |
| acastro | Gerente General | Gerencia |
| mrecalde | Asistente Gerencia | Gerencia |
| rpita | Directora Admin | Administración |
| jcuesta | Analista Admin | Administración |
| hmunoz | Director Finanzas | Finanzas |
| nvera | Analista Finanzas | Finanzas |

**Contraseña por defecto**: `Apm2024*` — bcrypt `$2y$12$...`

### Departamentos (20 registros)
Gerencia General, Auditoría Interna, Asesoría Jurídica, Control de Acceso / Seguridad, Trámites y Servicios, Videovigilancia / CCTV, Secretaría General, Infraestructuras, Inspección Portuaria, Talento Humano, Sistemas / TI, Finanzas, Administración, + jerarquía padre/hijo

### Empleados (11 registros TH_Empleados)
11 empleados con fichas completas (cedula, cargo_nominal, fecha_ingreso, fecha_nacimiento, etc.)

### Contratos (11 registros TH_Contratos)
11 contratos en formato `CONT-YYYY-NNNN`

### Roles (21 registros CORE_Roles)
21 roles, uno por cada usuario semilla aproximadamente. Columna `codigo` (no `codigo_rol`), columna `estado` (no `activo`).

### Nodos de Menú MOIS (134 registros CORE_Menu_Nodos)

Distribuidos en 11 módulos organizacionales:

| id_modulo | Módulo | Áreas / Opciones principales |
|---|---|---|
| 1 | Planificación Estratégica | Objetivos, Indicadores, Reportes |
| 2 | Tecnología e Informática | Infraestructura, Soporte, Seguridad, Administración |
| 3 | Asesoría Jurídica | Contratos, Normativa, Litigios |
| 4 | Infraestructura Portuaria | Proyectos, Mantenimiento, Inspección |
| 5 | Control de Acceso / Garita | Registros, Visitantes, Vehículos, Reportes |
| 6 | Operaciones Portuarias | Naves, Manifiestos, Estadísticas |
| 7 | Gerencia General | Dashboard Ejecutivo, Reportes, Actas |
| 8 | Dirección de Servicios | Facturación, Tarifas, Clientes |
| 9 | Dirección Administrativa / Bienes | Inventario, Movimientos, Bajas |
| 10 | Dirección Financiera | Presupuesto, Contabilidad, Tesorería |
| 11 | Talento Humano | Empleados, Contratos, Nómina, Evaluaciones |

Cada módulo tiene: 1 nodo raíz (`opcion=0,items=0,subitems=0`), múltiples áreas (`opcion>0,items=0`), ítems directos y subitems.

---

## 15. Seguridad

### Autenticación

1. **Login**: CSRF check → `sp_Login` (usuario/bloqueo) → `password_verify` (PHP bcrypt) → `session_regenerate_id(true)` → guardar sesión
2. **Sesión**: `$_SESSION['user_id']`, `$_SESSION['last_activity']`, timeout 1800s
3. **CSRF**: Token en sesión + verificación en POST (header `X-CSRF-TOKEN` o `_csrf_token` en body)
4. **Bloqueo**: 5 intentos fallidos → `bloqueado_hasta = GETDATE() + 30 min`

### Niveles de Jerarquía

| Nivel | Label | Acceso |
|---|---|---|
| 0 | Operativo | Acceso básico a módulos asignados |
| 1 | Analista | Funciones de consulta y registro |
| 2 | Jefatura | Puede dar de baja, soft-delete empleados |
| 3 | Director | Acceso completo, `/admin/*` |
| 4 | Super Admin | Máximo nivel, sin restricciones |

**Nota**: Los labels 0-4 provienen de `modules/Central/views/admin/usuarios.php`. La SYSPORT y los helpers solo mencionaban 3 niveles (1-3), pero el sistema real usa 5 (0-4).

### SSO Tokens

Formato: `base64url(json) + "." + base64url(signature)`
```php
$payload = ['uid' => $userId, 'cedula' => $cedula, 'exp' => time() + 1800, 'ip' => $_SERVER['REMOTE_ADDR']];
$sig = hash_hmac('sha256', base64url($json), $SECRET);
```
**CORREGIDO** (SEC #1): La clave se carga de `CORE_Config WHERE modulo='CORE' AND clave='SSO_SECRET'`. Ya no está hardcodeada en el código fuente.

### HTTP Security Headers (setSecurityHeaders())

```http
X-Frame-Options: DENY
X-Content-Type-Options: nosniff
X-XSS-Protection: 1; mode=block
Strict-Transport-Security: max-age=31536000; includeSubDomains
Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' [CDNs]; ...
Referrer-Policy: strict-origin-when-cross-origin
```

**Debilidad**: CSP usa `'unsafe-inline'`, anulando la protección contra XSS inline.

### Password Hashing

```php
password_hash($password, PASSWORD_BCRYPT, ['cost' => 12])
```
Seed usa `$2y$12$...` (bcrypt estándar).

### Código de Seguridad Legacy (models/Acceso_/Usuario.php)

**CORREGIDO** (BUG #8): El bypass mock `str_replace('$2b$12$HASH_', '', $hash)` fue **eliminado completamente**. El archivo fue reescrito para usar el flujo correcto: `sp_Login` (9 params) → `password_verify($password, $hash)`. La capa legacy es dead code (autoloader carga `modules/Credenciales/models/UsuarioModel.php` con prioridad).

---

## 16. Configuración y Variables de Entorno

### `config/app.php` — Único archivo de configuración

**Archivo único** en `config/`. El `.env`, `config/globals.php` y `config/routes.php` fueron **eliminados**. Para cambiar entorno solo se edita este archivo.

```php
// Rutas
define('ROOT',           dirname(__DIR__));

// Aplicación
define('APP_NAME',       'Portal APM');
define('APP_SHORT_NAME', 'APM');
define('APP_VERSION',    '2.0.0');
define('APP_ENV',        'development');     // 'development' | 'production'
define('DEBUG_MODE',     true);
define('APP_URL',        'http://localhost:8080');
define('DEFAULT_TIMEZONE', 'America/Guayaquil');

// Sesión
define('SESSION_TIMEOUT',      1800);   // segundos — inactividad
define('SESSION_HOURS_EXPIRA', 8);      // horas — duración token BD (CORE_Sesiones)

// Base de datos (SQL Server)
define('DB_SERVER',     '.\\VICTUS');   // instancia local
define('DB_NAME',       'PORTAL_APM');
define('DB_USER',       '');            // vacío = Windows Auth
define('DB_PASS',       '');
define('DB_TRUST_CERT', true);
define('DB_ENCRYPT',    false);
```

> Para cambiar a producción: editar `APP_ENV`, `DEBUG_MODE`, `APP_URL`, `DB_SERVER`.  
> `core/Env.php` se mantiene cargado por backward compat con `INICIAR_AUTOMATICO.ps1` — el PS1 aún referencia `.env` (eliminado), por lo que el auto-update de `APP_URL` del PS1 **no funciona** con la nueva config.

### Variables de Sesión (capa activa)

```php
$_SESSION['user_id']            // int — ID del usuario
$_SESSION['nombre_usuario']     // string — login name
$_SESSION['nombre_completo']    // string — display name
$_SESSION['nivel_jerarquia']    // int 0|1|2|3|4
$_SESSION['id_departamento']    // int
$_SESSION['tema']               // string 'light'|'dark'|'corporate'
$_SESSION['last_activity']      // int timestamp
$_SESSION['_csrf_token']        // string hex 64 chars
```

### Variables de Sesión (capa legacy, diferentes)

```php
$_SESSION['user_id']      // mismo
$_SESSION['sso_token']    // token legacy (distinto del nuevo SSO)
$_SESSION['user_dept']    // departamento (distinto de id_departamento)
```

---

## 17. Frontend — CSS/JS/Temas

### Sistema de Temas

**3 temas** definidos en `css/variables.css`, aplicados como clase en `<body>`:

| Clase | Nombre interno | Label en UI |
|---|---|---|
| `body.t1` | Institucional (light) | "Institucional" |
| `body.t2` | Cyber Dark (dark) | "Cyber Dark" |
| `body.t3` | Porto Glass (glassmorphism) | "Porto Glass" |

El tema se persiste en:
1. `localStorage.setItem('apm_theme', t)` (cliente)
2. `$_SESSION['tema']` (servidor)
3. `CORE_Usuarios.tema` (BD, via `POST /set-theme`)

#### Tokens de color por tema

**t1 (Institucional)**:
- `--bg-app: #F0F4FA` | `--bg-sidebar: #ffffff`
- `--primary-app: #1A3A5C` | `--primary-hover: #2E75B6`
- `--text-app: #1A253C` | `--text-muted: #64748B`

**t2 (Cyber Dark)**:
- `--bg-app: #050A14` | `--bg-sidebar: #0D162B`
- `--primary-app: #1E3A5F` | `--primary-hover: #3B82F6`
- `--text-app: #E2E8F0` | `--text-muted: #94A3B8`

**t3 (Porto Glass)**:
- `--bg-app: #071D3A` | fondo `linear-gradient(135deg, #071D3A 0%, #034839 100%)`
- `--bg-sidebar: rgba(15,29,58,0.4)` (glassmorphism)
- `--primary-app: #0891B2` | `--primary-hover: #10B981`
- `--backdrop: blur(16px)` — backdrop-filter en sidebar t3

#### Tokens globales (siempre activos, en `:root`)

```css
--font-body: 'Sora', sans-serif;
--font-code: 'JetBrains Mono', monospace;
--th: 56px;          /* topbar height */
--sw: 290px;         /* sidebar width */
--r-sm: 6px; --r-md: 10px; --r-lg: 18px;
--ease: .22s cubic-bezier(.4,0,.2,1);
--success: #10B981; --warn: #F59E0B; --danger: #EF4444;
```

### Login Page (diseño diferente)

`css/login.css` — usado como `<style>` inline en `modules/Credenciales/views/login/index.php`.

- Fuente: Outfit (no Sora)
- Diseño split: izquierdo animado (estrellas, agua SVG, barco) + derecho form
- 3 temas aplicados con clases `#login.t1`, `#login.t2`, `#login.t3`
- Switcher de tema en esquina superior derecha
- 21 cuentas demo en panel inferior
- Spinner animado en botón login
- MFA screen preparada (CSS presente aunque funcionalidad no implementada)

### `css/style.css` (~3000 líneas)

Design system completo:
- `app-shell` Flexbox layout: sidebar + main-wrapper
- `.sidebar.collapsed { width: 0 !important; visibility: hidden !important; }` — colapso total
- `.sm-header-block`, `.sidebar-mods`, `.sm-section` — bloques sidebar
- Topbar fijo, breadcrumb, notif badge, user dropdown
- Cards: `.kpi-card`, `.table-card`, `.chart-card`
- Badges: `.badge-success`, `.badge-warning`, `.badge-danger`, `.badge-info`
- Paginación, filtros, formularios
- Toasts: `#toast-container .toast`
- Spinner/loader: `#spa-loader`
- Grid responsivo con `@media` breakpoints
- Variables de compatibilidad: `--color-primary`, `--card-bg`, etc. que mapean a tokens UNIFICADO

### `js/main.js` — NO EXISTE

`js/main.js` **no existe en disco**. El directorio `js/` está vacío. Las referencias en docs anteriores a este archivo eran incorrectas.

### `public/js/app.js` — SPA Orchestrator (único y activo)

El único SPA orchestrator del sistema. Cargado por `shell.php`.

```javascript
navigate(url) → fetch(url, {headers: {'X-CSRF-Token': getCsrfToken()}})
```
- `window.C.setTheme(t)` — cambia clase body, localStorage
- `window.C.selectThemeDirect(t, e)` — selecciona tema + cierra dropdown
- Sidebar toggle, accordion via `[data-submenu]`
- `navigateToSpa(path)` → `executeSpaFetch(url, options, pushState)`
  - Intercepta: links `[data-spa]`, `.sidebar-mods`, `.sidebar`, `.btn-module`
  - Script loader secuencial para scripts en contenido nuevo
  - **Dispatcha**: `window.dispatchEvent(new CustomEvent('spa:loaded'))`
  - `history.pushState` y `updateSidebarHighlights()`
- Notificaciones: `loadNotifications()` → fetch `/notificaciones/recientes`
- Theme switcher: `.theme-toggle-btn[data-theme-target]` → persiste via `POST /set-theme`
- Form submit SPA intercept: GET→URLSearchParams, POST→FormData
- `window.addEventListener('popstate', ...)` → back/forward

### `public/js/charts.js` — ApexCharts Wrapper

```javascript
window.createChart(target, options) → ApexCharts instance
window.destroyChart(id)
window.destroyAllCharts()
```

- Registro en `registry{}` para evitar duplicados
- Lee CSS custom properties al init (colores responsivos a tema)
- Auto-init elementos con `[data-chart]` + `[data-chart-options='{}']`
- Escucha `spa:loaded` CustomEvent para reinicializar charts

### Sidebar

`modules/Central/views/layouts/sidebar.php` — 4 niveles de acordeón:

1. **Módulo** (`toggleSidebarModule`) — colapsa otros al abrir
2. **Área** (`toggleSidebarArea`)
3. **Opción** — link con `data-spa`
4. **Subopción** (`toggleSidebarSubopt`)

Características:
- Flyout popover card en hover (descripción, etiqueta de módulo)
- `normalizeFaIcon()` — mapea íconos Themify → FA6
- `sidebar-user-card` en footer: avatar con iniciales + nombre + cargo + logout
- Iconos: Font Awesome 6.5 + Lucide
- **CORREGIDO (BUG #14)**: usa claves `label`/`id` que retorna `Menu::getUserMenu()` — antes pedía `name`/`code` inexistentes → `Undefined array key` warnings. `ltrim($url ?? '', '/')` para url nullable.

### Topbar

`modules/Central/views/layouts/topbar.php`:
- Breadcrumb dinámico (`#breadcrumb-text`)
- Botón búsqueda
- Dropdown temas (3 botones llamando `C.selectThemeDirect(1|2|3)`)
- Badge de notificaciones (máx 99, carga desde `/notificaciones/recientes`)
- User dropdown: "Mi Perfil", "Cambiar Contraseña", "Administración" (si nivel≥3), "Cerrar Sesión"

### `shell.php` — Layout Principal

CDNs cargados:
```html
<!-- Fuentes -->
https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700;800
https://fonts.googleapis.com/css2?family=Fira+Code:wght@400;500
https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500

<!-- Iconos -->
https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css
https://unpkg.com/lucide@latest/dist/umd/lucide.min.js

<!-- Charts -->
https://cdn.jsdelivr.net/npm/apexcharts@3.45.1/dist/apexcharts.min.js
```

JS Global expuesto:
```javascript
window.APP_URL = '<?= APP_URL ?>';
window.APP_USER = {
    id: <?= $_SESSION['user_id'] ?>,
    nivel: <?= $_SESSION['nivel_jerarquia'] ?>,
    tema: '<?= $_SESSION['tema'] ?>'
};
```

Estructura HTML:
```html
<body class="t1">  <!-- clase actualizada por main.js según tema -->
  <div class="app-shell">
    sidebar.php
    <div class="main-wrapper">
      topbar.php
      <main id="main-spa-container">
        <?= $content ?>  <!-- inyectado por View::render() -->
      </main>
    </div>
  </div>
  <div id="spa-loader">...</div>
  <div id="toast-container">...</div>
</body>
```

---

## 18. Scripts de Instalación y Utilidades

### `SETUP_PROYECTO.ps1` — Asistente de Instalación Interactivo

Reescrito como asistente por pasos con menús (detalle completo en §20.9). Resumen:
1. Detecta XAMPP / WampServer / Laragon / PHP standalone / PHP en PATH en todos los discos — menú de selección
2. Verifica PHP 8.x + extensión `sqlsrv` (con guía de instalación si falta) + ODBC Driver
3. Pregunta modo de servidor: Apache (lee puerto de `httpd.conf`, junction/copia a htdocs) o PHP integrado (puerto libre)
4. Detecta instancias SQL Server (registro + servicios), autenticación Windows/SQL
5. **Edita `config/app.php` automáticamente** (APP_URL, DB_*) y regenera `.env` — UTF-8 sin BOM
6. Ejecuta `PORTAL_APM_COMPLETO.sql` vía `sqlcmd` (compatible ODBC 18 `-C` con fallback) y **prueba la conexión real desde PHP**
7. Verifica archivos críticos, genera `.htaccess`/`web.config`, inicia servidor y abre el navegador

### `SETUP_PROYECTO.bat`

Lanzador del PS1 con `powershell -ExecutionPolicy Bypass -NoProfile` + verificación de PowerShell + `chcp 65001`.

### `INICIAR_AUTOMATICO.ps1`

Inicio rápido de sesión de trabajo:
1. Detecta PHP en WampServer (`C:\wamp64\bin\php`) o XAMPP (`C:\xampp\php`)
2. Mata procesos PHP anteriores (`Stop-Process -Name php -Force`)
3. Lee `.env` para obtener `DB_SERVER`, `DB_NAME`, `DB_USER`, `DB_PASS`
4. Ejecuta `PORTAL_APM_COMPLETO.sql` via `sqlcmd`
5. Busca puerto libre a partir de **8080** (no 8000)
6. **Actualiza `APP_URL` en `.env`** con el puerto libre detectado
7. Lanza `php -S localhost:{port}` en background
8. Muestra URL (`http://localhost:{port}/login`) e intenta abrir en navegador
9. Credenciales mostradas en consola: `admin / Apm2024*`

### `INICIAR_AUTOMATICO.bat`

Lanzador del PS1 de inicio automático.

### `db/run_migration.php`

Ejecuta `db/update_menu.sql` dividiendo por batches `GO`. La ruta hardcodeada fue corregida a relativa, pero `$root` queda fuera de scope dentro del método `run()` (ver INC #4).

### `db/run_seed.php`

Ejecuta `db/seed_large.sql` en una única transacción, dividiendo por batches `GO`.

### `db/alter_sp.php`

Recrea `sp_GetMenuUsuario` usando schema legacy (tablas `Menu_Opciones`, `Departamentos_Modulos`, `Permisos_Grupos_Roles`, `Usuarios_Grupos_Roles`). **BUG**: usa `self::$db->exec($sql)` — la propiedad `$db` no es PDO, es `Database` singleton que usa sqlsrv. No funciona.

### `db/list_tables.php`

Lista todas las tablas via `INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE='BASE TABLE'`.

### `check_dashboard.py`

Utilidad Python diagnóstico que verifica si existe `views/General/dashboard/index.php`.

### `logs/test_conn.php`

Utilidad PHP para probar la conexión a la base de datos.

### `logs/sql_setup_output.txt`

Log de la última ejecución de `sqlcmd` con `PORTAL_APM_COMPLETO.sql`. Confirma instalación exitosa: 30 tablas, 22 índices, 9 SPs, 4 funciones, 10 vistas, 21 usuarios.

---

## 19. Bugs y Observaciones Críticas

### BUG #1 — ModuleSecurity::checkAccess() y audit() — **CORREGIDO**

**Archivo**: `core/ModuleSecurity.php`  
**Problema original**: Llamaba `$stmt->fetchAll()` y `$stmt->closeCursor()` sobre resource sqlsrv (no `SqlSrvStatement`).  
**Estado**: CORREGIDO — archivo completamente reescrito. `checkAccess()` ahora usa `fn_TienePermisoNodo` con mapeo MOIS; `audit()` usa clase anónima que extiende Model con `$this->free($stmt)`.

### BUG #2 — ThDatabase::connect() — **CORREGIDO**

**Archivo**: `core/ThDatabase.php`  
**Problema original**: Llamaba `Database::getConnection('database_th')` que no existe.  
**Estado**: CORREGIDO — ahora usa `Database::getInstance()->getConn()`. TH comparte la misma conexión singleton.

### BUG #3 — AuthController::cambiarContrasena()

**Archivo**: `modules/Credenciales/controllers/AuthController.php`  
**Problema**: El 3er parámetro de `sp_CambiarContrasena` es `@nuevo_salt`, pero se pasa `SecurityHelper::getClientIp()`.  
**Fix**:
```php
// Antes (bug):
$this->query('EXEC sp_CambiarContrasena ?, ?, ?', [$userId, $hash, SecurityHelper::getClientIp()]);

// Después (correcto):
$this->query('EXEC sp_CambiarContrasena ?, ?, ?', [$userId, $hash, '']);
```

### BUG #4 — UsuarioModel::authenticate() — Bind de parámetros incorrecto — **CORREGIDO**

**Archivo**: `modules/Credenciales/models/UsuarioModel.php`  
**Problema original**: INSERT de auditoría pasaba 5 valores en un único array `[[$idUsuario,'CORE','LOGIN',$ip,'EXITO']]`.  
**Estado**: CORREGIDO — ahora pasa 5 arrays separados `[$val, SQLSRV_PARAM_IN]`.

### BUG #5 — ReporteController::index() — Campo incorrecto

**Archivo**: `modules/Bitacoras/controllers/ReporteController.php`  
**Problema**: Consulta `BIT_Eventos.estado` (TINYINT 0/1, flag de validez) en lugar de `estado_evento` (NVARCHAR, workflow). El GROUP BY agrupa por 0/1 en lugar de 'Pendiente'/'En Proceso'/'Cerrado'.  
**Fix**: Cambiar todas las referencias a `estado` por `estado_evento` en la consulta de reportes.

### BUG #6 — AccesoModel — Mismo problema que BUG #1

**Archivo**: `models/Acceso_/AccesoModel.php`  
**Problema**: Todos los métodos llaman `$stmt->fetchAll()` y `$stmt->closeCursor()` directamente sobre el resource sqlsrv retornado por `$this->query()`.  
**Fix**: Mismo que BUG #1 — usar `$this->fetchAll($stmt)` y `$this->free($stmt)`.

### BUG #7 — db/alter_sp.php — exec() no existe

**Archivo**: `db/alter_sp.php`  
**Problema**: Usa `self::$db->exec($sql)` — `Database` no tiene método `exec()` ni propiedad estática `$db` pública.  
**Fix**: Usar `$conn = self::getConnection(); sqlsrv_query($conn, $sql);`

### BUG #8 — Hack de seguridad en authenticate() — **CORREGIDO**

**Archivo**: `models/Acceso_/Usuario.php`  
**Problema original**: Bypass `str_replace('$2b$12$HASH_', '', $hash)` permitía autenticación sin bcrypt.  
**Estado**: CORREGIDO — archivo completamente reescrito. Bypass eliminado. Ahora usa `sp_Login` (9 params) + `password_verify`. Además este archivo es dead code (autoloader prioriza `modules/Credenciales/models/UsuarioModel.php`).

### RIESGO DE SEGURIDAD #1 — Secret Key Hardcodeado — **CORREGIDO**

**Archivo**: `core/ModuleSecurity.php`  
**Problema original**: `$secret = 'SysPort_APM_Manta_Secret_SSO_Key_2026!'` en código fuente.  
**Estado**: CORREGIDO — clave se carga lazy desde `CORE_Config WHERE modulo='CORE' AND clave='SSO_SECRET' AND estado=1`. Solo usa fallback hardcodeado si la BD falla.

### RIESGO DE SEGURIDAD #2 — CSP con unsafe-inline

**Archivo**: `helpers/security_helper.php`  
**Problema**: `Content-Security-Policy` incluye `'unsafe-inline'` para scripts y estilos, anulando la protección contra XSS.  
**Fix**: Implementar nonces CSP o mover scripts inline a archivos externos.

### INCONSISTENCIA #1 — Dos SPA orchestrators

`js/main.js` y `public/js/app.js` tienen funcionalidades similares pero diferentes. `shell.php` solo carga `main.js`. `app.js` no está integrado actualmente. Riesgo de conflicto si se cargan ambos.

### INCONSISTENCIA #2 — Schema dual

La capa legacy (`models/Acceso_/`) usa tablas `dbo.Usuarios`, `dbo.Departamentos_Modulos`, `dbo.Grupos_Roles`, `dbo.Permisos_Grupos_Roles`, etc. que **no existen** en `PORTAL_APM_COMPLETO.sql`. La capa legacy es inoperable con el schema nuevo.

### INCONSISTENCIA #3 — query_log.txt en producción

**CORREGIDO**: `models/TH_/Empleado.php::getList()` escribía en `query_log.txt` con `@file_put_contents`. El log de debug fue retirado del código; el archivo `query_log.txt` en raíz es residuo histórico.

### INCONSISTENCIA #4 — Ruta hardcodeada en run_migration.php — **CORREGIDO (con observación)**

La ruta absoluta del equipo del desarrollador fue reemplazada por ruta relativa. **Nueva observación**: dentro de `MigrationRunner::run()` se usa `$root . '/db/update_menu.sql'`, pero `$root` es variable de ámbito de archivo — **no visible dentro del método** (queda `null`). El script fallará con "Migration file not found" hasta usar `dirname(__DIR__)` dentro del método. Script utilitario, no afecta producción.

### OBSERVACIÓN — Fonts inconsistentes

La página de login usa `Outfit` y `Fira Mono`. El shell usa `Sora` y `JetBrains Mono`. Inconsistencia tipográfica entre login y dashboard.

### OBSERVACIÓN — db/alter_sp.php usa schema legacy

El SP `sp_GetMenuUsuario` redefinido en `alter_sp.php` usa tablas del schema legacy (`Menu_Opciones`, `Departamentos_Modulos`) no del nuevo schema (`CORE_Menu`, `CORE_Departamentos`). Ejecutar este script en el schema nuevo romperá la función de menú.

---

## 20. Correcciones y Adiciones — Revisión Final Completa

Esta sección consolida todos los hallazgos descubiertos al leer **cada archivo** del proyecto en su totalidad.

### 20.1 Archivos que NO Existen (referenciados en docs pero ausentes en disco)

| Archivo | Referenciado en | Estado |
|---|---|---|
| `PORTAL_APM_DESDE_CERO.sql` | Docs y listing anterior | **NO EXISTE** |
| `config/database.php` | SETUP_PROYECTO.ps1 (v1) | **NO EXISTE** |
| `config/database_th.php` | SETUP_PROYECTO.ps1 (v1) | **NO EXISTE** |
| `config/globals.php` | boot v≤3.0 | **ELIMINADO** — constantes migradas a `config/app.php` |
| `config/routes.php` | boot v≤3.0 | **ELIMINADO** — movido a `routes.php` (raíz) |
| `.env` | `core/Env.php`, `INICIAR_AUTOMATICO.ps1` | **ELIMINADO** — reemplazado por `config/app.php` |
| `.env.example` | Documentación v≤3.0 | **ELIMINADO** |

`config/app.php` es el único archivo de configuración activo desde v3.1. `INICIAR_AUTOMATICO.ps1` todavía intenta leer y actualizar `.env` (inexistente) → el auto-update de `APP_URL` del PS1 no funciona en la config nueva.

### 20.2 Correcciones de Schema (DOCUMENTACION_SISTEMA.md § 16)

`DOCUMENTACION_SISTEMA.md` registra correcciones encontradas entre el código original y el esquema real. Resumen:

#### CORE_Usuarios
- `contrasena_hash` (en el código antiguo) → `hash_contrasena` (real) — aunque en `modules/` el ORM usa ambas; `UPDATE_PASSWORDS.sql` usa `hash_contrasena`

#### CORE_Notificaciones
- `id_notificacion` (legacy) → `id_notif` (real PK)

#### BIT_Eventos (la tabla `BIT_Bitacoras` **no existe**)
| Campo antiguo | Campo real |
|---|---|
| `BIT_Bitacoras` | `BIT_Eventos` |
| `id_bitacora` | `id_evento` |
| `descripcion_breve` | `descripcion` (columna única) |
| `id_usuario_creador` | `id_usuario` |
| `resolucion` | `observaciones` |
| `prioridad` STRING ('MEDIA') | `prioridad` TINYINT 1–3 |
| estado `'ABIERTO'` | `'Pendiente'` |
| estado `'CERRADO'` | `'Cerrado'` |

#### BIENES_Activos (la tabla `BIENES_Inventario` **no existe**)
| Campo antiguo | Campo real |
|---|---|
| `BIENES_Inventario` | `BIENES_Activos` |
| `id_bien` | `id_activo` |
| `codigo_bien` | `codigo` |
| `id_departamento_asignado` | `id_departamento` |
| estado `'ACTIVO'` | `'Activo'` |
| estado `'BAJA'` | `'Baja'` |
| estado `'MANTENIMIENTO'` | `'En Reparacion'` |

#### BIENES_Movimientos
| Campo antiguo | Campo real |
|---|---|
| `id_bien` | `id_activo` |
| `id_departamento_origen` | `id_depto_origen` |
| `id_departamento_destino` | `id_depto_destino` |
| `id_usuario_responsable` | `creado_por` |
| `descripcion` | `observaciones` |
| tipo `'TRASLADO'` | `'Transferencia'` |
| tipo `'ENTRADA'` | `'Asignacion'` |

#### ACCESO_Registros
| Campo antiguo | Campo real |
|---|---|
| `tipo_persona` | `tipo_acceso` ('Entrada'/'Salida') |
| `nombre_persona` | `persona_visita` |
| `motivo_visita` | `motivo` |
| `hora_ingreso` | `fecha_hora` |
| `hora_salida` | No existe — salida = UPDATE `estado='Finalizado'` |
| `id_usuario_registro` | `id_operador` |

El campo `estado` en ACCESO_Registros es NVARCHAR (no TINYINT): `'Activo'` (en recinto) → `'Finalizado'` (salida). El modelo activo (`AccesoController.registrarSalida`) usa `UPDATE estado='Finalizado'` WHERE `id_registro=?`.

### 20.3 Nuevos Bugs Encontrados

#### BUG #9 — visitantes/index.php usa columnas con nombres incorrectos — **CORREGIDO**

**Archivo**: `modules/Control_Acceso/views/visitantes/index.php`  
**Problema original**: Usaba `$vis['documento_identidad']` (→ `cedula`) y `$vis['nombre']` (→ `nombres`+`apellidos`).  
**Estado**: CORREGIDO — vista ahora usa `$vis['cedula']` y `htmlspecialchars($vis['nombres'].' '.$vis['apellidos'], ...)`.

#### ~~BUG #10~~ — FALSO POSITIVO (fecha_creacion SÍ existe)
`ACCESO_Visitantes.fecha_creacion DATETIME2 NOT NULL DEFAULT GETDATE()` — campo real. La vista es CORRECTA.

#### ~~BUG #11~~ — FALSO POSITIVO (contratos estados SÍ coinciden)
`TH_Contratos.estado_contrato` CHECK real: `('Vigente','Finalizado','Anulado')`. La vista es CORRECTA. El error estaba en DOCUMENTACION_SISTEMA.md que decía incorrectamente 'Terminado'/'Suspendido'.

#### ~~BUG #12~~ — FALSO POSITIVO (TH_Empleados SÍ tiene campos separados)
`TH_Empleados` tiene `nombres` y `apellidos` SEPARADOS. El form es CORRECTO.

#### ~~BUG #13~~ — FALSO POSITIVO (show.php usa nombres/apellidos correctamente)
La vista usa `$empleado['nombres']` y `$empleado['apellidos']` — estos campos SÍ existen en la tabla.

#### BUG #11 (real) — admin/roles.php usa columnas inexistentes — **CORREGIDO**

**Archivo**: `modules/Central/views/admin/roles.php`  
**Problema original**: `$r['codigo_rol']` (real: `codigo`) y `$r['activo']` (real: `estado`).  
**Estado**: CORREGIDO — vista ahora usa `$r['codigo']` y `$r['estado']`.

#### BUG #14 — sidebar.php key mismatch vs Menu::getUserMenu() — **CORREGIDO**

**Archivo**: `modules/Central/views/layouts/sidebar.php`  
**Problema**: Sidebar pedía `$mod['name']`, `$mod['code']`, `$area['name']`, `$area['code']` pero `Menu::getUserMenu()` retorna `label` e `id`. Además `ltrim($opt['url'], '/')` y `ltrim($subopt['url'], '/')` sin null-coalescing cuando `url_ruta` puede ser NULL.  
**Errores PHP**: `Warning: Undefined array key "name"` (líneas 145, 151, 177, 180, 183, 185, 242) + `Deprecated: ltrim(): Passing null` (líneas 214, 215, 233, 234).  
**Fix aplicado**:
```php
// name → label ?? ''
htmlspecialchars($mod['label'] ?? '')
htmlspecialchars($area['label'] ?? '')
// code → id
htmlspecialchars((string)($mod['id'] ?? ''))
htmlspecialchars((string)($area['id'] ?? ''))
// ltrim null-safe
ltrim($opt['url'] ?? '', '/')
ltrim($subopt['url'] ?? '', '/')
```
**Estado**: CORREGIDO.

### 20.4 Eventos SPA — FALSO POSITIVO (RESUELTO)

El bug reportado originalmente fue un **falso positivo** basado en la asunción errónea de que `js/main.js` existía.

**Estado real verificado**:
- `js/main.js` — **NO EXISTE** en disco
- `public/js/app.js` — es el único SPA orchestrator; dispara `spa:loaded`
- `public/js/charts.js` — escucha `spa:loaded`

**Conclusión**: Los nombres de evento son **consistentes** (`spa:loaded` en ambos sentidos). No hay ningún fix necesario.

### 20.5 Inconsistencia: DB Query en Vista

**Archivo**: `modules/Central/views/notificaciones/index.php`  
**Problema**: La vista consulta directamente `Database::getInstance()` con `SELECT TOP 50` de `CORE_Notificaciones`. Viola el patrón MVC.  
**Fix**: Mover la consulta al `NotificacionesController` y pasar `$notificaciones` a la vista.

### 20.6 Información Completa de Archivos Auxiliares

#### `extracted_scripts.txt`
Contiene el JS del modelo de datos (estado de la aplicación legacy) con:
- Módulos implementados: PORTAL, TH, GERENCIA, ADMIN, FINANCIERO, JURIDICA, INFRA, SYSTEM_BD
- Descripciones de módulos y áreas (incluyendo los no implementados: JURIDICA, FINANCIERO, INFRA, GERENCIA)
- **Módulos NO implementados en capa activa**: JURIDICA, FINANCIERO, INFRA, GERENCIA (solo en legacy landing page `home/index.php`)

#### `extracted_styles.txt`
Contiene tokens CSS de los 3 temas en formato condensado. Variables adicionales para login específico por módulo:
```css
--bg-login-juridica: #F6F3ED         /* t1 */
--jur-accent: #B45309
--inf-accent: #0284c7
--ger-accent: #4F46E5
```

#### `extracted_theme_html.txt`
HTML del theme switcher flotante y del modal de bienvenida (welcome carousel con imágenes del puerto de Manta). El modal tiene un carrusel con 2+ slides sobre el Puerto de Manta.

#### `query_log.txt`
Tiene 2 entradas de 2026-06-01 de `getList()` del modelo TH legacy:
```
2026-06-01 04:33:42 - getList called with filters: {"dept":"INFRA","search":"","active":"1"}, page: 1, limit: 10
Result total: 5, data count: 5
```
Confirma que el sistema fue ejecutado al menos una vez con datos reales.

#### `db/seed_large.sql`
- Genera **140 empleados adicionales** (targeting total de 151 con los 11 semilla)
- Usa T-SQL loop con `WHILE @i <= 140`
- Aleatoriza nombres/apellidos de tablas de variables (@Nombres 30 nombres, @Apellidos 30 apellidos)
- 10 cargos diferentes (LOSEP) con sueldos $733–$2100
- **IMPORTANTE**: También inserta en `dbo.Usuarios_Grupos_Roles` y `dbo.Usuarios` — **schema legacy**, no `CORE_Usuarios`. Confirma que seed_large.sql fue escrito para schema legacy.
- Limpia datos previos antes de insertar: `DELETE FROM dbo.TH_NovedadesMedicas WHERE id_empleado > 11`

#### `.claude/settings.local.json`
Archivo de permisos de Claude Code para este proyecto. Permite:
- Lectura de skills del plugin
- Comandos Bash/PowerShell específicos usados durante el desarrollo
- `Get-ChildItem` en la raíz del proyecto

#### `logs/test_conn.php`
Diagnóstico de conexión. Verifica:
1. PHP version
2. Extensiones: sqlsrv, mbstring, openssl
3. Conecta a `.\VICTUS` (hardcoded, no lee `.env`)
4. Consulta `CORE_Usuarios WHERE nombre_usuario = 'admin'`
5. Muestra: nombre_usuario, nivel_jerarquia, nombre_completo

### 20.7 Diseño de Vistas — Inventario Completo

#### Módulo Activos — Vistas Módulos
| Vista | Descripción |
|---|---|
| `modules/Central/views/layouts/shell.php` | Layout principal con CDNs, app-shell, spa-loader, toast-container |
| `modules/Central/views/layouts/sidebar.php` | Acordeón 4 niveles, normalizeFaIcon(), user card en footer. Sección **ADMINISTRACIÓN** (nivel≥3): links a Usuarios, Roles, Estructura Menú, Esquema BD; niveles <3 solo ven Esquema BD |
| `modules/Central/views/layouts/topbar.php` | Logo APM + título "AUTORIDAD PORTUARIA DE MANTA", temas, avatar (2 chars sesión), logout |
| `modules/Central/views/dashboard/ejecutivo.php` | 5 KPIs: Empleados, Bienes, Eventos mes, Ingresos hoy, Alertas. Saludos en español por hora. exec-badge |
| `modules/Central/views/dashboard/operativo.php` | KPIs simples, switch exec solo si nivel≥2 |
| `modules/Central/views/errors/404.php` | Standalone (sin shell), link a /dashboard |
| `modules/Central/views/admin/usuarios.php` | Stats bar, avatares con iniciales coloreados por nivel, badge "Tú", búsqueda client-side, niveles 0-4 |
| `modules/Central/views/admin/usuario_form.php` | Grid 2 cols, toggle de contraseña, selector visual de nivel (5 radios con icono/color), chips de roles asignables, nombre_usuario readonly en edición |
| `modules/Central/views/admin/roles.php` | Stats bar, badges de código con fondo, fila preview de descripción, búsqueda — campos `codigo` y `estado` (reales) |
| `modules/Central/views/admin/rol_form.php` | Selector visual de nivel con descripciones, código uppercase forzado, link al gestor de permisos |
| `modules/Central/views/admin/rol_permisos.php` | Árbol MOIS completo con `<select>` CRUD coloreados (tokens `--crud1..--crud3-bd` con overrides por tema), toolbar sticky de acceso rápido, cascada módulo/área, barras de progreso global y por módulo |
| `modules/Central/views/admin/auditoria.php` | Paginada, alias nombre_completo desde vw_AuditoriaGlobal |
| `modules/Central/views/admin/menu/index.php` | **NUEVO** — Árbol Estructura del Menú: glass adaptativo 3 temas (corporate plano blur 0), rieles conectores, toggle AJAX optimista, búsqueda + filtros por nivel, panel ayuda "¿Cómo funciona?", `+` contextual por nivel, eliminar solo nivel≥4 |
| `modules/Central/views/admin/menu/form.php` | **NUEVO** — Asistente 3 pasos: tipo (4 tarjetas explicativas) → ubicación (chips de padres existentes, nº libre sugerido, aviso duplicado que bloquea Guardar) → apariencia (preview ícono FA). Hidden `value=0` antes de cada checkbox |
| `modules/Central/views/notificaciones/index.php` | **CORREGIDO** (INC #2): ya no consulta DB en la vista — datos llegan del controller |
| `modules/Credenciales/views/login/index.php` | Split scene: izquierda animada (estrellas+agua+barco SVG) + derecha form. Font: Outfit |
| `modules/Credenciales/views/perfil/index.php` | Avatar iniciales 2 chars, correo editable, nombre readonly |
| `modules/Credenciales/views/perfil/cambiar_contrasena.php` | 3 campos, JS inline verifica coincidencia en tiempo real |
| `modules/Talento_Humano/views/empleados/form.php` | Campos: cedula, nombres, apellidos (separados!), telefono, correo_institucional, correo_personal |
| `modules/Talento_Humano/views/empleados/show.php` | Usa nombres/apellidos separados para iniciales — **BUG #13** |
| `modules/Talento_Humano/views/contratos/index.php` | Alert 90-días vencimiento, estados Vigente/Finalizado/Anulado — **BUG #11** |
| `modules/Talento_Humano/views/contratos/form.php` | Select tipos: Nombramiento/Contrato/Ocasional |
| `modules/Talento_Humano/views/contratos/show.php` | Card con accent por estado, datos en data-grid |
| `modules/Bitacoras/views/eventos/form.php` | Categoría (de $categorias), prioridad (1-3), fecha_evento datetime-local |
| `modules/Bitacoras/views/eventos/show.php` | Estados: Pendiente/En Proceso/Cerrado. Botón "Cerrar Bitácora" |
| `modules/Bitacoras/views/reportes/index.php` | Chart donut por estado + tabla por categoría. Usa `createChart()` |
| `modules/Control_Bienes/views/bienes/form.php` | codigo readonly en edición, categoría, depto, valor_adquisicion |
| `modules/Control_Bienes/views/bienes/show.php` | Botón Dar Baja solo si estado !== 'Baja'. Link → movimiento |
| `modules/Control_Bienes/views/movimientos/index.php` | Tipos: Asignacion(verde)/Transferencia(azul)/Baja(rojo)/Reparacion(amarillo) |
| `modules/Control_Bienes/views/movimientos/form.php` | JS show/hide campos origen/destino según tipo seleccionado |
| `modules/Control_Acceso/views/acceso/ingresar.php` | 2 campos: persona_visita, motivo |
| `modules/Control_Acceso/views/acceso/reporte.php` | Paginado. estado=Activo → "En Recinto" (amarillo) |
| `modules/Control_Acceso/views/visitantes/index.php` | **CORREGIDO**: usa `cedula` y `nombres.' '.$apellidos` |
| `modules/Control_Acceso/views/visitantes/form.php` | Campos: cedula, nombres, apellidos, empresa, correo, telefono |
| `modules/Control_Acceso/views/visitantes/show.php` | **CORREGIDO** (BUG #10): usa `fecha_registro` (columna real). Link "Registrar Ingreso" con doc pre-llenado |

#### Legacy Views
| Vista | Descripción |
|---|---|
| `views/layouts/header.php` | HTML head con Google Fonts Sora+JetBrains Mono, FA 6.5, Lucide. Abre app-shell div |
| `views/layouts/footer.php` | Cierra HTML, carga main.js, define `toggleSidebarModule()` inline, llama `lucide.createIcons()` |
| `views/layouts/topbar.php` | Logo APM, título organización, temas (C.selectThemeDirect), avatar 2-char sesión, logout |
| `views/layouts/sidebar.php` | Acordeón 4 niveles, `normalizeFaIcon()` mapea iconos Themify→FA6, `$baseUrl` calculado |
| `views/General/auth/login.php` | Config array por módulo: 11 módulos (TH/Bienes/Acceso/Bitacoras/Financiero/Juridica/Infra/Gerencia/Admin/DatabaseAdmin/General) con colores, títulos, demo_users |
| `views/General/dashboard/index.php` | Legacy premium: welcome banner (ship icon), stats mock (151 empleados), exec/ops selector |
| `views/General/dashboard/erd.php` | ERD explorer: stats de sys.tables/views/procedures, diagrama Mermaid dinámico, diccionario de tablas |
| `views/General/home/index.php` | Landing pública. Título: "SysPort — Portal Corporativo Único (APM)". Fonts: Fira Sans + Fira Code |
| `views/General/home/dynamic_form.php` | Cockpit de formulario dinámico con mock records. Usa `normalizeFaIcon()`. Gradiente por `$color` |
| `views/TH_Talento_Humano/index.php` | Lista TH legacy con Lucide, `data-spa-form` en filtro |
| `views/TH_Talento_Humano/ficha.php` | Tabs: ficha/contratos/adendas/médicos. Font Outfit en botones. `scrollbar-width: none` en tabs |
| `views/Acceso_Control_acceso/index.php` | Admin 4-tabs: Usuarios, Roles, Permisos Menú, Permisos Formulario. Max-width 1500px |
| `views/Bienes_Control_de_bienes/index.php` | Mock KPIs: total=1240, welcome-banner azul oscuro |
| `views/Bit_Bitacoras/index.php` | Mock KPIs: total=450 bitácoras, 4 patrullas, 2 incidencias |

### 20.8 CSS `style.css` — Secciones Adicionales (líneas 750–2900)

El archivo tiene ~2900 líneas. Secciones principales:

| Rango | Sección |
|---|---|
| 750–1050 | Page containers, welcome-banner, kpi-grid/card, section-container, modules-grid |
| 1050–1150 | Button variants: btn-primary/secondary/outline/ghost/sm/lg/danger/success/module |
| 1150–1550 | Alert boxes, form elements (form-control, input-wrapper), login layouts, personal-cards, badges, data-table |
| 1550–1730 | Universal table, page-header, empty-state, utility classes (flex/gap/mt/mb/text-*), alert-* modular, pagination |
| 1730–1950 | Folder cards (.folder-card), theme switcher floating pill (.theme-switcher, .ts-btn), per-tema overrides |
| 1950–2220 | Topbar theme dropdown (.tb-theme-dropdown), T3 dropdowns opacos, t1/t3 contrast overrides |
| 2220–2400 | Sidebar accordion nested (.sb-area-btn, .sb-items, .sb-item-btn, .sib-mfa) |
| 2400–2600 | Dashboard styles: charts-grid, exec-header, alerts-panel, activity-feed, tasks-panel, refresh-indicator |
| 2600–2900 | SYSPORT Premium layer v3.0: entrance animations (slideUpFade, anim-up), upgraded KPI cards con shimmer, chart-card, exec-badge, ops-welcome, refresh pill, T1 overrides para sidebar blanco |

**Clases específicas del Premium Layer**:
```css
.anim-up { animation: slideUpFade 0.42s cubic-bezier(0.22, 0.8, 0.4, 1) both; }
.anim-d1/.anim-d2/.anim-d3/.anim-d4 { delays 0/80/160/240/340ms }
.kpi-card::after { top-gradient shimmer decorativo }
.exec-badge { pill azul para label "VISTA EJECUTIVA" }
```

**Nota T3**: En t3, `--surface-app: rgba(255,255,255,0.06)` es insuficiente para dropdowns → overrides específicos: `body.t3 .user-dropdown, body.t3 .notif-dropdown { background: rgba(7,28,55,0.95) }`.

**Nota T1**: La sidebar tiene fondo blanco (`--bg-sidebar: #ffffff`) pero textos del sidebar-brand usan `#fff` hardcoded → overrides CSS al final del archivo:
```css
body.t1 .sm-header-block .sm-title-text h2 { color: var(--text-app) !important; }
```

### 20.9 SETUP_PROYECTO.ps1 — Asistente de Instalación Interactivo (REESCRITO)

Reescrito como asistente por pasos con menús numerados, portable a cualquier computador Windows. Pasos:

1. **Detección de entorno** — escanea todos los discos buscando XAMPP, WampServer (wamp64/wamp, última versión PHP), Laragon, PHP standalone y PHP en PATH; menú de selección + opción de ruta manual a `php.exe`
2. **Verificación PHP** — versión 8.x, extensión `sqlsrv` (con instrucciones detalladas de instalación si falta, incluyendo ruta del `php.ini` activo), ODBC Driver
3. **Modo de servidor** — si hay Apache (XAMPP/Wamp/Laragon) pregunta: Apache (`http://localhost/<carpeta>`) o PHP integrado (`http://localhost:<puerto libre>`). Para Apache: lee el puerto real de `httpd.conf` (`Listen N`); si el proyecto no está en htdocs/www ofrece crear **junction** (sin duplicar archivos, sin admin), **copiar** el proyecto, o manual
4. **SQL Server** — detecta instancias locales vía registro (`Instance Names\SQL`) + estado del servicio (`MSSQL$NAME`); ofrece iniciar el servicio si está detenido; menú instancia/manual; autenticación Windows o SQL (contraseña como SecureString)
5. **Escritura de configuración** — **edita `config/app.php` automáticamente** (regex sobre `define()` de APP_URL, DB_SERVER, DB_NAME, DB_USER, DB_PASS; escapa `\` y `'` para PHP; escribe UTF-8 **sin BOM** para no romper headers) + regenera `.env`; si se copió a htdocs, configura ambas carpetas
6. **Base de datos** — con `sqlcmd`: advierte que el script CREA la BD, reemplaza el nombre si es personalizado, intenta con `-C` (ODBC 18) y reintenta sin él (sqlcmd antiguo), `-E` o `-U/-P`. Sin sqlcmd: instrucciones SSMS. Después **prueba la conexión real desde PHP** (`sqlsrv_connect` vía archivo temporal) y muestra el error exacto si falla
7. **Archivos críticos** — lista actualizada (routes.php raíz, config/app.php, MenuController, app.js — ya no busca config/globals.php ni js/main.js inexistentes)
8. **Inicio** — Apache: verifica si el puerto escucha, ofrece abrir el panel de control (xampp-control/wampmanager); PHP: lanza `php -S` en puerto libre. Abre el navegador en `<APP_URL>/login`
9. **Resumen** — entorno, URL, BD, autenticación, credenciales

`SETUP_PROYECTO.bat`: lanzador con verificación de PowerShell, chcp 65001, `-NoProfile`.

### 20.10 Tipografías por Sección

| Sección | Font Body | Font Code/Mono |
|---|---|---|
| Shell (dashboard, módulos) | Sora | JetBrains Mono |
| Login (`modules/Credenciales`) | Outfit | Fira Mono |
| Legacy landing (`views/General/home`) | Fira Sans | Fira Code |
| Legacy TH ficha | Outfit | — |
| Legacy auth login | — | — (inline styles) |

3 tipografías distintas en uso → inconsistencia de diseño entre secciones del mismo sistema.

### 20.11 Resumen Final de Bugs Catalogados

| # | Archivo | Tipo | Gravedad | Estado |
|---|---|---|---|---|
| BUG #1 | `core/ModuleSecurity.php` | OOP: `$stmt->fetchAll()` + `closeCursor()` en resource sqlsrv | Alto | **CORREGIDO** — reescrito con `$this->free()` y `fn_TienePermisoNodo` |
| BUG #2 | `core/ThDatabase.php` | `Database::getConnection()` no existe | Alto (crash) | **CORREGIDO** — usa `Database::getInstance()->getConn()` |
| BUG #3 | `modules/Credenciales/AuthController.php` | `@nuevo_salt` recibía IP en lugar de string vacío | Medio | **CORREGIDO** — pasa `''` como salt |
| BUG #4 | `modules/Credenciales/UsuarioModel.php` | Bind auditoría: 5 vals en 1 array en lugar de 5 arrays | Alto | **CORREGIDO** — 5 arrays `[$val, SQLSRV_PARAM_IN]` separados |
| BUG #5 | `modules/Bitacoras/ReporteController.php` | Consultaba `estado` TINYINT en lugar de `estado_evento` | Medio | **CORREGIDO** — agrupa por `estado_evento` |
| BUG #6 | `models/Acceso_/AccesoModel.php` | `$stmt->fetchAll()` en resource sqlsrv (dead code) | Alto | Dead code — autoloader nunca carga este modelo |
| BUG #7 | `db/alter_sp.php` | `self::$db->exec()` no existe en Database singleton | Alto | Pendiente (script utilitario, no producción) |
| BUG #8 | `models/Acceso_/Usuario.php` | Bypass auth mock `str_replace('$2b$12$HASH_'...)` | Crítico | **CORREGIDO** — eliminado en reescritura completa |
| BUG #9 | `modules/Control_Acceso/visitantes/index.php` | Columnas `documento_identidad`/`nombre` inexistentes | Medio | **CORREGIDO** — usa `cedula` y `nombres.' '.$apellidos` |
| BUG #10 | `modules/Control_Acceso/visitantes/show.php` | `fecha_creacion` → columna real es `fecha_registro` | Bajo | **CORREGIDO** — usa `fecha_registro` |
| BUG #11 | `modules/Central/views/admin/roles.php` | `$r['codigo_rol']` inexistente (real: `codigo`), `activo` → `estado` | Medio | **CORREGIDO** — `codigo` y `estado` |
| BUG #12 | `modules/Talento_Humano/empleados/form.php` | Schema: `nombres`/`apellidos` son los campos reales | Bajo | FALSO POSITIVO — schema real tiene `nombres`/`apellidos` |
| BUG #13 | `modules/Talento_Humano/empleados/show.php` | Usa `nombres`/`apellidos` — son los campos correctos | Bajo | FALSO POSITIVO — corregido en schema |
| SEC #1 | `core/ModuleSecurity.php` | SSO secret hardcodeado en código fuente | Crítico | **CORREGIDO** — carga desde `CORE_Config WHERE modulo='CORE' AND clave='SSO_SECRET'` |
| SEC #2 | `helpers/security_helper.php` | CSP `unsafe-inline` anula protección XSS | Alto | Pendiente |
| INC #1 | ~~`js/main.js`~~ vs `charts.js` | Evento SPA inconsistente | Medio | FALSO POSITIVO — `main.js` no existe; `app.js` y `charts.js` ambos usan `spa:loaded` |
| INC #2 | `modules/Central/notificaciones/index.php` | Query DB directo en vista (violaba MVC) | Bajo | **CORREGIDO** — datos desde NotificacionesController |
| INC #3 | `models/TH_/Empleado.php` | `query_log.txt` activo en producción | Bajo | **CORREGIDO** — log de debug retirado |
| INC #4 | `db/run_migration.php` | Ruta hardcodeada al equipo del desarrollador | Bajo | **CORREGIDO** — pero `$root` fuera de scope en `run()` (nuevo bug menor, script utilitario) |
| BUG #14 | `modules/Central/layouts/sidebar.php` | Key mismatch `name`/`code` vs `label`/`id` de Menu::getUserMenu() + ltrim(null) | Medio | **CORREGIDO** — `label ?? ''`, `(string)($id ?? '')`, `ltrim($url ?? '', '/')` |
| BUG #15 | `modules/Central/controllers/AdminController.php` | Columna `nombre_depto` inexistente en CORE_Departamentos (real: `nombre`) | Alto (crash SQL) | **CORREGIDO** — 3 ocurrencias `nombre_depto` → `nombre` |
| BUG #16 | `modules/Central/views/admin/menu/index.php` (v1) | Heredoc PHP con `<?= ?>` sin procesar — toggles y botones editar rotos en HTML | Alto | **CORREGIDO** — reescritura con concatenación de strings |
| BUG #17 | `modules/Central/views/admin/menu/form.php` (v1) | Checkboxes estado/target_spa sin hidden fallback — desmarcar nunca llegaba al servidor (`?? 1` reactivaba) | Medio | **CORREGIDO** — `<input type="hidden" value="0">` antes de cada checkbox |
| BUG #18 | `modules/Central/views/admin/rol_permisos.php` | Color `#856404` hardcoded ilegible en tema dark | Bajo | **CORREGIDO** — tokens `--crud1..--crud3-bd` con overrides `[data-theme]` |

---

*Fin del documento — revisado a partir de lectura completa y exhaustiva de TODOS los archivos en `C:\Users\Usuario\Desktop\PRACTICAS\portal_apm`.*  
*Revisión final completada: 2026-06-12 (v3.3.0 — estado de bugs verificado contra el código fuente actual)*
