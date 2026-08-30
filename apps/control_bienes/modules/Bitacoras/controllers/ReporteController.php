<?php
/**
 * Centro integral de reportes operativos.
 * Las consultas y columnas se definen una sola vez para pantalla, CSV e impresión.
 */
require_once ROOT_PATH . 'modules/Bitacoras/models/LogModel.php';

class ReporteController extends Controller {
    private $db;
    private $logger;

    public function __construct() {
        parent::__construct();
        $this->logger = new Logger('rep');
        $this->db = Database::getInstance()->getConnection();
    }

    public function index() {
        $this->registrarAuditoria('ACCESO', 'rep', 'Acceso al Centro Integral de Reportes');
        $catalogo = $this->catalogoReportes();
        $tabActivo = $this->tabValido($_GET['tab'] ?? 'inventario', $catalogo);
        $filtros = $this->leerFiltros();
        $generarReporte = isset($_GET['generar']) && (int)$_GET['generar'] === 1;
        $datosReporte = [];
        $errorReporte = '';

        if ($generarReporte) {
            try {
                $datosReporte = $this->consultar($catalogo[$tabActivo], $filtros);
            } catch (Throwable $e) {
                $errorReporte = 'No fue posible generar el reporte solicitado.';
                $this->logger->inv_error("Error al generar reporte {$tabActivo}", $e, 'index');
            }
        }

        $this->render('bitacoras/reportes_varios', [
            'catalogoReportes' => $catalogo,
            'tabActivo' => $tabActivo,
            'reporteActivo' => $catalogo[$tabActivo],
            'filtros' => $filtros,
            'datosReporte' => $datosReporte,
            'generarReporte' => $generarReporte,
            'errorReporte' => $errorReporte,
            'proveedores' => $this->listaSegura("SELECT nombre FROM inv_proveedores ORDER BY nombre", 'nombre'),
            'categorias' => $this->listaSegura("SELECT id, codigo, nombre FROM inv_categorias ORDER BY codigo, nombre"),
        ], 'Centro de Reportes - Sistema Portuario');
    }

    public function exportar() {
        $catalogo = $this->catalogoReportes();
        $tabActivo = $this->tabValido($_GET['tab'] ?? 'inventario', $catalogo);
        $filtros = $this->leerFiltros();
        $datos = $this->consultar($catalogo[$tabActivo], $filtros);
        $this->registrarAuditoria('EXPORTAR', 'rep', 'Reporte CSV exportado: ' . $tabActivo);

        $nombre = 'reporte_' . $tabActivo . '_' . date('Y-m-d_His') . '.csv';
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $nombre . '"');
        echo "\xEF\xBB\xBF";
        $salida = fopen('php://output', 'w');
        fputcsv($salida, array_map(function ($columna) { return $columna['label']; }, $catalogo[$tabActivo]['columns']), ';');
        foreach ($datos as $fila) {
            $valores = [];
            foreach ($catalogo[$tabActivo]['columns'] as $clave => $columna) {
                $valores[] = $fila[$clave] ?? '';
            }
            fputcsv($salida, $valores, ';');
        }
        fclose($salida);
        exit;
    }

    public function imprimir() {
        $catalogo = $this->catalogoReportes();
        $tabActivo = $this->tabValido($_GET['tab'] ?? 'inventario', $catalogo);
        $filtros = $this->leerFiltros();
        $datos = [];
        $errorReporte = '';
        try {
            $datos = $this->consultar($catalogo[$tabActivo], $filtros);
        } catch (Throwable $e) {
            $errorReporte = 'No fue posible preparar la versión imprimible.';
            $this->logger->inv_error("Error al imprimir reporte {$tabActivo}", $e, 'imprimir');
        }
        $this->registrarAuditoria('IMPRIMIR', 'rep', 'Reporte preparado para impresión: ' . $tabActivo);
        $this->renderActa('bitacoras/inv_imprimir_reporte', [
            'tabActivo' => $tabActivo,
            'reporteActivo' => $catalogo[$tabActivo],
            'filtros' => $filtros,
            'datosReporte' => $datos,
            'errorReporte' => $errorReporte,
        ]);
    }

    private function catalogoReportes(): array {
        $id = ['label' => 'ID', 'type' => 'id'];
        $fecha = ['label' => 'Fecha', 'type' => 'date'];
        $estado = ['label' => 'Estado', 'type' => 'status'];
        $dinero = function ($label) { return ['label' => $label, 'type' => 'money']; };
        $precio = function ($label) { return ['label' => $label, 'type' => 'price']; };
        $numero = function ($label) { return ['label' => $label, 'type' => 'number']; };

        return [
            'inventario' => [
                'label' => 'Inventario', 'icon' => 'fa-boxes-stacked', 'description' => 'Existencias, valoración, responsables y estado de cada bien.',
                'sql' => "SELECT i.id, COALESCE(NULLIF(p.codigo,''),i.codigo_clasificacion,i.secuencial) codigo, i.nombre, i.marca, cat.nombre categoria, COALESCE(i.tipo_bien,p.tipo_bien,'CC') tipo_bien, i.cantidad existencia, COALESCE(u.extra,u.nombre,'u.') unidad, i.valor costo_unitario, (i.cantidad*i.valor) valor_total, est.descripcion estado, pers.nombre responsable, i.fecha_registro fecha FROM vw_inv_items_clasificados i JOIN inv_categorias cat ON cat.id=i.categoria_id JOIN inv_estados est ON est.idestado=i.estado_id LEFT JOIN inv_productos p ON p.id=i.producto_id LEFT JOIN inv_unidades u ON u.id=p.unidad_id LEFT JOIN vw_inv_talento_personal pers ON pers.id=i.responsable_id WHERE i.activo=1",
                'suffix' => ' ORDER BY i.fecha_registro DESC, i.id DESC', 'id_expr' => 'i.id', 'date_expr' => 'i.fecha_registro', 'status_expr' => 'est.descripcion', 'category_expr' => 'i.categoria_id',
                'search' => ['i.id','i.secuencial','i.codigo_clasificacion','p.codigo','i.nombre','i.marca','cat.nombre','est.descripcion','pers.nombre'],
                'statuses' => [], 'stock_filter' => true, 'summary_money' => 'valor_total',
                'columns' => ['id'=>$id,'codigo'=>['label'=>'Código','type'=>'code'],'nombre'=>['label'=>'Descripción'],'marca'=>['label'=>'Marca'],'categoria'=>['label'=>'Categoría'],'tipo_bien'=>['label'=>'Tipo'],'existencia'=>$numero('Existencia'),'unidad'=>['label'=>'Unidad'],'costo_unitario'=>$precio('Costo unitario'),'valor_total'=>$dinero('Valor total'),'estado'=>$estado,'responsable'=>['label'=>'Responsable'],'fecha'=>$fecha],
            ],
            'requisiciones' => [
                'label' => 'Requisiciones', 'icon' => 'fa-clipboard-list', 'description' => 'Solicitudes internas, cantidades pedidas, entregadas y pendientes.',
                'sql' => "SELECT n.id_nota id, n.secuencial, n.fecha_solicitud fecha, gcc.nombre centro_consumo, sol.nombre solicitante, COALESCE(rec.nombre,cc.funcionario) receptor, n.tipo_bien, n.estado, COUNT(d.id_detalle) lineas, SUM(d.cantidad_solicitada) solicitado, SUM(d.cantidad_entregada) entregado, SUM(d.cantidad_solicitada-d.cantidad_entregada) pendiente, n.motivo FROM inv_notas_pedido n JOIN inv_centros_consumo cc ON cc.id=n.centro_consumo_id JOIN inv_grupo_centros_consumo gcc ON gcc.id=cc.grupo_id LEFT JOIN vw_inv_talento_personal sol ON sol.id=n.solicitante_id LEFT JOIN vw_inv_talento_personal rec ON rec.id=n.receptor_id JOIN inv_notas_pedido_detalles d ON d.nota_id=n.id_nota WHERE 1=1",
                'suffix' => ' GROUP BY n.id_nota,n.secuencial,n.fecha_solicitud,gcc.nombre,sol.nombre,rec.nombre,cc.funcionario,n.tipo_bien,n.estado,n.motivo ORDER BY n.fecha_solicitud DESC,n.id_nota DESC', 'id_expr'=>'n.id_nota','date_expr'=>'n.fecha_solicitud','status_expr'=>'n.estado','category_condition'=>'EXISTS (SELECT 1 FROM inv_notas_pedido_detalles fx JOIN inv_inventario fi ON fi.id=fx.item_id WHERE fx.nota_id=n.id_nota AND fi.categoria_id=:categoria)',
                'search'=>['n.id_nota','n.secuencial','gcc.nombre','sol.nombre','rec.nombre','cc.funcionario','n.estado','n.motivo'], 'statuses'=>['BORRADOR','ENVIADA','EN_REVISION','DISPONIBLE','PARCIAL','SIN_EXISTENCIAS','ATENDIDA','ANULADA'],
                'columns'=>['id'=>$id,'secuencial'=>['label'=>'Requisición','type'=>'code'],'fecha'=>$fecha,'centro_consumo'=>['label'=>'Centro de consumo'],'solicitante'=>['label'=>'Solicitante'],'receptor'=>['label'=>'Receptor'],'tipo_bien'=>['label'=>'Tipo'],'estado'=>$estado,'lineas'=>$numero('Líneas'),'solicitado'=>$numero('Solicitado'),'entregado'=>$numero('Entregado'),'pendiente'=>$numero('Pendiente'),'motivo'=>['label'=>'Motivo']],
            ],
            'ordenes' => [
                'label'=>'Órdenes de compra','icon'=>'fa-cart-shopping','description'=>'Órdenes, proveedor, aprobación y valor estimado.',
                'sql'=>"SELECT o.id_orden id,o.secuencial,o.fecha,p.nombre proveedor,o.origen,o.estado,n.secuencial nota_pedido,(SELECT COUNT(*) FROM inv_ordenes_compra_detalles d WHERE d.orden_id=o.id_orden) lineas,(SELECT COALESCE(SUM(d.cantidad),0) FROM inv_ordenes_compra_detalles d WHERE d.orden_id=o.id_orden) unidades,(SELECT COALESCE(SUM(d.cantidad*d.precio_unitario_estimado),0) FROM inv_ordenes_compra_detalles d WHERE d.orden_id=o.id_orden) total_estimado,o.aprobado_por,o.creado_por FROM inv_ordenes_compra o JOIN inv_proveedores p ON p.id=o.proveedor_id LEFT JOIN inv_abast_notas_pedido n ON n.id_nota=o.nota_pedido_id WHERE 1=1",
                'suffix'=>' ORDER BY o.fecha DESC,o.id_orden DESC','id_expr'=>'o.id_orden','date_expr'=>'o.fecha','status_expr'=>'o.estado','provider_expr'=>'p.nombre','category_condition'=>'EXISTS (SELECT 1 FROM inv_ordenes_compra_detalles fx JOIN inv_inventario fi ON fi.id=fx.item_id WHERE fx.orden_id=o.id_orden AND fi.categoria_id=:categoria)','search'=>['o.id_orden','o.secuencial','p.nombre','p.ruc','o.origen','o.estado','n.secuencial','o.aprobado_por','o.creado_por'],'statuses'=>['PENDIENTE','APROBADA','CERRADA','CANCELADA'],
                'summary_money'=>'total_estimado','columns'=>['id'=>$id,'secuencial'=>['label'=>'Orden','type'=>'code'],'fecha'=>$fecha,'proveedor'=>['label'=>'Proveedor'],'origen'=>['label'=>'Origen'],'estado'=>$estado,'nota_pedido'=>['label'=>'Nota de pedido'],'lineas'=>$numero('Líneas'),'unidades'=>$numero('Unidades'),'total_estimado'=>$dinero('Total estimado'),'aprobado_por'=>['label'=>'Aprobado por'],'creado_por'=>['label'=>'Creado por']],
            ],
            'facturas' => [
                'label'=>'Facturas','icon'=>'fa-file-invoice-dollar','description'=>'Facturas registradas, impuestos, totales y trazabilidad.',
                'sql'=>"SELECT f.id_factura id,f.numero_factura,f.fecha_factura fecha,p.nombre proveedor,o.secuencial orden_compra,f.estado,f.base_cero,f.subtotal_gravado,f.valor_iva,f.total,(SELECT COUNT(*) FROM inv_facturas_detalles d WHERE d.factura_id=f.id_factura) lineas,f.creado_por FROM inv_facturas f JOIN inv_proveedores p ON p.id=f.proveedor_id JOIN inv_ordenes_compra o ON o.id_orden=f.orden_compra_id WHERE 1=1",
                'suffix'=>' ORDER BY f.fecha_factura DESC,f.id_factura DESC','id_expr'=>'f.id_factura','date_expr'=>'f.fecha_factura','status_expr'=>'f.estado','provider_expr'=>'p.nombre','category_condition'=>'EXISTS (SELECT 1 FROM inv_facturas_detalles fx JOIN inv_inventario fi ON fi.id=fx.item_id WHERE fx.factura_id=f.id_factura AND fi.categoria_id=:categoria)','search'=>['f.id_factura','f.numero_factura','p.nombre','p.ruc','o.secuencial','f.estado','f.descripcion','f.creado_por'],'statuses'=>['REGISTRADA','INGRESADA','ANULADA'],
                'summary_money'=>'total','columns'=>['id'=>$id,'numero_factura'=>['label'=>'Factura','type'=>'code'],'fecha'=>$fecha,'proveedor'=>['label'=>'Proveedor'],'orden_compra'=>['label'=>'Orden'],'estado'=>$estado,'base_cero'=>$dinero('Base 0%'),'subtotal_gravado'=>$dinero('Base gravada'),'valor_iva'=>$dinero('IVA'),'total'=>$dinero('Total'),'lineas'=>$numero('Líneas'),'creado_por'=>['label'=>'Creado por']],
            ],
            'ingresos' => [
                'label'=>'Ingresos','icon'=>'fa-arrow-down','description'=>'Entradas de bodega, proveedor, cantidades y valores recibidos.',
                'sql'=>"SELECT ing.id,ing.secuencial,ing.fecha,ing.proveedor,pers.nombre responsable,COALESCE(f.numero_factura,'') factura,COALESCE(o.secuencial,'') orden_compra,(SELECT COALESCE(SUM(d.cantidad),0) FROM inv_bod_ingresos_detalles d WHERE d.ingreso_id=ing.id) unidades,(SELECT COALESCE(SUM(d.cantidad*d.valor_unitario),0) FROM inv_bod_ingresos_detalles d WHERE d.ingreso_id=ing.id) total,ing.creado_por,ing.observaciones FROM inv_bod_ingresos ing JOIN vw_inv_talento_personal pers ON pers.id=ing.responsable_id LEFT JOIN inv_facturas f ON f.id_factura=ing.factura_id LEFT JOIN inv_ordenes_compra o ON o.id_orden=ing.orden_compra_id WHERE 1=1",
                'suffix'=>' ORDER BY ing.fecha DESC,ing.id DESC','id_expr'=>'ing.id','date_expr'=>'ing.fecha','provider_expr'=>'ing.proveedor','category_condition'=>'EXISTS (SELECT 1 FROM inv_bod_ingresos_detalles fx JOIN inv_inventario fi ON fi.id=fx.item_id WHERE fx.ingreso_id=ing.id AND fi.categoria_id=:categoria)','search'=>['ing.id','ing.secuencial','ing.proveedor','pers.nombre','f.numero_factura','o.secuencial','ing.creado_por','ing.observaciones'],'statuses'=>[],
                'summary_money'=>'total','columns'=>['id'=>$id,'secuencial'=>['label'=>'Ingreso','type'=>'code'],'fecha'=>$fecha,'proveedor'=>['label'=>'Proveedor'],'responsable'=>['label'=>'Responsable'],'factura'=>['label'=>'Factura'],'orden_compra'=>['label'=>'Orden'],'unidades'=>$numero('Unidades'),'total'=>$dinero('Valor recibido'),'creado_por'=>['label'=>'Creado por'],'observaciones'=>['label'=>'Observaciones']],
            ],
            'egresos' => [
                'label'=>'Egresos','icon'=>'fa-dolly','description'=>'Salidas de bodega, destino, responsable y cantidades entregadas.',
                'sql'=>"SELECT e.id,e.secuencial,e.fecha,COALESCE(cc.nombre,a.nombre) destino,pers.nombre responsable,n.secuencial requisicion,e.estado,(SELECT COALESCE(SUM(d.cantidad),0) FROM inv_bod_egresos_detalles d WHERE d.egreso_id=e.id) unidades,e.motivo,e.creado_por FROM inv_bod_egresos e JOIN vw_inv_talento_personal pers ON pers.id=e.responsable_id LEFT JOIN inv_centros_consumo cc ON cc.id=e.centro_consumo_id LEFT JOIN inv_talento_areas a ON a.id=e.area_id LEFT JOIN inv_notas_pedido n ON n.id_nota=e.nota_pedido_id WHERE 1=1",
                'suffix'=>' ORDER BY e.fecha DESC,e.id DESC','id_expr'=>'e.id','date_expr'=>'e.fecha','status_expr'=>'e.estado','category_condition'=>'EXISTS (SELECT 1 FROM inv_bod_egresos_detalles fx JOIN inv_inventario fi ON fi.id=fx.item_id WHERE fx.egreso_id=e.id AND fi.categoria_id=:categoria)','search'=>['e.id','e.secuencial','cc.nombre','a.nombre','pers.nombre','n.secuencial','e.estado','e.motivo','e.creado_por'],'statuses'=>['CONFIRMADO','ANULADO'],
                'columns'=>['id'=>$id,'secuencial'=>['label'=>'Egreso','type'=>'code'],'fecha'=>$fecha,'destino'=>['label'=>'Destino'],'responsable'=>['label'=>'Responsable'],'requisicion'=>['label'=>'Requisición'],'estado'=>$estado,'unidades'=>$numero('Unidades'),'motivo'=>['label'=>'Motivo'],'creado_por'=>['label'=>'Creado por']],
            ],
            'kardex' => [
                'label'=>'Kardex','icon'=>'fa-right-left','description'=>'Historial completo de entradas, salidas y saldos por ítem.',
                'sql'=>"SELECT k.id_movimiento id,k.fecha_movimiento fecha,k.tipo_movimiento,k.documento_tipo,k.documento_secuencial,i.id item_id,COALESCE(NULLIF(p.codigo,''),i.secuencial) item_codigo,i.nombre item,k.entrada,k.salida,k.saldo_anterior,k.saldo_resultante,cc.nombre centro_consumo,k.usuario_registro FROM inv_kardex k JOIN inv_inventario i ON i.id=k.item_id LEFT JOIN inv_productos p ON p.id=i.producto_id LEFT JOIN inv_centros_consumo cc ON cc.id=k.centro_consumo_id WHERE 1=1",
                'suffix'=>' ORDER BY k.fecha_movimiento DESC,k.id_movimiento DESC','id_expr'=>'k.id_movimiento','date_expr'=>'k.fecha_movimiento','status_expr'=>'k.tipo_movimiento','category_expr'=>'i.categoria_id','search'=>['k.id_movimiento','k.documento_secuencial','k.documento_tipo','i.id','i.secuencial','p.codigo','i.nombre','cc.nombre','k.usuario_registro'],'statuses'=>['INGRESO','EGRESO','AJUSTE'],
                'columns'=>['id'=>$id,'fecha'=>$fecha,'tipo_movimiento'=>['label'=>'Movimiento','type'=>'status'],'documento_tipo'=>['label'=>'Documento'],'documento_secuencial'=>['label'=>'N.º documento','type'=>'code'],'item_id'=>['label'=>'ID ítem','type'=>'id'],'item_codigo'=>['label'=>'Código ítem','type'=>'code'],'item'=>['label'=>'Ítem'],'entrada'=>$numero('Entrada'),'salida'=>$numero('Salida'),'saldo_anterior'=>$numero('Saldo anterior'),'saldo_resultante'=>$numero('Saldo resultante'),'centro_consumo'=>['label'=>'Centro de consumo'],'usuario_registro'=>['label'=>'Registrado por']],
            ],
            'proveedores' => [
                'label'=>'Proveedores','icon'=>'fa-truck-field','description'=>'Directorio de proveedores y datos de identificación.',
                'sql'=>"SELECT p.id,p.codigo,p.nombre,p.ruc,p.extra FROM inv_proveedores p WHERE 1=1",'suffix'=>' ORDER BY p.nombre','id_expr'=>'p.id','provider_expr'=>'p.nombre','search'=>['p.id','p.codigo','p.nombre','p.ruc','p.extra'],'statuses'=>[],
                'columns'=>['id'=>$id,'codigo'=>['label'=>'Código','type'=>'code'],'nombre'=>['label'=>'Proveedor'],'ruc'=>['label'=>'RUC / identificación'],'extra'=>['label'=>'Contacto e información adicional']],
            ],
            'centros' => [
                'label'=>'Centros de consumo','icon'=>'fa-building','description'=>'Áreas, responsables y volumen de despachos.',
                'sql'=>"SELECT cc.id,cc.codigo,cc.nombre,g.nombre grupo,COALESCE(p.nombre,cc.funcionario) responsable,(SELECT COUNT(*) FROM inv_bod_egresos e WHERE e.centro_consumo_id=cc.id OR e.area_id=cc.id) despachos,(SELECT COALESCE(SUM(d.cantidad),0) FROM inv_bod_egresos_detalles d JOIN inv_bod_egresos e ON e.id=d.egreso_id WHERE e.centro_consumo_id=cc.id OR e.area_id=cc.id) unidades FROM inv_centros_consumo cc JOIN inv_grupo_centros_consumo g ON g.id=cc.grupo_id LEFT JOIN vw_inv_talento_personal p ON p.id=cc.funcionario_id WHERE 1=1",'suffix'=>' ORDER BY cc.codigo','id_expr'=>'cc.id','search'=>['cc.id','cc.codigo','cc.nombre','g.nombre','p.nombre','cc.funcionario'],'statuses'=>[],
                'columns'=>['id'=>$id,'codigo'=>['label'=>'Código','type'=>'code'],'nombre'=>['label'=>'Centro de consumo'],'grupo'=>['label'=>'Grupo / área'],'responsable'=>['label'=>'Responsable'],'despachos'=>$numero('Despachos'),'unidades'=>$numero('Unidades entregadas')],
            ],
            'auditoria' => [
                'label'=>'Auditoría','icon'=>'fa-shield-halved','description'=>'Actividad de usuarios en todos los módulos y funciones.',
                'sql'=>"SELECT l.id_evento id,l.fecha_registro fecha,l.id_usuario,l.modulo,l.accion,l.resultado,l.descripcion,l.ip_cliente FROM inv_log_eventos l WHERE 1=1",'suffix'=>' ORDER BY l.fecha_registro DESC,l.id_evento DESC','id_expr'=>'l.id_evento','date_expr'=>'l.fecha_registro','status_expr'=>'l.resultado','search'=>['l.id_evento','l.id_usuario','l.modulo','l.accion','l.resultado','l.descripcion','l.ip_cliente'],'statuses'=>['EXITOSO','ERROR','DENEGADO'],
                'columns'=>['id'=>$id,'fecha'=>$fecha,'id_usuario'=>['label'=>'ID usuario','type'=>'id'],'modulo'=>['label'=>'Módulo'],'accion'=>['label'=>'Acción'],'resultado'=>$estado,'descripcion'=>['label'=>'Descripción'],'ip_cliente'=>['label'=>'IP']],
            ],
        ];
    }

    private function consultar(array $reporte, array $filtros): array {
        $sql = $reporte['sql'];
        $params = [];
        $this->agregarFiltroExacto($sql, $params, $reporte['id_expr'] ?? '', 'id_exacto', $filtros['id_exacto']);
        if ($filtros['id_exacto'] === '') {
            $this->agregarFiltroRango($sql, $params, $reporte['id_expr'] ?? '', 'id_inicio', $filtros['id_inicio'], '>=');
            $this->agregarFiltroRango($sql, $params, $reporte['id_expr'] ?? '', 'id_fin', $filtros['id_fin'], '<=');
        }
        $this->agregarFiltroRango($sql, $params, $reporte['date_expr'] ?? '', 'fecha_inicio', $filtros['fecha_inicio'], '>=', true);
        $this->agregarFiltroRango($sql, $params, $reporte['date_expr'] ?? '', 'fecha_fin', $filtros['fecha_fin'], '<=', true);
        $this->agregarFiltroExacto($sql, $params, $reporte['status_expr'] ?? '', 'estado', $filtros['estado']);
        $this->agregarFiltroExacto($sql, $params, $reporte['provider_expr'] ?? '', 'proveedor', $filtros['proveedor']);
        if ($filtros['categoria'] !== '') {
            if (!empty($reporte['category_expr'])) {
                $sql .= ' AND ' . $reporte['category_expr'] . ' = :categoria';
                $params[':categoria'] = (int)$filtros['categoria'];
            } elseif (!empty($reporte['category_condition'])) {
                $sql .= ' AND ' . $reporte['category_condition'];
                $params[':categoria'] = (int)$filtros['categoria'];
            }
        }
        if (!empty($reporte['stock_filter']) && $filtros['stock'] !== '') {
            if ($filtros['stock'] === 'sin_stock') $sql .= " AND COALESCE(i.tipo_bien,p.tipo_bien,'CC')<>'AF' AND COALESCE(i.cantidad,0)<=0";
            if ($filtros['stock'] === 'stock_bajo') $sql .= " AND COALESCE(i.tipo_bien,p.tipo_bien,'CC')<>'AF' AND COALESCE(i.cantidad,0)>0 AND COALESCE(p.existencia_min,0)>0 AND i.cantidad<=p.existencia_min";
            if ($filtros['stock'] === 'sin_responsable') $sql .= " AND COALESCE(i.tipo_bien,p.tipo_bien,'CC')='AF' AND i.responsable_id IS NULL";
        }
        if ($filtros['termino'] !== '' && !empty($reporte['search'])) {
            $condiciones = [];
            foreach ($reporte['search'] as $indice => $campo) {
                $parametro = ':termino' . $indice;
                $condiciones[] = 'CAST(' . $campo . ' AS VARCHAR(4000)) LIKE ' . $parametro;
                $params[$parametro] = '%' . $filtros['termino'] . '%';
            }
            $sql .= ' AND (' . implode(' OR ', $condiciones) . ')';
        }
        $sql .= $reporte['suffix'] ?? '';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    private function agregarFiltroExacto(&$sql, array &$params, string $expresion, string $nombre, string $valor): void {
        if ($expresion === '' || $valor === '') return;
        $sql .= ' AND ' . $expresion . ' = :' . $nombre;
        $params[':' . $nombre] = strpos($nombre, 'id_') === 0 ? (int)$valor : $valor;
    }

    private function agregarFiltroRango(&$sql, array &$params, string $expresion, string $nombre, string $valor, string $operador, bool $fecha = false): void {
        if ($expresion === '' || $valor === '') return;
        $comparada = $fecha ? 'CAST(' . $expresion . ' AS DATE)' : $expresion;
        $sql .= ' AND ' . $comparada . ' ' . $operador . ' :' . $nombre;
        $params[':' . $nombre] = $fecha ? $valor : (int)$valor;
    }

    private function leerFiltros(): array {
        $filtros = [
            'fecha_inicio' => $this->normalizarFecha($_GET['fecha_inicio'] ?? ''),
            'fecha_fin' => $this->normalizarFecha($_GET['fecha_fin'] ?? ''),
            'id_exacto' => $this->normalizarId($_GET['id_exacto'] ?? ''),
            'id_inicio' => $this->normalizarId($_GET['id_inicio'] ?? ''),
            'id_fin' => $this->normalizarId($_GET['id_fin'] ?? ''),
            'estado' => trim((string)($_GET['estado'] ?? '')),
            'proveedor' => trim((string)($_GET['proveedor'] ?? '')),
            'categoria' => $this->normalizarId($_GET['categoria'] ?? ''),
            'stock' => in_array($_GET['stock'] ?? '', ['sin_stock','stock_bajo','sin_responsable'], true) ? $_GET['stock'] : '',
            'termino' => mb_substr(trim((string)($_GET['termino'] ?? '')), 0, 150),
        ];
        if ($filtros['id_exacto'] !== '') $filtros['id_inicio'] = $filtros['id_fin'] = '';
        if ($filtros['id_inicio'] !== '' && $filtros['id_fin'] !== '' && (int)$filtros['id_inicio'] > (int)$filtros['id_fin']) {
            $temporal = $filtros['id_inicio']; $filtros['id_inicio'] = $filtros['id_fin']; $filtros['id_fin'] = $temporal;
        }
        if ($filtros['fecha_inicio'] !== '' && $filtros['fecha_fin'] !== '' && $filtros['fecha_inicio'] > $filtros['fecha_fin']) {
            $temporal = $filtros['fecha_inicio']; $filtros['fecha_inicio'] = $filtros['fecha_fin']; $filtros['fecha_fin'] = $temporal;
        }
        return $filtros;
    }

    private function normalizarId($valor): string {
        $valor = trim((string)$valor);
        return ctype_digit($valor) && (int)$valor > 0 ? (string)(int)$valor : '';
    }

    private function normalizarFecha($valor): string {
        $valor = trim((string)$valor);
        if ($valor === '') return '';
        $fecha = DateTime::createFromFormat('!Y-m-d', $valor);
        return $fecha && $fecha->format('Y-m-d') === $valor ? $valor : '';
    }

    private function tabValido($tab, array $catalogo): string {
        $tab = trim((string)$tab);
        return isset($catalogo[$tab]) ? $tab : 'inventario';
    }

    private function listaSegura(string $sql, string $columna = ''): array {
        try {
            $filas = $this->db->query($sql)->fetchAll();
            return $columna === '' ? $filas : array_values(array_filter(array_map(function ($fila) use ($columna) { return $fila[$columna] ?? ''; }, $filas)));
        } catch (Throwable $e) {
            return [];
        }
    }
}

class_alias('ReporteController', 'InvReporteController');
