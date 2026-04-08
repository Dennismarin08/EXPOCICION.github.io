<?php
// =========================================================
// login.php — Página de login con detección de "Recordarme"
// =========================================================
require_once 'db.php';

// Si ya hay sesión activa (normal o por cookie), redirigir
if (isset($_SESSION['user_id'])) {
    $rol = $_SESSION['user_rol'] ?? 'usuario';
    switch ($rol) {
        case 'admin':       header('Location: admin/admin-dashboard.php'); exit;
        case 'veterinaria': header('Location: vet/vet-dashboard.php');     exit;
        case 'tienda':      header('Location: tienda/tienda-dashboard.php'); exit;
        default:            header('Location: dashboard.php');              exit;
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - RUGAL</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Configuración PWA -->
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#667eea">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <link rel="apple-touch-icon" href="assets/images/logo.png">
    <script>
    if ('serviceWorker' in navigator) {
      window.addEventListener('load', function() {
        navigator.serviceWorker.register('service-worker.js')
          .then(reg => console.log('PWA lista', reg.scope))
          .catch(err => console.log('Error PWA:', err));
      });
    }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .auth-container {
            width: 100%;
            max-width: 400px;
        }

        .auth-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        .logo {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
            margin: 0 auto 15px;
        }

        .logo-text {
            font-size: 32px;
            font-weight: 800;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 10px;
            font-size: 28px;
        }

        .subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 30px;
            font-size: 16px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #444;
            font-weight: 500;
        }

        .required {
            color: #ff4757;
        }

        .form-input {
            width: 100%;
            padding: 15px;
            border: 2px solid #ddd;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s;
        }

        .form-input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .input-with-icon {
            position: relative;
        }

        .input-icon {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #667eea;
            cursor: pointer;
        }

        .btn {
            width: 100%;
            padding: 15px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }

        .btn-primary:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none !important;
        }

        .register-link {
            text-align: center;
            margin-top: 20px;
            color: #666;
        }

        .register-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }

        .error-message {
            background: #ffebee;
            color: #c62828;
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: none;
            text-align: center;
        }

        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: none;
            text-align: center;
        }

        .loading {
            text-align: center;
            margin-top: 20px;
            display: none;
        }

        .spinner {
            border: 3px solid rgba(102, 126, 234, 0.3);
            border-radius: 50%;
            border-top: 3px solid #667eea;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .forgot-password {
            text-align: right;
            margin-bottom: 20px;
        }

        .forgot-password a {
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
        }

        .forgot-password a:hover {
            text-decoration: underline;
        }

        @media (max-width: 768px) {
            .auth-card {
                padding: 30px 20px;
            }
        }
    </style>
</head>

<body>
    <a href="index.php" style="position: absolute; top: 20px; left: 20px; color: white; text-decoration: none; display: flex; align-items: center; gap: 8px; font-weight: 500; font-size: 15px; background: rgba(0,0,0,0.2); padding: 10px 18px; border-radius: 50px; backdrop-filter: blur(5px); transition: all 0.3s; z-index: 100;" onmouseover="this.style.background='rgba(0,0,0,0.4)'" onmouseout="this.style.background='rgba(0,0,0,0.2)'">
        <i class="fas fa-arrow-left"></i> Volver al inicio
    </a>
    <div class="auth-container">
        <div class="auth-card">
            <div class="logo">
                <div class="logo-icon">
                    <i class="fas fa-paw"></i>
                </div>
                <div class="logo-text">RUGAL</div>
            </div>

            <h1>Bienvenido de nuevo</h1>
            <p class="subtitle">Inicia sesión para acceder a tu cuenta</p>

            <form id="loginForm">
                <!-- Mensaje de error -->
                <div class="error-message" id="errorMessage">
                    <i class="fas fa-exclamation-circle"></i> <span id="errorText"></span>
                </div>

                <!-- Mensaje de éxito -->
                <div class="success-message" id="successMessage">
                    <i class="fas fa-check-circle"></i> ¡Inicio de sesión exitoso! Redirigiendo...
                </div>

                <!-- Email -->
                <div class="form-group">
                    <label>Email <span class="required">*</span></label>
                    <input type="email" class="form-input" id="email" name="email" required
                        placeholder="tucorreo@ejemplo.com">
                </div>

                <!-- Contraseña -->
                <div class="form-group">
                    <label>Contraseña <span class="required">*</span></label>
                    <div class="input-with-icon">
                        <input type="password" class="form-input" id="password" name="password" required
                            placeholder="Ingresa tu contraseña">
                        <span class="input-icon" onclick="togglePassword()">
                            <i class="fas fa-eye"></i>
                        </span>
                    </div>
                </div>

                <!-- Recordarme / Olvidé contraseña -->
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <label style="display: flex; align-items: center; gap: 8px; font-weight: normal; margin: 0; cursor: pointer;">
                        <input type="checkbox" name="remember" id="remember" style="width: 16px; height: 16px; accent-color: #667eea; cursor: pointer;">
                        <span style="font-size: 14px; color: #555;">Recordarme</span>
                    </label>
                    <div class="forgot-password" style="margin-bottom: 0;">
                        <a href="#">¿Olvidaste tu contraseña?</a>
                    </div>
                </div>

                <!-- Botón de login -->
                <button type="submit" class="btn btn-primary" id="submitBtn">
                    <i class="fas fa-sign-in-alt"></i> Iniciar Sesión
                </button>

                <!-- Loading -->
                <div class="loading" id="loading">
                    <div class="spinner"></div>
                    <p style="margin-top: 10px;">Verificando credenciales...</p>
                </div>
            </form>

            <div class="register-link">
                ¿No tienes una cuenta? <a href="registro.html">Regístrate aquí</a>
            </div>
        </div>
    </div>

    <script>
        // Mostrar/ocultar contraseña
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const eyeIcon = document.querySelector('.input-icon i');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.classList.remove('fa-eye');
                eyeIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                eyeIcon.classList.remove('fa-eye-slash');
                eyeIcon.classList.add('fa-eye');
            }
        }

        // Envío del formulario
        document.getElementById('loginForm').addEventListener('submit', async function (e) {
            e.preventDefault();

            document.getElementById('errorMessage').style.display = 'none';
            document.getElementById('successMessage').style.display = 'none';

            const submitBtn = document.getElementById('submitBtn');
            const loading = document.getElementById('loading');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verificando...';
            loading.style.display = 'block';

            const formData = new FormData(this);

            try {
                const response = await fetch('proceso-login.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-sign-in-alt"></i> Iniciar Sesión';
                loading.style.display = 'none';

                if (data.success) {
                    document.getElementById('successMessage').style.display = 'block';
                    setTimeout(() => {
                        window.location.href = data.redirect || 'dashboard.php';
                    }, 1000);
                } else {
                    document.getElementById('errorText').textContent = data.message || 'Credenciales incorrectas';
                    document.getElementById('errorMessage').style.display = 'block';

                    document.getElementById('loginForm').style.animation = 'shake 0.5s';
                    setTimeout(() => {
                        document.getElementById('loginForm').style.animation = '';
                    }, 500);
                }

            } catch (error) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-sign-in-alt"></i> Iniciar Sesión';
                loading.style.display = 'none';

                document.getElementById('errorText').textContent = 'Error de conexión. Intenta nuevamente.';
                document.getElementById('errorMessage').style.display = 'block';
                console.error('Error:', error);
            }
        });

        const style = document.createElement('style');
        style.textContent = `
            @keyframes shake {
                0%, 100% { transform: translateX(0); }
                10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
                20%, 40%, 60%, 80% { transform: translateX(5px); }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>

</html>
