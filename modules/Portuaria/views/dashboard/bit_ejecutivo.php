<?php
/**
 * Vista: Dashboard Ejecutivo (Python / Streamlit embebido).
 * Variables disponibles: $pageTitle, $dashboardUrl
 * Extraída de bit_dashboard_analitica.php (contenido puro, sin head/navbar/sidebar:
 * eso ya lo pone views/layouts/main.php).
 */
?>
<div class="row mb-3">
    <div class="col-12">
        <h1 class="h3 text-primary fw-bold mb-1">Módulo de Analítica Avanzada</h1>
        <p class="text-muted mb-0">Integración de entorno de ciencia de datos (Python) en entorno operativo</p>
    </div>
</div>

<div style="width: 100%; min-height: 1100px; overflow: hidden; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.08);">
    <iframe
        src="<?php echo htmlspecialchars($dashboardUrl); ?>"
        style="width: 100%; height: 1100px; border: none;"
        scrolling="no"
        allowfullscreen>
    </iframe>
</div>

<div class="alert alert-light border small mt-3" role="alert">
    <i class="bi bi-info-circle me-1"></i>
    Si el panel no carga, confirmá que el servicio de Python (Streamlit) esté corriendo en el servidor.
    Ver <code>analytics/README.md</code> para cómo iniciarlo o instalarlo como servicio de Windows.
</div>
