# Configuración de extensiones SQL Server (sqlsrv) en PHP 8.3

## Error que aparece

```
PHP Startup: Unable to load dynamic library 'php_sqlsrv' (tried: ...\ext\php_sqlsrv ... )
PHP Startup: Unable to load dynamic library 'php_pdo_sqlsrv' (tried: ...\ext\php_pdo_sqlsrv ... )
```

## Causa

En **php.ini** hay líneas que cargan extensiones con nombres antiguos (`php_sqlsrv`, `php_pdo_sqlsrv`). En PHP 8.3 las DLL se llaman `php_sqlsrv_83_ts_x64.dll` y `php_pdo_sqlsrv_83_ts_x64.dll`.

Si están **las dos** cosas (nombres nuevos y nombres viejos), PHP intenta cargar también las que no existen y muestra el warning.

## Solución

1. Abre el **php.ini** que usa Laragon:
   - Menú Laragon → PHP → php.ini  
   - O archivo: `C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.ini`

2. Busca las líneas de sqlsrv. Debes dejar **solo** estas dos (con el nombre completo de la DLL):

   ```ini
   extension=php_sqlsrv_83_ts_x64.dll
   extension=php_pdo_sqlsrv_83_ts_x64.dll
   ```

3. **Comenta o borra** estas líneas si aparecen:

   ```ini
   extension=php_sqlsrv
   extension=php_pdo_sqlsrv
   ```

   Puedes comentarlas con `;`:

   ```ini
   ;extension=php_sqlsrv
   ;extension=php_pdo_sqlsrv
   ```

4. Guarda el archivo y reinicia Apache (o “Stop All” y “Start All” en Laragon).

5. Comprueba: crea un `phpinfo.php` con `<?php phpinfo(); ?>` y en el navegador busca “sqlsrv”. Debe aparecer la sección del driver.

## Sigue fallando: comprobaciones paso a paso

### 1. Confirmar qué php.ini usa Apache

Puede que estés editando un php.ini y Apache use otro. Para ver el que realmente carga el servidor web:

1. Crea en tu proyecto un archivo `phpinfo.php` con solo:
   ```php
   <?php phpinfo();
   ```
2. Ábrelo en el navegador: `http://localhost/portuaria_demo/phpinfo.php`
3. Busca en la página **"Loaded Configuration File"**. Esa es la ruta del php.ini que debes editar.
4. Edita **ese** archivo (y no otro) con los cambios de extensiones.

### 2. Líneas duplicadas en el mismo php.ini

PHP lee **todo** el archivo. Si en otra parte del mismo php.ini aparece otra vez:

- `extension=php_sqlsrv`  
- `extension=php_pdo_sqlsrv`  

(sin el nombre completo `_83_ts_x64.dll`), seguirán saliendo los avisos.

- Abre el php.ini que indicó phpinfo.
- Usa Buscar (Ctrl+F) por **"sqlsrv"** y por **"pdo_sqlsrv"**.
- Debe haber **solo** estas dos líneas activas (sin `;`):
  ```ini
  extension=php_sqlsrv_83_ts_x64.dll
  extension=php_pdo_sqlsrv_83_ts_x64.dll
  ```
- Cualquier otra línea que cargue sqlsrv/pdo_sqlsrv (por ejemplo `extension=php_sqlsrv` o `extension=php_pdo_sqlsrv`) debe estar comentada con `;` o borrada.

### 3. Dependencias del sistema ("No se puede encontrar el módulo especificado")

El mensaje "No se puede encontrar el módulo especificado" a veces no es que falte la DLL de PHP, sino una **dependencia** de esa DLL (otra DLL del sistema). Para que sqlsrv funcione hacen falta:

- **Microsoft ODBC Driver for SQL Server** (64 bits), versión 17 o 18:  
  https://learn.microsoft.com/en-us/sql/connect/odbc/download-odbc-driver-for-sql-server  
  Sin esto, las extensiones pueden fallar al cargar.
- **Visual C++ Redistributable** (64 bits) reciente. Si actualizaste PHP o Windows, reinstala desde:  
  https://aka.ms/vs/17/release/vc_redist.x64.exe  

Después de instalar, reinicia Laragon (Stop All → Start All).

### 4. Reinicio completo

Tras tocar php.ini o instalar dependencias:

- Laragon → **Stop All**.
- Esperar unos segundos.
- **Start All**.

Si el error sigue apareciendo, indica el mensaje exacto que ves ahora (o envía captura) y en qué momento (al abrir una página, al ejecutar `php -v`, etc.).
