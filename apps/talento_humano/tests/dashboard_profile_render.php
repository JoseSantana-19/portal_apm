<?php
declare(strict_types=1);

define('ROOT', dirname(__DIR__));
define('BASE_URL', '');
define('IMG_URL', '/public/img');

final class Auth
{
    public static function can(string $module, string $action = 'visualizar'): bool { return true; }
    public static function user(): array { return ['name'=>'QA APM','role'=>'Administrador','usr'=>'qa']; }
    public static function csrfToken(): string { return 'csrf-test'; }
}

$_SERVER['REQUEST_URI'] = '/talento-humano/inicio';
$hoy = new DateTimeImmutable('today');
$empleados = [];
for ($i = 0; $i < 12; $i++) {
    $empleados[] = [
        'id' => $i + 1, 'empleado_id' => $i + 1, 'cedula' => '13000000'.str_pad((string)$i, 2, '0', STR_PAD_LEFT),
        'apellidos' => 'PRUEBA'.$i, 'nombres' => 'FUNCIONARIO', 'estado' => 1,
        'fecha_nacimiento' => $hoy->modify("+{$i} days")->modify('-30 years')->format('Y-m-d'),
        'direccion_area' => 'TALENTO HUMANO', 'cargo' => 'ANALISTA', 'correo_institucional' => 'qa@apm.test',
    ];
}
$empleados[] = [
    'id'=>90, 'empleado_id'=>90, 'cedula'=>'1399999999', 'apellidos'=>'FUERA', 'nombres'=>'RANGO', 'estado'=>1,
    'fecha_nacimiento'=>$hoy->modify('+31 days')->modify('-25 years')->format('Y-m-d'), 'direccion_area'=>'OTRA', 'cargo'=>'OTRO',
];

ob_start();
require ROOT.'/modules/talento-humano/Vistas/inicio.php';
$inicio = (string)ob_get_clean();

$empleado = [
    'id'=>1, 'empleado_id'=>1, 'cedula'=>'1300000001', 'tipo_identificacion'=>'CEDULA',
    'apellidos'=>'PRUEBA', 'nombres'=>'FUNCIONARIO', 'estado'=>1, 'cargo'=>'ANALISTA',
    'direccion_area'=>'TALENTO HUMANO', 'tipo_contrato'=>'NOMBRAMIENTO', 'jornada'=>'COMPLETA',
    'fecha_ingreso'=>'2015-06-01', 'fecha_nacimiento'=>'1990-08-15', 'sueldo_rmu'=>'986.00',
    'correo_institucional'=>'qa@apm.test', 'correo_personal'=>'personal@apm.test', 'telefono_movil'=>'0999999999',
    'numero_cuenta_bancaria'=>'1234567890', 'institucion_bancaria'=>'BANCO PRUEBA', 'tipo_cuenta_bancaria'=>'AHORROS',
    'ruta_foto'=>'', 'observaciones'=>'Expediente de prueba',
];
$historial = [];
$nacionalidadesEmpleado = [['nombre'=>'Ecuatoriana']];
$_SERVER['REQUEST_URI'] = '/talento-humano/empleado/perfil/1300000001';
ob_start();
require ROOT.'/modules/talento-humano/Vistas/perfil.php';
$perfil = (string)ob_get_clean();

$fallos = [];
$assert = static function (bool $condicion, string $mensaje) use (&$fallos): void {
    if (!$condicion) $fallos[] = $mensaje;
};

$assert(substr_count($inicio, 'data-bday-days=') === 12, 'El Inicio todavía limita o incluye incorrectamente los cumpleaños de 30 días.');
$assert(str_contains($inicio, 'data-bday-filter="hoy"') && str_contains($inicio, 'data-bday-filter="30"'), 'Los filtros de cumpleaños no se renderizan.');
$assert(str_contains($inicio, 'href="/reportes"') && str_contains($inicio, 'href="/auditoria/logs"'), 'Reportes o Auditoría continúan sin ruta funcional.');
$assert(str_contains($inicio, 'class="bday-date"'), 'La fecha de cumpleaños no usa el contenedor centrado.');
$assert(str_contains($perfil, 'Información laboral e institucional'), 'El perfil no muestra información laboral completa.');
$assert(str_contains($perfil, 'Contacto y domicilio') && str_contains($perfil, 'Información administrativa'), 'El perfil no incluye sus secciones relevantes.');
$assert(str_contains($perfil, '••••••7890') && !str_contains($perfil, '>1234567890<'), 'El perfil expone la cuenta bancaria completa.');
$assert(str_contains($perfil, 'Ecuatoriana') && str_contains($perfil, 'Expediente de prueba'), 'El perfil omite nacionalidad u observaciones registradas.');

if ($fallos) {
    foreach ($fallos as $fallo) fwrite(STDERR, "[FAIL] {$fallo}\n");
    exit(1);
}
echo "[OK] Inicio y expediente digital renderizados correctamente\n";
