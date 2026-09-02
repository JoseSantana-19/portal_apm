<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$failures = [];
$assert = static function (bool $condition, string $message) use (&$failures): void {
    if (!$condition) $failures[] = $message;
};

$action = (string)file_get_contents($root.'/modules/talento-humano/Controladores/AccionPersonalController.php');
$employee = (string)file_get_contents($root.'/modules/talento-humano/Controladores/EmpleadoController.php');
$audit = (string)file_get_contents($root.'/modules/auditoria/Controladores/AuditoriaController.php');
$principal = (string)file_get_contents($root.'/modules/talento-humano/Servicios/PdfFormularioPrincipal.php');
$socioeconomic = (string)file_get_contents($root.'/modules/talento-humano/Servicios/PdfEstudioSocioeconomico.php');
$samples = (string)file_get_contents($root.'/scripts/generar_muestras_formatos.php');
$all = implode("\n", [$action,$employee,$audit,$principal,$socioeconomic,$samples]);

$assert(!str_contains($all, 'utf8_decode('), 'Los generadores conservan utf8_decode, obsoleto en PHP 8.5.');
$assert(!str_contains($samples, 'setAccessible('), 'El generador de muestras usa ReflectionMethod::setAccessible obsoleto.');
foreach ([$action,$employee,$audit] as $source) {
    $assert(str_contains($source, 'mb_convert_encoding'), 'Un generador controlador no convierte UTF-8 de forma compatible con PHP 8.5.');
}
$assert(str_contains($principal, 'pagina1()') && str_contains($principal, 'pagina2()'), 'El Formulario Principal no genera sus dos páginas.');
$assert(str_contains($socioeconomic, 'pagina1()') && str_contains($socioeconomic, 'pagina2()') && str_contains($socioeconomic, 'pagina3()') && str_contains($socioeconomic, 'pagina4Ubicacion()'), 'El Socioeconómico no genera las cuatro páginas, incluida la ubicación domiciliaria.');
foreach ([
    'accion_personal_muestra.pdf','accion_personal_formato_blanco.pdf',
    'formulario_principal_muestra.pdf','formulario_principal_formato_blanco.pdf',
    'estudio_socioeconomico_muestra.pdf','estudio_socioeconomico_formato_blanco.pdf',
] as $filename) {
    $assert(str_contains($samples, $filename), "Falta la muestra estable {$filename}.");
}

if ($failures) {
    foreach ($failures as $failure) fwrite(STDERR, "[FAIL] {$failure}".PHP_EOL);
    exit(1);
}
echo '[OK] generadores PDF compatibles con PHP 8.5 y muestras oficiales cubiertas'.PHP_EOL;
