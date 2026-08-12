# Propuesta de seguridad — 2FA, endurecimiento de sesiones y recuperación de contraseña

**Fecha:** 2026-08-06
**Estado:** Propuesta aprobada en conversación, pendiente de implementar.
**Alcance pedido por el usuario:** 2FA, seguridad de tokens/sesiones, y un flujo de
recuperación de contraseña por enlace, "encriptado y seguro solo para el que lo recibe".

---

## 1. Diagnóstico del estado actual (verificado en código, no supuesto)

| Área | Estado real hoy |
|---|---|
| 2FA | `CORE_Usuarios.requiere_mfa` (bit) y `mfa_secreto` (nvarchar32, tamaño de un secreto TOTP) existen en el schema pero **`sp_Login` nunca los lee** — cero verificación de segundo factor en ningún punto del sistema. |
| Sesiones (`CORE_Sesiones`) | Se inserta un token de 64 bytes al hacer login (`UsuarioModel::authenticate()`), con IP, user-agent y expiración. Pero `Controller::requireAuth()` — el guardián real de cada página — **solo valida `$_SESSION['user_id']` de PHP** y un timeout de inactividad local (`SESSION_TIMEOUT`, 30 min). Nunca vuelve a consultar `CORE_Sesiones`. Consecuencia real: `sp_Logout`/revocar una sesión en BD **no cierra una sesión ya abierta en el navegador** — sigue viva hasta que expire su cookie de PHP por sí sola. |
| Bloqueo por intentos fallidos | **Sí funciona.** `sp_Login` devuelve `BLOQUEADO` y `UsuarioModel::authenticate()` llama a `sp_RegistrarFalloLogin` en cada fallo — el mecanismo de `intentos_fallidos`/`fecha_bloqueo`/`minutos_bloqueo` está activo en producción. |
| Historial de contraseñas (`CORE_Contrasenas_Hist`) | `sp_CambiarContrasena` **sí archiva** cada hash anterior (mantiene las últimas 5), pero **nunca los usa para rechazar una contraseña reutilizada** — solo se guarda, nunca se compara contra la nueva. |
| Envío de correo | **No existe ningún mecanismo** en todo el proyecto: sin PHPMailer, sin configuración SMTP, sin `mail()`. Confirmado por búsqueda exhaustiva en `modules/`, `apps/talento_humano`, `apps/control_bienes`, `apps/bitacoras`. |
| Correos de empleados en TH | `th_empleados.correo_institucional`: 365 de 620 empleados (59%) tienen un valor con forma de correo; 252 no tienen nada. No se puede verificar desde aquí si esos 365 son buzones reales y entregables. |
| Recuperación de contraseña | No existe absolutamente ningún mecanismo hoy — ni self-service ni asistido por admin. |
| `CORE_Config` | Ya existe (tabla `modulo`/`clave`/`valor`/`tipo`, con `UNIQUE(modulo,clave)`) y ya la usa `sp_Login` para `LOGIN_MAX_INTENTOS`. Es el lugar natural para settings editables sin tocar código. |
| `/cambiar-contrasena` | Ya existe (`AuthController::cambiarContrasena()`), reusa `sp_CambiarContrasena`. El flujo de reset por enlace debe reutilizar esta misma ruta/lógica de cambio de clave, no duplicarla. |

Decisiones ya confirmadas con el usuario (ver conversación):
- 2FA: **TOTP con app autenticadora** (Google Authenticator / Microsoft Authenticator / Authy — gratis, sin pagos, sin infraestructura de correo). 2FA por correo queda diseñado pero **apagado en la interfaz** hasta que exista una cuenta SMTP real (no confirmada disponible hoy).
- Alcance de 2FA: **opt-in por usuario + forzable por admin** (columna `requiere_mfa` ya existe para esto).
- Recuperación de contraseña: **automática cuando haya SMTP real**; mientras tanto, el enlace de un solo uso lo genera el sistema y queda visible en un panel admin para que el admin lo relaye a mano (mismo patrón ya aceptado hoy con las contraseñas temporales de "crear usuario desde TH").
- Vigencia del token de reset: **24 horas por defecto**, pero **editable por el admin** (vía `CORE_Config`), no hardcodeada.

---

## 2. Diseño — 2FA por TOTP

### 2.1 Por qué TOTP y no otra cosa
- Gratis, estándar abierto (RFC 6238), sin dependencia de un tercero ni de correo/SMS.
- Compatible con cualquier app autenticadora gratuita que el empleado ya pueda tener instalada.
- El schema (`mfa_secreto` nvarchar(32)) ya está dimensionado exactamente para un secreto Base32 de 20 bytes — sugiere que esta fue la intención original del diseño de la BD.
- Sin librerías externas: el algoritmo (HMAC-SHA1 truncado, ventana de 30s) es ~80 líneas de PHP puro. Sigue el mismo patrón que ya usa el proyecto para `libs/fpdf`, `libs/XlsxWriter.php` — vendorizado a mano, cero Composer.

### 2.2 Componentes nuevos
- `libs/Totp.php` — clase estática: `generateSecret(): string` (Base32, 20 bytes), `getOtpAuthUri(secreto, cuenta, emisor): string`, `verify(secreto, codigo, ventana=1): bool` (tolera ±1 paso de 30s por desfase de reloj).
- `/perfil/2fa` (nueva ruta, `AuthController::show2fa()` / `activar2fa()` / `confirmar2fa()` / `desactivar2fa()`):
  - Genera secreto → lo guarda en `mfa_secreto` con `requiere_mfa` aún en 0.
  - Muestra el secreto en texto (agrupado en bloques de 4, fácil de transcribir) + el URI `otpauth://totp/PORTAL_APM:{cedula}?secret=...&issuer=PORTAL_APM` como enlace (las apps autenticadoras en el mismo teléfono lo abren directo; en otro dispositivo se copia el secreto a mano — no se genera imagen QR en esta primera versión, para no meter una librería extra solo para eso).
  - Pide un código de 6 dígitos para confirmar antes de fijar `requiere_mfa=1` — evita que alguien quede bloqueado por haber copiado mal el secreto.
  - Botón "Desactivar" (pide contraseña actual, limpia `mfa_secreto` y `requiere_mfa`).
- Login (`AuthController::login()`): tras `authenticate()` exitoso, si `requiere_mfa=1`, **no crear la sesión real todavía** — guardar `$_SESSION['_mfa_pendiente'] = id_usuario` (con marca de tiempo, vence en 5 minutos) y redirigir a `/login/verificar-2fa`. Esa pantalla pide el código de 6 dígitos; al validar con `Totp::verify()`, recién ahí se llama a `SessionHelper::login()` y se crea la fila en `CORE_Sesiones`.
- Admin (`/admin/usuarios/{id}/editar`): un checkbox "Forzar verificación en dos pasos" que escribe `requiere_mfa` directamente — si el usuario aún no tiene `mfa_secreto`, el próximo login lo manda a configurarlo antes de continuar.

### 2.3 2FA por correo (diseñado, no activo)
Mismo patrón (`_mfa_pendiente`) pero generando un código de 6 dígitos random, guardado con hash + expiración corta (ej. 10 min) en una tabla pequeña `CORE_Mfa_Codigos_Correo`, y enviado por el mismo `Mailer` abstracto del punto 4. La opción "verificación por correo" en `/perfil/2fa` queda **oculta** mientras `Mailer::disponible()` sea `false` (ver 4.3) — no tiene sentido ofrecérsela a un empleado real si hoy nadie recibiría el código.

---

## 3. Diseño — Endurecer sesiones/tokens

### 3.1 El problema concreto
`Controller::requireAuth()` (en `core/Controller.php`) hoy es:
```php
if (empty($_SESSION['user_id'])) { ... redirigir a /login ... }
// timeout de inactividad local
```
Nunca toca `CORE_Sesiones`. Por lo tanto:
- Revocar una sesión desde BD (logout forzado por admin, o el propio flujo de reset de contraseña) no tiene ningún efecto sobre una sesión de navegador ya abierta.
- No hay forma real de "cerrar todas mis sesiones" ni de invalidar sesiones viejas al cambiar la contraseña.

### 3.2 Fix propuesto
- `requireAuth()` valida `$_SESSION['session_token']` contra `CORE_Sesiones` (`estado='ACTIVA'` y `fecha_expira > GETDATE()`), pero **no en cada request** — cada ~5 minutos de actividad (guardando la última verificación en `$_SESSION['_token_check_at']`), para no sumar una consulta a BD en cada carga de página.
- Si el token ya no es válido en BD → `destroySession()` + redirect a `/login` (igual que el timeout de inactividad ya maneja).
- Nuevo método `UsuarioModel::revokeAllSessions(int $idUsuario, ?string $exceptToken = null)` — marca `estado='REVOCADA'` en `CORE_Sesiones` para todas las sesiones del usuario. Se usa en:
  - El flujo de reset de contraseña (punto 4), tras fijar la nueva clave.
  - Un botón nuevo "Cerrar todas mis otras sesiones" en `/perfil`.
- `sp_CambiarContrasena` — sin cambios de por sí, pero el PHP que lo rodea (en `cambiarContrasena()` y en el nuevo flujo de reset) debe llamar a `revokeAllSessions()` justo después.

Este es un fix de comportamiento, no requiere UI nueva por sí solo (salvo el botón opcional de "cerrar otras sesiones").

---

## 4. Diseño — Recuperación de contraseña por enlace

### 4.1 Tabla nueva
```sql
CREATE TABLE dbo.CORE_Reset_Password_Tokens (
    id_token        INT IDENTITY(1,1) PRIMARY KEY,
    id_usuario      INT NOT NULL REFERENCES CORE_Usuarios(id_usuario),
    token_hash      NVARCHAR(64) NOT NULL UNIQUE,   -- SHA-256 hex del token real
    fecha_creacion  DATETIME2(7) NOT NULL DEFAULT GETDATE(),
    fecha_expira    DATETIME2(7) NOT NULL,
    usado           BIT NOT NULL DEFAULT 0,
    fecha_uso       DATETIME2(7) NULL,
    ip_solicitante  VARCHAR(45) NULL
);
CREATE INDEX IX_ResetTok_Usuario ON dbo.CORE_Reset_Password_Tokens(id_usuario, usado);
```
El token en crudo (32 bytes aleatorios vía `SecurityHelper::generateToken(32)`, 64 caracteres hex) **nunca se guarda** — solo su hash SHA-256. El token real solo existe en la URL que recibe el usuario. Esto es exactamente el mismo criterio que ya usa el proyecto para contraseñas (bcrypt) y tokens de sesión — nada nuevo conceptualmente, mismo estándar aplicado a un tercer tipo de secreto.

### 4.2 Configuración editable (`CORE_Config`)
```sql
INSERT INTO CORE_Config (modulo, clave, valor, tipo, descripcion, estado)
VALUES ('CORE', 'RESET_TOKEN_HORAS_VALIDEZ', '24', 'int',
        'Horas que un enlace de recuperación de contraseña permanece válido antes de expirar', 1);
```
Nueva pantalla admin `/admin/seguridad` (extensión mínima, no un editor genérico de `CORE_Config`): expone únicamente `RESET_TOKEN_HORAS_VALIDEZ` y `LOGIN_MAX_INTENTOS` (esta última ya existe y ya la lee `sp_Login`, pero hoy no tiene ninguna UI para cambiarla — se aprovecha la misma pantalla).

### 4.3 El "Mailer" abstracto
```php
interface Mailer {
    public function disponible(): bool;
    public function enviar(string $destino, string $asunto, string $cuerpoHtml): bool;
}
```
- `NullMailer` (activo hoy): `disponible()` devuelve `false`, `enviar()` no hace nada — cualquier código que dependa de correo automáticamente sabe que debe usar el camino de respaldo (panel admin).
- `SmtpMailer` (implementación real, PHPMailer o `Swift`-equivalente vendorizado a mano en `libs/`, sin Composer): se activa el día que exista una cuenta SMTP real, con una constante en `config/app.php` (host/usuario/clave de aplicación). `disponible()` devuelve `true` si esas constantes están definidas.
- Todo el código de reset/2FA-por-correo llama siempre a `Mailer::instancia()` — nunca a una implementación concreta. Cambiar de `NullMailer` a `SmtpMailer` es una sola línea de configuración, cero cambios en el flujo.

### 4.4 Flujo de recuperación
1. **`GET /recuperar`** (pública) — formulario: "Ingresa tu cédula".
2. **`POST /recuperar`**:
   - Busca el usuario por cédula. Si no existe, **igual muestra el mismo mensaje neutro** ("Si la cuenta existe, se generó un enlace de recuperación") — evita que alguien pueda usar este formulario para confirmar qué cédulas tienen cuenta.
   - Si existe: invalida cualquier token pendiente sin usar de ese usuario (evita acumular tokens vivos), genera uno nuevo, `fecha_expira = GETDATE() + RESET_TOKEN_HORAS_VALIDEZ horas`.
   - Intenta `Mailer::enviar()`. Si `disponible()` es `false` (caso de hoy): no se le muestra el enlace al usuario en pantalla (seguiría siendo una fuga de credenciales-equivalente a cualquiera que use el formulario) — el enlace queda **solo visible para el admin** en el panel del punto 4.5.
3. **`GET /recuperar/{token}`** — hashea el token recibido, busca en `CORE_Reset_Password_Tokens` por `token_hash` con `usado=0 AND fecha_expira > GETDATE()`. Si no matchea: página de "enlace inválido o vencido", con link para pedir uno nuevo. Si matchea: formulario "Crea tu nueva contraseña".
4. **`POST /recuperar/{token}`**: valida de nuevo (doble-check, evita condiciones de carrera de doble submit), aplica las mismas reglas de fuerza que `cambiarContrasena()` (mínimo 8 caracteres) **más** el chequeo nuevo de no-reutilización (punto 5), llama a `sp_CambiarContrasena`, marca el token `usado=1` + `fecha_uso`, llama a `revokeAllSessions()`, y redirige a `/login` con mensaje de éxito.

### 4.5 Panel admin de recuperaciones pendientes
Nueva pantalla `/admin/seguridad/recuperaciones`: lista los tokens con `usado=0 AND fecha_expira > GETDATE()`, mostrando usuario, fecha de solicitud, vigencia restante, y un botón "Copiar enlace" (el enlace completo con el token en claro se construye del lado servidor solo en esta vista protegida por sesión de admin — el token en sí sigue sin persistirse en claro en BD, solo se reconstruye porque esta consulta ocurre en la misma request que generó el token). El admin copia y relaya el enlace al usuario por el medio que tengan disponible hoy (WhatsApp, en persona, etc.), exactamente el mismo patrón ya aceptado para las contraseñas temporales de "crear cuenta desde TH".

> Nota de diseño importante: como el token en claro no se guarda en BD, esta vista solo puede mostrar el enlace **en el momento en que se genera** (la misma request de `POST /recuperar`, si quien la ejecuta es un admin actuando en nombre del usuario) — no puede "recuperarse" después desde el listado una vez que la página se cierra. Para que el listado sea útil como bandeja de "pendientes por relayar", el admin debe ser quien inicie la solicitud desde `/admin/seguridad/recuperaciones` (botón "Generar enlace para este usuario"), viendo el enlace ahí mismo tras generarlo. Si el usuario la pide por sí mismo desde `/recuperar`, el enlace no queda visible para nadie más — coherente con no guardar tokens en claro, pero implica que **la vía self-service solo sirve de verdad una vez que haya SMTP real**; mientras tanto, la vía operativa es que el admin la genere directamente.

### 4.6 Seguridad del enlace
- 256 bits de entropía (32 bytes aleatorios de `random_bytes`), imposible de adivinar por fuerza bruta.
- Un solo uso — se marca `usado=1` inmediatamente al aplicarse el cambio, dentro de la misma transacción que el `UPDATE` de la contraseña.
- Expira (24h por defecto, configurable).
- Solicitar un nuevo token invalida el anterior — nunca hay más de un enlace vivo por usuario a la vez.
- Nunca se guarda en claro en BD, solo su hash.
- Revoca todas las sesiones activas del usuario al usarse — si alguien más tenía una sesión abierta con esa cuenta, queda fuera.

---

## 5. Extra encontrado de paso — bloquear reutilización de contraseñas

`CORE_Contrasenas_Hist` ya archiva las últimas 5 contraseñas (vía `sp_CambiarContrasena`) pero nunca se usa para rechazar una nueva contraseña igual a una anterior. Ya que se toca este código para el reset, agregar antes de llamar a `sp_CambiarContrasena`:
```php
$historial = $db->fetchAll($db->query(
    'SELECT TOP 5 hash_contrasena FROM CORE_Contrasenas_Hist WHERE id_usuario=? ORDER BY fecha_cambio DESC',
    [[$idUsuario, SQLSRV_PARAM_IN]]
));
foreach ($historial as $h) {
    if (SecurityHelper::verifyPassword($nueva, $h['hash_contrasena'])) {
        // rechazar: "No puedes reutilizar una contraseña reciente."
    }
}
```
Este chequeo se agrega tanto en `cambiarContrasena()` (ya existente) como en el nuevo `POST /recuperar/{token}` — mismo código, sin duplicar lógica (se extrae a un método privado compartido, ej. `UsuarioModel::esContrasenaReutilizada()`).

---

## 6. Resumen de archivos/tablas nuevos

**Base de datos:**
- `CORE_Reset_Password_Tokens` (tabla nueva)
- `CORE_Mfa_Codigos_Correo` (tabla nueva, para 2FA por correo — inactiva hasta tener SMTP)
- `CORE_Config`: filas nuevas `RESET_TOKEN_HORAS_VALIDEZ`

**PHP nuevo:**
- `libs/Totp.php`
- `libs/Mailer.php` (interfaz + `NullMailer` + `SmtpMailer`)
- Métodos nuevos en `AuthController`: `show2fa()`, `activar2fa()`, `confirmar2fa()`, `desactivar2fa()`, `verificar2faLogin()`, `showRecuperar()`, `recuperar()`, `showNuevaContrasena($token)`, `nuevaContrasena($token)`
- Métodos nuevos en `UsuarioModel`: `revokeAllSessions()`, `esContrasenaReutilizada()`
- Métodos nuevos en `AdminController`: pantalla `/admin/seguridad` y `/admin/seguridad/recuperaciones`

**Rutas nuevas** (`routes.php`):
```
GET/POST  /recuperar
GET/POST  /recuperar/{token}
GET       /perfil/2fa
POST      /perfil/2fa/activar
POST      /perfil/2fa/confirmar
POST      /perfil/2fa/desactivar
GET/POST  /login/verificar-2fa
GET       /admin/seguridad
POST      /admin/seguridad
GET       /admin/seguridad/recuperaciones
POST      /admin/seguridad/recuperaciones/generar
```

---

## 7. Fuera de alcance / decisiones explícitas

- **No** se implementa 2FA por SMS (tendría costo por mensaje, incompatible con el requisito explícito de "gratuito").
- **No** se genera imagen QR para el enrolamiento TOTP en esta primera versión (evita meter una librería solo para eso); entrada manual del secreto.
- **No** se construye un editor genérico de `CORE_Config` — solo se exponen las 2 claves relevantes a esta propuesta.
- El envío automático de correo (reset y 2FA por correo) **queda con el código listo pero no soportado en el flujo self-service completo** hasta que el usuario confirme una cuenta SMTP real disponible.
