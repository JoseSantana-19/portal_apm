USE [Talento_Humano];
GO
SET ANSI_NULLS ON;
SET QUOTED_IDENTIFIER ON;
GO

/* Permite corregir únicamente los campos conciliables del respaldo legado.
   La cuenta de la aplicación conserva el principio de mínimo privilegio: se
   concede EXECUTE sobre el procedimiento, no UPDATE directo sobre la tabla. */
CREATE OR ALTER PROCEDURE dbo.sp_th_reconciliar_empleado_rolmaes
    @empleado_id INT,
    @apellidos VARCHAR(100),
    @nombres VARCHAR(100),
    @telefono VARCHAR(20)=NULL,
    @sueldo DECIMAL(10,2)=NULL,
    @num_iess VARCHAR(30)=NULL,
    @codigo_iess VARCHAR(30)=NULL,
    @cod_emplea VARCHAR(20)=NULL
AS
BEGIN
    SET NOCOUNT ON;
    IF NOT EXISTS(SELECT 1 FROM dbo.th_empleados WHERE empleado_id=@empleado_id)
        THROW 51530,'El empleado indicado no existe.',1;
    IF NULLIF(LTRIM(RTRIM(@apellidos)),'') IS NULL OR NULLIF(LTRIM(RTRIM(@nombres)),'') IS NULL
        THROW 51531,'Los apellidos y nombres son obligatorios.',1;

    UPDATE dbo.th_empleados
    SET apellidos=UPPER(LTRIM(RTRIM(@apellidos))),
        nombres=UPPER(LTRIM(RTRIM(@nombres))),
        telefono_movil=NULLIF(LTRIM(RTRIM(@telefono)),''),
        sueldo_rmu=@sueldo,
        num_iess=NULLIF(LTRIM(RTRIM(@num_iess)),''),
        codigo_iess=NULLIF(LTRIM(RTRIM(@codigo_iess)),''),
        cod_emplea=NULLIF(LTRIM(RTRIM(@cod_emplea)),'')
    WHERE empleado_id=@empleado_id;

    SELECT 1 exito;
END;
GO

IF DATABASE_PRINCIPAL_ID('portal_app') IS NOT NULL
    GRANT EXECUTE ON dbo.sp_th_reconciliar_empleado_rolmaes TO portal_app;
GO

PRINT 'Procedimiento de conciliación rolmaes instalado.';
GO
