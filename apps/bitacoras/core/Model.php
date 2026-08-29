<?php
/**
 * Base Model — sqlsrv native, NO PDO.
 * All module models extend this class.
 */
abstract class Model {

    protected static function db(): Database {
        return Database::getInstance();
    }

    /** Execute query, return raw stmt. */
    protected function query(string $sql, array $params = []) {
        return self::db()->query($sql, $params);
    }

    /** Fetch single row as assoc array. */
    protected function fetch($stmt): ?array {
        return self::db()->fetch($stmt);
    }

    /** Fetch all rows as array of assoc arrays. */
    protected function fetchAll($stmt): array {
        return self::db()->fetchAll($stmt);
    }

    /** Rows affected by last INSERT/UPDATE/DELETE. */
    protected function rowsAffected($stmt): int {
        return self::db()->rowsAffected($stmt);
    }

    /** Last auto-generated identity value (SCOPE_IDENTITY). */
    protected function lastInsertId(): ?int {
        return self::db()->lastInsertId();
    }

    protected function beginTransaction(): void { self::db()->beginTransaction(); }
    protected function commit(): void           { self::db()->commit(); }
    protected function rollback(): void         { self::db()->rollback(); }
    protected function free($stmt): void        { self::db()->free($stmt); }

    /** Build sqlsrv OUTPUT param array (pass variable by ref). */
    protected function outParam(&$var, ?int $phpType = null, ?int $sqlType = null): array {
        $phpType = $phpType ?? SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR);
        $sqlType = $sqlType ?? SQLSRV_SQLTYPE_NVARCHAR('max');
        return [&$var, SQLSRV_PARAM_INOUT, $phpType, $sqlType];
    }

    protected static function getLastError(): string {
        $errs = sqlsrv_errors(SQLSRV_ERR_ALL);
        if (!$errs) return 'Unknown error';
        return implode(' | ', array_map(fn($e) => "[{$e['SQLSTATE']}] {$e['message']}", $errs));
    }
}
