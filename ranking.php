<?php
require_once 'db.php';
require_once 'puntos-functions.php';

// Verificar sesión
if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit;
}

$userId = $_SESSION['user_id'];
$user = getUsuario($userId);

// Obtener ranking general (Top 20)
$stmt = $pdo->query("
    SELECT u.id, u.nombre, u.puntos, u.total_puntos_ganados, u.nivel, u.foto_perfil,
           (SELECT COUNT(*) FROM mascotas m WHERE m.user_id = u.id) as total_mascotas
    FROM usuarios u
    WHERE u.rol = 'usuario'
    ORDER BY u.total_puntos_ganados DESC
    LIMIT 20
");
$ranking = $stmt->fetchAll();

// Obtener mi posición
$stmt = $pdo->prepare("
    SELECT COUNT(*) + 1 as posicion
    FROM usuarios 
    WHERE rol = 'usuario' AND total_puntos_ganados > (
        SELECT total_puntos_ganados FROM usuarios WHERE id = ?
    )
");
$stmt->execute([$userId]);
$miPosicion = $stmt->fetchColumn();

// Obtener info de puntos mía
$misPuntos = obtenerPuntosUsuario($userId);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ranking Comunidad - RUGAL</title>
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="dashboard-extra.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .ranking-header {
            background: var(--primary-grad);
            color: white;
            padding: 50px 40px;
            border-radius: 24px;
            margin-bottom: 30px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        .podium {
            display: flex;
            justify-content: center;
            align-items: flex-end;
            gap: 20px;
            margin-top: 40px;
        }
        .podium-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }
        .podium-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            border: 4px solid white;
            background: #e2e8f0;
            overflow: hidden;
            margin-bottom: 10px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .podium-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .podium-1 .podium-avatar {
            width: 100px;
            height: 100px;
            border-color: #ffd700;
        }
        .podium-box {
            width: 120px;
            border-radius: 12px 12px 0 0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            font-weight: 800;
            color: white;
        }
        .podium-1 .podium-box { height: 120px; background: rgba(255,255,255,0.2); }
        .podium-2 .podium-box { height: 90px; background: rgba(255,255,255,0.15); }
        .podium-3 .podium-box { height: 70px; background: rgba(255,255,255,0.1); }
        
        .ranking-table-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
        }
        .ranking-row {
            display: flex;
            align-items: center;
            padding: 15px 25px;
            border-bottom: 1px solid #f1f5f9;
            transition: all 0.3s;
        }
        .ranking-row:hover {
            background: #f8fafc;
        }
        .ranking-row.me {
            background: #f0f7ff;
            border-left: 4px solid #667eea;
        }
        .col-pos { width: 50px; font-weight: 800; color: #64748b; font-size: 18px; }
        .col-user { flex: 1; display: flex; align-items: center; gap: 15px; }
        .user-mini-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: #64748b;
        }
        .col-points { width: 150px; text-align: right; font-weight: 700; color: #1e293b; }
        .col-level { width: 120px; text-align: center; }
    </style>
    <link rel="stylesheet" href="css/responsive.css">
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>


    <div class="main-content">
        <header class="header">
            <div class="header-left">
                <h1 class="page-title">Ranking General</h1>
                <div class="breadcrumb"><span>Comunidad</span> <i class="fas fa-chevron-right"></i> <span>Top Usuarios</span></div>
            </div>
            <div class="header-right">
                <div class="glass-card" style="padding: 10px 20px; background: white; border: 1px solid #e2e8f0;">
                    <i class="fas fa-trophy" style="color: #ffd700;"></i> Mi Lugar: <strong>#<?php echo $miPosicion; ?></strong>
                </div>
            </div>
        </header>

        <div class="content-wrapper">
            <div class="ranking-header animate-up">
                <h2 style="font-size: 32px; font-weight: 800; margin-bottom: 10px;">¡Compite con la Comunidad! 🐾</h2>
                <p style="opacity: 0.9;">Gana puntos completando tareas y escalas posiciones para obtener beneficios exclusivos.</p>
                
                <div class="podium">
                    <?php if (isset($ranking[1])): ?>
                    <div class="podium-item podium-2">
                        <div class="podium-avatar">
                            <?php if ($ranking[1]['foto_perfil']): ?>
                                <img src="uploads/<?php echo $ranking[1]['foto_perfil']; ?>">
                            <?php else: ?>
                                <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:32px;background:#cbd5e1;color:white;">
                                    <?php echo strtoupper(substr($ranking[1]['nombre'], 0, 1)); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div style="color: white; font-weight: 600; margin-bottom: 5px;"><?php echo explode(' ', $ranking[1]['nombre'])[0]; ?></div>
                        <div class="podium-box">2</div>
                    </div>
                    <?php endif; ?>

                    <?php if (isset($ranking[0])): ?>
                    <div class="podium-item podium-1">
                        <div class="podium-avatar">
                             <?php if ($ranking[0]['foto_perfil']): ?>
                                <img src="uploads/<?php echo $ranking[0]['foto_perfil']; ?>">
                            <?php else: ?>
                                <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:40px;background:#ffd700;color:white;">
                                    <?php echo strtoupper(substr($ranking[0]['nombre'], 0, 1)); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div style="color: white; font-weight: 800; margin-bottom: 5px;"><?php echo explode(' ', $ranking[0]['nombre'])[0]; ?></div>
                        <div class="podium-box">1</div>
                    </div>
                    <?php endif; ?>

                    <?php if (isset($ranking[2])): ?>
                    <div class="podium-item podium-3">
                        <div class="podium-avatar">
                             <?php if ($ranking[2]['foto_perfil']): ?>
                                <img src="uploads/<?php echo $ranking[2]['foto_perfil']; ?>">
                            <?php else: ?>
                                <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:32px;background:#94a3b8;color:white;">
                                    <?php echo strtoupper(substr($ranking[2]['nombre'], 0, 1)); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div style="color: white; font-weight: 600; margin-bottom: 5px;"><?php echo explode(' ', $ranking[2]['nombre'])[0]; ?></div>
                        <div class="podium-box">3</div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="ranking-table-card animate-up" style="animation-delay: 0.2s;">
                <div class="ranking-row" style="background: #f1f5f9; font-weight: 700; font-size: 13px; color: #64748b; text-transform: uppercase; letter-spacing: 1px;">
                    <div class="col-pos">Pos</div>
                    <div class="col-user">Usuario</div>
                    <div class="col-level">Nivel</div>
                    <div class="col-points">Total Puntos</div>
                </div>

                <?php foreach ($ranking as $index => $row): ?>
                <div class="ranking-row <?php echo $row['id'] == $userId ? 'me' : ''; ?>">
                    <div class="col-pos">#<?php echo $index + 1; ?></div>
                    <div class="col-user">
                        <div class="user-mini-avatar">
                            <?php if ($row['foto_perfil']): ?>
                                <img src="uploads/<?php echo $row['foto_perfil']; ?>" style="width:100%;height:100%;border-radius:50%;object-fit:cover;">
                            <?php else: ?>
                                <?php echo strtoupper(substr($row['nombre'], 0, 1)); ?>
                            <?php endif; ?>
                        </div>
                        <div>
                            <div style="font-weight: 700; color: #1e293b;">
                                <?php echo htmlspecialchars($row['nombre']); ?>
                                <?php if ($row['id'] == $userId): ?> <span style="font-size: 10px; background: #667eea; color: white; padding: 2px 6px; border-radius: 4px; margin-left: 5px;">TÚ</span> <?php endif; ?>
                            </div>
                            <div style="font-size: 12px; color: #64748b;"><?php echo $row['total_mascotas']; ?> Mascotas</div>
                        </div>
                    </div>
                    <div class="col-level">
                        <span class="badge-tipo badge-premium" style="font-size: 10px;">Nivel <?php echo ucfirst($row['nivel']); ?></span>
                    </div>
                    <div class="col-points">
                        <i class="fas fa-star" style="color: #ffd700;"></i> <?php echo number_format($row['total_puntos_ganados']); ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</body>
</html>
