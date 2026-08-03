<?php $error = $error ?? null; ?>

<div style="display:flex;align-items:center;gap:var(--sp-3);margin-bottom:var(--sp-5);">
    <a href="<?= APP_URL ?>/perfil" class="btn btn-ghost btn-sm" data-spa><i class="fa-solid fa-arrow-left"></i></a>
    <h2 style="font-size:var(--font-size-xl);font-weight:var(--font-weight-bold);">
        <i class="fa-solid fa-lock" style="color:var(--color-primary);margin-right:var(--sp-2);"></i>
        Cambiar Contraseña
    </h2>
</div>

<?php if ($error): ?>
<script>document.addEventListener('DOMContentLoaded', () => PortalAlert.error(<?= json_encode($error) ?>));</script>
<?php endif; ?>

<div style="max-width:420px;">
<form method="POST" action="<?= APP_URL ?>/cambiar-contrasena" id="form-pass">
    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
    <div class="card">

        <div class="form-group">
            <label class="form-label">Contraseña Actual *</label>
            <input type="password" name="contrasena_actual" class="form-control" required
                   autocomplete="current-password">
        </div>

        <div class="form-group" style="margin-top:var(--sp-4);">
            <label class="form-label">Nueva Contraseña * <span style="color:var(--color-text-muted);font-weight:normal;">(mín. 8 caracteres)</span></label>
            <input type="password" name="contrasena_nueva" class="form-control" required minlength="8"
                   id="pass-nueva" autocomplete="new-password">
        </div>

        <div class="form-group" style="margin-top:var(--sp-4);">
            <label class="form-label">Confirmar Nueva Contraseña *</label>
            <input type="password" name="contrasena_confirma" class="form-control" required
                   id="pass-confirma" autocomplete="new-password">
            <div id="pass-match-msg" style="font-size:var(--font-size-xs);margin-top:4px;display:none;"></div>
        </div>

        <div style="display:flex;gap:var(--sp-3);justify-content:flex-end;margin-top:var(--sp-5);">
            <a href="<?= APP_URL ?>/perfil" class="btn btn-outline" data-spa>Cancelar</a>
            <button type="submit" class="btn btn-primary" id="btn-submit-pass">
                <i class="fa-solid fa-lock"></i> Actualizar Contraseña
            </button>
        </div>
    </div>
</form>
</div>

<script>
const n = document.getElementById('pass-nueva');
const c = document.getElementById('pass-confirma');
const msg = document.getElementById('pass-match-msg');
const btn = document.getElementById('btn-submit-pass');

function checkMatch() {
    if (!c.value) { msg.style.display='none'; return; }
    msg.style.display = 'block';
    if (n.value === c.value) {
        msg.style.color = 'var(--color-success)';
        msg.textContent = '✓ Las contraseñas coinciden';
        btn.disabled = false;
    } else {
        msg.style.color = 'var(--color-danger)';
        msg.textContent = '✗ Las contraseñas no coinciden';
        btn.disabled = true;
    }
}
n.addEventListener('input', checkMatch);
c.addEventListener('input', checkMatch);
</script>
