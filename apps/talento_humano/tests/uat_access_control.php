<?php
declare(strict_types=1);

define('ROOT', dirname(__DIR__));
$_SERVER['HTTPS'] = 'on';
$_SERVER['HTTP_USER_AGENT'] = 'PortalAPM-UAT/1.0';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

require_once ROOT.'/core/Config.php';
require_once ROOT.'/core/Database.php';
require_once ROOT.'/core/Auth.php';

Auth::configureSession();
if (session_status() === PHP_SESSION_NONE) {
    session_id('uat-'.bin2hex(random_bytes(8)));
    session_start();
}

$db = Conexion::conectar();
$failures = [];
$assert = static function (bool $condition, string $message) use (&$failures): void {
    if (!$condition) $failures[] = $message;
};

$modules = $db->query('SELECT modulo_id,codigo_modulo,nombre_modulo FROM dbo.th_modulos')->fetchAll(PDO::FETCH_ASSOC);
$roles = $db->query(
    'SELECT r.rol_id,r.nombre_rol,r.estado,
            (SELECT COUNT_BIG(*) FROM dbo.th_permisos_rol p WHERE p.rol_id=r.rol_id) permisos,
            (SELECT COUNT_BIG(*) FROM dbo.th_usuarios_sistema u WHERE u.rol_id=r.rol_id AND u.estado=1) usuarios_activos
     FROM dbo.th_roles r ORDER BY r.rol_id'
)->fetchAll(PDO::FETCH_ASSOC);
$users = $db->query(
    'SELECT u.usuario_id,u.usuario,u.password_hash,u.estado,u.empleado_id,u.token_version,
            u.mfa_habilitado,u.mfa_secreto_enc,u.mfa_activado_en,r.estado rol_estado
     FROM dbo.th_usuarios_sistema u JOIN dbo.th_roles r ON r.rol_id=u.rol_id'
)->fetchAll(PDO::FETCH_ASSOC);

$moduleCodes = array_column($modules, 'codigo_modulo');
$assert(count($modules) > 0, 'No existen módulos RBAC.');
$assert(count($roles) > 0, 'No existen roles del sistema.');
$assert(count($users) > 0, 'No existen cuentas de acceso.');
$duplicateCodes = array_keys(array_filter(array_count_values($moduleCodes), static fn(int $count): bool => $count > 1));
$duplicateDetails = array_map(
    static fn(array $module): string => $module['codigo_modulo'].'#'.$module['modulo_id'].'('.$module['nombre_modulo'].')',
    array_values(array_filter($modules, static fn(array $module): bool => in_array($module['codigo_modulo'], $duplicateCodes, true)))
);
$assert($duplicateCodes === [], 'Hay códigos de módulo RBAC repetidos: '.implode(', ', $duplicateDetails).'.');
foreach (['dashboard','directorio','empleados','acciones','movimientos','socioeconomico','biblioteca','usuarios','roles','maestros','auditoria','reportes'] as $required) {
    $assert(in_array($required, $moduleCodes, true), "Falta el módulo RBAC {$required}.");
}
foreach ($roles as $role) {
    if ((bool)$role['estado']) {
        $assert((int)$role['permisos'] === count($modules), "El rol #{$role['rol_id']} no tiene una fila por módulo.");
    }
}
$superPermissions = (int)$db->query(
    'SELECT COUNT_BIG(*) FROM dbo.th_permisos_rol
     WHERE rol_id=1 AND puede_visualizar=1 AND puede_crear=1 AND puede_editar=1 AND puede_eliminar=1'
)->fetchColumn();
$assert($superPermissions === count($modules), 'Super Administrador no conserva permisos completos.');

$seenUsers = [];
$seenEmployees = [];
foreach ($users as $user) {
    $username = strtolower((string)$user['usuario']);
    $assert(!isset($seenUsers[$username]), "La cuenta {$username} está repetida.");
    $seenUsers[$username] = true;
    if ($user['empleado_id'] !== null) {
        $employee = (string)$user['empleado_id'];
        $assert(!isset($seenEmployees[$employee]), "El empleado #{$employee} tiene más de una cuenta.");
        $seenEmployees[$employee] = true;
    }
    $assert((password_get_info((string)$user['password_hash'])['algoName'] ?? 'unknown') !== 'unknown', "La cuenta {$username} no usa password_hash.");
    $assert((int)$user['token_version'] >= 1, "La cuenta {$username} tiene versión de token inválida.");
    if ((bool)$user['estado']) $assert((bool)$user['rol_estado'], "La cuenta activa {$username} pertenece a un rol inactivo.");
    $mfaConsistent = (bool)$user['mfa_habilitado']
        ? !empty($user['mfa_secreto_enc']) && !empty($user['mfa_activado_en'])
        : empty($user['mfa_secreto_enc']) && empty($user['mfa_activado_en']);
    $assert($mfaConsistent, "La cuenta {$username} tiene un estado MFA inconsistente.");
}

$auditSummary = $db->query('SELECT TOP 1 usuario,total_eventos FROM dbo.vw_th_resumen_auditoria_usuarios ORDER BY total_eventos DESC')->fetch(PDO::FETCH_ASSOC);
$assert(is_array($auditSummary), 'El reporte agregado de auditoría no devuelve información.');

$suffix = bin2hex(random_bytes(5));
$roleName = 'UAT transaccional '.$suffix;
$username = 'uat_'.$suffix;
$password = 'UatInicial!'.substr($suffix, 0, 4).'9a';
$newPassword = 'UatRenovada!'.substr($suffix, 0, 4).'8B';
$roleId = 0;
$userId = 0;

$db->beginTransaction();
try {
    $statement = $db->prepare('INSERT dbo.th_roles(nombre_rol,estado) OUTPUT INSERTED.rol_id VALUES(:nombre,1)');
    $statement->execute([':nombre' => $roleName]);
    $roleId = (int)$statement->fetchColumn();
    $assert($roleId > 1, 'No se creó el rol temporal UAT.');

    $statement = $db->prepare(
        'INSERT dbo.th_permisos_rol(rol_id,modulo_id,puede_visualizar,puede_crear,puede_editar,puede_eliminar)
         SELECT :rol,modulo_id,
                CASE WHEN codigo_modulo IN (\'dashboard\',\'directorio\') THEN 1 ELSE 0 END,
                0,0,0 FROM dbo.th_modulos'
    );
    $statement->execute([':rol' => $roleId]);

    $statement = $db->prepare('EXEC dbo.sp_th_crear_usuario_sistema :usuario,:hash,:correo,:nombre,NULL,:rol');
    $statement->execute([
        ':usuario' => $username,
        ':hash' => password_hash($password, PASSWORD_DEFAULT),
        ':correo' => $username.'@uat.invalid',
        ':nombre' => 'Cuenta temporal UAT',
        ':rol' => $roleId,
    ]);
    $userId = (int)$statement->fetchColumn();
    while ($statement->nextRowset()) {}
    $assert($userId > 0, 'No se creó la cuenta temporal UAT.');

    $assert(Auth::attempt($username, $password), 'El login correcto fue rechazado.');
    $token = (string)($_SESSION['auth_token'] ?? '');
    $claims = Auth::user();
    $assert($token !== '' && !str_contains($token, $username), 'El token no existe o expone el usuario en texto claro.');
    $assert(($claims['usr'] ?? '') === $username && ($claims['role_id'] ?? 0) === $roleId, 'El token no conserva la identidad y rol esperados.');
    $assert(Auth::can('dashboard','visualizar'), 'El permiso concedido dashboard.visualizar fue rechazado.');
    $assert(Auth::can('directorio','visualizar'), 'El permiso concedido directorio.visualizar fue rechazado.');
    $assert(!Auth::can('usuarios','visualizar'), 'Un permiso no concedido fue aceptado.');
    $assert(!Auth::can('dashboard','accion_invalida'), 'Una acción RBAC desconocida fue aceptada.');
    $csrf = Auth::csrfToken();
    $assert(Auth::validateCsrf($csrf) && !Auth::validateCsrf($csrf.'x'), 'La validación CSRF no diferencia tokens válidos y alterados.');

    $passwordChange = Auth::changePassword($password, $newPassword);
    $assert((bool)($passwordChange['success'] ?? false), 'No se completó el cambio obligatorio de clave.');

    $enrollment = Auth::prepareMfaEnrollment();
    $reflection = new ReflectionClass(Auth::class);
    $totpAt = $reflection->getMethod('totpAt');
    $currentStep = (int)floor(time() / 30);
    $activationCode = (string)$totpAt->invoke(null, (string)$enrollment['secret'], $currentStep);
    $activation = Auth::activateMfa($activationCode);
    $assert((bool)($activation['success'] ?? false), 'No se pudo activar MFA con un TOTP válido.');

    $statement = $db->prepare('SELECT mfa_secreto_enc FROM dbo.th_usuarios_sistema WHERE usuario_id=:id');
    $statement->execute([':id' => $userId]);
    $encryptedSecret = (string)$statement->fetchColumn();
    $assert($encryptedSecret !== '' && $encryptedSecret !== (string)$enrollment['secret'], 'El secreto MFA quedó vacío o en texto claro.');
    $db->prepare('UPDATE dbo.th_usuarios_sistema SET mfa_ultimo_paso=NULL WHERE usuario_id=:id')->execute([':id' => $userId]);

    Auth::logout();
    $assert(!Auth::attempt($username, $newPassword) && Auth::mfaPending(), 'El segundo login no solicitó MFA.');
    $verificationCode = (string)$totpAt->invoke(null, (string)$enrollment['secret'], (int)floor(time() / 30));
    $mfaResult = Auth::verifyMfa($verificationCode);
    $assert((bool)($mfaResult['success'] ?? false) && !empty(Auth::user()['mfa']), 'El TOTP válido no estableció una sesión MFA.');

    Auth::logout();
    $assert(!Auth::attempt($username, $newPassword) && Auth::mfaPending(), 'No se abrió el reto MFA para probar reutilización.');
    $replay = Auth::verifyMfa($verificationCode);
    $assert(!(bool)($replay['success'] ?? false), 'El mismo paso TOTP pudo reutilizarse.');
    Auth::cancelMfa();

    $statement = $db->prepare(
        "SELECT COUNT_BIG(*) FROM dbo.th_logs_auditoria
         WHERE usuario=:usuario AND accion IN ('LOGIN','MFA_ACTIVADO','MFA_SOLICITADO','MFA_VALIDADO','MFA_FALLIDO')"
    );
    $statement->execute([':usuario' => $username]);
    $assert((int)$statement->fetchColumn() >= 5, 'El ciclo de autenticación no produjo la auditoría esperada.');
} catch (Throwable $error) {
    $failures[] = 'La UAT transaccional falló: '.$error->getMessage();
} finally {
    if ($db->inTransaction()) $db->rollBack();
    unset($_SESSION['auth_token'], $_SESSION['csrf_token'], $_SESSION['last_activity'], $_SESSION['mfa_pending'], $_SESSION['mfa_enrollment']);
}

if ($roleId > 0) {
    $statement = $db->prepare('SELECT (SELECT COUNT_BIG(*) FROM dbo.th_roles WHERE rol_id=:rol) roles,(SELECT COUNT_BIG(*) FROM dbo.th_usuarios_sistema WHERE usuario_id=:usuario) usuarios');
    $statement->execute([':rol' => $roleId, ':usuario' => $userId]);
    $cleanup = $statement->fetch(PDO::FETCH_ASSOC) ?: [];
    $assert((int)($cleanup['roles'] ?? 1) === 0 && (int)($cleanup['usuarios'] ?? 1) === 0, 'La UAT dejó rol o cuenta temporal en la base.');
}

if (session_status() === PHP_SESSION_ACTIVE) session_destroy();

if ($failures) {
    foreach ($failures as $failure) fwrite(STDERR, "[FAIL] {$failure}".PHP_EOL);
    exit(1);
}

echo '[OK] inventario RBAC: '.count($roles).' roles, '.count($users).' cuentas y '.count($modules).' módulos'.PHP_EOL;
echo '[OK] login, token cifrado, permisos, CSRF, cambio de clave, MFA, anti-replay y auditoría'.PHP_EOL;
echo '[OK] rollback: sin cuentas ni roles UAT persistentes'.PHP_EOL;
echo 'UAT_ACCESS_CONTROL=OK'.PHP_EOL;
