# Resumen técnico del sistema actual – Control de Visitas (Autoridad Portuaria de Manta)

**Fecha de actualización:** 21 de abril de 2026  
**Alcance:** Describe el estado del código **tal como está en el repositorio**, incluyendo autenticación, permisos por rol, listado con DataTables, nivel de importancia de la visita, catálogos administrables, **bitácora de rondas (seguridad operativa)** y la presentación actual del listado (texto completo en columnas; motivo con recorte por líneas y tooltips).

---

## 0. Cambios aplicados recientemente

### 0.16 Dashboard jefatura en tiempo real por polling (abril 2026)

- **Nuevo módulo:** `bit_dashboard_jefe.php` con KPIs, tendencias semanales y feed de actividad reciente.
- **Acceso restringido:** `includes/auth_permissions.php` añade `apm_can_acceder_dashboard_jefe()`; solo departamentos **TECNOLOGIA DE LA INFORMACION (ID 1)** y **GERENCIA (ID 5)**. Se incorpora `ID_DEPARTAMENTO_GERENCIA` en `includes/config_constants.php`.
- **API de actualización:** `apis/get_dashboard_live.php` devuelve JSON con `kpis`, `charts` y últimos movimientos; consumo cada 10 s desde `js/dashboard_jefe.js` (`setInterval` + `fetch`).
- **Salidas JSON limpias:** `conexion/conexion.php` y `conexion/conexion_externa.php` evitan imprimir `console.log` en rutas de API (`/apis/`, `*_api.php`) para no corromper respuestas JSON.
- **KPIs actuales:**  
  - Visitas activas: `COUNT` en `dbo.bit_visitas` con `hora_salida IS NULL`.  
  - Rondas de hoy: `COUNT` en `dbo.rondas_detalles` por fecha operativa de `dbo.bit_rondas_cabecera`.  
  - Alertas críticas 24h: suma de críticos en rondas + visitas con nivel crítico en ventana de 24 horas.
- **UX de cards:** `Visitas activas` y `Rondas de hoy` son clicables (puntero + navegación directa a `bit_listado_visitas.php` y `bit_rondas.php`).
- **Feed de movimientos:** tabla dinámica con resaltado temporal de filas nuevas, badges por tipo (`INGRESO`, `SALIDA`, `RONDA`) y fallback de usuario a `Sistema`.
- **Navegación de detalle:** nuevas páginas `bit_consulta_visitas.php` y `bit_consulta.php` para abrir detalle por URL (`action=view`) desde el dashboard; modales centrados (`modal-dialog-centered`) y retorno contextual con `from=dashboard`.
- **Referencias en auditoría:**  
  - Visitas: migración `sql/migrations/20260421_movimientos_ref_visita_en_descripcion.sql` añade sufijo `[REF:V{id_visita}]` en trigger de movimientos.  
  - Rondas: `apis/rondas_bitacora_api.php` registra `[REF:D{id_detalle}]` al crear/editar detalle.
- **Presentación en vacío:** cuando no hay movimientos, el feed muestra mensaje suave: **“Sin actividad reciente registrada hoy”**.

### 0.15 Alineación de autenticación con departamentos APM locales (abril 2026)

- **Estructura de `dbo.bit_departamentos` (local):** se estandariza a `iddepart`, `nom_departa`, `nota`, `estado` en scripts de auth y seeds.
- **Catálogo de departamentos activo:** `TECNOLOGIA DE LA INFORMACION`, `EDIFICIO ADMINISTRATIVO`, `ASESORIA JURIDICA`, `SEGURIDAD INTEGRAL`, `OPERACIONES PORTUARIAS`, `GERENCIA`, `ARCHIVO CENTRAL`, `TESORERIA`.
- **Permisos por departamento:**  
  - **Acceso total:** `TECNOLOGIA DE LA INFORMACION` y `EDIFICIO ADMINISTRATIVO`  
  - **Operativo tipo Garita:** `SEGURIDAD INTEGRAL`  
  - **Resto:** sin permisos funcionales en módulos protegidos.
- **Compatibilidad de esquema legado en registro:** `bit_register.php` detecta y completa `usuarios_apm.id_area` cuando existe en bases antiguas para evitar error 515 por `NULL`.
- **Exportes en bitácora de rondas:** se corrige carga de librerías cliente:
  - Excel: `librerias/xlsx-js-style/1.2.0/xlsx.bundle.js`
  - PDF: `librerias/jspdf/2.5.1/jspdf.umd.min.js` + `librerias/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js`
  - `js/bitacora_rondas.js` añade manejo explícito de errores y toast cuando faltan librerías.
- **Migraciones relevantes añadidas/ajustadas en auth:**  
  `20260419_departamentos_estructura_apm.sql` (renombres de departamentos),  
  `20260420_fix_usuarios_apm_id_departamento.sql` (normalización `id_departamento`, FK e índices tolerantes a estado previo),  
  ajustes idempotentes en `20260408_1900_auth_bootstrap.sql` y `20260408_1930_auth_set_passwords.sql`.

### 0.14 Configuración web de ventana de edición en bitácora (abril 2026)

- **Objetivo:** que administración/jefatura definan desde interfaz cuántos días atrás pueden editar los guardias en bitácora de rondas.
- **Persistencia en BD (estado actual):** se usa `dbo.bit_parametro` (`nombre`, `valor`) con clave `dias_edicion` (default `1`), gestionada por `includes/system_config.php` (se mantiene compatibilidad de migración desde esquema legado `configuraciones_sistema`).
- **UI (Bitácora):** en `bit_rondas.php` se agrega selector administrativo con opciones **1 / 3 / 5 / 7** y guardado vía API.
- **API:** `apis/rondas_bitacora_api.php` expone `action=set_dias_edicion_guardia` y en `action=context` devuelve `dias_edicion_guardia`, `dias_edicion_actual_usuario` y `puede_configurar_dias_edicion`.
- **Permisos:** en `includes/auth_permissions.php` se añade `apm_can_configurar_dias_bitacora()` (solo **ADMINISTRATIVO** o **JEFE**). Para guardias, `apm_bitacora_guardia_dias_permitidos()` valida contra catálogo permitido (1,3,5,7).
- **Validaciones de edición:** se aplica límite de antigüedad en frontend (`min` en fecha de edición) y backend (403 en POST si excede días permitidos).
- **Ajustes de edición histórica:** se corrige actualización por `id_detalle` + `id_usuario` (sin exigir fecha/turno actuales), permitiendo mover fecha/hora dentro del rango autorizado.

### 0.13 Exportación PDF / Excel en bitácora de rondas (abril 2026)

- **Objetivo:** resaltar filas **Medio** y **Crítico** (fondo suave y columna **Nivel** en negrita) en exportes desde previsualización y búsqueda histórica.
- **PDF (`bit_rondas.php`):** solo **jsPDF** (`librerias/jspdf/.../jspdf.umd.min.js`). La tabla de datos se **dibuja en cliente** (`rect` + `text` + `splitTextToSize`), sin depender de jsPDF-AutoTable para el cuerpo, para que los colores de fondo se vean de forma fiable. Encabezado institucional y bloque de firmas al final.
- **Excel:** descarga **`.xls`** generado como **tabla HTML** con estilos inline (`background`, negrita) y BOM UTF-8; tipo `application/vnd.ms-excel`. No usa SheetJS/xlsx en esta pantalla (los estilos en `.xlsx` generados solo en navegador suelen perderse).
- **Lógica:** `js/bitacora_rondas.js` — `resolverNivelAlertaExport()` usa `id_alerta` **2** = Medio, **3** = Crítico, con respaldo por texto del nivel; `mapFilaPreview` / `mapFilaBusqueda` pasan `id_alerta` y `nivel` desde la API.
- **Orden de scripts en página:** Bootstrap bundle → `layout_sidebar.js` → `toast.js` → **jspdf.umd.min.js** → `bitacora_rondas.js` (versionado con `?v=filemtime(...)`). Sin autotable ni xlsx en esta vista.
- **Documentación SQL:** el orden de migraciones no cambia; véase `sql/migrations/_ORDEN_EJECUCION.txt` (nota al inicio y verificación tras paso 10).

### 0.7 Bitácora de rondas / reporte diario de protección (abril 2026)

- **Migración SQL (desarrollo / PortuariaDemo):** `sql/migrations/20260410_rondas_bitacora.sql` — tablas `dbo.bit_niveles_alerta`, `dbo.bit_rondas_cabecera`, `dbo.rondas_detalles`, `dbo.bit_totales_actividades`, trigger `dbo.trg_rondas_sync_totales`, ajuste de `dbo.bit_movimientos` y FK a `dbo.bit_usuarios_apm` cuando aplique. Orden documentado en `sql/migrations/_ORDEN_EJECUCION.txt` (paso A.10; si se despliega solo con `sql_final/01`…`05` contra **Bitacora**, el mismo cuerpo va al final de `sql_final/05_datos_maestros.sql`).
- **Despliegue MAN (base Bitacora):** paquete `sql_final/01_estructura_core.sql` … `05_datos_maestros.sql`; guía `sql_final/README_EJECUCION_MAN_BITACORA.md`. El módulo de rondas queda incluido en **`05_datos_maestros.sql`** (no hace falta un sexto script suelto en ese flujo).
- **Aplicación:** `bit_rondas.php` + `js/bitacora_rondas.js` + `apis/rondas_bitacora_api.php`. Turnos operativos (Mañana / Tarde / Noche) con fecha operativa; **previsualización** por defecto solo del usuario y turno actual; los perfiles de **administración** definidos por `apm_is_admin_area()` ven en la misma tabla los registros de **todos los guardias activos** del turno, con columna **Guardia** (nombre/cédula) y `puede_editar` solo en filas propias; búsqueda histórica; sugerencias de actividad; edición de detalle vía POST con `id_detalle`; auditoría opcional en `movimientos` (evento `RONDA`). Interfaz alineada a estilo **portal institucional** (tablas, encabezados, botones primarios coherentes).
- **Permiso:** `apm_can_acceder_bitacora_rondas()` en `includes/auth_permissions.php`; bloque colapsable **Seguridad operativa** en `bit_sidebar.php` (misma flecha `bi-chevron-right` y rotación que Edificio Administrativo; menú de usuario al final usa **180°** al expandir).

### 0.8 Personas — tipo de identificación y borrado lógico (abril 2026)

- **`apis/personas_api.php` (POST):** normalización de `tidentif` quitando tildes antes de validar (`CEDULA` / `PASAPORTE` / `RUC`); valores del modal en `bit_registrar_visita.php` (`CEDULA`, etc.) alineados con el backend. Si `tidentif` viene vacío y la identificación tiene **10 dígitos**, se asume cédula.
- **Reactivación:** si existe `dbo.bit_personas` con la misma identificación y `estado = 0`, se hace **UPDATE** a `estado = 1` y se actualizan nombres/apellidos; mensaje *Registro reactivado correctamente*. Si ya está activa: *Esta persona ya se encuentra registrada y activa*. Duplicidad real por restricción SQL: *Error: La identificación ya pertenece a otro registro*.

### 0.9 Sidebar y assets (abril 2026)

- **Animación de flechas:** `css/layout.css` — contenedor `.apm-admin-group-arrow-wrap` con `transition` en `transform`; rotación **90°** al abrir grupos laterales (`#adminGroupToggle`, `#segOpGroupToggle` con `aria-expanded="true"` o clase `.is-open`); **`#userActionsToggle`** usa **180°** (menú que se despliega hacia arriba).
- **`js/layout_sidebar.js`:** estado del grupo administrativo y de Seguridad operativa en `sessionStorage`; eventos `show.bs.collapse` / `hide.bs.collapse` y respaldo por click para sincronizar la flecha.
- **`rutas/config_rutas.php`:** versionado de query string en `layout.css` y `layout_sidebar.js` para evitar caché obsoleta al cambiar estilos o comportamiento del menú.

### 0.10 Renombre de PK en catálogos de incidentes/actividades (abril 2026)

- **Migración SQL (bases existentes):** `sql/migrations/20260413_rename_pk_incidentes_actividades.sql`.
- **Cambio de esquema:** `dbo.bit_niveles_incidente.id` -> `id_incidentes`; `dbo.bit_totales_actividades.id` -> `id_actividades`.
- **Integridad referencial:** elimina/recrea `FK_visitas_niveles` para que `dbo.bit_visitas.id_nivel_incidente` apunte a `dbo.bit_niveles_incidente.id_incidentes`.
- **Orden recomendado:** si aplica `sql/migrations/niveles_incidente_add_nivel.sql` (estructura antigua), ejecutarlo primero y luego el renombre de PK.

### 0.11 Listado de visitas — edición y rol Garita / Seguridad operativa (abril 2026)

- **Comportamiento actual:** usuarios de área **Seguridad operativa** o **Garita** pueden abrir el modal **Editar visita** en el listado y guardar cambios vía `bit_actualizar_visita.php` (helper `apm_can_editar_visita_desde_listado()` en `includes/auth_permissions.php`). El refresco de la fila tras guardar (incluido limpiar `hora_salida` y mostrar de nuevo **Registrar salida**) se hace con AJAX en `js/listado_visitas.js` según el JSON devuelto.
- **Estado:** la decisión de otorgar ese permiso a Garita está **en revisión**; **se puede revertir** (volver a restringir la edición desde el listado solo a administración) ajustando la lógica en `auth_permissions.php` y las comprobaciones en `bit_listado_visitas.php` / `bit_actualizar_visita.php`, sin cambiar el resto del flujo AJAX.

### 0.12 Ajustes finales bitácora + externa APM (abril 2026)

- **Bitácora de rondas (turnos dinámicos):** `bit_rondas.php`, `js/bitacora_rondas.js` y `apis/rondas_bitacora_api.php` ahora permiten definir manualmente `hora_inicio` y `hora_fin` por turno; se guardan en `dbo.bit_rondas_cabecera` y se validan en POST al grabar/editar. En edición inline del detalle se agregó fecha de registro (`input type="date"`) además de hora, con validación de fecha no futura.
- **Fecha/hora SQL Server 2014:** en `apis/rondas_bitacora_api.php` se consolidó el armado de datetime seguro para evitar conversiones ambiguas (incluyendo casos de turno nocturno con cruce de medianoche y fecha operativa).
- **Previsualización de rondas:** al consultar fecha/turno, el listado se alinea por cabecera (`fecha` + `turno`) sin ocultar filas por filtros horarios secundarios; mejora consistencia con la búsqueda histórica.
- **Vista de administración en rondas:** la detección para ver registros de todos los guardias usa `apm_is_admin_area()` (sin depender de IDs numéricos heredados).
- **Compatibilidad externa (personas/empresas):** `conexion/conexion_externa.php`, `apis/personas_api.php` y `apis/empresas_api.php` se ajustaron para entorno dual (oficina/local), con alias de columnas en APM (`idpersona AS id_persona`, `idempresa AS id_empresa`, tabla `reg_empresas`) y normalización de `tidentif` (`1/C/CEDULA -> Cédula`, `2/R/RUC -> RUC`, `3/P/PASAPORTE -> Pasaporte`).
- **Búsqueda combinada local + externa:** en `apis/personas_api.php` la búsqueda por identificación y por texto (prefijo/nombre/apellido) consulta local y externa según corresponda, fusiona resultados y elimina duplicados por `nidentificacion`, priorizando registros locales cuando existen en ambas fuentes.
- **Inserción de personas con estado activo:** en `includes/visitas_guardar_ingreso.php` y `apis/personas_api.php` se garantiza `estado = 1` al crear personas nuevas (manuales y automáticas), evitando errores por `NULL` en SQL Server.
- **Limpieza de depuración:** se retiraron funciones y escrituras de `log_debug.txt` en `conexion_externa.php`, `apis/personas_api.php` y `apis/rondas_bitacora_api.php`; se mantienen los `console.log` visuales de estado de conexión en navegador.

---

## 0. Cambios aplicados el 9 de abril de 2026

### 0.1 Totales y sincronización automática

- Se consolidó el enfoque de totales con `sql/Registro de visitas/totales_visitas.sql` como script principal para:
  - crear/ajustar `dbo.totales_visitas`,
  - recalcular históricos por fecha,
  - sincronizar automáticamente los totales al cambiar `dbo.bit_visitas`.
- El reporte de supervisor y vistas relacionadas priorizan lectura de `dbo.totales_visitas` para evitar recalcular `COUNT(*)` completo en cada consulta.

### 0.2 Auditoría operacional (`dbo.bit_movimientos`) y turnos

- Se incorporó/ajustó la tabla `dbo.bit_movimientos` para registrar eventos de `INGRESO` y `SALIDA`.
- Se usa `dbo.fn_turno_por_hora` para clasificar turno (`Mañana`, `Tarde`, `Noche`) en auditoría.
- También se mantiene trigger para completar turno en `dbo.bit_novedades` cuando llega vacío.

### 0.3 Enlace de usuario autenticado hacia SQL Server

- Se implementó `conexion/apm_session_context.php` para propagar `id_usuario` de sesión PHP a SQL Server al abrir conexión.
- `conexion/conexion.php` ahora:
  - inicia sesión de forma centralizada antes de conectar (`includes/auth_session.php`),
  - ejecuta la propagación de usuario al contexto SQL para que los triggers puedan registrar `id_usuario` en `dbo.bit_movimientos`.

### 0.4 Compatibilidad con SQL Server 2014

- Durante validación se detectó que la instancia objetivo no soporta `CREATE OR ALTER` ni `SESSION_CONTEXT`.
- El script SQL quedó adaptado para compatibilidad:
  - reemplazo de `CREATE OR ALTER` por patrón `IF EXISTS DROP + CREATE`,
  - reemplazo de lectura/escritura por `CONTEXT_INFO` para transportar `id_usuario`.
- Resultado: creación correcta de función y triggers en la instancia actual.

### 0.5 Ajustes funcionales recientes en visitas (UI + datos)

- Se separó motivo en dos niveles: `id_motivo` (categoría) + `detalle_motivo` (texto libre opcional) en `dbo.bit_visitas`.
- Ingreso/edición manejan `detalle_motivo` con validación de existencia de columna y fallback seguro.
- En listado:
  - la columna Motivo muestra botón `+` para abrir modal de detalle,
  - se retiró el popover/hover de motivo para evitar mostrar texto incorrecto.
- Se corrigió error de DataTables por conteo de columnas en filas de error.

### 0.6 Documentación operativa actualizada

- Se actualizó `sql/Supervición/LEEME_EJECUCION.txt` y el orden maestro en `sql/migrations/_ORDEN_EJECUCION.txt` (incl. migración bitácora rondas).
- Se verificó ejecución de objetos clave en BD:
  - `dbo.totales_visitas`,
  - `dbo.bit_movimientos`,
  - `dbo.fn_turno_por_hora`,
  - `dbo.trg_visitas_sync_totales`,
  - `dbo.trg_novedades_turno`.

---

## 1. Análisis general del sistema

### 1.1 Tipo de sistema

Sistema web de **control de ingreso y salida de visitas** para el edificio administrativo de la Autoridad Portuaria de Manta. Permite:

- Registrar ingresos de visitantes (personas o proveedores/empresas).
- Consultar y listar visitas.
- Registrar salidas y corregir fecha/hora de entrada y salida.
- Gestionar catálogos (personas, empresas, funcionarios, destinos, motivos, niveles de incidente, etc.) con interfaz en `bit_catalogos.php` y búsqueda en base local y externa donde aplique.
- Registrar **bitácora de rondas** (reporte diario de protección) por turno operativo en `bit_rondas.php`, con permiso dedicado.

### 1.2 Flujo principal de uso

1. **Sesión (`bit_login.php` / `bit_logout.php`):** Las páginas protegidas exigen usuario autenticado (`includes/auth_guard.php`, sesión en `includes/auth_session.php`). Sin sesión válida se redirige a `bit_login.php`.
2. **Dashboard (`bit_index.php`):** Resumen del día (visitas hoy, activas) y últimas visitas; usa totales en `dbo.totales_visitas` con fallback a conteos sobre `visitas`.
3. **Registrar ingreso (`bit_registrar_visita.php`):** Formulario con identificación (o Guest), nombres/apellidos, empresa/personal, funcionario, destino, motivo, **nivel de importancia** y fecha/hora. Búsquedas AJAX vía APIs bajo `apis/`.
4. **Guardar visita (`bit_guardar_visita.php`):** Delega la lógica en `includes/visitas_guardar_ingreso.php` (validación, persona, inserción en `visitas` con `id_nivel_incidente`, etc.) y comprueba permiso `apm_can_registrar_ingreso()`.
5. **Listado de visitas (`bit_listado_visitas.php`):** Tabla con DataTables; acciones según permisos (salida, asignar cédula Guest, **editar visita** completa por AJAX a `bit_actualizar_visita.php`).
6. **Registrar salida (`bit_registrar_salida.php`):** Actualiza `hora_salida` y redirige al listado.
7. **Catálogos (`bit_catalogos.php`):** Mantenimiento de tablas maestras (incluye niveles de incidente) con `apis/catalogos_api.php` y DataTables en `js/catalogos.js`.

### 1.3 Tecnologías utilizadas

| Tecnología esperada | ¿Implementada? | Dónde se utiliza |
|--------------------|----------------|------------------|
| **HTML5**          | Sí             | Todas las páginas: `<!doctype html>`, `<html lang="es">`, formularios, tablas, semántica básica. |
| **CSS3**           | Sí             | `css/estilos.css`, `css/toast.css`: layout, sidebar, tarjetas, formularios, toasts, media queries. |
| **JavaScript**     | Sí             | `bit_registrar_visita.php` (inline donde aplique), `js/listado_visitas.js`, `js/catalogos.js`, `js/layout_sidebar.js`, `js/toast.js`, etc. Validación, AJAX, modales, DataTables, tooltips Bootstrap. |
| **Bootstrap 5.x**  | Sí             | Archivos locales en `librerias/` (rutas en `rutas/config_rutas.php`): grid, cards, forms, modals, badges, tablas. |
| **AJAX**           | Sí             | `fetch()` hacia `apis/*.php` (personas, empresas, destinos, funcionarios, catálogos, visitas, etc.), `bit_actualizar_cedula_guest.php`, `bit_actualizar_visita.php`. |
| **DataTables**     | Sí             | jQuery + DataTables (archivos locales vía `rutas/config_rutas.php`): **`bit_listado_visitas.php`** (`#tablaVisitas`), **`bit_catalogos.php`**, `bit_dashboard_permisos_demo.php`. |
| **PHP**            | Sí             | Backend: `conexion/conexion.php`, páginas, `includes/` (auth, permisos, guardado de visitas), `apis/`. |
| **SQL Server 2014**| Sí             | Conexión vía `sqlsrv` (PHP), base `PortuariaDemo` (y opcional `PortuariaExterna`). |

**Resumen:** El listado de visitas y los catálogos usan **DataTables** para búsqueda, orden y paginación en cliente; los datos iniciales se renderizan en PHP.

---

## 2. Lista completa de funcionalidades actuales

### Función 1: Dashboard (inicio)

- **Descripción:** Muestra total de visitas del día, visitas activas (sin hora de salida) y las últimas 5 visitas en una tabla.
- **Archivos involucrados:** `bit_index.php`, `includes/auth_guard.php`, `bit_navbar.php`, `bit_sidebar.php`, `conexion/conexion.php`, `css/estilos.css`, `rutas/config_rutas.php`.
- **Tablas utilizadas:** `dbo.bit_visitas` (COUNT y SELECT con JOIN a `personas`, `empresas`).

---

### Función 2: Registrar ingreso de visita

- **Descripción:** Formulario para registrar un nuevo ingreso: cédula (o Guest), nombre, apellido, empresa/personal, fecha y hora, funcionario, destino, motivo. Incluye validación en cliente (cédula 10 dígitos, campos obligatorios), búsqueda por cédula y por empresa vía AJAX, y modales para alta rápida de persona, empresa, destino y funcionario.
- **Archivos involucrados:** `bit_registrar_visita.php`, `bit_guardar_visita.php`, `includes/auth_guard.php`, `includes/auth_permissions.php`, `includes/visitas_guardar_ingreso.php`, `conexion/conexion.php`, `apis/personas_api.php`, `apis/empresas_api.php`, `apis/destinos_api.php`, `apis/funcionarios_api.php`, `bit_navbar.php`, `bit_sidebar.php`, `css/estilos.css`, `css/toast.css`, `js/toast.js`, `rutas/config_rutas.php`.
- **Tablas utilizadas:** `personas`, `empresas`, `funcionarios`, `destinos`, `motivos`, `niveles_incidente` (lectura para combos), `visitas` (inserción vía `includes/visitas_guardar_ingreso.php`).

---

### Función 3: Guardar visita (procesamiento del formulario de ingreso)

- **Descripción:** Recibe el POST del formulario de ingreso. Comprueba permisos; la lógica está centralizada en `includes/visitas_guardar_ingreso.php`: validación; si es Guest crea persona con identificación 9999999999; si no, busca por `nidentificacion` y crea si no existe. Inserta en `visitas` con `id_persona`, tipo visitante, empresa, funcionario, destino, motivo, **`id_nivel_incidente`** y fecha/hora. Redirige a `bit_listado_visitas.php?msg=ingreso_ok`.
- **Archivos involucrados:** `bit_guardar_visita.php`, `includes/visitas_guardar_ingreso.php`, `includes/auth_guard.php`, `includes/auth_permissions.php`, `conexion/conexion.php`.
- **Tablas utilizadas:** `personas`, `visitas`, `niveles_incidente`.

---

### Función 4: Búsqueda de persona por cédula (API)

- **Descripción:** GET con parámetro `cedula` (u otros modos de búsqueda): busca en BD local y, si no encuentra, en BD externa (`conexion_externa.php`). Devuelve JSON con datos si existe. **POST:** alta o reactivación en BD local con `nidentificacion`, `nombres`, `apellidos`, `tidentif` (valores normalizados `CEDULA` / `PASAPORTE` / `RUC`); si la persona existe con `estado = 0`, se reactiva y actualiza datos; mensajes de error/éxito explícitos para modal y duplicados.
- **Archivos involucrados:** `apis/personas_api.php`, `conexion/conexion.php`, `conexion/conexion_externa.php`. Llamado desde `bit_registrar_visita.php` vía `fetch()` (GET y POST).
- **Tablas utilizadas:** `dbo.bit_personas` (local y, para lectura, `PortuariaExterna.dbo.bit_personas` si existe).

---

### Función 5: Búsqueda de empresa por nombre o RUC (API)

- **Descripción:** GET con parámetro `q`: busca empresa por nombre o RUC en BD local; si no encuentra, en BD externa. Si la encuentra en externa, puede insertarla en local (por RUC) y devolver `id_empresa` local. POST: alta de nueva empresa (nombre, ruc) en local.
- **Archivos involucrados:** `apis/empresas_api.php`, `conexion/conexion.php`, `conexion/conexion_externa.php`. Llamado desde `bit_registrar_visita.php` (botón buscar y, con checkbox “búsqueda automática”, al escribir).
- **Tablas utilizadas:** `dbo.bit_empresas` (local y, para lectura/sincronización, `PortuariaExterna.dbo.bit_empresas` si existe).

---

### Función 6: Alta rápida de destino (API)

- **Descripción:** POST con `nombre`: inserta un nuevo registro en `destinos` y devuelve `id_destino` y nombre en JSON. Usado desde el modal “Agregar nuevo destino” en el formulario de ingreso.
- **Archivos involucrados:** `apis/destinos_api.php`, `conexion/conexion.php`. Llamado desde `bit_registrar_visita.php`.
- **Tablas utilizadas:** `dbo.bit_destinos`.

---

### Función 7: Alta rápida de funcionario (API)

- **Descripción:** POST con `nombre` y `cargo`: inserta en `funcionarios` y devuelve `id_funcionario`, nombre y cargo. Usado desde el modal “Agregar nuevo funcionario”.
- **Archivos involucrados:** `apis/funcionarios_api.php`, `conexion/conexion.php`. Llamado desde `bit_registrar_visita.php`.
- **Tablas utilizadas:** `dbo.bit_funcionarios`.

---

### Función 8: Listado de visitas

- **Descripción:** Tabla con **DataTables** (jQuery): fecha, nombre, empresa/personal, identificación (o badge Guest), funcionario, destino, motivo, **nivel de importancia** (badges según `niveles_incidente.nivel`), horas, estado y acciones. La página exige permiso `apm_can_ver_listado_admin()`. Los botones dependen de `includes/auth_permissions.php`: registrar salida, asignar cédula a Guest, **editar visita** (modal que envía por POST a `bit_actualizar_visita.php` y refresca la fila vía `js/listado_visitas.js`). Mensajes: `ingreso_ok`, `salida_ok`, `horas_ok`, `horas_error`, `permiso_denegado`.
- **Nota (Garita):** el permiso para que Seguridad operativa / Garita use el modal de edición en el listado está documentado en el **apartado 0.11**; puede revertirse si la política lo exige.
- **Presentación de texto:** nombre, empresa, funcionario y destino se muestran **completos** (escapados). La columna **motivo** usa celdas `td.apm-motivo-cell` con `span.apm-motivo-text` y **recorte visual por líneas** (line-clamp en CSS; más líneas al pasar el ratón por la fila). Los **tooltips** de Bootstrap se aplican solo al motivo, en la celda, cuando el texto queda recortado o es muy largo (no hay truncado fijo por número de caracteres en PHP).
- **Archivos involucrados:** `bit_listado_visitas.php`, `js/listado_visitas.js`, `includes/auth_guard.php`, `includes/auth_permissions.php`, `bit_actualizar_visita.php`, `conexion/conexion.php`, `bit_navbar.php`, `bit_sidebar.php`, `css/estilos.css`, `css/toast.css`, `js/toast.js`, `js/layout_sidebar.js`, `rutas/config_rutas.php`, librerías DataTables locales.
- **Tablas utilizadas:** `visitas`, `personas`, `empresas`, `funcionarios`, `destinos`, `motivos`, `niveles_incidente`.

---

### Función 9: Registrar salida

- **Descripción:** Recibe `id` de visita por GET; actualiza `hora_salida` con la hora actual del servidor solo si aún es NULL. Redirige a `bit_listado_visitas.php?msg=salida_ok`.
- **Archivos involucrados:** `bit_registrar_salida.php`, `includes/auth_permissions.php`, `conexion/conexion.php`. Enlace desde `bit_listado_visitas.php`.
- **Tablas utilizadas:** `dbo.bit_visitas`.

---

### Función 10: Editar datos y horas de una visita (listado)

- **Descripción:** Desde el listado, el modal **Editar visita** envía por POST a **`bit_actualizar_visita.php`**, que actualiza empresa/personal, funcionario, destino, motivo, fecha y horas; responde JSON y el front repinta la fila (`js/listado_visitas.js`). Existe además **`bit_actualizar_horas.php`**: endpoint POST clásico que solo actualiza fecha/hora de entrada y salida y redirige con `msg=horas_ok` / `horas_error` (útil si se enlaza desde otra vista; el listado actual no muestra un formulario separado solo para horas).
- **Archivos involucrados:** `bit_actualizar_visita.php`, `bit_actualizar_horas.php`, `includes/auth_guard.php`, `includes/auth_permissions.php`, `js/listado_visitas.js`, `conexion/conexion.php`.
- **Tablas utilizadas:** `dbo.bit_visitas` y tablas relacionadas (empresa, funcionario, destino, motivo).

---

### Función 11: Asignar cédula a visitante Guest

- **Descripción:** Permite cambiar la cédula de un visitante Guest (9999999999) a una cédula real de 10 dígitos. Solo aplica a personas con `cedula = '9999999999'`; verifica que la nueva cédula no esté usada por otra persona. Respuesta JSON; el listado recarga tras éxito.
- **Archivos involucrados:** `bit_actualizar_cedula_guest.php`, `includes/auth_guard.php`, `conexion/conexion.php`. Llamado desde `bit_listado_visitas.php` (modal y botón “Asignar cédula”).
- **Tablas utilizadas:** `dbo.bit_personas`.

---

### Función 12: Visitante sin cédula (Guest)

- **Descripción:** En el formulario de ingreso, el usuario puede marcar “Visitante sin cédula (Guest)”. Se usa cédula interna 9999999999; cada ingreso Guest crea una nueva fila en `personas` (la BD permite repetir 9999999999 si se aplicó `allow_guest_cedula.sql`). Luego se puede “Asignar cédula” desde el listado.
- **Archivos involucrados:** `bit_registrar_visita.php`, `bit_guardar_visita.php`, `bit_actualizar_cedula_guest.php`, `bit_listado_visitas.php`, `conexion.php`.
- **Tablas utilizadas:** `personas`, `visitas`.

---

### Función 13: Notificaciones toast

- **Descripción:** Toasts en esquina superior derecha para éxito, info y error. Uso: `showToast('mensaje', 'success'|'info'|'error', { key: 'opcional' })`, `closeToast('key')`.
- **Archivos involucrados:** `js/toast.js`, `css/toast.css`. Incluidos en varias vistas. El contenedor se define en `bit_navbar.php` (`#toast-container`).

---

### Función 14: Autenticación (login / registro / sesión)

- **Descripción:** `bit_login.php` valida credenciales contra usuarios en BD (tablas definidas en migraciones bajo `sql/migrations/auth/`). `bit_register.php` y `bit_bit_cambiar_password.php` completan el flujo demo. La sesión se gestiona en `includes/auth_session.php`; las páginas internas incluyen `includes/auth_guard.php`.
- **Archivos involucrados:** `bit_login.php`, `bit_logout.php`, `bit_register.php`, `bit_bit_cambiar_password.php`, `includes/auth_session.php`, `includes/auth_guard.php`, `conexion/conexion.php`.

---

### Función 15: Permisos por rol (APM)

- **Descripción:** `includes/auth_permissions.php` expone helpers (`apm_can_registrar_ingreso`, `apm_can_registrar_salida`, `apm_can_editar_visita`, `apm_can_editar_visita_desde_listado`, `apm_can_asignar_cedula_guest`, `apm_can_ver_listado_admin`, etc.) según el rol del usuario en sesión. Condiciona visibilidad de botones y redirecciones con `?msg=permiso_denegado`. La ampliación de edición en listado a Garita/Seguridad operativa está **en revisión** y **es reversible** (véase el **apartado 0.11**).
- **Archivos involucrados:** `includes/auth_permissions.php`, páginas que llaman a estos helpers.

---

### Función 16: Catálogos administrables

- **Descripción:** `bit_catalogos.php` permite listar y mantener tablas maestras (empresas, funcionarios, destinos, motivos, niveles de incidente, etc.) con pestañas y **DataTables**; el backend JSON está en `apis/catalogos_api.php` y la lógica de tabla en `js/catalogos.js`.
- **Archivos involucrados:** `bit_catalogos.php`, `apis/catalogos_api.php`, `js/catalogos.js`, `includes/auth_guard.php`, `conexion/conexion.php`, `rutas/config_rutas.php`.

---

### Función 17: Editar visita (AJAX desde el listado)

- **Descripción:** `bit_actualizar_visita.php` recibe POST con ids de empresa, funcionario, destino, motivo y fechas/horas; actualiza la fila en `visitas` y devuelve JSON con etiquetas para repintar la fila en el listado sin recargar toda la página (coordinado con `js/listado_visitas.js` y DataTables).
- **Archivos involucrados:** `bit_actualizar_visita.php`, `includes/auth_guard.php`, `includes/auth_permissions.php`, `js/listado_visitas.js`, `conexion/conexion.php`.

---

### Función 18: Bitácora de rondas (seguridad operativa)

- **Descripción:** Registro de actividades por turno y fecha operativa con ventana horaria configurable por usuario (`hora_inicio` / `hora_fin`); listado del turno consultado; búsqueda por rango de fechas y guardia; sugerencias de texto; edición inline de detalles propios (incluye fecha y hora del registro); vista ampliada para administradores (detección por `apm_is_admin_area()` y compatibilidad con `id_area = 1`). Incluye configuración web de ventana de edición para guardias (1/3/5/7 días) por admin/jefe, persistida en `dbo.bit_parametro` (`nombre = dias_edicion`) y validada en frontend/backend. Auditoría opcional en `dbo.bit_movimientos`. **Exportación PDF** (jsPDF, tabla dibujada en cliente) y **Excel** (archivo `.xls` HTML con colores por nivel de alerta); detalle en **§0.13**.
- **Archivos involucrados:** `bit_rondas.php`, `js/bitacora_rondas.js`, `apis/rondas_bitacora_api.php`, `includes/auth_guard.php`, `includes/auth_permissions.php`, `bit_navbar.php`, `bit_sidebar.php`, `rutas/config_rutas.php`, `css/layout.css`, librería local `librerias/jspdf/.../jspdf.umd.min.js`.
- **Tablas utilizadas:** `dbo.bit_niveles_alerta`, `dbo.bit_rondas_cabecera`, `dbo.rondas_detalles`, `dbo.bit_totales_actividades`, `dbo.bit_usuarios_apm`, `dbo.bit_movimientos` (donde aplique), `dbo.bit_parametro`.

---

## 3. Flujo del sistema (paso a paso)

### Flujo 1: Registrar un ingreso (visitante con cédula)

1. Usuario abre **Registrar ingreso** (`bit_registrar_visita.php`).
2. (Opcional) Escribe identificación de 10 dígitos; si tiene “Búsqueda automática” activa, al terminar o al hacer blur se llama a `apis/personas_api.php?cedula=...`.
3. Si la persona existe (local o externa), se completan nombres y apellidos automáticamente; si no, el usuario los escribe o abre el modal y guarda nueva persona vía POST a `apis/personas_api.php`.
4. Usuario selecciona o busca empresa (filtro en dropdown y/o `apis/empresas_api.php?q=...`), o deja “Visita personal”.
5. Usuario elige funcionario, destino y motivo (o los crea desde los modales).
6. Usuario envía el formulario (POST a `bit_guardar_visita.php`).
7. `bit_guardar_visita.php` invoca `visitas_guardar_ingreso_desde_post`; inserta visita con nivel de importancia; redirige a `bit_listado_visitas.php?msg=ingreso_ok`.
8. El listado muestra el mensaje de éxito y la nueva visita en la tabla.

### Flujo 2: Registrar un ingreso Guest (sin cédula)

1. Usuario abre **Registrar ingreso**.
2. Marca “Visitante sin cédula (Guest)” (la cédula no es obligatoria).
3. Ingresa nombre y apellido manualmente.
4. Completa empresa, funcionario, destino, motivo y envía.
5. `bit_guardar_visita.php` asigna cédula 9999999999, inserta una nueva fila en `personas` e inserta la visita.
6. Redirección al listado. Luego, desde el listado, se puede usar “Asignar cédula” para ese Guest.

### Flujo 3: Registrar salida

1. Usuario está en **Listado de visitas**.
2. En una visita con estado “Dentro”, hace clic en “Registrar salida”.
3. Confirma en el diálogo.
4. Navega a `bit_registrar_salida.php?id=<id_visita>`.
5. El servidor actualiza `hora_salida` con la hora actual y redirige a `bit_listado_visitas.php?msg=salida_ok`.
6. El listado muestra la visita como “Finalizada” y hora de salida.

### Flujo 4: Editar una visita desde el listado

1. En el listado, el usuario hace clic en **Editar visita** (si su rol lo permite).
2. Se abre el modal con selects y campos de fecha/hora precargados (`data-*` en el botón).
3. Al enviar, `fetch` POST a `bit_actualizar_visita.php`; la respuesta JSON incluye textos para actualizar celdas; DataTables se refresca en memoria sin recargar la página completa.
4. Los mensajes `horas_ok` / `horas_error` del resumen siguen aplicando si se usa el flujo por `bit_actualizar_horas.php` con redirección.

---

## 4. Estructura de base de datos detectada

Basado en `demo.sql`, `SQLQuery2.sql`, `migracion_personas.sql` y `sql/allow_guest_cedula.sql`:

### Tabla: dbo.bit_empresas

| Columna     | Tipo           | Restricciones |
|------------|----------------|---------------|
| id_empresa | INT IDENTITY(1,1) | PRIMARY KEY |
| empresa    | NVARCHAR(150)  | NOT NULL      |
| razonsocial| NVARCHAR(200)  | NULL          |
| ruc        | NVARCHAR(20)   | NULL          |
| estado     | BIT            | NOT NULL DEFAULT(1) |

---

### Tabla: dbo.bit_funcionarios

| Columna       | Tipo             | Restricciones |
|--------------|------------------|---------------|
| id_funcionario | INT IDENTITY(1,1) | PRIMARY KEY |
| nombre       | NVARCHAR(150)    | NOT NULL      |
| cargo        | NVARCHAR(100)    | NOT NULL      |

---

### Tabla: dbo.bit_destinos

| Columna    | Tipo             | Restricciones |
|-----------|------------------|---------------|
| id_destino | INT IDENTITY(1,1) | PRIMARY KEY |
| nombre    | NVARCHAR(150)     | NOT NULL      |

---

### Tabla: dbo.bit_motivos

| Columna   | Tipo             | Restricciones |
|----------|------------------|---------------|
| id_motivo | INT IDENTITY(1,1) | PRIMARY KEY |
| descripcion | NVARCHAR(200)   | NOT NULL      |

---

### Tabla: dbo.bit_niveles_incidente (nivel de importancia de la visita)

Catálogo usado en el ingreso y en el listado (badges). Las migraciones en `sql/migrations/` definen columnas típicas: `id`, `descripcion`, `nivel` (1–3 importancia), `estado`, etc. La visita referencia **`id_nivel_incidente`**.

---

### Tabla: dbo.bit_personas

| Columna   | Tipo             | Restricciones |
|----------|------------------|---------------|
| id_persona | INT IDENTITY(1,1) | PRIMARY KEY |
| nidentificacion | NVARCHAR(20) | NOT NULL; UNIQUE salvo Guest (índice filtrado en scripts tipo `allow_guest_cedula.sql`) |
| nombres   | NVARCHAR(100)    | NOT NULL      |
| apellidos | NVARCHAR(100)    | NOT NULL      |

**Nota:** El código y las migraciones recientes usan **`nidentificacion`**, **`nombres`** y **`apellidos`**. Esquemas antiguos podían usar `cedula`/`nombre`/`apellido`; conviene alinear con las migraciones del proyecto. Tras los scripts de Guest, se permite repetir `nidentificacion = '9999999999'`.

---

### Tabla: dbo.bit_visitas

| Columna       | Tipo    | Restricciones |
|--------------|---------|---------------|
| id_visita    | INT IDENTITY(1,1) | PRIMARY KEY |
| id_persona   | INT     | NOT NULL, FK → personas(id_persona) |
| tipo_visitante | NVARCHAR(20) | NOT NULL ('Empresa' o 'Personal') |
| id_empresa   | INT     | NULL, FK → empresas(id_empresa) |
| id_funcionario | INT   | NOT NULL, FK → funcionarios(id_funcionario) |
| id_destino   | INT     | NOT NULL, FK → destinos(id_destino) |
| id_motivo    | INT     | NOT NULL, FK → motivos(id_motivo) |
| id_nivel_incidente | INT | NULL o NOT NULL según migración; FK → niveles_incidente(id) |
| fecha_visita | DATE    | NOT NULL, DEFAULT (CONVERT(date, GETDATE())) |
| hora_entrada | TIME    | NOT NULL, DEFAULT (CONVERT(time, GETDATE())) |
| hora_salida  | TIME    | NULL |
| estado       | BIT u otro | Según migraciones (soft delete / activo) |

### Relaciones entre tablas

- **visitas** → **personas** (id_persona).
- **visitas** → **empresas** (id_empresa, opcional).
- **visitas** → **funcionarios** (id_funcionario).
- **visitas** → **destinos** (id_destino).
- **visitas** → **motivos** (id_motivo).
- **visitas** → **niveles_incidente** (id_nivel_incidente), cuando la columna existe.

Las migraciones ordenadas están documentadas en `sql/migrations/_ORDEN_EJECUCION.txt` (niveles, auth, estados, etc.).

---

## 5. Funciones de búsqueda y consultas

| Funcionalidad              | ¿Existe? | Cómo funciona |
|---------------------------|----------|----------------|
| Búsqueda de visitantes    | Sí (por cédula y otros criterios) | GET `apis/personas_api.php` (p. ej. `cedula=...`). Busca en BD local y luego en externa. POST para alta/reactivación desde el modal “Registrar nueva persona”. |
| Búsqueda de empresas      | Sí      | GET `apis/empresas_api.php?q=...`. Busca por nombre o RUC en local y externa; si solo está en externa, la inserta en local y devuelve id. |
| Consultas por cédula      | Sí      | Misma que “búsqueda de visitantes” (personas por cédula). |
| Consultas por RUC         | Sí      | Incluida en búsqueda de empresas (`q` puede ser RUC). |
| Autocompletado            | Parcial | En cédula: con checkbox “Búsqueda automática” se dispara búsqueda al escribir (debounce 400 ms) o al blur. En empresa: filtro local del dropdown + búsqueda API con botón o automática si el checkbox está activo. No hay autocompletado tipo sugerencias en un input libre. |
| AJAX para consultas       | Sí      | Búsquedas y altas rápidas vía `fetch()` a `apis/*`, más `bit_actualizar_cedula_guest.php`, `bit_actualizar_visita.php`, `apis/catalogos_api.php`, etc. |

---

## 6. Organización del proyecto

Estructura principal (no exhaustiva):

```
portuaria_demoV2/
├── bit_index.php
├── bit_login.php, bit_logout.php, bit_register.php, bit_bit_cambiar_password.php
├── bit_registrar_visita.php
├── bit_guardar_visita.php
├── bit_listado_visitas.php
├── bit_catalogos.php
├── bit_registrar_salida.php
├── bit_actualizar_visita.php
├── bit_actualizar_horas.php
├── bit_actualizar_cedula_guest.php
├── bit_navbar.php, bit_sidebar.php
├── rutas/
│   └── config_rutas.php          # Rutas a librerías locales (Bootstrap, DataTables, jQuery, iconos, JS/CSS propios; versionado anti-caché donde aplica)
├── conexion/
│   ├── conexion.php
│   └── conexion_externa.php
├── includes/
│   ├── auth_session.php, auth_guard.php, auth_permissions.php
│   └── visitas_guardar_ingreso.php
├── apis/
│   ├── personas_api.php, empresas_api.php, destinos_api.php, funcionarios_api.php
│   ├── catalogos_api.php, visitas_api.php, rondas_bitacora_api.php, …
│   └── …
├── js/
│   ├── toast.js, layout_sidebar.js, listado_visitas.js, catalogos.js, bitacora_rondas.js
│   └── …
├── css/
│   ├── estilos.css, layout.css, variables.css, componentes.css, toast.css
│   └── …
├── librerias/
│   ├── Js/                       # jquery, jquery.dataTables, dataTables.bootstrap5
│   └── Css3/                     # dataTables.bootstrap5
├── sql/
│   ├── migrations/               # _ORDEN_EJECUCION.txt, auth/, niveles, etc.
│   ├── demo.sql, allow_guest_cedula.sql, …
│   └── …
└── RESUMEN_TECNICO_SISTEMA_ACTUAL.md
```

**Archivos de conexión:**

- `conexion/conexion.php`: SQL Server, base `PortuariaDemo` (parámetros según entorno), variable global `$conn`.
- `conexion/conexion_externa.php`: conexión condicional por entorno (`$es_oficina`): en oficina apunta a `APM` remota; en local apunta a `PortuariaExterna`; en fallo deja `$connExterna = null` y mantiene `console.log` visual de estado.

**Librerías:**

- **Locales** (`librerias/` vía `rutas/config_rutas.php`): Bootstrap 5, Bootstrap Icons, jQuery, **DataTables** (núcleo + integración Bootstrap 5), Select2, etc.

---

## 7. Posibles conflictos o puntos de atención

1. **Scripts SQL duplicados:** `demo.sql` y `SQLQuery2.sql` son prácticamente iguales (creación de PortuariaDemo y datos). Mantener uno solo o dejar uno como “backup” documentado para evitar confusiones.

2. **SCOPE_IDENTITY() en APIs:** En `apis/destinos_api.php` y `apis/funcionarios_api.php` puede usarse un string con `INSERT` + `SELECT SCOPE_IDENTITY()`. Con `sqlsrv`, puede hacer falta `sqlsrv_next_result()` para el segundo result set; si no, el `id` devuelto podría ser incorrecto. Conviene verificar o usar `OUTPUT INSERTED`.

3. **Navbar colapsable comentada:** En `bit_navbar.php` el menú colapsable puede estar comentado; la navegación principal suele ser el **sidebar** (`bit_sidebar.php` + `js/layout_sidebar.js`).

4. **Sidebar compartido:** Parte del comportamiento del layout está centralizada en `js/layout_sidebar.js`; cualquier duplicación restante en vistas conviene alinearla con ese archivo.

5. **Orden de scripts:** Donde se mezclen Bootstrap, Toast y DataTables, mantener el orden documentado en `bit_listado_visitas.php` (jQuery → Bootstrap → sidebar → toast → DataTables → `listado_visitas.js`) evita errores de inicialización. En **`bit_rondas.php`:** Bootstrap → sidebar → toast → **jsPDF** → `bitacora_rondas.js` (sin DataTables en esa página; sin xlsx ni autotable).

6. **Validación Guest (9999999999):** La regla está en cliente y en `includes/visitas_guardar_ingreso.php`; debe mantenerse coherente si cambia el flujo Guest.

7. **DataTables y acciones por fila:** Tras recargar filas por AJAX hay que destruir/recrear tooltips y respetar la API de DataTables (`js/listado_visitas.js`). Cualquier cambio en columnas o clases (`apm-motivo-cell`, etc.) debe probarse con búsqueda y paginación.

8. **Errores de BD en ingreso:** Ante fallo SQL, `includes/visitas_guardar_ingreso.php` puede imprimir `print_r(sqlsrv_errors())` en HTML. En producción conviene no exponer detalles y redirigir con mensaje genérico.

---

## 8. Resultado esperado de este documento

Este resumen describe de forma técnica **el estado actual del código**:

- Tipo de sistema, **autenticación**, **permisos por rol** y flujo principal.
- Uso de HTML5, CSS3, JavaScript (incl. **DataTables** y tooltips), Bootstrap 5, AJAX, PHP y SQL Server.
- Funcionalidades con **rutas reales** (`apis/`, `includes/`, `conexion/`).
- Ingreso con **nivel de importancia**; listado con columna de nivel y **presentación de texto**: columnas largas en claro; **motivo** con recorte por líneas (CSS) y **tooltip** solo cuando corresponde (sin truncado fijo por caracteres en PHP).
- Flujos por usuario (ingreso Guest, salida, edición desde listado vía **`bit_actualizar_visita.php`**).
- Esquema de datos alineado con **migraciones** (`sql/migrations/`, incl. bitácora rondas) y notas sobre `personas` / `visitas`.
- Carpetas, conexiones, CDN y librerías locales.
- Riesgos y mejoras puntuales (sin sustituir el código).

Sirve como referencia para evolucionar el sistema sin perder de vista Guest, BD externa, APIs, permisos, listado DataTables y acciones por fila.
