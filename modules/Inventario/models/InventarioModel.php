<?php
/**
 * InventarioModel — Bienes del inventario (inv_inventario).
 * Port nativo sqlsrv (sin PDO) de BinModel.
 */
class InventarioModel extends InvBaseModel
{
    private const BASE_SELECT = "
        SELECT i.*,
               cat.nombre       AS categoria,
               z.nombre         AS zona,
               est.descripcion  AS estado,
               pers.nombre      AS responsable,
               p.aplica_iva     AS producto_aplica_iva,
               p.codigo         AS producto_codigo,
               u.nombre         AS unidad_nombre,
               u.extra          AS unidad_abrev
        FROM inv_inventario i
        JOIN inv_categorias cat ON i.categoria_id = cat.id
        JOIN inv_zonas z        ON i.zona_id = z.id
        JOIN inv_estados est     ON i.estado_id = est.idestado
        LEFT JOIN inv_talento_personal pers ON i.responsable_id = pers.id
        LEFT JOIN inv_productos p ON i.producto_id = p.id
        LEFT JOIN inv_unidades u  ON p.unidad_id = u.id";

    private const ESTADOS_ACTIVOS = ['APROBADO','AUTORIZADO','VIGENTE','VERIFICADO','ATENDIDO','ACEPTADO','CORRECTO','FAVORABLE','REVISADO','OPERATIVO','TODOS','REGISTRADO'];
    private const ESTADOS_PEND    = ['EN TRAMITE','SOLICITADO','PENDIENTE','EN MANTENIMIENTO','EN TRANSITO'];

    private function clasificar(?array $item): ?array
    {
        if (!$item) return $item;
        $desc  = mb_strtoupper($item['estado'] ?? '', 'UTF-8');
        $clase = 'inactive';
        if (in_array($desc, self::ESTADOS_ACTIVOS, true))      $clase = 'active';
        elseif (in_array($desc, self::ESTADOS_PEND, true))     $clase = 'pending';
        elseif ($desc === 'DESPACHADO')                        $clase = 'dispatched';
        $item['estadoClase'] = $clase;
        return $item;
    }

    private function clasificarLista(array $items): array
    {
        return array_map(fn($i) => $this->clasificar($i), $items);
    }

    public function buscarPorId(int $id): ?array
    {
        $sql = self::BASE_SELECT . " WHERE i.id = ?";
        return $this->clasificar($this->fetch($sql, [[$id, SQLSRV_PARAM_IN]]));
    }

    /**
     * Filtra bienes activos. $filtros: categoria, unidad_id, estado, termino, sort_by, sort_dir.
     */
    public function filtrar(array $filtros = [], ?int $limit = null, ?int $offset = null): array
    {
        $sql    = self::BASE_SELECT . " WHERE i.activo = 1";
        $params = [];

        if (!empty($filtros['categoria'])) {
            if (is_numeric($filtros['categoria'])) {
                $sql .= " AND i.categoria_id = ?";
                $params[] = [(int)$filtros['categoria'], SQLSRV_PARAM_IN];
            } else {
                $sql .= " AND cat.nombre = ?";
                $params[] = [$filtros['categoria'], SQLSRV_PARAM_IN];
            }
        }
        if (!empty($filtros['unidad_id'])) {
            $sql .= " AND p.unidad_id = ?";
            $params[] = [(int)$filtros['unidad_id'], SQLSRV_PARAM_IN];
        }
        if (!empty($filtros['estado'])) {
            if (is_numeric($filtros['estado'])) {
                $sql .= " AND i.estado_id = ?";
                $params[] = [(int)$filtros['estado'], SQLSRV_PARAM_IN];
            } else {
                $sql .= " AND est.descripcion = ?";
                $params[] = [$filtros['estado'], SQLSRV_PARAM_IN];
            }
        }
        if (!empty($filtros['termino'])) {
            $like = $this->likeParam($filtros['termino']);
            $sql .= " AND (i.nombre LIKE ? OR i.secuencial LIKE ? OR i.marca LIKE ?)";
            $params[] = [$like, SQLSRV_PARAM_IN];
            $params[] = [$like, SQLSRV_PARAM_IN];
            $params[] = [$like, SQLSRV_PARAM_IN];
        }

        $allowed = [
            'secuencial' => 'i.secuencial', 'nombre' => 'i.nombre', 'categoria' => 'cat.nombre',
            'valor' => 'i.valor', 'total' => 'i.valor', 'estado' => 'est.descripcion',
        ];
        if (!empty($filtros['sort_by']) && isset($allowed[$filtros['sort_by']])) {
            $dir = (!empty($filtros['sort_dir']) && strtoupper($filtros['sort_dir']) === 'ASC') ? 'ASC' : 'DESC';
            $sql .= " ORDER BY " . $allowed[$filtros['sort_by']] . " {$dir}, i.id DESC";
        } else {
            $sql .= " ORDER BY i.fecha_registro DESC, i.id DESC";
        }

        if ($limit !== null && $offset !== null) {
            $sql .= " OFFSET " . (int)$offset . " ROWS FETCH NEXT " . (int)$limit . " ROWS ONLY";
        }

        return $this->clasificarLista($this->fetchAll($sql, $params));
    }

    public function obtenerActivos(): array
    {
        return $this->filtrar([]);
    }

    public function crear(array $d, string $secuencial): array
    {
        $sql = "INSERT INTO inv_inventario
                    (secuencial, nombre, marca, categoria_id, zona_id, estado_id, responsable_id, valor, fecha_registro, observaciones, activo)
                VALUES (?,?,?,?,?,?,?,?,?,?,1)";
        $this->query($sql, [
            [$secuencial,                                   SQLSRV_PARAM_IN],
            [$d['nombre'],                                  SQLSRV_PARAM_IN],
            [$d['marca'],                                   SQLSRV_PARAM_IN],
            [(int)$d['categoria_id'],                       SQLSRV_PARAM_IN],
            [(int)$d['zona_id'],                            SQLSRV_PARAM_IN],
            [(int)$d['estado_id'],                          SQLSRV_PARAM_IN],
            [!empty($d['responsable_id']) ? (int)$d['responsable_id'] : null, SQLSRV_PARAM_IN],
            [(float)$d['valor'],                            SQLSRV_PARAM_IN],
            [!empty($d['fecha_registro']) ? $d['fecha_registro'] : date('Y-m-d'), SQLSRV_PARAM_IN],
            [$d['observaciones'] ?? '',                     SQLSRV_PARAM_IN],
        ]);
        return $this->buscarPorId((int)$this->lastInsertId());
    }

    public function actualizar(int $id, array $d): ?array
    {
        $sql = "UPDATE inv_inventario SET
                    nombre = ?, marca = ?, categoria_id = ?, zona_id = ?, estado_id = ?,
                    responsable_id = ?, valor = ?, observaciones = ?
                WHERE id = ?";
        $this->query($sql, [
            [$d['nombre'],                                  SQLSRV_PARAM_IN],
            [$d['marca'],                                   SQLSRV_PARAM_IN],
            [(int)$d['categoria_id'],                       SQLSRV_PARAM_IN],
            [(int)$d['zona_id'],                            SQLSRV_PARAM_IN],
            [(int)$d['estado_id'],                          SQLSRV_PARAM_IN],
            [!empty($d['responsable_id']) ? (int)$d['responsable_id'] : null, SQLSRV_PARAM_IN],
            [(float)$d['valor'],                            SQLSRV_PARAM_IN],
            [$d['observaciones'] ?? '',                     SQLSRV_PARAM_IN],
            [$id,                                           SQLSRV_PARAM_IN],
        ]);
        return $this->buscarPorId($id);
    }

    public function eliminar(int $id): bool
    {
        return $this->exec("UPDATE inv_inventario SET activo = 0 WHERE id = ?", [[$id, SQLSRV_PARAM_IN]]) > 0;
    }

    public function obtenerEstadisticas(): array
    {
        $items = $this->obtenerActivos();
        $stats = ['total' => count($items), 'porEstado' => [], 'porCategoria' => [], 'porZona' => [], 'valorTotal' => 0.0];
        foreach ($items as $i) {
            $stats['porEstado'][$i['estado']]       = ($stats['porEstado'][$i['estado']] ?? 0) + 1;
            $stats['porCategoria'][$i['categoria']] = ($stats['porCategoria'][$i['categoria']] ?? 0) + 1;
            $stats['porZona'][$i['zona']]           = ($stats['porZona'][$i['zona']] ?? 0) + 1;
            $stats['valorTotal'] += (float)$i['valor'];
        }
        return $stats;
    }

    public function obtenerResumenPorCategoria(string $termino = ''): array
    {
        $sql = "SELECT cat.id AS categoria_id, cat.nombre AS categoria_nombre,
                       COUNT(i.id) AS total_items,
                       COALESCE(SUM(p.existencia_actual), 0) AS total_qty,
                       COALESCE(SUM(i.valor * COALESCE(p.existencia_actual, 1)), 0) AS total_value
                FROM inv_inventario i
                JOIN inv_categorias cat ON i.categoria_id = cat.id
                LEFT JOIN inv_productos p ON i.producto_id = p.id
                WHERE i.activo = 1";
        $params = [];
        if ($termino !== '') {
            $like = $this->likeParam($termino);
            $sql .= " AND (i.nombre LIKE ? OR i.secuencial LIKE ? OR i.marca LIKE ? OR cat.nombre LIKE ?)";
            $params = array_fill(0, 4, [$like, SQLSRV_PARAM_IN]);
        }
        $sql .= " GROUP BY cat.id, cat.nombre ORDER BY cat.nombre ASC";
        return $this->fetchAll($sql, $params);
    }

    /** Personal responsable (inv_talento_personal). */
    public function obtenerPersonal(): array
    {
        return $this->fetchAll("SELECT id, nombre, identificacion FROM inv_talento_personal ORDER BY nombre ASC");
    }

    public function tasaIvaParaProducto(int $aplicaIvaId): float
    {
        $tipos = $this->fetchAll("SELECT id, tasa_iva FROM inv_tipos_iva ORDER BY tasa_iva DESC");
        foreach ($tipos as $t) {
            if ((int)$t['id'] === $aplicaIvaId) return (float)$t['tasa_iva'];
        }
        return 0.0;
    }

    public function exportarCSV(array $filtros, float $tasaIva): string
    {
        $items  = $this->filtrar($filtros);
        $out    = "\xEF\xBB\xBF";
        $out   .= "Secuencial,Nombre,Marca,Categoria,Zona,Responsable,Estado,Valor Base,IVA %,IVA Calculado,Valor Total,Fecha\n";
        foreach ($items as $i) {
            $base  = (float)$i['valor'];
            $iva   = $base * ($tasaIva / 100);
            $total = $base + $iva;
            $out  .= sprintf("%s,\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",%.2f,%.1f,%.2f,%.2f,%s\n",
                $i['secuencial'], str_replace('"', '""', $i['nombre']), str_replace('"', '""', $i['marca']),
                $i['categoria'], $i['zona'], $i['responsable'] ?: 'Sin Responsable', $i['estado'],
                $base, $tasaIva, $iva, $total, $i['fecha_registro']);
        }
        return $out;
    }
}
