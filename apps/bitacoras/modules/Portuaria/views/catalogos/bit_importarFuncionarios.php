<?php
/**
 * Vista: Importar funcionarios desde CSV.
 * Variables disponibles: $pageTitle, $mensaje, $errores, $tieneCedula
 * Extraída de bit_importar_funcionarios.php (contenido puro, sin head/navbar/sidebar:
 * eso ya lo pone views/layouts/main.php).
 */
?>
<h1 class="h4 text-primary mb-3">Importar funcionarios desde CSV</h1>
<p class="text-muted small">Archivo exportado desde Visual FoxPro con columnas: <strong>cedula</strong>, <strong>apellidos</strong>, <strong>nombres</strong>. No se insertan duplicados (por cédula).</p>

<?php if (!$tieneCedula): ?>
    <div class="alert alert-warning">Ejecute el script <code>sql/agregar_cedula_funcionarios.sql</code> en la base de datos antes de importar.</div>
<?php endif; ?>

<?php foreach ($errores as $e): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($e); ?></div>
<?php endforeach; ?>
<?php if ($mensaje !== ''): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($mensaje); ?></div>
<?php endif; ?>

<div class="card shadow-sm border-0">
    <div class="card-body">
        <form method="post" enctype="multipart/form-data">
            <div class="mb-3">
                <label class="form-label fw-semibold">Archivo CSV</label>
                <input type="file" name="archivo_csv" class="form-control" accept=".csv,.txt" required>
                <div class="form-text">Primera línea: cabecera con cedula, apellidos, nombres. Separador: coma.</div>
            </div>
            <button type="submit" class="btn btn-primary"<?php if (!$tieneCedula) echo ' disabled'; ?>>Importar</button>
            <a href="dashboard" class="btn btn-outline-secondary">Volver</a>
        </form>
    </div>
</div>
