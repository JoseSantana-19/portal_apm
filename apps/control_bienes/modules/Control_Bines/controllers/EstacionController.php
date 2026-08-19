<?php
/**
 * ESTACIONCONTROLLER.PHP - Controlador de Cabeceras Maestras (Zonas, Categorías, Unidades, etc.)
 */

require_once ROOT_PATH . 'modules/Control_Bines/models/EstacionModel.php';
require_once ROOT_PATH . 'modules/Talento_Humano/models/EmpleadoModel.php';
require_once ROOT_PATH . 'modules/Central/models/NotificacionModel.php';
require_once ROOT_PATH . 'modules/Bitacoras/models/LogModel.php';

class EstacionController extends Controller {
    private $cabeceraModel;
    private $logger;
    private $talentoModel;

    public function __construct() {
        parent::__construct();
        $this->logger = new Logger('cab');
        $this->cabeceraModel = new EstacionModel();
        $this->talentoModel = new EmpleadoModel();
    }

    /**
     * Vista principal de Tablas Maestras
     */
    public function index() {
        $this->registrarAuditoria('ACCESO', 'th', 'Acceso al módulo de Tablas de Cabecera');
        
        $tablaActiva = isset($_GET['tabla']) ? $_GET['tabla'] : 'categorias';
        
        try {
            $items = $this->cabeceraModel->obtenerTodos($tablaActiva);
            $conteos = $this->cabeceraModel->obtenerConteos();
            
            $categoriasList = [];
            $unidadesList = [];
            if ($tablaActiva === 'productos') {
                $categoriasList = $this->cabeceraModel->obtenerTodos('categorias');
                $unidadesList = $this->cabeceraModel->obtenerTodos('unidades');
            }
        } catch (Exception $e) {
            $this->redirect('cabeceras', 'Error al cargar: ' . $e->getMessage(), 'error');
        }

        $this->render('estaciones/listar', [
            'items' => $items,
            'conteos' => $conteos,
            'tablaActiva' => $tablaActiva,
            'categoriasList' => $categoriasList,
            'unidadesList' => $unidadesList
        ], 'Tablas de Cabecera - Sistema Portuario');
    }

    /**
     * Vista de Maestros
     */
    public function maestros() {
        $this->registrarAuditoria('ACCESO', 'inv_maestros', 'Acceso al módulo de Maestros');

        $tablaActiva = isset($_GET['tabla']) ? $_GET['tabla'] : 'categorias';
        $tipoBien = isset($_GET['tipo']) && $_GET['tipo'] === 'AF' ? 'AF' : 'CC';

        $tablasPermitidas = ['categorias', 'productos', 'unidades', 'tipos_iva', 'proveedores', 'grupo_centros_consumo', 'centros_consumo', 'busqueda_global'];
        if (!in_array($tablaActiva, $tablasPermitidas)) {
            $tablaActiva = 'categorias';
        }

        $items = [];
        $categoriasList = [];
        $unidadesList   = [];
        $grupoCentrosList = [];
        $tiposIvaList = [];
        $personalList = [];
        $resultados = [];
        $soloLectura = in_array($tablaActiva, ['grupo_centros_consumo', 'centros_consumo'], true)
            || ($tablaActiva === 'productos' && $tipoBien === 'AF');
        $busquedaMaestro = isset($_GET['buscar_maestro']) ? trim($_GET['buscar_maestro']) : '';
        $paginaMaestro = max(1, (int)($_GET['pagina'] ?? 1));
        $porPaginaMaestro = (int)($_GET['por_pagina'] ?? 50);
        if (!in_array($porPaginaMaestro, [25, 50, 100], true)) $porPaginaMaestro = 50;
        $paginacion = null;
        
        $q = isset($_GET['q']) ? trim($_GET['q']) : '';
        $modulosSeleccionados = isset($_GET['modulos']) && is_array($_GET['modulos']) ? $_GET['modulos'] : [];
        if (empty($modulosSeleccionados)) {
            $modulosSeleccionados = ['inventario', 'bodega', 'inv_maestros', 'usuarios', 'inv_bitacora'];
        }
        
        $campoBusqueda = isset($_GET['campo']) ? trim($_GET['campo']) : 'todos';
        $limiteResultados = isset($_GET['limite']) ? (int)$_GET['limite'] : 50;

        try {
            $conteos = $this->cabeceraModel->obtenerConteos(true);

            if ($tablaActiva !== 'busqueda_global') {
                if ($tablaActiva === 'productos') {
                    $paginacion = $this->cabeceraModel->obtenerPaginaMaestros(
                        $tablaActiva,
                        $tipoBien,
                        $busquedaMaestro,
                        $paginaMaestro,
                        $porPaginaMaestro
                    );
                    $items = $paginacion['items'];
                } else {
                    $items = $this->cabeceraModel->obtenerTodos($tablaActiva, $tipoBien, true);
                }
                
                $db = Database::getInstance()->getConnection();
                $ivasStmt = $db->query("SELECT * FROM inv_tipos_iva ORDER BY tasa_iva DESC");
                $tiposIvaList = $ivasStmt->fetchAll();

                if ($tablaActiva === 'productos') {
                    $categoriasList = $this->cabeceraModel->obtenerTodos('categorias', $tipoBien, true);
                    $unidadesList   = $this->cabeceraModel->obtenerTodos('unidades');
                }
            } else {
                if (strlen($q) >= 2) {
                    require_once ROOT_PATH . 'modules/Central/models/BusquedaGlobalModel.php';
                    $globalSearchModel = new BusquedaGlobalModel();
                    $resultados = $globalSearchModel->buscarConFiltros($q, $modulosSeleccionados, $campoBusqueda, $limiteResultados);
                    $this->registrarAuditoria('CONSULTA', 'inv_maestros', "Búsqueda global desde Maestros: '{$q}' en " . implode(', ', $modulosSeleccionados));
                }
            }
        } catch (Exception $e) {
            $this->redirect('inv_maestros', 'Error al cargar: ' . $e->getMessage(), 'error');
        }

        $this->render('estaciones/maestros', [
            'items'            => $items,
            'conteos'          => $conteos,
            'tablaActiva'      => $tablaActiva,
            'categoriasList'   => $categoriasList,
            'unidadesList'     => $unidadesList,
            'tiposIvaList'     => $tiposIvaList,
            'grupoCentrosList' => $grupoCentrosList,
            'personalList'     => $personalList,
            'tipoBien'         => $tipoBien,
            'soloLectura'      => $soloLectura,
            'busquedaMaestro'  => $busquedaMaestro,
            'paginacion'       => $paginacion,
            'q'                => $q,
            'modulosSeleccionados' => $modulosSeleccionados,
            'resultados'       => $resultados,
            'campoBusqueda'    => $campoBusqueda,
            'limiteResultados' => $limiteResultados
        ], 'Maestros - Sistema Portuario');
    }

    /**
     * Filtra una tabla específica y retorna JSON (usado por AJAX)
     */
    public function filtrarAjax() {
        $tabla = isset($_GET['tabla']) ? $_GET['tabla'] : 'categorias';
        $tipoBien = isset($_GET['tipo']) && $_GET['tipo'] === 'AF' ? 'AF' : 'CC';
        
        try {
            $items = $this->cabeceraModel->obtenerTodos($tabla, $tipoBien, true);
            $this->jsonResponse($items);
        } catch (Exception $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Guarda o edita un registro maestro (POST)
     */
    public function guardar() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('cabeceras', 'Método no permitido', 'error');
        }

        $tabla = isset($_POST['tabla']) ? trim($_POST['tabla']) : '';
        $tipoBien = isset($_POST['tipo_bien']) && $_POST['tipo_bien'] === 'AF' ? 'AF' : 'CC';
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        
        $datos = [
            'nombre' => trim($_POST['nombre']),
            'extra' => isset($_POST['extra']) ? trim($_POST['extra']) : ''
        ];
        $validacionError = null;

        if ($tabla === 'estados') {
            $datos['clase'] = isset($_POST['clase']) ? trim($_POST['clase']) : 'active';
        } elseif ($tabla === 'categorias') {
            $datos['codigo'] = isset($_POST['codigo']) ? trim($_POST['codigo']) : '';
        } elseif ($tabla === 'productos') {
            $datos['grupo_id'] = isset($_POST['grupo_id']) ? (int)$_POST['grupo_id'] : 0;
            $datos['unidad_id'] = isset($_POST['unidad_id']) ? (int)$_POST['unidad_id'] : 0;
            $datos['aplica_iva'] = isset($_POST['aplica_iva']) ? (int)$_POST['aplica_iva'] : 1;
        } elseif ($tabla === 'tipos_iva') {
            $datos['tasa_iva'] = isset($_POST['tasa_iva']) ? (float)$_POST['tasa_iva'] : 0.0;
        } elseif ($tabla === 'proveedores') {
            $datos['ruc'] = isset($_POST['ruc']) ? trim($_POST['ruc']) : '';
            $datos['codigo'] = trim($_POST['codigo'] ?? '');
            $datos['representante'] = trim($_POST['representante'] ?? '');
            $datos['direccion'] = trim($_POST['direccion'] ?? '');
            $datos['ciudad'] = trim($_POST['ciudad'] ?? '');
            $datos['email'] = trim($_POST['email'] ?? '');
            $datos['telefono1'] = trim($_POST['telefono1'] ?? '');
            $datos['telefono2'] = trim($_POST['telefono2'] ?? '');
            $datos['fax'] = trim($_POST['fax'] ?? '');
            $datos['referencia'] = trim($_POST['referencia'] ?? '');
            $datos['extra'] = implode(' | ', array_filter([$datos['direccion'], $datos['ciudad'], $datos['telefono1'], $datos['email']]));
        } elseif ($tabla === 'grupo_centros_consumo') {
            $datos['codigo'] = isset($_POST['codigo']) ? trim($_POST['codigo']) : '';
            $datos['representante_id'] = isset($_POST['representante_id']) ? (int)$_POST['representante_id'] : 0;
            $persona = $datos['representante_id'] > 0 ? $this->talentoModel->obtenerDetalleCompleto($datos['representante_id']) : null;
            if (!$persona || (int)($persona['estado'] ?? 0) !== 1) $validacionError = 'Seleccione un funcionario activo como representante.';
            $datos['representante'] = trim(($persona['apellidos'] ?? '') . ' ' . ($persona['nombres'] ?? ''));
        } elseif ($tabla === 'centros_consumo') {
            $datos['grupo_id'] = isset($_POST['grupo_id']) ? (int)$_POST['grupo_id'] : 0;
            $datos['codigo'] = isset($_POST['codigo']) ? trim($_POST['codigo']) : '';
            $datos['funcionario_id'] = isset($_POST['funcionario_id']) ? (int)$_POST['funcionario_id'] : 0;
            $persona = $datos['funcionario_id'] > 0 ? $this->talentoModel->obtenerDetalleCompleto($datos['funcionario_id']) : null;
            if (!$persona || (int)($persona['estado'] ?? 0) !== 1) $validacionError = 'Seleccione un funcionario activo para el centro de consumo.';
            $datos['funcionario'] = trim(($persona['apellidos'] ?? '') . ' ' . ($persona['nombres'] ?? ''));
        }

        $referrer = $_SERVER['HTTP_REFERER'] ?? '';
        $redirectRoute = (strpos($referrer, 'route=inv_maestros') !== false) ? 'inv_maestros' : 'cabeceras';

        try {
            if (in_array($tabla, ['grupo_centros_consumo', 'centros_consumo'], true)) {
                throw new InvalidArgumentException('Estos datos se administran en Talento Humano y en Maestros son de solo lectura.');
            }
            if ($tabla === 'productos' && $tipoBien === 'AF') {
                throw new InvalidArgumentException('Los activos fijos se muestran desde el inventario individual y son de solo lectura en Maestros.');
            }
            if ($tabla === 'categorias') {
                $prefijoEsperado = $tipoBien === 'AF' ? '1.4.' : '1.3.';
                if (strpos($datos['codigo'], $prefijoEsperado) !== 0) {
                    throw new InvalidArgumentException("El codigo debe iniciar con {$prefijoEsperado} para la clasificacion seleccionada.");
                }
            }
            if ($validacionError !== null) throw new InvalidArgumentException($validacionError);
            if ($id > 0) {
                $registro = $this->cabeceraModel->actualizar($tabla, $id, $datos);
                $this->registrarAuditoria('ACTUALIZAR', 'th', "Registro actualizado en {$tabla}: ID {$id} - {$datos['nombre']}");
                
                if (isset($_POST['is_ajax'])) {
                    $this->jsonResponse(['success' => true, 'mensaje' => 'Registro actualizado', 'registro' => $registro]);
                }
                $this->redirect($redirectRoute . '&tabla=' . $tabla . '&tipo=' . $tipoBien, 'Registro actualizado exitosamente', 'success');
            } else {
                $registro = $this->cabeceraModel->crear($tabla, $datos);
                $this->registrarAuditoria('CREAR', 'th', "Nuevo registro creado en {$tabla}: {$datos['nombre']}");
                
                $_notifModel = new NotificacionModel();
                if ($tabla === 'proveedores') {
                    $_notifModel->crear('info', 'maestro', 'Nuevo Proveedor', "Se registró al proveedor oficial <strong>{$datos['nombre']}</strong> con RUC <strong>{$datos['ruc']}</strong>.");
                } elseif ($tabla === 'productos') {
                    $_notifModel->crear('info', 'maestro', 'Nuevo Producto', "Se ha creado el ítem <strong>{$datos['nombre']}</strong> en el catálogo de productos.");
                } elseif ($tabla === 'centros_consumo') {
                    $_notifModel->crear('info', 'maestro', 'Nuevo Centro Consumo', "Se ha creado el centro de consumo <strong>{$datos['nombre']}</strong> asignado a <strong>{$datos['funcionario']}</strong>.");
                } elseif ($tabla === 'grupo_centros_consumo') {
                    $_notifModel->crear('info', 'maestro', 'Nuevo Grupo Consumo', "Se ha creado el departamento <strong>{$datos['nombre']}</strong> con código <strong>{$datos['codigo']}</strong>.");
                } else {
                    $_notifModel->crear('info', 'maestro', 'Maestro Registrado', "Se ha añadido el registro <strong>{$datos['nombre']}</strong> en la tabla maestra '{$tabla}'.");
                }

                if (isset($_POST['is_ajax'])) {
                    $this->jsonResponse(['success' => true, 'mensaje' => 'Registro creado', 'registro' => $registro]);
                }
                $this->redirect($redirectRoute . '&tabla=' . $tabla . '&tipo=' . $tipoBien, 'Registro creado exitosamente', 'success');
            }
        } catch (Exception $e) {
            $this->logger->inv_error("Error en guardar cabecera tabla={$tabla}", $e, 'guardar');
            if (isset($_POST['is_ajax'])) {
                $this->jsonResponse(['success' => false, 'mensaje' => $e->getMessage()], 500);
            }
            $this->redirect($redirectRoute . '&tabla=' . $tabla . '&tipo=' . $tipoBien, 'Error al guardar: ' . $e->getMessage(), 'error');
        }
    }

    /**
     * Elimina un registro de una tabla maestra
     */
    public function eliminar() {
        $tabla = isset($_GET['tabla']) ? trim($_GET['tabla']) : '';
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

        try {
            $registro = $this->cabeceraModel->buscarPorId($tabla, $id);
            if ($registro) {
                $this->cabeceraModel->eliminar($tabla, $id);
                $this->registrarAuditoria('ELIMINAR', 'th', "Registro eliminado de {$tabla}: {$registro['nombre']}");
                
                if (isset($_GET['is_ajax'])) {
                    $this->jsonResponse(['success' => true, 'mensaje' => 'Registro eliminado']);
                }
                $this->redirect('cabeceras&tabla=' . $tabla, 'Registro eliminado exitosamente', 'warning');
            } else {
                throw new Exception("Registro no encontrado");
            }
        } catch (Exception $e) {
            $this->logger->inv_error("Error al eliminar de tabla={$tabla} id={$id}", $e, 'eliminar');
            if (isset($_GET['is_ajax'])) {
                $this->jsonResponse(['success' => false, 'mensaje' => $e->getMessage()], 404);
            }
            $this->redirect('cabeceras&tabla=' . $tabla, 'Error al eliminar', 'error');
        }
    }

    /**
     * Endpoint AJAX para realizar búsquedas globales en las tablas maestras
     */
    public function buscarGeneralAjax() {
        $q = isset($_GET['q']) ? trim($_GET['q']) : '';
        
        if (strlen($q) < 2) {
            $this->jsonResponse(['error' => 'La búsqueda debe tener al menos 2 caracteres'], 400);
        }

        try {
            $resultados = $this->cabeceraModel->buscarGeneral($q);
            $this->jsonResponse($resultados);
        } catch (Exception $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }
}
class_alias('EstacionController', 'InvCabeceraController');
