-- =============================================================================
-- SCRIPT SQL v2.1 – HISTORIAL LABORAL DE 15 AÑOS + FUSIÓN ORGANIZACIONAL
-- Base de datos : Talento_Humano (SQL Server)
-- Instancia     : JAVIER
-- Ejecutar en   : SQL Server Management Studio (SSMS)
-- Propósito     : Registra el caso de uso real del funcionario que estuvo
--                 15 años en la Dirección de Tecnología de la Información y
--                 posteriormente pasó a la Dirección de Planificación Estratégica
--                 (departamento: Gestión de Tecnología de la Información).
-- =============================================================================

USE Talento_Humano;
GO

-- =============================================================================
-- PASO 1 – Agregar columnas de herencia temporal si no existen
--           (seguro para re-ejecución — no duplica cambios)
-- =============================================================================
IF NOT EXISTS (
    SELECT 1 FROM sys.columns
    WHERE object_id = OBJECT_ID('th_unidades_organizacionales') AND name = 'sucedido_por_id'
)
BEGIN
    ALTER TABLE th_unidades_organizacionales ADD sucedido_por_id INT NULL;
    ALTER TABLE th_unidades_organizacionales
        ADD CONSTRAINT FK_Unidad_Sucedida
        FOREIGN KEY (sucedido_por_id) REFERENCES th_unidades_organizacionales(unidad_id);
    PRINT '✅ Columna sucedido_por_id agregada.';
END
ELSE PRINT '⚠️  sucedido_por_id ya existe.';
GO

IF NOT EXISTS (
    SELECT 1 FROM sys.columns
    WHERE object_id = OBJECT_ID('th_unidades_organizacionales') AND name = 'fecha_fin'
)
BEGIN
    ALTER TABLE th_unidades_organizacionales ADD fecha_fin DATE NULL;
    PRINT '✅ Columna fecha_fin agregada.';
END
ELSE PRINT '⚠️  fecha_fin ya existe.';
GO

-- =============================================================================
-- PASO 2 – Registrar las dos unidades organizacionales del caso de uso
--
-- UNIDAD A : Dirección de Tecnología de la Información       (activa 2009-2024, luego fusionada)
-- UNIDAD B : Dirección de Planificación Estratégica          (activa desde 2024, vigente)
-- DPTO B1  : Gestión de Tecnología de la Información         (sub-área de Unidad B)
--
-- ⚠️  Si los registros ya existen en tu BD, comenta el INSERT y
--     usa los IDs reales que ya tienes en th_unidades_organizacionales.
-- =============================================================================

-- Dirección histórica (15 años)
IF NOT EXISTS (SELECT 1 FROM th_unidades_organizacionales WHERE codigo_uorg = 'DTI-001')
BEGIN
    INSERT INTO th_unidades_organizacionales
        (codigo_uorg, nombre_unidad, tipo_proceso, unidad_padre_id, sucedido_por_id, fecha_inicio, fecha_fin, activo)
    VALUES
        ('DTI-001',
         'Dirección de Tecnología de la Información',
         'Procesos Adjetivos',
         NULL,   -- sin padre: es dirección de primer nivel
         NULL,   -- se llenará abajo con el ID de la nueva dirección
         '2009-01-01',
         '2024-12-31',   -- Día en que se fusionó / transformó
         0);             -- ya no está activa como entidad independiente
    PRINT '✅ DTI-001 insertada.';
END
ELSE PRINT '⚠️  DTI-001 ya existe.';
GO

-- Nueva dirección macro (donde aterrizó el funcionario tras la transformación)
IF NOT EXISTS (SELECT 1 FROM th_unidades_organizacionales WHERE codigo_uorg = 'DPE-001')
BEGIN
    INSERT INTO th_unidades_organizacionales
        (codigo_uorg, nombre_unidad, tipo_proceso, unidad_padre_id, sucedido_por_id, fecha_inicio, fecha_fin, activo)
    VALUES
        ('DPE-001',
         'Dirección de Planificación Estratégica',
         'Procesos Gobernantes',
         NULL,
         NULL,        -- esta es la dirección vigente, no tiene sucesor
         '2025-01-01',
         NULL,        -- NULL = activa actualmente
         1);
    PRINT '✅ DPE-001 insertada.';
END
ELSE PRINT '⚠️  DPE-001 ya existe.';
GO

-- Departamento sub-área dentro de Planificación Estratégica
IF NOT EXISTS (SELECT 1 FROM th_unidades_organizacionales WHERE codigo_uorg = 'DPE-GTI')
BEGIN
    INSERT INTO th_unidades_organizacionales
        (codigo_uorg, nombre_unidad, tipo_proceso, unidad_padre_id, sucedido_por_id, fecha_inicio, fecha_fin, activo)
    VALUES
        ('DPE-GTI',
         'Gestión de Tecnología de la Información',
         'Procesos Adjetivos',
         (SELECT unidad_id FROM th_unidades_organizacionales WHERE codigo_uorg = 'DPE-001'),
         NULL,
         '2025-01-01',
         NULL,
         1);
    PRINT '✅ DPE-GTI insertada (sub-área de DPE-001).';
END
ELSE PRINT '⚠️  DPE-GTI ya existe.';
GO

-- =============================================================================
-- PASO 3 – Enlazar la fusión: DTI-001 → sucedida por DPE-001
--           Esto es lo que hace que la vista muestre automáticamente:
--           "Dirección de Tecnología de la Información
--            (Actualmente unificada en: Dirección de Planificación Estratégica)"
-- =============================================================================
UPDATE th_unidades_organizacionales
SET sucedido_por_id = (SELECT unidad_id FROM th_unidades_organizacionales WHERE codigo_uorg = 'DPE-001')
WHERE codigo_uorg = 'DTI-001'
  AND sucedido_por_id IS NULL;  -- protección: no sobreescribir si ya está enlazado
GO
PRINT '✅ Enlace de fusión DTI-001 → DPE-001 aplicado.';
GO

-- =============================================================================
-- PASO 4 – Registrar el historial laboral del funcionario en th_historial_laboral
--
-- PERÍODO 1: 15 años en Dirección de Tecnología de la Información (2009–2024)
-- PERÍODO 2: Actual en Dirección de Planificación Estratégica / Gestión de TI
--
-- ⚠️  Reemplaza empleado_id y puesto_id con los valores reales de tu BD.
--     La variable @emp_id es el ID del funcionario (ej. el de Javier).
-- =============================================================================

DECLARE @emp_id  INT = (SELECT TOP 1 empleado_id FROM th_empleados WHERE cedula = '1309140808');  -- ← reemplaza con la cédula real
DECLARE @id_dti  INT = (SELECT unidad_id FROM th_unidades_organizacionales WHERE codigo_uorg = 'DTI-001');
DECLARE @id_gti  INT = (SELECT unidad_id FROM th_unidades_organizacionales WHERE codigo_uorg = 'DPE-GTI');

-- Puesto que tenía en TI (ajusta el puesto_id real o usa el nombre para buscarlo)
DECLARE @puesto_ti  INT = (SELECT TOP 1 puesto_id FROM th_puestos WHERE nombre_puesto LIKE '%Tecnolog%');
-- Puesto actual en Planificación (ajusta según tu catálogo real)
DECLARE @puesto_act INT = (SELECT TOP 1 puesto_id FROM th_puestos WHERE nombre_puesto LIKE '%Gestión%');

-- PERÍODO 1 – 15 años en DTI
IF @emp_id IS NOT NULL AND NOT EXISTS (
    SELECT 1 FROM th_historial_laboral
    WHERE empleado_id = @emp_id AND unidad_id = @id_dti AND fecha_desde = '2009-01-01'
)
BEGIN
    INSERT INTO th_historial_laboral (empleado_id, puesto_id, unidad_id, fecha_desde, fecha_hasta)
    VALUES (@emp_id, @puesto_ti, @id_dti, '2009-01-01', '2024-12-31');
    PRINT '✅ Período 1 (15 años en DTI) insertado.';
END
ELSE PRINT '⚠️  Período 1 ya existe o empleado_id no encontrado.';

-- PERÍODO 2 – Actual en Gestión de TI / Planificación Estratégica
IF @emp_id IS NOT NULL AND NOT EXISTS (
    SELECT 1 FROM th_historial_laboral
    WHERE empleado_id = @emp_id AND unidad_id = @id_gti AND fecha_desde = '2025-01-01'
)
BEGIN
    INSERT INTO th_historial_laboral (empleado_id, puesto_id, unidad_id, fecha_desde, fecha_hasta)
    VALUES (@emp_id, @puesto_act, @id_gti, '2025-01-01', NULL);  -- NULL = cargo actual, sin fecha de fin
    PRINT '✅ Período 2 (actual en Gestión de TI / Planificación) insertado.';
END
ELSE PRINT '⚠️  Período 2 ya existe o empleado_id no encontrado.';
GO

-- =============================================================================
-- PASO 5 – Reconstruir la vista vw_th_reporte_historial_jerarquico
--           (incluye la columna sub_area para mostrar el departamento exacto)
-- =============================================================================
IF OBJECT_ID('vw_th_reporte_historial_jerarquico', 'V') IS NOT NULL
    DROP VIEW vw_th_reporte_historial_jerarquico;
GO

CREATE VIEW vw_th_reporte_historial_jerarquico AS
SELECT
    e.empleado_id,
    e.cedula,
    e.apellidos + ' ' + e.nombres          AS funcionario,
    p.codigo_puesto,
    p.nombre_puesto,

    -- ① Nombre histórico: cómo se llamaba la dirección cuando el funcionario trabajó ahí
    u.nombre_unidad                         AS departamento_historico,

    -- ② Sub-área / departamento exacto (puede coincidir con la dirección si es de 1er nivel)
    CASE
        WHEN u_padre.unidad_id IS NOT NULL THEN u_padre.nombre_unidad
        ELSE u.nombre_unidad
    END                                     AS direccion_padre,

    -- ③ Nombre del departamento específico (Gestión de TI, etc.)
    CASE
        WHEN u_padre.unidad_id IS NOT NULL THEN u.nombre_unidad
        ELSE NULL
    END                                     AS sub_area,

    -- ④ Dirección actual unificada: si DTI se fusionó con DPE → muestra DPE
    ISNULL(u_nueva.nombre_unidad, u.nombre_unidad)      AS direccion_actual_unificada,
    ISNULL(u_nueva.tipo_proceso,  u.tipo_proceso)       AS tipo_proceso,

    h.fecha_desde,
    h.fecha_hasta,

    -- ⑤ Años de permanencia en ese cargo/área
    DATEDIFF(year, h.fecha_desde, ISNULL(h.fecha_hasta, GETDATE())) AS anios_permanencia,

    -- ⑥ Días exactos al próximo cumpleaños (maneja el caso ya-pasó-este-año)
    DATEDIFF(day, CAST(GETDATE() AS DATE),
        DATEFROMPARTS(
            YEAR(GETDATE()) + CASE
                WHEN DATEFROMPARTS(
                         YEAR(GETDATE()),
                         MONTH(e.fecha_nacimiento),
                         DAY(e.fecha_nacimiento)
                     ) < CAST(GETDATE() AS DATE)
                THEN 1 ELSE 0 END,
            MONTH(e.fecha_nacimiento),
            DAY(e.fecha_nacimiento)
        )
    ) AS dias_para_cumpleanos

FROM th_historial_laboral h
JOIN  th_empleados e                   ON h.empleado_id = e.empleado_id
JOIN  th_puestos   p                   ON h.puesto_id   = p.puesto_id
JOIN  th_unidades_organizacionales u   ON h.unidad_id   = u.unidad_id

-- Detecta si la unidad asignada es un sub-área (tiene padre)
LEFT JOIN th_unidades_organizacionales u_padre
                                       ON u.unidad_padre_id = u_padre.unidad_id

-- Detecta la fusión: si DTI fue sucedida por DPE, aparece DPE en direccion_actual_unificada
LEFT JOIN th_unidades_organizacionales u_nueva
                                       ON u.sucedido_por_id = u_nueva.unidad_id;
GO

PRINT '✅ Vista vw_th_reporte_historial_jerarquico reconstruida con sub_area y anios_permanencia.';
GO

-- =============================================================================
-- VERIFICACIÓN – Ejecutar para ver el historial completo del funcionario
-- (reemplaza la cédula real)
-- =============================================================================
-- SELECT
--     funcionario,
--     direccion_padre         AS [Dirección],
--     sub_area                AS [Departamento],
--     departamento_historico  AS [Nombre Histórico],
--     direccion_actual_unificada AS [Unificada en],
--     nombre_puesto,
--     fecha_desde,
--     fecha_hasta,
--     anios_permanencia       AS [Años],
--     dias_para_cumpleanos    AS [Días Cumple]
-- FROM vw_th_reporte_historial_jerarquico
-- WHERE cedula = '1309XXXXXX'   -- ← reemplaza
-- ORDER BY fecha_desde ASC;


-- =============================================================================
-- BLOQUE 15 – MÓDULO DE SEGURIDAD Y ROLES (RBAC)
-- Control de acceso basado en roles para el Portal Portuario APM
-- Ejecutar después del schema principal — seguro para re-ejecución
-- =============================================================================

-- A. TABLA DE ROLES
IF NOT EXISTS (SELECT 1 FROM sys.tables WHERE name = 'th_roles')
BEGIN
    CREATE TABLE th_roles (
        rol_id       INT IDENTITY(1,1) PRIMARY KEY,
        nombre_rol   VARCHAR(50)  NOT NULL UNIQUE,
        descripcion  VARCHAR(255) NULL,
        estado       BIT DEFAULT 1
    );
    PRINT '✓ Tabla th_roles creada';
END
ELSE PRINT '⚠️  th_roles ya existe';
GO

-- B. TABLA DE MÓDULOS (Las filas de la matriz de permisos)
IF NOT EXISTS (SELECT 1 FROM sys.tables WHERE name = 'th_modulos')
BEGIN
    CREATE TABLE th_modulos (
        modulo_id    INT IDENTITY(1,1) PRIMARY KEY,
        nombre_modulo VARCHAR(100) NOT NULL UNIQUE,
        grupo         VARCHAR(50)  NOT NULL
    );
    PRINT '✓ Tabla th_modulos creada';
END
ELSE PRINT '⚠️  th_modulos ya existe';
GO

-- C. TABLA PIVOTE DE PERMISOS (Los interruptores granulares)
IF NOT EXISTS (SELECT 1 FROM sys.tables WHERE name = 'th_permisos_rol')
BEGIN
    CREATE TABLE th_permisos_rol (
        permiso_id      INT IDENTITY(1,1) PRIMARY KEY,
        rol_id          INT NOT NULL,
        modulo_id       INT NOT NULL,

        -- Permisos granulares (1 = Permitido, 0 = Denegado)
        puede_visualizar BIT DEFAULT 0,
        puede_crear      BIT DEFAULT 0,
        puede_editar     BIT DEFAULT 0,
        puede_eliminar   BIT DEFAULT 0,
        puede_aprobar    BIT DEFAULT 0,

        CONSTRAINT FK_Permiso_Rol    FOREIGN KEY (rol_id)    REFERENCES th_roles(rol_id)   ON DELETE CASCADE,
        CONSTRAINT FK_Permiso_Modulo FOREIGN KEY (modulo_id) REFERENCES th_modulos(modulo_id),
        CONSTRAINT UQ_Rol_Modulo     UNIQUE (rol_id, modulo_id)  -- Un rol solo tiene una regla por módulo
    );
    PRINT '✓ Tabla th_permisos_rol creada';
END
ELSE PRINT '⚠️  th_permisos_rol ya existe';
GO

-- D. TABLA DE USUARIOS DEL SISTEMA
IF NOT EXISTS (SELECT 1 FROM sys.tables WHERE name = 'th_usuarios_sistema')
BEGIN
    CREATE TABLE th_usuarios_sistema (
        usuario_id   INT IDENTITY(1,1) PRIMARY KEY,
        usuario      VARCHAR(50)  NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        correo       VARCHAR(100) NOT NULL,
        nombre       VARCHAR(150) NOT NULL,
        empleado_id  INT NULL,                         -- FK a th_empleados (opcional)
        rol_id       INT NOT NULL,
        estado       BIT DEFAULT 1,
        ultimo_acceso DATETIME NULL,
        fecha_creacion DATETIME DEFAULT GETDATE(),

        CONSTRAINT FK_Usuario_Rol      FOREIGN KEY (rol_id)      REFERENCES th_roles(rol_id),
        CONSTRAINT FK_Usuario_Empleado FOREIGN KEY (empleado_id) REFERENCES th_empleados(empleado_id)
    );
    PRINT '✓ Tabla th_usuarios_sistema creada';
END
ELSE PRINT '⚠️  th_usuarios_sistema ya existe';
GO


-- =============================================================================
-- BLOQUE 16 – TABLA DE LOGS DE AUDITORÍA (SEGURIDAD / CONTRALORÍA)
-- Registro inmutable de todas las acciones realizadas en el sistema
-- =============================================================================
IF NOT EXISTS (SELECT 1 FROM sys.tables WHERE name = 'th_logs_auditoria')
BEGIN
    CREATE TABLE th_logs_auditoria (
        log_id             INT IDENTITY(1,1) PRIMARY KEY,
        fecha_hora         DATETIME     DEFAULT GETDATE(),
        usuario            VARCHAR(50)  NOT NULL,           -- Usuario que realizó la acción
        modulo             VARCHAR(50)  NOT NULL,           -- Ej: 'Directorio', 'Acción de Personal'
        accion             VARCHAR(50)  NOT NULL,           -- 'CREAR', 'ACTUALIZAR', 'ELIMINAR', 'EXPORTAR', 'APROBAR', 'LOGIN'
        descripcion_detalle VARCHAR(500) NOT NULL,          -- Descripción detallada del evento
        direccion_ip       VARCHAR(45)  NOT NULL            -- IP desde donde se ejecutó la acción
        -- NO se agrega ON DELETE CASCADE → los logs son INMUTABLES por diseño
    );
    PRINT '✓ Tabla th_logs_auditoria creada';
END
ELSE PRINT '⚠️  th_logs_auditoria ya existe';
GO


-- =============================================================================
-- BLOQUE 17 – INSERCIÓN DE DATOS DE PRUEBA (MOCK DATA SQL)
-- Ejecutar solo una vez. Usar IF NOT EXISTS para idempotencia.
-- =============================================================================

-- Insertar Módulos del Sistema
IF NOT EXISTS (SELECT 1 FROM th_modulos)
BEGIN
    INSERT INTO th_modulos (nombre_modulo, grupo) VALUES
    ('Directorio de Personal',    'Principal'),
    ('Vacaciones y Ausencias',    'Gestión Operativa'),
    ('Acción de Personal',        'Gestión Operativa'),
    ('Asistencia',                'Gestión Operativa'),
    ('Capacitación',              'Gestión Operativa'),
    ('Desempeño',                 'Gestión Operativa'),
    ('Reportes Generales',        'Administración y Seguridad'),
    ('Auditoría y Logs',          'Administración y Seguridad'),
    ('Gestión de Usuarios',       'Administración y Seguridad'),
    ('Roles y Permisos',          'Administración y Seguridad');
    PRINT '✓ Módulos del sistema insertados (10 módulos)';
END
ELSE PRINT '⚠️  th_modulos ya tiene datos';
GO

-- Insertar Roles del Sistema
IF NOT EXISTS (SELECT 1 FROM th_roles)
BEGIN
    INSERT INTO th_roles (nombre_rol, descripcion) VALUES
    ('Super Administrador', 'Acceso total sin restricciones al sistema completo'),
    ('Administrador TH',    'Gestión completa de RRHH — sin acceso a configuración del sistema'),
    ('Analista RRHH',       'Gestión operativa de expedientes y vacaciones — sin eliminar'),
    ('Consultor',           'Acceso de solo lectura a expedientes y reportes'),
    ('Supervisor',          'Aprobación de vacaciones y consulta del directorio');
    PRINT '✓ Roles insertados (5 roles)';
END
ELSE PRINT '⚠️  th_roles ya tiene datos';
GO

-- Insertar Permisos para Super Administrador (acceso total)
IF NOT EXISTS (SELECT 1 FROM th_permisos_rol WHERE rol_id = (SELECT rol_id FROM th_roles WHERE nombre_rol = 'Super Administrador'))
BEGIN
    INSERT INTO th_permisos_rol (rol_id, modulo_id, puede_visualizar, puede_crear, puede_editar, puede_eliminar, puede_aprobar)
    SELECT
        (SELECT rol_id FROM th_roles WHERE nombre_rol = 'Super Administrador'),
        modulo_id,
        1, 1, 1, 1, 1  -- Todos los permisos
    FROM th_modulos;
    PRINT '✓ Permisos de Super Administrador configurados';
END
GO

-- Insertar Permisos para Consultor (solo lectura)
IF NOT EXISTS (SELECT 1 FROM th_permisos_rol WHERE rol_id = (SELECT rol_id FROM th_roles WHERE nombre_rol = 'Consultor'))
BEGIN
    INSERT INTO th_permisos_rol (rol_id, modulo_id, puede_visualizar, puede_crear, puede_editar, puede_eliminar, puede_aprobar)
    SELECT
        (SELECT rol_id FROM th_roles WHERE nombre_rol = 'Consultor'),
        modulo_id,
        CASE WHEN nombre_modulo IN ('Gestión de Usuarios','Roles y Permisos','Auditoría y Logs') THEN 0 ELSE 1 END, -- Visualizar: no en módulos de admin
        0, 0, 0, 0  -- Sin crear, editar, eliminar, aprobar
    FROM th_modulos;
    PRINT '✓ Permisos de Consultor configurados (solo lectura)';
END
GO

-- Log inicial de auditoría (bootstrap del sistema)
IF NOT EXISTS (SELECT 1 FROM th_logs_auditoria)
BEGIN
    INSERT INTO th_logs_auditoria (usuario, modulo, accion, descripcion_detalle, direccion_ip)
    VALUES ('sistema', 'Sistema', 'CREAR', 'Base de datos del Portal Portuario APM inicializada correctamente', '127.0.0.1');
    PRINT '✓ Log inicial de auditoría registrado';
END
GO

PRINT '=================================================================';
PRINT '✅ Script de RBAC + Auditoría ejecutado correctamente';
PRINT '   Tablas creadas: th_roles, th_modulos, th_permisos_rol,';
PRINT '                   th_usuarios_sistema, th_logs_auditoria';
PRINT '   Datos de prueba: 5 roles, 10 módulos, permisos configurados';
PRINT '=================================================================';
GO

