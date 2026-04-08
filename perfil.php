<?php
require_once 'db.php';
require_once 'puntos-functions.php';

// Verificar sesión
if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit;
}

$userId = $_SESSION['user_id'];

// Obtener información del usuario
$user = getUsuario($userId);
$puntosInfo = obtenerPuntosUsuario($userId);
$nivelInfo = obtenerInfoNivel($puntosInfo['nivel']);
$progresoNivel = calcularProgresoNivel($puntosInfo['total_puntos_ganados']);

// Obtener estadísticas
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM mascotas WHERE user_id = ?");
$stmt->execute([$userId]);
$totalMascotas = $stmt->fetch()['total'];

$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM canjes WHERE user_id = ?");
$stmt->execute([$userId]);
$totalCanjes = $stmt->fetch()['total'];

// Obtener historial de puntos
$historial = obtenerHistorialPuntos($userId, 5);

// Obtener mascotas con fotos
$stmt = $pdo->prepare("SELECT id, nombre, foto_perfil, raza FROM mascotas WHERE user_id = ?");
$stmt->execute([$userId]);
$misMascotas = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil - RUGAL</title>
    <link rel="icon" href="assets/images/logo.png" type="image/png">
    <?php include 'pwa-head.php'; ?>
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="css/responsive.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .profile-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px;
            border-radius: 20px;
            margin-bottom: 30px;
        }
        
        .profile-content {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 30px;
            align-items: start;
        }
        
        .profile-avatar-section {
            text-align: center;
        }
        
        .profile-avatar {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            border: 5px solid rgba(255,255,255,0.3);
            object-fit: cover;
            margin-bottom: 20px;
        }
        
        .profile-avatar.default {
            background: rgba(255,255,255,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 60px;
            font-weight: bold;
            margin: 0 auto 20px;
        }
        
        .nivel-badge {
            display: inline-block;
            padding: 10px 20px;
            border-radius: 25px;
            background: rgba(255,255,255,0.2);
            font-size: 24px;
            margin-bottom: 10px;
        }
        
        .puntos-display {
            font-size: 48px;
            font-weight: bold;
            margin: 10px 0;
        }
        
        .progress-bar {
            background: rgba(255,255,255,0.2);
            border-radius: 10px;
            height: 20px;
            overflow: hidden;
            margin: 20px 0;
        }
        
        .progress-fill {
            background: linear-gradient(90deg, #00b09b, #96c93d);
            height: 100%;
            transition: width 0.3s ease;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-top: 20px;
        }
        
        .stat-box {
            background: rgba(255,255,255,0.1);
            padding: 20px;
            border-radius: 15px;
            text-align: center;
        }
        
        .stat-value {
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 14px;
            opacity: 0.9;
        }
        
        .info-section {
            background: white;
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 15px 0;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .info-row:last-child {
            border-bottom: none;
        }
        
        .info-label {
            font-weight: 600;
            color: #64748b;
        }
        
        .info-value {
            color: #1e293b;
        }
        
        .historial-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            background: #f8fafc;
            border-radius: 10px;
            margin-bottom: 10px;
        }
        
        .historial-item.ganado {
            border-left: 4px solid #00b09b;
        }
        
        .historial-item.canjeado {
            border-left: 4px solid #ff7e5f;
        }
        
        .puntos-ganados {
            color: #00b09b;
            font-weight: bold;
        }
        
        .puntos-canjeados {
            color: #ff7e5f;
            font-weight: bold;
        }
        
        .btn-edit {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            margin-top: 20px;
        }
        
        .btn-edit:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }

        @media (max-width: 768px) {
            .profile-header { padding: 28px 20px; text-align: center; }
            .profile-content { grid-template-columns: 1fr; }
            .profile-avatar-section { margin-bottom: 20px; }
            .stats-grid { grid-template-columns: 1fr; gap: 15px; }
            .info-section { padding: 20px; }
            .info-row { flex-direction: column; gap: 5px; align-items: flex-start; }
        }
        @media (max-width: 480px) {
            .profile-header { padding: 20px 15px; }
            .puntos-display { font-size: 36px; }
            .stat-value { font-size: 26px; }
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    
    <!-- Main Content -->
    <div class="main-content">
        <header class="header">
            <div class="header-left">
                <h1 class="page-title">Mi Perfil</h1>
                <div class="breadcrumb">
                    <span>Inicio</span>
                    <i class="fas fa-chevron-right"></i>
                    <span>Mi Perfil</span>
                </div>
            </div>
        </header>
        
        <div class="content-wrapper">
            <!-- Profile Header -->
            <div class="profile-header">
                <div class="profile-content">
                    <div class="profile-avatar-section">
                        <?php if ($user['foto_perfil']): ?>
                            <img src="uploads/<?php echo htmlspecialchars($user['foto_perfil']); ?>" 
                                 alt="<?php echo htmlspecialchars($user['nombre']); ?>"
                                 class="profile-avatar">
                        <?php else: ?>
                            <div class="profile-avatar default">
                                <?php echo strtoupper(substr($user['nombre'], 0, 1)); ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="nivel-badge">
                            <?php echo $nivelInfo['icono']; ?> <?php echo $nivelInfo['nombre']; ?>
                        </div>
                        
                        <button class="btn-edit" onclick="window.location.href='editar-perfil.php'">
                            <i class="fas fa-edit"></i> Editar Perfil
                        </button>
                    </div>
                    
                    <div>
                        <h2 style="margin-bottom: 10px;"><?php echo htmlspecialchars($user['nombre']); ?></h2>
                        <p style="opacity: 0.9; margin-bottom: 20px;">
                            <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($user['email']); ?>
                        </p>
                        
                        <div class="puntos-display">
                            <i class="fas fa-star"></i> <?php echo number_format($puntosInfo['puntos']); ?> puntos
                        </div>
                        
                        <p style="opacity: 0.9; margin-bottom: 10px;">
                            <?php echo $nivelInfo['beneficio']; ?>
                        </p>
                        
                        <?php if ($progresoNivel['siguiente_nivel']): ?>
                        <div>
                            <p style="font-size: 14px; margin-bottom: 5px;">
                                Progreso a <?php echo ucfirst($progresoNivel['siguiente_nivel']); ?>: 
                                <?php echo $progresoNivel['progreso']; ?>%
                            </p>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo $progresoNivel['progreso']; ?>%"></div>
                            </div>
                            <p style="font-size: 14px; opacity: 0.8;">
                                Te faltan <?php echo $progresoNivel['puntos_faltantes']; ?> puntos
                            </p>
                        </div>
                        <?php else: ?>
                        <p style="font-size: 14px; opacity: 0.9;">
                            ¡Has alcanzado el nivel máximo! 🎉
                        </p>
                        <?php endif; ?>
                        
                        <div class="stats-grid">
                            <div class="stat-box">
                                <div class="stat-value"><?php echo number_format($puntosInfo['total_puntos_ganados']); ?></div>
                                <div class="stat-label">Puntos Totales</div>
                            </div>
                            <div class="stat-box">
                                <div class="stat-value"><?php echo $totalMascotas; ?></div>
                                <div class="stat-label">Mascotas</div>
                            </div>
                            <div class="stat-box">
                                <div class="stat-value"><?php echo $totalCanjes; ?></div>
                                <div class="stat-label">Canjes</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <!-- Mis Mascotas -->
                <div class="col-6">
                    <div class="card">
                        <div class="card-header">
                            <h3>Mis Mascotas</h3>
                            <a href="mascotas.php" class="btn-text">Ver todas</a>
                        </div>
                        <div style="padding: 20px; display: flex; gap: 15px; overflow-x: auto;">
                            <?php if (empty($misMascotas)): ?>
                                <p style="text-align: center; color: #64748b; width: 100%;">No tienes mascotas registradas.</p>
                            <?php else: ?>
                                <?php foreach ($misMascotas as $m): ?>
                                    <a href="perfil-mascota.php?id=<?php echo $m['id']; ?>" style="text-decoration: none; text-align: center; min-width: 80px;">
                                        <?php if ($m['foto_perfil']): ?>
                                            <img src="uploads/<?php echo htmlspecialchars($m['foto_perfil']); ?>" 
                                                 style="width: 60px; height: 60px; border-radius: 50%; object-fit: cover; border: 2px solid #764ba2;">
                                        <?php else: ?>
                                            <div style="width: 60px; height: 60px; border-radius: 50%; background: #e2e8f0; display: flex; align-items: center; justify-content: center; margin: 0 auto; color: #64748b;">
                                                <i class="fas fa-paw"></i>
                                            </div>
                                        <?php endif; ?>
                                        <div style="font-size: 12px; margin-top: 5px; color: #1e293b; font-weight: 600;"><?php echo htmlspecialchars($m['nombre']); ?></div>
                                    </a>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Información Personal -->
                <div class="col-6">
                    <div class="card">
                        <div class="card-header">
                            <h3>Información Personal</h3>
                        </div>
                        <div class="info-section">
                            <div class="info-row">
                                <span class="info-label">Nombre</span>
                                <span class="info-value"><?php echo htmlspecialchars($user['nombre']); ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Email</span>
                                <span class="info-value"><?php echo htmlspecialchars($user['email']); ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Teléfono</span>
                                <span class="info-value"><?php echo htmlspecialchars($user['telefono'] ?? 'No registrado'); ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Ciudad</span>
                                <span class="info-value"><?php echo htmlspecialchars($user['ciudad'] ?? 'No registrado'); ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Miembro desde</span>
                                <span class="info-value"><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Historial de Puntos -->
                <div class="col-6">
                    <div class="card">
                        <div class="card-header">
                            <h3>Historial de Puntos</h3>
                            <a href="puntos.php" class="btn-text">Ver todo</a>
                        </div>
                        <div style="padding: 20px;">
                            <?php if (empty($historial)): ?>
                                <p style="text-align: center; color: #64748b; padding: 20px;">
                                    <i class="fas fa-info-circle"></i><br>
                                    Aún no has ganado puntos.<br>
                                    ¡Completa tareas para empezar!
                                </p>
                            <?php else: ?>
                                <?php foreach ($historial as $item): ?>
                                <div class="historial-item <?php echo $item['tipo']; ?>">
                                    <div>
                                        <div style="font-weight: 600;"><?php echo htmlspecialchars($item['descripcion']); ?></div>
                                        <div style="font-size: 12px; color: #64748b;">
                                            <?php echo date('d/m/Y H:i', strtotime($item['created_at'])); ?>
                                        </div>
                                    </div>
                                    <div class="puntos-<?php echo $item['tipo']; ?>">
                                        <?php echo $item['tipo'] === 'ganado' ? '+' : '-'; ?><?php echo $item['puntos']; ?> pts
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
