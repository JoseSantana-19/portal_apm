# Integración de Portuaria DemoV4 en Portal APM

**Fecha:** 2026-07-12 · **Origen:** `C:\xampp\htdocs\portuaria_demoV4` · **Plan:** `docs/superpowers/plans/2026-07-12-portuaria-demov4-integration.md`

## Vistas NATIVAS en el shell del portal (2026-07-13)

El módulo tiene "cara" nativa dentro del portal (temas t1/t2/t3, SPA), vía
`PortalPortuariaController` + `modules/Portuaria/views/portal/`:

| Vista | Ruta | Contenido |
|---|---|---|
| **Panel Portuario (hub)** | `/portuaria` | KPIs en vivo, tarjetas de acceso a las 9 secciones, últimas visitas, datos del módulo |
| Vista Rápida Visitas | `/portuaria/visitas-resumen` | Listado solo lectura con filtros (fecha + búsqueda), badge EN PUERTO |
| Actividad de Seguridad | `/portuaria/actividad` | Rondas del día (selector de fecha) + últimos registros CCTV con niveles de alerta |

El menú (área "Panel" del módulo 13) marca estas 3 con `target_spa=1`; el
resto del módulo abre con `target_spa=0` → recarga completa. Soporte nuevo:
el sidebar emite `data-no-spa` cuando `target_spa=0` y `js/main.js` lo
respeta (antes forzaba SPA en todo el sidebar y rompía páginas con layout
propio).

## Qué se integró (100%)

| Funcionalidad | Ruta en el portal | Implementación |
|---|---|---|
| Hub y vistas rápidas nativas | `/portuaria`, `/portuaria/visitas-resumen`, `/portuaria/actividad` | `PortalPortuariaController` (shell del portal) |
| Dashboard portuario del módulo (KPIs día) | `/portuaria/dashboard` | `PortDashboardController@index` |
| Panel Jefatura (MVC resumido) | `/dashboard-jefe` | `PortDashboardController@jefe` |
| Panel Jefatura completo (gráficos live) | `/bit_dashboard_jefe.php` | página legacy + `apis/bit_get_dashboard_live.php` + `public/js/portuaria/dashboard_jefe.js` |
| Dashboard Ejecutivo Python (Streamlit) | `/dashboard-ejecutivo` | iframe a `APM_DASHBOARD_EJECUTIVO_URL` (config/app.php); código en `analytics/` |
| Registrar ingreso de visitas | `/visitas/registrar` (+ POST `/bitacoras/visita/guardar`) | `PortVisitaController` + `includes/bit_visitas_guardar_ingreso.php` |
| Listado de visitas (DataTables, editar, salida, detalle) | `/visitas` + APIs `/bitacoras/visita/*` | `PortVisitaController` |
| Bitácora de rondas (turnos, ventana edición, XLSX) | `/rondas` + `/bitacoras/ronda/api` | `PortRondaController` + `bitacora_rondas.js` |
| Bitácora CCTV cámaras | `/camaras` + `/bitacoras/camara/api` | `PortCamaraController` + `bit_camaras.js` |
| Maestro de cámaras | `/camaras/inventario` + `apiInventario` | ídem + `inv_camaras.js` |
| Motivos CCTV | `/camaras/motivos` + `apiMotivos` | ídem + `bit_motivos_camaras.js` |
| Catálogos maestros (personas, empresas, destinos, motivos, funcionarios, niveles) | `/catalogos`, `/catalogos/*` + `/bitacoras/catalogo/api*` | `PortCatalogoController` + `catalogos.js` |
| Importar funcionarios desde FoxPro | `/importar-funcionarios` | vista + `apis/bit_sincronizar_funcionarios_dbf.php` |
| Lector rolmaes.DBF (Visual FoxPro) | `apis/bit_lector_rolmaes_dbf.php`, archivo en `dbf/` | consulta por cédula: `apis/bit_api_funcionarios_foxpro.php` |
| Reporte diario supervisor (novedades por turno) | `/bit_reporte_diario_supervisor.php` | página legacy + `apis/bit_reporte_supervisor_api.php` |
| Consulta de bitácoras | `/bit_consulta.php` | página legacy |
| Demo de permisos / diagnósticos | `/bit_dashboard_permisos_demo.php`, `/bit_test_*.php`, `/bit_diagnostico_dbf.php` | páginas legacy |
| APIs de datos (personas/empresas/motivos/destinos/funcionarios) | `apis/bit_*_api.php` | copiadas + **guard de sesión añadido** (`includes/bit_api_guard.php`) |

## Arquitectura de la integración

- **Módulo nativo** `modules/Portuaria/` en el runtime/rutas/autenticación del
  portal, con **layout propio** (Bootstrap 5 del origen) — no usa el shell SPA
  del portal para evitar choque de CSS. Enlace "Portal APM" en su sidebar
  vuelve al shell.
- **BDs separadas** `PortuariaDemo` / `PortuariaExterna` (constantes
  `DB_PORTUARIA_NAME` / `DB_PORTUARIA_EXT_NAME`): `PortDatabase` (singleton
  sqlsrv, auto-create) + `PortBaseModel` (misma API del Model origen).
- **`PortController`** replica la API del Controller origen (`model()`,
  `view()` con layout del módulo, `inputBody()`, `conn()/connExterna()`) sobre
  el Controller del portal (`requireAuth`, CSRF).
- **Clase `Auth` compat** (`modules/Portuaria/models/Auth.php`) + puente
  legacy (`includes/bit_dev_auto_login.php`, reescrito — el auto-login del
  origen quedó **desactivado**): traducen la sesión del portal a
  `$_SESSION['apm_auth']`. Mapeo de permisos: nivel ≥3 → área admin
  ('TECNOLOGIA DE LA INFORMACION'), nivel 2 → 'SEGURIDAD INTEGRAL'.
- **Rutas idénticas a los alias del origen** (routes.php sección Portuaria)
  para que el JS portado funcione sin reescritura; páginas/APIs legacy se
  sirven como archivos (Apache `.htaccess` !-f; en `php -S` lo replica el
  passthrough `cli-server` añadido a index.php).
- **Assets**: `public/librerias/` (Bootstrap, jQuery, DataTables, Select2,
  Chart.js, SweetAlert2, jsPDF, xlsx-js-style, icons), `public/js/portuaria/`,
  `public/css/portuaria/`, `public/img/portuaria/`.
- **Menú MOIS**: módulo **13 "Bitácoras Portuarias"** (5 áreas, 19 ítems,
  `target_spa=0`) — `db/portuaria_menu_integration.sql` (ejecutado);
  `MenuController::MODULES` y `$moduleColors` (#0891b2) actualizados.
- **CSP**: `frame-src` ahora permite el host del Streamlit (derivado de
  `APM_DASHBOARD_EJECUTIVO_URL`) y `X-Frame-Options: SAMEORIGIN`.

## Qué se sustituyó (no se copió tal cual)

- **Login/registro propio del origen** (bit_usuarios_apm, bit_login/register):
  el portal ya tiene login central (CORE_Usuarios + sp_Login). Sustituido por
  el **SSO de módulos** (abajo). Mismo criterio usado con TH e Inventario.
- `bit_dev_auto_login.php`: de auto-login demo → puente de sesión del portal.
- APIs de datos: se les añadió guard de sesión (eran públicas en el origen).

## SSO central para módulos (nuevo)

`db/sso_module_login.sql` (**ejecutado en PORTAL_APM**):

- Tabla `CORE_Aplicaciones` (api_key solo como hash SHA2-256, IPs permitidas,
  expiración) + `sp_SSO_RegistrarApp` (solo admin).
- `sp_SSO_Login` → valida app + estado/bloqueo de cuenta, entrega hash bcrypt
  solo a apps autenticadas (two-step, igual que sp_Login del portal).
- `sp_SSO_ConfirmarLogin` → token CRYPT_GEN_RANDOM(64) en `CORE_Sesiones`,
  resetea intentos, audita. `sp_SSO_RegistrarFallo` → lockout central.
- `sp_SSO_ValidarToken` / `sp_SSO_Logout` → sesión compartida entre módulos.
- Rol `rol_sso_modulos`: EXECUTE solo sobre los SP (mínimo privilegio).
- Cliente PHP: `libs/SsoClient.php`. Endpoints HTTP server-to-server:
  `POST /api/sso/login|validate|logout` (`ApiSsoController`).
- **Verificado end-to-end** (SQL y HTTP): login OK/fallo/lockout, app
  inválida sin filtración de hash, token válido → datos, logout revoca.

## Instalación en otro equipo

1. BDs: ver `db/portuaria/00_README_INSTALACION.md`.
2. Menú: ejecutar `db/portuaria_menu_integration.sql`.
3. SSO: ejecutar `db/sso_module_login.sql` y registrar apps con
   `sp_SSO_RegistrarApp`.
4. Streamlit: `analytics/README.md` (pip install + start_dashboard.bat).
