<?php

define('ROOT', dirname(__DIR__));

$checks = [
    'migration' => [
        ROOT.'/database/migracion_expediente_documental_historial_20260827.sql',
        ['th_documentos_firmados', 'sp_th_registrar_documento_firmado', 'vw_th_eventos_laborales', 'sp_th_consultar_eventos_laborales', "estado_documento='APROBADO'", "estado='CERRADO'"],
    ],
    'routes' => [
        ROOT.'/index.php',
        ['documentos-firmados/subir', 'documentos-firmados/descargar', "'documentos_firmados', 'crear'"],
    ],
    'service' => [
        ROOT.'/modules/talento-humano/Servicios/DocumentoFirmadoService.php',
        ['application/pdf', "str_starts_with(\$inicio, '%PDF-')", "stripos(\$muestra, '/Encrypt')", 'hash_file'],
    ],
    'history' => [
        ROOT.'/modules/talento-humano/Vistas/historial.php',
        ['Eventos del expediente', 'Jornadas temporales registradas', 'Vacaciones', 'DOCUMENTO_FIRMADO'],
    ],
    'legalization' => [
        ROOT.'/modules/talento-humano/Vistas/documentos_firmados.php',
        ['legalizable', 'PDF completo, escaneado y firmado', 'Completo y firmado', 'Versiones conservadas'],
    ],
];

$failed = 0;
foreach ($checks as $name => [$file, $needles]) {
    $content = is_file($file) ? (string)file_get_contents($file) : '';
    foreach ($needles as $needle) {
        if (!str_contains($content, $needle)) {
            fwrite(STDERR, "[FAIL] {$name}: falta {$needle}\n");
            $failed++;
        }
    }
}

$officialGenerators = [
    ROOT.'/modules/talento-humano/Controladores/EmpleadoController.php' => 'function imprimirFicha',
    ROOT.'/modules/talento-humano/Controladores/AccionPersonalController.php' => 'function imprimirAccion',
    ROOT.'/modules/talento-humano/Controladores/EstudioSeguridadController.php' => 'function imprimir',
    ROOT.'/modules/talento-humano/Controladores/PazSalvoController.php' => 'function imprimir',
];
foreach ($officialGenerators as $controller => $method) {
    $content = is_file($controller) ? (string)file_get_contents($controller) : '';
    if (!str_contains($content, $method)) {
        fwrite(STDERR, '[FAIL] No existe el generador oficial '.$method.' en '.basename($controller)."\n");
        $failed++;
    }
}

if ($failed > 0) exit(1);
echo "[OK] Expediente firmado, rutas e historial integral presentes.\n";
