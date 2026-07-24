# Diccionario de Datos - Sistema de Control de Bines (SysPort)

Este documento detalla la estructura lógica, tablas, columnas y relaciones de las bases de datos utilizadas por el sistema **Control de Bines (SysPort)** de la **Autoridad Portuaria de Manta (APM)**.

El sistema utiliza una arquitectura multi-driver y se conecta a dos bases de datos principales en el entorno de producción (SQL Server):
1. **`inventario`**: Contiene la información del catálogo de bienes, movimientos de bodega, usuarios, permisos, bitácora y configuración.
2. **`Talento_Humano`**: Almacena el expediente del personal portuario, cargos, unidades organizacionales y el historial laboral.

---

## 🗄️ 1. Base de Datos: `inventario`

Esta base de datos gestiona el inventario de bienes portuarios, transacciones de bodega e información administrativa del sistema.

### 📁 Módulo 1: Tablas Maestras de Control

#### 1.1 `inv_categorias`
Clasifica los bienes e insumos en grupos lógicos.
- **SQLite**: `inv_categorias`
- **SQL Server / PostgreSQL**: `inv_categorias`

| Columna | Tipo de Datos (SQL Server) | Restricciones | Descripción |
| :--- | :--- | :--- | :--- |
| `id` | `INT` | `PRIMARY KEY`, `IDENTITY` | Identificador único autoincremental de la categoría. |
| `nombre` | `NVARCHAR(255)` | `NOT NULL`, `UNIQUE` | Nombre descriptivo de la categoría (ej. Maquinaria Pesada, Contenedores). |
| `codigo` | `NVARCHAR(50)` | `NULL` | Código contable/jerárquico institucional (ej. `1.3.1.01.01.`). |
| `extra` | `NVARCHAR(MAX)` | `NULL` | Notas adicionales u observaciones. |

#### 1.2 `inv_zonas`
Registra las áreas geográficas o físicas dentro de la terminal portuaria.

| Columna | Tipo de Datos (SQL Server) | Restricciones | Descripción |
| :--- | :--- | :--- | :--- |
| `id` | `INT` | `PRIMARY KEY`, `IDENTITY` | Identificador único de la zona. |
| `nombre` | `NVARCHAR(255)` | `NOT NULL`, `UNIQUE` | Nombre del área física (ej. Terminal Sur, Muelle Norte). |
| `extra` | `NVARCHAR(MAX)` | `NULL` | Descripción o coordenadas de referencia. |

#### 1.3 `inv_estados`
Catálogo maestro de estados de solicitudes, procesos y operatividad de maquinaria.

| Columna | Tipo de Datos (SQL Server) | Restricciones | Descripción |
| :--- | :--- | :--- | :--- |
| `idestado` | `INT` | `PRIMARY KEY` | Código numérico del estado (ej. 111 para Operativo, 113 para Fuera de Servicio). |
| `descripcion` | `NVARCHAR(255)` | `NOT NULL`, `UNIQUE` | Etiqueta del estado (ej. APROBADO, OPERATIVO, EN MANTENIMIENTO). |
| `detalle` | `NVARCHAR(MAX)` | `NULL` | Explicación detallada del estado. |
| `estado` | `INT` | `DEFAULT 1` | Indicador de registro activo (1 = Activo, 0 = Inactivo). |

#### 1.4 `inv_marcas`
Fabricantes autorizados de los equipos portuarios.

| Columna | Tipo de Datos (SQL Server) | Restricciones | Descripción |
| :--- | :--- | :--- | :--- |
| `id` | `INT` | `PRIMARY KEY`, `IDENTITY` | Identificador único de la marca. |
| `nombre` | `NVARCHAR(255)` | `NOT NULL`, `UNIQUE` | Nombre del fabricante (ej. Kalmar, Konecranes, Terberg). |
| `extra` | `NVARCHAR(MAX)` | `NULL` | Datos adicionales de contacto o soporte del fabricante. |

#### 1.5 `inv_lineas`
Líneas navieras que operan en la terminal.

| Columna | Tipo de Datos (SQL Server) | Restricciones | Descripción |
| :--- | :--- | :--- | :--- |
| `id` | `INT` | `PRIMARY KEY`, `IDENTITY` | Identificador único de la línea naviera. |
| `nombre` | `NVARCHAR(255)` | `NOT NULL`, `UNIQUE` | Nombre comercial (ej. Maersk Line, MSC). |
| `extra` | `NVARCHAR(MAX)` | `NULL` | Información de contacto de la naviera. |

#### 1.6 `inv_unidades`
Unidades de medida para el control de stock de insumos.

| Columna | Tipo de Datos (SQL Server) | Restricciones | Descripción |
| :--- | :--- | :--- | :--- |
| `id` | `INT` | `PRIMARY KEY`, `IDENTITY` | Identificador de la unidad de medida. |
| `nombre` | `NVARCHAR(255)` | `NOT NULL`, `UNIQUE` | Nombre de la unidad (ej. Unidades, Galones, Kilogramos). |
| `extra` | `NVARCHAR(MAX)` | `NULL` | Abreviación de la unidad (ej. u., gl., kg.). |

#### 1.7 `inv_tipos_iva`
Tasas de IVA disponibles por ley.

| Columna | Tipo de Datos (SQL Server) | Restricciones | Descripción |
| :--- | :--- | :--- | :--- |
| `id` | `INT` | `PRIMARY KEY`, `IDENTITY` | Identificador único. |
| `nombre` | `NVARCHAR(255)` | `NOT NULL`, `UNIQUE` | Nombre del tipo de IVA (ej. IVA 15% (General)). |
| `tasa_iva` | `FLOAT` | `NOT NULL`, `UNIQUE` | Porcentaje numérico (ej. 15.0, 8.0, 0.0). |

---

### 📁 Módulo 2: Talento Humano (Sincronizado)

#### 2.1 `inv_talento_areas`
Áreas departamentales asignadas a los empleados para egresos de bodega.

| Columna | Tipo de Datos (SQL Server) | Restricciones | Descripción |
| :--- | :--- | :--- | :--- |
| `id` | `INT` | `PRIMARY KEY`, `IDENTITY` | ID único del departamento. |
| `nombre` | `NVARCHAR(255)` | `NOT NULL`, `UNIQUE` | Nombre de la división (ej. Operaciones Portuarias, Mantenimiento). |

#### 2.2 `inv_talento_personal`
Ficha espejo de los empleados autorizados en la base de datos `Talento_Humano` (Sincronizada por disparadores).

| Columna | Tipo de Datos (SQL Server) | Restricciones | Descripción |
| :--- | :--- | :--- | :--- |
| `id` | `INT` | `PRIMARY KEY` | ID de empleado correlativo en la base original `Talento_Humano`. |
| `nombre` | `NVARCHAR(255)` | `NOT NULL` | Concatenación de apellidos y nombres del funcionario. |
| `identificacion` | `NVARCHAR(50)` | `NOT NULL`, `UNIQUE` | Número de cédula de identidad o pasaporte. |

#### 2.3 `inv_talento_asignaciones`
Histórico de movimientos internos de personal entre áreas dentro del inventario.

| Columna | Tipo de Datos (SQL Server) | Restricciones | Descripción |
| :--- | :--- | :--- | :--- |
| `id` | `INT` | `PRIMARY KEY`, `IDENTITY` | Identificador de la asignación. |
| `personal_id` | `INT` | `FOREIGN KEY` | Enlace a [inv_talento_personal](#22-inv_talento_personal). |
| `area_id` | `INT` | `FOREIGN KEY` | Enlace a [inv_talento_areas](#21-inv_talento_areas). |
| `fecha_inicio` | `DATE` | `NOT NULL` | Fecha de ingreso al departamento. |
| `fecha_fin` | `DATE` | `NULL` | Fecha de traslado o salida del departamento. |

---

### 📁 Módulo 3: Catálogo, Inventario y Respaldos

#### 3.1 `inv_productos`
Catálogo de artículos maestros (SKU / Ficha de Insumos).

| Columna | Tipo de Datos (SQL Server) | Restricciones | Descripción |
| :--- | :--- | :--- | :--- |
| `id` | `INT` | `PRIMARY KEY`, `IDENTITY` | ID del producto maestro. |
| `nombre` | `NVARCHAR(255)` | `NOT NULL`, `UNIQUE` | Nombre comercial/institucional del producto. |
| `grupo_id` | `INT` | `FOREIGN KEY` | Enlace a la categoría [inv_categorias](#11-inv_categorias). |
| `unidad_id` | `INT` | `FOREIGN KEY` | Enlace a la unidad [inv_unidades](#16-inv_unidades). |
| `aplica_iva` | `INT` | `DEFAULT 1` | Bandera si el producto grava IVA (1 = Sí, 0 = No). |
| `codigo` | `NVARCHAR(50)` | `DEFAULT ''` | Código del producto maestro (`ITM-000001`). |
| `descripcion` | `NVARCHAR(MAX)` | `DEFAULT ''` | Descripción técnica del producto. |
| `ubicacion` | `NVARCHAR(MAX)` | `DEFAULT ''` | Percha, andén o almacén donde se ubica. |
| `existencia_min` | `FLOAT` | `DEFAULT 0` | Mínimo para alertas de reabastecimiento. |
| `existencia_max` | `FLOAT` | `DEFAULT 0` | Stock máximo recomendado. |
| `precio_promedio` | `FLOAT` | `DEFAULT 0` | Costo unitario promedio ponderado. |
| `existencia_actual` | `FLOAT` | `DEFAULT 0` | Stock total consolidado de existencias en bodega. |

#### 3.2 `inv_inventario`
Tabla principal de bienes y maquinarias activas (Bines, contenedores, grúas y stock físico).

| Columna | Tipo de Datos (SQL Server) | Restricciones | Descripción |
| :--- | :--- | :--- | :--- |
| `id` | `INT` | `PRIMARY KEY`, `IDENTITY` | Identificador único correlativo del bien. |
| `secuencial` | `NVARCHAR(50)` | `NOT NULL`, `UNIQUE` | Código secuencial autogenerado (`INV-00001`). |
| `nombre` | `NVARCHAR(255)` | `NOT NULL` | Descripción del bien físico. |
| `marca` | `NVARCHAR(255)` | `NOT NULL` | Fabricante del bien. |
| `categoria_id` | `INT` | `FOREIGN KEY` | Enlace a [inv_categorias](#11-inv_categorias). |
| `zona_id` | `INT` | `FOREIGN KEY` | Enlace a [inv_zonas](#12-inv_zonas). |
| `estado_id` | `INT` | `FOREIGN KEY` | Enlace a [inv_estados](#13-inv_estados) (`idestado`). |
| `responsable_id` | `INT` | `FOREIGN KEY`, `NULL` | Enlace a [inv_talento_personal](#22-inv_talento_personal). Custodio asignado. |
| `valor` | `FLOAT` | `DEFAULT 0.0` | Costo base neto (antes de impuestos). |
| `fecha_registro` | `DATE` | `NOT NULL` | Fecha de ingreso al inventario. |
| `observaciones` | `NVARCHAR(MAX)` | `NULL` | Bitácora técnica del bien o estado. |
| `activo` | `INT` | `DEFAULT 1` | Soft-delete (1 = Activo en listas, 0 = Dado de baja). |
| `cantidad` | `INT` | `DEFAULT 1` | Cantidad física disponible. |
| `producto_id` | `INT` | `FOREIGN KEY`, `NULL` | Enlace a la ficha maestra de catálogo [inv_productos](#31-inv_productos). |

#### 3.3 `inv_periodos`
Configuración de períodos contables / ejercicios fiscales.

| Columna | Tipo de Datos (SQL Server) | Restricciones | Descripción |
| :--- | :--- | :--- | :--- |
| `id` | `INT` | `PRIMARY KEY`, `IDENTITY` | Identificador del período contable. |
| `nombre` | `NVARCHAR(255)` | `NOT NULL`, `UNIQUE` | Denominación (ej. Periodo Actual (2026)). |
| `fecha_inicio` | `DATE` | `NOT NULL` | Inicio del ejercicio. |
| `fecha_fin` | `DATE` | `NOT NULL` | Cierre del ejercicio. |
| `estado` | `NVARCHAR(50)` | `DEFAULT 'activo'` | Estado del período (`activo` o `cerrado`). |

#### 3.4 `inv_valores_iva`
Tasa de IVA del período asignada en la foto histórica del corte.

| Columna | Tipo de Datos (SQL Server) | Restricciones | Descripción |
| :--- | :--- | :--- | :--- |
| `id` | `INT` | `PRIMARY KEY`, `IDENTITY` | Identificador único. |
| `tasa_iva` | `FLOAT` | `NOT NULL` | Porcentaje de IVA vigente para el período (ej. 15.0). |
| `periodo_id` | `INT` | `FOREIGN KEY` | Enlace a [inv_periodos](#33-inv_periodos). |

#### 3.5 `inv_respaldo_historico`
Archivo histórico de auditorías. Captura una foto inmutable del stock y responsables en el momento del cierre de período contable.

| Columna | Tipo de Datos (SQL Server) | Restricciones | Descripción |
| :--- | :--- | :--- | :--- |
| `id` | `INT` | `PRIMARY KEY`, `IDENTITY` | ID de registro del histórico. |
| `periodo_id` | `INT` | `FOREIGN KEY` | Enlace a [inv_periodos](#33-inv_periodos). Período de corte. |
| `item_id` | `INT` | `NOT NULL` | ID del bien físico original. |
| `secuencial` | `NVARCHAR(50)` | `NOT NULL` | Secuencial (`INV-xxxxx`) al momento del corte. |
| `nombre_historico` | `NVARCHAR(255)` | `NOT NULL` | Nombre del bien capturado. |
| `marca_historica` | `NVARCHAR(255)` | `NOT NULL` | Marca capturada. |
| `categoria_historica` | `NVARCHAR(255)` | `NOT NULL` | Nombre textual de la categoría al corte. |
| `zona_historica` | `NVARCHAR(255)` | `NOT NULL` | Nombre de la zona al corte. |
| `estado_historico` | `NVARCHAR(255)` | `NOT NULL` | Nombre descriptivo del estado al corte. |
| `responsable_historico`| `NVARCHAR(255)` | `NULL` | Nombre del custodio al corte. |
| `area_talento_historica`| `NVARCHAR(255)` | `NULL` | Departamento del custodio al corte. |
| `valor_historico` | `FLOAT` | `NOT NULL` | Costo base capturado. |
| `iva_aplicado` | `FLOAT` | `NOT NULL` | Tasa de IVA con la que se cerró la contabilidad. |
| `fecha_registro` | `DATE` | `NOT NULL` | Fecha original de registro del bien. |
| `fecha_corte` | `DATE` | `NOT NULL` | Fecha en que se ejecutó el cierre fiscal. |

---

### 📁 Módulo 4: Bodega (Transacciones)

#### 4.1 `inv_bod_ingresos`
Cabecera de transacciones de ingreso de insumos a bodega (Inflow).

| Columna | Tipo de Datos (SQL Server) | Restricciones | Descripción |
| :--- | :--- | :--- | :--- |
| `id` | `INT` | `PRIMARY KEY`, `IDENTITY` | Identificador de la transacción. |
| `secuencial` | `NVARCHAR(50)` | `NOT NULL`, `UNIQUE` | Código de ingreso autogenerado (`ING-00001`). |
| `proveedor` | `NVARCHAR(255)` | `NOT NULL` | Nombre o razón social del proveedor. |
| `fecha` | `DATE` | `NOT NULL` | Fecha del ingreso físico. |
| `observaciones` | `NVARCHAR(MAX)` | `NULL` | Detalles adicionales de la factura o entrega. |
| `responsable_id` | `INT` | `FOREIGN KEY` | Enlace a [inv_talento_personal](#22-inv_talento_personal). Operador que recibe. |
| `creado_por` | `NVARCHAR(255)` | `DEFAULT 'Admin'` | Cuenta de usuario que registró la entrada. |

#### 4.2 `inv_bod_ingresos_detalles`
Desglose detallado de los ítems recibidos en un ingreso de bodega.

| Columna | Tipo de Datos (SQL Server) | Restricciones | Descripción |
| :--- | :--- | :--- | :--- |
| `id` | `INT` | `PRIMARY KEY`, `IDENTITY` | Identificador del detalle. |
| `ingreso_id` | `INT` | `FOREIGN KEY` | Enlace a la cabecera [inv_bod_ingresos](#41-inv_bod_ingresos). |
| `item_id` | `INT` | `FOREIGN KEY` | Enlace al bien afectado [inv_inventario](#32-inv_inventario). |
| `cantidad` | `INT` | `NOT NULL` | Cantidad física ingresada (Suma al stock). |
| `valor_unitario` | `FLOAT` | `NOT NULL` | Costo unitario de adquisición. |

#### 4.3 `inv_bod_egresos`
Cabecera de transacciones de despacho o egreso de insumos (Outflow).

| Columna | Tipo de Datos (SQL Server) | Restricciones | Descripción |
| :--- | :--- | :--- | :--- |
| `id` | `INT` | `PRIMARY KEY`, `IDENTITY` | Identificador del egreso. |
| `secuencial` | `NVARCHAR(50)` | `NOT NULL`, `UNIQUE` | Código de egreso secuencial (`EGR-00001`). |
| `area_id` | `INT` | `FOREIGN KEY` | Departamento solicitante [inv_talento_areas](#21-inv_talento_areas). |
| `responsable_id` | `INT` | `FOREIGN KEY` | Funcionario que retira los insumos [inv_talento_personal](#22-inv_talento_personal). |
| `fecha` | `DATE` | `NOT NULL` | Fecha del egreso de bodega. |
| `motivo` | `NVARCHAR(MAX)` | `NOT NULL` | Justificación técnica del egreso. |
| `observaciones` | `NVARCHAR(MAX)` | `NULL` | Notas adicionales de despacho. |
| `creado_por` | `NVARCHAR(255)` | `DEFAULT 'Admin'` | Cuenta de usuario que ejecutó el egreso. |

#### 4.4 `inv_bod_egresos_detalles`
Desglose detallado de los ítems entregados en un egreso de bodega.

| Columna | Tipo de Datos (SQL Server) | Restricciones | Descripción |
| :--- | :--- | :--- | :--- |
| `id` | `INT` | `PRIMARY KEY`, `IDENTITY` | Identificador del detalle. |
| `egreso_id` | `INT` | `FOREIGN KEY` | Enlace a la cabecera [inv_bod_egresos](#43-inv_bod_egresos). |
| `item_id` | `INT` | `FOREIGN KEY` | Enlace al bien [inv_inventario](#32-inv_inventario). |
| `cantidad` | `INT` | `NOT NULL` | Cantidad despachada (Resta de la existencia). |

---

### 📁 Módulo 5: Administración y Seguridad del Sistema

#### 5.1 `inv_usuarios`
Cuentas autorizadas para ingresar a la plataforma.

| Columna | Tipo de Datos (SQL Server) | Restricciones | Descripción |
| :--- | :--- | :--- | :--- |
| `id` | `INT` | `PRIMARY KEY`, `IDENTITY` | Identificador único del usuario. |
| `secuencial` | `NVARCHAR(50)` | `NOT NULL`, `UNIQUE` | Código secuencial (`ACC-0001`). |
| `nombre` | `NVARCHAR(255)` | `NOT NULL` | Nombre completo del usuario administrativo. |
| `usuario` | `NVARCHAR(255)` | `NOT NULL`, `UNIQUE` | Nombre de login (ej. admin, juan, maria). |
| `contrasena` | `NVARCHAR(MAX)` | `NOT NULL` | Hash seguro de la contraseña (generado por `password_hash`). |
| `rol` | `NVARCHAR(50)` | `NOT NULL` | Perfil del usuario (`Administrador`, `Operador`, `Supervisor`, `Auditor`). |
| `activo` | `INT` | `DEFAULT 1` | Estado de acceso (1 = Permitido, 0 = Bloqueado). |

#### 5.2 `inv_permisos`
Matriz de privilegios de acceso basados en llaves de ruta para usuarios individuales.

| Columna | Tipo de Datos (SQL Server) | Restricciones | Descripción |
| :--- | :--- | :--- | :--- |
| `id` | `INT` | `PRIMARY KEY`, `IDENTITY` | Identificador único de regla de permiso. |
| `usuario_id` | `INT` | `FOREIGN KEY` | Enlace a [inv_usuarios](#51-inv_usuarios). |
| `route_key` | `NVARCHAR(255)` | `NOT NULL` | Identificador textual de la ruta permitida (ej. `maestros_guardar`). |
| **Index** | `UNIQUE` | `(usuario_id, route_key)` | Evita duplicidad de reglas para un mismo usuario. |

#### 5.3 `inv_secuenciales`
Contadores internos para la generación de códigos correlativos de módulos.

| Columna | Tipo de Datos (SQL Server) | Restricciones | Descripción |
| :--- | :--- | :--- | :--- |
| `modulo` | `NVARCHAR(50)` | `PRIMARY KEY` | Abreviatura del módulo (ej. `inv`, `ing`, `egr`, `bit`, `acc`, `itm`). |
| `prefijo` | `NVARCHAR(50)` | `NOT NULL` | Prefijo del código (ej. `INV-`, `ING-`, `EGR-`). |
| `ultimo_numero` | `INT` | `DEFAULT 0` | Último consecutivo utilizado. |

#### 5.4 `inv_parametros`
Configuraciones globales del sistema (ej. tiempo de caducidad de la sesión).

| Columna | Tipo de Datos (SQL Server) | Restricciones | Descripción |
| :--- | :--- | :--- | :--- |
| `clave` | `NVARCHAR(255)` | `PRIMARY KEY` | Nombre del parámetro (ej. `tiempo_inactividad`). |
| `valor` | `NVARCHAR(MAX)` | `NOT NULL` | Valor del parámetro (ej. `600` segundos). |
| `descripcion` | `NVARCHAR(MAX)` | `NULL` | Utilidad del parámetro. |

#### 5.5 `inv_notificaciones`
Alertas del sistema y avisos persistentes.

| Columna | Tipo de Datos (SQL Server) | Restricciones | Descripción |
| :--- | :--- | :--- | :--- |
| `id` | `INT` | `PRIMARY KEY`, `IDENTITY` | ID de la notificación. |
| `tipo` | `NVARCHAR(50)` | `NOT NULL` | Tipo de alerta (ej. `danger`, `warning`, `info`, `success`). |
| `categoria` | `NVARCHAR(50)` | `NOT NULL` | Categoría (ej. `stock`, `seguridad`, `maestros`, `bodega`). |
| `titulo` | `NVARCHAR(255)` | `NOT NULL` | Encabezado corto de la alerta. |
| `mensaje` | `NVARCHAR(MAX)` | `NOT NULL` | Cuerpo descriptivo del aviso. |
| `secuencial` | `NVARCHAR(50)` | `NULL` | Código relacionado de referencia (ej. `INV-00008`, `ING-00002`). |
| `visto` | `INT` | `DEFAULT 0` | Indicador de lectura (0 = No leída, 1 = Leída). |
| `created_at` | `DATETIME` | `DEFAULT CURRENT_TIMESTAMP` | Fecha y hora en la que se generó la alerta. |

---

### 📁 Módulo 6: Auditorías y Logs del Sistema

#### 6.1 `inv_bitacora`
Log plano e histórico de operaciones del sistema.

| Columna | Tipo de Datos (SQL Server) | Restricciones | Descripción |
| :--- | :--- | :--- | :--- |
| `id` | `INT` | `PRIMARY KEY`, `IDENTITY` | Identificador único del log. |
| `secuencial` | `NVARCHAR(50)` | `NOT NULL`, `UNIQUE` | Código de auditoría (`BIT-000001`). |
| `tipo` | `NVARCHAR(50)` | `NOT NULL` | Naturaleza de la acción (ej. `SISTEMA`, `BODEGA`, `SEGURIDAD`). |
| `modulo` | `NVARCHAR(50)` | `NOT NULL` | Carpeta de módulo de origen (ej. `sys`, `usr`, `bin`). |
| `descripcion` | `NVARCHAR(MAX)` | `NOT NULL` | Explicación legible de la transacción o cambio. |
| `fecha` | `DATETIME` | `DEFAULT CURRENT_TIMESTAMP` | Estampa de tiempo exacta de la acción. |

#### 6.2 `inv_log_errores`
Almacenamiento persistente de excepciones del servidor y runtime crash diagnostics.

| Columna | Tipo de Datos (SQL Server) | Restricciones | Descripción |
| :--- | :--- | :--- | :--- |
| `id_error` | `INT` | `PRIMARY KEY`, `IDENTITY` | ID de log de error. |
| `id_usuario` | `INT` | `FOREIGN KEY`, `NULL` | Usuario que ejecutó la acción (si estaba logeado). |
| `modulo` | `NVARCHAR(50)` | `DEFAULT 'sys'` | Módulo donde ocurrió. |
| `accion` | `NVARCHAR(255)` | `NULL` | Método controlador implicado. |
| `tipo_error` | `NVARCHAR(50)` | `DEFAULT 'Error'` | Clasificación del error. |
| `descripcion` | `NVARCHAR(MAX)` | `NOT NULL` | Exception stack trace o descripción del problema. |
| `archivo_origen` | `NVARCHAR(255)` | `NULL` | Ruta del archivo PHP afectado. |
| `linea_origen` | `INT` | `DEFAULT 0` | Número de línea física del fallo. |
| `nivel` | `NVARCHAR(50)` | `DEFAULT 'ERROR'` | Criticidad (WARNING, ERROR, FATAL). |
| `entorno` | `NVARCHAR(50)` | `DEFAULT 'development'`| Entorno de ejecución (`development`, `production`). |
| `ip_cliente` | `NVARCHAR(50)` | `NULL` | Dirección IP de red del solicitante. |
| `fecha_registro` | `DATETIME` | `DEFAULT CURRENT_TIMESTAMP` | Marca de tiempo del fallo. |

#### 6.3 `inv_log_eventos`
Auditoría técnica avanzada detallando la respuesta de las operaciones.

| Columna | Tipo de Datos (SQL Server) | Restricciones | Descripción |
| :--- | :--- | :--- | :--- |
| `id_evento` | `INT` | `PRIMARY KEY`, `IDENTITY` | Identificador del evento. |
| `id_usuario` | `INT` | `FOREIGN KEY`, `NULL` | Usuario ejecutor. Enlace a [inv_usuarios](#51-inv_usuarios). |
| `modulo` | `NVARCHAR(50)` | `DEFAULT 'sys'` | Componente ejecutado. |
| `accion` | `NVARCHAR(255)` | `NOT NULL` | Operación (ej. Login, Guardar Bien). |
| `descripcion` | `NVARCHAR(MAX)` | `NULL` | Resumen de los parámetros enviados. |
| `resultado` | `NVARCHAR(50)` | `DEFAULT 'OK'` | Despacho (`OK`, `FAIL`). |
| `ip_cliente` | `NVARCHAR(50)` | `NULL` | IP de red. |
| `fecha_registro` | `DATETIME` | `DEFAULT CURRENT_TIMESTAMP` | Estampa temporal. |

#### 6.4 `inv_proveedores`
Maestro extendido de proveedores externos.

| Columna | Tipo de Datos (SQL Server) | Restricciones | Descripción |
| :--- | :--- | :--- | :--- |
| `id` | `INT` | `PRIMARY KEY`, `IDENTITY` | Identificador de proveedor. |
| `nombre` | `NVARCHAR(255)` | `NOT NULL`, `UNIQUE` | Nombre comercial o institucional. |
| `ruc` | `NVARCHAR(50)` | `NOT NULL`, `UNIQUE` | RUC legal de la empresa (13 dígitos). |
| `extra` | `NVARCHAR(MAX)` | `NULL` | Dirección, teléfono y datos adicionales. |

#### 6.5 `inv_grupo_centros_consumo`
Estructura organizativa mayor para la asignación contable de bodega.

| Columna | Tipo de Datos (SQL Server) | Restricciones | Descripción |
| :--- | :--- | :--- | :--- |
| `id` | `INT` | `PRIMARY KEY`, `IDENTITY` | Identificador del grupo. |
| `codigo` | `NVARCHAR(50)` | `NOT NULL`, `UNIQUE` | Código institucional del grupo de consumo (ej. `04`). |
| `nombre` | `NVARCHAR(255)` | `NOT NULL`, `UNIQUE` | Nombre del grupo de centros. |
| `representante` | `NVARCHAR(255)` | `NULL` | Director o encargado principal. |

#### 6.6 `inv_centros_consumo`
Subcentro o área contable final de asignación física de materiales.

| Columna | Tipo de Datos (SQL Server) | Restricciones | Descripción |
| :--- | :--- | :--- | :--- |
| `id` | `INT` | `PRIMARY KEY`, `IDENTITY` | Identificador único del centro. |
| `grupo_id` | `INT` | `FOREIGN KEY` | Enlace al grupo superior [inv_grupo_centros_consumo](#65-inv_grupo_centros_consumo). |
| `codigo` | `NVARCHAR(50)` | `NOT NULL`, `UNIQUE` | Código oficial del centro (`0002`). |
| `nombre` | `NVARCHAR(255)` | `NOT NULL` | Descripción del centro de consumo. |
| `funcionario` | `NVARCHAR(255)` | `NOT NULL` | Encargado del consumo del área. |

---

## 🗄️ 2. Base de Datos: `Talento_Humano`

Esta base de datos es autónoma y almacena toda la ficha biográfica y contractual de los servidores públicos de la Autoridad Portuaria de Manta.

### 📁 Tablas Principales

#### 2.1 `th_empleados`
Expediente principal de cada servidor público del puerto.

| Columna | Tipo de Datos (SQL Server) | Restricciones | Descripción |
| :--- | :--- | :--- | :--- |
| `empleado_id` | `INT` | `PRIMARY KEY`, `IDENTITY` | Identificador único del servidor público. |
| `identificacion` | `NVARCHAR(50)` | `NOT NULL`, `UNIQUE` | Cédula de identidad o pasaporte. |
| `apellidos` | `NVARCHAR(255)` | `NOT NULL` | Apellidos del empleado. |
| `nombres` | `NVARCHAR(255)` | `NOT NULL` | Nombres del empleado. |
| `fecha_nacimiento`| `DATE` | `NOT NULL` | Fecha de nacimiento (se usa para alertas de cumpleaños). |
| `sexo` | `NVARCHAR(50)` | `NULL` | Género del funcionario. |
| `estado_civil` | `NVARCHAR(50)` | `NULL` | Estado civil. |
| `correo_institucional` | `NVARCHAR(255)` | `NULL` | Correo oficial corporativo. |
| `celular` | `NVARCHAR(50)` | `NULL` | Número móvil. |
| `direccion` | `NVARCHAR(MAX)` | `NULL` | Domicilio registrado. |
| `cargo_id` | `INT` | `FOREIGN KEY`, `NULL` | Enlace al cargo o perfil en [th_puestos](#23-th_puestos). |
| `unidad_id` | `INT` | `FOREIGN KEY`, `NULL` | Enlace a la unidad [th_unidades_organizacionales](#22-th_unidades_organizacionales). |
| `sueldo_rbu` | `FLOAT` | `NULL` | Remuneración Básica Unificada asignada. |
| `activo` | `INT` | `DEFAULT 1` | Estado laboral (1 = Activo, 0 = Inactivo/Liquidado). |

#### 2.2 `th_unidades_organizacionales`
Departamentos, áreas y direcciones operativas del puerto.

| Columna | Tipo de Datos (SQL Server) | Restricciones | Descripción |
| :--- | :--- | :--- | :--- |
| `unidad_id` | `INT` | `PRIMARY KEY`, `IDENTITY` | ID de la dirección o área. |
| `nombre_unidad` | `NVARCHAR(255)` | `NOT NULL` | Nombre de la oficina (ej. Dirección Administrativa). |
| `unidad_padre_id`| `INT` | `FOREIGN KEY`, `NULL` | Dirección jerárquica a la que responde (Relación reflexiva). |
| `sucedido_por_id`| `INT` | `FOREIGN KEY`, `NULL` | ID de la nueva unidad que absorbió a esta (Fusión de áreas). |
| `tipo_proceso` | `NVARCHAR(50)` | `NULL` | Tipo de proceso (ej. Sustantivo, Gobernante, Apoyo). |
| `fecha_fin` | `DATE` | `NULL` | Fecha de cese o disolución de la unidad. |
| `activo` | `INT` | `DEFAULT 1` | Estado de la unidad (1 = Vigente, 0 = Cerrada). |

#### 2.3 `th_puestos`
Catálogo de cargos y puestos orgánicos.

| Columna | Tipo de Datos (SQL Server) | Restricciones | Descripción |
| :--- | :--- | :--- | :--- |
| `puesto_id` | `INT` | `PRIMARY KEY`, `IDENTITY` | Identificador del puesto. |
| `codigo_puesto` | `NVARCHAR(50)` | `NOT NULL`, `UNIQUE` | Código secuencial del cargo. |
| `nombre_puesto` | `NVARCHAR(255)` | `NOT NULL` | Título oficial del cargo (ej. Especialista de Grúas). |
| `activo` | `INT` | `DEFAULT 1` | Estado (1 = Disponible, 0 = Eliminado). |

#### 2.4 `th_historial_laboral`
Historial de todos los cargos y traslados de área del empleado en su vida portuaria.

| Columna | Tipo de Datos (SQL Server) | Restricciones | Descripción |
| :--- | :--- | :--- | :--- |
| `historial_id` | `INT` | `PRIMARY KEY`, `IDENTITY` | Identificador único de traslado. |
| `empleado_id` | `INT` | `FOREIGN KEY` | Enlace al funcionario [th_empleados](#21-th_empleados). |
| `puesto_id` | `INT` | `FOREIGN KEY` | Enlace a [th_puestos](#23-th_puestos). |
| `unidad_id` | `INT` | `FOREIGN KEY` | Enlace a [th_unidades_organizacionales](#22-th_unidades_organizacionales). |
| `fecha_desde` | `DATE` | `NOT NULL` | Fecha de inicio en el puesto/área. |
| `fecha_hasta` | `DATE` | `NULL` | Fecha de término del puesto (Null indica asignación activa actual). |

#### 2.5 `th_acciones_personal`
Actas de movimientos de personal oficiales por decretos internos.

| Columna | Tipo de Datos (SQL Server) | Restricciones | Descripción |
| :--- | :--- | :--- | :--- |
| `accion_id` | `INT` | `PRIMARY KEY`, `IDENTITY` | ID de la acción. |
| `numero_accion` | `NVARCHAR(50)` | `NOT NULL`, `UNIQUE` | Número correlativo del documento formal. |
| `empleado_id` | `INT` | `FOREIGN KEY` | Empleado implicado [th_empleados](#21-th_empleados). |
| `fecha_elaboracion`| `DATE` | `NOT NULL` | Fecha de generación del acta. |
| `tipo_accion` | `NVARCHAR(100)` | `NOT NULL` | Motivo (ej. Ascenso, Traslado, Cese). |
| `estado_documento`| `NVARCHAR(50)` | `DEFAULT 'Borrador'` | Estado (`Aprobado`, `Firmado`, `Borrador`). |
| `fecha_rige_desde`| `DATE` | `NOT NULL` | Entrada en vigor. |
| `fecha_rige_hasta`| `DATE` | `NULL` | Vencimiento de la acción (si aplica). |
| `actual_puesto_id`| `INT` | `NULL` | Puesto anterior. |
| `actual_remuneracion`| `FLOAT` | `NULL` | Sueldo previo. |
| `propuesta_puesto_id`| `INT` | `NULL` | Nuevo puesto propuesto. |
| `propuesta_remuneracion`| `FLOAT` | `NULL`| Sueldo nuevo propuesto. |

---

## 🔄 3. Mecanismo de Integración y Sincronización

Para garantizar que el módulo de **Bienes e Inventarios** pueda consultar datos de personal sin interferir en el esquema de **Talento Humano**, se implementaron dos soluciones arquitectónicas a nivel de base de datos en SQL Server:

### 3.1 Vistas Cruzadas
En la base de datos `inventario`, se crearon vistas homónimas que leen mediante consultas vinculadas (*cross-database*) a las tablas de `Talento_Humano`. Esto permite consultar y mapear expedientes con una sola conexión PDO:
- `inventario.dbo.th_empleados` $\rightarrow$ apunta a $\rightarrow$ `Talento_Humano.dbo.th_empleados`
- `inventario.dbo.th_unidades_organizacionales` $\rightarrow$ apunta a $\rightarrow$ `Talento_Humano.dbo.th_unidades_organizacionales`
- `inventario.dbo.th_puestos` $\rightarrow$ apunta a $\rightarrow$ `Talento_Humano.dbo.th_puestos`
- `inventario.dbo.th_historial_laboral` $\rightarrow$ apunta a $\rightarrow$ `Talento_Humano.dbo.th_historial_laboral`
- `inventario.dbo.th_acciones_personal` $\rightarrow$ apunta a $\rightarrow$ `Talento_Humano.dbo.th_acciones_personal`
- `inventario.dbo.view_th_iddatosempledo` $\rightarrow$ points to $\rightarrow$ `Talento_Humano.dbo.view_th_iddatosempledo`
- `inventario.dbo.vw_th_reporte_historial_jerarquico` $\rightarrow$ points to $\rightarrow$ `Talento_Humano.dbo.vw_th_reporte_historial_jerarquico`
- `inventario.dbo.vw_th_acciones_resumen` $\rightarrow$ points to $\rightarrow$ `Talento_Humano.dbo.vw_th_acciones_resumen`

### 3.2 Sincronización Dinámica de Ficha Básica (Disparadores)
Para acelerar las consultas críticas de responsables en catálogos y resguardar la inmutabilidad física del inventario en cierres de períodos, se diseñó el disparador **`trg_sync_th_empleados_to_inventario`** en la base de datos de talento humano:

```sql
CREATE TRIGGER trg_sync_th_empleados_to_inventario
ON Talento_Humano.dbo.th_empleados
AFTER INSERT, UPDATE, DELETE
AS
BEGIN
    SET NOCOUNT ON;
    
    -- Sincronizar Eliminaciones (Limpia del espejo del inventario)
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
        SELECT empleado_id, apellidos + ' ' + nombres, identificacion
        FROM inserted;
        SET IDENTITY_INSERT inventario.dbo.inv_talento_personal OFF;
    END
    
    -- Sincronizar Modificaciones de Ficha
    IF EXISTS (SELECT 1 FROM inserted) AND EXISTS (SELECT 1 FROM deleted)
    BEGIN
        UPDATE p
        SET p.nombre = i.apellidos + ' ' + i.nombres,
            p.identificacion = i.identificacion
        FROM inventario.dbo.inv_talento_personal p
        JOIN inserted i ON p.id = i.empleado_id;
    END
END
```
De este modo, cuando un funcionario es contratado o edita su cédula/nombre en el panel de Talento Humano, los cambios se reflejan de inmediato y de forma inaudible en la tabla espejo del sistema de inventarios, sin necesidad de procesos de sincronización por lotes o scripts del cron en el servidor PHP.
