"""
Dashboard Ejecutivo APM — Streamlit.

Corre como servicio en el mismo servidor (puerto 8501 por defecto) y se
embebe vía iframe desde el sistema PHP (central/dashboard/ejecutivo,
antes bit_dashboard_analitica.php).

Configuración de conexión por variables de entorno (ver .env.example),
para no tener el servidor/credenciales hardcodeados a una máquina de
desarrollo. Ver README.md en esta misma carpeta para cómo instalarlo
como servicio de Windows.
"""
import os
import streamlit as st
import pyodbc
import pandas as pd
import altair as alt

# Cargar variables de entorno desde .env si existe
if os.path.exists(".env"):
    with open(".env", "r", encoding="utf-8") as f:
        for line in f:
            line = line.strip()
            if line and not line.startswith("#") and "=" in line:
                key, val = line.split("=", 1)
                os.environ[key.strip()] = val.strip()

st.set_page_config(page_title="Dashboard Ejecutivo APM", layout="wide")

st.title("🏛️ Panel de Control Ejecutivo - APM")
st.caption("Indicadores de Alta Gerencia — Autoridad Portuaria de Manta")
st.markdown("---")


def conectar_base_datos():
    """
    Conexión configurable por variables de entorno. Si no están seteadas,
    cae a los valores por defecto de PortuariaDemo local (mismo patrón que
    el resto del sistema PHP: conexion/conexion.php).
    """
    driver = os.environ.get("APM_DB_DRIVER", "{ODBC Driver 17 for SQL Server}")
    server = os.environ.get("APM_DB_SERVER", "localhost\\SQLEXPRESS")
    database = os.environ.get("APM_DB_NAME", "PortuariaDemo")
    trusted = os.environ.get("APM_DB_TRUSTED", "yes")

    if trusted.lower() in ("yes", "true", "1"):
        conn_str = (
            f"Driver={driver};Server={server};Database={database};"
            "Trusted_Connection=yes;"
        )
    else:
        uid = os.environ.get("APM_DB_UID", "sa")
        pwd = os.environ.get("APM_DB_PWD", "")
        conn_str = (
            f"Driver={driver};Server={server};Database={database};"
            f"UID={uid};PWD={pwd};TrustServerCertificate=yes;"
        )

    return pyodbc.connect(conn_str)


PHP_BASE_URL = os.environ.get("APM_PHP_BASE_URL", "http://localhost/portuaria_demoV4")


@st.cache_data(ttl=60)
def cargar_datos():
    conn = conectar_base_datos()
    try:
        visitas = pd.read_sql(
            """
            SELECT v.id_visita, v.fecha_visita, v.hora_entrada, v.hora_salida,
                   v.tipo_visitante, v.id_persona, v.id_empresa, v.id_funcionario,
                   v.id_destino, v.id_motivo, v.id_nivel_incidente,
                   p.nombres, p.apellidos, p.nidentificacion, p.genero,
                   e.empresa AS empresa_nombre,
                   d.nombre AS destino_nombre,
                   m.descripcion AS motivo_descripcion,
                   f.nombre AS funcionario_nombres,
                   n.descripcion AS nivel_descripcion
            FROM dbo.bit_visitas v
            INNER JOIN dbo.bit_personas p ON v.id_persona = p.id_persona
            LEFT JOIN dbo.bit_empresas e ON v.id_empresa = e.id_empresa
            INNER JOIN dbo.bit_destinos d ON v.id_destino = d.id_destino
            INNER JOIN dbo.bit_motivos m ON v.id_motivo = m.id_motivo
            LEFT JOIN dbo.bit_funcionarios f ON v.id_funcionario = f.id_funcionario
            LEFT JOIN dbo.bit_niveles_incidente n ON v.id_nivel_incidente = n.id_incidentes
            """,
            conn,
        )
    finally:
        conn.close()

    visitas["fecha_visita"] = pd.to_datetime(visitas["fecha_visita"], errors="coerce")
    visitas["visitante"] = visitas["nombres"].fillna("") + " " + visitas["apellidos"].fillna("")
    visitas["dentro_del_edificio"] = visitas["hora_salida"].isna()
    return visitas


try:
    df = cargar_datos()
except Exception as e:
    st.error(
        "No se pudo conectar a la base de datos. Revisá las variables de entorno "
        "APM_DB_SERVER / APM_DB_NAME / APM_DB_TRUSTED (ver analytics/.env.example)."
    )
    st.exception(e)
    st.stop()

if df.empty:
    st.info("Todavía no hay visitas registradas.")
    st.stop()

# ============================================================================
# FILA 1 — KPIs generales
# ============================================================================
c1, c2, c3, c4 = st.columns(4)
c1.metric("Total de visitas", f"{len(df):,}".replace(",", "."))
c2.metric("Dentro del edificio ahora", int(df["dentro_del_edificio"].sum()))

criticos = df[df["nivel_descripcion"].astype(str).str.lower() == "crítico"]
c3.metric("Incidentes nivel Crítico", len(criticos))

con_genero = df["genero"].notna().sum()
c4.metric("Visitas con género registrado", f"{con_genero}/{len(df)}")

st.markdown("---")

# ============================================================================
# FILA 2 — Género / Nivel de incidente
# ============================================================================
col_genero, col_nivel = st.columns(2)

with col_genero:
    st.subheader("👤 Desglose por género")
    mapa_genero = {"M": "Masculino", "F": "Femenino"}
    genero_df = df["genero"].map(mapa_genero).fillna("Sin dato").value_counts().reset_index()
    genero_df.columns = ["Género", "Visitas"]
    if con_genero == 0:
        st.info(
            "Todavía no hay visitas con género capturado — se agregó el campo "
            "recientemente al formulario de registro (sql/24_PERSONAS_GENERO.sql). "
            "Va a ir llenándose con los ingresos nuevos."
        )
    chart_genero = (
        alt.Chart(genero_df)
        .mark_arc(innerRadius=60)
        .encode(theta="Visitas", color="Género", tooltip=["Género", "Visitas"])
    )
    st.altair_chart(chart_genero, use_container_width=True)

with col_nivel:
    st.subheader("⚠️ Incidentes por nivel")
    nivel_df = df["nivel_descripcion"].fillna("Sin nivel").value_counts().reset_index()
    nivel_df.columns = ["Nivel", "Visitas"]
    chart_nivel = (
        alt.Chart(nivel_df)
        .mark_bar()
        .encode(x="Visitas", y=alt.Y("Nivel", sort="-x"), tooltip=["Nivel", "Visitas"])
    )
    st.altair_chart(chart_nivel, use_container_width=True)

st.markdown("---")

# ============================================================================
# FILA 3 — Top personas / Top destinos
# ============================================================================
col_personas, col_destinos = st.columns(2)

with col_personas:
    st.subheader("🏆 Personas que más ingresan")
    top_personas = (
        df.groupby(["id_persona", "visitante", "nidentificacion"])
        .size()
        .reset_index(name="Visitas")
        .sort_values("Visitas", ascending=False)
        .head(10)
    )
    st.dataframe(
        top_personas[["visitante", "nidentificacion", "Visitas"]].rename(
            columns={"visitante": "Visitante", "nidentificacion": "Identificación"}
        ),
        use_container_width=True,
        hide_index=True,
    )

with col_destinos:
    st.subheader("🏢 Destinos que más reciben visitas")
    top_destinos = (
        df.groupby("destino_nombre").size().reset_index(name="Visitas").sort_values("Visitas", ascending=False).head(10)
    )
    chart_destinos = (
        alt.Chart(top_destinos)
        .mark_bar()
        .encode(x="Visitas", y=alt.Y("destino_nombre", sort="-x", title="Destino"), tooltip=["destino_nombre", "Visitas"])
    )
    st.altair_chart(chart_destinos, use_container_width=True)

st.markdown("---")

# ============================================================================
# FILA 4 — Demanda mensual / Funcionario más solicitado
# ============================================================================
col_mensual, col_funcionario = st.columns(2)

with col_mensual:
    st.subheader("📈 Demanda de ingreso por mes")
    df_mes = df.dropna(subset=["fecha_visita"]).copy()
    df_mes["mes"] = df_mes["fecha_visita"].dt.to_period("M").astype(str)
    mensual = df_mes.groupby("mes").size().reset_index(name="Visitas").sort_values("mes")
    chart_mensual = (
        alt.Chart(mensual)
        .mark_line(point=True)
        .encode(x="mes", y="Visitas", tooltip=["mes", "Visitas"])
    )
    st.altair_chart(chart_mensual, use_container_width=True)

with col_funcionario:
    st.subheader("🧑‍💼 Funcionario que más solicita/recibe visitas")
    df_func = df.dropna(subset=["funcionario_nombres"])
    if df_func.empty:
        st.info("Todavía no hay visitas con funcionario asignado (es un campo opcional).")
    else:
        top_func = (
            df_func.groupby("funcionario_nombres").size().reset_index(name="Visitas").sort_values("Visitas", ascending=False).head(10)
        )
        chart_func = (
            alt.Chart(top_func)
            .mark_bar()
            .encode(x="Visitas", y=alt.Y("funcionario_nombres", sort="-x", title="Funcionario"), tooltip=["funcionario_nombres", "Visitas"])
        )
        st.altair_chart(chart_func, use_container_width=True)

st.markdown("---")

# ============================================================================
# NOTA: "Fechas de ingreso de autoridades" (pedido del Ing. Zambrano) no se
# pudo implementar todavía porque el sistema no tiene ningún campo que
# distinga a una "autoridad" de un visitante común (ni en bit_personas ni en
# bit_visitas). Falta definir con el ingeniero qué marca a alguien como
# autoridad (¿un motivo específico? ¿un tipo de visitante nuevo? ¿un campo en
# bit_personas?) antes de poder armar este KPI sin inventar el criterio.
# ============================================================================
st.warning(
    "⚠️ Pendiente: \"Fechas de ingreso de autoridades\" — el sistema todavía no "
    "tiene forma de distinguir una autoridad de un visitante común. Hay que "
    "definir el criterio (motivo específico, tipo de visitante, campo nuevo en "
    "Personas) antes de poder armar este indicador."
)

# ============================================================================
# ZONA — Registro global de movimientos (tabla + consulta individual)
# ============================================================================
st.subheader("📋 Registro Global de Movimientos")

tabla = df[["id_visita", "visitante", "nidentificacion", "destino_nombre", "tipo_visitante", "hora_salida"]].copy()
tabla["hora_salida"] = tabla["hora_salida"].apply(lambda v: "Dentro del edificio" if pd.isna(v) else str(v))
tabla.columns = ["ID Visita", "Visitante", "Identificación", "Destino", "Tipo", "Salida"]
st.dataframe(tabla.sort_values("ID Visita", ascending=False), use_container_width=True, hide_index=True, height=350)

st.markdown("---")
st.subheader("🔍 Consulta Individual de Registros Específicos")

id_seleccionado = st.selectbox(
    "Seleccione el ID de visita de referencia para la auditoría:",
    options=df["id_visita"].sort_values(ascending=False).unique(),
)

if st.button("📄 Generar Ficha Ejecutiva"):
    fila = df[df["id_visita"] == id_seleccionado].iloc[0]

    st.success(f"### Ficha de Control Ejecutivo - Registro #{id_seleccionado}")

    fc1, fc2 = st.columns(2)
    with fc1:
        st.markdown(f"**👤 Visitante:** {fila['visitante']}")
        st.markdown(f"**🪪 Identificación:** {fila['nidentificacion']}")
        st.markdown(f"**🏢 Destino Asignado:** {fila['destino_nombre']}")
        st.markdown(f"**🚗 Tipo de Entrada:** {fila['tipo_visitante']}")
    with fc2:
        st.markdown(f"**📅 Fecha de Ingreso:** {fila['fecha_visita']}")
        st.markdown(f"**🕒 Hora de Entrada:** {fila['hora_entrada']}")
        salida = "Dentro del edificio" if pd.isna(fila["hora_salida"]) else str(fila["hora_salida"])
        st.markdown(f"**🚪 Hora de Salida:** {salida}")
        st.markdown(f"**📝 Motivo Detallado:** {fila['motivo_descripcion']}")

    # Ruta MVC real (antes apuntaba a bit_consulta_visitas.php, que ya no existe).
    url_php = f"{PHP_BASE_URL}/bitacoras/visita/detalle?id={id_seleccionado}"
    st.markdown(f"[🔗 Abrir expediente completo en el Portal Operativo PHP]({url_php})")
