<?php
/**
 * Run migration script.
 * Uses the native sqlsrv driver. Splits SQL batches by GO separators.
 */

$root = dirname(__DIR__);
require_once "{$root}/core/Model.php";

class MigrationRunner extends Model {
    public function run() {
        $sqlFile = $root . '/db/update_menu.sql';
        
        if (!file_exists($sqlFile)) {
            echo "Error: Migration file not found at {$sqlFile}\n";
            exit(1);
        }

        echo "Connecting to SQL Server...\n";
        try {
            echo "Connection established successfully!\n";
        } catch (Exception $e) {
            echo "Error connecting: " . $e->getMessage() . "\n";
            exit(1);
        }

        $sqlContent = file_get_contents($sqlFile);
        
        // Split by GO batches (GO on its own line, case-insensitive)
        $batches = preg_split('/^\s*GO\s*$/mi', $sqlContent);

        echo "Running migration script batches...\n";
        try {
            $conn = self::db()->getConn();
            foreach ($batches as $idx => $batch) {
                $trimmed = trim($batch);
                if (empty($trimmed)) continue;
                
                echo "Executing Batch #" . ($idx + 1) . " (" . strlen($trimmed) . " bytes)...\n";
                
                $stmt = sqlsrv_query($conn, $trimmed);
                if ($stmt === false) {
                    $errors = sqlsrv_errors(SQLSRV_ERR_ALL);
                    throw new Exception("Batch #" . ($idx + 1) . " failed: " . ($errors[0]['message'] ?? 'Unknown error'));
                }
                sqlsrv_free_stmt($stmt);
            }
            echo "\nSUCCESS: Migration completed successfully!\n";
        } catch (Exception $e) {
            echo "\nERROR DURING MIGRATION: " . $e->getMessage() . "\n";
            exit(1);
        }
    }
}

$seeder = new MigrationRunner();
$seeder->run();
