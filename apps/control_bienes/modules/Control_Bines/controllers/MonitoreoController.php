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
require_once ROOT_PATH . 'modules/Control_Bines/models/EstacionModel.php';
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

    /** Relaciona por nombre o crea con códigos internos los productos leídos del PDF. */
    public function resolverProductosEscaneadosFactura(): void {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || !verify_csrf_token($_POST['csrf_token'] ?? '')) {
            $this->jsonResponse(['error'=>'La solicitud no es válida o venció.'],419);
        }
        try {
            $lineas = json_decode((string)($_POST['lineas'] ?? '[]'), true, 64, JSON_THROW_ON_ERROR);
            if (!is_array($lineas)) throw new InvalidArgumentException('Los productos detectados no tienen un formato válido.');
            $productos = $this->ingresoFacturaModel->resolverProductosEscaneados($lineas);
            $creados = count(array_filter($productos, static fn(array $item): bool => !empty($item['creado'])));
            if ($creados > 0) {
                $this->registrarAuditoria('CREAR','bod',$creados . ' producto(s) creado(s) desde escaneo de factura');
            }
            $this->jsonResponse(['productos'=>$productos, 'creados'=>$creados]);
        } catch (Throwable $e) {
            $this->logger->inv_error('Error al resolver productos escaneados de factura',$e,'resolverProductosEscaneadosFactura');
            $mensaje = $e instanceof InvalidArgumentException ? $e->getMessage() : 'No fue posible relacionar o crear los productos detectados.';
            $this->jsonResponse(['error'=>$mensaje],422);
        }
    }

    /** Recupera la cabecera y todos los productos de una requisición para la factura. */
    public function buscarRequisicionFactura(): void {
        try {
            $numero = trim((string)($_GET['numero'] ?? ''));
            if ($numero === '') $this->jsonResponse(['encontrada'=>false,'mensaje'=>'Escriba el número de requisición.']);
            $requisicion = $this->notaPedidoModel->buscarNotaPedidoPorNumero($numero);
            if (!$requisicion) $this->jsonResponse(['encontrada'=>false,'mensaje'=>'La requisición no está registrada.']);
            $this->jsonResponse(['encontrada'=>true,'nota'=>$requisicion]);
        } catch (Throwable $e) {
            $this->logger->inv_error('Error al buscar requisición para factura', $e, 'buscarRequisicionFactura');
            $this->jsonResponse(['encontrada'=>false,'mensaje'=>'No fue posible consultar la requisición.'],500);
        }
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
            $this->redirectFacturaIngreso($id,'Factura guardada correctamente. La orden de compra se generó automáticamente.','success',true);
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

    private function redirectFacturaIngreso(int $id,string $mensaje,string $tipo,bool $borradorGuardado=false): void {
        $_SESSION['toast']=['mensaje'=>$mensaje,'tipo'=>$tipo];
        header('Location: index.php?route=ingresos&action=facturaIngreso&id='.(int)$id.($borradorGuardado?'&borrador_guardado=1':'')); exit;
    }

    private function mensajeSeguroFactura(Throwable $e): string {
        if ($e instanceof InvalidArgumentException || $e instanceof RuntimeException) return $e->getMessage();
        return 'No fue posible completar la operación. El detalle técnico quedó registrado en la bitácora.';
    }

    /** Paso 1: requisiciones internas de productos. */
    public function requisiciones() {
        $this->registrarAuditoria('ACCESO', 'bod', 'Acceso a Requisiciones de Bodega');
        $this->render('monitoreo/requisiciones', [
            'periodoActivo' => $this->periodoModel->obtenerPeriodoActivo(),
        ], 'Requisiciones - Sistema Portuario');
    }

    public function requisicionesDataTable(): void {
        try { $this->jsonResponse($this->notaPedidoModel->requisicionesDataTable($_GET)); }
        catch (Throwable $e) { $this->logger->inv_error('Error al listar requisiciones', $e, 'requisicionesDataTable'); $this->jsonResponse(['error'=>'No fue posible cargar las requisiciones.'],500); }
    }

    /** Pestaña independiente para registrar una nueva requisición. */
    public function nuevaRequisicion(): void {
        $this->registrarAuditoria('ACCESO', 'bod', 'Acceso a Nueva Requisición de Bodega');
        $this->render('monitoreo/requisicion_nueva', [
            'personal' => $this->talentoModel->obtenerPersonal(),
            'gruposCentros' => $this->talentoModel->obtenerAreas(),
            'periodoActivo' => $this->periodoModel->obtenerPeriodoActivo(),
        ], 'Nueva requisición - Sistema Portuario');
    }

    /** Consulta opcional de una nota para completar la grilla de requisición. */
    public function buscarNotaPedidoRequisicion(): void {
        try {
            $numero = trim((string)($_GET['numero'] ?? ''));
            if ($numero === '') $this->jsonResponse(['encontrada' => false, 'mensaje' => 'Escriba un número de nota.']);
            $nota = $this->notaPedidoModel->buscarNotaPedidoPorNumero($numero);
            if (!$nota) $this->jsonResponse(['encontrada' => false, 'mensaje' => 'La nota no está registrada. Puede continuar sin asociarla.']);
            $this->jsonResponse(['encontrada' => true, 'nota' => $nota]);
        } catch (Throwable $e) {
            $this->logger->inv_error('Error al buscar nota para requisición', $e, 'buscarNotaPedidoRequisicion');
            $this->jsonResponse(['encontrada' => false, 'mensaje' => 'No fue posible consultar la nota. Puede continuar sin asociarla.']);
        }
    }

    /** Buscador remoto y limitado de productos para la grilla de requisición. */
    public function buscarProductosRequisicion(): void {
        try {
            $this->jsonResponse(['productos' => $this->notaPedidoModel->buscarProductos((string)($_GET['q'] ?? ''))]);
        } catch (Throwable $e) {
            $this->logger->inv_error('Error al buscar productos para requisición', $e, 'buscarProductosRequisicion');
            $this->jsonResponse(['productos' => [], 'error' => 'No fue posible buscar productos.'], 500);
        }
    }

    /** Paso 2: listado independiente de órdenes de compra. */
    public function ordenesCompra() {
        $this->registrarAuditoria('ACCESO', 'bod', 'Acceso a Órdenes de Compra');
        $esquemaDisponible = $this->abastecimientoModel->esquemaDisponible();
        $this->render('monitoreo/ordenes_compra', [
            'esquemaDisponible' => $esquemaDisponible,
            'periodoActivo' => $this->periodoModel->obtenerPeriodoActivo(),
        ], 'Órdenes de Compra - Sistema Portuario');
    }

    public function ordenesCompraDataTable(): void {
        try { $this->jsonResponse($this->abastecimientoModel->ordenesDataTable($_GET)); }
        catch (Throwable $e) { $this->logger->inv_error('Error al listar órdenes de compra', $e, 'ordenesCompraDataTable'); $this->jsonResponse(['error'=>'No fue posible cargar las órdenes de compra.'],500); }
    }

    /** Página completa para crear una orden de compra. */
    public function nuevaOrdenCompra(): void {
        $this->registrarAuditoria('ACCESO', 'bod', 'Acceso a Nueva Orden de Compra');
        $this->render('monitoreo/orden_compra_formulario', $this->datosFormularioOrden(), 'Nueva orden de compra - Sistema Portuario');
    }

    /** Página completa para editar una orden pendiente. */
    public function editarOrdenCompraForm(): void {
        $id = (int)($_GET['id'] ?? 0);
        $orden = null;
        foreach ($this->abastecimientoModel->listarOrdenes() as $candidata) {
            if ((int)$candidata['id_orden'] === $id) { $orden = $candidata; break; }
        }
        if (!$orden) $this->redirect('ordenes_compra', 'La orden solicitada no existe.', 'error');
        if ($orden['estado'] !== 'PENDIENTE') $this->redirect('ordenes_compra', 'Solo las órdenes pendientes pueden editarse.', 'error');
        $this->registrarAuditoria('ACCESO', 'bod', 'Edición de Orden de Compra ' . $orden['secuencial']);
        $this->render('monitoreo/orden_compra_formulario', $this->datosFormularioOrden($orden), 'Editar ' . $orden['secuencial'] . ' - Sistema Portuario');
    }

    private function datosFormularioOrden(?array $orden = null): array {
        $db = Database::getInstance()->getConnection();
        $requisiciones = [];
        foreach ($this->notaPedidoModel->obtenerTodos() as $requisicion) {
            if (in_array($requisicion['estado'], ['CANCELADA', 'CERRADA'], true)) continue;
            $detalle = $this->notaPedidoModel->buscarPorId((int)$requisicion['id_nota']);
            if ($detalle) $requisiciones[] = $detalle;
        }
        $periodo = $this->periodoModel->obtenerPeriodoActivo();
        $tiposIva = $db->query('SELECT id, nombre, tasa_iva FROM inv_tipos_iva ORDER BY tasa_iva DESC, nombre')->fetchAll();
        $ivaPredeterminado = ($periodo && $periodo['tasa_iva'] !== null)
            ? (float)$periodo['tasa_iva']
            : (!empty($tiposIva) ? (float)$tiposIva[0]['tasa_iva'] : 0.0);
        return [
            'orden' => $orden,
            'esquemaDisponible' => $this->abastecimientoModel->esquemaDisponible(),
            'periodoActivo' => $periodo,
            'ivaPredeterminado' => $ivaPredeterminado,
            'proveedores' => $db->query("SELECT * FROM inv_proveedores ORDER BY CASE WHEN nombre = '' THEN 1 ELSE 0 END, nombre, codigo")->fetchAll(),
            'requisicionesCompra' => $requisiciones,
            'csrfToken' => csrf_token(),
        ];
    }

    /** Paso 4: despacho de requisiciones y salida real de existencias. */
    public function egresos() {
        $this->registrarAuditoria('ACCESO', 'bod', 'Acceso a Egresos de Bodega');
        $this->render('monitoreo/egresos_bodega', [
            'personal' => $this->talentoModel->obtenerPersonal(),
            'ingresosDisponibles' => $this->abastecimientoModel->esquemaDisponible() ? $this->abastecimientoModel->listarIngresos() : [],
            'periodoActivo' => $this->periodoModel->obtenerPeriodoActivo(),
        ], 'Egresos de Bodega - Sistema Portuario');
    }

    public function egresosDataTable(): void {
        try { $this->jsonResponse($this->egresoModel->egresosDataTable($_GET)); }
        catch (Throwable $e) { $this->logger->inv_error('Error al listar egresos', $e, 'egresosDataTable'); $this->jsonResponse(['error'=>'No fue posible cargar los egresos.'],500); }
    }

    public function guardarOrdenCompra() {
        $this->exigirPostAbastecimiento();
        if (!verify_csrf_token($_POST['csrf_token'] ?? '')) $this->redirectAbastecimiento('ordenes', 'La solicitud no es válida o venció.', 'error');
        try {
            $periodoActivo = $this->exigirPeriodoActivoOrden();
            $this->abastecimientoModel->crearOrden([
                'fecha' => $_POST['fecha'] ?? date('Y-m-d'),
                'nota_pedido_id' => (int)($_POST['nota_pedido_id'] ?? 0),
                'requisicion_id' => (int)($_POST['requisicion_id'] ?? 0),
                'proveedor_id' => (int)($_POST['proveedor_id'] ?? 0),
                'observaciones' => trim($_POST['observaciones'] ?? ''),
                'creado_por' => $this->usuarioActual(),
                'periodo_id' => (int)$periodoActivo['id'],
                'direccion_requirente' => trim($_POST['direccion_requirente'] ?? ''),
                'memorando_solicitud' => trim($_POST['memorando_solicitud'] ?? ''),
                'autorizado_por' => trim($_POST['autorizado_por'] ?? ''),
                'acta_seleccion' => trim($_POST['acta_seleccion'] ?? ''),
                'certificacion_presupuestaria' => trim($_POST['certificacion_presupuestaria'] ?? ''),
                'plazo_entrega' => trim($_POST['plazo_entrega'] ?? ''),
                'forma_pago' => trim($_POST['forma_pago'] ?? ''),
                'condiciones_pago' => trim($_POST['condiciones_pago'] ?? ''),
            ], is_array($_POST['items'] ?? null) ? $_POST['items'] : []);
            $this->redirectAbastecimiento('ordenes', 'Orden creada y pendiente de aprobación.', 'success');
        } catch (Throwable $e) {
            $this->logger->inv_error('Error al crear orden de compra', $e, 'guardarOrdenCompra');
            $this->redirectAbastecimiento('ordenes', $e->getMessage(), 'error');
        }
    }

    public function editarOrdenCompra() {
        $this->exigirPostAbastecimiento();
        if (!verify_csrf_token($_POST['csrf_token'] ?? '')) $this->redirectAbastecimiento('ordenes', 'La solicitud no es válida o venció.', 'error');
        try {
            $periodoActivo = $this->exigirPeriodoActivoOrden();
            $this->abastecimientoModel->actualizarOrden(
                (int)($_POST['orden_id'] ?? 0),
                [
                    'fecha' => $_POST['fecha'] ?? date('Y-m-d'),
                    'proveedor_id' => (int)($_POST['proveedor_id'] ?? 0),
                    'observaciones' => trim($_POST['observaciones'] ?? ''),
                    'periodo_id' => (int)$periodoActivo['id'],
                    'requisicion_id' => (int)($_POST['requisicion_id'] ?? 0),
                    'direccion_requirente' => trim($_POST['direccion_requirente'] ?? ''),
                    'memorando_solicitud' => trim($_POST['memorando_solicitud'] ?? ''),
                    'autorizado_por' => trim($_POST['autorizado_por'] ?? ''),
                    'acta_seleccion' => trim($_POST['acta_seleccion'] ?? ''),
                    'certificacion_presupuestaria' => trim($_POST['certificacion_presupuestaria'] ?? ''),
                    'plazo_entrega' => trim($_POST['plazo_entrega'] ?? ''),
                    'forma_pago' => trim($_POST['forma_pago'] ?? ''),
                    'condiciones_pago' => trim($_POST['condiciones_pago'] ?? ''),
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
        if (!verify_csrf_token($_POST['csrf_token'] ?? '')) $this->redirectAbastecimiento('ordenes', 'La solicitud no es válida o venció.', 'error');
        try {
            $this->exigirPeriodoActivoOrden();
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

    /** Las órdenes solo pueden crearse o aprobarse dentro de un período abierto. */
    private function exigirPeriodoActivoOrden(): array {
        $periodo = $this->periodoModel->obtenerPeriodoActivo();
        if (!$periodo) {
            throw new RuntimeException('No se puede procesar la orden de compra porque no existe un período activo. Abra un período antes de continuar.');
        }
        return $periodo;
    }

    /** Ninguna vía de despacho o registro de egreso puede operar con el período cerrado. */
    private function exigirPeriodoActivoEgreso(): array {
        $periodo = $this->periodoModel->obtenerPeriodoActivo();
        if (!$periodo) {
            throw new RuntimeException('No existe un período activo. Los egresos están disponibles únicamente para consulta.');
        }
        return $periodo;
    }

    /** Alta rápida usada desde órdenes y facturas sin perder el documento en edición. */
    public function crearProveedorRapido(): void {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || !verify_csrf_token($_POST['csrf_token'] ?? '')) {
            $this->jsonResponse(['error' => 'La solicitud no es válida o venció.'], 419);
        }
        try {
            $nombre = trim((string)($_POST['nombre'] ?? ''));
            $ruc = preg_replace('/\D+/', '', (string)($_POST['ruc'] ?? ''));
            if ($nombre === '') throw new InvalidArgumentException('Indique la razón social del proveedor.');
            if ($ruc !== '' && strlen($ruc) !== 13) throw new InvalidArgumentException('El RUC debe contener 13 dígitos.');
            $modelo = new EstacionModel();
            $proveedor = $modelo->crear('proveedores', [
                'nombre' => $nombre,
                'ruc' => $ruc,
                'codigo' => '',
                'representante' => trim((string)($_POST['representante'] ?? '')),
                'direccion' => trim((string)($_POST['direccion'] ?? '')),
                'ciudad' => trim((string)($_POST['ciudad'] ?? '')),
                'email' => trim((string)($_POST['email'] ?? '')),
                'telefono1' => trim((string)($_POST['telefono1'] ?? '')),
                'telefono2' => '', 'fax' => '', 'referencia' => '',
                'extra' => '',
            ]);
            $this->registrarAuditoria('CREAR', 'bod', 'Proveedor creado desde el flujo de compras: ' . $nombre);
            $this->jsonResponse(['proveedor' => $proveedor], 201);
        } catch (Throwable $e) {
            $this->logger->inv_error('Error al crear proveedor desde compras', $e, 'crearProveedorRapido');
            $mensaje = $e instanceof InvalidArgumentException ? $e->getMessage() : 'No fue posible crear el proveedor. Verifique que el RUC no esté registrado.';
            $this->jsonResponse(['error' => $mensaje], 422);
        }
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
        if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
            $this->redirect('requisiciones', 'La solicitud no es válida o venció.', 'error');
        }
        $detalles = [];
        foreach (($_POST['items'] ?? []) as $item) {
            if (!empty($item['item_id']) && (int)($item['cantidad'] ?? 0) > 0) {
                $referencias = array_filter([
                    trim((string)($item['pedido_numero'] ?? '')) !== '' ? 'Pedido: ' . trim((string)$item['pedido_numero']) : '',
                    trim((string)($item['pedido_fecha'] ?? '')) !== '' ? 'Fecha pedido: ' . trim((string)$item['pedido_fecha']) : '',
                    trim((string)($item['referencia'] ?? '')) !== '' ? 'Referencia: ' . trim((string)$item['referencia']) : '',
                    trim((string)($item['otra_referencia'] ?? '')) !== '' ? 'Otra referencia: ' . trim((string)$item['otra_referencia']) : '',
                ]);
                $detalles[] = [
                    'item_id' => (int)$item['item_id'],
                    'cantidad' => (int)$item['cantidad'],
                    'referencia' => implode(' | ', $referencias),
                ];
            }
        }
        try {
            $periodoActivo = $this->periodoModel->obtenerPeriodoActivo();
            if (!$periodoActivo) {
                throw new RuntimeException('No existe un período activo. La requisición solo puede consultarse.');
            }
            $grupoCentroId = (int)($_POST['centro_consumo_grupo_id'] ?? 0);
            $personaCentroId = (int)($_POST['centro_consumo_persona_id'] ?? 0);
            $centroConsumoId = $this->notaPedidoModel->obtenerOCrearCentroParaPersona($personaCentroId, $grupoCentroId);
            $observaciones = trim((string)($_POST['observaciones'] ?? ''));
            $referencia = trim((string)($_POST['documento_referencia'] ?? ''));
            $prioridad = trim((string)($_POST['prioridad'] ?? 'Normal'));
            $contexto = array_filter([
                $referencia !== '' && stripos($observaciones, 'Nota de pedido/referencia: ' . $referencia) === false
                    ? 'Nota de pedido/referencia: ' . $referencia : '',
                $prioridad !== 'Normal' && stripos($observaciones, 'Prioridad: ' . $prioridad) === false
                    ? 'Prioridad: ' . $prioridad : '',
            ]);
            if ($contexto) $observaciones = implode('. ', $contexto) . ($observaciones !== '' ? '. ' . $observaciones : '');
            $notas = $this->notaPedidoModel->crearSolicitud([
                'centro_consumo_id' => $centroConsumoId,
                // Compatibilidad histórica: el campo ya no se solicita en pantalla.
                // Se conserva internamente con el responsable seleccionado del centro.
                'solicitante_id' => $personaCentroId,
                'responsable_id' => $personaCentroId,
                'fecha_solicitud' => !empty($_POST['fecha_solicitud']) ? $_POST['fecha_solicitud'] : date('Y-m-d'),
                'motivo' => trim($_POST['motivo'] ?? ''),
                'observaciones' => $observaciones,
                'creado_por' => $_SESSION['usuario']['nombre'] ?? 'Sistema',
            ], $detalles);
            $cantidad = count($notas);
            $this->logger->info("CREAR_SOLICITUD: {$cantidad} nota(s) generada(s)", 'guardarSolicitud');
            $this->redirect('requisiciones', "Requisición registrada correctamente en {$cantidad} documento(s) interno(s).", 'success');
        } catch (Throwable $e) {
            $this->logger->inv_error('Error al crear solicitud digital', $e, 'guardarSolicitud');
            $this->redirect('requisiciones', $e->getMessage(), 'error');
        }
    }

    /** Anula una requisición, conservándola íntegra en el historial. */
    public function anularRequisicion(): void {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || !verify_csrf_token($_POST['csrf_token'] ?? '')) {
            $this->redirect('requisiciones', 'La solicitud no es válida o venció.', 'error');
        }
        try {
            $this->notaPedidoModel->anular(
                (int)($_POST['nota_id'] ?? 0),
                trim((string)($_POST['motivo_anulacion'] ?? '')),
                $this->usuarioActual()
            );
            $this->logger->info('ANULAR_REQUISICION: requisición anulada y conservada en historial', 'anularRequisicion');
            $this->redirect('requisiciones', 'La requisición fue anulada correctamente.', 'success');
        } catch (Throwable $e) {
            $this->logger->inv_error('Error al anular requisición', $e, 'anularRequisicion');
            $this->redirect('requisiciones', $this->mensajeSeguroFactura($e), 'error');
        }
    }

    /** Genera el egreso confirmado desde los productos disponibles de una nota. */
    public function despacharNota() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('egresos', 'Método no permitido', 'error');
        }
        if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
            $this->redirect('egresos', 'La solicitud no es válida o venció.', 'error');
        }
        try {
            $this->exigirPeriodoActivoEgreso();
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

    /** Registra el egreso capturado desde la cabecera y su ventana de movimiento. */
    public function guardarMovimientoEgreso(): void {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || !verify_csrf_token($_POST['csrf_token'] ?? '')) {
            $this->redirect('egresos', 'La solicitud no es válida o venció.', 'error');
        }
        try {
            $this->exigirPeriodoActivoEgreso();
            $detalles = [];
            foreach (is_array($_POST['items'] ?? null) ? $_POST['items'] : [] as $item) {
                $itemId = (int)($item['item_id'] ?? 0);
                $cantidad = (int)($item['cantidad'] ?? 0);
                if ($itemId > 0 && $cantidad > 0) $detalles[] = ['item_id' => $itemId, 'cantidad' => $cantidad];
            }
            $centroId = $this->notaPedidoModel->obtenerOCrearCentroParaPersona((int)($_POST['centro_consumo_persona_id'] ?? 0));
            $egresoId = $this->egresoModel->crearDesdeMovimiento([
                'area_id' => (int)($_POST['area_id'] ?? 0),
                'centro_consumo_id' => $centroId,
                'responsable_id' => (int)($_POST['receptor_id'] ?? 0),
                'fecha' => $_POST['fecha'] ?? date('Y-m-d'),
                'motivo' => trim((string)($_POST['motivo'] ?? '')),
                'documento_origen' => trim((string)($_POST['documento_origen'] ?? '')),
                'observaciones' => trim((string)($_POST['observaciones'] ?? '')),
                'creado_por' => $this->usuarioActual(),
            ], $detalles);
            $egreso = $this->egresoModel->buscarPorId($egresoId);
            $codigo = $egreso['secuencial'] ?? (string)$egresoId;
            $this->redirect('egresos', "Egreso {$codigo} registrado; existencias y Kardex actualizados.", 'success');
        } catch (Throwable $e) {
            $this->logger->inv_error('Error al registrar el movimiento de egreso', $e, 'guardarMovimientoEgreso');
            $this->redirect('egresos', $e->getMessage(), 'error');
        }
    }

    public function kardexDataTable(): void {
        try {
            $this->jsonResponse($this->egresoModel->kardexDataTable($_GET));
        } catch (Throwable $e) {
            $this->logger->inv_error('Error al consultar Kardex', $e, 'kardexDataTable');
            $this->jsonResponse(['error' => 'No fue posible consultar el Kardex.'], 500);
        }
    }

    public function verNota() {
        $nota = $this->notaPedidoModel->buscarPorId((int)($_GET['id'] ?? 0));
        if (!$nota) {
            $this->jsonResponse(['error' => 'Nota no encontrada'], 404);
        }
        $this->jsonResponse($nota);
    }

    /** Documento limpio e imprimible de una requisición. */
    public function imprimirRequisicion(): void {
        $nota = $this->notaPedidoModel->buscarPorId((int)($_GET['id'] ?? 0));
        if (!$nota) $this->redirect('requisiciones', 'La requisición solicitada no existe.', 'error');
        $this->registrarAuditoria('IMPRIMIR', 'bod', 'Impresión de requisición ' . $nota['secuencial']);
        $this->render('monitoreo/requisicion_imprimir', ['nota' => $nota], 'Requisición ' . $nota['secuencial']);
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
        if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
            $this->redirect('egresos', 'La solicitud no es válida o venció.', 'error');
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
            $this->exigirPeriodoActivoEgreso();
            $egresoId = $this->egresoModel->crear($datosCabecera, $detalles);
            $this->logger->info('CREAR_EGRESO: Egreso de bodega registrado', 'guardar');
            
            $egresoGuardado = $this->egresoModel->buscarPorId($egresoId);
            if ($egresoGuardado) {
                $_notifModel = new NotificacionModel();
                $_notifModel->crear('info', 'egreso', 'Egreso Registrado', "Se ha registrado el egreso Nro. <strong>{$egresoGuardado['secuencial']}</strong> con destino al Centro de Consumo <strong>{$egresoGuardado['area_destino']}</strong>.", $egresoGuardado['secuencial']);
            }

            $this->redirect('egresos', 'Egreso de bodega registrado exitosamente', 'success');
        } catch (Throwable $e) {
            $this->logger->inv_error('Error al procesar egreso de bodega', $e, 'guardar');
            $this->redirect('egresos', $e->getMessage(), 'error');
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
