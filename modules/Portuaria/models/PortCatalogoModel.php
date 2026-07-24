<?php
/**
 * Modelo de Catálogos Maestros.
 */
class PortCatalogoModel extends PortBaseModel
{
    public function __construct(string $connection = 'principal')
    {
        parent::__construct($connection);
    }

    // =========================================================================
    // 1. MÉTODOS GENÉRICOS DE CATÁLOGOS (Reemplaza catalogos_api.php)
    // =========================================================================

    public function tableExists(string $table): bool
    {
        $sql = "SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = N'dbo' AND TABLE_NAME = ?";
        return (bool)$this->fetchOne($sql, [$table]);
    }

    public function colExists(string $table, string $column): bool
    {
        $sql = "SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = N'dbo' AND TABLE_NAME = ? AND COLUMN_NAME = ?";
        return (bool)$this->fetchOne($sql, [$table, $column]);
    }

    public function tieneVisitasAsociadas(string $catalogo, int $id): bool
    {
        if ($id <= 0) return false;
        
        $map = [
            'personas' => 'id_persona', 'empresas' => 'id_empresa', 'funcionarios' => 'id_funcionario',
            'destinos' => 'id_destino', 'motivos' => 'id_motivo', 'niveles_incidente' => 'id_nivel_incidente'
        ];
        
        if (!isset($map[$catalogo])) return false;
        $col = $map[$catalogo];
        
        if ($catalogo === 'niveles_incidente' && !$this->colExists('bit_visitas', 'id_nivel_incidente')) {
            return false;
        }

        $sql = "SELECT TOP 1 1 AS x FROM dbo.bit_visitas WHERE $col = ?";
        return (bool)$this->fetchOne($sql, [$id]);
    }

    public function listarCatalogo(array $cfg, string $q, bool $soloActivos)
    {
        $selectCols = implode(', ', $cfg['select']);
        $sql = "SELECT TOP 300 $selectCols FROM dbo." . $cfg['table'] . " WHERE 1=1";
        $params = [];

        if ($soloActivos) { $sql .= " AND estado = 1"; }
        
        if ($q !== '') {
            $term = '%' . str_replace(['%', '_'], ['[%]', '[_]'], $q) . '%';
            $searchChunks = [];
            foreach ($cfg['searchCols'] as $col) {
                $searchChunks[] = "$col LIKE ?";
                $params[] = $term;
            }
            $sql .= " AND (" . implode(' OR ', $searchChunks) . ")";
        }
        $sql .= " ORDER BY " . $cfg['orderBy'];

        return $this->fetchAll($sql, $params);
    }

    public function obtenerCatalogo(array $cfg, int $id)
    {
        $selectCols = implode(', ', $cfg['select']);
        $sql = "SELECT $selectCols FROM dbo." . $cfg['table'] . " WHERE estado = 1 AND " . $cfg['pk'] . " = ?";
        return $this->fetchOne($sql, [$id]);
    }

    public function insertarCatalogo(array $cfg, array $data): int
    {
        if (!isset($data['estado'])) $data['estado'] = 1;
        $cols = array_keys($data);
        $placeholders = implode(', ', array_fill(0, count($cols), '?'));
        
        $sql = "INSERT INTO dbo." . $cfg['table'] . " (" . implode(', ', $cols) . ") VALUES ($placeholders)";
        $this->query($sql, array_values($data));
        
        $row = $this->fetchOne("SELECT SCOPE_IDENTITY() AS id");
        return $row ? (int)$row['id'] : 0;
    }

    public function actualizarCatalogo(array $cfg, int $id, array $data): bool
    {
        $sets = [];
        $params = [];
        foreach ($data as $k => $v) { $sets[] = "$k = ?"; $params[] = $v; }
        if (empty($sets)) return false;
        
        $params[] = $id;
        $sql = "UPDATE dbo." . $cfg['table'] . " SET " . implode(', ', $sets) . " WHERE " . $cfg['pk'] . " = ?";
        return $this->query($sql, $params) !== false;
    }

    public function desactivarCatalogo(array $cfg, int $id): bool
    {
        $sql = "UPDATE dbo." . $cfg['table'] . " SET estado = 0 WHERE " . $cfg['pk'] . " = ?";
        return $this->query($sql, [$id]) !== false;
    }

    public function contarNivelesIncidente(): int
    {
        $row = $this->fetchOne("SELECT COUNT(*) AS c FROM dbo.bit_niveles_incidente WHERE nivel IN (1, 2, 3)");
        return $row ? (int)$row['c'] : 3;
    }

    public function existeNivelIncidente($nivel): bool
    {
        return (bool)$this->fetchOne("SELECT id_incidentes FROM dbo.bit_niveles_incidente WHERE nivel = ?", [$nivel]);
    }

    // =========================================================================
    // 2. MÉTODOS ESPECÍFICOS DE PERSONAS (Reemplaza bit_personas_api.php)
    // =========================================================================

    public function obtenerPersonaLocalIdentificacion(string $identificacion)
    {
        $sql = "SELECT id_persona, nidentificacion, tidentif, nombres, apellidos FROM dbo.bit_personas WHERE estado = 1 AND nidentificacion = ?";
        return $this->fetchOne($sql, [$identificacion]);
    }

    public function listarPersonaLocalPrefijo(string $q)
    {
        $sql = "SELECT TOP 20 id_persona, nidentificacion, tidentif, nombres, apellidos FROM dbo.bit_personas WHERE estado = 1 AND nidentificacion LIKE ? ORDER BY nidentificacion";
        return $this->fetchAll($sql, [$q . '%']);
    }

    public function listarPersonaLocalNombre(string $q)
    {
        $term = '%' . str_replace(['%', '_'], ['[%]', '[_]'], $q) . '%';
        $sql = "SELECT TOP 20 id_persona, nidentificacion, tidentif, nombres, apellidos FROM dbo.bit_personas WHERE estado = 1 AND (nombres LIKE ? OR apellidos LIKE ?) ORDER BY nombres, apellidos";
        return $this->fetchAll($sql, [$term, $term]);
    }

    public function verificarPersonaInactiva(string $nidentificacion)
    {
        return $this->fetchOne("SELECT estado FROM dbo.bit_personas WHERE nidentificacion = ?", [$nidentificacion]);
    }

    public function reactivarPersona(string $nidentificacion, string $tidentif, string $nombres, string $apellidos): bool
    {
        $sql = "UPDATE dbo.bit_personas SET estado = 1, tidentif = ?, nombres = ?, apellidos = ? WHERE nidentificacion = ? AND estado = 0";
        return $this->query($sql, [$tidentif, $nombres, $apellidos, $nidentificacion]) !== false;
    }

    public function insertarPersona(string $nidentificacion, string $tidentif, string $nombres, string $apellidos): bool
    {
        $sql = "INSERT INTO dbo.bit_personas (nidentificacion, tidentif, nombres, apellidos, estado) VALUES (?, ?, ?, ?, 1)";
        return $this->query($sql, [$nidentificacion, $tidentif, $nombres, $apellidos]) !== false;
    }

    // --- CONEXIÓN EXTERNA ---
    private function extColumnasPersonas($connExterna): array
    {
        $cols = [];
        $stCols = sqlsrv_query($connExterna, "SELECT LOWER(COLUMN_NAME) AS c FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = 'dbo' AND TABLE_NAME = 'bit_personas'");
        if ($stCols) { while ($r = sqlsrv_fetch_array($stCols, SQLSRV_FETCH_ASSOC)) { if (!empty($r['c'])) $cols[(string)$r['c']] = true; } }
        $idCol = isset($cols['idpersona']) ? 'idpersona' : 'id_persona';
        $tidCol = isset($cols['tidentif']) ? 'tidentif' : (isset($cols['tidenti']) ? 'tidenti' : (isset($cols['tipoidentif']) ? 'tipoidentif' : ''));
        return ['id' => $idCol, 'tidentif' => $tidCol];
    }

    private function extTidentifExpr(string $srcTid): string
    {
        if ($srcTid === '') return "N'Cédula' AS tidentif";
        $s = "UPPER(LTRIM(RTRIM(CONVERT(NVARCHAR(20), $srcTid))))";
        return "CASE WHEN $s IN (N'1', N'C', N'CEDULA') THEN N'Cédula' WHEN $s IN (N'2', N'R', N'RUC') THEN N'RUC' WHEN $s IN (N'3', N'P', N'PASAPORTE') THEN N'Pasaporte' ELSE N'Cédula' END AS tidentif";
    }

    public function obtenerPersonaExternaIdentificacion(string $identificacion)
    {
        $connExterna = PortDatabase::connExterna();
        if (!$connExterna) return null;
        $map = $this->extColumnasPersonas($connExterna);
        $idExpr = $map['id'] . ' AS id_persona';
        $tidExpr = $this->extTidentifExpr($map['tidentif']);
        $sql = "SELECT $idExpr, nidentificacion, $tidExpr, nombres, apellidos FROM dbo.bit_personas WHERE ISNULL(estado,1) = 1 AND nidentificacion = ?";
        $st = sqlsrv_query($connExterna, $sql, [$identificacion]);
        return ($st && $row = sqlsrv_fetch_array($st, SQLSRV_FETCH_ASSOC)) ? $row : null;
    }

    public function listarPersonaExternaPrefijo(string $prefijo, int $limite = 10)
    {
        $connExterna = PortDatabase::connExterna();
        if (!$connExterna) return [];
        $map = $this->extColumnasPersonas($connExterna);
        $idExpr = $map['id'] . ' AS id_persona';
        $tidExpr = $this->extTidentifExpr($map['tidentif']);
        $sql = "SELECT TOP ($limite) $idExpr, nidentificacion, $tidExpr, nombres, apellidos FROM dbo.bit_personas WHERE ISNULL(estado,1) = 1 AND nidentificacion LIKE ? ORDER BY nidentificacion";
        $st = sqlsrv_query($connExterna, $sql, [$prefijo . '%']);
        $out = [];
        if ($st) { while ($row = sqlsrv_fetch_array($st, SQLSRV_FETCH_ASSOC)) { $out[] = $row; } }
        return $out;
    }

    public function listarPersonaExternaNombre(string $term, int $limite = 10)
    {
        $connExterna = PortDatabase::connExterna();
        if (!$connExterna) return [];
        $map = $this->extColumnasPersonas($connExterna);
        $idExpr = $map['id'] . ' AS id_persona';
        $tidExpr = $this->extTidentifExpr($map['tidentif']);
        $p = '%' . str_replace(['%', '_'], ['[%]', '[_]'], $term) . '%';
        $sql = "SELECT TOP ($limite) $idExpr, nidentificacion, nombres, apellidos, $tidExpr FROM dbo.bit_personas WHERE (nombres LIKE ? OR apellidos LIKE ?) AND ISNULL(estado,1) = 1 ORDER BY nombres, apellidos";
        $st = sqlsrv_query($connExterna, $sql, [$p, $p]);
        $out = [];
        if ($st) { while ($row = sqlsrv_fetch_array($st, SQLSRV_FETCH_ASSOC)) { $out[] = $row; } }
        return $out;
    }

    // =========================================================================
    // 4. EMPRESAS — CONEXIÓN EXTERNA (APM / trámites en línea)
    //    OJO: a diferencia de personas, la tabla externa se llama
    //    dbo.reg_empresas (no bit_empresas) y su columna de id es "idempresa".
    //    Mismo servidor/BD que personas: conexion/conexion_externa.php.
    // =========================================================================

    public function obtenerEmpresaExternaRuc(string $ruc)
    {
        $connExterna = PortDatabase::connExterna();
        if (!$connExterna) return null;
        $sql = "SELECT idempresa AS id_empresa, empresa, razonsocial, ruc FROM dbo.reg_empresas WHERE CAST(ISNULL(estado,1) AS TINYINT) = 1 AND ruc = ?";
        $st = sqlsrv_query($connExterna, $sql, [$ruc]);
        return ($st && $row = sqlsrv_fetch_array($st, SQLSRV_FETCH_ASSOC)) ? $row : null;
    }

    public function listarEmpresaExternaNombreORuc(string $term, int $limite = 30)
    {
        $connExterna = PortDatabase::connExterna();
        if (!$connExterna) return [];
        $p = '%' . str_replace(['%', '_'], ['[%]', '[_]'], $term) . '%';
        $sql = "SELECT TOP ($limite) idempresa AS id_empresa, empresa, razonsocial, ruc FROM dbo.reg_empresas "
            . "WHERE CAST(ISNULL(estado,1) AS TINYINT) = 1 "
            . "AND (empresa COLLATE Latin1_General_CI_AI LIKE ? OR ISNULL(razonsocial,'') COLLATE Latin1_General_CI_AI LIKE ? OR ISNULL(ruc,'') LIKE ?) "
            . "ORDER BY empresa";
        $st = sqlsrv_query($connExterna, $sql, [$p, $p, $p]);
        $out = [];
        if ($st) { while ($row = sqlsrv_fetch_array($st, SQLSRV_FETCH_ASSOC)) { $out[] = $row; } }
        return $out;
    }

    // =========================================================================
    // 5. BÚSQUEDA EN CASCADA (Personas / Empresas)
    //    Orden pedido por el Ing. Zambrano, siempre en este orden y sin saltarse
    //    ningún nivel:
    //      1) Base LOCAL      → PortuariaDemo (bit_personas / bit_empresas)
    //      2) Base APM        → PortuariaExterna / APM, trámites en línea
    //                           (conexion/conexion_externa.php, ya existe)
    //      3) Microservicios  → SRI (RUC/empresas) y Registro Civil (cédula/personas)
    //                           TODAVÍA NO DISPONIBLE: no hay URL ni credenciales.
    //                           Dejar el espacio listo; NO inventar una URL.
    //    Cada resultado trae 'origen' => 'local' | 'apm' | 'sri' | 'registro_civil'
    //    para que la pantalla pueda indicar de dónde vino el dato si hace falta.
    // =========================================================================

    /** @return array|null */
    public function buscarPersonaCascada(string $identificacion)
    {
        // 1) Local
        $local = $this->obtenerPersonaLocalIdentificacion($identificacion);
        if ($local) {
            $local['origen'] = 'local';
            return $local;
        }

        // 2) APM (trámites en línea)
        $externa = $this->obtenerPersonaExternaIdentificacion($identificacion);
        if ($externa) {
            $externa['origen'] = 'apm';
            return $externa;
        }

        // 3) Registro Civil (microservicio) — PENDIENTE, sin URL/credenciales todavía.
        // Cuando exista, iría algo así (no ejecutar hasta tener la URL real):
        //
        // $cliente = new RegistroCivilClient(getenv('REGISTRO_CIVIL_URL')); // <-- URL Registro Civil (pendiente)
        // $datos = $cliente->consultarPorCedula($identificacion);
        // if ($datos) {
        //     $datos['origen'] = 'registro_civil';
        //     return $datos;
        // }

        return null;
    }

    /** @return array|null */
    public function buscarEmpresaCascada(string $ruc)
    {
        // 1) Local
        $local = $this->fetchOne(
            "SELECT id_empresa, empresa, razonsocial, ruc FROM dbo.bit_empresas WHERE estado = 1 AND ruc = ?",
            [$ruc]
        );
        if ($local) {
            $local['origen'] = 'local';
            return $local;
        }

        // 2) APM (trámites en línea) — dbo.reg_empresas
        $externa = $this->obtenerEmpresaExternaRuc($ruc);
        if ($externa) {
            $externa['origen'] = 'apm';
            return $externa;
        }

        // 3) SRI (microservicio) — PENDIENTE, sin URL/credenciales todavía.
        // Cuando exista, iría algo así (no ejecutar hasta tener la URL real):
        //
        // $cliente = new SriClient(getenv('SRI_URL')); // <-- URL SRI (pendiente)
        // $datos = $cliente->consultarPorRuc($ruc);
        // if ($datos) {
        //     $datos['origen'] = 'sri';
        //     return $datos;
        // }

        return null;
    }

    // =========================================================================
    // 6. IMPORTAR FUNCIONARIOS DESDE CSV (Reemplaza bit_importar_funcionarios.php)
    //    CSV exportado de Visual FoxPro, columnas: cedula, apellidos, nombres.
    // =========================================================================

    public function funcionariosTieneCedula(): bool
    {
        return $this->colExists('bit_funcionarios', 'cedula');
    }

    public function funcionarioExistePorCedula(string $cedula): bool
    {
        return (bool) $this->fetchOne("SELECT 1 AS x FROM dbo.bit_funcionarios WHERE cedula = ?", [$cedula]);
    }

    public function insertarFuncionarioImportado(string $cedula, string $nombre): bool
    {
        return $this->query(
            "INSERT INTO dbo.bit_funcionarios (cedula, nombre, cargo) VALUES (?, ?, N'Importado')",
            [$cedula, $nombre]
        ) !== false;
    }
}