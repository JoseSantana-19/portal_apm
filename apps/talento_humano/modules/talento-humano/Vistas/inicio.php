<?php
/* inicio.php – Vista: Pantalla principal de Inicio (Dashboard de RRHH)
   Muestra métricas generales + tabla de Próximos Cumpleaños con alertas de color. */

// ── Datos simulados (Mock Data) para probar la interfaz ─────────────────────
// Fecha base: hoy (30 mayo 2026)
$proximos_cumpleanos = [
    [
        'nombre'           => 'ZAMBRANO DELGADO HECTOR',
        'departamento'     => 'Tecnologías de la Información',
        'cargo'            => 'Jefe de Sistemas',
        'fecha_nacimiento' => '30 de Mayo',
        'alerta'           => 'HOY',
        'color_alerta'     => 'danger',   // Rojo
        'icono'            => 'bi-cake2',
        'initials'         => 'ZH',
    ],
    [
        'nombre'           => 'PEREZ MORALES JUAN CARLOS',
        'departamento'     => 'Financiero',
        'cargo'            => 'Economista',
        'fecha_nacimiento' => '31 de Mayo',
        'alerta'           => 'MAÑANA',
        'color_alerta'     => 'warning',  // Naranja
        'icono'            => 'bi-bell',
        'initials'         => 'PJ',
    ],
    [
        'nombre'           => 'TORRES VEGA ANA MARIA',
        'departamento'     => 'Talento Humano',
        'cargo'            => 'Analista de RRHH',
        'fecha_nacimiento' => '14 de Junio',
        'alerta'           => 'EN 15 DÍAS',
        'color_alerta'     => 'info',     // Celeste
        'icono'            => 'bi-calendar-event',
        'initials'         => 'TA',
    ],
    [
        'nombre'           => 'PALMA TEJENA MICHAEL',
        'departamento'     => 'Operaciones Portuarias',
        'cargo'            => 'Supervisor',
        'fecha_nacimiento' => '29 de Junio',
        'alerta'           => 'EN 30 DÍAS',
        'color_alerta'     => 'primary',  // Índigo
        'icono'            => 'bi-calendar-range',
        'initials'         => 'PM',
    ],
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
$hoy_bday  = count(array_filter($proximos_cumpleanos, fn($c) => $c['alerta'] === 'HOY'));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicio | Talento Humano – APM</title>
    <meta name="description" content="Panel de inicio de Talento Humano: métricas de personal y alertas de cumpleaños próximos.">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/variables.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/layout.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/toast.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Space+Grotesk:wght@500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
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
        <!-- ── TOPBAR ── -->
        <header class="topbar">
            <div class="topbar-left">
                <div class="brand">
                    <img src="<?= LOGO_URL ?>/logoapm.png" alt="Logo APM">
                    <div>
                        <h1>Autoridad Portuaria de Manta</h1>
                        <p>Módulo Talento Humano</p>
                    </div>
                </div>
            </div>
            <div class="topbar-actions">
                <div class="search">
                    <i class="bi bi-search"></i>
                    <input type="search" id="globalSearch" placeholder="Buscar en toda la plataforma...">
                </div>
                <div class="icon-chip">
                    <i class="bi bi-calendar-event"></i>
                    <span id="currentDate">--</span>
                </div>
                <button class="icon-btn notify" id="btnNotify" title="Notificaciones">
                    <i class="bi bi-bell"></i>
                    <?php if ($hoy_bday > 0): ?>
                    <span class="notify-dot"></span>
                    <?php endif; ?>
                </button>
                <div class="user-pill">
                    <span><?= htmlspecialchars($usuarioNombre ?? 'Usuario TH') ?></span>
                    <small>APM</small>
                </div>
            </div>
        </header>

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
                        <div class="metric-card metric-card--activos">
                            <div class="metric-label">
                                <i class="bi bi-person-check"></i> Activos
                            </div>
                            <div class="metric-value"><?= $activos ?></div>
                            <div class="metric-foot">En funciones hoy</div>
                        </div>
                        <div class="metric-card metric-card--permisos">
                            <div class="metric-label">
                                <i class="bi bi-calendar-x"></i> Permisos
                            </div>
                            <div class="metric-value"><?= $permisos ?></div>
                            <div class="metric-foot">Con permisos vigentes</div>
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
                    <div class="action-card" onclick="location.href='<?= BASE_URL ?>/talento-humano/empleado/crear'" id="ac-nuevo">
                        <i class="bi bi-person-plus-fill"></i>
                        <div>
                            <h4>Nuevo Expediente</h4>
                            <p>Registrar funcionario</p>
                        </div>
                    </div>
                    <div class="action-card" onclick="location.href='<?= BASE_URL ?>/talento-humano/directorio'" id="ac-directorio">
                        <i class="bi bi-card-list"></i>
                        <div>
                            <h4>Directorio</h4>
                            <p>Ver listado de personal</p>
                        </div>
                    </div>
                    <div class="action-card" id="ac-reportes">
                        <i class="bi bi-graph-up-arrow"></i>
                        <div>
                            <h4>Reportes</h4>
                            <p>Exportar y analizar datos</p>
                        </div>
                    </div>
                    <div class="action-card" id="ac-auditoria">
                        <i class="bi bi-journal-text"></i>
                        <div>
                            <h4>Auditoría</h4>
                            <p>Logs de actividad del sistema</p>
                        </div>
                    </div>
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
                                <?= count($proximos_cumpleanos) ?> próximos
                            </span>
                            <?php if ($hoy_bday > 0): ?>
                            <span class="bday-alert-chip">
                                <i class="bi bi-exclamation-circle-fill"></i>
                                <?= $hoy_bday ?> cumple HOY
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="table-wrap">
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
                            <?php
// Cédulas simuladas para los botones Ver Perfil (modo prototipo)
$cedulas_mock = [
    'ZAMBRANO DELGADO HECTOR' => '1308126646',
    'PEREZ MORALES JUAN CARLOS' => '1311567890',
    'TORRES VEGA ANA MARIA' => '0923456781',
    'PALMA TEJENA MICHAEL' => '1309876543',
];
?>
<?php foreach ($proximos_cumpleanos as $i => $f):
    $nombreKey = $f['nombre'];
    $cedula_perfil = '';
    foreach ($cedulas_mock as $k => $v) {
        if (str_contains($nombreKey, explode(' ', $k)[0])) { $cedula_perfil = $v; break; }
    }
?>
                                <tr class="table-row bday-row bday-row--<?= $f['color_alerta'] ?>"
                                    style="animation-delay:<?= $i * 0.08 ?>s">
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
                                        <i class="bi bi-calendar3 bday-cal-icon"></i>
                                        <?= htmlspecialchars($f['fecha_nacimiento']) ?>
                                    </td>
                                    <!-- ALERTA -->
                                    <td>
                                        <span class="bday-badge bday-badge--<?= $f['color_alerta'] ?>">
                                            <i class="bi <?= htmlspecialchars($f['icono']) ?>"></i>
                                            <?= htmlspecialchars($f['alerta']) ?>
                                        </span>
                                    </td>
                                    <!-- ACCIONES -->
                                    <td class="action-cell">
                                        <a href="<?= BASE_URL ?>/talento-humano/empleado/perfil/<?= htmlspecialchars($cedula_perfil ?: '1308126646') ?>"
                                           class="btn btn-outline bday-btn-perfil"
                                           title="Ver expediente de <?= htmlspecialchars($f['nombre']) ?>"
                                           id="btn-perfil-<?= $i ?>">
                                            <i class="bi bi-person-lines-fill"></i> Ver Perfil
                                        </a>
                                        <?php if ($f['alerta'] === 'HOY'): ?>
                                        <button class="btn bday-btn-felicitar"
                                                title="Enviar felicitación"
                                                id="btn-felicitar-<?= $i ?>">
                                            <i class="bi bi-envelope-heart"></i>
                                        </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Leyenda de colores -->
                    <div class="bday-leyenda">
                        <span class="bday-badge bday-badge--danger"><i class="bi bi-circle-fill"></i> HOY</span>
                        <span class="bday-badge bday-badge--warning"><i class="bi bi-circle-fill"></i> MAÑANA</span>
                        <span class="bday-badge bday-badge--info"><i class="bi bi-circle-fill"></i> EN 15 DÍAS</span>
                        <span class="bday-badge bday-badge--primary"><i class="bi bi-circle-fill"></i> EN 30 DÍAS</span>
                    </div>
                </section>

            </div><!-- /content-shell -->
        </main>
    </section>
</div><!-- /app -->

<div id="toastContainer" class="toast-container"></div>

<?php if (!empty($_GET['msg'])): ?>
<script>
    window.addEventListener('DOMContentLoaded', () => {
        showToast(<?= json_encode(htmlspecialchars($_GET['msg'])) ?>, <?= ($_GET['ok'] ?? '0') === '1' ? "'success'" : "'error'" ?>);
    });
</script>
<?php endif; ?>

<script src="<?= BASE_URL ?>/public/js/layout_sidebar.js"></script>
<script src="<?= BASE_URL ?>/public/js/toast.js"></script>
<script src="<?= BASE_URL ?>/public/js/talento_humano.js"></script>
<script>
/* ── Fecha en topbar ── */
document.addEventListener('DOMContentLoaded', () => {
    const el = document.getElementById('currentDate');
    if (el) {
        el.textContent = new Date().toLocaleDateString('es-EC', {
            weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
        });
    }

    /* Botones de felicitar (simulado) */
    document.querySelectorAll('.bday-btn-felicitar').forEach(btn => {
        btn.addEventListener('click', () => {
            if (typeof showToast === 'function') showToast('¡Felicitación enviada por correo! 🎉', 'success');
        });
    });
});

/* ── Exportar tabla a CSV (descarga real del navegador) ─────────────────── */
function exportarTablaCSV(tablaID, filename = 'reporte.csv') {
    const csv = [];
    const rows = document.querySelectorAll('table#' + tablaID + ' tr');

    for (let i = 0; i < rows.length; i++) {
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
