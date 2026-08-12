# Rediseño UI/UX: Crear cuenta desde Talento Humano

**Aprobado por el usuario el 2026-08-04** (brainstorming en chat).

## Objetivo

Mejorar visualmente `/admin/usuarios/desde-th/{id}/nuevo` y auto-rellenar todo
lo que la BD de Talento Humano permite, incluyendo departamento/rol.

## Contexto técnico (verificado contra `Z.BASES DE DATOS/Talento_Humano_actualizado.sql`)

- `th_empleados` **no tiene** columnas `id_departamento`/`id_rol` — esos
  conceptos son exclusivos de `PORTAL_APM.CORE_*`. El único puente real hoy
  es `TH_Unidad_Map` (`codigo_uorg → id_departamento, id_rol_director,
  id_rol_analista`), ya existente y ya usado por `nuevoUsuarioDesdeEmpleado()`.
  No hay forma de obtener departamento/rol de PORTAL_APM más "directo" desde
  TH que esa tabla — no hay una columna equivalente que inventar.
- Sí hay más datos de contexto en `th_empleados`/`th_puestos` sin usar hoy:
  `puesto_id → th_puestos.nombre_puesto` (cargo), `telefono_movil`,
  `telefono_convencional`, `fecha_ingreso`, `tipo_contrato`, `jornada`,
  `estado`, `ruta_foto` (default `'public/img/default_avatar.png'` cuando no
  hay foto real cargada).

## Cambios

### A. Auto-relleno de Departamento/Rol — más fuerte y visible
Hoy: el `<select>` viene con `selected` en la opción sugerida por
`TH_Unidad_Map`, pero se ve como un dropdown más — fácil de no notar que ya
viene resuelto. Cambio:
- Si hay mapeo (`$sugerido`): el campo se muestra en modo "auto-detectado"
  (badge verde "Detectado desde TH", valor ya seleccionado, con opción de
  cambiarlo manualmente si hace falta — nunca bloquear la corrección manual).
- Si NO hay mapeo: se mantiene el aviso actual, pero más explícito ("esta
  unidad organizacional todavía no está mapeada en `TH_Unidad_Map` — elegí
  manualmente").
- Backend: sin cambios de query (ya trae `codigo_uorg` y consulta
  `TH_Unidad_Map`); solo cambia cómo se presenta en la vista.

### B. Tarjeta de perfil del empleado — rediseño visual
Reemplaza el banner angosto actual por una tarjeta tipo credencial:
- Foto real (`ruta_foto`) si no es la default genérica; si no, avatar con
  iniciales (como hoy, pero más grande).
- Nombre + cargo (`th_puestos.nombre_puesto`) como subtítulo.
- Grid de datos de solo lectura: cédula, unidad, teléfono (móvil o
  convencional), correo institucional, fecha de ingreso, tipo de contrato,
  jornada, badge de estado (activo/inactivo con color).
- Todo de solo lectura — la ficha de TH no se edita desde acá.

### C. Query (`AdminController::nuevoUsuarioDesdeEmpleado`)
Una sola query ampliada: agrega `LEFT JOIN th_puestos` +
`telefono_movil, telefono_convencional, fecha_ingreso, tipo_contrato,
jornada, estado, ruta_foto, p.nombre_puesto` a la que ya existe. Sin cambios
en `crearUsuarioDesdeEmpleado()` — no se guarda nada nuevo, todo lo agregado
es contexto de solo lectura.

### D. Foto del empleado — resolución de ruta
`ruta_foto` es relativa a `apps/talento_humano/` (esa app sirve las fotos).
Desde el portal nativo (`modules/Central`), la URL completa es
`APP_URL . '/apps/talento_humano/' . $rutaFoto`. Si el valor es el default
genérico (`public/img/default_avatar.png`) o está vacío, usar el avatar de
iniciales existente — no hay foto real que mostrar.

## Fuera de alcance
- No se toca `crearUsuarioDesdeEmpleado()` (creación de la cuenta) — los
  campos nuevos son informativos, no se guardan en `CORE_Usuarios`.
- No se agrega edición de ficha de TH desde el portal.
- No se toca `TH_Unidad_Map` ni su contenido — es la fuente de verdad
  existente para departamento/rol, no se reemplaza.
