<?php
declare(strict_types=1);

define('ROOT', dirname(__DIR__));
require ROOT.'/core/Config.php';
require ROOT.'/core/SmtpMailer.php';

$keys = [
    'PORTAL_ENV','PORTAL_SMTP_ENABLED','PORTAL_SMTP_HOST','PORTAL_SMTP_PORT',
    'PORTAL_SMTP_ENCRYPTION','PORTAL_SMTP_USER','PORTAL_SMTP_PASSWORD',
    'PORTAL_SMTP_FROM_ADDRESS','PORTAL_SMTP_VERIFY_PEER','PORTAL_SMTP_TIMEOUT',
];
$previous = [];
foreach ($keys as $key) $previous[$key] = getenv($key);
$restore = static function () use ($keys, $previous): void {
    foreach ($keys as $key) {
        $value = $previous[$key];
        putenv($value === false ? $key : $key.'='.$value);
    }
};

try {
    putenv('PORTAL_ENV=production');
    putenv('PORTAL_SMTP_ENABLED=false');
    if (Config::smtp()['enabled'] !== false) throw new RuntimeException('SMTP debe iniciar deshabilitado.');

    putenv('PORTAL_SMTP_ENABLED=true');
    putenv('PORTAL_SMTP_HOST=smtp.example.test');
    putenv('PORTAL_SMTP_PORT=587');
    putenv('PORTAL_SMTP_FROM_ADDRESS=talento@example.test');
    putenv('PORTAL_SMTP_USER=portal');
    putenv('PORTAL_SMTP_PASSWORD=secret-for-test');
    putenv('PORTAL_SMTP_ENCRYPTION=none');
    putenv('PORTAL_SMTP_VERIFY_PEER=true');
    $rejected = false;
    try { Config::smtp(); } catch (RuntimeException) { $rejected = true; }
    if (!$rejected) throw new RuntimeException('Producción aceptó SMTP sin cifrado.');

    putenv('PORTAL_SMTP_ENCRYPTION=tls');
    $config = Config::smtp();
    if (!$config['enabled'] || $config['encryption'] !== 'tls' || !$config['verify_peer']) {
        throw new RuntimeException('La configuración TLS segura no fue aceptada.');
    }

    $recipients = SmtpMailer::normalizeRecipients(['UNO@EXAMPLE.TEST; dos@example.test', 'uno@example.test']);
    if ($recipients !== ['uno@example.test','dos@example.test']) throw new RuntimeException('La normalización SMTP no deduplica destinatarios.');
    $invalid = false;
    try { SmtpMailer::normalizeRecipients(['correo-invalido']); } catch (InvalidArgumentException) { $invalid = true; }
    if (!$invalid) throw new RuntimeException('Se aceptó un destinatario inválido.');

    echo "SMTP_CONFIG_OK\n";
} finally {
    $restore();
}
