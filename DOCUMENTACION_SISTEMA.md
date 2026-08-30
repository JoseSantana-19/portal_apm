# Portal APM — Documentación del Sistema v8.0

**Portal de Gestión Integral — Autoridad Portuaria de Manta**
PHP 7.4 / 8.3 / 8.5 (compatible con las 3 versiones a la vez, ver §2 y §18 v7.0) · SQL Server 2014+ · sqlsrv nativo · Sin PDO en el portal nativo · Sin Composer

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
11. [Identidad: cédula única + cuentas desde Talento Humano](#11-identidad-cédula-única--cuentas-exclusivamente-desde-talento-humano)
    - [11-bis. Contenido del Portal (landing administrable)](#11-bis-contenido-del-portal--landing-pública-administrable)
12. [Seguridad](#12-seguridad)
    - [12-bis. Alertas unificadas (SweetAlert2)](#12-bis-alertas-unificadas-sweetalert2-y-el-gotcha-de-forms-ajax)
13. [Frontend y Temas](#13-frontend-y-temas)
14. [Referencia de Rutas](#14-referencia-de-rutas)
15. [Guía de Configuración](#15-guía-de-configuración)
16. [Credenciales Iniciales](#16-credenciales-iniciales)
17. [Cómo integrar un módulo nuevo — o actualizar uno existente](#17-cómo-integrar-un-módulo-nuevo--o-actualizar-uno-existente)
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
| **Bitácoras Portuarias** | App embebida (`apps/bitacoras`), sesión compartida con el portal (desde 2026-08-04) | `PortuariaDemo` + `PortuariaExterna` |

Son los **únicos 3** con presencia real en el menú. `Control de Acceso` (que
en v4.x vivía nativo dentro de `PORTAL_APM`, tablas `ACCESO_*`) se dio de
baja por completo el 2026-07-28 — nunca llegó a desarrollarse funcionalidad
real ahí, y las tablas quedaron huérfanas causando errores en producción
(ver §18, v5.0). Todo lo demás que puede aparecer en el código
(`pendientes/`, prototipos viejos) son pruebas o trabajo en curso, no
integraciones.

Características clave:
- **Departamento ≠ Módulo.** `CORE_Departamentos` es el organigrama real (20
  direcciones/departamentos) y no tiene menú propio. El menú (`CORE_Menu_Nodos`)
  solo representa los 3 módulos de la tabla de arriba + el propio hub (módulo 1).
- **Identidad por cédula, un solo usuario inicial.** El login es **solo por
  número de cédula** (no existe "nombre de usuario" en ningún formulario).
  `PORTAL_APM` arranca con **una única cuenta** (`admin`, cédula de prueba
  `1234567777`) — cuentas adicionales se crean **exclusivamente** desde
  empleados de Talento Humano, y su nombre/cédula se leen **en vivo** desde
  `Talento_Humano.dbo.th_empleados` (cross-DB, misma instancia SQL Server),
  nunca se tipean a mano. Ver §11.
- **Config centralizada por máquina.** `config/connections.php` (no versionado,
  una copia por dev/servidor) es la única fuente de servidor/BDs/credenciales
  para **todo** el sistema — portal nativo, `apps/talento_humano`,
  `apps/control_bienes` y el dashboard Python.
- **SPA-Híbrido** — PHP renderiza el shell HTML, AJAX carga el contenido sin recargar la página.
- **sqlsrv exclusivo** en el portal nativo — driver nativo PHP para SQL Server, sin PDO.
- **3 temas CSS** — `body.t1` (Institucional), `body.t2` (Cyber Dark), `body.t3` (Porto Glass).
- **Landing pública administrable** — la página de bienvenida (`/`) tiene
  contenido editable desde `/admin/landing`: carrusel de fondos, noticias
  con imagen, y una franja separada de "consejos y novedades" en texto. Ver
  nueva sección §11-bis.
- **Alertas unificadas con SweetAlert2** — reemplazan los `confirm()`/`alert()`
  nativos y los `<div class="alert">` estáticos en todo el panel admin. Ver
  nueva sección §12-bis.
- **Contraseñas: híbrido cliente+servidor** — el navegador hashea con
  SHA-256 antes de enviar (`js/password-hash.js`), el servidor combina eso
  con un pepper compartido (`CORE_Config.PASSWORD_PEPPER`) y bcrypt. Mismo
  esquema en las 4 apps. Ver §12 y `INDICACIONES/GUIA_SEGURIDAD_CONTRASENAS.html`.
- **Notificaciones reales** — `CORE_Notificaciones` (dashboard, campana,
  `/notificaciones`) ya no está vacía: un generador real (cross-DB) crea
  alertas a partir de eventos genuinos de TH/Bienes/Bitácoras/seguridad.
  Ver §7.1 y §18 v7.0.

---

## 2. Stack Tecnológico

### Backend
| Componente | Versión / Detalle |
|---|---|
| PHP | **7.4 / 8.3 / 8.5 simultáneo** (ver §18 v7.0) — sin `match`/`never`/`mixed`/`?->`/`enum`/`readonly`/constructor promotion en todo el código; `str_starts_with`/`str_contains`/`str_ends_with` vía polyfill propio (`helpers/polyfills_php74.php`, no-op en 8.0+) |
| Driver DB (portal nativo) | `sqlsrv_*` nativo (Microsoft Drivers for PHP for SQL Server) |
| Driver DB (apps embebidas) | PDO `sqlsrv:` (mismo driver, distinta API) |
| SQL Server | 2014+ (Compatibility Level 120, collation `Modern_Spanish_CI_AS`) |
| Sesiones | `$_SESSION` PHP nativo + tabla `CORE_Sesiones` |
| Hashing | Híbrido: SHA-256 en cliente (`js/password-hash.js`) + `hash_hmac('sha256', ., PASSWORD_PEPPER)` + `password_hash(PASSWORD_BCRYPT, cost=12)` en servidor, prefijo `peppered:`. Ver §12. |
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

### Patrón A — Nativo (Central, Credenciales)
Vive dentro del MVC del portal, reusa `core/` (Controller/Model/Router/View),
lee `config/app.php`/`config/connections.php`. Es lo único que cuenta como
"propio" de PORTAL_APM — dashboards, admin, login, menú, contenido de la
landing pública. Sin módulos de negocio propios (ver §1).

### Patrón B — App embebida + SSO (Talento Humano, Control de Bienes, Bitácoras)
Cada una es su propia mini-app PHP (`apps/<nombre>/`), con su propio front
controller y su propio kernel MVC (copiado del más simple existente, no
reescrito desde cero). Se autentica contra la sesión central del portal
(puente SSO en su `index.php`: sin sesión → redirige a `/login`). Su BD es
propia y separada. Es el patrón estándar para integrar un módulo nuevo — ver §17.

**Actualizado 2026-08-11/12:** los 3 ahora tienen un árbol MOIS **real y
granular** en `CORE_Menu_Nodos`/`CORE_Permisos_Nodo` (una fila por
pantalla real, no un esqueleto genérico) — ver §10. Talento Humano además
sumó **login + MFA/TOTP + RBAC propios** (`th_roles`, `th_permisos_rol`),
que **coexisten** con el puente SSO del portal, no lo reemplazan: un
usuario puede entrar por SSO (sesión del portal) o directo con sus propias
credenciales de TH — ambos caminos resuelven permisos, cada uno contra su
propia fuente (portal vs. `th_permisos_rol`), sincronizadas por
`SyncPermisosModulo` cuando el rol está mapeado en `CORE_Roles_Modulo_Map`.

### Patrón C — retirado (2026-08-04)
Existió como "nativo con stack propio" para Bitácoras (`modules/Portuaria/`,
router del portal, sin puente SSO). Se migró a Patrón B (`apps/bitacoras/`)
porque en la práctica el módulo terminó desperdigado por 9 carpetas del
árbol propio del portal (`includes/`, `apis/`, `pendientes/`, `views/layouts/`,
`public/js/portuaria/`, `conexion/`, `rutas/`, `dbf/`, `analytics/`, además
de `modules/Portuaria/`) — lejos del aislamiento real que sí tenían TH/Bienes.
La migración además corrigió un bug de producción real: `bit_movimientos`/
`bit_rondas_cabecera` tienen FK a `bit_usuarios_apm`, pero el puente de
sesión escribía ahí el ID de `CORE_Usuarios` del portal directo (espacio de
IDs distinto) — los registros existentes quedaron mal atribuidos a una
cuenta demo vieja. Se resolvió con un resolver find-or-create por cédula en
`apps/bitacoras/modules/Portuaria/models/Auth.php::resolveUsuarioApmId()`.

```
                    ┌─ Patrón A: Central, Credenciales (dentro de core/)
index.php → Router ─┴─ Patrón B: /apps/talento_humano, /apps/control_bienes, /apps/bitacoras
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
1. El usuario hace clic en un link `[data-spa]`, **o envía un `<form>`**
   dentro de `.main-content`/`#main-spa-container` que no tenga el atributo
   `data-bypass` (ver §12-bis para el gotcha de forms que manejan su propio
   AJAX)
2. `js/main.js` intercepta el clic/submit, ejecuta `fetch(url, {headers: {'X-Requested-With': 'XMLHttpRequest'}})`
3. El servidor detecta la cabecera y renderiza **solo la vista** (sin shell)
4. `js/main.js` inyecta el HTML en `#main-spa-container`
5. `history.pushState` actualiza la URL

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
├── README.md                         ← Instalación tras clonar
│
├── Z.BASES DE DATOS/                 ← Scripts SQL de referencia, SQL Server 2014+, sin rutas fijas a una máquina
│   ├── PORTAL_APM_COMPLETO.sql       ← Script DROP+CREATE completo (schema + semilla) de PORTAL_APM
│   ├── Talento_Humano.sql            ← Esquema de referencia del módulo TH (BD propia)
│   ├── inventario.sql                ← Esquema de referencia del módulo Bienes (BD propia)
│   ├── PortuariaDemo.sql             ← Esquema de referencia del módulo Portuaria (BD propia)
│   └── PortuariaExterna.sql          ← Esquema del maestro externo de personas que usa Portuaria
│
├── INDICACIONES/                     ← Guías paso a paso (instalación, integrar/actualizar módulos)
│   ├── INSTALACION_MANUAL_WAMPSERVER.md          ← Instalación 100% manual en WampServer, sin SETUP_PROYECTO.ps1
│   ├── GUIA_MODULOS_NUEVOS_Y_ACTUALIZACIONES.md  ← Contrato para integrar un módulo nuevo (Patrón B) o actualizar uno existente
│   ├── GUIA_SEGURIDAD_CONTRASENAS.html           ← Guía interactiva del esquema híbrido de contraseñas (v7.0)
│   └── GUIA_SSO_LOGIN_ENTRE_MODULOS.md           ← Cómo usar sp_SSO_* / SsoClient.php para loguear un módulo nuevo (v7.0)
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
│   └── polyfills_php74.php           ← str_starts_with/contains/ends_with, no-op en PHP 8.0+ (v7.0)
│
├── modules/                          ← Patrón A únicamente (2 carpetas reales)
│   ├── Central/                      ← Dashboard, paneles nativos, Admin, Landing pública, layouts (Patrón A)
│   └── Credenciales/                 ← Auth (login por cédula), Perfil, SSO server-to-server (Patrón A)
│
├── apps/                             ← Patrón B: módulos externos embebidos
│   ├── talento_humano/               ← Mini-app propia. BD: Talento_Humano. Ver §7.3
│   ├── control_bienes/               ← Mini-app propia. BD: inventario. Ver §7.4
│   └── bitacoras/                    ← Mini-app propia. BDs: PortuariaDemo/PortuariaExterna. Ver §7.5
│
├── db/                                ← Migraciones SQL (idempotentes), aplicadas sobre PORTAL_APM
│   ├── identidad_cross_db.sql          ← CORE_Usuarios.id_empleado_th + vw_Usuarios_Identidad
│   ├── drop_tablas_muertas_modulos.sql ← Baja de TH_*/BIENES_*/BIT_* huérfanas
│   ├── th_bienes_menu_cleanup.sql      ← Menú TH/Bienes: solo header + "Sistema Completo"
│   ├── panel_th_bienes_menu.sql        ← Paneles nativos TH/Bienes + quita Dashboard duplicado
│   ├── portuaria_menu_simplificar.sql  ← Menú Portuaria: solo header + Panel + "Sistema de Bitácoras"
│   ├── apps_origen_integration.sql     ← Registra el nodo "Sistema Completo (Origen)" de TH/Bienes
│   ├── sso_module_login.sql            ← SPs sp_SSO_* + tabla CORE_Aplicaciones (documentado + verificado en v7.0)
│   ├── notificaciones_reales.sql       ← CORE_Notificaciones.id_usuario NULL-able (v7.0)
│   └── portuaria/                      ← Esquema y datos de PortuariaDemo/PortuariaExterna
│
├── pendientes/                        ← Trabajo sin terminar, no integrado (ver README ahí)
│
├── analytics/                          ← Dashboard ejecutivo Python/Streamlit (iframe en el portal)
├── imgs/                               ← Logo institucional + carrusel de fondos de la landing (`imgs/landing/`)
├── js/                                  ← JS global del portal nativo (compartido por TODAS las vistas via shell.php)
│   ├── main.js                          ← SPA orchestrator: sidebar, tema, form-submit interceptor, refreshSidebar()
│   └── alerts.js                        ← PortalAlert — wrapper de SweetAlert2 (ver §12-bis)
└── public/
    ├── js/  (charts.js)
    ├── librerias/Otras_librerias/sweetalert2/  ← SweetAlert2 vendorizado localmente (sin CDN)
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
  `PortuariaDemo.dbo.bit_visitas`. **v7.0:** ambos disparan
  `NotificacionGeneradorModel::generarSiCorresponde()` antes de armar la
  vista; el ejecutivo sumó KPIs reales que faltaban (valor total de
  inventario, empleados nuevos del mes, bienes fuera de servicio) — antes
  se calculaban en Control de Bienes pero no se mostraban acá.

**`PanelController`** (nuevo) — panel nativo de un módulo integrado, con KPIs
en vivo, **antes** de abrir su sistema completo externo. Mismo rol que
`PortalPortuariaController@hub` cumple para Portuaria.
- `talentoHumano()` → `/panel/talento-humano` — total empleados, nuevos del
  mes, género, top unidades organizacionales (`PanelModel::getKpisTH()`).
- `bienes()` → `/panel/bienes` — total, operativos/mantenimiento, valor
  total, por categoría (`PanelModel::getKpisBienes()`).

**`AdminController`** (requiere `requireLevel(3)`)
- `usuarios()` / `editarUsuario()` / `actualizarUsuario()` / `eliminarUsuario()`
  — **no existe creación manual de usuarios.** "Nuevo Usuario" es
  exclusivamente vía Talento Humano (ver debajo) — el formulario de edición
  (`usuario_form.php`) es edit-only.
- `empleadosTh()` → `/admin/usuarios/nuevo` (alias) y `/admin/usuarios/desde-th`
  — lista empleados de Talento Humano **sin** cuenta de portal aún
  (`NOT EXISTS ... CORE_Usuarios.id_empleado_th`), paginado.
- `nuevoUsuarioDesdeEmpleado(int $idEmpleadoTh)` → GET `/admin/usuarios/desde-th/{id}/nuevo`
  — formulario con departamento/rol **autosugeridos** vía `TH_Unidad_Map`
  según la unidad organizacional del empleado; el `nombre_usuario` de la
  cuenta se autogenera igual a la cédula del empleado (no se tipea).
- `crearUsuarioDesdeEmpleado()` → POST `/admin/usuarios/desde-th` — crea la
  cuenta con `id_empleado_th` seteado; nombre/cédula **no se tipean**, se
  leen del empleado. Ver §11.
- `roles()` / `rolPermisos()` / `guardarPermisos()` — matriz de checkboxes en
  cascada (`nivel_crud` 0-4 ordinal, no son 4 flags independientes). El
  guardado es **AJAX** (no recarga la página): responde JSON si detecta
  `View::isAjax()`, y el JS del lado cliente refresca el sidebar solo tras
  guardar (`window.refreshSidebar()`, compartida con Estructura del Menú —
  ver §12-bis para el gotcha de por qué el `<form>` necesita `data-bypass`).
- `auditoria()` — sin cambios de fondo.

**`MenuController`** — administra `CORE_Menu_Nodos`/`CORE_Permisos_Nodo` (ver
§10). El guardado de Estructura del Menú también es AJAX por lote
(`guardarLote()`, transacción + cascada de estado) con refresco de sidebar
sin recargar — `sidebarFragmento()` sirve el fragmento HTML que
`refreshSidebar()` (en `js/main.js`) inyecta.

**`LandingController`** (nuevo) — administra el contenido editable de la
landing pública (`/admin/landing`, nodo MOIS `1,2,6,0`): carrusel de fondos
(`CORE_Landing_Imagenes`), noticias con imagen (`CORE_Landing_Noticias`,
imagen **obligatoria**) y consejos/novedades en texto
(`CORE_Landing_Consejos`, sin imagen). Ver nueva sección §11-bis.

**`NotificacionesController`** — `index()`/`recientes()` disparan
`NotificacionGeneradorModel::generarSiCorresponde()` antes de leer
`CORE_Notificaciones` (igual `DashboardController::executive()/operational()`).
Hasta v7.0 esa tabla existía completa en el esquema pero **nunca tuvo una
sola fila** — nada insertaba ahí. Ver `modules/Central/models/NotificacionGeneradorModel.php`
y §18 v7.0 para el detalle de qué eventos reales generan alertas y el
throttle (`CORE_Config.NOTIF_ULTIMO_SCAN`, no re-escanea más seguido que
cada 15 min).

#### Layouts (`modules/Central/views/layouts/`)

`shell.php`, `sidebar.php` (100% dinámico, ver §10), `topbar.php` — sin
cambios de fondo. `sidebar.php` mapea, además de las rutas del menú, un
`$__prefijosModulo` para reconocer "estás dentro del módulo N" incluso en
URLs que no están en el árbol (ej. `/apps/talento_humano/*`, `/panel/bienes`).

---

### 7.2 Credenciales (Patrón A)

**`AuthController`** — login vía `sp_Login`, logout, tema, perfil, cambio de contraseña.
`perfil()` **(mejorado v7.0)**: foto real desde Talento Humano
(`vw_Usuarios_Identidad.foto` + `Talento_Humano.dbo.vw_th_directorio_empleados`,
solo si la cuenta tiene `id_empleado_th` Y la foto no es el placeholder
genérico `default_avatar.png` Y el archivo existe de verdad en disco —
si no, cae al avatar de iniciales, no muestra una silueta genérica),
cargo/dirección de área reales (antes solo mostraba `CORE_Departamentos.nombre`,
el organigrama genérico), y últimas 6 acciones propias reales desde
`CORE_Auditoria` (antes solo 2 fechas sueltas). Gotcha real encontrado acá:
`vw_AuditoriaGlobal` identifica por `nombre_usuario`, pero las entradas
`LOGIN`/`LOGOUT` nativas del portal **siempre** lo guardan `NULL` — para
"mis propias acciones" hay que consultar `CORE_Auditoria` directo, filtrado
por `id_usuario`.
**`ApiSsoController`** — `/api/sso/login|validate|logout`, para módulos
externos (no Patrón B — esos usan el puente de sesión, ver §3) que
necesiten loguearse contra `CORE_Usuarios` sin compartir sesión PHP con el
portal. Guía completa de uso para un módulo nuevo:
`INDICACIONES/GUIA_SSO_LOGIN_ENTRE_MODULOS.md`. **Bug real corregido en
v7.0:** `libs/SsoClient.php` nunca aplicaba el paso de SHA-256 de cliente
del esquema híbrido (§12) — fallaba el login de cualquier cuenta real; el
mecanismo entero nunca se había probado de punta a punta hasta entonces
(`CORE_Aplicaciones` tenía 0 filas).

---

### 7.3 Talento Humano (Patrón B)

**Dónde vive:** `apps/talento_humano/` — mini-app PHP propia, BD `Talento_Humano`
separada. No hay código de Talento Humano dentro de `modules/` del portal —
la reescritura nativa que existió ahí (`modules/Talento_Humano`) era una
prueba inicial, dada de baja (ver §18).

**Cómo se llega:**
- `/panel/talento-humano` — panel nativo (KPIs, dentro del shell del portal).
- `/apps/talento_humano/` — sistema completo, vía SSO.

**Identidad:** los empleados viven en `Talento_Humano.dbo.th_empleados`. Toda
cuenta de portal creada **después** del admin inicial se liga a un empleado
via `CORE_Usuarios.id_empleado_th` (obligatorio para cuentas nuevas — ver
§11); el admin inicial es la única excepción (`id_empleado_th=NULL`).

**Login/RBAC/auditoría propios (2026-08-11):** además del puente SSO, TH
tiene su propio login con MFA/TOTP y su propio RBAC —
`th_roles` (4 roles nativos: Super Administrador, Director de Talento
Humano, Analista de Nómina, Funcionario Lectura) y `th_permisos_rol`
(flags `puede_visualizar/crear/editar/eliminar` por rol × módulo nativo,
18 módulos). Auditoría inmutable propia (`th_logs_auditoria` o similar,
ver código). Puente de identidad **bidireccional** con el portal: un rol
nativo mapeado en `CORE_Roles_Modulo_Map` (id_modulo=11) sincroniza sus
permisos hacia/desde `CORE_Permisos_Nodo` vía `SyncPermisosModulo`. Roles
nativos actualmente mapeados: `rol_id` 1↔`ADMIN`, 2↔`DIR_TH`, 3↔`ANALISTA_TH`,
4↔`LECTOR`.

> **Gotcha de sesión:** el puente de identidad usa 3 ajustes de sesión PHP
> globales relacionados — si alguna vez hay que tocar el nombre de sesión
> o cómo se resuelve el usuario actual, los 3 se cambian juntos, no solo
> uno (fuente: memoria de proyecto, confirmar contra
> `apps/talento_humano/core/Auth.php` antes de tocar).

---

### 7.4 Control de Bienes (Patrón B)

**Dónde vive:** `apps/control_bienes/` — mini-app PHP propia, BD `inventario`
separada (tablas `inv_*`: `inv_inventario`, `inv_categorias`, `inv_estados`,
etc.). La reescritura nativa que existió (`modules/Inventario`,
`modules/Control_Bienes`) era prueba inicial, dada de baja (ver §18).

**Cómo se llega:**
- `/panel/bienes` — panel nativo (KPIs, dentro del shell del portal).
- `/apps/control_bienes/` — sistema completo, vía SSO.

**Estados de un bien** (`inv_inventario.estado_id` → `inv_estados.idestado`):
`1`=Operativo, `2`=En Mantenimiento, `3`=Fuera de Servicio, `4`=En
Tránsito, `5`=Despachado. **Corrección v7.0:** esta tabla decía
`111`-`115` — nunca fue el valor real (`inv_estados` va de 1 a 5), un
error heredado de versiones anteriores de este documento que se filtró a
código nuevo escrito basándose en él (KPIs del dashboard, generador de
notificaciones) hasta que se verificó contra la tabla real y se corrigió
en todos los puntos.

**Actualizado 2026-07-31** desde el proyecto origen (metodología completa en
`INDICACIONES/GUIA_MODULOS_NUEVOS_Y_ACTUALIZACIONES.md` §2). Trajo: modelo
de inventario v2 (`tipo_bien` CC/AF en productos, tablas
`inv_activos_fijos`/`inv_terceros`/`inv_cierres_periodo`/`inv_cierre_saldos`),
vínculo real de personal (`inv_talento_personal` sincronizado por **trigger
permanente** `trg_sync_th_empleados_to_inventario` en
`Talento_Humano.dbo.th_empleados` — cualquier alta/baja/cambio de empleado
se refleja automático en `inventario`), y FKs de responsable en centros de
consumo. Integraciones propias preservadas en la actualización: puente de
sesión SSO, `core/DatabaseConnection.php`/`DatabaseStatement.php` (fix
propio que ignora SQLSTATE clase `01`), logo/link de vuelta al portal en el
layout.

**Permisos por rol Y por usuario (2026-08-11/12):** Bienes ya tenía un
sistema de permisos **por usuario** (`inv_permisos`, ahora con columna
`nivel_crud`) pero ninguno por rol. Se agregó `inv_roles`/`inv_permisos_rol`
(por ahora sin sembrar — sin datos reales todavía) y 3 roles portal nuevos
mapeados en `CORE_Roles_Modulo_Map` (id_modulo=12):
`BIENES_SUPERVISOR`↔2, `BIENES_OPERADOR`↔3, `BIENES_AUDITOR`↔4 (más
`ADMIN`↔1). `Router::checkPermisos()` resuelve nivel por **dos caminos**
según cómo entró el usuario: puenteado (`Controller::tienePermisoPortal()`
→ `fn_TienePermisoNodo` cross-DB) o nativo (`PermisoModel::nivelEfectivoNativo()`).

**Hueco de seguridad cerrado:** el router resolvía la acción real por
`$_GET['action']`, no por la ruta declarada — cualquier acción pública del
controller resuelto era invocable sin importar qué ruta la gateaba
nominalmente. Se cerró con `Router::POLITICAS`, tabla explícita
`(ruta, acción) → (opción MOIS, nivel mínimo)` (17 políticas).

---

### 7.5 Bitácoras (Patrón B)

**Dónde vive:** `apps/bitacoras/` — app independiente, front controller y
router propios (`apps/bitacoras/index.php`, `apps/bitacoras/routes.php`),
layout Bootstrap propio, dos BDs propias: `PortuariaDemo` (operativo:
visitas, rondas, cámaras) y `PortuariaExterna` (maestros externos APM).
Migrado desde `modules/Portuaria/` (Patrón C) el 2026-08-04 — motivo y
detalle del bug de identidad corregido en la migración: ver §3.

Los controladores (`PortVisitaController`, `PortRondaController`,
`PortCamaraController`, `PortCatalogoController`, `PortDashboardController`,
todos bajo `apps/bitacoras/modules/Portuaria/controllers/`) replican los
paths del proyecto origen (`portuaria_demoV4`) para que el JS portado
funcione sin reescritura — mismas rutas de antes, solo que ahora resueltas
por el router propio de la app en vez del router del portal.

Identidad: sin login propio — sesión compartida del portal
(`apps/bitacoras/modules/Portuaria/models/Auth.php::hydrateFromPortal()`).
`bit_usuarios_apm` se sigue usando como catálogo interno (FK de
`bit_movimientos`/`bit_rondas_cabecera`), pero resuelto por cédula
(find-or-create), no por el ID de `CORE_Usuarios`.

No tiene panel nativo en el portal (`/panel/bitacoras`) como sí tienen TH/Bienes
— queda como mejora futura, no bloqueaba la migración.

**Árbol de permisos real + fixes de gating nativo (2026-08-11/12):** antes
del control de acceso vía MOIS, Bitácoras gateaba TODO por comparación de
`$_SESSION['apm_auth']['nom_departa']` contra strings de departamento —
todo o nada, sin granularidad por pantalla. Ahora `id_modulo=13` tiene 13
opciones + 10 ítems reales (Registros Base se abrió en 6 catálogos +
"Ver todo"; CCTV Cámaras en 2 pantallas + "Bitácora de Cámaras"), cada una
resuelta por `Auth::canXxx()` → `fn_TienePermisoNodo`. De paso se
corrigieron 3 huecos reales encontrados al auditar el sidebar nativo
contra sus propios permisos: CCTV Cámaras reutilizaba el flag de "Rondas"
en vez de `Auth::canAccederCctv()` (existía sin cablear); "Reporte
supervisor" no tenía ningún chequeo; "Importar funcionarios" — controller
sin gating, se agregó `Auth::canImportarFuncionarios()` (nueva, mismo
patrón que sus hermanas).

> **Control de Acceso**, que en v4.x vivía acá como §7.6 (`modules/Control_Acceso/`,
> tablas `ACCESO_*`), se dio de baja por completo el 2026-07-28 — nunca se
> desarrolló funcionalidad real, y las tablas quedaron huérfanas causando un
> error en producción (`DashboardModel` seguía consultándolas). Ver §18, v5.0.

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

### 9.2 Tablas CORE_ (18 tablas)

| Tabla | Descripción clave |
|---|---|
| `CORE_Departamentos` | Organigrama real (20 direcciones/departamentos), auto-referenciado (`id_padre`). **Sin menú propio** — ver §10. |
| `CORE_Usuarios` | Cuentas del portal. `hash_contrasena` BCrypt. `nombre_usuario` **siempre igual a la cédula** (login solo por cédula, ver §11). `id_empleado_th` (nullable) — liga la cuenta a un empleado de Talento Humano; obligatorio para toda cuenta creada después del admin inicial. |
| `CORE_Roles` | Roles con `nivel_jerarquia` y `id_departamento` opcional. |
| `CORE_Usuarios_Roles` | Puente usuario ↔ rol (múltiples roles por usuario). |
| `CORE_Menu_Nodos` | Jerarquía MOIS 4 niveles. 4 módulos reales (1, 11, 12, 13) — ver §10. |
| `CORE_Permisos_Nodo` | Permiso de rol sobre nodo MOIS. `nivel_crud` 0–4 (ordinal, acumulativo). |
| `CORE_Formularios` / `CORE_Formularios_Permisos` | Permisos granulares sub-nodo. |
| `CORE_Sesiones` | Token de sesión, IP, expiración. |
| `CORE_Auditoria` | Log centralizado del sistema. |
| `CORE_Notificaciones` | Notificaciones in-app. `id_usuario` **NULL-able** desde v7.0 (era `NOT NULL`, bloqueaba las notificaciones globales que el propio código ya asumía posibles) — poblada por `NotificacionGeneradorModel`, antes siempre vacía. |
| `CORE_Contrasenas_Hist` | Historial BCrypt (máx. 5). |
| `CORE_Config` | Clave-valor por módulo. |
| `CORE_Aplicaciones` | Apps registradas para SSO server-to-server (`sp_SSO_*`, ver §7.2). |
| `CORE_Roles_Modulo_Map` | Mapea un rol del portal a un rol nativo de un módulo integrado (`id_modulo, id_rol_portal, id_rol_externo`) — usado por `SyncPermisosModulo` para sincronizar permisos en ambas direcciones. Nuevo 2026-08-11, ver §10. |
| `CORE_Landing_Imagenes` | Carrusel de fondos de la landing pública (`/admin/landing`). Ver §11-bis. |
| `CORE_Landing_Noticias` | Noticias **con imagen obligatoria** — alimentan el carrusel de noticias de la landing. Ver §11-bis. |
| `CORE_Landing_Consejos` | Consejos/novedades en texto (sin imagen) — franja aparte, separada del carrusel de noticias. Ver §11-bis. |

### 9.3 Tabla auxiliar TH_Unidad_Map

`codigo_uorg → id_departamento, id_rol_director, id_rol_analista`. Sugiere
departamento/rol al crear una cuenta desde un empleado de Talento Humano
según su unidad organizacional. Ver §11.

### 9.4 Lo que YA NO está en PORTAL_APM

- `TH_Empleados/Contratos/Adendas/Novedades_Medicas/Auditoria`,
  `BIENES_Activos/Categorias/Movimientos/Auditoria`,
  `BIT_Eventos/Categorias/Archivos/Auditoria` y sus vistas
  (`vw_FichaEmpleado`, `vw_KPIs_TH/Bienes/Bitacoras`) — copias genéricas de
  la reescritura nativa inicial de TH/Bienes/Bitácoras, redundantes con las
  BDs reales de cada módulo integrado. Eliminadas en v4.0.
- `ACCESO_Visitantes`, `ACCESO_Vehiculos`, `ACCESO_Registros`,
  `ACCESO_Auditoria` — tablas del módulo Control de Acceso, nunca tuvo
  funcionalidad real desarrollada. Eliminadas en v5.0 (2026-07-28) — ver §18.

### 9.5 Bases de datos externas (por módulo)

| BD | Módulo | Tablas clave |
|---|---|---|
| `Talento_Humano` | Talento Humano (Patrón B) | `th_empleados`, `th_unidades_organizacionales`, `th_puestos`, `th_acciones_personal` |
| `inventario` | Control de Bienes (Patrón B) | `inv_inventario`, `inv_categorias`, `inv_estados`, `inv_productos`, `inv_talento_personal` (sincronizada por trigger desde `Talento_Humano`, ver §7.4), `inv_activos_fijos`, `inv_terceros`, `inv_cierres_periodo` |
| `PortuariaDemo` / `PortuariaExterna` | Bitácoras (Patrón B) | `bit_visitas`, `bit_totales_visitas`, `bit_usuarios_apm`, maestros externos |

### 9.6 Stored Procedures

`sp_Login` (resuelve por **cédula**, no por username — ver §11/§12),
`sp_Logout`, `sp_CambiarContrasena`, `sp_RegistrarFalloLogin`,
`sp_GetMenuUsuario`, `sp_RegistrarAuditoria`, `sp_PurgarAuditoria`,
`sp_SSO_*` (Login/ConfirmarLogin/Logout/RegistrarApp/RegistrarFallo/ValidarToken).

`sp_GetKPIs_Ejecutivo` / `sp_GetKPIs_Operativo` — **no los llama nada en PHP
hoy** (`DashboardModel` hace las queries directo), pero se mantienen al
mismo patrón cross-DB para que no queden como una trampa rota si alguien los
usa a futuro.

### 9.7 Funciones SQL

`fn_TienePermisoNodo`, `fn_TienePermisoFormulario`, `fn_SesionValida`, `fn_GetArbolDepartamento`.

### 9.8 Vistas SQL

| Vista | Descripción |
|---|---|
| `vw_MenuPorUsuario` / `vw_SSO_Menu` | Menú MOIS por usuario (con/sin parámetro). |
| `vw_Usuarios_Identidad` | Nombre/cédula en vivo desde Talento Humano cuando hay vínculo (`id_empleado_th`). Ver §11. |
| `vw_AuditoriaGlobal` | Unifica `CORE_Auditoria` + auditorías de módulos. |
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
    L3 (1,2,3,0) Roles y Permisos       → /admin/roles
    L3 (1,2,4,0) Estructura del Menú    → /admin/menu
    L3 (1,2,5,0) Auditoría del Sistema  → /admin/auditoria
    L3 (1,2,6,0) Contenido del Portal   → /admin/landing     (nuevo, ver §11-bis)
  L2 (1,3,0,0) Mi Cuenta                 (todos los roles)
    L3 (1,3,1,0) Mi Perfil              → /perfil
    L3 (1,3,2,0) Notificaciones         → /notificaciones
```

> **Nota:** el nodo `(1,2,2,0) "Crear cuenta desde Talento Humano"` que
> existía como entrada separada se eliminó — se fusionó dentro del propio
> botón "Nuevo Usuario" en `/admin/usuarios` (`AdminController::empleadosTh()`),
> ya no hay creación manual de cuentas en absoluto (ver §7.1/§11).

> **Nota:** existía también "Dashboard Ejecutivo" (`/dashboard/ejecutivo`) como
> entrada separada — se quitó porque `/dashboard` ya redirige exactamente ahí
> para cualquier Director+ (`DashboardController::index()`). Tenerla dos veces
> era 100% redundante.

### Módulos 11, 12, 13 (TH / Bienes / Bitácoras) — sistema de permisos centralizado ("permisos_centrales", 2026-08-11/12)

**Superado el esqueleto genérico de 2-3 nodos** que describían las
versiones anteriores de este documento. Los 3 módulos integrados ahora
tienen un árbol MOIS **real**, una fila `(id_modulo, opcion, items, 0)`
por pantalla de verdad de cada módulo — no un resumen. Tamaño actual:

| id_modulo | Módulo | Opciones (L2) | Ítems reales (L3) adicionales |
|---|---|---|---|
| 11 | Talento Humano | 15 (0=raíz, 1=Inicio, 2-14 pantallas) | 6 — Auditoría y Control→(Logs de Actividad, Reportes de Auditoría); Prototipos→(Asistencia, Vacaciones, Evaluación, Capacitación) |
| 12 | Control de Bienes | 16 (0=raíz, 1=Dashboard, 2-15 pantallas) | 0 — cada pantalla ya es 1:1 con el sidebar nativo |
| 13 | Bitácoras Portuarias | 14 (0=raíz, 1=Dashboard, 2-13 pantallas) | 10 — Registros Base→(Maestro Personas/Empresas/Destinos/Motivos, Talento Humano, Niveles de importancia, "Ver todo"); Cámaras CCTV→(Maestro de Cámaras, Motivos CCTV, "Bitácora de Cámaras") |

Cada nodo se resuelve por `fn_TienePermisoNodo` (la misma función que usa
Central, §12) — no hay una función de permisos distinta por módulo. Piezas
del sistema:

- **`CORE_Roles_Modulo_Map`** (`id_modulo, id_rol_portal, id_rol_externo`)
  — mapea un rol del portal a un rol nativo del módulo, cuando el módulo
  tiene su propio sistema de roles (TH: `th_roles`; Bienes: `inv_roles`).
- **`SyncPermisosModulo`** (`core/SyncPermisosModulo.php`) — sincronización
  **bidireccional**: guardar permisos desde `/admin/roles/{id}/permisos`
  llama `centralHaciaTh()`/`centralHaciaBienes()`, que actualiza
  `th_permisos_rol`/`inv_permisos_rol` para el rol nativo mapeado (si
  existe mapeo — si no, no hace nada, no falla). El camino inverso
  (nativo → central) vive del lado de cada módulo.
- **Resolución dual (puenteado vs. nativo)** — un módulo que además tiene
  login propio (TH; Bienes parcialmente) resuelve permisos por DOS
  caminos posibles según cómo entró el usuario: sesión puenteada del
  portal (`fn_TienePermisoNodo` cross-DB) o sesión nativa del módulo
  (tabla propia). Ver §7.3/§7.4 para el detalle por módulo.

**Ejemplo real de árbol expandido** (TH, ilustra el patrón L2-cabecera +
L3-ítems-reales, usado cuando una "opción" del sidebar en realidad agrupa
más de una pantalla):

```
L2 (11,12,0,0) Auditoría y Control        (cabecera, sin URL propia)
  L3 (11,12,1,0) Logs de Actividad        → /apps/talento_humano/auditoria/logs
  L3 (11,12,2,0) Reportes de Auditoría    → /apps/talento_humano/auditoria/reportes
```

Migraciones relevantes: `db/{th,bienes,bitacoras}_menu_estructura_real.sql`
(expansión de nodos), `db/bitacoras_menu_items_raiz_reales.sql` (2 ítems
con nombre propio que reemplazan un auto-registro genérico duplicado —
ver "Sidebar: dedupe" más abajo), `db/{th,bienes,bitacoras}_restaurar_acceso_completo.sql`
(corrección del bug de preservación de acceso — ver
`INDICACIONES/GUIA_MODULOS_NUEVOS_Y_ACTUALIZACIONES.md` §1.4).

Historial: Bitácoras llegó a tener ~29 nodos antes de simplificarse a 2
(`db/portuaria_menu_simplificar.sql`, 2026-08-01) y luego expandirse de
nuevo a 24 nodos reales (2026-08-12) — pero ahora cada nodo representa una
pantalla real, no es el mismo tipo de inflación que antes.

### Sidebar del portal: dedupe + aplanado de áreas de 1 pantalla (2026-08-12)

Con árboles más granulares, apareció un bug de UX en
`modules/Central/views/layouts/sidebar.php`: `models/Acceso_/Menu.php`
auto-registra cada nodo "opción" plano (items=0, sin hijos) como un ítem
que se auto-referencia — necesario para que el área no desaparezca del
sidebar cuando no tiene hijos reales (fix de sesión anterior). Con hijos
reales agregados, ese auto-registro quedaba como una fila duplicada con el
mismo nombre que su propio encabezado (ej. "Registros Base" adentro de
"Registros Base"). Dos fixes:

1. `Menu.php` — si algún ítem real del área ya apunta a la misma URL que
   el auto-registro, descarta el duplicado (se resuelve agregando un ítem
   real con nombre propio en la BD, no relabeleando en PHP a ciegas).
2. `sidebar.php` — **aplanado a nivel de ÁREA** (antes solo existía a
   nivel de módulo, para el caso de un módulo con 1 solo ítem total): un
   área con una sola pantalla real y sin hermanas se renderiza como link
   directo, sin acordeón — 1 click en vez de expandir+click, aplica a la
   mayoría de pantallas de TH/Bienes/Bitácoras.

### Visibilidad: dos capas independientes

| Capa | Dónde | Efecto |
|---|---|---|
| `CORE_Menu_Nodos.estado = 0` | Toggle en `/admin/menu` | Oculta para **todos** los usuarios |
| `CORE_Permisos_Nodo.acceso = 0` | Toggle en `/admin/roles` | Oculta solo para ese **rol** |

---

## 11. Identidad: cédula única + cuentas exclusivamente desde Talento Humano

**Reescrito completo 2026-07-28.** Antes existían ~20 cuentas de prueba (una
por rol/departamento) con login por `nombre_usuario` tipeado a mano, y
`CORE_Usuarios` tenía su propia copia de `nombre_completo`/`cedula` separada
de `Talento_Humano.dbo.th_empleados` (dos fuentes de verdad para el mismo
dato). El modelo actual es distinto en dos decisiones de fondo:

### 1. Un solo usuario inicial, cuentas nuevas solo desde TH

`PORTAL_APM` arranca con **una única cuenta** (`id_usuario=1`, rol ADMIN,
`nivel_jerarquia=4`). No hay creación manual de usuarios en ningún
formulario — el único camino para agregar una cuenta es
`AdminController::empleadosTh()` → `crearUsuarioDesdeEmpleado()`, que **exige**
un empleado real de Talento Humano como origen. No es posible crear una
cuenta "suelta" sin vínculo a TH (excepto la del admin inicial, sembrada
directo en el script de instalación).

### 2. Login solo por cédula — sin concepto de "nombre de usuario"

Ningún formulario pide "usuario" — el campo se llama "Número de Cédula" en
toda la UI (`login.php`, formularios de creación/edición). Internamente,
`CORE_Usuarios.nombre_usuario` **sigue existiendo como columna** (la usa
`sp_Login`, SSO, y el contrato de sesión hacia las apps embebidas) pero su
valor es **siempre igual a la cédula** — nunca se muestra ni se pide por
separado. `crearUsuarioDesdeEmpleado()` autogenera
`nombre_usuario = $empleado['cedula']`, no lo tipea el admin.

> Este doble mantenimiento de columna (`nombre_usuario` interno = `cedula`
> visible) es deliberado, no un descuido: cambiar la firma de `sp_Login`/SSO
> para que hablen de "cédula" en vez de "usuario" hubiera significado tocar
> el contrato que ya consumen `apps/talento_humano` y `apps/control_bienes`
> — se prefirió mantener el contrato estable y resolver la simplificación
> solo en la capa de UI/PHP.

### Identidad en vivo desde Talento Humano (sin duplicar)

`CORE_Usuarios.id_empleado_th` (nullable — el admin inicial es la única
cuenta sin vínculo) + la vista `vw_Usuarios_Identidad`:

```sql
CREATE VIEW vw_Usuarios_Identidad AS
SELECT u.id_usuario, u.nombre_usuario, u.id_empleado_th,
    COALESCE(NULLIF(LTRIM(RTRIM(e.nombres+' '+e.apellidos)),''), u.nombre_completo) AS nombre_completo,
    COALESCE(e.cedula, u.cedula) AS cedula,
    COALESCE(e.ruta_foto, u.foto) AS foto
FROM CORE_Usuarios u
LEFT JOIN Talento_Humano.dbo.th_empleados e ON e.empleado_id = u.id_empleado_th;
```

Si `id_empleado_th` está seteado, el nombre/cédula/foto se leen **en vivo**
de TH. Si un empleado cambia de nombre o unidad en Talento Humano, la cuenta
del portal lo refleja automáticamente sin ningún paso manual.

> Esta vista solo se crea si `Talento_Humano.dbo.th_empleados` ya existe al
> momento de correr `PORTAL_APM_COMPLETO.sql` — en una instalación donde
> Talento Humano todavía no está montada, el script imprime un aviso y sigue
> sin fallar; correr `db/identidad_cross_db.sql` después la crea.

### Crear una cuenta desde un empleado de TH

Flujo completo en `AdminController` (§7.1):

1. `/admin/usuarios/nuevo` (alias de `/admin/usuarios/desde-th`) — lista
   paginada de empleados de TH activos sin cuenta de portal aún
   (`NOT EXISTS ... CORE_Usuarios.id_empleado_th`).
2. `/admin/usuarios/desde-th/{id}/nuevo` — formulario con departamento/rol
   **autosugeridos** vía `TH_Unidad_Map` (según `codigo_uorg` de la unidad
   del empleado). Si la unidad no está mapeada, se elige a mano. Sin campo
   de "nombre de usuario" — se autogenera de la cédula.
3. POST crea `CORE_Usuarios` con `id_empleado_th` seteado + la fila en
   `CORE_Usuarios_Roles`.

**Requisito:** `Talento_Humano` y `PORTAL_APM` deben vivir en la misma
instancia SQL Server (ya es el caso — mismo `config/connections.php`).

---

## 11-bis. Contenido del Portal — landing pública administrable

**Nuevo 2026-07-29/31.** La página de bienvenida (`/`, `HomeController@index`,
sin login) tiene 3 piezas de contenido editables desde `/admin/landing`
(`LandingController`, nodo MOIS `1,2,6,0`), cada una en su propia tabla —
**no comparten fila ni semántica**, son entidades independientes a propósito:

| Tabla | Qué es | Dónde aparece en `/` |
|---|---|---|
| `CORE_Landing_Imagenes` | Fotos del carrusel de fondo detrás de toda la página | Fondo fijo (`.slideshow-bg`) |
| `CORE_Landing_Noticias` | Noticias **con imagen obligatoria** | Carrusel visual grande en la columna derecha del hero (`.news-image-carousel`) |
| `CORE_Landing_Consejos` | Texto rotativo corto, **sin imagen**, enlace opcional | Franja horizontal aparte, debajo del hero (`.news-ticker-card`), **solo si además hay noticias con imagen** — si no, el propio panel de consejos ocupa el lugar del carrusel (`.tips-panel-tall`) |

**Diseño adaptativo:** el layout público responde a qué contenido existe
realmente — nunca muestra una caja vacía ni reutiliza las fotos de fondo
como relleno falso:
- Sin noticias ni consejos → hero colapsa a una columna centrada.
- Solo noticias (con imagen) → carrusel a la derecha, sin franja de consejos.
- Solo consejos (sin imagen) → panel vertical de consejos ocupa el lugar del carrusel.
- Ambos → carrusel arriba + franja de consejos abajo (diseño completo).

**Fondo blanco en tema Institucional sin contenido (v7.0):** sin noticias
ni consejos, el fondo (`.slideshow-bg`/`.slideshow-overlay`) pasaba a un
overlay oscuro por defecto incluso bajo el tema Institucional (t1,
pensado como claro) — se veía como un fondo casi negro detrás de una
tarjeta blanca. Corregido con una clase `sin-contenido` en el `<body>`
(calculada en `views/General/home/index.php`, `$sinContenido =
!$tieneNoticias && !$tieneConsejos`) que fuerza `background:#ffffff
!important` solo bajo `body.t1.sin-contenido` — t2/t3 (temas oscuros por
diseño) y el caso con contenido real quedan sin tocar.

**Vista previa 100% real:** `/admin/landing` no reimplementa una copia en
miniatura de la landing — embebe un `<iframe>` apuntando a `/?preview=1`
(mismo `HomeController@index`, con un flag que evita el redirect a
`/dashboard` cuando hay sesión activa, para que el admin pueda verla sin
cerrar sesión), escalado con `transform: scale()` a partir de un ancho fijo
de escritorio (1440px). Cualquier cambio en el código de `views/General/home/index.php`
se refleja ahí automáticamente, sin mantener dos plantillas sincronizadas.

---

## 12. Seguridad

### Autenticación

**Esquema de contraseña — híbrido cliente+servidor (v7.0).** Guía
interactiva completa con demo en vivo: `INDICACIONES/GUIA_SEGURIDAD_CONTRASENAS.html`.
Resumen:
1. El navegador aplica SHA-256 a la contraseña **antes** de enviarla
   (`js/password-hash.js`, `crypto.subtle`, sin librerías) — el servidor
   nunca ve la contraseña real en texto plano, en ningún formulario del
   sistema (login, cambio de clave, crear usuario, desactivar MFA).
2. El servidor combina ese valor con `PASSWORD_PEPPER` (secreto de 32
   bytes en `CORE_Config`, compartido por las 4 apps, autogenerado la
   primera vez que hace falta — mismo mecanismo que `SSO_SECRET`/
   `MFA_ENCRYPTION_KEY`) vía `hash_hmac('sha256', ...)`, y el resultado
   pasa por `password_hash(..., PASSWORD_BCRYPT, cost=12)`. Se guarda con
   prefijo `peppered:` para distinguirlo del bcrypt directo heredado
   (fallback de compatibilidad: una cuenta con hash viejo sigue
   funcionando, se re-hashea sola al esquema nuevo en su próximo login
   exitoso — con el matiz real de que esa migración perezosa deja de
   funcionar en cuanto el cliente ya manda SHA-256 en vez de la
   contraseña real; ver el detalle en la guía interactiva).
3. Por qué no es "solo SHA-256 con un token fijo" (la primera propuesta
   que se evaluó y se descartó): SHA-256 es rápido a propósito — lo
   opuesto de lo que hace falta para una contraseña — y un pepper sin sal
   por usuario facilita, no dificulta, un ataque por diccionario si se
   filtra la base. bcrypt es lento a propósito (cost=12 ≈ 250ms/intento).

**Flujo de login:**
1. El usuario ingresa su **número de cédula** (campo único, sin "usuario"
   separado — ver §11) + contraseña en `/login`.
2. `sp_Login` recibe el hash-de-cliente como `@nombre_usuario` (el nombre
   del parámetro no cambió por compatibilidad con SSO — pero como
   `CORE_Usuarios.nombre_usuario` siempre es igual a la cédula, matchea
   directo) — verifica usuario/estado/bloqueo, retorna el hash guardado.
3. PHP `SecurityHelper::verifyPassword()` (pepper + bcrypt, ver arriba).
4. `$_SESSION` con `user_id`, `nombre_completo`, `nivel_jerarquia`, `id_departamento`.
5. `session_regenerate_id(true)` contra session fixation.
6. `sp_RegistrarFalloLogin` bloquea tras N fallos (config en `CORE_Config`).

**SSO entre módulos (login sin compartir sesión PHP):** `sp_SSO_*` +
`libs/SsoClient.php` / `/api/sso/*` — guía completa, con verificación real
end-to-end, en `INDICACIONES/GUIA_SSO_LOGIN_ENTRE_MODULOS.md`. La lista
blanca de IP por aplicación (`CORE_Aplicaciones.ip_permitidas`) es
**opcional y así debe quedar por defecto** — la red de la Autoridad
Portuaria de Manta es mayormente DHCP, solo un puñado de equipos tiene IP
fija por seguridad/necesidad operativa real.

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

## 12-bis. Alertas unificadas (SweetAlert2) y el gotcha de forms AJAX

**Nuevo 2026-07-30.** Se reemplazaron los `confirm()`/`alert()` nativos del
navegador y los `<div class="alert alert-success/danger">` estáticos por
[SweetAlert2](https://sweetalert2.github.io/), vendorizado localmente en
`public/librerias/Otras_librerias/sweetalert2/` (sin CDN — ya estaba en el
repo, usado antes solo por Portuaria cuando era nativo). Incluido una sola
vez en `modules/Central/views/layouts/shell.php`, así que cubre **todo** lo
que pasa por el shell del portal (Central + Credenciales) automáticamente
— Bitácoras, al ser app embebida (Patrón B) con su propio layout Bootstrap
desde 2026-08-04, no pasa por este shell y no hereda esto automático.

### `js/alerts.js` — `PortalAlert`

Wrapper delgado con 6 métodos, global en toda página que carga el shell:

```js
PortalAlert.success(msg)                 // toast verde, auto-dismiss 3.2s
PortalAlert.warning(msg)                 // toast amarillo, auto-dismiss 4s
PortalAlert.error(msg)                   // modal, requiere click
PortalAlert.errorList(titulo, [items])   // modal con <ul>, para listas de errores de validación
PortalAlert.confirmDelete(msg, target, opts)  // reemplaza confirm() — target: <form> o función
PortalAlert.confirmAction(msg, target, opts)  // igual, ícono "?" en vez de advertencia
```

Patrón para el mensaje flash de PHP (`SessionHelper::flash('success', ...)`),
que antes se renderizaba como un `<div>` estático — ahora se dispara como
toast en `DOMContentLoaded`:

```php
<?php if ($success): ?>
<script>document.addEventListener('DOMContentLoaded', () => PortalAlert.success(<?= json_encode($success) ?>));</script>
<?php endif; ?>
```

Reemplazo de `confirm()` en un botón de borrar (el form ya no necesita
`onsubmit`, el botón pasa de `type="submit"` a `type="button"` con `onclick`):

```html
<form method="POST" action="...">
    <input type="hidden" name="_csrf_token" value="...">
    <button type="button" onclick="PortalAlert.confirmDelete('¿Eliminar?', this.form)">Eliminar</button>
</form>
```

### ⚠️ Gotcha: `data-bypass` en forms que manejan su propio AJAX

`js/main.js` tiene un interceptor **global** de `submit` sobre
`document` que convierte automáticamente en fetch/SPA cualquier `<form>`
dentro de `.main-content`/`#main-spa-container` (ver §3, SPA-Híbrido). Si
además escribís tu **propio** handler de `submit` en un form específico
(por ejemplo para mandar la respuesta como JSON en vez de HTML, como hace
`rol_permisos.php` al guardar permisos), **los dos interceptores agarran el
mismo evento** — `e.preventDefault()` en tu handler no detiene la
propagación hacia el listener global en `document`, así que el genérico
también dispara su propio fetch, recibe tu JSON de vuelta, y lo inyecta
como si fuera HTML — el usuario ve literalmente el JSON crudo pisando la
pantalla en vez de la vista actualizada.

**Solución:** agregar el atributo `data-bypass` al `<form>` — es el
mecanismo de escape que el interceptor global ya respeta (lo usa
`login-form`):

```html
<form method="POST" action="..." id="mi-form" data-bypass>
```

Con eso, el interceptor global lo ignora por completo y solo corre tu
handler específico. Si tu form usa el patrón AJAX ya establecido en
`AdminController::guardarPermisos()`/`MenuController::guardarLote()`
(responde JSON si `View::isAjax()`, refresca el sidebar con
`window.refreshSidebar()` tras guardar), **siempre** necesita `data-bypass`.

---

## 13. Frontend y Temas

Sin cambios de fondo. `body.t1/t2/t3` (Institucional/Cyber Dark/Porto Glass) —
**no** `[data-theme]`, ese selector no matchea acá. `css/variables.css` +
`css/style.css`. `theme_preview.html` para verificar visualmente los 3 temas.
`js/main.js` (SPA orchestrator: sidebar, tema, form-submit interceptor,
`refreshSidebar()`) + `js/alerts.js` (`PortalAlert`, ver §12-bis) +
`public/js/charts.js` (ApexCharts wrapper). `public/js/app.js` es un
archivo huérfano de una versión anterior — nada lo referencia, no editar ahí.

---

## 14. Referencia de Rutas

### Auth / Home / Dashboard / Paneles / Perfil / Notificaciones
| Método | URL | Acción |
|---|---|---|
| GET/POST | `/login` | showLogin / login (por cédula, ver §11) |
| GET | `/logout` | logout |
| POST | `/set-theme` | setTheme |
| GET | `/` | HomeController@index (landing pública, `?preview=1` para el iframe de `/admin/landing`) |
| GET | `/dashboard` \| `/dashboard/ejecutivo` \| `/dashboard/operativo` | index (autoadapta) / executive / operational |
| GET | `/reportes` | DashboardController@reportes |
| GET | `/panel/talento-humano` \| `/panel/bienes` | PanelController@talentoHumano / bienes |
| GET/POST | `/perfil` | perfil / actualizarPerfil |
| GET/POST | `/cambiar-contrasena` | showCambiarContrasena / cambiarContrasena |
| GET | `/notificaciones` \| `/notificaciones/recientes` | index / recientes (JSON) |
| POST | `/notificaciones/marcar-leidas` | marcarLeidas |

### Admin (nivel ≥ 3) — Usuarios / Roles / Menú / Landing
| Método | URL | Acción |
|---|---|---|
| GET | `/admin/usuarios` | usuarios |
| GET | `/admin/usuarios/nuevo` \| `/admin/usuarios/desde-th` | empleadosTh (alias — sin creación manual, ver §11) |
| GET | `/admin/usuarios/desde-th/{id}/nuevo` | nuevoUsuarioDesdeEmpleado |
| POST | `/admin/usuarios/desde-th` | crearUsuarioDesdeEmpleado |
| GET/POST | `/admin/usuarios/{id}/editar` \| `/admin/usuarios/{id}` | editarUsuario / actualizarUsuario |
| POST | `/admin/usuarios/{id}/eliminar` | eliminarUsuario |
| GET | `/admin/usuarios/export/excel` \| `/pdf` \| `/{id}/export/excel` \| `/pdf` | exportar (lista o ficha individual) |
| GET/POST | `/admin/roles*` | CRUD |
| GET/POST | `/admin/roles/{id}/permisos` | rolPermisos / guardarPermisos (**AJAX**, ver §12-bis) |
| GET | `/admin/auditoria` \| `/export/pdf` \| `/export/excel` | auditoria + exportar |
| GET/POST | `/admin/menu*` | CRUD + `toggle` + `guardar-lote` (**AJAX por lote**) + `sidebar-fragmento` |
| GET | `/admin/landing` | LandingController@index |
| POST | `/admin/landing/imagenes*` | subirImagen / moverImagen / toggleImagen / eliminarImagen |
| POST | `/admin/landing/noticias*` | crearNoticia / actualizarNoticia / moverNoticia / toggleNoticia / eliminarNoticia |
| POST | `/admin/landing/consejos*` | crearConsejo / actualizarConsejo / moverConsejo / toggleConsejo / eliminarConsejo |

### Apps embebidas y SSO
| Método | URL | Acción |
|---|---|---|
| GET | `/apps/{app}` | AppsController@abrir (redirección robusta a `/apps/{app}/`) |
| POST | `/api/sso/login` \| `/validate` \| `/logout` | ApiSsoController |

### Bitácoras (Patrón B) — rutas en `apps/bitacoras/routes.php`, no en el portal
Desde 2026-08-04 estas rutas ya **no viven en `routes.php` del portal** — las
resuelve el router propio de `apps/bitacoras/` (mismos paths que antes, para
que el JS portado no se rompa). `/portuaria` (hub SPA nativo) se eliminó —
la app es standalone; `/` de `apps/bitacoras/` ahora sirve
`PortDashboardController@index` directo.

| Método | URL (dentro de `/apps/bitacoras/`) | Acción |
|---|---|---|
| GET | `/portuaria/dashboard` \| `/dashboard-jefe` \| `/dashboard-ejecutivo` | PortDashboardController |
| GET/POST | `/visitas*` \| `/bitacoras/visita/*` | PortVisitaController |
| GET/POST | `/rondas` \| `/bitacoras/ronda/api` | PortRondaController |
| GET/POST | `/camaras*` \| `/bitacoras/camara/*` | PortCamaraController |
| GET/POST | `/catalogos*` \| `/bitacoras/catalogo/*` \| `/importar-funcionarios` | PortCatalogoController |

> Ya **no existen** `/th/*`, `/bienes/*`, `/bitacoras` (genérico), `/inventario/*`
> (reescritura nativa dada de baja en v4.0) ni `/acceso*` (Control de Acceso,
> dado de baja en v5.0, ver §18).

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
5. Los 5 scripts de `Z.BASES DE DATOS/` se pueden correr en **cualquier
   orden** entre sí (cada uno crea su base solo si no existe, y si otro ya
   la creó vacía la puebla igual) — no hay un orden obligatorio.
6. Instalación 100% manual sin `SETUP_PROYECTO.ps1` (útil para WampServer o
   para entender cada paso): `INDICACIONES/INSTALACION_MANUAL_WAMPSERVER.md`.

---

## 16. Credenciales Iniciales

`PORTAL_APM_COMPLETO.sql` siembra **una sola cuenta** (ver §11 — ya no hay
20 cuentas de prueba por departamento/rol):

| Campo | Valor |
|---|---|
| **Cédula** (login) | `1234567777` — valor de prueba, cambiar en un entorno real |
| **Contraseña** | `Apm2024*` |
| **Nivel** | 4 — SuperAdmin |
| **Rol** | ADMIN (id_rol=1) — permisos MOIS completos |
| **id_empleado_th** | `NULL` — única cuenta sin vínculo a Talento Humano |

El login pide **solo cédula**, no hay campo de "usuario" en la UI (ver §11).
Cambiar contraseña en `/cambiar-contrasena` tras el primer acceso. Toda
cuenta adicional se crea desde `/admin/usuarios/nuevo` (empleados reales de
Talento Humano) — no hay forma de crear una cuenta "de prueba" suelta.

---

## 17. Cómo integrar un módulo nuevo — o actualizar uno existente

La guía completa y actualizada vive en
**[`INDICACIONES/GUIA_MODULOS_NUEVOS_Y_ACTUALIZACIONES.md`](INDICACIONES/GUIA_MODULOS_NUEVOS_Y_ACTUALIZACIONES.md)**.
Cubre dos escenarios distintos:

- **Módulo nuevo** — estructura de carpeta esperada, cómo usar
  `config/connections.php`, patrón de puente de sesión SSO, cómo registrar
  el módulo en `CORE_Menu_Nodos`, checklist de "listo para integrar".
- **Actualizar un módulo ya integrado con una versión nueva** (ej. un
  compañero te pasa una carpeta actualizada de su proyecto) — metodología
  de diff a 3 bandas (origen viejo / origen nuevo / tu copia actual), cómo
  distinguir integración propia de código puro, cómo probar migraciones SQL
  en una BD desechable antes de tocar la real, y los gotchas de SQL Server
  que ya aparecieron haciendo esto (Control de Bienes, 2026-07-31).

Resumen de la regla de oro (aplica a ambos casos):

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
- **Quinta corrección — rutas de archivo fijas a una máquina:** los 4
  scripts de módulos traían `FILENAME = N'C:\Program Files\...\MSSQL16.VICTUS\
  MSSQL\DATA\...'` (la ruta real del `CREATE DATABASE` que SSMS exportó, de
  esta máquina de desarrollo) — en cualquier otra instancia, con otro
  nombre/versión/unidad, `CREATE DATABASE` fallaba directo con "Error al
  buscar el archivo... no se puede encontrar la ruta especificada", y esa
  falla en cascada tumbaba cada `ALTER DATABASE` siguiente del script.
  Quitada la ruta fija de los 4: ahora `CREATE DATABASE [X] COLLATE
  Modern_Spanish_CI_AS;` sin `ON PRIMARY`/`FILENAME` — SQL Server usa las
  rutas de datos por defecto que tenga configuradas esa instancia,
  cualquiera que sea (mismo patrón que ya usaba `PORTAL_APM_COMPLETO.sql`
  desde el principio). Se agregó `COLLATE Modern_Spanish_CI_AS` explícito
  para que coincida con `PORTAL_APM` y evitar conflictos de collation en
  joins cross-DB si el collation por defecto del servidor fuera otro.
  Vuelto a probar las 2 secuencias de orden de punta a punta: mismos
  batches OK, `vw_Usuarios_Identidad` con 21 filas reales al final.

**Interfaz**
- Color fijo del sidebar (los 3 temas) cambiado de `#0D2B4E` a `#075177`,
  igual que el sidebar de Control de Bienes — franja de cabecera y
  superficie interna reescaladas proporcionalmente sobre la nueva base.
- Ícono de ancla del bloque de marca del sidebar reemplazado por el logo
  de la APM (`imgs/logoapm.png`).
- Topbar: agregado logo APM + "Autoridad Portuaria de Manta" junto al
  breadcrumb, oculto progresivamente en pantallas angostas (<720px texto,
  <480px todo el bloque).

### v5.0 (2026-07-28 a 2026-08-01) — Identidad por cédula, landing administrable, SweetAlert2, actualización de Control de Bienes

Sesión grande, varios frentes independientes. Resumen:

**Bug crítico corregido primero (2026-07-28):** login caía con
`El nombre de objeto 'ACCESO_Registros' no es válido` — `DashboardModel::getKpisEjecutivo()`/`getKpisOperativo()`
tenían SQL propio embebido consultando esa tabla (no se detectó en la
limpieza de v4.0 porque el grep de esa sesión buscó por nombre de columna
de salida, no por nombre de tabla). Se decidió, a partir de esto, dar de
baja **Control de Acceso por completo** — nunca tuvo funcionalidad real
desarrollada, y el módulo entero (`modules/Control_Acceso/`, tablas
`ACCESO_*`, rutas `/acceso*`) quedaba como una trampa de mantenimiento.

**Identidad: cédula única + un solo usuario inicial**
- `CORE_Usuarios` pasó de ~20 cuentas de prueba a **una sola** (admin).
  Cuentas nuevas solo se crean desde empleados reales de Talento Humano
  (`AdminController::crearUsuarioDesdeEmpleado()`), nunca sueltas.
- Login **solo por número de cédula** — se eliminó el concepto de "nombre
  de usuario" de toda la UI. Internamente `CORE_Usuarios.nombre_usuario`
  sigue existiendo (lo usan `sp_Login`/SSO) pero su valor es siempre igual
  a la cédula — se mantuvo así para no tocar el contrato de sesión que ya
  consumen las apps embebidas. Ver §11.
- Nodo de menú `(1,2,2,0) "Crear cuenta desde Talento Humano"` eliminado —
  se fusionó dentro del botón "Nuevo Usuario" de `/admin/usuarios`.

**Landing pública administrable (§11-bis)**
- 3 tablas nuevas: `CORE_Landing_Imagenes` (carrusel de fondo),
  `CORE_Landing_Noticias` (noticias con imagen **obligatoria**),
  `CORE_Landing_Consejos` (texto rotativo sin imagen — **entidad separada**
  de Noticias, no la misma tabla filtrada por presencia de imagen, como se
  intentó al principio y se corrigió tras feedback).
- `/admin/landing` (`LandingController`, nodo `1,2,6,0`) — vista previa
  100% real vía `<iframe src="/?preview=1">`, no una réplica en miniatura.
- Landing pública (`/`) rediseñada: layout adaptativo según haya o no
  contenido real (nunca cajas vacías ni relleno con las fotos de fondo),
  hero con 3 pasos reales del flujo de acceso (antes eran badges genéricos
  tipo "SSO Integrado"/"Seguridad Cripto" sin relación con lo que el
  sistema hace de verdad), navbar sólido sin imagen, sin modal de
  bienvenida ni consola de integración SSO falsa (esa última exponía
  `/api/demo-sso`, un endpoint sin auth que devolvía nombre/correo real de
  cualquier cédula — se eliminó por completo, era una fuga de datos real).

**SweetAlert2 (§12-bis)**
- Reemplazó `confirm()`/`alert()` nativos y los `<div class="alert">`
  estáticos en Central + Credenciales (13 archivos). `js/alerts.js`
  (`PortalAlert`), vendorizado localmente, sin CDN.
- Gotcha real encontrado y documentado: el interceptor SPA global de forms
  en `js/main.js` agarra cualquier `<form>` dentro de `.main-content`, y si
  el form además tiene su propio handler que responde JSON (como
  `guardarPermisos()`), los dos fetch corren en paralelo y el genérico
  inyecta el JSON crudo como si fuera HTML. Se resuelve con `data-bypass`
  en el form.

**Estructura del Menú / Roles y Permisos**
- Guardado por lote con transacción + cascada de estado
  (`MenuController::guardarLote()`), refresco de sidebar sin recargar
  (`sidebarFragmento()` + `window.refreshSidebar()` en `js/main.js`,
  compartida entre ambas pantallas).
- Roles y Permisos: tabla checklist con checkboxes en cascada reemplazó el
  `<select>` por nodo; guardado también AJAX con el mismo refresco de
  sidebar.

**Control de Bienes actualizado (2026-07-31)** — ver §7.4 y la guía
completa en `INDICACIONES/GUIA_MODULOS_NUEVOS_Y_ACTUALIZACIONES.md` §2.
Trajo modelo de inventario v2 y sincronización real de personal por trigger
permanente en `Talento_Humano.dbo.th_empleados`. Se probó exhaustivamente
en BDs desechables antes de tocar la BD real (incluyendo un caso real de
datos placeholder que colisionaban por ID con empleados reales — se guardó
respaldo antes de aplicar).

**Correcciones de memoria del proyecto**
- Dos memorias internas quedaron desactualizadas y se corrigieron: TH y
  Control de Bienes NO son módulos nativos en `modules/` (una versión
  anterior de esas notas describía una reescritura que no está presente en
  el filesystem actual) — ambos son apps embebidas en `apps/`, consistente
  con el resto de esta documentación.

**Descubrimiento operativo:** `C:\xampp\htdocs\portal_apm` es un **junction
de Windows** al repositorio (mismo archivo físico en disco, confirmado por
file ID idéntico en ambas rutas) — nunca hizo falta "sincronizar" con
`Copy-Item` como se venía haciendo, cualquier edición al repo ya está
sirviéndose en vivo.

### v6.0 (2026-08-11 a 2026-08-12) — Sistema de permisos centralizado + menú lateral

Sesión maratónica en 4 frentes. Resumen:

**1. Permisos centralizados ("permisos_centrales", Fase 0-3)** — TH, Bienes
y Bitácoras pasaron de un esqueleto de menú genérico (2-3 nodos) a un árbol
MOIS real y granular (15/16/14 opciones + 16 ítems reales entre los 3), el
mismo `fn_TienePermisoNodo` que ya usaba Central. Piezas nuevas:
`CORE_Roles_Modulo_Map` (mapeo rol portal ↔ rol nativo del módulo),
`core/SyncPermisosModulo.php` (sync bidireccional al guardar permisos).
Detalle completo en §7.3/§7.4/§7.5 y §10.

- **Talento Humano** sumó login + MFA/TOTP + RBAC propios
  (`th_roles`/`th_permisos_rol`), coexistiendo con el puente SSO.
- **Control de Bienes** sumó permisos por **rol** (antes solo tenía por
  usuario) — `inv_roles`/`inv_permisos_rol`, 3 roles portal nuevos
  (`BIENES_SUPERVISOR/OPERADOR/AUDITOR`). Se cerró un hueco de seguridad
  real: el router resolvía la acción por `$_GET['action']` sin validar
  contra la ruta declarada — cualquier acción pública era invocable sin
  importar qué ruta la gateaba. Fix: `Router::POLITICAS` (17 políticas
  ruta+acción → opción MOIS + nivel mínimo).
- **Bitácoras** pasó de gating 100% booleano por string de departamento a
  permisos granulares por pantalla. Se cerraron 3 huecos reales
  encontrados auditando el sidebar nativo: CCTV Cámaras reutilizaba el
  flag de "Rondas" en vez de su propio permiso (existía sin cablear);
  "Reporte supervisor" no tenía ningún chequeo; "Importar funcionarios"
  sin gating en el controller — las 3 corregidas.

**Gotcha real, encontrado 3 veces (TH, Bienes, Bitácoras):** las
migraciones de "preservar acceso ya otorgado" al pasar al árbol nuevo solo
re-otorgaban el nivel sobre el nodo de entrada (Dashboard), no sobre el
resto de pantallas reales — regresión funcional real (roles con acceso
amplio quedaron viendo solo Dashboard), no solo cosmética. Corregido con
scripts de restauración dedicados; documentado como gotcha permanente en
`INDICACIONES/GUIA_MODULOS_NUEVOS_Y_ACTUALIZACIONES.md` §1.4 para no
repetirlo con el próximo módulo.

**2. Estructura del Menú / Roles y Permisos — coinciden con el sidebar real**
Varias pantallas de TH y Bitácoras estaban agrupadas en el árbol de admin
(1 nodo = 1 grupo) cuando el sidebar real del módulo las mostraba como
links sueltos (ej. "Registros Base" de Bitácoras: 1 nodo → ahora 6 ítems
reales + "Ver todo"). Se investigó leyendo el código real de cada sidebar
(no asumido) y se expandió el árbol 1:1.

**3. Menú lateral del portal — dedupe + aplanado de áreas** El
auto-registro que evita que un área plana desaparezca del sidebar (fix de
sesión anterior) quedó mostrando filas duplicadas con el mismo nombre que
su propio encabezado, una vez que esas áreas ganaron hijos reales.
Corregido en `Menu.php` (descarta el auto-registro si un ítem real
comparte su URL) y en `sidebar.php` (aplanado nuevo a nivel de ÁREA, no
solo de módulo — área de 1 sola pantalla = link directo, sin acordeón).
Ver §10.

**4. Bitácoras — datos pendientes + fix de ruta rota**
`db/bitacoras_update_tipos_visita_adjuntos.sql` y
`db/bitacoras_externa_reg_empresas.sql` (128 filas reales de `reg_empresas`,
corrige una cascada de búsqueda de empresas silenciosamente rota sin esos
datos) aplicados a `PortuariaDemo`/`PortuariaExterna` con backup previo.
Se portó `bit_consulta.php` (detalle de ronda desde Dashboard Jefatura,
roto tras la migración a `apps/bitacoras`) a ruta+controller+vista reales.

### v7.0 (2026-08-23 a 2026-08-27) — Contraseñas híbridas, compatibilidad multi-PHP, SSO verificado, notificaciones reales

Sesión larga en 6 frentes independientes. Resumen:

**1. Esquema de contraseñas híbrido cliente+servidor.** Se descartó a
propósito la propuesta original ("SHA-256 + token fijo, guardado tal
cual") por insegura — ver §12 para el porqué — y se implementó en su
lugar: SHA-256 en cliente (`js/password-hash.js`, nuevo, compartido por
las 4 apps) + pepper (`CORE_Config.PASSWORD_PEPPER`) + bcrypt en servidor,
prefijo `peppered:`. Cableado en el login y en todo formulario que toque
una contraseña (cambio de clave, crear usuario, desactivar MFA) de Portal,
Talento Humano y Control de Bienes; Bitácoras actualizado por completitud
aunque su `PortUsuarioModel` no tiene caller real hoy. Guía interactiva
completa con demo en vivo (calculadora de fuerza bruta, línea de tiempo,
diagrama de arquitectura, mapa de archivos): `INDICACIONES/GUIA_SEGURIDAD_CONTRASENAS.html`.

- **Consecuencia real descubierta a mitad de la implementación:** el
  cliente mandando SHA-256 en vez de la contraseña real rompe la
  migración perezosa para cualquier cuenta cuyo hash actual siga sin el
  prefijo `peppered:` — el servidor ya no puede volver a comparar contra
  un hash calculado sobre texto plano. Afectó a las ~620 cuentas reales de
  Talento Humano, incluida `admin_apm` (única super-admin nativa de TH).
  Portal y Control de Bienes se re-provisionaron con su clave real
  conocida; `admin_apm` recibió clave temporal con cambio forzado en el
  próximo login (su clave real no estaba documentada en ningún lado del
  repo, se buscó exhaustivamente antes de resetearla).
- **Bug real cazado y corregido:** el login-puente de Talento Humano
  (cédula del portal tecleada en el login nativo de TH) fallaba de forma
  consistente pese a que servidor y cliente probaban correctos por
  separado. Causa: Chrome servía `login.js` desde caché de disco
  (`transferSize:0` en `performance.getEntriesByType`) sin volver a
  pedirlo por red — Apache no mandaba `Cache-Control` y el `<script src>`
  no tenía versión. Se agregó `?v=filemtime(...)` a todos los scripts de
  hasheo de contraseña del sistema (mismo patrón que ya usaba el sidebar).

**2. Compatibilidad simultánea PHP 7.4 / 8.3 / 8.5.** Escaneo de los 349
archivos `.php` del proyecto (PHPCompatibility 9.3.5 vía Composer en un
scratchpad — nunca en el proyecto — resultó tener sus tablas de datos
topadas en 7.4, sin conocimiento de nada agregado en 8.0+; el hallazgo
real se hizo con grep dirigido, no con la herramienta). Encontrado y
corregido en todo el proyecto:
- `str_starts_with`/`str_contains`/`str_ends_with` (172 llamadas, 40
  archivos) — resuelto con un polyfill centralizado
  (`helpers/polyfills_php74.php`, no-op en 8.0+) requerido lo antes
  posible en cada punto de entrada real (4 front controllers + 2 scripts
  CLI de TH + 10 tests estáticos de TH + 2 guards de APIs legacy de
  Bitácoras — 17 puntos de `require` en total), en vez de tocar los 172
  sitios de llamada uno por uno.
- `match()` (~24 sitios, ~20 archivos) → reescrito a `switch` o lookup por
  array literal, según la complejidad de cada caso.
- `: never` (13 sitios, 9 archivos) → removido, reemplazado por `: void`.
- `mixed` como tipo de parámetro/retorno (12 sitios reales, 10 archivos
  contando `core/Model.php::outParam()` y su copia en `apps/bitacoras`,
  encontrado en una segunda pasada — el patrón `mixed &$var` con
  referencia se había escapado del primer grep) → tipo removido.
- `?->` nullsafe (3 sitios) → reescrito a chequeo explícito `!== null`.
- Cero enums, `readonly`, constructor promotion o first-class callables
  en todo el proyecto — nada que tocar ahí.

Verificado con `php -l` sobre los 349 archivos (0 errores) y en vivo con
clic real de navegador: login del portal, dashboard, puente a TH, Control
de Bienes y Bitácoras cargan las 4 sin error. **Limitación real:** esta
máquina de desarrollo solo tiene PHP 8.2.4 instalado — no se pudo correr
el proyecto bajo un runtime 7.4 u 8.5 real, la verificación fue estática
(grep + `php -l`) más pruebas funcionales bajo 8.2.4.

**3. SSO entre módulos — documentado y verificado por primera vez.**
`db/sso_module_login.sql` (`sp_SSO_Login`/`ConfirmarLogin`/`RegistrarFallo`/
`ValidarToken`/`Logout`/`RegistrarApp` + `CORE_Aplicaciones`) existía
completo desde antes pero **nunca se había usado ni probado** — 0
aplicaciones registradas, 0 sesiones SSO en toda la base. Se probó de
punta a punta con una app desechable (creada, usada, borrada) por SQL
directo (`libs/SsoClient.php`) y por los 3 endpoints HTTP
(`/api/sso/login|validate|logout`). Encontró y corrigió el mismo bug de
fondo que en el punto 1: `SsoClient::verifyPassword()` nunca aplicaba el
paso de SHA-256 de cliente, fallaba el login de cualquier cuenta real.
Guía completa para módulos nuevos: `INDICACIONES/GUIA_SSO_LOGIN_ENTRE_MODULOS.md`
(incluye la aclaración de que `ip_permitidas` debe quedar `NULL` por
defecto — la red de la APM es mayormente DHCP).

**4. Notificaciones reales.** `CORE_Notificaciones` existía completa en el
esquema (tipo/prioridad/leída/url_accion) pero tenía 0 filas — nada en
todo el código insertaba ahí. Se corrigió `id_usuario` a `NULL`-able
(bloqueaba las notificaciones globales que el propio PHP ya asumía
posibles) y se agregó `NotificacionGeneradorModel` (nuevo,
`modules/Central/models/`), que escanea eventos reales cross-DB —
empleados nuevos y cumpleaños de hoy (TH), bienes en mantenimiento
(Bienes), visitas sin registro de salida hace 12+ horas (Bitácoras),
cuentas bloqueadas (portal) — con deduplicación (no repite el mismo
título+mensaje dentro de 24h) y throttle (no re-escanea más seguido que
cada 15 min, vía `CORE_Config.NOTIF_ULTIMO_SCAN`). Disparado desde
`DashboardController` y `NotificacionesController`. Verificado en vivo:
generó 3 alertas reales de datos reales (18 visitas sin salida, un
cumpleaños real, un ingreso real) en el primer escaneo.

**5. Dashboards enriquecidos.** `DashboardModel` sumó KPIs reales que
faltaban: valor total de inventario (ya se calculaba en Control de
Bienes, no se mostraba en el portal), empleados nuevos del mes, bienes
fuera de servicio, visitas del día. Ejecutivo sumó una 5ta tarjeta ("Valor
de Inventario"); Operativo sumó el ítem de pendientes correspondiente.

**6. Perfil mejorado con datos reales.** Foto real desde Talento Humano
(con fallback correcto al avatar de iniciales cuando es el placeholder
genérico o no hay archivo), cargo/dirección de área reales (antes solo el
organigrama genérico), y las últimas 6 acciones propias reales desde
`CORE_Auditoria` (antes solo 2 fechas sueltas). Gotcha real encontrado:
`vw_AuditoriaGlobal` identifica por `nombre_usuario`, que las entradas
`LOGIN`/`LOGOUT` nativas del portal siempre guardan `NULL` — hubo que
consultar `CORE_Auditoria` directo por `id_usuario` en su lugar.

### v8.0 (2026-08-28) — Actualización de Talento Humano y Control de Bienes a sus versiones origen más recientes

Aplicado el método de actualización de 3 vías documentado en
`INDICACIONES/GUIA_MODULOS_NUEVOS_Y_ACTUALIZACIONES.md` (Parte 2): diff
OLD/NEW/CUR, `git merge-file` para el grueso del diff, revisión manual de
cada conflicto real, escaneo dirigido de pérdidas silenciosas del merge
automático, verificación de BD contra el servidor real antes y después de
cualquier cambio de esquema. TH: 158 archivos en el diff, 8 conflictos
reales + 3 pérdidas silenciosas encontradas aparte. Bienes: 95 archivos en
el diff, 2 conflictos reales + 1 migración con bug real cazado en un clon
desechable.

**1. Pérdidas silenciosas del merge automático (el hallazgo metodológico
más importante de esta sesión).** `git merge-file` da por "sin conflicto"
cualquier hunk donde CUR nunca se apartó de OLD, aunque ese hunk sea
justo donde NEW hizo un cambio real — adopta NEW en silencio, sin marcar
nada. Encontrado así en TH: el bloque completo de puente SSO
(`loginTrusted()`/`syncPortalSession()`/cascada de inactividad, ~150
líneas) desapareció de `core/Auth.php`; la firma de `establishSession()`
perdió sus parámetros `$auditAction`/`$auditDescription`; el fallback a
`config/connections.php` desapareció de `core/Database.php`/`Config.php`;
el botón "Volver al Portal APM" desapareció de `shared/topbar.php`; el
`<link>` a SweetAlert2 desapareció de `shared/head_assets.php`. Los 4
archivos con caídas sospechosas de líneas en Bienes (hasta -722 líneas en
un archivo) se investigaron uno por uno — resultaron ser reescrituras
reales más compactas (dashboard rediseñado, reporte refactorizado a
config-driven con export CSV nuevo), no pérdidas; se confirmó comparando
el inventario de funciones antes de aceptar el resultado. Todo archivo
"limpio" del merge automático se re-verificó con un diff dirigido contra
el CUR real antes de darlo por bueno — no basta con que `php -l` pase.

**2. Bug real encontrado en producción por prueba en vivo, no por
lectura de código.** `apps/talento_humano/.htaccess` — un dotfile, fuera
del alcance de `php -l` y del diff por extensión — quedó con
`RewriteBase /PortalPortuario/` (valor de origen) en vez de
`/portal_apm/apps/talento_humano/`, rompiendo toda URL "bonita" de TH
(login incluido, 404 en todo). Detectado con un curl real después de
copiar al repo, no antes; corregido y reverificado (200/302 según
corresponde en cada ruta).

**3. Sistemas de inactividad propios de origen, descartados a
propósito.** Ambos módulos traen ahora su propio modal de aviso de
inactividad (`session_guard.css/js` en TH, `session_inactivity.js` +
`#session-warning-modal` en Bienes) compitiendo con el sistema
centralizado SweetAlert2 ya establecido (ver §12-bis, [[inactividad_sesion]]
en memoria). Se descartó el modal propio en los dos casos — nunca se
referencia su CSS/JS en ninguna vista — y se conservó el bloque
`window.APP_INACTIVIDAD` + SweetAlert2 vendorizado del portal tal cual
estaba. En Bienes el modal nunca había llegado a engancharse siquiera
(el HTML del modal no existía en ningún lado del CUR anterior); en TH sí
competía activamente.

**4. Funcionalidad nueva real adoptada.** TH: módulo Paz y Salvo completo
(formulario por secciones, cierre, PDF vía FPDF), Vacaciones reemplazado
de prototipo estático a datos reales (`vw_th_vacaciones_acciones`),
autoguardado de borradores de formulario (AES-256-GCM, `Config::draftKey()`),
selector de funcionario con autocompletado + sugerencia de rol por puesto
en creación de usuarios (`sp_th_mapa_roles_puestos`), mapa socioeconómico
con protección SSRF real contra IPs privadas (`validarDestinoPublico()`),
reloj institucional testeable (`InstitutionalClock`, `PORTAL_TEST_TODAY`),
exportación a Excel nativa (`XlsxWriter`, sin ZipArchive/Composer). Bienes:
módulo de Órdenes de Compra y Requisiciones completo, exportación a Excel
(`excel_export_helper.php`), buscador con ayudas de UI nuevas
(`inv_searchable_select.js`, `inv_list_search.js`), autoguardado de
borradores propio (`form_draft_recovery.js`), importador de proveedores
históricos desde DBF. Todo el código nuevo de origen usa sintaxis PHP
8.0+ (`match()`, `: never`, tipos unión, `catch (Throwable)` sin
variable) — se adaptó archivo por archivo al patrón de compatibilidad ya
establecido (§2) antes de integrarlo.

**5. Esquema híbrido de contraseñas preservado sin excepción.** El
formulario de creación de usuarios de TH origen ahora deriva el nombre de
usuario desde la cédula del funcionario (coherente con
[[identidad_cedula_unica]]) y valida la clave con fuerza en el servidor
sobre texto plano — se adoptó la derivación por cédula pero se descartó
la validación de texto plano, manteniendo `Auth::hashPasswordSecure()`
sobre el hash SHA-256 que ya manda el cliente.

**6. Migraciones SQL — dos historias distintas.** `Talento_Humano`: el
`.trn` restaurado por el usuario ya traía las 16 migraciones nuevas
aplicadas (`th_schema_migrations`, verificado por checksum SHA-256 exacto
contra cada archivo del drop nuevo — las 16 coinciden) — no hizo falta
ejecutar nada. `inventario`: el `.bak` restaurado era anterior a las 5
migraciones más nuevas (`fecha_fin` de períodos seguía `NOT NULL`,
columnas modernas de órdenes de compra ausentes) — se probaron las 5
contra un clon desechable (`BACKUP`/`RESTORE` real, sin `WITH COMPRESSION`
por ser SQL Server Express) antes de tocar la base real, siguiendo §2.7
de la guía de actualización. **Bug real cazado por la prueba en clon:**
`inv_20260824_proveedores_historicos_dbf.sql` crea `origen_datos` con
`ALTER TABLE ADD` y en el mismo lote intenta un `CREATE INDEX` sobre esa
columna — la resolución de nombres diferida de SQL Server no cubre este
caso, falla con "nombre de columna no válido". Corregido envolviendo esos
3 `CREATE INDEX` en `EXEC('...')` (fuerza resolución en tiempo de
ejecución); reprobado en el clon, aplicado a la base real, verificado
columna por columna. Clon y `.bak` de prueba eliminados al terminar.

**7. Menú, permisos, auditoría y MFA — verificados intactos, no
tocados.** Las rutas y entradas de menú nuevas (Vacaciones, Paz y Salvo,
Órdenes de Compra, Requisiciones) ya llegaban permisadas por rol desde
las migraciones de TH (`th_permisos_rol` real, no solo la tabla) y
enganchadas en `shared/menu.php`/`config/navigation.php` sin necesidad de
tocarlas a mano. MFA/TOTP de TH no se tocó (el conflicto de `Auth.php` no
alcanzaba esa zona). Verificado con `php -l` en el 100% del árbol de
ambos módulos y con curl real post-copia (login, rutas nuevas sin sesión
→ 302, assets nuevos → 200) — no solo "el script no dio error".

### v3.1 (2026-07-01) y anteriores

Ver historial de git para el detalle de las correcciones de exactitud contra
el código real (rutas, esquema, helpers) previas a esta reestructuración —
la mayoría de esos detalles quedaron superados por el cambio de arquitectura
de v4.0 y no se repiten acá.

---

*Portal APM v7.0 — Autoridad Portuaria de Manta*
