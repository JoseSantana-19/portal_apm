param(
    [string]$SiteName = 'PortalPortuario',
    [string]$HostName = 'portal-apm-preprod.local',
    [string]$AppPoolName = 'PortalPortuarioPool',
    [string]$PhpInstallPath = 'C:\php85-nts',
    [Parameter(Mandatory = $true)]
    [string]$PhpSourcePath,
    [Parameter(Mandatory = $true)]
    [string]$UrlRewriteInstaller,
    [Parameter(Mandatory = $true)]
    [string]$CertificateThumbprint
)

$ErrorActionPreference = 'Stop'

$projectPath = Split-Path (Split-Path $PSScriptRoot -Parent) -Parent
$phpCgi = Join-Path $PhpInstallPath 'php-cgi.exe'
$phpIniTemplate = Join-Path $PSScriptRoot 'php.ini'
$privatePath = Join-Path (Split-Path $projectPath -Parent) '.portal-portuario-private'
$iisIdentity = "IIS AppPool\$AppPoolName"
$appCmd = Join-Path $env:windir 'System32\inetsrv\appcmd.exe'

$principal = New-Object Security.Principal.WindowsPrincipal([Security.Principal.WindowsIdentity]::GetCurrent())
if (-not $principal.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)) {
    throw 'Este script debe ejecutarse como administrador.'
}

$transcriptDirectory = Join-Path $env:TEMP 'PortalAPM-IIS'
New-Item -ItemType Directory -Path $transcriptDirectory -Force | Out-Null
Start-Transcript -LiteralPath (Join-Path $transcriptDirectory 'configure-local-iis.result.txt') -Force

foreach ($requiredPath in @($projectPath, $PhpSourcePath, $UrlRewriteInstaller, $phpIniTemplate)) {
    if (-not (Test-Path -LiteralPath $requiredPath)) {
        throw "No existe la ruta requerida: $requiredPath"
    }
}

$signature = Get-AuthenticodeSignature -LiteralPath $UrlRewriteInstaller
if ($signature.Status -ne 'Valid' -or $signature.SignerCertificate.Subject -notmatch 'Microsoft Corporation') {
    throw 'El instalador de URL Rewrite no tiene una firma valida de Microsoft.'
}

$certificate = Get-Item -LiteralPath "Cert:\LocalMachine\My\$CertificateThumbprint" -ErrorAction Stop
if (-not $certificate.HasPrivateKey) {
    throw 'El certificado web no tiene clave privada.'
}
$privateKey = [System.Security.Cryptography.X509Certificates.RSACertificateExtensions]::GetRSAPrivateKey($certificate)
if ($null -eq $privateKey) {
    throw 'La clave privada del certificado web no existe o no es accesible.'
}
$privateKey.Dispose()
if ($certificate.NotAfter -le (Get-Date)) {
    throw 'El certificado web esta vencido.'
}

New-Item -ItemType Directory -Path $PhpInstallPath -Force | Out-Null
Copy-Item -Path (Join-Path $PhpSourcePath '*') -Destination $PhpInstallPath -Recurse -Force
Copy-Item -LiteralPath $phpIniTemplate -Destination (Join-Path $PhpInstallPath 'php.ini') -Force

$rewriteProcess = Start-Process -FilePath 'msiexec.exe' -ArgumentList @('/i', ('"' + $UrlRewriteInstaller + '"'), '/qn', '/norestart') -Wait -PassThru -WindowStyle Hidden
if ($rewriteProcess.ExitCode -notin @(0, 1641, 3010)) {
    throw "URL Rewrite no pudo instalarse. Codigo: $($rewriteProcess.ExitCode)"
}

Import-Module WebAdministration

$existingFastCgi = & $appCmd list config /section:system.webServer/fastCgi
if ($existingFastCgi -match [regex]::Escape($phpCgi)) {
    & $appCmd set config /section:system.webServer/fastCgi "/-[fullPath='$phpCgi']" | Out-Null
}
& $appCmd set config /section:system.webServer/fastCgi "/+[fullPath='$phpCgi']" | Out-Null
& $appCmd set config /section:system.webServer/fastCgi "/[fullPath='$phpCgi'].instanceMaxRequests:10000" | Out-Null
& $appCmd set config /section:system.webServer/fastCgi "/[fullPath='$phpCgi'].activityTimeout:120" | Out-Null
& $appCmd set config /section:system.webServer/fastCgi "/[fullPath='$phpCgi'].requestTimeout:300" | Out-Null
& $appCmd set config /section:system.webServer/fastCgi "/[fullPath='$phpCgi'].monitorChangesTo:$PhpInstallPath\php.ini" | Out-Null
& $appCmd set config /section:system.webServer/fastCgi "/+[fullPath='$phpCgi'].environmentVariables.[name='PHPRC',value='$PhpInstallPath']" | Out-Null
& $appCmd set config /section:system.webServer/fastCgi "/+[fullPath='$phpCgi'].environmentVariables.[name='PHP_FCGI_MAX_REQUESTS',value='10000']" | Out-Null

if (-not (Test-Path "IIS:\AppPools\$AppPoolName")) {
    New-WebAppPool -Name $AppPoolName | Out-Null
}
Set-ItemProperty "IIS:\AppPools\$AppPoolName" -Name managedRuntimeVersion -Value ''
Set-ItemProperty "IIS:\AppPools\$AppPoolName" -Name managedPipelineMode -Value Integrated
Set-ItemProperty "IIS:\AppPools\$AppPoolName" -Name processModel.identityType -Value ApplicationPoolIdentity
Set-ItemProperty "IIS:\AppPools\$AppPoolName" -Name startMode -Value AlwaysRunning

if (Test-Path "IIS:\Sites\$SiteName") {
    Remove-Website -Name $SiteName
}
New-Website -Name $SiteName -PhysicalPath $projectPath -ApplicationPool $AppPoolName -Port 80 -HostHeader $HostName | Out-Null
& $appCmd set config $SiteName /section:system.webServer/handlers "/+[name='PHP_via_FastCGI',path='*.php',verb='GET,HEAD,POST',modules='FastCgiModule',scriptProcessor='$phpCgi',resourceType='Either',requireAccess='Script']" /commit:apphost | Out-Null
& $appCmd set config $SiteName /section:system.webServer/security/authentication/anonymousAuthentication /enabled:true /commit:apphost | Out-Null
& $appCmd set config $SiteName /section:system.webServer/security/authentication/anonymousAuthentication '/userName:' /commit:apphost | Out-Null
New-WebBinding -Name $SiteName -Protocol https -Port 443 -HostHeader $HostName -SslFlags 1

$sslBindingPath = "IIS:\SslBindings\0.0.0.0!443!$HostName"
& netsh.exe http delete sslcert "hostnameport=${HostName}:443" | Out-Null
$certificate | New-Item $sslBindingPath -SSLFlags 1 | Out-Null

$hostsPath = Join-Path $env:windir 'System32\drivers\etc\hosts'
$hosts = Get-Content -LiteralPath $hostsPath -ErrorAction Stop
if (-not ($hosts | Where-Object { $_ -match "^\s*127\.0\.0\.1\s+$([regex]::Escape($HostName))(\s|$)" })) {
    Add-Content -LiteralPath $hostsPath -Value "`r`n127.0.0.1`t$HostName"
}

$runtimeDirectories = @(
    (Join-Path $projectPath 'storage\logs'),
    (Join-Path $projectPath 'public\img\empleados'),
    $privatePath,
    'C:\inetpub\logs\PortalPortuario',
    'C:\inetpub\temp\PortalPortuario'
)
foreach ($directory in $runtimeDirectories) {
    New-Item -ItemType Directory -Path $directory -Force | Out-Null
}

& icacls.exe $projectPath /grant "${iisIdentity}:(OI)(CI)RX" /T /C | Out-Null
foreach ($directory in $runtimeDirectories) {
    & icacls.exe $directory /grant "${iisIdentity}:(OI)(CI)M" /T /C | Out-Null
}

Set-Service -Name W3SVC -StartupType Automatic
Start-Service -Name W3SVC
Start-WebAppPool -Name $AppPoolName -ErrorAction SilentlyContinue
Start-Website -Name $SiteName

$phpVersion = & (Join-Path $PhpInstallPath 'php.exe') -c (Join-Path $PhpInstallPath 'php.ini') -r 'echo PHP_VERSION;'
$phpModules = & (Join-Path $PhpInstallPath 'php.exe') -c (Join-Path $PhpInstallPath 'php.ini') -m

Write-Output "SITE=$SiteName"
Write-Output "HOST=https://$HostName/"
Write-Output "APP_POOL=$AppPoolName"
Write-Output "PHP=$phpVersion"
Write-Output "PDO_SQLSRV=$([bool]($phpModules -contains 'pdo_sqlsrv'))"
Write-Output "SQLSRV=$([bool]($phpModules -contains 'sqlsrv'))"
Write-Output "CERTIFICATE=$($certificate.Thumbprint)"
Write-Output "CERTIFICATE_EXPIRES=$($certificate.NotAfter.ToString('s'))"
Write-Output "URL_REWRITE_EXIT=$($rewriteProcess.ExitCode)"
Stop-Transcript
