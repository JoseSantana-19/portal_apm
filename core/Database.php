<?php
/**
 * Database — Singleton sqlsrv connection manager.
 * NO PDO. Uses native sqlsrv_* driver exclusively.
 */
class Database {
    private static ?self $instance = null;
    private $conn = null;

    private function __construct() {
        $server = defined('DB_SERVER') ? DB_SERVER : '(local)';
        $dbName = defined('DB_NAME')   ? DB_NAME   : 'PORTAL_APM';
        $user   = defined('DB_USER')   ? DB_USER   : '';
        $pass   = defined('DB_PASS')   ? DB_PASS   : '';

        $info = [
            'Database'               => $dbName,
            'CharacterSet'           => 'UTF-8',
            'ReturnDatesAsStrings'   => true,
            'TrustServerCertificate' => defined('DB_TRUST_CERT') ? DB_TRUST_CERT : true,
            'Encrypt'                => defined('DB_ENCRYPT')    ? DB_ENCRYPT    : false,
        ];
        if ($user !== '') {
            $info['UID'] = $user;
            $info['PWD'] = $pass;
        }

        sqlsrv_configure("WarningsReturnAsErrors", 0);
        $this->conn = sqlsrv_connect($server, $info);
        if ($this->conn === false) {
            $err = sqlsrv_errors(SQLSRV_ERR_ERRORS);
            throw new RuntimeException('DB connect failed: ' . ($err[0]['message'] ?? 'unknown'));
        }
    }

    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConn() {
        return $this->conn;
    }

    /**
     * Execute parameterized query.
     * Simple values auto-wrapped as IN params.
     * For OUTPUT params pass [&$var, SQLSRV_PARAM_INOUT, SQLSRV_PHPTYPE_INT, SQLSRV_SQLTYPE_INT].
     */
    public function query(string $sql, array $params = []) {
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

    public function fetch($stmt): ?array {
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        return ($row === false || $row === null) ? null : $row;
    }

    public function fetchAll($stmt): array {
        $rows = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $rows[] = $row;
        }
        return $rows;
    }

    public function rowsAffected($stmt): int {
        $n = sqlsrv_rows_affected($stmt);
        return ($n === false || $n === null) ? 0 : $n;
    }

    public function lastInsertId(): ?int {
        $stmt = sqlsrv_query($this->conn, 'SELECT SCOPE_IDENTITY() AS lid');
        if ($stmt === false) return null;
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        sqlsrv_free_stmt($stmt);
        return ($row && $row['lid'] !== null) ? (int)$row['lid'] : null;
    }

    public function beginTransaction(): void { sqlsrv_begin_transaction($this->conn); }
    public function commit(): void           { sqlsrv_commit($this->conn); }
    public function rollback(): void         { sqlsrv_rollback($this->conn); }
    public function free($stmt): void        { if ($stmt) sqlsrv_free_stmt($stmt); }

    private function throwError(string $msg): void {
        $err = sqlsrv_errors(SQLSRV_ERR_ALL);
        throw new RuntimeException($msg . ': ' . ($err[0]['message'] ?? 'unknown'));
    }

    public static function reset(): void {
        if (self::$instance !== null && self::$instance->conn) sqlsrv_close(self::$instance->conn);
        self::$instance = null;
    }
}
