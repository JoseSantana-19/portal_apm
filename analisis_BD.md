# Análisis de Base de Datos — PORTAL_APM

> Documento generado a partir del análisis técnico de la BD y la estructura organizacional real del Excel  
> `DIRECCIONES-AREAS-OPCIONES-ITEMS.xlsx`

---

## 1. Grupos de Tablas — Visión General

La base de datos **PORTAL_APM** se divide en **5 grupos** con roles muy distintos:

| Prefijo | Grupo | Tablas | Propósito |
|---------|-------|--------|-----------|
| `CORE_` | Infraestructura del portal | 13 tablas | Identidad, permisos, auditoría global |
| `TH_` | Talento Humano | 5 tablas | RRHH, contratos, novedades médicas |
| `BIT_` | Operaciones / Bitácoras | 4 tablas | Incidentes y eventos operativos |
| `BIENES_` | Dirección Administrativa | 4 tablas | Inventario de activos físicos |
| `ACCESO_` | Garita / Control de Acceso | 4 tablas | Control de entrada y salida |

---

## 2. CORE_ — El Pegamento del Portal (13 tablas)

Estas tablas **no pertenecen a ningún departamento**. Son la infraestructura que conecta todo el sistema.

| Tabla | Rol |
|-------|-----|
| `CORE_Departamentos` | Árbol organizacional de la Autoridad Portuaria. Todo lo demás (usuarios, empleados, bienes, contratos) referencia un `id_departamento` de aquí. |
| `CORE_Usuarios` | Identidades del portal — los 21 usuarios que pueden iniciar sesión. **No confundir con empleados**: un empleado puede existir sin usuario del sistema. |
| `CORE_Roles` | Roles por departamento (ej: "Analista TH", "Inspector Portuario"). |
| `CORE_Usuarios_Roles` | Relación M:N — qué roles tiene cada usuario. |
| `CORE_Menu_Nodos` | Los 134 nodos MOIS — define qué ítems de menú existen en el sistema. |
| `CORE_Permisos_Nodo` | Qué roles pueden ver qué nodos de menú y con qué nivel CRUD. |
| `CORE_Formularios` | Formularios registrados en el sistema. |
| `CORE_Formularios_Permisos` | Permisos granulares por formulario (si un rol puede ver un form específico). |
| `CORE_Sesiones` | Tokens de sesión activos con expiración. |
| `CORE_Auditoria` | Pista de auditoría global — **TODAS** las operaciones de **TODOS** los módulos van aquí. |
| `CORE_Notificaciones` | Notificaciones del sistema (broadcast o dirigidas a usuario). |
| `CORE_Contrasenas_Hist` | Historial de contraseñas para evitar reutilización. |
| `CORE_Config` | Llave-valor de configuración dinámica (`SSO_SECRET`, límites de login, timeouts). |

---

## 3. Sistemas Departamentales (17 tablas)

Cada grupo de prefijo corresponde a la simulación del sistema de un departamento que funciona de forma independiente.

### 3.1 TH_ — Talento Humano

| Tabla | Dato que maneja |
|-------|----------------|
| `TH_Empleados` | Fichas del personal |
| `TH_Contratos` | Contratos laborales (Nombramiento / Contrato / Ocasional) |
| `TH_Adendas` | Modificaciones a contratos existentes |
| `TH_Novedades_Medicas` | Licencias y bajas médicas |
| `TH_Auditoria` | Trazabilidad de operaciones TH (también registra en `CORE_Auditoria`) |

### 3.2 BIT_ — Operaciones / Bitácoras

| Tabla | Dato que maneja |
|-------|----------------|
| `BIT_Categorias` | Tipos de evento (incidente, mantenimiento, etc.) |
| `BIT_Eventos` | Log de cada evento operativo del puerto |
| `BIT_Archivos` | Documentos adjuntos a cada evento |
| `BIT_Auditoria` | Trazabilidad de cambios en bitácoras |

### 3.3 BIENES_ — Dirección Administrativa

| Tabla | Dato que maneja |
|-------|----------------|
| `BIENES_Categorias` | Tipos de activo físico |
| `BIENES_Activos` | Inventario de bienes físicos |
| `BIENES_Movimientos` | Transferencias, asignaciones y bajas de activos |
| `BIENES_Auditoria` | Trazabilidad de cambios en bienes |

### 3.4 ACCESO_ — Garita / Control de Acceso

| Tabla | Dato que maneja |
|-------|----------------|
| `ACCESO_Visitantes` | Registro de personas externas |
| `ACCESO_Vehiculos` | Vehículos asociados a visitantes |
| `ACCESO_Registros` | Log de cada entrada/salida del puerto |
| `ACCESO_Auditoria` | Trazabilidad de cambios en accesos |

> **Nota:** Cada grupo departamental tiene su propia tabla `XX_Auditoria`, pero también registra en `CORE_Auditoria` vía el procedimiento almacenado `sp_RegistrarAuditoria` para el consolidado global.

---

## 4. Estructura Organizacional Real (del Excel)

El Excel `DIRECCIONES-AREAS-OPCIONES-ITEMS.xlsx` contiene la jerarquía organizacional real de APM, que se mapea directamente a los **11 módulos** de `CORE_Menu_Nodos`:

| `id_modulo` | Dirección real (del Excel) | Prefijo de tablas |
|-------------|---------------------------|-------------------|
| 1 | Dirección de Planificación Estratégica | _(sin tablas propias aún)_ |
| 2 | Gestión de Tecnología e Informática | _(sin tablas propias aún)_ |
| 3 | Dirección de Asesoría Jurídica | _(sin tablas propias aún)_ |
| 4 | Dirección de Infraestructura Portuaria | _(sin tablas propias aún)_ |
| 5 | Garita / Control de Acceso | `ACCESO_*` |
| 6 | Dirección de Operaciones Portuarias | _(sin tablas propias aún)_ |
| 7 | Gerencia General | _(sin tablas propias aún)_ |
| 8 | Dirección de Delegación de Servicios | _(sin tablas propias aún)_ |
| 9 | Dirección Administrativa (Bienes) | `BIENES_*` |
| 10 | Dirección Financiera | _(sin tablas propias aún)_ |
| 11 | Dirección de Talento Humano | `TH_*` |

### ¿Por qué solo 3 departamentos tienen tablas propias?

Las tablas `TH_*`, `BIENES_*`, `ACCESO_*` y `BIT_*` son las **simulaciones construidas hasta ahora**. Los otros 8 departamentos (Planificación, TI, Jurídica, Infraestructura, Operaciones, Gerencia, Servicios, Financiero) solo tienen entradas en el menú pero aún no tienen tablas ni código PHP detrás.

Esto **no es un problema de diseño** — es el estado de avance del proyecto. El portal está diseñado para que cada departamento se agregue progresivamente.

---

## 5. Arquitectura de Integración

```
┌─────────────────────────────────────────────────────┐
│              PORTAL_APM (esta BD)                   │
│                                                     │
│  CORE_*   ──── Identidad, permisos, auditoría global│
│                                                     │
│  TH_*     ──── Datos de RRHH                        │
│  BIT_*    ──── Datos de operaciones                 │
│  BIENES_* ──── Datos de activos                     │
│  ACCESO_* ──── Datos de control de acceso           │
└───────────┬─────────────┬────────────┬──────────────┘
            │             │            │
       Sistema TH    Sistema       Sistema
       (propio)      Bienes        Acceso
                                           (proyectos independientes
                                            de cada departamento)
```

Los otros proyectos (desarrollados de forma independiente por departamento) escriben directamente en las tablas de su prefijo dentro de la misma base de datos `PORTAL_APM`. Por ejemplo:

- El sistema propio de Talento Humano hace `INSERT`s en `TH_Empleados`, `TH_Contratos`, etc.
- El sistema propio de la garita hace `INSERT`s en `ACCESO_Registros`, `ACCESO_Visitantes`, etc.

---

## 6. Las 4 funciones centrales del Portal APM

### 6.1 Dashboards integrados
El `DashboardModel` hace subconsultas a `TH_Empleados`, `TH_Contratos`, `BIENES_Activos`, `BIT_Eventos` y `ACCESO_Registros` al mismo tiempo para calcular los **KPIs ejecutivos** que se muestran en una sola pantalla.

### 6.2 Control centralizado de permisos
Via `CORE_Menu_Nodos` + `CORE_Permisos_Nodo`, el portal define si un usuario del departamento TH puede ver el módulo de Bienes o no. Los sistemas independientes no manejan esto — **el portal centraliza la autorización**.

### 6.3 SSO para sistemas externos
`ModuleSecurity::generateSSOToken()` genera un token HMAC para que los sistemas de otros departamentos puedan autenticar a sus usuarios sin pedirles credenciales de nuevo:

1. El usuario inicia sesión en Portal APM
2. El portal genera un token firmado con la clave en `CORE_Config` (`SSO_SECRET`)
3. El sistema externo recibe el token y lo valida contra el portal
4. El usuario llega al sistema externo ya autenticado, sin volver a ingresar contraseña

> Las vistas `vw_SSO_Usuarios` y `vw_SSO_Menu` son vistas **read-only** pensadas para que sistemas externos consulten datos de usuarios y permisos.

### 6.4 Pista de auditoría unificada
Aunque cada sistema tiene su tabla `XX_Auditoria`, `CORE_Auditoria` + la vista `vw_AuditoriaGlobal` da una vista de **todo lo que pasó en el sistema** sin importar el departamento.

---

## 7. Modelo completo de la BD

```
PORTAL_APM (base de datos unificada)
│
├── CORE_*  ─── La "columna vertebral" del portal
│               Todos los módulos comparten esto:
│               - Quién puede iniciar sesión       → CORE_Usuarios
│               - A qué departamento pertenece     → CORE_Departamentos
│               - Qué puede ver en el menú         → CORE_Menu_Nodos + CORE_Permisos_Nodo
│               - Registro de todo lo que hizo     → CORE_Auditoria
│               - Notificaciones cruzadas          → CORE_Notificaciones
│               - Configuración del sistema        → CORE_Config
│
├── TH_*  ──── Simulación del sistema de Talento Humano
│               TH_Empleados         → fichas del personal
│               TH_Contratos         → contratos laborales
│               TH_Adendas           → modificaciones a contratos
│               TH_Novedades_Medicas → licencias, bajas médicas
│               TH_Auditoria         → trazabilidad TH
│
├── BIT_*  ─── Simulación del sistema de Bitácoras
│               BIT_Categorias  → tipos de evento
│               BIT_Eventos     → log de cada evento operativo
│               BIT_Archivos    → documentos adjuntos
│               BIT_Auditoria   → trazabilidad
│
├── BIENES_* ── Simulación del sistema de Control de Bienes
│               BIENES_Categorias  → tipos de activo
│               BIENES_Activos     → inventario de bienes físicos
│               BIENES_Movimientos → transferencias, asignaciones, bajas
│               BIENES_Auditoria   → trazabilidad
│
└── ACCESO_* ── Simulación del sistema de Garita
                ACCESO_Visitantes  → registro de personas externas
                ACCESO_Vehiculos   → vehículos de visitantes
                ACCESO_Registros   → log de cada entrada/salida
                ACCESO_Auditoria   → trazabilidad
```

---

## 8. Fases de integración

### Fase actual — Simulación
Portal APM tiene sus propias tablas simulando los datos de cada departamento. El equipo de TH usa el módulo TH del portal, el equipo de Bienes usa el módulo Bienes, etc.

### Fase futura — Integración real
Cuando un departamento ya tenga su propio sistema consolidado (ej: el sistema real de nóminas de TH, o el sistema real de contabilidad de Finanzas), el portal se conectará vía SSO sin duplicar datos.

---

## 9. Cobertura actual vs. lo que describe el Excel

Las tablas `TH_*` y `BIENES_*` actuales son una **simplificación** de lo que el Excel describe en detalle:

### TH — Lo que el Excel incluye y aún no está implementado
- Nóminas completas (períodos, títulos, cargos)
- Direcciones y áreas organizacionales detalladas
- Movimiento de personal completo

### BIENES — Lo que el Excel incluye y aún no está implementado
- Bienes de consumo corriente
- Bienes de larga duración
- Proveedores
- Kardex
- Actas de entrega/recepción
- Cierre de mes y ajustes

> **Estimación:** Las tablas actuales cubren el **30–40%** del detalle real que describe el Excel. El resto necesitaría tablas adicionales cuando se desarrolle esa funcionalidad.

---

## 10. Resumen ejecutivo

| Aspecto | Descripción |
|---------|-------------|
| **¿Qué es `CORE_*`?** | La columna vertebral compartida por todos los departamentos |
| **¿Qué son `TH_*`, `BIT_*`, `BIENES_*`, `ACCESO_*`?** | Simulaciones de los sistemas departamentales reales |
| **¿Por qué en una sola BD?** | Para que el portal pueda cruzar datos entre departamentos en dashboards |
| **¿Cómo se conectarán los sistemas reales?** | Via SSO con tokens HMAC firmados desde `CORE_Config` |
| **¿Quién es dueño de los datos?** | Cada departamento es dueño de sus tablas (`TH_*`, `BIT_*`, etc.) |
| **¿Qué centraliza el portal?** | Identidad, permisos, auditoría global, notificaciones y dashboard ejecutivo |
