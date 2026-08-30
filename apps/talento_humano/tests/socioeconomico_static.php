<?php
declare(strict_types=1);
require dirname(__DIR__,2) . '/helpers/polyfills_php74.php';

$root = dirname(__DIR__);
$fallos = [];
$assert = static function (bool $condicion, string $mensaje) use (&$fallos): void {
    if (!$condicion) $fallos[] = $mensaje;
};

$vista = (string)file_get_contents($root . '/modules/talento-humano/Vistas/estudio_seguridad.php');
$controlador = (string)file_get_contents($root . '/modules/talento-humano/Controladores/EstudioSeguridadController.php');
$modelo = (string)file_get_contents($root . '/modules/talento-humano/Modelos/EstudioSeguridadModel.php');
$pdf = (string)file_get_contents($root . '/modules/talento-humano/Servicios/PdfEstudioSocioeconomico.php');
$migracion = (string)file_get_contents($root . '/database/migracion_cierre_produccion_20260806.sql');

$assert(str_contains($vista, 'id="btnGuardarEstudio"') && str_contains($vista, "addEventListener('submit'"), 'El formulario no protege el envío o no muestra el estado Guardando.');
$assert(str_contains($vista, 'appearance: textfield') && str_contains($vista, '-webkit-inner-spin-button'), 'Los campos numéricos todavía muestran flechas laterales.');
$assert(str_contains($controlador, "'contacto_nombre'=>\$e['contacto_emergencia']") && str_contains($controlador, "'contacto_tel_cel'=>\$e['tel_emergencia']"), 'El contacto de emergencia no se precarga desde el expediente.');
$assert(str_contains($controlador, "\$_SESSION['socio_flash']") && str_contains($controlador, "'errorFormulario'"), 'El formulario pierde los datos cuando ocurre un error al guardar.');
$assert(str_contains($modelo, 'validarDatos($datos, $entrada)') && str_contains($modelo, 'beginTransaction()'), 'El guardado no valida o no es transaccional.');
$assert(str_contains($modelo, "'genero'") && !str_contains($modelo, "'provincia_ciudad_nac','sexo'"), 'El modelo socioeconómico no coincide con la columna genero de su tabla.');
$assert(str_contains($vista, 'id="genero" name="genero"') && str_contains($controlador, "'genero'=>\$e['genero'] ?? \$e['sexo']"), 'La precarga y el formulario socioeconómico no conservan el contrato genero/sexo correcto.');
$assert(str_contains($pdf, "'GENERO','genero'"), 'El PDF socioeconómico no imprime el género guardado.');
$assert(str_contains($modelo, 'for($i=1;$i<=3;$i++)') || str_contains($modelo, 'for ($i=1;$i<=3;$i++)'), 'Las colecciones no respetan las tres filas del formato autorizado.');
$assert(str_contains($pdf, 'celdaTablaMultilinea') && str_contains($pdf, 'envolverTexto'), 'La experiencia laboral del PDF puede superponer textos largos.');
$assert(str_contains($migracion, 'UX_th_estudios_empleado_vigente'), 'La base no impide más de un estudio vigente por funcionario.');

if ($fallos) {
    foreach ($fallos as $fallo) fwrite(STDERR, "[FAIL] {$fallo}\n");
    exit(1);
}

echo "[OK] flujo socioeconómico, persistencia y PDF\n";
