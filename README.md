# Portal APM

Portal central de la Autoridad Portuaria de Manta: login, menú y permisos
por rol, sobre el que se integran módulos independientes construidos por
separado — hoy Talento Humano, Control de Bienes y Bitácoras (CCTV/visitas/
rondas) — más un dashboard analítico en Python. Backend PHP 8.2+ nativo
(sin framework) sobre Microsoft SQL Server.

## Arquitectura rápida

- **Portal nativo** (raíz del repo): `index.php` es el front controller único;
  `routes.php`, `core/`, `modules/Central`, `views/` son el login, menú,
  permisos y administración — lo único que es realmente "de" Portal APM.
  Config única en `config/app.php` + `config/connections.php`.
- **Módulos independientes integrados**: cada uno construido y mantenido por
  separado, colgado del portal sin que este reescriba su código.
  - `apps/talento_humano`, `apps/control_bienes`: mini-app PHP propia con su
    propio front controller, montada como subcarpeta y autenticada contra la
    sesión central del portal (SSO).
  - `modules/Portuaria`: Bitácoras CCTV/visitas/rondas — corre nativo en el
    router del portal pero con sus propias 2 bases de datos.
  - Ver **`GUIA_INTEGRACION_MODULOS.md`** para el contrato que sigue todo
    módulo nuevo que se quiera integrar.
- **`analytics/`**: dashboard ejecutivo en Python/Streamlit, embebido por
  iframe en el portal.
- **`imgs/`**: logo institucional único (`logoapm.png` / `logoapm_banner.png`),
  compartido por portal y apps embebidas — no se duplica por módulo.

## 1. Requisitos previos

- **PHP 8.2 o superior**, con la extensión **`sqlsrv`** (driver nativo de
  Microsoft, no requiere PDO) habilitada en `php.ini`.
- **Microsoft SQL Server 2014 o superior** (Express, Developer o Standard).
- **Microsoft ODBC Driver 17 o 18 para SQL Server**.
- Opcional: **Python 3.10+** (solo si vas a levantar el dashboard analítico
  de `analytics/`).
- Windows recomendado (XAMPP, WampServer, Laragon o PHP standalone); el
  proyecto no se ha probado en Linux/Mac.

## 2. Instalación rápida (automática)

Desde la raíz del proyecto, tras clonar:

```bat
SETUP_PROYECTO.bat
```

Este asistente interactivo detecta tu instancia de SQL Server, configura
`config/app.php`, crea la base `PORTAL_APM` y carga `PORTAL_APM_COMPLETO.sql`,
y te ofrece levantar el servidor local. Cubre el **núcleo** del sistema; para
los módulos integrados después (Talento Humano, Portuaria, hubs, SSO) segui
igual con los pasos manuales de la sección 3.4 en adelante — el script no los
ejecuta todavía.

## 3. Instalación manual paso a paso

### 3.1. Clonar el repositorio

```bash
git clone https://github.com/JoseSantana-19/portal_apm.git
cd portal_apm
```

### 3.2. Base de datos principal — `PORTAL_APM`

Ejecutá en orden (SSMS, o `php db/run_sql.php <archivo> <servidor> [usuario] [password]`
para hacerlo sin SSMS):

1. **`PORTAL_APM_COMPLETO.sql`** — crea la base `PORTAL_APM` (si no existe) con
   toda la estructura base, menú, usuarios y permisos. ⚠️ Si la base ya
   existe, este script la recrea (borra datos previos).
2. Migraciones incrementales de `db/` (idempotentes, se pueden re-ejecutar),
   **en este orden**:
   ```
   th_hr_schema_fix.sql        (ejecutar sobre la BD Talento_Humano, ver 3.3)
   th_account_mapping.sql
   sso_module_login.sql
   portuaria_menu_integration.sql
   hubs_menu_integration.sql
   focus_mode_menu.sql
   apps_origen_integration.sql
   th_bienes_menu_cleanup.sql
   entrada_origen_menu.sql
   ```
   `inv_menu_integration.sql` y `th_integration.sql` quedaron obsoletos
   (registraban rutas nativas `/inventario/*` y `/th/*` que ya no existen —
   la integración real de esos dos módulos es la app embebida completa,
   ver sección 3.6). No hace falta correrlos en una instalación nueva.
   Opcionales (datos de prueba, no necesarios para que el sistema funcione):
   `seed_large.sql`, `th_seed_datos_prueba.sql`, `sso_test_demo.sql` (este
   último requiere haber corrido antes `sso_module_login.sql`).

### 3.3. Base de datos de Talento Humano — `Talento_Humano`

Es una base **separada** de `PORTAL_APM`. Creala vacía y corré ahí
`db/th_hr_schema_fix.sql` (ver nota en el paso anterior). Datos de prueba
opcionales: `db/th_seed_datos_prueba.sql`.

### 3.4. Bases del módulo Portuaria — `PortuariaDemo` / `PortuariaExterna`

El código las auto-crea vacías al primer intento de conexión, pero el
esquema y los datos hay que cargarlos manualmente. Guía completa y opciones
en **`db/portuaria/00_README_INSTALACION.md`** — la vía rápida es ejecutar
`db/portuaria/99_DUMP_COMPLETO_PortuariaDemo.sql` y luego
`db/portuaria/01_DATABASE_BASE.sql` (siembra `PortuariaExterna`).

### 3.5. Configurar `config/connections.php`

Es la **única fuente real** de servidor/BDs/credenciales de todo el sistema
— portal, apps embebidas y (regenerando `analytics/.env`) el dashboard
Python. **No se sube a git** (cada máquina tiene su propio servidor SQL) —
copiá la plantilla y ajustala:

```bash
cp config/connections.example.php config/connections.php
```

```php
return [
    'server_default' => '.\\SQLEXPRESS',   // tu instancia, ej. '.\VICTUS' o 'localhost'
    'credentials'    => ['user' => '', 'pass' => ''],   // vacío = autenticación de Windows
    'databases' => [
        'portal'        => ['name' => 'PORTAL_APM',       'server' => null],
        'talento'       => ['name' => 'Talento_Humano',   'server' => null],
        'portuaria'     => ['name' => 'PortuariaDemo',    'server' => null],
        'portuaria_ext' => ['name' => 'PortuariaExterna', 'server' => null],
        'inventario'    => ['name' => 'inventario',       'server' => null],
    ],
];
```

`config/app.php` (constantes `DB_SERVER`, `DB_NAME`, etc., usadas por el
código nativo del portal) y `apps/talento_humano/core/Database.php` leen
de acá — no hace falta tocarlos aparte. `APP_URL` en `config/app.php` queda
en `'auto'`, no hace falta tocarlo salvo que quieras fijar una URL fija.

Si tocás este archivo y usás el dashboard analítico, regenerá su config:
`php config/export_analytics_env.php`.

### 3.6. Apps embebidas (`apps/`)

- **`apps/control_bienes`**: copiá `.env.example` a `.env`. `DB_HOST`/
  `DB_USER`/`DB_PASS` vacíos heredan de `config/connections.php` — solo
  completalos si este módulo necesita un servidor distinto al del resto.
  Las tablas se auto-crean solas en el primer request. Sin SQL Server
  configurado, cae por defecto a SQLite local (`database.sqlite`).
- **`apps/talento_humano`**: no tiene `.env` propio; usa la sesión del
  portal (SSO) y lee `config/connections.php` directamente.

### 3.7. Levantar el servidor

Con XAMPP/WampServer/Laragon: copiá la carpeta del proyecto a `htdocs`/`www`
y entrá a `http://localhost/portal_apm/`.

O con el servidor embebido de PHP, desde la raíz del proyecto:

```bash
php -S localhost:8000 -t .
```

y abrí `http://localhost:8000/`.

### 3.8. Iniciar sesión

Usuario de prueba tras importar los datos base: `admin` / `admin`. Ver
`INSTRUCCIONES_SETUP.md` para el listado completo de usuarios de prueba por
departamento.

## 4. Dashboard analítico (opcional)

Panel ejecutivo en Python/Streamlit embebido en `/dashboard-ejecutivo`. Ver
`analytics/README.md` para instalación (`pip install -r requirements.txt`),
configuración (`.env`) y cómo dejarlo corriendo como servicio de Windows.

## 5. Notas

- El repo no versiona `logs/`, `backup/`, `vendor/`, `__pycache__/` ni
  `scratch/` (ver `.gitignore`). Los `.env` sí se versionan a propósito
  (ninguno tiene secretos reales — autenticación de Windows/local).
- **¿Vas a construir o mantener un módulo independiente?** Ver
  `GUIA_INTEGRACION_MODULOS.md` — de ahí sale toda integración nueva.
- Documentación adicional: `DOCUMENTACION_SISTEMA.md` (arquitectura completa),
  `analisis_BD.md` (modelo de datos), `SYSPORT.md`.
