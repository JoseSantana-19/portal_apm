<?php
/**
 * HomeController. Public landing page and demo SSO endpoint.
 */
class HomeController extends Controller {

    public function __construct() {
        parent::__construct();
    }

    public function index(): void {
        if (isset($_SESSION['user_id'])) {
            $this->redirect('/dashboard');
        }
        $this->render('General/home/index', ['title' => 'Portal Corporativo Único — Autoridad Portuaria de Manta'], false);
    }

    /**
     * Demo SSO API: looks up a user by cedula, generates and verifies an SSO token,
     * and returns the user's MOIS menu structure.
     */
    public function demoSso(): void {
        try {
            $cedula = $this->sanitize($_GET['cedula'] ?? '');
            if (empty($cedula)) {
                $this->json(['error' => 'Por favor, proporcione una cédula de identidad.'], 400);
            }

            $db   = Database::getInstance();
            $stmt = $db->query(
                "SELECT id_usuario AS id, nombre_usuario, nombre_completo, correo, cedula
                 FROM CORE_Usuarios WHERE cedula = ? AND estado = 1",
                [[$cedula, SQLSRV_PARAM_IN]]
            );
            $user = $db->fetch($stmt);
            $db->free($stmt);

            if (!$user) {
                $this->json(['error' => 'Usuario no encontrado con la cédula proporcionada o está inactivo.'], 404);
            }

            $token        = ModuleSecurity::generateSSOToken((int)$user['id'], $cedula);
            $verification = ModuleSecurity::verifySSOToken($token);

            $menuModel = new Menu();
            $menuTree  = $menuModel->getUserMenu((int)$user['id']);

            $this->json([
                'success' => true,
                'user'    => [
                    'id'              => $user['id'],
                    'nombre_usuario'  => $user['nombre_usuario'],
                    'nombre_completo' => $user['nombre_completo'],
                    'correo'          => $user['correo'],
                    'cedula'          => $user['cedula'],
                ],
                'token' => [
                    'raw'               => $token,
                    'is_verified'       => $verification['success'],
                    'decrypted_payload' => $verification['payload'] ?? null,
                ],
                'menu_mois' => $menuTree,
            ]);

        } catch (Exception $e) {
            $this->json(['error' => 'Error en el SSO: ' . $e->getMessage()], 500);
        }
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
