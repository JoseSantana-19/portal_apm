<?php $success = SessionHelper::getFlash('success'); $nivel = (int)($_SESSION['nivel_jerarquia'] ?? 0); ?>
<div style="animation:pageFadeIn .3s ease both;">
<?php if ($success): ?><div class="alert alert-success" style="margin-bottom:var(--sp-3);"><i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>

<div class="page-header" style="margin-bottom:var(--sp-4);">
    <div>
        <h2 class="page-title"><i class="fa-solid fa-list-ol" style="color:#fd7e14;margin-right:var(--sp-2);"></i> Secuenciales de Índice</h2>
        <p class="page-subtitle">Contadores automáticos por módulo del sistema de inventario</p>
    </div>
</div>

<div class="card"><div class="card-body" style="padding:0;overflow-x:auto;">
    <table style="width:100%;border-collapse:collapse;">
        <thead><tr>
            <?php foreach (['Módulo','Prefijo','Último número','Próximo'] as $h): ?>
            <th style="text-align:left;font-size:.7rem;text-transform:uppercase;color:var(--text-muted,var(--color-text-muted));padding:var(--sp-2) var(--sp-3);border-bottom:1px solid var(--border-app,var(--color-border));"><?= $h ?></th>
            <?php endforeach; ?>
            <?php if ($nivel >= 3): ?><th></th><?php endif; ?>
        </tr></thead>
        <tbody>
        <?php if (empty($secuenciales)): ?>
            <tr><td colspan="5" style="text-align:center;padding:var(--sp-6);color:var(--text-muted,var(--color-text-muted));">Sin secuenciales configurados.</td></tr>
        <?php else: foreach ($secuenciales as $s):
            $num = (int)$s['ultimo_numero'];
            $prox = $s['prefijo'] . str_pad((string)($num+1), 5, '0', STR_PAD_LEFT);
        ?>
            <tr style="border-bottom:1px solid var(--border-app,var(--color-border));font-size:.85rem;">
                <td style="padding:var(--sp-2) var(--sp-3);"><strong style="text-transform:uppercase;"><?= htmlspecialchars($s['modulo'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                <td style="padding:var(--sp-2) var(--sp-3);font-family:var(--font-mono,monospace);"><?= htmlspecialchars($s['prefijo'], ENT_QUOTES, 'UTF-8') ?></td>
                <td style="padding:var(--sp-2) var(--sp-3);"><?= $num ?></td>
                <td style="padding:var(--sp-2) var(--sp-3);"><span class="badge badge-info" style="font-family:var(--font-mono,monospace);"><?= htmlspecialchars($prox, ENT_QUOTES, 'UTF-8') ?></span></td>
                <?php if ($nivel >= 3): ?>
                <td style="text-align:right;">
                    <form method="POST" action="<?= APP_URL ?>/inventario/secuenciales/reiniciar" style="display:inline;" onsubmit="return confirm('¿Reiniciar este secuencial a 0?');">
                        <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="modulo" value="<?= htmlspecialchars($s['modulo'], ENT_QUOTES, 'UTF-8') ?>">
                        <button class="btn btn-ghost btn-sm" title="Reiniciar a 0" style="color:var(--color-warning,#ffc107);"><i class="fa-solid fa-rotate-left"></i></button>
                    </form>
                </td>
                <?php endif; ?>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
</div></div>
</div>
