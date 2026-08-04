<?php
// rutas/config_rutas.php (versión Portal APM — integración portuaria_demoV4)
//
// Las páginas legacy del módulo Portuaria hacen `include` de este archivo y
// esperan variables $url_* en el scope actual. La fuente única de verdad es
// modules/Portuaria/config_rutas.php (que además define base_url()).

$__rutasPortuaria = require __DIR__ . '/../modules/Portuaria/config_rutas.php';
extract($__rutasPortuaria, EXTR_SKIP);
unset($__rutasPortuaria);
