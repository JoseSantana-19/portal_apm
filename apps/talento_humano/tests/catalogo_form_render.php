<?php
declare(strict_types=1);
require dirname(__DIR__,2) . '/helpers/polyfills_php74.php';

$mode = $argv[1] ?? 'allow';
if (!in_array($mode, ['allow', 'deny'], true)) {
    fwrite(STDERR, "Modo de prueba inválido.\n");
    exit(2);
}

define('ROOT', dirname(__DIR__));
define('BASE_URL', '');
define('IMG_URL', '/public/img');
require_once ROOT.'/core/Config.php';
require_once ROOT.'/core/InstitutionalClock.php';
require_once ROOT . '/config/Catalogos.php';
$GLOBALS['catalogPermission'] = $mode === 'allow';

final class Auth
{
    public static function can(string $module, string $action = 'visualizar'): bool
    {
        return $module !== 'maestros' || $action !== 'crear' || (bool)$GLOBALS['catalogPermission'];
    }
    public static function user(): array { return ['name'=>'QA APM','role'=>'Administrador']; }
    public static function csrfToken(): string { return 'csrf-test'; }
}

$_SERVER['REQUEST_URI'] = '/talento-humano/empleado/crear';
$modoEdicion = false;
$empleado = [];
$nacionalidadesEmpleado = [];
$nacionalidades = [['nacionalidad_id'=>1, 'nombre'=>'Ecuatoriana', 'pais'=>'Ecuador']];
$areas = [
    ['unidad_id'=>1, 'nombre_unidad'=>'DIRECCIÓN ADMINISTRATIVA', 'direccion_padre'=>'', 'unidad_padre_id'=>null, 'activo'=>1],
    ['unidad_id'=>2, 'nombre_unidad'=>'TALENTO HUMANO', 'direccion_padre'=>'DIRECCIÓN ADMINISTRATIVA', 'unidad_padre_id'=>1, 'activo'=>1],
];
$cargos = [
    ['puesto_id'=>3, 'nombre_puesto'=>'ANALISTA', 'remuneracion_unificada'=>'986.00'],
];

ob_start();
require ROOT . '/modules/talento-humano/Vistas/formulario.php';
$html = (string)ob_get_clean();

$failures = [];
$assert = static function (bool $condition, string $message) use (&$failures): void {
    if (!$condition) $failures[] = $message;
};

$assert(str_contains($html, 'form-header-kicker'), 'El formulario perdió la nueva jerarquía del encabezado.');
$assert(str_contains($html, 'aria-controls="panel-laboral"') && str_contains($html, 'tabindex="-1"'), 'Las pestañas no conservan atributos accesibles.');
$assert(str_contains($html, 'DIRECCIÓN ADMINISTRATIVA / TALENTO HUMANO'), 'El selector no muestra la jerarquía dirección/área.');
$assert(str_contains($html, 'data-rmu="986.00"'), 'El cargo no expone la RMU referencial para autocompletar.');
$routes = (string)file_get_contents(ROOT . '/index.php');
$actionView = (string)file_get_contents(ROOT . '/modules/talento-humano/Vistas/accion_personal.php');
$assert(str_contains($routes, "talento-humano/catalogo/unidad") && str_contains($routes, "talento-humano/catalogo/puesto"), 'Faltan las rutas canónicas de alta rápida.');
$assert(str_contains($routes, "'maestros', 'crear'"), 'Las rutas de catálogos no declaran el permiso requerido.');
$assert(str_contains($actionView, "shared/catalogo_rapido.php"), 'Acción de Personal no reutiliza el componente compartido.');

$laborStart = strpos($html, 'id="panel-laboral"');
$contactStart = strpos($html, 'id="panel-contacto"');
$city = strpos($html, 'id="ciudad_residencia"');
$assert($laborStart !== false && $contactStart !== false && $city !== false && $city > $contactStart, 'Ciudad de residencia no quedó en la sección Contacto.');

if ($mode === 'allow') {
    $assert(substr_count($html, "abrirCatalogoRapido('") === 2, 'No se renderizan los dos botones de alta rápida.');
    $assert(str_contains($html, 'id="catalogoRapido"'), 'No se incluyó el modal compartido de catálogos.');
    $assert(str_contains($html, '/talento-humano/catalogo'), 'El modal no usa la ruta canónica protegida.');
} else {
    $assert(!str_contains($html, "abrirCatalogoRapido('"), 'Se muestran altas rápidas sin permiso de maestros.');
    $assert(!str_contains($html, 'id="catalogoRapido"'), 'Se expone el modal sin permiso de maestros.');
}

if ($failures) {
    foreach ($failures as $failure) fwrite(STDERR, "[FAIL] {$failure}\n");
    exit(1);
}

echo "[OK] Formulario y catálogos rápidos renderizados en modo {$mode}\n";
