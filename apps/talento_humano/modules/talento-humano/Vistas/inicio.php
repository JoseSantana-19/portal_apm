<?php
/* inicio.php – Vista: Pantalla principal de Inicio (Dashboard de RRHH)
   Muestra métricas generales + tabla de Próximos Cumpleaños con alertas de color. */

$proximos_cumpleanos = [];
$hoy = new DateTimeImmutable('today');
foreach (($empleados ?? []) as $fila) {
    if ((int)($fila['estado'] ?? 0) !== 1 || empty($fila['fecha_nacimiento'])) continue;
    try {
        $nacimiento = new DateTimeImmutable((string)$fila['fecha_nacimiento']);
        $cumple = $nacimiento->setDate((int)$hoy->format('Y'),(int)$nacimiento->format('m'),(int)$nacimiento->format('d'));
        if ($cumple < $hoy) $cumple=$cumple->modify('+1 year');
        $dias=(int)$hoy->diff($cumple)->format('%a');
        $alerta=$dias===0?'HOY':($dias===1?'MAÑANA':"EN {$dias} DÍAS");
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
    } catch (Throwable) {}
}
usort($proximos_cumpleanos,fn($a,$b)=>$a['dias']<=>$b['dias']);
$proximos_cumpleanos=array_values(array_filter($proximos_cumpleanos,fn($c)=>(int)$c['dias']<=30));
$conteoCumpleanos = [
    'hoy' => count(array_filter($proximos_cumpleanos, fn($c) => (int)$c['dias'] === 0)),
    'manana' => count(array_filter($proximos_cumpleanos, fn($c) => (int)$c['dias'] === 1)),
    '15' => count(array_filter($proximos_cumpleanos, fn($c) => (int)$c['dias'] <= 15)),
    '30' => count($proximos_cumpleanos),
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
                        <div class="hero-actions">
                            <a href="<?= BASE_URL ?>/talento-humano/empleado/crear" class="btn btn-primary" id="btn-nuevo-expediente">
                                <i class="bi bi-person-plus"></i> Nuevo expediente
                            </a>
                            <a href="<?= BASE_URL ?>/talento-humano/directorio" class="btn btn-outline" id="btn-ver-directorio">
                                <i class="bi bi-people"></i> Ver directorio
                            </a>
                            <button class="btn btn-ghost" id="btn-exportar"
                                    onclick="exportarTablaCSV('tablaCumpleanos', 'Reporte_Cumpleanos_APM.csv')">
                                <i class="bi bi-file-earmark-excel"></i> Exportar
                            </button>
                        </div>
                    </div>

                    <!-- MÉTRICAS DE PERSONAL -->
                    <div class="metrics">
                        <div class="metric-card metric-card--registrados">
                            <div class="metric-label">
                                <i class="bi bi-people-fill"></i> Registrados
                            </div>
                            <div class="metric-value"><?= $total ?></div>
                            <div class="metric-foot">Directorio APM</div>
                        </div>
                        <a class="metric-card metric-card--activos metric-card--link"
                           href="<?= BASE_URL ?>/talento-humano/directorio?estado=Activo"
                           aria-label="Ver empleados activos">
                            <div class="metric-label">
                                <i class="bi bi-person-check"></i> Activos
                            </div>
                            <div class="metric-value"><?= $activos ?></div>
                            <div class="metric-foot">En funciones hoy</div>
                        </a>
                        <div class="metric-card metric-card--permisos">
                            <div class="metric-label">
                                <i class="bi bi-person-x"></i> Inactivos
                            </div>
                            <div class="metric-value"><?= $inactivos ?></div>
                            <div class="metric-foot">Histórico institucional</div>
                        </div>
                        <div class="metric-card metric-card--cumples">
                            <div class="metric-label">
                                <i class="bi bi-cake2"></i> Cumpleaños hoy
                            </div>
                            <div class="metric-value"><?= $hoy_bday ?></div>
                            <div class="metric-foot">Felicitar hoy</div>
                        </div>
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

                <!-- ════════════════════════════════════════════
                     BLOQUE INFERIOR: PRÓXIMOS CUMPLEAÑOS
                ════════════════════════════════════════════ -->
                <section class="card bday-card" id="seccion-cumpleanos">
                    <div class="card-header bday-header">
                        <div>
                            <h3><i class="bi bi-cake2 bday-icon-title"></i> Próximos Cumpleaños</h3>
                            <p>Funcionarios que cumplen años en los próximos 30 días — ¡No olvides felicitarlos!</p>
                        </div>
                        <div class="bday-header-right">
                            <span class="chip">
                                <i class="bi bi-calendar-heart"></i>
                                <span id="bdayVisibleCount"><?= count($proximos_cumpleanos) ?></span> próximos
                            </span>
                            <?php if ($hoy_bday > 0): ?>
                            <span class="bday-alert-chip">
                                <i class="bi bi-exclamation-circle-fill"></i>
                                <?= $hoy_bday ?> cumple HOY
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="bday-filters" aria-label="Filtrar cumpleaños por rango">
                        <button type="button" class="bday-filter-button bday-filter-button--danger" data-bday-filter="hoy">
                            <span>Hoy</span><strong><?= $conteoCumpleanos['hoy'] ?></strong>
                        </button>
                        <button type="button" class="bday-filter-button bday-filter-button--warning" data-bday-filter="manana">
                            <span>Mañana</span><strong><?= $conteoCumpleanos['manana'] ?></strong>
                        </button>
                        <button type="button" class="bday-filter-button bday-filter-button--info" data-bday-filter="15">
                            <span>Próximos 15 días</span><strong><?= $conteoCumpleanos['15'] ?></strong>
                        </button>
                        <button type="button" class="bday-filter-button bday-filter-button--primary is-active" data-bday-filter="30" aria-pressed="true">
                            <span>Próximos 30 días</span><strong><?= $conteoCumpleanos['30'] ?></strong>
                        </button>
                    </div>

                    <div class="table-wrap bday-table-wrap">
                        <table id="tablaCumpleanos" aria-label="Tabla de próximos cumpleaños">
                            <thead>
                                <tr>
                                    <th>Funcionario</th>
                                    <th>Departamento / Cargo</th>
                                    <th>Fecha de Cumpleaños</th>
                                    <th>Estado / Alerta</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($proximos_cumpleanos as $i => $f): ?>
                                <tr class="table-row bday-row bday-row--<?= $f['color_alerta'] ?>"
                                    data-bday-days="<?= (int)$f['dias'] ?>">
                                    <!-- FUNCIONARIO -->
                                    <td>
                                        <div class="name-cell">
                                            <div class="bday-avatar bday-avatar--<?= $f['color_alerta'] ?>">
                                                <?= htmlspecialchars($f['initials']) ?>
                                            </div>
                                            <div class="name-meta">
                                                <span><?= htmlspecialchars($f['nombre']) ?></span>
                                                <small><?= htmlspecialchars($f['cargo']) ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <!-- DEPARTAMENTO -->
                                    <td>
                                        <span class="dept-pill"><?= htmlspecialchars($f['departamento']) ?></span>
                                    </td>
                                    <!-- FECHA -->
                                    <td class="bday-fecha-cell">
                                        <span class="bday-date">
                                            <i class="bi bi-calendar3 bday-cal-icon"></i>
                                            <?= htmlspecialchars($f['fecha_nacimiento']) ?>
                                        </span>
                                    </td>
                                    <!-- ALERTA -->
                                    <td>
                                        <span class="bday-badge bday-badge--<?= $f['color_alerta'] ?>">
                                            <i class="bi <?= htmlspecialchars($f['icono']) ?>"></i>
                                            <?= htmlspecialchars($f['alerta']) ?>
                                        </span>
                                    </td>
                                    <!-- ACCIONES -->
                                    <td class="bday-actions-cell">
                                        <a href="<?= BASE_URL ?>/talento-humano/empleado/perfil/<?= htmlspecialchars($f['cedula']) ?>"
                                           class="btn btn-outline bday-btn-perfil"
                                           title="Ver expediente de <?= htmlspecialchars($f['nombre']) ?>"
                                           id="btn-perfil-<?= $i ?>">
                                            <i class="bi bi-person-lines-fill"></i> Ver Perfil
                                        </a>
                                        <?php if ($f['alerta'] === 'HOY' && !empty($f['correo'])): ?>
                                        <a class="btn bday-btn-felicitar" href="mailto:<?= htmlspecialchars($f['correo']) ?>?subject=Feliz%20cumpleaños"
                                                title="Redactar felicitación" id="btn-felicitar-<?= $i ?>">
                                            <i class="bi bi-envelope-heart"></i>
                                        </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                                <tr id="bdayEmpty" class="bday-empty" hidden>
                                    <td colspan="5"><i class="bi bi-calendar2-check"></i> No hay cumpleaños en el rango seleccionado.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="bday-summary">
                        <i class="bi bi-info-circle"></i>
                        La lista incluye a todos los funcionarios activos con cumpleaños dentro de los próximos 30 días.
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

    const filterButtons = [...document.querySelectorAll('[data-bday-filter]')];
    const birthdayRows = [...document.querySelectorAll('#tablaCumpleanos tbody tr[data-bday-days]')];
    const visibleCount = document.getElementById('bdayVisibleCount');
    const emptyRow = document.getElementById('bdayEmpty');

    const applyBirthdayFilter = (filter) => {
        let shown = 0;
        birthdayRows.forEach((row) => {
            const days = Number(row.dataset.bdayDays);
            const visible = filter === 'hoy' ? days === 0
                : filter === 'manana' ? days === 1
                : days <= Number(filter);
            row.hidden = !visible;
            if (visible) shown++;
        });
        filterButtons.forEach((button) => {
            const active = button.dataset.bdayFilter === filter;
            button.classList.toggle('is-active', active);
            button.setAttribute('aria-pressed', active ? 'true' : 'false');
        });
        if (visibleCount) visibleCount.textContent = String(shown);
        if (emptyRow) emptyRow.hidden = shown !== 0;
    };

    filterButtons.forEach((button) => {
        button.addEventListener('click', () => applyBirthdayFilter(button.dataset.bdayFilter || '30'));
    });
});

/* ── Exportar tabla a CSV (descarga real del navegador) ─────────────────── */
function exportarTablaCSV(tablaID, filename = 'reporte.csv') {
    const csv = [];
    const rows = document.querySelectorAll('table#' + tablaID + ' tr');

    for (let i = 0; i < rows.length; i++) {
        if (rows[i].hidden || rows[i].id === 'bdayEmpty') continue;
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
