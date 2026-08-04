<style>
    body.bg-light { background-color: #f8f9fa !important; }
    .apm-master-card { border-radius: .75rem; border: none; }
    .tabla-catalogo th { font-size: 0.85rem; font-weight: 600; text-transform: uppercase; color: #495057; background-color: #f8f9fa; border-bottom: 2px solid #dee2e6; }
    .tabla-catalogo td { font-size: 0.9rem; vertical-align: middle; }
    .tabla-catalogo tbody tr:hover { background-color: rgba(13, 110, 253, 0.04); }
    .btn-outline-primary i, .btn-success i, .btn-outline-secondary i { margin-right: 0.25rem; }
    .dataTables_wrapper .dataTables_filter input { border-radius: 0.25rem; border: 1px solid #ced4da; padding: 0.25rem 0.5rem; }
</style>

<div class="container-fluid my-4">
    <div class="card shadow-sm border-0 apm-master-card">
        <div class="card-header py-3 d-flex flex-wrap justify-content-between align-items-center gap-2 bg-white border-bottom">
            <div>
                <h1 class="h4 text-primary mb-1">
                    <i class="bi <?php echo htmlspecialchars($infoCatalogo['icono'] ?? 'bi-table'); ?> me-1"></i>
                    <?php echo htmlspecialchars($infoCatalogo['titulo'] ?? 'Maestro'); ?>
                </h1>
                <p class="text-muted small mb-0"><?php echo htmlspecialchars($infoCatalogo['subtitulo'] ?? ''); ?></p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="bitacoras/visita/registrar" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Volver al registro</a>
                <button type="button" class="btn btn-success btn-sm btn-crear-registro" data-catalogo="<?php echo htmlspecialchars($catalogo); ?>"><i class="bi bi-plus-circle"></i> <?php echo htmlspecialchars($infoCatalogo['boton'] ?? 'Nuevo'); ?></button>
            </div>
        </div>

        <div class="card-body">
            <div class="alert alert-info small mb-3"><i class="bi bi-info-circle me-1"></i>Este maestro permite crear, listar, buscar, editar y desactivar registros usados en el control de visitas.</div>
            <div class="table-responsive" id="wrap-<?php echo htmlspecialchars($catalogo); ?>">
                <table class="table table-hover align-middle mb-0 tabla-catalogo w-100" id="tabla-<?php echo htmlspecialchars($catalogo); ?>" data-catalogo="<?php echo htmlspecialchars($catalogo); ?>">
                    <thead class="table-light"></thead><tbody></tbody>
                </table>
            </div>
            <button type="button" class="btn btn-outline-primary btn-sm btn-cargar-tabla mt-3 d-none" data-catalogo="<?php echo htmlspecialchars($catalogo); ?>"><i class="bi bi-eye"></i> Mostrar tabla</button>
        </div>
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

<script>window.APM_CATALOGO_DEFAULT = <?php echo json_encode($catalogo, JSON_UNESCAPED_UNICODE); ?>;</script>
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