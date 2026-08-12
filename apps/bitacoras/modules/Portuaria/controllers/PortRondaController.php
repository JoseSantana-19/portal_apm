<?php
/**
 * Controlador de Bitácora de Rondas (Seguridad Operativa).
 *
 * Migración completa: index() sirve la página y api() reemplaza a
 * apis/rondas_bitacora_api.php (856 líneas originales) delegando cada
 * consulta a RondaModel. La lógica de negocio (ventanas de turno, fechas
 * operativas, ventana de edición) se preservó tal cual el original.
 */
class PortRondaController extends PortController
{
    public function index()
    {
        Auth::guard();
        if (!Auth::canAccederBitacoraRondas()) {
            $this->redirect('dashboard?msg=permiso_denegado');
            return;
        }

        require_once ROOT_PATH . '/includes/bit_system_config.php';
        require_once ROOT_PATH . '/includes/bit_auth_permissions.php';

        $conn = $this->conn();
        apm_config_tabla_ensure($conn);
        $diasEdicionGuardia = apm_config_get_dias_edicion_bitacora($conn);
        $diasEdicionBitacora = apm_bitacora_dias_edicion_permitidos($diasEdicionGuardia);
        $fechaMinimaEdicionBitacora = apm_bitacora_fecha_minima_edicion(null, $diasEdicionGuardia);

        $this->view('rondas/index', [
            'pageTitle' => 'Bitácora de rondas | Autoridad Portuaria de Manta',
            'diasEdicionGuardia' => $diasEdicionGuardia,
            'diasEdicionBitacora' => $diasEdicionBitacora,
            'fechaMinimaEdicionBitacora' => $fechaMinimaEdicionBitacora,
            'puedeConfigurarDias' => Auth::canConfigurarDiasBitacora(),
            'fechaMaxServidor' => date('Y-m-d'),
            'extraCss' => [$GLOBALS['url_toast_css'] ?? ''],
        ]);
    }

    /**
     * GET/POST /bitacoras/ronda/api — reemplaza apis/rondas_bitacora_api.php.
     * Fase 2 de la migración: mismo flujo, misma validación, mismas reglas de
     * ventana de turno/edición, ahora delegando cada consulta a RondaModel.
     */
    public function api()
    {
        Auth::guard();

        require_once ROOT_PATH . '/includes/bit_system_config.php';
        require_once ROOT_PATH . '/includes/bit_auth_permissions.php';
        require_once ROOT_PATH . '/includes/bit_apm_fecha_iso.php';

        /** @var RondaModel $model */
        $model = $this->model('PortRondaModel');

        if (!$model->tablasOk()) {
            $this->json(['ok' => false, 'message' => 'Ejecute la migración sql/migrations/20260410_rondas_bitacora.sql en PortuariaDemo.']);
        }

        $conn = $this->conn();
        if (!apm_config_tabla_ensure($conn)) {
            $this->json(['ok' => false, 'message' => 'No se pudo preparar la tabla de configuraciones del sistema.']);
        }

        if (!Auth::canAccederBitacoraRondas()) {
            $this->json(['ok' => false, 'message' => 'No tiene permiso para acceder a este módulo.'], 403);
        }

        $idUsuario = Auth::userId();
        if ($idUsuario <= 0) {
            $this->json(['ok' => false, 'message' => 'Sesión inválida.'], 401);
        }
        $esAdmin = Auth::isAdminArea();

        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $now = new DateTime('now');
        $ctx = PortRondaModel::turnoYFechaOperativa($now);
        $diasGuardiaCfg = apm_config_get_dias_edicion_bitacora($conn);
        $diasEdicionBitacora = apm_bitacora_dias_edicion_permitidos($diasGuardiaCfg);
        $fechaMinimaEdicionBitacora = apm_bitacora_fecha_minima_edicion($now, $diasGuardiaCfg);
        $fechaMinimaEdicionBitacoraCompacta = $fechaMinimaEdicionBitacora !== null
            ? str_replace('-', '', $fechaMinimaEdicionBitacora)
            : null;

        // --- GET ---
        if ($method === 'GET') {
            $action = trim((string) ($_GET['action'] ?? 'context'));

            if ($action === 'context') {
                $dM = PortRondaModel::horariosDefaultPorTurno('Mañana');
                $dT = PortRondaModel::horariosDefaultPorTurno('Tarde');
                $dN = PortRondaModel::horariosDefaultPorTurno('Noche');
                $this->json([
                    'ok' => true,
                    'usuario' => [
                        'nombres' => Auth::userName(),
                        'cedula' => (string) ($_SESSION['apm_auth']['cedula'] ?? ''),
                        'id_usuario' => $idUsuario,
                        'id_departamento' => Auth::departamentoId(),
                    ],
                    'es_admin' => $esAdmin,
                    'puede_configurar_dias_edicion' => Auth::canConfigurarDiasBitacora(),
                    'dias_edicion_guardia' => $diasGuardiaCfg,
                    'dias_edicion_actual_usuario' => $diasEdicionBitacora,
                    'fecha_servidor' => $now->format('Y-m-d'),
                    'hora_servidor' => $now->format('H:i:s'),
                    'fecha_operativa' => $ctx['fecha'],
                    'turno' => $ctx['turno'],
                    'turno_etiqueta' => PortRondaModel::turnoEtiqueta($ctx['turno']),
                    'turno_horas_default' => [
                        'Mañana' => ['hora_inicio' => PortRondaModel::horaParaInput($dM['inicio']), 'hora_fin' => PortRondaModel::horaParaInput($dM['fin'])],
                        'Tarde' => ['hora_inicio' => PortRondaModel::horaParaInput($dT['inicio']), 'hora_fin' => PortRondaModel::horaParaInput($dT['fin'])],
                        'Noche' => ['hora_inicio' => PortRondaModel::horaParaInput($dN['inicio']), 'hora_fin' => PortRondaModel::horaParaInput($dN['fin'])],
                    ],
                    'niveles_alerta' => $model->nivelesAlertaActivos(),
                ]);
            }

            if ($action === 'set_dias_edicion_guardia') {
                if (!Auth::canConfigurarDiasBitacora()) {
                    $this->json(['ok' => false, 'message' => 'No tiene permisos para cambiar esta configuración.'], 403);
                }
                $diasReq = (int) ($_GET['dias'] ?? 0);
                $dias = apm_bitacora_guardia_dias_permitidos($diasReq);
                if (!apm_config_set($conn, APM_CONFIG_DIAS_EDICION_BITACORA, (string) $dias)) {
                    $this->json(['ok' => false, 'message' => 'No se pudo guardar la configuración.'], 500);
                }
                $this->json(['ok' => true, 'message' => 'Configuración actualizada.', 'dias_edicion_guardia' => $dias]);
            }

            if ($action === 'list_turno') {
                $fechaConsulta = $ctx['fecha'];
                $fechaGet = trim((string) ($_GET['fecha'] ?? ''));
                if ($fechaGet !== '') {
                    $isoFecha = apm_post_fecha_a_iso($fechaGet);
                    if ($isoFecha !== null) {
                        $fechaConsulta = $isoFecha;
                    }
                }
                $turnoConsulta = $ctx['turno'];
                $turnoGet = trim((string) ($_GET['turno'] ?? ''));
                if (in_array($turnoGet, ['Mañana', 'Tarde', 'Noche'], true)) {
                    $turnoConsulta = $turnoGet;
                }

                $res = $model->listarTurno($idUsuario, $esAdmin, $fechaConsulta, $turnoConsulta);
                if (!$res['ok']) {
                    $this->json($res);
                }

                $this->json([
                    'ok' => true,
                    'filas' => $res['filas'],
                    'es_admin' => $esAdmin,
                    'id_ronda' => $res['id_ronda'],
                    'fecha_operativa' => $fechaConsulta,
                    'turno' => $turnoConsulta,
                    'turno_etiqueta' => PortRondaModel::turnoEtiqueta($turnoConsulta),
                    'id_usuario' => $idUsuario,
                    'mi_cabecera_horas' => $model->miCabeceraHoras($idUsuario, $fechaConsulta, $turnoConsulta),
                ]);
            }

            if ($action === 'sugerencias_actividad') {
                $this->json(['ok' => true, 'sugerencias' => $model->sugerenciasActividad($idUsuario, 10)]);
            }

            if ($action === 'buscar') {
                $fd = trim((string) ($_GET['fecha_desde'] ?? ''));
                $fh = trim((string) ($_GET['fecha_hasta'] ?? ''));
                $q = trim((string) ($_GET['q'] ?? ''));
                if ($fd === '' || $fh === '') {
                    $this->json(['ok' => false, 'message' => 'Indique fecha_desde y fecha_hasta (YYYY-MM-DD).']);
                }
                $fdIso = apm_post_fecha_a_iso($fd);
                $fhIso = apm_post_fecha_a_iso($fh);
                if ($fdIso === null || $fhIso === null) {
                    $this->json(['ok' => false, 'message' => 'Fechas de búsqueda no válidas. Use YYYY-MM-DD o DD/MM/YYYY.']);
                }
                $this->json($model->buscar($fdIso, $fhIso, $q));
            }

            $this->json(['ok' => false, 'message' => 'Acción GET no válida.']);
        }

        // --- POST grabar ---
        if ($method === 'POST') {
            $actividad = trim((string) ($_POST['actividad'] ?? ''));
            $idAlerta = (int) ($_POST['id_alerta'] ?? 0);
            $idDetalleEdit = (int) ($_POST['id_detalle'] ?? 0);
            $horaRegistroRaw = trim((string) ($_POST['hora_registro'] ?? ''));
            $fechaOperativaPost = trim((string) ($_POST['fecha_operativa'] ?? ''));
            $turnoPost = trim((string) ($_POST['turno'] ?? ''));
            $horaIniPost = trim((string) ($_POST['hora_inicio'] ?? ''));
            $horaFinPost = trim((string) ($_POST['hora_fin'] ?? ''));
            $fechaRegistroPost = trim((string) ($_POST['fecha_registro'] ?? ''));

            if ($actividad === '') {
                $this->json(['ok' => false, 'message' => 'Describa la actividad.']);
            }
            if ($idAlerta < 1 || $idAlerta > 3) {
                $this->json(['ok' => false, 'message' => 'Nivel de alerta no válido.']);
            }
            if (!$model->nivelAlertaExiste($idAlerta)) {
                $this->json(['ok' => false, 'message' => 'Nivel de alerta inexistente.']);
            }

            $fechaOp = $ctx['fecha'];
            if ($fechaOperativaPost !== '') {
                $isoOp = apm_post_fecha_a_iso($fechaOperativaPost);
                if ($isoOp !== null) {
                    $fechaOp = $isoOp;
                }
            }

            $turnosValidos = ['Mañana', 'Tarde', 'Noche'];
            $turno = in_array($turnoPost, $turnosValidos, true) ? $turnoPost : $ctx['turno'];

            $defTurnoH = PortRondaModel::horariosDefaultPorTurno($turno);
            $hiParsed = PortRondaModel::parseHoraPost($horaIniPost) ?? $defTurnoH['inicio'];
            $hfParsed = PortRondaModel::parseHoraPost($horaFinPost) ?? $defTurnoH['fin'];
            if ($hiParsed === $hfParsed) {
                $this->json(['ok' => false, 'message' => 'La hora de inicio y fin del turno no pueden ser iguales.']);
            }

            $horaRegistroSql = null;
            $fechaVentanaValidacion = $fechaOp;
            if ($horaRegistroRaw !== '' && preg_match('/^(\d{1,2}):(\d{2})(?::(\d{2}))?$/', $horaRegistroRaw, $hm)) {
                $h = (int) $hm[1];
                $mi = (int) $hm[2];
                $se = isset($hm[3]) ? (int) $hm[3] : 0;
                if ($h >= 0 && $h <= 23 && $mi >= 0 && $mi <= 59 && $se >= 0 && $se <= 59) {
                    $fechaCalRegistroCompacta = null;

                    if ($idDetalleEdit > 0) {
                        $fechaFrCompacta = PortRondaModel::fechaCompactaYmd($fechaRegistroPost);
                        if ($fechaFrCompacta !== null) {
                            $hoyServidorCompacto = $now->format('Ymd');
                            if ($fechaFrCompacta > $hoyServidorCompacto) {
                                $this->json(['ok' => false, 'message' => 'La fecha del registro no puede ser posterior a la fecha actual del servidor.']);
                            }
                            $fechaCalRegistroCompacta = $fechaFrCompacta;
                            $fechaVentanaValidacion = substr($fechaFrCompacta, 0, 4) . '-' . substr($fechaFrCompacta, 4, 2) . '-' . substr($fechaFrCompacta, 6, 2);
                        }
                    }

                    if ($fechaCalRegistroCompacta === null) {
                        $fechaCalYmd = PortRondaModel::fechaCalendarioParaHoraTurno($fechaOp, $hiParsed, $hfParsed, $h, $mi, $se);
                        $fechaCalRegistroCompacta = PortRondaModel::fechaCompactaYmd($fechaCalYmd);
                    }

                    $horaRegistroSqlSafe = '';
                    if ($fechaCalRegistroCompacta !== null) {
                        $horaRegistroSqlSafe = PortRondaModel::datetimeSqlSafe($fechaCalRegistroCompacta, $h, $mi, $se);
                    }
                    $dtH = ($horaRegistroSqlSafe !== '') ? PortRondaModel::parseDatetimeSqlSafe($horaRegistroSqlSafe) : null;
                    if ($dtH) {
                        if (
                            $idDetalleEdit > 0 &&
                            $diasEdicionBitacora !== null &&
                            $fechaMinimaEdicionBitacoraCompacta !== null &&
                            $dtH->format('Ymd') < $fechaMinimaEdicionBitacoraCompacta
                        ) {
                            $this->json([
                                'ok' => false,
                                'message' => 'No tiene permisos para editar registros de más de ' . $diasEdicionBitacora . ' días de antigüedad',
                            ], 403);
                        }
                        $horaRegistroSql = $horaRegistroSqlSafe;
                    }
                }
            }

            if ($horaRegistroSql !== null) {
                $dtVal = PortRondaModel::parseDatetimeSqlSafe($horaRegistroSql);
                if (!$dtVal) {
                    $this->json(['ok' => false, 'message' => 'Fecha u hora del registro no válida.']);
                }
                if ($dtVal->format('Ymd') > $now->format('Ymd')) {
                    $this->json(['ok' => false, 'message' => 'La fecha del registro no puede ser posterior a la fecha actual del servidor.']);
                }
                if (!PortRondaModel::instanteEnVentana($dtVal, $fechaVentanaValidacion, $hiParsed, $hfParsed)) {
                    $this->json(['ok' => false, 'message' => 'La hora del registro debe estar dentro del rango de turno indicado (fecha operativa y franja inicio/fin).']);
                }
            }

            $cab = $model->upsertCabecera($idUsuario, $fechaOp, $turno, $hiParsed, $hfParsed);
            if (!$cab['ok']) {
                $this->json($cab);
            }
            $idRonda = $cab['id_ronda'];

            if ($idDetalleEdit > 0) {
                $res = $model->actualizarDetalle($idDetalleEdit, $idUsuario, $actividad, $idAlerta, $horaRegistroSql);
                if (!$res['ok']) {
                    $this->json($res);
                }
                $idDetalleRef = $idDetalleEdit;
            } else {
                $res = $model->insertarDetalle($idRonda, $actividad, $idAlerta, $horaRegistroSql);
                if (!$res['ok']) {
                    $this->json($res);
                }
                $idDetalleRef = $res['id_detalle'];
            }

            $nomU = $model->nombreUsuario($idUsuario);
            $labels = [1 => 'Normal', 2 => 'Medio', 3 => 'Crítico'];
            $lbl = $labels[$idAlerta] ?? '—';
            $desc = $idDetalleEdit > 0
                ? sprintf('Bitácora rondas: %s actualizó actividad (%s)', $nomU, $lbl)
                : sprintf('Bitácora rondas: %s registró actividad (%s)', $nomU, $lbl);
            if ($idDetalleRef > 0) {
                $suffix = sprintf(' [REF:D%d]', $idDetalleRef);
                if (mb_strlen($desc . $suffix, 'UTF-8') > 500) {
                    $maxBase = max(0, 500 - mb_strlen($suffix, 'UTF-8'));
                    $desc = mb_substr($desc, 0, $maxBase, 'UTF-8') . $suffix;
                } else {
                    $desc .= $suffix;
                }
            }
            if (mb_strlen($desc, 'UTF-8') > 500) {
                $desc = mb_substr($desc, 0, 497, 'UTF-8') . '...';
            }

            $model->registrarMovimiento($idUsuario, $desc, $turno);

            $this->json([
                'ok' => true,
                'message' => $idDetalleEdit > 0 ? 'Registro actualizado.' : 'Registro guardado.',
                'fila' => $model->obtenerUltimaFila($idRonda, $idDetalleEdit, $fechaOp),
                'id_ronda' => $idRonda,
                'fecha_operativa' => $fechaOp,
                'turno' => $turno,
                'turno_etiqueta' => PortRondaModel::turnoEtiqueta($turno),
            ]);
        }

        $this->json(['ok' => false, 'message' => 'Método no permitido.']);
    }

    /**
     * GET /bitacoras/ronda/detalle — detalle de un registro de bitácora de
     * rondas (modal o página completa), enlazado desde el panel jefe y desde
     * el feed de actividad reciente. Cierra un hueco real: dashboard_jefe.js
     * linkeaba a bit_consulta.php, que nunca existió en este puerto (Patrón B).
     */
    public function detalle(): void
    {
        Auth::guard();
        if (!Auth::canAccederDashboardJefe() && !Auth::canAccederBitacoraRondas()) {
            $this->redirect('dashboard?msg=acceso_denegado');
            return;
        }

        /** @var PortRondaModel $model */
        $model = $this->model('PortRondaModel');

        $idDetalle = isset($_GET['id_detalle']) ? (int) $_GET['id_detalle'] : 0;
        $action = isset($_GET['action']) ? trim((string) $_GET['action']) : '';
        $desdeDashboard = isset($_GET['from']) && $_GET['from'] === 'dashboard';
        $modalOnly = isset($_GET['modal_only']) && $_GET['modal_only'] === '1';

        $row = $model->obtenerDetalleParaConsulta($idDetalle);
        $abrirModal = ($action === 'view' && $row !== null);

        $data = [
            'pageTitle' => 'Consulta bitácora | Autoridad Portuaria de Manta',
            'row' => $row,
            'desdeDashboard' => $desdeDashboard,
            'abrirModal' => $abrirModal,
        ];

        if ($modalOnly) {
            $this->view('rondas/detalleModal', $data, false);
            return;
        }

        $this->view('rondas/detalle', $data);
    }
}
