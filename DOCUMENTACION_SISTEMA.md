# Portal APM — Documentación del Sistema v4.1

**Portal de Gestión Integral — Autoridad Portuaria de Manta**
PHP 8.0+ · SQL Server 2014+ · sqlsrv nativo · Sin PDO en el portal nativo · Sin Composer

---

## Tabla de Contenidos

1. [Resumen del Sistema](#1-resumen-del-sistema)
2. [Stack Tecnológico](#2-stack-tecnológico)
3. [Arquitectura: 3 patrones de integración](#3-arquitectura-3-patrones-de-integración)
4. [Estructura de Directorios](#4-estructura-de-directorios)
5. [Bootstrap y Front Controller](#5-bootstrap-y-front-controller)
6. [Framework Core](#6-framework-core)
7. [Módulos](#7-módulos)
8. [Helpers](#8-helpers)
9. [Esquema de Base de Datos (PORTAL_APM)](#9-esquema-de-base-de-datos-portal_apm)
10. [Sistema de Menú MOIS](#10-sistema-de-menú-mois)
11. [Identidad sin duplicar: cuentas desde Talento Humano](#11-identidad-sin-duplicar-cuentas-desde-talento-humano)
12. [Seguridad](#12-seguridad)
13. [Frontend y Temas](#13-frontend-y-temas)
14. [Referencia de Rutas](#14-referencia-de-rutas)
15. [Guía de Configuración](#15-guía-de-configuración)
16. [Credenciales Iniciales](#16-credenciales-iniciales)
17. [Cómo integrar un módulo nuevo](#17-cómo-integrar-un-módulo-nuevo)
18. [Registro de Cambios y Correcciones](#18-registro-de-cambios-y-correcciones)

---

## 1. Resumen del Sistema

**Portal APM no tiene módulos de negocio propios.** Es el *hub* central: login,
permisos por rol, menú, administración de cuentas y dashboards agregados.
Los módulos de negocio reales se construyen y mantienen por separado, y se
integran al portal sin que éste absorba su código ni duplique sus datos.

| Módulo integrado | Cómo vive | BD propia |
|---|---|---|
| **Talento Humano** | App embebida (`apps/talento_humano`), SSO con el portal | `Talento_Humano` |
| **Control de Bienes** | App embebida (`apps/control_bienes`), SSO con el portal | `inventario` |
| **Portuaria (Bitácoras)** | Nativo en el router del portal, con su propio stack/BDs | `PortuariaDemo` + `PortuariaExterna` |
| **Control de Acceso** | Nativo en el portal (es la única pieza que sí vive dentro de PORTAL_APM) | Tablas `ACCESO_*` en `PORTAL_APM` |

Son los **únicos 4** con presencia real en el menú. Todo lo demás que puede
aparecer en el código (`pendientes/`, prototipos viejos) son pruebas o
trabajo en curso, no integraciones — ver §18 para el detalle de qué se dio
de baja y por qué.

Características clave:
- **Departamento ≠ Módulo.** `CORE_Departamentos` es el organigrama real (20
  direcciones/departamentos) y no tiene menú propio. El menú (`CORE_Menu_Nodos`)
  solo representa los 4 módulos de la tabla de arriba.
- **Identidad sin duplicar.** El nombre/cédula de una cuenta ligada a un
  empleado de Talento Humano se lee **en vivo** desde `Talento_Humano.dbo.th_empleados`
  (cross-DB, misma instancia SQL Server) — no se copia a mano. Ver §11.
- **Config centralizada por máquina.** `config/connections.php` (no versionado,
  una copia por dev/servidor) es la única fuente de servidor/BDs/credenciales
  para todo el sistema — portal, apps embebidas y el dashboard Python.
- **SPA-Híbrido** — PHP renderiza el shell HTML, AJAX carga el contenido sin recargar la página.
- **sqlsrv exclusivo** en el portal nativo — driver nativo PHP para SQL Server, sin PDO.
- **3 temas CSS** — `body.t1` (Institucional), `body.t2` (Cyber Dark), `body.t3` (Porto Glass).

---

## 2. Stack Tecnológico

### Backend
| Componente | Versión / Detalle |
|---|---|
| PHP | 8.0+ (match expression, tipos union, spread operator) |
| Driver DB (portal nativo) | `sqlsrv_*` nativo (Microsoft Drivers for PHP for SQL Server) |
| Driver DB (apps embebidas) | PDO `sqlsrv:` (mismo driver, distinta API) |
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

## 3. Arquitectura: 3 patrones de integración

Los módulos de este sistema no se construyen todos igual — se les da el
tratamiento que corresponde según si son de PORTAL_APM o externos:

### Patrón A — Nativo (Central, Control de Acceso)
Vive dentro del MVC del portal, reusa `core/` (Controller/Model/Router/View),
lee `config/app.php`/`config/connections.php`. Es lo único que cuenta como
"propio" de PORTAL_APM.

### Patrón B — App embebida + SSO (Talento Humano, Control de Bienes)
Cada una es su propia mini-app PHP (`apps/<nombre>/`), con su propio front
controller y su propio kernel MVC (copiado del más simple existente, no
reescrito desde cero). Se autentica contra la sesión central del portal
(puente SSO en su `index.php`: sin sesión → redirige a `/login`). Su BD es
propia y separada. Es el patrón estándar para integrar un módulo nuevo — ver
§17 y `GUIA_INTEGRACION_MODULOS.md`.

### Patrón C — Nativo con stack propio (Portuaria)
Corre dentro del router nativo del portal (no es una app aparte, no necesita
puente SSO) pero mantiene su propio layout Bootstrap y sus propias 2 bases de
datos (`PortuariaDemo`, `PortuariaExterna`) — decisión real de dominio
(operativo vs. maestros externos), no un accidente a corregir.

```
                    ┌─ Patrón A: Central, Control de Acceso (dentro de core/)
index.php → Router ─┼─ Patrón B: /apps/talento_humano, /apps/control_bienes
                    └─ Patrón C: modules/Portuaria (stack propio, mismo router)
```

Además, dentro del portal nativo:
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

### Autoloader

`index.php` registra un `spl_autoload_register` que escanea, en este orden:
`modules/*/controllers`, `modules/*/models`, `controllers/*`, `models/*`.
Las dos últimas rutas son **fallback legado** — hoy solo quedan ahí
`HomeController` (genuinamente de la raíz, no un módulo de negocio) y
`Menu.php` (el motor del árbol MOIS). Todo lo demás que vivía en ese árbol
viejo (TH, Bienes, Bitácoras, Acceso, Database admin) era una reescritura
nativa de prueba ya dada de baja — ver §18.

---

## 4. Estructura de Directorios

```
portal_apm/
├── index.php                         ← Front Controller del portal nativo
├── routes.php                        ← Registro central de rutas del portal (RAÍZ, no config/)
├── .htaccess                         ← Rewrite rules (Apache)
├── GUIA_INTEGRACION_MODULOS.md       ← Contrato para integrar un módulo nuevo (Patrón B)
├── README.md                         ← Instalación tras clonar
│
├── Z.BASES DE DATOS/                 ← Scripts SQL de referencia, SQL Server 2014+
│   ├── PORTAL_APM_COMPLETO.sql       ← Script DROP+CREATE completo (schema + semilla) de PORTAL_APM
│   ├── Talento_Humano.sql            ← Esquema de referencia del módulo TH (BD propia)
│   ├── inventario.sql                ← Esquema de referencia del módulo Bienes (BD propia)
│   └── PortuariaDemo.sql             ← Esquema de referencia del módulo Portuaria (BD propia)
│
├── config/
│   ├── app.php                       ← Config del portal (APP_URL, DB_*, sesión…). DB_* se derivan
│   │                                    de connections.php, no se editan acá directamente.
│   ├── connections.php               ← NO versionado (depende de la máquina). Única fuente de
│   │                                    servidor/BDs/credenciales para TODO el sistema.
│   ├── connections.example.php       ← Plantilla versionada — copiar a connections.php
│   └── export_analytics_env.php      ← Regenera analytics/.env desde connections.php
│
├── core/                             ← Kernel del portal nativo (Patrón A)
│   ├── Database.php                  ← Singleton sqlsrv (BD PORTAL_APM)
│   ├── Model.php / View.php / Router.php / Controller.php
│   └── ModuleSecurity.php / SqlSrvStatement.php
│
├── helpers/                          ← security_helper, session_helper, url_helper, form_helper
│
├── modules/                          ← Patrón A y C únicamente
│   ├── Central/                      ← Dashboard, paneles nativos, Admin, layouts (Patrón A)
│   ├── Credenciales/                 ← Auth, Perfil, SSO server-to-server (Patrón A)
│   ├── Control_Acceso/               ← Garita, visitantes (Patrón A)
│   └── Portuaria/                    ← Bitácoras CCTV/Visitas/Rondas (Patrón C, stack propio)
│
├── apps/                             ← Patrón B: módulos externos embebidos
│   ├── talento_humano/               ← Mini-app propia. BD: Talento_Humano. Ver §7.3
│   └── control_bienes/               ← Mini-app propia. BD: inventario. Ver §7.4
│
├── db/                                ← Migraciones SQL (idempotentes), aplicadas sobre PORTAL_APM
│   ├── identidad_cross_db.sql          ← CORE_Usuarios.id_empleado_th + vw_Usuarios_Identidad
│   ├── drop_tablas_muertas_modulos.sql ← Baja de TH_*/BIENES_*/BIT_* huérfanas
│   ├── th_bienes_menu_cleanup.sql      ← Menú TH/Bienes: solo header + "Sistema Completo"
│   ├── panel_th_bienes_menu.sql        ← Paneles nativos TH/Bienes + quita Dashboard duplicado
│   ├── portuaria_menu_simplificar.sql  ← Menú Portuaria: solo header + Panel + "Sistema de Bitácoras"
│   ├── apps_origen_integration.sql     ← Registra el nodo "Sistema Completo (Origen)" de TH/Bienes
│   ├── sso_module_login.sql            ← SPs sp_SSO_* + tabla CORE_Aplicaciones
│   └── portuaria/                      ← Esquema y datos de PortuariaDemo/PortuariaExterna
│
├── pendientes/                        ← Trabajo sin terminar, no integrado (ver README ahí)
│
├── analytics/                          ← Dashboard ejecutivo Python/Streamlit (iframe en el portal)
├── imgs/                               ← Logo institucional único (logoapm.png / logoapm_banner.png)
└── public/
    ├── js/  (app.js, charts.js)
    └── img/
```

Estructura de una app embebida estándar (Patrón B, ejemplo `talento_humano`):
```
apps/talento_humano/
├── index.php              ← front controller + puente de sesión SSO
├── core/                  ← Controller/Model/Router/Database propios (copiados, no reescritos)
├── modules/talento-humano/ ← Controladores/Vistas propias de ESTA app (no confundir con modules/ del portal)
└── public/
```

---

## 5. Bootstrap y Front Controller

**`index.php`** (raíz) — punto de entrada único del portal nativo.

Secuencia de arranque:

```
1. require config/app.php      → si falta config/connections.php, muere con
                                  mensaje claro pidiendo copiar el .example
2. date_default_timezone_set  → America/Guayaquil
3. Carga core (Database, Model, View, Router, Controller)
4. spl_autoload_register      → modules/*/{controllers,models}, luego
                                  controllers/*, models/* (fallback legado)
5. Carga helpers              → security, session, url, form
6. require ROOT/routes.php     → new Router() + registra todas las rutas (en la RAÍZ)
7. $router->resolve(uri, method)
```

Las apps embebidas (Patrón B) tienen su **propio** `index.php` con su propio
bootstrap — no pasan por este front controller. Su puente de sesión:

```php
// apps/<modulo>/index.php
if (empty($_SESSION['user_id'])) {
    header('Location: ' . $portalLoginUrl);
    exit;
}
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

### 6.1 Database

**`core/Database.php`** — Singleton, sqlsrv nativo, sin PDO. Conecta a `PORTAL_APM`
usando `config/connections.php`. Para leer de otra BD del sistema (Talento
Humano, inventario, PortuariaDemo) desde una consulta del portal nativo, se
usa el nombre de 3 partes de SQL Server directamente en el SQL — no hace
falta una segunda conexión:

```php
$db->query("SELECT COUNT(*) FROM Talento_Humano.dbo.th_empleados WHERE estado=1");
```

```php
$db = Database::getInstance();
$stmt = $db->query('SELECT * FROM CORE_Usuarios WHERE id_usuario = ?', [$id]);
$usuario = $db->fetch($stmt);
```

Métodos disponibles: `getInstance()`, `query($sql,$params)`, `fetch($stmt)`,
`fetchAll($stmt)`, `rowsAffected($stmt)`, `lastInsertId()`,
`beginTransaction()/commit()/rollback()`, `free($stmt)`, `reset()`.

### 6.2 Model / View / Router / Controller

Sin cambios de fondo respecto a versiones anteriores:
- **`core/Model.php`** — base abstracta; expone los métodos de `Database` como protegidos de instancia.
- **`core/View.php`** — `$this->render('Modulo/vista', $data)` resuelve a `modules/Modulo/views/vista.php`; con header AJAX renderiza solo la vista (sin shell).
- **`core/Router.php`** — rutas exactas y con `{param}` (auto-cast a `int`).
- **`core/Controller.php`** — `requireAuth()`, `requireLevel(n)`, `render()`, `json()`, `redirect()`, `input()`, `csrfToken()`, `verifyCsrf()`, `currentUser()`.

---

## 7. Módulos

### 7.1 Central (Patrón A — lo único realmente "propio" de PORTAL_APM)

**`DashboardController`**
- `index()` — redirige a `executive()` si `nivel_jerarquia>=2`, si no a `operational()`.
  **Nota:** por esto el menú tiene una sola entrada "Dashboard" (`/dashboard`) —
  antes había también "Dashboard Ejecutivo" apuntando a `/dashboard/ejecutivo`,
  que para cualquier Director+ es literalmente la misma página. Se quitó la duplicada.
- `executive()` / `operational()` — KPIs vía `DashboardModel`, ahora **cross-DB**
  (antes leían tablas `TH_Empleados`/`BIENES_Activos`/`BIT_Eventos` locales,
  ya eliminadas — ver §18): empleados desde `Talento_Humano.dbo.th_empleados`,
  bienes desde `inventario.dbo.inv_inventario`, visitas desde
  `PortuariaDemo.dbo.bit_visitas`.

**`PanelController`** (nuevo) — panel nativo de un módulo integrado, con KPIs
en vivo, **antes** de abrir su sistema completo externo. Mismo rol que
`PortalPortuariaController@hub` cumple para Portuaria.
- `talentoHumano()` → `/panel/talento-humano` — total empleados, nuevos del
  mes, género, top unidades organizacionales (`PanelModel::getKpisTH()`).
- `bienes()` → `/panel/bienes` — total, operativos/mantenimiento, valor
  total, por categoría (`PanelModel::getKpisBienes()`).

**`AdminController`** (requiere `requireLevel(3)`)
- `usuarios()` / `nuevoUsuario()` / `crearUsuario()` / `editarUsuario()` / `actualizarUsuario()` / `eliminarUsuario()`
- `empleadosTh()` → `/admin/usuarios/desde-th` (nuevo) — lista empleados de
  Talento Humano **sin** cuenta de portal aún (`NOT EXISTS ... CORE_Usuarios.id_empleado_th`).
- `nuevoUsuarioDesdeEmpleado(int $idEmpleadoTh)` → GET con el mismo path +
  `/{id}/nuevo` (nuevo) — formulario con departamento/rol **autosugeridos**
  vía `TH_Unidad_Map` según la unidad organizacional del empleado.
- `crearUsuarioDesdeEmpleado()` → POST (nuevo) — crea la cuenta con
  `id_empleado_th` seteado; nombre/cédula **no se tipean**, se leen del
  empleado. Ver §11.
- `roles()` / `auditoria()` — sin cambios de fondo.

**`MenuController`** — administra `CORE_Menu_Nodos`/`CORE_Permisos_Nodo` (ver §10). Sin cambios de fondo.

**`NotificacionesController`** — sin cambios.

#### Layouts (`modules/Central/views/layouts/`)

`shell.php`, `sidebar.php` (100% dinámico, ver §10), `topbar.php` — sin
cambios de fondo. `sidebar.php` mapea, además de las rutas del menú, un
`$__prefijosModulo` para reconocer "estás dentro del módulo N" incluso en
URLs que no están en el árbol (ej. `/apps/talento_humano/*`, `/panel/bienes`).

---

### 7.2 Credenciales (Patrón A)

**`AuthController`** — login vía `sp_Login`, logout, tema, perfil, cambio de contraseña.
**`ApiSsoController`** — `/api/sso/login|validate|logout`, usado por las apps
embebidas (Patrón B) para validar sesión server-to-server. Ver `db/sso_module_login.sql`.

---

### 7.3 Talento Humano (Patrón B)

**Dónde vive:** `apps/talento_humano/` — mini-app PHP propia, BD `Talento_Humano`
separada. No hay código de Talento Humano dentro de `modules/` del portal —
la reescritura nativa que existió ahí (`modules/Talento_Humano`) era una
prueba inicial, dada de baja (ver §18).

**Cómo se llega:**
- `/panel/talento-humano` — panel nativo (KPIs, dentro del shell del portal).
- `/apps/talento_humano/` — sistema completo, vía SSO.

**Identidad:** los empleados viven en `Talento_Humano.dbo.th_empleados`. Una
cuenta de portal puede (opcionalmente) ligarse a un empleado via
`CORE_Usuarios.id_empleado_th` — ver §11.

---

### 7.4 Control de Bienes (Patrón B)

**Dónde vive:** `apps/control_bienes/` — mini-app PHP propia, BD `inventario`
separada (tablas `inv_*`: `inv_inventario`, `inv_categorias`, `inv_estados`,
etc.). La reescritura nativa que existió (`modules/Inventario`,
`modules/Control_Bienes`) era prueba inicial, dada de baja (ver §18).

**Cómo se llega:**
- `/panel/bienes` — panel nativo (KPIs, dentro del shell del portal).
- `/apps/control_bienes/` — sistema completo, vía SSO.

**Estados de un bien** (`inv_inventario.estado_id` → `inv_estados`):
`111`=Operativo, `112`=En mantenimiento, `113`=Fuera de servicio,
`114`=En tránsito, `115`=Despachado.

---

### 7.5 Portuaria — Bitácoras (Patrón C)

**Dónde vive:** `modules/Portuaria/` — nativo en el router del portal, layout
Bootstrap propio, dos BDs propias: `PortuariaDemo` (operativo: visitas,
rondas, cámaras) y `PortuariaExterna` (maestros externos APM).

**`PortalPortuariaController@hub`** → `/portuaria` — el equivalente de
`PanelController` para este módulo (existía antes de que TH/Bienes tuvieran
el suyo — de ahí que se les agregara uno igual, ver §18).

El resto de controladores (`PortVisitaController`, `PortRondaController`,
`PortCamaraController`, `PortCatalogoController`, `PortDashboardController`)
replican los paths del proyecto origen (`portuaria_demoV4`) para que el JS
portado funcione sin reescritura — ver rutas completas en §14.

---

### 7.6 Control de Acceso (Patrón A)

**`modules/Control_Acceso/`** — nativo, tablas `ACCESO_*` dentro de `PORTAL_APM`
(es la excepción: sí vive nativamente porque no es un módulo externo con
proyecto propio). `AccesoController`, `VisitanteController`.

`AccesoModel::getEmpleados()` lee la lista de empleados (para el selector de
"quién autorizó") desde `Talento_Humano.dbo.th_empleados` — cross-DB, ya no
de una tabla local (ver §18).

---

## 8. Helpers

Sin cambios de fondo. `SecurityHelper` (CSRF, bcrypt, headers, XSS),
`SessionHelper` (flash, sesión), `FormHelper` (`validate()` retorna `bool`,
errores via `errors()/error()/errorHtml()`, `flashOld()/old()`).

---

## 9. Esquema de Base de Datos (PORTAL_APM)

### 9.1 Convenciones
- **Collation:** `Modern_Spanish_CI_AS` · **Compatibility Level:** 120
- **`estado TINYINT`:** `0`=Anulado/eliminado lógico · `1`=Activo — nunca DELETE físico
- **`nivel_jerarquia`:** `0`=Operador · `1`=Analista · `2`=Director · `3`=Gerente · `4`=SuperAdmin

### 9.2 Tablas CORE_ (13 tablas)

| Tabla | Descripción clave |
|---|---|
| `CORE_Departamentos` | Organigrama real (20 direcciones/departamentos), auto-referenciado (`id_padre`). **Sin menú propio** — ver §10. |
| `CORE_Usuarios` | Cuentas del portal. `hash_contrasena` BCrypt. **`id_empleado_th` (nullable)** — liga la cuenta a un empleado de Talento Humano. Ver §11. |
| `CORE_Roles` | Roles con `nivel_jerarquia` y `id_departamento` opcional. |
| `CORE_Usuarios_Roles` | Puente usuario ↔ rol (múltiples roles por usuario). |
| `CORE_Menu_Nodos` | Jerarquía MOIS 4 niveles. Solo 4 módulos reales (1, 11, 12, 13) — ver §10. |
| `CORE_Permisos_Nodo` | Permiso de rol sobre nodo MOIS. `nivel_crud` 1–4 (ordinal, acumulativo). |
| `CORE_Formularios` / `CORE_Formularios_Permisos` | Permisos granulares sub-nodo. |
| `CORE_Sesiones` | Token de sesión, IP, expiración. |
| `CORE_Auditoria` | Log centralizado del sistema. |
| `CORE_Notificaciones` | Notificaciones in-app. |
| `CORE_Contrasenas_Hist` | Historial BCrypt (máx. 5). |
| `CORE_Config` | Clave-valor por módulo. |

### 9.3 Tabla auxiliar TH_Unidad_Map

`codigo_uorg → id_departamento, id_rol_director, id_rol_analista`. Sugiere
departamento/rol al crear una cuenta desde un empleado de Talento Humano
según su unidad organizacional. Ver §11.

### 9.4 Tablas ACCESO_ (4 tablas)

`ACCESO_Visitantes`, `ACCESO_Vehiculos`, `ACCESO_Registros`, `ACCESO_Auditoria`
— el único módulo de negocio con tablas propias dentro de `PORTAL_APM` (§7.6).

### 9.5 Lo que YA NO está en PORTAL_APM

`TH_Empleados/Contratos/Adendas/Novedades_Medicas/Auditoria`,
`BIENES_Activos/Categorias/Movimientos/Auditoria`,
`BIT_Eventos/Categorias/Archivos/Auditoria` y sus vistas
(`vw_FichaEmpleado`, `vw_KPIs_TH/Bienes/Bitacoras`) — eran copias genéricas
de la reescritura nativa inicial de TH/Bienes/Bitácoras, redundantes con las
BDs reales de cada módulo integrado. Eliminadas — ver `db/drop_tablas_muertas_modulos.sql` y §18.

### 9.6 Bases de datos externas (por módulo)

| BD | Módulo | Tablas clave |
|---|---|---|
| `Talento_Humano` | Talento Humano (Patrón B) | `th_empleados`, `th_unidades_organizacionales`, `th_puestos`, `th_acciones_personal` |
| `inventario` | Control de Bienes (Patrón B) | `inv_inventario`, `inv_categorias`, `inv_estados`, `inv_productos` |
| `PortuariaDemo` / `PortuariaExterna` | Portuaria (Patrón C) | `bit_visitas`, `bit_totales_visitas`, maestros externos |

### 9.7 Stored Procedures

`sp_Login`, `sp_Logout`, `sp_CambiarContrasena`, `sp_RegistrarFalloLogin`,
`sp_GetMenuUsuario`, `sp_RegistrarAuditoria`, `sp_PurgarAuditoria`,
`sp_SSO_*` (Login/ConfirmarLogin/Logout/RegistrarApp/RegistrarFallo/ValidarToken).

`sp_GetKPIs_Ejecutivo` / `sp_GetKPIs_Operativo` — **no los llama nada en PHP
hoy** (`DashboardModel` hace las queries directo), pero se corrigieron al
mismo patrón cross-DB para que no queden como una trampa rota si alguien los
usa a futuro.

### 9.8 Funciones SQL

`fn_TienePermisoNodo`, `fn_TienePermisoFormulario`, `fn_SesionValida`, `fn_GetArbolDepartamento`.

### 9.9 Vistas SQL

| Vista | Descripción |
|---|---|
| `vw_MenuPorUsuario` / `vw_SSO_Menu` | Menú MOIS por usuario (con/sin parámetro). |
| `vw_Usuarios_Identidad` | **Nueva.** Nombre/cédula en vivo desde Talento Humano cuando hay vínculo. Ver §11. |
| `vw_AuditoriaGlobal` | Unifica `CORE_Auditoria` + auditorías de módulos. |
| `vw_KPIs_Acceso` | KPIs de Control de Acceso. |
| `vw_ResumenRoles` / `vw_SSO_Usuarios` | Resumen de roles / proyección SSO. |

---

## 10. Sistema de Menú MOIS

### Departamento ≠ Módulo

Antes, el árbol de menú mezclaba dos cosas bajo `id_modulo`: los 20
departamentos-organigrama (cada uno con su propia entrada de menú, la
mayoría sin funcionalidad real) y los módulos de negocio de verdad. Hoy
están separados:

- **`CORE_Departamentos`** = organigrama puro, usado para `id_departamento`
  de usuarios/roles y reportes — **sin** entrada de menú.
- **`CORE_Menu_Nodos`** = **solo 4 módulos reales**:

| id_modulo | Módulo | Patrón |
|---|---|---|
| 1 | Central — Portal APM (dashboards, admin, mi cuenta) | A |
| 11 | Talento Humano | B |
| 12 | Control de Bienes | B |
| 13 | Portuaria (Bitácoras Portuarias) | C |

### Jerarquía de 4 niveles

```
(id_modulo, opcion, items, subitems)  →  UNIQUE constraint en CORE_Menu_Nodos

L1 (M, 0, 0, 0)  →  Menú/Módulo    — cabecera de módulo, sin URL
L2 (M, N, 0, 0)  →  Opción         — agrupador de pantallas, sin URL
L3 (M, N, I, 0)  →  Ítem           — pantalla real, con URL
L4 (M, N, I, S)  →  Sub-ítem       — acción dentro de una pantalla, con URL
```

`CORE_Permisos_Nodo` referencia la 4-tupla, no `id_nodo` — un nodo se puede
borrar y recrear con la misma coordenada sin perder los permisos asignados.

### Módulo 1 (Central) — estructura actual

```
L1 (1,0,0,0) Central — Portal APM
  L2 (1,1,0,0) Panel Principal
    L3 (1,1,1,0) Dashboard              → /dashboard        (autoadapta según nivel_jerarquia)
    L3 (1,1,3,0) Dashboard Operativo    → /dashboard/operativo
  L2 (1,2,0,0) Administración            (solo nivel_jerarquia >= 3, o ADMIN)
    L3 (1,2,1,0) Gestión de Usuarios    → /admin/usuarios
    L3 (1,2,2,0) Crear cuenta desde TH  → /admin/usuarios/desde-th
    L3 (1,2,3,0) Roles y Permisos       → /admin/roles
    L3 (1,2,4,0) Estructura del Menú    → /admin/menu
    L3 (1,2,5,0) Auditoría del Sistema  → /admin/auditoria
  L2 (1,3,0,0) Mi Cuenta                 (todos los roles)
    L3 (1,3,1,0) Mi Perfil              → /perfil
    L3 (1,3,2,0) Notificaciones         → /notificaciones
```

> **Nota:** existía también "Dashboard Ejecutivo" (`/dashboard/ejecutivo`) como
> entrada separada — se quitó porque `/dashboard` ya redirige exactamente ahí
> para cualquier Director+ (`DashboardController::index()`). Tenerla dos veces
> era 100% redundante.

### Módulos 11 y 12 (Talento Humano / Bienes) — estructura actual

```
L1 (11,0,0,0) Talento Humano
  L3 (11,1,1,0) Panel                  → /panel/talento-humano
  L3 (11,1,5,0) Sistema de Talento Humano → /apps/talento_humano/

L1 (12,0,0,0) Control de Bienes
  L3 (12,1,1,0) Panel                  → /panel/bienes
  L3 (12,1,5,0) Sistema de Control de Bienes → /apps/control_bienes/
```

El ítem 5 ("Sistema Completo") lo registra `db/apps_origen_integration.sql`.
El "Panel" (ítem 1) lo registra `db/panel_th_bienes_menu.sql`.

### Módulo 13 (Portuaria) — simplificado al mismo patrón que TH/Bienes

```
L1 (13,0,0,0) Bitácoras Portuarias
  L2 (13,1,0,0) Bitácoras
    L3 (13,1,1,0) Panel Portuario   → /portuaria
    L3 (13,1,7,0) Sistema de Bitácoras → /visitas
```

Antes tenía ~29 nodos (visitas, rondas, cámaras, catálogos como entradas de
menú separadas), pero la mayoría ya estaban con `estado=0` (invisibles para
cualquier usuario) — el sidebar real ya mostraba solo estos 2 ítems. Lo que
sobraba era la pantalla de administración `/admin/menu`, que sí lista los
nodos deshabilitados y mostraba un árbol de 29 contra 2-3 en TH/Bienes. Se
borraron los nodos ya inertes (y sus permisos) — la funcionalidad real
(`/visitas`, `/rondas`, `/camaras`, `/catalogos`…) no cambió, sigue
alcanzable desde los accesos directos dentro del propio hub (`/portuaria`).

### Visibilidad: dos capas independientes

| Capa | Dónde | Efecto |
|---|---|---|
| `CORE_Menu_Nodos.estado = 0` | Toggle en `/admin/menu` | Oculta para **todos** los usuarios |
| `CORE_Permisos_Nodo.acceso = 0` | Toggle en `/admin/roles` | Oculta solo para ese **rol** |

---

## 11. Identidad sin duplicar: cuentas desde Talento Humano

**El problema que resuelve esta sección:** antes, `CORE_Usuarios` tenía su
propia copia de `nombre_completo`/`cedula`, separada de
`Talento_Humano.dbo.th_empleados`. Si un empleado cambiaba de nombre en TH,
la cuenta del portal quedaba desactualizada — dos fuentes de verdad para el
mismo dato.

**La solución:** `CORE_Usuarios.id_empleado_th` (nullable — no toda cuenta es
un empleado, ej. el superadmin de TI) + la vista `vw_Usuarios_Identidad`:

```sql
CREATE VIEW vw_Usuarios_Identidad AS
SELECT u.id_usuario, u.nombre_usuario, u.id_empleado_th,
    COALESCE(NULLIF(LTRIM(RTRIM(e.nombres+' '+e.apellidos)),''), u.nombre_completo) AS nombre_completo,
    COALESCE(e.cedula, u.cedula) AS cedula
FROM CORE_Usuarios u
LEFT JOIN Talento_Humano.dbo.th_empleados e ON e.empleado_id = u.id_empleado_th;
```

Si `id_empleado_th` está seteado, el nombre/cédula se leen **en vivo** de TH.
Si no, cae al respaldo local de `CORE_Usuarios` (cuentas sin empleado
asociado). Cero duplicación para el caso normal.

### Crear una cuenta desde un empleado de TH

Flujo completo en `AdminController` (§7.1), reconstruido tras haberse perdido
al dar de baja `modules/Talento_Humano` (donde vivía originalmente como
`ThCuentaController`):

1. `/admin/usuarios/desde-th` — lista empleados de TH activos sin cuenta aún.
2. `/admin/usuarios/desde-th/{id}/nuevo` — formulario con departamento/rol
   **autosugeridos** vía `TH_Unidad_Map` (según `codigo_uorg` de la unidad
   del empleado). Si la unidad no está mapeada, se elige a mano.
3. POST crea `CORE_Usuarios` con `id_empleado_th` seteado + la fila en
   `CORE_Usuarios_Roles`.

**Requisito:** `Talento_Humano` y `PORTAL_APM` deben vivir en la misma
instancia SQL Server (ya es el caso — mismo `config/connections.php`).

---

## 12. Seguridad

### Autenticación
1. `sp_Login` verifica usuario/estado/bloqueo, retorna hash BCrypt
2. PHP `password_verify()`
3. `$_SESSION` con `user_id`, `nombre_completo`, `nivel_jerarquia`, `id_departamento`
4. `session_regenerate_id(true)` contra session fixation
5. `sp_RegistrarFalloLogin` bloquea tras N fallos (config en `CORE_Config`)

### CSRF / XSS
CSRF: `bin2hex(random_bytes(32))` en `$_SESSION['_csrf_token']`, verificado con `hash_equals()`.
XSS: `htmlspecialchars($val, ENT_QUOTES, 'UTF-8')` en toda salida a HTML. CSP header.

### Niveles de Jerarquía
| Nivel | Label | Acceso |
|---|---|---|
| 0 | Operativo | Solo módulos con permisos asignados |
| 1 | Analista | Módulos asignados + lectura amplia |
| 2 | Jefatura | Dashboard ejecutivo + todos los módulos |
| 3 | Director/Gerente | + panel Admin (`requireLevel(3)`) |
| 4 | SuperAdmin | Eliminar nodos de menú (`requireLevel(4)`) |

---

## 13. Frontend y Temas

Sin cambios de fondo. `body.t1/t2/t3` (Institucional/Cyber Dark/Porto Glass) —
**no** `[data-theme]`, ese selector no matchea acá. `css/variables.css` +
`css/style.css`. `theme_preview.html` para verificar visualmente los 3 temas.
`public/js/app.js` (SPA orchestrator, sidebar, tema, toasts) + `public/js/charts.js` (ApexCharts wrapper).

---

## 14. Referencia de Rutas

### Auth / Home / Dashboard / Paneles / Perfil / Notificaciones
| Método | URL | Acción |
|---|---|---|
| GET/POST | `/login` | showLogin / login |
| GET | `/logout` | logout |
| POST | `/set-theme` | setTheme |
| GET | `/` | HomeController@index |
| GET | `/dashboard` | index (autoadapta por nivel) |
| GET | `/dashboard/ejecutivo` \| `/dashboard/operativo` | executive / operational |
| GET | `/panel/talento-humano` | PanelController@talentoHumano |
| GET | `/panel/bienes` | PanelController@bienes |
| GET/POST | `/perfil` | perfil / actualizarPerfil |
| GET/POST | `/cambiar-contrasena` | showCambiarContrasena / cambiarContrasena |
| GET | `/notificaciones` \| `/notificaciones/recientes` | index / recientes (JSON) |
| POST | `/notificaciones/marcar-leidas` | marcarLeidas |

### Admin (nivel ≥ 3)
| Método | URL | Acción |
|---|---|---|
| GET | `/admin/usuarios` | usuarios |
| GET | `/admin/usuarios/nuevo` \| POST `/admin/usuarios` | nuevoUsuario / crearUsuario |
| GET | `/admin/usuarios/desde-th` | empleadosTh (nuevo) |
| GET | `/admin/usuarios/desde-th/{id}/nuevo` | nuevoUsuarioDesdeEmpleado (nuevo) |
| POST | `/admin/usuarios/desde-th` | crearUsuarioDesdeEmpleado (nuevo) |
| GET/POST | `/admin/usuarios/{id}/editar` \| `/admin/usuarios/{id}` | editarUsuario / actualizarUsuario |
| POST | `/admin/usuarios/{id}/eliminar` | eliminarUsuario |
| GET/POST | `/admin/roles*` | CRUD + `/admin/roles/{id}/permisos` (matriz MOIS) |
| GET | `/admin/auditoria` | auditoria |
| GET/POST | `/admin/menu*` | MenuController — CRUD + toggle (cascade) |

### Control de Acceso
| Método | URL | Acción |
|---|---|---|
| GET | `/acceso` \| `/acceso/ingresar` \| `/acceso/reporte` | index / ingresar / reporte |
| POST | `/acceso/ingresar` \| `/acceso/salida` | registrarIngreso / registrarSalida |
| GET/POST | `/acceso/visitantes*` | VisitanteController |

### Apps embebidas y SSO
| Método | URL | Acción |
|---|---|---|
| GET | `/apps/{app}` | AppsController@abrir (redirección robusta a `/apps/{app}/`) |
| POST | `/api/sso/login` \| `/validate` \| `/logout` | ApiSsoController |

### Portuaria (Bitácoras) — Patrón C, paths del proyecto origen
| Método | URL | Acción |
|---|---|---|
| GET | `/portuaria` | PortalPortuariaController@hub (panel nativo) |
| GET | `/portuaria/visitas-resumen` \| `/portuaria/actividad` | vistas rápidas |
| GET | `/portuaria/dashboard` \| `/dashboard-jefe` \| `/dashboard-ejecutivo` | PortDashboardController |
| GET/POST | `/visitas*` \| `/bitacoras/visita/*` | PortVisitaController |
| GET/POST | `/rondas` \| `/bitacoras/ronda/api` | PortRondaController |
| GET/POST | `/camaras*` \| `/bitacoras/camara/*` | PortCamaraController |
| GET/POST | `/catalogos*` \| `/bitacoras/catalogo/*` \| `/importar-funcionarios` | PortCatalogoController |

> Ya **no existen** `/th/*`, `/bienes/*`, `/bitacoras` (genérico), `/inventario/*`
> — eran rutas de la reescritura nativa dada de baja. Ver §18.

---

## 15. Guía de Configuración

Ver `README.md` para instalación paso a paso completa tras clonar. Resumen:

1. **`config/connections.php`** — no versionado, copiar de `connections.example.php`
   y apuntar a tu instancia SQL Server. Única fuente de servidor/BDs/credenciales.
2. Bases requeridas: `PORTAL_APM` (script `Z.BASES DE DATOS/PORTAL_APM_COMPLETO.sql`),
   `Talento_Humano`, `PortuariaDemo`/`PortuariaExterna` (ver `db/portuaria/`;
   esquema de referencia también en `Z.BASES DE DATOS/`), `inventario` (se
   auto-crea; esquema de referencia en `Z.BASES DE DATOS/inventario.sql`).
3. Apps embebidas: `apps/control_bienes/.env` (copiar de `.env.example`);
   `apps/talento_humano` no necesita `.env` propio, ya lee `config/connections.php`.
4. Requisitos: PHP 8.0+ con `sqlsrv`, SQL Server 2014+, ODBC Driver 17+, Apache+mod_rewrite (o `php -S`).

---

## 16. Credenciales Iniciales

| Campo | Valor |
|---|---|
| **Usuario** | `admin` |
| **Contraseña** | `Apm2024*` |
| **Nivel** | 4 — SuperAdmin |
| **Rol** | ADMIN (id_rol=1) — permisos MOIS completos |

Cambiar contraseña en `/cambiar-contrasena` tras el primer acceso. Hay 20
cuentas de prueba más (una por departamento/rol) — ver seed de
`Z.BASES DE DATOS/PORTAL_APM_COMPLETO.sql`.

---

## 17. Cómo integrar un módulo nuevo

La guía completa y actualizada vive en **`GUIA_INTEGRACION_MODULOS.md`** —
estructura de carpeta esperada, cómo usar `config/connections.php`, patrón
de puente de sesión SSO, cómo registrar el módulo en `CORE_Menu_Nodos`,
checklist de "listo para integrar". Resumen de la regla de oro:

> Portal APM presta login central, menú y (si querés) el servidor de base
> de datos. Vos seguís siendo dueño de tu propio MVC, tus vistas, tu propia BD.

No reescribir el módulo dentro de `modules/` del portal salvo que sea
genuinamente Patrón A (admin/config del propio portal) o C (necesite correr
nativo con stack propio, como Portuaria). Para todo lo demás, Patrón B.

---

## 18. Registro de Cambios y Correcciones

### v4.0 (2026-07-26) — Portal APM como hub puro, sin módulos propios

Sesión de pulido arquitectónico grande. Resumen de lo que cambió:

**Se dieron de baja** (reescrituras nativas de prueba, superadas por la
integración real vía apps embebidas / Portuaria):
- `modules/Talento_Humano`, `modules/Inventario`, `modules/Control_Bienes`,
  `modules/Bitacoras` — completas, con sus rutas `/th/*`, `/bienes/*`,
  `/inventario/*`, `/bitacoras` (genérico).
- `core/ThDatabase.php` (quedó huérfano).
- Árbol "legacy fallback" del autoloader: `controllers/TH_Talento_Humano`,
  `controllers/Bienes_Control_de_bienes`, `controllers/Bit_Bitacoras`,
  `controllers/General/{Auth,Dashboard}Controller.php`,
  `controllers/Acceso_Control_acceso` — todos **sombreados** (0 rutas los
  usaban) por versiones reales en `modules/` (`Credenciales`, `Central`,
  `Control_Acceso`).
- Tablas huérfanas en `PORTAL_APM`: `TH_Empleados/Contratos/Adendas/Novedades_Medicas/Auditoria`,
  `BIENES_Activos/Categorias/Movimientos/Auditoria`,
  `BIT_Eventos/Categorias/Archivos/Auditoria` + sus vistas
  (`vw_FichaEmpleado`, `vw_KPIs_TH/Bienes/Bitacoras`).

**Arquitectura, MOIS y permisos**
- Departamento ≠ Módulo: `CORE_Departamentos` (20, organigrama real) ya no
  tiene menú propio. `CORE_Menu_Nodos` solo tiene 4 módulos reales (1, 11, 12, 13).
- `CORE_Usuarios.id_empleado_th` + `vw_Usuarios_Identidad`: identidad en vivo
  desde `Talento_Humano.dbo.th_empleados`, sin duplicar nombre/cédula.
- Reconstruido "crear cuenta desde empleado TH" (se había perdido al borrar
  `modules/Talento_Humano`) — ahora en `AdminController`, con autosugerencia
  vía `TH_Unidad_Map`.
- `DashboardModel`/`AccesoModel` repuntados a fuentes reales cross-DB
  (`Talento_Humano`, `inventario`, `PortuariaDemo`) en vez de las tablas
  huérfanas. KPI de "contratos venciendo" eliminado (no tiene base real:
  Talento Humano no tiene tabla de contratos separada).
- Paneles nativos nuevos para Talento Humano (`/panel/talento-humano`) y
  Control de Bienes (`/panel/bienes`) — mismo rol que ya cumplía
  `PortalPortuariaController@hub` para Portuaria.
- Quitada la entrada de menú "Dashboard Ejecutivo" (duplicaba `/dashboard`
  para Director+).
- Paneles TH/Bienes elevados al mismo nivel de detalle visual que
  `/portuaria` (KPIs + gráfico de barras `apm_chart_bars` por unidad/categoría).
- Menú de Portuaria simplificado al mismo patrón que TH/Bienes (header +
  Panel + 1 link) — se borraron ~25 nodos que ya estaban `estado=0` (invisibles
  para todos) y solo ensuciaban la pantalla de administración del menú.

**Configuración**
- `config/connections.php` (no versionado, plantilla `connections.example.php`)
  — única fuente de servidor/BDs/credenciales para portal, apps embebidas y
  el dashboard Python (`config/export_analytics_env.php`).
- `apps/talento_humano/core/Database.php` dejó de hardcodear servidor/credenciales.

**Base de datos**
- `PORTAL_APM_COMPLETO.sql` reescrito completo (DROP+CREATE portable, sin
  rutas de archivo fijas a una máquina), probado de punta a punta contra una
  BD descartable antes de aplicarse.
- Migraciones nuevas en `db/`: `identidad_cross_db.sql`,
  `drop_tablas_muertas_modulos.sql`, `th_bienes_menu_cleanup.sql`,
  `panel_th_bienes_menu.sql`.

**Otros**
- Logo institucional consolidado a `imgs/logoapm.png` / `imgs/logoapm_banner.png`
  (antes duplicado en ~7 ubicaciones).
- `GUIA_INTEGRACION_MODULOS.md` nuevo — contrato para integrar módulos futuros.
- `README.md` actualizado con instalación real tras clonar.

### v4.1 (2026-07-27) — Carpeta única de esquemas + compatibilidad SQL Server 2014+

- `PORTAL_APM_COMPLETO.sql` se mudó a `Z.BASES DE DATOS/PORTAL_APM_COMPLETO.sql`.
  Misma carpeta ahora guarda los esquemas de referencia (solo estructura, sin
  datos) de los 3 módulos integrados, exportados directamente de sus BDs
  reales: `Talento_Humano.sql`, `inventario.sql`, `PortuariaDemo.sql`.
- Los 4 scripts se llevaron a compatibilidad real SQL Server 2014+ (antes
  declaraban serlo pero traían cláusulas de motor más nuevas, heredadas del
  asistente de scripting de SSMS sobre una instancia 2022):
  - `COMPATIBILITY_LEVEL` uniformado a `120` (2014) en los 4 (`Talento_Humano.sql`
    e `inventario.sql` traían `160`, es decir 2022).
  - Eliminado `QUERY_STORE` (requiere 2016+), `ACCELERATED_DATABASE_RECOVERY`
    (requiere 2019+) y `LEDGER`/`CATALOG_COLLATION` en el `CREATE DATABASE`
    (requiere 2022+) de los 4 scripts — ninguna opción tiene efecto real
    para este proyecto, solo bloqueaban la ejecución en un motor 2014 literal.
  - Cuerpo de los 4 scripts revisado contra `STRING_AGG`/`STRING_SPLIT`/
    `TRIM()`/`CONCAT_WS`/`CREATE OR ALTER`/funciones JSON (todas 2016+ o más
    nuevas) — ninguna en uso.
  - Los 4 scripts se probaron de punta a punta contra BDs descartables
    (`*_TESTSCRIPT`, creadas y eliminadas en la misma prueba) tras el ajuste:
    381/381, 180/180, 195/195 y 243/243 batches sin errores.
- Se sumó `PortuariaExterna.sql` (5to script, esquema del maestro externo de
  personas que usa Portuaria) con el mismo tratamiento 2014+.
- **Corrección posterior:** el ajuste inicial solo cubrió opciones a nivel
  de `ALTER DATABASE`. Al correr `PORTAL_APM_COMPLETO.sql` en una máquina con
  un motor SQL Server más antiguo, falló en cada `PRIMARY KEY`/`UNIQUE`/
  `CREATE INDEX` con `'OPTIMIZE_FOR_SEQUENTIAL_KEY' no es una opción
  reconocida` — una opción de **índice/constraint** (no de BD) que requiere
  motor 2019+, independiente del `COMPATIBILITY_LEVEL`. Probarlo en esta
  máquina (motor 2022) nunca lo iba a detectar, porque un motor más nuevo
  acepta sintaxis de versiones anteriores sin importar el nivel de
  compatibilidad configurado. Eliminada de los 5 scripts (182 apariciones);
  vuelto a probar de punta a punta tras la corrección, mismos batches OK.
- **Tercera corrección:** al correr `PORTAL_APM_COMPLETO.sql` en una máquina
  sin `Talento_Humano` instalada todavía, falló `CREATE VIEW
  vw_Usuarios_Identidad` con "nombre de objeto no válido" — a diferencia de
  los stored procedures (que sí difieren la resolución de nombres cross-DB
  hasta ejecutarse), `CREATE VIEW` necesita que la tabla referenciada
  (`Talento_Humano.dbo.th_empleados`) exista YA en el momento de crearse,
  sin importar si la base `Talento_Humano` existe vacía o no. Se envolvió
  la vista en `IF OBJECT_ID('Talento_Humano.dbo.th_empleados') IS NOT NULL
  EXEC(N'CREATE VIEW...')` — si la dependencia no está lista, se salta la
  creación con un aviso en vez de romper el resto del script; se puede
  recrear después corriendo `db/identidad_cross_db.sql`. De paso,
  `db/run_sql.php` no llamaba `sqlsrv_configure('WarningsReturnAsErrors', 0)`
  — un simple `PRINT` (como el aviso de arriba) se reportaba como error y
  abortaba la ejecución aunque el batch en sí no hubiera fallado; corregido
  con el mismo patrón que ya usaba `conexion/conexion.php`. Verificado
  simulando el escenario real (las 4 BDs de módulos sin existir en
  absoluto): 385/385 batches OK.
- **Cuarta corrección — orden de instalación ya no importa:** el stub vacío
  que `PORTAL_APM_COMPLETO.sql` crea para las 4 BDs de módulos (corrección
  anterior) chocaba con el `CREATE DATABASE` sin guarda de
  `Talento_Humano.sql`/`inventario.sql`/`PortuariaDemo.sql`/
  `PortuariaExterna.sql` — si `PORTAL_APM_COMPLETO.sql` corría primero, el
  `CREATE DATABASE` de esos 4 scripts fallaba después con "ya existe".
  Se envolvió el `CREATE DATABASE` de los 4 en
  `IF DB_ID('X') IS NULL EXEC(N'CREATE DATABASE...')`, mismo patrón. Ahora
  los 5 scripts se pueden correr en **cualquier orden**: si el stub vacío ya
  existe, el script simplemente sigue y lo puebla con sus tablas reales; si
  se corre `PORTAL_APM_COMPLETO.sql` de nuevo después, esta vez sí crea
  `vw_Usuarios_Identidad` (la dependencia ya existe). Verificado con las 3
  secuencias posibles (portal→módulos, módulos→portal, portal→módulos→portal
  otra vez) — todas OK, la vista quedó consultable con datos reales al
  final.

### v3.1 (2026-07-01) y anteriores

Ver historial de git para el detalle de las correcciones de exactitud contra
el código real (rutas, esquema, helpers) previas a esta reestructuración —
la mayoría de esos detalles quedaron superados por el cambio de arquitectura
de v4.0 y no se repiten acá.

---

*Portal APM v4.1 — Autoridad Portuaria de Manta*
