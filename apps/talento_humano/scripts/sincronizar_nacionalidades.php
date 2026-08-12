<?php
declare(strict_types=1);

define('ROOT', dirname(__DIR__));
require ROOT . '/core/Config.php';
require ROOT . '/core/Database.php';

if (!extension_loaded('intl')) {
    fwrite(STDERR, "Se requiere la extension PHP intl.\n");
    exit(1);
}

$bundle = ResourceBundle::create('es', 'ICUDATA-region');
$countries = $bundle?->get('Countries');
if (!$countries instanceof ResourceBundle) {
    fwrite(STDERR, "ICU no entrego el catalogo de paises.\n");
    exit(1);
}

$gentilicios = [
    'AR'=>['Argentina','Argentino Argentina'],'AT'=>['Austriaca','Austriaco Austria'],
    'BE'=>['Belga','Belgica Bélgica'],'BO'=>['Boliviana','Boliviano Bolivia'],
    'BR'=>['Brasileña','Brasileño Brasil'],'CA'=>['Canadiense','Canada Canadá'],
    'CH'=>['Suiza','Suizo Suiza'],'CL'=>['Chilena','Chileno Chile'],
    'CN'=>['China','Chino China'],'CO'=>['Colombiana','Colombiano Colombia'],
    'CR'=>['Costarricense','Costa Rica'],'CU'=>['Cubana','Cubano Cuba'],
    'DE'=>['Alemana','Aleman Alemán Alemania'],'DO'=>['Dominicana','Dominicano Republica República Dominicana'],
    'EC'=>['Ecuatoriana','Ecuatoriano Ecuador'],'ES'=>['Española','Español España'],
    'FR'=>['Francesa','Frances Francesa Francia'],'GB'=>['Británica','Britanico Reino Unido Inglaterra'],
    'GT'=>['Guatemalteca','Guatemalteco Guatemala'],'HN'=>['Hondureña','Hondureño Honduras'],
    'IN'=>['India','Indio India'],'IT'=>['Italiana','Italiano Italia'],
    'JP'=>['Japonesa','Japones Japón Japon'],'KR'=>['Surcoreana','Coreana Corea del Sur'],
    'MX'=>['Mexicana','Mexicano México Mexico'],'NI'=>['Nicaragüense','Nicaragua'],
    'NL'=>['Neerlandesa','Holandesa Paises Países Bajos Holanda'],'PA'=>['Panameña','Panameño Panamá Panama'],
    'PE'=>['Peruana','Peruano Perú Peru'],'PT'=>['Portuguesa','Portugues Portugal'],
    'PY'=>['Paraguaya','Paraguayo Paraguay'],'RU'=>['Rusa','Ruso Rusia'],
    'SV'=>['Salvadoreña','Salvadoreño El Salvador'],'UA'=>['Ucraniana','Ucraniano Ucrania'],
    'US'=>['Estadounidense','Estados Unidos Norteamericana Norteamericano'],
    'UY'=>['Uruguaya','Uruguayo Uruguay'],'VE'=>['Venezolana','Venezolano Venezuela'],
];

$db = Conexion::conectar();
$sql = "MERGE dbo.th_nacionalidades AS t
        USING(SELECT :codigo codigo,:pais pais,:nombre nombre,:aliases aliases) s
        ON t.codigo_iso=s.codigo
        WHEN MATCHED THEN UPDATE SET pais=s.pais,nombre=s.nombre,aliases=s.aliases,activo=1,fecha_actualizacion=SYSDATETIME()
        WHEN NOT MATCHED THEN INSERT(codigo_iso,pais,nombre,aliases) VALUES(s.codigo,s.pais,s.nombre,s.aliases);";
$stmt = $db->prepare($sql);
$total = 0;
$db->beginTransaction();
try {
    foreach ($countries as $code => $country) {
        if (!preg_match('/^[A-Z]{2}$/', (string)$code) || in_array($code, ['EU','EZ','QO','UN','XA','XB'], true)) {
            continue;
        }
        $country = trim((string)$country);
        if ($country === '') continue;
        [$name,$extra] = $gentilicios[$code] ?? [$country,$country];
        $stmt->execute([':codigo'=>$code,':pais'=>$country,':nombre'=>$name,':aliases'=>trim($extra.' '.$country)]);
        $total++;
    }
    $db->commit();
    echo "NACIONALIDADES_SINCRONIZADAS={$total}\n";
} catch (Throwable $e) {
    if ($db->inTransaction()) $db->rollBack();
    fwrite(STDERR, "No fue posible sincronizar nacionalidades: {$e->getMessage()}\n");
    exit(1);
}
