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

        (new NotificacionGeneradorModel())->generarSiCorresponde();

        $timeframe = trim($_GET['timeframe'] ?? '30d');
        $kpis      = $this->model->getKpisEjecutivo($timeframe);
        $alertas   = $this->model->getAlertasPendientes(8);

        $this->render('Central/dashboard/ejecutivo', [
            'pageTitle' => 'Dashboard Ejecutivo',
            'kpis'      => $kpis,
            'alertas'   => $alertas,
        ]);
    }

    public function operational(): void {
        $this->requireAuth();

        (new NotificacionGeneradorModel())->generarSiCorresponde();

        $timeframe = trim($_GET['timeframe'] ?? 'today');
        $kpis      = $this->model->getKpisOperativo($timeframe);
        $actividad = $this->model->getActividadReciente(25);

        $this->render('Central/dashboard/operativo', [
            'pageTitle' => 'Dashboard Operativo',
            'kpis'      => $kpis,
            'actividad' => $actividad,
        ]);
    }

    public function apiEjecutivo(): void {
        $this->requireAuth();
        $this->requireLevel(2);

        header('Content-Type: application/json; charset=utf-8');
        try {
            $timeframe = trim($_GET['timeframe'] ?? '30d');
            $kpis = $this->model->getKpisEjecutivo($timeframe);
            echo json_encode(['ok' => true, 'data' => $kpis, 'timestamp' => date('H:i:s')]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }

    public function apiOperativo(): void {
        $this->requireAuth();

        header('Content-Type: application/json; charset=utf-8');
        try {
            $timeframe = trim($_GET['timeframe'] ?? 'today');
            $kpis = $this->model->getKpisOperativo($timeframe);
            echo json_encode(['ok' => true, 'data' => $kpis, 'timestamp' => date('H:i:s')]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }

    public function apiDrilldown(): void {
        $this->requireAuth();

        header('Content-Type: application/json; charset=utf-8');
        try {
            $tipo = trim($_GET['tipo'] ?? '');
            $id   = $_GET['id'] ?? null;
            $data = $this->model->getDrilldown($tipo, $id);
            echo json_encode(['ok' => true, 'data' => $data]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }

    public function exportarExcel(): void {
        $this->requireAuth();
        $this->requireLevel(2);

        $kpis = $this->model->getKpisEjecutivo('30d');
        $filename = 'Reporte_Ejecutivo_APM_' . date('Ymd_His') . '.csv';

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $out = fopen('php://output', 'w');
        // BOM UTF-8 for Excel
        fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));

        // Header
        fputcsv($out, ['AUTORIDAD PORTUARIA DE MANTA - REPORTE EJECUTIVO CONSOLIDADO']);
        fputcsv($out, ['Fecha de Emisión', date('d/m/Y H:i:s')]);
        fputcsv($out, ['Emitido por', $_SESSION['nombre_completo'] ?? 'Usuario']);
        fputcsv($out, []);

        // 1. Resumen de Patrimonio
        fputcsv($out, ['--- RESUMEN DE PATRIMONIO Y ACTIVOS ---']);
        fputcsv($out, ['Métrica', 'Valor']);
        fputcsv($out, ['Valor Total Inventariado USD', '$' . number_format($kpis['patrimonio']['valor_total'], 2)]);
        fputcsv($out, ['Total de Bienes Físicos', $kpis['patrimonio']['total_bienes']]);
        fputcsv($out, ['Bienes Activos / Operativos', $kpis['patrimonio']['bienes_activos']]);
        fputcsv($out, ['Bienes en Mantenimiento', $kpis['patrimonio']['bienes_mantenimiento']]);
        fputcsv($out, ['Tasa de Disponibilidad Operativa', $kpis['patrimonio']['tasa_disponibilidad'] . '%']);
        fputcsv($out, []);

        // 2. Categorías de Bienes
        fputcsv($out, ['--- TOP CATEGORÍAS DE BIENES ---']);
        fputcsv($out, ['Categoría', 'Cantidad', 'Valor Total USD']);
        foreach ($kpis['patrimonio']['top_categorias'] as $cat) {
            fputcsv($out, [$cat['categoria'], $cat['cantidad'], '$' . number_format((float)$cat['valor_total'], 2)]);
        }
        fputcsv($out, []);

        // 3. Talento Humano
        fputcsv($out, ['--- RESUMEN DE TALENTO HUMANO ---']);
        fputcsv($out, ['Personal Activo', $kpis['talento']['activos']]);
        fputcsv($out, ['Masa Salarial Mensual USD', '$' . number_format($kpis['talento']['masa_salarial'], 2)]);
        fputcsv($out, ['Sueldo Promedio USD', '$' . number_format($kpis['talento']['sueldo_promedio'], 2)]);
        fputcsv($out, ['Total Unidades Organizacionales', $kpis['talento']['total_direcciones']]);
        fputcsv($out, []);

        fputcsv($out, ['--- PERSONAL POR DIRECCIÓN ---']);
        fputcsv($out, ['Dirección / Unidad', 'Total Servidores']);
        foreach ($kpis['talento']['top_unidades'] as $u) {
            fputcsv($out, [$u['unidad'], $u['total']]);
        }
        fputcsv($out, []);

        // 4. Operaciones Portuarias
        fputcsv($out, ['--- OPERACIONES PORTUARIAS Y SEGURIDAD ---']);
        fputcsv($out, ['Visitas en Recinto Portuario', $kpis['seguridad_operaciones']['visitas_en_puerto']]);
        fputcsv($out, ['Visitas del Periodo', $kpis['seguridad_operaciones']['visitas_periodo']]);
        fputcsv($out, ['Cámaras CCTV Activas', $kpis['seguridad_operaciones']['camaras_operativas']]);
        fputcsv($out, ['Tasa de Éxito en Auditoría Transaccional', $kpis['gobernanza']['auditorias_exito_pct'] . '%']);

        fclose($out);
        exit;
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
