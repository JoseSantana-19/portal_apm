<?php
/**
 * DATABASECONNECTION.PHP - Adaptador de Conexión de Base de Datos Nativo (Reemplazo de PDO)
 * Soporta de manera transparente sqlsrv, pgsql y sqlite3 usando las extensiones nativas de PHP.
 */

require_once ROOT_PATH . 'core/DatabaseStatement.php';

if (!class_exists('PDO')) {
    class PDO {
        const FETCH_ASSOC = 2;
        const FETCH_NUM = 3;
        const FETCH_BOTH = 4;
        const FETCH_OBJ = 5;
        const FETCH_BOUND = 6;
        const FETCH_COLUMN = 7;
        const FETCH_CLASS = 8;
        const ATTR_ERRMODE = 3;
        const ERRMODE_EXCEPTION = 2;
        const ATTR_DEFAULT_FETCH_MODE = 19;
    }
}

class DatabaseConnection {
    private $driver;
    private $conn;
    private bool $inTransaction = false;

    public function __construct(?string $dbName = null) {
        $this->driver = defined('DB_DRIVER') ? DB_DRIVER : 'sqlite';
        $targetDb = $dbName ?: (defined('DB_NAME') ? DB_NAME : 'inventario');

        if ($this->driver === 'pgsql') {
            $port = defined('DB_PORT') && DB_PORT !== '' ? DB_PORT : '5432';
            $connectionString = "host=" . DB_HOST . " port=" . $port . " dbname=" . $targetDb;
            if (!empty(DB_USER)) {
                $connectionString .= " user=" . DB_USER . " password=" . DB_PASS;
            }
            $this->conn = @pg_connect($connectionString);
            if (!$this->conn) {
                die("Error de conexión a PostgreSQL (Nativo): " . pg_last_error());
            }
        } elseif ($this->driver === 'sqlsrv') {
            $connectionInfo = [
                "Database" => $targetDb,
                "CharacterSet" => "UTF-8",
                "ReturnDatesAsStrings" => true
            ];
            if (!empty(DB_USER)) {
                $connectionInfo["UID"] = DB_USER;
                $connectionInfo["PWD"] = DB_PASS;
            }
            if (defined('DB_TRUST_CERT') && DB_TRUST_CERT) {
                $connectionInfo["TrustServerCertificate"] = true;
            }

            $this->conn = @sqlsrv_connect(DB_HOST, $connectionInfo);
            if (!$this->conn) {
                // Intentar conectar al servidor sin base de datos (conecta a master) para crearla
                $masterInfo = $connectionInfo;
                unset($masterInfo["Database"]);
                $masterConn = @sqlsrv_connect(DB_HOST, $masterInfo);
                if ($masterConn) {
                    $createDbSql = "IF NOT EXISTS (SELECT name FROM sys.databases WHERE name = N'" . str_replace("'", "''", $targetDb) . "') CREATE DATABASE [" . $targetDb . "]";
                    @sqlsrv_query($masterConn, $createDbSql);
                    @sqlsrv_close($masterConn);
                    
                    // Reintentar la conexión normal con la base de datos ya creada
                    $this->conn = @sqlsrv_connect(DB_HOST, $connectionInfo);
                }
            }

            if (!$this->conn) {
                $errors = sqlsrv_errors();
                $msg = "";
                if ($errors) {
                    foreach ($errors as $err) {
                        $msg .= $err['message'] . "\n";
                    }
                }
                die("Error de conexión a Microsoft SQL Server (Nativo): " . $msg);
            }
        } else {
            // SQLite3
            $dbPath = defined('DB_SQLITE_PATH') ? DB_SQLITE_PATH : (ROOT_PATH . 'database.sqlite');
            try {
                $this->conn = new SQLite3($dbPath);
                $this->conn->enableExceptions(true);
                // Habilitar claves foráneas
                $this->conn->exec("PRAGMA foreign_keys = ON;");
            } catch (Exception $e) {
                die("Error de conexión a SQLite3 (Nativo): " . $e->getMessage());
            }
        }
    }

    public function getRawConnection() {
        return $this->conn;
    }

    public function getDriver(): string {
        return $this->driver;
    }

    /**
     * Dummy method for compatibility with PDO::setAttribute
     */
    public function setAttribute($attribute, $value): bool {
        return true;
    }

    /**
     * Prepara una sentencia SQL para su ejecución
     */
    public function prepare(string $sql): DatabaseStatement {
        return new DatabaseStatement($this, $sql);
    }

    /**
     * Ejecuta una consulta SQL directa y devuelve un conjunto de resultados
     */
    public function query(string $sql): DatabaseStatement {
        $stmt = $this->prepare($sql);
        $stmt->execute();
        return $stmt;
    }

    /**
     * Ejecuta una sentencia SQL y devuelve el número de filas afectadas
     */
    public function exec(string $sql): int {
        if ($this->driver === 'pgsql') {
            $result = @pg_query($this->conn, $sql);
            if (!$result) {
                throw new Exception("Error en exec (PostgreSQL): " . pg_last_error($this->conn));
            }
            return pg_affected_rows($result);
        } elseif ($this->driver === 'sqlsrv') {
            $result = @sqlsrv_query($this->conn, $sql);
            if (!$result) {
                $errors = sqlsrv_errors();
                $msg = "";
                if ($errors) {
                    foreach ($errors as $err) {
                        $msg .= $err['message'] . "\n";
                    }
                }
                throw new Exception("Error en exec (SQL Server): " . $msg);
            }
            return sqlsrv_rows_affected($result) ?? 0;
        } else {
            // SQLite3
            try {
                $this->conn->exec($sql);
                return $this->conn->changes();
            } catch (Exception $e) {
                throw new Exception("Error en exec (SQLite3): " . $e->getMessage());
            }
        }
    }

    /**
     * Inicia una transacción
     */
    public function beginTransaction(): bool {
        if ($this->inTransaction) {
            return false;
        }

        if ($this->driver === 'pgsql') {
            $result = @pg_query($this->conn, "BEGIN");
            $this->inTransaction = ($result !== false);
        } elseif ($this->driver === 'sqlsrv') {
            $result = @sqlsrv_begin_transaction($this->conn);
            $this->inTransaction = $result;
        } else {
            // SQLite3
            try {
                $this->conn->exec("BEGIN TRANSACTION");
                $this->inTransaction = true;
            } catch (Exception $e) {
                $this->inTransaction = false;
            }
        }
        return $this->inTransaction;
    }

    /**
     * Confirma una transacción
     */
    public function commit(): bool {
        if (!$this->inTransaction) {
            return false;
        }

        if ($this->driver === 'pgsql') {
            $result = @pg_query($this->conn, "COMMIT");
        } elseif ($this->driver === 'sqlsrv') {
            $result = @sqlsrv_commit($this->conn);
        } else {
            // SQLite3
            try {
                $this->conn->exec("COMMIT");
                $result = true;
            } catch (Exception $e) {
                $result = false;
            }
        }

        if ($result) {
            $this->inTransaction = false;
            return true;
        }
        return false;
    }

    /**
     * Revierte una transacción
     */
    public function rollBack(): bool {
        if (!$this->inTransaction) {
            return false;
        }

        if ($this->driver === 'pgsql') {
            $result = @pg_query($this->conn, "ROLLBACK");
        } elseif ($this->driver === 'sqlsrv') {
            $result = @sqlsrv_rollback($this->conn);
        } else {
            // SQLite3
            try {
                $this->conn->exec("ROLLBACK");
                $result = true;
            } catch (Exception $e) {
                $result = false;
            }
        }

        if ($result) {
            $this->inTransaction = false;
            return true;
        }
        return false;
    }

    public function inTransaction(): bool {
        return $this->inTransaction;
    }

    /**
     * Devuelve el ID de la última fila insertada
     */
    public function lastInsertId() {
        if ($this->driver === 'pgsql') {
            $res = @pg_query($this->conn, "SELECT lastval()");
            if ($res) {
                $row = pg_fetch_row($res);
                return $row ? (int)$row[0] : 0;
            }
            return 0;
        } elseif ($this->driver === 'sqlsrv') {
            $res = @sqlsrv_query($this->conn, "SELECT @@IDENTITY AS id");
            if ($res) {
                $row = sqlsrv_fetch_array($res, SQLSRV_FETCH_ASSOC);
                return $row ? (int)$row['id'] : 0;
            }
            return 0;
        } else {
            // SQLite3
            return $this->conn->lastInsertRowID();
        }
    }
}
