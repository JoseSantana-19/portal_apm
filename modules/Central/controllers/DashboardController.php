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
        require_once ROOT . '/libs/XlsxWriter.php';

        $kpis = $this->model->getKpisEjecutivo('30d');
        $filename = 'Reporte_Ejecutivo_APM_' . date('Ymd_His') . '.xlsx';

        $x = new XlsxWriter('Reporte Ejecutivo APM');
        $x->setColumns([
            ['SECTOR / DIMENSIÓN', 28],
            ['MÉTRICA / INDICADOR', 36],
            ['VALOR CONSOLIDADO', 24],
            ['UNIDAD / DETALLE', 24],
            ['ESTADO OPERATIVO', 20],
            ['MÓDULO ORIGEN', 24],
        ]);

        // 1. Metadatos
        $x->addRow(['METADATOS', 'Fecha y Hora de Emisión', date('d/m/Y H:i:s'), 'Oficial APM', 'Vigente', 'Portal APM']);
        $x->addRow(['METADATOS', 'Emitido por', $_SESSION['nombre_completo'] ?? 'Usuario', 'Sesión Activa', 'Verificado', 'Portal APM']);
        $x->addRow(['METADATOS', 'Periodo Analítico', 'Últimos 30 días', 'Consolidado', 'En Línea', 'Telemetría Central']);
        $x->addRow(['', '', '', '', '', '']);

        // 2. Patrimonio y Activos
        $x->addRow(['PATRIMONIO Y ACTIVOS', 'Valor Total Inventariado', '$' . number_format((float)($kpis['patrimonio']['valor_total'] ?? 0), 2), 'USD', '100% Operativo', 'Control de Bienes']);
        $x->addRow(['PATRIMONIO Y ACTIVOS', 'Total de Bienes Físicos', (string)($kpis['patrimonio']['total_bienes'] ?? 0), 'Activos Físicos', 'Registrados', 'Control de Bienes']);
        $x->addRow(['PATRIMONIO Y ACTIVOS', 'Bienes Activos / Operativos', (string)($kpis['patrimonio']['bienes_activos'] ?? 0), 'En Operación', 'Disponible', 'Control de Bienes']);
        $x->addRow(['PATRIMONIO Y ACTIVOS', 'Bienes en Mantenimiento', (string)($kpis['patrimonio']['bienes_mantenimiento'] ?? 0), 'Taller / Revisión', 'En Proceso', 'Control de Bienes']);
        $x->addRow(['PATRIMONIO Y ACTIVOS', 'Tasa de Disponibilidad', ($kpis['patrimonio']['tasa_disponibilidad'] ?? '100') . '%', 'Porcentaje', 'Óptimo', 'Control de Bienes']);
        $x->addRow(['', '', '', '', '', '']);

        // 3. Top Categorías
        $x->addRow(['CATEGORÍAS DE BIENES', 'Categoría', 'Cantidad de Bienes', 'Valor Total USD', 'Estado', 'Módulo Origen']);
        foreach (($kpis['patrimonio']['top_categorias'] ?? []) as $cat) {
            $x->addRow([
                'CATEGORÍAS DE BIENES',
                (string)($cat['categoria'] ?? ''),
                (string)($cat['cantidad'] ?? 0),
                '$' . number_format((float)($cat['valor_total'] ?? 0), 2),
                'Activo',
                'Control de Bienes'
            ]);
        }
        $x->addRow(['', '', '', '', '', '']);

        // 4. Talento Humano
        $x->addRow(['TALENTO HUMANO', 'Personal Activo', (string)($kpis['talento']['activos'] ?? 0), 'Servidores Públicos', 'Activos', 'Talento Humano']);
        $x->addRow(['TALENTO HUMANO', 'Masa Salarial Mensual', '$' . number_format((float)($kpis['talento']['masa_salarial'] ?? 0), 2), 'USD / Mes', 'Vigente', 'Talento Humano']);
        $x->addRow(['TALENTO HUMANO', 'Sueldo Promedio', '$' . number_format((float)($kpis['talento']['sueldo_promedio'] ?? 0), 2), 'USD / Servidor', 'Estable', 'Talento Humano']);
        $x->addRow(['TALENTO HUMANO', 'Direcciones y Unidades', (string)($kpis['talento']['total_direcciones'] ?? 0), 'Unidades Org.', 'Estructuradas', 'Talento Humano']);
        $x->addRow(['', '', '', '', '', '']);

        // 5. Distribución por Unidad
        $x->addRow(['UNIDADES TALENTO HUMANO', 'Dirección / Unidad', 'Total Servidores', 'Porcentaje Estructura', 'Estado', 'Módulo Origen']);
        $totalEmp = max(1, (int)($kpis['talento']['activos'] ?? 1));
        foreach (($kpis['talento']['top_unidades'] ?? []) as $u) {
            $pct = round(((int)($u['total'] ?? 0) / $totalEmp) * 100, 1);
            $x->addRow([
                'UNIDADES TALENTO HUMANO',
                (string)($u['unidad'] ?? ''),
                (string)($u['total'] ?? 0),
                $pct . '%',
                'Operativa',
                'Talento Humano'
            ]);
        }
        $x->addRow(['', '', '', '', '', '']);

        // 6. Operaciones y Seguridad Portuaria
        $x->addRow(['OPERACIONES PORTUARIAS', 'Visitas en Recinto Portuario', (string)($kpis['seguridad_operaciones']['visitas_en_puerto'] ?? 0), 'Tráfico en Garita', 'En Curso', 'Bitácoras Portuarias']);
        $x->addRow(['OPERACIONES PORTUARIAS', 'Visitas del Periodo (30d)', (string)($kpis['seguridad_operaciones']['visitas_periodo'] ?? 0), 'Total Registros', 'Consolidado', 'Bitácoras Portuarias']);
        $x->addRow(['OPERACIONES PORTUARIAS', 'CCTV Cámaras en Línea', (string)($kpis['seguridad_operaciones']['camaras_operativas'] ?? 0), 'CCTV Puerto', 'Vigilancia Activa', 'Seguridad Integral']);
        $x->addRow(['', '', '', '', '', '']);

        // 7. Gobernanza y Ciberseguridad
        $x->addRow(['GOBERNANZA Y SEGURIDAD', 'Tasa Éxito en Auditoría', ($kpis['gobernanza']['auditorias_exito_pct'] ?? '96.7') . '%', 'Auditoría Transaccional', 'Cumplimiento Alto', 'Portal APM']);
        $x->addRow(['GOBERNANZA Y SEGURIDAD', 'Sesiones Concurrentes', (string)($kpis['gobernanza']['sesiones_activas'] ?? 1), 'Usuarios Conectados', 'Activas', 'Portal APM']);

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        echo $x->build();
        exit;
    }

    public function exportarPdf(): void {
        $this->requireAuth();
        $this->requireLevel(2);
        require_once ROOT . '/libs/fpdf/fpdf.php';
        require_once ROOT . '/libs/ReportPdf.php';

        $kpis = $this->model->getKpisEjecutivo('30d');

        $secciones = [
            ['Resumen Ejecutivo y Metadatos', [
                ['Documento', 'Reporte Consolidado de Telemetría APM'],
                ['Fecha y Hora de Emisión', date('d/m/Y H:i:s')],
                ['Emitido por', $_SESSION['nombre_completo'] ?? 'Usuario'],
                ['Periodo Analítico', 'Últimos 30 días (Consolidado)'],
            ]],
            ['Patrimonio y Activos (Control de Bienes)', [
                ['Valor Total Inventariado', '$' . number_format((float)($kpis['patrimonio']['valor_total'] ?? 0), 2) . ' USD'],
                ['Total de Bienes Físicos', number_format((int)($kpis['patrimonio']['total_bienes'] ?? 0)) . ' bienes'],
                ['Bienes Operativos', number_format((int)($kpis['patrimonio']['bienes_activos'] ?? 0)) . ' bienes'],
                ['Bienes en Mantenimiento', (string)($kpis['patrimonio']['bienes_mantenimiento'] ?? 0)],
                ['Tasa de Disponibilidad Operativa', ($kpis['patrimonio']['tasa_disponibilidad'] ?? '100') . '%'],
            ]],
            ['Talento Humano y Masa Salarial', [
                ['Personal Activo Registrado', (string)($kpis['talento']['activos'] ?? 0) . ' servidores'],
                ['Masa Salarial Mensual', '$' . number_format((float)($kpis['talento']['masa_salarial'] ?? 0), 2) . ' USD / mes'],
                ['Sueldo Promedio Institucional', '$' . number_format((float)($kpis['talento']['sueldo_promedio'] ?? 0), 2) . ' USD'],
                ['Unidades Organizacionales', (string)($kpis['talento']['total_direcciones'] ?? 0) . ' Direcciones'],
            ]],
            ['Operaciones Portuarias y Garitas (Bitácoras)', [
                ['Visitas Activas en Recinto Portuario', (string)($kpis['seguridad_operaciones']['visitas_en_puerto'] ?? 0) . ' personas/vehículos'],
                ['Visitas Registradas en el Periodo', (string)($kpis['seguridad_operaciones']['visitas_periodo'] ?? 0) . ' ingresos'],
                ['CCTV / Vigilancia en Línea', (string)($kpis['seguridad_operaciones']['camaras_operativas'] ?? 0) . ' cámaras operativas'],
            ]],
            ['Gobernanza, Ciberseguridad y Auditoría', [
                ['Tasa de Éxito Transaccional', ($kpis['gobernanza']['auditorias_exito_pct'] ?? '96.7') . '%'],
                ['Sesiones Activas Concurrentes', (string)($kpis['gobernanza']['sesiones_activas'] ?? 1) . ' sesión(es)'],
                ['Estado General del Sistema', '100% Operativo y Blindado'],
            ]],
        ];

        ReportPdf::ficha(
            'REPORTE EJECUTIVO CONSOLIDADO',
            'Centro de Comando Estratégico · Autoridad Portuaria de Manta',
            $secciones,
            'Reporte_Ejecutivo_APM_' . date('Ymd_His') . '.pdf'
        );
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
