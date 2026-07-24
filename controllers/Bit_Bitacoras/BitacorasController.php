<?php
/**
 * BitacorasController. Handles Bitácoras / Shift Logs Module.
 */
class BitacorasController extends Controller {

    public function __construct() {
        parent::__construct();
        $this->requireAuth();
    }

    public function index(): void {
        $userId = $_SESSION['user_id'];
        $menuObj = new Menu();
        $userMenu = $menuObj->getUserMenu($userId);

        // Mock operational logs statistics
        $stats = [
            'total_shifts_logged' => 450,
            'active_patrols' => 4,
            'incidents_reported' => 2,
            'operational_status' => '100% OPERATIVO'
        ];

        // Mock recent shift patrol logs
        $shiftLogs = [
            ['shift' => 'Matutino B', 'guard' => 'Sgto. Segundo Cedeño', 'incident' => 'Revisión y apertura del Muelle 2 completada sin anomalías', 'date' => 'Hoy, 08:30'],
            ['shift' => 'Nocturno A', 'guard' => 'Cabo Moreira Solís', 'incident' => 'Control perimetral exterior; se reporta luminaria dañada en patio 4', 'date' => 'Ayer, 23:45'],
            ['shift' => 'Nocturno A', 'guard' => 'Cabo Moreira Solís', 'incident' => 'Arribo de navío de carga internacional portando contenedor sellado', 'date' => 'Ayer, 21:10']
        ];

        $this->render('Bit_Bitacoras/index', [
            'title' => 'Bitácoras Operativas Portuarias — Portal APM',
            'userMenu' => $userMenu,
            'stats' => $stats,
            'logs' => $shiftLogs
        ]);
    }
}
