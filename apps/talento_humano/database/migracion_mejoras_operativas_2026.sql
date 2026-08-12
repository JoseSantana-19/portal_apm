USE [Talento_Humano];
GO
SET NOCOUNT ON;
SET XACT_ABORT ON;
SET ANSI_NULLS ON;
SET QUOTED_IDENTIFIER ON;
GO

/* Catalogo normalizado de nacionalidades y relacion N:M con empleados. */
IF OBJECT_ID('dbo.th_nacionalidades','U') IS NULL
BEGIN
    CREATE TABLE dbo.th_nacionalidades(
        nacionalidad_id INT IDENTITY(1,1) NOT NULL CONSTRAINT PK_th_nacionalidades PRIMARY KEY,
        codigo_iso CHAR(2) NOT NULL,
        pais NVARCHAR(100) NOT NULL,
        nombre NVARCHAR(100) NOT NULL,
        aliases NVARCHAR(300) NULL,
        activo BIT NOT NULL CONSTRAINT DF_th_nacionalidades_activo DEFAULT(1),
        fecha_actualizacion DATETIME2(3) NOT NULL CONSTRAINT DF_th_nacionalidades_fecha DEFAULT(SYSDATETIME()),
        CONSTRAINT UX_th_nacionalidades_iso UNIQUE(codigo_iso)
    );
END;
GO

IF OBJECT_ID('dbo.th_empleado_nacionalidades','U') IS NULL
BEGIN
    CREATE TABLE dbo.th_empleado_nacionalidades(
        empleado_id INT NOT NULL,
        nacionalidad_id INT NOT NULL,
        es_principal BIT NOT NULL CONSTRAINT DF_th_emp_nac_principal DEFAULT(0),
        orden TINYINT NOT NULL CONSTRAINT DF_th_emp_nac_orden DEFAULT(1),
        usuario_crea VARCHAR(50) NOT NULL,
        fecha_creacion DATETIME2(3) NOT NULL CONSTRAINT DF_th_emp_nac_fecha DEFAULT(SYSDATETIME()),
        CONSTRAINT PK_th_empleado_nacionalidades PRIMARY KEY(empleado_id,nacionalidad_id),
        CONSTRAINT FK_th_emp_nac_empleado FOREIGN KEY(empleado_id) REFERENCES dbo.th_empleados(empleado_id),
        CONSTRAINT FK_th_emp_nac_nacionalidad FOREIGN KEY(nacionalidad_id) REFERENCES dbo.th_nacionalidades(nacionalidad_id)
    );
END;
GO

MERGE dbo.th_nacionalidades AS t
USING (SELECT 'EC' codigo,N'Ecuador' pais,N'Ecuatoriana' nombre,N'Ecuatoriano Ecuador Ecuatoriana' aliases) s
ON t.codigo_iso=s.codigo
WHEN MATCHED THEN UPDATE SET pais=s.pais,nombre=s.nombre,aliases=s.aliases,activo=1,fecha_actualizacion=SYSDATETIME()
WHEN NOT MATCHED THEN INSERT(codigo_iso,pais,nombre,aliases) VALUES(s.codigo,s.pais,s.nombre,s.aliases);
GO

DECLARE @ec INT=(SELECT nacionalidad_id FROM dbo.th_nacionalidades WHERE codigo_iso='EC');
INSERT dbo.th_empleado_nacionalidades(empleado_id,nacionalidad_id,es_principal,orden,usuario_crea)
SELECT empleado_id,@ec,1,1,'MIGRACION'
FROM dbo.th_empleados e
WHERE UPPER(LTRIM(RTRIM(ISNULL(e.nacionalidad,'')))) IN ('ECUATORIANO','ECUATORIANA')
  AND NOT EXISTS(SELECT 1 FROM dbo.th_empleado_nacionalidades en WHERE en.empleado_id=e.empleado_id AND en.nacionalidad_id=@ec);
UPDATE dbo.th_empleados SET nacionalidad='Ecuatoriana'
WHERE UPPER(LTRIM(RTRIM(ISNULL(nacionalidad,'')))) IN ('ECUATORIANO','ECUATORIANA');
GO

CREATE OR ALTER PROCEDURE dbo.sp_th_consultar_nacionalidades
    @usuario VARCHAR(50),@ip VARCHAR(45)='0.0.0.0'
AS
BEGIN
    SET NOCOUNT ON;
    EXEC dbo.sp_th_registrar_auditoria @usuario,'Nacionalidades','CONSULTAR_CATALOGO','Consulta del catalogo de nacionalidades.',@ip;
    SELECT nacionalidad_id,codigo_iso,pais,nombre,aliases
    FROM dbo.th_nacionalidades WHERE activo=1 ORDER BY nombre,pais;
END;
GO

CREATE OR ALTER PROCEDURE dbo.sp_th_sincronizar_nacionalidades_empleado
    @empleado_id INT,@nacionalidades_json NVARCHAR(MAX),@usuario VARCHAR(50),@ip VARCHAR(45)='0.0.0.0'
AS
BEGIN
    SET NOCOUNT ON; SET XACT_ABORT ON;
    BEGIN TRY
        BEGIN TRAN;
        IF NOT EXISTS(SELECT 1 FROM dbo.th_empleados WHERE empleado_id=@empleado_id)
            THROW 51300,'El empleado indicado no existe.',1;
        IF ISJSON(@nacionalidades_json)<>1 THROW 51301,'La lista de nacionalidades no es valida.',1;
        DECLARE @ids TABLE(id INT PRIMARY KEY,orden INT);
        INSERT @ids(id,orden)
        SELECT CONVERT(INT,[value]),CONVERT(INT,[key])+1 FROM OPENJSON(@nacionalidades_json)
        WHERE TRY_CONVERT(INT,[value]) IS NOT NULL;
        IF EXISTS(SELECT 1 FROM @ids i LEFT JOIN dbo.th_nacionalidades n ON n.nacionalidad_id=i.id AND n.activo=1 WHERE n.nacionalidad_id IS NULL)
            THROW 51302,'Una nacionalidad seleccionada no existe o esta inactiva.',1;
        DELETE FROM dbo.th_empleado_nacionalidades WHERE empleado_id=@empleado_id;
        INSERT dbo.th_empleado_nacionalidades(empleado_id,nacionalidad_id,es_principal,orden,usuario_crea)
        SELECT @empleado_id,id,CASE WHEN orden=1 THEN 1 ELSE 0 END,orden,@usuario FROM @ids;
        UPDATE e SET nacionalidad=(SELECT TOP 1 n.nombre FROM @ids i JOIN dbo.th_nacionalidades n ON n.nacionalidad_id=i.id ORDER BY i.orden)
        FROM dbo.th_empleados e WHERE e.empleado_id=@empleado_id;
        DECLARE @detalle VARCHAR(500)=CONCAT('Actualizo nacionalidades del empleado #',@empleado_id,'. Total=',(SELECT COUNT(*) FROM @ids));
        EXEC dbo.sp_th_registrar_auditoria @usuario,'Directorio de Personal','ACTUALIZAR_NACIONALIDADES',@detalle,@ip;
        COMMIT;
        SELECT 1 exito,'Nacionalidades actualizadas.' mensaje;
    END TRY
    BEGIN CATCH
        IF @@TRANCOUNT>0 ROLLBACK;
        SELECT 0 exito,ERROR_MESSAGE() mensaje;
    END CATCH
END;
GO

/* Expediente completo para el primer formulario de Biblioteca. */
CREATE OR ALTER PROCEDURE dbo.sp_th_obtener_expediente_impresion
    @empleado_id INT,@usuario VARCHAR(50),@ip VARCHAR(45)='0.0.0.0'
AS
BEGIN
    SET NOCOUNT ON;
    EXEC dbo.sp_th_registrar_auditoria @usuario,'Formulario Principal','IMPRIMIR','Consulta de expediente completo para PDF.',@ip;
    SELECT e.*,u.nombre_unidad direccion_area,u.codigo_uorg,p.nombre_puesto cargo,p.codigo_puesto,
           p.remuneracion_unificada rmu_catalogo,padre.nombre_unidad direccion_padre,
           (SELECT STRING_AGG(n.nombre,', ') WITHIN GROUP(ORDER BY en.orden)
            FROM dbo.th_empleado_nacionalidades en JOIN dbo.th_nacionalidades n ON n.nacionalidad_id=en.nacionalidad_id
            WHERE en.empleado_id=e.empleado_id) nacionalidades
    FROM dbo.th_empleados e
    LEFT JOIN dbo.th_unidades_organizacionales u ON u.unidad_id=e.unidad_id
    LEFT JOIN dbo.th_unidades_organizacionales padre ON padre.unidad_id=u.unidad_padre_id
    LEFT JOIN dbo.th_puestos p ON p.puesto_id=e.puesto_id
    WHERE e.empleado_id=@empleado_id;
END;
GO

/* Busqueda compuesta: todos los terminos deben aparecer en cualquier campo. */
IF NOT EXISTS(SELECT 1 FROM sys.indexes WHERE object_id=OBJECT_ID('dbo.th_empleados') AND name='IX_th_empleados_identificacion_busqueda')
    CREATE INDEX IX_th_empleados_identificacion_busqueda ON dbo.th_empleados(identificacion) INCLUDE(apellidos,nombres,unidad_id,puesto_id,estado,tipo_contrato);
GO

CREATE OR ALTER PROCEDURE dbo.sp_th_buscar_personal
    @termino NVARCHAR(200)=NULL,@unidad_id INT=NULL,@contrato NVARCHAR(100)=NULL,@estado INT=NULL,
    @pagina INT=1,@tamano INT=1000,@usuario VARCHAR(50),@ip VARCHAR(45)='0.0.0.0'
AS
BEGIN
    SET NOCOUNT ON;
    SET @pagina=CASE WHEN @pagina<1 THEN 1 ELSE @pagina END;
    SET @tamano=CASE WHEN @tamano<1 THEN 25 WHEN @tamano>1000 THEN 1000 ELSE @tamano END;
    SET @termino=NULLIF(LTRIM(RTRIM(@termino)),'');
    DECLARE @detalle_busqueda VARCHAR(500)=CONCAT('Busqueda compuesta: ',COALESCE(@termino,'(sin termino)'));
    EXEC dbo.sp_th_registrar_auditoria @usuario,'Directorio de Personal','BUSCAR',@detalle_busqueda,@ip;
    ;WITH Base AS(
        SELECT e.empleado_id,e.identificacion,e.apellidos,e.nombres,e.unidad_id,e.puesto_id,e.estado,e.tipo_contrato,
               u.nombre_unidad,p.nombre_puesto,
               CONCAT(e.identificacion,' ',e.apellidos,' ',e.nombres,' ',u.nombre_unidad,' ',p.nombre_puesto,' ',e.tipo_contrato) texto
        FROM dbo.th_empleados e
        LEFT JOIN dbo.th_unidades_organizacionales u ON u.unidad_id=e.unidad_id
        LEFT JOIN dbo.th_puestos p ON p.puesto_id=e.puesto_id
        WHERE (@unidad_id IS NULL OR e.unidad_id=@unidad_id)
          AND (@estado IS NULL OR e.estado=@estado)
          AND (@contrato IS NULL OR e.tipo_contrato COLLATE Modern_Spanish_CI_AI LIKE '%'+@contrato+'%')
          AND (@termino IS NULL OR NOT EXISTS(
              SELECT 1 FROM STRING_SPLIT(@termino,' ') token
              WHERE NULLIF(LTRIM(RTRIM(token.value)),'') IS NOT NULL
                AND CONCAT(e.identificacion,' ',e.apellidos,' ',e.nombres,' ',u.nombre_unidad,' ',p.nombre_puesto,' ',e.tipo_contrato)
                    COLLATE Modern_Spanish_CI_AI NOT LIKE '%'+LTRIM(RTRIM(token.value))+'%'))
    ),Paginada AS(
        SELECT *,COUNT(*) OVER() total_resultados,ROW_NUMBER() OVER(ORDER BY apellidos,nombres,empleado_id) fila FROM Base
    )
    SELECT empleado_id,total_resultados FROM Paginada
    WHERE fila BETWEEN ((@pagina-1)*@tamano)+1 AND @pagina*@tamano ORDER BY fila;
END;
GO

/* Movimiento interno por lote, completamente atomico. */
IF OBJECT_ID('dbo.th_movimientos_lote','U') IS NULL
BEGIN
    CREATE TABLE dbo.th_movimientos_lote(
        lote_id INT IDENTITY(1,1) NOT NULL CONSTRAINT PK_th_movimientos_lote PRIMARY KEY,
        unidad_destino_id INT NOT NULL,
        puesto_destino_id INT NOT NULL,
        fecha_movimiento DATE NOT NULL,
        motivo VARCHAR(500) NOT NULL,
        cantidad INT NOT NULL,
        estado VARCHAR(20) NOT NULL CONSTRAINT DF_th_mov_lote_estado DEFAULT('APLICADO'),
        usuario_crea VARCHAR(50) NOT NULL,
        direccion_ip VARCHAR(45) NOT NULL,
        fecha_creacion DATETIME2(3) NOT NULL CONSTRAINT DF_th_mov_lote_fecha DEFAULT(SYSDATETIME()),
        CONSTRAINT FK_th_mov_lote_unidad FOREIGN KEY(unidad_destino_id) REFERENCES dbo.th_unidades_organizacionales(unidad_id),
        CONSTRAINT FK_th_mov_lote_puesto FOREIGN KEY(puesto_destino_id) REFERENCES dbo.th_puestos(puesto_id)
    );
END;
GO
IF COL_LENGTH('dbo.th_movimientos_personal','lote_id') IS NULL
    ALTER TABLE dbo.th_movimientos_personal ADD lote_id INT NULL;
GO
IF NOT EXISTS(SELECT 1 FROM sys.foreign_keys WHERE name='FK_th_movimientos_personal_lote')
    ALTER TABLE dbo.th_movimientos_personal ADD CONSTRAINT FK_th_movimientos_personal_lote FOREIGN KEY(lote_id) REFERENCES dbo.th_movimientos_lote(lote_id);
GO

CREATE OR ALTER PROCEDURE dbo.sp_th_mover_empleados_lote
    @empleados_json NVARCHAR(MAX),@unidad_destino_id INT,@puesto_destino_id INT,
    @fecha_movimiento DATE,@motivo VARCHAR(500),@usuario VARCHAR(50),@ip VARCHAR(45)='0.0.0.0'
AS
BEGIN
    SET NOCOUNT ON; SET XACT_ABORT ON;
    BEGIN TRY
        BEGIN TRAN;
        IF ISJSON(@empleados_json)<>1 THROW 51400,'La seleccion de empleados no es valida.',1;
        DECLARE @seleccion TABLE(empleado_id INT PRIMARY KEY,unidad_origen_id INT,puesto_origen_id INT);
        INSERT @seleccion(empleado_id)
        SELECT DISTINCT TRY_CONVERT(INT,[value]) FROM OPENJSON(@empleados_json) WHERE TRY_CONVERT(INT,[value]) IS NOT NULL;
        IF (SELECT COUNT(*) FROM @seleccion)<2 THROW 51401,'Seleccione al menos dos empleados para un movimiento grupal.',1;
        IF NOT EXISTS(SELECT 1 FROM dbo.th_unidades_organizacionales WHERE unidad_id=@unidad_destino_id AND activo=1)
            THROW 51402,'La unidad de destino no existe o esta inactiva.',1;
        IF NOT EXISTS(SELECT 1 FROM dbo.th_puestos WHERE puesto_id=@puesto_destino_id AND activo=1)
            THROW 51403,'El cargo de destino no existe o esta inactivo.',1;
        IF NULLIF(LTRIM(RTRIM(@motivo)),'') IS NULL THROW 51404,'El motivo es obligatorio.',1;
        UPDATE s SET unidad_origen_id=e.unidad_id,puesto_origen_id=e.puesto_id
        FROM @seleccion s JOIN dbo.th_empleados e WITH(UPDLOCK,HOLDLOCK) ON e.empleado_id=s.empleado_id AND e.estado=1;
        IF EXISTS(SELECT 1 FROM @seleccion WHERE unidad_origen_id IS NULL OR puesto_origen_id IS NULL)
            THROW 51405,'La seleccion contiene empleados inexistentes, inactivos o sin asignacion.',1;
        IF EXISTS(SELECT 1 FROM @seleccion WHERE unidad_origen_id=@unidad_destino_id AND puesto_origen_id=@puesto_destino_id)
            THROW 51406,'Al menos un empleado ya posee la asignacion de destino.',1;
        IF EXISTS(SELECT 1 FROM @seleccion s JOIN dbo.th_historial_laboral h ON h.empleado_id=s.empleado_id AND h.fecha_hasta IS NULL WHERE @fecha_movimiento<h.fecha_desde)
            THROW 51407,'La fecha efectiva es anterior al inicio de un historial vigente.',1;

        INSERT dbo.th_movimientos_lote(unidad_destino_id,puesto_destino_id,fecha_movimiento,motivo,cantidad,usuario_crea,direccion_ip)
        VALUES(@unidad_destino_id,@puesto_destino_id,@fecha_movimiento,LTRIM(RTRIM(@motivo)),(SELECT COUNT(*) FROM @seleccion),@usuario,@ip);
        DECLARE @lote_id INT=CONVERT(INT,SCOPE_IDENTITY());
        UPDATE h SET fecha_hasta=DATEADD(DAY,-1,@fecha_movimiento)
        FROM dbo.th_historial_laboral h JOIN @seleccion s ON s.empleado_id=h.empleado_id WHERE h.fecha_hasta IS NULL;
        INSERT dbo.th_historial_laboral(empleado_id,puesto_id,unidad_id,fecha_desde,fecha_hasta,observaciones,usuario_crea,fecha_creacion)
        SELECT empleado_id,@puesto_destino_id,@unidad_destino_id,@fecha_movimiento,NULL,
               CONCAT('Movimiento interno grupal #',@lote_id,'. ',LTRIM(RTRIM(@motivo))),@usuario,GETDATE() FROM @seleccion;
        INSERT dbo.th_movimientos_personal(empleado_id,unidad_origen_id,puesto_origen_id,unidad_destino_id,puesto_destino_id,fecha_movimiento,motivo,usuario_crea,direccion_ip,lote_id)
        SELECT empleado_id,unidad_origen_id,puesto_origen_id,@unidad_destino_id,@puesto_destino_id,@fecha_movimiento,LTRIM(RTRIM(@motivo)),@usuario,@ip,@lote_id FROM @seleccion;
        UPDATE e SET unidad_id=@unidad_destino_id,puesto_id=@puesto_destino_id
        FROM dbo.th_empleados e JOIN @seleccion s ON s.empleado_id=e.empleado_id;
        DECLARE @detalle VARCHAR(500)=CONCAT('Movimiento grupal #',@lote_id,'. Empleados=',(SELECT COUNT(*) FROM @seleccion),'; unidad=',@unidad_destino_id,'; puesto=',@puesto_destino_id,'.');
        EXEC dbo.sp_th_registrar_auditoria @usuario,'Movimiento de Personal','MOVER_LOTE',@detalle,@ip;
        COMMIT;
        SELECT 1 exito,@lote_id lote_id,(SELECT COUNT(*) FROM @seleccion) cantidad,'Movimiento grupal aplicado correctamente.' mensaje;
    END TRY
    BEGIN CATCH
        IF @@TRANCOUNT>0 ROLLBACK;
        SELECT 0 exito,0 lote_id,0 cantidad,ERROR_MESSAGE() mensaje;
    END CATCH
END;
GO

EXEC dbo.sp_th_registrar_auditoria 'MIGRACION','Sistema','MIGRACION_OPERATIVA',
    'Catalogo de nacionalidades, busqueda compuesta, impresion completa y movimientos grupales.','127.0.0.1';
GO
PRINT 'migracion_mejoras_operativas_2026 aplicada correctamente';
GO
