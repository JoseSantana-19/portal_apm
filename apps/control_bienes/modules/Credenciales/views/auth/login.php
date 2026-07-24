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
            --primary: #3b82f6;
            --primary-glow: rgba(59, 130, 246, 0.5);
            --bg-gradient: linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #020617 100%);
            --glass-bg: rgba(30, 41, 59, 0.45);
            --glass-border: rgba(255, 255, 255, 0.08);
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
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

        /* Orbes Decorativos Animados en Segundo Plano */
        .orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(100px);
            z-index: 1;
            opacity: 0.6;
            animation: orbFloat 25s infinite ease-in-out alternate;
        }
        .orb-1 {
            width: 400px;
            height: 400px;
            background: #1d4ed8;
            top: -100px;
            left: -100px;
        }
        .orb-2 {
            width: 500px;
            height: 500px;
            background: #4f46e5;
            bottom: -150px;
            right: -100px;
            animation-delay: -5s;
        }
        .orb-3 {
            width: 300px;
            height: 300px;
            background: #06b6d4;
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
            border: 1px solid var(--glass-border);
            border-radius: 24px;
            padding: 40px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            transition: all 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            text-align: center;
        }

        .inv_login-card:hover {
            border-color: rgba(255, 255, 255, 0.15);
            box-shadow: 0 30px 60px -15px rgba(59, 130, 246, 0.2);
            transform: translateY(-4px);
        }

        /* InvCabecera del Card */
        .brand-logo {
            width: 80px;
            height: 80px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 24px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
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
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid var(--glass-border);
            border-radius: 16px;
            color: var(--text-main);
            font-size: 16px;
            font-weight: 500;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .inv_login-input::placeholder {
            color: rgba(148, 163, 184, 0.5);
        }

        .inv_login-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px var(--primary-glow);
            background: rgba(15, 23, 42, 0.8);
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
            background: rgba(245, 158, 11, 0.15);
            border: 1px solid rgba(245, 158, 11, 0.3);
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
            color: #fef3c7;
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
    </style>
</head>
<body>

    <!-- Orbes de fondo -->
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>
    <div class="orb orb-3"></div>

    <div class="inv_login-container">
        <div class="inv_login-card">
            
            <div class="brand-logo">
                <i class="fa-solid fa-anchor"></i>
            </div>
            
            <h1>Terminal Portuaria</h1>
            <p>Control de Acceso Operativo e Inventario General. Por favor, introduce tus credenciales para iniciar sesión.</p>

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
                    <label for="usuario">Nombre de Usuario</label>
                    <div class="input-wrapper">
                        <input type="text" name="usuario" id="usuario" class="inv_login-input" placeholder="Nombre de usuario (ej: admin)" required autocomplete="username">
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
        }
    </script>
</body>
</html>
