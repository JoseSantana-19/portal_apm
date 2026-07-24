<?php
/**
 * Command line database seeder runner.
 * Connects via the native sqlsrv driver and runs the SQL script batch by batch (splitting by GO).
 */

$root = dirname(__DIR__);
require_once "{$root}/core/Model.php";

class SeederRunner extends Model {
    public function run() {
        $root = dirname(__DIR__);
        $seedFile = "{$root}/db/seed_large.sql";
        
        if (!file_exists($seedFile)) {
            echo "Error: Seed file not found at {$seedFile}\n";
            exit(1);
        }

        echo "Connecting to SQL Server...\n";
        try {
            // Instantiating SeederRunner automatically triggers connect() in parent Model constructor
            echo "Connection established successfully!\n";
        } catch (Exception $e) {
            echo "Error connecting: " . $e->getMessage() . "\n";
            exit(1);
        }

        $sqlContent = file_get_contents($seedFile);
        
        // Split by GO batches on their own line (case-insensitive)
        $batches = preg_split('/^\s*GO\s*$/mi', $sqlContent);

        echo "Running seed script batches...\n";
        self::beginTransaction();
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
            self::commit();
            echo "\nSUCCESS: Seeding completed successfully! Check the SQL Server console logs.\n";
        } catch (Exception $e) {
            self::rollback();
            echo "\nERROR DURING SEEDING: " . $e->getMessage() . "\n";
            exit(1);
        }
    }
}

$seeder = new SeederRunner();
$seeder->run();
