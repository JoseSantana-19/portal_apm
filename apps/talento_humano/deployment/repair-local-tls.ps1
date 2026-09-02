param(
    [string]$DnsName = 'portal-apm-preprod.local',
    [string]$SqlFriendlyName = 'Portal APM SQL Cert',
    [string]$WebFriendlyName = 'Portal APM Web Cert',
    [switch]$ConfirmRepair
)

$ErrorActionPreference = 'Stop'
$principal = [Security.Principal.WindowsPrincipal]::new([Security.Principal.WindowsIdentity]::GetCurrent())
if (-not $principal.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)) {
    throw 'Este script debe ejecutarse como administrador.'
}
if (-not $ConfirmRepair) {
    throw 'La reparacion sustituye y vuelve a enlazar certificados. Repita con -ConfirmRepair despues de autorizarla.'
}

$logDirectory = Join-Path $env:ProgramData 'PortalAPM'
New-Item -ItemType Directory -Path $logDirectory -Force | Out-Null
$logPath = Join-Path $logDirectory 'repair-local-tls.log'
Start-Transcript -LiteralPath $logPath -Force
try {
    $oldSql = @(Get-ChildItem Cert:\LocalMachine\My |
        Where-Object FriendlyName -eq $SqlFriendlyName |
        Select-Object -ExpandProperty Thumbprint)
    $oldWeb = @(Get-ChildItem Cert:\LocalMachine\My |
        Where-Object FriendlyName -eq $WebFriendlyName |
        Select-Object -ExpandProperty Thumbprint)

    & (Join-Path $PSScriptRoot 'sql\replace-local-sql-certificate.ps1') `
        -DnsName $DnsName -FriendlyName $SqlFriendlyName -ConfirmReplacement

    & (Join-Path $PSScriptRoot 'iis\replace-local-web-certificate.ps1') `
        -DnsName $DnsName -FriendlyName $WebFriendlyName -ConfirmReplacement

    $newSql = Get-ChildItem Cert:\LocalMachine\My |
        Where-Object FriendlyName -eq $SqlFriendlyName |
        Sort-Object NotBefore -Descending | Select-Object -First 1
    $newWeb = Get-ChildItem Cert:\LocalMachine\My |
        Where-Object FriendlyName -eq $WebFriendlyName |
        Sort-Object NotBefore -Descending | Select-Object -First 1

    foreach ($certificate in @($newSql, $newWeb)) {
        if ($null -eq $certificate) {
            throw 'No fue posible localizar uno de los certificados sustituidos.'
        }
        $key = [System.Security.Cryptography.X509Certificates.RSACertificateExtensions]::GetRSAPrivateKey($certificate)
        if ($null -eq $key) {
            throw "La clave privada no es utilizable para $($certificate.FriendlyName)."
        }
        $key.Dispose()
    }

    Write-Output "OLD_SQL=$($oldSql -join ',')"
    Write-Output "OLD_WEB=$($oldWeb -join ',')"
    Write-Output "NEW_SQL=$($newSql.Thumbprint)"
    Write-Output "NEW_WEB=$($newWeb.Thumbprint)"
    Write-Output "LOG=$logPath"
}
finally {
    Stop-Transcript
}
