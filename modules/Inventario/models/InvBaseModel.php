<?php
/**
 * InvBaseModel — Modelo base del módulo Inventario.
 * Misma forma que core/Model.php de portal_apm, pero usando InvDatabase
 * (conexión nativa sqlsrv a la BD `inventario`). NO usa PDO.
 */
abstract class InvBaseModel
{
    protected function db(): InvDatabase
    {
        return InvDatabase::getInstance();
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

    /** Normaliza término de búsqueda para LIKE acento/caso-insensitivo. */
    protected function likeParam(string $term): string
    {
        return '%' . trim($term) . '%';
    }
}
