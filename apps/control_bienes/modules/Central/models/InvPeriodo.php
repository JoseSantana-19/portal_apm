<?php
/**
 * PERIODO.PHP - Modelo de Períodos, IVA y Respaldos (Cortes Históricos)
 * Compatible con PHP 7.4, 8.3 y 8.4
 */

require_once ROOT_PATH . 'core/Model.php';
require_once ROOT_PATH . 'modules/Talento_Humano/models/EmpleadoModel.php';

class PeriodoModel extends Model {

    public function obtenerTodos() {
        $stmt = $this->db->query("SELECT p.*, v.tasa_iva 
                                  FROM inv_periodos p 
                                  LEFT JOIN inv_valores_iva v ON p.id = v.periodo_id
                                  ORDER BY p.fecha_inicio DESC");
        return $stmt->fetchAll();
    }

    public function buscarPorId($id) {
        $stmt = $this->db->prepare("SELECT p.*, v.tasa_iva 
                                    FROM inv_periodos p 
                                    LEFT JOIN inv_valores_iva v ON p.id = v.periodo_id 
                                    WHERE p.id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    public function obtenerPeriodoActivo() {
        $driver = defined('DB_DRIVER') ? DB_DRIVER : 'sqlite';
        $sql = ($driver === 'sqlsrv') ? 
               "SELECT TOP 1 p.*, v.tasa_iva 
                FROM inv_periodos p 
                LEFT JOIN inv_valores_iva v ON p.id = v.periodo_id 
                WHERE p.estado = 'activo'" :
               "SELECT p.*, v.tasa_iva 
                FROM inv_periodos p 
                LEFT JOIN inv_valores_iva v ON p.id = v.periodo_id 
                WHERE p.estado = 'activo' 
                LIMIT 1";
        $stmt = $this->db->query($sql);
        return $stmt->fetch();
    }

    /**
     * Crea un nuevo período y le asigna su tasa de IVA (15%, 8%, 5%)
     */
    public function crear($nombre, $fechaInicio, $fechaFin, $tasaIva) {
        if ($fechaInicio > $fechaFin) {
            throw new InvalidArgumentException("La fecha de inicio no puede ser posterior a la fecha de fin.");
        }

        try {
            $this->db->beginTransaction();

            // 1. Desactivar o cerrar el período activo anterior si existe
            $activo = $this->obtenerPeriodoActivo();
            if ($activo) {
                throw new Exception("Existe un periodo activo. Debe ejecutar su cierre y respaldo antes de crear otro.");
            }

            // 2. Insertar período
            $stmt = $this->db->prepare("INSERT INTO inv_periodos (nombre, fecha_inicio, fecha_fin, estado) 
                                        VALUES (:nombre, :f_ini, :f_fin, 'activo')");
            $stmt->execute([
                ':nombre' => $nombre,
                ':f_ini' => $fechaInicio,
                ':f_fin' => $fechaFin
            ]);
            $periodoId = $this->db->lastInsertId();

            // 3. Insertar tasa de IVA
            $stmtIva = $this->db->prepare("INSERT INTO inv_valores_iva (tasa_iva, periodo_id) VALUES (:iva, :p_id)");
            $stmtIva->execute([
                ':iva' => (float)$tasaIva,
                ':p_id' => $periodoId
            ]);

            $this->db->commit();
            return $periodoId;
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * CORTE DE PERÍODO ("se corta deja un respaldo")
     * Toma una foto/snapshot del inventario activo y lo congela en la tabla 'inv_respaldo_historico'
     */
    public function ejecutarCorteYRespaldo($periodoId) {
        $periodo = $this->buscarPorId($periodoId);
        if (!$periodo) {
            throw new Exception("El período indicado no existe.");
        }

        try {
            $this->db->beginTransaction();

            $driver = defined('DB_DRIVER') ? DB_DRIVER : 'sqlite';
            $sqlBloqueo = ($driver === 'sqlsrv')
                ? "SELECT estado FROM inv_periodos WITH (UPDLOCK, HOLDLOCK) WHERE id = :id"
                : "SELECT estado FROM inv_periodos WHERE id = :id";
            $stmtBloqueo = $this->db->prepare($sqlBloqueo);
            $stmtBloqueo->execute([':id' => $periodoId]);
            $estadoActual = $stmtBloqueo->fetchColumn();
            if ($estadoActual !== 'activo') {
                throw new Exception("Solo se puede cerrar un periodo activo.");
            }

            $stmtExiste = $this->db->prepare("SELECT COUNT(*) FROM inv_respaldo_historico WHERE periodo_id = :id");
            $stmtExiste->execute([':id' => $periodoId]);
            if ((int)$stmtExiste->fetchColumn() > 0) {
                throw new Exception("El periodo ya posee un respaldo historico y no puede cerrarse nuevamente.");
            }

            // 1. Obtener todos los bienes activos del inventario al momento del corte
            $sql = "SELECT i.*, 
                           cat.nombre as categoria_nombre, 
                           z.nombre as zona_nombre, 
                           est.descripcion as estado_nombre,
                           pers.nombre as responsable_nombre,
                           pers.id as responsable_id
                    FROM inv_inventario i
                    JOIN inv_categorias cat ON i.categoria_id = cat.id
                    JOIN inv_zonas z ON i.zona_id = z.id
                    JOIN inv_estados est ON i.estado_id = est.idestado
                    LEFT JOIN inv_talento_personal pers ON i.responsable_id = pers.id
                    WHERE i.activo = 1";
            $stmtItems = $this->db->query($sql);
            $items = $stmtItems->fetchAll();

            $asigTalento = new InvAsignacionTalento();
            $tasaIva = (float)$periodo['tasa_iva'];
            $fechaCorte = date('Y-m-d');

            // 2. Congelar cada bien en la tabla de respaldo histórico
            $stmtInsert = $this->db->prepare("INSERT INTO inv_respaldo_historico (
                periodo_id, item_id, secuencial, nombre_historico, marca_historica, 
                categoria_historica, zona_historica, estado_historico, responsable_historico, 
                area_talento_historica, valor_historico, iva_aplicado, fecha_registro, fecha_corte
            ) VALUES (
                :p_id, :item_id, :sec, :nombre, :marca, 
                :cat, :zona, :estado, :resp, 
                :area, :valor, :iva, :f_reg, :f_corte
            )");

            foreach ($items as $item) {
                $areaInfo = ['area_nombre' => 'Sin Asignar'];
                if ($item['responsable_id']) {
                    $areaInfo = $asigTalento->obtenerAreaPorFecha($item['responsable_id'], $fechaCorte);
                }

                $stmtInsert->execute([
                    ':p_id' => $periodoId,
                    ':item_id' => $item['id'],
                    ':sec' => $item['secuencial'],
                    ':nombre' => $item['nombre'],
                    ':marca' => $item['marca'],
                    ':cat' => $item['categoria_nombre'],
                    ':zona' => $item['zona_nombre'],
                    ':estado' => $item['estado_nombre'],
                    ':resp' => $item['responsable_nombre'] ? $item['responsable_nombre'] : 'Sin Responsable',
                    ':area' => $areaInfo['area_nombre'],
                    ':valor' => (float)$item['valor'],
                    ':iva' => $tasaIva,
                    ':f_reg' => $item['fecha_registro'],
                    ':f_corte' => $fechaCorte
                ]);
            }

            // 3. Marcar el período como cerrado
            $stmtClose = $this->db->prepare("UPDATE inv_periodos SET estado = 'cerrado' WHERE id = :id");
            $stmtClose->execute([':id' => $periodoId]);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    public function obtenerRespaldoHistorico($periodoId) {
        $stmt = $this->db->prepare("SELECT * FROM inv_respaldo_historico WHERE periodo_id = :p_id ORDER BY secuencial ASC");
        $stmt->execute([':p_id' => $periodoId]);
        return $stmt->fetchAll();
    }

    public function generarReportePeriodo($fechaInicio, $fechaFin) {
        $driver = defined('DB_DRIVER') ? DB_DRIVER : 'sqlite';
        $sqlP = ($driver === 'sqlsrv') ?
                "SELECT TOP 1 p.id, p.nombre, v.tasa_iva FROM inv_periodos p 
                 LEFT JOIN inv_valores_iva v ON p.id = v.periodo_id 
                 WHERE p.fecha_inicio >= :f_ini AND p.fecha_fin <= :f_fin AND p.estado = 'cerrado'" :
                "SELECT p.id, p.nombre, v.tasa_iva FROM inv_periodos p 
                 LEFT JOIN inv_valores_iva v ON p.id = v.periodo_id 
                 WHERE p.fecha_inicio >= :f_ini AND p.fecha_fin <= :f_fin AND p.estado = 'cerrado' 
                 LIMIT 1";
        $stmtP = $this->db->prepare($sqlP);
        $stmtP->execute([':f_ini' => $fechaInicio, ':f_fin' => $fechaFin]);
        $periodo = $stmtP->fetch();

        if ($periodo) {
            return [
                'origen' => 'respaldo_historico',
                'periodo_nombre' => $periodo['nombre'],
                'tasa_iva' => $periodo['tasa_iva'],
                'datos' => $this->obtenerRespaldoHistorico($periodo['id'])
            ];
        }

        // 2. Si no hay período cerrado, consultar datos vivos aplicando el IVA del período activo o proporcional
        $sqlIva = ($driver === 'sqlsrv') ?
                  "SELECT TOP 1 tasa_iva FROM inv_valores_iva v 
                   JOIN inv_periodos p ON v.periodo_id = p.id 
                   WHERE p.fecha_inicio <= :f_fin AND p.fecha_fin >= :f_ini 
                   ORDER BY p.id DESC" :
                  "SELECT tasa_iva FROM inv_valores_iva v 
                   JOIN inv_periodos p ON v.periodo_id = p.id 
                   WHERE p.fecha_inicio <= :f_fin AND p.fecha_fin >= :f_ini 
                   ORDER BY p.id DESC LIMIT 1";
        $stmtIva = $this->db->prepare($sqlIva);
        $stmtIva->execute([':f_ini' => $fechaInicio, ':f_fin' => $fechaFin]);
        $ivaRes = $stmtIva->fetch();
        $tasaIva = $ivaRes ? (float)$ivaRes['tasa_iva'] : 15.0;

        $sql = "SELECT i.secuencial, 
                       i.nombre as nombre_historico, 
                       i.marca as marca_historica, 
                       cat.nombre as categoria_historica, 
                       z.nombre as zona_historica, 
                       est.descripcion as estado_historico,
                       pers.nombre as responsable_historico,
                       i.valor as valor_historico,
                       i.fecha_registro
                FROM inv_inventario i
                JOIN inv_categorias cat ON i.categoria_id = cat.id
                JOIN inv_zonas z ON i.zona_id = z.id
                JOIN inv_estados est ON i.estado_id = est.idestado
                LEFT JOIN inv_talento_personal pers ON i.responsable_id = pers.id
                WHERE i.activo = 1 AND i.fecha_registro BETWEEN :f_ini AND :f_fin
                ORDER BY i.secuencial ASC";
        
        $stmtItems = $this->db->prepare($sql);
        $stmtItems->execute([':f_ini' => $fechaInicio, ':f_fin' => $fechaFin]);
        $items = $stmtItems->fetchAll();

        $asigTalento = new InvAsignacionTalento();
        $finalItems = [];

        foreach ($items as $item) {
            $item['iva_aplicado'] = $tasaIva;
            $areaInfo = ['area_nombre' => 'Sin Asignar'];
            $stmtResp = $this->db->prepare("SELECT id FROM inv_talento_personal WHERE nombre = :nombre");
            $stmtResp->execute([':nombre' => $item['responsable_historico']]);
            $respInfo = $stmtResp->fetch();
            if ($respInfo) {
                $areaInfo = $asigTalento->obtenerAreaPorFecha($respInfo['id'], $item['fecha_registro']);
            }
            $item['area_talento_historica'] = $areaInfo['area_nombre'];
            $finalItems[] = $item;
        }

        return [
            'origen' => 'consulta_activa',
            'periodo_nombre' => 'Reporte Personalizado (' . $fechaInicio . ' al ' . $fechaFin . ')',
            'tasa_iva' => $tasaIva,
            'datos' => $finalItems
        ];
    }
}

class InvPeriodo extends PeriodoModel {}
