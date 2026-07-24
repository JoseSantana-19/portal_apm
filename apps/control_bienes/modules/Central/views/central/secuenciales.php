<?php
/**
 * SEQ_SECUENCIALES.PHP - Vista de Contadores y Secuenciales del Sistema
 * Compatible con PHP 7.4, 8.3 y 8.4
 */
?>

<!-- InvCabecera de Página -->
<div class="page-header animate-fade-in">
    <div class="page-title">
        <h1>Secuenciales de Índices</h1>
        <p>Configura y supervisa los contadores automáticos del sistema. Estos índices garantizan códigos únicos universales (ej: <strong>INV-00001</strong>) para cada registro de auditoría, personal o inventario.</p>
    </div>
</div>

<div class="panel animate-fade-in" style="margin-bottom: 24px;">
    <div class="panel-header">
        <h3>Contadores de Módulos Registrados</h3>
    </div>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Módulo / Componente</th>
                    <th>Prefijo Configurado</th>
                    <th>Último Valor Registrado</th>
                    <th>Ejemplo de Código Generado</th>
                    <th>Acciones Operativas</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($inv_secuenciales as $s): 
                    $modulo = $s['modulo'];
                    $prefijo = $s['prefijo'];
                    $ultimo = (int)$s['ultimo_numero'];
                    
                    // Formatear un ejemplo visual del código
                    $ejemplo = 'GEN-0000';
                    $modLabel = 'General';
                    if ($modulo === 'inv') {
                        $ejemplo = $prefijo . str_pad($ultimo + 1, 5, '0', STR_PAD_LEFT);
                        $modLabel = 'Inventario General (Bienes)';
                    } elseif ($modulo === 'bit') {
                        $ejemplo = $prefijo . str_pad($ultimo + 1, 6, '0', STR_PAD_LEFT);
                        $modLabel = 'Bitácora de Auditoría (Logs)';
                    } elseif ($modulo === 'th') {
                        $ejemplo = $prefijo . str_pad($ultimo + 1, 4, '0', STR_PAD_LEFT);
                        $modLabel = 'Cabeceras de Talento Humano';
                    } elseif ($modulo === 'acc') {
                        $ejemplo = $prefijo . str_pad($ultimo + 1, 4, '0', STR_PAD_LEFT);
                        $modLabel = 'Control de Acceso (Usuarios)';
                    }
                ?>
                    <tr>
                        <td>
                            <strong style="color:var(--text-color);"><i class="fa-solid fa-server" style="margin-right:8px;color:var(--primary);"></i> <?= htmlspecialchars($modLabel) ?></strong>
                            <span style="display:block;font-size:11px;color:var(--text-muted);">Clave técnica: <code><?= htmlspecialchars($modulo) ?></code></span>
                        </td>
                        <td><span style="font-family: monospace;font-size: 15px;font-weight: 700;color: var(--primary);"><?= htmlspecialchars($prefijo) ?></span></td>
                        <td>
                            <strong style="font-size: 16px;"><?= $ultimo ?></strong>
                            <span style="display:block;font-size:10px;color:var(--text-muted);">Registros creados</span>
                        </td>
                        <td>
                            <span class="status-badge transit" style="font-family: monospace;font-size: 13.5px;font-weight: 700;background: rgba(59,130,246,0.08);color: var(--primary);">
                                <i class="fa-solid fa-signature"></i> Siguiente: <?= $ejemplo ?>
                            </span>
                        </td>
                        <td class="acciones-cell" style="gap:12px;">
                            <a href="index.php?route=inv_secuenciales&action=test&modulo=<?= $modulo ?>" class="btn-primary btn-sm" style="text-decoration:none;display:inline-flex;align-items:center;gap:6px;" title="Incrementa y genera un código de prueba temporal">
                                <i class="fa-solid fa-wand-magic-sparkles"></i> Probar Generador
                            </a>
                            <a href="index.php?route=inv_secuenciales&action=reiniciar&modulo=<?= $modulo ?>" class="btn-outline btn-sm" style="text-decoration:none;display:inline-flex;align-items:center;gap:6px;border-color:var(--danger);color:var(--danger);" onclick="return confirm('¿Seguro que desea reiniciar a cero el contador de este módulo? Esto podría provocar duplicaciones si existen registros activos.');" title="Reinicia el contador a cero">
                                <i class="fa-solid fa-rotate-left"></i> Reiniciar
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="panel animate-fade-in" style="border-left: 5px solid var(--primary);">
    <div class="panel-header" style="background:rgba(59,130,246,0.03);padding:16px 20px;">
        <h4 style="margin:0;color:var(--primary);"><i class="fa-solid fa-circle-question"></i> Nota Técnica de Coherencia e Integridad</h4>
    </div>
    <div style="padding:20px;font-size:13.5px;line-height:1.6;color:var(--text-color);">
        <p style="margin:0 0 12px 0;">
            Los inv_secuenciales son claves primarias o secundarias correlativas de lectura humana. Se incrementan de forma transaccional directa sobre la base de datos SQLite para evitar colisiones de concurrencia.
        </p>
        <p style="margin:0;">
            <strong>¡Precaución!</strong> Reiniciar un secuencial a cero en un entorno de producción que ya contiene registros físicos activos provocará fallos por clave primaria duplicada la próxima vez que se intente registrar un equipo en dicho módulo. Use la acción de reiniciar únicamente durante pruebas de desarrollo.
        </p>
    </div>
</div>
