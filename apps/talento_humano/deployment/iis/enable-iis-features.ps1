$ErrorActionPreference = 'Stop'

$principal = New-Object Security.Principal.WindowsPrincipal([Security.Principal.WindowsIdentity]::GetCurrent())
if (-not $principal.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)) {
    throw 'Este script debe ejecutarse como administrador.'
}

$features = @(
    'IIS-WebServerRole',
    'IIS-WebServer',
    'IIS-CommonHttpFeatures',
    'IIS-DefaultDocument',
    'IIS-StaticContent',
    'IIS-HttpErrors',
    'IIS-HttpRedirect',
    'IIS-ApplicationDevelopment',
    'IIS-CGI',
    'IIS-ISAPIExtensions',
    'IIS-ISAPIFilter',
    'IIS-HealthAndDiagnostics',
    'IIS-HttpLogging',
    'IIS-Security',
    'IIS-RequestFiltering',
    'IIS-Performance',
    'IIS-HttpCompressionStatic',
    'IIS-WebServerManagementTools',
    'IIS-ManagementConsole',
    'WAS-WindowsActivationService',
    'WAS-ProcessModel',
    'WAS-ConfigurationAPI'
)

$result = Enable-WindowsOptionalFeature -Online -FeatureName $features -All -NoRestart
Write-Output "RESTART_NEEDED=$($result.RestartNeeded)"
foreach ($name in $features) {
    $feature = Get-WindowsOptionalFeature -Online -FeatureName $name
    Write-Output "FEATURE=$name;STATE=$($feature.State)"
}
