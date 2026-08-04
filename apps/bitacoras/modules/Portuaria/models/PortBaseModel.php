<?php
/**
 * PortBaseModel — Modelo base del módulo Portuaria.
 *
 * Replica EXACTAMENTE la API del `Model` base de portuaria_demoV4
 * (query/fetchAll/fetchOne/count sobre recurso sqlsrv crudo), de modo que los
 * modelos portados (bit_*Model.php) solo cambian su `extends`.
 */
abstract class PortBaseModel
{
    /** @var resource conexión sqlsrv */
    protected $conn;

    public function __construct(string $connection = 'principal')
    {
        $this->conn = PortDatabase::connection($connection);
    }

    /**
     * Ejecuta una consulta con parámetros y devuelve el statement.
     * @return resource|false
     */
    protected function query(string $sql, array $params = [])
    {
        $stmt = sqlsrv_query($this->conn, $sql, $params);
        if ($stmt === false) {
            error_log('SQL Error (Portuaria): ' . print_r(sqlsrv_errors(), true));
        }
        return $stmt;
    }

    /** Todas las filas como array asociativo. */
    protected function fetchAll(string $sql, array $params = []): array
    {
        $stmt = $this->query($sql, $params);
        if ($stmt === false) {
            return [];
        }
        $rows = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $rows[] = $row;
        }
        sqlsrv_free_stmt($stmt);
        return $rows;
    }

    /** Primera fila o false. */
    protected function fetchOne(string $sql, array $params = [])
    {
        $stmt = $this->query($sql, $params);
        if ($stmt === false) {
            return false;
        }
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        sqlsrv_free_stmt($stmt);
        return $row ?: false;
    }

    /** Consulta de conteo → int. */
    protected function count(string $sql, array $params = []): int
    {
        $row = $this->fetchOne($sql, $params);
        if (!$row) {
            return 0;
        }
        return (int) reset($row);
    }
}
