<?php
/**
 * DatabaseController. Manages interactive ERD visualization and live data dictionary.
 */
class DatabaseController extends Controller {

    public function __construct() {
        parent::__construct();
        $this->requireAuth();
    }

    public function index(): void {
        $userId = $_SESSION['user_id'];
        $menuObj = new Menu();
        $userMenu = $menuObj->getUserMenu($userId);
        
        // Fetch database stats using system catalog views
        $db = new class extends Model {
            public function getDbDictionary(): array {
                try {
                    // Total counts
                    $tablesStmt = $this->query("SELECT COUNT(*) as total FROM sys.tables WHERE is_ms_shipped = 0");
                    $tableCount = $tablesStmt->fetch()['total'] ?? 0;
                    $tablesStmt->closeCursor();

                    $viewsStmt = $this->query("SELECT COUNT(*) as total FROM sys.views");
                    $viewCount = $viewsStmt->fetch()['total'] ?? 0;
                    $viewsStmt->closeCursor();

                    $spStmt = $this->query("SELECT COUNT(*) as total FROM sys.procedures");
                    $spCount = $spStmt->fetch()['total'] ?? 0;
                    $spStmt->closeCursor();

                    // Retrieve tables and column dictionaries
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
                    $columns = $dictStmt->fetchAll();
                    $dictStmt->closeCursor();

                    $dictionary = [];
                    foreach ($columns as $col) {
                        $tblName = $col['table_name'];
                        if (!isset($dictionary[$tblName])) {
                            $dictionary[$tblName] = [];
                        }
                        $dictionary[$tblName][] = [
                            'column' => $col['column_name'],
                            'type' => $col['data_type'],
                            'max_length' => $col['max_length'],
                            'nullable' => $col['is_nullable'],
                            'pk' => $col['is_pk']
                        ];
                    }

                    return [
                        'stats' => [
                            'tables' => $tableCount,
                            'views' => $viewCount,
                            'procedures' => $spCount
                        ],
                        'dictionary' => $dictionary
                    ];
                } catch (Exception $e) {
                    $logDir = dirname(dirname(__DIR__)) . '/controllers/Control_acceso/log';
                    if (!is_dir($logDir)) {
                        @mkdir($logDir, 0777, true);
                    }
                    @file_put_contents($logDir . '/log_erd_error.txt', date('Y-m-d H:i:s') . " - Error: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n", FILE_APPEND);
                    return [
                        'stats' => ['tables' => 0, 'views' => 0, 'procedures' => 0],
                        'dictionary' => []
                    ];
                }
            }
        };

        $dbInfo = $db->getDbDictionary();

        $this->render('General/dashboard/erd', [
            'title' => 'Esquema Relacional ERD — Portal APM',
            'userMenu' => $userMenu,
            'dbStats' => $dbInfo['stats'],
            'dictionary' => $dbInfo['dictionary']
        ]);
    }
}
