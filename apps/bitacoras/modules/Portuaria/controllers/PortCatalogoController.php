<?php
/**
 * Controlador de Catálogos Maestros.
 */
class PortCatalogoController extends PortController
{
    private $catalogosPermitidos = array(
        'personas' => array('titulo' => 'Maestro de Personas', 'subtitulo' => 'Administración de visitantes/personas registradas.', 'icono' => 'bi-person-vcard', 'boton' => 'Nueva persona'),
        'empresas' => array('titulo' => 'Maestro de Empresas', 'subtitulo' => 'Administración de empresas asociadas a visitas.', 'icono' => 'bi-building-add', 'boton' => 'Nueva empresa'),
        'funcionarios' => array('titulo' => 'Talento Humano', 'subtitulo' => 'Administración del personal que gestiona visitas.', 'icono' => 'bi-person-badge', 'boton' => 'Nuevo registro TH'),
        'destinos' => array('titulo' => 'Maestro de Destinos', 'subtitulo' => 'Administración de destinos internos.', 'icono' => 'bi-signpost-split', 'boton' => 'Nuevo destino'),
        'motivos' => array('titulo' => 'Maestro de Motivos', 'subtitulo' => 'Administración de motivos de visitas.', 'icono' => 'bi-chat-left-text', 'boton' => 'Nuevo motivo'),
        'niveles_incidente' => array('titulo' => 'Niveles de importancia', 'subtitulo' => 'Administración de niveles de visitas.', 'icono' => 'bi-exclamation-triangle', 'boton' => 'Nuevo nivel')
    );

    /** Permiso granular por catálogo (opcion=5, items 1-6) -- antes solo se
     *  chequeaba el nodo de grupo (items=0), así que revocar un catálogo
     *  puntual desde /admin/roles no bloqueaba su URL directa. */
    private const CATALOGO_PERMISO = [
        'personas' => 'canVerCatalogoPersonas',
        'empresas' => 'canVerCatalogoEmpresas',
        'destinos' => 'canVerCatalogoDestinos',
        'motivos' => 'canVerCatalogoMotivos',
        'funcionarios' => 'canVerCatalogoFuncionarios',
        'niveles_incidente' => 'canVerCatalogoNivelesIncidente',
    ];

    private function renderMaestro(string $catalogo)
    {
        Auth::guard();
        if (!isset($this->catalogosPermitidos[$catalogo])) { $catalogo = 'personas'; }
        $metodoPermiso = self::CATALOGO_PERMISO[$catalogo] ?? null;
        if ($metodoPermiso === null || !Auth::$metodoPermiso()) { $this->redirect('visitas?msg=permiso_denegado'); return; }
        
        $this->view('catalogos/maestro', array(
            'catalogo' => $catalogo,
            'infoCatalogo' => $this->catalogosPermitidos[$catalogo],
            'pageTitle' => $this->catalogosPermitidos[$catalogo]['titulo'] . ' | Autoridad Portuaria de Manta',
            'extraCss' => array($this->rutas['url_datatables_css'] ?? '', $this->rutas['url_toast_css'] ?? '', $this->rutas['url_sweetalert2_css'] ?? '')
        ));
    }

    public function index()
    {
        Auth::guard();
        if (!Auth::canGestionarMaestrosAcceso()) { $this->redirect('visitas?msg=permiso_denegado'); return; }
        $this->view('catalogos/index', array(
            'pageTitle' => 'Maestros de Acceso | Autoridad Portuaria de Manta',
            'extraCss' => array($this->rutas['url_datatables_css'] ?? '', $this->rutas['url_toast_css'] ?? '', $this->rutas['url_sweetalert2_css'] ?? '')
        ));
    }

    public function personas() { $this->renderMaestro('personas'); }
    public function empresas() { $this->renderMaestro('empresas'); }
    public function destinos() { $this->renderMaestro('destinos'); }
    public function motivos() { $this->renderMaestro('motivos'); }
    public function funcionarios() { $this->renderMaestro('funcionarios'); }
    public function nivelesIncidente() { $this->renderMaestro('niveles_incidente'); }

    private function getConfigCatalogo($catalogo, $model)
    {
        $hasCedulaFunc = $model->colExists('bit_funcionarios', 'cedula');
        $cfg = array(
            'personas' => array('table' => 'bit_personas', 'pk' => 'id_persona', 'select' => array('id_persona', 'nidentificacion', 'tidentif', 'nombres', 'apellidos'), 'allowed' => array('nidentificacion', 'tidentif', 'nombres', 'apellidos'), 'allowed_update' => array('nombres', 'apellidos'), 'required' => array('nidentificacion', 'nombres', 'apellidos', 'tidentif'), 'required_update' => array('nombres', 'apellidos'), 'searchCols' => array('nidentificacion', 'nombres', 'apellidos'), 'orderBy' => 'nombres, apellidos', 'hasEstado' => true),
            'empresas' => array('table' => 'bit_empresas', 'pk' => 'id_empresa', 'select' => array('id_empresa', 'empresa', 'razonsocial', 'ruc'), 'allowed' => array('empresa', 'razonsocial', 'ruc'), 'allowed_update' => array('empresa', 'razonsocial'), 'required' => array('empresa', 'razonsocial', 'ruc'), 'required_update' => array('empresa', 'razonsocial'), 'searchCols' => array('empresa', 'razonsocial', 'ruc'), 'orderBy' => 'empresa', 'hasEstado' => true),
            'funcionarios' => array('table' => 'bit_funcionarios', 'pk' => 'id_funcionario', 'select' => $hasCedulaFunc ? array('id_funcionario', 'cedula', 'nombre', 'cargo') : array('id_funcionario', 'nombre', 'cargo'), 'allowed' => $hasCedulaFunc ? array('cedula', 'nombre', 'cargo') : array('nombre', 'cargo'), 'allowed_update' => array('nombre', 'cargo'), 'required' => $hasCedulaFunc ? array('cedula', 'nombre', 'cargo') : array('nombre', 'cargo'), 'required_update' => array('nombre', 'cargo'), 'searchCols' => $hasCedulaFunc ? array('nombre', 'cargo', 'cedula') : array('nombre', 'cargo'), 'orderBy' => 'nombre', 'hasEstado' => true),
            'destinos' => array('table' => 'bit_destinos', 'pk' => 'id_destino', 'select' => array('id_destino', 'nombre'), 'allowed' => array('nombre'), 'allowed_update' => array('nombre'), 'required' => array('nombre'), 'required_update' => array('nombre'), 'searchCols' => array('nombre'), 'orderBy' => 'nombre', 'hasEstado' => true),
            'motivos' => array('table' => 'bit_motivos', 'pk' => 'id_motivo', 'select' => array('id_motivo', 'descripcion'), 'allowed' => array('descripcion'), 'allowed_update' => array('descripcion'), 'required' => array('descripcion'), 'required_update' => array('descripcion'), 'searchCols' => array('descripcion'), 'orderBy' => 'descripcion', 'hasEstado' => true),
            'niveles_incidente' => array('table' => 'bit_niveles_incidente', 'pk' => 'id_incidentes', 'select' => array('id_incidentes', 'nivel', 'descripcion'), 'allowed' => array('nivel', 'descripcion'), 'allowed_update' => array('descripcion'), 'required' => array('nivel', 'descripcion'), 'required_update' => array('descripcion'), 'searchCols' => array('descripcion'), 'orderBy' => 'nivel', 'hasEstado' => true)
        );
        return $cfg[$catalogo] ?? null;
    }

    private function limpiarValor($key, $v)
    {
        $v = trim((string)$v);
        if ($key === 'tidentif') {
            $map = array('C' => 'Cédula', 'CEDULA' => 'Cédula', 'P' => 'Pasaporte', 'PASAPORTE' => 'Pasaporte', 'R' => 'RUC', 'RUC' => 'RUC');
            $k = strtoupper(strtr($v, array('á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u')));
            if (!isset($map[$k])) $this->json(array('ok' => false, 'message' => 'Tipo de identificación no válido.'));
            return $map[$k];
        }
        if (in_array($key, array('nidentificacion', 'cedula', 'ruc'))) return preg_replace('/\D+/', '', $v);
        if ($key === 'nivel') {
            $n = (int)$v; if ($n < 1 || $n > 3) $this->json(array('ok' => false, 'message' => 'El nivel debe ser 1 (Normal), 2 (Medio) o 3 (Crítico)'));
            return (string)$n;
        }
        return $v;
    }

    /** API General de Catálogos (CRUD) */
    public function api()
    {
        $model = $this->model('PortCatalogoModel');
        $method = $_SERVER['REQUEST_METHOD'];
        $catalogo = trim((string)($_REQUEST['catalogo'] ?? ''));
        if ($catalogo === '') $this->json(array('ok' => false, 'message' => 'Parámetro catalogo requerido'), 400);
        $cfg = $this->getConfigCatalogo($catalogo, $model);
        if (!$cfg) $this->json(array('ok' => false, 'message' => 'Catálogo no válido'), 400);

        if ($method === 'GET') {
            $action = trim((string)($_GET['action'] ?? 'list'));
            if ($action === 'list') {
                $items = $model->listarCatalogo($cfg, trim($_GET['q'] ?? ''), (!isset($_GET['estado']) || $_GET['estado'] === '' || $_GET['estado'] === '1'));
                $this->json(array('ok' => true, 'items' => $items));
            }
            if ($action === 'get') {
                $id = (int)($_GET['id'] ?? 0);
                if ($id <= 0) $this->json(array('ok' => false, 'message' => 'Id no válido'));
                $row = $model->obtenerCatalogo($cfg, $id);
                if (!$row) $this->json(array('ok' => false, 'message' => 'Registro no encontrado'), 404);
                $this->json(array('ok' => true, 'item' => $row));
            }
            $this->json(array('ok' => false, 'message' => 'Acción GET no permitida'), 400);
        }

        if ($method === 'POST') {
            $action = trim((string)($_POST['action'] ?? ''));
            if ($action === '') $this->json(array('ok' => false, 'message' => 'Parámetro action requerido'));

            // Hueco real cerrado (permisos_centrales Fase 3, 2026-08-11):
            // hasta acá cualquier usuario autenticado del portal podía
            // crear/editar/desactivar cualquier catálogo, sin chequeo alguno.
            $nivelMinAccion = ($action === 'deactivate') ? 3 : 2; // desactivar = editar; crear/actualizar = crear
            if (!Auth::canGestionarCatalogosEscritura($nivelMinAccion)) {
                $this->json(array('ok' => false, 'message' => 'Permiso denegado.'), 403);
            }

            if ($action === 'create' || $action === 'update') {
                $id = (int)($_POST['id'] ?? 0);
                if ($action === 'update' && $id <= 0) $this->json(array('ok' => false, 'message' => 'Id no válido'));

                $allowedFields = ($action === 'update' && !empty($cfg['allowed_update'])) ? $cfg['allowed_update'] : $cfg['allowed'];
                $data = array();
                foreach ($allowedFields as $f) { if (isset($_POST[$f])) $data[$f] = $this->limpiarValor($f, $_POST[$f]); }

                $requiredList = ($action === 'create') ? $cfg['required'] : ($cfg['required_update'] ?? array());
                foreach ($requiredList as $req) { if (!isset($data[$req]) || $data[$req] === '') $this->json(array('ok' => false, 'message' => 'Falta campo obligatorio: ' . $req)); }

                // Validaciones Ecuador
                require_once ROOT_PATH . '/includes/bit_validaciones_ecuador.php';
                if ($action === 'create' && $catalogo === 'personas') {
                    if (isset($data['tidentif']) && $data['tidentif'] === 'Cédula') {
                        if (strlen($data['nidentificacion']) !== 10 || !ec_validar_cedula_ecuador($data['nidentificacion'])) $this->json(array('ok' => false, 'message' => apm_mensaje_identificacion_invalida()));
                    }
                    if (isset($data['tidentif']) && $data['tidentif'] === 'RUC') {
                        if (strlen($data['nidentificacion']) !== 13 || !ec_validar_ruc_ecuador($data['nidentificacion'])) $this->json(array('ok' => false, 'message' => apm_mensaje_identificacion_invalida()));
                    }
                }
                if ($action === 'create' && $catalogo === 'empresas') {
                    if (strlen($data['ruc']) !== 13 || !ec_validar_ruc_ecuador($data['ruc'])) $this->json(array('ok' => false, 'message' => apm_mensaje_identificacion_invalida()));
                }
                if ($action === 'create' && $catalogo === 'funcionarios' && isset($data['cedula'])) {
                    if ($data['cedula'] !== '' && (strlen($data['cedula']) !== 10 || !ec_validar_cedula_ecuador($data['cedula']))) $this->json(array('ok' => false, 'message' => apm_mensaje_identificacion_invalida()));
                }
                if ($action === 'create' && $catalogo === 'niveles_incidente') {
                    if (!$model->colExists('bit_niveles_incidente', 'nivel')) $this->json(array('ok' => false, 'message' => 'Actualice la BD.'));
                    if ($model->contarNivelesIncidente() >= 3) $this->json(array('ok' => false, 'message' => 'Ya existen los tres niveles. Solo puede editar.'));
                    if (isset($data['nivel']) && $model->existeNivelIncidente($data['nivel'])) $this->json(array('ok' => false, 'message' => 'Ese nivel ya está registrado.'));
                }

                if ($action === 'create') {
                    $newId = $model->insertarCatalogo($cfg, $data);
                    $this->json(array('ok' => true, 'id' => $newId));
                } else {
                    $ok = $model->actualizarCatalogo($cfg, $id, $data);
                    if (!$ok) $this->json(array('ok' => false, 'message' => 'Error al actualizar registro'));
                    $this->json(array('ok' => true, 'updated' => true));
                }
            }

            if ($action === 'deactivate') {
                $id = (int)($_POST['id'] ?? 0);
                if ($id <= 0) $this->json(array('ok' => false, 'message' => 'Id no válido'));
                if ($model->tieneVisitasAsociadas($catalogo, $id)) $this->json(array('ok' => false, 'message' => 'No se puede desactivar este registro porque tiene visitas históricas asociadas.'));
                $ok = $model->desactivarCatalogo($cfg, $id);
                $this->json(array('ok' => $ok, 'deactivated' => $ok));
            }
        }
    }

    /** API Exclusiva de Búsqueda y Registro Rápido de Personas */
    public function apiPersonas()
    {
        $model = $this->model('PortCatalogoModel');
        $method = $_SERVER['REQUEST_METHOD'];

        if ($method === 'GET') {
            $q = trim($_GET['cedula'] ?? ($_GET['nidentificacion'] ?? ''));
            if ($q === '') $this->json(array('ok' => false, 'message' => 'Indique identificación o nombre.'));
            
            function fmtPersona($r) { return array('id_persona' => (int)($r['id_persona']??0), 'nidentificacion' => $r['nidentificacion'], 'nombres' => $r['nombres'], 'apellidos' => $r['apellidos'], 'tidentif' => $r['tidentif'] ?? 'Cédula'); }

            $esIdentExacta = (bool)preg_match('/^\d{10}$/', $q);
            $soloDigitos = (bool)preg_match('/^\d+$/', $q);

            if ($esIdentExacta) {
                $row = $model->obtenerPersonaLocalIdentificacion($q);
                if ($row) $this->json(array('ok' => true, 'found' => true, 'data' => fmtPersona($row)));
                $rowExt = $model->obtenerPersonaExternaIdentificacion($q);
                if ($rowExt) $this->json(array('ok' => true, 'found' => true, 'data' => fmtPersona($rowExt)));
                $this->json(array('ok' => true, 'found' => false, 'nidentificacion' => $q, 'message' => 'No se encontraron registros.'));
            }

            if ($soloDigitos) {
                $results = array(); $vistos = array();
                foreach ($model->listarPersonaLocalPrefijo($q) as $r) { $f = fmtPersona($r); $results[] = $f; $vistos[$f['nidentificacion']] = true; }
                if (strlen($q) >= 2) {
                    foreach ($model->listarPersonaExternaPrefijo($q) as $re) { $f = fmtPersona($re); if (!isset($vistos[$f['nidentificacion']])) $results[] = $f; }
                }
                if (count($results) > 0) $this->json(array('ok' => true, 'found' => true, 'results' => $results));
                $this->json(array('ok' => true, 'found' => false, 'nidentificacion' => $q, 'message' => 'No se encontraron registros con este prefijo.'));
            }

            $results = array(); $vistos = array();
            foreach ($model->listarPersonaLocalNombre($q) as $r) { $f = fmtPersona($r); $results[] = $f; $vistos[$f['nidentificacion']] = true; }
            foreach ($model->listarPersonaExternaNombre($q) as $re) { $f = fmtPersona($re); if (!isset($vistos[$f['nidentificacion']])) $results[] = $f; }
            if (count($results) > 0) $this->json(array('ok' => true, 'found' => true, 'results' => $results));
            $this->json(array('ok' => true, 'found' => false, 'message' => 'No se encontraron personas con ese nombre o apellido.'));
        }

        if ($method === 'POST') {
            // Hueco real cerrado (permisos_centrales Fase 3, 2026-08-11):
            // hasta acá cualquier usuario autenticado del portal podía
            // registrar una persona nueva, sin chequeo alguno.
            if (!Auth::canGestionarPersonasEscritura()) {
                $this->json(array('ok' => false, 'message' => 'Permiso denegado.'), 403);
            }

            require_once ROOT_PATH . '/includes/bit_validaciones_ecuador.php';
            $nid = trim($_POST['nidentificacion'] ?? ($_POST['cedula'] ?? ''));
            $nom = trim($_POST['nombres'] ?? ($_POST['nombre'] ?? ''));
            $ape = trim($_POST['apellidos'] ?? ($_POST['apellido'] ?? ''));
            $tidRaw = trim($_POST['tidentif'] ?? '');
            if ($tidRaw === '' && preg_match('/^\d{10}$/', $nid)) $tidRaw = 'CEDULA';
            $tid = $this->limpiarValor('tidentif', $tidRaw);

            if ($nid === '' || $nom === '' || $ape === '') $this->json(array('ok' => false, 'message' => 'Todos los campos son obligatorios.'));

            $nidSolo = preg_replace('/\D/', '', $nid);
            if ($tid === 'Cédula') {
                if (strlen($nidSolo) !== 10 || !ec_validar_cedula_ecuador($nidSolo)) $this->json(array('ok' => false, 'message' => apm_mensaje_identificacion_invalida()));
                $nid = $nidSolo;
            } elseif ($tid === 'RUC') {
                if (strlen($nidSolo) !== 13 || !ec_validar_ruc_ecuador($nidSolo)) $this->json(array('ok' => false, 'message' => apm_mensaje_identificacion_invalida()));
                $nid = $nidSolo;
            }

            if ($nid !== '9999999999') {
                $rowEx = $model->verificarPersonaInactiva($nid);
                if ($rowEx) {
                    if ((int)$rowEx['estado'] === 1) $this->json(array('ok' => false, 'message' => 'Esta persona ya se encuentra registrada y activa.'));
                    $ok = $model->reactivarPersona($nid, $tid, $nom, $ape);
                    if ($ok) $this->json(array('ok' => true, 'found' => true, 'reactivado' => true, 'message' => 'Registro reactivado.', 'data' => array('nidentificacion' => $nid, 'nombres' => $nom, 'apellidos' => $ape, 'tidentif' => $tid)));
                    $this->json(array('ok' => false, 'message' => 'La identificación ya pertenece a otro registro o hubo error al reactivar.'));
                }
            }

            $ok = $model->insertarPersona($nid, $tid, $nom, $ape);
            if (!$ok) $this->json(array('ok' => false, 'message' => 'Error: La identificación ya pertenece a otro registro o error al guardar.'));
            $this->json(array('ok' => true, 'found' => true, 'message' => 'Persona registrada.', 'data' => array('nidentificacion' => $nid, 'nombres' => $nom, 'apellidos' => $ape, 'tidentif' => $tid)));
        }
    }

    /**
     * GET/POST /bitacoras/catalogo/importarFuncionarios — reemplaza bit_importar_funcionarios.php.
     * CSV exportado de Visual FoxPro (cedula, apellidos, nombres) -> dbo.bit_funcionarios.
     */
    public function importarFuncionarios()
    {
        Auth::guard();
        if (!Auth::canImportarFuncionarios()) { $this->redirect('visitas?msg=permiso_denegado'); return; }

        $mensaje = '';
        $errores = array();
        $importados = 0;
        $omitidos = 0;

        /** @var CatalogoModel $model */
        $model = $this->model('PortCatalogoModel');
        $tieneCedula = $model->funcionariosTieneCedula();

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_FILES['archivo_csv']['tmp_name'])) {
            $archivo = $_FILES['archivo_csv'];
            if ($archivo['error'] !== UPLOAD_ERR_OK) {
                $errores[] = 'Error al subir el archivo.';
            } else {
                $contenido = file_get_contents($archivo['tmp_name']);
                $contenido = trim(str_replace(array("\r\n", "\r"), "\n", $contenido));
                $lineas = explode("\n", $contenido);
                if (count($lineas) < 2) {
                    $errores[] = 'El archivo debe tener al menos cabecera y una fila de datos.';
                } else {
                    $cabecera = str_getcsv(array_shift($lineas), ',', '"');
                    $cabecera = array_map('trim', array_map(function ($c) {
                        return preg_replace('/[\x00-\x1F\x7F]/u', '', $c);
                    }, $cabecera));
                    $idxCedula = array_search('cedula', array_map('strtolower', $cabecera));
                    $idxApellidos = array_search('apellidos', array_map('strtolower', $cabecera));
                    $idxNombres = array_search('nombres', array_map('strtolower', $cabecera));

                    if ($idxCedula === false || $idxApellidos === false || $idxNombres === false) {
                        $errores[] = 'El CSV debe tener columnas: cedula, apellidos, nombres (cabecera en la primera línea).';
                    } else {
                        foreach ($lineas as $linea) {
                            $campos = str_getcsv($linea, ',', '"');
                            if (count($campos) <= max($idxCedula, $idxApellidos, $idxNombres)) {
                                continue;
                            }
                            $cedula = trim(preg_replace('/[^0-9]/', '', isset($campos[$idxCedula]) ? $campos[$idxCedula] : ''));
                            $apellidos = trim(isset($campos[$idxApellidos]) ? $campos[$idxApellidos] : '');
                            $nombres = trim(isset($campos[$idxNombres]) ? $campos[$idxNombres] : '');
                            if ($cedula === '' && $apellidos === '' && $nombres === '') {
                                continue;
                            }
                            $nombre = trim($nombres . ' ' . $apellidos);
                            if ($nombre === '') {
                                continue;
                            }
                            if ($cedula === '') {
                                $omitidos++;
                                continue;
                            }
                            if ($model->funcionarioExistePorCedula($cedula)) {
                                $omitidos++;
                                continue;
                            }
                            if ($model->insertarFuncionarioImportado($cedula, $nombre)) {
                                $importados++;
                            }
                        }
                        $mensaje = "Importación finalizada: {$importados} registros importados, {$omitidos} omitidos (duplicados o sin cédula).";
                    }
                }
            }
        }

        $this->view('catalogos/importarFuncionarios', array(
            'pageTitle' => 'Importar funcionarios desde CSV | Autoridad Portuaria de Manta',
            'mensaje' => $mensaje,
            'errores' => $errores,
            'tieneCedula' => $tieneCedula,
        ));
    }
}