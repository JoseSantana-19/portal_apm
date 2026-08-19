<?php
/**
 * MfaHelper — TOTP (RFC 6238) para el Portal Central.
 *
 * Mismo algoritmo ya probado en apps/talento_humano/core/Auth.php (base32,
 * HMAC-SHA1, ventana ±1 paso de 30s, cifrado AES-256-GCM del secreto en
 * reposo) -- no se reinventa la matemática de TOTP, se replica el patrón ya
 * validado en producción de ese módulo. La única diferencia real: la clave
 * de cifrado vive en CORE_Config (mismo mecanismo que SSO_SECRET en
 * ModuleSecurity, no un archivo privado aparte) porque el portal ya tiene
 * ese patrón establecido para secretos de aplicación.
 */
class MfaHelper {

    private static ?string $encryptionKey = null;

    /**
     * Clave AES-256 (32 bytes binarios) para cifrar/descifrar secretos TOTP.
     * Auto-generada con random_bytes(32) real la primera vez que se
     * necesita (nunca un valor predecible/hardcodeado, a diferencia del
     * fallback histórico de SSO_SECRET) y persistida en CORE_Config.
     */
    private static function encryptionKey(): string {
        if (self::$encryptionKey !== null) {
            return self::$encryptionKey;
        }
        $db  = Database::getInstance();
        $row = $db->fetch($db->query(
            "SELECT valor FROM CORE_Config WHERE modulo='CORE' AND clave='MFA_ENCRYPTION_KEY'"
        ));
        if ($row && !empty($row['valor'])) {
            self::$encryptionKey = hex2bin($row['valor']);
            return self::$encryptionKey;
        }

        $keyHex = bin2hex(random_bytes(32));
        $db->query(
            "IF NOT EXISTS (SELECT 1 FROM CORE_Config WHERE modulo='CORE' AND clave='MFA_ENCRYPTION_KEY')
             INSERT INTO CORE_Config (modulo, clave, valor, descripcion, estado)
             VALUES ('CORE', 'MFA_ENCRYPTION_KEY', ?, 'Clave AES-256 (hex) para cifrar secretos TOTP de usuarios — autogenerada, no editar a mano.', 1)",
            [[$keyHex, SQLSRV_PARAM_IN]]
        );
        self::$encryptionKey = hex2bin($keyHex);
        return self::$encryptionKey;
    }

    /** Secreto TOTP nuevo: 20 bytes aleatorios, codificados en base32. */
    public static function generateSecret(): string {
        return self::base32Encode(random_bytes(20));
    }

    /** URI otpauth:// estándar — la app autenticadora la usa si se escanea o pega. */
    public static function otpAuthUri(string $secret, string $account): string
    {
        $issuer  = rawurlencode('Portal APM — Autoridad Portuaria de Manta');
        $account = rawurlencode($account);
        return "otpauth://totp/{$issuer}:{$account}?secret={$secret}&issuer={$issuer}&digits=6&period=30";
    }

    /** Cifra el secreto (AES-256-GCM) para guardarlo en CORE_Usuarios.mfa_secreto. */
    public static function encryptSecret(string $secret): string {
        $iv     = random_bytes(12);
        $tag    = '';
        $cipher = openssl_encrypt($secret, 'aes-256-gcm', self::encryptionKey(), OPENSSL_RAW_DATA, $iv, $tag, 'portal-apm-mfa-v1', 16);
        if ($cipher === false) {
            throw new RuntimeException('No fue posible cifrar el secreto MFA.');
        }
        return self::base64UrlEncode($iv . $tag . $cipher);
    }

    /** Descifra un secreto guardado con encryptSecret(). */
    public static function decryptSecret(string $encrypted): string {
        $raw = self::base64UrlDecode($encrypted);
        if (strlen($raw) < 29) {
            throw new RuntimeException('Secreto MFA inválido.');
        }
        $plain = openssl_decrypt(
            substr($raw, 28), 'aes-256-gcm', self::encryptionKey(),
            OPENSSL_RAW_DATA, substr($raw, 0, 12), substr($raw, 12, 16), 'portal-apm-mfa-v1'
        );
        if ($plain === false) {
            throw new RuntimeException('No fue posible descifrar el secreto MFA.');
        }
        return $plain;
    }

    /**
     * Verifica un código de 6 dígitos contra el secreto, con ventana ±1 paso
     * (30s cada uno, tolera pequeños desfaces de reloj del celular).
     * $lastStep = último paso ya usado por este usuario (anti-replay); si se
     * pasa, un código ya validado antes no vuelve a aceptarse. Devuelve el
     * paso que hizo match en $matchedStep (para guardarlo como el nuevo
     * mfa_ultimo_paso), o null si no hubo match.
     */
    public static function verify(string $secret, string $code, ?int $lastStep, ?int &$matchedStep = null): bool {
        $code = preg_replace('/\D/', '', $code) ?? '';
        if (strlen($code) !== 6) {
            return false;
        }
        $step = (int)floor(time() / 30);
        for ($offset = -1; $offset <= 1; $offset++) {
            $candidate = $step + $offset;
            if ($lastStep !== null && $candidate <= $lastStep) {
                continue; // anti-replay: ya se uso este paso (o uno anterior)
            }
            if (hash_equals(self::totpAt($secret, $candidate), $code)) {
                $matchedStep = $candidate;
                return true;
            }
        }
        return false;
    }

    private static function totpAt(string $secret, int $step): string {
        $key     = self::base32Decode($secret);
        $counter = pack('N2', intdiv($step, 0x100000000), $step % 0x100000000);
        $hash    = hash_hmac('sha1', $counter, $key, true);
        $offset  = ord($hash[19]) & 0x0F;
        $binary  = ((ord($hash[$offset]) & 0x7F) << 24)
                 | ((ord($hash[$offset + 1]) & 0xFF) << 16)
                 | ((ord($hash[$offset + 2]) & 0xFF) << 8)
                 | (ord($hash[$offset + 3]) & 0xFF);
        return str_pad((string)($binary % 1000000), 6, '0', STR_PAD_LEFT);
    }

    private static function base32Encode(string $data): string {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $bits = '';
        foreach (str_split($data) as $char) { $bits .= str_pad(decbin(ord($char)), 8, '0', STR_PAD_LEFT); }
        $output = '';
        foreach (str_split($bits, 5) as $chunk) {
            $chunk = str_pad($chunk, 5, '0', STR_PAD_RIGHT);
            $output .= $alphabet[bindec($chunk)];
        }
        return $output;
    }

    private static function base32Decode(string $data): string {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $bits = '';
        foreach (str_split(strtoupper($data)) as $char) {
            $pos = strpos($alphabet, $char);
            if ($pos === false) continue;
            $bits .= str_pad(decbin($pos), 5, '0', STR_PAD_LEFT);
        }
        $bytes = '';
        foreach (str_split($bits, 8) as $chunk) {
            if (strlen($chunk) < 8) continue;
            $bytes .= chr(bindec($chunk));
        }
        return $bytes;
    }

    private static function base64UrlEncode(string $data): string {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $data): string {
        return base64_decode(strtr($data, '-_', '+/') . str_repeat('=', (4 - strlen($data) % 4) % 4));
    }
}
