<?php
require_once ROOT_PATH . 'core/Model.php';
require_once ROOT_PATH . 'modules/Central/models/InvSecuencial.php';
require_once ROOT_PATH . 'modules/Bitacoras/models/BitacoraModel.php';

class InvEgreso extends Model {
    // Conexión PDO heredada en $this->db


    /**
     * Obtiene todos los egresos de bodega
     */
    public function obtenerTodos() {
        $sql = "SELECT egr.*, 
                       pers.nombre as responsable, 
                       COALESCE(cc.nombre, area.nombre) as area_destino,
                       nota.secuencial as nota_pedido_secuencial,
                       (SELECT SUM(det.cantidad) FROM inv_bod_egresos_detalles det WHERE det.egreso_id = egr.id) as total_items
                FROM inv_bod_egresos egr
                JOIN vw_inv_talento_personal pers ON egr.responsable_id = pers.id
                LEFT JOIN inv_centros_consumo cc ON egr.centro_consumo_id = cc.id
                LEFT JOIN inv_talento_areas area ON egr.area_id = area.id
                LEFT JOIN inv_notas_pedido nota ON egr.nota_pedido_id = nota.id_nota
                ORDER BY egr.fecha DESC, egr.id DESC";
        $stmt = $this->db->query($sql);
        $egresos = $stmt->fetchAll();
        foreach ($egresos as &$egreso) {
            $egreso['documento_origen'] = $egreso['nota_pedido_secuencial'] ?? null;
            if (preg_match('/^Documento de origen:\s*([^.]*)/u', (string)($egreso['observaciones'] ?? ''), $coincidencia)) {
                $egreso['documento_origen'] = trim($coincidencia[1]);
            }
        }
        unset($egreso);
        return $egresos;
    }

    /** Historial paginado de egresos para DataTables. */
    public function egresosDataTable(array $peticion): array {
        $draw=max(0,(int)($peticion['draw']??0));$inicio=max(0,(int)($peticion['start']??0));$largo=min(100,max(10,(int)($peticion['length']??10)));
        $busqueda=trim((string)($peticion['search']['value']??''));$where=['1=1'];$params=[];
        if($busqueda!==''){$where[]='(egr.secuencial LIKE :b1 OR egr.motivo LIKE :b2 OR egr.observaciones LIKE :b3 OR pers.nombre LIKE :b4 OR cc.nombre LIKE :b5 OR area.nombre LIKE :b6 OR nota.secuencial LIKE :b7)';foreach(range(1,7) as $i)$params[':b'.$i]='%'.$busqueda.'%';}
        $desde=trim((string)($peticion['fecha_desde']??''));$hasta=trim((string)($peticion['fecha_hasta']??''));
        if($desde!==''){$where[]='egr.fecha>=:desde';$params[':desde']=$desde;}if($hasta!==''){$where[]='egr.fecha<=:hasta';$params[':hasta']=$hasta;}
        $from=' FROM inv_bod_egresos egr JOIN vw_inv_talento_personal pers ON egr.responsable_id=pers.id LEFT JOIN inv_centros_consumo cc ON egr.centro_consumo_id=cc.id LEFT JOIN inv_talento_areas area ON egr.area_id=area.id LEFT JOIN inv_notas_pedido nota ON egr.nota_pedido_id=nota.id_nota';
        $whereSql=implode(' AND ',$where);$total=(int)$this->db->query('SELECT COUNT(*) FROM inv_bod_egresos')->fetchColumn();$conteo=$this->db->prepare('SELECT COUNT(*)'.$from.' WHERE '.$whereSql);$conteo->execute($params);$filtrados=(int)$conteo->fetchColumn();
        $columnas=['egr.secuencial','egr.fecha','nota.secuencial','area.nombre','pers.nombre','egr.motivo','egr.id'];$indice=(int)($peticion['order'][0]['column']??1);$orden=$columnas[$indice]??'egr.fecha';$direccion=strtolower((string)($peticion['order'][0]['dir']??'desc'))==='asc'?'ASC':'DESC';
        $sql="SELECT egr.id,egr.secuencial,egr.fecha,egr.motivo,egr.observaciones,pers.nombre responsable,COALESCE(cc.nombre,area.nombre) area_destino,nota.secuencial nota_pedido_secuencial,(SELECT COALESCE(SUM(d.cantidad),0) FROM inv_bod_egresos_detalles d WHERE d.egreso_id=egr.id) total_items {$from} WHERE {$whereSql} ORDER BY {$orden} {$direccion},egr.id DESC OFFSET {$inicio} ROWS FETCH NEXT {$largo} ROWS ONLY";
        $stmt=$this->db->prepare($sql);$stmt->execute($params);$data=$stmt->fetchAll();foreach($data as &$fila){$fila['documento_origen']=$fila['nota_pedido_secuencial']??null;if(preg_match('/^Documento de origen:\s*([^.]*)/u',(string)($fila['observaciones']??''),$m))$fila['documento_origen']=trim($m[1]);}unset($fila);
        return ['draw'=>$draw,'recordsTotal'=>$total,'recordsFiltered'=>$filtrados,'data'=>$data];
    }

    /**
     * Busca un egreso por ID con sus detalles asociados
     */
    public function buscarPorId($id) {
        // InvCabecera
        $sql = "SELECT egr.*, 
                       pers.nombre as responsable,
                       pers.identificacion as responsable_identificacion,
                       COALESCE(cc.nombre, area.nombre) as area_destino,
                       nota.secuencial as nota_pedido_secuencial
                FROM inv_bod_egresos egr
                JOIN vw_inv_talento_personal pers ON egr.responsable_id = pers.id
                LEFT JOIN inv_centros_consumo cc ON egr.centro_consumo_id = cc.id
                LEFT JOIN inv_talento_areas area ON egr.area_id = area.id
                LEFT JOIN inv_notas_pedido nota ON egr.nota_pedido_id = nota.id_nota
                WHERE egr.id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $cabecera = $stmt->fetch();

        if (!$cabecera) {
            return null;
        }

        // Detalles
        $sqlDet = "SELECT det.*, 
                          inv.nombre as item_nombre, 
                          COALESCE(NULLIF(prod.codigo, ''), inv.secuencial) as item_codigo,
                          COALESCE(NULLIF(prod.codigo, ''), inv.secuencial) as item_secuencial,
                          inv.marca as item_marca,
                          cat.nombre as item_categoria
                   FROM inv_bod_egresos_detalles det
                   JOIN inv_inventario inv ON det.item_id = inv.id
                   LEFT JOIN inv_productos prod ON prod.id = inv.producto_id
                   JOIN inv_categorias cat ON inv.categoria_id = cat.id
                   WHERE det.egreso_id = :id";
        $stmtDet = $this->db->prepare($sqlDet);
        $stmtDet->execute([':id' => $id]);
        $cabecera['detalles'] = $stmtDet->fetchAll();

        return $cabecera;
    }

    /**
     * Filtra los registros de egresos de bodega
     */
    public function filtrar($filtros = []) {
        $sql = "SELECT egr.*, 
                       pers.nombre as responsable,
                       COALESCE(cc.nombre, area.nombre) as area_destino,
                       nota.secuencial as nota_pedido_secuencial,
                       (SELECT SUM(det.cantidad) FROM inv_bod_egresos_detalles det WHERE det.egreso_id = egr.id) as total_items
                FROM inv_bod_egresos egr
                JOIN vw_inv_talento_personal pers ON egr.responsable_id = pers.id
                LEFT JOIN inv_centros_consumo cc ON egr.centro_consumo_id = cc.id
                LEFT JOIN inv_talento_areas area ON egr.area_id = area.id
                LEFT JOIN inv_notas_pedido nota ON egr.nota_pedido_id = nota.id_nota
                WHERE 1=1";
        
        $params = [];

        if (!empty($filtros['fecha_inicio'])) {
            $sql .= " AND egr.fecha >= :fecha_ini";
            $params[':fecha_ini'] = $filtros['fecha_inicio'];
        }

        if (!empty($filtros['fecha_fin'])) {
            $sql .= " AND egr.fecha <= :fecha_fin";
            $params[':fecha_fin'] = $filtros['fecha_fin'];
        }

        if (!empty($filtros['area_id'])) {
            $sql .= " AND egr.area_id = :area_id";
            $params[':area_id'] = $filtros['area_id'];
        }

        if (!empty($filtros['responsable_id'])) {
            $sql .= " AND egr.responsable_id = :resp_id";
            $params[':resp_id'] = $filtros['responsable_id'];
        }

        if (!empty($filtros['termino'])) {
            $sql .= " AND (egr.secuencial LIKE :term OR egr.motivo LIKE :term OR egr.observaciones LIKE :term)";
            $params[':term'] = '%' . $filtros['termino'] . '%';
        }

        $sql .= " ORDER BY egr.fecha DESC, egr.id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Registra un InvEgreso de Bodega (InvCabecera + Detalles) con validación estricta de stock
     */
    public function crear($datos, $detalles) {
        try {
            $this->db->beginTransaction();

            // Generar secuencial de egreso
            $secuencialObj = new InvSecuencial();
            $secuencial = $secuencialObj->generarSiguiente('egr');

            // Insertar InvCabecera de InvEgreso
            $sql = "INSERT INTO inv_bod_egresos (secuencial, area_id, responsable_id, fecha, motivo, observaciones, creado_por) 
                    VALUES (:sec, :area_id, :resp_id, :fecha, :motivo, :obs, :creado_por)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':sec' => $secuencial,
                ':area_id' => $datos['area_id'],
                ':resp_id' => $datos['responsable_id'],
                ':fecha' => !empty($datos['fecha']) ? $datos['fecha'] : date('Y-m-d'),
                ':motivo' => $datos['motivo'],
                ':obs' => isset($datos['observaciones']) ? $datos['observaciones'] : '',
                ':creado_por' => !empty($datos['creado_por']) ? $datos['creado_por'] : 'Admin Terminal'
            ]);

            $egresoId = $this->db->lastInsertId();

            // Validar stock e insertar detalles
            $sqlDet = "INSERT INTO inv_bod_egresos_detalles (egreso_id, item_id, cantidad) 
                       VALUES (:egr_id, :item_id, :cant)";
            $stmtDet = $this->db->prepare($sqlDet);

            $sqlStock = "UPDATE inv_inventario SET cantidad = cantidad - :cant WHERE id = :item_id AND cantidad >= :validar";
            $stmtStock = $this->db->prepare($sqlStock);

            $lockStock = defined('DB_DRIVER') && DB_DRIVER === 'sqlsrv' ? ' WITH (UPDLOCK, HOLDLOCK)' : '';
            $sqlCheck = "SELECT nombre, secuencial, cantidad FROM inv_inventario{$lockStock} WHERE id = :item_id";
            $stmtCheck = $this->db->prepare($sqlCheck);

            foreach ($detalles as $det) {
                if (empty($det['item_id']) || empty($det['cantidad']) || (int)$det['cantidad'] <= 0) {
                    continue;
                }

                $itemId = (int)$det['item_id'];
                $cant = (int)$det['cantidad'];

                // 1. Obtener y verificar stock disponible en inventario
                $stmtCheck->execute([':item_id' => $itemId]);
                $item = $stmtCheck->fetch();

                if (!$item) {
                    throw new Exception("El producto con ID '{$itemId}' no existe en el inventario.");
                }

                $stockActual = (int)$item['cantidad'];
                if ($stockActual < $cant) {
                    throw new Exception("Stock insuficiente para '{$item['nombre']}' ({$item['secuencial']}). Disponible: {$stockActual}, Solicitado: {$cant}");
                }

                // 2. Insertar Detalle de InvEgreso
                $stmtDet->execute([
                    ':egr_id' => $egresoId,
                    ':item_id' => $itemId,
                    ':cant' => $cant
                ]);

                // 3. Reducir stock en InvInventario
                $stmtStock->execute([
                    ':cant' => $cant,
                    ':item_id' => $itemId,
                    ':validar' => $cant,
                ]);
                if ($stmtStock->rowCount() !== 1) {
                    throw new RuntimeException('La existencia cambió mientras se confirmaba el egreso. Intente nuevamente.');
                }
            }

            // Registrar acción en la bitácora
            $bitacoraObj = new InvBitacora();
            $bitacoraObj->registrar('CREAR', 'bod', "Egreso de Bodega creado: {$secuencial} para el área ID {$datos['area_id']}");

            $this->db->commit();
            return $egresoId;
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Confirma una entrega desde una nota digital en una sola transacción.
     */
    public function crearDesdeNota(int $notaId, int $receptorId, array $cantidades, string $usuario, string $observaciones = ''): int
    {
        if ($notaId <= 0 || $receptorId <= 0) {
            throw new InvalidArgumentException('La nota y el receptor son obligatorios.');
        }

        try {
            $this->db->beginTransaction();
            $lock = DB_DRIVER === 'sqlsrv' ? ' WITH (UPDLOCK, HOLDLOCK)' : '';
            $stmtNota = $this->db->prepare("SELECT n.* FROM inv_notas_pedido n{$lock} WHERE n.id_nota = :id");
            $stmtNota->execute([':id' => $notaId]);
            $nota = $stmtNota->fetch();
            if (!$nota) {
                throw new RuntimeException('La nota de pedido no existe.');
            }
            if (in_array($nota['estado'], ['ATENDIDA', 'CERRADA', 'CANCELADA'], true)) {
                throw new RuntimeException('La nota ya no admite nuevos despachos.');
            }

            $stmtDetalles = $this->db->prepare(
                "SELECT d.*, i.nombre, COALESCE(NULLIF(p.codigo, ''), i.secuencial) AS item_codigo,
                        COALESCE(NULLIF(p.codigo, ''), i.secuencial) AS item_secuencial, i.cantidad AS stock_actual,
                        COALESCE(i.tipo_bien, p.tipo_bien, 'CC') AS tipo_bien
                 FROM inv_notas_pedido_detalles d
                 JOIN inv_inventario i{$lock} ON i.id = d.item_id
                 LEFT JOIN inv_productos p ON p.id = i.producto_id
                 WHERE d.nota_id = :nota ORDER BY d.id_detalle"
            );
            $stmtDetalles->execute([':nota' => $notaId]);
            $lineas = [];
            foreach ($stmtDetalles->fetchAll() as $detalle) {
                $cantidad = max(0, (int)($cantidades[$detalle['id_detalle']] ?? 0));
                $pendiente = (int)$detalle['cantidad_solicitada'] - (int)$detalle['cantidad_entregada'];
                if ($cantidad === 0) continue;
                if ($cantidad > $pendiente) {
                    throw new RuntimeException("La entrega de {$detalle['nombre']} supera la cantidad pendiente.");
                }
                if ($cantidad > (int)$detalle['stock_actual']) {
                    throw new RuntimeException("No existe stock suficiente de {$detalle['nombre']}.");
                }
                if ($detalle['tipo_bien'] === 'AF' && $cantidad !== 1) {
                    throw new RuntimeException('Cada nota de activo fijo solo puede despachar una unidad.');
                }
                $detalle['cantidad_despacho'] = $cantidad;
                $lineas[] = $detalle;
            }
            if (!$lineas) {
                throw new InvalidArgumentException('Indique al menos una cantidad disponible para entregar.');
            }

            $secuencial = (new InvSecuencial())->generarSiguiente('egr');
            $insertarEgreso = $this->db->prepare(
                "INSERT INTO inv_bod_egresos
                    (secuencial, area_id, centro_consumo_id, responsable_id, nota_pedido_id,
                     fecha, motivo, observaciones, creado_por, estado)
                 VALUES
                    (:secuencial, :area, :centro, :responsable, :nota,
                     :fecha, :motivo, :observaciones, :usuario, 'CONFIRMADO')"
            );
            $insertarEgreso->execute([
                ':secuencial' => $secuencial,
                ':area' => (int)$nota['centro_consumo_id'],
                ':centro' => (int)$nota['centro_consumo_id'],
                ':responsable' => $receptorId,
                ':nota' => $notaId,
                ':fecha' => date('Y-m-d'),
                ':motivo' => $nota['motivo'],
                ':observaciones' => trim($observaciones),
                ':usuario' => $usuario,
            ]);
            $egresoId = (int)$this->db->lastInsertId();

            $insertarDetalle = $this->db->prepare(
                "INSERT INTO inv_bod_egresos_detalles (egreso_id, item_id, cantidad)
                 VALUES (:egreso, :item, :cantidad)"
            );
            $actualizarStock = $this->db->prepare(
                "UPDATE inv_inventario SET cantidad = cantidad - :cantidad
                 WHERE id = :item AND cantidad >= :cantidad_validacion"
            );
            $actualizarNota = $this->db->prepare(
                "UPDATE inv_notas_pedido_detalles SET cantidad_entregada = cantidad_entregada + :cantidad
                 WHERE id_detalle = :detalle"
            );
            $insertarKardex = $this->db->prepare(
                "INSERT INTO inv_kardex
                    (item_id, tipo_movimiento, documento_tipo, documento_id, documento_secuencial,
                     entrada, salida, saldo_anterior, saldo_resultante, centro_consumo_id,
                     responsable_id, usuario_registro, observaciones)
                 VALUES
                    (:item, 'EGRESO', 'EGRESO', :documento, :secuencial,
                     0, :salida, :saldo_anterior, :saldo_resultante, :centro,
                     :responsable, :usuario, :observaciones)"
            );

            foreach ($lineas as $detalle) {
                $cantidad = (int)$detalle['cantidad_despacho'];
                $saldoAnterior = (int)$detalle['stock_actual'];
                $insertarDetalle->execute([':egreso' => $egresoId, ':item' => (int)$detalle['item_id'], ':cantidad' => $cantidad]);
                $actualizarStock->execute([
                    ':cantidad' => $cantidad,
                    ':item' => (int)$detalle['item_id'],
                    ':cantidad_validacion' => $cantidad,
                ]);
                if ($actualizarStock->rowCount() !== 1) {
                    throw new RuntimeException('La existencia cambió mientras se confirmaba el despacho. Intente nuevamente.');
                }
                $actualizarNota->execute([':cantidad' => $cantidad, ':detalle' => (int)$detalle['id_detalle']]);
                $insertarKardex->execute([
                    ':item' => (int)$detalle['item_id'],
                    ':documento' => $egresoId,
                    ':secuencial' => $secuencial,
                    ':salida' => $cantidad,
                    ':saldo_anterior' => $saldoAnterior,
                    ':saldo_resultante' => $saldoAnterior - $cantidad,
                    ':centro' => (int)$nota['centro_consumo_id'],
                    ':responsable' => $receptorId,
                    ':usuario' => $usuario,
                    ':observaciones' => 'Despacho de la nota ' . $nota['secuencial'],
                ]);
            }

            $pendientes = $this->db->prepare(
                "SELECT SUM(cantidad_solicitada - cantidad_entregada)
                 FROM inv_notas_pedido_detalles WHERE nota_id = :nota"
            );
            $pendientes->execute([':nota' => $notaId]);
            $estado = (int)$pendientes->fetchColumn() === 0 ? 'ATENDIDA' : 'PARCIAL';
            $actualizarCabecera = $this->db->prepare(
                "UPDATE inv_notas_pedido
                 SET receptor_id = :receptor, estado = :estado, fecha_actualizacion = SYSDATETIME()
                 WHERE id_nota = :nota"
            );
            $actualizarCabecera->execute([':receptor' => $receptorId, ':estado' => $estado, ':nota' => $notaId]);

            (new InvBitacora())->registrar('CREAR', 'bod', "Egreso {$secuencial} generado desde la nota {$nota['secuencial']}.");
            $this->db->commit();
            return $egresoId;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        }
    }

    /** Registra la cabecera y el movimiento; descuenta stock y genera Kardex en una transacción. */
    public function crearDesdeMovimiento(array $datos, array $detalles): int
    {
        $areaId = (int)($datos['area_id'] ?? 0);
        $centroId = (int)($datos['centro_consumo_id'] ?? 0);
        $responsableId = (int)($datos['responsable_id'] ?? 0);
        $motivo = trim((string)($datos['motivo'] ?? ''));
        $areaId = $this->resolverAreaLegada($areaId, $responsableId);
        if ($areaId <= 0 || $centroId <= 0 || $responsableId <= 0 || $motivo === '') {
            throw new InvalidArgumentException('Complete el centro de consumo, el receptor y el motivo del egreso.');
        }

        $normalizados = [];
        foreach ($detalles as $detalle) {
            $itemId = (int)($detalle['item_id'] ?? 0);
            $cantidad = (int)($detalle['cantidad'] ?? 0);
            if ($itemId <= 0 || $cantidad <= 0) continue;
            if (isset($normalizados[$itemId])) throw new InvalidArgumentException('No repita productos en el egreso.');
            $normalizados[$itemId] = $cantidad;
        }
        if (!$normalizados) throw new InvalidArgumentException('Agregue al menos un producto al egreso.');

        try {
            $this->db->beginTransaction();
            $lock = defined('DB_DRIVER') && DB_DRIVER === 'sqlsrv' ? ' WITH (UPDLOCK, HOLDLOCK)' : '';
            $buscarItem = $this->db->prepare(
                "SELECT i.id, i.nombre, COALESCE(NULLIF(p.codigo, ''), i.secuencial) AS codigo,
                        i.cantidad, i.valor
                 FROM inv_inventario i{$lock}
                 LEFT JOIN inv_productos p ON p.id = i.producto_id
                 WHERE i.id = :id AND i.activo = 1"
            );
            $lineas = [];
            foreach ($normalizados as $itemId => $cantidad) {
                $buscarItem->execute([':id' => $itemId]);
                $item = $buscarItem->fetch();
                if (!$item) throw new RuntimeException("El producto con ID {$itemId} no existe o está inactivo.");
                if ((int)$item['cantidad'] < $cantidad) {
                    throw new RuntimeException("Stock insuficiente para {$item['codigo']} · {$item['nombre']}. Disponible: {$item['cantidad']}; solicitado: {$cantidad}.");
                }
                $item['cantidad_despacho'] = $cantidad;
                $lineas[] = $item;
            }

            $secuencial = (new InvSecuencial())->generarSiguiente('egr');
            $documento = trim((string)($datos['documento_origen'] ?? ''));
            $observaciones = trim((string)($datos['observaciones'] ?? ''));
            if ($documento !== '') $observaciones = 'Documento de origen: ' . $documento . ($observaciones !== '' ? '. ' . $observaciones : '');
            $insertarEgreso = $this->db->prepare(
                "INSERT INTO inv_bod_egresos
                    (secuencial, area_id, centro_consumo_id, responsable_id, nota_pedido_id, fecha, motivo, observaciones, creado_por, estado)
                 VALUES (:secuencial, :area, :centro, :responsable, NULL, :fecha, :motivo, :observaciones, :usuario, 'CONFIRMADO')"
            );
            $insertarEgreso->execute([
                ':secuencial' => $secuencial, ':area' => $areaId, ':centro' => $centroId,
                ':responsable' => $responsableId, ':fecha' => $datos['fecha'] ?? date('Y-m-d'),
                ':motivo' => $motivo, ':observaciones' => $observaciones,
                ':usuario' => $datos['creado_por'] ?? 'Sistema',
            ]);
            $egresoId = (int)$this->db->lastInsertId();
            $insertarDetalle = $this->db->prepare('INSERT INTO inv_bod_egresos_detalles (egreso_id, item_id, cantidad) VALUES (:egreso, :item, :cantidad)');
            $actualizarStock = $this->db->prepare('UPDATE inv_inventario SET cantidad = cantidad - :cantidad WHERE id = :item AND cantidad >= :validar');
            $insertarKardex = $this->db->prepare(
                "INSERT INTO inv_kardex
                    (item_id, tipo_movimiento, documento_tipo, documento_id, documento_secuencial, entrada, salida,
                     saldo_anterior, saldo_resultante, centro_consumo_id, responsable_id, usuario_registro, observaciones)
                 VALUES (:item, 'EGRESO', 'EGRESO', :documento, :secuencial, 0, :salida,
                         :anterior, :saldo, :centro, :responsable, :usuario, :observaciones)"
            );
            foreach ($lineas as $item) {
                $cantidad = (int)$item['cantidad_despacho'];
                $anterior = (int)$item['cantidad'];
                $insertarDetalle->execute([':egreso' => $egresoId, ':item' => $item['id'], ':cantidad' => $cantidad]);
                $actualizarStock->execute([':cantidad' => $cantidad, ':item' => $item['id'], ':validar' => $cantidad]);
                if ($actualizarStock->rowCount() !== 1) throw new RuntimeException('La existencia cambió mientras se confirmaba el egreso. Intente nuevamente.');
                $insertarKardex->execute([
                    ':item' => $item['id'], ':documento' => $egresoId, ':secuencial' => $secuencial,
                    ':salida' => $cantidad, ':anterior' => $anterior, ':saldo' => $anterior - $cantidad,
                    ':centro' => $centroId, ':responsable' => $responsableId,
                    ':usuario' => $datos['creado_por'] ?? 'Sistema', ':observaciones' => $documento !== '' ? 'Egreso · ingreso/documento ' . $documento : 'Egreso de bodega',
                ]);
            }
            (new InvBitacora())->registrar('CREAR', 'bod', "Egreso {$secuencial} confirmado; existencias descontadas y Kardex generado.");
            $this->db->commit();
            return $egresoId;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * area_id se conserva únicamente por compatibilidad con la tabla histórica.
     * La interfaz ya no solicita Área institucional: se deriva de la asignación
     * vigente del receptor y, si no existe, usa un registro técnico neutral.
     */
    private function resolverAreaLegada(int $areaId, int $responsableId): int
    {
        if ($areaId > 0) {
            $validar = $this->db->prepare('SELECT COUNT(*) FROM inv_talento_areas WHERE id = :id');
            $validar->execute([':id' => $areaId]);
            if ((int)$validar->fetchColumn() > 0) return $areaId;
        }
        if ($responsableId > 0) {
            $asignacion = $this->db->prepare(
                "SELECT TOP 1 area_id FROM inv_talento_asignaciones
                 WHERE personal_id = :persona AND (fecha_fin IS NULL OR fecha_fin >= CAST(GETDATE() AS date))
                 ORDER BY fecha_inicio DESC, id DESC"
            );
            $asignacion->execute([':persona' => $responsableId]);
            $asignada = (int)$asignacion->fetchColumn();
            if ($asignada > 0) return $asignada;
        }
        $nombre = 'Centro de consumo (asignación automática)';
        $buscar = $this->db->prepare('SELECT id FROM inv_talento_areas WHERE nombre = :nombre');
        $buscar->execute([':nombre' => $nombre]);
        $neutral = (int)$buscar->fetchColumn();
        if ($neutral > 0) return $neutral;
        $crear = $this->db->prepare('INSERT INTO inv_talento_areas (nombre) VALUES (:nombre)');
        $crear->execute([':nombre' => $nombre]);
        $buscar->execute([':nombre' => $nombre]);
        return (int)$buscar->fetchColumn();
    }

    public function obtenerKardex(array $filtros = []): array
    {
        $sql = "SELECT k.*, COALESCE(NULLIF(p.codigo, ''),NULLIF(c.codigo,''),i.secuencial) AS item_codigo,
                       COALESCE(NULLIF(p.codigo, ''),NULLIF(c.codigo,''),i.secuencial) AS item_secuencial, i.nombre AS item_nombre,
                       cc.nombre AS centro_consumo, pers.nombre AS responsable
                FROM inv_kardex k
                JOIN inv_inventario i ON i.id = k.item_id
                LEFT JOIN inv_productos p ON p.id = i.producto_id
                LEFT JOIN inv_categorias c ON c.id=i.categoria_id
                LEFT JOIN inv_unidades u ON u.id=p.unidad_id
                LEFT JOIN inv_centros_consumo cc ON cc.id = k.centro_consumo_id
                LEFT JOIN vw_inv_talento_personal pers ON pers.id = k.responsable_id
                WHERE 1 = 1";
        $params = [];
        if (!empty($filtros['item_id'])) {
            $sql .= " AND k.item_id = :item";
            $params[':item'] = (int)$filtros['item_id'];
        }
        if (!empty($filtros['termino'])) {
            $sql .= " AND (i.nombre LIKE :termino OR i.secuencial LIKE :termino OR p.codigo LIKE :termino OR i.marca LIKE :termino OR c.codigo LIKE :termino OR c.nombre LIKE :termino OR u.nombre LIKE :termino OR u.extra LIKE :termino OR k.documento_secuencial LIKE :termino)";
            $params[':termino'] = '%' . trim($filtros['termino']) . '%';
        }
        $sql .= " ORDER BY k.fecha_movimiento DESC, k.id_movimiento DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** Respuesta paginada para la consulta general de Kardex. */
    public function kardexDataTable(array $peticion): array
    {
        $draw = max(0, (int)($peticion['draw'] ?? 0));
        $inicio = max(0, (int)($peticion['start'] ?? 0));
        $largo = max(10, min(100, (int)($peticion['length'] ?? 25)));
        $where = ['1=1'];
        $params = [];
        $termino = trim((string)($peticion['search']['value'] ?? $peticion['termino'] ?? ''));
        if ($termino !== '') {
            $where[] = '(i.nombre LIKE :termino OR i.secuencial LIKE :termino OR p.codigo LIKE :termino OR i.marca LIKE :termino OR c.codigo LIKE :termino OR c.nombre LIKE :termino OR u.nombre LIKE :termino OR u.extra LIKE :termino OR k.documento_secuencial LIKE :termino OR k.observaciones LIKE :termino)';
            $params[':termino'] = '%' . $termino . '%';
        }
        if (!empty($peticion['tipo'])) {
            $where[] = 'k.tipo_movimiento = :tipo';
            $params[':tipo'] = strtoupper(trim((string)$peticion['tipo']));
        }
        if (!empty($peticion['fecha_desde'])) {
            $where[] = 'CAST(k.fecha_movimiento AS date) >= :desde';
            $params[':desde'] = $peticion['fecha_desde'];
        }
        if (!empty($peticion['fecha_hasta'])) {
            $where[] = 'CAST(k.fecha_movimiento AS date) <= :hasta';
            $params[':hasta'] = $peticion['fecha_hasta'];
        }
        $whereSql = implode(' AND ', $where);
        $total = (int)$this->db->query('SELECT COUNT(*) FROM inv_kardex')->fetchColumn();
        $contar = $this->db->prepare("SELECT COUNT(*) FROM inv_kardex k JOIN inv_inventario i ON i.id=k.item_id LEFT JOIN inv_productos p ON p.id=i.producto_id LEFT JOIN inv_categorias c ON c.id=i.categoria_id LEFT JOIN inv_unidades u ON u.id=p.unidad_id WHERE {$whereSql}");
        $contar->execute($params);
        $filtrados = (int)$contar->fetchColumn();
        $columnas = ['k.fecha_movimiento','k.tipo_movimiento','p.codigo','i.nombre','k.documento_secuencial','k.entrada','k.salida','k.saldo_resultante','pers.nombre','k.observaciones'];
        $indiceOrden = (int)($peticion['order'][0]['column'] ?? 0);
        $direccion = strtolower((string)($peticion['order'][0]['dir'] ?? 'desc')) === 'asc' ? 'ASC' : 'DESC';
        $orden = $columnas[$indiceOrden] ?? 'k.fecha_movimiento';
        $sql = "SELECT k.id_movimiento, k.fecha_movimiento, k.tipo_movimiento, k.documento_tipo,
                       k.documento_secuencial, k.entrada, k.salida, k.saldo_anterior, k.saldo_resultante,
                       k.observaciones, COALESCE(NULLIF(p.codigo, ''),NULLIF(c.codigo,''),i.secuencial) item_codigo,
                       i.nombre item_nombre, cc.nombre centro_consumo, pers.nombre responsable
                FROM inv_kardex k
                JOIN inv_inventario i ON i.id=k.item_id
                LEFT JOIN inv_productos p ON p.id=i.producto_id
                LEFT JOIN inv_categorias c ON c.id=i.categoria_id
                LEFT JOIN inv_unidades u ON u.id=p.unidad_id
                LEFT JOIN inv_centros_consumo cc ON cc.id=k.centro_consumo_id
                LEFT JOIN vw_inv_talento_personal pers ON pers.id=k.responsable_id
                WHERE {$whereSql}
                ORDER BY {$orden} {$direccion}, k.id_movimiento DESC
                OFFSET {$inicio} ROWS FETCH NEXT {$largo} ROWS ONLY";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return ['draw'=>$draw,'recordsTotal'=>$total,'recordsFiltered'=>$filtrados,'data'=>$stmt->fetchAll()];
    }
}
