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
        return $stmt->fetchAll();
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
                          inv.secuencial as item_secuencial,
                          inv.marca as item_marca,
                          cat.nombre as item_categoria
                   FROM inv_bod_egresos_detalles det
                   JOIN inv_inventario inv ON det.item_id = inv.id
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

            $sqlStock = "UPDATE inv_inventario SET cantidad = cantidad - :cant WHERE id = :item_id";
            $stmtStock = $this->db->prepare($sqlStock);

            $sqlCheck = "SELECT nombre, secuencial, cantidad FROM inv_inventario WHERE id = :item_id";
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
                    ':item_id' => $itemId
                ]);
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
                "SELECT d.*, i.nombre, i.secuencial AS item_secuencial, i.cantidad AS stock_actual,
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

    public function obtenerKardex(array $filtros = []): array
    {
        $sql = "SELECT k.*, i.secuencial AS item_secuencial, i.nombre AS item_nombre,
                       cc.nombre AS centro_consumo, pers.nombre AS responsable
                FROM inv_kardex k
                JOIN inv_inventario i ON i.id = k.item_id
                LEFT JOIN inv_centros_consumo cc ON cc.id = k.centro_consumo_id
                LEFT JOIN vw_inv_talento_personal pers ON pers.id = k.responsable_id
                WHERE 1 = 1";
        $params = [];
        if (!empty($filtros['item_id'])) {
            $sql .= " AND k.item_id = :item";
            $params[':item'] = (int)$filtros['item_id'];
        }
        if (!empty($filtros['termino'])) {
            $sql .= " AND (i.nombre LIKE :termino OR i.secuencial LIKE :termino OR k.documento_secuencial LIKE :termino)";
            $params[':termino'] = '%' . trim($filtros['termino']) . '%';
        }
        $sql .= " ORDER BY k.fecha_movimiento DESC, k.id_movimiento DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
