<?php
/**
 * SqlSrvStatement — Wrapper profesional sobre el recurso nativo sqlsrv.
 *
 * Encapsula un statement resource retornado por sqlsrv_query() o sqlsrv_prepare()
 * y expone una interfaz orientada a objetos limpia y compatible con el resto del framework.
 *
 * Métodos expuestos:
 *   - fetch()       : Obtiene la siguiente fila como arreglo asociativo.
 *   - fetchAll()    : Obtiene todas las filas restantes como arreglo de arreglos asociativos.
 *   - fetchColumn() : Obtiene el valor de una sola columna de la siguiente fila.
 *   - closeCursor() : Libera el recurso del statement.
 *   - execute()     : Ejecuta un statement preparado con sqlsrv_prepare().
 *   - rowCount()    : Retorna el número de filas afectadas por INSERT/UPDATE/DELETE.
 *
 * @package PortalAPM\Core
 */

class SqlSrvStatement {

    /** @var resource|false El recurso nativo sqlsrv statement */
    private $stmt;

    /**
     * @param resource|false $stmt Recurso retornado por sqlsrv_query() o sqlsrv_prepare()
     * @throws \Exception Si el statement es inválido (la consulta falló)
     */
    public function __construct($stmt) {
        if ($stmt === false) {
            $errors = sqlsrv_errors(SQLSRV_ERR_ALL);
            $message = 'Error en consulta SQL Server';
            if ($errors) {
                $details = [];
                foreach ($errors as $error) {
                    $details[] = "[SQLSTATE {$error['SQLSTATE']}] {$error['message']} (Código: {$error['code']})";
                }
                $message .= ': ' . implode(' | ', $details);
            }
            throw new \Exception($message);
        }
        $this->stmt = $stmt;
    }

    /**
     * Obtiene la siguiente fila del resultado como arreglo asociativo.
     *
     * @return array|null Arreglo asociativo con los datos de la fila, o null si no hay más filas.
     */
    public function fetch(): ?array {
        $row = sqlsrv_fetch_array($this->stmt, SQLSRV_FETCH_ASSOC);
        return ($row === null || $row === false) ? null : $row;
    }

    /**
     * Obtiene todas las filas restantes del resultado.
     *
     * @return array Arreglo de arreglos asociativos. Arreglo vacío si no hay resultados.
     */
    public function fetchAll(): array {
        $rows = [];
        while ($row = sqlsrv_fetch_array($this->stmt, SQLSRV_FETCH_ASSOC)) {
            $rows[] = $row;
        }
        return $rows;
    }

    /**
     * Obtiene el valor de una columna específica de la siguiente fila.
     *
     * @param int $columnIndex Índice de la columna (base 0).
     * @return mixed|false Valor de la columna, o false si no hay más filas.
     */
    public function fetchColumn(int $columnIndex = 0) {
        $row = sqlsrv_fetch_array($this->stmt, SQLSRV_FETCH_NUMERIC);
        if ($row === null || $row === false) {
            return false;
        }
        return $row[$columnIndex] ?? false;
    }

    /**
     * Libera el recurso del statement y el cursor asociado.
     */
    public function closeCursor(): void {
        if ($this->stmt && is_resource($this->stmt)) {
            sqlsrv_free_stmt($this->stmt);
        }
        $this->stmt = null;
    }

    /**
     * Ejecuta un statement previamente preparado con sqlsrv_prepare().
     *
     * @return bool true si la ejecución fue exitosa.
     * @throws \Exception Si la ejecución falla.
     */
    public function execute(): bool {
        $result = sqlsrv_execute($this->stmt);
        if ($result === false) {
            $errors = sqlsrv_errors(SQLSRV_ERR_ALL);
            $message = 'Error al ejecutar statement preparado';
            if ($errors) {
                $details = [];
                foreach ($errors as $error) {
                    $details[] = "[SQLSTATE {$error['SQLSTATE']}] {$error['message']}";
                }
                $message .= ': ' . implode(' | ', $details);
            }
            throw new \Exception($message);
        }
        return true;
    }

    /**
     * Retorna el número de filas afectadas por la última operación INSERT, UPDATE o DELETE.
     *
     * @return int Número de filas afectadas, o -1 si no aplica.
     */
    public function rowCount(): int {
        $count = sqlsrv_rows_affected($this->stmt);
        return ($count === false || $count === -1) ? -1 : $count;
    }

    /**
     * Avanza al siguiente conjunto de resultados (para stored procedures que retornan múltiples result sets).
     *
     * @return bool true si se avanzó exitosamente, false si no hay más conjuntos.
     */
    public function nextResult(): bool {
        return sqlsrv_next_result($this->stmt) === true;
    }

    /**
     * Destructor: libera automáticamente el recurso si no fue cerrado manualmente.
     */
    public function __destruct() {
        $this->closeCursor();
    }
}
