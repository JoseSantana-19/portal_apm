<?php
/**
 * Modelo de Bitácora de Rondas.
 * Fase 2 de la migración: puerto fiel de apis/rondas_bitacora_api.php.
 * La lógica de negocio (ventanas de turno, fechas operativas, ventana de
 * edición) se preservó tal cual — son reglas sensibles que no se pudieron
 * probar contra una base real en este entorno, así que se priorizó la
 * fidelidad al original sobre la reescritura.
 */
class PortRondaModel extends PortBaseModel
{
    public function __construct(string $connection = 'principal')
    {
        parent::__construct($connection);
    }

    // =========================================================================
    // CÁLCULOS PUROS (sin BD) — turno, fechas, horas
    // =========================================================================

    /** Turnos: Mañana 07:00–14:59, Tarde 15:00–22:59, Noche 23:00–06:59 (fecha operativa noche antes de 07:00 = día anterior). */
    public static function turnoYFechaOperativa(DateTime $dt): array
    {
        $minutes = (int) $dt->format('H') * 60 + (int) $dt->format('i');
        $d0 = $dt->format('Y-m-d');

        if ($minutes >= 420 && $minutes < 900) {
            return ['turno' => 'Mañana', 'fecha' => $d0];
        }
        if ($minutes >= 900 && $minutes < 1380) {
            return ['turno' => 'Tarde', 'fecha' => $d0];
        }
        if ($minutes >= 1380) {
            return ['turno' => 'Noche', 'fecha' => $d0];
        }

        $d = clone $dt;
        $d->modify('-1 day');

        return ['turno' => 'Noche', 'fecha' => $d->format('Y-m-d')];
    }

    public static function turnoEtiqueta(string $turno): string
    {
        $map = [
            'Mañana' => 'Mañana (07:00 - 15:00)',
            'Tarde' => 'Tarde (15:00 - 23:00)',
            'Noche' => 'Noche (23:00 - 07:00)',
        ];

        return $map[$turno] ?? $turno;
    }

    /** @return array{inicio:string,fin:string} */
    public static function horariosDefaultPorTurno(string $turno): array
    {
        $map = [
            'Mañana' => ['inicio' => '07:00:00', 'fin' => '15:00:00'],
            'Tarde' => ['inicio' => '15:00:00', 'fin' => '23:00:00'],
            'Noche' => ['inicio' => '23:00:00', 'fin' => '07:00:00'],
        ];

        return $map[$turno] ?? $map['Mañana'];
    }

    public static function horaParaInput(?string $hms): string
    {
        if ($hms === null || $hms === '') {
            return '';
        }
        $hms = trim($hms);
        if (preg_match('/^(\d{1,2}):(\d{2})(?::(\d{2}))?$/', $hms, $m)) {
            return sprintf('%02d:%02d', (int) $m[1], (int) $m[2]);
        }

        return '';
    }

    public static function parseHoraPost(string $raw): ?string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }
        if (!preg_match('/^(\d{1,2}):(\d{2})(?::(\d{2}))?$/', $raw, $m)) {
            return null;
        }
        $h = (int) $m[1];
        $mi = (int) $m[2];
        $s = isset($m[3]) ? (int) $m[3] : 0;
        if ($h < 0 || $h > 23 || $mi < 0 || $mi > 59 || $s < 0 || $s > 59) {
            return null;
        }

        return sprintf('%02d:%02d:%02d', $h, $mi, $s);
    }

    /** ¿Cae el instante en la ventana [inicio, fin) sobre la fecha operativa (fin puede ser al día siguiente si fin < inicio)? */
    public static function instanteEnVentana(DateTime $instante, string $fechaOpYmd, string $hiHms, string $hfHms): bool
    {
        $hi = self::parseHoraPost($hiHms);
        $hf = self::parseHoraPost($hfHms);
        if ($hi === null || $hf === null || $hi === $hf) {
            return false;
        }

        $partsHi = explode(':', $hi);
        $ti = (int) $partsHi[0] * 3600 + (int) $partsHi[1] * 60 + (int) ($partsHi[2] ?? 0);
        $partsHf = explode(':', $hf);
        $tf = (int) $partsHf[0] * 3600 + (int) $partsHf[1] * 60 + (int) ($partsHf[2] ?? 0);

        $tInst = (int) $instante->format('H') * 3600 + (int) $instante->format('i') * 60 + (int) $instante->format('s');
        $dInst = $instante->format('Y-m-d');

        if ($ti < $tf) {
            return $dInst === $fechaOpYmd && $tInst >= $ti && $tInst < $tf;
        }

        $dOp = DateTime::createFromFormat('Y-m-d', $fechaOpYmd);
        if (!$dOp) {
            return false;
        }
        $dSig = $dOp->modify('+1 day')->format('Y-m-d');

        return ($dInst === $fechaOpYmd && $tInst >= $ti) || ($dInst === $dSig && $tInst < $tf);
    }

    /** Con la hora del reloj y la ventana inicio/fin, obtiene la fecha calendario correcta (turno cruza medianoche). */
    public static function fechaCalendarioParaHoraTurno(
        string $fechaOpYmd,
        string $hiHms,
        string $hfHms,
        int $h,
        int $mi,
        int $se
    ): string {
        $hi = self::parseHoraPost($hiHms);
        $hf = self::parseHoraPost($hfHms);
        if ($hi === null || $hf === null) {
            return $fechaOpYmd;
        }
        $partsHi = explode(':', $hi);
        $partsHf = explode(':', $hf);
        $ti = (int) $partsHi[0] * 3600 + (int) $partsHi[1] * 60 + (int) ($partsHi[2] ?? 0);
        $tf = (int) $partsHf[0] * 3600 + (int) $partsHf[1] * 60 + (int) ($partsHf[2] ?? 0);
        $tUser = $h * 3600 + $mi * 60 + $se;

        if ($ti < $tf) {
            return $fechaOpYmd;
        }
        if ($tUser >= $ti) {
            return $fechaOpYmd;
        }
        if ($tUser < $tf) {
            $d = DateTime::createFromFormat('Y-m-d', $fechaOpYmd);
            if ($d) {
                return $d->modify('+1 day')->format('Y-m-d');
            }
        }

        return $fechaOpYmd;
    }

    public static function fechaCompactaYmd(?string $raw): ?string
    {
        return apm_post_fecha_a_ymd_compacto($raw);
    }

    public static function datetimeSqlSafe(string $fechaYmdCompacto, int $h, int $mi, int $se): string
    {
        return $fechaYmdCompacto . sprintf(' %02d:%02d:%02d', $h, $mi, $se);
    }

    public static function parseDatetimeSqlSafe(string $dt): ?DateTime
    {
        $d = DateTime::createFromFormat('Ymd H:i:s', $dt);

        return $d instanceof DateTime ? $d : null;
    }

    /** Normaliza hora_registro de sqlsrv (objeto o string) para JSON, agrega bandera de cambio de día. */
    public static function enriquecerFilaHora(array $fila, string $fechaOp): array
    {
        $hr = $fila['hora_registro'] ?? null;
        if ($hr instanceof DateTimeInterface) {
            $fila['hora_registro_iso'] = $hr->format('c');
            $fila['hora_registro_fecha'] = $hr->format('Y-m-d');
            $fila['hora_registro_hora'] = $hr->format('H:i:s');
        } elseif (is_string($hr) && $hr !== '') {
            $ts = strtotime($hr);
            if ($ts !== false) {
                $fila['hora_registro_fecha'] = date('Y-m-d', $ts);
                $fila['hora_registro_hora'] = date('H:i:s', $ts);
            }
        }
        $ff = isset($fila['hora_registro_fecha']) ? (string) $fila['hora_registro_fecha'] : '';
        $fila['cambio_dia'] = ($ff !== '' && $ff !== $fechaOp);

        return $fila;
    }

    private function sqlsrvErrMsg(string $base): string
    {
        $e = sqlsrv_errors();
        $m = ($e && isset($e[0]['message'])) ? (string) $e[0]['message'] : '';

        return $m !== '' ? $base . ' ' . $m : $base;
    }

    // =========================================================================
    // CONSULTAS
    // =========================================================================

    public function tablasOk(): bool
    {
        $st = $this->query("SELECT 1 FROM sys.tables WHERE name = N'bit_rondas_cabecera' AND schema_id = SCHEMA_ID(N'dbo')");

        return $st !== false && sqlsrv_fetch_array($st) !== null;
    }

    public function nivelesAlertaActivos(): array
    {
        return $this->fetchAll('SELECT id_alerta, descripcion, color_hex FROM dbo.bit_niveles_alerta WHERE estado = 1 ORDER BY id_alerta');
    }

    /** @return list<string> Hasta $limite frases: prioridad por frecuencia, se completa con las más recientes. */
    public function sugerenciasActividad(int $idUsuario, int $limite = 10): array
    {
        $lim = max(1, min(20, $limite));
        $noVacio = 'NULLIF(LTRIM(RTRIM(CAST(d.actividad AS NVARCHAR(4000)))), N\'\') IS NOT NULL';

        $sqlFreq = 'SELECT TOP (' . $lim . ') x.actividad AS actividad FROM ('
            . 'SELECT d.actividad, COUNT(*) AS cnt, MAX(d.id_detalle) AS mx '
            . 'FROM dbo.bit_rondas_detalles d '
            . 'INNER JOIN dbo.bit_rondas_cabecera c ON c.id_ronda = d.id_ronda '
            . 'WHERE c.id_usuario = ? AND c.estado = 1 AND ' . $noVacio . ' '
            . 'GROUP BY d.actividad'
            . ') x ORDER BY x.cnt DESC, x.mx DESC';

        $out = [];
        $seen = [];
        foreach ($this->fetchAll($sqlFreq, [$idUsuario]) as $row) {
            $t = isset($row['actividad']) ? trim((string) $row['actividad']) : '';
            if ($t === '' || isset($seen[$t])) {
                continue;
            }
            $seen[$t] = true;
            $out[] = $t;
        }

        if (count($out) >= $lim) {
            return array_slice($out, 0, $lim);
        }

        $sqlRec = 'SELECT TOP (25) x.actividad AS actividad, x.mx AS mx FROM ('
            . 'SELECT d.actividad, MAX(d.id_detalle) AS mx '
            . 'FROM dbo.bit_rondas_detalles d '
            . 'INNER JOIN dbo.bit_rondas_cabecera c ON c.id_ronda = d.id_ronda '
            . 'WHERE c.id_usuario = ? AND c.estado = 1 AND ' . $noVacio . ' '
            . 'GROUP BY d.actividad'
            . ') x ORDER BY x.mx DESC';

        foreach ($this->fetchAll($sqlRec, [$idUsuario]) as $row) {
            if (count($out) >= $lim) {
                break;
            }
            $t = isset($row['actividad']) ? trim((string) $row['actividad']) : '';
            if ($t === '' || isset($seen[$t])) {
                continue;
            }
            $seen[$t] = true;
            $out[] = $t;
        }

        return $out;
    }

    /**
     * Listado del turno: si $esAdmin, todos los guardias activos del turno/fecha; si no, solo el usuario logueado.
     * @return array{ok:bool,message?:string,filas?:array,id_ronda?:?int}
     */
    public function listarTurno(int $idUsuario, bool $esAdmin, string $fecha, string $turno): array
    {
        if ($esAdmin) {
            $sql = 'SELECT d.id_detalle, d.hora_registro, d.actividad, d.id_alerta, a.descripcion AS alerta_desc, a.color_hex, '
                . 'c.id_ronda AS _id_ronda_cab, c.id_usuario AS _id_usuario_reg, u.nombres AS guardia_nombres, u.cedula AS guardia_cedula '
                . 'FROM dbo.bit_rondas_detalles d '
                . 'INNER JOIN dbo.bit_rondas_cabecera c ON c.id_ronda = d.id_ronda '
                . 'INNER JOIN dbo.bit_usuarios_apm u ON u.id_usuario = c.id_usuario '
                . 'INNER JOIN dbo.bit_niveles_alerta a ON a.id_alerta = d.id_alerta '
                . 'WHERE c.fecha = ? AND c.turno = ? AND c.estado = 1 AND ISNULL(u.estado,1) = 1 '
                . 'ORDER BY d.hora_registro ASC';
            $st = $this->query($sql, [$fecha, $turno]);
        } else {
            $sql = 'SELECT d.id_detalle, d.hora_registro, d.actividad, d.id_alerta, a.descripcion AS alerta_desc, a.color_hex, '
                . 'c.id_ronda AS _id_ronda_cab, c.id_usuario AS _id_usuario_reg '
                . 'FROM dbo.bit_rondas_detalles d '
                . 'INNER JOIN dbo.bit_rondas_cabecera c ON c.id_ronda = d.id_ronda '
                . 'INNER JOIN dbo.bit_niveles_alerta a ON a.id_alerta = d.id_alerta '
                . 'WHERE c.id_usuario = ? AND c.fecha = ? AND c.turno = ? AND c.estado = 1 '
                . 'ORDER BY d.hora_registro ASC';
            $st = $this->query($sql, [$idUsuario, $fecha, $turno]);
        }
        if ($st === false) {
            return ['ok' => false, 'message' => $this->sqlsrvErrMsg('Error al listar detalles del turno.')];
        }

        $filas = [];
        $idRonda = null;
        while ($r = sqlsrv_fetch_array($st, SQLSRV_FETCH_ASSOC)) {
            if ($idRonda === null && isset($r['_id_ronda_cab'])) {
                $idRonda = (int) $r['_id_ronda_cab'];
            }
            $idUsuarioReg = isset($r['_id_usuario_reg']) ? (int) $r['_id_usuario_reg'] : 0;
            $r['puede_editar'] = ($idUsuarioReg > 0 && $idUsuarioReg === $idUsuario);
            unset($r['_id_ronda_cab'], $r['_id_usuario_reg']);
            $filas[] = self::enriquecerFilaHora($r, $fecha);
        }

        return ['ok' => true, 'filas' => $filas, 'id_ronda' => $idRonda];
    }

    /** Horas de la cabecera del usuario para el turno/fecha, o el default del turno si no existe. */
    public function miCabeceraHoras(int $idUsuario, string $fecha, string $turno): array
    {
        $def = self::horariosDefaultPorTurno($turno);
        $hi = self::horaParaInput($def['inicio']);
        $hf = self::horaParaInput($def['fin']);

        $sql = 'SELECT hora_inicio, hora_fin FROM dbo.bit_rondas_cabecera WHERE id_usuario = ? AND fecha = ? AND turno = ? AND estado = 1';
        $row = $this->fetchOne($sql, [$idUsuario, $fecha, $turno]);
        if ($row) {
            $hiDb = $row['hora_inicio'] ?? null;
            $hfDb = $row['hora_fin'] ?? null;
            if ($hiDb instanceof DateTimeInterface) {
                $hi = self::horaParaInput($hiDb->format('H:i:s'));
            } elseif (is_string($hiDb) && trim($hiDb) !== '') {
                $hi = self::horaParaInput($hiDb);
            }
            if ($hfDb instanceof DateTimeInterface) {
                $hf = self::horaParaInput($hfDb->format('H:i:s'));
            } elseif (is_string($hfDb) && trim($hfDb) !== '') {
                $hf = self::horaParaInput($hfDb);
            }
        }

        return ['hora_inicio' => $hi, 'hora_fin' => $hf];
    }

    /** @return array{ok:bool,message?:string,filas?:array} */
    public function buscar(string $fechaDesde, string $fechaHasta, string $q): array
    {
        if ($q === '') {
            $sql = 'SELECT d.id_detalle, d.hora_registro, d.actividad, d.id_alerta, na.descripcion AS alerta_desc, na.color_hex, '
                . 'c.fecha AS fecha_operativa, c.turno, u.nombres, u.cedula '
                . 'FROM dbo.bit_rondas_detalles d '
                . 'INNER JOIN dbo.bit_rondas_cabecera c ON c.id_ronda = d.id_ronda '
                . 'INNER JOIN dbo.bit_usuarios_apm u ON u.id_usuario = c.id_usuario '
                . 'INNER JOIN dbo.bit_niveles_alerta na ON na.id_alerta = d.id_alerta '
                . 'WHERE c.fecha >= ? AND c.fecha <= ? AND c.estado = 1 '
                . 'ORDER BY d.hora_registro DESC';
            $st = $this->query($sql, [$fechaDesde, $fechaHasta]);
        } else {
            $term = '%' . str_replace(['%', '_'], ['[%]', '[_]'], $q) . '%';
            $sql = 'SELECT d.id_detalle, d.hora_registro, d.actividad, d.id_alerta, na.descripcion AS alerta_desc, na.color_hex, '
                . 'c.fecha AS fecha_operativa, c.turno, u.nombres, u.cedula '
                . 'FROM dbo.bit_rondas_detalles d '
                . 'INNER JOIN dbo.bit_rondas_cabecera c ON c.id_ronda = d.id_ronda '
                . 'INNER JOIN dbo.bit_usuarios_apm u ON u.id_usuario = c.id_usuario '
                . 'INNER JOIN dbo.bit_niveles_alerta na ON na.id_alerta = d.id_alerta '
                . 'WHERE c.fecha >= ? AND c.fecha <= ? AND c.estado = 1 '
                . 'AND (u.nombres LIKE ? OR u.cedula LIKE ?) '
                . 'ORDER BY d.hora_registro DESC';
            $st = $this->query($sql, [$fechaDesde, $fechaHasta, $term, $term]);
        }
        if ($st === false) {
            return ['ok' => false, 'message' => $this->sqlsrvErrMsg('Error en búsqueda.')];
        }

        $out = [];
        while ($r = sqlsrv_fetch_array($st, SQLSRV_FETCH_ASSOC)) {
            $hr = $r['hora_registro'];
            if ($hr instanceof DateTimeInterface) {
                $r['hora_registro_iso'] = $hr->format('c');
                $r['hora_registro_txt'] = $hr->format('d/m/Y H:i:s');
            }
            $out[] = $r;
        }

        return ['ok' => true, 'filas' => $out];
    }

    public function nivelAlertaExiste(int $idAlerta): bool
    {
        return (bool) $this->fetchOne('SELECT 1 AS ok FROM dbo.bit_niveles_alerta WHERE id_alerta = ? AND estado = 1', [$idAlerta]);
    }

    public function nombreUsuario(int $idUsuario): string
    {
        $row = $this->fetchOne('SELECT nombres FROM dbo.bit_usuarios_apm WHERE id_usuario = ?', [$idUsuario]);

        return $row ? (string) $row['nombres'] : 'Usuario';
    }

    /**
     * Crea o actualiza la cabecera (id_usuario+fecha+turno) y devuelve su id_ronda.
     * @return array{ok:bool,message?:string,id_ronda?:int}
     */
    public function upsertCabecera(int $idUsuario, string $fechaOp, string $turno, string $horaInicio, string $horaFin): array
    {
        $sqlFind = 'SELECT id_ronda FROM dbo.bit_rondas_cabecera WHERE id_usuario = ? AND fecha = ? AND turno = ? AND estado = 1';
        $ex = $this->fetchOne($sqlFind, [$idUsuario, $fechaOp, $turno]);

        if ($ex) {
            $idRonda = (int) $ex['id_ronda'];
            $st = $this->query(
                'UPDATE dbo.bit_rondas_cabecera SET hora_inicio = ?, hora_fin = ? WHERE id_ronda = ? AND estado = 1',
                [$horaInicio, $horaFin, $idRonda]
            );
            if ($st === false) {
                return ['ok' => false, 'message' => $this->sqlsrvErrMsg('No se pudo actualizar la cabecera de ronda.')];
            }

            return ['ok' => true, 'id_ronda' => $idRonda];
        }

        $sqlIns = 'INSERT INTO dbo.bit_rondas_cabecera (id_usuario, fecha, turno, hora_inicio, hora_fin, estado) '
            . 'OUTPUT INSERTED.id_ronda VALUES (?, ?, ?, ?, ?, 1)';
        $st = $this->query($sqlIns, [$idUsuario, $fechaOp, $turno, $horaInicio, $horaFin]);
        if ($st === false) {
            return ['ok' => false, 'message' => $this->sqlsrvErrMsg('No se pudo crear la cabecera de ronda.')];
        }
        $idRonda = 0;
        do {
            while ($rowOut = sqlsrv_fetch_array($st, SQLSRV_FETCH_ASSOC)) {
                if (isset($rowOut['id_ronda'])) {
                    $idRonda = (int) $rowOut['id_ronda'];
                }
            }
        } while (sqlsrv_next_result($st));
        if ($idRonda <= 0) {
            return ['ok' => false, 'message' => $this->sqlsrvErrMsg('No se obtuvo id de ronda.')];
        }

        return ['ok' => true, 'id_ronda' => $idRonda];
    }

    /** @return array{ok:bool,message?:string} */
    public function actualizarDetalle(int $idDetalle, int $idUsuario, string $actividad, int $idAlerta, ?string $horaRegistroSql): array
    {
        if ($horaRegistroSql !== null) {
            $sql = 'UPDATE d SET d.actividad = ?, d.id_alerta = ?, d.hora_registro = ? '
                . 'FROM dbo.bit_rondas_detalles d '
                . 'INNER JOIN dbo.bit_rondas_cabecera c ON c.id_ronda = d.id_ronda '
                . 'WHERE d.id_detalle = ? AND c.id_usuario = ? AND c.estado = 1';
            $st = $this->query($sql, [$actividad, $idAlerta, $horaRegistroSql, $idDetalle, $idUsuario]);
        } else {
            $sql = 'UPDATE d SET d.actividad = ?, d.id_alerta = ? '
                . 'FROM dbo.bit_rondas_detalles d '
                . 'INNER JOIN dbo.bit_rondas_cabecera c ON c.id_ronda = d.id_ronda '
                . 'WHERE d.id_detalle = ? AND c.id_usuario = ? AND c.estado = 1';
            $st = $this->query($sql, [$actividad, $idAlerta, $idDetalle, $idUsuario]);
        }
        if ($st === false) {
            return ['ok' => false, 'message' => $this->sqlsrvErrMsg('No se pudo actualizar el detalle.')];
        }
        if (sqlsrv_rows_affected($st) < 1) {
            return ['ok' => false, 'message' => 'No se encontró el detalle para actualizar.'];
        }

        return ['ok' => true];
    }

    /** @return array{ok:bool,message?:string,id_detalle?:int} */
    public function insertarDetalle(int $idRonda, string $actividad, int $idAlerta, ?string $horaRegistroSql): array
    {
        if ($horaRegistroSql !== null) {
            $st = $this->query(
                'INSERT INTO dbo.bit_rondas_detalles (id_ronda, actividad, id_alerta, hora_registro) VALUES (?, ?, ?, ?)',
                [$idRonda, $actividad, $idAlerta, $horaRegistroSql]
            );
        } else {
            $st = $this->query(
                'INSERT INTO dbo.bit_rondas_detalles (id_ronda, actividad, id_alerta) VALUES (?, ?, ?)',
                [$idRonda, $actividad, $idAlerta]
            );
        }
        if ($st === false) {
            return ['ok' => false, 'message' => $this->sqlsrvErrMsg('No se pudo registrar el detalle.')];
        }
        $idDetalle = 0;
        $scope = $this->fetchOne('SELECT CAST(SCOPE_IDENTITY() AS INT) AS i');
        if ($scope && isset($scope['i'])) {
            $idDetalle = (int) $scope['i'];
        }

        return ['ok' => true, 'id_detalle' => $idDetalle];
    }

    public function registrarMovimiento(int $idUsuario, string $descripcion, string $turno): void
    {
        // Auditoría opcional: no falla el guardado si esto falla (p. ej. columna corta).
        $this->query(
            'INSERT INTO dbo.bit_movimientos (id_usuario, tipo_evento, descripcion, turno, fecha_hora) VALUES (?, ?, ?, ?, GETDATE())',
            [$idUsuario, 'RONDA', $descripcion, $turno]
        );
    }

    /** Última fila del detalle recién grabado/editado, ya enriquecida para el JSON de respuesta. */
    public function obtenerUltimaFila(int $idRonda, int $idDetalleEdit, string $fechaOp): ?array
    {
        $sql = 'SELECT TOP 1 d.id_detalle, d.hora_registro, d.actividad, d.id_alerta, a.descripcion AS alerta_desc, a.color_hex '
            . 'FROM dbo.bit_rondas_detalles d '
            . 'INNER JOIN dbo.bit_niveles_alerta a ON a.id_alerta = d.id_alerta '
            . 'WHERE d.id_ronda = ? ';
        $params = [$idRonda];
        if ($idDetalleEdit > 0) {
            $sql .= 'AND d.id_detalle = ? ORDER BY d.id_detalle DESC';
            $params[] = $idDetalleEdit;
        } else {
            $sql .= 'ORDER BY d.id_detalle DESC';
        }
        $row = $this->fetchOne($sql, $params);

        return $row ? self::enriquecerFilaHora($row, $fechaOp) : null;
    }
}
