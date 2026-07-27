<?php
/**
 * config/export_analytics_env.php — Regenera analytics/.env a partir de
 * config/connections.php (la config central del sistema).
 *
 * El dashboard Python no puede leer un array de PHP, así que en vez de
 * mantener sus credenciales a mano en paralelo, este script las deriva de
 * la MISMA fuente que usa el resto del sistema. Correr después de tocar
 * config/connections.php:
 *
 *   php config/export_analytics_env.php
 */

$conn = require __DIR__ . '/connections.php';

$db       = $conn['databases']['portuaria'];
$server   = $db['server'] ?? $conn['server_default'];
$user     = $conn['credentials']['user'];
$pass     = $conn['credentials']['pass'];
$trusted  = ($user === '') ? 'yes' : 'no';

$lines = [
    '# Generado por config/export_analytics_env.php a partir de config/connections.php',
    '# No editar a mano — volver a correr el script tras cambiar la config central.',
    '',
    'APM_DB_DRIVER={ODBC Driver 17 for SQL Server}',
    "APM_DB_SERVER={$server}",
    "APM_DB_NAME={$db['name']}",
    "APM_DB_TRUSTED={$trusted}",
];

if ($trusted === 'no') {
    $lines[] = "APM_DB_UID={$user}";
    $lines[] = "APM_DB_PWD={$pass}";
}

$lines[] = '';
$lines[] = '# URL base del sistema PHP (Portal APM), para el link "Abrir expediente completo".';
$lines[] = 'APM_PHP_BASE_URL=http://localhost/portal_apm';
$lines[] = '';

$target = __DIR__ . '/../analytics/.env';
file_put_contents($target, implode("\n", $lines));

echo "OK: analytics/.env regenerado desde config/connections.php\n";
