<?php
/**
 * SETUP_DATABASE.PHP - Script de Configuración y Restauración de Base de Datos
 * Ejecuta la restauración de inventario.bak y Talento_Humano.bak,
 * crea vistas cruzadas e instala el disparador de sincronización de personal.
 */

// Inhabilitar límite de tiempo para restauración
set_time_limit(0);

// Cargar globals
require_once 'config/globals.php';

/**
 * Ejecuta un archivo de SQL Server respetando los separadores GO de sqlcmd.
 */
function ejecutarMigracionSqlServer(PDO $pdo, string $archivo): void
{
    $sql = file_get_contents($archivo);
    if ($sql === false) {
        throw new RuntimeException("No se pudo leer la migracion: {$archivo}");
    }

    $lotes = preg_split('/^\s*GO\s*(?:--.*)?$/mi', $sql);
    foreach ($lotes as $lote) {
        $lote = trim($lote);
        if ($lote !== '') {
            $pdo->exec($lote);
        }
    }
}

/**
 * Encuentra el primer respaldo existente. Los patrones se ordenan por fecha
 * para que una instalacion nueva use automaticamente la copia mas reciente.
 */
function buscarRespaldo(array $rutas, array $patrones = []): string
{
    foreach ($rutas as $ruta) {
        $real = realpath($ruta);
        if ($real !== false && is_file($real)) return $real;
    }
    $encontrados = [];
    foreach ($patrones as $patron) {
        foreach (glob($patron) ?: [] as $archivo) {
            if (is_file($archivo)) $encontrados[] = $archivo;
        }
    }
    usort($encontrados, static function (string $a, string $b): int {
        return filemtime($b) <=> filemtime($a);
    });
    if (!$encontrados) throw new RuntimeException('No se encontro un respaldo compatible en el proyecto.');
    return (string)realpath($encontrados[0]);
}

echo "=== INICIANDO CONFIGURACIÓN DE BASE DE DATOS ===\n";

try {
    // 1. Conectar al servidor (usando base de datos master)
    $dsnMaster = "sqlsrv:Server=" . DB_HOST . ";TrustServerCertificate=1";
    echo "Conectando a SQL Server: " . DB_HOST . "...\n";
    $pdoMaster = new PDO($dsnMaster, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    echo "Conexión a SQL Server exitosa.\n\n";

    // 2. Obtener rutas por defecto para datos y logs
    echo "Consultando rutas de almacenamiento por defecto...\n";
    $stmtPath = $pdoMaster->query("SELECT SERVERPROPERTY('InstanceDefaultDataPath') AS DefaultData, SERVERPROPERTY('InstanceDefaultLogPath') AS DefaultLog");
    $rowPath = $stmtPath->fetch(PDO::FETCH_ASSOC);
    $defaultData = $rowPath['DefaultData'] ?: "C:\\Program Files\\Microsoft SQL Server\\MSSQL16.JORDANYMB1\\MSSQL\\DATA\\";
    $defaultLog = $rowPath['DefaultLog'] ?: "C:\\Program Files\\Microsoft SQL Server\\MSSQL16.JORDANYMB1\\MSSQL\\DATA\\";
    
    // Asegurar diagonal final
    $defaultData = rtrim($defaultData, '\\') . '\\';
    $defaultLog = rtrim($defaultLog, '\\') . '\\';
    echo "Ruta DATA: {$defaultData}\n";
    echo "Ruta LOG : {$defaultLog}\n\n";

    // 3. Restaurar Base de Datos "inventario"
    echo "Verificando base de datos 'inventario'...\n";
    $dbExists = false;
    $stmtCheckDb = $pdoMaster->query("SELECT 1 FROM sys.databases WHERE name = 'inventario'");
    if ($stmtCheckDb->fetch()) {
        $dbExists = true;
    }

    if (!$dbExists || isset($_GET['force_restore']) || (php_sapi_name() === 'cli' && in_array('--force', $argv))) {
        echo "Restaurando base de datos 'inventario' desde inventario.bak...\n";
        
        // Poner en SINGLE_USER para liberar conexiones si ya existe
        if ($dbExists) {
            $pdoMaster->exec("ALTER DATABASE [inventario] SET SINGLE_USER WITH ROLLBACK IMMEDIATE");
        }

        $backupFile = buscarRespaldo(
            [__DIR__ . DIRECTORY_SEPARATOR . 'inventario.bak'],
            [__DIR__ . DIRECTORY_SEPARATOR . 'backup' . DIRECTORY_SEPARATOR . 'inventario_*.bak']
        );
        echo "Respaldo seleccionado: {$backupFile}\n";
        $sqlRestore = "RESTORE DATABASE [inventario] FROM DISK = :backup_file 
                       WITH FILE = 1, REPLACE,
                       MOVE 'inventario' TO :mdf_path,
                       MOVE 'inventario_log' TO :ldf_path";
                       
        $stmtRestore = $pdoMaster->prepare($sqlRestore);
        $stmtRestore->execute([
            ':backup_file' => $backupFile,
            ':mdf_path' => $defaultData . "inventario.mdf",
            ':ldf_path' => $defaultLog . "inventario_log.ldf"
        ]);
        
        $pdoMaster->exec("ALTER DATABASE [inventario] SET MULTI_USER");
        echo "Base de datos 'inventario' restaurada exitosamente.\n\n";
    } else {
        echo "Base de datos 'inventario' ya existe. Omitiendo restauración.\n\n";
    }

    // 4. Restaurar Base de Datos "Talento_Humano"
    echo "Verificando base de datos 'Talento_Humano'...\n";
    $thExists = false;
    $stmtCheckTh = $pdoMaster->query("SELECT 1 FROM sys.databases WHERE name = 'Talento_Humano'");
    if ($stmtCheckTh->fetch()) {
        $thExists = true;
    }

    if (!$thExists || isset($_GET['force_restore']) || (php_sapi_name() === 'cli' && in_array('--force', $argv))) {
        echo "Restaurando base de datos 'Talento_Humano' desde Talento_Humano.bak...\n";
        
        if ($thExists) {
            $pdoMaster->exec("ALTER DATABASE [Talento_Humano] SET SINGLE_USER WITH ROLLBACK IMMEDIATE");
        }

        $backupFileTh = buscarRespaldo([
            __DIR__ . DIRECTORY_SEPARATOR . 'talento_humano' . DIRECTORY_SEPARATOR . 'base de datos' . DIRECTORY_SEPARATOR . 'Talento_Humano.bak',
            dirname(__DIR__) . DIRECTORY_SEPARATOR . 'base_talentoHumano' . DIRECTORY_SEPARATOR . 'Talento_Humano_pre_mejoras_operativas_20260729_115921.bak',
        ]);
        echo "Respaldo seleccionado: {$backupFileTh}\n";
        $sqlRestoreTh = "RESTORE DATABASE [Talento_Humano] FROM DISK = :backup_file
                         WITH FILE = 1, REPLACE,
                         MOVE 'Talento_Humano' TO :mdf_path,
                         MOVE 'Talento_Humano_log' TO :ldf_path";
                         
        $stmtRestoreTh = $pdoMaster->prepare($sqlRestoreTh);
        $stmtRestoreTh->execute([
            ':backup_file' => $backupFileTh,
            ':mdf_path' => $defaultData . "Talento_Humano.mdf",
            ':ldf_path' => $defaultLog . "Talento_Humano_log.ldf"
        ]);

        $pdoMaster->exec("ALTER DATABASE [Talento_Humano] SET MULTI_USER");
        echo "Base de datos 'Talento_Humano' restaurada exitosamente.\n\n";
    } else {
        echo "Base de datos 'Talento_Humano' ya existe. Omitiendo restauración.\n\n";
    }

    // 4.1. Crear base de datos "APM_Tramites" y su tabla de prueba
    echo "Verificando base de datos de trámites en línea de la APM ('APM_Tramites')...\n";
    $apmDbExists = false;
    $stmtCheckApm = $pdoMaster->query("SELECT 1 FROM sys.databases WHERE name = 'APM_Tramites'");
    if ($stmtCheckApm->fetch()) {
        $apmDbExists = true;
    }
    if (!$apmDbExists) {
        echo "Creando base de datos 'APM_Tramites'...\n";
        $pdoMaster->exec("CREATE DATABASE APM_Tramites");
        echo "Base de datos 'APM_Tramites' creada con éxito.\n";
    }

    $dsnApm = "sqlsrv:Server=" . DB_HOST . ";Database=APM_Tramites;TrustServerCertificate=1";
    $pdoApm = new PDO($dsnApm, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    $pdoApm->exec("
        IF OBJECT_ID('dbo.apm_tramites_linea', 'U') IS NULL
        BEGIN
            CREATE TABLE dbo.apm_tramites_linea (
                ruc VARCHAR(20) PRIMARY KEY,
                nombre VARCHAR(150) NOT NULL,
                tipo VARCHAR(20) NOT NULL,
                direccion VARCHAR(200) NULL
            );
            
            INSERT INTO dbo.apm_tramites_linea (ruc, nombre, tipo, direccion) VALUES
            ('1309140808', 'JAVIER PALACIOS (APM Online Persona)', 'persona', 'Manta Central'),
            ('1790001234001', 'IMPORTADORA PACIFICO S.A. (APM Online Empresa)', 'empresa', 'Manta Puerto');
        END
    ");
    echo "Tabla 'apm_tramites_linea' configurada en 'APM_Tramites'.\n\n";

    // 4.5. Configurar tablas y vistas faltantes en "Talento_Humano"
    $dsnTh = "sqlsrv:Server=" . DB_HOST . ";Database=Talento_Humano;TrustServerCertificate=1";
    $pdoTh = new PDO($dsnTh, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    echo "Configurando tablas y vistas faltantes en 'Talento_Humano'...\n";
    
    // Crear th_historial_laboral
    $sqlCreateHistorial = "
    IF OBJECT_ID('dbo.th_historial_laboral', 'U') IS NULL
    BEGIN
        CREATE TABLE dbo.th_historial_laboral (
            historial_id INT IDENTITY(1,1) PRIMARY KEY,
            empleado_id INT NOT NULL,
            puesto_id INT NOT NULL,
            unidad_id INT NOT NULL,
            fecha_desde DATE NOT NULL,
            fecha_hasta DATE NULL,
            CONSTRAINT FK_Historial_Empleado FOREIGN KEY (empleado_id) REFERENCES th_empleados(empleado_id) ON DELETE CASCADE,
            CONSTRAINT FK_Historial_Puesto FOREIGN KEY (puesto_id) REFERENCES th_puestos(puesto_id),
            CONSTRAINT FK_Historial_Unidad FOREIGN KEY (unidad_id) REFERENCES th_unidades_organizacionales(unidad_id)
        );
    END
    ";
    $pdoTh->exec($sqlCreateHistorial);
    echo "Tabla 'th_historial_laboral' verificada/creada.\n";

    // Asegurar columnas sucedido_por_id y fecha_fin en th_unidades_organizacionales
    $pdoTh->exec("
    IF NOT EXISTS (
        SELECT 1 FROM sys.columns
        WHERE object_id = OBJECT_ID('dbo.th_unidades_organizacionales') AND name = 'sucedido_por_id'
    )
    BEGIN
        ALTER TABLE dbo.th_unidades_organizacionales ADD sucedido_por_id INT NULL;
        ALTER TABLE dbo.th_unidades_organizacionales
            ADD CONSTRAINT FK_Unidad_Sucedida
            FOREIGN KEY (sucedido_por_id) REFERENCES dbo.th_unidades_organizacionales(unidad_id);
    END
    ");
    
    $pdoTh->exec("
    IF NOT EXISTS (
        SELECT 1 FROM sys.columns
        WHERE object_id = OBJECT_ID('dbo.th_unidades_organizacionales') AND name = 'fecha_fin'
    )
    BEGIN
        ALTER TABLE dbo.th_unidades_organizacionales ADD fecha_fin DATE NULL;
    END
    ");
    echo "Columnas sucedido_por_id y fecha_fin aseguradas en th_unidades_organizacionales.\n";

    // Crear vw_th_reporte_historial_jerarquico
    $sqlCreateViewHistorial = "
    CREATE OR ALTER VIEW dbo.vw_th_reporte_historial_jerarquico AS
    SELECT
        e.empleado_id,
        e.identificacion AS cedula,
        e.apellidos + ' ' + e.nombres          AS funcionario,
        p.codigo_puesto,
        p.nombre_puesto,
        u.nombre_unidad                         AS departamento_historico,
        CASE
            WHEN u_padre.unidad_id IS NOT NULL THEN u_padre.nombre_unidad
            ELSE u.nombre_unidad
        END                                     AS direccion_padre,
        CASE
            WHEN u_padre.unidad_id IS NOT NULL THEN u.nombre_unidad
            ELSE NULL
        END                                     AS sub_area,
        ISNULL(u_nueva.nombre_unidad, u.nombre_unidad)      AS direccion_actual_unificada,
        ISNULL(u_nueva.tipo_proceso,  u.tipo_proceso)       AS tipo_proceso,
        h.fecha_desde,
        h.fecha_hasta,
        DATEDIFF(year, h.fecha_desde, ISNULL(h.fecha_hasta, GETDATE())) AS anios_permanencia,
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
    LEFT JOIN th_unidades_organizacionales u_padre ON u.unidad_padre_id = u_padre.unidad_id
    LEFT JOIN th_unidades_organizacionales u_nueva ON u.sucedido_por_id = u_nueva.unidad_id;
    ";
    $pdoTh->exec($sqlCreateViewHistorial);
    echo "Vista 'vw_th_reporte_historial_jerarquico' verificada/creada.\n";

    // Crear vw_th_acciones_resumen
    $sqlCreateViewAcciones = "
    CREATE OR ALTER VIEW dbo.vw_th_acciones_resumen AS
    SELECT
        ap.accion_id,
        ap.numero_accion,
        ap.fecha_elaboracion,
        ap.tipo_accion,
        ap.estado_documento,
        ap.fecha_rige_desde AS rige_desde,
        ap.fecha_rige_hasta AS rige_hasta,
        e.identificacion AS cedula_pasaporte,
        e.apellidos + ' ' + e.nombres AS apellidos_nombres,
        (SELECT TOP 1 nombre_puesto FROM th_puestos WHERE puesto_id = ap.actual_puesto_id) AS actual_puesto,
        ap.actual_remuneracion,
        (SELECT TOP 1 nombre_puesto FROM th_puestos WHERE puesto_id = ap.propuesta_puesto_id) AS propuesta_puesto,
        ap.propuesta_remuneracion,
        (ISNULL(ap.propuesta_remuneracion, 0) - ISNULL(ap.actual_remuneracion, 0)) AS diferencia_remuneracion
    FROM th_acciones_personal ap
    JOIN th_empleados e ON e.empleado_id = ap.empleado_id;
    ";
    $pdoTh->exec($sqlCreateViewAcciones);
    echo "Vista 'vw_th_acciones_resumen' verificada/creada.\n";

    // 5. Conectar a "inventario" para crear vistas cruzadas
    $dsnInventario = "sqlsrv:Server=" . DB_HOST . ";Database=inventario;TrustServerCertificate=1";
    $pdoInv = new PDO($dsnInventario, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    echo "Conectado a la base de datos 'inventario' para configurar vistas...\n";

    // Asegurar esquema de inv_estados para compatibilidad con el código PHP
    $pdoInv->exec("
        IF EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('dbo.inv_estados') AND name = 'id')
        BEGIN
            EXEC sp_rename 'inv_estados.id', 'idestado', 'COLUMN';
        END
    ");
    $pdoInv->exec("
        IF EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('dbo.inv_estados') AND name = 'nombre')
        BEGIN
            EXEC sp_rename 'inv_estados.nombre', 'descripcion', 'COLUMN';
        END
    ");
    $pdoInv->exec("
        IF EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('dbo.inv_estados') AND name = 'clase')
        BEGIN
            EXEC sp_rename 'inv_estados.clase', 'detalle', 'COLUMN';
        END
    ");
    $pdoInv->exec("
        IF NOT EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('dbo.inv_estados') AND name = 'estado')
        BEGIN
            ALTER TABLE inv_estados ADD estado INT NOT NULL DEFAULT 1;
        END
    ");
    echo "Esquema de inv_estados corregido/verificado.\n";

    // Asegurar parámetros requeridos en inv_parametros para búsquedas externas de proveedores/usuarios
    $pdoInv->exec("
        IF NOT EXISTS (SELECT 1 FROM inv_parametros WHERE clave = 'url_microservicio_personas')
        BEGIN
            INSERT INTO inv_parametros (clave, valor, descripcion) VALUES
            ('url_microservicio_personas', 'http://localhost/Control_bines/public/mock_api.php', 'URL del microservicio de consulta de personas naturales (RUC/Cedula)');
        END
    ");
    $pdoInv->exec("
        IF NOT EXISTS (SELECT 1 FROM inv_parametros WHERE clave = 'url_microservicio_empresas')
        BEGIN
            INSERT INTO inv_parametros (clave, valor, descripcion) VALUES
            ('url_microservicio_empresas', 'http://localhost/Control_bines/public/mock_api.php', 'URL del microservicio de consulta de empresas (RUC)');
        END
    ");
    echo "Parámetros de microservicios registrados en inv_parametros.\n";

    $vistas = [
        'th_empleados' => 'Talento_Humano.dbo.th_empleados',
        'th_unidades_organizacionales' => 'Talento_Humano.dbo.th_unidades_organizacionales',
        'th_puestos' => 'Talento_Humano.dbo.th_puestos',
        'th_historial_laboral' => 'Talento_Humano.dbo.th_historial_laboral',
        'th_acciones_personal' => 'Talento_Humano.dbo.th_acciones_personal',
        'view_th_iddatosempledo' => 'Talento_Humano.dbo.view_th_iddatosempledo',
        'vw_th_reporte_historial_jerarquico' => 'Talento_Humano.dbo.vw_th_reporte_historial_jerarquico',
        'vw_th_acciones_resumen' => 'Talento_Humano.dbo.vw_th_acciones_resumen'
    ];

    foreach ($vistas as $vistaNombre => $tablaOrigen) {
        echo "Configurando vista '{$vistaNombre}' -> '{$tablaOrigen}'...\n";
        
        // Eliminar si existe como tabla o vista
        $pdoInv->exec("IF OBJECT_ID('dbo.{$vistaNombre}', 'U') IS NOT NULL DROP TABLE dbo.{$vistaNombre}");
        $pdoInv->exec("IF OBJECT_ID('dbo.{$vistaNombre}', 'V') IS NOT NULL DROP VIEW dbo.{$vistaNombre}");
        
        // Crear vista
        $pdoInv->exec("CREATE VIEW dbo.{$vistaNombre} AS SELECT * FROM {$tablaOrigen}");
    }
    echo "Vistas cruzadas configuradas con éxito.\n\n";

    // 6. Configurar Trigger de Sincronización en "Talento_Humano"
    echo "Configurando disparadores (triggers) en 'Talento_Humano'...\n";

    // Averiguar estructura de columnas de th_empleados
    $stmtCols = $pdoTh->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'th_empleados'");
    $cols = $stmtCols->fetchAll(PDO::FETCH_COLUMN);
    
    $nombreSelect = "nombres";
    if (in_array('apellidos', $cols) && in_array('nombres', $cols)) {
        $nombreSelect = "apellidos + ' ' + nombres";
    } elseif (in_array('apellidos_nombres', $cols)) {
        $nombreSelect = "apellidos_nombres";
    }
    
    $cedulaSelect = "cedula";
    if (in_array('identificacion', $cols)) {
        $cedulaSelect = "identificacion";
    } elseif (in_array('cedula_pasaporte', $cols)) {
        $cedulaSelect = "cedula_pasaporte";
    }

    echo "Estructura detectada para trigger: Nombre = {$nombreSelect}, Cédula = {$cedulaSelect}\n";

    $sqlTrigger = "
    CREATE OR ALTER TRIGGER trg_sync_th_empleados_to_inventario
    ON th_empleados
    AFTER INSERT, UPDATE, DELETE
    AS
    BEGIN
        SET NOCOUNT ON;
        
        -- Sincronizar Eliminaciones
        IF NOT EXISTS (SELECT 1 FROM inserted)
        BEGIN
            DELETE FROM inventario.dbo.inv_talento_personal
            WHERE id IN (SELECT empleado_id FROM deleted);
        END
        
        -- Sincronizar Inserciones
        IF EXISTS (SELECT 1 FROM inserted) AND NOT EXISTS (SELECT 1 FROM deleted)
        BEGIN
            SET IDENTITY_INSERT inventario.dbo.inv_talento_personal ON;
            INSERT INTO inventario.dbo.inv_talento_personal (id, nombre, identificacion)
            SELECT empleado_id, {$nombreSelect}, {$cedulaSelect}
            FROM inserted;
            SET IDENTITY_INSERT inventario.dbo.inv_talento_personal OFF;
        END
        
        -- Sincronizar Modificaciones
        IF EXISTS (SELECT 1 FROM inserted) AND EXISTS (SELECT 1 FROM deleted)
        BEGIN
            UPDATE p
            SET p.nombre = i.{$nombreSelect},
                p.identificacion = i.{$cedulaSelect}
            FROM inventario.dbo.inv_talento_personal p
            JOIN inserted i ON p.id = i.empleado_id;
        END
    END
    ";

    $pdoTh->exec($sqlTrigger);
    echo "Disparador 'trg_sync_th_empleados_to_inventario' configurado con éxito.\n\n";

    // 7. Sincronizar datos iniciales de th_empleados a inv_talento_personal
    echo "Sincronizando registros de personal iniciales...\n";
    
    // Actualizar e insertar desde th_empleados sin borrar funcionarios
    // historicos, pues pueden estar referenciados por asignaciones antiguas.
    $sqlInitialSync = "
        UPDATE destino
        SET destino.nombre = {$nombreSelect},
            destino.identificacion = origen.{$cedulaSelect}
        FROM inv_talento_personal destino
        JOIN Talento_Humano.dbo.th_empleados origen
          ON origen.empleado_id = destino.id;

        SET IDENTITY_INSERT inv_talento_personal ON;
        INSERT INTO inv_talento_personal (id, nombre, identificacion)
        SELECT origen.empleado_id, {$nombreSelect}, origen.{$cedulaSelect}
        FROM Talento_Humano.dbo.th_empleados origen
        WHERE NOT EXISTS (
            SELECT 1 FROM inv_talento_personal destino
            WHERE destino.id = origen.empleado_id
        );
        SET IDENTITY_INSERT inv_talento_personal OFF;
    ";
    $pdoInv->exec($sqlInitialSync);
    
    echo "Registros iniciales sincronizados exitosamente.\n\n";

    // 8. Aplicar en orden las migraciones versionadas del proyecto.
    echo "Aplicando migraciones versionadas...\n";
    $migraciones = [
        'inv_20260727_modelo_inventario.sql',
        'th_20260727_responsables_inventario.sql',
        'inv_20260727_centros_consumo_personal.sql',
        'inv_20260803_prevenir_duplicados_catalogo.sql',
        'inv_20260803_talento_humano_vistas.sql',
        'inv_20260803_tiempos_sesion_inventario.sql',
        'inv_20260805_inactividad_por_usuario.sql',
        'inv_20260806_flujo_digital_egresos.sql',
        'inv_20260808_abastecimiento_bodega.sql',
        'inv_20260808_facturas_documentos.sql',
        'inv_20260808_proveedores_contacto.sql',
        'inv_20260810_notificaciones_auditoria_contexto.sql',
        'inv_20260813_clasificacion_items.sql',
        'inv_20260813_ingresos_factura_v2.sql',
        'inv_20260823_periodos_fecha_fin_opcional.sql',
        'inv_20260824_ordenes_compra_campos_modernos.sql',
        'inv_20260827_precision_monetaria.sql',
    ];
    foreach ($migraciones as $migracion) {
        $pdoInv->exec('USE [inventario]');
        ejecutarMigracionSqlServer($pdoInv, __DIR__ . '/database/migrations/' . $migracion);
        echo "- {$migracion}: OK\n";
    }
    echo "Migraciones aplicadas exitosamente.\n\n";

    echo "=== PROCESO DE BASE DE DATOS COMPLETADO CON ÉXITO ===\n";

} catch (Exception $e) {
    echo "\n[ERROR CRÍTICO]: " . $e->getMessage() . "\n";
    exit(1);
}
