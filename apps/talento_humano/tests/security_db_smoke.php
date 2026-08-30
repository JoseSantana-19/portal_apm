<?php
declare(strict_types=1);

define('ROOT', dirname(__DIR__));
require_once ROOT . '/core/Config.php';
require_once ROOT . '/core/Database.php';

$db = Conexion::conectar();
$failures = [];
$assert = static function (bool $condition, string $message) use (&$failures): void {
    if (!$condition) $failures[] = $message;
};

$summary = $db->query('SELECT TOP 1 usuario,total_eventos FROM dbo.vw_th_resumen_auditoria_usuarios ORDER BY total_eventos DESC')->fetch(PDO::FETCH_ASSOC);
$assert(is_array($summary), 'El rol de la aplicación no puede consultar el resumen de auditoría.');

$username = 'qa_mfa_' . bin2hex(random_bytes(4));
$db->beginTransaction();
try {
    $stmt = $db->prepare('EXEC dbo.sp_th_crear_usuario_sistema :usuario,:hash,:correo,:nombre,NULL,:rol');
    $stmt->execute([
        ':usuario' => $username,
        ':hash' => password_hash('Temporal!12345', PASSWORD_DEFAULT),
        ':correo' => $username . '@apm.test',
        ':nombre' => 'Prueba transaccional MFA',
        ':rol' => 1,
    ]);
    $createdId = (int)$stmt->fetchColumn();
    $assert($createdId > 0, 'El procedimiento controlado no creó la cuenta de prueba.');
} catch (Throwable $error) {
    $failures[] = 'El procedimiento controlado de usuarios falló: ' . $error->getMessage();
} finally {
    if ($db->inTransaction()) $db->rollBack();
}

$directInsertDenied = false;
try {
    $stmt = $db->prepare("INSERT dbo.th_usuarios_sistema(usuario,password_hash,nombre,rol_id,estado) VALUES(:usuario,'x','x',1,1)");
    $stmt->execute([':usuario' => $username . '_direct']);
} catch (PDOException $e) {
    $directInsertDenied = true;
}
$assert($directInsertDenied, 'El rol de aplicación todavía puede insertar cuentas directamente.');

$forbiddenUpdateDenied = false;
try {
    $db->exec('UPDATE dbo.th_usuarios_sistema SET nombre=nombre WHERE usuario_id=-1');
} catch (PDOException $e) {
    $forbiddenUpdateDenied = true;
}
$assert($forbiddenUpdateDenied, 'El rol de aplicación puede modificar columnas de identidad no autorizadas.');

try {
    $db->exec('UPDATE dbo.th_usuarios_sistema SET intentos_fallidos=intentos_fallidos WHERE usuario_id=-1');
} catch (PDOException $error) {
    $failures[] = 'El rol perdió una actualización necesaria para autenticación: ' . $error->getMessage();
}

if ($failures) {
    foreach ($failures as $failure) fwrite(STDERR, "[FAIL] {$failure}\n");
    exit(1);
}

echo "[OK] privilegios SQL mínimos y procedimiento de usuarios validados\n";
