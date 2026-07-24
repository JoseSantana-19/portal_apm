<?php
$success = SessionHelper::getFlash('success');

$cruds = [0=>'Sin acceso', 1=>'Ver', 2=>'Crear', 3=>'Editar', 4=>'Total'];
$crudColors = [
    0 => ['bg'=>'transparent',                                        'text'=>'var(--color-text-muted)', 'border'=>'var(--color-border)'],
    1 => ['bg'=>'color-mix(in srgb,var(--crud1) 10%,transparent)',    'text'=>'var(--crud1)',            'border'=>'var(--crud1)'],
    2 => ['bg'=>'color-mix(in srgb,var(--crud2) 10%,transparent)',    'text'=>'var(--crud2)',            'border'=>'var(--crud2)'],
    3 => ['bg'=>'color-mix(in srgb,var(--crud3-bd) 10%,transparent)', 'text'=>'var(--crud3)',            'border'=>'var(--crud3-bd)'],
    4 => ['bg'=>'color-mix(in srgb,var(--color-primary) 10%,transparent)', 'text'=>'var(--color-primary)', 'border'=>'var(--color-primary)'],
];

// Count total configured nodos
$totalNodos = 0;
$configNodos = 0;
foreach ($tree as $mod) {
    if ($mod['raiz']) { $totalNodos++; if ($mod['raiz']['permiso']) $configNodos++; }
    foreach ($mod['areas'] as $area) {
        if ($area['nodo']) { $totalNodos++; if ($area['nodo']['permiso']) $configNodos++; }
        foreach ($area['items'] as $item) {
            if ($item['nodo']) { $totalNodos++; if ($item['nodo']['permiso']) $configNodos++; }
            foreach ($item['subitems'] as $sub) { $totalNodos++; if ($sub['permiso']) $configNodos++; }
        }
    }
}
?>

<?php if ($success): ?>
<div class="alert alert-success" style="margin-bottom:var(--sp-4);">
    <i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?>
</div>
<?php endif; ?>

<!-- Header -->
<div style="display:flex;align-items:flex-start;gap:var(--sp-3);margin-bottom:var(--sp-5);flex-wrap:wrap;">
    <a href="<?= APP_URL ?>/admin/roles" class="btn btn-ghost btn-sm" data-spa style="margin-top:3px;">
        <i class="fa-solid fa-arrow-left"></i>
    </a>
    <div style="flex:1;min-width:200px;">
        <h2 class="page-title">
            <i class="fa-solid fa-shield-halved" style="color:var(--color-primary);margin-right:var(--sp-2);"></i>
            Permisos — <?= htmlspecialchars($rol['nombre'], ENT_QUOTES, 'UTF-8') ?>
        </h2>
        <div style="display:flex;align-items:center;gap:var(--sp-3);margin-top:var(--sp-1);flex-wrap:wrap;">
            <code style="font-size:var(--font-size-xs);background:color-mix(in srgb,var(--color-primary) 8%,transparent);
                         color:var(--color-primary);padding:2px 6px;border-radius:var(--radius-sm);">
                <?= htmlspecialchars($rol['codigo'], ENT_QUOTES, 'UTF-8') ?>
            </code>
            <div style="font-size:var(--font-size-xs);color:var(--color-text-muted);">
                <span id="perm-count" style="font-weight:var(--font-weight-semibold);color:var(--color-text);"><?= $configNodos ?></span>
                / <?= $totalNodos ?> nodos configurados
            </div>
            <!-- Progress bar -->
            <div style="flex:1;min-width:120px;max-width:200px;height:6px;background:var(--color-surface-3);border-radius:var(--radius-full);overflow:hidden;">
                <div id="perm-bar" style="height:100%;width:<?= $totalNodos > 0 ? round($configNodos/$totalNodos*100) : 0 ?>%;
                                          background:var(--color-primary);border-radius:var(--radius-full);transition:width 0.3s ease;"></div>
            </div>
        </div>
    </div>
</div>

<!-- Legend -->
<div style="display:flex;align-items:center;gap:var(--sp-2);margin-bottom:var(--sp-4);flex-wrap:wrap;">
    <span style="font-size:var(--font-size-xs);color:var(--color-text-muted);margin-right:var(--sp-1);">Niveles:</span>
    <?php foreach ($cruds as $val => $lbl): ?>
    <span style="display:inline-flex;align-items:center;gap:4px;font-size:var(--font-size-xs);
                 padding:2px 8px;border-radius:var(--radius-full);
                 background:<?= $crudColors[$val]['bg'] ?>;
                 color:<?= $crudColors[$val]['text'] ?>;
                 border:1px solid <?= $crudColors[$val]['border'] ?>;">
        <?= $lbl ?>
    </span>
    <?php endforeach; ?>
</div>

<form method="POST" action="<?= APP_URL ?>/admin/roles/<?= (int)$rol['id_rol'] ?>/permisos" id="perms-form">
    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">

    <!-- Sticky toolbar -->
    <div style="position:sticky;top:calc(var(--topbar-height) + var(--sp-2));z-index:10;
                background:var(--color-bg);padding:var(--sp-3) 0;margin-bottom:var(--sp-4);">
        <div class="card" style="border-radius:var(--radius-md);">
            <div style="padding:var(--sp-3) var(--sp-4);display:flex;align-items:center;gap:var(--sp-2);flex-wrap:wrap;">
                <span style="font-size:var(--font-size-xs);color:var(--color-text-muted);margin-right:var(--sp-1);">Acceso rápido:</span>
                <button type="button" class="btn btn-ghost btn-sm" onclick="setAll(0)">
                    <i class="fa-solid fa-ban"></i> Sin acceso
                </button>
                <button type="button" class="btn btn-ghost btn-sm" style="color:var(--crud1);" onclick="setAll(1)">
                    <i class="fa-solid fa-eye"></i> Solo ver
                </button>
                <button type="button" class="btn btn-ghost btn-sm" style="color:var(--crud2);" onclick="setAll(2)">
                    <i class="fa-solid fa-plus"></i> Ver + Crear
                </button>
                <button type="button" class="btn btn-ghost btn-sm" style="color:var(--crud3);" onclick="setAll(3)">
                    <i class="fa-solid fa-pen"></i> Ver + Editar
                </button>
                <button type="button" class="btn btn-ghost btn-sm" style="color:var(--color-primary);" onclick="setAll(4)">
                    <i class="fa-solid fa-unlock"></i> Acceso total
                </button>
                <div style="flex:1;"></div>
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-floppy-disk"></i> Guardar Permisos
                </button>
            </div>
        </div>
    </div>

    <!-- Tree -->
    <?php foreach ($tree as $modId => $mod):
        // Count configured in this module
        $modTotal = 0; $modConfig = 0;
        if ($mod['raiz']) { $modTotal++; if ($mod['raiz']['permiso']) $modConfig++; }
        foreach ($mod['areas'] as $area) {
            if ($area['nodo']) { $modTotal++; if ($area['nodo']['permiso']) $modConfig++; }
            foreach ($area['items'] as $item) {
                if ($item['nodo']) { $modTotal++; if ($item['nodo']['permiso']) $modConfig++; }
                foreach ($item['subitems'] as $sub) { $modTotal++; if ($sub['permiso']) $modConfig++; }
            }
        }
    ?>
    <details class="perm-module" data-mod="<?= $modId ?>" style="margin-bottom:var(--sp-3);" open>
        <summary style="cursor:pointer;list-style:none;display:flex;align-items:center;gap:var(--sp-3);
                        background:var(--color-surface-2);border:1px solid var(--color-border);
                        border-radius:var(--radius-md);padding:var(--sp-3) var(--sp-4);
                        transition:background var(--transition);"
                 onmouseenter="this.style.background='var(--color-surface-3)'"
                 onmouseleave="this.style.background='var(--color-surface-2)'">
            <i class="fa-solid fa-chevron-right" style="font-size:0.65rem;color:var(--color-text-muted);
               transition:transform 0.2s;flex-shrink:0;" id="chev-mod-<?= $modId ?>"></i>
            <span style="font-weight:var(--font-weight-semibold);flex:1;">
                <?= htmlspecialchars($mod['label'], ENT_QUOTES, 'UTF-8') ?>
            </span>
            <!-- Module config progress -->
            <span style="font-size:var(--font-size-xs);color:var(--color-text-muted);" id="mod-badge-<?= $modId ?>">
                <?= $modConfig ?>/<?= $modTotal ?>
            </span>
            <div style="width:60px;height:5px;background:var(--color-surface-3);border-radius:var(--radius-full);overflow:hidden;margin-left:var(--sp-1);"
                 id="mod-bar-wrap-<?= $modId ?>">
                <div style="height:100%;width:<?= $modTotal > 0 ? round($modConfig/$modTotal*100) : 0 ?>%;
                             background:var(--color-primary);border-radius:var(--radius-full);transition:width 0.3s;"
                     id="mod-bar-<?= $modId ?>"></div>
            </div>
            <?php if ($mod['raiz']): ?>
            <div style="margin-left:var(--sp-2);" onclick="event.stopPropagation()">
                <?php $pr = $mod['raiz']['permiso']; ?>
                <select name="permisos[<?= $mod['raiz']['key'] ?>]"
                        class="perm-select form-control" data-mod="<?= $modId ?>"
                        style="width:110px;font-size:var(--font-size-xs);<?= permStyle($pr) ?>"
                        onchange="handleSelect(this); cascadeModule(this, <?= $modId ?>)">
                    <?php foreach ($cruds as $val => $lbl): ?>
                    <option value="<?= $val ?>" <?= $pr === $val ? 'selected' : '' ?>><?= $lbl ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
        </summary>

        <div style="border:1px solid var(--color-border);border-top:none;
                    border-radius:0 0 var(--radius-md) var(--radius-md);
                    padding:var(--sp-3) var(--sp-4) var(--sp-2);">
        <?php foreach ($mod['areas'] as $opId => $area): ?>

            <details class="perm-area" data-mod="<?= $modId ?>" data-op="<?= $opId ?>"
                     style="margin-bottom:var(--sp-2);" open>
                <summary style="cursor:pointer;list-style:none;display:flex;align-items:center;gap:var(--sp-2);
                                padding:var(--sp-2) var(--sp-3);border-radius:var(--radius-md);
                                background:color-mix(in srgb,var(--color-bg-subtle,var(--color-surface-2)) 40%,transparent);
                                transition:background var(--transition);"
                         onmouseenter="this.style.background='var(--color-surface-2)'"
                         onmouseleave="this.style.background='color-mix(in srgb,var(--color-surface-2) 40%,transparent)'">
                    <i class="fa-solid fa-chevron-right" style="font-size:0.6rem;color:var(--color-text-muted);
                       transition:transform 0.2s;flex-shrink:0;" id="chev-area-<?= $modId ?>-<?= $opId ?>"></i>
                    <span style="font-weight:var(--font-weight-medium);font-size:var(--font-size-sm);flex:1;">
                        <?= htmlspecialchars($area['nodo']['descripcion'] ?? "Área $opId", ENT_QUOTES, 'UTF-8') ?>
                    </span>
                    <?php if ($area['nodo']): ?>
                    <div onclick="event.stopPropagation()">
                        <?php $pa = $area['nodo']['permiso']; ?>
                        <select name="permisos[<?= $area['nodo']['key'] ?>]"
                                class="perm-select form-control" data-mod="<?= $modId ?>" data-op="<?= $opId ?>"
                                style="width:105px;font-size:var(--font-size-xs);<?= permStyle($pa) ?>"
                                onchange="handleSelect(this); cascadeArea(this, <?= $modId ?>, <?= $opId ?>)">
                            <?php foreach ($cruds as $val => $lbl): ?>
                            <option value="<?= $val ?>" <?= $pa === $val ? 'selected' : '' ?>><?= $lbl ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                </summary>

                <?php if (!empty($area['items'])): ?>
                <div style="padding:var(--sp-1) 0 0 var(--sp-4);">
                    <table style="width:100%;">
                    <?php foreach ($area['items'] as $itId => $item): ?>
                        <?php if ($item['nodo']): ?>
                        <tr class="perm-row" data-mod="<?= $modId ?>" data-op="<?= $opId ?>"
                            style="border-bottom:1px solid var(--color-border-light);">
                            <td style="padding:var(--sp-2) var(--sp-2);font-size:var(--font-size-sm);">
                                <i class="fa-solid fa-minus" style="color:var(--color-text-light);margin-right:var(--sp-2);font-size:0.55rem;"></i>
                                <?= htmlspecialchars($item['nodo']['descripcion'] ?? "Item $itId", ENT_QUOTES, 'UTF-8') ?>
                            </td>
                            <td style="width:115px;text-align:right;padding:var(--sp-1);">
                                <?php $pi = $item['nodo']['permiso']; ?>
                                <select name="permisos[<?= $item['nodo']['key'] ?>]"
                                        class="perm-select form-control" data-mod="<?= $modId ?>" data-op="<?= $opId ?>"
                                        style="width:105px;font-size:var(--font-size-xs);<?= permStyle($pi) ?>"
                                        onchange="handleSelect(this)">
                                    <?php foreach ($cruds as $val => $lbl): ?>
                                    <option value="<?= $val ?>" <?= $pi === $val ? 'selected' : '' ?>><?= $lbl ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <?php endif; ?>
                        <?php foreach ($item['subitems'] as $sub): ?>
                        <tr class="perm-row" data-mod="<?= $modId ?>" data-op="<?= $opId ?>"
                            style="border-bottom:1px solid var(--color-border-light);
                                   background:color-mix(in srgb,var(--color-surface-2) 30%,transparent);">
                            <td style="padding:var(--sp-1) var(--sp-2) var(--sp-1) var(--sp-8);font-size:var(--font-size-sm);">
                                <i class="fa-solid fa-corner-down-right" style="color:var(--color-text-light);margin-right:var(--sp-1);font-size:0.55rem;"></i>
                                <?= htmlspecialchars($sub['descripcion'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                            </td>
                            <td style="width:115px;text-align:right;padding:var(--sp-1);">
                                <?php $ps = $sub['permiso']; ?>
                                <select name="permisos[<?= $sub['key'] ?>]"
                                        class="perm-select form-control" data-mod="<?= $modId ?>" data-op="<?= $opId ?>"
                                        style="width:105px;font-size:var(--font-size-xs);<?= permStyle($ps) ?>"
                                        onchange="handleSelect(this)">
                                    <?php foreach ($cruds as $val => $lbl): ?>
                                    <option value="<?= $val ?>" <?= $ps === $val ? 'selected' : '' ?>><?= $lbl ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                    </table>
                </div>
                <?php endif; ?>
            </details>

        <?php endforeach; ?>
        </div>
    </details>
    <?php endforeach; ?>

    <div style="display:flex;justify-content:flex-end;gap:var(--sp-3);margin-top:var(--sp-5);padding-top:var(--sp-4);
                border-top:1px solid var(--color-border);">
        <a href="<?= APP_URL ?>/admin/roles" class="btn btn-outline" data-spa>Cancelar</a>
        <button type="submit" class="btn btn-primary">
            <i class="fa-solid fa-floppy-disk"></i> Guardar Permisos
        </button>
    </div>
</form>

<?php
function permStyle(int $v): string {
    $styles = [
        0 => '',
        1 => 'border-color:var(--crud1);color:var(--crud1);background:color-mix(in srgb,var(--crud1) 8%,transparent);',
        2 => 'border-color:var(--crud2);color:var(--crud2);background:color-mix(in srgb,var(--crud2) 8%,transparent);',
        3 => 'border-color:var(--crud3-bd);color:var(--crud3);background:color-mix(in srgb,var(--crud3-bd) 8%,transparent);',
        4 => 'border-color:var(--color-primary);color:var(--color-primary);background:color-mix(in srgb,var(--color-primary) 8%,transparent);',
    ];
    return $styles[$v] ?? '';
}
?>

<style>
/* Tokens CRUD — colores vivos legibles en los 3 temas (t1/t2/t3) */
:root {
    --crud1: #17a2b8;
    --crud2: #28a745;
    --crud3: #d4a005;
    --crud3-bd: #d4a005;
}
/* Tema 2 (Cyber Dark) y Tema 3 (Porto): tonos más brillantes para contraste */
body.t2, body.t3 {
    --crud1: #39c5dd;
    --crud2: #3fb950;
    --crud3: #e0b341;
    --crud3-bd: #e0b341;
}

details[open] > summary > i.fa-chevron-right { transform: rotate(90deg); }

.perm-select { transition: border-color 0.15s, color 0.15s, background 0.15s; }
</style>

<script>
const CRUD_STYLES = {
    0: '',
    1: 'border-color:var(--crud1);color:var(--crud1);background:color-mix(in srgb,var(--crud1) 8%,transparent)',
    2: 'border-color:var(--crud2);color:var(--crud2);background:color-mix(in srgb,var(--crud2) 8%,transparent)',
    3: 'border-color:var(--crud3-bd);color:var(--crud3);background:color-mix(in srgb,var(--crud3-bd) 8%,transparent)',
    4: 'border-color:var(--color-primary);color:var(--color-primary);background:color-mix(in srgb,var(--color-primary) 8%,transparent)',
};

function applyStyle(sel) {
    sel.style.cssText = CRUD_STYLES[parseInt(sel.value)] || '';
    sel.style.width   = sel.style.width || '105px';
    sel.style.fontSize = 'var(--font-size-xs)';
}

function handleSelect(sel) {
    applyStyle(sel);
    updateProgress();
}

function setAll(val) {
    document.querySelectorAll('.perm-select').forEach(s => {
        s.value = val;
        applyStyle(s);
    });
    updateProgress();
}

function cascadeModule(sel, modId) {
    const val = parseInt(sel.value);
    document.querySelectorAll(`.perm-select[data-mod="${modId}"]`).forEach(s => {
        if (s !== sel) { s.value = val; applyStyle(s); }
    });
    updateProgress();
}

function cascadeArea(sel, modId, opId) {
    const val = parseInt(sel.value);
    document.querySelectorAll(`.perm-select[data-mod="${modId}"][data-op="${opId}"]`).forEach(s => {
        if (s !== sel) { s.value = val; applyStyle(s); }
    });
    updateProgress();
}

function updateProgress() {
    const all   = document.querySelectorAll('.perm-select');
    const total = all.length;
    const conf  = [...all].filter(s => parseInt(s.value) > 0).length;
    const pct   = total > 0 ? Math.round(conf / total * 100) : 0;

    const countEl = document.getElementById('perm-count');
    const barEl   = document.getElementById('perm-bar');
    if (countEl) countEl.textContent = conf;
    if (barEl)   barEl.style.width   = pct + '%';

    // Per-module badges
    document.querySelectorAll('.perm-module').forEach(details => {
        const modId = details.dataset.mod;
        const mods  = details.querySelectorAll('.perm-select');
        const mc    = [...mods].filter(s => parseInt(s.value) > 0).length;
        const badge = document.getElementById('mod-badge-' + modId);
        const bar   = document.getElementById('mod-bar-' + modId);
        if (badge) badge.textContent = mc + '/' + mods.length;
        if (bar)   bar.style.width   = (mods.length > 0 ? Math.round(mc/mods.length*100) : 0) + '%';
    });
}
</script>
