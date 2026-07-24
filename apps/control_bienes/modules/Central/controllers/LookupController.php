<?php
/**
 * LookupController.php - Controlador para la búsqueda en 3 pasos (Local, APM y Microservicio)
 */

class LookupController extends Controller {
    
    public function buscar() {
        header('Content-Type: application/json');
        
        // Verificar autenticación
        if (!isset($_SESSION['usuario'])) {
            echo json_encode(['success' => false, 'mensaje' => 'Sesión no activa']);
            exit;
        }

        $ruc = isset($_GET['ruc']) ? trim($_GET['ruc']) : '';
        $tipo = isset($_GET['tipo']) ? trim($_GET['tipo']) : 'proveedor'; // 'proveedor', 'empresa', 'persona'

        if (empty($ruc)) {
            echo json_encode(['success' => false, 'mensaje' => 'Código RUC o Cédula es requerido']);
            exit;
        }

        // --- PASO 1: BÚSQUEDA LOCAL ---
        $db = Database::getInstance()->getConnection();
        
        // Si buscamos proveedor
        if ($tipo === 'proveedor') {
            $stmt = $db->prepare("SELECT nombre, ruc, extra FROM inv_proveedores WHERE ruc = :ruc");
            $stmt->execute([':ruc' => $ruc]);
            $res = $stmt->fetch();
            if ($res) {
                echo json_encode([
                    'success' => true,
                    'origen' => 'Local (Base de Datos)',
                    'nombre' => $res['nombre'],
                    'ruc' => $res['ruc'],
                    'extra' => $res['extra'] ?? 'Proveedor Local'
                ]);
                exit;
            }
        }

        // --- PASO 2: BÚSQUEDA EN BASE DE TRÁMITE EN LÍNEA (APM) ---
        try {
            $dsnApm = "sqlsrv:Server=" . DB_HOST . ";Database=APM_Tramites;TrustServerCertificate=1";
            $pdoApm = new PDO($dsnApm, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);
            
            $stmtApm = $pdoApm->prepare("SELECT nombre, ruc, direccion, tipo FROM apm_tramites_linea WHERE ruc = :ruc");
            $stmtApm->execute([':ruc' => $ruc]);
            $resApm = $stmtApm->fetch(PDO::FETCH_ASSOC);
            
            if ($resApm) {
                echo json_encode([
                    'success' => true,
                    'origen' => 'Trámite en Línea (APM)',
                    'nombre' => $resApm['nombre'],
                    'ruc' => $resApm['ruc'],
                    'extra' => $resApm['direccion'] ?? 'Base APM Trámites'
                ]);
                exit;
            }
        } catch (Exception $e) {
            // Continuar silenciosamente al paso 3 si la conexión externa falla
        }

        // --- PASO 3: BÚSQUEDA POR MICROSERVICIOS (JSON API) ---
        // Leer parámetro del microservicio según el tipo
        $claveParam = ($tipo === 'proveedor' || $tipo === 'empresa') ? 'url_microservicio_empresas' : 'url_microservicio_personas';
        $urlMicroservicio = CommonHelper::obtenerParametro($claveParam, 'http://localhost/Control_bines/public/mock_api.php');

        $url = $urlMicroservicio . (strpos($urlMicroservicio, '?') !== false ? '&' : '?') . 'ruc=' . urlencode($ruc) . '&tipo=' . urlencode($tipo);

        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 4);
            $response = curl_exec($ch);
            curl_close($ch);
        } catch (Exception $e) {
            $response = false;
        }

        // FALLBACK: Si el servidor local está apagado, ejecutamos el script mock directamente
        if ($response === false && (strpos($url, 'localhost') !== false || strpos($url, '127.0.0.1') !== false)) {
            $mockFile = ROOT_PATH . 'public/mock_api.php';
            if (file_exists($mockFile)) {
                $oldGet = $_GET;
                $_GET['ruc'] = $ruc;
                $_GET['tipo'] = $tipo;
                
                ob_start();
                include $mockFile;
                $response = ob_get_clean();
                
                $_GET = $oldGet;
            }
        }

        try {
            if ($response) {
                $data = json_decode($response, true);
                if (isset($data['success']) && $data['success'] === true) {
                    echo json_encode([
                        'success' => true,
                        'origen' => 'Microservicio Externo (JSON)',
                        'nombre' => $data['nombre'],
                        'ruc' => $data['ruc'],
                        'extra' => $data['extra'] ?? 'Datos SRI/Registro Civil'
                    ]);
                    exit;
                }
            }
        } catch (Exception $e) {
            // Continuar
        }

        echo json_encode([
            'success' => false,
            'mensaje' => 'No se encontraron registros de RUC/Cédula en ninguna de las 3 bases de datos (Local, APM, Microservicio)'
        ]);
        exit;
    }
}
