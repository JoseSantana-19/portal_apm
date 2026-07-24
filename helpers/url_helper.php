<?php
class UrlHelper {

    public static function base(string $path = ''): string {
        $base = rtrim(APP_URL, '/');
        return $base . '/' . ltrim($path, '/');
    }

    public static function asset(string $path): string {
        return self::base('public/' . ltrim($path, '/'));
    }

    public static function current(): string {
        return $_SERVER['REQUEST_URI'] ?? '/';
    }

    public static function isActive(string $path): bool {
        $current = parse_url(self::current(), PHP_URL_PATH);
        return str_starts_with($current, $path);
    }

    public static function redirect(string $path): never {
        header('Location: ' . self::base($path));
        exit;
    }

    public static function back(string $fallback = '/'): never {
        $ref = $_SERVER['HTTP_REFERER'] ?? '';
        header('Location: ' . ($ref ?: self::base($fallback)));
        exit;
    }
}
