# Sistema central de menú/permisos — Fase 0 (base) + Fase 1 (TH) + Fase 2 (Bienes) + Fase 3 (Bitácoras)

## Contexto

`/admin/menu` (`MenuController`) y `/admin/roles/{id}/permisos`
(`AdminController::rolPermisos()`/`guardarPermisos()`) ya existen y funcionan
bien para el portal nativo: coordenadas MOIS (Módulo/Opción/Ítem/Sub-ítem,
4 niveles) en `CORE_Menu_Nodos`, permisos por rol en `CORE_Permisos_Nodo`
(`nivel_crud` 0-4, semántica **acumulativa/jerárquica** — decisión de modelo
ya confirmada en `2026-07-29-permisos-checklist-design.md`, no se toca),
resolución vía `fn_TienePermisoNodo(@id_usuario, @id_modulo, @opcion, @items,
@subitems, @nivel_min, @mfa_ok)`.

El usuario pidió que esto sea el sistema de menú/permisos de **todo el
portal, incluidos los módulos embebidos (Patrón B) actuales y futuros** —
hoy solo alimenta el sidebar nativo. Investigación previa a este diseño
confirmó:

- `apps/talento_humano`, `apps/control_bienes`, `apps/bitacoras` **no leen
  `CORE_Menu_Nodos`/`CORE_Permisos_Nodo` para nada** — cada uno tiene su
  propio menú hardcodeado.
- **TH** (reconstruido el 2026-08-11, ver `th_hr_module.md`) tiene su propio
  RBAC real y ya probado: `th_roles`/`th_permisos_rol`/`th_modulos`, 4
  columnas booleanas independientes por rol (`puede_visualizar`,
  `puede_crear`, `puede_editar`, `puede_eliminar`), verificado en runtime vía
  `Auth::can($modulo, $accion)`.
- **Bienes** no tiene permisos granulares (solo un string de rol).
- **Bitácoras** tiene algo de gating por `nivel_jerarquia`, no el sistema
  completo.
- `CORE_Menu_Nodos` solo tiene 3 nodos-esqueleto para cada uno de
  TH/Bienes/Bitácoras — no refleja su menú real.
- No existe override de permiso por usuario individual en ningún lado.
- La lista de módulos es un array PHP duplicado en `MenuController::MODULES`
  y `AdminController::moduleMeta()` — no hay tabla, un módulo nuevo no
  aparece sin tocar código.

Decisión de arquitectura del usuario: el central debe ser compatible con
**todos** los módulos, y para los que YA tienen su propio sistema de
permisos (como TH), los cambios se **sincronizan en ambas direcciones** —
editar en el portal actualiza TH y viceversa, sin que ninguna pantalla deje
de ser editable. Última escritura gana, con auditoría de origen en ambos
lados. Granularidad: hasta nivel de **acción individual** dentro de una
pantalla (ej. "Aprobar" en Acción de Personal), no solo visibilidad de menú.
Override de permiso por **usuario específico**, cascada usuario > rol.

Alcance de este documento: **Fase 0** (base reutilizable por cualquier
módulo) + **Fase 1** (piloto completo con TH) + **Fase 2** (Control de
Bienes) + **Fase 3** (Bitácoras Portuarias) — las cuatro fases están
diseñadas a nivel arquitectónico completo en este documento. El **orden de
construcción confirmado con el usuario** es TH primero (ya tiene un RBAC
real del otro lado, el caso más representativo y el que más prueba el
patrón de sync bidireccional), Bienes y Bitácoras después, cada una en su
propio ciclo spec→plan→build una vez arrancada — no se implementan las
cuatro de una sola vez.

## Fase 0 — Base

### `CORE_Modulos` (nueva tabla)

Reemplaza el array PHP hardcodeado y duplicado. Mismos `id_modulo` que ya
usa `CORE_Menu_Nodos`/`CORE_Permisos_Nodo` (no se renumera nada existente).

```
CORE_Modulos
  id_modulo     TINYINT PK
  codigo        NVARCHAR(30) UNIQUE   -- 'PORTAL','TH','BIENES','BITACORAS',...
  nombre        NVARCHAR(150)
  icono         NVARCHAR(50)          -- clase fa-*
  color         NVARCHAR(10)          -- hex
  tipo          NVARCHAR(20)          -- 'nativo' | 'embebido'
  base_url      NVARCHAR(200) NULL    -- ej '/apps/talento_humano' (solo embebido)
  conexion_bd   NVARCHAR(50)  NULL    -- clave lógica en config/connections.php->databases (ej 'talento')
  orden         SMALLINT DEFAULT 0
  estado        TINYINT DEFAULT 1
  fecha_creacion DATETIME2 DEFAULT SYSDATETIME()
```

Seed: los 13 módulos actuales de `MenuController::MODULES`, mismos IDs.
`MenuController` y `AdminController` pasan a leer esta tabla (`SELECT ...
FROM CORE_Modulos ORDER BY orden`) en vez del array — elimina la
duplicación existente entre los dos archivos de paso.

Nueva UI mínima (`/admin/modulos`): listar, crear, editar, desactivar. Un
módulo nuevo (nativo o Patrón B) queda disponible en `/admin/menu` y
`/admin/roles/{id}/permisos` sin tocar PHP.

### `CORE_Permisos_Nodo_Usuario` (override por usuario)

```
CORE_Permisos_Nodo_Usuario
  id_perm_usuario  INT PK IDENTITY
  id_usuario       INT FK CORE_Usuarios
  id_modulo        TINYINT
  opcion           TINYINT
  items            TINYINT
  subitems         SMALLINT
  nivel_crud       TINYINT           -- 0-4, misma semántica jerárquica; 0 = revoca explícitamente aunque el rol sí dé acceso
  estado           TINYINT DEFAULT 1
  fecha_asignacion DATETIME2 DEFAULT SYSDATETIME()
  asignado_por     INT NULL
  UNIQUE (id_usuario, id_modulo, opcion, items, subitems)
```

`fn_TienePermisoNodo` se **extiende** (no se reemplaza) para consultar
primero esta tabla: si existe fila activa para ese usuario+nodo, decide ella
sola (incluyendo nivel 0 = acceso explícitamente revocado); si no hay fila,
cae al `EXISTS` por rol que ya tiene hoy. Mismo patrón cascada
usuario>módulo>global ya usado en inactividad de sesión y en el resto del
proyecto — nada nuevo conceptualmente, solo un nivel más en una función que
ya existe.

UI: en la pantalla de perfil/edición de un usuario (`/admin/usuarios/{id}`),
nueva pestaña "Permisos individuales" — mismo componente de tabla-checklist
del spec `2026-07-29`, reutilizado, con badge "excepción" en las filas donde
el usuario tiene una fila propia distinta de lo que le daría su rol.

## Fase 1 — Piloto Talento Humano

### `CORE_Roles_Modulo_Map` (mapeo de identidad de roles)

```
CORE_Roles_Modulo_Map
  id_map        INT PK IDENTITY
  id_modulo     TINYINT FK CORE_Modulos
  id_rol_portal INT FK CORE_Roles
  id_rol_externo INT      -- rol_id dentro del esquema propio del módulo (ej th_roles.rol_id)
  UNIQUE (id_modulo, id_rol_portal)
  UNIQUE (id_modulo, id_rol_externo)
```

Seed para TH (`id_modulo=11`), confirmado con el usuario:

| `th_roles.rol_id` (TH) | `CORE_Roles.id_rol` (Portal) |
|---|---|
| 1 Super Administrador | 1 ADMIN |
| 2 Director de Talento Humano | 11 DIR_TH |
| 3 Analista de Nómina | 12 ANALISTA_TH |
| 4 Funcionario (Lectura) | 21 LECTOR |

### `CORE_Menu_Nodos.es_accion` (columna nueva, BIT DEFAULT 0)

Distingue nodos que son un ítem real de sidebar (`es_accion=0`, como hoy)
de nodos que son un permiso de **acción puntual** dentro de una pantalla sin
link propio (`es_accion=1`, ej. "Aprobar Acción de Personal", "Eliminar
empleado"). Ambos viven en la misma jerarquía MOIS y usan el mismo
`nivel_crud`/`fn_TienePermisoNodo`. El sidebar (nativo y, tras esta fase, el
de TH) solo pinta `es_accion=0`; los controladores consultan también los
`es_accion=1` antes de ejecutar la acción.

Nodos a crear para TH (reemplazan los 3 nodos-esqueleto actuales,
`id_modulo=11`): árbol completo — Inicio, Directorio de Personal, Acción de
Personal (+ acciones Guardar/Aprobar/Anular), Movimientos internos, Estudio
Socioeconómico, Biblioteca de Formularios, Estructura y cargos (Admin >
Maestros), Auditoría, Administración (Usuarios/Roles/Políticas).

### Sync bidireccional central ↔ TH

**Central → TH** (`AdminController::guardarPermisos()`, portal): al
guardar, por cada nodo tocado con `id_modulo=11` y rol con mapeo en
`CORE_Roles_Modulo_Map`, UPSERT cross-DB en `Talento_Humano.dbo.th_permisos_rol`
para `rol_id = id_rol_externo`, columna `modulo_id` resuelto contra
`th_modulos.codigo_modulo` (tabla ya existente en TH; cada nodo MOIS de TH
que se cree en Fase 1 debe declarar a qué `codigo_modulo` corresponde — el
detalle de esa correspondencia queda para el plan de implementación, no
cambia el diseño), traduciendo `nivel_crud` → 4 flags:
`puede_visualizar = nivel>=1`, `puede_crear = nivel>=2`, `puede_editar =
nivel>=3`, `puede_eliminar = nivel>=4`. Sin pérdida en esta dirección
(jerárquico → acumulativo siempre representable).

**TH → Central** (`AdminController::guardarPermisos()` de TH, ya existe la
ruta `admin/roles/guardar-permisos`): al guardar, por cada fila de
`th_permisos_rol` tocada con rol mapeado, UPSERT cross-DB en
`PORTAL_APM.dbo.CORE_Permisos_Nodo`. Traducción con pérdida documentada:
`nivel_crud = ` el nivel jerárquico más alto **contiguo** desde
`puede_visualizar` (ej. visualizar=1,crear=1,editar=0,eliminar=1 → nivel=2,
se pierde que eliminar seguía habilitado). Si se detecta una combinación no
contigua, se registra una advertencia en la auditoría de TH
(`sp_th_registrar_auditoria`, acción `SYNC_PERMISO_NO_CONTIGUO`) además del
UPSERT — no bloquea el guardado, solo deja rastro de que hubo
simplificación. En la práctica casi ningún rol real usa combinaciones no
jerárquicas (quien puede eliminar generalmente puede editar y ver).

**Conflicto simultáneo**: última escritura gana (UPDATE simplemente pisa).
Cada escritura, propia o reflejada, se audita con origen (`CENTRAL`|`TH`),
usuario, timestamp — en `CORE_Auditoria` (portal) y `th_logs_auditoria`
(TH, inmutable). No hay bloqueo optimista ni lock — el caso de dos admins
editando el mismo nodo en el mismo segundo se considera aceptable de perder
sin fricción extra, dado que es una pantalla de un solo operador casi
siempre.

**Manejo de fallos**: si el UPSERT cross-DB falla (BD del otro lado caída),
el guardado local **no se revierte** — se guarda igual del lado que se
editó, se registra el fallo de sync en auditoría, y una tarea futura
(fuera de este documento) podría reintentar. Prioridad: nunca bloquear a un
admin por que el otro lado esté caído.

### Sidebar de TH

`apps/talento_humano/shared/menu.php` deja de tener el árbol hardcodeado;
consulta (cross-DB, mismo patrón ya usado en `Auth::resolveInactividad()`)
`fn_TienePermisoNodo` para cada nodo `es_accion=0` de `id_modulo=11` y arma
el sidebar con lo que devuelva. El chequeo de acción dentro de una pantalla
(`Auth::can()`) sigue siendo 100% de TH, local y rápido — ahora alimentado
por datos que llegan sincronizados desde el central, sin cambiar su forma
de consulta interna.

## Fase 2 — Control de Bienes (revisado 2026-08-11: sí hay sync, a dos niveles)

**Corrección sobre la primera versión de este documento**: la premisa
"Bienes no tiene ningún sistema de permisos granular hoy" era falsa.
Investigación de código real (segunda pasada, al empezar la implementación)
encontró:

- `inv_permisos` (tabla real) + `Credenciales/PermisoModel.php` +
  `PermisoController.php` + vista propia `/credenciales/permisos`: sistema
  **por usuario individual** (no por rol) que ya filtra el sidebar Y
  bloquea rutas server-side (`Router::checkPermisos()`). Sin embargo es
  puramente binario (¿el usuario ve esta `route` sí/no?) — **cero
  distinción Ver/Crear/Editar/Eliminar**, y el rol `'Administrador'`
  bypasea todo.
- Hueco real de seguridad confirmado por código: `Router::checkPermisos()`
  gatea por `route` (`?route=inventario`), pero `Router::dispatch()` llama
  a `$actionName` tomado de `$_GET['action']` **sin validarlo contra el
  action por defecto de esa route** — cualquier usuario con acceso de
  lectura a `route=inventario` puede llamar
  `?route=inventario&action=eliminar` aunque el menú nunca muestre un botón
  de borrar. Todas las escrituras (`guardar`, `eliminar` en
  `BinController`/`EstacionController`/`MonitoreoController`/
  `EmpleadoController`/`UsuarioController`/`ConfigController`) están hoy
  expuestas a cualquiera con visibilidad de la pantalla asociada.
- Sesiones puenteadas desde el portal (`index.php`, rol sintetizado desde
  `nivel_jerarquia`) **no tienen fila en `inv_permisos`** (esa tabla está
  keyed por `inv_usuarios.id`, un espacio de IDs local; el puente usa el
  `id_usuario` del portal) — un usuario del portal que entra a Bienes sin
  ser `nivel_jerarquia>=3` hoy prácticamente no ve nada, salvo lo que esté
  en la lista pública de rutas. Es un bug funcional preexistente, no algo
  que este trabajo introduce — Fase 2 lo corrige como efecto colateral.

Diseño corregido, confirmado con el usuario ("a nivel completo no solo
usuario sino roles también, de manera completa y abarque todo"):

1. **Nivel rol (nuevo para Bienes, no existía)**: tabla nueva
   `inventario.dbo.inv_roles` (4 filas: Administrador/Supervisor/Operador/
   Auditor — el dato real tiene un 4to rol, `Auditor`, que la v1 de este
   documento no contemplaba) + `inventario.dbo.inv_permisos_rol`
   (`rol_id`, `route_key`, `puede_visualizar/crear/editar/eliminar` — mismo
   shape que `th_permisos_rol`, mismo patrón). Esto se convierte en la
   fuente de verdad nativa de Bienes para permisos de rol, y sincroniza
   bidireccional con `CORE_Permisos_Nodo` vía `CORE_Roles_Modulo_Map`
   (`id_modulo=12`) — mismo patrón exacto que TH en Fase 1. Se crean 3
   roles nuevos en el portal (`BIENES_SUPERVISOR`, `BIENES_OPERADOR`,
   `BIENES_AUDITOR`, departamento "CONTROL DE BIENES" id=23) — no existía
   ningún rol portal equivalente a mapear, a diferencia de TH. El rol
   `Administrador` de Bienes mapea al `ADMIN` (`id_rol=1`) ya existente.
2. **Nivel usuario, dos caminos distintos según el tipo de cuenta**:
   - Cuentas **puenteadas desde el portal** (tienen `id_usuario` real en
     `CORE_Usuarios`): gobernadas 100% por la infraestructura de Fase 0 ya
     construida — `fn_TienePermisoNodo` cross-DB
     (`inventario`→`PORTAL_APM`), que ya resuelve rol + override de usuario
     (`CORE_Permisos_Nodo_Usuario`) en cascada. Cero tabla nueva necesaria
     para este camino — y resuelve el bug funcional de arriba.
   - Cuentas **nativas de Bienes** (`inv_usuarios` sin cédula real en el
     portal — login propio, ver `htdocs_is_junction`/memoria de módulo):
     no tienen `id_usuario` de portal contra el cual resolver
     `fn_TienePermisoNodo`, así que no pueden sincronizar con
     `CORE_Permisos_Nodo_Usuario` (no hay fila del lado central que
     represente esa cuenta). Se quedan gobernadas por `inv_permisos`
     **local**, que se **actualiza** (no se reemplaza) para expresar
     `nivel_crud` 0-4 real en vez de solo visible/oculto — incluye
     `ALTER TABLE inv_permisos ADD nivel_crud TINYINT NOT NULL DEFAULT 1`
     con backfill de las filas existentes a nivel 1 (preserva el
     comportamiento actual exacto, que nunca fue más que "visible"). Esta
     rama sigue siendo puramente local, sin sync — mismo motivo que la v1
     de este documento daba (no hay contraparte central), solo que ahora
     aplica nada más a este subconjunto de cuentas, no a todo Bienes.
3. `id_modulo=12` en `CORE_Menu_Nodos`: reemplazar los 2 nodos-esqueleto
   (`opcion=1` "Panel" / "Sistema de Control de Bienes") por el árbol real
   de 15 opciones (Dashboard, Inventario General, Catálogo de Ítems, Ítems
   del Sistema, Tablas de Cabecera, Maestros, Ingresos, Egresos, Directorio
   de Personal, Bitácora del Sistema, Reportes, Períodos e IVA,
   Secuenciales, Gestión de Usuarios, Gestión de Permisos) — mismo patrón
   de captura-antes-de-borrar que TH Fase 1 para no perder los 6 roles
   (`ADMIN`, `AUDITOR`, `GERENTE`, `ASIST_GCIA`, `DIR_ADMIN`,
   `ANALISTA_ADMIN`) que ya tienen `CORE_Permisos_Nodo` real sobre esos
   nodos-esqueleto.
4. **Sin `es_accion`**: mismo razonamiento YAGNI que TH Fase 1 — el
   inventario real de escrituras (`docs/superpowers/plans/...`) no tiene
   ninguna acción que no encaje en el modelo `nivel_crud` 0-4 jerárquico
   ("dar de baja un bien" = `eliminar` nivel 4 sobre Inventario General;
   la idea original de "aprobar egreso" no corresponde a ningún código
   real — `MonitoreoController::guardar` no tiene paso de aprobación
   separado hoy).
5. `Router::checkPermisos()` (no `Controller.php` — el chequeo ya vive
   centralizado ahí) gana una tabla de políticas `(route, action) →
   (opción MOIS, nivel mínimo)`, mismo patrón que `$routePolicies` de TH,
   evaluada ANTES de despachar — cierra el hueco de acciones no gateadas
   descrito arriba. Rama dual: sesión puenteada → `fn_TienePermisoNodo`;
   sesión nativa → cascada local `inv_permisos` > `inv_permisos_rol`.
6. `layout.php` reemplaza el filtro por `InvPermiso::obtenerPermisosUsuario()`
   con la misma cascada dual de nivel (visualizar ⇔ nivel≥1).
7. `PermisoController`/`permisos.php`: gana una segunda pestaña "Permisos
   por Rol" (nueva, editando `inv_permisos_rol`, con sync hacia el central)
   junto a la pestaña existente "Permisos por Usuario" (se mantiene, ahora
   con selects V/C/E/D reales en vez de checkbox único de visibilidad).

## Fase 3 — Bitácoras Portuarias (revisado 2026-08-11: dos sistemas que ya divergen, no tres)

**Corrección sobre la primera versión de este documento**: investigación real
(segunda pasada) confirma que hay **dos** implementaciones paralelas de la
misma matriz "¿puede este departamento hacer X?" — no tres, ya que
`nivel_jerarquia` no es un tercer sistema independiente, está mezclado
como fallback DENTRO de una de las dos:

1. `includes/bit_auth_permissions.php` (procedural, funciones
   `apm_can_*()`) — comparación **exacta** (`===`) de
   `$_SESSION['apm_auth']['nom_departa']` normalizado contra strings
   literales (`'TECNOLOGIA DE LA INFORMACION'`, `'SEGURIDAD INTEGRAL'`,
   etc). Usada por `bit_sidebar.php` y por los archivos legacy standalone
   (`bit_dashboard_jefe.php`, `apis/bit_get_dashboard_live.php`) — y
   también, indirectamente, por la vista MVC `bit_listado.php` (la carga
   `bit_sidebar.php` vía el layout antes de renderizar la vista).
2. `modules/Portuaria/models/Auth.php` (OOP, métodos `Auth::can*()`) —
   comparación por **substring** (`str_contains`) del mismo campo, MÁS un
   fallback numérico por `nivel_jerarquia` que el sistema 1 no tiene.
   Usada por los 5 controladores reales (`PortController`/`PortXxxController`).

**Ambos sistemas están activos en la MISMA response** (confirmado:
`PortVisitaController::listado()`, gateado con `Auth::` OOP, renderiza una
vista que llama `apm_can_asignar_cedula_guest()` procedural) y **pueden dar
resultados distintos** para el mismo usuario en el mismo request si el
nombre de un departamento contiene como substring alguna de las palabras
clave (`'ADMINISTRATIV'`, `'SEGURIDAD'`) sin ser un match exacto — riesgo
real, no hipotético, con el catálogo real de `CORE_Departamentos`.

`requireLevel($minLevel, $nodo)` (el gancho ya construido en
`core/Controller.php`, cross-check confirmado idéntico byte a byte al de
Bienes/TH) está confirmado **sin ningún uso**, pero además **no es el
punto de integración real** — los controladores nunca lo llaman, todos
pasan por `Auth::guard()` + `Auth::canXxx()`. La integración correcta no es
"activar requireLevel", es reescribir `Auth::canXxx()` para que consulte
`fn_TienePermisoNodo` (mismo patrón, otro punto de entrada), y hacer que
`apm_can_*()` procedural pase a ser un wrapper delgado sobre `Auth::canXxx()`
— así los dos sistemas colapsan en una sola implementación real, eliminando
la divergencia en vez de solo documentarla.

`CORE_Menu_Nodos` para `id_modulo=13` hoy tiene **un solo nodo plano**
(el punto de entrada de la SPA, sin ninguna granularidad) — a diferencia
de Bienes, acá la premisa "no hay nada" sí era correcta.

**Huecos reales de autorización encontrados** (nuevos, no estaban en la v1
de este documento) que Fase 3 cierra de paso:
- `PortCatalogoController::api()` y `apiPersonas()` (CRUD de personas,
  empresas, destinos, motivos, funcionarios, niveles de incidente) **no
  tienen ningún chequeo de permiso más allá de estar logueado** — cualquier
  usuario autenticado del portal puede crear/editar/desactivar cualquier
  catálogo hoy.
- `PortDashboardController::ejecutivo()` — sin chequeo de permiso.
- `bit_reporte_diario_supervisor.php` (archivo legacy standalone, fuera
  del router) — solo verifica sesión, cero autorización.
- `/importar-funcionarios` (POST): la ruta POST nunca se registró en
  `routes.php` — el import CSV es hoy inalcanzable vía el router (bug de
  ruteo preexistente, no de permisos). Se documenta pero **no se arregla
  acá** — no es parte de este trabajo; sí se le da su nodo MOIS para que,
  si algún día se arregla la ruta, ya quede gobernado.

Diseño:

1. `id_modulo=13` en `CORE_Menu_Nodos`: árbol real de 13 opciones (Dashboard,
   Dashboard Ejecutivo, Dashboard de Jefatura, Registrar Ingreso, Listado
   de Visitas, Registros Base/Catálogos, Rondas de Vigilancia, Cámaras
   CCTV — separadas de Rondas, hoy comparten un solo flag por accidente
   histórico, se separan porque ya son controladores/rutas distintos —,
   Reporte de Supervisor, Importar Funcionarios, Configuración de Días de
   Bitácora). Reemplaza el único nodo plano existente — no hay nada que
   preservar (nunca se otorgó ningún `CORE_Permisos_Nodo` real sobre ese
   nodo-esqueleto, se verifica antes de borrar por las dudas, mismo patrón
   de captura que TH/Bienes).
2. `Auth::tienePermisoNodo(int $opcion, int $items, int $subitems, int
   $nivelMin): bool` — nuevo método estático en
   `modules/Portuaria/models/Auth.php`, mismo query que
   `Controller::tienePermisoNodo()` (same-DB, `id_modulo=13` fijo), usando
   `$_SESSION['apm_auth']['id_usuario_portal'] ?? $_SESSION['user_id']`
   (cubre el caso legacy-standalone donde `id_usuario_portal` no se
   setea, sin tocar el bug de atribución FK que eso arrastra — fuera de
   alcance de este trabajo de permisos).
3. Cada `Auth::canXxx()` se reescribe para llamar
   `self::tienePermisoNodo($opcion, 0, 0, $nivelMin)` contra el nodo que le
   corresponde, en vez de comparar `nom_departa`. Se cierran los 3 huecos
   reales agregando el chequeo que hoy no existe (`api()`/`apiPersonas()`
   de catálogos, `ejecutivo()`, reporte de supervisor) con nuevos
   `Auth::canXxx()`.
4. `apm_can_*()` (procedural, `bit_auth_permissions.php`) pasan a ser
   wrappers de una línea sobre el `Auth::canXxx()` equivalente — mismos
   nombres (los usa `bit_sidebar.php` y las vistas), misma firma, cero
   comparación de texto. Esto es lo que efectivamente unifica los dos
   sistemas.
5. Sin `es_accion` — mismo razonamiento YAGNI que TH/Bienes: "aprobar
   ronda" no existe como paso separado en el código real
   (`PortRondaController::api()` solo crea/edita por `id_detalle>0`);
   "cerrar visita" es `registrarSalida()`, una edición más sobre el mismo
   registro — ambos caben en el modelo `nivel_crud` 0-4 jerárquico.
6. `bit_sidebar.php` **no se reescribe** (desviación consciente de la v1
   de este documento, que proponía reconstruirlo desde los nodos MOIS
   directamente): ya llama a `apm_can_*()` por nombre, y esas funciones
   pasan a estar respaldadas por MOIS real vía el punto 4 — reescribir la
   estructura del sidebar no agrega nada, ya que el resultado (decisión
   real basada en permiso) es idéntico sin tocarlo. Mismo tipo de ajuste
   que "shared/menu.php ya funciona solo" en TH Fase 1.
7. Sin sync bidireccional — confirmado, no existe ninguna pantalla
   `admin/roles` propia de Bitácoras que reconciliar.

## Fuera de alcance de este documento

- Reintentos automáticos de sync tras un fallo cross-DB (Fase 1).
- UI de "duplicar permisos de otro rol" (mencionada y pospuesta ya en el
  spec `2026-07-26`).
- Cambiar el modelo `nivel_crud` jerárquico del central (decisión ya
  tomada y confirmada de nuevo en este documento).

## Testing

- Migraciones SQL: aplicar contra BD real con backup previo (mismo
  procedimiento que la actualización de TH del 2026-08-11), verificar con
  queries directas antes/después.
- `fn_TienePermisoNodo` extendida: casos con y sin override de usuario,
  verificar contra usuarios de prueba desechables (crear, ejercer, borrar
  en la misma sesión de trabajo — patrón ya establecido este proyecto).
- Sync bidireccional: editar un permiso en `/admin/roles/{id}/permisos`
  para un rol mapeado a TH, confirmar el reflejo en `th_permisos_rol`
  (query directa); editar en TH, confirmar el reflejo en
  `CORE_Permisos_Nodo`. Caso de combinación no contigua TH→Central,
  confirmar que se guarda el nivel esperado y aparece la advertencia de
  auditoría.
- Sidebar de TH: verificación real en navegador (Playwright o Claude-in-Chrome,
  como el resto de este proyecto) con un usuario de permisos limitados,
  confirmar que el sidebar oculta lo que corresponde.
- Bienes (Fase 2): confirmar que un usuario con `inv_roles` limitado ya no
  ve los ítems que antes veía solo por el string de rol; confirmar que el
  puente portal→Bienes sigue funcionando con el nuevo mapeo en vez del
  `if/else` de `nivel_jerarquia` que reemplaza.
- Bitácoras (Fase 3): confirmar que cada `apm_can_*()` reescrita da el
  MISMO resultado que daba antes para los roles/departamentos reales ya en
  uso (evitar regresión silenciosa: alguien que hoy ve el Bloque Admin por
  su departamento debe seguir viéndolo tras el cambio, ahora vía el nodo
  MOIS equivalente) antes de retirar la comparación de texto vieja.
