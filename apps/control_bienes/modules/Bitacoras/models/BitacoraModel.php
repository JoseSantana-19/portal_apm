<?php
require_once ROOT_PATH . 'core/Model.php';
require_once ROOT_PATH . 'modules/Central/models/InvSecuencial.php';

class BitacoraModel extends Model {
    private static $esquemaPreparado = false;
    private static $requestId = null;

    public function __construct() {
        parent::__construct();
        $this->prepararEsquema();
    }

    public function registrar($tipo, $modulo, $descripcion, array $contexto = []) {
        try {
            if (session_status() === PHP_SESSION_NONE) @session_start();
            $usuario = $_SESSION['usuario'] ?? [];
            $usuarioId = (int)($_SESSION['usuario_id'] ?? $usuario['id'] ?? 0);
            $ip = $this->resolverIp();
            $equipo = $this->resolverEquipo();
            $userAgent = mb_substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 1000, 'UTF-8');
            $metodo = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'CLI'));
            $ruta = mb_substr((string)($_GET['route'] ?? $contexto['route'] ?? 'sistema'), 0, 255, 'UTF-8');
            $accion = mb_substr((string)($_GET['action'] ?? $contexto['action'] ?? 'index'), 0, 255, 'UTF-8');
            $resultado = mb_substr((string)($contexto['resultado'] ?? 'OK'), 0, 30, 'UTF-8');
            $duracion = isset($contexto['duracion_ms']) ? round((float)$contexto['duracion_ms'], 2) : null;
            $datos = $contexto;
            unset($datos['route'], $datos['action'], $datos['resultado'], $datos['duracion_ms']);
            $datosJson = $datos ? json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null;

            $secuencial = (new InvSecuencial())->generarSiguiente('bit');
            $stmt = $this->db->prepare("INSERT INTO inv_bitacora
                (secuencial, tipo, modulo, descripcion, fecha, usuario_id, usuario_nombre, usuario_login, rol, ip, equipo, user_agent, metodo_http, ruta, accion, request_id, resultado, datos_contexto, duracion_ms)
                VALUES (:sec, :tipo, :mod, :desc, CURRENT_TIMESTAMP, :usuario_id, :usuario_nombre, :usuario_login, :rol, :ip, :equipo, :ua, :metodo, :ruta, :accion, :request_id, :resultado, :datos, :duracion)");
            $stmt->execute([
                ':sec' => $secuencial, ':tipo' => strtoupper((string)$tipo), ':mod' => $modulo, ':desc' => $descripcion,
                ':usuario_id' => $usuarioId ?: null, ':usuario_nombre' => $usuario['nombre'] ?? 'Sistema',
                ':usuario_login' => $usuario['usuario'] ?? null, ':rol' => $_SESSION['rol'] ?? $usuario['rol'] ?? null,
                ':ip' => $ip, ':equipo' => $equipo, ':ua' => $userAgent ?: null, ':metodo' => $metodo,
                ':ruta' => $ruta, ':accion' => $accion, ':request_id' => $this->requestId(),
                ':resultado' => $resultado, ':datos' => $datosJson, ':duracion' => $duracion,
            ]);
            return true;
        } catch (Throwable $e) {
            error_log('Error al escribir en bitácora: ' . $e->getMessage());
            return false;
        }
    }

    public function filtrar($filtros = []) {
        $driver = defined('DB_DRIVER') ? DB_DRIVER : 'sqlite';
        $sql = $driver === 'sqlsrv' ? 'SELECT TOP 500 * FROM inv_bitacora WHERE 1=1' : 'SELECT * FROM inv_bitacora WHERE 1=1';
        $params = [];
        foreach (['modulo' => 'modulo', 'resultado' => 'resultado'] as $filtro => $campo) {
            if (!empty($filtros[$filtro])) { $sql .= " AND {$campo} = :{$filtro}"; $params[":{$filtro}"] = $filtros[$filtro]; }
        }
        if (!empty($filtros['tipo'])) { $sql .= ' AND tipo = :tipo'; $params[':tipo'] = strtoupper($filtros['tipo']); }
        if (!empty($filtros['usuario'])) {
            $sql .= ' AND (usuario_nombre LIKE :usuario OR usuario_login LIKE :usuario)';
            $params[':usuario'] = '%' . $filtros['usuario'] . '%';
        }
        if (!empty($filtros['ip'])) { $sql .= ' AND ip LIKE :ip'; $params[':ip'] = '%' . $filtros['ip'] . '%'; }
        if (!empty($filtros['desde'])) { $sql .= ' AND fecha >= :desde'; $params[':desde'] = $filtros['desde'] . ' 00:00:00'; }
        if (!empty($filtros['hasta'])) { $sql .= ' AND fecha <= :hasta'; $params[':hasta'] = $filtros['hasta'] . ' 23:59:59'; }
        if (!empty($filtros['termino'])) {
            $sql .= ' AND (descripcion LIKE :term OR secuencial LIKE :term OR ruta LIKE :term OR accion LIKE :term OR equipo LIKE :term)';
            $params[':term'] = '%' . $filtros['termino'] . '%';
        }
        $sql .= ' ORDER BY fecha DESC';
        if ($driver !== 'sqlsrv') $sql .= ' LIMIT 500';
        $stmt = $this->db->prepare($sql); $stmt->execute($params); return $stmt->fetchAll();
    }

    public function obtenerEstadisticas() {
        $stats = ['total' => 0, 'CREAR' => 0, 'ACTUALIZAR' => 0, 'ELIMINAR' => 0, 'OTROS' => 0];
        foreach ($this->db->query('SELECT tipo, COUNT(*) cantidad FROM inv_bitacora GROUP BY tipo')->fetchAll() as $row) {
            $stats['total'] += (int)$row['cantidad']; $tipo = strtoupper((string)$row['tipo']);
            if (array_key_exists($tipo, $stats)) $stats[$tipo] = (int)$row['cantidad']; else $stats['OTROS'] += (int)$row['cantidad'];
        }
        return $stats;
    }

    public function exportarCSV($filtros = []) {
        $output = "Secuencial,Fecha,Usuario,Login,Rol,IP,Equipo,Método,Ruta,Acción,Módulo,Tipo,Resultado,Duración ms,Descripción\n";
        foreach ($this->filtrar($filtros) as $i) {
            $campos = [$i['secuencial'], $i['fecha'], $i['usuario_nombre'] ?? '', $i['usuario_login'] ?? '', $i['rol'] ?? '', $i['ip'] ?? '', $i['equipo'] ?? '', $i['metodo_http'] ?? '', $i['ruta'] ?? '', $i['accion'] ?? '', strtoupper((string)$i['modulo']), $i['tipo'], $i['resultado'] ?? '', $i['duracion_ms'] ?? '', $i['descripcion']];
            $output .= implode(',', array_map(static function ($v) { return '"' . str_replace('"', '""', (string)$v) . '"'; }, $campos)) . "\n";
        }
        return $output;
    }

    private function resolverIp(): string {
        $candidatos = [];
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) $candidatos[] = trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
        if (!empty($_SERVER['HTTP_X_REAL_IP'])) $candidatos[] = trim($_SERVER['HTTP_X_REAL_IP']);
        if (!empty($_SERVER['REMOTE_ADDR'])) $candidatos[] = trim($_SERVER['REMOTE_ADDR']);
        foreach ($candidatos as $ip) if (filter_var($ip, FILTER_VALIDATE_IP)) return mb_substr($ip, 0, 64, 'UTF-8');
        return 'desconocida';
    }

    private function resolverEquipo(): string {
        foreach (['HTTP_X_DEVICE_NAME', 'HTTP_X_COMPUTER_NAME', 'REMOTE_HOST'] as $clave) {
            if (!empty($_SERVER[$clave])) return mb_substr(trim((string)$_SERVER[$clave]), 0, 255, 'UTF-8');
        }
        $ua = (string)($_SERVER['HTTP_USER_AGENT'] ?? '');
        if (preg_match('/\(([^;)]+)[;)]/', $ua, $m)) return mb_substr(trim($m[1]), 0, 255, 'UTF-8');
        return 'No informado';
    }

    private function requestId(): string {
        if (self::$requestId === null) self::$requestId = bin2hex(random_bytes(12));
        return self::$requestId;
    }

    private function prepararEsquema(): void {
        if (self::$esquemaPreparado) return; self::$esquemaPreparado = true;
        $columnas = [
            'usuario_id' => 'INT NULL', 'usuario_nombre' => 'NVARCHAR(255) NULL', 'usuario_login' => 'NVARCHAR(150) NULL',
            'rol' => 'NVARCHAR(80) NULL', 'ip' => 'NVARCHAR(64) NULL', 'equipo' => 'NVARCHAR(255) NULL',
            'user_agent' => 'NVARCHAR(1000) NULL', 'metodo_http' => 'NVARCHAR(10) NULL', 'ruta' => 'NVARCHAR(255) NULL',
            'accion' => 'NVARCHAR(255) NULL', 'request_id' => 'NVARCHAR(64) NULL', 'resultado' => 'NVARCHAR(30) NULL',
            'datos_contexto' => 'NVARCHAR(MAX) NULL', 'duracion_ms' => 'DECIMAL(12,2) NULL',
        ];
        try {
            $driver = defined('DB_DRIVER') ? DB_DRIVER : 'sqlite';
            if ($driver === 'sqlsrv') {
                $existentes = $this->db->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'inv_bitacora'")->fetchAll(PDO::FETCH_COLUMN);
                foreach ($columnas as $nombre => $definicion) if (!in_array($nombre, $existentes, true)) $this->db->exec("ALTER TABLE inv_bitacora ADD {$nombre} {$definicion}");
            } elseif ($driver === 'pgsql') {
                foreach ($columnas as $nombre => $definicion) {
                    $tipo = strpos($definicion, 'INT') === 0 ? 'INTEGER' : (strpos($definicion, 'DECIMAL') === 0 ? 'DECIMAL(12,2)' : 'TEXT');
                    $this->db->exec("ALTER TABLE inv_bitacora ADD COLUMN IF NOT EXISTS {$nombre} {$tipo} NULL");
                }
            } else {
                $existentes = $this->db->query('PRAGMA table_info(inv_bitacora)')->fetchAll(PDO::FETCH_COLUMN, 1);
                foreach ($columnas as $nombre => $definicion) if (!in_array($nombre, $existentes, true)) {
                    $tipo = $nombre === 'usuario_id' ? 'INTEGER' : ($nombre === 'duracion_ms' ? 'REAL' : 'TEXT');
                    $this->db->exec("ALTER TABLE inv_bitacora ADD COLUMN {$nombre} {$tipo} NULL");
                }
            }
        } catch (Throwable $e) { error_log('No se pudo preparar el esquema de bitácora: ' . $e->getMessage()); }
    }
}

class InvBitacora extends BitacoraModel {}
