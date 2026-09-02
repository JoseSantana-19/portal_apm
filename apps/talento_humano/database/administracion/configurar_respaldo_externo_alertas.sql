/*
  Cierre operativo opcional: respaldo cifrado externo y alertas SQL Agent.

  Requisitos previos:
  - ejecutar con sqlcmd y una cuenta sysadmin;
  - certificado APM_BackupEncryptionCert instalado en master y su clave privada
    exportada a una custodia diferente del respaldo;
  - recurso UNC accesible para la cuenta del servicio SQL Server;
  - perfil de Database Mail ya validado.

  Ejemplo:
  sqlcmd -S SERVIDOR -E -b -i configurar_respaldo_externo_alertas.sql ^
    -v ExternalBackupPath="\\servidor\respaldo-apm" ^
       OperatorEmail="infraestructura@institucion.gob.ec" ^
       MailProfile="APM Database Mail"
*/
USE msdb;
SET NOCOUNT ON;
SET XACT_ABORT ON;

DECLARE @externalPath nvarchar(2048)=N'$(ExternalBackupPath)';
DECLARE @operatorEmail nvarchar(320)=N'$(OperatorEmail)';
DECLARE @mailProfile sysname=N'$(MailProfile)';
DECLARE @operator sysname=N'APM Operaciones';

IF LEFT(@externalPath,2)<>N'\\'
    THROW 51000,N'ExternalBackupPath debe ser una ruta UNC.',1;
IF @operatorEmail NOT LIKE N'%_@_%._%'
    THROW 51000,N'OperatorEmail no parece una dirección válida.',1;
IF NOT EXISTS(SELECT 1 FROM master.sys.certificates WHERE name=N'APM_BackupEncryptionCert')
    THROW 51000,N'Falta el certificado master.APM_BackupEncryptionCert para cifrar respaldos.',1;
IF NOT EXISTS(SELECT 1 FROM dbo.sysmail_profile WHERE name=@mailProfile)
    THROW 51000,N'El perfil de Database Mail indicado no existe.',1;

EXEC dbo.sysmail_help_status_sp;
EXEC dbo.sp_set_sqlagent_properties @email_save_in_sent_folder=1, @databasemail_profile=@mailProfile, @use_databasemail=1;

IF EXISTS(SELECT 1 FROM dbo.sysoperators WHERE name=@operator)
    EXEC dbo.sp_update_operator @name=@operator,@enabled=1,@email_address=@operatorEmail;
ELSE
    EXEC dbo.sp_add_operator @name=@operator,@enabled=1,@email_address=@operatorEmail;

DECLARE @job sysname=N'APM - Respaldo externo cifrado diario';
DECLARE @command nvarchar(max)=N'DECLARE @f nvarchar(4000)=N'''+REPLACE(@externalPath,N'''',N'''''')+
    N'\Talento_Humano_EXT_''+CONVERT(char(8),GETDATE(),112)+N''_''+REPLACE(CONVERT(char(8),GETDATE(),108),'':'','''')+N''.bak'';'+
    N'BACKUP DATABASE [Talento_Humano] TO DISK=@f WITH COPY_ONLY,INIT,CHECKSUM,COMPRESSION,'+
    N'ENCRYPTION(ALGORITHM=AES_256,SERVER CERTIFICATE=[APM_BackupEncryptionCert]),STATS=10;'+
    N'RESTORE VERIFYONLY FROM DISK=@f WITH CHECKSUM;';

IF NOT EXISTS(SELECT 1 FROM dbo.sysjobs WHERE name=@job)
    EXEC dbo.sp_add_job @job_name=@job,@enabled=1,@description=N'Respaldo COPY_ONLY cifrado AES-256 a custodia UNC externa.',
        @notify_level_email=2,@notify_email_operator_name=@operator;
ELSE
    EXEC dbo.sp_update_job @job_name=@job,@enabled=1,@notify_level_email=2,@notify_email_operator_name=@operator;

DECLARE @stepId int=(SELECT s.step_id FROM dbo.sysjobsteps s JOIN dbo.sysjobs j ON j.job_id=s.job_id WHERE j.name=@job AND s.step_name=N'Respaldar y verificar');
IF @stepId IS NOT NULL
    EXEC dbo.sp_update_jobstep @job_name=@job,@step_id=@stepId,@command=@command,@database_name=N'master',@on_fail_action=2;
ELSE
    EXEC dbo.sp_add_jobstep @job_name=@job,@step_name=N'Respaldar y verificar',@subsystem=N'TSQL',@command=@command,@database_name=N'master',@on_success_action=1,@on_fail_action=2;

IF NOT EXISTS(SELECT 1 FROM dbo.sysschedules WHERE name=N'APM - Externo diario 0130')
    EXEC dbo.sp_add_schedule @schedule_name=N'APM - Externo diario 0130',@enabled=1,@freq_type=4,@freq_interval=1,@active_start_time=013000;
IF NOT EXISTS(SELECT 1 FROM dbo.sysjobschedules js JOIN dbo.sysjobs j ON j.job_id=js.job_id JOIN dbo.sysschedules s ON s.schedule_id=js.schedule_id WHERE j.name=@job AND s.name=N'APM - Externo diario 0130')
    EXEC dbo.sp_attach_schedule @job_name=@job,@schedule_name=N'APM - Externo diario 0130';
IF NOT EXISTS(SELECT 1 FROM dbo.sysjobservers js JOIN dbo.sysjobs j ON j.job_id=js.job_id WHERE j.name=@job)
    EXEC dbo.sp_add_jobserver @job_name=@job;

DECLARE @alertName sysname,@messageId int;
DECLARE alert_cursor CURSOR LOCAL FAST_FORWARD FOR
    SELECT N'APM - Error SQL '+CONVERT(nvarchar(10),message_id),message_id FROM (VALUES(823),(824),(825)) AS e(message_id);
OPEN alert_cursor;
FETCH NEXT FROM alert_cursor INTO @alertName,@messageId;
WHILE @@FETCH_STATUS=0
BEGIN
    IF NOT EXISTS(SELECT 1 FROM dbo.sysalerts WHERE name=@alertName)
        EXEC dbo.sp_add_alert @name=@alertName,@message_id=@messageId,@enabled=1,@delay_between_responses=300,@include_event_description_in=1;
    IF NOT EXISTS(SELECT 1 FROM dbo.sysnotifications n JOIN dbo.sysalerts a ON a.id=n.alert_id JOIN dbo.sysoperators o ON o.id=n.operator_id WHERE a.name=@alertName AND o.name=@operator)
        EXEC dbo.sp_add_notification @alert_name=@alertName,@operator_name=@operator,@notification_method=1;
    FETCH NEXT FROM alert_cursor INTO @alertName,@messageId;
END
CLOSE alert_cursor;
DEALLOCATE alert_cursor;

DECLARE @existingJob sysname;
DECLARE job_cursor CURSOR LOCAL FAST_FORWARD FOR
    SELECT name FROM dbo.sysjobs WHERE name IN(
        N'APM - Respaldo completo semanal',N'APM - Respaldo diferencial diario',
        N'APM - Respaldo log 15 minutos',N'APM - Integridad semanal',N'APM - Vigencias laborales');
OPEN job_cursor;
FETCH NEXT FROM job_cursor INTO @existingJob;
WHILE @@FETCH_STATUS=0
BEGIN
    EXEC dbo.sp_update_job @job_name=@existingJob,@notify_level_email=2,@notify_email_operator_name=@operator;
    FETCH NEXT FROM job_cursor INTO @existingJob;
END
CLOSE job_cursor;
DEALLOCATE job_cursor;

SELECT N'OK' resultado,@job trabajo,@externalPath destino,@operator operador;
