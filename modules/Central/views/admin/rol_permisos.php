<?php
/**
 * Roles y Permisos — tabla checklist (rediseño 2026-07-29, pulido x2).
 * Modelo de datos sin cambios: nivel_crud 0-4 acumulativo por nodo
 * (CORE_Permisos_Nodo). Los 4 checkboxes son presentación en cascada —
 * el POST sigue viajando como permisos[clave] = entero 0-4, vía un
 * <input type="hidden"> por fila que el JS mantiene sincronizado.
 *
 * Fix del pulido anterior: el scroll interno (max-height:70vh + sticky
 * de fila de módulo con top adivinado a mano) generaba doble scrollbar
 * y saltos — se sacó. Ahora la tabla fluye con la página, un solo
 * scroll, y la cabecera de columnas se pega al tope de la PÁGINA (no de
 * un contenedor propio) usando la posición real del <thead>, medida en
 * runtime — nada de offsets fijos adivinados.
 */
$success = SessionHelper::getFlash('success');
$cruds = [1=>'Ver', 2=>'Crear', 3=>'Editar', 4=>'Total'];

// Aplana el árbol a filas [nivel, nodo] en el mismo orden que antes
// (raíz, luego áreas → items → subitems), calculando de paso cuántos
// nodos configurables tiene cada módulo y cuántos ya están configurados
// (para el resumen por sección).
function permisos_flatten(array $tree): array {
    $rows = [];
    foreach ($tree as $modId => $mod) {
        $modRows = [];
        if ($mod['raiz']) $modRows[] = ['tipo' => 'nodo', 'lvl' => 1, 'n' => $mod['raiz']];
        foreach ($mod['areas'] as $area) {
            if ($area['nodo']) $modRows[] = ['tipo' => 'nodo', 'lvl' => 2, 'n' => $area['nodo']];
            foreach ($area['items'] as $item) {
                if ($item['nodo']) $modRows[] = ['tipo' => 'nodo', 'lvl' => 3, 'n' => $item['nodo']];
                foreach ($item['subitems'] as $sub) {
                    $modRows[] = ['tipo' => 'nodo', 'lvl' => 4, 'n' => $sub];
                }
            }
        }
        $modTotal  = count($modRows);
        $modConfig = count(array_filter($modRows, fn($r) => $r['n']['permiso'] > 0));
        $rows[] = ['tipo' => 'modulo', 'modId' => $modId, 'mod' => $mod, 'total' => $modTotal, 'config' => $modConfig];
        array_push($rows, ...$modRows);
    }
    return $rows;
}
$filas = permisos_flatten($tree);

$totalNodos = count(array_filter($filas, fn($f) => $f['tipo'] === 'nodo'));
$configNodos = count(array_filter($filas, fn($f) => $f['tipo'] === 'nodo' && $f['n']['permiso'] > 0));

$nivelLabels = [0=>'Operativo',1=>'Analista',2=>'Jefatura',3=>'Director',4=>'Super Admin'];
?>

<?php if ($success): ?>
<script>document.addEventListener('DOMContentLoaded', () => PortalAlert.success(<?= json_encode($success) ?>));</script>
<?php endif; ?>

<style>
.perm2 {
    --g-bg: var(--surface-app); --g-bg-soft: var(--accent-app); --g-bd: var(--border-app);
    --g-shadow: var(--shadow-app);
    --l1: #8b5cf6; --l2: #3b82f6; --l3: #22c55e; --l4: #f59e0b;
}
.perm2 .gx { background:var(--g-bg); border:1px solid var(--g-bd); border-radius:var(--radius-lg); box-shadow:var(--g-shadow); }

/* ── Hero del rol ── */
.perm2-hero {
    display:flex; align-items:center; gap:var(--sp-4); padding:var(--sp-4) var(--sp-5); margin-bottom:var(--sp-4);
    position:relative; overflow:hidden;
}
.perm2-hero::before {
    content:''; position:absolute; inset:0; opacity:.05; pointer-events:none;
    background:radial-gradient(ellipse 60% 100% at 0% 50%, var(--color-primary), transparent);
}
.perm2-hero-ico {
    width:52px; height:52px; border-radius:var(--radius-lg); flex-shrink:0;
    display:flex; align-items:center; justify-content:center; font-size:1.15rem;
    background:linear-gradient(135deg, var(--color-primary), color-mix(in srgb, var(--color-primary) 55%, #000));
    color:#fff; box-shadow:0 6px 18px -4px color-mix(in srgb, var(--color-primary) 55%, transparent);
}
.perm2-hero-body { flex:1; min-width:0; position:relative; }
.perm2-hero-name { font-size:var(--font-size-xl); font-weight:var(--font-weight-bold); letter-spacing:-.015em; color:var(--color-text); line-height:1.15; }
.perm2-hero-meta { display:flex; align-items:center; gap:var(--sp-3); margin-top:6px; flex-wrap:wrap; font-size:var(--font-size-xs); color:var(--color-text-muted); }
.perm2-hero-meta code { font-family:var(--font-mono); background:var(--g-bg-soft); border:1px solid var(--g-bd); padding:2px 7px; border-radius:var(--radius-sm); color:var(--color-primary); font-weight:var(--font-weight-semibold); }
.perm2-hero-meta .sep { width:3px; height:3px; border-radius:50%; background:var(--color-text-light); }
.perm2-hero-stats { display:flex; gap:var(--sp-5); flex-shrink:0; position:relative; }
.perm2-stat { text-align:right; }
.perm2-stat b { display:block; font-size:var(--font-size-lg); font-weight:var(--font-weight-bold); color:var(--color-text); line-height:1.1; font-variant-numeric:tabular-nums; }
.perm2-stat span { font-size:10.5px; color:var(--color-text-muted); text-transform:uppercase; letter-spacing:.05em; }

/* ── Toolbar ── */
.perm2-toolbar { display:flex; align-items:center; gap:var(--sp-4); flex-wrap:wrap; padding:var(--sp-3) var(--sp-4); margin-bottom:var(--sp-4); }
.perm2-search { position:relative; flex:1; min-width:220px; max-width:320px; }
.perm2-search i { position:absolute; left:var(--sp-3); top:50%; transform:translateY(-50%); color:var(--color-text-light); font-size:11px; }
.perm2-search input {
    width:100%; padding:8px var(--sp-3) 8px 2rem; border-radius:var(--radius-md);
    border:1px solid var(--g-bd); background:var(--g-bg-soft); color:var(--color-text);
    font-size:var(--font-size-sm); transition:border-color var(--transition-fast), box-shadow var(--transition-fast);
}
.perm2-search input:focus { outline:none; border-color:var(--color-primary); box-shadow:0 0 0 3px color-mix(in srgb,var(--color-primary) 16%,transparent); }
.perm2-search input::placeholder { color:var(--color-text-light); }

.perm2-progress { display:flex; align-items:center; gap:10px; font-size:var(--font-size-xs); color:var(--color-text-muted); font-variant-numeric:tabular-nums; }
.perm2-progress b { color:var(--color-text); font-weight:var(--font-weight-bold); font-size:var(--font-size-sm); }
.perm2-ring { width:30px; height:30px; flex-shrink:0; transform:rotate(-90deg); }
.perm2-ring circle { fill:none; stroke-width:3; }
.perm2-ring .track { stroke:var(--color-surface-3); }
.perm2-ring .fill { stroke:var(--color-primary); stroke-linecap:round; transition:stroke-dashoffset .5s cubic-bezier(.4,0,.2,1); }

.perm2-quick { display:flex; gap:4px; margin-left:auto; padding-left:var(--sp-3); border-left:1px solid var(--g-bd); }
.perm2-quick button {
    display:inline-flex; align-items:center; gap:6px; padding:6px 12px; border-radius:var(--radius-md);
    border:1px solid transparent; background:transparent; color:var(--color-text-muted);
    font-size:var(--font-size-xs); font-weight:var(--font-weight-medium); cursor:pointer;
    font-family:var(--font-family); transition:all var(--transition-fast);
}
.perm2-quick button:hover { color:var(--color-text); background:var(--g-bg-soft); border-color:var(--g-bd); }
.perm2-quick button:active { transform:translateY(1px); }

/* ── Tabla — un solo flujo de scroll (el de la página), sin contenedor propio ── */
.perm2-table-wrap { overflow-x:auto; }
table.perm2-table { width:100%; border-collapse:separate; border-spacing:0; font-size:var(--font-size-sm); }

table.perm2-table thead th {
    position:sticky; top:0; z-index:6; background:var(--g-bg-soft);
    border-bottom:1px solid var(--g-bd); padding:11px var(--sp-2);
    font-size:10.5px; font-weight:var(--font-weight-bold); color:var(--color-text-muted);
    text-transform:uppercase; letter-spacing:.06em; white-space:nowrap;
    transition:box-shadow .2s ease;
}
table.perm2-table.is-scrolled thead th { box-shadow:0 4px 10px -4px rgba(0,0,0,.18); }
table.perm2-table th.col-nodo { text-align:left; padding-left:var(--sp-4); }
table.perm2-table th.col-chk { text-align:center; width:72px; cursor:pointer; user-select:none; }
table.perm2-table th.col-chk:hover { color:var(--color-primary); }
table.perm2-table th.col-chk .hint { display:block; font-weight:var(--font-weight-normal); text-transform:none; letter-spacing:0; font-size:9px; opacity:.65; margin-top:2px; }

/* Cabecera de módulo: acento de color sobre superficie neutra (no bloque sólido), NO sticky — fluye con la página */
tr.perm2-modrow td {
    padding:9px var(--sp-3) 9px 14px; font-weight:var(--font-weight-bold); font-size:12.5px;
    letter-spacing:.02em; color:var(--color-text);
    background:var(--g-bg-soft); border-left:3px solid var(--mod-c, var(--color-primary));
    border-bottom:1px solid var(--g-bd); border-top:1px solid var(--g-bd);
}
tr.perm2-modrow:first-child td { border-top:none; }
tr.perm2-modrow .modico {
    display:inline-flex; align-items:center; justify-content:center; width:21px; height:21px; border-radius:6px;
    background:color-mix(in srgb, var(--mod-c, var(--color-primary)) 16%, transparent);
    color:var(--mod-c, var(--color-primary)); margin-right:9px; font-size:.68rem; flex-shrink:0;
}
tr.perm2-modrow .modcount {
    float:right; font-size:10.5px; font-weight:var(--font-weight-semibold); color:var(--color-text-muted);
    background:var(--g-bg); border:1px solid var(--g-bd); padding:1px 8px; border-radius:var(--radius-full);
    font-variant-numeric:tabular-nums;
}

tr.perm2-noderow td { border-bottom:1px solid var(--g-bd); padding:5px var(--sp-2); vertical-align:middle; transition:background .15s ease; }
tr.perm2-noderow:hover td { background:color-mix(in srgb, var(--g-bg-soft) 65%, transparent); }
tr.perm2-noderow.changed td.col-nodo { border-left:2px solid var(--color-warning); }
tr.perm2-noderow.changed td:first-child { padding-left:calc(var(--sp-2) - 2px); }

.pn-nodo { display:flex; align-items:center; gap:9px; padding-left:calc(var(--lvl) * 18px); min-height:24px; }
.pn-nodo[style*="--lvl: 1"], .pn-nodo[style*="--lvl:1"] { border-left:1px solid var(--g-bd); margin-left:9px; }
.pn-nodo[style*="--lvl: 2"], .pn-nodo[style*="--lvl:2"] { border-left:1px solid var(--g-bd); margin-left:27px; }
.pn-nodo[style*="--lvl: 3"], .pn-nodo[style*="--lvl:3"] { border-left:1px solid var(--g-bd); margin-left:45px; }

.pn-dot { width:7px; height:7px; border-radius:50%; flex-shrink:0; }
.pn-dot.l1 { background:var(--l1); } .pn-dot.l2 { background:var(--l2); }
.pn-dot.l3 { background:var(--l3); } .pn-dot.l4 { background:var(--l4); }
.pn-desc { color:var(--color-text); font-weight:var(--font-weight-medium); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:320px; }
.pn-badge {
    margin-left:auto; font-size:9.5px; font-weight:var(--font-weight-semibold); padding:2px 8px 2px 6px;
    border-radius:var(--radius-full); white-space:nowrap; flex-shrink:0; display:inline-flex; align-items:center; gap:4px;
    transition:color .15s ease, background .15s ease;
}
.pn-badge .d { width:5px; height:5px; border-radius:50%; background:currentColor; opacity:.85; }

/* ── Checklist en cascada: los 4 casilleros leen como UNA escala continua ── */
td.col-chk {
    text-align:center; position:relative;
    background:color-mix(in srgb, var(--g-bg-soft) 55%, transparent);
    border-left:1px solid var(--g-bd);
}
td.col-chk:last-child { border-right:1px solid var(--g-bd); }
td.col-chk::after {
    content:''; position:absolute; left:0; right:0; bottom:0; height:2.5px;
    background:var(--g-bd); transition:background .2s ease;
}
td.col-chk.step-fill::after { background:var(--step-c); }

.pn-cb { position:relative; display:inline-flex; width:18px; height:18px; }
.pn-cb input { position:absolute; opacity:0; width:100%; height:100%; margin:0; cursor:pointer; z-index:1; }
.pn-cb .box {
    position:absolute; inset:0; border:1.5px solid var(--g-bd); border-radius:5px; background:var(--g-bg);
    transition:transform .12s cubic-bezier(.34,1.56,.64,1), background .15s ease, border-color .15s ease;
    display:flex; align-items:center; justify-content:center;
}
.pn-cb .box i { font-size:9.5px; color:#fff; opacity:0; transform:scale(.4); transition:all .15s cubic-bezier(.34,1.56,.64,1); }
.pn-cb input:hover ~ .box { border-color:var(--step-c, var(--color-primary)); }
.pn-cb input:checked ~ .box i { opacity:1; transform:scale(1); }
.pn-cb input:checked ~ .box { transform:scale(1.04); }
.pn-cb.c1 input:checked ~ .box { background:var(--l1); border-color:var(--l1); }
.pn-cb.c2 input:checked ~ .box { background:var(--l2); border-color:var(--l2); }
.pn-cb.c3 input:checked ~ .box { background:var(--l3); border-color:var(--l3); }
.pn-cb.c4 input:checked ~ .box { background:var(--l4); border-color:var(--l4); }
.pn-cb input:focus-visible ~ .box { box-shadow:0 0 0 3px color-mix(in srgb,var(--color-primary) 30%,transparent); }

@media (max-width:820px) {
    .perm2-hero { flex-wrap:wrap; }
    .perm2-hero-stats { width:100%; justify-content:space-between; }
    .pn-desc { max-width:150px; }
    table.perm2-table th.col-chk .hint { display:none; }
    .perm2-quick { border-left:none; padding-left:0; margin-left:0; }
}
</style>

<div class="perm2">

<div style="display:flex;align-items:center;gap:var(--sp-3);margin-bottom:var(--sp-2);">
    <a href="<?= APP_URL ?>/admin/roles" class="btn btn-ghost btn-sm" data-spa>
        <i class="fa-solid fa-arrow-left"></i> Roles
    </a>
</div>

<div class="gx perm2-hero">
    <div class="perm2-hero-ico"><i class="fa-solid fa-shield-halved"></i></div>
    <div class="perm2-hero-body">
        <div class="perm2-hero-name"><?= htmlspecialchars($rol['nombre'], ENT_QUOTES, 'UTF-8') ?></div>
        <div class="perm2-hero-meta">
            <code><?= htmlspecialchars($rol['codigo'], ENT_QUOTES, 'UTF-8') ?></code>
            <span class="sep"></span>
            <span><?= $nivelLabels[(int)$rol['nivel_jerarquia']] ?? 'Operativo' ?></span>
            <?php if (!empty($rol['departamento'])): ?>
            <span class="sep"></span>
            <span><i class="fa-solid fa-sitemap" style="opacity:.6;"></i> <?= htmlspecialchars($rol['departamento'], ENT_QUOTES, 'UTF-8') ?></span>
            <?php endif; ?>
        </div>
    </div>
    <div class="perm2-hero-stats">
        <div class="perm2-stat"><b><?= (int)$usuariosConRol ?></b><span>Usuario<?= $usuariosConRol === 1 ? '' : 's' ?></span></div>
        <div class="perm2-stat"><b><?= $configNodos ?>/<?= $totalNodos ?></b><span>Permisos</span></div>
    </div>
</div>

<form method="POST" action="<?= APP_URL ?>/admin/roles/<?= (int)$rol['id_rol'] ?>/permisos" id="perm2-form" data-bypass>
    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">

    <div class="gx perm2-toolbar">
        <div class="perm2-search">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" id="perm2-q" placeholder="Buscar por nombre…" oninput="perm2Search(this.value)" autocomplete="off">
        </div>
        <div class="perm2-progress">
            <svg class="perm2-ring" viewBox="0 0 36 36">
                <circle class="track" cx="18" cy="18" r="15.5"></circle>
                <circle class="fill" id="perm2-ring-fill" cx="18" cy="18" r="15.5"
                        stroke-dasharray="97.4" stroke-dashoffset="<?= $totalNodos > 0 ? round(97.4 * (1 - $configNodos / $totalNodos)) : 97.4 ?>"></circle>
            </svg>
            <span id="perm2-count"><b><?= $configNodos ?></b>/<?= $totalNodos ?><br>configurados</span>
        </div>
        <div class="perm2-quick">
            <button type="button" onclick="perm2SetAll(0)"><i class="fa-solid fa-ban"></i> Sin acceso</button>
            <button type="button" onclick="perm2SetAll(1)"><i class="fa-solid fa-eye"></i> Solo ver</button>
            <button type="button" onclick="perm2SetAll(4)"><i class="fa-solid fa-unlock"></i> Acceso total</button>
        </div>
    </div>

    <div class="gx perm2-table-wrap">
        <table class="perm2-table" id="perm2-table">
            <thead>
                <tr>
                    <th class="col-nodo">Módulo / Pantalla</th>
                    <?php foreach ($cruds as $val => $lbl): ?>
                    <th class="col-chk" data-col="<?= $val ?>" onclick="perm2ToggleColumn(<?= $val ?>)">
                        <?= $lbl ?><span class="hint">tildar todo</span>
                    </th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($filas as $f): ?>
                <?php if ($f['tipo'] === 'modulo'):
                    $mod = $f['mod'];
                ?>
                <tr class="perm2-modrow" style="--mod-c:<?= $mod['color'] ?>;" data-mod-header="<?= $f['modId'] ?>">
                    <td colspan="5">
                        <span class="modcount"><?= $f['config'] ?>/<?= $f['total'] ?></span>
                        <span class="modico"><i class="fa-solid <?= $mod['icon'] ?>"></i></span>
                        <?= htmlspecialchars($mod['label'], ENT_QUOTES, 'UTF-8') ?>
                    </td>
                </tr>
                <?php else:
                    $n = $f['n']; $lvl = $f['lvl']; $perm = (int)$n['permiso'];
                    $badgeInfo = [0=>['Sin acceso','var(--color-text-muted)','transparent'],1=>['Ver','var(--l1)','color-mix(in srgb,var(--l1) 12%,transparent)'],2=>['Crear','var(--l2)','color-mix(in srgb,var(--l2) 12%,transparent)'],3=>['Editar','var(--l3)','color-mix(in srgb,var(--l3) 12%,transparent)'],4=>['Total','var(--l4)','color-mix(in srgb,var(--l4) 14%,transparent)']][$perm];
                ?>
                <tr class="perm2-noderow" data-search="<?= strtolower(htmlspecialchars($n['descripcion'], ENT_QUOTES, 'UTF-8')) ?>" data-original="<?= $perm ?>">
                    <td class="col-nodo">
                        <div class="pn-nodo" style="--lvl:<?= $lvl - 1 ?>;">
                            <span class="pn-dot l<?= $lvl ?>" title="Nivel L<?= $lvl ?>"></span>
                            <span class="pn-desc" title="<?= htmlspecialchars($n['descripcion'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($n['descripcion'], ENT_QUOTES, 'UTF-8') ?></span>
                            <span class="pn-badge" data-role="badge" style="color:<?= $badgeInfo[1] ?>;background:<?= $badgeInfo[2] ?>;"><span class="d"></span><?= $badgeInfo[0] ?></span>
                        </div>
                        <input type="hidden" name="permisos[<?= $n['key'] ?>]" value="<?= $perm ?>" data-role="value">
                    </td>
                    <?php foreach ($cruds as $val => $lbl):
                        $stepColor = ['', 'var(--l1)', 'var(--l2)', 'var(--l3)', 'var(--l4)'][$val];
                    ?>
                    <td class="col-chk <?= $perm >= $val ? 'step-fill' : '' ?>" style="--step-c:<?= $stepColor ?>;">
                        <label class="pn-cb c<?= $val ?>">
                            <input type="checkbox" data-lvl="<?= $val ?>" <?= $perm >= $val ? 'checked' : '' ?> onchange="perm2Toggle(this)">
                            <span class="box"><i class="fa-solid fa-check"></i></span>
                        </label>
                    </td>
                    <?php endforeach; ?>
                </tr>
                <?php endif; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div style="display:flex;justify-content:flex-end;gap:var(--sp-3);margin-top:var(--sp-5);padding-top:var(--sp-4);border-top:1px solid var(--g-bd);">
        <a href="<?= APP_URL ?>/admin/roles" class="btn btn-outline" data-spa>Cancelar</a>
        <button type="submit" class="btn btn-primary">
            <i class="fa-solid fa-floppy-disk"></i> Guardar Permisos
        </button>
    </div>
</form>
</div>

<script>
const PERM2_LABELS = {0:'Sin acceso',1:'Ver',2:'Crear',3:'Editar',4:'Total'};
const PERM2_COLORS = {0:['var(--color-text-muted)','transparent'],1:['var(--l1)','color-mix(in srgb,var(--l1) 12%,transparent)'],2:['var(--l2)','color-mix(in srgb,var(--l2) 12%,transparent)'],3:['var(--l3)','color-mix(in srgb,var(--l3) 12%,transparent)'],4:['var(--l4)','color-mix(in srgb,var(--l4) 14%,transparent)']};

function perm2Row(el) { return el.closest('tr.perm2-noderow'); }

function perm2ApplyRow(tr, value) {
    value = Math.max(0, Math.min(4, value));
    tr.querySelectorAll('td.col-chk').forEach(td => {
        const cb = td.querySelector('input[type=checkbox]');
        const lvl = parseInt(cb.dataset.lvl);
        cb.checked = lvl <= value;
        td.classList.toggle('step-fill', lvl <= value);
    });
    tr.querySelector('[data-role="value"]').value = value;
    const badge = tr.querySelector('[data-role="badge"]');
    if (badge) {
        badge.lastChild.textContent = PERM2_LABELS[value];
        badge.style.color = PERM2_COLORS[value][0];
        badge.style.background = PERM2_COLORS[value][1];
    }
    tr.classList.toggle('changed', String(value) !== tr.dataset.original);
}

window.perm2Toggle = function (checkbox) {
    const tr = perm2Row(checkbox);
    const lvl = parseInt(checkbox.dataset.lvl);
    const newValue = checkbox.checked ? lvl : (lvl - 1);
    perm2ApplyRow(tr, newValue);
    perm2UpdateProgress();
};

window.perm2ToggleColumn = function (lvl) {
    const rows = [...document.querySelectorAll('tr.perm2-noderow')].filter(tr => tr.style.display !== 'none');
    if (!rows.length) return;
    const allAtLeast = rows.every(tr => parseInt(tr.querySelector('[data-role="value"]').value) >= lvl);
    rows.forEach(tr => {
        const cur = parseInt(tr.querySelector('[data-role="value"]').value);
        perm2ApplyRow(tr, allAtLeast ? Math.min(cur, lvl - 1) : Math.max(cur, lvl));
    });
    perm2UpdateProgress();
};

window.perm2SetAll = function (value) {
    document.querySelectorAll('tr.perm2-noderow').forEach(tr => perm2ApplyRow(tr, value));
    perm2UpdateProgress();
};

const RING_CIRC = 97.4;
function perm2UpdateProgress() {
    const rows = document.querySelectorAll('tr.perm2-noderow');
    const total = rows.length;
    let conf = 0;
    rows.forEach(tr => { if (parseInt(tr.querySelector('[data-role="value"]').value) > 0) conf++; });

    const countEl = document.getElementById('perm2-count');
    const ringEl  = document.getElementById('perm2-ring-fill');
    if (countEl) countEl.innerHTML = `<b>${conf}</b>/${total}<br>configurados`;
    if (ringEl)  ringEl.style.strokeDashoffset = total > 0 ? (RING_CIRC * (1 - conf / total)) : RING_CIRC;

    // Contador por módulo (badge redondo en cada fila de sección)
    const perMod = {};
    let curMod = null;
    document.querySelectorAll('#perm2-table tbody tr').forEach(tr => {
        if (tr.classList.contains('perm2-modrow')) { curMod = tr; perMod[tr.dataset.modHeader] = { c: 0, t: 0, tr }; return; }
        if (!curMod) return;
        const key = curMod.dataset.modHeader;
        perMod[key].t++;
        if (parseInt(tr.querySelector('[data-role="value"]').value) > 0) perMod[key].c++;
    });
    Object.values(perMod).forEach(m => {
        const el = m.tr.querySelector('.modcount');
        if (el) el.textContent = `${m.c}/${m.t}`;
    });
}

window.perm2Search = function (q) {
    q = q.toLowerCase().trim();
    const modGroups = {};
    document.querySelectorAll('#perm2-table tbody tr').forEach(tr => {
        if (tr.classList.contains('perm2-modrow')) { modGroups[tr.dataset.modHeader] = { tr, any: false }; return; }
    });
    let currentMod = null;
    document.querySelectorAll('#perm2-table tbody tr').forEach(tr => {
        if (tr.classList.contains('perm2-modrow')) { currentMod = tr.dataset.modHeader; return; }
        const hit = !q || (tr.dataset.search || '').includes(q);
        tr.style.display = hit ? '' : 'none';
        if (hit && currentMod !== null) modGroups[currentMod].any = true;
    });
    Object.values(modGroups).forEach(g => { g.tr.style.display = (!q || g.any) ? '' : 'none'; });
};

document.addEventListener('DOMContentLoaded', () => {
    // Sombra en la cabecera pegajosa cuando el CONTENIDO DE LA PÁGINA (no un
    // contenedor propio) se desplaza por debajo de ella — un solo scroll,
    // sin offsets fijos adivinados.
    const table = document.getElementById('perm2-table');
    const thead = table?.querySelector('thead');
    if (!table || !thead) return;
    const onScroll = () => {
        const r = thead.getBoundingClientRect();
        table.classList.toggle('is-scrolled', r.top <= 0);
    };
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
});

// Guardar por AJAX: los cambios quedan efectivos al instante y el sidebar se
// refresca solo, sin recargar la página completa (mismo patrón que Estructura
// del Menú).
document.getElementById('perm2-form')?.addEventListener('submit', async function (e) {
    e.preventDefault();
    const btn = this.querySelector('button[type="submit"]');
    const original = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Guardando…';

    let d;
    try {
        const r = await fetch(this.action, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: new FormData(this),
        });
        const text = await r.text();
        try {
            d = JSON.parse(text);
        } catch (parseErr) {
            console.error('Respuesta no-JSON al guardar permisos:', text);
            PortalAlert.error('El servidor respondió algo inesperado (revisa la consola).');
            return;
        }
    } catch (err) {
        console.error('Error de red al guardar permisos:', err);
        PortalAlert.error('Error de conexión al guardar.');
        return;
    } finally {
        btn.disabled = false;
        btn.innerHTML = original;
    }

    if (!d.ok) { PortalAlert.error(d.msg || 'No se pudieron guardar los permisos.'); return; }
    PortalAlert.success(d.msg || 'Permisos guardados correctamente.');

    // El refresco del sidebar es un extra visual — si falla, NUNCA debe
    // hacer parecer que el guardado (ya confirmado arriba) fue el que falló.
    try {
        await window.refreshSidebar();
    } catch (err) {
        console.error('El guardado fue exitoso, pero no se pudo refrescar el sidebar:', err);
    }
});
</script>
