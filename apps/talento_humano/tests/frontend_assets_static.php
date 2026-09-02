<?php
$root = dirname(__DIR__);
$failures = [];
$head = file_get_contents($root.'/shared/head_assets.php') ?: '';
$index = file_get_contents($root.'/index.php') ?: '';

foreach ([
    'public/vendor/fonts/google-fonts.css',
    'public/vendor/bootstrap-icons/bootstrap-icons.min.css',
    'public/vendor/bootstrap-icons/fonts/bootstrap-icons.woff2',
    'public/vendor/bootstrap-icons/LICENSE',
    'public/vendor/fonts/OFL-Manrope.txt',
    'public/vendor/fonts/OFL-SpaceGrotesk.txt',
    'public/vendor/datatables/dataTables.min.js',
    'public/vendor/datatables/dataTables.dataTables.min.css',
    'public/vendor/datatables/LICENSE',
    'public/js/apm_datatables.js',
    'public/css/apm_datatables.css',
] as $relative) {
    if (!is_file($root.'/'.$relative) || filesize($root.'/'.$relative) === 0) $failures[] = "Falta el recurso local $relative";
}
if (preg_match('~(?:fonts\.googleapis|fonts\.gstatic|cdn\.jsdelivr)~i', $head)) $failures[] = 'head_assets.php conserva dependencias remotas';
if (preg_match('~(?:fonts\.googleapis|fonts\.gstatic|cdn\.jsdelivr)~i', $index)) $failures[] = 'La CSP conserva orígenes frontend externos';
if (!str_contains($head, '/public/vendor/fonts/google-fonts.css')) $failures[] = 'No se carga la tipografía local';
if (!str_contains($head, '/public/vendor/bootstrap-icons/bootstrap-icons.min.css')) $failures[] = 'No se cargan los iconos locales';
if (!str_contains($head, '/public/vendor/datatables/dataTables.dataTables.min.css')) $failures[] = 'No se carga el estilo local de DataTables';
$footer = file_get_contents($root.'/shared/footer_scripts.php') ?: '';
if (!str_contains($footer, '/public/vendor/datatables/dataTables.min.js') || !str_contains($footer, '/public/js/apm_datatables.js')) $failures[] = 'No se carga la integración local de DataTables';

foreach ($failures as $failure) fwrite(STDERR, "[FAIL] $failure\n");
echo $failures ? 'FRONTEND_ASSETS_STATIC=FAIL'.PHP_EOL : 'FRONTEND_ASSETS_STATIC=OK'.PHP_EOL;
exit($failures ? 1 : 0);
