param(
    [string]$HostName = 'portal-apm-preprod.local',
    [string]$PhpPath = 'C:\php85-nts\php.exe',
    [int]$CertificateWarningDays = 45
)

$ErrorActionPreference = 'Stop'
$project = Split-Path $PSScriptRoot -Parent
$failures = [System.Collections.Generic.List[string]]::new()
$warnings = [System.Collections.Generic.List[string]]::new()
$details = [ordered]@{ checked_at=(Get-Date).ToString('o'); host=$HostName }

foreach ($serviceName in @('MSSQLSERVER','SQLSERVERAGENT','W3SVC')) {
    try {
        $service = Get-Service -Name $serviceName -ErrorAction Stop
        $details["service_$serviceName"] = $service.Status.ToString()
        if ($service.Status -ne 'Running') { $failures.Add("El servicio $serviceName no está iniciado.") }
    } catch { $failures.Add("No se pudo consultar ${serviceName}: $($_.Exception.Message)") }
}

try {
    $response = Invoke-WebRequest -Uri "https://$HostName/login" -UseBasicParsing -TimeoutSec 15
    $details.https_status = [int]$response.StatusCode
    if ([int]$response.StatusCode -ne 200 -or $response.Content -notmatch 'Portal Portuario APM') { $failures.Add('El login HTTPS no entregó la respuesta institucional esperada.') }
} catch { $failures.Add("HTTPS no está disponible: $($_.Exception.Message)") }

try {
    $certificate = Get-ChildItem Cert:\LocalMachine\My | Where-Object {
        $_.FriendlyName -eq 'Portal APM Web Cert' -and $_.HasPrivateKey -and
        ($_.DnsNameList.Unicode -contains $HostName)
    } | Sort-Object NotBefore -Descending | Select-Object -First 1
    if($null -eq $certificate){throw 'No se encontró el certificado Web activo por nombre y DNS.'}
    $days = [math]::Floor(($certificate.NotAfter-(Get-Date)).TotalDays)
    $details.web_certificate_thumbprint = $certificate.Thumbprint
    $details.web_certificate_days = $days
    if ($days -lt 1) { $failures.Add('El certificado Web está vencido.') }
    elseif ($days -le $CertificateWarningDays) { $warnings.Add("El certificado Web vence en $days días.") }
} catch { $failures.Add("No se pudo validar el certificado Web: $($_.Exception.Message)") }

if (-not (Test-Path -LiteralPath $PhpPath)) { $failures.Add("No existe PHP en $PhpPath") }
else {
    Push-Location $project
    try {
        $preflight = & $PhpPath 'scripts\preflight.php' 2>&1 | Out-String
        $details.preflight_exit = $LASTEXITCODE
        if ($LASTEXITCODE -ne 0 -or $preflight -notmatch 'Fallos:\s*0') { $failures.Add('El preflight de aplicación/SQL no fue satisfactorio.') }
    } finally { Pop-Location }
}

foreach ($drive in Get-PSDrive -PSProvider FileSystem | Where-Object { $_.Root -match '^[A-Z]:\\$' }) {
    if ($drive.Used -ne $null -and $drive.Free -ne $null) {
        $percentFree = [math]::Round(($drive.Free/($drive.Used+$drive.Free))*100,1)
        $details["disk_$($drive.Name)_free_percent"] = $percentFree
        if ($percentFree -lt 10) { $warnings.Add("La unidad $($drive.Name): tiene solo $percentFree% libre.") }
    }
}

$result = [ordered]@{ status=if($failures.Count){'FAIL'}elseif($warnings.Count){'WARN'}else{'OK'}; failures=@($failures); warnings=@($warnings); details=$details }
$directory = Join-Path $env:ProgramData 'PortalAPM'
New-Item -ItemType Directory -Force -Path $directory | Out-Null
$result | ConvertTo-Json -Depth 5 | Set-Content -LiteralPath (Join-Path $directory 'health-latest.json') -Encoding UTF8
$result | ConvertTo-Json -Depth 5
if ($failures.Count) { exit 1 }
exit 0
