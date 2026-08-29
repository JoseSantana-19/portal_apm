<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Cambiar clave | APM</title><?php require ROOT.'/shared/head_assets.php'; ?><style>body{display:grid;place-items:center;padding:24px}.password-card{width:min(460px,100%);padding:28px}.password-card .field{margin-bottom:14px}</style></head><body><main class="card password-card"><img src="<?= IMG_URL ?>/logoapm.png" alt="APM" style="width:64px;margin:auto"><h1 style="text-align:center">Actualizar clave</h1><p>Por seguridad debe definir una clave personal antes de continuar.</p><?php if($error): ?><div style="padding:12px;background:#fee2e2;color:#991b1b;border-radius:10px;margin-bottom:14px"><?= htmlspecialchars($error) ?></div><?php endif; ?><form method="post" action="<?= BASE_URL ?>/cuenta/actualizar-clave"><input type="hidden" name="_csrf" value="<?= htmlspecialchars(Auth::csrfToken()) ?>"><div class="field"><label>Clave actual</label><input type="password" name="clave_actual" required autocomplete="current-password"></div><div class="field"><label>Nueva clave</label><input type="password" name="nueva_clave" required minlength="12" autocomplete="new-password"></div><div class="field"><label>Confirmar nueva clave</label><input type="password" name="confirmar_clave" required minlength="12" autocomplete="new-password"></div><small>Use al menos 12 caracteres con mayúscula, minúscula, número y símbolo.</small><div id="clave-req-msg" style="display:none;padding:10px 12px;border-radius:8px;margin-top:10px;font-size:.82rem"></div><button class="btn btn-primary" style="width:100%;margin-top:18px" type="submit">Guardar y continuar</button></form></main>
<script src="<?= PORTAL_ROOT_URL ?>/js/password-hash.js?v=<?= @filemtime(dirname(ROOT, 2) . '/js/password-hash.js') ?: time() ?>"></script>
<script>
// El servidor ya no puede revisar mayúscula/minúscula/número/símbolo (ver
// Auth::changePassword()): el navegador manda un hash SHA-256, no la clave
// en texto plano. Ese chequeo se hace acá, ANTES de hashear, y bloquea el
// envío si no se cumple -- mismo criterio que ya usa cambiar_contrasena.php
// del portal nativo.
document.querySelector('form').addEventListener('submit', function (e) {
    var actual = document.querySelector('[name="clave_actual"]');
    var nueva = document.querySelector('[name="nueva_clave"]');
    var confirma = document.querySelector('[name="confirmar_clave"]');
    var msg = document.getElementById('clave-req-msg');
    var v = nueva.value;
    var cumple = v.length >= 12 && /[A-Z]/.test(v) && /[a-z]/.test(v) && /\d/.test(v) && /[^A-Za-z0-9]/.test(v);
    if (!cumple) {
        e.preventDefault();
        msg.style.display = 'block';
        msg.style.background = '#fee2e2';
        msg.style.color = '#991b1b';
        msg.textContent = 'La nueva clave debe tener 12+ caracteres, mayúscula, minúscula, número y símbolo.';
        return;
    }
    if (v !== confirma.value) {
        e.preventDefault();
        msg.style.display = 'block';
        msg.style.background = '#fee2e2';
        msg.style.color = '#991b1b';
        msg.textContent = 'Las claves no coinciden.';
        return;
    }
    if (!window.hashPasswordFieldsBeforeSubmit) return;
    e.preventDefault();
    var form = e.target;
    hashPasswordFieldsBeforeSubmit(form, ['clave_actual', 'nueva_clave', 'confirmar_clave']).then(function () {
        form.submit();
    });
});
</script>
</body></html>
