param(
    [Parameter(Mandatory = $true)][string]$ActiveSqlThumbprint,
    [Parameter(Mandatory = $true)][string]$ActiveWebThumbprint,
    [string]$DnsName = 'portal-apm-preprod.local',
    [string]$SqlServiceName = 'MSSQLSERVER',
    [switch]$ConfirmCleanup
)

$ErrorActionPreference = 'Stop'
$principal = [Security.Principal.WindowsPrincipal]::new([Security.Principal.WindowsIdentity]::GetCurrent())
if (-not $principal.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)) {
    throw 'Este script debe ejecutarse como administrador.'
}
if (-not $ConfirmCleanup) {
    throw 'La limpieza elimina certificados obsoletos. Repita con -ConfirmCleanup.'
}

$activeSql = ($ActiveSqlThumbprint -replace '\s','').ToUpperInvariant()
$activeWeb = ($ActiveWebThumbprint -replace '\s','').ToUpperInvariant()
$instanceId = (Get-ItemProperty -LiteralPath 'HKLM:\SOFTWARE\Microsoft\Microsoft SQL Server\Instance Names\SQL').$SqlServiceName
if ([string]::IsNullOrWhiteSpace($instanceId)) {
    throw "No se encontro la instancia asociada al servicio $SqlServiceName."
}
$socketPath = "HKLM:\SOFTWARE\Microsoft\Microsoft SQL Server\$instanceId\MSSQLServer\SuperSocketNetLib"
$sqlRegistry = Get-ItemProperty -LiteralPath $socketPath
if (([string]$sqlRegistry.Certificate).ToUpperInvariant() -ne $activeSql) {
    throw 'La huella SQL indicada no coincide con el certificado activo.'
}
$binding = (& netsh.exe http show sslcert "hostnameport=${DnsName}:443" | Out-String).ToUpperInvariant()
if ($binding -notmatch $activeWeb) {
    throw 'La huella Web indicada no coincide con el enlace HTTPS activo.'
}

foreach ($thumbprint in @($activeSql,$activeWeb)) {
    $certificate = Get-Item -LiteralPath "Cert:\LocalMachine\My\$thumbprint" -ErrorAction Stop
    $key = [System.Security.Cryptography.X509Certificates.RSACertificateExtensions]::GetRSAPrivateKey($certificate)
    if ($null -eq $key) { throw "La clave privada activa no es utilizable: $thumbprint" }
    $key.Dispose()
}

$obsolete = @(Get-ChildItem Cert:\LocalMachine\My | Where-Object {
    $_.FriendlyName -in @('Portal APM SQL Cert','Portal APM Web Cert') -and
    $_.Thumbprint -notin @($activeSql,$activeWeb)
})
foreach ($certificate in $obsolete) {
    $thumbprint = $certificate.Thumbprint
    Remove-Item -LiteralPath "Cert:\LocalMachine\My\$thumbprint" -Force
    if (Test-Path -LiteralPath "Cert:\LocalMachine\Root\$thumbprint") {
        Remove-Item -LiteralPath "Cert:\LocalMachine\Root\$thumbprint" -Force
    }
    Write-Output "REMOVED=$thumbprint"
}
Write-Output "REMOVED_COUNT=$($obsolete.Count)"
