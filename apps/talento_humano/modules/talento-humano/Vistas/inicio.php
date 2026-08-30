<?php
/* inicio.php – Vista: Pantalla principal de Inicio (Dashboard de RRHH)
   Muestra métricas generales + tabla de Próximos Cumpleaños con alertas de color. */

$proximos_cumpleanos = [];
$hoy = InstitutionalClock::today();
foreach (($empleados ?? []) as $fila) {
    if ((int)($fila['estado'] ?? 0) !== 1 || empty($fila['fecha_nacimiento'])) continue;
    try {
        $proximo = InstitutionalClock::nextBirthday((string)$fila['fecha_nacimiento']);
        $cumple = $proximo['date'];
        $dias = $proximo['days'];
        $alerta = $proximo['label'];
        $proximos_cumpleanos[]=[
            'nombre'=>trim(($fila['apellidos'] ?? '').' '.($fila['nombres'] ?? '')),
            'departamento'=>$fila['direccion_area'] ?: 'Sin asignación',
            'cargo'=>$fila['cargo'] ?: 'Sin denominación',
            'fecha_nacimiento'=>$cumple->format('d/m'),'alerta'=>$alerta,'dias'=>$dias,
            'color_alerta'=>$dias===0?'danger':($dias===1?'warning':($dias<=15?'info':'primary')),
            'icono'=>$dias===0?'bi-cake2':'bi-calendar-event',
            'initials'=>strtoupper(substr((string)($fila['apellidos']??'U'),0,1).substr((string)($fila['nombres']??'A'),0,1)),
            'cedula'=>$fila['cedula'] ?? '', 'correo'=>$fila['correo_institucional'] ?? '',
        ];
    } catch (Throwable $e) {}
}
usort($proximos_cumpleanos,fn($a,$b)=>$a['dias']<=>$b['dias']);
$proximos_cumpleanos=array_values(array_filter($proximos_cumpleanos,fn($c)=>(int)$c['dias']<=30));
$conteoCumpleanos = [
    'hoy' => count(array_filter($proximos_cumpleanos, fn($c) => (int)$c['dias'] === 0)),
    'manana' => count(array_filter($proximos_cumpleanos, fn($c) => (int)$c['dias'] === 1)),
    '15' => count(array_filter($proximos_cumpleanos, fn($c) => (int)$c['dias'] <= 15)),
    '30' => count($proximos_cumpleanos),
];
$filtroCumpleInicial = $conteoCumpleanos['hoy'] > 0 ? 'hoy'
    : ($conteoCumpleanos['manana'] > 0 ? 'manana'
        : ($conteoCumpleanos['15'] > 0 ? '15' : '30'));
$limiteVistaPrevia = 5;
$hitosServicio = array_values($hitosServicio ?? []);
$conteoHitos = [
    'todos' => count($hitosServicio),
    '5' => count(array_filter($hitosServicio, fn($h) => (int)($h['hito_anios'] ?? 0) === 5)),
    '10' => count(array_filter($hitosServicio, fn($h) => (int)($h['hito_anios'] ?? 0) === 10)),
    '15plus' => count(array_filter($hitosServicio, fn($h) => (int)($h['hito_anios'] ?? 0) >= 15)),
];

// ── Métricas calculadas desde los funcionarios reales ────────────────────────────────────
$total     = count($empleados ?? []);
// La vista view_th_iddatosempledo devuelve 'estado' como int: 1=Activo, 0=Inactivo
// Se soporta también el formato texto por compatibilidad futura
$activos   = count(array_filter($empleados ?? [], function($e) {
    $est = $e['estado'] ?? $e['estado_funcionario'] ?? null;
    return $est === 1 || $est === '1' || $est === 'Activo';
}));
$permisos  = count(array_filter($empleados ?? [], function($e) {
    $est = $e['estado'] ?? $e['estado_funcionario'] ?? null;
    return $est === 'Permiso' || $est === 2;
}));
$inactivos = count(array_filter($empleados ?? [], function($e) {
    $est = $e['estado'] ?? $e['estado_funcionario'] ?? null;
    return $est === 0 || $est === '0' || $est === 'Inactivo';
}));
$hoy_bday  = $conteoCumpleanos['hoy'];
$vacacionesVigentes=(int)($resumenVacaciones['vigentes']??0);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicio | Talento Humano – APM</title>
    <meta name="description" content="Panel de inicio de Talento Humano: métricas de personal y alertas de cumpleaños próximos.">
    <?php require ROOT . '/shared/head_assets.php'; ?>
</head>
<body>

<div id="overlay" class="overlay" onclick="closeSidebar()"></div>
<button class="sidebar-open-btn" id="sidebarOpenBtn" onclick="openSidebar()"
        title="Abrir menú lateral" aria-label="Abrir menú lateral">
    <i class="bi bi-layout-sidebar"></i>
</button>

<div class="app">
    <?php require_once ROOT . '/shared/menu.php'; ?>

    <section class="content">
        <?php $topbarShowSearch=true; require ROOT.'/shared/topbar.php'; ?>

        <main class="main">
            <div class="content-shell">

                <!-- ════════════════════════════════════════════
                     BLOQUE SUPERIOR: RESUMEN GENERAL + MÉTRICAS
                ════════════════════════════════════════════ -->
                <section class="hero" id="seccion-resumen">
                    <div>
                        <div class="hero-kicker">Panel de Control · Talento Humano</div>
                        <h2>Bienvenido al Sistema</h2>
                        <p>Gestiona expedientes, consulta el estado del personal y controla las alertas de la institución desde un solo lugar.</p>
                    </div>

                    <!-- MÉTRICAS DE PERSONAL -->
                    <div class="metrics">
                        <a class="metric-card metric-card--registrados metric-card--link" href="<?= BASE_URL ?>/talento-humano/directorio" aria-label="Ver todo el directorio">
                            <div class="metric-label">
                                <i class="bi bi-people-fill"></i> Registrados
                            </div>
                            <div class="metric-value"><?= $total ?></div>
                            <div class="metric-foot">Directorio APM</div>
                        </a>
                        <a class="metric-card metric-card--activos metric-card--link"
                           href="<?= BASE_URL ?>/talento-humano/directorio?estado=Activo"
                           aria-label="Ver empleados activos">
                            <div class="metric-label">
                                <i class="bi bi-person-check"></i> Activos
                            </div>
                            <div class="metric-value"><?= $activos ?></div>
                            <div class="metric-foot">En funciones hoy</div>
                        </a>
                        <a class="metric-card metric-card--permisos metric-card--link" href="<?= BASE_URL ?>/talento-humano/vacaciones?estado=VIGENTE" aria-label="Ver vacaciones vigentes">
                            <div class="metric-label">
                                <i class="bi bi-calendar-check"></i> Vacaciones
                            </div>
                            <div class="metric-value"><?= $vacacionesVigentes ?></div>
                            <div class="metric-foot">Vigentes hoy</div>
                        </a>
                        <a class="metric-card metric-card--cumples metric-card--link" href="#agenda-talento">
                            <div class="metric-label">
                                <i class="bi bi-cake2"></i> Cumpleaños hoy
                            </div>
                            <div class="metric-value"><?= $hoy_bday ?></div>
                            <div class="metric-foot">Felicitar hoy</div>
                        </a>
                    </div>
                </section>

                <!-- BOTONES DE ACCESO RÁPIDO -->
                <div class="action-grid" id="accesos-rapidos">
                    <?php if (Auth::can('empleados', 'crear')): ?>
                    <a class="action-card" href="<?= BASE_URL ?>/talento-humano/empleado/crear" id="ac-nuevo">
                        <i class="bi bi-person-plus-fill"></i>
                        <div>
                            <h4>Nuevo Expediente</h4>
                            <p>Registrar funcionario</p>
                        </div>
                    </a>
                    <?php endif; ?>
                    <?php if (Auth::can('directorio', 'visualizar')): ?>
                    <a class="action-card" href="<?= BASE_URL ?>/talento-humano/directorio" id="ac-directorio">
                        <i class="bi bi-card-list"></i>
                        <div>
                            <h4>Directorio</h4>
                            <p>Ver listado de personal</p>
                        </div>
                    </a>
                    <?php endif; ?>
                    <?php if (Auth::can('reportes', 'visualizar')): ?>
                    <a class="action-card" href="<?= BASE_URL ?>/reportes" id="ac-reportes">
                        <i class="bi bi-graph-up-arrow"></i>
                        <div>
                            <h4>Reportes</h4>
                            <p>Exportar y analizar datos</p>
                        </div>
                    </a>
                    <?php endif; ?>
                    <?php if (Auth::can('auditoria', 'visualizar')): ?>
                    <a class="action-card" href="<?= BASE_URL ?>/auditoria/logs" id="ac-auditoria">
                        <i class="bi bi-journal-text"></i>
                        <div>
                            <h4>Auditoría</h4>
                            <p>Logs de actividad del sistema</p>
                        </div>
                    </a>
                    <?php endif; ?>
                </div>

                <!-- AGENDA COMPACTA: CUMPLEAÑOS + ANIVERSARIOS -->
                <section class="card agenda-card" id="agenda-talento">
                    <div class="card-header agenda-header">
                        <div>
                            <h3><i class="bi bi-calendar2-heart"></i> Agenda de Talento Humano</h3>
                            <p>Alertas de cumpleaños y reconocimientos de servicio en un solo bloque compacto.</p>
                        </div>
                        <?php if ($hoy_bday > 0): ?>
                        <span class="bday-alert-chip"><i class="bi bi-exclamation-circle-fill"></i><?= $hoy_bday ?> cumple HOY</span>
                        <?php endif; ?>
                    </div>

                    <div class="agenda-tabs" role="tablist" aria-label="Contenido de la agenda">
                        <button type="button" class="agenda-tab is-active" id="tab-cumpleanos" role="tab"
                                aria-selected="true" aria-controls="seccion-cumpleanos" data-agenda-tab="cumpleanos">
                            <i class="bi bi-cake2"></i> Cumpleaños
                            <strong><?= $conteoCumpleanos['30'] ?></strong>
                        </button>
                        <button type="button" class="agenda-tab" id="tab-aniversarios" role="tab"
                                aria-selected="false" aria-controls="hitos-servicio" data-agenda-tab="aniversarios" tabindex="-1">
                            <i class="bi bi-award"></i> Aniversarios de servicio <?= $hoy->format('Y') ?>
                            <strong><?= $conteoHitos['todos'] ?></strong>
                        </button>
                    </div>

                    <div class="agenda-panel" id="seccion-cumpleanos" role="tabpanel" aria-labelledby="tab-cumpleanos" data-agenda-panel="cumpleanos">
                        <div class="bday-filters" aria-label="Filtrar cumpleaños por rango">
                            <button type="button" class="bday-filter-button bday-filter-button--danger <?= $filtroCumpleInicial === 'hoy' ? 'is-active' : '' ?>" data-bday-filter="hoy" aria-pressed="<?= $filtroCumpleInicial === 'hoy' ? 'true' : 'false' ?>">
                                <span>Hoy</span><strong><?= $conteoCumpleanos['hoy'] ?></strong>
                            </button>
                            <button type="button" class="bday-filter-button bday-filter-button--warning <?= $filtroCumpleInicial === 'manana' ? 'is-active' : '' ?>" data-bday-filter="manana" aria-pressed="<?= $filtroCumpleInicial === 'manana' ? 'true' : 'false' ?>">
                                <span>Mañana</span><strong><?= $conteoCumpleanos['manana'] ?></strong>
                            </button>
                            <button type="button" class="bday-filter-button bday-filter-button--info <?= $filtroCumpleInicial === '15' ? 'is-active' : '' ?>" data-bday-filter="15" aria-pressed="<?= $filtroCumpleInicial === '15' ? 'true' : 'false' ?>">
                                <span>Próximos 15 días</span><strong><?= $conteoCumpleanos['15'] ?></strong>
                            </button>
                            <button type="button" class="bday-filter-button bday-filter-button--primary <?= $filtroCumpleInicial === '30' ? 'is-active' : '' ?>" data-bday-filter="30" aria-pressed="<?= $filtroCumpleInicial === '30' ? 'true' : 'false' ?>">
                                <span>Próximos 30 días</span><strong><?= $conteoCumpleanos['30'] ?></strong>
                            </button>
                        </div>

                        <div class="table-wrap agenda-table-wrap bday-table-wrap">
                            <table id="tablaCumpleanos" aria-label="Tabla de próximos cumpleaños">
                                <thead><tr><th>Funcionario</th><th>Departamento / Cargo</th><th>Fecha de Cumpleaños</th><th>Estado / Alerta</th><th>Acciones</th></tr></thead>
                                <tbody>
                                <?php foreach ($proximos_cumpleanos as $i => $f): ?>
                                    <tr class="table-row bday-row bday-row--<?= $f['color_alerta'] ?>" data-bday-days="<?= (int)$f['dias'] ?>">
                                        <td><div class="name-cell"><div class="bday-avatar bday-avatar--<?= $f['color_alerta'] ?>"><?= htmlspecialchars($f['initials']) ?></div><div class="name-meta"><span><?= htmlspecialchars($f['nombre']) ?></span><small><?= htmlspecialchars($f['cargo']) ?></small></div></div></td>
                                        <td><span class="dept-pill"><?= htmlspecialchars($f['departamento']) ?></span></td>
                                        <td class="bday-fecha-cell"><span class="bday-date"><i class="bi bi-calendar3 bday-cal-icon"></i><?= htmlspecialchars($f['fecha_nacimiento']) ?></span></td>
                                        <td><span class="bday-badge bday-badge--<?= $f['color_alerta'] ?>"><i class="bi <?= htmlspecialchars($f['icono']) ?>"></i><?= htmlspecialchars($f['alerta']) ?></span></td>
                                        <td class="bday-actions-cell">
                                            <a href="<?= BASE_URL ?>/talento-humano/empleado/perfil/<?= htmlspecialchars($f['cedula']) ?>" class="btn btn-outline bday-btn-perfil" title="Ver expediente de <?= htmlspecialchars($f['nombre']) ?>" id="btn-perfil-<?= $i ?>"><i class="bi bi-person-lines-fill"></i> Ver Perfil</a>
                                            <?php if ($f['alerta'] === 'HOY' && !empty($f['correo'])): ?><a class="btn bday-btn-felicitar" href="mailto:<?= htmlspecialchars($f['correo']) ?>?subject=Feliz%20cumpleaños" title="Redactar felicitación" id="btn-felicitar-<?= $i ?>"><i class="bi bi-envelope-heart"></i></a><?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                    <tr id="bdayEmpty" class="bday-empty" hidden><td colspan="5"><i class="bi bi-calendar2-check"></i> No hay cumpleaños en el rango seleccionado.</td></tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="agenda-preview-footer">
                            <span id="bdayPreviewStatus" class="agenda-preview-status"></span>
                            <div class="agenda-preview-actions">
                                <button type="button" class="btn btn-ghost" id="btn-exportar"
                                        onclick="exportarTablaCSV('tablaCumpleanos', 'Reporte_Cumpleanos_APM.csv')">
                                    <i class="bi bi-file-earmark-excel"></i> Exportar cumpleaños
                                </button>
                                <button type="button" class="btn btn-outline" id="btnTodosCumpleanos" data-bday-toggle-all>
                                    Ver los <?= $conteoCumpleanos['30'] ?> cumpleaños <i class="bi bi-chevron-down"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="agenda-panel" id="hitos-servicio" role="tabpanel" aria-labelledby="tab-aniversarios" data-agenda-panel="aniversarios" hidden>
                        <div class="bday-filters" aria-label="Filtrar aniversarios por hito">
                            <button type="button" class="bday-filter-button bday-filter-button--primary is-active" data-hito-filter="todos" aria-pressed="true"><span>Todos</span><strong><?= $conteoHitos['todos'] ?></strong></button>
                            <button type="button" class="bday-filter-button" data-hito-filter="5" aria-pressed="false"><span>5 años</span><strong><?= $conteoHitos['5'] ?></strong></button>
                            <button type="button" class="bday-filter-button" data-hito-filter="10" aria-pressed="false"><span>10 años</span><strong><?= $conteoHitos['10'] ?></strong></button>
                            <button type="button" class="bday-filter-button" data-hito-filter="15plus" aria-pressed="false"><span>15 años o más</span><strong><?= $conteoHitos['15plus'] ?></strong></button>
                        </div>
                        <div class="table-wrap agenda-table-wrap">
                            <table id="tablaAniversarios" aria-label="Aniversarios de servicio <?= $hoy->format('Y') ?>">
                                <thead><tr><th>Funcionario</th><th>Área / Cargo</th><th>Hito</th><th>Fecha</th><th>Perfil</th></tr></thead>
                                <tbody>
                                <?php foreach ($hitosServicio as $i => $hito): ?>
                                    <tr data-hito-years="<?= (int)$hito['hito_anios'] ?>" <?= $i >= $limiteVistaPrevia ? 'hidden' : '' ?>>
                                        <td><strong><?= htmlspecialchars(trim($hito['apellidos'].' '.$hito['nombres'])) ?></strong><small class="agenda-secondary-line"><?= htmlspecialchars($hito['identificacion']) ?></small></td>
                                        <td><?= htmlspecialchars($hito['area']??'Sin área') ?><small class="agenda-secondary-line"><?= htmlspecialchars($hito['cargo']??'Sin cargo') ?></small></td>
                                        <td><span class="bday-pill bday-pill--primary"><i class="bi bi-award"></i> <?= (int)$hito['hito_anios'] ?> años</span></td>
                                        <td><?= date('d/m/Y',strtotime($hito['fecha_hito'])) ?></td>
                                        <td><a class="btn btn-outline" href="<?= BASE_URL ?>/talento-humano/empleado/perfil/<?= urlencode($hito['identificacion']) ?>"><i class="bi bi-person-lines-fill"></i> Ver perfil</a></td>
                                    </tr>
                                <?php endforeach; ?>
                                    <tr id="anniversaryEmpty" class="bday-empty" <?= empty($hitosServicio) ? '' : 'hidden' ?>><td colspan="5"><i class="bi bi-award"></i> No hay aniversarios en el rango seleccionado.</td></tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="agenda-preview-footer">
                            <span id="anniversaryPreviewStatus" class="agenda-preview-status"></span>
                            <button type="button" class="btn btn-outline" id="btnTodosAniversarios" data-anniversary-toggle-all <?= empty($hitosServicio) ? 'hidden' : '' ?>>
                                Ver los <?= $conteoHitos['todos'] ?> aniversarios <i class="bi bi-chevron-down"></i>
                            </button>
                        </div>
                    </div>
                </section>

            </div><!-- /content-shell -->
        </main>
    </section>
</div><!-- /app -->


<?php if (!empty($_GET['msg'])): ?>
<script>
    window.addEventListener('DOMContentLoaded', () => {
        showToast(<?= json_encode(htmlspecialchars($_GET['msg'])) ?>, <?= ($_GET['ok'] ?? '0') === '1' ? "'success'" : "'error'" ?>);
    });
</script>
<?php endif; ?>

<script>
/* ── Fecha en topbar ── */
document.addEventListener('DOMContentLoaded', () => {
    const el = document.getElementById('currentDate');
    if (el) {
        el.textContent = new Date().toLocaleDateString('es-EC', {
            weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
        });
    }

    const previewLimit = <?= (int)$limiteVistaPrevia ?>;
    const agendaTabs = [...document.querySelectorAll('[data-agenda-tab]')];
    const agendaPanels = [...document.querySelectorAll('[data-agenda-panel]')];
    const activateAgendaTab = (name, moveFocus = false) => {
        agendaTabs.forEach((tab) => {
            const active = tab.dataset.agendaTab === name;
            tab.classList.toggle('is-active', active);
            tab.setAttribute('aria-selected', active ? 'true' : 'false');
            tab.tabIndex = active ? 0 : -1;
            if (active && moveFocus) tab.focus();
        });
        agendaPanels.forEach((panel) => { panel.hidden = panel.dataset.agendaPanel !== name; });
    };
    agendaTabs.forEach((tab, index) => {
        tab.addEventListener('click', () => activateAgendaTab(tab.dataset.agendaTab || 'cumpleanos'));
        tab.addEventListener('keydown', (event) => {
            if (!['ArrowLeft', 'ArrowRight'].includes(event.key)) return;
            event.preventDefault();
            const offset = event.key === 'ArrowRight' ? 1 : -1;
            const target = agendaTabs[(index + offset + agendaTabs.length) % agendaTabs.length];
            activateAgendaTab(target.dataset.agendaTab || 'cumpleanos', true);
        });
    });
    if (window.location.hash === '#hitos-servicio') activateAgendaTab('aniversarios');

    const filterButtons = [...document.querySelectorAll('[data-bday-filter]')];
    const birthdayRows = [...document.querySelectorAll('#tablaCumpleanos tbody tr[data-bday-days]')];
    const emptyRow = document.getElementById('bdayEmpty');
    const birthdayStatus = document.getElementById('bdayPreviewStatus');
    const birthdayToggle = document.querySelector('[data-bday-toggle-all]');
    let birthdayExpanded = false;
    let currentBirthdayFilter = <?= json_encode($filtroCumpleInicial, JSON_UNESCAPED_UNICODE) ?>;

    const applyBirthdayFilter = (filter) => {
        currentBirthdayFilter = filter;
        let matches = 0;
        let rendered = 0;
        birthdayRows.forEach((row) => {
            const days = Number(row.dataset.bdayDays);
            const match = filter === 'hoy' ? days === 0
                : filter === 'manana' ? days === 1
                : days <= Number(filter);
            if (match) matches++;
            const visible = match && (birthdayExpanded || rendered < previewLimit);
            row.hidden = !visible;
            row.dataset.agendaMatch = match ? '1' : '0';
            if (visible) rendered++;
        });
        filterButtons.forEach((button) => {
            const active = button.dataset.bdayFilter === filter;
            button.classList.toggle('is-active', active);
            button.setAttribute('aria-pressed', active ? 'true' : 'false');
        });
        if (emptyRow) emptyRow.hidden = matches !== 0;
        if (birthdayStatus) birthdayStatus.textContent = matches === 0
            ? 'Sin cumpleaños en este rango.'
            : `Mostrando ${rendered} de ${matches} cumpleaños.`;
        if (birthdayToggle) {
            birthdayToggle.hidden = birthdayRows.length <= previewLimit && !birthdayExpanded;
            birthdayToggle.innerHTML = birthdayExpanded
                ? 'Mostrar vista resumida <i class="bi bi-chevron-up"></i>'
                : `Ver los ${birthdayRows.length} cumpleaños <i class="bi bi-chevron-down"></i>`;
        }
    };

    filterButtons.forEach((button) => {
        button.addEventListener('click', () => {
            birthdayExpanded = false;
            applyBirthdayFilter(button.dataset.bdayFilter || '30');
        });
    });
    birthdayToggle?.addEventListener('click', () => {
        birthdayExpanded = !birthdayExpanded;
        if (birthdayExpanded) currentBirthdayFilter = '30';
        applyBirthdayFilter(currentBirthdayFilter);
    });
    applyBirthdayFilter(currentBirthdayFilter);

    const milestoneButtons = [...document.querySelectorAll('[data-hito-filter]')];
    const milestoneRows = [...document.querySelectorAll('#tablaAniversarios tbody tr[data-hito-years]')];
    const milestoneEmpty = document.getElementById('anniversaryEmpty');
    const milestoneStatus = document.getElementById('anniversaryPreviewStatus');
    const milestoneToggle = document.querySelector('[data-anniversary-toggle-all]');
    let milestoneExpanded = false;
    let currentMilestoneFilter = 'todos';
    const applyMilestoneFilter = (filter) => {
        currentMilestoneFilter = filter;
        let matches = 0;
        let rendered = 0;
        milestoneRows.forEach((row) => {
            const years = Number(row.dataset.hitoYears);
            const match = filter === 'todos' || (filter === '15plus' ? years >= 15 : years === Number(filter));
            if (match) matches++;
            const visible = match && (milestoneExpanded || rendered < previewLimit);
            row.hidden = !visible;
            if (visible) rendered++;
        });
        milestoneButtons.forEach((button) => {
            const active = button.dataset.hitoFilter === filter;
            button.classList.toggle('is-active', active);
            button.classList.toggle('bday-filter-button--primary', active);
            button.setAttribute('aria-pressed', active ? 'true' : 'false');
        });
        if (milestoneEmpty) milestoneEmpty.hidden = matches !== 0;
        if (milestoneStatus) milestoneStatus.textContent = matches === 0
            ? 'Sin aniversarios para este hito.'
            : `Mostrando ${rendered} de ${matches} reconocimientos.`;
        if (milestoneToggle) {
            milestoneToggle.hidden = milestoneRows.length <= previewLimit && !milestoneExpanded;
            milestoneToggle.innerHTML = milestoneExpanded
                ? 'Mostrar vista resumida <i class="bi bi-chevron-up"></i>'
                : `Ver los ${milestoneRows.length} aniversarios <i class="bi bi-chevron-down"></i>`;
        }
    };
    milestoneButtons.forEach((button) => {
        button.addEventListener('click', () => {
            milestoneExpanded = false;
            applyMilestoneFilter(button.dataset.hitoFilter || 'todos');
        });
    });
    milestoneToggle?.addEventListener('click', () => {
        milestoneExpanded = !milestoneExpanded;
        if (milestoneExpanded) currentMilestoneFilter = 'todos';
        applyMilestoneFilter(currentMilestoneFilter);
    });
    applyMilestoneFilter(currentMilestoneFilter);
});

/* ── Exportar tabla a CSV (descarga real del navegador) ─────────────────── */
function exportarTablaCSV(tablaID, filename = 'reporte.csv') {
    const csv = [];
    const rows = document.querySelectorAll('table#' + tablaID + ' tr');

    for (let i = 0; i < rows.length; i++) {
        if (rows[i].id === 'bdayEmpty') continue;
        if (rows[i].dataset.agendaMatch === '0') continue;
        if (rows[i].hidden && rows[i].dataset.agendaMatch === undefined) continue;
        const row = [];
        const cols = rows[i].querySelectorAll('td, th');
        // Ignorar la última columna (Acciones) para el export
        for (let j = 0; j < cols.length - 1; j++) {
            row.push('"' + cols[j].innerText.trim().replace(/"/g, '""') + '"');
        }
        csv.push(row.join(','));
    }

    const BOM = '\uFEFF'; // Para que Excel abra con acentos correctamente
    const csvFile = new Blob([BOM + csv.join('\n')], { type: 'text/csv;charset=utf-8;' });
    const downloadLink = document.createElement('a');
    downloadLink.download = filename;
    downloadLink.href = window.URL.createObjectURL(csvFile);
    downloadLink.style.display = 'none';
    document.body.appendChild(downloadLink);
    downloadLink.click();
    document.body.removeChild(downloadLink);
    if (typeof showToast === 'function') showToast('✅ Archivo descargado: ' + filename, 'success');
}
</script>
<?php require_once ROOT . '/shared/footer_scripts.php'; ?>
</body>
</html>
