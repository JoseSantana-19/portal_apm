<?php
/**
 * EventoController.php - Controlador del Módulo de Bitácora y Auditoría
 */

require_once ROOT_PATH . 'modules/Bitacoras/models/BitacoraModel.php';
require_once ROOT_PATH . 'modules/Bitacoras/models/LogModel.php';

class EventoController extends Controller {
    private $bitacoraModel;
    private $logger;

    public function __construct() {
        parent::__construct();
        $this->logger = new Logger('bit');
        $this->bitacoraModel = new BitacoraModel();
    }

    /**
     * Listado de Bitácoras con estadísticas y filtros
     */
    public function index() {
        $this->registrarAuditoria('ACCESO', 'bit', 'Acceso al módulo de Bitácora de Auditoría');

        $filtros = [
            'modulo' => isset($_GET['modulo']) ? $_GET['modulo'] : '',
            'tipo' => isset($_GET['tipo']) ? $_GET['tipo'] : '',
            'termino' => isset($_GET['termino']) ? $_GET['termino'] : '',
            'usuario' => isset($_GET['usuario']) ? trim($_GET['usuario']) : '',
            'ip' => isset($_GET['ip']) ? trim($_GET['ip']) : '',
            'resultado' => isset($_GET['resultado']) ? trim($_GET['resultado']) : '',
            'desde' => isset($_GET['desde']) ? trim($_GET['desde']) : '',
            'hasta' => isset($_GET['hasta']) ? trim($_GET['hasta']) : '',
        ];

        $logs = $this->bitacoraModel->filtrar($filtros);
        $stats = $this->bitacoraModel->obtenerEstadisticas();

        $this->render('bitacoras/listar', [
            'logs' => $logs,
            'stats' => $stats,
            'filtros' => $filtros
        ], 'Bitácora del Sistema - Sistema Portuario');
    }

    /**
     * Exporta los logs filtrados a CSV
     */
    public function exportar() {
        $filtros = [
            'modulo' => isset($_GET['modulo']) ? $_GET['modulo'] : '',
            'tipo' => isset($_GET['tipo']) ? $_GET['tipo'] : '',
            'termino' => isset($_GET['termino']) ? $_GET['termino'] : '',
            'usuario' => isset($_GET['usuario']) ? trim($_GET['usuario']) : '',
            'ip' => isset($_GET['ip']) ? trim($_GET['ip']) : '',
            'resultado' => isset($_GET['resultado']) ? trim($_GET['resultado']) : '',
            'desde' => isset($_GET['desde']) ? trim($_GET['desde']) : '',
            'hasta' => isset($_GET['hasta']) ? trim($_GET['hasta']) : '',
        ];

        $csv = $this->bitacoraModel->exportarCSV($filtros);
        $this->registrarAuditoria('EXPORTAR', 'bit', 'Bitácora de auditoría exportada a CSV');

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=bit_auditoria_' . date('Y-m-d') . '.csv');
        echo $csv;
        exit;
    }
}
class_alias('EventoController', 'InvBitacoraController');
