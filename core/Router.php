<?php
/**
 * Router — Maps URLs to Controller@action handlers.
 * Supports exact match and {param} placeholders.
 */
class Router {
    private array $routes = [];

    public function get(string $path, string $handler): void  { $this->routes['GET'][$path]  = $handler; }
    public function post(string $path, string $handler): void { $this->routes['POST'][$path] = $handler; }

    public function resolve(string $uri, string $method): void {
        $path   = parse_url($uri, PHP_URL_PATH);
        $path   = ($path !== '/' && str_ends_with($path, '/')) ? rtrim($path, '/') : $path;
        $method = strtoupper($method);

        // Exact match
        if (isset($this->routes[$method][$path])) {
            $this->dispatch($this->routes[$method][$path]);
            return;
        }

        // Param match: /usuarios/{id} → /usuarios/42
        foreach ($this->routes[$method] ?? [] as $pattern => $handler) {
            if (!str_contains($pattern, '{')) continue;
            $regex = '#^' . preg_replace('/\{[^}]+\}/', '([^/]+)', $pattern) . '$#';
            if (preg_match($regex, $path, $m)) {
                array_shift($m);
                $this->dispatch($handler, $m);
                return;
            }
        }

        $this->notFound();
    }

    private function dispatch(string $handler, array $params = []): void {
        [$class, $action] = explode('@', $handler);
        if (!class_exists($class)) {
            throw new RuntimeException("Controller not found: {$class}");
        }
        $ctrl = new $class();
        if (!method_exists($ctrl, $action)) {
            throw new RuntimeException("Action {$action} not found on {$class}");
        }
        // Cast numeric URL params (e.g. {id}) to int so type-hinted methods work
        $castParams = array_map(fn($p) => ctype_digit((string)$p) ? (int)$p : $p, $params);
        $ctrl->$action(...$castParams);
    }

    private function notFound(): void {
        http_response_code(404);
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Not found', 'code' => 404]);
        } else {
            $file = ROOT . '/modules/Central/views/errors/404.php';
            file_exists($file) ? require $file : http_response_code(404);
        }
    }
}
