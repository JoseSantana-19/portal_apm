<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Vacaciones | Portal Portuario APM</title>
    <?php require ROOT . '/shared/head_assets.php'; ?>
    <style>
        .vac-page { gap: 18px; }
        .vac-hero {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 24px;
            padding: 20px 24px;
        }
        .vac-hero-copy { min-width: 0; }
        .vac-hero .hero-kicker { margin-bottom: 6px; }
        .vac-hero h2 { margin-bottom: 6px; }
        .vac-hero p { max-width: 720px; }
        .vac-hero-action { flex: 0 0 auto; }
        .vac-summary {
            display: grid;
            grid-template-columns: repeat(4, minmax(150px, 1fr));
            gap: 12px;
        }
        .vac-metric {
            display: block;
            background: var(--card);
            border: 1px solid var(--line);
            border-left: 4px solid var(--ocean-600);
            border-radius: 14px;
            padding: 13px 16px;
            text-decoration: none;
            transition: transform .18s ease, box-shadow .18s ease;
        }
        .vac-metric:hover { transform: translateY(-2px); box-shadow: var(--shadow-md); }
        .vac-metric b { display: block; margin-top: 3px; color: var(--navy-900); font-size: 1.55rem; }
        .vac-metric small { color: var(--ink-600); }
        .vac-list-card { overflow: hidden; }
        .vac-list-toolbar {
            display: flex;
            align-items: end;
            justify-content: space-between;
            gap: 18px;
            padding: 14px 18px;
            border-bottom: 1px solid var(--line);
            background: var(--card);
        }
        .vac-list-heading h3 { margin: 0; color: var(--navy-900); font-size: 1rem; }
        .vac-list-heading p { margin: 3px 0 0; color: var(--ink-600); font-size: .78rem; }
        .vac-filter {
            display: flex;
            flex-direction: row;
            align-items: end;
            gap: 8px;
            margin: 0;
            padding: 0;
        }
        .vac-filter-field { display: grid; gap: 4px; }
        .vac-filter label { color: var(--ink-600); font-size: .72rem; font-weight: 700; }
        .vac-filter select {
            min-width: 190px;
            padding: 9px 34px 9px 11px;
            border: 1px solid var(--line);
            border-radius: 10px;
            background: var(--card);
            color: var(--ink-900);
        }
        .vac-filter .btn { min-height: 39px; }
        .vac-list-card .table-wrap { overflow-x: auto; }
        .vac-list-card table { min-width: 900px; }
        .vac-list-card thead th { padding-top: 12px; padding-bottom: 12px; }
        .vac-status { display: inline-flex; padding: 5px 10px; border-radius: 999px; font-size: .72rem; font-weight: 800; }
        .vac-status--vigente { background: #d9f7ec; color: #087d69; }
        .vac-status--programada { background: #e8f0ff; color: #2259c7; }
        .vac-status--finalizada { background: #eef1f5; color: #5d6878; }
        @media (max-width: 900px) {
            .vac-summary { grid-template-columns: repeat(2, 1fr); }
            .vac-hero, .vac-list-toolbar { align-items: stretch; flex-direction: column; }
            .vac-hero-action .btn { width: 100%; justify-content: center; }
        }
        @media (max-width: 600px) {
            .vac-summary { grid-template-columns: 1fr 1fr; gap: 8px; }
            .vac-filter { align-items: stretch; flex-direction: column; width: 100%; }
            .vac-filter select { width: 100%; }
            .vac-filter .btn { justify-content: center; }
        }
    </style>
</head>
<body>
<div id="overlay" class="overlay" onclick="closeSidebar()"></div>
<div class="app">
    <?php require ROOT . '/shared/menu.php'; ?>
    <section class="content">
        <?php $topbarSubtitle='Vacaciones registradas por Acción de Personal';$topbarShowSearch=true;require ROOT.'/shared/topbar.php'; ?>
        <main class="main">
            <div class="content-shell vac-page">
                <section class="hero vac-hero">
                    <div class="vac-hero-copy">
                        <div class="hero-kicker">Gestión de Talento Humano</div>
                        <h2>Vacaciones</h2>
                        <p>Fuente oficial: acciones de personal de tipo Vacaciones que ya fueron aprobadas. Este módulo no duplica solicitudes ni saldos.</p>
                    </div>
                    <div class="vac-hero-action">
                        <a class="btn btn-primary" href="<?= BASE_URL ?>/talento-humano/accion-personal?tipo=VACACIONES">
                            <i class="bi bi-file-earmark-plus"></i> Registrar Acción de Vacaciones
                        </a>
                    </div>
                </section>

                <div class="vac-summary">
                    <?php $metricas=[['Total','total',''],['Programadas','programadas','PROGRAMADA'],['Vigentes','vigentes','VIGENTE'],['Finalizadas','finalizadas','FINALIZADA']];foreach($metricas as [$label,$key,$filtro]): ?>
                        <a class="vac-metric" href="<?= BASE_URL ?>/talento-humano/vacaciones<?= $filtro?'?estado='.$filtro:'' ?>">
                            <small><?= $label ?></small><b><?= (int)($resumen[$key]??0) ?></b>
                        </a>
                    <?php endforeach; ?>
                </div>

                <section class="card vac-list-card">
                    <div class="vac-list-toolbar">
                        <div class="vac-list-heading">
                            <h3>Vacaciones registradas</h3>
                            <p><?= count($vacaciones) ?> resultado(s) para el filtro seleccionado.</p>
                        </div>
                        <form class="vac-filter" method="get">
                            <div class="vac-filter-field">
                                <label for="estado">Estado</label>
                                <select id="estado" name="estado">
                                    <option value="">Todos</option>
                                    <?php foreach(['PROGRAMADA','VIGENTE','FINALIZADA'] as $o): ?>
                                        <option <?= $estadoFiltro===$o?'selected':'' ?>><?= $o ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button class="btn btn-primary" type="submit"><i class="bi bi-funnel"></i> Filtrar</button>
                            <a class="btn btn-ghost" href="<?= BASE_URL ?>/talento-humano/vacaciones">Limpiar</a>
                        </form>
                    </div>
                    <div class="table-wrap">
                        <table>
                            <thead><tr><th>Serie</th><th>Funcionario</th><th>Área / Cargo</th><th>Período</th><th>Días</th><th>Estado</th><th>Acción</th></tr></thead>
                            <tbody>
                            <?php if(!$vacaciones): ?>
                                <tr><td colspan="7" style="text-align:center;padding:30px;color:var(--ink-600)">No existen vacaciones aprobadas para este filtro.</td></tr>
                            <?php endif; ?>
                            <?php foreach($vacaciones as $v): $estado=strtolower((string)$v['estado_vacacion']); ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($v['numero_accion']) ?></strong></td>
                                    <td><strong><?= htmlspecialchars(trim($v['apellidos'].' '.$v['nombres'])) ?></strong><small style="display:block"><?= htmlspecialchars($v['identificacion']) ?></small></td>
                                    <td><?= htmlspecialchars($v['area']??'Sin área') ?><small style="display:block"><?= htmlspecialchars($v['cargo']??'Sin cargo') ?></small></td>
                                    <td><?= date('d/m/Y',strtotime($v['fecha_inicio'])) ?> → <?= $v['fecha_fin']?date('d/m/Y',strtotime($v['fecha_fin'])):'Sin fecha final' ?></td>
                                    <td><?= (int)($v['dias_calendario']??0) ?></td>
                                    <td><span class="vac-status vac-status--<?= $estado ?>"><?= htmlspecialchars($v['estado_vacacion']) ?></span></td>
                                    <td><a class="btn btn-outline" href="<?= BASE_URL ?>/talento-humano/accion-personal/ver?id=<?= (int)$v['accion_id'] ?>"><i class="bi bi-eye"></i> Ver</a></td>
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
