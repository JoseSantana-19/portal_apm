<?php
/**
 * BUSQUEDAGLOBAL.PHP - Modelo de Búsqueda Global Unificada en el Sistema
 * Rastrea coincidencias en InvInventario, Bodega, Maestros, Usuarios y Bitácora
 * Compatible con SQLite, PostgreSQL y SQL Server
 */

require_once ROOT_PATH . 'core/Model.php';

class BusquedaGlobalModel extends Model {

    /**
     * Realiza la búsqueda clásica (para compatibilidad)
     */
    public function buscar(string $termino, array $modulos = []): array {
        return $this->buscarConFiltros($termino, $modulos, 'todos', 50);
    }

    /**
     * Realiza la búsqueda en los módulos seleccionados aplicando filtros avanzados
     */
    public function buscarConFiltros(string $termino, array $modulos = [], string $campo = 'todos', int $limite = 50): array {
        $resultados = [];
        $likeTerm = '%' . $termino . '%';
        $driver = defined('DB_DRIVER') ? DB_DRIVER : 'sqlite';
        $castTexto = function($campoSql) use ($driver) {
            return $driver === 'sqlite'
                ? "CAST($campoSql AS TEXT)"
                : "CAST($campoSql AS VARCHAR(30))";
        };

        if (empty($modulos)) {
            $modulos = ['inventario', 'bodega', 'inv_maestros', 'usuarios', 'inv_bitacora'];
        }

        // Helper para compilar consultas SELECT con TOP (SQL Server) o LIMIT (PostgreSQL / SQLite)
        $compileQuery = function($selectFields, $tableName, $whereClause, $orderBy) use ($driver, $limite) {
            if ($driver === 'sqlsrv') {
                return "SELECT TOP $limite $selectFields FROM $tableName WHERE $whereClause ORDER BY $orderBy";
            } else {
                return "SELECT $selectFields FROM $tableName WHERE $whereClause ORDER BY $orderBy LIMIT $limite";
            }
        };

        // 1. BÚSQUEDA EN INVENTARIO GENERAL
        if (in_array('inventario', $modulos)) {
            $where = "i.activo = 1";
            if ($campo === 'codigo') {
                $where .= " AND ({$castTexto('i.id')} LIKE :q OR i.codigo_clasificacion LIKE :q OR c.codigo LIKE :q)";
            } elseif ($campo === 'nombre') {
                $where .= " AND (i.nombre LIKE :q OR p.nombre LIKE :q OR c.nombre LIKE :q)";
            } elseif ($campo === 'extra') {
                $where .= " AND (i.marca LIKE :q OR i.observaciones LIKE :q)";
            } else {
                $where .= " AND ({$castTexto('i.id')} LIKE :q OR i.codigo_clasificacion LIKE :q OR c.codigo LIKE :q OR i.nombre LIKE :q OR p.nombre LIKE :q OR c.nombre LIKE :q OR i.marca LIKE :q OR i.observaciones LIKE :q)";
            }

            $sql = $compileQuery(
                "i.id, i.secuencial, i.nombre, i.marca, i.observaciones, i.codigo_clasificacion AS producto_codigo",
                "vw_inv_items_clasificados i LEFT JOIN inv_productos p ON i.producto_id = p.id LEFT JOIN inv_categorias c ON i.categoria_id = c.id",
                $where,
                "i.secuencial ASC"
            );
            try {
                $stmt = $this->db->prepare($sql);
                $stmt->execute([':q' => $likeTerm]);
                while ($row = $stmt->fetch()) {
                    $resultados[] = [
                        'modulo' => 'inventario',
                        'modulo_label' => 'Inventario General',
                        'icon' => 'fa-ship',
                        'color' => '#1e40af', // azul
                        'id' => $row['id'],
                        'titulo' => $row['nombre'],
                        'subtitulo' => 'Secuencial: ' . $row['secuencial']
                            . (!empty($row['producto_codigo']) ? ' | Código maestro: ' . $row['producto_codigo'] : '')
                            . ' | Marca: ' . $row['marca'],
                        'detalle' => $row['observaciones'] ?? '',
                        'url' => 'index.php?route=inventario&termino=' . urlencode($row['secuencial'])
                    ];
                }
            } catch (Exception $e) {}
        }

        // 2. BÚSQUEDA EN BODEGA (INGRESOS Y EGRESOS)
        if (in_array('bodega', $modulos)) {
            // Ingresos
            $whereIng = "1=1";
            if ($campo === 'codigo') {
                $whereIng .= " AND ({$castTexto('id')} LIKE :q OR secuencial LIKE :q)";
            } elseif ($campo === 'nombre') {
                $whereIng .= " AND (proveedor LIKE :q)";
            } elseif ($campo === 'extra') {
                $whereIng .= " AND (observaciones LIKE :q)";
            } else {
                $whereIng .= " AND ({$castTexto('id')} LIKE :q OR secuencial LIKE :q OR proveedor LIKE :q OR observaciones LIKE :q)";
            }
            
            $sqlIng = $compileQuery("id, secuencial, proveedor, observaciones", "inv_bod_ingresos", $whereIng, "secuencial ASC");
            try {
                $stmt = $this->db->prepare($sqlIng);
                $stmt->execute([':q' => $likeTerm]);
                while ($row = $stmt->fetch()) {
                    $resultados[] = [
                        'modulo' => 'bodega',
                        'modulo_label' => 'Ingreso a Bodega',
                        'icon' => 'fa-truck-ramp-box',
                        'color' => '#10b981', // verde
                        'id' => $row['id'],
                        'titulo' => $row['secuencial'],
                        'subtitulo' => 'Proveedor: ' . $row['proveedor'],
                        'detalle' => $row['observaciones'] ?? '',
                        'url' => 'index.php?route=ingresos&termino=' . urlencode($row['secuencial'])
                    ];
                }
            } catch (Exception $e) {}

            // Egresos
            $whereEgr = "1=1";
            if ($campo === 'codigo') {
                $whereEgr .= " AND ({$castTexto('id')} LIKE :q OR secuencial LIKE :q)";
            } elseif ($campo === 'nombre') {
                $whereEgr .= " AND (motivo LIKE :q)";
            } elseif ($campo === 'extra') {
                $whereEgr .= " AND (observaciones LIKE :q)";
            } else {
                $whereEgr .= " AND ({$castTexto('id')} LIKE :q OR secuencial LIKE :q OR motivo LIKE :q OR observaciones LIKE :q)";
            }

            $sqlEgr = $compileQuery("id, secuencial, motivo, observaciones", "inv_bod_egresos", $whereEgr, "secuencial ASC");
            try {
                $stmt = $this->db->prepare($sqlEgr);
                $stmt->execute([':q' => $likeTerm]);
                while ($row = $stmt->fetch()) {
                    $resultados[] = [
                        'modulo' => 'bodega',
                        'modulo_label' => 'Egreso de Bodega',
                        'icon' => 'fa-truck-arrow-right',
                        'color' => '#ef4444', // rojo
                        'id' => $row['id'],
                        'titulo' => $row['secuencial'],
                        'subtitulo' => 'Motivo: ' . $row['motivo'],
                        'detalle' => $row['observaciones'] ?? '',
                        'url' => 'index.php?route=egresos&termino=' . urlencode($row['secuencial'])
                    ];
                }
            } catch (Exception $e) {}
        }

        // 3. BÚSQUEDA EN TABLAS MAESTRAS (MAESTROS)
        if (in_array('inv_maestros', $modulos)) {
            require_once ROOT_PATH . 'modules/Control_Bines/models/EstacionModel.php';
            $cabModel = new InvCabecera();
            try {
                // Buscamos todas las coincidencias y luego filtramos en memoria por campo
                $maestrosMatches = $cabModel->buscarGeneral($termino);
                $filteredMatches = [];

                foreach ($maestrosMatches as $match) {
                    $keep = true;
                    if ($campo === 'codigo') {
                        $keep = (!empty($match['codigo']) && strpos(strtolower($match['codigo']), strtolower($termino)) !== false) || ((string)$match['id'] === $termino);
                    } elseif ($campo === 'nombre') {
                        $keep = (!empty($match['nombre']) && strpos(strtolower($match['nombre']), strtolower($termino)) !== false);
                    } elseif ($campo === 'extra') {
                        $keep = (!empty($match['extra']) && strpos(strtolower($match['extra']), strtolower($termino)) !== false);
                    }
                    
                    if ($keep) {
                        $filteredMatches[] = $match;
                    }
                }

                // Aplicar el límite a los resultados filtrados
                $filteredMatches = array_slice($filteredMatches, 0, $limite);

                foreach ($filteredMatches as $match) {
                    $label = $match['tabla_label'];
                    $icon = 'fa-tag';
                    $color = '#64748b';
                    if (strpos($label, 'Producto') !== false) { $icon = 'fa-box'; $color = '#10b981'; }
                    elseif (strpos($label, 'Centro') !== false) { $icon = 'fa-building-flag'; $color = '#ec4899'; }
                    elseif (strpos($label, 'Proveedor') !== false) { $icon = 'fa-truck-field'; $color = '#f59e0b'; }
                    elseif (strpos($label, 'Medida') !== false) { $icon = 'fa-calculator'; $color = '#06b6d4'; }
                    elseif (strpos($label, 'IVA') !== false) { $icon = 'fa-file-invoice-dollar'; $color = '#ef4444'; }
                    elseif (strpos($label, 'Zona') !== false) { $icon = 'fa-map-location-dot'; $color = '#3b82f6'; }
                    elseif (strpos($label, 'Estado') !== false) { $icon = 'fa-circle-info'; $color = '#14b8a6'; }
                    elseif (strpos($label, 'Marca') !== false) { $icon = 'fa-copyright'; $color = '#6366f1'; }
                    elseif (strpos($label, 'Línea') !== false) { $icon = 'fa-ship'; $color = '#0284c7'; }

                    $resultados[] = [
                        'modulo' => 'inv_maestros',
                        'modulo_label' => $match['tabla_label'],
                        'icon' => $icon,
                        'color' => $color,
                        'id' => $match['id'],
                        'titulo' => $match['nombre'],
                        'subtitulo' => 'ID #' . $match['id'] . ($match['codigo'] ? ' | Código: ' . $match['codigo'] : ''),
                        'detalle' => $match['extra'] ?? '',
                        'url' => $match['url']
                    ];
                }
            } catch (Exception $e) {}
        }

        // 4. BÚSQUEDA EN USUARIOS
        if (in_array('usuarios', $modulos)) {
            $whereUsr = "1=1";
            if ($campo === 'codigo') {
                $whereUsr .= " AND ({$castTexto('id')} LIKE :q OR secuencial LIKE :q)";
            } elseif ($campo === 'nombre') {
                $whereUsr .= " AND (nombre LIKE :q OR usuario LIKE :q)";
            } elseif ($campo === 'extra') {
                $whereUsr .= " AND (rol LIKE :q)";
            } else {
                $whereUsr .= " AND ({$castTexto('id')} LIKE :q OR secuencial LIKE :q OR nombre LIKE :q OR usuario LIKE :q OR rol LIKE :q)";
            }

            $sqlUsr = $compileQuery("id, secuencial, nombre, usuario, rol, activo", "inv_usuarios", $whereUsr, "nombre ASC");
            try {
                $stmt = $this->db->prepare($sqlUsr);
                $stmt->execute([':q' => $likeTerm]);
                while ($row = $stmt->fetch()) {
                    $estadoStr = (int)$row['activo'] === 1 ? 'Activo' : 'Inactivo';
                    $resultados[] = [
                        'modulo' => 'usuarios',
                        'modulo_label' => 'Usuarios del Sistema',
                        'icon' => 'fa-user-shield',
                        'color' => '#8b5cf6', // morado
                        'id' => $row['id'],
                        'titulo' => $row['nombre'],
                        'subtitulo' => 'Usuario: ' . $row['usuario'] . ' | Rol: ' . $row['rol'],
                        'detalle' => 'Secuencial: ' . $row['secuencial'] . ' | Estado: ' . $estadoStr,
                        'url' => 'index.php?route=usuarios&highlight=' . $row['id']
                    ];
                }
            } catch (Exception $e) {}
        }

        // 5. BÚSQUEDA EN BITÁCORA DEL SISTEMA
        if (in_array('inv_bitacora', $modulos)) {
            $whereBit = "descripcion NOT LIKE 'Búsqueda global desde Maestros:%'";
            if ($campo === 'codigo') {
                $whereBit .= " AND ({$castTexto('id')} LIKE :q OR secuencial LIKE :q)";
            } elseif ($campo === 'nombre') {
                $whereBit .= " AND (descripcion LIKE :q)";
            } elseif ($campo === 'extra') {
                $whereBit .= " AND (modulo LIKE :q OR tipo LIKE :q)";
            } else {
                $whereBit .= " AND ({$castTexto('id')} LIKE :q OR secuencial LIKE :q OR tipo LIKE :q OR descripcion LIKE :q)";
            }

            $sqlBit = $compileQuery("id, secuencial, tipo, modulo, descripcion, fecha", "inv_bitacora", $whereBit, "fecha DESC");
            try {
                $stmt = $this->db->prepare($sqlBit);
                $stmt->execute([':q' => $likeTerm]);
                while ($row = $stmt->fetch()) {
                    $resultados[] = [
                        'modulo' => 'inv_bitacora',
                        'modulo_label' => 'Bitácora del Sistema',
                        'icon' => 'fa-clock-rotate-left',
                        'color' => '#64748b', // gris azulado
                        'id' => $row['id'],
                        'titulo' => $row['descripcion'],
                        'subtitulo' => 'Secuencial: ' . $row['secuencial'] . ' | Acción: ' . $row['tipo'],
                        'detalle' => 'Módulo: ' . strtoupper($row['modulo']) . ' | Fecha: ' . $row['fecha'],
                        'url' => 'index.php?route=inv_bitacora&termino=' . urlencode($row['secuencial'])
                    ];
                }
            } catch (Exception $e) {}
        }

        // Priorizar coincidencias exactas y respetar el límite como máximo global,
        // no como un límite independiente por cada módulo consultado.
        $terminoNormalizado = mb_strtolower(trim($termino), 'UTF-8');
        foreach ($resultados as $indice => &$resultado) {
            $titulo = mb_strtolower((string)($resultado['titulo'] ?? ''), 'UTF-8');
            $subtitulo = mb_strtolower((string)($resultado['subtitulo'] ?? ''), 'UTF-8');
            $detalle = mb_strtolower((string)($resultado['detalle'] ?? ''), 'UTF-8');
            $patronIdentificador = '/(?:usuario|código maestro|código|secuencial):\s*'
                . preg_quote($terminoNormalizado, '/') . '(?:\s*\||\s*$)/iu';
            $resultado['_orden_original'] = $indice;
            $resultado['_relevancia'] = 5;

            if ((string)($resultado['id'] ?? '') === trim($termino)) {
                $resultado['_relevancia'] = 0;
            } elseif (preg_match($patronIdentificador, $subtitulo . ' ' . $detalle)) {
                $resultado['_relevancia'] = 1;
            } elseif ($titulo === $terminoNormalizado) {
                $resultado['_relevancia'] = 1;
            } elseif (strpos($titulo, $terminoNormalizado) === 0) {
                $resultado['_relevancia'] = 2;
            } elseif (strpos($titulo, $terminoNormalizado) !== false) {
                $resultado['_relevancia'] = 3;
            } elseif (strpos($subtitulo . ' ' . $detalle, $terminoNormalizado) !== false) {
                $resultado['_relevancia'] = 4;
            }
        }
        unset($resultado);

        usort($resultados, function($a, $b) {
            if ($a['_relevancia'] === $b['_relevancia']) {
                return $a['_orden_original'] <=> $b['_orden_original'];
            }
            return $a['_relevancia'] <=> $b['_relevancia'];
        });

        $resultados = array_slice($resultados, 0, $limite);
        foreach ($resultados as &$resultado) {
            unset($resultado['_relevancia'], $resultado['_orden_original']);
        }
        unset($resultado);

        return $resultados;
    }
}

// Compatibilidad con referencias históricas del módulo.
class_alias('BusquedaGlobalModel', 'InvBusquedaGlobal');
