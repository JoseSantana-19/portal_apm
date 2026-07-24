<?php
/**
 * BienesController. Handles Control de Bienes / Inventario Module.
 */
class BienesController extends Controller {

    public function __construct() {
        parent::__construct();
        $this->requireAuth();
    }

    public function index(): void {
        $userId = $_SESSION['user_id'];
        $menuObj = new Menu();
        $userMenu = $menuObj->getUserMenu($userId);

        // Fetch some realistic mock inventory stats
        $stats = [
            'total_items' => 1240,
            'assigned' => 985,
            'maintenance' => 15,
            'available' => 240
        ];

        // Mock recent asset movements
        $recentMovements = [
            ['id' => 'ACT-0098', 'description' => 'Laptop Dell Latitude 5440', 'responsible' => 'Juan Carlos Pérez', 'date' => '2026-05-20', 'type' => 'ASIGNACIÓN'],
            ['id' => 'ACT-0104', 'description' => 'Monitor LG Ultrawide 29"', 'responsible' => 'María Belén Solórzano', 'date' => '2026-05-18', 'type' => 'DEVOLUCIÓN'],
            ['id' => 'ACT-0052', 'description' => 'Impresora HP LaserJet M404', 'responsible' => 'Área de Facturación', 'date' => '2026-05-15', 'type' => 'MANTENIMIENTO']
        ];

        $this->render('Bienes_Control_de_bienes/index', [
            'title' => 'Control de Bienes & Inventario — Portal APM',
            'userMenu' => $userMenu,
            'stats' => $stats,
            'movements' => $recentMovements
        ]);
    }
}
