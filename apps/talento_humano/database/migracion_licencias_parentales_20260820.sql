/* Portal Portuario APM — licencia parental y jornada base/temporal
   Requiere: migracion_vigencias_temporales_20260820.sql
   Idempotente. No modifica la jornada base del expediente. */
SET NOCOUNT ON;
SET XACT_ABORT ON;
GO

IF OBJECT_ID('dbo.th_jornadas_especiales','U') IS NULL
    THROW 52000,'Falta dbo.th_jornadas_especiales. Ejecute primero la migracion de vigencias temporales.',1;
IF OBJECT_ID('dbo.sp_th_aprobar_accion_personal_v3','P') IS NULL
    THROW 52001,'Falta dbo.sp_th_aprobar_accion_personal_v3.',1;
GO

/* Maternidad y paternidad son licencias: su jornada efectiva es cero horas. */
IF OBJECT_ID('dbo.CK_th_jornada_esp_horas','C') IS NOT NULL
    ALTER TABLE dbo.th_jornadas_especiales DROP CONSTRAINT CK_th_jornada_esp_horas;
ALTER TABLE dbo.th_jornadas_especiales WITH CHECK ADD CONSTRAINT CK_th_jornada_esp_horas
CHECK((UPPER(tipo_novedad) IN('MATERNIDAD','PATERNIDAD') AND horas_diarias=0)
   OR (UPPER(tipo_novedad) NOT IN('MATERNIDAD','PATERNIDAD') AND horas_diarias>0 AND horas_diarias<=24));
ALTER TABLE dbo.th_jornadas_especiales CHECK CONSTRAINT CK_th_jornada_esp_horas;
GO

/* El procedimiento v3 ya concentra toda la aprobación atómica. Este parche
   amplía exclusivamente su regla parental para mantener una sola fuente de
   verdad y conservar compatibilidad con las acciones existentes. */
DECLARE @definition NVARCHAR(MAX)=OBJECT_DEFINITION(OBJECT_ID('dbo.sp_th_aprobar_accion_personal_v3'));
IF @definition IS NULL THROW 52002,'No fue posible leer sp_th_aprobar_accion_personal_v3.',1;

IF CHARINDEX('PATERNIDAD',UPPER(@definition))=0
BEGIN
    SET @definition=REPLACE(@definition,
        'UPPER(@novedad)<>''MATERNIDAD''',
        'UPPER(@novedad) NOT IN(''MATERNIDAD'',''PATERNIDAD'')');
    SET @definition=REPLACE(@definition,
        'UPPER(@novedad)=''MATERNIDAD''',
        'UPPER(@novedad) IN(''MATERNIDAD'',''PATERNIDAD'')');
    SET @definition=REPLACE(@definition,
        'maternidad se registra con cero horas',
        'maternidad y paternidad se registran con cero horas');

    DECLARE @procedurePosition INT=CHARINDEX('PROCEDURE',UPPER(@definition));
    IF @procedurePosition=0 THROW 52003,'La definición del procedimiento no contiene PROCEDURE.',1;
    SET @definition='ALTER '+SUBSTRING(@definition,@procedurePosition,LEN(@definition));
    EXEC sys.sp_executesql @definition;
END;
GO

IF CHARINDEX('PATERNIDAD',UPPER(OBJECT_DEFINITION(OBJECT_ID('dbo.sp_th_aprobar_accion_personal_v3'))))=0
    THROW 52004,'No se pudo habilitar paternidad en la aprobación de acciones.',1;
GO

IF OBJECT_ID('dbo.th_schema_migrations','U') IS NOT NULL
AND NOT EXISTS(SELECT 1 FROM dbo.th_schema_migrations WHERE version='2026.08.20.2')
    INSERT dbo.th_schema_migrations(version,nombre_archivo)
    VALUES('2026.08.20.2','migracion_licencias_parentales_20260820.sql');
GO

EXEC dbo.sp_th_registrar_auditoria
    'MIGRACION','Sistema','MIGRACION_LICENCIAS_PARENTALES',
    'Separacion de jornada base y temporal; maternidad y paternidad con cero horas.',
    '127.0.0.1';
GO

PRINT 'Migracion 2026.08.20.2 completada.';
GO
