<?php
require_once ROOT_PATH . 'core/Model.php';
require_once ROOT_PATH . 'modules/Central/models/InvSecuencial.php';

class BitacoraModel extends Model {
    // Conexión PDO heredada en $this->db


    /**
     * Registra un evento en la bitácora
     * @param string $tipo (CREAR, ACTUALIZAR, ELIMINAR, ACCESO, CONSULTA, EXPORTAR, CIERRE)
     * @param string $modulo (inv, th, bit, acc, seq, per)
     * @param string $descripcion Detalle comprensible del evento
     */
    public function registrar($tipo, $modulo, $descripcion) {
        try {
            $secuencialObj = new InvSecuencial();
            $secuencial = $secuencialObj->generarSiguiente('bit');

            // CURRENT_TIMESTAMP es estándar SQL y compatible con SQLite, PostgreSQL y SQL Server
            $stmt = $this->db->prepare("INSERT INTO inv_bitacora (secuencial, tipo, modulo, descripcion, fecha) 
                                        VALUES (:sec, :tipo, :mod, :desc, CURRENT_TIMESTAMP)");
            $stmt->execute([
                ':sec' => $secuencial,
                ':tipo' => strtoupper($tipo),
                ':mod' => $modulo,
                ':desc' => $descripcion
            ]);
            return true;
        } catch (Exception $e) {
            // Failsafe para evitar interrumpir flujos de usuario si la bitácora falla
            error_log("Error al escribir en bitácora: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene registros de la bitácora con opción de filtrado
     */
    public function filtrar($filtros = []) {
        $driver = defined('DB_DRIVER') ? DB_DRIVER : 'sqlite';
        
        if ($driver === 'sqlsrv') {
            $sql = "SELECT TOP 500 * FROM inv_bitacora WHERE 1=1";
        } else {
            $sql = "SELECT * FROM inv_bitacora WHERE 1=1";
        }
        
        $params = [];

        if (!empty($filtros['modulo'])) {
            $sql .= " AND modulo = :modulo";
            $params[':modulo'] = $filtros['modulo'];
        }

        if (!empty($filtros['tipo'])) {
            $sql .= " AND tipo = :tipo";
            $params[':tipo'] = strtoupper($filtros['tipo']);
        }

        if (!empty($filtros['termino'])) {
            $sql .= " AND (descripcion LIKE :term OR secuencial LIKE :term)";
            $params[':term'] = '%' . $filtros['termino'] . '%';
        }

        $sql .= " ORDER BY fecha DESC";
        
        if ($driver !== 'sqlsrv') {
            $sql .= " LIMIT 500";
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Obtiene estadísticas de la bitácora agrupadas por tipo de acción
     */
    public function obtenerEstadisticas() {
        $stats = [
            'total' => 0,
            'CREAR' => 0,
            'ACTUALIZAR' => 0,
            'ELIMINAR' => 0,
            'OTROS' => 0
        ];

        $stmt = $this->db->query("SELECT tipo, COUNT(*) as cantidad FROM inv_bitacora GROUP BY tipo");
        $rows = $stmt->fetchAll();

        foreach ($rows as $row) {
            $stats['total'] += $row['cantidad'];
            $tipo = strtoupper($row['tipo']);
            if (array_key_exists($tipo, $stats)) {
                $stats[$tipo] = $row['cantidad'];
            } else {
                $stats['OTROS'] += $row['cantidad'];
            }
        }

        return $stats;
    }

    /**
     * Exporta la bitácora filtrada a un string en formato CSV
     */
    public function exportarCSV($filtros = []) {
        $items = $this->filtrar($filtros);
        $output = "Secuencial,Fecha,Módulo,Acción,Descripción\n";
        
        foreach ($items as $i) {
            // Escapar comillas para CSV
            $desc = str_replace('"', '""', $i['descripcion']);
            $output .= sprintf(
                "%s,%s,%s,%s,\"%s\"\n",
                $i['secuencial'],
                $i['fecha'],
                strtoupper($i['modulo']),
                $i['tipo'],
                $desc
            );
        }
        return $output;
    }
}

// Clase de compatibilidad hacia atrás
class InvBitacora extends BitacoraModel {}

