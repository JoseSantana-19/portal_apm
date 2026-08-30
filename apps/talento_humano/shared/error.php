<?php
$home = defined('BASE_URL') ? BASE_URL.'/talento-humano/inicio' : '/';
$login = defined('BASE_URL') ? BASE_URL.'/login' : '/login';
$target = in_array($status,[401,419],true) ? $login : $home;
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?= (int)$status ?> | Portal Portuario APM</title><link rel="stylesheet" href="<?= defined('BASE_URL')?BASE_URL:'' ?>/public/vendor/fonts/google-fonts.css"><link rel="stylesheet" href="<?= defined('BASE_URL')?BASE_URL:'' ?>/public/css/error.css"></head><body class="error-page"><main class="error-card" role="main"><img src="<?= defined('BASE_URL')?BASE_URL:'' ?>/public/img/logoapm.png" alt="Autoridad Portuaria de Manta"><span class="error-code"><?= (int)$status ?></span><h1><?= htmlspecialchars($title) ?></h1><p><?= htmlspecialchars($publicMessage) ?></p><p class="error-reference">Referencia: <strong><?= htmlspecialchars($requestId) ?></strong></p><a href="<?= htmlspecialchars($target) ?>">Volver al portal</a></main></body></html>
