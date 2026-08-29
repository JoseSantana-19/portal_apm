<?php
class UsuarioModel extends Model {

    /**
     * Resuelve el identificador de login a un nombre_usuario real.
     * Permite ingresar tanto el usuario como la cédula.
     * Prioridad: 1) coincide con nombre_usuario, 2) coincide con cédula.
     * Si no coincide con nada, devuelve el original (sp_Login dará NO_ENCONTRADO).
     */
    private function resolverUsuario($conn, string $input): string {
        $porUsuario = sqlsrv_query($conn, 'SELECT nombre_usuario FROM CORE_Usuarios WHERE nombre_usuario=?', [[$input, SQLSRV_PARAM_IN]]);
        if ($porUsuario && ($r = sqlsrv_fetch_array($porUsuario, SQLSRV_FETCH_ASSOC))) {
            return $r['nombre_usuario'];
        }
        $porCedula = sqlsrv_query($conn, 'SELECT nombre_usuario FROM CORE_Usuarios WHERE cedula=?', [[$input, SQLSRV_PARAM_IN]]);
        if ($porCedula && ($r = sqlsrv_fetch_array($porCedula, SQLSRV_FETCH_ASSOC))) {
            return $r['nombre_usuario'];
        }
        return $input;
    }

    /** Cuánto falta para que expire el bloqueo actual, en texto legible. */
    private function mensajeBloqueo($conn, string $nombreUsuario): string {
        $stmt = sqlsrv_query($conn,
            'SELECT fecha_bloqueo, minutos_bloqueo FROM CORE_Usuarios WHERE nombre_usuario=?',
            [[$nombreUsuario, SQLSRV_PARAM_IN]]
        );
        $row = $stmt ? sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC) : null;
        if (!$row || !$row['fecha_bloqueo']) {
            return 'Cuenta bloqueada temporalmente por demasiados intentos fallidos. Intente más tarde.';
        }

        // fecha_bloqueo llega como string (Database usa ReturnDatesAsStrings=true
        // a nivel de conexión) o, si algún día cambia esa opción, como DateTime.
        $fechaBloqueo = $row['fecha_bloqueo'] instanceof \DateTimeInterface
            ? $row['fecha_bloqueo']
            : new \DateTime((string)$row['fecha_bloqueo']);
        $finBloqueo  = (clone $fechaBloqueo)->modify('+' . (int)$row['minutos_bloqueo'] . ' minutes');
        $restanteSeg = $finBloqueo->getTimestamp() - time();
        if ($restanteSeg <= 0) {
            return 'Cuenta bloqueada temporalmente por demasiados intentos fallidos. Intente de nuevo.';
        }

        $restanteMin = (int)ceil($restanteSeg / 60);
        if ($restanteMin >= 60) {
            $horas = intdiv($restanteMin, 60);
            $mins  = $restanteMin % 60;
            $tiempo = $horas . ' hora' . ($horas === 1 ? '' : 's') . ($mins > 0 ? ' y ' . $mins . ' min' : '');
        } else {
            $tiempo = $restanteMin . ' minuto' . ($restanteMin === 1 ? '' : 's');
        }

        return "Cuenta bloqueada por demasiados intentos fallidos. Intenta de nuevo en {$tiempo}.";
    }

    /**
     * Authenticate user. Returns user data array on success, error string on failure.
     * Uses sp_Login to get hash, then PHP bcrypt verification.
     * El identificador puede ser el nombre de usuario o la cédula.
     */
    public function authenticate(string $username, string $password): array {
        $db = self::db();
        $conn = $db->getConn();

        // Aceptar login por usuario O por cédula → resolver a nombre_usuario real.
        $username = $this->resolverUsuario($conn, $username);

        // SP output params
        $resultado    = null;
        $idUsuario    = null;
        $hash         = null;
        $nivel        = null;
        $reqCambio    = null;
        $nombre       = null;
        $tema         = null;
        $idDepto      = null;

        $params = [
            [$username,     SQLSRV_PARAM_IN],
            [&$resultado,   SQLSRV_PARAM_INOUT, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR), SQLSRV_SQLTYPE_NVARCHAR(30)],
            [&$idUsuario,   SQLSRV_PARAM_INOUT, SQLSRV_PHPTYPE_INT,                     SQLSRV_SQLTYPE_INT],
            [&$hash,        SQLSRV_PARAM_INOUT, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR), SQLSRV_SQLTYPE_NVARCHAR(512)],
            [&$nivel,       SQLSRV_PARAM_INOUT, SQLSRV_PHPTYPE_INT,                     SQLSRV_SQLTYPE_TINYINT],
            [&$reqCambio,   SQLSRV_PARAM_INOUT, SQLSRV_PHPTYPE_INT,                     SQLSRV_SQLTYPE_BIT],
            [&$nombre,      SQLSRV_PARAM_INOUT, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR), SQLSRV_SQLTYPE_NVARCHAR(150)],
            [&$tema,        SQLSRV_PARAM_INOUT, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR), SQLSRV_SQLTYPE_NVARCHAR(20)],
            [&$idDepto,     SQLSRV_PARAM_INOUT, SQLSRV_PHPTYPE_INT,                     SQLSRV_SQLTYPE_INT],
        ];

        $sql  = '{CALL sp_Login(?,?,?,?,?,?,?,?,?)}';
        $stmt = sqlsrv_prepare($conn, $sql, $params);
        if ($stmt === false || sqlsrv_execute($stmt) === false) {
            return ['error' => 'Error interno de autenticación.'];
        }
        sqlsrv_free_stmt($stmt);

        if ($resultado !== 'OK') {
            if ($resultado === 'BLOQUEADO') {
                return ['error' => $this->mensajeBloqueo($conn, $username)];
            }
            $messages = [
                'NO_ENCONTRADO' => 'Usuario o contraseña incorrectos.',
                'INACTIVO'      => 'Usuario inactivo. Contacte al Administrador.',
            ];
            return ['error' => $messages[$resultado] ?? 'Error de autenticación.'];
        }

        // PHP verifica bcrypt (con pepper compartido -- ver SecurityHelper)
        if (!SecurityHelper::verifyPassword($password, $hash)) {
            // Registrar fallo
            sqlsrv_query($conn, '{CALL sp_RegistrarFalloLogin(?,?)}', [
                [$username, SQLSRV_PARAM_IN],
                [SecurityHelper::getClientIp(), SQLSRV_PARAM_IN],
            ]);
            return ['error' => 'Usuario o contraseña incorrectos.'];
        }
        // Migración perezosa: si el hash guardado todavía es del esquema
        // viejo (sin pepper), se regraba con el nuevo ahora que ya se
        // verificó la contraseña real -- transparente para el usuario,
        // ninguna cuenta se bloquea por este cambio.
        if (SecurityHelper::passwordNeedsRehash($hash)) {
            sqlsrv_query($conn, 'UPDATE CORE_Usuarios SET hash_contrasena=? WHERE id_usuario=?', [
                [SecurityHelper::hashPassword($password), SQLSRV_PARAM_IN],
                [$idUsuario, SQLSRV_PARAM_IN],
            ]);
        }

        // MFA: si el usuario lo tiene activo, la sesión NO se crea todavía acá
        // -- se resuelve en AuthController tras validar el código de 6
        // dígitos (ver completeLogin()). Contraseña ya verificada arriba, así
        // que esto ya cuenta como "credenciales correctas, falta 2do factor".
        $mfaRow = $this->fetch($this->query(
            'SELECT requiere_mfa, mfa_secreto, mfa_ultimo_paso FROM CORE_Usuarios WHERE id_usuario=?',
            [[$idUsuario, SQLSRV_PARAM_IN]]
        ));
        if ($mfaRow && (bool)$mfaRow['requiere_mfa'] && !empty($mfaRow['mfa_secreto'])) {
            return [
                'success'          => true,
                'mfa_required'     => true,
                'id_usuario'       => (int)$idUsuario,
                'nombre_usuario'   => $username,
                'nombre_completo'  => $nombre,
                'nivel_jerarquia'  => (int)$nivel,
                'id_departamento'  => $idDepto,
                'tema_preferido'   => $tema ?? 'light',
                'requiere_cambio'  => (bool)$reqCambio,
            ];
        }

        return array_merge(
            ['mfa_required' => false],
            $this->completeLogin((int)$idUsuario, $username, $nombre, (int)$nivel, $idDepto, $tema ?? 'light', (bool)$reqCambio)
        );
    }

    /**
     * Segunda mitad de un login exitoso: crea la sesión real en BD, resetea
     * el contador de intentos fallidos y registra la auditoría de LOGIN.
     * Separado de authenticate() para que un login con MFA activo NO deje
     * una sesión/auditoría "a medias" mientras el usuario todavía no
     * confirmó el segundo factor (ver ese branch arriba).
     */
    public function completeLogin(int $idUsuario, string $username, string $nombre, int $nivel, $idDepto, string $tema, bool $reqCambio, bool $requiereMfa = false): array {
        $conn  = self::db()->getConn();
        $token = SecurityHelper::generateToken(64);
        $horas = defined('SESSION_HOURS_EXPIRA') ? SESSION_HOURS_EXPIRA : 8;
        $ip    = SecurityHelper::getClientIp();
        $ua    = $_SERVER['HTTP_USER_AGENT'] ?? '';

        sqlsrv_query($conn,
            'INSERT INTO CORE_Sesiones (id_usuario,token,ip_address,user_agent,fecha_expira) VALUES (?,?,?,?,DATEADD(HOUR,?,GETDATE()))',
            [
                [$idUsuario, SQLSRV_PARAM_IN],
                [$token,     SQLSRV_PARAM_IN],
                [$ip,        SQLSRV_PARAM_IN],
                [$ua,        SQLSRV_PARAM_IN],
                [$horas,     SQLSRV_PARAM_IN],
            ]
        );
        // Reset fail counter — veces_bloqueado también: un login exitoso es
        // lo único que "perdona" la escalada de bloqueos, no que el
        // bloqueo simplemente expire solo (ver sp_RegistrarFalloLogin).
        sqlsrv_query($conn, 'UPDATE CORE_Usuarios SET intentos_fallidos=0,fecha_bloqueo=NULL,veces_bloqueado=0 WHERE id_usuario=?', [[$idUsuario, SQLSRV_PARAM_IN]]);
        // Audit
        sqlsrv_query($conn,
            "INSERT INTO CORE_Auditoria(id_usuario,modulo,operacion,ip_address,resultado) VALUES(?,?,?,?,?)",
            [
                [$idUsuario, SQLSRV_PARAM_IN],
                ['CORE',     SQLSRV_PARAM_IN],
                ['LOGIN',    SQLSRV_PARAM_IN],
                [$ip,        SQLSRV_PARAM_IN],
                ['EXITO',    SQLSRV_PARAM_IN],
            ]
        );

        return [
            'success'          => true,
            'id_usuario'       => $idUsuario,
            'nombre_usuario'   => $username,
            'nombre_completo'  => $nombre,
            'nivel_jerarquia'  => $nivel,
            'id_departamento'  => $idDepto,
            'tema_preferido'   => $tema,
            'requiere_cambio'  => $reqCambio,
            'requiere_mfa'     => $requiereMfa,
            'session_token'    => $token,
        ];
    }

    public function getMenuItems(int $idUsuario): array {
        $stmt = $this->query('{CALL sp_GetMenuUsuario(?)}', [[$idUsuario, SQLSRV_PARAM_IN]]);
        return $this->fetchAll($stmt);
    }

    public function revokeSession(string $token): void {
        $ip = SecurityHelper::getClientIp();
        $this->query('{CALL sp_Logout(?,?)}', [
            [$token, SQLSRV_PARAM_IN],
            [$ip,    SQLSRV_PARAM_IN],
        ]);
    }

    public function findById(int $id): ?array {
        $stmt = $this->query(
            'SELECT id_usuario,nombre_usuario,correo,nombre_completo,nivel_jerarquia,id_departamento,tema_preferido,estado FROM CORE_Usuarios WHERE id_usuario=?',
            [[$id, SQLSRV_PARAM_IN]]
        );
        return $this->fetch($stmt);
    }

    public function updateTheme(int $idUsuario, string $tema): void {
        $allowed = ['light','dark','corporate'];
        if (!in_array($tema, $allowed, true)) return;
        $this->query('UPDATE CORE_Usuarios SET tema_preferido=? WHERE id_usuario=?', [[$tema], [$idUsuario]]);
    }

    public function getNotifCount(int $idUsuario): int {
        $stmt = $this->query('SELECT COUNT(*) AS cnt FROM CORE_Notificaciones WHERE id_usuario=? AND leida=0', [[$idUsuario]]);
        $row  = $this->fetch($stmt);
        return (int)($row['cnt'] ?? 0);
    }
}
