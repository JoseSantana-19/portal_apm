USE [msdb];
GO
SET NOCOUNT ON;
GO

SELECT d.name base_datos,d.recovery_model_desc,d.state_desc,
       d.page_verify_option_desc,d.is_auto_close_on,d.is_auto_shrink_on
FROM sys.databases d WHERE d.name=N'Talento_Humano';

SELECT j.name,j.enabled,s.name horario,
       CASE h.run_status WHEN 0 THEN 'FALLO' WHEN 1 THEN 'OK' WHEN 2 THEN 'REINTENTO'
            WHEN 3 THEN 'CANCELADO' WHEN 4 THEN 'EN_CURSO' ELSE 'SIN_EJECUCION' END ultimo_estado,
       msdb.dbo.agent_datetime(h.run_date,h.run_time) ultima_ejecucion,
       h.message
FROM dbo.sysjobs j
LEFT JOIN dbo.sysjobschedules js ON js.job_id=j.job_id
LEFT JOIN dbo.sysschedules s ON s.schedule_id=js.schedule_id
OUTER APPLY(
    SELECT TOP 1 run_status,run_date,run_time,message
    FROM dbo.sysjobhistory x WHERE x.job_id=j.job_id AND x.step_id=0
    ORDER BY instance_id DESC
) h
WHERE j.name LIKE N'APM - %'
ORDER BY j.name;

SELECT TOP 30 bs.database_name,bs.type,
       CASE bs.type WHEN 'D' THEN 'FULL' WHEN 'I' THEN 'DIFERENCIAL' WHEN 'L' THEN 'LOG' ELSE bs.type END tipo,
       bs.backup_start_date,bs.backup_finish_date,
       CONVERT(decimal(18,2),bs.backup_size/1048576.0) tamano_mb,
       CONVERT(decimal(18,2),bs.compressed_backup_size/1048576.0) comprimido_mb,
       bs.has_backup_checksums,bmf.physical_device_name
FROM dbo.backupset bs
JOIN dbo.backupmediafamily bmf ON bmf.media_set_id=bs.media_set_id
WHERE bs.database_name=N'Talento_Humano'
ORDER BY bs.backup_finish_date DESC;
GO
