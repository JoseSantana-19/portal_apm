# Reglas y Estándares para el Sistema de Inventario

## 1. Uso Obligatorio de Prefijos

Todas las tablas, archivos, modelos, controladores, vistas, scripts y demás componentes del sistema deben utilizar prefijos obligatoriamente para mantener el orden y facilitar el mantenimiento del proyecto.

Los prefijos dependerán del módulo o funcionalidad correspondiente.

### Ejemplos

```sql id="0wo57f"
inv_productos
th_movimientos
bit_accesos
acc_usuarios
```

---

## 2. Compatibilidad Tecnológica Obligatoria

El sistema debe ser totalmente compatible con la infraestructura tecnológica de la institución.

### Requisitos Obligatorios

* Compatibilidad con:

  * SQL Server Enterprise 2014
* El desarrollo debe realizarse utilizando:

  * PHP puro
* No se permite utilizar:

  * Laravel
  * Frameworks que alteren la compatibilidad del proyecto

### Recomendaciones

* Utilizar consultas compatibles con SQL Server 2014.
* Evitar funciones exclusivas de versiones modernas de SQL Server.
* Mantener compatibilidad con drivers SQLSRV o PDO_SQLSRV.

---

## 3. Reglas para Manejo de Errores

El sistema debe implementar registro de errores obligatoriamente.

### Reglas

* Todos los errores críticos deben registrarse en archivos `.txt`.
* Los logs deben almacenarse organizadamente por fecha o módulo.
* Los errores no deben mostrarse directamente al usuario final.
* Registrar:

  * Fecha
  * Hora
  * Usuario
  * Módulo
  * Descripción del error

### Ejemplo

```bash id="f01g0n"
/logs/error_2026_05_21.txt
```

### Ejemplo de Registro

```txt id="6h9fza"
[2026-05-21 10:45:12]
Usuario: admin
Modulo: inventario
Error: conexion fallida con base de datos
```

---

## 4. Reglas para el Menú del Sistema

El menú principal debe ser reutilizable y centralizado.

### Reglas Obligatorias

* El menú debe estar en un único archivo independiente.
* Debe cargarse o incluirse en cada formulario o módulo.
* No duplicar código del menú en múltiples archivos.
* Cualquier cambio del menú debe realizarse desde un solo archivo.

### Objetivo

Facilitar mantenimiento y evitar modificaciones repetitivas en cada formulario.

### Ejemplo

```bash id="c1hkdi"
/includes/menu.php
```

### Ejemplo de Inclusión

```php id="z90kq5"
include('includes/menu.php');
```

---

## 5. Reglas para Tablas

### Convenciones Generales

* Todos los nombres deben estar en minúsculas.
* Usar `_` para separar palabras.
* No usar espacios ni caracteres especiales.
* El nombre debe describir claramente la función de la tabla.
* Todas las tablas deben tener prefijo.

### Ejemplos Correctos

```sql id="9w70u3"
inv_productos
inv_stock
th_movimientos
bit_login_usuarios
```

### Ejemplos Incorrectos

```sql id="qmn56m"
Productos
InventarioProductos
tabla_stock
usuariosSistema
```

---

## 6. Reglas para Campos

### Convenciones

* Claves primarias:

```sql id="0z36dy"
id_producto
id_usuario
id_movimiento
```

* Claves foráneas:

```sql id="e6gpc4"
id_categoria
id_bodega
id_cliente
```

* Fechas:

```sql id="8h83m5"
fecha_creacion
fecha_actualizacion
fecha_movimiento
```

* Estados:

```sql id="y1n98x"
estado
activo
eliminado
```

---

## 7. Reglas para Archivos

Todos los archivos deben respetar el mismo prefijo del módulo correspondiente.

### Backend

```bash id="t2ccdk"
inv_productos.controller.php
inv_productos.model.php
inv_productos.routes.php
```

### Frontend

```bash id="z7ajpz"
inv_productos.page.php
inv_productos.service.php
inv_productos.styles.css
```

### SQL

```bash id="m0n7xw"
inv_productos.sql
th_movimientos.sql
bit_logs.sql
```

---

## 8. Reglas para Bitácoras

Toda acción importante debe registrarse en tablas de bitácora.

### Acciones Obligatorias

* Inicio de sesión
* Cierre de sesión
* Creación de registros
* Edición de registros
* Eliminación de registros
* Ajustes de inventario
* Movimientos de stock
* Errores críticos

### Ejemplos

```sql id="j27b0x"
bit_movimientos_sistema
bit_errores
bit_acceso_usuarios
```

---

## 9. Reglas para Tablas Históricas

Las tablas históricas almacenan cambios o movimientos realizados.

### Ejemplos

```sql id="u5drad"
th_movimientos_inventario
th_historial_precios
th_cambios_stock
```

### Reglas

* Nunca eliminar registros históricos.
* Todas deben tener:

```sql id="vzc6pb"
fecha_registro
usuario_registro
```

---

## 10. Reglas de Documentación

Cada modificación realizada debe documentarse inmediatamente.

### Obligatorio Documentar

* Nuevas tablas
* Nuevos endpoints
* Nuevos módulos
* Cambios en lógica de negocio
* Migraciones
* Errores solucionados
* Cambios de estructura

### Documentos Recomendados

| Documento      | Uso                     |
| -------------- | ----------------------- |
| `CHANGELOG.md` | Historial de cambios    |
| `README.md`    | Explicación general     |
| `DATABASE.md`  | Documentación de tablas |
| `API.md`       | Endpoints y servicios   |
| `RULES.md`     | Reglas del proyecto     |

---

## 11. Estructura Recomendada del Proyecto

```bash id="9qxk5m"
/project
│
├── backend
│   ├── controllers
│   ├── models
│   ├── routes
│   ├── services
│   ├── middlewares
│   └── logs
│
├── frontend
│   ├── pages
│   ├── components
│   ├── services
│   └── layouts
│
├── includes
│   └── menu.php
│
├── database
│   ├── migrations
│   ├── seeds
│   └── scripts
│
├── docs
│   ├── CHANGELOG.md
│   ├── DATABASE.md
│   ├── API.md
│   └── RULES.md
│
└── README.md
```

---

## 12. Reglas de Desarrollo

### Código

* Mantener funciones pequeñas y reutilizables.
* Evitar código duplicado.
* Usar nombres descriptivos.
* Separar lógica de negocio de acceso a datos.

### Base de Datos

* Evitar nombres ambiguos.
* Todas las tablas deben tener PK.
* Usar FK correctamente.
* No guardar datos redundantes innecesarios.

### Seguridad

* Nunca guardar contraseñas en texto plano.
* Validar entradas del usuario.
* Registrar accesos y errores.

---

## 13. Reglas para Commits

Formato recomendado:

```bash id="3iyt9m"
[TIPO] modulo: descripcion
```

### Ejemplos

```bash id="g8c8fh"
[ADD] inv_productos: se agrega modulo de productos
[FIX] th_movimientos: correccion de calculo de stock
[DOC] rules: actualizacion de reglas del sistema
```

---

## 14. Reglas para Versionado

Usar versionado semántico:

```bash id="3by6pn"
MAJOR.MINOR.PATCH
```

### Ejemplo

```bash id="aj5g6h"
1.0.0
1.1.0
1.1.1
```

---

## 15. Recomendaciones Adicionales

### Muy Recomendado

* Implementar migraciones automáticas.
* Usar entornos `dev`, `test` y `prod`.
* Crear respaldos automáticos.
* Implementar auditoría completa.
* Validar integridad del stock.
* Mantener documentación actualizada.
* Crear diagramas ER de la base de datos.
* Usar variables de entorno (`.env`).
* Estandarizar respuestas API.

---

## 16. Regla Final

Ningún cambio debe considerarse terminado si:

* No está documentado.
* No sigue la nomenclatura establecida.
* No tiene validaciones.
* No tiene registro en bitácora si aplica.
* No fue probado correctamente.
