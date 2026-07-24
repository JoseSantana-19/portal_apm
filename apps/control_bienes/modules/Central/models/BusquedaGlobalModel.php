<?php
/**
 * BUSQUEDAGLOBAL.PHP - Modelo de Búsqueda Global Unificada en el Sistema
 * Rastrea coincidencias en InvInventario, Bodega, Maestros, Usuarios y Bitácora
 * Compatible con SQLite, PostgreSQL y SQL Server
 */

require_once ROOT_PATH . 'core/Model.php';

class InvBusquedaGlobal extends Model {

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
            $where = "activo = 1";
            if ($campo === 'codigo') {
                $where .= " AND (secuencial LIKE :q)";
            } elseif ($campo === 'nombre') {
                $where .= " AND (nombre LIKE :q)";
            } elseif ($campo === 'extra') {
                $where .= " AND (marca LIKE :q OR observaciones LIKE :q)";
            } else {
                $where .= " AND (secuencial LIKE :q OR nombre LIKE :q OR marca LIKE :q OR observaciones LIKE :q)";
            }

            $sql = $compileQuery("id, secuencial, nombre, marca, observaciones", "inv_inventario", $where, "secuencial ASC");
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
                        'subtitulo' => 'Secuencial: ' . $row['secuencial'] . ' | Marca: ' . $row['marca'],
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
                $whereIng .= " AND (secuencial LIKE :q)";
            } elseif ($campo === 'nombre') {
                $whereIng .= " AND (proveedor LIKE :q)";
            } elseif ($campo === 'extra') {
                $whereIng .= " AND (observaciones LIKE :q)";
            } else {
                $whereIng .= " AND (secuencial LIKE :q OR proveedor LIKE :q OR observaciones LIKE :q)";
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
                $whereEgr .= " AND (secuencial LIKE :q)";
            } elseif ($campo === 'nombre') {
                $whereEgr .= " AND (motivo LIKE :q)";
            } elseif ($campo === 'extra') {
                $whereEgr .= " AND (observaciones LIKE :q)";
            } else {
                $whereEgr .= " AND (secuencial LIKE :q OR motivo LIKE :q OR observaciones LIKE :q)";
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
                $whereUsr .= " AND (secuencial LIKE :q)";
            } elseif ($campo === 'nombre') {
                $whereUsr .= " AND (nombre LIKE :q OR usuario LIKE :q)";
            } elseif ($campo === 'extra') {
                $whereUsr .= " AND (rol LIKE :q)";
            } else {
                $whereUsr .= " AND (nombre LIKE :q OR usuario LIKE :q OR rol LIKE :q)";
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
            $whereBit = "1=1";
            if ($campo === 'codigo') {
                $whereBit .= " AND (secuencial LIKE :q)";
            } elseif ($campo === 'nombre') {
                $whereBit .= " AND (descripcion LIKE :q)";
            } elseif ($campo === 'extra') {
                $whereBit .= " AND (modulo LIKE :q OR tipo LIKE :q)";
            } else {
                $whereBit .= " AND (secuencial LIKE :q OR tipo LIKE :q OR descripcion LIKE :q)";
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

        return $resultados;
    }
}
