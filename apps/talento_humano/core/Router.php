<?php
/**
 * ROUTER – Despachador de URLs amigables a Controlador::método
 *
 * Funciona en dos modos:
 *  • php -S (cli-server): lee REQUEST_URI directamente (el .htaccess no aplica).
 *  • Apache/XAMPP:        lee $_GET['url'] que mod_rewrite inyecta vía RewriteBase.
 */

class Router
{
    /** @var array<string, array{controller: string, method: string}> */
    private array $rutas = [];

    /** @var array<string, array{controller: string, method: string}> */
    private array $rutasDinamicas = [];

    /**
     * Registra una ruta.
     * @param string $url        URL relativa (sin BASE_URL), ej: 'talento-humano/empleado/editar'
     * @param string $controller Nombre de la clase controladora
     * @param string $method     Nombre del método a invocar
     */
    public function add(string $url, string $controller, string $method): void
    {
        $urlNormalizada = trim($url, '/');

        // Si la ruta contiene {param}, la guardamos como dinámica
        if (str_contains($url, '{')) {
            $this->rutasDinamicas[$urlNormalizada] = ['controller' => $controller, 'method' => $method];
        } else {
            $this->rutas[$urlNormalizada] = ['controller' => $controller, 'method' => $method];
        }
    }

    /**
     * Lee la URL actual y despacha al controlador correspondiente.
     * Si no encuentra ruta, devuelve 404.
     */
    public function dispatch(): bool
    {
        // ── Modo php -S (cli-server): .htaccess ignorado, leer REQUEST_URI ──────
        if (php_sapi_name() === 'cli-server' && !isset($_GET['url'])) {
            $uri  = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/';
            $base = defined('BASE_URL') ? BASE_URL : '';

            // Quitar el prefijo BASE_URL si existe (vacío en dev, '/PortalPortuario' en prod)
            if ($base !== '' && strpos($uri, $base) === 0) {
                $uri = substr($uri, strlen($base));
            }

            // Servir archivos estáticos reales (css, js, img, etc.)
            if (is_file(ROOT . $uri) && str_starts_with(ltrim($uri,'/'),'public/')) {
                return false; // php -S sirve únicamente recursos del directorio público
            }

            $url = trim($uri, '/');

        // ── Modo Apache con mod_rewrite: leer ?url= inyectado por .htaccess ──────
        } else {
            $url = $_GET['url'] ?? '';

            // NOTA: parse_url(PHP_URL_PATH) solo funciona con URLs absolutas que
            // contienen un esquema (http://...). Para rutas relativas simples como
            // 'talento-humano/directorio' (sin ://), usarlo retorna null y rompe el
            // routing. Lo aplicamos únicamente si la cadena es una URL completa.
            if (str_contains($url, '://')) {
                $url = parse_url($url, PHP_URL_PATH) ?? $url;
            }

            // Sanear: eliminar barras iniciales/finales y query-string residual
            $url = trim(strtok($url, '?'), '/');
        }

        // ── Despachar al controlador ──────────────────────────────────────────────

        // 1. Coincidencia exacta (rutas estáticas)
        if (isset($this->rutas[$url])) {
            $info       = $this->rutas[$url];
            $controller = new $info['controller']();
            $method     = $info['method'];
            $controller->$method();
            return true;
        }

        // 2. Coincidencia con rutas dinámicas (con parámetros {param})
        foreach ($this->rutasDinamicas as $patron => $info) {
            // Convertir 'talento-humano/empleado/perfil/{cedula}' en regex
            $regex = '#^' . preg_replace('/\{[^}]+\}/', '([^/]+)', $patron) . '$#';
            if (preg_match($regex, $url, $matches)) {
                array_shift($matches); // quitar la coincidencia completa
                $controller = new $info['controller']();
                $method     = $info['method'];
                $controller->$method(...$matches); // pasar parámetros al método
                return true;
            }
        }

        http_response_code(404);
        echo '<h3>404 – Ruta no encontrada: /' . htmlspecialchars($url) . '</h3>';
        return true;
    }
}
