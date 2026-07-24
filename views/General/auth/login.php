<?php
/**
 * Login view. Sleek, professional split layout with theme selection and demo account autocomplete.
 * Fully parameterized to display personalized department branding, color schemes, and demo accounts.
 */
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$baseUrl = dirname($scriptName);
$baseUrl = str_replace('\\', '/', $baseUrl);
if ($baseUrl === '/' || $baseUrl === '\\') {
    $baseUrl = '';
}

// Personalized configuration per department/module
$moduleConfig = [
    'General' => [
        'title' => 'Portal Único Corporativo — SysPort',
        'header_title' => 'Iniciar sesión',
        'header_desc' => 'Ingresa tus credenciales institucionales para ingresar a SysPort',
        'brand_sub' => 'Portal Único Corporativo · Sistema integrado de gestión y acceso unificado para los departamentos de la Autoridad Portuaria de Manta.',
        'color_p' => '#1A3A5C',
        'color_p2' => '#2E75B6',
        'color_p3' => '#EBF4FD',
        'tag_icon' => 'fa-shield-halved',
        'tag_text' => 'Acceso Seguro SSO',
        'demo_users' => ['admin', 'fmora', 'acastro']
    ],
    'TH' => [
        'title' => 'Talento Humano — SysPort',
        'header_title' => 'Acceso Talento Humano',
        'header_desc' => 'Ingresa al sistema de Talento Humano para gestionar personal y nómina.',
        'brand_sub' => 'Departamento de Talento Humano · Control de fichas del personal, marcaciones, novedades médicas y gestión institucional de APM.',
        'color_p' => '#8B5CF6',
        'color_p2' => '#7C3AED',
        'color_p3' => '#F5F3FF',
        'tag_icon' => 'fa-users-gear',
        'tag_text' => 'Módulo Talento Humano',
        'demo_users' => ['admin', 'fmora']
    ],
    'Bienes' => [
        'title' => 'Control de Bienes — SysPort',
        'header_title' => 'Acceso Control de Bienes',
        'header_desc' => 'Ingresa para administrar el inventario y activos fijos portuarios.',
        'brand_sub' => 'Área de Control de Bienes · Custodia de equipamiento, registro de activos institucionales y auditoría física de APM.',
        'color_p' => '#10B981',
        'color_p2' => '#059669',
        'color_p3' => '#ECFDF5',
        'tag_icon' => 'fa-boxes-packing',
        'tag_text' => 'Control de Bienes',
        'demo_users' => ['admin']
    ],
    'Acceso' => [
        'title' => 'Control de Acceso — SysPort',
        'header_title' => 'Acceso Seguridad Perimetral',
        'header_desc' => 'Ingresa para auditar usuarios, roles y accesos perimetrales.',
        'brand_sub' => 'Área de Seguridad Digital · Matriz de control, perfiles de colaboradores y auditoría transaccional de sistemas.',
        'color_p' => '#0284C7',
        'color_p2' => '#0369A1',
        'color_p3' => '#F0F9FF',
        'tag_icon' => 'fa-lock-open',
        'tag_text' => 'Seguridad y Permisos',
        'demo_users' => ['admin', 'fmora']
    ],
    'Bitacoras' => [
        'title' => 'Bitácoras y Novedades — SysPort',
        'header_title' => 'Acceso Bitácoras Operativas',
        'header_desc' => 'Ingresa para registrar sucesos y novedades diarias de la Portuaria.',
        'brand_sub' => 'Bitácoras y Novedades APM · Registro de incidencias en muelles, reportes de turnos, y monitoreo operativo en tiempo real.',
        'color_p' => '#8B5CF6',
        'color_p2' => '#7C3AED',
        'color_p3' => '#F5F3FF',
        'tag_icon' => 'fa-book-journal-whills',
        'tag_text' => 'Bitácoras Digitales',
        'demo_users' => ['fmora', 'acastro']
    ],
    'Financiero' => [
        'title' => 'Dirección Financiera — SysPort',
        'header_title' => 'Acceso Dirección Financiera',
        'header_desc' => 'Ingresa para gestionar auditorías presupuestarias y contables.',
        'brand_sub' => 'Dirección Financiera APM · Facturación de tasas portuarias, contabilidad general y conciliaciones bancarias oficiales.',
        'color_p' => '#2563EB',
        'color_p2' => '#1D4ED8',
        'color_p3' => '#EFF6FF',
        'tag_icon' => 'fa-scale-balanced',
        'tag_text' => 'Gestión Financiera',
        'demo_users' => ['admin', 'acastro']
    ],
    'Juridica' => [
        'title' => 'Asesoría Jurídica — SysPort',
        'header_title' => 'Acceso Asesoría Jurídica',
        'header_desc' => 'Ingresa al sistema para gestionar contratos, resoluciones y consultas.',
        'brand_sub' => 'Dirección de Asesoría Jurídica · Redacción, validación y archivo digital de la base legal institucional de la Autoridad Portuaria de Manta.',
        'color_p' => '#B45309',
        'color_p2' => '#92400E',
        'color_p3' => '#FEF3C7',
        'tag_icon' => 'fa-scale-balanced',
        'tag_text' => 'Consultas Legales APM',
        'demo_users' => ['admin']
    ],
    'Infraestructura' => [
        'title' => 'Infraestructuras Portuarias — SysPort',
        'header_title' => 'Acceso Infraestructuras',
        'header_desc' => 'Ingresa al sistema para controlar accesos perimetrales y rondas CCTV.',
        'brand_sub' => 'Infraestructuras Portuarias · Control perimetral en tiempo real, registro de bitácoras físicas de muelles y control de rondas.',
        'color_p' => '#0284c7',
        'color_p2' => '#0369a1',
        'color_p3' => '#f0f9ff',
        'tag_icon' => 'fa-industry',
        'tag_text' => 'Monitoreo Perimetral',
        'demo_users' => ['admin', 'fmora']
    ],
    'Gerencia' => [
        'title' => 'Gerencia General — SysPort',
        'header_title' => 'Acceso Gerencia General',
        'header_desc' => 'Ingresa para visualizar KPIs estratégicos y reportes ejecutivos consolidados.',
        'brand_sub' => 'Gerencia General APM · Cuadro de mando ejecutivo, telemetría operativa y toma de decisiones corporativas en tiempo real.',
        'color_p' => '#4F46E5',
        'color_p2' => '#4338CA',
        'color_p3' => '#EEF2FF',
        'tag_icon' => 'fa-briefcase',
        'tag_text' => 'Control Ejecutivo',
        'demo_users' => ['admin', 'acastro']
    ],
    'Admin' => [
        'title' => 'Dirección Administrativa — SysPort',
        'header_title' => 'Acceso Dirección Administrativa',
        'header_desc' => 'Ingresa al sistema para registrar activos fijos y suministros portuarios.',
        'brand_sub' => 'Dirección Administrativa APM · Control patrimonial de equipamiento, inventario técnico de almacenes y suministros.',
        'color_p' => '#10B981',
        'color_p2' => '#059669',
        'color_p3' => '#ECFDF5',
        'tag_icon' => 'fa-gear',
        'tag_text' => 'Control de Bienes',
        'demo_users' => ['admin']
    ],
    'DatabaseAdmin' => [
        'title' => 'Administración de Base de Datos — SysPort',
        'header_title' => 'Acceso Diccionario y ERD',
        'header_desc' => 'Ingresa para auditar el esquema relacional DDL de SQL Server.',
        'brand_sub' => 'Administración Técnica APM · Modelo Entidad-Relación (ERD) interactivo y definición transaccional de esquemas.',
        'color_p' => '#14B8A6',
        'color_p2' => '#0D9488',
        'color_p3' => '#F0FDFA',
        'tag_icon' => 'fa-diagram-project',
        'tag_text' => 'Administración de Datos',
        'demo_users' => ['admin']
    ]
];

$module = $module ?? 'General';
$cfg = $moduleConfig[$module] ?? $moduleConfig['General'];

// Form submission endpoint routing based on module
$formAction = $baseUrl . '/login';
if ($module === 'TH') {
    $formAction = $baseUrl . '/talento-humano/login';
} elseif ($module === 'Bienes') {
    $formAction = $baseUrl . '/control-bienes/login';
} elseif ($module === 'Acceso') {
    $formAction = $baseUrl . '/control-acceso/login';
} elseif ($module === 'Bitacoras') {
    $formAction = $baseUrl . '/bitacoras/login';
} elseif ($module === 'Financiero') {
    $formAction = $baseUrl . '/financiero/login';
}

$demoUsersData = [
    'admin' => [
        'user' => 'admin',
        'pass' => 'admin',
        'avatar' => 'AD',
        'color' => '#1A3A5C',
        'name' => 'Administrador del Sistema',
        'role' => 'Dirección TI · Acceso total',
        'cred' => 'admin'
    ],
    'fmora' => [
        'user' => 'fmora',
        'pass' => 'fm',
        'avatar' => 'FM',
        'color' => '#0369A1',
        'name' => 'Ing. Felipe Mora García',
        'role' => 'Jefe de Infraestructuras · MFA',
        'cred' => 'fm'
    ],
    'acastro' => [
        'user' => 'acastro',
        'pass' => 'ac',
        'avatar' => 'AC',
        'color' => '#7C3AED',
        'name' => 'Dra. Ana Castro Lara',
        'role' => 'Gerente General · Acceso Ejecutivo',
        'cred' => 'ac'
    ]
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($cfg['title']) ?></title>
    
    <!-- Google Fonts: Sora & Fira Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700;800&family=Fira+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- FontAwesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* ═══════════════════════════════════════════════════
           VARIABLES Y ESTILOS BASE DE LOGIN
           ═══════════════════════════════════════════════════ */
        :root {
            --p: <?= $cfg['color_p'] ?>;
            --p2: <?= $cfg['color_p2'] ?>;
            --p3: <?= $cfg['color_p3'] ?>;
            --success: #059669;
            --warn: #D97706;
            --danger: #DC2626;
            --surface: #ffffff;
            --bg: #F0F4FA;
            --border: #DDE4EF;
            --text: #162032;
            --muted: #667085;
            --r: 10px;
            --r2: 16px;
            --ease: .22s cubic-bezier(.4, 0, .2, 1);
            --shadow: 0 1px 4px rgba(0,0,0,.1), 0 2px 12px rgba(0,0,0,.06);
            --shadow-xl: 0 20px 60px rgba(0,0,0,.18);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Sora', sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            overflow-x: hidden;
            margin: 0;
        }

        button, input {
            font-family: inherit;
        }

        /* ═══════════════════════════════════════════════════
           THEME SWITCHER
           ═══════════════════════════════════════════════════ */
        .theme-switcher {
            position: fixed;
            top: 16px;
            right: 16px;
            z-index: 999;
            display: flex;
            gap: 6px;
        }

        .ts-btn {
            padding: 8px 14px;
            border: none;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .04em;
            text-transform: uppercase;
            cursor: pointer;
            transition: var(--ease);
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        /* ═══════════════════════════════════════════════════
           SPLIT PANEL LAYOUT
           ═══════════════════════════════════════════════════ */
        #login {
            display: flex;
            flex-direction: row;
            min-height: 100vh;
            width: 100%;
            position: relative;
        }

        /* LEFT PANEL (BRANDING) */
        .login-brand {
            width: 44%;
            background: var(--p);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 60px 48px;
            position: relative;
            overflow: hidden;
            box-shadow: 4px 0 24px rgba(0,0,0,0.15);
        }

        .lb-waves {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            opacity: .08;
            pointer-events: none;
        }

        .lb-logo {
            width: 80px;
            height: 80px;
            background: rgba(255,255,255,.12);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 28px;
            border: 1px solid rgba(255,255,255,.2);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
        }

        .lb-logo i {
            font-size: 36px;
            color: #ffffff;
        }

        .lb-title {
            font-size: 28px;
            font-weight: 800;
            color: #ffffff;
            line-height: 1.15;
            text-align: center;
            margin-bottom: 16px;
            font-family: 'Sora', sans-serif;
            text-transform: uppercase;
            letter-spacing: 0.02em;
        }

        .lb-sub {
            font-size: 13.5px;
            color: rgba(255,255,255,.7);
            text-align: center;
            line-height: 1.6;
            max-width: 320px;
        }

        .lb-tag {
            margin-top: 40px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(255,255,255,.1);
            border: 1px solid rgba(255,255,255,.15);
            border-radius: 999px;
            padding: 8px 18px;
        }

        .lb-tag i {
            color: rgba(255,255,255,.9);
            font-size: 13px;
        }

        .lb-tag span {
            font-size: 12px;
            color: rgba(255,255,255,.9);
            font-weight: 600;
            letter-spacing: 0.02em;
        }

        /* RIGHT PANEL (FORM) */
        .login-form-wrap {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 40px 48px;
            background: #ffffff;
            position: relative;
        }

        .login-card {
            width: 100%;
            max-width: 420px;
        }

        .lc-head {
            margin-bottom: 32px;
        }

        .lc-head h2 {
            font-size: 28px;
            font-weight: 800;
            color: var(--text);
            margin-bottom: 8px;
            letter-spacing: -0.02em;
        }

        .lc-head p {
            font-size: 14px;
            color: var(--muted);
            line-height: 1.5;
        }

        .field {
            margin-bottom: 20px;
        }

        .field label {
            display: block;
            font-size: 11px;
            font-weight: 700;
            color: var(--muted);
            letter-spacing: .06em;
            text-transform: uppercase;
            margin-bottom: 8px;
        }

        .field-wrap {
            position: relative;
        }

        .field-wrap i.fi {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #aab;
            font-size: 15px;
            pointer-events: none;
        }

        .field input {
            width: 100%;
            padding: 13px 14px 13px 42px;
            border: 1.5px solid var(--border);
            border-radius: var(--r);
            font-size: 14px;
            color: var(--text);
            background: var(--bg);
            transition: var(--ease);
            outline: none;
        }

        .field input:focus {
            border-color: var(--p2);
            background: #ffffff;
            box-shadow: 0 0 0 3px rgba(46,117,182,.15);
        }

        .field-wrap .eye {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: #aab;
            font-size: 14px;
            padding: 4px;
            outline: none;
            transition: color 0.2s ease;
        }

        .field-wrap .eye:hover {
            color: var(--p2);
        }

        .btn-login {
            width: 100%;
            padding: 14px;
            background: var(--p);
            color: #ffffff;
            border: none;
            border-radius: var(--r);
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: var(--ease);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            box-shadow: 0 4px 12px rgba(26,58,92,.15);
        }

        .btn-login:hover {
            background: var(--p2);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(46,117,182,.3);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .btn-back {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            color: #64748B;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            padding: 12px 16px;
            margin-top: 16px;
            border-radius: var(--r);
            transition: var(--ease);
            width: 100%;
            border: 1.5px solid var(--border);
            background: transparent;
        }

        .btn-back:hover {
            border-color: var(--p);
            color: var(--p);
            background: rgba(26, 58, 92, 0.02);
            transform: translateY(-1px);
        }

        /* Tema 2 overrides */
        #login.t2 .btn-back {
            border-color: #1E2D40;
            color: #94A3B8;
        }
        #login.t2 .btn-back:hover {
            border-color: #3B82F6;
            color: #3B82F6;
            background: rgba(59, 130, 246, 0.05);
        }

        /* Tema 3 overrides */
        #login.t3 .btn-back {
            border-color: rgba(255, 255, 255, 0.12);
            color: rgba(255, 255, 255, 0.7);
        }
        #login.t3 .btn-back:hover {
            border-color: rgba(255, 255, 255, 0.4);
            color: #ffffff;
            background: rgba(255, 255, 255, 0.06);
        }

        .login-err {
            background: #FEF2F2;
            border: 1.5px solid #FCA5A5;
            border-radius: var(--r);
            padding: 12px 16px;
            margin-bottom: 24px;
            font-size: 13.5px;
            color: var(--danger);
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 500;
        }

        /* DEMO ACCOUNTS ACCORDION */
        .demo-accounts {
            margin-top: 32px;
            border-top: 1px solid var(--border);
            padding-top: 24px;
        }

        .da-title {
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .06em;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: 12px;
        }

        .da-card {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 14px;
            border-radius: var(--r);
            background: var(--bg);
            border: 1.5px solid transparent;
            cursor: pointer;
            transition: var(--ease);
            margin-bottom: 8px;
        }

        .da-card:hover {
            border-color: var(--p2);
            background: var(--p3);
        }

        .da-avatar {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 700;
            color: #ffffff;
            flex-shrink: 0;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        }

        .da-info {
            flex: 1;
            min-width: 0;
        }

        .da-name {
            font-size: 13px;
            font-weight: 700;
            color: var(--text);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .da-role {
            font-size: 11px;
            color: var(--muted);
            margin-top: 2px;
        }

        .da-cred {
            font-family: 'Fira Code', monospace;
            font-size: 10.5px;
            color: #7a8a9e;
            background: rgba(0,0,0,.05);
            padding: 3px 8px;
            border-radius: 5px;
            white-space: nowrap;
        }

        .login-footer {
            margin-top: 40px;
            text-align: center;
            font-size: 11px;
            color: var(--muted);
        }

        /* ═══════════════════════════════════════════════════
           THEME SPECIFIC STYLE OVERRIDES
           ═══════════════════════════════════════════════════ */
        
        /* --- TEMA 1: INSTITUCIONAL --- */
        #login.t1 {
            background: #ffffff;
        }
        #login.t1 .ts-btn[data-theme="1"] {
            background: #1A3A5C;
            color: #ffffff;
            box-shadow: 0 0 0 2px #fff, 0 0 0 4px #2E75B6;
        }
        #login.t1 .ts-btn[data-theme="2"] { background: #E2E8F0; color: #475569; }
        #login.t1 .ts-btn[data-theme="3"] { background: #E2E8F0; color: #475569; }

        /* --- TEMA 2: CYBER DARK --- */
        #login.t2 {
            background: #060C18;
        }
        #login.t2 .ts-btn[data-theme="2"] {
            background: #0D1830;
            color: #38BDF8;
            box-shadow: 0 0 0 2px #060C18, 0 0 0 4px #38BDF8;
        }
        #login.t2 .ts-btn[data-theme="1"] { background: #1E293B; color: #94A3B8; }
        #login.t2 .ts-btn[data-theme="3"] { background: #1E293B; color: #94A3B8; }

        #login.t2 .login-brand {
            background: linear-gradient(160deg, #0D1830 0%, #0F2544 100%);
        }
        #login.t2 .login-form-wrap {
            background: #060C18;
        }
        #login.t2 .lc-head h2 { color: #E2E8F0; }
        #login.t2 .lc-head p { color: #64748B; }
        #login.t2 .field label { color: #475569; }
        #login.t2 .field input {
            background: #0D1830;
            border-color: #1E3A5F;
            color: #E2E8F0;
        }
        #login.t2 .field input:focus {
            background: #0F2544;
            border-color: #3B82F6;
            box-shadow: 0 0 0 3px rgba(59,130,246,.2);
        }
        #login.t2 .btn-login {
            background: linear-gradient(135deg, #2563EB, #1D4ED8);
            box-shadow: 0 4px 12px rgba(37,99,235,.2);
        }
        #login.t2 .btn-login:hover {
            background: linear-gradient(135deg, #3B82F6, #2563EB);
            box-shadow: 0 6px 20px rgba(59,130,246,.4);
        }
        #login.t2 .demo-accounts { border-color: #1E2D40; }
        #login.t2 .da-title { color: #475569; }
        #login.t2 .da-card { background: #0D1830; border-color: #1E2D40; }
        #login.t2 .da-card:hover { border-color: #3B82F6; background: #0F2544; }
        #login.t2 .da-name { color: #CBD5E1; }
        #login.t2 .da-cred { background: #1E2D40; color: #64748B; }
        #login.t2 .login-err { background: #1C1020; border-color: #7F1D1D; color: #FCA5A5; }
        #login.t2 .login-footer { color: #475569; }

        /* --- TEMA 3: PORTO GLASS --- */
        #login.t3 {
            background: linear-gradient(135deg, <?= $cfg['color_p'] ?> 0%, #065F46 100%);
        }
        #login.t3 .ts-btn[data-theme="3"] {
            background: #0C4A6E;
            color: #7DD3FC;
            box-shadow: 0 0 0 2px #0B3C58, 0 0 0 4px #0EA5E9;
        }
        #login.t3 .ts-btn[data-theme="1"] { background: rgba(255,255,255,.1); color: #fff; }
        #login.t3 .ts-btn[data-theme="2"] { background: rgba(255,255,255,.1); color: #fff; }

        #login.t3 .login-brand {
            background: rgba(0,0,0,.25);
            backdrop-filter: blur(12px);
            border-right: 1px solid rgba(255,255,255,.08);
            box-shadow: none;
        }
        #login.t3 .login-form-wrap {
            background: rgba(0,0,0,.2);
            backdrop-filter: blur(16px);
        }
        #login.t3 .lc-head h2 { color: #ffffff; }
        #login.t3 .lc-head p { color: rgba(255,255,255,.65); }
        #login.t3 .field label { color: rgba(255,255,255,.5); }
        #login.t3 .field input {
            background: rgba(255,255,255,.08);
            border-color: rgba(255,255,255,.12);
            color: #ffffff;
        }
        #login.t3 .field input::placeholder { color: rgba(255,255,255,.3); }
        #login.t3 .field input:focus {
            background: rgba(255,255,255,.12);
            border-color: rgba(255,255,255,.4);
            box-shadow: 0 0 0 3px rgba(255,255,255,.08);
        }
        #login.t3 .field-wrap i.fi { color: rgba(255,255,255,.4); }
        #login.t3 .field-wrap .eye { color: rgba(255,255,255,.4); }
        #login.t3 .field-wrap .eye:hover { color: #ffffff; }
        #login.t3 .btn-login {
            background: linear-gradient(135deg, #0891B2, #059669);
            box-shadow: 0 4px 12px rgba(8,145,178,.25);
        }
        #login.t3 .btn-login:hover {
            background: linear-gradient(135deg, #0EA5E9, #10B981);
            box-shadow: 0 6px 20px rgba(14,165,233,.45);
        }
        #login.t3 .demo-accounts { border-color: rgba(255,255,255,.1); }
        #login.t3 .da-title { color: rgba(255,255,255,.4); }
        #login.t3 .da-card { background: rgba(255,255,255,.06); border-color: rgba(255,255,255,.08); }
        #login.t3 .da-card:hover { border-color: rgba(14,165,233,.6); background: rgba(255,255,255,.1); }
        #login.t3 .da-name { color: rgba(255,255,255,.9); }
        #login.t3 .da-role { color: rgba(255,255,255,.55); }
        #login.t3 .da-cred { background: rgba(0,0,0,.2); color: rgba(255,255,255,.4); }
        #login.t3 .login-err { background: rgba(220,38,38,.15); border-color: rgba(220,38,38,.3); color: #FCA5A5; }
        #login.t3 .login-footer { color: rgba(255,255,255,.4); }

        /* ═══════════════════════════════════════════════════
           ANIMATED WAVES (T3 SPECIAL EFFECT)
           ═══════════════════════════════════════════════════ */
        .wave-anim {
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            z-index: 0;
            opacity: .15;
            pointer-events: none;
        }

        @keyframes waveMove {
            0% { transform: translateX(0); }
            100% { transform: translateX(-50%); }
        }

        .wave-path {
            animation: waveMove 8s linear infinite;
        }

        /* ═══════════════════════════════════════════════════
           RESPONSIVE DESIGN
           ═══════════════════════════════════════════════════ */
        @media (max-width: 992px) {
            .login-brand {
                width: 40%;
                padding: 40px 24px;
            }
            .lb-title {
                font-size: 22px;
            }
            .lb-sub {
                font-size: 12px;
            }
            .login-form-wrap {
                padding: 40px 32px;
            }
        }

        @media (max-width: 768px) {
            #login {
                flex-direction: column;
            }
            .login-brand {
                width: 100%;
                min-height: auto;
                padding: 48px 24px;
            }
            .lb-logo {
                width: 64px;
                height: 64px;
                margin-bottom: 16px;
            }
            .lb-logo i {
                font-size: 28px;
            }
            .lb-title {
                font-size: 20px;
                margin-bottom: 8px;
            }
            .lb-tag {
                margin-top: 24px;
                padding: 6px 14px;
            }
            .login-form-wrap {
                padding: 48px 24px;
                min-height: auto;
            }
            .theme-switcher {
                position: absolute;
                top: 12px;
                right: 12px;
            }
        }
    </style>
</head>
<body class="login-layout-body">

    <!-- 1. Floating Theme Switcher -->
    <div class="theme-switcher" id="themeSwitcher">
        <button class="ts-btn" data-theme="1" onclick="setTheme(1)">⚓ Inst.</button>
        <button class="ts-btn" data-theme="2" onclick="setTheme(2)">◈ Cyber</button>
        <button class="ts-btn" data-theme="3" onclick="setTheme(3)">〜 Porto</button>
    </div>

    <!-- 2. Screen Split Login Container -->
    <div class="screen active t1" id="login">

        <!-- Theme 3 Decorative animated sea waves -->
        <svg class="wave-anim" id="waveSvg" viewBox="0 0 1440 100" preserveAspectRatio="none" style="display:none">
            <path class="wave-path" fill="rgba(255,255,255,1)"
                d="M0,50 C240,90 480,10 720,50 C960,90 1200,10 1440,50 L1440,100 L0,100 Z M1440,50 C1680,90 1920,10 2160,50 C2400,90 2640,10 2880,50 L2880,100 L1440,100 Z"/>
        </svg>

        <!-- LEFT PANEL: Branding & Visuals -->
        <div class="login-brand">
            <div class="lb-waves">
                <svg viewBox="0 0 400 200" width="100%" height="auto"><path fill="currentColor" d="M0 100 Q100 20 200 100 Q300 180 400 100 L400 200 L0 200Z"/></svg>
            </div>
            <div class="lb-logo">
                <i class="fa-solid fa-anchor"></i>
            </div>
            <h1 class="lb-title">SysPort</h1>
            <div class="lb-sub"><?= htmlspecialchars($cfg['brand_sub']) ?></div>
            <div class="lb-tag">
                <i class="fa-solid <?= htmlspecialchars($cfg['tag_icon']) ?>"></i>
                <span><?= htmlspecialchars($cfg['tag_text']) ?></span>
            </div>
        </div>

        <!-- RIGHT PANEL: Authentication Form -->
        <div class="login-form-wrap">
            <div class="login-card">
                <div class="lc-head">
                    <h2><?= htmlspecialchars($cfg['header_title']) ?></h2>
                    <p><?= htmlspecialchars($cfg['header_desc']) ?></p>
                </div>

                <!-- Error Messages Box -->
                <?php if (!empty($error)): ?>
                    <div class="login-err" id="loginErrBox">
                        <i class="fa-solid fa-circle-exclamation"></i>
                        <span><?= htmlspecialchars($error) ?></span>
                    </div>
                <?php endif; ?>

                <!-- Real Form Submission POST -->
                <form action="<?= $formAction ?>" method="POST" id="login-form">
                    <div class="field">
                        <label for="username">Usuario Institucional</label>
                        <div class="field-wrap">
                            <i class="fa-solid fa-user fi"></i>
                            <input type="text" id="username" name="username" placeholder="nombre.apellido" required autocomplete="username">
                        </div>
                    </div>

                    <div class="field">
                        <label for="password">Contraseña</label>
                        <div class="field-wrap">
                            <i class="fa-solid fa-lock fi"></i>
                            <input type="password" id="password" name="password" placeholder="••••••••••••" required autocomplete="current-password">
                            <button type="button" class="eye" id="toggle-password-btn" title="Mostrar contraseña">
                                <i class="fa-solid fa-eye" id="eyeIcon"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn-login">
                        <span class="btn-txt">Ingresar al portal</span>
                        <i class="fa-solid fa-arrow-right-to-bracket"></i>
                    </button>

                    <a href="<?= $baseUrl ?>/" class="btn-back">
                        <i class="fa-solid fa-arrow-left"></i> Volver a la Página Principal
                    </a>
                </form>

                <!-- Demo Accounts Autocomplete Grid -->
                <div class="demo-accounts">
                    <div class="da-title">Cuentas de demostración (autocompletar)</div>
                    
                    <?php 
                    foreach ($cfg['demo_users'] as $usernameKey) {
                        if (isset($demoUsersData[$usernameKey])) {
                            $u = $demoUsersData[$usernameKey];
                            ?>
                            <div class="da-card" onclick="fillDemo('<?= htmlspecialchars($u['user']) ?>','<?= htmlspecialchars($u['pass']) ?>')">
                                <div class="da-avatar" style="background:<?= htmlspecialchars($u['color']) ?>"><?= htmlspecialchars($u['avatar']) ?></div>
                                <div class="da-info">
                                    <div class="da-name"><?= htmlspecialchars($u['name']) ?></div>
                                    <div class="da-role"><?= htmlspecialchars($u['role']) ?></div>
                                </div>
                                <div class="da-cred"><?= htmlspecialchars($u['cred']) ?></div>
                            </div>
                            <?php
                        }
                    }
                    ?>
                </div>

                <div class="login-footer">
                    <span>© <?= date('Y') ?> Autoridad Portuaria de Manta. Módulo de Autenticación & Auditoría Perimetral.</span>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════
       INTERACTIVE JAVASCRIPT
       ═══════════════════════════════════════════════════ -->
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Restore theme from localStorage or fallback to 1
            const savedTheme = localStorage.getItem('apm_theme') || '1';
            setTheme(parseInt(savedTheme));

            // Eye password toggle
            const passwordInput = document.getElementById("password");
            const togglePasswordBtn = document.getElementById("toggle-password-btn");
            const eyeIcon = document.getElementById("eyeIcon");

            togglePasswordBtn.addEventListener("click", function() {
                if (passwordInput.type === "password") {
                    passwordInput.type = "text";
                    eyeIcon.className = "fa-solid fa-eye-slash";
                } else {
                    passwordInput.type = "password";
                    eyeIcon.className = "fa-solid fa-eye";
                }
            });
        });

        // Theme switching handler
        function setTheme(t) {
            const loginScreen = document.getElementById("login");
            loginScreen.className = 'screen active t' + t;
            
            // Toggle wave visibility
            const waveSvg = document.getElementById("waveSvg");
            if (waveSvg) {
                waveSvg.style.display = t === 3 ? 'block' : 'none';
            }

            // Sync active theme state in buttons
            document.querySelectorAll('.ts-btn').forEach((btn, idx) => {
                btn.classList.toggle('active', idx + 1 === t);
            });

            // Save theme in localStorage for persistence
            localStorage.setItem('apm_theme', t);
        }

        // Demo accounts fill script
        function fillDemo(user, pass) {
            document.getElementById("username").value = user;
            document.getElementById("password").value = pass;
            
            // Force password field to be visible as dots initially
            const passwordInput = document.getElementById("password");
            const eyeIcon = document.getElementById("eyeIcon");
            passwordInput.type = "password";
            eyeIcon.className = "fa-solid fa-eye";
        }
    </script>
</body>
</html>
