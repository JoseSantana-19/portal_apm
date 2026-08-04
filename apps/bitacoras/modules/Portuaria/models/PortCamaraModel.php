<?php
/**
 * Modelo de Cámaras CCTV.
 * Gestiona el inventario, la bitácora operativa y los motivos de novedades.
 */
class PortCamaraModel extends PortBaseModel
{
    /**
     * @param string $connection Nombre de la conexión configurada
     */
    public function __construct(string $connection = 'principal')
    {
        parent::__construct($connection);
    }

    public function tableExists(string $table)
    {
        $sql = "SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = N'dbo' AND TABLE_NAME = ?";
        $st = $this->query($sql, [$table]);
        return $st && (bool)$this->fetchOne($sql, [$table]);
    }

    public function obtenerEstadosCctv()
    {
        $sql = "SELECT idestado, descripcion, detalle FROM dbo.bit_estados 
                WHERE estado = 1 AND idestado IN (100, 101, 102, 103, 104, 105, 106)
                ORDER BY CASE WHEN idestado IN (102, 103) THEN 1 WHEN idestado IN (101, 100) THEN 2 WHEN idestado IN (104, 105, 106) THEN 3 ELSE 9 END,
                         CASE WHEN idestado = 102 THEN 1 WHEN idestado = 103 THEN 2 WHEN idestado = 101 THEN 1 WHEN idestado = 100 THEN 2 WHEN idestado = 104 THEN 1 WHEN idestado = 105 THEN 2 WHEN idestado = 106 THEN 3 ELSE idestado END";
        return $this->fetchAll($sql);
    }

    public function obtenerMotivosActivos()
    {
        $sql = "SELECT id_motivo_camara, codigo_motivo, descripcion, nivel_sugerido, requiere_observacion 
                FROM dbo.bit_motivos_camaras WHERE estado = 1 AND nivel_sugerido IN (N'Medio', N'Crítico') 
                ORDER BY nivel_sugerido, descripcion";
        return $this->fetchAll($sql);
    }

    public function buscarCamarasInventario(string $q)
    {
        $where = 'WHERE estado = 1';
        $params = [];
        if ($q !== '') {
            $where .= ' AND (cod_old LIKE ? OR codigo LIKE ? OR tipo LIKE ? OR marca LIKE ? OR modelo LIKE ? OR tecnologia LIKE ? OR caracteristica LIKE ? OR ip LIKE ? OR mac LIKE ? OR serie LIKE ? OR ubicacion LIKE ? OR detalle LIKE ? OR grabador LIKE ?)';
            $like = '%' . $q . '%';
            $params = array_fill(0, 13, $like);
        }
        $sql = "SELECT TOP 50 id_camara, cod_old, codigo, tipo, marca, modelo, tecnologia, caracteristica, ip, mac, serie, ubicacion, detalle, grabador 
                FROM dbo.bit_inv_camaras $where ORDER BY ubicacion, detalle, ip";
        return $this->fetchAll($sql, $params);
    }

    public function listarBitacora(string $fechaSql, string $turno, string $q, string $orden)
    {
        $where = 'WHERE bc.estado = 1 AND bc.fecha = ?';
        $params = [$fechaSql];
        if ($turno !== '') { $where .= ' AND bc.turno = ?'; $params[] = $turno; }
        if ($q !== '') {
            $where .= ' AND (bc.codigo_bitacora LIKE ? OR CONVERT(NVARCHAR(20), bc.tipo_registro) LIKE ? OR (CASE WHEN bc.tipo_registro = 103 THEN \'NOVEDAD CAMARA\' WHEN bc.tipo_registro = 102 THEN \'ACTIVIDAD DIARIA\' ELSE \'\' END) LIKE ? OR etipo.descripcion LIKE ? OR bc.rol_responsable LIKE ? OR bc.novedad LIKE ? OR bc.camara_ip LIKE ? OR bc.ubicacion LIKE ? OR bc.sitio LIKE ? OR CONVERT(NVARCHAR(20), bc.estado_camara) LIKE ? OR (CASE WHEN bc.estado_camara = 101 THEN \'OPERATIVA\' WHEN bc.estado_camara = 100 THEN \'NO OPERATIVA\' ELSE \'\' END) LIKE ? OR eestado.descripcion LIKE ? OR CONVERT(NVARCHAR(20), bc.nivel_alerta) LIKE ? OR (CASE WHEN bc.nivel_alerta = 106 THEN \'CRITICO\' WHEN bc.nivel_alerta = 105 THEN \'MEDIO\' WHEN bc.nivel_alerta = 104 THEN \'NORMAL\' ELSE \'\' END) LIKE ? OR enivel.descripcion LIKE ? OR bc.observaciones LIKE ? OR bm.descripcion LIKE ? OR bm.codigo_motivo LIKE ? OR ic.cod_old LIKE ? OR ic.codigo LIKE ? OR ic.tipo LIKE ? OR ic.marca LIKE ? OR ic.modelo LIKE ? OR ic.detalle LIKE ? OR ic.grabador LIKE ?)';
            $like = '%' . $q . '%';
            $params = array_merge($params, array_fill(0, 25, $like));
        }
        $sql = "SELECT bc.id_bitacora_camara, bc.sec_bitacora, bc.codigo_bitacora, bc.tipo_registro, etipo.descripcion AS tipo_registro_texto, bc.rol_responsable, bc.id_camara, bc.id_motivo_camara, bm.codigo_motivo AS motivo_codigo, bm.descripcion AS motivo_descripcion, bm.nivel_sugerido AS motivo_nivel_sugerido, bc.fecha, bc.secuencia, bc.turno, bc.hora_inicio, bc.hora_fin, bc.consolista, bc.hora_registro, bc.novedad, bc.camara_ip, bc.ubicacion, bc.sitio, bc.estado_camara, eestado.descripcion AS estado_camara_texto, bc.nivel_alerta, enivel.descripcion AS nivel_alerta_texto, bc.observaciones, bc.usuario_registro, bc.fecha_creacion, ic.cod_old AS inv_cod_old, ic.codigo AS inv_codigo, ic.tipo AS inv_tipo, ic.marca AS inv_marca, ic.modelo AS inv_modelo, ic.tecnologia AS inv_tecnologia, ic.caracteristica AS inv_caracteristica, ic.mac AS inv_mac, ic.serie AS inv_serie, ic.detalle AS inv_detalle, ic.grabador AS inv_grabador
                FROM dbo.bit_camaras bc LEFT JOIN dbo.bit_inv_camaras ic ON bc.id_camara = ic.id_camara LEFT JOIN dbo.bit_motivos_camaras bm ON bc.id_motivo_camara = bm.id_motivo_camara LEFT JOIN dbo.bit_estados etipo ON bc.tipo_registro = etipo.idestado LEFT JOIN dbo.bit_estados eestado ON bc.estado_camara = eestado.idestado LEFT JOIN dbo.bit_estados enivel ON bc.nivel_alerta = enivel.idestado
                $where ORDER BY bc.hora_registro $orden, bc.id_bitacora_camara $orden";
        return $this->fetchAll($sql, $params);
    }

    public function obtenerFilaBitacora(int $id)
    {
        $sql = "SELECT bc.id_bitacora_camara, bc.sec_bitacora, bc.codigo_bitacora, bc.tipo_registro, etipo.descripcion AS tipo_registro_texto, bc.rol_responsable, bc.id_camara, bc.id_motivo_camara, bm.codigo_motivo AS motivo_codigo, bm.descripcion AS motivo_descripcion, bm.nivel_sugerido AS motivo_nivel_sugerido, bc.fecha, bc.secuencia, bc.turno, bc.hora_inicio, bc.hora_fin, bc.consolista, bc.hora_registro, bc.novedad, bc.camara_ip, bc.ubicacion, bc.sitio, bc.estado_camara, eestado.descripcion AS estado_camara_texto, bc.nivel_alerta, enivel.descripcion AS nivel_alerta_texto, bc.observaciones, bc.usuario_registro, bc.fecha_creacion, ic.cod_old AS inv_cod_old, ic.codigo AS inv_codigo, ic.tipo AS inv_tipo, ic.marca AS inv_marca, ic.modelo AS inv_modelo, ic.tecnologia AS inv_tecnologia, ic.caracteristica AS inv_caracteristica, ic.mac AS inv_mac, ic.serie AS inv_serie, ic.detalle AS inv_detalle, ic.grabador AS inv_grabador
                FROM dbo.bit_camaras bc LEFT JOIN dbo.bit_inv_camaras ic ON bc.id_camara = ic.id_camara LEFT JOIN dbo.bit_motivos_camaras bm ON bc.id_motivo_camara = bm.id_motivo_camara LEFT JOIN dbo.bit_estados etipo ON bc.tipo_registro = etipo.idestado LEFT JOIN dbo.bit_estados eestado ON bc.estado_camara = eestado.idestado LEFT JOIN dbo.bit_estados enivel ON bc.nivel_alerta = enivel.idestado
                WHERE bc.id_bitacora_camara = ? AND bc.estado = 1";
        return $this->fetchOne($sql, [$id]);
    }

    public function eliminarFilaBitacora(int $id): bool
    {
        $sql = "UPDATE dbo.bit_camaras SET estado = 0, fecha_actualizacion = GETDATE() WHERE id_bitacora_camara = ? AND estado = 1";
        return $this->query($sql, [$id]) !== false;
    }

    public function registrarFilaBitacora(array $p): bool
    {
        $sql = "INSERT INTO dbo.bit_camaras (sec_bitacora, codigo_bitacora, tipo_registro, rol_responsable, id_camara, id_motivo_camara, fecha, secuencia, turno, hora_inicio, hora_fin, consolista, hora_registro, novedad, camara_ip, ubicacion, sitio, estado_camara, nivel_alerta, observaciones, usuario_registro) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        return $this->query($sql, $p) !== false;
    }

    public function actualizarFilaBitacora(array $p): bool
    {
        $sql = "UPDATE dbo.bit_camaras SET tipo_registro = ?, rol_responsable = ?, id_camara = ?, id_motivo_camara = ?, fecha = ?, secuencia = ?, turno = ?, hora_inicio = ?, hora_fin = ?, consolista = ?, hora_registro = ?, novedad = ?, camara_ip = ?, ubicacion = ?, sitio = ?, estado_camara = ?, nivel_alerta = ?, observaciones = ?, usuario_registro = ?, fecha_actualizacion = GETDATE() WHERE id_bitacora_camara = ? AND estado = 1";
        return $this->query($sql, $p) !== false;
    }

    public function generarSecuencialCctv(string $tabla)
    {
        $sqlSelect = "SELECT prefijo_codigo, ultimo_numero, longitud FROM dbo.bit_acc_secuenciales WITH (UPDLOCK, HOLDLOCK) WHERE tabla = ? AND estado = 1";
        $row = $this->fetchOne($sqlSelect, [$tabla]);
        if (!$row) throw new RuntimeException('No existe configuración de secuencial para ' . $tabla);

        $nuevoNumero = ((int)$row['ultimo_numero']) + 1;
        $codigo = $row['prefijo_codigo'] . str_pad((string)$nuevoNumero, max(1, (int)$row['longitud']), '0', STR_PAD_LEFT);

        $this->query("UPDATE dbo.bit_acc_secuenciales SET ultimo_numero = ?, fecha_actualizacion = GETDATE() WHERE tabla = ?", [$nuevoNumero, $tabla]);
        return ['numero' => $nuevoNumero, 'codigo' => $codigo];
    }

    // --- MÉTODOS ADICIONALES PARA MOTIVOS (API MOTIVOS) ---
    public function listarMotivosMaestro(string $q, string $estado, string $nivel)
    {
        $where = 'WHERE 1=1'; $params = [];
        if ($estado === '1' || $estado === '0') { $where .= ' AND estado = ?'; $params[] = (int)$estado; }
        if ($nivel !== '') { $where .= ' AND nivel_sugerido = ?'; $params[] = $nivel; }
        if ($q !== '') { $where .= ' AND (descripcion LIKE ? OR codigo_motivo LIKE ?)'; $like = '%' . $q . '%'; $params[] = $like; $params[] = $like; }
        return $this->fetchAll("SELECT TOP 300 id_motivo_camara, sec_motivo, codigo_motivo, descripcion, nivel_sugerido, requiere_observacion, estado, fecha_creacion, fecha_actualizacion FROM dbo.bit_motivos_camaras $where ORDER BY sec_motivo DESC, id_motivo_camara DESC", $params);
    }

    public function obtenerMotivoMaestro(int $id) {
        return $this->fetchOne("SELECT id_motivo_camara, sec_motivo, codigo_motivo, descripcion, nivel_sugerido, requiere_observacion, estado, fecha_creacion, fecha_actualizacion FROM dbo.bit_motivos_camaras WHERE id_motivo_camara = ?", [$id]);
    }

    public function cambiarEstadoMotivo(int $id, int $estado): bool {
        return $this->query("UPDATE dbo.bit_motivos_camaras SET estado = ?, fecha_actualizacion = GETDATE() WHERE id_motivo_camara = ?", [$estado, $id]) !== false;
    }

    public function existeMotivoDuplicado(string $desc, int $id): bool {
        return (bool)$this->fetchOne("SELECT TOP 1 id_motivo_camara FROM dbo.bit_motivos_camaras WHERE estado = 1 AND UPPER(LTRIM(RTRIM(descripcion))) = UPPER(LTRIM(RTRIM(?))) AND id_motivo_camara <> ?", [$desc, $id]);
    }

    public function insertarMotivoMaestro(array $p): bool {
        return $this->query("INSERT INTO dbo.bit_motivos_camaras (sec_motivo, codigo_motivo, descripcion, nivel_sugerido, requiere_observacion) VALUES (?, ?, ?, ?, ?)", $p) !== false;
    }

    public function actualizarMotivoMaestro(array $p): bool {
        return $this->query("UPDATE dbo.bit_motivos_camaras SET descripcion = ?, nivel_sugerido = ?, requiere_observacion = ?, fecha_actualizacion = GETDATE() WHERE id_motivo_camara = ?", $p) !== false;
    }
     
    // --- MÉTODOS ADICIONALES PARA INVENTARIO (API MAESTRO) ---
    public function colExistsInInventario(string $column): bool
    {
        $sql = "SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = N'dbo' AND TABLE_NAME = N'bit_inv_camaras' AND COLUMN_NAME = ?";
        return (bool)$this->fetchOne($sql, [$column]);
    }

    public function listarInventarioMaestro(string $q, string $estado, int $limite)
    {
        $hasSec = $this->colExistsInInventario('sec_camara');
        $hasCodSec = $this->colExistsInInventario('codigo_secuencial');

        $select = ['id_camara', 'cod_old', 'codigo', 'tipo', 'marca', 'modelo', 'tecnologia', 'caracteristica', 'ip', 'mac', 'serie', 'ubicacion', 'detalle', 'grabador', 'estado', 'fecha_creacion', 'fecha_actualizacion'];
        if ($hasSec) array_splice($select, 1, 0, ['sec_camara']);
        if ($hasCodSec) array_splice($select, $hasSec ? 2 : 1, 0, ['codigo_secuencial']);

        $where = 'WHERE 1=1'; $params = [];
        if ($estado === '1' || $estado === '0') { $where .= ' AND estado = ?'; $params[] = (int)$estado; }
        
        if ($q !== '') {
            $where .= ' AND (cod_old LIKE ? OR codigo LIKE ? OR tipo LIKE ? OR marca LIKE ? OR modelo LIKE ? OR tecnologia LIKE ? OR caracteristica LIKE ? OR ip LIKE ? OR mac LIKE ? OR serie LIKE ? OR ubicacion LIKE ? OR detalle LIKE ? OR grabador LIKE ?';
            if ($hasCodSec) $where .= ' OR codigo_secuencial LIKE ?';
            $where .= ')';
            $like = '%' . $q . '%';
            for ($i = 0; $i < 13; $i++) { $params[] = $like; }
            if ($hasCodSec) $params[] = $like;
        }

        $order = $hasSec ? 'sec_camara DESC, id_camara DESC' : 'id_camara DESC';
        return $this->fetchAll("SELECT TOP $limite " . implode(', ', $select) . " FROM dbo.bit_inv_camaras $where ORDER BY $order", $params);
    }

    public function obtenerCamaraMaestro(int $id)
    {
        $hasSec = $this->colExistsInInventario('sec_camara');
        $hasCodSec = $this->colExistsInInventario('codigo_secuencial');
        $select = ['id_camara', 'cod_old', 'codigo', 'tipo', 'marca', 'modelo', 'tecnologia', 'caracteristica', 'ip', 'mac', 'serie', 'ubicacion', 'detalle', 'grabador', 'estado', 'fecha_creacion', 'fecha_actualizacion'];
        if ($hasSec) array_splice($select, 1, 0, ['sec_camara']);
        if ($hasCodSec) array_splice($select, $hasSec ? 2 : 1, 0, ['codigo_secuencial']);

        return $this->fetchOne("SELECT " . implode(', ', $select) . " FROM dbo.bit_inv_camaras WHERE id_camara = ?", [$id]);
    }

    public function cambiarEstadoCamara(int $id, int $estado): bool
    {
        return $this->query("UPDATE dbo.bit_inv_camaras SET estado = ?, fecha_actualizacion = GETDATE() WHERE id_camara = ?", [$estado, $id]) !== false;
    }

    public function insertarCamaraMaestro(array $p, bool $conSecuencial): bool
    {
        if ($conSecuencial) {
            $sql = "INSERT INTO dbo.bit_inv_camaras (sec_camara, codigo_secuencial, cod_old, codigo, tipo, marca, modelo, tecnologia, caracteristica, ip, mac, serie, ubicacion, detalle, grabador) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        } else {
            $sql = "INSERT INTO dbo.bit_inv_camaras (cod_old, codigo, tipo, marca, modelo, tecnologia, caracteristica, ip, mac, serie, ubicacion, detalle, grabador) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        }
        return $this->query($sql, $p) !== false;
    }

    public function actualizarCamaraMaestro(array $p): bool
    {
        $sql = "UPDATE dbo.bit_inv_camaras SET cod_old = ?, codigo = ?, tipo = ?, marca = ?, modelo = ?, tecnologia = ?, caracteristica = ?, ip = ?, mac = ?, serie = ?, ubicacion = ?, detalle = ?, grabador = ?, fecha_actualizacion = GETDATE() WHERE id_camara = ?";
        return $this->query($sql, $p) !== false;
    }
}