# Plan: Integración 100% de portuaria_demoV4 en portal_apm

**Fecha:** 2026-07-12 · **Origen:** `C:\xampp\htdocs\portuaria_demoV4` · **Destino:** `C:\Users\Usuario\Desktop\PRACTICAS\portal_apm`

## Objetivo
Integrar TODAS las funcionalidades de Portuaria DemoV4 (Bitácoras: visitas/rondas/cámaras CCTV/catálogos, dashboards jefe/supervisor/ejecutivo, APIs, analytics Streamlit, lector DBF rolmaes, SQL) como módulo nativo del portal integrador, + SPs de login seguro en PORTAL_APM invocables por otros módulos.

## Decisiones de arquitectura

1. **Módulo destino:** `modules/Portuaria/` (nuevo). El `modules/Bitacoras/` existente (eventos BIT_* de PORTAL_APM) se conserva intacto.
2. **BDs:** mismas del origen — `PortuariaDemo` (principal, tablas bit_*) y `PortuariaExterna` (APM externa). Constantes `DB_PORTUARIA_NAME` / `DB_PORTUARIA_EXT_NAME` en `config/app.php`. Server/credenciales = las del portal (`DB_SERVER` etc.).
3. **Acceso a datos:** `PortDatabase` (patrón InvDatabase/ThHrDatabase): singleton sqlsrv nativo, sin PDO, auto-CREATE DATABASE si falta. Métodos `connection('principal'|'externa')` para compat con el core del origen. `PortBaseModel` replica la API del `Model` origen (query/fetchAll/fetchOne/count con firma idéntica) → modelos bit_*Model.php se portan casi sin cambios.
4. **Controllers:** `PortController extends Controller` (del portal) que replica la API del Controller origen: `model()`, `view()` (layout PROPIO del módulo), `inputBody()` (renombre de `input()` origen — la firma choca con el del portal), `conn()`, `connExterna()`. Auth = portal (`requireAuth()` + sesión `$_SESSION['user_id']`).
5. **Clase `Auth` compat** (`modules/Portuaria/models/Auth.php`): misma API estática del origen (`check/guard/guardApi/user/userName/departamento/canX...`) pero alimentada por la sesión del PORTAL. Mapa de permisos: nivel_jerarquia>=3 = área admin; departamento con 'SEGURIDAD' o nivel>=2 = seguridad operativa. Puebla `$_SESSION['apm_auth']` para vistas/APIs legacy.
6. **Vistas:** portadas tal cual (Bootstrap 5 + DataTables) con **layout propio del módulo** (`modules/Portuaria/views/layouts/main.php` + navbar + sidebar del origen, adaptados: APP_URL, sesión portal, link "Portal APM"). NO se meten al shell del portal (choque Bootstrap vs css propio). Navegación del portal → entra al módulo; navbar del módulo → vuelve al portal.
7. **Rutas:** registrar en `routes.php` los MISMOS paths alias del origen (`/visitas`, `/rondas`, `/camaras...`, `/catalogos...`, `/bitacoras/{ronda|camara|catalogo}/api*`, `/dashboard-jefe`) para que los JS del origen funcionen sin reescritura. Sin colisiones con rutas existentes (verificado: `/bitacoras/{id}` no matchea paths de 3 segmentos).
8. **APIs legacy (`apis/bit_*.php`)** — se copian a `portal_apm/apis/` con shim de bootstrap (`apis/_bootstrap_portuaria.php`: config portal + conexiones + sesión apm_auth). Se sirven como archivos directos (RewriteCond !-f). Los JS llaman `apis/...` relativo — funciona con base href.
9. **Páginas legacy raíz** (bit_dashboard_jefe, bit_reporte_diario_supervisor, bit_consulta, bit_dashboard_permisos_demo, diagnósticos): portadas bajo controller `PortLegacyController` (rutas `/portuaria/*`) con includes/ copiados a `modules/Portuaria/legacy/includes/`.
10. **Assets:** `public/librerias/` (bootstrap, jquery, datatables, select2, chart.js, sweetalert2, jspdf, xlsx, icons) → copiar a `portal_apm/public/librerias/`. `public/js/*.js` → `portal_apm/public/js/portuaria/`. `public/css/*` → `portal_apm/public/css/portuaria/`. `public/img` → `public/img/portuaria/`. `$url_*` centralizados en `modules/Portuaria/config_rutas.php` con APP_URL absoluto.
11. **Credenciales origen (login/registro propio):** NO se porta el login (portal manda; misma decisión que TH/Inventario). Su reemplazo institucional = SPs SSO (abajo). `bit_UsuarioModel` se porta solo en lo necesario para catálogos de usuarios del módulo.
12. **SQL:** `sql/*.sql` del origen → `db/portuaria/` + README de orden. `script (3).sql` (dump completo 11-jul-2026) → `db/portuaria/99_DUMP_COMPLETO_PortuariaDemo.sql` (opción rápida). Advertir: 02 es DESTRUCTIVO (DROP tablas).
13. **SPs Login para módulos (PORTAL_APM):** `db/sso_module_login.sql`:
    - `CORE_Aplicaciones` (codigo, secret SHA2_256, estado, expira) + `sp_App_Registrar`.
    - `sp_SSO_Login` (@codigo_app,@api_key,@usuario,@ip → valida app+usuario+lockout, entrega hash bcrypt para verificación en cliente; nunca expone hash si app inválida).
    - `sp_SSO_ConfirmarLogin` (crea token CRYPT_GEN_RANDOM en CORE_Sesiones, resetea intentos, audita).
    - `sp_SSO_RegistrarFallo`, `sp_SSO_ValidarToken` (sesión válida→datos+menú), `sp_SSO_Logout`.
    - Rol BD `rol_sso_modulos` con GRANT EXECUTE mínimo.
    - Cliente PHP `libs/SsoClient.php` + endpoints HTTP `/api/sso/*` (`ApiSsoController`) para módulos que no acceden a la BD.
14. **Analytics Streamlit:** copiar `analytics/` a `portal_apm/analytics/` (dashboard.py, requirements, .env.example → PortuariaDemo en DB_SERVER portal, start_dashboard.bat, README). Constante `APM_DASHBOARD_EJECUTIVO_URL` en config/app.php. Vista dashboard ejecutivo del módulo la embebe.
15. **DBF:** copiar `dbf/` (rolmaes.DBF + docs) a `portal_apm/dbf/`; el lector/sincronizador ya viene en apis/.
16. **MOIS:** módulo 13 "Bitácoras Portuarias" en CORE_Menu_Nodos vía `db/portuaria_menu_integration.sql` (idempotente): opciones Visitas/Rondas/CCTV/Catálogos/Dashboards, permisos rol ADMIN, color sidebar #0ea5e9, MenuController::MODULES + sidebar $moduleColors.

## Inventario origen → destino

| Origen | Destino | Acción |
|---|---|---|
| modules/Bitacoras/controllers/bit_{Visita,Ronda,Camara,Catalogo}Controller.php | modules/Portuaria/controllers/ | portar (extends PortController) |
| modules/Bitacoras/models/bit_*Model.php | modules/Portuaria/models/ | portar (extends PortBaseModel) |
| modules/Bitacoras/views/** | modules/Portuaria/views/** | copiar + adaptar refs |
| modules/Central/{controllers,models,views} | PortDashboardController + views/dashboard | portar |
| modules/Credenciales | — (auth portal) + SSO SPs | sustituir |
| views/layouts/{main,bit_navbar,bit_sidebar}.php | modules/Portuaria/views/layouts/ | adaptar |
| apis/*.php (11) | apis/ + shim | copiar+shim |
| includes/*.php (10) | modules/Portuaria/legacy/includes/ | copiar+adaptar |
| bit_dashboard_jefe.php, bit_reporte_diario_supervisor.php, bit_consulta.php, bit_dashboard_permisos_demo.php, bit_test/diagnóstico*.php | rutas /portuaria/* vía PortLegacyController | portar |
| helpers/listado_helper.php | helpers/ (merge, sin pisar) | copiar |
| includes/bit_apm_fecha_iso.php, bit_validaciones_ecuador.php | modules/Portuaria/legacy/includes/ (cargados por PortController) | copiar |
| public/js (14 js) | public/js/portuaria/ | copiar |
| public/css (6) | public/css/portuaria/ | copiar |
| public/librerias | public/librerias | copiar |
| public/img | public/img/portuaria | copiar |
| analytics/ | analytics/ | copiar+ajustar .env |
| dbf/ | dbf/ | copiar |
| sql/ + script (3).sql | db/portuaria/ | copiar+README |
| docs (ESTADO, RESUMEN, MIGRACION, CONSIDERACIONES) | docs/portuaria/ | copiar referencia |

## Orden de ejecución
1. Config (constantes) + PortDatabase + PortBaseModel + PortController + Auth compat + config_rutas.
2. Copia masiva de assets (librerias, js, css, img, dbf, analytics, docs, sql).
3. Portar models (4) + controllers (5: Visita, Ronda, Camara, Catalogo, Dashboard).
4. Layout del módulo + vistas (13).
5. APIs shim + includes legacy + páginas legacy.
6. routes.php + MOIS SQL + sidebar/MenuController.
7. SSO SPs + SsoClient + ApiSsoController + rutas /api/sso.
8. Lint PHP de todo lo nuevo + verificación rutas + doc final.

## Riesgos
- JS origen llama URLs relativas → mitigar con `<base href>` en layout del módulo (origen ya lo usaba) y rutas alias idénticas.
- `Auth` clase nueva global — no existe hoy en portal (verificado). Autoloader la encuentra en modules/Portuaria/models/.
- Colisión helper `base_url()`: portal ya define uno en helpers/url_helper.php — el módulo usa el del portal; verificar semántica en vistas portadas.
- 02_DATABASE_ESTRUCTURA.sql es destructivo → README con advertencia; el dump completo es la vía recomendada para instalar desde cero.
