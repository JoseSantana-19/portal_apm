<?php
/* perfil.php – Vista: Expediente Digital del Funcionario (Solo lectura)
   Muestra los datos del empleado en un formato tipo currículum visual.
   En producción, los datos se cargan desde la BD usando $cedula recibida del controlador. */

$cedula   = $cedula   ?? '1308126646';
$empleado = $empleado ?? [];

// Datos simulados por cédula (modo prototipo)
$perfilesMock = [
    '1308126646' => [
        'cedula'       => '1308126646',
        'nombres'      => 'ZAMBRANO DELGADO HECTOR FERNANDO',
        'cargo'        => 'Jefe de Sistemas',
        'direccion'    => 'Dirección de Planificación Estratégica',
        'departamento' => 'Gestión de Tecnología de la Información',
        'proceso'      => 'Procesos Adjetivos',
        'contrato'     => 'Nombramiento Permanente',
        'sueldo'       => '$1,500.00',
        'inicio'       => '01/01/2009',
        'email'        => 'h.zambrano@apm.gob.ec',
        'telefono'     => '+593 99 888 7766',
        'estado'       => 'Activo',
        'initials'     => 'ZH',
        'color'        => '#0f4c81',
        'anios'        => 17,
        'historial'    => [
            ['periodo' => '2009 – 2024', 'direccion' => 'Dirección de Tecnología de la Información', 'cargo' => 'Jefe de Sistemas'],
            ['periodo' => '2025 – hoy',  'direccion' => 'Dirección de Planificación Estratégica / GTI', 'cargo' => 'Jefe de Sistemas'],
        ]
    ],
    '1311567890' => [
        'cedula'       => '1311567890',
        'nombres'      => 'PEREZ MORALES JUAN CARLOS',
        'cargo'        => 'Economista de Planta',
        'direccion'    => 'Dirección Administrativa Financiera',
        'departamento' => 'Contabilidad',
        'proceso'      => 'Procesos Sustantivos',
        'contrato'     => 'Contrato de Servicios',
        'sueldo'       => '$1,200.00',
        'inicio'       => '15/03/2019',
        'email'        => 'j.perez@apm.gob.ec',
        'telefono'     => '+593 98 765 4321',
        'estado'       => 'Activo',
        'initials'     => 'PJ',
        'color'        => '#0e7490',
        'anios'        => 7,
        'historial'    => [
            ['periodo' => '2019 – hoy', 'direccion' => 'Dirección Administrativa Financiera', 'cargo' => 'Economista de Planta'],
        ]
    ],
];

$emp = $perfilesMock[$cedula] ?? $perfilesMock['1308126646'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expediente · <?= htmlspecialchars($emp['nombres']) ?> | APM</title>
    <meta name="description" content="Expediente digital de <?= htmlspecialchars($emp['nombres']) ?> — Autoridad Portuaria de Manta.">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/bootstrap-icons.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/fonts.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/variables.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/layout.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/toast.css">
    <style>
        /* ── Expediente hero ──────────────────────────────────────────────── */
        .perfil-hero {
            background: linear-gradient(135deg, var(--navy-800, #1e3a5f) 0%, #0e4d72 60%, #0891b2 100%);
            border-radius: 20px;
            padding: 32px 28px;
            display: flex;
            gap: 24px;
            align-items: center;
            flex-wrap: wrap;
            margin-bottom: 24px;
            position: relative;
            overflow: hidden;
        }
        .perfil-hero::before {
            content: '';
            position: absolute;
            right: -60px; top: -60px;
            width: 260px; height: 260px;
            background: rgba(255,255,255,.05);
            border-radius: 50%;
        }
        .perfil-hero::after {
            content: '';
            position: absolute;
            right: 60px; bottom: -80px;
            width: 180px; height: 180px;
            background: rgba(255,255,255,.04);
            border-radius: 50%;
        }
        .perfil-avatar {
            width: 100px; height: 100px;
            border-radius: 24px;
            display: grid; place-items: center;
            font-size: 2.2rem; font-weight: 800; color: #fff;
            border: 3px solid rgba(255,255,255,.35);
            flex-shrink: 0;
            position: relative; z-index: 1;
        }
        .perfil-hero-data { flex: 1; min-width: 220px; position: relative; z-index: 1; }
        .perfil-hero-data h2 { color: #fff; font-size: 1.4rem; margin: 0 0 4px; }
        .perfil-hero-data p  { color: rgba(255,255,255,.75); font-size: .9rem; margin: 0 0 14px; }
        .perfil-chips { display: flex; gap: 8px; flex-wrap: wrap; }
        .perfil-chip {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 5px 12px; border-radius: 999px; font-size: .78rem; font-weight: 600;
            background: rgba(255,255,255,.14); color: rgba(255,255,255,.9);
            border: 1px solid rgba(255,255,255,.2);
        }
        .perfil-chip.activo { background: rgba(16,185,129,.25); border-color: rgba(16,185,129,.4); color: #a7f3d0; }
        /* Info cards */
        .info-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 16px; margin-bottom: 24px; }
        .info-card { background: #fff; border: 1px solid var(--line, #e2e8f0); border-radius: 14px; padding: 16px 18px; }
        .info-card-label { font-size: .72rem; font-weight: 700; text-transform: uppercase; letter-spacing: .12em; color: var(--ink-600, #64748b); margin-bottom: 6px; display: flex; align-items: center; gap: 6px; }
        .info-card-value { font-size: .95rem; font-weight: 600; color: var(--navy-900, #1e293b); }
        /* Historial */
        .historial-item { display: flex; gap: 16px; align-items: flex-start; margin-bottom: 16px; }
        .historial-dot { width: 14px; height: 14px; border-radius: 50%; background: var(--ocean-700, #0e7490); flex-shrink: 0; margin-top: 4px; box-shadow: 0 0 0 4px rgba(14,116,144,.15); }
        .historial-line { flex: 1; }
        .historial-line strong { display: block; font-size: .88rem; color: var(--navy-900, #1e293b); }
        .historial-line small { font-size: .78rem; color: var(--ink-600, #64748b); }
        .historial-period { font-size: .75rem; font-weight: 700; color: var(--ocean-700, #0e7490); background: #f0f9ff; padding: 2px 8px; border-radius: 6px; margin-top: 4px; display: inline-block; }
        /* Badge solo lectura */
        .readonly-badge {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 4px 10px; border-radius: 8px; font-size: .73rem; font-weight: 700;
            background: rgba(245,158,11,.1); color: #b45309; border: 1px solid rgba(245,158,11,.3);
            margin-bottom: 16px;
        }
    </style>
</head>
<body>
<div id="overlay" class="overlay" onclick="closeSidebar()"></div>
<button class="sidebar-open-btn" id="sidebarOpenBtn" onclick="openSidebar()"
        title="Abrir menú lateral" aria-label="Abrir menú lateral">
    <i class="bi bi-layout-sidebar"></i>
</button>

<div class="app">
    <?php require_once ROOT . '/shared/menu.php'; ?>

    <section class="content">
        <header class="topbar">
            <div class="topbar-left">
                <div class="brand">
                    <img src="<?= IMG_URL ?>/logoapm.png" alt="Logo APM">
                    <div>
                        <h1>Autoridad Portuaria de Manta</h1>
                        <p>Módulo Talento Humano</p>
                    </div>
                </div>
            </div>
            <div class="topbar-actions">
                <div class="icon-chip"><i class="bi bi-calendar-event"></i><span id="currentDate">--</span></div>
                <a href="<?= BASE_URL ?>/talento-humano/inicio" class="btn btn-ghost">
                    <i class="bi bi-arrow-left"></i> Volver al Inicio
                </a>
            </div>
        </header>

        <main class="main">
            <div class="content-shell">

                <!-- BADGE SOLO LECTURA -->
                <div class="readonly-badge">
                    <i class="bi bi-lock-fill"></i>
                    Expediente Digital — Modo solo lectura. Para editar use el Directorio de Personal.
                </div>

                <!-- HERO DEL PERFIL -->
                <div class="perfil-hero">
                    <div class="perfil-avatar" style="background:<?= htmlspecialchars($emp['color']) ?>;">
                        <?= htmlspecialchars($emp['initials']) ?>
                    </div>
                    <div class="perfil-hero-data">
                        <h2><i class="bi bi-person-badge"></i> <?= htmlspecialchars($emp['nombres']) ?></h2>
                        <p><?= htmlspecialchars($emp['cargo']) ?> · <?= htmlspecialchars($emp['departamento']) ?></p>
                        <div class="perfil-chips">
                            <span class="perfil-chip activo"><i class="bi bi-check-circle-fill"></i> <?= htmlspecialchars($emp['estado']) ?></span>
                            <span class="perfil-chip"><i class="bi bi-calendar3"></i> <?= $emp['anios'] ?> años en la institución</span>
                            <span class="perfil-chip"><i class="bi bi-file-earmark-person"></i> C.I. <?= htmlspecialchars($emp['cedula']) ?></span>
                        </div>
                    </div>
                    <div style="position:relative; z-index:1; display:flex; flex-direction:column; gap:8px; flex-shrink:0;">
                        <a href="<?= BASE_URL ?>/talento-humano/accion-personal?cedula=<?= urlencode($emp['cedula']) ?>"
                           class="btn" style="background:rgba(255,255,255,.15); color:#fff; border:1px solid rgba(255,255,255,.3); font-size:.83rem;">
                            <i class="bi bi-file-earmark-text"></i> Acción de Personal
                        </a>
                        <button class="btn" style="background:rgba(255,255,255,.1); color:#fff; border:1px solid rgba(255,255,255,.2); font-size:.83rem;"
                                onclick="showToast('Generando PDF del expediente... (TCPDF en producción)', 'info')">
                            <i class="bi bi-file-earmark-pdf"></i> Exportar PDF
                        </button>
                    </div>
                </div>

                <!-- DATOS GENERALES -->
                <section class="card" style="padding:20px; margin-bottom:20px;">
                    <h3 style="font-size:.9rem; font-weight:700; color:var(--navy-900); margin-bottom:16px; border-bottom:1px solid #e2e8f0; padding-bottom:10px;">
                        <i class="bi bi-person-lines-fill" style="color:var(--ocean-700);"></i> Datos Generales del Funcionario
                    </h3>
                    <div class="info-grid">
                        <div class="info-card">
                            <div class="info-card-label"><i class="bi bi-card-text"></i> Cédula de Identidad</div>
                            <div class="info-card-value"><?= htmlspecialchars($emp['cedula']) ?></div>
                        </div>
                        <div class="info-card">
                            <div class="info-card-label"><i class="bi bi-building"></i> Dirección Institucional</div>
                            <div class="info-card-value"><?= htmlspecialchars($emp['direccion']) ?></div>
                        </div>
                        <div class="info-card">
                            <div class="info-card-label"><i class="bi bi-diagram-3"></i> Proceso Institucional</div>
                            <div class="info-card-value"><?= htmlspecialchars($emp['proceso']) ?></div>
                        </div>
                        <div class="info-card">
                            <div class="info-card-label"><i class="bi bi-briefcase"></i> Tipo de Contrato</div>
                            <div class="info-card-value"><?= htmlspecialchars($emp['contrato']) ?></div>
                        </div>
                        <div class="info-card">
                            <div class="info-card-label"><i class="bi bi-currency-dollar"></i> RMU Mensual</div>
                            <div class="info-card-value" style="color:#059669; font-size:1.05rem;"><?= htmlspecialchars($emp['sueldo']) ?></div>
                        </div>
                        <div class="info-card">
                            <div class="info-card-label"><i class="bi bi-calendar-check"></i> Fecha de Ingreso</div>
                            <div class="info-card-value"><?= htmlspecialchars($emp['inicio']) ?></div>
                        </div>
                        <div class="info-card">
                            <div class="info-card-label"><i class="bi bi-envelope"></i> Correo Institucional</div>
                            <div class="info-card-value" style="font-size:.85rem;"><?= htmlspecialchars($emp['email']) ?></div>
                        </div>
                        <div class="info-card">
                            <div class="info-card-label"><i class="bi bi-telephone"></i> Teléfono</div>
                            <div class="info-card-value"><?= htmlspecialchars($emp['telefono']) ?></div>
                        </div>
                    </div>
                </section>

                <!-- HISTORIAL LABORAL -->
                <section class="card" style="padding:20px;">
                    <h3 style="font-size:.9rem; font-weight:700; color:var(--navy-900); margin-bottom:16px; border-bottom:1px solid #e2e8f0; padding-bottom:10px;">
                        <i class="bi bi-clock-history" style="color:var(--ocean-700);"></i> Historial Laboral en la APM
                    </h3>
                    <?php foreach ($emp['historial'] as $h): ?>
                    <div class="historial-item">
                        <div class="historial-dot"></div>
                        <div class="historial-line">
                            <strong><?= htmlspecialchars($h['cargo']) ?></strong>
                            <small><?= htmlspecialchars($h['direccion']) ?></small>
                            <span class="historial-period"><i class="bi bi-calendar-range"></i> <?= htmlspecialchars($h['periodo']) ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>

                    <div style="margin-top:16px; padding-top:14px; border-top:1px solid #f1f5f9; font-size:.8rem; color:var(--ink-600);">
                        <i class="bi bi-info-circle"></i>
                        El historial completo se cargará desde la vista <code>vw_th_reporte_historial_jerarquico</code> al conectar con la base de datos.
                    </div>
                </section>

            </div>
        </main>
    </section>
</div>

<div id="toastContainer" class="toast-container"></div>
<script src="<?= BASE_URL ?>/public/js/layout_sidebar.js"></script>
<script src="<?= BASE_URL ?>/public/js/toast.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const el = document.getElementById('currentDate');
    if (el) el.textContent = new Date().toLocaleDateString('es-EC', { weekday:'long', year:'numeric', month:'long', day:'numeric' });
});
</script>
</body>
</html>
