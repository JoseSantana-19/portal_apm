# Guía de integración de módulos independientes — Portal APM

Para quien está construyendo, por su cuenta, un módulo que después se va a
colgar de Portal APM (como ya pasó con Talento Humano, Bienes y Bitácoras).
Portal APM no reescribe tu código — vos seguís siendo dueño de tu propio
MVC, tus propias vistas, tu propia base de datos. Portal APM solo te presta
tres cosas: **login central, menú, y (si querés) el servidor de base de
datos**. Esta guía es el contrato para que esa conexión sea siempre igual,
sin importar quién construyó cada módulo.

## 1. Qué es "tuyo" y qué es de Portal APM

- **Portal APM** = login, permisos por rol, menú, notificaciones, admin. Nada más.
  No tiene "módulos de negocio propios" — Talento Humano, Bienes y Bitácoras
  son módulos independientes integrados, no código nativo del portal.
- **Tu módulo** = todo tu MVC, tus vistas, tu(s) base(s) de datos. Vive en su
  propia carpeta, con su propio front controller. Portal APM no lo toca.

## 2. Estructura de carpeta esperada

Tu módulo vive en `apps/<tu_modulo>/` con un único punto de entrada:

```
apps/<tu_modulo>/
├── index.php          ← front controller único (ver plantilla abajo)
├── core/               ← tu Controller/Model/Router — son tuyos, no los
│                         reescribas por cada módulo nuevo, copialos de otro
│                         módulo ya integrado (apps/talento_humano/core es
│                         el más simple) y ajustá lo que necesites.
├── public/             ← tus propios assets (css/js/img)
└── ...                 ← tu estructura MVC, como la necesites
```

## 3. Conexión a base de datos: una sola fuente

**No hardcodees servidor, usuario ni contraseña en tu código.** Todo el
sistema —portal, Talento Humano, Bienes— lee del mismo lugar:
`config/connections.php`, en la raíz de portal_apm. **No está en git**
(cada máquina tiene su propio servidor SQL) — si no lo tenés, copiá
`config/connections.example.php`. Es un archivo de datos puro (`return
[...]`), pensado para que cualquier módulo lo pueda leer sin chocar con sus
propias constantes:

```php
$conn = require __DIR__ . '/../../config/connections.php';   // ajustá los ../ a tu profundidad

$servidor  = $conn['databases']['tu_bd']['server'] ?? $conn['server_default'];
$baseDatos = $conn['databases']['tu_bd']['name'];             // agregá tu BD a la lista
$usuario   = $conn['credentials']['user'];
$clave     = $conn['credentials']['pass'];

$dsn = "sqlsrv:Server=$servidor;Database=$baseDatos;TrustServerCertificate=true";
$pdo = new PDO($dsn, $usuario ?: null, $clave ?: null, [...]);
```

Si tu módulo necesita su propia BD con nombre propio (como pasa con
`inventario`), agregala a la lista `databases` de `connections.php` — es la
única línea que hace falta tocar ahí. Si en algún momento cambia el
servidor o las credenciales, se edita **una sola vez**, en ese archivo, y
todos los módulos lo heredan automáticamente.

> ¿Tu módulo tiene un `.env` propio (como `apps/control_bienes`)? Dejá
> `DB_HOST`/`DB_USER`/`DB_PASS` vacíos ahí — así heredan de la config
> central por defecto, y solo los completás si de verdad necesitás un
> servidor distinto al del resto del portal.

## 4. Puente de sesión (SSO): tu módulo no tiene login propio

Portal APM ya resolvió login y permisos — tu módulo no necesita los suyos.
Al entrar, revisá si hay sesión del portal; si no, mandá al login central:

```php
// index.php — front controller de tu módulo
if (session_status() === PHP_SESSION_NONE) session_start();

define('ROOT', __DIR__);

// BASE_URL autodetectada: subcarpeta real donde vive tu módulo
// (p. ej. /portal_apm/apps/tu_modulo), sin tocar nada al cambiar de entorno.
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
define('BASE_URL', $basePath);

if (empty($_SESSION['user_id'])) {
    $portalLogin = preg_replace('#/apps/tu_modulo$#', '', $basePath) . '/login';
    header('Location: ' . ($portalLogin ?: '/login'));
    exit;
}

// A partir de acá tenés disponible, sin hacer nada más:
//   $_SESSION['user_id']          — id del usuario logueado
//   $_SESSION['nombre_completo']
//   $_SESSION['nivel_jerarquia']  — 0 Operador · 1 Analista · 2 Director · 3 Gerente · 4 SuperAdmin
//   $_SESSION['id_departamento']
```

## 5. Registrar tu módulo en el menú del portal

El menú vive en dos tablas de la BD `PORTAL_APM`: `CORE_Menu_Nodos` (qué
aparece) y `CORE_Permisos_Nodo` (quién lo ve). Pedí un `id_modulo` libre (el
siguiente disponible — mirá el máximo actual antes de elegir) y agregá un
único nodo "tu módulo completo", igual al patrón ya usado para Talento
Humano y Bienes (`db/apps_origen_integration.sql`):

```sql
USE PORTAL_APM;
INSERT INTO CORE_Menu_Nodos
  (id_modulo, opcion, items, subitems, descripcion, url_ruta, icono, orden, requiere_mfa, target_spa, estado)
VALUES
  (<TU_ID_MODULO>, 1, 1, 0, N'Tu Módulo', N'/apps/tu_modulo/', N'fa-window-restore', 1, 0, 0, 1);

-- Permisos: heredá los del rol ADMIN (id_rol = 1) para empezar
INSERT INTO CORE_Permisos_Nodo
  (id_rol, id_modulo, opcion, items, subitems, nivel_crud, acceso, estado, fecha_asignacion, asignado_por)
VALUES
  (1, <TU_ID_MODULO>, 1, 1, 0, 'CRUD', 1, 1, GETDATE(), 1);
```

Guardalo como `db/<tu_modulo>_menu_integration.sql`, idempotente (con sus
`IF NOT EXISTS` / `IF EXISTS ... UPDATE`), igual que los demás en `db/`.

## 6. Checklist antes de pedir que te integren

- [ ] Tu módulo corre solo, en su propia carpeta, con su propio `index.php`.
- [ ] No hay ningún servidor/usuario/contraseña de BD escrito a mano en tu
      código — todo sale de `config/connections.php`.
- [ ] Si no hay sesión de Portal APM, redirigís al login central (sección 4)
      en vez de mostrar un login propio.
- [ ] Tenés un `db/<tu_modulo>_menu_integration.sql` idempotente listo.
- [ ] El logo institucional lo tomás de `imgs/logoapm.png` (raíz de
      portal_apm) — no metas tu propia copia del logo.
- [ ] No duplicaste `modules/Talento_Humano`, `modules/Inventario` ni
      ningún otro nombre que ya exista como módulo independiente — si tu
      módulo pisa un dominio que ya está integrado, avisá antes de escribir
      código.

## 7. Qué evitar (errores ya encontrados y corregidos en este repo)

- **No** reescribas tu propio Controller/Model/Router/Database desde cero
  para cada módulo nuevo — copiá el kernel de un módulo ya integrado
  (`apps/talento_humano/core/` es el más simple) y ajustalo.
- **No** hardcodees el servidor de BD ni credenciales directo en PHP — pasó
  con `apps/talento_humano/core/Database.php` (servidor y `sa/123` escritos
  a mano) y obligaba a editar dos archivos cada vez que cambiaba de máquina.
- **No** dejes carpetas de prueba/prototipo viviendo en paralelo con la
  integración real una vez que esta última existe (pasó con
  `modules/Talento_Humano`, `modules/Inventario`, `modules/Control_Bienes`,
  `modules/Bitacoras` — reescrituras nativas iniciales, dadas de baja
  porque `apps/talento_humano`, `apps/control_bienes` y `modules/Portuaria`
  ya son la integración real y completa).
