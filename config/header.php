<?php
/**
 * config/header.php — Configuración del encabezado del módulo Portuaria
 * (integrado de portuaria_demoV4).
 *
 *   'modo' => 'logo'   → logo cuadrado + título
 *   'modo' => 'imagen' → banner panorámico
 */

return [

    'modo' => 'logo',   // 'logo' | 'imagen'

    // ── Modo LOGO ────────────────────────────────────────────────
    'logo' => [
        'src'       => 'public/img/logoapm.png',
        'alt'       => 'Logo APM',
        'titulo'    => 'Autoridad Portuaria de Manta',
        'subtitulo' => 'Bitácoras Portuarias — Portal APM',
    ],

    // ── Modo IMAGEN (banner) ─────────────────────────────────────
    'imagen' => [
        'src'    => 'public/img/portuaria/headerlogoapm.png',
        'alt'    => 'Autoridad Portuaria de Manta',
        'height' => '60px',
    ],

];
