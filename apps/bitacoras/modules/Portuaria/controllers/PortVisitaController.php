<?php
/**
 * Controlador de Visitas.
 * Reemplaza: bit_registrar_visita.php, bit_guardar_visita.php, bit_listado_visitas.php,
 * bit_actualizar_visita.php, bit_actualizar_horas.php, bit_registrar_salida.php,
 * bit_consulta_visitas.php, apis/visitas_api.php
 */
class PortVisitaController extends PortController
{
    /**
     * GET /bitacoras/visita/registrar
     */
    public function registrar()
    {
        Auth::guard();

        if (!Auth::canRegistrarIngreso()) {
            $this->redirect('visitas?msg=permiso_denegado');
            return;
        }

        /** @var VisitaModel $visitas */
        $visitas = $this->model('PortVisitaModel');

        $this->view('visitas/registrar', [
            'pageTitle'      => 'Registrar ingreso de visita | Autoridad Portuaria de Manta',
            'funcionarios'   => $visitas->funcionariosActivos(),
            'destinos'       => $visitas->destinosActivos(),
            'motivos'        => $visitas->motivosActivos(),
            'niveles'        => $visitas->nivelesIncidenteActivos(),
            'dbaseDisponible' => extension_loaded('dbase'),
            'errorCode'      => $_GET['error'] ?? '',
            'extraCss'       => [
                $this->rutas['url_select2_css'] ?? '',
                $this->rutas['url_select2_bootstrap_theme_css'] ?? '',
                $this->rutas['url_toast_css'] ?? '',
            ],
        ]);
    }

    /**
     * POST /bitacoras/visita/guardar
     */
    public function guardar()
    {
        Auth::guard();

        $esAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

        if (!Auth::canRegistrarIngreso()) {
            if ($esAjax) {
                Auth::denyJson('Permiso denegado: su departamento no puede registrar ingresos.', 403);
            }
            $this->redirect('visitas?msg=permiso_denegado');
            return;
        }

        /** @var VisitaModel $visitas */
        $visitas = $this->model('PortVisitaModel');
        $visitas->guardarIngresoDesdePost();
    }

    /**
     * GET /bitacoras/visita/listado
     */
    public function listado()
    {
        Auth::guard();

        if (!Auth::canVerListadoAdmin()) {
            $this->redirect('dashboard?msg=permiso_denegado');
            return;
        }

        /** @var VisitaModel $visitas */
        $visitas = $this->model('PortVisitaModel');

        $mensajes = [
            'ingreso_ok'       => 'La visita se registró correctamente.',
            'salida_ok'        => 'La salida se registró correctamente.',
            'horas_ok'         => 'Las horas se actualizaron correctamente.',
            'horas_error'      => 'Ocurrió un error al actualizar las horas.',
            'permiso_denegado' => 'No tiene permisos para ejecutar esta acción.',
        ];
        $msgKey = $_GET['msg'] ?? '';

        $this->view('visitas/listado', [
            'pageTitle'             => 'Listado de visitas | Autoridad Portuaria de Manta',
            'visitas'               => $visitas->listado(),
            'empresas'              => $visitas->empresasActivas(),
            'funcionarios'          => $visitas->funcionariosActivos(),
            'destinos'              => $visitas->destinosActivos(),
            'motivos'               => $visitas->motivosActivos(),
            'puedeRegistrarIngreso' => Auth::canRegistrarIngreso(),
            'puedeRegistrarSalida'  => Auth::canRegistrarSalida(),
            'puedeEditarVisita'     => Auth::canEditarVisitaDesdeListado(),
            'mensaje'               => $mensajes[$msgKey] ?? '',
            'extraCss'              => [
                $this->rutas['url_datatables_css'] ?? '',
                $this->rutas['url_toast_css'] ?? '',
                $this->rutas['url_sweetalert2_css'] ?? '',
            ],
        ]);
    }

    /**
     * GET /bitacoras/visita/registrarSalida
     */
    public function registrarSalida()
    {
        Auth::guard();

        $idVisita = (int)($_GET['id'] ?? 0);
        $esAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

        if ($idVisita <= 0) {
            if ($esAjax) {
                $this->json(['ok' => false, 'message' => 'ID de visita inválido']);
            }
            $this->redirect('visitas');
            return;
        }

        if (!Auth::canRegistrarSalida()) {
            if ($esAjax) {
                $this->json(['ok' => false, 'message' => 'Permiso denegado: su departamento no puede registrar salidas.']);
            }
            $this->redirect('visitas?msg=permiso_denegado');
            return;
        }

        /** @var VisitaModel $visitas */
        $visitas = $this->model('PortVisitaModel');
        $ok = $visitas->registrarSalida($idVisita);

        if (!$ok) {
            if ($esAjax) {
                $this->json(['ok' => false, 'message' => 'Error al registrar la salida']);
            }
            echo '<h3>Error al registrar la salida</h3>';
            echo '<a href="visitas">Volver al listado</a>';
            exit;
        }

        if ($esAjax) {
            $this->json(['ok' => true, 'message' => 'La hora de salida se registró correctamente.', 'hora_salida' => date('H:i')]);
        }

        $this->redirect('visitas?msg=salida_ok');
    }

    /**
     * POST /bitacoras/visita/actualizarHoras
     */
    public function actualizarHoras()
    {
        Auth::guard();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('visitas');
            return;
        }

        if (!Auth::canEditarVisita()) {
            $this->redirect('visitas?msg=permiso_denegado');
            return;
        }

        $idVisita = (int)($_POST['id_visita'] ?? 0);
        $fechaRaw = trim((string)($_POST['fecha_visita'] ?? ''));
        $isoFv = apm_post_fecha_a_iso($fechaRaw);

        if ($fechaRaw !== '' && $isoFv === null) {
            $this->redirect('visitas?msg=horas_error');
            return;
        }

        $fechaVisita = $isoFv ?? $fechaRaw;
        $horaEntrada = trim((string)($_POST['hora_entrada'] ?? ''));
        $horaSalida = trim((string)($_POST['hora_salida'] ?? ''));

        if ($idVisita <= 0 || $fechaVisita === '' || $horaEntrada === '') {
            $this->redirect('visitas?msg=horas_error');
            return;
        }

        /** @var VisitaModel $visitas */
        $visitas = $this->model('PortVisitaModel');
        $ok = $visitas->actualizarHoras($idVisita, $fechaVisita, $horaEntrada, $horaSalida === '' ? null : $horaSalida);

        $this->redirect($ok ? 'visitas?msg=horas_ok' : 'visitas?msg=horas_error');
    }

    /**
     * POST /bitacoras/visita/actualizar
     */
    public function actualizar()
    {
        Auth::guard();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['ok' => false, 'message' => 'Método no permitido'], 405);
        }

        if (!Auth::canEditarVisitaDesdeListado()) {
            Auth::denyJson('Permiso denegado: no puede editar visitas desde el listado.', 403);
        }

        $idVisita = (int)($_POST['id_visita'] ?? 0);
        $idEmpresa = trim((string)($_POST['id_empresa'] ?? ''));
        $idFuncionario = (isset($_POST['id_funcionario']) && trim((string)$_POST['id_funcionario']) !== '')
            ? (int)$_POST['id_funcionario'] : null;
        $idDestino = (int)($_POST['id_destino'] ?? 0);
        $idMotivo = (int)($_POST['id_motivo'] ?? 0);
        $detalleMotivo = trim((string)($_POST['detalle_motivo'] ?? ''));
        $detalleMotivo = function_exists('mb_substr') ? mb_substr($detalleMotivo, 0, 500, 'UTF-8') : substr($detalleMotivo, 0, 500);
        $horaSalida = trim((string)($_POST['hora_salida'] ?? ''));

        if ($idVisita <= 0 || $idDestino <= 0 || $idMotivo <= 0) {
            $this->json(['ok' => false, 'message' => 'Datos incompletos o id_visita inválido.']);
        }

        /** @var VisitaModel $visitas */
        $visitas = $this->model('PortVisitaModel');

        $actual = $visitas->obtenerFechaHoraActual($idVisita);
        if (!$actual) {
            $this->json(['ok' => false, 'message' => 'No se encontró la visita que intenta actualizar.']);
        }

        $fechaVisita = $actual['fecha_visita'] instanceof DateTime ? $actual['fecha_visita']->format('Y-m-d') : (string)$actual['fecha_visita'];
        $horaEntrada = $actual['hora_entrada'] instanceof DateTime ? $actual['hora_entrada']->format('H:i:s') : (string)$actual['hora_entrada'];

        if ($fechaVisita === '' || $horaEntrada === '') {
            $this->json(['ok' => false, 'message' => 'La visita no tiene fecha u hora de entrada registrada correctamente.']);
        }

        $ok = $visitas->actualizarCompleta([
            'id_visita'      => $idVisita,
            'id_empresa'     => $idEmpresa === '' ? null : (int)$idEmpresa,
            'tipo_visitante' => $idEmpresa === '' ? 'Personal' : 'Empresa',
            'id_funcionario' => $idFuncionario,
            'id_destino'     => $idDestino,
            'id_motivo'      => $idMotivo,
            'detalle_motivo' => $detalleMotivo === '' ? null : $detalleMotivo,
            'fecha_visita'   => $fechaVisita,
            'hora_entrada'   => $horaEntrada,
            'hora_salida'    => $horaSalida === '' ? null : $horaSalida,
        ]);

        if (!$ok) {
            $this->json(['ok' => false, 'message' => 'Error al actualizar la visita'], 500);
        }

        $fila = $visitas->detalle($idVisita);
        $data = $this->formatearFilaActualizada($fila);

        $this->json(['ok' => true, 'message' => 'Los cambios se han actualizado correctamente.', 'data' => $data]);
    }

    /**
     * GET /bitacoras/visita/detalle
     */
    public function detalle()
    {
        Auth::guard();

        if (!Auth::canAccederDashboardJefe() && !Auth::canVerListadoAdmin()) {
            $this->redirect('dashboard?msg=acceso_denegado');
            return;
        }

        $idVisita = (int)($_GET['id'] ?? 0);
        $modalOnly = ($_GET['modal_only'] ?? '') === '1';

        /** @var VisitaModel $visitas */
        $visitas = $this->model('PortVisitaModel');
        $visita = $idVisita > 0 ? $visitas->detalle($idVisita) : false;

        if ($modalOnly) {
            if (!$visita) {
                echo '<div class="alert alert-warning m-3">No se encontró la visita solicitada.</div>';
                exit;
            }
            $this->view('visitas/detalle_modal', ['visita' => $visita], false);
            return;
        }

        $this->view('visitas/detalle_pagina', [
            'pageTitle' => 'Detalle de visita | Autoridad Portuaria de Manta',
            'visita'    => $visita,
        ]);
    }

    private function formatearFilaActualizada($row): array
    {
        $data = [
            'empresa_label' => 'Personal', 'funcionario' => '', 'destino' => '', 'motivo' => '',
            'detalle_motivo' => '', 'fecha_dmy' => '', 'fecha_ymd' => '', 'hora_entrada' => '',
            'hora_salida' => '', 'estado_label' => 'Finalizada', 'hora_salida_null' => true,
            'id_persona' => 0, 'nidentificacion' => '', 'nombre_visitante' => '',
            'mostrar_btn_registrar_salida' => false, 'mostrar_btn_asignar_cedula_guest' => false,
        ];

        if (!$row) {
            return $data;
        }

        $data['funcionario'] = $row['funcionario'] ?? '';
        $data['destino'] = $row['destino'] ?? '';
        $data['motivo'] = $row['motivo'] ?? '';
        $data['detalle_motivo'] = (string)($row['detalle_motivo'] ?? '');

        if (($row['fecha_visita'] ?? null) instanceof DateTime) {
            $data['fecha_dmy'] = $row['fecha_visita']->format('d/m/Y');
            $data['fecha_ymd'] = $row['fecha_visita']->format('Y-m-d');
        }
        if (($row['hora_entrada'] ?? null) instanceof DateTime) {
            $data['hora_entrada'] = $row['hora_entrada']->format('H:i');
        }
        if (($row['hora_salida'] ?? null) instanceof DateTime) {
            $data['hora_salida'] = $row['hora_salida']->format('H:i');
            $data['hora_salida_null'] = false;
        } else {
            $data['estado_label'] = 'Dentro';
        }
        if (!empty($row['empresa'])) {
            $data['empresa_label'] = $row['empresa'] . (!empty($row['empresa_ruc']) ? ' (' . $row['empresa_ruc'] . ')' : '');
        }

        $nid = (string)($row['nidentificacion'] ?? '');
        $data['id_persona'] = (int)($row['id_persona'] ?? 0);
        $data['nidentificacion'] = $nid;
        $data['nombre_visitante'] = (string)($row['nombre_visitante'] ?? '');
        $data['mostrar_btn_registrar_salida'] = Auth::canRegistrarSalida() && $data['hora_salida_null'];
        $data['mostrar_btn_asignar_cedula_guest'] = Auth::canAsignarCedulaGuest() && $nid === '9999999999';

        return $data;
    }
}