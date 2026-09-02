<?php
$root = dirname(__DIR__);
$failures = [];
$files = [
    'core/ErrorHandler.php', 'shared/error.php', 'public/css/error.css',
    'core/Router.php', 'core/Controller.php', 'core/Auth.php', 'index.php',
];
foreach ($files as $file) if (!is_file($root.'/'.$file)) $failures[] = "Falta $file";
$router = file_get_contents($root.'/core/Router.php') ?: '';
$controller = file_get_contents($root.'/core/Controller.php') ?: '';
$auth = file_get_contents($root.'/core/Auth.php') ?: '';
$index = file_get_contents($root.'/index.php') ?: '';
$handler = file_get_contents($root.'/core/ErrorHandler.php') ?: '';
if (!str_contains($router, 'ErrorHandler::abort(404)')) $failures[] = 'Router no usa la página 404 central';
if (str_contains($controller, 'die(')) $failures[] = 'Controller aún expone fallos mediante die';
if (!str_contains($auth, 'ErrorHandler::abort(403)') || !str_contains($auth, 'ErrorHandler::abort(419)')) $failures[] = 'RBAC/CSRF no usan errores centralizados';
if (!str_contains($index, 'ErrorHandler::register()')) $failures[] = 'El manejador global no se registra';
if (!str_contains($handler, 'request_id') || !str_contains($handler, 'application/json')) $failures[] = 'El manejador no entrega referencia o JSON seguro';
foreach ($failures as $failure) fwrite(STDERR, "[FAIL] $failure\n");
echo $failures ? 'ERROR_HANDLING_STATIC=FAIL'.PHP_EOL : 'ERROR_HANDLING_STATIC=OK'.PHP_EOL;
exit($failures ? 1 : 0);
