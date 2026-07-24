@echo off
setlocal

:: ===================================================================
:: SETUP_PROYECTO.bat - Portal APM v2.0
:: Autoridad Portuaria de Manta
::
:: Doble clic para iniciar el asistente de instalacion interactivo.
:: El asistente:
::   - Detecta XAMPP / WampServer / Laragon / PHP standalone
::   - Configura config/app.php y .env automaticamente
::   - Detecta SQL Server y ejecuta la base de datos
::   - Inicia el servidor y abre el navegador
:: Funciona en cualquier computador con Windows + PowerShell 5.1+
:: ===================================================================

title Portal APM v2.0 - Asistente de Instalacion

echo.
echo  ======================================================
echo    Portal APM v2.0 - Autoridad Portuaria de Manta
echo    Asistente de instalacion interactivo
echo  ======================================================
echo.

:: -- Verificar que PowerShell esta disponible -----------------------
where powershell >nul 2>&1
if %ERRORLEVEL% NEQ 0 (
    echo  [ERR] PowerShell no encontrado en PATH.
    echo        Instale PowerShell 5.1 o superior.
    echo.
    pause
    exit /b 1
)

:: -- Verificar que el script PS1 existe ----------------------------
if not exist "%~dp0SETUP_PROYECTO.ps1" (
    echo  [ERR] SETUP_PROYECTO.ps1 no encontrado en:
    echo        %~dp0
    echo.
    echo        Asegurese de ejecutar este .bat desde la raiz del proyecto.
    echo.
    pause
    exit /b 1
)

:: -- Forzar pagina de codigos UTF-8 para esta sesion ---------------
chcp 65001 >nul 2>&1

:: -- Ejecutar el asistente PowerShell -------------------------------
echo  Iniciando asistente ...
echo.

powershell -ExecutionPolicy Bypass -NoProfile -File "%~dp0SETUP_PROYECTO.ps1"
set PS_EXIT=%ERRORLEVEL%

echo.
if %PS_EXIT% EQU 0 (
    echo  ======================================================
    echo    Asistente finalizado.
    echo  ======================================================
) else (
    echo  ======================================================
    echo    [WARN] El asistente termino con codigo %PS_EXIT%.
    echo    Revise los mensajes anteriores.
    echo  ======================================================
)

echo.
echo  Recordatorio:
echo    - Credenciales por defecto:  admin / Apm2024*
echo    - Si cambia de equipo, vuelva a ejecutar este .bat:
echo      la configuracion se regenera automaticamente.
echo.
pause
endlocal
