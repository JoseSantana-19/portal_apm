# Dashboard Ejecutivo (Python / Streamlit)

Panel embebido en el **Portal APM** en la ruta **`/dashboard-ejecutivo`**
(módulo Portuaria, integrado de portuaria_demoV4), vía iframe a
`http://localhost:8501`.

## 1. Instalación (una sola vez)

```bat
cd analytics
pip install -r requirements.txt
```

Necesita el driver ODBC de SQL Server instalado en el servidor (**ODBC Driver
17 for SQL Server** — si Laragon/el equipo ya corre el resto del sistema PHP
contra SQL Server, probablemente ya lo tengas).

## 2. Configuración

La configuración vive en `.env` (ya apunta a `.\VICTUS`, base `PortuariaDemo`,
autenticación de Windows — igual que el resto del portal). Si tu servidor usa
otra configuración, ajustá los valores (ver `.env.example` para el detalle de
cada variable).

## 3. Probarlo manualmente

```bat
start_dashboard.bat
```

o directamente:

```bat
streamlit run dashboard.py --server.port 8501
```

Abrí `http://localhost:8501` — si carga el panel, andá al sistema PHP →
**Dashboard Ejecutivo** del menú y confirmá que se ve embebido ahí también.

## 4. Dejarlo como servicio (arranca solo con Windows)

Streamlit no tiene un modo "servicio de Windows" nativo. La forma más simple
y confiable es con **NSSM** (Non-Sucking Service Manager, gratis):

1. Descargar NSSM: https://nssm.cc/download
2. Abrir una consola como Administrador en la carpeta donde descomprimiste NSSM.
3. Ejecutar:
   ```bat
   nssm install ApmDashboardEjecutivo
   ```
4. En la ventana que se abre:
   - **Path**: la ruta completa a `streamlit.exe` (normalmente algo como
     `C:\Users\TU_USUARIO\AppData\Local\Programs\Python\Python3XX\Scripts\streamlit.exe`
     — corré `where streamlit` en consola para encontrarla exacta).
   - **Startup directory**: la carpeta `analytics` de este proyecto
     (ej. `C:\Users\Usuario\Desktop\PRACTICAS\portal_apm\analytics`).
   - **Arguments**: `run dashboard.py --server.port 8501 --server.headless true`
5. Click **Install service**.
6. Iniciar el servicio: `nssm start ApmDashboardEjecutivo` (o desde
   `services.msc`, buscá "ApmDashboardEjecutivo").

Con eso el dashboard arranca solo cada vez que prende el servidor, sin
depender de que alguien deje una consola abierta.

### Alternativa más simple (sin instalar nada extra)

Si no querés instalar NSSM, se puede usar el **Programador de tareas** de
Windows: crear una tarea que ejecute `start_dashboard.bat` "al iniciar sesión"
o "al iniciar el sistema". Es menos robusto que un servicio real (no se
reinicia solo si se cae el proceso), pero no requiere instalar nada.

## 5. Notas

- El puerto por defecto es **8501**. Si lo cambiás, actualizá también la
  constante `APM_DASHBOARD_EJECUTIVO_URL` en `config/app.php` del Portal APM
  (es el único lugar del sistema donde está escrita esa URL).
- El botón "Abrir expediente completo" dentro del dashboard apunta a
  `bitacoras/visita/detalle` en el sistema PHP — si el sistema PHP corre en
  otra URL/puerto, ajustá `APM_PHP_BASE_URL` en `.env`.
- Pendiente conocido: el KPI "Fechas de ingreso de autoridades" no está
  implementado — el sistema todavía no tiene ningún campo que distinga una
  autoridad de un visitante común. Queda señalado en el propio dashboard
  hasta que se defina el criterio.
