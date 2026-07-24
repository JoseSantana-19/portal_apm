# Resumen de la Arquitectura del Proyecto — MVC Modular v4.0.0

Este documento proporciona una visión general y técnica de la arquitectura actual del sistema **Control_bines**, el cual ha sido estructurado bajo un patrón **MVC Modular en Puro PHP** (sin frameworks de terceros), diseñado para permitir integraciones sencillas con otros proyectos en un único entorno unificado.

---

## 🏗️ Estructura Completa del Proyecto

El proyecto está organizado de la siguiente manera:

```
Control_Bines/
├── index.php                             ← Front Controller / Router Principal
├── .env                                  ← Configuración centralizada de base de datos y entorno
├── diccionario_datos.md                  ← Diccionario de datos del sistema (BBDD inventario y Talento_Humano)
├── logoapm.png                           ← Logo institucional de la Autoridad Portuaria
├── modificaciones_ui.md                  ← Bitácora de cambios y guía operativa
├── resumen_arquitectura.md               ← [Este archivo] Arquitectura y diseño técnico
├── setup_database.php                    ← Script de inicialización y restauración de BD
├── test_conexion.php                     ← Herramienta de verificación de conexión a BD
│
├── config/                               ← CONFIGURACIONES GLOBALES
│   ├── database.php                      ← Configuración del adaptador de datos
│   ├── globals.php                       ← Carga del .env y funciones helper de rutas y URLs
│   └── routes.php                        ← Mapa centralizado de rutas del sistema
│
├── core/                                 ← NÚCLEO DEL FRAMEWORK MVC
│   ├── Controller.php                    ← Controlador base (toasts, renderizado de layout, auditorías)
│   ├── Database.php                      ← Singleton unificado (soporte sqlsrv, pgsql, sqlite)
│   ├── Model.php                         ← Modelo base (transacciones, PDO y lastInsertId)
│   ├── Router.php                        ← Router con middlewares de sesión, inactividad y permisos
│   └── View.php                          ← Manejador de compilación de plantillas HTML
│
├── helpers/                              ← FUNCIONES AUXILIARES GLOBALES
│   ├── form_helper.php                   ← Sanitización contra XSS e inyecciones básicas
│   ├── security_helper.php               ← Gestión y validación de tokens CSRF
│   ├── session_helper.php                ← Manejo estructurado de variables de sesión
│   └── url_helper.php                    ← Construcción de rutas y redirecciones
│
├── libs/                                 ← LIBRERÍAS DE TERCEROS
│   └── fpdf/                             ← Generador de reportes PDF vectorizados
│
├── modules/                              ← MÓDULOS DEL NEGOCIO (Controladores, Modelos y Vistas)
│   ├── Control_Bines/                    ← Gestión de Equipos, Estaciones y Movimientos (Bodega)
│   │   ├── controllers/                  
│   │   │   ├── BinController.php         ← Inventario General y consulta AJAX para DataTables
│   │   │   ├── EstacionController.php    ← Tablas Maestras y Administración de Zonas
│   │   │   └── MonitoreoController.php   ← Transacciones de Ingrese y Egreso de Bodega
│   │   ├── models/                       
│   │   │   ├── BinModel.php              ← Modelo para tabla inv_inventario
│   │   │   ├── EstacionModel.php         ← Modelo para tablas maestras y zonas
│   │   │   ├── InvEgreso.php             ← Modelo para egresos y detalles con control de stock
│   │   │   ├── InvIngreso.php            ← Modelo para ingresos y detalles de proveedores
│   │   │   ├── InvItemSistema.php        ← Modelo para compatibilidad de componentes portuarios
│   │   │   └── MedicionModel.php         ← Histórico de telemetría de bines
│   │   └── views/                        
│   │       ├── bines/                    ← Listado con DataTables, catálogo e ítems
│   │       ├── estaciones/               ← Administración de zonas y maestros
│   │       ├── monitoreo/                ← Formularios y listados de bodega
│   │       └── inv_reporte_inventario_pdf.php
│   │
│   ├── Talento_Humano/                   ← Directorio del Personal Portuario
│   │   ├── controllers/                  
│   │   │   └── EmpleadoController.php    ← Control de personal y asignaciones a departamentos
│   │   ├── models/                       
│   │   │   └── EmpleadoModel.php         ← Modelador de personal e históricos de traslados
│   │   └── views/                        
│   │       └── talento/                  ← Vista interactiva de empleados con AJAX logs
│   │
│   ├── Bitacoras/                        ← Módulo de Auditorías, Logs y Reportes
│   │   ├── controllers/                  
│   │   │   ├── EventoController.php      ← Visualización de la bitácora del sistema
│   │   │   └── ReporteController.php     ← Reportes financieros y cortes de periodos
│   │   ├── models/                       
│   │   │   ├── BitacoraModel.php         ← Modelo para bitácora general de operaciones
│   │   │   └── LogModel.php              ← Logger interno para errores de sistema
│   │   └── views/                        
│   │       └── bitacoras/                ← Vista de logs e informes financieros
│   │
│   ├── Central/                          ← Dashboard y Núcleo de Estilo Visual
│   │   ├── controllers/                  
│   │   │   ├── DashboardController.php   ← Estadísticas generales y gráficos iniciales
│   │   │   ├── ConfigController.php      ← Configuración de períodos e IVA
│   │   │   └── NotificacionesController.php ← AJAX seen state para alertas del header
│   │   ├── models/                       
│   │   │   ├── BusquedaGlobalModel.php   ← Buscador global cruzado multicomponente
│   │   │   ├── ConfigModel.php           ← Parámetros globales y tiempos de expiración
│   │   │   ├── NotificacionModel.php     ← Gestor persistente de alertas en base de datos
│   │   │   └── Notificacion.php          
│   │   └── views/                        
│   │       ├── central/                  ← Vistas de dashboard, configuración y periodos
│   │       ├── layout.php                ← Plantilla base global (Sidebar y Header Campanita)
│   │       └── error.php                 ← Pantilla de error del sistema
│   │
│   └── Credenciales/                     ← Control de Accesos y Seguridad
│       ├── controllers/                  
│       │   ├── AuthController.php        ← Login, loginPost y logout
│       │   ├── PermisoController.php     ← Módulo para asignar permisos por ruta a usuarios
│       │   └── UsuarioController.php     ← Registro de cuentas del sistema
│       ├── models/                       
│       │   ├── PermisoModel.php          ← Modelo de permisos por usuario y rol
│       │   └── UsuarioModel.php          ← Hashing de contraseñas y búsqueda
│       └── views/                        
│           ├── auth/                     ← Pantalla glassmorphic de Login
│           └── credenciales/             ← Vistas de gestión de usuarios y permisos
│
├── public/                               ← RECURSOS PÚBLICOS ESTÁTICOS
│   ├── css/
│   │   ├── estilos_inventario.css        ← Hoja de estilo alternativa de la UI
│   │   ├── inv_estilos.css               ← Diseño visual unificado (glassmorphic, azul profundo)
│   │   ├── talento_custom.css            ← Adaptación responsiva de modo claro/oscuro para TH
│   │   ├── talento_layout.css            ← Estilos de estructura de talento humano
│   │   ├── talento_toast.css             ← Notificaciones flash en TH
│   │   └── talento_variables.css         ← Variables cromáticas de talento humano
│   └── js/
│       ├── app_ajax.js                   ← Controlador de llamadas asíncronas AJAX
│       ├── inv_menu.js                   ← Gestión dinámica de navegación
│       ├── menu_inventario.js            ← Comportamientos del sidebar y menús
│       ├── talento_humano.js             ← Lógica de interacción en talento humano
│       └── talento_toast.js              ← Despacho de Toasts en talento humano
│
├── storage/                              ← ALMACENAMIENTO DE DATOS TEMPORALES
│   ├── cache/                            ← Cache de optimización del sistema
│   └── sessions/                         ← Almacenamiento persistente de sesiones
│
└── vendor/                               ← DEPENDENCIAS DE COMPOSER (Autoload)
```

---

## ⚙️ Características Destacadas del Framework Modular

### 1. Centralización de Entorno y Autodetección de URL
Toda la configuración crítica de base de datos se almacena en el archivo `.env`. Al no definirse la variable `APP_URL`, el archivo [config/globals.php](file:///C:/xampp/htdocs/Control_bines/config/globals.php) autodetecta de forma dinámica el protocolo, puerto y nombre del directorio de ejecución mediante variables `$_SERVER`, evitando enlaces caídos y errores 404 al renombrar carpetas.

### 2. Singleton de Base de Datos Multimotor
La clase [core/Database.php](file:///C:/xampp/htdocs/Control_bines/core/Database.php) implementa un patrón **Singleton** para instanciar una sola conexión PDO en toda la solicitud. Es compatible de manera nativa con:
- **SQL Server (`sqlsrv`)**: Empleado en entornos de producción local de XAMPP Windows.
- **PostgreSQL (`pgsql`)**: Ideal para servidores Linux empresariales.
- **SQLite (`sqlite`)**: Fallback de almacenamiento local autocontenido.
Si las tablas principales no existen, el motor ejecuta la creación del esquema y siembra datos iniciales de forma automatizada.

### 3. DataTables Server-Side Integrado
El listado general de inventario implementa procesamiento de lado del servidor (Server-Side) a través de jQuery DataTables.
- [BinController::listarAjax()](file:///C:/xampp/htdocs/Control_bines/modules/Control_Bines/controllers/BinController.php#L39-L93) recibe los parámetros asíncronos de paginación (`start`, `length`), ordenación (`order`), y filtros personalizados de categoría, unidad, estado o término de búsqueda.
- Transmite los parámetros a [BinModel::filtrar()](file:///C:/xampp/htdocs/Control_bines/modules/Control_Bines/models/BinModel.php#L95-L193), el cual ejecuta una consulta SQL paginada usando cláusulas `OFFSET / ROWS FETCH` (SQL Server) o `LIMIT / OFFSET` (SQLite/PostgreSQL).
- El navegador renderiza únicamente las filas visibles en milisegundos, garantizando un rendimiento óptimo incluso con miles de registros de equipos.

### 4. Middleware de Seguridad e Inactividad
El enrutador [core/Router.php](file:///C:/xampp/htdocs/Control_bines/core/Router.php) comprueba tres políticas de seguridad en cada despacho:
1. **Control de Sesión**: Redirige a la pantalla de login si el usuario no se encuentra autenticado.
2. **Tiempo de Inactividad**: Compara el tiempo transcurrido desde el último clic del usuario contra un parámetro global editable en base de datos. Si supera este límite, destruye la sesión y redirige a la pantalla de login con un Toast informativo.
3. **Gestión de Permisos**: Valida a nivel de base de datos si el rol del usuario posee permisos específicos para ejecutar la ruta GET solicitada, retornando respuestas HTTP 403 o JSON de acceso denegado en peticiones asíncronas.
