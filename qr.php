<?php
require_once 'db.php';
require_once 'puntos-functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit;
}

$userId = $_SESSION['user_id'];
$user = getUsuario($userId);
$nivelInfo = obtenerInfoNivel($user['nivel'] ?? 'bronce');

// Obtener todas las mascotas del usuario
$stmt = $pdo->prepare("SELECT * FROM mascotas WHERE user_id = ?");
$stmt->execute([$userId]);
$mascotas = $stmt->fetchAll();

// Mascota seleccionada (por defecto la primera)
$selectedPetId = $_GET['mascota_id'] ?? ($mascotas[0]['id'] ?? null);
$selectedPet = null;

if ($selectedPetId) {
    foreach ($mascotas as $m) {
        if ($m['id'] == $selectedPetId) {
            $selectedPet = $m;
            break;
        }
    }
}

// Generar URL del perfil y QR
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$host = $_SERVER['HTTP_HOST'];
$path = dirname($_SERVER['PHP_SELF']);
$profileUrl = $selectedPet ? "$protocol://$host$path/perfil-mascota.php?id=" . $selectedPet['id'] : "";
$qrUrl = $selectedPet ? "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($profileUrl) : "";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR ID - RUGAL</title>
    <link rel="icon" href="assets/images/logo.png" type="image/png">
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="css/themes.css">
    <link rel="stylesheet" href="dashboard-extra.css">
    <link rel="stylesheet" href="css/responsive.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --p-primary: #6E6AD9;
            --p-accent: #7C89F5;
            --p-gradient: linear-gradient(135deg, #6E6AD9 0%, #7C89F5 100%);
            --p-glass: rgba(255, 255, 255, 0.03);
            --p-border: rgba(255, 255, 255, 0.1);
            --p-text-main: #f8fafc;
            --p-text-muted: #94a3b8;
            --bg-dark: #0f172a;
        }

        body {
            background-color: var(--bg-dark);
            background-image: radial-gradient(circle at 50% -20%, rgba(110, 106, 217, 0.15) 0%, transparent 50%);
            color: var(--p-text-main);
        }

        .qr-card {
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--p-border);
            border-radius: 32px;
            padding: 50px 40px;
            text-align: center;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            max-width: 550px;
            margin: 40px auto;
            position: relative;
            overflow: hidden;
            border-top: 1px solid rgba(255, 255, 255, 0.2);
        }

        .qr-card::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 100%;
            background: radial-gradient(circle at top right, rgba(110, 106, 217, 0.1), transparent 40%);
            pointer-events: none;
        }
        
        .qr-code-container {
            margin: 35px 0;
            padding: 30px;
            background: white;
            border-radius: 24px;
            display: inline-block;
            box-shadow: 0 0 40px rgba(110, 106, 217, 0.3);
            position: relative;
            z-index: 1;
        }

        .qr-code-container::before {
            content: '';
            position: absolute;
            inset: -4px;
            background: var(--p-gradient);
            border-radius: 28px;
            z-index: -1;
            opacity: 0.5;
        }
        
        .qr-image {
            width: 240px;
            height: 240px;
            object-fit: contain;
        }
        
        .pet-selector {
            margin-bottom: 35px;
            width: 100%;
            max-width: 350px;
            padding: 14px 24px;
            border-radius: 16px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: white;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            appearance: none;
            -webkit-appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='rgba(255,255,255,0.5)'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 20px center;
            background-size: 20px;
        }

        .pet-selector:hover {
            background-color: rgba(255, 255, 255, 0.08);
            border-color: rgba(255, 255, 255, 0.2);
        }

        .pet-selector:focus {
            outline: none;
            border-color: var(--p-accent);
            background-color: rgba(255, 255, 255, 0.1);
            box-shadow: 0 0 0 4px rgba(110, 106, 217, 0.2);
        }

        .pet-selector option {
            background-color: #1e293b;
            color: white;
            padding: 12px;
        }

        .qr-title {
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 3px;
            color: var(--p-accent);
            font-weight: 800;
            margin-bottom: 12px;
            text-shadow: 0 0 10px rgba(124, 137, 245, 0.3);
        }

        .pet-name-display {
            font-size: 28px;
            font-weight: 800;
            margin-bottom: 8px;
            background: linear-gradient(135deg, #fff 0%, #cbd5e1 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .btn-premium-action {
            padding: 14px 28px;
            border-radius: 14px;
            font-weight: 700;
            font-size: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s;
            cursor: pointer;
            border: none;
        }

        .btn-primary-gradient {
            background: var(--p-gradient);
            color: white;
            box-shadow: 0 10px 20px -5px rgba(110, 106, 217, 0.4);
        }

        .btn-primary-gradient:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px -5px rgba(110, 106, 217, 0.5);
            filter: brightness(1.1);
        }

        .btn-glass-action {
            background: rgba(255, 255, 255, 0.05);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
        }

        .btn-glass-action:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.3);
            transform: translateY(-3px);
        }

        /* Fix para visibilidad de cabecera */
        .header {
            flex-direction: column;
            align-items: center;
            text-align: center;
            padding: 30px 20px;
            gap: 15px;
        }
        .header-left, .header-right {
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .page-title {
            color: #ffffff !important;
            font-weight: 800 !important;
            font-size: 2.2rem !important;
            margin-bottom: 5px !important;
            background: linear-gradient(135deg, #000000ff 0%, #9465ecff 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            transition: transform 0.3s ease;
        }
        .page-title:hover {
            transform: scale(1.05);
        }
        .breadcrumb, .breadcrumb span, .breadcrumb i {
            color: rgba(255, 255, 255, 0.6) !important;
            justify-content: center;
        }

        /* Responsive Fixes */
        @media (max-width: 768px) {
            .qr-card {
                padding: 30px 20px;
                margin: 20px 10px;
                border-radius: 24px;
                max-width: calc(100% - 20px);
            }
            .qr-code-container {
                padding: 20px;
            }
            .qr-image {
                width: 200px;
                height: 200px;
            }
            .pet-name-display {
                font-size: 22px;
            }
            .btn-premium-action {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body class="theme-usuario">
    <?php include 'includes/sidebar.php'; ?>
    <div class="main-content">
        <header class="header">
            <div class="header-left">
                <h1 class="page-title">Identificación QR 📱</h1>
                <div class="breadcrumb">
                    <span>Dashboard</span> <i class="fas fa-chevron-right"></i> <span>QR ID</span>
                </div>
            </div>
            
            <div class="header-right">
                <div class="nivel-badge">
                    <?php echo $nivelInfo['icono']; ?> Nivel <?php echo $nivelInfo['nombre']; ?>
                </div>
            </div>
        </header>
        
        <div class="content-wrapper">
            <?php if (empty($mascotas)): ?>
                <div class="empty-state">
                    <i class="fas fa-paw"></i>
                    <p>No tienes mascotas registradas.</p>
                    <a href="agregar-mascota.php" class="btn-primary">Agregar Mascota</a>
                </div>
            <?php else: ?>
                <div class="qr-card">
                    <div class="qr-title">Identificación Única RUGAL</div>
                    <div style="color: var(--p-text-muted); font-size: 14px; margin-bottom: 25px; font-weight: 500;">Gestiona el acceso médico de tu mascota</div>
                    
                    <select class="pet-selector" onchange="window.location.href='?mascota_id='+this.value">
                        <?php foreach ($mascotas as $pet): ?>
                            <option value="<?php echo $pet['id']; ?>" <?php echo ($selectedPet && $pet['id'] == $selectedPet['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($pet['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <div class="qr-code-container animate-pulse">
                        <img src="<?php echo $qrUrl; ?>" alt="QR Code" class="qr-image" id="qrImage">
                    </div>

                    <div class="pet-name-display"><?php echo htmlspecialchars($selectedPet['nombre']); ?></div>
                    <p style="color: var(--p-text-muted); margin-bottom: 35px; font-weight: 500;">Escanea este código para ver el perfil médico completo</p>

                    <div style="display: flex; gap: 20px; justify-content: center; flex-wrap: wrap;">
                        <a href="<?php echo $qrUrl; ?>" download="qr-<?php echo $selectedPet['nombre']; ?>.png" class="btn-premium-action btn-primary-gradient" target="_blank">
                            <i class="fas fa-download"></i> Descargar QR
                        </a>
                        <button class="btn-premium-action btn-glass-action" onclick="compartirPerfil('<?php echo $profileUrl; ?>')">
                            <i class="fas fa-share-alt"></i> Compartir Link
                        </button>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function compartirPerfil(url) {
            if (navigator.share) {
                navigator.share({
                    title: 'Perfil de Mascota - RUGAL',
                    text: 'Mira el perfil de mi mascota en RUGAL',
                    url: url
                })
                .catch((error) => console.log('Error compartiendo', error));
            } else {
                // Fallback: Copiar al portapapeles
                navigator.clipboard.writeText(url).then(() => {
                    alert('Enlace copiado al portapapeles');
                });
            }
        }
    </script>
</body>
</html>
