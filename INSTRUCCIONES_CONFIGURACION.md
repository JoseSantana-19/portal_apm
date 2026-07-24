# Guía de Configuración para Probar "Portal APM"

Esta guía explica detalladamente los pasos para configurar y ejecutar el proyecto **Portal APM** en tu máquina local.

---

## 🛠️ Paso 1: Requisitos Previos del Sistema

Antes de iniciar la aplicación, asegúrate de cumplir con los siguientes componentes en tu sistema (Windows):

1. **Servidor Web Local**: WampServer o XAMPP con **PHP 8.2 o superior**.
2. **Base de Datos**: Microsoft SQL Server instalado y corriendo (cualquier edición: Express, Developer o Standard).
3. **Controlador ODBC**: Debes tener instalado el [Microsoft ODBC Driver para SQL Server](https://learn.microsoft.com/sql/connect/odbc/download-odbc-driver-for-sql-server) (comúnmente versión 17 o 18).
4. **Extensión de PHP requerida**:
   Asegúrate de que la extensión **`sqlsrv`** esté activa en tu configuración de PHP (`php.ini`):
   ```ini
   extension=php_sqlsrv.dll
   ```
   > **Nota:** Este proyecto utiliza el driver nativo `sqlsrv` de Microsoft (funciones `sqlsrv_*`). **NO** necesitas la extensión `php_pdo_sqlsrv.dll`.
   
   *(En WampServer, puedes activarla haciendo clic izquierdo en el menú de la barra de tareas -> PHP -> PHP Extensions -> activar **php_sqlsrv**).*

---

## 🗄️ Paso 2: Importar la Base de Datos

1. Abre **SQL Server Management Studio (SSMS)** y conéctate a tu servidor local.
2. Crea una base de datos vacía llamada exactamente **`PORTAL_APM`**.
3. Abre el archivo **`PORTAL_APM_DESDE_CERO.sql`** (incluido en la carpeta raíz del proyecto) y ejecútalo para estructurar y poblar las tablas.

---

## ⚙️ Paso 3: Modificar Archivos de Configuración del Proyecto

Debes cambiar las rutas y credenciales del servidor de base de datos en los archivos de configuración para adaptarlos a tu máquina local:

### 1. Base de Datos Principal:
* **Archivo**: `portal_apm/config/database.php`
* **Cambio en Línea 8**: Reemplaza el nombre de la instancia por la tuya.
  ```php
  'host' => 'localhost\\SQLEXPRESS', // Cambia 'localhost\\VICTUS' por tu servidor
  ```
  *(Nota: Si usas la instancia predeterminada de SQL Server, simplemente escribe `'localhost'`).*
* **Cambio en Líneas 10 y 11**: Si usas Autenticación de SQL Server con usuario y contraseña (en lugar de Autenticación de Windows), reemplaza los valores `null` por tus credenciales correspondientes:
  ```php
  'username' => 'tu_usuario',
  'password' => 'tu_contraseña',
  ```

### 2. Base de Datos del Módulo de Talento Humano (Simulada):
* **Archivo**: `portal_apm/config/database_th.php`
* **Cambio en Línea 8, 10 y 11**: Aplica la misma configuración realizada en el archivo anterior:
  ```php
  'host' => 'localhost\\SQLEXPRESS', // O tu servidor correspondiente
  'username' => null,                // Tu usuario (o null si es Windows Auth)
  'password' => null,                // Tu contraseña (o null)
  ```

### 3. URL del Sitio Web:
* **Archivo**: `portal_apm/config/app.php`
* **Cambio en Línea 9**: Configura la URL base de tu servidor local:
  * Si utilizas la carpeta `www` o `htdocs` en WAMP/XAMPP:
    ```php
    'base_url' => 'http://localhost/portal_apm',
    ```
  * Si vas a levantar la aplicación mediante la consola integrada de PHP (puerto 8000), déjalo como:
    ```php
    'base_url' => 'http://localhost:8000',
    ```

---

## 🚀 Paso 4: Levantar y Ejecutar la Aplicación

Tienes dos formas de ejecutar y probar el proyecto:

### Opción A: Usando tu Servidor Web Local (WampServer / XAMPP)
1. Copia o mueve la carpeta completa `portal_apm` dentro de la carpeta pública de tu servidor:
   * **WampServer**: `C:\wamp64\www\portal_apm`
   * **XAMPP**: `C:\xampp\htdocs\portal_apm`
2. Abre tu navegador web y entra a:
   `http://localhost/portal_apm/` *(el archivo `.htaccess` redireccionará automáticamente hacia el login)*.

### Opción B: Usando el Servidor Integrado de PHP
1. Abre una consola de comandos (Terminal, Git Bash o PowerShell) en la carpeta raíz `portal_apm`.
2. Ejecuta el comando:
   ```bash
   php -S localhost:8000 -t public
   ```
3. Abre tu navegador web y entra a:
   `http://localhost:8000/`

---

## 🔧 Información Técnica

Este proyecto utiliza el **driver nativo `sqlsrv` de Microsoft** (funciones `sqlsrv_connect()`, `sqlsrv_query()`, etc.) para comunicarse con SQL Server. Esto significa:

- **Extensión requerida**: Solo `php_sqlsrv.dll`.
- **NO se usa PDO**: No se necesita `php_pdo_sqlsrv.dll`.
- **Autenticación de Windows**: Por defecto, la conexión usa "Trusted Connection" (Windows Authentication). Si tu SQL Server requiere usuario y contraseña, configúralo en los archivos `database.php` y `database_th.php`.
