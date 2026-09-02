<?php /* historial.php – Vista: Historial laboral jerárquico del funcionario */ ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial Laboral | Talento Humano APM</title>
    <meta name="description" content="Historial laboral cronológico del funcionario con trazabilidad de fusiones organizacionales — Autoridad Portuaria de Manta.">
    <?php require ROOT . '/shared/head_assets.php'; ?>
    <style>
        /* ── Línea de tiempo del historial ─────────────────────────────── */
        .historial-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 1rem;
            padding: .9rem 1rem;
            border-radius: 14px;
            background: color-mix(in srgb, var(--primary,#0d6efd) 5%, var(--surface,#fff));
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
        .periodo-detalle { cursor:pointer; }
        .periodo-detalle:focus-visible { outline:3px solid rgba(14,116,144,.3);outline-offset:3px; }
        .detalle-hint {font-size:.72rem;color:var(--primary,#0e7490);margin-top:.65rem;font-weight:700;}
        .history-modal {position:fixed;inset:0;z-index:1200;background:rgba(2,12,27,.7);display:none;align-items:center;justify-content:center;padding:20px;}
        .history-modal.open {display:flex;}
        .history-dialog {width:min(760px,100%);max-height:90vh;overflow:auto;background:var(--surface,#fff);color:var(--text,#1e293b);border-radius:20px;box-shadow:0 30px 90px rgba(0,0,0,.35);}
        .history-dialog-head {padding:20px 24px;background:linear-gradient(135deg,#0b3551,#0e7490);color:#fff;display:flex;justify-content:space-between;gap:14px;align-items:start;}
        .history-dialog-body {padding:22px 24px;}
        .history-detail-grid {display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;}
        .history-detail {padding:12px;border:1px solid var(--border,#dbe4ee);border-radius:12px;}
        .history-detail small {display:block;text-transform:uppercase;letter-spacing:.08em;color:var(--text-muted,#64748b);margin-bottom:4px;}
        @media(max-width:650px){.history-detail-grid{grid-template-columns:1fr}}

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
        <?php $topbarSubtitle='Talento Humano — Historial Laboral';$topbarShowSearch=true;require ROOT.'/shared/topbar.php'; ?>

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
                        $jornadasPorEmpleado=[];
                        foreach (($jornadasEspeciales ?? []) as $jornada) $jornadasPorEmpleado[(int)$jornada['empleado_id']][]=$jornada;
                        $vigenciasPorEmpleado=[];
                        foreach (($vigenciasLaborales ?? []) as $vigencia) $vigenciasPorEmpleado[(int)$vigencia['empleado_id']][]=$vigencia;
                        $eventosPorEmpleado=[];
                        foreach (($eventosLaborales ?? []) as $evento) $eventosPorEmpleado[(int)$evento['empleado_id']][]=$evento;
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

                                            <?php
                                            $detallePeriodo=$p;
                                            $inicioPeriodo=(string)($p['fecha_desde']??'0001-01-01');
                                            $finPeriodo=(string)($p['fecha_hasta']??'9999-12-31');
                                             $detallePeriodo['jornadas_especiales']=array_values(array_filter(
                                                $jornadasPorEmpleado[(int)$p['empleado_id']] ?? [],
                                                static fn(array $j):bool=>(string)($j['fecha_hasta']??'0001-01-01') >= $inicioPeriodo
                                                    && (string)($j['fecha_desde']??'9999-12-31') <= $finPeriodo
                                             ));
                                             $detallePeriodo['vigencias_laborales']=array_values(array_filter(
                                                 $vigenciasPorEmpleado[(int)$p['empleado_id']] ?? [],
                                                 static fn(array $v):bool=>(string)($v['fecha_hasta']??'9999-12-31') >= $inicioPeriodo
                                                     && (string)($v['fecha_desde']??'9999-12-31') <= $finPeriodo
                                             ));
                                             $detallePeriodo['eventos_laborales']=array_values(array_filter(
                                                 $eventosPorEmpleado[(int)$p['empleado_id']] ?? [],
                                                 static fn(array $v):bool=>(string)($v['fecha_evento']??'9999-12-31') >= $inicioPeriodo
                                                     && (string)($v['fecha_evento']??'0001-01-01') <= $finPeriodo
                                             ));
                                            ?>
                                            <div class="periodo-card periodo-detalle <?= $esActual ? 'actual-card' : '' ?>" role="button" tabindex="0"
                                                 aria-label="Ver detalle del periodo de <?= htmlspecialchars($p['nombre_puesto']) ?>"
                                                 data-periodo="<?= htmlspecialchars(json_encode($detallePeriodo,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),ENT_QUOTES,'UTF-8') ?>">
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
                                                <div class="detalle-hint"><i class="bi bi-eye"></i> Ver detalle completo del periodo</div>
                                                <?php if(!empty($detallePeriodo['eventos_laborales'])): ?>
                                                    <div style="margin-top:8px;display:flex;gap:6px;flex-wrap:wrap">
                                                        <span class="badge-anios"><i class="bi bi-activity"></i> <?= count($detallePeriodo['eventos_laborales']) ?> evento(s)</span>
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

<div class="history-modal" id="historyModal" role="dialog" aria-modal="true" aria-labelledby="historyModalTitle">
    <div class="history-dialog">
        <div class="history-dialog-head"><div><small>Detalle del periodo laboral</small><h3 id="historyModalTitle" style="margin:4px 0 0"></h3></div><button type="button" class="btn btn-ghost" id="closeHistoryModal" aria-label="Cerrar"><i class="bi bi-x-lg"></i></button></div>
        <div class="history-dialog-body"><div class="history-detail-grid" id="historyDetailGrid"></div><div id="historyEvents"></div><div id="historyAssignments"></div><div id="historySchedules"></div></div>
    </div>
</div>


<?php if (!empty($_GET['msg'])): ?>
<script>
    window.addEventListener('DOMContentLoaded', () => {
        showToast(<?= json_encode(htmlspecialchars($_GET['msg'])) ?>, <?= ($_GET['ok'] ?? '0') === '1' ? "'success'" : "'error'" ?>);
    });
</script>
<?php endif; ?>

<script>
/* Fecha en el topbar */
document.addEventListener('DOMContentLoaded', () => {
    const modal=document.getElementById('historyModal');
    const abrir=card=>{
        const p=JSON.parse(card.dataset.periodo||'{}');
        document.getElementById('historyModalTitle').textContent=`${p.nombre_puesto||'Cargo'} — ${p.departamento_historico||''}`;
        const formato=valor=>valor===null||valor===undefined||valor===''?'No registrado':String(valor);
        const fecha=valor=>valor?new Date(`${valor}T12:00:00`).toLocaleDateString('es-EC'):'Vigente';
        const campos=[['Dirección / área',p.departamento_historico],['Cargo / puesto',p.nombre_puesto],['Periodo',`${fecha(p.fecha_desde)} — ${fecha(p.fecha_hasta)}`],['Tipo de contrato',p.tipo_contrato],['Proceso institucional',p.proceso_institucional],['Nivel de gestión',p.nivel_gestion],['Lugar de trabajo',p.lugar_trabajo],['Grupo ocupacional',p.grupo_ocupacional],['Grado',p.grado_laboral],['Partida individual',p.partida_individual],['RMU',p.sueldo_rmu?`$${Number(p.sueldo_rmu).toFixed(2)}`:'No registrada'],['Jornada',`${formato(p.jornada)} · ${formato(p.horas_jornada)} h`],['Condición especial',p.condicion_especial],['Origen',p.accion_id?`Acción de Personal #${p.accion_id}`:(p.movimiento_id?`Movimiento interno #${p.movimiento_id}`:'Registro del expediente')],['Observaciones',p.observaciones]];
        document.getElementById('historyDetailGrid').innerHTML=campos.map(([k,v])=>`<div class="history-detail"><small>${k}</small><strong>${formato(v).replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]))}</strong></div>`).join('');
        const jornadas=p.jornadas_especiales||[];
        document.getElementById('historySchedules').innerHTML=jornadas.length?`<h4 style="margin:22px 0 10px">Jornadas temporales registradas</h4>${jornadas.map(j=>`<div class="history-detail" style="margin-bottom:8px"><strong>${formato(j.tipo_novedad)}</strong><br><small>${fecha(j.fecha_desde)} — ${fecha(j.fecha_hasta)} · ${formato(j.horas_diarias)} horas · ${formato(j.numero_accion)}</small></div>`).join('')}`:'';
        const vigencias=p.vigencias_laborales||[];
        document.getElementById('historyAssignments').innerHTML=vigencias.length?`<h4 style="margin:22px 0 10px">Vigencias laborales y retornos</h4>${vigencias.map(v=>`<div class="history-detail" style="margin-bottom:8px"><strong>${formato(v.tipo_vigencia)} · ${formato(v.estado)}</strong><br><small>${fecha(v.fecha_desde)} — ${fecha(v.fecha_hasta)} · ${formato(v.area_propuesta)} · ${formato(v.cargo_propuesto)} · ${formato(v.numero_accion)}</small><br><small>Situación de retorno: ${formato(v.area_original)} · ${formato(v.cargo_original)}</small></div>`).join('')}`:'';
        const eventos=p.eventos_laborales||[];
        // Las jornadas ya tienen una sección detallada con horas y vigencia. Se
        // conservan como eventos en SQL, pero no se dibujan dos veces en el modal.
        const eventosVisibles=eventos.filter(e=>e.categoria!=='JORNADA');
        const etiquetas={ACCION_PERSONAL:'Acción de Personal',VACACIONES:'Vacaciones',JORNADA:'Jornada temporal',MOVIMIENTO_INTERNO:'Movimiento interno',FORMULARIO:'Formulario registrado',DOCUMENTO_FIRMADO:'Documento firmado'};
        const escapeHtml=valor=>formato(valor).replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]));
        const enlaceEvento=e=>{const id=Number(e.origen_id||0);if(!id)return'';let url='';if(e.origen_tipo==='ACCION_PERSONAL')url=`<?= BASE_URL ?>/talento-humano/accion-personal/ver?id=${id}`;if(e.origen_tipo==='ESTUDIO_SOCIOECONOMICO')url=`<?= BASE_URL ?>/talento-humano/estudio-seguridad?estudio_id=${id}`;if(e.origen_tipo==='PAZ_SALVO')url=`<?= BASE_URL ?>/talento-humano/paz-salvo/ver?id=${id}`;if(e.origen_tipo==='DOCUMENTO_FIRMADO')url=`<?= BASE_URL ?>/talento-humano/documentos-firmados/descargar?id=${id}`;return url?`<a class="btn btn-outline" style="margin-top:8px" href="${url}" target="_blank"><i class="bi bi-box-arrow-up-right"></i> Abrir respaldo</a>`:''};
        document.getElementById('historyEvents').innerHTML=eventosVisibles.length?`<h4 style="margin:22px 0 10px">Eventos del expediente</h4>${eventosVisibles.map(e=>`<div class="history-detail" style="margin-bottom:8px"><small>${escapeHtml(etiquetas[e.categoria]||e.categoria)} · ${escapeHtml(e.estado)}</small><strong style="display:block;margin-top:4px">${escapeHtml(e.subtipo||e.titulo)}${e.numero_documento?` · ${escapeHtml(e.numero_documento)}`:''}</strong><small>${fecha(e.fecha_desde)}${e.fecha_hasta?` — ${fecha(e.fecha_hasta)}`:''}${e.detalle?` · ${escapeHtml(e.detalle)}`:''}</small>${enlaceEvento(e)}</div>`).join('')}`:'<h4 style="margin:22px 0 10px">Eventos del expediente</h4><p style="color:var(--text-muted)">No existen novedades adicionales dentro de este periodo.</p>';
        modal.classList.add('open');document.body.style.overflow='hidden';document.getElementById('closeHistoryModal').focus();
    };
    document.querySelectorAll('.periodo-detalle').forEach(card=>{card.addEventListener('click',()=>abrir(card));card.addEventListener('keydown',e=>{if(e.key==='Enter'||e.key===' '){e.preventDefault();abrir(card)}})});
    const cerrar=()=>{modal.classList.remove('open');document.body.style.overflow=''};
    document.getElementById('closeHistoryModal')?.addEventListener('click',cerrar);modal?.addEventListener('click',e=>{if(e.target===modal)cerrar()});document.addEventListener('keydown',e=>{if(e.key==='Escape')cerrar()});
});
</script>
<?php require_once ROOT . '/shared/footer_scripts.php'; ?>
</body>
</html>
