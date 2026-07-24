<?php
/**
 * PeriodoModel — Períodos contables e IVA por período (inv_periodos / inv_valores_iva).
 * Port nativo sqlsrv (sin PDO).
 */
class PeriodoModel extends InvBaseModel
{
    public function obtenerTodos(): array
    {
        return $this->fetchAll(
            "SELECT p.*, v.tasa_iva
             FROM inv_periodos p
             LEFT JOIN inv_valores_iva v ON p.id = v.periodo_id
             ORDER BY p.fecha_inicio DESC");
    }

    public function buscarPorId(int $id): ?array
    {
        return $this->fetch(
            "SELECT p.*, v.tasa_iva
             FROM inv_periodos p
             LEFT JOIN inv_valores_iva v ON p.id = v.periodo_id
             WHERE p.id = ?", [[$id, SQLSRV_PARAM_IN]]);
    }

    public function obtenerPeriodoActivo(): ?array
    {
        return $this->fetch(
            "SELECT TOP 1 p.*, v.tasa_iva
             FROM inv_periodos p
             LEFT JOIN inv_valores_iva v ON p.id = v.periodo_id
             WHERE p.estado = 'activo'");
    }

    public function crear(string $nombre, string $fIni, string $fFin, float $tasaIva): int
    {
        $db = $this->db();
        $db->beginTransaction();
        try {
            $db->query("UPDATE inv_periodos SET estado = 'cerrado' WHERE estado = 'activo'");
            $db->query("INSERT INTO inv_periodos (nombre, fecha_inicio, fecha_fin, estado) VALUES (?,?,?,'activo')", [
                [$nombre, SQLSRV_PARAM_IN], [$fIni, SQLSRV_PARAM_IN], [$fFin, SQLSRV_PARAM_IN],
            ]);
            $pid = (int)$db->lastInsertId();
            $db->query("INSERT INTO inv_valores_iva (tasa_iva, periodo_id) VALUES (?,?)", [
                [$tasaIva, SQLSRV_PARAM_IN], [$pid, SQLSRV_PARAM_IN],
            ]);
            $db->commit();
            return $pid;
        } catch (Throwable $e) {
            $db->rollback();
            throw $e;
        }
    }
}
