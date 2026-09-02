<?php

final class ErrorHandler
{
    private static bool $registered = false;

    public static function register(): void
    {
        if (self::$registered) return;
        self::$registered = true;
        set_exception_handler(static function (Throwable $error): void {
            self::log($error);
            self::render(500);
        });
        register_shutdown_function(static function (): void {
            $last = error_get_last();
            if (!$last || !in_array($last['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) return;
            $error = new ErrorException($last['message'], 0, $last['type'], $last['file'], $last['line']);
            self::log($error);
            if (!headers_sent()) self::render(500);
        });
    }

    public static function abort(int $status, ?string $message = null, ?Throwable $error = null): never
    {
        if ($error !== null) self::log($error);
        self::render($status, $message);
    }

    public static function render(int $status, ?string $message = null): never
    {
        if (!in_array($status, [400, 401, 403, 404, 405, 419, 422, 500, 503], true)) $status = 500;
        $requestId = self::requestId();
        http_response_code($status);
        header('Cache-Control: no-store, max-age=0');
        $publicMessage = $message ?: self::defaultMessage($status);
        if (self::wantsJson()) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success'=>false,'status'=>$status,'message'=>$publicMessage,'request_id'=>$requestId], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
            exit;
        }
        $title = self::title($status);
        $errorView = (defined('ROOT') ? ROOT : dirname(__DIR__)) . '/shared/error.php';
        if (is_file($errorView)) require $errorView;
        else echo '<h1>'.(int)$status.' · '.htmlspecialchars($title).'</h1><p>'.htmlspecialchars($publicMessage).'</p><p>Referencia: '.htmlspecialchars($requestId).'</p>';
        exit;
    }

    public static function log(Throwable $error, string $context = 'sistema'): string
    {
        $requestId = self::requestId();
        $root = defined('ROOT') ? ROOT : dirname(__DIR__);
        $safeContext = trim((string)preg_replace('/[^a-z0-9-]+/', '-', strtolower($context)), '-') ?: 'sistema';
        $directory = $root . '/storage/logs/' . $safeContext;
        if (!is_dir($directory)) @mkdir($directory, 0755, true);
        $line = sprintf("[%s] request_id=%s %s: %s in %s:%d uri=%s\n", date('c'), $requestId, get_class($error), str_replace(["\r","\n"], ' ', $error->getMessage()), $error->getFile(), $error->getLine(), (string)($_SERVER['REQUEST_URI'] ?? 'CLI'));
        @file_put_contents($directory.'/log_'.date('Y-m-d').'.txt', $line, FILE_APPEND|LOCK_EX);
        return $requestId;
    }

    private static function requestId(): string
    {
        static $id = null;
        if ($id === null) try {$id = strtoupper(bin2hex(random_bytes(6)));} catch (Throwable) {$id = strtoupper(substr(hash('sha256', uniqid('', true)),0,12));}
        return $id;
    }

    private static function wantsJson(): bool
    {
        return str_contains(strtolower((string)($_SERVER['HTTP_ACCEPT'] ?? '')), 'application/json') || strtolower((string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';
    }

    private static function title(int $status): string
    {
        return match($status){400,422=>'Solicitud no válida',401=>'Sesión requerida',403=>'Acceso denegado',404=>'Página no encontrada',405=>'Método no permitido',419=>'Formulario vencido',503=>'Servicio temporalmente no disponible',default=>'No fue posible completar la operación'};
    }

    private static function defaultMessage(int $status): string
    {
        return match($status){400,422=>'Revise la información enviada e inténtelo nuevamente.',401=>'Inicie sesión para continuar.',403=>'Su rol no tiene permisos para realizar esta operación.',404=>'El recurso solicitado no existe o ya no está disponible.',405=>'La operación no admite el método utilizado.',419=>'La sesión del formulario venció. Recargue la página e inténtelo nuevamente.',503=>'El servicio está temporalmente fuera de línea. Inténtelo más tarde.',default=>'La incidencia fue registrada. Comuníquese con la Dirección de TI e indique la referencia mostrada.'};
    }
}
