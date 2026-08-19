# Instalación automática

El proyecto está preparado para Windows, XAMPP y Microsoft SQL Server.

## Requisitos

- XAMPP con PHP 7.4 o superior.
- Microsoft SQL Server 2014 o superior.
- Controladores Microsoft PHP para SQL Server.
- Extensiones PHP `pdo_sqlsrv`, `sqlsrv`, `mbstring`, `curl`, `openssl`, `fileinfo` y `json`.
- Apache con acceso de escritura a `logs`, `storage` y `public/uploads`.

FPDF ya está incluido en el proyecto. La lectura OCR carga Tesseract.js desde CDN y necesita conexión a Internet en el navegador.

## Instalación

Abra PowerShell en la carpeta del proyecto y ejecute:

```powershell
.\install.ps1
```

También puede hacer doble clic en `install.bat`.

Si faltan o son incompatibles los controladores PHP de SQL Server, ejecute PowerShell como administrador y use:

```powershell
.\install.ps1 -InstallPhpDrivers
```

El instalador descarga los binarios oficiales compatibles con la versión, arquitectura y modalidad TS/NTS de PHP, actualiza `php.ini` y valida que puedan cargarse. Después debe reiniciar Apache.

Para configurar una instalación nueva directamente desde la consola:

```powershell
.\install.ps1 -DbHost 'SERVIDOR\INSTANCIA' -DbUser 'usuario' -DbPassword 'clave' -AppUrl 'http://localhost/portuareaO/Control_binesC/'
```

El instalador valida PHP y sus extensiones, crea las carpetas necesarias, configura `.env`, restaura las bases ausentes y aplica las migraciones versionadas.

Para reemplazar deliberadamente las bases existentes con los respaldos:

```powershell
.\install.ps1 -ForceRestore
```

Esta última opción desconecta usuarios y reemplaza las bases actuales. Debe usarse únicamente cuando exista un respaldo recuperable.

## Crear un respaldo

```powershell
.\scripts\backup_database.ps1
```

El archivo se guarda en `backup`, utiliza `CHECKSUM` y se verifica automáticamente con SQL Server.
