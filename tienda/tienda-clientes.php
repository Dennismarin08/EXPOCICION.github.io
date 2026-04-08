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

// Obtener clientes que han comprado (excluyendo ventas anónimas donde usuario_id es NULL)
$stmt = $pdo->prepare("
    SELECT DISTINCT u.id, u.nombre, u.email, u.telefono, u.ciudad, u.foto_perfil,
    COUNT(v.id) as total_compras,
    SUM(v.total) as total_gastado,
    MAX(v.fecha) as ultima_compra
    FROM usuarios u
    JOIN ventas_tienda v ON u.id = v.usuario_id
    WHERE v.tienda_id = ?
    GROUP BY u.id
    ORDER BY total_gastado DESC
");
$stmt->execute([$tiendaId]);
$clientes = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mis Clientes - RUGAL Tienda</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/common-dashboard.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .clients-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .client-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            gap: 15px;
            transition: transform 0.2s;
        }
        
        .client-card:hover { transform: translateY(-3px); }
        
        .client-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
            background: #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: #94a3b8;
        }
        
        .vip-badge {
            background: #f59e0b;
            color: white;
            font-size: 10px;
            padding: 2px 6px;
            border-radius: 10px;
            margin-left: 5px;
            vertical-align: middle;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/sidebar-tienda.php'; ?>
    
    <div class="main-content">
        <header class="header">
            <h1 class="page-title">Mis Clientes</h1>
        </header>

        <div class="content-wrapper">
             <?php if (empty($clientes)): ?>
                <div style="text-align: center; padding: 60px; color: #94a3b8;">
                    <i class="fas fa-users" style="font-size: 48px; margin-bottom: 20px; opacity: 0.3;"></i>
                    <h3>Aún no tienes clientes registrados</h3>
                    <p>Cuando realices una venta vinculada a un usuario, aparecerá aquí.</p>
                </div>
            <?php else: ?>
                <div class="clients-grid">
                    <?php foreach ($clientes as $cliente): ?>
                        <div class="client-card">
                            <?php if ($cliente['foto_perfil']): ?>
                                <img src="<?php echo htmlspecialchars($cliente['foto_perfil']); ?>" class="client-avatar">
                            <?php else: ?>
                                <div class="client-avatar"><i class="fas fa-user"></i></div>
                            <?php endif; ?>
                            
                            <div style="flex:1;">
                                <div style="font-weight:700; color:#1e293b; margin-bottom:2px;">
                                    <?php echo htmlspecialchars($cliente['nombre']); ?>
                                    <?php if($cliente['total_gastado'] > 100000) echo '<span class="vip-badge"><i class="fas fa-crown"></i> VIP</span>'; ?>
                                </div>
                                <div style="font-size:12px; color:#64748b; display:grid; gap:2px;">
                                    <span><i class="fas fa-shopping-bag" style="width:15px; text-align:center;"></i> <?php echo $cliente['total_compras']; ?> compras</span>
                                    <span><i class="fas fa-coins" style="width:15px; text-align:center;"></i> $<?php echo number_format($cliente['total_gastado'], 0); ?> gastados</span>
                                    <span><i class="fas fa-calendar" style="width:15px; text-align:center;"></i> Última: <?php echo date('d/m/Y', strtotime($cliente['ultima_compra'])); ?></span>
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
