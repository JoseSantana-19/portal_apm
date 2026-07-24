<?php
/**
 * SsoClient — Cliente PHP del SSO central del Portal APM.
 *
 * Para que OTROS módulos/aplicaciones PHP (en el mismo servidor o con acceso
 * SQL a PORTAL_APM) hagan login contra el directorio central CORE_Usuarios
 * usando ÚNICAMENTE los procedimientos almacenados seguros de
 * db/sso_module_login.sql (sin SELECT directo a tablas).
 *
 * Uso:
 *   require 'libs/SsoClient.php';
 *   $sso = new SsoClient([
 *       'server'  => '.\\VICTUS',
 *       'app'     => 'PORTUARIA',
 *       'api_key' => '<api-key-de-64-chars-entregada-al-registrar-la-app>',
 *   ]);
 *   $r = $sso->login('usuario', 'contraseña');
 *   if ($r['ok']) { $token = $r['token']; ... }
 *   $v = $sso->validate($token);
 *   $sso->logout($token);
 *
 * Los módulos que NO tienen acceso SQL usan los endpoints HTTP equivalentes
 * del portal: POST /api/sso/login | /api/sso/validate | /api/sso/logout.
 */
class SsoClient
{
    private $conn;
    private string $app;
    private string $apiKey;
    private string $ip;

    public function __construct(array $cfg)
    {
        $server = $cfg['server']   ?? (defined('DB_SERVER') ? DB_SERVER : '.\\VICTUS');
        $db     = $cfg['database'] ?? (defined('DB_NAME') ? DB_NAME : 'PORTAL_APM');
        $user   = $cfg['user']     ?? (defined('DB_USER') ? DB_USER : '');
        $pass   = $cfg['pass']     ?? (defined('DB_PASS') ? DB_PASS : '');

        $this->app    = (string)($cfg['app'] ?? '');
        $this->apiKey = (string)($cfg['api_key'] ?? '');
        $this->ip     = (string)($cfg['ip'] ?? ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'));

        $opts = [
            'Database'               => $db,
            'CharacterSet'           => 'UTF-8',
            'TrustServerCertificate' => true,
            'Encrypt'                => false,
        ];
        if ($user !== '') { $opts['UID'] = $user; $opts['PWD'] = $pass; }

        $this->conn = sqlsrv_connect($server, $opts);
        if ($this->conn === false) {
            throw new RuntimeException('SsoClient: sin conexión a PORTAL_APM: '
                . print_r(sqlsrv_errors(), true));
        }
    }

    /**
     * Login completo: valida app+cuenta (SP), verifica bcrypt localmente,
     * emite token de sesión central o registra el fallo.
     *
     * @return array{ok:bool, error?:string, resultado?:string, token?:string,
     *               expira?:string, usuario?:array}
     */
    public function login(string $username, string $password): array
    {
        $sql = "DECLARE @res NVARCHAR(30), @id INT, @hash NVARCHAR(512), @niv TINYINT,
                        @req BIT, @nom NVARCHAR(150), @dep INT;
                EXEC dbo.sp_SSO_Login ?, ?, ?, ?, @res OUTPUT, @id OUTPUT, @hash OUTPUT,
                     @niv OUTPUT, @req OUTPUT, @nom OUTPUT, @dep OUTPUT;
                SELECT @res AS res, @id AS id, @hash AS hash, @niv AS niv,
                       @req AS req, @nom AS nom, @dep AS dep;";
        $row = $this->execRow($sql, [$this->app, $this->apiKey, $username, $this->ip]);

        if (!$row || ($row['res'] ?? '') === 'APP_INVALIDA') {
            return ['ok' => false, 'resultado' => 'APP_INVALIDA',
                    'error' => 'Aplicación no autorizada para SSO.'];
        }
        if ($row['res'] !== 'OK') {
            $msg = match ($row['res']) {
                'NO_ENCONTRADO' => 'Usuario o contraseña incorrectos.',
                'INACTIVO'      => 'La cuenta está inactiva.',
                'BLOQUEADO'     => 'Cuenta bloqueada temporalmente por intentos fallidos.',
                default         => 'No fue posible iniciar sesión.',
            };
            // Genérico hacia el usuario; el código exacto va en 'resultado'.
            return ['ok' => false, 'resultado' => $row['res'], 'error' => $msg];
        }

        if (!password_verify($password, (string)$row['hash'])) {
            $this->execRow(
                'EXEC dbo.sp_SSO_RegistrarFallo ?, ?, ?, ?',
                [$this->app, $this->apiKey, $username, $this->ip]
            );
            return ['ok' => false, 'resultado' => 'PASSWORD',
                    'error' => 'Usuario o contraseña incorrectos.'];
        }

        $sql = "DECLARE @tok NVARCHAR(128), @exp DATETIME2;
                EXEC dbo.sp_SSO_ConfirmarLogin ?, ?, ?, ?, ?, NULL, @tok OUTPUT, @exp OUTPUT;
                SELECT @tok AS tok, CONVERT(NVARCHAR(30), @exp, 120) AS exp;";
        $tk = $this->execRow($sql, [
            $this->app, $this->apiKey, (int)$row['id'], $this->ip,
            substr((string)($_SERVER['HTTP_USER_AGENT'] ?? 'SsoClient'), 0, 400),
        ]);

        if (!$tk || empty($tk['tok'])) {
            return ['ok' => false, 'resultado' => 'ERROR',
                    'error' => 'No fue posible emitir la sesión.'];
        }

        return [
            'ok'     => true,
            'token'  => (string)$tk['tok'],
            'expira' => (string)$tk['exp'],
            'usuario' => [
                'id_usuario'      => (int)$row['id'],
                'nombre_usuario'  => $username,
                'nombre_completo' => (string)$row['nom'],
                'nivel_jerarquia' => (int)$row['niv'],
                'id_departamento' => $row['dep'] !== null ? (int)$row['dep'] : null,
                'requiere_cambio' => (bool)$row['req'],
            ],
        ];
    }

    /**
     * Valida un token de sesión central (peticiones posteriores / SSO).
     * @return array{ok:bool, resultado:string, usuario?:array}
     */
    public function validate(string $token): array
    {
        $sql = "DECLARE @res NVARCHAR(30), @id INT, @nu NVARCHAR(50), @nc NVARCHAR(150),
                        @niv TINYINT, @dep INT;
                EXEC dbo.sp_SSO_ValidarToken ?, ?, ?, ?, @res OUTPUT, @id OUTPUT,
                     @nu OUTPUT, @nc OUTPUT, @niv OUTPUT, @dep OUTPUT;
                SELECT @res AS res, @id AS id, @nu AS nu, @nc AS nc, @niv AS niv, @dep AS dep;";
        $row = $this->execRow($sql, [$this->app, $this->apiKey, $token, $this->ip]);

        $res = (string)($row['res'] ?? 'ERROR');
        if ($res !== 'OK') {
            return ['ok' => false, 'resultado' => $res];
        }
        return [
            'ok' => true,
            'resultado' => 'OK',
            'usuario' => [
                'id_usuario'      => (int)$row['id'],
                'nombre_usuario'  => (string)$row['nu'],
                'nombre_completo' => (string)$row['nc'],
                'nivel_jerarquia' => (int)$row['niv'],
                'id_departamento' => $row['dep'] !== null ? (int)$row['dep'] : null,
            ],
        ];
    }

    /** Revoca un token de sesión central. */
    public function logout(string $token): bool
    {
        $this->execRow('EXEC dbo.sp_SSO_Logout ?, ?, ?, ?',
            [$this->app, $this->apiKey, $token, $this->ip]);
        return true;
    }

    /** Ejecuta y devuelve la última fila del último resultset (o null). */
    private function execRow(string $sql, array $params = []): ?array
    {
        $stmt = sqlsrv_query($this->conn, $sql, $params);
        if ($stmt === false) {
            error_log('SsoClient SQL error: ' . print_r(sqlsrv_errors(), true));
            return null;
        }
        $row = null;
        do {
            while ($r = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $row = $r;
            }
        } while (sqlsrv_next_result($stmt));
        sqlsrv_free_stmt($stmt);
        return $row;
    }
}
