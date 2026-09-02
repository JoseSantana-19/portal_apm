param(
    [string]$DnsName = 'portal-apm-preprod.local',
    [string]$SqlServiceName = 'MSSQLSERVER',
    [string]$SqlFriendlyName = 'Portal APM SQL Cert',
    [string]$WebFriendlyName = 'Portal APM Web Cert'
)

$ErrorActionPreference = 'Stop'

function Test-PrivateKey([System.Security.Cryptography.X509Certificates.X509Certificate2]$Certificate) {
    try {
        $key = [System.Security.Cryptography.X509Certificates.RSACertificateExtensions]::GetRSAPrivateKey($Certificate)
        if ($null -eq $key) { return $false }
        $key.Dispose()
        return $true
    }
    catch [System.Security.Cryptography.CryptographicException] {
        return $false
    }
}

$instanceId = (Get-ItemProperty -LiteralPath 'HKLM:\SOFTWARE\Microsoft\Microsoft SQL Server\Instance Names\SQL').$SqlServiceName
$socketPath = "HKLM:\SOFTWARE\Microsoft\Microsoft SQL Server\$instanceId\MSSQLServer\SuperSocketNetLib"
$socket = Get-ItemProperty -LiteralPath $socketPath
$sqlThumbprint = ([string]$socket.Certificate).ToUpperInvariant()
$sqlCertificate = Get-Item -LiteralPath "Cert:\LocalMachine\My\$sqlThumbprint" -ErrorAction Stop
$sqlPrivateKeyUsable = Test-PrivateKey $sqlCertificate
if ($sqlCertificate.FriendlyName -ne $SqlFriendlyName -or -not $sqlPrivateKeyUsable) {
    throw 'El certificado SQL activo no tiene una clave privada utilizable.'
}
if (-not (Test-Path -LiteralPath "Cert:\LocalMachine\Root\$sqlThumbprint")) {
    throw 'El certificado SQL activo no esta en el almacen de confianza local.'
}
if ([int]$socket.ForceEncryption -ne 1) {
    throw 'SQL Server no tiene ForceEncryption habilitado.'
}

$binding = (& netsh.exe http show sslcert "hostnameport=${DnsName}:443" | Out-String)
$webCertificate = Get-ChildItem Cert:\LocalMachine\My |
    Where-Object { $_.FriendlyName -eq $WebFriendlyName -and $binding -match $_.Thumbprint.ToLowerInvariant() } |
    Select-Object -First 1
$webPrivateKeyUsable = $null -ne $webCertificate -and (Test-PrivateKey $webCertificate)
if (-not $webPrivateKeyUsable) {
    throw 'El enlace HTTPS no usa un certificado Web con clave privada utilizable.'
}
if (-not (Test-Path -LiteralPath "Cert:\LocalMachine\Root\$($webCertificate.Thumbprint)")) {
    throw 'El certificado Web activo no esta en el almacen de confianza local.'
}

Write-Output "SQL_CERTIFICATE=$sqlThumbprint"
Write-Output "SQL_FORCE_ENCRYPTION=1"
Write-Output "WEB_CERTIFICATE=$($webCertificate.Thumbprint)"
Write-Output "CERTIFICATES=OK"
