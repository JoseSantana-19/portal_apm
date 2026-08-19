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

    private function aplicarSegmentoOperativo(string &$sql, array $filtros): void {
        $segmento = trim((string)($filtros['segmento'] ?? ''));
        if ($segmento === 'sin_stock') {
            $sql .= " AND COALESCE(i.tipo_bien,p.tipo_bien,'CC')<>'AF' AND COALESCE(i.cantidad,0)<=0";
        } elseif ($segmento === 'stock_bajo') {
            $sql .= " AND COALESCE(i.tipo_bien,p.tipo_bien,'CC')<>'AF' AND COALESCE(i.cantidad,0)>0 AND COALESCE(p.existencia_min,0)>0 AND i.cantidad<=p.existencia_min";
        } elseif ($segmento === 'mantenimiento') {
            $sql .= " AND UPPER(est.descripcion) LIKE '%MANTENIMIENTO%'";
        } elseif ($segmento === 'sin_responsable') {
            $sql .= " AND COALESCE(i.tipo_bien,p.tipo_bien,'CC')='AF' AND i.responsable_id IS NULL";
        } elseif ($segmento === 'consumo') {
            $sql .= " AND COALESCE(i.tipo_bien,p.tipo_bien,'CC')<>'AF'";
        } elseif ($segmento === 'activo_fijo') {
            $sql .= " AND COALESCE(i.tipo_bien,p.tipo_bien,'CC')='AF'";
        }
    }

    public function obtenerTodos() {
        $sql = "SELECT i.*, 
                       cat.nombre as categoria, 
                       z.nombre as zona, 
                       est.descripcion as estado, 
                       pers.nombre as responsable,
                       p.aplica_iva as producto_aplica_iva,
                       i.codigo_clasificacion as producto_codigo,
                       COALESCE(i.tipo_bien, p.tipo_bien, 'CC') as tipo_bien
                FROM vw_inv_items_clasificados i
                JOIN inv_categorias cat ON i.categoria_id = cat.id
                JOIN inv_zonas z ON i.zona_id = z.id
                JOIN inv_estados est ON i.estado_id = est.idestado
                LEFT JOIN vw_inv_talento_personal pers ON i.responsable_id = pers.id
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
                       i.codigo_clasificacion as producto_codigo,
                       COALESCE(i.tipo_bien, p.tipo_bien, 'CC') as tipo_bien
                FROM vw_inv_items_clasificados i
                JOIN inv_categorias cat ON i.categoria_id = cat.id
                JOIN inv_zonas z ON i.zona_id = z.id
                JOIN inv_estados est ON i.estado_id = est.idestado
                LEFT JOIN vw_inv_talento_personal pers ON i.responsable_id = pers.id
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
                       i.codigo_clasificacion as producto_codigo,
                       COALESCE(i.tipo_bien, p.tipo_bien, 'CC') as tipo_bien
                FROM vw_inv_items_clasificados i
                JOIN inv_categorias cat ON i.categoria_id = cat.id
                JOIN inv_zonas z ON i.zona_id = z.id
                JOIN inv_estados est ON i.estado_id = est.idestado
                LEFT JOIN vw_inv_talento_personal pers ON i.responsable_id = pers.id
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
                       i.codigo_clasificacion as producto_codigo,
                       COALESCE(i.tipo_bien, p.tipo_bien, 'CC') as tipo_bien,
                       u.nombre as unidad_nombre,
                       u.extra as unidad_abrev
                FROM vw_inv_items_clasificados i
                JOIN inv_categorias cat ON i.categoria_id = cat.id
                JOIN inv_zonas z ON i.zona_id = z.id
                JOIN inv_estados est ON i.estado_id = est.idestado
                LEFT JOIN vw_inv_talento_personal pers ON i.responsable_id = pers.id
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

        $this->aplicarSegmentoOperativo($sql, $filtros);

        if (!empty($filtros['termino'])) {
            $sql .= " AND (i.secuencial LIKE :prefix1
                        OR i.codigo_clasificacion LIKE :prefix2
                        OR cat.codigo LIKE :prefix3
                        OR i.nombre COLLATE Modern_Spanish_CI_AI LIKE :term1
                        OR i.marca COLLATE Modern_Spanish_CI_AI LIKE :term2
                        OR cat.nombre COLLATE Modern_Spanish_CI_AI LIKE :term3
                        OR p.nombre COLLATE Modern_Spanish_CI_AI LIKE :term4)";
            $contiene = '%' . trim($filtros['termino']) . '%';
            $prefijo = trim($filtros['termino']) . '%';
            foreach (range(1, 4) as $indice) $params[':term' . $indice] = $contiene;
            foreach (range(1, 3) as $indice) $params[':prefix' . $indice] = $prefijo;
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
            'estado'     => 'est.descripcion'
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

    /** Conteo liviano para la paginación de DataTables, sin materializar miles de filas. */
    public function contarFiltrados(array $filtros = []): int {
        $sql = "SELECT COUNT(*)
                FROM vw_inv_items_clasificados i
                JOIN inv_categorias cat ON i.categoria_id = cat.id
                JOIN inv_estados est ON i.estado_id = est.idestado
                LEFT JOIN inv_productos p ON i.producto_id = p.id
                WHERE i.activo = 1";
        $params = [];

        if (!empty($filtros['categoria'])) {
            if (is_numeric($filtros['categoria'])) {
                $sql .= " AND i.categoria_id = :cat";
            } else {
                $sql .= " AND cat.nombre = :cat";
            }
            $params[':cat'] = $filtros['categoria'];
        }
        if (!empty($filtros['unidad_id'])) {
            $sql .= " AND p.unidad_id = :unidad_id";
            $params[':unidad_id'] = (int)$filtros['unidad_id'];
        }
        if (!empty($filtros['estado'])) {
            if (is_numeric($filtros['estado'])) {
                $sql .= " AND i.estado_id = :estado";
            } else {
                $sql .= " AND est.descripcion = :estado";
            }
            $params[':estado'] = $filtros['estado'];
        }
        $this->aplicarSegmentoOperativo($sql, $filtros);
        if (!empty($filtros['termino'])) {
            $sql .= " AND (i.secuencial LIKE :prefix1
                        OR i.codigo_clasificacion LIKE :prefix2
                        OR cat.codigo LIKE :prefix3
                        OR i.nombre COLLATE Modern_Spanish_CI_AI LIKE :term1
                        OR i.marca COLLATE Modern_Spanish_CI_AI LIKE :term2
                        OR cat.nombre COLLATE Modern_Spanish_CI_AI LIKE :term3
                        OR p.nombre COLLATE Modern_Spanish_CI_AI LIKE :term4)";
            $contiene = '%' . trim($filtros['termino']) . '%';
            $prefijo = trim($filtros['termino']) . '%';
            foreach (range(1, 4) as $indice) $params[':term' . $indice] = $contiene;
            foreach (range(1, 3) as $indice) $params[':prefix' . $indice] = $prefijo;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
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
        $sqlResumen = "SELECT COUNT(*) total,
                              SUM(CASE WHEN COALESCE(i.tipo_bien,p.tipo_bien,'CC')='AF' THEN 1 ELSE 0 END) total_activo_fijo,
                              SUM(CASE WHEN COALESCE(i.tipo_bien,p.tipo_bien,'CC')<>'AF' THEN 1 ELSE 0 END) total_consumo,
                              SUM(COALESCE(i.cantidad,0)) total_unidades,
                              SUM(COALESCE(i.valor,0)*COALESCE(i.cantidad,0)) valor_inventariado
                       FROM vw_inv_items_clasificados i
                       LEFT JOIN inv_productos p ON p.id=i.producto_id
                       WHERE i.activo=1";
        $resumen = $this->db->query($sqlResumen)->fetch() ?: [];
        $stats = [
            'total' => (int)($resumen['total'] ?? 0),
            'totalConsumoCorriente' => (int)($resumen['total_consumo'] ?? 0),
            'totalActivoFijo' => (int)($resumen['total_activo_fijo'] ?? 0),
            'totalUnidades' => (float)($resumen['total_unidades'] ?? 0),
            'valorInventariado' => (float)($resumen['valor_inventariado'] ?? 0),
            'porEstado' => [], 'porCategoria' => [], 'porZona' => []
        ];

        $consultas = [
            'porEstado' => "SELECT est.descripcion etiqueta, COUNT(*) total FROM vw_inv_items_clasificados i JOIN inv_estados est ON est.idestado=i.estado_id WHERE i.activo=1 GROUP BY est.descripcion ORDER BY total DESC",
            'porCategoria' => "SELECT cat.nombre etiqueta, COUNT(*) total FROM vw_inv_items_clasificados i JOIN inv_categorias cat ON cat.id=i.categoria_id WHERE i.activo=1 GROUP BY cat.nombre ORDER BY total DESC",
            'porZona' => "SELECT z.nombre etiqueta, COUNT(*) total FROM vw_inv_items_clasificados i JOIN inv_zonas z ON z.id=i.zona_id WHERE i.activo=1 GROUP BY z.nombre ORDER BY total DESC"
        ];
        foreach ($consultas as $clave => $sql) {
            foreach ($this->db->query($sql)->fetchAll() as $fila) {
                $stats[$clave][(string)$fila['etiqueta']] = (int)$fila['total'];
            }
        }
        return $stats;
    }

    public function obtenerResumenOperativo(): array {
        $sql = "SELECT
                    SUM(CASE WHEN COALESCE(i.tipo_bien,p.tipo_bien,'CC')<>'AF' AND COALESCE(i.cantidad,0)<=0 THEN 1 ELSE 0 END) sin_stock,
                    SUM(CASE WHEN COALESCE(i.tipo_bien,p.tipo_bien,'CC')<>'AF' AND COALESCE(i.cantidad,0)>0 AND COALESCE(p.existencia_min,0)>0 AND i.cantidad<=p.existencia_min THEN 1 ELSE 0 END) stock_bajo,
                    SUM(CASE WHEN UPPER(est.descripcion) LIKE '%MANTENIMIENTO%' THEN 1 ELSE 0 END) mantenimiento,
                    SUM(CASE WHEN COALESCE(i.tipo_bien,p.tipo_bien,'CC')='AF' AND i.responsable_id IS NULL THEN 1 ELSE 0 END) sin_responsable
                FROM vw_inv_items_clasificados i
                LEFT JOIN inv_productos p ON p.id=i.producto_id
                JOIN inv_estados est ON est.idestado=i.estado_id
                WHERE i.activo=1";
        $resumen = $this->db->query($sql)->fetch() ?: [];
        $resultado = [
            'sin_stock' => (int)($resumen['sin_stock'] ?? 0),
            'stock_bajo' => (int)($resumen['stock_bajo'] ?? 0),
            'mantenimiento' => (int)($resumen['mantenimiento'] ?? 0),
            'sin_responsable' => (int)($resumen['sin_responsable'] ?? 0),
            'movimientos' => [],
        ];
        try {
            $driver = defined('DB_DRIVER') ? DB_DRIVER : 'sqlite';
            $limite = $driver === 'sqlsrv' ? 'TOP 6 ' : '';
            $sqlMovimientos = "SELECT {$limite}k.fecha_movimiento, k.tipo_movimiento, k.documento_secuencial,
                                      k.entrada, k.salida, k.saldo_resultante, i.secuencial item_codigo, i.nombre item_nombre
                               FROM inv_kardex k JOIN inv_inventario i ON i.id=k.item_id
                               ORDER BY k.fecha_movimiento DESC, k.id_movimiento DESC";
            if ($driver !== 'sqlsrv') $sqlMovimientos .= ' LIMIT 6';
            $resultado['movimientos'] = $this->db->query($sqlMovimientos)->fetchAll();
        } catch (Exception $e) {
            $resultado['movimientos'] = [];
        }
        return $resultado;
    }

    /**
     * Cuenta las opciones principales abiertas por el usuario en los ultimos
     * 90 dias para personalizar sus accesos rapidos.
     */
    public function obtenerRutasFrecuentesUsuario(int $usuarioId): array {
        if ($usuarioId <= 0) return [];

        $rutas = [
            'items', 'inv_items_sistema', 'requisiciones', 'ordenes_compra',
            'ingresos', 'egresos', 'inv_maestros', 'reportes',
            'busqueda_global', 'talento_directorio'
        ];
        $marcadores = [];
        $params = [
            ':usuario_id' => $usuarioId,
            ':desde' => date('Y-m-d H:i:s', strtotime('-90 days')),
        ];
        foreach ($rutas as $indice => $ruta) {
            $clave = ':ruta_' . $indice;
            $marcadores[] = $clave;
            $params[$clave] = $ruta;
        }

        try {
            $sql = "SELECT ruta, COUNT(*) AS usos
                    FROM inv_bitacora
                    WHERE usuario_id = :usuario_id
                      AND fecha >= :desde
                      AND tipo = 'ACCESO'
                      AND (resultado = 'OK' OR resultado IS NULL)
                      AND ruta IN (" . implode(',', $marcadores) . ")
                    GROUP BY ruta";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $resultado = [];
            foreach ($stmt->fetchAll() as $fila) {
                $ruta = (string)($fila['ruta'] ?? '');
                if ($ruta !== '') $resultado[$ruta] = (int)($fila['usos'] ?? 0);
            }
            arsort($resultado, SORT_NUMERIC);
            return $resultado;
        } catch (Throwable $e) {
            error_log('No se pudieron calcular las acciones frecuentes: ' . $e->getMessage());
            return [];
        }
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
        $sql = "SELECT cat.id as categoria_id, cat.nombre as categoria_nombre, cat.codigo as categoria_codigo,
                       COUNT(i.id) as total_items,
                       COALESCE(SUM(p.existencia_actual), 0) as total_qty,
                       COALESCE(SUM(i.valor * COALESCE(p.existencia_actual, 1)), 0) as total_value
                FROM vw_inv_items_clasificados i
                JOIN inv_categorias cat ON i.categoria_id = cat.id
                LEFT JOIN inv_productos p ON i.producto_id = p.id
                WHERE i.activo = 1";
        
        $params = [];
        if (!empty($termino)) {
            $sql .= " AND (TRANSLATE(LOWER(i.nombre), 'áéíóúüñÁÉÍÓÚÜÑ', 'aeiouunaeiouun') LIKE :term1 
                        OR i.secuencial LIKE :raw_term
                        OR i.codigo_clasificacion LIKE :raw_term
                        OR cat.codigo LIKE :raw_term
                        OR TRANSLATE(LOWER(i.marca), 'áéíóúüñÁÉÍÓÚÜÑ', 'aeiouunaeiouun') LIKE :term2
                        OR TRANSLATE(LOWER(cat.nombre), 'áéíóúüñÁÉÍÓÚÜÑ', 'aeiouunaeiouun') LIKE :term3
                        OR TRANSLATE(LOWER(p.nombre), 'áéíóúüñÁÉÍÓÚÜÑ', 'aeiouunaeiouun') LIKE :term4)";
            $params[':term1'] = '%' . $this->normalizarTexto($termino) . '%';
            $params[':term2'] = '%' . $this->normalizarTexto($termino) . '%';
            $params[':term3'] = '%' . $this->normalizarTexto($termino) . '%';
            $params[':term4'] = '%' . $this->normalizarTexto($termino) . '%';
            $params[':raw_term'] = '%' . $termino . '%';
        }
        
        $sql .= " GROUP BY cat.id, cat.nombre, cat.codigo ORDER BY cat.nombre ASC";
        
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

