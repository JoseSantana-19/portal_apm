<?php
/**
 * Controlador de Cámaras CCTV.
 */
class PortCamaraController extends PortController
{
    private function unificarFuncionesFormato()
    {
        if (!function_exists('bc_normalizar_fila')) {
            function bc_fecha_out($v) { return $v instanceof DateTimeInterface ? $v->format('Y-m-d') : (trim((string)$v) !== '' ? date('Y-m-d', strtotime($v)) : ''); }
            function bc_time_input($v) { return $v instanceof DateTimeInterface ? $v->format('H:i') : (preg_match('/(\d{1,2}):(\d{2})/', (string)$v, $m) ? sprintf('%02d:%02d', (int)$m[1], (int)$m[2]) : ''); }
            function bc_normalizar_fila($row) {
                return array(
                    'id_bitacora_camara' => (int)($row['id_bitacora_camara'] ?? 0),
                    'sec_bitacora' => (int)($row['sec_bitacora'] ?? 0),
                    'codigo_bitacora' => (string)($row['codigo_bitacora'] ?? ''),
                    'tipo_registro' => (int)($row['tipo_registro'] ?? 102),
                    'tipo_registro_texto' => (int)($row['tipo_registro'] ?? 102) === 103 ? 'Novedad de cámara' : 'Actividad diaria',
                    'rol_responsable' => (string)($row['rol_responsable'] ?? ''),
                    'id_camara' => (int)($row['id_camara'] ?? 0),
                    'id_motivo_camara' => (int)($row['id_motivo_camara'] ?? 0),
                    'motivo_codigo' => (string)($row['motivo_codigo'] ?? ''),
                    'motivo_descripcion' => (string)($row['motivo_descripcion'] ?? ''),
                    'fecha' => bc_fecha_out($row['fecha'] ?? ''),
                    'secuencia' => (string)($row['secuencia'] ?? ''),
                    'turno' => (string)($row['turno'] ?? ''),
                    'hora_inicio' => bc_time_input($row['hora_inicio'] ?? ''),
                    'hora_fin' => bc_time_input($row['hora_fin'] ?? ''),
                    'consolista' => (string)($row['consolista'] ?? ''),
                    'hora_registro' => bc_time_input($row['hora_registro'] ?? ''),
                    'novedad' => (string)($row['novedad'] ?? ''),
                    'camara_ip' => (string)($row['camara_ip'] ?? ''),
                    'ubicacion' => (string)($row['ubicacion'] ?? ''),
                    'sitio' => (string)($row['sitio'] ?? ''),
                    'estado_camara' => (int)($row['estado_camara'] ?? 101) === 100 ? 'NO OPERATIVA' : 'OPERATIVA',
                    'nivel_alerta' => (int)($row['nivel_alerta'] ?? 104) === 106 ? 'Crítico' : ((int)$row['nivel_alerta'] === 105 ? 'Medio' : 'Normal'),
                    'observaciones' => (string)($row['observaciones'] ?? ''),
                    'inv_cod_old' => (string)($row['inv_cod_old'] ?? ''),
                    'inv_codigo' => (string)($row['inv_codigo'] ?? ''),
                    'inv_tipo' => (string)($row['inv_tipo'] ?? ''),
                    'inv_marca' => (string)($row['inv_marca'] ?? ''),
                    'inv_modelo' => (string)($row['inv_modelo'] ?? ''),
                    'inv_detalle' => (string)($row['inv_detalle'] ?? ''),
                    'inv_grabador' => (string)($row['inv_grabador'] ?? '')
                );
            }
        }
    }

    public function index()
    {
        Auth::guard();
        if (!Auth::canVerCctvBitacora()) { $this->redirect('dashboard?msg=permiso_denegado'); return; }
        $this->view('camaras/index', array('pageTitle' => 'Bitácora de Cámaras CCTV | Autoridad Portuaria de Manta'));
    }

    public function motivos()
    {
        Auth::guard();
        if (!Auth::canVerCctvMotivos()) { $this->redirect('dashboard?msg=permiso_denegado'); return; }
        $this->view('camaras/motivos', array('pageTitle' => 'Motivos CCTV | Autoridad Portuaria de Manta'));
    }

    public function inventario()
    {
        Auth::guard();
        if (!Auth::canVerCctvInventario()) { $this->redirect('dashboard?msg=permiso_denegado'); return; }
        $this->view('camaras/inventario', array('pageTitle' => 'Maestro de Cámaras CCTV | Autoridad Portuaria de Manta'));
    }

    public function api()
    {
        Auth::guard();
        if (!Auth::canVerCctvBitacora()) { $this->json(array('ok' => false, 'message' => 'Permiso denegado.'), 403); }
        $this->unificarFuncionesFormato();
        
        $model = $this->model('PortCamaraModel');
        $action = $_SERVER['REQUEST_METHOD'] === 'GET' ? ($_GET['action'] ?? 'context') : ($_POST['action'] ?? 'guardar');

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            if ($action === 'context') {
                $now = new DateTime('now');
                $min = ((int)$now->format('H')) * 60 + (int)$now->format('i');
                $ctx = array('turno' => 'Mañana', 'fecha' => $now->format('Y-m-d'), 'hora_inicio' => '07:00', 'hora_fin' => '15:00');
                if ($min >= 900 && $min < 1380) { $ctx = array('turno' => 'Tarde', 'fecha' => $now->format('Y-m-d'), 'hora_inicio' => '15:00', 'hora_fin' => '23:00'); }
                elseif ($min >= 1380 || $min < 420) {
                    $op = clone $now; if ($min < 420) $op->modify('-1 day');
                    $ctx = array('turno' => 'Noche', 'fecha' => $op->format('Y-m-d'), 'hora_inicio' => '23:00', 'hora_fin' => '07:00');
                }
                
                $meses = array('01'=>'ENERO','02'=>'FEBRERO','03'=>'MARZO','04'=>'ABRIL','05'=>'MAYO','06'=>'JUNIO','07'=>'JULIO','08'=>'AGOSTO','09'=>'SEPTIEMBRE','10'=>'OCTUBRE','11'=>'NOVIEMBRE','12'=>'DICIEMBRE');
                
                $catalogosRaw = $model->obtenerEstadosCctv();
                $catalogos = array('tipo_registro' => array(), 'estado_camara' => array(), 'nivel_alerta' => array());
                foreach ($catalogosRaw as $row) {
                    $id = (int)$row['idestado'];
                    $lbl = $id===100?'NO OPERATIVA':($id===101?'OPERATIVA':($id===102?'Actividad diaria':($id===103?'Novedad de cámara':($id===104?'Normal':($id===105?'Medio':'Crítico')))));
                    $item = array('idestado' => $id, 'descripcion' => $row['descripcion'], 'detalle' => $row['detalle'], 'texto' => $lbl);
                    if (in_array($id, array(102, 103))) $catalogos['tipo_registro'][] = $item;
                    elseif (in_array($id, array(100, 101))) $catalogos['estado_camara'][] = $item;
                    elseif (in_array($id, array(104, 105, 106))) $catalogos['nivel_alerta'][] = $item;
                }

                $this->json(array(
                    'ok' => true,
                    'usuario' => array('id_usuario' => $_SESSION['apm_auth']['id_usuario'], 'nombres' => $_SESSION['apm_auth']['nombres'], 'cedula' => $_SESSION['apm_auth']['cedula']),
                    'fecha_servidor' => $now->format('Y-m-d'), 'hora_servidor' => $now->format('H:i:s'),
                    'fecha_operativa' => $ctx['fecha'], 'turno' => $ctx['turno'], 'hora_inicio' => $ctx['hora_inicio'], 'hora_fin' => $ctx['hora_fin'],
                    'secuencia' => $meses[$now->format('m')] ?? '', 'catalogos_estados' => $catalogos
                ));
            }

            if ($action === 'motivos') {
                $rows = $model->obtenerMotivosActivos();
                $items = array();
                foreach ($rows as $r) {
                    $items[] = array('id_motivo_camara' => (int)$r['id_motivo_camara'], 'codigo_motivo' => $r['codigo_motivo'], 'descripcion' => $r['descripcion'], 'nivel_sugerido' => $r['nivel_sugerido'], 'requiere_observacion' => (int)$r['requiere_observacion']);
                }
                $this->json(array('ok' => true, 'data' => $items));
            }

            if ($action === 'buscar_camaras') {
                $q = trim((string)($_GET['q'] ?? ''));
                $rows = $model->buscarCamarasInventario($q);
                $items = array();
                foreach ($rows as $r) {
                    $partes = array_filter(array($r['ip'], $r['detalle'], $r['marca'], $r['modelo'], $r['cod_old'] ? 'COD-OLD: '.$r['cod_old'] : '', $r['codigo'] ? 'COD: '.$r['codigo'] : ''));
                    $items[] = array('id_camara' => (int)$r['id_camara'], 'cod_old' => $r['cod_old'], 'codigo' => $r['codigo'], 'tipo' => $r['tipo'], 'marca' => $r['marca'], 'modelo' => $r['modelo'], 'tecnologia' => $r['tecnologia'], 'caracteristica' => $r['caracteristica'], 'ip' => $r['ip'], 'mac' => $r['mac'], 'serie' => $r['serie'], 'ubicacion' => $r['ubicacion'], 'detalle' => $r['detalle'], 'grabador' => $r['grabador'], 'texto' => implode(' - ', $partes));
                }
                $this->json(array('ok' => true, 'data' => $items));
            }

            if ($action === 'listar') {
                $fechaRaw = $_GET['fecha'] ?? date('Y-m-d');
                $fechaSql = str_replace('-', '', $fechaRaw);
                $turno = trim((string)($_GET['turno'] ?? ''));
                $q = trim((string)($_GET['q'] ?? ''));
                $orden = strtoupper(trim((string)($_GET['orden'] ?? 'ASC'))) === 'DESC' ? 'DESC' : 'ASC';
                
                $rows = $model->listarBitacora($fechaSql, $turno, $q, $orden);
                $items = array();
                foreach ($rows as $row) { $items[] = bc_normalizar_fila($row); }
                $this->json(array('ok' => true, 'data' => $items));
            }

            if ($action === 'obtener') {
                $id = (int)($_GET['id'] ?? 0);
                $row = $model->obtenerFilaBitacora($id);
                if (!$row) { $this->json(array('ok' => false, 'message' => 'Registro no encontrado.'), 404); }
                $this->json(array('ok' => true, 'data' => bc_normalizar_fila($row)));
            }
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if ($action === 'eliminar') {
                $id = (int)($_POST['id_bitacora_camara'] ?? 0);
                $ok = $model->eliminarFilaBitacora($id);
                $this->json(array('ok' => $ok, 'message' => $ok ? 'Registro eliminado.' : 'No se pudo eliminar el registro.'));
            }

            if ($action === 'guardar') {
                $id = (int)($_POST['id_bitacora_camara'] ?? 0);
                $tipoRegistro = (int)($_POST['tipo_registro'] ?? 102);
                $fechaSql = str_replace('-', '', $_POST['fecha'] ?? '');
                
                $idCamara = $tipoRegistro === 102 ? null : ((int)$_POST['id_camara'] > 0 ? (int)$_POST['id_camara'] : null);
                $idMotivo = $tipoRegistro === 102 ? null : ((int)$_POST['id_motivo_camara'] > 0 ? (int)$_POST['id_motivo_camara'] : null);
                
                $novedad = trim((string)($_POST['novedad'] ?? ''));
                if ($novedad === '') { $this->json(array('ok' => false, 'message' => 'Ingrese la actividad o novedad descriptiva.'), 400); }

                if ($id > 0) {
                    $params = array(
                        $tipoRegistro, $_POST['rol_responsable'] ?? 'Consolista', $idCamara, $idMotivo, $fechaSql, $_POST['secuencia'] ?? '', $_POST['turno'] ?? '',
                        $_POST['hora_inicio'] ?? null, $_POST['hora_fin'] ?? null, $_POST['consolista'] ?? '', $_POST['hora_registro'] ?? null, $novedad,
                        $tipoRegistro === 102 ? '' : ($_POST['camara_ip'] ?? ''), $tipoRegistro === 102 ? '' : ($_POST['ubicacion'] ?? ''), $tipoRegistro === 102 ? '' : ($_POST['sitio'] ?? ''),
                        $tipoRegistro === 102 ? 101 : (int)$_POST['estado_camara'], (int)$_POST['nivel_alerta'], $_POST['observaciones'] ?? '', $_SESSION['apm_auth']['nombres'], $id
                    );
                    $ok = $model->actualizarFilaBitacora($params);
                    $this->json(array('ok' => $ok, 'message' => $ok ? 'Registro actualizado.' : 'Error al actualizar el registro.'));
                } else {
                    $secuencial = $model->generarSecuencialCctv('bit_camaras');
                    $params = array(
                        $secuencial['numero'], $secuencial['codigo'], $tipoRegistro, $_POST['rol_responsable'] ?? 'Consolista', $idCamara, $idMotivo, $fechaSql, $_POST['secuencia'] ?? '', $_POST['turno'] ?? '',
                        $_POST['hora_inicio'] ?? null, $_POST['hora_fin'] ?? null, $_POST['consolista'] ?? '', $_POST['hora_registro'] ?? null, $novedad,
                        $tipoRegistro === 102 ? '' : ($_POST['camara_ip'] ?? ''), $tipoRegistro === 102 ? '' : ($_POST['ubicacion'] ?? ''), $tipoRegistro === 102 ? '' : ($_POST['sitio'] ?? ''),
                        $tipoRegistro === 102 ? 101 : (int)$_POST['estado_camara'], (int)$_POST['nivel_alerta'], $_POST['observaciones'] ?? '', $_SESSION['apm_auth']['nombres']
                    );
                    $ok = $model->registrarFilaBitacora($params);
                    $this->json(array('ok' => $ok, 'message' => $ok ? 'Registro guardado con secuencial ' . $secuencial['codigo'] . '.' : 'Error al guardar.', 'codigo_bitacora' => $secuencial['codigo']));
                }
            }
        }
    }

    public function apiMotivos()
    {
        Auth::guard();
        if (!Auth::canVerCctvMotivos()) { $this->json(array('ok' => false, 'message' => 'Permiso denegado.'), 403); }
        
        $model = $this->model('PortCamaraModel');
        $action = $_SERVER['REQUEST_METHOD'] === 'GET' ? ($_GET['action'] ?? 'listar') : ($_POST['action'] ?? 'guardar');

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            if ($action === 'listar') {
                $rows = $model->listarMotivosMaestro($_GET['q'] ?? '', $_GET['estado'] ?? '1', $_GET['nivel'] ?? '');
                $items = array();
                foreach ($rows as $r) {
                    $items[] = array(
                        'id_motivo_camara' => (int)$r['id_motivo_camara'], 'sec_motivo' => (int)$r['sec_motivo'], 'codigo_motivo' => $r['codigo_motivo'],
                        'descripcion' => $r['descripcion'], 'nivel_sugerido' => $r['nivel_sugerido'], 'requiere_observacion' => (int)$r['requiere_observacion'], 'estado' => (int)$r['estado']
                    );
                }
                $this->json(array('ok' => true, 'data' => $items));
            }
            if ($action === 'obtener') {
                $r = $model->obtenerMotivoMaestro((int)($_GET['id'] ?? 0));
                if (!$r) $this->json(array('ok' => false, 'message' => 'No encontrado'), 404);
                $this->json(array('ok' => true, 'data' => array(
                    'id_motivo_camara' => (int)$r['id_motivo_camara'], 'sec_motivo' => (int)$r['sec_motivo'], 'codigo_motivo' => $r['codigo_motivo'],
                    'descripcion' => $r['descripcion'], 'nivel_sugerido' => $r['nivel_sugerido'], 'requiere_observacion' => (int)$r['requiere_observacion'], 'estado' => (int)$r['estado']
                )));
            }
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if ($action === 'eliminar' || $action === 'activar') {
                $id = (int)($_POST['id_motivo_camara'] ?? 0);
                $estado = $action === 'activar' ? 1 : 0;
                $ok = $model->cambiarEstadoMotivo($id, $estado);
                $this->json(array('ok' => $ok, 'message' => 'Estado actualizado con éxito.'));
            }
            if ($action === 'guardar') {
                $id = (int)($_POST['id_motivo_camara'] ?? 0);
                $desc = trim(preg_replace('/\s+/', ' ', $_POST['descripcion'] ?? ''));
                $nivel = $_POST['nivel_sugerido'] ?? 'Medio';
                $reqObs = ($_POST['requiere_observacion'] === '1') ? 1 : 0;

                if ($model->existeMotivoDuplicado($desc, $id)) { $this->json(array('ok' => false, 'message' => 'Ya existe un motivo activo con esa descripción.'), 400); }

                if ($id > 0) {
                    $ok = $model->actualizarMotivoMaestro(array($desc, $nivel, $reqObs, $id));
                    $this->json(array('ok' => $ok, 'message' => 'Motivo CCTV actualizado correctamente.'));
                } else {
                    $sec = $model->generarSecuencialCctv('bit_motivos_camaras');
                    $ok = $model->insertarMotivoMaestro(array($sec['numero'], $sec['codigo'], $desc, $nivel, $reqObs));
                    $this->json(array('ok' => $ok, 'message' => 'Motivo CCTV registrado correctamente.', 'codigo_motivo' => $sec['codigo']));
                }
            }
        }
    }

    public function apiInventario()
    {
        Auth::guard();
        if (!Auth::canVerCctvInventario()) { $this->json(array('ok' => false, 'message' => 'Permiso denegado.'), 403); }
        
        $model = $this->model('PortCamaraModel');
        $action = $_SERVER['REQUEST_METHOD'] === 'GET' ? ($_GET['action'] ?? 'listar') : ($_POST['action'] ?? 'guardar');

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            if ($action === 'listar') {
                $limite = (int)($_GET['limite'] ?? 300);
                $limite = ($limite > 0 && $limite <= 1000) ? $limite : 300;
                $rows = $model->listarInventarioMaestro($_GET['q'] ?? '', $_GET['estado'] ?? '1', $limite);
                
                $items = array();
                foreach ($rows as $r) {
                    $items[] = array(
                        'id_camara' => (int)$r['id_camara'], 'sec_camara' => (int)($r['sec_camara'] ?? 0), 'codigo_secuencial' => $r['codigo_secuencial'] ?? '',
                        'cod_old' => $r['cod_old'], 'codigo' => $r['codigo'], 'tipo' => $r['tipo'], 'marca' => $r['marca'], 'modelo' => $r['modelo'],
                        'tecnologia' => $r['tecnologia'], 'caracteristica' => $r['caracteristica'], 'ip' => $r['ip'], 'mac' => $r['mac'], 'serie' => $r['serie'],
                        'ubicacion' => $r['ubicacion'], 'detalle' => $r['detalle'], 'grabador' => $r['grabador'], 'estado' => (int)$r['estado']
                    );
                }
                $this->json(array('ok' => true, 'data' => $items, 'total' => count($items)));
            }

            if ($action === 'obtener') {
                $r = $model->obtenerCamaraMaestro((int)($_GET['id'] ?? 0));
                if (!$r) $this->json(array('ok' => false, 'message' => 'Cámara no encontrada.'), 404);
                $this->json(array('ok' => true, 'data' => array(
                    'id_camara' => (int)$r['id_camara'], 'sec_camara' => (int)($r['sec_camara'] ?? 0), 'codigo_secuencial' => $r['codigo_secuencial'] ?? '',
                    'cod_old' => $r['cod_old'], 'codigo' => $r['codigo'], 'tipo' => $r['tipo'], 'marca' => $r['marca'], 'modelo' => $r['modelo'],
                    'tecnologia' => $r['tecnologia'], 'caracteristica' => $r['caracteristica'], 'ip' => $r['ip'], 'mac' => $r['mac'], 'serie' => $r['serie'],
                    'ubicacion' => $r['ubicacion'], 'detalle' => $r['detalle'], 'grabador' => $r['grabador'], 'estado' => (int)$r['estado']
                )));
            }
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if ($action === 'eliminar' || $action === 'activar') {
                $id = (int)($_POST['id_camara'] ?? 0);
                $estado = $action === 'activar' ? 1 : 0;
                $ok = $model->cambiarEstadoCamara($id, $estado);
                $this->json(array('ok' => $ok, 'message' => $estado === 1 ? 'Cámara activada correctamente.' : 'Cámara desactivada correctamente.'));
            }

            if ($action === 'guardar') {
                $id = (int)($_POST['id_camara'] ?? 0);
                $clean = array(
                    'cod_old' => trim(preg_replace('/\s+/', ' ', $_POST['cod_old'] ?? '')),
                    'codigo' => trim(preg_replace('/\s+/', ' ', $_POST['codigo'] ?? '')),
                    'tipo' => trim(preg_replace('/\s+/', ' ', $_POST['tipo'] ?? '')),
                    'marca' => trim(preg_replace('/\s+/', ' ', $_POST['marca'] ?? '')),
                    'modelo' => trim(preg_replace('/\s+/', ' ', $_POST['modelo'] ?? '')),
                    'tecnologia' => trim(preg_replace('/\s+/', ' ', $_POST['tecnologia'] ?? '')),
                    'caracteristica' => trim(preg_replace('/\s+/', ' ', $_POST['caracteristica'] ?? '')),
                    'ip' => trim(preg_replace('/\s+/', ' ', $_POST['ip'] ?? '')),
                    'mac' => trim(preg_replace('/\s+/', ' ', $_POST['mac'] ?? '')),
                    'serie' => trim(preg_replace('/\s+/', ' ', $_POST['serie'] ?? '')),
                    'ubicacion' => trim(preg_replace('/\s+/', ' ', $_POST['ubicacion'] ?? '')),
                    'detalle' => trim(preg_replace('/\s+/', ' ', $_POST['detalle'] ?? '')),
                    'grabador' => trim(preg_replace('/\s+/', ' ', $_POST['grabador'] ?? ''))
                );

                if ($clean['ip'] === '' && $clean['codigo'] === '' && $clean['cod_old'] === '') { $this->json(array('ok' => false, 'message' => 'Ingrese al menos IP, código actual o antiguo.'), 400); }
                if ($clean['ubicacion'] === '') { $this->json(array('ok' => false, 'message' => 'Ingrese la ubicación de la cámara.'), 400); }
                if ($clean['detalle'] === '') { $this->json(array('ok' => false, 'message' => 'Ingrese el detalle o sitio.'), 400); }

                if ($id > 0) {
                    $p = array_values($clean); $p[] = $id;
                    $ok = $model->actualizarCamaraMaestro($p);
                    $this->json(array('ok' => $ok, 'message' => 'Cámara actualizada correctamente.'));
                } else {
                    $hasSec = $model->colExistsInInventario('sec_camara');
                    $hasCodSec = $model->colExistsInInventario('codigo_secuencial');
                    
                    if ($hasSec && $hasCodSec) {
                        $sec = $model->generarSecuencialCctv('inv_camaras');
                        $p = array($sec['numero'], $sec['codigo'], $clean['cod_old'], $clean['codigo'], $clean['tipo'], $clean['marca'], $clean['modelo'], $clean['tecnologia'], $clean['caracteristica'], $clean['ip'], $clean['mac'], $clean['serie'], $clean['ubicacion'], $clean['detalle'], $clean['grabador']);
                        $ok = $model->insertarCamaraMaestro($p, true);
                        $this->json(array('ok' => $ok, 'message' => 'Cámara registrada correctamente.', 'codigo_secuencial' => $sec['codigo']));
                    } else {
                        $p = array_values($clean);
                        $ok = $model->insertarCamaraMaestro($p, false);
                        $this->json(array('ok' => $ok, 'message' => 'Cámara registrada correctamente.'));
                    }
                }
            }
        }
    }
}