<?php

final class Auth
{
    private const TOKEN_TTL = 28800; // 8 horas
    // Valor de reserva: solo se usa si el Portal APM no resuelve la cascada
    // (BD caída, etc.) -- en operación normal manda resolveInactividad(),
    // que lee la cascada centralizada usuario > módulo 'TALENTO_HUMANO' >
    // global desde PORTAL_APM (misma fn_InactividadSegundos/fn_InactividadAvisoSegundos
    // que usan el portal nativo, Control de Bienes y Bitácoras).
    private const IDLE_TTL_DEFAULT = 1800;   // 30 minutos sin actividad
    private const SESSION_NAME = 'APMSESSID';
    private const MAX_LOGIN_ATTEMPTS = 5;
    private const LOCK_MINUTES = 15;
    private const MFA_TTL = 300;
    private const MFA_MAX_ATTEMPTS = 5;
    private static ?array $currentUser = null;
    private static array $permissionCache = [];

    public static function sessionName(): string
    {
        return self::SESSION_NAME;
    }

    public static function configureSession(): void
    {
        $sessionPath = Config::privateDirectory() . DIRECTORY_SEPARATOR . 'sessions';
        if (!is_dir($sessionPath)) {
            mkdir($sessionPath, 0700, true);
        }
        session_save_path($sessionPath);
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.cookie_httponly', '1');
        session_name(self::SESSION_NAME);
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => self::isHttps(),
            'httponly' => true,
            'samesite' => 'Strict',
        ]);
    }

    public static function attempt(string $username, string $password): bool
    {
        $username = trim($username);
        $db = Conexion::conectar();
        $stmt = $db->prepare(
            "SELECT TOP 1 u.usuario_id, u.usuario, u.password_hash, u.nombre, u.correo,
                    u.rol_id, u.token_version, u.intentos_fallidos, u.bloqueado_hasta,
                    u.debe_cambiar_clave,u.mfa_habilitado,u.mfa_secreto_enc,u.mfa_ultimo_paso,r.nombre_rol
             FROM dbo.th_usuarios_sistema u
             JOIN dbo.th_roles r ON r.rol_id = u.rol_id
             WHERE u.usuario = :usuario AND u.estado = 1 AND r.estado = 1"
        );
        $stmt->execute([':usuario' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && !empty($user['bloqueado_hasta']) && strtotime((string)$user['bloqueado_hasta']) > time()) {
            self::audit($username, 'LOGIN_BLOQUEADO', 'Cuenta temporalmente bloqueada por intentos fallidos.');
            usleep(250000);
            return false;
        }

        if (!$user || !password_verify($password, $user['password_hash'])) {
            // Puente de identidad Portal APM -> TH, mismo camino que
            // loginTrusted() usa para el super admin (admin_apm no tiene
            // empleado_id, no se resuelve por cédula) pero disponible
            // también desde el formulario de login PROPIO de TH -- igual
            // que Control de Bienes acepta la cédula del portal en su
            // login nativo. Solo para super admin (nivel_jerarquia>=4):
            // el resto de empleados sí necesita su cuenta TH real.
            $bridge = self::attemptPortalBridge($username, $password);
            if ($bridge !== null) {
                return $bridge; // true = sesión establecida, false = MFA pendiente (ver mfaPending())
            }
            if ($user) {
                $failed = $db->prepare(
                    'UPDATE dbo.th_usuarios_sistema
                     SET intentos_fallidos=intentos_fallidos+1,
                         bloqueado_hasta=CASE WHEN intentos_fallidos+1>=:maximos
                             THEN DATEADD(MINUTE,:minutos,GETDATE()) ELSE bloqueado_hasta END
                     WHERE usuario_id=:id'
                );
                $failed->execute([
                    ':maximos' => self::MAX_LOGIN_ATTEMPTS,
                    ':minutos' => self::LOCK_MINUTES,
                    ':id' => $user['usuario_id'],
                ]);
            }
            self::audit($username !== '' ? $username : 'ANONIMO', 'LOGIN_FALLIDO', 'Credenciales invalidas.');
            usleep(250000);
            return false;
        }

        unset($_SESSION['mfa_pending']);
        if ((bool)($user['mfa_habilitado'] ?? false) && !empty($user['mfa_secreto_enc'])) {
            session_regenerate_id(true);
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            $_SESSION['mfa_pending'] = [
                'user_id'=>(int)$user['usuario_id'],
                'username'=>(string)$user['usuario'],
                'expires'=>time()+self::MFA_TTL,
                'attempts'=>0,
            ];
            self::audit((string)$user['usuario'],'MFA_SOLICITADO','Credenciales válidas; se solicitó el segundo factor TOTP.');
            return false;
        }

        self::establishSession($user,false);
        return true;
    }

    /**
     * Login directo en TH con la cédula+clave del Portal APM, solo para
     * super admin (nivel_jerarquia>=4 -- mismo umbral que loginTrusted()).
     * No crea ni busca ninguna cuenta por cédula: resuelve siempre a la
     * cuenta fija 'admin_apm' (sin empleado_id), igual que la SSO. Evita
     * usleep/bloqueo por intentos propio de TH -- si la cédula ni siquiera
     * existe en el portal o el nivel no alcanza, simplemente no aplica
     * (null) y el flujo normal de "credenciales inválidas" sigue su curso.
     *
     * @return bool|null null = no aplica (seguir con el flujo normal de
     *   fallo), true = sesión TH establecida, false = MFA pendiente (igual
     *   convención que attempt(): revisar mfaPending() después).
     */
    private static function attemptPortalBridge(string $username, string $password): ?bool
    {
        if ($username === '' || $password === '') {
            return null;
        }
        try {
            $db = Conexion::conectar();
            $portal = $db->prepare(
                'SELECT hash_contrasena, nivel_jerarquia, estado
                 FROM PORTAL_APM.dbo.CORE_Usuarios WHERE cedula = :cedula'
            );
            $portal->execute([':cedula' => $username]);
            $row = $portal->fetch(PDO::FETCH_ASSOC);
            if (!$row || !(bool)$row['estado'] || (int)$row['nivel_jerarquia'] < 4) {
                return null;
            }
            if (!password_verify($password, (string)$row['hash_contrasena'])) {
                return null;
            }

            $stmt = $db->prepare(
                "SELECT TOP 1 u.usuario_id,u.usuario,u.password_hash,u.nombre,u.correo,u.rol_id,
                        u.token_version,u.intentos_fallidos,u.bloqueado_hasta,u.debe_cambiar_clave,
                        u.mfa_habilitado,u.mfa_secreto_enc,u.mfa_ultimo_paso,r.nombre_rol
                 FROM dbo.th_usuarios_sistema u JOIN dbo.th_roles r ON r.rol_id=u.rol_id
                 WHERE u.usuario='admin_apm' AND u.estado=1 AND r.estado=1"
            );
            $stmt->execute();
            $adminApm = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$adminApm) {
                return null;
            }

            unset($_SESSION['mfa_pending']);
            if ((bool)($adminApm['mfa_habilitado'] ?? false) && !empty($adminApm['mfa_secreto_enc'])) {
                session_regenerate_id(true);
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                $_SESSION['mfa_pending'] = [
                    'user_id'   => (int)$adminApm['usuario_id'],
                    'username'  => (string)$adminApm['usuario'],
                    'expires'   => time() + self::MFA_TTL,
                    'attempts'  => 0,
                ];
                self::audit((string)$adminApm['usuario'], 'MFA_SOLICITADO', "Credenciales válidas vía cédula del Portal APM ({$username}); se solicitó el segundo factor TOTP.");
                return false;
            }
            self::establishSession($adminApm, false, true, 'LOGIN_PUENTE_PORTAL', "Acceso vía cédula del Portal APM ({$username}).");
            // Puente inverso (botón "Volver al Portal APM") -- establishSession()
            // solo lo hace sola con el audit por defecto, no con un $auditAction
            // propio como el de arriba.
            self::syncPortalSession((int)$adminApm['usuario_id'], (string)$adminApm['usuario']);
            return true;
        } catch (Throwable) {
            return null;
        }
    }

    public static function user(): ?array
    {
        if (self::$currentUser !== null) {
            return self::$currentUser;
        }
        $token = $_SESSION['auth_token'] ?? null;
        if (!is_string($token) || $token === '') {
            return null;
        }
        try {
            $claims = self::decryptClaims($token);
            if (($claims['exp'] ?? 0) < time() || !hash_equals((string)($claims['fp'] ?? ''), self::fingerprint())) {
                self::clear();
                return null;
            }
            $lastActivity = (int)($_SESSION['last_activity'] ?? 0);
            if ($lastActivity > 0 && $lastActivity + self::resolveIdleTtlSegundos() < time()) {
                self::audit((string)($claims['usr']??'ANONIMO'),'SESSION_EXPIRADA','Sesión invalidada por inactividad en el servidor.');
                self::clear();
                return null;
            }

            $stmt = Conexion::conectar()->prepare(
                'SELECT u.usuario,u.nombre,u.rol_id,u.token_version,u.estado,r.nombre_rol,r.estado AS rol_estado
                 FROM dbo.th_usuarios_sistema u JOIN dbo.th_roles r ON r.rol_id=u.rol_id
                 WHERE u.usuario_id=:id'
            );
            $stmt->execute([':id' => (int)($claims['sub'] ?? 0)]);
            $actual = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$actual || !(bool)$actual['estado'] || !(bool)$actual['rol_estado']
                || (int)$actual['token_version'] !== (int)($claims['ver'] ?? -1)) {
                self::clear();
                return null;
            }
            $claims['usr'] = $actual['usuario'];
            $claims['name'] = $actual['nombre'];
            $claims['role_id'] = (int)$actual['rol_id'];
            $claims['role'] = $actual['nombre_rol'];
            $_SESSION['last_activity'] = time();
            self::$currentUser = $claims;
            return self::$currentUser;
        } catch (Throwable) {
            self::clear();
            return null;
        }
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function requireAuthentication(): void
    {
        if (!self::check()) {
            header('Location: ' . BASE_URL . '/login');
            exit;
        }
    }

    public static function can(string $module, string $action = 'visualizar'): bool
    {
        $user = self::user();
        if (!$user) {
            return false;
        }
        if ((int)($user['role_id'] ?? 0) === 1) {
            return true;
        }
        $columns = [
            'visualizar' => 'puede_visualizar',
            'crear' => 'puede_crear',
            'editar' => 'puede_editar',
            'eliminar' => 'puede_eliminar',
        ];
        if (!isset($columns[$action])) {
            return false;
        }
        $cacheKey = (int)$user['role_id'] . ':' . $module . ':' . $action;
        if (array_key_exists($cacheKey, self::$permissionCache)) {
            return self::$permissionCache[$cacheKey];
        }
        $sql = "SELECT COUNT_BIG(*) FROM dbo.th_permisos_rol p
                JOIN dbo.th_modulos m ON m.modulo_id=p.modulo_id
                WHERE p.rol_id=:rol AND m.codigo_modulo=:modulo AND p.{$columns[$action]}=1";
        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->execute([':rol' => (int)$user['role_id'], ':modulo' => $module]);
        return self::$permissionCache[$cacheKey] = ((int)$stmt->fetchColumn() > 0);
    }

    public static function requirePermission(string $module, string $action = 'visualizar'): void
    {
        if (self::can($module, $action)) {
            return;
        }
        self::audit(self::username(), 'ACCESO_DENEGADO', "Permiso denegado: {$module}.{$action}");
        http_response_code(403);
        exit('Acceso denegado. Su rol no tiene permisos para realizar esta operacion.');
    }

    public static function username(): string
    {
        return (string)(self::user()['usr'] ?? 'ANONIMO');
    }

    public static function clientIp(): string
    {
        return substr((string)($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'), 0, 45);
    }

    public static function csrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function validateCsrf(?string $token): bool
    {
        $known = $_SESSION['csrf_token'] ?? '';
        return is_string($token) && is_string($known) && $known !== '' && hash_equals($known, $token);
    }

    public static function requireCsrf(?string $token): void
    {
        if (self::validateCsrf($token)) {
            return;
        }
        // Antes: exit() con un texto plano sin login ni forma de volver
        // -- bug real reportado (usuario varado tras vencer la sesión a
        // mitad de un formulario). El token normalmente solo deja de
        // coincidir cuando la sesión de verdad ya venció (self::clear()
        // la vació) o la BD/GC se la llevó -- en ambos casos lo correcto
        // es mandar al login con el mismo aviso que expireSession(), no
        // dejar al usuario en una pantalla muerta.
        http_response_code(419);
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower((string)$_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => 'La sesión venció.', 'redirect' => BASE_URL . '/login?expired=1']);
            exit;
        }
        header('Location: ' . BASE_URL . '/login?expired=1');
        exit;
    }

    public static function mfaPending(): bool
    {
        $pending=$_SESSION['mfa_pending']??null;
        if(!is_array($pending) || (int)($pending['expires']??0)<time()){
            unset($_SESSION['mfa_pending']);
            return false;
        }
        return (int)($pending['user_id']??0)>0;
    }

    public static function verifyMfa(string $code): array
    {
        if(!self::mfaPending())return ['success'=>false,'message'=>'La verificación venció. Ingrese nuevamente.'];
        $pending=&$_SESSION['mfa_pending'];$code=preg_replace('/\D/','',$code)??'';
        $db=Conexion::conectar();
        $stmt=$db->prepare("SELECT u.usuario_id,u.usuario,u.nombre,u.correo,u.rol_id,u.token_version,u.debe_cambiar_clave,
            u.mfa_habilitado,u.mfa_secreto_enc,u.mfa_ultimo_paso,r.nombre_rol
            FROM dbo.th_usuarios_sistema u JOIN dbo.th_roles r ON r.rol_id=u.rol_id
            WHERE u.usuario_id=:id AND u.estado=1 AND r.estado=1");
        $stmt->execute([':id'=>(int)$pending['user_id']]);$user=$stmt->fetch(PDO::FETCH_ASSOC);
        $step=null;$valid=false;
        try {
            $valid=$user && (bool)$user['mfa_habilitado']
                && self::verifyTotp(self::decryptMfaSecret((string)$user['mfa_secreto_enc']),$code,$step);
        } catch (Throwable $error) {
            Conexion::registrarErrorLog($error,'MFA',false);
            self::audit((string)($pending['username']??'ANONIMO'),'MFA_ERROR','No fue posible validar el secreto cifrado del segundo factor.');
        }
        if(!$valid || $step===null || $step<=(int)($user['mfa_ultimo_paso']??-1)){
            $pending['attempts']=(int)($pending['attempts']??0)+1;
            self::audit((string)($pending['username']??'ANONIMO'),'MFA_FALLIDO','Código de segundo factor inválido o reutilizado.');
            if($pending['attempts']>=self::MFA_MAX_ATTEMPTS){unset($_SESSION['mfa_pending']);return ['success'=>false,'message'=>'Demasiados intentos. Ingrese nuevamente.'];}
            return ['success'=>false,'message'=>'Código de verificación incorrecto.'];
        }
        $db->prepare('UPDATE dbo.th_usuarios_sistema SET mfa_ultimo_paso=:paso WHERE usuario_id=:id')->execute([':paso'=>$step,':id'=>$user['usuario_id']]);
        unset($_SESSION['mfa_pending']);
        self::establishSession($user,true);
        return ['success'=>true,'message'=>'Segundo factor validado.'];
    }

    public static function cancelMfa(): void
    {
        if(self::mfaPending())self::audit((string)($_SESSION['mfa_pending']['username']??'ANONIMO'),'MFA_CANCELADO','El usuario canceló la verificación del segundo factor.');
        unset($_SESSION['mfa_pending']);
        session_regenerate_id(true);
    }

    public static function mfaStatus(): array
    {
        $user=self::user();if(!$user)return ['enabled'=>false,'activated_at'=>null];
        $s=Conexion::conectar()->prepare('SELECT mfa_habilitado,mfa_activado_en FROM dbo.th_usuarios_sistema WHERE usuario_id=:id');
        $s->execute([':id'=>(int)$user['sub']]);$r=$s->fetch(PDO::FETCH_ASSOC)?:[];
        return ['enabled'=>(bool)($r['mfa_habilitado']??false),'activated_at'=>$r['mfa_activado_en']??null];
    }

    public static function prepareMfaEnrollment(): array
    {
        $user=self::user();if(!$user)throw new RuntimeException('Sesión no disponible.');
        $secret=self::base32Encode(random_bytes(20));
        $issuer=rawurlencode('Portal Portuario APM');$account=rawurlencode((string)$user['usr']);
        $uri="otpauth://totp/{$issuer}:{$account}?secret={$secret}&issuer={$issuer}&digits=6&period=30";
        $_SESSION['mfa_enrollment']=['secret'=>$secret,'uri'=>$uri,'expires'=>time()+600];
        return ['secret'=>$secret,'uri'=>$uri];
    }

    public static function pendingEnrollment(): ?array
    {
        $e=$_SESSION['mfa_enrollment']??null;
        return is_array($e)&&(int)($e['expires']??0)>=time()?$e:null;
    }

    public static function activateMfa(string $code): array
    {
        $user=self::user();$enrollment=self::pendingEnrollment();
        if(!$user||!$enrollment)return ['success'=>false,'message'=>'La configuración venció. Genere una nueva clave.'];
        $step=null;if(!self::verifyTotp((string)$enrollment['secret'],$code,$step)||$step===null)return ['success'=>false,'message'=>'El código no coincide. Revise la hora del dispositivo.'];
        $enc=self::encryptMfaSecret((string)$enrollment['secret']);
        $s=Conexion::conectar()->prepare('UPDATE dbo.th_usuarios_sistema SET mfa_habilitado=1,mfa_secreto_enc=:secret,mfa_activado_en=SYSDATETIME(),mfa_ultimo_paso=:paso WHERE usuario_id=:id');
        $s->execute([':secret'=>$enc,':paso'=>$step,':id'=>(int)$user['sub']]);unset($_SESSION['mfa_enrollment']);
        self::audit((string)$user['usr'],'MFA_ACTIVADO','El usuario activó el segundo factor TOTP.');
        return ['success'=>true,'message'=>'Doble autenticación activada correctamente.'];
    }

    public static function disableMfa(string $password,string $code): array
    {
        $user=self::user();if(!$user)return ['success'=>false,'message'=>'Sesión no disponible.'];
        $s=Conexion::conectar()->prepare('SELECT password_hash,mfa_habilitado,mfa_secreto_enc FROM dbo.th_usuarios_sistema WHERE usuario_id=:id AND estado=1');
        $s->execute([':id'=>(int)$user['sub']]);$r=$s->fetch(PDO::FETCH_ASSOC);
        if(!$r||!password_verify($password,(string)$r['password_hash']))return ['success'=>false,'message'=>'La contraseña actual no es correcta.'];
        $step=null;if(!(bool)$r['mfa_habilitado']||!self::verifyTotp(self::decryptMfaSecret((string)$r['mfa_secreto_enc']),$code,$step))return ['success'=>false,'message'=>'El código de autenticación no es correcto.'];
        Conexion::conectar()->prepare('UPDATE dbo.th_usuarios_sistema SET mfa_habilitado=0,mfa_secreto_enc=NULL,mfa_activado_en=NULL,mfa_ultimo_paso=NULL WHERE usuario_id=:id')->execute([':id'=>(int)$user['sub']]);
        self::audit((string)$user['usr'],'MFA_DESACTIVADO','El usuario desactivó el segundo factor TOTP.');
        return ['success'=>true,'message'=>'Doble autenticación desactivada.'];
    }

    /**
     * Puente de identidad Portal APM -> Talento Humano (igual patrón que
     * Control de Bienes): el portal ya autenticó a esta persona; si existe
     * una cuenta correspondiente en th_usuarios_sistema se establece sesión
     * TH real (con su rol/permisos reales) SIN pedir clave ni MFA de nuevo
     * -- se confía en la autenticación ya hecha en el portal, igual que
     * Bienes. admin_apm no tiene empleado_id (cuenta sin expediente TH), por
     * eso el nivel jerárquico máximo del portal (super admin) se resuelve
     * directo a esa cuenta en vez de buscar coincidencia por cédula.
     */
    public static function loginTrusted(int $portalUserId, int $nivelJerarquiaPortal): bool
    {
        if (self::check()) {
            return true;
        }
        $db = Conexion::conectar();
        if ($nivelJerarquiaPortal >= 4) {
            $stmt = $db->prepare(
                "SELECT TOP 1 u.usuario_id,u.usuario,u.nombre,u.rol_id,u.token_version,u.debe_cambiar_clave,r.nombre_rol
                 FROM dbo.th_usuarios_sistema u JOIN dbo.th_roles r ON r.rol_id=u.rol_id
                 WHERE u.usuario='admin_apm' AND u.estado=1 AND r.estado=1"
            );
            $stmt->execute();
        } else {
            $stmt = $db->prepare(
                "SELECT TOP 1 u.usuario_id,u.usuario,u.nombre,u.rol_id,u.token_version,u.debe_cambiar_clave,r.nombre_rol
                 FROM dbo.th_usuarios_sistema u
                 JOIN dbo.th_roles r ON r.rol_id=u.rol_id
                 JOIN PORTAL_APM.dbo.CORE_Usuarios cu ON cu.id_empleado_th=u.empleado_id
                 WHERE cu.id_usuario=:pid AND u.estado=1 AND r.estado=1"
            );
            $stmt->execute([':pid' => $portalUserId]);
        }
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user) {
            return false;
        }
        self::establishSession(
            $user,
            false,
            true,
            'LOGIN_PUENTE_PORTAL',
            'Inicio de sesión mediante puente de identidad del Portal APM (sesión ya autenticada en el portal).'
        );
        $_SESSION['tiene_acceso_portal'] = true;
        $_SESSION['_portal_user_id_link'] = $portalUserId;
        return true;
    }

    public static function idleTtl(): int { return self::resolveIdleTtlSegundos(); }

    public static function idleAviso(): int { return self::resolveInactividad()[1]; }

    /**
     * Cascada de inactividad centralizada del Portal APM (usuario > módulo
     * 'TALENTO_HUMANO' > global), cacheada 5 min en esta misma sesión TH
     * -- igual patrón que usan el portal nativo, Control de Bienes y
     * Bitácoras (fn_InactividadSegundos/fn_InactividadAvisoSegundos en
     * PORTAL_APM, cross-DB). Reemplaza el IDLE_TTL fijo de 30 min: aquí
     * también aplican los overrides que el administrador configure en
     * /admin/inactividad, sea el usuario TH-only o puenteado desde el
     * portal (self::$_SESSION['_portal_user_id_link'], 0 si no hay vínculo
     * -- la cascada cae en el override de módulo o el global igual).
     */
    private static function resolveInactividad(): array
    {
        if (isset($_SESSION['_inactividad_segundos'], $_SESSION['_inactividad_aviso'])
            && (time() - ($_SESSION['_inactividad_resuelto_en'] ?? 0)) < 300) {
            return [(int)$_SESSION['_inactividad_segundos'], (int)$_SESSION['_inactividad_aviso']];
        }
        $segundos = self::IDLE_TTL_DEFAULT;
        $aviso = 60;
        try {
            $idPortal = (int)($_SESSION['_portal_user_id_link'] ?? 0);
            $stmt = Conexion::conectar()->prepare(
                'SELECT PORTAL_APM.dbo.fn_InactividadSegundos(?, ?) AS s, PORTAL_APM.dbo.fn_InactividadAvisoSegundos(?, ?) AS a'
            );
            $stmt->execute([$idPortal, 'TALENTO_HUMANO', $idPortal, 'TALENTO_HUMANO']);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $segundos = (int)$row['s'];
                $aviso = (int)$row['a'];
            }
        } catch (Throwable $e) {
            // BD/función no disponible: se conservan los valores por defecto.
        }
        $_SESSION['_inactividad_segundos'] = $segundos;
        $_SESSION['_inactividad_aviso'] = $aviso;
        $_SESSION['_inactividad_resuelto_en'] = time();
        return [$segundos, $aviso];
    }

    private static function resolveIdleTtlSegundos(): int
    {
        return self::resolveInactividad()[0];
    }

    /**
     * Invalida la caché de 5 min de esta sesión -- llamar justo después de
     * que ESTE mismo usuario (o su módulo/global) cambie la configuración
     * de inactividad, para que tome efecto sin esperar ni cerrar sesión
     * (mismo bug ya resuelto para el portal nativo, Bug #9 en memoria).
     */
    public static function invalidarCacheInactividad(): void
    {
        unset($_SESSION['_inactividad_segundos'], $_SESSION['_inactividad_aviso'], $_SESSION['_inactividad_resuelto_en']);
    }

    public static function renewSession(bool $manual=false): bool
    {
        $user=self::user();if(!$user)return false;
        $_SESSION['last_activity']=time();
        if($manual)self::audit((string)$user['usr'],'SESSION_RENOVADA','El usuario confirmó que continúa en el sistema.');
        return true;
    }

    public static function expireForInactivity(): void
    {
        $username='ANONIMO';
        try{$token=$_SESSION['auth_token']??'';if(is_string($token)&&$token!=='')$username=(string)(self::decryptClaims($token)['usr']??'ANONIMO');}catch(Throwable){}
        self::audit($username,'SESSION_EXPIRADA','Cierre automático por inactividad.');
        self::clear();session_regenerate_id(true);
    }

    public static function logout(): void
    {
        if (self::check()) {
            self::audit(self::username(), 'LOGOUT', 'Cierre de sesion.');
        }
        self::clear();
        session_regenerate_id(true);
    }

    public static function changePassword(string $currentPassword, string $newPassword): array
    {
        $user=self::user();
        if(!$user)return ['success'=>false,'message'=>'La sesión no está disponible.'];
        if(strlen($newPassword)<12 || !preg_match('/[A-Z]/',$newPassword) || !preg_match('/[a-z]/',$newPassword)
            || !preg_match('/\d/',$newPassword) || !preg_match('/[^A-Za-z0-9]/',$newPassword)) {
            return ['success'=>false,'message'=>'La nueva clave debe tener 12 caracteres, mayúscula, minúscula, número y símbolo.'];
        }
        $db=Conexion::conectar();$stmt=$db->prepare('SELECT password_hash FROM dbo.th_usuarios_sistema WHERE usuario_id=:id AND estado=1');
        $stmt->execute([':id'=>(int)$user['sub']]);$hash=$stmt->fetchColumn();
        if(!$hash || !password_verify($currentPassword,(string)$hash))return ['success'=>false,'message'=>'La clave actual no es correcta.'];
        if(password_verify($newPassword,(string)$hash))return ['success'=>false,'message'=>'La nueva clave debe ser diferente de la actual.'];
        $update=$db->prepare('UPDATE dbo.th_usuarios_sistema SET password_hash=:hash,debe_cambiar_clave=0,token_version=token_version+1 WHERE usuario_id=:id');
        $update->execute([':hash'=>password_hash($newPassword,PASSWORD_DEFAULT),':id'=>(int)$user['sub']]);
        $username=(string)$user['usr'];self::audit($username,'CAMBIAR_CLAVE','El usuario actualizó su clave.');
        $fresh=$db->prepare("SELECT u.usuario_id,u.usuario,u.nombre,u.correo,u.rol_id,u.token_version,u.debe_cambiar_clave,r.nombre_rol
            FROM dbo.th_usuarios_sistema u JOIN dbo.th_roles r ON r.rol_id=u.rol_id WHERE u.usuario_id=:id");
        $fresh->execute([':id'=>(int)$user['sub']]);$actual=$fresh->fetch(PDO::FETCH_ASSOC);
        if(!$actual){self::clear();return ['success'=>false,'message'=>'No fue posible renovar la sesión.'];}
        self::establishSession($actual,!empty($user['mfa']),false);
        return ['success'=>true,'message'=>'Clave actualizada correctamente.'];
    }

    private static function clear(): void
    {
        unset($_SESSION['auth_token'],$_SESSION['csrf_token'],$_SESSION['last_activity'],$_SESSION['mfa_pending'],$_SESSION['mfa_enrollment']);
        self::$currentUser = null;
        self::$permissionCache = [];
    }

    private static function establishSession(array $user,bool $mfaVerified,bool $auditLogin=true,?string $auditAction=null,?string $auditDescription=null): void
    {
        session_regenerate_id(true);$now=time();
        $claims=[
            'sub'=>(int)$user['usuario_id'],'usr'=>(string)$user['usuario'],'name'=>(string)$user['nombre'],
            'role_id'=>(int)$user['rol_id'],'role'=>(string)$user['nombre_rol'],'ver'=>(int)$user['token_version'],
            'password_change_required'=>(bool)$user['debe_cambiar_clave'],'mfa'=>$mfaVerified,
            'iat'=>$now,'exp'=>$now+self::TOKEN_TTL,'nonce'=>bin2hex(random_bytes(16)),'fp'=>self::fingerprint(),
        ];
        $_SESSION['auth_token']=self::encryptClaims($claims);$_SESSION['csrf_token']=bin2hex(random_bytes(32));$_SESSION['last_activity']=$now;
        self::$currentUser=$claims;self::$permissionCache=[];
        Conexion::conectar()->prepare('UPDATE dbo.th_usuarios_sistema SET ultimo_acceso=GETDATE(),intentos_fallidos=0,bloqueado_hasta=NULL WHERE usuario_id=:id')->execute([':id'=>(int)$user['usuario_id']]);
        if($auditLogin){
            if($auditAction!==null){
                self::audit((string)$user['usuario'],$auditAction,$auditDescription??'Inicio de sesión.');
            } else {
                if($mfaVerified)self::audit((string)$user['usuario'],'MFA_VALIDADO','Segundo factor TOTP validado correctamente.');
                self::audit((string)$user['usuario'],'LOGIN',$mfaVerified?'Inicio de sesión exitoso con doble autenticación.':'Inicio de sesión exitoso.');
                // Puente INVERSO (solo en login nativo TH, nunca en loginTrusted
                // -- ahí ya vinimos del portal -- ni en cambio de clave): si esta
                // cuenta TH corresponde a una identidad del portal, se puebla
                // también la sesión del portal (misma idea que Control de
                // Bienes) para que el botón "Volver al Portal APM" funcione y
                // se navegue ya autenticado del otro lado.
                self::syncPortalSession((int)$user['usuario_id'],(string)$user['usuario']);
            }
        }
    }

    /**
     * Ver comentario en establishSession(). Escribe en la sesión PROPIA del
     * portal (nombre/cookie distintos a los de esta app) sin perturbar la
     * sesión TH actualmente activa: cierra, cambia de sesión, escribe,
     * cierra, y vuelve a la sesión TH original con su mismo id.
     */
    private static function syncPortalSession(int $thUsuarioId, string $thUsuario): void
    {
        try {
            $db = Conexion::conectar();
            if ($thUsuario === 'admin_apm') {
                $stmt = $db->query(
                    "SELECT TOP 1 id_usuario FROM PORTAL_APM.dbo.CORE_Usuarios WHERE nivel_jerarquia>=4 AND estado=1 ORDER BY id_usuario"
                );
            } else {
                $stmt = $db->prepare(
                    "SELECT TOP 1 cu.id_usuario
                     FROM PORTAL_APM.dbo.CORE_Usuarios cu
                     JOIN dbo.th_usuarios_sistema u ON u.empleado_id=cu.id_empleado_th
                     WHERE u.usuario_id=:id AND cu.estado=1"
                );
                $stmt->execute([':id' => $thUsuarioId]);
            }
            $link = $stmt->fetch(PDO::FETCH_ASSOC);
            $_SESSION['tiene_acceso_portal'] = $link !== false;
            if (!$link) {
                return;
            }
            $_SESSION['_portal_user_id_link'] = (int)$link['id_usuario'];
            $stmt = $db->prepare(
                'SELECT id_usuario,nombre_usuario,nombre_completo,nivel_jerarquia,id_departamento,tema_preferido
                 FROM PORTAL_APM.dbo.CORE_Usuarios WHERE id_usuario=:id AND estado=1'
            );
            $stmt->execute([':id' => (int)$link['id_usuario']]);
            $portalUser = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$portalUser) {
                return;
            }

            $thSessionId = session_id();
            session_write_close();

            // session_save_path() es un ajuste global por request -- cambiar
            // solo el nombre de sesión NO alcanza, sin restaurar también la
            // ruta del portal esto escribiría (por error) dentro del
            // directorio privado de sesiones de TH. use_strict_mode=1 (fijado
            // por Auth::configureSession(), ini_set() es global) también se
            // apaga: un id de portal sin sesión en disco NO debe generar uno
            // nuevo por sorpresa acá. Ver mismo fix en apps/talento_humano/index.php.
            ini_set('session.use_strict_mode', '0');
            session_save_path(defined('PORTAL_SESSION_SAVE_PATH') ? PORTAL_SESSION_SAVE_PATH : session_save_path());
            $portalSessionName = defined('PORTAL_SESSION_NAME') ? PORTAL_SESSION_NAME : 'PHPSESSID';
            session_name($portalSessionName);
            // session_id('') NO alcanza (fija el id a la cadena vacía, un
            // valor inválido). Sin resolver el id a mano, session_start()
            // reutiliza el id de TH que se acaba de cerrar en vez de leer
            // la cookie real del portal -- si no hay cookie del portal
            // (cuenta TH-only navegando por primera vez), se genera una
            // nueva de una, lo cual es correcto: activa el botón "Volver
            // al Portal APM" desde cero.
            if (isset($_COOKIE[$portalSessionName]) && $_COOKIE[$portalSessionName] !== '') {
                session_id($_COOKIE[$portalSessionName]);
            }
            session_start();
            $_SESSION['user_id']         = (int)$portalUser['id_usuario'];
            $_SESSION['nombre_usuario']  = $portalUser['nombre_usuario'];
            $_SESSION['nombre_completo'] = $portalUser['nombre_completo'];
            $_SESSION['nivel_jerarquia'] = (int)$portalUser['nivel_jerarquia'];
            $_SESSION['id_departamento'] = $portalUser['id_departamento'];
            $_SESSION['tema']            = $portalUser['tema_preferido'] ?? 'light';
            $_SESSION['last_activity']   = time();
            if (empty($_SESSION['_csrf_token'])) {
                $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
            }
            session_write_close();

            self::configureSession(); // restaura nombre + ruta + cookie propios de TH
            session_id($thSessionId);
            session_start();
        } catch (Throwable $e) {
            Conexion::registrarErrorLog($e, 'Core', false);
        }
    }

    private static function encryptMfaSecret(string $secret): string
    {
        $iv=random_bytes(12);$tag='';$cipher=openssl_encrypt($secret,'aes-256-gcm',Config::tokenKey(),OPENSSL_RAW_DATA,$iv,$tag,'portal-portuario-apm-mfa-v1',16);
        if($cipher===false)throw new RuntimeException('No fue posible cifrar el secreto MFA.');
        return self::base64UrlEncode($iv.$tag.$cipher);
    }

    private static function decryptMfaSecret(string $encrypted): string
    {
        $raw=self::base64UrlDecode($encrypted);if(strlen($raw)<29)throw new RuntimeException('Secreto MFA inválido.');
        $plain=openssl_decrypt(substr($raw,28),'aes-256-gcm',Config::tokenKey(),OPENSSL_RAW_DATA,substr($raw,0,12),substr($raw,12,16),'portal-portuario-apm-mfa-v1');
        if($plain===false)throw new RuntimeException('No fue posible descifrar el secreto MFA.');return $plain;
    }

    private static function verifyTotp(string $secret,string $code,?int &$matchedStep=null): bool
    {
        $code=preg_replace('/\D/','',$code)??'';if(strlen($code)!==6)return false;$step=(int)floor(time()/30);
        for($offset=-1;$offset<=1;$offset++){
            $candidateStep=$step+$offset;if(hash_equals(self::totpAt($secret,$candidateStep),$code)){$matchedStep=$candidateStep;return true;}
        }
        return false;
    }

    private static function totpAt(string $secret,int $step): string
    {
        $key=self::base32Decode($secret);$counter=pack('N2',intdiv($step,0x100000000),$step%0x100000000);$hash=hash_hmac('sha1',$counter,$key,true);
        $offset=ord($hash[19])&0x0f;$binary=((ord($hash[$offset])&0x7f)<<24)|((ord($hash[$offset+1])&0xff)<<16)|((ord($hash[$offset+2])&0xff)<<8)|(ord($hash[$offset+3])&0xff);
        return str_pad((string)($binary%1000000),6,'0',STR_PAD_LEFT);
    }

    private static function base32Encode(string $data): string
    {
        $alphabet='ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';$bits='';foreach(str_split($data) as $char)$bits.=str_pad(decbin(ord($char)),8,'0',STR_PAD_LEFT);
        $out='';foreach(str_split($bits,5) as $chunk){$out.=$alphabet[bindec(str_pad($chunk,5,'0',STR_PAD_RIGHT))];}return $out;
    }

    private static function base32Decode(string $data): string
    {
        $alphabet='ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';$bits='';foreach(str_split(strtoupper(preg_replace('/[^A-Z2-7]/','',$data)??'')) as $char){$pos=strpos($alphabet,$char);if($pos===false)continue;$bits.=str_pad(decbin($pos),5,'0',STR_PAD_LEFT);}
        $out='';foreach(str_split($bits,8) as $chunk){if(strlen($chunk)===8)$out.=chr(bindec($chunk));}return $out;
    }

    private static function encryptClaims(array $claims): string
    {
        $iv = random_bytes(12);
        $tag = '';
        $ciphertext = openssl_encrypt(
            json_encode($claims, JSON_THROW_ON_ERROR),
            'aes-256-gcm',
            Config::tokenKey(),
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            'portal-portuario-apm-v1',
            16
        );
        if ($ciphertext === false) {
            throw new RuntimeException('No fue posible cifrar el token de sesion.');
        }
        return self::base64UrlEncode($iv . $tag . $ciphertext);
    }

    private static function decryptClaims(string $token): array
    {
        $raw = self::base64UrlDecode($token);
        if (strlen($raw) < 29) {
            throw new RuntimeException('Token invalido.');
        }
        $iv = substr($raw, 0, 12);
        $tag = substr($raw, 12, 16);
        $ciphertext = substr($raw, 28);
        $json = openssl_decrypt(
            $ciphertext,
            'aes-256-gcm',
            Config::tokenKey(),
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            'portal-portuario-apm-v1'
        );
        if ($json === false) {
            throw new RuntimeException('Token alterado o invalido.');
        }
        $claims = json_decode($json, true, 16, JSON_THROW_ON_ERROR);
        return is_array($claims) ? $claims : throw new RuntimeException('Token sin datos.');
    }

    private static function audit(string $username, string $action, string $description): void
    {
        try {
            $stmt = Conexion::conectar()->prepare(
                'EXEC dbo.sp_th_registrar_auditoria :usuario, :modulo, :accion, :descripcion, :ip'
            );
            $stmt->execute([
                ':usuario' => substr($username, 0, 50),
                ':modulo' => 'Sistema',
                ':accion' => $action,
                ':descripcion' => $description,
                ':ip' => self::clientIp(),
            ]);
        } catch (Throwable $e) {
            Conexion::registrarErrorLog($e, 'Core', false);
        }
    }

    private static function fingerprint(): string
    {
        return hash('sha256', (string)($_SERVER['HTTP_USER_AGENT'] ?? 'unknown'));
    }

    private static function isHttps(): bool
    {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (Config::trustProxyHeaders() && (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https'));
    }

    private static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $data): string
    {
        $padding = (4 - strlen($data) % 4) % 4;
        $decoded = base64_decode(strtr($data . str_repeat('=', $padding), '-_', '+/'), true);
        if ($decoded === false) {
            throw new RuntimeException('Token Base64 invalido.');
        }
        return $decoded;
    }
}
