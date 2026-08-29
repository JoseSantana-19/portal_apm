<?php
/**
 * LOGIN.PHP - Vista Premium de Inicio de Sesión y Bloqueo de Pantalla
 * Presenta un diseño glassmorphic de alta fidelidad, animaciones micro-interactivas y responsivo.
 * Compatible con PHP 7.4, 8.3 y 8.4
 */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bloqueo de Terminal - Sistema Portuario</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #2777d3;
            --primary-glow: rgba(39, 119, 211, 0.18);
            --bg-gradient: linear-gradient(145deg, #ffffff 0%, #f6fbff 46%, #eaf5ff 100%);
            --glass-bg: linear-gradient(145deg, rgba(235, 247, 255, 0.98), rgba(205, 231, 255, 0.95));
            --glass-border: rgba(255, 255, 255, 0.92);
            --text-main: #17324d;
            --text-muted: #58738f;
            --danger: #ef4444;
            --success: #10b981;
            --warning: #f59e0b;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Outfit', sans-serif;
        }

        body {
            background: var(--bg-gradient);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
        }

        body::before {
            content: '';
            position: absolute;
            inset: 0;
            z-index: 0;
            pointer-events: none;
            background:
                radial-gradient(circle at 12% 18%, rgba(255,255,255,.98) 0 9%, transparent 27%),
                radial-gradient(circle at 88% 82%, rgba(255,255,255,.9) 0 11%, transparent 30%),
                linear-gradient(rgba(83, 155, 224, .045) 1px, transparent 1px),
                linear-gradient(90deg, rgba(83, 155, 224, .045) 1px, transparent 1px);
            background-size: auto, auto, 42px 42px, 42px 42px;
        }

        body::after {
            content: '';
            position: absolute;
            z-index: 2;
            left: -8%;
            bottom: -235px;
            width: 116%;
            height: 390px;
            border-radius: 50% 50% 0 0;
            pointer-events: none;
            background: linear-gradient(180deg, rgba(118, 190, 246, .25), rgba(224, 243, 255, .82));
            box-shadow:
                0 -18px 0 rgba(255,255,255,.72),
                0 -36px 0 rgba(126,194,247,.08);
            transform: rotate(-2deg);
        }

        .nautical-mark {
            position: absolute;
            z-index: 3;
            display: grid;
            place-items: center;
            width: 82px;
            height: 82px;
            border: 1px solid rgba(70, 143, 210, .16);
            border-radius: 26px;
            background: rgba(255,255,255,.66);
            color: rgba(48, 123, 190, .42);
            box-shadow: 0 16px 36px rgba(62, 130, 190, .1), inset 0 1px 0 #fff;
            backdrop-filter: blur(7px);
            animation: decorDrift 8s ease-in-out infinite alternate;
        }

        .nautical-mark i { font-size: 29px; }
        .nautical-mark.compass { top: 11%; right: 9%; transform: rotate(9deg); }
        .nautical-mark.ship { right: 14%; bottom: 15%; width: 102px; height: 70px; border-radius: 35px; animation-delay: -3s; }
        .nautical-mark.waves { left: 10%; bottom: 18%; width: 112px; height: 62px; border-radius: 31px; animation-delay: -5s; }

        .bubble-cluster {
            position: absolute;
            z-index: 2;
            top: 20%;
            left: 9%;
            width: 125px;
            height: 170px;
            pointer-events: none;
            background:
                radial-gradient(circle at 25% 80%, rgba(255,255,255,.9) 0 8px, rgba(74,151,219,.18) 9px 10px, transparent 11px),
                radial-gradient(circle at 68% 55%, rgba(255,255,255,.9) 0 14px, rgba(74,151,219,.15) 15px 16px, transparent 17px),
                radial-gradient(circle at 38% 22%, rgba(255,255,255,.92) 0 20px, rgba(74,151,219,.14) 21px 22px, transparent 23px);
            animation: bubblesFloat 7s ease-in-out infinite alternate;
        }

        @keyframes decorDrift {
            from { translate: 0 0; }
            to { translate: 0 -12px; }
        }

        @keyframes bubblesFloat {
            from { transform: translateY(8px); }
            to { transform: translateY(-10px); }
        }

        /* Orbes Decorativos Animados en Segundo Plano */
        .orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(100px);
            z-index: 1;
            opacity: 0.32;
            animation: orbFloat 25s infinite ease-in-out alternate;
        }
        .orb-1 {
            width: 400px;
            height: 400px;
            background: #a8d8ff;
            top: -100px;
            left: -100px;
        }
        .orb-2 {
            width: 500px;
            height: 500px;
            background: #c5dcff;
            bottom: -150px;
            right: -100px;
            animation-delay: -5s;
        }
        .orb-3 {
            width: 300px;
            height: 300px;
            background: #b7edf7;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            animation-delay: -10s;
        }

        @keyframes orbFloat {
            0% { transform: translate(0, 0) scale(1); }
            50% { transform: translate(60px, -40px) scale(1.15); }
            100% { transform: translate(-40px, 50px) scale(0.9); }
        }

        /* Contenedor Principal con Glassmorphism */
        .inv_login-container {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 480px;
            padding: 24px;
        }

        .inv_login-card {
            background: var(--glass-bg);
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border: 2px solid var(--glass-border);
            border-radius: 24px;
            padding: 40px;
            box-shadow: 0 26px 65px -24px rgba(40, 105, 165, 0.38), inset 0 1px 0 rgba(255,255,255,.9);
            transition: all 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            text-align: center;
        }

        .inv_login-card:hover {
            border-color: #ffffff;
            box-shadow: 0 32px 72px -24px rgba(40, 105, 165, 0.42), inset 0 1px 0 #fff;
            transform: translateY(-4px);
        }

        /* InvCabecera del Card */
        .brand-logo {
            width: 80px;
            height: 80px;
            background: rgba(255, 255, 255, 0.72);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 24px;
            box-shadow: 0 12px 26px rgba(42, 113, 177, 0.15);
        }

        .brand-logo i {
            font-size: 38px;
            color: var(--primary);
            filter: drop-shadow(0 0 8px var(--primary-glow));
            animation: pulseLogo 3s infinite ease-in-out;
        }

        @keyframes pulseLogo {
            0%, 100% { transform: scale(1); filter: drop-shadow(0 0 8px var(--primary-glow)); }
            50% { transform: scale(1.08); filter: drop-shadow(0 0 16px #3b82f6); }
        }

        .inv_login-card h1 {
            font-size: 26px;
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 8px;
            letter-spacing: -0.5px;
        }

        .inv_login-card p {
            color: var(--text-muted);
            font-size: 15px;
            line-height: 1.6;
            margin-bottom: 32px;
        }

        /* Formulario e Inputs */
        .form-group {
            text-align: left;
            margin-bottom: 24px;
            position: relative;
        }

        .form-group label {
            display: block;
            color: var(--text-muted);
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-bottom: 8px;
        }

        .input-wrapper {
            position: relative;
            width: 100%;
        }

        .input-wrapper i.input-icon {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 18px;
            pointer-events: none;
            transition: color 0.3s;
            z-index: 5;
        }

        .inv_login-input {
            width: 100%;
            padding: 16px 20px 16px 52px;
            background: rgba(255, 255, 255, 0.9);
            border: 1px solid rgba(69, 133, 194, 0.2);
            border-radius: 16px;
            color: var(--text-main);
            font-size: 16px;
            font-weight: 500;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .inv_login-input::placeholder {
            color: rgba(88, 115, 143, 0.58);
        }

        .inv_login-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px var(--primary-glow);
            background: #ffffff;
        }

        .inv_login-input:focus + i.input-icon {
            color: var(--primary);
        }

        /* Botón de Entrada */
        .btn-inv_login {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            border: none;
            border-radius: 16px;
            color: white;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 10px 20px -5px rgba(29, 78, 216, 0.4);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .btn-inv_login::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: 0.5s;
        }

        .btn-inv_login:hover::before {
            left: 100%;
        }

        .btn-inv_login:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 25px -5px rgba(29, 78, 216, 0.6);
            background: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%);
        }

        .btn-inv_login:active {
            transform: translateY(1px);
        }

        /* Alertas / Toast */
        .alert-toast {
            background: rgba(255, 250, 235, 0.9);
            border: 1px solid rgba(217, 143, 20, 0.28);
            border-radius: 16px;
            padding: 16px 20px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
            text-align: left;
            animation: shakeAlert 0.5s ease-in-out;
        }

        .alert-toast i {
            font-size: 20px;
            color: var(--warning);
        }

        .alert-toast p {
            font-size: 14px;
            color: #815414;
            margin: 0;
            line-height: 1.4;
        }

        @keyframes shakeAlert {
            0%, 100% { transform: translateX(0); }
            20%, 60% { transform: translateX(-6px); }
            40%, 80% { transform: translateX(6px); }
        }

        /* Footer */
        .inv_login-footer {
            margin-top: 32px;
            font-size: 12px;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .inv_login-footer i {
            color: var(--primary);
        }

        /* Spinner en Login */
        .spinner {
            display: none;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        @media (max-width: 820px) {
            .nautical-mark.compass { right: 3%; top: 5%; transform: scale(.72) rotate(9deg); }
            .nautical-mark.ship { right: 2%; bottom: 7%; transform: scale(.72); }
            .nautical-mark.waves, .bubble-cluster { display: none; }
        }
    </style>
    <?php if (defined('PORTAL_ROOT_URL')): ?>
    <script src="<?= PORTAL_ROOT_URL ?>/js/password-hash.js?v=<?= @filemtime(dirname(rtrim(ROOT_PATH, '/'), 2) . '/js/password-hash.js') ?: time() ?>"></script>
    <?php endif; ?>
</head>
<body>

    <!-- Orbes de fondo -->
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>
    <div class="orb orb-3"></div>
    <div class="bubble-cluster" aria-hidden="true"></div>
    <div class="nautical-mark compass" aria-hidden="true"><i class="fa-regular fa-compass"></i></div>
    <div class="nautical-mark waves" aria-hidden="true"><i class="fa-solid fa-water"></i></div>
    <div class="nautical-mark ship" aria-hidden="true"><i class="fa-solid fa-ship"></i></div>

    <div class="inv_login-container">
        <div class="inv_login-card">
            
            <div class="brand-logo">
                <i class="fa-solid fa-anchor"></i>
            </div>
            
            <h1>Terminal Portuaria</h1>
            <p>Control de Acceso Operativo e Inventario General. Ingresa con tu cédula y contraseña.</p>

            <!-- Alerta por inactividad/timeout o inv_error de sesión -->
            <?php if (isset($_SESSION['toast'])): ?>
                <div class="alert-toast">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <div>
                        <p><?= htmlspecialchars($_SESSION['toast']['mensaje']) ?></p>
                    </div>
                </div>
                <?php unset($_SESSION['toast']); ?>
            <?php endif; ?>

            <form action="index.php?route=login_post" method="POST" onsubmit="iniciarCarga(event)">
                <div class="form-group">
                    <label for="usuario">Cédula</label>
                    <div class="input-wrapper">
                        <input type="text" name="usuario" id="usuario" class="inv_login-input" placeholder="Cédula (o admin para pruebas)" required autocomplete="username" inputmode="numeric">
                        <i class="fa-solid fa-user input-icon"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label for="contrasena">Contraseña</label>
                    <div class="input-wrapper">
                        <input type="password" name="contrasena" id="contrasena" class="inv_login-input" style="padding-right: 52px;" placeholder="Contraseña de acceso" required autocomplete="current-password">
                        <i class="fa-solid fa-lock input-icon"></i>
                        <i class="fa-solid fa-eye eye-icon" id="toggle-password" onclick="togglePasswordVisibility()" style="cursor: pointer; position: absolute; right: 18px; top: 50%; transform: translateY(-50%); color: var(--text-muted); transition: color 0.3s; font-size: 18px; z-index: 10;"></i>
                    </div>
                </div>

                <button type="submit" class="btn-inv_login" id="btn-inv_login-text">
                    <span id="btn-lbl"><i class="fa-solid fa-sign-in-alt"></i> Acceder al Sistema</span>
                    <div class="spinner" id="btn-spinner"></div>
                </button>
            </form>

            <div class="inv_login-footer">
                <i class="fa-solid fa-shield-halved"></i>
                <span>Conexión Segura Directa a Terminal v3.0.0</span>
            </div>

        </div>
    </div>

    <script>
        function togglePasswordVisibility() {
            const pwdField = document.getElementById('contrasena');
            const icon = document.getElementById('toggle-password');
            if (pwdField.type === 'password') {
                pwdField.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                pwdField.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        function iniciarCarga(event) {
            const user = document.getElementById('usuario').value.trim();
            const pass = document.getElementById('contrasena').value.trim();
            if (user === "" || pass === "") {
                event.preventDefault();
                return;
            }

            // Animación de carga para una excelente experiencia de usuario
            const label = document.getElementById('btn-lbl');
            const spinner = document.getElementById('btn-spinner');

            label.style.display = 'none';
            spinner.style.display = 'block';

            // Hash SHA-256 de la contraseña en el navegador (ver
            // js/password-hash.js) -- el servidor combina ese hash con el
            // pepper compartido de todo el sistema, ver AuthController::loginPost().
            if (window.hashPasswordFieldsBeforeSubmit) {
                event.preventDefault();
                const form = event.target;
                hashPasswordFieldsBeforeSubmit(form, ['contrasena']).then(function () { form.submit(); });
            }
        }
    </script>
</body>
</html>
