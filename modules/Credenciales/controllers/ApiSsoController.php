<?php
/**
 * ApiSsoController — Endpoints HTTP del SSO central para módulos externos
 * que no tienen acceso directo a SQL Server.
 *
 *   POST /api/sso/login     {app, api_key, username, password}
 *   POST /api/sso/validate  {app, api_key, token}
 *   POST /api/sso/logout    {app, api_key, token}
 *
 * Seguridad:
 *   - Server-to-server: la app llamante se autentica con codigo + api_key
 *     (validados por HASH en los SP sp_SSO_*; nunca se guarda la clave en claro).
 *   - Lockout de cuenta y auditoría los aplica la BD central (mismos SP).
 *   - Mensajes de error genéricos (no revelan si el usuario existe).
 *   - Recomendado exponer solo por HTTPS y/o restringir por IP en el servidor
 *     web; adicionalmente cada app puede tener `ip_permitidas` en BD.
 */
require_once ROOT . '/libs/SsoClient.php';

class ApiSsoController extends Controller
{
    /** Cuerpo JSON o form-data. */
    private function body(): array
    {
        $ct = $_SERVER['CONTENT_TYPE'] ?? '';
        if (stripos($ct, 'application/json') !== false) {
            $data = json_decode(file_get_contents('php://input'), true);
            return is_array($data) ? $data : [];
        }
        return $_POST;
    }

    private function client(array $in): ?SsoClient
    {
        $app = trim((string)($in['app'] ?? ''));
        $key = (string)($in['api_key'] ?? '');
        if ($app === '' || $key === '') {
            return null;
        }
        try {
            return new SsoClient([
                'app'     => $app,
                'api_key' => $key,
                'ip'      => SecurityHelper::getClientIp(),
            ]);
        } catch (Throwable $e) {
            return null;
        }
    }

    public function login(): void
    {
        $in  = $this->body();
        $sso = $this->client($in);
        $user = trim((string)($in['username'] ?? ''));
        $pass = (string)($in['password'] ?? '');

        if (!$sso || $user === '' || $pass === '') {
            $this->json(['ok' => false, 'error' => 'Solicitud inválida.'], 400);
        }

        $r = $sso->login($user, $pass);
        $this->json($r, $r['ok'] ? 200 : 401);
    }

    public function validate(): void
    {
        $in  = $this->body();
        $sso = $this->client($in);
        $token = (string)($in['token'] ?? '');

        if (!$sso || $token === '') {
            $this->json(['ok' => false, 'error' => 'Solicitud inválida.'], 400);
        }

        $r = $sso->validate($token);
        $this->json($r, $r['ok'] ? 200 : 401);
    }

    public function logout(): void
    {
        $in  = $this->body();
        $sso = $this->client($in);
        $token = (string)($in['token'] ?? '');

        if (!$sso || $token === '') {
            $this->json(['ok' => false, 'error' => 'Solicitud inválida.'], 400);
        }

        $sso->logout($token);
        $this->json(['ok' => true]);
    }
}
