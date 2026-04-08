<?php
require_once __DIR__ . '/../db.php';

// Verificar acceso de tienda
checkRole('tienda');

$userId = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT id FROM aliados WHERE usuario_id = ? AND tipo = 'tienda'");
$stmt->execute([$userId]);
$tienda = $stmt->fetch();
if (!$tienda) header("Location: tienda-dashboard.php");
$tiendaId = $tienda['id'];

// Obtener estadísticas
// 1. Total Ventas
$stmt = $pdo->prepare("SELECT COUNT(*) FROM ventas_tienda WHERE tienda_id = ?");
$stmt->execute([$tiendaId]);
$totalVentas = $stmt->fetchColumn();

// 2. Ingresos Totales
$stmt = $pdo->prepare("SELECT SUM(total) FROM ventas_tienda WHERE tienda_id = ?");
$stmt->execute([$tiendaId]);
$ingresos = $stmt->fetchColumn() ?: 0;

// 3. Productos Más Vendidos
$stmt = $pdo->prepare("
    SELECT p.nombre, SUM(d.cantidad) as total_vendido
    FROM detalle_ventas_tienda d
    JOIN ventas_tienda v ON d.venta_id = v.id
    JOIN productos_tienda p ON d.producto_id = p.id
    WHERE v.tienda_id = ?
    GROUP BY p.id
    ORDER BY total_vendido DESC
    LIMIT 5
");
$stmt->execute([$tiendaId]);
$topProductos = $stmt->fetchAll();

// 4. Ventas últimos 7 días
$stmt = $pdo->prepare("
    SELECT DATE(fecha) as dia, SUM(total) as total
    FROM ventas_tienda
    WHERE tienda_id = ? AND fecha >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY dia
    ORDER BY dia ASC
");
$stmt->execute([$tiendaId]);
$ventasSemana = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Estadísticas - RUGAL Tienda</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/common-dashboard.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .stats-grid {
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
        }
        
        .chart-container {
            background: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/sidebar-tienda.php'; ?>
    
    <div class="main-content">
        <header class="header">
            <h1 class="page-title">Reportes de Ventas</h1>
        </header>

        <div class="content-wrapper">
            <div class="stats-grid">
                <div class="stat-card">
                    <div style="font-size:14px; color:#64748b;">Total Ingresos</div>
                    <div style="font-size:28px; font-weight:800; color:#10b981;">$<?php echo number_format($ingresos, 0); ?></div>
                </div>
                <div class="stat-card">
                    <div style="font-size:14px; color:#64748b;">Ventas Realizadas</div>
                    <div style="font-size:28px; font-weight:800; color:#3b82f6;"><?php echo $totalVentas; ?></div>
                </div>
            </div>

            <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap:20px;">
                <div class="chart-container">
                    <h3>Ingresos Semanales</h3>
                    <?php if(empty($ventasSemana)): ?>
                        <p style="text-align:center; padding:40px; color:#cbd5e1;">Sin datos recientes</p>
                    <?php else: ?>
                        <canvas id="salesChart"></canvas>
                    <?php endif; ?>
                </div>
                
                <div class="chart-container">
                    <h3>Productos Estrella</h3>
                    <?php if(empty($topProductos)): ?>
                        <p style="text-align:center; padding:40px; color:#cbd5e1;">Sin datos recientes</p>
                    <?php else: ?>
                        <canvas id="productsChart"></canvas>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        <?php if(!empty($ventasSemana)): ?>
        new Chart(document.getElementById('salesChart'), {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_keys($ventasSemana)); ?>,
                datasets: [{
                    label: 'Ventas ($)',
                    data: <?php echo json_encode(array_values($ventasSemana)); ?>,
                    borderColor: '#10b981',
                    tension: 0.4
                }]
            }
        });
        <?php endif; ?>

        <?php if(!empty($topProductos)): ?>
        new Chart(document.getElementById('productsChart'), {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column($topProductos, 'nombre')); ?>,
                datasets: [{
                    label: 'Unidades Vendidas',
                    data: <?php echo json_encode(array_column($topProductos, 'total_vendido')); ?>,
                    backgroundColor: '#ff7e5f'
                }]
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>
