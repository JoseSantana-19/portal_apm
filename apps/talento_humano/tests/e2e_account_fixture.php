<?php
declare(strict_types=1);

define('ROOT', dirname(__DIR__));
require_once ROOT.'/core/Config.php';

function failFixture(string $message): never
{
    fwrite(STDERR, '[FAIL] '.$message.PHP_EOL);
    exit(1);
}

$options = getopt('', ['setup','cleanup','username:','confirm-username:']);
$setup = array_key_exists('setup', $options);
$cleanup = array_key_exists('cleanup', $options);
$username = strtolower(trim((string)($options['username'] ?? '')));
$confirmation = strtolower(trim((string)($options['confirm-username'] ?? '')));
if ($setup === $cleanup) failFixture('Indique exactamente --setup o --cleanup.');
if (!preg_match('/^e2e_local_[a-z0-9]{8,20}$/', $username) || $confirmation !== $username) {
    failFixture('El usuario y su confirmación no cumplen el patrón E2E seguro.');
}

$dbaUser = getenv('PORTAL_DBA_USER') ?: '';
$dbaPassword = getenv('PORTAL_DBA_PASSWORD') ?: '';
if ($dbaUser === '' || $dbaPassword === '') failFixture('Faltan credenciales DBA transitorias.');
$password = getenv('PORTAL_E2E_PASSWORD') ?: '';
if ($setup && (strlen($password) < 14 || !preg_match('/[A-Z]/',$password) || !preg_match('/[a-z]/',$password) || !preg_match('/\d/',$password) || !preg_match('/[^A-Za-z0-9]/',$password))) {
    failFixture('La clave E2E transitoria no cumple la política.');
}

$config = Config::database();
$driver = (string)$config['driver'];
$dsn = "sqlsrv:Driver={$driver};Server={$config['server']};Database={$config['database']};Encrypt=true;TrustServerCertificate=false";
try {
    $db = new PDO($dsn, $dbaUser, $dbaPassword, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $encrypted = (string)$db->query("SELECT encrypt_option FROM sys.dm_exec_connections WHERE session_id=@@SPID")->fetchColumn();
    if (strtoupper($encrypted) !== 'TRUE') failFixture('La conexión DBA no está cifrada.');

    $exists = $db->prepare('SELECT usuario_id,empleado_id FROM dbo.th_usuarios_sistema WHERE usuario=:usuario');
    $exists->execute([':usuario' => $username]);
    $current = $exists->fetch(PDO::FETCH_ASSOC);

    if ($setup) {
        if ($current) failFixture('La cuenta E2E temporal ya existe.');
        $statement = $db->prepare('EXEC dbo.sp_th_crear_usuario_sistema :usuario,:hash,:correo,:nombre,NULL,1');
        $statement->execute([
            ':usuario' => $username,
            ':hash' => password_hash($password, PASSWORD_DEFAULT),
            ':correo' => $username.'@uat.invalid',
            ':nombre' => 'Navegación E2E temporal',
        ]);
        $id = (int)$statement->fetchColumn();
        while ($statement->nextRowset()) {}
        if ($id <= 0) failFixture('No se creó la cuenta E2E temporal.');
        $db->prepare('UPDATE dbo.th_usuarios_sistema SET debe_cambiar_clave=0 WHERE usuario_id=:id')->execute([':id' => $id]);
        $audit = $db->prepare("EXEC dbo.sp_th_registrar_auditoria :usuario,'Pruebas E2E','CREAR_FIXTURE','Cuenta temporal sin empleado asociado.','127.0.0.1'");
        $audit->execute([':usuario' => $username]);
        echo 'E2E_FIXTURE=READY'.PHP_EOL;
    } else {
        if (!$current) {
            echo 'E2E_FIXTURE=ALREADY_CLEAN'.PHP_EOL;
            exit(0);
        }
        if ($current['empleado_id'] !== null) failFixture('La cuenta está vinculada a un empleado y no puede eliminarse como fixture.');
        $audit = $db->prepare("EXEC dbo.sp_th_registrar_auditoria :usuario,'Pruebas E2E','ELIMINAR_FIXTURE','Cuenta temporal retirada al finalizar.','127.0.0.1'");
        $audit->execute([':usuario' => $username]);
        while ($audit->nextRowset()) {}
        $delete = $db->prepare('DELETE FROM dbo.th_usuarios_sistema WHERE usuario=:usuario AND empleado_id IS NULL');
        $delete->execute([':usuario' => $username]);
        if ($delete->rowCount() !== 1) failFixture('No se eliminó exactamente una cuenta E2E.');
        echo 'E2E_FIXTURE=CLEAN'.PHP_EOL;
    }
} catch (Throwable $error) {
    failFixture(preg_replace('/\s+/', ' ', $error->getMessage()) ?? 'Error desconocido.');
}
