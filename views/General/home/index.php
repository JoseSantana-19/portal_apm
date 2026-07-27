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
$isLoggedIn = isset($_SESSION['user_id']);
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
            background-image: linear-gradient(to bottom, rgba(9, 13, 22, 0.85) 0%, rgba(9, 13, 22, 0.6) 100%), url('<?= $baseUrl ?>/imgs/f1-pto-manta-2024.png');
            background-size: cover;
            background-position: center 30%;
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
            grid-template-columns: 1.1fr 0.9fr;
            gap: 32px;
            max-width: 1400px;
            margin: 0 auto;
            width: 100%;
            padding: 40px 48px;
            align-items: start;
            flex: 1;
        }

        @media (max-width: 1024px) {
            .portal-hero {
                grid-template-columns: 1fr;
                padding: 24px;
            }
        }

        .hero-glass-card {
            background: var(--surface-app);
            backdrop-filter: var(--backdrop);
            border: 1.5px solid var(--border-app);
            border-radius: var(--r-lg);
            padding: 36px;
            box-shadow: var(--shadow-app);
            transition: all 0.3s ease;
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
        }

        .ph-title {
            font-family: 'Fira Code', monospace;
            font-size: 2.2rem;
            margin: 0 0 16px 0;
            color: var(--text-app);
        }

        .ph-sub {
            color: var(--text-muted);
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
            background: rgba(255, 255, 255, 0.015);
            border: 1px solid var(--border-app);
            padding: 16px;
            border-radius: var(--r-md);
            transition: all 0.3s ease;
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
            opacity: 0;
            transform: translateY(20px);
            transition: all 0.5s ease;
            font-size: 13px;
            color: var(--text-muted);
            white-space: nowrap;
            text-overflow: ellipsis;
            overflow: hidden;
        }

        .news-slide.active {
            opacity: 1;
            transform: translateY(0);
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
<body class="portal-layout-body t1">
    <script>
        (function() {
            var savedTheme = localStorage.getItem('apm_theme') || '1';
            document.body.classList.remove('t1', 't2', 't3');
            document.body.classList.add('t' + savedTheme);
        })();
    </script>

    <!-- Slideshow Background cycling smoothly -->
    <div class="slideshow-bg">
        <div class="slide-img active" style="background-image: url('<?= $baseUrl ?>/imgs/formato-fotos-para-web-cruise-01.jpg');"></div>
        <div class="slide-img" style="background-image: url('<?= $baseUrl ?>/imgs/5NM4Z6GW7RAJTN7GDWF7UTE6VA.jpg');"></div>
        <div class="slide-img" style="background-image: url('<?= $baseUrl ?>/imgs/f1-pto-manta-2024.png');"></div>
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
    <div class="portal-hero">
        <!-- LEFT COLUMN -->
        <div class="portal-hero-left">
            <!-- Welcome Glass Card -->
            <div class="hero-glass-card">
                <div class="ph-badge"><i class="fa-solid fa-shield-halved"></i> SysPort · Acceso Unificado</div>
                <h1 class="ph-title" style="font-size: 2.6rem; line-height: 1.1; margin-bottom: 20px;">
                    <span style="color: var(--accent-hover, #0284C7); font-weight: 800;">Sys</span>Port
                    <span style="font-size: 18px; font-weight: 300; opacity: 0.8; margin-left: 8px;">| Acceso Corporativo</span>
                </h1>
                <p class="ph-sub">Bienvenido a <strong>SysPort</strong>, el portal único de integración operativa y administrativa de la Autoridad Portuaria de Manta. Inicie sesión de manera segura para acceder a sus módulos correspondientes.</p>
                
                <div class="hero-features-grid">
                    <div class="h-feat">
                        <div class="h-feat-icon" style="color: #38BDF8;"><i class="fa-solid fa-network-wired"></i></div>
                        <div class="h-feat-title">SSO Integrado</div>
                        <div class="h-feat-desc">Autenticación compartida segura entre departamentos.</div>
                    </div>
                    <div class="h-feat">
                        <div class="h-feat-icon" style="color: #34D399;"><i class="fa-solid fa-shield-halved"></i></div>
                        <div class="h-feat-title">Seguridad Cripto</div>
                        <div class="h-feat-desc">Conexiones cifradas AES-256 y MFA.</div>
                    </div>
                    <div class="h-feat">
                        <div class="h-feat-icon" style="color: #818CF8;"><i class="fa-solid fa-server"></i></div>
                        <div class="h-feat-title">Auditoría TI</div>
                        <div class="h-feat-desc">Monitoreo transaccional de accesos y logs.</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- RIGHT COLUMN -->
        <div class="portal-hero-right">
            <!-- Quick Services Card (Interactive Grid) -->
            <div class="quick-services-card">
                <div class="qs-title">
                    <i class="fa-solid fa-anchor" style="color:var(--accent-hover)"></i>
                    <span>Servicios Portuarios Rápidos</span>
                </div>
                <div class="qs-grid">
                    <button class="qs-btn" onclick="openServiceModal('buques')">
                        <div class="qs-btn-icon"><i class="fa-solid fa-ship"></i></div>
                        <div>
                            <div style="font-weight:700">Cronograma</div>
                            <div style="font-size:10px; color:var(--text-muted)">Próximos buques</div>
                        </div>
                    </button>
                    <button class="qs-btn" onclick="openServiceModal('tarifas')">
                        <div class="qs-btn-icon"><i class="fa-solid fa-calculator"></i></div>
                        <div>
                            <div style="font-weight:700">Simulador Tasas</div>
                            <div style="font-size:10px; color:var(--text-muted)">Cálculo de tarifas</div>
                        </div>
                    </button>
                    <button class="qs-btn" onclick="openServiceModal('manifiestos')">
                        <div class="qs-btn-icon"><i class="fa-solid fa-magnifying-glass"></i></div>
                        <div>
                            <div style="font-weight:700">Manifiestos</div>
                            <div style="font-size:10px; color:var(--text-muted)">Buscar contenedor</div>
                        </div>
                    </button>
                    <button class="qs-btn" onclick="openServiceModal('turnos')">
                        <div class="qs-btn-icon"><i class="fa-solid fa-calendar-days"></i></div>
                        <div>
                            <div style="font-weight:700">Turnos Esclusas</div>
                            <div style="font-size:10px; color:var(--text-muted)">Fila transportistas</div>
                        </div>
                    </button>
                </div>
            </div>

            <!-- News & Alerts Ticker Carousel -->
            <div class="news-ticker-card">
                <div class="news-label"><i class="fa-solid fa-bullhorn"></i> Boletín APM</div>
                <div class="news-container">
                    <div class="news-slide active" id="newsSlide0">
                        <span style="color:var(--accent-hover); font-weight:700">●</span> 
                        <span>Puerto de Manta incrementa el calado de acceso en muelle internacional a 13 metros.</span>
                    </div>
                    <div class="news-slide" id="newsSlide1">
                        <span style="color:var(--accent-hover); font-weight:700">●</span> 
                        <span>Nuevo récord operativo: transferencia de más de 45,000 TEUs en el último trimestre.</span>
                    </div>
                    <div class="news-slide" id="newsSlide2">
                        <span style="color:var(--accent-hover); font-weight:700">●</span> 
                        <span>Mantenimiento preventivo en báscula electrónica norte programado para este domingo 04:00 AM.</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 3. Welcome Welcome Modal with Dynamic Carousel -->
    <div class="w-modal-overlay show" id="welcomeModal">
        <div class="w-modal-card">
            <button class="w-close" onclick="closeWelcomeModal()"><i class="fa-solid fa-xmark"></i></button>
            <div class="w-logo-hdr">
                <img src="<?= $baseUrl ?>/imgs/logoapm.png" alt="Logo APM Manta" onerror="this.src='https://i.imgur.com/8QG4pQA.png'">
                <div class="w-logo-title" style="font-family: 'Sora', sans-serif; font-size: 22px;">
                    <span style="color: var(--accent-hover, #0284C7); font-weight: 800;">Sys</span>Port <span style="font-size: 14px; font-weight: 300; opacity: 0.8; margin-left: 4px;">· APM</span>
                </div>
            </div>
            
            <div class="w-carousel">
                <div class="wc-slide active" id="welcomeSlide0">
                    <div class="wc-title">Bienvenido a SysPort</div>
                    <div class="wc-desc">
                        Bienvenido a <strong>SysPort</strong>. Hemos consolidado los accesos departamentales de la Autoridad Portuaria de Manta bajo una arquitectura de software robusta, estable y de alta seguridad. Su sesión le otorgará privilegios SSO según su perfil.
                    </div>
                </div>
                <div class="wc-slide" id="welcomeSlide1">
                    <div class="wc-title">Conectividad Relacional SQL Server</div>
                    <div class="wc-desc">
                        Toda la información operativa se sincroniza en tiempo real con el servidor de base de datos de APM. Se registran logs de auditoría exhaustivos y firmas digitales para cada operación crítica.
                    </div>
                </div>
                <div class="wc-slide" id="welcomeSlide2">
                    <div class="wc-title">Seguridad de Nivel Gubernamental</div>
                    <div class="wc-desc">
                        Protegemos la información portuaria mediante cifrado AES-256, hashes seguros y autenticación en dos pasos (MFA). Su IP y actividad serán monitoreadas para cumplir con los estándares ISO 27001.
                    </div>
                </div>
            </div>

            <div class="wc-dots">
                <div class="wc-dot active" onclick="setWelcomeSlide(0)"></div>
                <div class="wc-dot" onclick="setWelcomeSlide(1)"></div>
                <div class="wc-dot" onclick="setWelcomeSlide(2)"></div>
            </div>

            <div class="w-footer">
                <button class="btn" style="background:rgba(255,255,255,0.05); border:1px solid var(--border-app); padding:8px 16px; font-weight:700; color:var(--text-muted);" onclick="closeWelcomeModal()">Omitir</button>
                <button class="btn btn-primary" id="btnNextWelcome" onclick="nextWelcomeSlide()">Siguiente <i class="fa-solid fa-arrow-right"></i></button>
            </div>
        </div>
    </div>

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
                <!-- 1. Dirección de Asesoría Jurídica Card -->
                <a href="<?= !$isLoggedIn ? APP_URL . '/login' : APP_URL . '/dashboard' ?>" class="folder-card" style="--folder-color: #B45309;">
                    <div class="fc-glow" style="background:#B45309"></div>
                    <div style="position:absolute;top:0;left:0;right:0;height:3px;background:#B45309"></div>
                    <div class="fc-icon" style="background:#B4530918;border:1px solid #B4530930"><i class="fa-solid fa-scale-balanced" style="color:#B45309"></i></div>
                    <div class="fc-dept">Módulo APM</div>
                    <div class="fc-name">Dir. Asesoría Jurídica</div>
                    <p class="fc-desc">Contratos, certificaciones, resoluciones y absolución de consultas legales institucionales de la APM.</p>
                    <div class="fc-areas">
                        <span class="fc-area-tag" style="background:#B4530915;color:#B45309">Revisión de contratos</span>
                        <span class="fc-area-tag" style="background:#B4530915;color:#B45309">Certificaciones</span>
                        <span class="fc-area-tag" style="background:#B4530915;color:#B45309">Buzón de Consultas</span>
                    </div>
                    <button class="fc-action" style="background:#B45309">
                        <i class="fa-solid fa-arrow-right-to-bracket"></i> Ingresar al módulo
                    </button>
                    <div class="fc-url"><i class="fa-solid fa-lock"></i>portal.apm.gob.ec/juridica/login</div>
                </a>

                <!-- 2. Infraestructuras Portuarias Card -->
                <a href="<?= !$isLoggedIn ? APP_URL . '/login' : APP_URL . '/acceso' ?>" class="folder-card" style="--folder-color: #0284c7;">
                    <div class="fc-glow" style="background:#0284c7"></div>
                    <div style="position:absolute;top:0;left:0;right:0;height:3px;background:#0284c7"></div>
                    <div class="fc-icon" style="background:#0284c718;border:1px solid #0284c730"><i class="fa-solid fa-industry" style="color:#0284c7"></i></div>
                    <div class="fc-dept">Módulo APM</div>
                    <div class="fc-name">Infraestructuras Portuarias</div>
                    <p class="fc-desc">Control de acceso perimetral, monitoreo CCTV de muelles, bandeja de trámites y bitácoras físicas.</p>
                    <div class="fc-areas">
                        <span class="fc-area-tag" style="background:#0284c715;color:#0284c7">Control de Acceso</span>
                        <span class="fc-area-tag" style="background:#0284c715;color:#0284c7">Rondas CCTV</span>
                        <span class="fc-area-tag" style="background:#0284c715;color:#0284c7">Bitácoras</span>
                    </div>
                    <button class="fc-action" style="background:#0284c7">
                        <i class="fa-solid fa-arrow-right-to-bracket"></i> Ingresar al módulo
                    </button>
                    <div class="fc-url"><i class="fa-solid fa-lock"></i>portal.apm.gob.ec/infraestructura/login</div>
                </a>

                <!-- 3. Talento Humano Card -->
                <a href="<?= !$isLoggedIn ? APP_URL . '/login' : APP_URL . '/apps/talento_humano/' ?>" class="folder-card" style="--folder-color: #8B5CF6;">
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

                <!-- 4. Gerencia General Card -->
                <a href="<?= !$isLoggedIn ? APP_URL . '/login' : APP_URL . '/dashboard' ?>" class="folder-card" style="--folder-color: #4F46E5;">
                    <div class="fc-glow" style="background:#4F46E5"></div>
                    <div style="position:absolute;top:0;left:0;right:0;height:3px;background:#4F46E5"></div>
                    <div class="fc-icon" style="background:#4F46E518;border:1px solid #4F46E530"><i class="fa-solid fa-briefcase" style="color:#4F46E5"></i></div>
                    <div class="fc-dept">Módulo APM</div>
                    <div class="fc-name">Gerencia General</div>
                    <p class="fc-desc">Cuadro de mando integral (KPIs), reportes consolidados y autorizaciones ejecutivas del puerto.</p>
                    <div class="fc-areas">
                        <span class="fc-area-tag" style="background:#4F46E515;color:#4F46E5">KPIs Estratégicos</span>
                        <span class="fc-area-tag" style="background:#4F46E515;color:#4F46E5">Aprobaciones</span>
                        <span class="fc-area-tag" style="background:#4F46E515;color:#4F46E5">Reportes Consolidados</span>
                    </div>
                    <button class="fc-action" style="background:#4F46E5">
                        <i class="fa-solid fa-arrow-right-to-bracket"></i> Ingresar al módulo
                    </button>
                    <div class="fc-url"><i class="fa-solid fa-lock"></i>portal.apm.gob.ec/gerencia/login</div>
                </a>

                <!-- 5. Dirección Administrativa Card -->
                <a href="<?= !$isLoggedIn ? APP_URL . '/login' : APP_URL . '/apps/control_bienes/' ?>" class="folder-card" style="--folder-color: #10B981;">
                    <div class="fc-glow" style="background:#10B981"></div>
                    <div style="position:absolute;top:0;left:0;right:0;height:3px;background:#10B981"></div>
                    <div class="fc-icon" style="background:#10B98118;border:1px solid #10B98130"><i class="fa-solid fa-gear" style="color:#10B981"></i></div>
                    <div class="fc-dept">Módulo APM</div>
                    <div class="fc-name">Dirección Administrativa</div>
                    <p class="fc-desc">Control patrimonial de activos fijos, inventario de bienes muebles e infraestructura tecnológica portuaria.</p>
                    <div class="fc-areas">
                        <span class="fc-area-tag" style="background:#10B98115;color:#10B981">Control Patrimonial</span>
                        <span class="fc-area-tag" style="background:#10B98115;color:#10B981">Activos TI</span>
                        <span class="fc-area-tag" style="background:#10B98115;color:#10B981">Suministros</span>
                    </div>
                    <button class="fc-action" style="background:#10B981">
                        <i class="fa-solid fa-arrow-right-to-bracket"></i> Ingresar al módulo
                    </button>
                    <div class="fc-url"><i class="fa-solid fa-lock"></i>portal.apm.gob.ec/admin/login</div>
                </a>

                <!-- 6. Dirección Financiera Card -->
                <a href="<?= !$isLoggedIn ? APP_URL . '/login' : APP_URL . '/dashboard' ?>" class="folder-card" style="--folder-color: #2563EB;">
                    <div class="fc-glow" style="background:#2563EB"></div>
                    <div style="position:absolute;top:0;left:0;right:0;height:3px;background:#2563EB"></div>
                    <div class="fc-icon" style="background:#2563EB18;border:1px solid #2563EB30"><i class="fa-solid fa-chart-pie" style="color:#2563EB"></i></div>
                    <div class="fc-dept">Módulo APM</div>
                    <div class="fc-name">Dirección Financiera</div>
                    <p class="fc-desc">Planificación presupuestaria anual y cuatrimestral, control de tesorería y contabilidad gubernamental.</p>
                    <div class="fc-areas">
                        <span class="fc-area-tag" style="background:#2563EB15;color:#2563EB">Presupuesto</span>
                        <span class="fc-area-tag" style="background:#2563EB15;color:#2563EB">Tesorería & Caja</span>
                        <span class="fc-area-tag" style="background:#2563EB15;color:#2563EB">Reportes de Cierre</span>
                    </div>
                    <button class="fc-action" style="background:#2563EB">
                        <i class="fa-solid fa-arrow-right-to-bracket"></i> Ingresar al módulo
                    </button>
                    <div class="fc-url"><i class="fa-solid fa-lock"></i>portal.apm.gob.ec/financiero/login</div>
                </a>

                <!-- 7. Administración Esquema BD Card -->
                <a href="<?= !$isLoggedIn ? APP_URL . '/login' : APP_URL . '/admin/auditoria' ?>" class="folder-card card-span-3" style="--folder-color: #14B8A6;">
                    <div class="fc-glow" style="background:#14B8A6"></div>
                    <div style="position:absolute;top:0;left:0;right:0;height:3px;background:#14B8A6"></div>
                    <div class="fc-icon" style="background:#14B8A618;border:1px solid #14B8A630"><i class="fa-solid fa-diagram-project" style="color:#14B8A6"></i></div>
                    <div class="fc-dept">Módulo APM</div>
                    <div class="fc-name">Administración Esquema BD & ERD</div>
                    <p class="fc-desc">Matriz técnica de diccionario de tablas físicas, llaves relacionales y script DDL interactivo en SQL Server.</p>
                    <div class="fc-areas">
                        <span class="fc-area-tag" style="background:#14B8A615;color:#14B8A6">Modelo ERD</span>
                        <span class="fc-area-tag" style="background:#14B8A615;color:#14B8A6">Diccionario de Datos</span>
                        <span class="fc-area-tag" style="background:#14B8A615;color:#14B8A6">Script DDL SQL</span>
                    </div>
                    <button class="fc-action" style="background:#14B8A6">
                        <i class="fa-solid fa-arrow-right-to-bracket"></i> Explorar base de datos
                    </button>
                    <div class="fc-url"><i class="fa-solid fa-lock"></i>portal.apm.gob.ec/database-admin/login</div>
                </a>
            </div>
        </div>
    </div>

    <!-- Live Developer SSO & Views Integration Console -->
    <div class="section-container" style="margin-top: 40px; margin-bottom: 40px;">
        <div class="hero-glass-card" style="border: 1px solid rgba(255, 255, 255, 0.1); padding: 30px; background: rgba(10, 25, 47, 0.65);">
            <div style="display:flex; align-items:center; gap:12px; margin-bottom: 10px;">
                <div style="background:#2563EB; width:40px; height:40px; border-radius:10px; display:flex; align-items:center; justify-content:center; color:#fff; font-size:18px;">
                    <i class="fa-solid fa-code"></i>
                </div>
                <div>
                    <h3 style="font-size: 20px; font-weight: 800; margin: 0; color:#fff; font-family:'Sora', sans-serif;">Consola de Integración SSO Portuaria <span style="font-size:11px; font-weight:400; background:#2563EB30; color:#38BDF8; padding:3px 8px; border-radius:20px; margin-left:8px; border:1px solid #2563EB50;">Área de Desarrolladores</span></h3>
                    <p style="font-size: 12px; color: var(--text-muted); margin: 3px 0 0 0;">Prueba y valida la conexión independiente, generación de tokens criptográficos y consulta de vistas relacionales de seguridad.</p>
                </div>
            </div>
            
            <div style="display:grid; grid-template-columns: 1fr 2fr; gap:30px; margin-top:25px;">
                <!-- Left panel: Trigger authentication -->
                <div style="background: rgba(0,0,0,0.2); padding:20px; border-radius:12px; border:1px solid rgba(255,255,255,0.05); display:flex; flex-direction:column; gap:16px;">
                    <div>
                        <label style="display:block; font-size:11.5px; font-weight:700; color:rgba(255,255,255,0.85); margin-bottom:8px;">SELECCIONAR FUNCIONARIO DEMO</label>
                        <select id="ssoDemoSelect" style="width:100%; background:rgba(15,23,42,0.8); border:1px solid rgba(255,255,255,0.15); color:#fff; padding:10px; border-radius:8px; font-size:12.5px; outline:none; font-family:'Sora', sans-serif;" onchange="updateDemoInput()">
                            <option value="1300000001">Ana Castro (Gerente - Cédula: 1300000001)</option>
                            <option value="1300000003">Felipe Mora (Jefe Infraestructura - Cédula: 1300000003)</option>
                            <option value="1300000010">Nadia Vera (Analista Financiera - Cédula: 1300000010)</option>
                        </select>
                    </div>
                    
                    <div>
                        <label style="display:block; font-size:11.5px; font-weight:700; color:rgba(255,255,255,0.85); margin-bottom:8px;">CÉDULA DE IDENTIDAD</label>
                        <div style="position:relative;">
                            <input type="text" id="ssoCedulaInput" value="1300000001" style="width:100%; background:rgba(15,23,42,0.8); border:1px solid rgba(255,255,255,0.15); color:#fff; padding:10px 10px 10px 35px; border-radius:8px; font-size:13px; outline:none; font-family:'JetBrains Mono', monospace;" placeholder="Ingrese cédula">
                            <i class="fa-solid fa-address-card" style="position:absolute; left:12px; top:13px; color:rgba(255,255,255,0.4); font-size:13px;"></i>
                        </div>
                    </div>
                    
                    <button class="btn btn-primary" style="width:100%; background:#2563EB; border:none; padding:12px; border-radius:8px; font-weight:700; display:flex; align-items:center; justify-content:center; gap:8px; font-size:12.5px; transition:all 0.2s;" onclick="runDemoSSO()">
                        <i class="fa-solid fa-key"></i> Simular Autenticación y Firmar Token
                    </button>
                    
                    <div style="font-size:11px; color:var(--text-muted); line-height:1.4; border-top:1px solid rgba(255,255,255,0.08); padding-top:12px;">
                        <i class="fa-solid fa-circle-info" style="color:#38BDF8; margin-right:4px;"></i> La autenticación consulta la vista <code>View_portal_apm_usuario</code> en SQL Server y valida que el estado sea activo antes de firmar digitalmente el token de acceso.
                    </div>
                </div>
                
                <!-- Right panel: Interactive console output -->
                <div style="background: rgba(0,0,0,0.25); border-radius:12px; border:1px solid rgba(255,255,255,0.05); display:flex; flex-direction:column; overflow:hidden;">
                    <!-- Tabs header -->
                    <div style="display:flex; background:rgba(15,23,42,0.6); border-bottom:1px solid rgba(255,255,255,0.08);">
                        <button class="sso-tab-btn active" id="ssoTabBtnToken" onclick="switchSsoTab('token')"><i class="fa-solid fa-ticket-simple"></i> Token SSO Firmado</button>
                        <button class="sso-tab-btn" id="ssoTabBtnViews" onclick="switchSsoTab('views')"><i class="fa-solid fa-database"></i> Respuestas SQL Server (Vistas)</button>
                        <button class="sso-tab-btn" id="ssoTabBtnPHP" onclick="switchSsoTab('php')"><i class="fa-brands fa-php"></i> Snippet de Integración PHP</button>
                    </div>
                    
                    <!-- Content panes -->
                    <div style="padding:20px; flex-grow:1; min-height: 280px; display:flex; flex-direction:column;">
                        <!-- Pane 1: Token -->
                        <div class="sso-tab-pane active" id="ssoTabPaneToken">
                            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:12px; width: 100%;">
                                <span style="font-size:11.5px; font-weight:700; color:rgba(255,255,255,0.85);">Estructura del Token (SSO Token Stream)</span>
                                <span id="ssoTokenStatus" style="font-size:10px; font-weight:700; background:#10B98120; color:#34D399; border:1px solid #10B98140; padding:2px 8px; border-radius:20px; display:none;"><i class="fa-solid fa-circle-check"></i> FIRMA CRIPTOGRÁFICA VERIFICADA (HMAC-SHA256)</span>
                            </div>
                            <div id="ssoTokenRaw" style="font-family:'JetBrains Mono', monospace; font-size:11px; background:rgba(0,0,0,0.4); padding:10px; border-radius:6px; border:1px solid rgba(255,255,255,0.08); color:#a7f3d0; word-break:break-all; min-height:48px; margin-bottom:15px;">Seleccione un funcionario y presione el botón de simulación para generar el token.</div>
                            
                            <span style="display:block; font-size:11.5px; font-weight:700; color:rgba(255,255,255,0.85); margin-bottom:8px;">Cuerpo del Payload Desencriptado (JSON Claims)</span>
                            <pre id="ssoTokenDecoded" style="font-family:'JetBrains Mono', monospace; font-size:11px; background:rgba(0,0,0,0.4); padding:10px; border-radius:6px; border:1px solid rgba(255,255,255,0.08); color:#38bdf8; margin:0; overflow-x:auto;">{
  "info": "Espere inicialización..."
}</pre>
                        </div>
                        
                        <!-- Pane 2: Views -->
                        <div class="sso-tab-pane" id="ssoTabPaneViews" style="display:none;">
                            <div style="display:flex; gap:10px; margin-bottom:12px;">
                                <select id="ssoViewSelect" style="background:rgba(15,23,42,0.8); border:1px solid rgba(255,255,255,0.15); color:#fff; padding:6px 12px; border-radius:6px; font-size:11.5px; outline:none; font-family:'Sora', sans-serif;" onchange="renderSsoViewData()">
                                    <option value="usuario">dbo.View_portal_apm_usuario (Autenticación y Cédula)</option>
                                    <option value="modulos">dbo.view_portal_apm_modulos (Autorizaciones de Módulo)</option>
                                    <option value="menu">dbo.view_portal_apm_menu (Árbol de Menús)</option>
                                    <option value="opciones">dbo.view_portal_apm_opciones (Acción sobre Menús)</option>
                                    <option value="formulario">dbo.view_portal_apm_formulario (Formularios y Permisos 1-4)</option>
                                </select>
                            </div>
                            <pre id="ssoViewOutput" style="font-family:'JetBrains Mono', monospace; font-size:11px; background:rgba(0,0,0,0.4); padding:10px; border-radius:6px; border:1px solid rgba(255,255,255,0.08); color:#fbbf24; margin:0; flex-grow:1; overflow-y:auto; max-height:220px;">Seleccione una vista para visualizar los registros retornados para este usuario...</pre>
                        </div>
                        
                        <!-- Pane 3: PHP integration -->
                        <div class="sso-tab-pane" id="ssoTabPanePHP" style="display:none;">
                            <span style="display:block; font-size:11.5px; font-weight:700; color:rgba(255,255,255,0.85); margin-bottom:8px;">Uso del Helper de Seguridad Compartido en Formularios Independientes</span>
                            <pre style="font-family:'JetBrains Mono', monospace; font-size:11.5px; background:rgba(0,0,0,0.4); padding:12px; border-radius:6px; border:1px solid rgba(255,255,255,0.08); color:#f472b6; margin:0; overflow-x:auto; line-height:1.4;">// 1. Verificar autenticidad y expiración del Token de Acceso SSO
require_once 'core/ModuleSecurity.php';
$token = $_SESSION['sso_token'] ?? $_GET['token'] ?? '';
$sso = ModuleSecurity::verifySSOToken($token);

if (!$sso['success']) {
    die("Acceso Denegado: " . $sso['error']);
}
$userId = $sso['payload']['id'];

// 2. Verificar permisos para el módulo, menú y nivel de formulario (ej: TH_Empleados, Permiso Crear=2)
if (!ModuleSecurity::checkAccess($userId, 'TH', null, 5, 2)) {
    die("Acceso Denegado: Privilegios insuficientes para crear fichas.");
}

// 3. Registrar auditoría transaccional de movimientos en la tabla dual (TH_Auditoria_Movimientos)
ModuleSecurity::audit(
    'TH',             // Prefijo del módulo
    'MOVIMIENTO',     // Tipo de auditoría (ACCESO o MOVIMIENTO)
    $userId,          // ID del usuario logueado
    'INSERT',         // Operación ejecutada
    'TH_Empleados',   // Tabla afectada
    $newRecordId,     // ID del registro insertado
    null,             // Datos anteriores (JSON)
    json_encode($newEmployeeData) // Datos nuevos (JSON)
);</pre>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    let lastSsoResult = null;

    function updateDemoInput() {
        const select = document.getElementById('ssoDemoSelect');
        const input = document.getElementById('ssoCedulaInput');
        input.value = select.value;
    }

    function switchSsoTab(tab) {
        document.getElementById('ssoTabBtnToken').classList.remove('active');
        document.getElementById('ssoTabBtnViews').classList.remove('active');
        document.getElementById('ssoTabBtnPHP').classList.remove('active');
        
        document.getElementById('ssoTabPaneToken').style.display = 'none';
        document.getElementById('ssoTabPaneViews').style.display = 'none';
        document.getElementById('ssoTabPanePHP').style.display = 'none';
        
        if (tab === 'token') {
            document.getElementById('ssoTabBtnToken').classList.add('active');
            document.getElementById('ssoTabPaneToken').style.display = 'block';
        } else if (tab === 'views') {
            document.getElementById('ssoTabBtnViews').classList.add('active');
            document.getElementById('ssoTabPaneViews').style.display = 'block';
            renderSsoViewData();
        } else if (tab === 'php') {
            document.getElementById('ssoTabBtnPHP').classList.add('active');
            document.getElementById('ssoTabPanePHP').style.display = 'block';
        }
    }

    function runDemoSSO() {
        const cedula = document.getElementById('ssoCedulaInput').value.trim();
        if (!cedula) {
            alert('Por favor ingrese una cédula.');
            return;
        }
        
        const baseUrl = '<?= $baseUrl ?>';
        
        fetch(`${baseUrl}/api/demo-sso?cedula=${cedula}`)
            .then(response => {
                if (!response.ok) {
                    return response.json().then(err => { throw new Error(err.error || 'Error del servidor'); });
                }
                return response.json();
            })
            .then(data => {
                lastSsoResult = data;
                
                document.getElementById('ssoTokenStatus').style.display = 'inline-flex';
                document.getElementById('ssoTokenRaw').textContent = data.token.raw;
                document.getElementById('ssoTokenDecoded').textContent = JSON.stringify(data.token.decrypted_payload, null, 2);
                
                renderSsoViewData();
                
                document.getElementById('ssoTokenRaw').style.borderColor = '#10B981';
                setTimeout(() => {
                    document.getElementById('ssoTokenRaw').style.borderColor = 'rgba(255,255,255,0.08)';
                }, 1000);
            })
            .catch(err => {
                alert('Error en Autenticación SSO: ' + err.message);
                document.getElementById('ssoTokenStatus').style.display = 'none';
                document.getElementById('ssoTokenRaw').textContent = 'Error: ' + err.message;
                document.getElementById('ssoTokenDecoded').textContent = '{\n  "error": "' + err.message + '"\n}';
                lastSsoResult = null;
                renderSsoViewData();
            });
    }

    function renderSsoViewData() {
        const view = document.getElementById('ssoViewSelect').value;
        const output = document.getElementById('ssoViewOutput');
        
        if (!lastSsoResult) {
            output.textContent = 'Ningún usuario autenticado. Presione "Simular Autenticación y Firmar Token" primero.';
            return;
        }
        
        let subset = {};
        if (view === 'usuario') {
            subset = lastSsoResult.user;
        } else if (view === 'modulos') {
            subset = lastSsoResult.permisos.modulos;
        } else if (view === 'menu') {
            subset = lastSsoResult.permisos.menus;
        } else if (view === 'opciones') {
            subset = lastSsoResult.permisos.opciones;
        } else if (view === 'formulario') {
            subset = lastSsoResult.permisos.formularios;
        }
        
        output.textContent = JSON.stringify(subset, null, 2);
    }
    </script>

    <style>
    .sso-tab-btn {
        background: transparent;
        border: none;
        color: var(--text-muted);
        padding: 12px 18px;
        font-size: 12px;
        font-weight: 700;
        cursor: pointer;
        font-family: 'Sora', sans-serif;
        transition: all 0.2s;
        border-bottom: 2px solid transparent;
        outline: none;
    }
    .sso-tab-btn:hover {
        color: #fff;
        background: rgba(255,255,255,0.02);
    }
    .sso-tab-btn.active {
        color: #38BDF8;
        border-bottom-color: #38BDF8;
        background: rgba(255,255,255,0.04);
    }
    </style>

    <!-- 5. Services Overlays (Quick Modals) -->
    <!-- Buques Modal -->
    <div class="services-overlay" id="serviceModalBuques" onclick="if(event.target===this) closeServiceModal('buques')">
        <div class="services-modal">
            <button class="sm-close" onclick="closeServiceModal('buques')"><i class="fa-solid fa-xmark"></i></button>
            <div class="sm-header-block">
                <div class="sm-icon" style="color:var(--accent-hover); font-size:24px"><i class="fa-solid fa-ship"></i></div>
                <div class="sm-title-text">
                    <h2>Cronograma de Buques Anunciados</h2>
                    <p>Arribos estimados y estado de atraques en las próximas 72 horas</p>
                </div>
            </div>
            <div class="sched-list">
                <div class="sched-item">
                    <div class="sc-left">
                        <span class="sc-name">Maersk Salina</span>
                        <span class="sc-line">Línea: Maersk Line · Capacidad: 8400 TEUs</span>
                    </div>
                    <div class="sc-right">
                        <span class="sc-eta">ETA: 22-May 08:30</span>
                        <span class="sc-dock">Asignado: Muelle 1</span>
                    </div>
                </div>
                <div class="sched-item">
                    <div class="sc-left">
                        <span class="sc-name">CMA CGM Tigris</span>
                        <span class="sc-line">Línea: CMA CGM · Capacidad: 9200 TEUs</span>
                    </div>
                    <div class="sc-right">
                        <span class="sc-eta">ETA: 22-May 15:45</span>
                        <span class="sc-dock">Asignado: Muelle 2</span>
                    </div>
                </div>
                <div class="sched-item">
                    <div class="sc-left">
                        <span class="sc-name">Hapag-Lloyd Andes</span>
                        <span class="sc-line">Línea: Hapag-Lloyd · Capacidad: 10500 TEUs</span>
                    </div>
                    <div class="sc-right">
                        <span class="sc-eta">ETA: 23-May 02:15</span>
                        <span class="sc-dock">Fondeadero: Zona A</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tarifas Modal -->
    <div class="services-overlay" id="serviceModalTarifas" onclick="if(event.target===this) closeServiceModal('tarifas')">
        <div class="services-modal">
            <button class="sm-close" onclick="closeServiceModal('tarifas')"><i class="fa-solid fa-xmark"></i></button>
            <div class="sm-header-block">
                <div class="sm-icon" style="color:var(--accent-hover); font-size:24px"><i class="fa-solid fa-calculator"></i></div>
                <div class="sm-title-text">
                    <h2>Simulador de Tasas Portuarias</h2>
                    <p>Cálculo referencial de tarifas operativas de carga general y contenedores</p>
                </div>
            </div>
            
            <div class="calc-row">
                <div class="sm-field">
                    <label for="calcLoadType">Tipo de Carga</label>
                    <select id="calcLoadType" onchange="updateTariffCalculation()">
                        <option value="cnt20">Contenedor 20 FT (Dry)</option>
                        <option value="cnt40">Contenedor 40 FT (Dry)</option>
                        <option value="reefer">Contenedor Refrigerado (Reefer)</option>
                        <option value="bulk">Carga General / Granel (Toneladas)</option>
                    </select>
                </div>
                <div class="sm-field">
                    <label for="calcWeight">Cantidad (Peso en Ton / Unidades)</label>
                    <input type="number" id="calcWeight" value="1" min="1" oninput="updateTariffCalculation()">
                </div>
            </div>
            
            <div class="calc-row">
                <div class="sm-field">
                    <label for="calcDays">Días de Permanencia en Patio</label>
                    <input type="number" id="calcDays" value="3" min="1" oninput="updateTariffCalculation()">
                </div>
                <div class="sm-field">
                    <label for="calcOperator">Operador de Estiba</label>
                    <select id="calcOperator" onchange="updateTariffCalculation()">
                        <option value="std">Estiba Estándar APM</option>
                        <option value="prio">Estiba Prioritaria / Express</option>
                    </select>
                </div>
            </div>
            
            <div class="calc-result-box">
                <div class="cr-row">
                    <span class="l">Tasa por Uso de Muelle (Muellaje):</span>
                    <span class="v" id="calcResMuellaje">$0.00</span>
                </div>
                <div class="cr-row">
                    <span class="l">Servicio de Almacenamiento en Patio:</span>
                    <span class="v" id="calcResPatio">$0.00</span>
                </div>
                <div class="cr-row">
                    <span class="l">Operación de Carga/Descarga:</span>
                    <span class="v" id="calcResOperacion">$0.00</span>
                </div>
                <div class="cr-row cr-total">
                    <span class="l">Total Estimado Tasas Portuarias:</span>
                    <span class="v" id="calcResTotal">$0.00</span>
                </div>
                <div style="font-size:10px; color:var(--text-muted); text-align:center; margin-top:8px">
                    *Los valores son referenciales basados en el tarifario oficial de la APM y no incluyen IVA.
                </div>
            </div>
        </div>
    </div>

    <!-- Manifiestos Modal -->
    <div class="services-overlay" id="serviceModalManifiestos" onclick="if(event.target===this) closeServiceModal('manifiestos')">
        <div class="services-modal">
            <button class="sm-close" onclick="closeServiceModal('manifiestos')"><i class="fa-solid fa-xmark"></i></button>
            <div class="sm-header-block">
                <div class="sm-icon" style="color:var(--accent-hover); font-size:24px"><i class="fa-solid fa-magnifying-glass"></i></div>
                <div class="sm-title-text">
                    <h2>Consulta de Manifiestos de Contenedores</h2>
                    <p>Verifica el estado de desaduanamiento, liberación y ubicación física de tu carga</p>
                </div>
            </div>
            
            <div class="search-bar-wrap">
                <input type="text" class="sm-search-input" id="manifestSearchCode" placeholder="Ingrese código del contenedor (Pruebe con: APM-83492, APM-91048)..." onkeydown="if(event.key==='Enter') searchContainerManifest()">
                <button class="sm-search-btn" onclick="searchContainerManifest()"><i class="fa-solid fa-magnifying-glass"></i> Buscar</button>
            </div>
            
            <div class="manifest-result" id="manifestResultBox">
                <div class="mr-header">
                    <span class="mr-code" id="mrResCode">APM-83492</span>
                    <span class="dr-badge" id="mrResStatus" style="background:#10B981">Liberado</span>
                </div>
                <div class="mr-grid">
                    <div class="mr-item">
                        <span class="mr-lbl">Consignatario</span>
                        <span class="mr-val" id="mrResConsignee">Importadora Manta S.A.</span>
                    </div>
                    <div class="mr-item">
                        <span class="mr-lbl">Tipo de Carga / Tamaño</span>
                        <span class="mr-val" id="mrResType">40 FT Dry Cargo</span>
                    </div>
                    <div class="mr-item">
                        <span class="mr-lbl">Ubicación en Patio</span>
                        <span class="mr-val" id="mrResLocation">Zona C - Bloque 4 - Fila 2</span>
                    </div>
                    <div class="mr-item">
                        <span class="mr-lbl">Último Movimiento</span>
                        <span class="mr-val" id="mrResDate">21-May-2026 09:12</span>
                    </div>
                </div>
            </div>
            <div id="manifestErrorMsg" style="display:none; color:#EF4444; font-size:13px; font-weight:600; text-align:center; padding:10px; border:1px solid #FCA5A5; background:#FEF2F2; border-radius:8px;">
                Código de contenedor no encontrado. Pruebe con: APM-83492, APM-91048 o APM-12495.
            </div>
        </div>
    </div>

    <!-- Turnos Modal -->
    <div class="services-overlay" id="serviceModalTurnos" onclick="if(event.target===this) closeServiceModal('turnos')">
        <div class="services-modal">
            <button class="sm-close" onclick="closeServiceModal('turnos')"><i class="fa-solid fa-xmark"></i></button>
            <div class="sm-header-block">
                <div class="sm-icon" style="color:var(--accent-hover); font-size:24px"><i class="fa-solid fa-calendar-days"></i></div>
                <div class="sm-title-text">
                    <h2>Turnos y Flujo en Esclusas de Ingreso</h2>
                    <p>Monitoreo del tráfico y tiempos de espera para transportistas de carga pesada</p>
                </div>
            </div>
            
            <div class="docking-board">
                <div style="font-weight:700; color:var(--text-app); font-size:14px; margin-bottom:12px;">Esclusas de Acceso Activas</div>
                <div class="docking-list">
                    <div class="sched-item" style="padding:10px 14px">
                        <div class="sc-left">
                            <span style="font-weight:700; font-size:12.5px; color:var(--text-app)">Esclusa Norte (Ingreso Contenedores)</span>
                            <span class="sc-line">Estado: Activo · Tiempos de espera: ~12 minutos</span>
                        </div>
                        <span class="dr-badge" style="background:#10B981">Tránsito Fluido</span>
                    </div>
                    <div class="sched-item" style="padding:10px 14px">
                        <div class="sc-left">
                            <span style="font-weight:700; font-size:12.5px; color:var(--text-app)">Esclusa Central (Carga Fraccionada)</span>
                            <span class="sc-line">Estado: Activo · Tiempos de espera: ~25 minutos</span>
                        </div>
                        <span class="dr-badge" style="background:#F59E0B">Tránsito Medio</span>
                    </div>
                </div>
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
        let welcomeSlideIndex = 0;
        let newsTickerIndex = 0;

        document.addEventListener("DOMContentLoaded", function() {
            lucide.createIcons();
            
            // Start the news ticker automatic rotation
            setInterval(rotateNewsTicker, 5000);
            
            // Initialize tariff simulation first values
            updateTariffCalculation();

            // Start the background image slideshow rotation
            let currentBgSlide = 0;
            const bgSlides = document.querySelectorAll('.slideshow-bg .slide-img');
            const bgCount = bgSlides.length;
            if (bgCount > 0) {
                setInterval(() => {
                    const prev = currentBgSlide;
                    currentBgSlide = (currentBgSlide + 1) % bgCount;
                    if (bgSlides[prev]) bgSlides[prev].classList.remove('active');
                    if (bgSlides[currentBgSlide]) bgSlides[currentBgSlide].classList.add('active');
                }, 5000);
            }
        });

        // Welcome Modal slides controls
        function closeWelcomeModal() {
            document.getElementById("welcomeModal").classList.remove("show");
        }

        function setWelcomeSlide(index) {
            welcomeSlideIndex = index;
            const slides = document.querySelectorAll(".wc-slide");
            const dots = document.querySelectorAll(".wc-dot");
            
            slides.forEach((s, idx) => {
                s.classList.toggle("active", idx === index);
            });
            dots.forEach((d, idx) => {
                d.classList.toggle("active", idx === index);
            });

            const btnNext = document.getElementById("btnNextWelcome");
            if (index === slides.length - 1) {
                btnNext.innerHTML = 'Comenzar <i class="fa-solid fa-circle-check"></i>';
            } else {
                btnNext.innerHTML = 'Siguiente <i class="fa-solid fa-arrow-right"></i>';
            }
        }

        function nextWelcomeSlide() {
            const slides = document.querySelectorAll(".wc-slide");
            if (welcomeSlideIndex < slides.length - 1) {
                setWelcomeSlide(welcomeSlideIndex + 1);
            } else {
                closeWelcomeModal();
            }
        }

        // Folder Modal operations
        function openFolders() {
            document.getElementById("moduleFoldersOverlay").classList.add("show");
        }

        function closeFolders() {
            document.getElementById("moduleFoldersOverlay").classList.remove("show");
        }

        // Service Modals operations
        function openServiceModal(id) {
            const modalId = "serviceModal" + id.charAt(0).toUpperCase() + id.slice(1);
            document.getElementById(modalId).classList.add("show");
        }

        function closeServiceModal(id) {
            const modalId = "serviceModal" + id.charAt(0).toUpperCase() + id.slice(1);
            document.getElementById(modalId).classList.remove("show");
        }

        // News ticker slider
        function rotateNewsTicker() {
            const slides = document.querySelectorAll(".news-slide");
            slides[newsTickerIndex].classList.remove("active");
            
            newsTickerIndex = (newsTickerIndex + 1) % slides.length;
            slides[newsTickerIndex].classList.add("active");
        }

        // Tariff Calculator Algorithm
        function updateTariffCalculation() {
            const type = document.getElementById("calcLoadType").value;
            const weight = parseFloat(document.getElementById("calcWeight").value) || 1;
            const days = parseFloat(document.getElementById("calcDays").value) || 1;
            const operator = document.getElementById("calcOperator").value;

            let muellaje = 0;
            let patio = 0;
            let operacion = 0;

            if (type === 'cnt20') {
                muellaje = 45.00 * weight;
                patio = 8.50 * weight * days;
                operacion = 12.00 * weight;
            } else if (type === 'cnt40') {
                muellaje = 75.00 * weight;
                patio = 14.00 * weight * days;
                operacion = 22.00 * weight;
            } else if (type === 'reefer') {
                muellaje = 90.00 * weight;
                patio = 25.00 * weight * days; // Includes electrical hookup
                operacion = 30.00 * weight;
            } else if (type === 'bulk') {
                muellaje = 2.50 * weight; // Per Ton
                patio = 0.60 * weight * days;
                operacion = 1.10 * weight;
            }

            if (operator === 'prio') {
                operacion *= 1.35; // Express estiba surcharge
            }

            const total = muellaje + patio + operacion;

            document.getElementById("calcResMuellaje").innerText = "$" + muellaje.toFixed(2);
            document.getElementById("calcResPatio").innerText = "$" + patio.toFixed(2);
            document.getElementById("calcResOperacion").innerText = "$" + operacion.toFixed(2);
            document.getElementById("calcResTotal").innerText = "$" + total.toFixed(2);
        }

        // Container manifests mock search engine
        function searchContainerManifest() {
            const query = document.getElementById("manifestSearchCode").value.trim().toUpperCase();
            const resultBox = document.getElementById("manifestResultBox");
            const errorMsg = document.getElementById("manifestErrorMsg");
            
            const database = {
                "APM-83492": { consignee: "Importadora Manta S.A.", type: "40 FT Dry Cargo", location: "Zona C - Bloque 4 - Fila 2", date: "21-May-2026 09:12", status: "Liberado", statusColor: "#10B981" },
                "APM-91048": { consignee: "Pesquera Oceanía Cía. Ltda.", type: "40 FT Reefer Cargo", location: "Zona Refrigerados A - Enchufe 14", date: "22-May-2026 05:45", status: "En Inspección Senae", statusColor: "#F59E0B" },
                "APM-12495": { consignee: "Corporación Logística del Pacífico", type: "20 FT Dry Cargo", location: "Zona B - Bloque 1 - Fila 5", date: "20-May-2026 14:30", status: "Liberado", statusColor: "#10B981" }
            };

            if (database[query]) {
                const info = database[query];
                document.getElementById("mrResCode").innerText = query;
                document.getElementById("mrResStatus").innerText = info.status;
                document.getElementById("mrResStatus").style.background = info.statusColor;
                document.getElementById("mrResConsignee").innerText = info.consignee;
                document.getElementById("mrResType").innerText = info.type;
                document.getElementById("mrResLocation").innerText = info.location;
                document.getElementById("mrResDate").innerText = info.date;
                
                resultBox.style.display = "flex";
                errorMsg.style.display = "none";
            } else {
                resultBox.style.display = "none";
                errorMsg.style.display = "block";
            }
        }
    </script>
</body>
</html>
