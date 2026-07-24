<?php
require_once ROOT_PATH . 'core/Model.php';
require_once ROOT_PATH . 'modules/Central/models/InvSecuencial.php';
require_once ROOT_PATH . 'modules/Bitacoras/models/BitacoraModel.php';

class InvIngreso extends Model {
    // Conexión PDO heredada en $this->db


    /**
     * Obtiene todos los ingresos de bodega
     */
    public function obtenerTodos() {
        $sql = "SELECT ing.*, 
                       pers.nombre as responsable, 
                       (SELECT SUM(det.cantidad) FROM inv_bod_ingresos_detalles det WHERE det.ingreso_id = ing.id) as total_items,
                       (SELECT SUM(det.cantidad * det.valor_unitario) FROM inv_bod_ingresos_detalles det WHERE det.ingreso_id = ing.id) as total_valor
                FROM inv_bod_ingresos ing
                JOIN inv_talento_personal pers ON ing.responsable_id = pers.id
                ORDER BY ing.fecha DESC, ing.id DESC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Busca un ingreso por ID con sus detalles asociados
     */
    public function buscarPorId($id) {
        // InvCabecera
        $sql = "SELECT ing.*, 
                       pers.nombre as responsable,
                       pers.identificacion as responsable_identificacion
                FROM inv_bod_ingresos ing
                JOIN inv_talento_personal pers ON ing.responsable_id = pers.id
                WHERE ing.id = :id";
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
                   FROM inv_bod_ingresos_detalles det
                   JOIN inv_inventario inv ON det.item_id = inv.id
                   JOIN inv_categorias cat ON inv.categoria_id = cat.id
                   WHERE det.ingreso_id = :id";
        $stmtDet = $this->db->prepare($sqlDet);
        $stmtDet->execute([':id' => $id]);
        $cabecera['detalles'] = $stmtDet->fetchAll();

        return $cabecera;
    }

    /**
     * Filtra los registros de ingresos de bodega
     */
    public function filtrar($filtros = []) {
        $sql = "SELECT ing.*, 
                       pers.nombre as responsable,
                       (SELECT SUM(det.cantidad) FROM inv_bod_ingresos_detalles det WHERE det.ingreso_id = ing.id) as total_items,
                       (SELECT SUM(det.cantidad * det.valor_unitario) FROM inv_bod_ingresos_detalles det WHERE det.ingreso_id = ing.id) as total_valor
                FROM inv_bod_ingresos ing
                JOIN inv_talento_personal pers ON ing.responsable_id = pers.id
                WHERE 1=1";
        
        $params = [];

        if (!empty($filtros['fecha_inicio'])) {
            $sql .= " AND ing.fecha >= :fecha_ini";
            $params[':fecha_ini'] = $filtros['fecha_inicio'];
        }

        if (!empty($filtros['fecha_fin'])) {
            $sql .= " AND ing.fecha <= :fecha_fin";
            $params[':fecha_fin'] = $filtros['fecha_fin'];
        }

        if (!empty($filtros['proveedor'])) {
            $sql .= " AND ing.proveedor LIKE :proveedor";
            $params[':proveedor'] = '%' . $filtros['proveedor'] . '%';
        }

        if (!empty($filtros['responsable_id'])) {
            $sql .= " AND ing.responsable_id = :resp_id";
            $params[':resp_id'] = $filtros['responsable_id'];
        }

        if (!empty($filtros['termino'])) {
            $sql .= " AND (ing.secuencial LIKE :term OR ing.proveedor LIKE :term OR ing.observaciones LIKE :term)";
            $params[':term'] = '%' . $filtros['termino'] . '%';
        }

        $sql .= " ORDER BY ing.fecha DESC, ing.id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Crea un ingreso de bodega (InvCabecera + Detalles) y actualiza el stock del inventario
     */
    public function crear($datos, $detalles) {
        try {
            $this->db->beginTransaction();

            // Generar secuencial de ingreso
            $secuencialObj = new InvSecuencial();
            $secuencial = $secuencialObj->generarSiguiente('ing');

            // Insertar InvCabecera
            $sql = "INSERT INTO inv_bod_ingresos (secuencial, proveedor, fecha, observaciones, responsable_id, creado_por) 
                    VALUES (:sec, :prov, :fecha, :obs, :resp_id, :creado_por)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':sec' => $secuencial,
                ':prov' => $datos['proveedor'],
                ':fecha' => !empty($datos['fecha']) ? $datos['fecha'] : date('Y-m-d'),
                ':obs' => isset($datos['observaciones']) ? $datos['observaciones'] : '',
                ':resp_id' => $datos['responsable_id'],
                ':creado_por' => !empty($datos['creado_por']) ? $datos['creado_por'] : 'Admin Terminal'
            ]);

            $ingresoId = $this->db->lastInsertId();

            // Insertar Detalles y Actualizar Stock
            $sqlDet = "INSERT INTO inv_bod_ingresos_detalles (ingreso_id, item_id, cantidad, valor_unitario) 
                       VALUES (:ing_id, :item_id, :cant, :val_unit)";
            $stmtDet = $this->db->prepare($sqlDet);

            $sqlStock = "UPDATE inv_inventario SET cantidad = cantidad + :cant WHERE id = :item_id";
            $stmtStock = $this->db->prepare($sqlStock);

            foreach ($detalles as $det) {
                if (empty($det['item_id']) || empty($det['cantidad']) || (int)$det['cantidad'] <= 0) {
                    continue; // Saltar registros vacíos o inválidos
                }

                $cant = (int)$det['cantidad'];
                $valUnit = (float)$det['valor_unitario'];

                // Insertar detalle
                $stmtDet->execute([
                    ':ing_id' => $ingresoId,
                    ':item_id' => $det['item_id'],
                    ':cant' => $cant,
                    ':val_unit' => $valUnit
                ]);

                // Actualizar inventario (aumentar cantidad/stock)
                $stmtStock->execute([
                    ':cant' => $cant,
                    ':item_id' => $det['item_id']
                ]);
            }

            // Registrar acción en la bitácora
            $bitacoraObj = new InvBitacora();
            $bitacoraObj->registrar('CREAR', 'bod', "Registro de InvIngreso a Bodega creado: {$secuencial} del proveedor {$datos['proveedor']}");

            $this->db->commit();
            return $ingresoId;
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }
}
