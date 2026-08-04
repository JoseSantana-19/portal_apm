<?php
/**
 * Funciones auxiliares para tablas con texto truncado + modal "ver más".
 * Migradas desde el inicio de bit_listado_visitas.php (antes definidas inline).
 */

if (!function_exists('apm_esc')) {
    function apm_esc($s)
    {
        return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
    }
}

/**
 * Celda con texto completo en el DOM (ellipsis por CSS); overflow, (+) y popover se resuelven en JS.
 *
 * @param string $fullText Texto íntegro mostrado en la celda
 * @param string $popoverLabel Título del recuadro al pasar el cursor
 * @param string|null $modalBody Texto del modal (por defecto igual que $fullText)
 * @param array $opts force_expand_button: mostrar (+) aunque no haya overflow
 */
if (!function_exists('apm_listado_trunc_row')) {
    function apm_listado_trunc_row($fullText, $popoverLabel, $modalBody = null, array $opts = [])
    {
        $full = (string) $fullText;
        $modalBody = $modalBody !== null ? (string) $modalBody : $full;
        $forceExpand = !empty($opts['force_expand_button']);
        $rowAttr = $forceExpand ? ' data-apm-expand-modal-only="1"' : '';

        return '<div class="apm-truncate-row d-flex align-items-center gap-1 min-w-0 w-100"' . $rowAttr . '>'
            . '<span class="apm-truncate-text" data-apm-full="' . apm_esc($full) . '">' . apm_esc($full) . '</span>'
            . '<button type="button" class="btn btn-link btn-sm p-0 apm-btn-expand d-none" data-bs-toggle="modal" data-bs-target="#modalDetalleTexto" data-apm-campo="' . apm_esc($popoverLabel) . '" data-apm-text="' . apm_esc($modalBody) . '" title="Ver más" aria-label="Ver texto completo"><i class="bi bi-plus-circle fs-6" aria-hidden="true"></i></button>'
            . '</div>';
    }
}
