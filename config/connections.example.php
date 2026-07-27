<?php
/**
 * config/connections.example.php — Plantilla. Copiar a "connections.php"
 * (en la misma carpeta) y ajustar a TU máquina.
 *
 * connections.php NO se sube a git (cada dev tiene su propio servidor SQL
 * local) — este ejemplo sí, para que cualquiera sepa qué forma tiene.
 *
 * Es un archivo de DATOS puro (solo `return`, ningún `define`/side effect),
 * a propósito: así lo puede hacer `require` cualquier módulo — nativo o
 * independiente — sin riesgo de chocar con sus propias constantes (ROOT,
 * APP_URL, etc. las define cada quien por su lado).
 */

return [
    // Instancia SQL Server de TU máquina. Ejemplos: '.\VICTUS', '.\SQLEXPRESS',
    // 'localhost', '192.168.1.10\SQLEXPRESS,1433'.
    'server_default' => '.\\SQLEXPRESS',

    // vacío = Autenticación de Windows (recomendado en desarrollo).
    'credentials' => [
        'user' => '',
        'pass' => '',
    ],

    'options' => [
        'trust_cert' => true,   // true en dev (certificado autofirmado)
        'encrypt'    => false,  // true solo si el servidor usa SSL
        'charset'    => 'UTF-8',
    ],

    // Nombre lógico → nombre real de la BD. 'server' en null = server_default;
    // solo se pisa si esa BD en particular vive en otra instancia.
    'databases' => [
        'portal'        => ['name' => 'PORTAL_APM',       'server' => null],
        'talento'       => ['name' => 'Talento_Humano',   'server' => null],
        'portuaria'     => ['name' => 'PortuariaDemo',    'server' => null],
        'portuaria_ext' => ['name' => 'PortuariaExterna', 'server' => null],
        'inventario'    => ['name' => 'inventario',       'server' => null],
    ],
];
