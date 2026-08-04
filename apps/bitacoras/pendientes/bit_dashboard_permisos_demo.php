<?php
include_once __DIR__ . '/rutas/config_rutas.php';
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard Permisos Demo | APM</title>
    <link rel="stylesheet" href="<?php echo htmlspecialchars($url_bootstrap_css); ?>">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($url_datatables_css); ?>">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($url_icons_css); ?>">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($url_sweetalert2_css); ?>">
    <style>
      .apm-topbar { background:#003a70; color:#fff; }
      .apm-sidebar { background:#003a70; min-height:calc(100vh - 56px); }
      .apm-sidebar .nav-link { border-radius:.4rem; }
      .apm-sidebar .nav-link:hover { background:rgba(255,255,255,.12); }
    </style>
</head>
<body class="bg-light">
  <header class="apm-topbar d-flex align-items-center justify-content-between px-3" style="height:56px;">
    <strong>Autoridad Portuaria de Manta | Control de Visitas</strong>
    <span id="deptText" class="small"></span>
  </header>

  <div class="container-fluid">
    <div class="row">
      <aside class="col-12 col-md-3 col-lg-2 apm-sidebar text-white p-3">
        <ul id="menuGlobal" class="nav flex-column mb-2"></ul>

        <div id="adminWrap" style="display:none;">
          <button id="adminToggle" class="btn btn-sm btn-outline-light w-100 d-flex align-items-center justify-content-between mb-2">
            <span>Edificio Administrativo</span>
            <i id="adminArrow" class="bi bi-chevron-down"></i>
          </button>
          <ul id="menuAdmin" class="nav flex-column"></ul>
        </div>
      </aside>

      <main class="col-12 col-md-9 col-lg-10 p-4">
        <div class="card shadow-sm">
          <div class="card-header bg-white">
            <strong>per_menuopciones (mock SQL Server)</strong>
          </div>
          <div class="card-body">
            <div class="table-responsive">
              <table id="tablaPermisos" class="table table-striped table-hover align-middle mb-0">
                <thead class="table-light">
                  <tr>
                    <th>idusuario</th>
                    <th>menu_tram</th>
                    <th>opcion</th>
                    <th>item</th>
                    <th>estado</th>
                  </tr>
                </thead>
                <tbody id="tablaPermisosBody"></tbody>
              </table>
            </div>
          </div>
        </div>
      </main>
    </div>
  </div>

  <script src="<?php echo htmlspecialchars($url_jquery_datatables); ?>"></script>
  <script src="<?php echo htmlspecialchars($url_bootstrap_js); ?>"></script>
  <script src="<?php echo htmlspecialchars($url_datatables_js); ?>"></script>
  <script src="<?php echo htmlspecialchars($url_datatables_bootstrap5_js); ?>"></script>
  <script src="<?php echo htmlspecialchars($url_sweetalert2_js); ?>"></script>
  <script src="public/js/portuaria/dashboard_permisos_demo.js"></script>
</body>
</html>

