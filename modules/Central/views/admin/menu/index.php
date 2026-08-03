<?php
/**
 * Estructura del Menú — árbol MOIS (Módulo · Opción · Ítem · Sub-ítem)
 * Compatible con los 3 temas (light / dark / corporate) vía tokens locales.
 */
$nivelUsuario = (int)($_SESSION['nivel_jerarquia'] ?? 0);
$puedeEliminar = $nivelUsuario >= 4;

/* ── Helpers ──────────────────────────────────────── */
function mois_actions(array $n, bool $puedeEliminar): string {
    $id      = (int)$n['id_nodo'];
    $checked = ((int)$n['estado']) ? ' checked' : '';
    $editUrl = APP_URL . "/admin/menu/{$id}/editar";
    $mfa     = ((int)($n['requiere_mfa'] ?? 0))
        ? '<span class="mfa-dot" title="Requiere MFA"><i class="fa-solid fa-shield-halved"></i></span>'
        : '';
    $html  = $mfa;
    $html .= '<label class="sw" title="Activar / Desactivar" data-node-id="' . $id . '" '
           . 'onclick="event.preventDefault();event.stopPropagation();stageToggle(' . $id . ', this)">'
           . '<input type="checkbox"' . $checked . ' tabindex="-1">'
           . '<span class="sw-track"></span><span class="sw-thumb"></span></label>';
    $html .= '<a href="' . $editUrl . '" class="row-btn" data-spa title="Editar nodo" '
           . 'onclick="event.stopPropagation()"><i class="fa-solid fa-pen"></i></a>';
    if ($puedeEliminar) {
        $html .= '<button type="button" class="row-btn row-btn-danger" title="Eliminar nodo" '
               . 'onclick="event.preventDefault();event.stopPropagation();delNode(' . $id . ')">'
               . '<i class="fa-solid fa-trash-can"></i></button>';
    }
    return $html;
}

function mois_row(array $n, int $lvl, bool $puedeEliminar, string $addUrl = '', string $addTitle = ''): string {
    $desc  = htmlspecialchars($n['descripcion'] ?? '', ENT_QUOTES, 'UTF-8');
    $icon  = htmlspecialchars($n['icono'] ?: 'fa-circle', ENT_QUOTES, 'UTF-8');
    $url   = trim((string)($n['url_ruta'] ?? ''));
    $coord = "{$n['id_modulo']}·{$n['opcion']}·{$n['items']}·{$n['subitems']}";
    $off   = ((int)$n['estado']) ? '' : ' row-off';

    $html  = '<span class="lvl-chip lc-' . $lvl . '">L' . $lvl . '</span>';
    $html .= '<i class="fa-solid ' . $icon . ' row-ico ric-' . $lvl . '"></i>';
    $html .= '<span class="row-desc' . $off . '">' . $desc . '</span>';
    $html .= '<code class="row-coord">' . $coord . '</code>';
    if ($url !== '') {
        $html .= '<span class="url-chip" title="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '">'
               . '<i class="fa-solid fa-link"></i>' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '</span>';
    }
    $html .= '<span class="row-actions">' . mois_actions($n, $puedeEliminar);
    if ($addUrl !== '') {
        $html .= '<a href="' . $addUrl . '" class="row-btn row-btn-add" data-spa title="' . $addTitle . '" '
               . 'onclick="event.stopPropagation()"><i class="fa-solid fa-plus"></i></a>';
    }
    $html .= '</span>';
    return $html;
}
?>
<style>
/* ════════════════════════════════════════════════════
   MOIS — tokens locales por tema
   ════════════════════════════════════════════════════ */
.mois {
    /* Tokens derivados de las variables del tema activo (t1/t2/t3) — adaptan solos */
    --g-bg:      var(--surface-app);
    --g-bg-soft: var(--accent-app);
    --g-bd:      var(--border-app);
    --g-blur:    0px;
    --g-shadow:  var(--shadow-app);
    --g-inset:   none;
    --rail:      var(--border-app);
    --row-hover: color-mix(in srgb, var(--primary-hover) 8%, transparent);
    /* niveles — colores vivos legibles sobre fondo claro u oscuro */
    --l1: #8b5cf6;  --l2: #3b82f6;  --l3: #22c55e;  --l4: #f59e0b;
    /* mesh decorativo (desactivado por defecto, glass solo en Porto) */
    --mesh-a: transparent;
    --mesh-b: transparent;
    --mesh-c: transparent;
}
/* Tema 3 (Porto / glassmorphism): activar desenfoque y un leve mesh */
body.t3 .mois {
    --g-blur: 16px;
    --mesh-a: rgba(8, 145, 178, .08);
    --mesh-b: rgba(16, 185, 129, .06);
}

/* ── Lienzo ── */
.mois {
    margin: calc(-1 * var(--sp-5));
    padding: var(--sp-5) var(--sp-6) var(--sp-10);
    min-height: calc(100vh - var(--topbar-height));
    background:
        radial-gradient(ellipse 60% 40% at 12% 0%,   var(--mesh-a) 0%, transparent 60%),
        radial-gradient(ellipse 50% 45% at 92% 88%,  var(--mesh-b) 0%, transparent 60%),
        radial-gradient(ellipse 45% 35% at 60% 30%,  var(--mesh-c) 0%, transparent 55%),
        var(--color-bg);
}

/* ── Glass primitives ── */
.mois .gx {
    background: var(--g-bg);
    backdrop-filter: blur(var(--g-blur)) saturate(1.6);
    -webkit-backdrop-filter: blur(var(--g-blur)) saturate(1.6);
    border: 1px solid var(--g-bd);
    box-shadow: var(--g-shadow), var(--g-inset);
    border-radius: var(--radius-lg);
}

/* ── Cabecera ── */
.mois-head {
    display: flex; align-items: flex-end; justify-content: space-between;
    gap: var(--sp-4); flex-wrap: wrap; margin-bottom: var(--sp-5);
}
.mois-eyebrow {
    font-size: var(--font-size-xs); font-weight: var(--font-weight-bold);
    letter-spacing: .14em; text-transform: uppercase;
    color: var(--color-primary); margin-bottom: var(--sp-1);
    display: flex; align-items: center; gap: var(--sp-2);
}
.mois-eyebrow::before {
    content: ''; width: 22px; height: 2px;
    background: var(--color-primary); border-radius: 2px;
}
.mois-title {
    font-size: var(--font-size-2xl); font-weight: var(--font-weight-bold);
    letter-spacing: -.02em; color: var(--color-text); margin: 0;
}
.mois-sub {
    margin: var(--sp-1) 0 0; color: var(--color-text-muted);
    font-size: var(--font-size-sm);
}
.mois-sub code {
    font-family: var(--font-mono); font-size: var(--font-size-xs);
    background: var(--color-surface-3); padding: 1px 6px;
    border-radius: var(--radius-sm); color: var(--color-text);
}

/* ── Strip de stats ── */
.stat-strip {
    display: flex; align-items: stretch;
    padding: 0; margin-bottom: var(--sp-4); overflow: hidden;
}
.stat-cell {
    flex: 1; display: flex; align-items: center; gap: var(--sp-3);
    padding: var(--sp-3) var(--sp-4); min-width: 130px;
}
.stat-cell + .stat-cell { border-left: 1px solid var(--g-bd); }
.stat-ico {
    width: 34px; height: 34px; border-radius: var(--radius-md); flex-shrink: 0;
    display: flex; align-items: center; justify-content: center; font-size: .85rem;
}
.stat-num {
    font-size: var(--font-size-xl); font-weight: var(--font-weight-bold);
    line-height: 1.1; color: var(--color-text); font-variant-numeric: tabular-nums;
}
.stat-lbl { font-size: var(--font-size-xs); color: var(--color-text-muted); }

/* ── Cabecera superior (fluye con el scroll — ya NO es sticky para no
      tapar el árbol al desplazar) ── */
.mois-sticky-top {
    position: static;
    margin: 0;
    padding: 0;
}

/* ── Toolbar ── */
.mois-toolbar {
    display: flex; align-items: center; gap: var(--sp-3); flex-wrap: wrap;
    padding: var(--sp-3) var(--sp-4); margin-bottom: var(--sp-4);
}
.mois-search { position: relative; flex: 1; min-width: 220px; max-width: 360px; }
.mois-search i {
    position: absolute; left: var(--sp-3); top: 50%; transform: translateY(-50%);
    color: var(--color-text-muted); font-size: var(--font-size-xs); pointer-events: none;
}
.mois-search input {
    width: 100%; padding: var(--sp-2) var(--sp-3) var(--sp-2) 2rem;
    font-size: var(--font-size-sm); color: var(--color-text);
    background: var(--g-bg-soft); border: 1px solid var(--g-bd);
    border-radius: var(--radius-md); outline: none;
    transition: border-color var(--transition-fast), box-shadow var(--transition-fast);
}
.mois-search input:focus {
    border-color: var(--color-primary);
    box-shadow: 0 0 0 3px color-mix(in srgb, var(--color-primary) 18%, transparent);
}
.mois-search input::placeholder { color: var(--color-text-light); }

/* ── Filtros de nivel ── */
.lvl-filters { display: flex; gap: var(--sp-1); }
.lvl-f {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 4px 11px; border-radius: var(--radius-full);
    border: 1px solid var(--g-bd); background: transparent;
    color: var(--color-text-muted); cursor: pointer;
    font-size: var(--font-size-xs); font-weight: var(--font-weight-semibold);
    font-family: var(--font-family); transition: all var(--transition-fast);
}
.lvl-f .dot { width: 7px; height: 7px; border-radius: 50%; }
.lvl-f[data-lvl="1"] .dot { background: var(--l1); }
.lvl-f[data-lvl="2"] .dot { background: var(--l2); }
.lvl-f[data-lvl="3"] .dot { background: var(--l3); }
.lvl-f[data-lvl="4"] .dot { background: var(--l4); }
.lvl-f:hover { border-color: var(--color-text-muted); color: var(--color-text); }
.lvl-f.on[data-lvl="1"] { border-color: var(--l1); color: var(--l1); background: color-mix(in srgb, var(--l1) 10%, transparent); }
.lvl-f.on[data-lvl="2"] { border-color: var(--l2); color: var(--l2); background: color-mix(in srgb, var(--l2) 10%, transparent); }
.lvl-f.on[data-lvl="3"] { border-color: var(--l3); color: var(--l3); background: color-mix(in srgb, var(--l3) 10%, transparent); }
.lvl-f.on[data-lvl="4"] { border-color: var(--l4); color: var(--l4); background: color-mix(in srgb, var(--l4) 10%, transparent); }

.tb-btn {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 5px 12px; border-radius: var(--radius-md);
    border: 1px solid var(--g-bd); background: transparent;
    color: var(--color-text-muted); cursor: pointer;
    font-size: var(--font-size-xs); font-weight: var(--font-weight-medium);
    font-family: var(--font-family); transition: all var(--transition-fast);
}
.tb-btn:hover { color: var(--color-text); border-color: var(--color-text-muted); background: var(--row-hover); }

/* ── Módulo (card raíz) ── */
.mod-card { margin-bottom: var(--sp-3); animation: mois-in .35s ease both; }
.mod-card:nth-child(1)  { animation-delay: .02s; }
.mod-card:nth-child(2)  { animation-delay: .05s; }
.mod-card:nth-child(3)  { animation-delay: .08s; }
.mod-card:nth-child(4)  { animation-delay: .11s; }
.mod-card:nth-child(5)  { animation-delay: .14s; }
.mod-card:nth-child(6)  { animation-delay: .17s; }
.mod-card:nth-child(n+7){ animation-delay: .2s; }
@keyframes mois-in {
    from { opacity: 0; transform: translateY(8px); }
    to   { opacity: 1; transform: none; }
}

.mod-summary {
    display: flex; align-items: center; gap: var(--sp-3);
    padding: var(--sp-3) var(--sp-4); cursor: pointer; user-select: none;
    list-style: none; outline: none;
}
.mod-summary::-webkit-details-marker { display: none; }
.mod-summary:focus-visible { box-shadow: 0 0 0 3px color-mix(in srgb, var(--color-primary) 25%, transparent); border-radius: var(--radius-lg); }
.mod-ico {
    width: 38px; height: 38px; border-radius: var(--radius-md); flex-shrink: 0;
    display: flex; align-items: center; justify-content: center; font-size: .9rem;
    color: #fff;
}
.mod-name {
    font-weight: var(--font-weight-semibold); font-size: var(--font-size-md);
    color: var(--color-text); letter-spacing: -.01em;
}
.mod-meta {
    font-size: var(--font-size-xs); color: var(--color-text-muted);
    display: flex; align-items: center; gap: var(--sp-2); margin-top: 1px;
}
.mod-count {
    font-size: var(--font-size-xs); font-weight: var(--font-weight-semibold);
    color: var(--color-text-muted); background: var(--g-bg-soft);
    border: 1px solid var(--g-bd); padding: 2px 9px;
    border-radius: var(--radius-full); white-space: nowrap;
    font-variant-numeric: tabular-nums;
}
.ch {
    font-size: .6rem; color: var(--color-text-light);
    transition: transform var(--transition); flex-shrink: 0;
}
details[open] > summary .ch { transform: rotate(90deg); }
details > summary { list-style: none; }
details > summary::-webkit-details-marker { display: none; }

/* ── Cuerpo del módulo: árbol con rieles ── */
.mod-body { padding: 0 var(--sp-4) var(--sp-3); }
.tree { position: relative; margin-left: 19px; padding-left: var(--sp-4); border-left: 1px solid var(--rail); }
.tree-leaf { position: relative; }
.tree-leaf::before {
    content: ''; position: absolute; left: calc(-1 * var(--sp-4)); top: 19px;
    width: calc(var(--sp-4) - 4px); height: 1px; background: var(--rail);
}

/* ── Filas de nodo ── */
.node {
    display: flex; align-items: center; gap: var(--sp-2);
    padding: var(--sp-2) var(--sp-3); min-height: 38px;
    border-radius: var(--radius-md);
    transition: background var(--transition-fast);
}
.node:hover { background: var(--row-hover); }
.node-sum { cursor: pointer; user-select: none; outline: none; }
.node-sum:focus-visible { box-shadow: 0 0 0 2px color-mix(in srgb, var(--color-primary) 30%, transparent); }

.lvl-chip {
    display: inline-flex; align-items: center; justify-content: center;
    min-width: 26px; height: 18px; padding: 0 5px;
    border-radius: var(--radius-sm); flex-shrink: 0;
    font-size: 10px; font-weight: var(--font-weight-bold);
    font-family: var(--font-mono); letter-spacing: .03em;
}
.lc-1 { background: color-mix(in srgb, var(--l1) 14%, transparent); color: var(--l1); }
.lc-2 { background: color-mix(in srgb, var(--l2) 14%, transparent); color: var(--l2); }
.lc-3 { background: color-mix(in srgb, var(--l3) 14%, transparent); color: var(--l3); }
.lc-4 { background: color-mix(in srgb, var(--l4) 16%, transparent); color: var(--l4); }

.row-ico { font-size: .75rem; flex-shrink: 0; width: 16px; text-align: center; }
.ric-1 { color: var(--l1); } .ric-2 { color: var(--l2); }
.ric-3 { color: var(--l3); } .ric-4 { color: var(--l4); }

.row-desc {
    flex: 1; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
    font-size: var(--font-size-sm); color: var(--color-text);
}
.row-desc.row-off { opacity: .45; text-decoration: line-through; text-decoration-color: var(--color-text-light); }

.row-coord {
    font-family: var(--font-mono); font-size: 10.5px;
    color: var(--color-text-light); flex-shrink: 0;
    font-variant-numeric: tabular-nums; letter-spacing: .02em;
    background: none; padding: 0;
}
.url-chip {
    display: inline-flex; align-items: center; gap: 4px;
    font-family: var(--font-mono); font-size: 10.5px;
    color: var(--color-text-muted); background: var(--g-bg-soft);
    border: 1px solid var(--g-bd); padding: 1px 7px;
    border-radius: var(--radius-full); flex-shrink: 1;
    max-width: 190px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
}
.url-chip i { font-size: 8px; opacity: .7; }
.mfa-dot { color: var(--color-warning); font-size: .65rem; flex-shrink: 0; }

/* ── Acciones de fila ── */
.row-actions {
    display: inline-flex; align-items: center; gap: var(--sp-1);
    flex-shrink: 0; opacity: .35; transition: opacity var(--transition-fast);
}
.node:hover .row-actions, .node:focus-within .row-actions { opacity: 1; }
.row-btn {
    display: inline-flex; align-items: center; justify-content: center;
    width: 26px; height: 26px; border-radius: var(--radius-sm);
    border: none; background: transparent; cursor: pointer;
    color: var(--color-text-muted); font-size: .68rem;
    transition: all var(--transition-fast); text-decoration: none;
}
.row-btn:hover { background: color-mix(in srgb, var(--color-primary) 12%, transparent); color: var(--color-primary); }
.row-btn-add:hover { background: color-mix(in srgb, var(--color-success) 12%, transparent); color: var(--color-success); }
.row-btn-danger:hover { background: color-mix(in srgb, var(--color-danger) 12%, transparent); color: var(--color-danger); }

/* ── Switch ── */
.sw { position: relative; display: inline-block; width: 30px; height: 17px; flex-shrink: 0; cursor: pointer; }
.sw input { opacity: 0; width: 0; height: 0; position: absolute; }
.sw-track {
    position: absolute; inset: 0;
    background: var(--color-surface-3); border: 1px solid var(--g-bd);
    border-radius: var(--radius-full); transition: background var(--transition-fast);
}
.sw input:checked ~ .sw-track { background: var(--color-success); border-color: var(--color-success); }
.sw-thumb {
    position: absolute; top: 3px; left: 3px; width: 11px; height: 11px;
    background: #fff; border-radius: 50%; box-shadow: 0 1px 2px rgba(0,0,0,.25);
    pointer-events: none; transition: transform var(--transition-fast);
}
.sw input:checked ~ .sw-thumb { transform: translateX(13px); }

/* ── Panel de ayuda ── */
.help-grid {
    display: grid; grid-template-columns: repeat(4, 1fr);
    gap: var(--sp-4); padding: var(--sp-4) 0;
}
.help-t {
    display: flex; align-items: center; gap: var(--sp-2);
    font-size: var(--font-size-sm); font-weight: var(--font-weight-semibold);
    color: var(--color-text); margin-bottom: var(--sp-1);
}
.help-p {
    font-size: var(--font-size-xs); color: var(--color-text-muted);
    line-height: 1.5; margin: 0;
}
.help-steps {
    border-top: 1px dashed var(--g-bd); padding-top: var(--sp-3);
    display: flex; flex-direction: column; gap: var(--sp-2);
}
.help-step {
    display: flex; align-items: center; gap: var(--sp-2);
    font-size: var(--font-size-xs); color: var(--color-text-muted);
}
.help-step strong { color: var(--color-text); }
.hs-n {
    display: inline-flex; align-items: center; justify-content: center;
    width: 18px; height: 18px; border-radius: 50%; flex-shrink: 0;
    background: color-mix(in srgb, var(--color-primary) 13%, transparent);
    color: var(--color-primary);
    font-size: 10px; font-weight: var(--font-weight-bold);
}

/* ── Barra flotante de cambios pendientes ── */
.save-bar {
    position: fixed; left: 50%; bottom: var(--sp-5); transform: translate(-50%, 24px);
    display: flex; align-items: center; gap: var(--sp-3);
    padding: var(--sp-3) var(--sp-4); border-radius: var(--radius-lg);
    background: var(--g-bg); backdrop-filter: blur(16px) saturate(1.6);
    -webkit-backdrop-filter: blur(16px) saturate(1.6);
    border: 1px solid var(--g-bd); box-shadow: 0 12px 40px rgba(0,0,0,.28), var(--g-shadow);
    z-index: 500; opacity: 0; pointer-events: none;
    transition: opacity .2s ease, transform .2s ease;
}
.save-bar.show { opacity: 1; pointer-events: auto; transform: translate(-50%, 0); }
.save-bar-count {
    display: flex; align-items: center; gap: 8px;
    font-size: var(--font-size-sm); font-weight: var(--font-weight-semibold); color: var(--color-text);
}
.save-bar-count .n {
    display: inline-flex; align-items: center; justify-content: center;
    min-width: 22px; height: 22px; padding: 0 6px; border-radius: var(--radius-full);
    background: color-mix(in srgb, var(--color-primary) 16%, transparent);
    color: var(--color-primary); font-size: 12px; font-weight: var(--font-weight-bold);
}
.save-bar .btn-sm { padding: 6px 14px; }
.node-pending { position: relative; }
.node-pending::after {
    content: ''; position: absolute; left: -10px; top: 50%; transform: translateY(-50%);
    width: 5px; height: 5px; border-radius: 50%; background: var(--color-warning);
}

/* ── Responsive ── */
@media (max-width: 720px) {
    .help-grid { grid-template-columns: 1fr 1fr; }
    .url-chip, .row-coord { display: none; }
    .stat-strip { flex-wrap: wrap; }
    .stat-cell { min-width: 45%; }
    .stat-cell + .stat-cell { border-left: none; }
}
</style>

<div class="mois">

<?php if (!empty($success)): ?>
<script>document.addEventListener('DOMContentLoaded', () => PortalAlert.success(<?= json_encode($success) ?>));</script>
<?php endif; ?>
<?php if (!empty($error)): ?>
<script>document.addEventListener('DOMContentLoaded', () => PortalAlert.error(<?= json_encode($error) ?>));</script>
<?php endif; ?>

<!-- ── Sticky top: cabecera + stats + toolbar + ayuda ── -->
<div class="mois-sticky-top">

<!-- ── Cabecera ── -->
<div class="mois-head" style="padding-top:var(--sp-5);">
    <div>
        <div class="mois-eyebrow">Administración · Navegación</div>
        <h2 class="mois-title">Estructura del Menú</h2>
        <p class="mois-sub">
            Jerarquía <code>MOIS</code> — Módulo · Opción · Ítem · Sub-ítem.
            Los cambios se reflejan en el menú lateral de cada usuario según sus permisos.
        </p>
    </div>
    <a href="<?= APP_URL ?>/admin/menu/nuevo" class="btn btn-primary" data-spa>
        <i class="fa-solid fa-plus"></i> Nuevo Nodo
    </a>
</div>

<!-- ── Stats ── -->
<div class="gx stat-strip">
    <?php
    $statDefs = [
        ['fa-diagram-project', $total,            'Nodos totales', 'var(--color-primary)'],
        ['fa-circle-check',    $activos,          'Activos',       'var(--color-success)'],
        ['fa-circle-minus',    $total - $activos, 'Inactivos',     'var(--color-danger)'],
        ['fa-layer-group',     count($tree),      'Módulos',       'var(--l1)'],
    ];
    foreach ($statDefs as [$ic, $val, $lbl, $c]): ?>
    <div class="stat-cell">
        <div class="stat-ico" style="background:color-mix(in srgb,<?= $c ?> 13%,transparent);color:<?= $c ?>;">
            <i class="fa-solid <?= $ic ?>"></i>
        </div>
        <div>
            <div class="stat-num"><?= (int)$val ?></div>
            <div class="stat-lbl"><?= $lbl ?></div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- ── Toolbar ── -->
<div class="gx mois-toolbar">
    <div class="mois-search">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input type="text" id="mois-q" placeholder="Buscar por descripción o ruta…" oninput="moisSearch(this.value)" autocomplete="off">
    </div>
    <div class="lvl-filters" role="group" aria-label="Filtrar por nivel">
        <button type="button" class="lvl-f" data-lvl="1" onclick="moisFilter(1,this)"><span class="dot"></span>Módulo</button>
        <button type="button" class="lvl-f" data-lvl="2" onclick="moisFilter(2,this)"><span class="dot"></span>Opción</button>
        <button type="button" class="lvl-f" data-lvl="3" onclick="moisFilter(3,this)"><span class="dot"></span>Ítem</button>
        <button type="button" class="lvl-f" data-lvl="4" onclick="moisFilter(4,this)"><span class="dot"></span>Sub-ítem</button>
    </div>
    <div style="margin-left:auto;display:flex;gap:var(--sp-1);">
        <button type="button" class="tb-btn" onclick="moisExpand(true)"><i class="fa-solid fa-angles-down"></i> Expandir</button>
        <button type="button" class="tb-btn" onclick="moisExpand(false)"><i class="fa-solid fa-angles-up"></i> Colapsar</button>
    </div>
</div>

<!-- ── Ayuda: cómo funciona la estructura ── -->
<details class="gx help-card" style="margin-bottom:var(--sp-4);">
    <summary class="node node-sum" style="padding:var(--sp-3) var(--sp-4);display:flex;align-items:center;gap:var(--sp-2);">
        <i class="fa-solid fa-chevron-right ch"></i>
        <i class="fa-solid fa-circle-question" style="color:var(--color-primary);font-size:.85rem;"></i>
        <span style="font-weight:var(--font-weight-semibold);font-size:var(--font-size-sm);color:var(--color-text);">
            ¿Cómo funciona la estructura del menú?
        </span>
        <span style="margin-left:auto;font-size:var(--font-size-xs);color:var(--color-text-muted);">guía rápida</span>
    </summary>
    <div style="padding:0 var(--sp-4) var(--sp-4);border-top:1px solid var(--g-bd);">
        <div class="help-grid">
            <div>
                <div class="help-t"><span class="lvl-chip lc-1">L1</span> Módulo</div>
                <p class="help-p">La dirección o gerencia (ej. "Dirección Financiera"). Es la cabecera grande del menú lateral. Ya existen 11.</p>
            </div>
            <div>
                <div class="help-t"><span class="lvl-chip lc-2">L2</span> Opción</div>
                <p class="help-p">Una carpeta dentro del módulo que agrupa pantallas (ej. "Presupuesto"). No lleva URL.</p>
            </div>
            <div>
                <div class="help-t"><span class="lvl-chip lc-3">L3</span> Ítem</div>
                <p class="help-p">Una página real del sistema, con URL (ej. "Consultar partidas"). Es lo que más se crea.</p>
            </div>
            <div>
                <div class="help-t"><span class="lvl-chip lc-4">L4</span> Sub-ítem</div>
                <p class="help-p">Un atajo dentro de un ítem (ej. "Nueva partida", "Reporte"). También lleva URL.</p>
            </div>
        </div>
        <div class="help-steps">
            <div class="help-step"><span class="hs-n">1</span> Pulsa el botón <i class="fa-solid fa-plus" style="color:var(--color-success);"></i> del elemento <strong>padre</strong> donde quieres agregar (aparece al pasar el cursor).</div>
            <div class="help-step"><span class="hs-n">2</span> El formulario llega con la ubicación ya cargada — solo completa nombre, URL e ícono.</div>
            <div class="help-step"><span class="hs-n">3</span> Usa el interruptor <span class="sw" style="vertical-align:middle;transform:scale(.8);"><input type="checkbox" checked disabled><span class="sw-track"></span><span class="sw-thumb"></span></span> para ocultar/mostrar sin borrar, y <i class="fa-solid fa-pen" style="color:var(--color-text-muted);font-size:.7rem;"></i> para editar.</div>
            <div class="help-step"><span class="hs-n"><i class="fa-solid fa-arrows-up-down" style="font-size:.6rem;"></i></span> <strong>Jerarquía automática:</strong> al <strong>activar</strong> un Sub-ítem/Ítem/Opción se activan también sus niveles superiores (no puede quedar visible bajo un padre oculto); al <strong>desactivar</strong> un nivel se ocultan todos los inferiores.</div>
            <div class="help-step"><span class="hs-n">!</span> Lo que cada usuario ve depende de los <a href="<?= APP_URL ?>/admin/roles" data-spa style="color:var(--color-primary);">permisos de su rol</a> — crear el nodo no basta: hay que dar permiso.</div>
        </div>
    </div>
</details>

</div><!-- /.mois-sticky-top -->

<!-- ── Árbol ── -->
<div id="mois-tree">
<?php foreach ($tree as $modId => $mod):
    $meta     = $mod['meta'];
    $modRaiz  = $mod['raiz'];
    $modColor = $meta['color'];

    $nCount = ($modRaiz ? 1 : 0);
    foreach ($mod['areas'] as $a) {
        $nCount += ($a['raiz'] ? 1 : 0);
        foreach ($a['items'] ?? [] as $i) {
            $nCount += ($i['raiz'] ? 1 : 0) + count($i['children'] ?? []);
        }
    }
?>
<details class="gx mod-card" data-mod="<?= $modId ?>" open>
    <summary class="mod-summary">
        <i class="fa-solid fa-chevron-right ch"></i>
        <div class="mod-ico" style="background:<?= $modColor ?>;box-shadow:0 2px 10px color-mix(in srgb,<?= $modColor ?> 40%,transparent);">
            <i class="fa-solid <?= $meta['icon'] ?>"></i>
        </div>
        <div style="flex:1;min-width:0;">
            <div class="mod-name"><?= htmlspecialchars($meta['label'], ENT_QUOTES, 'UTF-8') ?></div>
            <div class="mod-meta">
                <code class="row-coord">mod <?= $modId ?></code>
                <?php if ($modRaiz): ?>
                · <?= htmlspecialchars($modRaiz['descripcion'], ENT_QUOTES, 'UTF-8') ?>
                <?php endif; ?>
            </div>
        </div>
        <span class="mod-count"><?= $nCount ?> nodos</span>
        <span class="row-actions" style="opacity:1;" onclick="event.stopPropagation();event.preventDefault();">
            <?php if ($modRaiz) echo mois_actions($modRaiz, $puedeEliminar); ?>
            <a href="<?= APP_URL ?>/admin/menu/nuevo?mod=<?= $modId ?>" class="row-btn row-btn-add" data-spa title="Agregar opción al módulo">
                <i class="fa-solid fa-plus"></i>
            </a>
        </span>
    </summary>

    <div class="mod-body">
        <div class="tree">
        <?php foreach ($mod['areas'] as $opId => $area):
            $aRaiz = $area['raiz'];
            $items = $area['items'] ?? [];
            $addOpUrl = APP_URL . "/admin/menu/nuevo?mod={$modId}&op={$opId}";
        ?>
            <div class="tree-leaf area-blk" data-lvl="2">
            <?php if (!empty($items)): ?>
                <details open>
                    <summary class="node node-sum">
                        <i class="fa-solid fa-chevron-right ch"></i>
                        <?php if ($aRaiz): ?>
                            <?= mois_row($aRaiz, 2, $puedeEliminar, $addOpUrl, 'Agregar pantalla') ?>
                        <?php else: ?>
                            <span class="lvl-chip lc-2">L2</span>
                            <span class="row-desc" style="color:var(--color-text-muted);font-style:italic;">Opción <?= $opId ?> (sin nodo raíz)</span>
                            <span class="row-actions"><a href="<?= $addOpUrl ?>" class="row-btn row-btn-add" data-spa title="Agregar pantalla" onclick="event.stopPropagation()"><i class="fa-solid fa-plus"></i></a></span>
                        <?php endif; ?>
                    </summary>
                    <div class="tree">
                    <?php foreach ($items as $itId => $item):
                        $iRaiz    = $item['raiz'];
                        $children = $item['children'] ?? [];
                        $addItUrl = APP_URL . "/admin/menu/nuevo?mod={$modId}&op={$opId}&it={$itId}";
                    ?>
                        <div class="tree-leaf item-blk" data-lvl="3">
                        <?php if (!empty($children)): ?>
                            <details open>
                                <summary class="node node-sum">
                                    <i class="fa-solid fa-chevron-right ch"></i>
                                    <?php if ($iRaiz): ?>
                                        <?= mois_row($iRaiz, 3, $puedeEliminar, $addItUrl, 'Agregar sub-acción') ?>
                                    <?php else: ?>
                                        <span class="lvl-chip lc-3">L3</span>
                                        <span class="row-desc" style="color:var(--color-text-muted);font-style:italic;">Ítem <?= $itId ?> (sin nodo raíz)</span>
                                    <?php endif; ?>
                                </summary>
                                <div class="tree">
                                <?php foreach ($children as $sub): ?>
                                    <div class="tree-leaf sub-blk" data-lvl="4">
                                        <div class="node"><?= mois_row($sub, 4, $puedeEliminar) ?></div>
                                    </div>
                                <?php endforeach; ?>
                                </div>
                            </details>
                        <?php elseif ($iRaiz): ?>
                            <div class="node"><?= mois_row($iRaiz, 3, $puedeEliminar, $addItUrl, 'Agregar sub-acción') ?></div>
                        <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                    </div>
                </details>
            <?php elseif ($aRaiz): ?>
                <div class="node"><?= mois_row($aRaiz, 2, $puedeEliminar, $addOpUrl, 'Agregar pantalla') ?></div>
            <?php endif; ?>
            </div>
        <?php endforeach; ?>
        </div>
    </div>
</details>
<?php endforeach; ?>
</div>

<!-- barra flotante: cambios de estado sin guardar -->
<div class="gx save-bar" id="save-bar">
    <span class="save-bar-count"><span class="n" id="save-bar-n">0</span> cambios sin guardar</span>
    <button type="button" class="btn btn-ghost btn-sm" onclick="discardPending()">Descartar</button>
    <button type="button" class="btn btn-primary btn-sm" id="save-bar-btn" onclick="saveBatch()">
        <i class="fa-solid fa-floppy-disk"></i> Guardar cambios
    </button>
</div>

<!-- form oculto para eliminar -->
<?php if ($puedeEliminar): ?>
<form id="mois-del" method="POST" style="display:none;">
    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
</form>
<?php endif; ?>

</div><!-- .mois -->

<script>
(function () {
    const BASE = '<?= APP_URL ?>';
    const CSRF = '<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>';

    // ── Guardado en lote: los interruptores ya NO guardan al toque.
    //    Solo cambian de estilo y quedan "pendientes" en este Map hasta que
    //    se presiona "Guardar cambios" en la barra flotante.
    const pending = new Map(); // id_nodo -> nuevoEstado (0|1)

    function applySwitchVisual(id, on) {
        const sw = document.querySelector(`.sw[data-node-id="${id}"]`);
        if (!sw) return;
        const inp = sw.querySelector('input');
        if (inp) inp.checked = on;
        const nodeRow = sw.closest('.node, .mod-summary');
        if (nodeRow) {
            const desc = nodeRow.querySelector('.row-desc');
            if (desc) desc.classList.toggle('row-off', !on);
            nodeRow.classList.toggle('node-pending', pending.has(id));
        }
    }

    function updateSaveBar() {
        const bar = document.getElementById('save-bar');
        const n   = document.getElementById('save-bar-n');
        if (n) n.textContent = pending.size;
        if (bar) bar.classList.toggle('show', pending.size > 0);
    }

    window.stageToggle = function (id, label) {
        const inp = label.querySelector('input');
        const originalOn = inp.defaultChecked; // estado tal como llegó del servidor
        const newOn = !inp.checked;

        if (newOn === originalOn) {
            pending.delete(id); // volvió a como estaba — ya no hay nada pendiente para este nodo
        } else {
            pending.set(id, newOn ? 1 : 0);
        }
        applySwitchVisual(id, newOn);
        updateSaveBar();
    };

    window.discardPending = function () {
        pending.forEach((_, id) => applySwitchVisual(id, document.querySelector(`.sw[data-node-id="${id}"] input`)?.defaultChecked));
        pending.clear();
        updateSaveBar();
    };

    window.saveBatch = async function () {
        if (pending.size === 0) return;
        const btn = document.getElementById('save-bar-btn');
        btn.disabled = true;
        const original = btn.innerHTML;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Guardando…';

        try {
            const cambios = [...pending.entries()].map(([id_nodo, estado]) => ({ id_nodo, estado }));
            const r = await fetch(`${BASE}/admin/menu/guardar-lote`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-Token': CSRF,
                },
                body: JSON.stringify({ cambios }),
            });
            const d = await r.json();
            if (!d.ok) { PortalAlert.error(d.msg || 'No se pudieron guardar los cambios.'); return; }

            // Sincronizar TODOS los nodos afectados (incluye cascada, no solo los tocados a mano)
            Object.entries(d.estados || {}).forEach(([id, estado]) => {
                pending.delete(parseInt(id));
                applySwitchVisual(parseInt(id), !!estado);
            });
            pending.clear();
            updateSaveBar();
            await refreshSidebar();
        } catch (e) {
            PortalAlert.error('Error de conexión al guardar.');
        } finally {
            btn.disabled = false;
            btn.innerHTML = original;
        }
    };

    // Aviso del navegador si hay cambios sin guardar y se intenta salir de la página.
    window.addEventListener('beforeunload', (e) => {
        if (pending.size > 0) { e.preventDefault(); e.returnValue = ''; }
    });

    // refreshSidebar() ahora vive en js/main.js (compartida con Roles y Permisos).

    window.delNode = function (id) {
        const f = document.getElementById('mois-del');
        if (!f) return;
        f.action = `${BASE}/admin/menu/${id}/eliminar`;
        PortalAlert.confirmDelete('¿Eliminar este nodo del menú? Esta acción no se puede deshacer.', f);
    };

    window.moisSearch = function (q) {
        q = q.toLowerCase().trim();
        document.querySelectorAll('.mod-card').forEach(card => {
            let any = false;
            card.querySelectorAll('.node').forEach(row => {
                const hit = !q || row.textContent.toLowerCase().includes(q);
                const blk = row.closest('.sub-blk, .item-blk, .area-blk');
                if (blk) blk.style.display = hit ? '' : 'none';
                if (hit) any = true;
            });
            // si un padre coincide, mostrar todos sus hijos
            if (q) {
                card.querySelectorAll('.area-blk, .item-blk').forEach(blk => {
                    const sum = blk.querySelector(':scope > details > summary, :scope > .node');
                    if (sum && sum.textContent.toLowerCase().includes(q)) {
                        blk.style.display = '';
                        blk.querySelectorAll('.sub-blk, .item-blk').forEach(c => c.style.display = '');
                        any = true;
                    }
                });
            }
            card.style.display = (!q || any) ? '' : 'none';
            if (q && any) card.open = true;
        });
    };

    let lvlOn = 0;
    window.moisFilter = function (lvl, btn) {
        lvlOn = (lvlOn === lvl) ? 0 : lvl;
        document.querySelectorAll('.lvl-f').forEach(b =>
            b.classList.toggle('on', parseInt(b.dataset.lvl) === lvlOn));
        const all = lvlOn === 0;
        document.querySelectorAll('.area-blk').forEach(b => b.style.display = (all || lvlOn >= 2) ? '' : 'none');
        document.querySelectorAll('.item-blk').forEach(b => b.style.display = (all || lvlOn >= 3) ? '' : 'none');
        document.querySelectorAll('.sub-blk').forEach(b  => b.style.display = (all || lvlOn === 4) ? '' : 'none');
        if (lvlOn >= 2) moisExpand(true);
    };

    window.moisExpand = function (open) {
        // solo el árbol — el panel de ayuda se maneja aparte
        document.querySelectorAll('#mois-tree details').forEach(d => d.open = open);
    };
})();
</script>
