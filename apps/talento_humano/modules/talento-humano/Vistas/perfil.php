<?php
/* Expediente digital completo del funcionario (solo lectura). */
$empleado = is_array($empleado ?? null) ? $empleado : [];
if (!$empleado) {
    ErrorHandler::abort(404, 'El funcionario solicitado no existe.');
}

$e = static fn(string $valor): string => htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
$valor = static function (array $origen, array $campos, string $alternativa = 'No registrado'): string {
    foreach ($campos as $campo) {
        if (isset($origen[$campo]) && trim((string)$origen[$campo]) !== '') {
            return trim((string)$origen[$campo]);
        }
    }
    return $alternativa;
};
$fecha = static function ($dato): string {
    if ($dato === null || trim((string)$dato) === '') return 'No registrada';
    $marca = strtotime((string)$dato);
    return $marca ? date('d/m/Y', $marca) : (string)$dato;
};

$cedula = $valor($empleado, ['cedula', 'identificacion'], 'Sin identificación');
$apellidos = $valor($empleado, ['apellidos'], '');
$nombres = $valor($empleado, ['nombres'], '');
$nombreCompleto = trim($apellidos.' '.$nombres) ?: 'Funcionario sin nombre registrado';
$iniciales = strtoupper(substr($apellidos ?: 'U', 0, 1).substr($nombres ?: 'A', 0, 1));
$fechaIngreso = !empty($empleado['fecha_ingreso']) ? new DateTimeImmutable((string)$empleado['fecha_ingreso']) : null;
$fechaNacimiento = !empty($empleado['fecha_nacimiento']) ? new DateTimeImmutable((string)$empleado['fecha_nacimiento']) : null;
$estadoActivo = (int)($empleado['estado'] ?? 0) === 1;
$estadoTexto = $estadoActivo ? 'Activo' : 'Inactivo';
$situacionTemporal = !empty($empleado['situacion_temporal']);
$vigenciaTemporalHasta = $empleado['vigencia_hasta'] ?? $empleado['jornada_hasta'] ?? null;
$aniosInstitucion = isset($antiguedad['anios_servicio']) ? (float)$antiguedad['anios_servicio'] : ($fechaIngreso ? (float)$fechaIngreso->diff(new DateTimeImmutable('today'))->y : null);
$edad = $fechaNacimiento ? $fechaNacimiento->diff(new DateTimeImmutable('today'))->y : null;

$nacionalidades = [];
foreach (($nacionalidadesEmpleado ?? []) as $nacionalidad) {
    $nombre = trim((string)($nacionalidad['nombre'] ?? ''));
    if ($nombre !== '') $nacionalidades[] = $nombre;
}
if (!$nacionalidades && trim((string)($empleado['nacionalidad'] ?? '')) !== '') {
    $nacionalidades[] = trim((string)$empleado['nacionalidad']);
}
$nacionalidadTexto = $nacionalidades ? implode(', ', array_unique($nacionalidades)) : 'No registrada';

$cuenta = $valor($empleado, ['numero_cuenta_bancaria', 'cuenta_bancaria'], '');
$cuentaEnmascarada = $cuenta === '' ? 'No registrada' : str_repeat('•', max(4, strlen($cuenta) - 4)).substr($cuenta, -4);
$remuneracion = (float)($empleado['remuneracion_mensual'] ?? $empleado['sueldo_rmu'] ?? 0);
$remuneracionTexto = $remuneracion > 0 ? '$'.number_format($remuneracion, 2, '.', ',') : 'No registrada';
$porcentajeDiscapacidad = trim((string)($empleado['porcentaje_discapacidad'] ?? ''));
$discapacidadTexto = $valor($empleado, ['tipo_discapacidad'], 'No registrada');
if ($porcentajeDiscapacidad !== '' && (float)$porcentajeDiscapacidad > 0) {
    $discapacidadTexto .= ' ('.number_format((float)$porcentajeDiscapacidad, 2).'% )';
}

$fotoRuta = trim((string)($empleado['ruta_foto'] ?? ''));
$fotoUrl = '';
if ($fotoRuta !== '') {
    $fotoUrl = preg_match('~^https?://~i', $fotoRuta)
        ? $fotoRuta
        : BASE_URL.'/'.ltrim(str_replace('\\', '/', $fotoRuta), '/');
}

$historialPerfil = [];
foreach (($historial ?? []) as $fila) {
    $historialPerfil[] = [
        'periodo' => $fecha($fila['fecha_desde'] ?? null).' – '.(!empty($fila['fecha_hasta']) ? $fecha($fila['fecha_hasta']) : 'Actualidad'),
        'direccion' => $valor($fila, ['direccion_actual_unificada', 'departamento_historico', 'nombre_unidad'], 'Sin asignación'),
        'cargo' => $valor($fila, ['nombre_puesto', 'cargo'], 'Sin denominación'),
    ];
}
if (!$historialPerfil) {
    $historialPerfil[] = [
        'periodo' => $fechaIngreso ? $fechaIngreso->format('d/m/Y').' – Actualidad' : 'Sin historial registrado',
        'direccion' => $valor($empleado, ['direccion_area'], 'Sin asignación organizacional'),
        'cargo' => $valor($empleado, ['cargo'], 'Sin denominación asignada'),
    ];
}

$secciones = [
    [
        'titulo' => 'Información laboral e institucional', 'icono' => 'bi-building',
        'campos' => [
            ['Código de empleado', $valor($empleado, ['cod_emplea'], 'No registrado'), 'bi-hash'],
            ['Dirección / Área', $valor($empleado, ['direccion_area'], 'Sin asignación'), 'bi-building'],
            ['Proceso institucional', $valor($empleado, ['proceso_institucional','tipo_proceso'], 'No registrado'), 'bi-diagram-3'],
            ['Nivel de gestión', $valor($empleado, ['nivel_gestion'], 'No registrado'), 'bi-diagram-2'],
            ['Cargo / Denominación', $valor($empleado, ['cargo'], 'Sin denominación'), 'bi-briefcase'],
            ['Lugar de trabajo', $valor($empleado, ['lugar_trabajo'], 'No registrado'), 'bi-geo'],
            ['Grupo ocupacional', $valor($empleado, ['grupo_ocupacional'], 'No registrado'), 'bi-people'],
            ['Grado laboral', $valor($empleado, ['grado_laboral'], 'No registrado'), 'bi-bar-chart-steps'],
            ['Partida individual', $valor($empleado, ['partida_individual'], 'No registrada'), 'bi-receipt'],
            ['Régimen laboral', strtoupper((string)($empleado['regimen_laboral'] ?? 'LOSEP')) === 'CODIGO_TRABAJO' ? 'Código del Trabajo' : 'LOSEP', 'bi-shield-check'],
            ['Tipo de contrato', $valor($empleado, ['tipo_contrato'], 'No especificado'), 'bi-file-earmark-text'],
            ['Jornada', $valor($empleado, ['jornada'], 'No registrada').' · '.$valor($empleado, ['horas_jornada'], '0').' horas', 'bi-clock'],
            ['Fecha de ingreso', $fecha($empleado['fecha_ingreso'] ?? null), 'bi-calendar-check'],
            ['Fecha de salida', $fecha($empleado['fecha_salida'] ?? null), 'bi-calendar-x'],
            ['RMU mensual', $remuneracionTexto, 'bi-currency-dollar', 'info-card-value--money'],
            ['Estado laboral', $estadoTexto, $estadoActivo ? 'bi-check-circle' : 'bi-x-circle', $estadoActivo ? 'info-card-value--success' : 'info-card-value--danger'],
            ['Fecha efectiva del estado', $fecha($empleado['estado_fecha_efectiva'] ?? null), 'bi-calendar-event'],
            ['Motivo / origen del estado', $valor($empleado, ['estado_motivo', 'estado_origen'], 'No registrado'), 'bi-info-circle', 'info-card--wide'],
        ],
    ],
    [
        'titulo' => 'Información personal', 'icono' => 'bi-person-vcard',
        'campos' => [
            ['Tipo de identificación', $valor($empleado, ['tipo_identificacion'], 'Cédula'), 'bi-card-text'],
            ['Identificación', $cedula, 'bi-fingerprint'],
            ['Apellidos', $apellidos ?: 'No registrados', 'bi-person'],
            ['Nombres', $nombres ?: 'No registrados', 'bi-person'],
            ['Fecha de nacimiento', $fecha($empleado['fecha_nacimiento'] ?? null), 'bi-cake2'],
            ['Edad', $edad !== null ? $edad.' años' : 'No registrada', 'bi-calendar3'],
            ['Nacionalidad', $nacionalidadTexto, 'bi-globe-americas'],
            ['Sexo / Género', $valor($empleado, ['sexo'], 'No registrado'), 'bi-person-standing'],
            ['Estado civil', $valor($empleado, ['estado_civil'], 'No registrado'), 'bi-people'],
            ['Tipo de sangre', $valor($empleado, ['tipo_sangre'], 'No registrado'), 'bi-droplet'],
            ['Cargas familiares', (string)($empleado['cargas_familiares'] ?? 0), 'bi-person-hearts'],
            ['Condición especial', $valor($empleado, ['condicion_especial'], 'Ninguna registrada'), 'bi-universal-access'],
        ],
    ],
    [
        'titulo' => 'Contacto y domicilio', 'icono' => 'bi-telephone',
        'campos' => [
            ['Correo institucional', $valor($empleado, ['correo_institucional'], 'No registrado'), 'bi-envelope-at'],
            ['Correo personal', $valor($empleado, ['correo_personal'], 'No registrado'), 'bi-envelope'],
            ['Teléfono móvil', $valor($empleado, ['telefono_movil'], 'No registrado'), 'bi-phone'],
            ['Teléfono convencional', $valor($empleado, ['telefono_convencional'], 'No registrado'), 'bi-telephone'],
            ['Ciudad de residencia', $valor($empleado, ['ciudad_residencia'], 'No registrada'), 'bi-geo-alt'],
            ['Dirección domiciliaria', $valor($empleado, ['direccion_domiciliaria'], 'No registrada'), 'bi-house-door', 'info-card--wide'],
        ],
    ],
    [
        'titulo' => 'Emergencia, formación y condición especial', 'icono' => 'bi-shield-plus',
        'campos' => [
            ['Contacto de emergencia', $valor($empleado, ['contacto_emergencia'], 'No registrado'), 'bi-person-exclamation'],
            ['Parentesco', $valor($empleado, ['emergencia_relacion'], 'No registrado'), 'bi-people'],
            ['Teléfono de emergencia', $valor($empleado, ['tel_emergencia'], 'No registrado'), 'bi-telephone-forward'],
            ['Nivel de estudio', $valor($empleado, ['nivel_estudio'], 'No registrado'), 'bi-mortarboard'],
            ['Título', $valor($empleado, ['titulo'], 'No registrado'), 'bi-award', 'info-card--wide'],
            ['Discapacidad', $discapacidadTexto, 'bi-universal-access-circle'],
        ],
    ],
    [
        'titulo' => 'Información administrativa', 'icono' => 'bi-bank',
        'campos' => [
            ['Institución bancaria', $valor($empleado, ['institucion_bancaria'], 'No registrada'), 'bi-bank'],
            ['Tipo de cuenta', $valor($empleado, ['tipo_cuenta_bancaria'], 'No registrado'), 'bi-wallet2'],
            ['Número de cuenta', $cuentaEnmascarada, 'bi-credit-card-2-front'],
            ['Código IESS', $valor($empleado, ['codigo_iess', 'num_iess'], 'No registrado'), 'bi-person-badge'],
        ],
    ],
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expediente · <?= $e($nombreCompleto) ?> | APM</title>
    <meta name="description" content="Expediente digital del funcionario en la Autoridad Portuaria de Manta.">
    <?php require ROOT . '/shared/head_assets.php'; ?>
</head>
<body>
<div id="overlay" class="overlay" onclick="closeSidebar()"></div>
<button class="sidebar-open-btn" id="sidebarOpenBtn" onclick="openSidebar()" title="Abrir menú lateral" aria-label="Abrir menú lateral"><i class="bi bi-layout-sidebar"></i></button>

<div class="app">
    <?php require_once ROOT . '/shared/menu.php'; ?>
    <section class="content">
        <?php $topbarShowSearch=true; $topbarBackUrl=BASE_URL.'/talento-humano/directorio'; $topbarBackLabel='Volver al Directorio'; require ROOT.'/shared/topbar.php'; ?>
        <main class="main">
            <div class="content-shell profile-page">
                <div class="readonly-badge"><i class="bi bi-lock-fill"></i> Expediente digital en modo de consulta. La edición se realiza desde el Nómina de Personal.</div>

                <section class="perfil-hero">
                    <div class="perfil-avatar" aria-label="Fotografía o iniciales del funcionario">
                        <?php if ($fotoUrl !== ''): ?>
                            <img src="<?= $e($fotoUrl) ?>" alt="Foto de <?= $e($nombreCompleto) ?>">
                        <?php else: ?>
                            <?= $e($iniciales) ?>
                        <?php endif; ?>
                    </div>
                    <div class="perfil-hero-data">
                        <span class="perfil-eyebrow">Expediente institucional</span>
                        <h1><?= $e($nombreCompleto) ?></h1>
                        <p><?= $e($valor($empleado, ['cargo'], 'Sin denominación')) ?> · <?= $e($valor($empleado, ['direccion_area'], 'Sin asignación')) ?></p>
                        <div class="perfil-chips">
                            <span class="perfil-chip <?= $estadoActivo ? 'activo' : 'inactivo' ?>"><i class="bi <?= $estadoActivo ? 'bi-check-circle-fill' : 'bi-x-circle-fill' ?>"></i> <?= $e($estadoTexto) ?></span>
                            <?php if ($aniosInstitucion !== null): ?><span class="perfil-chip"><i class="bi bi-calendar3"></i> <?= number_format($aniosInstitucion,1) ?> años efectivos · <?= (int)($antiguedad['periodos_vinculacion']??1) ?> período(s)</span><?php endif; ?>
                            <span class="perfil-chip"><i class="bi bi-file-earmark-person"></i> <?= $e($cedula) ?></span>
                            <?php if ($situacionTemporal): ?>
                            <span class="perfil-chip"><i class="bi bi-arrow-counterclockwise"></i> Situación temporal<?= $vigenciaTemporalHasta ? ' hasta '.$e($fecha($vigenciaTemporalHasta)) : '' ?> · retorno automático</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="perfil-actions">
                        <?php if (Auth::can('acciones', 'crear')): ?>
                        <a href="<?= BASE_URL ?>/talento-humano/accion-personal?cedula=<?= urlencode($cedula) ?>" class="perfil-action"><i class="bi bi-file-earmark-text"></i> Acción de Personal</a>
                        <?php endif; ?>
                        <?php if (Auth::can('empleados', 'editar')): ?>
                        <a href="<?= BASE_URL ?>/talento-humano/empleado/editar?id=<?= (int)$empleado['empleado_id'] ?>" class="perfil-action"><i class="bi bi-pencil-square"></i> Editar expediente</a>
                        <?php endif; ?>
                        <a target="_blank" href="<?= BASE_URL ?>/talento-humano/empleado/imprimir-ficha?id=<?= (int)$empleado['empleado_id'] ?>" class="perfil-action"><i class="bi bi-file-earmark-pdf"></i> Exportar PDF</a>
                        <?php if(Auth::can('documentos_firmados','visualizar')): ?>
                        <a href="<?= BASE_URL ?>/talento-humano/documentos-firmados?tipo=FICHA_PERSONAL&amp;origen_id=<?= (int)$empleado['empleado_id'] ?>" class="perfil-action"><i class="bi bi-file-earmark-check"></i> Documento firmado</a>
                        <?php endif; ?>
                    </div>
                </section>

                <?php foreach ($secciones as $seccion): ?>
                <section class="card profile-section">
                    <div class="profile-section-heading">
                        <span class="profile-section-icon"><i class="bi <?= $e($seccion['icono']) ?>"></i></span>
                        <div><h2><?= $e($seccion['titulo']) ?></h2><p>Información registrada en el expediente institucional.</p></div>
                    </div>
                    <div class="profile-info-grid">
                        <?php foreach ($seccion['campos'] as $campo): ?>
                        <?php $modificador = $campo[3] ?? ''; ?>
                        <article class="profile-info-card <?= $modificador === 'info-card--wide' ? 'info-card--wide' : '' ?>">
                            <div class="profile-info-label"><i class="bi <?= $e($campo[2]) ?>"></i> <?= $e($campo[0]) ?></div>
                            <div class="profile-info-value <?= $modificador !== 'info-card--wide' ? $e($modificador) : '' ?>"><?= $e($campo[1]) ?></div>
                        </article>
                        <?php endforeach; ?>
                    </div>
                </section>
                <?php endforeach; ?>

                <?php if (trim((string)($empleado['observaciones'] ?? '')) !== ''): ?>
                <section class="card profile-section">
                    <div class="profile-section-heading"><span class="profile-section-icon"><i class="bi bi-journal-text"></i></span><div><h2>Observaciones internas</h2><p>Notas registradas en el expediente.</p></div></div>
                    <div class="profile-note"><?= nl2br($e(trim((string)$empleado['observaciones']))) ?></div>
                </section>
                <?php endif; ?>

                <section class="card profile-section">
                    <div class="profile-section-heading"><span class="profile-section-icon"><i class="bi bi-calendar-range"></i></span><div><h2>Períodos de vinculación</h2><p>La antigüedad excluye los intervalos en que el funcionario no mantuvo relación con la institución.</p></div></div>
                    <div class="profile-timeline">
                    <?php foreach(($periodosVinculacion??[]) as $periodo): ?><article class="historial-item"><div class="historial-dot"></div><div class="historial-line"><strong><?= $e($periodo['tipo_ingreso']??'Período laboral') ?></strong><small><?= $e($periodo['motivo_salida']??(!empty($periodo['fecha_hasta'])?'Período finalizado':'Vínculo vigente')) ?></small><span class="historial-period"><i class="bi bi-calendar-range"></i> <?= $e($fecha($periodo['fecha_desde']??null)) ?> – <?= !empty($periodo['fecha_hasta'])?$e($fecha($periodo['fecha_hasta'])):'Actualidad' ?></span></div></article><?php endforeach; ?>
                    </div>
                    <?php if(!empty($hitosServicio)): ?><div class="profile-audit-note"><i class="bi bi-award"></i> Hito de reconocimiento en <?= InstitutionalClock::today()->format('Y') ?>: <?= (int)$hitosServicio[0]['hito_anios'] ?> años efectivos, el <?= $e($fecha($hitosServicio[0]['fecha_hito'])) ?>.</div><?php endif; ?>
                </section>

                <section class="card profile-section">
                    <div class="profile-section-heading"><span class="profile-section-icon"><i class="bi bi-clock-history"></i></span><div><h2>Historial laboral en la APM</h2><p>Asignaciones institucionales registradas cronológicamente.</p></div></div>
                    <div class="profile-timeline">
                        <?php foreach ($historialPerfil as $h): ?>
                        <article class="historial-item">
                            <div class="historial-dot"></div>
                            <div class="historial-line"><strong><?= $e($h['cargo']) ?></strong><small><?= $e($h['direccion']) ?></small><span class="historial-period"><i class="bi bi-calendar-range"></i> <?= $e($h['periodo']) ?></span></div>
                        </article>
                        <?php endforeach; ?>
                    </div>
                    <div class="profile-audit-note"><i class="bi bi-shield-check"></i> Consulta registrada mediante la auditoría institucional del sistema.</div>
                </section>
            </div>
        </main>
    </section>
</div>
<?php require_once ROOT . '/shared/footer_scripts.php'; ?>
</body>
</html>
