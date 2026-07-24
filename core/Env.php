<?php
class Env {
    private static array $vars = [];
    private static bool $loaded = false;

    public static function load(string $path): void {
        if (self::$loaded || !file_exists($path)) return;
        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) continue;
            if (!str_contains($line, '=')) continue;
            [$key, $val] = explode('=', $line, 2);
            $key = trim($key);
            $val = trim($val, " \t\"'");
            self::$vars[$key] = $val;
            $_ENV[$key]       = $val;
        }
        self::$loaded = true;
    }

    public static function get(string $key, mixed $default = null): mixed {
        return self::$vars[$key] ?? $_ENV[$key] ?? $default;
    }

    public static function bool(string $key, bool $default = false): bool {
        $v = strtolower(self::get($key, $default ? 'true' : 'false'));
        return in_array($v, ['true', '1', 'yes', 'on'], true);
    }

    public static function int(string $key, int $default = 0): int {
        return (int)(self::get($key, $default));
    }
}
