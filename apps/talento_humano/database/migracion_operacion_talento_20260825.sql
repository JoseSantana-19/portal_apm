/* Portal Portuario APM — operación integral de Talento Humano
   Versión 2026.08.25.1
   - Series documentales por tipo de Acción de Personal.
   - Períodos de vinculación y antigüedad acumulada con reingresos.
   - Vacaciones derivadas exclusivamente de acciones aprobadas.
   - Hitos de servicio y estadísticas de género.
   - Flujo auditable de Paz y Salvo para personal saliente.
   Idempotente. No renumera documentos históricos. */
USE [Talento_Humano];
GO
SET NOCOUNT ON;
SET XACT_ABORT ON;
SET ANSI_NULLS ON;
SET QUOTED_IDENTIFIER ON;
GO

IF OBJECT_ID('dbo.th_catalogo_series_accion','U') IS NULL
BEGIN
    CREATE TABLE dbo.th_catalogo_series_accion(
        catalogo_serie_id INT IDENTITY(1,1) NOT NULL CONSTRAINT PK_th_catalogo_series_accion PRIMARY KEY,
        tipo_accion NVARCHAR(100) NOT NULL,
        serie VARCHAR(8) NOT NULL,
        descripcion NVARCHAR(180) NOT NULL,
        activo BIT NOT NULL CONSTRAINT DF_th_catalogo_series_activo DEFAULT(1),
        fecha_creacion DATETIME2(0) NOT NULL CONSTRAINT DF_th_catalogo_series_fecha DEFAULT(SYSDATETIME()),
        CONSTRAINT UQ_th_catalogo_series_tipo UNIQUE(tipo_accion),
        CONSTRAINT CK_th_catalogo_series_codigo CHECK(serie NOT LIKE '%[^A-Z]%')
    );
END;
GO

MERGE dbo.th_catalogo_series_accion AS destino
USING (VALUES
    (N'INGRESO','MP',N'Movimiento de personal'),(N'REINGRESO','MP',N'Movimiento de personal'),
    (N'RESTITUCIÓN','MP',N'Movimiento de personal'),(N'REINTEGRO','MP',N'Movimiento de personal'),
    (N'ASCENSO','MP',N'Movimiento de personal'),(N'TRASLADO','MP',N'Movimiento de personal'),
    (N'TRASPASO','MP',N'Movimiento de personal'),(N'INTERCAMBIO VOLUNTARIO','MP',N'Movimiento de personal'),
    (N'COMISIÓN DE SERVICIOS','MP',N'Movimiento de personal'),(N'SUBROGACIÓN','MP',N'Movimiento de personal'),
    (N'ENCARGO','MP',N'Movimiento de personal'),(N'CESACIÓN DE FUNCIONES','MP',N'Movimiento de personal'),
    (N'DESTITUCIÓN','MP',N'Movimiento de personal'),(N'OTRO (DETALLAR)','MP',N'Movimiento de personal'),
    (N'CAMBIO ADMINISTRATIVO','CA',N'Cambio administrativo'),
    (N'LICENCIA','LI',N'Licencia'),
    (N'SANCIONES','RD',N'Régimen disciplinario'),
    (N'VACACIONES','VAC',N'Vacaciones')
) AS fuente(tipo_accion,serie,descripcion)
ON destino.tipo_accion COLLATE Modern_Spanish_CI_AI=fuente.tipo_accion COLLATE Modern_Spanish_CI_AI
WHEN MATCHED THEN UPDATE SET serie=fuente.serie,descripcion=fuente.descripcion,activo=1
WHEN NOT MATCHED THEN INSERT(tipo_accion,serie,descripcion) VALUES(fuente.tipo_accion,fuente.serie,fuente.descripcion);
GO

IF OBJECT_ID('dbo.th_contadores_series_accion','U') IS NULL
BEGIN
    CREATE TABLE dbo.th_contadores_series_accion(
        serie VARCHAR(8) NOT NULL,
        anio SMALLINT NOT NULL,
        ultimo_numero INT NOT NULL CONSTRAINT DF_th_contadores_ultimo DEFAULT(0),
        fecha_actualizacion DATETIME2(0) NOT NULL CONSTRAINT DF_th_contadores_fecha DEFAULT(SYSDATETIME()),
        CONSTRAINT PK_th_contadores_series_accion PRIMARY KEY(serie,anio),
        CONSTRAINT CK_th_contadores_anio CHECK(anio BETWEEN 2000 AND 9999),
        CONSTRAINT CK_th_contadores_numero CHECK(ultimo_numero>=0)
    );
END;
GO

;WITH existentes AS(
 SELECT LEFT(numero_accion,CHARINDEX('-',numero_accion)-1) serie,
        TRY_CONVERT(INT,REVERSE(SUBSTRING(REVERSE(numero_accion),1,CHARINDEX('-',REVERSE(numero_accion))-1))) anio,
        TRY_CONVERT(INT,SUBSTRING(numero_accion,CHARINDEX('-',numero_accion)+1,LEN(numero_accion)-CHARINDEX('-',numero_accion)-CHARINDEX('-',REVERSE(numero_accion)))) numero
 FROM dbo.th_acciones_personal
 WHERE numero_accion LIKE '[A-Z]%-[0-9]%-[0-9][0-9][0-9][0-9]'
), maximos AS(SELECT serie,anio,MAX(numero) ultimo FROM existentes WHERE anio IS NOT NULL AND numero IS NOT NULL GROUP BY serie,anio)
MERGE dbo.th_contadores_series_accion d USING maximos s ON s.serie=d.serie AND s.anio=d.anio
WHEN MATCHED AND s.ultimo>d.ultimo_numero THEN UPDATE SET ultimo_numero=s.ultimo,fecha_actualizacion=SYSDATETIME()
WHEN NOT MATCHED THEN INSERT(serie,anio,ultimo_numero) VALUES(s.serie,s.anio,s.ultimo);
GO

CREATE OR ALTER FUNCTION dbo.fn_th_serie_accion(@tipo_accion NVARCHAR(100))
RETURNS VARCHAR(8)
AS
BEGIN
    DECLARE @serie VARCHAR(8);
    SELECT @serie=serie FROM dbo.th_catalogo_series_accion
    WHERE activo=1 AND tipo_accion COLLATE Modern_Spanish_CI_AI=LTRIM(RTRIM(@tipo_accion)) COLLATE Modern_Spanish_CI_AI;
    RETURN COALESCE(@serie,'MP');
END;
GO

/* El procedimiento vigente conserva su bloqueo general. Este trigger reemplaza
   el correlativo provisional dentro de la misma transacción, antes del COMMIT. */
CREATE OR ALTER TRIGGER dbo.tr_th_acciones_asignar_serie
ON dbo.th_acciones_personal
AFTER INSERT
AS
BEGIN
    SET NOCOUNT ON;
    DECLARE @accion_id INT,@tipo NVARCHAR(100),@fecha DATE,@serie VARCHAR(8),@anio SMALLINT,@numero INT,@lock INT,@recurso NVARCHAR(255);
    DECLARE acciones CURSOR LOCAL FAST_FORWARD FOR
        SELECT accion_id,tipo_accion,COALESCE(fecha_elaboracion,dbo.fn_th_fecha_institucional()) FROM inserted ORDER BY accion_id;
    OPEN acciones;
    FETCH NEXT FROM acciones INTO @accion_id,@tipo,@fecha;
    WHILE @@FETCH_STATUS=0
    BEGIN
        SET @serie=dbo.fn_th_serie_accion(@tipo);SET @anio=YEAR(@fecha);
        SET @recurso=CONCAT('th_serie_accion_',@serie,'_',@anio);
        EXEC @lock=sys.sp_getapplock @Resource=@recurso,@LockMode='Exclusive',@LockOwner='Transaction',@LockTimeout=10000;
        IF @lock<0 THROW 52100,'No fue posible reservar la serie documental.',1;
        UPDATE dbo.th_contadores_series_accion WITH(UPDLOCK,HOLDLOCK)
           SET ultimo_numero=ultimo_numero+1,fecha_actualizacion=SYSDATETIME(),@numero=ultimo_numero+1
         WHERE serie=@serie AND anio=@anio;
        IF @@ROWCOUNT=0
        BEGIN
            SET @numero=1;
            INSERT dbo.th_contadores_series_accion(serie,anio,ultimo_numero) VALUES(@serie,@anio,@numero);
        END;
        UPDATE dbo.th_acciones_personal
           SET numero_accion=CONCAT(@serie,'-',RIGHT(CONCAT('000',@numero),CASE WHEN @numero<1000 THEN 3 ELSE LEN(CONVERT(VARCHAR(12),@numero)) END),'-',@anio)
         WHERE accion_id=@accion_id;
        FETCH NEXT FROM acciones INTO @accion_id,@tipo,@fecha;
    END;
    CLOSE acciones;DEALLOCATE acciones;
END;
GO

IF OBJECT_ID('dbo.th_periodos_vinculacion','U') IS NULL
BEGIN
    CREATE TABLE dbo.th_periodos_vinculacion(
        periodo_id INT IDENTITY(1,1) NOT NULL CONSTRAINT PK_th_periodos_vinculacion PRIMARY KEY,
        empleado_id INT NOT NULL,
        fecha_desde DATE NOT NULL,
        fecha_hasta DATE NULL,
        tipo_ingreso NVARCHAR(60) NOT NULL CONSTRAINT DF_th_periodos_tipo DEFAULT(N'INGRESO INICIAL'),
        accion_inicio_id INT NULL,
        accion_fin_id INT NULL,
        motivo_salida NVARCHAR(300) NULL,
        usuario_crea VARCHAR(50) NOT NULL,
        fecha_creacion DATETIME2(0) NOT NULL CONSTRAINT DF_th_periodos_fecha DEFAULT(SYSDATETIME()),
        CONSTRAINT FK_th_periodos_empleado FOREIGN KEY(empleado_id) REFERENCES dbo.th_empleados(empleado_id),
        CONSTRAINT FK_th_periodos_accion_inicio FOREIGN KEY(accion_inicio_id) REFERENCES dbo.th_acciones_personal(accion_id),
        CONSTRAINT FK_th_periodos_accion_fin FOREIGN KEY(accion_fin_id) REFERENCES dbo.th_acciones_personal(accion_id),
        CONSTRAINT CK_th_periodos_fechas CHECK(fecha_hasta IS NULL OR fecha_hasta>=fecha_desde)
    );
    CREATE INDEX IX_th_periodos_empleado_fecha ON dbo.th_periodos_vinculacion(empleado_id,fecha_desde,fecha_hasta);
    CREATE UNIQUE INDEX UX_th_periodos_abierto ON dbo.th_periodos_vinculacion(empleado_id) WHERE fecha_hasta IS NULL;
END;
GO

INSERT dbo.th_periodos_vinculacion(empleado_id,fecha_desde,fecha_hasta,tipo_ingreso,motivo_salida,usuario_crea)
SELECT e.empleado_id,base.fecha_desde,
       CASE WHEN e.estado=0 THEN CASE WHEN e.fecha_salida>=base.fecha_desde THEN e.fecha_salida ELSE base.fecha_desde END END,
       N'INGRESO INICIAL',CASE WHEN e.estado=0 THEN N'Período histórico conciliado desde el expediente.' END,'MIGRACION'
FROM dbo.th_empleados e
CROSS APPLY(SELECT COALESCE(e.fecha_ingreso,CONVERT(date,e.fecha_creacion),CONVERT(date,'19000101')) fecha_desde) base
WHERE NOT EXISTS(SELECT 1 FROM dbo.th_periodos_vinculacion p WHERE p.empleado_id=e.empleado_id);
GO

/* Alta/baja aprobada abre o cierra episodios. Es idempotente y forma parte de
   la misma transacción que aprueba la Acción de Personal. */
CREATE OR ALTER TRIGGER dbo.tr_th_acciones_sincronizar_periodos
ON dbo.th_acciones_personal
AFTER UPDATE
AS
BEGIN
    SET NOCOUNT ON;
    DECLARE @id INT,@empleado INT,@tipo NVARCHAR(100),@fecha DATE,@usuario VARCHAR(50);
    DECLARE cambios CURSOR LOCAL FAST_FORWARD FOR
      SELECT i.accion_id,i.empleado_id,i.tipo_accion,i.fecha_rige_desde,COALESCE(i.usuario_aprueba,i.usuario_crea)
      FROM inserted i JOIN deleted d ON d.accion_id=i.accion_id
      WHERE UPPER(i.estado_documento)='APROBADO' AND UPPER(ISNULL(d.estado_documento,''))<>'APROBADO';
    OPEN cambios;FETCH NEXT FROM cambios INTO @id,@empleado,@tipo,@fecha,@usuario;
    WHILE @@FETCH_STATUS=0
    BEGIN
      DECLARE @n NVARCHAR(100)=UPPER(LTRIM(RTRIM(@tipo))) COLLATE Modern_Spanish_CI_AI;
      IF @n IN(N'CESACION DE FUNCIONES',N'DESTITUCION')
        UPDATE dbo.th_periodos_vinculacion
           SET fecha_hasta=CASE WHEN @fecha>fecha_desde THEN DATEADD(DAY,-1,@fecha) ELSE fecha_desde END,
               accion_fin_id=@id,motivo_salida=@tipo
         WHERE empleado_id=@empleado AND fecha_hasta IS NULL;
      ELSE IF @n IN(N'INGRESO',N'REINGRESO',N'RESTITUCION',N'REINTEGRO')
      BEGIN
        IF EXISTS(SELECT 1 FROM dbo.th_periodos_vinculacion WHERE empleado_id=@empleado AND fecha_hasta IS NULL)
            UPDATE dbo.th_periodos_vinculacion SET fecha_hasta=CASE WHEN @fecha>fecha_desde THEN DATEADD(DAY,-1,@fecha) ELSE fecha_desde END,
                motivo_salida=N'Cierre técnico previo al reingreso',accion_fin_id=@id WHERE empleado_id=@empleado AND fecha_hasta IS NULL;
        IF NOT EXISTS(SELECT 1 FROM dbo.th_periodos_vinculacion WHERE empleado_id=@empleado AND accion_inicio_id=@id)
            INSERT dbo.th_periodos_vinculacion(empleado_id,fecha_desde,tipo_ingreso,accion_inicio_id,usuario_crea)
            VALUES(@empleado,@fecha,@tipo,@id,@usuario);
      END;
      FETCH NEXT FROM cambios INTO @id,@empleado,@tipo,@fecha,@usuario;
    END;
    CLOSE cambios;DEALLOCATE cambios;
END;
GO

CREATE OR ALTER VIEW dbo.vw_th_antiguedad_empleados
AS
SELECT e.empleado_id,e.identificacion,e.apellidos,e.nombres,e.estado,
       MIN(p.fecha_desde) primera_fecha_ingreso,
       SUM(CASE WHEN p.fecha_desde>dbo.fn_th_fecha_institucional() THEN 0 ELSE DATEDIFF(DAY,p.fecha_desde,
           CASE WHEN p.fecha_hasta IS NULL OR p.fecha_hasta>dbo.fn_th_fecha_institucional() THEN dbo.fn_th_fecha_institucional() ELSE p.fecha_hasta END)+1 END) dias_servicio,
       CAST(SUM(CASE WHEN p.fecha_desde>dbo.fn_th_fecha_institucional() THEN 0 ELSE DATEDIFF(DAY,p.fecha_desde,
           CASE WHEN p.fecha_hasta IS NULL OR p.fecha_hasta>dbo.fn_th_fecha_institucional() THEN dbo.fn_th_fecha_institucional() ELSE p.fecha_hasta END)+1 END)/365.2425 AS DECIMAL(10,2)) anios_servicio,
       COUNT_BIG(*) periodos_vinculacion
FROM dbo.th_empleados e JOIN dbo.th_periodos_vinculacion p ON p.empleado_id=e.empleado_id
GROUP BY e.empleado_id,e.identificacion,e.apellidos,e.nombres,e.estado;
GO

CREATE OR ALTER VIEW dbo.vw_th_hitos_servicio
AS
WITH periodos AS(
 SELECT p.*,DATEDIFF(DAY,p.fecha_desde,COALESCE(p.fecha_hasta,dbo.fn_th_fecha_institucional()))+1 dias_periodo,
        COALESCE(SUM(DATEDIFF(DAY,p.fecha_desde,COALESCE(p.fecha_hasta,dbo.fn_th_fecha_institucional()))+1)
          OVER(PARTITION BY p.empleado_id ORDER BY p.fecha_desde,p.periodo_id ROWS BETWEEN UNBOUNDED PRECEDING AND 1 PRECEDING),0) dias_previos
 FROM dbo.th_periodos_vinculacion p
), hitos AS(
 SELECT * FROM (VALUES(5),(10),(15),(20),(25),(30)) h(anios)
)
SELECT e.empleado_id,e.identificacion,e.apellidos,e.nombres,e.estado,h.anios hito_anios,
       DATEADD(DAY,CONVERT(INT,ROUND(h.anios*365.2425,0))-p.dias_previos-1,p.fecha_desde) fecha_hito,
       u.nombre_unidad area,pue.nombre_puesto cargo
FROM periodos p JOIN dbo.th_empleados e ON e.empleado_id=p.empleado_id
CROSS JOIN hitos h
LEFT JOIN dbo.th_unidades_organizacionales u ON u.unidad_id=e.unidad_id
LEFT JOIN dbo.th_puestos pue ON pue.puesto_id=e.puesto_id
WHERE CONVERT(INT,ROUND(h.anios*365.2425,0))>p.dias_previos
  AND CONVERT(INT,ROUND(h.anios*365.2425,0))<=p.dias_previos+p.dias_periodo;
GO

CREATE OR ALTER VIEW dbo.vw_th_vacaciones_acciones
AS
SELECT a.accion_id,a.numero_accion,a.empleado_id,e.identificacion,e.apellidos,e.nombres,
       u.nombre_unidad area,p.nombre_puesto cargo,a.fecha_rige_desde fecha_inicio,a.fecha_rige_hasta fecha_fin,
       CASE WHEN a.fecha_rige_hasta IS NULL THEN NULL ELSE DATEDIFF(DAY,a.fecha_rige_desde,a.fecha_rige_hasta)+1 END dias_calendario,
       CASE WHEN dbo.fn_th_fecha_institucional()<a.fecha_rige_desde THEN 'PROGRAMADA'
            WHEN a.fecha_rige_hasta IS NULL OR dbo.fn_th_fecha_institucional()<=a.fecha_rige_hasta THEN 'VIGENTE' ELSE 'FINALIZADA' END estado_vacacion,
       a.explicacion_legal,a.fecha_aprobacion,a.usuario_aprueba
FROM dbo.th_acciones_personal a JOIN dbo.th_empleados e ON e.empleado_id=a.empleado_id
LEFT JOIN dbo.th_unidades_organizacionales u ON u.unidad_id=e.unidad_id
LEFT JOIN dbo.th_puestos p ON p.puesto_id=e.puesto_id
WHERE UPPER(LTRIM(RTRIM(a.tipo_accion))) COLLATE Modern_Spanish_CI_AI=N'VACACIONES'
  AND UPPER(a.estado_documento)='APROBADO';
GO

CREATE OR ALTER VIEW dbo.vw_th_estadisticas_genero
AS
SELECT CASE WHEN UPPER(LTRIM(RTRIM(ISNULL(sexo,'')))) IN('M','MASCULINO','HOMBRE') THEN N'Masculino'
            WHEN UPPER(LTRIM(RTRIM(ISNULL(sexo,'')))) IN('F','FEMENINO','MUJER') THEN N'Femenino'
            ELSE N'No registrado' END genero,
       COUNT_BIG(*) total,SUM(CASE WHEN estado=1 THEN 1 ELSE 0 END) activos
FROM dbo.th_empleados
GROUP BY CASE WHEN UPPER(LTRIM(RTRIM(ISNULL(sexo,'')))) IN('M','MASCULINO','HOMBRE') THEN N'Masculino'
              WHEN UPPER(LTRIM(RTRIM(ISNULL(sexo,'')))) IN('F','FEMENINO','MUJER') THEN N'Femenino' ELSE N'No registrado' END;
GO

IF OBJECT_ID('dbo.th_reconocimientos_servicio','U') IS NULL
BEGIN
 CREATE TABLE dbo.th_reconocimientos_servicio(
  reconocimiento_id INT IDENTITY(1,1) NOT NULL CONSTRAINT PK_th_reconocimientos PRIMARY KEY,
  empleado_id INT NOT NULL,hito_anios TINYINT NOT NULL,fecha_hito DATE NOT NULL,estado VARCHAR(15) NOT NULL CONSTRAINT DF_th_reconocimiento_estado DEFAULT('PENDIENTE'),
  fecha_entrega DATE NULL,observaciones NVARCHAR(300) NULL,usuario_actualiza VARCHAR(50) NULL,fecha_actualizacion DATETIME2(0) NULL,
  CONSTRAINT FK_th_reconocimiento_empleado FOREIGN KEY(empleado_id) REFERENCES dbo.th_empleados(empleado_id),
  CONSTRAINT UQ_th_reconocimiento_hito UNIQUE(empleado_id,hito_anios,fecha_hito),
  CONSTRAINT CK_th_reconocimiento_estado CHECK(estado IN('PENDIENTE','ENTREGADO'))
 );
END;
GO

IF OBJECT_ID('dbo.th_paz_salvo','U') IS NULL
BEGIN
 CREATE TABLE dbo.th_paz_salvo(
  paz_salvo_id INT IDENTITY(1,1) NOT NULL CONSTRAINT PK_th_paz_salvo PRIMARY KEY,
  numero_documento VARCHAR(30) NOT NULL CONSTRAINT UQ_th_paz_salvo_numero UNIQUE,
  empleado_id INT NOT NULL,accion_salida_id INT NOT NULL,fecha_emision DATE NOT NULL,fecha_salida DATE NOT NULL,lugar NVARCHAR(100) NOT NULL CONSTRAINT DF_th_paz_salvo_lugar DEFAULT(N'Manta'),
  estado VARCHAR(15) NOT NULL CONSTRAINT DF_th_paz_salvo_estado DEFAULT('BORRADOR'),observaciones_generales NVARCHAR(1000) NULL,
  usuario_crea VARCHAR(50) NOT NULL,fecha_creacion DATETIME2(0) NOT NULL CONSTRAINT DF_th_paz_salvo_fecha DEFAULT(SYSDATETIME()),usuario_actualiza VARCHAR(50) NULL,fecha_actualizacion DATETIME2(0) NULL,
  CONSTRAINT FK_th_paz_salvo_empleado FOREIGN KEY(empleado_id) REFERENCES dbo.th_empleados(empleado_id),
  CONSTRAINT FK_th_paz_salvo_accion FOREIGN KEY(accion_salida_id) REFERENCES dbo.th_acciones_personal(accion_id),
  CONSTRAINT UQ_th_paz_salvo_accion UNIQUE(accion_salida_id),
  CONSTRAINT CK_th_paz_salvo_estado CHECK(estado IN('BORRADOR','EN_REVISION','OBSERVADO','PARCIAL','COMPLETO','CERRADO'))
 );
END;
GO

IF OBJECT_ID('dbo.th_paz_salvo_secciones','U') IS NULL
BEGIN
 CREATE TABLE dbo.th_paz_salvo_secciones(
  seccion_id INT IDENTITY(1,1) NOT NULL CONSTRAINT PK_th_paz_salvo_secciones PRIMARY KEY,paz_salvo_id INT NOT NULL,
  codigo_seccion VARCHAR(20) NOT NULL,estado VARCHAR(15) NOT NULL CONSTRAINT DF_th_paz_seccion_estado DEFAULT('PENDIENTE'),datos_json NVARCHAR(MAX) NOT NULL CONSTRAINT DF_th_paz_seccion_json DEFAULT(N'{}'),
  observaciones NVARCHAR(1000) NULL,responsable_nombre NVARCHAR(150) NULL,responsable_puesto NVARCHAR(150) NULL,sumilla NVARCHAR(100) NULL,
  fecha_revision DATETIME2(0) NULL,usuario_actualiza VARCHAR(50) NULL,fecha_actualizacion DATETIME2(0) NOT NULL CONSTRAINT DF_th_paz_seccion_fecha DEFAULT(SYSDATETIME()),
  CONSTRAINT FK_th_paz_seccion_documento FOREIGN KEY(paz_salvo_id) REFERENCES dbo.th_paz_salvo(paz_salvo_id),
  CONSTRAINT UQ_th_paz_seccion_codigo UNIQUE(paz_salvo_id,codigo_seccion),
  CONSTRAINT CK_th_paz_seccion_codigo CHECK(codigo_seccion IN('JEFE_INMEDIATO','TALENTO_HUMANO','FINANCIERO','ADMINISTRATIVO','TIC')),
  CONSTRAINT CK_th_paz_seccion_estado CHECK(estado IN('PENDIENTE','CONFORME','OBSERVADO')),
  CONSTRAINT CK_th_paz_seccion_json CHECK(ISJSON(datos_json)=1)
 );
END;
GO

CREATE OR ALTER PROCEDURE dbo.sp_th_crear_paz_salvo
 @empleado_id INT,@accion_salida_id INT,@fecha_emision DATE,@fecha_salida DATE,@lugar NVARCHAR(100),@observaciones NVARCHAR(1000),@usuario VARCHAR(50),@ip VARCHAR(45)
AS
BEGIN
 SET NOCOUNT ON;SET XACT_ABORT ON;
 BEGIN TRY
  BEGIN TRAN;
  IF NOT EXISTS(SELECT 1 FROM dbo.th_acciones_personal WHERE accion_id=@accion_salida_id AND empleado_id=@empleado_id AND UPPER(estado_documento)='APROBADO'
      AND UPPER(tipo_accion) COLLATE Modern_Spanish_CI_AI IN(N'CESACION DE FUNCIONES',N'DESTITUCION')) THROW 52110,'Seleccione una acción de salida aprobada del funcionario.',1;
  IF EXISTS(SELECT 1 FROM dbo.th_paz_salvo WHERE accion_salida_id=@accion_salida_id) THROW 52111,'La acción de salida ya tiene un Paz y Salvo.',1;
  DECLARE @anio SMALLINT=YEAR(@fecha_emision),@lock INT,@siguiente INT,@prefijo VARCHAR(20),@recurso NVARCHAR(255);
  SET @prefijo=CONCAT('PS-',@anio,'-');
  SET @recurso=CONCAT('th_paz_salvo_',@anio);
  EXEC @lock=sys.sp_getapplock @Resource=@recurso,@LockMode='Exclusive',@LockOwner='Transaction',@LockTimeout=10000;
  IF @lock<0 THROW 52112,'No fue posible reservar el número de Paz y Salvo.',1;
  SELECT @siguiente=COALESCE(MAX(TRY_CONVERT(INT,SUBSTRING(numero_documento,LEN(@prefijo)+1,12))),0)+1 FROM dbo.th_paz_salvo WITH(UPDLOCK,HOLDLOCK) WHERE numero_documento LIKE @prefijo+'%';
  DECLARE @numero VARCHAR(30)=CONCAT(@prefijo,RIGHT(CONCAT('0000',@siguiente),4));
  INSERT dbo.th_paz_salvo(numero_documento,empleado_id,accion_salida_id,fecha_emision,fecha_salida,lugar,observaciones_generales,usuario_crea)
  VALUES(@numero,@empleado_id,@accion_salida_id,@fecha_emision,@fecha_salida,COALESCE(NULLIF(@lugar,''),N'Manta'),NULLIF(@observaciones,''),@usuario);
  DECLARE @id INT=CONVERT(INT,SCOPE_IDENTITY());
  INSERT dbo.th_paz_salvo_secciones(paz_salvo_id,codigo_seccion,datos_json)
  SELECT @id,codigo,N'{}' FROM (VALUES('JEFE_INMEDIATO'),('TALENTO_HUMANO'),('FINANCIERO'),('ADMINISTRATIVO'),('TIC')) s(codigo);
  DECLARE @detalle_auditoria NVARCHAR(500)=CONCAT(N'Creó ',@numero,N' para empleado #',@empleado_id,N'.');
  EXEC dbo.sp_th_registrar_auditoria @usuario,'Paz y Salvo','CREAR',@detalle_auditoria,@ip;
  COMMIT;SELECT 1 exito,@id paz_salvo_id,@numero numero_documento,'Paz y Salvo creado en borrador.' mensaje;
 END TRY BEGIN CATCH IF @@TRANCOUNT>0 ROLLBACK;SELECT 0 exito,0 paz_salvo_id,CAST(NULL AS VARCHAR(30)) numero_documento,ERROR_MESSAGE() mensaje;END CATCH
END;
GO

CREATE OR ALTER PROCEDURE dbo.sp_th_guardar_seccion_paz_salvo
 @paz_salvo_id INT,@codigo_seccion VARCHAR(20),@estado VARCHAR(15),@datos_json NVARCHAR(MAX),@observaciones NVARCHAR(1000),
 @responsable_nombre NVARCHAR(150),@responsable_puesto NVARCHAR(150),@sumilla NVARCHAR(100),@usuario VARCHAR(50),@ip VARCHAR(45)
AS
BEGIN
 SET NOCOUNT ON;SET XACT_ABORT ON;
 BEGIN TRY
  BEGIN TRAN;
  SET @codigo_seccion=UPPER(LTRIM(RTRIM(@codigo_seccion)));SET @estado=UPPER(LTRIM(RTRIM(@estado)));
  IF ISJSON(@datos_json)<>1 THROW 52120,'Los datos de la sección no son JSON válido.',1;
  IF @estado NOT IN('PENDIENTE','CONFORME','OBSERVADO') THROW 52121,'Estado de sección no válido.',1;
  UPDATE dbo.th_paz_salvo_secciones SET estado=@estado,datos_json=@datos_json,observaciones=NULLIF(@observaciones,''),responsable_nombre=NULLIF(@responsable_nombre,''),
   responsable_puesto=NULLIF(@responsable_puesto,''),sumilla=NULLIF(@sumilla,''),fecha_revision=CASE WHEN @estado='PENDIENTE' THEN NULL ELSE SYSDATETIME() END,
   usuario_actualiza=@usuario,fecha_actualizacion=SYSDATETIME() WHERE paz_salvo_id=@paz_salvo_id AND codigo_seccion=@codigo_seccion;
  IF @@ROWCOUNT=0 THROW 52122,'No existe la sección solicitada.',1;
  DECLARE @total INT,@conformes INT,@observados INT;SELECT @total=COUNT(*),@conformes=SUM(IIF(estado='CONFORME',1,0)),@observados=SUM(IIF(estado='OBSERVADO',1,0)) FROM dbo.th_paz_salvo_secciones WHERE paz_salvo_id=@paz_salvo_id;
  UPDATE dbo.th_paz_salvo SET estado=CASE WHEN @conformes=@total THEN 'COMPLETO' WHEN @observados>0 THEN 'OBSERVADO' WHEN @conformes>0 THEN 'PARCIAL' ELSE 'EN_REVISION' END,
    usuario_actualiza=@usuario,fecha_actualizacion=SYSDATETIME() WHERE paz_salvo_id=@paz_salvo_id AND estado<>'CERRADO';
  DECLARE @detalle_auditoria NVARCHAR(500)=CONCAT(N'Actualizó ',@codigo_seccion,N' del Paz y Salvo #',@paz_salvo_id,N' como ',@estado,N'.');
  EXEC dbo.sp_th_registrar_auditoria @usuario,'Paz y Salvo','ACTUALIZAR_SECCION',@detalle_auditoria,@ip;
  COMMIT;SELECT 1 exito,'Sección guardada y estado general actualizado.' mensaje;
 END TRY BEGIN CATCH IF @@TRANCOUNT>0 ROLLBACK;SELECT 0 exito,ERROR_MESSAGE() mensaje;END CATCH
END;
GO

CREATE OR ALTER PROCEDURE dbo.sp_th_cerrar_paz_salvo @paz_salvo_id INT,@usuario VARCHAR(50),@ip VARCHAR(45)
AS
BEGIN
 SET NOCOUNT ON;SET XACT_ABORT ON;
 BEGIN TRY
  BEGIN TRAN;
  IF NOT EXISTS(SELECT 1 FROM dbo.th_paz_salvo WHERE paz_salvo_id=@paz_salvo_id AND estado='COMPLETO') THROW 52130,'Solo se puede cerrar un Paz y Salvo con todas sus secciones conformes.',1;
  UPDATE dbo.th_paz_salvo SET estado='CERRADO',usuario_actualiza=@usuario,fecha_actualizacion=SYSDATETIME() WHERE paz_salvo_id=@paz_salvo_id;
  DECLARE @detalle_auditoria NVARCHAR(500)=CONCAT(N'Cerró Paz y Salvo #',@paz_salvo_id,N'.');
  EXEC dbo.sp_th_registrar_auditoria @usuario,'Paz y Salvo','CERRAR',@detalle_auditoria,@ip;
  COMMIT;SELECT 1 exito,'Paz y Salvo cerrado.' mensaje;
 END TRY BEGIN CATCH IF @@TRANCOUNT>0 ROLLBACK;SELECT 0 exito,ERROR_MESSAGE() mensaje;END CATCH
END;
GO

IF NOT EXISTS(SELECT 1 FROM dbo.th_modulos WHERE codigo_modulo='paz_salvo')
 INSERT dbo.th_modulos(nombre_modulo,ruta_frontend,codigo_modulo) VALUES(N'Paz y Salvo',N'talento-humano/paz-salvo','paz_salvo');
IF NOT EXISTS(SELECT 1 FROM dbo.th_modulos WHERE codigo_modulo='vacaciones')
 INSERT dbo.th_modulos(nombre_modulo,ruta_frontend,codigo_modulo) VALUES(N'Vacaciones',N'talento-humano/vacaciones','vacaciones');
GO
MERGE dbo.th_permisos_rol AS d
USING (SELECT r.rol_id,m.modulo_id,
  CAST(IIF(r.rol_id IN(1,2,3),1,0) AS BIT) ver,CAST(IIF(r.rol_id IN(1,2,3),1,0) AS BIT) crear,
  CAST(IIF(r.rol_id IN(1,2,3),1,0) AS BIT) editar,CAST(IIF(r.rol_id=1,1,0) AS BIT) eliminar
 FROM dbo.th_roles r CROSS JOIN dbo.th_modulos m WHERE r.estado=1 AND m.codigo_modulo IN('paz_salvo','vacaciones')) s
ON d.rol_id=s.rol_id AND d.modulo_id=s.modulo_id
WHEN NOT MATCHED THEN INSERT(rol_id,modulo_id,puede_visualizar,puede_crear,puede_editar,puede_eliminar) VALUES(s.rol_id,s.modulo_id,s.ver,s.crear,s.editar,s.eliminar);
GO

IF OBJECT_ID('dbo.th_schema_migrations','U') IS NOT NULL
AND NOT EXISTS(SELECT 1 FROM dbo.th_schema_migrations WHERE version='2026.08.25.1')
 INSERT dbo.th_schema_migrations(version,nombre_archivo) VALUES('2026.08.25.1','migracion_operacion_talento_20260825.sql');
GO
EXEC dbo.sp_th_registrar_auditoria 'MIGRACION','Sistema','MIGRACION_OPERACION_TALENTO_20260825','Series, períodos, vacaciones, hitos, estadísticas y Paz y Salvo instalados.','127.0.0.1';
GO
PRINT 'Migración 2026.08.25.1 completada.';
GO
