# Migración MVC — Portuaria DemoV4

## Principio fundamental

El proyecto **NO se reescribió desde cero**. La migración se hizo **dentro de la misma carpeta**, de forma que:

- Los archivos `bit_*.php` originales **siguen funcionando** mientras existan.
- Las nuevas rutas MVC conviven con las antiguas.
- Se puede migrar módulo por módulo sin romper nada.

## Cómo funciona la convivencia

El `.htaccess` hace esto:
1. Si el archivo solicitado **existe** (ej. `bit_login.php`) → lo sirve directo (comportamiento original).
2. Si **no existe** (ej. `/credenciales/auth/login`) → lo manda a `index.php` (front controller MVC).

Esto significa que puedes usar las dos formas en paralelo mientras migras.

## Estructura MVC creada

```
portuaria_demoV4/
├── index.php                    ← Front controller MVC (NUEVO)
├── .htaccess                    ← Reglas de reescritura (NUEVO)
│
├── config/
│   ├── globals.php              ← Unifica config_constants.php + zona_horaria.php
│   ├── database.php             ← Conexiones SQL Server (antes conexion/conexion.php)
│   └── routes.php               ← Alias de rutas amigables
│
├── core/
│   ├── Auth.php                 ← Unifica auth_session + auth_guard + auth_permissions
│   ├── Controller.php           ← Controlador base
│   ├── Database.php             ← Conexión sqlsrv centralizada
│   ├── Model.php                ← Modelo base con sqlsrv
│   └── Router.php               ← Enrutador módulo/controlador/acción
│
├── helpers/
│   ├── url_helper.php
│   └── security_helper.php
│
├── views/layouts/
│   └── main.php                 ← Layout compartido (reutiliza bit_navbar.php y bit_sidebar.php)
│
├── modules/
│   ├── Credenciales/            ← ✅ MIGRADO
│   │   ├── controllers/AuthController.php
│   │   ├── models/UsuarioModel.php
│   │   └── views/
│   │       ├── login/login.php
│   │       ├── login/register.php
│   │       └── password/cambiar.php
│   │
│   ├── Central/                 ← ✅ MIGRADO (parcial: jefe es esqueleto)
│   │   ├── controllers/DashboardController.php
│   │   ├── models/DashboardModel.php
│   │   └── views/dashboard/
│   │       ├── index.php
│   │       └── jefe.php
│   │
│   ├── Bitacoras/               ← ⏳ PENDIENTE
│   │   ├── controllers/
│   │   ├── models/
│   │   └── views/
│   │
│   ├── Talento_Humano/          ← ⏳ PENDIENTE (no existía antes)
│   ├── Control_Bines/           ← ⏳ PENDIENTE (no existía antes)
│
├── bit_*.php                    ← Archivos originales (siguen funcionando)
├── apis/                        ← APIs originales (siguen funcionando)
├── conexion/                    ← Conexiones originales (respaldo)
├── includes/                    ← Includes originales (respaldo)
└── ...
```

## Mapeo completo de archivos

### Credenciales (✅ migrado)

| Archivo original | → Nuevo destino | Notas |
|---|---|---|
| `bit_login.php` | `modules/Credenciales/controllers/AuthController.php::login()` + `views/login/login.php` | Lógica en controller, HTML en vista |
| `bit_logout.php` | `AuthController::logout()` | Solo 5 líneas, ahora es un método |
| `bit_register.php` | `AuthController::register()` + `views/login/register.php` | Lógica de validación en controller |
| `bit_cambiar_password.php` | `AuthController::cambiarPassword()` + `views/password/cambiar.php` | Usa el layout con navbar/sidebar |
| `includes/auth_session.php` | `core/Auth.php::start()` | Ahora es un método estático |
| `includes/auth_guard.php` | `core/Auth.php::guard()` | Ya no necesita `require` suelto |
| `includes/auth_permissions.php` | `core/Auth.php` (métodos `can*()` e `is*()`) | Todas las funciones `apm_*` ahora son `Auth::*()` |
| `includes/config_constants.php` | `config/globals.php` | Unificado con zona_horaria |
| `includes/dev_auto_bit_login.php` | Ya no se necesita (es solo para desarrollo) | Puedes recrearlo si lo necesitas |

### Central (✅ migrado parcialmente)

| Archivo original | → Nuevo destino |
|---|---|
| `bit_index.php` | `modules/Central/controllers/DashboardController.php::index()` + `views/dashboard/index.php` |
| `bit_dashboard_jefe.php` | `DashboardController::jefe()` + `views/dashboard/jefe.php` (esqueleto) |
| `includes/dashboard_jefe_data.php` | `modules/Central/models/DashboardModel.php` |
| `apis/get_dashboard_live.php` | Pendiente: será `DashboardController::apiLive()` |

### Bitácoras (⏳ pendiente — cómo migrar)

Este módulo es el más grande. Para cada archivo del grupo, el patrón es el mismo:
(✅ migrado)
**1. Visitas** (`bit_registrar_visita.php`, `bit_guardar_visita.php`, `bit_listado_visitas.php`, `bit_actualizar_visita.php`, `bit_actualizar_horas.php`, `bit_registrar_salida.php`, `bit_consulta_visitas.php`)
Crear:
- `modules/Bitacoras/models/VisitaModel.php` → extraer las queries de `includes/visitas_guardar_ingreso.php` y de las APIs
- `modules/Bitacoras/controllers/VisitaController.php` con métodos: `registrar()`, `guardar()`, `listado()`, `actualizar()`, `registrarSalida()`, `detalle()`
- `modules/Bitacoras/views/visitas/registrar.php` → HTML puro de `bit_registrar_visita.php`
- `modules/Bitacoras/views/visitas/listado.php` → HTML puro de `bit_listado_visitas.php`

(✅ migrado)
**2. Cámaras** (`bit_camaras.php`, `bit_inv_camaras.php`, `bit_motivos_camaras.php`)

Crear:
- `modules/Bitacoras/models/CamaraModel.php`
- `modules/Bitacoras/controllers/CamaraController.php`
- Vistas en `views/camaras/
`
(🫵 no migrado)
**3. Rondas** (`bit_rondas.php`)

Crear:
- `modules/Bitacoras/models/RondaModel.php`
- `modules/Bitacoras/controllers/RondaController.php`
- Vistas en `views/rondas/`

**4. Catálogos** (`bit_catalogos.php`, `bit_acc_*.php`, `includes/maestro_catalogo_base.php`)

Crear:
- `modules/Bitacoras/controllers/CatalogoController.php` con métodos: `index()`, `personas()`, `empresas()`, `destinos()`, `motivos()`
- Los `bit_acc_*.php` desaparecen — cada uno se vuelve una acción del controller

**5. APIs** → se convierten en métodos JSON del controller correspondiente:

| API original | → Método del controller |
|---|---|
| `apis/visitas_api.php` | `VisitaController::apiGuardar()` |
| `apis/personas_api.php` | `CatalogoController::apiPersonas()` |
| `apis/empresas_api.php` | `CatalogoController::apiEmpresas()` |
| `apis/destinos_api.php` | `CatalogoController::apiDestinos()` |
| `apis/motivos_api.php` | `CatalogoController::apiMotivos()` |
| `apis/catalogos_api.php` | `CatalogoController::api()` |
| `apis/funcionarios_api.php` | `CatalogoController::apiFuncionarios()` |
| `apis/camaras_bitacora_api.php` | `CamaraController::api()` |
| `apis/rondas_bitacora_api.php` | `RondaController::api()` |
| `apis/get_dashboard_live.php` | `DashboardController::apiLive()` |

### Infraestructura (✅ migrado)

| Archivo original | → Nuevo destino |
|---|---|
| `conexion/conexion.php` | `config/database.php` + `core/Database.php` |
| `conexion/conexion_externa.php` | `config/database.php` (sección 'externa') + `Database::connExterna()` |
| `conexion/zona_horaria.php` | `config/globals.php` (línea `date_default_timezone_set`) |
| `rutas/config_rutas.php` | Se sigue usando tal cual (el layout lo carga) |
| `bit_navbar.php` | Se sigue usando tal cual (el layout lo incluye) |
| `bit_sidebar.php` | Se sigue usando tal cual (el layout lo incluye) |

## URLs nuevas (MVC) vs. URLs originales

| URL original | URL MVC equivalente |
|---|---|
| `bit_login.php` | `/credenciales/auth/login` (alias: `/login`) |
| `bit_logout.php` | `/credenciales/auth/logout` (alias: `/logout`) |
| `bit_register.php` | `/credenciales/auth/register` (alias: `/register`) |
| `bit_index.php` | `/central/dashboard/index` (alias: `/` o `/dashboard`) |
| `bit_dashboard_jefe.php` | `/central/dashboard/jefe` |

## Patrón para migrar cualquier archivo bit_*.php

1. **Identificar las queries SQL** → van al Model
2. **Identificar la lógica de decisión** (if/else, permisos, redirects) → va al Controller
3. **Identificar el HTML** → va a la Vista
4. **Crear la ruta** en `config/routes.php` si necesita alias
5. **Probar** que la nueva URL funciona igual
6. **NO borrar** el `bit_*.php` original hasta confirmar que todo funciona

## Diferencias clave con el código original

| Antes | Ahora (MVC) |
|---|---|
| `require 'conexion/conexion.php'` → variable global `$conn` | `Database::conn()` o `$this->conn` en el Model |
| `require 'includes/auth_guard.php'` | `Auth::guard()` en el Controller |
| `apm_can_registrar_ingreso()` (función global) | `Auth::canRegistrarIngreso()` (método estático) |
| HTML mezclado con PHP en el mismo archivo | HTML en `views/`, lógica en `controllers/` |
| `apis/visitas_api.php` (archivo suelto) | `VisitaController::apiGuardar()` con `$this->json()` |
