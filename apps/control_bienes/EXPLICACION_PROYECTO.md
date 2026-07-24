# Guía General del Proyecto: Control de Bines (SysPort)

Bienvenido a la documentación explicativa de **Control_bines (SysPort)**. Este documento sirve como manual explicativo y guía técnica para entender el propósito de la aplicación, su alcance, características funcionales y la forma de integrarlo con otros proyectos.

---

## 🎯 Propósito del Proyecto

El sistema **Control_Bines** es un portal web corporativo diseñado para la **Autoridad Portuaria de Manta (APM)**. Su objetivo principal es automatizar la administración física y contable del inventario de equipos portuarios (como grúas pórtico, contenedores reefer, cabezales y reach stackers), controlando las transacciones de bodega (ingresos y egresos), el talento humano asignado a las operaciones y la seguridad de acceso de los operadores del sistema.

Este proyecto ha sido reestructurado en una **arquitectura MVC modular en puro PHP** (v4.0.0), lo que facilita su integración directa con otros módulos de la terminal portuaria en un solo sistema unificado.

---

## 🚀 Funcionalidades Principales

### 1. Inventario General (DataTables Server-Side)
- **Visualización Eficiente**: La lista principal carga miles de equipos en milisegundos gracias a la paginación del lado del servidor (Server-Side) con DataTables.
- **Filtros Avanzados**: Búsqueda en tiempo real por texto libre, filtrado por categorías de insumos, unidades de medida y estado operativo de las máquinas.
- **Exportaciones Rápidas**: Permite descargar la base de datos de bienes filtrados a formato CSV (Excel) o generar un reporte formal vectorizado en PDF.

### 2. Tablas Maestras (Estructuras de Datos)
- Administración centralizada de las categorías de bienes, zonas o terminales físicas del puerto, marcas autorizadas de maquinaria, unidades de medida de stock y tasas de IVA vigentes.
- Se implementó un panel lateral por pestañas con autocompletados inteligentes al asociar datos.

### 3. Movimientos de Bodega (Ingresos y Egresos)
- **Ingresos a Bodega**: Registro detallado de insumos comprados a proveedores portuarios. Incrementa de forma automática y transaccional el stock físico de los bienes.
- **Egresos a Bodega**: Despacho de insumos a funcionarios para áreas técnicas (como operaciones o mantenimiento). Valida preventivamente la disponibilidad de existencias antes de descontar stock.
- **Actas Oficiales**: Genera un acta oficial A4 imprimible con firmas de descargo de responsabilidad para cada ingreso o egreso.

### 4. Talento Humano y Reasignación de Áreas
- Registro del personal portuario y su departamento de trabajo activo.
- **Historial de Movimientos**: A través de solicitudes asíncronas AJAX, el sistema muestra la línea de tiempo de áreas donde ha trabajado cada empleado sin necesidad de recargar la pantalla.

### 5. Cierre Fiscal y Auditoría Inmutable
- **Cierre Contable**: Congela el inventario y las asignaciones del personal al realizar un corte de período.
- **Respaldo Histórico**: Almacena de manera plana los datos consolidados en el momento exacto del corte. Esto garantiza que las auditorías de años pasados no muestren variaciones, incluso si se eliminan empleados o se renombran áreas en el futuro.

### 6. Sistema de Alertas del Header (Campanita)
- campanita en el Header superior que realiza consultas en base de datos para alertar a los supervisores sobre stock agotado, existencias críticamente bajas y equipos reportados en mantenimiento o fuera de servicio.

---

## 🛠️ Arquitectura Técnica y Ejecución

El proyecto corre del lado del servidor utilizando **PHP nativo** y es compatible con **PHP 7.4, 8.3, 8.4 y 8.5**.

### Compatibilidad con PHP 8.5
El código ha sido diseñado y validado bajo estándares modernos de PHP, garantizando compatibilidad absoluta con PHP 8.5:
- **Sin Nullables Implícitos**: Se evita la sintaxis deprecada en 8.4 y removida en 8.5 de parámetros con tipos estrictos asignados a null sin signo de interrogación (ej. `string $var = null` ahora se define como `?string $var = null` o simplemente sin tipar en firmas genéricas).
- **Propiedades Declaradas**: Todas las propiedades en clases Controladores y Modelos están explícitamente declaradas para evitar advertencias de creación dinámica de propiedades (introducido en PHP 8.2).
- **Manejo Seguro de Errores**: El gestor de excepciones y errores captura las advertencias modernas sin interferir con las respuestas de red asíncronas.

### Requisitos de Ejecución
1. Servidor web local como **XAMPP** con soporte PHP.
2. Extensión de base de datos activa según tu entorno:
   - **Microsoft SQL Server**: Driver `sqlsrv` (Configuración por defecto en el `.env` del proyecto).
   - **PostgreSQL**: Driver `pgsql`.
   - **SQLite**: Driver `sqlite` (no requiere motor externo, corre directo en archivo local).

### Configuración Rápida (`.env`)
Para configurar la conexión de datos, edita el archivo `.env` en la raíz del proyecto. El sistema autodetecta automáticamente la URL del servidor para evitar enlaces rotos:

```env
DB_DRIVER=sqlsrv             # Drivers: sqlsrv, pgsql o sqlite
DB_HOST=JORDANYMB1\JORDANYMB # Servidor de base de datos
DB_NAME=inventario           # Base de datos
DB_USER=sa                   # Usuario
DB_PASS=1234                 # Contraseña
```

### Seguridad y Roles por Defecto
El sistema cuenta con 4 cuentas de acceso iniciales para pruebas operativas:
- **Administrador** (Acceso total): Usuario `admin` | Clave `admin123`
- **Operador** (Registro y bodega): Usuario `juan` | Clave `juan123`
- **Supervisor** (Cierres contables): Usuario `maria` | Clave `maria123`
- **Auditor** (Solo lectura y descarga): Usuario `pedro` | Clave `pedro123`

---

## 🗃️ Diccionario de Datos

Para conocer en detalle el diseño de las bases de datos de `inventario` y `Talento_Humano`, así como las relaciones, vistas y disparadores de sincronización automática, consulte el [Diccionario de Datos](file:///c:/xampp/htdocs/Control_bines/diccionario_datos.md).

