<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$checks = [
    'database/migracion_geolocalizacion_socioeconomica_20260825.sql' => [
        '2026.08.25.2', 'mapa_url_original', 'CK_th_estudio_coordenadas_par',
        'IX_th_estudios_geolocalizacion', 'sp_th_registrar_auditoria',
        'SET QUOTED_IDENTIFIER ON', 'SET NUMERIC_ROUNDABORT OFF',
    ],
    'modules/talento-humano/Vistas/estudio_seguridad.php' => [
        'Parte 4 - Ubicación', 'mapaSocioeconomico', 'mapa_url_original',
        'indicaciones_llegada', 'new QRCode', 'leafletImage', "method:'POST'", 'tileload',
    ],
    'modules/talento-humano/Controladores/EstudioSeguridadController.php' => [
        'resolverMapa', 'Método de consulta no permitido', 'validarDestinoPublico',
        'socio-geolocation', 'data:image/png;base64', 'No fue posible generar el código QR de la ubicación',
        '(?:search|place)',
    ],
    'modules/talento-humano/Modelos/EstudioSeguridadModel.php' => [
        'origen_geolocalizacion', 'La latitud y longitud deben registrarse juntas',
        "'URL','MAPA','MANUAL'", 'sp_th_registrar_auditoria',
    ],
    'modules/talento-humano/Servicios/PdfEstudioSocioeconomico.php' => [
        'pagina4Ubicacion', 'UBICACION DOMICILIARIA Y REFERENCIA',
        'ACCESO MOVIL', 'Responsable de verificación',
    ],
    'modules/talento-humano/Vistas/accion_personal.php' => [
        'Código previsto', 'SQL Server confirma el correlativo definitivo',
        'displayTipoAccion', 'displayVigencia',
    ],
    'public/js/form_drafts.js' => ['el.dataset.noDraft', "=== 'true'", 'window.portalConfirm'],
    'public/js/toast.js' => ['function portalConfirm', 'window.portalConfirm'],
    'index.php' => ['Config::mapTileOrigin()', 'img-src \'self\' data: {$mapTileOrigin}'],
    'core/Config.php' => ['server.arcgisonline.com', 'mapTileOrigin'],
];

$failures = [];
foreach ($checks as $relative => $tokens) {
    $path = $root . '/' . $relative;
    $contents = is_file($path) ? (string)file_get_contents($path) : '';
    if ($contents === '') {
        $failures[] = "No se pudo leer {$relative}.";
        continue;
    }
    foreach ($tokens as $token) {
        if (!str_contains($contents, $token)) {
            $failures[] = "Falta {$token} en {$relative}.";
        }
    }
}

foreach ([
    'public/vendor/leaflet/leaflet.js',
    'public/vendor/leaflet/leaflet-image.js',
    'public/vendor/leaflet/leaflet.css',
    'public/vendor/qrcode/qrcode.min.js',
] as $asset) {
    if (!is_file($root . '/' . $asset) || filesize($root . '/' . $asset) < 1000) {
        $failures[] = "Activo local ausente o incompleto: {$asset}.";
    }
}

if ($failures) {
    fwrite(STDERR, implode(PHP_EOL, $failures) . PHP_EOL);
    exit(1);
}

echo "SOCIO_GEOLOCATION_STATIC_OK\n";
