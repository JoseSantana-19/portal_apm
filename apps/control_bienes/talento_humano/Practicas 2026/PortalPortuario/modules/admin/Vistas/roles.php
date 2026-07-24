<?php /* roles.php – Vista: Roles y Permisos (RBAC) */ ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Roles y Permisos | Administración – APM</title>
    <meta name="description" content="Control de acceso basado en roles (RBAC) del Sistema de Talento Humano APM.">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/bootstrap-icons.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/fonts.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/variables.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/layout.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/toast.css">
    <style>
        .role-card { background:#fff; border:1px solid var(--line); border-radius:var(--radius-md); overflow:hidden; transition:transform .2s, box-shadow .2s; cursor:pointer; }
        .role-card:hover { transform:translateY(-3px); box-shadow:var(--shadow-md); }
        .role-card.active { box-shadow:0 0 0 2px var(--teal-500), var(--shadow-md); }
        .role-head { padding:16px 20px; display:flex; align-items:center; justify-content:space-between; gap:12px; }
        .role-head.danger { background:linear-gradient(135deg, rgba(239,68,68,.08), rgba(239,68,68,.04)); border-bottom:2px solid rgba(239,68,68,.2); }
        .role-head.primary { background:linear-gradient(135deg, rgba(99,102,241,.08), rgba(99,102,241,.04)); border-bottom:2px solid rgba(99,102,241,.2); }
        .role-head.info    { background:linear-gradient(135deg, rgba(16,180,199,.08), rgba(16,180,199,.04)); border-bottom:2px solid rgba(16,180,199,.2); }
        .role-head.success { background:linear-gradient(135deg, rgba(16,185,129,.08), rgba(16,185,129,.04)); border-bottom:2px solid rgba(16,185,129,.2); }
        .role-head.warning { background:linear-gradient(135deg, rgba(245,158,11,.08), rgba(245,158,11,.04)); border-bottom:2px solid rgba(245,158,11,.2); }
        .role-icon { width:46px; height:46px; border-radius:14px; display:grid; place-items:center; font-size:1.4rem; flex-shrink:0; }
        .role-icon.danger  { background:rgba(239,68,68,.12); color:#dc2626; }
        .role-icon.primary { background:rgba(99,102,241,.12); color:#4338ca; }
        .role-icon.info    { background:rgba(16,180,199,.12); color:var(--ocean-700); }
        .role-icon.success { background:rgba(16,185,129,.12); color:#059669; }
        .role-icon.warning { background:rgba(245,158,11,.12); color:#b45309; }
        .role-body { padding:16px 20px; }
        .roles-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(300px,1fr)); gap:16px; }
        .perm-tag { display:inline-flex; align-items:center; gap:4px; padding:2px 9px; border-radius:6px; font-size:.72rem; font-weight:600; background:#f0f4ff; color:#4338ca; border:1px solid #c7d2fe; margin:2px; }
        .perm-all { background:#fef3c7; color:#92400e; border-color:#fde68a; }
        .users-count { display:inline-flex; align-items:center; gap:5px; padding:4px 10px; border-radius:999px; font-size:.78rem; font-weight:700; background:rgba(16,180,199,.1); color:var(--ocean-700); border:1px solid rgba(16,180,199,.2); }
        .perms-matrix { overflow-x:auto; }
        .perms-matrix table { width:100%; border-collapse:collapse; font-size:.82rem; }
        .perms-matrix th { padding:12px 10px; text-align:left; background:#f8fbff; border-bottom:2px solid var(--line); color:var(--navy-900); font-weight:700; white-space:nowrap; }
        .perms-matrix td { padding:10px; border-bottom:1px solid #f1f5f9; vertical-align:middle; }
        .perms-matrix tr:hover td { background:#f9fafb; }
        .perm-check { text-align:center; }
        .check-yes { color:#10b981; font-size:1.1rem; }
        .check-no  { color:#d1d5db; font-size:1.1rem; }
        .tab-nav { display:flex; gap:4px; padding:4px; background:#f1f5f9; border-radius:12px; margin-bottom:20px; }
        .tab-btn { flex:1; padding:10px 16px; border:none; background:transparent; border-radius:10px; cursor:pointer; font-weight:600; font-size:.85rem; color:var(--ink-600); transition:all .2s; }
        .tab-btn.active { background:#fff; color:var(--navy-900); box-shadow:0 2px 8px rgba(0,0,0,.08); }
        .tab-content { display:none; }
        .tab-content.active { display:block; }
    </style>
</head>
<body>
<div id="overlay" class="overlay" onclick="closeSidebar()"></div>
<button class="sidebar-open-btn" id="sidebarOpenBtn" onclick="openSidebar()" title="Abrir menú lateral" aria-label="Abrir menú lateral">
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
                        <p>Administración y Seguridad</p>
                    </div>
                </div>
            </div>
            <div class="topbar-actions">
                <div class="icon-chip"><i class="bi bi-calendar-event"></i><span id="currentDate">--</span></div>
                <div class="user-pill"><span><?= htmlspecialchars($usuarioNombre ?? 'Administrador') ?></span><small>APM</small></div>
            </div>
        </header>

        <main class="main">
            <div class="content-shell">

                <!-- HERO -->
                <section class="hero" id="hero-roles">
                    <div>
                        <div class="hero-kicker">Administración · Seguridad RBAC</div>
                        <h2>Roles y Permisos</h2>
                        <p>Control de acceso granular basado en roles. Define exactamente qué acciones puede ejecutar cada tipo de usuario dentro del sistema.</p>
                        <div class="hero-actions">
                            <button class="btn btn-primary" id="btn-nuevo-rol" onclick="showToast('Creación de rol personalizado — próxima versión.', 'info')">
                                <i class="bi bi-plus-circle"></i> Nuevo Rol
                            </button>
                        </div>
                    </div>
                    <div class="metrics" style="grid-template-columns:repeat(2,1fr);">
                        <div class="metric-card" style="border-left:4px solid var(--ocean-700)">
                            <div class="metric-label"><i class="bi bi-shield"></i> Roles definidos</div>
                            <div class="metric-value"><?= count($roles) ?></div>
                            <div class="metric-foot">En el sistema</div>
                        </div>
                        <div class="metric-card" style="border-left:4px solid var(--teal-500)">
                            <div class="metric-label"><i class="bi bi-grid"></i> Módulos protegidos</div>
                            <div class="metric-value"><?= count($modulos) ?></div>
                            <div class="metric-foot">Con permisos asignados</div>
                        </div>
                    </div>
                </section>

                <!-- TABS -->
                <div class="card" style="padding:20px;">
                    <div class="tab-nav">
                        <button class="tab-btn active" onclick="showTabRol('tab-roles')" id="tabBtnRol-roles"><i class="bi bi-shield-shaded"></i> Roles</button>
                        <button class="tab-btn" onclick="showTabRol('tab-matrix')" id="tabBtnRol-matrix"><i class="bi bi-table"></i> Matriz de permisos</button>
                    </div>

                    <!-- TAB: Roles -->
                    <div class="tab-content active" id="tab-roles">
                        <div class="roles-grid">
                        <?php foreach ($roles as $rol): ?>
                            <div class="role-card" onclick="cargarMatrizPermisos('<?= addslashes($rol['nombre']) ?>', this)">
                                <div class="role-head <?= $rol['color'] ?>">
                                    <div style="display:flex; align-items:center; gap:12px;">
                                        <div class="role-icon <?= $rol['color'] ?>">
                                            <?php
                                            $icono = match($rol['nombre']) {
                                                'Super Administrador' => '<i class="bi bi-patch-check-fill"></i>',
                                                'Administrador TH'    => '<i class="bi bi-person-gear"></i>',
                                                'Analista RRHH'       => '<i class="bi bi-file-person"></i>',
                                                'Consultor'           => '<i class="bi bi-eye"></i>',
                                                'Supervisor'          => '<i class="bi bi-person-check"></i>',
                                                default               => '<i class="bi bi-shield"></i>',
                                            };
                                            echo $icono;
                                            ?>
                                        </div>
                                        <div>
                                            <div style="font-weight:700; font-size:.95rem; color:var(--navy-900);"><?= htmlspecialchars($rol['nombre']) ?></div>
                                            <span class="users-count"><i class="bi bi-people"></i><?= $rol['usuarios'] ?> usuario<?= $rol['usuarios'] !== 1 ? 's' : '' ?></span>
                                        </div>
                                    </div>
                                    <button class="btn btn-outline" style="padding:6px 10px; font-size:.78rem;" onclick="showToast('Editando rol: <?= addslashes($rol['nombre']) ?>', 'info')">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                </div>
                                <div class="role-body">
                                    <p style="font-size:.83rem; color:var(--ink-600); margin:0 0 12px;"><?= htmlspecialchars($rol['descripcion']) ?></p>
                                    <div style="display:flex; flex-wrap:wrap; gap:2px;">
                                    <?php foreach ($rol['permisos'] as $p): ?>
                                        <span class="perm-tag <?= $p === '*' ? 'perm-all' : '' ?>">
                                            <?php if ($p === '*'): ?>
                                            <i class="bi bi-infinity"></i> Acceso total
                                            <?php else: ?>
                                            <i class="bi bi-check2"></i> <?= htmlspecialchars($p) ?>
                                            <?php endif; ?>
                                        </span>
                                    <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- TAB: Matriz de permisos -->
                    <div class="tab-content" id="tab-matrix">
                        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:16px; flex-wrap:wrap; gap:10px;">
                            <h5 class="fw-bold" style="margin:0; color:var(--navy-900); font-size:.95rem;">
                                Matriz de Permisos —
                                <span style="color:var(--ocean-700);" id="nombreRolMatriz">Seleccione un rol</span>
                            </h5>
                            <span style="font-size:.78rem; color:var(--ink-600); background:#f1f5f9; padding:4px 10px; border-radius:6px;">
                                <i class="bi bi-info-circle"></i> Haga clic en una tarjeta de rol para ver su matriz
                            </span>
                        </div>
                        <div class="perms-matrix">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Módulo / Acción</th>
                                        <?php foreach ($roles as $rol): ?>
                                        <th style="text-align:center;"><?= htmlspecialchars($rol['nombre']) ?></th>
                                        <?php endforeach; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($modulos as $modulo => $acciones): ?>
                                    <tr>
                                        <td colspan="<?= count($roles) + 1 ?>" style="background:#f0f7ff; font-weight:700; font-size:.8rem; text-transform:uppercase; letter-spacing:.15em; color:var(--ocean-700); padding:10px;">
                                            <i class="bi bi-box-seam"></i> <?= ucfirst($modulo) ?>
                                        </td>
                                    </tr>
                                    <?php foreach ($acciones as $accion): ?>
                                    <tr>
                                        <td style="padding-left:24px; color:var(--ink-600); min-width:180px;">└ <?= ucfirst($accion) ?></td>
                                        <?php foreach ($roles as $rol): ?>
                                        <td class="perm-check">
                                            <?php
                                            $key = $modulo.'.'.$accion;
                                            $tieneAcceso = in_array('*', $rol['permisos']) ||
                                                           in_array($key, $rol['permisos']) ||
                                                           in_array($modulo.'.*', $rol['permisos']);
                                            if ($modulo === 'admin' && $rol['nombre'] !== 'Super Administrador') $tieneAcceso = false;
                                            if ($modulo === 'auditoria' && $rol['nombre'] === 'Consultor') $tieneAcceso = false;
                                            $permId = 'perm_' . preg_replace('/[^a-z0-9]/i', '_', strtolower($modulo)) . '_' . preg_replace('/[^a-z0-9]/i', '_', strtolower($accion));
                                            ?>
                                            <input class="form-check-input toggle-permiso" type="checkbox"
                                                   id="<?= $permId ?>"
                                                   style="cursor:pointer; width:1.1rem; height:1.1rem;"
                                                   <?= $tieneAcceso ? 'checked' : '' ?>
                                                   onchange="showToast('Permiso actualizado. En producción esto se guarda en BD.', 'info')">
                                        </td>
                                        <?php endforeach; ?>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

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
    // Activar la primera tarjeta de rol por defecto
    const primeraCard = document.querySelector('.role-card');
    if (primeraCard) primeraCard.click();
});
function showTabRol(tabId) {
    document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById(tabId)?.classList.add('active');
    document.getElementById('tabBtnRol-' + tabId.replace('tab-',''))?.classList.add('active');
}

/* ── RBAC Simulado — Matriz Interactiva ────────────────────────────── */
const mockRBAC = {
    'Super Administrador': {
        directorio:   { visualizar: true,  crear: true,  editar: true,  eliminar: true,  aprobar: true  },
        vacaciones:   { visualizar: true,  crear: true,  editar: true,  eliminar: true,  aprobar: true  },
        reportes:     { visualizar: true,  crear: true,  editar: true,  eliminar: true,  aprobar: true  },
        admin:        { visualizar: true,  crear: true,  editar: true,  eliminar: true,  aprobar: true  },
        auditoria:    { visualizar: true,  crear: false, editar: false, eliminar: false, aprobar: false }
    },
    'Administrador TH': {
        directorio:   { visualizar: true,  crear: true,  editar: true,  eliminar: false, aprobar: false },
        vacaciones:   { visualizar: true,  crear: true,  editar: true,  eliminar: false, aprobar: true  },
        reportes:     { visualizar: true,  crear: true,  editar: false, eliminar: false, aprobar: false },
        admin:        { visualizar: false, crear: false, editar: false, eliminar: false, aprobar: false },
        auditoria:    { visualizar: true,  crear: false, editar: false, eliminar: false, aprobar: false }
    },
    'Analista RRHH': {
        directorio:   { visualizar: true,  crear: true,  editar: true,  eliminar: false, aprobar: false },
        vacaciones:   { visualizar: true,  crear: true,  editar: false, eliminar: false, aprobar: false },
        reportes:     { visualizar: true,  crear: false, editar: false, eliminar: false, aprobar: false },
        admin:        { visualizar: false, crear: false, editar: false, eliminar: false, aprobar: false },
        auditoria:    { visualizar: false, crear: false, editar: false, eliminar: false, aprobar: false }
    },
    'Consultor': {
        directorio:   { visualizar: true,  crear: false, editar: false, eliminar: false, aprobar: false },
        vacaciones:   { visualizar: true,  crear: false, editar: false, eliminar: false, aprobar: false },
        reportes:     { visualizar: true,  crear: false, editar: false, eliminar: false, aprobar: false },
        admin:        { visualizar: false, crear: false, editar: false, eliminar: false, aprobar: false },
        auditoria:    { visualizar: false, crear: false, editar: false, eliminar: false, aprobar: false }
    },
    'Supervisor': {
        directorio:   { visualizar: true,  crear: false, editar: false, eliminar: false, aprobar: false },
        vacaciones:   { visualizar: true,  crear: false, editar: false, eliminar: false, aprobar: true  },
        reportes:     { visualizar: true,  crear: false, editar: false, eliminar: false, aprobar: false },
        admin:        { visualizar: false, crear: false, editar: false, eliminar: false, aprobar: false },
        auditoria:    { visualizar: false, crear: false, editar: false, eliminar: false, aprobar: false }
    }
};

// Mapa de módulos → acciones → IDs de los checkboxes
const moduloAccionMap = {
    directorio: ['visualizar', 'crear', 'editar', 'eliminar', 'aprobar'],
    vacaciones: ['visualizar', 'crear', 'editar', 'eliminar', 'aprobar'],
    reportes:   ['visualizar', 'crear', 'editar', 'eliminar', 'aprobar'],
    admin:      ['visualizar', 'crear', 'editar', 'eliminar', 'aprobar'],
    auditoria:  ['visualizar', 'crear', 'editar', 'eliminar', 'aprobar']
};

function cargarMatrizPermisos(nombreRol, cardEl) {
    // Marcar la tarjeta activa visualmente
    document.querySelectorAll('.role-card').forEach(c => c.classList.remove('active'));
    if (cardEl) cardEl.classList.add('active');

    // Actualizar título de la matriz
    const titulo = document.getElementById('nombreRolMatriz');
    if (titulo) titulo.textContent = nombreRol;

    const permisos = mockRBAC[nombreRol] || {};

    for (const [modulo, acciones] of Object.entries(moduloAccionMap)) {
        for (const accion of acciones) {
            const idBase = `perm_${modulo}_${accion}`;
            const toggle = document.getElementById(idBase);
            if (toggle) {
                toggle.checked = permisos[modulo] ? (permisos[modulo][accion] || false) : false;
            }
        }
    }
    console.log(`Matriz RBAC cargada para: ${nombreRol}`);
}
</script>
</body>
</html>
