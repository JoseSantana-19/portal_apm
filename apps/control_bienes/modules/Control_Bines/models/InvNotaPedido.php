<?php
require_once ROOT_PATH . 'core/Model.php';
require_once ROOT_PATH . 'modules/Central/models/InvSecuencial.php';
require_once ROOT_PATH . 'modules/Bitacoras/models/BitacoraModel.php';

class InvNotaPedido extends Model
{
    /**
     * Traduce el funcionario seleccionado en el formulario al registro interno
     * requerido por las notas y egresos. Conserva la compatibilidad historica
     * de centro_consumo_id sin volver a mostrar los centros semilla antiguos.
     */
    public function obtenerOCrearCentroParaPersona(int $personaId): int
    {
        if ($personaId <= 0) {
            throw new InvalidArgumentException('Seleccione una persona como centro de consumo.');
        }

        $personaStmt = $this->db->prepare(
            "SELECT id, nombre, identificacion, area
             FROM vw_inv_talento_personal
             WHERE id = :id AND estado = 1"
        );
        $personaStmt->execute([':id' => $personaId]);
        $persona = $personaStmt->fetch();
        if (!$persona) {
            throw new InvalidArgumentException('La persona seleccionada no existe o ya no se encuentra activa.');
        }

        $centroStmt = $this->db->prepare(
            "SELECT id FROM inv_centros_consumo
             WHERE funcionario_id = :persona OR codigo = :codigo
             ORDER BY id ASC"
        );
        $codigoCentro = 'PER-' . $personaId;
        $centroStmt->execute([':persona' => $personaId, ':codigo' => $codigoCentro]);
        $centroId = (int)$centroStmt->fetchColumn();
        if ($centroId > 0) {
            $actualizar = $this->db->prepare(
                "UPDATE inv_centros_consumo
                 SET nombre = :nombre, funcionario = :funcionario, funcionario_id = :persona
                 WHERE id = :id"
            );
            $actualizar->execute([
                ':nombre' => $persona['nombre'],
                ':funcionario' => $persona['nombre'],
                ':persona' => $personaId,
                ':id' => $centroId,
            ]);
            return $centroId;
        }

        $grupoStmt = $this->db->query(
            "SELECT id FROM inv_grupo_centros_consumo
             WHERE codigo = 'PERSONAL'"
        );
        $grupoId = (int)$grupoStmt->fetchColumn();
        if ($grupoId <= 0) {
            $crearGrupo = $this->db->prepare(
                "INSERT INTO inv_grupo_centros_consumo (codigo, nombre, representante)
                 VALUES ('PERSONAL', 'PERSONAL - CENTROS DE CONSUMO', :representante)"
            );
            $crearGrupo->execute([':representante' => 'Talento Humano']);
            $grupoId = (int)$this->db->query(
                "SELECT id FROM inv_grupo_centros_consumo WHERE codigo = 'PERSONAL'"
            )->fetchColumn();
        }

        $crearCentro = $this->db->prepare(
            "INSERT INTO inv_centros_consumo
                (grupo_id, codigo, nombre, funcionario, funcionario_id)
             VALUES
                (:grupo, :codigo, :nombre, :funcionario, :persona)"
        );
        $crearCentro->execute([
            ':grupo' => $grupoId,
            ':codigo' => $codigoCentro,
            ':nombre' => $persona['nombre'],
            ':funcionario' => $persona['nombre'],
            ':persona' => $personaId,
        ]);

        $centroStmt->execute([':persona' => $personaId, ':codigo' => $codigoCentro]);
        $centroId = (int)$centroStmt->fetchColumn();
        if ($centroId <= 0) {
            throw new RuntimeException('No fue posible vincular la persona con el centro de consumo.');
        }
        return $centroId;
    }

    public function obtenerTodos(array $filtros = []): array
    {
        $sql = "SELECT n.*, cc.codigo AS centro_codigo, cc.nombre AS centro_consumo,
                       sol.nombre AS solicitante, rec.nombre AS receptor,
                       COUNT(d.id_detalle) AS total_productos,
                       SUM(d.cantidad_solicitada) AS total_solicitado,
                       SUM(d.cantidad_entregada) AS total_entregado
                FROM inv_notas_pedido n
                JOIN inv_centros_consumo cc ON cc.id = n.centro_consumo_id
                JOIN vw_inv_talento_personal sol ON sol.id = n.solicitante_id
                LEFT JOIN vw_inv_talento_personal rec ON rec.id = n.receptor_id
                JOIN inv_notas_pedido_detalles d ON d.nota_id = n.id_nota
                WHERE 1 = 1";
        $params = [];

        if (!empty($filtros['estado'])) {
            $sql .= " AND n.estado = :estado";
            $params[':estado'] = $filtros['estado'];
        }
        if (!empty($filtros['termino'])) {
            $sql .= " AND (n.secuencial LIKE :termino OR cc.nombre LIKE :termino OR sol.nombre LIKE :termino OR n.motivo LIKE :termino)";
            $params[':termino'] = '%' . trim($filtros['termino']) . '%';
        }

        $sql .= " GROUP BY n.id_nota, n.secuencial, n.centro_consumo_id, n.solicitante_id,
                         n.receptor_id, n.fecha_solicitud, n.motivo, n.observaciones,
                         n.tipo_bien, n.estado, n.grupo_solicitud, n.creado_por,
                         n.fecha_creacion, n.fecha_actualizacion, cc.codigo, cc.nombre,
                         sol.nombre, rec.nombre
                  ORDER BY CASE WHEN n.estado IN ('ENVIADA','EN_REVISION','DISPONIBLE','PARCIAL','SIN_EXISTENCIAS') THEN 0 ELSE 1 END,
                           n.fecha_creacion DESC, n.id_nota DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT n.*, cc.codigo AS centro_codigo, cc.nombre AS centro_consumo,
                    sol.nombre AS solicitante, rec.nombre AS receptor
             FROM inv_notas_pedido n
             JOIN inv_centros_consumo cc ON cc.id = n.centro_consumo_id
             JOIN vw_inv_talento_personal sol ON sol.id = n.solicitante_id
             LEFT JOIN vw_inv_talento_personal rec ON rec.id = n.receptor_id
             WHERE n.id_nota = :id"
        );
        $stmt->execute([':id' => $id]);
        $nota = $stmt->fetch();
        if (!$nota) {
            return null;
        }

        $detalle = $this->db->prepare(
            "SELECT d.*, i.secuencial AS item_secuencial, i.nombre AS item_nombre,
                    i.marca, i.cantidad AS stock_actual,
                    COALESCE(i.tipo_bien, p.tipo_bien, 'CC') AS tipo_bien,
                    (d.cantidad_solicitada - d.cantidad_entregada) AS cantidad_pendiente
             FROM inv_notas_pedido_detalles d
             JOIN inv_inventario i ON i.id = d.item_id
             LEFT JOIN inv_productos p ON p.id = i.producto_id
             WHERE d.nota_id = :id
             ORDER BY d.id_detalle"
        );
        $detalle->execute([':id' => $id]);
        $nota['detalles'] = $detalle->fetchAll();
        return $nota;
    }

    /**
     * Crea una nota para consumo corriente y una nota por cada unidad de activo fijo.
     * Una solicitud mixta queda vinculada mediante grupo_solicitud.
     */
    public function crearSolicitud(array $datos, array $detalles): array
    {
        if (empty($datos['centro_consumo_id']) || empty($datos['solicitante_id']) || trim($datos['motivo'] ?? '') === '') {
            throw new InvalidArgumentException('Complete el centro de consumo, solicitante y motivo.');
        }
        if (empty($detalles)) {
            throw new InvalidArgumentException('Agregue al menos un producto a la solicitud.');
        }

        $ids = [];
        foreach ($detalles as $detalle) {
            $itemId = (int)($detalle['item_id'] ?? 0);
            $cantidad = (int)($detalle['cantidad'] ?? 0);
            if ($itemId <= 0 || $cantidad <= 0) {
                throw new InvalidArgumentException('Todos los productos deben tener una cantidad válida.');
            }
            if (isset($ids[$itemId])) {
                $ids[$itemId] += $cantidad;
            } else {
                $ids[$itemId] = $cantidad;
            }
        }

        try {
            $this->db->beginTransaction();
            $items = [];
            $buscar = $this->db->prepare(
                "SELECT i.id, i.nombre, i.categoria_id, COALESCE(i.tipo_bien, p.tipo_bien, 'CC') AS tipo_bien
                 FROM inv_inventario i
                 LEFT JOIN inv_productos p ON p.id = i.producto_id
                 WHERE i.id = :id AND i.activo = 1"
            );
            foreach ($ids as $itemId => $cantidad) {
                $buscar->execute([':id' => $itemId]);
                $item = $buscar->fetch();
                if (!$item) {
                    throw new RuntimeException("El producto con ID {$itemId} no existe o está inactivo.");
                }
                $item['cantidad_solicitada'] = $cantidad;
                $items[] = $item;
            }

            $grupo = $this->crearGuid();
            $creadas = [];
            $consumo = array_values(array_filter($items, static function ($item) {
                return $item['tipo_bien'] !== 'AF';
            }));
            if ($consumo) {
                $creadas[] = $this->insertarNota($datos, 'CC', $grupo, $consumo);
            }

            foreach ($items as $item) {
                if ($item['tipo_bien'] !== 'AF') {
                    continue;
                }
                $unidades = $this->buscarUnidadesActivoFijo($item, (int)$item['cantidad_solicitada']);
                for ($unidad = 0; $unidad < (int)$item['cantidad_solicitada']; $unidad++) {
                    // Si aún no existe una unidad individual suficiente, la nota conserva
                    // el bien solicitado como pendiente hasta que Bodega tenga disponibilidad.
                    $detalleUnitario = $unidades[$unidad] ?? $item;
                    $detalleUnitario['cantidad_solicitada'] = 1;
                    $creadas[] = $this->insertarNota($datos, 'AF', $grupo, [$detalleUnitario]);
                }
            }

            (new InvBitacora())->registrar(
                'CREAR',
                'bod',
                'Solicitud digital registrada. Notas generadas: ' . implode(', ', array_column($creadas, 'secuencial'))
            );
            $this->db->commit();
            return $creadas;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    public function marcarSinExistencias(int $notaId, string $usuario): void
    {
        $nota = $this->buscarPorId($notaId);
        if (!$nota) {
            throw new RuntimeException('La nota de pedido no existe.');
        }
        if (in_array($nota['estado'], ['ATENDIDA', 'CERRADA', 'CANCELADA'], true)) {
            throw new RuntimeException('La nota ya no admite revisión.');
        }
        foreach ($nota['detalles'] as $detalle) {
            if ((int)$detalle['cantidad_pendiente'] > 0 && (int)$detalle['stock_actual'] > 0) {
                throw new RuntimeException('La nota tiene productos disponibles; registre la entrega o ajuste las cantidades.');
            }
        }
        $stmt = $this->db->prepare(
            "UPDATE inv_notas_pedido
             SET estado = 'SIN_EXISTENCIAS', fecha_actualizacion = SYSDATETIME()
             WHERE id_nota = :id"
        );
        $stmt->execute([':id' => $notaId]);
        (new InvBitacora())->registrar('EDITAR', 'bod', "Nota {$nota['secuencial']} revisada sin existencias por {$usuario}.");
    }

    private function insertarNota(array $datos, string $tipoBien, string $grupo, array $detalles): array
    {
        $secuencial = (new InvSecuencial())->generarSiguiente('npe');
        $stmt = $this->db->prepare(
            "INSERT INTO inv_notas_pedido
                (secuencial, centro_consumo_id, solicitante_id, fecha_solicitud, motivo,
                 observaciones, tipo_bien, estado, grupo_solicitud, creado_por)
             VALUES
                (:secuencial, :centro, :solicitante, :fecha, :motivo,
                 :observaciones, :tipo_bien, 'ENVIADA', :grupo, :creado_por)"
        );
        $stmt->execute([
            ':secuencial' => $secuencial,
            ':centro' => (int)$datos['centro_consumo_id'],
            ':solicitante' => (int)$datos['solicitante_id'],
            ':fecha' => $datos['fecha_solicitud'] ?? date('Y-m-d'),
            ':motivo' => trim($datos['motivo']),
            ':observaciones' => trim($datos['observaciones'] ?? ''),
            ':tipo_bien' => $tipoBien,
            ':grupo' => $grupo,
            ':creado_por' => $datos['creado_por'] ?? 'Sistema',
        ]);
        $notaId = (int)$this->db->lastInsertId();

        $insertarDetalle = $this->db->prepare(
            "INSERT INTO inv_notas_pedido_detalles (nota_id, item_id, cantidad_solicitada, cantidad_entregada)
             VALUES (:nota, :item, :cantidad, 0)"
        );
        foreach ($detalles as $detalle) {
            $insertarDetalle->execute([
                ':nota' => $notaId,
                ':item' => (int)$detalle['id'],
                ':cantidad' => (int)$detalle['cantidad_solicitada'],
            ]);
        }
        return ['id_nota' => $notaId, 'secuencial' => $secuencial, 'tipo_bien' => $tipoBien];
    }

    private function buscarUnidadesActivoFijo(array $item, int $cantidad): array
    {
        $cantidad = max(1, $cantidad);
        $limiteInicio = DB_DRIVER === 'sqlsrv' ? 'TOP ' . $cantidad . ' ' : '';
        $limiteFin = DB_DRIVER === 'sqlsrv' ? '' : ' LIMIT ' . $cantidad;
        $stmt = $this->db->prepare(
            "SELECT {$limiteInicio}i.id, i.nombre, i.categoria_id, 'AF' AS tipo_bien
             FROM inv_inventario i
             WHERE i.activo = 1 AND i.tipo_bien = 'AF' AND i.cantidad > 0
               AND i.categoria_id = :categoria AND LOWER(i.nombre) = LOWER(:nombre)
             ORDER BY CASE WHEN i.id = :preferido THEN 0 ELSE 1 END, i.id
             {$limiteFin}"
        );
        $stmt->execute([
            ':categoria' => (int)$item['categoria_id'],
            ':nombre' => $item['nombre'],
            ':preferido' => (int)$item['id'],
        ]);
        return $stmt->fetchAll();
    }

    private function crearGuid(): string
    {
        $datos = random_bytes(16);
        $datos[6] = chr((ord($datos[6]) & 0x0f) | 0x40);
        $datos[8] = chr((ord($datos[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($datos), 4));
    }
}
