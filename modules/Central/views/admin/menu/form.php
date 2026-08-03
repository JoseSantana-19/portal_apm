<?php
/**
 * Formulario de nodo MOIS — crear / editar.
 *
 * MODO CREAR: asistente guiado en 3 pasos.
 *   Paso 1 — ¿Qué tipo de elemento? (Módulo / Opción / Pantalla / Acción)
 *   Paso 2 — ¿Dónde va? (coordenadas con padres existentes clicables,
 *            sugerencia de siguiente número libre y aviso de duplicados)
 *   Paso 3 — ¿Cómo se muestra? (descripción, URL, ícono, orden)
 *
 * MODO EDITAR: coordenadas fijas (solo lectura), metadatos editables.
 *
 * Compatible con los 3 temas (light / dark / corporate) vía tokens locales.
 */
$isEdit = !empty($nodo);
$v = fn(string $k, $d = '') => htmlspecialchars((string)($oldInput[$k] ?? ($nodo[$k] ?? $d)), ENT_QUOTES, 'UTF-8');

$action = $isEdit
    ? APP_URL . '/admin/menu/' . (int)$nodo['id_nodo']
    : APP_URL . '/admin/menu';

/* Coordenadas pre-cargadas desde el árbol (botones "+") o desde old input */
$preM = (int)($_GET['mod'] ?? ($oldInput['id_modulo'] ?? ($nodo['id_modulo'] ?? 0)));
$preO = (int)($_GET['op']  ?? ($oldInput['opcion']    ?? ($nodo['opcion']    ?? 0)));
$preI = (int)($_GET['it']  ?? ($oldInput['items']     ?? ($nodo['items']     ?? 0)));
$preS = (int)($oldInput['subitems'] ?? ($nodo['subitems'] ?? 0));

/* Tipo inicial deducido del botón "+" que trajo al usuario:
   "+ en módulo" → crear Opción · "+ en opción" → crear Pantalla · "+ en pantalla" → crear Acción */
if     (isset($_GET['it']))  $preTipo = 4;
elseif (isset($_GET['op']))  $preTipo = 3;
elseif (isset($_GET['mod'])) $preTipo = 2;
else                         $preTipo = 3;   // lo más habitual es crear una pantalla

$iconActual  = htmlspecialchars((string)($oldInput['icono'] ?? ($nodo['icono'] ?? 'fa-circle')), ENT_QUOTES, 'UTF-8');
$modulesJson = json_encode(array_map(fn($m) => $m['label'], $modules), JSON_UNESCAPED_UNICODE);

/* Nodos existentes en formato compacto [m,o,i,s,"desc"] para JS:
   permiten mostrar padres existentes, sugerir números libres y detectar duplicados. */
$nodosJson = json_encode(array_map(
    fn($n) => [(int)$n['id_modulo'], (int)$n['opcion'], (int)$n['items'], (int)$n['subitems'], (string)$n['descripcion']],
    $nodos ?? []
), JSON_UNESCAPED_UNICODE);
?>
<style>
/* ════ MOIS form — tokens por tema ════ */
.mois-f {
    /* Tokens derivados del tema activo (t1/t2/t3) — adaptan solos */
    --g-bg:      var(--surface-app);
    --g-bg-soft: var(--accent-app);
    --g-bd:      var(--border-app);
    --g-blur:    0px;
    --g-shadow:  var(--shadow-app);
    --g-inset:   none;
    --l1: #8b5cf6;  --l2: #3b82f6;  --l3: #22c55e;  --l4: #f59e0b;
    --mesh-a: transparent;
    --mesh-b: transparent;
}
body.t3 .mois-f {
    --g-blur: 16px;
    --mesh-a: rgba(8, 145, 178, .08);
    --mesh-b: rgba(16, 185, 129, .06);
}

.mois-f {
    margin: calc(-1 * var(--sp-5));
    padding: var(--sp-5) var(--sp-6) var(--sp-10);
    min-height: calc(100vh - var(--topbar-height));
    background:
        radial-gradient(ellipse 55% 40% at 10% 0%,  var(--mesh-a) 0%, transparent 60%),
        radial-gradient(ellipse 50% 45% at 90% 90%, var(--mesh-b) 0%, transparent 60%),
        var(--color-bg);
}
.mois-f .gx {
    background: var(--g-bg);
    backdrop-filter: blur(var(--g-blur)) saturate(1.6);
    -webkit-backdrop-filter: blur(var(--g-blur)) saturate(1.6);
    border: 1px solid var(--g-bd);
    box-shadow: var(--g-shadow), var(--g-inset);
    border-radius: var(--radius-lg);
}
.gx-head {
    display: flex; align-items: center; gap: var(--sp-2);
    padding: var(--sp-3) var(--sp-4);
    border-bottom: 1px solid var(--g-bd);
    font-weight: var(--font-weight-semibold);
    font-size: var(--font-size-sm); color: var(--color-text);
}
.gx-head i { color: var(--color-primary); font-size: .8rem; }
.gx-body { padding: var(--sp-4); }

/* número de paso */
.step-n {
    display: inline-flex; align-items: center; justify-content: center;
    width: 22px; height: 22px; border-radius: 50%; flex-shrink: 0;
    background: var(--color-primary); color: #fff;
    font-size: var(--font-size-xs); font-weight: var(--font-weight-bold);
}
.gx-head .gx-hint {
    margin-left: auto; font-weight: var(--font-weight-normal);
    font-size: var(--font-size-xs); color: var(--color-text-muted);
}

/* eyebrow */
.mois-eyebrow {
    font-size: var(--font-size-xs); font-weight: var(--font-weight-bold);
    letter-spacing: .14em; text-transform: uppercase;
    color: var(--color-primary); margin-bottom: var(--sp-1);
    display: flex; align-items: center; gap: var(--sp-2);
}
.mois-eyebrow::before { content: ''; width: 22px; height: 2px; background: var(--color-primary); border-radius: 2px; }

/* banner de contexto (cuando se llega desde un botón "+") */
.ctx-banner {
    display: flex; align-items: center; gap: var(--sp-2); flex-wrap: wrap;
    padding: var(--sp-3) var(--sp-4); margin-bottom: var(--sp-4);
    border-radius: var(--radius-md);
    background: color-mix(in srgb, var(--color-primary) 7%, transparent);
    border: 1px solid color-mix(in srgb, var(--color-primary) 22%, transparent);
    font-size: var(--font-size-sm); color: var(--color-text);
}
.ctx-banner i { color: var(--color-primary); }

/* ── Paso 1: selector de tipo ── */
.tipo-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: var(--sp-2); }
.tipo-card {
    display: flex; flex-direction: column; gap: var(--sp-1);
    padding: var(--sp-3); border-radius: var(--radius-md); cursor: pointer;
    border: 1.5px solid var(--g-bd); background: var(--g-bg-soft);
    transition: all var(--transition-fast); position: relative;
}
.tipo-card:hover { border-color: var(--color-text-muted); transform: translateY(-1px); }
.tipo-card input { position: absolute; opacity: 0; pointer-events: none; }
.tipo-card .tc-ico {
    width: 30px; height: 30px; border-radius: var(--radius-md);
    display: flex; align-items: center; justify-content: center; font-size: .8rem;
}
.tipo-card .tc-name { font-size: var(--font-size-sm); font-weight: var(--font-weight-semibold); color: var(--color-text); }
.tipo-card .tc-desc { font-size: var(--font-size-xs); color: var(--color-text-muted); line-height: 1.35; }
.tipo-card .tc-lvl {
    position: absolute; top: var(--sp-2); right: var(--sp-2);
    font-family: var(--font-mono); font-size: 10px; font-weight: var(--font-weight-bold);
    padding: 1px 6px; border-radius: var(--radius-sm);
}
/* estado seleccionado, coloreado por nivel */
.tipo-card.sel-1 { border-color: var(--l1); background: color-mix(in srgb, var(--l1) 8%, transparent); }
.tipo-card.sel-2 { border-color: var(--l2); background: color-mix(in srgb, var(--l2) 8%, transparent); }
.tipo-card.sel-3 { border-color: var(--l3); background: color-mix(in srgb, var(--l3) 8%, transparent); }
.tipo-card.sel-4 { border-color: var(--l4); background: color-mix(in srgb, var(--l4) 8%, transparent); }

/* ── Paso 2: coordenadas ── */
.coord-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: var(--sp-3); margin-bottom: var(--sp-4); }
.coord-lbl {
    display: flex; align-items: center; gap: 6px;
    font-size: var(--font-size-xs); font-weight: var(--font-weight-semibold);
    color: var(--color-text-muted); margin-bottom: var(--sp-1);
}
.coord-key {
    display: inline-flex; align-items: center; justify-content: center;
    min-width: 24px; height: 17px; border-radius: var(--radius-sm);
    font-family: var(--font-mono); font-size: 10px; font-weight: var(--font-weight-bold);
}
.ck-1 { background: color-mix(in srgb, var(--l1) 14%, transparent); color: var(--l1); }
.ck-2 { background: color-mix(in srgb, var(--l2) 14%, transparent); color: var(--l2); }
.ck-3 { background: color-mix(in srgb, var(--l3) 14%, transparent); color: var(--l3); }
.ck-4 { background: color-mix(in srgb, var(--l4) 16%, transparent); color: var(--l4); }
.coord-ro {
    display: flex; align-items: center; justify-content: center;
    height: 40px; border-radius: var(--radius-md);
    font-family: var(--font-mono); font-size: var(--font-size-lg);
    font-weight: var(--font-weight-bold); font-variant-numeric: tabular-nums;
    background: var(--g-bg-soft); border: 1px solid var(--g-bd);
}
/* campo deshabilitado por el tipo elegido */
.coord-cell.is-off { opacity: .38; pointer-events: none; filter: grayscale(.6); }
.coord-cell.is-off .form-control { background: var(--color-surface-3); }

/* chip "siguiente libre" */
.sug-chip {
    display: inline-flex; align-items: center; gap: 4px; margin-top: 5px;
    padding: 2px 9px; border-radius: var(--radius-full); cursor: pointer;
    border: 1px dashed var(--color-success); color: var(--color-success);
    background: color-mix(in srgb, var(--color-success) 7%, transparent);
    font-size: 10.5px; font-weight: var(--font-weight-semibold);
    font-family: var(--font-family); transition: all var(--transition-fast);
}
.sug-chip:hover { background: color-mix(in srgb, var(--color-success) 15%, transparent); }

/* chips de padres existentes */
.parent-box { margin-bottom: var(--sp-4); }
.parent-box-lbl {
    font-size: var(--font-size-xs); color: var(--color-text-muted);
    margin-bottom: var(--sp-2); display: flex; align-items: center; gap: 5px;
}
.parent-chips { display: flex; flex-wrap: wrap; gap: var(--sp-1); }
.p-chip {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 3px 10px; border-radius: var(--radius-full); cursor: pointer;
    border: 1px solid var(--g-bd); background: var(--g-bg-soft);
    color: var(--color-text); font-size: var(--font-size-xs);
    font-family: var(--font-family); transition: all var(--transition-fast);
}
.p-chip code { font-family: var(--font-mono); font-size: 10px; color: var(--color-text-muted); }
.p-chip:hover { border-color: var(--color-primary); color: var(--color-primary); }
.p-chip.on {
    border-color: var(--color-primary); color: var(--color-primary);
    background: color-mix(in srgb, var(--color-primary) 10%, transparent);
}

/* breadcrumb MOIS en vivo */
.mois-path {
    display: flex; align-items: center; gap: 6px; flex-wrap: wrap;
    padding: var(--sp-3) var(--sp-4);
    background: var(--g-bg-soft); border: 1px solid var(--g-bd);
    border-radius: var(--radius-md);
    font-family: var(--font-mono); font-size: var(--font-size-xs);
    min-height: 42px;
}
.crumb {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 2px 9px; border-radius: var(--radius-full);
    font-weight: var(--font-weight-bold); white-space: nowrap;
}
.crumb-1 { background: color-mix(in srgb, var(--l1) 14%, transparent); color: var(--l1); }
.crumb-2 { background: color-mix(in srgb, var(--l2) 14%, transparent); color: var(--l2); }
.crumb-3 { background: color-mix(in srgb, var(--l3) 14%, transparent); color: var(--l3); }
.crumb-4 { background: color-mix(in srgb, var(--l4) 16%, transparent); color: var(--l4); }
.crumb-sep { font-size: .5rem; color: var(--color-text-light); }
.crumb-mod-label {
    color: var(--color-text-muted); font-size: .65rem;
    max-width: 220px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
}
.lvl-tag {
    margin-left: auto; font-weight: var(--font-weight-bold);
    font-size: var(--font-size-xs); padding: 2px 10px;
    border-radius: var(--radius-full); border: 1px solid currentColor;
}

/* aviso de duplicado */
.dup-warn {
    display: none; align-items: flex-start; gap: var(--sp-2);
    margin-top: var(--sp-3); padding: var(--sp-3) var(--sp-4);
    border-radius: var(--radius-md);
    background: color-mix(in srgb, var(--color-danger) 8%, transparent);
    border: 1px solid color-mix(in srgb, var(--color-danger) 30%, transparent);
    font-size: var(--font-size-sm); color: var(--color-text);
}
.dup-warn.show { display: flex; }
.dup-warn i { color: var(--color-danger); margin-top: 2px; }

/* inputs sobre glass */
.mois-f .form-control {
    background: var(--g-bg-soft);
    border-color: var(--g-bd);
    color: var(--color-text);
}
.mois-f .form-control:focus {
    border-color: var(--color-primary);
    box-shadow: 0 0 0 3px color-mix(in srgb, var(--color-primary) 18%, transparent);
}

/* preview de ícono */
.ico-prev {
    width: 38px; height: 38px; border-radius: var(--radius-md); flex-shrink: 0;
    display: flex; align-items: center; justify-content: center; font-size: .95rem;
    background: color-mix(in srgb, var(--color-primary) 13%, transparent);
    color: var(--color-primary); border: 1px solid color-mix(in srgb, var(--color-primary) 25%, transparent);
    transition: all var(--transition-fast);
}

/* filas de switch */
.t-row {
    display: flex; align-items: center; justify-content: space-between;
    gap: var(--sp-3); padding: var(--sp-3) 0;
}
.t-row + .t-row { border-top: 1px solid var(--g-bd); }
.t-name { font-size: var(--font-size-sm); font-weight: var(--font-weight-medium); color: var(--color-text); }
.t-desc { font-size: var(--font-size-xs); color: var(--color-text-muted); margin-top: 1px; }

.sw { position: relative; display: inline-block; width: 36px; height: 20px; flex-shrink: 0; cursor: pointer; }
.sw input { opacity: 0; width: 0; height: 0; position: absolute; }
.sw-track {
    position: absolute; inset: 0;
    background: var(--color-surface-3); border: 1px solid var(--g-bd);
    border-radius: var(--radius-full); transition: background var(--transition-fast);
}
.sw input:checked ~ .sw-track { background: var(--color-success); border-color: var(--color-success); }
.sw-thumb {
    position: absolute; top: 3px; left: 3px; width: 14px; height: 14px;
    background: #fff; border-radius: 50%; box-shadow: 0 1px 2px rgba(0,0,0,.25);
    pointer-events: none; transition: transform var(--transition-fast);
}
.sw input:checked ~ .sw-thumb { transform: translateX(16px); }

/* guía lateral */
.guide-row { display: flex; align-items: flex-start; gap: var(--sp-2); }
.guide-row + .guide-row { margin-top: var(--sp-2); }
.guide-t { font-size: var(--font-size-xs); font-weight: var(--font-weight-semibold); color: var(--color-text); }
.guide-d { font-size: 10.5px; color: var(--color-text-muted); }

/* layout */
.mois-f-grid {
    display: grid; grid-template-columns: 1fr 300px;
    gap: var(--sp-4); align-items: start;
}
@media (max-width: 880px) {
    .mois-f-grid { grid-template-columns: 1fr; }
    .tipo-grid   { grid-template-columns: repeat(2, 1fr); }
    .coord-grid  { grid-template-columns: repeat(2, 1fr); }
}
</style>

<div class="mois-f">

<!-- migas -->
<div style="display:flex;align-items:center;gap:var(--sp-3);margin-bottom:var(--sp-3);">
    <a href="<?= APP_URL ?>/admin/menu" class="btn btn-ghost btn-sm" data-spa>
        <i class="fa-solid fa-arrow-left"></i> Estructura del Menú
    </a>
    <span style="color:var(--color-text-light);">/</span>
    <span style="color:var(--color-text-muted);font-size:var(--font-size-sm);"><?= $isEdit ? 'Editar nodo' : 'Nuevo nodo' ?></span>
</div>

<div style="margin-bottom:var(--sp-5);">
    <div class="mois-eyebrow">Administración · Navegación</div>
    <h2 style="font-size:var(--font-size-2xl);font-weight:var(--font-weight-bold);letter-spacing:-.02em;margin:0;color:var(--color-text);">
        <?= $isEdit ? 'Editar Nodo de Menú' : 'Agregar al Menú' ?>
    </h2>
    <p style="margin:var(--sp-1) 0 0;color:var(--color-text-muted);font-size:var(--font-size-sm);">
        <?php if ($isEdit): ?>
        La posición en la jerarquía es fija — aquí solo se cambia cómo se ve y se comporta el nodo.
        <?php else: ?>
        Sigue los 3 pasos. El sistema te sugiere la posición libre y avisa si ya está ocupada.
        <?php endif; ?>
    </p>
</div>

<!-- banner de contexto: aparece cuando se llegó con un "+" desde el árbol -->
<?php if (!$isEdit && isset($_GET['mod'])): ?>
<div class="ctx-banner" id="ctx-banner">
    <i class="fa-solid fa-location-dot"></i>
    <span>Estás agregando dentro de:</span>
    <strong id="ctx-path" style="font-weight:var(--font-weight-semibold);">…</strong>
</div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
<script>document.addEventListener('DOMContentLoaded', () => PortalAlert.errorList('Corrige los errores', <?= json_encode(array_values((array)$errors)) ?>));</script>
<?php endif; ?>

<form method="POST" action="<?= $action ?>" id="mois-form">
    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">

    <div class="mois-f-grid">

        <!-- ── Columna principal ── -->
        <div style="display:flex;flex-direction:column;gap:var(--sp-4);">

            <?php if (!$isEdit): ?>
            <!-- ── PASO 1 · tipo de elemento ── -->
            <div class="gx">
                <div class="gx-head">
                    <span class="step-n">1</span> ¿Qué quieres agregar?
                    <span class="gx-hint">elige uno — los campos se ajustan solos</span>
                </div>
                <div class="gx-body">
                    <div class="tipo-grid" id="tipo-grid">
                        <label class="tipo-card" data-tipo="1">
                            <input type="radio" name="_tipo" value="1" <?= $preTipo === 1 ? 'checked' : '' ?>>
                            <span class="tc-lvl ck-1">L1</span>
                            <span class="tc-ico" style="background:color-mix(in srgb,var(--l1) 14%,transparent);color:var(--l1);"><i class="fa-solid fa-building"></i></span>
                            <span class="tc-name">Módulo</span>
                            <span class="tc-desc">Cabecera de una dirección. Normalmente ya existen — rara vez se crea uno nuevo.</span>
                        </label>
                        <label class="tipo-card" data-tipo="2">
                            <input type="radio" name="_tipo" value="2" <?= $preTipo === 2 ? 'checked' : '' ?>>
                            <span class="tc-lvl ck-2">L2</span>
                            <span class="tc-ico" style="background:color-mix(in srgb,var(--l2) 14%,transparent);color:var(--l2);"><i class="fa-solid fa-folder-open"></i></span>
                            <span class="tc-name">Opción</span>
                            <span class="tc-desc">Sección que agrupa pantallas dentro de un módulo. No lleva URL.</span>
                        </label>
                        <label class="tipo-card" data-tipo="3">
                            <input type="radio" name="_tipo" value="3" <?= $preTipo === 3 ? 'checked' : '' ?>>
                            <span class="tc-lvl ck-3">L3</span>
                            <span class="tc-ico" style="background:color-mix(in srgb,var(--l3) 14%,transparent);color:var(--l3);"><i class="fa-solid fa-window-maximize"></i></span>
                            <span class="tc-name">Pantalla</span>
                            <span class="tc-desc">Página real del sistema, con URL. Es el caso más común.</span>
                        </label>
                        <label class="tipo-card" data-tipo="4">
                            <input type="radio" name="_tipo" value="4" <?= $preTipo === 4 ? 'checked' : '' ?>>
                            <span class="tc-lvl ck-4">L4</span>
                            <span class="tc-ico" style="background:color-mix(in srgb,var(--l4) 16%,transparent);color:var(--l4);"><i class="fa-solid fa-bolt"></i></span>
                            <span class="tc-name">Acción</span>
                            <span class="tc-desc">Sub-enlace dentro de una pantalla. Ej.: "Nuevo registro", "Reporte".</span>
                        </label>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- ── PASO 2 · ubicación ── -->
            <div class="gx">
                <div class="gx-head">
                    <?php if (!$isEdit): ?><span class="step-n">2</span> ¿Dónde va ubicado?
                    <?php else: ?><i class="fa-solid fa-sitemap"></i> Posición en la Jerarquía<?php endif; ?>
                    <span id="lvl-badge" class="lvl-tag" style="display:none;"></span>
                </div>
                <div class="gx-body">

                    <?php if ($isEdit): ?>
                    <!-- EDITAR: coordenadas solo lectura -->
                    <div class="coord-grid">
                        <?php
                        $coords = [
                            ['Módulo',   'ck-1', (int)$nodo['id_modulo'], 'var(--l1)'],
                            ['Opción',   'ck-2', (int)$nodo['opcion'],    'var(--l2)'],
                            ['Ítem',     'ck-3', (int)$nodo['items'],     'var(--l3)'],
                            ['Sub-ítem', 'ck-4', (int)$nodo['subitems'],  'var(--l4)'],
                        ];
                        foreach ($coords as $ix => [$cl, $ck, $cv, $cc]): ?>
                        <div>
                            <div class="coord-lbl"><span class="coord-key <?= $ck ?>">L<?= $ix + 1 ?></span><?= $cl ?></div>
                            <div class="coord-ro" style="color:<?= $cc ?>;"><?= $cv ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <input type="hidden" id="f-mod" value="<?= (int)$nodo['id_modulo'] ?>">
                    <input type="hidden" id="f-op"  value="<?= (int)$nodo['opcion'] ?>">
                    <input type="hidden" id="f-it"  value="<?= (int)$nodo['items'] ?>">
                    <input type="hidden" id="f-sub" value="<?= (int)$nodo['subitems'] ?>">

                    <?php else: ?>
                    <!-- CREAR: módulo + padres clicables + número sugerido -->

                    <div class="form-group" style="margin-bottom:var(--sp-4);">
                        <div class="coord-lbl"><span class="coord-key ck-1">L1</span>Módulo (dirección) *</div>
                        <select name="id_modulo" id="f-mod" class="form-control" required onchange="onCoordChange('mod')">
                            <option value="0">— Selecciona la dirección —</option>
                            <?php foreach ($modules as $mid => $mm): ?>
                            <option value="<?= $mid ?>" <?= $preM === $mid ? 'selected' : '' ?>>
                                <?= $mid ?> — <?= htmlspecialchars($mm['label'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- padre nivel 2: opciones existentes del módulo, clicables -->
                    <div class="parent-box" id="box-op" style="display:none;">
                        <div class="parent-box-lbl">
                            <span class="coord-key ck-2">L2</span>
                            <span id="lbl-op">Elige la opción donde irá:</span>
                        </div>
                        <div class="parent-chips" id="chips-op"></div>
                        <span class="sug-chip" id="sug-op" style="display:none;" onclick="useSuggestion('op')"></span>
                    </div>

                    <!-- padre nivel 3: pantallas existentes de la opción, clicables -->
                    <div class="parent-box" id="box-it" style="display:none;">
                        <div class="parent-box-lbl">
                            <span class="coord-key ck-3">L3</span>
                            <span id="lbl-it">Elige la pantalla donde irá:</span>
                        </div>
                        <div class="parent-chips" id="chips-it"></div>
                        <span class="sug-chip" id="sug-it" style="display:none;" onclick="useSuggestion('it')"></span>
                    </div>

                    <!-- número propio del nuevo nodo (sugerido automáticamente, editable) -->
                    <div class="parent-box" id="box-own" style="display:none;">
                        <div class="parent-box-lbl">
                            <span class="coord-key" id="own-key">·</span>
                            <span id="lbl-own">Número del nuevo elemento:</span>
                        </div>
                        <div style="display:flex;align-items:center;gap:var(--sp-2);">
                            <input type="number" id="f-own" class="form-control" min="1" max="999"
                                   style="max-width:110px;font-family:var(--font-mono);" oninput="onCoordChange('own')">
                            <span style="font-size:var(--font-size-xs);color:var(--color-text-muted);" id="own-hint"></span>
                        </div>
                    </div>

                    <!-- los valores reales que viajan al servidor (sincronizados por JS) -->
                    <input type="hidden" name="opcion"   id="f-op"  value="<?= $preO ?>">
                    <input type="hidden" name="items"    id="f-it"  value="<?= $preI ?>">
                    <input type="hidden" name="subitems" id="f-sub" value="<?= $preS ?>">
                    <?php endif; ?>

                    <!-- vista previa de la ruta -->
                    <div class="mois-path" id="mois-path">
                        <span style="color:var(--color-text-light);">Completa los pasos para ver la ruta…</span>
                    </div>

                    <!-- aviso de tupla ocupada -->
                    <div class="dup-warn" id="dup-warn">
                        <i class="fa-solid fa-circle-exclamation"></i>
                        <div>
                            <strong>Esta posición ya está ocupada</strong>
                            <div style="font-size:var(--font-size-xs);color:var(--color-text-muted);margin-top:2px;" id="dup-desc"></div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- ── PASO 3 · apariencia ── -->
            <div class="gx">
                <div class="gx-head">
                    <?php if (!$isEdit): ?><span class="step-n">3</span> ¿Cómo se muestra?
                    <?php else: ?><i class="fa-solid fa-pen-nib"></i> Metadatos del Nodo<?php endif; ?>
                </div>
                <div class="gx-body" style="display:flex;flex-direction:column;gap:var(--sp-4);">

                    <div class="form-group" style="margin:0;">
                        <label class="form-label">Nombre visible *</label>
                        <input type="text" name="descripcion" class="form-control" required maxlength="200"
                               value="<?= $v('descripcion') ?>"
                               placeholder="Ej: Gestión de Usuarios, Reportes Financieros…">
                        <span class="form-help">Es el texto que verán los usuarios en el menú lateral</span>
                    </div>

                    <div class="form-group" style="margin:0;" id="grp-url">
                        <label class="form-label">Ruta URL <span id="url-req" style="color:var(--color-text-muted);font-weight:normal;"></span></label>
                        <input type="text" name="url_ruta" id="f-url" class="form-control" maxlength="150"
                               value="<?= $v('url_ruta') ?>" style="font-family:var(--font-mono);font-size:var(--font-size-sm);"
                               placeholder="/modulo/seccion">
                        <span class="form-help" id="url-help">A dónde lleva el clic. Las cabeceras (Módulo y Opción) no llevan URL.</span>
                    </div>

                    <div style="display:grid;grid-template-columns:1fr 140px;gap:var(--sp-4);">
                        <div class="form-group" style="margin:0;">
                            <label class="form-label">Ícono <span style="color:var(--color-text-muted);font-weight:normal;">(FontAwesome)</span></label>
                            <div style="display:flex;align-items:center;gap:var(--sp-2);">
                                <div class="ico-prev"><i id="ico-el" class="fa-solid <?= $iconActual ?>"></i></div>
                                <input type="text" name="icono" id="ico-in" class="form-control"
                                       value="<?= $iconActual ?>" placeholder="fa-chart-bar"
                                       oninput="icoPreview(this.value)"
                                       style="font-family:var(--font-mono);font-size:var(--font-size-sm);">
                            </div>
                            <span class="form-help"><code>fa-users</code> · <code>fa-file-invoice</code> · <code>fa-gears</code></span>
                        </div>
                        <div class="form-group" style="margin:0;">
                            <label class="form-label">Orden</label>
                            <input type="number" name="orden" class="form-control" min="0" max="9999"
                                   value="<?= $v('orden', '0') ?>">
                            <span class="form-help">Menor = primero</span>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <!-- ── Columna lateral ── -->
        <div style="display:flex;flex-direction:column;gap:var(--sp-4);">

            <div class="gx">
                <div class="gx-head"><i class="fa-solid fa-sliders"></i> Opciones</div>
                <div class="gx-body" style="padding-top:var(--sp-1);padding-bottom:var(--sp-1);">

                    <?php if ($isEdit): ?>
                    <div class="t-row">
                        <div>
                            <div class="t-name">Estado</div>
                            <div class="t-desc">Visible en el menú</div>
                        </div>
                        <label class="sw">
                            <!-- hidden = 0: si el checkbox queda sin marcar igual viaja "0" al servidor -->
                            <input type="hidden" name="estado" value="0">
                            <input type="checkbox" name="estado" value="1"
                                   <?= (int)($oldInput['estado'] ?? $nodo['estado'] ?? 1) ? 'checked' : '' ?>>
                            <span class="sw-track"></span><span class="sw-thumb"></span>
                        </label>
                    </div>
                    <?php endif; ?>

                    <div class="t-row">
                        <div>
                            <div class="t-name"><i class="fa-solid fa-shield-halved" style="color:var(--color-warning);font-size:.7rem;margin-right:4px;"></i>Requiere MFA</div>
                            <div class="t-desc">Pide doble factor al entrar</div>
                        </div>
                        <label class="sw">
                            <input type="hidden" name="requiere_mfa" value="0">
                            <input type="checkbox" name="requiere_mfa" value="1"
                                   <?= (int)($oldInput['requiere_mfa'] ?? $nodo['requiere_mfa'] ?? 0) ? 'checked' : '' ?>>
                            <span class="sw-track"></span><span class="sw-thumb"></span>
                        </label>
                    </div>

                    <div class="t-row">
                        <div>
                            <div class="t-name"><i class="fa-solid fa-bolt" style="color:var(--color-info);font-size:.7rem;margin-right:4px;"></i>SPA / AJAX</div>
                            <div class="t-desc">Abre sin recargar la página</div>
                        </div>
                        <label class="sw">
                            <input type="hidden" name="target_spa" value="0">
                            <input type="checkbox" name="target_spa" value="1"
                                   <?= (int)($oldInput['target_spa'] ?? $nodo['target_spa'] ?? 1) ? 'checked' : '' ?>>
                            <span class="sw-track"></span><span class="sw-thumb"></span>
                        </label>
                    </div>

                </div>
            </div>

            <div class="gx">
                <div class="gx-head"><i class="fa-solid fa-circle-info"></i> ¿Cómo se organiza el menú?</div>
                <div class="gx-body">
                    <p style="font-size:var(--font-size-xs);color:var(--color-text-muted);margin:0 0 var(--sp-3);line-height:1.5;">
                        El menú es un árbol de 4 niveles. Cada elemento "vive" dentro del anterior:
                    </p>
                    <div class="guide-row">
                        <span class="coord-key ck-1" style="margin-top:1px;">L1</span>
                        <div><div class="guide-t">Módulo</div><div class="guide-d">La dirección o gerencia. Ej.: "Dirección Financiera".</div></div>
                    </div>
                    <div class="guide-row">
                        <span class="coord-key ck-2" style="margin-top:1px;">L2</span>
                        <div><div class="guide-t">Opción</div><div class="guide-d">Una carpeta dentro del módulo. Ej.: "Presupuesto".</div></div>
                    </div>
                    <div class="guide-row">
                        <span class="coord-key ck-3" style="margin-top:1px;">L3</span>
                        <div><div class="guide-t">Pantalla</div><div class="guide-d">Una página con URL. Ej.: "Consultar partidas".</div></div>
                    </div>
                    <div class="guide-row">
                        <span class="coord-key ck-4" style="margin-top:1px;">L4</span>
                        <div><div class="guide-t">Acción</div><div class="guide-d">Atajo dentro de la pantalla. Ej.: "Nueva partida".</div></div>
                    </div>
                </div>
            </div>

            <div style="display:flex;flex-direction:column;gap:var(--sp-2);">
                <button type="submit" class="btn btn-primary" id="btn-save" style="justify-content:center;">
                    <i class="fa-solid fa-floppy-disk"></i> <?= $isEdit ? 'Guardar Cambios' : 'Crear Nodo' ?>
                </button>
                <a href="<?= APP_URL ?>/admin/menu" class="btn btn-ghost" data-spa style="justify-content:center;">Cancelar</a>
            </div>
        </div>

    </div>
</form>

</div><!-- .mois-f -->

<script>
(function () {
    'use strict';

    /* ── Datos del servidor ────────────────────────────────
       MODS:  { id: "nombre del módulo" }
       NODOS: [[mod, opcion, items, subitems, "descripción"], …]  (todos los nodos, activos e inactivos) */
    const MODS    = <?= $modulesJson ?>;
    const NODOS   = <?= $nodosJson ?>;
    const IS_EDIT = <?= $isEdit ? 'true' : 'false' ?>;

    /* Configuración por tipo:
       parents = qué niveles padre hay que elegir · own = qué coordenada recibe el número nuevo */
    const TIPOS = {
        1: { parents: [],           own: null,  ownLabel: '',                                lvl: 'L1 · Módulo',   cssVar: '--l1', urlHint: 'Las cabeceras de módulo no llevan URL.' },
        2: { parents: [],           own: 'op',  ownLabel: 'Número de la nueva opción:',     lvl: 'L2 · Opción',   cssVar: '--l2', urlHint: 'Las opciones agrupan pantallas — normalmente sin URL.' },
        3: { parents: ['op'],       own: 'it',  ownLabel: 'Número de la nueva pantalla:',   lvl: 'L3 · Pantalla', cssVar: '--l3', urlHint: 'Obligatoria en la práctica: es la página que se abre.' },
        4: { parents: ['op', 'it'], own: 'sub', ownLabel: 'Número de la nueva acción:',     lvl: 'L4 · Acción',   cssVar: '--l4', urlHint: 'Lleva la URL de la sub-acción.' },
    };

    let tipo = <?= (int)$preTipo ?>;

    /* ── helpers de datos ── */
    const $id = (x) => document.getElementById(x);
    const val = (x) => parseInt($id(x)?.value || 0) || 0;

    // Busca un nodo exacto por tupla
    function findNodo(m, o, i, s) {
        return NODOS.find(n => n[0] === m && n[1] === o && n[2] === i && n[3] === s);
    }
    // Opciones (L2) existentes de un módulo
    function opcionesDe(m) {
        return NODOS.filter(n => n[0] === m && n[1] > 0 && n[2] === 0 && n[3] === 0);
    }
    // Pantallas (L3) existentes de una opción
    function itemsDe(m, o) {
        return NODOS.filter(n => n[0] === m && n[1] === o && n[2] > 0 && n[3] === 0);
    }
    // Siguiente número libre para la coordenada indicada
    function siguienteLibre(coord) {
        const m = val('f-mod'), o = val('f-op'), i = val('f-it');
        let usados;
        if (coord === 'op')  usados = NODOS.filter(n => n[0] === m && n[2] === 0 && n[3] === 0).map(n => n[1]);
        if (coord === 'it')  usados = NODOS.filter(n => n[0] === m && n[1] === o && n[3] === 0).map(n => n[2]);
        if (coord === 'sub') usados = NODOS.filter(n => n[0] === m && n[1] === o && n[2] === i).map(n => n[3]);
        let k = 1;
        while (usados.includes(k)) k++;
        return k;
    }

    /* ── Paso 1: selector de tipo ── */
    function pintarTipos() {
        document.querySelectorAll('.tipo-card').forEach(c => {
            const t = parseInt(c.dataset.tipo);
            c.classList.remove('sel-1', 'sel-2', 'sel-3', 'sel-4');
            if (t === tipo) c.classList.add('sel-' + t);
        });
    }
    document.querySelectorAll('.tipo-card input').forEach(r => {
        r.addEventListener('change', () => {
            tipo = parseInt(r.value);
            // al cambiar de tipo se limpian las coordenadas que ya no aplican
            if (tipo <= 2) { $id('f-op').value = 0; }
            if (tipo <= 3) { $id('f-it').value = 0; }
            $id('f-sub').value = 0;
            pintarTipos();
            refrescar();
        });
    });

    /* ── Paso 2: chips de padres + número propio ── */
    function renderChips(boxId, chipsId, lista, coordHidden, actual) {
        const box = $id(boxId), wrap = $id(chipsId);
        if (!box || !wrap) return;
        wrap.innerHTML = '';
        lista.forEach(n => {
            const num = coordHidden === 'f-op' ? n[1] : n[2];
            const b = document.createElement('button');
            b.type = 'button';
            b.className = 'p-chip' + (num === actual ? ' on' : '');
            b.innerHTML = `<code>${num}</code> ${n[4]}`;
            b.onclick = () => { $id(coordHidden).value = num; refrescar(); };
            wrap.appendChild(b);
        });
        if (lista.length === 0) {
            wrap.innerHTML = '<span style="font-size:var(--font-size-xs);color:var(--color-text-light);font-style:italic;">No hay elementos todavía en este nivel — crea primero el padre.</span>';
        }
    }

    /* ── refresco general de la UI según tipo + coordenadas ── */
    function refrescar() {
        if (IS_EDIT) { pintarRuta(); return; }

        const cfg = TIPOS[tipo];
        const m = val('f-mod');

        // Paso 2a: módulo elegido → mostrar (o no) selección de padres
        const needOp = cfg.parents.includes('op') || cfg.own === 'op';
        const needIt = cfg.parents.includes('it') || cfg.own === 'it';

        // caja de opciones (L2): visible si el tipo necesita elegir opción padre
        const showOpChips = m > 0 && cfg.parents.includes('op');
        $id('box-op').style.display = showOpChips ? '' : 'none';
        if (showOpChips) renderChips('box-op', 'chips-op', opcionesDe(m), 'f-op', val('f-op'));

        // caja de pantallas (L3): visible para tipo 4 una vez elegida la opción
        const showItChips = m > 0 && cfg.parents.includes('it') && val('f-op') > 0;
        $id('box-it').style.display = showItChips ? '' : 'none';
        if (showItChips) renderChips('box-it', 'chips-it', itemsDe(m, val('f-op')), 'f-it', val('f-it'));

        // caja del número propio: visible cuando los padres ya están elegidos
        const parentsOk = m > 0
            && (!cfg.parents.includes('op') || val('f-op') > 0)
            && (!cfg.parents.includes('it') || val('f-it') > 0);
        const showOwn = parentsOk && cfg.own !== null;
        $id('box-own').style.display = showOwn ? '' : 'none';

        if (showOwn) {
            $id('lbl-own').textContent = cfg.ownLabel;
            const keyEl = $id('own-key');
            keyEl.className = 'coord-key ck-' + tipo;
            keyEl.textContent = 'L' + tipo;

            // sugerir el siguiente libre si el campo está vacío o quedó del tipo anterior
            const hidden = cfg.own === 'op' ? 'f-op' : cfg.own === 'it' ? 'f-it' : 'f-sub';
            let cur = val(hidden);
            if (cur === 0) {
                cur = siguienteLibre(cfg.own);
                $id(hidden).value = cur;
            }
            $id('f-own').value = cur;
            $id('own-hint').textContent = 'Sugerido automáticamente — puedes cambiarlo.';
        }

        // ayuda de URL contextual al tipo
        $id('url-help').textContent = cfg.urlHint;
        $id('url-req').textContent = (tipo >= 3) ? '(recomendada)' : '(no aplica)';

        pintarRuta();
        avisarDuplicado();
        pintarContexto();
    }

    // El usuario edita el número propio a mano → sincronizar con el hidden real
    window.onCoordChange = function (which) {
        if (which === 'own') {
            const cfg = TIPOS[tipo];
            const hidden = cfg.own === 'op' ? 'f-op' : cfg.own === 'it' ? 'f-it' : 'f-sub';
            $id(hidden).value = val('f-own');
            pintarRuta();
            avisarDuplicado();
            return;
        }
        if (which === 'mod') {
            // cambiar de módulo invalida los padres elegidos
            $id('f-op').value = 0;
            $id('f-it').value = 0;
            $id('f-sub').value = 0;
        }
        refrescar();
    };

    window.useSuggestion = function (coord) {
        const hidden = coord === 'op' ? 'f-op' : 'f-it';
        $id(hidden).value = siguienteLibre(coord);
        refrescar();
    };

    /* ── vista previa de ruta ── */
    function pintarRuta() {
        const m = val('f-mod'), o = val('f-op'), i = val('f-it'), s = val('f-sub');
        const path = $id('mois-path'), badge = $id('lvl-badge');
        if (!path) return;

        if (!m) {
            path.innerHTML = '<span style="color:var(--color-text-light);">Completa los pasos para ver la ruta…</span>';
            if (badge) badge.style.display = 'none';
            return;
        }

        const sep = '<i class="fa-solid fa-chevron-right crumb-sep"></i>';
        // si el padre existe, mostramos su nombre real en vez del número pelado
        const nombreDe = (mm, oo, ii, ss, fallback) => {
            const n = findNodo(mm, oo, ii, ss);
            return n ? n[4] : fallback;
        };

        let html = `<span class="crumb crumb-1">M${m}</span>`
                 + `<span class="crumb-mod-label">${MODS[m] || 'Módulo ' + m}</span>`;
        let lbl = 'L1 · Módulo', cssVar = '--l1';

        if (o > 0) {
            html += `${sep}<span class="crumb crumb-2" title="${nombreDe(m, o, 0, 0, '')}">O${o}</span>`;
            const nm = findNodo(m, o, 0, 0);
            if (nm) html += `<span class="crumb-mod-label">${nm[4]}</span>`;
            lbl = 'L2 · Opción'; cssVar = '--l2';
        }
        if (o > 0 && i > 0) {
            html += `${sep}<span class="crumb crumb-3">I${i}</span>`;
            const nm = findNodo(m, o, i, 0);
            if (nm) html += `<span class="crumb-mod-label">${nm[4]}</span>`;
            lbl = 'L3 · Pantalla'; cssVar = '--l3';
        }
        if (o > 0 && i > 0 && s > 0) {
            html += `${sep}<span class="crumb crumb-4">S${s}</span>`;
            lbl = 'L4 · Acción'; cssVar = '--l4';
        }

        const color = getComputedStyle(document.querySelector('.mois-f')).getPropertyValue(cssVar).trim();
        html += `<span class="lvl-tag" style="color:${color};">${lbl}</span>`;
        path.innerHTML = html;

        if (badge) {
            badge.textContent = lbl;
            badge.style.color = color;
            badge.style.display = '';
        }
    }

    /* ── aviso de duplicado: la tupla ya existe ── */
    function avisarDuplicado() {
        if (IS_EDIT) return;
        const warn = $id('dup-warn'), btn = $id('btn-save');
        const m = val('f-mod'), o = val('f-op'), i = val('f-it'), s = val('f-sub');
        const dup = m > 0 ? findNodo(m, o, i, s) : null;
        warn.classList.toggle('show', !!dup);
        if (dup) {
            $id('dup-desc').textContent =
                `Ya existe "${dup[4]}" en la posición ${m}·${o}·${i}·${s}. Cambia el número o edita ese nodo.`;
        }
        // se bloquea el guardado mientras haya duplicado
        btn.disabled = !!dup;
        btn.style.opacity = dup ? '.55' : '';
        btn.style.cursor = dup ? 'not-allowed' : '';
    }

    /* ── banner de contexto (llegada vía "+") ── */
    function pintarContexto() {
        const el = $id('ctx-path');
        if (!el) return;
        const m = val('f-mod'), o = val('f-op'), i = val('f-it');
        let parts = [MODS[m] || ('Módulo ' + m)];
        const nOp = o > 0 ? findNodo(m, o, 0, 0) : null;
        const nIt = (o > 0 && i > 0) ? findNodo(m, o, i, 0) : null;
        if (nOp) parts.push(nOp[4]);
        if (nIt) parts.push(nIt[4]);
        el.textContent = parts.join(' › ');
    }

    /* ── preview de ícono ── */
    window.icoPreview = function (v) {
        const el = $id('ico-el');
        if (el) el.className = 'fa-solid ' + (v.trim() || 'fa-circle');
    };

    /* init */
    pintarTipos();
    refrescar();
})();
</script>
