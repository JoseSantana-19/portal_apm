USE [msdb];
GO
SET NOCOUNT ON;
GO

DECLARE @jobId UNIQUEIDENTIFIER;

IF NOT EXISTS(SELECT 1 FROM dbo.sysjobs WHERE name=N'APM - Respaldo completo semanal')
BEGIN
    EXEC dbo.sp_add_job @job_name=N'APM - Respaldo completo semanal',@enabled=1,@description=N'Respaldo FULL semanal con CHECKSUM y compresión.',@owner_login_name=N'sa',@job_id=@jobId OUTPUT;
    EXEC dbo.sp_add_jobstep @job_id=@jobId,@step_name=N'Backup FULL',@subsystem=N'TSQL',@database_name=N'master',@command=N'DECLARE @d nvarchar(4000)=CONVERT(nvarchar(4000),SERVERPROPERTY(''InstanceDefaultBackupPath''));DECLARE @f nvarchar(4000)=@d+N''\Talento_Humano_FULL_''+CONVERT(char(8),GETDATE(),112)+N''.bak'';BACKUP DATABASE Talento_Humano TO DISK=@f WITH INIT,CHECKSUM,COMPRESSION,STATS=10;RESTORE VERIFYONLY FROM DISK=@f WITH CHECKSUM;';
    EXEC dbo.sp_add_schedule @schedule_name=N'APM - Domingo 02h00',@freq_type=8,@freq_interval=1,@freq_recurrence_factor=1,@active_start_time=020000;
    EXEC dbo.sp_attach_schedule @job_id=@jobId,@schedule_name=N'APM - Domingo 02h00';
    EXEC dbo.sp_add_jobserver @job_id=@jobId;
END;

IF NOT EXISTS(SELECT 1 FROM dbo.sysjobs WHERE name=N'APM - Respaldo diferencial diario')
BEGIN
    SET @jobId=NULL;
    EXEC dbo.sp_add_job @job_name=N'APM - Respaldo diferencial diario',@enabled=1,@description=N'Respaldo diferencial de lunes a sábado con CHECKSUM.',@owner_login_name=N'sa',@job_id=@jobId OUTPUT;
    EXEC dbo.sp_add_jobstep @job_id=@jobId,@step_name=N'Backup diferencial',@subsystem=N'TSQL',@database_name=N'master',@command=N'DECLARE @d nvarchar(4000)=CONVERT(nvarchar(4000),SERVERPROPERTY(''InstanceDefaultBackupPath''));DECLARE @f nvarchar(4000)=@d+N''\Talento_Humano_DIFF_''+CONVERT(char(8),GETDATE(),112)+N''.bak'';BACKUP DATABASE Talento_Humano TO DISK=@f WITH DIFFERENTIAL,INIT,CHECKSUM,COMPRESSION,STATS=10;RESTORE VERIFYONLY FROM DISK=@f WITH CHECKSUM;';
    EXEC dbo.sp_add_schedule @schedule_name=N'APM - Lunes a sabado 02h00',@freq_type=8,@freq_interval=126,@freq_recurrence_factor=1,@active_start_time=020000;
    EXEC dbo.sp_attach_schedule @job_id=@jobId,@schedule_name=N'APM - Lunes a sabado 02h00';
    EXEC dbo.sp_add_jobserver @job_id=@jobId;
END;

IF NOT EXISTS(SELECT 1 FROM dbo.sysjobs WHERE name=N'APM - Respaldo log 15 minutos')
BEGIN
    SET @jobId=NULL;
    EXEC dbo.sp_add_job @job_name=N'APM - Respaldo log 15 minutos',@enabled=1,@description=N'Respaldo del log de transacciones cada 15 minutos.',@owner_login_name=N'sa',@job_id=@jobId OUTPUT;
    EXEC dbo.sp_add_jobstep @job_id=@jobId,@step_name=N'Backup LOG',@subsystem=N'TSQL',@database_name=N'master',@command=N'DECLARE @d nvarchar(4000)=CONVERT(nvarchar(4000),SERVERPROPERTY(''InstanceDefaultBackupPath''));DECLARE @f nvarchar(4000)=@d+N''\Talento_Humano_LOG_''+CONVERT(char(8),GETDATE(),112)+N''_''+REPLACE(CONVERT(char(8),GETDATE(),108),'':'','''')+N''.trn'';BACKUP LOG Talento_Humano TO DISK=@f WITH CHECKSUM,COMPRESSION,STATS=5;';
    EXEC dbo.sp_add_schedule @schedule_name=N'APM - Cada 15 minutos',@freq_type=4,@freq_interval=1,@freq_subday_type=4,@freq_subday_interval=15,@active_start_time=000000,@active_end_time=235959;
    EXEC dbo.sp_attach_schedule @job_id=@jobId,@schedule_name=N'APM - Cada 15 minutos';
    EXEC dbo.sp_add_jobserver @job_id=@jobId;
END;

IF NOT EXISTS(SELECT 1 FROM dbo.sysjobs WHERE name=N'APM - Integridad semanal')
BEGIN
    SET @jobId=NULL;
    EXEC dbo.sp_add_job @job_name=N'APM - Integridad semanal',@enabled=1,@description=N'DBCC CHECKDB semanal sin reparación automática.',@owner_login_name=N'sa',@job_id=@jobId OUTPUT;
    EXEC dbo.sp_add_jobstep @job_id=@jobId,@step_name=N'CHECKDB',@subsystem=N'TSQL',@database_name=N'master',@command=N'DBCC CHECKDB (Talento_Humano) WITH NO_INFOMSGS,ALL_ERRORMSGS;';
    EXEC dbo.sp_add_schedule @schedule_name=N'APM - Domingo 04h00',@freq_type=8,@freq_interval=1,@freq_recurrence_factor=1,@active_start_time=040000;
    EXEC dbo.sp_attach_schedule @job_id=@jobId,@schedule_name=N'APM - Domingo 04h00';
    EXEC dbo.sp_add_jobserver @job_id=@jobId;
END;

-- Mantener actualizados también los pasos cuando los trabajos ya existían.
EXEC dbo.sp_update_jobstep @job_name=N'APM - Respaldo completo semanal',@step_id=1,@command=N'DECLARE @d nvarchar(4000)=CONVERT(nvarchar(4000),SERVERPROPERTY(''InstanceDefaultBackupPath''));DECLARE @f nvarchar(4000)=@d+N''\Talento_Humano_FULL_''+CONVERT(char(8),GETDATE(),112)+N''.bak'';BACKUP DATABASE Talento_Humano TO DISK=@f WITH INIT,CHECKSUM,COMPRESSION,STATS=10;RESTORE VERIFYONLY FROM DISK=@f WITH CHECKSUM;';
EXEC dbo.sp_update_jobstep @job_name=N'APM - Respaldo diferencial diario',@step_id=1,@command=N'DECLARE @d nvarchar(4000)=CONVERT(nvarchar(4000),SERVERPROPERTY(''InstanceDefaultBackupPath''));DECLARE @f nvarchar(4000)=@d+N''\Talento_Humano_DIFF_''+CONVERT(char(8),GETDATE(),112)+N''.bak'';BACKUP DATABASE Talento_Humano TO DISK=@f WITH DIFFERENTIAL,INIT,CHECKSUM,COMPRESSION,STATS=10;RESTORE VERIFYONLY FROM DISK=@f WITH CHECKSUM;';
EXEC dbo.sp_update_jobstep @job_name=N'APM - Respaldo log 15 minutos',@step_id=1,@command=N'DECLARE @d nvarchar(4000)=CONVERT(nvarchar(4000),SERVERPROPERTY(''InstanceDefaultBackupPath''));DECLARE @f nvarchar(4000)=@d+N''\Talento_Humano_LOG_''+CONVERT(char(8),GETDATE(),112)+N''_''+REPLACE(CONVERT(char(8),GETDATE(),108),'':'','''')+N''.trn'';BACKUP LOG Talento_Humano TO DISK=@f WITH CHECKSUM,COMPRESSION,STATS=5;';
GO

SELECT j.name,j.enabled,s.name horario
FROM dbo.sysjobs j
LEFT JOIN dbo.sysjobschedules js ON js.job_id=j.job_id
LEFT JOIN dbo.sysschedules s ON s.schedule_id=js.schedule_id
WHERE j.name LIKE N'APM - %'
ORDER BY j.name;
GO
