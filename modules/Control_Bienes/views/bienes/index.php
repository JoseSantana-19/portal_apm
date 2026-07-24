<?php $success = SessionHelper::getFlash('success'); ?>

<?php if ($success): ?>
<div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:var(--sp-5);flex-wrap:wrap;gap:var(--sp-3);">
    <h2 style="font-size:var(--font-size-xl);font-weight:var(--font-weight-bold);">
        <i class="fa-solid fa-boxes-stacked" style="color:var(--color-success);margin-right:var(--sp-2);"></i>
        Inventario de Bienes <span class="badge badge-success"><?= number_format($total) ?></span>
    </h2>
    <div style="display:flex;gap:var(--sp-2);">
        <a href="<?= APP_URL ?>/bienes/movimientos" class="btn btn-outline" data-spa>
            <i class="fa-solid fa-arrows-rotate"></i> Movimientos
        </a>
        <a href="<?= APP_URL ?>/bienes/nuevo" class="btn btn-primary" data-spa>
            <i class="fa-solid fa-plus"></i> Registrar Bien
        </a>
    </div>
</div>

<!-- Filters -->
<div class="card" style="margin-bottom:var(--sp-4);">
    <div class="card-body" style="padding:var(--sp-4) var(--sp-5);">
    <form method="GET" action="<?= APP_URL ?>/bienes">
        <div style="display:flex;gap:var(--sp-3);flex-wrap:wrap;align-items:flex-end;">
            <div class="form-group" style="flex:1;min-width:140px;margin:0;">
                <label class="form-label">Código</label>
                <input type="text" name="codigo" class="form-control"
                       value="<?= htmlspecialchars($filters['codigo'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                       placeholder="Código del bien">
            </div>
            <div class="form-group" style="min-width:140px;margin:0;">
                <label class="form-label">Estado</label>
                <select name="estado" class="form-control">
                    <option value="">Todos</option>
                    <option value="Activo"       <?= ($filters['estado'] ?? '') === 'Activo'       ? 'selected' : '' ?>>Activo</option>
                    <option value="En Reparacion" <?= ($filters['estado'] ?? '') === 'En Reparacion' ? 'selected' : '' ?>>En Reparación</option>
                    <option value="Baja"         <?= ($filters['estado'] ?? '') === 'Baja'         ? 'selected' : '' ?>>Baja</option>
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
            <div style="margin:0;display:flex;gap:var(--sp-2);align-items:flex-end;padding-bottom:1px;">
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-magnifying-glass"></i> Filtrar
                </button>
                <a href="<?= APP_URL ?>/bienes" class="btn btn-ghost">
                    <i class="fa-solid fa-rotate-left"></i> Limpiar
                </a>
            </div>
        </div>
    </form>
    </div>
</div>

<div class="card">
    <div style="overflow-x:auto;">
        <table>
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Nombre</th>
                    <th>Categoría</th>
                    <th>Departamento</th>
                    <th>Valor</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($bienes)): ?>
            <tr><td colspan="7" style="text-align:center;color:var(--color-text-muted);padding:var(--sp-8);">
                Sin resultados
            </td></tr>
            <?php else: ?>
            <?php foreach ($bienes as $b):
                $estClass = match($b['estado'] ?? '') {
                    'Activo'      => 'badge-success',
                    'En Reparacion' => 'badge-warning',
                    'Baja'        => 'badge-danger',
                    default       => 'badge-secondary',
                };
            ?>
            <tr>
                <td><code><?= htmlspecialchars($b['codigo'], ENT_QUOTES, 'UTF-8') ?></code></td>
                <td>
                    <a href="<?= APP_URL ?>/bienes/<?= $b['id_activo'] ?>" data-spa
                       style="color:var(--color-primary);text-decoration:none;font-weight:var(--font-weight-medium);">
                        <?= htmlspecialchars($b['nombre'], ENT_QUOTES, 'UTF-8') ?>
                    </a>
                </td>
                <td><?= htmlspecialchars($b['categoria'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($b['departamento'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                <td>$<?= number_format((float)($b['valor_adquisicion'] ?? 0), 2) ?></td>
                <td><span class="badge <?= $estClass ?>"><?= htmlspecialchars($b['estado'] ?? '', ENT_QUOTES, 'UTF-8') ?></span></td>
                <td>
                    <a href="<?= APP_URL ?>/bienes/<?= $b['id_activo'] ?>/editar"
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
