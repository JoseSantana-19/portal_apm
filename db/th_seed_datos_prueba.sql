-- =============================================================================
-- Datos de PRUEBA para la BD Talento_Humano — idempotente (re-ejecutable).
-- Puestos, empleados, historial laboral, acciones de personal y títulos.
-- Resuelve unidad/puesto por CÓDIGO (no por id) para ser estable.
-- =============================================================================
SET QUOTED_IDENTIFIER ON;
SET ANSI_NULLS ON;
GO
USE [Talento_Humano];
GO

-- ── 1. PUESTOS (catálogo ampliado) ──────────────────────────────────────────
INSERT INTO th_puestos (codigo_puesto, nombre_puesto, remuneracion_unificada, activo)
SELECT v.cod, v.nom, v.rmu, 1
FROM (VALUES
    ('PST-006','DIRECTOR FINANCIERO',            2800.00),
    ('PST-007','ANALISTA FINANCIERO',            1400.00),
    ('PST-008','CONTADOR GENERAL',               1900.00),
    ('PST-009','ASISTENTE CONTABLE',             1000.00),
    ('PST-010','TESORERO',                       1900.00),
    ('PST-011','ANALISTA DE PRESUPUESTO',        1400.00),
    ('PST-012','DIRECTOR DE PLANIFICACION',      2800.00),
    ('PST-013','ANALISTA DE PLANIFICACION',      1400.00),
    ('PST-014','DIRECTOR JURIDICO',              2800.00),
    ('PST-015','ABOGADO',                        1600.00),
    ('PST-016','JEFE DE TECNOLOGIA',             2400.00),
    ('PST-017','ANALISTA DE SISTEMAS',           1500.00),
    ('PST-018','SOPORTE TECNICO',                1000.00),
    ('PST-019','ASISTENTE ADMINISTRATIVO',        900.00),
    ('PST-020','RECAUDADOR',                     1000.00)
) v(cod, nom, rmu)
WHERE NOT EXISTS (SELECT 1 FROM th_puestos p WHERE p.codigo_puesto = v.cod);
GO

-- ── 2. EMPLEADOS ────────────────────────────────────────────────────────────
;WITH nuevos (identificacion, nombres, apellidos, fnac, sexo, ecivil, cod_uorg, cod_puesto, fing, sueldo, correo, cel, ciudad, dir, cargas, tcta, ncta, banco) AS (
    SELECT * FROM (VALUES
    ('1310000003','MARÍA JOSÉ','CEDEÑO ZAMBRANO','1990-04-22','F','Casada','DEP-NOM','PST-002','2016-03-01',1400.00,'mcedeno@apm.gob.ec','0991000003','Manta','Cdla. Los Esteros mz 4','1','Ahorros','2210000003','Banco Pichincha'),
    ('1310000004','LUIS FERNANDO','PÁRRAGA MOREIRA','1985-11-05','M','Soltero','DEP-SEL','PST-003','2014-07-15',1400.00,'lparraga@apm.gob.ec','0991000004','Manta','Av. Flavio Reyes y c.20','0','Ahorros','2210000004','Banco Guayaquil'),
    ('1310000005','ANA GABRIELA','VÉLEZ INTRIAGO','1992-01-30','F','Soltera','DEP-BS','PST-004','2018-09-10',1200.00,'avelez@apm.gob.ec','0991000005','Montecristi','Vía Montecristi km 2','0','Ahorros','2210000005','Banco del Pacífico'),
    ('1310000006','JORGE ANDRÉS','MACÍAS LOOR','1980-06-18','M','Casado','DIR-FIN','PST-006','2010-02-01',2800.00,'jmacias@apm.gob.ec','0991000006','Manta','Urb. Manta 2000 mz 12','3','Corriente','2210000006','Banco Pichincha'),
    ('1310000007','DIANA CAROLINA','MENDOZA BRAVO','1989-08-14','F','Casada','DIR-FIN','PST-007','2015-05-20',1400.00,'dmendoza@apm.gob.ec','0991000007','Manta','Barrio Córdova c.15','2','Ahorros','2210000007','Banco Bolivariano'),
    ('1310000008','CARLOS EDUARDO','ANCHUNDIA PÁRRAGA','1978-12-03','M','Divorciado','DIR-CON','PST-008','2009-01-12',1900.00,'canchundia@apm.gob.ec','0991000008','Manta','Av. 24 y calle J','1','Corriente','2210000008','Banco Pichincha'),
    ('1310000009','GLORIA ESTEFANÍA','LUCAS SÁNCHEZ','1994-03-27','F','Soltera','DEP-CON','PST-009','2019-11-04',1000.00,'glucas@apm.gob.ec','0991000009','Manta','Cdla. Universitaria','0','Ahorros','2210000009','Banco Guayaquil'),
    ('1310000010','PABLO ROBERTO','GARCÍA CHÁVEZ','1983-05-09','M','Casado','DIR-TES','PST-010','2012-08-01',1900.00,'pgarcia@apm.gob.ec','0991000010','Manta','Av. 4 de Noviembre','2','Corriente','2210000010','Banco del Pacífico'),
    ('1310000011','SANDRA PATRICIA','ZAMBRANO MERO','1987-10-21','F','Casada','DEP-TES','PST-020','2016-06-15',1000.00,'szambrano@apm.gob.ec','0991000011','Jaramijó','Vía Jaramijó c.3','1','Ahorros','2210000011','Banco Pichincha'),
    ('1310000012','MIGUEL ÁNGEL','PICO INTRIAGO','1991-02-16','M','Soltero','DEP-FAC','PST-020','2018-04-02',1000.00,'mpico@apm.gob.ec','0991000012','Manta','Barrio 15 de Abril','0','Ahorros','2210000012','Banco Guayaquil'),
    ('1310000013','KARLA MICHELLE','SOLÓRZANO VERA','1993-07-08','F','Soltera','DEP-FAC','PST-019','2020-01-20',900.00,'ksolorzano@apm.gob.ec','0991000013','Manta','Cdla. San José','0','Ahorros','2210000013','Banco Bolivariano'),
    ('1310000014','FREDDY GEOVANNY','CHÁVEZ DELGADO','1979-09-11','M','Casado','DEP-PRE','PST-011','2011-03-14',1400.00,'fchavez@apm.gob.ec','0991000014','Manta','Av. 113 y calle 202','3','Corriente','2210000014','Banco Pichincha'),
    ('1310000015','VERÓNICA ALEXANDRA','LOOR CAGUA','1986-04-19','F','Casada','DIR-PLAN','PST-012','2013-10-01',2800.00,'vloor@apm.gob.ec','0991000015','Manta','Urb. Los Ceibos','2','Corriente','2210000015','Banco del Pacífico'),
    ('1310000016','DAVID SANTIAGO','MERA BAILÓN','1990-11-28','M','Soltero','DEP-PLAN','PST-013','2017-07-03',1400.00,'dmera@apm.gob.ec','0991000016','Manta','Cdla. Aeropuerto','0','Ahorros','2210000016','Banco Guayaquil'),
    ('1310000017','ANDREA PAOLA','QUIROZ MENÉNDEZ','1988-06-06','F','Casada','DIR-JUR','PST-014','2012-05-11',2800.00,'aquiroz@apm.gob.ec','0991000017','Manta','Av. Malecón y c.11','1','Corriente','2210000017','Banco Pichincha'),
    ('1310000018','HÉCTOR RAMÓN','BRIONES SALTOS','1982-01-25','M','Casado','DIR-JUR','PST-015','2014-02-17',1600.00,'hbriones@apm.gob.ec','0991000018','Manta','Barrio Santa Martha','2','Ahorros','2210000018','Banco Bolivariano'),
    ('1310000019','LISSETTE JOHANNA','CAGUA PÁRRAGA','1995-05-15','F','Soltera','DIR-TICS','PST-017','2021-03-01',1500.00,'lcagua@apm.gob.ec','0991000019','Manta','Cdla. Los Almendros','0','Ahorros','2210000019','Banco Pichincha'),
    ('1310000020','WILSON JAVIER','INTRIAGO VERA','1984-08-30','M','Casado','DIR-TICS','PST-016','2011-09-19',2400.00,'wintriago@apm.gob.ec','0991000020','Manta','Urb. La Pradera','2','Corriente','2210000020','Banco del Pacífico'),
    ('1310000021','JÉSSICA MARIBEL','ZAMORA LOOR','1996-12-12','F','Soltera','DIR-TICS','PST-018','2022-06-06',1000.00,'jzamora@apm.gob.ec','0991000021','Manta','Cdla. 4 de Noviembre','0','Ahorros','2210000021','Banco Guayaquil'),
    ('1310000022','ÁNGEL GUSTAVO','MOREIRA PALMA','1981-03-03','M','Divorciado','DIR-CON','PST-009','2010-11-22',1000.00,'amoreira@apm.gob.ec','0991000022','Manta','Barrio Cuba','1','Ahorros','2210000022','Banco Pichincha'),
    ('1310000023','TATIANA ELIZABETH','SÁNCHEZ RODRÍGUEZ','1990-09-17','F','Casada','DEP-NOM','PST-002','2016-08-08',1400.00,'tsanchez@apm.gob.ec','0991000023','Manta','Cdla. Los Rosales','1','Ahorros','2210000023','Banco Bolivariano'),
    ('1310000024','RICARDO ANTONIO','BAILÓN CEVALLOS','1977-07-07','M','Casado','DIR-FIN','PST-007','2008-04-01',1400.00,'rbailon@apm.gob.ec','0991000024','Manta','Urb. Manta 2000','3','Corriente','2210000024','Banco del Pacífico'),
    ('1310000025','GABRIELA NICOLE','VERA MACÍAS','1997-02-28','F','Soltera','DEP-SEL','PST-003','2022-01-10',1400.00,'gvera@apm.gob.ec','0991000025','Manta','Cdla. El Palmar','0','Ahorros','2210000025','Banco Pichincha'),
    ('1310000026','FERNANDO JOSÉ','DELGADO MERO','1985-10-10','M','Casado','DEP-BS','PST-004','2015-12-01',1200.00,'fdelgado@apm.gob.ec','0991000026','Montecristi','Ciudadela Nueva','2','Ahorros','2210000026','Banco Guayaquil'),
    ('1310000027','PAOLA CRISTINA','RODRÍGUEZ INTRIAGO','1991-06-24','F','Soltera','DEP-PRE','PST-011','2018-02-19',1400.00,'prodriguez@apm.gob.ec','0991000027','Manta','Av. 24 y calle 15','0','Ahorros','2210000027','Banco Pichincha'),
    ('1310000028','JUAN CARLOS','PALMA TEJENA','1983-04-04','M','Casado','DIR-TES','PST-020','2012-03-30',1000.00,'jpalma@apm.gob.ec','0991000028','Manta','Barrio Umiña','2','Corriente','2210000028','Banco Bolivariano'),
    ('1310000029','MÓNICA ALEXANDRA','ZAMBRANO CEDEÑO','1989-01-19','F','Casada','DIR-CON','PST-008','2014-09-09',1900.00,'mzambrano2@apm.gob.ec','0991000029','Manta','Cdla. Los Geranios','2','Corriente','2210000029','Banco del Pacífico'),
    ('1310000030','ESTEBAN MAURICIO','LOOR VÉLEZ','1994-11-11','M','Soltero','DEP-CON','PST-009','2020-10-05',1000.00,'eloor@apm.gob.ec','0991000030','Manta','Cdla. Universitaria','0','Ahorros','2210000030','Banco Pichincha')
    ) v(identificacion, nombres, apellidos, fnac, sexo, ecivil, cod_uorg, cod_puesto, fing, sueldo, correo, cel, ciudad, dir, cargas, tcta, ncta, banco)
)
INSERT INTO th_empleados
    (identificacion, tipo_identificacion, nombres, apellidos, fecha_nacimiento, sexo, estado_civil,
     nacionalidad, unidad_id, puesto_id, fecha_ingreso, sueldo_rmu, correo_institucional,
     correo_personal, telefono_movil, ciudad_residencia, direccion_domiciliaria, cuenta_bancaria,
     codigo_iess, estado, cargas_familiares, tipo_cuenta_bancaria, numero_cuenta_bancaria, institucion_bancaria)
SELECT n.identificacion, 'Cédula', n.nombres, n.apellidos, n.fnac, n.sexo, n.ecivil,
       'Ecuatoriana', u.unidad_id, p.puesto_id, n.fing, n.sueldo, n.correo,
       NULL, n.cel, n.ciudad, n.dir, n.ncta, n.identificacion, 1, n.cargas, n.tcta, n.ncta, n.banco
FROM nuevos n
JOIN th_unidades_organizacionales u ON u.codigo_uorg   = n.cod_uorg
JOIN th_puestos p                   ON p.codigo_puesto = n.cod_puesto
WHERE NOT EXISTS (SELECT 1 FROM th_empleados e WHERE e.identificacion = n.identificacion);
GO

-- ── 3. Fusión organizacional de ejemplo: Tesorería → unificada en Financiera ─
UPDATE th_unidades_organizacionales
SET sucedido_por_id = (SELECT TOP 1 unidad_id FROM th_unidades_organizacionales WHERE codigo_uorg='DIR-FIN')
WHERE codigo_uorg = 'DIR-TES' AND sucedido_por_id IS NULL;
GO

-- ── 4. HISTORIAL LABORAL: periodo vigente para todo empleado (poblar reporte) ─
INSERT INTO th_historial_laboral (empleado_id, puesto_id, unidad_id, fecha_desde, fecha_hasta)
SELECT e.empleado_id, e.puesto_id, e.unidad_id, e.fecha_ingreso, NULL
FROM th_empleados e
WHERE e.puesto_id IS NOT NULL AND e.unidad_id IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM th_historial_laboral h
      WHERE h.empleado_id = e.empleado_id AND h.fecha_desde = e.fecha_ingreso);
GO

-- Periodo pasado (fusión) para el funcionario 1301234567: estuvo en TICS antes de TH
INSERT INTO th_historial_laboral (empleado_id, puesto_id, unidad_id, fecha_desde, fecha_hasta)
SELECT e.empleado_id,
       (SELECT TOP 1 puesto_id FROM th_puestos WHERE codigo_puesto='PST-016'),
       (SELECT TOP 1 unidad_id FROM th_unidades_organizacionales WHERE codigo_uorg='DIR-TICS'),
       '2010-01-01', '2014-12-31'
FROM th_empleados e
WHERE e.identificacion='1301234567'
  AND NOT EXISTS (SELECT 1 FROM th_historial_laboral h WHERE h.empleado_id=e.empleado_id AND h.fecha_desde='2010-01-01');
GO

-- ── 5. ACCIONES DE PERSONAL de ejemplo ──────────────────────────────────────
INSERT INTO th_acciones_personal
    (numero_accion, fecha_elaboracion, empleado_id, tipo_accion, fecha_rige_desde, fecha_rige_hasta,
     explicacion_legal, actual_unidad_id, actual_puesto_id, actual_lugar_trabajo, actual_remuneracion,
     propuesta_unidad_id, propuesta_puesto_id, propuesta_lugar_trabajo, propuesta_remuneracion, estado_documento)
SELECT x.numero_accion, x.fe, e.empleado_id, x.tipo, x.desde, x.hasta, x.motivo,
       e.unidad_id, e.puesto_id, 'Manta - Instalaciones APM', e.sueldo_rmu,
       e.unidad_id, pp.puesto_id, 'Manta - Instalaciones APM', pp.remuneracion_unificada, 'Aprobado'
FROM (VALUES
    ('APM-TH-2026-001','2026-01-15','1310000007','ASCENSO',   '2026-02-01', NULL, 'Ascenso por méritos y evaluación de desempeño sobresaliente (Art. 68 LOSEP).','PST-006'),
    ('APM-TH-2026-002','2026-02-10','1310000016','TRASLADO',  '2026-03-01', NULL, 'Traslado administrativo por necesidad institucional (Art. 35 LOSEP).','PST-013'),
    ('APM-TH-2026-003','2026-03-05','1310000021','ENCARGO',   '2026-03-10','2026-06-10','Encargo de funciones por ausencia temporal del titular.','PST-017'),
    ('APM-TH-2026-004','2026-04-20','1310000011','INCREMENTO RMU','2026-05-01', NULL, 'Revisión de la remuneración conforme a la escala vigente.','PST-020'),
    ('APM-TH-2026-005','2026-05-18','1310000025','SUBROGACIÓN','2026-06-01','2026-08-31','Subrogación por vacaciones del servidor titular.','PST-002')
) x(numero_accion, fe, ced, tipo, desde, hasta, motivo, cod_puesto_prop)
JOIN th_empleados e ON e.identificacion = x.ced
JOIN th_puestos   pp ON pp.codigo_puesto = x.cod_puesto_prop
WHERE NOT EXISTS (SELECT 1 FROM th_acciones_personal a WHERE a.numero_accion = x.numero_accion);
GO

-- ── 6. TÍTULOS académicos de ejemplo ────────────────────────────────────────
INSERT INTO th_titulos (empleado_id, nivel_instruccion, nombre_titulo, institucion_educativa, numero_senescyt, estado)
SELECT e.empleado_id, t.nivel, t.titulo, t.inst, t.senescyt, 1
FROM (VALUES
    ('1310000006','Cuarto Nivel','MAGÍSTER EN FINANZAS',                 'Universidad Espíritu Santo','1006-2015-1650123'),
    ('1310000015','Cuarto Nivel','MAGÍSTER EN GESTIÓN PÚBLICA',          'IAEN',                       '1006-2016-1650456'),
    ('1310000017','Tercer Nivel','ABOGADA DE LOS TRIBUNALES',            'Universidad Laica Eloy Alfaro','1005-2011-1140789'),
    ('1310000020','Tercer Nivel','INGENIERO EN SISTEMAS INFORMÁTICOS',   'ULEAM',                       '1005-2009-1140012'),
    ('1310000008','Tercer Nivel','INGENIERO EN CONTABILIDAD Y AUDITORÍA','ULEAM',                       '1005-2008-1140345')
) t(ced, nivel, titulo, inst, senescyt)
JOIN th_empleados e ON e.identificacion = t.ced
WHERE NOT EXISTS (SELECT 1 FROM th_titulos x WHERE x.empleado_id = e.empleado_id AND x.nombre_titulo = t.titulo);
GO
