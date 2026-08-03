<?php
/**
 * Contenido del Portal — administra lo que se ve en la página pública
 * (index.php, visitantes sin sesión): carrusel de fondos, noticias con
 * imagen (carrusel dedicado) y consejos/novedades en texto (franja aparte).
 * Noticias y Consejos son independientes a propósito: no comparten tabla ni
 * semántica, cada una con su propio CRUD. Incluye vista previa real (iframe
 * de la página pública) para ver el resultado sin salir del panel.
 */
?>

<?php if ($success): ?>
<script>document.addEventListener('DOMContentLoaded', () => PortalAlert.success(<?= json_encode($success) ?>));</script>
<?php endif; ?>
<?php if ($error): ?>
<script>document.addEventListener('DOMContentLoaded', () => PortalAlert.error(<?= json_encode($error) ?>));</script>
<?php endif; ?>

<style>
.land {
    --g-bg: var(--surface-app); --g-bg-soft: var(--accent-app); --g-bd: var(--border-app);
    --g-shadow: var(--shadow-app); --sidebar-c: #075177;
}
.land .gx { background:var(--g-bg); border:1px solid var(--g-bd); border-radius:var(--radius-lg); box-shadow:var(--g-shadow); }
.land-eyebrow { font-size:var(--font-size-xs); font-weight:var(--font-weight-bold); letter-spacing:.16em; text-transform:uppercase; color:var(--color-primary); display:flex; align-items:center; gap:var(--sp-2); margin-bottom:var(--sp-2); }
.land-eyebrow::before { content:''; width:22px; height:2px; background:var(--color-primary); border-radius:2px; }

.gx-head { display:flex; align-items:center; gap:var(--sp-2); padding:var(--sp-3) var(--sp-4); border-bottom:1px solid var(--g-bd); font-weight:var(--font-weight-semibold); font-size:var(--font-size-sm); color:var(--color-text); }
.gx-head i { color:var(--color-primary); font-size:.8rem; }
.gx-head .cnt { margin-left:auto; font-size:10.5px; font-weight:var(--font-weight-semibold); color:var(--color-text-muted); background:var(--g-bg-soft); border:1px solid var(--g-bd); padding:1px 8px; border-radius:var(--radius-full); }
.gx-head .hint { font-weight:400; color:var(--color-text-muted); font-size:var(--font-size-xs); }
.gx-body { padding:var(--sp-4); }

.land-grid { display:grid; grid-template-columns:1fr 1fr; gap:var(--sp-4); align-items:start; margin-bottom:var(--sp-4); }
@media (max-width:960px) { .land-grid { grid-template-columns:1fr; } }

/* ── Imágenes de fondo ── */
.land-upload { display:flex; gap:var(--sp-2); align-items:center; margin-bottom:var(--sp-4); flex-wrap:wrap; }
.land-upload input[type=file] { flex:1; min-width:180px; font-size:var(--font-size-xs); }

.img-list { display:flex; flex-direction:column; gap:var(--sp-2); }
.img-row { display:flex; align-items:center; gap:var(--sp-3); padding:var(--sp-2); border:1px solid var(--g-bd); border-radius:var(--radius-md); background:var(--g-bg-soft); }
.img-row.off { opacity:.5; }
.img-thumb { width:64px; height:40px; border-radius:var(--radius-sm); object-fit:cover; flex-shrink:0; border:1px solid var(--g-bd); background:var(--color-surface-3); }
.img-row-actions { display:flex; align-items:center; gap:4px; margin-left:auto; }
.row-btn { display:inline-flex; align-items:center; justify-content:center; width:28px; height:28px; border-radius:var(--radius-sm); border:1px solid var(--g-bd); background:transparent; color:var(--color-text-muted); cursor:pointer; font-size:.72rem; transition:all var(--transition-fast); }
.row-btn:hover { background:var(--g-bg); color:var(--color-primary); border-color:var(--color-primary); }
.row-btn.danger:hover { color:var(--color-danger); border-color:var(--color-danger); }
.row-empty { text-align:center; padding:var(--sp-5); color:var(--color-text-muted); font-size:var(--font-size-sm); }

/* ── Noticias (con imagen, obligatoria) ── */
.news-add { display:flex; flex-direction:column; gap:var(--sp-2); margin-bottom:var(--sp-4); padding:var(--sp-3); border:1px dashed var(--g-bd); border-radius:var(--radius-md); }
.news-add-row2 { display:flex; gap:var(--sp-2); flex-wrap:wrap; align-items:center; }
.news-add-row2 input[type=url] { flex:1; min-width:160px; }

.news-file-btn { display:inline-flex; align-items:center; gap:6px; font-size:var(--font-size-xs); font-weight:var(--font-weight-semibold); color:var(--color-text-muted); border:1px solid var(--g-bd); border-radius:var(--radius-sm); padding:7px 12px; cursor:pointer; white-space:nowrap; transition:all var(--transition-fast); background:var(--g-bg-soft); }
.news-file-btn:hover { color:var(--color-primary); border-color:var(--color-primary); }
.news-file-btn.required { border-color:color-mix(in srgb, var(--color-primary) 45%, var(--g-bd)); }

.news-row { display:flex; flex-direction:column; gap:6px; padding:var(--sp-2); border:1px solid var(--g-bd); border-radius:var(--radius-md); background:var(--g-bg-soft); margin-bottom:var(--sp-2); }
.news-row.off { opacity:.5; }
.news-row form.edit-form { display:flex; flex-direction:column; gap:6px; }
.news-row-main { display:flex; align-items:center; gap:var(--sp-2); }
.news-row-main input[type=text] { flex:1; font-size:var(--font-size-sm); }
.news-row-extra { display:flex; align-items:center; gap:6px; flex-wrap:wrap; }
.news-row-extra input[type=url] { flex:1; min-width:140px; font-size:var(--font-size-xs); }
.news-row-actions { display:flex; justify-content:flex-end; gap:4px; }
.news-thumb { width:40px; height:40px; border-radius:var(--radius-sm); object-fit:cover; flex-shrink:0; border:1px solid var(--g-bd); background:var(--color-surface-3); }

/* ── Consejos y novedades (texto puro, sin imagen) ── */
.tip-add { display:flex; gap:var(--sp-2); flex-wrap:wrap; margin-bottom:var(--sp-4); padding:var(--sp-3); border:1px dashed var(--g-bd); border-radius:var(--radius-md); align-items:center; }
.tip-add input[type=text] { flex:2; min-width:220px; }
.tip-add input[type=url] { flex:1; min-width:160px; }

.tip-row { display:flex; align-items:center; gap:var(--sp-2); padding:var(--sp-2); border:1px solid var(--g-bd); border-radius:var(--radius-md); background:var(--g-bg-soft); margin-bottom:var(--sp-2); flex-wrap:wrap; }
.tip-row.off { opacity:.5; }
.tip-row form.edit-form { flex:1; display:flex; gap:6px; flex-wrap:wrap; min-width:220px; }
.tip-row input[type=text] { flex:2; min-width:160px; font-size:var(--font-size-sm); }
.tip-row input[type=url] { flex:1; min-width:140px; font-size:var(--font-size-xs); }
.tip-row-actions { display:flex; gap:4px; flex-shrink:0; }

/* ── Vista previa (iframe real de la página pública) ── */
.prev-browser { border-radius:var(--radius-lg); overflow:hidden; border:1px solid var(--g-bd); background:#0a1929; }
.prev-bar { display:flex; align-items:center; gap:8px; padding:9px 12px; background:#111827; }
.prev-bar .dot { width:10px; height:10px; border-radius:50%; display:inline-block; flex-shrink:0; }
.prev-bar .dot.r { background:#ef4444; } .prev-bar .dot.y { background:#f59e0b; } .prev-bar .dot.g { background:#22c55e; }
.prev-url { flex:1; background:rgba(255,255,255,.06); border-radius:999px; padding:5px 14px; font-size:11.5px; color:#cbd5e1; display:flex; align-items:center; gap:6px; margin-left:6px; overflow:hidden; white-space:nowrap; text-overflow:ellipsis; }
.prev-bar-actions { display:flex; gap:6px; flex-shrink:0; }
.prev-bar-actions .row-btn { border-color:rgba(255,255,255,.15); color:#cbd5e1; }
.prev-bar-actions .row-btn:hover { background:rgba(255,255,255,.08); color:#fff; border-color:rgba(255,255,255,.3); }
.prev-scale-wrap { position:relative; width:100%; overflow:hidden; background:#0a1929; }
.prev-scale-wrap.loading::after { content:'Cargando vista previa…'; position:absolute; inset:0; display:flex; align-items:center; justify-content:center; color:#94a3b8; font-size:12px; }
.prev-iframe { border:0; display:block; width:1440px; background:#0a1929; }
</style>

<div class="land">

<div style="margin-bottom:var(--sp-5);">
    <div class="land-eyebrow">Administración · Portal Público</div>
    <h2 class="page-title" style="margin:0;">Contenido del Portal</h2>
    <p class="page-subtitle" style="margin-top:4px;">
        Lo que ve cualquier visitante en <code style="font-size:var(--font-size-xs);background:color-mix(in srgb,var(--color-primary) 8%,transparent);color:var(--color-primary);padding:2px 6px;border-radius:var(--radius-sm);"><?= APP_URL ?>/</code> antes de iniciar sesión — carrusel de fondo, noticias con imagen y consejos en texto.
    </p>
</div>

<!-- Vista previa: iframe real de la página pública, no una réplica — 100% fiel -->
<div class="gx" style="margin-bottom:var(--sp-4);">
    <div class="gx-head">
        <i class="fa-solid fa-eye"></i> Vista previa en vivo
        <span class="hint">— es la página pública real, no una simulación</span>
    </div>
    <div class="gx-body">
        <div class="prev-browser">
            <div class="prev-bar">
                <span class="dot r"></span><span class="dot y"></span><span class="dot g"></span>
                <div class="prev-url"><i class="fa-solid fa-lock"></i> <?= htmlspecialchars(parse_url(APP_URL, PHP_URL_HOST) . (parse_url(APP_URL, PHP_URL_PATH) ?? ''), ENT_QUOTES, 'UTF-8') ?>/</div>
                <div class="prev-bar-actions">
                    <button type="button" class="row-btn" id="prevRefreshBtn" title="Actualizar vista previa"><i class="fa-solid fa-rotate-right"></i></button>
                    <a class="row-btn" href="<?= APP_URL ?>/" target="_blank" rel="noopener" title="Abrir en pestaña nueva" style="text-decoration:none;"><i class="fa-solid fa-arrow-up-right-from-square"></i></a>
                </div>
            </div>
            <div class="prev-scale-wrap loading" id="prevScaleWrap">
                <iframe id="prevIframe" class="prev-iframe" src="<?= APP_URL ?>/?preview=1" loading="lazy" title="Vista previa del portal público"></iframe>
            </div>
        </div>
    </div>
</div>

<div class="land-grid">
    <!-- Carrusel de fondos -->
    <div class="gx">
        <div class="gx-head"><i class="fa-solid fa-images"></i> Carrusel de Fondos <span class="cnt"><?= count($imagenes) ?></span></div>
        <div class="gx-body">
            <form method="POST" action="<?= APP_URL ?>/admin/landing/imagenes" enctype="multipart/form-data" class="land-upload">
                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                <input type="file" name="imagen" accept=".jpg,.jpeg,.png,.webp" required class="form-control">
                <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-upload"></i> Subir</button>
            </form>
            <span class="form-help" style="display:block;margin:-10px 0 var(--sp-3);">JPG, PNG o WEBP — máx. 5 MB. Es el fondo detrás de todo el portal público.</span>

            <div class="img-list">
                <?php if (empty($imagenes)): ?>
                <div class="row-empty">Todavía no hay imágenes en el carrusel.</div>
                <?php endif; ?>
                <?php foreach ($imagenes as $img): ?>
                <div class="img-row <?= $img['estado'] ? '' : 'off' ?>">
                    <img class="img-thumb" src="<?= APP_URL ?>/<?= htmlspecialchars($img['ruta_archivo'], ENT_QUOTES, 'UTF-8') ?>" alt="">
                    <span style="font-size:var(--font-size-xs);color:var(--color-text-muted);"><?= $img['estado'] ? 'Activa' : 'Oculta' ?></span>
                    <div class="img-row-actions">
                        <form method="POST" action="<?= APP_URL ?>/admin/landing/imagenes/<?= (int)$img['id_imagen'] ?>/mover">
                            <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="direccion" value="arriba">
                            <button type="submit" class="row-btn" title="Subir orden"><i class="fa-solid fa-arrow-up"></i></button>
                        </form>
                        <form method="POST" action="<?= APP_URL ?>/admin/landing/imagenes/<?= (int)$img['id_imagen'] ?>/mover">
                            <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="direccion" value="abajo">
                            <button type="submit" class="row-btn" title="Bajar orden"><i class="fa-solid fa-arrow-down"></i></button>
                        </form>
                        <form method="POST" action="<?= APP_URL ?>/admin/landing/imagenes/<?= (int)$img['id_imagen'] ?>/toggle">
                            <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                            <button type="submit" class="row-btn" title="<?= $img['estado'] ? 'Ocultar' : 'Activar' ?>"><i class="fa-solid <?= $img['estado'] ? 'fa-eye' : 'fa-eye-slash' ?>"></i></button>
                        </form>
                        <form method="POST" action="<?= APP_URL ?>/admin/landing/imagenes/<?= (int)$img['id_imagen'] ?>/eliminar">
                            <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                            <button type="button" class="row-btn danger" title="Eliminar" onclick="PortalAlert.confirmDelete('¿Eliminar esta imagen del carrusel?', this.form)"><i class="fa-solid fa-trash-can"></i></button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Noticias (con imagen — alimentan el carrusel visual del portal) -->
    <div class="gx">
        <div class="gx-head"><i class="fa-solid fa-newspaper"></i> Noticias <span class="hint">(con imagen — carrusel)</span> <span class="cnt"><?= count($noticias) ?></span></div>
        <div class="gx-body">
            <form method="POST" action="<?= APP_URL ?>/admin/landing/noticias" enctype="multipart/form-data" class="news-add">
                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                <input type="text" name="texto" class="form-control" maxlength="300" required placeholder="Ej: Puerto de Manta incrementa el calado de acceso a 13 metros…">
                <div class="news-add-row2">
                    <input type="url" name="enlace" class="form-control" placeholder="Enlace opcional (https://…) — a dónde redirige al hacer clic">
                    <label class="news-file-btn required"><i class="fa-solid fa-image"></i> <span class="news-file-label">Imagen (obligatoria)</span><input type="file" name="imagen" accept=".jpg,.jpeg,.png,.webp" required hidden onchange="this.closest('label').querySelector('.news-file-label').textContent = this.files[0] ? this.files[0].name : 'Imagen (obligatoria)';"></label>
                    <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-plus"></i> Publicar</button>
                </div>
            </form>
            <span class="form-help" style="display:block;margin:-8px 0 var(--sp-3);">Rota en el carrusel de noticias del portal. Necesita imagen siempre — para texto sin imagen usa Consejos y Novedades, abajo.</span>

            <?php if (empty($noticias)): ?>
            <div class="row-empty">Todavía no hay noticias publicadas.</div>
            <?php endif; ?>
            <?php foreach ($noticias as $n): ?>
            <div class="news-row <?= $n['estado'] ? '' : 'off' ?>">
                <form method="POST" action="<?= APP_URL ?>/admin/landing/noticias/<?= (int)$n['id_noticia'] ?>" class="edit-form" enctype="multipart/form-data">
                    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                    <div class="news-row-main">
                        <img class="news-thumb" src="<?= APP_URL ?>/<?= htmlspecialchars($n['imagen'], ENT_QUOTES, 'UTF-8') ?>" alt="">
                        <input type="text" name="texto" maxlength="300" class="form-control" value="<?= htmlspecialchars($n['texto'], ENT_QUOTES, 'UTF-8') ?>" placeholder="Texto de la noticia…">
                    </div>
                    <div class="news-row-extra">
                        <input type="url" name="enlace" class="form-control" placeholder="Enlace opcional (https://…)" value="<?= htmlspecialchars($n['enlace'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        <label class="news-file-btn" title="Reemplazar imagen">
                            <i class="fa-solid fa-image"></i> <span class="news-file-label">Reemplazar</span>
                            <input type="file" name="imagen" accept=".jpg,.jpeg,.png,.webp" hidden onchange="this.closest('label').querySelector('.news-file-label').textContent = this.files[0] ? this.files[0].name : 'Reemplazar';">
                        </label>
                        <button type="submit" class="row-btn" title="Guardar cambios"><i class="fa-solid fa-floppy-disk"></i></button>
                    </div>
                </form>
                <div class="news-row-actions">
                    <form method="POST" action="<?= APP_URL ?>/admin/landing/noticias/<?= (int)$n['id_noticia'] ?>/mover">
                        <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="direccion" value="arriba">
                        <button type="submit" class="row-btn" title="Subir orden"><i class="fa-solid fa-arrow-up"></i></button>
                    </form>
                    <form method="POST" action="<?= APP_URL ?>/admin/landing/noticias/<?= (int)$n['id_noticia'] ?>/mover">
                        <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="direccion" value="abajo">
                        <button type="submit" class="row-btn" title="Bajar orden"><i class="fa-solid fa-arrow-down"></i></button>
                    </form>
                    <form method="POST" action="<?= APP_URL ?>/admin/landing/noticias/<?= (int)$n['id_noticia'] ?>/toggle">
                        <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                        <button type="submit" class="row-btn" title="<?= $n['estado'] ? 'Ocultar' : 'Activar' ?>"><i class="fa-solid <?= $n['estado'] ? 'fa-eye' : 'fa-eye-slash' ?>"></i></button>
                    </form>
                    <form method="POST" action="<?= APP_URL ?>/admin/landing/noticias/<?= (int)$n['id_noticia'] ?>/eliminar">
                        <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                        <button type="button" class="row-btn danger" title="Eliminar" onclick="PortalAlert.confirmDelete('¿Eliminar esta noticia?', this.form)"><i class="fa-solid fa-trash-can"></i></button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Consejos y novedades (texto puro, sin imagen — franja aparte del carrusel de noticias) -->
<div class="gx">
    <div class="gx-head"><i class="fa-solid fa-lightbulb"></i> Consejos y Novedades <span class="hint">(texto — franja aparte, sin imagen)</span> <span class="cnt"><?= count($consejos) ?></span></div>
    <div class="gx-body">
        <form method="POST" action="<?= APP_URL ?>/admin/landing/consejos" class="tip-add">
            <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
            <input type="text" name="texto" class="form-control" maxlength="300" required placeholder="Ej: Recuerda actualizar tus datos en Talento Humano cada año.">
            <input type="url" name="enlace" class="form-control" placeholder="Enlace opcional (https://…)">
            <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-plus"></i> Agregar</button>
        </form>
        <span class="form-help" style="display:block;margin:-8px 0 var(--sp-3);">Texto rotativo en la franja "Consejos y novedades" del portal — independiente de las noticias con imagen.</span>

        <?php if (empty($consejos)): ?>
        <div class="row-empty">Todavía no hay consejos publicados.</div>
        <?php endif; ?>
        <?php foreach ($consejos as $c): ?>
        <div class="tip-row <?= $c['estado'] ? '' : 'off' ?>">
            <form method="POST" action="<?= APP_URL ?>/admin/landing/consejos/<?= (int)$c['id_consejo'] ?>" class="edit-form">
                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                <input type="text" name="texto" maxlength="300" class="form-control" value="<?= htmlspecialchars($c['texto'], ENT_QUOTES, 'UTF-8') ?>">
                <input type="url" name="enlace" class="form-control" placeholder="Enlace opcional" value="<?= htmlspecialchars($c['enlace'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                <button type="submit" class="row-btn" title="Guardar cambio"><i class="fa-solid fa-floppy-disk"></i></button>
            </form>
            <div class="tip-row-actions">
                <form method="POST" action="<?= APP_URL ?>/admin/landing/consejos/<?= (int)$c['id_consejo'] ?>/mover">
                    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="direccion" value="arriba">
                    <button type="submit" class="row-btn" title="Subir orden"><i class="fa-solid fa-arrow-up"></i></button>
                </form>
                <form method="POST" action="<?= APP_URL ?>/admin/landing/consejos/<?= (int)$c['id_consejo'] ?>/mover">
                    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="direccion" value="abajo">
                    <button type="submit" class="row-btn" title="Bajar orden"><i class="fa-solid fa-arrow-down"></i></button>
                </form>
                <form method="POST" action="<?= APP_URL ?>/admin/landing/consejos/<?= (int)$c['id_consejo'] ?>/toggle">
                    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                    <button type="submit" class="row-btn" title="<?= $c['estado'] ? 'Ocultar' : 'Activar' ?>"><i class="fa-solid <?= $c['estado'] ? 'fa-eye' : 'fa-eye-slash' ?>"></i></button>
                </form>
                <form method="POST" action="<?= APP_URL ?>/admin/landing/consejos/<?= (int)$c['id_consejo'] ?>/eliminar">
                    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                    <button type="button" class="row-btn danger" title="Eliminar" onclick="PortalAlert.confirmDelete('¿Eliminar este consejo?', this.form)"><i class="fa-solid fa-trash-can"></i></button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
</div>

<script>
(function () {
    // Escala el iframe (ancho real de escritorio, 1440px) para que quepa en
    // la tarjeta sin deformar el layout — la página que se ve dentro es la
    // pública real (?preview=1), no una copia.
    const wrap   = document.getElementById('prevScaleWrap');
    const iframe = document.getElementById('prevIframe');
    const IFRAME_H = 980;
    iframe.style.height = IFRAME_H + 'px';

    function rescale() {
        const scale = wrap.clientWidth / 1440;
        iframe.style.transform = 'scale(' + scale + ')';
        iframe.style.transformOrigin = 'top left';
        wrap.style.height = (IFRAME_H * scale) + 'px';
    }
    window.addEventListener('resize', rescale);
    rescale();

    iframe.addEventListener('load', function () {
        wrap.classList.remove('loading');
        rescale();
    });

    document.getElementById('prevRefreshBtn').addEventListener('click', function () {
        wrap.classList.add('loading');
        try {
            iframe.contentWindow.location.reload();
        } catch (e) {
            iframe.src = iframe.src;
        }
    });
})();
</script>
