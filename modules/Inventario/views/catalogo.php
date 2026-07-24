<?php
$paleta = InventarioController::paleta();
?>
<style>
.cat-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(260px,1fr)); gap:var(--sp-4); }
.cat-card { border-radius:var(--radius-lg,12px); overflow:hidden; background:var(--surface-app,var(--color-surface,#fff)); border:1px solid var(--border-app,var(--color-border)); transition:transform .15s, box-shadow .15s; }
.cat-card:hover { transform:translateY(-3px); box-shadow:0 10px 28px rgba(0,0,0,.12); }
.cat-top { padding:var(--sp-5); color:#fff; position:relative; }
.cat-top i.bg { position:absolute; right:14px; top:10px; font-size:3.2rem; opacity:.18; }
.cat-body { padding:var(--sp-4) var(--sp-5); }
.cat-metric { display:flex; justify-content:space-between; padding:6px 0; font-size:.85rem; border-bottom:1px dashed var(--border-app,var(--color-border)); }
.cat-metric:last-child { border-bottom:none; }
</style>

<div style="animation:pageFadeIn .3s ease both;">
<div class="page-header" style="margin-bottom:var(--sp-4);">
    <div>
        <h2 class="page-title"><i class="fa-solid fa-grip" style="color:#fd7e14;margin-right:var(--sp-2);"></i> Catálogo de Ítems</h2>
        <p class="page-subtitle">Resumen del inventario agrupado por categoría</p>
    </div>
    <a href="<?= APP_URL ?>/inventario" class="btn btn-outline btn-sm" data-spa><i class="fa-solid fa-table-list"></i> Ver tabla</a>
</div>

<form method="GET" action="<?= APP_URL ?>/inventario/catalogo" style="margin-bottom:var(--sp-4);display:flex;gap:var(--sp-2);max-width:420px;">
    <input type="text" name="termino" value="<?= htmlspecialchars($termino, ENT_QUOTES, 'UTF-8') ?>" class="form-control" placeholder="Buscar categoría o bien…">
    <button class="btn btn-primary btn-sm"><i class="fa-solid fa-magnifying-glass"></i></button>
</form>

<?php if (empty($resumenCategorias)): ?>
<div style="text-align:center;padding:var(--sp-10) 0;color:var(--text-muted,var(--color-text-muted));">
    <i class="fa-solid fa-box-open" style="font-size:2rem;opacity:.4;display:block;margin-bottom:10px;"></i>Sin resultados.
</div>
<?php else: ?>
<div class="cat-grid">
    <?php foreach ($resumenCategorias as $cat):
        $nombre = $cat['categoria_nombre'];
        $color  = $paleta[$nombre]['color'] ?? '#64748b';
        $icono  = $paleta[$nombre]['icono'] ?? 'fa-box';
    ?>
    <div class="cat-card">
        <div class="cat-top" style="background:linear-gradient(135deg,<?= $color ?>,<?= $color ?>cc);">
            <i class="fa-solid <?= $icono ?> bg"></i>
            <div style="font-size:.72rem;text-transform:uppercase;letter-spacing:.08em;opacity:.85;">Categoría</div>
            <div style="font-size:1.2rem;font-weight:800;line-height:1.2;"><?= htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') ?></div>
        </div>
        <div class="cat-body">
            <div class="cat-metric"><span style="color:var(--text-muted,var(--color-text-muted));">Bienes registrados</span><strong><?= (int)$cat['total_items'] ?></strong></div>
            <div class="cat-metric"><span style="color:var(--text-muted,var(--color-text-muted));">Existencias</span><strong><?= number_format((float)$cat['total_qty'], 0) ?></strong></div>
            <div class="cat-metric"><span style="color:var(--text-muted,var(--color-text-muted));">Valor estimado</span><strong style="color:<?= $color ?>;">$<?= number_format((float)$cat['total_value'], 2) ?></strong></div>
            <a href="<?= APP_URL ?>/inventario?categoria=<?= (int)$cat['categoria_id'] ?>" data-spa class="btn btn-ghost btn-sm" style="width:100%;justify-content:center;margin-top:var(--sp-3);">Ver bienes <i class="fa-solid fa-arrow-right" style="font-size:.7rem;"></i></a>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>
</div>
