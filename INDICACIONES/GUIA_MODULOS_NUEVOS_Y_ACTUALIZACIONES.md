# Guía: Módulos Nuevos y Actualizaciones — Portal APM

Esta guía tiene dos partes independientes:

- **Parte 1** — cómo construir un módulo **nuevo** e integrarlo al portal (el
  camino que ya siguieron Talento Humano y Control de Bienes).
- **Parte 2** — cómo traer una **versión nueva** de un módulo que ya está
  integrado (por ejemplo, cuando un compañero te pasa una carpeta actualizada
  de su proyecto) sin perder las integraciones ni romper nada. Esta parte
  está basada en la actualización real de Control de Bienes hecha el
  2026-07-31 — todos los pasos y gotchas descritos ahí pasaron de verdad.

Si solo necesitás una de las dos, andá directo a esa sección — no hace falta
leer la guía completa de punta a punta.

---

## Parte 0 — Conceptos previos

Portal APM **no tiene módulos de negocio propios**. Es el *hub*: login
central, permisos por rol, menú, administración de cuentas. Los módulos de
negocio reales (Talento Humano, Control de Bienes, Portuaria) se construyen
y mantienen **por separado**, y se integran sin que el portal absorba su
código ni duplique sus datos.

Hay 3 formas de integrar algo al portal — elegir la que corresponde, no
inventar una cuarta:

| Patrón | Cuándo usarlo | Ejemplo |
|---|---|---|
| **A — Nativo** | Es genuinamente parte del portal (admin, auth, dashboards del hub) | `modules/Central`, `modules/Credenciales` |
| **B — App embebida + SSO** | Es un módulo de negocio externo, con su propio proyecto y BD | `apps/talento_humano`, `apps/control_bienes` |
| **C — Nativo con stack propio** | Corre dentro del router del portal pero mantiene su propio layout/BDs por una razón de dominio real | `modules/Portuaria` |

**Para un módulo nuevo, casi siempre es Patrón B.** No reescribas el módulo
dentro de `modules/` del portal — eso ya se intentó con Talento Humano,
Inventario, Control de Bienes y Bitácoras al principio del proyecto, y se
dio de baja por completo cuando la integración real (Patrón B/C) quedó
lista. Reescribir duplica trabajo y crea dos fuentes de verdad para lo mismo.

---

## Parte 1 — Implementar un módulo nuevo (Patrón B)

### 1.1 Estructura de carpeta

Tu módulo vive en `apps/<tu_modulo>/`, con **un único punto de entrada**:

```
apps/<tu_modulo>/
├── index.php     ← front controller único (puente de sesión + tu router)
├── core/         ← tu Controller/Model/Router/Database — copiados de otro
│                    módulo ya integrado (apps/talento_humano/core/ es el
│                    más simple), NO reescritos desde cero cada vez
├── public/       ← tus propios assets (css/js/img)
└── ...           ← tu estructura MVC, como la necesites
```

**No dupliques** el kernel MVC a mano. Copiar el de un módulo que ya
funciona y ajustar es más rápido y evita bugs ya resueltos ahí (ver Parte 3).

### 1.2 Conexión a base de datos: una sola fuente

**Nunca hardcodees servidor, usuario o contraseña en tu código.** Todo el
sistema — portal nativo, apps embebidas, dashboard analítico — lee del mismo
archivo: `config/connections.php` (raíz de portal_apm). No está en git (cada
máquina/servidor tiene el suyo); si no existe, copiá `config/connections.example.php`.

```php
$conn = require __DIR__ . '/../../config/connections.php';   // ajustá los ../ a tu profundidad

$servidor  = $conn['databases']['tu_bd']['server'] ?? $conn['server_default'];
$baseDatos = $conn['databases']['tu_bd']['name'];             // agregá tu BD a la lista
$usuario   = $conn['credentials']['user'];
$clave     = $conn['credentials']['pass'];

$dsn = "sqlsrv:Server=$servidor;Database=$baseDatos;TrustServerCertificate=true";
$pdo = new PDO($dsn, $usuario ?: null, $clave ?: null, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);
```

Si tu módulo necesita una BD con nombre propio, agregala a la lista
`databases` de `connections.php` — es la única línea que hay que tocar ahí.
Si cambia el servidor o las credenciales, se edita **una sola vez**, ese
archivo, y todos los módulos lo heredan.

> ¿Tu módulo tiene un `.env` propio (como `apps/control_bienes`)? Dejá
> `DB_HOST`/`DB_USER`/`DB_PASS` **vacíos** ahí — así por defecto heredan de
> la config central, y solo los completás si de verdad necesitás un servidor
> distinto al del resto del portal.

### 1.3 Puente de sesión (SSO): tu módulo no tiene login propio

Portal APM ya resolvió login y permisos. Tu módulo, al entrar, revisa si hay
sesión activa del portal; si no, redirige al login central:

```php
// apps/<tu_modulo>/index.php — front controller
if (session_status() === PHP_SESSION_NONE) session_start();

define('ROOT', __DIR__);

// BASE_URL autodetectada — no hardcodear "/portal_apm/apps/tu_modulo"
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
define('BASE_URL', $basePath);

if (empty($_SESSION['user_id'])) {
    $portalLogin = preg_replace('#/apps/tu_modulo$#', '', $basePath) . '/login';
    header('Location: ' . ($portalLogin ?: '/login'));
    exit;
}

// A partir de acá, sin hacer nada más, tenés disponible:
//   $_SESSION['user_id']          — id del usuario logueado
//   $_SESSION['nombre_completo']  (o 'nombre_usuario' — ver nota abajo)
//   $_SESSION['nivel_jerarquia']  — 0 Operador · 1 Analista · 2 Director · 3 Gerente · 4 SuperAdmin
//   $_SESSION['id_departamento']
```

> **Nota sobre identidad (actualizada 2026-07-30):** el portal usa **login
> único por cédula** — no hay concepto de "nombre de usuario" visible en
> ningún formulario. La columna `CORE_Usuarios.nombre_usuario` sigue
> existiendo internamente (la usa `sp_Login`/SSO) pero su valor **siempre es
> la cédula**. Si tu módulo muestra "usuario" en algún lado, mostrá la
> cédula, no inventes un campo de username separado.

Si tu módulo necesita mapear el nivel de jerarquía del portal a un rol
propio, hacelo con una función simple, no una tabla nueva — ejemplo real
(`apps/control_bienes/index.php`):

```php
$nivelPortal = (int)($_SESSION['nivel_jerarquia'] ?? 0);
$rolApp = $nivelPortal >= 3 ? 'Administrador' : ($nivelPortal === 2 ? 'Supervisor' : 'Operador');
```

### 1.4 Registrar tu módulo en el menú del portal

El menú vive en 2 tablas de `PORTAL_APM`: `CORE_Menu_Nodos` (qué aparece) y
`CORE_Permisos_Nodo` (quién lo ve). Elegí un `id_modulo` libre (mirá el
máximo actual en `CORE_Menu_Nodos` antes de elegir) y agregá un nodo mínimo,
mismo patrón que ya usan Talento Humano (11), Control de Bienes (12) y
Portuaria (13):

```sql
USE PORTAL_APM;
GO

-- L1: cabecera del módulo (sin URL)
INSERT INTO CORE_Menu_Nodos (id_modulo, opcion, items, subitems, descripcion, url_ruta, icono, orden, requiere_mfa, target_spa, estado)
VALUES (<TU_ID_MODULO>, 0, 0, 0, N'Tu Módulo', NULL, N'fa-window-restore', <siguiente_orden>, 0, 0, 1);

-- L3: el link real al sistema completo (target_spa=0 porque es una app externa, no una vista del portal)
INSERT INTO CORE_Menu_Nodos (id_modulo, opcion, items, subitems, descripcion, url_ruta, icono, orden, requiere_mfa, target_spa, estado)
VALUES (<TU_ID_MODULO>, 1, 1, 0, N'Tu Módulo', N'/apps/tu_modulo/', N'fa-window-restore', 1, 0, 0, 1);
GO

-- Permisos: dale acceso total al rol ADMIN (id_rol=1) para empezar
INSERT INTO CORE_Permisos_Nodo (id_rol, id_modulo, opcion, items, subitems, nivel_crud, acceso, estado, asignado_por)
VALUES (1, <TU_ID_MODULO>, 1, 1, 0, 4, 1, 1, 1);
GO
```

Guardalo como `db/<tu_modulo>_menu_integration.sql`, **idempotente** (con
`IF NOT EXISTS` antes de cada INSERT) — mismo patrón que el resto de scripts
en `db/`. Correlo contra la BD real solo después de probarlo en una BD
desechable (ver §2.7 — el mismo cuidado aplica acá).

Si además querés un panel nativo dentro del shell del portal (KPIs en vivo
antes de abrir el sistema completo — como `/panel/talento-humano` o
`/panel/bienes`), agregá un `PanelController@tuModulo()` en
`modules/Central/controllers/` con una query cross-DB simple
(`SELECT COUNT(*) FROM tu_bd.dbo.tu_tabla`) y un nodo de menú apuntando ahí.
No es obligatorio, pero es el patrón ya establecido.

### 1.5 Checklist antes de dar el módulo por integrado

- [ ] Corre solo, en su propia carpeta, con su propio `index.php`.
- [ ] Ningún servidor/usuario/contraseña de BD escrito a mano — todo sale
      de `config/connections.php`.
- [ ] Sin sesión del portal, redirige al login central (no muestra un login propio).
- [ ] Login/identidad usa **cédula**, no "nombre de usuario" en la UI.
- [ ] Tenés un `db/<tu_modulo>_menu_integration.sql` idempotente, **probado
      en una BD desechable** antes de correrlo contra la real.
- [ ] El logo institucional sale de `imgs/logoapm.png` (raíz de
      portal_apm) — no metas tu propia copia.
- [ ] `php -l` sin errores en todo el árbol de tu módulo.
- [ ] No duplicaste un dominio que ya está integrado (Talento Humano,
      Bienes, Portuaria) — si tu módulo pisa uno de esos, avisá antes de
      escribir código; probablemente lo que corresponde es la Parte 2 de
      esta guía, no un módulo nuevo.

---

## Parte 2 — Actualizar un módulo existente con una versión nueva

### 2.1 Cuándo aplica esto

Un compañero (o vos mismo, en otra máquina) trabaja el proyecto origen de
un módulo de forma independiente y te pasa una carpeta nueva — por ejemplo
`Downloads/MODULOS ACTUALIZADOS/.../Control_binesC`. Vos ya tenés ese módulo
integrado en `apps/<modulo>/`, con integraciones propias del portal
(puente de sesión, config central, algún fix). El objetivo: traer las
mejoras/fixes de la versión nueva **sin perder tus integraciones ni romper
lo que ya funciona.**

**Nunca hagas un `robocopy /MIR` o un `rm -rf` + copiar todo entero.** Eso
borra tus integraciones y cualquier archivo que solo exista en tu copia. El
método es comparar, clasificar, y copiar solo lo que corresponde.

### 2.2 Paso 1 — Localizar el origen viejo

Necesitás 3 carpetas para comparar, no 2:

- **OLD** — la copia del proyecto origen que se usó quejo la integración
  inicial (normalmente sigue en `Downloads/` de quien la integró la primera
  vez). Si no la encontrás, preguntá antes de asumir cuál es.
- **NEW** — la carpeta nueva que te acaban de pasar.
- **CUR** — tu copia actual integrada, `apps/<modulo>/`.

Sin el OLD, no podés distinguir "esto lo agregamos nosotros" de "esto ya
venía en el proyecto origen desde el principio" — es la pieza que hace
posible el diff a 3 bandas.

### 2.3 Paso 2 — Diff a 3 bandas

```bash
# Qué cambió upstream (para entender qué trae la actualización)
diff -rq --exclude=.git --exclude=__pycache__ --exclude=backup --exclude=logs "$OLD" "$NEW"

# Qué personalizamos nosotros (para saber qué NO tocar)
diff -rq --exclude=.git --exclude=__pycache__ --exclude=backup --exclude=logs "$OLD" "$CUR"

# El delta real que importa — el más útil de los 3
diff -rq --exclude=.git --exclude=__pycache__ --exclude=backup --exclude=logs --exclude=vendor --exclude=.env "$NEW" "$CUR"
```

El tercer diff (`NEW` vs `CUR`) suele ser mucho más corto que los otros
dos — en la práctica, tu copia integrada ya tiene casi toda la estructura
de la actualización, y lo que queda son los archivos realmente distintos.
Ese es el que hay que revisar uno por uno.

### 2.4 Paso 3 — Clasificar cada archivo que difiere

Para cada archivo en el diff `NEW` vs `CUR`, se cae en uno de estos casos:

| Caso | Qué hacer |
|---|---|
| **Archivo de integración nuestro** (puente de sesión, `config/connections.php`, logo del portal, un fix nuestro que el origen no tiene) | **NO sobrescribir.** Ver §2.4.1 |
| **Código puro que cambió upstream** (controllers/models/views sin nada del portal adentro) | Adoptar la versión NEW, pero primero verificar que CUR no tenga un fix propio escondido ahí (ver §2.4.2) |
| **Archivo/carpeta que solo existe en CUR** | Dejarlo — probablemente es algo que agregaste vos, no se toca |
| **Archivo/carpeta que solo existe en NEW** | Investigar qué es antes de copiarlo — no todo lo nuevo es código real (ver §2.5) |

#### 2.4.1 Cómo detectar un archivo de integración

Normalmente son pocos, pero **hay que revisarlos con `diff -u` uno por
uno**, nunca asumir por el nombre. Los candidatos típicos:

- `index.php` — casi siempre tiene el puente de sesión SSO.
- El archivo de config de conexión a BD (`config/globals.php`,
  `db/connection.php`, o como se llame en ese proyecto) — puede estar
  leyendo de `config/connections.php` en vez de tener credenciales sueltas.
- El layout/sidebar — puede tener el logo del portal y un link "Volver al
  Portal APM".
- Cualquier archivo donde antes ya se documentó un bug real del origen
  (ver §2.8) y se agregó un fix — si el NEW no trae ese fix, sobrescribir
  lo regresa.

**Ejemplo real** (Control de Bienes, 2026-07-31): `core/DatabaseConnection.php`
y `core/DatabaseStatement.php` tenían un fix propio que ignora mensajes
SQLSTATE clase `01` (informativos, tipo `PRINT`) al ejecutar SQL — el NEW no
lo traía. Si se hubiera sobrescrito sin revisar, se perdía ese fix
silenciosamente (nada tira error al copiar, el bug reaparece recién cuando
algo emite un PRINT).

#### 2.4.2 Cómo verificar que el código puro no esconde un fix

Antes de adoptar la versión NEW de un archivo "normal", correr:

```bash
diff -u "$NEW/ruta/Archivo.php" "$CUR/ruta/Archivo.php"
```

Si el diff es grande, no asumas que es solo "mejoras upstream" — puede ser
una reescritura real con cambios de comportamiento (nuevas columnas, tablas
nuevas que la vista/controller ahora espera). Si es así, hay que rastrear
**de dónde salen** esos cambios — normalmente de una migración SQL que
también viene en la actualización (ver §2.7). Adoptar el código sin la
migración correspondiente rompe la app apenas alguien la usa.

### 2.5 Paso 4 — Copiar el código seguro

Con la clasificación del paso anterior ya hecha, copiar archivo por archivo
(no la carpeta entera):

```bash
cp -f "$NEW/modules/Algo/controllers/AlgoController.php" "$CUR/modules/Algo/controllers/AlgoController.php"
# ... uno por cada archivo clasificado como "código puro, seguro de adoptar"
```

### 2.6 Paso 5 — Cuidado con lo que solo existe en NEW

No todo lo que trae la carpeta nueva es código real del módulo. Cosas que
aparecieron en la práctica y **no había que copiar**:

- **Una carpeta con el proyecto de OTRO módulo, de OTRA persona**, metida
  por error o como referencia dentro del ZIP que te pasaron. En el caso
  real: `Control_binesC` traía una carpeta suelta `talento_humano/` con una
  copia completa (código + BD) del proyecto de Talento Humano de **otro**
  compañero — sin relación con nuestro `apps/talento_humano` ni con
  nuestra BD `Talento_Humano`. Se descarta.
- **Scripts de debug personal del desarrollador** (`scratch_*.php`,
  `test_conexion.php` con credenciales de su máquina hardcodeadas tipo
  `sa`/`1234`) — no son parte de la app, no van a producción.
- **Un archivo `.bak` de varios MB** (dump binario de BD) — no es código,
  no se versiona.

Regla general: si algo en NEW no tiene relación obvia con el módulo que
estás actualizando, **investigalo antes de copiarlo** — `grep` si se
referencia desde algún lado del código real, mirá el contenido, preguntá si
hace falta.

**Dotfiles:** `ls` sin `-a` no los muestra. Un `.htaccess` nuevo puede
pisar reglas de rewrite que ya tenías (pasó una vez: perdió una regla y
causó 404 en toda la app). Revisar siempre con `ls -la` antes de decidir
qué excluir.

**Renombres de carpeta:** un módulo puede venir con la carpeta renombrada
(cambio de mayúsculas, ej. `Control_bines` → `Control_Bines`) — Windows es
insensible a mayúsculas en rutas, así que si copiás encima sin cuidado
podés terminar con una mezcla de ambas. Si ves esto, es más seguro borrar
la carpeta vieja del árbol específico afectado antes de copiar la nueva.

### 2.7 Paso 6 — Migraciones SQL nuevas: probar en BD desechable SIEMPRE

Si la actualización trae `.sql` sueltos (normalmente en algo como
`database/migrations/`), **nunca los corras directo contra la BD real**. El
método, sin excepción:

1. **Clonar la(s) BD(s) real(es) a una copia desechable.** Si tenés permiso
   de `BACKUP DATABASE`/`RESTORE`, usalo; si no (pasa en SQL Server Express
   si el servicio no tiene acceso a la carpeta de backup), la alternativa
   que funciona siempre es un `SELECT INTO` tabla por tabla:
   ```sql
   CREATE DATABASE tu_bd_TESTCLONE;
   -- por cada tabla:
   SELECT * INTO tu_bd_TESTCLONE.dbo.[nombre_tabla] FROM tu_bd.dbo.[nombre_tabla];
   ```
   **Ojo:** `SELECT INTO` copia datos y tipos de columna, pero **no copia
   PRIMARY KEY, FOREIGN KEY ni índices**. Si la migración nueva agrega un
   FK, hay que agregar la PK correspondiente a mano en el clon antes de
   probar, si no la migración va a fallar por una razón que no tiene nada
   que ver con la migración en sí.

2. **Correr la migración contra el clon**, no contra la real.

3. **Verificar el resultado consultando la BD directo** (`sys.objects`,
   `SELECT COUNT(*)`, etc.) — **no confiar solo en el mensaje de "éxito"
   del script.** Ver el gotcha específico en §2.8.

4. Si la migración toca datos ya existentes (no solo agrega tablas/columnas
   vacías), **revisar qué hay ahí antes de dejar que la sobrescriba.**
   Ejemplo real: una migración sincronizaba una tabla de "personal" contra
   la BD real de Talento Humano usando el mismo ID numérico — y la tabla
   destino tenía 4 filas de datos de prueba (`"Juan Pérez"`, etc.) cuyos IDs
   coincidían por casualidad con IDs de empleados reales. Sin revisar esto
   antes, la migración iba a reasignar 8 ítems de inventario y 4
   asignaciones de área a personas reales sin que nadie lo pidiera. Se
   detectó comparando los datos del clon contra los reales ANTES de correr
   nada en la BD real, y se guardó un respaldo (`SELECT * INTO
   zz_backup_tabla_fecha FROM tabla WHERE id IN (...)`) antes de aplicar,
   por si hacía falta revertir esos valores puntuales.

5. Recién con el clon verificado de punta a punta, correr contra la BD real
   — y volver a verificar ahí también (mismo método: consultar
   directo, no confiar en el stdout).

### 2.8 Paso 7 — Gotchas reales de SQL Server (ya nos mordieron una vez cada uno)

- **`sqlsrv_query()` no siempre reporta un error real que pasó dentro de un
  bloque `IF ... BEGIN ... END`.** Un script puede terminar con "N batches
  ejecutados correctamente" y aun así una tabla dentro de un `IF OBJECT_ID
  (...) IS NULL BEGIN CREATE TABLE ... END` no haberse creado, porque una
  constraint (ej. un FOREIGN KEY sin la PK correspondiente en la tabla
  referenciada) falló silenciosamente dentro del bloque. **Por esto el
  Paso 6.3 de arriba insiste en verificar consultando la BD directo, nunca
  confiar solo en el mensaje de éxito del runner.**

- **Referenciar una columna en el mismo batch donde se la agrega, sin `GO`
  de por medio, falla siempre.** SQL Server compila el batch completo antes
  de ejecutar ninguna sentencia — si hacés `ALTER TABLE ... ADD columna` y
  después, en el mismo batch (sin `GO` entre medio), un `UPDATE` que usa esa
  columna, falla con "nombre de columna no válido" aunque la lógica sea
  correcta. Solución: un `GO` entre el `ALTER TABLE` y cualquier sentencia
  que use la columna nueva.

- **Una transacción explícita (`BEGIN TRANSACTION` / `COMMIT`) no sobrevive
  cruzar un `GO`, con el driver `sqlsrv` que usa este proyecto** (conexión
  MARS) — tira "una transacción iniciada en un lote MARS todavía está
  activa al final del lote" y hace rollback de todo. Si tu migración
  necesita un `GO` en medio (por el punto anterior), **no la envuelvas en
  una transacción explícita** — apoyate en que cada paso ya sea idempotente
  por su propio `IF ... IS NULL` (mismo patrón que usa
  `PORTAL_APM_COMPLETO.sql` en todo el proyecto), así es seguro re-ejecutar
  el script completo si algo falla a mitad de camino.

- **Un simple `PRINT` o mensaje informativo (SQLSTATE clase `01`) se puede
  reportar como error** si no se llama
  `sqlsrv_configure('WarningsReturnAsErrors', 0)` en la conexión — el batch
  en sí puede haber corrido bien. `db/run_sql.php` ya tiene esto resuelto;
  si escribís tu propio script de conexión sqlsrv para probar algo suelto,
  acordate de esta línea.

- **`CREATE OR ALTER` requiere SQL Server 2016 SP1+.** Si el script apunta
  a ser compatible con 2014 (como los scripts principales de este
  proyecto), usar el patrón `IF OBJECT_ID(...) IS NOT NULL DROP PROCEDURE
  ...; GO; CREATE PROCEDURE ...` en su lugar. Si el motor real que estás
  usando ya es 2016+, `CREATE OR ALTER` funciona igual — pero si el
  objetivo es portabilidad a cualquier máquina, no asumas la versión del
  motor de tu propia PC.

### 2.9 Paso 8 — `php -l` en todo el árbol copiado

El proyecto origen puede traer bugs propios, no solo mejoras. Antes de dar
por terminada la actualización:

```bash
find "apps/<modulo>" -name "*.php" -not -path "*/vendor/*" | while read f; do
  php -l "$f" | grep -v "No syntax errors"
done
```

Si aparece algo, es un bug del origen (ej. un `try` sin cerrar), no algo que
vos rompiste — corregilo igual, pero documentalo como "fix nuestro" para la
próxima actualización (§2.4.1 de nuevo).

### 2.10 Paso 9 — Aplicar a la BD real

Con todo probado en el clon:

1. Correr la(s) migración(es) contra la BD real, **en el orden correcto**
   si hay dependencias entre ellas (probado ya en el Paso 6).
2. Verificar consultando la BD real directo (mismo método, nunca confiar
   solo en el stdout).
3. Si la app tiene una URL de prueba rápida, pegarle un `curl` para
   confirmar que responde (200/302 esperado, no 500).

### 2.11 Resumen del flujo completo

```
1. Localizar OLD, tener NEW a mano, CUR es tu copia actual
2. diff -rq a 3 bandas → entender qué cambió y qué es nuestro
3. Clasificar archivo por archivo (integración / código puro / solo-CUR / solo-NEW)
4. Copiar solo lo clasificado como seguro
5. Investigar todo lo "solo-NEW" antes de copiarlo (puede ser basura ajena)
6. Si trae SQL nuevo: clonar BD → probar ahí → verificar consultando directo
   → revisar impacto en datos existentes → recién ahí, BD real
7. php -l en todo el árbol
8. Aplicar a BD real + verificar de nuevo
```

---

## Parte 3 — Errores ya cometidos (no repetir)

- **No** reescribas tu propio Controller/Model/Router/Database desde cero
  para cada módulo nuevo — copiá el kernel de un módulo ya integrado
  (`apps/talento_humano/core/` es el más simple) y ajustalo.
- **No** hardcodees servidor/credenciales de BD directo en PHP — pasó con
  `apps/talento_humano/core/Database.php` originalmente (servidor y
  `sa/123` escritos a mano) y obligaba a editar el archivo cada vez que
  cambiaba de máquina.
- **No** dejes carpetas de prueba/prototipo viviendo en paralelo con la
  integración real una vez que esta existe — pasó con `modules/Talento_Humano`,
  `modules/Inventario`, `modules/Control_Bienes`, `modules/Bitacoras`
  (reescrituras nativas iniciales, dadas de baja por completo cuando
  `apps/talento_humano`, `apps/control_bienes` y `modules/Portuaria`
  quedaron como la integración real).
- **No** confíes en el mensaje de éxito de un script SQL sin verificar
  consultando la BD directo — ver §2.8, primer punto.
- **No** corras una migración que toca datos existentes sin revisar antes
  qué hay ahí — ver §2.7, punto 4.
- **No** asumas que un `robocopy`/copia completa de carpeta es seguro para
  actualizar un módulo integrado — siempre borra integraciones si no se
  excluyen a mano primero.

---

*Ver también: `DOCUMENTACION_SISTEMA.md` §7 para el detalle de cada módulo
ya integrado, y §17 para el resumen corto de esta guía.*
