<?php
require_once ROOT_PATH . 'core/Model.php';
require_once ROOT_PATH . 'modules/Central/models/InvSecuencial.php';
require_once ROOT_PATH . 'modules/Central/models/InvPeriodo.php';


class BinModel extends Model {
    // Conexión PDO heredada en $this->db


    private function normalizarResultado($item) {
        if (!$item) return $item;
        
        $desc = isset($item['estado']) ? mb_strtoupper($item['estado'], 'UTF-8') : '';
        $clase = 'inactive';
        if (in_array($desc, ['APROBADO', 'AUTORIZADO', 'VIGENTE', 'VERIFICADO', 'ATENDIDO', 'ACEPTADO', 'CORRECTO', 'FAVORABLE', 'REVISADO', 'OPERATIVO', 'TODOS', 'REGISTRADO'])) {
            $clase = 'active';
        } elseif (in_array($desc, ['EN TRAMITE', 'SOLICITADO', 'PENDIENTE', 'EN MANTENIMIENTO', 'EN TRANSITO'])) {
            $clase = 'pending';
        } elseif ($desc === 'DESPACHADO') {
            $clase = 'dispatched';
        }
        
        $item['estadoClase'] = $clase;
        return $item;
    }

    private function normalizarResultados($items) {
        if (!is_array($items)) return $items;
        foreach ($items as &$item) {
            $item = $this->normalizarResultado($item);
        }
        return $items;
    }

    public function obtenerTodos() {
        $sql = "SELECT i.*, 
                       cat.nombre as categoria, 
                       z.nombre as zona, 
                       est.descripcion as estado, 
                       pers.nombre as responsable,
                       p.aplica_iva as producto_aplica_iva,
                       p.codigo as producto_codigo,
                       COALESCE(i.tipo_bien, p.tipo_bien, 'CC') as tipo_bien
                FROM inv_inventario i
                JOIN inv_categorias cat ON i.categoria_id = cat.id
                JOIN inv_zonas z ON i.zona_id = z.id
                JOIN inv_estados est ON i.estado_id = est.idestado
                LEFT JOIN inv_talento_personal pers ON i.responsable_id = pers.id
                LEFT JOIN inv_productos p ON i.producto_id = p.id
                ORDER BY i.fecha_registro DESC, i.id DESC";
        $stmt = $this->db->query($sql);
        return $this->normalizarResultados($stmt->fetchAll());
    }

    public function obtenerActivos() {
        $sql = "SELECT i.*, 
                       cat.nombre as categoria, 
                       z.nombre as zona, 
                       est.descripcion as estado, 
                       pers.nombre as responsable,
                       p.aplica_iva as producto_aplica_iva,
                       p.codigo as producto_codigo,
                       COALESCE(i.tipo_bien, p.tipo_bien, 'CC') as tipo_bien
                FROM inv_inventario i
                JOIN inv_categorias cat ON i.categoria_id = cat.id
                JOIN inv_zonas z ON i.zona_id = z.id
                JOIN inv_estados est ON i.estado_id = est.idestado
                LEFT JOIN inv_talento_personal pers ON i.responsable_id = pers.id
                LEFT JOIN inv_productos p ON i.producto_id = p.id
                WHERE i.activo = 1
                ORDER BY i.fecha_registro DESC, i.id DESC";
        $stmt = $this->db->query($sql);
        return $this->normalizarResultados($stmt->fetchAll());
    }

    public function buscarPorId($id) {
        $sql = "SELECT i.*, 
                       cat.nombre as categoria, 
                       z.nombre as zona, 
                       est.descripcion as estado, 
                       pers.nombre as responsable,
                       p.aplica_iva as producto_aplica_iva,
                       p.codigo as producto_codigo,
                       COALESCE(i.tipo_bien, p.tipo_bien, 'CC') as tipo_bien
                FROM inv_inventario i
                JOIN inv_categorias cat ON i.categoria_id = cat.id
                JOIN inv_zonas z ON i.zona_id = z.id
                JOIN inv_estados est ON i.estado_id = est.idestado
                LEFT JOIN inv_talento_personal pers ON i.responsable_id = pers.id
                LEFT JOIN inv_productos p ON i.producto_id = p.id
                WHERE i.id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $this->normalizarResultado($stmt->fetch());
    }

    public function filtrar($filtros = [], $limit = null, $offset = null) {
        $sql = "SELECT i.*, 
                       cat.nombre as categoria, 
                       z.nombre as zona, 
                       est.descripcion as estado, 
                       pers.nombre as responsable,
                       p.aplica_iva as producto_aplica_iva,
                       p.codigo as producto_codigo,
                       COALESCE(i.tipo_bien, p.tipo_bien, 'CC') as tipo_bien,
                       u.nombre as unidad_nombre,
                       u.extra as unidad_abrev
                FROM inv_inventario i
                JOIN inv_categorias cat ON i.categoria_id = cat.id
                JOIN inv_zonas z ON i.zona_id = z.id
                JOIN inv_estados est ON i.estado_id = est.idestado
                LEFT JOIN inv_talento_personal pers ON i.responsable_id = pers.id
                LEFT JOIN inv_productos p ON i.producto_id = p.id
                LEFT JOIN inv_unidades u ON p.unidad_id = u.id
                WHERE i.activo = 1";
        
        $params = [];

        if (!empty($filtros['categoria'])) {
            // Puede ser ID o Nombre
            if (is_numeric($filtros['categoria'])) {
                $sql .= " AND i.categoria_id = :cat";
                $params[':cat'] = $filtros['categoria'];
            } else {
                $sql .= " AND cat.nombre = :cat";
                $params[':cat'] = $filtros['categoria'];
            }
        }

        if (!empty($filtros['unidad_id'])) {
            $sql .= " AND p.unidad_id = :unidad_id";
            $params[':unidad_id'] = (int)$filtros['unidad_id'];
        }

        if (!empty($filtros['estado'])) {
            if (is_numeric($filtros['estado'])) {
                $sql .= " AND i.estado_id = :estado";
                $params[':estado'] = $filtros['estado'];
            } else {
                $sql .= " AND est.nombre = :estado";
                $params[':estado'] = $filtros['estado'];
            }
        }

        if (!empty($filtros['termino'])) {
            $sql .= " AND (TRANSLATE(LOWER(i.nombre), 'áéíóúüñÁÉÍÓÚÜÑ', 'aeiouunaeiouun') LIKE :term1 
                        OR i.secuencial LIKE :raw_term 
                        OR TRANSLATE(LOWER(i.marca), 'áéíóúüñÁÉÍÓÚÜÑ', 'aeiouunaeiouun') LIKE :term2)";
            $params[':term1'] = '%' . $this->normalizarTexto($filtros['termino']) . '%';
            $params[':term2'] = '%' . $this->normalizarTexto($filtros['termino']) . '%';
            $params[':raw_term'] = '%' . $filtros['termino'] . '%';
        }

        // Ordenamiento dinámico
        $sortField = 'i.fecha_registro';
        $sortDir = 'DESC';
        
        $allowedSorts = [
            'secuencial' => 'i.secuencial',
            'nombre'     => 'i.nombre',
            'categoria'  => 'cat.nombre',
            'unidad'     => 'COALESCE(u.extra, u.nombre)',
            'valor'      => 'i.valor',
            'iva'        => 'p.aplica_iva',
            'total'      => 'i.valor',
            'estado'     => 'est.nombre'
        ];

        if (!empty($filtros['sort_by']) && isset($allowedSorts[$filtros['sort_by']])) {
            $sortField = $allowedSorts[$filtros['sort_by']];
            if (!empty($filtros['sort_dir']) && strtoupper($filtros['sort_dir']) === 'ASC') {
                $sortDir = 'ASC';
            } else {
                $sortDir = 'DESC';
            }
        }

        if ($sortField === 'i.fecha_registro') {
            $sql .= " ORDER BY i.fecha_registro DESC, i.id DESC";
        } else {
            $sql .= " ORDER BY " . $sortField . " " . $sortDir . ", i.id DESC";
        }

        if ($limit !== null && $offset !== null) {
            $driver = defined('DB_DRIVER') ? DB_DRIVER : 'sqlite';
            if ($driver === 'sqlsrv') {
                $sql .= " OFFSET " . (int)$offset . " ROWS FETCH NEXT " . (int)$limit . " ROWS ONLY";
            } else {
                $sql .= " LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
            }
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $this->normalizarResultados($stmt->fetchAll());
    }

    public function crear($datos) {
        $secuencialObj = new InvSecuencial();
        $secuencial = $secuencialObj->generarSiguiente('inv');

        $sql = "INSERT INTO inv_inventario (
                    secuencial, nombre, marca, categoria_id, zona_id, estado_id, 
                    responsable_id, valor, fecha_registro, observaciones, activo
                ) VALUES (
                    :sec, :nombre, :marca, :cat_id, :zona_id, :est_id, 
                    :resp_id, :valor, :fecha, :obs, 1
                )";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':sec' => $secuencial,
            ':nombre' => $datos['nombre'],
            ':marca' => $datos['marca'],
            ':cat_id' => $datos['categoria_id'],
            ':zona_id' => $datos['zona_id'],
            ':est_id' => $datos['estado_id'],
            ':resp_id' => !empty($datos['responsable_id']) ? $datos['responsable_id'] : null,
            ':valor' => (float)$datos['valor'],
            ':fecha' => !empty($datos['fecha_registro']) ? $datos['fecha_registro'] : date('Y-m-d'),
            ':obs' => isset($datos['observaciones']) ? $datos['observaciones'] : ''
        ]);

        return $this->buscarPorId($this->db->lastInsertId());
    }

    public function actualizar($id, $datos) {
        $sql = "UPDATE inv_inventario SET 
                    nombre = :nombre, 
                    marca = :marca, 
                    categoria_id = :cat_id, 
                    zona_id = :zona_id, 
                    estado_id = :est_id, 
                    responsable_id = :resp_id, 
                    valor = :valor,
                    observaciones = :obs 
                WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':nombre' => $datos['nombre'],
            ':marca' => $datos['marca'],
            ':cat_id' => $datos['categoria_id'],
            ':zona_id' => $datos['zona_id'],
            ':est_id' => $datos['estado_id'],
            ':resp_id' => !empty($datos['responsable_id']) ? $datos['responsable_id'] : null,
            ':valor' => (float)$datos['valor'],
            ':obs' => isset($datos['observaciones']) ? $datos['observaciones'] : '',
            ':id' => $id
        ]);

        // Sincronización reversa hacia inv_productos si existe producto_id
        $checkStmt = $this->db->prepare("SELECT producto_id, cantidad FROM inv_inventario WHERE id = :id");
        $checkStmt->execute([':id' => $id]);
        $res = $checkStmt->fetch();
        
        if ($res && !empty($res['producto_id'])) {
            $productId = (int)$res['producto_id'];
            $cantidad = (float)($res['cantidad'] ?? 1);
            
            $syncProdStmt = $this->db->prepare(
                "UPDATE inv_productos SET
                    nombre = :nombre,
                    grupo_id = :grupo_id,
                    precio_promedio = :precio,
                    existencia_actual = :existencia
                 WHERE id = :id"
            );
            $syncProdStmt->execute([
                ':nombre'     => $datos['nombre'],
                ':grupo_id'   => (int)$datos['categoria_id'],
                ':precio'     => (float)$datos['valor'],
                ':existencia' => $cantidad,
                ':id'         => $productId
            ]);
        }

        return $this->buscarPorId($id);
    }


    public function eliminar($id) {
        $stmt = $this->db->prepare("UPDATE inv_inventario SET activo = 0 WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function obtenerEstadisticas() {
        $items = $this->obtenerActivos();
        
        $stats = [
            'total' => count($items),
            'totalConsumoCorriente' => 0,
            'totalActivoFijo' => 0,
            'porEstado' => [],
            'porCategoria' => [],
            'porZona' => []
        ];

        foreach ($items as $i) {
            if (($i['tipo_bien'] ?? 'CC') === 'AF') {
                $stats['totalActivoFijo']++;
            } else {
                $stats['totalConsumoCorriente']++;
            }
            $estado = $i['estado'];
            $categoria = $i['categoria'];
            $zona = $i['zona'];

            $stats['porEstado'][$estado] = isset($stats['porEstado'][$estado]) ? $stats['porEstado'][$estado] + 1 : 1;
            $stats['porCategoria'][$categoria] = isset($stats['porCategoria'][$categoria]) ? $stats['porCategoria'][$categoria] + 1 : 1;
            $stats['porZona'][$zona] = isset($stats['porZona'][$zona]) ? $stats['porZona'][$zona] + 1 : 1;
        }

        return $stats;
    }

    public function exportarCSV($filtros = []) {
        $items = $this->filtrar($filtros);
        
        // Obtener el IVA del período activo para mostrar los cálculos
        $periodoObj = new InvPeriodo();
        $periodoActivo = $periodoObj->obtenerPeriodoActivo();
        $tasaIva = $periodoActivo ? (float)$periodoActivo['tasa_iva'] : 15.0;

        $output = "\xEF\xBB\xBF"; // UTF-8 BOM
        $output .= "Secuencial,Nombre,Marca,Categoria,Zona,Responsable,Estado,Valor Base,IVA (%,),IVA Calculado,Valor Total,Fecha Registro\n";
        
        foreach ($items as $i) {
            $valorBase = (float)$i['valor'];
            $aplicaIva = isset($i['producto_aplica_iva']) && (int)$i['producto_aplica_iva'] === 1;
            $tasaAplicada = $aplicaIva ? $tasaIva : 0.0;
            $ivaCalc = $valorBase * ($tasaAplicada / 100);
            $valorTotal = $valorBase + $ivaCalc;
            
            $output .= sprintf(
                "%s,\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",%.2f,%.1f,%.2f,%.2f,%s\n",
                $i['secuencial'],
                str_replace('"', '""', $i['nombre']),
                str_replace('"', '""', $i['marca']),
                $i['categoria'],
                $i['zona'],
                $i['responsable'] ? $i['responsable'] : 'Sin Responsable',
                $i['estado'],
                $valorBase,
                $tasaAplicada,
                $ivaCalc,
                $valorTotal,
                $i['fecha_registro']
            );
        }
        return $output;
    }

    public function obtenerResumenPorCategoria($termino = '') {
        $sql = "SELECT cat.id as categoria_id, cat.nombre as categoria_nombre,
                       COUNT(i.id) as total_items,
                       COALESCE(SUM(p.existencia_actual), 0) as total_qty,
                       COALESCE(SUM(i.valor * COALESCE(p.existencia_actual, 1)), 0) as total_value
                FROM inv_inventario i
                JOIN inv_categorias cat ON i.categoria_id = cat.id
                LEFT JOIN inv_productos p ON i.producto_id = p.id
                WHERE i.activo = 1";
        
        $params = [];
        if (!empty($termino)) {
            $sql .= " AND (TRANSLATE(LOWER(i.nombre), 'áéíóúüñÁÉÍÓÚÜÑ', 'aeiouunaeiouun') LIKE :term1 
                        OR i.secuencial LIKE :raw_term 
                        OR TRANSLATE(LOWER(i.marca), 'áéíóúüñÁÉÍÓÚÜÑ', 'aeiouunaeiouun') LIKE :term2
                        OR TRANSLATE(LOWER(cat.nombre), 'áéíóúüñÁÉÍÓÚÜÑ', 'aeiouunaeiouun') LIKE :term3)";
            $params[':term1'] = '%' . $this->normalizarTexto($termino) . '%';
            $params[':term2'] = '%' . $this->normalizarTexto($termino) . '%';
            $params[':term3'] = '%' . $this->normalizarTexto($termino) . '%';
            $params[':raw_term'] = '%' . $termino . '%';
        }
        
        $sql .= " GROUP BY cat.id, cat.nombre ORDER BY cat.nombre ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    private function normalizarTexto($str) {
        $str = mb_strtolower($str, 'UTF-8');
        $replaces = [
            'á'=>'a', 'é'=>'e', 'í'=>'i', 'ó'=>'o', 'ú'=>'u', 'ü'=>'u',
            'Á'=>'a', 'É'=>'e', 'Í'=>'i', 'Ó'=>'o', 'Ú'=>'u', 'Ü'=>'u',
            'ñ'=>'n', 'Ñ'=>'n'
        ];
        return strtr($str, $replaces);
    }
}

// Clase de compatibilidad hacia atrás
class InvInventario extends BinModel {}

