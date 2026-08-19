[CmdletBinding()]
param(
    [string]$DbHost,
    [string]$DbUser,
    [string]$DbPassword,
    [string]$AppUrl,
    [switch]$InstallPhpDrivers,
    [switch]$ForceRestore,
    [switch]$SkipDatabase
)

$ErrorActionPreference = 'Stop'
$ProjectRoot = Split-Path -Parent $MyInvocation.MyCommand.Path
Set-Location -LiteralPath $ProjectRoot

function Stop-Install([string]$Message) {
    Write-Host "ERROR: $Message" -ForegroundColor Red
    exit 1
}

function Set-EnvValue([string]$Path, [string]$Name, [string]$Value) {
    $lines = @(Get-Content -LiteralPath $Path -Encoding UTF8)
    $found = $false
    for ($i = 0; $i -lt $lines.Count; $i++) {
        if ($lines[$i] -match ('^' + [regex]::Escape($Name) + '=')) {
            $lines[$i] = "$Name=$Value"
            $found = $true
            break
        }
    }
    if (-not $found) { $lines += "$Name=$Value" }
    Set-Content -LiteralPath $Path -Value $lines -Encoding UTF8
}

Write-Host '=== Instalador de Control de Bienes ===' -ForegroundColor Cyan

$phpCommand = Get-Command php -ErrorAction SilentlyContinue
if (Test-Path -LiteralPath 'C:\xampp\php\php.exe') {
    $phpExe = 'C:\xampp\php\php.exe'
} elseif ($phpCommand) {
    $phpExe = $phpCommand.Source
} else {
    Stop-Install 'No se encontro PHP. Instale XAMPP con PHP 7.4 o superior.'
}

$version = & $phpExe -r "echo PHP_VERSION;"
if ([version]$version -lt [version]'7.4.0') {
    Stop-Install "PHP $version no es compatible; se requiere PHP 7.4 o superior."
}
Write-Host "PHP ${version}: OK"

$requiredExtensions = @('pdo_sqlsrv','sqlsrv','mbstring','curl','openssl','fileinfo','json')
$loadedModules = @(& $phpExe -m | ForEach-Object { $_.Trim().ToLowerInvariant() })
$missing = @($requiredExtensions | Where-Object { $loadedModules -notcontains $_.ToLowerInvariant() })
if ($missing.Count -gt 0 -and $InstallPhpDrivers -and ($missing -contains 'pdo_sqlsrv' -or $missing -contains 'sqlsrv')) {
    $phpMinor = (& $phpExe -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;").Trim()
    $threadSafe = ((& $phpExe -r "echo PHP_ZTS ? 'ts' : 'nts';").Trim())
    $architecture = ((& $phpExe -r "echo PHP_INT_SIZE === 8 ? 'x64' : 'x86';").Trim())
    $release = if ($phpMinor -eq '8.2') { '5.11.1' } elseif ($phpMinor -eq '8.1') { '5.10.1' } else { $null }
    if (-not $release) { Stop-Install "La instalacion automatica esta preparada para PHP 8.1/8.2. Descargue manualmente el controlador oficial compatible con PHP $phpMinor." }

    $tempFolder = Join-Path ([IO.Path]::GetTempPath()) ('control-bienes-sqlsrv-' + [guid]::NewGuid().ToString('N'))
    New-Item -ItemType Directory -Path $tempFolder | Out-Null
    try {
        $zipPath = Join-Path $tempFolder 'drivers.zip'
        $downloadUrl = "https://github.com/microsoft/msphpsql/releases/download/v$release/Windows-$phpMinor.zip"
        Write-Host "Descargando controladores oficiales SQL Server $release para PHP $phpMinor..."
        Invoke-WebRequest -Uri $downloadUrl -OutFile $zipPath -UseBasicParsing
        Expand-Archive -LiteralPath $zipPath -DestinationPath $tempFolder -Force
        $suffix = $phpMinor.Replace('.', '') + '_' + $threadSafe + '_' + $architecture + '.dll'
        $sqlsrvDll = Get-ChildItem -LiteralPath $tempFolder -Recurse -Filter ("php_sqlsrv_" + $suffix) | Select-Object -First 1
        $pdoDll = Get-ChildItem -LiteralPath $tempFolder -Recurse -Filter ("php_pdo_sqlsrv_" + $suffix) | Select-Object -First 1
        if (-not $sqlsrvDll -or -not $pdoDll) { Stop-Install 'El paquete descargado no contiene los controladores compatibles.' }

        $extensionDir = (& $phpExe -r "echo ini_get('extension_dir');").Trim()
        $phpIni = (& $phpExe --ini | Select-String 'Loaded Configuration File' | ForEach-Object { ($_ -split ':',2)[1].Trim() })
        if (-not (Test-Path -LiteralPath $phpIni)) { Stop-Install 'No se pudo localizar php.ini.' }
        Copy-Item -LiteralPath $sqlsrvDll.FullName -Destination (Join-Path $extensionDir $sqlsrvDll.Name) -Force
        Copy-Item -LiteralPath $pdoDll.FullName -Destination (Join-Path $extensionDir $pdoDll.Name) -Force
        $iniLines = @(Get-Content -LiteralPath $phpIni -Encoding UTF8 | Where-Object { $_ -notmatch '^\s*extension\s*=\s*php_(pdo_)?sqlsrv.*\.dll\s*$' })
        $iniLines += "extension=$($sqlsrvDll.Name)"
        $iniLines += "extension=$($pdoDll.Name)"
        [IO.File]::WriteAllLines($phpIni, $iniLines, (New-Object Text.UTF8Encoding($false)))
        Write-Host 'Controladores instalados. Reinicie Apache al terminar.' -ForegroundColor Yellow
    } finally {
        $resolvedTempRoot = [IO.Path]::GetFullPath([IO.Path]::GetTempPath())
        $resolvedWork = [IO.Path]::GetFullPath($tempFolder)
        if ($resolvedWork.StartsWith($resolvedTempRoot, [StringComparison]::OrdinalIgnoreCase) -and (Test-Path -LiteralPath $resolvedWork)) {
            Remove-Item -LiteralPath $resolvedWork -Recurse -Force
        }
    }
    $loadedModules = @(& $phpExe -m | ForEach-Object { $_.Trim().ToLowerInvariant() })
    $missing = @($requiredExtensions | Where-Object { $loadedModules -notcontains $_.ToLowerInvariant() })
}
if ($missing.Count -gt 0) {
    Stop-Install ("Faltan extensiones de PHP: " + ($missing -join ', ') + '. Use -InstallPhpDrivers para instalar SQL Server o habilite las extensiones en php.ini.')
}
Write-Host 'Extensiones PHP requeridas: OK'

$envPath = Join-Path $ProjectRoot '.env'
if (-not (Test-Path -LiteralPath $envPath)) {
    Copy-Item -LiteralPath (Join-Path $ProjectRoot '.env.example') -Destination $envPath
    Write-Host 'Se creo .env desde la plantilla.'
}
if ($DbHost) { Set-EnvValue $envPath 'DB_HOST' $DbHost }
if ($DbUser) { Set-EnvValue $envPath 'DB_USER' $DbUser }
if ($PSBoundParameters.ContainsKey('DbPassword')) { Set-EnvValue $envPath 'DB_PASS' $DbPassword }
if ($AppUrl) { Set-EnvValue $envPath 'APP_URL' $AppUrl }

$envText = Get-Content -LiteralPath $envPath -Raw -Encoding UTF8
if ($envText -match 'DB_HOST=SERVIDOR\\INSTANCIA' -or $envText -match 'DB_USER=usuario_sql') {
    Stop-Install 'Complete DB_HOST, DB_USER y DB_PASS en .env o proporcionelos como parametros al instalador.'
}

$directories = @(
    'backup', 'logs', 'storage', 'storage/facturas',
    'public/uploads', 'public/uploads/perfiles'
)
foreach ($directory in $directories) {
    $path = Join-Path $ProjectRoot $directory
    if (-not (Test-Path -LiteralPath $path)) { New-Item -ItemType Directory -Path $path | Out-Null }
}
Write-Host 'Carpetas de trabajo: OK'

if (-not $SkipDatabase) {
    $arguments = @((Join-Path $ProjectRoot 'setup_database.php'))
    if ($ForceRestore) { $arguments += '--force' }
    & $phpExe @arguments
    if ($LASTEXITCODE -ne 0) { Stop-Install 'La preparacion de la base de datos no pudo completarse.' }
}

& $phpExe -l (Join-Path $ProjectRoot 'index.php') | Out-Host
if ($LASTEXITCODE -ne 0) { Stop-Install 'La aplicacion no supero la validacion de PHP.' }

Write-Host ''
Write-Host 'Instalacion completada.' -ForegroundColor Green
Write-Host 'Abra el proyecto desde la URL configurada en APP_URL.'
Write-Host 'Use -ForceRestore solo cuando desee reemplazar las bases existentes desde los respaldos.'
