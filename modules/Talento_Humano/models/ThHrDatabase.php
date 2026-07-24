<?php
/**
 * ThHrDatabase — Conexión nativa sqlsrv (NO PDO) a la base de datos `Talento_Humano`.
 *
 * El módulo Talento Humano es autocontenido: vive en su propia BD `Talento_Humano`
 * (aislada de PORTAL_APM) pero usa la MISMA instancia de SQL Server y la MISMA
 * estructura MVC que portal_apm. Réplica de la API de core/Database.php e InvDatabase,
 * para que los modelos se escriban igual que el resto del proyecto pero apuntando a
 * la BD de Talento Humano. Parámetros posicionales `?` (nunca named params PDO).
 *
 * A diferencia de InvDatabase NO auto-crea la BD: `Talento_Humano` proviene de un
 * respaldo (.bak) con datos reales; si no existe se lanza un error explícito en
 * lugar de crear una BD vacía que enmascare el problema.
 */
class ThHrDatabase
{
    private static ?self $instance = null;
    private $conn = null;

    private function __construct()
    {
        $server = defined('DB_SERVER')  ? DB_SERVER  : '.\\VICTUS';
        $user   = defined('DB_USER')    ? DB_USER    : '';
        $pass   = defined('DB_PASS')    ? DB_PASS    : '';
        $dbName = defined('DB_TH_NAME') ? DB_TH_NAME : 'Talento_Humano';

        $base = [
            'Database'               => $dbName,
            'CharacterSet'           => 'UTF-8',
            'ReturnDatesAsStrings'   => true,
            'TrustServerCertificate' => defined('DB_TRUST_CERT') ? DB_TRUST_CERT : true,
            'Encrypt'                => defined('DB_ENCRYPT') ? DB_ENCRYPT : false,
        ];
        if ($user !== '') { $base['UID'] = $user; $base['PWD'] = $pass; }

        $this->conn = @sqlsrv_connect($server, $base);

        if ($this->conn === false) {
            $err = sqlsrv_errors(SQLSRV_ERR_ALL);
            throw new RuntimeException(
                "No se pudo conectar a la base de datos '{$dbName}'. " .
                "Restaure el respaldo Talento_Humano.bak en la instancia {$server}. Detalle: " .
                ($err[0]['message'] ?? 'desconocido')
            );
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
