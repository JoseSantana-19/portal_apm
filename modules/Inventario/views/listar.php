<?php
use InventarioController as IC;
$paleta  = InventarioController::paleta();
$success = SessionHelper::getFlash('success');
$error   = SessionHelper::getFlash('error');
$tasa    = (float)($tasaIva ?? 15.0);

function inv_estadoBadge(string $clase): string {
    return [
        'active' => 'badge-success', 'pending' => 'badge-warning',
        'dispatched' => 'badge-info', 'inactive' => 'badge-secondary',
    ][$clase] ?? 'badge-secondary';
}
?>
<style>
.inv-wrap { --inv-accent:#fd7e14; }
.inv-stats { display:grid; grid-template-columns:repeat(auto-fit,minmax(190px,1fr)); gap:var(--sp-3); margin-bottom:var(--sp-4); }
.inv-stat { display:flex; align-items:center; gap:var(--sp-3); padding:var(--sp-4); border-radius:var(--radius-lg,12px);
    background:var(--surface-app,var(--color-surface,#fff)); border:1px solid var(--border-app,var(--color-border)); }
.inv-stat-ico { width:42px; height:42px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:1.05rem; flex-shrink:0; }
.inv-stat-num { font-size:1.35rem; font-weight:800; line-height:1.1; color:var(--text-app,var(--color-text)); }
.inv-stat-lbl { font-size:.7rem; text-transform:uppercase; letter-spacing:.05em; color:var(--text-muted,var(--color-text-muted)); }
.inv-toolbar { display:flex; gap:var(--sp-2); flex-wrap:wrap; align-items:center; margin-bottom:var(--sp-3); }
.inv-toolbar form { display:flex; gap:var(--sp-2); flex-wrap:wrap; align-items:center; }
.inv-table { width:100%; border-collapse:collapse; }
.inv-table th { text-align:left; font-size:.7rem; text-transform:uppercase; letter-spacing:.05em; color:var(--text-muted,var(--color-text-muted));
    padding:var(--sp-2) var(--sp-3); border-bottom:1px solid var(--border-app,var(--color-border)); white-space:nowrap; }
.inv-table td { padding:var(--sp-2) var(--sp-3); border-bottom:1px solid var(--border-app,var(--color-border)); font-size:.85rem; vertical-align:middle; }
.inv-table tr:hover td { background:color-mix(in srgb, var(--inv-accent) 5%, transparent); }
.inv-item { display:flex; align-items:center; gap:var(--sp-2); }
.inv-item-ico { width:32px; height:32px; border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:.8rem; flex-shrink:0; }
.inv-cat { display:inline-flex; align-items:center; gap:5px; font-size:.78rem; font-weight:600; }
.inv-cat::before { content:''; width:8px; height:8px; border-radius:50%; background:var(--cat-color,#64748b); }
.inv-mono { font-family:var(--font-mono,monospace); font-size:.8rem; color:var(--text-muted,var(--color-text-muted)); }
.inv-actions { display:flex; gap:4px; }
.inv-actions button, .inv-actions a { width:28px; height:28px; border-radius:7px; border:none; cursor:pointer; display:inline-flex; align-items:center; justify-content:center;
    background:transparent; color:var(--text-muted,var(--color-text-muted)); font-size:.72rem; transition:all .15s; text-decoration:none; }
.inv-actions button:hover { background:color-mix(in srgb,var(--inv-accent) 14%,transparent); color:var(--inv-accent); }
/* Modal */
.inv-modal { position:fixed; inset:0; z-index:1000; display:none; align-items:flex-start; justify-content:center; padding:5vh 16px; background:rgba(10,20,35,.55); backdrop-filter:blur(3px); overflow:auto; }
.inv-modal.open { display:flex; }
.inv-modal-card { background:var(--surface-app,var(--color-surface,#fff)); border:1px solid var(--border-app,var(--color-border)); border-radius:14px; width:100%; max-width:640px; box-shadow:0 20px 60px rgba(0,0,0,.35); }
.inv-modal-head { display:flex; align-items:center; justify-content:space-between; padding:var(--sp-4) var(--sp-5); border-bottom:1px solid var(--border-app,var(--color-border)); }
.inv-modal-body { padding:var(--sp-5); }
.inv-grid2 { display:grid; grid-template-columns:1fr 1fr; gap:var(--sp-3); }
.inv-grid2 .full { grid-column:1/-1; }
.inv-field label { display:block; font-size:.72rem; font-weight:600; color:var(--text-muted,var(--color-text-muted)); margin-bottom:4px; text-transform:uppercase; letter-spacing:.04em; }
.inv-empty { text-align:center; padding:var(--sp-8) 0; color:var(--text-muted,var(--color-text-muted)); }
@media(max-width:640px){ .inv-grid2{grid-template-columns:1fr;} }
</style>

<div class="inv-wrap" style="animation:pageFadeIn .3s ease both;">

<?php if ($success): ?><div class="alert alert-success" style="margin-bottom:var(--sp-3);"><i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger" style="margin-bottom:var(--sp-3);"><i class="fa-solid fa-triangle-exclamation"></i> <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>

<div class="page-header" style="margin-bottom:var(--sp-4);">
    <div>
        <h2 class="page-title"><i class="fa-solid fa-boxes-stacked" style="color:#fd7e14;margin-right:var(--sp-2);"></i> Inventario General</h2>
        <p class="page-subtitle">Control de bienes portuarios — IVA período activo: <strong><?= number_format($tasa, 0) ?>%</strong></p>
    </div>
    <div class="page-actions" style="display:flex;gap:var(--sp-2);">
        <a href="<?= APP_URL ?>/inventario/exportar?<?= http_build_query($filtros) ?>" class="btn btn-outline btn-sm"><i class="fa-solid fa-file-csv"></i> CSV</a>
        <button class="btn btn-primary btn-sm" onclick="invAbrirModal()"><i class="fa-solid fa-plus"></i> Nuevo Bien</button>
    </div>
</div>

<!-- Stats -->
<div class="inv-stats">
    <?php
    $valorTotal = (float)($stats['valorTotal'] ?? 0);
    $cards = [
        ['fa-cubes',           number_format($stats['total'] ?? 0),       'Bienes activos',  '#fd7e14'],
        ['fa-sack-dollar',     '$'.number_format($valorTotal, 0),         'Valor base total','#28a745'],
        ['fa-layer-group',     count($stats['porCategoria'] ?? []),       'Categorías',      '#6f42c1'],
        ['fa-location-dot',    count($stats['porZona'] ?? []),            'Zonas',           '#17a2b8'],
    ];
    foreach ($cards as [$ico,$num,$lbl,$c]): ?>
    <div class="inv-stat">
        <div class="inv-stat-ico" style="background:color-mix(in srgb,<?= $c ?> 13%,transparent);color:<?= $c ?>;"><i class="fa-solid <?= $ico ?>"></i></div>
        <div><div class="inv-stat-num"><?= $num ?></div><div class="inv-stat-lbl"><?= $lbl ?></div></div>
    </div>
    <?php endforeach; ?>
</div>

<div class="card">
    <div class="card-body">
        <!-- Toolbar / filtros -->
        <div class="inv-toolbar">
            <form method="GET" action="<?= APP_URL ?>/inventario" data-spa-skip>
                <input type="text" name="termino" value="<?= htmlspecialchars($filtros['termino'] ?? '', ENT_QUOTES, 'UTF-8') ?>" class="form-control" placeholder="Buscar nombre, marca o secuencial…" style="min-width:240px;">
                <select name="categoria" class="form-control">
                    <option value="">Todas las categorías</option>
                    <?php foreach ($categorias as $c): ?>
                    <option value="<?= (int)$c['id'] ?>" <?= (string)($filtros['categoria'] ?? '') === (string)$c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['nombre'], ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-filter"></i> Filtrar</button>
                <a href="<?= APP_URL ?>/inventario" class="btn btn-ghost btn-sm">Limpiar</a>
            </form>
            <span style="margin-left:auto;font-size:.8rem;color:var(--text-muted,var(--color-text-muted));"><?= count($items) ?> resultado(s)</span>
        </div>

        <!-- Tabla -->
        <div style="overflow-x:auto;">
        <table class="inv-table">
            <thead><tr>
                <th>Secuencial</th><th>Bien</th><th>Categoría</th><th>Zona</th>
                <th style="text-align:right;">Valor</th><th style="text-align:center;">IVA</th>
                <th style="text-align:right;">Total</th><th>Estado</th><th></th>
            </tr></thead>
            <tbody>
            <?php if (empty($items)): ?>
                <tr><td colspan="9"><div class="inv-empty"><i class="fa-solid fa-box-open" style="font-size:1.8rem;opacity:.4;display:block;margin-bottom:8px;"></i>No hay bienes que coincidan con el filtro.</div></td></tr>
            <?php else: foreach ($items as $i):
                $cat   = $i['categoria'] ?? '';
                $color = $paleta[$cat]['color'] ?? '#64748b';
                $icono = $paleta[$cat]['icono'] ?? 'fa-box';
                $base  = (float)$i['valor'];
                $aplica = (int)($i['producto_aplica_iva'] ?? 1);
                $ivaPct = 0.0;
                foreach (($tiposIva ?? []) as $t) { if ((int)$t['id'] === $aplica) { $ivaPct = (float)$t['tasa_iva']; break; } }
                $ivaCalc = $base * ($ivaPct/100);
                $total   = $base + $ivaCalc;
                $itemJson = htmlspecialchars(json_encode($i), ENT_QUOTES, 'UTF-8');
            ?>
                <tr>
                    <td class="inv-mono"><?= htmlspecialchars($i['secuencial'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><div class="inv-item">
                        <div class="inv-item-ico" style="background:color-mix(in srgb,<?= $color ?> 14%,transparent);color:<?= $color ?>;"><i class="fa-solid <?= $icono ?>"></i></div>
                        <div><strong><?= htmlspecialchars($i['nombre'], ENT_QUOTES, 'UTF-8') ?></strong><br><span style="font-size:.72rem;color:var(--text-muted,var(--color-text-muted));">Marca: <?= htmlspecialchars($i['marca'], ENT_QUOTES, 'UTF-8') ?></span></div>
                    </div></td>
                    <td><span class="inv-cat" style="--cat-color:<?= $color ?>"><?= htmlspecialchars($cat, ENT_QUOTES, 'UTF-8') ?></span></td>
                    <td style="font-size:.8rem;color:var(--text-muted,var(--color-text-muted));"><?= htmlspecialchars($i['zona'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                    <td style="text-align:right;font-weight:600;">$<?= number_format($base, 2) ?></td>
                    <td style="text-align:center;"><span class="badge <?= $ivaCalc>0 ? 'badge-success':'badge-secondary' ?>"><?= number_format($ivaPct,0) ?>%</span></td>
                    <td style="text-align:right;font-weight:700;color:#fd7e14;">$<?= number_format($total, 2) ?></td>
                    <td><span class="badge <?= inv_estadoBadge($i['estadoClase']) ?>"><?= htmlspecialchars($i['estado'] ?? '—', ENT_QUOTES, 'UTF-8') ?></span></td>
                    <td><div class="inv-actions">
                        <button title="Ver" onclick="invVer(<?= (int)$i['id'] ?>)"><i class="fa-solid fa-eye"></i></button>
                        <button title="Editar" onclick='invEditar(<?= $itemJson ?>)'><i class="fa-solid fa-pen"></i></button>
                        <button title="Dar de baja" onclick="invEliminar(<?= (int)$i['id'] ?>)"><i class="fa-solid fa-ban"></i></button>
                    </div></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>

<!-- Modal Crear/Editar -->
<div class="inv-modal" id="invModal">
    <div class="inv-modal-card">
        <div class="inv-modal-head">
            <h3 id="invModalTitle" style="font-size:1.05rem;font-weight:700;"><i class="fa-solid fa-box" style="color:#fd7e14;"></i> Nuevo Bien</h3>
            <button class="btn btn-ghost btn-sm" onclick="invCerrarModal()"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form method="POST" action="<?= APP_URL ?>/inventario/guardar">
            <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="id" id="inv_id" value="0">
            <div class="inv-modal-body">
                <div class="inv-grid2">
                    <div class="inv-field full"><label>Nombre del bien *</label><input type="text" name="nombre" id="inv_nombre" class="form-control" required></div>
                    <div class="inv-field"><label>Marca *</label><input type="text" name="marca" id="inv_marca" class="form-control" required></div>
                    <div class="inv-field"><label>Valor base (USD) *</label><input type="number" step="0.01" name="valor" id="inv_valor" class="form-control" required></div>
                    <div class="inv-field"><label>Categoría *</label><select name="categoria_id" id="inv_categoria" class="form-control" required>
                        <?php foreach ($categorias as $c): ?><option value="<?= (int)$c['id'] ?>"><?= htmlspecialchars($c['nombre'], ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?>
                    </select></div>
                    <div class="inv-field"><label>Zona *</label><select name="zona_id" id="inv_zona" class="form-control" required>
                        <?php foreach ($zonas as $z): ?><option value="<?= (int)$z['id'] ?>"><?= htmlspecialchars($z['nombre'], ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?>
                    </select></div>
                    <div class="inv-field"><label>Estado *</label><select name="estado_id" id="inv_estado" class="form-control" required>
                        <?php foreach ($estados as $e): ?><option value="<?= (int)$e['id'] ?>"><?= htmlspecialchars($e['nombre'], ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?>
                    </select></div>
                    <div class="inv-field"><label>Responsable</label><select name="responsable_id" id="inv_responsable" class="form-control">
                        <option value="">— Sin asignar —</option>
                        <?php foreach ($personal as $p): ?><option value="<?= (int)$p['id'] ?>"><?= htmlspecialchars($p['nombre'], ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?>
                    </select></div>
                    <div class="inv-field full"><label>Observaciones</label><textarea name="observaciones" id="inv_obs" class="form-control" rows="2"></textarea></div>
                </div>
            </div>
            <div class="inv-modal-head" style="border-top:1px solid var(--border-app,var(--color-border));border-bottom:none;">
                <span style="font-size:.75rem;color:var(--text-muted,var(--color-text-muted));">El IVA se aplica según el producto/período.</span>
                <div style="display:flex;gap:8px;">
                    <button type="button" class="btn btn-ghost btn-sm" onclick="invCerrarModal()">Cancelar</button>
                    <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-floppy-disk"></i> Guardar</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Form oculto para baja -->
<form id="invDelForm" method="POST" style="display:none;"><input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>"></form>

<script>
(function(){
  const BASE = '<?= APP_URL ?>';
  window.invAbrirModal = function(){
    document.getElementById('invModalTitle').innerHTML = '<i class="fa-solid fa-box" style="color:#fd7e14;"></i> Nuevo Bien';
    document.getElementById('inv_id').value = 0;
    ['inv_nombre','inv_marca','inv_valor','inv_obs'].forEach(id=>document.getElementById(id).value='');
    document.getElementById('invModal').classList.add('open');
  };
  window.invCerrarModal = function(){ document.getElementById('invModal').classList.remove('open'); };
  window.invEditar = function(it){
    document.getElementById('invModalTitle').innerHTML = '<i class="fa-solid fa-pen" style="color:#fd7e14;"></i> Editar Bien';
    document.getElementById('inv_id').value = it.id;
    document.getElementById('inv_nombre').value = it.nombre || '';
    document.getElementById('inv_marca').value = it.marca || '';
    document.getElementById('inv_valor').value = it.valor || 0;
    document.getElementById('inv_categoria').value = it.categoria_id || '';
    document.getElementById('inv_zona').value = it.zona_id || '';
    document.getElementById('inv_estado').value = it.estado_id || '';
    document.getElementById('inv_responsable').value = it.responsable_id || '';
    document.getElementById('inv_obs').value = it.observaciones || '';
    document.getElementById('invModal').classList.add('open');
  };
  window.invVer = async function(id){
    try { const r = await fetch(`${BASE}/inventario/${id}/detalle`,{headers:{'X-Requested-With':'XMLHttpRequest'}}); const d = await r.json();
      if(d.error){ alert(d.error); return; }
      alert(`${d.secuencial} — ${d.nombre}\nMarca: ${d.marca}\nCategoría: ${d.categoria}\nZona: ${d.zona}\nEstado: ${d.estado}\nResponsable: ${d.responsable||'Sin asignar'}\n\nValor base: $${Number(d.valor).toFixed(2)}\nIVA (${d.tasa_iva}%): $${Number(d.iva_calculado).toFixed(2)}\nValor total: $${Number(d.valor_total).toFixed(2)}`);
    } catch(e){ alert('No se pudo cargar el detalle.'); }
  };
  window.invEliminar = function(id){
    if(!confirm('¿Dar de baja este bien? Quedará inactivo.')) return;
    const f = document.getElementById('invDelForm');
    f.action = `${BASE}/inventario/${id}/eliminar`;
    f.submit();
  };
})();
</script>
</div>
