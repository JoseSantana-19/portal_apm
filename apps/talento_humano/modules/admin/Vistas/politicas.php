<?php /* politicas.php – Vista: Políticas y Normativas */ ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Políticas y Normativas | Administración – APM</title>
    <meta name="description" content="Repositorio documental de políticas, reglamentos y normativas internas de la APM.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Space+Grotesk:wght@500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/variables.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/layout.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/toast.css">
    <style>
        .doc-card { background:#fff; border:1px solid var(--line); border-radius:var(--radius-md); padding:0; overflow:hidden; transition:transform .2s, box-shadow .2s; display:flex; flex-direction:column; }
        .doc-card:hover { transform:translateY(-3px); box-shadow:var(--shadow-md); }
        .doc-head { padding:14px 16px; display:flex; align-items:flex-start; gap:12px; border-bottom:1px solid var(--line); }
        .doc-icon { width:46px; height:46px; border-radius:12px; display:grid; place-items:center; font-size:1.4rem; flex-shrink:0; background:linear-gradient(135deg,#fee2e2,#fca5a5); color:#dc2626; }
        .doc-body { padding:14px 16px; flex:1; display:flex; flex-direction:column; gap:10px; }
        .doc-footer { padding:12px 16px; border-top:1px solid #f1f5f9; display:flex; align-items:center; justify-content:space-between; background:#fafbff; }
        .docs-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(280px,1fr)); gap:16px; }
        .vigente-badge { display:inline-flex; align-items:center; gap:5px; padding:3px 10px; border-radius:999px; font-size:.73rem; font-weight:600; }
        .vigente-si  { background:rgba(16,185,129,.12); color:#059669; border:1px solid rgba(16,185,129,.25); }
        .vigente-no  { background:rgba(107,114,128,.1);  color:#6b7280; border:1px solid rgba(107,114,128,.2); }
        .cat-badge { display:inline-flex; align-items:center; gap:5px; padding:3px 9px; border-radius:7px; font-size:.72rem; font-weight:600; background:#f0f7ff; color:var(--ocean-700); border:1px solid var(--line); }
        .modal-overlay { position:fixed; inset:0; background:rgba(10,19,30,.55); backdrop-filter:blur(4px); z-index:100; display:none; align-items:center; justify-content:center; }
        .modal-overlay.open { display:flex; }
        .modal-box { background:#fff; border-radius:var(--radius-lg); padding:28px; max-width:540px; width:90%; box-shadow:var(--shadow-lg); animation:floatIn .3s ease both; }
        .form-field { margin-bottom:14px; }
        .form-field label { display:block; font-size:.83rem; font-weight:600; color:var(--navy-900); margin-bottom:6px; }
        .form-field input, .form-field select, .form-field textarea { width:100%; padding:11px 14px; border:1px solid var(--line); border-radius:10px; font-size:.88rem; outline:none; background:#fff; transition:border .2s; }
        .form-field input:focus, .form-field select:focus, .form-field textarea:focus { border-color:var(--teal-500); box-shadow:0 0 0 3px rgba(18,180,199,.15); }
        .upload-zone { border:2px dashed var(--line); border-radius:12px; padding:24px; text-align:center; color:var(--ink-600); transition:border-color .2s; cursor:pointer; }
        .upload-zone:hover { border-color:var(--teal-500); }
        .upload-zone i { font-size:1.8rem; color:var(--ocean-600); display:block; margin-bottom:8px; }
        .metric-card--total2   { border-left:4px solid var(--ocean-700); }
        .metric-card--vigentes2{ border-left:4px solid #10b981; }
        .metric-card--desc2    { border-left:4px solid var(--teal-500); }
    </style>
</head>
<body>
<div id="overlay" class="overlay" onclick="closeSidebar()"></div>
<button class="sidebar-open-btn" id="sidebarOpenBtn" onclick="openSidebar()" title="Abrir menú lateral" aria-label="Abrir menú lateral">
    <i class="bi bi-layout-sidebar"></i>
</button>

<!-- Modal subir documento -->
<div class="modal-overlay" id="modalDoc">
    <div class="modal-box">
        <h3 style="margin:0 0 4px; color:var(--navy-900);"><i class="bi bi-file-earmark-plus" style="color:var(--ocean-600)"></i> Subir Documento</h3>
        <p style="margin:0 0 20px; font-size:.85rem; color:var(--ink-600);">Agregue manuales, reglamentos o memorandos al repositorio institucional.</p>
        <form id="formDoc" onsubmit="return guardarDoc(event)">
            <div class="form-field">
                <label>Título del documento</label>
                <input type="text" id="doc-titulo" placeholder="Ej: Reglamento Interno de Trabajo" required>
            </div>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px;">
                <div class="form-field">
                    <label>Categoría</label>
                    <select id="doc-cat">
                        <option>Normativa</option>
                        <option>Ética</option>
                        <option>Seguridad</option>
                        <option>Memorando</option>
                        <option>Tecnología</option>
                        <option>Recursos Humanos</option>
                        <option>Finanzas</option>
                    </select>
                </div>
                <div class="form-field">
                    <label>Versión</label>
                    <input type="text" id="doc-version" placeholder="Ej: 1.0, 2.1" required>
                </div>
            </div>
            <div class="form-field">
                <label>Descripción</label>
                <textarea rows="2" id="doc-desc" placeholder="Breve descripción del contenido del documento..."></textarea>
            </div>
            <div class="upload-zone" id="uploadZone" onclick="document.getElementById('docFile').click()">
                <i class="bi bi-cloud-arrow-up"></i>
                <p style="margin:0 0 4px; font-weight:600;">Seleccione el archivo</p>
                <small>Formatos aceptados: PDF, DOC, DOCX — Máx. 20 MB</small>
                <input type="file" id="docFile" accept=".pdf,.doc,.docx" style="display:none" required onchange="handleFile(this)">
            </div>
            <div id="fileSelected" style="display:none; margin-top:8px; padding:10px 14px; background:#f0fdf4; border:1px solid #bbf7d0; border-radius:10px; font-size:.85rem; color:#166534;">
                <i class="bi bi-check-circle"></i> <span id="fileName"></span>
            </div>
            <div style="display:flex; gap:10px; justify-content:flex-end; margin-top:16px;">
                <button type="button" class="btn btn-ghost" onclick="cerrarDoc()">Cancelar</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-upload"></i> Publicar documento</button>
            </div>
        </form>
    </div>
</div>

<div class="app">
    <?php require_once ROOT . '/shared/menu.php'; ?>

    <section class="content">
        <header class="topbar">
            <div class="topbar-left">
                <div class="brand">
                    <img src="<?= LOGO_URL ?>/logoapm.png" alt="Logo APM">
                    <div>
                        <h1>Autoridad Portuaria de Manta</h1>
                        <p>Administración y Seguridad</p>
                    </div>
                </div>
            </div>
            <div class="topbar-actions">
                <div class="search">
                    <i class="bi bi-search"></i>
                    <input type="search" id="docSearch" oninput="filtrarDocs()" placeholder="Buscar documento...">
                </div>
                <div class="icon-chip"><i class="bi bi-calendar-event"></i><span id="currentDate">--</span></div>
                <div class="user-pill"><span><?= htmlspecialchars($usuarioNombre ?? 'Administrador') ?></span><small>APM</small></div>
            </div>
        </header>

        <main class="main">
            <div class="content-shell">

                <!-- HERO -->
                <section class="hero" id="hero-politicas">
                    <div>
                        <div class="hero-kicker">Administración · Repositorio Documental</div>
                        <h2>Políticas y Normativas</h2>
                        <p>Repositorio centralizado de documentos institucionales: manuales, reglamentos, código de ética y memorandos. Acceso disponible para todo el personal.</p>
                        <div class="hero-actions">
                            <button class="btn btn-primary" id="btn-subir-doc" onclick="abrirDoc()">
                                <i class="bi bi-file-earmark-plus"></i> Subir Documento
                            </button>
                            <button class="btn btn-ghost" id="btn-exportar-docs">
                                <i class="bi bi-file-earmark-excel"></i> Exportar índice
                            </button>
                        </div>
                    </div>
                    <div class="metrics" style="grid-template-columns:repeat(3,1fr);">
                        <div class="metric-card metric-card--total2">
                            <div class="metric-label"><i class="bi bi-files"></i> Total documentos</div>
                            <div class="metric-value"><?= $total ?></div>
                            <div class="metric-foot">En el repositorio</div>
                        </div>
                        <div class="metric-card metric-card--vigentes2">
                            <div class="metric-label"><i class="bi bi-patch-check"></i> Vigentes</div>
                            <div class="metric-value"><?= $vigentes ?></div>
                            <div class="metric-foot">Documentos activos</div>
                        </div>
                        <div class="metric-card metric-card--desc2">
                            <div class="metric-label"><i class="bi bi-download"></i> Descargas</div>
                            <div class="metric-value"><?= $total_descargas ?></div>
                            <div class="metric-foot">Total histórico</div>
                        </div>
                    </div>
                </section>

                <!-- FILTROS POR CATEGORÍA -->
                <div class="card" style="padding:14px 20px; display:flex; flex-wrap:wrap; gap:8px; align-items:center;">
                    <span style="font-weight:600; font-size:.85rem; color:var(--navy-900);"><i class="bi bi-funnel"></i> Categoría:</span>
                    <button class="btn btn-primary" onclick="filtrarCat('')"  id="catBtn-all" style="padding:6px 12px; font-size:.78rem;">Todas</button>
                    <?php foreach ($categorias as $cat): ?>
                    <button class="btn btn-ghost" onclick="filtrarCat('<?= $cat ?>')" id="catBtn-<?= $cat ?>" style="padding:6px 12px; font-size:.78rem;"><?= $cat ?></button>
                    <?php endforeach; ?>
                </div>

                <!-- GRID DE DOCUMENTOS -->
                <div class="docs-grid" id="docsGrid">
                <?php foreach ($documentos as $doc): ?>
                    <div class="doc-card" data-cat="<?= $doc['categoria'] ?>" data-titulo="<?= strtolower($doc['titulo']) ?>">
                        <div class="doc-head">
                            <div class="doc-icon">
                                <i class="bi bi-file-earmark-pdf-fill"></i>
                            </div>
                            <div style="flex:1; min-width:0;">
                                <h4 style="margin:0 0 5px; font-size:.9rem; color:var(--navy-900); line-height:1.3;"><?= htmlspecialchars($doc['titulo']) ?></h4>
                                <div style="display:flex; flex-wrap:wrap; gap:5px;">
                                    <span class="cat-badge"><i class="bi bi-tag"></i><?= $doc['categoria'] ?></span>
                                    <span class="vigente-badge <?= $doc['vigente'] ? 'vigente-si' : 'vigente-no' ?>">
                                        <i class="bi <?= $doc['vigente'] ? 'bi-check-circle' : 'bi-archive' ?>"></i>
                                        <?= $doc['vigente'] ? 'Vigente' : 'Archivado' ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="doc-body">
                            <p style="font-size:.82rem; color:var(--ink-600); margin:0; flex:1;"><?= htmlspecialchars($doc['descripcion']) ?></p>
                            <div style="display:grid; grid-template-columns:1fr 1fr; gap:6px; font-size:.75rem; color:var(--ink-600);">
                                <span><i class="bi bi-code-square"></i> v<?= $doc['version'] ?></span>
                                <span><i class="bi bi-hdd"></i> <?= $doc['tamaño'] ?></span>
                                <span><i class="bi bi-calendar3"></i> <?= date('d/m/Y', strtotime($doc['fecha_subida'])) ?></span>
                                <span><i class="bi bi-download"></i> <?= $doc['descargas'] ?> descargas</span>
                            </div>
                        </div>
                        <div class="doc-footer">
                            <small style="color:var(--ink-600); font-size:.75rem;"><i class="bi bi-person"></i> <?= htmlspecialchars($doc['subido_por']) ?></small>
                            <div style="display:flex; gap:6px;">
                                <button class="btn btn-outline" style="padding:6px 10px; font-size:.78rem;" onclick="showToast('Visualizando: <?= addslashes(htmlspecialchars($doc['titulo'])) ?>', 'info')" title="Ver documento">
                                    <i class="bi bi-eye"></i> Ver
                                </button>
                                <button class="btn btn-primary" style="padding:6px 10px; font-size:.78rem;" onclick="showToast('Descargando: <?= addslashes(htmlspecialchars($doc['titulo'])) ?> (<?= $doc['tipo_archivo'] ?>)...', 'success')" title="Descargar">
                                    <i class="bi bi-download"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                </div>

            </div>
        </main>
    </section>
</div>

<div id="toastContainer" class="toast-container"></div>
<script src="<?= BASE_URL ?>/public/js/layout_sidebar.js"></script>
<script src="<?= BASE_URL ?>/public/js/toast.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const el = document.getElementById('currentDate');
    if (el) el.textContent = new Date().toLocaleDateString('es-EC', { weekday:'long', year:'numeric', month:'long', day:'numeric' });
    document.getElementById('btn-exportar-docs')?.addEventListener('click', () => showToast('Generando índice de documentos...', 'info'));
});
function abrirDoc() { document.getElementById('modalDoc').classList.add('open'); }
function cerrarDoc() { document.getElementById('modalDoc').classList.remove('open'); }
function guardarDoc(e) {
    e.preventDefault();
    showToast('Documento publicado exitosamente en el repositorio.', 'success');
    cerrarDoc();
    e.target.reset();
    document.getElementById('fileSelected').style.display = 'none';
    return false;
}
function handleFile(input) {
    if (input.files.length) {
        document.getElementById('fileName').textContent = input.files[0].name;
        document.getElementById('fileSelected').style.display = 'block';
    }
}
function filtrarCat(cat) {
    document.querySelectorAll('.doc-card').forEach(c => {
        c.style.display = !cat || c.dataset.cat === cat ? '' : 'none';
    });
    document.querySelectorAll('[id^="catBtn-"]').forEach(b => b.classList.replace('btn-primary','btn-ghost'));
    document.getElementById(!cat ? 'catBtn-all' : 'catBtn-'+cat)?.classList.replace('btn-ghost','btn-primary');
}
function filtrarDocs() {
    const q = document.getElementById('docSearch').value.toLowerCase();
    document.querySelectorAll('.doc-card').forEach(c => {
        c.style.display = c.dataset.titulo?.includes(q) ? '' : 'none';
    });
}
document.getElementById('modalDoc')?.addEventListener('click', e => {
    if (e.target === document.getElementById('modalDoc')) cerrarDoc();
});
</script>
<?php require_once ROOT . '/shared/footer_scripts.php'; ?>
</body>
</html>
