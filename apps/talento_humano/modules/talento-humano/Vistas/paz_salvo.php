<?php
$estadosPaz = array_values(array_unique(array_filter(array_map(
    static fn(array $documento): string => (string)($documento['estado'] ?? ''),
    $documentos ?? []
))));
sort($estadosPaz);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Paz y Salvo | APM</title>
    <?php require ROOT.'/shared/head_assets.php'; ?>
    <style>
        .ps-state {
            display: inline-flex;
            padding: 5px 10px;
            border-radius: 999px;
            background: #e8f4f8;
            color: #086b88;
            font-size: .74rem;
            font-weight: 800;
        }
        .ps-actions { display: flex; gap: 8px; }
    </style>
</head>
<body>
<div class="app">
    <?php require ROOT.'/shared/menu.php'; ?>
    <section class="content">
        <?php
        $topbarSubtitle = 'Salida institucional y certificaciones';
        $topbarShowSearch = true;
        require ROOT.'/shared/topbar.php';
        ?>
        <main class="main">
            <div class="content-shell ps-page">
                <section class="hero ps-hero">
                    <div>
                        <div class="hero-kicker">Documento de salida</div>
                        <h2>Paz y Salvo</h2>
                        <p>Certifica de forma auditable la entrega de información, bienes, credenciales y accesos del personal que sale de la institución.</p>
                        <div class="hero-actions">
                            <a class="btn btn-primary" href="<?= BASE_URL ?>/talento-humano/paz-salvo/crear">
                                <i class="bi bi-file-earmark-plus"></i> Nuevo documento
                            </a>
                            <a class="btn btn-ghost" target="_blank" href="<?= BASE_URL ?>/talento-humano/paz-salvo/formato-blanco">
                                <i class="bi bi-download"></i> Formato blanco
                            </a>
                        </div>
                    </div>
                </section>

                <?php if (isset($_GET['msg'])): ?>
                    <div class="alert <?= ($_GET['ok'] ?? '0') === '1' ? 'alert-success' : 'alert-error' ?>">
                        <?= htmlspecialchars((string)$_GET['msg']) ?>
                    </div>
                <?php endif; ?>

                <section class="card ps-list-card">
                    <div class="ps-list-toolbar">
                        <div class="ps-list-heading">
                            <h3>Documentos registrados</h3>
                            <p><?= count($documentos ?? []) ?> documento(s) disponibles para consulta y legalización.</p>
                        </div>
                        <div class="ps-filter">
                            <label for="pazSalvoEstado">
                                Estado
                                <select id="pazSalvoEstado" data-dt-filter-target="#pazSalvoTable" data-dt-column="4" data-dt-filter-mode="exact">
                                    <option value="">Todos</option>
                                    <?php foreach ($estadosPaz as $estadoPaz): ?>
                                        <option><?= htmlspecialchars($estadoPaz) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                        </div>
                    </div>
                    <div class="table-wrap">
                        <table id="pazSalvoTable" data-apm-datatable data-dt-page-length="25"
                               data-dt-order='[[3,"desc"]]' data-dt-order-disabled="5"
                               data-dt-search-placeholder="Buscar por número, funcionario o acción…"
                               data-dt-empty="Aún no hay documentos registrados.">
                            <thead>
                            <tr>
                                <th>Número</th>
                                <th>Funcionario</th>
                                <th>Acción de salida</th>
                                <th>Fecha</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php if (empty($documentos)): ?>
                                <tr data-dt-empty><td colspan="6" style="text-align:center;padding:30px">Aún no hay documentos registrados.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($documentos ?? [] as $documento): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($documento['numero_documento']) ?></strong></td>
                                    <td>
                                        <?= htmlspecialchars(trim($documento['apellidos'].' '.$documento['nombres'])) ?>
                                        <small style="display:block"><?= htmlspecialchars($documento['identificacion']) ?> · <?= htmlspecialchars($documento['cargo'] ?? '') ?></small>
                                    </td>
                                    <td><?= htmlspecialchars($documento['numero_accion']) ?></td>
                                    <td data-order="<?= htmlspecialchars((string)$documento['fecha_emision']) ?>"><?= date('d/m/Y', strtotime($documento['fecha_emision'])) ?></td>
                                    <td data-search="<?= htmlspecialchars((string)$documento['estado']) ?>">
                                        <span class="ps-state"><?= htmlspecialchars($documento['estado']) ?></span>
                                    </td>
                                    <td>
                                        <div class="ps-actions">
                                            <a class="btn btn-outline" href="<?= BASE_URL ?>/talento-humano/paz-salvo/ver?id=<?= (int)$documento['paz_salvo_id'] ?>">
                                                <i class="bi bi-pencil-square"></i> Gestionar
                                            </a>
                                            <a class="btn btn-ghost" target="_blank" href="<?= BASE_URL ?>/talento-humano/paz-salvo/imprimir?id=<?= (int)$documento['paz_salvo_id'] ?>">
                                                <i class="bi bi-file-pdf"></i> PDF
                                            </a>
                                            <?php if (Auth::can('documentos_firmados', 'visualizar')): ?>
                                                <a class="btn btn-ghost" href="<?= BASE_URL ?>/talento-humano/documentos-firmados?tipo=PAZ_SALVO&amp;origen_id=<?= (int)$documento['paz_salvo_id'] ?>">
                                                    <i class="bi bi-file-earmark-check"></i> Firmado
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </main>
    </section>
</div>
<?php require ROOT.'/shared/footer_scripts.php'; ?>
</body>
</html>
