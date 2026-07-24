<?php
/**
 * Vista del dashboard principal.
 * Variables: $totalHoy, $activas, $ultimas, $msgAcceso
 * Se renderiza DENTRO del layout (navbar + sidebar ya incluidos).
 */
?>

<?php if ($msgAcceso): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        Acceso denegado.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
    </div>
<?php endif; ?>

<div class="row mb-4">
    <div class="col-12 col-lg-8">
        <h1 class="h3 text-primary fw-bold mb-1">Control de Ingreso de Visitas</h1>
        <p class="text-muted mb-0">Edificio administrativo - Autoridad Portuaria de Manta</p>
    </div>
    <div class="col-12 col-lg-4 text-lg-end mt-3 mt-lg-0">
        <span class="badge bg-secondary fs-6">Fecha actual: <?= date("d/m/Y") ?></span>
    </div>
</div>

<!-- Tarjetas resumen -->
<div class="row g-3 mb-4">
    <div class="col-12 col-md-4">
        <div class="card shadow-sm border-0 resumen-card">
            <div class="card-body d-flex align-items-center">
                <div class="resumen-icon bg-primary-subtle text-primary me-3"><i class="bi bi-people-fill"></i></div>
                <div>
                    <div class="text-muted small">Visitas registradas hoy</div>
                    <div class="display-6 fw-bold"><?= $totalHoy ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card shadow-sm border-0 resumen-card">
            <div class="card-body d-flex align-items-center">
                <div class="resumen-icon bg-warning-subtle text-warning me-3"><i class="bi bi-person-walking"></i></div>
                <div>
                    <div class="text-muted small">Visitas activas en el edificio</div>
                    <div class="display-6 fw-bold"><?= $activas ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4 d-flex flex-column justify-content-between">
        <a href="visitas/registrar" class="btn btn-primary btn-lg w-100 mb-2">Registrar nuevo ingreso</a>
        <a href="visitas" class="btn btn-outline-primary btn-lg w-100">Ver listado de visitas</a>
    </div>
</div>

<!-- Últimas visitas -->
<div class="card shadow-sm border-0">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h2 class="h5 mb-0 text-primary">Últimas visitas registradas</h2>
        <a href="visitas" class="btn btn-sm btn-outline-secondary">Ver todas</a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Nombre</th>
                        <th>N.º identificación</th>
                        <th>Empresa / Personal</th>
                        <th>Fecha</th>
                        <th>Hora entrada</th>
                        <th>Hora salida</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($ultimas as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['nombre_visitante'] ?? '') ?></td>
                        <td><?= htmlspecialchars($row['nidentificacion'] ?? '') ?></td>
                        <td>
                            <?php if (($row['tipo_visitante'] ?? '') === 'Personal' || empty($row['empresa'])): ?>
                                <span class="badge bg-secondary">Personal</span>
                            <?php else: ?>
                                <?php
                                    $label = $row['empresa'];
                                    if (!empty($row['empresa_ruc'])) $label .= ' (' . $row['empresa_ruc'] . ')';
                                    echo htmlspecialchars($label);
                                ?>
                            <?php endif; ?>
                        </td>
                        <td><?= ($row['fecha_visita'] instanceof DateTime) ? $row['fecha_visita']->format('d/m/Y') : '' ?></td>
                        <td><?= ($row['hora_entrada'] instanceof DateTime) ? $row['hora_entrada']->format('H:i') : '' ?></td>
                        <td>
                            <?php if ($row['hora_salida'] instanceof DateTime): ?>
                                <?= $row['hora_salida']->format('H:i') ?>
                            <?php else: ?>
                                <span class="text-muted">Pendiente</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($row['hora_salida'] === null): ?>
                                <span class="badge bg-success">Dentro</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Finalizada</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($ultimas)): ?>
                    <tr><td colspan="7" class="text-center text-muted py-3">No se encontraron visitas.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
