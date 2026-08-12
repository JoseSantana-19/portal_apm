# Permisos Centrales Fase 3 (Bitácoras Portuarias) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Colapsar los dos sistemas de autorización paralelos de Bitácoras (comparación exacta de departamento vs comparación por substring, hoy activos simultáneamente y capaces de divergir) en una sola fuente de verdad real basada en `fn_TienePermisoNodo`/MOIS, y cerrar los huecos de autorización reales encontrados en el camino.

**Architecture:** Bitácoras conecta a `PORTAL_APM` por defecto (`DB_NAME`), así que la consulta a `fn_TienePermisoNodo` es same-DB, sin gimnasia cross-DB. Se agrega `Auth::tienePermisoNodo()` (estático, mismo query que `Controller::tienePermisoNodo()` ya existente pero inalcanzable desde `Auth.php`), se reescriben los 10 métodos `Auth::canXxx()` para consultarlo contra el nodo MOIS real que les corresponde, y las funciones procedurales `apm_can_*()` de `includes/bit_auth_permissions.php` pasan a ser wrappers de una línea sobre `Auth::canXxx()` — mismos nombres en ambos lados (los usan `bit_sidebar.php` y varias vistas), pero una sola implementación real detrás.

**Tech Stack:** PHP 8, sqlsrv nativo (sin PDO), same-DB `PORTAL_APM`.

## Global Constraints

- Modelo `nivel_crud` 0-4 jerárquico — no se toca.
- Sin `es_accion` — YAGNI confirmado (ver spec, sección Fase 3 revisada): ninguna acción real de Bitácoras necesita más granularidad que Ver/Crear/Editar/Eliminar.
- Sin sync bidireccional — no existe ninguna pantalla `admin/roles` propia de Bitácoras.
- `PortDashboardController::ejecutivo()` **NO se gatea** — el código ya documenta explícitamente ("Sin permiso específico — igual que el original, cualquier usuario logueado puede verlo") que es intencional. Darle un nodo MOIS con gate real sería una regresión (nadie tiene grants hoy porque el módulo no tenía árbol real; de repente exigir nivel 1 dejaría a todos sin acceso). Queda fuera de alcance.
- `/importar-funcionarios` POST: bug de ruteo preexistente (la ruta POST nunca se registró, el import CSV es hoy inalcanzable) — se documenta, se le da nodo MOIS por si se arregla, pero **no se arregla el bug de ruteo** en este plan (fuera de alcance, no es un problema de permisos).
- El bug de atribución FK (`bit_dev_auto_login.php` reintroduce `id_usuario` crudo del portal en vez de resolver por cédula) — **no se toca**, es un problema de integridad de datos preexistente, no de permisos.
- Toda migración SQL es idempotente, con backup previo y verificación por queries directas — mismo patrón que Fase 0/1/2.
- No commitear sin confirmación explícita del usuario en el chat.

---

## Contexto que todo task debe conocer

**`CORE_Menu_Nodos` hoy para `id_modulo=13`**: un solo nodo plano
(`opcion=1, items=7`, "Sistema de Bitácoras", `url_ruta='/apps/bitacoras/'`)
sin ningún `CORE_Permisos_Nodo` real otorgado sobre él (verificado por
código en `db/bitacoras_apps_migration_menu.sql` — no hay nada que
preservar, a diferencia de TH/Bienes).

**Árbol MOIS real de 13 opciones (id_modulo=13)** que este plan construye:

| opcion | descripcion | quién la usa hoy (antes de este plan) |
|---|---|---|
| 1 | Dashboard | `PortDashboardController::index()` — sin permiso hoy, se mantiene así (nivel 1 = solo estar logueado, todo rol lo tendrá) |
| 2 | Dashboard de Jefatura | `PortDashboardController::jefe()` + `bit_dashboard_jefe.php` (legacy) — `canAccederDashboardJefe()` |
| 3 | Registrar Ingreso | `PortVisitaController::registrar()`/`guardar()` — `canRegistrarIngreso()` |
| 4 | Listado de Visitas | `PortVisitaController::listado()`/`registrarSalida()`/`actualizarHoras()`/`actualizar()`/`detalle()` — `canVerListadoAdmin()`/`canRegistrarSalida()`/`canEditarVisita()`/`canEditarVisitaDesdeListado()` |
| 5 | Registros Base (Catálogos) | `PortCatalogoController::index/personas/empresas/destinos/motivos/funcionarios/nivelesIncidente()` — `canGestionarMaestrosAcceso()` |
| 6 | Rondas de Vigilancia | `PortRondaController::index()`/`api()` — `canAccederBitacoraRondas()` |
| 7 | Cámaras CCTV | `PortCamaraController::*` (index/motivos/inventario/api/apiMotivos/apiInventario) — hoy comparte el flag de Rondas por accidente histórico, se separa (controladores/rutas ya distintos, sin ningún grant real que perder) |
| 8 | Reporte de Supervisor | `bit_reporte_diario_supervisor.php` (legacy) — **sin permiso hoy**, se cierra el hueco |
| 9 | Importar Funcionarios | `PortCatalogoController::importarFuncionarios()` — sin permiso hoy (además de la ruta POST rota) |
| 10 | Configurar Días de Bitácora | sub-acción `set_dias_edicion_guardia` dentro de `PortRondaController::api()` — `canConfigurarDiasBitacora()` |
| 11 | Gestión de Catálogos (escritura) | `PortCatalogoController::api()` (POST create/update/deactivate) — **sin ningún chequeo hoy**, hueco real |
| 12 | Gestión de Personas (escritura) | `PortCatalogoController::apiPersonas()` (POST) — **sin ningún chequeo hoy**, hueco real |
| 13 | Asignar Cédula Guest | `canAsignarCedulaGuest()`, usado en `bit_listado.php` para mostrar/ocultar un botón — sin controlador propio, es una acción embebida en el listado |

**`Auth::canXxx()` actual → nodo MOIS destino**:

| Método (`Auth.php`) | opcion | nivel mínimo |
|---|---|---|
| `canAccederDashboardJefe` | 2 | 1 |
| `canRegistrarIngreso` | 3 | 2 |
| `canVerListadoAdmin` | 4 | 1 |
| `canRegistrarSalida` | 4 | 3 |
| `canEditarVisita` | 4 | 3 |
| `canGestionarMaestrosAcceso` | 5 | 1 |
| `canEditarVisitaDesdeListado` | 4 | 3 |
| `canAsignarCedulaGuest` | 13 | 1 |
| `canAccederBitacoraRondas` | 6 | 1 |
| `canConfigurarDiasBitacora` | 10 | 1 |

Nuevos métodos a agregar: `canAccederCctv` (opcion 7, nivel 1), `canGestionarCatalogosEscritura` (opcion 11, nivel≥2 según create/update/deactivate), `canGestionarPersonasEscritura` (opcion 12, nivel 2), `canAccederReporteSupervisor` (opcion 8, nivel 1).

**`Database` de Bitácoras** (`apps/bitacoras/core/Database.php`): `Database::getInstance()->query($sql, $params)` retorna un `$stmt` sqlsrv; `->fetch($stmt): ?array`; `->free($stmt)`. `DB_NAME` default `'PORTAL_APM'` (`config/app.php` de Bitácoras) — confirmar en Task 2 Step 1 que no está sobreescrito a otra cosa antes de asumir same-DB.

**`Auth::tienePermisoNodo()` usa `$_SESSION['apm_auth']['id_usuario_portal'] ?? $_SESSION['user_id']`** — cubre tanto sesiones hidratadas por `Auth::hydrateFromPortal()` (MVC, siempre setea `id_usuario_portal`) como sesiones del camino legacy-standalone (`bit_dev_auto_login.php`, que NO setea `id_usuario_portal` pero sí `$_SESSION['user_id']` porque viene de la sesión puenteada del portal).

---

### Task 1: Migración SQL — árbol real `id_modulo=13`

**Files:**
- Create: `db/permisos_centrales_fase3_bitacoras.sql`

**Interfaces:**
- Produces: 13 nodos reales en `CORE_Menu_Nodos` para `id_modulo=13`, reemplazando el nodo-esqueleto único.

- [ ] **Step 1: Escribir el script**

```sql
/* db/permisos_centrales_fase3_bitacoras.sql
   Fase 3: Bitácoras Portuarias. Idempotente. */
USE [PORTAL_APM];
GO
SET ANSI_NULLS ON;
SET QUOTED_IDENTIFIER ON;
GO

-- Verificación de seguridad: confirmar que no hay CORE_Permisos_Nodo real
-- sobre el nodo-esqueleto antes de borrar (por si acaso; la investigación
-- de código no encontró ninguno, pero un backup + verificación directa
-- es más barato que un supuesto incorrecto).
IF EXISTS (SELECT 1 FROM dbo.CORE_Permisos_Nodo WHERE id_modulo=13)
BEGIN
    RAISERROR('Hay permisos reales sobre id_modulo=13 -- revisar antes de continuar, no asumir que está vacío.', 16, 1);
END;
GO

IF EXISTS (SELECT 1 FROM dbo.CORE_Menu_Nodos WHERE id_modulo=13 AND opcion>=1)
   AND NOT EXISTS (SELECT 1 FROM dbo.CORE_Menu_Nodos WHERE id_modulo=13 AND descripcion='Dashboard')
BEGIN
    DELETE FROM dbo.CORE_Menu_Nodos WHERE id_modulo=13 AND opcion>=1;
END;
GO

IF NOT EXISTS (SELECT 1 FROM dbo.CORE_Menu_Nodos WHERE id_modulo=13 AND descripcion='Dashboard')
BEGIN
    INSERT dbo.CORE_Menu_Nodos (id_modulo, opcion, items, subitems, descripcion, url_ruta, icono, orden, requiere_mfa, target_spa, estado) VALUES
    (13, 1, 0, 0, 'Dashboard',                    'apps/bitacoras/index.php?route=/portuaria/dashboard', 'fa-gauge-high',        1,  0, 0, 1),
    (13, 2, 0, 0, 'Dashboard de Jefatura',         'apps/bitacoras/index.php?route=/dashboard-jefe',       'fa-chart-line',        2,  1, 0, 1),
    (13, 3, 0, 0, 'Registrar Ingreso',             'apps/bitacoras/index.php?route=/visitas/registrar',    'fa-right-to-bracket',  3,  0, 0, 1),
    (13, 4, 0, 0, 'Listado de Visitas',            'apps/bitacoras/index.php?route=/visitas',               'fa-list-check',        4,  0, 0, 1),
    (13, 5, 0, 0, 'Registros Base (Catálogos)',    'apps/bitacoras/index.php?route=/catalogos',             'fa-address-book',      5,  0, 0, 1),
    (13, 6, 0, 0, 'Rondas de Vigilancia',          'apps/bitacoras/index.php?route=/rondas',                'fa-person-walking-arrow-right', 6, 0, 0, 1),
    (13, 7, 0, 0, 'Cámaras CCTV',                  'apps/bitacoras/index.php?route=/camaras',               'fa-video',             7,  0, 0, 1),
    (13, 8, 0, 0, 'Reporte de Supervisor',         'apps/bitacoras/bit_reporte_diario_supervisor.php',      'fa-clipboard-list',    8,  0, 0, 1),
    (13, 9, 0, 0, 'Importar Funcionarios',         'apps/bitacoras/index.php?route=/importar-funcionarios', 'fa-file-csv',          9,  0, 0, 1),
    (13, 10,0, 0, 'Configurar Días de Bitácora',   NULL,                                                     'fa-calendar-days',     10, 0, 0, 1),
    (13, 11,0, 0, 'Gestión de Catálogos (escritura)', NULL,                                                  'fa-pen',               11, 0, 0, 1),
    (13, 12,0, 0, 'Gestión de Personas (escritura)',  NULL,                                                  'fa-user-pen',          12, 0, 0, 1),
    (13, 13,0, 0, 'Asignar Cédula Guest',          NULL,                                                     'fa-id-card',           13, 0, 0, 1);
END;
GO
```

- [ ] **Step 2: Backup de PORTAL_APM**

```powershell
php -r "
\$conn = require 'config/connections.php';
\$c = sqlsrv_connect(\$conn['server_default'], ['TrustServerCertificate'=>true]);
sqlsrv_query(\$c, \"BACKUP DATABASE [PORTAL_APM] TO DISK='<misma-ruta-de-fases-anteriores>\PORTAL_APM_prefase3.bak'\");
print_r(sqlsrv_errors());
"
```

- [ ] **Step 3: Aplicar**

```powershell
php db/run_sql.php db/permisos_centrales_fase3_bitacoras.sql "<servidor>"
```
Expected: `OK: N batches ejecutados correctamente.` Si falla en el `RAISERROR` del Step 1 del script, DETENERSE — significa que la investigación de código estaba equivocada y hay permisos reales que preservar (mismo patrón captura-antes-de-borrar que TH/Bienes debe aplicarse entonces, no continuar a ciegas).

- [ ] **Step 4: Verificar**

```powershell
php -r "
\$conn = require 'config/connections.php';
\$c = sqlsrv_connect(\$conn['server_default'], ['CharacterSet'=>'UTF-8','TrustServerCertificate'=>true,'Database'=>'PORTAL_APM']);
\$s = sqlsrv_query(\$c, 'SELECT COUNT(*) n FROM CORE_Menu_Nodos WHERE id_modulo=13 AND opcion>=1');
\$r = sqlsrv_fetch_array(\$s, SQLSRV_FETCH_ASSOC); echo 'nodos: ' . \$r['n'] . PHP_EOL;
"
```
Expected: `nodos: 13`

---

### Task 2: `Auth::tienePermisoNodo()` + reescritura de `Auth::canXxx()`

**Files:**
- Modify: `apps/bitacoras/modules/Portuaria/models/Auth.php`

**Interfaces:**
- Consumes: `Database::getInstance()->query()/fetch()/free()` (ya existe).
- Produces: `Auth::tienePermisoNodo(int $opcion, int $items, int $subitems, int $nivelMin): bool`; los 10 `canXxx()` existentes reescritos; 4 nuevos (`canAccederCctv`, `canGestionarCatalogosEscritura`, `canGestionarPersonasEscritura`, `canAccederReporteSupervisor`).

- [ ] **Step 1: Confirmar `DB_NAME` real antes de asumir same-DB**

```powershell
grep -n "DB_NAME" apps/bitacoras/config/app.php
```
Si no es `'PORTAL_APM'`, ajustar Step 2 para usar el nombre real con prefijo de 3 partes en vez de asumir same-DB.

- [ ] **Step 2: Agregar `tienePermisoNodo()` — insertar antes de `// ============ PERMISOS`**

```php
    /** Consulta fn_TienePermisoNodo — id_modulo=13 fijo (Bitácoras Portuarias). Same-DB. */
    public static function tienePermisoNodo(int $opcion, int $items, int $subitems, int $nivelMin): bool
    {
        $idUsuario = (int)($_SESSION['apm_auth']['id_usuario_portal'] ?? $_SESSION['user_id'] ?? 0);
        if ($idUsuario <= 0) return false;
        try {
            $db   = Database::getInstance();
            $stmt = $db->query(
                'SELECT dbo.fn_TienePermisoNodo(?,13,?,?,?,?,1) AS ok',
                [$idUsuario, $opcion, $items, $subitems, $nivelMin]
            );
            $row = $db->fetch($stmt);
            $db->free($stmt);
            return (bool)($row['ok'] ?? false);
        } catch (Throwable $e) {
            return false;
        }
    }

```

- [ ] **Step 3: Reescribir los métodos `canXxx()` existentes**

Reemplazar el bloque completo desde `// --- Permisos funcionales (misma matriz del origen) ---` hasta el final de `canConfigurarDiasBitacora()`:

```php
    // --- Permisos funcionales (respaldados por fn_TienePermisoNodo real, id_modulo=13) ---

    public static function canAccederDashboardJefe(): bool
    {
        return self::tienePermisoNodo(2, 0, 0, 1);
    }

    public static function canRegistrarIngreso(): bool
    {
        return self::tienePermisoNodo(3, 0, 0, 2);
    }

    public static function canVerListadoAdmin(): bool
    {
        return self::tienePermisoNodo(4, 0, 0, 1);
    }

    public static function canRegistrarSalida(): bool
    {
        return self::tienePermisoNodo(4, 0, 0, 3);
    }

    public static function canEditarVisita(): bool
    {
        return self::tienePermisoNodo(4, 0, 0, 3);
    }

    public static function canGestionarMaestrosAcceso(): bool
    {
        return self::tienePermisoNodo(5, 0, 0, 1);
    }

    public static function canEditarVisitaDesdeListado(): bool
    {
        return self::tienePermisoNodo(4, 0, 0, 3);
    }

    public static function canAsignarCedulaGuest(): bool
    {
        return self::tienePermisoNodo(13, 0, 0, 1);
    }

    public static function canAccederBitacoraRondas(): bool
    {
        return self::tienePermisoNodo(6, 0, 0, 1);
    }

    public static function canAccederCctv(): bool
    {
        return self::tienePermisoNodo(7, 0, 0, 1);
    }

    public static function canConfigurarDiasBitacora(): bool
    {
        return self::tienePermisoNodo(10, 0, 0, 1);
    }

    /** Cierra hueco real: hoy PortCatalogoController::api() (POST) no chequea nada. */
    public static function canGestionarCatalogosEscritura(int $nivelMin = 2): bool
    {
        return self::tienePermisoNodo(11, 0, 0, $nivelMin);
    }

    /** Cierra hueco real: hoy PortCatalogoController::apiPersonas() (POST) no chequea nada. */
    public static function canGestionarPersonasEscritura(): bool
    {
        return self::tienePermisoNodo(12, 0, 0, 2);
    }

    /** Cierra hueco real: hoy bit_reporte_diario_supervisor.php no chequea nada. */
    public static function canAccederReporteSupervisor(): bool
    {
        return self::tienePermisoNodo(8, 0, 0, 1);
    }
```

Se retiran `isTecnologiaInformacion/isEdificioAdministrativo/isAdminArea/isSeguridadOperativa/isJefeArea/isGerencia/areaKey` — quedaban huérfanos (sin ningún llamador fuera de los métodos que se acaban de reescribir; confirmar con grep antes de borrar, Step 4).

- [ ] **Step 4: Confirmar que las funciones `is*`/`areaKey` no tienen otros llamadores antes de borrarlas**

```powershell
grep -rn "Auth::isAdminArea\|Auth::isSeguridadOperativa\|Auth::isTecnologiaInformacion\|Auth::isEdificioAdministrativo\|Auth::isJefeArea\|Auth::isGerencia" apps/bitacoras --include="*.php" | grep -v "modules/Portuaria/models/Auth.php"
```
Expected: sin resultados (0 líneas). Si aparece alguno, NO borrar ese método — dejar la implementación vieja de ESE método específico intacta (o adaptarlo) y anotar el hallazgo antes de continuar.

- [ ] **Step 5: `php -l`**

```powershell
php -l apps/bitacoras/modules/Portuaria/models/Auth.php
```
Expected: `No syntax errors detected`

---

### Task 3: `apm_can_*()` procedural — wrappers delgados sobre `Auth::canXxx()`

**Files:**
- Modify: `apps/bitacoras/includes/bit_auth_permissions.php`

**Interfaces:**
- Consumes: `Auth::canXxx()` (Task 2).
- Produces: mismas 15 funciones `apm_*`/`apm_can_*`, mismos nombres y firmas, ahora respaldadas por MOIS real.

- [ ] **Step 1: Reemplazar el bloque completo desde `function apm_is_tecnologia_informacion()` hasta `function apm_can_configurar_dias_bitacora()`**

```php
require_once __DIR__ . '/../modules/Portuaria/models/Auth.php';

/** @deprecated usar Auth::canAccederDashboardJefe() -- queda solo por compat de nombre en vistas legacy. */
function apm_can_acceder_dashboard_jefe()
{
    return Auth::canAccederDashboardJefe();
}

function apm_can_registrar_ingreso()
{
    return Auth::canRegistrarIngreso();
}

function apm_can_ver_listado_admin()
{
    return Auth::canVerListadoAdmin();
}

function apm_can_ver_bloque_admin()
{
    return apm_can_ver_listado_admin();
}

function apm_can_registrar_salida()
{
    return Auth::canRegistrarSalida();
}

function apm_can_editar_visita()
{
    return Auth::canEditarVisita();
}

function apm_can_gestionar_maestros_acceso()
{
    return Auth::canGestionarMaestrosAcceso();
}

function apm_can_editar_visita_desde_listado()
{
    return Auth::canEditarVisitaDesdeListado();
}

function apm_can_asignar_cedula_guest()
{
    return Auth::canAsignarCedulaGuest();
}

function apm_can_acceder_bitacora_rondas()
{
    return Auth::canAccederBitacoraRondas();
}

function apm_can_acceder_cctv()
{
    return Auth::canAccederCctv();
}

function apm_can_acceder_reporte_supervisor()
{
    return Auth::canAccederReporteSupervisor();
}

function apm_can_configurar_dias_bitacora()
{
    return Auth::canConfigurarDiasBitacora();
}
```

Nota: `apm_is_tecnologia_informacion()`, `apm_is_edificio_administrativo()`,
`apm_is_admin_area()`, `apm_is_seguridad_operativa()`, `apm_is_talento_humano()`,
`apm_is_jefe_area()`, `apm_is_gerencia()`, `apm_current_area_key()`,
`apm_current_departamento_id()`, `apm_departamento_cmp_key()`,
`apm_normalize_text()` se retiran — confirmar sin otros llamadores primero
(Step 2). Las funciones de cálculo de días (`apm_bitacora_guardia_dias_permitidos`,
`apm_bitacora_dias_edicion_permitidos`, `apm_bitacora_fecha_minima_edicion`)
y `apm_deny_json()` **NO se tocan** — ya llaman a `apm_can_configurar_dias_bitacora()`
por nombre, que sigue existiendo con la misma firma.

- [ ] **Step 2: Confirmar que las funciones `apm_is_*`/`apm_current_*`/`apm_departamento_cmp_key`/`apm_normalize_text` no tienen otros llamadores antes de borrarlas**

```powershell
grep -rn "apm_is_tecnologia_informacion\|apm_is_edificio_administrativo\|apm_is_admin_area\|apm_is_seguridad_operativa\|apm_is_talento_humano\|apm_is_jefe_area\|apm_is_gerencia\|apm_current_area_key\|apm_current_departamento_id\|apm_departamento_cmp_key\|apm_normalize_text" apps/bitacoras --include="*.php" | grep -v "includes/bit_auth_permissions.php"
```
Si aparece algún resultado fuera de `bit_auth_permissions.php`, NO borrar esa función específica — dejarla intacta y anotar el hallazgo (puede ser una vista que la usa directamente para otra cosa no relacionada a permisos).

- [ ] **Step 3: `php -l`**

```powershell
php -l apps/bitacoras/includes/bit_auth_permissions.php
```
Expected: `No syntax errors detected`

---

### Task 4: Cerrar los 3 huecos reales de autorización

**Files:**
- Modify: `apps/bitacoras/modules/Portuaria/controllers/PortCatalogoController.php`
- Modify: `apps/bitacoras/bit_reporte_diario_supervisor.php`

**Interfaces:**
- Consumes: `Auth::canGestionarCatalogosEscritura()`, `Auth::canGestionarPersonasEscritura()`, `Auth::canAccederReporteSupervisor()` (Task 2).

- [ ] **Step 1: `PortCatalogoController::api()` — agregar chequeo en la rama POST**

Ubicar el bloque `if ($method === 'GET') { ... }` en `api()` (línea ~88) y el `if ($method === 'POST')` que le sigue. Justo al inicio de la rama POST, agregar:

```php
        if ($method === 'POST') {
            $action = trim((string)($_POST['action'] ?? ''));
            $nivelMin = ($action === 'deactivate') ? 3 : 2; // desactivar = editar; crear/actualizar = crear
            if (!Auth::canGestionarCatalogosEscritura($nivelMin)) {
                $this->json(array('ok' => false, 'message' => 'Permiso denegado.'), 403);
            }
            // ... resto del código POST existente sin cambios ...
```

(Insertar la llamada ANTES de la lógica existente de esa rama, no reemplazar nada del cuerpo — solo agregar el guard al principio del bloque POST.)

- [ ] **Step 2: `PortCatalogoController::apiPersonas()` — agregar chequeo en la rama POST**

Mismo patrón, en el `if ($method === 'POST')` de `apiPersonas()` (la rama GET es búsqueda, solo lectura, no se toca):

```php
        if ($method === 'POST') {
            if (!Auth::canGestionarPersonasEscritura()) {
                $this->json(array('ok' => false, 'message' => 'Permiso denegado.'), 403);
            }
            // ... resto del código POST existente sin cambios ...
```

- [ ] **Step 3: `bit_reporte_diario_supervisor.php` — agregar chequeo tras el guard de sesión**

```php
require_once __DIR__ . '/includes/bit_auth_guard.php';
require_once __DIR__ . '/modules/Portuaria/models/Auth.php';
if (!Auth::canAccederReporteSupervisor()) {
    http_response_code(403);
    echo '<h2 style="text-align:center;margin-top:60px;">Acceso denegado.</h2>';
    exit;
}
require_once __DIR__ . '/conexion/zona_horaria.php';
```

- [ ] **Step 4: `php -l`**

```powershell
php -l apps/bitacoras/modules/Portuaria/controllers/PortCatalogoController.php
php -l apps/bitacoras/bit_reporte_diario_supervisor.php
```
Expected: `No syntax errors detected` en ambos.

---

### Task 5: Verificación end-to-end + limpieza + `php -l` final

**Files:** ninguno — solo verificación.

- [ ] **Step 1: Con la sesión de administrador ya disponible, verificar en `/admin/roles/{id}/permisos` que aparece la sección "Bitácoras Portuarias (CCTV/Visitas)" con los 13 nodos reales** (ya se vio de pasada durante la Fase 2 — confirmar ahora con atención: nombres correctos, orden correcto).

- [ ] **Step 2: Otorgar un permiso real de prueba.** Elegir un rol de prueba desechable o reusar uno existente sin usuarios reales asignados, otorgar nivel 2 (Ver+Crear) sobre "Registrar Ingreso" (opcion 3), guardar. Verificar con query directa:
```powershell
php -r "
\$conn = require 'config/connections.php';
\$c = sqlsrv_connect(\$conn['server_default'], ['CharacterSet'=>'UTF-8','TrustServerCertificate'=>true,'Database'=>'PORTAL_APM']);
\$s = sqlsrv_query(\$c, 'SELECT nivel_crud FROM CORE_Permisos_Nodo WHERE id_rol=<id> AND id_modulo=13 AND opcion=3');
print_r(sqlsrv_fetch_array(\$s, SQLSRV_FETCH_ASSOC));
"
```

- [ ] **Step 3: Verificación real en navegador — usuario de prueba desechable puenteado, asignado a ese rol.** Crear usuario portal desechable (mismo patrón que Fase 2 Task 11), asignarle el rol de prueba, loguear, navegar a `/portuaria/dashboard` (Fase 3 no gatea el dashboard base, debe entrar), luego intentar `/visitas` (nivel 1 en opcion 4, sin otorgar — debe redirigir a `dashboard?msg=acceso_denegado`), luego `/visitas/registrar` (nivel 2 en opcion 3, SÍ otorgado — debe entrar).

- [ ] **Step 4: Confirmar cierre del hueco real.** Con el mismo usuario de prueba (sin ningún permiso en opcion 11), intentar `POST /bitacoras/catalogo/api?catalogo=personas&action=create` — debe responder `403 {"ok":false,"message":"Permiso denegado."}` en vez de crear el registro.

- [ ] **Step 5: Limpiar todo dato de prueba** — usuario portal desechable, rol de prueba si se creó uno nuevo, cualquier `CORE_Permisos_Nodo` de prueba que no sea parte de la migración real. Verificar con queries directas que no queda rastro.

- [ ] **Step 6: `php -l` final sobre el árbol completo tocado**

```powershell
for f in apps/bitacoras/modules/Portuaria/models/Auth.php apps/bitacoras/includes/bit_auth_permissions.php apps/bitacoras/modules/Portuaria/controllers/PortCatalogoController.php apps/bitacoras/bit_reporte_diario_supervisor.php; do php -l "$f"; done
```
Expected: `No syntax errors detected` en los 4.

---

## Self-Review de este plan

**Cobertura del spec (sección Fase 3 revisada)**: árbol MOIS real (Task 1),
colapso de los dos sistemas divergentes en `Auth::tienePermisoNodo()` (Task 2),
`apm_can_*()` como wrappers (Task 3), cierre de los 3 huecos reales
(Task 4), verificación real (Task 5). `es_accion` deliberadamente omitido
(YAGNI, documentado). `bit_sidebar.php` deliberadamente NO reescrito
(ya funciona vía `apm_can_*()`, que ahora es MOIS real — mismo tipo de
ajuste que "shared/menu.php ya funciona solo" de TH Fase 1).

**Fuera de alcance explícito** (ya lo estaba en el spec o se decidió durante
la investigación de esta fase): `ejecutivo()` sin gate (intencional, documentado
en el propio código fuente); bug de ruteo de `/importar-funcionarios` POST;
bug de atribución FK de `bit_dev_auto_login.php`; las 3 divergencias de
`hydrateFromPortal()` vs el camino legacy-standalone en cuanto a
`id_usuario_portal` — se cubre lo mínimo necesario (fallback a `user_id`)
para que `tienePermisoNodo()` funcione en ambos caminos, sin tocar el bug
de fondo.
