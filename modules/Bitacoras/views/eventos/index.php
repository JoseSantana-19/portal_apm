<?php $success = SessionHelper::getFlash('success'); ?>

<?php if ($success): ?>
<div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:var(--sp-5);flex-wrap:wrap;gap:var(--sp-3);">
    <h2 style="font-size:var(--font-size-xl);font-weight:var(--font-weight-bold);">
        <i class="fa-solid fa-book-open" style="color:var(--color-warning);margin-right:var(--sp-2);"></i>
        Bitácoras <span class="badge badge-warning"><?= number_format($total) ?></span>
    </h2>
    <a href="<?= APP_URL ?>/bitacoras/nuevo" class="btn btn-primary" data-spa>
        <i class="fa-solid fa-plus"></i> Nueva Bitácora
    </a>
</div>

<!-- Filters -->
<div class="card" style="margin-bottom:var(--sp-4);">
    <form method="GET" action="<?= APP_URL ?>/bitacoras">
        <div style="display:flex;gap:var(--sp-3);flex-wrap:wrap;align-items:flex-end;">
            <div class="form-group" style="min-width:160px;margin:0;">
                <label class="form-label">Estado</label>
                <select name="estado" class="form-control">
                    <option value="">Todos</option>
                    <option value="Pendiente"  <?= ($filters['estado'] ?? '') === 'Pendiente'  ? 'selected' : '' ?>>Pendiente</option>
                    <option value="En Proceso" <?= ($filters['estado'] ?? '') === 'En Proceso' ? 'selected' : '' ?>>En Proceso</option>
                    <option value="Cerrado"    <?= ($filters['estado'] ?? '') === 'Cerrado'    ? 'selected' : '' ?>>Cerrado</option>
                </select>
            </div>
            <div class="form-group" style="min-width:180px;margin:0;">
                <label class="form-label">Categoría</label>
                <select name="categoria" class="form-control">
                    <option value="">Todas</option>
                    <?php foreach ($categorias as $cat): ?>
                    <option value="<?= $cat['id_categoria'] ?>"
                        <?= ($filters['categoria'] ?? '') == $cat['id_categoria'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat['nombre'], ENT_QUOTES, 'UTF-8') ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="margin:0;">
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-magnifying-glass"></i> Filtrar
                </button>
                <a href="<?= APP_URL ?>/bitacoras" class="btn btn-outline">Limpiar</a>
            </div>
        </div>
    </form>
</div>

<!-- Table -->
<div class="card">
    <div style="overflow-x:auto;">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Título</th>
                    <th>Categoría</th>
                    <th>Prioridad</th>
                    <th>Estado</th>
                    <th>Fecha Evento</th>
                    <th>Creado por</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($eventos)): ?>
            <tr><td colspan="8" style="text-align:center;color:var(--color-text-muted);padding:var(--sp-8);">
                Sin registros
            </td></tr>
            <?php else: ?>
            <?php foreach ($eventos as $ev):
                $estadoClass = match($ev['estado'] ?? '') {
                    'Pendiente'  => 'badge-warning',
                    'En Proceso' => 'badge-info',
                    'Cerrado'    => 'badge-success',
                    default      => 'badge-secondary',
                };
                $prioInt = (int)($ev['prioridad'] ?? 2);
                $prioClass = match(true) {
                    $prioInt >= 3 => 'badge-danger',
                    $prioInt == 2 => 'badge-warning',
                    default       => 'badge-info',
                };
                $prioLabel = match($prioInt) { 3 => 'Alta', 2 => 'Media', default => 'Baja' };
                $fe = $ev['fecha_evento'];
                if ($fe instanceof DateTime) { $feStr = $fe->format('d/m/Y'); }
                elseif (is_string($fe) && $fe) { $feStr = date('d/m/Y', strtotime($fe)); }
                else { $feStr = '—'; }
            ?>
            <tr>
                <td><?= $ev['id_evento'] ?></td>
                <td>
                    <a href="<?= APP_URL ?>/bitacoras/<?= $ev['id_evento'] ?>" data-spa
                       style="color:var(--color-primary);text-decoration:none;font-weight:var(--font-weight-medium);">
                        <?= htmlspecialchars($ev['titulo'], ENT_QUOTES, 'UTF-8') ?>
                    </a>
                    <div style="font-size:var(--font-size-xs);color:var(--color-text-muted);">
                        <?= htmlspecialchars(mb_substr($ev['descripcion'] ?? '', 0, 70), ENT_QUOTES, 'UTF-8') ?>
                    </div>
                </td>
                <td><?= htmlspecialchars($ev['categoria'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                <td><span class="badge <?= $prioClass ?>"><?= $prioLabel ?></span></td>
                <td><span class="badge <?= $estadoClass ?>"><?= htmlspecialchars($ev['estado'], ENT_QUOTES, 'UTF-8') ?></span></td>
                <td><?= $feStr ?></td>
                <td><?= htmlspecialchars($ev['creado_por'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                <td>
                    <a href="<?= APP_URL ?>/bitacoras/<?= $ev['id_evento'] ?>/editar"
                       class="btn btn-ghost btn-sm" data-spa>
                        <i class="fa-solid fa-pencil"></i>
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
