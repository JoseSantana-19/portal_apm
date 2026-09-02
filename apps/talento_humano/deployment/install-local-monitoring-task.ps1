[CmdletBinding(SupportsShouldProcess)]
param([string]$TaskName='Portal APM - Salud diaria',[string]$RunAt='07:00')
$ErrorActionPreference='Stop'
$monitor=Join-Path $PSScriptRoot 'monitor-local-health.ps1'
$action=New-ScheduledTaskAction -Execute 'powershell.exe' -Argument "-NoProfile -ExecutionPolicy Bypass -File `"$monitor`""
$trigger=New-ScheduledTaskTrigger -Daily -At $RunAt
$settings=New-ScheduledTaskSettingsSet -StartWhenAvailable -ExecutionTimeLimit (New-TimeSpan -Minutes 10)
$identity="${env:USERDOMAIN}\${env:USERNAME}"
$principal=New-ScheduledTaskPrincipal -UserId $identity -LogonType Interactive -RunLevel Limited
if($PSCmdlet.ShouldProcess($TaskName,'Crear o actualizar tarea diaria de monitoreo')){
    Register-ScheduledTask -TaskName $TaskName -Action $action -Trigger $trigger -Settings $settings -Principal $principal -Force | Out-Null
}
Write-Output "MONITORING_TASK=$TaskName"
Write-Output "MONITORING_IDENTITY=$identity"
Write-Output 'MONITORING_PRIVILEGE=LIMITED'
