<?php
/**
 * DATABASE.PHP - Clase de Conexión a Base de Datos (SQL Server & SQLite)
 * Compatible con PHP 7.4, 8.3 y 8.4
 */

class Database {
    private static $instances = [];
    private $pdo = null;
    private $dbPath;

    private function __construct(?string $dbName = null) {
        require_once ROOT_PATH . 'core/DatabaseConnection.php';
        
        try {
            $this->pdo = new DatabaseConnection($dbName);
            $driver = $this->pdo->getDriver();
            $isDefaultDb = ($dbName === null || $dbName === DB_NAME);

            if ($isDefaultDb) {
                if ($driver === 'pgsql') {
                    // Verificar si las tablas principales ya existen
                    $tableExists = false;
                    try {
                        $check = $this->pdo->query("SELECT 1 FROM pg_tables WHERE tablename = 'inv_usuarios'");
                        $tableExists = $check !== false && $check->fetchColumn() !== false;
                    } catch (Exception $e) {
                        $tableExists = false;
                    }

                    if (!$tableExists) {
                        $this->inicializarBaseDatosPostgreSQL();
                    } else {
                        $this->migrarBaseDatosExistentePostgreSQL();
                    }
                } elseif ($driver === 'sqlsrv') {
                    // Verificar si las tablas principales ya existen
                    $tableExists = false;
                    try {
                        $check = $this->pdo->query("SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = 'inv_usuarios'");
                        $tableExists = $check !== false && $check->fetchColumn() !== false;
                    } catch (Exception $e) {
                        $tableExists = false;
                    }

                    if (!$tableExists) {
                        $this->inicializarBaseDatosSQLServer();
                    } else {
                        $this->migrarBaseDatosExistenteSQLServer();
                    }
                } else {
                    // SQLite3
                    $tableExists = false;
                    try {
                        $check = $this->pdo->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name='inv_usuarios'");
                        $tableExists = $check !== false && $check->fetchColumn() !== false;
                    } catch (Exception $e) {
                        $tableExists = false;
                    }

                    if (!$tableExists) {
                        $this->inicializarBaseDatosSQLite();
                    } else {
                        $this->migrarBaseDatosExistenteSQLite();
                    }
                }
            }
        } catch (Exception $e) {
            die("Error de inicialización de Base de Datos (Nativa): " . $e->getMessage());
        }
    }

    public static function getInstance(?string $dbName = null) {
        $key = $dbName ?: 'default';
        if (!isset(self::$instances[$key])) {
            self::$instances[$key] = new self($dbName);
        }
        return self::$instances[$key];
    }

    public function getConnection() {
        return $this->pdo;
    }

    /**
     * Inicialización del esquema completo en PostgreSQL
     */
    private function inicializarBaseDatosPostgreSQL() {
        $queries = [
            // 1. Tablas Maestras
            "CREATE TABLE inv_categorias (
                id SERIAL PRIMARY KEY,
                nombre VARCHAR(255) NOT NULL UNIQUE,
                codigo VARCHAR(50),
                extra TEXT
            );",
            
            "CREATE TABLE inv_zonas (
                id SERIAL PRIMARY KEY,
                nombre VARCHAR(255) NOT NULL UNIQUE,
                extra TEXT
            );",
            
            "CREATE TABLE inv_estados (
                idestado INT PRIMARY KEY,
                descripcion VARCHAR(255) NOT NULL UNIQUE,
                detalle TEXT,
                estado INT NOT NULL DEFAULT 1
            );",
            
            "CREATE TABLE inv_marcas (
                id SERIAL PRIMARY KEY,
                nombre VARCHAR(255) NOT NULL UNIQUE,
                extra TEXT
            );",
            
            "CREATE TABLE inv_lineas (
                id SERIAL PRIMARY KEY,
                nombre VARCHAR(255) NOT NULL UNIQUE,
                extra TEXT
            );",

            // 2. Talento Humano
            "CREATE TABLE inv_talento_areas (
                id SERIAL PRIMARY KEY,
                nombre VARCHAR(255) NOT NULL UNIQUE
            );",

            "CREATE TABLE inv_talento_personal (
                id SERIAL PRIMARY KEY,
                nombre VARCHAR(255) NOT NULL,
                identificacion VARCHAR(50) NOT NULL UNIQUE,
                cargo VARCHAR(150),
                area VARCHAR(150),
                correo VARCHAR(150),
                estado BOOLEAN NOT NULL DEFAULT TRUE,
                fecha_sincronizacion TIMESTAMP
            );",

            "CREATE TABLE inv_talento_asignaciones (
                id SERIAL PRIMARY KEY,
                personal_id INT NOT NULL,
                area_id INT NOT NULL,
                fecha_inicio DATE NOT NULL,
                fecha_fin DATE,
                FOREIGN KEY (personal_id) REFERENCES inv_talento_personal(id) ON DELETE CASCADE,
                FOREIGN KEY (area_id) REFERENCES inv_talento_areas(id) ON DELETE CASCADE
            );",

            // 3. Períodos e IVA
            "CREATE TABLE inv_periodos (
                id SERIAL PRIMARY KEY,
                nombre VARCHAR(255) NOT NULL UNIQUE,
                fecha_inicio DATE NOT NULL,
                fecha_fin DATE NOT NULL,
                estado VARCHAR(50) NOT NULL DEFAULT 'activo'
            );",

            "CREATE TABLE inv_valores_iva (
                id SERIAL PRIMARY KEY,
                tasa_iva DOUBLE PRECISION NOT NULL,
                periodo_id INT NOT NULL,
                FOREIGN KEY (periodo_id) REFERENCES inv_periodos(id) ON DELETE CASCADE
            );",

            // 4. Catálogo de Unidades y Productos
            "CREATE TABLE inv_unidades (
                id SERIAL PRIMARY KEY,
                nombre VARCHAR(255) NOT NULL UNIQUE,
                extra TEXT
            );",

            "CREATE TABLE inv_tipos_iva (
                id SERIAL PRIMARY KEY,
                nombre VARCHAR(255) NOT NULL UNIQUE,
                tasa_iva DOUBLE PRECISION NOT NULL UNIQUE
            );",

            "CREATE TABLE inv_productos (
                id SERIAL PRIMARY KEY,
                nombre VARCHAR(255) NOT NULL UNIQUE,
                grupo_id INT NOT NULL,
                unidad_id INT NOT NULL,
                aplica_iva INT NOT NULL DEFAULT 1,
                codigo VARCHAR(50) DEFAULT '',
                descripcion TEXT DEFAULT '',
                ubicacion TEXT DEFAULT '',
                existencia_min DOUBLE PRECISION DEFAULT 0,
                existencia_max DOUBLE PRECISION DEFAULT 0,
                precio_promedio DOUBLE PRECISION DEFAULT 0,
                existencia_actual DOUBLE PRECISION DEFAULT 0,
                FOREIGN KEY (grupo_id) REFERENCES inv_categorias(id),
                FOREIGN KEY (unidad_id) REFERENCES inv_unidades(id)
            );",

            // 5. InvInventario General de Bienes
            "CREATE TABLE inv_inventario (
                id SERIAL PRIMARY KEY,
                secuencial VARCHAR(50) NOT NULL UNIQUE,
                nombre VARCHAR(255) NOT NULL,
                marca VARCHAR(255) NOT NULL,
                categoria_id INT NOT NULL,
                zona_id INT NOT NULL,
                estado_id INT NOT NULL,
                responsable_id INT,
                valor DOUBLE PRECISION NOT NULL DEFAULT 0.0,
                fecha_registro DATE NOT NULL,
                observaciones TEXT,
                activo INT NOT NULL DEFAULT 1,
                cantidad INT NOT NULL DEFAULT 1,
                producto_id INT,
                FOREIGN KEY (categoria_id) REFERENCES inv_categorias(id),
                FOREIGN KEY (zona_id) REFERENCES inv_zonas(id),
                FOREIGN KEY (estado_id) REFERENCES inv_estados(idestado),
                FOREIGN KEY (responsable_id) REFERENCES inv_talento_personal(id),
                FOREIGN KEY (producto_id) REFERENCES inv_productos(id)
            );",

            // 6. Respaldo Histórico
            "CREATE TABLE inv_respaldo_historico (
                id SERIAL PRIMARY KEY,
                periodo_id INT NOT NULL,
                item_id INT NOT NULL,
                secuencial VARCHAR(50) NOT NULL,
                nombre_historico VARCHAR(255) NOT NULL,
                marca_historica VARCHAR(255) NOT NULL,
                categoria_historica VARCHAR(255) NOT NULL,
                zona_historica VARCHAR(255) NOT NULL,
                estado_historico VARCHAR(255) NOT NULL,
                responsable_historico VARCHAR(255),
                area_talento_historica VARCHAR(255),
                valor_historico DOUBLE PRECISION NOT NULL,
                iva_aplicado DOUBLE PRECISION NOT NULL,
                fecha_registro DATE NOT NULL,
                fecha_corte DATE NOT NULL,
                FOREIGN KEY (periodo_id) REFERENCES inv_periodos(id) ON DELETE CASCADE
            );",

            // 7. Bitácora del Sistema
            "CREATE TABLE inv_bitacora (
                id SERIAL PRIMARY KEY,
                secuencial VARCHAR(50) NOT NULL UNIQUE,
                tipo VARCHAR(50) NOT NULL,
                modulo VARCHAR(50) NOT NULL,
                descripcion TEXT NOT NULL,
                fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );",

            // 8. Configuración de Secuenciales
            "CREATE TABLE inv_secuenciales (
                modulo VARCHAR(50) PRIMARY KEY,
                prefijo VARCHAR(50) NOT NULL,
                ultimo_numero INT NOT NULL DEFAULT 0
            );",

            // 9. Usuarios
            "CREATE TABLE inv_usuarios (
                id SERIAL PRIMARY KEY,
                secuencial VARCHAR(50) NOT NULL UNIQUE,
                nombre VARCHAR(255) NOT NULL,
                usuario VARCHAR(255) NOT NULL UNIQUE,
                contrasena TEXT NOT NULL,
                rol VARCHAR(50) NOT NULL,
                activo INT NOT NULL DEFAULT 1
            );",

            // 10. Bodega - Ingresos
            "CREATE TABLE inv_bod_ingresos (
                id SERIAL PRIMARY KEY,
                secuencial VARCHAR(50) NOT NULL UNIQUE,
                proveedor VARCHAR(255) NOT NULL,
                fecha DATE NOT NULL,
                observaciones TEXT,
                responsable_id INT NOT NULL,
                creado_por VARCHAR(255) NOT NULL DEFAULT 'Admin Terminal',
                FOREIGN KEY (responsable_id) REFERENCES inv_talento_personal(id)
            );",

            // 11. Bodega - Ingresos Detalles
            "CREATE TABLE inv_bod_ingresos_detalles (
                id SERIAL PRIMARY KEY,
                ingreso_id INT NOT NULL,
                item_id INT NOT NULL,
                cantidad INT NOT NULL,
                valor_unitario DOUBLE PRECISION NOT NULL,
                FOREIGN KEY (ingreso_id) REFERENCES inv_bod_ingresos(id) ON DELETE CASCADE,
                FOREIGN KEY (item_id) REFERENCES inv_inventario(id)
            );",

            // 12. Bodega - Egresos
            "CREATE TABLE inv_bod_egresos (
                id SERIAL PRIMARY KEY,
                secuencial VARCHAR(50) NOT NULL UNIQUE,
                area_id INT NOT NULL,
                responsable_id INT NOT NULL,
                fecha DATE NOT NULL,
                motivo TEXT NOT NULL,
                observaciones TEXT,
                creado_por VARCHAR(255) NOT NULL DEFAULT 'Admin Terminal',
                FOREIGN KEY (area_id) REFERENCES inv_talento_areas(id),
                FOREIGN KEY (responsable_id) REFERENCES inv_talento_personal(id)
            );",

            // 13. Bodega - Egresos Detalles
            "CREATE TABLE inv_bod_egresos_detalles (
                id SERIAL PRIMARY KEY,
                egreso_id INT NOT NULL,
                item_id INT NOT NULL,
                cantidad INT NOT NULL,
                FOREIGN KEY (egreso_id) REFERENCES inv_bod_egresos(id) ON DELETE CASCADE,
                FOREIGN KEY (item_id) REFERENCES inv_inventario(id)
            );",

            // 14. Parámetros de Configuración General
            "CREATE TABLE inv_parametros (
                clave VARCHAR(255) PRIMARY KEY,
                valor TEXT NOT NULL,
                descripcion TEXT
            );",

            // 15. Log de Errores del Sistema
            "CREATE TABLE inv_log_errores (
                id_error        SERIAL PRIMARY KEY,
                id_usuario      INT NULL,
                modulo          VARCHAR(50) NOT NULL DEFAULT 'sys',
                accion          VARCHAR(255) DEFAULT '',
                tipo_error      VARCHAR(50) NOT NULL DEFAULT 'Error',
                descripcion     TEXT NOT NULL,
                archivo_origen  VARCHAR(255) DEFAULT '',
                linea_origen    INT DEFAULT 0,
                nivel           VARCHAR(50) NOT NULL DEFAULT 'ERROR',
                entorno         VARCHAR(50) NOT NULL DEFAULT 'development',
                ip_cliente      VARCHAR(50) DEFAULT '',
                fecha_registro  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (id_usuario) REFERENCES inv_usuarios(id) ON DELETE SET NULL
            );",

            // 16. Log de Eventos del Sistema
            "CREATE TABLE inv_log_eventos (
                id_evento       SERIAL PRIMARY KEY,
                id_usuario      INT NULL,
                modulo          VARCHAR(50) NOT NULL DEFAULT 'sys',
                accion          VARCHAR(255) NOT NULL DEFAULT '',
                descripcion     TEXT DEFAULT '',
                resultado       VARCHAR(50) NOT NULL DEFAULT 'OK',
                ip_cliente      VARCHAR(50) DEFAULT '',
                fecha_registro  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (id_usuario) REFERENCES inv_usuarios(id) ON DELETE SET NULL
            );",

            // 17. Proveedores
            "CREATE TABLE inv_proveedores (
                id SERIAL PRIMARY KEY,
                nombre VARCHAR(255) NOT NULL UNIQUE,
                ruc VARCHAR(50) NOT NULL UNIQUE,
                extra TEXT
            );",

            // 18. Grupo de Centros de Consumo
            "CREATE TABLE inv_grupo_centros_consumo (
                id SERIAL PRIMARY KEY,
                codigo VARCHAR(50) NOT NULL UNIQUE,
                nombre VARCHAR(255) NOT NULL UNIQUE,
                representante VARCHAR(255),
                representante_id INT,
                FOREIGN KEY (representante_id) REFERENCES inv_talento_personal(id)
            );",

            // 19. Centros de Consumo
            "CREATE TABLE inv_centros_consumo (
                id SERIAL PRIMARY KEY,
                grupo_id INT NOT NULL,
                codigo VARCHAR(50) NOT NULL UNIQUE,
                nombre VARCHAR(255) NOT NULL,
                funcionario VARCHAR(255) NOT NULL,
                funcionario_id INT,
                FOREIGN KEY (grupo_id) REFERENCES inv_grupo_centros_consumo(id),
                FOREIGN KEY (funcionario_id) REFERENCES inv_talento_personal(id)
            );",

            // 20. Notificaciones
            "CREATE TABLE inv_notificaciones (
                id SERIAL PRIMARY KEY,
                tipo VARCHAR(50) NOT NULL,
                categoria VARCHAR(50) NOT NULL,
                titulo VARCHAR(255) NOT NULL,
                mensaje TEXT NOT NULL,
                secuencial VARCHAR(50),
                visto INT DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );",
            // 21. Permisos de Usuarios
            "CREATE TABLE inv_permisos (
                id SERIAL PRIMARY KEY,
                usuario_id INT NOT NULL,
                route_key VARCHAR(255) NOT NULL,
                UNIQUE(usuario_id, route_key),
                FOREIGN KEY (usuario_id) REFERENCES inv_usuarios(id) ON DELETE CASCADE
            );"
        ];

        foreach ($queries as $q) {
            $this->pdo->exec($q);
        }

        $this->cargarDatosSemilla('PostgreSQL');
    }

    private function migrarBaseDatosExistentePostgreSQL() {
        try {
            $checkProv = $this->pdo->query("SELECT 1 FROM pg_tables WHERE tablename = 'inv_proveedores'");
            if ($checkProv === false || $checkProv->fetchColumn() === false) {
                $this->pdo->exec("CREATE TABLE inv_proveedores (
                    id SERIAL PRIMARY KEY,
                    nombre VARCHAR(255) NOT NULL UNIQUE,
                    ruc VARCHAR(50) NOT NULL UNIQUE,
                    extra TEXT
                );");
                
                $this->pdo->exec("INSERT INTO inv_proveedores (nombre, ruc, extra) VALUES 
                    ('Importadora Portuaria S.A.', '0992384712001', 'Av. Malecón y Calle 15, Manta - Telf: 0987654321'),
                    ('Consorcio Naval del Pacífico', '1794827163001', 'Zona Industrial Los Esteros, Manta - Telf: 052621432'),
                    ('APM Logistic Services', '1398273645001', 'Puerto de Manta, Edificio Central - Telf: 0998877665'),
                    ('Suministros Industriales Manta', '0991234567001', 'Av. 4 de Noviembre, Manta - Telf: 0991122334');");
            }
            
            $checkGcc = $this->pdo->query("SELECT 1 FROM pg_tables WHERE tablename = 'inv_grupo_centros_consumo'");
            if ($checkGcc === false || $checkGcc->fetchColumn() === false) {
                $this->pdo->exec("CREATE TABLE inv_grupo_centros_consumo (
                    id SERIAL PRIMARY KEY,
                    codigo VARCHAR(50) NOT NULL UNIQUE,
                    nombre VARCHAR(255) NOT NULL UNIQUE,
                    representante VARCHAR(255),
                    representante_id INT,
                    FOREIGN KEY (representante_id) REFERENCES inv_talento_personal(id)
                );");
                
                $this->pdo->exec("INSERT INTO inv_grupo_centros_consumo (codigo, nombre, representante) VALUES 
                    ('04', 'CASA MILITAR PRESIDENCIAL', 'JHON C. BARRERA CALDERON.'),
                    ('05', 'DIRECCION ADMINISTRATIVA', 'MARIA EUGENIA FLORES'),
                    ('06', 'DEPARTAMENTO DE OPERACIONES', 'CARLOS HUGO VELEZ');");
            }

            $checkCc = $this->pdo->query("SELECT 1 FROM pg_tables WHERE tablename = 'inv_centros_consumo'");
            if ($checkCc === false || $checkCc->fetchColumn() === false) {
                $this->pdo->exec("CREATE TABLE inv_centros_consumo (
                    id SERIAL PRIMARY KEY,
                    grupo_id INT NOT NULL,
                    codigo VARCHAR(50) NOT NULL UNIQUE,
                    nombre VARCHAR(255) NOT NULL,
                    funcionario VARCHAR(255) NOT NULL,
                    funcionario_id INT,
                    FOREIGN KEY (grupo_id) REFERENCES inv_grupo_centros_consumo(id),
                    FOREIGN KEY (funcionario_id) REFERENCES inv_talento_personal(id)
                );");

                $this->pdo->exec("INSERT INTO inv_centros_consumo (grupo_id, codigo, nombre, funcionario) VALUES 
                    (2, '0002', 'EXPERTO DE SERVICIOS INSTITUCIONALES L.M', 'ING. GINA MENENDEZ LOOR'),
                    (1, '0003', 'SEGURIDAD INMEDIATA PRESIDENCIAL', 'MAYOR FABRICIO CORONEL'),
                    (3, '0004', 'SUPERVISIÓN DE GRÚAS Y PATIOS', 'ING. JORGE RAMIREZ');");
            }

            $checkNotif = $this->pdo->query("SELECT 1 FROM pg_tables WHERE tablename = 'inv_notificaciones'");
            if ($checkNotif === false || $checkNotif->fetchColumn() === false) {
                $this->pdo->exec("CREATE TABLE inv_notificaciones (
                    id SERIAL PRIMARY KEY,
                    tipo VARCHAR(50) NOT NULL,
                    categoria VARCHAR(50) NOT NULL,
                    titulo VARCHAR(255) NOT NULL,
                    mensaje TEXT NOT NULL,
                    secuencial VARCHAR(50),
                    visto INT DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                );");
            }

            // Verificar columnas extras de inv_categorias
            $checkCatCols = $this->pdo->query("SELECT column_name FROM information_schema.columns WHERE table_name = 'inv_categorias'");
            $catCols = $checkCatCols->fetchAll(PDO::FETCH_COLUMN);
            if (!in_array('codigo', $catCols)) {
                $this->pdo->exec("ALTER TABLE inv_categorias ADD COLUMN codigo VARCHAR(50);");
                $this->pdo->exec("UPDATE inv_categorias SET codigo = '1.3.1.01.01.' WHERE id = 1;");
                $this->pdo->exec("UPDATE inv_categorias SET codigo = '1.3.1.01.02.' WHERE id = 2;");
                $this->pdo->exec("UPDATE inv_categorias SET codigo = '1.3.1.01.03.' WHERE id = 3;");
                $this->pdo->exec("UPDATE inv_categorias SET codigo = '1.3.1.01.04.' WHERE id = 4;");
                $this->pdo->exec("UPDATE inv_categorias SET codigo = '1.3.1.01.05.' WHERE id = 5;");
            }

            // Verificar e inicializar la tabla de inv_permisos en migración si no existe
            $checkPermisos = $this->pdo->query("SELECT 1 FROM pg_tables WHERE tablename = 'inv_permisos'");
            if ($checkPermisos === false || $checkPermisos->fetchColumn() === false) {
                $this->pdo->exec("CREATE TABLE inv_permisos (
                    id SERIAL PRIMARY KEY,
                    usuario_id INT NOT NULL,
                    route_key VARCHAR(255) NOT NULL,
                    UNIQUE(usuario_id, route_key),
                    FOREIGN KEY (usuario_id) REFERENCES inv_usuarios(id) ON DELETE CASCADE
                );");
            }

        } catch (Exception $e) {
            // Ignorar o registrar
        }
    }

    /**
     * Inicialización del esquema completo en SQL Server 2014
     */
    private function inicializarBaseDatosSQLServer() {
        $queries = [
            // 1. Tablas Maestras
            "CREATE TABLE inv_categorias (
                id INT IDENTITY(1,1) PRIMARY KEY,
                nombre NVARCHAR(255) NOT NULL UNIQUE,
                codigo NVARCHAR(50),
                extra NVARCHAR(MAX)
            );",
            
            "CREATE TABLE inv_zonas (
                id INT IDENTITY(1,1) PRIMARY KEY,
                nombre NVARCHAR(255) NOT NULL UNIQUE,
                extra NVARCHAR(MAX)
            );",
            
            "CREATE TABLE inv_estados (
                idestado INT PRIMARY KEY,
                descripcion NVARCHAR(255) NOT NULL UNIQUE,
                detalle NVARCHAR(MAX),
                estado INT NOT NULL DEFAULT 1
            );",
            
            "CREATE TABLE inv_marcas (
                id INT IDENTITY(1,1) PRIMARY KEY,
                nombre NVARCHAR(255) NOT NULL UNIQUE,
                extra NVARCHAR(MAX)
            );",
            
            "CREATE TABLE inv_lineas (
                id INT IDENTITY(1,1) PRIMARY KEY,
                nombre NVARCHAR(255) NOT NULL UNIQUE,
                extra NVARCHAR(MAX)
            );",

            // 2. Talento Humano
            "CREATE TABLE inv_talento_areas (
                id INT IDENTITY(1,1) PRIMARY KEY,
                nombre NVARCHAR(255) NOT NULL UNIQUE
            );",

            "CREATE TABLE inv_talento_personal (
                id INT IDENTITY(1,1) PRIMARY KEY,
                nombre NVARCHAR(255) NOT NULL,
                identificacion NVARCHAR(50) NOT NULL UNIQUE,
                cargo NVARCHAR(150),
                area NVARCHAR(150),
                correo NVARCHAR(150),
                estado BIT NOT NULL DEFAULT 1,
                fecha_sincronizacion DATETIME2
            );",

            "CREATE TABLE inv_talento_asignaciones (
                id INT IDENTITY(1,1) PRIMARY KEY,
                personal_id INT NOT NULL,
                area_id INT NOT NULL,
                fecha_inicio DATE NOT NULL,
                fecha_fin DATE,
                FOREIGN KEY (personal_id) REFERENCES inv_talento_personal(id) ON DELETE CASCADE,
                FOREIGN KEY (area_id) REFERENCES inv_talento_areas(id) ON DELETE CASCADE
            );",

            // 3. Períodos e IVA
            "CREATE TABLE inv_periodos (
                id INT IDENTITY(1,1) PRIMARY KEY,
                nombre NVARCHAR(255) NOT NULL UNIQUE,
                fecha_inicio DATE NOT NULL,
                fecha_fin DATE NOT NULL,
                estado NVARCHAR(50) NOT NULL DEFAULT 'activo'
            );",

            "CREATE TABLE inv_valores_iva (
                id INT IDENTITY(1,1) PRIMARY KEY,
                tasa_iva FLOAT NOT NULL,
                periodo_id INT NOT NULL,
                FOREIGN KEY (periodo_id) REFERENCES inv_periodos(id) ON DELETE CASCADE
            );",

            // 4. Catálogo de Unidades y Productos
            "CREATE TABLE inv_unidades (
                id INT IDENTITY(1,1) PRIMARY KEY,
                nombre NVARCHAR(255) NOT NULL UNIQUE,
                extra NVARCHAR(MAX)
            );",

            "CREATE TABLE inv_tipos_iva (
                id INT IDENTITY(1,1) PRIMARY KEY,
                nombre NVARCHAR(255) NOT NULL UNIQUE,
                tasa_iva FLOAT NOT NULL UNIQUE
            );",

            "CREATE TABLE inv_productos (
                id INT IDENTITY(1,1) PRIMARY KEY,
                nombre NVARCHAR(255) NOT NULL UNIQUE,
                grupo_id INT NOT NULL,
                unidad_id INT NOT NULL,
                aplica_iva INT NOT NULL DEFAULT 1,
                codigo NVARCHAR(50) DEFAULT '',
                descripcion NVARCHAR(MAX) DEFAULT '',
                ubicacion NVARCHAR(MAX) DEFAULT '',
                existencia_min FLOAT DEFAULT 0,
                existencia_max FLOAT DEFAULT 0,
                precio_promedio FLOAT DEFAULT 0,
                existencia_actual FLOAT DEFAULT 0,
                FOREIGN KEY (grupo_id) REFERENCES inv_categorias(id),
                FOREIGN KEY (unidad_id) REFERENCES inv_unidades(id)
            );",

            // 5. InvInventario General de Bienes
            "CREATE TABLE inv_inventario (
                id INT IDENTITY(1,1) PRIMARY KEY,
                secuencial NVARCHAR(50) NOT NULL UNIQUE,
                nombre NVARCHAR(255) NOT NULL,
                marca NVARCHAR(255) NOT NULL,
                categoria_id INT NOT NULL,
                zona_id INT NOT NULL,
                estado_id INT NOT NULL,
                responsable_id INT,
                valor FLOAT NOT NULL DEFAULT 0.0,
                fecha_registro DATE NOT NULL,
                observaciones NVARCHAR(MAX),
                activo INT NOT NULL DEFAULT 1,
                cantidad INT NOT NULL DEFAULT 1,
                producto_id INT,
                FOREIGN KEY (categoria_id) REFERENCES inv_categorias(id),
                FOREIGN KEY (zona_id) REFERENCES inv_zonas(id),
                FOREIGN KEY (estado_id) REFERENCES inv_estados(idestado),
                FOREIGN KEY (responsable_id) REFERENCES inv_talento_personal(id),
                FOREIGN KEY (producto_id) REFERENCES inv_productos(id)
            );",

            // 6. Respaldo Histórico
            "CREATE TABLE inv_respaldo_historico (
                id INT IDENTITY(1,1) PRIMARY KEY,
                periodo_id INT NOT NULL,
                item_id INT NOT NULL,
                secuencial NVARCHAR(50) NOT NULL,
                nombre_historico NVARCHAR(255) NOT NULL,
                marca_historica NVARCHAR(255) NOT NULL,
                categoria_historica NVARCHAR(255) NOT NULL,
                zona_historica NVARCHAR(255) NOT NULL,
                estado_historico NVARCHAR(255) NOT NULL,
                responsable_historico NVARCHAR(255),
                area_talento_historica NVARCHAR(255),
                valor_historico FLOAT NOT NULL,
                iva_aplicado FLOAT NOT NULL,
                fecha_registro DATE NOT NULL,
                fecha_corte DATE NOT NULL,
                FOREIGN KEY (periodo_id) REFERENCES inv_periodos(id) ON DELETE CASCADE
            );",

            // 7. Bitácora del Sistema
            "CREATE TABLE inv_bitacora (
                id INT IDENTITY(1,1) PRIMARY KEY,
                secuencial NVARCHAR(50) NOT NULL UNIQUE,
                tipo NVARCHAR(50) NOT NULL,
                modulo NVARCHAR(50) NOT NULL,
                descripcion NVARCHAR(MAX) NOT NULL,
                fecha DATETIME DEFAULT CURRENT_TIMESTAMP
            );",

            // 8. Configuración de Secuenciales
            "CREATE TABLE inv_secuenciales (
                modulo NVARCHAR(50) PRIMARY KEY,
                prefijo NVARCHAR(50) NOT NULL,
                ultimo_numero INT NOT NULL DEFAULT 0
            );",

            // 9. Usuarios
            "CREATE TABLE inv_usuarios (
                id INT IDENTITY(1,1) PRIMARY KEY,
                secuencial NVARCHAR(50) NOT NULL UNIQUE,
                nombre NVARCHAR(255) NOT NULL,
                usuario NVARCHAR(255) NOT NULL UNIQUE,
                contrasena NVARCHAR(MAX) NOT NULL,
                rol NVARCHAR(50) NOT NULL,
                activo INT NOT NULL DEFAULT 1
            );",

            // 10. Bodega - Ingresos
            "CREATE TABLE inv_bod_ingresos (
                id INT IDENTITY(1,1) PRIMARY KEY,
                secuencial NVARCHAR(50) NOT NULL UNIQUE,
                proveedor NVARCHAR(255) NOT NULL,
                fecha DATE NOT NULL,
                observaciones NVARCHAR(MAX),
                responsable_id INT NOT NULL,
                creado_por NVARCHAR(255) NOT NULL DEFAULT 'Admin Terminal',
                FOREIGN KEY (responsable_id) REFERENCES inv_talento_personal(id)
            );",

            // 11. Bodega - Ingresos Detalles
            "CREATE TABLE inv_bod_ingresos_detalles (
                id INT IDENTITY(1,1) PRIMARY KEY,
                ingreso_id INT NOT NULL,
                item_id INT NOT NULL,
                cantidad INT NOT NULL,
                valor_unitario FLOAT NOT NULL,
                FOREIGN KEY (ingreso_id) REFERENCES inv_bod_ingresos(id) ON DELETE CASCADE,
                FOREIGN KEY (item_id) REFERENCES inv_inventario(id)
            );",

            // 12. Bodega - Egresos
            "CREATE TABLE inv_bod_egresos (
                id INT IDENTITY(1,1) PRIMARY KEY,
                secuencial NVARCHAR(50) NOT NULL UNIQUE,
                area_id INT NOT NULL,
                responsable_id INT NOT NULL,
                fecha DATE NOT NULL,
                motivo NVARCHAR(MAX) NOT NULL,
                observaciones NVARCHAR(MAX),
                creado_por NVARCHAR(255) NOT NULL DEFAULT 'Admin Terminal',
                FOREIGN KEY (area_id) REFERENCES inv_talento_areas(id),
                FOREIGN KEY (responsable_id) REFERENCES inv_talento_personal(id)
            );",

            // 13. Bodega - Egresos Detalles
            "CREATE TABLE inv_bod_egresos_detalles (
                id INT IDENTITY(1,1) PRIMARY KEY,
                egreso_id INT NOT NULL,
                item_id INT NOT NULL,
                cantidad INT NOT NULL,
                FOREIGN KEY (egreso_id) REFERENCES inv_bod_egresos(id) ON DELETE CASCADE,
                FOREIGN KEY (item_id) REFERENCES inv_inventario(id)
            );",

            // 14. Parámetros de Configuración General
            "CREATE TABLE inv_parametros (
                clave NVARCHAR(255) PRIMARY KEY,
                valor NVARCHAR(MAX) NOT NULL,
                descripcion NVARCHAR(MAX)
            );",

            // 15. Log de Errores del Sistema
            "CREATE TABLE inv_log_errores (
                id_error        INT IDENTITY(1,1) PRIMARY KEY,
                id_usuario      INT NULL,
                modulo          NVARCHAR(50) NOT NULL DEFAULT 'sys',
                accion          NVARCHAR(255) DEFAULT '',
                tipo_error      NVARCHAR(50) NOT NULL DEFAULT 'Error',
                descripcion     NVARCHAR(MAX) NOT NULL,
                archivo_origen  NVARCHAR(255) DEFAULT '',
                linea_origen    INT DEFAULT 0,
                nivel           NVARCHAR(50) NOT NULL DEFAULT 'ERROR',
                entorno         NVARCHAR(50) NOT NULL DEFAULT 'development',
                ip_cliente      NVARCHAR(50) DEFAULT '',
                fecha_registro  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (id_usuario) REFERENCES inv_usuarios(id) ON DELETE SET NULL
            );",

            // 16. Log de Eventos del Sistema
            "CREATE TABLE inv_log_eventos (
                id_evento       INT IDENTITY(1,1) PRIMARY KEY,
                id_usuario      INT NULL,
                modulo          NVARCHAR(50) NOT NULL DEFAULT 'sys',
                accion          NVARCHAR(255) NOT NULL DEFAULT '',
                descripcion     NVARCHAR(MAX) DEFAULT '',
                resultado       NVARCHAR(50) NOT NULL DEFAULT 'OK',
                ip_cliente      NVARCHAR(50) DEFAULT '',
                fecha_registro  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (id_usuario) REFERENCES inv_usuarios(id) ON DELETE SET NULL
            );",

            // 17. Proveedores
            "CREATE TABLE inv_proveedores (
                id INT IDENTITY(1,1) PRIMARY KEY,
                nombre NVARCHAR(255) NOT NULL UNIQUE,
                ruc NVARCHAR(50) NOT NULL UNIQUE,
                extra NVARCHAR(MAX)
            );",

            // 18. Grupo de Centros de Consumo
            "CREATE TABLE inv_grupo_centros_consumo (
                id INT IDENTITY(1,1) PRIMARY KEY,
                codigo NVARCHAR(50) NOT NULL UNIQUE,
                nombre NVARCHAR(255) NOT NULL UNIQUE,
                representante NVARCHAR(255),
                representante_id INT,
                FOREIGN KEY (representante_id) REFERENCES inv_talento_personal(id)
            );",

            // 19. Centros de Consumo
            "CREATE TABLE inv_centros_consumo (
                id INT IDENTITY(1,1) PRIMARY KEY,
                grupo_id INT NOT NULL,
                codigo NVARCHAR(50) NOT NULL UNIQUE,
                nombre NVARCHAR(255) NOT NULL,
                funcionario NVARCHAR(255) NOT NULL,
                funcionario_id INT,
                FOREIGN KEY (grupo_id) REFERENCES inv_grupo_centros_consumo(id),
                FOREIGN KEY (funcionario_id) REFERENCES inv_talento_personal(id)
            );",

            // 20. Notificaciones
            "CREATE TABLE inv_notificaciones (
                id INT IDENTITY(1,1) PRIMARY KEY,
                tipo NVARCHAR(50) NOT NULL,
                categoria NVARCHAR(50) NOT NULL,
                titulo NVARCHAR(255) NOT NULL,
                mensaje NVARCHAR(MAX) NOT NULL,
                secuencial NVARCHAR(50),
                visto INT DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );",
            // 21. Permisos de Usuarios
            "CREATE TABLE inv_permisos (
                id INT IDENTITY(1,1) PRIMARY KEY,
                usuario_id INT NOT NULL,
                route_key NVARCHAR(255) NOT NULL,
                UNIQUE(usuario_id, route_key),
                FOREIGN KEY (usuario_id) REFERENCES inv_usuarios(id) ON DELETE CASCADE
            );"
        ];

        foreach ($queries as $q) {
            $this->pdo->exec($q);
        }

        $this->cargarDatosSemilla('SQL Server');
    }

    /**
     * Carga de datos semilla e iniciales para pruebas
     */
    private function cargarDatosSemilla($motor = 'SQLite') {
        // 1. Cargar Cabeceras
        $this->pdo->exec("INSERT INTO inv_categorias (nombre, extra, codigo) VALUES 
            ('Maquinaria Pesada', 'Equipos industriales de gran escala', '1.3.1.01.01.'),
            ('Contenedores', 'Depósitos de almacenamiento estándar', '1.3.1.01.02.'),
            ('Equipos de Muelle', 'Accesorios y defensas para muelles', '1.3.1.01.03.'),
            ('Vehículos', 'Camionetas y tractores de terminal', '1.3.1.01.04.'),
            ('Herramientas', 'Equipos de mantenimiento menor', '1.3.1.01.05.');");

        $this->pdo->exec("INSERT INTO inv_zonas (nombre, extra) VALUES 
            ('Terminal Sur', 'Área de atraque y grúas de pórtico'),
            ('Muelle Norte', 'Área de carga general y granel'),
            ('Patio de Contenedores A', 'Patio principal de importación'),
            ('Patio de Contenedores B', 'Patio principal de exportación'),
            ('Bodega Central', 'Almacenamiento de herramientas y repuestos');");

        $this->pdo->exec("INSERT INTO inv_estados (idestado, descripcion, detalle, estado) VALUES 
            (0, 'ANULADO', 'REG. ANULADO', 1),
            (1, 'TODOS', 'REGISTRO ACTIVO', 1),
            (2, 'EN TRAMITE', 'SOLITUD EN TRAMITE', 1),
            (3, 'APROBADO', 'SOLICITUD A SIDO ATENDIDA/APROBADA', 1),
            (4, 'SOLICITADO', 'NO SE HA EMPEZADO A ATENDER POR APM', 1),
            (5, 'AUTORIZADO', 'autorizacion', 1),
            (6, 'VIGENTE', 'desautorizado algo autorizado', 1),
            (7, 'NO AUTORIZADO', 'PERMISO NEGADO', 1),
            (8, 'VERIFICADO', 'VERIFICACIÓN DE DCTOS EN ACCESOS OK', 1),
            (9, 'ATENDIDO', 'CERRADO - YA ATENDIDO', 1),
            (10, 'NO APROBADO', 'TRAMITE NO APROBADO', 1),
            (11, 'NO VIGENTES', 'OPERADORES PERMISO CADUCADOS', 1),
            (12, 'REGISTRADO', 'REGISTRADO', 1),
            (13, 'NO REGISTRADO', 'NO REGISTRADO', 1),
            (14, 'ACEPTADO', 'SE REGISTRA SI EN EL PROCESO DE VALID. PROCEDE', 1),
            (15, 'RECHAZADO', 'POLIZA NO VALIDA', 1),
            (16, 'CORRECTO', 'DATOS VERIFICADOS CON SRI SPTMF CORRECTOS', 1),
            (17, 'INCORRECTO', 'DATOS REVISADOS ESTAN INCORRECTOS', 1),
            (18, 'FAVORABLE', 'INFORME FAVORABLE', 1),
            (19, 'NO FAVORABLE', 'INFORME NO FAVORABLE', 1),
            (20, 'REVISADO', 'DCTO REVISADO', 1),
            (21, 'PENDIENTE', 'DCTO PENDIENTE DE REVISION', 1),
            (22, 'DEVOLVER', 'DEVOLVER POLIZA', 1),
            (111, 'OPERATIVO', 'EQUIPO DISPONIBLE Y FUNCIONANDO', 1),
            (112, 'EN MANTENIMIENTO', 'EQUIPO EN REVISION PERIODICA O REPARACION', 1),
            (113, 'FUERA DE SERVICIO', 'EQUIPO DAÑADO TEMPORALMENTE', 1),
            (114, 'EN TRANSITO', 'BAJO TRASLADO O DESPACHANDOSE', 1),
            (115, 'DESPACHADO', 'EQUIPO ENTREGADO A CLIENTE', 1);");

        $this->pdo->exec("INSERT INTO inv_marcas (nombre, extra) VALUES 
            ('Kalmar', 'Marca líder en grúas y tractores'),
            ('Maersk', 'Línea naviera y contenedores'),
            ('Konecranes', 'Grúas industriales pesadas'),
            ('Terberg', 'Tractores especializados de terminal'),
            ('Hyster', 'Montacargas y manipuladores'),
            ('Fendercare', 'Sistemas de defensas portuarias'),
            ('MSC', 'Mediterranean Shipping Company');");

        $this->pdo->exec("INSERT INTO inv_lineas (nombre, extra) VALUES 
            ('Maersk Line', 'Copenhague, Dinamarca'),
            ('MSC', 'Ginebra, Suiza'),
            ('CMA CGM', 'Marsella, Francia'),
            ('Hapag-Lloyd', 'Hamburgo, Alemania'),
            ('Evergreen', 'Taoyuan, Taiwán');");

        // 2. Talento Humano Áreas y Personal
        $this->pdo->exec("INSERT INTO inv_talento_areas (nombre) VALUES 
            ('Operaciones Portuarias'),
            ('Logística y Terminal'),
            ('Talento Humano'),
            ('Mantenimiento y Equipos');");

        $this->pdo->exec("INSERT INTO inv_talento_personal (nombre, identificacion) VALUES 
            ('Juan Pérez', '0958472819'),
            ('María Rodríguez', '0928374615'),
            ('Pedro Gómez', '0912345678'),
            ('Ana Belén', '0987654321');");

        // Asignaciones Iniciales
        $this->pdo->exec("INSERT INTO inv_talento_asignaciones (personal_id, area_id, fecha_inicio, fecha_fin) VALUES 
            (1, 1, '2020-01-01', NULL),
            (2, 2, '2021-05-15', NULL),
            (3, 3, '2019-10-10', NULL),
            (4, 4, '2022-03-01', NULL);");

        // 3. Período Inicial e IVA por Período
        $this->pdo->exec("INSERT INTO inv_periodos (nombre, fecha_inicio, fecha_fin, estado) VALUES 
            ('Periodo Actual (2026)', '2026-01-01', '2026-12-31', 'activo');");

        // Asignar IVA del 15% al período actual
        $this->pdo->exec("INSERT INTO inv_valores_iva (tasa_iva, periodo_id) VALUES (15.0, 1);");

        // 4. Configuración de Secuenciales
        $this->pdo->exec("INSERT INTO inv_secuenciales (modulo, prefijo, ultimo_numero) VALUES 
            ('inv', 'INV-', 8),
            ('th', 'TH-', 4),
            ('bit', 'BIT-', 1),
            ('acc', 'ACC-', 4),
            ('ing', 'ING-', 0),
            ('egr', 'EGR-', 0),
            ('itm', 'ITM-', 7);");

        // 5. Cargar Unidades
        $this->pdo->exec("INSERT INTO inv_unidades (nombre, extra) VALUES 
            ('Unidades', 'u.'),
            ('Galones', 'gl.'),
            ('Metros', 'm.'),
            ('Kilogramos', 'kg.');");

        // 6. Cargar Tipos de IVA
        $this->pdo->exec("INSERT INTO inv_tipos_iva (nombre, tasa_iva) VALUES 
            ('IVA 15% (General)', 15.0),
            ('IVA 8% (Turismo)', 8.0),
            ('IVA 5% (Emergencia)', 5.0),
            ('IVA 0% (Exento)', 0.0);");

        // 7. Cargar Productos del Catálogo Maestro
        $this->pdo->exec("INSERT INTO inv_productos (nombre, grupo_id, unidad_id, aplica_iva, codigo, descripcion, ubicacion, existencia_min, existencia_max, precio_promedio, existencia_actual) VALUES 
            ('Grúa Pórtico RTG-04', 1, 1, 1, '000001', 'Grúa para manipulación de contenedores', 'Muelle Sur', 1, 5, 120000.0, 1),
            ('Contenedor Reefer 40ft MSKU9012', 2, 1, 1, '000002', 'Contenedor refrigerado', 'Patio B', 1, 10, 8500.0, 1),
            ('Reach Stacker RS-02', 1, 1, 1, '000003', 'Reach stacker Konecranes', 'Patio A', 1, 3, 45000.0, 1),
            ('Tractor de Terminal TT-15', 4, 1, 1, '000004', 'Tractor Terberg', 'Terminal', 1, 5, 28000.0, 1),
            ('Defensa de Goma Cilíndrica', 3, 1, 1, '000005', 'Defensa Fendercare', 'Muelle', 5, 20, 3200.0, 1),
            ('Grúa Pórtico RTG-07', 1, 1, 1, '000006', 'Grúa Liebherr', 'Muelle Norte', 1, 5, 135000.0, 1),
            ('Contenedor Dry 20ft MSCU4521', 2, 1, 1, '000007', 'Contenedor estándar', 'Patio A', 2, 15, 4200.0, 1),
            ('Montacargas H250 HD', 4, 1, 1, '000008', 'Montacargas Hyster', 'Bodega', 1, 4, 18500.0, 1);");

        // 8. Cargar InvInventario Inicial sincronizado
        $this->pdo->exec("INSERT INTO inv_inventario (secuencial, nombre, marca, categoria_id, zona_id, estado_id, responsable_id, valor, fecha_registro, observaciones, activo, cantidad, producto_id) VALUES 
            ('INV-00001', 'Grúa Pórtico RTG-04', 'Kalmar', 1, 1, 111, 1, 120000.0, '2026-05-14', 'Última revisión: Mayo 2026', 1, 1, 1),
            ('INV-00002', 'Contenedor Reefer 40ft MSKU9012', 'Maersk', 2, 4, 115, 2, 8500.0, '2026-05-14', 'Contenedor refrigerado', 1, 1, 2),
            ('INV-00003', 'Reach Stacker RS-02', 'Konecranes', 1, 1, 111, 4, 45000.0, '2026-05-15', '', 1, 1, 3),
            ('INV-00004', 'Tractor de Terminal TT-15', 'Terberg', 4, 2, 111, 1, 28000.0, '2026-05-15', '', 1, 1, 4),
            ('INV-00005', 'Defensa de Goma Cilíndrica', 'Fendercare', 3, 2, 112, 4, 3200.0, '2026-05-15', 'Requiere reemplazo parcial', 1, 1, 5),
            ('INV-00006', 'Grúa Pórtico RTG-07', 'Liebherr', 1, 3, 111, 1, 135000.0, '2026-05-16', '', 1, 1, 6),
            ('INV-00007', 'Contenedor Dry 20ft MSCU4521', 'MSC', 2, 3, 114, 2, 4200.0, '2026-05-16', 'Destino: Guayaquil', 1, 1, 7),
            ('INV-00008', 'Montacargas H250 HD', 'Hyster', 4, 5, 113, 4, 18500.0, '2026-05-17', 'Motor dañado', 1, 1, 8);");

        // 9. Cargar Usuarios
        $usuariosSemilla = [
            ['ACC-0001', 'Admin Terminal', 'admin', 'admin123', 'Administrador'],
            ['ACC-0002', 'Juan Operador', 'juan', 'juan123', 'Operador'],
            ['ACC-0003', 'María Supervisora', 'maria', 'maria123', 'Supervisor'],
            ['ACC-0004', 'Pedro Auditor', 'pedro', 'pedro123', 'Auditor']
        ];
        
        $stmtUsr = $this->pdo->prepare("INSERT INTO inv_usuarios (secuencial, nombre, usuario, contrasena, rol, activo) VALUES (:sec, :nombre, :usuario, :pass, :rol, 1)");
        foreach ($usuariosSemilla as $u) {
            $stmtUsr->execute([
                ':sec' => $u[0],
                ':nombre' => $u[1],
                ':usuario' => $u[2],
                // hash('sha256', ...) primero: simula el hash que
                // js/password-hash.js va a calcular en el navegador cuando
                // alguien escriba esta clave demo en el login real.
                ':pass' => hash_password_secure(hash('sha256', $u[3])),
                ':rol' => $u[4]
            ]);
        }

        // 10. Cargar Bitácora Inicial
        $descBitacora = "Inicialización completa del sistema en base de datos " . $motor . ".";
        $stmtBit = $this->pdo->prepare("INSERT INTO inv_bitacora (secuencial, tipo, modulo, descripcion) VALUES ('BIT-000001', 'SISTEMA', 'sys', :desc)");
        $stmtBit->execute([':desc' => $descBitacora]);

        // 11. Cargar Parámetros Iniciales
        $this->pdo->exec("INSERT INTO inv_parametros (clave, valor, descripcion) VALUES 
            ('tiempo_inactividad', '600', 'Tiempo de inactividad permitido antes de relogearse (en segundos)');");

        // 12. Cargar Proveedores Semilla
        $this->pdo->exec("INSERT INTO inv_proveedores (nombre, ruc, extra) VALUES 
            ('Importadora Portuaria S.A.', '0992384712001', 'Av. Malecón y Calle 15, Manta - Telf: 0987654321'),
            ('Consorcio Naval del Pacífico', '1794827163001', 'Zona Industrial Los Esteros, Manta - Telf: 052621432'),
            ('APM Logistic Services', '1398273645001', 'Puerto de Manta, Edificio Central - Telf: 0998877665'),
            ('Suministros Industriales Manta', '0991234567001', 'Av. 4 de Noviembre, Manta - Telf: 0991122334');");

        // 13. Cargar Centros de Consumo Semilla
        $this->pdo->exec("INSERT INTO inv_grupo_centros_consumo (codigo, nombre, representante) VALUES 
            ('04', 'CASA MILITAR PRESIDENCIAL', 'JHON C. BARRERA CALDERON.'),
            ('05', 'DIRECCION ADMINISTRATIVA', 'MARIA EUGENIA FLORES'),
            ('06', 'DEPARTAMENTO DE OPERACIONES', 'CARLOS HUGO VELEZ');");

        $this->pdo->exec("INSERT INTO inv_centros_consumo (grupo_id, codigo, nombre, funcionario) VALUES 
            (2, '0002', 'EXPERTO DE SERVICIOS INSTITUCIONALES L.M', 'ING. GINA MENENDEZ LOOR'),
            (1, '0003', 'SEGURIDAD INMEDIATA PRESIDENCIAL', 'MAYOR FABRICIO CORONEL'),
            (3, '0004', 'SUPERVISIÓN DE GRÚAS Y PATIOS', 'ING. JORGE RAMIREZ');");
    }

    private function migrarBaseDatosExistenteSQLServer() {
        // En SQL Server, verificamos la existencia de las nuevas tablas maestras añadidas
        try {
            $checkProv = $this->pdo->query("SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = 'inv_proveedores'");
            if ($checkProv === false || $checkProv->fetchColumn() === false) {
                // Crear tablas de la v3.5 si no existen en SQL Server
                $this->pdo->exec("CREATE TABLE inv_proveedores (
                    id INT IDENTITY(1,1) PRIMARY KEY,
                    nombre NVARCHAR(255) NOT NULL UNIQUE,
                    ruc NVARCHAR(50) NOT NULL UNIQUE,
                    extra NVARCHAR(MAX)
                );");
                
                $this->pdo->exec("INSERT INTO inv_proveedores (nombre, ruc, extra) VALUES 
                    ('Importadora Portuaria S.A.', '0992384712001', 'Av. Malecón y Calle 15, Manta - Telf: 0987654321'),
                    ('Consorcio Naval del Pacífico', '1794827163001', 'Zona Industrial Los Esteros, Manta - Telf: 052621432'),
                    ('APM Logistic Services', '1398273645001', 'Puerto de Manta, Edificio Central - Telf: 0998877665'),
                    ('Suministros Industriales Manta', '0991234567001', 'Av. 4 de Noviembre, Manta - Telf: 0991122334');");
            }
            
            $checkGcc = $this->pdo->query("SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = 'inv_grupo_centros_consumo'");
            if ($checkGcc === false || $checkGcc->fetchColumn() === false) {
                $this->pdo->exec("CREATE TABLE inv_grupo_centros_consumo (
                    id INT IDENTITY(1,1) PRIMARY KEY,
                    codigo NVARCHAR(50) NOT NULL UNIQUE,
                    nombre NVARCHAR(255) NOT NULL UNIQUE,
                    representante NVARCHAR(255),
                    representante_id INT,
                    FOREIGN KEY (representante_id) REFERENCES inv_talento_personal(id)
                );");
                
                $this->pdo->exec("INSERT INTO inv_grupo_centros_consumo (codigo, nombre, representante) VALUES 
                    ('04', 'CASA MILITAR PRESIDENCIAL', 'JHON C. BARRERA CALDERON.'),
                    ('05', 'DIRECCION ADMINISTRATIVA', 'MARIA EUGENIA FLORES'),
                    ('06', 'DEPARTAMENTO DE OPERACIONES', 'CARLOS HUGO VELEZ');");
            }

            $checkCc = $this->pdo->query("SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = 'inv_centros_consumo'");
            if ($checkCc === false || $checkCc->fetchColumn() === false) {
                $this->pdo->exec("CREATE TABLE inv_centros_consumo (
                    id INT IDENTITY(1,1) PRIMARY KEY,
                    grupo_id INT NOT NULL,
                    codigo NVARCHAR(50) NOT NULL UNIQUE,
                    nombre NVARCHAR(255) NOT NULL,
                    funcionario NVARCHAR(255) NOT NULL,
                    funcionario_id INT,
                    FOREIGN KEY (grupo_id) REFERENCES inv_grupo_centros_consumo(id),
                    FOREIGN KEY (funcionario_id) REFERENCES inv_talento_personal(id)
                );");

                $this->pdo->exec("INSERT INTO inv_centros_consumo (grupo_id, codigo, nombre, funcionario) VALUES 
                    (2, '0002', 'EXPERTO DE SERVICIOS INSTITUCIONALES L.M', 'ING. GINA MENENDEZ LOOR'),
                    (1, '0003', 'SEGURIDAD INMEDIATA PRESIDENCIAL', 'MAYOR FABRICIO CORONEL'),
                    (3, '0004', 'SUPERVISIÓN DE GRÚAS Y PATIOS', 'ING. JORGE RAMIREZ');");
            }

            $checkNotif = $this->pdo->query("SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = 'inv_notificaciones'");
            if ($checkNotif === false || $checkNotif->fetchColumn() === false) {
                $this->pdo->exec("CREATE TABLE inv_notificaciones (
                    id INT IDENTITY(1,1) PRIMARY KEY,
                    tipo NVARCHAR(50) NOT NULL,
                    categoria NVARCHAR(50) NOT NULL,
                    titulo NVARCHAR(255) NOT NULL,
                    mensaje NVARCHAR(MAX) NOT NULL,
                    secuencial NVARCHAR(50),
                    visto INT DEFAULT 0,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                );");
            }

            // Verificar columnas extras de inv_categorias
            $checkCatCols = $this->pdo->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'inv_categorias'");
            $catCols = $checkCatCols->fetchAll(PDO::FETCH_COLUMN);
            if (!in_array('codigo', $catCols)) {
                $this->pdo->exec("ALTER TABLE inv_categorias ADD codigo NVARCHAR(50);");
                $this->pdo->exec("UPDATE inv_categorias SET codigo = '1.3.1.01.01.' WHERE id = 1;");
                $this->pdo->exec("UPDATE inv_categorias SET codigo = '1.3.1.01.02.' WHERE id = 2;");
                $this->pdo->exec("UPDATE inv_categorias SET codigo = '1.3.1.01.03.' WHERE id = 3;");
                $this->pdo->exec("UPDATE inv_categorias SET codigo = '1.3.1.01.04.' WHERE id = 4;");
                $this->pdo->exec("UPDATE inv_categorias SET codigo = '1.3.1.01.05.' WHERE id = 5;");
            }

            // Las copias anteriores al 2026-08-05 no incluyen el tiempo de
            // inactividad individual. Mantener esta migración aquí permite
            // que una restauración antigua se actualice al abrir el sistema.
            $checkUserInactivity = $this->pdo->query(
                "SELECT COL_LENGTH('dbo.inv_usuarios', 'tiempo_inactividad')"
            );
            if ($checkUserInactivity === false || $checkUserInactivity->fetchColumn() === null) {
                $this->pdo->exec("ALTER TABLE dbo.inv_usuarios ADD tiempo_inactividad INT NULL;");
            }

            $this->pdo->exec("IF NOT EXISTS (
                    SELECT 1 FROM sys.check_constraints
                    WHERE name = 'CK_inv_usuarios_tiempo_inactividad'
                )
                ALTER TABLE dbo.inv_usuarios ADD CONSTRAINT CK_inv_usuarios_tiempo_inactividad
                    CHECK (tiempo_inactividad IS NULL OR tiempo_inactividad BETWEEN 60 AND 14400);");

        } catch (Exception $e) {
            // Ignorar o registrar
        }
    }

    /**
     * SQLite Fallbacks (Código original conservado intacto para integridad)
     */
    private function inicializarBaseDatosSQLite() {
        $queries = [
            "CREATE TABLE IF NOT EXISTS inv_categorias (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nombre TEXT NOT NULL UNIQUE,
                codigo TEXT,
                extra TEXT
            );",
            "CREATE TABLE IF NOT EXISTS inv_zonas (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nombre TEXT NOT NULL UNIQUE,
                extra TEXT
            );",
            "CREATE TABLE IF NOT EXISTS inv_estados (
                idestado INTEGER PRIMARY KEY,
                descripcion TEXT NOT NULL UNIQUE,
                detalle TEXT,
                estado INTEGER NOT NULL DEFAULT 1
            );",
            "CREATE TABLE IF NOT EXISTS inv_marcas (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nombre TEXT NOT NULL UNIQUE,
                extra TEXT
            );",
            "CREATE TABLE IF NOT EXISTS inv_lineas (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nombre TEXT NOT NULL UNIQUE,
                extra TEXT
            );",
            "CREATE TABLE IF NOT EXISTS inv_talento_areas (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nombre TEXT NOT NULL UNIQUE
            );",
            "CREATE TABLE IF NOT EXISTS inv_talento_personal (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nombre TEXT NOT NULL,
                identificacion TEXT NOT NULL UNIQUE,
                cargo TEXT,
                area TEXT,
                correo TEXT,
                estado INTEGER NOT NULL DEFAULT 1,
                fecha_sincronizacion DATETIME
            );",
            "CREATE TABLE IF NOT EXISTS inv_talento_asignaciones (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                personal_id INTEGER NOT NULL,
                area_id INTEGER NOT NULL,
                fecha_inicio DATE NOT NULL,
                fecha_fin DATE,
                FOREIGN KEY (personal_id) REFERENCES inv_talento_personal(id) ON DELETE CASCADE,
                FOREIGN KEY (area_id) REFERENCES inv_talento_areas(id) ON DELETE CASCADE
            );",
            "CREATE TABLE IF NOT EXISTS inv_periodos (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nombre TEXT NOT NULL UNIQUE,
                fecha_inicio DATE NOT NULL,
                fecha_fin DATE NOT NULL,
                estado TEXT NOT NULL DEFAULT 'activo'
            );",
            "CREATE TABLE IF NOT EXISTS inv_valores_iva (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                tasa_iva REAL NOT NULL,
                periodo_id INTEGER NOT NULL,
                FOREIGN KEY (periodo_id) REFERENCES inv_periodos(id) ON DELETE CASCADE
            );",
            "CREATE TABLE IF NOT EXISTS inv_inventario (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                secuencial TEXT NOT NULL UNIQUE,
                nombre TEXT NOT NULL,
                marca TEXT NOT NULL,
                categoria_id INTEGER NOT NULL,
                zona_id INTEGER NOT NULL,
                estado_id INTEGER NOT NULL,
                responsable_id INTEGER,
                valor REAL NOT NULL DEFAULT 0.0,
                fecha_registro DATE NOT NULL,
                observaciones TEXT,
                activo INTEGER NOT NULL DEFAULT 1,
                cantidad INTEGER NOT NULL DEFAULT 1,
                FOREIGN KEY (categoria_id) REFERENCES inv_categorias(id),
                FOREIGN KEY (zona_id) REFERENCES inv_zonas(id),
                FOREIGN KEY (estado_id) REFERENCES inv_estados(idestado),
                FOREIGN KEY (responsable_id) REFERENCES inv_talento_personal(id)
            );",
            "CREATE TABLE IF NOT EXISTS inv_respaldo_historico (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                periodo_id INTEGER NOT NULL,
                item_id INTEGER NOT NULL,
                secuencial TEXT NOT NULL,
                nombre_historico TEXT NOT NULL,
                marca_historica TEXT NOT NULL,
                categoria_historica TEXT NOT NULL,
                zona_historica TEXT NOT NULL,
                estado_historico TEXT NOT NULL,
                responsable_historico TEXT,
                area_talento_historica TEXT,
                valor_historico REAL NOT NULL,
                iva_aplicado REAL NOT NULL,
                fecha_registro DATE NOT NULL,
                fecha_corte DATE NOT NULL,
                FOREIGN KEY (periodo_id) REFERENCES inv_periodos(id) ON DELETE CASCADE
            );",
            "CREATE TABLE IF NOT EXISTS inv_bitacora (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                secuencial TEXT NOT NULL UNIQUE,
                tipo TEXT NOT NULL,
                modulo TEXT NOT NULL,
                descripcion TEXT NOT NULL,
                fecha DATETIME DEFAULT CURRENT_TIMESTAMP
            );",
            "CREATE TABLE IF NOT EXISTS inv_secuenciales (
                modulo TEXT PRIMARY KEY,
                prefijo TEXT NOT NULL,
                ultimo_numero INTEGER NOT NULL DEFAULT 0
            );",
            "CREATE TABLE IF NOT EXISTS inv_usuarios (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                secuencial TEXT NOT NULL UNIQUE,
                nombre TEXT NOT NULL,
                usuario TEXT NOT NULL UNIQUE,
                contrasena TEXT NOT NULL,
                rol TEXT NOT NULL,
                activo INTEGER NOT NULL DEFAULT 1
            );",
            "CREATE TABLE IF NOT EXISTS inv_bod_ingresos (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                secuencial TEXT NOT NULL UNIQUE,
                proveedor TEXT NOT NULL,
                fecha DATE NOT NULL,
                observaciones TEXT,
                responsable_id INTEGER NOT NULL,
                creado_por TEXT NOT NULL DEFAULT 'Admin Terminal',
                FOREIGN KEY (responsable_id) REFERENCES inv_talento_personal(id)
            );",
            "CREATE TABLE IF NOT EXISTS inv_bod_ingresos_detalles (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                ingreso_id INTEGER NOT NULL,
                item_id INTEGER NOT NULL,
                cantidad INTEGER NOT NULL,
                valor_unitario REAL NOT NULL,
                FOREIGN KEY (ingreso_id) REFERENCES inv_bod_ingresos(id) ON DELETE CASCADE,
                FOREIGN KEY (item_id) REFERENCES inv_inventario(id)
            );",
            "CREATE TABLE IF NOT EXISTS inv_bod_egresos (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                secuencial TEXT NOT NULL UNIQUE,
                area_id INTEGER NOT NULL,
                responsable_id INTEGER NOT NULL,
                fecha DATE NOT NULL,
                motivo TEXT NOT NULL,
                observaciones TEXT,
                creado_por TEXT NOT NULL DEFAULT 'Admin Terminal',
                FOREIGN KEY (area_id) REFERENCES inv_talento_areas(id),
                FOREIGN KEY (responsable_id) REFERENCES inv_talento_personal(id)
            );",
            "CREATE TABLE IF NOT EXISTS inv_bod_egresos_detalles (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                egreso_id INTEGER NOT NULL,
                item_id INTEGER NOT NULL,
                cantidad INTEGER NOT NULL,
                FOREIGN KEY (egreso_id) REFERENCES inv_bod_egresos(id) ON DELETE CASCADE,
                FOREIGN KEY (item_id) REFERENCES inv_inventario(id)
            );",
            "CREATE TABLE IF NOT EXISTS inv_parametros (
                clave TEXT PRIMARY KEY,
                valor TEXT NOT NULL,
                descripcion TEXT
            );",
            "CREATE TABLE IF NOT EXISTS inv_log_errores (
                id_error        INTEGER PRIMARY KEY AUTOINCREMENT,
                id_usuario      INTEGER NULL,
                modulo          TEXT    NOT NULL DEFAULT 'sys',
                accion          TEXT    DEFAULT '',
                tipo_error      TEXT    NOT NULL DEFAULT 'Error',
                descripcion     TEXT    NOT NULL,
                archivo_origen  TEXT    DEFAULT '',
                linea_origen    INTEGER DEFAULT 0,
                nivel           TEXT    NOT NULL DEFAULT 'ERROR',
                entorno         TEXT    NOT NULL DEFAULT 'development',
                ip_cliente      TEXT    DEFAULT '',
                fecha_registro  TEXT    NOT NULL DEFAULT (datetime('now', 'localtime')),
                FOREIGN KEY (id_usuario) REFERENCES inv_usuarios(id) ON DELETE SET NULL
            );",
            "CREATE TABLE IF NOT EXISTS inv_log_eventos (
                id_evento       INTEGER PRIMARY KEY AUTOINCREMENT,
                id_usuario      INTEGER NULL,
                modulo          TEXT    NOT NULL DEFAULT 'sys',
                accion          TEXT    NOT NULL DEFAULT '',
                descripcion     TEXT    DEFAULT '',
                resultado       TEXT    NOT NULL DEFAULT 'OK',
                ip_cliente      TEXT    DEFAULT '',
                fecha_registro  TEXT    NOT NULL DEFAULT (datetime('now', 'localtime')),
                FOREIGN KEY (id_usuario) REFERENCES inv_usuarios(id) ON DELETE SET NULL
            );",
            "CREATE TABLE IF NOT EXISTS inv_permisos (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                usuario_id INTEGER NOT NULL,
                route_key TEXT NOT NULL,
                UNIQUE(usuario_id, route_key),
                FOREIGN KEY (usuario_id) REFERENCES inv_usuarios(id) ON DELETE CASCADE
            );"
        ];

        foreach ($queries as $q) {
            $this->pdo->exec($q);
        }

        $this->cargarDatosSemilla('SQLite');
    }

    private function migrarBaseDatosExistenteSQLite() {
        try {
            $check = $this->pdo->query("PRAGMA table_info(inv_inventario)");
            $columns = $check->fetchAll(PDO::FETCH_COLUMN, 1);
            if (!in_array('cantidad', $columns)) {
                $this->pdo->exec("ALTER TABLE inv_inventario ADD COLUMN cantidad INTEGER DEFAULT 1;");
            }

            $this->pdo->exec("CREATE TABLE IF NOT EXISTS inv_bod_ingresos (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                secuencial TEXT NOT NULL UNIQUE,
                proveedor TEXT NOT NULL,
                fecha DATE NOT NULL,
                observaciones TEXT,
                responsable_id INTEGER NOT NULL,
                creado_por TEXT NOT NULL DEFAULT 'Admin Terminal',
                FOREIGN KEY (responsable_id) REFERENCES inv_talento_personal(id)
            );");

            $this->pdo->exec("CREATE TABLE IF NOT EXISTS inv_bod_ingresos_detalles (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                ingreso_id INTEGER NOT NULL,
                item_id INTEGER NOT NULL,
                cantidad INTEGER NOT NULL,
                valor_unitario REAL NOT NULL,
                FOREIGN KEY (ingreso_id) REFERENCES inv_bod_ingresos(id) ON DELETE CASCADE,
                FOREIGN KEY (item_id) REFERENCES inv_inventario(id)
            );");

            $this->pdo->exec("CREATE TABLE IF NOT EXISTS inv_bod_egresos (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                secuencial TEXT NOT NULL UNIQUE,
                area_id INTEGER NOT NULL,
                responsable_id INTEGER NOT NULL,
                fecha DATE NOT NULL,
                motivo TEXT NOT NULL,
                observaciones TEXT,
                creado_por TEXT NOT NULL DEFAULT 'Admin Terminal',
                FOREIGN KEY (area_id) REFERENCES inv_talento_areas(id),
                FOREIGN KEY (responsable_id) REFERENCES inv_talento_personal(id)
            );");

            $this->pdo->exec("CREATE TABLE IF NOT EXISTS inv_bod_egresos_detalles (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                egreso_id INTEGER NOT NULL,
                item_id INTEGER NOT NULL,
                cantidad INTEGER NOT NULL,
                FOREIGN KEY (egreso_id) REFERENCES inv_bod_egresos(id) ON DELETE CASCADE,
                FOREIGN KEY (item_id) REFERENCES inv_inventario(id)
            );");

            $this->pdo->exec("INSERT OR IGNORE INTO inv_secuenciales (modulo, prefijo, ultimo_numero) VALUES 
                ('ing', 'ING-', 0),
                ('egr', 'EGR-', 0);");

            $this->pdo->exec("CREATE TABLE IF NOT EXISTS inv_parametros (
                clave TEXT PRIMARY KEY,
                valor TEXT NOT NULL,
                descripcion TEXT
            );");

            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM inv_parametros WHERE clave = 'tiempo_inactividad'");
            $stmt->execute();
            if ($stmt->fetchColumn() == 0) {
                $this->pdo->exec("INSERT INTO inv_parametros (clave, valor, descripcion) VALUES 
                    ('tiempo_inactividad', '600', 'Tiempo de inactividad permitido antes de relogearse (en segundos)');");
            }

            $checkUsr = $this->pdo->query("PRAGMA table_info(inv_usuarios)");
            $columnsUsr = $checkUsr->fetchAll(PDO::FETCH_COLUMN, 1);
            if (!in_array('usuario', $columnsUsr)) {
                $this->pdo->exec("ALTER TABLE inv_usuarios ADD COLUMN usuario TEXT;");
            }
            if (!in_array('contrasena', $columnsUsr)) {
                $this->pdo->exec("ALTER TABLE inv_usuarios ADD COLUMN contrasena TEXT;");
            }

            $this->pdo->exec("CREATE TABLE IF NOT EXISTS inv_log_errores (
                id_error        INTEGER PRIMARY KEY AUTOINCREMENT,
                id_usuario      INTEGER NULL,
                modulo          TEXT    NOT NULL DEFAULT 'sys',
                accion          TEXT    DEFAULT '',
                tipo_error      TEXT    NOT NULL DEFAULT 'Error',
                descripcion     TEXT    NOT NULL,
                archivo_origen  TEXT    DEFAULT '',
                linea_origen    INTEGER DEFAULT 0,
                nivel           TEXT    NOT NULL DEFAULT 'ERROR',
                entorno         TEXT    NOT NULL DEFAULT 'development',
                ip_cliente      TEXT    DEFAULT '',
                fecha_registro  TEXT    NOT NULL DEFAULT (datetime('now', 'localtime')),
                FOREIGN KEY (id_usuario) REFERENCES inv_usuarios(id) ON DELETE SET NULL
            );");

            $this->pdo->exec("CREATE TABLE IF NOT EXISTS inv_log_eventos (
                id_evento       INTEGER PRIMARY KEY AUTOINCREMENT,
                id_usuario      INTEGER NULL,
                modulo          TEXT    NOT NULL DEFAULT 'sys',
                accion          TEXT    NOT NULL DEFAULT '',
                descripcion     TEXT    DEFAULT '',
                resultado       TEXT    NOT NULL DEFAULT 'OK',
                ip_cliente      TEXT    DEFAULT '',
                fecha_registro  TEXT    NOT NULL DEFAULT (datetime('now', 'localtime')),
                FOREIGN KEY (id_usuario) REFERENCES inv_usuarios(id) ON DELETE SET NULL
            );");

            $this->pdo->exec("CREATE TABLE IF NOT EXISTS inv_unidades (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nombre TEXT NOT NULL UNIQUE,
                extra TEXT
            );");

            $this->pdo->exec("CREATE TABLE IF NOT EXISTS inv_tipos_iva (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nombre TEXT NOT NULL UNIQUE,
                tasa_iva REAL NOT NULL UNIQUE
            );");

            $this->pdo->exec("CREATE TABLE IF NOT EXISTS inv_productos (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nombre TEXT NOT NULL UNIQUE,
                grupo_id INTEGER NOT NULL,
                unidad_id INTEGER NOT NULL,
                aplica_iva INTEGER NOT NULL DEFAULT 1,
                FOREIGN KEY (grupo_id) REFERENCES inv_categorias(id),
                FOREIGN KEY (unidad_id) REFERENCES inv_unidades(id)
            );");

            $checkInv = $this->pdo->query("PRAGMA table_info(inv_inventario)");
            $columnsInv = $checkInv->fetchAll(PDO::FETCH_COLUMN, 1);
            if (!in_array('producto_id', $columnsInv)) {
                $this->pdo->exec("ALTER TABLE inv_inventario ADD COLUMN producto_id INTEGER REFERENCES inv_productos(id);");
            }

            $countUnidades = $this->pdo->query("SELECT COUNT(*) FROM inv_unidades")->fetchColumn();
            if ($countUnidades == 0) {
                $this->pdo->exec("INSERT INTO inv_unidades (nombre, extra) VALUES 
                    ('Unidades', 'u.'),
                    ('Galones', 'gl.'),
                    ('Metros', 'm.'),
                    ('Kilogramos', 'kg.');");
            }

            $countIvas = $this->pdo->query("SELECT COUNT(*) FROM inv_tipos_iva")->fetchColumn();
            if ($countIvas == 0) {
                $this->pdo->exec("INSERT INTO inv_tipos_iva (nombre, tasa_iva) VALUES 
                    ('IVA 15% (General)', 15.0),
                    ('IVA 8% (Turismo)', 8.0),
                    ('IVA 5% (Emergencia)', 5.0),
                    ('IVA 0% (Exento)', 0.0);");
            }

            $countProductos = $this->pdo->query("SELECT COUNT(*) FROM inv_productos")->fetchColumn();
            if ($countProductos == 0) {
                $this->pdo->exec("INSERT INTO inv_productos (nombre, grupo_id, unidad_id, aplica_iva) VALUES 
                    ('Grúa Pórtico RTG', 1, 1, 1),
                    ('Contenedor Reefer 40ft', 2, 1, 1),
                    ('Reach Stacker RS', 1, 1, 1),
                    ('Tractor de Terminal', 4, 1, 1),
                    ('Defensa de Goma Cilíndrica', 3, 1, 1),
                    ('Contenedor Dry 20ft', 2, 1, 1),
                    ('Montacargas H250 HD', 4, 1, 1);");

                $this->pdo->exec("UPDATE inv_inventario SET producto_id = 1 WHERE nombre LIKE '%Grúa Pórtico RTG-04%';");
                $this->pdo->exec("UPDATE inv_inventario SET producto_id = 2 WHERE nombre LIKE '%Contenedor Reefer%';");
                $this->pdo->exec("UPDATE inv_inventario SET producto_id = 3 WHERE nombre LIKE '%Reach Stacker%';");
                $this->pdo->exec("UPDATE inv_inventario SET producto_id = 4 WHERE nombre LIKE '%Tractor de Terminal%';");
                $this->pdo->exec("UPDATE inv_inventario SET producto_id = 5 WHERE nombre LIKE '%Defensa de Goma%';");
                $this->pdo->exec("UPDATE inv_inventario SET producto_id = 1 WHERE nombre LIKE '%Grúa Pórtico RTG-07%';");
                $this->pdo->exec("UPDATE inv_inventario SET producto_id = 6 WHERE nombre LIKE '%Contenedor Dry%';");
                $this->pdo->exec("UPDATE inv_inventario SET producto_id = 7 WHERE nombre LIKE '%Montacargas%';");
                $this->pdo->exec("UPDATE inv_inventario SET producto_id = 1 WHERE producto_id IS NULL;");
            }

            $checkCat = $this->pdo->query("PRAGMA table_info(inv_categorias)");
            $columnsCat = $checkCat->fetchAll(PDO::FETCH_COLUMN, 1);
            if (!in_array('codigo', $columnsCat)) {
                $this->pdo->exec("ALTER TABLE inv_categorias ADD COLUMN codigo TEXT;");
                $this->pdo->exec("UPDATE inv_categorias SET codigo = '1.3.1.01.01.' WHERE id = 1;");
                $this->pdo->exec("UPDATE inv_categorias SET codigo = '1.3.1.01.02.' WHERE id = 2;");
                $this->pdo->exec("UPDATE inv_categorias SET codigo = '1.3.1.01.03.' WHERE id = 3;");
                $this->pdo->exec("UPDATE inv_categorias SET codigo = '1.3.1.01.04.' WHERE id = 4;");
                $this->pdo->exec("UPDATE inv_categorias SET codigo = '1.3.1.01.05.' WHERE id = 5;");
            }

            $this->pdo->exec("CREATE TABLE IF NOT EXISTS inv_proveedores (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nombre TEXT NOT NULL UNIQUE,
                ruc TEXT NOT NULL UNIQUE,
                extra TEXT
            );");

            $countProveedores = $this->pdo->query("SELECT COUNT(*) FROM inv_proveedores")->fetchColumn();
            if ($countProveedores == 0) {
                $this->pdo->exec("INSERT INTO inv_proveedores (nombre, ruc, extra) VALUES 
                    ('Importadora Portuaria S.A.', '0992384712001', 'Av. Malecón y Calle 15, Manta - Telf: 0987654321'),
                    ('Consorcio Naval del Pacífico', '1794827163001', 'Zona Industrial Los Esteros, Manta - Telf: 052621432'),
                    ('APM Logistic Services', '1398273645001', 'Puerto de Manta, Edificio Central - Telf: 0998877665'),
                    ('Suministros Industriales Manta', '0991234567001', 'Av. 4 de Noviembre, Manta - Telf: 0991122334');");
            }

            $this->pdo->exec("CREATE TABLE IF NOT EXISTS inv_grupo_centros_consumo (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                codigo TEXT NOT NULL UNIQUE,
                nombre TEXT NOT NULL UNIQUE,
                representante TEXT,
                representante_id INTEGER,
                FOREIGN KEY (representante_id) REFERENCES inv_talento_personal(id)
            );");

            $countGcc = $this->pdo->query("SELECT COUNT(*) FROM inv_grupo_centros_consumo")->fetchColumn();
            if ($countGcc == 0) {
                $this->pdo->exec("INSERT INTO inv_grupo_centros_consumo (codigo, nombre, representante) VALUES 
                    ('04', 'CASA MILITAR PRESIDENCIAL', 'JHON C. BARRERA CALDERON.'),
                    ('05', 'DIRECCION ADMINISTRATIVA', 'MARIA EUGENIA FLORES'),
                    ('06', 'DEPARTAMENTO DE OPERACIONES', 'CARLOS HUGO VELEZ');");
            }

            $this->pdo->exec("CREATE TABLE IF NOT EXISTS inv_centros_consumo (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                grupo_id INTEGER NOT NULL,
                codigo TEXT NOT NULL UNIQUE,
                nombre TEXT NOT NULL,
                funcionario TEXT NOT NULL,
                funcionario_id INTEGER,
                FOREIGN KEY (grupo_id) REFERENCES inv_grupo_centros_consumo(id),
                FOREIGN KEY (funcionario_id) REFERENCES inv_talento_personal(id)
            );");

            $countCc = $this->pdo->query("SELECT COUNT(*) FROM inv_centros_consumo")->fetchColumn();
            if ($countCc == 0) {
                $this->pdo->exec("INSERT INTO inv_centros_consumo (grupo_id, codigo, nombre, funcionario) VALUES 
                    (2, '0002', 'EXPERTO DE SERVICIOS INSTITUCIONALES L.M', 'ING. GINA MENENDEZ LOOR'),
                    (1, '0003', 'SEGURIDAD INMEDIATA PRESIDENCIAL', 'MAYOR FABRICIO CORONEL'),
                    (3, '0004', 'SUPERVISIÓN DE GRÚAS Y PATIOS', 'ING. JORGE RAMIREZ');");
            }

            $this->pdo->exec("CREATE TABLE IF NOT EXISTS inv_notificaciones (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                tipo TEXT NOT NULL,
                categoria TEXT NOT NULL,
                titulo TEXT NOT NULL,
                mensaje TEXT NOT NULL,
                secuencial TEXT,
                visto INTEGER DEFAULT 0,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP
            );");

            $this->pdo->exec("CREATE TABLE IF NOT EXISTS inv_permisos (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                usuario_id INTEGER NOT NULL,
                route_key TEXT NOT NULL,
                UNIQUE(usuario_id, route_key),
                FOREIGN KEY (usuario_id) REFERENCES inv_usuarios(id) ON DELETE CASCADE
            );");

        } catch (PDOException $e) {
            // Ignorar o registrar
        }
    }
}

// Clase de compatibilidad hacia atrás para modelos legados
class InvDatabase extends Database {}

