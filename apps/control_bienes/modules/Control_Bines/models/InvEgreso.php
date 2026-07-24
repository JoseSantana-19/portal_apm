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
                       area.nombre as area_destino,
                       (SELECT SUM(det.cantidad) FROM inv_bod_egresos_detalles det WHERE det.egreso_id = egr.id) as total_items
                FROM inv_bod_egresos egr
                JOIN inv_talento_personal pers ON egr.responsable_id = pers.id
                JOIN inv_talento_areas area ON egr.area_id = area.id
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
                       area.nombre as area_destino
                FROM inv_bod_egresos egr
                JOIN inv_talento_personal pers ON egr.responsable_id = pers.id
                JOIN inv_talento_areas area ON egr.area_id = area.id
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
                       area.nombre as area_destino,
                       (SELECT SUM(det.cantidad) FROM inv_bod_egresos_detalles det WHERE det.egreso_id = egr.id) as total_items
                FROM inv_bod_egresos egr
                JOIN inv_talento_personal pers ON egr.responsable_id = pers.id
                JOIN inv_talento_areas area ON egr.area_id = area.id
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
            $bitacoraObj->registrar('ELIMINAR', 'bod', "Registro de InvEgreso de Bodega creado: {$secuencial} para el área ID {$datos['area_id']}");

            $this->db->commit();
            return $egresoId;
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }
}
