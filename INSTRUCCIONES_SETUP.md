# 🚀 Guía de Instalación Rápida y Configuración — Portal APM

Esta guía describe cómo pasar el proyecto **Portal APM** a un nuevo computador de manera completamente automatizada utilizando los scripts y el archivo SQL unificado que hemos preparado. El sistema es compatible con **SQL Server 2014** (o superior) y no requiere el uso de PDO (utiliza la extensión nativa `sqlsrv` de Microsoft).

---

## 📦 Archivos Creados en el Proyecto

En la carpeta raíz de tu proyecto se han añadido los siguientes archivos clave para la migración:

1. **`Z.BASES DE DATOS/PORTAL_APM_COMPLETO.sql`**: Script unificado que contiene la estructura completa de la base de datos (tablas, índices, funciones, vistas y procedimientos almacenados adaptados para SQL Server 2014 en adelante) junto con la inserción de todos los datos reales del menú, usuarios, empleados y permisos cruzados. La misma carpeta `Z.BASES DE DATOS/` guarda además los esquemas de referencia de Talento Humano, Bienes y Portuaria, todos verificados contra SQL Server 2014+.
2. **`SETUP_PROYECTO.ps1`**: Script de PowerShell que automatiza la detección de SQL Server, configura los archivos PHP de base de datos (`config/database.php` y `config/database_th.php`), valida la extensión de PHP, carga el SQL y arranca el servidor local.
3. **`SETUP_PROYECTO.bat`**: Lanzador por doble clic que ejecuta el script de PowerShell con los permisos y políticas correctos (`Bypass`).

---

## 💻 Pasos para Ejecutar en el Nuevo Computador

Sigue estas sencillas instrucciones en la máquina de destino:

### Paso 1: Copiar el Proyecto
1. Comprime la carpeta `portal_apm` del computador original.
2. Descomprime la carpeta en el nuevo computador (por ejemplo, en el Escritorio o en la raíz de tu servidor web local `C:\wamp64\www\` o `C:\xampp\htdocs\`).

### Paso 2: Ejecutar el Instalador Automático
1. Abre la carpeta `portal_apm`.
2. Haz **doble clic** sobre el archivo **`SETUP_PROYECTO.bat`** (o haz clic derecho sobre `SETUP_PROYECTO.ps1` y selecciona *"Ejecutar con PowerShell"*).
3. **Sigue las instrucciones en pantalla**:
   * **Detección de Instancia**: El script buscará automáticamente las instancias de SQL Server activas en tu equipo (ej. `SQLEXPRESS`, `MSSQLSERVER` o instancias personalizadas). Si encuentra varias, te dejará elegir; si no encuentra ninguna, te permitirá escribir el nombre manualmente.
   * **Autenticación**: Elige entre *Autenticación de Windows* (Recomendada, opción por defecto presionando Enter) o *Autenticación de SQL Server* (te pedirá usuario y contraseña si es necesario).
   * **Configuración PHP**: El instalador actualizará automáticamente los archivos `config/database.php` y `config/database_th.php` con los datos de conexión detectados.
   * **Instalación de Driver**: El script verificará si tienes la extensión `sqlsrv` activa. Si no la encuentra, buscará y copiará automáticamente el driver adecuado (por ejemplo, `php_sqlsrv_85_ts_x64.dll` si usas PHP 8.5) desde la carpeta de descargas de drivers a la carpeta `ext` de PHP y lo registrará en el archivo `php.ini`.
   * **Carga de Datos**: Creará la base de datos `PORTAL_APM` e importará toda la estructura y datos de prueba.
   * **Inicio del Servidor**: Te preguntará si deseas levantar de inmediato el servidor web local integrado de PHP en `http://localhost:8000`.

---

## 🔑 Credenciales y Cuentas de Prueba

Una vez finalizado el instalador, puedes acceder al sistema con las siguientes cuentas preconfiguradas:

* **URL de acceso**: [http://localhost:8000](http://localhost:8000)
* **Usuario Administrador**:
  * **Usuario**: `admin`
  * **Contraseña**: `admin` (se configurará como contraseña de acceso para pruebas)
* **Usuarios por Departamentos (Total: 21 usuarios preconfigurados)**:
  * **Auditoría**: `aauditor` (Ana Auditora)
  * **Asesoría Jurídica**: `cmendoza` (Director), `pvasquez` (Abogada Sr), `jperez` (Abogado Jr)
  * **Control de Acceso (Seguridad)**: `ksuarez` (Supervisora), `ltorres` (Operador)
  * **Trámites**: `apalma` (Tramitador)
  * **Videovigilancia (CCTV)**: `rchavez` (Analista)
  * **Secretaría**: `mlema` (Secretaria)
  * **Infraestructuras**: `fmora` (Jefe)
  * **Inspectores Portuarios**: `esalazar` (Supervisora), `dalvarado` (Inspector)
  * **Talento Humano**: `mquintero` (Jefa), `glopez` (Analista)
  * **Gerencia**: `acastro` (Gerente General), `mrecalde` (Asistente)
  * **Administración**: `rpita` (Directora), `jcuesta` (Analista)
  * **Finanzas**: `hmunoz` (Director), `nvera` (Analista)

---

## 🛠️ Requisitos Previos en el Nuevo Computador

Asegúrate de que la nueva máquina tenga instalado lo siguiente antes de ejecutar la aplicación:
1. **PHP 8.2 o superior** (a través de XAMPP, WampServer o standalone).
2. **Microsoft SQL Server 2014 o superior** (Edición Express, Developer o superior).
3. **Microsoft ODBC Driver para SQL Server** (Versión 17 o 18, necesaria para que el driver nativo de PHP funcione).
4. **Extensión nativa de SQL Server habilitada**:
   * En tu archivo `php.ini`, debe estar cargado el driver de SQLSRV correspondiente a la versión de PHP en uso (por ejemplo: `extension=php_sqlsrv_85_ts_x64.dll` para PHP 8.5 o `php_sqlsrv_82_ts_x64.dll` para PHP 8.2). El instalador automático (`SETUP_PROYECTO.ps1`) se encargará de realizar esta copia y registro si encuentra los drivers correspondientes en la carpeta de descargas.
   * *Nota: No es necesario tener habilitado PDO SQLSRV, ya que el proyecto utiliza la librería directa nativa `sqlsrv_*`.*
