<style>
    body.bg-light { background-color: #f8f9fa !important; }
    .apm-master-card { border-radius: .75rem; border: none; }
    .nav-tabs .nav-link { font-weight: 500; color: #495057; }
    .nav-tabs .nav-link.active { color: #0d6efd; font-weight: 600; }
    .tabla-catalogo th { font-size: 0.85rem; font-weight: 600; text-transform: uppercase; color: #495057; background-color: #f8f9fa; border-bottom: 2px solid #dee2e6; }
    .tabla-catalogo td { font-size: 0.9rem; vertical-align: middle; }
    .tabla-catalogo tbody tr:hover { background-color: rgba(13, 110, 253, 0.04); }
    .btn-outline-primary i, .btn-success i { margin-right: 0.25rem; }
    .dataTables_wrapper .dataTables_filter input { border-radius: 0.25rem; border: 1px solid #ced4da; padding: 0.25rem 0.5rem; }
</style>

<div class="container-fluid my-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 text-primary mb-0">Panel de Maestros de Acceso</h1>
            <p class="text-muted small mb-0">Gestione los maestros que alimentan el registro de ingreso: personas, empresas, destinos, motivos y niveles de importancia.</p>
        </div>
    </div>

    <ul class="nav nav-tabs" id="catalogosTabs" role="tablist">
        <li class="nav-item" role="presentation"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-personas" type="button" role="tab">Personas</button></li>
        <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-empresas" type="button" role="tab">Empresas</button></li>
        <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-destinos" type="button" role="tab">Destinos</button></li>
        <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-motivos" type="button" role="tab">Motivos</button></li>
        <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-niveles_incidente" type="button" role="tab">Niveles de importancia</button></li>
    </ul>

    <div class="tab-content border border-top-0 bg-white p-3 rounded-bottom shadow-sm" id="catalogosTabContent">
        <?php
        $cats = ['personas' => 'Personas', 'empresas' => 'Empresas', 'destinos' => 'Destinos', 'motivos' => 'Motivos', 'niveles_incidente' => 'Niveles de importancia'];
        $first = true;
        foreach ($cats as $slug => $title):
        ?>
        <div class="tab-pane fade <?php echo $first ? 'show active' : ''; ?>" id="tab-<?php echo $slug; ?>" role="tabpanel">
            <div class="mb-3">
                <h2 class="h6 mb-2 d-none"><?php echo htmlspecialchars($title); ?></h2>
                <div class="d-flex flex-wrap align-items-center mt-2">
                    <button type="button" class="btn btn-outline-primary btn-sm btn-cargar-tabla me-2" data-catalogo="<?php echo $slug; ?>"><i class="bi bi-eye"></i> Mostrar tabla</button>
                    <button type="button" class="btn btn-success btn-sm btn-crear-registro" data-catalogo="<?php echo $slug; ?>"><i class="bi bi-plus-circle"></i> Nuevo</button>
                </div>
            </div>
            <div class="table-responsive mt-3" id="wrap-<?php echo $slug; ?>" style="display:none;">
                <table class="table table-hover align-middle mb-0 tabla-catalogo" id="tabla-<?php echo $slug; ?>" data-catalogo="<?php echo $slug; ?>">
                    <thead class="table-light"></thead><tbody></tbody>
                </table>
            </div>
        </div>
        <?php $first = false; endforeach; ?>
    </div>
</div>

<!-- Modal Genérico -->
<div class="modal fade" id="modalCatalogo" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <form id="formCatalogo" class="needs-validation" novalidate>
        <div class="modal-header"><h5 class="modal-title" id="modalCatalogoLabel">Gestión de catálogo</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div>
        <div class="modal-body">
          <input type="hidden" id="cat_action" name="action" value="create">
          <input type="hidden" id="cat_catalogo" name="catalogo" value="">
          <input type="hidden" id="cat_id" name="id" value="">
          <div id="catalogoFields" class="row g-3"></div>
          <div class="text-danger small mt-2" id="catalogoError" style="display:none;"></div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary">Guardar</button></div>
      </form>
    </div>
  </div>
</div>

<?php 
global $url_jquery_datatables, $url_datatables_js, $url_datatables_bootstrap5_js, $url_sweetalert2_js, $url_toast_js; 
?>
<script src="<?php echo htmlspecialchars($url_jquery_datatables ?? $GLOBALS['url_jquery_datatables'] ?? ''); ?>"></script>
<script src="<?php echo htmlspecialchars($url_datatables_js ?? $GLOBALS['url_datatables_js'] ?? ''); ?>"></script>
<script src="<?php echo htmlspecialchars($url_datatables_bootstrap5_js ?? $GLOBALS['url_datatables_bootstrap5_js'] ?? ''); ?>"></script>
<script src="<?php echo htmlspecialchars($url_sweetalert2_js ?? $GLOBALS['url_sweetalert2_js'] ?? ''); ?>"></script>
<script src="<?php echo htmlspecialchars($url_toast_js ?? $GLOBALS['url_toast_js'] ?? ''); ?>"></script>
<script src="<?php echo htmlspecialchars(($url_js_validaciones_ec ?? $GLOBALS['url_js_validaciones_ec'] ?? '/public/js/portuaria/validaciones_ecuador.js') . '?v=' . @filemtime(ROOT_PATH . '/public/js/portuaria/validaciones_ecuador.js')); ?>"></script>
<script src="<?php echo htmlspecialchars(($url_js_catalogos ?? $GLOBALS['url_js_catalogos'] ?? '/public/js/portuaria/catalogos.js') . '?v=' . @filemtime(ROOT_PATH . '/public/js/portuaria/catalogos.js')); ?>"></script>