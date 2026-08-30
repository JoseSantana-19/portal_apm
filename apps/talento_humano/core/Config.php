<?php

final class Config
{
    public static function timezone(): string
    {
        $value = trim((string)(getenv('PORTAL_TIMEZONE') ?: 'America/Guayaquil'));
        return in_array($value, timezone_identifiers_list(), true)
            ? $value
            : 'America/Guayaquil';
    }

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

        // Sin PORTAL_BASE_URL (despliegue standalone real, no embebido en el
        // portal): se autodetecta desde SCRIPT_NAME en vez de asumir un
        // path fijo -- más portable que un hardcode que no coincide con
        // esta instalación (aquí BASE_URL real llega vía PORTAL_BASE_URL
        // en apps/talento_humano/.env.example, este bloque es solo respaldo).
        $scriptName = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? ''));
        if ($scriptName !== '') {
            $base = rtrim(str_replace('/index.php', '', $scriptName), '/');
            return $base === '/' ? '' : $base;
        }

        return '';
    }

    public static function trustProxyHeaders(): bool
    {
        return filter_var(getenv('PORTAL_TRUST_PROXY') ?: 'false', FILTER_VALIDATE_BOOL);
    }

    /** Plantilla de mosaicos usada por el mapa socioeconomico. */
    public static function mapTileUrl(): string
    {
        $value = trim((string)(getenv('PORTAL_MAP_TILE_URL') ?: 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Street_Map/MapServer/tile/{z}/{y}/{x}'));
        if (!str_starts_with($value, 'https://') || !str_contains($value, '{z}') || !str_contains($value, '{x}') || !str_contains($value, '{y}')) {
            throw new RuntimeException('PORTAL_MAP_TILE_URL debe ser HTTPS e incluir {z}, {x} y {y}.');
        }
        return $value;
    }

    public static function mapAttribution(): string
    {
        return trim((string)(getenv('PORTAL_MAP_ATTRIBUTION') ?: '&copy; Esri, OpenStreetMap contributors'));
    }

    /** Origen HTTPS seguro que debe autorizarse en img-src para los mosaicos. */
    public static function mapTileOrigin(): string
    {
        $parts = parse_url(self::mapTileUrl());
        $host = strtolower((string)($parts['host'] ?? ''));
        if (($parts['scheme'] ?? '') !== 'https' || $host === '' || isset($parts['user']) || isset($parts['pass'])) {
            throw new RuntimeException('PORTAL_MAP_TILE_URL no contiene un origen HTTPS válido.');
        }
        $port = isset($parts['port']) ? ':' . (int)$parts['port'] : '';
        return 'https://' . $host . $port;
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
            // Sin equivalente en config/connections.php -- solo env/database.php
            // privado o el valor fijo por defecto (driver moderno recomendado).
            'driver'   => $read('PORTAL_DB_DRIVER', 'driver', 'ODBC Driver 18 for SQL Server'),
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

    /** Clave independiente para cifrar borradores de formularios (feature form_drafts). */
    public static function draftKey(): string
    {
        $env = getenv('PORTAL_DRAFT_KEY');
        if ($env !== false && $env !== '') {
            if (preg_match('/^[a-f0-9]{64}$/i', $env)) return hex2bin($env);
            $decoded = base64_decode($env, true);
            if ($decoded !== false && strlen($decoded) === 32) return $decoded;
            throw new RuntimeException('PORTAL_DRAFT_KEY debe ser hexadecimal de 64 caracteres o Base64 de 32 bytes.');
        }

        $directory = self::privateDirectory();
        $keyFile = $directory . DIRECTORY_SEPARATOR . 'form-draft.key';
        if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
            throw new RuntimeException('No fue posible crear el directorio privado de borradores.');
        }
        if (!is_file($keyFile)) {
            $key = random_bytes(32);
            if (file_put_contents($keyFile, base64_encode($key), LOCK_EX) === false) {
                throw new RuntimeException('No fue posible guardar la clave privada de borradores.');
            }
            @chmod($keyFile, 0600);
            return $key;
        }
        $key = base64_decode(trim((string)file_get_contents($keyFile)), true);
        if ($key === false || strlen($key) !== 32) throw new RuntimeException('La clave de borradores no es valida.');
        return $key;
    }
}
