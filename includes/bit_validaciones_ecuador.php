<?php
/**
 * Validación de cédula y RUC (Ecuador) para uso antes de INSERT/UPDATE.
 */

/** Mensaje unificado solicitado para identificación inválida. */
function apm_mensaje_identificacion_invalida(): string
{
    return 'La identificación ingresada no es válida';
}

/**
 * Cédula ecuatoriana: 10 dígitos, módulo 10.
 * Provincia 01–24 o 30; tercer dígito &lt; 6 (persona natural).
 */
function ec_validar_cedula_ecuador(string $cedula): bool
{
    $cedula = preg_replace('/\D/', '', $cedula);
    if (strlen($cedula) !== 10 || !ctype_digit($cedula)) {
        return false;
    }

    $prov = (int) substr($cedula, 0, 2);
    if (($prov < 1 || $prov > 24) && $prov !== 30) {
        return false;
    }

    if ((int) $cedula[2] > 5) {
        return false;
    }

    $coef = [2, 1, 2, 1, 2, 1, 2, 1, 2];
    $sum = 0;
    for ($i = 0; $i < 9; $i++) {
        $p = (int) $cedula[$i] * $coef[$i];
        if ($p >= 10) {
            $p -= 9;
        }
        $sum += $p;
    }

    $verif = (10 - ($sum % 10)) % 10;

    return $verif === (int) $cedula[9];
}

/**
 * RUC persona natural: 13 dígitos, cédula válida en los primeros 10 y sufijo 001.
 */
function ec_validar_ruc_persona_natural(string $ruc): bool
{
    $ruc = preg_replace('/\D/', '', $ruc);
    if (strlen($ruc) !== 13) {
        return false;
    }
    if (substr($ruc, 10, 3) !== '001') {
        return false;
    }

    return ec_validar_cedula_ecuador(substr($ruc, 0, 10));
}

/**
 * RUC sociedad (persona jurídica): tercer dígito 9, dígito verificador módulo 11 (coef. SRI).
 */
function ec_validar_ruc_sociedad_modulo11(string $ruc): bool
{
    $ruc = preg_replace('/\D/', '', $ruc);
    if (strlen($ruc) !== 13) {
        return false;
    }
    if ($ruc[2] !== '9') {
        return false;
    }

    $coef = [4, 3, 2, 7, 6, 5, 4, 3, 2];
    $sum = 0;
    for ($i = 0; $i < 9; $i++) {
        $sum += (int) $ruc[$i] * $coef[$i];
    }
    $mod = $sum % 11;
    $d = 11 - $mod;
    if ($d === 11) {
        $d = 0;
    } elseif ($d === 10) {
        $d = 0;
    }

    return (int) $ruc[9] === $d;
}

/**
 * RUC válido: persona natural (cédula + 001) o sociedad (módulo 11).
 */
function ec_validar_ruc_ecuador(string $ruc): bool
{
    $ruc = preg_replace('/\D/', '', $ruc);
    if (strlen($ruc) !== 13) {
        return false;
    }

    if (ec_validar_ruc_persona_natural($ruc)) {
        return true;
    }

    return ec_validar_ruc_sociedad_modulo11($ruc);
}
