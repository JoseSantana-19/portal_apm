param(
    [string]$Migration = 'database\migracion_regimen_laboral_20260829.sql',
    [string]$Server = 'tcp:portal-apm-preprod.local,1433',
    [string]$Database = 'Talento_Humano',
    [string]$CredentialFile = ''
)

$ErrorActionPreference = 'Stop'
$principal = [Security.Principal.WindowsPrincipal]::new([Security.Principal.WindowsIdentity]::GetCurrent())
if (-not $principal.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)) {
    throw 'Esta migracion debe ejecutarse como administrador.'
}

$project = Split-Path $PSScriptRoot -Parent
$databaseRoot = [IO.Path]::GetFullPath((Join-Path $project 'database'))
$migrationPath = [IO.Path]::GetFullPath((Join-Path $project $Migration))
if (-not $migrationPath.StartsWith($databaseRoot + [IO.Path]::DirectorySeparatorChar, [StringComparison]::OrdinalIgnoreCase)) {
    throw 'La migracion debe pertenecer al directorio database del proyecto.'
}
if (-not (Test-Path -LiteralPath $migrationPath -PathType Leaf)) { throw 'No existe el archivo de migracion.' }
if (-not (Get-Command sqlcmd.exe -ErrorAction SilentlyContinue)) { throw 'sqlcmd.exe no esta disponible en PATH.' }

$logDirectory = Join-Path $env:ProgramData 'PortalAPM'
New-Item -ItemType Directory -Path $logDirectory -Force | Out-Null
$logPath = Join-Path $logDirectory ('migration-' + (Get-Date -Format 'yyyyMMdd-HHmmss') + '.log')
$resultPath = Join-Path $logDirectory 'migration-latest.result'
Set-Content -LiteralPath $resultPath -Value 'IN_PROGRESS' -Encoding UTF8
Start-Transcript -LiteralPath $logPath -Force
try {
    $dbaUser = [Environment]::GetEnvironmentVariable('PORTAL_DBA_USER','Process')
    $dbaPassword = [Environment]::GetEnvironmentVariable('PORTAL_DBA_PASSWORD','Process')
    if (-not [string]::IsNullOrWhiteSpace($CredentialFile)) {
        $credentialPath = [IO.Path]::GetFullPath($CredentialFile)
        if (-not (Test-Path -LiteralPath $credentialPath -PathType Leaf)) { throw 'No existe el archivo temporal de credencial.' }
        $credential = Import-Clixml -LiteralPath $credentialPath
        if ($credential -isnot [Management.Automation.PSCredential]) { throw 'La credencial temporal no es valida.' }
        $dbaUser = $credential.UserName
        $dbaPassword = $credential.GetNetworkCredential().Password
    }
    if ([string]::IsNullOrWhiteSpace($dbaUser) -xor [string]::IsNullOrWhiteSpace($dbaPassword)) {
        throw 'PORTAL_DBA_USER y PORTAL_DBA_PASSWORD deben proporcionarse juntos.'
    }
    $auth = if ([string]::IsNullOrWhiteSpace($dbaUser)) { @('-E') } else { @('-U',$dbaUser,'-P',$dbaPassword) }
    $common = @('-S',$Server) + $auth + @('-N','-b')

    $isSysadmin = (& sqlcmd.exe @common -d master -h -1 -W -Q "SET NOCOUNT ON;SELECT IS_SRVROLEMEMBER('sysadmin');" | Out-String).Trim()
    if ($LASTEXITCODE -ne 0 -or $isSysadmin -ne '1') {
        $mode = if ([string]::IsNullOrWhiteSpace($dbaUser)) { 'integrada' } else { 'SQL temporal' }
        throw "La cuenta $mode no tiene privilegios DBA o no pudo autenticarse (exit=$LASTEXITCODE; respuesta=$isSysadmin)."
    }

    $stamp = Get-Date -Format 'yyyyMMdd_HHmmss'
    $migrationSlug = [IO.Path]::GetFileNameWithoutExtension($migrationPath) -replace '[^A-Za-z0-9_-]','_'
    $backupQuery = "DECLARE @d nvarchar(4000)=CONVERT(nvarchar(4000),SERVERPROPERTY('InstanceDefaultBackupPath'));" +
        "IF RIGHT(@d,1) NOT IN ('\','/') SET @d=@d+'\';" +
        "DECLARE @f nvarchar(4000)=@d+'${Database}_pre_${migrationSlug}_${stamp}.bak';" +
        "BACKUP DATABASE [$Database] TO DISK=@f WITH COPY_ONLY,CHECKSUM,COMPRESSION,INIT;" +
        "RESTORE VERIFYONLY FROM DISK=@f WITH CHECKSUM;SELECT @f AS backup_verificado;"
    & sqlcmd.exe @common -d master -r 1 -Q $backupQuery
    if ($LASTEXITCODE -ne 0) { throw 'No se pudo crear y verificar el respaldo previo.' }

    & sqlcmd.exe @common -d $Database -r 1 -f 65001 -i $migrationPath
    if ($LASTEXITCODE -ne 0) { throw 'La migracion termino con errores.' }

    # El hash se calcula fuera del propio SQL para evitar un checksum
    # autorreferencial. También completa migraciones vigentes aplicadas antes de
    # que esta compuerta incorporara el registro automático de SHA-256.
    foreach ($sqlFile in Get-ChildItem -LiteralPath $databaseRoot -File -Filter '*.sql') {
        $fileName = $sqlFile.Name.Replace("'", "''")
        $sha256 = (Get-FileHash -LiteralPath $sqlFile.FullName -Algorithm SHA256).Hash.ToLowerInvariant()
        $checksumQuery = "SET NOCOUNT ON;IF OBJECT_ID('dbo.th_schema_migrations','U') IS NOT NULL UPDATE dbo.th_schema_migrations SET checksum_sha256='$sha256' WHERE nombre_archivo=N'$fileName';"
        & sqlcmd.exe @common -d $Database -r 1 -Q $checksumQuery
        if ($LASTEXITCODE -ne 0) { throw "No se pudo registrar el checksum de $($sqlFile.Name)." }
    }

    $isVigencyMigration = [IO.Path]::GetFileName($migrationPath) -eq 'migracion_vigencias_temporales_20260820.sql'
    $isParentalMigration = [IO.Path]::GetFileName($migrationPath) -eq 'migracion_licencias_parentales_20260820.sql'
    $isTalentOperationMigration = [IO.Path]::GetFileName($migrationPath) -eq 'migracion_operacion_talento_20260825.sql'
    $isSocioGeolocationMigration = [IO.Path]::GetFileName($migrationPath) -eq 'migracion_geolocalizacion_socioeconomica_20260825.sql'
    $isRolePositionMigration = [IO.Path]::GetFileName($migrationPath) -eq 'migracion_roles_por_puesto_20260826.sql'
    $isSignedDocumentMigration = [IO.Path]::GetFileName($migrationPath) -eq 'migracion_expediente_documental_historial_20260827.sql'
    $isAssistantRoleMigration = [IO.Path]::GetFileName($migrationPath) -eq 'migracion_rol_asistente_talento_20260827.sql'
    $isLaborRegimeMigration = [IO.Path]::GetFileName($migrationPath) -eq 'migracion_regimen_laboral_20260829.sql'
    $isPeriodIntegrityMigration = [IO.Path]::GetFileName($migrationPath) -eq 'migracion_integridad_periodos_20260830.sql'
    $verify = if ($isPeriodIntegrityMigration) {
        "SET NOCOUNT ON;" +
        "IF OBJECT_ID('dbo.tr_th_empleados_crear_periodo_inicial','TR') IS NULL THROW 52510,'Falta el trigger de períodos iniciales.',1;" +
        "IF OBJECT_ID('dbo.sp_th_actualizar_borrador_accion_personal','P') IS NULL THROW 52513,'Falta el procedimiento de edición segura.',1;" +
        "IF DATABASE_PRINCIPAL_ID('portal_app_role') IS NOT NULL AND NOT EXISTS(SELECT 1 FROM sys.database_permissions dp JOIN sys.database_principals pr ON pr.principal_id=dp.grantee_principal_id WHERE pr.name='portal_app_role' AND dp.major_id=OBJECT_ID('dbo.sp_th_actualizar_borrador_accion_personal') AND dp.permission_name='EXECUTE' AND dp.state IN('G','W')) THROW 52514,'Falta EXECUTE de edición para portal_app_role.',1;" +
        "IF EXISTS(SELECT 1 FROM dbo.th_empleados e WHERE NOT EXISTS(SELECT 1 FROM dbo.th_periodos_vinculacion p WHERE p.empleado_id=e.empleado_id)) THROW 52511,'Persisten funcionarios sin período.',1;" +
        "IF NOT EXISTS(SELECT 1 FROM dbo.th_schema_migrations WHERE version='2026.08.30.1' AND LEN(checksum_sha256)=64) THROW 52512,'Falta el ledger/checksum 2026.08.30.1.',1;" +
        "SELECT 'MIGRATION_2026_08_30_1=OK';"
    } elseif ($isLaborRegimeMigration) {
        "SET NOCOUNT ON;" +
        "IF COL_LENGTH('dbo.th_empleados','regimen_laboral') IS NULL THROW 52430,'Falta el régimen laboral del empleado.',1;" +
        "IF OBJECT_ID('dbo.th_secuencias_documentos','U') IS NULL THROW 52431,'Falta el maestro de secuencias documentales.',1;" +
        "IF OBJECT_ID('dbo.sp_th_asignar_regimen_empleado','P') IS NULL THROW 52432,'Falta el procedimiento de régimen laboral.',1;" +
        "IF OBJECT_ID('dbo.tr_th_acciones_asignar_serie','TR') IS NULL THROW 52433,'Falta el correlativo documental.',1;" +
        "IF NOT EXISTS(SELECT 1 FROM dbo.th_schema_migrations WHERE version='2026.08.29.1' AND LEN(checksum_sha256)=64) THROW 52434,'Falta el ledger/checksum 2026.08.29.1.',1;" +
        "SELECT 'MIGRATION_2026_08_29_1=OK';"
    } elseif ($isAssistantRoleMigration) {
        "SET NOCOUNT ON;" +
        "DECLARE @r int=(SELECT rol_id FROM dbo.th_roles WHERE nombre_rol=N'Asistente de Talento Humano' AND estado=1);" +
        "IF @r IS NULL THROW 52420,'Falta el rol Asistente de Talento Humano.',1;" +
        "IF NOT EXISTS(SELECT 1 FROM dbo.th_puesto_rol_mapa WHERE rol_id=@r) THROW 52421,'El rol no tiene cargos asociados.',1;" +
        "IF EXISTS(SELECT 1 FROM dbo.th_modulos m LEFT JOIN dbo.th_permisos_rol p ON p.modulo_id=m.modulo_id AND p.rol_id=@r WHERE p.permiso_id IS NULL) THROW 52422,'La matriz RBAC está incompleta.',1;" +
        "IF NOT EXISTS(SELECT 1 FROM dbo.th_schema_migrations WHERE version='2026.08.27.2' AND LEN(checksum_sha256)=64) THROW 52423,'Falta el ledger/checksum 2026.08.27.2.',1;" +
        "SELECT 'MIGRATION_2026_08_27_2=OK';"
    } elseif ($isRolePositionMigration) {
        "SET NOCOUNT ON;" +
        "IF OBJECT_ID('dbo.th_puesto_rol_mapa','U') IS NULL THROW 52390,'Falta el mapa puesto-rol.',1;" +
        "IF OBJECT_ID('dbo.sp_th_rol_sugerido_por_empleado','P') IS NULL THROW 52391,'Falta el SP de rol sugerido.',1;" +
        "IF OBJECT_ID('dbo.sp_th_mapa_roles_puestos','P') IS NULL THROW 52392,'Falta el SP de mapa completo.',1;" +
        "IF NOT EXISTS(SELECT 1 FROM dbo.th_schema_migrations WHERE version='2026.08.26' AND LEN(checksum_sha256)=64) THROW 52393,'Falta el ledger/checksum 2026.08.26.',1;" +
        "CREATE TABLE #mapa(puesto_id int,rol_id int,es_principal bit,nombre_puesto nvarchar(250),nombre_rol nvarchar(150));" +
        "INSERT #mapa EXEC dbo.sp_th_mapa_roles_puestos;" +
        "IF (SELECT COUNT(DISTINCT puesto_id) FROM #mapa)<>(SELECT COUNT(*) FROM dbo.th_puestos WHERE activo=1) THROW 52394,'El mapa no cubre todos los puestos activos.',1;" +
        "SELECT 'MIGRATION_2026_08_26=OK';"
    } elseif ($isSignedDocumentMigration) {
        "SET NOCOUNT ON;" +
        "IF OBJECT_ID('dbo.th_documentos_firmados','U') IS NULL THROW 52400,'Falta el repositorio de documentos firmados.',1;" +
        "IF OBJECT_ID('dbo.vw_th_eventos_laborales','V') IS NULL THROW 52401,'Falta la vista integral de eventos laborales.',1;" +
        "IF OBJECT_ID('dbo.sp_th_registrar_documento_firmado','P') IS NULL THROW 52402,'Falta el alta de documentos firmados.',1;" +
        "IF OBJECT_ID('dbo.sp_th_consultar_eventos_laborales','P') IS NULL THROW 52403,'Falta la consulta integral del historial.',1;" +
        "IF NOT EXISTS(SELECT 1 FROM dbo.th_schema_migrations WHERE version='2026.08.27.1' AND LEN(checksum_sha256)=64) THROW 52404,'Falta el ledger/checksum 2026.08.27.1.',1;" +
        "SELECT 'MIGRATION_2026_08_27_1=OK';"
    } elseif ($isSocioGeolocationMigration) {
        "SET NOCOUNT ON;" +
        "IF COL_LENGTH('dbo.th_estudios_socioeconomicos','latitud') IS NULL THROW 52290,'Falta latitud socioeconomica.',1;" +
        "IF COL_LENGTH('dbo.th_estudios_socioeconomicos','qr_imagen') IS NULL THROW 52291,'Falta QR socioeconomico.',1;" +
        "IF OBJECT_ID('dbo.CK_th_estudio_coordenadas_par','C') IS NULL THROW 52292,'Falta integridad de coordenadas.',1;" +
        "IF NOT EXISTS(SELECT 1 FROM dbo.th_schema_migrations WHERE version='2026.08.25.2') THROW 52293,'Falta el ledger 2026.08.25.2.',1;" +
        "SELECT 'MIGRATION_2026_08_25_2=OK';"
    } elseif ($isTalentOperationMigration) {
        "SET NOCOUNT ON;" +
        "IF OBJECT_ID('dbo.th_periodos_vinculacion','U') IS NULL THROW 52190,'Faltan períodos de vinculación.',1;" +
        "IF OBJECT_ID('dbo.vw_th_vacaciones_acciones','V') IS NULL THROW 52191,'Falta la vista de vacaciones.',1;" +
        "IF OBJECT_ID('dbo.th_paz_salvo','U') IS NULL THROW 52192,'Falta Paz y Salvo.',1;" +
        "IF OBJECT_ID('dbo.tr_th_acciones_asignar_serie','TR') IS NULL THROW 52193,'Falta el correlativo por serie.',1;" +
        "IF NOT EXISTS(SELECT 1 FROM dbo.th_schema_migrations WHERE version='2026.08.25.1') THROW 52194,'Falta el ledger 2026.08.25.1.',1;" +
        "SELECT 'MIGRATION_2026_08_25_1=OK';"
    } elseif ($isParentalMigration) {
        "SET NOCOUNT ON;" +
        "IF OBJECT_ID('dbo.CK_th_jornada_esp_horas','C') IS NULL THROW 52090,'Falta CK_th_jornada_esp_horas.',1;" +
        "IF CHARINDEX('PATERNIDAD',UPPER(OBJECT_DEFINITION(OBJECT_ID('dbo.sp_th_aprobar_accion_personal_v3'))))=0 THROW 52091,'La aprobación no admite paternidad.',1;" +
        "IF NOT EXISTS(SELECT 1 FROM dbo.th_schema_migrations WHERE version='2026.08.20.2') THROW 52092,'Falta el ledger 2026.08.20.2.',1;" +
        "SELECT 'MIGRATION_2026_08_20_2=OK';"
    } elseif ($isVigencyMigration) {
        "SET NOCOUNT ON;" +
        "IF OBJECT_ID('dbo.sp_th_registrar_accion_personal_v3','P') IS NULL THROW 51990,'Falta sp_th_registrar_accion_personal_v3.',1;" +
        "IF OBJECT_ID('dbo.sp_th_aprobar_accion_personal_v3','P') IS NULL THROW 51991,'Falta sp_th_aprobar_accion_personal_v3.',1;" +
        "IF OBJECT_ID('dbo.sp_th_refrescar_vigencias_laborales','P') IS NULL THROW 51992,'Falta sp_th_refrescar_vigencias_laborales.',1;" +
        "IF OBJECT_ID('dbo.th_vigencias_laborales','U') IS NULL THROW 51993,'Falta th_vigencias_laborales.',1;" +
        "IF OBJECT_ID('dbo.vw_th_situacion_laboral_efectiva','V') IS NULL THROW 51994,'Falta vw_th_situacion_laboral_efectiva.',1;" +
        "SELECT 'MIGRATION_2026_08_20_1=OK';"
    } else {
        "SET NOCOUNT ON;" +
        "IF OBJECT_ID('dbo.sp_th_guardar_empleado_v2','P') IS NULL THROW 51990,'Falta sp_th_guardar_empleado_v2.',1;" +
        "IF OBJECT_ID('dbo.sp_th_aprobar_accion_personal_v2','P') IS NULL THROW 51991,'Falta sp_th_aprobar_accion_personal_v2.',1;" +
        "IF OBJECT_ID('dbo.th_borradores_formulario','U') IS NULL THROW 51992,'Falta th_borradores_formulario.',1;" +
        "IF OBJECT_ID('dbo.th_jornadas_especiales','U') IS NULL THROW 51993,'Falta th_jornadas_especiales.',1;" +
        "SELECT 'MIGRATION_2026_08_20=OK';"
    }
    & sqlcmd.exe @common -d $Database -r 1 -Q $verify
    if ($LASTEXITCODE -ne 0) { throw 'La verificacion posterior de objetos fallo.' }
    Set-Content -LiteralPath $resultPath -Value "OK`nLOG=$logPath" -Encoding UTF8
    Write-Output "MIGRATION_LOG=$logPath"
}
catch {
    $message = $_.Exception.Message
    Set-Content -LiteralPath $resultPath -Value "FAIL`nERROR=$message`nLOG=$logPath" -Encoding UTF8
    Write-Error $message
    exit 1
}
finally {
    if (-not [string]::IsNullOrWhiteSpace($CredentialFile)) {
        $credentialPath = [IO.Path]::GetFullPath($CredentialFile)
        if (Test-Path -LiteralPath $credentialPath -PathType Leaf) { Remove-Item -LiteralPath $credentialPath -Force }
    }
    Stop-Transcript
}
