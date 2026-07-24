# Portal APM — Documentación del Sistema v3.1

**Portal de Gestión Integral — Autoridad Portuaria de Manta**
PHP 8.0+ · SQL Server 2014+ · sqlsrv nativo · Sin PDO · Sin Composer

---

## Tabla de Contenidos

1. [Resumen del Sistema](#1-resumen-del-sistema)
2. [Stack Tecnológico](#2-stack-tecnológico)
3. [Arquitectura](#3-arquitectura)
4. [Estructura de Directorios](#4-estructura-de-directorios)
5. [Bootstrap y Front Controller](#5-bootstrap-y-front-controller)
6. [Framework Core](#6-framework-core)
7. [Módulos](#7-módulos)
8. [Helpers](#8-helpers)
9. [Esquema de Base de Datos](#9-esquema-de-base-de-datos)
10. [Sistema de Menú MOIS](#10-sistema-de-menú-mois)
11. [Seguridad](#11-seguridad)
12. [Frontend y Temas](#12-frontend-y-temas)
13. [Referencia de Rutas](#13-referencia-de-rutas)
14. [Guía de Configuración](#14-guía-de-configuración)
15. [Credenciales Iniciales](#15-credenciales-iniciales)
16. [Guía de Extensión](#16-guía-de-extensión)
17. [Módulo Inventario — BD Separada](#17-módulo-inventario--bd-separada)
18. [Registro de Cambios y Correcciones](#18-registro-de-cambios-y-correcciones)

---

## 1. Resumen del Sistema

Portal APM v3.1 es una aplicación web PHP nativa que integra las áreas operativas de la Autoridad Portuaria de Manta en un único portal autenticado con menú dinámico basado en roles.

| Módulo | Prefijo BD | Función |
|---|---|---|
| **Talento Humano** | `TH_` | CRUD de empleados, contratos y alertas de vencimiento |
| **Bitácoras** | `BIT_` | Registro, seguimiento y cierre de eventos operativos |
| **Control de Bienes (PORTAL_APM)** | `BIENES_` | Activos institucionales y movimientos |
| **Control de Acceso** | `ACCESO_` | Ingresos/salidas de personal y visitantes |
| **Inventario (Control_bines)** | `inv_*` (BD separada) | Inventario completo con secuenciales, períodos IVA, bodega |

Características clave:
- **MVC modular** — cada área es un módulo autocontenido bajo `modules/`
- **SPA-Híbrido** — PHP renderiza el shell HTML, AJAX carga el contenido sin recargar la página
- **Menú 100% dinámico** — árbol MOIS controlado por `CORE_Menu_Nodos` + `CORE_Permisos_Nodo`, sin secciones hardcodeadas
- **sqlsrv exclusivo** — driver nativo PHP para SQL Server, cero PDO en todo el código
- **3 temas CSS** — `body.t1` (Institucional), `body.t2` (Cyber Dark), `body.t3` (Porto Glass)

---

## 2. Stack Tecnológico

### Backend
| Componente | Versión / Detalle |
|---|---|
| PHP | 8.0+ (match expression, tipos union, spread operator) |
| Driver DB | `sqlsrv_*` nativo (Microsoft Drivers for PHP for SQL Server) |
| SQL Server | 2014+ (Compatibility Level 120, collation `Modern_Spanish_CI_AS`) |
| Sesiones | `$_SESSION` PHP nativo + tabla `CORE_Sesiones` |
| Hashing | `password_hash(PASSWORD_BCRYPT, ['cost' => 12])` |
| CSRF | `bin2hex(random_bytes(32))` |

### Frontend
| Componente | Versión / CDN |
|---|---|
| ApexCharts | 3.45.1 — `cdn.jsdelivr.net` |
| Font Awesome | 6.5.0 — `cdnjs.cloudflare.com` |
| CSS | Propio (variables, Grid, Flexbox, 3 temas) |
| JavaScript | Vanilla ES2020 (fetch API, CustomEvent, MutationObserver) |

Sin Composer, sin npm, sin paso de build.

---

## 3. Arquitectura

### Patrón MVC Modular

```
index.php  →  Router  →  Controller  →  Model  →  Database (sqlsrv)
                   ↓
                 View  →  shell.php  →  [sidebar + topbar + $content]
```

### SPA-Híbrido

Carga inicial: PHP renderiza el shell completo con sidebar, topbar y el dashboard.

Navegación posterior:
1. El usuario hace clic en un link `[data-spa]`
2. `app.js` intercepta el clic, ejecuta `fetch(url, {headers: {'X-Requested-With': 'XMLHttpRequest'}})`
3. El servidor detecta la cabecera y renderiza **solo la vista** (sin shell)
4. `app.js` inyecta el HTML en `#main-spa-container`
5. Se dispara el CustomEvent `spa:loaded`
6. `history.pushState` actualiza la URL

### Inyección de Vista en Shell

```php
// View::render()
ob_start();
require $viewFile;           // captura el HTML de la vista
$content = ob_get_clean();

require ROOT . '/modules/Central/views/layouts/shell.php';
// shell.php hace echo $content en el <main id="main-content">
```

### Autoloader de Módulos

`index.php` registra un `spl_autoload_register` que escanea todos los directorios
`modules/*/controllers/` y `modules/*/models/` buscando `{ClassName}.php`.
No se requiere `require_once` explícito.

---

## 4. Estructura de Directorios

```
portal_apm/
├── index.php                    ← Front Controller
├── .env                         ← Variables de entorno (no versionado)
├── .env.example                 ← Plantilla de configuración
├── .htaccess                    ← Rewrite rules (Apache)
├── PORTAL_APM_COMPLETO.sql      ← Esquema completo + semilla + objetos SQL (BD PORTAL_APM)
├── SETUP_PROYECTO.ps1           ← Script de instalación interactivo
├── SETUP_PROYECTO.bat           ← Lanzador del script PS1
├── theme_preview.html           ← Herramienta de verificación visual de los 3 temas
├── routes.php                   ← Registro central de TODAS las rutas (en la RAÍZ, NO en config/)
│
├── config/
│   └── app.php                  ← ÚNICO archivo de config: constantes de app + BD + sesión
│                                   (APP_URL, DB_SERVER, DB_NAME, SESSION_TIMEOUT, DEBUG_MODE…)
│
├── core/
│   ├── Env.php                  ← Loader de .env
│   ├── Database.php             ← Singleton sqlsrv (BD PORTAL_APM)
│   ├── ThDatabase.php           ← Conexión sqlsrv del sistema externo Talento Humano
│   ├── Model.php                ← Clase base de modelos
│   ├── View.php                 ← Motor de renderizado (shell + SPA)
│   ├── Router.php               ← Router con placeholders {param}
│   ├── Controller.php           ← Clase base de controladores
│   ├── ModuleSecurity.php       ← Verificaciones de permisos por módulo
│   └── SqlSrvStatement.php      ← Utilidades de stmt
│
├── helpers/
│   ├── security_helper.php      ← CSRF, bcrypt, headers HTTP, XSS
│   ├── session_helper.php       ← Flash messages, sessionGet/set
│   ├── url_helper.php           ← url(), redirect(), asset()
│   └── form_helper.php          ← Validación de formularios
│
├── modules/
│   ├── Central/                 ← Dashboard, Admin (usuarios/roles/menú/auditoría), layouts
│   ├── Credenciales/            ← Auth, Perfil
│   ├── Talento_Humano/          ← Empleados, Contratos
│   ├── Bitacoras/               ← Eventos, Reportes
│   ├── Control_Bienes/          ← Activos BIENES_*, Movimientos
│   ├── Control_Acceso/          ← Acceso, Visitantes
│   └── Inventario/              ← Control de Bienes nativo (BD separada: inventario)
│       ├── controllers/
│       │   ├── InventarioController.php
│       │   ├── MaestrosController.php
│       │   ├── MonitoreoController.php
│       │   └── ConfigInventarioController.php
│       ├── models/
│       │   ├── InvDatabase.php      ← Singleton sqlsrv a BD 'inventario'
│       │   ├── InvBaseModel.php     ← Base de modelos Inventario
│       │   ├── InventarioModel.php
│       │   ├── MaestroModel.php
│       │   ├── PeriodoModel.php
│       │   ├── SecuencialModel.php
│       │   ├── ItemSistemaModel.php
│       │   └── MonitoreoModel.php
│       └── views/
│           ├── listar.php
│           ├── catalogo.php
│           ├── items.php
│           ├── maestros.php
│           ├── ingresos.php
│           ├── egresos.php
│           ├── periodos.php
│           └── secuenciales.php
│
├── db/
│   └── inv_menu_integration.sql ← Migración: módulo 12 en CORE_Menu_Nodos + permisos ADMIN
│
├── css/
│   ├── variables.css            ← CSS custom properties + tokens de 3 temas (t1/t2/t3)
│   └── style.css                ← Design system unificado (~3000 líneas)
│
└── public/
    ├── js/
    │   ├── app.js               ← SPA orchestrator, tema, toasts, AJAX forms, sidebar
    │   └── charts.js            ← ApexCharts wrapper, auto-init, tema dinámico
    └── img/
```

Estructura interna de un módulo estándar (ejemplo `Talento_Humano`):
```
modules/Talento_Humano/
├── controllers/
│   ├── EmpleadoController.php
│   └── ContratosController.php
├── models/
│   ├── EmpleadoModel.php
│   └── ContratoModel.php
└── views/
    ├── empleados/
    │   ├── index.php
    │   ├── form.php
    │   └── show.php
    └── contratos/
        ├── index.php
        └── form.php
```

---

## 5. Bootstrap y Front Controller

**`index.php`** — punto de entrada único para todas las peticiones HTTP.

Secuencia de arranque:

```
1. require config/app.php      → define ROOT, APP_URL, DB_SERVER, DB_NAME,
                                  SESSION_TIMEOUT, DEBUG_MODE, DEFAULT_TIMEZONE…
2. date_default_timezone_set  → America/Guayaquil
3. Carga core (Database, Model, View, Router, Controller)
4. spl_autoload_register      → escanea modules/*/controllers, modules/*/models,
                                  controllers/* y models/* (soporta estructura legada)
5. Carga helpers              → security, session, url, form
6. require ROOT/routes.php     → new Router() + registra todas las rutas (en la RAÍZ)
7. $router->resolve(uri, method)
```

El `.htaccess` redirige todo a `index.php` excepto archivos estáticos reales:
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]
```

---

## 6. Framework Core

### 6.1 Env

**`core/Env.php`** — parsea el archivo `.env`.

```php
Env::get('DB_SERVER');              // string
Env::int('SESSION_TIMEOUT', 1800); // int con default
Env::bool('DEBUG_MODE', false);    // bool con default
```

### 6.2 Database

**`core/Database.php`** — Singleton, sqlsrv nativo, sin PDO. Conecta a `PORTAL_APM`.

```php
$db = Database::getInstance();

// Consulta simple
$stmt = $db->query('SELECT * FROM CORE_Usuarios WHERE estado = 1');
$usuarios = $db->fetchAll($stmt);

// Consulta parametrizada — parámetro simple auto-wraped como SQLSRV_PARAM_IN
$stmt = $db->query('SELECT * FROM CORE_Usuarios WHERE id_usuario = ?', [$id]);
$usuario = $db->fetch($stmt);

// Parámetro explícito con tipo
$stmt = $db->query('...', [[$id, SQLSRV_PARAM_IN]]);

// Parámetro OUTPUT de SP
$resultado = null;
$params = [
    [$idUsuario,  SQLSRV_PARAM_IN],
    [&$resultado, SQLSRV_PARAM_INOUT, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR), SQLSRV_SQLTYPE_NVARCHAR('max')],
];
$db->query('EXEC sp_Login ?, ? OUTPUT', $params);
```

Métodos disponibles:
| Método | Descripción |
|---|---|
| `getInstance()` | Retorna la instancia Singleton |
| `query($sql, $params)` | Ejecuta consulta, retorna stmt |
| `fetch($stmt)` | Primera fila como array asociativo |
| `fetchAll($stmt)` | Todas las filas |
| `rowsAffected($stmt)` | Filas afectadas (INSERT/UPDATE/DELETE) |
| `lastInsertId()` | Último SCOPE_IDENTITY() |
| `beginTransaction()` / `commit()` / `rollback()` | Transacciones |
| `free($stmt)` | Libera el statement |
| `reset()` | Destruye la conexión (tests) |

### 6.3 Model

**`core/Model.php`** — clase base abstract. Todos los modelos de módulo la extienden.

Expone los mismos métodos de `Database` como métodos protegidos de instancia.
Método adicional:
```php
protected function outParam(mixed &$var, int $phpType, int $sqlType): array
// Construye el array de parámetro SQLSRV_PARAM_INOUT para SPs con OUTPUT
```

### 6.4 View

**`core/View.php`** — renderizado con soporte SPA.

```php
// Desde un controlador:
$this->render('Talento_Humano/empleados/index', ['empleados' => $lista]);
// Resuelve a: modules/Talento_Humano/views/empleados/index.php

// Petición AJAX (X-Requested-With: XMLHttpRequest):
// → solo renderiza la vista, sin shell
// Petición normal:
// → ob_start() captura vista → $content → shell.php lo inyecta en #main-content
```

### 6.5 Router

**`core/Router.php`** — soporta rutas exactas y rutas con parámetros `{param}`.

```php
$router->get('/inventario/{id}/detalle', 'InventarioController@verDetalle');
$router->post('/admin/menu/{id}/toggle', 'MenuController@toggle');
```

El Router:
- Convierte automáticamente parámetros numéricos a `int` antes de pasarlos al controlador
- Responde JSON con HTTP 404 si la petición es AJAX y no hay match
- Carga `modules/Central/views/errors/404.php` en navegación normal

### 6.6 Controller

**`core/Controller.php`** — clase base abstract. Todos los controladores la extienden.

```php
$this->requireAuth();              // redirige a /login si no hay sesión
$this->requireLevel(3);            // verifica nivel_jerarquia >= 3
$this->render('Modulo/vista', $data);
$this->json(['ok' => true]);       // responde JSON + exit
$this->redirect('/dashboard');     // redirige + exit
$this->input('nombre');            // $_POST['nombre'] ?? $_GET['nombre']
$this->input('id', 'get');         // solo $_GET
$this->input('nombre', 'post');    // solo $_POST
$this->csrfToken();                // genera/retorna token CSRF de sesión
$this->verifyCsrf();               // aborta con 403 si no coincide
$this->currentUser();              // array con datos del usuario logueado
```

Timeout de sesión: configurable en `.env` (`SESSION_TIMEOUT`, default 1800 seg).
Cada petición autenticada renueva `$_SESSION['last_activity']`.

---

## 7. Módulos

### 7.1 Central

Módulo del sistema — gestiona el dashboard, administración y los layouts compartidos.

#### Controladores de Administración

**`MenuController`** (requiere `requireLevel(3)`)

Gestiona el árbol MOIS (`CORE_Menu_Nodos`) y sus permisos:

| Método | Ruta | Descripción |
|---|---|---|
| `index()` | GET `/admin/menu` | Vista árbol MOIS completo con accordion |
| `nuevo()` | GET `/admin/menu/nuevo` | Formulario nuevo nodo |
| `crear()` | POST `/admin/menu` | Inserta nodo, verifica duplicado de 4-tupla |
| `editar(int $id)` | GET `/admin/menu/{id}/editar` | Formulario edición |
| `actualizar(int $id)` | POST `/admin/menu/{id}` | Actualiza nodo |
| `toggle(int $id)` | POST `/admin/menu/{id}/toggle` | Activa/desactiva **con cascade** |
| `eliminar(int $id)` | POST `/admin/menu/{id}/eliminar` | Elimina si no tiene permisos asignados |

**Cascade en `toggle()`:** al desactivar un nodo, se calculan todos los descendientes según nivel MOIS y se actualizan en una sola query. Retorna `{ok, estado, cascaded: [ids]}`. El frontend actualiza visualmente todos los switches afectados.

```
L1 (Menú, opcion=0)   → cascada sobre todos en id_modulo=M
L2 (Opción, items=0)  → cascada sobre todos en id_modulo=M AND opcion=N
L3 (Ítem, subitems=0) → cascada sobre todos en id_modulo=M AND opcion=N AND items=I
L4 (Sub-ítem)         → solo el nodo (sin descendientes)
```

`MODULES` const en `MenuController` declara todos los módulos (id 1–12) con label, icon y color. Módulo 12 = "Control de Bienes (Inventario)" / `fa-boxes-stacked` / `#fd7e14`.

**`AdminController`** (requiere `requireLevel(3)`)
- `usuarios()` — listado paginado de usuarios del sistema
- `nuevoUsuario()` / `crearUsuario()` — formulario + creación con bcrypt
- `editarUsuario()` / `actualizarUsuario()` — edición de datos + cambio de rol
- `eliminarUsuario()` — soft-delete (estado = 0)
- `roles()` — lista roles con nivel y permisos MOIS por rol (`/admin/roles`)
- `auditoria()` — pista de auditoría paginada con filtros (`/admin/auditoria`)

**`DashboardController`**
- `index()` — redirige a `executive()` si nivel ≥ 2, si no a `operational()`
- `executive()` — KPIs ejecutivos via `sp_GetKPIs_Ejecutivo`, últimas alertas
- `operational()` — KPIs operativos via `sp_GetKPIs_Operativo`, actividad reciente

**`NotificacionesController`**
- `index()` — vista de notificaciones con paginación
- `recientes()` — API JSON: últimas 10 notificaciones para el dropdown del topbar
- `marcarLeidas()` — POST: marca todas como leídas

#### Layouts (`modules/Central/views/layouts/`)

**`shell.php`** — HTML completo: carga CSS/JS, incluye sidebar, topbar, inyecta `$content`. Aplica tema desde `localStorage.apm_theme` antes de pintar (sin flash).

**`sidebar.php`** — Menú lateral **100% dinámico**. NO tiene secciones hardcodeadas.

Flujo de renderizado del sidebar:
1. Si `$userMenu` no está disponible, lo carga vía `new Menu()->getUserMenu($userId)`
2. Itera `$userMenu` (árbol construido por `core/Menu.php` a partir de `sp_GetMenuUsuario`)
3. Por cada módulo (L1): renderiza `<button class="sm-header" onclick="toggleSidebarModule(modId)">` con color de `$moduleColors[id_modulo]`
4. Por cada área (L2): si el módulo tiene una sola área, se omite el botón colapsable y los ítems aparecen directo. Si tiene varias áreas, renderiza `<button class="sb-area-btn">`
5. Por cada ítem (L3): `<a href data-spa>` o, si tiene hijos, `<button class="sb-subopt-btn">`
6. Por cada sub-ítem (L4): `<a href data-spa>`

**Auto-expansión de áreas:** `toggleSidebarModule(modId)` — al expandir un módulo, también auto-expande todas las `.sb-area-btn` y `.sb-items` internas, para que los links L3 sean visibles de inmediato con un solo click.

**Colores por módulo:**
```php
$moduleColors = [
    1 => '#6f42c1', 2 => '#0056b3', 3 => '#dc3545', 4 => '#fd7e14',
    5 => '#20c997', 6 => '#17a2b8', 7 => '#343a40', 8 => '#8b5cf6',
    9 => '#0ea5e9', 10 => '#28a745', 11 => '#e83e8c', 12 => '#fd7e14',
];
```

**Flyout popover:** al hacer hover sobre cualquier elemento del sidebar, aparece una tarjeta con el nombre, descripción y color del elemento.

**`topbar.php`** — breadcrumb, selectores de tema (t1/t2/t3), badge de notificaciones, menú de usuario.

#### Vista Admin Menú (`modules/Central/views/admin/menu/index.php`)

Características:
- Árbol `<details>` expandible por módulo, con riel visual y chips de nivel (L1–L4)
- **Sticky header:** la sección desde el encabezado "Administración · Navegación" hasta el panel "¿Cómo funciona?" es `position: sticky` — el árbol queda abajo y scrollea independiente
- **Labels MOIS:** L1=Módulo · L2=Opción · L3=Ítem · L4=Sub-ítem
- **Filtros de nivel** en toolbar para mostrar solo L1/L2/L3/L4
- **Switch con cascade:** click en el switch llama `POST /admin/menu/{id}/toggle` → `{cascaded:[ids]}` → JS actualiza todos los switches/row-desc afectados en el DOM vía `data-node-id` en cada `.sw` label
- Botón `+` en cada nodo para agregar hijo con coordenada MOIS precargada

---

### 7.2 Credenciales

**Controlador:** `AuthController`
- `showLogin()` / `login()` — autenticación via `sp_Login`, inicia sesión PHP
- `logout()` — destruye sesión
- `setTheme()` — POST JSON: guarda tema en `$_SESSION['tema']`
- `perfil()` / `actualizarPerfil()` — vista y edición del perfil
- `showCambiarContrasena()` / `cambiarContrasena()` — verifica con bcrypt, llama `sp_CambiarContrasena`

**Modelo:** `UsuarioModel`
- `findById(int $id)` — busca usuario activo por ID
- `getMenuItems(int $idUsuario)` — llama `sp_GetMenuUsuario`
- `updateCorreo(int $id, string $correo)` — actualiza correo

---

### 7.3 Talento Humano

**Controladores:** `EmpleadoController`, `ContratosController`
- CRUD completo de empleados con paginación OFFSET/FETCH
- Contratos con alertas de próximos a vencer (≤30 días)
- `EmpleadoController::show()` — ficha detallada con tarjeta avatar + grid de datos

**Modelos:** `EmpleadoModel`, `ContratoModel`
- `EmpleadoModel::paginate()` — OFFSET/FETCH con filtros
- `ContratoModel::getProximosVencer(int $days)` — `DATEDIFF(day, GETDATE(), fecha_fin) <= $days`

**Vistas:** `empleados/index.php`, `form.php`, `show.php` · `contratos/index.php`, `form.php`, `show.php`

---

### 7.4 Bitácoras

**Controladores:** `EventoController`, `ReporteController`
- Filtros por estado (`Pendiente`/`En Proceso`/`Cerrado`) y categoría
- `close(int $id)` — POST: valida observaciones, actualiza a `'Cerrado'`
- `ReporteController::index()` — resumen por estado + tabla categoría + gráfico donut

**Modelo:** `BitacoraModel` — tabla `BIT_Eventos`
- `close(int $id, string $observaciones)` — UPDATE `estado='Cerrado'`, `fecha_cierre=GETDATE()`

---

### 7.5 Control de Bienes (PORTAL_APM)

**Controladores:** `BienController`, `MovimientoController`
- CRUD de `BIENES_Activos` con filtros por código/estado/categoría
- `darBaja(int $id)` — UPDATE `estado_bien='Baja'`
- `MovimientoController::store()` — si tipo `Transferencia`, actualiza `id_departamento` en el activo

**Modelos:** `BienModel`, `MovimientoModel`

---

### 7.6 Control de Acceso

**Controladores:** `AccesoController`, `VisitanteController`
- Entrada: INSERT `tipo_acceso='Entrada'`, `estado='Activo'`
- Salida: UPDATE `estado='Finalizado'` vía POST JSON; actualiza badge in-place
- CRUD de `ACCESO_Visitantes` con dedup por cédula

**Modelos:** `AccesoModel`, `VisitanteModel`

---

### 7.7 Inventario — Control de Bienes (módulo nativo)

Módulo integrado en `modules/Inventario/`. Independiente pero parte del runtime de portal_apm. Usa BD separada `inventario` (tablas `inv_*`) en la misma instancia SQL Server (`.\VICTUS`).

#### Arquitectura

```
modules/Inventario/
  models/
    InvDatabase.php    ← Singleton sqlsrv a BD 'inventario' (NO usa Database.php)
    InvBaseModel.php   ← Base abstract con helpers query/fetch/fetchAll usando InvDatabase
    InventarioModel    ← inv_inventario: buscar/filtrar/crear/actualizar/eliminar/exportar
    MaestroModel       ← Tablas maestras: categorías, zonas, estados, marcas, líneas, unidades,
                         tipos_iva, productos, proveedores, grupo_centros_consumo, centros_consumo
    PeriodoModel       ← inv_periodos / inv_valores_iva
    SecuencialModel    ← inv_secuenciales: generarSiguiente() → "INV-00009"
    ItemSistemaModel   ← inv_productos: catálogo de ítems
    MonitoreoModel     ← Movimientos de bodega (ingresos/egresos)
```

#### `InvDatabase` — conexión a BD `inventario`

```php
// Singleton que conecta a la BD 'inventario' (NO PORTAL_APM)
// Usa DB_SERVER/DB_USER de config/app.php (misma instancia, Windows Auth si user vacío)
// Auto-crea la BD si no existe (conecta a master y ejecuta CREATE DATABASE)
$this->conn = @sqlsrv_connect($server, ['Database' => 'inventario', ...]);
if ($this->conn === false) {
    $master = @sqlsrv_connect($server, ['Database' => 'master', ...]);
    sqlsrv_query($master, "IF DB_ID('inventario') IS NULL CREATE DATABASE inventario;");
    // reconecta a inventario
}
```

#### Parámetros en modelos Inventario

Parámetros **posicionales** `?` (no PDO named params `:key`):
```php
$this->query('SELECT * FROM inv_inventario WHERE id_inventario = ?', [[$id, SQLSRV_PARAM_IN]]);
```

#### Controllers

Todos extienden el `Controller` base de portal_apm. Usan los mismos `requireAuth()`, `render()` (con shell de portal_apm), `verifyCsrf()`, `csrfToken()`, `json()`, `redirect()`.

| Controller | Rutas |
|---|---|
| `InventarioController` | `/inventario` (listado), catálogo, ítems, guardar, eliminar, exportar, detalle JSON |
| `MaestrosController` | `/inventario/maestros` — CRUD tablas maestras |
| `MonitoreoController` | `/inventario/ingresos`, `/inventario/egresos` |
| `ConfigInventarioController` | `/inventario/periodos`, `/inventario/secuenciales` |

#### Menú lateral (módulo 12)

Módulo 12 en `CORE_Menu_Nodos`. Estructura:

```
L1 (12,0,0,0) Control de Bienes
  L2 (12,1,0,0) Inventario
    L3 (12,1,1,0) Inventario General → /inventario
    L3 (12,1,2,0) Catálogo → /inventario/catalogo
    L3 (12,1,3,0) Ítems del Sistema → /inventario/items
  L2 (12,2,0,0) Bodega
    L3 (12,2,1,0) Ingresos → /inventario/ingresos
    L3 (12,2,2,0) Egresos → /inventario/egresos
  L2 (12,3,0,0) Configuración
    L3 (12,3,1,0) Maestros → /inventario/maestros
    L3 (12,3,2,0) Períodos e IVA → /inventario/periodos
    L3 (12,3,3,0) Secuenciales → /inventario/secuenciales
```

Migración: `db/inv_menu_integration.sql` — idempotente, crea módulo 12 + permisos al rol ADMIN (id_rol=1, nivel_crud=4).

---

## 8. Helpers

### SecurityHelper

```php
SecurityHelper::csrfToken()              // genera/retorna token en $_SESSION
SecurityHelper::verifyCsrf()             // aborta 403 si POST _csrf_token no coincide
SecurityHelper::csrfField()              // <input type="hidden" name="_csrf_token" ...>
SecurityHelper::hashPassword($pass)      // bcrypt cost 12
SecurityHelper::verifyPassword($p, $h)   // password_verify()
SecurityHelper::generateToken($bytes)    // bin2hex(random_bytes($bytes))
SecurityHelper::setSecurityHeaders()     // X-Frame-Options, CSP, X-Content-Type-Options, etc.
SecurityHelper::e($val)                  // htmlspecialchars ENT_QUOTES|ENT_SUBSTITUTE
SecurityHelper::getClientIp()            // IP real (CF-Connecting-IP, X-Forwarded-For)
```

### SessionHelper

```php
SessionHelper::flash('success', 'Guardado');   // método real: flash() (NO setFlash)
SessionHelper::getFlash('success');             // retorna Y elimina el mensaje (one-shot)
SessionHelper::get('key', $default);            // valor de sesión con default
SessionHelper::set('key', $value);
SessionHelper::has('key');                       // bool
SessionHelper::remove('key');
SessionHelper::isLoggedIn();                      // !empty($_SESSION['user_id'])
SessionHelper::userId();                          // ?int
SessionHelper::nivel();                           // int nivel_jerarquia (0–4)
SessionHelper::login($userRow);                   // regenera id + puebla sesión + CSRF
SessionHelper::logout();                          // limpia sesión, cookie y destruye
```

### FormHelper

```php
// validate() retorna BOOL (true = todo válido). Los errores se guardan internos.
$ok = FormHelper::validate($_POST, [
    'nombre'  => 'required|min:3|max:100',
    'correo'  => 'required|email',
    'monto'   => 'required|numeric',
]);
if (!$ok) {
    $errores = FormHelper::errors();          // ['campo' => 'mensaje', …]
    $msg     = FormHelper::error('nombre');   // ?string de un campo
    $html    = FormHelper::errorHtml('nombre');// <span class="form-error">…</span>
    FormHelper::flashOld($_POST);             // conserva input para repintar el form
}
// Reglas soportadas: required · min:N · max:N · email · numeric
//                    in:a,b,c · confirmed · alpha_num
// old(): FormHelper::old('nombre') recupera el valor tras flashOld().
```

---

## 9. Esquema de Base de Datos

### 9.1 Convenciones

- **Collation:** `Modern_Spanish_CI_AS`
- **Compatibility Level:** 120 (SQL Server 2014)
- **`estado TINYINT`:** `0`=Anulado/eliminado lógico · `1`=Activo/válido — nunca DELETE físico
- **`estado_[contexto] NVARCHAR`:** flujo de trabajo (`'Vigente'`, `'Pendiente'`, etc.) — independiente de `estado`
- **`nivel_jerarquia`:** `0`=Operador · `1`=Analista · `2`=Director · `3`=Gerente · `4`=SuperAdmin
- **Prefijos:** `CORE_` · `TH_` · `BIT_` · `BIENES_` · `ACCESO_`

### 9.2 Tablas CORE_ (13 tablas)

| Tabla | Descripción clave |
|---|---|
| `CORE_Departamentos` | Árbol organizacional auto-referenciado (`id_padre`). `nivel` 0–3. Icono y badge por nodo. |
| `CORE_Usuarios` | Cuentas del portal. `hash_contrasena` BCrypt. `intentos_fallidos` + `fecha_bloqueo` + `minutos_bloqueo` para lockout. `requiere_cambio_pass`. `tema_preferido` (campo heredado; en práctica se usa `localStorage`). |
| `CORE_Roles` | Roles con `nivel_jerarquia` y `id_departamento` opcional. |
| `CORE_Usuarios_Roles` | Tabla puente usuario ↔ rol. Un usuario puede tener múltiples roles. `asignado_por` audita quién asignó. |
| `CORE_Menu_Nodos` | Jerarquía MOIS 4 niveles. `UQ(id_modulo, opcion, items, subitems)`. `estado=0` oculta para todos. Ver §10. |
| `CORE_Permisos_Nodo` | Permiso de rol sobre nodo MOIS. `nivel_crud` 1–4. `acceso=0` oculta solo para ese rol. FK compuesta por 4-tupla (no por `id_nodo`). |
| `CORE_Formularios` | Catálogo de pantallas para permisos granulares sub-nodo. `config_json` para configuración específica. |
| `CORE_Formularios_Permisos` | Permiso de rol sobre formulario con `nivel_crud`. |
| `CORE_Sesiones` | Token de sesión, IP, fecha expiración, fecha revocación. `fn_SesionValida(@token)` lo consulta. |
| `CORE_Auditoria` | Log centralizado de todo el sistema. `fecha_purga` calculada desde `CORE_Config.AUDIT_RETENTION_YEARS`. |
| `CORE_Notificaciones` | Notificaciones in-app. `leida BIT`, `url_accion`, `prioridad` 1–3. PK: `id_notif`. |
| `CORE_Contrasenas_Hist` | Historial BCrypt. `sp_CambiarContrasena` lo llena antes de actualizar. Máx. 5 entradas. |
| `CORE_Config` | Clave-valor por módulo. `tipo` para castear (`string`/`int`/`bool`/`json`). `UQ(modulo, clave)`. |

### 9.3 Tablas TH_ (5 tablas)

| Tabla | Descripción |
|---|---|
| `TH_Empleados` | FK opcional a `CORE_Usuarios` (`id_usuario NULL UNIQUE`) — empleado sin cuenta o con cuenta. |
| `TH_Contratos` | `tipo_contrato` CHECK: `Nombramiento/Contrato/Ocasional`. `estado_contrato`: `Vigente/Finalizado/Anulado`. |
| `TH_Adendas` | Modificaciones a contrato: `campo_modificado`, `valor_anterior`, `valor_nuevo`. CASCADE de contrato. |
| `TH_Novedades_Medicas` | Altas/bajas/licencias. `estado_novedad`: `Activa/Cerrada/Anulada`. |
| `TH_Auditoria` | Log específico del módulo TH. |

### 9.4 Tablas BIT_ (4 tablas)

| Tabla | Descripción |
|---|---|
| `BIT_Categorias` | Tipos de evento con color badge. |
| `BIT_Eventos` | PK: `id_evento`. `prioridad` TINYINT 1–3. `estado_evento`: `Pendiente/En Proceso/Cerrado`. |
| `BIT_Archivos` | Adjuntos a eventos (CASCADE on delete). |
| `BIT_Auditoria` | Log específico del módulo Bitácoras. |

> `BIT_Bitacoras` **no existe**. La tabla real es `BIT_Eventos`.

### 9.5 Tablas BIENES_ (4 tablas)

| Tabla | Descripción |
|---|---|
| `BIENES_Categorias` | Categorías de activos. |
| `BIENES_Activos` | PK: `id_activo`. `codigo` UNIQUE. `estado_bien`: `Activo/Baja/En Reparacion/Transferido`. `id_custodio` FK CORE_Usuarios. |
| `BIENES_Movimientos` | `tipo_movimiento` CHECK: `Asignacion/Transferencia/Baja/Devolucion/Reparacion`. `id_depto_origen`/`destino`. |
| `BIENES_Auditoria` | Log específico del módulo Bienes. |

> `BIENES_Inventario` **no existe**. La tabla real es `BIENES_Activos`. PK: `id_activo`.

### 9.6 Tablas ACCESO_ (4 tablas)

| Tabla | Descripción |
|---|---|
| `ACCESO_Visitantes` | `cedula` UNIQUE. Directorio de visitantes frecuentes. |
| `ACCESO_Vehiculos` | Placa UNIQUE. FK opcional a visitante. |
| `ACCESO_Registros` | PK: `id_registro` (BIGINT). Un registro por evento. `tipo_acceso`: `Entrada/Salida`. `estado_registro`: `Activo/Finalizado`. `id_operador` FK CORE_Usuarios. |
| `ACCESO_Auditoria` | Log específico del módulo Acceso. |

### 9.7 BD `inventario` — tablas `inv_*`

BD separada en la misma instancia. Creada automáticamente por `InvDatabase` si no existe.

Tablas principales: `inv_inventario`, `inv_categorias`, `inv_zonas`, `inv_estados`, `inv_marcas`, `inv_lineas`, `inv_unidades`, `inv_tipos_iva`, `inv_productos`, `inv_proveedores`, `inv_grupo_centros_consumo`, `inv_centros_consumo`, `inv_periodos`, `inv_valores_iva`, `inv_secuenciales`, movimientos de bodega.

### 9.8 Stored Procedures

| SP | Descripción |
|---|---|
| `sp_Login` | Devuelve hash BCrypt a PHP para `password_verify()`. Verifica estado y bloqueo. Limpia bloqueo si expiró. |
| `sp_RegistrarFalloLogin` | Incrementa `intentos_fallidos`. Bloquea al alcanzar `LOGIN_MAX_INTENTOS` de `CORE_Config`. Audita. |
| `sp_Logout` | Revoca token en `CORE_Sesiones`, inserta en `CORE_Auditoria`. |
| `sp_CambiarContrasena` | Mueve hash actual a `CORE_Contrasenas_Hist`, limita a N entradas, actualiza usuario, audita. |
| `sp_GetMenuUsuario` | JOIN 5 tablas (Usuarios→Roles→Permisos→Nodos). Triple filtro: `ur.estado=1`, `pn.acceso=1 AND pn.estado=1`, `mn.estado=1`. `MAX(nivel_crud)` para múltiples roles. Resultado plano; PHP construye árbol. |
| `sp_RegistrarAuditoria` | Centraliza inserciones en `CORE_Auditoria`. Calcula `fecha_purga` desde `CORE_Config`. |
| `sp_PurgarAuditoria` | Borra `CORE_Auditoria` vencida. Limpia `*_Auditoria` de módulos con ventana fija 2 años. |
| `sp_GetKPIs_Ejecutivo` | 10 conteos en una llamada para dashboard gerencial. |
| `sp_GetKPIs_Operativo` | 4 KPIs filtrados por `id_departamento` del usuario. |

### 9.9 Funciones SQL

| Función | Retorna | Descripción |
|---|---|---|
| `fn_TienePermisoNodo` | `BIT` | Verifica acceso a una coordenada MOIS con nivel mínimo y MFA. |
| `fn_TienePermisoFormulario` | `BIT` | Verifica acceso a un formulario (permisos sub-nodo). |
| `fn_SesionValida` | `BIT` | Token válido: `estado=1 AND fecha_expira > GETDATE()`. |
| `fn_GetArbolDepartamento` | `TABLE` | CTE recursivo desde `@id_raiz` con breadcrumb y profundidad. |

### 9.10 Vistas SQL (10 vistas)

| Vista | Descripción |
|---|---|
| `vw_MenuPorUsuario` | Versión sin parámetro de `sp_GetMenuUsuario` (para reportes de permisos). |
| `vw_FichaEmpleado` | Empleado con cargo, departamento y tipo de contrato activo. |
| `vw_AuditoriaGlobal` | Unifica `CORE_Auditoria` + los `*_Auditoria` de módulos en un solo log consultable. |
| `vw_KPIs_TH` | KPIs de Talento Humano (empleados activos, contratos vigentes, novedades). |
| `vw_KPIs_Bienes` | KPIs de Control de Bienes (activos, valor, bajas, movimientos). |
| `vw_KPIs_Acceso` | KPIs de Control de Acceso (ingresos/salidas, visitantes activos). |
| `vw_KPIs_Bitacoras` | KPIs de Bitácoras (eventos por estado/prioridad). |
| `vw_ResumenRoles` | Cada rol con su nivel, nº de usuarios y nº de nodos permitidos. |
| `vw_SSO_Usuarios` | Proyección de usuarios para integración SSO externa. |
| `vw_SSO_Menu` | Proyección del menú MOIS para integración SSO externa. |

---

## 10. Sistema de Menú MOIS

### Jerarquía de 4 niveles

```
(id_modulo, opcion, items, subitems)  →  UNIQUE constraint en CORE_Menu_Nodos

L1 (M, 0, 0, 0)  →  Menú/Módulo    — cabecera de módulo, sin URL
L2 (M, N, 0, 0)  →  Opción         — agrupador de pantallas, sin URL
L3 (M, N, I, 0)  →  Ítem           — pantalla real, con URL
L4 (M, N, I, S)  →  Sub-ítem       — acción dentro de una pantalla, con URL
```

### Por qué la FK no usa `id_nodo`

`CORE_Permisos_Nodo` referencia la 4-tupla MOIS, no `id_nodo`. Esto significa que si se borra y recrea un nodo con las mismas coordenadas, los permisos ya asignados siguen siendo válidos sin reasignación. La clave de negocio es la coordenada.

### Visibilidad: dos capas independientes

| Capa | Dónde | Efecto |
|---|---|---|
| `CORE_Menu_Nodos.estado = 0` | Toggle en `/admin/menu` | Oculta para **todos** los usuarios, independiente de permisos |
| `CORE_Permisos_Nodo.acceso = 0` | Toggle en `/admin/roles` | Oculta solo para ese **rol** |

`sp_GetMenuUsuario` filtra `mn.estado=1 AND pn.acceso=1 AND pn.estado=1`. Las tres deben ser true.

### Toggle con cascade

Al desactivar un nodo:

```
L1 (Menú)     → UPDATE estado=X WHERE id_modulo=M
L2 (Opción)   → UPDATE estado=X WHERE id_modulo=M AND opcion=N
L3 (Ítem)     → UPDATE estado=X WHERE id_modulo=M AND opcion=N AND items=I
L4 (Sub-ítem) → UPDATE estado=X WHERE id_nodo=id (solo el nodo)
```

El SP devuelve `{cascaded:[id1,id2,...]}`. El frontend actualiza visualmente todos los switches y tacha las descripciones.

### Módulos registrados (id 1–12)

| ID | Módulo | Color |
|---|---|---|
| 1 | Dirección de Planificación Estratégica | #6f42c1 |
| 2 | Gestión de Tecnología de la Información | #0056b3 |
| 3 | Dirección de Asesoría Jurídica | #dc3545 |
| 4 | Dirección de Infraestructura Portuaria | #fd7e14 |
| 5 | Garita de Acceso / Control de Acceso | #20c997 |
| 6 | Dirección de Operaciones | #17a2b8 |
| 7 | Gerencia General | #343a40 |
| 8 | Delegación de Servicios Portuarios | #8b5cf6 |
| 9 | Dirección Administrativa | #0ea5e9 |
| 10 | Dirección Financiera | #28a745 |
| 11 | Dirección de Talento Humano | #e83e8c |
| 12 | Control de Bienes (Inventario) | #fd7e14 |

### `core/Menu.php`

Llama `sp_GetMenuUsuario(@id_usuario)` y construye el árbol:
```php
$userMenu[$modId] = [
    'id'    => $modId,
    'label' => $label,
    'icon'  => $icon,
    'areas' => [
        $opId => [
            'id'    => $opId,
            'label' => $label,
            'icon'  => $icon,
            'items' => [
                $itId => [
                    'id'       => $itId,
                    'label'    => $label,
                    'icon'     => $icon,
                    'url'      => $url,
                    'children' => [ ... ] // L4
                ]
            ]
        ]
    ]
];
```

---

## 11. Seguridad

### Autenticación

1. `sp_Login` verifica si el usuario existe, está activo y no bloqueado; retorna hash BCrypt
2. PHP llama `password_verify()` contra el hash
3. Si válido: crea `$_SESSION` con `user_id`, `nombre_completo`, `nivel_jerarquia`, `id_departamento`
4. Regenera session ID con `session_regenerate_id(true)` contra session fixation
5. Tras N fallos, `sp_RegistrarFalloLogin` bloquea la cuenta (configurable en `CORE_Config`)

### CSRF

Cada formulario POST incluye `<input type="hidden" name="_csrf_token" value="...">`.
Token: `bin2hex(random_bytes(32))` almacenado en `$_SESSION['_csrf_token']`.
Verificado con `hash_equals()` (resistente a timing attacks).

### XSS

Todas las salidas a HTML: `htmlspecialchars($val, ENT_QUOTES, 'UTF-8')`.
CSP header restringe scripts a dominios conocidos.

### Niveles de Jerarquía

| Nivel | Label | Acceso |
|---|---|---|
| 0 | Operativo | Solo módulos con permisos asignados |
| 1 | Analista | Módulos asignados + lectura amplia |
| 2 | Jefatura | Dashboard ejecutivo + todos los módulos |
| 3 | Director/Gerente | Todo lo anterior + panel Admin (`requireLevel(3)`) |
| 4 | SuperAdmin | Eliminar nodos de menú (`requireLevel(4)`) |

---

## 12. Frontend y Temas

### 12.1 Sistema de Temas CSS

**IMPORTANTE — selector correcto:**

```
body.t1  →  Tema 1: Institucional (blanco/azul navy)
body.t2  →  Tema 2: Cyber Dark (oscuro con acentos cyan)
body.t3  →  Tema 3: Porto Glass (glassmorphism)
```

**NO usar `[data-theme]`** — ese selector no matchea en portal_apm. El shell aplica `body.t1/t2/t3`, no `data-theme`. Los selectores `[data-theme="dark"]` o `[data-theme="corporate"]` quedan muertos.

**Archivos CSS:**
- `css/variables.css` — tokens CSS de los 3 temas con selectores `body.t1`, `body.t2`, `body.t3`
- `css/style.css` — design system completo (~3000 líneas)

**Tokens disponibles:**

| Token | Descripción |
|---|---|
| `--surface-app` | Fondo de tarjetas/superficies |
| `--bg-app` | Fondo de la aplicación |
| `--accent-app` | Superficie secundaria/acentos |
| `--text-app` | Texto principal |
| `--text-muted` | Texto secundario/tenue |
| `--border-app` | Bordes |
| `--primary-app` / `--primary-hover` | Color primario |
| `--shadow-app` | Sombras |
| `--bg-sidebar` | Fondo del sidebar |

**T3 Porto Glass — variante glass:**
```css
body.t3 .mi-clase { backdrop-filter: blur(16px); }
/* NO usar var(--backdrop) directamente en backdrop-filter */
```

**Persistencia:** usuario selecciona en topbar → `localStorage['apm_theme']` ('1'/'2'/'3') → `body.tN`. Shell lee `localStorage` al cargar para evitar flash.

**Herramienta de verificación:** `theme_preview.html` en raíz del proyecto. Abre en navegador, cambia entre t1/t2/t3 en vivo para verificar componentes.

### 12.2 app.js — SPA Orchestrator

Ubicación: `public/js/app.js`.

**Navegación SPA:**
```javascript
// Intercepta clicks en a[data-spa]
// fetch con X-Requested-With: XMLHttpRequest
// Inyecta response en #main-spa-container
// history.pushState actualiza URL
// Dispara CustomEvent 'spa:loaded'
```

**Sidebar JS:**
```javascript
window.toggleSidebarModule(modId)
// Cierra todos los demás módulos (acordeón)
// Abre el módulo clickeado
// Auto-expande TODOS los .sb-area-btn y .sb-items dentro del módulo
// → Links L3 visibles de inmediato sin click adicional

window.toggleSidebarArea(areaId)
// Toggle individual de un área

window.toggleSidebarSubopt(optId)
// Toggle individual de un sub-opción L3 colapsable
```

**Tema:**
```javascript
C.selectThemeDirect(n)  // n=1|2|3 → aplica body.tN + localStorage + POST /set-theme
```

**Toasts:**
```javascript
window.showToast(message, type = 'info', duration = 3500)
```

### 12.3 charts.js

```javascript
window.createChart(target, options)   // con deep merge de defaults de tema
window.destroyAllCharts()             // antes de cada navegación SPA
// Auto-init: [data-chart] con [data-chart-options='{"type":"bar"}']
```

---

## 13. Referencia de Rutas

### Auth
| Método | URL | Acción |
|---|---|---|
| GET | `/login` | showLogin |
| POST | `/login` | login |
| GET | `/logout` | logout |
| POST | `/set-theme` | setTheme |

### Home (landing público)
| Método | URL | Acción |
|---|---|---|
| GET | `/` | HomeController@index (landing / SSO demo) |
| GET | `/api/demo-sso` | HomeController@demoSso (endpoint JSON demo SSO) |

### Dashboard
| Método | URL | Acción |
|---|---|---|
| GET | `/dashboard` | index |
| GET | `/dashboard/ejecutivo` | executive |
| GET | `/dashboard/operativo` | operational |
| GET | `/reportes` | reportes |

### Perfil
| Método | URL | Acción |
|---|---|---|
| GET | `/perfil` | perfil |
| POST | `/perfil` | actualizarPerfil |
| GET | `/cambiar-contrasena` | showCambiarContrasena |
| POST | `/cambiar-contrasena` | cambiarContrasena |

### Notificaciones
| Método | URL | Acción |
|---|---|---|
| GET | `/notificaciones` | index |
| GET | `/notificaciones/recientes` | recientes (JSON) |
| POST | `/notificaciones/marcar-leidas` | marcarLeidas |

### Admin (nivel ≥ 3)
| Método | URL | Acción |
|---|---|---|
| GET | `/admin/usuarios` | usuarios |
| GET | `/admin/usuarios/nuevo` | nuevoUsuario |
| POST | `/admin/usuarios` | crearUsuario |
| GET | `/admin/usuarios/{id}/editar` | editarUsuario |
| POST | `/admin/usuarios/{id}` | actualizarUsuario |
| POST | `/admin/usuarios/{id}/eliminar` | eliminarUsuario |
| GET | `/admin/roles` | roles (listado) |
| GET | `/admin/roles/nuevo` | nuevoRol |
| POST | `/admin/roles` | crearRol |
| GET | `/admin/roles/{id}/editar` | editarRol |
| POST | `/admin/roles/{id}` | actualizarRol |
| POST | `/admin/roles/{id}/eliminar` | eliminarRol |
| GET | `/admin/roles/{id}/permisos` | rolPermisos (matriz MOIS del rol) |
| POST | `/admin/roles/{id}/permisos` | guardarPermisos |
| GET | `/admin/auditoria` | auditoria |
| GET | `/admin/menu` | MenuController@index |
| GET | `/admin/menu/nuevo` | MenuController@nuevo |
| POST | `/admin/menu` | MenuController@crear |
| GET | `/admin/menu/{id}/editar` | MenuController@editar |
| POST | `/admin/menu/{id}` | MenuController@actualizar |
| POST | `/admin/menu/{id}/toggle` | MenuController@toggle (cascade) |
| POST | `/admin/menu/{id}/eliminar` | MenuController@eliminar |

### Talento Humano
| Método | URL | Acción |
|---|---|---|
| GET | `/th/empleados` | index |
| GET | `/th/empleados/nuevo` | create |
| POST | `/th/empleados` | store |
| GET | `/th/empleados/{id}` | show |
| GET | `/th/empleados/{id}/editar` | edit |
| POST | `/th/empleados/{id}` | update |
| POST | `/th/empleados/{id}/eliminar` | destroy |
| GET | `/th/contratos` | index |
| GET | `/th/contratos/nuevo` | create |
| POST | `/th/contratos` | store |
| GET | `/th/contratos/{id}` | show |
| GET | `/th/contratos/{id}/editar` | edit |
| POST | `/th/contratos/{id}` | update |

### Bitácoras
| Método | URL | Acción |
|---|---|---|
| GET | `/bitacoras` | index |
| GET | `/bitacoras/nuevo` | create |
| POST | `/bitacoras` | store |
| GET | `/bitacoras/{id}` | show |
| GET | `/bitacoras/{id}/editar` | edit |
| POST | `/bitacoras/{id}` | update |
| POST | `/bitacoras/{id}/cerrar` | close |
| GET | `/bitacoras/reportes` | ReporteController@index |

### Control de Bienes (PORTAL_APM)
| Método | URL | Acción |
|---|---|---|
| GET | `/bienes` | index |
| GET | `/bienes/nuevo` | create |
| POST | `/bienes` | store |
| GET | `/bienes/{id}` | show |
| GET | `/bienes/{id}/editar` | edit |
| POST | `/bienes/{id}` | update |
| POST | `/bienes/{id}/dar-baja` | darBaja |
| GET | `/bienes/movimientos` | MovimientoController@index |
| GET | `/bienes/movimientos/nuevo` | create |
| POST | `/bienes/movimientos` | store |

### Control de Acceso
| Método | URL | Acción |
|---|---|---|
| GET | `/acceso` | index |
| GET | `/acceso/ingresar` | ingresar |
| POST | `/acceso/ingresar` | registrarIngreso |
| POST | `/acceso/salida` | registrarSalida (JSON) |
| GET | `/acceso/reporte` | reporte |
| GET | `/acceso/visitantes` | VisitanteController@index |
| GET | `/acceso/visitantes/nuevo` | create |
| POST | `/acceso/visitantes` | store |
| GET | `/acceso/visitantes/{id}` | show |

### Inventario (Control de Bienes nativo)
| Método | URL | Acción |
|---|---|---|
| GET | `/inventario` | InventarioController@index |
| GET | `/inventario/catalogo` | catalogo |
| GET | `/inventario/items` | items |
| GET | `/inventario/exportar` | exportar |
| POST | `/inventario/guardar` | guardar |
| GET | `/inventario/maestros` | MaestrosController@index |
| POST | `/inventario/maestros/guardar` | guardar |
| POST | `/inventario/maestros/eliminar` | eliminar |
| GET | `/inventario/ingresos` | MonitoreoController@ingresos |
| GET | `/inventario/egresos` | MonitoreoController@egresos |
| GET | `/inventario/periodos` | ConfigInventarioController@periodos |
| POST | `/inventario/periodos` | crearPeriodo |
| GET | `/inventario/secuenciales` | secuenciales |
| POST | `/inventario/secuenciales/reiniciar` | reiniciarSecuencial |
| GET | `/inventario/{id}/detalle` | InventarioController@verDetalle (JSON) |
| POST | `/inventario/{id}/eliminar` | eliminar |

---

## 14. Guía de Configuración

### Requisitos del servidor

- Windows 10+ / Windows Server 2016+
- PHP 8.0+ con extensiones: `sqlsrv`, `mbstring`, `openssl`, `fileinfo`
- SQL Server 2014+ (instancia `.\VICTUS` en desarrollo)
- Apache 2.4+ con `mod_rewrite` (o `php -S` para desarrollo)
- Microsoft ODBC Driver 17+ para SQL Server

### Instalación automatizada

```powershell
Set-ExecutionPolicy -Scope Process -ExecutionPolicy Bypass
.\SETUP_PROYECTO.ps1
```

El script:
1. Solicita datos de conexión SQL Server (contraseña como `SecureString`)
2. Genera `.env`
3. Genera `UPDATE_PASSWORDS.sql` con hash bcrypt del admin
4. Crea `.htaccess` si no existe
5. Ofrece iniciar servidor PHP en `localhost:8080` (o puerto elegido)

### Variables de entorno

| Variable | Default | Descripción |
|---|---|---|
| `APP_URL` | `http://localhost` | URL base sin trailing slash |
| `APP_NAME` | `Portal APM` | Nombre mostrado en UI |
| `DEBUG_MODE` | `false` | `true` muestra errores PHP |
| `DEFAULT_TIMEZONE` | `America/Guayaquil` | Zona horaria PHP |
| `DB_SERVER` | `(local)` | Servidor SQL Server (ej: `.\VICTUS`) |
| `DB_NAME` | `PORTAL_APM` | BD principal |
| `DB_USER` | _(vacío)_ | Vacío = Windows Auth |
| `DB_PASS` | _(vacío)_ | Contraseña SQL Auth |
| `DB_TRUST_CERT` | `true` | Confiar en certificado SSL |
| `DB_ENCRYPT` | `false` | Encriptar conexión |
| `SESSION_TIMEOUT` | `1800` | Timeout de inactividad en segundos |

---

## 15. Credenciales Iniciales

| Campo | Valor |
|---|---|
| **Usuario** | `admin` |
| **Contraseña** | `Apm2024*` |
| **Nivel** | 4 — SuperAdmin (acceso total + eliminar nodos) |
| **Rol** | ADMIN (id_rol=1) — permisos MOIS completos incluyendo módulo 12 |
| **URL login** | `/login` |

Cambiar contraseña en `/cambiar-contrasena` tras el primer acceso.

---

## 16. Guía de Extensión

### Agregar un nuevo módulo

**Paso 1 — Estructura de directorios:**
```
modules/NuevoModulo/
├── controllers/
│   └── NuevoController.php
├── models/
│   └── NuevoModel.php
└── views/
    └── items/
        ├── index.php
        ├── form.php
        └── show.php
```

**Paso 2 — Controller:**
```php
class NuevoController extends Controller {
    public function index(): void {
        $this->requireAuth();
        $db = Database::getInstance();
        $items = $db->fetchAll($db->query('SELECT * FROM NUEVO_Items WHERE estado=1'));
        $this->render('NuevoModulo/items/index', compact('items'));
    }

    public function guardar(): void {
        $this->requireAuth();
        $this->verifyCsrf();
        // ... lógica ...
        $this->json(['ok' => true]);
    }
}
```

**Paso 3 — Rutas en `routes.php` (archivo en la RAÍZ del proyecto):**
```php
$router->get('/nuevo',           'NuevoController@index');
$router->get('/nuevo/nuevo',     'NuevoController@create');
$router->post('/nuevo/guardar',  'NuevoController@guardar');
$router->get('/nuevo/{id}',      'NuevoController@show');
```

**Paso 4 — Declarar el módulo en `MenuController::MODULES`:**
```php
13 => ['label' => 'Gestión de Contratos', 'icon' => 'fa-file-contract', 'color' => '#6366f1'],
```

**Paso 5 — Insertar nodos MOIS:**
```sql
-- L1: cabecera del módulo
INSERT INTO CORE_Menu_Nodos (id_modulo,opcion,items,subitems,descripcion,url_ruta,icono,orden,requiere_mfa,target_spa,estado)
VALUES (13,0,0,0,N'Gestión de Contratos',NULL,'fa-file-contract',13,0,1,1);

-- L2: agrupador (sin URL)
INSERT INTO CORE_Menu_Nodos (id_modulo,opcion,items,subitems,descripcion,url_ruta,icono,orden,requiere_mfa,target_spa,estado)
VALUES (13,1,0,0,N'Contratos',NULL,'fa-folder',1,0,1,1);

-- L3: pantallas (con URL)
INSERT INTO CORE_Menu_Nodos (id_modulo,opcion,items,subitems,descripcion,url_ruta,icono,orden,requiere_mfa,target_spa,estado)
VALUES (13,1,1,0,N'Listado','/nuevo','fa-list',1,0,1,1),
       (13,1,2,0,N'Nuevo','/nuevo/nuevo','fa-plus',2,0,1,1);
```

**Paso 6 — Dar permisos al rol:**
```sql
INSERT INTO CORE_Permisos_Nodo (id_rol,id_modulo,opcion,items,subitems,nivel_crud,acceso,estado,fecha_asignacion,asignado_por)
SELECT 1, id_modulo, opcion, items, subitems, 4, 1, 1, GETDATE(), 1
FROM CORE_Menu_Nodos WHERE id_modulo = 13;
```

**Paso 7 — Vistas:** usar tokens CSS del tema (`var(--surface-app)`, `var(--text-app)`, etc.). Usar clases del design system: `.card`, `.btn`, `.badge`, `.alert`.

El módulo hereda automáticamente: auth, CSRF, shell completo, SPA, sidebar dinámico, los 3 temas, toggle de visibilidad por nodo.

### Si el módulo necesita BD separada

Seguir el patrón de `InvDatabase`:
```php
class NuevoDatabase {
    private static ?self $instance = null;
    private $conn = null;

    private function __construct() {
        $server = DB_SERVER;
        $base   = ['Database' => 'nueva_bd', 'CharacterSet' => 'UTF-8', ...];
        $this->conn = @sqlsrv_connect($server, $base);
        // auto-crear si no existe (conectar a master → CREATE DATABASE)
    }

    public static function getInstance(): self { ... }
}
```

---

## 17. Módulo Inventario — BD Separada

### Por qué BD separada

`Control_bines1` venía de un proyecto externo con su propio modelo de datos (secuenciales tipo `INV-00009`, períodos contables, IVA, múltiples tablas maestras). Consolidarlo en `PORTAL_APM` requería replicar ~22 tablas con DDL complejo. Se optó por mantener BD `inventario` separada para:
- Aislamiento: problemas en inventario no afectan el portal principal
- El proyecto original ya tenía datos en esa BD
- `InvDatabase` auto-crea la BD si no existe → cero configuración manual

### Conflicto de rutas resuelto

El directorio físico `portal_apm/inventario/` (del proyecto sub-app anterior) interceptaba la ruta `/inventario` antes de que el router pudiera procesarla. Se eliminó el directorio. La BD `inventario` no fue afectada (es una BD de SQL Server, no un directorio del proyecto).

### Parámetros positionales obligatorios

`InvBaseModel` usa `sqlsrv_*` nativo igual que el resto del proyecto. Los parámetros son posicionales `?`, NO named params PDO (`:key`). Esta es la misma convención de `Database.php`.

---

## 18. Registro de Cambios y Correcciones

### v3.1 (2026-07-01) — pase de exactitud contra el código real

| Corrección | Detalle |
|---|---|
| `routes.php` NO está en `config/` | Está en la **raíz** del proyecto; `index.php` hace `require ROOT/routes.php`. Corregido árbol, bootstrap, guía de extensión y changelog. |
| `config/` tiene un solo archivo | Solo existe `config/app.php` (no `globals.php` ni `database.php`). Bootstrap carga `config/app.php`. |
| `core/ThDatabase.php` faltaba en el árbol | Conexión sqlsrv del sistema externo de Talento Humano. |
| `SessionHelper::setFlash` no existe | El método real es `SessionHelper::flash()`. Añadidos `has/remove/isLoggedIn/userId/nivel/login/logout`. |
| `FormHelper::validate()` retorna `bool` | No un array. Los errores se leen con `errors()/error()/errorHtml()`; `flashOld()/old()` para repintar. |
| Tablas CORE_ son **13**, no 11 | El listado ya tenía las 13; se corrigió el encabezado. |
| Vistas SQL son **10**, no 2 | Añadidas `vw_AuditoriaGlobal`, `vw_KPIs_TH/Bienes/Acceso/Bitacoras`, `vw_ResumenRoles`, `vw_SSO_Usuarios`, `vw_SSO_Menu`. |
| Faltaban rutas Home | `GET /` → `HomeController@index` y `GET /api/demo-sso` → `HomeController@demoSso`. `/` NO es del Dashboard. |
| Faltaban 7 rutas `/admin/roles/*` | `nuevo`, `crear`, `{id}/editar`, `{id}`, `{id}/eliminar`, `{id}/permisos` (GET+POST). |
| Autoloader escanea 4 raíces | `modules/*/controllers`, `modules/*/models`, `controllers/*`, `models/*` (soporta estructura legada). |

### v3.0 (2026-06-29) — sesión previa

#### Nuevas funcionalidades

| Cambio | Archivos |
|---|---|
| Módulo Inventario integrado nativamente (MVC, sqlsrv, BD separada) | `modules/Inventario/**` |
| Sidebar 100% dinámico — eliminadas secciones hardcodeadas | `sidebar.php` |
| Auto-expansión de áreas al expandir módulo (1 click para ver links) | `sidebar.php` JS |
| `CORE_Menu_Nodos` módulo 12 con 3 áreas y 8 ítems | `db/inv_menu_integration.sql` |
| Admin menú: labels MOIS correctos (Ítem / Sub-ítem) | `admin/menu/index.php` |
| Admin menú: sticky header desde título hasta "Cómo funciona" | `admin/menu/index.php` |
| Toggle de nodo con cascade: desactiva todos los descendientes | `MenuController.php` + `admin/menu/index.php` |
| `data-node-id` en cada switch para actualización DOM del cascade | `admin/menu/index.php` |
| `MenuController::MODULES` incluye módulo 12 | `MenuController.php` |
| 16 rutas nuevas `/inventario/*` | `routes.php` (raíz) |
| 7 rutas nuevas `/admin/menu/*` | `routes.php` (raíz) |
| `theme_preview.html` — herramienta de verificación visual de temas | `theme_preview.html` |

#### Correcciones de bugs

| Bug | Corrección |
|---|---|
| `[data-theme="dark"]` / `[data-theme="corporate"]` no matcheaban | Reemplazado por `body.t2, body.t3` / `body.t1` en `admin/menu/index.php`, `admin/menu/form.php`, `admin/rol_permisos.php` |
| Directorio `portal_apm/inventario/` interceptaba ruta `/inventario` | Directorio eliminado |
| `SELECT INTO` perdía constraints DEFAULT de tablas `inv_*` | Tablas `inv_*` mantenidas en BD `inventario` (no copiadas a PORTAL_APM) |
| Error de precedencia AND/OR en SQL de migración | Añadidos paréntesis en condición compuesta |
| `QUOTED_IDENTIFIER` error en script SQL | Añadido `SET QUOTED_IDENTIFIER ON; SET ANSI_NULLS ON;` |

### v2.0 (2026-06-06) — correcciones de esquema

| Corrección | Detalle |
|---|---|
| `BIT_Bitacoras` → `BIT_Eventos` | Tabla real. PK `id_evento`. |
| `BIENES_Inventario` → `BIENES_Activos` | Tabla real. PK `id_activo`. |
| `id_bien` → `id_activo` | BIENES_Activos y BIENES_Movimientos |
| `BIENES_Movimientos` columnas | `id_depto_origen/destino`, `creado_por`, `observaciones` |
| `ACCESO_Registros` columnas | `tipo_acceso`, `persona_visita`, `motivo`, `id_operador` |
| `name="_token"` → `name="_csrf_token"` | 15 vistas + JS de acceso |
| `sp_GetKPIs_*` sin parámetros OUTPUT | Reemplazado por queries directas en DashboardModel |
| Paginación `TOP(n)...OFFSET` inválida | `OFFSET ? ROWS FETCH NEXT ? ROWS ONLY` |
| `core/Model.php:43` — función como default de parámetro | `?int = null` + `??` en cuerpo |

---

*Portal APM v3.1 — Autoridad Portuaria de Manta*
*Última actualización: 2026-07-01*
