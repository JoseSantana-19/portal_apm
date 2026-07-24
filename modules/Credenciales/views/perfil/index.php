<?php
$success = SessionHelper::getFlash('success');
$id      = (int)($usuario['id_usuario'] ?? 0);
$nombre  = $usuario['nombre_completo'] ?? '';
$words   = explode(' ', $nombre);
$init    = '';
foreach (array_slice($words, 0, 2) as $w) { $init .= mb_strtoupper(mb_substr($w, 0, 1)); }
?>

<?php if ($success): ?>
<div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<div style="display:flex;align-items:center;gap:var(--sp-3);margin-bottom:var(--sp-5);">
    <h2 style="font-size:var(--font-size-xl);font-weight:var(--font-weight-bold);">
        <i class="fa-solid fa-user-circle" style="color:var(--color-primary);margin-right:var(--sp-2);"></i>
        Mi Perfil
    </h2>
</div>

<div style="display:grid;grid-template-columns:260px 1fr;gap:var(--sp-5);">

    <!-- Avatar card -->
    <div class="card" style="text-align:center;height:fit-content;">
        <div style="width:72px;height:72px;border-radius:50%;background:var(--color-primary);color:#fff;display:flex;align-items:center;justify-content:center;font-size:1.6rem;font-weight:700;margin:0 auto var(--sp-3);">
            <?= htmlspecialchars($init, ENT_QUOTES, 'UTF-8') ?>
        </div>
        <div style="font-size:var(--font-size-base);font-weight:var(--font-weight-bold);">
            <?= htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') ?>
        </div>
        <div style="font-size:var(--font-size-sm);color:var(--color-text-muted);margin-top:var(--sp-1);">
            @<?= htmlspecialchars($usuario['nombre_usuario'] ?? '', ENT_QUOTES, 'UTF-8') ?>
        </div>
        <div style="margin-top:var(--sp-3);">
            <?php
            $nivelLabel = match((int)($usuario['nivel_jerarquia'] ?? 0)) {
                3 => 'Director', 2 => 'Jefatura', 1 => 'Analista', default => 'Operativo',
            };
            ?>
            <span class="badge badge-primary"><?= $nivelLabel ?></span>
        </div>
        <hr style="margin:var(--sp-4) 0;border-color:var(--color-border);">
        <a href="<?= APP_URL ?>/cambiar-contrasena" class="btn btn-outline" style="width:100%;" data-spa>
            <i class="fa-solid fa-lock"></i> Cambiar Contraseña
        </a>
    </div>

    <!-- Edit form -->
    <div class="card">
        <h3 style="font-size:var(--font-size-base);font-weight:var(--font-weight-semibold);margin-bottom:var(--sp-5);">Información de la Cuenta</h3>
        <form method="POST" action="<?= APP_URL ?>/perfil">
            <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--sp-4);">
                <div class="form-group">
                    <label class="form-label">Nombre de Usuario</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($usuario['nombre_usuario'] ?? '', ENT_QUOTES, 'UTF-8') ?>" readonly style="opacity:0.6;">
                </div>
                <div class="form-group">
                    <label class="form-label">Nombre Completo</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') ?>" readonly style="opacity:0.6;">
                </div>
                <div class="form-group" style="grid-column:1/-1;">
                    <label class="form-label">Correo Electrónico</label>
                    <input type="email" name="correo" class="form-control"
                           value="<?= htmlspecialchars($usuario['correo'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                           maxlength="150">
                </div>
            </div>
            <div style="margin-top:var(--sp-5);">
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-floppy-disk"></i> Guardar Cambios
                </button>
            </div>
        </form>
    </div>

</div>
