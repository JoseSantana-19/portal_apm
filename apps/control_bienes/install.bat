@echo off
setlocal
cd /d "%~dp0"
powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%~dp0install.ps1" %*
if errorlevel 1 (
  echo.
  echo La instalacion no pudo completarse. Revise el mensaje anterior.
  pause
  exit /b 1
)
echo.
pause
