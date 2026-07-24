<?php
/**
 * ConfigController.php - Controlador de Períodos, Secuenciales e IVA Histórico
 */

require_once ROOT_PATH . 'modules/Central/models/InvPeriodo.php';
require_once ROOT_PATH . 'modules/Central/models/InvSecuencial.php';
require_once ROOT_PATH . 'modules/Bitacoras/models/LogModel.php';

class ConfigController extends Controller {
    private $periodoModel;
    private $secuencialModel;
    private $logger;

    public function __construct() {
        parent::__construct();
        $this->logger = new Logger('per');
        $this->periodoModel = new InvPeriodo();
        $this->secuencialModel = new InvSecuencial();
    }

    /**
     * Vista de Períodos
     */
    public function periodos() {
        $this->registrarAuditoria('ACCESO', 'per', 'Acceso al módulo de Períodos e IVA Histórico');
        
        $inv_periodos = $this->periodoModel->obtenerTodos();
        $periodoActivo = $this->periodoModel->obtenerPeriodoActivo();

        $db = Database::getInstance()->getConnection();
        $tiposIva = $db->query("SELECT * FROM inv_tipos_iva ORDER BY tasa_iva DESC")->fetchAll();
        if (empty($tiposIva)) {
            $tiposIva = [
                ['id' => 0, 'nombre' => 'IVA 15% (General)',       'tasa_iva' => 15.0],
                ['id' => 0, 'nombre' => 'IVA 8% (Turismo)',        'tasa_iva' => 8.0],
                ['id' => 0, 'nombre' => 'IVA 5% (Emergencia)',     'tasa_iva' => 5.0],
                ['id' => 0, 'nombre' => 'IVA 0% (Exento)',         'tasa_iva' => 0.0],
            ];
        }

        $respaldoId = isset($_GET['respaldo_id']) ? (int)$_GET['respaldo_id'] : 0;
        $respaldoItems = [];
        $respaldoPeriodo = null;
        if ($respaldoId > 0) {
            $respaldoItems = $this->periodoModel->obtenerRespaldoHistorico($respaldoId);
            $respaldoPeriodo = $this->periodoModel->buscarPorId($respaldoId);
            $this->registrarAuditoria('CONSULTA', 'per', 'Consulta de respaldo histórico del período ID: ' . $respaldoId);
        }

        $this->render('central/periodos', [
            'inv_periodos'       => $inv_periodos,
            'periodoActivo'  => $periodoActivo,
            'tiposIva'       => $tiposIva,
            'respaldoItems'  => $respaldoItems,
            'respaldoPeriodo'=> $respaldoPeriodo
        ], 'Períodos e IVA - Sistema Portuario');
    }

    /**
     * Vista de Secuenciales
     */
    public function secuenciales() {
        $this->registrarAuditoria('ACCESO', 'seq', 'Acceso al módulo de configuración de Secuenciales');
        
        $inv_secuenciales = $this->secuencialModel->obtenerTodos();

        $this->render('central/secuenciales', [
            'inv_secuenciales' => $inv_secuenciales
        ], 'Secuenciales de Índice - Sistema Portuario');
    }

    /**
     * Guarda un período
     */
    public function guardar() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('inv_periodos', 'Método no permitido', 'error');
        }

        $nombre = trim($_POST['nombre']);
        $fechaInicio = $_POST['fecha_inicio'];
        $fechaFin = $_POST['fecha_fin'];
        $tasaIva = (float)$_POST['tasa_iva'];

        try {
            $periodoId = $this->periodoModel->crear($nombre, $fechaInicio, $fechaFin, $tasaIva);
            $this->registrarAuditoria('CREAR', 'per', "Nuevo período creado: {$nombre} con IVA al {$tasaIva}%");
            $this->logger->info("CREAR_PERIODO: {$nombre} IVA={$tasaIva}%", 'guardar');
            $this->redirect('inv_periodos', "Período '{$nombre}' creado y activado exitosamente", 'success');
        } catch (Exception $e) {
            $this->logger->inv_error("Error al crear período '{$nombre}'", $e, 'guardar');
            $this->redirect('inv_periodos', 'Error al crear el período', 'error');
        }
    }

    /**
     * Ejecuta el corte y respaldo del período activo
     */
    public function ejecutarCorte() {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $periodo = $this->periodoModel->buscarPorId($id);

        if ($periodo) {
            if ($periodo['estado'] === 'cerrado') {
                $this->redirect('inv_periodos', 'El período ya se encuentra cerrado', 'warning');
            }

            try {
                $this->periodoModel->ejecutarCorteYRespaldo($id);
                $this->registrarAuditoria('CIERRE', 'per', "Cierre ejecutado exitosamente para el período: {$periodo['nombre']}. Respaldo histórico archivado.");
                $this->logger->info("CIERRE_PERIODO: {$periodo['nombre']} (ID={$id})", 'ejecutarCorte');
                $this->redirect('inv_periodos', "Cierre del período '{$periodo['nombre']}' realizado con éxito.", 'success');
            } catch (Exception $e) {
                $this->logger->inv_error("Error al ejecutar cierre del período '{$periodo['nombre']}'", $e, 'ejecutarCorte');
                $this->redirect('inv_periodos', 'Error al ejecutar el cierre del período', 'error');
            }
        } else {
            $this->redirect('inv_periodos', 'Período no encontrado', 'error');
        }
    }

    /**
     * Genera un reporte de auditoría histórico
     */
    public function generarReporte() {
        $this->registrarAuditoria('ACCESO', 'per', 'Acceso al generador de reportes históricos por período');

        $fechaInicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : date('Y-01-01');
        $fechaFin = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : date('Y-12-31');

        $reporte = $this->periodoModel->generarReportePeriodo($fechaInicio, $fechaFin);

        $this->render('central/periodos_reporte', [
            'fechaInicio' => $fechaInicio,
            'fechaFin' => $fechaFin,
            'reporte' => $reporte
        ], 'Reporte por Período - Sistema Portuario');
    }

    /**
     * Prueba la generación de secuencial
     */
    public function test() {
        $modulo = isset($_GET['modulo']) ? trim($_GET['modulo']) : '';
        
        try {
            $siguiente = $this->secuencialModel->generarSiguiente($modulo);
            $this->registrarAuditoria('CREAR', 'seq', "Generación de prueba del secuencial para módulo '{$modulo}': Nuevo código generado = {$siguiente}");
            $this->redirect('inv_secuenciales', "Secuencial de prueba generado con éxito: <strong>{$siguiente}</strong>", 'success');
        } catch (Exception $e) {
            $this->logger->inv_error("Error al generar secuencial de prueba para módulo={$modulo}", $e, 'test');
            $this->redirect('inv_secuenciales', 'Error al generar de prueba', 'error');
        }
    }

    /**
     * Reinicia el secuencial
     */
    public function reiniciar() {
        $modulo = isset($_GET['modulo']) ? trim($_GET['modulo']) : '';
        
        try {
            $this->secuencialModel->reiniciar($modulo);
            $this->registrarAuditoria('ELIMINAR', 'seq', "Se ha reiniciado el contador del secuencial para el módulo: '{$modulo}'");
            $this->redirect('inv_secuenciales', "Contador del módulo '{$modulo}' reiniciado exitosamente a cero", 'warning');
        } catch (Exception $e) {
            $this->logger->inv_error("Error al reiniciar contador del módulo={$modulo}", $e, 'reiniciar');
            $this->redirect('inv_secuenciales', 'Error al reiniciar contador', 'error');
        }
    }
}
class_alias('ConfigController', 'InvPeriodoController');
class_alias('ConfigController', 'InvSecuencialController');
