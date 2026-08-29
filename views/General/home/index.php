<?php
/**
 * Public Landing Page - Operations Hub and Modules Directorio
 */
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$baseUrl = dirname($scriptName);
$baseUrl = str_replace('\\', '/', $baseUrl);
if ($baseUrl === '/' || $baseUrl === '\\') {
    $baseUrl = '';
}
$isLoggedIn = isset($_SESSION['user_id']) && !isset($_GET['preview']);
// El hero se adapta según lo que realmente haya publicado el admin en
// /admin/landing — nunca rellena con contenido falso/duplicado (antes el
// carrusel de noticias reusaba las fotos de fondo cuando estaba vacío).
$tieneNoticias = !empty($noticias);
$tieneConsejos = !empty($consejos);
// Sin noticias ni consejos publicados: bajo el tema institucional (t1) el
// fondo pasa a blanco liso en vez del overlay oscuro por defecto -- ese
// overlay está pensado para cuando SÍ hay contenido/fotos de fondo detrás.
$sinContenido = !$tieneNoticias && !$tieneConsejos;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SysPort — Portal Corporativo Único (APM)</title>
    
    <!-- Google Fonts pairing: Fira Sans (Body) & Fira Code (Technical Metrics & Headers) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700;800&family=Fira+Code:wght@400;500;600;700&family=Fira+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Link our existing variables and global styles -->
    <link rel="stylesheet" href="<?= $baseUrl ?>/css/variables.css">
    <link rel="stylesheet" href="<?= $baseUrl ?>/css/style.css">
    
    <!-- Lucide Icons and FontAwesome for fallback -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* Specific CSS overrides and animations from the original unificado file for the public layout */
        .portal-layout-body {
            background-color: var(--bg-app);
            color: var(--text-app);
            font-family: 'Fira Sans', sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            margin: 0;
            overflow-x: hidden;
        }
        
        .portal-nav {
            padding: 24px 48px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            /* Fijo con el mismo azul institucional del menú lateral de portal_apm
               (ver css/style.css .sidebar --bg-sidebar) — sin foto de fondo. */
            background: #075177;
            border-bottom: 2px solid var(--border-app);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.25);
            position: relative;
        }

        .portal-logo-text {
            color: #ffffff;
            font-size: 18px;
            font-weight: 700;
            text-shadow: 0 2px 4px rgba(0,0,0,0.5);
        }

        .portal-nav-folder-btn {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: #ffffff;
            padding: 10px 20px;
            border-radius: var(--r-md);
            font-size: 14px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .portal-nav-folder-btn:hover {
            background: var(--accent-app);
            border-color: var(--accent-app);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(2, 132, 199, 0.3);
        }

        .portal-hero {
            display: grid;
            grid-template-columns: 1.05fr 0.95fr;
            gap: 32px;
            max-width: 1400px;
            margin: 0 auto;
            width: 100%;
            padding: 40px 48px;
            align-items: stretch;
            flex: 1;
        }

        @media (max-width: 1024px) {
            .portal-hero {
                grid-template-columns: 1fr;
                padding: 24px;
            }
        }

        @media (max-width: 480px) {
            .portal-hero {
                padding: 16px;
                gap: 20px;
            }
        }

        /* Sin noticias ni consejos publicados: colapsa a una sola columna
           centrada — nunca se muestra una caja vacía o contenido relleno. */
        .portal-hero.hero-single {
            grid-template-columns: 1fr;
            justify-items: center;
        }

        .portal-hero.hero-single .portal-hero-left {
            max-width: 760px;
            width: 100%;
        }

        .hero-glass-card {
            background: var(--surface-app);
            backdrop-filter: var(--backdrop);
            border: 1.5px solid var(--border-app);
            border-radius: var(--r-lg);
            padding: clamp(22px, 4vw, 36px);
            box-shadow: var(--shadow-app);
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            justify-content: center;
            height: 100%;
        }

        .hero-glass-card:hover {
            transform: translateY(-3px);
            border-color: var(--border-hover);
        }

        .ph-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 12px;
            background: rgba(2, 132, 199, 0.15);
            color: var(--accent-hover);
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 16px;
            width: fit-content;
        }

        .ph-title {
            font-family: 'Fira Code', monospace;
            font-size: clamp(1.65rem, 1.1rem + 2.2vw, 2.6rem);
            line-height: 1.15;
            margin: 0 0 16px 0;
            color: var(--text-app);
        }

        .ph-title-sub {
            font-size: 0.4em;
            font-weight: 300;
            opacity: 0.8;
            margin-left: 8px;
            display: inline-block;
        }

        .ph-sub {
            color: var(--text-muted);
            font-size: clamp(13px, 12px + 0.3vw, 15px);
            line-height: 1.6;
            margin-bottom: 28px;
        }

        .hero-features-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
        }

        @media (max-width: 600px) {
            .hero-features-grid {
                grid-template-columns: 1fr;
            }
        }

        .h-feat {
            position: relative;
            background: rgba(255, 255, 255, 0.015);
            border: 1px solid var(--border-app);
            padding: 18px 16px 16px;
            border-radius: var(--r-md);
            transition: all 0.3s ease;
        }

        .h-feat-num {
            position: absolute;
            top: -10px;
            left: 14px;
            width: 22px;
            height: 22px;
            border-radius: 50%;
            background: var(--accent-hover);
            color: #fff;
            font-size: 11px;
            font-weight: 800;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 8px rgba(2, 132, 199, .4);
        }

        .h-feat-icon {
            font-size: 20px;
            margin-bottom: 12px;
        }

        .h-feat-title {
            font-weight: 700;
            color: var(--text-app);
            margin-bottom: 6px;
            font-size: 14px;
        }

        .h-feat-desc {
            font-size: 11px;
            color: var(--text-muted);
            line-height: 1.4;
        }

        .quick-services-card {
            background: var(--surface-app);
            backdrop-filter: var(--backdrop);
            border: 1.5px solid var(--border-app);
            border-radius: var(--r-lg);
            padding: 24px 28px;
            box-shadow: var(--shadow-app);
        }

        .qs-title {
            display: flex;
            align-items: center;
            gap: 10px;
            font-family: 'Fira Code', monospace;
            font-weight: 700;
            font-size: 16px;
            color: var(--text-app);
            margin-bottom: 20px;
            border-bottom: 1px solid var(--border-app);
            padding-bottom: 12px;
        }

        .qs-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 14px;
        }

        .qs-btn {
            background: rgba(255, 255, 255, 0.015);
            border: 1.5px solid var(--border-app);
            border-radius: var(--r-md);
            padding: 16px;
            color: var(--text-app);
            cursor: pointer;
            text-align: left;
            display: flex;
            align-items: center;
            gap: 14px;
            transition: all 0.3s ease;
        }

        .qs-btn:hover {
            background: rgba(2, 132, 199, 0.08);
            border-color: var(--primary-hover);
            transform: translateY(-2px);
        }

        .qs-btn-icon {
            font-size: 20px;
            color: var(--accent-hover);
        }

        .portal-hero-right {
            display: flex;
            flex-direction: column;
        }

        /* --- News image carousel (noticias publicadas con imagen) --- */
        .news-image-carousel {
            position: relative;
            flex: 1;
            min-height: 420px;
            border-radius: var(--r-lg);
            overflow: hidden;
            border: 1.5px solid var(--border-app);
            box-shadow: var(--shadow-app);
            background: #0a1929;
        }

        @media (max-width: 1024px) {
            .news-image-carousel { min-height: 340px; }
        }

        @media (max-width: 480px) {
            .news-image-carousel { min-height: 260px; }
        }

        .nic-slide {
            position: absolute;
            inset: 0;
            display: block;
            opacity: 0;
            transition: opacity .9s ease;
            text-decoration: none;
        }

        .nic-slide.active {
            opacity: 1;
            z-index: 1;
        }

        /* Copia de fondo, desenfocada y ampliada: llena el marco sin dejar
           bandas vacías cuando la imagen real no coincide con el ratio del
           card. La copia de primer plano (contain) es la que se ve nítida
           y siempre completa, sin recortes. */
        .nic-slide-bg {
            position: absolute;
            inset: 0;
            background-size: cover;
            background-position: center;
            filter: blur(26px) brightness(.5) saturate(1.15);
            transform: scale(1.15);
        }

        .nic-slide-fg {
            position: absolute;
            inset: 0;
            z-index: 1;
            background-size: contain;
            background-repeat: no-repeat;
            background-position: center;
        }

        .nic-slide::after {
            content: '';
            position: absolute;
            inset: 0;
            z-index: 2;
            background: linear-gradient(to top, rgba(5, 10, 20, .85) 0%, rgba(5, 10, 20, .15) 42%, transparent 68%);
        }

        .nic-caption {
            position: absolute;
            left: 0;
            right: 0;
            bottom: 0;
            padding: 20px 22px 18px;
            z-index: 3;
        }

        .nic-empty {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #94a3b8;
            font-size: 13px;
            z-index: 1;
        }

        /* --- Panel vertical de Consejos y Novedades (cuando NO hay noticias
           con imagen — ocupa el lugar del carrusel, en vez de dejarlo vacío) --- */
        .tips-panel-tall {
            position: relative;
            flex: 1;
            min-height: 420px;
            border-radius: var(--r-lg);
            border: 1.5px solid var(--border-app);
            box-shadow: var(--shadow-app);
            background: var(--surface-app);
            overflow: hidden;
        }

        @media (max-width: 1024px) { .tips-panel-tall { min-height: 320px; } }
        @media (max-width: 480px) { .tips-panel-tall { min-height: 260px; } }

        .tpt-badge {
            position: absolute;
            top: 18px;
            left: 20px;
            z-index: 2;
            font-size: 11px;
            font-weight: 700;
            color: var(--accent-hover);
            display: flex;
            align-items: center;
            gap: 6px;
            text-transform: uppercase;
            letter-spacing: .04em;
        }

        .tpt-slide {
            position: absolute;
            inset: 56px 32px 32px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            gap: 16px;
            opacity: 0;
            transition: opacity .6s ease;
        }

        .tpt-slide.active { opacity: 1; z-index: 1; }

        .tpt-icon {
            font-size: 26px;
            color: var(--accent-hover);
        }

        .tpt-text {
            font-size: clamp(16px, 14px + 0.8vw, 21px);
            font-weight: 600;
            color: var(--text-app);
            line-height: 1.5;
            max-width: 420px;
        }

        .tpt-cta {
            font-size: 12px;
            font-weight: 700;
            color: var(--accent-hover);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .tpt-cta:hover { text-decoration: underline; }

        .tpt-dots {
            position: absolute;
            bottom: 16px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 2;
            display: flex;
            gap: 6px;
        }

        .tpt-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            border: 0;
            background: var(--border-app);
            cursor: pointer;
            padding: 0;
            transition: all .25s ease;
        }

        .tpt-dot.active {
            background: var(--accent-hover);
            width: 20px;
            border-radius: 4px;
        }

        .nic-caption-text {
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
            color: #fff;
            font-weight: 600;
            font-size: clamp(14.5px, 13px + 0.3vw, 16.5px);
            line-height: 1.45;
            text-shadow: 0 1px 3px rgba(0, 0, 0, .4);
        }

        .nic-caption-cta {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-top: 8px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .04em;
            color: #38BDF8;
        }

        .nic-badge {
            position: absolute;
            top: 14px;
            left: 16px;
            z-index: 2;
            background: rgba(5, 10, 20, .55);
            backdrop-filter: blur(6px);
            border: 1px solid rgba(255, 255, 255, .15);
            color: #fff;
            font-size: 11px;
            font-weight: 700;
            padding: 5px 12px;
            border-radius: 999px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .nic-dots {
            position: absolute;
            right: 16px;
            bottom: 16px;
            z-index: 2;
            display: flex;
            gap: 6px;
        }

        .nic-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            border: 0;
            background: rgba(255, 255, 255, .35);
            cursor: pointer;
            padding: 0;
            transition: all .25s ease;
        }

        .nic-dot.active {
            background: #fff;
            width: 20px;
            border-radius: 4px;
        }

        .portal-tips-wrap {
            max-width: 1400px;
            margin: 0 auto;
            width: 100%;
            padding: 0 48px 40px;
            position: relative;
            z-index: 10;
        }

        @media (max-width: 1024px) {
            .portal-tips-wrap { padding: 0 24px 24px; }
        }

        @media (max-width: 480px) {
            .portal-tips-wrap { padding: 0 16px 20px; }
        }

        .news-ticker-card {
            background: var(--surface-app);
            border: 1.5px solid var(--border-app);
            border-radius: var(--r-lg);
            padding: 16px 24px;
            display: flex;
            align-items: center;
            gap: 16px;
            overflow: hidden;
            box-shadow: var(--shadow-app);
        }

        .news-label {
            font-weight: 700;
            color: var(--accent-hover);
            display: flex;
            align-items: center;
            gap: 8px;
            white-space: nowrap;
        }

        .news-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: #22c55e;
            display: inline-block;
            flex-shrink: 0;
            box-shadow: 0 0 0 0 rgba(34, 197, 94, .6);
            animation: newsPulse 2s infinite;
        }

        @keyframes newsPulse {
            0% { box-shadow: 0 0 0 0 rgba(34, 197, 94, .5); }
            70% { box-shadow: 0 0 0 6px rgba(34, 197, 94, 0); }
            100% { box-shadow: 0 0 0 0 rgba(34, 197, 94, 0); }
        }

        .news-container {
            position: relative;
            flex: 1;
            height: 24px;
            overflow: hidden;
        }

        .news-slide {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            display: flex;
            align-items: center;
            gap: 8px;
            opacity: 0;
            transform: translateY(20px);
            transition: all 0.5s ease;
            font-size: 13px;
            color: var(--text-muted);
            white-space: nowrap;
            overflow: hidden;
        }

        .news-slide.active {
            opacity: 1;
            transform: translateY(0);
        }

        .news-slide-text {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .news-slide-link {
            flex-shrink: 0;
            margin-left: 4px;
            font-size: 11px;
            font-weight: 700;
            color: var(--accent-hover);
            text-decoration: none;
            white-space: nowrap;
        }

        .news-slide-link:hover {
            text-decoration: underline;
        }

        .news-counter {
            flex-shrink: 0;
            font-size: 10.5px;
            font-weight: 700;
            color: var(--text-muted);
            background: var(--accent-app);
            border: 1px solid var(--border-app);
            padding: 3px 9px;
            border-radius: 999px;
        }

        /* Modal Overlays & Styles */
        .folders-overlay,
        .services-overlay,
        .w-modal-overlay {
            position: fixed;
            inset: 0;
            z-index: 10000;
            background: rgba(5, 10, 20, 0.85);
            backdrop-filter: blur(16px);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s ease;
            padding: 20px;
        }

        .folders-overlay.show,
        .services-overlay.show,
        .w-modal-overlay.show {
            opacity: 1;
            pointer-events: all;
        }

        .folders-modal,
        .services-modal,
        .w-modal-card {
            background: var(--surface-app);
            border: 1.5px solid var(--border-app);
            border-radius: var(--r-lg);
            box-shadow: var(--shadow-xl);
            transform: translateY(30px) scale(0.96);
            opacity: 0;
            transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
            position: relative;
            color: var(--text-app);
        }

        .folders-modal {
            width: 96vw;
            max-width: 1100px;
            max-height: 88vh;
            overflow-y: auto;
            padding: 28px 36px;
        }

        .services-modal {
            width: 96vw;
            max-width: 680px;
            padding: 32px;
        }

        .w-modal-card {
            width: 100%;
            max-width: 600px;
            padding: 36px;
        }

        .folders-overlay.show .folders-modal,
        .services-overlay.show .services-modal,
        .w-modal-overlay.show .w-modal-card {
            transform: translateY(0) scale(1);
            opacity: 1;
        }

        .fm-close,
        .sm-close,
        .w-close {
            position: absolute;
            top: 20px;
            right: 20px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--border-app);
            color: var(--text-muted);
            width: 36px;
            height: 36px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .fm-close:hover,
        .sm-close:hover,
        .w-close:hover {
            background: var(--accent-app);
            color: #ffffff;
            border-color: var(--accent-app);
        }

        .fm-title,
        .sm-header-block {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 24px;
        }

        .fm-title img,
        .w-logo-img {
            height: 48px;
            width: auto;
            object-fit: contain;
        }

        .fm-title h2,
        .sm-title-text h2 {
            font-family: 'Sora', sans-serif;
            font-size: 22px;
            font-weight: 800;
            margin: 0;
            color: var(--text-app);
        }

        .fm-subtitle,
        .sm-title-text p {
            color: var(--text-muted);
            font-size: 13.5px;
            margin-top: 4px;
        }

        .folders-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            margin-top: 20px;
        }

        .folders-grid .card-span-3 {
            grid-column: span 3;
        }

        @media (max-width: 900px) {
            .folders-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .folders-grid .card-span-3 {
                grid-column: auto !important;
            }
        }

        @media (max-width: 600px) {
            .folders-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Folder Cards style */
        .folder-card {
            position: relative;
            background: var(--surface-app);
            border: 1.5px solid var(--border-app);
            border-radius: 14px;
            padding: 24px 22px 20px;
            cursor: pointer;
            transition: var(--ease);
            display: flex;
            flex-direction: column;
            gap: 0;
            text-decoration: none;
            color: var(--text-app);
            overflow: hidden;
        }

        body.t1 .folder-card {
            background: #F8FAFC;
            border-color: #E2E8F0;
        }

        .folder-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: var(--accent-hover);
        }

        .folder-card:hover {
            transform: translateY(-3px);
            border-color: var(--accent-hover);
            background: rgba(2, 132, 199, 0.05);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }

        .fc-header {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .fc-icon {
            font-size: 22px;
            color: var(--accent-hover);
        }

        .fc-name {
            font-weight: 700;
            color: var(--text-app);
            font-size: 15px;
        }

        .fc-desc {
            font-size: 12px;
            color: var(--text-muted);
            line-height: 1.5;
            flex: 1;
        }

        .fc-action {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 11px;
            font-weight: 700;
            color: var(--accent-hover);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-top: 8px;
        }

        .folder-card:hover .fc-action {
            color: #ffffff;
        }

        /* Welcome modal carousel */
        .w-logo-hdr {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 24px;
            border-bottom: 1px solid var(--border-app);
            padding-bottom: 16px;
        }

        .w-logo-title {
            font-size: 18px;
            font-weight: 800;
            color: var(--text-app);
        }

        .w-carousel {
            position: relative;
            height: 240px;
            margin-bottom: 24px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .wc-slide {
            display: none;
            text-align: left;
        }

        .wc-slide.active {
            display: block;
            animation: fadeIn 0.4s ease;
        }

        .wc-title {
            font-family: 'Fira Code', monospace;
            font-size: 18px;
            font-weight: 700;
            color: var(--accent-hover);
            margin-bottom: 12px;
        }

        .wc-desc {
            font-size: 13.5px;
            line-height: 1.6;
            color: var(--text-muted);
        }

        .wc-dots {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-bottom: 28px;
        }

        .wc-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--border-app);
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .wc-dot.active {
            background: var(--accent-hover);
            width: 20px;
            border-radius: 4px;
        }

        .w-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        /* Specific styles for Quick services modal contents */
        .sched-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-top: 16px;
        }

        .sched-item {
            background: rgba(255, 255, 255, 0.015);
            border: 1px solid var(--border-app);
            border-radius: var(--r-md);
            padding: 14px 18px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .sc-left {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .sc-name {
            font-weight: 700;
            color: var(--text-app);
            font-size: 14px;
        }

        .sc-line {
            font-size: 11px;
            color: var(--text-muted);
        }

        .sc-right {
            text-align: right;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .sc-eta {
            font-size: 12px;
            font-weight: 700;
            color: var(--accent-hover);
        }

        .sc-dock {
            font-size: 11px;
            color: var(--text-muted);
        }

        .calc-row {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
            margin-bottom: 16px;
        }

        .sm-field {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .sm-field label {
            font-size: 12px;
            font-weight: 700;
            color: var(--text-app);
        }

        .sm-field select,
        .sm-field input {
            background: var(--bg-sidebar, rgba(14, 22, 38, 0.6));
            border: 1.5px solid var(--border-app);
            border-radius: var(--r-md);
            padding: 10px 14px;
            color: var(--text-app);
            font-size: 13px;
            outline: none;
            transition: all 0.3s ease;
        }

        .sm-field select:focus,
        .sm-field input:focus {
            border-color: var(--primary-hover);
        }

        .calc-result-box {
            background: rgba(2, 132, 199, 0.05);
            border: 1px solid rgba(2, 132, 199, 0.2);
            border-radius: var(--r-md);
            padding: 20px;
            margin-top: 24px;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .cr-row {
            display: flex;
            justify-content: space-between;
            font-size: 12.5px;
            color: var(--text-muted);
        }

        .cr-row .v {
            color: var(--text-app);
            font-weight: 700;
        }

        .cr-total {
            border-top: 1px solid rgba(2, 132, 199, 0.2);
            padding-top: 12px;
            font-size: 14px;
            font-weight: 700;
            color: var(--text-app);
        }

        .cr-total .v {
            font-size: 18px;
            color: var(--accent-hover);
            font-family: 'Fira Code', monospace;
        }

        .search-bar-wrap {
            display: flex;
            gap: 10px;
            margin-bottom: 24px;
        }

        .sm-search-input {
            flex: 1;
            background: var(--bg-sidebar, rgba(14, 22, 38, 0.6));
            border: 1.5px solid var(--border-app);
            border-radius: var(--r-md);
            padding: 12px 16px;
            color: var(--text-app);
            font-size: 13px;
            outline: none;
        }

        .sm-search-input:focus {
            border-color: var(--primary-hover);
        }

        .sm-search-btn {
            background: var(--accent-app);
            border: none;
            border-radius: var(--r-md);
            padding: 0 20px;
            color: #ffffff;
            font-weight: 700;
            font-size: 13px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .sm-search-btn:hover {
            background: var(--primary-hover);
        }

        .manifest-result {
            background: rgba(16, 185, 129, 0.05);
            border: 1px solid rgba(16, 185, 129, 0.2);
            border-radius: var(--r-md);
            padding: 20px;
            display: none;
            flex-direction: column;
            gap: 16px;
        }

        .mr-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid rgba(16, 185, 129, 0.2);
            padding-bottom: 12px;
        }

        .mr-code {
            font-family: 'Fira Code', monospace;
            font-weight: 700;
            font-size: 16px;
            color: var(--text-app);
        }

        .dr-badge {
            background: #10B981;
            color: #ffffff;
            font-size: 11px;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 12px;
            text-transform: uppercase;
        }

        .mr-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
        }

        .mr-item {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .mr-lbl {
            font-size: 11px;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .mr-val {
            font-size: 13px;
            font-weight: 700;
            color: var(--text-app);
        }

        .portal-footer {
            padding: 20px 48px;
            text-align: center;
            font-size: 11px;
            color: var(--text-muted);
            border-top: 1px solid var(--border-app);
            background: rgba(9, 13, 22, 0.4);
            backdrop-filter: blur(10px);
        }

        /* --- BACKGROUND SLIDESHOW --- */
        .slideshow-bg {
            position: fixed;
            inset: 0;
            z-index: 0;
            width: 100vw;
            height: 100vh;
        }
        .slide-img {
            position: absolute;
            inset: 0;
            background-size: cover;
            background-position: center;
            opacity: 0;
            transition: opacity 1.5s ease-in-out;
        }
        .slide-img.active {
            opacity: 1;
        }
        .slideshow-overlay {
            position: absolute;
            inset: 0;
            z-index: 1;
            transition: background 0.3s ease;
        }
        body.t1 .slideshow-overlay {
            background: radial-gradient(circle at center, rgba(30, 58, 95, 0.4) 0%, rgba(20, 30, 55, 0.88) 100%);
        }
        body.t2 .slideshow-overlay {
            background: radial-gradient(circle at center, rgba(15, 23, 42, 0.45) 0%, rgba(5, 10, 20, 0.95) 100%);
        }
        body.t3 .slideshow-overlay {
            background: radial-gradient(circle at center, rgba(12, 74, 110, 0.35) 0%, rgba(7, 29, 58, 0.95) 100%);
        }

        /* Tema Institucional (t1) sin noticias ni consejos publicados: fondo
           blanco liso en vez del overlay oscuro (ese overlay es para cuando
           hay fotos de fondo/contenido detrás que conviene oscurecer). */
        body.t1.sin-contenido .slideshow-bg,
        body.t1.sin-contenido .slide-img,
        body.t1.sin-contenido .slideshow-overlay {
            background: #ffffff !important;
        }

        /* Ensure all other content has a higher z-index and body background is transparent so the slideshow is visible */
        .portal-layout-body {
            background: transparent !important;
        }
        .portal-nav,
        .portal-hero,
        .portal-footer {
            position: relative;
            z-index: 10;
        }

        /* Responsive scaling for card span 3 */
        @media (max-width: 900px) {
            .folders-grid .card-span-3 {
                grid-column: auto !important;
            }
        }

        /* ══════════════════════════════════════════
           THEME VISIBILITY FIXES
           ══════════════════════════════════════════ */

        /* --- BTN-PRIMARY & BTN base (welcome modal buttons) --- */
        .btn {
            padding: 8px 20px;
            border-radius: var(--r-md);
            font-weight: 700;
            cursor: pointer;
            font-size: 13.5px;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-family: 'Fira Sans', sans-serif;
        }
        .btn-primary {
            background: var(--accent-hover);
            color: #ffffff !important;
            border: none;
            box-shadow: 0 4px 12px rgba(2, 132, 199, 0.25);
        }
        .btn-primary:hover {
            opacity: 0.88;
            transform: translateY(-1px);
            box-shadow: 0 6px 18px rgba(2, 132, 199, 0.35);
        }

        /* ── T1 INSTITUCIONAL (light) ── */
        body.t1 .h-feat {
            background: #F1F5F9;
            border-color: #CBD5E1;
        }
        body.t1 .h-feat:hover {
            background: #EBF4FD;
            border-color: var(--accent-hover);
        }
        body.t1 .qs-btn {
            background: #F8FAFC;
            border-color: #CBD5E1;
        }
        body.t1 .qs-btn:hover {
            background: #EBF4FD;
            border-color: var(--primary-hover);
        }
        body.t1 .quick-services-card {
            background: #ffffff;
        }
        body.t1 .news-ticker-card {
            background: #ffffff;
        }
        body.t1 .sched-item {
            background: #F8FAFC;
            border-color: #CBD5E1;
        }
        body.t1 .portal-footer {
            background: rgba(240, 244, 250, 0.98);
            backdrop-filter: blur(12px);
            border-top-color: #DDE4EF;
        }
        body.t1 .sm-search-btn {
            background: var(--primary-app);
            color: #ffffff;
        }
        body.t1 .sm-search-btn:hover {
            background: var(--primary-hover);
        }
        body.t1 .sm-search-input,
        body.t1 .sm-field select,
        body.t1 .sm-field input {
            background: #F8FAFC;
            border-color: #CBD5E1;
            color: var(--text-app);
        }
        body.t1 .sm-field select:focus,
        body.t1 .sm-field input:focus,
        body.t1 .sm-search-input:focus {
            border-color: var(--primary-hover);
            background: #ffffff;
        }
        body.t1 .calc-result-box {
            background: rgba(2, 132, 199, 0.06);
            border-color: rgba(2, 132, 199, 0.18);
        }
        body.t1 .cr-row { color: var(--text-muted); }
        body.t1 .cr-row .v { color: var(--text-app); }
        body.t1 .fm-close:hover,
        body.t1 .sm-close:hover,
        body.t1 .w-close:hover {
            background: var(--primary-app);
        }
        /* SSO developer console wrapper positioning */
        .section-container {
            position: relative;
            z-index: 10;
            max-width: 1400px;
            margin-left: auto;
            margin-right: auto;
            padding-left: 48px;
            padding-right: 48px;
        }
        @media (max-width: 1024px) {
            .section-container { padding-left: 24px; padding-right: 24px; }
        }
        /* SSO developer console: keep always-dark on t1 */
        body.t1 .section-container > .hero-glass-card {
            background: rgba(10, 25, 47, 0.96) !important;
            border-color: rgba(56, 189, 248, 0.18) !important;
        }

        /* ── T2 CYBER DARK & T3 PORTO: manifest error stays dark-friendly ── */
        body.t2 #manifestErrorMsg,
        body.t3 #manifestErrorMsg {
            background: rgba(239, 68, 68, 0.08) !important;
            border-color: rgba(239, 68, 68, 0.25) !important;
            color: #FCA5A5 !important;
        }
        body.t2 .portal-footer,
        body.t3 .portal-footer {
            background: rgba(5, 10, 20, 0.7);
        }

        /* ── Theme switcher active state indicator ── */
        .ts-btn { position: relative; }
        .ts-btn.active::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 50%;
            transform: translateX(-50%);
            width: 4px;
            height: 4px;
            border-radius: 50%;
            background: currentColor;
        }

        /* ── Login nav button ── */
        .portal-nav-login-btn {
            background: linear-gradient(135deg, #0284c7, #0369a1);
            border: none;
            color: #ffffff;
            padding: 10px 22px;
            border-radius: var(--r-md);
            font-size: 13.5px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 9px;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.25s ease;
            box-shadow: 0 4px 14px rgba(2, 132, 199, 0.4);
            letter-spacing: 0.02em;
        }
        .portal-nav-login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 22px rgba(2, 132, 199, 0.5);
            background: linear-gradient(135deg, #0369a1, #025c8a);
        }
        .portal-nav-login-btn:active {
            transform: translateY(0);
        }
    </style>
</head>
<body class="portal-layout-body t1<?= $sinContenido ? ' sin-contenido' : '' ?>">
    <script>
        (function() {
            var savedTheme = localStorage.getItem('apm_theme') || '1';
            document.body.classList.remove('t1', 't2', 't3');
            document.body.classList.add('t' + savedTheme);
        })();
    </script>

    <!-- Slideshow Background cycling smoothly -->
    <div class="slideshow-bg">
        <?php if (empty($imagenes)): ?>
        <div class="slide-img active" style="background:#0a1929;"></div>
        <?php else: foreach ($imagenes as $i => $ruta): ?>
        <div class="slide-img <?= $i === 0 ? 'active' : '' ?>" style="background-image: url('<?= $baseUrl ?>/<?= htmlspecialchars($ruta, ENT_QUOTES, 'UTF-8') ?>');"></div>
        <?php endforeach; endif; ?>
        <div class="slideshow-overlay"></div>
    </div>
    
    <!-- 1. Navbar Section -->
    <div class="portal-nav">
        <div class="portal-logo" style="display:flex; align-items:center; gap:16px;">
            <img src="<?= $baseUrl ?>/imgs/logoapm.png" alt="Logo APM" onerror="this.src='https://i.imgur.com/8QG4pQA.png'" style="height:60px; width:auto; object-fit:contain;">
            <div class="portal-logo-text" style="font-family: 'Sora', sans-serif; font-size: 22px; font-weight: 700; line-height: 1.2;">
                <span style="color: var(--accent-hover, #0284C7); font-weight: 800;">Sys</span>Port
                <span style="font-size: 14px; font-weight: 300; opacity: 0.85; margin-left: 6px;">| APM Manta</span>
                <div style="font-size: 11px; font-weight: 400; color: rgba(255, 255, 255, 0.7); margin-top: 3px; letter-spacing: 0.05em; font-family: 'Sora', sans-serif;">Portal Corporativo · Autoridad Portuaria de Manta</div>
            </div>
        </div>
        <div style="font-size:13px; display:flex; align-items:center; gap:14px; flex-wrap:wrap;">
            <button class="portal-nav-folder-btn" title="Directorio de Módulos" onclick="openFolders()">
                <i class="fa-solid fa-folder-open"></i>
                <span>Directorio de Módulos</span>
            </button>
            <?php if (!$isLoggedIn): ?>
            <a href="<?= APP_URL ?>/login" class="portal-nav-login-btn">
                <i class="fa-solid fa-right-to-bracket"></i>
                <span>Iniciar sesión</span>
            </a>
            <?php else: ?>
            <a href="<?= APP_URL ?>/dashboard" class="portal-nav-login-btn" style="background:linear-gradient(135deg,#059669,#047857);">
                <i class="fa-solid fa-table-columns"></i>
                <span>Ir al dashboard</span>
            </a>
            <?php endif; ?>
            <span style="background:rgba(0,0,0,0.25); padding:8px 16px; border-radius:30px; border:1px solid rgba(255,255,255,0.1); display:inline-flex; align-items:center; gap:6px;"><i class="fa-solid fa-headset"></i> Soporte: soporte@apm.gob.ec</span>
        </div>
    </div>

    <!-- 2. Balanced Two-Column Operations Hub Hero Grid -->
    <div class="portal-hero<?= (!$tieneNoticias && !$tieneConsejos) ? ' hero-single' : '' ?>">
        <!-- LEFT COLUMN -->
        <div class="portal-hero-left">
            <!-- Welcome Glass Card -->
            <div class="hero-glass-card">
                <div class="ph-badge"><i class="fa-solid fa-shield-halved"></i> SysPort · Acceso Unificado</div>
                <h1 class="ph-title">
                    <span style="color: var(--accent-hover, #0284C7); font-weight: 800;">Sys</span>Port
                    <span class="ph-title-sub">| Acceso Corporativo</span>
                </h1>
                <p class="ph-sub">Bienvenido a <strong>SysPort</strong>, el portal único de integración operativa y administrativa de la Autoridad Portuaria de Manta. Inicie sesión de manera segura para acceder a sus módulos correspondientes.</p>

                <div class="hero-features-grid">
                    <div class="h-feat">
                        <div class="h-feat-num">1</div>
                        <div class="h-feat-icon" style="color: #38BDF8;"><i class="fa-solid fa-id-card"></i></div>
                        <div class="h-feat-title">Ingresa con tu cédula</div>
                        <div class="h-feat-desc">Un solo número de identificación — sin usuarios ni contraseñas distintas por módulo.</div>
                    </div>
                    <div class="h-feat">
                        <div class="h-feat-num">2</div>
                        <div class="h-feat-icon" style="color: #34D399;"><i class="fa-solid fa-diagram-project"></i></div>
                        <div class="h-feat-title">Elige tu módulo</div>
                        <div class="h-feat-desc">Talento Humano, Control de Bienes o Bitácoras Portuarias, según tu perfil.</div>
                    </div>
                    <div class="h-feat">
                        <div class="h-feat-num">3</div>
                        <div class="h-feat-icon" style="color: #818CF8;"><i class="fa-solid fa-clipboard-check"></i></div>
                        <div class="h-feat-title">Acceso auditado</div>
                        <div class="h-feat-desc">Cada sesión queda registrada para control interno de la Autoridad Portuaria.</div>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($tieneNoticias || $tieneConsejos): ?>
        <!-- RIGHT COLUMN — solo existe si hay contenido real que mostrar -->
        <div class="portal-hero-right">
            <?php if ($tieneNoticias): ?>
            <!-- News Image Carousel (noticias publicadas, siempre con imagen — administrable
                 en /admin/landing, independiente de Consejos y Novedades). -->
            <div class="news-image-carousel" id="newsImgCarousel">
                <div class="nic-badge"><i class="fa-solid fa-newspaper"></i> Noticias APM</div>
                <?php foreach ($noticias as $i => $n): $tag = !empty($n['enlace']) ? 'a' : 'div'; ?>
                <<?= $tag ?> class="nic-slide <?= $i === 0 ? 'active' : '' ?>"
                    <?php if (!empty($n['enlace'])): ?>href="<?= htmlspecialchars($n['enlace'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener"<?php endif; ?>>
                    <div class="nic-slide-bg" style="background-image:url('<?= $baseUrl ?>/<?= htmlspecialchars($n['imagen'], ENT_QUOTES, 'UTF-8') ?>');"></div>
                    <div class="nic-slide-fg" style="background-image:url('<?= $baseUrl ?>/<?= htmlspecialchars($n['imagen'], ENT_QUOTES, 'UTF-8') ?>');"></div>
                    <div class="nic-caption">
                        <span class="nic-caption-text"><?= htmlspecialchars($n['texto'], ENT_QUOTES, 'UTF-8') ?></span>
                        <?php if (!empty($n['enlace'])): ?><span class="nic-caption-cta">Ver más <i class="fa-solid fa-arrow-up-right-from-square"></i></span><?php endif; ?>
                    </div>
                </<?= $tag ?>>
                <?php endforeach; ?>
                <?php if (count($noticias) > 1): ?>
                <div class="nic-dots">
                    <?php foreach ($noticias as $i => $n): ?>
                    <button type="button" class="nic-dot <?= $i === 0 ? 'active' : '' ?>" data-i="<?= $i ?>" aria-label="Noticia <?= $i + 1 ?>"></button>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <?php elseif ($tieneConsejos): ?>
            <!-- Sin noticias con imagen: el panel de Consejos y Novedades ocupa el lugar
                 del carrusel (versión vertical), en vez de dejar la columna vacía. -->
            <div class="tips-panel-tall" id="tipsPanelTall">
                <div class="tpt-badge"><i class="fa-solid fa-lightbulb"></i> Consejos y novedades</div>
                <?php foreach ($consejos as $i => $c): ?>
                <div class="tpt-slide <?= $i === 0 ? 'active' : '' ?>">
                    <span class="tpt-icon"><i class="fa-solid fa-circle-info"></i></span>
                    <p class="tpt-text"><?= htmlspecialchars($c['texto'], ENT_QUOTES, 'UTF-8') ?></p>
                    <?php if (!empty($c['enlace'])): ?>
                    <a class="tpt-cta" href="<?= htmlspecialchars($c['enlace'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">Ver más <i class="fa-solid fa-arrow-up-right-from-square"></i></a>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
                <?php if (count($consejos) > 1): ?>
                <div class="tpt-dots">
                    <?php foreach ($consejos as $i => $c): ?>
                    <button type="button" class="tpt-dot <?= $i === 0 ? 'active' : '' ?>" data-i="<?= $i ?>" aria-label="Consejo <?= $i + 1 ?>"></button>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <?php if ($tieneNoticias && $tieneConsejos): ?>
    <!-- Consejos y novedades del portal — franja aparte, separada del carrusel de noticias.
         Solo se muestra cuando YA existe el carrusel de noticias arriba (si no hay noticias,
         los consejos ya se ven en el panel vertical de la columna derecha). -->
    <div class="portal-tips-wrap">
        <div class="news-ticker-card">
            <div class="news-label"><span class="news-dot"></span> Consejos y novedades</div>
            <div class="news-container">
                <?php foreach ($consejos as $i => $consejo): ?>
                <div class="news-slide <?= $i === 0 ? 'active' : '' ?>">
                    <span class="news-slide-text"><?= htmlspecialchars($consejo['texto'], ENT_QUOTES, 'UTF-8') ?></span>
                    <?php if (!empty($consejo['enlace'])): ?>
                    <a href="<?= htmlspecialchars($consejo['enlace'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener" class="news-slide-link">Ver <i class="fa-solid fa-arrow-up-right-from-square"></i></a>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php if (count($consejos) > 1): ?>
            <div class="news-counter" id="newsCounter">1 / <?= count($consejos) ?></div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- 4. Folder Directory Overlay Modal -->
    <div class="folders-overlay" id="moduleFoldersOverlay" onclick="if(event.target===this) closeFolders()">
        <div class="folders-modal">
            <button class="fm-close" onclick="closeFolders()"><i class="fa-solid fa-xmark"></i></button>
            <div class="fm-title">
                <img src="<?= $baseUrl ?>/imgs/logoapm.png" alt="Logo APM" onerror="this.src='https://i.imgur.com/8QG4pQA.png'">
                <h2>Directorio de <span style="color:var(--primary-hover)">Módulos APM</span></h2>
            </div>
            <div class="fm-subtitle">Selecciona el departamento al cual deseas ingresar. La autenticación compartida (SSO) te permitirá navegar entre módulos autorizados de forma fluida.</div>
            
            <div class="folders-grid">
                <!-- 1. Talento Humano Card — tiene login propio (th_usuarios_sistema,
                     con MFA): igual que Control de Bienes, siempre entra directo al
                     módulo, sea con sesión del portal (puente de identidad) o sin
                     ella (su propio /login se muestra solo). -->
                <a href="<?= APP_URL . '/apps/talento_humano/' ?>" class="folder-card" style="--folder-color: #8B5CF6;">
                    <div class="fc-glow" style="background:#8B5CF6"></div>
                    <div style="position:absolute;top:0;left:0;right:0;height:3px;background:#8B5CF6"></div>
                    <div class="fc-icon" style="background:#8B5CF618;border:1px solid #8B5CF630"><i class="fa-solid fa-users" style="color:#8B5CF6"></i></div>
                    <div class="fc-dept">Módulo APM</div>
                    <div class="fc-name">Talento Humano</div>
                    <p class="fc-desc">Fichas técnicas de personal, expediente único del colaborador, contratos, adendas y control de nómina.</p>
                    <div class="fc-areas">
                        <span class="fc-area-tag" style="background:#8B5CF615;color:#8B5CF6">Fichas de personal</span>
                        <span class="fc-area-tag" style="background:#8B5CF615;color:#8B5CF6">Contratos y adendas</span>
                        <span class="fc-area-tag" style="background:#8B5CF615;color:#8B5CF6">Asistencia</span>
                    </div>
                    <button class="fc-action" style="background:#8B5CF6">
                        <i class="fa-solid fa-arrow-right-to-bracket"></i> Ingresar al módulo
                    </button>
                    <div class="fc-url"><i class="fa-solid fa-lock"></i>portal.apm.gob.ec/talento-humano/login</div>
                </a>

                <!-- 2. Control de Bienes Card — tiene login propio (inv_usuarios): siempre
                     entra directo al módulo, sea con sesión del portal (puente SSO) o sin
                     ella (su propio index.php?route=inv_login se muestra solo). -->
                <a href="<?= APP_URL . '/apps/control_bienes/' ?>" class="folder-card" style="--folder-color: #10B981;">
                    <div class="fc-glow" style="background:#10B981"></div>
                    <div style="position:absolute;top:0;left:0;right:0;height:3px;background:#10B981"></div>
                    <div class="fc-icon" style="background:#10B98118;border:1px solid #10B98130"><i class="fa-solid fa-boxes-stacked" style="color:#10B981"></i></div>
                    <div class="fc-dept">Módulo APM</div>
                    <div class="fc-name">Control de Bienes</div>
                    <p class="fc-desc">Inventario general de activos, ingresos y egresos de bodega, y catálogo de ítems del sistema.</p>
                    <div class="fc-areas">
                        <span class="fc-area-tag" style="background:#10B98115;color:#10B981">Inventario General</span>
                        <span class="fc-area-tag" style="background:#10B98115;color:#10B981">Bodega</span>
                        <span class="fc-area-tag" style="background:#10B98115;color:#10B981">Catálogo de Ítems</span>
                    </div>
                    <button class="fc-action" style="background:#10B981">
                        <i class="fa-solid fa-arrow-right-to-bracket"></i> Ingresar al módulo
                    </button>
                    <div class="fc-url"><i class="fa-solid fa-lock"></i>portal.apm.gob.ec/control-bienes/login</div>
                </a>

                <!-- 3. Bitácoras Portuarias Card -->
                <a href="<?= !$isLoggedIn ? APP_URL . '/login' : APP_URL . '/apps/bitacoras/' ?>" class="folder-card" style="--folder-color: #0891b2;">
                    <div class="fc-glow" style="background:#0891b2"></div>
                    <div style="position:absolute;top:0;left:0;right:0;height:3px;background:#0891b2"></div>
                    <div class="fc-icon" style="background:#0891b218;border:1px solid #0891b230"><i class="fa-solid fa-anchor" style="color:#0891b2"></i></div>
                    <div class="fc-dept">Módulo APM</div>
                    <div class="fc-name">Bitácoras Portuarias</div>
                    <p class="fc-desc">Registro de visitas, rondas de vigilancia y bitácora de cámaras CCTV en las instalaciones portuarias.</p>
                    <div class="fc-areas">
                        <span class="fc-area-tag" style="background:#0891b215;color:#0891b2">Visitas</span>
                        <span class="fc-area-tag" style="background:#0891b215;color:#0891b2">Rondas</span>
                        <span class="fc-area-tag" style="background:#0891b215;color:#0891b2">Cámaras CCTV</span>
                    </div>
                    <button class="fc-action" style="background:#0891b2">
                        <i class="fa-solid fa-arrow-right-to-bracket"></i> Ingresar al módulo
                    </button>
                    <div class="fc-url"><i class="fa-solid fa-lock"></i>portal.apm.gob.ec/bitacoras/login</div>
                </a>
            </div>
        </div>
    </div>

    <!-- 6. Footer Section -->
    <div class="portal-footer">
        © <?= date('Y') ?> Autoridad Portuaria de Manta. Todos los derechos reservados. Módulo Corporativo de Acceso e Integración Operativa.
    </div>

    <!-- ═══════════════════ THEME SWITCHER FLOATING PILL ═══════════════════ -->
    <div class="theme-switcher" id="themeSwitcher">
        <button class="ts-btn" data-theme="1" onclick="C.setTheme(1)">⚓ Institucional</button>
        <button class="ts-btn" data-theme="2" onclick="C.setTheme(2)">◈ Cyber Dark</button>
        <button class="ts-btn" data-theme="3" onclick="C.setTheme(3)">〜 Porto Glass</button>
    </div>

    <script src="<?= $baseUrl ?>/js/main.js"></script>
    <script>
        // Interactive state handlers
        let newsTickerIndex = 0;

        document.addEventListener("DOMContentLoaded", function() {
            lucide.createIcons();

            // Start the news ticker automatic rotation (solo si hay más de 1 noticia activa)
            if (document.querySelectorAll(".news-slide").length > 1) {
                setInterval(rotateNewsTicker, 5000);
            }

            // Start the background image slideshow rotation
            let currentBgSlide = 0;
            const bgSlides = document.querySelectorAll('.slideshow-bg .slide-img');
            const bgCount = bgSlides.length;
            if (bgCount > 1) {
                setInterval(() => {
                    const prev = currentBgSlide;
                    currentBgSlide = (currentBgSlide + 1) % bgCount;
                    if (bgSlides[prev]) bgSlides[prev].classList.remove('active');
                    if (bgSlides[currentBgSlide]) bgSlides[currentBgSlide].classList.add('active');
                }, 5000);
            }

            // News image carousel (noticias con imagen)
            const nic = document.getElementById('newsImgCarousel');
            if (nic) {
                const nicSlides = nic.querySelectorAll('.nic-slide');
                const nicDots = nic.querySelectorAll('.nic-dot');
                if (nicSlides.length > 1) {
                    let ni = 0, nicTimer;
                    const nicGoTo = (idx) => {
                        nicSlides[ni].classList.remove('active');
                        nicDots[ni]?.classList.remove('active');
                        ni = idx;
                        nicSlides[ni].classList.add('active');
                        nicDots[ni]?.classList.add('active');
                    };
                    const nicNext = () => nicGoTo((ni + 1) % nicSlides.length);
                    const nicStart = () => { nicTimer = setInterval(nicNext, 5500); };
                    const nicStop = () => clearInterval(nicTimer);
                    nicDots.forEach(d => d.addEventListener('click', () => { nicStop(); nicGoTo(parseInt(d.dataset.i, 10)); nicStart(); }));
                    nic.addEventListener('mouseenter', nicStop);
                    nic.addEventListener('mouseleave', nicStart);
                    nicStart();
                }
            }

            // Panel vertical de Consejos y Novedades (cuando no hay noticias con imagen)
            const tpt = document.getElementById('tipsPanelTall');
            if (tpt) {
                const tptSlides = tpt.querySelectorAll('.tpt-slide');
                const tptDots = tpt.querySelectorAll('.tpt-dot');
                if (tptSlides.length > 1) {
                    let ti = 0, tptTimer;
                    const tptGoTo = (idx) => {
                        tptSlides[ti].classList.remove('active');
                        tptDots[ti]?.classList.remove('active');
                        ti = idx;
                        tptSlides[ti].classList.add('active');
                        tptDots[ti]?.classList.add('active');
                    };
                    const tptNext = () => tptGoTo((ti + 1) % tptSlides.length);
                    const tptStart = () => { tptTimer = setInterval(tptNext, 5000); };
                    const tptStop = () => clearInterval(tptTimer);
                    tptDots.forEach(d => d.addEventListener('click', () => { tptStop(); tptGoTo(parseInt(d.dataset.i, 10)); tptStart(); }));
                    tpt.addEventListener('mouseenter', tptStop);
                    tpt.addEventListener('mouseleave', tptStart);
                    tptStart();
                }
            }
        });

        // Folder Modal operations
        function openFolders() {
            document.getElementById("moduleFoldersOverlay").classList.add("show");
        }

        function closeFolders() {
            document.getElementById("moduleFoldersOverlay").classList.remove("show");
        }

        // News ticker slider
        function rotateNewsTicker() {
            const slides = document.querySelectorAll(".news-slide");
            if (!slides.length) return;
            slides[newsTickerIndex].classList.remove("active");

            newsTickerIndex = (newsTickerIndex + 1) % slides.length;
            slides[newsTickerIndex].classList.add("active");

            const counter = document.getElementById("newsCounter");
            if (counter) counter.textContent = (newsTickerIndex + 1) + " / " + slides.length;
        }
    </script>
</body>
</html>
