<?php
/**
 * config/header.php — Configuración del encabezado del módulo Bitácoras.
 *
 *   'modo' => 'logo'   → logo cuadrado + título
 *   'modo' => 'imagen' → banner panorámico
 *
 * El logo institucional es el compartido de todo el portal
 * (../../imgs/logoapm*.png desde apps/bitacoras/) — no una copia propia.
 */

return [

    'modo' => 'logo',   // 'logo' | 'imagen'

    // ── Modo LOGO ────────────────────────────────────────────────
    'logo' => [
        'src'       => function_exists('base_url') ? base_url('../../imgs/logoapm.png') : '/imgs/logoapm.png',
        'alt'       => 'Logo APM',
        'titulo'    => 'Autoridad Portuaria de Manta',
        'subtitulo' => 'Bitácoras Portuarias — Portal APM',
    ],

    // ── Modo IMAGEN (banner) ─────────────────────────────────────
    'imagen' => [
        'src'    => function_exists('base_url') ? base_url('../../imgs/logoapm_banner.png') : '/imgs/logoapm_banner.png',
        'alt'    => 'Autoridad Portuaria de Manta',
        'height' => '60px',
    ],

];
