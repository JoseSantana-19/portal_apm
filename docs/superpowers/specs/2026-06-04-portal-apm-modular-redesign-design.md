# Portal APM — Rediseño Modular Integral
**Fecha:** 2026-06-04  
**Estado:** Aprobado por usuario  
**Versión:** 1.0

---

## 1. Contexto y Objetivo

Portal APM es el hub de integración institucional para la Autoridad Portuaria de Manta. Actualmente opera con estructura MVC plana (`controllers/models/views/`) con módulos funcionales de Talento Humano, Bitácoras, Control de Bienes y Control de Acceso.

**Objetivo:** Reescritura limpia a arquitectura modular (`modules/`) que sirva como plataforma de integración para módulos actuales y futuros, con dos dashboards diferenciados (ejecutivo/operativo), esquema BD modular prefijado, seguridad completa y setup automatizado.

**Restricción crítica:** PHP nativo con driver `sqlsrv` únicamente. Sin PDO, sin Composer obligatorio, sin frameworks externos.

---

## 2. Arquitectura General

**Patrón:** SPA-Hybrid — PHP renderiza shell (sidebar + topbar + layout), AJAX carga contenido modular en `#main-content`. Sin recarga de página en navegación interna.

**Stack:**
- Backend: PHP 8.0+ nativo, sqlsrv driver
- Base de datos: SQL Server 2014+ (Compatibility Level 120)
- Frontend: Vanilla CSS (custom properties, Grid, Flexbox), Vanilla JS (fetch API)
- Charts: ApexCharts (CDN, sin npm/build step)
- Auth: bcrypt + salt + TOTP base para MFA

**Flujo de request:**
```
Browser → index.php → Router → Controller → Model (sqlsrv nativo)
                                          ↓
                                     View::render()
                                          ↓
                    layout/shell.php (sidebar + topbar + #main-content)
                                          ↓
                    AJAX fetch() → /api/modulo → JSON | HTML parcial
                                          ↓
                    JS inyecta en #main-content + inicializa ApexCharts
```

---

## 3. Estructura de Carpetas

```
portal_apm/
├── index.php                          # Front Controller + autoloader
├── .htaccess                          # mod_rewrite → index.php
├── .env                               # Credenciales y config (NO en git)
├── .env.example                       # Template público
│
├── config/
│   ├── app.php                        # Lee .env, define constantes globales
│   ├── database.php                   # Pool sqlsrv nativo
│   ├── routes.php                     # Registro de rutas por módulo
│   └── globals.php                    # Constantes del sistema
│
├── core/
│   ├── Controller.php                 # Base: auth guard, render, jsonResponse
│   ├── Model.php                      # Base: sqlsrv_query, transacciones nativas
│   ├── View.php                       # Renderizado con layouts
│   ├── Router.php                     # Despacho GET/POST + regex dinámico
│   ├── Database.php                   # Conexión sqlsrv, pool, retry
│   └── Env.php                        # Parser .env nativo (sin Composer)
│
├── modules/
│   ├── Central/                       # Hub: dashboards, notificaciones
│   │   ├── controllers/
│   │   │   ├── DashboardController.php
│   │   │   └── NotificacionesController.php
│   │   ├── models/
│   │   │   ├── DashboardModel.php
│   │   │   └── NotificacionModel.php
│   │   └── views/
│   │       └── dashboard/
│   │           ├── ejecutivo.php
│   │           └── operativo.php
│   │
│   ├── Credenciales/                  # Auth, usuarios, roles, permisos
│   │   ├── controllers/
│   │   │   ├── AuthController.php
│   │   │   ├── UsuarioController.php
│   │   │   └── RolController.php
│   │   ├── models/
│   │   │   ├── UsuarioModel.php
│   │   │   ├── RolModel.php
│   │   │   └── SesionModel.php
│   │   └── views/
│   │       └── login/
│   │
│   ├── Talento_Humano/
│   │   ├── controllers/
│   │   │   ├── EmpleadoController.php
│   │   │   └── ContratosController.php
│   │   ├── models/
│   │   │   ├── EmpleadoModel.php
│   │   │   └── ContratoModel.php
│   │   └── views/
│   │       ├── empleados/
│   │       ├── contratos/
│   │       └── novedades/
│   │
│   ├── Bitacoras/
│   │   ├── controllers/
│   │   │   ├── EventoController.php
│   │   │   └── ReporteController.php
│   │   ├── models/
│   │   │   ├── BitacoraModel.php
│   │   │   └── EventoModel.php
│   │   └── views/
│   │       ├── eventos/
│   │       └── reportes/
│   │
│   ├── Control_Bienes/
│   │   ├── controllers/
│   │   │   ├── BienController.php
│   │   │   └── MovimientoController.php
│   │   ├── models/
│   │   │   ├── BienModel.php
│   │   │   └── MovimientoModel.php
│   │   └── views/
│   │       ├── bienes/
│   │       └── movimientos/
│   │
│   └── Control_Acceso/
│       ├── controllers/
│       │   ├── AccesoController.php
│       │   └── VisitanteController.php
│       ├── models/
│       │   ├── AccesoModel.php
│       │   └── VisitanteModel.php
│       └── views/
│           ├── accesos/
│           └── visitantes/
│
├── public/
│   ├── css/
│   │   ├── variables.css              # Design tokens
│   │   ├── theme-light.css
│   │   ├── theme-dark.css
│   │   ├── theme-corporate.css
│   │   ├── layout.css
│   │   ├── components.css
│   │   └── dashboard.css
│   ├── js/
│   │   ├── app.js                     # SPA orchestrator + tema toggle
│   │   ├── charts.js                  # Wrapper ApexCharts
│   │   └── modules/                   # JS por módulo
│   └── img/
│       └── logo_apm.png
│
├── helpers/
│   ├── session_helper.php
│   ├── security_helper.php            # CSRF, XSS, bcrypt
│   ├── url_helper.php
│   └── form_helper.php
│
├── logs/
│   └── error.log
│
├── storage/
│   ├── cache/
│   └── sessions/
│
├── PORTAL_APM_COMPLETO.sql
├── DOCUMENTACION_SISTEMA.md
├── SETUP_PROYECTO.bat
└── SETUP_PROYECTO.ps1
```

---

## 4. Esquema de Base de Datos

### Prefijos por módulo

| Prefijo | Módulo |
|---------|--------|
| `CORE_` | Central + Credenciales (usuarios, roles, permisos, menú, sesiones, auditoría) |
| `TH_` | Talento Humano |
| `BIT_` | Bitácoras |
| `BIENES_` | Control de Bienes |
| `ACCESO_` | Control de Acceso |

### Tablas CORE_ (13 tablas)

```sql
CORE_Departamentos        -- Árbol jerárquico L0-L2, self-ref id_padre, ON DELETE SET NULL
CORE_Usuarios             -- bcrypt+salt, MFA flag, lockout, NVARCHAR(512) para hash
CORE_Roles                -- Roles por departamento, nivel jerarquía 0-3
CORE_Usuarios_Roles       -- M:N usuarios↔roles, unique(id_usuario, id_rol)
CORE_Menu                 -- Árbol menú, item_padre self-ref, requiere_mfa flag
CORE_Permisos             -- Rol→Menú: tipo 1=ver, 2=editar, 3=crear, 4=admin
CORE_Formularios          -- Definiciones JSON de formularios dinámicos
CORE_Formularios_Permisos -- Rol→Formulario con nivel CRUD
CORE_Sesiones             -- Token activo por dispositivo, IP, user_agent, expiry, revocado
CORE_Auditoria            -- Log global: módulo, operación, antes/después JSON, fecha_purga
CORE_Notificaciones       -- Alertas por usuario: leídas/no leídas, prioridad 1-3
CORE_Contrasenas_Hist     -- Historial últimas 5 hashes por usuario
CORE_Config               -- Configuración dinámica clave-valor por módulo
```

### Tablas TH_ (5 tablas)
```sql
TH_Empleados, TH_Contratos, TH_Adendas, TH_Novedades_Medicas, TH_Auditoria
```

### Tablas BIT_ (4 tablas)
```sql
BIT_Categorias, BIT_Eventos, BIT_Archivos, BIT_Auditoria
```

### Tablas BIENES_ (4 tablas)
```sql
BIENES_Categorias, BIENES_Activos, BIENES_Movimientos, BIENES_Auditoria
```

### Tablas ACCESO_ (4 tablas)
```sql
ACCESO_Visitantes, ACCESO_Vehiculos, ACCESO_Registros, ACCESO_Auditoria
```

**Total: 30 tablas** (vs 37 actuales — consolidación sin pérdida de funcionalidad)

### Stored Procedures (9)
`sp_Login`, `sp_Logout`, `sp_CambiarContrasena`, `sp_GetMenuUsuario`, `sp_RegistrarAuditoria`, `sp_PurgarAuditoria`, `sp_GetKPIs_Operativo`, `sp_GetKPIs_Ejecutivo`, `sp_CrearNotificacion`

### Funciones (4)
`fn_TienePermiso`, `fn_TienePermisoFormulario`, `fn_GetArbolDepartamento`, `fn_SesionValida`

### Vistas (10)
`vw_MenuUsuario`, `vw_FichaEmpleado`, `vw_AuditoriaGlobal`, `vw_ResumenRoles`, `vw_KPIs_TH`, `vw_KPIs_Bienes`, `vw_KPIs_Acceso`, `vw_KPIs_Bitacoras`, `vw_SSO_Usuarios`, `vw_SSO_Menu`

### Índices clave (añadidos vs actual)
- `IX_CORE_U_Bloqueo` ON `CORE_Usuarios(fecha_bloqueo) WHERE fecha_bloqueo IS NOT NULL`
- `IX_CORE_S_Token` ON `CORE_Sesiones(token) WHERE revocado = 0`
- `IX_CORE_N_Usuario` ON `CORE_Notificaciones(id_usuario, leida)`
- `IX_CORE_A_Modulo_Fecha` ON `CORE_Auditoria(modulo, fecha_registro DESC)`

---

## 5. Dashboards

### Dashboard Ejecutivo (jerarquía nivel 2-3)
- 4 KPI cards: Empleados activos, Eventos hoy, Bienes registrados, Accesos hoy
- 4 charts ApexCharts: Donut (personal por dirección), Area (accesos 24h), Bar (contratos TH), Bar (bienes por estado)
- Panel alertas críticas con prioridad y link a detalle
- Cada KPI card tiene `[→ Ver detalle]` que carga dashboard operativo del módulo en `#main-content`
- Refresh automático cada 60 segundos vía AJAX

### Dashboard Operativo (jerarquía nivel 0-1)
- 4 KPI cards: Pendientes, Alertas, Movimientos hoy, Estado mi área
- Timeline de actividad reciente con hora/usuario/acción
- DataTable interactiva del módulo activo (filtros, búsqueda, exportar)
- Panel tareas pendientes accionables
- Chart area actividad del módulo hoy
- Chart radialBar cumplimiento tareas

### Despacho por rol
```php
// DashboardController.php
$nivel = $_SESSION['jerarquia_nivel'];
$vista = ($nivel >= 2) ? 'ejecutivo' : 'operativo';
View::render("Central/dashboard/{$vista}", $kpis);
```

---

## 6. Sistema de Temas (3)

| Tema | Trigger | Uso |
|------|---------|-----|
| `light` | Default | Corporativo claro, azul APM (#0056b3) |
| `dark` | Toggle | Oscuro moderno, acento azul claro (#4a9eff) |
| `corporate` | Toggle | Neutro ejecutivo, sidebar gris oscuro |

Implementación: `data-theme` en `<html>`, CSS custom properties en `variables.css`, persistido en `localStorage` + columna `tema_preferido` en `CORE_Usuarios`.

Toggle en topbar: ciclo `light → dark → corporate → light`.

---

## 7. Seguridad

| Control | Implementación |
|---------|---------------|
| CSRF | Token por sesión, `<input type="hidden">` en todo POST, `security_helper::verifyCsrf()` |
| XSS | `htmlspecialchars()` en todo output, CSP header |
| Sesiones | Tabla `CORE_Sesiones`, revocación individual por dispositivo |
| Credenciales | `.env` nativo, `Env.php` parser, nunca en PHP |
| Headers HTTP | X-Frame-Options, X-Content-Type-Options, X-XSS-Protection, CSP |
| Lockout | 5 intentos → bloqueo 30 min (configurable en `.env`) |
| Password | bcrypt + per-user salt, historial 5 hashes, no reutilización |
| MFA | TOTP base compatible Google Authenticator, flag por usuario |
| Input | Whitelist + tipo + longitud por campo en `form_helper.php` |
| Audit | `CORE_Auditoria` con retención configurable (`AUDIT_RETENTION_YEARS` en `.env`) |

---

## 8. Setup Automatizado (SETUP_PROYECTO.ps1 — Nivel C)

**Fases:**

1. **Verificación prerequisitos:**
   - PHP ≥ 8.0 instalado
   - Driver sqlsrv instalado y en php.ini (auto-install si no)
   - SQL Server accesible (lista instancias si falla)
   - Puerto 8000 libre (ofrece alternativa si ocupado)
   - `.env` existe (copia `.env.example` e interactivo si no)

2. **Reporte de estado:** Muestra tabla verificaciones antes de proceder. Pide confirmación `[S/N]`.

3. **Instalación:**
   - Crea BD `PORTAL_APM` si no existe
   - Ejecuta `PORTAL_APM_COMPLETO.sql` con barra de progreso
   - Crea carpetas `logs/`, `storage/cache/`, `storage/sessions/`
   - Verifica permisos escritura

4. **Resumen final:** URL, credenciales de prueba, abre navegador automáticamente.

**SETUP_PROYECTO.bat:** Wrapper de una línea con `powershell -ExecutionPolicy Bypass`.

---

## 9. Protocolo Integración Módulo Futuro

1. Crear `modules/NuevoModulo/{controllers,models,views}/`
2. Registrar rutas en `config/routes.php`
3. Insertar en BD: `CORE_Departamentos`, `CORE_Menu`, `CORE_Permisos`
4. Ejecutar SQL propio del módulo (tablas con prefijo `NUEVO_`)
5. Módulo aparece en sidebar automáticamente según permisos de rol

Zero acoplamiento con módulos existentes.

---

## 10. Decisiones de Diseño Clave

| Decisión | Razón |
|----------|-------|
| sqlsrv nativo (sin PDO) | Requisito explícito del usuario. Mejor rendimiento con SQL Server. |
| ApexCharts sobre Chart.js | Tooltips superiores, animaciones profesionales, tipos de chart necesarios |
| Reescritura limpia (no migración) | Estructura actual no escalable para hub modular. Deuda técnica alta. |
| `.env` nativo sin Composer | Mantiene zero-dependency del proyecto. Env.php < 50 líneas. |
| Tabla CORE_Sesiones separada | Permite múltiples sesiones por usuario, revocación granular, auditoría de dispositivos |
| Audit con retención configurable | Tablas audit sin control crecen indefinidamente. Retención 2 años por defecto. |
| 3 temas CSS (no framework CSS) | Sin Bootstrap/Tailwind — control total, sin clases de utilidad, peso mínimo |
