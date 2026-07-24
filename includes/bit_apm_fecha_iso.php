<?php
/**
 * Normaliza fechas de formularios (YYYY-MM-DD o DD/MM/YYYY) a ISO Y-m-d para parámetros sqlsrv / SQL Server.
 * La presentación en pantalla puede seguir siendo DD/MM/YYYY; solo usar esto antes de INSERT/UPDATE.
 *
 * @return string|null Y-m-d o null si no es interpretable
 */
function apm_post_fecha_a_iso(?string $raw): ?string
{
    if ($raw === null) {
        return null;
    }
    $s = trim($raw);
    if ($s === '') {
        return null;
    }

    if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $s, $m)) {
        $y = (int) $m[1];
        $mo = (int) $m[2];
        $d = (int) $m[3];
        if (checkdate($mo, $d, $y)) {
            return sprintf('%04d-%02d-%02d', $y, $mo, $d);
        }

        return null;
    }

    // DD/MM/YYYY o D/M/YYYY (latino)
    if (preg_match('#^(\d{1,2})/(\d{1,2})/(\d{4})$#', $s, $m)) {
        $d = (int) $m[1];
        $mo = (int) $m[2];
        $y = (int) $m[3];
        if (checkdate($mo, $d, $y)) {
            return sprintf('%04d-%02d-%02d', $y, $mo, $d);
        }

        return null;
    }

    $ts = strtotime(str_replace('/', '-', $s));
    if ($ts !== false) {
        $y = (int) date('Y', $ts);
        if ($y >= 1990 && $y <= 2100) {
            return date('Y-m-d', $ts);
        }
    }

    return null;
}

/**
 * Devuelve fecha segura para SQL Server en formato internacional YYYYMMDD.
 *
 * @return string|null YYYYMMDD o null si no es interpretable
 */
function apm_post_fecha_a_ymd_compacto(?string $raw): ?string
{
    $iso = apm_post_fecha_a_iso($raw);
    if ($iso === null) {
        return null;
    }

    return str_replace('-', '', $iso);
}
