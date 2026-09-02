<?php
declare(strict_types=1);

define('ROOT', dirname(__DIR__));
require_once ROOT.'/core/Config.php';

function stop(string $message): never
{
    fwrite(STDERR, '[FAIL] '.$message.PHP_EOL);
    exit(1);
}

$dbaUser = getenv('PORTAL_DBA_USER') ?: '';
$dbaPassword = getenv('PORTAL_DBA_PASSWORD') ?: '';
if ($dbaUser === '' || $dbaPassword === '') stop('Faltan credenciales DBA transitorias.');

$config = Config::database();
$driver = (string)$config['driver'];
$server = (string)$config['server'];
$database = (string)$config['database'];
$dsn = "sqlsrv:Driver={$driver};Server={$server};Database={$database};Encrypt=true;TrustServerCertificate=false";

try {
    $dba = new PDO($dsn, $dbaUser, $dbaPassword, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $statement = $dba->prepare(
        'SELECT sp.is_disabled,CONVERT(int,LOGINPROPERTY(sp.name,\'IsLocked\')) is_locked,
                CONVERT(int,PWDCOMPARE(:candidate,sl.password_hash)) password_matches
         FROM sys.server_principals sp JOIN sys.sql_logins sl ON sl.principal_id=sp.principal_id
         WHERE sp.name=:login'
    );
    $statement->execute([':candidate' => (string)$config['password'], ':login' => (string)$config['user']]);
    $status = $statement->fetch(PDO::FETCH_ASSOC);
    if (!$status) stop('El login de aplicación no existe en SQL Server.');
    echo 'LOGIN_DISABLED='.(int)$status['is_disabled'].PHP_EOL;
    echo 'LOGIN_LOCKED='.(int)$status['is_locked'].PHP_EOL;
    echo 'PASSWORD_MATCH='.(int)$status['password_matches'].PHP_EOL;

    $app = new PDO($dsn, (string)$config['user'], (string)$config['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $connection = $app->query('SELECT ORIGINAL_LOGIN() login_name,DB_NAME() database_name')->fetch(PDO::FETCH_ASSOC);
    if (($connection['login_name'] ?? '') !== (string)$config['user'] || ($connection['database_name'] ?? '') !== $database) {
        stop('La conexión no usa la identidad o base configurada.');
    }
    echo 'APP_CONNECTION=OK'.PHP_EOL;
} catch (Throwable $error) {
    stop(preg_replace('/\s+/', ' ', $error->getMessage()) ?? 'Diagnóstico desconocido.');
}
