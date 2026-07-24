<?php
/**
 * PortController — Controlador base del módulo Portuaria.
 *
 * Extiende el Controller del portal (auth, CSRF, sesión) y replica la API del
 * Controller base de portuaria_demoV4 para que los controladores portados
 * (bit_*Controller.php) funcionen con cambios mínimos:
 *
 *   - model('PortCatalogoModel')  → modules/Portuaria/models/PortCatalogoModel.php
 *   - view('visitas/listado', $d) → modules/Portuaria/views/visitas/bit_listado.php
 *                                    dentro del layout PROPIO del módulo
 *   - inputBody()                 → body POST/JSON (era input() en el origen;
 *                                    renombrado: choca con Controller::input del portal)
 *   - conn()/connExterna()        → recursos sqlsrv de PortuariaDemo/PortuariaExterna
 *
 * Todas las acciones pasan por la autenticación del PORTAL (requireAuth) y la
 * clase Auth compat puebla $_SESSION['apm_auth'] para vistas/JS del origen.
 */
abstract class PortController extends Controller
{
    /** @var array<string,string> variables $url_* para vistas y layout */
    protected array $rutas;

    public function __construct()
    {
        parent::__construct();

        // Autenticación del portal para TODO el módulo Portuaria.
        $this->requireAuth();

        // Puente de sesión portal → apm_auth (vistas/APIs del origen).
        Auth::hydrateFromPortal();

        // Rutas de assets (define además base_url() compat).
        $this->rutas = require __DIR__ . '/../config_rutas.php';

        // Includes de compatibilidad que el bootstrap del origen cargaba siempre.
        require_once ROOT . '/includes/bit_apm_fecha_iso.php';
        require_once ROOT . '/includes/bit_validaciones_ecuador.php';
        require_once ROOT . '/helpers/listado_helper.php';
    }

    /**
     * Carga un modelo del módulo Portuaria (archivos con prefijo bit_,
     * la clase interna no cambia — igual que el origen).
     */
    protected function model(string $name, ?string $module = null): object
    {
        $path = ROOT . "/modules/Portuaria/models/{$name}.php";
        if (!file_exists($path)) {
            throw new RuntimeException("Modelo Portuaria no encontrado: {$path}");
        }
        require_once $path;
        return new $name();
    }

    /**
     * Renderiza una vista del módulo dentro del layout propio (main.php del
     * origen adaptado). "catalogos/index" → views/catalogos/bit_index.php
     */
    protected function view(string $viewPath, array $data = [], bool $withLayout = true): void
    {
        $slash = strrpos($viewPath, '/');
        $viewFileName = $slash === false
            ? 'bit_' . $viewPath
            : substr($viewPath, 0, $slash + 1) . 'bit_' . substr($viewPath, $slash + 1);

        $file = ROOT . "/modules/Portuaria/views/{$viewFileName}.php";
        if (!file_exists($file)) {
            throw new RuntimeException("Vista Portuaria no encontrada: {$file}");
        }

        $vars = $this->rutas + $data;

        if ($withLayout) {
            $vars['file'] = $file;
            self::renderFile(ROOT . '/modules/Portuaria/views/layouts/main.php', $vars);
        } else {
            self::renderFile($file, $vars);
        }
    }

    /** Render aislado (mismo contrato que View::render del origen). */
    protected static function renderFile(string $__viewFile, array $data = []): void
    {
        if (!file_exists($__viewFile)) {
            throw new RuntimeException("Vista no encontrada: {$__viewFile}");
        }
        extract($data, EXTR_SKIP);
        require $__viewFile;
    }

    /**
     * Body de la petición (formulario o JSON) — era input() en el origen.
     */
    protected function inputBody(): array
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (stripos($contentType, 'application/json') !== false) {
            $raw  = file_get_contents('php://input');
            $data = json_decode($raw, true);
            return $data ?? [];
        }
        return $_POST;
    }

    /** Conexión principal del módulo (BD PortuariaDemo). */
    protected function conn()
    {
        return PortDatabase::conn();
    }

    /** Conexión externa del módulo (BD PortuariaExterna) — null si no disponible. */
    protected function connExterna()
    {
        return PortDatabase::connExterna();
    }
}
