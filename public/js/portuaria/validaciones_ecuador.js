/**
 * Validación cédula / RUC Ecuador (espejo de includes/bit_validaciones_ecuador.php) para uso en formularios.
 * @global
 */
(function (global) {
    'use strict';

    function soloDigitos(s) {
        return String(s || '').replace(/\D/g, '');
    }

    function ecValidarCedulaEcuador(cedula) {
        cedula = soloDigitos(cedula);
        if (cedula.length !== 10) return false;

        var prov = parseInt(cedula.slice(0, 2), 10);
        if ((prov < 1 || prov > 24) && prov !== 30) return false;
        if (parseInt(cedula.charAt(2), 10) > 5) return false;

        var coef = [2, 1, 2, 1, 2, 1, 2, 1, 2];
        var sum = 0;
        var i;
        for (i = 0; i < 9; i++) {
            var p = parseInt(cedula.charAt(i), 10) * coef[i];
            if (p >= 10) p -= 9;
            sum += p;
        }
        var verif = (10 - (sum % 10)) % 10;
        return verif === parseInt(cedula.charAt(9), 10);
    }

    function ecValidarRucPersonaNatural(ruc) {
        ruc = soloDigitos(ruc);
        if (ruc.length !== 13) return false;
        if (ruc.slice(10, 13) !== '001') return false;
        return ecValidarCedulaEcuador(ruc.slice(0, 10));
    }

    function ecValidarRucSociedadModulo11(ruc) {
        ruc = soloDigitos(ruc);
        if (ruc.length !== 13) return false;
        if (ruc.charAt(2) !== '9') return false;

        var coef = [4, 3, 2, 7, 6, 5, 4, 3, 2];
        var sum = 0;
        var i;
        for (i = 0; i < 9; i++) {
            sum += parseInt(ruc.charAt(i), 10) * coef[i];
        }
        var mod = sum % 11;
        var d = 11 - mod;
        if (d === 11) d = 0;
        else if (d === 10) d = 0;

        return parseInt(ruc.charAt(9), 10) === d;
    }

    function ecValidarRucEcuador(ruc) {
        ruc = soloDigitos(ruc);
        if (ruc.length !== 13) return false;
        if (ecValidarRucPersonaNatural(ruc)) return true;
        return ecValidarRucSociedadModulo11(ruc);
    }

    /** Solo números; máximo maxLen. */
    function apmRestringirInputNumerico(el, maxLen) {
        if (!el) return;
        function filtrar() {
            var v = soloDigitos(el.value).slice(0, maxLen);
            if (el.value !== v) el.value = v;
        }
        el.setAttribute('maxlength', String(maxLen));
        el.setAttribute('inputmode', 'numeric');
        el.addEventListener('input', filtrar);
        el.addEventListener('paste', function () {
            setTimeout(filtrar, 0);
        });
        filtrar();
    }

    global.ecValidarCedulaEcuador = ecValidarCedulaEcuador;
    global.ecValidarRucEcuador = ecValidarRucEcuador;
    global.apmRestringirInputNumerico = apmRestringirInputNumerico;
    global.APM_MSG_IDENTIFICACION_INVALIDA = 'La identificación ingresada no es válida';
})(typeof window !== 'undefined' ? window : this);
