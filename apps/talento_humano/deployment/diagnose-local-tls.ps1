param(
    [string]$DnsName = 'portal-apm-preprod.local',
    [string]$SqlServiceName = 'MSSQLSERVER'
)

$ErrorActionPreference = 'Continue'
$principal = [Security.Principal.WindowsPrincipal]::new([Security.Principal.WindowsIdentity]::GetCurrent())
if (-not $principal.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)) {
    throw 'Este diagnostico debe ejecutarse como administrador.'
}

$logDirectory = Join-Path $env:ProgramData 'PortalAPM'
New-Item -ItemType Directory -Path $logDirectory -Force | Out-Null
$logPath = Join-Path $logDirectory 'diagnose-local-tls.log'
Start-Transcript -LiteralPath $logPath -Force
try {
    $instanceId = (Get-ItemProperty -LiteralPath 'HKLM:\SOFTWARE\Microsoft\Microsoft SQL Server\Instance Names\SQL').$SqlServiceName
    $socketPath = "HKLM:\SOFTWARE\Microsoft\Microsoft SQL Server\$instanceId\MSSQLServer\SuperSocketNetLib"
    $socket = Get-ItemProperty -LiteralPath $socketPath
    $sqlThumbprint = ([string]$socket.Certificate).ToUpperInvariant()
    $sqlCertificate = Get-Item -LiteralPath "Cert:\LocalMachine\My\$sqlThumbprint" -ErrorAction Stop
    $sqlKey = [System.Security.Cryptography.X509Certificates.RSACertificateExtensions]::GetRSAPrivateKey($sqlCertificate)
    Write-Output "SQL_CERTIFICATE=$sqlThumbprint"
    Write-Output "SQL_PRIVATE_KEY=$($null -ne $sqlKey)"
    Write-Output "SQL_FORCE_ENCRYPTION=$($socket.ForceEncryption)"
    if ($null -ne $sqlKey) { $sqlKey.Dispose() }

    Import-Module WebAdministration
    Get-WebBinding -Name PortalPortuario | Format-List protocol,bindingInformation,sslFlags
    Get-ChildItem IIS:\SslBindings | Format-List *
    & netsh.exe http show sslcert "hostnameport=${DnsName}:443"
    Get-NetTCPConnection -State Listen |
        Where-Object LocalPort -in 80,443,1433 |
        Format-Table LocalAddress,LocalPort,OwningProcess -AutoSize

    $webCertificate = Get-ChildItem Cert:\LocalMachine\My |
        Where-Object FriendlyName -eq 'Portal APM Web Cert' |
        Sort-Object NotBefore -Descending |
        Select-Object -First 1
    $webKey = [System.Security.Cryptography.X509Certificates.RSACertificateExtensions]::GetRSAPrivateKey($webCertificate)
    Write-Output "WEB_CERTIFICATE=$($webCertificate.Thumbprint)"
    Write-Output "WEB_PRIVATE_KEY=$($null -ne $webKey)"
    if ($null -ne $webKey) { $webKey.Dispose() }
    Write-Output "LOG=$logPath"
}
finally {
    Stop-Transcript
}
