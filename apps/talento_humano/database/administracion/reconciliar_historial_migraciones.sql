USE [Talento_Humano];
GO
SET NOCOUNT ON;
SET XACT_ABORT ON;
GO

IF OBJECT_ID('dbo.th_schema_migrations','U') IS NULL
    THROW 52000,'No existe dbo.th_schema_migrations. Aplique primero migracion_cierre_produccion_20260806.sql.',1;

/*
  Este script no vuelve a ejecutar migraciones. Solo registra el historial
  anterior a th_schema_migrations después de comprobar objetos distintivos de
  cada entrega. Si falta una firma se detiene sin escribir ningún registro.
*/
DECLARE @firmas TABLE(
    version VARCHAR(30) NOT NULL,
    nombre_archivo VARCHAR(180) NOT NULL,
    checksum_sha256 CHAR(64) NOT NULL,
    firma_valida BIT NOT NULL
);

INSERT @firmas VALUES
('2026.07.01','migracion_critica_2026.sql','3fd68630bbf2f0a3bb89ebbeed4a4a52e97e02bce152133600e09e706196ff78',
 IIF(OBJECT_ID('dbo.sp_th_guardar_empleado','P') IS NOT NULL AND OBJECT_ID('dbo.th_movimientos_personal','U') IS NOT NULL,1,0)),
('2026.07.02','migracion_formatos_oficiales_2026.sql','cf8180b1544156f07964e60b63211f2481f19d230a821e49d4a5de7b0c528a7f',
 IIF(OBJECT_ID('dbo.th_estudios_socioeconomicos','U') IS NOT NULL AND OBJECT_ID('dbo.sp_th_consultar_estudios_socioeconomicos','P') IS NOT NULL,1,0)),
('2026.07.03','migracion_culminacion_critica_2026.sql','d2c808364a10d8a1f52460f2d1eb6ba326cd4b7c9035555c7ac749064ad3a208',
 IIF(OBJECT_ID('dbo.th_permisos_rol','U') IS NOT NULL AND OBJECT_ID('dbo.tr_th_logs_auditoria_append_only','TR') IS NOT NULL,1,0)),
('2026.07.04','migracion_mejoras_operativas_2026.sql','0f4ab5219d98429f01cca3eda45241e7d2e04badce0350c8480025f2c393f1a1',
 IIF(OBJECT_ID('dbo.th_nacionalidades','U') IS NOT NULL AND OBJECT_ID('dbo.sp_th_mover_empleados_lote','P') IS NOT NULL,1,0)),
('2026.07.05','migracion_calidad_busqueda_2026.sql','0a701b5d84b869410796a2e88a70d4b47e95eab58b1f448746883daac05efa90',
 IIF(EXISTS(SELECT 1 FROM sys.indexes WHERE object_id=OBJECT_ID('dbo.th_unidades_organizacionales') AND name='UX_th_unidades_nombre_activo'),1,0)),
('2026.07.06','patch_reconciliacion_rolmaes_2026.sql','acba7bf70aa9ad1406c416d37ce2d2712910167d413d6eb47dfd238bd4cc5231',
 IIF(OBJECT_ID('dbo.sp_th_reconciliar_empleado_rolmaes','P') IS NOT NULL,1,0)),
('2026.07.07','migracion_ciclo_laboral_2026.sql','8d6d22f332c83d9d3638d5252df4b1a75430018e901b7b51b129870e54700096',
 IIF(OBJECT_ID('dbo.sp_th_cambiar_estado_empleado','P') IS NOT NULL AND OBJECT_ID('dbo.sp_th_aprobar_accion_personal','P') IS NOT NULL,1,0)),
('2026.08.06','migracion_cierre_produccion_20260806.sql','c460b6527a4f0ebc5e76900bb74eac65b29fb78606115d0cb58f58aff1e4a183',
 IIF(EXISTS(SELECT 1 FROM sys.indexes WHERE object_id=OBJECT_ID('dbo.th_logs_auditoria') AND name='IX_th_logs_fecha_log'),1,0)),
('2026.08.10','migracion_seguridad_auditoria_20260810.sql','3de1b81932b83ecf289b659ed82d3f86f7d81f46017597d44ecab5c6d9938111',
 IIF(OBJECT_ID('dbo.vw_th_resumen_auditoria_usuarios','V') IS NOT NULL AND OBJECT_ID('dbo.sp_th_crear_usuario_sistema','P') IS NOT NULL,1,0)),
('2026.08.13','migracion_integridad_rbac_20260813.sql','ddacc69bf8ea93333a3e7f0745e8c37d6a7cfd839753056e41cf964b387aad8c',
 IIF(EXISTS(SELECT 1 FROM sys.indexes WHERE object_id=OBJECT_ID('dbo.th_modulos') AND name='UX_th_modulos_codigo' AND is_unique=1),1,0));

IF EXISTS(SELECT 1 FROM @firmas WHERE firma_valida=0)
BEGIN
    SELECT version,nombre_archivo FROM @firmas WHERE firma_valida=0;
    THROW 52001,'Faltan objetos requeridos; no se reconciliara el historial.',1;
END;

IF EXISTS(
    SELECT 1 FROM @firmas f JOIN dbo.th_schema_migrations m ON m.version=f.version
    WHERE m.checksum_sha256 IS NOT NULL AND m.checksum_sha256<>f.checksum_sha256
)
    THROW 52002,'Existe una migracion registrada con un checksum diferente.',1;

BEGIN TRAN;
INSERT dbo.th_schema_migrations(version,nombre_archivo,checksum_sha256)
SELECT f.version,f.nombre_archivo,f.checksum_sha256
FROM @firmas f
WHERE NOT EXISTS(SELECT 1 FROM dbo.th_schema_migrations m WHERE m.version=f.version);

UPDATE m SET checksum_sha256=f.checksum_sha256,nombre_archivo=f.nombre_archivo
FROM dbo.th_schema_migrations m JOIN @firmas f ON f.version=m.version
WHERE m.checksum_sha256 IS NULL;
COMMIT;

SELECT version,nombre_archivo,checksum_sha256,fecha_aplicacion,aplicado_por
FROM dbo.th_schema_migrations ORDER BY migration_id;
GO
