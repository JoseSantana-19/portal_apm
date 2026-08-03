# Central — Endurecimiento e ideas de mejora (TH / Bienes)

## Contexto

Se revisaron los 3 scripts de BD "actualizados" de los módulos integrados
(`Talento_Humano.sql`, `inventario.sql`, `PortuariaDemo.sql`, exportados
2026-07-26) contra el código real de PORTAL_APM, para verificar que las
integraciones cross-DB construidas en esta misma sesión (paneles nativos,
identidad unificada, creación de cuenta desde TH, dashboards) siguen siendo
correctas, y para detectar oportunidades reales de mejora en las siguientes
áreas de Central: Dashboard, Dashboard Operativo, Gestión de Usuarios, Crear
cuenta desde Talento Humano, Roles y Permisos, Estructura del Menú, Auditoría
del Sistema, Mi Perfil.

Hallazgo general: la integración con **Talento Humano** (paneles, identidad,
creación de cuenta) coincide 100% con el esquema actualizado — no requiere
cambios. Los hallazgos accionables están en **Bienes** (endurecimiento) y en
**permisos/auditoría/perfil** (gaps reales, verificados en vivo contra la BD).

Portuaria queda explícitamente **fuera de alcance** en este ciclo: su
identidad (`bit_usuarios_apm`) sigue separada de `CORE_Usuarios`
(`PortUsuarioModel::validarUsuarioCore()` es un stub que siempre responde
válido) — es un hecho ya conocido y aceptado, no algo a resolver ahora.

## Alcance de este ciclo

1. Blindar KPIs de Bienes (magic numbers → constantes documentadas)
2. Auditoría del Sistema (unión cross-DB con TH e Inventario)
3. Mi Perfil enriquecido (datos en vivo desde TH)
4. Crear cuenta desde TH (detección de cambios/bajas + pulido de UI)
5. Roles y Permisos (grants faltantes de Bienes + gap documentado en TH)
6. Estructura del Menú — **solo ideas, no implementar en este ciclo**

> **Actualización 2026-07-28** — Sesión de implementación real sobre 4, 5 y 6.
> Los ítems 1, 2 y 3 siguen aprobados pero **pendientes**, no se tocan en
> esta ronda (el usuario pidió foco explícito en Roles y Permisos,
> Estructura del Menú, y Crear cuenta desde TH). El detalle de 4/5/6 de
> abajo queda **reemplazado** por las secciones ampliadas al final del
> documento («4′», «5′», «6′»), que incorporan lo ya aprobado acá más lo
> pedido en esta sesión. Motivador adicional: se detectó y corrigió en esta
> sesión un error real en producción — `DashboardModel` todavía consultaba
> `ACCESO_Registros` (tabla eliminada la sesión anterior junto con el resto
> del módulo Control de Acceso, nunca desarrollado) y rompía `/dashboard`
> al iniciar sesión. Ver detalle en «Corrección de regresión» al final.

---

## 1. Blindar KPIs de Bienes

**Problema:** `PanelModel::getKpisBienes()` y `DashboardModel::getKpisEjecutivo/Operativo()`
comparan `estado_id` contra literales `111/112/113` directamente en el SQL.
`inventario.dbo.inv_estados` es una tabla de lookup genérica y compartida
(PK `idestado INT` explícito, no identity, reutilizada para ~20 estados de
flujo no relacionados con inventario físico) — nada impide que se
renumeren. Verificado en vivo: hoy 111=OPERATIVO, 112=EN MANTENIMIENTO,
113=FUERA DE SERVICIO (correcto), pero un cambio ahí rompe los KPIs en
silencio (muestran 0, sin error).

**Solución:** un solo punto de verdad para el mapeo, referenciado desde
ambos modelos:

```php
// modules/Central/models/BienesEstados.php (nuevo, ~10 líneas)
final class BienesEstados {
    // IDs de inventario.dbo.inv_estados — verificar ahí si cambian.
    public const OPERATIVO      = 111;
    public const MANTENIMIENTO  = 112;
    public const FUERA_SERVICIO = 113;
}
```

`PanelModel`/`DashboardModel` referencian `BienesEstados::OPERATIVO`, etc.,
en vez del literal. No cambia ningún comportamiento — solo hace explícita y
centralizada la dependencia.

---

## 2. Auditoría del Sistema — visión cross-DB real

**Problema:** `vw_AuditoriaGlobal` (fuente de `DashboardModel::getAuditRecent()`
y `getActividadReciente()`) solo cubre actividad nativa de PORTAL_APM. TH y
Bienes ya registran su propia actividad (`Talento_Humano.dbo.th_logs_auditoria`:
`fecha_hora, usuario, modulo, accion, descripcion_detalle, direccion_ip`;
`inventario.dbo.inv_log_eventos`: `fecha_registro, id_usuario, modulo, accion,
descripcion, resultado, ip_cliente`; `inventario.dbo.inv_log_errores` con
forma similar) — compatibles en forma con lo que ya consume la vista.

**Solución:** ampliar `vw_AuditoriaGlobal` (o vista hermana) con `UNION ALL`
hacia esas 3 fuentes, normalizando columnas:

```sql
SELECT modulo, accion AS operacion, usuario AS nombre_usuario,
       direccion_ip AS ip_address, 'OK' AS resultado, fecha_hora AS fecha_registro
FROM Talento_Humano.dbo.th_logs_auditoria
UNION ALL
SELECT modulo, accion, CAST(id_usuario AS NVARCHAR(50)), ip_cliente, resultado, fecha_registro
FROM inventario.dbo.inv_log_eventos
UNION ALL
SELECT modulo, accion, CAST(id_usuario AS NVARCHAR(50)), ip_cliente, 'ERROR', fecha_registro
FROM inventario.dbo.inv_log_errores
```

Ningún cambio en `DashboardModel` ni en la vista de Auditoría — al vivir en
la vista, se propaga solo.

---

## 3. Mi Perfil enriquecido

**Problema real (no solo oportunidad):** `UsuarioModel::findById()` consulta
`CORE_Usuarios` en crudo — ni siquiera usa `vw_Usuarios_Identidad` (creada
esta misma sesión). Mi Perfil hoy no refleja nombre/foto en vivo desde TH,
y mucho menos el resto de datos del empleado.

**Solución:**
- `findById()` pasa a leer de `vw_Usuarios_Identidad` (ya trae
  `nombre_completo`, `cedula`, `foto` en vivo con fallback a lo local).
- Si `id_empleado_th IS NOT NULL`, una segunda consulta trae de
  `Talento_Humano.dbo.th_empleados`: `telefono_movil, ciudad_residencia,
  direccion_domiciliaria, contacto_emergencia, emergencia_relacion,
  tel_emergencia, tipo_sangre, titulo` — solo lectura, mostrados en una
  sección "Datos de Talento Humano" en `Credenciales/perfil/index`.
- Edición sigue limitada a `correo` y tema, como hoy — los datos de TH se
  editan en TH, no se duplica la edición aquí.

---

## 4. Crear cuenta desde Talento Humano

El flujo actual (`empleadosTh/nuevoUsuarioDesdeEmpleado/crearUsuarioDesdeEmpleado`)
ya es 100% correcto contra el esquema. Se agrega:

- **Detección de cambios:** en la lista `Central/admin/usuarios`, para cada
  usuario con `id_empleado_th` no nulo, comparar su `id_departamento` actual
  contra lo que sugeriría `TH_Unidad_Map` para el `unidad_id` *actual* del
  empleado en TH (join cross-DB). Si difieren, badge "Unidad TH cambió" —
  no se autoaplica, el admin decide.
- **Detección de bajas:** mismo listado, si `th_empleados.estado=0` para el
  `id_empleado_th` vinculado, badge "Baja en TH" + acción manual
  "Desactivar cuenta portal" (un solo click, actualiza `CORE_Usuarios.estado=0`).
- **Pulido de UI:** mismas pantallas (`empleados_th.php`,
  `usuario_desde_empleado.php`), tratamiento visual consistente con el resto
  de Central (cards, badges, iconografía ya usada en los paneles TH/Bienes).

---

## 5. Roles y Permisos

**Hallazgo 1 (bug real, verificado en vivo):** `CORE_Permisos_Nodo` tiene
solo 3 filas para módulo 12 (Bienes) — únicamente el rol "Administrador TI"
puede ver `/panel/bienes` y `/apps/control_bienes/`. Ningún rol operativo
(Director Administrativo, Analista Administrativo, Gerente General — todos
existen en `CORE_Roles`) fue dado de alta ahí, a diferencia de módulo 11 (TH)
que sí tiene 6 roles correctamente concedidos (18 filas). Es una migración
incompleta de cuando se crearon los nodos de panel — se corrige agregando
los grants faltantes, espejando el patrón de TH.

**Hallazgo 2 (gap documentado, no se resuelve en este ciclo):**
`Talento_Humano.th_roles`/`th_permisos_rol` existen en la BD (poblados por
scripts de migración antiguos) pero ningún controlador PHP de
`apps/talento_humano` los lee — hoy, cualquiera que llegue a
`/apps/talento_humano` vía sesión compartida tiene acceso sin restricción a
todas sus acciones; no hay granularidad interna. Queda documentado como
limitación conocida en `DOCUMENTACION_SISTEMA.md`, no se construye ahora un
puente Central ↔ `th_permisos_rol` (decisión de alcance, no técnica).

---

## 6. Estructura del Menú — ideas para más adelante (no implementar ahora)

- Badge de estado por módulo en `/admin/menu` (Integrado / Pendiente),
  formalizando la distinción ya real entre TH/Bienes y Portuaria.
- Botón de "verificar integridad" que detecte nodos/permisos huérfanos
  (`estado=0` sin nodo padre activo) — el tipo de limpieza manual que se
  hizo para Portuaria esta sesión, para no repetir la auditoría a mano.
- Documentar la convención: módulos externos (Patrón B/C) se mantienen en
  profundidad "header + 1-2 items"; el anidamiento de 4 niveles queda
  reservado para los árboles propios de administración de Central.

---

## Fuera de alcance

- Integración real de identidad de Portuaria (`validarUsuarioCore` real) —
  explícitamente pospuesto por decisión del usuario.
- Cualquier cambio al esquema/código propio de `apps/control_bienes` o
  `apps/talento_humano` más allá de lecturas — son proyectos independientes
  (Patrón B), Central solo lee sus datos.
- Mover o reestructurar `modules/Portuaria` — se deja tal cual está.

## Riesgos / consideraciones

- Los `UNION ALL` cross-DB de auditoría añaden carga a cada render de
  Dashboard/Auditoría — mismo patrón ya usado (KPIs), aceptable a esta
  escala de datos.
- El badge de "cambios en TH" en Gestión de Usuarios es informativo, nunca
  automático — evita sorpresas de reasignar departamento/rol sin que el
  admin lo confirme.

---
---

# Actualización 2026-07-28 — Roles y Permisos / Estructura del Menú / Crear cuenta desde TH

## Corrección de regresión (ya aplicada, no parte del diseño — contexto)

En la sesión anterior se eliminaron las tablas `ACCESO_*` de `PORTAL_APM`
(Control de Acceso nunca se desarrolló, no correspondía tenerlas en el hub).
Se limpiaron rutas, controller, vistas y el SP `sp_GetKPIs_Ejecutivo` — pero
`DashboardModel::getKpisEjecutivo()`/`getKpisOperativo()` tenían **su propia
consulta embebida**, separada del SP, que también apuntaba a
`ACCESO_Registros` y no se detectó en el grep original (buscaba por nombre
de columna de salida, no por nombre de tabla). Rompía `/dashboard` al
iniciar sesión. Corregido: se quitó el sub-select de `ACCESO_Registros` de
ambos métodos y la tarjeta KPI "Ingresos Hoy" + gráfico relacionado de
`ejecutivo.php`/`operativo.php` (dato que ya no existe). Sincronizado a
`C:\xampp\htdocs\portal_apm` (copia real servida, distinta del repo git).

## 4′ — Crear cuenta desde Talento Humano (reemplaza sección 4)

Mantiene lo aprobado en la sección 4 original (detección de cambios de
unidad/bajas en TH, badges informativos) y agrega:

**Bug de rendimiento encontrado:** `AdminController::empleadosTh()` trae
TODOS los empleados activos de TH sin cuenta, sin `LIMIT`/paginación. En la
BD real hay 619 registros en `th_empleados` — si la mayoría no tiene cuenta
todavía, la tabla renderiza cientos de filas de una sola vez. Se agrega
paginación server-side (`OFFSET/FETCH`, mismo patrón que `AdminController::auditoria()`
ya usa) + el buscador existente pasa a filtrar también por página.

**Rediseño visual** (`empleados_th.php`, `usuario_desde_empleado.php`):
mismo lenguaje "glass" (`.gx`, tokens `--surface-app`/`--border-app`/etc.)
que ya usan Roles y Permisos / Estructura del Menú — hoy estas 2 pantallas
son las únicas que todavía usan `.card`/`.table` genéricos sin ese
tratamiento. Incluye: avatar con iniciales por fila (mismo patrón que
`sidebar-user-avatar`), chip de unidad organizacional en vez de texto
plano, badges de "cambios en TH"/"baja en TH" ya spec'ados en la sección 4
con el mismo lenguaje visual.

**Formulario de creación** (`usuario_desde_empleado.php`):
- Verificación en vivo de nombre de usuario disponible (`fetch` a un
  endpoint nuevo `GET /admin/usuarios/check-username?u=...` que consulta
  `SELECT 1 FROM CORE_Usuarios WHERE nombre_usuario=?`).
- Botón "Generar contraseña segura" — genera una contraseña aleatoria
  client-side (crypto.getRandomValues, sin depender del server) y la
  vuelca en el campo, visible en texto plano con botón de copiar (el
  usuario la comunica al empleado y este la cambia en su primer ingreso,
  como ya exige el flujo actual).
- Autosugerencia de `nombre_usuario` a partir de nombres/apellidos del
  empleado (`nombre.apellido`, minúsculas, sin tildes) — editable.

## 5′ — Roles y Permisos (reemplaza sección 5)

Mantiene el **Hallazgo 1** de la sección 5 original (bug real: módulo 12
Bienes con solo 3 filas en `CORE_Permisos_Nodo`, únicamente Administrador
TI) y su fix (agregar grants para Director Administrativo/Analista
Administrativo/Gerente General, espejando el patrón de TH) — se aplica en
`PORTAL_APM_COMPLETO.sql`, sección de seed de `CORE_Permisos_Nodo` módulo
12. El **Hallazgo 2** (permisos internos de `apps/talento_humano` sin
puente) sigue fuera de alcance, sin cambios.

Se agrega:

**Buscador en el árbol de permisos** — `rol_permisos.php` no tiene filtro
de texto (Estructura del Menú sí, vía `moisSearch()`). Se agrega el mismo
patrón: input con `oninput`, filtra `.perm-module`/filas por texto,
respeta el estado abierto/cerrado de los `<details>`.

**Badge de alerta por módulo sin cobertura de roles** — en el header de
cada módulo del árbol, si ningún rol activo salvo Administrador TI tiene
`nivel_crud>=1` en ese módulo, mostrar un badge "⚠ Solo Administrador TI"
(cálculo en PHP al construir `$tree` en `AdminController::rolPermisos()`
recorriendo `CORE_Permisos_Nodo` agregado por módulo — sin nueva consulta,
ya se tienen los datos). Esto habría marcado el bug de Bienes al instante
en vez de necesitar una auditoría manual.

**"Duplicar permisos de otro rol"** — selector en la cabecera de
`rol_permisos.php`: elegir un rol existente y copiar su matriz completa de
`nivel_crud` como punto de partida (solo en memoria/formulario, no se
guarda hasta que el admin presiona "Guardar Permisos" — mismo botón que ya
existe). Nueva ruta `GET /admin/roles/{id}/permisos/copiar-de/{otroId}`
que devuelve JSON `{clave: nivel}` y el JS existente (`setAll`/`applyStyle`)
lo aplica a los `<select>`.

**Visual:** los nombres de módulo en el árbol (`$moduleNames` hardcodeado
en `AdminController::rolPermisos()`) pasan a usar el mismo ícono+color que
`MenuController::MODULES` — se extrae ese array a una constante compartida
(`ModuloMeta::LIST` o similar, `core/` o `modules/Central/`) para no
duplicar la tabla de 13 módulos en 2 controllers.

## 6′ — Estructura del Menú (reemplaza sección 6 — ahora SÍ se implementa)

La sección 6 original decía "solo ideas, no implementar" — el usuario pidió
explícitamente implementarlo ahora. Diseño:

**Problema de fondo:** el sidebar (`modules/Central/views/layouts/sidebar.php`)
se renderiza 100% server-side a partir de `Menu::getUserMenu()` en cada
carga de página — no existe hoy ningún endpoint que devuelva el menú
"fresco" sin recargar. Además, cada toggle de `/admin/menu` guarda
inmediato vía `fetch()` (`MenuController::toggle()`), lo cual el usuario
encontró confuso: “actualiza” la fila en el árbol de admin, pero el
sidebar de cualquier pestaña/usuario sigue mostrando el estado viejo hasta
recargar.

**Diseño:**
1. **Staging client-side**: los interruptores de `index.php` (árbol MOIS)
   dejan de llamar `fetch()` al toque. Solo cambian de estilo localmente y
   se agregan a un `Map` en memoria `{id_nodo: nuevoEstado}`. Aparece una
   barra flotante inferior "N cambios sin guardar" con botones Guardar /
   Descartar (Descartar revierte los estilos y vacía el `Map`).
2. **Guardado en lote**: nueva ruta `POST /admin/menu/guardar-lote`,
   nuevo método `MenuController::guardarLote()`. Recibe
   `{cambios: [{id_nodo, estado}, ...]}` (JSON). Reutiliza la lógica de
   cascada que hoy vive inline en `toggle()` — se extrae a un método
   privado `aplicarCascada(int $id, int $nuevoEstado): array` (devuelve
   los IDs afectados) y se llama una vez por cada entrada del lote, dentro
   de una única transacción SQL (`BEGIN TRAN`/`COMMIT`) para que un fallo a
   mitad de lote no deje estado parcial.
3. **Refresco del sidebar sin recargar**: nueva ruta
   `GET /admin/menu/sidebar-fragmento` (o reutilizar `View::render` con
   `useLayout=false` sobre `Central/layouts/sidebar`) que devuelve solo el
   HTML de `.sidebar-mods` para el usuario actual. Tras un guardado
   exitoso, el JS de `index.php` hace `fetch()` a esa ruta y reemplaza
   `document.querySelector('.sidebar-mods').innerHTML` con la respuesta —
   sin recargar la página. Nota: esto solo refresca el sidebar del usuario
   que está guardando (el admin); otros usuarios con sesión abierta en
   otra pestaña seguirán viendo el estado viejo hasta su próxima
   navegación — igual que hoy, no se agrega polling ni WebSockets (fuera
   de alcance, no pedido).

**Riesgo aceptado:** mover de "guarda al toque" a "guarda en lote" cambia
el comportamiento existente — si el admin activa 5 interruptores y cierra
la pestaña sin guardar, pierde los 5 cambios (hoy no perdería nada porque
cada uno ya se había guardado). Se mitiga con `beforeunload` (aviso del
navegador si hay cambios sin guardar) y la barra flotante siempre visible
mientras haya pendientes.

## Alcance de esta actualización

1. Fix regresión `ACCESO_Registros` en `DashboardModel` — **ya aplicado**.
2. 5′ Hallazgo 1: grants de Bienes en `CORE_Permisos_Nodo` (SQL).
3. 5′: buscador, badge de alerta por módulo, duplicar permisos, íconos/colores compartidos.
4. 6′: staging + guardado en lote + refresco de sidebar sin recargar en Estructura del Menú.
5. 4′: paginación (bug), rediseño visual glass, check de username en vivo, generador de contraseña, autosugerencia de usuario.

Fuera de esta actualización (siguen aprobados, pendientes de ciclo futuro):
ítems 1/2/3 del documento original (KPIs Bienes, Auditoría cross-DB, Mi
Perfil), Hallazgo 2 de 5′ (permisos internos de TH), y todo lo listado
en "Fuera de alcance" del documento original.
