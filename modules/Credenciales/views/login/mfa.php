<!DOCTYPE html>
<html lang="es" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Verificación en dos pasos — Portal APM</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700;800&family=Outfit:wght@300;400;500;600;700;800&family=Fira+Code:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
:root {
    --bg-mesh: radial-gradient(at 0% 0%, #E0F2FE 0px, transparent 50%),
               radial-gradient(at 100% 0%, #F5F3FF 0px, transparent 50%),
               radial-gradient(at 100% 100%, #ECFDF5 0px, transparent 50%),
               radial-gradient(at 0% 100%, #FEF3C7 0px, transparent 50%);
    --bg-page: #F8FAFC;
    --bg-card: rgba(255, 255, 255, 0.94);
    --bg-input: #F1F5F9;
    --border-color: #E2E8F0;
    --text-main: #0F172A;
    --text-muted: #64748B;
    --text-sub: #334155;
    --shadow-card: 0 24px 48px -12px rgba(15, 23, 42, 0.12);
    --r: 14px;
    --ease: 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}
html:not(.portal-dark-mode) body { background-color: #F8FAFC !important; color: #0F172A !important; }
html.portal-dark-mode body {
    --bg-page: #070D19; --bg-card: rgba(15, 23, 42, 0.9); --bg-input: #0F172A;
    --border-color: #334155; --text-main: #F8FAFC; --text-muted: #94A3B8; --text-sub: #CBD5E1;
    background-color: #070D19 !important; color: #F8FAFC !important;
}
html, body { height: 100%; font-family: 'Sora', 'Outfit', sans-serif; background: var(--bg-page); overflow-x: hidden; }
.mesh-bg { position: fixed; inset: 0; background: var(--bg-page); background-image: var(--bg-mesh); z-index: 0; pointer-events: none; }
.orb { position: absolute; border-radius: 50%; filter: blur(70px); opacity: .28; animation: floatOrb 12s ease-in-out infinite alternate; }
.orb-1 { width: 380px; height: 380px; background: #0284c7; top: -100px; left: -100px; }
.orb-2 { width: 420px; height: 420px; background: #8B5CF6; bottom: -120px; right: -120px; animation-duration: 15s; }
@keyframes floatOrb { 0% { transform: translate(0,0) scale(1); } 100% { transform: translate(-25px,35px) scale(.92); } }

.scene { display: flex; align-items: center; justify-content: center; min-height: 100vh; width: 100%; position: relative; z-index: 10; padding: 24px; }
.card { width: 100%; max-width: 420px; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 20px; box-shadow: var(--shadow-card); padding: 40px 36px; position: relative; backdrop-filter: blur(12px); }
.card-top-accent { position: absolute; top: -2px; left: 20px; right: 20px; height: 4px; background: linear-gradient(90deg, #0284c7, #8B5CF6, #10B981); border-radius: 99px; }

.mfa-icon { width: 64px; height: 64px; border-radius: 18px; background: linear-gradient(135deg, #0284c7, #8B5CF6); display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; box-shadow: 0 12px 28px rgba(2,132,199,.3); }
.mfa-icon i { font-size: 26px; color: #fff; }

.heading { text-align: center; font-size: 24px; font-weight: 800; color: var(--text-main); letter-spacing: -.02em; margin-bottom: 6px; }
.sub { text-align: center; font-size: 13.5px; color: var(--text-muted); margin-bottom: 8px; line-height: 1.5; }
.sub strong { color: var(--text-sub); }

.mfa-error { display: flex; align-items: center; gap: 10px; background: #FEF2F2; border: 1.5px solid #FCA5A5; border-radius: var(--r); padding: 12px 16px; margin: 18px 0; font-size: 13.5px; color: #DC2626; font-weight: 500; }

.code-boxes { display: flex; gap: 10px; justify-content: center; margin: 26px 0 8px; }
.code-boxes input {
    width: 46px; height: 56px; text-align: center; font-size: 24px; font-weight: 700;
    font-family: 'Fira Code', monospace; border: 1.5px solid var(--border-color); border-radius: 12px;
    background: var(--bg-input); color: var(--text-main); outline: none; transition: var(--ease);
}
.code-boxes input:focus { border-color: #0284c7; background: var(--bg-card); box-shadow: 0 0 0 4px rgba(2,132,199,.15); }

.hidden-code { position: absolute; opacity: 0; pointer-events: none; height: 0; width: 0; }

.btn-verify {
    width: 100%; margin-top: 18px; padding: 15px; border: none; border-radius: var(--r);
    font-family: inherit; font-size: 15px; font-weight: 700; cursor: pointer; color: #fff;
    background: linear-gradient(135deg, #0284c7 0%, #8B5CF6 100%); box-shadow: 0 8px 24px rgba(2,132,199,.3);
    display: flex; align-items: center; justify-content: center; gap: 10px; transition: var(--ease);
}
.btn-verify:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 12px 32px rgba(139,92,246,.4); }
.btn-verify:disabled { opacity: .6; cursor: not-allowed; }

.back-link { display: block; text-align: center; margin-top: 22px; font-size: 13px; color: var(--text-muted); text-decoration: none; font-weight: 600; }
.back-link:hover { color: #0284c7; }
</style>
</head>
<body>
<div class="mesh-bg"><div class="orb orb-1"></div><div class="orb orb-2"></div></div>

<div class="scene">
    <div class="card">
        <div class="card-top-accent"></div>
        <div class="mfa-icon"><i class="fa-solid fa-shield-halved"></i></div>
        <div class="heading">Verificación en dos pasos</div>
        <div class="sub">Hola <strong><?= htmlspecialchars($nombre ?? '', ENT_QUOTES, 'UTF-8') ?></strong>, ingresa el código de 6 dígitos de tu aplicación autenticadora.</div>

        <?php if (!empty($error)): ?>
        <div class="mfa-error" role="alert"><i class="fa-solid fa-circle-exclamation"></i><span><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></span></div>
        <?php endif; ?>

        <form method="POST" action="<?= APP_URL ?>/login/verificar" id="mfa-form" autocomplete="off">
            <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf ?? '', ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="codigo" id="codigo-full">

            <div class="code-boxes" id="code-boxes">
                <input inputmode="numeric" maxlength="1" pattern="[0-9]" autofocus>
                <input inputmode="numeric" maxlength="1" pattern="[0-9]">
                <input inputmode="numeric" maxlength="1" pattern="[0-9]">
                <input inputmode="numeric" maxlength="1" pattern="[0-9]">
                <input inputmode="numeric" maxlength="1" pattern="[0-9]">
                <input inputmode="numeric" maxlength="1" pattern="[0-9]">
            </div>

            <button type="submit" class="btn-verify" id="verify-btn">
                <i class="fa-solid fa-check"></i> Verificar e ingresar
            </button>
        </form>

        <a href="<?= APP_URL ?>/login" class="back-link"><i class="fa-solid fa-arrow-left-long"></i> Cancelar y volver al login</a>
    </div>
</div>

<script>
(function () {
    var boxes = Array.prototype.slice.call(document.querySelectorAll('#code-boxes input'));
    var full  = document.getElementById('codigo-full');
    var form  = document.getElementById('mfa-form');

    function sync() {
        full.value = boxes.map(function (b) { return b.value; }).join('');
    }

    boxes.forEach(function (box, i) {
        box.addEventListener('input', function () {
            box.value = box.value.replace(/\D/g, '').slice(0, 1);
            if (box.value && i < boxes.length - 1) boxes[i + 1].focus();
            sync();
            if (boxes.every(function (b) { return b.value; })) {
                form.requestSubmit();
            }
        });
        box.addEventListener('keydown', function (e) {
            if (e.key === 'Backspace' && !box.value && i > 0) boxes[i - 1].focus();
        });
        box.addEventListener('paste', function (e) {
            e.preventDefault();
            var text = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '').slice(0, 6);
            text.split('').forEach(function (ch, idx) { if (boxes[idx]) boxes[idx].value = ch; });
            sync();
            if (text.length === 6) { boxes[5].focus(); form.requestSubmit(); }
        });
    });

    form.addEventListener('submit', function () {
        sync();
        document.getElementById('verify-btn').disabled = true;
    });
})();
</script>
</body>
</html>
