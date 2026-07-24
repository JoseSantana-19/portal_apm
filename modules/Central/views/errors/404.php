<!DOCTYPE html>
<html lang="es" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Página no encontrada — Portal APM</title>
<link rel="stylesheet" href="<?= defined('APP_URL') ? APP_URL : '' ?>/css/variables.css">
<link rel="stylesheet" href="<?= defined('APP_URL') ? APP_URL : '' ?>/css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<div style="min-height:100vh;display:flex;align-items:center;justify-content:center;background:var(--color-bg);padding:var(--sp-4);">
    <div class="empty-state" style="max-width:420px;text-align:center;">
        <i class="fa-solid fa-map-location-dot" style="font-size:4rem;color:var(--color-text-muted);margin-bottom:var(--sp-4);display:block;"></i>
        <h1 style="font-size:5rem;font-weight:800;color:var(--color-primary);line-height:1;margin-bottom:var(--sp-2);">404</h1>
        <h2 style="font-size:1.25rem;font-weight:600;color:var(--color-text);margin-bottom:var(--sp-2);">Página no encontrada</h2>
        <p style="color:var(--color-text-muted);margin-bottom:var(--sp-6);">
            La ruta solicitada no existe o no tiene permisos para acceder.
        </p>
        <a href="<?= defined('APP_URL') ? APP_URL : '/' ?>/dashboard" class="btn btn-primary">
            <i class="fa-solid fa-house"></i> Ir al Dashboard
        </a>
    </div>
</div>
</body>
</html>
