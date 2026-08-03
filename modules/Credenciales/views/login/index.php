<!DOCTYPE html>
<html lang="es" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Portal APM — Acceso Corporativo</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700;800&family=Outfit:wght@300;400;500;600;700;800&family=Fira+Code:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

:root {
    --p: #0284c7;
    --p2: #0369a1;
    --p3: #E0F2FE;
    --p-glow: rgba(2, 132, 199, 0.2);
    
    /* Vibrant Light Theme Palette (Default) */
    --bg-mesh: radial-gradient(at 0% 0%, #E0F2FE 0px, transparent 50%),
               radial-gradient(at 100% 0%, #F5F3FF 0px, transparent 50%),
               radial-gradient(at 100% 100%, #ECFDF5 0px, transparent 50%),
               radial-gradient(at 0% 100%, #FEF3C7 0px, transparent 50%);
    --bg-page: #F8FAFC;
    --bg-card: rgba(255, 255, 255, 0.94);
    --bg-input: #F1F5F9;
    --border-color: #E2E8F0;
    --border-hover: #CBD5E1;
    --text-main: #0F172A;
    --text-muted: #64748B;
    --text-sub: #334155;
    --shadow-card: 0 24px 48px -12px rgba(15, 23, 42, 0.12);
    --shadow-subtle: 0 4px 20px -2px rgba(15, 23, 42, 0.05);
    --r: 14px;
    --ease: 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}

/* HIGH SPECIFICITY LIGHT MODE OVERRIDES */
html:not(.portal-dark-mode) body,
body.light-mode {
    background-color: #F8FAFC !important;
    color: #0F172A !important;
}

html:not(.portal-dark-mode) .panel-right,
body.light-mode .panel-right {
    background: rgba(255, 255, 255, 0.92) !important;
    backdrop-filter: blur(16px) !important;
}

html:not(.portal-dark-mode) .panel-left,
body.light-mode .panel-left {
    background: linear-gradient(145deg, rgba(255,255,255,0.95) 0%, rgba(241,245,249,0.85) 100%) !important;
    border-right-color: #E2E8F0 !important;
}

html:not(.portal-dark-mode) .field-input,
body.light-mode .field-input {
    background-color: #F8FAFC !important;
    color: #0F172A !important;
    border-color: #E2E8F0 !important;
}

html:not(.portal-dark-mode) .field-input:focus,
body.light-mode .field-input:focus {
    background-color: #FFFFFF !important;
    border-color: #0284c7 !important;
    box-shadow: 0 0 0 4px rgba(2, 132, 199, 0.15) !important;
}

html:not(.portal-dark-mode) .login-heading,
html:not(.portal-dark-mode) .field-label,
body.light-mode .login-heading,
body.light-mode .field-label {
    color: #0F172A !important;
}

/* DARK MODE OVERRIDES (Vibrant Cyber Space) */
html.portal-dark-mode body,
body.dark-mode {
    --bg-mesh: radial-gradient(at 0% 0%, #0F172A 0px, transparent 50%),
               radial-gradient(at 100% 0%, #1E1B4B 0px, transparent 50%),
               radial-gradient(at 100% 100%, #064E3B 0px, transparent 50%),
               radial-gradient(at 0% 100%, #451A03 0px, transparent 50%);
    --bg-page: #070D19 !important;
    --bg-card: rgba(15, 23, 42, 0.9) !important;
    --bg-input: #0F172A !important;
    --border-color: #334155 !important;
    --border-hover: #475569 !important;
    --text-main: #F8FAFC !important;
    --text-muted: #94A3B8 !important;
    --text-sub: #CBD5E1 !important;
    --shadow-card: 0 24px 48px -12px rgba(0, 0, 0, 0.6) !important;
    background-color: #070D19 !important;
    color: #F8FAFC !important;
}

html.portal-dark-mode .panel-right,
body.dark-mode .panel-right {
    background: rgba(15, 23, 42, 0.92) !important;
    backdrop-filter: blur(16px) !important;
}

html.portal-dark-mode .panel-left,
body.dark-mode .panel-left {
    background: linear-gradient(135deg, rgba(15,23,42,0.95) 0%, rgba(30,41,59,0.85) 100%) !important;
    border-right-color: #334155 !important;
}

html.portal-dark-mode .field-input,
body.dark-mode .field-input {
    background-color: #0F172A !important;
    color: #F8FAFC !important;
    border-color: #334155 !important;
}

html.portal-dark-mode .login-heading,
html.portal-dark-mode .field-label,
body.dark-mode .login-heading,
body.dark-mode .field-label {
    color: #F8FAFC !important;
}

html, body {
    height: 100%;
    font-family: 'Sora', 'Outfit', sans-serif;
    background: var(--bg-page);
    color: var(--text-main);
    overflow-x: hidden;
    transition: background 0.35s ease, color 0.35s ease;
}

/* ── ANIMATED MESH BACKGROUND WITH FLOATING ORBS ── */
.mesh-bg {
    position: fixed;
    inset: 0;
    background: var(--bg-page);
    background-image: var(--bg-mesh);
    z-index: 0;
    pointer-events: none;
    transition: all 0.5s ease;
}

.orb {
    position: absolute;
    border-radius: 50%;
    filter: blur(70px);
    opacity: 0.28;
    animation: floatOrb 12s ease-in-out infinite alternate;
    pointer-events: none;
}
.orb-1 { width: 380px; height: 380px; background: #0284c7; top: -100px; left: -100px; animation-duration: 11s; }
.orb-2 { width: 420px; height: 420px; background: #8B5CF6; bottom: -120px; right: -120px; animation-duration: 15s; }
.orb-3 { width: 320px; height: 320px; background: #10B981; top: 35%; left: 25%; animation-duration: 13s; }
.orb-4 { width: 280px; height: 280px; background: #F59E0B; bottom: 20%; right: 30%; animation-duration: 10s; }

@keyframes floatOrb {
    0% { transform: translate(0, 0) scale(1); }
    50% { transform: translate(35px, -45px) scale(1.12); }
    100% { transform: translate(-25px, 35px) scale(0.92); }
}

/* ── SUN / MOON ANIMATED TOGGLE BUTTON ── */
.theme-toggle-wrap {
    position: fixed;
    top: 20px;
    right: 24px;
    z-index: 999;
}

.theme-toggle-btn {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 7px 16px 7px 10px;
    background: var(--bg-card);
    border: 1.5px solid var(--border-color);
    border-radius: 999px;
    cursor: pointer;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
    transition: var(--ease);
    outline: none;
    backdrop-filter: blur(12px);
}

body.dark-mode .theme-toggle-btn,
html.portal-dark-mode .theme-toggle-btn {
    background: rgba(15, 23, 42, 0.9) !important;
    border-color: #334155 !important;
}

.theme-toggle-btn:hover {
    transform: translateY(-2px) scale(1.03);
    border-color: #0284c7;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
}

.toggle-track {
    width: 38px;
    height: 22px;
    background: #E2E8F0;
    border-radius: 999px;
    position: relative;
    transition: background 0.3s ease;
}

body.dark-mode .toggle-track,
html.portal-dark-mode .toggle-track {
    background: #334155 !important;
}

.toggle-thumb {
    width: 18px;
    height: 18px;
    background: #FFFFFF;
    border-radius: 50%;
    position: absolute;
    top: 2px;
    left: 2px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
    transition: transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1), background 0.3s ease;
}

body.dark-mode .toggle-thumb,
html.portal-dark-mode .toggle-thumb {
    transform: translateX(16px) !important;
    background: #0F172A !important;
}

.icon-sun {
    font-size: 11px;
    color: #F59E0B;
    transition: opacity 0.25s ease, transform 0.4s ease;
}

.icon-moon {
    font-size: 10px;
    color: #38BDF8;
    position: absolute;
    opacity: 0;
    transform: rotate(-90deg) scale(0.5);
    transition: opacity 0.25s ease, transform 0.4s ease;
}

body.dark-mode .icon-sun,
html.portal-dark-mode .icon-sun {
    opacity: 0 !important;
    transform: rotate(90deg) scale(0.5) !important;
}

body.dark-mode .icon-moon,
html.portal-dark-mode .icon-moon {
    opacity: 1 !important;
    transform: rotate(0deg) scale(1) !important;
}

.toggle-label {
    font-size: 12.5px;
    font-weight: 700;
    color: var(--text-main);
    letter-spacing: 0.02em;
    user-select: none;
}

/* ── SCENE & SPLIT LAYOUT ── */
.scene {
    display: flex;
    height: 100vh;
    width: 100%;
    position: relative;
    z-index: 10;
}

/* LEFT PANEL (BRANDING & VIBRANT MODULE BADGES) */
.panel-left {
    flex: 1 1 52%;
    position: relative;
    overflow-y: auto;
    display: none;
    background: linear-gradient(135deg, rgba(255,255,255,0.92) 0%, rgba(241,245,249,0.85) 100%);
    border-right: 1px solid var(--border-color);
    flex-direction: column;
    justify-content: center;
    align-items: center;
    padding: 60px 48px;
    transition: var(--ease);
}

@media (min-width: 900px) { .panel-left { display: flex; } }

.lb-logo {
    width: 92px;
    height: 92px;
    background: linear-gradient(135deg, #0284c7 0%, #8B5CF6 50%, #10B981 100%);
    border-radius: 26px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 24px;
    box-shadow: 0 16px 36px rgba(2, 132, 199, 0.35);
    animation: logoPulse 4s ease-in-out infinite alternate;
}

@keyframes logoPulse {
    0% { transform: scale(1); box-shadow: 0 14px 32px rgba(2, 132, 199, 0.3); }
    100% { transform: scale(1.06) rotate(3deg); box-shadow: 0 20px 44px rgba(139, 92, 246, 0.45); }
}

.lb-logo i {
    font-size: 42px;
    color: #ffffff;
}

.panel-brand-name {
    font-size: 38px;
    font-weight: 800;
    color: var(--brand-title);
    line-height: 1.15;
    text-align: center;
    margin-bottom: 12px;
    letter-spacing: -0.03em;
}

.panel-brand-name span.gradient-txt {
    background: linear-gradient(135deg, #0284c7 0%, #8B5CF6 50%, #10B981 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

.panel-tagline {
    font-size: 14.5px;
    color: var(--brand-sub);
    text-align: center;
    line-height: 1.65;
    max-width: 400px;
    margin-bottom: 40px;
}

/* VIBRANT MODULE BADGES FLOW (Directorio Inspirado) */
.module-badges-wrap {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    justify-content: center;
    max-width: 500px;
}

.badge-item {
    display: inline-flex;
    align-items: center;
    gap: 9px;
    background: var(--bg-card);
    border: 1.5px solid var(--border-color);
    border-radius: 999px;
    padding: 10px 20px;
    font-size: 13px;
    font-weight: 700;
    color: var(--text-main);
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.05);
    transition: all 0.35s cubic-bezier(0.34, 1.56, 0.64, 1);
    animation: floatBadge 6s ease-in-out infinite alternate;
}

.badge-item:nth-child(1) { animation-delay: 0s; --b-color: #B45309; --b-bg: #FEF3C7; }
.badge-item:nth-child(2) { animation-delay: 0.5s; --b-color: #0284c7; --b-bg: #F0F9FF; }
.badge-item:nth-child(3) { animation-delay: 1s; --b-color: #8B5CF6; --b-bg: #F5F3FF; }
.badge-item:nth-child(4) { animation-delay: 1.5s; --b-color: #4F46E5; --b-bg: #EEF2FF; }
.badge-item:nth-child(5) { animation-delay: 2s; --b-color: #10B981; --b-bg: #ECFDF5; }
.badge-item:nth-child(6) { animation-delay: 2.5s; --b-color: #2563EB; --b-bg: #EFF6FF; }
.badge-item:nth-child(7) { animation-delay: 3s; --b-color: #14B8A6; --b-bg: #F0FDFA; }

@keyframes floatBadge {
    0% { transform: translateY(0); }
    100% { transform: translateY(-6px); }
}

.badge-item:hover {
    transform: translateY(-8px) scale(1.05);
    border-color: var(--b-color);
    box-shadow: 0 10px 24px rgba(0, 0, 0, 0.12);
}

.badge-icon {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: var(--b-bg);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--b-color);
    font-size: 13px;
}

/* RIGHT PANEL (FORM) */
.panel-right {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 40px 48px;
    position: relative;
    background: var(--bg-card);
    box-shadow: var(--shadow-card);
}

@media (min-width: 900px) {
    .panel-right {
        flex: 0 0 460px;
        border-left: 1px solid var(--border-color);
    }
}

.login-card {
    width: 100%;
    max-width: 400px;
    position: relative;
    z-index: 2;
}

/* TOP VIBRANT MULTI-COLOR ACCENT LINE */
.card-top-accent {
    position: absolute;
    top: -40px;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #0284c7, #8B5CF6, #10B981, #B45309, #4F46E5);
    border-radius: 99px;
}

.mobile-brand {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 28px;
}

.mobile-brand-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: linear-gradient(135deg, #0284c7, #8B5CF6);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.3rem;
    color: #fff;
}

.mobile-brand-text {
    font-size: 1.4rem;
    font-weight: 800;
    color: var(--text-main);
}

@media (min-width: 900px) { .mobile-brand { display: none; } }

.back-home-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: var(--text-sub);
    font-size: 13.5px;
    font-weight: 600;
    text-decoration: none;
    margin-bottom: 24px;
    transition: color 0.2s ease, transform 0.2s ease;
}

.back-home-link:hover {
    color: #0284c7;
    transform: translateX(-3px);
}

.login-heading {
    margin-bottom: 8px;
    font-size: 30px;
    font-weight: 800;
    letter-spacing: -0.02em;
    color: var(--text-main);
}

.login-sub {
    font-size: 14px;
    color: var(--text-muted);
    margin-bottom: 28px;
    line-height: 1.5;
}

.heading-line {
    width: 50px;
    height: 4px;
    background: linear-gradient(90deg, #0284c7 0%, #8B5CF6 100%);
    border-radius: 99px;
    margin-bottom: 20px;
}

.login-error {
    display: flex;
    align-items: center;
    gap: 10px;
    background: #FEF2F2;
    border: 1.5px solid #FCA5A5;
    border-radius: var(--r);
    padding: 12px 16px;
    margin-bottom: 22px;
    font-size: 13.5px;
    color: #DC2626;
    font-weight: 500;
}

body.dark-mode .login-error {
    background: #2C1517;
    border-color: #7F1D1D;
    color: #FCA5A5;
}

.field { margin-bottom: 22px; }
.field-label {
    display: block;
    font-size: 11.5px;
    font-weight: 700;
    letter-spacing: 0.05em;
    text-transform: uppercase;
    color: var(--text-sub);
    margin-bottom: 8px;
}

.field-wrap { position: relative; }
.field-icon {
    position: absolute;
    left: 15px;
    top: 50%;
    transform: translateY(-50%);
    color: #94A3B8;
    font-size: 15px;
    pointer-events: none;
    transition: color 0.2s ease;
}

.field-input {
    width: 100%;
    background: var(--bg-input);
    border: 1.5px solid var(--border-color);
    border-radius: var(--r);
    padding: 14px 44px;
    font-size: 14.5px;
    font-family: inherit;
    color: var(--text-main);
    transition: var(--ease);
    outline: none;
}

.field-input:focus {
    border-color: #0284c7;
    background: var(--bg-card);
    box-shadow: 0 0 0 4px rgba(2, 132, 199, 0.15);
}

.field-wrap:focus-within .field-icon {
    color: #0284c7;
}

.field-btn {
    position: absolute;
    right: 14px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    cursor: pointer;
    color: #94A3B8;
    padding: 6px;
    outline: none;
    transition: color 0.2s ease;
    font-size: 14px;
}

.field-btn:hover { color: #0284c7; }

.btn-login {
    width: 100%;
    margin-top: 10px;
    padding: 16px;
    border: none;
    border-radius: var(--r);
    font-family: inherit;
    font-size: 15px;
    font-weight: 700;
    cursor: pointer;
    background: linear-gradient(135deg, #0284c7 0%, #8B5CF6 100%);
    color: #ffffff;
    transition: var(--ease);
    box-shadow: 0 8px 24px rgba(2, 132, 199, 0.3);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
}

.btn-login:hover:not(:disabled) {
    background: linear-gradient(135deg, #0369a1 0%, #7C3AED 100%);
    transform: translateY(-2px) scale(1.01);
    box-shadow: 0 12px 32px rgba(139, 92, 246, 0.4);
}

.btn-login:disabled { opacity: 0.65; cursor: not-allowed; }

.btn-text, .btn-loading { display: flex; align-items: center; justify-content: center; gap: 8px; }
.btn-loading { display: none; }

@keyframes spin { to { transform: rotate(360deg); } }
.spin { animation: spin 0.8s linear infinite; display: inline-block; }

.login-footer {
    margin-top: 38px;
    text-align: center;
    font-size: 11.5px;
    color: var(--text-muted);
    line-height: 1.6;
}
</style>
</head>
<body class="light-mode">

    <!-- FLOATING MULTI-COLOR ANIMATED MESH & ORBS -->
    <div class="mesh-bg">
        <div class="orb orb-1"></div>
        <div class="orb orb-2"></div>
        <div class="orb orb-3"></div>
        <div class="orb orb-4"></div>
    </div>

    <!-- 1. FLOATING SUN / MOON ANIMATED TOGGLE BUTTON -->
    <div class="theme-toggle-wrap">
        <button type="button" class="theme-toggle-btn" id="themeToggleBtn" onclick="toggleMode()" title="Cambiar tema Día / Noche">
            <div class="toggle-track">
                <div class="toggle-thumb">
                    <i class="fa-solid fa-sun icon-sun"></i>
                    <i class="fa-solid fa-moon icon-moon"></i>
                </div>
            </div>
            <span class="toggle-label" id="toggleLabel">Modo Claro</span>
        </button>
    </div>

<div class="scene">

    <!-- ── LEFT: BRANDING & VIBRANT MODULE BADGES ── -->
    <div class="panel-left">
        <div class="lb-logo">
            <i class="fa-solid fa-anchor"></i>
        </div>
        <div class="panel-brand-name">Sys<span class="gradient-txt">Port</span></div>
        <div class="panel-tagline">Autoridad Portuaria de Manta · Sistema Integrado Corporativo SSO</div>

        <!-- Módulos reales del sistema (Inspirado en Directorio de Módulos) -->
        <div class="module-badges-wrap">
            <div class="badge-item">
                <div class="badge-icon"><i class="fa-solid fa-users"></i></div>
                <span>Talento Humano</span>
            </div>
            <div class="badge-item">
                <div class="badge-icon"><i class="fa-solid fa-boxes-stacked"></i></div>
                <span>Control de Bienes</span>
            </div>
            <div class="badge-item">
                <div class="badge-icon"><i class="fa-solid fa-anchor"></i></div>
                <span>Bitácoras Portuarias</span>
            </div>
        </div>
    </div>

    <!-- ── RIGHT: LOGIN FORM ── -->
    <div class="panel-right">
        <div class="login-card">
            <div class="card-top-accent"></div>

            <div class="mobile-brand">
                <div class="mobile-brand-icon">
                    <i class="fa-solid fa-anchor"></i>
                </div>
                <div class="mobile-brand-text">SysPort</div>
            </div>

            <a href="<?= APP_URL ?>" class="back-home-link">
                <i class="fa-solid fa-arrow-left-long"></i> Volver al inicio
            </a>

            <div class="login-heading">Iniciar sesión</div>
            <div class="heading-line"></div>
            <div class="login-sub">Ingrese sus credenciales de acceso al sistema portuario.</div>

            <?php if (!empty($error)): ?>
            <div class="login-error" role="alert">
                <i class="fa-solid fa-circle-exclamation"></i>
                <span><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <?php endif; ?>

            <form method="POST" action="<?= APP_URL ?>/login" id="login-form" novalidate autocomplete="off">
                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf ?? '', ENT_QUOTES, 'UTF-8') ?>">

                <div class="field">
                    <label class="field-label" for="username">Usuario Institucional</label>
                    <div class="field-wrap">
                        <i class="fa-solid fa-user field-icon"></i>
                        <input
                            type="text"
                            id="username"
                            name="username"
                            class="field-input"
                            placeholder="nombre.apellido o cédula"
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
                            placeholder="••••••••••••"
                            autocomplete="current-password"
                            required>
                        <button type="button" class="field-btn" id="toggle-pass" aria-label="Mostrar u ocultar contraseña" title="Mostrar u ocultar contraseña">
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
                <strong>Autoridad Portuaria de Manta</strong><br>
                Sistema Integrado Corporativo &copy; <?= date('Y') ?>
            </div>
        </div>
    </div>

</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    // Default theme mode (Light Mode default)
    let savedMode = localStorage.getItem('apm_login_mode');
    if (!savedMode) {
        savedMode = 'light';
        localStorage.setItem('apm_login_mode', 'light');
    }
    applyMode(savedMode);

    // Password toggle
    const toggleBtn = document.getElementById('toggle-pass');
    const pwdInput = document.getElementById('password');
    const eyeIcon = document.getElementById('eye-icon');

    if (toggleBtn && pwdInput && eyeIcon) {
        toggleBtn.addEventListener('click', function() {
            const show = pwdInput.type === 'password';
            pwdInput.type = show ? 'text' : 'password';
            eyeIcon.className = show ? 'fa-regular fa-eye-slash' : 'fa-regular fa-eye';
        });
    }

    // Form submit loading
    const loginForm = document.getElementById('login-form');
    if (loginForm) {
        loginForm.addEventListener('submit', function() {
            const btnTxt = document.getElementById('btn-text');
            const btnLoad = document.getElementById('btn-loading');
            const btn = document.getElementById('login-btn');
            if (btnTxt) btnTxt.style.display = 'none';
            if (btnLoad) btnLoad.style.display = 'flex';
            if (btn) btn.disabled = true;
        });
    }
});

// Toggle between Light and Dark mode
function toggleMode() {
    const isDark = document.body.classList.contains('dark-mode') || document.documentElement.classList.contains('portal-dark-mode');
    const newMode = isDark ? 'light' : 'dark';
    applyMode(newMode);
    localStorage.setItem('apm_login_mode', newMode);
    localStorage.setItem('apm_theme_mode', newMode);
}

// Apply theme mode classes and update Sun/Moon label
function applyMode(mode) {
    const label = document.getElementById("toggleLabel");
    const isDark = (mode === 'dark');

    if (isDark) {
        document.documentElement.setAttribute('data-theme', 'dark');
        document.documentElement.setAttribute('data-bs-theme', 'dark');
        document.documentElement.classList.add('portal-dark-mode');
        document.body.classList.add('dark-mode', 'portal-dark-mode');
        document.body.classList.remove('light-mode');
        if (label) label.textContent = 'Modo Oscuro';
    } else {
        document.documentElement.setAttribute('data-theme', 'light');
        document.documentElement.setAttribute('data-bs-theme', 'light');
        document.documentElement.classList.remove('portal-dark-mode');
        document.body.classList.remove('dark-mode', 'portal-dark-mode');
        document.body.classList.add('light-mode');
        if (label) label.textContent = 'Modo Claro';
    }
}
</script>

</body>
</html>
