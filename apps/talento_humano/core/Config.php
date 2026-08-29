<?php

final class Config
{
    public static function environment(): string
    {
        $value = strtolower(trim((string)(getenv('PORTAL_ENV') ?: 'production')));
        return in_array($value, ['development', 'testing', 'production'], true)
            ? $value
            : 'production';
    }

    public static function isProduction(): bool
    {
        return self::environment() === 'production';
    }

    public static function baseUrl(): string
    {
        $configured = getenv('PORTAL_BASE_URL');
        if ($configured !== false) {
            return rtrim('/' . trim($configured, '/'), '/');
        }
        return php_sapi_name() === 'cli-server' ? '' : '/PortalPortuario';
    }

    public static function trustProxyHeaders(): bool
    {
        return filter_var(getenv('PORTAL_TRUST_PROXY') ?: 'false', FILTER_VALIDATE_BOOL);
    }

    public static function privateDirectory(): string
    {
        $configured = getenv('PORTAL_PRIVATE_DIR');
        return $configured !== false && trim($configured) !== ''
            ? rtrim($configured, '/\\')
            : dirname(ROOT) . DIRECTORY_SEPARATOR . '.portal-portuario-private';
    }

    public static function database(): array
    {
        $fileConfig = [];
        $configFile = self::privateDirectory() . DIRECTORY_SEPARATOR . 'database.php';
        if (is_file($configFile)) {
            $loaded = require $configFile;
            if (is_array($loaded)) {
                $fileConfig = $loaded;
            }
        }

        // Config única del sistema (config/connections.php en la raíz de
        // portal_apm) — misma fuente que usan el portal nativo y las demás
        // apps embebidas (apps/control_bienes, apps/bitacoras). Se consulta
        // solo como último fallback: variables de entorno y database.php
        // privado (flujo documentado en docs/DESPLIEGUE_PRODUCCION.php)
        // siguen teniendo prioridad para un despliegue standalone real.
        $portalConn = null;
        $portalConnFile = dirname(ROOT, 2) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'connections.php';
        if (is_file($portalConnFile)) {
            $loaded = require $portalConnFile;
            if (is_array($loaded)) {
                $portalConn = $loaded;
            }
        }

        $read = static function (string $env, string $key, ?string $default = null) use ($fileConfig): ?string {
            $value = getenv($env);
            if ($value !== false && $value !== '') {
                return $value;
            }
            return isset($fileConfig[$key]) ? (string)$fileConfig[$key] : $default;
        };

        $portalDefault = static function (string $key) use ($portalConn): ?string {
            if ($portalConn === null) {
                return null;
            }
            switch ($key) {
                case 'server':
                    return $portalConn['databases']['talento']['server'] ?? $portalConn['server_default'] ?? null;
                case 'database':
                    return $portalConn['databases']['talento']['name'] ?? null;
                case 'user':
                    return $portalConn['credentials']['user'] ?? null;
                case 'password':
                    return $portalConn['credentials']['pass'] ?? null;
                case 'trust_server_certificate':
                    return isset($portalConn['options']['trust_cert']) ? ($portalConn['options']['trust_cert'] ? '1' : '0') : null;
                case 'encrypt':
                    return isset($portalConn['options']['encrypt']) ? ($portalConn['options']['encrypt'] ? '1' : '0') : null;
                default:
                    return null;
            }
        };

        $config = [
            'server'   => $read('PORTAL_DB_SERVER', 'server', $portalDefault('server') ?? 'JAVIER'),
            'database' => $read('PORTAL_DB_NAME', 'database', $portalDefault('database') ?? 'Talento_Humano'),
            'user'     => $read('PORTAL_DB_USER', 'user', $portalDefault('user')),
            'password' => $read('PORTAL_DB_PASSWORD', 'password', $portalDefault('password')),
            // Debe activarse en produccion una vez instalado el certificado de SQL Server.
            // El valor por defecto conserva compatibilidad con instalaciones locales sin PKI.
            'encrypt'  => filter_var($read('PORTAL_DB_ENCRYPT', 'encrypt', $portalDefault('encrypt') ?? 'false'), FILTER_VALIDATE_BOOL),
            'trust_server_certificate' => filter_var($read('PORTAL_DB_TRUST_CERT', 'trust_server_certificate', $portalDefault('trust_server_certificate') ?? 'false'), FILTER_VALIDATE_BOOL),
        ];

        // Autenticación de Windows (config/connections.php con credenciales
        // vacías, uso estándar en desarrollo para este proyecto): user/password
        // en '' es una config válida, solo null (ninguna fuente los proveyó)
        // es un error real de configuración.
        if ($config['user'] === null || $config['password'] === null) {
            throw new RuntimeException(
                'Faltan credenciales SQL en variables PORTAL_DB_*, en el archivo privado database.php o en config/connections.php.'
            );
        }

        return $config;
    }

    /** Devuelve una clave binaria de 32 bytes para AES-256-GCM. */
    public static function tokenKey(): string
    {
        $env = getenv('PORTAL_TOKEN_KEY');
        if ($env !== false && $env !== '') {
            if (preg_match('/^[a-f0-9]{64}$/i', $env)) {
                return hex2bin($env);
            }
            $decoded = base64_decode($env, true);
            if ($decoded !== false && strlen($decoded) === 32) {
                return $decoded;
            }
            throw new RuntimeException('PORTAL_TOKEN_KEY debe ser hexadecimal de 64 caracteres o Base64 de 32 bytes.');
        }

        $directory = self::privateDirectory();
        $keyFile = $directory . DIRECTORY_SEPARATOR . 'auth-token.key';
        if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
            throw new RuntimeException('No fue posible crear el directorio privado de seguridad.');
        }
        if (!is_file($keyFile)) {
            $key = random_bytes(32);
            if (file_put_contents($keyFile, base64_encode($key), LOCK_EX) === false) {
                throw new RuntimeException('No fue posible guardar la clave privada de tokens.');
            }
            @chmod($keyFile, 0600);
            return $key;
        }

        $key = base64_decode(trim((string)file_get_contents($keyFile)), true);
        if ($key === false || strlen($key) !== 32) {
            throw new RuntimeException('La clave privada de tokens no es valida.');
        }
        return $key;
    }
}
