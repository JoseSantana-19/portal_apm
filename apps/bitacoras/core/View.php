<?php
/**
 * View — Renders module views with optional shell layout.
 * View paths: "ModuleName/subpath" → modules/ModuleName/views/subpath.php
 * Layout shell: modules/Central/views/layouts/shell.php
 */
class View {

    public static function render(string $view, array $data = [], bool $useLayout = true): void {
        extract($data, EXTR_SKIP);

        $viewFile = self::resolve($view);

        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
               && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

        if ($isAjax || !$useLayout) {
            require $viewFile;
            return;
        }

        // Capture view output then inject into shell
        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        require ROOT . '/modules/Central/views/layouts/shell.php';
    }

    public static function partial(string $view, array $data = []): void {
        self::render($view, $data, false);
    }

    public static function resolve(string $view): string {
        // Support two formats:
        //   "ModuleName/sub/path"  → modules/ModuleName/views/sub/path.php
        //   absolute path          → used as-is
        if (str_starts_with($view, '/')) {
            $file = $view;
        } else {
            $parts   = explode('/', $view, 2);
            $module  = $parts[0];
            $subpath = $parts[1] ?? 'index';
            $file    = ROOT . "/modules/{$module}/views/{$subpath}.php";

            // Fallback: legacy views/ directory (General/home/index → views/General/home/index.php)
            if (!file_exists($file)) {
                $legacy = ROOT . "/views/{$view}.php";
                if (file_exists($legacy)) {
                    $file = $legacy;
                }
            }
        }

        if (!file_exists($file)) {
            throw new RuntimeException("View not found: {$file}");
        }
        return $file;
    }

    public static function isAjax(): bool {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
}
