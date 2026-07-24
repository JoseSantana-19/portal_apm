-- vistas_sps_restantes.sql
USE [Talento_Humano];
GO

-- Vista vw_th_reporte_historial_jerarquico
CREATE OR ALTER VIEW [dbo].[vw_th_reporte_historial_jerarquico] AS
SELECT
    e.empleado_id,
    e.identificacion AS cedula,
    e.apellidos + ' ' + e.nombres AS funcionario,
    p.codigo_puesto,
    p.nombre_puesto,
    u.nombre_unidad AS departamento_historico,
    CASE WHEN u_padre.unidad_id IS NOT NULL THEN u_padre.nombre_unidad ELSE u.nombre_unidad END AS direccion_padre,
    CASE WHEN u_padre.unidad_id IS NOT NULL THEN u.nombre_unidad ELSE NULL END AS sub_area,
    ISNULL(u_nueva.nombre_unidad, u.nombre_unidad) AS direccion_actual_unificada,
    ISNULL(u_nueva.tipo_proceso,  u.tipo_proceso)  AS tipo_proceso,
    h.fecha_desde,
    h.fecha_hasta,
    DATEDIFF(year, h.fecha_desde, ISNULL(h.fecha_hasta, GETDATE())) AS anios_permanencia,
    DATEDIFF(day, CAST(GETDATE() AS DATE),
        DATEFROMPARTS(
            YEAR(GETDATE()) + CASE
                WHEN DATEFROMPARTS(YEAR(GETDATE()), MONTH(e.fecha_nacimiento), DAY(e.fecha_nacimiento)) < CAST(GETDATE() AS DATE)
                THEN 1 ELSE 0 END,
            MONTH(e.fecha_nacimiento), DAY(e.fecha_nacimiento)
        )
    ) AS dias_para_cumpleanos
FROM [dbo].[th_historial_laboral] h
JOIN  [dbo].[th_empleados]                 e      ON h.empleado_id = e.empleado_id
JOIN  [dbo].[th_puestos]                   p      ON h.puesto_id   = p.puesto_id
JOIN  [dbo].[th_unidades_organizacionales] u      ON h.unidad_id   = u.unidad_id
LEFT JOIN [dbo].[th_unidades_organizacionales] u_padre ON u.unidad_padre_id = u_padre.unidad_id
LEFT JOIN [dbo].[th_unidades_organizacionales] u_nueva ON u.sucedido_por_id = u_nueva.unidad_id;
GO

-- Vista vw_th_acciones_resumen
CREATE OR ALTER VIEW [dbo].[vw_th_acciones_resumen] AS
SELECT
    ap.accion_id,
    ap.numero_accion,
    ap.fecha_elaboracion,
    ap.tipo_accion,
    ap.estado_documento,
    ap.fecha_rige_desde,
    ap.fecha_rige_hasta,
    e.identificacion               AS cedula_pasaporte,
    e.apellidos + ' ' + e.nombres AS apellidos_nombres,
    p_act.nombre_puesto            AS actual_puesto,
    ap.actual_remuneracion,
    p_prop.nombre_puesto           AS propuesta_puesto,
    ap.propuesta_remuneracion,
    (ISNULL(ap.propuesta_remuneracion, 0) - ISNULL(ap.actual_remuneracion, 0)) AS diferencia_remuneracion,
    ap.usuario_crea,
    ap.fecha_elaboracion           AS fecha_creacion
FROM [dbo].[th_acciones_personal] ap
JOIN  [dbo].[th_empleados]  e        ON e.empleado_id      = ap.empleado_id
LEFT JOIN [dbo].[th_puestos] p_act   ON ap.actual_puesto_id   = p_act.puesto_id
LEFT JOIN [dbo].[th_puestos] p_prop  ON ap.propuesta_puesto_id = p_prop.puesto_id;
GO

-- SP sp_th_guardar_empleado
CREATE OR ALTER PROCEDURE [dbo].[sp_th_guardar_empleado]
    @cedula             VARCHAR(20),
    @nombres            VARCHAR(100),
    @fecha_nac          DATE         = NULL,
    @condicion          VARCHAR(50)  = 'Ninguna',
    @tipo_disc          VARCHAR(80)  = NULL,
    @porcentaje_disc    DECIMAL(5,2) = NULL,
    @sexo               CHAR(1)      = NULL,
    @estado_civil       VARCHAR(30)  = NULL,
    @nacionalidad       VARCHAR(50)  = NULL,
    @tipo_sangre        VARCHAR(10)  = NULL,
    @depto              INT          = NULL,
    @puesto             INT          = NULL,
    @tipo_contrato      VARCHAR(100) = NULL,
    @fecha_ing          DATE         = NULL,
    @sueldo             DECIMAL(10,2)= NULL,
    @jornada            VARCHAR(30)  = 'Completa',
    @correo             VARCHAR(100) = NULL,
    @celular            VARCHAR(20)  = NULL,
    @convencional       VARCHAR(20)  = NULL,
    @ciudad             VARCHAR(50)  = NULL,
    @direccion          VARCHAR(MAX) = NULL,
    @contacto_emerg     VARCHAR(150) = NULL,
    @parentesco         VARCHAR(50)  = NULL,
    @tel_emerg          VARCHAR(20)  = NULL,
    @nivel_estudio      VARCHAR(80)  = NULL,
    @titulo             VARCHAR(150) = NULL,
    @iess               VARCHAR(30)  = NULL,
    @foto               VARCHAR(300) = 'public/img/default_avatar.png',
    @obs                VARCHAR(MAX) = NULL
AS
BEGIN
    SET NOCOUNT ON;
    BEGIN TRY
        INSERT INTO [dbo].[th_empleados] (
            identificacion, apellidos, nombres, fecha_nacimiento,
            sexo, estado_civil, nacionalidad,
            unidad_id, puesto_id, fecha_ingreso, sueldo_rmu,
            correo_institucional, telefono_movil, telefono_convencional,
            ciudad_residencia, direccion_domiciliaria,
            codigo_iess, ruta_foto, observaciones,
            tipo_contrato, jornada, condicion_especial,
            tipo_discapacidad, porcentaje_discapacidad, tipo_sangre,
            contacto_emergencia, emergencia_relacion, tel_emergencia,
            nivel_estudio, titulo, estado, cargas_familiares, fecha_creacion
        ) VALUES (
            @cedula, @nombres, '', @fecha_nac,
            @sexo, @estado_civil, @nacionalidad,
            @depto, @puesto, @fecha_ing, @sueldo,
            ISNULL(@correo,''), ISNULL(@celular,''), @convencional,
            ISNULL(@ciudad,''), ISNULL(@direccion,''),
            @iess, ISNULL(@foto,'public/img/default_avatar.png'), @obs,
            @tipo_contrato, @jornada, @condicion,
            @tipo_disc, @porcentaje_disc, @tipo_sangre,
            @contacto_emerg, @parentesco, @tel_emerg,
            @nivel_estudio, @titulo, 1, 0, GETDATE()
        );
        SELECT SCOPE_IDENTITY() AS nuevo_id, 1 AS exito, 'Empleado guardado correctamente.' AS mensaje;
    END TRY
    BEGIN CATCH
        SELECT 0 AS nuevo_id, 0 AS exito, ERROR_MESSAGE() AS mensaje;
    END CATCH;
END;
GO

-- SP sp_th_modificar_empleado
CREATE OR ALTER PROCEDURE [dbo].[sp_th_modificar_empleado]
    @id                 INT,
    @cedula             VARCHAR(20),
    @nombres            VARCHAR(100),
    @fecha_nac          DATE         = NULL,
    @condicion          VARCHAR(50)  = 'Ninguna',
    @tipo_disc          VARCHAR(80)  = NULL,
    @porcentaje_disc    DECIMAL(5,2) = NULL,
    @sexo               CHAR(1)      = NULL,
    @estado_civil       VARCHAR(30)  = NULL,
    @nacionalidad       VARCHAR(50)  = NULL,
    @tipo_sangre        VARCHAR(10)  = NULL,
    @depto              INT          = NULL,
    @puesto             INT          = NULL,
    @tipo_contrato      VARCHAR(100) = NULL,
    @fecha_ing          DATE         = NULL,
    @sueldo             DECIMAL(10,2)= NULL,
    @jornada            VARCHAR(30)  = 'Completa',
    @correo             VARCHAR(100) = NULL,
    @celular            VARCHAR(20)  = NULL,
    @convencional       VARCHAR(20)  = NULL,
    @ciudad             VARCHAR(50)  = NULL,
    @direccion          VARCHAR(MAX) = NULL,
    @contacto_emerg     VARCHAR(150) = NULL,
    @parentesco         VARCHAR(50)  = NULL,
    @tel_emerg          VARCHAR(20)  = NULL,
    @nivel_estudio      VARCHAR(80)  = NULL,
    @titulo             VARCHAR(150) = NULL,
    @iess               VARCHAR(30)  = NULL,
    @foto               VARCHAR(300) = NULL,
    @obs                VARCHAR(MAX) = NULL
AS
BEGIN
    SET NOCOUNT ON;
    BEGIN TRY
        UPDATE [dbo].[th_empleados] SET
            identificacion          = @cedula,
            fecha_nacimiento        = @fecha_nac,
            sexo                    = @sexo,
            estado_civil            = @estado_civil,
            nacionalidad            = @nacionalidad,
            unidad_id               = @depto,
            puesto_id               = @puesto,
            fecha_ingreso           = @fecha_ing,
            sueldo_rmu              = @sueldo,
            correo_institucional    = ISNULL(@correo, correo_institucional),
            telefono_movil          = ISNULL(@celular, telefono_movil),
            telefono_convencional   = @convencional,
            ciudad_residencia       = ISNULL(@ciudad, ciudad_residencia),
            direccion_domiciliaria  = ISNULL(@direccion, direccion_domiciliaria),
            codigo_iess             = @iess,
            ruta_foto               = ISNULL(@foto, ruta_foto),
            observaciones           = @obs,
            tipo_contrato           = @tipo_contrato,
            jornada                 = @jornada,
            condicion_especial      = @condicion,
            tipo_discapacidad       = @tipo_disc,
            porcentaje_discapacidad = @porcentaje_disc,
            tipo_sangre             = @tipo_sangre,
            contacto_emergencia     = @contacto_emerg,
            emergencia_relacion     = @parentesco,
            tel_emergencia          = @tel_emerg,
            nivel_estudio           = @nivel_estudio,
            titulo                  = @titulo
        WHERE empleado_id = @id;
        SELECT @@ROWCOUNT AS filas_afectadas, 1 AS exito, 'Empleado actualizado.' AS mensaje;
    END TRY
    BEGIN CATCH
        SELECT 0 AS filas_afectadas, 0 AS exito, ERROR_MESSAGE() AS mensaje;
    END CATCH;
END;
GO

-- SP sp_th_eliminar_empleado
CREATE OR ALTER PROCEDURE [dbo].[sp_th_eliminar_empleado]
    @id INT
AS
BEGIN
    SET NOCOUNT ON;
    BEGIN TRY
        UPDATE [dbo].[th_empleados] SET estado = 0 WHERE empleado_id = @id;
        SELECT @@ROWCOUNT AS filas_afectadas, 1 AS exito, 'Empleado dado de baja.' AS mensaje;
    END TRY
    BEGIN CATCH
        SELECT 0 AS filas_afectadas, 0 AS exito, ERROR_MESSAGE() AS mensaje;
    END CATCH;
END;
GO
