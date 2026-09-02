<?php

define('ROOT', dirname(__DIR__));
require ROOT.'/modules/talento-humano/Servicios/DocumentoFirmadoService.php';

$service = new DocumentoFirmadoService();
$directory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'portal-apm-pdf-test-'.bin2hex(random_bytes(5));
if (!mkdir($directory, 0700, true) && !is_dir($directory)) {
    throw new RuntimeException('No fue posible preparar la prueba temporal.');
}

$valid = $directory.DIRECTORY_SEPARATOR.'valid.pdf';
$invalid = $directory.DIRECTORY_SEPARATOR.'invalid.pdf';
$encrypted = $directory.DIRECTORY_SEPARATOR.'encrypted.pdf';
file_put_contents($valid, "%PDF-1.4\n1 0 obj<</Type/Catalog>>endobj\n%%EOF");
file_put_contents($invalid, "documento que no es PDF");
file_put_contents($encrypted, "%PDF-1.4\n1 0 obj<</Encrypt 2 0 R>>endobj\n%%EOF");

$failed = 0;
try {
    $service->validarPdf($valid);
} catch (Throwable $error) {
    fwrite(STDERR, '[FAIL] PDF válido rechazado: '.$error->getMessage()."\n");
    $failed++;
}
foreach ([$invalid, $encrypted] as $path) {
    try {
        $service->validarPdf($path);
        fwrite(STDERR, '[FAIL] Se aceptó un archivo inválido: '.basename($path)."\n");
        $failed++;
    } catch (InvalidArgumentException) {
        // Resultado esperado.
    }
}

foreach ([$valid, $invalid, $encrypted] as $path) @unlink($path);
@rmdir($directory);
if ($failed > 0) exit(1);
echo "[OK] Validación de PDF firmado y rechazo de archivos inválidos.\n";
