<?php
/**
 * ThDatabase. Handles independent database connection for the Talento Humano external system.
 * Uses the native Microsoft sqlsrv driver (sqlsrv_* functions).
 *
 * Simulates query execution on a dedicated Human Resources server.
 *
 * @package PortalAPM\Core
 */

require_once __DIR__ . '/SqlSrvStatement.php';
require_once __DIR__ . '/Database.php';

class ThDatabase {

    /** @var resource|null Conexión nativa sqlsrv para Talento Humano */
    private static $db = null;

    /**
     * Establece y retorna la conexión sqlsrv al servidor de Talento Humano.
     *
     * @return resource Conexión sqlsrv activa.
     * @throws \Exception Si la conexión falla.
     */
    public static function connect() {
        if (self::$db === null) {
            self::$db = Database::getInstance()->getConn();
        }
        return self::$db;
    }

    /**
     * Retorna la conexión activa para uso directo (transacciones, etc.).
     *
     * @return resource Conexión sqlsrv activa.
     */
    public static function getConnection() {
        return self::connect();
    }

    /**
     * Ejecuta una consulta parametrizada en la base de datos de Talento Humano.
     *
     * @param string $sql    Consulta SQL con placeholders '?'.
     * @param array  $params Valores para los placeholders.
     * @return SqlSrvStatement Wrapper sobre el statement ejecutado.
     * @throws \Exception Si la consulta falla.
     */
    public static function query(string $sql, array $params = []): SqlSrvStatement {
        $conn = self::connect();
        $paramsCopy = array_values($params);
        $stmt = sqlsrv_query($conn, $sql, $paramsCopy);
        return new SqlSrvStatement($stmt);
    }

    /**
     * Inicia una transacción en la conexión de Talento Humano.
     *
     * @return bool true si se inició exitosamente.
     * @throws \Exception Si falla.
     */
    public static function beginTransaction(): bool {
        $conn = self::connect();
        $result = sqlsrv_begin_transaction($conn);
        if ($result === false) {
            throw new \Exception('Failed to begin TH transaction');
        }
        return true;
    }

    /**
     * Confirma la transacción actual de Talento Humano.
     *
     * @return bool true si se confirmó.
     */
    public static function commit(): bool {
        return sqlsrv_commit(self::$db);
    }

    /**
     * Revierte la transacción actual de Talento Humano.
     *
     * @return bool true si se revirtió.
     */
    public static function rollback(): bool {
        return sqlsrv_rollback(self::$db);
    }
}
