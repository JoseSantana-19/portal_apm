# Guía General del Proyecto: Control de Bines (SysPort)

## Actualización 2026-07-27: filtro de ítems y centros de consumo

En **Ítems del Sistema**, el grupo contable funciona como filtro real: al activarlo, la lista, la navegación y la búsqueda muestran solamente los productos cuyo `grupo_id` corresponde al grupo seleccionado. El término de búsqueda se conserva al cambiar de grupo y el selector para copiar una plantilla también se limita al grupo elegido en el formulario.

Los grupos de centros de consumo continúan representando departamentos y los centros de consumo representan puestos o destinos de entrega. Sus campos **Funcionario representante** y **Funcionario responsable** ahora se alimentan exclusivamente con el personal activo de `Talento_Humano`. Se guardan `representante_id` y `funcionario_id` usando el ID oficial del empleado; los nombres de texto anteriores se mantienen como respaldo histórico.

Al escoger un centro de consumo en un egreso, el sistema completa el funcionario receptor por su ID, sin intentar relacionarlo por coincidencias aproximadas de nombre. La migración reproducible está en `database/migrations/inv_20260727_centros_consumo_personal.sql`.

## Actualización 2026-07-27: responsables desde Talento Humano

Se restauró la base oficial `Talento_Humano` desde `base_talentoHumano/Talento_Humano.bak`. La fuente contiene 619 funcionarios, de los cuales 217 están activos. Antes de restaurarla se generó el respaldo recuperable `backup/Talento_Humano_actual_pre_restore_20260727.bak`; también se conservó una copia verificada del archivo recibido en `backup/Talento_Humano_20260727_113936.bak`.

El inventario obtiene los responsables mediante `Talento_Humano.dbo.sp_th_buscar_responsables`, que permite consultar por nombre o identificación y devolver el ID original, nombres, apellidos, cargo, unidad, correo y estado. El selector de responsables carga solamente funcionarios activos y sigue apareciendo exclusivamente para activos fijos.

`inventario.dbo.inv_talento_personal` conserva un espejo local con el mismo ID de Talento Humano y los campos `cargo`, `area`, `correo`, `estado` y `fecha_sincronizacion`. El disparador `trg_sync_th_empleados_to_inventario` replica altas, modificaciones y bajas lógicas sin borrar responsables que formen parte de movimientos históricos. La definición reproducible está en `database/migrations/th_20260727_responsables_inventario.sql`.

El espejo local contiene 621 filas: las 619 oficiales y 2 registros de demostración anteriores que ya estaban referenciados por asignaciones. Esos dos registros se conservaron inactivos para no romper el historial y no aparecen en el selector.

## Actualización 2026-07-27: clasificación del inventario

El sistema distingue entre consumo corriente (`CC`), activo fijo (`AF`) y control administrativo histórico (`CA`). Los consumibles se controlan por cantidad y precio promedio y no tienen custodio permanente. Los activos fijos son bienes individualizados y son los únicos que pueden tener un funcionario responsable. `CA` permanece como clasificación independiente.

En Ítems del Sistema, el tipo se determina automáticamente desde el código de la categoría contable: `1.3.x` corresponde a consumo corriente y `1.4.x` a activo fijo. El campo `aplica_iva` es una bandera de sí/no; cuando está activo, el porcentaje se obtiene del período vigente y se conserva en los movimientos y cierres históricos.

La revisión histórica encontró 4.056 registros `AF` en `activos.DBF`: 1.976 vigentes y 2.080 dados de baja. También existen 3.691 registros `CA`. Los activos no estaban incluidos en la carga moderna original, razón por la que el tablero mostraba cero aunque sí existieran en FoxPro.

El importador `database/migrations/importar_activos_dbf.php` incorporó 4.056 activos sin duplicar códigos: 1.976 vigentes y 2.080 conservados como registros inactivos por baja. Los códigos departamentales antiguos (`DCOD`) se guardan como referencia hasta cruzarlos correctamente con Talento Humano y centros de consumo.

| Fuente | Información principal |
|---|---|
| `items.DBF` | Consumo corriente |
| `activos.DBF` | Activos fijos y control administrativo |
| `provee.DBF` | Proveedores y RUC |
| `centros.DBF` / `CENT_RES.DBF` | Centros de consumo |
| `cierres.DBF` / `cie_act.DBF` | Cortes históricos |

---

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

Para conocer en detalle el diseño de las bases de datos de `inventario` y `Talento_Humano`, así como las relaciones, vistas y disparadores de sincronización automática, consulte el [Diccionario de Datos](diccionario_datos.md).
