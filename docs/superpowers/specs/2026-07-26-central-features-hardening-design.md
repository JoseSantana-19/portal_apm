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
