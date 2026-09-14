/* ============================================================================
   core_modulos_sync_generico.sql — Sync genérico y data-driven de permisos
   hacia módulos con RBAC propio (reemplaza SyncPermisosModulo::centralHaciaTh/
   centralHaciaBienes hardcodeados por PHP).
   ============================================================================
   Ejecutar contra PORTAL_APM. Idempotente (IF NOT EXISTS / MERGE en los
   INSERT de datos) — se puede correr de nuevo sin duplicar nada.

   POR QUÉ:
   Antes, agregar un módulo nuevo con su propio sistema de permisos (como
   Talento Humano) requería escribir un método PHP nuevo en
   core/SyncPermisosModulo.php con un array hardcodeado "opción → código
   interno del módulo". Con esto, agregar un módulo nuevo compatible con
   este patrón (rol_id + N columnas booleanas de permiso, con o sin JOIN a
   una tabla de catálogo) es SOLO 2 INSERTs de datos — cero PHP nuevo. Ver
   modules/Central/controllers/ModuloController.php (acción `sync`) para la
   UI de administración que arma estos INSERTs por formulario.

   HALLAZGO REAL AL MIGRAR (2026-09-13): SyncPermisosModulo::centralHaciaBienes()
   apuntaba a una tabla `inv_permisos_rol` que NO EXISTE en la base
   `inventario` actual — Control de Bienes cambió su modelo de permisos a
   uno POR USUARIO (inv_permisos_detalle, columna usuario_id, sin rol_id)
   en su actualización de origen del 2026-08-18, y ese método nunca se
   actualizó. Cada sqlsrv_query() fallaba en silencio (nunca se revisaba el
   valor de retorno) — el sync a Bienes lleva roto, sin avisar a nadie,
   desde esa fecha. NO se migra Bienes acá: un modelo por-usuario no es
   "rol → permisos", es un problema distinto (ver nota al final del
   archivo). Talento Humano SÍ es rol-based real y se migra completo.
   ============================================================================ */

USE PORTAL_APM;
GO

/* ---------------------------------------------------------------------------
   1. Tablas
   --------------------------------------------------------------------------- */
IF NOT EXISTS (SELECT 1 FROM sys.tables WHERE name = 'CORE_Modulos_Sync_Config')
BEGIN
    CREATE TABLE dbo.CORE_Modulos_Sync_Config (
        id_modulo               TINYINT      NOT NULL PRIMARY KEY,   -- FK lógica a CORE_Modulos.id_modulo
        tabla_permisos          SYSNAME      NOT NULL,               -- ej. 'th_permisos_rol'
        columna_rol_id          SYSNAME      NOT NULL,               -- ej. 'rol_id'
        columna_identificador   SYSNAME      NOT NULL,               -- ej. 'codigo_modulo' (columna donde vive el identificador, en tabla_join si hay join, si no en tabla_permisos)

        -- Si el identificador NO está directo en tabla_permisos (caso TH:
        -- th_permisos_rol solo tiene modulo_id numérico, hay que pasar por
        -- th_modulos para llegar a codigo_modulo) completar estos 3:
        tabla_join              SYSNAME      NULL,                   -- ej. 'th_modulos'
        columna_join_permisos   SYSNAME      NULL,                   -- columna de JOIN en tabla_permisos, ej. 'modulo_id'
        columna_join_externa    SYSNAME      NULL,                   -- columna de JOIN en tabla_join, ej. 'modulo_id'

        columna_visualizar      SYSNAME      NOT NULL DEFAULT 'puede_visualizar',
        columna_crear           SYSNAME      NOT NULL DEFAULT 'puede_crear',
        columna_editar          SYSNAME      NOT NULL DEFAULT 'puede_editar',
        columna_eliminar        SYSNAME      NOT NULL DEFAULT 'puede_eliminar',

        -- 'UPDATE_JOIN'   = UPDATE ... FROM tabla_permisos JOIN tabla_join (fila ya existe siempre, caso TH)
        -- 'MERGE_DIRECTO' = MERGE sobre tabla_permisos directo, sin join, upsert (rol_id+columna_identificador nuevos se insertan)
        modo_escritura          VARCHAR(15)  NOT NULL DEFAULT 'UPDATE_JOIN'
                                             CONSTRAINT CK_syncconfig_modo CHECK (modo_escritura IN ('UPDATE_JOIN','MERGE_DIRECTO')),

        sp_auditoria            SYSNAME      NULL,                   -- opcional: nombre de un SP con firma (@usuario,@modulo,@accion,@descripcion,@ip='0.0.0.0') — ej. 'sp_th_registrar_auditoria'
        estado                  BIT          NOT NULL DEFAULT 1,
        notas                   NVARCHAR(500) NULL,
        fecha_creacion          DATETIME2    NOT NULL DEFAULT SYSDATETIME(),
        fecha_actualizacion     DATETIME2    NULL
    );
END
GO

IF NOT EXISTS (SELECT 1 FROM sys.tables WHERE name = 'CORE_Modulos_Sync_Nodos')
BEGIN
    CREATE TABLE dbo.CORE_Modulos_Sync_Nodos (
        id_modulo               TINYINT       NOT NULL,
        opcion                  SMALLINT      NOT NULL,               -- "opción" MOIS bajo ese id_modulo (mismo número que CORE_Menu_Nodos.opcion)
        identificador_externo   NVARCHAR(100) NOT NULL,                -- valor real en la columna_identificador del módulo destino
        descripcion             NVARCHAR(200) NULL,                    -- solo para que la UI muestre algo legible, no se usa en la sincronización
        CONSTRAINT PK_core_modulos_sync_nodos PRIMARY KEY (id_modulo, opcion),
        CONSTRAINT FK_core_modulos_sync_nodos_config FOREIGN KEY (id_modulo)
            REFERENCES dbo.CORE_Modulos_Sync_Config(id_modulo) ON DELETE CASCADE
    );
END
GO

/* ---------------------------------------------------------------------------
   2. Config real: Talento Humano (id_modulo = 11)
   Migra 1:1 el array NODOS_TH + la query UPDATE...FROM...JOIN que tenía
   hardcodeada SyncPermisosModulo::centralHaciaTh() — mismo comportamiento,
   ahora como datos.
   --------------------------------------------------------------------------- */
IF NOT EXISTS (SELECT 1 FROM dbo.CORE_Modulos_Sync_Config WHERE id_modulo = 11)
BEGIN
    INSERT INTO dbo.CORE_Modulos_Sync_Config
        (id_modulo, tabla_permisos, columna_rol_id, columna_identificador,
         tabla_join, columna_join_permisos, columna_join_externa,
         columna_visualizar, columna_crear, columna_editar, columna_eliminar,
         modo_escritura, sp_auditoria, estado, notas)
    VALUES
        (11, 'th_permisos_rol', 'rol_id', 'codigo_modulo',
         'th_modulos', 'modulo_id', 'modulo_id',
         'puede_visualizar', 'puede_crear', 'puede_editar', 'puede_eliminar',
         'UPDATE_JOIN', 'sp_th_registrar_auditoria', 1,
         N'Migrado desde SyncPermisosModulo::centralHaciaTh() hardcodeado (2026-09-13). th_permisos_rol siempre tiene la fila (rol_id, modulo_id) precreada para todo rol — por eso UPDATE_JOIN alcanza, nunca hace falta INSERT.');
END
GO

IF NOT EXISTS (SELECT 1 FROM dbo.CORE_Modulos_Sync_Nodos WHERE id_modulo = 11)
BEGIN
    INSERT INTO dbo.CORE_Modulos_Sync_Nodos (id_modulo, opcion, identificador_externo, descripcion) VALUES
        (11, 1,  'dashboard',      'Inicio'),
        (11, 2,  'directorio',     'Directorio de Personal'),
        (11, 3,  'empleados',      'Formulario de Ingreso'),
        (11, 4,  'acciones',       'Acción de Personal'),
        (11, 5,  'movimientos',    'Movimientos internos'),
        (11, 6,  'socioeconomico', 'Estudio Socioeconómico'),
        (11, 7,  'biblioteca',     'Biblioteca de Formularios'),
        (11, 8,  'maestros',       'Estructura y cargos'),
        (11, 9,  'usuarios',       'Administración de Usuarios'),
        (11, 10, 'roles',          'Roles y Permisos'),
        (11, 11, 'politicas',      'Políticas y Normativas'),
        (11, 12, 'auditoria',      'Auditoría y Control'),
        (11, 13, 'reportes',       'Reportes Generales'),
        (11, 14, 'prototipos',     'Prototipos (Asistencia/Vacaciones/Desempeño/Capacitación)');
END
GO

PRINT N'CORE_Modulos_Sync_Config / CORE_Modulos_Sync_Nodos listas. Talento Humano (id_modulo=11) migrado y activo.';
GO

/* ============================================================================
   NOTA — Control de Bienes (id_modulo = 12): NO migrado a propósito.

   Su tabla real de permisos (inv_permisos_detalle) es POR USUARIO
   (usuario_id + route_key + scope_key + 4 flags), sin ningún concepto de
   rol_id. Este motor genérico sincroniza "rol del portal → permisos del
   módulo" — no tiene sentido para un modelo sin roles. Forzarlo acá
   produciría una fila que "parece" configurada pero nunca aplicaría nada
   correcto (no hay rol_id que matchear).

   Si en el futuro se quiere sincronizar Bienes de verdad, hace falta un
   camino aparte: portal rol → lista de usuario_id con ese rol (via
   CORE_Usuarios/CORE_Usuarios_Roles + el cruce por cédula) → UPDATE/MERGE
   por cada usuario_id en inv_permisos_detalle. Es un problema distinto
   (rol→muchos usuarios, no rol→rol), fuera del alcance de este motor.
   ============================================================================ */
