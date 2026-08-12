<?php
/**
 * Controlador de Dashboard.
 * Reemplaza: bit_index.php, bit_dashboard_jefe.php
 */
class PortDashboardController extends PortController
{
    /**
     * GET /central/dashboard/index
     * Reemplaza: bit_index.php
     */
    public function index(): void
    {
        Auth::guard();

        /** @var DashboardModel $dashboard */
        $dashboard = $this->model('PortDashboardModel');

        $totales = $dashboard->totalesHoy();
        $ultimas = $dashboard->ultimasVisitas(5);

        $this->view('dashboard/index', [
            'pageTitle'  => 'Dashboard - Control de Visitas | Autoridad Portuaria de Manta',
            'totalHoy'   => $totales['total_hoy'],
            'activas'    => $totales['activas'],
            'ultimas'    => $ultimas,
            'msgAcceso'  => (($_GET['msg'] ?? '') === 'acceso_denegado'),
            'extraCss'   => ['public/css/portuaria/portal_portuario.css?v=1'],
        ]);
    }

    /**
     * GET /central/dashboard/jefe
     * Reemplaza: bit_dashboard_jefe.php
     */
    public function jefe(): void
    {
        Auth::guard();

        if (!Auth::canAccederDashboardJefe()) {
            $this->redirect('dashboard?msg=acceso_denegado');
            return;
        }

        // La versión completa (gráficos + feed de movimientos en vivo, polling)
        // vive como página propia en la raíz de la app — bit_dashboard_jefe.php.
        header('Location: bit_dashboard_jefe.php');
        exit;
    }

    /**
     * GET /central/dashboard/ejecutivo
     * Reemplaza: bit_dashboard_analitica.php
     * Incrusta el Dashboard Ejecutivo en Python (Streamlit), corriendo como
     * servicio en el mismo servidor. Sin permiso específico — igual que el
     * original, cualquier usuario logueado puede verlo.
     */
    public function ejecutivo(): void
    {
        Auth::guard();

        $this->view('dashboard/ejecutivo', [
            'pageTitle' => 'Dashboard Ejecutivo Python | Autoridad Portuaria de Manta',
            'dashboardUrl' => APM_DASHBOARD_EJECUTIVO_URL,
            'extraCss' => ['public/css/portuaria/portal_portuario.css?v=1'],
        ]);
    }
}
