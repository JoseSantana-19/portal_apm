[CmdletBinding(SupportsShouldProcess)]
param([int]$ApplicationDays=90,[int]$IisDays=30,[switch]$ConfirmCleanup)
$ErrorActionPreference='Stop'
if($ApplicationDays -lt 30 -or $IisDays -lt 14){throw 'La retención mínima es 30 días para aplicación y 14 días para IIS.'}
$project=Split-Path $PSScriptRoot -Parent
$targets=@(
    @{Root=(Join-Path $project 'storage\logs');Days=$ApplicationDays;Pattern='*.txt'},
    @{Root='C:\inetpub\logs\PortalPortuario';Days=$IisDays;Pattern='*.log'}
)
$candidates=@()
foreach($target in $targets){
    try{
        if(-not(Test-Path -LiteralPath $target.Root -ErrorAction Stop)){continue}
        $resolved=(Resolve-Path -LiteralPath $target.Root -ErrorAction Stop).Path
        if($resolved -notlike "$project*" -and $resolved -ne 'C:\inetpub\logs\PortalPortuario'){throw "Raíz no permitida: $resolved"}
        $limit=(Get-Date).AddDays(-[int]$target.Days)
        $candidates+=Get-ChildItem -LiteralPath $resolved -File -Recurse -Filter $target.Pattern -ErrorAction Stop | Where-Object LastWriteTime -lt $limit
    }catch{Write-Warning "No se pudo inspeccionar $($target.Root): $($_.Exception.Message)"}
}
$candidates | Select-Object FullName,LastWriteTime,Length
if(-not $ConfirmCleanup){Write-Output "RETENTION_DRY_RUN=$($candidates.Count)";exit 0}
foreach($file in $candidates){if($PSCmdlet.ShouldProcess($file.FullName,'Eliminar log vencido')){Remove-Item -LiteralPath $file.FullName -Force}}
Write-Output "RETENTION_REMOVED=$($candidates.Count)"
