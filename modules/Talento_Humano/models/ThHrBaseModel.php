<?php
/**
 * ThHrBaseModel — Modelo base del módulo Talento Humano.
 * Misma forma que core/Model.php de portal_apm, pero usando ThHrDatabase
 * (conexión nativa sqlsrv a la BD `Talento_Humano`). NO usa PDO.
 */
abstract class ThHrBaseModel
{
    protected function db(): ThHrDatabase
    {
        return ThHrDatabase::getInstance();
    }

    protected function query(string $sql, array $params = [])
    {
        return $this->db()->query($sql, $params);
    }

    protected function fetch(string $sql, array $params = []): ?array
    {
        return $this->db()->fetch($this->db()->query($sql, $params));
    }

    protected function fetchAll(string $sql, array $params = []): array
    {
        return $this->db()->fetchAll($this->db()->query($sql, $params));
    }

    protected function fetchColumn(string $sql, array $params = [], int $col = 0)
    {
        return $this->db()->fetchColumn($this->db()->query($sql, $params), $col);
    }

    protected function exec(string $sql, array $params = []): int
    {
        return $this->db()->rowsAffected($this->db()->query($sql, $params));
    }

    protected function lastInsertId(): ?int
    {
        return $this->db()->lastInsertId();
    }

    /** Normaliza término de búsqueda para LIKE (colación CI/AI de SQL Server). */
    protected function likeParam(string $term): string
    {
        return '%' . trim($term) . '%';
    }
}
