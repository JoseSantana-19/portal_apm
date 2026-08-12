# Permisos Centrales Fase 2 (Control de Bienes) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Dar a `apps/control_bienes` permisos reales por rol (nuevo, no existía) y por usuario (existente, se upgradea a CRUD real), sincronizados bidireccionalmente con el sistema central del portal (`CORE_Permisos_Nodo`/`CORE_Permisos_Nodo_Usuario`), cerrando de paso el hueco real de seguridad donde cualquier acción (`crear`/`editar`/`eliminar`) es alcanzable vía `?action=` sin importar los permisos de menú.

**Architecture:** Bienes gana dos tablas nativas nuevas (`inv_roles`, `inv_permisos_rol`) que reflejan el mismo modelo `nivel_crud` 0-4 jerárquico del central, sincronizadas vía `CORE_Roles_Modulo_Map` (mismo patrón que Talento Humano en Fase 1). `Router::checkPermisos()` gana una tabla de políticas `(route, action) → (opción MOIS, nivel mínimo)` con dos ramas de resolución: sesiones puenteadas desde el portal usan `fn_TienePermisoNodo` cross-DB directo (ya cubre rol + override de usuario, sin código nuevo); cuentas nativas de Bienes (sin cédula en el portal) usan una cascada local usuario>rol contra las tablas nuevas, porque no tienen fila equivalente en `CORE_Usuarios` con la que sincronizar a nivel de usuario individual.

**Tech Stack:** PHP 8 nativo (MVC propio, sin framework), PDO sqlsrv (`apps/control_bienes`), sqlsrv nativo (portal), SQL Server cross-DB same-instance (`inventario` ↔ `PORTAL_APM`, nombre de 3 partes, sin linked server).

## Global Constraints

- Modelo `nivel_crud` 0-4 jerárquico/acumulativo (1=Ver,2=+Crear,3=+Editar,4=+Total) — no se toca, decisión ya confirmada.
- Sin `es_accion` — YAGNI, mismo razonamiento que TH Fase 1 (ver spec, sección Fase 2 revisada).
- Toda migración SQL es idempotente (`IF OBJECT_ID(...) IS NULL` / `IF NOT EXISTS`) y se aplica con backup previo de la BD real (`BACKUP DATABASE ... TO DISK`, patrón ya usado en Fase 0/1).
- Verificación con queries directas antes/después de cada migración — nunca confiar solo en el mensaje "OK" del script.
- CSRF: los controladores de Bienes hoy NO verifican CSRF en ninguna escritura — está fuera de alcance arreglarlo en este plan (es un problema de hardening general, no de permisos); no introducir una regresión pero tampoco expandir el arreglo más allá de lo pedido.
- No commitear nada a git sin confirmación explícita del usuario en el chat, pedida de nuevo al terminar este plan.

---

## Contexto que todo task debe conocer

**Router real** (`apps/control_bienes/core/Router.php::dispatch()`): resuelve `module`+`controller` desde `config/routes.php` por la clave `route` (`$_GET['route']`, default `'inventario'`). El método a ejecutar (`$actionName`) sale de `$_GET['action']` (default el `action` declarado en `routes.php` para esa `route`) y se llama con `method_exists()+$controllerInstance->$actionName()` **sin validarlo contra el action por defecto** — cualquier método público del controlador resuelto por esa `route` es alcanzable con cualquier `route` que apunte a ese mismo controlador. La gating debe ser por el par `(route, action)` tal como llega en el request, no por controlador.

**Las 17 `route` reales** (`apps/control_bienes/config/routes.php`) y su controlador:
`inventario`/`items`/`inv_items_sistema` → `BinController`; `cabeceras`/`inv_maestros` → `EstacionController`; `ingresos`/`egresos` → `MonitoreoController`; `talento`/`talento_directorio`/`talento_crear`/`talento_guardar`/`talento_editar`/`talento_borrar`/`talento_eliminar`/`talento_imprimir_ficha` → `EmpleadoController`; `inv_bitacora` → `EventoController`; `reportes` → `ReporteController`; `inv_lookup` → `LookupController`; `dashboard` → `DashboardController`; `inv_periodos`/`inv_secuenciales` → `ConfigController`; `notificaciones_marcar_leidas`/`notificaciones_vaciar` → `NotificacionesController` (ya públicas, sin cambios); `inv_login`/`login_post`/`logout` → `AuthController` (públicas, sin cambios); `usuarios` → `UsuarioController`; `inv_permisos` → `PermisoController`.

**Sesión puenteada vs nativa** (`apps/control_bienes/index.php`): si `$_SESSION['user_id']` existe (viene del portal) y `$_SESSION['usuario']` está vacío, se sintetiza `$_SESSION['usuario']`/`rol`/`usuario_id` = el `id_usuario` DEL PORTAL. Si el login fue nativo (`Credenciales/AuthController::loginPost` contra `inv_usuarios`), `$_SESSION['usuario_id']` es un ID LOCAL de `inv_usuarios`, sin relación con el portal. La forma de distinguir en cualquier punto del código: `!empty($_SESSION['user_id'])` ⇒ puenteada; si no, nativa.

**`fn_TienePermisoNodo`** (`PORTAL_APM.dbo`, ya existe desde Fase 0):
```sql
fn_TienePermisoNodo(@id_usuario INT, @id_modulo TINYINT, @opcion TINYINT, @items TINYINT, @subitems SMALLINT, @nivel_min TINYINT, @mfa_ok BIT = 1) RETURNS BIT
```
Resuelve override de usuario (`CORE_Permisos_Nodo_Usuario`) primero, si no hay fila cae a rol (`CORE_Permisos_Nodo` vía `CORE_Usuarios_Roles`). `@id_usuario` para Bienes puenteado = `$_SESSION['user_id']` (el id del portal).

**Roles portal existentes** (`CORE_Roles`, `PORTAL_APM`): 22 filas, ninguna relacionada con Bienes/Inventario. Se crean 3 nuevas: `BIENES_SUPERVISOR` (nivel_jerarquia=2), `BIENES_OPERADOR` (nivel_jerarquia=1), `BIENES_AUDITOR` (nivel_jerarquia=1) — las 3 con `id_departamento=23` ("CONTROL DE BIENES", ya existe). El rol `Administrador` nativo de Bienes mapea al `ADMIN` ya existente (`id_rol=1`), igual que en TH.

**`CORE_Menu_Nodos`/`CORE_Permisos_Nodo` actuales para `id_modulo=12`**: 2 nodos-esqueleto (`opcion=1,items=1` "Panel"; `opcion=1,items=5` "Sistema de Control de Bienes"), con permisos reales ya otorgados a 6 roles: `id_rol` 1 (ADMIN, nivel 4), 2 (AUDITOR, nivel 1), 13 (GERENTE, nivel 1), 14 (ASIST_GCIA, nivel 1), 15 (DIR_ADMIN, nivel 4), 16 (ANALISTA_ADMIN, nivel 2) — deben preservarse (re-otorgar sobre el nodo nuevo "Dashboard").

**Árbol MOIS real de 15 opciones (id_modulo=12)** que este plan construye — memorizar esta tabla, se referencia en varios tasks:

| opcion | descripcion | route nativa | url_ruta |
|---|---|---|---|
| 1 | Dashboard | `dashboard` | `apps/control_bienes/index.php?route=dashboard` |
| 2 | Inventario General | `inventario` | `apps/control_bienes/index.php?route=inventario` |
| 3 | Catálogo de Ítems | `items` | `apps/control_bienes/index.php?route=items` |
| 4 | Ítems del Sistema | `inv_items_sistema` | `apps/control_bienes/index.php?route=inv_items_sistema` |
| 5 | Tablas de Cabecera | `cabeceras` | `apps/control_bienes/index.php?route=cabeceras` |
| 6 | Maestros | `inv_maestros` | `apps/control_bienes/index.php?route=inv_maestros` |
| 7 | Ingresos de Bodega | `ingresos` | `apps/control_bienes/index.php?route=ingresos` |
| 8 | Egresos de Bodega | `egresos` | `apps/control_bienes/index.php?route=egresos` |
| 9 | Directorio de Personal | `talento_directorio` | `apps/control_bienes/index.php?route=talento_directorio` |
| 10 | Bitácora del Sistema | `inv_bitacora` | `apps/control_bienes/index.php?route=inv_bitacora` |
| 11 | Reportes Varios | `reportes` | `apps/control_bienes/index.php?route=reportes` |
| 12 | Períodos e IVA | `inv_periodos` | `apps/control_bienes/index.php?route=inv_periodos` |
| 13 | Secuenciales de Índice | `inv_secuenciales` | `apps/control_bienes/index.php?route=inv_secuenciales` |
| 14 | Gestión de Usuarios | `usuarios` | `apps/control_bienes/index.php?route=usuarios` |
| 15 | Gestión de Permisos | `inv_permisos` | `apps/control_bienes/index.php?route=inv_permisos` |

`route_key` nativo (columna en `inv_permisos`/`inv_permisos_rol`) = el string de la columna "route nativa" de arriba (ej. `'inventario'`, `'talento_directorio'`).

---

### Task 1: Migración SQL — lado portal (`PORTAL_APM`)

**Files:**
- Create: `db/permisos_centrales_fase2_bienes_portal.sql`

**Interfaces:**
- Produces: 3 roles portal nuevos (`BIENES_SUPERVISOR`/`BIENES_OPERADOR`/`BIENES_AUDITOR`), `CORE_Roles_Modulo_Map` con 4 filas para `id_modulo=12`, árbol real de 15 nodos en `CORE_Menu_Nodos` (tabla de arriba), permisos de los 6 roles preexistentes preservados sobre el nuevo nodo "Dashboard" (`opcion=1`).

- [ ] **Step 1: Escribir el script de migración**

```sql
/* db/permisos_centrales_fase2_bienes_portal.sql
   Fase 2: Control de Bienes, lado portal. Idempotente. */
USE [PORTAL_APM];
GO
SET ANSI_NULLS ON;
SET QUOTED_IDENTIFIER ON;
GO

-- 3 roles portal nuevos para los roles nativos de Bienes que no tienen
-- equivalente hoy (Administrador sí lo tiene: ADMIN, id_rol=1).
IF NOT EXISTS (SELECT 1 FROM dbo.CORE_Roles WHERE codigo = 'BIENES_SUPERVISOR')
BEGIN
    INSERT dbo.CORE_Roles (codigo, nombre, id_departamento, nivel_jerarquia, estado, fecha_creacion) VALUES
    ('BIENES_SUPERVISOR', 'Supervisor de Bienes', 23, 2, 1, SYSDATETIME()),
    ('BIENES_OPERADOR',   'Operador de Bienes',   23, 1, 1, SYSDATETIME()),
    ('BIENES_AUDITOR',    'Auditor de Bienes',    23, 1, 1, SYSDATETIME());
END;
GO

IF NOT EXISTS (SELECT 1 FROM dbo.CORE_Roles_Modulo_Map WHERE id_modulo = 12)
BEGIN
    INSERT dbo.CORE_Roles_Modulo_Map (id_modulo, id_rol_portal, id_rol_externo)
    SELECT 12, r.id_rol, x.id_rol_externo
    FROM (VALUES ('ADMIN', 1), ('BIENES_SUPERVISOR', 2), ('BIENES_OPERADOR', 3), ('BIENES_AUDITOR', 4)) AS x(codigo, id_rol_externo)
    JOIN dbo.CORE_Roles r ON r.codigo = x.codigo;
END;
GO

-- Retirar los 2 nodos-esqueleto de Bienes antes de sembrar el árbol real
-- (protegido: solo corre si el árbol real, identificado por el nodo
-- "Dashboard", todavía no existe). Los nodos viejos ya tienen permisos
-- reales de 6 roles -- se guardan primero y se re-otorgan sobre "Dashboard".
IF EXISTS (SELECT 1 FROM dbo.CORE_Menu_Nodos WHERE id_modulo=12 AND opcion>=1)
   AND NOT EXISTS (SELECT 1 FROM dbo.CORE_Menu_Nodos WHERE id_modulo=12 AND descripcion='Dashboard')
BEGIN
    SELECT id_rol, MAX(nivel_crud) AS nivel_crud
    INTO #PermisosBienesViejos
    FROM dbo.CORE_Permisos_Nodo
    WHERE id_modulo=12
    GROUP BY id_rol;

    DELETE pn FROM dbo.CORE_Permisos_Nodo pn
        JOIN dbo.CORE_Menu_Nodos mn ON mn.id_modulo=pn.id_modulo AND mn.opcion=pn.opcion AND mn.items=pn.items AND mn.subitems=pn.subitems
        WHERE mn.id_modulo=12;
    DELETE FROM dbo.CORE_Menu_Nodos WHERE id_modulo=12 AND opcion>=1;
END;
GO

IF NOT EXISTS (SELECT 1 FROM dbo.CORE_Menu_Nodos WHERE id_modulo=12 AND descripcion='Dashboard')
BEGIN
    INSERT dbo.CORE_Menu_Nodos (id_modulo, opcion, items, subitems, descripcion, url_ruta, icono, orden, requiere_mfa, target_spa, estado) VALUES
    (12, 1, 0, 0, 'Dashboard',                 'apps/control_bienes/index.php?route=dashboard',           'fa-gauge-high',     1,  0, 0, 1),
    (12, 2, 0, 0, 'Inventario General',        'apps/control_bienes/index.php?route=inventario',          'fa-ship',           2,  0, 0, 1),
    (12, 3, 0, 0, 'Catálogo de Ítems',         'apps/control_bienes/index.php?route=items',               'fa-box',            3,  0, 0, 1),
    (12, 4, 0, 0, 'Ítems del Sistema',         'apps/control_bienes/index.php?route=inv_items_sistema',   'fa-cubes',          4,  0, 0, 1),
    (12, 5, 0, 0, 'Tablas de Cabecera',        'apps/control_bienes/index.php?route=cabeceras',           'fa-table-columns',  5,  0, 0, 1),
    (12, 6, 0, 0, 'Maestros',                  'apps/control_bienes/index.php?route=inv_maestros',        'fa-layer-group',    6,  0, 0, 1),
    (12, 7, 0, 0, 'Ingresos de Bodega',        'apps/control_bienes/index.php?route=ingresos',             'fa-truck-ramp-box', 7,  0, 0, 1),
    (12, 8, 0, 0, 'Egresos de Bodega',         'apps/control_bienes/index.php?route=egresos',              'fa-truck-arrow-right', 8, 0, 0, 1),
    (12, 9, 0, 0, 'Directorio de Personal',    'apps/control_bienes/index.php?route=talento_directorio',   'fa-users',          9,  0, 0, 1),
    (12, 10,0, 0, 'Bitácora del Sistema',      'apps/control_bienes/index.php?route=inv_bitacora',         'fa-clock-rotate-left', 10, 1, 0, 1),
    (12, 11,0, 0, 'Reportes Varios',           'apps/control_bienes/index.php?route=reportes',             'fa-chart-pie',      11, 0, 0, 1),
    (12, 12,0, 0, 'Períodos e IVA',            'apps/control_bienes/index.php?route=inv_periodos',         'fa-calendar-days',  12, 1, 0, 1),
    (12, 13,0, 0, 'Secuenciales de Índice',    'apps/control_bienes/index.php?route=inv_secuenciales',     'fa-list-ol',        13, 1, 0, 1),
    (12, 14,0, 0, 'Gestión de Usuarios',       'apps/control_bienes/index.php?route=usuarios',             'fa-user-gear',      14, 1, 0, 1),
    (12, 15,0, 0, 'Gestión de Permisos',       'apps/control_bienes/index.php?route=inv_permisos',         'fa-key',            15, 1, 0, 1);
END;
GO

IF OBJECT_ID('tempdb..#PermisosBienesViejos') IS NOT NULL
BEGIN
    INSERT dbo.CORE_Permisos_Nodo (id_rol, id_modulo, opcion, items, subitems, nivel_crud, acceso, estado, fecha_asignacion)
    SELECT v.id_rol, 12, 1, 0, 0, v.nivel_crud, 1, 1, SYSDATETIME()
    FROM #PermisosBienesViejos v
    WHERE NOT EXISTS (
        SELECT 1 FROM dbo.CORE_Permisos_Nodo p
        WHERE p.id_rol=v.id_rol AND p.id_modulo=12 AND p.opcion=1 AND p.items=0 AND p.subitems=0
    );
    DROP TABLE #PermisosBienesViejos;
END;
GO
```

- [ ] **Step 2: Backup de PORTAL_APM antes de aplicar**

```powershell
php -r "
\$conn = require 'config/connections.php';
\$c = sqlsrv_connect(\$conn['server_default'], ['TrustServerCertificate'=>true]);
\$stmt = sqlsrv_query(\$c, \"SELECT SERVERPROPERTY('InstanceDefaultBackupPath') AS p\");
\$row = sqlsrv_fetch_array(\$stmt, SQLSRV_FETCH_ASSOC);
echo \$row['p'] . PHP_EOL;
"
```
Con esa ruta, ejecutar (ajustar `<ruta>`):
```powershell
php -r "
\$conn = require 'config/connections.php';
\$c = sqlsrv_connect(\$conn['server_default'], ['TrustServerCertificate'=>true]);
sqlsrv_query(\$c, \"BACKUP DATABASE [PORTAL_APM] TO DISK='<ruta>\PORTAL_APM_prefase2.bak'\");
print_r(sqlsrv_errors());
"
```

- [ ] **Step 3: Aplicar el script**

```powershell
php db/run_sql.php db/permisos_centrales_fase2_bienes_portal.sql <servidor>
```
Expected: `OK: N batches ejecutados correctamente.`

- [ ] **Step 4: Verificar con queries directas**

```powershell
php -r "
\$conn = require 'config/connections.php';
\$c = sqlsrv_connect(\$conn['server_default'], ['CharacterSet'=>'UTF-8','TrustServerCertificate'=>true,'Database'=>'PORTAL_APM']);
echo '-- roles nuevos --' . PHP_EOL;
\$s = sqlsrv_query(\$c, \"SELECT id_rol, codigo FROM CORE_Roles WHERE codigo LIKE 'BIENES_%'\");
while (\$r = sqlsrv_fetch_array(\$s, SQLSRV_FETCH_ASSOC)) { echo json_encode(\$r) . PHP_EOL; }
echo '-- mapeo --' . PHP_EOL;
\$s = sqlsrv_query(\$c, 'SELECT * FROM CORE_Roles_Modulo_Map WHERE id_modulo=12');
while (\$r = sqlsrv_fetch_array(\$s, SQLSRV_FETCH_ASSOC)) { echo json_encode(\$r) . PHP_EOL; }
echo '-- nodos (esperado 15) --' . PHP_EOL;
\$s = sqlsrv_query(\$c, 'SELECT COUNT(*) n FROM CORE_Menu_Nodos WHERE id_modulo=12 AND opcion>=1');
\$r = sqlsrv_fetch_array(\$s, SQLSRV_FETCH_ASSOC); echo \$r['n'] . PHP_EOL;
echo '-- permisos preservados sobre Dashboard (esperado 6 roles) --' . PHP_EOL;
\$s = sqlsrv_query(\$c, 'SELECT id_rol, nivel_crud FROM CORE_Permisos_Nodo WHERE id_modulo=12 AND opcion=1');
while (\$r = sqlsrv_fetch_array(\$s, SQLSRV_FETCH_ASSOC)) { echo json_encode(\$r) . PHP_EOL; }
"
```
Expected: 3 roles nuevos, 4 filas de mapeo, 15 nodos, 6 filas de permisos con los mismos `id_rol`/`nivel_crud` que existían antes de la migración (1→4, 2→1, 13→1, 14→1, 15→4, 16→2).

---

### Task 2: Migración SQL — lado nativo (`inventario`)

**Files:**
- Create: `db/permisos_centrales_fase2_bienes_inventario.sql`

**Interfaces:**
- Produces: `inv_roles` (4 filas), `inv_permisos_rol` (vacía, estructura lista), `inv_permisos.nivel_crud` (columna nueva, backfill a 1), `inv_usuarios.rol_id` (columna nueva, backfill desde el string `rol`).

- [ ] **Step 1: Escribir el script**

```sql
/* db/permisos_centrales_fase2_bienes_inventario.sql
   Fase 2: Control de Bienes, lado nativo (BD inventario). Idempotente. */
USE [inventario];
GO
SET ANSI_NULLS ON;
SET QUOTED_IDENTIFIER ON;
GO

IF OBJECT_ID(N'dbo.inv_roles', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.inv_roles (
        id     INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
        nombre NVARCHAR(50) NOT NULL UNIQUE
    );
    INSERT dbo.inv_roles (nombre) VALUES ('Administrador'), ('Supervisor'), ('Operador'), ('Auditor');
END;
GO

IF OBJECT_ID(N'dbo.inv_permisos_rol', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.inv_permisos_rol (
        id                 INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
        rol_id             INT NOT NULL,
        route_key          NVARCHAR(255) NOT NULL,
        puede_visualizar   BIT NOT NULL DEFAULT 0,
        puede_crear        BIT NOT NULL DEFAULT 0,
        puede_editar       BIT NOT NULL DEFAULT 0,
        puede_eliminar     BIT NOT NULL DEFAULT 0,
        CONSTRAINT UQ_inv_permisos_rol UNIQUE (rol_id, route_key),
        CONSTRAINT FK_inv_permisos_rol_rol FOREIGN KEY (rol_id) REFERENCES dbo.inv_roles(id)
    );
END;
GO

IF NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='inv_permisos' AND COLUMN_NAME='nivel_crud')
BEGIN
    ALTER TABLE dbo.inv_permisos ADD nivel_crud TINYINT NOT NULL DEFAULT 1;
END;
GO

IF NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='inv_usuarios' AND COLUMN_NAME='rol_id')
BEGIN
    ALTER TABLE dbo.inv_usuarios ADD rol_id INT NULL;
    ALTER TABLE dbo.inv_usuarios ADD CONSTRAINT FK_inv_usuarios_rol FOREIGN KEY (rol_id) REFERENCES dbo.inv_roles(id);

    UPDATE u SET u.rol_id = r.id
    FROM dbo.inv_usuarios u
    JOIN dbo.inv_roles r ON r.nombre = u.rol;
END;
GO
```

- [ ] **Step 2: Backup de `inventario` antes de aplicar**

```powershell
php -r "
\$conn = require 'config/connections.php';
\$c = sqlsrv_connect(\$conn['server_default'], ['TrustServerCertificate'=>true]);
sqlsrv_query(\$c, \"BACKUP DATABASE [inventario] TO DISK='<misma-ruta-de-Task1>\inventario_prefase2.bak'\");
print_r(sqlsrv_errors());
"
```

- [ ] **Step 3: Aplicar el script**

```powershell
php db/run_sql.php db/permisos_centrales_fase2_bienes_inventario.sql <servidor>
```

- [ ] **Step 4: Verificar**

```powershell
php -r "
\$conn = require 'config/connections.php';
\$c = sqlsrv_connect(\$conn['server_default'], ['CharacterSet'=>'UTF-8','TrustServerCertificate'=>true,'Database'=>'inventario']);
echo '-- inv_roles --' . PHP_EOL;
\$s = sqlsrv_query(\$c, 'SELECT * FROM inv_roles'); while (\$r = sqlsrv_fetch_array(\$s, SQLSRV_FETCH_ASSOC)) { echo json_encode(\$r) . PHP_EOL; }
echo '-- inv_usuarios.rol_id backfill (4 filas, todas con rol_id no nulo) --' . PHP_EOL;
\$s = sqlsrv_query(\$c, 'SELECT id, usuario, rol, rol_id FROM inv_usuarios'); while (\$r = sqlsrv_fetch_array(\$s, SQLSRV_FETCH_ASSOC)) { echo json_encode(\$r) . PHP_EOL; }
echo '-- inv_permisos.nivel_crud backfill (2 filas existentes, nivel_crud=1) --' . PHP_EOL;
\$s = sqlsrv_query(\$c, 'SELECT usuario_id, route_key, nivel_crud FROM inv_permisos'); while (\$r = sqlsrv_fetch_array(\$s, SQLSRV_FETCH_ASSOC)) { echo json_encode(\$r) . PHP_EOL; }
"
```
Expected: 4 roles; 4 usuarios todos con `rol_id` resuelto (ninguno NULL); 2 filas de `inv_permisos` con `nivel_crud=1`.

- [ ] **Step 5: Espejo en `Database.php` para instalaciones nuevas (no aplica a la BD real, solo código)**

Modificar `apps/control_bienes/core/Database.php`:
1. En `migrarBaseDatosExistenteSQLServer()` (después del bloque de `inv_categorias`, antes del `catch`), agregar:
```php
$checkRoles = $this->pdo->query("SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = 'inv_roles'");
if ($checkRoles === false || $checkRoles->fetchColumn() === false) {
    $this->pdo->exec("CREATE TABLE inv_roles (
        id INT IDENTITY(1,1) PRIMARY KEY,
        nombre NVARCHAR(50) NOT NULL UNIQUE
    );");
    $this->pdo->exec("INSERT INTO inv_roles (nombre) VALUES ('Administrador'), ('Supervisor'), ('Operador'), ('Auditor');");
}

$checkPermRol = $this->pdo->query("SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = 'inv_permisos_rol'");
if ($checkPermRol === false || $checkPermRol->fetchColumn() === false) {
    $this->pdo->exec("CREATE TABLE inv_permisos_rol (
        id INT IDENTITY(1,1) PRIMARY KEY,
        rol_id INT NOT NULL,
        route_key NVARCHAR(255) NOT NULL,
        puede_visualizar BIT NOT NULL DEFAULT 0,
        puede_crear BIT NOT NULL DEFAULT 0,
        puede_editar BIT NOT NULL DEFAULT 0,
        puede_eliminar BIT NOT NULL DEFAULT 0,
        CONSTRAINT UQ_inv_permisos_rol UNIQUE (rol_id, route_key),
        FOREIGN KEY (rol_id) REFERENCES inv_roles(id)
    );");
}

$checkPermCols = $this->pdo->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'inv_permisos'");
$permCols = $checkPermCols->fetchAll(PDO::FETCH_COLUMN);
if (!in_array('nivel_crud', $permCols)) {
    $this->pdo->exec("ALTER TABLE inv_permisos ADD nivel_crud TINYINT NOT NULL DEFAULT 1;");
}

$checkUsrCols = $this->pdo->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'inv_usuarios'");
$usrCols = $checkUsrCols->fetchAll(PDO::FETCH_COLUMN);
if (!in_array('rol_id', $usrCols)) {
    $this->pdo->exec("ALTER TABLE inv_usuarios ADD rol_id INT NULL;");
    $this->pdo->exec("ALTER TABLE inv_usuarios ADD CONSTRAINT FK_inv_usuarios_rol FOREIGN KEY (rol_id) REFERENCES inv_roles(id);");
    $this->pdo->exec("UPDATE u SET u.rol_id = r.id FROM inv_usuarios u JOIN inv_roles r ON r.nombre = u.rol;");
}
```
2. En `inicializarBaseDatosSQLServer()` (fresh install desde cero), agregar las mismas 2 tablas al array `$queries` (antes de `inv_permisos`, ya que `inv_permisos_rol` no depende de `inv_permisos`) y agregar `nivel_crud TINYINT NOT NULL DEFAULT 1` + `rol_id INT REFERENCES inv_roles(id)` directamente en las definiciones `CREATE TABLE inv_permisos` / `CREATE TABLE inv_usuarios` de ese método (para que una instalación 100% nueva nazca ya con las columnas, sin pasar por el camino de migración).

- [ ] **Step 6: `php -l` de Database.php**

```powershell
php -l apps/control_bienes/core/Database.php
```
Expected: `No syntax errors detected`

---

### Task 3: `PermisoModel` — cascada nativa + soporte de rol

**Files:**
- Modify: `apps/control_bienes/modules/Credenciales/models/PermisoModel.php`

**Interfaces:**
- Consumes: tablas `inv_roles`, `inv_permisos_rol`, `inv_permisos.nivel_crud` (Task 2).
- Produces (usado por Task 5 `Router`, Task 6 `layout.php`, Task 9 `PermisoController`):
  - `nivelEfectivoNativo(int $usuarioId, int $rolId, string $routeKey): int`
  - `tieneNivelNativo(int $usuarioId, int $rolId, string $rolNombre, string $routeKey, int $nivelMin): bool`
  - `obtenerNivelesUsuario(int $usuarioId): array` (reemplaza a `obtenerPermisosUsuario`, ahora retorna `['route_key' => nivel_crud]`)
  - `actualizarPermisos(int $usuarioId, array $niveles): void` (firma cambiada: `$niveles` es `['route_key' => nivel_crud]`, ya no lista plana)
  - `listarRoles(): array`
  - `nivelesPorRol(int $rolId): array` (`['route_key' => nivel_crud]`)
  - `guardarPermisosRol(int $rolId, array $niveles): void`

- [ ] **Step 1: Reescribir el modelo completo**

```php
<?php
/**
 * PERMISOMODEL.PHP - Modelo de Permisos por Rol y por Usuario
 * inv_permisos_rol = permisos por rol (nuevo). inv_permisos = override
 * individual por usuario nativo (upgradeado a nivel_crud real).
 */

require_once ROOT_PATH . 'core/Model.php';

class PermisoModel extends Model {

    public function __construct() {
        parent::__construct();
    }

    /** Nivel de override individual del usuario para esa route, 0 si no hay fila. */
    private function nivelUsuario(int $usuarioId, string $routeKey): int {
        $stmt = $this->db->prepare("SELECT nivel_crud FROM inv_permisos WHERE usuario_id = :uid AND route_key = :rk");
        $stmt->execute([':uid' => $usuarioId, ':rk' => $routeKey]);
        $v = $stmt->fetchColumn();
        return $v === false ? 0 : (int)$v;
    }

    /** Nivel del rol para esa route, 0 si no hay fila. */
    private function nivelRol(int $rolId, string $routeKey): int {
        $stmt = $this->db->prepare("SELECT
                CASE WHEN puede_eliminar=1 THEN 4 WHEN puede_editar=1 THEN 3
                     WHEN puede_crear=1 THEN 2 WHEN puede_visualizar=1 THEN 1 ELSE 0 END AS nivel
            FROM inv_permisos_rol WHERE rol_id = :rid AND route_key = :rk");
        $stmt->execute([':rid' => $rolId, ':rk' => $routeKey]);
        $v = $stmt->fetchColumn();
        return $v === false ? 0 : (int)$v;
    }

    /** Cascada usuario > rol para cuentas NATIVAS de Bienes (sin puente al portal). */
    public function nivelEfectivoNativo(int $usuarioId, int $rolId, string $routeKey): int {
        $nivelUsr = $this->nivelUsuario($usuarioId, $routeKey);
        if ($nivelUsr > 0) {
            return $nivelUsr;
        }
        return $this->nivelRol($rolId, $routeKey);
    }

    /** true si el nivel efectivo (nativo) cubre $nivelMin. Administrador siempre pasa. */
    public function tieneNivelNativo(int $usuarioId, int $rolId, string $rolNombre, string $routeKey, int $nivelMin): bool {
        if (strtolower($rolNombre) === 'administrador') {
            return true;
        }
        return $this->nivelEfectivoNativo($usuarioId, $rolId, $routeKey) >= $nivelMin;
    }

    /** ['route_key' => nivel_crud] del override individual de un usuario. */
    public function obtenerNivelesUsuario(int $usuarioId): array {
        $stmt = $this->db->prepare("SELECT route_key, nivel_crud FROM inv_permisos WHERE usuario_id = :uid");
        $stmt->execute([':uid' => $usuarioId]);
        $out = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $out[$row['route_key']] = (int)$row['nivel_crud'];
        }
        return $out;
    }

    /** Reemplaza el override individual completo de un usuario. $niveles = ['route_key' => nivel_crud 1-4]. */
    public function actualizarPermisos(int $usuarioId, array $niveles): void {
        $del = $this->db->prepare("DELETE FROM inv_permisos WHERE usuario_id = :uid");
        $del->execute([':uid' => $usuarioId]);

        if (!empty($niveles)) {
            $ins = $this->db->prepare("INSERT INTO inv_permisos (usuario_id, route_key, nivel_crud) VALUES (:uid, :rk, :nv)");
            foreach ($niveles as $rk => $nivel) {
                $nivel = (int)$nivel;
                $rk = trim((string)$rk);
                if ($rk === '' || $nivel < 1 || $nivel > 4) continue;
                $ins->execute([':uid' => $usuarioId, ':rk' => $rk, ':nv' => $nivel]);
            }
        }
    }

    /** Los 4 roles nativos de Bienes. */
    public function listarRoles(): array {
        $stmt = $this->db->query("SELECT id, nombre FROM inv_roles ORDER BY id");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function rolIdPorNombre(string $nombre): ?int {
        $stmt = $this->db->prepare("SELECT id FROM inv_roles WHERE nombre = :n");
        $stmt->execute([':n' => $nombre]);
        $v = $stmt->fetchColumn();
        return $v === false ? null : (int)$v;
    }

    /** ['route_key' => nivel_crud] de un rol. */
    public function nivelesPorRol(int $rolId): array {
        $stmt = $this->db->prepare("SELECT route_key,
                CASE WHEN puede_eliminar=1 THEN 4 WHEN puede_editar=1 THEN 3
                     WHEN puede_crear=1 THEN 2 WHEN puede_visualizar=1 THEN 1 ELSE 0 END AS nivel
            FROM inv_permisos_rol WHERE rol_id = :rid");
        $stmt->execute([':rid' => $rolId]);
        $out = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $out[$row['route_key']] = (int)$row['nivel'];
        }
        return $out;
    }

    /** Reemplaza los permisos completos de un rol. $niveles = ['route_key' => nivel_crud 0-4]. */
    public function guardarPermisosRol(int $rolId, array $niveles): void {
        $del = $this->db->prepare("DELETE FROM inv_permisos_rol WHERE rol_id = :rid");
        $del->execute([':rid' => $rolId]);

        $ins = $this->db->prepare(
            "INSERT INTO inv_permisos_rol (rol_id, route_key, puede_visualizar, puede_crear, puede_editar, puede_eliminar)
             VALUES (:rid, :rk, :v, :c, :e, :d)"
        );
        foreach ($niveles as $rk => $nivel) {
            $nivel = (int)$nivel;
            $rk = trim((string)$rk);
            if ($rk === '' || $nivel < 1) continue;
            $ins->execute([
                ':rid' => $rolId, ':rk' => $rk,
                ':v' => $nivel >= 1 ? 1 : 0, ':c' => $nivel >= 2 ? 1 : 0,
                ':e' => $nivel >= 3 ? 1 : 0, ':d' => $nivel >= 4 ? 1 : 0,
            ]);
        }
    }

    /**
     * Obtiene los permisos de todos los usuarios (para la pantalla de admin)
     */
    public function obtenerTodosLosPermisos(): array {
        $stmt = $this->db->query(
            "SELECT p.usuario_id, p.route_key, p.nivel_crud, u.nombre
             FROM inv_permisos p
             JOIN inv_usuarios u ON p.usuario_id = u.id
             ORDER BY u.nombre, p.route_key"
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

class InvPermiso extends PermisoModel {}
```

Nota: se retira `crearTablasSiNoExisten()` (el `CREATE TABLE IF NOT EXISTS inv_permisos` original) — la tabla y su nueva columna ya las garantiza `Database.php` (Task 2 Step 5), duplicar la creación aquí divergiría del esquema real (le faltaría `nivel_crud`).

- [ ] **Step 2: `php -l`**

```powershell
php -l apps/control_bienes/modules/Credenciales/models/PermisoModel.php
```
Expected: `No syntax errors detected`

---

### Task 4: `Controller::requirePermisoBienes()` — puente cross-DB

**Files:**
- Modify: `apps/control_bienes/core/Controller.php`

**Interfaces:**
- Consumes: `fn_TienePermisoNodo` (`PORTAL_APM`, ya existe), `config/connections.php` (raíz del portal).
- Produces: `Controller::tienePermisoPortal(int $idUsuarioPortal, int $opcion, int $nivelMin): bool` — usado por `Router::checkPermisos()` (Task 5).

- [ ] **Step 1: Agregar el método al final de la clase, antes del cierre `}`**

```php
    /**
     * Consulta fn_TienePermisoNodo del portal (PORTAL_APM.dbo) en cross-DB
     * same-instance (nombre de 3 partes, sin linked server) para una sesión
     * puenteada desde el portal. id_modulo=12 fijo (Control de Bienes).
     */
    protected function tienePermisoPortal(int $idUsuarioPortal, int $opcion, int $nivelMin): bool {
        try {
            $conexionesPath = dirname(ROOT_PATH, 3) . '/config/connections.php';
            if (!is_file($conexionesPath)) return false;
            $conn = require $conexionesPath;

            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare(
                "SELECT {$conn['databases']['portal']['name']}.dbo.fn_TienePermisoNodo(?,12,?,0,0,?,1) AS ok"
            );
            $stmt->execute([$idUsuarioPortal, $opcion, $nivelMin]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row !== false && (bool)$row['ok'];
        } catch (Exception $e) {
            return false;
        }
    }
```

`dirname(ROOT_PATH, 3)`: `ROOT_PATH` en Bienes apunta a `apps/control_bienes/` (con slash final, confirmar en `config/globals.php` si difiere) — 3 niveles arriba de `apps/control_bienes/` es la raíz del portal (`apps/control_bienes` → `apps` → raíz). Verificar el valor real de `ROOT_PATH` antes de fijar el número de niveles (Step 2).

- [ ] **Step 2: Verificar `ROOT_PATH` y el nombre de la clave de BD portal en `connections.php`**

```powershell
php -r "require 'apps/control_bienes/config/globals.php'; echo ROOT_PATH . PHP_EOL;"
php -r "print_r((require 'config/connections.php')['databases']['portal'] ?? 'NO EXISTE LA CLAVE portal');"
```
Si `ROOT_PATH` no es `.../apps/control_bienes/` con exactamente ese número de segmentos hasta la raíz del repo, ajustar el `dirname(ROOT_PATH, N)` del Step 1. Si `connections.php` no tiene una clave `databases.portal`, usar el nombre literal `PORTAL_APM` en el SQL en vez de interpolar `$conn['databases']['portal']['name']`.

- [ ] **Step 3: `php -l`**

```powershell
php -l apps/control_bienes/core/Controller.php
```
Expected: `No syntax errors detected`

---

### Task 5: `Router::checkPermisos()` — tabla de políticas + rama dual

**Files:**
- Modify: `apps/control_bienes/core/Router.php:150-187`

**Interfaces:**
- Consumes: `PermisoModel::tieneNivelNativo()` (Task 3), `Controller::tienePermisoPortal()` (Task 4) — instanciado vía un controlador temporal ya que el método vive en `Controller`, no en `Router` (`Router` no extiende `Controller`); más simple: mover la lógica cross-DB a un método público estático o duplicar la consulta directamente en `Router` usando PDO. Se opta por lo segundo — `Router` ya tiene acceso a `Database::getInstance()`.
- Produces: gating real de escritura para las 13 `route` no públicas.

- [ ] **Step 1: Reemplazar `checkPermisos()` completo**

```php
    /**
     * Tabla de políticas (route, action) -> (opción MOIS bajo id_modulo=12,
     * nivel_crud mínimo). Las acciones no listadas para una route caen al
     * 'default' de esa route (típicamente 1 = solo Ver). Sin entrada para
     * la route completa -> no gateada aquí (ver $rutasPublicas en dispatch()).
     */
    private const POLITICAS = [
        'dashboard'          => ['opcion' => 1,  'default' => 1, 'acciones' => []],
        'inventario'         => ['opcion' => 2,  'default' => 1, 'acciones' => ['guardar' => 'crud', 'eliminar' => 4]],
        'items'              => ['opcion' => 3,  'default' => 1, 'acciones' => ['guardar' => 'crud', 'eliminar' => 4]],
        'inv_items_sistema'  => ['opcion' => 4,  'default' => 1, 'acciones' => ['guardar' => 'crud']],
        'cabeceras'          => ['opcion' => 5,  'default' => 1, 'acciones' => ['guardar' => 'crud', 'eliminar' => 4]],
        'inv_maestros'       => ['opcion' => 6,  'default' => 1, 'acciones' => ['guardar' => 'crud', 'eliminar' => 4]],
        'ingresos'           => ['opcion' => 7,  'default' => 1, 'acciones' => ['guardar' => 2]],
        'egresos'            => ['opcion' => 8,  'default' => 1, 'acciones' => ['guardar' => 2]],
        'talento'            => ['opcion' => 9,  'default' => 1, 'acciones' => []],
        'talento_directorio' => ['opcion' => 9,  'default' => 1, 'acciones' => []],
        'talento_crear'      => ['opcion' => 9,  'default' => 2, 'acciones' => []],
        'talento_guardar'    => ['opcion' => 9,  'default' => 1, 'acciones' => ['guardar' => 'crud']],
        'talento_editar'     => ['opcion' => 9,  'default' => 3, 'acciones' => []],
        'talento_borrar'     => ['opcion' => 9,  'default' => 4, 'acciones' => []],
        'talento_eliminar'   => ['opcion' => 9,  'default' => 4, 'acciones' => []],
        'talento_imprimir_ficha' => ['opcion' => 9, 'default' => 1, 'acciones' => []],
        'inv_bitacora'       => ['opcion' => 10, 'default' => 1, 'acciones' => []],
        'reportes'           => ['opcion' => 11, 'default' => 1, 'acciones' => []],
        'inv_periodos'       => ['opcion' => 12, 'default' => 1, 'acciones' => ['guardar' => 2, 'ejecutarCorte' => 3]],
        'inv_secuenciales'   => ['opcion' => 13, 'default' => 1, 'acciones' => ['test' => 3, 'reiniciar' => 3]],
        'usuarios'           => ['opcion' => 14, 'default' => 1, 'acciones' => ['guardar' => 'crud', 'eliminar' => 4, 'guardarParametro' => 4]],
        'inv_permisos'       => ['opcion' => 15, 'default' => 1, 'acciones' => ['guardar' => 3, 'obtenerPermisos' => 1]],
    ];

    /**
     * Middleware de Permisos: rama dual segun el tipo de cuenta.
     * - Puenteada desde el portal (tiene user_id del portal en sesion):
     *   fn_TienePermisoNodo cross-DB, ya resuelve rol + override de usuario.
     * - Nativa de Bienes (inv_usuarios, sin cedula real en el portal):
     *   cascada local usuario > rol contra inv_permisos / inv_permisos_rol.
     */
    private function checkPermisos(string $route) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $rol = isset($_SESSION['rol']) ? $_SESSION['rol'] : '';
        if (strtolower($rol) === 'administrador') {
            return;
        }

        $politica = self::POLITICAS[$route] ?? null;
        if ($politica === null) {
            // Route sin politica declarada (ej. inv_lookup): sin gating adicional.
            return;
        }

        $action = isset($_GET['action']) ? $_GET['action'] : '';
        $nivelMin = $politica['default'];
        if ($action !== '' && array_key_exists($action, $politica['acciones'])) {
            $spec = $politica['acciones'][$action];
            if ($spec === 'crud') {
                $tieneId = !empty($_POST['id']) || !empty($_GET['id']);
                $nivelMin = $tieneId ? 3 : 2;
            } else {
                $nivelMin = (int)$spec;
            }
        }

        $puenteada = !empty($_SESSION['user_id']);
        $ok = false;

        if ($puenteada) {
            require_once ROOT_PATH . 'core/Controller.php';
            require_once ROOT_PATH . 'core/Model.php';
            $probe = new class extends Controller {
                public function check(int $idUsuario, int $opcion, int $nivelMin): bool {
                    return $this->tienePermisoPortal($idUsuario, $opcion, $nivelMin);
                }
            };
            $ok = $probe->check((int)$_SESSION['user_id'], $politica['opcion'], $nivelMin);
        } else {
            $usuarioId = isset($_SESSION['usuario_id']) ? (int)$_SESSION['usuario_id'] : 0;
            $rolId = isset($_SESSION['usuario']['rol_id']) ? (int)$_SESSION['usuario']['rol_id'] : 0;
            if ($usuarioId > 0) {
                require_once ROOT_PATH . 'modules/Credenciales/models/PermisoModel.php';
                $permisoModel = new PermisoModel();
                $ok = $permisoModel->tieneNivelNativo($usuarioId, $rolId, $rol, $route, $nivelMin);
            }
        }

        if (!$ok) {
            $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH'])
                      || isset($_GET['is_ajax'])
                      || isset($_POST['is_ajax'])
                      || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

            if ($isAjax) {
                header('Content-Type: application/json');
                http_response_code(403);
                echo json_encode(['error' => 'Acceso denegado', 'route' => $route]);
                exit;
            }

            $_SESSION['toast'] = [
                'mensaje' => 'No tienes permiso para realizar esta acción. Contacta al Administrador.',
                'tipo'    => 'error'
            ];
            header('Location: index.php?route=inventario');
            exit;
        }
    }
```

`$_SESSION['usuario']['rol_id']`: hay que asegurarse de que el login nativo (`AuthController::loginPost`, Task 7) guarde `rol_id` dentro de `$_SESSION['usuario']` al autenticar — confirmar/agregar en Task 7.

- [ ] **Step 2: `php -l`**

```powershell
php -l apps/control_bienes/core/Router.php
```
Expected: `No syntax errors detected`

- [ ] **Step 3: Confirmar que `usuarios` ya no incluye `index` como escritura**

Releer la tabla `POLITICAS` contra el inventario de métodos de `UsuarioController`/`ConfigController`/`EstacionController`/`MonitoreoController`/`BinController` (ver "Contexto" arriba) antes de continuar — todo método de solo lectura debe caer al `default` (nivel 1), todo método que hace INSERT/UPDATE/DELETE debe estar explícito en `acciones`. Si al releer alguno de esos controladores aparece un método de escritura no listado, agregarlo a `POLITICAS` ahora, antes de pasar a Task 6.

---

### Task 6: Login nativo — guardar `rol_id` en sesión

**Files:**
- Modify: `apps/control_bienes/modules/Credenciales/controllers/AuthController.php`

**Interfaces:**
- Consumes: `inv_usuarios.rol_id` (Task 2).
- Produces: `$_SESSION['usuario']['rol_id']` disponible para `Router::checkPermisos()` (Task 5) y `layout.php` (Task 8).

- [ ] **Step 1: Ubicar el método de login y el punto donde arma `$_SESSION['usuario']`**

```powershell
grep -n "SESSION\['usuario'\]" apps/control_bienes/modules/Credenciales/controllers/AuthController.php
```

- [ ] **Step 2: Agregar `rol_id` al array de sesión**

En el bloque donde se puebla `$_SESSION['usuario'] = [...]` tras un login exitoso, agregar la clave `'rol_id' => (int)($usuario['rol_id'] ?? 0)` (el nombre exacto del array `$usuario` obtenido de `UsuarioModel` puede variar — confirmar contra `UsuarioModel::autenticar()`/`buscarPorUsuario()` qué columnas trae; `rol_id` ya existe en `inv_usuarios` desde Task 2, debe venir en el SELECT del modelo — si el modelo usa `SELECT *` no requiere cambio ahí, si usa columnas explícitas agregar `rol_id` a esa lista).

- [ ] **Step 3: `php -l` de ambos archivos tocados**

```powershell
php -l apps/control_bienes/modules/Credenciales/controllers/AuthController.php
php -l apps/control_bienes/modules/Credenciales/models/UsuarioModel.php
```
Expected: `No syntax errors detected` en ambos.

---

### Task 7: `layout.php` — sidebar con cascada dual

**Files:**
- Modify: `apps/control_bienes/modules/Central/views/layout.php:264-322`

**Interfaces:**
- Consumes: `PermisoModel::nivelEfectivoNativo()` (Task 3), `Controller::tienePermisoPortal()` (Task 4) — vía la misma técnica de instancia anónima que `Router` (Task 5), o extrayendo un helper compartido si se prefiere evitar duplicación (ver Step 2).
- Produces: sidebar filtrado por nivel real en vez de lista binaria de rutas.

- [ ] **Step 1: Reemplazar el bloque de carga de permisos y el filtro de items**

Sustituir (líneas 278-297 aprox., desde `// Cargar inv_permisos del usuario actual` hasta el cierre del `foreach ($menuItems as $seccion => $info):` que arma `$itemsVisibles`):

```php
            <?php
            // Nivel efectivo por route (MOIS opcion via POLITICAS de Router, cascada dual)
            $nivelesPorRoute = [];
            if (!$esAdminActual) {
                require_once ROOT_PATH . 'core/Controller.php';
                require_once ROOT_PATH . 'modules/Credenciales/models/PermisoModel.php';
                $rutaAOpcion = [
                    'inventario' => 2, 'items' => 3, 'inv_items_sistema' => 4,
                    'cabeceras' => 5, 'inv_maestros' => 6, 'ingresos' => 7, 'egresos' => 8,
                    'talento_directorio' => 9, 'inv_bitacora' => 10, 'reportes' => 11,
                    'inv_periodos' => 12, 'inv_secuenciales' => 13, 'usuarios' => 14, 'inv_permisos' => 15,
                ];
                $puenteada = !empty($_SESSION['user_id']);
                if ($puenteada) {
                    $probeLayout = new class extends Controller {
                        public function nivel(int $idUsuario, int $opcion): int {
                            for ($n = 4; $n >= 1; $n--) {
                                if ($this->tienePermisoPortal($idUsuario, $opcion, $n)) return $n;
                            }
                            return 0;
                        }
                    };
                    foreach ($rutaAOpcion as $rk => $op) {
                        $nivelesPorRoute[$rk] = $probeLayout->nivel((int)$_SESSION['user_id'], $op);
                    }
                } else {
                    $usuarioIdActual = isset($_SESSION['usuario_id']) ? (int)$_SESSION['usuario_id'] : 0;
                    $rolIdActual = isset($_SESSION['usuario']['rol_id']) ? (int)$_SESSION['usuario']['rol_id'] : 0;
                    if ($usuarioIdActual > 0) {
                        $_permisoModel = new PermisoModel();
                        foreach ($rutaAOpcion as $rk => $op) {
                            $nivelesPorRoute[$rk] = $_permisoModel->nivelEfectivoNativo($usuarioIdActual, $rolIdActual, $rk);
                        }
                    }
                }
            }
            foreach ($menuItems as $seccion => $info):
                // Filtrar los items de esta sección según el nivel efectivo (>=1 = visible)
                $itemsVisibles = [];
                foreach ($info['items'] as $routeKey => $item) {
                    $rutaReal = ($routeKey === 'busqueda_global') ? 'inv_maestros' : $routeKey;
                    if ($esAdminActual || ($nivelesPorRoute[$rutaReal] ?? 0) >= 1) {
                        $itemsVisibles[$routeKey] = $item;
                    }
                }
                if (empty($itemsVisibles)) continue;
            ?>
```

Nota: `$menuItems` usa claves de sección distintas a `route_key` en algunos casos (`talento_directorio` en el array es `'talento_directorio'` pero la sección `rrhh` lo declara como key `talento_directorio` — coincide). Verificar al aplicar que cada `$routeKey` de `$menuItems` (líneas 22-58 del archivo) tenga una entrada correspondiente en `$rutaAOpcion` salvo `busqueda_global` (mapeado explícitamente arriba) — si aparece alguna otra key sin mapeo, tratarla igual que `busqueda_global` o agregarla a `$rutaAOpcion` con su opción real de la tabla del plan.

- [ ] **Step 2: `php -l`**

```powershell
php -l apps/control_bienes/modules/Central/views/layout.php
```
Expected: `No syntax errors detected`

---

### Task 8: Sync Central → Bienes (rol)

**Files:**
- Modify: `core/SyncPermisosModulo.php` (portal)
- Modify: `modules/Central/controllers/AdminController.php:guardarPermisos()` (portal, ya tiene la llamada a `centralHaciaTh`, agregar la de Bienes al lado)

**Interfaces:**
- Consumes: `CORE_Roles_Modulo_Map` (Task 1), tabla de la sección "Contexto" (opcion → route_key).
- Produces: `SyncPermisosModulo::centralHaciaBienes(int $idRolPortal, array $cambiosPorNodo): void`

- [ ] **Step 1: Agregar el mapa y el método a `core/SyncPermisosModulo.php`**

Dentro de la clase `SyncPermisosModulo`, agregar junto a `NODOS_TH`:

```php
    /** Opción MOIS (bajo id_modulo=12) -> route_key real de Bienes. */
    private const NODOS_BIENES = [
        1 => 'dashboard', 2 => 'inventario', 3 => 'items', 4 => 'inv_items_sistema',
        5 => 'cabeceras', 6 => 'inv_maestros', 7 => 'ingresos', 8 => 'egresos',
        9 => 'talento_directorio', 10 => 'inv_bitacora', 11 => 'reportes',
        12 => 'inv_periodos', 13 => 'inv_secuenciales', 14 => 'usuarios', 15 => 'inv_permisos',
    ];

    public static function centralHaciaBienes(int $idRolPortal, array $cambiosPorNodo): void {
        $mapa = self::mapaRolExterno($idRolPortal, 12);
        if ($mapa === null) return;

        $conn = require dirname(__DIR__) . '/config/connections.php';
        $opts = ['CharacterSet' => 'UTF-8', 'TrustServerCertificate' => (bool)($conn['options']['trust_cert'] ?? true)];
        if (!empty($conn['credentials']['user'])) {
            $opts['UID'] = $conn['credentials']['user'];
            $opts['PWD'] = $conn['credentials']['pass'];
        }
        $opts['Database'] = 'inventario';
        $c = @sqlsrv_connect($conn['databases']['talento']['server'] ?? $conn['server_default'], $opts);
        if ($c === false) { self::registrarFalloSync($idRolPortal, sqlsrv_errors()); return; }

        foreach ($cambiosPorNodo as $key => $nivel) {
            $parts = explode('-', (string)$key);
            if (count($parts) !== 4) continue;
            [$mod, $op, $it, $sub] = array_map('intval', $parts);
            if ($mod !== 12 || $it !== 0 || $sub !== 0) continue;
            $routeKey = self::NODOS_BIENES[$op] ?? null;
            if ($routeKey === null) continue;

            $puedeV = $nivel >= 1 ? 1 : 0; $puedeC = $nivel >= 2 ? 1 : 0;
            $puedeE = $nivel >= 3 ? 1 : 0; $puedeD = $nivel >= 4 ? 1 : 0;

            sqlsrv_query($c,
                'MERGE dbo.inv_permisos_rol AS t
                 USING (SELECT ? AS rol_id, ? AS route_key) AS s
                 ON t.rol_id=s.rol_id AND t.route_key=s.route_key
                 WHEN MATCHED THEN UPDATE SET puede_visualizar=?, puede_crear=?, puede_editar=?, puede_eliminar=?
                 WHEN NOT MATCHED THEN INSERT (rol_id, route_key, puede_visualizar, puede_crear, puede_editar, puede_eliminar)
                     VALUES (s.rol_id, s.route_key, ?, ?, ?, ?);',
                [$mapa, $routeKey, $puedeV, $puedeC, $puedeE, $puedeD, $puedeV, $puedeC, $puedeE, $puedeD]
            );
        }
        sqlsrv_close($c);
    }

    /** Version generalizada de mapaRolTh: resuelve id_rol_externo para cualquier id_modulo mapeado. */
    private static function mapaRolExterno(int $idRolPortal, int $idModulo): ?int {
        $db = Database::getInstance();
        $row = $db->fetch($db->query(
            'SELECT id_rol_externo FROM CORE_Roles_Modulo_Map WHERE id_modulo=? AND id_rol_portal=?',
            [[$idModulo, SQLSRV_PARAM_IN], [$idRolPortal, SQLSRV_PARAM_IN]]
        ));
        return $row ? (int)$row['id_rol_externo'] : null;
    }
```

`$conn['databases']['talento']['server']`: se reutiliza la misma clave de servidor que usa `centralHaciaTh` (mismo servidor físico que aloja tanto `Talento_Humano` como `inventario`) — solo cambia `Database` a `'inventario'`. Confirmar en `config/connections.php` que no exista una clave `databases.inventario` propia con un server distinto; si existe, usarla en su lugar (`$conn['databases']['inventario']['server']`).

- [ ] **Step 2: Reemplazar `private static function mapaRolTh` para reusar `mapaRolExterno`**

```php
    private static function mapaRolTh(int $idRolPortal): ?int {
        return self::mapaRolExterno($idRolPortal, 11);
    }
```

- [ ] **Step 3: Enganchar en `AdminController::guardarPermisos()`**

En `modules/Central/controllers/AdminController.php`, justo después de la línea existente `SyncPermisosModulo::centralHaciaTh($id, $despuesMap);`, agregar:

```php
        SyncPermisosModulo::centralHaciaBienes($id, $despuesMap);
```

- [ ] **Step 4: `php -l`**

```powershell
php -l core/SyncPermisosModulo.php
php -l modules/Central/controllers/AdminController.php
```
Expected: `No syntax errors detected` en ambos.

---

### Task 9: Sync Bienes (rol) → Central

**Files:**
- Modify: `apps/control_bienes/modules/Credenciales/models/PermisoModel.php` (`guardarPermisosRol`, Task 3)

**Interfaces:**
- Consumes: `CORE_Roles_Modulo_Map` (Task 1), `config/connections.php` (raíz portal).
- Produces: cada guardado de permisos por rol nativo refleja en `PORTAL_APM.CORE_Permisos_Nodo`.

- [ ] **Step 1: Extender `guardarPermisosRol()` para sincronizar tras guardar**

En `PermisoModel.php` (Task 3), modificar el final de `guardarPermisosRol()`:

```php
    public function guardarPermisosRol(int $rolId, array $niveles): void {
        $del = $this->db->prepare("DELETE FROM inv_permisos_rol WHERE rol_id = :rid");
        $del->execute([':rid' => $rolId]);

        $ins = $this->db->prepare(
            "INSERT INTO inv_permisos_rol (rol_id, route_key, puede_visualizar, puede_crear, puede_editar, puede_eliminar)
             VALUES (:rid, :rk, :v, :c, :e, :d)"
        );
        foreach ($niveles as $rk => $nivel) {
            $nivel = (int)$nivel;
            $rk = trim((string)$rk);
            if ($rk === '' || $nivel < 1) continue;
            $ins->execute([
                ':rid' => $rolId, ':rk' => $rk,
                ':v' => $nivel >= 1 ? 1 : 0, ':c' => $nivel >= 2 ? 1 : 0,
                ':e' => $nivel >= 3 ? 1 : 0, ':d' => $nivel >= 4 ? 1 : 0,
            ]);
        }

        $this->sincronizarHaciaCentral($rolId, $niveles);
    }

    /** Refleja el guardado de permisos de un rol nativo hacia CORE_Permisos_Nodo del portal. */
    private function sincronizarHaciaCentral(int $rolIdNativo, array $niveles): void {
        $rolPortal = $this->rolPortalDesdeInv($rolIdNativo);
        if ($rolPortal === null) return;

        $rutaAOpcion = [
            'dashboard' => 1, 'inventario' => 2, 'items' => 3, 'inv_items_sistema' => 4,
            'cabeceras' => 5, 'inv_maestros' => 6, 'ingresos' => 7, 'egresos' => 8,
            'talento_directorio' => 9, 'inv_bitacora' => 10, 'reportes' => 11,
            'inv_periodos' => 12, 'inv_secuenciales' => 13, 'usuarios' => 14, 'inv_permisos' => 15,
        ];

        try {
            $conexionesPath = dirname(ROOT_PATH, 3) . '/config/connections.php';
            if (!is_file($conexionesPath)) return;
            $conn = require $conexionesPath;
            $opts = ['CharacterSet' => 'UTF-8', 'TrustServerCertificate' => (bool)($conn['options']['trust_cert'] ?? true)];
            if (!empty($conn['credentials']['user'])) { $opts['UID'] = $conn['credentials']['user']; $opts['PWD'] = $conn['credentials']['pass']; }
            $opts['Database'] = $conn['databases']['portal']['name'] ?? 'PORTAL_APM';
            $c = @sqlsrv_connect($conn['databases']['portal']['server'] ?? $conn['server_default'], $opts);
            if ($c === false) return;

            foreach ($rutaAOpcion as $rk => $opcion) {
                $nivel = (int)($niveles[$rk] ?? 0);
                sqlsrv_query($c,
                    'MERGE dbo.CORE_Permisos_Nodo AS t
                     USING (SELECT ? AS id_rol, 12 AS id_modulo, ? AS opcion, 0 AS items, 0 AS subitems, ? AS nivel_crud) AS s
                     ON t.id_rol=s.id_rol AND t.id_modulo=s.id_modulo AND t.opcion=s.opcion AND t.items=s.items AND t.subitems=s.subitems
                     WHEN MATCHED AND s.nivel_crud > 0 THEN UPDATE SET nivel_crud=s.nivel_crud, acceso=1, estado=1
                     WHEN MATCHED AND s.nivel_crud = 0 THEN DELETE
                     WHEN NOT MATCHED AND s.nivel_crud > 0 THEN INSERT (id_rol,id_modulo,opcion,items,subitems,nivel_crud,acceso,estado,fecha_asignacion)
                         VALUES (s.id_rol,s.id_modulo,s.opcion,s.items,s.subitems,s.nivel_crud,1,1,SYSDATETIME());',
                    [$rolPortal, $opcion, $nivel]
                );
            }
            sqlsrv_close($c);
        } catch (Exception $e) {
            // No bloquear el guardado nativo si el portal no esta disponible.
        }
    }

    /** Resuelve el id_rol del portal mapeado a este rol nativo de Bienes (CORE_Roles_Modulo_Map), o null. */
    private function rolPortalDesdeInv(int $rolIdNativo): ?int {
        try {
            $conexionesPath = dirname(ROOT_PATH, 3) . '/config/connections.php';
            if (!is_file($conexionesPath)) return null;
            $conn = require $conexionesPath;
            $opts = ['CharacterSet' => 'UTF-8', 'TrustServerCertificate' => (bool)($conn['options']['trust_cert'] ?? true)];
            if (!empty($conn['credentials']['user'])) { $opts['UID'] = $conn['credentials']['user']; $opts['PWD'] = $conn['credentials']['pass']; }
            $opts['Database'] = $conn['databases']['portal']['name'] ?? 'PORTAL_APM';
            $c = @sqlsrv_connect($conn['databases']['portal']['server'] ?? $conn['server_default'], $opts);
            if ($c === false) return null;
            $stmt = sqlsrv_query($c, 'SELECT id_rol_portal FROM dbo.CORE_Roles_Modulo_Map WHERE id_modulo=12 AND id_rol_externo=?', [$rolIdNativo]);
            $row = $stmt ? sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC) : false;
            sqlsrv_close($c);
            return $row ? (int)$row['id_rol_portal'] : null;
        } catch (Exception $e) {
            return null;
        }
    }
```

`dirname(ROOT_PATH, 3)` desde `PermisoModel.php` (`apps/control_bienes/modules/Credenciales/models/`, pero `ROOT_PATH` ya apunta a `apps/control_bienes/`, no a este archivo — mismo valor de `ROOT_PATH` verificado en Task 4 Step 2, mismo cálculo de niveles).

- [ ] **Step 2: `php -l`**

```powershell
php -l apps/control_bienes/modules/Credenciales/models/PermisoModel.php
```
Expected: `No syntax errors detected`

---

### Task 10: `PermisoController` + vista — pestaña de Rol nueva, pestaña de Usuario con V/C/E/D real

**Files:**
- Modify: `apps/control_bienes/modules/Credenciales/controllers/PermisoController.php`
- Modify: `apps/control_bienes/modules/Credenciales/views/credenciales/permisos.php`

**Interfaces:**
- Consumes: `PermisoModel::listarRoles/nivelesPorRol/guardarPermisosRol/obtenerNivelesUsuario/actualizarPermisos` (Task 3).

- [ ] **Step 1: Actualizar `PermisoController`**

Reemplazar `index()` y `guardar()`, agregar `guardarRol()`:

```php
    public function index() {
        $this->verificarAdmin();
        $this->registrarAuditoria('ACCESO', 'inv_permisos', 'Acceso al módulo de Gestión de Permisos');

        $usuarios  = $this->usuarioModel->obtenerTodos();
        $rutas     = self::$rutasDisponibles;
        $roles     = $this->permisoModel->listarRoles();

        $nivelesPorUsuario = [];
        foreach ($usuarios as $usr) {
            $nivelesPorUsuario[$usr['id']] = $this->permisoModel->obtenerNivelesUsuario($usr['id']);
        }

        $nivelesPorRol = [];
        foreach ($roles as $rol) {
            $nivelesPorRol[$rol['id']] = $this->permisoModel->nivelesPorRol($rol['id']);
        }

        $this->render('credenciales/permisos', [
            'usuarios'           => $usuarios,
            'rutas'              => $rutas,
            'roles'              => $roles,
            'nivelesPorUsuario'  => $nivelesPorUsuario,
            'nivelesPorRol'      => $nivelesPorRol,
        ], 'Gestión de Permisos - Sistema Portuario');
    }

    public function obtenerPermisos() {
        $this->verificarAdmin();
        $usuarioId = isset($_GET['usuario_id']) ? (int)$_GET['usuario_id'] : 0;
        if (!$usuarioId) {
            $this->jsonResponse(['error' => 'Usuario no especificado'], 400);
        }
        $niveles = $this->permisoModel->obtenerNivelesUsuario($usuarioId);
        $this->jsonResponse(['niveles' => $niveles]);
    }

    /** Guarda/actualiza los permisos individuales de un usuario nativo (POST) */
    public function guardar() {
        $this->verificarAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Método no permitido'], 405);
        }

        $usuarioId = isset($_POST['usuario_id']) ? (int)$_POST['usuario_id'] : 0;
        $niveles   = isset($_POST['niveles']) && is_array($_POST['niveles']) ? $_POST['niveles'] : [];

        if (!$usuarioId) {
            $this->jsonResponse(['error' => 'Usuario no especificado'], 400);
        }

        try {
            $this->permisoModel->actualizarPermisos($usuarioId, $niveles);
            $usuario = $this->usuarioModel->buscarPorId($usuarioId);
            $nombreUsr = $usuario ? $usuario['nombre'] : "ID {$usuarioId}";
            $this->registrarAuditoria('ACTUALIZAR', 'inv_permisos', "Permisos individuales actualizados para: {$nombreUsr}");

            if (isset($_POST['is_ajax'])) {
                $this->jsonResponse(['success' => true, 'mensaje' => 'Permisos actualizados correctamente']);
            }
            $this->redirect('inv_permisos', 'Permisos actualizados exitosamente', 'success');
        } catch (Exception $e) {
            $this->logger->inv_error('Error al guardar permisos', $e, 'guardar');
            if (isset($_POST['is_ajax'])) {
                $this->jsonResponse(['success' => false, 'mensaje' => $e->getMessage()], 500);
            }
            $this->redirect('inv_permisos', 'Error al guardar permisos: ' . $e->getMessage(), 'error');
        }
    }

    /** Guarda/actualiza los permisos por rol nativo (POST) — nuevo, sincroniza al central. */
    public function guardarRol() {
        $this->verificarAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Método no permitido'], 405);
        }

        $rolId   = isset($_POST['rol_id']) ? (int)$_POST['rol_id'] : 0;
        $niveles = isset($_POST['niveles']) && is_array($_POST['niveles']) ? $_POST['niveles'] : [];

        if (!$rolId) {
            $this->jsonResponse(['error' => 'Rol no especificado'], 400);
        }

        try {
            $this->permisoModel->guardarPermisosRol($rolId, $niveles);
            $this->registrarAuditoria('ACTUALIZAR', 'inv_permisos', "Permisos de rol actualizados para rol_id={$rolId}");

            if (isset($_POST['is_ajax'])) {
                $this->jsonResponse(['success' => true, 'mensaje' => 'Permisos de rol actualizados correctamente']);
            }
            $this->redirect('inv_permisos', 'Permisos de rol actualizados exitosamente', 'success');
        } catch (Exception $e) {
            $this->logger->inv_error('Error al guardar permisos de rol', $e, 'guardarRol');
            if (isset($_POST['is_ajax'])) {
                $this->jsonResponse(['success' => false, 'mensaje' => $e->getMessage()], 500);
            }
            $this->redirect('inv_permisos', 'Error al guardar permisos de rol: ' . $e->getMessage(), 'error');
        }
    }
```

- [ ] **Step 2: Registrar la nueva route `inv_permisos_rol` en `config/routes.php`**

Agregar junto a la entrada `'inv_permisos'`:

```php
    'inv_permisos_rol' => [
        'module'     => 'Credenciales',
        'controller' => 'PermisoController',
        'action'     => 'guardarRol'
    ],
```

Y en `Router::POLITICAS` (Task 5), agregar:
```php
        'inv_permisos_rol'   => ['opcion' => 15, 'default' => 3, 'acciones' => []],
```

- [ ] **Step 3: Leer la vista actual completa antes de editarla**

```powershell
cat apps/control_bienes/modules/Credenciales/views/credenciales/permisos.php
```
(usar la herramienta de lectura de archivos, no el comando de shell literal — el objetivo es ver la estructura HTML/JS real antes del siguiente step, ya que el archivo no fue leído durante la planificación)

- [ ] **Step 4: Agregar una pestaña "Permisos por Rol" a la vista**

Sobre la estructura ya existente en `permisos.php` (que hoy renderiza una tabla checkbox por usuario), agregar:
- Un selector de pestañas (`Permisos por Usuario` / `Permisos por Rol`) usando las mismas clases CSS que el resto del módulo (`.tabs`/`.tab-btn` si existen en `inv_estilos.css`, o un patrón simple de `display:none`/`active` como el resto de modales de este archivo).
- Pestaña "Permisos por Rol": un `<select>` de rol (`$roles`), y por cada `route` de `$rutas` (mismo array agrupado que ya usa la pestaña de usuario) 4 checkboxes Ver/Crear/Editar/Eliminar, precargados desde `$nivelesPorRol[$rolId]` vía JS (mismo patrón AJAX que ya usa `obtenerPermisos()` para la pestaña de usuario — agregar un endpoint `obtenerPermisosRol` análogo si el patrón existente lo requiere, o incrustar `$nivelesPorRol` completo como JSON en la vista para evitar una llamada AJAX extra, ya que son solo 4 roles).
- Botón guardar de la pestaña de rol hace `POST index.php?route=inv_permisos_rol` con `rol_id` y `niveles[route_key]=nivel` (checkboxes → nivel_crud calculado en JS igual que hace `rol_permisos.php` del portal: `Math.max(...)` de los 4 niveles marcados, o un solo `<select>` 0-4 por fila en vez de 4 checkboxes — más simple de implementar y consistente con `usuario_form.php` del portal (`permisosUsuario_flatten` pattern), preferir el `<select>` por fila.
- Pestaña "Permisos por Usuario" (existente): cambiar el checkbox único por fila a un `<select>` Ver/Crear/Editar/Eliminar/Sin acceso (0-4) igual criterio, precargado desde `$nivelesPorUsuario[$usuarioId][$routeKey] ?? 0`. El submit pasa a `niveles[route_key]=nivel` (antes era `inv_permisos[]=route_key`).

No hay código exacto de HTML/JS aquí porque el archivo no fue leído durante la planificación (Step 3 es un prerequisito real, no retórico) — al ejecutar este step, seguir el patrón visual y de JS ya usado en `modules/Central/views/admin/rol_permisos.php` del portal nativo (selects 0-4 por fila, un botón guardar por pestaña, fetch con `is_ajax=1`) adaptado a las clases CSS de Bienes (`inv_estilos.css`).

- [ ] **Step 5: `php -l`**

```powershell
php -l apps/control_bienes/modules/Credenciales/controllers/PermisoController.php
php -l apps/control_bienes/modules/Credenciales/views/credenciales/permisos.php
php -l apps/control_bienes/config/routes.php
```
Expected: `No syntax errors detected` en los 3.

---

### Task 11: Verificación end-to-end (SQL real + navegador real)

**Files:** ninguno — solo verificación.

- [ ] **Step 1: Rol nativo → Central.** Loguearse en Bienes como `admin` (`Administrador`, bypass total, único usuario nativo con acceso a `/credenciales/permisos` hoy). Ir a la pestaña "Permisos por Rol", seleccionar `Operador`, asignar `Inventario General` = Ver+Crear (nivel 2), guardar. Verificar:
```powershell
php -r "
\$conn = require 'config/connections.php';
\$c = sqlsrv_connect(\$conn['server_default'], ['CharacterSet'=>'UTF-8','TrustServerCertificate'=>true,'Database'=>'inventario']);
\$s = sqlsrv_query(\$c, \"SELECT puede_visualizar,puede_crear,puede_editar,puede_eliminar FROM inv_permisos_rol WHERE rol_id=(SELECT id FROM inv_roles WHERE nombre='Operador') AND route_key='inventario'\");
print_r(sqlsrv_fetch_array(\$s, SQLSRV_FETCH_ASSOC));
"
php -r "
\$conn = require 'config/connections.php';
\$c = sqlsrv_connect(\$conn['server_default'], ['CharacterSet'=>'UTF-8','TrustServerCertificate'=>true,'Database'=>'PORTAL_APM']);
\$s = sqlsrv_query(\$c, \"SELECT nivel_crud FROM CORE_Permisos_Nodo WHERE id_rol=(SELECT id_rol FROM CORE_Roles WHERE codigo='BIENES_OPERADOR') AND id_modulo=12 AND opcion=2\");
print_r(sqlsrv_fetch_array(\$s, SQLSRV_FETCH_ASSOC));
"
```
Expected: `puede_visualizar=1, puede_crear=1, puede_editar=0, puede_eliminar=0` nativo; `nivel_crud=2` central.

- [ ] **Step 2: Rol Central → Bienes.** Desde el portal nativo, `/admin/roles/{id_rol de BIENES_OPERADOR}/permisos`, cambiar `Inventario General` a nivel 3 (Ver+Crear+Editar), guardar. Verificar `inv_permisos_rol` refleja `puede_editar=1`.

- [ ] **Step 3: Cuenta nativa gobernada por el rol.** Crear un usuario nativo desechable en Bienes con `rol='Operador'` (vía `/credenciales/usuarios` o SQL directo), loguearse como esa cuenta, confirmar que "Inventario General" aparece en el sidebar (nivel≥1) y que un intento de `?route=inventario&action=eliminar` da el toast de acceso denegado (nivel requerido 4, rol tiene 3). Confirmar que `?route=inventario&action=guardar` con `id` vacío (crear) si pasa. Borrar el usuario desechable al terminar.

- [ ] **Step 4: Cuenta puenteada.** Con un usuario de prueba del portal (nivel_jerarquia baja, desechable) asignado al rol `BIENES_OPERADOR` vía `/admin/usuarios`, entrar a Bienes por el puente SSO, confirmar que ve exactamente lo que su rol permite (antes de este plan, un usuario así prácticamente no veía nada — confirmar que el hueco funcional descrito en el spec quedó cerrado). Limpiar el usuario de prueba.

- [ ] **Step 5: Cierre del hueco de seguridad.** Con la cuenta nativa `Operador` del Step 3 (sin permiso de eliminar), confirmar que `?route=inventario&action=eliminar&id=<id-real>&is_ajax=1` responde 403/JSON de error en vez de ejecutar el borrado — este es el hueco real encontrado durante la investigación, confirmar que efectivamente ya no es explotable.

- [ ] **Step 6: Limpieza total.** Verificar con queries directas que no queda ningún usuario/rol/permiso de prueba en `inventario` ni en `PORTAL_APM` (roles reales `BIENES_*` SÍ se quedan — son parte de la migración, no datos de prueba).

- [ ] **Step 7: `php -l` final sobre todo el árbol tocado**

```powershell
for f in apps/control_bienes/core/Controller.php apps/control_bienes/core/Router.php apps/control_bienes/core/Database.php apps/control_bienes/modules/Credenciales/models/PermisoModel.php apps/control_bienes/modules/Credenciales/controllers/PermisoController.php apps/control_bienes/modules/Credenciales/controllers/AuthController.php apps/control_bienes/modules/Credenciales/models/UsuarioModel.php apps/control_bienes/modules/Central/views/layout.php apps/control_bienes/config/routes.php core/SyncPermisosModulo.php modules/Central/controllers/AdminController.php; do php -l "$f"; done
```
Expected: `No syntax errors detected` en los 11 archivos.

---

## Self-Review de este plan

**Cobertura del spec (sección Fase 2 revisada)**: `inv_roles`+`inv_permisos_rol` (Task 2,3), `inv_permisos.nivel_crud` upgrade (Task 2,3), `CORE_Menu_Nodos`/`CORE_Roles_Modulo_Map` reales para `id_modulo=12` (Task 1), rama dual puente/nativa (Task 4,5), cierre del hueco `(route,action)` (Task 5), `layout.php` con cascada dual (Task 7), sync bidireccional a nivel rol (Task 8,9), pestaña de rol + upgrade de pestaña de usuario en `PermisoController`/vista (Task 10). Sin `es_accion` (decisión, no task). Todo lo de la sección Fase 2 del spec revisado tiene task.

**Fuera de alcance (ya lo estaba en el spec/decisión explícita del plan)**: CSRF en escrituras de Bienes (hardening general, no permisos). Fase 3 (Bitácoras) — plan separado. Override de usuario individual para cuentas puenteadas ya lo cubre `CORE_Permisos_Nodo_Usuario` de Fase 0 sin código nuevo — no hay task dedicado porque no hace falta ninguno.

**Riesgo señalado explícitamente para quien ejecute**: Task 5 Step 3 y Task 10 Step 3 dependen de releer archivos reales (`UsuarioController`/`ConfigController`/etc. y `permisos.php`) que no fueron abiertos completos durante la planificación — están marcados como steps explícitos, no como huecos silenciosos.
