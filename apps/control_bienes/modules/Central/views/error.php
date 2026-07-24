<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error del Sistema — APM Terminal</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg:        #0f1117;
            --surface:   #1a1d27;
            --border:    rgba(255,255,255,0.07);
            --accent:    #e74c6e;
            --accent-glow: rgba(231,76,110,0.25);
            --text:      #e8eaf0;
            --muted:     #6b7280;
        }

        body {
            background: var(--bg);
            color: var(--text);
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        /* Orbes de fondo */
        .orb {
            position: fixed;
            border-radius: 50%;
            filter: blur(100px);
            pointer-events: none;
            opacity: 0.35;
        }
        .orb-1 { width: 500px; height: 500px; background: var(--accent); top: -200px; left: -150px; }
        .orb-2 { width: 400px; height: 400px; background: #4b5df0; bottom: -200px; right: -100px; }

        .card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 3rem 3.5rem;
            max-width: 520px;
            width: 90%;
            text-align: center;
            backdrop-filter: blur(20px);
            box-shadow: 0 0 60px var(--accent-glow), 0 20px 60px rgba(0,0,0,0.5);
            position: relative;
            z-index: 10;
            animation: fadeIn 0.6s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .icon-wrap {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: var(--accent-glow);
            border: 2px solid var(--accent);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 2rem;
        }

        h1 {
            font-size: 1.6rem;
            font-weight: 700;
            margin-bottom: 0.75rem;
            color: var(--text);
        }

        p {
            color: var(--muted);
            font-size: 0.95rem;
            line-height: 1.6;
            margin-bottom: 1.25rem;
        }

        .code-ref {
            display: inline-block;
            background: rgba(231,76,110,0.12);
            border: 1px solid rgba(231,76,110,0.3);
            color: var(--accent);
            padding: 0.2rem 0.75rem;
            border-radius: 6px;
            font-family: 'Courier New', monospace;
            font-size: 0.8rem;
            margin-bottom: 2rem;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.75rem;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.9rem;
            text-decoration: none;
            transition: all 0.2s ease;
        }
        .btn-primary {
            background: var(--accent);
            color: #fff;
            box-shadow: 0 4px 20px var(--accent-glow);
        }
        .btn-primary:hover {
            background: #d63d5f;
            transform: translateY(-2px);
            box-shadow: 0 8px 30px var(--accent-glow);
        }
        .btn-ghost {
            background: transparent;
            color: var(--muted);
            border: 1px solid var(--border);
            margin-left: 0.75rem;
        }
        .btn-ghost:hover {
            color: var(--text);
            border-color: rgba(255,255,255,0.2);
        }

        .divider {
            height: 1px;
            background: var(--border);
            margin: 2rem 0;
        }

        .footer-note {
            font-size: 0.75rem;
            color: var(--muted);
        }
    </style>
</head>
<body>

    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>

    <div class="card">
        <div class="icon-wrap">⚠️</div>

        <h1>Ha ocurrido un inv_error</h1>

        <p>
            El sistema ha detectado un problema inesperado al procesar tu solicitud.
            El equipo técnico ha sido notificado automáticamente y el inv_error
            ha sido registrado para su revisión.
        </p>

        <p>
            Por favor intenta nuevamente. Si el problema persiste,
            contacta al administrador del sistema.
        </p>

        <div class="code-ref">
            REF: <?= date('YmdHis') . '-' . strtoupper(substr(md5(uniqid()), 0, 8)) ?>
        </div>

        <div>
            <a href="index.php" class="btn btn-primary">
                🏠 Volver al inicio
            </a>
            <a href="javascript:history.back()" class="btn btn-ghost">
                ← Regresar
            </a>
        </div>

        <div class="divider"></div>

        <div class="footer-note">
            Sistema de Inventario Portuario v<?= defined('APP_VERSION') ? APP_VERSION : '3.2' ?> — APM Terminal
        </div>
    </div>

</body>
</html>
