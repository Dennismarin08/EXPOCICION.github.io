<?php
require_once 'db.php';
require_once 'puntos-functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit;
}

$userId = $_SESSION['user_id'];
$puntosInfo = obtenerPuntosUsuario($userId);
$nivelInfo = obtenerInfoNivel($puntosInfo['nivel']);
$progreso = calcularProgresoNivel($puntosInfo['total_puntos_ganados']);
$historial = obtenerHistorialPuntos($userId, 50); // Últimos 50 movimientos
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Puntos - RUGAL</title>
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="css/common-dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .points-hero {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 20px; padding: 40px; color: white;
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 30px; position: relative; overflow: hidden;
        }
        .points-hero::after {
            content: ''; position: absolute; right: -20px; bottom: -20px;
            font-size: 180px; font-family: "Font Awesome 6 Free"; font-weight: 900;
            content: "\f005"; color: rgba(255,255,255,0.1); transform: rotate(-15deg);
        }
        .hero-content { position: relative; z-index: 2; }
        .points-big { font-size: 56px; font-weight: 900; line-height: 1; margin-bottom: 5px; }
        .level-badge {
            background: rgba(255,255,255,0.2); padding: 8px 16px; border-radius: 30px;
            font-weight: 700; display: inline-flex; align-items: center; gap: 8px;
            backdrop-filter: blur(5px); margin-bottom: 20px;
        }
        
        .progress-container { background: rgba(0,0,0,0.2); height: 12px; border-radius: 10px; width: 100%; max-width: 400px; overflow: hidden; margin-top: 15px; }
        .progress-bar { height: 100%; background: #ffd700; border-radius: 10px; transition: width 1s ease; }
        
        .history-card { background: white; border-radius: 20px; padding: 25px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .history-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #f1f5f9; }
        .history-title { font-size: 18px; font-weight: 700; color: #1e293b; }
        
        .history-list { display: flex; flex-direction: column; gap: 15px; }
        .history-item {
            display: flex; align-items: center; justify-content: space-between;
            padding: 15px; background: #f8fafc; border-radius: 12px;
            transition: transform 0.2s;
        }
        .history-item:hover { transform: translateX(5px); background: #f1f5f9; }
        
        .item-left { display: flex; align-items: center; gap: 15px; }
        .item-icon {
            width: 45px; height: 45px; border-radius: 12px; display: flex;
            align-items: center; justify-content: center; font-size: 20px;
        }
        .icon-ganado { background: #dcfce7; color: #166534; }
        .icon-canjeado { background: #fee2e2; color: #991b1b; }
        .icon-revocado { background: #f1f5f9; color: #64748b; }
        
        .item-info h4 { margin: 0 0 4px 0; font-size: 15px; color: #1e293b; }
        .item-date { font-size: 12px; color: #64748b; }
        
        .item-points { font-size: 18px; font-weight: 800; }
        .points-plus { color: #166534; }
        .points-minus { color: #991b1b; }
        
        .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 30px; }
        .stat-box { background: white; padding: 20px; border-radius: 15px; text-align: center; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
        .stat-val { font-size: 28px; font-weight: 800; color: #1e293b; }
        .stat-lbl { font-size: 13px; color: #64748b; text-transform: uppercase; font-weight: 600; }
        
        @media (max-width: 768px) {
            .points-hero { flex-direction: column; text-align: center; }
            .stats-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <header class="header">
            <div class="header-left">
                <h1 class="page-title">Mis Puntos</h1>
                <div class="breadcrumb">
                    <span>Comunidad</span>
                    <i class="fas fa-chevron-right"></i>
                    <span>Historial de Puntos</span>
                </div>
            </div>
            <div class="header-right">
                <button class="btn-add" onclick="window.location.href='recompensas.php'">
                    <i class="fas fa-gift"></i> Canjear Puntos
                </button>
            </div>
        </header>

        <div class="content-wrapper">
            <!-- Hero Section -->
            <div class="points-hero">
                <div class="hero-content">
                    <div class="level-badge">
                        <?php echo $nivelInfo['icono']; ?> <?php echo $nivelInfo['nombre']; ?>
                    </div>
                    <div class="points-big"><?php echo number_format($puntosInfo['puntos']); ?> PTS</div>
                    <p style="opacity: 0.9; margin-bottom: 10px;">Disponibles para canjear</p>
                    
                    <?php if ($progreso['siguiente_nivel']): ?>
                        <div style="font-size: 14px; margin-bottom: 5px;">
                            Progreso al Nivel <?php echo $progreso['siguiente_nivel']; ?>: <?php echo $progreso['progreso']; ?>%
                        </div>
                        <div class="progress-container">
                            <div class="progress-bar" style="width: <?php echo $progreso['progreso']; ?>%"></div>
                        </div>
                        <div style="font-size: 12px; margin-top: 5px; opacity: 0.8;">
                            Faltan <?php echo number_format($progreso['puntos_faltantes']); ?> puntos para subir de nivel
                        </div>
                    <?php else: ?>
                        <div style="font-weight: bold; margin-top: 10px;">¡Has alcanzado el nivel máximo! 🏆</div>
                    <?php endif; ?>
                </div>
                <div style="font-size: 100px; opacity: 0.2;">
                    <i class="fas fa-trophy"></i>
                </div>
            </div>

            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-box">
                    <div class="stat-val" style="color: #667eea;"><?php echo number_format($puntosInfo['total_puntos_ganados']); ?></div>
                    <div class="stat-lbl">Total Ganado (Histórico)</div>
                </div>
                <div class="stat-box">
                    <div class="stat-val" style="color: #10b981;"><?php echo count($historial); ?></div>
                    <div class="stat-lbl">Movimientos</div>
                </div>
                <div class="stat-box">
                    <div class="stat-val" style="color: #f59e0b;"><?php echo $nivelInfo['multiplicador']; ?>x</div>
                    <div class="stat-lbl">Multiplicador Actual</div>
                </div>
            </div>

            <!-- Historial -->
            <div class="history-card">
                <div class="history-header">
                    <div class="history-title">Historial de Movimientos</div>
                </div>
                
                <div class="history-list">
                    <?php if (empty($historial)): ?>
                        <div style="text-align: center; padding: 40px; color: #94a3b8;">
                            <i class="fas fa-history" style="font-size: 40px; margin-bottom: 10px; opacity: 0.5;"></i>
                            <p>Aún no tienes movimientos registrados.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($historial as $item): 
                            $esGanado = $item['tipo'] === 'ganado';
                            $icono = $esGanado ? 'fa-arrow-up' : ($item['tipo'] === 'canjeado' ? 'fa-gift' : 'fa-undo');
                            $claseIcono = $esGanado ? 'icon-ganado' : ($item['tipo'] === 'canjeado' ? 'icon-canjeado' : 'icon-revocado');
                            $clasePuntos = $esGanado ? 'points-plus' : 'points-minus';
                            $signo = $esGanado ? '+' : '-';
                        ?>
                        <div class="history-item">
                            <div class="item-left">
                                <div class="item-icon <?php echo $claseIcono; ?>">
                                    <i class="fas <?php echo $icono; ?>"></i>
                                </div>
                                <div class="item-info">
                                    <h4><?php echo htmlspecialchars($item['descripcion']); ?></h4>
                                    <div class="item-date"><?php echo date('d M Y, h:i A', strtotime($item['created_at'])); ?></div>
                                </div>
                            </div>
                            <div class="item-points <?php echo $clasePuntos; ?>">
                                <?php echo $signo . number_format($item['puntos']); ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>