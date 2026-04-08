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

// Procesar CRUD
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'crear') {
        $stmt = $pdo->prepare("INSERT INTO promociones (aliado_id, titulo, descripcion, descuento_porcentaje, codigo_cupon, fecha_inicio, fecha_fin) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$tiendaId, $_POST['titulo'], $_POST['descripcion'], $_POST['descuento'], $_POST['codigo'], $_POST['inicio'], $_POST['fin']]);
    } elseif ($action === 'eliminar') {
        $stmt = $pdo->prepare("DELETE FROM promociones WHERE id = ? AND aliado_id = ?");
        $stmt->execute([$_POST['id'], $tiendaId]);
    }
    
    header("Location: tienda-promociones.php");
    exit;
}

// Obtener promociones
$stmt = $pdo->prepare("SELECT * FROM promociones WHERE aliado_id = ? ORDER BY created_at DESC");
$stmt->execute([$tiendaId]);
$promos = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Promociones - RUGAL Tienda</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/common-dashboard.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .promos-list {
            display: grid;
            gap: 20px;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        }
        
        .promo-ticket {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            position: relative;
        }
        
        .ticket-header {
            background: #ff7e5f;
            color: white;
            padding: 20px;
            text-align: center;
            border-bottom: 2px dashed white;
        }
        
        .ticket-percent {
            font-size: 36px;
            font-weight: 800;
        }
        
        .ticket-body {
            padding: 20px;
            flex: 1;
        }
        
        .ticket-code {
            text-align: center;
            background: #f1f5f9;
            padding: 10px;
            border-radius: 8px;
            margin: 10px 0;
            font-family: monospace;
            font-weight: bold;
            color: #1e293b;
            letter-spacing: 2px;
            border: 1px dashed #cbd5e1;
        }
        
        /* Modal simplified from previous usage */
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; }
        .modal.active { display: flex; }
        .modal-content { background: white; padding: 30px; border-radius: 15px; width: 90%; max-width: 500px; }
        .form-input { width: 100%; padding: 10px; margin: 5px 0 15px; border: 1px solid #ddd; border-radius: 5px; }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/sidebar-tienda.php'; ?>
    
    <div class="main-content">
        <header class="header">
            <h1 class="page-title">Mis Promociones</h1>
            <button onclick="document.getElementById('newPromoModal').classList.add('active')" style="background:#ff7e5f; color:white; border:none; padding:10px 20px; border-radius:10px; cursor:pointer;">
                <i class="fas fa-plus"></i> Crear Promo
            </button>
        </header>

        <div class="content-wrapper">
            <div class="promos-list">
                <?php foreach ($promos as $promo): ?>
                    <div class="promo-ticket">
                        <div class="ticket-header">
                            <div class="ticket-percent"><?php echo $promo['descuento_porcentaje']; ?>% OFF</div>
                            <div><?php echo htmlspecialchars($promo['titulo']); ?></div>
                        </div>
                        <div class="ticket-body">
                            <p style="color:#64748b; font-size:14px;"><?php echo htmlspecialchars($promo['descripcion']); ?></p>
                            <?php if ($promo['codigo_cupon']): ?>
                                <div class="ticket-code"><?php echo $promo['codigo_cupon']; ?></div>
                            <?php endif; ?>
                            <div style="font-size:12px; color:#94a3b8; text-align:center;">
                                Válido hasta: <?php echo date('d/m/Y', strtotime($promo['fecha_fin'])); ?>
                            </div>
                            <button onclick="if(confirm('¿Eliminar?')) { document.getElementById('del-<?php echo $promo['id']; ?>').submit(); }" style="width:100%; margin-top:15px; background:none; border:1px solid #ef4444; color:#ef4444; padding:8px; border-radius:5px; cursor:pointer;">Eliminar</button>
                            <form id="del-<?php echo $promo['id']; ?>" method="POST" style="display:none;">
                                <input type="hidden" name="action" value="eliminar">
                                <input type="hidden" name="id" value="<?php echo $promo['id']; ?>">
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <?php if(empty($promos)): ?>
                    <div style="text-align:center; grid-column:1/-1; padding:40px; color:#cbd5e1;">
                        <i class="fas fa-tags" style="font-size:48px;"></i>
                        <p>No tienes promociones activas</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div id="newPromoModal" class="modal">
        <div class="modal-content">
            <h2>Nueva Promoción</h2>
            <form method="POST">
                <input type="hidden" name="action" value="crear">
                <label>Título</label>
                <input type="text" name="titulo" class="form-input" required>
                <label>Descripción</label>
                <textarea name="descripcion" class="form-input" required></textarea>
                <label>Descuento (%)</label>
                <input type="number" name="descuento" class="form-input" required>
                <label>Código Cupón</label>
                <input type="text" name="codigo" class="form-input">
                <label>Fecha Inicio</label>
                <input type="date" name="inicio" class="form-input" required value="<?php echo date('Y-m-d'); ?>">
                <label>Fecha Fin</label>
                <input type="date" name="fin" class="form-input" required>
                
                <button type="submit" style="width:100%; background:#ff7e5f; color:white; border:none; padding:15px; border-radius:10px; font-weight:bold; cursor:pointer;">Crear Promoción</button>
                <button type="button" onclick="document.getElementById('newPromoModal').classList.remove('active')" style="width:100%; background:none; border:none; padding:10px; margin-top:5px; cursor:pointer;">Cancelar</button>
            </form>
        </div>
    </div>
</body>
</html>
