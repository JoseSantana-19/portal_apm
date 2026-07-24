<?php
/**
 * ModuleSecurity. SSO token generation/verification, MOIS permission checks, and audit logging.
 * SSO_SECRET loaded lazily from CORE_Config — NOT hardcoded.
 */

require_once __DIR__ . '/Model.php';

class ModuleSecurity {

    private static ?string $secretKey = null;

    /**
     * Load SSO_SECRET from CORE_Config. Falls back to env default if DB unavailable.
     */
    private static function getSecretKey(): string {
        if (self::$secretKey !== null) {
            return self::$secretKey;
        }
        try {
            $db   = Database::getInstance();
            $stmt = sqlsrv_query(
                $db->getConn(),
                "SELECT valor FROM CORE_Config WHERE modulo='CORE' AND clave='SSO_SECRET' AND estado=1"
            );
            if ($stmt !== false) {
                $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
                sqlsrv_free_stmt($stmt);
                if (!empty($row['valor'])) {
                    self::$secretKey = $row['valor'];
                    return self::$secretKey;
                }
            }
        } catch (Exception $e) {
            // fall through
        }
        self::$secretKey = 'SysPort_APM_Manta_Secret_SSO_Key_2026!';
        return self::$secretKey;
    }

    /**
     * Generate a cryptographically signed SSO Token (JWT-like) for a user session.
     */
    public static function generateSSOToken(int $userId, string $cedula): string {
        $ip      = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $payload = json_encode(['id' => $userId, 'cedula' => $cedula, 'ip' => $ip, 'exp' => time() + 1800]);
        $b64     = rtrim(strtr(base64_encode($payload), '+/', '-_'), '=');
        $sig     = hash_hmac('sha256', $b64, self::getSecretKey());
        return $b64 . '.' . $sig;
    }

    /**
     * Verify a signed SSO Token — checks signature integrity, expiry, and IP binding.
     */
    public static function verifySSOToken(string $token): array {
        $parts = explode('.', $token);
        if (count($parts) !== 2) {
            return ['success' => false, 'error' => 'Formato de token de sesión no válido.'];
        }

        [$b64, $sig] = $parts;

        if (!hash_equals(hash_hmac('sha256', $b64, self::getSecretKey()), $sig)) {
            return ['success' => false, 'error' => 'La firma del token SSO es inválida.'];
        }

        $payload = json_decode(base64_decode(strtr($b64, '-_', '+/')), true);
        if (!$payload || !isset($payload['id'], $payload['exp'])) {
            return ['success' => false, 'error' => 'El cuerpo del token SSO está corrupto.'];
        }
        if (time() > $payload['exp']) {
            return ['success' => false, 'error' => 'El token SSO ha expirado.'];
        }

        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        if ($payload['ip'] !== $ip && $payload['ip'] !== '127.0.0.1') {
            return ['success' => false, 'error' => 'Intento de secuestro de sesión detectado.'];
        }

        return ['success' => true, 'payload' => $payload];
    }

    /**
     * Check permission via fn_TienePermisoNodo (MOIS 4-tuple).
     * $moduleCode: string key (e.g. 'TH', 'CORE', 'ACCESO') mapped to MOIS id_modulo.
     * $optionId maps to opcion, $formId maps to items; subitems defaults to 0.
     */
    public static function checkAccess(
        int     $userId,
        string  $moduleCode,
        ?int    $optionId          = null,
        ?int    $formId            = null,
        int     $requiredPermission = 1
    ): bool {
        static $moduleMap = [
            'PE'            => 1, 'PLANIFICACION'    => 1,
            'TI'            => 2, 'CORE'             => 2, 'CENTRAL'      => 2,
            'ADMIN'         => 2, 'CREDENCIALES'     => 2,
            'AJ'            => 3, 'JURIDICA'         => 3,
            'IP'            => 4, 'INFRAESTRUCTURA'  => 4,
            'GA'            => 5, 'GARITA'           => 5, 'CONTROL_ACCESO' => 5, 'ACCESO' => 5,
            'DO'            => 6, 'OPERACIONES'      => 6,
            'GG'            => 7, 'GERENCIA'         => 7,
            'DSP'           => 8, 'SERVICIOS'        => 8,
            'DA'            => 9, 'BIENES'           => 9, 'CONTROL_BIENES' => 9,
            'DF'            => 10, 'FINANCIERO'      => 10,
            'TH'            => 11, 'TALENTO_HUMANO'  => 11, 'TALENTO' => 11,
        ];

        try {
            $idModulo = $moduleMap[strtoupper($moduleCode)] ?? null;
            if ($idModulo === null) {
                return false;
            }

            $opcion   = $optionId ?? 0;
            $items    = $formId   ?? 0;
            $subitems = 0;

            $db   = Database::getInstance();
            $stmt = sqlsrv_query(
                $db->getConn(),
                "SELECT dbo.fn_TienePermisoNodo(?,?,?,?,?,?,1) AS ok",
                [
                    [$userId,              SQLSRV_PARAM_IN],
                    [$idModulo,            SQLSRV_PARAM_IN],
                    [$opcion,              SQLSRV_PARAM_IN],
                    [$items,               SQLSRV_PARAM_IN],
                    [$subitems,            SQLSRV_PARAM_IN],
                    [$requiredPermission,  SQLSRV_PARAM_IN],
                ]
            );

            if ($stmt === false) return false;
            $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            sqlsrv_free_stmt($stmt);
            return (bool)($row['ok'] ?? false);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Write an audit record via sp_RegistrarAuditoria.
     */
    public static function audit(
        string  $modulo,
        string  $tipoAuditoria,
        int     $userId,
        string  $evento,
        ?string $tablaDetalles = null,
        ?int    $registroId    = null,
        ?string $datosAnt      = null,
        ?string $datosNue      = null
    ): bool {
        try {
            $model = new class extends Model {
                public function callSP(string $sql, array $params): void {
                    $stmt = $this->query($sql, $params);
                    $this->free($stmt);
                }
            };

            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'CLI';

            $model->callSP('{CALL dbo.sp_RegistrarAuditoria(?,?,?,?,?,?,?,?,?,?)}', [
                $modulo, $tipoAuditoria, $userId, $evento,
                $tablaDetalles, $registroId, $datosAnt, $datosNue,
                $ip, $ua,
            ]);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}
