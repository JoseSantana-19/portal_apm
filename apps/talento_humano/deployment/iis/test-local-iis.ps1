param(
    [string]$HostName = 'portal-apm-preprod.local',
    [string]$OutputPath = ''
)

$ErrorActionPreference = 'Stop'

function Get-HttpResult(
    [string]$Uri,
    [bool]$AllowRedirect = $true,
    [System.Net.CookieContainer]$Cookies = $null,
    [string]$Method = 'GET',
    [string]$Body = ''
) {
    $request = [System.Net.HttpWebRequest]::Create($Uri)
    $request.AllowAutoRedirect = $AllowRedirect
    $request.Method = $Method
    if ($null -ne $Cookies) { $request.CookieContainer = $Cookies }
    if ($Method -eq 'POST') {
        $payload = [System.Text.Encoding]::UTF8.GetBytes($Body)
        $request.ContentType = 'application/x-www-form-urlencoded'
        $request.ContentLength = $payload.Length
        $requestStream = $request.GetRequestStream()
        try { $requestStream.Write($payload, 0, $payload.Length) }
        finally { $requestStream.Dispose() }
    }
    try {
        $response = $request.GetResponse()
    }
    catch [System.Net.WebException] {
        if (-not $_.Exception.Response) { throw }
        $response = $_.Exception.Response
    }
    try {
        $body = ''
        $stream = $response.GetResponseStream()
        if ($null -ne $stream) {
            $reader = [System.IO.StreamReader]::new($stream)
            try { $body = $reader.ReadToEnd() }
            finally { $reader.Dispose() }
        }
        return [pscustomobject]@{
            StatusCode = [int]$response.StatusCode
            Headers = $response.Headers
            Body = $body
        }
    }
    finally {
        $response.Close()
    }
}

$result = [System.Collections.Generic.List[string]]::new()
$exitCode = 0
try {
    $http = Get-HttpResult -Uri "http://$HostName/login" -AllowRedirect $false
    if ([int]$http.StatusCode -ne 301) { throw "HTTP no redirige con 301: $([int]$http.StatusCode)" }

    $cookies = [System.Net.CookieContainer]::new()
    $login = Get-HttpResult -Uri "https://$HostName/login" -Cookies $cookies
    if ([int]$login.StatusCode -ne 200) { throw "El login HTTPS no responde 200: $([int]$login.StatusCode)" }
    if ($login.Body -notmatch '/public/css/login\.css') {
        throw 'El login no referencia la hoja de estilos local.'
    }

    $csrf = [regex]::Match($login.Body, 'name="_csrf" value="([^"]+)"')
    if (-not $csrf.Success) { throw 'El login HTTPS no entregó un token CSRF.' }
    $probeBody = '_csrf=' + [System.Net.WebUtility]::UrlEncode($csrf.Groups[1].Value) +
        '&usuario=codex_iis_probe&clave=INVALIDA-no-es-una-clave-real-2026%21'
    $loginProbe = Get-HttpResult -Uri "https://$HostName/login/autenticar" -Cookies $cookies -Method 'POST' -Body $probeBody
    if ([int]$loginProbe.StatusCode -ne 200 -or $loginProbe.Body -notmatch 'Usuario o clave incorrectos\.') {
        throw 'El flujo real de login bajo IIS no pudo consultar SQL cifrado o no devolvió el error funcional esperado.'
    }

    $loginCss = Get-HttpResult -Uri "https://$HostName/public/css/login.css"
    if ([int]$loginCss.StatusCode -ne 200) {
        throw "La hoja login.css no responde 200: $([int]$loginCss.StatusCode)"
    }
    if ([string]$loginCss.Headers['Content-Type'] -notmatch '^text/css' -or $loginCss.Body -notmatch '\.login-card') {
        throw 'login.css no se entrega como CSS valido o no contiene las reglas esperadas.'
    }

    $requiredHeaders = @('Strict-Transport-Security', 'Content-Security-Policy', 'X-Frame-Options')
    foreach ($header in $requiredHeaders) {
        if ([string]::IsNullOrWhiteSpace([string]$login.Headers[$header])) {
            throw "Falta la cabecera de seguridad $header."
        }
    }

    foreach ($path in @('/core/Config.php', '/README.md', '/.git/config', '/storage/logs/portal.log')) {
        $response = Get-HttpResult -Uri "https://$HostName$path"
        if ([int]$response.StatusCode -ne 404) {
            throw "El recurso interno $path respondió $([int]$response.StatusCode) en lugar de 404."
        }
    }

    $result.Add('HTTP_REDIRECT=301')
    $result.Add('HTTPS_LOGIN=200')
    $result.Add('IIS_SQL_LOGIN_FLOW=OK')
    $result.Add('LOCAL_CSS=200')
    $result.Add('SECURITY_HEADERS=OK')
    $result.Add('INTERNAL_PATHS=404')
}
catch {
    $exitCode = 1
    $result.Add("ERROR=$($_.Exception.Message)")
}

if (-not [string]::IsNullOrWhiteSpace($OutputPath)) {
    $result | Set-Content -LiteralPath $OutputPath -Encoding UTF8
}
$result
exit $exitCode
