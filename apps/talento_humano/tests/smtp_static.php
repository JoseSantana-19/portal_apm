<?php

$root = dirname(__DIR__);
$config = file_get_contents($root.'/core/Config.php');
$mailer = file_get_contents($root.'/core/SmtpMailer.php');
$controller = file_get_contents($root.'/modules/talento-humano/Controladores/AccionPersonalController.php');
$env = file_get_contents($root.'/.env.example');
$fail = [];
$assert = static function (bool $condition, string $message) use (&$fail): void { if (!$condition) $fail[] = $message; };

$assert(str_contains($config, 'public static function smtp()'), 'Falta la configuración SMTP centralizada.');
$assert(str_contains($config, "self::isProduction() && (\$encryption === 'none' || !\$verifyPeer)"), 'Producción no exige cifrado y validación SMTP.');
$assert(str_contains($mailer, "STARTTLS") && str_contains($mailer, 'STREAM_CRYPTO_METHOD_TLS_CLIENT'), 'El cliente SMTP no negocia STARTTLS.');
$assert(str_contains($mailer, "'verify_peer'=>(bool)\$config['verify_peer']"), 'El cliente SMTP no valida el certificado remoto.');
$assert(str_contains($mailer, 'quoted_printable_encode') && str_contains($mailer, 'normalizeRecipients'), 'El correo no normaliza destinatarios o cuerpo UTF-8.');
$assert(!str_contains($mailer, 'mail('), 'El envío depende de mail() y no de un relay SMTP verificable.');
$assert(str_contains($controller, 'notificarAprobacionPorCorreo') && str_contains($controller, "\$accion === 'aprobar'"), 'La aprobación no dispara la notificación SMTP.');
$assert(str_contains($controller, "ErrorHandler::log(\$error, 'smtp')"), 'Los fallos SMTP no dejan evidencia técnica.');
$assert(str_contains($env, 'PORTAL_SMTP_ENABLED=false') && str_contains($env, 'PORTAL_SMTP_VERIFY_PEER=true'), 'Faltan marcadores SMTP seguros en .env.example.');

if ($fail) { fwrite(STDERR, "SMTP_STATIC_FAIL\n- ".implode("\n- ", $fail)."\n"); exit(1); }
echo "SMTP_STATIC_OK\n";
