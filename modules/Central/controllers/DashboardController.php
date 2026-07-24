<?php
class DashboardController extends Controller {

    private DashboardModel $model;

    public function __construct() {
        parent::__construct();
        $this->model = new DashboardModel();
    }

    public function index(): void {
        $this->requireAuth();
        $nivel = (int)($_SESSION['nivel_jerarquia'] ?? 0);

        // Managers (nivel >= 2) → executive dashboard; everyone else → operational
        if ($nivel >= 2) {
            $this->executive();
        } else {
            $this->operational();
        }
    }

    public function executive(): void {
        $this->requireAuth();
        $this->requireLevel(2);

        $kpis    = $this->model->getKpisEjecutivo();
        $alertas = $this->model->getAlertasPendientes(8);

        $this->render('Central/dashboard/ejecutivo', [
            'pageTitle' => 'Dashboard Ejecutivo',
            'kpis'      => $kpis,
            'alertas'   => $alertas,
        ]);
    }

    public function operational(): void {
        $this->requireAuth();

        $kpis      = $this->model->getKpisOperativo();
        $actividad = $this->model->getActividadReciente(15);

        $this->render('Central/dashboard/operativo', [
            'pageTitle' => 'Dashboard Operativo',
            'kpis'      => $kpis,
            'actividad' => $actividad,
        ]);
    }

    public function reportes(): void {
        $this->requireAuth();
        $auditoria = $this->model->getAuditRecent(20);

        $this->render('Central/dashboard/reportes', [
            'pageTitle' => 'Reportes Globales',
            'auditoria' => $auditoria,
        ]);
    }
}
