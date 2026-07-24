<?php $success = SessionHelper::getFlash('success'); $error = SessionHelper::getFlash('error'); ?>
<div style="animation:pageFadeIn .3s ease both;">
<?php if ($success): ?><div class="alert alert-success" style="margin-bottom:var(--sp-3);"><i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger" style="margin-bottom:var(--sp-3);"><i class="fa-solid fa-triangle-exclamation"></i> <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>

<div class="page-header" style="margin-bottom:var(--sp-4);">
    <div>
        <h2 class="page-title"><i class="fa-solid fa-calendar-days" style="color:#fd7e14;margin-right:var(--sp-2);"></i> Períodos e IVA</h2>
        <p class="page-subtitle">Gestión de períodos contables y tasa de IVA vigente</p>
    </div>
</div>

<?php if ($activo): ?>
<div class="card" style="margin-bottom:var(--sp-4);border-left:4px solid #28a745;"><div class="card-body" style="display:flex;align-items:center;gap:var(--sp-4);flex-wrap:wrap;">
    <div style="width:46px;height:46px;border-radius:12px;background:color-mix(in srgb,#28a745 14%,transparent);color:#28a745;display:flex;align-items:center;justify-content:center;font-size:1.2rem;"><i class="fa-solid fa-circle-check"></i></div>
    <div><div style="font-size:.72rem;text-transform:uppercase;color:var(--text-muted,var(--color-text-muted));">Período activo</div>
        <div style="font-size:1.1rem;font-weight:800;"><?= htmlspecialchars($activo['nombre'], ENT_QUOTES, 'UTF-8') ?></div></div>
    <div style="margin-left:auto;text-align:right;"><div style="font-size:.72rem;color:var(--text-muted,var(--color-text-muted));">IVA vigente</div><div style="font-size:1.4rem;font-weight:800;color:#28a745;"><?= number_format((float)($activo['tasa_iva'] ?? 0),0) ?>%</div></div>
</div></div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 320px;gap:var(--sp-4);align-items:start;">
    <div class="card"><div class="card-body" style="padding:0;overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;">
            <thead><tr>
                <?php foreach (['Período','Inicio','Fin','IVA','Estado'] as $h): ?>
                <th style="text-align:left;font-size:.7rem;text-transform:uppercase;color:var(--text-muted,var(--color-text-muted));padding:var(--sp-2) var(--sp-3);border-bottom:1px solid var(--border-app,var(--color-border));"><?= $h ?></th>
                <?php endforeach; ?>
            </tr></thead>
            <tbody>
            <?php if (empty($periodos)): ?>
                <tr><td colspan="5" style="text-align:center;padding:var(--sp-6);color:var(--text-muted,var(--color-text-muted));">Sin períodos.</td></tr>
            <?php else: foreach ($periodos as $p):
                $fmt = fn($v) => htmlspecialchars(is_string($v) ? $v : ($v instanceof DateTime ? $v->format('Y-m-d') : ''), ENT_QUOTES, 'UTF-8');
            ?>
                <tr style="border-bottom:1px solid var(--border-app,var(--color-border));font-size:.85rem;">
                    <td style="padding:var(--sp-2) var(--sp-3);"><strong><?= htmlspecialchars($p['nombre'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                    <td style="padding:var(--sp-2) var(--sp-3);"><?= $fmt($p['fecha_inicio']) ?></td>
                    <td style="padding:var(--sp-2) var(--sp-3);"><?= $fmt($p['fecha_fin']) ?></td>
                    <td style="padding:var(--sp-2) var(--sp-3);"><span class="badge badge-info"><?= number_format((float)($p['tasa_iva'] ?? 0),0) ?>%</span></td>
                    <td style="padding:var(--sp-2) var(--sp-3);"><span class="badge <?= ($p['estado'] ?? '')==='activo' ? 'badge-success':'badge-secondary' ?>"><?= htmlspecialchars(ucfirst($p['estado'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div></div>

    <div class="card"><div class="card-body">
        <h3 style="font-size:.92rem;font-weight:700;margin-bottom:var(--sp-3);"><i class="fa-solid fa-plus" style="color:#fd7e14;"></i> Nuevo período</h3>
        <form method="POST" action="<?= APP_URL ?>/inventario/periodos" style="display:flex;flex-direction:column;gap:var(--sp-3);" onsubmit="return confirm('Al crear un nuevo período, el actual se cerrará. ¿Continuar?');">
            <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
            <div><label class="form-label">Nombre *</label><input type="text" name="nombre" class="form-control" required placeholder="Período 2027"></div>
            <div><label class="form-label">Fecha inicio *</label><input type="date" name="fecha_inicio" class="form-control" required></div>
            <div><label class="form-label">Fecha fin *</label><input type="date" name="fecha_fin" class="form-control" required></div>
            <div><label class="form-label">Tasa IVA (%) *</label><input type="number" step="0.01" name="tasa_iva" class="form-control" value="15" required></div>
            <button class="btn btn-primary" style="justify-content:center;"><i class="fa-solid fa-floppy-disk"></i> Crear y activar</button>
        </form>
    </div></div>
</div>
</div>
