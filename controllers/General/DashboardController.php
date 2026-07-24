<?php
/**
 * DashboardController. Handles the landing dashboard portal and widget loading.
 */
class DashboardController extends Controller {

    private Menu $menuModel;

    public function __construct() {
        parent::__construct();
        $this->requireAuth();
        $this->menuModel = new Menu();
    }

    /**
     * Main dashboard page
     */
    public function index(): void {
        $userId = $_SESSION['user_id'];
        
        // Fetch menus and profile info
        $userMenu = $this->menuModel->getUserMenu($userId);
        
        $userModel = new Usuario();
        $profile = $userModel->getProfile($userId);

        // Fetch some metrics to display dynamically on the dashboard
        $db = new class extends Model {
            public function getStats(): array {
                try {
                    $empCount = $this->query("SELECT COUNT(*) as total FROM dbo.vw_FichaEmpleado WHERE activo = 1")->fetch()['total'] ?? 0;
                    $deptCount = $this->query("SELECT COUNT(*) as total FROM dbo.Departamentos_Modulos WHERE tipo_nodo = 'DEPARTAMENTO' AND habilitado = 1")->fetch()['total'] ?? 0;
                    $contractCount = $this->query("SELECT COUNT(*) as total FROM dbo.TH_Contratos WHERE estado = 'VIGENTE'")->fetch()['total'] ?? 0;
                    $medicalCount = $this->query("SELECT COUNT(*) as total FROM dbo.TH_NovedadesMedicas")->fetch()['total'] ?? 0;
                    
                    return [
                        'employees' => $empCount,
                        'departments' => $deptCount,
                        'contracts' => $contractCount,
                        'medicals' => $medicalCount
                    ];
                } catch (Exception $e) {
                    return ['employees' => 0, 'departments' => 0, 'contracts' => 0, 'medicals' => 0];
                }
            }
        };

        $stats = $db->getStats();

        $this->render('General/dashboard/index', [
            'title' => 'Portal de Integración — Autoridad Portuaria de Manta',
            'profile' => $profile,
            'userMenu' => $userMenu,
            'stats' => $stats
        ]);
    }
}
