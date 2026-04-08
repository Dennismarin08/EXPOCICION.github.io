<?php
require_once 'db.php';
require_once 'premium-functions.php';
require_once 'includes/planes_salud_functions.php';
require_once 'includes/comunidad_functions.php';
require_once 'includes/salud_functions.php';
require_once 'includes/dashboard_data_functions.php';
require_once 'puntos-functions.php';

// Verificar login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit;
}

$userId = $_SESSION['user_id'];
$user = getUsuario($userId);
$mascotaId = $_GET['id'] ?? null;

if (!$mascotaId) {
    header('Location: mascotas.php');
    exit;
}

// Obtener datos de la mascota
$stmt = $pdo->prepare("
    SELECT m.*, u.nombre as dueno_nombre 
    FROM mascotas m 
    JOIN usuarios u ON m.user_id = u.id 
    WHERE m.id = ?
");
$stmt->execute([$mascotaId]);
$mascota = $stmt->fetch();

if (!$mascota) {
    header('Location: mascotas.php');
    exit;
}

$esDueno = ($mascota['user_id'] == $userId);
$planSalud = obtenerPlanSalud($mascotaId);
$publicaciones = obtenerPublicacionesMascota($mascotaId);
$pesoActual = obtenerUltimoPeso($mascotaId) ?? $mascota['peso'];
$statsSalud = obtenerEstadisticasMascotaReal($mascotaId);
$isPremium = esPremium($userId);

// Age calculation based on request
$edadDecimal = floatval($mascota['edad'] ?? 0);
$mesesTranscurridos = 0;
if (!empty($mascota['created_at'])) {
    // Using user's formula. Note: this is an approximation.
    $mesesTranscurridos = round((time() - strtotime($mascota['created_at'])) / (30*24*3600));
}
$totalMeses = ($edadDecimal * 12) + $mesesTranscurridos;
$edadAniosCalculada = floor($totalMeses / 12);
$edadMesesCalculada = intval($totalMeses % 12);

// GAMIFICACIÓN
$gamificationInfo = obtenerInfoGamificacion($userId);
$userLevel = $gamificationInfo['nivel'];
$userStreak = $gamificationInfo['racha'];
$userProgress = $gamificationInfo['progreso_porcentaje'];

// HEALTH SCORE
require_once 'includes/plan_salud_mensual_functions.php';
$latestPlan = obtenerPlanSaludMensual($mascotaId);
$healthScore = 85; // Default
if ($latestPlan) {
    // Usar el health score persistente del plan
    $healthScore = intval($latestPlan['health_score'] ?? 85);
    
    // Si el score es 0 o no existe, calcularlo basado en el nivel de alerta
    if ($healthScore == 0) {
        $healthInfo = calcularPuntajeSalud($latestPlan['nivel_alerta'] ?? 'verde', $latestPlan['id']);
        $healthScore = $healthInfo['score'];
    }
} else {
    // Calcular "al vuelo" si no hay plan, basado en el estado básico
    if (strtolower($mascota['estado_salud'] ?? '') === 'excelente') $healthScore = 95;
    elseif (strtolower($mascota['estado_salud'] ?? '') === 'regular') $healthScore = 70;
    else $healthScore = 50;
}

// Estado de salud unificado (plan > peso > BD)
$saludDisplay = calcularEstadoSaludDisplay($mascota, $latestPlan);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil de <?php echo htmlspecialchars($mascota['nombre']); ?> - RUGAL</title>
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="css/themes.css">
    <link rel="stylesheet" href="css/responsive.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --glass-bg: var(--bg-card);
            --glass-border: var(--border-color);
            --primary-gradient: var(--gradient-primary);
        }

        .perfil-hero {
            background: linear-gradient(rgba(15, 23, 42, 0.7), rgba(15, 23, 42, 0.9)), 
                        url('assets/images/bg-pet-profile.jpg');
            background-size: cover;
            background-position: center;
            border-radius: 30px;
            padding: 50px 40px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 40px;
            border: 1px solid var(--glass-border);
            position: relative;
            overflow: hidden;
        }

        .hero-left { flex-shrink: 0; }
        .hero-info { flex: 1; min-width: 0; }

        .pet-portrait {
            width: 220px;
            height: 220px;
            border-radius: 30px;
            object-fit: cover;
            box-shadow: 0 20px 40px rgba(0,0,0,0.5);
            border: 4px solid var(--primary-light);
            background: #1e293b;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 80px;
            color: #475569;
        }

        .hero-info h1 {
            font-size: 48px;
            font-weight: 800;
            margin: 0 0 10px 0;
            background: linear-gradient(to right, #fff, #94a3b8);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 700;
            font-size: 14px;
            text-transform: uppercase;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }

        .status-excelente { background: #10b981; color: white; }
        .status-regular { background: #f59e0b; color: white; }
        .status-revision { background: #3b82f6; color: white; }

        .profile-tabs {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
            border-bottom: 1px solid var(--glass-border);
            padding-bottom: 5px;
            flex-wrap: wrap;
        }

        .tab-btn {
            padding: 12px 25px;
            background: transparent;
            border: none;
            color: #94a3b8;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            position: relative;
            border-radius: 10px;
        }
        .tab-btn:hover { color: #cbd5e1; background: rgba(255,255,255,0.05); }
        .tab-btn.active {
            color: #fff;
        }
        .tab-btn.active::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 100%;
            height: 3px;
            background: var(--primary-gradient);
            border-radius: 3px;
        }

        .tab-content { display: none; }
        .tab-content.active { display: block; animation: fadeIn 0.4s ease; }

        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

        .stats-row {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }

        .stat-card-premium {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 25px;
            text-align: center;
            flex: 1;
            min-width: 140px;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .stat-card-premium:hover { transform: translateY(-5px); background: rgba(255,255,255,0.05); box-shadow: 0 10px 25px rgba(0,0,0,0.2); }

        .details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }

        .pillar-box {
            background: rgba(255,255,255,0.02);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 15px;
            border-left: 4px solid var(--primary-light);
        }

        .plan-inner-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
            flex-wrap: wrap;
        }

        .post-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
        }

        .pet-post-card {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            overflow: hidden;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .pet-post-card:hover { transform: translateY(-4px); box-shadow: 0 12px 24px rgba(0,0,0,0.15); }

        .pet-post-img { width: 100%; height: 250px; object-fit: contain; background: var(--bg-card); }

        .btn-edit-hero {
            position: absolute;
            top: 30px;
            right: 40px;
            background: rgba(255,255,255,0.1);
            padding: 10px 20px;
            border-radius: 12px;
            text-decoration: none;
            color: white;
            font-weight: 600;
            border: 1px solid var(--glass-border);
            transition: background 0.2s, transform 0.2s;
        }
        .btn-edit-hero:hover { background: rgba(255,255,255,0.2); transform: scale(1.02); }

        /* Pet Info Grid in Plan Tab */
        .pet-info-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 25px;
        }

        .pet-info-item {
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.1) 0%, rgba(16, 185, 129, 0.05) 100%);
            border: 1px solid rgba(59, 130, 246, 0.2);
            border-radius: 16px;
            padding: 20px;
            text-align: center;
        }

        .pet-info-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            background: rgba(59, 130, 246, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 12px;
            font-size: 24px;
            color: #3b82f6;
        }

        .pet-info-label {
            font-size: 12px;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }

        .pet-info-value {
            font-weight: 700;
            color: #f8fafc;
            font-size: 20px;
        }

        /* Rutinas Section */
        .rutinas-section {
            margin-top: 25px;
        }

        .rutinas-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }

        .rutina-card {
            background: rgba(255,255,255,0.02);
            border: 1px solid var(--glass-border);
            border-radius: 16px;
            padding: 20px;
        }

        .rutina-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }

        .rutina-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }

        .rutina-icon.diaria { background: rgba(16, 185, 129, 0.1); color: #10b981; }
        .rutina-icon.semanal { background: rgba(245, 158, 11, 0.1); color: #f59e0b; }
        .rutina-icon.mensual { background: rgba(139, 92, 246, 0.1); color: #8b5cf6; }

        .rutina-title {
            font-weight: 700;
            color: #f8fafc;
            font-size: 16px;
        }

        .rutina-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .rutina-list li {
            padding: 10px 0;
            border-bottom: 1px solid rgba(255,255,255,0.05);
            font-size: 14px;
            color: #cbd5e1;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .rutina-list li:last-child {
            border-bottom: none;
        }

        .rutina-list li i {
            color: #10b981;
            font-size: 12px;
        }

        /* Responsive Perfil Mascota */
        @media (max-width: 768px) {
            .perfil-hero {
                flex-direction: column;
                padding: 30px 20px;
                border-radius: 20px;
                text-align: center;
            }
            .hero-left { order: 1; }
            .hero-info { order: 2; }
            .pet-portrait {
                width: 160px;
                height: 160px;
                border-radius: 24px;
                font-size: 60px;
            }
            .hero-info h1 { font-size: 32px; }
            .hero-info > div[style*="display: flex"] {
                flex-wrap: wrap;
                justify-content: center;
                gap: 10px;
            }
            .btn-edit-hero {
                position: static;
                display: inline-block;
                margin-bottom: 15px;
            }
            .stats-row {
                flex-direction: column;
                gap: 15px;
            }
            .stat-card-premium { min-width: 100%; }
            .details-grid { grid-template-columns: 1fr; gap: 20px; }
            .profile-tabs { gap: 8px; }
            .tab-btn { padding: 10px 16px; font-size: 14px; }
            .plan-inner-tabs { gap: 8px; }
            .plan-inner-tabs .tab-btn { padding: 8px 14px; font-size: 13px; }
            .post-grid { grid-template-columns: 1fr; }
            
            /* Pet Info Grid Responsive */
            .pet-info-grid { grid-template-columns: 1fr; gap: 15px; }
            .rutinas-grid { grid-template-columns: 1fr; gap: 15px; }
        }
        @media (max-width: 480px) {
            .perfil-hero { padding: 24px 16px; }
            .pet-portrait { width: 120px; height: 120px; font-size: 48px; }
            .hero-info h1 { font-size: 26px; }
            .tab-btn { padding: 8px 12px; font-size: 13px; }
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <header class="header">
            <div class="header-left">
                <h1 class="page-title"><?php echo htmlspecialchars($mascota['nombre']); ?></h1>
                <div class="breadcrumb">
                    <a href="mascotas.php" style="color: inherit; text-decoration: none;">Mis Mascotas</a>
                    <i class="fas fa-chevron-right"></i>
                    <span>Perfil</span>
                </div>
            </div>
            <div class="header-right">
                <?php if ($esDueno): ?>
                    <a href="editar-mascota.php?id=<?php echo $mascotaId; ?>" class="btn-add"><i class="fas fa-edit"></i> Editar</a>
                <?php endif; ?>
            </div>
        </header>
        <div class="content-wrapper">
            <!-- Hero Section -->
            <div class="perfil-hero">
                <?php if ($esDueno): ?>
                    <a href="editar-mascota.php?id=<?php echo $mascotaId; ?>" class="btn-edit-hero">
                        <i class="fas fa-edit"></i> Editar Perfil
                    </a>
                <?php endif; ?>

                <div class="hero-left">
                    <?php if (!empty($mascota['foto_perfil'])): 
                        $fotoUrl = htmlspecialchars($mascota['foto_perfil']);
                        if (strpos($fotoUrl, 'uploads/') !== 0) $fotoUrl = 'uploads/' . $fotoUrl;
                    ?>
                        <img src="<?php echo $fotoUrl; ?>" class="pet-portrait" alt="Foto">
                    <?php else: ?>
                        <div class="pet-portrait"><i class="fas fa-paw"></i></div>
                    <?php endif; ?>
                </div>

                <div class="hero-info">
                    <div style="color: var(--primary-light); font-weight: 700; margin-bottom: 5px; text-transform: uppercase; letter-spacing: 2px;">
                        <?php echo htmlspecialchars($mascota['raza']); ?>
                    </div>
                    <h1><?php echo htmlspecialchars($mascota['nombre']); ?></h1>
                    
                    <div style="display: flex; gap: 15px; align-items: center; margin-top: 20px;">
                        <div class="status-badge" style="background: <?php echo $saludDisplay['color']; ?>; color: white;">
                            <i class="fas fa-heartbeat"></i> Salud: <?php echo htmlspecialchars($saludDisplay['text']); ?>
                        </div>
                        <div style="background: rgba(255,255,255,0.1); padding: 8px 16px; border-radius: 20px; font-size: 14px; font-weight: 600; display: flex; align-items: center; gap: 8px;">
                             <i class="fas fa-calendar"></i> <?php echo $edadAniosCalculada; ?>a <?php echo $edadMesesCalculada; ?>m
                        </div>
                        <div style="background: rgba(255,255,255,0.1); padding: 8px 16px; border-radius: 20px; font-size: 14px; font-weight: 600; display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-venus-mars"></i> <?php echo ucfirst($mascota['sexo']); ?>
                        </div>
                    </div>

                    <!-- GAMIFICATION & HEALTH SCORE BAR -->
                    <div style="margin-top: 25px; padding-top: 20px; border-top: 1px solid rgba(255,255,255,0.1); display: flex; gap: 30px; flex-wrap: wrap;">
                        
                        <!-- User Level -->
                        <div style="flex: 1; min-width: 200px;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 5px; font-size: 14px;">
                                <span style="color: #cbd5e1;"><i class="fas fa-medal" style="color: #FFD700;"></i> Nivel Cuidador <?php echo $userLevel; ?></span>
                                <span style="color: #94a3b8;"><?php echo intval($userProgress); ?>%</span>
                            </div>
                            <div style="height: 8px; background: rgba(255,255,255,0.1); border-radius: 4px; overflow: hidden;">
                                <div style="height: 100%; width: <?php echo $userProgress; ?>%; background: linear-gradient(90deg, #3b82f6, #8b5cf6); border-radius: 4px;"></div>
                            </div>
                            <?php if($userStreak > 0): ?>
                                <div style="font-size: 12px; margin-top: 5px; color: #fbbf24;">
                                    <i class="fas fa-fire"></i> Racha: <?php echo $userStreak; ?> días <?php if($userStreak >= 8) echo "(x2 XP Activo)"; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Health Score -->
                        <div style="flex: 1; min-width: 200px;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 5px; font-size: 14px;">
                                <span style="color: #cbd5e1;"><i class="fas fa-heart" style="color: #ef4444;"></i> Health Score</span>
                                <span style="font-weight: 700; color: <?php echo ($healthScore >= 80 ? '#10b981' : ($healthScore >= 50 ? '#f59e0b' : '#ef4444')); ?>;"><?php echo $healthScore; ?>%</span>
                            </div>
                            <div style="height: 8px; background: rgba(255,255,255,0.1); border-radius: 4px; overflow: hidden;">
                                <div style="height: 100%; width: <?php echo $healthScore; ?>%; background: <?php echo ($healthScore >= 80 ? '#10b981' : ($healthScore >= 50 ? '#f59e0b' : '#ef4444')); ?>; border-radius: 4px;"></div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <!-- Tabs -->
            <div class="profile-tabs">
                <button class="tab-btn active" onclick="openTab(event, 'info')">Información General</button>
                <button class="tab-btn" onclick="openTab(event, 'salud')">Plan de Salud</button>
                <button class="tab-btn" onclick="openTab(event, 'feed')">Publicaciones</button>
            </div>

            <!-- Tab: Info -->
            <div id="info" class="tab-content active">
                <div class="stats-row">
                    <div class="stat-card-premium">
                        <i class="fas fa-weight" style="font-size: 24px; color: #10b981; margin-bottom: 10px;"></i>
                        <div style="font-size: 12px; opacity: 0.7;">Peso Actual</div>
                        <div style="font-size: 24px; font-weight: 800;"><?php echo $pesoActual; ?> kg</div>
                    </div>
                    <div class="stat-card-premium">
                        <i class="fas fa-venus-mars" style="font-size: 24px; color: #f59e0b; margin-bottom: 10px;"></i>
                        <div style="font-size: 12px; opacity: 0.7;">Sexo</div>
                        <div style="font-size: 24px; font-weight: 800;"><?php echo ucfirst($mascota['sexo']); ?></div>
                    </div>
                    <div class="stat-card-premium">
                        <i class="fas fa-syringe" style="font-size: 24px; color: #8b5cf6; margin-bottom: 10px;"></i>
                        <div style="font-size: 12px; opacity: 0.7;">Próxima Vacuna</div>
                        <div style="font-size: 24px; font-weight: 800;"><?php echo $statsSalud['next_vaccine']; ?></div>
                    </div>
                </div>

                <div class="card" style="padding: 30px;">
                    <h3><i class="fas fa-circle-info"></i> Detalles de la mascota</h3>
                    <div class="details-grid" style="margin-top: 25px;">
                        <div>
                            <div style="font-size: 13px; opacity: 0.6;">Color</div>
                            <div style="font-weight: 700;"><?php echo htmlspecialchars($mascota['color'] ?: 'No especificado'); ?></div>
                        </div>
                        <div>
                            <div style="font-size: 13px; opacity: 0.6;">Esterilizado</div>
                            <div style="font-weight: 700;"><?php echo $mascota['esterilizado'] ? 'Sí' : 'No'; ?></div>
                        </div>
                        <div>
                            <div style="font-size: 13px; opacity: 0.6;">Alergias</div>
                            <div style="font-weight: 700;"><?php echo htmlspecialchars($mascota['alergias'] ?: 'Ninguna conocida'); ?></div>
                        </div>
                        <div>
                            <div style="font-size: 13px; opacity: 0.6;">Vive en</div>
                            <div style="font-weight: 700;"><?php echo ucfirst($mascota['vive_en'] ?: 'No especificado'); ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab: Salud -->
            <div id="salud" class="tab-content">
                <?php if ($planSalud || $latestPlan): ?>
                    <div class="card" style="padding: 20px;">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
                            <h2 style="margin:0;">Plan de Salud Mensual</h2>
                            <a href="plan-salud-mensual.php?mascota_id=<?php echo $mascotaId; ?>" style="background: rgba(16, 185, 129, 0.1); color: #10b981; padding: 5px 12px; border-radius: 12px; font-weight:700; text-decoration: none; transition: transform 0.2s;"><i class="fas fa-check-circle"></i> Activo</a>
                        </div>

                        <!-- Pet Info Grid - Raza, Edad, Peso -->
                        <div class="pet-info-grid">
                            <div class="pet-info-item">
                                <div class="pet-info-icon">
                                    <i class="fas fa-paw"></i>
                                </div>
                                <div class="pet-info-label">Raza</div>
                                <div class="pet-info-value"><?php echo htmlspecialchars($mascota['raza'] ?? 'No especificada'); ?></div>
                            </div>
                            <div class="pet-info-item">
                                <div class="pet-info-icon" style="background: rgba(245, 158, 11, 0.1); color: #f59e0b;">
                                    <i class="fas fa-calendar"></i>
                                </div>
                                <div class="pet-info-label">Edad</div>
                                <div class="pet-info-value"><?php echo $edadAniosCalculada; ?>a <?php echo $edadMesesCalculada; ?>m</div>
                            </div>
                            <div class="pet-info-item">
                                <div class="pet-info-icon" style="background: rgba(16, 185, 129, 0.1); color: #10b981;">
                                    <i class="fas fa-weight"></i>
                                </div>
                                <div class="pet-info-label">Peso</div>
                                <div class="pet-info-value"><?php echo $pesoActual; ?> kg</div>
                            </div>
                        </div>

                        <!-- Inner tabs for plan sections -->
                        <div class="plan-inner-tabs">
                            <button class="tab-btn active" onclick="openPlanTab(event,'objetivo')">Objetivo</button>
                            <button class="tab-btn" onclick="openPlanTab(event,'alimentacion')">Alimentación</button>
                            <button class="tab-btn" onclick="openPlanTab(event,'ejercicio')">Ejercicio</button>
                            <button class="tab-btn" onclick="openPlanTab(event,'diaria')">Rutina Diaria</button>
                            <button class="tab-btn" onclick="openPlanTab(event,'semanal')">Rutina Semanal</button>
                            <button class="tab-btn" onclick="openPlanTab(event,'mensual')">Rutina Mensual</button>
                            <button class="tab-btn" onclick="openPlanTab(event,'recomendaciones')">Recomendaciones</button>
                        </div>

                        <div id="plan-objetivo" class="tab-content active" style="padding:15px; background:rgba(255,255,255,0.02); border-radius:8px; margin-bottom:12px;">
                            <h4 style="margin-top:0; color:#60a5fa;"><i class="fas fa-bullseye"></i> Objetivo</h4>
                            <p style="margin:8px 0 0 0; font-size:14px; opacity:0.95;"><?php echo nl2br(htmlspecialchars($planSalud['objetivo'] ?? '')); ?></p>
                        </div>

                        <div id="plan-alimentacion" class="tab-content" style="padding:15px; background:rgba(255,255,255,0.02); border-radius:8px; margin-bottom:12px;">
                            <h4 style="margin-top:0; color:#60a5fa;"><i class="fas fa-utensils"></i> Plan de Alimentación</h4>
                            <div style="white-space:pre-line; opacity:0.9; margin-top:8px;"><?php echo htmlspecialchars($planSalud['plan_alimentacion'] ?? ''); ?></div>
                            <?php if (!empty($planSalud['marca_recomendada']) || !empty($mascota['marca_comercial'])): ?>
                                <div style="margin-top:12px; font-size:13px; color:#94a3b8;">Marca recomendada: <?php echo htmlspecialchars($planSalud['marca_recomendada'] ?? $mascota['marca_comercial'] ?? 'N/D'); ?></div>
                            <?php endif; ?>
                        </div>

                        <div id="plan-ejercicio" class="tab-content" style="padding:15px; background:rgba(255,255,255,0.02); border-radius:8px; margin-bottom:12px;">
                            <h4 style="margin-top:0; color:#10b981;"><i class="fas fa-running"></i> Plan de Ejercicio y Actividad</h4>
                            <div style="white-space:pre-line; opacity:0.9; margin-top:8px;"><?php echo htmlspecialchars($planSalud['plan_ejercicio'] ?? ''); ?></div>
                        </div>

                        <div id="plan-diaria" class="tab-content" style="padding:15px; background:rgba(255,255,255,0.02); border-radius:8px; margin-bottom:12px;">
                            <h4 style="margin-top:0; color:#8b5cf6;"><i class="fas fa-calendar-day"></i> Rutina Diaria</h4>
                            <div style="white-space:pre-line; opacity:0.9; margin-top:8px;"><?php echo htmlspecialchars($planSalud['rutina_diaria'] ?? $planSalud['plan_bienestar_mental'] ?? ''); ?></div>
                        </div>

                        <div id="plan-semanal" class="tab-content" style="padding:15px; background:rgba(255,255,255,0.02); border-radius:8px; margin-bottom:12px;">
                            <h4 style="margin-top:0; color:#f59e0b;"><i class="fas fa-calendar-week"></i> Rutina Semanal</h4>
                            <div style="white-space:pre-line; opacity:0.9; margin-top:8px;"><?php echo htmlspecialchars($planSalud['rutina_semanal'] ?? ''); ?></div>
                        </div>

                        <div id="plan-mensual" class="tab-content" style="padding:15px; background:rgba(255,255,255,0.02); border-radius:8px; margin-bottom:12px;">
                            <h4 style="margin-top:0; color:#ec4899;"><i class="fas fa-calendar-alt"></i> Rutina Mensual</h4>
                            <div style="white-space:pre-line; opacity:0.9; margin-top:8px;"><?php echo htmlspecialchars($planSalud['rutina_mensual'] ?? $planSalud['plan_higiene'] ?? ''); ?></div>
                        </div>

                        <div id="plan-recomendaciones" class="tab-content" style="padding:15px; background:rgba(255,255,255,0.02); border-radius:8px;">
                            <h4 style="margin-top:0; color:#06b6d4;"><i class="fas fa-lightbulb"></i> Recomendaciones</h4>
                            <div style="white-space:pre-line; opacity:0.9; margin-top:8px;"><?php echo htmlspecialchars($planSalud['recomendaciones'] ?? ($planSalud['examenes_recomendados'] ?? '')); ?></div>
                        </div>

                        <?php if ($latestPlan && !empty($latestPlan['recomendaciones'])): ?>
                            <div class="recomendaciones-box" style="background: linear-gradient(135deg, rgba(16, 185, 129, 0.1), rgba(59, 130, 246, 0.1)); border-left: 4px solid #10b981; padding: 20px; border-radius: 12px; margin-top: 20px; margin-bottom: 20px;">
                                <h4 style="margin:0 0 10px 0; color: #10b981;"><i class="fas fa-magic"></i> Recomendaciones del Plan Mensual (<?php echo date('F Y', strtotime($latestPlan['created_at'])); ?>)</h4>
                                <div style="font-size: 14px; color: #cbd5e1; white-space: pre-line;"><?php echo htmlspecialchars($latestPlan['recomendaciones']); ?></div>
                            </div>
                        <?php endif; ?>

                        <!-- Rutinas Section -->
                        <div class="rutinas-section">
                            <h4 style="margin:0 0 20px 0; color:#f8fafc; font-size:18px;"><i class="fas fa-tasks"></i> Rutinas del Plan</h4>
                            <div class="rutinas-grid">
                                <div class="rutina-card">
                                    <div class="rutina-header">
                                        <div class="rutina-icon diaria"><i class="fas fa-sun"></i></div>
                                        <div class="rutina-title">Diaria</div>
                                    </div>
                                    <ul class="rutina-list">
                                        <?php 
                                        $rutinasDiarias = explode("\n", $planSalud['rutina_diaria'] ?? $planSalud['plan_bienestar_mental'] ?? '');
                                        foreach (array_slice(array_filter($rutinasDiarias), 0, 5) as $rutina): 
                                        ?>
                                            <li><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars(trim($rutina)); ?></li>
                                        <?php endforeach; ?>
                                        <?php if (empty(array_filter($rutinasDiarias))): ?>
                                            <li><i class="fas fa-info-circle"></i> No hay rutinas diarias definidas</li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                                <div class="rutina-card">
                                    <div class="rutina-header">
                                        <div class="rutina-icon semanal"><i class="fas fa-calendar-week"></i></div>
                                        <div class="rutina-title">Semanal</div>
                                    </div>
                                    <ul class="rutina-list">
                                        <?php 
                                        $rutinasSemanales = explode("\n", $planSalud['rutina_semanal'] ?? '');
                                        foreach (array_slice(array_filter($rutinasSemanales), 0, 5) as $rutina): 
                                        ?>
                                            <li><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars(trim($rutina)); ?></li>
                                        <?php endforeach; ?>
                                        <?php if (empty(array_filter($rutinasSemanales))): ?>
                                            <li><i class="fas fa-info-circle"></i> No hay rutinas semanales definidas</li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                                <div class="rutina-card">
                                    <div class="rutina-header">
                                        <div class="rutina-icon mensual"><i class="fas fa-calendar-alt"></i></div>
                                        <div class="rutina-title">Mensual</div>
                                    </div>
                                    <ul class="rutina-list">
                                        <?php 
                                        $rutinasMensuales = explode("\n", $planSalud['rutina_mensual'] ?? $planSalud['plan_higiene'] ?? '');
                                        foreach (array_slice(array_filter($rutinasMensuales), 0, 5) as $rutina): 
                                        ?>
                                            <li><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars(trim($rutina)); ?></li>
                                        <?php endforeach; ?>
                                        <?php if (empty(array_filter($rutinasMensuales))): ?>
                                            <li><i class="fas fa-info-circle"></i> No hay rutinas mensuales definidas</li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <?php if (!empty($planSalud['recordatorios'])): ?>
                            <div style="margin-top:25px;">
                                <h4 style="margin:0 0 15px 0; color:#60a5fa; font-size:16px;"><i class="fas fa-bell"></i> Recordatorios del Mes</h4>
                                <div style="background: rgba(255,255,255,0.02); border-radius: 12px; padding: 15px; max-height: 240px; overflow-y: auto;">
                                    <?php foreach ($planSalud['recordatorios'] as $rem): ?>
                                        <div style="padding:10px 0; border-bottom:1px solid rgba(255,255,255,0.05); display:flex; justify-content:space-between; align-items:center;">
                                            <div>
                                                <strong style="color:#f8fafc;"><?php echo htmlspecialchars($rem['descripcion']); ?></strong>
                                                <div style="font-size:12px; color:#94a3b8; margin-top:4px;">
                                                    <i class="fas fa-calendar"></i> <?php echo date('d M Y', strtotime($rem['fecha_programada'])); ?>
                                                </div>
                                            </div>
                                            <span style="background: rgba(59,130,246,0.1); color:#60a5fa; padding:4px 10px; border-radius:20px; font-size:11px;">
                                                <?php echo ucfirst($rem['tipo'] ?? 'General'); ?>
                                            </span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="card" style="padding: 50px; text-align: center;">
                        <i class="fas fa-clipboard-question" style="font-size: 50px; opacity: 0.2; margin-bottom: 20px;"></i>
                        <h3>Sin Plan Activo</h3>
                        <p style="margin-bottom: 25px;">Genera un plan de salud basado en IA para mejorar la calidad de vida de tu mascota.</p>
                        
                        <!-- Mostrar info de la mascota aunque no tenga plan -->
                        <div style="background: rgba(59, 130, 246, 0.05); border: 1px solid rgba(59, 130, 246, 0.2); border-radius: 16px; padding: 25px; margin-bottom: 25px; max-width: 500px; margin-left: auto; margin-right: auto;">
                            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;">
                                <div>
                                    <div style="font-size: 11px; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;"><i class="fas fa-paw"></i> Raza</div>
                                    <div style="font-weight: 700; color: #f8fafc; font-size: 16px;"><?php echo htmlspecialchars($mascota['raza'] ?? 'No especificada'); ?></div>
                                </div>
                                <div>
                                    <div style="font-size: 11px; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;"><i class="fas fa-calendar"></i> Edad</div>
                                    <div style="font-weight: 700; color: #f8fafc; font-size: 16px;"><?php echo $edadAniosCalculada; ?>a <?php echo $edadMesesCalculada; ?>m</div>
                                </div>
                                <div>
                                    <div style="font-size: 11px; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;"><i class="fas fa-weight"></i> Peso</div>
                                    <div style="font-weight: 700; color: #f8fafc; font-size: 16px;"><?php echo $pesoActual; ?> kg</div>
                                </div>
                            </div>
                        </div>
                        <a href="plan-salud-mensual.php?mascota_id=<?php echo $mascotaId; ?>" class="btn-add" style="margin-top: 20px; display: inline-block; text-decoration: none; background: var(--gradient-primary); color: white; padding: 12px 24px; border-radius: 12px; font-weight: 700;">
                            <i class="fas fa-plus-circle"></i> Generar Plan de Salud
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Tab: Feed -->
            <div id="feed" class="tab-content">
                <div class="post-grid">
                    <?php if (count($publicaciones) > 0): ?>
                        <?php foreach ($publicaciones as $post): ?>
                            <div class="pet-post-card">
                                <?php if (!empty($post['media_url'])): 
                                    $mediaUrl = htmlspecialchars($post['media_url']);
                                    if (strpos($mediaUrl, 'uploads/') !== 0 && strpos($mediaUrl, 'http') !== 0) $mediaUrl = 'uploads/' . $mediaUrl;
                                ?>
                                    <?php if ($post['media_type'] === 'video'): ?>
                                        <video src="<?php echo $mediaUrl; ?>" class="pet-post-img" controls></video>
                                    <?php else: ?>
                                        <img src="<?php echo $mediaUrl; ?>" class="pet-post-img" alt="Post">
                                    <?php endif; ?>
                                <?php else: ?>
                                    <!-- No image placeholder -->
                                    <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); height: 250px; display: flex; align-items: center; justify-content: center; color: white;">
                                        <i class="fas fa-image" style="font-size: 48px; opacity: 0.5;"></i>
                                    </div>
                                <?php endif; ?>
                                <div style="padding: 15px;">
                                    <p style="margin: 0; color: #cbd5e1; font-size: 14px;"><?php echo htmlspecialchars($post['contenido']); ?></p>
                                    <div style="margin-top: 10px; font-size: 12px; color: #64748b;">
                                        <?php 
                                        $fecha = $post['fecha_publicacion'] ?? $post['created_at'] ?? null;
                                        echo $fecha ? date('d M Y', strtotime($fecha)) : 'Fecha no disponible'; 
                                        ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div style="grid-column: 1/-1; text-align: center; padding: 40px; color: #64748b;">
                            <i class="fas fa-camera" style="font-size: 32px; opacity: 0.5; margin-bottom: 10px;"></i>
                            <p>No hay publicaciones aún.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        function openTab(evt, tabName) {
            var i, tabcontent, tablinks;
            tabcontent = document.getElementsByClassName("tab-content");
            for (i = 0; i < tabcontent.length; i++) {
                tabcontent[i].classList.remove("active");
            }
            tablinks = document.getElementsByClassName("tab-btn");
            for (i = 0; i < tablinks.length; i++) {
                tablinks[i].classList.remove("active");
            }
            document.getElementById(tabName).classList.add("active");
            evt.currentTarget.classList.add("active");
        }

        function openPlanTab(evt, tabName) {
            // Hide all direct children of 'salud' that match the pattern 'plan-*'
            // To simplify, we rely on IDs.
            var ids = ['plan-objetivo', 'plan-alimentacion', 'plan-ejercicio', 'plan-diaria', 'plan-semanal', 'plan-mensual', 'plan-recomendaciones'];
            ids.forEach(function(id) {
                var el = document.getElementById(id);
                if (el) el.style.display = 'none';
            });
            
            // Remove active class from inner tabs
            var tablinks = document.querySelectorAll('.plan-inner-tabs .tab-btn');
            tablinks.forEach(function(btn) {
                btn.classList.remove('active');
            });

            // Show current
            var current = document.getElementById('plan-' + tabName);
            if (current) {
                current.style.display = 'block';
                current.style.animation = 'fadeIn 0.3s ease';
            }
            evt.currentTarget.classList.add("active");
        }

        // Initialize inner tabs
        document.addEventListener('DOMContentLoaded', function() {
            // Ensure first tab is open if exists
            var firstInner = document.querySelector('.plan-inner-tabs .tab-btn');
            if(firstInner) firstInner.click();
        });
    </script>
</body>
</html>
