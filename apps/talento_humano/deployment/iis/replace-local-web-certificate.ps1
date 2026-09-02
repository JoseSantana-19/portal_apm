param(
    [string]$DnsName = 'portal-apm-preprod.local',
    [string]$FriendlyName = 'Portal APM Web Cert',
    [datetime]$NotAfter = (Get-Date).AddYears(3),
    [string]$SiteName = 'PortalPortuario',
    [switch]$ConfirmReplacement
)

$ErrorActionPreference = 'Stop'

$principal = [Security.Principal.WindowsPrincipal]::new([Security.Principal.WindowsIdentity]::GetCurrent())
if (-not $principal.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)) {
    throw 'Este script debe ejecutarse como administrador.'
}
if (-not $ConfirmReplacement) {
    throw 'La sustitucion crea y vincula un certificado nuevo. Repita con -ConfirmReplacement.'
}
if ($NotAfter -le (Get-Date).AddDays(30)) {
    throw 'La vigencia solicitada para el certificado web es insuficiente.'
}

$certificate = New-SelfSignedCertificate `
    -Type SSLServerAuthentication `
    -Subject "CN=$DnsName" `
    -DnsName $DnsName `
    -CertStoreLocation 'Cert:\LocalMachine\My' `
    -Provider 'Microsoft Software Key Storage Provider' `
    -KeyAlgorithm RSA `
    -KeyLength 2048 `
    -HashAlgorithm SHA256 `
    -KeyExportPolicy NonExportable `
    -NotAfter $NotAfter
$certificate.FriendlyName = $FriendlyName

$privateKey = [System.Security.Cryptography.X509Certificates.RSACertificateExtensions]::GetRSAPrivateKey($certificate)
if ($null -eq $privateKey) {
    throw 'El certificado web fue creado sin una clave privada RSA utilizable.'
}
$privateKey.Dispose()

$publicPath = Join-Path $env:TEMP ("portal-apm-web-$($certificate.Thumbprint).cer")
try {
    Export-Certificate -Cert $certificate -FilePath $publicPath -Force | Out-Null
    Import-Certificate -FilePath $publicPath -CertStoreLocation 'Cert:\LocalMachine\Root' | Out-Null
}
finally {
    if (Test-Path -LiteralPath $publicPath) {
        Remove-Item -LiteralPath $publicPath -Force
    }
}

Import-Module WebAdministration
if (-not (Test-Path "IIS:\Sites\$SiteName")) {
    throw "No existe el sitio IIS $SiteName."
}
if (-not (Get-WebBinding -Name $SiteName -Protocol https -ErrorAction SilentlyContinue |
    Where-Object { $_.bindingInformation -eq "*:443:$DnsName" })) {
    New-WebBinding -Name $SiteName -Protocol https -Port 443 -HostHeader $DnsName -SslFlags 1
}

$bindingPath = "IIS:\SslBindings\0.0.0.0!443!$DnsName"
& netsh.exe http delete sslcert "hostnameport=${DnsName}:443" | Out-Null
$certificate | New-Item $bindingPath -SSLFlags 1 -Force | Out-Null

$appPool = (Get-Item "IIS:\Sites\$SiteName").applicationPool
Restart-WebAppPool -Name $appPool
Start-Website -Name $SiteName -ErrorAction SilentlyContinue

Write-Output "CERTIFICATE=$($certificate.Thumbprint)"
Write-Output "CERTIFICATE_EXPIRES=$($certificate.NotAfter.ToString('s'))"
Write-Output "BINDING=https://${DnsName}/"
