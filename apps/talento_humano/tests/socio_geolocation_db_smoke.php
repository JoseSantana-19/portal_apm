<?php
declare(strict_types=1);

define('ROOT', dirname(__DIR__));
require ROOT . '/core/Config.php';
require ROOT . '/core/Database.php';

$db = Conexion::conectar();
$failures = [];

$columns = (int)$db->query(
    "SELECT COUNT(*) FROM sys.columns
     WHERE object_id=OBJECT_ID('dbo.th_estudios_socioeconomicos')
       AND name IN ('mapa_url_original','latitud','longitud','indicaciones_llegada',
                    'origen_geolocalizacion','mapa_imagen','qr_imagen')"
)->fetchColumn();
if ($columns !== 7) $failures[] = "Columnas de geolocalización: {$columns}/7.";

$constraints = (int)$db->query(
    "SELECT COUNT(*) FROM sys.check_constraints
     WHERE parent_object_id=OBJECT_ID('dbo.th_estudios_socioeconomicos')
       AND name IN ('CK_th_estudio_latitud','CK_th_estudio_longitud',
                    'CK_th_estudio_coordenadas_par','CK_th_estudio_origen_geo')"
)->fetchColumn();
if ($constraints !== 4) $failures[] = "Restricciones de geolocalización: {$constraints}/4.";

$index = (int)$db->query(
    "SELECT COUNT(*) FROM sys.indexes
     WHERE object_id=OBJECT_ID('dbo.th_estudios_socioeconomicos')
       AND name='IX_th_estudios_geolocalizacion'"
)->fetchColumn();
if ($index !== 1) $failures[] = 'Índice filtrado de geolocalización ausente.';

$ledger = (int)$db->query(
    "SELECT COUNT(*) FROM dbo.th_schema_migrations
     WHERE version='2026.08.25.2' AND LEN(checksum_sha256)=64"
)->fetchColumn();
if ($ledger !== 1) $failures[] = 'Ledger/checksum 2026.08.25.2 ausente.';

if ($failures) {
    fwrite(STDERR, "SOCIO_GEOLOCATION_DB_SMOKE_FAIL\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}

echo "SOCIO_GEOLOCATION_DB_SMOKE_OK\n";
