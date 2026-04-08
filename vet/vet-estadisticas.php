<?php
require_once __DIR__ . '/../db.php';

// Verificar acceso de veterinaria
checkRole('veterinaria');

$userId = $_SESSION['user_id'];

// Obtener info veterinaria
$stmt = $pdo->prepare("SELECT id FROM aliados WHERE usuario_id = ? AND tipo = 'veterinaria'");
$stmt->execute([$userId]);
$vet = $stmt->fetch();

if (!$vet) {
    header("Location: vet-dashboard.php");
    exit;
}

$vetId = $vet['id'];

// Obtener estadísticas REALES
// 1. Total citas
$stmt = $pdo->prepare("SELECT COUNT(*) FROM citas WHERE veterinaria_id = ?");
$stmt->execute([$vetId]);
$totalCitas = $stmt->fetchColumn();

// 2. Ingresos estimados (citas completadas * precio servicio + productos vendidos si hubiera tabla de ventas)
// Por ahora solo citas completadas
$stmt = $pdo->prepare("
    SELECT SUM(s.precio) 
    FROM citas c
    JOIN servicios_veterinaria s ON c.servicio_id = s.id
    WHERE c.veterinaria_id = ? AND c.estado = 'completada'
");
$stmt->execute([$vetId]);
$ingresos = $stmt->fetchColumn() ?: 0;

// 3. Servicios más populares
$stmt = $pdo->prepare("
    SELECT s.nombre, COUNT(c.id) as total
    FROM citas c
    JOIN servicios_veterinaria s ON c.servicio_id = s.id
    WHERE c.veterinaria_id = ?
    GROUP BY s.id
    ORDER BY total DESC
    LIMIT 5
");
$stmt->execute([$vetId]);
$serviciosPopulares = $stmt->fetchAll();

// 4. Citas por estado
$stmt = $pdo->prepare("
    SELECT estado, COUNT(*) as total 
    FROM citas 
    WHERE veterinaria_id = ? 
    GROUP BY estado
");
$stmt->execute([$vetId]);
$citasPorEstado = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// 5. Citas últimos 7 días
$stmt = $pdo->prepare("
    SELECT DATE(fecha_hora) as dia, COUNT(*) as total
    FROM citas
    WHERE veterinaria_id = ? AND fecha_hora >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY dia
    ORDER BY dia ASC
");
$stmt->execute([$vetId]);
$citasUltimos7Dias = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Estadísticas - RUGAL Veterinaria</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/common-dashboard.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Chart.js para gráficos -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .stats-overview {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .stat-title { font-size: 14px; color: #64748b; }
        .stat-value { font-size: 28px; font-weight: 800; color: #1e293b; }
        .stat-icon { font-size: 24px; opacity: 0.2; }
        
        .charts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .chart-container {
            background: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            width: 100%;
            overflow-x: auto;
            position: relative;
        }
        
        .chart-wrapper {
            position: relative;
            height: 300px;
            width: 100%;
        }
        
        .chart-title {
            font-size: 18px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 20px;
        }

        /* --- Hamburger Menu --- */
        .hamburger-menu {
            display: none;
            position: fixed;
            top: 15px;
            left: 15px;
            width: 45px;
            height: 45px;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            z-index: 10002;
            cursor: pointer;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            gap: 5px;
            padding: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .hamburger-menu span {
            display: block;
            width: 22px;
            height: 2px;
            background: #334155;
            border-radius: 2px;
            transition: all 0.3s;
        }

        .sidebar-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 10000;
            display: none;
        }
        .sidebar-overlay.active { display: block; }

        @media (max-width: 992px) {
            .hamburger-menu { display: flex; }
            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            .stats-overview {
                grid-template-columns: repeat(2, 1fr);
            }
            .charts-grid {
                grid-template-columns: 1fr;
            }
            .sidebar {
                position: fixed;
                left: -280px;
                top: 0;
                height: 100vh;
                z-index: 10001;
                transition: left 0.3s ease;
            }
            .sidebar.active { left: 0; }
            .main-content { margin-left: 0 !important; }
        }
        @media (max-width: 480px) {
            .stats-overview {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/sidebar-vet.php'; ?>
    
    <!-- Hamburger Menu -->
    <button class="hamburger-menu" onclick="toggleSidebar()">
        <span></span><span></span><span></span>
    </button>
    <div class="sidebar-overlay" onclick="toggleSidebar()"></div>

    <div class="main-content">
        <header class="header">
            <div class="header-left">
                <h1 class="page-title">Estadísticas y Reportes</h1>
                <div class="breadcrumb">
                    <span>Veterinaria</span>
                    <i class="fas fa-chevron-right"></i>
                    <span>Estadísticas</span>
                </div>
            </div>
        </header>

        <div class="content-wrapper">
            <!-- Overview Cards -->
            <div class="stats-overview">
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-title">Total Citas</div>
                        <i class="fas fa-calendar-check stat-icon" style="color: #3b82f6;"></i>
                    </div>
                    <div class="stat-value"><?php echo $totalCitas; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-title">Ingresos Estimados</div>
                        <i class="fas fa-dollar-sign stat-icon" style="color: #10b981;"></i>
                    </div>
                    <div class="stat-value">$<?php echo number_format($ingresos, 0); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-title">Completadas</div>
                        <i class="fas fa-check-circle stat-icon" style="color: #6366f1;"></i>
                    </div>
                    <div class="stat-value"><?php echo $citasPorEstado['completada'] ?? 0; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-title">Pendientes</div>
                        <i class="fas fa-clock stat-icon" style="color: #f59e0b;"></i>
                    </div>
                    <div class="stat-value"><?php echo $citasPorEstado['pendiente'] ?? 0; ?></div>
                </div>
            </div>

            <div class="charts-grid">
                <!-- Gráfico de Servicios -->
                <div class="chart-container">
                    <h3 class="chart-title">Servicios Más Solicitados</h3>
                    <?php if (empty($serviciosPopulares)): ?>
                        <p style="color: #94a3b8; text-align: center; padding: 40px;">No hay datos suficientes</p>
                    <?php else: ?>
                        <div class="chart-wrapper">
                            <canvas id="serviciosChart"></canvas>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Gráfico de Actividad Semanal -->
                <div class="chart-container">
                    <h3 class="chart-title">Citas de la semana</h3>
                    <?php if (empty($citasUltimos7Dias)): ?>
                        <p style="color: #94a3b8; text-align: center; padding: 40px;">No hay datos recientes</p>
                    <?php else: ?>
                        <div class="chart-wrapper">
                            <canvas id="semanaChart"></canvas>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Configuración de Gráficos si hay datos
        <?php if (!empty($serviciosPopulares)): ?>
        const ctxServicios = document.getElementById('serviciosChart').getContext('2d');
        new Chart(ctxServicios, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_column($serviciosPopulares, 'nombre')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($serviciosPopulares, 'total')); ?>,
                    backgroundColor: ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#6366f1']
                }]
            },
            options: { responsive: true, maintainAspectRatio: false }
        });
        <?php endif; ?>

        <?php if (!empty($citasUltimos7Dias)): ?>
        const ctxSemana = document.getElementById('semanaChart').getContext('2d');
        new Chart(ctxSemana, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_keys($citasUltimos7Dias)); ?>,
                datasets: [{
                    label: 'Citas por día',
                    data: <?php echo json_encode(array_values($citasUltimos7Dias)); ?>,
                    backgroundColor: '#00b09b',
                    borderRadius: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { beginAtZero: true, ticks: { stepSize: 1 } }
                }
            }
        });
        <?php endif; ?>

        function toggleSidebar() {
            document.querySelector('.sidebar').classList.toggle('active');
            document.querySelector('.sidebar-overlay').classList.toggle('active');
            document.querySelector('.hamburger-menu').classList.toggle('active');
        }
    </script>
</body>
</html>
