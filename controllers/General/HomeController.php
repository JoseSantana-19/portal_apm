<?php
/**
 * HomeController. Public landing page.
 */
class HomeController extends Controller {

    public function __construct() {
        parent::__construct();
    }

    public function index(): void {
        // ?preview=1 lo usa /admin/landing para mostrar el iframe de vista
        // previa aun con sesion activa — el contenido es publico, no hay
        // datos sensibles de por medio.
        if (isset($_SESSION['user_id']) && !isset($_GET['preview'])) {
            $this->redirect('/dashboard');
        }

        $db = Database::getInstance();
        $imagenes = array_column($db->fetchAll($db->query(
            'SELECT ruta_archivo FROM CORE_Landing_Imagenes WHERE estado=1 ORDER BY orden, id_imagen'
        )), 'ruta_archivo');
        // Noticias (con imagen, carrusel visual) y Consejos (texto, franja
        // aparte) son entidades independientes — no se derivan una de otra.
        $noticias = $db->fetchAll($db->query(
            'SELECT texto, imagen, enlace FROM CORE_Landing_Noticias WHERE estado=1 ORDER BY orden, id_noticia'
        ));
        $consejos = $db->fetchAll($db->query(
            'SELECT texto, enlace FROM CORE_Landing_Consejos WHERE estado=1 ORDER BY orden, id_consejo'
        ));

        $this->render('General/home/index', [
            'title'    => 'Portal Corporativo Único — Autoridad Portuaria de Manta',
            'imagenes' => $imagenes,
            'noticias' => $noticias,
            'consejos' => $consejos,
        ], false);
    }

    /**
     * Render a dynamic form/dashboard for a menu URL.
     * Looks up the node in CORE_Menu_Nodos by url_ruta.
     */
    public function renderDynamicForm(): void {
        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) {
            $this->redirect('/login');
        }

        $uri  = $_SERVER['REQUEST_URI'] ?? '';
        $path = parse_url($uri, PHP_URL_PATH);

        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $projectDir = preg_replace('/\/public$/', '', dirname($scriptName));
        $projectDir = str_replace('\\', '/', $projectDir);
        if ($projectDir !== '/' && $projectDir !== '' && str_starts_with($path, $projectDir)) {
            $path = substr($path, strlen($projectDir));
        }
        if (str_starts_with($path, '/public')) {
            $path = substr($path, 7);
        }
        $path         = '/' . trim($path, '/');
        $pathNoSlash  = rtrim($path, '/');

        $db   = Database::getInstance();
        $stmt = $db->query(
            "SELECT id_nodo,
                    descripcion                                                          AS descripcion_interfaz,
                    url_ruta                                                             AS url_formulario,
                    icono,
                    CONCAT(id_modulo,'.',opcion,'.',items,'.',subitems)                  AS codigo_secuencial,
                    CAST(id_modulo AS NVARCHAR(10))                                     AS codigo_depto,
                    NULL                                                                 AS color_tema,
                    NULL                                                                 AS nombre_departamento
             FROM CORE_Menu_Nodos
             WHERE (url_ruta = ? OR url_ruta = ?) AND estado = 1",
            [[$path, SQLSRV_PARAM_IN], [$pathNoSlash, SQLSRV_PARAM_IN]]
        );
        $option = $db->fetch($stmt);
        $db->free($stmt);

        if (!$option) {
            $option = [
                'id_nodo'              => 0,
                'descripcion_interfaz' => 'Formulario Operativo APM',
                'url_formulario'       => $path,
                'icono'                => 'fa-file-text',
                'codigo_secuencial'    => '0.0.0.0',
                'codigo_depto'         => 'PORTAL',
                'color_tema'           => '#0F172A',
                'nombre_departamento'  => 'Dirección General',
            ];
        }

        $menuObj  = new Menu();
        $userMenu = $menuObj->getUserMenu((int)$userId);

        $this->render('General/home/dynamic_form', [
            'title'    => ($option['descripcion_interfaz'] ?? 'Formulario') . ' — Portal APM',
            'userMenu' => $userMenu,
            'option'   => $option,
        ]);
    }
}
