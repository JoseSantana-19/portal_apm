@echo off
REM Inicia el Dashboard Ejecutivo (Streamlit) en el puerto 8501.
REM Uso manual: doble clic o "start_dashboard.bat" desde una consola.
REM Para que quede como SERVICIO (arranca solo con Windows), ver README.md.

cd /d "%~dp0"

if not exist ".env" (
    echo No se encontro .env - usando valores por defecto de PortuariaDemo local.
    echo Copia .env.example a .env si necesitas otra configuracion.
)

streamlit run dashboard.py --server.port 8501 --server.headless true
