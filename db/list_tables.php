<?php
/**
 * List all tables in the connected SQL Server database.
 * Uses the native sqlsrv driver.
 */

$root = dirname(__DIR__);
require_once "{$root}/core/Model.php";

class TableLister extends Model {
    public function run() {
        $conn = self::db()->getConn();
        $stmt = sqlsrv_query($conn, "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE = 'BASE TABLE'");
        
        if ($stmt === false) {
            echo "Error querying tables: " . print_r(sqlsrv_errors(), true) . "\n";
            return;
        }

        $tables = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_NUMERIC)) {
            $tables[] = $row[0];
        }
        sqlsrv_free_stmt($stmt);

        echo "=== TABLES ===\n";
        print_r($tables);
    }
}
$l = new TableLister();
$l->run();
