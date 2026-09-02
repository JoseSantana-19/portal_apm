param(
    [string]$DnsName = 'portal-apm-preprod.local',
    [string]$FriendlyName = 'Portal APM SQL Cert',
    [datetime]$NotAfter = (Get-Date).AddYears(3),
    [string]$ServiceName = 'MSSQLSERVER',
    [string]$ServiceAccount = 'NT SERVICE\MSSQLSERVER',
    [switch]$ConfirmReplacement
)

$ErrorActionPreference = 'Stop'

$principal = New-Object Security.Principal.WindowsPrincipal([Security.Principal.WindowsIdentity]::GetCurrent())
if (-not $principal.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)) {
    throw 'Este script debe ejecutarse como administrador.'
}
if (-not $ConfirmReplacement) {
    throw 'La sustitución crea un certificado nuevo. Repita con -ConfirmReplacement después de autorizar el cambio.'
}

$certificate = New-SelfSignedCertificate `
    -Type Custom `
    -Subject "CN=$DnsName" `
    -DnsName $DnsName `
    -TextExtension @('2.5.29.37={text}1.3.6.1.5.5.7.3.1') `
    -CertStoreLocation 'Cert:\LocalMachine\My' `
    -Provider 'Microsoft RSA SChannel Cryptographic Provider' `
    -KeyAlgorithm RSA `
    -KeyLength 2048 `
    -HashAlgorithm SHA256 `
    -KeyUsage DigitalSignature,KeyEncipherment `
    -KeySpec KeyExchange `
    -KeyExportPolicy NonExportable `
    -NotAfter $NotAfter
$certificate.FriendlyName = $FriendlyName

$privateKey = [System.Security.Cryptography.X509Certificates.RSACertificateExtensions]::GetRSAPrivateKey($certificate)
if ($null -eq $privateKey) {
    throw 'El certificado SQL fue creado sin una clave privada RSA utilizable.'
}
$privateKey.Dispose()

$providerInfo = $certificate.PrivateKey.CspKeyContainerInfo
if (-not $providerInfo.MachineKeyStore) {
    throw 'La clave SQL generada no pertenece al almacén del equipo.'
}
$keyPath = Join-Path 'C:\ProgramData\Microsoft\Crypto\RSA\MachineKeys' $providerInfo.UniqueKeyContainerName
if (-not (Test-Path -LiteralPath $keyPath)) {
    throw "No se creó el contenedor de clave esperado: $keyPath"
}
if ((Get-Item -LiteralPath $keyPath).Length -lt 256) {
    throw "El contenedor de clave SQL no contiene una clave privada valida: $keyPath"
}

$acl = Get-Acl -LiteralPath $keyPath
$systemAccount = ([System.Security.Principal.SecurityIdentifier]'S-1-5-18').Translate(
    [System.Security.Principal.NTAccount]
).Value
$administratorsAccount = ([System.Security.Principal.SecurityIdentifier]'S-1-5-32-544').Translate(
    [System.Security.Principal.NTAccount]
).Value
$requiredAccess = @(
    @($systemAccount, [System.Security.AccessControl.FileSystemRights]::FullControl),
    @($administratorsAccount, [System.Security.AccessControl.FileSystemRights]::FullControl),
    @(
        $ServiceAccount,
        ([System.Security.AccessControl.FileSystemRights]::ReadAndExecute -bor
            [System.Security.AccessControl.FileSystemRights]::Write)
    )
)
foreach ($entry in $requiredAccess) {
    $rule = [System.Security.AccessControl.FileSystemAccessRule]::new(
        [string]$entry[0],
        [System.Security.AccessControl.FileSystemRights]$entry[1],
        [System.Security.AccessControl.AccessControlType]::Allow
    )
    $acl.SetAccessRule($rule)
}
Set-Acl -LiteralPath $keyPath -AclObject $acl

$publicCertificatePath = Join-Path $env:TEMP ("portal-apm-sql-$($certificate.Thumbprint).cer")
try {
    Export-Certificate -Cert $certificate -FilePath $publicCertificatePath -Force | Out-Null
    Import-Certificate -FilePath $publicCertificatePath -CertStoreLocation 'Cert:\LocalMachine\Root' | Out-Null
}
finally {
    if (Test-Path -LiteralPath $publicCertificatePath) {
        Remove-Item -LiteralPath $publicCertificatePath -Force
    }
}

$instanceId = (Get-ItemProperty -LiteralPath 'HKLM:\SOFTWARE\Microsoft\Microsoft SQL Server\Instance Names\SQL').$ServiceName
if ([string]::IsNullOrWhiteSpace($instanceId)) {
    throw "No se encontró la instancia SQL $ServiceName en el registro."
}
$superSocketPath = "HKLM:\SOFTWARE\Microsoft\Microsoft SQL Server\$instanceId\MSSQLServer\SuperSocketNetLib"
Set-ItemProperty -LiteralPath $superSocketPath -Name Certificate -Value $certificate.Thumbprint.ToLowerInvariant()
Set-ItemProperty -LiteralPath $superSocketPath -Name ForceEncryption -Value 1 -Type DWord

Restart-Service -Name $ServiceName -Force
if ($ServiceName -eq 'MSSQLSERVER') {
    Start-Service -Name 'SQLSERVERAGENT' -ErrorAction SilentlyContinue
}

Write-Output "CERTIFICATE=$($certificate.Thumbprint)"
Write-Output "KEY_CONTAINER=$($providerInfo.UniqueKeyContainerName)"
Write-Output "KEY_PATH=$keyPath"
Get-Service -Name $ServiceName | Format-Table Name,Status,StartType -AutoSize
