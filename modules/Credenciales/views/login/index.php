<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Portal APM — Acceso</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Fira+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

:root {
    --navy-900: #020812;
    --navy-800: #050e1f;
    --navy-700: #091628;
    --navy-600: #0d1f38;
    --navy-500: #122847;
    --blue-500: #0284c7;
    --blue-400: #38bdf8;
    --blue-300: #7dd3fc;
    --amber:    #f59e0b;
    --amber-l:  #fcd34d;
    --white:    #f0f6ff;
    --muted:    rgba(176,200,230,0.5);
    --border:   rgba(56,189,248,0.14);
    --card-bg:  rgba(9,22,40,0.82);
    --glass:    rgba(255,255,255,0.03);
}

html, body {
    height: 100%;
    font-family: 'Outfit', sans-serif;
    background: var(--navy-900);
    color: var(--white);
    overflow: hidden;
}

/* ── SCENE ─────────────────────────────────────────── */
.scene {
    display: flex;
    height: 100vh;
    position: relative;
}

/* ── LEFT PANEL ─────────────────────────────────────── */
.panel-left {
    flex: 1 1 55%;
    position: relative;
    overflow: hidden;
    display: none;
}

@media (min-width: 900px) { .panel-left { display: block; } }

.sky {
    position: absolute; inset: 0;
    background: radial-gradient(ellipse 120% 70% at 50% 0%, #071830 0%, #020812 70%);
}

/* Stars */
.stars {
    position: absolute; inset: 0;
    pointer-events: none;
}
.star {
    position: absolute;
    width: 2px; height: 2px;
    border-radius: 50%;
    background: rgba(180,210,255,0.7);
    animation: twinkle var(--d,3s) ease-in-out infinite var(--delay,0s);
}
@keyframes twinkle {
    0%,100% { opacity: 0.15; transform: scale(1); }
    50%      { opacity: 0.9;  transform: scale(1.4); }
}

/* Water */
.water {
    position: absolute;
    bottom: 0; left: 0; right: 0;
    height: 42%;
    background: linear-gradient(180deg, #051220 0%, #081c35 40%, #0a2545 100%);
}

.wave {
    position: absolute;
    width: 200%;
    height: 60px;
    background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 1200 60'%3E%3Cpath d='M0,30 C150,55 350,5 600,30 C850,55 1050,5 1200,30 L1200,60 L0,60 Z' fill='%230e2f52' opacity='0.8'/%3E%3C/svg%3E") repeat-x;
    background-size: 600px 60px;
}
.wave1 { bottom: 46%; animation: wave-move 7s linear infinite; opacity: 0.6; }
.wave2 { bottom: 43%; animation: wave-move 11s linear infinite reverse; opacity: 0.4; }
.wave3 { bottom: 40%; animation: wave-move 9s linear infinite; opacity: 0.3; }

@keyframes wave-move { from { background-position-x: 0; } to { background-position-x: 600px; } }

/* Lights on water */
.water-shimmer {
    position: absolute;
    bottom: 0; left: 0; right: 0;
    height: 100%;
    background: repeating-linear-gradient(
        105deg,
        transparent 0px,
        transparent 60px,
        rgba(2,132,199,0.04) 60px,
        rgba(2,132,199,0.04) 62px
    );
}

/* Port silhouette */
.port-silhouette {
    position: absolute;
    bottom: 38%;
    left: 0; right: 0;
    height: 200px;
}

/* Crane SVG */
.crane-wrap {
    position: absolute;
    bottom: 0;
    left: 12%;
    width: 180px;
    animation: crane-sway 8s ease-in-out infinite;
    transform-origin: bottom center;
}
@keyframes crane-sway {
    0%,100% { transform: rotate(-0.3deg); }
    50%      { transform: rotate(0.3deg); }
}

/* Ship */
.ship-wrap {
    position: absolute;
    bottom: 0; right: 8%;
    width: 220px;
    animation: ship-bob 5s ease-in-out infinite;
}
@keyframes ship-bob {
    0%,100% { transform: translateY(0); }
    50%      { transform: translateY(-5px); }
}

/* Beacon light */
.beacon {
    position: absolute;
    top: 22%; left: 68%;
    width: 8px; height: 8px;
    border-radius: 50%;
    background: var(--amber);
    box-shadow: 0 0 12px 6px rgba(245,158,11,0.6);
    animation: beacon-pulse 2s ease-in-out infinite;
}
@keyframes beacon-pulse {
    0%,100% { opacity: 1; box-shadow: 0 0 12px 6px rgba(245,158,11,0.6); }
    50%      { opacity: 0.4; box-shadow: 0 0 4px 2px rgba(245,158,11,0.2); }
}

/* Left panel branding */
.panel-brand {
    position: absolute;
    top: 48px; left: 52px;
    z-index: 10;
}
.panel-brand-logo {
    display: flex; align-items: center; gap: 14px;
    margin-bottom: 14px;
}
.panel-brand-icon {
    width: 46px; height: 46px;
    border-radius: 12px;
    background: linear-gradient(135deg, var(--blue-500), #0369a1);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.3rem; color: #fff;
    box-shadow: 0 8px 24px rgba(2,132,199,0.4);
}
.panel-brand-name {
    font-size: 1.5rem;
    font-weight: 800;
    letter-spacing: -0.02em;
    color: var(--white);
}
.panel-brand-name span { color: var(--blue-400); }
.panel-tagline {
    font-size: 0.78rem;
    font-weight: 400;
    color: var(--muted);
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

/* Module badges at bottom-left */
.module-badges {
    position: absolute;
    bottom: 52px; left: 52px;
    z-index: 10;
    display: flex; flex-direction: column; gap: 10px;
}
.mod-badge {
    display: inline-flex; align-items: center; gap: 10px;
    background: rgba(9,22,40,0.6);
    border: 1px solid var(--border);
    border-radius: 10px;
    padding: 8px 16px;
    font-size: 0.78rem; font-weight: 500;
    color: rgba(176,200,230,0.8);
    backdrop-filter: blur(8px);
    width: fit-content;
    animation: badge-in 0.6s ease both;
}
.mod-badge:nth-child(1) { animation-delay: 0.2s; }
.mod-badge:nth-child(2) { animation-delay: 0.35s; }
.mod-badge:nth-child(3) { animation-delay: 0.5s; }
.mod-badge:nth-child(4) { animation-delay: 0.65s; }
.mod-badge i { color: var(--blue-400); font-size: 0.7rem; }
@keyframes badge-in {
    from { opacity: 0; transform: translateX(-12px); }
    to   { opacity: 1; transform: translateX(0); }
}

/* ── RIGHT PANEL ─────────────────────────────────────── */
.panel-right {
    flex: 0 0 100%;
    display: flex; align-items: center; justify-content: center;
    padding: 24px;
    position: relative;
    background: linear-gradient(160deg, #040d1a 0%, #020812 100%);
}

@media (min-width: 900px) {
    .panel-right {
        flex: 0 0 420px;
        border-left: 1px solid var(--border);
    }
}

/* Subtle grid */
.panel-right::before {
    content: '';
    position: absolute; inset: 0;
    background-image:
        linear-gradient(rgba(56,189,248,0.025) 1px, transparent 1px),
        linear-gradient(90deg, rgba(56,189,248,0.025) 1px, transparent 1px);
    background-size: 40px 40px;
    pointer-events: none;
}

/* ── LOGIN CARD ──────────────────────────────────────── */
.login-card {
    width: 100%;
    max-width: 380px;
    position: relative;
    z-index: 2;
    animation: card-in 0.7s cubic-bezier(.22,.9,.36,1) both;
}
@keyframes card-in {
    from { opacity: 0; transform: translateY(22px); }
    to   { opacity: 1; transform: translateY(0); }
}

/* Mobile logo (shown only when left panel hidden) */
.mobile-brand {
    display: flex; align-items: center; gap: 12px;
    margin-bottom: 36px;
}
.mobile-brand-icon {
    width: 40px; height: 40px;
    border-radius: 10px;
    background: linear-gradient(135deg, var(--blue-500), #0369a1);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.1rem; color: #fff;
}
.mobile-brand-text { font-size: 1.2rem; font-weight: 800; }
.mobile-brand-text span { color: var(--blue-400); }

@media (min-width: 900px) { .mobile-brand { display: none; } }

/* Heading */
.login-heading {
    margin-bottom: 6px;
    font-size: 1.75rem;
    font-weight: 800;
    letter-spacing: -0.03em;
    color: var(--white);
}
.login-sub {
    font-size: 0.82rem;
    color: var(--muted);
    margin-bottom: 32px;
    line-height: 1.5;
}

/* Divider line */
.heading-line {
    width: 40px; height: 3px;
    background: linear-gradient(90deg, var(--blue-500), var(--blue-400));
    border-radius: 99px;
    margin-bottom: 24px;
}

/* Error */
.login-error {
    display: flex; align-items: flex-start; gap: 10px;
    background: rgba(239,68,68,0.1);
    border: 1px solid rgba(239,68,68,0.25);
    border-radius: 10px;
    padding: 12px 14px;
    margin-bottom: 22px;
    font-size: 0.82rem;
    color: #fca5a5;
    animation: shake 0.4s ease;
}
@keyframes shake {
    0%,100% { transform: translateX(0); }
    20%,60%  { transform: translateX(-5px); }
    40%,80%  { transform: translateX(5px); }
}
.login-error i { color: #f87171; margin-top: 1px; flex-shrink: 0; }

/* Form */
.field { margin-bottom: 18px; }
.field-label {
    display: block;
    font-size: 0.73rem;
    font-weight: 600;
    letter-spacing: 0.07em;
    text-transform: uppercase;
    color: rgba(176,200,230,0.6);
    margin-bottom: 8px;
}
.field-wrap {
    position: relative;
}
.field-icon {
    position: absolute;
    left: 14px; top: 50%;
    transform: translateY(-50%);
    color: rgba(56,189,248,0.4);
    font-size: 0.82rem;
    pointer-events: none;
    transition: color 0.2s;
}
.field-input {
    width: 100%;
    background: rgba(255,255,255,0.04);
    border: 1px solid rgba(56,189,248,0.12);
    border-radius: 10px;
    padding: 13px 42px;
    font-size: 0.9rem;
    font-family: 'Outfit', sans-serif;
    color: var(--white);
    transition: border-color 0.2s, background 0.2s, box-shadow 0.2s;
    outline: none;
}
.field-input::placeholder { color: rgba(176,200,230,0.25); }
.field-input:focus {
    border-color: rgba(56,189,248,0.45);
    background: rgba(56,189,248,0.04);
    box-shadow: 0 0 0 3px rgba(2,132,199,0.12);
}
.field-input:focus + .field-icon-after, /* unused */
.field-wrap:focus-within .field-icon { color: var(--blue-400); }

.field-btn {
    position: absolute;
    right: 12px; top: 50%;
    transform: translateY(-50%);
    background: none; border: none; cursor: pointer;
    color: rgba(176,200,230,0.3);
    padding: 4px; line-height: 1;
    transition: color 0.2s;
    font-size: 0.85rem;
}
.field-btn:hover { color: var(--blue-400); }

/* Submit */
.btn-login {
    width: 100%;
    margin-top: 8px;
    padding: 14px;
    border: none;
    border-radius: 10px;
    font-family: 'Outfit', sans-serif;
    font-size: 0.9rem;
    font-weight: 600;
    cursor: pointer;
    background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
    color: #fff;
    letter-spacing: 0.03em;
    transition: transform 0.15s, box-shadow 0.2s, opacity 0.2s;
    box-shadow: 0 4px 20px rgba(2,132,199,0.35);
    position: relative;
    overflow: hidden;
}
.btn-login::before {
    content: '';
    position: absolute; inset: 0;
    background: linear-gradient(135deg, rgba(255,255,255,0.12), transparent);
    opacity: 0; transition: opacity 0.2s;
}
.btn-login:hover:not(:disabled) { transform: translateY(-1px); box-shadow: 0 8px 28px rgba(2,132,199,0.45); }
.btn-login:hover:not(:disabled)::before { opacity: 1; }
.btn-login:active:not(:disabled) { transform: translateY(0); }
.btn-login:disabled { opacity: 0.65; cursor: not-allowed; }

/* Loading state */
.btn-text, .btn-loading { display: flex; align-items: center; justify-content: center; gap: 8px; }
.btn-loading { display: none; }

@keyframes spin { to { transform: rotate(360deg); } }
.spin { animation: spin 0.8s linear infinite; display: inline-block; }

/* Footer */
.login-footer {
    margin-top: 28px;
    text-align: center;
    font-size: 0.72rem;
    color: rgba(176,200,230,0.25);
    line-height: 1.7;
}
.login-footer strong { color: rgba(176,200,230,0.4); font-weight: 500; }

/* Glow orbs */
.orb {
    position: absolute;
    border-radius: 50%;
    pointer-events: none;
    filter: blur(80px);
    opacity: 0.18;
}
.orb1 { width: 280px; height: 280px; background: #0284c7; top: -80px; right: -60px; }
.orb2 { width: 200px; height: 200px; background: #0369a1; bottom: -40px; left: -40px; }

/* Back to home link */
.back-home-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: rgba(176, 200, 230, 0.45);
    font-size: 0.76rem;
    font-weight: 500;
    text-decoration: none;
    margin-bottom: 28px;
    letter-spacing: 0.03em;
    transition: color 0.2s, transform 0.2s;
}
.back-home-link:hover {
    color: var(--blue-400);
    transform: translateX(-3px);
}
.back-home-link i {
    font-size: 0.7rem;
    transition: transform 0.2s;
}
.back-home-link:hover i {
    transform: translateX(-2px);
}
</style>
</head>
<body>

<div class="scene">

    <!-- ── LEFT: ANIMATED PORT SCENE ── -->
    <div class="panel-left">
        <div class="sky"></div>

        <!-- Stars -->
        <div class="stars" id="stars"></div>

        <!-- Beacon -->
        <div class="beacon"></div>

        <!-- Waves -->
        <div class="water">
            <div class="wave wave1"></div>
            <div class="wave wave2"></div>
            <div class="wave wave3"></div>
            <div class="water-shimmer"></div>
        </div>

        <!-- Port silhouette SVG -->
        <div class="port-silhouette">
            <!-- Crane -->
            <div class="crane-wrap">
                <svg width="180" height="200" viewBox="0 0 180 200" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <!-- Base -->
                    <rect x="72" y="150" width="36" height="50" fill="#071628"/>
                    <!-- Tower -->
                    <rect x="82" y="60" width="16" height="92" fill="#071628"/>
                    <!-- Horizontal boom -->
                    <rect x="10" y="58" width="150" height="8" fill="#091e38"/>
                    <!-- Counter-weight arm -->
                    <rect x="10" y="58" width="40" height="4" fill="#0b2445"/>
                    <!-- Cable vertical -->
                    <line x1="120" y1="66" x2="120" y2="140" stroke="#0f2e50" stroke-width="2"/>
                    <!-- Hook -->
                    <rect x="114" y="138" width="12" height="8" rx="2" fill="#0d2644"/>
                    <!-- Operator cabin -->
                    <rect x="78" y="78" width="24" height="18" rx="2" fill="#0a1e36"/>
                    <rect x="82" y="81" width="6" height="6" rx="1" fill="rgba(56,189,248,0.3)"/>
                    <!-- Wheels -->
                    <circle cx="75" cy="198" r="8" fill="#060f1e"/>
                    <circle cx="105" cy="198" r="8" fill="#060f1e"/>
                </svg>
            </div>

            <!-- Ship -->
            <div class="ship-wrap">
                <svg width="220" height="90" viewBox="0 0 220 90" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <!-- Hull -->
                    <path d="M5,50 L30,70 L190,70 L215,50 L200,40 L20,40 Z" fill="#071628"/>
                    <!-- Hull detail -->
                    <path d="M30,70 L190,70 L200,60 L180,62 L40,62 L20,60 Z" fill="#061020"/>
                    <!-- Superstructure -->
                    <rect x="60" y="22" width="90" height="20" rx="3" fill="#091e38"/>
                    <rect x="80" y="10" width="50" height="14" rx="3" fill="#0b2445"/>
                    <!-- Funnel -->
                    <rect x="118" y="2" width="14" height="22" rx="3" fill="#0d2644"/>
                    <!-- Windows -->
                    <rect x="70" y="26" width="8" height="6" rx="1" fill="rgba(56,189,248,0.2)"/>
                    <rect x="84" y="26" width="8" height="6" rx="1" fill="rgba(56,189,248,0.2)"/>
                    <rect x="98" y="26" width="8" height="6" rx="1" fill="rgba(245,158,11,0.25)"/>
                    <rect x="112" y="26" width="8" height="6" rx="1" fill="rgba(56,189,248,0.2)"/>
                    <!-- Mast -->
                    <line x1="100" y1="2" x2="100" y2="22" stroke="#0f2e50" stroke-width="2"/>
                </svg>
            </div>
        </div>

        <!-- Branding -->
        <div class="panel-brand">
            <div class="panel-brand-logo">
                <div class="panel-brand-icon">
                    <i class="fa-solid fa-anchor"></i>
                </div>
                <div>
                    <div class="panel-brand-name">Portal <span>APM</span></div>
                </div>
            </div>
            <div class="panel-tagline">Autoridad Portuaria de Manta</div>
        </div>

        <!-- Module badges -->
        <div class="module-badges">
            <div class="mod-badge"><i class="fa-solid fa-users"></i> Talento Humano</div>
            <div class="mod-badge"><i class="fa-solid fa-boxes-stacked"></i> Control de Bienes</div>
            <div class="mod-badge"><i class="fa-solid fa-book-open"></i> Bitácoras</div>
            <div class="mod-badge"><i class="fa-solid fa-door-open"></i> Control de Acceso</div>
        </div>
    </div>

    <!-- ── RIGHT: LOGIN FORM ── -->
    <div class="panel-right">
        <div class="orb orb1"></div>
        <div class="orb orb2"></div>

        <div class="login-card">

            <!-- Mobile-only brand -->
            <div class="mobile-brand">
                <div class="mobile-brand-icon">
                    <i class="fa-solid fa-anchor"></i>
                </div>
                <div class="mobile-brand-text">Portal <span>APM</span></div>
            </div>

            <a href="<?= APP_URL ?>" class="back-home-link">
                <i class="fa-solid fa-arrow-left-long"></i> Volver al inicio
            </a>

            <div class="login-heading">Iniciar sesión</div>
            <div class="heading-line"></div>
            <div class="login-sub">Ingrese sus credenciales de acceso al sistema integrado portuario.</div>

            <?php if (!empty($error)): ?>
            <div class="login-error" role="alert">
                <i class="fa-solid fa-circle-exclamation"></i>
                <span><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <?php endif; ?>

            <form method="POST" action="<?= APP_URL ?>/login" id="login-form" novalidate autocomplete="off">
                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf ?? '', ENT_QUOTES, 'UTF-8') ?>">

                <div class="field">
                    <label class="field-label" for="username">Usuario o cédula</label>
                    <div class="field-wrap">
                        <i class="fa-solid fa-user field-icon"></i>
                        <input
                            type="text"
                            id="username"
                            name="username"
                            class="field-input"
                            placeholder="nombre.usuario o cédula"
                            autocomplete="username"
                            required
                            autofocus
                            value="<?= htmlspecialchars($_POST['username'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                </div>

                <div class="field">
                    <label class="field-label" for="password">Contraseña</label>
                    <div class="field-wrap">
                        <i class="fa-solid fa-lock field-icon"></i>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="field-input"
                            placeholder="••••••••"
                            autocomplete="current-password"
                            required>
                        <button type="button" class="field-btn" id="toggle-pass" title="Mostrar / ocultar">
                            <i class="fa-regular fa-eye" id="eye-icon"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn-login" id="login-btn">
                    <span class="btn-text" id="btn-text">
                        <i class="fa-solid fa-right-to-bracket"></i> Ingresar al sistema
                    </span>
                    <span class="btn-loading" id="btn-loading">
                        <i class="fa-solid fa-circle-notch spin"></i> Verificando...
                    </span>
                </button>
            </form>

            <div class="login-footer">
                <strong>Sistema Integrado Portuario</strong><br>
                Autoridad Portuaria de Manta &copy; <?= date('Y') ?>
            </div>
        </div>
    </div>

</div>

<script>
// Generate stars
(function() {
    const container = document.getElementById('stars');
    if (!container) return;
    for (let i = 0; i < 120; i++) {
        const s = document.createElement('div');
        s.className = 'star';
        s.style.cssText = `
            left:${Math.random()*100}%;
            top:${Math.random()*58}%;
            --d:${2+Math.random()*4}s;
            --delay:-${Math.random()*4}s;
            width:${Math.random()<0.15 ? 3 : 2}px;
            height:${Math.random()<0.15 ? 3 : 2}px;
            opacity:${0.1+Math.random()*0.6};
        `;
        container.appendChild(s);
    }
})();

// Password toggle
document.getElementById('toggle-pass').addEventListener('click', function() {
    const pwd  = document.getElementById('password');
    const icon = document.getElementById('eye-icon');
    const show = pwd.type === 'password';
    pwd.type = show ? 'text' : 'password';
    icon.className = show ? 'fa-regular fa-eye-slash' : 'fa-regular fa-eye';
    pwd.focus();
});

// Submit loading state
document.getElementById('login-form').addEventListener('submit', function() {
    document.getElementById('btn-text').style.display    = 'none';
    document.getElementById('btn-loading').style.display = 'flex';
    document.getElementById('login-btn').disabled = true;
});
</script>

</body>
</html>
