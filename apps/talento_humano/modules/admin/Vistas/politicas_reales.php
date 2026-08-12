<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Políticas | APM</title>
    <?php require ROOT.'/shared/head_assets.php'; ?>
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/admin_compact.css">
</head>
<body><div class="app">
<?php require ROOT.'/shared/menu.php'; ?>
<section class="content">
    <?php $topbarTitle='Políticas y normativas';$topbarSubtitle='Repositorio documental privado';$topbarShowSearch=true;require ROOT.'/shared/topbar.php'; ?>
    <main class="main"><div class="content-shell admin-page">
        <section class="admin-section-head"><div><h1>Repositorio institucional</h1><p>Documentos privados, versiones y vigencia normativa.</p></div><span class="admin-count-chip"><i class="bi bi-file-earmark-check"></i><?= (int)$vigentes ?> vigentes de <?= (int)$total ?></span></section>
        <?php if(Auth::can('politicas','crear')): ?>
        <details class="admin-disclosure">
            <summary><span class="admin-disclosure-icon"><i class="bi bi-cloud-arrow-up-fill"></i></span><span class="admin-disclosure-copy"><span>Publicar documento</span><small>PDF o DOCX institucional, máximo 20 MB.</small></span><i class="bi bi-chevron-down admin-disclosure-chevron"></i></summary>
            <div class="admin-disclosure-body"><form method="post" enctype="multipart/form-data" action="<?= BASE_URL ?>/admin/politicas/subir" class="admin-form-grid">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Auth::csrfToken()) ?>">
                <div class="field"><label>Título</label><input name="titulo" required maxlength="200"></div>
                <div class="field"><label>Categoría</label><input name="categoria" required maxlength="80"></div>
                <div class="field"><label>Versión</label><input name="version" value="1.0" required maxlength="30"></div>
                <div class="field"><label>Archivo PDF/DOCX</label><input type="file" name="archivo" accept=".pdf,.docx" required></div>
                <div class="field" style="grid-column:1/-1"><label>Descripción</label><textarea name="descripcion" maxlength="500"></textarea></div>
                <div class="admin-form-actions"><button class="btn btn-primary" type="submit"><i class="bi bi-cloud-arrow-up"></i> Publicar</button></div>
            </form></div>
        </details>
        <?php endif; ?>
        <section class="card admin-table-card"><div class="admin-table-scroll"><table><thead><tr><th>Documento</th><th>Categoría</th><th>Versión</th><th>Fecha</th><th>Descargas</th><th></th></tr></thead><tbody>
        <?php foreach($documentos as $d): ?><tr><td><strong><?= htmlspecialchars($d['titulo']) ?></strong><br><small><?= htmlspecialchars($d['descripcion']??'') ?></small></td><td><?= htmlspecialchars($d['categoria']) ?><br><small><?= (int)$d['vigente']===1?'Vigente':'Retirado' ?></small></td><td><?= htmlspecialchars($d['version']) ?></td><td><?= htmlspecialchars(substr((string)$d['fecha_subida'],0,10)) ?></td><td><?= (int)$d['descargas'] ?></td><td style="white-space:nowrap"><?php if((int)$d['vigente']===1): ?><a class="btn btn-outline" href="<?= BASE_URL ?>/admin/politicas/descargar?id=<?= (int)$d['id'] ?>">Descargar</a><?php if(Auth::can('politicas','editar')): ?><form method="post" action="<?= BASE_URL ?>/admin/politicas/retirar" style="display:inline" onsubmit="return confirm('¿Retirar la vigencia de este documento?')"><input type="hidden" name="_csrf" value="<?= htmlspecialchars(Auth::csrfToken()) ?>"><input type="hidden" name="documento_id" value="<?= (int)$d['id'] ?>"><button class="btn btn-ghost" type="submit">Retirar</button></form><?php endif; ?><?php endif; ?></td></tr><?php endforeach; ?>
        <?php if(!$documentos): ?><tr><td colspan="6">Aún no hay documentos publicados.</td></tr><?php endif; ?>
        </tbody></table></div></section>
    </div></main>
</section></div>
<script><?php if(!empty($_GET['msg'])): ?>addEventListener('DOMContentLoaded',()=>showToast(<?= json_encode($_GET['msg']) ?>,<?= ($_GET['ok']??'0')==='1'?"'success'":"'error'" ?>));<?php endif; ?></script>
<?php require ROOT.'/shared/footer_scripts.php'; ?>
</body></html>
