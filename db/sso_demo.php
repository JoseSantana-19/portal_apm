<?php
/**
 * DEMO / REVISIÓN del SSO desde el lado del PROYECTO (cliente PHP).
 *
 * Simula lo que hará OTRO MÓDULO para logonear contra el portal usando
 * únicamente los procedimientos almacenados seguros (via libs/SsoClient.php).
 *
 * Uso por CONSOLA:
 *   C:\xampp\php\php.exe db\sso_demo.php admin "Apm2024*"
 *   C:\xampp\php\php.exe db\sso_demo.php admin "clave-mala"      ← ver rechazo
 *
 * Uso por NAVEGADOR (con el proyecto corriendo y sesión de ADMIN iniciada):
 *   http://localhost/portal_apm/db/sso_demo.php
 *   http://localhost/portal_apm/db/sso_demo.php?usuario=admin&clave=Apm2024*
 *   http://localhost/portal_apm/db/sso_demo.php?clave=clave-mala   ← ver rechazo
 *
 * Registra una app temporal DEMO_CLI, corre el ciclo completo
 * (login → token → validar → logout) y limpia al final.
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../libs/SsoClient.php';

$esWeb = (PHP_SAPI !== 'cli');

if ($esWeb) {
    // Solo administradores logueados en el portal pueden correr la demo por web
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    if (empty($_SESSION['user_id']) || (int)($_SESSION['nivel_jerarquia'] ?? 0) < 3) {
        http_response_code(403);
        die('Acceso denegado: inicie sesión en el portal como administrador (nivel 3+) y vuelva a abrir esta página.');
    }
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><meta charset="utf-8"><title>Demo SSO — Portal APM</title>'
       . '<body style="background:#0f172a;color:#e2e8f0;font-family:Consolas,monospace;padding:24px;">'
       . '<h2 style="color:#38bdf8;">Demo SSO de módulos — ciclo completo</h2>'
       . '<p>Parámetros: <code>?usuario=…&clave=…</code> (por defecto admin / Apm2024*)</p><pre>';
    $usuario = trim((string)($_GET['usuario'] ?? 'admin'));
    $clave   = (string)($_GET['clave'] ?? 'Apm2024*');
} else {
    $usuario = $argv[1] ?? 'admin';
    $clave   = $argv[2] ?? 'Apm2024*';
}

$appCodigo = 'DEMO_CLI';
$apiKey    = bin2hex(random_bytes(32)); // 64 chars, distinta en cada corrida

function out(string $titulo, $data): void {
    global $esWeb;
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    echo "\n== {$titulo} ==\n" . ($esWeb ? htmlspecialchars($json, ENT_QUOTES, 'UTF-8') : $json) . "\n";
}

// Conexión admin solo para registrar/limpiar la app de la demo
$opts = ['Database' => DB_NAME, 'CharacterSet' => 'UTF-8', 'TrustServerCertificate' => true, 'Encrypt' => false];
if (DB_USER !== '') { $opts['UID'] = DB_USER; $opts['PWD'] = DB_PASS; }
$admin = sqlsrv_connect(DB_SERVER, $opts);
if ($admin === false) { fwrite(STDERR, print_r(sqlsrv_errors(), true)); exit(1); }

sqlsrv_query($admin, "EXEC dbo.sp_SSO_RegistrarApp @codigo=?, @nombre=N'Demo CLI', @api_key=?, @creado_por=N'sso_demo.php'",
    [$appCodigo, $apiKey]);
echo "App temporal '{$appCodigo}' registrada (api_key aleatoria de esta corrida).\n";

try {
    $sso = new SsoClient(['app' => $appCodigo, 'api_key' => $apiKey, 'ip' => '127.0.0.1']);

    // 1) LOGIN — el SP valida app + cuenta + lockout y entrega el hash;
    //    SsoClient verifica bcrypt y confirma la sesión.
    $login = $sso->login($usuario, $clave);
    out("1) login('{$usuario}', '…')", $login);

    if (!empty($login['ok'])) {
        $token = $login['token'];

        // 2) VALIDAR TOKEN — lo que haría cualquier otro módulo al recibirlo
        out('2) validate(token)', $sso->validate($token));

        // 3) Token manipulado → rechazado
        out('3) validate(token adulterado)', $sso->validate(substr($token, 0, -4) . 'FFFF'));

        // 4) LOGOUT y re-validación → TOKEN_INVALIDO
        $sso->logout($token);
        out('4) validate(token) tras logout', $sso->validate($token));
    }

    // 5) App con api_key equivocada → APP_INVALIDA (sin datos ni hash)
    $ssoMal = new SsoClient(['app' => $appCodigo, 'api_key' => str_repeat('X', 40), 'ip' => '127.0.0.1']);
    out('5) login con api_key de app INCORRECTA', $ssoMal->login($usuario, $clave));

} finally {
    // Limpieza: app demo, sus sesiones y contadores de intentos del usuario
    sqlsrv_query($admin, "DELETE FROM dbo.CORE_Sesiones WHERE user_agent LIKE N'SSO:DEMO_CLI%'");
    sqlsrv_query($admin, "DELETE FROM dbo.CORE_Aplicaciones WHERE codigo = ?", [$appCodigo]);
    sqlsrv_query($admin, "UPDATE dbo.CORE_Usuarios SET intentos_fallidos = 0, fecha_bloqueo = NULL WHERE nombre_usuario = ?", [$usuario]);
    echo "\nDemo limpiada (app temporal eliminada).\n";
    if ($esWeb) {
        echo '</pre><p><a href="' . APP_URL . '/dashboard" style="color:#38bdf8;">← Volver al portal</a></p></body>';
    }
}
