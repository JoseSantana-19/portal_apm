param(
    [string]$ProjectPath = '',
    [string]$PhpPath = 'C:\php85-nts\php.exe'
)

$ErrorActionPreference = 'Continue'
$ProjectPath = if ([string]::IsNullOrWhiteSpace($ProjectPath)) { Split-Path $PSScriptRoot -Parent } else { $ProjectPath }
$principal = [Security.Principal.WindowsPrincipal]::new([Security.Principal.WindowsIdentity]::GetCurrent())
if (-not $principal.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)) {
    throw 'Esta compuerta debe ejecutarse como administrador.'
}

$logDirectory = Join-Path $env:ProgramData 'PortalAPM'
New-Item -ItemType Directory -Path $logDirectory -Force | Out-Null
$logPath = Join-Path $logDirectory 'local-validation-gate.log'
$resultPath = Join-Path $logDirectory 'local-validation-gate.exit'
Set-Content -LiteralPath $resultPath -Value '1' -Encoding ASCII
Start-Transcript -LiteralPath $logPath -Force
try {
    Set-Location -LiteralPath $ProjectPath
    $failed = $false

    & powershell.exe -NoProfile -ExecutionPolicy Bypass -File 'deployment\test-local-certificates.ps1'
    if ($LASTEXITCODE -ne 0) { $failed = $true }

    foreach ($test in @(
        'scripts\preflight.php',
        'tests\migration_ledger.php',
        'tests\run_sql_smoke.php',
        'tests\workforce_db_smoke.php',
        'tests\talent_operation_db_smoke.php',
        'tests\role_position_db_smoke.php',
        'tests\regimen_laboral_db_smoke.php',
        'tests\rbac_asistente_talento_db_smoke.php',
        'tests\temporal_vigency_db_smoke.php',
        'tests\socio_geolocation_db_smoke.php',
        'tests\signed_documents_db_smoke.php',
        'tests\security_db_smoke.php',
        'tests\db_privilege_test.php',
        'tests\uat_access_control.php'
    )) {
        & $PhpPath $test
        if ($LASTEXITCODE -ne 0) { $failed = $true }
    }

    & powershell.exe -NoProfile -ExecutionPolicy Bypass -File 'deployment\iis\test-local-iis.ps1'
    if ($LASTEXITCODE -ne 0) { $failed = $true }

    if ($failed) {
        Write-Output 'VALIDATION_GATE=FAIL'
        Set-Content -LiteralPath $resultPath -Value '1' -Encoding ASCII
        exit 1
    }
    Write-Output 'VALIDATION_GATE=OK'
    Set-Content -LiteralPath $resultPath -Value '0' -Encoding ASCII
    exit 0
}
finally {
    Stop-Transcript
}
