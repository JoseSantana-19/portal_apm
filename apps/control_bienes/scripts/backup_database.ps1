[CmdletBinding()]
param()

$ErrorActionPreference = 'Stop'
$projectRoot = Split-Path -Parent (Split-Path -Parent $MyInvocation.MyCommand.Path)
$envPath = Join-Path $projectRoot '.env'
if (-not (Test-Path -LiteralPath $envPath)) { throw 'No se encontro el archivo .env.' }

$settings = @{}
Get-Content -LiteralPath $envPath -Encoding UTF8 | ForEach-Object {
    $line = $_.Trim()
    if ($line -and -not $line.StartsWith('#') -and $line.Contains('=')) {
        $parts = $line.Split('=', 2)
        $settings[$parts[0].Trim()] = $parts[1].Trim().Trim('"').Trim("'")
    }
}
foreach ($name in @('DB_HOST','DB_NAME','DB_USER','DB_PASS')) {
    if (-not $settings.ContainsKey($name)) { throw "Falta $name en .env." }
}
if ($settings.DB_NAME -notmatch '^[A-Za-z0-9_]+$') { throw 'DB_NAME no es valido.' }

$backupFolder = Join-Path $projectRoot 'backup'
if (-not (Test-Path -LiteralPath $backupFolder)) { New-Item -ItemType Directory -Path $backupFolder | Out-Null }
$backupPath = Join-Path $backupFolder ($settings.DB_NAME + '_' + (Get-Date -Format 'yyyyMMdd_HHmmss') + '.bak')
$safePath = $backupPath.Replace("'", "''")

$builder = New-Object System.Data.SqlClient.SqlConnectionStringBuilder
$builder['Data Source'] = $settings.DB_HOST
$builder['Initial Catalog'] = 'master'
$builder['User ID'] = $settings.DB_USER
$builder['Password'] = $settings.DB_PASS
$builder['Encrypt'] = $false
$builder['TrustServerCertificate'] = $true
$connection = New-Object System.Data.SqlClient.SqlConnection $builder.ConnectionString

try {
    $connection.Open()
    $command = $connection.CreateCommand()
    $command.CommandTimeout = 0
    $command.CommandText = "BACKUP DATABASE [$($settings.DB_NAME)] TO DISK=N'$safePath' WITH COPY_ONLY, INIT, CHECKSUM"
    [void]$command.ExecuteNonQuery()
    $command.CommandText = "RESTORE VERIFYONLY FROM DISK=N'$safePath' WITH CHECKSUM"
    [void]$command.ExecuteNonQuery()
} finally {
    $connection.Dispose()
    $builder['Password'] = ''
    $settings.DB_PASS = ''
}

$file = Get-Item -LiteralPath $backupPath
Write-Host "Respaldo verificado: $($file.FullName)" -ForegroundColor Green
Write-Host ('Tamano: {0:N2} MB' -f ($file.Length / 1MB))
