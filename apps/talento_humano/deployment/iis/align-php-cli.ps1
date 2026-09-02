[CmdletBinding(SupportsShouldProcess)]
param(
    [string]$PhpPath = 'C:\php85-nts',
    [switch]$Restore
)

$ErrorActionPreference = 'Stop'
$repoRoot = Split-Path (Split-Path $PSScriptRoot -Parent) -Parent
$backupDir = Join-Path $repoRoot '.portal-portuario-private\environment'
$backupFile = Join-Path $backupDir 'machine-path-before-php-alignment.txt'

if ($Restore) {
    if (-not (Test-Path -LiteralPath $backupFile)) {
        throw "No existe el respaldo de PATH: $backupFile"
    }
    $previous = Get-Content -LiteralPath $backupFile -Raw
    if ($PSCmdlet.ShouldProcess('PATH de máquina', 'Restaurar el valor respaldado')) {
        [Environment]::SetEnvironmentVariable('Path', $previous.TrimEnd("`r", "`n"), 'Machine')
    }
    Write-Output 'MACHINE_PATH=RESTORED'
    exit 0
}

$phpExe = Join-Path $PhpPath 'php.exe'
if (-not (Test-Path -LiteralPath $phpExe)) {
    throw "No se encontró PHP CLI en $phpExe"
}

$current = [Environment]::GetEnvironmentVariable('Path', 'Machine')
$segments = @($current -split ';' | Where-Object { $_ -and $_.Trim() })
$normalizedTarget = $PhpPath.TrimEnd('\')
$clean = @($segments | Where-Object {
    $candidate = $_.Trim().TrimEnd('\')
    $candidate -notin @('C:\php85', 'C:\php85-nts', 'C:\xampp\php')
})
$updated = (@($normalizedTarget) + $clean) -join ';'

New-Item -ItemType Directory -Force -Path $backupDir | Out-Null
if (-not (Test-Path -LiteralPath $backupFile)) {
    Set-Content -LiteralPath $backupFile -Value $current -Encoding UTF8
}

if ($PSCmdlet.ShouldProcess('PATH de máquina', "Priorizar $normalizedTarget")) {
    [Environment]::SetEnvironmentVariable('Path', $updated, 'Machine')
}

$version = & $phpExe -r 'echo PHP_VERSION . "|" . PHP_SAPI . "|" . (PHP_ZTS ? "ZTS" : "NTS");'
Write-Output 'MACHINE_PATH=ALIGNED'
Write-Output "PHP=$version"
Write-Output "BACKUP=$backupFile"
