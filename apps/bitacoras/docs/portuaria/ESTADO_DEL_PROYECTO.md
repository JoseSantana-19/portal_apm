# Estado del proyecto — Portuaria DemoV4

Actualizado: 10/07/2026 (segunda pasada — limpieza de legacy completada). Este documento reemplaza/complementa a `MIGRACION_MVC.md` con el estado real verificado (no solo el plan).

---

## 0. Limpieza completada en esta pasada

Se repuntaron **19 referencias hardcodeadas** a archivos legacy en 8 archivos distintos (`core/Auth.php`, `includes/auth_guard.php`, `bit_sidebar.php`, `public/js/layout_sidebar.js`, `modules/Credenciales/controllers/AuthController.php`, `modules/Credenciales/views/login/{login,register}.php`, `modules/Bitacoras/views/visitas/registrar.php`, `bit_registrar_visita.php`) y se agregó el alias `cambiar-password` en `config/routes.php` (faltaba). De paso se encontró y corrigió un bug **preexistente** (no de esta migración): un typo `bit_bit_cambiar_password.php` en el sidebar que dejaba ese link roto en producción.

Con eso, se borraron **10 archivos** ya 100% cubiertos por MVC:
`bit_login.php`, `bit_logout.php`, `bit_register.php`, `bit_cambiar_password.php`, `bit_catalogos.php`, `bit_acc_personas.php`, `bit_acc_empresas.php`, `bit_acc_destinos.php`, `bit_acc_motivos.php`, `bit_acc_funcionarios.php`, más `apis/catalogos_api.php`.

**Verificado** ejecutando cada ruta afectada en un proceso PHP aislado (simulando request real): `catalogos`, `catalogos/personas`, `catalogos/empresas`, `catalogos/destinos`, `catalogos/motivos`, `catalogos/funcionarios`, `catalogos/niveles-incidente` — las 7 renderizan completas (HTML cierra en `</html>`, `<base>` presente, tamaño de respuesta correcto). `login` y `cambiar-password` no se pudieron verificar end-to-end en este sandbox porque consultan la base de datos real incluso en GET (dropdown de departamentos) y acá no hay SQL Server disponible — probalas vos en Laragon antes de dar por cerrado ese punto.

**Nota cosmética pendiente (no funcional):** `bit_sidebar.php` todavía compara `$paginaActual` contra nombres de archivo ya borrados (`bit_catalogos.php`, `bit_acc_personas.php`, etc.) para resaltar el ítem activo del menú. Como `$paginaActual` en rutas MVC siempre vale `index.php`, esas comparaciones nunca matchean — el menú funciona, pero no resalta el ítem activo en Catálogos. Ya pasaba antes de esta limpieza para todas las rutas MVC; no es una regresión nueva. Pendiente de una pasada futura si se quiere resolver.

---

## 0b. Paso 2 completado: vistas huérfanas de Cámaras

`CamaraController::motivos()` e `inventario()` ya tenían el método y la ruta, pero no existía `views/camaras/motivos.php` ni `views/camaras/inventario.php` — cualquiera de las dos rutas tiraba "Vista no encontrada". Se crearon ambas, extrayendo el HTML puro de `bit_motivos_camaras.php` y `bit_inv_camaras.php` (mismo patrón que ya se usó para `views/camaras/index.php`).

De paso:
- `public/js/bit_motivos_camaras.js` apuntaba a la API vieja (`apis/bit_motivos_camaras_api.php`). Se confirmó que `CamaraController::apiMotivos()` ya es 100% compatible (mismos campos de formulario, misma forma de respuesta `{ok, data, message}`) y se cambió una sola constante (`API_URL`) para apuntarlo al endpoint nuevo. Este JS es compartido entre la página legacy y la vista MVC, así que el fix beneficia a las dos.
- Se encontró y borró `public/js/bitacora_camaras.js`: un duplicado casi byte-a-byte de `bit_camaras.js` (2200 líneas, solo difería en una constante), sin ninguna referencia real en el proyecto. Código muerto, no una vista funcional.
- Se agregó el alias `camaras/inventario` en `config/routes.php` (faltaba; `camaras/motivos` sí existía).

**Verificado**: `camaras`, `camaras/motivos`, `camaras/inventario` — las 3 renderizan completas (HTML cierra, `<base>` presente, tamaño correcto) en un proceso PHP aislado por ruta.

**No se tocó (a propósito, fuera de alcance de este paso):** el sidebar todavía linkea a `bit_inv_camaras.php` y `bit_motivos_camaras.php` en vez de a las rutas MVC nuevas. Ambos caminos funcionan hoy (comparten el mismo JS/backend), así que no es urgente — es un candidato más para la limpieza de links cuando se quiera cerrar del todo este submódulo.

---

## 0c. Paso 3 completado: `bit_consulta_visitas.php`

Resultó más simple de lo previsto: `VisitaController::detalle()` **ya era** un reemplazo funcional completo (mismo chequeo de permisos `canAccederDashboardJefe()||canVerListadoAdmin()`, mismos parámetros `id`/`modal_only`, misma rama modal-vs-página-completa). Solo hacía falta repuntar quien lo llamaba.

- `public/js/dashboard_jefe.js` (compartido entre `bit_dashboard_jefe.php` legacy y `DashboardController::jefe()` MVC) armaba la URL `bit_consulta_visitas.php?id=X&action=view`. Se cambió a `bitacoras/visita/detalle?id=X`.
- Se descartó el parámetro `action=view`: en el legacy activaba un auto-apertura de modal en la página completa (fallback para sin-JS/click-derecho). La vista MVC `detalle_pagina.php` no replica ese detalle cosmético — el flujo principal (clic normal, que usa `modal_only=1` vía AJAX) no se ve afectado, es una diferencia menor solo en el fallback sin JS.
- **No se tocó** `bit_consulta.php` (detalle de Rondas) — es un archivo distinto, para un módulo que no está migrado. La misma función `buildVerUrl()` en el JS lo usa para eventos tipo "ronda" y quedó intacto.
- Borrado: `bit_consulta_visitas.php`.

**Verificado:** lint de PHP y JS sin errores; cero referencias reales restantes (solo comentarios). No pude ejecutar la ruta de punta a punta en este sandbox porque `VisitaController::detalle()` instancia `VisitaModel` (y por ende intenta conectar a SQL Server real) incluso con `id=0` — el fallo ocurre recién en `Database.php`, lo cual confirma que routing, permisos y resolución del controller funcionan bien antes de ese punto. Probalo en Laragon para el último tramo (consulta real a `bit_visitas`).

---

## 0d. Paso 4 completado: limpieza de `bit_index.php`

`bit_index.php` **no se puede borrar todavía** — 10 archivos legacy activos (Rondas, Cámaras, Listado de Visitas, etc., ninguno migrado aún) siguen apuntándole y eso es correcto, no se tocaron. Lo que sí se limpió: **11 referencias hardcodeadas desde código ya migrado a MVC** que apuntaban para atrás a un archivo legacy (inconsistente, y el mismo tipo de bug de rutas anidadas que ya se corrigió antes con `<base href>`).

Repuntadas a la ruta `dashboard`: `VisitaController` (2), `CamaraController` (3), `AuthController` (4), `DashboardController::jefe()` (1), la vista `visitas/registrar.php` (botón Cancelar), la vista `password/cambiar.php` (botón Volver), y `public/js/dashboard_jefe.js` (compartido legacy/MVC, redirect en error 403).

**Verificado:** lint sin errores en los 6 PHP + 1 JS tocados; `camaras`, `camaras/motivos`, `camaras/inventario`, `catalogos` renderizan completos de punta a punta. `visitas/registrar` no se pudo probar completo (necesita SQL Server real para las listas desplegables).

---

## 0e. Sidebar de Cámaras repuntado + limpieza final del módulo

Se detectó (con capturas del sistema real) que el sidebar seguía linkeando a los 3 `.php` de Cámaras aunque las rutas MVC ya funcionaban perfecto entrando por URL directa — por eso "desde el menú" se veía la ruta vieja y "escribiendo la ruta" la nueva. Se repuntaron los 3 links (`Maestro de Cámaras`, `Motivos CCTV`, `Bitácora de Cámaras`) a `camaras/inventario`, `camaras/motivos`, `camaras`.

Con eso, `bit_camaras.php`, `bit_inv_camaras.php` y `bit_motivos_camaras.php` quedaron sin ninguna referencia real → **borrados**. Se revisó también `apis/` y se encontraron 4 APIs viejas de cámaras ya sin ningún uso (los JS ya usan los endpoints MVC) → **borradas**: `apis/bit_camaras_api.php`, `apis/camaras_bitacora_api.php`, `apis/inv_camaras_api.php`, `apis/bit_motivos_camaras_api.php`.

**Módulo Cámaras: migración 100% completa**, sin archivos legacy restantes.

**Verificado:** `camaras`, `camaras/motivos`, `camaras/inventario` renderizan completos tras cada borrado.

---

## 0f. Rondas — Fase 1: página migrada a MVC

Módulo más grande pendiente (600 líneas de página + 856 de API + 1494 de JS). Se migró en dos capas para no arriesgar de una sola vez una lógica de negocio compleja (ventanas de edición por turno, exportación PDF/Excel, sugerencias de actividad):

**Fase 1 (hecha ahora):** la página entra a MVC.
- `modules/Bitacoras/controllers/RondaController.php::index()` — mismos permisos (`Auth::canAccederBitacoraRondas()`, `Auth::canConfigurarDiasBitacora()`) y misma lógica de ventana de edición (`apm_config_get_dias_edicion_bitacora`, `apm_bitacora_dias_edicion_permitidos`, `apm_bitacora_fecha_minima_edicion`) que tenía `bit_rondas.php`.
- `modules/Bitacoras/views/rondas/index.php` — HTML extraído tal cual.
- Ruta `rondas` agregada en `config/routes.php`.
- Sidebar repuntado, y de una vez las 6 referencias restantes a `bit_rondas.php` (`bit_consulta.php` x4, `bit_dashboard_jefe.php`, `dashboard_jefe.js`).
- `bit_rondas.php` **borrado** — quedó 100% sin uso tras repuntar todo.

**Fase 2 (pendiente, próximo paso):** `public/js/bitacora_rondas.js` sigue llamando directo a `apis/rondas_bitacora_api.php` (856 líneas: listar por turno, guardar, buscar histórico, sugerencias de actividad, configurar días de edición). Falta construir `RondaModel` + `RondaController::api()` reemplazando esa API, igual que se hizo con `CatalogoController`/`CamaraController`.

**Verificado:** lint sin errores en los 7 archivos tocados. La ruta `rondas` llega correctamente hasta el intento de conexión a SQL Server (falla ahí por no haber servidor real en este sandbox, igual que otras rutas con BD) — confirma que routing y permisos funcionan. Sin sesión, redirige antes de tocar la BD (comportamiento correcto).

---

## 0g. Rondas — Fase 2: API migrada, módulo 100% completo

Se portó `apis/rondas_bitacora_api.php` (856 líneas) a `RondaModel` (consultas SQL + funciones de cálculo puro: turno/fecha operativa, ventanas horarias, ventana de edición) y `RondaController::api()` (orquestación GET/POST, idéntica a la original). Al ser lógica de negocio sensible y sin poder probarla contra una BD real acá, se priorizó el **puerto fiel** (mismo SQL, misma validación, mismo orden de checks) sobre cualquier reescritura.

- `public/js/bitacora_rondas.js`: 7 referencias a `apis/rondas_bitacora_api.php` repuntadas a `bitacoras/ronda/api`.
- Ruta `bitacoras/ronda/api` agregada en `config/routes.php`.
- `apis/rondas_bitacora_api.php` **borrado** — sin referencias reales restantes.

**Verificado:**
- Lint sin errores en los 5 archivos tocados.
- **17 casos de prueba unitarios** sobre las funciones de cálculo puro (las más propensas a error al portear a mano): cálculo de turno/fecha operativa en los 4 turnos y sus límites exactos, ventana horaria del turno Noche cruzando medianoche, cálculo de fecha calendario para el turno que cruza medianoche, parseo de horas, bandera de cambio de día. Los 17 casos coinciden con la regla de negocio documentada.
- Rutas `rondas` y `bitacoras/ronda/api` llegan correctamente hasta el intento de conexión a SQL Server (mismo límite del sandbox que el resto de rutas con BD).

**Módulo Rondas: migración 100% completa.**

---

## 0h. Pedido grande del Ing. Zambrano (compat PHP, portabilidad, login simple, rutas, rename bit_)

### Compatibilidad PHP 7.2 → 8.5
Auditoría completa del proyecto (grep de sintaxis exclusiva 7.4+/8.0+/8.1+, sin PHP 7.2 real disponible para instalar en este sandbox). Encontrado y corregido:
- `UsuarioModel.php`: 2 tipos de retorno union (`array|false`, PHP 8.0+) → PHPDoc. 4x `str_starts_with()` (PHP 8.0+) → `substr(...) === ...`.
- `core/Controller.php`, `core/Router.php`, `core/Database.php`: 3 propiedades tipadas (PHP 7.4+) → PHPDoc + sin tipo.
- Lint completo del proyecto: 0 errores. **No pude instalar PHP 7.2 real en este sandbox** (no está en los repos disponibles) — la verificación fue por inspección exhaustiva, no ejecución real. Recomendado probar en un entorno con PHP 7.2 real antes de certificar.

### Portabilidad (funcionar en cualquier carpeta/nombre de proyecto)
Ya estaba resuelto arquitectónicamente (`Router.php` calcula el basePath desde `DOCUMENT_ROOT`, `base_url()` desde `SCRIPT_NAME`, sin `RewriteBase` en `.htaccess`). Verificado: cero referencias hardcodeadas a "portuaria_demoV4" en lógica real (solo comentarios de ejemplo).

### Conexión APM (nivel 2) y espacio para SRI/Registro Civil (nivel 3) y Talento Humano
- Ya existía `conexion/conexion_externa.php` (conexión APM) y el nivel 2 para Personas en `CatalogoModel`. Se agregó el nivel 2 para **Empresas** (`obtenerEmpresaExternaRuc`, `listarEmpresaExternaNombreORuc` — ojo, la tabla externa es `dbo.reg_empresas`/`idempresa`, distinta de la local `dbo.bit_empresas`/`id_empresa`).
- Se agregaron `buscarPersonaCascada()` y `buscarEmpresaCascada()` con la cascada documentada: 1) local → 2) APM → 3) comentario con el punto exacto de enganche para SRI/Registro Civil (sin URL todavía, no se inventó ninguna).
- Se creó `conexion/conexion_talento_humano.php`: placeholder documentado, no conecta a nada todavía (esa BD no existe aún).

### Login simplificado (solo cédula + contraseña)
`AuthController::login()` y `UsuarioModel::buscarParaLogin()` ya no piden departamento. Nota: cédula+departamento era la clave única real (una cédula podía tener cuentas en más de un departamento); por ahora se toma la primera cuenta activa encontrada. El registro de cuenta nueva (`register()`) no se tocó, sigue pidiendo departamento.

### Simulación BASE_CORE
`UsuarioModel::validarUsuarioCore()` nuevo: devuelve siempre válido (simulado), con el query real comentado (`SELECT * FROM dbo.Usuario_core WHERE Cedula = ?`) para cuando exista la conexión. Enganchado en `AuthController::login()` de forma **aditiva** — no cambia ni bloquea el login real de hoy, solo dejó el origen (`core_origen`) guardado en sesión para auditoría futura. Verificado con test aislado.

### Bug de Rondas: `Invalid object name 'dbo.rondas_cabecera'`
**No está en el código PHP** (verificado: las 21 referencias en `RondaModel.php` usan `dbo.bit_rondas_cabecera` correctamente). Se encontró y corrigió un bug real en el script de migración (`sql/03_DATABASE_LOGICA_TRIGGERS.sql`): la guarda `IF OBJECT_ID(N'dbo.rondas_detalles'...)` (sin `bit_`) en vez de `dbo.bit_rondas_detalles`. Esto no explica el error exacto reportado (que menciona "cabecera", no "detalles") — lo más probable es que **la base de datos real ya tiene una tabla creada con un nombre distinto al del script actual** (de una versión anterior del script). Recomendado: correr `SELECT name FROM sys.tables WHERE name LIKE '%rondas%'` en SSMS contra la BD real y confirmar los nombres exactos antes de seguir.

### Rutas del sidebar → MVC (Registrar ingreso, Listado de visitas)
Repuntados a `visitas/registrar` y `visitas` en: `bit_sidebar.php`, `modules/Central/views/dashboard/index.php`, `modules/Bitacoras/views/visitas/{listado,detalle_pagina,detalle_modal}.php`, `modules/Bitacoras/controllers/{Visita,Catalogo}Controller.php`, `public/js/dashboard_jefe.js`, y los legacy que se quedan vivos (`bit_index.php`, `bit_dashboard_jefe.php`, `bit_registrar_salida.php`).

Con eso quedaron huérfanos y se borraron: `bit_registrar_visita.php`, `bit_listado_visitas.php`, `bit_guardar_visita.php`, `includes/bit_visitas_guardar_ingreso.php` *(el nombre real antes de renombrar era `visitas_guardar_ingreso.php`)*, `includes/maestro_catalogo_base.php`, `apis/visitas_api.php`.

### Renombrado a prefijo `bit_` en `apis/` e `includes/`
**19 archivos** renombrados (10 en `apis/`, 9 en `includes/`) y cada referencia actualizada en todo el proyecto (PHP + JS). Verificado: 0 errores de lint en todo el proyecto, 0 referencias a nombres viejos, 0 duplicados `bit_bit_`.

**No se tocó a propósito:** `index.php` (nombre requerido por el front controller / `.htaccess`) y los archivos de `modules/**/*.php` (Controllers/Models/Views siguen la convención PascalCase que el `Router` necesita para el autoload dinámico — renombrarlos ahí rompería todo el framework). Si el pedido incluía también esos, avisar para tratarlo aparte con mucho más cuidado.

**Verificado:** lint completo del proyecto (0 errores), regresión de rutas (`catalogos`, `camaras`, `camaras/motivos`, `camaras/inventario`, `login` — todas OK; `rondas`/`visitas` llegan hasta el límite esperado de SQL Server real).

---

## 0i. Confirmado: tablas de Rondas OK, y prefijo `bit_` extendido a `modules/`

**Rondas:** el Ing. Zambrano confirmó por SSMS que las tablas reales son `bit_rondas_cabecera` y `bit_rondas_detalles` — coinciden exactamente con lo que usa `RondaModel.php`. El error que vio fue de una corrida anterior a la Fase 2; con el zip actual no debería repetirse.

**Prefijo `bit_` en `modules/`:** confirmado que sí se quería, con la condición explícita de no romper nada. El Router y `Controller::model()`/`Controller::view()` ubican archivos por **coincidencia exacta de nombre**, así que renombrar sin ajustar el framework rompía el sistema entero. Se hizo así:

- **27 archivos** renombrados (6 Controllers, 6 Models, 15 Views) con prefijo `bit_` en el nombre de archivo — las carpetas (`catalogos/`, `camaras/`, etc.) NO se tocaron, solo el archivo.
- Los nombres de **clase** adentro de Controllers/Models NO cambiaron (`CatalogoController` sigue siendo `CatalogoController`) — evita tener que tocar cada `$this->model('X')`, cada `instanceof`, cada docblock `@var`, etc. en todo el proyecto.
- Se ajustaron los **3 únicos puntos** del framework que localizan estos archivos: `Router.php` (busca `bit_{Controller}.php`), `Controller::model()` (busca `bit_{Modelo}.php`), `Controller::view()` (inyecta `bit_` automáticamente en el nombre de archivo de la vista, sin tocar ningún `$this->view('carpeta/nombre')` de ningún controller).
- Se verificó que ningún otro archivo del proyecto carga controllers/models/views por fuera de esos 3 puntos (grep exhaustivo, 0 resultados).

**Verificado:** lint completo del proyecto (0 errores) y **11 rutas probadas de punta a punta**: `catalogos`, `catalogos/{personas,empresas,destinos,motivos,funcionarios,niveles-incidente}`, `camaras`, `camaras/motivos`, `camaras/inventario`, `login` — las 11 renderizan completas. `rondas`, `visitas`, `dashboard`, `cambiar-password` llegan hasta el límite esperado (SQL Server real, no disponible en este sandbox).

---

## 0j. Bug crítico propio corregido: `includes/visitas_guardar_ingreso.php` faltante

Al borrar este archivo unas pasadas atrás, mi barrida de referencias solo revisó `VisitaController.php` y `bit_guardar_visita.php` — **no revisé `VisitaModel.php`**, que lo requería directo en el constructor. Rompía `visitas/registrar` y `visitas` con un Fatal error. El usuario todavía tenía el archivo original guardado y lo volvió a subir.

Restaurado como `includes/bit_visitas_guardar_ingreso.php` (prefijo `bit_`, dependencias internas actualizadas a `bit_apm_fecha_iso.php`/`bit_validaciones_ecuador.php`) y `bit_VisitaModel.php` repuntado.

**De paso se encontró y corrigió un bug más grande**, ya sistemático: `Controller::redirect()` armaba `header('Location: ...')` con rutas **relativas**. A diferencia de los links HTML (que sí resuelven bien gracias al `<base href>`), las cabeceras HTTP `Location` NO se ven afectadas por `<base href>` — se resuelven contra la URL de la request real. Como varias acciones (`bitacoras/visita/guardar`, etc.) corren desde URLs de 3 segmentos, esos redirects relativos resolvían mal. Se corrigió en el único punto central (`Controller::redirect()` ahora arma la ruta absoluta con `base_url()` si no es ya absoluta), lo que corrige esto en **todos** los controllers de una sola vez, no solo en Visitas.

**Auditoría completa repetida:** se revisaron los 28 archivos borrados en toda la conversación contra el proyecto entero (no solo controllers) — el único archivo con este problema era el de arriba, ya corregido. El resto de referencias restantes son las comparaciones cosméticas ya conocidas del sidebar.

**Verificado:** lint limpio, `visitas/registrar` y `visitas` ahora llegan hasta el límite esperado de SQL Server real (antes fallaban con "require_once failed").

## 0k. Sidebar: scroll roto en pantallas angostas

`overflow-y`/altura máxima del sidebar solo estaban definidos dentro de `@media (min-width: 992px)` — en pantallas angostas (celular, ventana chica) un submenú desplegado no tenía límite de altura ni scroll propio, quedando inaccesible aunque se hiciera scroll de la página. Agregado `overflow-y: auto` + `max-height` incondicional (fuera del media query) sobre `#sidebarMenu .apm-sidebar-nav.offcanvas-body`, en `public/css/layout.css`.

---

## 0l. Dashboard Ejecutivo (Python/Streamlit): completo y en verde

Se verificó/completó de punta a punta lo pedido para el dashboard, según la lista del ingeniero:

**KPIs implementados** (`analytics/dashboard.py`): total de visitas, desglose por género, incidentes nivel Crítico, personas que más ingresan, destinos que más reciben visitas, demanda mensual, funcionario que más solicita/recibe visitas, registro global con ficha individual por visita.

**Pendiente y señalado explícitamente en el propio dashboard** (con un `st.warning`, no oculto): "Fechas de ingreso de autoridades" — el sistema no tiene ningún campo que distinga una autoridad de un visitante común. Falta que el ingeniero defina el criterio (¿motivo específico? ¿campo nuevo?) antes de poder armarlo sin inventar la regla.

**Campo género agregado** (era condición para el KPI de género):
- `sql/24_PERSONAS_GENERO.sql`: agrega `genero CHAR(1) NULL` a `bit_personas`, idempotente, no rompe registros existentes (quedan en `NULL` = "Sin dato").
- Select `<select name="genero">` (opcional) agregado al formulario de registro de ingreso.
- `includes/bit_visitas_guardar_ingreso.php` lo captura y guarda.

**Integración con MVC:**
- `DashboardController::ejecutivo()` (ruta `dashboard-ejecutivo`) sirve una vista que embebe el dashboard vía `<iframe>`.
- La URL del iframe es una sola constante (`APM_DASHBOARD_EJECUTIVO_URL` en `config/globals.php`) — si cambia el puerto/host del servicio Python, se edita en un solo lugar.
- Sidebar ya enlaza a "Dashboard Ejecutivo" → `dashboard-ejecutivo`.
- Reemplaza a `bit_dashboard_analitica.php` (ya borrado).

**Deploy como servicio** (`analytics/README.md`, `requirements.txt`, `.env.example`, `start_dashboard.bat`):
- Conexión a base de datos por variables de entorno (ya no hardcodeada a una máquina de desarrollo, que era el problema original señalado en el reporte al ingeniero).
- Instrucciones para dejarlo como servicio real de Windows con NSSM, o alternativa más simple con el Programador de tareas.
- Botón "Abrir expediente completo" dentro del dashboard ya apunta a la ruta MVC real (`bitacoras/visita/detalle`), no a un archivo legacy borrado.

**Verificado:** sintaxis Python válida (`py_compile`), lint PHP sin errores en los archivos tocados, y la ruta `dashboard-ejecutivo` renderiza completa de punta a punta en este sandbox (no depende de la base de datos del lado PHP — solo arma el iframe). El servicio Python en sí **no se pudo correr ni probar acá** (no hay Python/pyodbc/SQL Server en este sandbox) — probarlo en Laragon siguiendo el README.

---

## 1. Respuesta corta a tus dos preguntas (histórico — ver sección 0 para lo ya resuelto)

**¿Puedo borrar los archivos sueltos ya migrados a MVC?**
Los 10 de Credenciales y Catálogos ya se borraron (sección 0). El resto de "Catálogos" y todo "Credenciales" están migrados en el backend, pero tienen **referencias hardcodeadas** (sidebar, `Auth::loginUrl()`) que hay que repuntar primero o se rompe la navegación. Todo lo demás (Cámaras/Motivos, Cámaras/Inventario, Rondas, varias APIs de Visitas) **todavía no tiene reemplazo funcional completo** — borrar esos archivos hoy rompe el sistema. Detalle completo en la sección 3.

**¿Hay un `.md` con todo lo hecho y lo que falta?**
Este mismo archivo.

---

## 0o. `bit_index.php` retirado + "Importar funcionarios" migrado

**`bit_index.php`:** `DashboardController::index()` ya era un puerto fiel completo (confirmado comparando ambos, misma lógica de fallback totales_visitas → conteo directo). Se repuntaron las **4 referencias reales** restantes (`bit_consulta.php`, `bit_dashboard_jefe.php`, `views/layouts/bit_sidebar.php`, `bit_importar_funcionarios.php`) a la ruta `dashboard`, y se borró el archivo.

**Importar funcionarios:** confirmado que es un feature autocontenido (subida de CSV exportado de FoxPro → `bit_funcionarios`) sin relación con las utilidades DBF/FoxPro que siguen fuera de alcance (`bit_diagnostico_dbf.php`, `bit_test_rolmaes_dbf.php`, etc. — esas siguen igual, el usuario no pidió tocarlas). Migrado dentro de `CatalogoController::importarFuncionarios()` (mismo módulo que ya gestiona el catálogo de funcionarios) + 3 métodos nuevos en `CatalogoModel` + vista `catalogos/bit_importarFuncionarios.php`, puerto fiel línea por línea de la lógica de parseo CSV original. Ruta `importar-funcionarios` agregada, sidebar repuntado, y un link real que había quedado en `visitas/bit_registrar.php` ("Importar funcionarios" como contingencia cuando falla `dbase`) también corregido.

Borrado: `bit_index.php`, `bit_importar_funcionarios.php`.

**Verificado:** lint completo del proyecto (0 errores). Auditoría completa de **los 20 archivos borrados en toda la conversación** contra el proyecto entero (no solo controllers — la lección del bug de `visitas_guardar_ingreso.php`): cero referencias reales restantes, solo comparaciones cosméticas ya conocidas del sidebar. Rutas `catalogos`, `catalogos/funcionarios`, `camaras` renderizan completas; `dashboard`, `importar-funcionarios`, `visitas/registrar` llegan hasta el límite esperado de SQL Server real (no disponible en este sandbox).

---


Movidos de la raíz del proyecto a `views/layouts/bit_navbar.php` y `views/layouts/bit_sidebar.php` — junto a `main.php`, que es donde vive el resto de la infraestructura de layout del MVC. Antes eran archivos sueltos en la raíz.

- Arreglados los `require` internos de ambos archivos: usaban `__DIR__` para cargar `includes/bit_auth_session.php`, `includes/bit_auth_permissions.php` y `config/header.php` — al cambiar de carpeta, `__DIR__` ya no apuntaba a la raíz. Se reemplazó por `ROOT_PATH` cuando está definida (contexto MVC) con respaldo a `dirname(__DIR__, 2)` (contexto de página legacy suelta, donde `ROOT_PATH` no siempre está definida).
- Actualizadas las **6 referencias** que los incluían: `views/layouts/main.php` (MVC) y las 5 páginas legacy que todavía quedan con layout completo (`bit_index.php`, `bit_dashboard_jefe.php`, `bit_consulta.php`, `bit_reporte_diario_supervisor.php`, `bit_importar_funcionarios.php`).
- Como se **movieron** los archivos (no se copiaron), no queda ningún duplicado suelto que borrar de esta migración puntual — confirmado que `bit_navbar.php`/`bit_sidebar.php` ya no existen en la raíz.

**Verificado:** lint completo del proyecto (0 errores), rutas MVC (`catalogos`, `camaras`, `camaras/motivos`) renderizan completas con el navbar/sidebar nuevos. Se probó también el navbar/sidebar de forma aislada simulando el contexto de una página legacy plana **sin** `ROOT_PATH` definida — el mecanismo de respaldo (`dirname(__DIR__, 2)`) funcionó correctamente (16 711 bytes, logo/título presente, nav del sidebar presente, link a Catálogos presente).

---

## 0m. Auditoría de re-verificación (zip re-subido) + limpieza

El usuario volvió a subir el proyecto y pidió: (1) lista exacta de qué falta para la migración completa, excluyendo Dashboard Python y conexiones a otras BD (los va a hacer él después), (2) corregir errores, (3) borrar duplicados/código muerto.

**Verificado que todo el trabajo de sesiones anteriores sigue intacto en este zip:** bug crítico de `View.php` (parámetro renombrado), `Controller::redirect()` con rutas absolutas, compatibilidad PHP 7.2 (`UsuarioModel`, propiedades sin tipar), login simplificado a cédula+contraseña, prefijo `bit_` en `modules/`, fix del typo SQL de Rondas, `sql/24_PERSONAS_GENERO.sql`. Lint completo del proyecto: 0 errores. 11 rutas probadas de punta a punta (todos los catálogos, cámaras, dashboard-ejecutivo): todas renderizan completas.

**Limpieza esta pasada:**
- `descargas/` (588 KB, duplicado muerto de `librerias/`, ya señalado en sesiones previas pero nunca borrado por no tener autorización explícita) — **borrado**, junto con su referencia ya inerte en `core/Router.php`.

**Encontrado, no tocado (pendiente de confirmación):**
- `frontend-demo/` — un proyecto React/Vite completo y separado, sin relación aparente con el sistema PHP en producción. Podría ser un prototipo de diseño a conservar o código muerto — no se borró sin preguntar.

**Sigue exactamente como antes (a propósito, diferido por el usuario):**
- `analytics/` (Dashboard Streamlit) — el usuario dijo explícitamente que lo hará él al final.
- Conexiones externas a Talento Humano / SRI / Registro Civil — placeholders documentados, sin URLs inventadas, a la espera de que esas bases existan.

Ver la lista completa de "qué falta" en el mensaje de chat de esta misma sesión (no duplicada acá para no desactualizarse en dos lugares).

---

## 2. Qué se hizo en esta sesión (verificado, no solo revisado)

### Módulo Catálogos (Bitácoras/CatalogoController)
- Agregada acción `nivelesIncidente()` + ruta `catalogos/niveles-incidente` (faltaba por completo).
- Corregido slug roto en `bit_acc_motivos.php` (`'bit_motivos'` → `'motivos'`).
- Agregados al sidebar los links de "Talento Humano" y "Niveles de importancia" (no estaban).
- Verificado contra el `.sql` real: las columnas FK que usa `tieneVisitasAsociadas()` coinciden con `bit_visitas`.

### Reestructuración a `public/`
- Movidos `css/`, `js/`, `img/`, `librerias/` → `public/css`, `public/js`, `public/img`, `public/librerias`.
- Creadas `public/uploads/`, `libs/`, `logs/`, `storage/{cache,sessions}/` (vacías, para uso futuro).
- Actualizadas **21 archivos** con las nuevas rutas (verificado con grep exhaustivo: 0 referencias rotas).
- `.htaccess` actualizado para bloquear acceso directo a `libs/`, `logs/`, `storage/`.
- `core/Router.php` simplificado (ya no necesita conocer `css/`, `js/`, `img/`, `librerias/` por separado).

### Bug crítico de layout (el de los estilos/pantalla en blanco)
- **Causa raíz:** al crear `core/View.php`, su parámetro `$file` colisionaba con la clave `'file'` que `Controller::view()` le pasa dentro de `$data` para que el layout sepa qué vista incluir. `extract($data)` pisaba el parámetro y el layout completo (`<head>`, navbar, sidebar) se saltaba — por eso no cargaban estilos y por eso `apm_can_asignar_cedula_guest()` aparecía "indefinida" (esa función vive en `includes/auth_permissions.php`, que solo se carga porque `bit_sidebar.php` lo requiere).
- **Corregido y verificado** ejecutando el stack real (Router → Controller → View → layout → navbar → sidebar) con PHP 8.3 en un entorno de pruebas.
- Efecto secundario corregido de paso: `rutas/config_rutas.php` ahora se carga en scope global real desde `index.php`, así `$GLOBALS['url_*']` funciona en cualquier controller (antes `CatalogoController::index()` y `VisitaController::listado()` leían `$GLOBALS['url_datatables_css']` vacío).

### Confirmado ya existente (no lo tocamos, ya cumple lo que pidió el ingeniero)
- **Header configurable logo/imagen**: `config/header.php` ya tiene el switch `'modo' => 'logo' | 'imagen'`, documentado con comentarios, un solo archivo para editar. ✅ Ya cumple el pedido.
- **Header fijo con contenido por debajo**: `bit_navbar.php` usa `sticky-top` + `z-index: 1080` (`public/css/layout.css` línea 19-21), con comentario explícito `"Header fijo: el contenido pasa por debajo"`. ✅ Ya cumple el pedido.

---

## 3. Archivos legacy: qué queda y por qué (actualizado)

Después de todas las pasadas, **ya no quedan páginas legacy de los módulos migrados** (Credenciales, Catálogos, Visitas, Cámaras, Rondas: cero archivos sueltos). Lo que queda en la raíz, `apis/` e `includes/` cae en 3 categorías:

### 🟢 Núcleo compartido (NO son candidatos a borrar — los usa también el MVC)
`bit_navbar.php`, `bit_sidebar.php` (incluidos directo por `views/layouts/main.php`), y los helpers en `includes/`: `bit_auth_guard.php`, `bit_auth_permissions.php`, `bit_auth_session.php`, `bit_system_config.php`, `bit_config_constants.php`, `bit_apm_fecha_iso.php`, `bit_validaciones_ecuador.php`, `bit_dev_auto_login.php`, `bit_dashboard_jefe_data.php`, `bit_visitas_guardar_ingreso.php` (usado por `VisitaModel`). Estos no son "legacy a reemplazar", son la base compartida del sistema completo.

### 🟡 Páginas/APIs sin reemplazo MVC todavía (quedan vivas a propósito)
| Archivo(s) | Motivo concreto |
|---|---|
| `apis/bit_personas_api.php`, `bit_empresas_api.php`, `bit_destinos_api.php`, `bit_motivos_api.php`, `bit_funcionarios_api.php` | Llamados **directo** por `public/js/registrar_visita.js` (quick-add/búsqueda en "Registrar ingreso"). `VisitaController` no absorbió esta lógica todavía. |
| `apis/bit_reporte_supervisor_api.php` + `bit_reporte_diario_supervisor.php` | Sin controller MVC propio. |
| `apis/bit_get_dashboard_live.php` + `includes/bit_dashboard_jefe_data.php` | Usados por `public/js/dashboard_jefe.js`; `DashboardController` no tiene `apiLive()` todavía. |
| `bit_index.php`, `bit_dashboard_jefe.php` | Tienen equivalente MVC (`DashboardController::index()/jefe()`), pero siguen siendo el destino de varios redirects internos y del propio JS compartido. Retirarlos es una pasada aparte (ver sección 0d). |
| `bit_consulta.php` | Detalle de Rondas (evento tipo "ronda" en `dashboard_jefe.js`) — `RondaController` no tiene acción de detalle individual. |

### ⚪ Fuera de alcance de la migración (utilidades/demo, nunca estuvieron en el plan)
`bit_diagnostico_dbf.php`, `bit_importar_funcionarios.php`, `bit_test_conexiones.php`, `bit_test_empresa_externa.php`, `bit_test_rolmaes_dbf.php`, `bit_dashboard_permisos_demo.php`, `apis/bit_lector_rolmaes_dbf.php`, `apis/bit_sincronizar_funcionarios_dbf.php`, `apis/bit_api_funcionarios_foxpro.php` — utilidades de sincronización DBF/FoxPro y páginas de prueba, no forman parte de ningún módulo a migrar.

### ✅ Todo lo ya borrado en esta sesión (histórico completo)
`bit_login.php`, `bit_logout.php`, `bit_register.php`, `bit_cambiar_password.php`, `bit_catalogos.php`, `bit_acc_personas.php`, `bit_acc_empresas.php`, `bit_acc_destinos.php`, `bit_acc_motivos.php`, `bit_acc_funcionarios.php`, `bit_camaras.php`, `bit_inv_camaras.php`, `bit_motivos_camaras.php`, `bit_consulta_visitas.php`, `bit_rondas.php`, `bit_registrar_visita.php`, `bit_listado_visitas.php`, `bit_guardar_visita.php`, `bit_dashboard_analitica.php` (reemplazada por `dashboard-ejecutivo`), `apis/catalogos_api.php`, `apis/bit_camaras_api.php`, `apis/camaras_bitacora_api.php`, `apis/inv_camaras_api.php`, `apis/bit_motivos_camaras_api.php`, `apis/rondas_bitacora_api.php`, `apis/visitas_api.php`, `includes/maestro_catalogo_base.php`, `public/js/bitacora_camaras.js` (duplicado muerto).

Más **19 archivos** de `apis/` e `includes/` y **27 archivos** de `modules/` renombrados (no borrados) con prefijo `bit_` — ver secciones 0h/0i.

**Regla general al borrar cualquier archivo:** `grep -rn "nombre_del_archivo.php"` sobre **todo** el proyecto (PHP + JS), incluyendo `modules/**/*.php` — no solo controllers (la sección 0j documenta el único caso donde esto falló).

---

## 4. Estado por módulo

| Módulo | Estado | Detalle |
|---|---|---|
| **Credenciales** | 🟡 Backend migrado, navegación sin repuntar | Ver sección 3. |
| **Central (Dashboard)** | 🟡 Parcial | `index()`, `jefe()` y `ejecutivo()` (Dashboard Ejecutivo Python, ver sección 0l) migrados. `bit_dashboard_permisos_demo.php` nunca estuvo en el plan — decidir si entra. `apis/get_dashboard_live.php` pendiente de convertirse en `apiLive()`. |
| **Bitácoras / Visitas** | 🟢 Migrado completo | `registrar_visita.js` sigue dependiendo de las 5 APIs viejas de catálogos (no de Visitas, ver tabla de la sección 3). |
| **Bitácoras / Cámaras** | 🟢 Migrado completo | Log principal, Motivos e Inventario funcionando en MVC. Sidebar repuntado. Sin archivos legacy restantes. |
| **Bitácoras / Rondas** | 🟢 Migrado completo | `RondaController` (index + api) + `RondaModel`, sin archivos legacy restantes. Lógica de negocio (turnos, ventanas de edición) verificada con 17 casos de prueba unitarios. |
| **Bitácoras / Catálogos** | 🟢 Migrado completo | Personas, Empresas, Destinos, Motivos, Funcionarios, Niveles de importancia — los 6 funcionan vía MVC. |
| **Talento_Humano** | 🔴 No iniciado | Carpetas vacías. Hoy "Talento Humano" vive como catálogo `funcionarios` dentro de Bitácoras/Catálogos. |
| **Control_Bines** | 🔴 No iniciado | Carpetas vacías, sin tablas SQL asociadas encontradas. |
| **Credenciales — Roles/Permisos** | 🔴 No iniciado | Hoy los permisos son hardcodeados en `core/Auth.php` (métodos `is*`/`can*`), no hay tablas ni UI de roles. |

---

## 5. Pedidos del Ing. Fernando Zambrano — estado

| Pedido | Estado |
|---|---|
| No usar Laravel | ✅ Cumplido — framework MVC propio (`core/`), sin Composer/vendor. |
| Header con logo/imagen configurable desde un solo archivo | ✅ Ya existe (`config/header.php`). |
| Header fijo, el cuerpo pasa por debajo | ✅ Ya existe (`sticky-top` + z-index en `layout.css`). |
| Proyecto funcional e integrado entre módulos | 🟡 En progreso — ver huecos de la sección 3 y 4 (Cámaras/Motivos y /Inventario rotos, Rondas sin migrar, Visitas con dependencias cruzadas a APIs viejas). |
| Dashboards en `.py` corriendo en el mismo servidor | ✅ Completo — ver sección 0l. Dashboard con los KPIs pedidos, integrado al MVC vía iframe, conexión por variables de entorno (ya no hardcodeada), instrucciones para dejarlo como servicio real de Windows (NSSM). No se pudo *correr* el servicio Python en este sandbox (no hay Python/SQL Server acá) — probarlo en Laragon. |
| Manuales: diccionario de datos, manual de usuario | 🔴 No creados. Hay documentación técnica interna (`RESUMEN_TECNICO_SISTEMA_ACTUAL.md`, `CONSIDERACIONES_PROYECTO.md`) pero no un diccionario de datos formal ni un manual de usuario final. |

---

## 6. Próximos pasos sugeridos (en orden)

1. ~~Repuntar las 6 referencias hardcodeadas y confirmar que el sitio sigue funcionando~~ ✅ Hecho (sección 0), terminó siendo 19 referencias en 8 archivos.
2. ~~Crear `views/camaras/motivos.php` e `views/camaras/inventario.php`~~ ✅ Hecho (sección 0b).
3. ~~Migrar `bit_consulta_visitas.php`~~ ✅ Hecho (sección 0c).
4. ~~Limpieza de `bit_index.php`~~ ✅ Hecho (sección 0d) — no se pudo borrar el archivo (10 legacy activos aún lo necesitan), pero se limpiaron las 11 referencias del lado MVC.
5. Decidir alcance real de Rondas, Talento_Humano, Control_Bines y Roles/Permisos (¿se migran en esta entrega o quedan para una siguiente fase?).
6. ~~Desplegar `analytics/dashboard.py` como servicio del mismo servidor y enlazarlo desde el sistema~~ ✅ Hecho (sección 0l) — falta solo que se pruebe/instale como servicio real en el servidor de producción, siguiendo `analytics/README.md`.
7. Diccionario de datos + manual de usuario.
