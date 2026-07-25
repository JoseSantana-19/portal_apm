<?php /* historial.php – Vista: Historial laboral jerárquico del funcionario */ ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial Laboral | Talento Humano APM</title>
    <meta name="description" content="Historial laboral cronológico del funcionario con trazabilidad de fusiones organizacionales — Autoridad Portuaria de Manta.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Space+Grotesk:wght@500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/variables.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/layout.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/toast.css">
    <style>
        /* ── Línea de tiempo del historial ─────────────────────────────── */
        .historial-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        .historial-header h3 { margin: 0; font-size: 1.15rem; }

        .filtro-cargo-form {
            display: flex;
            gap: .5rem;
            align-items: center;
            flex-wrap: wrap;
        }
        .filtro-cargo-form input[type="text"] {
            padding: .45rem .9rem;
            border-radius: var(--radius-md, 8px);
            border: 1px solid var(--border, #ddd);
            font-size: .875rem;
            background: var(--surface, #fff);
            color: var(--text, #333);
            min-width: 220px;
        }

        /* Timeline */
        .timeline { position: relative; padding-left: 2.25rem; margin-top: 1.5rem; }
        .timeline::before {
            content: '';
            position: absolute;
            left: .85rem;
            top: 0; bottom: 0;
            width: 2px;
            background: linear-gradient(to bottom, var(--primary, #0d6efd), var(--accent, #6c757d));
            border-radius: 99px;
        }

        .timeline-item {
            position: relative;
            margin-bottom: 2rem;
            animation: fadeSlideIn .4s ease both;
        }
        .timeline-item:last-child { margin-bottom: 0; }

        /* Punto de la línea de tiempo */
        .timeline-dot {
            position: absolute;
            left: -1.65rem;
            top: .3rem;
            width: 1.1rem;
            height: 1.1rem;
            border-radius: 50%;
            background: var(--primary, #0d6efd);
            border: 2px solid #fff;
            box-shadow: 0 0 0 3px color-mix(in srgb, var(--primary, #0d6efd) 25%, transparent);
        }
        .timeline-item.actual .timeline-dot {
            background: #198754;
            box-shadow: 0 0 0 3px rgba(25,135,84,.25);
            animation: pulseDot 1.8s ease-in-out infinite;
        }

        @keyframes pulseDot {
            0%, 100% { box-shadow: 0 0 0 3px rgba(25,135,84,.25); }
            50%       { box-shadow: 0 0 0 7px rgba(25,135,84,.08); }
        }
        @keyframes fadeSlideIn {
            from { opacity: 0; transform: translateY(12px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* Tarjeta de período */
        .periodo-card {
            background: var(--surface, #fff);
            border: 1px solid var(--border, #e0e0e0);
            border-radius: var(--radius-lg, 12px);
            padding: 1.1rem 1.3rem;
            transition: box-shadow .2s;
        }
        .periodo-card:hover { box-shadow: 0 4px 18px rgba(0,0,0,.08); }

        .periodo-card.actual-card {
            border-color: #198754;
            background: color-mix(in srgb, #198754 5%, var(--surface, #fff));
        }

        .periodo-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
            flex-wrap: wrap;
        }
        .periodo-direccion {
            font-weight: 700;
            font-size: 1rem;
            color: var(--text, #1a1a2e);
        }
        .periodo-sub-area {
            font-size: .82rem;
            color: var(--text-muted, #6c757d);
            margin-top: .15rem;
        }
        .badge-anios {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            padding: .3rem .75rem;
            background: color-mix(in srgb, var(--primary,#0d6efd) 12%, transparent);
            color: var(--primary,#0d6efd);
            border-radius: 99px;
            font-size: .78rem;
            font-weight: 600;
            white-space: nowrap;
        }
        .badge-anios.vigente {
            background: rgba(25,135,84,.12);
            color: #198754;
        }

        .periodo-meta {
            display: flex;
            gap: 1.2rem;
            margin-top: .65rem;
            flex-wrap: wrap;
        }
        .periodo-meta span {
            font-size: .8rem;
            color: var(--text-muted, #6c757d);
            display: flex;
            align-items: center;
            gap: .3rem;
        }
        .periodo-meta span b { color: var(--text, #333); font-weight: 600; }

        /* Fusión / alianza */
        .fusion-badge {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            margin-top: .6rem;
            padding: .3rem .8rem;
            border-radius: 8px;
            background: rgba(255,193,7,.13);
            color: #856404;
            font-size: .78rem;
            font-weight: 500;
        }

        /* Estado sin resultados */
        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: var(--text-muted, #6c757d);
        }
        .empty-state i { font-size: 2.5rem; margin-bottom: .75rem; display: block; }

        /* Log info – visible solo en desarrollo */
        .log-info-dev {
            margin-top: 1.5rem;
            padding: .75rem 1rem;
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 8px;
            font-size: .78rem;
            color: #664d03;
        }
    </style>
</head>
<body>
<div id="overlay" class="overlay" onclick="closeSidebar()"></div>
<button class="sidebar-open-btn" id="sidebarOpenBtn" onclick="openSidebar()" title="Abrir menú lateral" aria-label="Abrir menú lateral">
    <i class="bi bi-layout-sidebar"></i>
</button>

<div class="app">
    <?php require_once ROOT . '/shared/menu.php'; ?>

    <section class="content">
        <!-- TOPBAR -->
        <header class="topbar">
            <div class="topbar-left">
                <div class="brand">
                    <img src="<?= LOGO_URL ?>/logoapm.png" alt="Logo APM">
                    <div>
                        <h1>Autoridad Portuaria de Manta</h1>
                        <p>Modulo Talento Humano — Historial Laboral</p>
                    </div>
                </div>
            </div>
            <div class="topbar-actions">
                <div class="icon-chip">
                    <i class="bi bi-calendar-event"></i>
                    <span id="currentDate">--</span>
                </div>
                <button class="icon-btn notify" title="Notificaciones">
                    <i class="bi bi-bell"></i>
                    <span class="notify-dot"></span>
                </button>
                <div class="user-pill">
                    <span>Usuario Talento Humano</span>
                    <small>APM</small>
                </div>
            </div>
        </header>

        <main class="main">
            <div class="content-shell">

                <!-- HERO -->
                <section class="hero">
                    <div>
                        <div class="hero-kicker">Trazabilidad organizacional</div>
                        <h2>Historial Laboral Jerárquico</h2>
                        <p>Consulta el recorrido cronológico de cada funcionario, incluyendo fusiones y alianzas entre direcciones.</p>
                        <div class="hero-actions">
                            <a href="<?= BASE_URL ?>/talento-humano" class="btn btn-ghost">
                                <i class="bi bi-arrow-left"></i> Volver al directorio
                            </a>
                        </div>
                    </div>
                </section>

                <!-- FILTROS -->
                <section class="card table-card">
                    <div class="historial-header">
                        <div>
                            <h3><i class="bi bi-clock-history"></i> Filtrar historial por cargo</h3>
                            <p style="margin:0;font-size:.83rem;color:var(--text-muted);">
                                Ej: "DIRECTOR", "GERENTE", "ANALISTA" — búsqueda segura anti-SQLi
                            </p>
                        </div>

                        <!-- Formulario GET con un solo parámetro visible: seguro, no modifica datos -->
                        <form method="GET" action="<?= BASE_URL ?>/talento-humano/reporte" class="filtro-cargo-form" id="filtroCargoForm">
                            <input type="text"
                                   id="inputCargo"
                                   name="cargo"
                                   placeholder="Filtrar por cargo..."
                                   value="<?= htmlspecialchars($filtro_cargo ?? '') ?>"
                                   autocomplete="off">
                            <button type="submit" class="btn btn-primary" id="btnFiltrar">
                                <i class="bi bi-funnel"></i> Filtrar
                            </button>
                            <?php if ($filtro_cargo): ?>
                                <a href="<?= BASE_URL ?>/talento-humano/reporte" class="btn btn-outline" id="btnLimpiar">
                                    <i class="bi bi-x-circle"></i> Limpiar
                                </a>
                            <?php endif; ?>
                        </form>
                    </div>

                    <!-- LÍNEA DE TIEMPO DEL HISTORIAL -->
                    <?php if (empty($historial)): ?>
                        <div class="empty-state">
                            <i class="bi bi-inbox"></i>
                            <h4>No hay registros de historial</h4>
                            <p>
                                <?= $filtro_cargo
                                    ? 'No se encontraron funcionarios con el cargo "' . htmlspecialchars($filtro_cargo) . '".'
                                    : 'No hay datos de historial laboral en el sistema aún.' ?>
                            </p>
                        </div>
                    <?php else: ?>

                        <?php
                        // Agrupa el historial por funcionario para mostrar una línea de tiempo por persona
                        $porFuncionario = [];
                        foreach ($historial as $fila) {
                            $key = $fila['cedula'];
                            $porFuncionario[$key]['info'] = [
                                'cedula'      => $fila['cedula'],
                                'funcionario' => $fila['funcionario'],
                            ];
                            $porFuncionario[$key]['periodos'][] = $fila;
                        }
                        ?>

                        <?php foreach ($porFuncionario as $persona): ?>
                            <div class="periodo-card" style="margin-bottom:1.5rem;">
                                <!-- Encabezado del funcionario -->
                                <div style="display:flex;align-items:center;gap:.75rem;margin-bottom:1rem;padding-bottom:.75rem;border-bottom:1px solid var(--border,#eee);">
                                    <div class="avatar" style="width:2.5rem;height:2.5rem;background:var(--primary,#0d6efd);color:#fff;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:1.1rem;flex-shrink:0;">
                                        <?= mb_strtoupper(mb_substr($persona['info']['funcionario'], 0, 1)) ?>
                                    </div>
                                    <div>
                                        <div style="font-weight:700;font-size:1rem;"><?= htmlspecialchars($persona['info']['funcionario']) ?></div>
                                        <small style="color:var(--text-muted);">C.I. <?= htmlspecialchars($persona['info']['cedula']) ?></small>
                                    </div>
                                </div>

                                <!-- Línea de tiempo de períodos -->
                                <div class="timeline">
                                    <?php foreach ($persona['periodos'] as $idx => $p):
                                        $esActual   = empty($p['fecha_hasta']);   // fecha_hasta NULL = cargo vigente
                                        $anios      = (int)($p['anios_permanencia'] ?? 0);
                                        $hayFusion  = !empty($p['direccion_actual_unificada'])
                                                      && $p['departamento_historico'] !== $p['direccion_actual_unificada'];
                                        $subArea    = $p['sub_area'] ?? null;
                                        $dirPadre   = $p['direccion_padre'] ?? $p['departamento_historico'];
                                    ?>
                                        <div class="timeline-item <?= $esActual ? 'actual' : '' ?>"
                                             style="animation-delay:<?= $idx * 0.08 ?>s">
                                            <div class="timeline-dot"></div>

                                            <div class="periodo-card <?= $esActual ? 'actual-card' : '' ?>">
                                                <div class="periodo-head">
                                                    <div>
                                                        <!-- Dirección (padre o única) -->
                                                        <div class="periodo-direccion">
                                                            <i class="bi bi-building"></i>
                                                            <?= htmlspecialchars($dirPadre) ?>
                                                        </div>
                                                        <!-- Departamento sub-área (si existe) -->
                                                        <?php if ($subArea): ?>
                                                            <div class="periodo-sub-area">
                                                                <i class="bi bi-diagram-3"></i>
                                                                Departamento: <b><?= htmlspecialchars($subArea) ?></b>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>

                                                    <!-- Badge de años -->
                                                    <span class="badge-anios <?= $esActual ? 'vigente' : '' ?>">
                                                        <i class="bi bi-<?= $esActual ? 'star-fill' : 'hourglass-split' ?>"></i>
                                                        <?= $esActual ? 'Cargo actual' : $anios . ' año' . ($anios !== 1 ? 's' : '') ?>
                                                    </span>
                                                </div>

                                                <!-- Metadatos del período -->
                                                <div class="periodo-meta">
                                                    <span>
                                                        <i class="bi bi-briefcase"></i>
                                                        <b><?= htmlspecialchars($p['nombre_puesto']) ?></b>
                                                    </span>
                                                    <span>
                                                        <i class="bi bi-calendar-check"></i>
                                                        Desde: <b><?= htmlspecialchars(date('d/m/Y', strtotime($p['fecha_desde']))) ?></b>
                                                    </span>
                                                    <?php if (!$esActual): ?>
                                                        <span>
                                                            <i class="bi bi-calendar-x"></i>
                                                            Hasta: <b><?= htmlspecialchars(date('d/m/Y', strtotime($p['fecha_hasta']))) ?></b>
                                                        </span>
                                                    <?php else: ?>
                                                        <span style="color:#198754;">
                                                            <i class="bi bi-check-circle-fill"></i>
                                                            <b>Vigente a la fecha</b>
                                                        </span>
                                                    <?php endif; ?>
                                                </div>

                                                <!-- Etiqueta de fusión organizacional (si aplica) -->
                                                <?php if ($hayFusion): ?>
                                                    <div class="fusion-badge">
                                                        <i class="bi bi-diagram-2-fill"></i>
                                                        Actualmente unificada en:
                                                        <b><?= htmlspecialchars($p['direccion_actual_unificada']) ?></b>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div><!-- /timeline -->
                            </div><!-- /periodo-card -->
                        <?php endforeach; ?>

                    <?php endif; ?>

                    <?php
                    /**
                     * CONTROL DE ERRORES – LOG DIARIO POR MÓDULO
                     *
                     * Los errores de este módulo se guardan en:
                     *   modules/Talento_Humano/log/log_YYYY-MM-DD.txt
                     *
                     * El archivo se crea automáticamente por día desde Conexion::registrarErrorLog().
                     * En producción NUNCA se muestra esta sección al usuario.
                     * En desarrollo aparece el aviso amarillo de abajo.
                     *
                     * El acceso directo a /modules/.../log/*.txt está bloqueado por .htaccess (HTTP 403).
                     */
                    if (defined('ENTORNO') && ENTORNO === 'development'): ?>
                        <div class="log-info-dev">
                            <i class="bi bi-shield-lock-fill"></i>
                            <b>Dev Info — Control de errores:</b>
                            Los errores de este módulo se guardan en
                            <code>modules/Talento_Humano/log/log_<?= date('Y-m-d') ?>.txt</code>.
                            Este bloque no se muestra en producción.
                        </div>
                    <?php endif; ?>

                </section>
            </div>
        </main>
    </section>
</div>

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
<script>
/* Fecha en el topbar */
document.addEventListener('DOMContentLoaded', () => {
    const el = document.getElementById('currentDate');
    if (el) {
        const ahora = new Date();
        el.textContent = ahora.toLocaleDateString('es-EC', {
            weekday: 'short', year: 'numeric', month: 'short', day: 'numeric'
        });
    }
});
</script>
<?php require_once ROOT . '/shared/footer_scripts.php'; ?>
</body>
</html>
