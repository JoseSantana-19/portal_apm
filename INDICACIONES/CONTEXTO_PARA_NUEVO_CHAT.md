# Contexto para un chat nuevo — Portal APM

Este archivo es el puente para arrancar una conversación nueva sin perder
el hilo de lo trabajado. Léelo primero; los detalles técnicos exhaustivos
viven en `DOCUMENTACION_SISTEMA.md` (documento vivo, se actualiza cada
sesión) y en `INDICACIONES/GUIA_MODULOS_NUEVOS_Y_ACTUALIZACIONES.md`.

## Qué es este proyecto

**Portal APM** — portal de gestión de la Autoridad Portuaria de Manta.
PHP 8+ nativo (MVC propio, sin framework), `sqlsrv` nativo (sin PDO en el
portal nativo), SQL Server 2014+.

**Principio de arquitectura clave:** Portal APM **no tiene módulos de
negocio propios** — es el hub central (login, permisos, menú,
administración). Los módulos reales son proyectos independientes que se
integran:

- **Talento Humano** (`apps/talento_humano`) y **Control de Bienes**
  (`apps/control_bienes`) — apps embebidas (Patrón B): proyecto propio, BD
  propia, se autentican por sesión PHP compartida (`$_SESSION['user_id']`
  que setea el login del portal) — no hay bridge HTTP, es literalmente la
  misma sesión. **Ninguno de los dos es un "módulo nativo" en `modules/`**
  — si algo dice lo contrario, está desactualizado.
- **Bitácoras Portuarias** (`apps/bitacoras`) — **desde 2026-08-04 es app
  embebida Patrón B, igual que TH/Bienes** (antes vivía nativo en
  `modules/Portuaria`, Patrón C — ese patrón quedó retirado). Se autentica
  por sesión compartida del portal (`Auth::hydrateFromPortal()`), sin
  puente HTTP. `bit_usuarios_apm` se resuelve por cédula (find-or-create),
  no por el ID de `CORE_Usuarios`.
- `modules/` del portal **solo tiene 2 carpetas reales**: `Central`,
  `Credenciales`. `Portuaria` se fue a `apps/bitacoras` (2026-08-04).
  `Control_Acceso` existió hasta 2026-07-28 y se dio de baja por completo
  — si aparece en código/docs viejos, ninguno de los dos existe ya.
- **Talento Humano ahora tiene login + MFA/TOTP + RBAC propios**
  (`th_roles`, `th_permisos_rol`, actualizado 2026-08-11) — coexiste con el
  puente SSO del portal, no lo reemplaza. Ver "Sistema de permisos
  centralizado" más abajo.

Todo lo demás que puedas encontrar bajo `controllers/`, `views/` sueltos en
la raíz puede ser código legacy ya reemplazado — si dudás si algo está
vivo, revisá `routes.php` primero.

## Identidad: login solo por cédula, un solo usuario inicial

Cambio de fondo hecho esta sesión, no es un detalle menor:

- **No existe "nombre de usuario" en ningún formulario.** El login pide
  solo número de cédula. Internamente `CORE_Usuarios.nombre_usuario` sigue
  existiendo (lo usan `sp_Login`/SSO) pero su valor **siempre es la
  cédula** — nunca se muestra ni se tipea por separado.
- **`PORTAL_APM` arranca con una sola cuenta** (admin, cédula de prueba
  `1234567777`, contraseña `Apm2024*`). Ya no hay ~20 cuentas de prueba por
  rol/departamento.
- **No hay creación manual de usuarios.** El único camino para una cuenta
  nueva es `/admin/usuarios/nuevo` → elegir un empleado real de Talento
  Humano → `AdminController::crearUsuarioDesdeEmpleado()`. El
  `nombre_usuario`/cédula se autogeneran del empleado, nunca se tipean.
- Detalle completo en `DOCUMENTACION_SISTEMA.md` §11.

## Cómo trabajar en este proyecto (reglas ya establecidas)

- **Nunca commitear ni pushear sin confirmación explícita del usuario, cada
  vez.** No asumas que un "sí" anterior aplica a cambios nuevos.
- `config/connections.php` **no se sube a git** (gitignored, credenciales
  por máquina). La plantilla trackeada es `connections.example.php`. Nunca
  reintroducir credenciales hardcodeadas en ningún archivo.
- Cambios a los scripts SQL **siempre se prueban antes** contra una base
  descartable (clon vía `SELECT INTO` tabla por tabla si `BACKUP`/`RESTORE`
  no tiene permisos de carpeta — pasa seguido en SQL Server Express; ojo
  que `SELECT INTO` no copia PK/FK/índices, hay que agregarlos a mano antes
  de probar constraints), correr con `php db/run_sql.php <archivo> .\INSTANCIA`,
  **verificar consultando la BD directo** (no confiar en el mensaje de
  éxito del script — un error real dentro de un bloque `IF...BEGIN...END`
  puede no hacer que `sqlsrv_query()` devuelva `false`), `DROP DATABASE` al
  final. Nunca aplicar un cambio de esquema real sin probarlo así primero.
  Metodología completa y gotchas de SQL Server (batch/GO, transacciones
  MARS, `CREATE OR ALTER` requiere 2016+) en
  `INDICACIONES/GUIA_MODULOS_NUEVOS_Y_ACTUALIZACIONES.md` §2.7-2.8.
- **`C:\xampp\htdocs\portal_apm` es un junction de Windows al repo** (mismo
  archivo físico en disco, confirmado por file ID idéntico) — **nunca hace
  falta copiar/sincronizar nada**, cualquier edición al repo ya está
  sirviéndose en vivo. (Esto reemplaza cualquier aviso anterior sobre
  sincronizar a mano o sobre una carpeta `portal_apm_n` — ya no aplica.)
- Al actualizar un módulo integrado (`apps/talento_humano`,
  `apps/control_bienes`) con una versión nueva del proyecto origen: **nunca
  copiar la carpeta entera encima** — seguir la metodología de diff a 3
  bandas en `INDICACIONES/GUIA_MODULOS_NUEVOS_Y_ACTUALIZACIONES.md` §2
  (preserva integraciones propias, evita perder fixes ya aplicados).
- Nada de features o refactors no pedidos. El usuario prefiere iteración
  rápida y directa; corrige cuando algo no es lo que pidió.

## Qué se hizo en la sesión más reciente (2026-08-11 a 2026-08-12)

Sesión maratónica: sistema de permisos centralizado (MOIS real, granular,
por rol Y por usuario) extendido a los 3 módulos integrados, más una
pasada grande de estilo/UX del menú lateral. **Ya commiteado y pusheado**
a `main` (commit `fe6ee2e`, 209 archivos) — si `git log -1` no muestra ese
hash como el más reciente, algo cambió después; correr `git status` para
ver qué hay pendiente.

### 1. Sistema de permisos centralizado ("permisos_centrales", Fase 0-3)

Antes, TH/Bienes/Bitácoras tenían en `CORE_Menu_Nodos` un esqueleto de 2-3
nodos genéricos ("Panel" + "Sistema Completo") — el control de acceso real
vivía **adentro** de cada módulo (flags booleanos por departamento en
Bitácoras, nada en Bienes, tablas propias en TH). Ahora los 3 tienen un
**árbol MOIS real, una fila por pantalla de verdad**, gobernado por
`fn_TienePermisoNodo` — la misma función que usa Central:

| Módulo | id_modulo | Nodos reales | Fuente de verdad nativa |
|---|---|---|---|
| Talento Humano | 11 | 15 opciones + 6 ítems reales (Auditoría×2, Prototipos×4) | `Talento_Humano.dbo.th_permisos_rol` (rol nativo × módulo, V/C/E/D) |
| Control de Bienes | 12 | 15 opciones | `Inventario.dbo.inv_permisos_rol` (existe, vacía — sin sembrar aún) |
| Bitácoras | 13 | 13 opciones + 10 ítems reales (Registros Base×7, CCTV×3) | Ninguna (era 100% booleano por depto, no granular) |

Piezas nuevas: `CORE_Roles_Modulo_Map` (mapea rol portal ↔ rol nativo del
módulo, por `id_modulo`), `SyncPermisosModulo` (`core/SyncPermisosModulo.php`
— sincronización bidireccional: cambiar un permiso desde `/admin/roles`
también actualiza `th_permisos_rol`/`inv_permisos_rol` si el rol está
mapeado, y viceversa desde el lado nativo). Bienes además tenía un hueco de
seguridad real: `?action=` en su router podía invocar cualquier método
público del controller sin importar la ruta declarada — cerrado con
`Router::POLITICAS` (tabla explícita ruta+acción → opción MOIS + nivel
mínimo).

**Gotcha real, se repitió 3 veces (TH, Bienes, Bitácoras):** al migrar de
"esqueleto viejo" al árbol nuevo, la lógica de "preservar acceso ya
otorgado" solo re-otorgaba el nivel sobre el nodo de entrada (Dashboard),
no sobre el resto de pantallas reales — roles que antes tenían acceso
amplio (Bitácoras: booleano por depto = todo o nada) quedaron viendo solo
el Dashboard. Corregido con 3 scripts de restauración
(`db/{bitacoras,th,bienes}_restaurar_acceso_completo.sql`) — para TH se usó
la fuente nativa real (`th_permisos_rol`) en vez de extender un nivel
genérico, porque sí tenía granularidad real que preservar. **Si se integra
o migra un módulo nuevo al árbol MOIS, replicar el nivel preservado sobre
CADA pantalla real, no solo la de entrada** — ver
`INDICACIONES/GUIA_MODULOS_NUEVOS_Y_ACTUALIZACIONES.md` §2.8 (nuevo gotcha
agregado ahí).

**3 huecos de seguridad reales cerrados en Bitácoras** (código nativo, no
solo el árbol de permisos): CCTV Cámaras reutilizaba el flag de "Rondas"
en vez de su propio permiso (`Auth::canAccederCctv()` existía sin
cablear); "Reporte supervisor" no tenía ningún chequeo (visible a
cualquier logueado); "Importar funcionarios" — controller sin gating,
cualquiera podía importar. Los 3 corregidos con permiso real por nodo
MOIS.

### 2. Estructura del Menú / Roles y Permisos ahora coinciden con el sidebar real

`/admin/menu` y `/admin/roles/{id}/permisos` ya mostraban un árbol
genérico basado en `CORE_Menu_Nodos` — pero varias pantallas de TH y
Bitácoras estaban **agrupadas** ahí (1 nodo = 1 grupo de pantallas) cuando
en el sidebar real del módulo eran links sueltos. Se expandió el árbol
para reflejar 1:1 lo que cada módulo muestra de verdad — investigado
leyendo el código real de cada sidebar (`apps/talento_humano/shared/menu.php`,
`apps/control_bienes/modules/Central/views/layout.php`,
`apps/bitacoras/modules/Portuaria/views/layouts/bit_sidebar.php`), no
asumido.

### 3. Menú lateral del portal — dedupe + estilo

Con más ítems reales por área, apareció un bug de UX: `Menu.php`
auto-registra cada pantalla plana como un ítem que se auto-referencia
(necesario para que no desaparezca del sidebar) — con áreas que ahora
tienen hermanos reales, ese auto-registro quedaba como una fila duplicada
con el mismo nombre que su propio encabezado. Corregido en 2 capas:
- `models/Acceso_/Menu.php` — si un ítem real de esa área ya apunta a la
  misma URL que el auto-registro, descarta el duplicado.
- `modules/Central/views/layouts/sidebar.php` — **nuevo aplanado a nivel
  de ÁREA** (antes solo existía a nivel de módulo): un área con una sola
  pantalla real se muestra como link directo, sin acordeón — elimina el
  doble-click en ~90% de las pantallas de TH/Bienes/Bitácoras.

### 4. Bitácoras: 2 scripts SQL pendientes ejecutados + fix de dato roto

`db/bitacoras_update_tipos_visita_adjuntos.sql` (tabla `bit_tipos_visita` +
adjuntos) y `db/bitacoras_externa_reg_empresas.sql` (128 filas reales de
`reg_empresas`, cascada de búsqueda de empresas que estaba silenciosamente
rota sin esos datos) — ambos aplicados a las BDs reales
(`PortuariaDemo`/`PortuariaExterna`) con backup previo. Además se portó
`bit_consulta.php` (detalle de ronda desde Dashboard Jefatura, quedaba
roto tras la migración a `apps/bitacoras`) como ruta+controller+vista real.

### 5. Documentación

Este archivo, `DOCUMENTACION_SISTEMA.md` y
`INDICACIONES/GUIA_MODULOS_NUEVOS_Y_ACTUALIZACIONES.md` actualizados en
esta misma sesión — ver §18 de `DOCUMENTACION_SISTEMA.md` para el
changelog completo. Además: `apps/.portal-portuario-private/` (secreto
real de TH, `auth-token.key`) no estaba en `.gitignore` — se agregó al
`.gitignore` raíz antes de commitear, no borrar esa entrada.

Diseño/plan de referencia de todo lo de arriba, si hace falta el detalle
completo de una decisión: `docs/superpowers/specs/2026-08-11-permisos-centrales-modulo-th-design.md`
y `docs/superpowers/plans/2026-08-11-permisos-centrales-fase{0-fase1-th,2-bienes,3-bitacoras}.md`.

### 6. Decisiones deliberadas — NO son bugs, no "arreglar" sin que el usuario lo pida

- **SUPERADO 2026-08-12 — ya no aplica:** lo de abajo describía que
  Bitácoras gateaba "Registros Base"/"CCTV" solo por grupo, no por
  sub-ítem. El usuario pidió explícitamente cerrarlo en sesión posterior:
  `bit_sidebar.php` ahora oculta cada sub-ítem individual
  (`$puedeCatPersonasSidebar` etc.) Y los controllers reales
  (`PortCatalogoController::renderMaestro()`, `PortCamaraController`) lo
  aplican server-side vía `Auth::canVerCatalogoX()`/`canVerCctvX()` — no
  solo el sidebar. De paso se corrigió un bug real: `PortCamaraController`
  gateaba las 3 pantallas CCTV con el permiso de RONDAS (opcion=6) en vez
  del propio de CCTV (opcion=7) — no se notaba porque todos los roles
  actuales tienen ambos a la vez. Ver memoria `portuaria_module` para el
  detalle completo.
- **`BIENES_SUPERVISOR`/`BIENES_OPERADOR`/`BIENES_AUDITOR` tienen una
  matriz de permisos por defecto que YO diseñé** (basada en
  `Router::POLITICAS` + el nombre/jerarquía de cada rol), no en un spec
  aprobado ni en datos nativos reales (`inv_permisos_rol` seguía vacía).
  Si el usuario reporta que alguno de los 3 puede/no puede hacer algo que
  no esperaba, no es una regresión — es esa matriz por defecto, ajustable
  desde `/admin/roles/{id}/permisos`.
- **`GERENTE`/`ASIST_GCIA` (TH) y los 6 roles genéricos preexistentes de
  Bienes** (ADMIN, AUDITOR, GERENTE, ASIST_GCIA, DIR_ADMIN, ANALISTA_ADMIN)
  se dejaron **a propósito** con acceso Dashboard-only a esos módulos —
  no están mapeados a ningún rol nativo (`CORE_Roles_Modulo_Map`), así que
  no había nivel real que preservar más allá de esa visibilidad ejecutiva
  genérica. No expandirlos a ciegas asumiendo que es el mismo bug que se
  corrigió para los roles sí-mapeados.
- **`Inventario.dbo.inv_permisos_rol` sigue vacía** — Bienes tiene el
  esquema de permisos por rol nativo listo pero sin sembrar. El control de
  acceso real de Bienes hoy corre por el lado del portal
  (`CORE_Permisos_Nodo` vía `fn_TienePermisoNodo`/`tienePermisoPortal()`),
  no por `inv_permisos_rol`.

### 7. Metodología de verificación usada toda la sesión (seguir igual)

- **SQL Server:** instancia `.\VICTUS` (nombre local de esta máquina —
  confirmalo si trabajás en otra). Para queries sueltas de verificación,
  patrón usado toda la sesión:
  ```php
  php -r "
  \$conn = require 'config/connections.php';
  \$c = sqlsrv_connect(\$conn['server_default'], ['CharacterSet'=>'UTF-8','TrustServerCertificate'=>true,'Database'=>'PORTAL_APM']);
  \$s = sqlsrv_query(\$c, 'SELECT ...');
  while (\$r = sqlsrv_fetch_array(\$s, SQLSRV_FETCH_ASSOC)) { echo json_encode(\$r) . PHP_EOL; }
  "
  ```
  Migraciones: `php db/run_sql.php db/archivo.sql .\VICTUS`. Backup real
  antes de tocar `PORTAL_APM`: `BACKUP DATABASE [PORTAL_APM] TO
  DISK='<carpeta de backup por defecto de la instancia>\nombre.bak'` (la
  carpeta se obtiene con `SELECT SERVERPROPERTY('InstanceDefaultBackupPath')`).
- **Verificación de UI:** navegador real (Claude-in-Chrome), nunca asumir
  que el código "debería" funcionar. Login de prueba: cédula
  `1234567777` / `Apm2024*` (admin, acceso total). Para probar un rol
  específico con menos privilegios: crear un usuario desechable por SQL
  directo (`CORE_Usuarios` + `CORE_Usuarios_Roles`, hash real con
  `password_hash()`), loguear, verificar, **borrar todo al final en la
  misma sesión** (`CORE_Auditoria`/`CORE_Usuarios_Roles`/`CORE_Usuarios`
  del usuario de prueba) — nunca dejar cuentas de prueba viviendo en la
  BD real.
- **Nunca confiar en "N batches ejecutados correctamente"** de
  `db/run_sql.php` — siempre verificar con una query directa después (ver
  gotchas de SQL Server en la guía de módulos §2.8).

## Plan viejo (2026-07-26) — verificar vigencia antes de asumir

`docs/superpowers/specs/2026-07-26-central-features-hardening-design.md`
sigue sin implementarse en varios de sus 6 ítems (KPIs de Bienes, Auditoría
unificada, Mi Perfil enriquecido, detección de cambios TH→cuenta). El
ítem 5 (Roles y Permisos de Bienes con pocos permisos) **ya no aplica** —
quedó cubierto por el sistema de permisos centralizado de esta sesión. El
ítem 6 (Estructura del Menú) también quedó cubierto, y más a fondo de lo
que el spec pedía. Si no hay otra prioridad mencionada, sigue siendo un
punto de partida razonable para los 4 ítems restantes — pero confirmar
contra la BD/código real antes de asumir que algo sigue pendiente tal cual
se describió en julio.

## Dónde mirar primero según la pregunta

| Pregunta | Dónde |
|---|---|
| "¿Cómo está estructurado X?" | `DOCUMENTACION_SISTEMA.md` |
| "¿Cómo integro un módulo nuevo o actualizo uno existente?" | `INDICACIONES/GUIA_MODULOS_NUEVOS_Y_ACTUALIZACIONES.md` |
| "¿Cómo instalo esto de cero?" | `README.md` (automático) o `INDICACIONES/INSTALACION_MANUAL_WAMPSERVER.md` (manual) |
| "¿Qué falta por hacer?" | `docs/superpowers/specs/2026-07-26-central-features-hardening-design.md` (verificar vigencia primero, ver arriba) |
| Esquema real de un módulo integrado | `Z.BASES DE DATOS/*.sql` |
| "¿Por qué el sidebar/la página se ve rara después de guardar algo?" | Revisar `data-bypass` en el `<form>` — ver regla de SweetAlert2 arriba |
| "¿Por qué el rol X puede/no puede ver Y en TH/Bienes/Bitácoras?" | Sección "Decisiones deliberadas" arriba primero (puede ser diseño, no bug) — después `/admin/roles/{id}/permisos` |
| "¿Cómo verifico algo en la BD o probé un rol de verdad?" | Sección "Metodología de verificación" arriba |
