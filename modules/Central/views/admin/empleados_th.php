<?php
/**
 * Nuevo Usuario — SIEMPRE desde un empleado de Talento Humano. No existe
 * creación manual: cédula, nombre y correo institucional se leen en vivo
 * de Talento_Humano.dbo.th_empleados (misma fuente que vw_Usuarios_Identidad).
 */
$initialsOf = function (string $n): string {
    $words = array_slice(explode(' ', trim($n)), 0, 2);
    return mb_strtoupper(implode('', array_map(fn($w) => mb_substr($w, 0, 1), $words)));
};
$avatarColors = ['#0056b3','#28a745','#17a2b8','#6f42c1','#dc3545','#fd7e14','#20c997'];
?>
<style>
.nu-wrap {
    --g-bg: var(--surface-app); --g-bg-soft: var(--accent-app); --g-bd: var(--border-app);
    --g-shadow: var(--shadow-app);
}
.nu-wrap .gx {
    background: var(--g-bg); border: 1px solid var(--g-bd);
    border-radius: var(--radius-lg); box-shadow: var(--g-shadow);
}
.nu-hero {
    display: flex; align-items: center; justify-content: space-between;
    gap: var(--sp-4); flex-wrap: wrap; margin-bottom: var(--sp-5);
}
.nu-eyebrow {
    font-size: var(--font-size-xs); font-weight: var(--font-weight-bold);
    letter-spacing: .14em; text-transform: uppercase; color: var(--color-primary);
    display: flex; align-items: center; gap: var(--sp-2); margin-bottom: var(--sp-1);
}
.nu-eyebrow::before { content:''; width:22px; height:2px; background:var(--color-primary); border-radius:2px; }
.nu-search { position: relative; max-width: 420px; }
.nu-search i { position:absolute; left:var(--sp-3); top:50%; transform:translateY(-50%); color:var(--color-text-muted); font-size:var(--font-size-xs); }
.nu-search input {
    width:100%; padding:var(--sp-2) var(--sp-3) var(--sp-2) 2rem; border-radius:var(--radius-md);
    border:1px solid var(--g-bd); background:var(--g-bg-soft); color:var(--color-text);
}
.nu-search input:focus { border-color:var(--color-primary); box-shadow:0 0 0 3px color-mix(in srgb,var(--color-primary) 18%,transparent); outline:none; }

.emp-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(280px,1fr)); gap:var(--sp-3); margin-top:var(--sp-4); }
.emp-card {
    display:flex; align-items:center; gap:var(--sp-3); padding:var(--sp-3) var(--sp-4);
    border-radius:var(--radius-lg); transition:all var(--transition-fast); animation: nuIn .3s ease both;
}
.emp-card:hover { border-color:color-mix(in srgb,var(--color-primary) 40%,var(--g-bd)); transform:translateY(-1px); box-shadow:0 6px 18px rgba(0,0,0,.1); }
@keyframes nuIn { from{opacity:0;transform:translateY(6px);} to{opacity:1;transform:none;} }
.emp-avatar {
    width:44px; height:44px; border-radius:var(--radius-full); flex-shrink:0;
    display:flex; align-items:center; justify-content:center; color:#fff;
    font-weight:var(--font-weight-bold); font-size:var(--font-size-sm);
}
.emp-info { flex:1; min-width:0; }
.emp-name { font-weight:var(--font-weight-semibold); font-size:var(--font-size-sm); color:var(--color-text); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.emp-meta { font-size:var(--font-size-xs); color:var(--color-text-muted); margin-top:2px; display:flex; align-items:center; gap:6px; flex-wrap:wrap; }
.emp-chip {
    display:inline-flex; align-items:center; gap:4px; font-family:var(--font-mono); font-size:10.5px;
    background:var(--g-bg-soft); border:1px solid var(--g-bd); padding:1px 7px; border-radius:var(--radius-full);
    color:var(--color-text-muted); max-width:150px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;
}
.emp-action {
    flex-shrink:0; width:36px; height:36px; border-radius:var(--radius-md); display:flex; align-items:center; justify-content:center;
    background:color-mix(in srgb,var(--color-primary) 10%,transparent); color:var(--color-primary);
    border:1px solid color-mix(in srgb,var(--color-primary) 25%,transparent); transition:all var(--transition-fast);
}
.emp-card:hover .emp-action { background:var(--color-primary); color:#fff; }

.nu-pager { display:flex; align-items:center; justify-content:center; gap:var(--sp-2); margin-top:var(--sp-5); }
.nu-pager a, .nu-pager span {
    display:inline-flex; align-items:center; justify-content:center; min-width:34px; height:34px; padding:0 8px;
    border-radius:var(--radius-md); border:1px solid var(--g-bd); color:var(--color-text); text-decoration:none;
    font-size:var(--font-size-sm); font-family:var(--font-mono);
}
.nu-pager a:hover { border-color:var(--color-primary); color:var(--color-primary); }
.nu-pager .cur { background:var(--color-primary); color:#fff; border-color:var(--color-primary); font-weight:var(--font-weight-bold); }
</style>

<div class="nu-wrap">

<div style="display:flex;align-items:center;gap:var(--sp-3);margin-bottom:var(--sp-2);">
    <a href="<?= APP_URL ?>/admin/usuarios" class="btn btn-ghost btn-sm" data-spa>
        <i class="fa-solid fa-arrow-left"></i> Usuarios
    </a>
</div>

<div class="nu-hero">
    <div>
        <div class="nu-eyebrow">Administración · Nuevo Usuario</div>
        <h2 class="page-title" style="margin:0;">Elegir empleado de Talento Humano</h2>
        <p class="page-subtitle" style="margin-top:4px;">
            Toda cuenta nueva se crea a partir de un empleado activo en TH — cédula, nombre
            y correo se leen en vivo, nunca se digitan a mano.
        </p>
    </div>
    <div class="nu-search">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input type="text" id="emp-q" placeholder="Buscar por nombre o cédula…"
               value="<?= htmlspecialchars($buscar, ENT_QUOTES, 'UTF-8') ?>"
               onkeydown="if(event.key==='Enter'){goSearch(this.value)}">
    </div>
</div>

<?php if (empty($empleados)): ?>
<div class="gx" style="padding:var(--sp-8);text-align:center;color:var(--color-text-muted);">
    <i class="fa-solid fa-user-check" style="font-size:1.8rem;opacity:.35;display:block;margin-bottom:var(--sp-2);"></i>
    <?= $buscar !== ''
        ? 'Sin resultados para "' . htmlspecialchars($buscar, ENT_QUOTES, 'UTF-8') . '".'
        : 'Todos los empleados activos de Talento Humano ya tienen cuenta.' ?>
</div>
<?php else: ?>

<div style="font-size:var(--font-size-xs);color:var(--color-text-muted);margin-top:var(--sp-4);">
    <?= (int)$total ?> empleado<?= $total === 1 ? '' : 's' ?> sin cuenta<?= $buscar !== '' ? ' — filtrado' : '' ?>
    · página <?= (int)$page ?> de <?= (int)$totalPages ?>
</div>

<div class="emp-grid">
    <?php foreach ($empleados as $i => $e):
        $color = $avatarColors[$i % count($avatarColors)];
    ?>
    <a href="<?= APP_URL ?>/admin/usuarios/desde-th/<?= (int)$e['empleado_id'] ?>/nuevo" class="gx emp-card" data-spa>
        <div class="emp-avatar" style="background:<?= $color ?>;">
            <?= htmlspecialchars($initialsOf($e['nombre_completo']), ENT_QUOTES, 'UTF-8') ?>
        </div>
        <div class="emp-info">
            <div class="emp-name"><?= htmlspecialchars($e['nombre_completo'], ENT_QUOTES, 'UTF-8') ?></div>
            <div class="emp-meta">
                <span class="emp-chip"><i class="fa-solid fa-id-card"></i><?= htmlspecialchars($e['cedula'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
                <?php if (!empty($e['nombre_unidad'])): ?>
                <span class="emp-chip" title="<?= htmlspecialchars($e['nombre_unidad'], ENT_QUOTES, 'UTF-8') ?>">
                    <i class="fa-solid fa-sitemap"></i><?= htmlspecialchars($e['nombre_unidad'], ENT_QUOTES, 'UTF-8') ?>
                </span>
                <?php endif; ?>
            </div>
        </div>
        <div class="emp-action"><i class="fa-solid fa-arrow-right"></i></div>
    </a>
    <?php endforeach; ?>
</div>

<?php if ($totalPages > 1): ?>
<div class="nu-pager">
    <?php
    $qs = $buscar !== '' ? '&q=' . urlencode($buscar) : '';
    if ($page > 1): ?>
    <a href="?pagina=<?= $page - 1 ?><?= $qs ?>" data-spa><i class="fa-solid fa-chevron-left"></i></a>
    <?php endif;
    for ($p = max(1, $page - 2); $p <= min($totalPages, $page + 2); $p++): ?>
        <?php if ($p === $page): ?>
        <span class="cur"><?= $p ?></span>
        <?php else: ?>
        <a href="?pagina=<?= $p ?><?= $qs ?>" data-spa><?= $p ?></a>
        <?php endif; ?>
    <?php endfor;
    if ($page < $totalPages): ?>
    <a href="?pagina=<?= $page + 1 ?><?= $qs ?>" data-spa><i class="fa-solid fa-chevron-right"></i></a>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php endif; ?>
</div>

<script>
function goSearch(q) {
    const url = new URL(window.location.href);
    url.searchParams.set('q', q);
    url.searchParams.delete('pagina');
    window.location.href = url.toString();
}
</script>
