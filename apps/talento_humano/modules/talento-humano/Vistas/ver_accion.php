<?php
/* ver_accion.php – Vista: Documento de Acción de Personal (Solo Lectura)
   Muestra el documento guardado de una Acción de Personal con opción de
   imprimir el PDF oficial y volver al directorio. */

$a   = $accion    ?? [];
$nro = $a['numero_accion'] ?? "Sin número";

// Formatear fechas
$fmtFecha = function(?string $ts): string {
    if (empty($ts)) return '—';
    return date('d/m/Y', strtotime($ts));
};
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acción de Personal <?= htmlspecialchars($nro) ?> | Talento Humano APM</title>
    <meta name="description" content="Documento de Acción de Personal <?= htmlspecialchars($nro) ?> — Autoridad Portuaria de Manta.">
    <?php require ROOT . '/shared/head_assets.php'; ?>
    <style>
        /* ── Vista del documento guardado ───────────────────────────── */
        .doc-header {
            background: linear-gradient(135deg, var(--navy-800, #1e3a5f) 0%, #1e3a5f 100%);
            border-radius: 20px; padding: 22px 28px;
            display: flex; align-items: center; justify-content: space-between;
            flex-wrap: wrap; gap: 14px; margin-bottom: 24px;
        }
        .doc-header h2 { color: #fff; font-size: 1.3rem; margin: 0; }
        .doc-header p  { color: rgba(255,255,255,.7); font-size: .82rem; margin: 2px 0 0; }
        .doc-num {
            background: rgba(18,180,199,.22); border: 1px solid rgba(18,180,199,.4);
            color: #a5f3fc; padding: 8px 20px; border-radius: 999px;
            font-size: .9rem; font-weight: 700; letter-spacing: .06em;
        }
        .doc-card {
            background: #fff; border: 1px solid var(--line, #e2e8f0);
            border-radius: var(--radius-lg, 16px); overflow: hidden;
            box-shadow: var(--shadow-md, 0 4px 24px rgba(0,0,0,.08));
            margin-bottom: 20px;
        }
        .doc-card-header {
            padding: 16px 24px; background: linear-gradient(135deg, #f8fbff, #f1f7ff);
            border-bottom: 1px solid var(--line, #e2e8f0);
            display: flex; align-items: center; gap: 10px;
        }
        .doc-card-header h3 { margin: 0; font-size: .95rem; color: var(--navy-900, #0f172a); }
        .doc-card-header .card-icon {
            width: 36px; height: 36px; border-radius: 10px;
            background: rgba(14,116,144,.12); color: var(--ocean-700, #0e7490);
            display: grid; place-items: center; font-size: 1.1rem; flex-shrink: 0;
        }
        .doc-body { padding: 20px 24px; }
        .doc-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 16px; }
        .doc-field { }
        .doc-field .label {
            font-size: .72rem; font-weight: 700; letter-spacing: .12em;
            text-transform: uppercase; color: var(--ink-600, #64748b); margin-bottom: 4px;
        }
        .doc-field .value {
            font-size: .9rem; font-weight: 600; color: var(--navy-900, #0f172a);
            background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px;
            padding: 8px 12px; min-height: 38px;
        }
        .situacion-grid {
            display: grid; grid-template-columns: 1fr 1fr; gap: 20px;
        }
        .situacion-block {
            border: 1px solid var(--line, #e2e8f0); border-radius: 12px; overflow: hidden;
        }
        .situacion-head {
            padding: 10px 16px; font-size: .78rem; font-weight: 700;
            letter-spacing: .1em; text-transform: uppercase;
        }
        .situacion-head.actual   { background: rgba(15,23,42,.05); color: var(--navy-900); }
        .situacion-head.propuesta{ background: rgba(14,116,144,.1); color: var(--ocean-700); }
        .situacion-body { padding: 14px 16px; }
        .tipo-badge {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 6px 16px; border-radius: 999px; font-size: .82rem; font-weight: 700;
            background: rgba(99,102,241,.12); color: #4338ca;
            border: 1px solid rgba(99,102,241,.25);
        }
        .readonly-banner {
            background: rgba(245,158,11,.08); border: 1px solid rgba(245,158,11,.3);
            border-radius: 10px; padding: 10px 16px; display: flex; align-items: center;
            gap: 10px; font-size: .83rem; color: #92400e; margin-bottom: 20px;
        }
        @media (max-width: 680px) {
            .situacion-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<div id="overlay" class="overlay" onclick="closeSidebar()"></div>
<button class="sidebar-open-btn" id="sidebarOpenBtn" onclick="openSidebar()" title="Abrir menú lateral"
        aria-label="Abrir menú lateral">
    <i class="bi bi-layout-sidebar"></i>
</button>

<div class="app">
    <?php require_once ROOT . '/shared/menu.php'; ?>

    <section class="content">
        <?php $topbarShowSearch=false;$topbarBackUrl=BASE_URL.'/talento-humano/biblioteca';$topbarBackLabel='Volver a Biblioteca';require ROOT.'/shared/topbar.php'; ?>

        <main class="main">
            <div class="content-shell">

                <?php if (empty($a)): ?>
                    <!-- Estado vacío -->
                    <div style="text-align:center; padding:60px 20px;">
                        <i class="bi bi-file-earmark-x" style="font-size:3rem; color:var(--ink-600);"></i>
                        <h3 style="margin:16px 0 8px;">Documento no encontrado</h3>
                        <p style="color:var(--ink-600);">La Acción de Personal con ID <?= (int)($accion_id ?? 0) ?> no existe o fue eliminada.</p>
                        <a href="<?= BASE_URL ?>/talento-humano/directorio" class="btn btn-primary" style="margin-top:16px;">
                            <i class="bi bi-arrow-left"></i> Volver al directorio
                        </a>
                    </div>
                <?php else: ?>

                    <!-- Encabezado del documento -->
                    <div class="doc-header">
                        <div>
                            <h2><i class="bi bi-file-earmark-text-fill"></i> Acción de Personal</h2>
                            <p>Documento oficial registrado en el sistema. Solo lectura.</p>
                        </div>
                        <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
                            <span class="doc-num"><?= htmlspecialchars($nro) ?></span>
                            <a href="<?= BASE_URL ?>/talento-humano/accion-personal/imprimir-accion?id=<?= (int)($accion_id ?? 0) ?>"
                               target="_blank" class="btn btn-primary" id="btn-imprimir-accion">
                                <i class="bi bi-printer"></i> Imprimir PDF
                            </a>
                            <a href="<?= BASE_URL ?>/talento-humano/accion-personal" class="btn btn-outline">
                                <i class="bi bi-plus-circle"></i> Nueva Acción
                            </a>
                        </div>
                    </div>

                    <div class="readonly-banner">
                        <i class="bi bi-lock-fill"></i>
                        Este documento es de solo lectura. Para modificarlo, genere una nueva Acción de Personal.
                    </div>

                    <!-- Sección 1: Datos del Servidor -->
                    <div class="doc-card">
                        <div class="doc-card-header">
                            <div class="card-icon"><i class="bi bi-person-badge"></i></div>
                            <h3>Datos del Servidor Público</h3>
                        </div>
                        <div class="doc-body">
                            <div class="doc-grid">
                                <div class="doc-field">
                                    <div class="label">Apellidos</div>
                                    <div class="value"><?= htmlspecialchars($a['apellidos'] ?? '—') ?></div>
                                </div>
                                <div class="doc-field">
                                    <div class="label">Nombres</div>
                                    <div class="value"><?= htmlspecialchars($a['nombres'] ?? '—') ?></div>
                                </div>
                                <div class="doc-field">
                                    <div class="label">Cédula de Identidad</div>
                                    <div class="value"><?= htmlspecialchars($a['identificacion'] ?? '—') ?></div>
                                </div>
                                <div class="doc-field">
                                    <div class="label">Tipo de Acción</div>
                                    <div class="value">
                                        <span class="tipo-badge">
                                            <i class="bi bi-tag"></i>
                                            <?= htmlspecialchars($a['tipo_accion'] ?? '—') ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="doc-field">
                                    <div class="label">Rige Desde</div>
                                    <div class="value"><?= htmlspecialchars($fmtFecha($a['fecha_rige_desde'] ?? null)) ?></div>
                                </div>
                                <div class="doc-field">
                                    <div class="label">Rige Hasta</div>
                                    <div class="value"><?= htmlspecialchars(!empty($a['fecha_rige_hasta']) ? $fmtFecha($a['fecha_rige_hasta']) : 'PERMANENTE') ?></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sección 2: Situación Actual vs Propuesta -->
                    <div class="doc-card">
                        <div class="doc-card-header">
                            <div class="card-icon"><i class="bi bi-arrow-left-right"></i></div>
                            <h3>Comparativo de Situación</h3>
                        </div>
                        <div class="doc-body">
                            <div class="situacion-grid">
                                <!-- Situación Actual -->
                                <div class="situacion-block">
                                    <div class="situacion-head actual">
                                        <i class="bi bi-circle-fill" style="font-size:.55rem;"></i> Situación Actual
                                    </div>
                                    <div class="situacion-body">
                                        <div class="doc-field" style="margin-bottom:12px;">
                                            <div class="label">Unidad / Área</div>
                                            <div class="value"><?= htmlspecialchars($a['actual_unidad'] ?? '—') ?></div>
                                        </div>
                                        <div class="doc-field" style="margin-bottom:12px;">
                                            <div class="label">Puesto / Cargo</div>
                                            <div class="value"><?= htmlspecialchars($a['actual_puesto'] ?? '—') ?></div>
                                        </div>
                                        <div class="doc-field">
                                            <div class="label">Remuneración Mensual</div>
                                            <div class="value">$ <?= htmlspecialchars(number_format((float)($a['actual_remuneracion'] ?? 0), 2)) ?></div>
                                        </div>
                                    </div>
                                </div>
                                <!-- Situación Propuesta -->
                                <div class="situacion-block">
                                    <div class="situacion-head propuesta">
                                        <i class="bi bi-arrow-right-circle" style="font-size:.9rem;"></i> Situación Propuesta
                                    </div>
                                    <div class="situacion-body">
                                        <div class="doc-field" style="margin-bottom:12px;">
                                            <div class="label">Unidad / Área</div>
                                            <div class="value"><?= htmlspecialchars($a['propuesta_unidad'] ?? '—') ?></div>
                                        </div>
                                        <div class="doc-field" style="margin-bottom:12px;">
                                            <div class="label">Puesto / Cargo</div>
                                            <div class="value"><?= htmlspecialchars($a['propuesta_puesto'] ?? '—') ?></div>
                                        </div>
                                        <div class="doc-field">
                                            <div class="label">Remuneración Mensual</div>
                                            <div class="value">$ <?= htmlspecialchars(number_format((float)($a['propuesta_remuneracion'] ?? 0), 2)) ?></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sección 3: Motivación Legal -->
                    <?php if (!empty($a['explicacion_legal'])): ?>
                    <div class="doc-card">
                        <div class="doc-card-header">
                            <div class="card-icon"><i class="bi bi-book"></i></div>
                            <h3>Motivación Legal</h3>
                        </div>
                        <div class="doc-body">
                            <div class="doc-field">
                                <div class="value" style="white-space:pre-wrap; min-height:60px;"><?= htmlspecialchars($a['explicacion_legal']) ?></div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Sección 4: Auditoría -->
                    <div class="doc-card">
                        <div class="doc-card-header">
                            <div class="card-icon"><i class="bi bi-clock-history"></i></div>
                            <h3>Trazabilidad del Documento</h3>
                        </div>
                        <div class="doc-body">
                            <div class="doc-grid">
                                <div class="doc-field">
                                    <div class="label">Número de Acción</div>
                                    <div class="value"><?= htmlspecialchars($nro) ?></div>
                                </div>
                                <div class="doc-field">
                                    <div class="label">Fecha de Elaboración</div>
                                    <div class="value"><?= htmlspecialchars($fmtFecha($a['fecha_elaboracion'] ?? null)) ?></div>
                                </div>
                                <div class="doc-field">
                                    <div class="label">Creado por</div>
                                    <div class="value"><?= htmlspecialchars($a['usuario_crea'] ?? '—') ?></div>
                                </div>
                                <div class="doc-field">
                                    <div class="label">ID del Registro</div>
                                    <div class="value"><?= (int)($accion_id ?? 0) ?></div>
                                </div>
                            </div>
                        </div>
                    </div>

                <?php endif; ?>
            </div>
        </main>
    </section>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const el = document.getElementById('currentDate');
    if (el) el.textContent = new Date().toLocaleDateString('es-EC', {
        weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
    });
});
</script>
<?php require_once ROOT . '/shared/footer_scripts.php'; ?>
</body>
</html>
