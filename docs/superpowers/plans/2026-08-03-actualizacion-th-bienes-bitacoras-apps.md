# Actualización TH/Bienes + Migración Bitácoras a apps/ — Plan de Implementación

> **Para agentic workers:** REQUIRED SUB-SKILL: superpowers:subagent-driven-development (recomendado)
> o superpowers:executing-plans para ejecutar este plan tarea por tarea. Los pasos usan checkbox
> (`- [ ]`) para tracking.
>
> **Nota de adaptación:** Este proyecto (PHP nativo + SQL Server, sin framework, sin suite de tests
> automatizados) no sigue TDD clásico. La verificación real es la ya establecida en
> `INDICACIONES/GUIA_MODULOS_NUEVOS_Y_ACTUALIZACIONES.md`: `php -l`, clon desechable de BD +
> verificación consultando directo, y `curl` de humo. Cada tarea usa esos mecanismos en vez de
> pytest/jest.

**Goal:** Traer las versiones nuevas de Talento Humano y Control de Bienes sin perder integraciones
propias, y migrar Bitácoras (hoy nativo/mezclado dentro de PORTAL_APM) a `apps/bitacoras/` como
módulo independiente Patrón B, igual que TH y Bienes — sin login propio, sesión compartida del portal.

**Architecture:** Metodología de diff a 3 bandas (Parte 2 de la guía) para TH y Bienes — sin OLD
disponible, se usa NEW vs CUR y clasificación manual con los candidatos típicos de §2.4.1. Para
Bitácoras, migración estilo Parte 1 (módulo nuevo) tomando como base el NEW (`portuaria_demoV4`) y
portando la lógica operativa ya viva en `modules/Portuaria/` + los archivos sueltos en el árbol del
portal, preservando el esquema de `PortuariaDemo` (FKs a `bit_usuarios_apm`) vía un resolver
find-or-create por cédula en vez de migrar el esquema.

**Tech Stack:** PHP 8+ nativo, `sqlsrv`/PDO, SQL Server 2014+, sin CDN (todo local).

## Global Constraints

- Nunca commitear ni pushear sin confirmación explícita del usuario, cada vez.
- `config/connections.php` nunca se sube a git, nunca hardcodear servidor/usuario/contraseña en
  ningún módulo — todo sale de ahí (pedido explícito del usuario en esta sesión).
- Todo cambio SQL se prueba primero en una BD clon desechable (`SELECT INTO` tabla por tabla si no
  hay permiso de `BACKUP`/`RESTORE`; agregar PK/FK a mano en el clon antes de probar constraints),
  se corre con `php db/run_sql.php <archivo> .\INSTANCIA`, se verifica consultando la BD directo
  (nunca confiar en el mensaje de éxito), y se dropea el clon al final.
- Nunca `robocopy`/copiar carpeta entera encima de un módulo integrado — diff y clasificación
  archivo por archivo (GUIA §2).
- Nada de features/refactors no pedidos.
- `C:\xampp\htdocs\portal_apm` es junction al repo — nunca hace falta sincronizar nada aparte.
- Identidad: login solo por cédula, sin "nombre de usuario" visible en ningún módulo.
- `CREATE OR ALTER` no usar si el script debe ser compatible con SQL Server 2014 — usar
  `IF OBJECT_ID(...) IS NOT NULL DROP ... ; GO ; CREATE ...`.
- Un `ALTER TABLE ... ADD columna` y un uso posterior de esa columna en el mismo batch requieren un
  `GO` entre medio.
- No envolver migraciones que necesitan `GO` intermedio en una transacción explícita (MARS + sqlsrv
  no la sobrevive) — apoyarse en idempotencia (`IF ... IS NULL`) en cada paso.

---

## Fase 1 — Actualizar Talento Humano (`apps/talento_humano`)

**NEW:** `C:\Users\Usuario\Downloads\MODULOS ACTUALIZADOS\actualizacion\Practicas 2026\PortalPortuario`
**CUR:** `apps/talento_humano/`
**OLD:** no disponible — se procede solo con NEW vs CUR (decisión del usuario).
**BD nueva:** `C:\Users\Usuario\Downloads\MODULOS ACTUALIZADOS\BD MODULOS\Talento_Humano.sql`

### Tarea 1.1 — Diff estructural y clasificación

- [ ] **Paso 1:** Correr el diff NEW vs CUR:
  ```bash
  diff -rq --exclude=.git --exclude=__pycache__ --exclude=backup --exclude=logs --exclude=vendor --exclude=.env \
    "C:\Users\Usuario\Downloads\MODULOS ACTUALIZADOS\actualizacion\Practicas 2026\PortalPortuario" \
    "apps/talento_humano"
  ```
- [ ] **Paso 2:** Para cada archivo que difiere, `diff -u NEW/ruta CUR/ruta`. Candidatos de
  integración a revisar con especial cuidado antes de decidir (NO sobreescribir sin comparar):
  - `index.php` (puente de sesión SSO — `$_SESSION['user_id']`, redirect a login central)
  - `core/Database.php` (debe leer de `config/connections.php`, no credenciales sueltas)
  - `shared/menu.php` (ítem "Portal APM" + tema propio, ver spec
    `docs/superpowers/specs/2026-07-15-modulos-identidad-propia-design.md`)
- [ ] **Paso 3:** Código puro (controllers/models/vistas sin nada del portal adentro) que cambió
  upstream: adoptar versión NEW, después de confirmar con `diff -u` que CUR no esconde un fix
  propio no documentado.
- [ ] **Paso 4:** Todo lo que solo existe en NEW: investigar antes de copiar (puede ser basura del
  desarrollador — scripts de debug, `.bak`, carpetas de otro proyecto). Todo lo que solo existe en
  CUR: dejarlo.

### Tarea 1.2 — Migración SQL en clon desechable

- [ ] **Paso 1:** Crear clon:
  ```sql
  CREATE DATABASE Talento_Humano_TESTCLONE;
  -- por cada tabla de Talento_Humano:
  SELECT * INTO Talento_Humano_TESTCLONE.dbo.[tabla] FROM Talento_Humano.dbo.[tabla];
  ```
  Agregar a mano PK/FK/índices que la migración nueva necesite validar (no los copia `SELECT INTO`).
- [ ] **Paso 2:** Aplicar `BD MODULOS/Talento_Humano.sql` (o el delta que corresponda) contra
  `Talento_Humano_TESTCLONE` con `php db/run_sql.php`.
- [ ] **Paso 3:** Verificar consultando directo (`sys.objects`, `SELECT COUNT(*)` en tablas
  tocadas) — no confiar en el mensaje de éxito del runner.
- [ ] **Paso 4:** Si la migración toca datos existentes (no solo agrega tablas/columnas vacías),
  revisar qué hay ahí antes de dejar que se sobrescriba; respaldar con
  `SELECT * INTO zz_backup_<tabla>_20260803 FROM <tabla> WHERE ...` si aplica.
  Trigger de sincronización `inv_talento_personal` (Control de Bienes) depende de
  `Talento_Humano.dbo.th_empleados` — confirmar que la migración no le cambia el shape de columnas
  que ese trigger lee.
- [ ] **Paso 5:** `DROP DATABASE Talento_Humano_TESTCLONE;`
- [ ] **Paso 6:** Recién ahí, aplicar contra `Talento_Humano` real. Verificar de nuevo consultando
  directo.

### Tarea 1.3 — Verificación

- [ ] **Paso 1:**
  ```bash
  find apps/talento_humano -name "*.php" -not -path "*/vendor/*" | while read f; do
    php -l "$f" | grep -v "No syntax errors"
  done
  ```
  Cualquier error es un bug del origen — corregirlo y documentarlo como fix propio para la próxima
  actualización.
- [ ] **Paso 2:** `curl` a `/apps/talento_humano/` sin sesión → debe dar 302 al login central (no
  500). Con sesión de portal activa → 200.
- [ ] **Paso 3:** Probar manualmente en navegador logueado: listado de empleados, ficha, una acción
  de personal — confirmar que no rompió nada visible.
- [ ] **Paso 4:** Commit (solo si el usuario confirma explícitamente).

---

## Fase 2 — Actualizar Control de Bienes (`apps/control_bienes`)

**NEW:** `C:\Users\Usuario\Downloads\MODULOS ACTUALIZADOS\portuarea\Control_binesC`
**CUR:** `apps/control_bienes/`
**BD nueva:** `C:\Users\Usuario\Downloads\MODULOS ACTUALIZADOS\BD MODULOS\inventario.sql`

### Tarea 2.1 — Descartar basura conocida en NEW antes de diffear

- [ ] **Paso 1:** Confirmar y excluir del diff (ya identificados, no requieren más investigación
  salvo sorpresa):
  - `Control_binesC/talento_humano/` — copia completa de OTRO proyecto de TH de otro compañero, sin
    relación con `apps/talento_humano` ni con la BD `Talento_Humano` real. Descartar.
  - `Control_binesC/scratch/`, `scratch_check_backup.php`, `scratch_check_databases.php`,
    `scratch_check_paths.php`, `test_conexion.php` (credenciales de máquina del desarrollador) —
    debug personal, no va a producción.
  - `Control_binesC/__pycache__/`, `Control_binesC/backup/` (revisar tamaño/contenido rápido antes
    de asumir, pero probablemente dump/backup local descartable).
  - `Control_binesC/inventario.bak` — dump binario, no se versiona.
  - `Control_binesC/vendor/` — solo comparar `composer.json`/`composer.lock` si difieren; no
    versionar `vendor/` a mano si el proyecto usa Composer (usar `composer install` en su lugar).

### Tarea 2.2 — Diff estructural y clasificación

- [ ] **Paso 1:**
  ```bash
  diff -rq --exclude=.git --exclude=__pycache__ --exclude=backup --exclude=logs --exclude=vendor \
    --exclude=.env --exclude=talento_humano --exclude=scratch --exclude=scratch_check_*.php \
    --exclude=test_conexion.php --exclude=inventario.bak \
    "C:\Users\Usuario\Downloads\MODULOS ACTUALIZADOS\portuarea\Control_binesC" \
    "apps/control_bienes"
  ```
- [ ] **Paso 2:** Candidatos de integración a revisar con `diff -u` uno por uno — **no
  sobreescribir sin verificar**:
  - `index.php` (puente de sesión SSO)
  - `core/Database.php` (lectura de `config/connections.php`)
  - `core/DatabaseConnection.php` y `core/DatabaseStatement.php` — **ya tienen un fix propio**
    (ignoran mensajes SQLSTATE clase `01` al ejecutar SQL). Si el NEW no lo trae, NO sobreescribir
    o se pierde el fix silenciosamente.
  - `modules/Central/views/layout.php` (logo, franja, tema — ver spec 2026-07-15)
  - `config/routes.php` si define algo propio del portal
- [ ] **Paso 3:** Código puro que cambió upstream: adoptar NEW tras confirmar con `diff -u` que CUR
  no esconde un fix propio.
- [ ] **Paso 4:** Todo lo "solo-NEW" que no sea de la lista de descarte de la Tarea 2.1: investigar
  antes de copiar.

### Tarea 2.3 — Migración SQL en clon desechable

- [ ] **Paso 1:** Clonar `inventario` (`SELECT INTO` tabla por tabla, agregar PK/FK a mano donde la
  migración las necesite).
- [ ] **Paso 2:** Aplicar `BD MODULOS/inventario.sql` contra el clon con `php db/run_sql.php`.
- [ ] **Paso 3:** Verificar consultando directo. Prestar atención especial a `inv_talento_personal`
  — tiene un **trigger permanente** en `Talento_Humano.dbo.th_empleados` que la sincroniza en cada
  alta/baja/cambio (ver `apps/control_bienes` §2026-07-31 en memoria del asistente). Confirmar que
  la migración no le cambia columnas que el trigger escribe, y que no duplica/rompe filas ya
  sincronizadas.
- [ ] **Paso 4:** Si toca datos existentes, respaldar antes con `SELECT * INTO
  zz_backup_<tabla>_20260803 FROM <tabla> WHERE ...`.
- [ ] **Paso 5:** `DROP DATABASE` del clon. Aplicar a `inventario` real. Verificar de nuevo.

### Tarea 2.4 — Verificación

- [ ] **Paso 1:** `php -l` en todo `apps/control_bienes` (excluyendo `vendor/`).
- [ ] **Paso 2:** `curl` de humo sin sesión (302 a login) y con sesión (200).
- [ ] **Paso 3:** Probar manualmente: inventario general, un ingreso/egreso, catálogo de ítems.
- [ ] **Paso 4:** Commit (solo si el usuario confirma explícitamente).

---

## Fase 3 — Migrar Bitácoras a `apps/bitacoras/` (Patrón B)

**NEW (base MVC):** `C:\Users\Usuario\Downloads\MODULOS ACTUALIZADOS\portuaria_demoV4`
**CUR (lógica operativa viva hoy):**
- `modules/Portuaria/` (controllers, models, views propias del módulo)
- `views/layouts/bit_navbar.php`, `views/layouts/bit_sidebar.php` (en la raíz del portal, no en
  `modules/Portuaria/views/`)
- `public/js/portuaria/*.js` (14 archivos: `bit_camaras.js`, `bit_motivos_camaras.js`,
  `bitacora_rondas.js`, `catalogos.js`, `dashboard_jefe.js`, `dashboard_permisos_demo.js`,
  `inv_camaras.js`, `layout_sidebar.js`, `listado_visitas.js`, `registrar_visita.js`,
  `reporte_diario_supervisor.js`, `theme_mode.js`, `toast.js`, `validaciones_ecuador.js`)
- `css/style.css` regla `.bg-bitacoras` (línea 1012)
- `routes.php` líneas 96-149 (~50 rutas nativas de Portuaria/Bitácoras)
- `db/portuaria_menu_integration.sql`, `db/portuaria_menu_simplificar.sql`
- `pendientes/bit_dashboard_jefe.php`, `pendientes/bit_consulta.php`,
  `pendientes/bit_reporte_diario_supervisor.php`

**BD nueva:** `C:\Users\Usuario\Downloads\MODULOS ACTUALIZADOS\BD MODULOS\PortuariaDemo.sql`

**Decisión de arquitectura (confirmar entendimiento con el usuario antes de tocar datos):**
`bit_usuarios_apm` tiene FKs reales desde `bit_movimientos.id_usuario` y
`bit_rondas_cabecera.id_usuario` — no se migra el esquema. En vez de eso, `apps/bitacoras/` no
expone login propio (sesión compartida del portal, igual que TH/Bienes), y un resolver
`findOrCreateUsuarioApm($cedula)` busca-o-crea la fila correspondiente en `bit_usuarios_apm` a
partir de la cédula de la sesión portal — mismo patrón ya usado para sincronizar
`inv_talento_personal` desde `Talento_Humano.dbo.th_empleados` en Control de Bienes. Preserva el
historial y las FKs existentes sin reescribir el esquema.

### Tarea 3.1 — Base del módulo

- [ ] **Paso 1:** Crear `apps/bitacoras/` copiando la estructura del NEW (`portuaria_demoV4`):
  `core/`, `helpers/`, `rutas/`, `modules/`, `public/`, `views/`, `index.php`.
- [ ] **Paso 2:** Reescribir `apps/bitacoras/index.php` como front controller Patrón B (mismo
  patrón que `apps/control_bienes/index.php`):
  ```php
  if (session_status() === PHP_SESSION_NONE) session_start();
  define('ROOT', __DIR__);
  $basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
  define('BASE_URL', $basePath);
  if (empty($_SESSION['user_id'])) {
      $portalLogin = preg_replace('#/apps/bitacoras$#', '', $basePath) . '/login';
      header('Location: ' . ($portalLogin ?: '/login'));
      exit;
  }
  ```
  Eliminar la llamada a `Auth::start()` propia del NEW (`core/Auth.php`) — ya no hay login propio.
  Mapear rol: reusar el patrón de `control_bienes/index.php`
  (`$nivelPortal = (int)($_SESSION['nivel_jerarquia'] ?? 0);`).
- [ ] **Paso 3:** `core/Database.php` (o `config/globals.php` del NEW, según cómo resuelva
  conexión) → leer de `config/connections.php` central. Agregar `PortuariaDemo` y
  `PortuariaExterna` a la lista `databases` de `config/connections.php` si no están.

### Tarea 3.2 — Resolver de identidad

- [ ] **Paso 1:** Crear `apps/bitacoras/core/UsuarioApmResolver.php` (o donde corresponda según el
  kernel del NEW) con `findOrCreateUsuarioApm(string $cedula, array $sessionData): int` que:
  1. Busca en `bit_usuarios_apm` por cédula.
  2. Si no existe, la crea (nombre completo y departamento desde `vw_Usuarios_Identidad` /
     `CORE_Usuarios`, mapeando `id_departamento` si hay equivalente; si no hay mapeo directo,
     dejar el campo que corresponda con un valor por defecto documentado).
  3. Devuelve `id_usuario` de `bit_usuarios_apm` para usar en los INSERT existentes
     (`bit_movimientos.id_usuario`, `bit_rondas_cabecera.id_usuario`, etc.).
- [ ] **Paso 2:** Reemplazar en los controllers portados (Tarea 3.3) cualquier lectura de
  `$_SESSION` propia del módulo original por: `id_usuario = findOrCreateUsuarioApm($_SESSION['cedula'] ?? ..., $_SESSION)`.
  Confirmar de dónde sale la cédula en la sesión del portal (`$_SESSION['cedula']` — verificar
  nombre exacto de la key en `apps/control_bienes/index.php` o `modules/Central` login antes de
  asumir).

### Tarea 3.3 — Portar lógica operativa

- [ ] **Paso 1:** Comparar cada controller/model de `modules/Portuaria/` contra su equivalente en
  el NEW (`PortVisitaController` vs el controller de visitas del NEW, etc. — los nombres pueden
  diferir, el NEW usa MVC con `modulo/controlador/accion`). Adoptar la versión más completa,
  verificando con `diff -u` qué fixes tiene cada lado.
- [ ] **Paso 2:** Copiar a `apps/bitacoras/` los controllers/models resultantes:
  `PortVisitaController`, `PortRondaController`, `PortCamaraController`, `PortCatalogoController`,
  `PortDashboardController`, `PortalPortuariaController` (o su equivalente MVC del NEW), y sus
  modelos (`PortVisitaModel`, `PortRondaModel`, `PortCamaraModel`, `PortCatalogoModel`,
  `PortDashboardModel`, `PortUsuarioModel`, `PortBaseModel`, `PortDatabase`).
- [ ] **Paso 3:** Copiar las vistas de `modules/Portuaria/views/` (`camaras/`, `catalogos/`,
  `dashboard/`, `layouts/`, `portal/`, `reportes/`, `rondas/`, `visitas/`) a
  `apps/bitacoras/views/` (o la carpeta de vistas que use el kernel del NEW).

### Tarea 3.4 — Mover assets sueltos del árbol del portal

- [ ] **Paso 1:** Mover `views/layouts/bit_navbar.php` y `views/layouts/bit_sidebar.php` (portal
  root) a `apps/bitacoras/views/layouts/`. Ajustar el `require_once` de
  `bit_sidebar.php:3` (hoy `.../includes/bit_auth_permissions.php` con `ROOT_PATH`) para que
  apunte dentro de `apps/bitacoras/` — o reemplazar esas funciones de permisos
  (`apm_can_registrar_ingreso()`, etc.) por checks basados en `$_SESSION['nivel_jerarquia']` del
  portal, igual que hace `control_bienes/index.php` con `$rolApp`.
- [ ] **Paso 2:** Mover los 14 archivos de `public/js/portuaria/` a `apps/bitacoras/public/js/`.
  Revisar cada uno por rutas hardcodeadas tipo `/portal_apm/...` o `/bitacoras/...` que deban
  actualizarse al nuevo prefijo `/apps/bitacoras/...`.
- [ ] **Paso 3:** Mover la regla `.bg-bitacoras` (`css/style.css:1012`) al CSS propio de
  `apps/bitacoras/public/` si sigue en uso; si no se referencia desde ningún lado dentro de
  `apps/bitacoras/`, no portarla.
- [ ] **Paso 4:** Revisar `pendientes/bit_dashboard_jefe.php`, `pendientes/bit_consulta.php`,
  `pendientes/bit_reporte_diario_supervisor.php` — confirmar si son código en desarrollo real o
  descartable; si son válidos, migrarlos igual que el resto.

### Tarea 3.5 — Migración SQL en clon desechable

- [ ] **Paso 1:** Clonar `PortuariaDemo` (`SELECT INTO` tabla por tabla + PK/FK a mano donde la
  migración las necesite; prestar atención a `bit_usuarios_apm`, `bit_movimientos`,
  `bit_rondas_cabecera` y sus FKs, ver `Z.BASES DE DATOS\PortuariaDemo.sql:543-893`).
- [ ] **Paso 2:** Aplicar `BD MODULOS/PortuariaDemo.sql` contra el clon con `php db/run_sql.php`.
- [ ] **Paso 3:** Verificar consultando directo. Revisar si la migración toca `bit_usuarios_apm`
  existente (cuentas reales ya usadas) antes de dejar que se sobrescriba — respaldar si aplica.
- [ ] **Paso 4:** `DROP DATABASE` del clon. Aplicar a `PortuariaDemo` real. Verificar de nuevo.

### Tarea 3.6 — Rutas y menú

- [ ] **Paso 1:** Eliminar las rutas de Portuaria en `routes.php` (líneas 96-149) — ya no las sirve
  el router nativo del portal.
- [ ] **Paso 2:** Crear `db/bitacoras_apps_migration_menu.sql` (idempotente, mismo patrón que
  `db/apps_origen_integration.sql` usado para TH/Bienes): actualizar `CORE_Menu_Nodos` módulo 13
  para que el ítem "Sistema de Bitácoras" apunte a `/apps/bitacoras/` en vez de `/visitas`, y
  agregar (opcional, mismo patrón que TH/Bienes) un ítem "Panel" → `/panel/bitacoras` si se decide
  mantener un hub nativo con KPIs antes de entrar al sistema completo. Probar en clon antes de
  correr contra la BD real (mismo método de la Tarea 3.5).
- [ ] **Paso 3:** Actualizar la tarjeta "Bitácoras Portuarias" en
  `views/General/home/index.php:1640` (Directorio de Módulos):
  ```php
  <a href="<?= !$isLoggedIn ? APP_URL . '/login' : APP_URL . '/apps/bitacoras/' ?>" ...>
  ```
  igual patrón que las tarjetas de TH (`:1602`) y Bienes (`:1621`).

### Tarea 3.7 — Retirar la implementación vieja

- [ ] **Paso 1:** Con `apps/bitacoras/` verificado funcionando end-to-end (Tarea 3.8), borrar
  `modules/Portuaria/` completo.
- [ ] **Paso 2:** Borrar `views/layouts/bit_navbar.php`, `views/layouts/bit_sidebar.php`,
  `public/js/portuaria/` (ya migrados en la Tarea 3.4).
- [ ] **Paso 3:** Borrar `modules/Portuaria/config_rutas.php` si no se borró junto con el paso 1
  (confirmar que no queda huérfano).

### Tarea 3.8 — Verificación

- [ ] **Paso 1:** `php -l` en todo `apps/bitacoras/`.
- [ ] **Paso 2:** `curl` sin sesión → 302 a login central (no expone login propio). Con sesión de
  portal activa → 200 en `/apps/bitacoras/`.
- [ ] **Paso 3:** Probar manualmente logueado: registrar una visita, ver listado, bitácora de
  rondas, cámaras, catálogos — confirmar que `findOrCreateUsuarioApm` no rompe los INSERT
  existentes (revisar que la fila se crea/reutiliza bien en `bit_usuarios_apm`).
- [ ] **Paso 4:** Actualizar `DOCUMENTACION_SISTEMA.md` §7.5 (Patrón C → Patrón B), §9.5 (agregar
  `PortuariaDemo`/`PortuariaExterna` como Patrón B), §10 (menú módulo 13).
- [ ] **Paso 5:** Actualizar memoria del asistente (`portuaria_module.md` → ya no es Patrón C).
- [ ] **Paso 6:** Commit (solo si el usuario confirma explícitamente).

---

## Fase 4 — Verificación cruzada final

- [ ] **Paso 1:** `grep -rn "/portuaria\|/visitas\|/rondas\|/camaras\|/catalogos" --include=*.php`
  desde la raíz del portal (excluyendo `apps/`) para confirmar que no quedan referencias rotas a
  las rutas nativas viejas.
- [ ] **Paso 2:** Confirmar que las 3 tarjetas del Directorio de Módulos (`views/General/home/index.php`)
  se comportan igual: sin sesión → login central; con sesión → directo al `apps/<módulo>/`
  correspondiente. Ningún módulo queda con "login propio" tras esta migración — si el usuario
  tenía en mente otro módulo distinto para ese comportamiento dual, avisar antes de dar la fase por
  cerrada.
- [ ] **Paso 3:** Checklist visual con el usuario: navegar los 3 módulos logueado, confirmar que
  nada se ve roto.
