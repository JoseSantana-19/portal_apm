-- ============================================================
-- PORTAL APM v2.1 — Esquema completo SQL Server
-- Autoridad Portuaria de Manta
-- Prefijos: CORE_ | TH_ | BIT_ | BIENES_ | ACCESO_
-- Compatibilidad: SQL Server 2014+ (Level 120)
-- NO PDO — PHP nativo sqlsrv_*
--
-- Menú: jerarquía MOIS 4 niveles
--   CORE_Menu_Nodos (id_modulo, opcion, items, subitems)
--   CORE_Permisos_Nodo: acceso por rol a cada nodo
--
-- estado TINYINT: 0=ANULADO | 1=REGISTRO VÁLIDO/ACTIVO
-- estado_[contexto] NVARCHAR: flujo de trabajo
-- nivel_jerarquia: 0=Operador 1=Analista 2=Director 3=Gerente 4=SuperAdmin
-- ============================================================

USE master;
GO

IF EXISTS (SELECT name FROM sys.databases WHERE name = N'PORTAL_APM')
BEGIN
    ALTER DATABASE PORTAL_APM SET SINGLE_USER WITH ROLLBACK IMMEDIATE;
    DROP DATABASE PORTAL_APM;
END
GO

CREATE DATABASE PORTAL_APM COLLATE Modern_Spanish_CI_AS;
GO
ALTER DATABASE PORTAL_APM SET COMPATIBILITY_LEVEL = 120;
ALTER DATABASE PORTAL_APM SET RECOVERY SIMPLE;
GO

USE PORTAL_APM;
GO

-- ============================================================
-- SECCIÓN 1: CORE_ — Hub central
-- ============================================================

IF OBJECT_ID('CORE_Config','U')               IS NOT NULL DROP TABLE CORE_Config;
IF OBJECT_ID('CORE_Contrasenas_Hist','U')     IS NOT NULL DROP TABLE CORE_Contrasenas_Hist;
IF OBJECT_ID('CORE_Notificaciones','U')       IS NOT NULL DROP TABLE CORE_Notificaciones;
IF OBJECT_ID('CORE_Auditoria','U')            IS NOT NULL DROP TABLE CORE_Auditoria;
IF OBJECT_ID('CORE_Sesiones','U')             IS NOT NULL DROP TABLE CORE_Sesiones;
IF OBJECT_ID('CORE_Formularios_Permisos','U') IS NOT NULL DROP TABLE CORE_Formularios_Permisos;
IF OBJECT_ID('CORE_Formularios','U')          IS NOT NULL DROP TABLE CORE_Formularios;
IF OBJECT_ID('CORE_Permisos_Nodo','U')        IS NOT NULL DROP TABLE CORE_Permisos_Nodo;
IF OBJECT_ID('CORE_Menu_Nodos','U')           IS NOT NULL DROP TABLE CORE_Menu_Nodos;
IF OBJECT_ID('CORE_Usuarios_Roles','U')       IS NOT NULL DROP TABLE CORE_Usuarios_Roles;
IF OBJECT_ID('CORE_Roles','U')                IS NOT NULL DROP TABLE CORE_Roles;
IF OBJECT_ID('CORE_Usuarios','U')             IS NOT NULL DROP TABLE CORE_Usuarios;
IF OBJECT_ID('CORE_Departamentos','U')        IS NOT NULL DROP TABLE CORE_Departamentos;
GO

CREATE TABLE CORE_Departamentos (
    id_departamento INT           IDENTITY(1,1) PRIMARY KEY,
    codigo          NVARCHAR(20)  NOT NULL CONSTRAINT UQ_CoreDepto_Codigo UNIQUE,
    nombre          NVARCHAR(100) NOT NULL,
    descripcion     NVARCHAR(255) NULL,
    id_padre        INT           NULL CONSTRAINT FK_CoreDepto_Padre REFERENCES CORE_Departamentos(id_departamento) ON DELETE NO ACTION,
    nivel           TINYINT       NOT NULL DEFAULT 0 CONSTRAINT CK_CoreDep_Nivel CHECK (nivel BETWEEN 0 AND 3),
    estado          TINYINT       NOT NULL DEFAULT 1 CONSTRAINT CK_CoreDep_Estado CHECK (estado IN (0,1)),
    icono           NVARCHAR(50)  NULL DEFAULT 'fa-building',
    color_badge     NVARCHAR(7)   NULL DEFAULT '#0056b3',
    fecha_creacion  DATETIME2     NOT NULL DEFAULT GETDATE()
);
GO

CREATE TABLE CORE_Usuarios (
    id_usuario           INT           IDENTITY(1,1) PRIMARY KEY,
    nombre_usuario       NVARCHAR(50)  NOT NULL CONSTRAINT UQ_CoreU_Usuario UNIQUE,
    correo               NVARCHAR(150) NOT NULL CONSTRAINT UQ_CoreU_Correo  UNIQUE,
    nombre_completo      NVARCHAR(150) NOT NULL,
    hash_contrasena      NVARCHAR(512) NOT NULL,
    salt                 NVARCHAR(64)  NOT NULL DEFAULT '',
    id_departamento      INT           NULL CONSTRAINT FK_CoreU_Depto REFERENCES CORE_Departamentos(id_departamento) ON DELETE SET NULL,
    -- 0=Operador 1=Analista 2=Director 3=Gerente 4=SuperAdmin
    nivel_jerarquia      TINYINT       NOT NULL DEFAULT 0 CONSTRAINT CK_CoreU_Nivel CHECK (nivel_jerarquia BETWEEN 0 AND 4),
    estado               TINYINT       NOT NULL DEFAULT 1 CONSTRAINT CK_CoreU_Estado CHECK (estado IN (0,1)),
    requiere_mfa         BIT           NOT NULL DEFAULT 0,
    mfa_secreto          NVARCHAR(32)  NULL,
    requiere_cambio_pass BIT           NOT NULL DEFAULT 0,
    intentos_fallidos    TINYINT       NOT NULL DEFAULT 0,
    fecha_bloqueo        DATETIME2     NULL,
    minutos_bloqueo      SMALLINT      NOT NULL DEFAULT 30,
    tema_preferido       NVARCHAR(20)  NOT NULL DEFAULT 'light' CONSTRAINT CK_CoreU_Tema CHECK (tema_preferido IN ('light','dark','corporate')),
    cedula               NVARCHAR(20)  NULL,
    foto                 NVARCHAR(255) NULL,
    fecha_creacion       DATETIME2     NOT NULL DEFAULT GETDATE(),
    fecha_modificacion   DATETIME2     NULL
);
GO

CREATE TABLE CORE_Roles (
    id_rol          INT           IDENTITY(1,1) PRIMARY KEY,
    codigo          NVARCHAR(30)  NOT NULL CONSTRAINT UQ_CoreRol_Codigo UNIQUE,
    nombre          NVARCHAR(100) NOT NULL,
    descripcion     NVARCHAR(255) NULL,
    id_departamento INT           NULL CONSTRAINT FK_CoreRol_Depto REFERENCES CORE_Departamentos(id_departamento) ON DELETE SET NULL,
    nivel_jerarquia TINYINT       NOT NULL DEFAULT 0,
    estado          TINYINT       NOT NULL DEFAULT 1 CONSTRAINT CK_CoreRol_Estado CHECK (estado IN (0,1)),
    fecha_creacion  DATETIME2     NOT NULL DEFAULT GETDATE()
);
GO

CREATE TABLE CORE_Usuarios_Roles (
    id_usr_rol       INT       IDENTITY(1,1) PRIMARY KEY,
    id_usuario       INT       NOT NULL CONSTRAINT FK_CoreUR_Usuario REFERENCES CORE_Usuarios(id_usuario) ON DELETE CASCADE,
    id_rol           INT       NOT NULL CONSTRAINT FK_CoreUR_Rol     REFERENCES CORE_Roles(id_rol) ON DELETE CASCADE,
    fecha_asignacion DATETIME2 NOT NULL DEFAULT GETDATE(),
    asignado_por     INT       NULL CONSTRAINT FK_CoreUR_AsigPor REFERENCES CORE_Usuarios(id_usuario) ON DELETE NO ACTION,
    estado           TINYINT   NOT NULL DEFAULT 1 CONSTRAINT CK_CoreUR_Estado CHECK (estado IN (0,1)),
    CONSTRAINT UQ_CoreUR UNIQUE (id_usuario, id_rol)
);
GO

-- Jerarquía MOIS 4 niveles:
--   id_modulo=X, opcion=0, items=0, subitems=0 → cabecera de módulo
--   id_modulo=X, opcion=N, items=0, subitems=0 → cabecera de opción
--   id_modulo=X, opcion=N, items=M, subitems=0 → pantalla/formulario
--   id_modulo=X, opcion=N, items=M, subitems=K → subacción específica
CREATE TABLE CORE_Menu_Nodos (
    id_nodo       INT           IDENTITY(1,1) PRIMARY KEY,
    id_modulo     TINYINT       NOT NULL,
    opcion        TINYINT       NOT NULL DEFAULT 0,
    items         TINYINT       NOT NULL DEFAULT 0,
    subitems      SMALLINT      NOT NULL DEFAULT 0,
    descripcion   NVARCHAR(200) NOT NULL,
    url_ruta      NVARCHAR(150) NULL,
    icono         NVARCHAR(50)  NULL DEFAULT 'fa-circle',
    orden         SMALLINT      NOT NULL DEFAULT 0,
    requiere_mfa  BIT           NOT NULL DEFAULT 0,
    target_spa    BIT           NOT NULL DEFAULT 1,
    estado        TINYINT       NOT NULL DEFAULT 1 CONSTRAINT CK_MN_Estado CHECK (estado IN (0,1)),
    fecha_creacion DATETIME2    NOT NULL DEFAULT GETDATE(),
    CONSTRAINT UQ_MenuNodo UNIQUE (id_modulo, opcion, items, subitems)
);
GO

-- Permiso de un rol a un nodo del menú MOIS
CREATE TABLE CORE_Permisos_Nodo (
    id_perm_nodo     INT       IDENTITY(1,1) PRIMARY KEY,
    id_rol           INT       NOT NULL CONSTRAINT FK_PN_Rol REFERENCES CORE_Roles(id_rol) ON DELETE CASCADE,
    id_modulo        TINYINT   NOT NULL,
    opcion           TINYINT   NOT NULL DEFAULT 0,
    items            TINYINT   NOT NULL DEFAULT 0,
    subitems         SMALLINT  NOT NULL DEFAULT 0,
    -- 1=Leer 2=Leer+Crear 3=Leer+Crear+Editar 4=Administrar
    nivel_crud       TINYINT   NOT NULL DEFAULT 1 CONSTRAINT CK_PN_CRUD CHECK (nivel_crud BETWEEN 1 AND 4),
    acceso           TINYINT   NOT NULL DEFAULT 1 CONSTRAINT CK_PN_Acceso CHECK (acceso IN (0,1)),
    estado           TINYINT   NOT NULL DEFAULT 1 CONSTRAINT CK_PN_Estado CHECK (estado IN (0,1)),
    fecha_asignacion DATETIME2 NOT NULL DEFAULT GETDATE(),
    asignado_por     INT       NULL CONSTRAINT FK_PN_AsigPor REFERENCES CORE_Usuarios(id_usuario) ON DELETE NO ACTION,
    CONSTRAINT FK_PN_Nodo FOREIGN KEY (id_modulo, opcion, items, subitems)
        REFERENCES CORE_Menu_Nodos(id_modulo, opcion, items, subitems),
    CONSTRAINT UQ_PermNodo UNIQUE (id_rol, id_modulo, opcion, items, subitems)
);
GO

-- Catálogo de formularios para permisos granulares (equivalente per_formulario del viejo sistema)
CREATE TABLE CORE_Formularios (
    id_formulario  INT           IDENTITY(1,1) PRIMARY KEY,
    codigo         NVARCHAR(50)  NOT NULL CONSTRAINT UQ_CoreForm_Codigo UNIQUE,
    nombre         NVARCHAR(150) NOT NULL,
    modulo         NVARCHAR(30)  NOT NULL,
    config_json    NVARCHAR(MAX) NULL,
    estado         TINYINT       NOT NULL DEFAULT 1 CONSTRAINT CK_CoreFrm_Estado CHECK (estado IN (0,1)),
    fecha_creacion DATETIME2     NOT NULL DEFAULT GETDATE()
);
GO

CREATE TABLE CORE_Formularios_Permisos (
    id_form_perm  INT     IDENTITY(1,1) PRIMARY KEY,
    id_rol        INT     NOT NULL CONSTRAINT FK_CoreFormPerm_Rol  REFERENCES CORE_Roles(id_rol) ON DELETE CASCADE,
    id_formulario INT     NOT NULL CONSTRAINT FK_CoreFormPerm_Form REFERENCES CORE_Formularios(id_formulario) ON DELETE CASCADE,
    nivel_crud    TINYINT NOT NULL DEFAULT 1 CONSTRAINT CK_CoreFrmP_CRUD CHECK (nivel_crud BETWEEN 1 AND 4),
    estado        TINYINT NOT NULL DEFAULT 1 CONSTRAINT CK_CoreFrmP_Estado CHECK (estado IN (0,1)),
    CONSTRAINT UQ_CoreFormPerm UNIQUE (id_rol, id_formulario)
);
GO

-- estado: 1=sesión activa | 0=revocada/expirada
CREATE TABLE CORE_Sesiones (
    id_sesion              BIGINT        IDENTITY(1,1) PRIMARY KEY,
    id_usuario             INT           NOT NULL CONSTRAINT FK_CoreSes_Usuario REFERENCES CORE_Usuarios(id_usuario) ON DELETE CASCADE,
    token                  NVARCHAR(128) NOT NULL CONSTRAINT UQ_CoreSes_Token UNIQUE,
    ip_address             NVARCHAR(45)  NULL,
    user_agent             NVARCHAR(512) NULL,
    fecha_inicio           DATETIME2     NOT NULL DEFAULT GETDATE(),
    fecha_expira           DATETIME2     NOT NULL,
    fecha_ultima_actividad DATETIME2     NOT NULL DEFAULT GETDATE(),
    estado                 TINYINT       NOT NULL DEFAULT 1 CONSTRAINT CK_CoreSes_Estado CHECK (estado IN (0,1)),
    fecha_revocacion       DATETIME2     NULL
);
GO

CREATE TABLE CORE_Auditoria (
    id_auditoria   BIGINT        IDENTITY(1,1) PRIMARY KEY,
    id_usuario     INT           NULL CONSTRAINT FK_CoreAud_Usuario REFERENCES CORE_Usuarios(id_usuario) ON DELETE SET NULL,
    nombre_usuario NVARCHAR(50)  NULL,
    modulo         NVARCHAR(30)  NOT NULL DEFAULT 'CORE',
    operacion      NVARCHAR(30)  NOT NULL,
    tabla_afectada NVARCHAR(100) NULL,
    id_registro    NVARCHAR(50)  NULL,
    datos_antes    NVARCHAR(MAX) NULL,
    datos_despues  NVARCHAR(MAX) NULL,
    ip_address     NVARCHAR(45)  NULL,
    resultado      NVARCHAR(20)  NOT NULL DEFAULT 'EXITO',
    detalle        NVARCHAR(500) NULL,
    fecha_registro DATETIME2     NOT NULL DEFAULT GETDATE(),
    fecha_purga    DATETIME2     NULL,
    estado         TINYINT       NOT NULL DEFAULT 1 CONSTRAINT CK_CoreAud_Estado CHECK (estado IN (0,1))
);
GO

CREATE TABLE CORE_Notificaciones (
    id_notif       BIGINT        IDENTITY(1,1) PRIMARY KEY,
    id_usuario     INT           NOT NULL CONSTRAINT FK_CoreNotif_Usuario REFERENCES CORE_Usuarios(id_usuario) ON DELETE CASCADE,
    titulo         NVARCHAR(150) NOT NULL,
    mensaje        NVARCHAR(500) NOT NULL,
    tipo           NVARCHAR(20)  NOT NULL DEFAULT 'info'  CONSTRAINT CK_CoreNtf_Tipo  CHECK (tipo IN ('info','success','warning','danger')),
    prioridad      TINYINT       NOT NULL DEFAULT 2        CONSTRAINT CK_CoreNtf_Prior CHECK (prioridad BETWEEN 1 AND 3),
    leida          BIT           NOT NULL DEFAULT 0,
    url_accion     NVARCHAR(255) NULL,
    fecha_creacion DATETIME2     NOT NULL DEFAULT GETDATE(),
    fecha_lectura  DATETIME2     NULL,
    estado         TINYINT       NOT NULL DEFAULT 1 CONSTRAINT CK_CoreNtf_Estado CHECK (estado IN (0,1))
);
GO

CREATE TABLE CORE_Contrasenas_Hist (
    id_hist         INT           IDENTITY(1,1) PRIMARY KEY,
    id_usuario      INT           NOT NULL CONSTRAINT FK_CorePassHist_Usuario REFERENCES CORE_Usuarios(id_usuario) ON DELETE CASCADE,
    hash_contrasena NVARCHAR(512) NOT NULL,
    salt            NVARCHAR(64)  NOT NULL DEFAULT '',
    fecha_cambio    DATETIME2     NOT NULL DEFAULT GETDATE(),
    estado          TINYINT       NOT NULL DEFAULT 1 CONSTRAINT CK_CorePH_Estado CHECK (estado IN (0,1))
);
GO

CREATE TABLE CORE_Config (
    id_config   INT            IDENTITY(1,1) PRIMARY KEY,
    modulo      NVARCHAR(30)   NOT NULL DEFAULT 'CORE',
    clave       NVARCHAR(100)  NOT NULL,
    valor       NVARCHAR(1000) NULL,
    tipo        NVARCHAR(20)   NOT NULL DEFAULT 'string' CONSTRAINT CK_CoreCfg_Tipo CHECK (tipo IN ('string','int','bool','json')),
    descripcion NVARCHAR(255)  NULL,
    fecha_mod   DATETIME2      NOT NULL DEFAULT GETDATE(),
    estado      TINYINT        NOT NULL DEFAULT 1 CONSTRAINT CK_CoreCfg_Estado CHECK (estado IN (0,1)),
    CONSTRAINT UQ_CoreConfig UNIQUE (modulo, clave)
);
GO

-- ============================================================
-- SECCIÓN 2: TH_ (Talento Humano)
-- ============================================================

IF OBJECT_ID('TH_Novedades_Medicas','U') IS NOT NULL DROP TABLE TH_Novedades_Medicas;
IF OBJECT_ID('TH_Adendas','U')           IS NOT NULL DROP TABLE TH_Adendas;
IF OBJECT_ID('TH_Contratos','U')         IS NOT NULL DROP TABLE TH_Contratos;
IF OBJECT_ID('TH_Auditoria','U')         IS NOT NULL DROP TABLE TH_Auditoria;
IF OBJECT_ID('TH_Empleados','U')         IS NOT NULL DROP TABLE TH_Empleados;
GO

CREATE TABLE TH_Empleados (
    id_empleado      INT            IDENTITY(1,1) PRIMARY KEY,
    id_usuario       INT            NULL CONSTRAINT UQ_TH_Usuario UNIQUE
                                         CONSTRAINT FK_TH_Usuario REFERENCES CORE_Usuarios(id_usuario) ON DELETE SET NULL,
    cedula           NVARCHAR(20)   NOT NULL CONSTRAINT UQ_TH_Cedula UNIQUE,
    nombres          NVARCHAR(100)  NOT NULL,
    apellidos        NVARCHAR(100)  NOT NULL,
    correo           NVARCHAR(150)  NULL,
    telefono         NVARCHAR(20)   NULL,
    fecha_nacimiento DATE           NULL,
    genero           CHAR(1)        NULL CONSTRAINT CK_TH_Genero   CHECK (genero IN ('M','F','O')),
    estado_civil     NVARCHAR(20)   NULL CONSTRAINT CK_TH_EstCivil CHECK (estado_civil IN ('Soltero','Casado','Divorciado','Viudo','Union Libre') OR estado_civil IS NULL),
    id_departamento  INT            NULL CONSTRAINT FK_TH_Depto REFERENCES CORE_Departamentos(id_departamento) ON DELETE SET NULL,
    cargo            NVARCHAR(100)  NULL,
    fecha_ingreso    DATE           NULL,
    estado           TINYINT        NOT NULL DEFAULT 1 CONSTRAINT CK_TH_Emp_Estado CHECK (estado IN (0,1)),
    foto             NVARCHAR(255)  NULL,
    observaciones    NVARCHAR(1000) NULL,
    fecha_creacion   DATETIME2      NOT NULL DEFAULT GETDATE()
);
GO

-- estado_contrato: flujo de trabajo | estado: validez del registro
CREATE TABLE TH_Contratos (
    id_contrato     INT            IDENTITY(1,1) PRIMARY KEY,
    id_empleado     INT            NOT NULL CONSTRAINT FK_TH_Contrato_Emp  REFERENCES TH_Empleados(id_empleado) ON DELETE CASCADE,
    tipo_contrato   NVARCHAR(30)   NOT NULL CONSTRAINT CK_TH_Cont_Tipo     CHECK (tipo_contrato IN ('Nombramiento','Contrato','Ocasional')),
    fecha_inicio    DATE           NOT NULL,
    fecha_fin       DATE           NULL,
    salario         DECIMAL(10,2)  NOT NULL,
    cargo           NVARCHAR(100)  NOT NULL,
    id_departamento INT            NULL CONSTRAINT FK_TH_Contrato_Depto REFERENCES CORE_Departamentos(id_departamento) ON DELETE SET NULL,
    estado_contrato NVARCHAR(20)   NOT NULL DEFAULT 'Vigente' CONSTRAINT CK_TH_Cont_EstCont CHECK (estado_contrato IN ('Vigente','Finalizado','Anulado')),
    estado          TINYINT        NOT NULL DEFAULT 1 CONSTRAINT CK_TH_Cont_Estado CHECK (estado IN (0,1)),
    observaciones   NVARCHAR(1000) NULL,
    fecha_creacion  DATETIME2      NOT NULL DEFAULT GETDATE(),
    creado_por      INT            NULL CONSTRAINT FK_TH_Contrato_Crea REFERENCES CORE_Usuarios(id_usuario) ON DELETE NO ACTION
);
GO

CREATE TABLE TH_Adendas (
    id_adenda        INT           IDENTITY(1,1) PRIMARY KEY,
    id_contrato      INT           NOT NULL CONSTRAINT FK_TH_Adenda_Contrato REFERENCES TH_Contratos(id_contrato) ON DELETE CASCADE,
    descripcion      NVARCHAR(255) NOT NULL,
    campo_modificado NVARCHAR(100) NOT NULL,
    valor_anterior   NVARCHAR(500) NULL,
    valor_nuevo      NVARCHAR(500) NOT NULL,
    fecha_vigencia   DATE          NOT NULL,
    estado           TINYINT       NOT NULL DEFAULT 1 CONSTRAINT CK_TH_Aden_Estado CHECK (estado IN (0,1)),
    fecha_creacion   DATETIME2     NOT NULL DEFAULT GETDATE(),
    creado_por       INT           NULL CONSTRAINT FK_TH_Adenda_Crea REFERENCES CORE_Usuarios(id_usuario) ON DELETE NO ACTION
);
GO

-- estado_novedad: flujo de trabajo | estado: validez del registro
CREATE TABLE TH_Novedades_Medicas (
    id_novedad     INT           IDENTITY(1,1) PRIMARY KEY,
    id_empleado    INT           NOT NULL CONSTRAINT FK_TH_Nov_Emp  REFERENCES TH_Empleados(id_empleado) ON DELETE CASCADE,
    tipo_novedad   NVARCHAR(30)  NOT NULL CONSTRAINT CK_TH_Nov_Tipo CHECK (tipo_novedad IN ('Alta','Baja','Certificado','Licencia','Permiso')),
    descripcion    NVARCHAR(500) NULL,
    fecha_inicio   DATE          NOT NULL,
    fecha_fin      DATE          NULL,
    dias           SMALLINT      NULL,
    documento_ref  NVARCHAR(100) NULL,
    estado_novedad NVARCHAR(20)  NOT NULL DEFAULT 'Activa' CONSTRAINT CK_TH_Nov_EstNov CHECK (estado_novedad IN ('Activa','Cerrada','Anulada')),
    estado         TINYINT       NOT NULL DEFAULT 1 CONSTRAINT CK_TH_Nov_Estado CHECK (estado IN (0,1)),
    fecha_creacion DATETIME2     NOT NULL DEFAULT GETDATE(),
    creado_por     INT           NULL CONSTRAINT FK_TH_Nov_Crea REFERENCES CORE_Usuarios(id_usuario) ON DELETE NO ACTION
);
GO

CREATE TABLE TH_Auditoria (
    id_auditoria   BIGINT        IDENTITY(1,1) PRIMARY KEY,
    id_usuario     INT           NULL CONSTRAINT FK_TH_Aud_Usuario REFERENCES CORE_Usuarios(id_usuario) ON DELETE SET NULL,
    operacion      NVARCHAR(20)  NOT NULL,
    tabla          NVARCHAR(100) NOT NULL,
    id_registro    INT           NULL,
    datos_antes    NVARCHAR(MAX) NULL,
    datos_despues  NVARCHAR(MAX) NULL,
    ip_address     NVARCHAR(45)  NULL,
    estado         TINYINT       NOT NULL DEFAULT 1 CONSTRAINT CK_TH_Aud_Estado CHECK (estado IN (0,1)),
    fecha_registro DATETIME2     NOT NULL DEFAULT GETDATE()
);
GO

-- ============================================================
-- SECCIÓN 3: BIT_ (Bitácoras)
-- ============================================================

IF OBJECT_ID('BIT_Auditoria','U')  IS NOT NULL DROP TABLE BIT_Auditoria;
IF OBJECT_ID('BIT_Archivos','U')   IS NOT NULL DROP TABLE BIT_Archivos;
IF OBJECT_ID('BIT_Eventos','U')    IS NOT NULL DROP TABLE BIT_Eventos;
IF OBJECT_ID('BIT_Categorias','U') IS NOT NULL DROP TABLE BIT_Categorias;
GO

CREATE TABLE BIT_Categorias (
    id_categoria INT           IDENTITY(1,1) PRIMARY KEY,
    codigo       NVARCHAR(20)  NOT NULL CONSTRAINT UQ_BIT_Cat_Codigo UNIQUE,
    nombre       NVARCHAR(100) NOT NULL,
    descripcion  NVARCHAR(255) NULL,
    color        NVARCHAR(7)   NOT NULL DEFAULT '#0056b3',
    estado       TINYINT       NOT NULL DEFAULT 1 CONSTRAINT CK_BIT_Cat_Estado CHECK (estado IN (0,1))
);
GO

-- estado_evento: flujo de trabajo | estado: validez del registro
CREATE TABLE BIT_Eventos (
    id_evento       INT            IDENTITY(1,1) PRIMARY KEY,
    id_categoria    INT            NULL CONSTRAINT FK_BIT_Ev_Cat   REFERENCES BIT_Categorias(id_categoria) ON DELETE SET NULL,
    titulo          NVARCHAR(200)  NOT NULL,
    descripcion     NVARCHAR(MAX)  NULL,
    tipo            NVARCHAR(30)   NOT NULL DEFAULT 'Registro',
    prioridad       TINYINT        NOT NULL DEFAULT 2 CONSTRAINT CK_BIT_Ev_Prior CHECK (prioridad BETWEEN 1 AND 3),
    estado_evento   NVARCHAR(30)   NOT NULL DEFAULT 'Pendiente' CONSTRAINT CK_BIT_Ev_EstEv CHECK (estado_evento IN ('Pendiente','En Proceso','Cerrado')),
    estado          TINYINT        NOT NULL DEFAULT 1 CONSTRAINT CK_BIT_Ev_Estado CHECK (estado IN (0,1)),
    id_departamento INT            NULL CONSTRAINT FK_BIT_Ev_Depto REFERENCES CORE_Departamentos(id_departamento) ON DELETE SET NULL,
    id_usuario      INT            NULL CONSTRAINT FK_BIT_Ev_User  REFERENCES CORE_Usuarios(id_usuario) ON DELETE SET NULL,
    fecha_evento    DATETIME2      NOT NULL DEFAULT GETDATE(),
    fecha_cierre    DATETIME2      NULL,
    observaciones   NVARCHAR(1000) NULL,
    fecha_creacion  DATETIME2      NOT NULL DEFAULT GETDATE()
);
GO

CREATE TABLE BIT_Archivos (
    id_archivo   INT           IDENTITY(1,1) PRIMARY KEY,
    id_evento    INT           NOT NULL CONSTRAINT FK_BIT_Arch_Evento REFERENCES BIT_Eventos(id_evento) ON DELETE CASCADE,
    nombre       NVARCHAR(255) NOT NULL,
    ruta         NVARCHAR(500) NOT NULL,
    tipo_mime    NVARCHAR(100) NULL,
    tamanio      INT           NULL,
    estado       TINYINT       NOT NULL DEFAULT 1 CONSTRAINT CK_BIT_Arch_Estado CHECK (estado IN (0,1)),
    fecha_subida DATETIME2     NOT NULL DEFAULT GETDATE(),
    subido_por   INT           NULL CONSTRAINT FK_BIT_Arch_User REFERENCES CORE_Usuarios(id_usuario) ON DELETE SET NULL
);
GO

CREATE TABLE BIT_Auditoria (
    id_auditoria   BIGINT        IDENTITY(1,1) PRIMARY KEY,
    id_usuario     INT           NULL CONSTRAINT FK_BIT_Aud_Usuario REFERENCES CORE_Usuarios(id_usuario) ON DELETE SET NULL,
    operacion      NVARCHAR(20)  NOT NULL,
    tabla          NVARCHAR(100) NOT NULL,
    id_registro    INT           NULL,
    datos_antes    NVARCHAR(MAX) NULL,
    datos_despues  NVARCHAR(MAX) NULL,
    estado         TINYINT       NOT NULL DEFAULT 1 CONSTRAINT CK_BIT_Aud_Estado CHECK (estado IN (0,1)),
    fecha_registro DATETIME2     NOT NULL DEFAULT GETDATE()
);
GO

-- ============================================================
-- SECCIÓN 4: BIENES_ (Control de Bienes)
-- ============================================================

IF OBJECT_ID('BIENES_Auditoria','U')   IS NOT NULL DROP TABLE BIENES_Auditoria;
IF OBJECT_ID('BIENES_Movimientos','U') IS NOT NULL DROP TABLE BIENES_Movimientos;
IF OBJECT_ID('BIENES_Activos','U')     IS NOT NULL DROP TABLE BIENES_Activos;
IF OBJECT_ID('BIENES_Categorias','U')  IS NOT NULL DROP TABLE BIENES_Categorias;
GO

CREATE TABLE BIENES_Categorias (
    id_categoria INT           IDENTITY(1,1) PRIMARY KEY,
    codigo       NVARCHAR(20)  NOT NULL CONSTRAINT UQ_BIENES_Cat_Codigo UNIQUE,
    nombre       NVARCHAR(100) NOT NULL,
    descripcion  NVARCHAR(255) NULL,
    estado       TINYINT       NOT NULL DEFAULT 1 CONSTRAINT CK_BIENES_Cat_Estado CHECK (estado IN (0,1))
);
GO

-- estado_bien: flujo de trabajo | estado: validez del registro
CREATE TABLE BIENES_Activos (
    id_activo         INT           IDENTITY(1,1) PRIMARY KEY,
    codigo            NVARCHAR(50)  NOT NULL CONSTRAINT UQ_BIENES_Activo_Codigo UNIQUE,
    nombre            NVARCHAR(200) NOT NULL,
    descripcion       NVARCHAR(500) NULL,
    id_categoria      INT           NULL CONSTRAINT FK_BIENES_Activo_Cat   REFERENCES BIENES_Categorias(id_categoria) ON DELETE SET NULL,
    id_departamento   INT           NULL CONSTRAINT FK_BIENES_Activo_Depto REFERENCES CORE_Departamentos(id_departamento) ON DELETE SET NULL,
    id_custodio       INT           NULL CONSTRAINT FK_BIENES_Activo_Cust  REFERENCES CORE_Usuarios(id_usuario) ON DELETE SET NULL,
    estado_bien       NVARCHAR(30)  NOT NULL DEFAULT 'Activo' CONSTRAINT CK_BIENES_Act_EstBien CHECK (estado_bien IN ('Activo','Baja','En Reparacion','Transferido')),
    estado            TINYINT       NOT NULL DEFAULT 1 CONSTRAINT CK_BIENES_Act_Estado CHECK (estado IN (0,1)),
    valor_adquisicion DECIMAL(12,2) NULL,
    fecha_adquisicion DATE          NULL,
    vida_util         SMALLINT      NULL,
    numero_serie      NVARCHAR(100) NULL,
    marca             NVARCHAR(100) NULL,
    modelo            NVARCHAR(100) NULL,
    ubicacion         NVARCHAR(200) NULL,
    foto              NVARCHAR(255) NULL,
    fecha_creacion    DATETIME2     NOT NULL DEFAULT GETDATE()
);
GO

CREATE TABLE BIENES_Movimientos (
    id_movimiento     INT           IDENTITY(1,1) PRIMARY KEY,
    id_activo         INT           NOT NULL CONSTRAINT FK_BIENES_Mov_Activo  REFERENCES BIENES_Activos(id_activo) ON DELETE CASCADE,
    tipo_movimiento   NVARCHAR(30)  NOT NULL CONSTRAINT CK_BIENES_Mov_Tipo    CHECK (tipo_movimiento IN ('Asignacion','Transferencia','Baja','Devolucion','Reparacion')),
    id_depto_origen   INT           NULL CONSTRAINT FK_BIENES_Mov_DepOri  REFERENCES CORE_Departamentos(id_departamento) ON DELETE NO ACTION,
    id_depto_destino  INT           NULL CONSTRAINT FK_BIENES_Mov_DepDest REFERENCES CORE_Departamentos(id_departamento) ON DELETE NO ACTION,
    id_custodio_ant   INT           NULL CONSTRAINT FK_BIENES_Mov_CustAnt REFERENCES CORE_Usuarios(id_usuario) ON DELETE NO ACTION,
    id_custodio_nuevo INT           NULL CONSTRAINT FK_BIENES_Mov_CustNvo REFERENCES CORE_Usuarios(id_usuario) ON DELETE NO ACTION,
    observaciones     NVARCHAR(500) NULL,
    acta_referencia   NVARCHAR(100) NULL,
    estado            TINYINT       NOT NULL DEFAULT 1 CONSTRAINT CK_BIENES_Mov_Estado CHECK (estado IN (0,1)),
    fecha_movimiento  DATE          NOT NULL DEFAULT CAST(GETDATE() AS DATE),
    fecha_creacion    DATETIME2     NOT NULL DEFAULT GETDATE(),
    creado_por        INT           NULL CONSTRAINT FK_BIENES_Mov_CreaBy REFERENCES CORE_Usuarios(id_usuario) ON DELETE NO ACTION
);
GO

CREATE TABLE BIENES_Auditoria (
    id_auditoria   BIGINT        IDENTITY(1,1) PRIMARY KEY,
    id_usuario     INT           NULL CONSTRAINT FK_BIENES_Aud_User REFERENCES CORE_Usuarios(id_usuario) ON DELETE SET NULL,
    operacion      NVARCHAR(20)  NOT NULL,
    tabla          NVARCHAR(100) NOT NULL,
    id_registro    INT           NULL,
    datos_antes    NVARCHAR(MAX) NULL,
    datos_despues  NVARCHAR(MAX) NULL,
    estado         TINYINT       NOT NULL DEFAULT 1 CONSTRAINT CK_BIENES_Aud_Estado CHECK (estado IN (0,1)),
    fecha_registro DATETIME2     NOT NULL DEFAULT GETDATE()
);
GO

-- ============================================================
-- SECCIÓN 5: ACCESO_ (Control de Acceso)
-- ============================================================

IF OBJECT_ID('ACCESO_Auditoria','U')  IS NOT NULL DROP TABLE ACCESO_Auditoria;
IF OBJECT_ID('ACCESO_Registros','U')  IS NOT NULL DROP TABLE ACCESO_Registros;
IF OBJECT_ID('ACCESO_Vehiculos','U')  IS NOT NULL DROP TABLE ACCESO_Vehiculos;
IF OBJECT_ID('ACCESO_Visitantes','U') IS NOT NULL DROP TABLE ACCESO_Visitantes;
GO

CREATE TABLE ACCESO_Visitantes (
    id_visitante   INT           IDENTITY(1,1) PRIMARY KEY,
    cedula         NVARCHAR(20)  NOT NULL CONSTRAINT UQ_ACCESO_Visit_Cedula UNIQUE,
    nombres        NVARCHAR(100) NOT NULL,
    apellidos      NVARCHAR(100) NOT NULL,
    empresa        NVARCHAR(150) NULL,
    telefono       NVARCHAR(20)  NULL,
    correo         NVARCHAR(150) NULL,
    foto           NVARCHAR(255) NULL,
    estado         TINYINT       NOT NULL DEFAULT 1 CONSTRAINT CK_ACCESO_Visit_Estado CHECK (estado IN (0,1)),
    fecha_creacion DATETIME2     NOT NULL DEFAULT GETDATE()
);
GO

CREATE TABLE ACCESO_Vehiculos (
    id_vehiculo    INT           IDENTITY(1,1) PRIMARY KEY,
    placa          NVARCHAR(20)  NOT NULL CONSTRAINT UQ_ACCESO_Veh_Placa UNIQUE,
    tipo           NVARCHAR(50)  NULL,
    marca          NVARCHAR(50)  NULL,
    modelo         NVARCHAR(50)  NULL,
    color          NVARCHAR(30)  NULL,
    id_visitante   INT           NULL CONSTRAINT FK_ACCESO_Veh_Visit REFERENCES ACCESO_Visitantes(id_visitante) ON DELETE SET NULL,
    estado         TINYINT       NOT NULL DEFAULT 1 CONSTRAINT CK_ACCESO_Veh_Estado CHECK (estado IN (0,1)),
    fecha_creacion DATETIME2     NOT NULL DEFAULT GETDATE()
);
GO

-- estado_registro: flujo de trabajo | estado: validez del registro
CREATE TABLE ACCESO_Registros (
    id_registro     BIGINT        IDENTITY(1,1) PRIMARY KEY,
    id_visitante    INT           NULL CONSTRAINT FK_ACCESO_Reg_Visit REFERENCES ACCESO_Visitantes(id_visitante) ON DELETE SET NULL,
    id_vehiculo     INT           NULL CONSTRAINT FK_ACCESO_Reg_Veh   REFERENCES ACCESO_Vehiculos(id_vehiculo)   ON DELETE SET NULL,
    tipo_acceso     NVARCHAR(20)  NOT NULL CONSTRAINT CK_ACCESO_Reg_Tipo    CHECK (tipo_acceso IN ('Entrada','Salida')),
    punto_control   NVARCHAR(100) NULL,
    id_departamento INT           NULL CONSTRAINT FK_ACCESO_Reg_Depto REFERENCES CORE_Departamentos(id_departamento) ON DELETE SET NULL,
    persona_visita  NVARCHAR(150) NULL,
    motivo          NVARCHAR(500) NULL,
    estado_registro NVARCHAR(20)  NOT NULL DEFAULT 'Activo' CONSTRAINT CK_ACCESO_Reg_EstReg CHECK (estado_registro IN ('Activo','Finalizado')),
    estado          TINYINT       NOT NULL DEFAULT 1 CONSTRAINT CK_ACCESO_Reg_Estado CHECK (estado IN (0,1)),
    id_operador     INT           NULL CONSTRAINT FK_ACCESO_Reg_Oper  REFERENCES CORE_Usuarios(id_usuario) ON DELETE SET NULL,
    fecha_hora      DATETIME2     NOT NULL DEFAULT GETDATE(),
    observaciones   NVARCHAR(500) NULL
);
GO

CREATE TABLE ACCESO_Auditoria (
    id_auditoria   BIGINT        IDENTITY(1,1) PRIMARY KEY,
    id_usuario     INT           NULL CONSTRAINT FK_ACCESO_Aud_User REFERENCES CORE_Usuarios(id_usuario) ON DELETE SET NULL,
    operacion      NVARCHAR(20)  NOT NULL,
    tabla          NVARCHAR(100) NOT NULL,
    id_registro    BIGINT        NULL,
    datos_antes    NVARCHAR(MAX) NULL,
    datos_despues  NVARCHAR(MAX) NULL,
    estado         TINYINT       NOT NULL DEFAULT 1 CONSTRAINT CK_ACCESO_Aud_Estado CHECK (estado IN (0,1)),
    fecha_registro DATETIME2     NOT NULL DEFAULT GETDATE()
);
GO

-- ============================================================
-- SECCIÓN 6: ÍNDICES
-- ============================================================
SET QUOTED_IDENTIFIER ON;
GO

CREATE UNIQUE NONCLUSTERED INDEX UQ_CoreU_Cedula     ON CORE_Usuarios(cedula)                    WHERE cedula IS NOT NULL;
CREATE NONCLUSTERED INDEX IX_CoreDepto_Padre         ON CORE_Departamentos(id_padre)             WHERE id_padre IS NOT NULL;
CREATE NONCLUSTERED INDEX IX_CoreU_Correo            ON CORE_Usuarios(correo)                    WHERE estado = 1;
CREATE NONCLUSTERED INDEX IX_CoreU_Bloqueo           ON CORE_Usuarios(fecha_bloqueo)             WHERE fecha_bloqueo IS NOT NULL;
CREATE NONCLUSTERED INDEX IX_CoreU_Depto             ON CORE_Usuarios(id_departamento);
CREATE NONCLUSTERED INDEX IX_CoreSes_Token           ON CORE_Sesiones(token)                     WHERE estado = 1;
CREATE NONCLUSTERED INDEX IX_CoreSes_User            ON CORE_Sesiones(id_usuario)                WHERE estado = 1;
CREATE NONCLUSTERED INDEX IX_CoreSes_Expira          ON CORE_Sesiones(fecha_expira)              WHERE estado = 1;
CREATE NONCLUSTERED INDEX IX_CoreAud_Modulo          ON CORE_Auditoria(modulo, fecha_registro DESC);
CREATE NONCLUSTERED INDEX IX_CoreAud_User            ON CORE_Auditoria(id_usuario, fecha_registro DESC);
CREATE NONCLUSTERED INDEX IX_CoreNotif_User          ON CORE_Notificaciones(id_usuario, leida, fecha_creacion DESC);
-- Índices para MOIS (búsqueda por módulo y por rol)
CREATE NONCLUSTERED INDEX IX_MN_Modulo               ON CORE_Menu_Nodos(id_modulo, opcion, items, subitems) WHERE estado = 1;
CREATE NONCLUSTERED INDEX IX_PN_Rol                  ON CORE_Permisos_Nodo(id_rol)              WHERE acceso = 1 AND estado = 1;
CREATE NONCLUSTERED INDEX IX_PN_Nodo                 ON CORE_Permisos_Nodo(id_modulo, opcion, items, subitems) WHERE acceso = 1 AND estado = 1;
CREATE NONCLUSTERED INDEX IX_TH_Emp_Depto            ON TH_Empleados(id_departamento)           WHERE estado = 1;
CREATE NONCLUSTERED INDEX IX_TH_Emp_Estado           ON TH_Empleados(estado, id_departamento);
CREATE NONCLUSTERED INDEX IX_TH_Cont_Emp             ON TH_Contratos(id_empleado, estado_contrato);
CREATE NONCLUSTERED INDEX IX_TH_Cont_Vencer          ON TH_Contratos(fecha_fin, estado_contrato) WHERE estado_contrato = 'Vigente' AND fecha_fin IS NOT NULL;
CREATE NONCLUSTERED INDEX IX_BIT_Ev_Depto            ON BIT_Eventos(id_departamento, fecha_evento DESC);
CREATE NONCLUSTERED INDEX IX_BIT_Ev_Estado           ON BIT_Eventos(estado_evento, prioridad)   WHERE estado = 1;
CREATE NONCLUSTERED INDEX IX_BIENES_Act_Est          ON BIENES_Activos(estado_bien)             WHERE estado_bien = 'Activo' AND estado = 1;
CREATE NONCLUSTERED INDEX IX_BIENES_Act_Dep          ON BIENES_Activos(id_departamento);
CREATE NONCLUSTERED INDEX IX_ACCESO_Reg_Fech         ON ACCESO_Registros(fecha_hora DESC);
CREATE NONCLUSTERED INDEX IX_ACCESO_Reg_Tipo         ON ACCESO_Registros(tipo_acceso, fecha_hora DESC);
GO

-- ============================================================
-- SECCIÓN 7: STORED PROCEDURES
-- ============================================================

-- SP1: Autenticación — devuelve hash para password_verify() en PHP
CREATE PROCEDURE sp_Login
    @nombre_usuario  NVARCHAR(50),
    @resultado       NVARCHAR(30)  OUTPUT,
    @id_usuario      INT           OUTPUT,
    @hash_contrasena NVARCHAR(512) OUTPUT,
    @nivel_jerarquia TINYINT       OUTPUT,
    @req_cambio_pass BIT           OUTPUT,
    @nombre_completo NVARCHAR(150) OUTPUT,
    @tema_preferido  NVARCHAR(20)  OUTPUT,
    @id_departamento INT           OUTPUT
AS
BEGIN
    SET NOCOUNT ON;
    SET @id_usuario = NULL;
    SELECT @id_usuario=id_usuario, @hash_contrasena=hash_contrasena,
           @nivel_jerarquia=nivel_jerarquia, @req_cambio_pass=requiere_cambio_pass,
           @nombre_completo=nombre_completo, @tema_preferido=tema_preferido,
           @id_departamento=id_departamento
    FROM CORE_Usuarios WHERE nombre_usuario=@nombre_usuario;

    IF @id_usuario IS NULL BEGIN SET @resultado='NO_ENCONTRADO'; RETURN; END

    DECLARE @est TINYINT, @fb DATETIME2, @min SMALLINT, @int TINYINT;
    SELECT @est=estado, @fb=fecha_bloqueo, @min=minutos_bloqueo, @int=intentos_fallidos
    FROM CORE_Usuarios WHERE id_usuario=@id_usuario;

    IF @est=0 BEGIN SET @resultado='INACTIVO'; RETURN; END
    IF @fb IS NOT NULL AND GETDATE() < DATEADD(MINUTE,@min,@fb) BEGIN SET @resultado='BLOQUEADO'; RETURN; END
    IF @fb IS NOT NULL AND GETDATE() >= DATEADD(MINUTE,@min,@fb)
        UPDATE CORE_Usuarios SET intentos_fallidos=0, fecha_bloqueo=NULL WHERE id_usuario=@id_usuario;

    SET @resultado='OK';
END;
GO

-- SP2: Registrar fallo de login
CREATE PROCEDURE sp_RegistrarFalloLogin
    @nombre_usuario NVARCHAR(50),
    @ip_address     NVARCHAR(45) = NULL
AS
BEGIN
    SET NOCOUNT ON;
    DECLARE @id INT, @int TINYINT, @max INT;
    SELECT @id=id_usuario, @int=intentos_fallidos FROM CORE_Usuarios WHERE nombre_usuario=@nombre_usuario;
    IF @id IS NULL RETURN;
    SELECT @max=CAST(valor AS INT) FROM CORE_Config WHERE modulo='CORE' AND clave='LOGIN_MAX_INTENTOS' AND estado=1;
    IF @max IS NULL SET @max=5;
    SET @int=@int+1;
    IF @int>=@max
        UPDATE CORE_Usuarios SET intentos_fallidos=@int, fecha_bloqueo=GETDATE() WHERE id_usuario=@id;
    ELSE
        UPDATE CORE_Usuarios SET intentos_fallidos=@int WHERE id_usuario=@id;
    INSERT INTO CORE_Auditoria(id_usuario,modulo,operacion,ip_address,resultado,detalle)
    VALUES(@id,'CORE','LOGIN',@ip_address,'ERROR','Fallo intento '+CAST(@int AS NVARCHAR));
END;
GO

-- SP3: Cerrar sesión
CREATE PROCEDURE sp_Logout
    @token      NVARCHAR(128),
    @ip_address NVARCHAR(45) = NULL
AS
BEGIN
    SET NOCOUNT ON;
    DECLARE @id INT;
    SELECT @id=id_usuario FROM CORE_Sesiones WHERE token=@token;
    UPDATE CORE_Sesiones SET estado=0, fecha_revocacion=GETDATE() WHERE token=@token;
    IF @id IS NOT NULL
        INSERT INTO CORE_Auditoria(id_usuario,modulo,operacion,ip_address,resultado)
        VALUES(@id,'CORE','LOGOUT',@ip_address,'EXITO');
END;
GO

-- SP4: Cambiar contraseña con historial
-- Verificar reuso (password_verify vs historial) ANTES de llamar este SP — bcrypt no es determinístico en SQL
CREATE PROCEDURE sp_CambiarContrasena
    @id_usuario    INT,
    @nuevo_hash    NVARCHAR(512),
    @nuevo_salt    NVARCHAR(64) = '',
    @max_historial TINYINT      = 5
AS
BEGIN
    SET NOCOUNT ON;
    DECLARE @hact NVARCHAR(512), @sact NVARCHAR(64);
    SELECT @hact=hash_contrasena, @sact=salt FROM CORE_Usuarios WHERE id_usuario=@id_usuario;
    IF @hact IS NOT NULL
        INSERT INTO CORE_Contrasenas_Hist(id_usuario,hash_contrasena,salt) VALUES(@id_usuario,@hact,@sact);
    DELETE FROM CORE_Contrasenas_Hist WHERE id_usuario=@id_usuario
    AND id_hist NOT IN (
        SELECT TOP (@max_historial) id_hist FROM CORE_Contrasenas_Hist
        WHERE id_usuario=@id_usuario ORDER BY fecha_cambio DESC
    );
    UPDATE CORE_Usuarios SET hash_contrasena=@nuevo_hash, salt=@nuevo_salt,
        requiere_cambio_pass=0, fecha_modificacion=GETDATE() WHERE id_usuario=@id_usuario;
    INSERT INTO CORE_Auditoria(id_usuario,modulo,operacion,resultado)
    VALUES(@id_usuario,'CORE','CAMBIO_PASS','EXITO');
END;
GO

-- SP5: Menú autorizado del usuario vía jerarquía MOIS
-- Devuelve todos los nodos accesibles ordenados por id_modulo > opcion > items > subitems
-- La capa PHP construye el árbol colapsable a partir de este resultado plano
CREATE PROCEDURE sp_GetMenuUsuario
    @id_usuario INT
AS
BEGIN
    SET NOCOUNT ON;
    SELECT
        mn.id_nodo, mn.id_modulo, mn.opcion, mn.items, mn.subitems,
        mn.descripcion, mn.url_ruta, mn.icono, mn.orden, mn.target_spa,
        MAX(pn.nivel_crud) AS nivel_crud
    FROM CORE_Usuarios u
    INNER JOIN CORE_Usuarios_Roles ur ON ur.id_usuario = u.id_usuario AND ur.estado = 1
    INNER JOIN CORE_Roles r           ON r.id_rol = ur.id_rol AND r.estado = 1
    INNER JOIN CORE_Permisos_Nodo pn  ON pn.id_rol = r.id_rol AND pn.acceso = 1 AND pn.estado = 1
    INNER JOIN CORE_Menu_Nodos mn     ON mn.id_modulo = pn.id_modulo
                                     AND mn.opcion   = pn.opcion
                                     AND mn.items    = pn.items
                                     AND mn.subitems = pn.subitems
                                     AND mn.estado   = 1
    WHERE u.id_usuario = @id_usuario AND u.estado = 1
    GROUP BY mn.id_nodo, mn.id_modulo, mn.opcion, mn.items, mn.subitems,
             mn.descripcion, mn.url_ruta, mn.icono, mn.orden, mn.target_spa
    ORDER BY mn.id_modulo, mn.opcion, mn.items, mn.subitems;
END;
GO

-- SP6: Registrar auditoría centralizada
CREATE PROCEDURE sp_RegistrarAuditoria
    @modulo         NVARCHAR(30),
    @id_usuario     INT           = NULL,
    @nombre_usuario NVARCHAR(50)  = NULL,
    @operacion      NVARCHAR(30),
    @tabla          NVARCHAR(100) = NULL,
    @id_registro    NVARCHAR(50)  = NULL,
    @datos_antes    NVARCHAR(MAX) = NULL,
    @datos_despues  NVARCHAR(MAX) = NULL,
    @ip_address     NVARCHAR(45)  = NULL,
    @resultado      NVARCHAR(20)  = 'EXITO',
    @detalle        NVARCHAR(500) = NULL
AS
BEGIN
    SET NOCOUNT ON;
    DECLARE @ret INT, @fp DATETIME2;
    SELECT @ret=CAST(valor AS INT) FROM CORE_Config WHERE modulo='CORE' AND clave='AUDIT_RETENTION_YEARS' AND estado=1;
    IF @ret IS NULL SET @ret=2;
    SET @fp=DATEADD(YEAR,@ret,GETDATE());
    IF @id_usuario IS NULL AND @nombre_usuario IS NOT NULL
        SELECT @id_usuario=id_usuario FROM CORE_Usuarios WHERE nombre_usuario=@nombre_usuario;
    INSERT INTO CORE_Auditoria(id_usuario,nombre_usuario,modulo,operacion,tabla_afectada,id_registro,
        datos_antes,datos_despues,ip_address,resultado,detalle,fecha_purga)
    VALUES(@id_usuario,@nombre_usuario,@modulo,@operacion,@tabla,@id_registro,
        @datos_antes,@datos_despues,@ip_address,@resultado,@detalle,@fp);
END;
GO

-- SP7: Purgar auditorías antiguas
CREATE PROCEDURE sp_PurgarAuditoria
AS
BEGIN
    SET NOCOUNT ON;
    DELETE FROM CORE_Auditoria   WHERE fecha_purga IS NOT NULL AND fecha_purga < GETDATE();
    DELETE FROM TH_Auditoria     WHERE fecha_registro < DATEADD(YEAR,-2,GETDATE());
    DELETE FROM BIT_Auditoria    WHERE fecha_registro < DATEADD(YEAR,-2,GETDATE());
    DELETE FROM BIENES_Auditoria WHERE fecha_registro < DATEADD(YEAR,-2,GETDATE());
    DELETE FROM ACCESO_Auditoria WHERE fecha_registro < DATEADD(YEAR,-2,GETDATE());
END;
GO

-- SP8: KPIs Dashboard Ejecutivo
CREATE PROCEDURE sp_GetKPIs_Ejecutivo
AS
BEGIN
    SET NOCOUNT ON;
    SELECT
        (SELECT COUNT(*) FROM TH_Empleados    WHERE estado=1)                                                     AS empleados_activos,
        (SELECT COUNT(*) FROM BIENES_Activos  WHERE estado_bien='Activo' AND estado=1)                            AS bienes_activos,
        (SELECT COUNT(*) FROM ACCESO_Registros WHERE CAST(fecha_hora AS DATE)=CAST(GETDATE() AS DATE) AND estado=1) AS accesos_hoy,
        (SELECT COUNT(*) FROM BIT_Eventos     WHERE CAST(fecha_evento AS DATE)=CAST(GETDATE() AS DATE) AND estado=1) AS eventos_hoy,
        (SELECT COUNT(*) FROM CORE_Notificaciones WHERE leida=0 AND estado=1)                                     AS notif_pendientes,
        (SELECT COUNT(*) FROM CORE_Usuarios   WHERE estado=1)                                                     AS usuarios_activos,
        (SELECT COUNT(*) FROM TH_Contratos    WHERE estado_contrato='Vigente' AND estado=1)                       AS contratos_vigentes,
        (SELECT COUNT(*) FROM BIENES_Movimientos WHERE fecha_creacion>=DATEADD(DAY,-30,GETDATE()) AND estado=1)   AS movimientos_mes,
        (SELECT COUNT(*) FROM BIT_Eventos     WHERE prioridad=3 AND estado_evento!='Cerrado' AND estado=1)        AS alertas_criticas,
        (SELECT COUNT(*) FROM ACCESO_Registros WHERE estado_registro='Activo' AND estado=1)                       AS en_instalaciones;
END;
GO

-- SP9: KPIs Dashboard Operativo
CREATE PROCEDURE sp_GetKPIs_Operativo
    @id_usuario INT,
    @modulo     NVARCHAR(30) = 'Central'
AS
BEGIN
    SET NOCOUNT ON;
    DECLARE @id_dep INT;
    SELECT @id_dep=id_departamento FROM CORE_Usuarios WHERE id_usuario=@id_usuario;
    SELECT
        (SELECT COUNT(*) FROM BIT_Eventos WHERE estado_evento='Pendiente' AND estado=1
            AND (id_departamento=@id_dep OR @id_dep IS NULL))                                                     AS tareas_pendientes,
        (SELECT COUNT(*) FROM BIT_Eventos WHERE prioridad=3 AND estado_evento!='Cerrado' AND estado=1
            AND (id_departamento=@id_dep OR @id_dep IS NULL))                                                     AS alertas_criticas,
        (SELECT COUNT(*) FROM CORE_Auditoria WHERE id_usuario=@id_usuario AND estado=1
            AND CAST(fecha_registro AS DATE)=CAST(GETDATE() AS DATE))                                             AS acciones_hoy,
        (SELECT COUNT(*) FROM CORE_Notificaciones WHERE id_usuario=@id_usuario AND leida=0 AND estado=1)          AS mis_notificaciones;
END;
GO

-- ============================================================
-- SECCIÓN 8: FUNCIONES
-- ============================================================

-- Verifica acceso a un nodo MOIS específico
CREATE FUNCTION fn_TienePermisoNodo(
    @id_usuario INT,
    @id_modulo  TINYINT,
    @opcion     TINYINT,
    @items      TINYINT,
    @subitems   SMALLINT,
    @nivel_min  TINYINT,
    @mfa_ok     BIT = 1
) RETURNS BIT AS
BEGIN
    IF EXISTS (
        SELECT 1 FROM CORE_Usuarios u
        JOIN CORE_Usuarios_Roles ur ON ur.id_usuario=u.id_usuario AND ur.estado=1
        JOIN CORE_Roles r           ON r.id_rol=ur.id_rol AND r.estado=1
        JOIN CORE_Permisos_Nodo pn  ON pn.id_rol=r.id_rol AND pn.acceso=1 AND pn.estado=1
                                   AND pn.id_modulo=@id_modulo AND pn.opcion=@opcion
                                   AND pn.items=@items AND pn.subitems=@subitems
        JOIN CORE_Menu_Nodos mn     ON mn.id_modulo=pn.id_modulo AND mn.opcion=pn.opcion
                                   AND mn.items=pn.items AND mn.subitems=pn.subitems
                                   AND mn.estado=1
        WHERE u.id_usuario=@id_usuario AND u.estado=1
          AND pn.nivel_crud>=@nivel_min AND (mn.requiere_mfa=0 OR @mfa_ok=1)
    ) RETURN 1; RETURN 0;
END;
GO

-- Verifica acceso a un formulario específico (permisos granulares)
CREATE FUNCTION fn_TienePermisoFormulario(
    @id_usuario    INT,
    @id_formulario INT,
    @nivel_minimo  TINYINT
) RETURNS BIT AS
BEGIN
    IF EXISTS (
        SELECT 1 FROM CORE_Usuarios u
        JOIN CORE_Usuarios_Roles ur       ON ur.id_usuario=u.id_usuario AND ur.estado=1
        JOIN CORE_Roles r                 ON r.id_rol=ur.id_rol AND r.estado=1
        JOIN CORE_Formularios_Permisos fp ON fp.id_rol=r.id_rol AND fp.id_formulario=@id_formulario AND fp.estado=1
        WHERE u.id_usuario=@id_usuario AND u.estado=1 AND fp.nivel_crud>=@nivel_minimo
    ) RETURN 1; RETURN 0;
END;
GO

-- Valida sesión activa por token
CREATE FUNCTION fn_SesionValida(@token NVARCHAR(128)) RETURNS BIT AS
BEGIN
    IF EXISTS (SELECT 1 FROM CORE_Sesiones WHERE token=@token AND estado=1 AND fecha_expira>GETDATE()) RETURN 1;
    RETURN 0;
END;
GO

-- Árbol de departamentos con recursión CTE (SQL Server 2014+)
CREATE FUNCTION fn_GetArbolDepartamento(@id_raiz INT)
RETURNS TABLE AS RETURN (
    WITH Arbol AS (
        SELECT id_departamento,codigo,nombre,id_padre,nivel,0 AS prof,
               CAST(nombre AS NVARCHAR(500)) AS breadcrumb
        FROM CORE_Departamentos WHERE id_departamento=@id_raiz
        UNION ALL
        SELECT d.id_departamento,d.codigo,d.nombre,d.id_padre,d.nivel,a.prof+1,
               CAST(a.breadcrumb+' > '+d.nombre AS NVARCHAR(500))
        FROM CORE_Departamentos d JOIN Arbol a ON a.id_departamento=d.id_padre
    )
    SELECT * FROM Arbol
);
GO

-- ============================================================
-- SECCIÓN 9: VISTAS
-- ============================================================

-- Menú por usuario via MOIS — sin IDs hardcodeados, JOIN correcto por 4-tupla
CREATE VIEW vw_MenuPorUsuario AS
SELECT
    u.id_usuario,
    mn.id_nodo, mn.id_modulo, mn.opcion, mn.items, mn.subitems,
    mn.descripcion, mn.url_ruta, mn.icono, mn.orden, mn.target_spa,
    MAX(pn.nivel_crud) AS nivel_crud
FROM CORE_Usuarios u
INNER JOIN CORE_Usuarios_Roles ur ON ur.id_usuario = u.id_usuario AND ur.estado = 1
INNER JOIN CORE_Roles r           ON r.id_rol = ur.id_rol AND r.estado = 1
INNER JOIN CORE_Permisos_Nodo pn  ON pn.id_rol = r.id_rol AND pn.acceso = 1 AND pn.estado = 1
INNER JOIN CORE_Menu_Nodos mn     ON mn.id_modulo = pn.id_modulo
                                 AND mn.opcion    = pn.opcion
                                 AND mn.items     = pn.items
                                 AND mn.subitems  = pn.subitems
                                 AND mn.estado    = 1
WHERE u.estado = 1
GROUP BY u.id_usuario, mn.id_nodo, mn.id_modulo, mn.opcion, mn.items,
         mn.subitems, mn.descripcion, mn.url_ruta, mn.icono, mn.orden, mn.target_spa;
GO

CREATE VIEW vw_FichaEmpleado AS
SELECT e.id_empleado, e.cedula,
       ISNULL(e.nombres,'')+' '+ISNULL(e.apellidos,'') AS nombre_completo,
       e.correo, e.telefono, e.cargo, e.estado, e.fecha_ingreso, e.genero, e.estado_civil, e.foto,
       d.nombre AS departamento,
       DATEDIFF(YEAR,e.fecha_nacimiento,GETDATE())
           - CASE WHEN FORMAT(e.fecha_nacimiento,'MMdd') > FORMAT(GETDATE(),'MMdd') THEN 1 ELSE 0 END AS edad,
       DATEDIFF(MONTH,e.fecha_ingreso,GETDATE()) AS meses_antiguedad,
       c.tipo_contrato, c.salario, c.estado_contrato,
       u.nombre_usuario, u.correo AS correo_sistema
FROM TH_Empleados e
LEFT JOIN CORE_Departamentos d ON d.id_departamento = e.id_departamento
LEFT JOIN CORE_Usuarios u      ON u.id_usuario = e.id_usuario
OUTER APPLY (
    SELECT TOP 1 tipo_contrato, salario, estado_contrato FROM TH_Contratos
    WHERE id_empleado=e.id_empleado AND estado_contrato='Vigente' AND estado=1
    ORDER BY fecha_inicio DESC, id_contrato DESC
) c;
GO

CREATE VIEW vw_AuditoriaGlobal AS
SELECT a.id_auditoria, a.modulo, a.operacion, a.tabla_afectada, a.resultado,
       a.ip_address, a.detalle, a.fecha_registro,
       ISNULL(u.nombre_completo, a.nombre_usuario) AS nombre_usuario
FROM CORE_Auditoria a LEFT JOIN CORE_Usuarios u ON u.id_usuario = a.id_usuario;
GO

CREATE VIEW vw_KPIs_TH AS
SELECT COUNT(*) AS total,
       SUM(CASE WHEN estado=1 THEN 1 ELSE 0 END) AS activos,
       SUM(CASE WHEN estado=0 THEN 1 ELSE 0 END) AS inactivos,
       SUM(CASE WHEN genero='M' AND estado=1 THEN 1 ELSE 0 END) AS masculino,
       SUM(CASE WHEN genero='F' AND estado=1 THEN 1 ELSE 0 END) AS femenino,
       AVG(CASE WHEN estado=1 THEN DATEDIFF(YEAR,fecha_nacimiento,GETDATE()) ELSE NULL END) AS edad_promedio
FROM TH_Empleados;
GO

CREATE VIEW vw_KPIs_Bienes AS
SELECT COUNT(*) AS total,
       SUM(CASE WHEN estado_bien='Activo'        AND estado=1 THEN 1 ELSE 0 END) AS activos,
       SUM(CASE WHEN estado_bien='Baja'          AND estado=1 THEN 1 ELSE 0 END) AS bajas,
       SUM(CASE WHEN estado_bien='En Reparacion' AND estado=1 THEN 1 ELSE 0 END) AS en_reparacion,
       SUM(CASE WHEN estado=1 THEN ISNULL(valor_adquisicion,0) ELSE 0 END)        AS valor_total,
       COUNT(DISTINCT CASE WHEN estado=1 THEN id_departamento ELSE NULL END)      AS departamentos
FROM BIENES_Activos;
GO

CREATE VIEW vw_KPIs_Acceso AS
SELECT COUNT(*) AS registros_hoy,
       SUM(CASE WHEN tipo_acceso='Entrada' THEN 1 ELSE 0 END) AS entradas_hoy,
       SUM(CASE WHEN tipo_acceso='Salida'  THEN 1 ELSE 0 END) AS salidas_hoy,
       SUM(CASE WHEN estado_registro='Activo' AND estado=1 THEN 1 ELSE 0 END) AS en_instalaciones
FROM ACCESO_Registros
WHERE CAST(fecha_hora AS DATE)=CAST(GETDATE() AS DATE) AND estado=1;
GO

CREATE VIEW vw_KPIs_Bitacoras AS
SELECT COUNT(*) AS total,
       SUM(CASE WHEN estado_evento='Pendiente' AND estado=1 THEN 1 ELSE 0 END) AS pendientes,
       SUM(CASE WHEN prioridad=3              AND estado=1 THEN 1 ELSE 0 END) AS criticos,
       SUM(CASE WHEN CAST(fecha_evento AS DATE)=CAST(GETDATE() AS DATE) AND estado=1 THEN 1 ELSE 0 END) AS hoy
FROM BIT_Eventos;
GO

CREATE VIEW vw_ResumenRoles AS
SELECT u.id_usuario, u.nombre_usuario, u.nombre_completo, u.nivel_jerarquia,
       (SELECT COUNT(*) FROM CORE_Usuarios_Roles ur2 WHERE ur2.id_usuario=u.id_usuario AND ur2.estado=1) AS total_roles,
       -- STUFF+FOR XML PATH para SQL Server 2014 (STRING_AGG requiere 2017+)
       STUFF((SELECT ', ' + r2.nombre
              FROM CORE_Usuarios_Roles ur2
              JOIN CORE_Roles r2 ON r2.id_rol=ur2.id_rol
              WHERE ur2.id_usuario=u.id_usuario AND ur2.estado=1 AND r2.estado=1
              ORDER BY r2.nombre
              FOR XML PATH(''), TYPE).value('.','NVARCHAR(MAX)'),1,2,'') AS roles
FROM CORE_Usuarios u;
GO

CREATE VIEW vw_SSO_Usuarios AS
SELECT id_usuario, nombre_usuario, correo, nombre_completo, nivel_jerarquia, estado, tema_preferido, id_departamento
FROM CORE_Usuarios;
GO

-- SSO menú via MOIS — JOIN correcto por 4-tupla, sin hardcode
CREATE VIEW vw_SSO_Menu AS
SELECT DISTINCT
    u.id_usuario, mn.id_nodo, mn.id_modulo, mn.opcion, mn.items, mn.subitems,
    mn.descripcion, mn.url_ruta, mn.icono, mn.orden
FROM CORE_Usuarios u
JOIN CORE_Usuarios_Roles ur ON ur.id_usuario=u.id_usuario AND ur.estado=1
JOIN CORE_Roles r           ON r.id_rol=ur.id_rol AND r.estado=1
JOIN CORE_Permisos_Nodo pn  ON pn.id_rol=r.id_rol AND pn.acceso=1 AND pn.estado=1
JOIN CORE_Menu_Nodos mn     ON mn.id_modulo=pn.id_modulo AND mn.opcion=pn.opcion
                           AND mn.items=pn.items AND mn.subitems=pn.subitems AND mn.estado=1
WHERE u.estado=1;
GO

-- ============================================================
-- SECCIÓN 10: DATOS SEMILLA
-- ============================================================

SET IDENTITY_INSERT CORE_Departamentos ON;
INSERT INTO CORE_Departamentos(id_departamento,codigo,nombre,id_padre,nivel,icono,color_badge) VALUES
(1,'PORTAL','Portal APM',NULL,0,'fa-anchor','#0056b3'),
(2,'GERENCIA','Gerencia General',1,1,'fa-building','#1a2332'),
(3,'PLANIFICACION','Dirección de Planificación',1,1,'fa-chart-gantt','#0056b3'),
(4,'JURIDICA','Dirección Jurídica',1,1,'fa-scale-balanced','#6f42c1'),
(5,'INFRA','Dirección de Infraestructura',1,1,'fa-hard-hat','#fd7e14'),
(6,'OPERACIONES','Dirección de Operaciones',1,1,'fa-ship','#20c997'),
(7,'DELEGACION','Delegación Provincial',1,1,'fa-landmark','#6c757d'),
(8,'ADMIN','Dirección Administrativa',1,1,'fa-briefcase','#dc3545'),
(9,'FINANCIERO','Dirección Financiera',1,1,'fa-wallet','#198754'),
(10,'TH','Dirección de Talento Humano',1,1,'fa-users','#0dcaf0'),
(11,'TI','Departamento TI',8,2,'fa-server','#6c757d'),
(12,'AUDITORIA','Auditoría Interna',2,2,'fa-clipboard-check','#6f42c1'),
(13,'JURIDICA_ABOG','Abogados',4,2,'fa-gavel','#6f42c1'),
(14,'CCTV','Vigilancia CCTV',6,2,'fa-video','#495057'),
(15,'GARITA','Garita de Acceso',6,2,'fa-door-open','#20c997'),
(16,'INSPECTORES','Inspectores Portuarios',5,2,'fa-binoculars','#fd7e14'),
(17,'TH_ANALISTAS','Analistas TH',10,2,'fa-user-gear','#0dcaf0'),
(18,'CONTABILIDAD','Contabilidad',9,2,'fa-calculator','#198754'),
(19,'PRESUPUESTO','Presupuesto',9,2,'fa-coins','#198754'),
(20,'SECRETARIA','Secretaría General',2,2,'fa-envelope','#6c757d');
SET IDENTITY_INSERT CORE_Departamentos OFF;
GO

-- Hash bcrypt $2y$12$ para contraseña inicial: Apm2024*
-- password_hash('Apm2024*', PASSWORD_BCRYPT, ['cost'=>12])
DECLARE @hash NVARCHAR(512) = '$2y$12$rdWFpTOPEQSoN8QUYf4A5uTczqz9ivTV7qpIaW473OMW.JjF2woYK';

SET IDENTITY_INSERT CORE_Usuarios ON;
INSERT INTO CORE_Usuarios(id_usuario,nombre_usuario,correo,nombre_completo,hash_contrasena,salt,id_departamento,nivel_jerarquia,estado,tema_preferido) VALUES
-- nivel 4 = SuperAdmin (solo admin TI)
(1,'admin','admin@apm.gob.ec','Administrador TI',@hash,'',11,4,1,'dark'),
-- nivel 3 = Gerente
(16,'gerente','gerente@apm.gob.ec','Gerente General',@hash,'',2,3,1,'corporate'),
-- nivel 2 = Directores
(2,'auditor','auditor@apm.gob.ec','Auditor Interno',@hash,'',12,2,1,'light'),
(3,'dir.juridica','djuridica@apm.gob.ec','Director Jurídico',@hash,'',4,2,1,'corporate'),
(11,'dir.infra','dinfra@apm.gob.ec','Director Infraestructura',@hash,'',5,2,1,'corporate'),
(14,'dir.th','dth@apm.gob.ec','Directora Talento Humano',@hash,'',10,2,1,'light'),
(18,'dir.admin','dadmin@apm.gob.ec','Directora Administrativa',@hash,'',8,2,1,'corporate'),
(20,'dir.fin','dfin@apm.gob.ec','Director Financiero',@hash,'',9,2,1,'corporate'),
-- nivel 1 = Analistas / Supervisores
(4,'abogada1','abg1@apm.gob.ec','Abogada Primera',@hash,'',13,1,1,'light'),
(5,'abogado2','abg2@apm.gob.ec','Abogado Segundo',@hash,'',13,1,1,'light'),
(6,'sup.acceso','sacceso@apm.gob.ec','Supervisora Control Acceso',@hash,'',15,1,1,'light'),
(10,'secretaria','secretaria@apm.gob.ec','Secretaria General',@hash,'',20,1,1,'light'),
(15,'analista.th','ath@apm.gob.ec','Analista Talento Humano',@hash,'',17,1,1,'light'),
(17,'asist.gcia','agcia@apm.gob.ec','Asistente Gerencia',@hash,'',2,1,1,'light'),
(19,'analista.admin','aadmin@apm.gob.ec','Analista Administrativo',@hash,'',8,1,1,'light'),
(21,'analista.fin','afin@apm.gob.ec','Analista Financiero',@hash,'',9,1,1,'light'),
-- nivel 0 = Operadores
(7,'garita1','garita1@apm.gob.ec','Operador Garita 1',@hash,'',15,0,1,'light'),
(8,'garita2','garita2@apm.gob.ec','Operador Garita 2',@hash,'',15,0,1,'light'),
(9,'cctv.op','cctv@apm.gob.ec','Operador CCTV',@hash,'',14,0,1,'dark'),
(12,'inspector1','insp1@apm.gob.ec','Inspector Portuario 1',@hash,'',16,0,1,'light'),
(13,'inspector2','insp2@apm.gob.ec','Inspector Portuario 2',@hash,'',16,0,1,'light');
SET IDENTITY_INSERT CORE_Usuarios OFF;
GO

SET IDENTITY_INSERT CORE_Roles ON;
INSERT INTO CORE_Roles(id_rol,codigo,nombre,id_departamento,nivel_jerarquia) VALUES
(1,'ADMIN','Administrador TI',11,4),
(2,'AUDITOR','Auditor Interno',12,2),
(3,'DIR_JURIDICA','Director Jurídico',4,2),
(4,'ABOGADO','Abogado/a',13,1),
(5,'SUP_ACCESO','Supervisor Acceso',15,1),
(6,'OP_GARITA','Operador Garita',15,0),
(7,'OP_CCTV','Operador CCTV',14,0),
(8,'SECRETARIA','Secretaria',20,1),
(9,'DIR_INFRA','Director Infraestructura',5,2),
(10,'INSPECTOR','Inspector Portuario',16,0),
(11,'DIR_TH','Director Talento Humano',10,2),
(12,'ANALISTA_TH','Analista TH',17,1),
(13,'GERENTE','Gerente General',2,3),
(14,'ASIST_GCIA','Asistente Gerencia',2,1),
(15,'DIR_ADMIN','Director Administrativo',8,2),
(16,'ANALISTA_ADMIN','Analista Administrativo',8,1),
(17,'DIR_FIN','Director Financiero',9,2),
(18,'ANALISTA_FIN','Analista Financiero',9,1),
(19,'PLANIFICACION','Planificación',3,2),
(20,'DELEGACION','Delegación',7,2),
(21,'LECTOR','Acceso Solo Lectura',1,0);
SET IDENTITY_INSERT CORE_Roles OFF;
GO

INSERT INTO CORE_Usuarios_Roles(id_usuario,id_rol,asignado_por) VALUES
(1,1,1),(2,2,1),(3,3,1),(4,4,1),(5,4,1),(6,5,1),(7,6,1),(8,6,1),(9,7,1),(10,8,1),
(11,9,1),(12,10,1),(13,10,1),(14,11,1),(15,12,1),(16,13,1),(17,14,1),
(18,15,1),(19,16,1),(20,17,1),(21,18,1);
GO

-- ============================================================
-- SEMILLA: CORE_Menu_Nodos (estructura MOIS 4 niveles)
-- Fuente: DIRECCIONES-AREAS-OPCIONES-ITEMS.xlsx / base1.sql
-- id_modulo: 1=Planificación 2=TI 3=Jurídica 4=Infraestructura
--            5=Control Acceso 6=Operaciones 7=Gerencia 8=Delegación
--            9=Administrativa 10=Financiero 11=Talento Humano
-- opcion=0 items=0 subitems=0 → cabecera de módulo (no clickable)
-- opcion=N items=0 subitems=0 → cabecera de área (no clickable)
-- opcion=N items=M subitems=0 → pantalla/formulario (url_ruta asignada si existe en portal)
-- opcion=N items=M subitems=K → subacción específica
-- ============================================================

INSERT INTO CORE_Menu_Nodos(id_modulo,opcion,items,subitems,descripcion,url_ruta,icono,orden,estado) VALUES
-- Módulo 1: Dirección de Planificación Estratégica
(1,0,0,0,'Dirección de Planificación Estratégica',NULL,'fa-chart-gantt',1,1),
(1,1,0,0,'Dirección Planificación',NULL,'fa-sitemap',1,1),
(1,1,1,0,'Dashboard','/dashboard','fa-gauge',1,1),
(1,1,2,0,'Formularios Necesarios',NULL,'fa-file-alt',2,1),

-- Módulo 2: Gestión de Tecnología de la Información (Admin Portal)
(2,0,0,0,'Gestión de Tecnología de la Información',NULL,'fa-server',2,1),
(2,1,0,0,'Dashboard TI','/dashboard/ejecutivo','fa-gauge',1,1),
(2,2,0,0,'Administrador TICS',NULL,'fa-sliders',2,1),
(2,2,1,0,'Gestión de Usuarios','/admin/usuarios','fa-users-gear',1,1),
(2,2,2,0,'Roles y Permisos','/admin/roles','fa-shield-halved',2,1),
(2,3,0,0,'Notas de Pedido TI',NULL,'fa-file-lines',3,0),

-- Módulo 3: Dirección de Asesoría Jurídica
(3,0,0,0,'Dirección de Asesoría Jurídica',NULL,'fa-scale-balanced',3,1),
(3,1,0,0,'Director / Jefe Jurídico',NULL,'fa-user-tie',1,1),
(3,1,1,0,'Dashboard','/dashboard','fa-gauge',1,1),
(3,2,0,0,'Gestionar Permiso de Operaciones','/bitacoras','fa-clipboard-list',2,1),
(3,3,0,0,'Agregar Operadoras',NULL,'fa-building-circle-check',3,1),
(3,4,0,0,'Lista de Requisitos',NULL,'fa-list-check',4,1),

-- Módulo 4: Dirección de Infraestructura Portuaria
(4,0,0,0,'Dirección de Infraestructura Portuaria',NULL,'fa-hard-hat',4,1),
(4,1,0,0,'Director / Jefe Infraestructura',NULL,'fa-user-tie',1,1),
(4,1,1,0,'Dashboard','/dashboard','fa-gauge',1,1),
(4,1,2,0,'Aprobación de Solicitud de Credenciales','/acceso/visitantes','fa-id-card',2,1),
(4,1,3,0,'Lista de Requisitos',NULL,'fa-list-check',3,1),

-- Módulo 5: Garita de Acceso / Control de Acceso
(5,0,0,0,'Garita de Acceso - Edificio Administrativo',NULL,'fa-door-open',5,1),
(5,1,0,0,'Registro de Ingreso y Salida','/acceso','fa-clipboard-list',1,1),
(5,2,0,0,'Formularios de Registro Maestros','/acceso/visitantes','fa-person-walking-arrow-right',2,1),
(5,3,0,0,'Otras Opciones Garita',NULL,'fa-ellipsis',3,1),
(5,4,0,0,'Inspectores',NULL,'fa-binoculars',4,1),
(5,4,1,0,'Registro de Actividades Inspectores','/bitacoras','fa-book-open',1,1),
(5,4,2,0,'Formularios Registro Maestros Inspectores',NULL,'fa-file-lines',2,1),
(5,4,3,0,'Otras Opciones Inspectores',NULL,'fa-ellipsis',3,1),
(5,5,0,0,'CCTV - Registro de Actividades',NULL,'fa-video',5,1),
(5,5,1,0,'Registro de Actividades CCTV','/acceso','fa-video',1,1),
(5,5,2,0,'Formularios Maestros CCTV',NULL,'fa-file-lines',2,1),
(5,5,3,0,'Otras Opciones CCTV',NULL,'fa-ellipsis',3,1),
(5,6,0,0,'Control de Accesos',NULL,'fa-shield-halved',6,1),
(5,6,1,0,'Validación de Solicitud de Credenciales','/acceso/visitantes','fa-id-card-clip',1,1),
(5,6,2,0,'Activación de Credenciales','/acceso/visitantes','fa-address-card',2,1),
(5,6,3,0,'Lista de Requisitos Acceso',NULL,'fa-list-check',3,1),
(5,7,0,0,'Reportes de Acceso',NULL,'fa-chart-bar',7,1),
(5,7,1,0,'Verificar Solicitud por Cédula','/acceso/visitantes','fa-magnifying-glass',1,1),
(5,7,2,0,'Solicitud de Trámites',NULL,'fa-file-circle-plus',2,1),
(5,7,3,0,'Otras Opciones Reportes',NULL,'fa-ellipsis',3,1),
(5,8,0,0,'Notas de Pedido Acceso',NULL,'fa-file-lines',8,0),

-- Módulo 6: Dirección de Operaciones
(6,0,0,0,'Dirección de Operaciones',NULL,'fa-ship',6,1),
(6,1,0,0,'Permiso de Operaciones',NULL,'fa-file-contract',1,1),
(6,1,1,0,'Atender Permiso de Operaciones','/bitacoras','fa-pen-to-square',1,1),
(6,1,2,0,'Lista de Requisitos Operaciones',NULL,'fa-list-check',2,1),
(6,1,3,0,'Otras Opciones Operaciones',NULL,'fa-ellipsis',3,1),
(6,2,0,0,'Notas de Pedido Operaciones',NULL,'fa-file-lines',2,0),

-- Módulo 7: Gerencia General
(7,0,0,0,'Gerencia General',NULL,'fa-building',7,1),
(7,1,0,0,'Permiso de Operaciones Gerencia',NULL,'fa-file-contract',1,1),
(7,1,1,0,'Gestionar Operaciones','/bitacoras','fa-gear',1,1),
(7,1,2,0,'Lista de Requisitos Gerencia',NULL,'fa-list-check',2,1),
(7,1,3,0,'Varias Opciones de Formularios',NULL,'fa-ellipsis',3,1),
(7,2,0,0,'Renovación de Pólizas',NULL,'fa-file-circle-check',2,1),
(7,2,1,0,'Gestionar Pólizas','/dashboard/ejecutivo','fa-hand-holding-dollar',1,1),
(7,2,2,0,'Varias Opciones Pólizas',NULL,'fa-ellipsis',2,1),
(7,3,0,0,'Notas de Pedido Gerencia',NULL,'fa-file-lines',3,0),

-- Módulo 8: Delegación de Servicios Portuarios
(8,0,0,0,'Dirección de Delegación de Servicios Portuarios',NULL,'fa-landmark',8,1),
(8,1,0,0,'Área / Jefatura / Dirección',NULL,'fa-sitemap',1,1),
(8,1,1,0,'Dashboard Delegación','/dashboard','fa-gauge',1,1),
(8,1,2,0,'Varios Formularios Delegación',NULL,'fa-file-lines',2,1),
(8,2,0,0,'Notas de Pedido Delegación',NULL,'fa-file-lines',2,0),

-- Módulo 9: Dirección Administrativa / Control de Bienes
(9,0,0,0,'Dirección Administrativa',NULL,'fa-briefcase',9,1),
(9,1,0,0,'Director / Jefe Administrativo',NULL,'fa-user-tie',1,1),
(9,1,1,0,'Dashboard Administrativo','/dashboard/ejecutivo','fa-gauge',1,1),
(9,1,2,0,'Otras Opciones Administrativo',NULL,'fa-ellipsis',2,1),
(9,2,0,0,'Control de Bienes',NULL,'fa-boxes-stacked',2,1),
(9,2,1,0,'Maestros - Grupos Contables',NULL,'fa-layer-group',1,1),
(9,2,2,0,'Maestros - Proveedores',NULL,'fa-truck',2,1),
(9,2,3,0,'Maestros - Unidades',NULL,'fa-ruler',3,1),
(9,2,4,0,'Maestros - Periodos',NULL,'fa-calendar',4,1),
(9,2,5,0,'Maestros - Ubicación',NULL,'fa-location-dot',5,1),
(9,2,6,0,'Maestros - Otros',NULL,'fa-cog',6,1),
(9,2,7,0,'Bienes de Consumo Corriente','/bienes','fa-box',7,1),
(9,2,7,1,'Grupos de Productos',NULL,'fa-layer-group',1,1),
(9,2,7,2,'Registro de Ítems','/bienes','fa-list',2,1),
(9,2,7,3,'Requisiciones',NULL,'fa-file-circle-plus',3,1),
(9,2,7,4,'Orden de Compra',NULL,'fa-cart-shopping',4,1),
(9,2,7,5,'Comprobante de Ingreso',NULL,'fa-file-invoice',5,1),
(9,2,7,6,'Reclasificación de Grupo Contable',NULL,'fa-shuffle',6,1),
(9,2,7,7,'Egresos Consumo Corriente',NULL,'fa-arrow-right-from-bracket',7,1),
(9,2,7,8,'Otras Opciones Consumo',NULL,'fa-ellipsis',8,1),
(9,2,8,0,'Bienes de Larga Duración','/bienes','fa-box-archive',8,1),
(9,2,8,1,'Clase de Activos',NULL,'fa-tag',1,1),
(9,2,8,2,'Grupo de Activos',NULL,'fa-layer-group',2,1),
(9,2,8,3,'Registro de Activos','/bienes','fa-list',3,1),
(9,2,8,4,'Requisiciones de Activo Fijo',NULL,'fa-file-circle-plus',4,1),
(9,2,8,5,'Orden de Compra Activo Fijo',NULL,'fa-cart-shopping',5,1),
(9,2,8,6,'Comprobante de Ingreso Activo Fijo',NULL,'fa-file-invoice',6,1),
(9,2,8,7,'Cambio de Responsable del Bien','/bienes/movimientos','fa-arrows-left-right',7,1),
(9,2,8,8,'Reclasificación de Cuenta Contable',NULL,'fa-shuffle',8,1),
(9,2,9,0,'Reportes Consumo Corriente',NULL,'fa-chart-bar',9,1),
(9,2,9,1,'Lista de Proveedores',NULL,'fa-address-book',1,1),
(9,2,9,2,'Funcionarios / Centros de Consumo',NULL,'fa-users',2,1),
(9,2,9,3,'Grupos de Centros de Consumo',NULL,'fa-layer-group',3,1),
(9,2,9,4,'Ingresos de Consumo Corriente',NULL,'fa-arrow-right-to-bracket',4,1),
(9,2,9,5,'Egresos de Consumo Corriente',NULL,'fa-arrow-right-from-bracket',5,1),
(9,2,9,6,'Kardex',NULL,'fa-table-list',6,1),
(9,2,9,7,'Registro Total de Artículos',NULL,'fa-list',7,1),
(9,2,9,8,'Grupo en Valores',NULL,'fa-layer-group',8,1),
(9,2,9,9,'Inventario General','/bienes','fa-warehouse',9,1),
(9,2,9,10,'Saldo del Año Anterior',NULL,'fa-calendar-check',10,1),
(9,2,10,0,'Reportes Bienes de Larga Duración',NULL,'fa-chart-bar',10,1),
(9,2,10,1,'Reporte de Activos','/bienes','fa-list',1,1),
(9,2,10,2,'Actas de Bienes APM',NULL,'fa-file-contract',2,1),
(9,2,10,3,'Actas de Bienes TPM',NULL,'fa-file-contract',3,1),
(9,2,10,4,'Reporte de Cambio de Responsable','/bienes/movimientos','fa-arrows-left-right',4,1),
(9,2,10,5,'Reporte de Reclasificación',NULL,'fa-shuffle',5,1),
(9,2,11,0,'Cierre de Mes',NULL,'fa-lock',11,1),
(9,2,12,0,'Ajuste de Diferencias',NULL,'fa-sliders',12,1),
(9,2,13,0,'Anular Movimiento Consumo Corriente',NULL,'fa-ban',13,1),
(9,2,14,0,'Anular Movimientos Larga Duración',NULL,'fa-ban',14,1),
(9,2,15,0,'Anular Nota de Pedido',NULL,'fa-ban',15,1),
(9,2,16,0,'Anular Requisición',NULL,'fa-ban',16,1),
(9,3,0,0,'Departamento de Archivo Central',NULL,'fa-folder-open',3,1),
(9,3,1,0,'Recepción de Pólizas',NULL,'fa-file-circle-plus',1,1),
(9,3,2,0,'Notas de Pedido Archivo',NULL,'fa-file-lines',2,1),
(9,4,0,0,'Notas de Pedido Administrativo',NULL,'fa-file-lines',4,0),

-- Módulo 10: Dirección Financiera
(10,0,0,0,'Dirección Financiera',NULL,'fa-wallet',10,1),
(10,1,0,0,'Dirección / Jefatura Financiera',NULL,'fa-user-tie',1,1),
(10,1,1,0,'Dashboard Financiero','/dashboard/ejecutivo','fa-gauge',1,1),
(10,1,2,0,'Formulario Gestión de Pólizas',NULL,'fa-file-contract',2,1),
(10,1,3,0,'Varios Formularios Financiero',NULL,'fa-file-lines',3,1),
(10,2,0,0,'Tesorería',NULL,'fa-vault',2,1),
(10,2,1,0,'Gestión de Pólizas Tesorería',NULL,'fa-file-contract',1,1),
(10,2,2,0,'Lista de Requisitos Tesorería',NULL,'fa-list-check',2,1),
(10,2,3,0,'Varios Formularios Tesorería',NULL,'fa-file-lines',3,1),
(10,3,0,0,'Contabilidad',NULL,'fa-calculator',3,1),
(10,3,1,0,'Gestión de Pólizas Contabilidad',NULL,'fa-file-contract',1,1),
(10,3,2,0,'Lista de Requisitos Contabilidad',NULL,'fa-list-check',2,1),
(10,3,3,0,'Varios Formularios Contabilidad',NULL,'fa-file-lines',3,1),
(10,4,0,0,'Notas de Pedido Financiero',NULL,'fa-file-lines',4,0),

-- Módulo 11: Dirección de Talento Humano
(11,0,0,0,'Dirección Administración de Talento Humano',NULL,'fa-users',11,1),
(11,1,0,0,'Director / Jefe de Talento Humano',NULL,'fa-user-tie',1,1),
(11,1,1,0,'Dashboard TH','/dashboard','fa-gauge',1,1),
(11,1,2,0,'Otros Formularios TH',NULL,'fa-file-lines',2,1),
(11,2,0,0,'Nóminas y Personal',NULL,'fa-id-card',2,1),
(11,2,1,0,'Dashboard Nóminas','/dashboard/operativo','fa-chart-pie',1,1),
(11,2,2,0,'Maestro de Empleados','/th/empleados','fa-user-tie',2,1),
(11,2,3,0,'Maestro de Periodos',NULL,'fa-calendar',3,1),
(11,2,4,0,'Maestro de Títulos',NULL,'fa-graduation-cap',4,1),
(11,2,5,0,'Maestro de Cargos',NULL,'fa-briefcase',5,1),
(11,2,6,0,'Maestro de Dirección-Áreas',NULL,'fa-sitemap',6,1),
(11,2,7,0,'Maestro de Departamentos',NULL,'fa-building',7,1),
(11,2,8,0,'Maestro Varios Formularios',NULL,'fa-file-lines',8,1),
(11,2,9,0,'Movimiento de Personal / Contratos','/th/contratos','fa-file-contract',9,1),
(11,2,10,0,'Otros Formularios Requeridos',NULL,'fa-ellipsis',10,1),
(11,3,0,0,'Notas de Pedido TH',NULL,'fa-file-lines',3,0);
GO

-- ============================================================
-- SEMILLA: CORE_Permisos_Nodo
-- Estrategia: INSERT...SELECT para evitar listar cada nodo manualmente
-- nivel_crud: 1=Leer 2=Crear 3=Editar 4=Administrar
-- ============================================================

-- Rol 1 (ADMIN/TI): acceso total a todos los módulos, nivel 4
INSERT INTO CORE_Permisos_Nodo(id_rol,id_modulo,opcion,items,subitems,nivel_crud,asignado_por)
SELECT 1,id_modulo,opcion,items,subitems,4,1 FROM CORE_Menu_Nodos WHERE estado=1;

-- Rol 13 (GERENTE): todos los módulos lectura + módulos 7,10 nivel 4
INSERT INTO CORE_Permisos_Nodo(id_rol,id_modulo,opcion,items,subitems,nivel_crud,asignado_por)
SELECT 13,id_modulo,opcion,items,subitems,
       CASE WHEN id_modulo IN (7,10) THEN 4 ELSE 1 END,1
FROM CORE_Menu_Nodos WHERE estado=1;

-- Rol 2 (AUDITOR): todos los módulos en lectura
INSERT INTO CORE_Permisos_Nodo(id_rol,id_modulo,opcion,items,subitems,nivel_crud,asignado_por)
SELECT 2,id_modulo,opcion,items,subitems,1,1 FROM CORE_Menu_Nodos WHERE estado=1;

-- Rol 19 (PLANIFICACION): módulo 1 + dashboard → lectura
INSERT INTO CORE_Permisos_Nodo(id_rol,id_modulo,opcion,items,subitems,nivel_crud,asignado_por)
SELECT 19,id_modulo,opcion,items,subitems,1,1 FROM CORE_Menu_Nodos
WHERE id_modulo=1 AND estado=1;

-- Rol 3 (DIR_JURIDICA): módulo 3 nivel 4
INSERT INTO CORE_Permisos_Nodo(id_rol,id_modulo,opcion,items,subitems,nivel_crud,asignado_por)
SELECT 3,id_modulo,opcion,items,subitems,4,1 FROM CORE_Menu_Nodos
WHERE id_modulo=3 AND estado=1;

-- Rol 4 (ABOGADO): módulo 3 lectura
INSERT INTO CORE_Permisos_Nodo(id_rol,id_modulo,opcion,items,subitems,nivel_crud,asignado_por)
SELECT 4,id_modulo,opcion,items,subitems,1,1 FROM CORE_Menu_Nodos
WHERE id_modulo=3 AND estado=1;

-- Rol 9 (DIR_INFRA): módulo 4 nivel 4 + módulo 5 lectura
INSERT INTO CORE_Permisos_Nodo(id_rol,id_modulo,opcion,items,subitems,nivel_crud,asignado_por)
SELECT 9,id_modulo,opcion,items,subitems,
       CASE WHEN id_modulo=4 THEN 4 ELSE 1 END,1
FROM CORE_Menu_Nodos WHERE id_modulo IN (4,5) AND estado=1;

-- Rol 5 (SUP_ACCESO): módulo 5 nivel 4
INSERT INTO CORE_Permisos_Nodo(id_rol,id_modulo,opcion,items,subitems,nivel_crud,asignado_por)
SELECT 5,id_modulo,opcion,items,subitems,4,1 FROM CORE_Menu_Nodos
WHERE id_modulo=5 AND estado=1;

-- Rol 6 (OP_GARITA): módulo 5 opciones 1 y 2 → nivel 2 (crear)
INSERT INTO CORE_Permisos_Nodo(id_rol,id_modulo,opcion,items,subitems,nivel_crud,asignado_por)
SELECT 6,id_modulo,opcion,items,subitems,2,1 FROM CORE_Menu_Nodos
WHERE id_modulo=5 AND opcion IN (1,2) AND estado=1;

-- Rol 7 (OP_CCTV): módulo 5 opciones 5 y 7 → lectura
INSERT INTO CORE_Permisos_Nodo(id_rol,id_modulo,opcion,items,subitems,nivel_crud,asignado_por)
SELECT 7,id_modulo,opcion,items,subitems,1,1 FROM CORE_Menu_Nodos
WHERE id_modulo=5 AND opcion IN (0,5,7) AND estado=1;

-- Rol 10 (INSPECTOR): módulo 5 opcion 4 → nivel 2
INSERT INTO CORE_Permisos_Nodo(id_rol,id_modulo,opcion,items,subitems,nivel_crud,asignado_por)
SELECT 10,id_modulo,opcion,items,subitems,2,1 FROM CORE_Menu_Nodos
WHERE id_modulo=5 AND opcion IN (0,4) AND estado=1;

-- Rol 20 (DELEGACION): módulo 8 nivel 4 + lectura general
INSERT INTO CORE_Permisos_Nodo(id_rol,id_modulo,opcion,items,subitems,nivel_crud,asignado_por)
SELECT 20,id_modulo,opcion,items,subitems,4,1 FROM CORE_Menu_Nodos
WHERE id_modulo=8 AND estado=1;

-- Rol 15 (DIR_ADMIN): módulo 9 nivel 4
INSERT INTO CORE_Permisos_Nodo(id_rol,id_modulo,opcion,items,subitems,nivel_crud,asignado_por)
SELECT 15,id_modulo,opcion,items,subitems,4,1 FROM CORE_Menu_Nodos
WHERE id_modulo=9 AND estado=1;

-- Rol 16 (ANALISTA_ADMIN): módulo 9 nivel 2 (crear, no administrar)
INSERT INTO CORE_Permisos_Nodo(id_rol,id_modulo,opcion,items,subitems,nivel_crud,asignado_por)
SELECT 16,id_modulo,opcion,items,subitems,2,1 FROM CORE_Menu_Nodos
WHERE id_modulo=9 AND estado=1;

-- Rol 17 (DIR_FIN): módulo 10 nivel 4
INSERT INTO CORE_Permisos_Nodo(id_rol,id_modulo,opcion,items,subitems,nivel_crud,asignado_por)
SELECT 17,id_modulo,opcion,items,subitems,4,1 FROM CORE_Menu_Nodos
WHERE id_modulo=10 AND estado=1;

-- Rol 18 (ANALISTA_FIN): módulo 10 lectura + módulo 9 lectura
INSERT INTO CORE_Permisos_Nodo(id_rol,id_modulo,opcion,items,subitems,nivel_crud,asignado_por)
SELECT 18,id_modulo,opcion,items,subitems,1,1 FROM CORE_Menu_Nodos
WHERE id_modulo IN (9,10) AND estado=1;

-- Rol 11 (DIR_TH): módulo 11 nivel 4
INSERT INTO CORE_Permisos_Nodo(id_rol,id_modulo,opcion,items,subitems,nivel_crud,asignado_por)
SELECT 11,id_modulo,opcion,items,subitems,4,1 FROM CORE_Menu_Nodos
WHERE id_modulo=11 AND estado=1;

-- Rol 12 (ANALISTA_TH): módulo 11 nivel 2
INSERT INTO CORE_Permisos_Nodo(id_rol,id_modulo,opcion,items,subitems,nivel_crud,asignado_por)
SELECT 12,id_modulo,opcion,items,subitems,2,1 FROM CORE_Menu_Nodos
WHERE id_modulo=11 AND estado=1;

-- Rol 8 (SECRETARIA): dashboard + notificaciones → módulo 7 lectura
INSERT INTO CORE_Permisos_Nodo(id_rol,id_modulo,opcion,items,subitems,nivel_crud,asignado_por)
SELECT 8,id_modulo,opcion,items,subitems,1,1 FROM CORE_Menu_Nodos
WHERE id_modulo=7 AND estado=1;

-- Rol 14 (ASIST_GCIA): módulo 7 + módulo 11 lectura
INSERT INTO CORE_Permisos_Nodo(id_rol,id_modulo,opcion,items,subitems,nivel_crud,asignado_por)
SELECT 14,id_modulo,opcion,items,subitems,1,1 FROM CORE_Menu_Nodos
WHERE id_modulo IN (7,11) AND estado=1;

-- Rol 21 (LECTOR): módulo 7 opcion cabecera lectura
INSERT INTO CORE_Permisos_Nodo(id_rol,id_modulo,opcion,items,subitems,nivel_crud,asignado_por)
SELECT 21,id_modulo,opcion,items,subitems,1,1 FROM CORE_Menu_Nodos
WHERE id_modulo=7 AND opcion=0 AND estado=1;
GO

-- ============================================================
-- SEMILLA: TH
-- ============================================================

SET IDENTITY_INSERT TH_Empleados ON;
INSERT INTO TH_Empleados(id_empleado,id_usuario,cedula,nombres,apellidos,id_departamento,cargo,fecha_ingreso,genero,estado) VALUES
(1,16,'1300000001','Carlos','Mendoza Vera',2,'Gerente General','2018-03-01','M',1),
(2,14,'1300000002','Patricia','Salazar Torres',10,'Directora Talento Humano','2019-06-15','F',1),
(3,15,'1300000003','Andrea','Cevallos Mora',17,'Analista Talento Humano','2021-01-10','F',1),
(4,11,'1300000004','Marco','Alvarado Pino',5,'Director Infraestructura','2017-08-20','M',1),
(5,3,'1300000005','Luis','Reyes Castro',4,'Director Jurídico','2016-02-28','M',1),
(6,18,'1300000006','Ana','Delgado Suárez',8,'Directora Administrativa','2020-04-05','F',1),
(7,20,'1300000007','Roberto','Loor Chávez',9,'Director Financiero','2019-11-15','M',1),
(8,6,'1300000008','Silvia','Gómez Reyes',15,'Supervisora Control Acceso','2022-03-01','F',1),
(9,12,'1300000009','Pedro','Navarrete Bowen',16,'Inspector Portuario','2023-01-15','M',1),
(10,4,'1300000010','Diana','Torres Leal',13,'Abogada','2021-07-01','F',1),
(11,2,'1300000011','Mario','Intriago Zambrano',12,'Auditor Interno','2020-09-20','M',1);
SET IDENTITY_INSERT TH_Empleados OFF;
GO

INSERT INTO TH_Contratos(id_empleado,tipo_contrato,fecha_inicio,salario,cargo,id_departamento,estado_contrato) VALUES
(1,'Nombramiento','2018-03-01',5800.00,'Gerente General',2,'Vigente'),
(2,'Nombramiento','2019-06-15',3200.00,'Directora Talento Humano',10,'Vigente'),
(3,'Contrato','2021-01-10',1800.00,'Analista Talento Humano',17,'Vigente'),
(4,'Nombramiento','2017-08-20',3400.00,'Director Infraestructura',5,'Vigente'),
(5,'Nombramiento','2016-02-28',3600.00,'Director Jurídico',4,'Vigente'),
(6,'Nombramiento','2020-04-05',3100.00,'Directora Administrativa',8,'Vigente'),
(7,'Nombramiento','2019-11-15',3300.00,'Director Financiero',9,'Vigente'),
(8,'Contrato','2022-03-01',1500.00,'Supervisora Control Acceso',15,'Vigente'),
(9,'Ocasional','2023-01-15',1200.00,'Inspector Portuario',16,'Vigente'),
(10,'Contrato','2021-07-01',1600.00,'Abogada',13,'Vigente'),
(11,'Nombramiento','2020-09-20',2200.00,'Auditor Interno',12,'Vigente');
GO

-- ============================================================
-- SEMILLA: BIT_ y BIENES_
-- ============================================================

INSERT INTO BIT_Categorias(codigo,nombre,color) VALUES
('MANTTO','Mantenimiento','#fd7e14'),
('SEGURIDAD','Seguridad','#dc3545'),
('OPERACIONES','Operaciones Portuarias','#0056b3'),
('INCIDENTE','Incidentes','#ffc107'),
('ADMIN','Administrativo','#6c757d');
GO

INSERT INTO BIENES_Categorias(codigo,nombre) VALUES
('TI','Tecnología e Informática'),
('MUEBLES','Mobiliario de Oficina'),
('VEHICULOS','Vehículos'),
('EQUIPOS','Equipos y Maquinaria'),
('OTROS','Otros Bienes');
GO

-- ============================================================
-- SEMILLA: CORE_Config
-- SSO_SECRET debe rotar periódicamente — leer desde aquí en PHP,
-- no hardcodear en ModuleSecurity.php
-- ============================================================

INSERT INTO CORE_Config(modulo,clave,valor,tipo,descripcion) VALUES
('CORE','LOGIN_MAX_INTENTOS','5','int','Intentos antes de bloqueo'),
('CORE','LOGIN_BLOQUEO_MINUTOS','30','int','Minutos de bloqueo por exceso de intentos'),
('CORE','AUDIT_RETENTION_YEARS','2','int','Años de retención de auditoría'),
('CORE','PASSWORD_HISTORIAL_MAX','5','int','Máximo de contraseñas anteriores en historial'),
('CORE','SESSION_TIMEOUT_MIN','30','int','Tiempo de inactividad antes de expirar sesión (min)'),
('CORE','SSO_SECRET','CAMBIAR_EN_PRODUCCION_MIN32CHARS_HMAC_SHA256','string','Clave HMAC-SHA256 para tokens SSO — cambiar antes de producción'),
('TH','MAX_CONTRATOS_VIGENTES','1','int','Contratos vigentes simultáneos por empleado'),
('BIT','PRIORIDAD_ALTA_COLOR','#dc3545','string','Color para eventos de prioridad alta');
GO

PRINT '=== Portal APM v2.1 instalado correctamente ===';
PRINT 'Menú MOIS: CORE_Menu_Nodos + CORE_Permisos_Nodo';
PRINT 'Nodos: 134 distribuidos en 11 módulos organizacionales';
PRINT 'Permisos: 21 roles con INSERT...SELECT sobre jerarquía MOIS';
PRINT 'nivel_jerarquia: 0=Operador 1=Analista 2=Director 3=Gerente 4=SuperAdmin';
PRINT 'tema_preferido: light | dark | corporate';
PRINT 'Acceso inicial: admin / Apm2024*  (nivel_jerarquia=4)';
PRINT 'SSO_SECRET en CORE_Config — leer desde PHP, no hardcodear';
GO
