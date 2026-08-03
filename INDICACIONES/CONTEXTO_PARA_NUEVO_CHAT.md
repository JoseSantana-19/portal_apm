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
- **Portuaria** — nativo enrutado dentro del portal pero con stack/BDs
  propias (Patrón C, `modules/Portuaria`). **Su identidad NO está
  unificada con `CORE_Usuarios`** — tiene su propio login/tabla de
  usuarios (`bit_usuarios_apm`), es una limitación conocida y aceptada,
  no una tarea pendiente por resolver ahora (decisión explícita del
  usuario).
- `modules/` del portal **solo tiene 3 carpetas reales**: `Central`,
  `Credenciales`, `Portuaria`. `Control_Acceso` existió hasta 2026-07-28 y
  se dio de baja por completo — si aparece en código/docs viejos, ya no
  existe.

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

## Qué se hizo en la sesión más reciente (2026-07-28 a 2026-08-02)

**Todavía sin commitear** al arrancar este archivo — correr `git status`
apenas empieces, puede que ya se haya resuelto. Resumen de lo que cambió:

1. **Bug crítico corregido:** login caía con `ACCESO_Registros no válido`
   (`DashboardModel` tenía SQL embebido no detectado en la limpieza
   anterior). Se decidió dar de baja **Control de Acceso por completo**
   (nunca tuvo funcionalidad real) — módulo, tablas `ACCESO_*`, rutas.
2. **Identidad reescrita** — cédula única, un solo usuario inicial, sin
   creación manual (ver sección arriba).
3. **Landing pública administrable** — `/admin/landing`
   (`LandingController`, nuevo), 3 tablas nuevas
   (`CORE_Landing_Imagenes/Noticias/Consejos` — Noticias con imagen
   obligatoria y Consejos en texto son **entidades separadas**, no la
   misma tabla filtrada). Vista previa real vía `<iframe src="/?preview=1">`.
   Landing pública rediseñada con layout adaptativo (nunca cajas vacías).
   Se eliminó `/api/demo-sso` — filtraba nombre/correo real de cualquier
   cédula sin auth, era una fuga de datos real, no solo código de más.
4. **SweetAlert2** reemplazó `confirm()`/`alert()` nativos y los
   `<div class="alert">` estáticos (`js/alerts.js`, `PortalAlert`).
   **Gotcha real:** el interceptor SPA global de forms en `js/main.js`
   agarra cualquier `<form>` en `.main-content` — un form con su propio
   handler AJAX (JSON) necesita `data-bypass` o el interceptor genérico
   también dispara su fetch e inyecta el JSON crudo como HTML.
5. **Estructura del Menú / Roles y Permisos** — guardado por AJAX en
   ambos, con refresco de sidebar sin recargar (`window.refreshSidebar()`
   compartida, `sidebarFragmento()`).
6. **Control de Bienes actualizado** (2026-07-31) desde el proyecto
   origen — modelo de inventario v2, y un **trigger permanente** en
   `Talento_Humano.dbo.th_empleados` que sincroniza hacia
   `inventario.dbo.inv_talento_personal` en cada alta/baja/cambio. Ya
   probado y aplicado a la BD real (con respaldo previo de los 4
   registros placeholder que colisionaban por ID con empleados reales).
7. **Documentación**: `DOCUMENTACION_SISTEMA.md` reescrito a v5.0 (varias
   secciones estaban describiendo arquitectura vieja — multi-usuario,
   Control de Acceso nativo). `GUIA_INTEGRACION_MODULOS.md` (raíz) se
   eliminó — su contenido vive ahora en
   `INDICACIONES/GUIA_MODULOS_NUEVOS_Y_ACTUALIZACIONES.md`, con una Parte 2
   nueva (cómo actualizar un módulo existente) que no existía antes.
8. Dos memorias internas del asistente se corrigieron (decían que TH y
   Bienes eran módulos nativos — no lo son, ver arriba).

## Lo más importante: hay un plan aprobado sin implementar todavía

Existe un spec de diseño **aprobado por el usuario pero sin implementar**:
`docs/superpowers/specs/2026-07-26-central-features-hardening-design.md`.
Cubre 6 frentes (con hallazgos verificados contra la BD en vivo):

1. Blindar KPIs de Bienes (magic numbers `estado_id` 111/112/113 →
   constante nombrada).
2. Auditoría del Sistema — unir `vw_AuditoriaGlobal` con los logs de TH e
   Inventario (`th_logs_auditoria`, `inv_log_eventos/errores`).
3. Mi Perfil enriquecido — `UsuarioModel::findById()` ni siquiera usa
   `vw_Usuarios_Identidad` todavía; agregar datos de TH en vivo.
4. Crear cuenta desde TH — detectar cambios de unidad/depto y bajas en
   empleados ya vinculados a una cuenta portal.
5. Roles y Permisos — **verificar si sigue vigente**: el hallazgo original
   decía que el módulo 12 (Bienes) solo tenía 3 filas de permisos
   (`CORE_Permisos_Nodo`); esta sesión se expandieron esos grants como
   parte de otro cambio — confirmar contra la BD real antes de asumir que
   sigue pendiente.
6. Estructura del Menú — solo ideas, explícitamente no implementar aún
   (aunque el guardado por lote + AJAX ya se implementó esta sesión, por
   otro pedido puntual del usuario, no por este ítem del spec).

**Si el usuario no menciona otra prioridad, este spec sigue siendo el punto
de partida más natural para retomar** — pero revisar primero cuáles de los
6 ítems ya quedaron cubiertos como efecto colateral de esta sesión.

## Dónde mirar primero según la pregunta

| Pregunta | Dónde |
|---|---|
| "¿Cómo está estructurado X?" | `DOCUMENTACION_SISTEMA.md` |
| "¿Cómo integro un módulo nuevo o actualizo uno existente?" | `INDICACIONES/GUIA_MODULOS_NUEVOS_Y_ACTUALIZACIONES.md` |
| "¿Cómo instalo esto de cero?" | `README.md` (automático) o `INDICACIONES/INSTALACION_MANUAL_WAMPSERVER.md` (manual) |
| "¿Qué falta por hacer?" | `docs/superpowers/specs/2026-07-26-central-features-hardening-design.md` (verificar vigencia primero, ver arriba) |
| Esquema real de un módulo integrado | `Z.BASES DE DATOS/*.sql` |
| "¿Por qué el sidebar/la página se ve rara después de guardar algo?" | Revisar `data-bypass` en el `<form>` — ver regla de SweetAlert2 arriba |
