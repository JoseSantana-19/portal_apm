# Habilitar la extensión PHP dbase (para leer rolmaes.DBF)

El archivo **rolmaes.DBF** está en la carpeta correcta, pero el sistema no puede leerlo porque la extensión **dbase** no está cargada en PHP.

## Opción 1: Usar la extensión dbase en Laragon (recomendado)

### Paso 1: Descargar la DLL de dbase para tu versión de PHP

1. Averigua tu versión y tipo de PHP:
   - En Laragon: **Menu → PHP → Version** (ej. PHP 8.3).
   - O crea un archivo `info.php` con `<?php phpinfo(); ?>` y abre en el navegador; busca "Thread Safety" (enabled = **TS**, disabled = **NTS**) y "Architecture" (x64 o x86).

2. Descarga la extensión dbase para Windows desde PECL:
   - https://pecl.php.net/package/dbase
   - Elige la versión compatible con tu PHP (ej. 7.1.0 para PHP 8).
   - En "Windows downloads" selecciona el archivo que coincida con:
     - PHP 8.3 → **php_8.3**
     - 64 bits → **x64**
     - Thread Safe → **ts** (Laragon suele usar TS)

   Si no hay build para tu versión exacta, puedes probar la más cercana (ej. 8.2 si no hay 8.3).

3. Descomprime el ZIP y copia **php_dbase.dll** a la carpeta de extensiones de PHP en Laragon:
   ```
   C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\ext\
   ```
   (Ajusta el nombre de la carpeta si tu versión es distinta: Menu → PHP → ver ruta.)

### Paso 2: Editar php.ini

1. En Laragon: **Menu → PHP → php.ini** (se abre el archivo).
2. Busca la sección de extensiones (líneas que empiezan con `extension=`).
3. Añade en una nueva línea:
   ```ini
   extension=dbase
   ```
   o, si no funciona:
   ```ini
   extension=php_dbase.dll
   ```
4. Guarda el archivo.

### Paso 3: Reiniciar Apache

En Laragon: **Menu → Stop All** y luego **Start All** (o solo reinicia Apache).

### Paso 4: Comprobar

Abre de nuevo en el navegador:

**http://localhost/portuaria_demo/test_rolmaes_dbf.php**

La sección "2. EXTENSIÓN PHP dbase" debe mostrar "Extensión dbase: cargada" y el resto del informe mostrará columnas y registros del DBF.

---

## Opción 2: Si no encuentras DLL para tu PHP

En algunas versiones de PHP para Windows la extensión dbase no tiene build precompilado. En ese caso puedes:

- Usar una versión de PHP en Laragon que sí tenga dbase (por ejemplo PHP 7.4 si existe build), o
- Comentar en el proyecto y se puede valorar leer el DBF con una librería en PHP puro (sin extensión).

---

## Resumen

| Qué                      | Dónde / Cómo                                      |
|--------------------------|---------------------------------------------------|
| Archivo DBF              | Ya está: `dbf/rolmaes.DBF`                        |
| Extensión dbase          | Descargar DLL de PECL → copiar a `php/ext/`       |
| Activar extensión        | En `php.ini`: `extension=dbase`                   |
| Reiniciar                | Laragon → Stop All → Start All                    |
| Probar                   | Abrir `test_rolmaes_dbf.php` en el navegador      |

