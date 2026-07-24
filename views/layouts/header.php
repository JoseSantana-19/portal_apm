<?php
/**
 * Global Header Component. Sets up responsive metadata, premium typography, and styles.
 * Matches PORTAL_APM_UNIFICADO.html layout structure.
 */
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$baseUrl = dirname($scriptName);
$baseUrl = str_replace('\\', '/', $baseUrl);
if ($baseUrl === '/' || $baseUrl === '\\') {
    $baseUrl = '';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Portal Único APM' ?></title>
    
    <!-- Premium Google Fonts: Sora (Body) & JetBrains Mono (Code/Technical) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600;700&family=Sora:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Styling Framework Sheets -->
    <link rel="stylesheet" href="<?= $baseUrl ?>/css/variables.css">
    <link rel="stylesheet" href="<?= $baseUrl ?>/css/style.css">
    
    <!-- Lucide Icons & FontAwesome for corporate high-fidelity icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="t1">
    <script>
        (function() {
            var savedTheme = localStorage.getItem('apm_theme') || '1';
            document.body.className = 't' + savedTheme;
        })();
    </script>
    <div class="app-shell">
