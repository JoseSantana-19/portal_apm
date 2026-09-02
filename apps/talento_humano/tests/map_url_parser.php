<?php
declare(strict_types=1);

define('ROOT', dirname(__DIR__));
require_once ROOT . '/core/Controller.php';
require_once ROOT . '/modules/talento-humano/Controladores/EstudioSeguridadController.php';

$controller = (new ReflectionClass(EstudioSeguridadController::class))->newInstanceWithoutConstructor();
$method = new ReflectionMethod(EstudioSeguridadController::class, 'extraerCoordenadas');

$cases = [
    'https://www.google.com/maps/@-0.956788,-80.711470,17z' => [-0.956788, -80.711470],
    'https://www.google.com/maps/search/?api=1&query=-0.956788%2C-80.711470' => [-0.956788, -80.711470],
    'https://www.google.com/maps/search/-0.956788,+-80.711470?entry=tts' => [-0.956788, -80.711470],
];

foreach ($cases as $url => $expected) {
    $actual = $method->invoke($controller, $url);
    if (!is_array($actual) || abs($actual[0] - $expected[0]) > 0.000001 || abs($actual[1] - $expected[1]) > 0.000001) {
        fwrite(STDERR, "No se reconocieron las coordenadas de {$url}.\n");
        exit(1);
    }
}

echo "MAP_URL_PARSER_OK\n";
