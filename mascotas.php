<?php
require_once 'db.php';
require_once 'puntos-functions.php';
require_once 'includes/salud_functions.php';
require_once 'includes/plan_salud_mensual_functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit;
}

$user = getUsuario($_SESSION['user_id']);
$userId = $_SESSION['user_id'];
$nivelInfo = obtenerInfoNivel($user['nivel'] ?? 'bronce');

// Obtener todas las mascotas del usuario
$stmt = $pdo->prepare("SELECT * FROM mascotas WHERE user_id = ?");
$stmt->execute([$userId]);
$mascotas = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Mascotas - RUGAL</title>
    <link rel="icon" href="assets/images/logo.png" type="image/png">
    <?php include 'pwa-head.php'; ?>
    <!-- Sistema de Diseño Unificado -->
    <link rel="stylesheet" href="css/themes.css">
    <link rel="stylesheet" href="css/design-system.css">
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="css/common-dashboard.css">
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
            --bg-dark: #0f172a;
        }

        body {
            background-color: var(--bg-dark);
            background-image: radial-gradient(circle at 50% -20%, rgba(110, 106, 217, 0.15) 0%, transparent 50%);
            color: #f8fafc;
        }

        .mascotas-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
            gap: 24px;
            margin-top: 30px;
        }

        .pet-card-desktop, .pet-card-mobile {
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--p-border);
            border-radius: 28px;
            overflow: hidden;
            box-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.5);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            position: relative;
            margin-bottom: 20px;
        }

        .pet-card-desktop:hover, .pet-card-mobile:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px -15px rgba(110, 106, 217, 0.3);
            border-color: rgba(110, 106, 217, 0.4);
        }

        .pet-image-section-desktop, .pet-image-section {
            height: 250px;
            position: relative;
            background: rgba(15, 23, 42, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .pet-main-img-desktop, .pet-main-img {
            max-width: 100%;
            max-height: 100%;
            width: auto;
            height: auto;
            object-fit: contain;
            transition: transform 0.5s ease;
        }

        .pet-card-desktop:hover .pet-main-img-desktop, 
        .pet-card-mobile:hover .pet-main-img {
            transform: scale(1.05);
        }

        .pet-name-overlay-desktop, .pet-name-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 25px 20px;
            background: linear-gradient(to top, rgba(15, 23, 42, 0.95), transparent);
            z-index: 5;
        }

        .pet-name-overlay-desktop h2, .pet-name-overlay h2 {
            font-size: 24px;
            font-weight: 800;
            color: white;
            margin: 0;
            letter-spacing: -0.5px;
            text-shadow: 0 2px 10px rgba(0,0,0,0.5);
        }

        .pet-info-section-desktop, .pet-info-section {
            padding: 24px;
            position: relative;
            z-index: 2;
        }

        .info-row-desktop, .info-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 12px;
        }

        .info-item-desktop, .info-item {
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 16px;
            padding: 12px 16px;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: all 0.3s ease;
        }

        .info-item-desktop:hover, .info-item:hover {
            background: rgba(255, 255, 255, 0.08);
            border-color: rgba(255, 255, 255, 0.15);
            transform: translateY(-2px);
        }

        .info-item-desktop.full-width-desktop, .info-item.full-width {
            grid-column: span 2;
        }

        .info-label-desktop, .info-label {
            font-size: 11px;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 1px;
            display: block;
        }

        .info-value-desktop, .info-value {
            font-size: 15px;
            font-weight: 700;
            color: #f1f5f9;
        }

        .pet-action-buttons-desktop, .pet-action-buttons {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-top: 20px;
        }

        .btn-edit-profile-desktop, .btn-qr-code-desktop, .btn-edit-profile, .btn-qr-code {
            padding: 14px;
            border-radius: 16px;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            text-decoration: none;
            font-size: 14px;
        }

        .btn-edit-profile-desktop, .btn-edit-profile {
            background: var(--p-gradient);
            color: white;
            box-shadow: 0 8px 20px -5px rgba(110, 106, 217, 0.4);
        }

        .btn-qr-code-desktop, .btn-qr-code {
            background: rgba(255, 255, 255, 0.05);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
        }

        .btn-edit-profile-desktop:hover, .btn-edit-profile:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 25px -5px rgba(110, 106, 217, 0.6);
            filter: brightness(1.1);
        }

        .btn-qr-code-desktop:hover, .btn-qr-code:hover {
            transform: translateY(-4px);
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.3);
        }

        .pet-qr-badge-desktop {
            position: absolute;
            top: 20px;
            right: 20px;
            width: 44px;
            height: 44px;
            background: var(--p-gradient);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            z-index: 10;
            box-shadow: 0 8px 16px rgba(110, 106, 217, 0.3);
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .pet-qr-badge-desktop:hover {
            transform: scale(1.1) rotate(5deg);
        }

        /* Empty state premium */
        .empty-state {
            text-align: center;
            padding: 80px 20px;
        }
        .empty-state i {
            font-size: 72px;
            color: var(--p-accent);
            opacity: 0.5;
            margin-bottom: 25px;
            display: block;
        }

        /* Fix para visibilidad de cabecera */
        .page-title {
            color: #ffffff !important;
            font-weight: 800 !important;
            font-size: 2rem !important;
            margin-bottom: 5px !important;
            background: linear-gradient(135deg, #452a5cff 0%, #000000ff 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .breadcrumb, .breadcrumb span, .breadcrumb i {
            color: rgba(255, 255, 255, 0.6) !important;
        }

        @media (max-width: 768px) {
            .mascotas-grid { grid-template-columns: 1fr; }
            .pet-card-mobile .hero h2 { font-size: 20px !important; }
        }
    </style>
</head>
<body class="theme-usuario">
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <header class="header">
            <div class="header-left">
                <h1 class="page-title">Mis Mascotas 🐾</h1>
                <div class="breadcrumb">
                    <span>Dashboard</span> <i class="fas fa-chevron-right"></i> <span>Mascotas</span>
                </div>
            </div>
            
            <div class="header-right">
                <a href="agregar-mascota.php" class="btn-add">
                    <i class="fas fa-plus"></i> Agregar Mascota
                </a>
                <div class="nivel-badge">
                    <?php echo $nivelInfo['icono']; ?> Nivel <?php echo $nivelInfo['nombre']; ?>
                </div>
            </div>
        </header>
        
        <div class="content-wrapper">
            <?php if (empty($mascotas)): ?>
                <div class="card">
                    <div class="empty-state">
                        <i class="fas fa-paw"></i>
                        <p>No tienes mascotas registradas aún.</p>
                        <a href="agregar-mascota.php" class="btn-primary">
                            <i class="fas fa-plus"></i> Registrar mi primera mascota
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <!-- Grid de mascotas (desktop y tablet) -->
                <div class="mascotas-grid desktop-only">
                    <?php foreach ($mascotas as $pet):
                        $planPet = obtenerPlanSaludMensual($pet['id']);
                        $saludInfo = calcularEstadoSaludDisplay($pet, $planPet);
                        $fotoSrc = $pet['foto_perfil'] ? (strpos($pet['foto_perfil'], 'uploads/') === 0 ? $pet['foto_perfil'] : 'uploads/'.$pet['foto_perfil']) : '';
                    ?>
                    <div class="pet-card-desktop" onclick="window.location.href='perfil-mascota.php?id=<?php echo $pet['id']; ?>'">
                        <!-- Imagen principal centrada -->
                        <div class="pet-image-section-desktop">
                            <?php if ($fotoSrc): ?>
                                <img src="<?php echo htmlspecialchars($fotoSrc); ?>" class="pet-main-img-desktop" alt="<?php echo htmlspecialchars($pet['nombre']); ?>">
                            <?php else: ?>
                                <div class="pet-main-placeholder-desktop">
                                    <i class="fas fa-paw"></i>
                                </div>
                            <?php endif; ?>

                            <!-- Overlay con nombre -->
                            <div class="pet-name-overlay-desktop">
                                <h2><?php echo htmlspecialchars($pet['nombre']); ?></h2>
                            </div>

                            <!-- Badge QR -->
                            <a href="qr.php?id=<?php echo $pet['id']; ?>" class="pet-qr-badge-desktop" onclick="event.stopPropagation();">
                                <i class="fas fa-qrcode"></i>
                            </a>
                        </div>

                        <!-- Información detallada -->
                        <div class="pet-info-section-desktop">
                            <div class="pet-basic-info-desktop">
                                <!-- Primera fila: Edad y Peso -->
                                <div class="info-row-desktop">
                                    <div class="info-item-desktop">
                                        <i class="fas fa-birthday-cake" style="color: var(--accent);"></i>
                                        <div class="info-content-desktop">
                                            <span class="info-label-desktop">Edad</span>
                                            <span class="info-value-desktop"><?php echo $pet['edad']; ?> años</span>
                                        </div>
                                    </div>
                                    <div class="info-item-desktop">
                                        <i class="fas fa-weight-hanging" style="color: var(--secondary);"></i>
                                        <div class="info-content-desktop">
                                            <span class="info-label-desktop">Peso</span>
                                            <span class="info-value-desktop"><?php echo $pet['peso']; ?> kg</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Segunda fila: Raza completa -->
                                <div class="info-row-desktop">
                                    <div class="info-item-desktop full-width-desktop">
                                        <i class="fas fa-dna" style="color: var(--primary);"></i>
                                        <div class="info-content-desktop">
                                            <span class="info-label-desktop">Raza</span>
                                            <span class="info-value-desktop"><?php echo htmlspecialchars($pet['raza']); ?></span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Tercera fila: Estado de salud -->
                                <div class="info-row-desktop">
                                    <div class="info-item-desktop full-width-desktop">
                                        <i class="fas <?php echo $saludInfo['icon']; ?>" style="color: <?php echo $saludInfo['color']; ?>;"></i>
                                        <div class="info-content-desktop">
                                            <span class="info-label-desktop">Salud</span>
                                            <span class="info-value-desktop" style="color: <?php echo $saludInfo['color']; ?>;"><?php echo htmlspecialchars($saludInfo['text']); ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Botones de acción -->
                            <div class="pet-action-buttons-desktop">
                                <a href="perfil-mascota.php?id=<?php echo $pet['id']; ?>" class="btn-edit-profile-desktop" onclick="event.stopPropagation();">
                                    <i class="fas fa-user"></i> Ver perfil
                                </a>
                                <a href="qr.php?id=<?php echo $pet['id']; ?>" class="btn-qr-code-desktop" onclick="event.stopPropagation();">
                                    <i class="fas fa-qrcode"></i> QR
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- MOBILE LIST -->
                <div class="mobile-only mascotas-mobile-list">
                    <?php foreach ($mascotas as $pet):
                        $fotoSrc = $pet['foto_perfil'] ? (strpos($pet['foto_perfil'], 'uploads/') === 0 ? $pet['foto_perfil'] : 'uploads/'.$pet['foto_perfil']) : '';
                        $planPet = obtenerPlanSaludMensual($pet['id']);
                        $saludInfo = calcularEstadoSaludDisplay($pet, $planPet);
                    ?>
                    <div class="pet-card-mobile" onclick="window.location.href='perfil-mascota.php?id=<?php echo $pet['id']; ?>'">
                        <!-- Imagen principal centrada -->
                        <div class="pet-image-section">
                            <?php if ($fotoSrc): ?>
                                <img src="<?php echo htmlspecialchars($fotoSrc); ?>" class="pet-main-img" alt="<?php echo htmlspecialchars($pet['nombre']); ?>">
                            <?php else: ?>
                                <div class="pet-main-placeholder">
                                    <i class="fas fa-paw"></i>
                                </div>
                            <?php endif; ?>

                            <!-- Overlay con nombre -->
                            <div class="pet-name-overlay">
                                <h2><?php echo htmlspecialchars($pet['nombre']); ?></h2>
                            </div>

                            <!-- Badge QR -->
                            <a href="qr.php?id=<?php echo $pet['id']; ?>" class="pet-qr-badge-desktop" onclick="event.stopPropagation();">
                                <i class="fas fa-qrcode"></i>
                            </a>
                        </div>

                        <!-- Información detallada -->
                        <div class="pet-info-section">
                            <div class="pet-basic-info">
                                <!-- Primera fila: Edad y Peso -->
                                <div class="info-row">
                                    <div class="info-item">
                                        <i class="fas fa-birthday-cake" style="color: var(--accent);"></i>
                                        <div class="info-content">
                                            <span class="info-label">Edad</span>
                                            <span class="info-value"><?php echo $pet['edad']; ?> años</span>
                                        </div>
                                    </div>
                                    <div class="info-item">
                                        <i class="fas fa-weight-hanging" style="color: var(--secondary);"></i>
                                        <div class="info-content">
                                            <span class="info-label">Peso</span>
                                            <span class="info-value"><?php echo $pet['peso']; ?> kg</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Segunda fila: Raza completa -->
                                <div class="info-row">
                                    <div class="info-item full-width">
                                        <i class="fas fa-dna" style="color: var(--primary);"></i>
                                        <div class="info-content">
                                            <span class="info-label">Raza</span>
                                            <span class="info-value"><?php echo htmlspecialchars($pet['raza']); ?></span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Tercera fila: Estado de salud -->
                                <div class="info-row">
                                    <div class="info-item full-width">
                                        <i class="fas <?php echo $saludInfo['icon']; ?>" style="color: <?php echo $saludInfo['color']; ?>;"></i>
                                        <div class="info-content">
                                            <span class="info-label">Salud</span>
                                            <span class="info-value" style="color: <?php echo $saludInfo['color']; ?>;"><?php echo htmlspecialchars($saludInfo['text']); ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Botones de acción -->
                            <div class="pet-action-buttons">
                                <a href="perfil-mascota.php?id=<?php echo $pet['id']; ?>" class="btn-edit-profile" onclick="event.stopPropagation();">
                                    <i class="fas fa-user"></i> Ver perfil
                                </a>
                                <a href="qr.php?id=<?php echo $pet['id']; ?>" class="btn-qr-code" onclick="event.stopPropagation();">
                                    <i class="fas fa-qrcode"></i> QR
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
