<div style="animation:pageFadeIn 0.3s ease-out both;">

<div class="page-heading">
    <div style="display:flex;align-items:center;gap:var(--sp-3);">
        <a href="<?= APP_URL ?>/acceso/visitantes" class="btn btn-ghost btn-sm" data-spa>
            <i class="fa-solid fa-arrow-left"></i>
        </a>
        <div class="page-title">
            <div class="page-title-icon"><i class="fa-solid fa-address-card"></i></div>
            Ficha del Visitante
        </div>
    </div>
    <a href="<?= APP_URL ?>/acceso/ingresar?doc=<?= urlencode($visitante['cedula'] ?? '') ?>"
       class="btn btn-primary btn-sm" data-spa>
        <i class="fa-solid fa-person-walking-arrow-right"></i> Registrar Ingreso
    </a>
</div>

<div style="max-width:520px;">
<div class="card card-accent">

    <div class="profile-hero">
        <div class="profile-avatar profile-avatar-circle" style="background:linear-gradient(135deg,#0891B2,#0E7490);">
            <i class="fa-solid fa-user" style="font-size:1.4rem;"></i>
        </div>
        <div>
            <div class="profile-name">
                <?= htmlspecialchars(($visitante['nombres'] ?? '') . ' ' . ($visitante['apellidos'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
            </div>
            <div class="profile-sub">
                <?= htmlspecialchars($visitante['empresa'] ?? 'Sin empresa', ENT_QUOTES, 'UTF-8') ?>
            </div>
            <div class="profile-tags">
                <span class="badge badge-success">
                    <i class="fa-solid fa-circle" style="font-size:5px;vertical-align:middle;"></i>
                    Registrado
                </span>
            </div>
        </div>
    </div>

    <div class="section-header">
        <div class="section-title">
            <span class="section-title-mark"></span>
            Datos de Contacto
        </div>
    </div>

    <div class="data-grid">
        <?php
        $fr = $visitante['fecha_registro'] ?? null;
        if ($fr instanceof DateTime) { $fr = $fr->format('d/m/Y'); }
        elseif (is_string($fr) && $fr) { $fr = date('d/m/Y', strtotime($fr)); }
        else { $fr = '—'; }
        $campos = [
            'Cédula'     => $visitante['cedula']       ?? '—',
            'Empresa'    => $visitante['empresa']      ?? '—',
            'Correo'     => $visitante['correo']       ?? '—',
            'Teléfono'   => $visitante['telefono']     ?? '—',
            'Registrado' => $fr,
        ];
        foreach ($campos as $lbl => $val):
        ?>
        <div>
            <div class="data-label"><?= $lbl ?></div>
            <div class="data-value"><?= htmlspecialchars($val, ENT_QUOTES, 'UTF-8') ?></div>
        </div>
        <?php endforeach; ?>
    </div>

</div>
</div>

</div>
