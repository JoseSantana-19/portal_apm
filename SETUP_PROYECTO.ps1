<#
.SYNOPSIS
    Portal APM v2.0 - Asistente de Instalacion Interactivo
    Funciona en cualquier computador con Windows:
      - Detecta XAMPP, WampServer, Laragon o PHP standalone
      - Configura config/app.php y .env automaticamente (URL, puerto, BD)
      - Detecta instancias de SQL Server y ejecuta el script de base de datos
      - Genera .htaccess / web.config
      - Inicia el servidor y abre el navegador
.NOTES
    Ejecutar desde la raiz del proyecto (o doble clic en SETUP_PROYECTO.bat):
    powershell -ExecutionPolicy Bypass -NoProfile -File SETUP_PROYECTO.ps1
#>

$Host.UI.RawUI.WindowTitle = "Portal APM - Asistente de Instalacion"
$ErrorActionPreference = "Stop"

# ════════════════════════════════════════════════════════════
#  Helpers de consola
# ════════════════════════════════════════════════════════════
function Write-Ok  ($msg) { Write-Host "  [OK]  $msg" -ForegroundColor Green  }
function Write-Err ($msg) { Write-Host "  [ERR] $msg" -ForegroundColor Red    }
function Write-Warn($msg) { Write-Host "  [!]   $msg" -ForegroundColor Yellow }
function Write-Inf ($msg) { Write-Host "  [...] $msg" -ForegroundColor Cyan   }
function Write-Head($msg) { Write-Host "`n=== $msg ===" -ForegroundColor White }

# Menu numerado: devuelve el indice (1-based) elegido
function Show-Menu {
    param([string]$Title, [string[]]$Options)
    Write-Host ""
    Write-Host "  $Title" -ForegroundColor White
    for ($i = 0; $i -lt $Options.Count; $i++) {
        Write-Host ("    [{0}] {1}" -f ($i + 1), $Options[$i]) -ForegroundColor Gray
    }
    while ($true) {
        $sel = Read-Host "  Opcion (1-$($Options.Count))"
        $n = 0
        if ([int]::TryParse($sel, [ref]$n) -and $n -ge 1 -and $n -le $Options.Count) { return $n }
        Write-Host "  Opcion invalida, intente de nuevo." -ForegroundColor Yellow
    }
}

# Pregunta si/no. Default = $false (No) salvo que se indique
function Ask-YesNo {
    param([string]$Question, [bool]$DefaultYes = $false)
    $suffix = "(s/N)"
    if ($DefaultYes) { $suffix = "(S/n)" }
    $r = Read-Host "  $Question $suffix"
    if ([string]::IsNullOrWhiteSpace($r)) { return $DefaultYes }
    return ($r -ieq 's' -or $r -ieq 'si' -or $r -ieq 'y' -or $r -ieq 'yes')
}

# Escribe archivo UTF-8 SIN BOM (un BOM antes de <?php rompe los headers PHP)
function Write-FileUtf8NoBom {
    param([string]$Path, [string]$Content)
    $enc = New-Object System.Text.UTF8Encoding $false
    [System.IO.File]::WriteAllText($Path, $Content, $enc)
}

# Puerto TCP en escucha?
function Test-PortListening {
    param([int]$Port)
    try {
        $c = Get-NetTCPConnection -LocalPort $Port -State Listen -ErrorAction SilentlyContinue
        return [bool]$c
    } catch {
        # Fallback con .NET si Get-NetTCPConnection no existe
        try {
            $listener = New-Object System.Net.Sockets.TcpListener([System.Net.IPAddress]::Loopback, $Port)
            $listener.Start(); $listener.Stop()
            return $false   # se pudo abrir => estaba libre
        } catch { return $true }
    }
}

function Find-FreePort {
    param([int]$Start = 8080)
    $p = $Start
    while (Test-PortListening -Port $p) { $p++ }
    return $p
}

# Pide un puerto valido (1-65535). Si esta ocupado, avisa y ofrece el siguiente libre.
function Ask-Port {
    param([int]$Default, [string]$Para = "el servidor")
    while ($true) {
        $pIn = Read-Host "  Puerto para $Para (ENTER = $Default)"
        $p = $Default
        if (-not [string]::IsNullOrWhiteSpace($pIn)) {
            $n = 0
            if (-not ([int]::TryParse($pIn, [ref]$n)) -or $n -lt 1 -or $n -gt 65535) {
                Write-Warn "Puerto invalido. Debe ser un numero entre 1 y 65535."
                continue
            }
            $p = $n
        }
        if (Test-PortListening -Port $p) {
            $libre = Find-FreePort -Start ($p + 1)
            Write-Warn "El puerto $p YA esta en uso por otro programa."
            if (Ask-YesNo "Usar el siguiente puerto libre ($libre)?" $true) { return $libre }
            continue   # volver a preguntar
        }
        return $p
    }
}

# Panel "asi va quedando" - estado acumulado de la configuracion
$script:Estado = [ordered]@{}
function Show-Estado {
    Write-Host ""
    Write-Host "  +--------------------- ASI VA QUEDANDO ---------------------" -ForegroundColor DarkCyan
    foreach ($k in $script:Estado.Keys) {
        Write-Host ("  |  {0,-14} {1}" -f ($k + ":"), $script:Estado[$k]) -ForegroundColor Cyan
    }
    Write-Host "  +------------------------------------------------------------" -ForegroundColor DarkCyan
}

# ════════════════════════════════════════════════════════════
#  Banner
# ════════════════════════════════════════════════════════════
Write-Host ""
Write-Host "  ======================================================" -ForegroundColor Cyan
Write-Host "   Portal APM v2.0 - Autoridad Portuaria de Manta"        -ForegroundColor Cyan
Write-Host "   Asistente de Instalacion Interactivo"                  -ForegroundColor White
Write-Host "  ======================================================" -ForegroundColor Cyan

$ProjectRoot = $PSScriptRoot
$ProjectName = Split-Path $ProjectRoot -Leaf
Write-Inf "Proyecto: $ProjectRoot"

# Lista de carpetas donde se escribiran los archivos de configuracion.
# Si el usuario copia el proyecto a htdocs/www, se agrega la copia.
$ConfigTargets = @($ProjectRoot)

# ════════════════════════════════════════════════════════════
#  PASO 1 - Detectar entornos disponibles (XAMPP / Wamp / Laragon / PHP)
# ════════════════════════════════════════════════════════════
Write-Head "PASO 1 - Deteccion de entorno"
Write-Inf "Buscando XAMPP, WampServer, Laragon y PHP en sus discos..."

$drives = @()
try {
    $drives = (Get-PSDrive -PSProvider FileSystem -ErrorAction SilentlyContinue |
               Where-Object { $null -ne $_.Used }).Root
} catch { $drives = @("C:\") }
if (-not $drives -or $drives.Count -eq 0) { $drives = @("C:\") }

# Cada candidato: @{ Label; Type; Php; Htdocs; ApacheConf; Control }
$candidates = New-Object System.Collections.ArrayList

foreach ($drive in $drives) {

    # ── XAMPP ──
    $xampp = Join-Path $drive "xampp"
    if (Test-Path (Join-Path $xampp "php\php.exe")) {
        [void]$candidates.Add(@{
            Label      = "XAMPP  ($xampp)"
            Type       = "XAMPP"
            Php        = Join-Path $xampp "php\php.exe"
            Htdocs     = Join-Path $xampp "htdocs"
            ApacheConf = Join-Path $xampp "apache\conf\httpd.conf"
            Control    = Join-Path $xampp "xampp-control.exe"
        })
    }

    # ── WampServer (64 y 32 bits) ──
    foreach ($wampName in @("wamp64", "wamp")) {
        $wamp = Join-Path $drive $wampName
        $wampPhpDir = Join-Path $wamp "bin\php"
        if (Test-Path $wampPhpDir) {
            $latestPhp = Get-ChildItem -Path $wampPhpDir -Directory -ErrorAction SilentlyContinue |
                         Where-Object { Test-Path (Join-Path $_.FullName "php.exe") } |
                         Sort-Object Name -Descending | Select-Object -First 1
            if ($latestPhp) {
                $apacheDir = Get-ChildItem -Path (Join-Path $wamp "bin\apache") -Directory -ErrorAction SilentlyContinue |
                             Sort-Object Name -Descending | Select-Object -First 1
                $conf = $null
                if ($apacheDir) { $conf = Join-Path $apacheDir.FullName "conf\httpd.conf" }
                [void]$candidates.Add(@{
                    Label      = "WampServer  ($wamp, PHP $($latestPhp.Name))"
                    Type       = "WAMP"
                    Php        = Join-Path $latestPhp.FullName "php.exe"
                    Htdocs     = Join-Path $wamp "www"
                    ApacheConf = $conf
                    Control    = Join-Path $wamp "wampmanager.exe"
                })
            }
        }
    }

    # ── Laragon ──
    $laragon = Join-Path $drive "laragon"
    $laragonPhpDir = Join-Path $laragon "bin\php"
    if (Test-Path $laragonPhpDir) {
        $latestPhp = Get-ChildItem -Path $laragonPhpDir -Directory -ErrorAction SilentlyContinue |
                     Where-Object { Test-Path (Join-Path $_.FullName "php.exe") } |
                     Sort-Object Name -Descending | Select-Object -First 1
        if ($latestPhp) {
            [void]$candidates.Add(@{
                Label      = "Laragon  ($laragon, PHP $($latestPhp.Name))"
                Type       = "LARAGON"
                Php        = Join-Path $latestPhp.FullName "php.exe"
                Htdocs     = Join-Path $laragon "www"
                ApacheConf = $null
                Control    = Join-Path $laragon "laragon.exe"
            })
        }
    }

    # ── PHP standalone (C:\php, D:\php) ──
    $phpStandalone = Join-Path $drive "php"
    if (Test-Path (Join-Path $phpStandalone "php.exe")) {
        [void]$candidates.Add(@{
            Label = "PHP standalone  ($phpStandalone)"
            Type  = "PHP"
            Php   = Join-Path $phpStandalone "php.exe"
            Htdocs = $null; ApacheConf = $null; Control = $null
        })
    }
}

# ── PHP en PATH ──
$phpInPath = Get-Command php -ErrorAction SilentlyContinue
if ($phpInPath) {
    $already = $false
    foreach ($c in $candidates) { if ($c.Php -ieq $phpInPath.Source) { $already = $true; break } }
    if (-not $already) {
        [void]$candidates.Add(@{
            Label = "PHP en PATH del sistema  ($($phpInPath.Source))"
            Type  = "PHP"
            Php   = $phpInPath.Source
            Htdocs = $null; ApacheConf = $null; Control = $null
        })
    }
}

# ── Menu de seleccion ──
$menuOptions = @()
foreach ($c in $candidates) { $menuOptions += $c.Label }
$menuOptions += "Especificar la ruta de php.exe manualmente"

if ($candidates.Count -eq 0) {
    Write-Warn "No se detecto ningun entorno PHP instalado."
} else {
    Write-Ok "$($candidates.Count) entorno(s) detectado(s)"
}

$sel = Show-Menu -Title "Con que entorno desea ejecutar el portal?" -Options $menuOptions

if ($sel -le $candidates.Count) {
    $EnvChoice = $candidates[$sel - 1]
} else {
    while ($true) {
        $manualPhp = Read-Host "  Ruta completa a php.exe (ej: C:\php\php.exe)"
        if (Test-Path $manualPhp) { break }
        Write-Warn "No existe: $manualPhp"
    }
    $EnvChoice = @{ Label = "PHP manual ($manualPhp)"; Type = "PHP"; Php = $manualPhp
                    Htdocs = $null; ApacheConf = $null; Control = $null }
}

$PhpExe = $EnvChoice.Php
Write-Ok "Entorno elegido: $($EnvChoice.Label)"
$script:Estado["Entorno"] = $EnvChoice.Label
$script:Estado["PHP"]     = $PhpExe
Show-Estado

# ════════════════════════════════════════════════════════════
#  PASO 2 - Verificar PHP y extensiones
# ════════════════════════════════════════════════════════════
Write-Head "PASO 2 - Verificacion de PHP"

$phpVer = & $PhpExe -r "echo PHP_VERSION;" 2>$null
if ($phpVer -match "^8\.") {
    Write-Ok "PHP $phpVer"
} elseif ($phpVer) {
    Write-Warn "PHP $phpVer - el portal requiere PHP 8.0+. Puede fallar."
} else {
    Write-Err "No se pudo ejecutar PHP en: $PhpExe"
    Read-Host "Presione ENTER para salir"; exit 1
}

# Extension sqlsrv (obligatoria para SQL Server)
$sqlsrvExt = & $PhpExe -r "echo extension_loaded('sqlsrv') ? 'YES' : 'NO';" 2>$null
if ($sqlsrvExt -eq "YES") {
    Write-Ok "Extension sqlsrv cargada"
} else {
    Write-Warn "Extension sqlsrv NO cargada - el portal NO funcionara sin ella."
    $phpIni = & $PhpExe -r "echo php_ini_loaded_file();" 2>$null
    Write-Host ""
    Write-Host "  Como habilitarla:" -ForegroundColor White
    Write-Host "   1. Descargue los drivers de Microsoft:" -ForegroundColor Gray
    Write-Host "      https://learn.microsoft.com/sql/connect/php/download-drivers-php-sql-server" -ForegroundColor Gray
    Write-Host "   2. Copie php_sqlsrv_8x_ts_x64.dll a la carpeta ext\ de su PHP" -ForegroundColor Gray
    if ($phpIni) {
        Write-Host "   3. Agregue 'extension=php_sqlsrv_8x_ts_x64' en: $phpIni" -ForegroundColor Gray
    } else {
        Write-Host "   3. Agregue 'extension=php_sqlsrv...' en su php.ini" -ForegroundColor Gray
    }
    Write-Host "   4. Reinicie Apache (o el servidor PHP) y vuelva a ejecutar este setup" -ForegroundColor Gray
    Write-Host ""
    if (-not (Ask-YesNo "Desea continuar con el setup de todas formas?")) {
        Read-Host "Presione ENTER para salir"; exit 1
    }
}

# ODBC Driver (lo usa php_sqlsrv por debajo)
try {
    $odbc = Get-OdbcDriver -ErrorAction SilentlyContinue | Where-Object { $_.Name -like "*ODBC Driver*SQL Server*" }
    if ($odbc) { Write-Ok "ODBC Driver: $($odbc[0].Name)" }
    else { Write-Warn "ODBC Driver for SQL Server no detectado - instalelo si la conexion falla." }
} catch { Write-Warn "No se pudo consultar drivers ODBC (no critico)." }

# ════════════════════════════════════════════════════════════
#  PASO 3 - Modo de servidor web y URL del portal
# ════════════════════════════════════════════════════════════
Write-Head "PASO 3 - Servidor web"

$ServeMode = "PHP"   # PHP | APACHE
$AppUrl    = ""
$PhpPort   = 0
$DeployRoot = $ProjectRoot   # carpeta que servira Apache (si aplica)

$hasApache = ($EnvChoice.Type -eq "XAMPP" -or $EnvChoice.Type -eq "WAMP" -or $EnvChoice.Type -eq "LARAGON")

if ($hasApache) {
    $m = Show-Menu -Title "Como desea servir el portal?" -Options @(
        "Apache de $($EnvChoice.Type)  ->  http://localhost/$ProjectName  (recomendado)",
        "Servidor PHP local (php -S)  ->  http://localhost:PUERTO  (sin Apache, ideal desarrollo)"
    )
    if ($m -eq 1) { $ServeMode = "APACHE" }
} else {
    Write-Inf "Sin Apache disponible: se usara el servidor PHP local (php -S localhost:PUERTO)."
    Write-Inf "Funciona en cualquier computador - solo necesita el php.exe ya detectado."
}

if ($ServeMode -eq "APACHE") {

    # ── Puerto de Apache (leido de httpd.conf si es posible) ──
    $apachePort = 80
    if ($EnvChoice.ApacheConf -and (Test-Path $EnvChoice.ApacheConf)) {
        $listenLine = Select-String -Path $EnvChoice.ApacheConf -Pattern '^\s*Listen\s+(\d+)' |
                      Select-Object -First 1
        if ($listenLine) { $apachePort = [int]$listenLine.Matches[0].Groups[1].Value }
    }
    Write-Inf "Puerto de Apache detectado en httpd.conf: $apachePort"
    $pIn = Read-Host "  Puerto de Apache (ENTER = $apachePort)"
    if (-not [string]::IsNullOrWhiteSpace($pIn)) {
        $n = 0
        if ([int]::TryParse($pIn, [ref]$n) -and $n -ge 1 -and $n -le 65535) { $apachePort = $n }
        else { Write-Warn "Puerto invalido - se mantiene $apachePort" }
    }

    # ── El proyecto debe estar dentro de htdocs/www ──
    $htdocs = $EnvChoice.Htdocs
    $insideHtdocs = $ProjectRoot.ToLower().StartsWith($htdocs.ToLower())

    $urlFolder = $ProjectName
    if ($insideHtdocs) {
        $rel = $ProjectRoot.Substring($htdocs.Length).Trim('\')
        $urlFolder = $rel -replace '\\', '/'
        Write-Ok "El proyecto YA esta dentro de $htdocs"
    } else {
        Write-Warn "El proyecto NO esta dentro de: $htdocs"
        $linkChoice = Show-Menu -Title "Como desea publicarlo en Apache?" -Options @(
            "Crear un enlace (junction) en $htdocs\$ProjectName  (recomendado, sin duplicar archivos)",
            "Copiar el proyecto completo a $htdocs\$ProjectName",
            "Nada - yo lo movere manualmente despues"
        )
        $linkPath = Join-Path $htdocs $ProjectName

        if ($linkChoice -eq 1) {
            if (Test-Path $linkPath) {
                Write-Warn "Ya existe: $linkPath - se usara tal cual."
            } else {
                try {
                    New-Item -ItemType Junction -Path $linkPath -Target $ProjectRoot | Out-Null
                    Write-Ok "Junction creado: $linkPath -> $ProjectRoot"
                } catch {
                    # Fallback con mklink
                    cmd /c mklink /J "$linkPath" "$ProjectRoot" | Out-Null
                    if (Test-Path $linkPath) { Write-Ok "Junction creado via mklink" }
                    else { Write-Err "No se pudo crear el junction. Copie el proyecto manualmente." }
                }
            }
        } elseif ($linkChoice -eq 2) {
            if (Test-Path $linkPath) {
                Write-Warn "Ya existe $linkPath"
                if (-not (Ask-YesNo "Sobrescribir la copia existente?")) { $linkChoice = 3 }
            }
            if ($linkChoice -eq 2) {
                Write-Inf "Copiando proyecto a $linkPath (puede tardar)..."
                Copy-Item -Path $ProjectRoot -Destination $linkPath -Recurse -Force
                Write-Ok "Proyecto copiado."
                $DeployRoot = $linkPath
                $ConfigTargets += $linkPath   # la copia tambien recibe la configuracion
            }
        }
    }

    if ($apachePort -eq 80) { $AppUrl = "http://localhost/$urlFolder" }
    else                    { $AppUrl = "http://localhost:$apachePort/$urlFolder" }

} else {
    # ── Servidor PHP local (php -S localhost:PUERTO) ──
    $sugerido = Find-FreePort -Start 8080
    Write-Inf "Puerto libre sugerido: $sugerido"
    $PhpPort = Ask-Port -Default $sugerido -Para "el servidor PHP local"
    $AppUrl = "http://localhost:$PhpPort"
    Write-Inf "Comando que usara el portal:  php -S localhost:$PhpPort"
}

Write-Ok "URL del portal: $AppUrl"
if ($ServeMode -eq "APACHE") {
    $script:Estado["Servidor"] = "Apache ($($EnvChoice.Type)) puerto $apachePort"
} else {
    $script:Estado["Servidor"] = "PHP local  (php -S localhost:$PhpPort)"
}
$script:Estado["URL"] = "$AppUrl/login"
Show-Estado

# ════════════════════════════════════════════════════════════
#  PASO 4 - Base de datos: instancia, autenticacion
# ════════════════════════════════════════════════════════════
Write-Head "PASO 4 - SQL Server"

# Detectar instancias locales (registro + servicios)
$instances = @()
try {
    $reg = Get-ItemProperty "HKLM:\SOFTWARE\Microsoft\Microsoft SQL Server\Instance Names\SQL" -ErrorAction SilentlyContinue
    if ($reg) {
        foreach ($p in $reg.PSObject.Properties) {
            if ($p.Name -notmatch '^PS') {
                if ($p.Name -eq "MSSQLSERVER") { $instances += "localhost" }
                else { $instances += "localhost\$($p.Name)" }
            }
        }
    }
} catch {}

$dbMenuOptions = @()
foreach ($i in $instances) {
    # Esta el servicio corriendo?
    $svcName = "MSSQLSERVER"
    if ($i -match '\\(.+)$') { $svcName = "MSSQL`$$($Matches[1])" }
    $svc = Get-Service -Name $svcName -ErrorAction SilentlyContinue
    $state = "?"
    if ($svc) { $state = $svc.Status }
    $dbMenuOptions += "$i   (servicio: $state)"
}
$dbMenuOptions += "Escribir servidor/instancia manualmente (ej: 192.168.1.10\SQLEXPRESS,1433)"

if ($instances.Count -gt 0) { Write-Ok "$($instances.Count) instancia(s) local(es) detectada(s)" }
else { Write-Warn "No se detectaron instancias locales de SQL Server." }

$dbSel = Show-Menu -Title "Que servidor SQL Server usara?" -Options $dbMenuOptions
if ($dbSel -le $instances.Count) {
    $dbServer = $instances[$dbSel - 1]
    # Si el servicio esta detenido, ofrecer iniciarlo
    $svcName = "MSSQLSERVER"
    if ($dbServer -match '\\(.+)$') { $svcName = "MSSQL`$$($Matches[1])" }
    $svc = Get-Service -Name $svcName -ErrorAction SilentlyContinue
    if ($svc -and $svc.Status -ne "Running") {
        if (Ask-YesNo "El servicio $svcName esta detenido. Intentar iniciarlo? (requiere permisos)" $true) {
            try { Start-Service -Name $svcName; Write-Ok "Servicio iniciado." }
            catch { Write-Warn "No se pudo iniciar: $_. Inicielo manualmente desde services.msc" }
        }
    }
} else {
    $dbServer = Read-Host "  Servidor\Instancia"
    if ([string]::IsNullOrWhiteSpace($dbServer)) { $dbServer = "localhost\SQLEXPRESS" }
}

$dbName = Read-Host "  Nombre de la base de datos (ENTER = PORTAL_APM)"
if ([string]::IsNullOrWhiteSpace($dbName)) { $dbName = "PORTAL_APM" }

$authSel = Show-Menu -Title "Tipo de autenticacion:" -Options @(
    "Windows Authentication  (recomendado en equipo local)",
    "SQL Server  (usuario y contrasena)"
)
$dbUser = ""; $dbPass = ""
if ($authSel -eq 2) {
    $dbUser = Read-Host "  Usuario SQL (ej: sa)"
    $secure = Read-Host "  Contrasena" -AsSecureString
    $dbPass = [Runtime.InteropServices.Marshal]::PtrToStringAuto(
                  [Runtime.InteropServices.Marshal]::SecureStringToBSTR($secure))
}

$script:Estado["SQL Server"] = $dbServer
$script:Estado["Base datos"] = $dbName
if ([string]::IsNullOrWhiteSpace($dbUser)) { $script:Estado["Auth BD"] = "Windows Authentication" }
else { $script:Estado["Auth BD"] = "SQL ($dbUser)" }
Show-Estado

# ════════════════════════════════════════════════════════════
#  PASO 5 - Escribir configuracion (config/app.php + .env)
# ════════════════════════════════════════════════════════════
Write-Head "PASO 5 - Escribiendo configuracion"

# config/app.php soporta URL 'auto': detecta protocolo/host/puerto/subcarpeta
# en tiempo de ejecucion. Con 'auto' el portal navega bien en php -S, XAMPP
# y Wamp SIN volver a tocar la configuracion al cambiar de entorno.
$urlMode = Show-Menu -Title "Modo de URL para las rutas del portal:" -Options @(
    "AUTOMATICA ('auto') - se adapta sola a php -S / XAMPP / Wamp / IIS  (recomendado)",
    "FIJA - escribir exactamente: $AppUrl"
)
if ($urlMode -eq 1) {
    $appUrlValue = "auto"
    $script:Estado["Config URL"] = "auto (se adapta sola al entorno)"
} else {
    $appUrlValue = $AppUrl
    $script:Estado["Config URL"] = "fija: $AppUrl"
}

# En PHP entre comillas simples solo hay que escapar \ y '
function Escape-PhpSingleQuoted { param([string]$v)
    return $v.Replace("\", "\\").Replace("'", "\'")
}

function Update-AppConfig {
    param([string]$AppFile)
    if (-not (Test-Path $AppFile)) { Write-Warn "No existe: $AppFile"; return }
    $content = [System.IO.File]::ReadAllText($AppFile)

    Write-Host ""
    Write-Host "  Cambios en $AppFile :" -ForegroundColor White

    # ── URL: linea `$__appUrl = '...';` (soporta 'auto') ──
    $urlPattern  = "\`$__appUrl\s*=\s*'([^']*)';"
    $urlOldMatch = [regex]::Match($content, $urlPattern)
    $urlOld      = "(no encontrado)"
    if ($urlOldMatch.Success) { $urlOld = $urlOldMatch.Groups[1].Value }
    if ($urlOld -eq $appUrlValue) {
        Write-Host ("    {0,-10} sin cambios  ({1})" -f "APP_URL", $appUrlValue) -ForegroundColor DarkGray
    } else {
        Write-Host ("    {0,-10} '{1}'  ->  '{2}'" -f "APP_URL", $urlOld, $appUrlValue) -ForegroundColor Yellow
    }
    $urlReplacement = ("`$__appUrl = '" + (Escape-PhpSingleQuoted $appUrlValue) + "';").Replace('$', '$$')
    $content = [regex]::Replace($content, $urlPattern, $urlReplacement, 'None')

    # ── Constantes de BD: lineas define('X', ...); ──
    # DB_PASS se enmascara al mostrar; el resto se muestra tal cual
    $pairs = @(
        @{ K = 'DB_SERVER'; V = "'" + (Escape-PhpSingleQuoted $dbServer) + "'"; Show = $dbServer },
        @{ K = 'DB_NAME';   V = "'" + (Escape-PhpSingleQuoted $dbName)   + "'"; Show = $dbName },
        @{ K = 'DB_USER';   V = "'" + (Escape-PhpSingleQuoted $dbUser)   + "'"; Show = $dbUser },
        @{ K = 'DB_PASS';   V = "'" + (Escape-PhpSingleQuoted $dbPass)   + "'"; Show = ('*' * $dbPass.Length) }
    )
    foreach ($pair in $pairs) {
        # Leer valor actual para mostrar ANTES -> DESPUES
        $pattern  = "define\(\s*'" + $pair.K + "'\s*,\s*([^;]*)\);"
        $oldMatch = [regex]::Match($content, $pattern)
        $oldVal   = "(no encontrado)"
        if ($oldMatch.Success) { $oldVal = $oldMatch.Groups[1].Value.Trim().Trim("'") }
        $newShow = $pair.Show
        if ($pair.K -eq 'DB_PASS' -and $oldVal -ne "(no encontrado)" -and $oldVal.Length -gt 0) {
            $oldVal = '*' * $oldVal.Length
        }
        if ($oldVal -eq $newShow) {
            Write-Host ("    {0,-10} sin cambios  ({1})" -f $pair.K, $newShow) -ForegroundColor DarkGray
        } else {
            Write-Host ("    {0,-10} '{1}'  ->  '{2}'" -f $pair.K, $oldVal, $newShow) -ForegroundColor Yellow
        }

        $replacement = "define('" + $pair.K + "', " + $pair.V + ");"
        # Escapar $ en el replacement para .NET Regex
        $replacement = $replacement.Replace('$', '$$')
        $content = [regex]::Replace($content, $pattern, $replacement, 'None')
    }
    Write-FileUtf8NoBom -Path $AppFile -Content $content
    Write-Ok "Guardado: $AppFile"
}

foreach ($target in $ConfigTargets) {
    Update-AppConfig -AppFile (Join-Path $target "config\app.php")

    # .env (compatibilidad con herramientas y scripts que aun lo leen)
    $envContent = @"
# Portal APM v2.0 - Variables de Entorno
# Generado por SETUP_PROYECTO.ps1 el $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')

APP_NAME="Portal APM"
APP_ENV=development
APP_DEBUG=true
APP_URL=$AppUrl
APP_TIMEZONE=America/Guayaquil

DB_SERVER=$dbServer
DB_NAME=$dbName
DB_USER=$dbUser
DB_PASS=$dbPass
DB_ENCRYPT=false
DB_TRUST_CERT=true

SESSION_TIMEOUT=1800
SESSION_HOURS_EXPIRA=8
LOGIN_MAX_INTENTOS=5
LOGIN_BLOQUEO_MINUTOS=30
PASSWORD_HISTORIAL_MAX=5
AUDIT_RETENTION_YEARS=2
APP_THEME_DEFAULT=light
"@
    Write-FileUtf8NoBom -Path (Join-Path $target ".env") -Content $envContent
    Write-Ok "Actualizado: $(Join-Path $target '.env')"
}

# ── .htaccess (Apache) y web.config (IIS) ──
foreach ($target in $ConfigTargets) {
    $htaccess = Join-Path $target ".htaccess"
    if (-not (Test-Path $htaccess)) {
        $ht = @'
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php?url=$1 [QSA,L]
'@
        Write-FileUtf8NoBom -Path $htaccess -Content $ht
        Write-Ok ".htaccess creado en $target"
    } else {
        Write-Ok ".htaccess ya existe en $target"
    }

    $webConfig = Join-Path $target "web.config"
    if (-not (Test-Path $webConfig)) {
        $wc = @'
<?xml version="1.0" encoding="UTF-8"?>
<configuration>
    <system.webServer>
        <rewrite>
            <rules>
                <rule name="Portal APM URL Rewrite" stopProcessing="true">
                    <match url="^(.*)$" ignoreCase="false" />
                    <conditions logicalGrouping="MatchAll">
                        <add input="{REQUEST_FILENAME}" matchType="IsFile" negate="true" />
                        <add input="{REQUEST_FILENAME}" matchType="IsDirectory" negate="true" />
                    </conditions>
                    <action type="Rewrite" url="index.php?url={R:1}" appendQueryString="true" />
                </rule>
            </rules>
        </rewrite>
    </system.webServer>
</configuration>
'@
        Write-FileUtf8NoBom -Path $webConfig -Content $wc
        Write-Ok "web.config creado en $target"
    } else {
        Write-Ok "web.config ya existe en $target"
    }
}

# ════════════════════════════════════════════════════════════
#  PASO 6 - Ejecutar el script de base de datos
# ════════════════════════════════════════════════════════════
Write-Head "PASO 6 - Base de datos"

# Start-Process -ArgumentList une los argumentos SIN comillas: cualquier
# argumento con espacios (rutas, "SELECT 1", contrasenas) se rompe.
# Esta funcion agrega comillas y escapa las internas.
function Quote-Arg {
    param([string]$v)
    return '"' + ($v -replace '"', '\"') + '"'
}

# Ejecuta sqlcmd capturando salida en archivos temporales (evita problemas
# de stderr nativo con ErrorActionPreference=Stop) y devuelve resultado.
function Invoke-SqlcmdSafe {
    param([string[]]$ArgList)
    $so = Join-Path $env:TEMP "apm_sqlout_$(Get-Random).txt"
    $se = Join-Path $env:TEMP "apm_sqlerr_$(Get-Random).txt"
    try {
        $p = Start-Process sqlcmd -ArgumentList $ArgList -Wait -PassThru `
             -RedirectStandardOutput $so -RedirectStandardError $se -WindowStyle Hidden
        $out = ""; $err = ""
        if (Test-Path $so) { $out = Get-Content $so -Raw -ErrorAction SilentlyContinue }
        if (Test-Path $se) { $err = Get-Content $se -Raw -ErrorAction SilentlyContinue }
        return @{ Exit = $p.ExitCode; Out = "$out"; Err = "$err" }
    } catch {
        return @{ Exit = -1; Out = ""; Err = "$_" }
    } finally {
        Remove-Item $so, $se -Force -ErrorAction SilentlyContinue
    }
}

$sqlScriptOk = $false
Write-Warn "ATENCION: PORTAL_APM_COMPLETO.sql CREA/RECREA la base '$dbName' (borra datos si ya existia)."

if (Ask-YesNo "Ejecutar el script de base de datos ahora?" $true) {

    # Preparar archivo (reemplazo de nombre si la BD es personalizada)
    $sqlFile = Join-Path $ProjectRoot "Z.BASES DE DATOS\PORTAL_APM_COMPLETO.sql"
    $tempSqlFile = $null
    if ($dbName -ne "PORTAL_APM") {
        Write-Inf "Ajustando nombre de BD a '$dbName' en copia temporal..."
        $tempSqlFile = Join-Path $env:TEMP "portal_apm_setup_$(Get-Random).sql"
        (Get-Content $sqlFile -Raw) -replace 'PORTAL_APM', $dbName |
            Set-Content $tempSqlFile -Encoding UTF8
        $sqlFile = $tempSqlFile
    }

    $authArgs = @()
    if (-not [string]::IsNullOrWhiteSpace($dbUser)) {
        $authArgs = @("-U", (Quote-Arg $dbUser), "-P", (Quote-Arg $dbPass))
    } else {
        $authArgs = @("-E")
    }
    $serverArg = Quote-Arg $dbServer

    # ── METODO 1: sqlcmd (si existe) ──
    $sqlcmd = Get-Command sqlcmd -ErrorAction SilentlyContinue
    if ($sqlcmd) {
        Write-Inf "Metodo 1: sqlcmd - probando conexion a $dbServer ..."

        # Sondeo: detectar que combinacion de flags funciona ANTES del script grande.
        # sqlcmd moderno (ODBC18) exige -C con certificado autofirmado; el antiguo no conoce -C.
        $goodFlags = $null
        $probeErr  = ""
        foreach ($extra in @(@("-C"), @())) {
            $probe = Invoke-SqlcmdSafe -ArgList (@("-S", $serverArg) + $authArgs + @("-Q", (Quote-Arg "SELECT 1"), "-b") + $extra)
            if ($probe.Exit -eq 0) { $goodFlags = $extra; break }
            $probeErr = ($probe.Err + "`n" + $probe.Out).Trim()
        }

        if ($null -ne $goodFlags) {
            Write-Ok "Conexion sqlcmd verificada (flags: $(if ($goodFlags.Count) { $goodFlags -join ' ' } else { 'estandar' }))"
            Write-Inf "Ejecutando script (puede tardar 1-2 minutos)..."
            $run = Invoke-SqlcmdSafe -ArgList (@("-S", $serverArg) + $authArgs + @("-i", (Quote-Arg $sqlFile), "-b") + $goodFlags)
            if ($run.Exit -eq 0) {
                Write-Ok "Base de datos '$dbName' creada e inicializada correctamente (sqlcmd)."
                $sqlScriptOk = $true
            } else {
                Write-Warn "sqlcmd fallo (codigo $($run.Exit)). Salida:"
                $tail = (($run.Err + "`n" + $run.Out).Trim() -split "`n" | Select-Object -Last 12) -join "`n"
                Write-Host $tail -ForegroundColor DarkYellow
            }
        } else {
            Write-Warn "sqlcmd no pudo conectarse a $dbServer. Ultimo error:"
            Write-Host (($probeErr -split "`n" | Select-Object -Last 5) -join "`n") -ForegroundColor DarkYellow
        }
    } else {
        Write-Warn "sqlcmd no esta instalado en este equipo."
    }

    # ── METODO 2 (fallback universal): ejecutar via PHP + sqlsrv ──
    # No requiere sqlcmd: usa el mismo PHP que necesita el portal.
    if (-not $sqlScriptOk) {
        if ($sqlsrvExt -eq "YES") {
            Write-Inf "Metodo 2: ejecucion via PHP (db\run_sql.php) - no requiere sqlcmd..."
            $runner = Join-Path $ProjectRoot "db\run_sql.php"
            if (Test-Path $runner) {
                & $PhpExe $runner $sqlFile $dbServer $dbUser $dbPass
                if ($LASTEXITCODE -eq 0) {
                    Write-Ok "Base de datos '$dbName' creada e inicializada correctamente (PHP)."
                    $sqlScriptOk = $true
                } else {
                    Write-Warn "El ejecutor PHP fallo (codigo $LASTEXITCODE). Revise el error mostrado arriba."
                }
            } else {
                Write-Warn "No se encontro db\run_sql.php"
            }
        } else {
            Write-Warn "Sin sqlcmd y sin extension sqlsrv: no hay forma automatica de ejecutar el SQL."
        }
    }

    if (-not $sqlScriptOk) {
        Write-Host ""
        Write-Host "  Para crear la base de datos manualmente:" -ForegroundColor White
        Write-Host "   1. Abra SQL Server Management Studio (SSMS)" -ForegroundColor Gray
        Write-Host "   2. Conectese a: $dbServer" -ForegroundColor Gray
        Write-Host "   3. Abra 'Z.BASES DE DATOS\PORTAL_APM_COMPLETO.sql' y ejecutelo (F5)" -ForegroundColor Gray
        Write-Host ""
    }

    if ($tempSqlFile -and (Test-Path $tempSqlFile)) { Remove-Item $tempSqlFile -Force }
}

# ── Probar la conexion desde PHP (la prueba definitiva) ──
if ($sqlsrvExt -eq "YES") {
    Write-Inf "Probando conexion desde PHP..."
    $testFile = Join-Path $env:TEMP "apm_test_conn_$(Get-Random).php"
    $userPhp = Escape-PhpSingleQuoted $dbUser
    $passPhp = Escape-PhpSingleQuoted $dbPass
    $servPhp = Escape-PhpSingleQuoted $dbServer
    $namePhp = Escape-PhpSingleQuoted $dbName
    $testCode = @"
<?php
`$opt = ['Database' => '$namePhp', 'TrustServerCertificate' => true, 'Encrypt' => false];
if ('$userPhp' !== '') { `$opt['UID'] = '$userPhp'; `$opt['PWD'] = '$passPhp'; }
`$c = @sqlsrv_connect('$servPhp', `$opt);
if (!`$c) { echo 'CONN_FAIL: ' . print_r(sqlsrv_errors(), true); exit; }
`$s = @sqlsrv_query(`$c, 'SELECT COUNT(*) AS n FROM CORE_Usuarios');
if (`$s && (`$r = sqlsrv_fetch_array(`$s, SQLSRV_FETCH_ASSOC))) { echo 'CONN_OK;USERS=' . `$r['n']; }
else { echo 'CONN_OK;NOTABLES'; }
sqlsrv_close(`$c);
"@
    Write-FileUtf8NoBom -Path $testFile -Content $testCode
    $testOut = & $PhpExe $testFile 2>$null
    Remove-Item $testFile -Force -ErrorAction SilentlyContinue
    if ($testOut -match "^CONN_OK;USERS=(\d+)") {
        Write-Ok "BD LISTA: PHP conectado a $dbServer / $dbName - $($Matches[1]) usuario(s) en CORE_Usuarios"
        $script:Estado["Estado BD"] = "Lista ($($Matches[1]) usuarios)"
    } elseif ($testOut -like "CONN_OK*") {
        Write-Warn "PHP conecta a '$dbName' pero NO existen las tablas - el script SQL no se ejecuto."
        Write-Warn "Ejecute PORTAL_APM_COMPLETO.sql (SSMS) o vuelva a correr este setup."
        $script:Estado["Estado BD"] = "Sin tablas - falta ejecutar SQL"
    } else {
        Write-Warn "PHP no pudo conectarse a la base de datos:"
        Write-Host "  $testOut" -ForegroundColor DarkYellow
        Write-Warn "Revise instancia, credenciales, y que la BD '$dbName' exista."
        $script:Estado["Estado BD"] = "SIN CONEXION"
    }
    Show-Estado
}

# ════════════════════════════════════════════════════════════
#  PASO 7 - Verificacion rapida de archivos criticos
# ════════════════════════════════════════════════════════════
Write-Head "PASO 7 - Archivos criticos"
$criticalFiles = @(
    "index.php", "routes.php", "config\app.php",
    "core\Database.php", "core\Router.php", "core\Controller.php",
    "core\Model.php", "core\View.php", "core\ModuleSecurity.php",
    "helpers\security_helper.php", "helpers\session_helper.php",
    "helpers\url_helper.php", "helpers\form_helper.php",
    "modules\Credenciales\controllers\AuthController.php",
    "modules\Central\controllers\DashboardController.php",
    "modules\Central\controllers\AdminController.php",
    "modules\Central\controllers\MenuController.php",
    "modules\Central\views\layouts\shell.php",
    "modules\Central\views\layouts\sidebar.php",
    "modules\Credenciales\views\login\index.php",
    "css\variables.css", "css\style.css",
    "public\js\app.js", "public\js\charts.js",
    "Z.BASES DE DATOS\PORTAL_APM_COMPLETO.sql"
)
$missing = 0
foreach ($file in $criticalFiles) {
    if (-not (Test-Path (Join-Path $ProjectRoot $file))) {
        Write-Warn "FALTA: $file"; $missing++
    }
}
if ($missing -eq 0) { Write-Ok "Los $($criticalFiles.Count) archivos criticos estan presentes" }
else { Write-Warn "$missing archivo(s) faltante(s) - verifique la copia del proyecto." }

# ════════════════════════════════════════════════════════════
#  PASO 8 - Iniciar el portal
# ════════════════════════════════════════════════════════════
Write-Head "PASO 8 - Iniciar"

if ($ServeMode -eq "APACHE") {
    $apacheUp = Test-PortListening -Port $apachePort
    if ($apacheUp) {
        Write-Ok "Apache ya esta escuchando en el puerto $apachePort"

        # Prueba REAL de navegacion: pedir /login y verificar que el rewrite funciona
        Write-Inf "Verificando navegacion: $AppUrl/login ..."
        try {
            $resp = Invoke-WebRequest -Uri "$AppUrl/login" -UseBasicParsing -TimeoutSec 10 -ErrorAction Stop
            if ($resp.StatusCode -eq 200) {
                Write-Ok "NAVEGACION VERIFICADA: /login responde 200 - rutas y rewrite funcionando."
                $script:Estado["Navegacion"] = "Verificada (200 OK)"
            }
        } catch {
            $code = $null
            if ($_.Exception.Response) { try { $code = [int]$_.Exception.Response.StatusCode } catch {} }
            if ($code -eq 404) {
                Write-Warn "/login devuelve 404: mod_rewrite o AllowOverride NO estan activos en Apache."
                Write-Host "   WampServer: icono verde -> Apache -> Apache Modules -> marcar 'rewrite_module'" -ForegroundColor Gray
                Write-Host "   XAMPP: en apache\conf\httpd.conf quite el # de 'LoadModule rewrite_module'" -ForegroundColor Gray
                Write-Host "          y asegure 'AllowOverride All' en el bloque <Directory> de htdocs" -ForegroundColor Gray
                $script:Estado["Navegacion"] = "FALLO 404 - activar rewrite_module"
            } elseif ($code -eq 500) {
                Write-Warn "/login devuelve 500: el rewrite funciona pero PHP fallo (revise BD/extension sqlsrv del PHP de Apache)."
                $script:Estado["Navegacion"] = "Rewrite OK, error PHP 500"
            } elseif ($code) {
                Write-Warn "Respuesta HTTP $code en $AppUrl/login"
                $script:Estado["Navegacion"] = "HTTP $code"
            } else {
                Write-Warn "No se pudo probar la URL: $_"
                $script:Estado["Navegacion"] = "No probada"
            }
        }
        Show-Estado
    } else {
        Write-Warn "Apache NO esta en ejecucion (puerto $apachePort libre)."
        if ($EnvChoice.Control -and (Test-Path $EnvChoice.Control)) {
            if (Ask-YesNo "Abrir el panel de control de $($EnvChoice.Type) para iniciarlo?" $true) {
                Start-Process $EnvChoice.Control
                Write-Inf "Inicie Apache desde el panel y luego abra: $AppUrl/login"
            }
        } else {
            Write-Warn "Inicie Apache manualmente y luego abra: $AppUrl/login"
        }
    }
    if (Ask-YesNo "Abrir el portal en el navegador ahora?" $true) {
        Start-Process "$AppUrl/login"
    }
} else {
    # ── Generar lanzador INICIAR_SERVIDOR.bat con el PHP y puerto elegidos ──
    # Permite relanzar el portal con doble clic en este (o cualquier) computador.
    $launcherPath = Join-Path $ProjectRoot "INICIAR_SERVIDOR.bat"
    $launcher = @"
@echo off
title Portal APM - Servidor PHP local (puerto $PhpPort)
echo.
echo  Portal APM - Servidor PHP local
echo  URL: $AppUrl/login
echo  (Mantenga esta ventana abierta mientras usa el portal)
echo.
start "" "$AppUrl/login"
"$PhpExe" -S localhost:$PhpPort -t "%~dp0"
pause
"@
    Write-FileUtf8NoBom -Path $launcherPath -Content $launcher
    Write-Ok "Lanzador creado: INICIAR_SERVIDOR.bat  (doble clic para arrancar el portal)"
    Write-Inf "Si este setup se ejecuta en otro computador, el lanzador se regenera con sus rutas."

    if (Ask-YesNo "Iniciar el servidor PHP en el puerto $PhpPort ahora?" $true) {
        Start-Process $PhpExe -ArgumentList "-S localhost:$PhpPort -t `"$ProjectRoot`"" -WorkingDirectory $ProjectRoot
        Start-Sleep -Seconds 2
        Start-Process "$AppUrl/login"
        Write-Ok "Servidor iniciado. Navegador abierto en $AppUrl/login"
        Write-Inf "La ventana del servidor PHP debe permanecer abierta mientras use el portal."
    } else {
        Write-Inf "Para iniciarlo despues: doble clic en INICIAR_SERVIDOR.bat"
        Write-Inf "  o ejecute:  `"$PhpExe`" -S localhost:$PhpPort -t `"$ProjectRoot`""
    }
}

# ════════════════════════════════════════════════════════════
#  Resumen
# ════════════════════════════════════════════════════════════
Write-Host ""
Write-Host "  ======================================================" -ForegroundColor Cyan
Write-Host "   Setup completado" -ForegroundColor Green
Write-Host "  ------------------------------------------------------" -ForegroundColor Cyan
Write-Host "   Entorno:       $($EnvChoice.Label)"                     -ForegroundColor Gray
Write-Host "   URL:           $AppUrl/login"                           -ForegroundColor Gray
Write-Host "   Base de datos: $dbServer  ->  $dbName"                  -ForegroundColor Gray
if ([string]::IsNullOrWhiteSpace($dbUser)) {
Write-Host "   Autenticacion: Windows Authentication"                  -ForegroundColor Gray
} else {
Write-Host "   Autenticacion: SQL ($dbUser)"                           -ForegroundColor Gray
}
Write-Host "   Credenciales:  admin / Apm2024*"                        -ForegroundColor Gray
if ($ServeMode -eq "PHP") {
Write-Host "   Relanzar:      doble clic en INICIAR_SERVIDOR.bat"      -ForegroundColor Gray
Write-Host "                  (php -S localhost:$PhpPort)"             -ForegroundColor DarkGray
} else {
Write-Host "   Relanzar:      inicie Apache desde el panel de $($EnvChoice.Type)" -ForegroundColor Gray
}
Write-Host "  ------------------------------------------------------" -ForegroundColor Cyan
Write-Host "   Si cambia de equipo o de entorno, vuelva a ejecutar"    -ForegroundColor DarkGray
Write-Host "   SETUP_PROYECTO.bat - la configuracion se regenera."     -ForegroundColor DarkGray
Write-Host "  ======================================================" -ForegroundColor Cyan
Write-Host ""
Read-Host "Presione ENTER para cerrar"
