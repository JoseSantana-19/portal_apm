<?php
require_once __DIR__ . '/config/app.php';

// Set up class autoloading
spl_autoload_register(function ($class) {
    $paths = [
        __DIR__ . '/core/',
        __DIR__ . '/models/',
        __DIR__ . '/controllers/',
    ];
    foreach ($paths as $base) {
        if (!is_dir($base)) continue;
        $directory = new RecursiveDirectoryIterator($base);
        $iterator = new RecursiveIteratorIterator($directory);
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getFilename() === $class . '.php') {
                require_once $file->getPathname();
                return;
            }
        }
    }
});

try {
    $db = new class extends Model {
        public function test(): array {
            $tablesStmt = $this->query("SELECT COUNT(*) as total FROM sys.tables WHERE is_ms_shipped = 0");
            $tableCount = $this->fetch($tablesStmt)['total'] ?? 0;
            $this->free($tablesStmt);
            
            $viewsStmt = $this->query("SELECT COUNT(*) as total FROM sys.views");
            $viewCount = $this->fetch($viewsStmt)['total'] ?? 0;
            $this->free($viewsStmt);
            
            $spStmt = $this->query("SELECT COUNT(*) as total FROM sys.procedures");
            $spCount = $this->fetch($spStmt)['total'] ?? 0;
            $this->free($spStmt);
            
            $sql = "SELECT t.name AS table_name, c.name AS column_name, ty.name AS data_type, 
                           c.max_length, c.is_nullable,
                           ISNULL((SELECT 1 FROM sys.index_columns ic 
                                   INNER JOIN sys.indexes i ON ic.object_id = i.object_id AND ic.index_id = i.index_id
                                   WHERE ic.object_id = t.object_id AND ic.column_id = c.column_id AND i.is_primary_key = 1), 0) as is_pk
                    FROM sys.tables t
                    INNER JOIN sys.columns c ON t.object_id = c.object_id
                    INNER JOIN sys.types ty ON c.user_type_id = ty.user_type_id
                    WHERE t.is_ms_shipped = 0
                    ORDER BY t.name, c.column_id";
            
            $dictStmt = $this->query($sql);
            $columns = $this->fetchAll($dictStmt);
            $this->free($dictStmt);
            
            return [
                'tables' => $tableCount,
                'views' => $viewCount,
                'procedures' => $spCount,
                'columns_count' => count($columns)
            ];
        }
    };
    
    $res = $db->test();
    echo "SUCCESS: " . json_encode($res) . "\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
