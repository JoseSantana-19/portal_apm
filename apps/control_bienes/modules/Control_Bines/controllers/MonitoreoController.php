<?php
/**
 * MONITOREOCONTROLLER.PHP - Controlador del Módulo de Bodega (Ingresos y Egresos)
 */

require_once ROOT_PATH . 'modules/Control_Bines/models/InvIngreso.php';
require_once ROOT_PATH . 'modules/Control_Bines/models/InvEgreso.php';
require_once ROOT_PATH . 'modules/Control_Bines/models/InvNotaPedido.php';
require_once ROOT_PATH . 'modules/Control_Bines/models/InvAbastecimiento.php';
require_once ROOT_PATH . 'modules/Control_Bines/models/InvIngresoFactura.php';
require_once ROOT_PATH . 'modules/Control_Bines/models/BinModel.php';
require_once ROOT_PATH . 'modules/Talento_Humano/models/EmpleadoModel.php';
require_once ROOT_PATH . 'modules/Central/models/InvPeriodo.php';
require_once ROOT_PATH . 'modules/Central/models/InvParametro.php';
require_once ROOT_PATH . 'modules/Central/models/NotificacionModel.php';
require_once ROOT_PATH . 'modules/Bitacoras/models/LogModel.php';

class MonitoreoController extends Controller {
    private $ingresoModel;
    private $egresoModel;
    private $notaPedidoModel;
    private $abastecimientoModel;
    private $ingresoFacturaModel;
    private $inventarioModel;
    private $talentoModel;
    private $periodoModel;
    private $logger;

    public function __construct() {
        parent::__construct();
        $this->logger = new Logger('bod');
        $this->ingresoModel = new InvIngreso();
        $this->egresoModel = new InvEgreso();
        $this->notaPedidoModel = new InvNotaPedido();
        $this->abastecimientoModel = new InvAbastecimiento();
        $this->ingresoFacturaModel = new InvIngresoFactura();
        $this->inventarioModel = new BinModel();
        $this->talentoModel = new EmpleadoModel();
        $this->periodoModel = new InvPeriodo();
    }

    /** Pantalla v2: ingresos a bodega respaldados por factura. */
    public function ingresos() {
        $this->registrarAuditoria('ACCESO', 'bod', 'Acceso a Ingresos a Bodega con Factura');
        $tiempoVigenciaConsulta = max(60, min(3600, (int)(new InvParametro())->obtener('tiempo_vigencia_inventario', 600)));
        $this->render('monitoreo/ingresos', [
            'esquemaDisponible' => $this->ingresoFacturaModel->esquemaDisponible(),
            'proveedores' => $this->ingresoFacturaModel->proveedores(),
            'tiposIva' => $this->ingresoFacturaModel->tiposIva(),
            'personal' => $this->talentoModel->obtenerPersonal(),
            'csrfToken' => csrf_token(),
            'tiempoVigenciaConsulta' => $tiempoVigenciaConsulta,
            'tokenSesionConsulta' => (string)($_SESSION['inventario_sesion_token'] ?? session_id()),
        ], 'Ingresos a Bodega con Factura - Sistema Portuario');
    }

    /** Página completa para crear, consultar, editar o previsualizar una factura. */
    public function facturaIngreso(): void {
        $id=(int)($_GET['id']??0);
        $factura=$id>0?$this->ingresoFacturaModel->obtenerFactura($id):null;
        if($id>0&&!$factura){$this->redirectIngresosFactura('La factura solicitada no existe.','error');}
        $this->registrarAuditoria('ACCESO','bod',$id>0?'Consulta de factura de ingreso '.$id:'Creación de factura de ingreso');
        $this->render('monitoreo/ingreso_factura_formulario',[
            'factura'=>$factura,
            'proveedores'=>$this->ingresoFacturaModel->proveedores(),
            'tiposIva'=>$this->ingresoFacturaModel->tiposIva(),
            'personal'=>$this->talentoModel->obtenerPersonal(),
            'csrfToken'=>csrf_token(),
            'esVistaPrevia'=>!empty($_GET['preview']),
        ],($factura?'Factura '.$factura['numero_factura']:'Nueva factura').' - Ingreso a Bodega');
    }

    public function facturasIngresoDataTable(): void {
        try { $this->jsonResponse($this->ingresoFacturaModel->facturasDataTable($_GET)); }
        catch (Throwable $e) { $this->logger->inv_error('Error al listar facturas de ingreso', $e, 'facturasIngresoDataTable'); $this->jsonResponse(['error'=>'No fue posible cargar las facturas.'],500); }
    }

    public function productosFacturaDataTable(): void {
        try { $this->jsonResponse($this->ingresoFacturaModel->productosDataTable($_GET)); }
        catch (Throwable $e) { $this->logger->inv_error('Error al listar productos para factura', $e, 'productosFacturaDataTable'); $this->jsonResponse(['error'=>'No fue posible cargar los productos.'],500); }
    }

    public function proveedoresFacturaJson(): void {
        try { $this->jsonResponse(['proveedores'=>$this->ingresoFacturaModel->proveedores()]); }
        catch (Throwable $e) { $this->logger->inv_error('Error al actualizar proveedores de factura', $e, 'proveedoresFacturaJson'); $this->jsonResponse(['error'=>'No fue posible actualizar los proveedores.'],500); }
    }

    public function obtenerFacturaIngreso(): void {
        try {
            $factura=$this->ingresoFacturaModel->obtenerFactura((int)($_GET['id']??0));
            if(!$factura) $this->jsonResponse(['error'=>'Factura no encontrada.'],404);
            $this->jsonResponse(['factura'=>$factura]);
        } catch(Throwable $e) { $this->logger->inv_error('Error al consultar factura', $e, 'obtenerFacturaIngreso'); $this->jsonResponse(['error'=>'No fue posible consultar la factura.'],500); }
    }

    public function guardarFacturaIngreso(): void {
        if(($_SERVER['REQUEST_METHOD']??'')!=='POST' || !verify_csrf_token($_POST['csrf_token']??'')) $this->redirectIngresosFactura('Solicitud no válida o vencida.','error');
        try {
            $id=$this->ingresoFacturaModel->guardar([
                'numero_factura'=>$_POST['numero_factura']??'', 'fecha_factura'=>$_POST['fecha_factura']??date('Y-m-d'),
                'proveedor_id'=>(int)($_POST['proveedor_id']??0), 'descripcion'=>$_POST['descripcion']??'',
            ],is_array($_POST['items']??null)?$_POST['items']:[],$this->usuarioActual(),(int)($_POST['factura_id']??0));
            $this->redirectFacturaIngreso($id,'Factura guardada correctamente. La orden de compra se generó automáticamente.','success');
        } catch(Throwable $e) { $this->logger->inv_error('Error al guardar factura de ingreso',$e,'guardarFacturaIngreso'); $this->redirectIngresosFactura($this->mensajeSeguroFactura($e),'error'); }
    }

    public function anularFacturaIngreso(): void {
        if(($_SERVER['REQUEST_METHOD']??'')!=='POST' || !verify_csrf_token($_POST['csrf_token']??'')) $this->redirectIngresosFactura('Solicitud no válida o vencida.','error');
        try { $id=(int)($_POST['factura_id']??0); $this->ingresoFacturaModel->anular($id,(string)($_POST['motivo']??''),$this->usuarioActual()); $this->redirectFacturaIngreso($id,'Factura anulada correctamente.','success'); }
        catch(Throwable $e){ $this->logger->inv_error('Error al anular factura',$e,'anularFacturaIngreso'); $this->redirectIngresosFactura($this->mensajeSeguroFactura($e),'error'); }
    }

    public function confirmarIngresoFactura(): void {
        if(($_SERVER['REQUEST_METHOD']??'')!=='POST' || !verify_csrf_token($_POST['csrf_token']??'')) $this->redirectIngresosFactura('Solicitud no válida o vencida.','error');
        try {
            $id=(int)($_POST['factura_id']??0);
            $this->abastecimientoModel->crearIngresoDesdeFactura($id,(int)($_POST['responsable_id']??0),$this->usuarioActual(),trim((string)($_POST['observaciones']??'')),$_POST['fecha_ingreso']??date('Y-m-d'));
            $this->redirectFacturaIngreso($id,'Ingreso confirmado; se actualizaron existencias, costo promedio y Kardex.','success');
        } catch(Throwable $e){ $this->logger->inv_error('Error al confirmar ingreso con factura',$e,'confirmarIngresoFactura'); $this->redirectIngresosFactura($this->mensajeSeguroFactura($e),'error'); }
    }

    private function redirectIngresosFactura(string $mensaje,string $tipo): void {
        $_SESSION['toast']=['mensaje'=>$mensaje,'tipo'=>$tipo]; header('Location: index.php?route=ingresos'); exit;
    }

    private function redirectFacturaIngreso(int $id,string $mensaje,string $tipo): void {
        $_SESSION['toast']=['mensaje'=>$mensaje,'tipo'=>$tipo];
        header('Location: index.php?route=ingresos&action=facturaIngreso&id='.(int)$id); exit;
    }

    private function mensajeSeguroFactura(Throwable $e): string {
        if ($e instanceof InvalidArgumentException || $e instanceof RuntimeException) return $e->getMessage();
        return 'No fue posible completar la operación. El detalle técnico quedó registrado en la bitácora.';
    }

    /** Paso 1: requisiciones internas de productos. */
    public function requisiciones() {
        $this->registrarAuditoria('ACCESO', 'bod', 'Acceso a Requisiciones de Bodega');
        $this->render('monitoreo/requisiciones', [
            'notas' => $this->notaPedidoModel->obtenerTodos(),
            'personal' => $this->talentoModel->obtenerPersonal(),
            'itemsInventario' => $this->inventarioModel->obtenerActivos(),
        ], 'Requisiciones - Sistema Portuario');
    }

    /** Paso 2: órdenes de compra, reutilizando el flujo existente. */
    public function ordenesCompra() {
        $this->registrarAuditoria('ACCESO', 'bod', 'Acceso a Órdenes de Compra');
        $vistaActiva = 'ordenes';
        $esquemaDisponible = $this->abastecimientoModel->esquemaDisponible();
        $db = Database::getInstance()->getConnection();
        $datos = [
            'vistaActiva' => $vistaActiva,
            'esquemaDisponible' => $esquemaDisponible,
            'resumen' => ['notas' => 0, 'ordenes_pendientes' => 0, 'facturas_pendientes' => 0, 'ingresos' => 0],
            'notasCompra' => [], 'ordenesCompra' => [], 'facturasCompra' => [],
            'ingresosCompra' => [], 'kardexEntradas' => [],
            'personal' => $this->talentoModel->obtenerPersonal(),
            'itemsInventario' => $this->inventarioModel->obtenerActivos(),
            'proveedores' => $db->query('SELECT * FROM inv_proveedores ORDER BY nombre')->fetchAll(),
            'tiposIva' => $db->query('SELECT id, nombre, tasa_iva FROM inv_tipos_iva ORDER BY tasa_iva DESC, nombre')->fetchAll(),
            'periodoActivo' => $this->periodoModel->obtenerPeriodoActivo(),
        ];
        if ($esquemaDisponible) {
            $this->abastecimientoModel->prepararDocumentosFactura();
            $datos['resumen'] = $this->abastecimientoModel->resumen();
            $datos['ordenesCompra'] = $this->abastecimientoModel->listarOrdenes();
            $datos['facturasCompra'] = $this->abastecimientoModel->listarFacturas();
            $datos['ingresosCompra'] = $this->abastecimientoModel->listarIngresos();
            $datos['kardexEntradas'] = $this->abastecimientoModel->listarKardexEntradas();
        }
        $datos['flujoSeparado'] = true;
        $this->render('monitoreo/egresos', $datos, 'Órdenes de Compra - Sistema Portuario');
    }

    /** Paso 4: despacho de requisiciones y salida real de existencias. */
    public function egresos() {
        $this->registrarAuditoria('ACCESO', 'bod', 'Acceso a Egresos de Bodega');
        $notas = $this->notaPedidoModel->obtenerTodos();
        $notasPendientes = [];
        foreach ($notas as $nota) {
            if (in_array($nota['estado'], ['ATENDIDA', 'CERRADA', 'CANCELADA'], true)) continue;
            $detalle = $this->notaPedidoModel->buscarPorId((int)$nota['id_nota']);
            if ($detalle) $notasPendientes[] = $detalle;
        }
        $this->render('monitoreo/egresos_bodega', [
            'notasPendientes' => $notasPendientes,
            'egresos' => $this->egresoModel->obtenerTodos(),
            'personal' => $this->talentoModel->obtenerPersonal(),
        ], 'Egresos de Bodega - Sistema Portuario');
    }

    public function guardarOrdenCompra() {
        $this->exigirPostAbastecimiento();
        try {
            $this->abastecimientoModel->crearOrden([
                'fecha' => $_POST['fecha'] ?? date('Y-m-d'),
                'nota_pedido_id' => (int)($_POST['nota_pedido_id'] ?? 0),
                'proveedor_id' => (int)($_POST['proveedor_id'] ?? 0),
                'observaciones' => trim($_POST['observaciones'] ?? ''),
                'creado_por' => $this->usuarioActual(),
            ], is_array($_POST['items'] ?? null) ? $_POST['items'] : []);
            $this->redirectAbastecimiento('ordenes', 'Orden creada y pendiente de aprobación.', 'success');
        } catch (Throwable $e) {
            $this->logger->inv_error('Error al crear orden de compra', $e, 'guardarOrdenCompra');
            $this->redirectAbastecimiento('ordenes', $e->getMessage(), 'error');
        }
    }

    public function editarOrdenCompra() {
        $this->exigirPostAbastecimiento();
        try {
            $this->abastecimientoModel->actualizarOrden(
                (int)($_POST['orden_id'] ?? 0),
                [
                    'fecha' => $_POST['fecha'] ?? date('Y-m-d'),
                    'proveedor_id' => (int)($_POST['proveedor_id'] ?? 0),
                    'observaciones' => trim($_POST['observaciones'] ?? ''),
                ],
                is_array($_POST['items'] ?? null) ? $_POST['items'] : [],
                $this->usuarioActual()
            );
            $this->redirectAbastecimiento('ordenes', 'Orden actualizada correctamente.', 'success');
        } catch (Throwable $e) {
            $this->logger->inv_error('Error al editar orden de compra', $e, 'editarOrdenCompra');
            $this->redirectAbastecimiento('ordenes', $e->getMessage(), 'error');
        }
    }

    public function aprobarOrdenCompra() {
        $this->exigirPostAbastecimiento();
        try {
            $this->abastecimientoModel->aprobarOrden((int)($_POST['orden_id'] ?? 0), $this->usuarioActual());
            $this->redirectAbastecimiento('ordenes', 'Orden aprobada; ya puede registrarse la factura.', 'success');
        } catch (Throwable $e) {
            $this->redirectAbastecimiento('ordenes', $e->getMessage(), 'error');
        }
    }

    public function guardarFacturaCompra() {
        $this->exigirPostAbastecimiento();
        $archivo = null;
        try {
            $archivo = $this->procesarArchivoFactura();
            $this->abastecimientoModel->crearFactura([
                'orden_compra_id' => (int)($_POST['orden_compra_id'] ?? 0),
                'proveedor_id' => (int)($_POST['proveedor_id'] ?? 0),
                'numero_factura' => trim($_POST['numero_factura'] ?? ''),
                'fecha_factura' => $_POST['fecha_factura'] ?? date('Y-m-d'),
                'iva_porcentaje' => $this->resolverIvaAplicable(),
                'creado_por' => $this->usuarioActual(),
                'archivo_nombre_original' => $archivo['nombre_original'] ?? null,
                'archivo_ruta' => $archivo['ruta_relativa'] ?? null,
                'archivo_mime' => $archivo['mime'] ?? null,
                'ocr_texto' => mb_substr(trim((string)($_POST['ocr_texto'] ?? '')), 0, 60000, 'UTF-8'),
            ], is_array($_POST['items'] ?? null) ? $_POST['items'] : []);
            $this->redirectAbastecimiento('ingresos', 'Factura registrada. Revisa su movimiento y confirma el ingreso a bodega.', 'success');
        } catch (Throwable $e) {
            if ($archivo && !empty($archivo['ruta_absoluta']) && is_file($archivo['ruta_absoluta'])) @unlink($archivo['ruta_absoluta']);
            $this->logger->inv_error('Error al registrar factura de compra', $e, 'guardarFacturaCompra');
            $this->redirectAbastecimiento('facturas', $e->getMessage(), 'error');
        }
    }

    public function editarFacturaCompra() {
        $this->exigirPostAbastecimiento();
        $archivo = null;
        try {
            $archivo = $this->procesarArchivoFactura();
            $rutaAnterior = $this->abastecimientoModel->actualizarFactura(
                (int)($_POST['factura_id'] ?? 0),
                [
                    'proveedor_id' => (int)($_POST['proveedor_id'] ?? 0),
                    'numero_factura' => trim($_POST['numero_factura'] ?? ''),
                    'fecha_factura' => $_POST['fecha_factura'] ?? date('Y-m-d'),
                    'iva_porcentaje' => $this->resolverIvaAplicable(),
                    'archivo_nombre_original' => $archivo['nombre_original'] ?? null,
                    'archivo_ruta' => $archivo['ruta_relativa'] ?? null,
                    'archivo_mime' => $archivo['mime'] ?? null,
                    'ocr_texto' => mb_substr(trim((string)($_POST['ocr_texto'] ?? '')), 0, 60000, 'UTF-8'),
                ],
                is_array($_POST['items'] ?? null) ? $_POST['items'] : [],
                $this->usuarioActual()
            );
            if ($rutaAnterior !== null && $rutaAnterior !== '') {
                $base = realpath(STORAGE_PATH . 'facturas');
                $anterior = realpath(STORAGE_PATH . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $rutaAnterior));
                if ($base !== false && $anterior !== false && strpos($anterior, $base . DIRECTORY_SEPARATOR) === 0 && is_file($anterior)) @unlink($anterior);
            }
            $this->redirectAbastecimiento('facturas', 'Factura actualizada correctamente.', 'success');
        } catch (Throwable $e) {
            if ($archivo && !empty($archivo['ruta_absoluta']) && is_file($archivo['ruta_absoluta'])) @unlink($archivo['ruta_absoluta']);
            $this->logger->inv_error('Error al editar factura de compra', $e, 'editarFacturaCompra');
            $this->redirectAbastecimiento('facturas', $e->getMessage(), 'error');
        }
    }

    public function verDocumentoFactura() {
        $id = (int)($_GET['id'] ?? 0);
        $documento = $this->abastecimientoModel->obtenerDocumentoFactura($id);
        if (!$documento || empty($documento['archivo_ruta'])) {
            http_response_code(404);
            exit('Documento de factura no disponible.');
        }

        $base = realpath(STORAGE_PATH . 'facturas');
        $ruta = realpath(STORAGE_PATH . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, (string)$documento['archivo_ruta']));
        if ($base === false || $ruta === false || strpos($ruta, $base . DIRECTORY_SEPARATOR) !== 0 || !is_file($ruta)) {
            http_response_code(404);
            exit('Archivo no encontrado.');
        }

        $nombre = preg_replace('/[^A-Za-z0-9._-]/', '_', (string)($documento['archivo_nombre_original'] ?: ('factura-' . $id)));
        header('Content-Type: ' . ($documento['archivo_mime'] ?: 'application/octet-stream'));
        header('Content-Length: ' . filesize($ruta));
        header('Content-Disposition: inline; filename="' . $nombre . '"');
        header('X-Content-Type-Options: nosniff');
        readfile($ruta);
        exit;
    }

    public function ingresarFacturaBodega() {
        $this->exigirPostAbastecimiento();
        try {
            $this->abastecimientoModel->crearIngresoDesdeFactura(
                (int)($_POST['factura_id'] ?? 0), (int)($_POST['responsable_id'] ?? 0),
                $this->usuarioActual(), trim($_POST['observaciones'] ?? ''),
                $_POST['fecha_ingreso'] ?? date('Y-m-d')
            );
            $this->redirectAbastecimiento('ingresos', 'Ingreso registrado; existencia y costo promedio actualizados.', 'success');
        } catch (Throwable $e) {
            $this->logger->inv_error('Error al ingresar factura a bodega', $e, 'ingresarFacturaBodega');
            $this->redirectAbastecimiento('facturas', $e->getMessage(), 'error');
        }
    }

    private function exigirPostAbastecimiento(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') $this->redirectAbastecimiento('ordenes', 'Método no permitido.', 'error');
        if (!$this->abastecimientoModel->esquemaDisponible()) $this->redirectAbastecimiento('ordenes', 'Primero debe aplicarse la migración del módulo.', 'error');
    }

    private function procesarArchivoFactura(): ?array {
        if (!isset($_FILES['factura_archivo']) || (int)$_FILES['factura_archivo']['error'] === UPLOAD_ERR_NO_FILE) return null;
        $archivo = $_FILES['factura_archivo'];
        if ((int)$archivo['error'] !== UPLOAD_ERR_OK) throw new RuntimeException('No fue posible recibir el archivo de la factura.');
        if ((int)$archivo['size'] <= 0 || (int)$archivo['size'] > 10 * 1024 * 1024) throw new RuntimeException('La factura debe pesar máximo 10 MB.');
        if (!is_uploaded_file($archivo['tmp_name'])) throw new RuntimeException('El archivo de factura no es válido.');

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = (string)$finfo->file($archivo['tmp_name']);
        $extensiones = ['application/pdf' => 'pdf', 'image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        if (!isset($extensiones[$mime])) throw new RuntimeException('Use una factura en PDF, JPG, PNG o WEBP.');

        $subruta = 'facturas/' . date('Y/m');
        $directorio = STORAGE_PATH . str_replace('/', DIRECTORY_SEPARATOR, $subruta);
        if (!is_dir($directorio) && !mkdir($directorio, 0775, true) && !is_dir($directorio)) throw new RuntimeException('No se pudo preparar el almacenamiento de facturas.');
        $nombreInterno = bin2hex(random_bytes(16)) . '.' . $extensiones[$mime];
        $rutaAbsoluta = $directorio . DIRECTORY_SEPARATOR . $nombreInterno;
        if (!move_uploaded_file($archivo['tmp_name'], $rutaAbsoluta)) throw new RuntimeException('No se pudo guardar el archivo de la factura.');

        return [
            'nombre_original' => mb_substr(basename((string)$archivo['name']), 0, 255, 'UTF-8'),
            'ruta_relativa' => $subruta . '/' . $nombreInterno,
            'ruta_absoluta' => $rutaAbsoluta,
            'mime' => $mime,
        ];
    }

    private function usuarioActual(): string {
        return $_SESSION['usuario']['nombre'] ?? ($_SESSION['nombre'] ?? 'Sistema');
    }

    /** Resuelve la tasa únicamente desde una fuente autorizada del sistema. */
    private function resolverIvaAplicable(): float {
        $opcion = trim((string)($_POST['iva_opcion'] ?? ''));
        if ($opcion === 'no_aplica') return 0.0;

        if ($opcion === 'periodo_actual') {
            $periodo = $this->periodoModel->obtenerPeriodoActivo();
            if (!$periodo || $periodo['tasa_iva'] === null) {
                throw new InvalidArgumentException('No existe un período activo con una tasa de IVA configurada.');
            }
            return max(0.0, min(100.0, (float)$periodo['tasa_iva']));
        }

        if (strpos($opcion, 'maestro:') === 0) {
            $tipoId = (int)substr($opcion, 8);
            $stmt = Database::getInstance()->getConnection()->prepare('SELECT tasa_iva FROM inv_tipos_iva WHERE id = :id');
            $stmt->execute([':id' => $tipoId]);
            $tasa = $stmt->fetchColumn();
            if ($tasa === false) throw new InvalidArgumentException('La tasa seleccionada ya no existe en Maestros.');
            return max(0.0, min(100.0, (float)$tasa));
        }

        throw new InvalidArgumentException('Seleccione el IVA desde Maestros, el período actual o la opción no aplicable.');
    }

    private function redirectAbastecimiento(string $vista, string $mensaje, string $tipo): void {
        $_SESSION['toast'] = ['mensaje' => $mensaje, 'tipo' => $tipo];
        $route = $vista === 'ordenes' ? 'ordenes_compra' : 'ingresos';
        header('Location: index.php?route=' . rawurlencode($route));
        exit;
    }

    /** Registra la solicitud y divide automáticamente CC y AF. */
    public function guardarSolicitud() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('requisiciones', 'Método no permitido', 'error');
        }
        $detalles = [];
        foreach (($_POST['items'] ?? []) as $item) {
            if (!empty($item['item_id']) && (int)($item['cantidad'] ?? 0) > 0) {
                $detalles[] = ['item_id' => (int)$item['item_id'], 'cantidad' => (int)$item['cantidad']];
            }
        }
        try {
            $personaCentroId = (int)($_POST['centro_consumo_persona_id'] ?? 0);
            $centroConsumoId = $this->notaPedidoModel->obtenerOCrearCentroParaPersona($personaCentroId);
            $notas = $this->notaPedidoModel->crearSolicitud([
                'centro_consumo_id' => $centroConsumoId,
                'solicitante_id' => (int)($_POST['solicitante_id'] ?? 0),
                'fecha_solicitud' => !empty($_POST['fecha_solicitud']) ? $_POST['fecha_solicitud'] : date('Y-m-d'),
                'motivo' => trim($_POST['motivo'] ?? ''),
                'observaciones' => trim($_POST['observaciones'] ?? ''),
                'creado_por' => $_SESSION['usuario']['nombre'] ?? 'Sistema',
            ], $detalles);
            $cantidad = count($notas);
            $this->logger->info("CREAR_SOLICITUD: {$cantidad} nota(s) generada(s)", 'guardarSolicitud');
            $this->redirect('requisiciones', "Requisición registrada. Se generaron {$cantidad} nota(s) de pedido.", 'success');
        } catch (Throwable $e) {
            $this->logger->inv_error('Error al crear solicitud digital', $e, 'guardarSolicitud');
            $this->redirect('requisiciones', $e->getMessage(), 'error');
        }
    }

    /** Genera el egreso confirmado desde los productos disponibles de una nota. */
    public function despacharNota() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('egresos', 'Método no permitido', 'error');
        }
        try {
            $egresoId = $this->egresoModel->crearDesdeNota(
                (int)($_POST['nota_id'] ?? 0),
                (int)($_POST['receptor_id'] ?? 0),
                is_array($_POST['cantidades'] ?? null) ? $_POST['cantidades'] : [],
                $_SESSION['usuario']['nombre'] ?? 'Sistema',
                trim($_POST['observaciones'] ?? '')
            );
            $egreso = $this->egresoModel->buscarPorId($egresoId);
            $codigo = $egreso['secuencial'] ?? (string)$egresoId;
            $this->logger->info("DESPACHAR_NOTA: Egreso {$codigo} confirmado", 'despacharNota');
            $this->redirect('egresos', "Egreso {$codigo} generado y Kardex actualizado.", 'success');
        } catch (Throwable $e) {
            $this->logger->inv_error('Error al despachar nota de pedido', $e, 'despacharNota');
            $this->redirect('egresos', $e->getMessage(), 'error');
        }
    }

    public function verNota() {
        $nota = $this->notaPedidoModel->buscarPorId((int)($_GET['id'] ?? 0));
        if (!$nota) {
            $this->jsonResponse(['error' => 'Nota no encontrada'], 404);
        }
        $this->jsonResponse($nota);
    }

    public function marcarSinExistencias() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('egresos', 'Método no permitido', 'error');
        }
        try {
            $this->notaPedidoModel->marcarSinExistencias(
                (int)($_POST['nota_id'] ?? 0),
                $_SESSION['usuario']['nombre'] ?? 'Sistema'
            );
            $this->redirect('egresos', 'La nota quedó marcada sin existencias y continuará pendiente.', 'warning');
        } catch (Throwable $e) {
            $this->logger->inv_error('Error al revisar disponibilidad de la nota', $e, 'marcarSinExistencias');
            $this->redirect('egresos', $e->getMessage(), 'error');
        }
    }

    /**
     * Guarda un registro (Ingreso o Egreso) según la ruta
     */
    public function guardar() {
        if (isset($_GET['route']) && $_GET['route'] === 'ingresos') {
            return $this->guardarIngreso();
        } else {
            return $this->guardarEgreso();
        }
    }

    private function guardarIngreso() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('ingresos', 'Método no permitido', 'error');
        }

        $datosCabecera = [
            'proveedor'      => trim($_POST['proveedor']),
            'fecha'          => !empty($_POST['fecha']) ? $_POST['fecha'] : date('Y-m-d'),
            'responsable_id' => (int)$_POST['responsable_id'],
            'observaciones'  => trim($_POST['observaciones']),
            'creado_por'     => $_SESSION['usuario']['nombre'] ?? 'Admin Terminal'
        ];

        $detalles = [];
        if (isset($_POST['items']) && is_array($_POST['items'])) {
            foreach ($_POST['items'] as $item) {
                if (!empty($item['item_id']) && isset($item['cantidad'])) {
                    $detalles[] = [
                        'item_id'        => (int)$item['item_id'],
                        'cantidad'       => (int)$item['cantidad'],
                        'valor_unitario' => (float)$item['valor_unitario']
                    ];
                }
            }
        }

        if (empty($detalles)) {
            $this->redirect('ingresos', 'Debe agregar al menos un insumo con cantidad válida.', 'error');
        }

        try {
            $ingresoId = $this->ingresoModel->crear($datosCabecera, $detalles);
            $this->logger->info('CREAR_INGRESO: Ingreso de bodega registrado', 'guardar');
            
            $ingresoGuardado = $this->ingresoModel->buscarPorId($ingresoId);
            if ($ingresoGuardado) {
                $_notifModel = new NotificacionModel();
                $_notifModel->crear('info', 'ingreso', 'Ingreso Registrado', "Se ha registrado el ingreso Nro. <strong>{$ingresoGuardado['secuencial']}</strong> de la bodega por compra al proveedor <strong>{$datosCabecera['proveedor']}</strong>.", $ingresoGuardado['secuencial']);
            }

            $this->redirect('ingresos', 'Ingreso de bodega registrado exitosamente', 'success');
        } catch (Exception $e) {
            $this->logger->inv_error('Error al guardar ingreso de bodega', $e, 'guardar');
            $this->redirect('ingresos', 'Error al guardar el ingreso', 'error');
        }
    }

    private function guardarEgreso() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('egresos', 'Método no permitido', 'error');
        }

        $datosCabecera = [
            'area_id'        => (int)$_POST['area_id'],
            'responsable_id' => (int)$_POST['responsable_id'],
            'fecha'          => !empty($_POST['fecha']) ? $_POST['fecha'] : date('Y-m-d'),
            'motivo'         => trim($_POST['motivo']),
            'observaciones'  => trim($_POST['observaciones']),
            'creado_por'     => $_SESSION['usuario']['nombre'] ?? 'Admin Terminal'
        ];

        $detalles = [];
        if (isset($_POST['items']) && is_array($_POST['items'])) {
            foreach ($_POST['items'] as $item) {
                if (!empty($item['item_id']) && isset($item['cantidad'])) {
                    $detalles[] = [
                        'item_id'  => (int)$item['item_id'],
                        'cantidad' => (int)$item['cantidad']
                    ];
                }
            }
        }

        if (empty($detalles)) {
            $this->redirect('egresos', 'Debe agregar al menos un insumo con cantidad válida.', 'error');
        }

        try {
            $egresoId = $this->egresoModel->crear($datosCabecera, $detalles);
            $this->logger->info('CREAR_EGRESO: Egreso de bodega registrado', 'guardar');
            
            $egresoGuardado = $this->egresoModel->buscarPorId($egresoId);
            if ($egresoGuardado) {
                $_notifModel = new NotificacionModel();
                $_notifModel->crear('info', 'egreso', 'Egreso Registrado', "Se ha registrado el egreso Nro. <strong>{$egresoGuardado['secuencial']}</strong> con destino al Centro de Consumo <strong>{$egresoGuardado['area_destino']}</strong>.", $egresoGuardado['secuencial']);
            }

            $this->redirect('egresos', 'Egreso de bodega registrado exitosamente', 'success');
        } catch (Exception $e) {
            $this->logger->inv_error('Error al procesar egreso de bodega', $e, 'guardar');
            $this->redirect('egresos', 'Error al procesar el egreso', 'error');
        }
    }

    /**
     * Consulta detalle en JSON
     */
    public function verDetalle() {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if (isset($_GET['route']) && $_GET['route'] === 'ingresos') {
            $ingreso = $this->ingresoModel->buscarPorId($id);
            if ($ingreso) {
                $this->registrarAuditoria('CONSULTA', 'bod', 'Detalle consultado de Ingreso: ' . $ingreso['secuencial']);
                $this->jsonResponse($ingreso);
            } else {
                $this->jsonResponse(['error' => 'Registro no encontrado'], 404);
            }
        } else {
            $egreso = $this->egresoModel->buscarPorId($id);
            if ($egreso) {
                $this->registrarAuditoria('CONSULTA', 'bod', 'Detalle consultado de Egreso: ' . $egreso['secuencial']);
                $this->jsonResponse($egreso);
            } else {
                $this->jsonResponse(['error' => 'Registro no encontrado'], 404);
            }
        }
    }

    /**
     * Genera Acta imprimible
     */
    public function acta() {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if (isset($_GET['route']) && $_GET['route'] === 'ingresos') {
            $ingreso = $this->ingresoModel->buscarPorId($id);
            if (!$ingreso) {
                die("Error: El ingreso a bodega solicitado no existe.");
            }
            $this->registrarAuditoria('EXPORTAR', 'bod', 'Generado documento impreso Acta de Ingreso: ' . $ingreso['secuencial']);
            $this->renderActa('monitoreo/inv_acta_ingreso', ['ingreso' => $ingreso]);
        } else {
            $egreso = $this->egresoModel->buscarPorId($id);
            if (!$egreso) {
                die("Error: El egreso de bodega solicitado no existe.");
            }
            $this->registrarAuditoria('EXPORTAR', 'bod', 'Generado documento impreso Acta de Egreso: ' . $egreso['secuencial']);
            $this->renderActa('monitoreo/inv_acta_egreso', ['egreso' => $egreso]);
        }
    }
}
class_alias('MonitoreoController', 'InvIngresosController');
class_alias('MonitoreoController', 'InvEgresosController');
