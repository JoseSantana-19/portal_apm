<?php
require_once ROOT_PATH . 'core/Model.php';
require_once ROOT_PATH . 'modules/Central/models/InvSecuencial.php';
require_once ROOT_PATH . 'modules/Bitacoras/models/BitacoraModel.php';

class InvIngresoFactura extends Model
{
    public function esquemaDisponible(): bool
    {
        try {
            $this->db->query('SELECT descripcion FROM inv_facturas WHERE 1 = 0');
            $this->db->query('SELECT iva_porcentaje, subtotal, valor_iva, total FROM inv_facturas_detalles WHERE 1 = 0');
            return true;
        } catch (Throwable $e) {
            return false;
        }
    }

    public function proveedores(): array
    {
        return $this->db->query("SELECT id, codigo, nombre, ruc FROM inv_proveedores ORDER BY codigo, nombre")->fetchAll();
    }

    public function tiposIva(): array
    {
        return $this->db->query('SELECT id, nombre, tasa_iva FROM inv_tipos_iva ORDER BY tasa_iva, nombre')->fetchAll();
    }

    public function facturasDataTable(array $peticion): array
    {
        $draw = max(0, (int)($peticion['draw'] ?? 0));
        $inicio = max(0, (int)($peticion['start'] ?? 0));
        $largo = min(100, max(10, (int)($peticion['length'] ?? 10)));
        $busqueda = trim((string)($peticion['search']['value'] ?? ''));
        $params = [];
        $where = ['1=1'];

        if ($busqueda !== '') {
            $where[] = '(f.numero_factura LIKE :b1 OR f.descripcion LIKE :b2 OR p.codigo LIKE :b3 OR p.nombre LIKE :b4 OR p.ruc LIKE :b5 OR o.secuencial LIKE :b6)';
            foreach (range(1,6) as $i) $params[':b'.$i] = '%' . $busqueda . '%';
        }
        $desde = trim((string)($peticion['fecha_desde'] ?? ''));
        $hasta = trim((string)($peticion['fecha_hasta'] ?? ''));
        if ($desde !== '') { $where[] = 'f.fecha_factura >= :desde'; $params[':desde'] = $desde; }
        if ($hasta !== '') { $where[] = 'f.fecha_factura <= :hasta'; $params[':hasta'] = $hasta; }
        $estado = strtoupper(trim((string)($peticion['estado'] ?? '')));
        if (in_array($estado, ['REGISTRADA', 'INGRESADA', 'ANULADA'], true)) { $where[] = 'f.estado = :estado'; $params[':estado'] = $estado; }

        $whereSql = implode(' AND ', $where);
        $total = (int)$this->db->query('SELECT COUNT(*) FROM inv_facturas')->fetchColumn();
        $stmtConteo = $this->db->prepare("SELECT COUNT(*) FROM inv_facturas f JOIN inv_proveedores p ON p.id=f.proveedor_id JOIN inv_ordenes_compra o ON o.id_orden=f.orden_compra_id WHERE {$whereSql}");
        $stmtConteo->execute($params);
        $filtrados = (int)$stmtConteo->fetchColumn();

        $columnasOrden = ['f.numero_factura','f.fecha_factura','p.codigo','p.nombre','p.ruc','o.secuencial','f.descripcion','f.id_factura','f.total','f.estado'];
        $ordenIndice = (int)($peticion['order'][0]['column'] ?? 1);
        $orden = $columnasOrden[$ordenIndice] ?? 'f.fecha_factura';
        $direccion = strtolower((string)($peticion['order'][0]['dir'] ?? 'desc')) === 'asc' ? 'ASC' : 'DESC';
        $sql = "SELECT f.id_factura, f.numero_factura, f.fecha_factura, f.descripcion, f.total, f.estado,
                       p.codigo proveedor_codigo, p.nombre proveedor, p.ruc proveedor_ruc, o.secuencial orden_secuencial,
                       (SELECT COUNT(*) FROM inv_facturas_detalles d WHERE d.factura_id=f.id_factura) total_lineas
                FROM inv_facturas f
                JOIN inv_proveedores p ON p.id=f.proveedor_id
                JOIN inv_ordenes_compra o ON o.id_orden=f.orden_compra_id
                WHERE {$whereSql}
                ORDER BY {$orden} {$direccion}, f.id_factura DESC
                OFFSET {$inicio} ROWS FETCH NEXT {$largo} ROWS ONLY";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return ['draw' => $draw, 'recordsTotal' => $total, 'recordsFiltered' => $filtrados, 'data' => $stmt->fetchAll()];
    }

    public function productosDataTable(array $peticion): array
    {
        $draw = max(0, (int)($peticion['draw'] ?? 0));
        $inicio = max(0, (int)($peticion['start'] ?? 0));
        $largo = min(50, max(5, (int)($peticion['length'] ?? 10)));
        $busqueda = trim((string)($peticion['search']['value'] ?? ''));
        $where = 'i.activo=1'; $params = [];
        if ($busqueda !== '') {
            $ignoradas = ['de','del','la','el','los','las','y','para','gl','un','una'];
            $tokens = preg_split('/\s+/u', mb_strtolower($busqueda, 'UTF-8'), -1, PREG_SPLIT_NO_EMPTY) ?: [];
            $tokens = array_values(array_filter($tokens, static function ($token) use ($ignoradas) {
                return mb_strlen($token, 'UTF-8') >= 3 && !in_array($token, $ignoradas, true);
            }));
            if (!$tokens) $tokens = [$busqueda];
            foreach (array_slice($tokens, 0, 6) as $indice => $token) {
                $campos = [];
                foreach (range(1, 5) as $campo) {
                    $parametro = ':b' . $indice . '_' . $campo;
                    $campos[] = [
                        'i.secuencial', 'i.nombre COLLATE Modern_Spanish_CI_AI',
                        'p.codigo', 'p.nombre COLLATE Modern_Spanish_CI_AI',
                        'c.codigo'
                    ][$campo - 1] . ' LIKE ' . $parametro;
                    $params[$parametro] = '%' . $token . '%';
                }
                $where .= ' AND (' . implode(' OR ', $campos) . ')';
            }
        }
        $total = (int)$this->db->query('SELECT COUNT(*) FROM inv_inventario WHERE activo=1')->fetchColumn();
        $count = $this->db->prepare("SELECT COUNT(*) FROM inv_inventario i LEFT JOIN inv_productos p ON p.id=i.producto_id LEFT JOIN inv_categorias c ON c.id=i.categoria_id WHERE {$where}");
        $count->execute($params);
        $filtrados = (int)$count->fetchColumn();
        $sql = "SELECT i.id, i.secuencial codigo, c.codigo codigo_clasificacion, p.codigo codigo_maestro, i.nombre,
                       i.cantidad existencia, i.valor precio_actual, COALESCE(p.aplica_iva,1) aplica_iva,
                       c.codigo codigo_contable, c.nombre cuenta_contable
                FROM inv_inventario i
                LEFT JOIN inv_productos p ON p.id=i.producto_id
                LEFT JOIN inv_categorias c ON c.id=i.categoria_id
                WHERE {$where}
                ORDER BY i.nombre, i.id OFFSET {$inicio} ROWS FETCH NEXT {$largo} ROWS ONLY";
        $stmt = $this->db->prepare($sql); $stmt->execute($params);
        return ['draw'=>$draw, 'recordsTotal'=>$total, 'recordsFiltered'=>$filtrados, 'data'=>$stmt->fetchAll()];
    }

    public function obtenerFactura(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT f.*, p.codigo proveedor_codigo, p.nombre proveedor, p.ruc proveedor_ruc, o.secuencial orden_secuencial
            FROM inv_facturas f JOIN inv_proveedores p ON p.id=f.proveedor_id JOIN inv_ordenes_compra o ON o.id_orden=f.orden_compra_id WHERE f.id_factura=:id");
        $stmt->execute([':id'=>$id]); $factura = $stmt->fetch();
        if (!$factura) return null;
        $det = $this->db->prepare("SELECT d.*, i.nombre item_nombre, i.secuencial item_codigo,
                i.cantidad existencia, c.codigo codigo_contable, c.nombre cuenta_contable
            FROM inv_facturas_detalles d JOIN inv_inventario i ON i.id=d.item_id LEFT JOIN inv_categorias c ON c.id=i.categoria_id
            WHERE d.factura_id=:id ORDER BY d.id_detalle");
        $det->execute([':id'=>$id]); $factura['detalles']=$det->fetchAll();
        return $factura;
    }

    public function guardar(array $datos, array $lineas, string $usuario, int $id=0): int
    {
        $esEdicion = $id > 0;
        $numero = trim((string)($datos['numero_factura'] ?? ''));
        $proveedorId = (int)($datos['proveedor_id'] ?? 0);
        if ($numero === '' || $proveedorId <= 0) throw new InvalidArgumentException('Indique el número de factura y seleccione el proveedor.');
        $fecha = trim((string)($datos['fecha_factura'] ?? ''));
        $fechaValida = DateTime::createFromFormat('Y-m-d', $fecha);
        if (!$fechaValida || $fechaValida->format('Y-m-d') !== $fecha) throw new InvalidArgumentException('Indique una fecha de factura válida.');
        if (!$lineas) throw new InvalidArgumentException('Agregue al menos un producto a la factura.');
        $tasas = [];
        foreach ($this->tiposIva() as $tipo) $tasas[(int)$tipo['id']] = (float)$tipo['tasa_iva'];
        $normalizadas = []; $vistos = []; $baseCero=0.0; $baseGravada=0.0; $ivaTotal=0.0; $tasaMax=0.0;
        foreach ($lineas as $linea) {
            $itemId=(int)($linea['item_id']??0); $cantidad=(int)($linea['cantidad']??0); $precio=round((float)($linea['precio_unitario']??0),4);
            if ($itemId<=0 || $cantidad<=0) continue;
            if (isset($vistos[$itemId])) throw new InvalidArgumentException('No repita un producto dentro de la misma factura.');
            if ($precio<0) throw new InvalidArgumentException('El precio unitario no puede ser negativo.');
            $vistos[$itemId]=true;
            $aplica=!empty($linea['aplica_iva']); $tipoId=$aplica?(int)($linea['iva_tipo_id']??0):0;
            if ($aplica && !isset($tasas[$tipoId])) throw new InvalidArgumentException('Una de las tasas de IVA seleccionadas ya no existe en Maestros.');
            $tasa=$aplica?$tasas[$tipoId]:0.0; $subtotal=round($cantidad*$precio,4); $valorIva=round($subtotal*$tasa/100,2); $total=round($subtotal+$valorIva,4);
            if ($aplica && $tasa>0) $baseGravada += $subtotal; else $baseCero += $subtotal;
            $ivaTotal += $valorIva; $tasaMax=max($tasaMax,$tasa);
            $normalizadas[]=['item_id'=>$itemId,'cantidad'=>$cantidad,'precio'=>$precio,'aplica'=>$aplica,'tipo_id'=>$tipoId?:null,'tasa'=>$tasa,
                'subtotal'=>$subtotal,'iva'=>$valorIva,'total'=>$total,'pedido'=>trim((string)($linea['pedido']??'')),
                'requisicion'=>trim((string)($linea['requisicion']??'')),'referencia'=>trim((string)($linea['referencia']??''))];
        }
        if (!$normalizadas) throw new InvalidArgumentException('Agregue al menos un producto válido.');

        try {
            $this->db->beginTransaction();
            $ordenId=0;
            if ($id>0) {
                $actual=$this->obtenerFactura($id);
                if (!$actual || $actual['estado']!=='REGISTRADA') throw new RuntimeException('Solo se puede editar una factura registrada y aún no ingresada.');
                $ordenId=(int)$actual['orden_compra_id'];
                $this->db->prepare('UPDATE inv_ordenes_compra SET fecha=:fecha, proveedor_id=:proveedor, observaciones=:obs WHERE id_orden=:id')->execute([
                    ':fecha'=>$datos['fecha_factura'],':proveedor'=>$proveedorId,':obs'=>'Orden automática de la factura '.$numero,':id'=>$ordenId]);
                $this->db->prepare('DELETE FROM inv_ordenes_compra_detalles WHERE orden_id=:id')->execute([':id'=>$ordenId]);
            } else {
                $secuencial=(new InvSecuencial())->generarSiguiente('ocp');
                $stmt=$this->db->prepare("INSERT INTO inv_ordenes_compra (secuencial,fecha,nota_pedido_id,proveedor_id,origen,estado,observaciones,creado_por,fecha_aprobacion,aprobado_por)
                    VALUES (:sec,:fecha,NULL,:proveedor,'FACTURA','APROBADA',:obs,:usuario,CURRENT_TIMESTAMP,:usuario)");
                $stmt->execute([':sec'=>$secuencial,':fecha'=>$datos['fecha_factura'],':proveedor'=>$proveedorId,':obs'=>'Orden automática de la factura '.$numero,':usuario'=>$usuario]);
                $ordenId=(int)$this->db->lastInsertId();
            }
            $ordenDet=$this->db->prepare('INSERT INTO inv_ordenes_compra_detalles (orden_id,item_id,cantidad,precio_unitario_estimado) VALUES (:orden,:item,:cantidad,:precio)');
            foreach($normalizadas as $linea) $ordenDet->execute([':orden'=>$ordenId,':item'=>$linea['item_id'],':cantidad'=>$linea['cantidad'],':precio'=>$linea['precio']]);

            $total=round($baseCero+$baseGravada+$ivaTotal,4);
            if ($id>0) {
                $stmt=$this->db->prepare('UPDATE inv_facturas SET numero_factura=:numero,fecha_factura=:fecha,proveedor_id=:proveedor,descripcion=:descripcion,
                    iva_porcentaje=:tasa,base_cero=:base0,subtotal_gravado=:gravado,valor_iva=:iva,total=:total,actualizado_por=:usuario,fecha_actualizacion=CURRENT_TIMESTAMP WHERE id_factura=:id');
                $stmt->execute([':numero'=>$numero,':fecha'=>$datos['fecha_factura'],':proveedor'=>$proveedorId,':descripcion'=>trim((string)($datos['descripcion']??'')),
                    ':tasa'=>$tasaMax,':base0'=>$baseCero,':gravado'=>$baseGravada,':iva'=>$ivaTotal,':total'=>$total,':usuario'=>$usuario,':id'=>$id]);
                $this->db->prepare('DELETE FROM inv_facturas_detalles WHERE factura_id=:id')->execute([':id'=>$id]);
            } else {
                $stmt=$this->db->prepare("INSERT INTO inv_facturas (numero_factura,fecha_factura,proveedor_id,orden_compra_id,descripcion,iva_porcentaje,base_cero,subtotal_gravado,valor_iva,total,estado,creado_por)
                    VALUES (:numero,:fecha,:proveedor,:orden,:descripcion,:tasa,:base0,:gravado,:iva,:total,'REGISTRADA',:usuario)");
                $stmt->execute([':numero'=>$numero,':fecha'=>$datos['fecha_factura'],':proveedor'=>$proveedorId,':orden'=>$ordenId,':descripcion'=>trim((string)($datos['descripcion']??'')),
                    ':tasa'=>$tasaMax,':base0'=>$baseCero,':gravado'=>$baseGravada,':iva'=>$ivaTotal,':total'=>$total,':usuario'=>$usuario]);
                $id=(int)$this->db->lastInsertId();
            }
            $detalle=$this->db->prepare('INSERT INTO inv_facturas_detalles (factura_id,item_id,cantidad,precio_unitario,grava_iva,codigo_presupuestario,pedido,requisicion,referencia,iva_tipo_id,iva_porcentaje,subtotal,valor_iva,total)
                VALUES (:factura,:item,:cantidad,:precio,:aplica,:codigo,:pedido,:requisicion,:referencia,:tipo,:tasa,:subtotal,:iva,:total)');
            foreach($normalizadas as $linea) $detalle->execute([':factura'=>$id,':item'=>$linea['item_id'],':cantidad'=>$linea['cantidad'],':precio'=>$linea['precio'],
                ':aplica'=>$linea['aplica']?1:0,':codigo'=>'',':pedido'=>$linea['pedido'],':requisicion'=>$linea['requisicion'],':referencia'=>$linea['referencia'],
                ':tipo'=>$linea['tipo_id'],':tasa'=>$linea['tasa'],':subtotal'=>$linea['subtotal'],':iva'=>$linea['iva'],':total'=>$linea['total']]);
            (new InvBitacora())->registrar($esEdicion?'ACTUALIZAR':'CREAR','bod',"Factura {$numero} guardada con orden automática y detalle de IVA por producto.");
            $this->db->commit(); return $id;
        } catch(Throwable $e) { if($this->db->inTransaction()) $this->db->rollBack(); throw $e; }
    }

    public function anular(int $id, string $motivo, string $usuario): void
    {
        if (trim($motivo)==='') throw new InvalidArgumentException('Indique el motivo de anulación.');
        $factura=$this->obtenerFactura($id);
        if(!$factura || $factura['estado']!=='REGISTRADA') throw new RuntimeException('Solo se pueden anular facturas registradas que aún no ingresaron a bodega.');
        try { $this->db->beginTransaction();
            $this->db->prepare("UPDATE inv_facturas SET estado='ANULADA',motivo_anulacion=:motivo,anulado_por=:usuario,fecha_anulacion=CURRENT_TIMESTAMP WHERE id_factura=:id")
                ->execute([':motivo'=>trim($motivo),':usuario'=>$usuario,':id'=>$id]);
            $this->db->prepare("UPDATE inv_ordenes_compra SET estado='CANCELADA' WHERE id_orden=:id")->execute([':id'=>$factura['orden_compra_id']]);
            (new InvBitacora())->registrar('ANULAR','bod','Factura '.$factura['numero_factura'].' anulada. Motivo: '.trim($motivo));
            $this->db->commit();
        } catch(Throwable $e){ if($this->db->inTransaction())$this->db->rollBack(); throw $e; }
    }
}
