# Bitácora de Modificaciones - Sistema de Inventario Portuario v3.0 PHP MVC + SQLite
> Última actualización: 21 de Mayo de 2026  
> Autor: Antigravity AI  
> Versión: 3.0.0

---

## 📋 Índice
1. [Resumen de la Transición (v2.0 a v3.0)](#resumen)
2. [Cómo Ejecutar el Sistema](#como-ejecutar)
3. [Estructura de Archivos del Servidor](#estructura)
4. [Nuevos Módulos y Funcionalidades v3.0](#nuevos-modulos)
5. [Base de Datos SQLite & Modelos PHP](#bd-modelos)
6. [Flujo MVC del Lado del Servidor](#flujo-mvc)
7. [Controladores y Base de Datos (Seed Data)](#seed-data)
8. [Auditoría y Auditoría Contable (Históricos)](#auditoria)
9. [Seguridad de Acceso: Credenciales y Expiración (v3.1.0)](#seguridad)

---

## 📌 Resumen de la Transición (v2.0 a v3.0) <a name="resumen"></a>

El sistema ha dado un salto tecnológico mayor, migrando de la **v2.0 (MVC basada puramente en el Frontend con HTML y LocalStorage)** a la **v3.0 (Arquitectura MVC Robusta en el Servidor en PHP 7.4/8.x con Base de Datos SQLite Relacional)**. 

### 🚀 Mejoras Clave de la v3.0:
- **Persistencia Real**: Se reemplazó el `localStorage` del navegador por un motor SQL relacional (**SQLite 3**) gestionado a través de **PDO (PHP Data Objects)**.
- **Evitación de Código Duplicado**: Se implementó una **Vista de Plantilla Unificada (`app/views/layout.php`)** controlada por un controlador base (`ControllerBase.php`), que inyecta dinámicamente las vistas específicas e integra de forma global el sidebar y la barra de navegación.
- **Control de Existencias Físicas**: Se añadió la columna `cantidad` a los ítems del inventario, habilitando un control exacto de stock.
- **Módulo de Bodega (Ingresos y Egresos)**: Transacciones completas tipo cabecera-detalle para registrar ingresos de proveedores (inflows) y egresos de materiales a áreas (outflows), con recalculo automático de existencias físicas y alertas visuales.
- **Módulo de Talento Humano**: Directorio completo de empleados asignados a áreas, con historial de movimientos y transferencias de departamentos cronológicas (fechas de inicio y fin).
- **Módulo de Períodos e IVA Variable**: Configuración de períodos contables con tasas de IVA específicas (15%, 8%, 5%, 0%).
- **Congelamiento de Datos (Foto Histórica)**: Cierre de períodos contables que genera un respaldo inmutable de inventario y personal en el momento del corte para futuras auditorías tributarias.
- **Sistema de Notificaciones Reales**: Campanita interactiva en el header que avisa en tiempo real sobre stocks críticamente bajos (cantidad <= 5), desabastecimientos (cantidad = 0), o bien equipos fuera de servicio o bajo mantenimiento.
- **Reportes Formales e Impresión**: Generación de actas oficiales imprimibles en formato A4 para Ingresos y Egresos, y exportación a PDF formal del inventario.

---

## 🚀 Cómo Ejecutar el Sistema <a name="como-ejecutar"></a>

Debido a que el sistema ahora corre del lado del servidor en PHP, **requiere un servidor web local con soporte PHP y SQLite**.

### Opción 1: Usando XAMPP (Recomendado)
1. Colocar la carpeta `html` en el directorio raíz de publicaciones de XAMPP (usualmente `C:\xampp\htdocs\html`).
2. Asegurarse de tener habilitada la extensión `pdo_sqlite` en su archivo `php.ini` (XAMPP la tiene activa por defecto).
3. Iniciar el módulo **Apache** desde el Panel de Control de XAMPP.
4. Abrir en el navegador:
   `http://localhost/html/html/index.php` (o la ruta correspondiente).

### Opción 2: Servidor de Desarrollo Rápido de PHP (Línea de comandos)
Si cuenta con PHP instalado en su PATH, ejecute en la terminal dentro de la carpeta del proyecto (`c:\xampp\htdocs\html\html`):
```bash
php -S 127.0.0.1:8000
```
Y abra en el navegador: `http://127.0.0.1:8000/`

---

## 📁 Estructura de Archivos del Servidor <a name="estructura"></a>

El proyecto se encuentra totalmente limpio y modularizado en carpetas lógicas:

```
html/
│
├── index.php                            ← 🛣️ Front Controller y Enrutador Principal
├── logoapm.png                          ← Logo institucional
├── modificaciones_ui.md                 ← Esta bitácora de modificaciones
├── resumen_arquitectura.md              ← Documento técnico de la arquitectura v3.0
├── test_report.php                      ← Script de prueba de la base de datos
│
├── app/
│   ├── database.sqlite                  ← 🗄️ Base de datos relacional SQLite 3 (Auto-creada)
│   │
│   ├── models/                          ← 📦 MODELOS (Acceso a SQLite a través de PDO)
│   │   ├── Database.php                 ←    Conexión PDO Singleton + Esquema + Semilla (Seed)
│   │   ├── Inventario.php               ←    CRUD de bienes e ítems del puerto
│   │   ├── Cabecera.php                 ←    Gestión de tablas maestras (categorías, marcas, zonas, etc.)
│   │   ├── Periodo.php                  ←    Gestión de períodos, IVA y respaldo de cortes
│   │   ├── AsignacionTalento.php        ←    Gestión de empleados e historial de traslados
│   │   ├── Ingreso.php                  ←    Registro de transacciones de ingreso (inflow)
│   │   ├── Egreso.php                   ←    Registro de transacciones de egreso (outflow)
│   │   ├── Secuencial.php               ←    Manejo centralizado de secuenciales correlativos
│   │   ├── Bitacora.php                 ←    Auditoría persistente del sistema
│   │   └── Usuario.php                  ←    Control de usuarios y accesos
│   │
│   ├── controllers/                     ← 🎮 CONTROLADORES (Lógica de negocio y renderizado)
│   │   ├── ControllerBase.php           ←    Controlador base (Alerta campana, render templates, toasts)
│   │   ├── InventarioController.php     ←    Controlador para catálogo e inventario
│   │   ├── CabeceraController.php       ←    Controlador para tablas maestras
│   │   ├── PeriodoController.php        ←    Controlador para períodos, IVA e históricos
│   │   ├── AsignacionTalentoController.php ← Controlador de personal y traslados
│   │   ├── IngresosController.php       ←    Controlador de bodega (Entradas)
│   │   ├── EgresosController.php        ←    Controlador de bodega (Salidas)
│   │   ├── SecuencialController.php     ←    Controlador de contadores
│   │   └── UsuarioController.php        ←    Controlador de usuarios del sistema
│   │
│   └── views/                           ← 👁️ VISTAS (Inyectadas en layout.php)
│       ├── layout.php                   ←    Plantilla global (Sidebar menu + Header + Alertas campana)
│       ├── inv_inventario.php           ←    Dashboard principal y CRUD de inventario
│       ├── inv_items.php                ←    Catálogo visual de insumos en tarjetas
│       ├── th_cabeceras.php             ←    Gestión de 5 tablas maestras por pestañas (Tabs)
│       ├── periodos.php                 ←    Períodos contables, tasas de IVA y foto de cortes
│       ├── periodos_reporte.php         ←    Reportes avanzados financieros por rango de fecha
│       ├── talento_humano.php           ←    Directorio de personal y modal de reasignación
│       ├── bodega_ingresos.php          ←    Control de entradas a bodega e items dinámicos
│       ├── bodega_egresos.php           ←    Control de salidas a bodega por área
│       ├── bit_bitacora.php             ←    Bitácora persistente de auditoría
│       ├── acc_usuarios.php             ←    Gestión de usuarios y roles
│       ├── seq_secuenciales.php         ←    Controlador visual de contadores automáticos
│       ├── acta_ingreso.php             ←    Formato de acta limpia imprimible de ingreso
│       ├── acta_egreso.php              ←    Formato de acta limpia imprimible de egreso
│       └── reporte_inventario_pdf.php   ←    Formato limpio de reporte total del inventario
│
└── public/
    ├── css/
    │   └── inv_estilos.css              ← 🎨 Estilos unificados modernos (con soporte responsive)
    └── js/
        └── app_ajax.js                  ← ⚡ Controlador de peticiones AJAX asíncronas
```

---

## ✅ Nuevos Módulos y Funcionalidades v3.0 <a name="nuevos-modulos"></a>

### 1. Bodega - Gestión de Ingresos y Egresos (Cabecera-Detalle)
- **Ingresos**: Permite el ingreso de múltiples bienes o insumos en una sola transacción asociando un proveedor, fecha, observaciones y un responsable de bodega. Las cantidades ingresadas **incrementan automáticamente** la existencia física del ítem en el inventario general.
- **Egresos**: Permite la salida controlada de múltiples bienes a un responsable técnico asignado a un área portuaria específica. Cada egreso valida que existan unidades suficientes en stock antes de procesarse y **descuenta automáticamente** las cantidades.
- **Actas Oficiales**: Cada ingreso o egreso genera un **Acta Oficial Imprimible** (`acta_ingreso.php` y `acta_egreso.php`) que elimina los paneles laterales y cabeceras del sistema, mostrando un diseño limpio ideal para firmar entregas en formato físico o guardar como PDF corporativo.

### 2. Talento Humano y Reasignación Cronológica
- Directorio de colaboradores con asignación a su área de trabajo actual.
- **Historial de Traslados**: Modal interactivo que consume datos mediante AJAX para desplegar la línea de tiempo completa del empleado, indicando las áreas en las que ha prestado servicios, con fechas de inicio y fin exactas.
- **Reasignaciones**: Al transferir un empleado a un nuevo departamento, el sistema cierra automáticamente la asignación activa anterior con la fecha del cambio y crea el nuevo registro ininterrumpido.

### 3. Gestión de Períodos e IVA Variable
- Configuración de períodos activos (ejemplo: Año Fiscal actual).
- Asignación de tasas tributarias variables (15% general, 8% turismo, 5% emergencias, 0% exento).
- **Cierre y Respaldo de Seguridad**: Al cerrar un período fiscal, se realiza una **foto inmutable** (copia de seguridad) de todo el inventario, los valores unitarios, las asignaciones del personal y las áreas vigentes. Esto se guarda de forma persistente en `inv_respaldo_historico` para asegurar que las auditorías de años pasados no se vean alteradas si se cambian nombres de áreas o empleados en el futuro.

### 4. Sistema Centralizado de Alertas en Tiempo Real
- **Campanita Dinámica**: Ubicada en el header superior, consulta de forma reactiva la base de datos SQLite y despliega un globo rojo indicando la cantidad de novedades urgentes.
- **Tipos de Alertas**:
  - *Stock Agotado (Crítico)*: Cuando un bien activo tiene `cantidad = 0`.
  - *Existencias Bajas*: Cuando quedan 5 o menos unidades.
  - *Equipos en Incidencia*: Cuando el estado de un equipo es marcado como "Fuera de Servicio" o "En Mantenimiento".
- Al hacer clic en cualquier alerta del dropdown, el sistema redirige al usuario al inventario y aplica un filtro de búsqueda automático para aislar el equipo afectado.

---

## 🗄️ Base de Datos SQLite & Modelos PHP <a name="bd-modelos"></a>

El modelo relacional está diseñado para asegurar la integridad de los datos mediante claves foráneas y restricciones de unicidad.

### Esquema de Tablas en SQLite (`app/models/Database.php`):
1. **Tablas Maestras (TH)**:
   - `th_categorias`: Almacena tipos de bienes (Maquinaria Pesada, Contenedores, etc.).
   - `th_zonas`: Ubicaciones físicas en el puerto (Terminal Sur, Muelle Norte, etc.).
   - `th_estados`: Estados de operatividad (Operativo, En Mantenimiento, etc.).
   - `th_marcas`: Marcas autorizadas (Kalmar, Terberg, Liebherr).
   - `th_lineas`: Líneas navieras asociadas.
2. **Estructura del Personal**:
   - `talento_humano_areas`: Departamentos (Operaciones, Logística, Mantenimiento).
   - `talento_humano_personal`: Ficha de datos básicos e identificación única.
   - `talento_humano_asignaciones`: Pivote de asignación área-empleado con control de fechas (`fecha_inicio`, `fecha_fin`).
3. **Control Tributario**:
   - `periodos`: Rango de vigencia de años fiscales.
   - `valores_iva`: Tasas asociadas a cada período contable.
4. **Inventario y Respaldos**:
   - `inv_inventario`: Tabla de bienes activos, enlazando llaves foráneas a categorías, zonas, estados y responsables. Incluye cantidad física y valor base.
   - `inv_respaldo_historico`: Contenedor inmutable que guarda los datos planos consolidados tras el cierre de un período (evita pérdidas de datos históricos por reestructuraciones).
5. **Bodega (Ingresos y Egresos)**:
   - `bod_ingresos` y `bod_ingresos_detalles`: Transacciones de entradas de insumos.
   - `bod_egresos` y `bod_egresos_detalles`: Transacciones de salidas a departamentos técnicos.
6. **Sistema**:
   - `bitacora`: Registro cronológico persistente de acciones.
   - `secuenciales`: Contadores automáticos para generar códigos únicos (`INV-00001`, `ING-00001`, etc.).
   - `acc_usuarios`: Gestión de credenciales y perfiles de auditoría.

---

## 🔄 Flujo MVC del Lado del Servidor <a name="flujo-mvc"></a>

En la v3.0, el flujo es controlado en un 100% por el servidor, garantizando seguridad y consistencia:

```
Petición HTTP (URL) → index.php (Front Controller)
                          ↓
              Carga de Modelo correspondiente
                          ↓
              Ejecución de Acción en Controlador
                          ↓
          [Operaciones en app/database.sqlite]
                          ↓
     Inyección de Vista específica en layout.php
                          ↓
              Retorno de HTML Renderizado al Navegador
```

### Ejemplo de Carga Dinámica:
1. El usuario entra a `index.php?route=inventario`.
2. `index.php` detecta la ruta e instancia a `InventarioController`.
3. El controlador llama al modelo `Inventario.php` para obtener las existencias y estadísticas desde SQLite.
4. El controlador ejecuta `render('inv_inventario', $datos)`, lo cual captura el HTML del archivo `inv_inventario.php`, calcula las alertas dinámicas del header y lo encapsula dentro de la estructura general de `layout.php`.
5. El servidor envía una única página HTML optimizada al cliente.

---

## 🗃️ Datos de Prueba (Seed Data) <a name="seed-data"></a>

El sistema incluye un sistema de auto-inicialización. Si el archivo `app/database.sqlite` no existe, el motor lo crea y lo rellena automáticamente con datos coherentes para pruebas inmediatas:

- **8 Equipos de Inventario**: Grúa Pórtico, Contenedores Reefer, reach stackers, montacargas, etc.
- **4 Colaboradores de Talento Humano**: Juan Pérez, María Rodríguez, Pedro Gómez, Ana Belén; distribuidos en las 4 áreas del puerto.
- **1 Período Fiscal en Curso**: Con IVA al 15%.
- **4 Cuentas de Usuario Semilla (Credenciales)**:
  - **Administrador** (`Admin Terminal`): Usuario: `admin` | Contraseña: `admin123`
  - **Operador** (`Juan Operador`): Usuario: `juan` | Contraseña: `juan123`
  - **Supervisor** (`María Supervisora`): Usuario: `maria` | Contraseña: `maria123`
  - **Auditor** (`Pedro Auditor`): Usuario: `pedro` | Contraseña: `pedro123`

---

## 📝 Auditoría y Auditoría Contable (Históricos) <a name="auditoria"></a>

### Registro de Bitácora
Cualquier acción de modificación (CREAR, ACTUALIZAR, ELIMINAR, EXPORTAR, ACCESO) queda registrada inmediatamente en la tabla `bitacora` con marcas de tiempo exactas. Esta información es visible en el módulo **Bitácora del Sistema** y puede ser exportada en formato CSV.

### Inmutabilidad del Histórico
La funcionalidad de corte del período copia los registros activos resolviendo todas sus llaves foráneas y guardando los textos planos en `inv_respaldo_historico`. De este modo, si en el futuro se da de baja una zona o un empleado cambia de nombre, el reporte financiero de ese año contable cerrado permanecerá **idéntico e intacto**, cumpliendo con estándares internacionales de auditoría fiscal portuaria.

---

## 🔒 Seguridad de Acceso: Credenciales y Expiración (v3.1.0) <a name="seguridad"></a>

En la versión **v3.1.0**, el sistema ha incorporado mecanismos avanzados de autenticación y control de sesión, eliminando el antiguo inicio de sesión por selector pasivo y reforzando la protección de datos operativos.

### 🔑 Autenticación Basada en Credenciales Reales
- **Hashing Seguro**: Las contraseñas se almacenan de forma encriptada en la base de datos SQLite utilizando el algoritmo nativo `password_hash()` con `PASSWORD_DEFAULT` (compatible con PHP 7.4, 8.3 y 8.4).
- **Validación en Servidor**: El proceso de login valida los hashes de forma robusta utilizando `password_verify()`.
- **Campos Flexibles**: En la pantalla de login glassmorphic, los usuarios escriben directamente su **Usuario** y **Contraseña**.
- **Visualización de Contraseña**: Se integró un botón interactivo (icono de ojo `fa-eye`) para alternar la visibilidad de la contraseña en el formulario sin comprometer la seguridad.

### 👤 Administración Completa de Credenciales (CRUD de Usuarios)
- **Visualización de Usuario**: El panel de administración de usuarios ahora incluye la columna **Usuario (Login)** con un badge de código.
- **Campos de Modal de Registro**:
  - **Usuario (Login)**: Campo de texto con validación de patrón de caracteres.
  - **Contraseña**: Se valida dinámicamente mediante JavaScript:
    - *Al registrar un nuevo usuario*: El campo es **obligatorio**.
    - *Al editar un usuario existente*: El campo es **opcional**. Si se deja en blanco, el sistema conserva de manera segura la contraseña actual en la base de datos.

### 🧪 Pruebas de Expiración Automatizadas (`test_inactivity.php`)
- Se ha diseñado un script de aserciones en el directorio raíz del proyecto (`test_inactivity.php`).
- **Simulación Multi-entorno**: El script detecta de manera reactiva el entorno. Si se ejecuta en el navegador (Web), despliega un panel de pruebas premium con diseño glassmorphic y orbes decorativos. Si se corre en terminal (CLI), genera una salida en formato de logs coloreados mediante códigos de escape ANSI.
- **Validación del Middleware**:
  - Simula el inicio de sesión y marca el último acceso.
  - Simula inactividad por debajo del tiempo configurado y afirma que la sesión se mantiene activa.
  - Simula inactividad por encima del tiempo configurado y afirma que la sesión es completamente destruida, inyectando la notificación de Toast en la pantalla de login.

---

## 🚀 Recreación desde Cero y Máxima Compatibilidad de Bodega y Telemetría Activa (v3.4.0)
- **Recreación Limpia Completa**: Se eliminaron y reescribieron desde cero los archivos de vistas `bodega_ingresos.php` y `bodega_egresos.php` para eliminar cualquier vestigio de código antiguo, inconsistencias del DOM o errores residuales de parsing de HTML5.
- **Sintaxis de JavaScript Ultra-Compatible**: Se eliminó totalmente el uso de la etiqueta `<template>` HTML5. En su lugar, las filas dinámicas del desglose de productos se manejan mediante arrays de cadenas concatenadas (`[ '<tr>', '... </tr>' ].join('\n')`), el cual es procesado de forma nativa por el motor de JavaScript en cualquier navegador del mundo sin importar su antigüedad o restricciones de WebView.
- **Escapado Preventivo Contra Rompimientos de JS**: En el mapeo PHP de los productos, se integró una limpieza activa contra caracteres especiales (`str_replace(array("'", "\"", "\r", "\n"), " ", ...)`) previniendo que cualquier comilla simple, comilla doble o salto de línea en los nombres o marcas de los insumos rompa la delimitación de las cadenas en JavaScript.
- **Telemetría de Consola en Tiempo Real**: Se configuraron impresiones de consola (`console.log`) en la inicialización de los scripts, en la apertura de modales, inyección de filas base, adición y remoción de desgloses dinámicos, facilitando un diagnóstico rápido de las interacciones en el navegador.
- **Flujo AJAX Protegido**: Las consultas dinámicas para cargar los detalles del "ojito" de ingresos (`verDetallesIngreso`) y egresos (`verDetallesEgreso`) se ejecutan a través de un consumo de texto plano con decodificación `JSON.parse` envuelto en `try-catch`, lo cual evita bloqueos visuales y controla con gracia cualquier problema de red o respuestas inesperadas.

---

## 🚀 Maestros Relacionales, Reportes Varios y Ajustes de Contraste (v3.5.0)
- **Base de Datos Ampliada**: Se integraron las tablas relacionales `th_proveedores`, `th_grupo_centros_consumo` y `th_centros_consumo` en `Database.php`, además de la columna `codigo` en `th_categorias` para soportar códigos jerárquicos institucionales, sembrando semillas iniciales coherentes.
- **Modelo y Controlador Robustos**: Se adaptó `Cabecera.php` y `CabeceraController.php` para admitir operaciones seguras e independientes de enrutamiento y captura condicional de campos en las 7 entidades maestras.
- **Maestros Modernizados por Pestañas**: Se reescribió `maestros.php` incorporando un panel izquierdo de pestañas interactivas, tablas detalladas con badges estéticos y formularios modales glassmorphic que capturan información jerárquica con autoselecciones automáticas.
- **Autocompletado Inteligente en Bodega**:
  - *Ingresos*: Selector oficial de proveedores con script JS que precarga dinámicamente el valor unitario base de los insumos seleccionados al detalle de compra.
  - *Egresos*: Selector oficial de centros de consumo con script JS que precarga y autoselecciona de inmediato al funcionario a cargo como receptor de la entrega.
- **Módulo de Reportes Varios**:
  - Controlador `ReporteController.php` con queries complejas de agregaciones.
  - Vista unificada `reportes_varios.php` con 5 pestañas de consulta (Listado de Proveedores, Listado de Centros, Listado de Ítems valorizados con stock crítico, Compras por fecha/proveedor y Reporte Mensual de compras consolidado).
  - Plantilla de impresión `imprimir_reporte.php` adaptada a membrete institucional formal A4 y pie de firmas de auditoría.
- **Ajustes Estéticos de UI ("Más Azulito")**:
  - Modificación de la paleta general en `inv_estilos.css` a azul rey corporativo profundo (`#0c2340` en Claro, `#08162b` en Oscuro), con transiciones y menús activos estilizados de forma premium.
  - Corrección de la tarjeta informativa inferior de Maestros cambiando el fondo oscuro e ilegible por un overlay traslúcido celeste claro (`rgba(37,99,235,0.05)`) con un contraste excelente y legibilidad del 100% en Claro y Oscuro.

---

## 🔔 Sistema Inteligente de Notificaciones Reales e Interactivas (v3.6.0)
- **Persistencia de Alertas (Tabla y Modelo)**:
  - Creación de la tabla `th_notificaciones` en base de datos para almacenar avisos permanentes del sistema.
  - Desarrollo del modelo [Notificacion.php](file:///c:/xampp/htdocs/html/app/models/Notificacion.php) para la manipulación y obtención de alertas tanto leídas como no leídas.
- **AJAX Seen State (Desvanecimiento del Badge e Indicadores)**:
  - Enrutamiento seguro en `Router.php` de la ruta `notificaciones_marcar_leidas` mapeada a `InventarioController.php`.
  - Al hacer clic sobre el botón de la campanita (`bell-btn`), se envía un fetch AJAX que actualiza todas las alertas persistentes como vistas (`visto = 1`) y registra sus IDs en la sesión.
  - Esto **remueve inmediatamente el globo numérico rojo (badge)** y quita la clase `.no-leida` (fondo sombreado y el **punto azul de no leído** a la derecha de los ítems) en tiempo real sin recargar la pantalla.
- **Sistema Automatizado de Limpieza (Auto-Cleanup)**:
  - Integración en las consultas generales de un borrado automático de registros obsoletos de notificaciones persistentes que posean más de **15 días de antigüedad**, garantizando que el espacio en disco de SQLite esté optimizado de forma perpetua.
- **Notificación Ampliada a Más Apartados**:
  - Incorporamos alertas transaccionales nativas del tipo `info` en las acciones de los controladores:
    1. **Ingresos**: Genera alerta del secuencial y proveedor emisor al registrar una compra.
    2. **Egresos**: Genera alerta del secuencial y del centro de consumo receptor al despachar materiales.
    3. **Seguridad**: Genera alerta sobre inicios de sesión de usuarios y creaciones o eliminaciones de cuentas.
    4. **Maestros**: Genera alerta cuando se registra un nuevo Proveedor, Producto, Centro de Consumo, etc.
  - Se configuró iconografía y tags dinámicos acordes a la naturaleza del aviso.

---

## 📊 Carga Bajo Demanda y Corrección de Impresión de Reportes Varios (v3.7.0)
- **Política de Generación Bajo Demanda (On-Demand Loading)**:
  - Se modificó la interfaz para que, al acceder por primera vez o alternar entre las 5 pestañas de reportes, el sistema no ejecute automáticamente las consultas a SQLite.
  - Se implementó un **Placeholder Glassmorphic** premium e interactivo que invita al usuario a configurar los filtros deseados y hacer clic en **"Filtrar"** o **"Cargar Listado Oficial"** para efectuar la búsqueda de forma explícita, acelerando drásticamente el tiempo de respuesta inicial de la página.
- **Resolución de Bug de Impresión (Nesting Bug)**:
  - Se corrigió el anidamiento implícito y defectuoso de condicionales `if` dentro del archivo de impresión oficial (`imprimir_reporte.php`), reestructurándolo como una cadena limpia de `if-elseif-endif`. Esto solucionó el problema por el cual solo se podía imprimir el reporte de Proveedores, habilitando ahora la impresión vectorizada oficial A4/PDF perfecta para los 5 tipos de reportes del sistema.
- **Búsqueda por ID unificada y robusta**:
  - Se integró el parámetro de búsqueda general (`termino`) a todas las pestañas de reportes sin distinción, permitiendo buscar y aislar un registro exacto de forma instantánea mediante su ID numérico primario, códigos correlativos o de responsable.
  - Se sincronizaron en un 100% las consultas SQL entre las vistas interactivas del sistema (`index()`) y sus correspondientes formatos oficiales impresos (`imprimir()`).
- **Botón de Impresión Dinámico**:
  - Se acondicionó el botón superior de **"Imprimir Reporte Oficial"** para que permanezca oculto y solo se habilite cuando el listado contenga registros válidos generados bajo demanda.

---

## 📊 Rangos de ID, Persistencia de Estado y Purga Manual de Alertas (v3.7.1)
- **Búsqueda por Rango de IDs (ID Desde / ID Hasta)**:
  - Se agregaron campos de filtrado dedicados (`id_inicio` y `id_fin`) en la barra de controles de búsqueda del módulo de **Reportes Varios** para todos los reportes.
  - Se modificaron las consultas SQL relacionales en `ReporteController.php` para filtrar dinámicamente por límites numéricos de ID (`id >= :id_ini` y `id <= :id_fin`) tanto en la vista interactiva (`index()`) como en la versión imprimible oficial (`imprimir()`).
  - Se adaptó la cabecera de la plantilla imprimible (`imprimir_reporte.php`) para incluir el indicador de "IDs Filtrados" en los metadatos cuando estos filtros estén activos.
- **Persistencia de Estado de Carga y Filtros en Pestañas**:
  - Se modificaron los enlaces de las pestañas en `reportes_varios.php` para que acarreen y mantengan de forma permanente los filtros activos de fecha, proveedor, término y rango de IDs.
  - Se logró que, una vez que el usuario hace clic en "Filtrar" (activando la generación de datos bajo demanda `generar=1`), al alternar entre pestañas, **el listado se mantenga cargado** y no requiera volver a presionar el botón, optimizando radicalmente la usabilidad.
- **Opción de Purga / Limpieza Manual de Alertas**:
  - Se agregó una opción interactiva de **"Vaciar Alertas"** con icono de papelera (`fa-trash-can`) en el pie de página (`notif-footer`) del menú desplegable de notificaciones del header global.
  - Se implementó la acción `vaciarNotificaciones()` en `InventarioController.php` y el método `vaciarTodas()` en `Notificacion.php` para purgar por completo la tabla `th_notificaciones` de SQLite y limpiar el estado de la sesión por AJAX, actualizando visualmente el menú sin recargar la pantalla.
  - **Corrección de Persistencia en el Vaciado**: Se solucionó el problema por el cual las alertas dinámicas de stock y mantenimiento (calculadas en tiempo real) reaparecían tras recargar la página. Ahora, al presionar "Vaciar Alertas", todos los IDs activos (persistentes y dinámicos) se inyectan en el arreglo `$_SESSION['notificaciones_eliminadas']` y `ControllerBase.php` las omite del renderizado durante toda la vigencia de la sesión.
  - **Modal de Confirmación Glassmorphic Estilizado**: Se eliminó el antiestético cuadro de diálogo nativo del navegador `confirm()`. En su lugar, se diseñó e inyectó un **Modal Overlay de Confirmación Glassmorphic** personalizado de altísima calidad visual. Cuenta con fondo de desfoque de desenfoque de pantalla (`backdrop-filter: blur(8px)`), icono de alerta con animación pulsante de peligro (`pulseDanger`), tipografía refinada y botones estilizados adaptativos compatibles al 100% con los modos Claro y Oscuro del sistema.

---

## 🔍 Búsqueda Global Dedicada, Filtros Modulares y Corrección de Compatibilidad Multi-Driver (v3.8.0)
- **Búsqueda Global Dedicada (`route=busqueda_global`)**:
  - Se creó un módulo de búsqueda global completamente unificado y aislado de los buscadores individuales locales, accesible desde la barra lateral ("Búsqueda Global") y desde el Header principal.
  - El buscador del Header principal se sincronizó con el atajo de teclado global `Ctrl + K` para enfocar de forma inmediata el buscador en cualquier parte del sistema.
- **Vistas Específicas del Buscador (`app/views/busqueda_global.php`)**:
  - Se diseñó un panel interactivo premium con estética glassmorphic, que incluye sugerencias rápidas ("Grúa", "Kalmar", "ING-", "EGR-", "admin") para guiar al usuario mediante clics interactivos.
  - Implementación de un panel lateral dinámico con interruptores estéticos de filtro por módulo (Inventario, Bodega, Tablas Maestras, Usuarios, Bitácora) con reactividad instantánea por AJAX/GET (se recarga automáticamente al cambiar el estado de los filtros).
- **Modelo de Búsqueda Cruzada Unificado (`app/models/BusquedaGlobal.php`)**:
  - Nuevo modelo que centraliza consultas de texto libre contra las tablas `inv_inventario`, `bod_ingresos`, `bod_egresos`, las 11 entidades maestras de `Cabecera`, `acc_usuarios` y `bitacora`.
  - Normaliza la salida de los datos en una estructura común con iconos representativos, colores, badges de visualización e hipervínculos dinámicos de redirección visual.
  - Elimina sintaxis específicas de concatenación de cadenas de motores SQL particulares en favor de procesamientos de bindings nativos de PHP.
- **Controlador e Integración de Enrutamiento**:
  - Registro formal de la ruta `busqueda_global` mapeada a `InventarioController.php` dentro de `Router.php`.
  - Creación del método `busquedaGlobal()` en el controlador para recibir y limpiar la consulta `q`, procesar el array de filtros modulares activos, invocar al modelo y despachar las variables al frontend.
- **Limpieza de Vistas Locales (`maestros.php` y `th_cabeceras.php`)**:
  - Se eliminaron por completo las antiguas cajas de búsqueda global flotante y paneles asíncronos redundantes de las vistas de Maestros y Cabeceras, dejando únicamente el filtro local `#buscador-individual` (tiempo real por cliente) y el botón `#btn-toggle-datos`, aliviando la interfaz visual de elementos duplicados.
  - Se preservó la lógica inteligente de auto-resaltado: si el usuario llega a una de estas páginas mediante un clic de resultado global con el parámetro `highlight=ID`, la página abre la tabla correspondiente (si estaba oculta), hace scroll automático con desplazamiento suave (`scrollIntoView`) y hace brillar la fila en azul corporativo por 4 segundos con animación fluida.
- **Compatibilidad Multi-Driver de Base de Datos (PostgreSQL, SQL Server, SQLite)**:
  - **Esquema de Permisos robusto (`app/models/Database.php` and `app/models/Permiso.php`)**: Se formalizó la DDL de creación de la tabla `acc_permisos` para todos los drivers dentro del migrador y base de datos. Se eliminaron tipos de datos SQLite exclusivos (`INTEGER PRIMARY KEY AUTOINCREMENT`) que hacían colapsar las bases de datos de producción de PostgreSQL. Se cambió `INSERT OR IGNORE` por una operación estandarizada y segura.
  - **Bitácora y Límites Dinámicos (`app/models/Bitacora.php`)**: Se sustituyeron las funciones de fecha propietarias de SQL Server (`GETDATE()`) por la palabra clave estándar de la industria `CURRENT_TIMESTAMP`.
  - **Compilador Dinámico de Límites en Bitácora**: Se corrigió el error del visualizador histórico de bitácora modificando el constructor de consultas `filtrar()` para inyectar `SELECT TOP 500` en entornos SQL Server (`sqlsrv`) y usar una cláusula `LIMIT 500` estándar al final de la consulta para PostgreSQL (`pgsql`) y SQLite (`sqlite`), resolviendo los desplomes por incompatibilidades sintácticas en servidores Linux/PostgreSQL.

---

## 🔍 Búsqueda Global Unificada en Maestros, Filtros Avanzados y Normalización de PostgreSQL (v3.8.1)
- **Reubicación de la Búsqueda Global**: Traslado del módulo de búsqueda global directamente dentro de la interfaz de **Gestión de Maestros** (`index.php?route=maestros&tabla=busqueda_global`), centralizándolo como una pestaña destacada con medalla de estrella dorada en el menú lateral de estructuras.
- **Eliminación del Buscador Superior Redundante**: Limpieza del header principal en `layout.php` removiendo la barra de entrada de texto duplicada para simplificar la UI y evitar redundancias operativas.
- **Filtros Avanzados de Búsqueda**: Adición en el panel de búsqueda de dos controles desplegables: "Ámbito del Campo" (permite restringir el rastreo a Todos los campos, Código/Secuencial únicamente, Nombre/Descripción/Proveedor, o Ubicación/Marca/Detalle Adicional) y "Límite de Resultados" (máximo de 10, 25, 50 o 100 coincidencias).
- **Atajos y Enrutamiento Inteligente**: Reconfiguración del atajo `Ctrl + K`. Si el usuario se encuentra en cualquier otra vista, el atajo redirige instantáneamente a la pestaña de Búsqueda Global en Maestros; si ya se encuentra allí, enfoca el cursor de inmediato en la barra de búsqueda central.
- **Limpieza de Archivos Obsoletos**: Eliminación de la vista duplicada `app/views/busqueda_global.php`, la ruta de dispatch de `Router.php` y el método obsoleto `busquedaGlobal()` del `InventarioController.php` para mantener la arquitectura de la aplicación en el máximo orden y ligereza.
- **Compatibilidad Extrema con PostgreSQL y Robustez en PHP 8.4**:
  - Inyección de una política de silenciado de errores por stdout para solicitudes AJAX/JSON (`$isAjax` detection) en el manejador global de errores (`set_error_handler` de `ControllerBase.php`). De esta forma, si ocurre algún aviso no crítico o advertencia menor en desarrollo, este se registra perfectamente en los logs de la terminal pero **nunca se imprime en la respuesta AJAX**, previniendo que se corrompa el formato JSON de lazy-load y eliminando de forma definitiva el aviso `"Error al cargar los datos. Intente de nuevo"`.

---

## 🚀 Compatibilidad PostgreSQL en Historial de Períodos, Cierres y Respaldos (v3.8.2)
- **Resolución de Error de Sintaxis SQL**: Se corrigió el desplome crítico al generar reportes por período y realizar cierres contables con respaldo en la función `obtenerAreaPorFecha()` de [AsignacionTalento.php](file:///c:/xampp/htdocs/html/app/models/AsignacionTalento.php). La consulta utilizaba el operador `SELECT TOP 1` exclusivo de SQL Server, el cual colapsaba en PostgreSQL. Se reestructuró la consulta de forma dinámica: utiliza `SELECT TOP 1` en entornos con driver `sqlsrv`, y la sintaxis estándar de la industria `SELECT ... LIMIT 1` para motores PostgreSQL (`pgsql`) y SQLite (`sqlite`), permitiendo cierres y respaldos de períodos sin fallos.
- **Null-Safety y Robustez en PHP 8.4**: Se añadieron castings explícitos a `(string)` y protectores contra nulos `?? ''` al invocar `htmlspecialchars()` en [periodos.php](file:///c:/xampp/htdocs/html/app/views/periodos.php) y [periodos_reporte.php](file:///c:/xampp/htdocs/html/app/views/periodos_reporte.php), previniendo de forma proactiva avisos de deprecación al recuperar campos opcionales del histórico contable desde la base de datos de PostgreSQL.

---

## 🏗️ Reestructuración Modular, .env y DataTables Server-Side (v4.0.0)
- **Patrón MVC Modular Unificado**: Se restructuró todo el sistema migrando del antiguo directorio `/app` a una estructura modular basada en carpetas independientes bajo `/modules` (Control_bines, Talento_Humano, Bitacoras, Central y Credenciales).
- **Centralización en `.env`**: Se eliminó la configuración distribuida por archivos y se unificó en un único archivo `.env` en la raíz, con autodetección dinámica de la URL del servidor (`APP_URL`) en [config/globals.php](file:///C:/xampp/htdocs/Control_bines/config/globals.php) para evitar errores 404 al renombrar carpetas.
- **Integración de DataTables Server-Side**: Se implementó el plugin interactivo **DataTables** (jQuery) con procesamiento completo del lado del servidor (Server-Side) en [BinController.php](file:///C:/xampp/htdocs/Control_bines/modules/Control_Bines/controllers/BinController.php) y [BinModel.php](file:///C:/xampp/htdocs/Control_bines/modules/Control_Bines/models/BinModel.php) para manejar de forma premium y veloz el inventario general con búsqueda en tiempo real, ordenación y paginación asíncrona.
- **Limpieza y Depuración del Workspace**: Eliminación completa del antiguo directorio `/app` y de scripts de prueba obsoletos del directorio raíz.

---

## 🗂️ Adaptación de Casing Modular, Helpers y Compatibilidad PHP 8.5 (v4.1.0)
- **Cambio de Casing a `Control_Bines`**: Se renombró la carpeta del módulo de `modules/Control_bines` a `modules/Control_Bines` y se actualizaron todas las referencias de importación (`require_once`), configuraciones de ruta (`config/routes.php`) y variables de entorno para cumplir con el estándar.
- **Directorios Estructurales y Helpers**: Creación de las carpetas `helpers/`, `libs/`, `storage/cache/`, `storage/sessions/` y `vendor/`. Implementación de helpers globales autolimpiables:
  - `session_helper.php` para manejo de sesiones y alertas flash.
  - `url_helper.php` para redireccionamiento estructurado.
  - `form_helper.php` para sanitización de inputs y prevención de XSS.
  - `security_helper.php` para tokens CSRF.
  - Carga automática en [globals.php](file:///C:/xampp/htdocs/Control_bines/config/globals.php) vía `glob()`.
- **Robustez en PHP 8.5 e Independencia de PDO**:
  - Implementación de clase dummy de `PDO` en [DatabaseConnection.php](file:///C:/xampp/htdocs/Control_bines/core/DatabaseConnection.php) para evitar errores si la extensión PDO de PHP se deshabilita en `php.ini`.
  - Soporte de recuperación de índices de columnas en `fetchAll()` con `PDO::FETCH_COLUMN` en [DatabaseStatement.php](file:///C:/xampp/htdocs/Control_bines/core/DatabaseStatement.php).
  - Explicitación de nulos (`?int`) en las firmas de los adaptadores de consultas para cumplir con el estándar estricto de tipos de PHP 8.4/8.5.

---

## 🏢 Integración del Módulo de Talento Humano con SQL Server y Sincronización en Tiempo Real (v4.2.0)
- **Restauración y Bridge de Base de Datos**:
  - Creación del script [setup_database.php](file:///c:/xampp/htdocs/Control_bines/setup_database.php) para automatizar la restauración de las bases de datos de SQL Server (`inventario` y `Talento_Humano`) desde los archivos `.bak` del proyecto.
  - Creación de la tabla `th_historial_laboral` y columnas adicionales (`sucedido_por_id`, `fecha_fin`) en `Talento_Humano` si no existen.
  - Configuración de 8 vistas de lectura/escritura cruzadas en la base de datos `inventario` apuntando a `Talento_Humano` para permitir operaciones fluidas desde un único adaptador.
- **Sincronización Automática en Tiempo Real**:
  - Configuración de un trigger de base de datos `trg_sync_th_empleados_to_inventario` en `Talento_Humano.dbo.th_empleados`. Cualquier alta, modificación o baja de personal se replica de forma inmediata y automática en la tabla `inventario.dbo.inv_talento_personal`.
- **Enrutamiento y Menú Lateral**:
  - Registro de más de 20 endpoints operacionales en [routes.php](file:///c:/xampp/htdocs/Control_bines/config/routes.php).
  - Modificación de [layout.php](file:///c:/xampp/htdocs/Control_bines/modules/Central/views/layout.php) para incluir la opción "Gestión de Personal" en el sidebar y gestionar la carga dinámica del stylesheet `talento_layout.css`.
- **Vistas de Talento Humano Utilizadas (Layout-Stripped)**:
  - Se portaron y reorganizaron las vistas necesarias para **Control de Bienes** (el CRUD de expedientes de personal) dentro de subcarpetas en `modules/Talento_Humano/views/`:
    - **`empleados/listar.php`**: Tabla maestra de funcionarios con búsqueda instantánea y filtros avanzados.
    - **`empleados/agregar.php`**: Formulario estructurado para registro de nuevos expedientes.
    - **`empleados/editar.php`**: Formulario estructurado para edición de expedientes existentes.
    - **`nominas/`**: Carpeta modular para vistas de nómina.
    - **`capacitaciones/`**: Carpeta modular para vistas de capacitación.

---

## 🏢 Independencia de Conexiones de Base de Datos y Aislamiento Modular (v4.2.1)
- **Aislamiento de Módulos a nivel de Datos**:
  - Modificación de [DatabaseConnection.php](file:///C:/xampp/htdocs/Control_bines/core/DatabaseConnection.php) y [Database.php](file:///C:/xampp/htdocs/Control_bines/core/Database.php) para soportar múltiples conexiones simultáneas y parametrización de nombres de base de datos.
  - Conexión directa del módulo **Talento Humano** a la base de datos `Talento_Humano` a través de sus modelos, eliminando el prefijo estático `Talento_Humano.dbo` de las consultas y manteniendo los esquemas y conexiones 100% aislados e independientes.
- **Depuración de la Interfaz General**:
  - Simplificación del sidebar principal en [layout.php](file:///C:/xampp/htdocs/Control_bines/modules/Central/views/layout.php) para exponer únicamente el "Directorio de Personal", eliminando opciones irrelevantes para el inventario de bienes (tales como asistencias, vacaciones, acciones de personal y desempeño).
  - Depuración de botones y enlaces a rutas inactivas en la vista [listar.php](file:///C:/xampp/htdocs/Control_bines/modules/Talento_Humano/views/empleados/listar.php).

---

## 🎨 Ajustes Estéticos de UI, Solapamientos y Soporte de Modo Oscuro en Talento Humano (v4.2.2)
- **Corrección de Avatares y Parpadeo**:
  - Reemplazo del fallback faltante por un SVG incrustado (`data:image/svg+xml;...`) y adición de control `this.onerror=null` para detener el parpadeo cíclico si una imagen no se localiza.
- **Prevención de Solapamiento en Tabla**:
  - Alineación de texto y adición de márgenes correctos a las columnas en `listar.php`, agregando `flex-shrink: 0` al contenedor del avatar de modo que no colapse el nombre del funcionario.
- **Soporte de Modo Oscuro**:
  - Eliminación de stylesheets externos clónicos (`talento_layout.css` y `talento_variables.css`) que sobreescribían el diseño global de la app.
  - Creación y vinculación de [talento_custom.css](file:///c:/xampp/htdocs/Control_bines/public/css/talento_custom.css) con el sistema de variables de CSS del inventario para cambiar dinámicamente de colores al alternar entre tema oscuro y claro.