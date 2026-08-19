<?php
/**
 * DATABASESTATEMENT.PHP - Adaptador de Sentencias SQL Nativo (Reemplazo de PDOStatement)
 * Provee preparación, binding de parámetros y recuperación de datos de forma unificada.
 */

class DatabaseStatement {
    private DatabaseConnection $db;
    private string $originalSql;
    private string $rewrittenSql;
    private array $paramNames = [];
    private array $boundValues = [];
    private $stmtResource = null; // Para sqlsrv y pgsql
    private $resultResource = null; // Para sqlite3 y pgsql
    private string $stmtName = ''; // Para pgsql

    public function __construct(DatabaseConnection $db, string $sql) {
        $this->db = $db;
        $this->originalSql = $sql;
        $this->parseParameters();
    }

    /**
     * Analiza la consulta y convierte parámetros nombrados (:param) a posicionales
     */
    private function parseParameters() {
        $sql = $this->originalSql;
        $driver = $this->db->getDriver();

        // Encontrar todos los :parametro
        // Evitar capturar :: de PostgreSQL (ej. CAST(x AS type))
        $pattern = '/(?<!:):([a-zA-Z0-9_]+)/';
        
        $this->paramNames = [];
        
        $rewritten = preg_replace_callback($pattern, function($matches) {
            $this->paramNames[] = ':' . $matches[1];
            return '?';
        }, $sql);

        // Si es PostgreSQL, cambiar los "?" por "$1", "$2", etc.
        if ($driver === 'pgsql') {
            $index = 1;
            $rewritten = preg_replace_callback('/\?/', function() use (&$index) {
                return '$' . ($index++);
            }, $rewritten);
            
            // Generar nombre de sentencia único para pg_prepare
            $this->stmtName = uniqid('stmt_', true);
        }

        $this->rewrittenSql = $rewritten;
    }

    /**
     * Enlaza un valor a un parámetro
     */
    public function bindValue($param, $value, $type = null) {
        // Asegurar que el nombre tenga el prefijo ":" si es un string y no lo tiene
        if (is_string($param) && strpos($param, ':') !== 0) {
            $param = ':' . $param;
        }
        $this->boundValues[$param] = $value;
        return $this;
    }

    /**
     * Ejecuta la sentencia preparada
     */
    public function execute(array $params = []): bool {
        $driver = $this->db->getDriver();
        $rawConn = $this->db->getRawConnection();

        // Mezclar parámetros pasados en execute con los enlazados previamente por bindValue
        $allParams = array_merge($this->boundValues, $params);

        // Limpiar nombres de llaves en $allParams para soportar tanto "id" como ":id"
        $normalizedParams = [];
        foreach ($allParams as $key => $val) {
            if (is_string($key) && strpos($key, ':') !== 0) {
                $normalizedParams[':' . $key] = $val;
            } else {
                $normalizedParams[$key] = $val;
            }
        }

        // Construir el array ordenado de valores para la consulta posicional
        $orderedValues = [];
        if (!empty($this->paramNames)) {
            foreach ($this->paramNames as $pName) {
                if (array_key_exists($pName, $normalizedParams)) {
                    $orderedValues[] = $normalizedParams[$pName];
                } else {
                    // Fallback a nulo si falta el parámetro
                    $orderedValues[] = null;
                }
            }
        } else {
            // Si no hay parámetros nombrados detectados, usar los parámetros secuenciales tal cual
            $orderedValues = array_values($params);
        }

        if ($driver === 'pgsql') {
            // PostgreSQL Nativo
            // Preparar la sentencia si no se ha hecho
            if (empty($this->stmtResource)) {
                $this->stmtResource = @pg_prepare($rawConn, $this->stmtName, $this->rewrittenSql);
                if (!$this->stmtResource) {
                    throw new Exception("Error al preparar sentencia (PostgreSQL): " . pg_last_error($rawConn));
                }
            }
            
            // Ejecutar la sentencia
            $this->resultResource = @pg_execute($rawConn, $this->stmtName, $orderedValues);
            if (!$this->resultResource) {
                throw new Exception("Error al ejecutar sentencia (PostgreSQL): " . pg_last_error($rawConn));
            }
            return true;

        } elseif ($driver === 'sqlsrv') {
            // SQL Server Nativo
            // Para evitar problemas de referencias con sqlsrv_prepare, usamos sqlsrv_query directamente
            // que es extremadamente rápido y seguro al pasarle el array de parámetros
            $this->stmtResource = @sqlsrv_query($rawConn, $this->rewrittenSql, $orderedValues);
            if (!$this->stmtResource) {
                $errors = sqlsrv_errors();
                $msg = "";
                $hasRealError = false;
                if ($errors) {
                    foreach ($errors as $err) {
                        $sqlState = $err['SQLSTATE'] ?? '';
                        if (substr($sqlState, 0, 2) !== '01') {
                            $hasRealError = true;
                        }
                        $msg .= $err['message'] . "\n";
                    }
                }
                if ($hasRealError) {
                    throw new Exception("Error al ejecutar sentencia (SQL Server): " . $msg . " | SQL: " . $this->rewrittenSql);
                }
            }
            return true;

        } else {
            // SQLite3 Nativo
            try {
                $stmt = $rawConn->prepare($this->rewrittenSql);
                if (!$stmt) {
                    return false;
                }
                
                foreach ($orderedValues as $idx => $val) {
                    // SQLite3 usa índices basados en 1 para parámetros posicionales
                    $stmt->bindValue($idx + 1, $val);
                }
                
                $this->resultResource = $stmt->execute();
                return ($this->resultResource !== false);
            } catch (Exception $e) {
                throw new Exception("Error al ejecutar sentencia (SQLite3): " . $e->getMessage());
            }
        }
    }

    /**
     * Recupera la siguiente fila de un conjunto de resultados
     */
    public function fetch() {
        $driver = $this->db->getDriver();

        if ($driver === 'pgsql') {
            if (!$this->resultResource) return false;
            $row = pg_fetch_assoc($this->resultResource);
            return $row ? $row : false;

        } elseif ($driver === 'sqlsrv') {
            if (!$this->stmtResource) return false;
            $row = sqlsrv_fetch_array($this->stmtResource, SQLSRV_FETCH_ASSOC);
            return $row ? $row : false;

        } else {
            // SQLite3
            if (!$this->resultResource) return false;
            $row = $this->resultResource->fetchArray(SQLITE3_ASSOC);
            return $row ? $row : false;
        }
    }

    /**
     * Recupera todas las filas del conjunto de resultados
     */
    public function fetchAll(?int $fetchMode = null, int $columnCol = 0): array {
        $results = [];
        if ($fetchMode === 7) { // 7 is PDO::FETCH_COLUMN
            while ($row = $this->fetch()) {
                $values = array_values($row);
                $results[] = array_key_exists($columnCol, $values) ? $values[$columnCol] : null;
            }
        } else {
            while ($row = $this->fetch()) {
                $results[] = $row;
            }
        }
        return $results;
    }

    /**
     * Recupera una única columna de la siguiente fila del conjunto de resultados
     */
    public function fetchColumn(int $columnNumber = 0) {
        $row = $this->fetch();
        if ($row === false) {
            return false;
        }
        $values = array_values($row);
        return array_key_exists($columnNumber, $values) ? $values[$columnNumber] : false;
    }

    /**
     * Devuelve el número de filas afectadas por la última sentencia SQL
     */
    public function rowCount(): int {
        $driver = $this->db->getDriver();

        if ($driver === 'pgsql') {
            return $this->resultResource ? pg_affected_rows($this->resultResource) : 0;
        } elseif ($driver === 'sqlsrv') {
            return $this->stmtResource ? (sqlsrv_rows_affected($this->stmtResource) ?? 0) : 0;
        } else {
            // SQLite3 no tiene rowCount directo en la sentencia de selección
            // Retorna cambios si fue una escritura
            return $this->db->getRawConnection()->changes();
        }
    }
}
