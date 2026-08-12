# Sistema central de menú/permisos — Fase 0 + Fase 1 (TH) — Plan de Implementación

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Hacer que `CORE_Modulos`/`CORE_Menu_Nodos`/`CORE_Permisos_Nodo` sean
el registro central de menú/permisos de TODO el portal (módulos nativos y
embebidos, actuales y futuros), con override por usuario, y sincronizar en
ambas direcciones con el RBAC propio de Talento Humano como primer módulo
piloto — sin romper nada de lo ya construido en TH ni en el portal.

**Arquitectura:** Dos tablas nuevas (`CORE_Modulos`, `CORE_Permisos_Nodo_Usuario`)
más una extensión de `fn_TienePermisoNodo` conforman la base reutilizable
(Fase 0). Fase 1 agrega `CORE_Roles_Modulo_Map` (mapeo de identidad de
roles TH↔Portal) y hooks de sincronización en los dos puntos donde HOY se
guardan permisos (`AdminController::guardarPermisos()` del portal y
`AdminModel::guardarPermisos()` de TH) — cada guardado escribe también del
otro lado, cross-DB, con auditoría de origen.

**Tech Stack:** PHP 8+ nativo (sin framework), SQL Server, `sqlsrv` (portal
nativo) y PDO (TH), sin test runner automatizado — verificación establecida
en este proyecto: `php -l`, queries SQL directas post-migración, y
navegador real (Playwright o Claude-in-Chrome) para UI/flujos.

## Global Constraints

- Nunca commitear sin confirmación explícita fresca del usuario en el chat, en cada ocasión.
- Toda migración SQL se aplica con backup previo de la BD afectada (`BACKUP DATABASE ... TO DISK` al `InstanceDefaultBackupPath` real del servidor) y se verifica con una query directa después — nunca confiar solo en "OK" del script.
- Migraciones SQL idempotentes: `IF OBJECT_ID(...) IS NULL` / `IF NOT EXISTS` / `CREATE OR ALTER`, mismo patrón que el resto de `db/*.sql` en este proyecto.
- CSRF en cada acción POST nueva: `$this->csrfToken()` (portal) o `Auth::csrfToken()`+`Auth::requireCsrf()` (TH) — mismos helpers ya usados en cada lado, no inventar uno nuevo.
- No tocar el modelo `nivel_crud` jerárquico (0-4) existente — decisión ya confirmada en `2026-07-29-permisos-checklist-design.md` y de nuevo en el spec de esta feature.
- Todo dato de prueba (usuarios, roles, filas de permiso) se crea, ejercita y borra en la misma sesión de trabajo — patrón ya establecido en este proyecto (ver `login_bloqueo_escalado.md`, `th_hr_module.md`).
- Interfaz nueva (`/admin/modulos`, pestaña de permisos individuales) reutiliza las convenciones visuales YA establecidas: variables CSS (`--card-bg`, `--color-primary`, `--sp-*`, `--radius-*`, `--font-size-*`), estructura `page-header` + tarjetas de stats + `<table>` en `.card`, iconos Font Awesome (`fa-solid fa-*`) — mismo patrón que `modules/Central/views/admin/departamentos.php`. No introducir un framework CSS ni un patrón visual nuevo.

---

## Descubrimiento importante durante la planificación (ajusta el alcance de Fase 1 frente al spec)

Leyendo el código real de TH (`apps/talento_humano/modules/admin/Modelos/AdminModel.php::guardarPermisos()`
y `apps/talento_humano/shared/menu.php`) se confirmaron dos cosas que el
spec no tenía y que simplifican/ajustan Fase 1:

1. **`th_permisos_rol` es granular por `codigo_modulo` (14 valores
   distintos hoy: `dashboard, directorio, empleados, acciones, prototipos,
   usuarios, roles, maestros, socioeconomico, biblioteca, auditoria,
   reportes, politicas, movimientos`), con exactamente 4 columnas
   booleanas — NO existe manera de darle a TH un permiso más fino que esos
   14×4. `Auth::can('acciones','editar')` es literalmente lo que decide si
   alguien puede aprobar/anular una Acción de Personal (confirmado en las
   `$routePolicies` de `apps/talento_humano/index.php`) — no hay una
   permiso "Aprobar" separado de "Editar" dentro de TH.** Por eso, para
   el piloto TH, **no se crean nodos de acción granular** — sería una
   casilla central que no tendría ningún efecto real distinto de "Editar"
   en TH (mala funcionalidad, exactamente lo que hay que evitar).
   Adicionalmente, revisando la UI YA construida de `/admin/menu/nuevo` se
   confirmó que el nivel L4 de MOIS ("Sub-ítem") ya se llama ahí
   **"Acción"** y ya se renderiza en el sidebar nativo como sub-enlace
   navegable (`<div class="sb-subitems">`) — es decir, la jerarquía MOIS
   YA tiene un concepto de "acción" para casos como "Nuevo registro"
   dentro de una pantalla. Es distinto de lo que se necesitaría para un
   permiso-de-botón sin URL propia (ej. "puede aprobar"), pero como TH no
   necesita esa granularidad de todas formas, **se descarta agregar una
   columna `es_accion` nueva en esta ronda** (YAGNI — no se construye
   schema sin un caso real que lo use). Si un módulo futuro necesita
   permisos de botón puros, se diseña esa columna en ESE momento, con un
   caso real que la valide.
2. **El sidebar de TH (`shared/menu.php`) YA lee el permiso real en cada
   render** (`<?php if(Auth::can('directorio','visualizar')): ?>` por cada
   ítem) — no hace falta tocar `menu.php` en absoluto. En cuanto el sync
   Central→TH escriba en `th_permisos_rol`, el sidebar ya construido
   refleja el cambio solo. Se elimina del alcance de este plan la tarea
   "reescribir el sidebar de TH" que sí estaba en el spec — quedó
   innecesaria, y no tocar código que ya funciona correctamente es mejor
   ingeniería que tocarlo sin necesidad.

Mapeo `codigo_modulo` (TH) ↔ nodo MOIS (Central) para Fase 1, confirmado
contra `th_modulos` real:

| `codigo_modulo` (TH) | Nodo MOIS central (`id_modulo=11`) |
|---|---|
| `dashboard` | Inicio |
| `directorio` | Directorio de Personal |
| `empleados` | Empleado: crear/editar/eliminar (usado por el formulario de ingreso) |
| `acciones` | Acción de Personal |
| `movimientos` | Movimientos internos |
| `socioeconomico` | Estudio Socioeconómico |
| `biblioteca` | Biblioteca de Formularios |
| `maestros` | Estructura y cargos (Admin > Maestros) |
| `usuarios` | Administración > Usuarios |
| `roles` | Administración > Roles y Permisos |
| `politicas` | Administración > Políticas |
| `auditoria` | Auditoría y Control |
| `reportes` | Reportes Generales |
| `prototipos` | Asistencia/Vacaciones/Desempeño/Capacitación (agrupados, igual que TH los agrupa) |

---

## Fase 0 — Base

### Task 1: Migración SQL — `CORE_Modulos` + `CORE_Permisos_Nodo_Usuario` + `fn_TienePermisoNodo` extendida

**Files:**
- Create: `db/permisos_centrales_fase0.sql`

**Interfaces:**
- Produces: tabla `PORTAL_APM.dbo.CORE_Modulos` (columnas: `id_modulo, codigo, nombre, icono, color, tipo, base_url, conexion_bd, orden, estado, fecha_creacion`), tabla `PORTAL_APM.dbo.CORE_Permisos_Nodo_Usuario` (columnas: `id_perm_usuario, id_usuario, id_modulo, opcion, items, subitems, nivel_crud, estado, fecha_asignacion, asignado_por`), función `dbo.fn_TienePermisoNodo` extendida (misma firma, nuevo comportamiento).

- [ ] **Step 1: Escribir el script de migración**

```sql
/* db/permisos_centrales_fase0.sql
   Fase 0 del sistema central de menú/permisos: registro de módulos +
   override de permiso por usuario individual. Idempotente. */
USE [PORTAL_APM];
GO
SET ANSI_NULLS ON;
SET QUOTED_IDENTIFIER ON;
GO

IF OBJECT_ID(N'dbo.CORE_Modulos', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.CORE_Modulos (
        id_modulo      TINYINT NOT NULL PRIMARY KEY,
        codigo         NVARCHAR(30)  NOT NULL,
        nombre         NVARCHAR(150) NOT NULL,
        icono          NVARCHAR(50)  NOT NULL CONSTRAINT DF_CORE_Modulos_icono DEFAULT ('fa-folder'),
        color          NVARCHAR(10)  NOT NULL CONSTRAINT DF_CORE_Modulos_color DEFAULT ('#6c757d'),
        tipo           NVARCHAR(20)  NOT NULL CONSTRAINT DF_CORE_Modulos_tipo DEFAULT ('nativo'),
        base_url       NVARCHAR(200) NULL,
        conexion_bd    NVARCHAR(50)  NULL,
        orden          SMALLINT NOT NULL CONSTRAINT DF_CORE_Modulos_orden DEFAULT (0),
        estado         TINYINT NOT NULL CONSTRAINT DF_CORE_Modulos_estado DEFAULT (1),
        fecha_creacion DATETIME2(3) NOT NULL CONSTRAINT DF_CORE_Modulos_fecha DEFAULT (SYSDATETIME()),
        CONSTRAINT UQ_CORE_Modulos_codigo UNIQUE (codigo),
        CONSTRAINT CK_CORE_Modulos_tipo CHECK (tipo IN ('nativo','embebido'))
    );
END;
GO

IF NOT EXISTS (SELECT 1 FROM dbo.CORE_Modulos)
BEGIN
    INSERT dbo.CORE_Modulos (id_modulo, codigo, nombre, icono, color, tipo, base_url, conexion_bd, orden) VALUES
    (1,  'PLANIFICACION', 'Dirección de Planificación Estratégica',  'fa-chart-gantt',    '#6f42c1', 'nativo',   NULL, NULL, 1),
    (2,  'TICS',          'Gestión de Tecnología de la Información', 'fa-server',         '#0056b3', 'nativo',   NULL, NULL, 2),
    (3,  'JURIDICA',      'Dirección de Asesoría Jurídica',          'fa-scale-balanced', '#dc3545', 'nativo',   NULL, NULL, 3),
    (4,  'INFRAESTRUCTURA','Dirección de Infraestructura Portuaria', 'fa-hard-hat',       '#fd7e14', 'nativo',   NULL, NULL, 4),
    (5,  'GARITA',        'Garita de Acceso / Control de Acceso',    'fa-door-open',      '#20c997', 'nativo',   NULL, NULL, 5),
    (6,  'OPERACIONES',   'Dirección de Operaciones',                'fa-ship',           '#17a2b8', 'nativo',   NULL, NULL, 6),
    (7,  'GERENCIA',      'Gerencia General',                        'fa-building',       '#343a40', 'nativo',   NULL, NULL, 7),
    (8,  'DELEGACION',    'Delegación de Servicios Portuarios',      'fa-landmark',       '#6f42c1', 'nativo',   NULL, NULL, 8),
    (9,  'ADMINISTRATIVA','Dirección Administrativa',                'fa-briefcase',      '#0056b3', 'nativo',   NULL, NULL, 9),
    (10, 'FINANCIERA',    'Dirección Financiera',                    'fa-wallet',         '#28a745', 'nativo',   NULL, NULL, 10),
    (11, 'TH',            'Dirección de Talento Humano',             'fa-users',          '#e83e8c', 'embebido', '/apps/talento_humano',  'talento',    11),
    (12, 'BIENES',        'Control de Bienes (Inventario)',          'fa-boxes-stacked',  '#fd7e14', 'embebido', '/apps/control_bienes',  'inventario', 12),
    (13, 'BITACORAS',     'Bitácoras Portuarias (CCTV/Visitas)',     'fa-anchor',         '#0891b2', 'embebido', '/apps/bitacoras',       'portuaria',  13);
END;
GO

IF OBJECT_ID(N'dbo.CORE_Permisos_Nodo_Usuario', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.CORE_Permisos_Nodo_Usuario (
        id_perm_usuario  INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
        id_usuario       INT NOT NULL,
        id_modulo        TINYINT NOT NULL,
        opcion           TINYINT NOT NULL,
        items            TINYINT NOT NULL,
        subitems         SMALLINT NOT NULL,
        nivel_crud       TINYINT NOT NULL,
        estado           TINYINT NOT NULL CONSTRAINT DF_CORE_PNU_estado DEFAULT (1),
        fecha_asignacion DATETIME2(3) NOT NULL CONSTRAINT DF_CORE_PNU_fecha DEFAULT (SYSDATETIME()),
        asignado_por     INT NULL,
        CONSTRAINT FK_CORE_PNU_usuario FOREIGN KEY (id_usuario) REFERENCES dbo.CORE_Usuarios(id_usuario),
        CONSTRAINT UQ_CORE_PNU UNIQUE (id_usuario, id_modulo, opcion, items, subitems),
        CONSTRAINT CK_CORE_PNU_nivel CHECK (nivel_crud BETWEEN 0 AND 4)
    );
END;
GO

CREATE OR ALTER FUNCTION [dbo].[fn_TienePermisoNodo](
    @id_usuario INT,
    @id_modulo  TINYINT,
    @opcion     TINYINT,
    @items      TINYINT,
    @subitems   SMALLINT,
    @nivel_min  TINYINT,
    @mfa_ok     BIT = 1
) RETURNS BIT AS
BEGIN
    DECLARE @nivelUsuario TINYINT = NULL;

    SELECT TOP 1 @nivelUsuario = pu.nivel_crud
    FROM dbo.CORE_Permisos_Nodo_Usuario pu
    WHERE pu.id_usuario = @id_usuario AND pu.estado = 1
      AND pu.id_modulo = @id_modulo AND pu.opcion = @opcion
      AND pu.items = @items AND pu.subitems = @subitems;

    IF @nivelUsuario IS NOT NULL
    BEGIN
        IF @nivelUsuario >= @nivel_min RETURN 1;
        RETURN 0;
    END;

    IF EXISTS (
        SELECT 1 FROM CORE_Usuarios u
        JOIN CORE_Usuarios_Roles ur ON ur.id_usuario=u.id_usuario AND ur.estado=1
        JOIN CORE_Roles r           ON r.id_rol=ur.id_rol AND r.estado=1
        JOIN CORE_Permisos_Nodo pn  ON pn.id_rol=r.id_rol AND pn.acceso=1 AND pn.estado=1
                                   AND pn.id_modulo=@id_modulo AND pn.opcion=@opcion
                                   AND pn.items=@items AND pn.subitems=@subitems
        JOIN CORE_Menu_Nodos mn     ON mn.id_modulo=pn.id_modulo AND mn.opcion=pn.opcion
                                   AND mn.items=pn.items AND mn.subitems=pn.subitems
                                   AND mn.estado=1
        WHERE u.id_usuario=@id_usuario AND u.estado=1
          AND pn.nivel_crud>=@nivel_min AND (mn.requiere_mfa=0 OR @mfa_ok=1)
    ) RETURN 1; RETURN 0;
END;
GO
```

- [ ] **Step 2: Correr `php -l` sobre el script** — no aplica (es SQL puro, no PHP); en su lugar, revisar visualmente que cada `GO` cierra un batch completo (mismo chequeo manual que el resto de `db/*.sql` de este proyecto).

- [ ] **Step 3: Backup de PORTAL_APM antes de aplicar**

```bash
php -r "
\$conn = require 'config/connections.php';
echo \$conn['server_default'];
"
```

Con el servidor real impreso, ejecutar (ajustando el server si difiere):

```sql
-- guardar como C:\Users\Usuario\.claude\jobs\<job>\tmp\backup_portal_fase0.sql
BACKUP DATABASE PORTAL_APM
TO DISK = '<InstanceDefaultBackupPath real del servidor>\PORTAL_APM_pre_permisos_fase0.bak'
WITH FORMAT, INIT, NAME = 'PORTAL_APM pre Fase 0 permisos centrales', STATS = 10;
```

Resolver `InstanceDefaultBackupPath` real con:
```sql
SELECT SERVERPROPERTY('InstanceDefaultBackupPath');
```
(mismo procedimiento ya usado para el backup de `Talento_Humano` el 2026-08-11 — ver `th_hr_module.md`).

Aplicar: `php db/run_sql.php <ruta_backup.sql> <servidor>`
Esperado: `OK: 1 batches ejecutados correctamente.`

- [ ] **Step 4: Aplicar la migración**

```bash
php db/run_sql.php db/permisos_centrales_fase0.sql <servidor>
```
Esperado: `OK: N batches ejecutados correctamente.` (sin errores).

- [ ] **Step 5: Verificar con queries directas (no confiar en el mensaje de éxito)**

```sql
SELECT COUNT(*) FROM dbo.CORE_Modulos; -- esperado: 13
SELECT id_modulo, codigo, tipo, base_url FROM dbo.CORE_Modulos ORDER BY id_modulo;
SELECT OBJECT_ID('dbo.CORE_Permisos_Nodo_Usuario','U'); -- esperado: NOT NULL
SELECT OBJECT_ID('dbo.fn_TienePermisoNodo','FN'); -- esperado: NOT NULL
```

- [ ] **Step 6: Probar `fn_TienePermisoNodo` extendida con un caso real de override**

Crear un usuario y un permiso de rol/usuario desechables, verificar el
comportamiento, y borrarlos en el mismo paso — mismo patrón que
`login_bloqueo_escalado.md`:

```sql
-- Insertar un permiso de rol NORMAL para el rol LECTOR (id=21) sobre un nodo real (ajustar a un nodo real existente, ej id_modulo=1,opcion=1,items=1,subitems=0)
-- luego insertar un override de usuario en CORE_Permisos_Nodo_Usuario con nivel_crud=0 (revoca)
-- SELECT dbo.fn_TienePermisoNodo(@id_usuario_prueba, 1,1,1,0, 1, 1) debe devolver 0 pese a que el rol sí da acceso
-- borrar la fila de override y el usuario de prueba
```

- [ ] **Step 7: Commit** (solo tras confirmación del usuario en el chat — no antes)

---

### Task 2: Registro compartido de módulos — reemplaza el array PHP duplicado

**Files:**
- Create: `core/CatalogoModulos.php`
- Modify: `modules/Central/controllers/MenuController.php:4-18` (borra `MODULES`, usa el catálogo)
- Modify: `modules/Central/controllers/AdminController.php:587-604` (borra `moduleMeta()`, usa el catálogo)

**Interfaces:**
- Produces: `CatalogoModulos::todos(): array` (array `id_modulo => ['codigo','nombre','icono','color','tipo','base_url','conexion_bd']`, cacheado en memoria de request), `CatalogoModulos::meta(int $idModulo): array` (con fallback `['nombre'=>"Módulo $id", 'icono'=>'fa-folder','color'=>'#6c757d']` si no existe, igual que el comportamiento actual).
- Consumes: `Database::getInstance()` (ya existente, mismo patrón que `MenuController::db()`).

- [ ] **Step 1: Crear `core/CatalogoModulos.php`**

```php
<?php
/**
 * CatalogoModulos — fuente única de la lista de módulos del portal
 * (CORE_Modulos), reemplaza los arrays PHP antes duplicados en
 * MenuController::MODULES y AdminController::moduleMeta().
 */
class CatalogoModulos {
    private static ?array $cache = null;

    public static function todos(): array {
        if (self::$cache !== null) return self::$cache;
        $db = Database::getInstance();
        $rows = $db->fetchAll($db->query(
            'SELECT id_modulo, codigo, nombre, icono, color, tipo, base_url, conexion_bd
             FROM CORE_Modulos WHERE estado=1 ORDER BY orden'
        ));
        $out = [];
        foreach ($rows as $r) {
            $out[(int)$r['id_modulo']] = [
                'label'       => $r['nombre'],
                'icon'        => $r['icono'],
                'color'       => $r['color'],
                'tipo'        => $r['tipo'],
                'base_url'    => $r['base_url'],
                'conexion_bd' => $r['conexion_bd'],
            ];
        }
        return self::$cache = $out;
    }

    public static function meta(int $idModulo): array {
        return self::todos()[$idModulo]
            ?? ['label' => "Módulo $idModulo", 'icon' => 'fa-folder', 'color' => '#6c757d', 'tipo' => 'nativo', 'base_url' => null, 'conexion_bd' => null];
    }
}
```

- [ ] **Step 2: `php -l core/CatalogoModulos.php`** — esperado: sin errores.

- [ ] **Step 3: Registrar el require en el bootstrap del portal**

Buscar dónde el portal nativo hace `require_once` de `core/*.php` (mismo
lugar que `core/ModuleSecurity.php`) y agregar `core/CatalogoModulos.php`
en esa misma lista.

- [ ] **Step 4: Reemplazar `MenuController::MODULES`**

En `modules/Central/controllers/MenuController.php`, borrar el array
`const MODULES = [...]` (líneas 4-18) y en `index()` (línea 74) cambiar
`'modules' => self::MODULES,` por `'modules' => CatalogoModulos::todos(),`.
En el mismo archivo, línea 49, cambiar
`self::MODULES[$mod] ?? ['label' => "Módulo $mod", ...]` por
`CatalogoModulos::meta($mod)`.

- [ ] **Step 5: Reemplazar `AdminController::moduleMeta()`**

En `modules/Central/controllers/AdminController.php`, borrar el método
privado `moduleMeta()` (líneas 587-604) y cambiar la única línea que lo
llama (línea 489, dentro de `rolPermisos()`) de
`$moduleMeta = $this->moduleMeta();` a
`$moduleMeta = CatalogoModulos::todos();` — el shape devuelto es idéntico
(`['label'=>...,'icon'=>...,'color'=>...]` por id_modulo), no requiere
tocar el resto de `rolPermisos()`.

- [ ] **Step 6: Verificación**

```bash
php -l modules/Central/controllers/MenuController.php
php -l modules/Central/controllers/AdminController.php
```

Navegador (sesión admin real): `/admin/menu` debe mostrar los mismos 13
módulos con los mismos íconos/colores que antes del cambio (comparar
captura antes/después). `/admin/roles/{id}/permisos` para cualquier rol
debe seguir mostrando los mismos módulos.

- [ ] **Step 7: Commit** (con confirmación del usuario)

---

### Task 3: UI `/admin/modulos` — alta de módulos sin tocar código

**Files:**
- Create: `modules/Central/controllers/ModuloController.php`
- Create: `modules/Central/views/admin/modulos.php`
- Create: `modules/Central/views/admin/modulo_form.php`
- Modify: `routes.php` (agregar rutas nuevas junto a las de `/admin/menu`, línea ~82-90)

**Interfaces:**
- Consumes: `CatalogoModulos` (Task 2), `Controller::requireLevel()`, `Controller::csrfToken()`/`verifyCsrf()`, `SessionHelper::flash()` — todos ya existentes, mismo patrón que `MenuController`.
- Produces: rutas `GET/POST /admin/modulos`, `/admin/modulos/nuevo`, `/admin/modulos/{id}/editar`, `/admin/modulos/{id}` (POST update), `/admin/modulos/{id}/toggle` (POST).

- [ ] **Step 1: Rutas** — agregar en `routes.php` junto al bloque de `/admin/menu`:

```php
$router->get('/admin/modulos',                  'ModuloController@index');
$router->get('/admin/modulos/nuevo',             'ModuloController@nuevo');
$router->post('/admin/modulos',                  'ModuloController@crear');
$router->get('/admin/modulos/{id}/editar',       'ModuloController@editar');
$router->post('/admin/modulos/{id}',             'ModuloController@actualizar');
$router->post('/admin/modulos/{id}/toggle',      'ModuloController@toggle');
```

- [ ] **Step 2: `ModuloController.php`**

```php
<?php
class ModuloController extends Controller {
    private const NODO_MODULOS = [1, 3, 0, 0]; // misma coordenada MOIS que NODO_MENU (Central > Administración > Estructura del Menú), reutiliza el mismo permiso de "quién administra el menú" — un módulo nuevo es una extensión de esa misma responsabilidad.

    private function db(): Database { return Database::getInstance(); }

    public function index(): void {
        $this->requireAuth();
        $this->requireLevel(3, [...self::NODO_MODULOS, 1]);
        $db = $this->db();
        $modulos = $db->fetchAll($db->query('SELECT * FROM CORE_Modulos ORDER BY orden'));
        $this->render('Central/admin/modulos', [
            'pageTitle' => 'Módulos del Portal',
            'modulos'   => $modulos,
            'total'     => count($modulos),
            'activos'   => count(array_filter($modulos, fn($m) => (int)$m['estado'] === 1)),
            'success'   => SessionHelper::getFlash('success'),
            'error'     => SessionHelper::getFlash('error'),
            'csrf'      => $this->csrfToken(),
        ]);
    }

    public function nuevo(): void {
        $this->requireAuth();
        $this->requireLevel(3, [...self::NODO_MODULOS, 2]);
        $db = $this->db();
        $maxId = (int)($db->fetch($db->query('SELECT MAX(id_modulo) AS m FROM CORE_Modulos'))['m'] ?? 0);
        $this->render('Central/admin/modulo_form', [
            'pageTitle'      => 'Nuevo Módulo',
            'modulo'         => null,
            'siguienteId'    => $maxId + 1,
            'errors'         => $_SESSION['_form_errors'] ?? [],
            'oldInput'       => $_SESSION['_old_input'] ?? [],
            'csrf'           => $this->csrfToken(),
        ]);
        unset($_SESSION['_form_errors'], $_SESSION['_old_input']);
    }

    public function crear(): void {
        $this->requireAuth();
        $this->requireLevel(3, [...self::NODO_MODULOS, 2]);
        $this->verifyCsrf();

        if (!FormHelper::validate($_POST, [
            'id_modulo' => 'required|numeric',
            'codigo'    => 'required|min:2|max:30',
            'nombre'    => 'required|min:3|max:150',
            'tipo'      => 'required|in:nativo,embebido',
        ])) {
            $_SESSION['_form_errors'] = FormHelper::errors();
            $_SESSION['_old_input']   = $_POST;
            $this->redirect('/admin/modulos/nuevo');
        }

        $db = $this->db();
        $existe = $db->fetch($db->query(
            'SELECT id_modulo FROM CORE_Modulos WHERE id_modulo=? OR codigo=?',
            [[(int)$_POST['id_modulo'], SQLSRV_PARAM_IN], [trim($_POST['codigo']), SQLSRV_PARAM_IN]]
        ));
        if ($existe) {
            $_SESSION['_form_errors'] = ['Ya existe un módulo con ese ID o código.'];
            $_SESSION['_old_input']   = $_POST;
            $this->redirect('/admin/modulos/nuevo');
        }

        $db->query(
            'INSERT INTO CORE_Modulos (id_modulo, codigo, nombre, icono, color, tipo, base_url, conexion_bd, orden, estado)
             VALUES (?,?,?,?,?,?,?,?,?,1)',
            [
                [(int)$_POST['id_modulo'],                        SQLSRV_PARAM_IN],
                [strtoupper(trim($_POST['codigo'])),              SQLSRV_PARAM_IN],
                [trim($_POST['nombre']),                          SQLSRV_PARAM_IN],
                [trim($_POST['icono'] ?? '') ?: 'fa-folder',      SQLSRV_PARAM_IN],
                [trim($_POST['color'] ?? '') ?: '#6c757d',        SQLSRV_PARAM_IN],
                [$_POST['tipo'],                                  SQLSRV_PARAM_IN],
                [trim($_POST['base_url'] ?? '') ?: null,          SQLSRV_PARAM_IN],
                [trim($_POST['conexion_bd'] ?? '') ?: null,       SQLSRV_PARAM_IN],
                [(int)($_POST['orden'] ?? 0),                     SQLSRV_PARAM_IN],
            ]
        );

        ModuleSecurity::audit('CORE', 'CREAR', 'CORE_Modulos', $_POST['id_modulo'], null, $_POST, 'EXITO', 'Alta de módulo desde /admin/modulos');
        SessionHelper::flash('success', 'Módulo creado. Ya está disponible en Estructura del Menú y Roles y Permisos.');
        $this->redirect('/admin/modulos');
    }

    public function editar(int $id): void {
        $this->requireAuth();
        $this->requireLevel(3, [...self::NODO_MODULOS, 1]);
        $db = $this->db();
        $modulo = $db->fetch($db->query('SELECT * FROM CORE_Modulos WHERE id_modulo=?', [[$id, SQLSRV_PARAM_IN]]));
        if (!$modulo) { http_response_code(404); exit; }
        $this->render('Central/admin/modulo_form', [
            'pageTitle' => 'Editar Módulo',
            'modulo'    => $modulo,
            'errors'    => $_SESSION['_form_errors'] ?? [],
            'oldInput'  => $_SESSION['_old_input'] ?? [],
            'csrf'      => $this->csrfToken(),
        ]);
        unset($_SESSION['_form_errors'], $_SESSION['_old_input']);
    }

    public function actualizar(int $id): void {
        $this->requireAuth();
        $this->requireLevel(3, [...self::NODO_MODULOS, 3]);
        $this->verifyCsrf();
        $db = $this->db();
        $db->query(
            'UPDATE CORE_Modulos SET nombre=?, icono=?, color=?, tipo=?, base_url=?, conexion_bd=?, orden=? WHERE id_modulo=?',
            [
                [trim($_POST['nombre']),                     SQLSRV_PARAM_IN],
                [trim($_POST['icono'] ?? '') ?: 'fa-folder', SQLSRV_PARAM_IN],
                [trim($_POST['color'] ?? '') ?: '#6c757d',   SQLSRV_PARAM_IN],
                [$_POST['tipo'],                             SQLSRV_PARAM_IN],
                [trim($_POST['base_url'] ?? '') ?: null,     SQLSRV_PARAM_IN],
                [trim($_POST['conexion_bd'] ?? '') ?: null,  SQLSRV_PARAM_IN],
                [(int)($_POST['orden'] ?? 0),                SQLSRV_PARAM_IN],
                [$id,                                        SQLSRV_PARAM_IN],
            ]
        );
        ModuleSecurity::audit('CORE', 'ACTUALIZAR', 'CORE_Modulos', (string)$id, null, $_POST, 'EXITO', null);
        SessionHelper::flash('success', 'Módulo actualizado.');
        $this->redirect('/admin/modulos');
    }

    public function toggle(int $id): void {
        $this->requireAuth();
        $this->requireLevel(3, [...self::NODO_MODULOS, 3]);
        $this->verifyCsrf();
        $db = $this->db();
        $modulo = $db->fetch($db->query('SELECT estado FROM CORE_Modulos WHERE id_modulo=?', [[$id, SQLSRV_PARAM_IN]]));
        if (!$modulo) { $this->json(['ok' => false], 404); return; }
        $nuevo = $modulo['estado'] ? 0 : 1;
        $db->query('UPDATE CORE_Modulos SET estado=? WHERE id_modulo=?', [[$nuevo, SQLSRV_PARAM_IN], [$id, SQLSRV_PARAM_IN]]);
        $this->json(['ok' => true, 'estado' => $nuevo]);
    }
}
```

- [ ] **Step 3: `php -l modules/Central/controllers/ModuloController.php`**

- [ ] **Step 4: Vista `Central/admin/modulos.php`** — mismo patrón visual que
`Central/admin/departamentos.php` (page-header + 2 tarjetas de stats +
tabla en `.card`): columnas Código/Nombre/Tipo/Base URL/Estado/Acciones,
botón "Nuevo Módulo" en el header, toggle de estado vía fetch POST (mismo
patrón `guardarInactividadYRecargar`-style ya usado en
`modules/Central/views/admin/inactividad.php`).

- [ ] **Step 5: Vista `Central/admin/modulo_form.php`** — formulario simple:
`id_modulo` (solo editable en alta, readonly en edición), `codigo`,
`nombre`, `icono` (input texto con preview del ícono FA en vivo vía JS),
`color` (input `type=color`), `tipo` (select nativo/embebido), `base_url`,
`conexion_bd`, `orden`. Mismo patrón de card+form que
`Central/admin/departamento_form.php`.

- [ ] **Step 6: Verificación en navegador** (sesión admin real): crear un
módulo de prueba (`id_modulo=99, codigo='PRUEBA'`), confirmar que aparece
en `/admin/menu` y en `/admin/roles/{id}/permisos` sin recargar código,
editarlo, desactivarlo, y luego borrarlo directamente por SQL (no hay UI
de borrado — igual que `CORE_Menu_Nodos`, un módulo con nodos/permisos
asignados no debería poder eliminarse desde la UI; el desechable de prueba
se limpia por SQL directo ya que nunca tuvo nodos reales).

- [ ] **Step 7: Commit** (con confirmación del usuario)

---

### Task 4: Permisos individuales por usuario — pestaña en `/admin/usuarios/{id}/editar`

**Files:**
- Modify: `modules/Central/controllers/AdminController.php` (método `editarUsuario()`, líneas 40-61 — agregar datos de permisos individuales; nuevo método `guardarPermisosUsuario()`)
- Modify: `modules/Central/views/admin/usuario_form.php` (agregar pestaña/sección)
- Modify: `routes.php` (nueva ruta POST)

**Interfaces:**
- Consumes: mismo `$tree` que ya arma `AdminController::rolPermisos()` (líneas 470-524) — se extrae esa construcción de árbol a un método privado reutilizable `construirArbolPermisos(array $permisosMap): array` para no duplicar la lógica entre `rolPermisos()` y esta pantalla nueva.
- Produces: ruta `POST /admin/usuarios/{id}/permisos`.

- [ ] **Step 1: Extraer `construirArbolPermisos()` en `AdminController.php`**

Refactor sin cambio de comportamiento: mover el bloque `foreach ($nodos as $n) { ... }` de `rolPermisos()` (líneas 491-523) a un método privado:

```php
private function construirArbolPermisos(array $nodos, array $permisosMap): array {
    $moduleMeta = CatalogoModulos::todos();
    $tree = [];
    foreach ($nodos as $n) {
        $mod = (int)$n['id_modulo']; $op = (int)$n['opcion']; $it = (int)$n['items']; $sub = (int)$n['subitems'];
        $key = "{$mod}-{$op}-{$it}-{$sub}";
        $n['key'] = $key; $n['permiso'] = $permisosMap[$key] ?? 0;
        if (!isset($tree[$mod])) {
            $meta = $moduleMeta[$mod] ?? ['label' => "Módulo $mod", 'icon' => 'fa-folder', 'color' => '#6c757d'];
            $tree[$mod] = ['label' => $meta['label'], 'icon' => $meta['icon'], 'color' => $meta['color'], 'raiz' => null, 'areas' => []];
        }
        if ($op === 0) { $tree[$mod]['raiz'] = $n; }
        elseif ($it === 0) { $tree[$mod]['areas'][$op] = ['nodo' => $n, 'items' => []]; }
        elseif ($sub === 0) {
            if (!isset($tree[$mod]['areas'][$op])) $tree[$mod]['areas'][$op] = ['nodo' => null, 'items' => []];
            $tree[$mod]['areas'][$op]['items'][$it] = ['nodo' => $n, 'subitems' => []];
        } else {
            if (!isset($tree[$mod]['areas'][$op])) $tree[$mod]['areas'][$op] = ['nodo' => null, 'items' => []];
            if (!isset($tree[$mod]['areas'][$op]['items'][$it])) $tree[$mod]['areas'][$op]['items'][$it] = ['nodo' => null, 'subitems' => []];
            $tree[$mod]['areas'][$op]['items'][$it]['subitems'][] = $n;
        }
    }
    return $tree;
}
```

En `rolPermisos()`, reemplazar el bloque original por:
`$tree = $this->construirArbolPermisos($nodos, $permisosMap);`

- [ ] **Step 2: `php -l modules/Central/controllers/AdminController.php`** tras el refactor — confirmar que `/admin/roles/{id}/permisos` sigue funcionando exactamente igual (verificación en navegador, no solo lint).

- [ ] **Step 3: Extender `editarUsuario()`** para calcular, además de lo que ya arma, el permiso EFECTIVO del usuario (rol + override) y sus overrides propios:

```php
$nodos = $db->fetchAll($db->query(
    'SELECT id_nodo, id_modulo, opcion, items, subitems, descripcion, url_ruta, icono
     FROM CORE_Menu_Nodos WHERE estado=1 ORDER BY id_modulo, opcion, items, subitems'
));
$overrides = $db->fetchAll($db->query(
    'SELECT id_modulo, opcion, items, subitems, nivel_crud FROM CORE_Permisos_Nodo_Usuario WHERE id_usuario=? AND estado=1',
    [[$id, SQLSRV_PARAM_IN]]
));
$overridesMap = [];
foreach ($overrides as $o) { $overridesMap["{$o['id_modulo']}-{$o['opcion']}-{$o['items']}-{$o['subitems']}"] = (int)$o['nivel_crud']; }
$treePermisosUsuario = $this->construirArbolPermisos($nodos, $overridesMap);
```

Agregar `'treePermisosUsuario' => $treePermisosUsuario,` al `render()` de `editarUsuario()`.

- [ ] **Step 4: Nuevo método `guardarPermisosUsuario(int $id)`**

```php
public function guardarPermisosUsuario(int $id): void {
    $this->requireAuth();
    $this->requireLevel(4, [...self::NODO_USUARIOS, 4]);
    $this->verifyCsrf();

    $db = $this->db();
    $antes = $db->fetchAll($db->query(
        'SELECT id_modulo, opcion, items, subitems, nivel_crud FROM CORE_Permisos_Nodo_Usuario WHERE id_usuario=?',
        [[$id, SQLSRV_PARAM_IN]]
    ));
    $db->query('DELETE FROM CORE_Permisos_Nodo_Usuario WHERE id_usuario=?', [[$id, SQLSRV_PARAM_IN]]);

    $despues = [];
    foreach ($_POST['overrides'] ?? [] as $key => $nivel) {
        $nivel = (int)$nivel;
        if ($nivel < 0 || $nivel > 4) continue; // 0 permitido: revoca explícitamente
        $parts = explode('-', (string)$key);
        if (count($parts) !== 4) continue;
        [$mod, $op, $it, $sub] = array_map('intval', $parts);
        $db->query(
            'INSERT INTO CORE_Permisos_Nodo_Usuario (id_usuario, id_modulo, opcion, items, subitems, nivel_crud, estado, asignado_por)
             VALUES (?,?,?,?,?,?,1,?)',
            [
                [$id, SQLSRV_PARAM_IN], [$mod, SQLSRV_PARAM_IN], [$op, SQLSRV_PARAM_IN],
                [$it, SQLSRV_PARAM_IN], [$sub, SQLSRV_PARAM_IN], [$nivel, SQLSRV_PARAM_IN],
                [(int)($_SESSION['user_id'] ?? 0), SQLSRV_PARAM_IN],
            ]
        );
        $despues[(string)$key] = $nivel;
    }

    ModuleSecurity::audit('CORE', 'ACTUALIZAR', 'CORE_Permisos_Nodo_Usuario', (string)$id, $antes ?: null, $despues ?: null, 'EXITO', 'Override de permisos por usuario individual');

    if (View::isAjax()) { $this->json(['ok' => true, 'msg' => 'Permisos individuales guardados.']); return; }
    SessionHelper::flash('success', 'Permisos individuales guardados.');
    $this->redirect('/admin/usuarios/' . $id . '/editar');
}
```

- [ ] **Step 5: Ruta** — agregar en `routes.php` junto a las de `/admin/usuarios/{id}`:

```php
$router->post('/admin/usuarios/{id}/permisos', 'AdminController@guardarPermisosUsuario');
```

- [ ] **Step 6: Vista** — en `modules/Central/views/admin/usuario_form.php`,
agregar una sección/pestaña "Permisos individuales" reutilizando el MISMO
componente de tabla-checklist de `rol_permisos.php` (spec `2026-07-29`),
recibiendo `$treePermisosUsuario` en vez de `$tree`, con un badge
"Excepción" en cada fila donde `$nodo['permiso'] > 0` (viene de un
override real, no del rol) — comparar contra el nivel que le daría SOLO
el rol (se puede calcular aparte con `CORE_Permisos_Nodo` para mostrar
"rol da: Ver, aquí: Editar" como ayuda visual). Form submit hacia
`POST /admin/usuarios/{id}/permisos`, mismo botón único "Guardar" que
`rol_permisos.php`.

- [ ] **Step 7: Verificación en navegador** — con un usuario de prueba
desechable: asignarle un rol de solo lectura, confirmar que NO puede
editar un nodo; agregarle un override `nivel_crud=3` en ESE nodo desde
`/admin/usuarios/{id}/editar`, confirmar (login como ese usuario, o vía
`fn_TienePermisoNodo` en SQL directo) que ahora sí puede; borrar el
override, confirmar que vuelve a como estaba el rol; borrar el usuario de
prueba.

- [ ] **Step 8: Commit** (con confirmación del usuario)

---

## Fase 1 — Piloto Talento Humano

### Task 5: Migración SQL — `CORE_Roles_Modulo_Map`, árbol real de TH

**Files:**
- Create: `db/permisos_centrales_fase1_th.sql`

**Interfaces:**
- Produces: tabla `CORE_Roles_Modulo_Map`, 14 nodos reales de `id_modulo=11` (reemplazan los 3 nodos-esqueleto), 4 filas de mapeo TH↔Portal. (`es_accion` descartado — ver "Descubrimiento importante" arriba.)

- [ ] **Step 1: Consultar primero los 3 nodos-esqueleto reales de TH a reemplazar**

```sql
SELECT id_nodo, opcion, items, subitems, descripcion FROM PORTAL_APM.dbo.CORE_Menu_Nodos WHERE id_modulo=11;
```
(anotar sus `id_nodo` reales — el script de migración los referencia por
coordenada MOIS, no por `id_nodo`, así que este paso es solo para
confirmar qué se va a reemplazar antes de correrlo.)

- [ ] **Step 2: Escribir el script**

```sql
/* db/permisos_centrales_fase1_th.sql
   Fase 1: piloto Talento Humano. Idempotente. */
USE [PORTAL_APM];
GO
SET ANSI_NULLS ON;
SET QUOTED_IDENTIFIER ON;
GO

IF OBJECT_ID(N'dbo.CORE_Roles_Modulo_Map', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.CORE_Roles_Modulo_Map (
        id_map         INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
        id_modulo      TINYINT NOT NULL,
        id_rol_portal  INT NOT NULL,
        id_rol_externo INT NOT NULL,
        CONSTRAINT FK_CRMM_rol_portal FOREIGN KEY (id_rol_portal) REFERENCES dbo.CORE_Roles(id_rol),
        CONSTRAINT FK_CRMM_modulo FOREIGN KEY (id_modulo) REFERENCES dbo.CORE_Modulos(id_modulo),
        CONSTRAINT UQ_CRMM_portal UNIQUE (id_modulo, id_rol_portal),
        CONSTRAINT UQ_CRMM_externo UNIQUE (id_modulo, id_rol_externo)
    );
END;
GO

IF NOT EXISTS (SELECT 1 FROM dbo.CORE_Roles_Modulo_Map WHERE id_modulo = 11)
BEGIN
    INSERT dbo.CORE_Roles_Modulo_Map (id_modulo, id_rol_portal, id_rol_externo) VALUES
    (11, 1,  1), -- ADMIN <-> Super Administrador
    (11, 11, 2), -- DIR_TH <-> Director de Talento Humano
    (11, 12, 3), -- ANALISTA_TH <-> Analista de Nómina
    (11, 21, 4); -- LECTOR <-> Funcionario (Lectura)
END;
GO

-- Retirar los 3 nodos-esqueleto de TH antes de sembrar el árbol real
-- (solo si no tienen permisos de rol asignados aún -- si los tuvieran,
-- CORE_Permisos_Nodo quedaría huérfano; se verifica antes con el SELECT
-- del Step 1 de este task, y este DELETE solo corre la primera vez que
-- se aplica esta migración, protegido por el IF).
IF EXISTS (SELECT 1 FROM dbo.CORE_Menu_Nodos WHERE id_modulo=11 AND opcion<=3)
   AND NOT EXISTS (SELECT 1 FROM dbo.CORE_Menu_Nodos WHERE id_modulo=11 AND descripcion='Inicio')
BEGIN
    DELETE pn FROM dbo.CORE_Permisos_Nodo pn
        JOIN dbo.CORE_Menu_Nodos mn ON mn.id_modulo=pn.id_modulo AND mn.opcion=pn.opcion AND mn.items=pn.items AND mn.subitems=pn.subitems
        WHERE mn.id_modulo=11;
    DELETE FROM dbo.CORE_Menu_Nodos WHERE id_modulo=11;
END;
GO

IF NOT EXISTS (SELECT 1 FROM dbo.CORE_Menu_Nodos WHERE id_modulo=11 AND descripcion='Inicio')
BEGIN
    INSERT dbo.CORE_Menu_Nodos (id_modulo, opcion, items, subitems, descripcion, url_ruta, icono, orden, requiere_mfa, target_spa, estado) VALUES
    (11, 0, 0, 0, 'Talento Humano',                'apps/talento_humano/',                         'fa-users',              0,  0, 0, 1),
    (11, 1, 0, 0, 'Inicio',                        'apps/talento_humano/talento-humano/inicio',    'fa-house',              1,  0, 0, 1),
    (11, 2, 0, 0, 'Directorio de Personal',        'apps/talento_humano/talento-humano/directorio','fa-address-book',       2,  0, 0, 1),
    (11, 3, 0, 0, 'Formulario de Ingreso',         'apps/talento_humano/talento-humano/empleado/crear', 'fa-user-plus',     3,  0, 0, 1),
    (11, 4, 0, 0, 'Acción de Personal',            'apps/talento_humano/talento-humano/accion-personal','fa-file-signature',4,  0, 0, 1),
    (11, 5, 0, 0, 'Movimientos internos',          'apps/talento_humano/talento-humano/directorio?modo=movimiento','fa-arrows-turn-right', 5, 0, 0, 1),
    (11, 6, 0, 0, 'Estudio Socioeconómico',        'apps/talento_humano/talento-humano/estudio-seguridad','fa-house-chimney-user', 6, 0, 0, 1),
    (11, 7, 0, 0, 'Biblioteca de Formularios',     'apps/talento_humano/talento-humano/biblioteca','fa-book',               7,  0, 0, 1),
    (11, 8, 0, 0, 'Estructura y cargos',           'apps/talento_humano/admin/maestros',           'fa-sitemap',            8,  0, 0, 1),
    (11, 9, 0, 0, 'Administración de Usuarios',    'apps/talento_humano/admin/usuarios',           'fa-user-gear',          9,  1, 0, 1),
    (11, 10,0, 0, 'Roles y Permisos',              'apps/talento_humano/admin/roles',              'fa-shield-halved',      10, 1, 0, 1),
    (11, 11,0, 0, 'Políticas y Normativas',        'apps/talento_humano/admin/politicas',          'fa-scroll',             11, 0, 0, 1),
    (11, 12,0, 0, 'Auditoría y Control',           'apps/talento_humano/auditoria/logs',           'fa-clipboard-list',     12, 1, 0, 1),
    (11, 13,0, 0, 'Reportes Generales',            'apps/talento_humano/reportes',                 'fa-chart-column',       13, 0, 0, 1),
    (11, 14,0, 0, 'Prototipos (Asistencia/Vacaciones/Desempeño/Capacitación)', NULL, 'fa-flask',    14, 0, 0, 1);
END;
GO
```

- [ ] **Step 3: Backup de PORTAL_APM** (mismo procedimiento que Task 1, Step 3 — repetir el backup, no reusar el de Fase 0 porque el estado de la BD cambió entre medio).

- [ ] **Step 4: Aplicar**

```bash
php db/run_sql.php db/permisos_centrales_fase1_th.sql <servidor>
```

- [ ] **Step 5: Verificar con queries directas**

```sql
SELECT COUNT(*) FROM dbo.CORE_Menu_Nodos WHERE id_modulo=11; -- esperado: 15 (1 raíz + 14 opciones)
SELECT COUNT(*) FROM dbo.CORE_Roles_Modulo_Map WHERE id_modulo=11; -- esperado: 4
```

Confirmar en navegador: `/admin/menu` muestra el árbol real de TH (14
opciones bajo "Talento Humano", ya no los 3 nodos-esqueleto).
`/admin/roles/{id}/permisos` para el rol DIR_TH (`id_rol=11`) muestra esas
mismas 14 filas bajo Talento Humano.

- [ ] **Step 6: Commit** (con confirmación del usuario)

---

### Task 6: Sync Central → TH

**Files:**
- Modify: `modules/Central/controllers/AdminController.php::guardarPermisos()` (líneas 534-585)
- Create: `core/SyncPermisosModulo.php`

**Interfaces:**
- Produces: `SyncPermisosModulo::centralHaciaTh(int $idRolPortal, array $cambiosPorNodo): void` — `$cambiosPorNodo` es `['mod-op-it-sub' => nivel_crud, ...]`, mismo shape que `$despuesMap` que `guardarPermisos()` ya construye.
- Consumes: `config/connections.php` (clave `databases.talento`, ya existente), mismo patrón de conexión cross-DB con `sqlsrv_connect` + 3-part naming ya usado en el proyecto (ver `apps/control_bienes/modules/Credenciales/controllers/AuthController.php::obtenerUsuarioPortal()` como referencia de estilo).

- [ ] **Step 1: Crear `core/SyncPermisosModulo.php`**

```php
<?php
/**
 * SyncPermisosModulo — sincroniza CORE_Permisos_Nodo con el RBAC propio
 * de un módulo embebido que tenga mapeo en CORE_Roles_Modulo_Map. Fase 1:
 * solo Talento Humano (id_modulo=11) tiene contraparte real que sincronizar.
 */
class SyncPermisosModulo {
    /** Nodo MOIS (opcion, sin items/subitems) -> codigo_modulo real de th_modulos. */
    private const NODOS_TH = [
        1  => 'dashboard',
        2  => 'directorio',
        3  => 'empleados',
        4  => 'acciones',
        5  => 'movimientos',
        6  => 'socioeconomico',
        7  => 'biblioteca',
        8  => 'maestros',
        9  => 'usuarios',
        10 => 'roles',
        11 => 'politicas',
        12 => 'auditoria',
        13 => 'reportes',
        14 => 'prototipos',
    ];

    public static function centralHaciaTh(int $idRolPortal, array $cambiosPorNodo): void {
        $mapa = self::mapaRolTh($idRolPortal);
        if ($mapa === null) return; // rol sin contraparte en TH, nada que sincronizar

        $conn = require dirname(__DIR__) . '/config/connections.php';
        $opts = ['CharacterSet' => 'UTF-8', 'TrustServerCertificate' => (bool)($conn['options']['trust_cert'] ?? true)];
        if (!empty($conn['credentials']['user'])) {
            $opts['UID'] = $conn['credentials']['user'];
            $opts['PWD'] = $conn['credentials']['pass'];
        }
        $opts['Database'] = $conn['databases']['talento']['name'];
        $c = @sqlsrv_connect($conn['databases']['talento']['server'] ?? $conn['server_default'], $opts);
        if ($c === false) { self::registrarFalloSync('CENTRAL_A_TH', $idRolPortal, sqlsrv_errors()); return; }

        foreach ($cambiosPorNodo as $key => $nivel) {
            [$mod, $op, $it, $sub] = array_map('intval', explode('-', (string)$key));
            if ($mod !== 11 || $it !== 0 || $sub !== 0) continue; // solo nodos raíz de opción, único nivel que TH puede reflejar
            $codigoModulo = self::NODOS_TH[$op] ?? null;
            if ($codigoModulo === null) continue;

            $puedeV = $nivel >= 1 ? 1 : 0; $puedeC = $nivel >= 2 ? 1 : 0;
            $puedeE = $nivel >= 3 ? 1 : 0; $puedeD = $nivel >= 4 ? 1 : 0;

            sqlsrv_query($c,
                'UPDATE p SET p.puede_visualizar=?, p.puede_crear=?, p.puede_editar=?, p.puede_eliminar=?
                 FROM dbo.th_permisos_rol p JOIN dbo.th_modulos m ON m.modulo_id=p.modulo_id
                 WHERE p.rol_id=? AND m.codigo_modulo=?',
                [$puedeV, $puedeC, $puedeE, $puedeD, $mapa, $codigoModulo]
            );
            sqlsrv_query($c,
                "EXEC dbo.sp_th_registrar_auditoria ?, 'Sistema', 'SYNC_PERMISO_DESDE_PORTAL', ?, '127.0.0.1'",
                ['CENTRAL', "Nivel {$nivel} aplicado a {$codigoModulo} (rol_id={$mapa}) desde /admin/roles/{$idRolPortal}/permisos."]
            );
        }
        sqlsrv_close($c);
    }

    private static function mapaRolTh(int $idRolPortal): ?int {
        $db = Database::getInstance();
        $row = $db->fetch($db->query(
            'SELECT id_rol_externo FROM CORE_Roles_Modulo_Map WHERE id_modulo=11 AND id_rol_portal=?',
            [[$idRolPortal, SQLSRV_PARAM_IN]]
        ));
        return $row ? (int)$row['id_rol_externo'] : null;
    }

    private static function registrarFalloSync(string $direccion, int $idRol, $errores): void {
        ModuleSecurity::audit('CORE', 'SYNC_FALLO', 'CORE_Roles_Modulo_Map', (string)$idRol, null, null, 'FALLO',
            "$direccion: no se pudo conectar al módulo destino. " . json_encode($errores));
    }
}
```

- [ ] **Step 2: `php -l core/SyncPermisosModulo.php`**

- [ ] **Step 3: Enganchar en `guardarPermisos()`**

En `modules/Central/controllers/AdminController.php`, después de la línea
`ModuleSecurity::audit(...)` (línea 577) y antes del `if (View::isAjax())`
(línea 579), agregar:

```php
SyncPermisosModulo::centralHaciaTh($id, $despuesMap);
```

- [ ] **Step 4: Registrar el require** en el bootstrap del portal (mismo
lugar que `core/CatalogoModulos.php`, Task 2 Step 3).

- [ ] **Step 5: Verificación real (no solo lint)**

Como admin del portal: entrar a `/admin/roles/12/permisos` (ANALISTA_TH),
cambiar el nivel de "Acción de Personal" a 3 (Editar), guardar. Verificar
con query directa:

```sql
SELECT p.puede_visualizar, p.puede_crear, p.puede_editar, p.puede_eliminar
FROM Talento_Humano.dbo.th_permisos_rol p
JOIN Talento_Humano.dbo.th_modulos m ON m.modulo_id=p.modulo_id
WHERE p.rol_id=3 AND m.codigo_modulo='acciones';
-- esperado: 1,1,1,0
```

Confirmar también en `Talento_Humano.dbo.th_logs_auditoria` que aparece
la fila `SYNC_PERMISO_DESDE_PORTAL`. Revertir el cambio de prueba al valor
original si no era el deseado.

- [ ] **Step 6: Commit** (con confirmación del usuario)

---

### Task 7: Sync TH → Central

**Files:**
- Modify: `apps/talento_humano/modules/admin/Modelos/AdminModel.php::guardarPermisos()` (líneas 133-152)

**Interfaces:**
- Consumes: `config/connections.php` desde TH (mismo patrón ya usado en `apps/talento_humano/core/Config.php` tras la integración del 2026-08-11 — ver `th_hr_module.md`), `CORE_Roles_Modulo_Map` (Task 5).

- [ ] **Step 1: Localizar el mapeo inverso** (rol TH → rol portal) — agregar
método privado en `AdminModel.php`:

```php
private function rolPortalDesdeTh(int $rolIdTh): ?int {
    $conn = require dirname(__DIR__, 4) . '/config/connections.php';
    $opts = ['CharacterSet' => 'UTF-8', 'TrustServerCertificate' => (bool)($conn['options']['trust_cert'] ?? true)];
    if (!empty($conn['credentials']['user'])) { $opts['UID'] = $conn['credentials']['user']; $opts['PWD'] = $conn['credentials']['pass']; }
    $opts['Database'] = $conn['databases']['portal']['name'];
    $c = @sqlsrv_connect($conn['databases']['portal']['server'] ?? $conn['server_default'], $opts);
    if ($c === false) return null;
    $stmt = sqlsrv_query($c, 'SELECT id_rol_portal FROM dbo.CORE_Roles_Modulo_Map WHERE id_modulo=11 AND id_rol_externo=?', [$rolIdTh]);
    $row = $stmt ? sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC) : false;
    sqlsrv_close($c);
    return $row ? (int)$row['id_rol_portal'] : null;
}
```

(`dirname(__DIR__, 4)` desde `modules/admin/Modelos/AdminModel.php` sube a
la raíz de `apps/talento_humano`, y de ahí dos niveles más para
`portal_apm/` — verificar el conteo real de niveles al implementar, mismo
cálculo ya usado y verificado en `core/Config.php` de TH este mismo día.)

- [ ] **Step 2: Modificar `guardarPermisos()`** — agregar el sync DESPUÉS
del `$this->db->commit();` (línea 145), fuera de la transacción PDO local
(la conexión cross-DB es una conexión sqlsrv distinta, no participa de la
misma transacción):

```php
public function guardarPermisos(int $rolId, array $matriz): array
{
    if ($rolId===1) return ['exito'=>0,'mensaje'=>'El rol Super Administrador mantiene acceso total.'];
    $this->db->beginTransaction();
    try {
        $stmt=$this->db->prepare('UPDATE dbo.th_permisos_rol SET puede_visualizar=:v,puede_crear=:c,puede_editar=:e,puede_eliminar=:d WHERE rol_id=:rol AND modulo_id=:modulo');
        $cambios = [];
        foreach ($this->db->query('SELECT modulo_id, codigo_modulo FROM dbo.th_modulos')->fetchAll(PDO::FETCH_ASSOC) as $m) {
            $moduloId = (int)$m['modulo_id'];
            $p=$matriz[(string)$moduloId]??[];
            $v = isset($p['visualizar'])?1:0; $c = isset($p['crear'])?1:0;
            $e = isset($p['editar'])?1:0;     $d = isset($p['eliminar'])?1:0;
            $stmt->execute([':v'=>$v,':c'=>$c,':e'=>$e,':d'=>$d,':rol'=>$rolId,':modulo'=>$moduloId]);
            $cambios[$m['codigo_modulo']] = [$v,$c,$e,$d];
        }
        $this->db->prepare('UPDATE dbo.th_usuarios_sistema SET token_version=token_version+1 WHERE rol_id=:rol')->execute([':rol'=>$rolId]);
        $this->auditarCambio('Roles','ACTUALIZAR_PERMISOS',"Actualizo permisos del rol #{$rolId}.");
        $this->db->commit();
        $this->sincronizarHaciaCentral($rolId, $cambios);
        return ['exito'=>1,'mensaje'=>'Permisos guardados y sesiones del rol revocadas.'];
    } catch(Throwable $e) {
        if($this->db->inTransaction())$this->db->rollBack();
        Conexion::registrarErrorLog($e,'admin',false);
        return ['exito'=>0,'mensaje'=>'No fue posible guardar la matriz de permisos.'];
    }
}

/** Traduce las 4 columnas de TH a nivel_crud jerárquico y las UPSERTea cross-DB en CORE_Permisos_Nodo. Pérdida documentada si la combinación no es contigua (ver spec 2026-08-11). */
private function sincronizarHaciaCentral(int $rolIdTh, array $cambiosPorCodigo): void {
    $rolPortal = $this->rolPortalDesdeTh($rolIdTh);
    if ($rolPortal === null) return;

    $codigoAOpcion = array_flip([
        1=>'dashboard',2=>'directorio',3=>'empleados',4=>'acciones',5=>'movimientos',
        6=>'socioeconomico',7=>'biblioteca',8=>'maestros',9=>'usuarios',10=>'roles',
        11=>'politicas',12=>'auditoria',13=>'reportes',14=>'prototipos',
    ]);

    $conn = require dirname(__DIR__, 4) . '/config/connections.php';
    $opts = ['CharacterSet' => 'UTF-8', 'TrustServerCertificate' => (bool)($conn['options']['trust_cert'] ?? true)];
    if (!empty($conn['credentials']['user'])) { $opts['UID'] = $conn['credentials']['user']; $opts['PWD'] = $conn['credentials']['pass']; }
    $opts['Database'] = $conn['databases']['portal']['name'];
    $c = @sqlsrv_connect($conn['databases']['portal']['server'] ?? $conn['server_default'], $opts);
    if ($c === false) { $this->auditarCambio('Roles','SYNC_FALLO',"No se pudo conectar al portal para sincronizar el rol #{$rolIdTh}."); return; }

    foreach ($cambiosPorCodigo as $codigo => [$v,$cr,$e,$d]) {
        $opcion = $codigoAOpcion[$codigo] ?? null;
        if ($opcion === null) continue;

        // nivel = el prefijo contiguo más largo empezando en "visualizar"
        $nivel = 0;
        if ($v) { $nivel = 1; if ($cr) { $nivel = 2; if ($e) { $nivel = 3; if ($d) { $nivel = 4; } } } }
        // no contiguo: alguna bandera posterior al corte de arriba seguía en 1
        // (ej. editar=0 pero eliminar=1) -- esa información se pierde al bajar
        // a un único nivel_crud y se deja constancia en la auditoría.
        $noContiguo = ($cr && !$v) || ($e && !$cr) || ($d && !$e);

        sqlsrv_query($c,
            'MERGE dbo.CORE_Permisos_Nodo AS t
             USING (SELECT ? AS id_rol, 11 AS id_modulo, ? AS opcion, 0 AS items, 0 AS subitems, ? AS nivel_crud) AS s
             ON t.id_rol=s.id_rol AND t.id_modulo=s.id_modulo AND t.opcion=s.opcion AND t.items=s.items AND t.subitems=s.subitems
             WHEN MATCHED THEN UPDATE SET nivel_crud=s.nivel_crud, acceso=1, estado=1
             WHEN NOT MATCHED THEN INSERT (id_rol,id_modulo,opcion,items,subitems,nivel_crud,acceso,estado,fecha_asignacion)
                 VALUES (s.id_rol,s.id_modulo,s.opcion,s.items,s.subitems,s.nivel_crud,1,1,SYSDATETIME());',
            [$rolPortal, $opcion, $nivel]
        );
        if ($noContiguo) {
            $this->auditarCambio('Roles','SYNC_PERMISO_NO_CONTIGUO', "Módulo {$codigo}: combinación no jerárquica simplificada a nivel {$nivel} al sincronizar hacia el portal.");
        }
    }
    sqlsrv_close($c);
}
```

- [ ] **Step 2: `php -l apps/talento_humano/modules/admin/Modelos/AdminModel.php`**

- [ ] **Step 3: Verificación real** — como Director TH (rol_id=2, mapeado a
`DIR_TH`/id_rol=11): entrar a TH `/admin/roles`, cambiar permisos de
"Directorio de Personal" a solo Ver+Crear, guardar. Verificar:

```sql
SELECT nivel_crud FROM PORTAL_APM.dbo.CORE_Permisos_Nodo WHERE id_rol=11 AND id_modulo=11 AND opcion=2;
-- esperado: 2
```

Confirmar que `/admin/roles/11/permisos` en el portal nativo muestra ese
mismo nivel para "Directorio de Personal" bajo Talento Humano.

- [ ] **Step 4: Commit** (con confirmación del usuario)

---

### Task 8: Verificación end-to-end completa (navegador real)

**Files:** ninguno — solo verificación.

- [ ] **Step 1:** Crear un rol de prueba TH-mapeado (usar uno de los 4 ya
mapeados, ej. ANALISTA_TH/Analista de Nómina) y un usuario portal + un
usuario TH de prueba, ambos desechables, ligados a ese rol.

- [ ] **Step 2:** Editar permisos desde `/admin/roles/{id}/permisos`
(portal) para "Acción de Personal" → nivel 2 (Ver+Crear). Loguear como el
usuario TH de prueba (login nativo de TH), confirmar en el sidebar
(`Auth::can('acciones','visualizar')`) que el ítem aparece, y que
intentar aprobar una acción (requiere nivel editar) da 403 — sin tocar
`shared/menu.php` (Task de "descubrimiento" arriba: ya funciona solo).

- [ ] **Step 3:** Editar el MISMO rol desde el `/admin/roles`
propio de TH, subir a nivel Editar. Confirmar que ahora sí puede aprobar,
Y que `/admin/roles/{id}/permisos` del portal refleja nivel 3 para ese
nodo sin recargar código.

- [ ] **Step 4:** Probar el override por usuario (Task 4): al usuario TH de
prueba, agregarle un override individual revocando "Acción de Personal"
(nivel 0) desde `/admin/usuarios/{id}/editar` en el portal — esto NO
sincroniza a TH (el override de usuario es exclusivamente del sistema
central, fuera del alcance del sync con TH según el spec) — documentar
esto como limitación conocida si no estaba explícito: **el override por
usuario solo protege pantallas nativas del portal en esta fase**, TH sigue
gobernado por su propio rol hasta que exista un mecanismo de override por
usuario específico DENTRO de TH (fuera de alcance de Fase 1).

- [ ] **Step 5:** Limpiar TODO dato de prueba (roles, usuarios, overrides,
filas de `CORE_Roles_Modulo_Map` de prueba si se crearon extra) — verificar
con queries directas que no queda rastro, igual que el resto de este
proyecto.

- [ ] **Step 6:** `php -l` sobre el árbol completo tocado
(`core/`, `modules/Central/controllers/`, `apps/talento_humano/modules/admin/`)
una vez más, para descartar cualquier archivo modificado a mano en la
verificación.

---

## Self-Review de este plan

**Cobertura del spec**: `CORE_Modulos` (Task 1,2,3), `CORE_Permisos_Nodo_Usuario`
+ cascada usuario>rol (Task 1,4), `CORE_Roles_Modulo_Map` (Task 5),
`es_accion` (Task 5, construida pero deliberadamente sin uso en TH —
documentado por qué), árbol real de TH (Task 5), sync bidireccional con
auditoría de origen y traducción con pérdida documentada (Task 6,7),
"conflicto = último guardado gana" (implícito: ambos UPDATE simplemente
pisan, sin lock). Cubierto: todo lo de Fase 0 y Fase 1 del spec aprobado.

**Ajuste consciente frente al spec**: no se toca `shared/menu.php` de TH
(el spec decía que sí) — se comprobó leyendo el código que ya lee
`Auth::can()` en cada ítem, así que el sync solo (Task 6) alcanza para que
el sidebar reaccione. Documentado arriba como "Descubrimiento importante".

**Fuera de alcance de este plan (ya lo estaba en el spec)**: Fase 2
(Bienes) y Fase 3 (Bitácoras) — tienen su propio diseño en el spec pero no
se implementan acá, quedan como plan(es) separado(s) cuando el usuario
confirme avanzar con ellas. Reintentos automáticos de sync tras fallo
cross-DB.
