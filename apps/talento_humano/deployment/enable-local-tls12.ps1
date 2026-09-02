param(
    [switch]$ConfirmChange,
    [switch]$RestartComputer
)

$ErrorActionPreference = 'Stop'
$principal = [Security.Principal.WindowsPrincipal]::new([Security.Principal.WindowsIdentity]::GetCurrent())
if (-not $principal.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)) {
    throw 'Este script debe ejecutarse como administrador.'
}
if (-not $ConfirmChange) {
    throw 'La configuracion modifica Schannel y .NET. Repita con -ConfirmChange despues de autorizar el cambio.'
}

$logDirectory = Join-Path $env:ProgramData 'PortalAPM'
New-Item -ItemType Directory -Path $logDirectory -Force | Out-Null
$backupPath = Join-Path $logDirectory ("tls-registry-before-{0}.json" -f (Get-Date -Format 'yyyyMMdd-HHmmss'))

$protocolRoot = 'HKLM:\SYSTEM\CurrentControlSet\Control\SecurityProviders\SCHANNEL\Protocols\TLS 1.2'
$frameworkPaths = @(
    'HKLM:\SOFTWARE\Microsoft\.NETFramework\v4.0.30319',
    'HKLM:\SOFTWARE\WOW6432Node\Microsoft\.NETFramework\v4.0.30319'
)
$targetPaths = @(
    (Join-Path $protocolRoot 'Client'),
    (Join-Path $protocolRoot 'Server')
) + $frameworkPaths

$before = foreach ($path in $targetPaths) {
    $values = if (Test-Path -LiteralPath $path) { Get-ItemProperty -LiteralPath $path } else { $null }
    [pscustomobject]@{
        Path = $path
        Exists = $null -ne $values
        Enabled = if ($null -ne $values -and $values.PSObject.Properties.Name -contains 'Enabled') { $values.Enabled } else { $null }
        DisabledByDefault = if ($null -ne $values -and $values.PSObject.Properties.Name -contains 'DisabledByDefault') { $values.DisabledByDefault } else { $null }
        SchUseStrongCrypto = if ($null -ne $values -and $values.PSObject.Properties.Name -contains 'SchUseStrongCrypto') { $values.SchUseStrongCrypto } else { $null }
        SystemDefaultTlsVersions = if ($null -ne $values -and $values.PSObject.Properties.Name -contains 'SystemDefaultTlsVersions') { $values.SystemDefaultTlsVersions } else { $null }
    }
}
$before | ConvertTo-Json -Depth 4 | Set-Content -LiteralPath $backupPath -Encoding UTF8

foreach ($side in @('Client','Server')) {
    $path = Join-Path $protocolRoot $side
    New-Item -Path $path -Force | Out-Null
    New-ItemProperty -Path $path -Name Enabled -PropertyType DWord -Value 1 -Force | Out-Null
    New-ItemProperty -Path $path -Name DisabledByDefault -PropertyType DWord -Value 0 -Force | Out-Null
}
foreach ($path in $frameworkPaths) {
    New-Item -Path $path -Force | Out-Null
    New-ItemProperty -Path $path -Name SchUseStrongCrypto -PropertyType DWord -Value 1 -Force | Out-Null
    New-ItemProperty -Path $path -Name SystemDefaultTlsVersions -PropertyType DWord -Value 1 -Force | Out-Null
}

Write-Output "BACKUP=$backupPath"
Write-Output 'TLS12_CLIENT=ENABLED'
Write-Output 'TLS12_SERVER=ENABLED'
Write-Output 'DOTNET_STRONG_CRYPTO=ENABLED'
Write-Output 'RESTART_REQUIRED=YES'

if ($RestartComputer) {
    Restart-Computer -Force
}
