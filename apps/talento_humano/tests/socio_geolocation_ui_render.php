<?php
declare(strict_types=1);

define('ROOT', dirname(__DIR__));
define('BASE_URL', '');
define('IMG_URL', '/public/img');
require ROOT . '/core/Config.php';
require ROOT . '/core/InstitutionalClock.php';

final class Auth
{
    public static function can(string $module, string $action = 'visualizar'): bool { return true; }
    public static function user(): array { return ['name'=>'QA APM','role'=>'Administrador']; }
    public static function csrfToken(): string { return 'csrf-qa'; }
}

$_SERVER['REQUEST_URI'] = '/talento-humano/estudio-seguridad?id=1';
$empleado = ['id'=>1,'empleado_id'=>1,'nro_documento'=>'1300000001','apellidos'=>'PRUEBA','nombres'=>'FUNCIONARIO'];
$estudio = $empleado + ['latitud'=>'-0.967653','longitud'=>'-80.708910','origen_geolocalizacion'=>'MANUAL'];
$selectorPersonal = [['id'=>1,'cedula'=>'1300000001','apellidos'=>'PRUEBA','nombres'=>'FUNCIONARIO','cargo'=>'ANALISTA','area'=>'TALENTO HUMANO']];
$codigoFormato = 'APM-BASC-TH-FO-002';
$fechaFormato = '01/04/2019';
$usuarioNombre = 'QA APM';
$usuarioRol = 'Administrador';
$errorFormulario = '';

ob_start();
require ROOT . '/modules/talento-humano/Vistas/estudio_seguridad.php';
$html = (string)ob_get_clean();

$required = [
    'Parte 4 - Ubicación', 'id="mapaSocioeconomico"', 'name="mapa_url_original"',
    'name="latitud"', 'name="longitud"', 'name="indicaciones_llegada"',
    'id="qrUbicacion"', 'leaflet-image.js', 'qrcode.min.js',
    "method:'POST'", 'Página 4 de 4',
];
$missing = array_values(array_filter($required, static fn(string $token): bool => !str_contains($html, $token)));
if ($missing) {
    fwrite(STDERR, 'SOCIO_GEOLOCATION_UI_RENDER_FAIL: ' . implode(', ', $missing) . PHP_EOL);
    exit(1);
}

if (preg_match('#resolver-mapa\?url=#', $html)) {
    fwrite(STDERR, "SOCIO_GEOLOCATION_UI_RENDER_FAIL: la dirección sensible todavía viaja en la URL HTTP.\n");
    exit(1);
}

$scriptOutput = getenv('PORTAL_RENDERED_JS');
if (is_string($scriptOutput) && $scriptOutput !== '') {
    preg_match_all('#<script(?![^>]+src=)[^>]*>(.*?)</script>#si', $html, $matches);
    if (file_put_contents($scriptOutput, implode("\n", $matches[1] ?? [])) === false) {
        fwrite(STDERR, "SOCIO_GEOLOCATION_UI_RENDER_FAIL: no se pudo exportar JavaScript renderizado.\n");
        exit(1);
    }
}

echo "SOCIO_GEOLOCATION_UI_RENDER_OK\n";
