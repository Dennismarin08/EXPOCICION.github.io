<?php
require_once __DIR__ . '/../db.php';

// Verificar acceso de tienda
checkRole('tienda');

$userId = $_SESSION['user_id'];

// Obtener información de la tienda
$stmt = $pdo->prepare("SELECT id FROM aliados WHERE usuario_id = ? AND tipo = 'tienda'");
$stmt->execute([$userId]);
$tienda = $stmt->fetch();

if (!$tienda) {
    header("Location: tienda-dashboard.php");
    exit;
}

$tiendaId = $tienda['id'];

// Procesar actualización rápida de stock
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_stock') {
    $id = $_POST['product_id'];
    $newStock = $_POST['new_stock'];
    
    $stmt = $pdo->prepare("UPDATE productos_tienda SET stock = ? WHERE id = ? AND tienda_id = ?");
    $stmt->execute([$newStock, $id, $tiendaId]);
    
    header("Location: tienda-inventario.php?updated=1");
    exit;
}

// Obtener productos y calcular valor del inventario
$stmt = $pdo->prepare("SELECT * FROM productos_tienda WHERE tienda_id = ? ORDER BY stock ASC");
$stmt->execute([$tiendaId]);
$productos = $stmt->fetchAll();

$totalItems = 0;
$totalValue = 0;
$lowStockCount = 0;

foreach ($productos as $p) {
    $totalItems += $p['stock'];
    $totalValue += ($p['stock'] * $p['precio']);
    if ($p['stock'] < 10) $lowStockCount++;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Inventario - RUGAL Tienda</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/common-dashboard.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
        }
        
        .inventory-list {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            overflow: hidden;
        }
        
        .inventory-item {
            display: grid;
            grid-template-columns: 3fr 1fr 1fr 1fr;
            padding: 20px;
            border-bottom: 1px solid #f1f5f9;
            align-items: center;
        }
        
        .inventory-item:last-child { border-bottom: none; }
        
        .item-header { background: #f8fafc; font-weight: 600; color: #64748b; }
        
        .stock-input {
            width: 80px;
            padding: 8px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            text-align: center;
            font-weight: bold;
        }
        
        .stock-control {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .btn-update {
            background: #3b82f6;
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 12px;
        }
        
        .alert-low { color: #ef4444; font-weight: bold; font-size: 12px; display: block; margin-top: 5px; }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/sidebar-tienda.php'; ?>
    
    <div class="main-content">
        <header class="header">
            <h1 class="page-title">Control de Inventario</h1>
        </header>

        <div class="content-wrapper">
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon" style="background: #3b82f6;"><i class="fas fa-boxes"></i></div>
                    <div>
                        <div style="font-size: 24px; font-weight: 800;"><?php echo $totalItems; ?></div>
                        <div style="font-size: 12px; color: #64748b;">Unidades Totales</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background: #10b981;"><i class="fas fa-dollar-sign"></i></div>
                    <div>
                        <div style="font-size: 24px; font-weight: 800;">$<?php echo number_format($totalValue, 0); ?></div>
                        <div style="font-size: 12px; color: #64748b;">Valor Inventario</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background: <?php echo $lowStockCount > 0 ? '#ef4444' : '#10b981'; ?>;"><i class="fas fa-exclamation-triangle"></i></div>
                    <div>
                        <div style="font-size: 24px; font-weight: 800;"><?php echo $lowStockCount; ?></div>
                        <div style="font-size: 12px; color: #64748b;">Stock Bajo (<10)</div>
                    </div>
                </div>
            </div>

            <div class="inventory-list">
                <div class="inventory-item item-header">
                    <div>Producto</div>
                    <div style="text-align:center;">Precio</div>
                    <div style="text-align:center;">Stock Actual</div>
                    <div style="text-align:center;">Acción</div>
                </div>
                
                <?php foreach ($productos as $producto): ?>
                    <form method="POST" class="inventory-item">
                        <input type="hidden" name="action" value="update_stock">
                        <input type="hidden" name="product_id" value="<?php echo $producto['id']; ?>">
                        
                        <div>
                            <div style="font-weight: bold; color: #1e293b;"><?php echo htmlspecialchars($producto['nombre']); ?></div>
                            <div style="font-size: 12px; color: #64748b;"><?php echo htmlspecialchars($producto['categoria']); ?></div>
                            <?php if ($producto['stock'] < 10): ?>
                                <span class="alert-low"><i class="fas fa-arrow-down"></i> Stock Bajo</span>
                            <?php endif; ?>
                        </div>
                        
                        <div style="text-align:center; font-weight:600; color:#1e293b;">
                            $<?php echo number_format($producto['precio'], 0); ?>
                        </div>
                        
                        <div style="display:flex; justify-content:center;">
                            <input type="number" name="new_stock" value="<?php echo $producto['stock']; ?>" class="stock-input">
                        </div>
                        
                        <div style="text-align:center;">
                            <button type="submit" class="btn-update">
                                <i class="fas fa-sync"></i> Actualizar
                            </button>
                        </div>
                    </form>
                <?php endforeach; ?>
                
                <?php if (empty($productos)): ?>
                    <div style="padding: 40px; text-align: center; color: #94a3b8;">
                        No hay productos en inventario
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
