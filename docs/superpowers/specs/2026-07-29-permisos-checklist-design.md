# Roles y Permisos — rediseño a tabla checklist

## Contexto

`/admin/roles/{id}/permisos` (`rol_permisos.php` + `AdminController::rolPermisos()`/
`guardarPermisos()`) hoy es un árbol de `<details>` anidados (módulo > área >
ítem > sub-ítem) con un `<select>` por nodo (Sin acceso/Ver/Crear/Editar/Total).
El usuario pidió una "mejora monumental" en diseño y método de trabajo: tabla
tipo checklist con casilleros, no un selector.

## Decisión de modelo (confirmada con el usuario)

Se mantiene el modelo de datos actual sin cambios: `CORE_Permisos_Nodo.nivel_crud`
sigue siendo un `TINYINT` único por fila (0-4), semántica acumulativa
(`nivel_crud >= N` en `fn_TienePermisoNodo`). El checklist son 4 casilleros
**en cascada**, no 4 flags independientes: tildar "Editar" tilda Ver+Crear
automáticamente; destildar "Ver" destilda todo lo de la derecha. Cero cambio
de schema, cero riesgo sobre permisos ya configurados — la conversión
select→checkboxes es puramente de presentación (el `value` que viaja al
backend en `guardarPermisos()` sigue siendo el mismo entero 0-4 por nodo, el
POST no cambia de forma).

## Diseño — Enfoque A: tabla plana agrupada por módulo

Reemplaza los `<details>` anidados por una tabla real (`<table>`), agrupada
por módulo con una fila de encabezado de sección coloreada (mismo ícono/color
que ya usa `MenuController::MODULES` / Estructura del Menú — se reutiliza esa
paleta para consistencia visual entre pantallas). Cada fila = un nodo
configurable, con:
- Indentación + chip de nivel (L1/L2/L3/L4) igual que ya tiene Estructura del
  Menú (`.lvl-chip`, `--l1..--l4`), para que ambas pantallas "hablen el mismo
  idioma" visual.
- 4 checkboxes (Ver/Crear/Editar/Total) en vez del `<select>`.

**Features incluidas** (pedido explícito de invertir en diseño/funcionalidad):
1. **Toggle por columna** — click en el header "Editar" tilda esa columna
   completa para todas las filas visibles (respeta filtro de búsqueda activo).
2. **Resaltado de cambios sin guardar** — la fila que se tocó queda marcada
   (borde/fondo distinto) hasta el submit, para ver de un vistazo qué se va a
   guardar.
3. **Buscador en vivo** — mismo patrón `oninput` que `moisSearch()` de
   Estructura del Menú (filtra filas por texto, colapsa módulos sin match).
4. Se mantiene el botón único "Guardar Permisos" (un solo `<form>` submit,
   igual que hoy) — no hace falta guardado en lote/AJAX como en Estructura
   del Menú, porque acá ya era un único submit de entrada.
5. Se mantienen las utilidades ya existentes: contador "X/Y nodos
   configurados" + barra de progreso, accesos rápidos "Sin acceso/Solo
   ver/Ver+Crear/Ver+Editar/Acceso total" en la toolbar superior (hoy aplican
   a todos los `<select>`; pasan a aplicar a todas las filas de checkboxes).

## Componentes a tocar

- `modules/Central/views/admin/rol_permisos.php` — reescritura completa del
  render de la tabla + CSS + JS (cascada de checkboxes, toggle por columna,
  resaltado, búsqueda). El PHP que arma `$tree` en el controller no cambia.
- `modules/Central/controllers/AdminController.php` — `rolPermisos()` y
  `guardarPermisos()` no necesitan cambios de lógica (el POST sigue siendo
  `permisos[clave] = nivel 0-4`); si acaso, pequeños ajustes de nombres de
  variable si la vista necesita algo adicional (ej. `$moduleColors` para las
  secciones — reusar constante compartida si ya existe, si no, replicar el
  array `MenuController::MODULES` ahí mismo).

## Fuera de alcance

- No se toca el modelo de datos (`nivel_crud` sigue 0-4).
- No se agrega "duplicar permisos de otro rol" ni badge de módulo sin
  cobertura en esta ronda (quedaron en el spec anterior 2026-07-26 sección
  5′, no se pidieron ahora — se puede retomar después si se pide).
- No se toca `fn_TienePermisoNodo` ni ningún SP.

## Testing

Sin acceso a BD real desde el navegador (extensión Chrome desconectada) —
verificación por: `php -l` en los archivos tocados, revisión manual del HTML
generado (estructura de tabla, atributos `name`/`value` idénticos a los que
ya consumía `guardarPermisos()`), y confirmación de que el payload POST final
tiene la misma forma que antes (`permisos[mod-op-it-sub] = 0..4`).
