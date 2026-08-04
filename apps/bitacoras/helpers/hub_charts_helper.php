<?php
/**
 * hub_charts_helper.php — Mini-gráficos de los paneles hub (sin librerías).
 *
 * Dos formas, ambas monoserie (una sola métrica ⇒ sin leyenda; el título de la
 * tarjeta nombra la serie). El color de la serie es el acento del módulo; los
 * textos SIEMPRE usan los tokens de texto del tema (nunca el color de la serie).
 *
 *   apm_chart_bars($rows, '#0891b2')  → barras horizontales por categoría
 *   apm_chart_cols($rows, '#0891b2')  → columnas por período (tiempo)
 *
 * $rows = [['label' => 'Garita 1', 'value' => 12], ...] (ya ordenadas).
 */

if (!function_exists('apm_chart_bars')) {
    /** Barras horizontales: categoría + barra + valor. Marcas finas (10px),
     *  extremo de dato redondeado, pista sutil con el token de acento. */
    function apm_chart_bars(array $rows, string $accent): string
    {
        if (empty($rows)) {
            return '<div style="padding:var(--sp-6) 0;text-align:center;font-size:.8rem;color:var(--text-muted,var(--color-text-muted));">Sin datos para graficar.</div>';
        }
        $max = 0;
        foreach ($rows as $r) { $max = max($max, (int)$r['value']); }
        if ($max <= 0) { $max = 1; }

        $h = '<div style="display:flex;flex-direction:column;gap:10px;">';
        foreach ($rows as $r) {
            $label = htmlspecialchars((string)$r['label'], ENT_QUOTES, 'UTF-8');
            $val   = (int)$r['value'];
            $pct   = max(round($val / $max * 100), $val > 0 ? 2 : 0);
            $h .= '<div style="display:grid;grid-template-columns:minmax(90px,38%) 1fr 34px;gap:8px;align-items:center;" title="' . $label . ': ' . $val . '">'
                .   '<div style="font-size:.74rem;color:var(--text-app,var(--text-color));white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">' . $label . '</div>'
                .   '<div style="height:10px;border-radius:0 4px 4px 0;background:var(--accent-app,rgba(127,127,127,.12));overflow:hidden;">'
                .     '<div style="width:' . $pct . '%;height:100%;border-radius:0 4px 4px 0;background:' . $accent . ';"></div>'
                .   '</div>'
                .   '<div style="font-size:.74rem;font-weight:700;text-align:right;color:var(--text-app,var(--text-color));font-variant-numeric:tabular-nums;">' . $val . '</div>'
                . '</div>';
        }
        return $h . '</div>';
    }
}

if (!function_exists('apm_chart_cols')) {
    /** Columnas por período: altura relativa, tope redondeado, etiqueta corta
     *  bajo cada columna. Valor visible solo en el máximo y el último período
     *  (etiquetado selectivo); el resto por tooltip nativo. */
    function apm_chart_cols(array $rows, string $accent): string
    {
        if (empty($rows)) {
            return '<div style="padding:var(--sp-6) 0;text-align:center;font-size:.8rem;color:var(--text-muted,var(--color-text-muted));">Sin datos para graficar.</div>';
        }
        $max = 0; $iMax = 0; $n = count($rows); $i = 0;
        foreach ($rows as $r) {
            if ((int)$r['value'] > $max) { $max = (int)$r['value']; $iMax = $i; }
            $i++;
        }
        if ($max <= 0) { $max = 1; }

        $h = '<div style="display:flex;align-items:flex-end;gap:6px;height:120px;padding-top:18px;">';
        $i = 0;
        foreach ($rows as $r) {
            $label = htmlspecialchars((string)$r['label'], ENT_QUOTES, 'UTF-8');
            $val   = (int)$r['value'];
            $pct   = max(round($val / $max * 100), $val > 0 ? 4 : 0);
            $showN = ($i === $iMax || $i === $n - 1);
            $h .= '<div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:4px;min-width:0;height:100%;justify-content:flex-end;" title="' . $label . ': ' . $val . '">'
                .   ($showN ? '<div style="font-size:.68rem;font-weight:700;color:var(--text-app,var(--text-color));font-variant-numeric:tabular-nums;">' . $val . '</div>' : '')
                .   '<div style="width:100%;max-width:26px;height:' . $pct . '%;min-height:' . ($val > 0 ? 3 : 0) . 'px;border-radius:4px 4px 0 0;background:' . $accent . ';"></div>'
                .   '<div style="font-size:.62rem;color:var(--text-muted,var(--color-text-muted));white-space:nowrap;overflow:hidden;max-width:100%;text-overflow:ellipsis;">' . $label . '</div>'
                . '</div>';
            $i++;
        }
        return $h . '</div>';
    }
}
