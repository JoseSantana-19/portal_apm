param(
    [Parameter(Mandatory = $true)]
    [string]$CertificateThumbprint,
    [string]$ServiceName = 'MSSQLSERVER',
    [string]$ServiceAccount = 'NT SERVICE\MSSQLSERVER',
    [switch]$RestartService
)

$ErrorActionPreference = 'Stop'

$principal = New-Object Security.Principal.WindowsPrincipal([Security.Principal.WindowsIdentity]::GetCurrent())
if (-not $principal.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)) {
    throw 'Este script debe ejecutarse como administrador.'
}

$thumbprint = ($CertificateThumbprint -replace '\s', '').ToUpperInvariant()
$certificate = Get-Item -LiteralPath "Cert:\LocalMachine\My\$thumbprint" -ErrorAction Stop
if (-not $certificate.HasPrivateKey) {
    throw 'El certificado SQL no tiene clave privada.'
}

$providerInfo = $certificate.PrivateKey.CspKeyContainerInfo
if (-not $providerInfo.MachineKeyStore) {
    throw 'La clave privada no pertenece al almacen del equipo.'
}

$keyPath = Join-Path 'C:\ProgramData\Microsoft\Crypto\RSA\MachineKeys' $providerInfo.UniqueKeyContainerName
if (-not (Test-Path -LiteralPath $keyPath)) {
    throw "No existe el contenedor de clave: $keyPath"
}

$acl = Get-Acl -LiteralPath $keyPath

# Preserve the operating-system and administrative control entries required by
# CryptoAPI. SQL receives the tested CSP access without delete, ownership or
# permission-management rights.
$systemAccount = ([System.Security.Principal.SecurityIdentifier]'S-1-5-18').Translate(
    [System.Security.Principal.NTAccount]
).Value
$administratorsAccount = ([System.Security.Principal.SecurityIdentifier]'S-1-5-32-544').Translate(
    [System.Security.Principal.NTAccount]
).Value
$requiredRules = @(
    [System.Security.AccessControl.FileSystemAccessRule]::new(
        $systemAccount,
        [System.Security.AccessControl.FileSystemRights]::FullControl,
        [System.Security.AccessControl.AccessControlType]::Allow
    ),
    [System.Security.AccessControl.FileSystemAccessRule]::new(
        $administratorsAccount,
        [System.Security.AccessControl.FileSystemRights]::FullControl,
        [System.Security.AccessControl.AccessControlType]::Allow
    ),
    [System.Security.AccessControl.FileSystemAccessRule]::new(
        $ServiceAccount,
        ([System.Security.AccessControl.FileSystemRights]::ReadAndExecute -bor
            [System.Security.AccessControl.FileSystemRights]::Write),
        [System.Security.AccessControl.AccessControlType]::Allow
    )
)
foreach ($rule in $requiredRules) {
    $acl.SetAccessRule($rule)
}
Set-Acl -LiteralPath $keyPath -AclObject $acl

if ($RestartService) {
    Restart-Service -Name $ServiceName -Force
    if ($ServiceName -eq 'MSSQLSERVER') {
        Start-Service -Name 'SQLSERVERAGENT' -ErrorAction SilentlyContinue
    }
}

Write-Output "CERTIFICATE=$thumbprint"
Write-Output "SERVICE_ACCOUNT=$ServiceAccount"
Write-Output "KEY_PATH=$keyPath"
icacls.exe $keyPath
Get-Service -Name $ServiceName | Format-Table Name,Status,StartType -AutoSize
