<?php
/**
 * helpers/polyfills_php74.php
 *
 * El proyecto debe correr igual en PHP 7.4, 8.3 y 8.5. Estas funciones se
 * agregaron nativamente en PHP 8.0 y se usan por todo el código (str_starts_with,
 * str_contains, str_ends_with) -- acá se definen SOLO si no existen ya (o sea,
 * no hacen nada en 8.0+, donde el motor ya las trae). Requerir este archivo
 * una sola vez, lo antes posible, en cada punto de entrada real (portal,
 * cada app embebida, scripts de CLI que no pasan por un index.php).
 */

if (!function_exists('str_starts_with')) {
    function str_starts_with(string $haystack, string $needle): bool {
        return $needle === '' || strncmp($haystack, $needle, strlen($needle)) === 0;
    }
}

if (!function_exists('str_ends_with')) {
    function str_ends_with(string $haystack, string $needle): bool {
        return $needle === '' || substr($haystack, -strlen($needle)) === $needle;
    }
}

if (!function_exists('str_contains')) {
    function str_contains(string $haystack, string $needle): bool {
        return $needle === '' || strpos($haystack, $needle) !== false;
    }
}
