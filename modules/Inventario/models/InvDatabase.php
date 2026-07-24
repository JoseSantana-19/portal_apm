<?php
/**
 * InvDatabase — Conexión nativa sqlsrv (NO PDO) a la base de datos `inventario`.
 *
 * El módulo Inventario es independiente: vive en su propia BD `inventario`
 * (aislada de PORTAL_APM) pero usa la MISMA instancia de SQL Server y la
 * MISMA estructura MVC que portal_apm. Esta clase replica la API de
 * core/Database.php para que los modelos se escriban igual que el resto
 * del proyecto, pero apuntando a la BD de inventario.
 */
class InvDatabase
{
    private static ?self $instance = null;
    private $conn = null;

    private function __construct()
    {
        $server = defined('DB_SERVER') ? DB_SERVER : '.\\VICTUS';
        $user   = defined('DB_USER')   ? DB_USER   : '';
        $pass   = defined('DB_PASS')   ? DB_PASS   : '';

        // BD `inventario`: misma instancia y credenciales que portal_apm
        // (Windows auth si user vacío). Esquema completo con defaults/FK propios.
        $base = [
            'CharacterSet'           => 'UTF-8',
            'ReturnDatesAsStrings'   => true,
            'TrustServerCertificate' => true,
            'Encrypt'                => false,
        ];
        if ($user !== '') { $base['UID'] = $user; $base['PWD'] = $pass; }

        $this->conn = @sqlsrv_connect($server, ['Database' => 'inventario'] + $base);

        // Robustez: si la BD `inventario` no existe en este servidor, crearla
        // desde `master` y reconectar (para que el módulo funcione en cualquier equipo).
        if ($this->conn === false) {
            $master = @sqlsrv_connect($server, ['Database' => 'master'] + $base);
            if ($master !== false) {
                sqlsrv_query($master, "IF DB_ID('inventario') IS NULL CREATE DATABASE inventario;");
                sqlsrv_close($master);
                $this->conn = @sqlsrv_connect($server, ['Database' => 'inventario'] + $base);
            }
        }

        if ($this->conn === false) {
            $err = sqlsrv_errors(SQLSRV_ERR_ALL);
            throw new RuntimeException('No se pudo conectar a la base de datos de inventario: ' . ($err[0]['message'] ?? 'desconocido'));
        }
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConn()
    {
        return $this->conn;
    }

    /**
     * Ejecuta una consulta parametrizada (parámetros posicionales `?`).
     * Valores simples se envuelven como SQLSRV_PARAM_IN automáticamente.
     */
    public function query(string $sql, array $params = [])
    {
        if (empty($params)) {
            $stmt = sqlsrv_query($this->conn, $sql);
        } else {
            $normalized = [];
            foreach ($params as $p) {
                $normalized[] = is_array($p) ? $p : [$p, SQLSRV_PARAM_IN];
            }
            $stmt = sqlsrv_prepare($this->conn, $sql, $normalized);
            if ($stmt === false) { $this->throwError('Prepare failed'); }
            if (sqlsrv_execute($stmt) === false) { $this->throwError('Execute failed'); }
        }
        if ($stmt === false) { $this->throwError('Query failed'); }
        return $stmt;
    }

    public function fetch($stmt): ?array
    {
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        return ($row === false || $row === null) ? null : $row;
    }

    public function fetchAll($stmt): array
    {
        $rows = [];
        if ($stmt === false) return $rows;
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $rows[] = $row;
        }
        return $rows;
    }

    public function fetchColumn($stmt, int $col = 0)
    {
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_NUMERIC);
        return ($row === false || $row === null) ? null : ($row[$col] ?? null);
    }

    public function rowsAffected($stmt): int
    {
        $n = sqlsrv_rows_affected($stmt);
        return ($n === false || $n === null) ? 0 : $n;
    }

    public function lastInsertId(): ?int
    {
        $stmt = sqlsrv_query($this->conn, 'SELECT CAST(SCOPE_IDENTITY() AS INT) AS lid');
        if ($stmt === false) return null;
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        sqlsrv_free_stmt($stmt);
        return ($row && $row['lid'] !== null) ? (int)$row['lid'] : null;
    }

    public function beginTransaction(): void { sqlsrv_begin_transaction($this->conn); }
    public function commit(): void           { sqlsrv_commit($this->conn); }
    public function rollback(): void         { sqlsrv_rollback($this->conn); }
    public function free($stmt): void        { if ($stmt) sqlsrv_free_stmt($stmt); }

    private function throwError(string $msg): never
    {
        $err = sqlsrv_errors(SQLSRV_ERR_ALL);
        throw new RuntimeException($msg . ': ' . ($err[0]['message'] ?? 'unknown'));
    }
}
