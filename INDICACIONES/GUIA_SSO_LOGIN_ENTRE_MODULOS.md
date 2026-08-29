# SSO — Login centralizado entre módulos del Portal APM

Guía de referencia para integrar un módulo nuevo (propio o externo) contra el
login centralizado del Portal APM, usando el mecanismo real que ya existe en
la base de datos: `db/sso_module_login.sql`. **Verificado y corregido en
vivo el 2026-08-26** — ver sección 9.

---

## 1. Resumen: ¿qué es esto y cuándo lo necesito?

`PORTAL_APM.dbo.CORE_Usuarios` es el directorio central de identidad de todo
el sistema (una sola cuenta por persona, login por cédula). Un módulo nuevo
que necesite "iniciar sesión como ese usuario" tiene **dos caminos posibles**,
según cómo esté desplegado:

| Tu módulo es... | Mecanismo a usar | Por qué |
|---|---|---|
| Una app PHP embebida en este mismo proyecto (`apps/tu_modulo/`), en el mismo servidor, misma sesión PHP del portal | **Puente de sesión (Patrón B)** — lee `$_SESSION` directamente, sin pasar contraseña de nuevo | Ya comparte cookie/sesión con el portal; pedirle usuario+clave otra vez sería mala UX. Así lo hacen hoy Talento Humano, Control de Bienes y Bitácoras. |
| Una app en **otro servidor**, un servicio externo, una app móvil, o cualquier cosa que **no** comparta sesión PHP con el portal, pero **sí** tiene acceso a la base de datos `PORTAL_APM` | **SSO por procedimientos almacenados** (`sp_SSO_*`) vía `libs/SsoClient.php` | Es justo para lo que se diseñó: autenticar sin acceso directo a las tablas, con su propia credencial de aplicación. |
| Una app externa que **ni siquiera** tiene acceso SQL a `PORTAL_APM` | **Mismo SSO, pero por HTTP** — `POST /api/sso/login` etc. | Mismos procedimientos por debajo, expuestos como API REST del portal. |

**Esta guía documenta el segundo y tercer camino** (el pedido explícito: "el
procedimiento almacenado que se encarga de llamar y hacer login a los otros
módulos"). El primer camino (puente de sesión) se explica brevemente en la
sección 10 para que sepas cuál te corresponde.

---

## 2. Arquitectura del SSO por procedimientos almacenados

```
Módulo nuevo                    PORTAL_APM (SQL Server)
─────────────                   ───────────────────────
                                 CORE_Aplicaciones  (credencial del módulo)
                                 CORE_Usuarios      (directorio central)
                                 CORE_Sesiones      (tokens emitidos)
                                 CORE_Auditoria     (registro de todo)

1) sp_SSO_Login(app, key, usuario)
   ───────────────────────────────►  valida app+key, busca la cuenta,
                                      aplica sp_Login (estado/bloqueo)
   ◄───────────────────────────────  resultado + hash bcrypt (solo si OK)

2) password_verify() EN TU MÓDULO (nunca en SQL)

3a) si coincide:
    sp_SSO_ConfirmarLogin(app, key, id_usuario)
    ───────────────────────────────►  emite token aleatorio + expiración
    ◄───────────────────────────────  token, fecha_expira

3b) si NO coincide:
    sp_SSO_RegistrarFallo(app, key, usuario)
    ───────────────────────────────►  cuenta intento fallido / bloquea
                                       (mismo control que el login nativo)

4) en cada petición siguiente:
    sp_SSO_ValidarToken(app, key, token)
    ───────────────────────────────►  ¿token vivo? ¿no expiró?
    ◄───────────────────────────────  datos del usuario o error

5) al cerrar sesión:
    sp_SSO_Logout(app, key, token)
    ───────────────────────────────►  revoca el token
```

**Por qué la contraseña nunca viaja por SQL**: `sp_SSO_Login` devuelve el
hash bcrypt de la cuenta, pero la comparación real (`password_verify`) la
hace tu módulo en PHP. SQL Server nunca ve la contraseña en texto plano ni
decide si es correcta — solo entrega el hash a una aplicación ya autenticada
por su propia credencial (`codigo` + `api_key`).

---

## 3. Los procedimientos, uno por uno

Definidos en `db/sso_module_login.sql` (ya ejecutado contra `PORTAL_APM` —
confirmado, ver sección 9). Todos exigen `@codigo_app` + `@api_key` válidos
como primeros parámetros; si no lo son, no revelan nada más.

### `dbo.sp_SSO_Login` — Paso 1: validar cuenta

```sql
EXEC dbo.sp_SSO_Login
    @codigo_app      = N'TU_MODULO',
    @api_key         = N'<tu api key>',
    @nombre_usuario  = N'1234567777',      -- la cédula (login del portal es solo por cédula)
    @ip_address      = N'127.0.0.1',       -- opcional, para el filtro ip_permitidas
    @resultado       = @res OUTPUT,        -- ver tabla de resultados abajo
    @id_usuario      = @id OUTPUT,
    @hash_contrasena = @hash OUTPUT,       -- SOLO si @resultado = 'OK'
    @nivel_jerarquia = @niv OUTPUT,
    @req_cambio_pass = @req OUTPUT,
    @nombre_completo = @nom OUTPUT,
    @id_departamento = @dep OUTPUT;
```

| `@resultado` | Significa |
|---|---|
| `APP_INVALIDA` | El `codigo_app`/`api_key` no coincide con nada activo en `CORE_Aplicaciones`, o la IP no está en `ip_permitidas` |
| `NO_ENCONTRADO` | No existe ninguna cuenta con esa cédula |
| `INACTIVO` | La cuenta existe pero `estado = 0` |
| `BLOQUEADO` | Superó los intentos fallidos permitidos, sigue dentro de la ventana de bloqueo escalado |
| `OK` | Cuenta operativa — `@hash_contrasena` viene poblado, andá al paso 2 |

### Paso 2: verificar la contraseña (en tu código, no en SQL)

**Punto crítico, no obvio** — ver sección 9: el hash guardado en
`CORE_Usuarios` no es bcrypt directo sobre la contraseña. Es
`peppered:` + `bcrypt(HMAC-SHA256(SHA-256(contraseña_real), PEPPER))`. El
paso `SHA-256(contraseña_real)` es el mismo que hace el navegador con
`js/password-hash.js` antes de que cualquier formulario del sistema llegue
al servidor (ver `INDICACIONES/GUIA_SEGURIDAD_CONTRASENAS.html`). Si tu
módulo compara directo contra la contraseña cruda, **el login SIEMPRE va a
fallar** para cualquier cuenta real del sistema. Usá `libs/SsoClient.php`
(sección 5) para no tener que replicar esto a mano — ya lo hace bien.

### `dbo.sp_SSO_ConfirmarLogin` — Paso 3a: emitir sesión

```sql
EXEC dbo.sp_SSO_ConfirmarLogin
    @codigo_app   = N'TU_MODULO',
    @api_key      = N'<tu api key>',
    @id_usuario   = @id,                 -- el que devolvió sp_SSO_Login
    @ip_address   = N'127.0.0.1',
    @user_agent   = N'MiApp/1.0',
    @horas_vida   = NULL,                -- NULL = usa CORE_Config.SESSION_HOURS, o 8h por defecto
    @token        = @tok OUTPUT,         -- 128 caracteres hex, aleatorio criptográfico
    @fecha_expira = @exp OUTPUT;
```

Solo llamar acá **después** de que `password_verify()` haya dado `true` en
tu código. Este paso además resetea `intentos_fallidos` de la cuenta y dejan
auditoría (`CORE_Auditoria`, operación `LOGIN`, detalle `Login vía
SSO:TU_MODULO`).

### `dbo.sp_SSO_RegistrarFallo` — Paso 3b: contraseña incorrecta

```sql
EXEC dbo.sp_SSO_RegistrarFallo
    @codigo_app     = N'TU_MODULO',
    @api_key        = N'<tu api key>',
    @nombre_usuario = N'1234567777',
    @ip_address     = N'127.0.0.1';
```

Delega en `sp_RegistrarFalloLogin`, el mismo mecanismo de bloqueo escalado
del login nativo del portal (3 fallos bloquea; 5min→15min→30min→24h — ver
`[[login_bloqueo_escalado]]` en la memoria del proyecto). **Llamar siempre**
que `password_verify()` dé `false`, para que el bloqueo funcione igual que
en el portal.

### `dbo.sp_SSO_ValidarToken` — peticiones siguientes

```sql
EXEC dbo.sp_SSO_ValidarToken
    @codigo_app      = N'TU_MODULO',
    @api_key         = N'<tu api key>',
    @token           = N'<token guardado>',
    @ip_address      = N'127.0.0.1',
    @resultado       = @res OUTPUT,   -- APP_INVALIDA | TOKEN_INVALIDO | EXPIRADO | OK
    @id_usuario      = @id  OUTPUT,
    @nombre_usuario  = @nu  OUTPUT,
    @nombre_completo = @nc  OUTPUT,
    @nivel_jerarquia = @niv OUTPUT,
    @id_departamento = @dep OUTPUT;
```

Si `@resultado = 'EXPIRADO'`, el procedimiento además marca el token como
revocado (`estado = 0`) — no hace falta que tu módulo llame a logout aparte
en ese caso. Cada validación exitosa actualiza
`CORE_Sesiones.fecha_ultima_actividad`.

### `dbo.sp_SSO_Logout` — cerrar sesión

```sql
EXEC dbo.sp_SSO_Logout
    @codigo_app = N'TU_MODULO',
    @api_key    = N'<tu api key>',
    @token      = N'<token a revocar>',
    @ip_address = N'127.0.0.1';
```

---

## 4. Dar de alta tu módulo (una sola vez, lo hace un administrador de BD)

`sp_SSO_RegistrarApp` queda **fuera** del rol `rol_sso_modulos` a propósito
— ningún módulo puede auto-registrarse ni rotar su propia clave. Lo ejecuta
alguien con acceso de administrador a `PORTAL_APM`:

```sql
EXEC dbo.sp_SSO_RegistrarApp
    @codigo        = N'TU_MODULO',            -- identificador corto, único, mayúsculas
    @nombre        = N'Nombre descriptivo',
    @api_key       = N'<64+ caracteres aleatorios — generar con random_bytes(32) en hex, por ejemplo>',
    @ip_permitidas = NULL,                     -- opcional, ver nota abajo
    @fecha_expira  = NULL,                     -- o una fecha si la credencial debe caducar
    @creado_por    = N'quien la registra';
```

> **`@ip_permitidas` — dejalo en `NULL` por defecto.** Confirmado con la
> Autoridad Portuaria de Manta: la red interna asigna IP por DHCP en la
> gran mayoría de equipos, así que forzar una lista blanca rompería el
> acceso apenas el DHCP reasigne una IP distinta. Usar esta lista **solo**
> para el puñado de casos reales con IP fija por seguridad o necesidad
> operativa (un servidor, un puesto de vigilancia con equipo dedicado,
> etc.) — nunca como práctica general para todos los módulos.

La `api_key` se guarda como `HASHBYTES('SHA2_256', ...)` — **nunca en
claro**. Guardá la clave real vos mismo (variable de entorno, secreto de
despliegue) porque no se puede recuperar después de este paso, solo
rotar (correr `sp_SSO_RegistrarApp` de nuevo con el mismo `@codigo`).

**Generar una api_key fuerte:**
```bash
php -r "echo bin2hex(random_bytes(32));"    # 64 caracteres hex
```

Después, dar permiso de ejecución al login SQL de tu módulo:
```sql
CREATE LOGIN mod_tu_modulo WITH PASSWORD = '<contraseña-fuerte>';
USE PORTAL_APM;
CREATE USER mod_tu_modulo FOR LOGIN mod_tu_modulo;
ALTER ROLE rol_sso_modulos ADD MEMBER mod_tu_modulo;
```

`rol_sso_modulos` tiene **únicamente** `EXECUTE` sobre los 5 procedimientos
de login/validación — sin `SELECT` directo a ninguna tabla. Mínimo
privilegio real, no solo de nombre.

---

## 5. Usarlo desde PHP con acceso SQL directo — `libs/SsoClient.php`

Ya existe un cliente PHP hecho, probado, standalone (no depende de ninguna
otra clase del portal — solo necesita `sqlsrv`):

```php
require 'libs/SsoClient.php';

$sso = new SsoClient([
    'server'  => '.\\VICTUS',                    // o el que corresponda
    'app'     => 'TU_MODULO',
    'api_key' => getenv('SSO_API_KEY'),           // nunca hardcodeado
]);

// Login — $password es la contraseña REAL tal cual la escribió el usuario,
// el cliente hace el resto (SHA-256 + pepper + bcrypt) internamente.
$r = $sso->login('1234567777', $passwordDelFormulario);

if ($r['ok']) {
    $_SESSION['sso_token'] = $r['token'];
    $_SESSION['usuario']   = $r['usuario'];
} else {
    // $r['resultado'] ∈ APP_INVALIDA | NO_ENCONTRADO | INACTIVO | BLOQUEADO | PASSWORD | ERROR
    // $r['error'] ya es un mensaje genérico listo para mostrar
}

// En cada petición siguiente:
$v = $sso->validate($_SESSION['sso_token'] ?? '');
if (!$v['ok']) {
    // token vencido/inválido → mandar a loguearse de nuevo
}

// Logout:
$sso->logout($_SESSION['sso_token']);
```

No hace falta escribir SQL a mano ni preocuparse por el esquema de hash —
`SsoClient` ya lo resuelve.

---

## 6. Usarlo por HTTP — sin acceso SQL directo

Para módulos que **no** pueden conectarse a `PORTAL_APM` (otro proveedor de
hosting, app móvil, servicio en otro lenguaje). Mismos procedimientos, por
detrás de `ApiSsoController`:

```
POST /api/sso/login
Content-Type: application/json
{"app":"TU_MODULO","api_key":"...","username":"1234567777","password":"..."}

→ 200 {"ok":true,"token":"...","expira":"...","usuario":{...}}
→ 401 {"ok":false,"resultado":"PASSWORD","error":"Usuario o contraseña incorrectos."}
```

```
POST /api/sso/validate
{"app":"TU_MODULO","api_key":"...","token":"..."}

→ 200 {"ok":true,"resultado":"OK","usuario":{...}}
→ 401 {"ok":false,"resultado":"TOKEN_INVALIDO"}
```

```
POST /api/sso/logout
{"app":"TU_MODULO","api_key":"...","token":"..."}

→ 200 {"ok":true}
```

Ejemplo real con `curl` (probado, ver sección 9):
```bash
curl -X POST "https://TU-DOMINIO/portal_apm/api/sso/login" \
  -H "Content-Type: application/json" \
  -d '{"app":"TU_MODULO","api_key":"...","username":"1234567777","password":"..."}'
```

**Usar siempre HTTPS en producción** — la `api_key` y la contraseña viajan
en el cuerpo de la petición. El portal no fuerza esto por código; es
responsabilidad del despliegue (certificado + redirect HTTP→HTTPS en
Apache/IIS).

---

## 7. Manejo de errores — checklist para tu módulo

- **Nunca** mostrar mensajes distintos para "usuario no existe" vs
  "contraseña incorrecta" al usuario final — ambos casos ya vienen con
  mensaje genérico ("Usuario o contraseña incorrectos.") en `SsoClient`,
  respetalo si construís tu propia UI.
- Tratar `APP_INVALIDA` como error de **configuración de tu módulo**
  (api_key mal copiada, IP no autorizada, credencial expirada) — no
  mostrarlo nunca al usuario final, es un problema tuyo, no de él.
  Loguealo para vos.
- `BLOQUEADO` — mostrar que la cuenta está bloqueada temporalmente, sin
  decir por cuánto exactamente si no lo necesitás (el portal nativo sí
  calcula el tiempo restante real, ver `[[login_bloqueo_escalado]]`; tu
  módulo puede simplemente reintentar más tarde).
- `EXPIRADO` en `validate()` — no es un error de tu módulo, es esperado
  (la sesión venció). Redirigir a login sin alarmar.
- Guardar el `token`, nunca la contraseña ni el hash, en tu sesión local.

---

## 8. Qué protege este mecanismo (y qué no)

**Protege:**
- La contraseña real nunca se guarda ni compara dentro de SQL Server.
- Cada módulo tiene su propia credencial (`codigo` + `api_key`), revocable
  individualmente sin afectar a los demás.
- Lista blanca de IP **opcional** por módulo (no forzada — la red de la
  Autoridad Portuaria de Manta es mayormente DHCP, así que el default
  correcto es `NULL`; se activa solo caso por caso donde de verdad haya
  una IP fija por seguridad o necesidad operativa).
- Mismo control de bloqueo por intentos fallidos que el login nativo del
  portal — un atacante no puede fuerza-brutear una cuenta pasando por el
  SSO para esquivar el bloqueo del portal.
- Todo queda en `CORE_Auditoria` (operación `SSO_LOGIN`/`SSO_CONFIRM`, o
  `LOGIN` con detalle `Login vía SSO:<app>`).
- Tokens aleatorios criptográficos (`CRYPT_GEN_RANDOM`, 64 bytes → 128 hex
  chars), con expiración — no son JWT ni tienen payload adivinable.

**No protege (responsabilidad de quien despliega el módulo):**
- Que la conexión sea HTTPS — si se usa el endpoint HTTP sin TLS, la
  contraseña viaja en claro por la red.
- Que la `api_key` se guarde de forma segura del lado del módulo (variable
  de entorno, no en el código fuente ni en el repositorio).
- Rotar la `api_key` periódicamente — `sp_SSO_RegistrarApp` lo permite,
  pero nadie lo hace automáticamente.

---

## 9. Verificado en vivo (2026-08-26) — y un bug real que se encontró y corrigió

Antes de documentar esto como "funcional", se probó de punta a punta con
una app de prueba desechable (`QATEST_SSO`, creada, usada y borrada de
`CORE_Aplicaciones` — no queda rastro):

1. **`SsoClient` vía SQL directo**: login con clave correcta → `ok:true` +
   token real. Login con clave incorrecta → rechazado. Login con
   `api_key` inválida → `APP_INVALIDA`. `validate()` del token recién
   emitido → `ok:true`. `logout()` → revocado. `validate()` del token ya
   cerrado → `TOKEN_INVALIDO`. Los 6 casos, correctos.
2. **Los 3 endpoints HTTP** (`/api/sso/login`, `/validate`, `/logout`)
   probados con `curl` contra el servidor real — mismos resultados.

**Bug real encontrado y corregido en el camino**: `libs/SsoClient.php`
comparaba la contraseña recibida directo contra el pepper (`hash_hmac`
sobre la contraseña cruda), sin aplicar primero el paso de `SHA-256` que
el navegador SÍ aplica en todos los formularios del sistema desde la
sesión de trabajo del 2026-08-23/25 (ver
`INDICACIONES/GUIA_SEGURIDAD_CONTRASENAS.html`). Como consecuencia,
`SsoClient::login()` **fallaba para el 100% de las cuentas reales** del
sistema — nunca había sido probado end-to-end antes de hoy. Corregido:
ahora `verifyPassword()` aplica `SHA-256(contraseña)` antes de la
combinación con el pepper, igual que cualquier login web. El contrato
externo de `SsoClient::login($usuario, $contraseñaReal)` no cambió — sigue
recibiendo la contraseña tal cual, el ajuste fue interno.

---

## 10. La otra opción: puente de sesión (Patrón B) — para apps embebidas

Si tu módulo vive **dentro de este mismo proyecto** (`apps/tu_modulo/`,
mismo servidor, mismo dominio/subcarpeta), lo que usan hoy Talento Humano,
Control de Bienes y Bitácoras **no es este SSO** — es más simple: leen
directo la sesión PHP del portal (`$_SESSION['user_id']`,
`$_SESSION['nivel_jerarquia']`, etc., ya pobladas por el login nativo del
portal) y arman su propia sesión de módulo a partir de ahí, sin pedir
usuario/contraseña de nuevo. No hay token, no hay tabla `CORE_Aplicaciones`
de por medio.

Usar **SSO por procedimientos** en cambio de esto para un módulo embebido
sería innecesario (ya comparte la sesión, no hace falta credencial de
aplicación aparte) — reservá esta guía para el caso real: un módulo que NO
comparte sesión PHP con el portal.

---

*Portal APM — documentación técnica interna ·
`INDICACIONES/GUIA_SSO_LOGIN_ENTRE_MODULOS.md`*
