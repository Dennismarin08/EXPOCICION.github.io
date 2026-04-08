<?php
require_once 'db.php';
require_once 'includes/check-auth.php';
require_once 'includes/horarios_functions.php';

$userId = $_SESSION['user_id'];
$aliadoId = intval($_GET['id'] ?? 0);

// Obtener información de la tienda
$stmt = $pdo->prepare("
    SELECT a.*, u.telefono 
    FROM aliados a 
    JOIN usuarios u ON a.usuario_id = u.id 
    WHERE a.id = ? AND a.tipo = 'tienda' AND a.activo = 1
");
$stmt->execute([$aliadoId]);
$tienda = $stmt->fetch();

if (!$tienda) {
    header("Location: aliados.php?tipo=tienda");
    exit;
}

// Estado actual (abierto/cerrado)
$statusInfo = getAllyCurrentStatus($tienda['horario']);
$estado_actual = $statusInfo['status'] ?? 'closed';
$label_actual = $statusInfo['label'] ?? 'Cerrado';
$class_actual  = $statusInfo['badge_class'] ?? 'status-closed';

// Horario decodificado
$rawHorario = $tienda['horario'] ?? '[]';
$cleanHorario = preg_replace('/[[:cntrl:]]/', '', $rawHorario);
$horarioJson = json_decode($cleanHorario, true) ?: [];

// Productos activos
$stmt = $pdo->prepare("SELECT * FROM productos_tienda WHERE tienda_id = ? AND activo = 1 ORDER BY destacado DESC, nombre ASC");
$stmt->execute([$tienda['id']]);
$productos = $stmt->fetchAll();

// Número Nequi (guardado en cuenta_banco)
$nequiNumero = trim($tienda['cuenta_banco'] ?? '');
$nequiTitular = trim($tienda['titular_cuenta'] ?? $tienda['nombre_local']);

// WhatsApp
$telefonoLimpio = preg_replace('/[^0-9]/', '', $tienda['telefono'] ?? '');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($tienda['nombre_local']); ?> - RUGAL</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/common-dashboard.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/dashboard.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/themes.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* ── Layout ── */
        .store-layout { display: grid; grid-template-columns: 1fr 340px; gap: 30px; }
        @media(max-width:900px){ .store-layout { grid-template-columns: 1fr; } .store-sidebar { order: -1; } }
        @media(max-width: 640px) {
            .store-header {
                flex-direction: column;
                align-items: center;
                text-align: center;
                padding: 20px;
            }
            .store-logo {
                width: 100px;
                height: 100px;
            }
            .store-name {
                font-size: 24px;
            }
        }

        /* ── Profile Header ── */
        .store-header {
            background: white; border-radius: 24px; padding: 30px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.06); margin-bottom: 30px;
            display: flex; gap: 30px; align-items: flex-start;
            border: 1px solid #f1f5f9; position: relative; overflow: hidden;
        }
        .store-header::before {
            content:''; position:absolute; top:0; left:0; right:0; height:5px;
            background: linear-gradient(135deg, #ff7e5f 0%, #feb47b 100%);
        }
        .store-logo {
            width: 130px; height: 130px; border-radius: 20px;
            object-fit: cover; box-shadow: 0 8px 25px rgba(0,0,0,0.12);
            flex-shrink: 0; background: #f1f5f9;
            display: flex; align-items: center; justify-content: center;
            font-size: 48px; color: #cbd5e1; overflow: hidden;
        }
        .store-logo img { width: 100%; height: 100%; object-fit: cover; }
        .store-info { flex: 1; }
        .store-name { font-size: 28px; font-weight: 900; color: #0f172a; margin-bottom: 8px; }
        .store-desc { color: #64748b; line-height: 1.6; margin-bottom: 15px; max-width: 700px; }

        /* ── Status Badge ── */
        .status-badge {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 5px 14px; border-radius: 50px; font-size: 12px; font-weight: 700;
            text-transform: uppercase; letter-spacing: .5px; margin-bottom: 12px;
        }
        .status-open  { background: #dcfce7; color: #166534; }
        .status-closed{ background: #fee2e2; color: #991b1b; }

        /* ── Products ── */
        .section-header { font-size: 20px; font-weight: 800; color: #0f172a; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        .section-header i { color: #ff7e5f; }

        .products-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 20px; }

        .product-card {
            background: white; border-radius: 18px; overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.06); border: 1px solid #f1f5f9;
            transition: transform 0.2s, box-shadow 0.2s; cursor: default;
        }
        .product-card:hover { transform: translateY(-4px); box-shadow: 0 12px 30px rgba(0,0,0,0.1); }

        .prod-img-wrap {
            height: 160px; background: #f8fafc; overflow: hidden;
            display: flex; align-items: center; justify-content: center; position: relative;
        }
        .prod-img-wrap img { width: 100%; height: 100%; object-fit: cover; }
        .prod-img-wrap i { font-size: 40px; color: #e2e8f0; }
        .prod-badge-destacado {
            position: absolute; top: 8px; right: 8px; background: #f59e0b;
            color: white; font-size: 10px; font-weight: 800; padding: 3px 8px;
            border-radius: 20px; text-transform: uppercase;
        }

        .prod-body { padding: 15px; }
        .prod-cat { font-size: 11px; text-transform: uppercase; font-weight: 700; color: #94a3b8; margin-bottom: 4px; }
        .prod-name { font-weight: 800; color: #1e293b; margin-bottom: 4px; font-size: 15px; line-height: 1.3; }
        .prod-price { color: #ff7e5f; font-weight: 900; font-size: 20px; margin-bottom: 12px; }
        .prod-stock { font-size: 12px; color: #94a3b8; margin-bottom: 12px; }

        .btn-add-cart {
            width: 100%; padding: 10px; border: none; border-radius: 10px;
            background: linear-gradient(135deg, #ff7e5f 0%, #feb47b 100%);
            color: white; font-weight: 700; cursor: pointer; font-size: 14px;
            display: flex; align-items: center; justify-content: center; gap: 8px;
            transition: opacity 0.2s, transform 0.15s;
        }
        .btn-add-cart:hover { opacity: 0.9; transform: scale(1.02); }
        .btn-add-cart:disabled { background: #e2e8f0; color: #94a3b8; cursor: not-allowed; }

        /* ── Sidebar ── */
        .store-sidebar .info-card {
            background: white; border-radius: 20px; padding: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.06); border: 1px solid #f1f5f9;
            margin-bottom: 20px; position: sticky; top: 20px;
        }
        .info-card-title { font-size: 16px; font-weight: 800; color: #0f172a; margin-bottom: 20px; display: flex; align-items: center; gap: 8px; }
        .info-card-title i { color: #ff7e5f; }
        .info-row { display: flex; gap: 12px; margin-bottom: 16px; }
        .info-icon { width: 34px; height: 34px; background: #fff7f5; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: #ff7e5f; flex-shrink: 0; font-size: 14px; }
        .info-label { font-size: 11px; text-transform: uppercase; font-weight: 700; color: #94a3b8; margin-bottom: 2px; }
        .info-value { font-weight: 600; color: #1e293b; font-size: 14px; }

        .btn-link {
            display: flex; align-items: center; justify-content: center; gap: 8px;
            width: 100%; padding: 11px; border-radius: 12px; border: 1.5px solid #e2e8f0;
            background: white; color: #475569; font-weight: 700; text-decoration: none;
            transition: all 0.2s; margin-top: 10px; cursor: pointer; font-size: 14px;
        }
        .btn-link:hover { background: #f8fafc; border-color: #cbd5e1; }
        .btn-wsp { background: #25d366; color: white; border-color: #25d366; }
        .btn-wsp:hover { background: #1da851; border-color: #1da851; color: white; }

        /* ── Schedule ── */
        .schedule-row { display: flex; justify-content: space-between; padding: 7px 0; border-bottom: 1px solid #f1f5f9; font-size: 13px; }
        .schedule-row:last-child { border-bottom: none; }
        .schedule-row.today { background: #fff7f0; border-radius: 8px; padding: 7px 10px; color: #c05621; font-weight: 700; }

        /* ── Nequi Card ── */
        .nequi-card {
            background: linear-gradient(135deg, #3c0069 0%, #6b21a8 100%);
            border-radius: 20px; padding: 25px; color: white;
        }
        .nequi-card .info-card-title { color: white; }
        .nequi-number { font-size: 22px; font-weight: 900; letter-spacing: 2px; margin: 12px 0; }
        .nequi-titular { font-size: 13px; opacity: 0.8; }
        .btn-copy-nequi {
            display: flex; align-items: center; justify-content: center; gap: 8px;
            width: 100%; padding: 12px; border-radius: 12px;
            background: rgba(255,255,255,0.2); color: white; border: 1.5px solid rgba(255,255,255,0.4);
            font-weight: 700; cursor: pointer; margin-top: 15px; font-size: 14px;
            transition: background 0.2s;
        }
        .btn-copy-nequi:hover { background: rgba(255,255,255,0.3); }

        /* ── Cart Button Floating ── */
        .cart-fab {
            position: fixed; bottom: 30px; right: 30px; z-index: 500;
            background: linear-gradient(135deg, #ff7e5f 0%, #feb47b 100%);
            color: white; border: none; border-radius: 50px; padding: 16px 28px;
            font-size: 16px; font-weight: 800; cursor: pointer;
            box-shadow: 0 8px 30px rgba(255,126,95,0.5);
            display: none; align-items: center; gap: 12px;
            transition: transform 0.2s; animation: bounceIn 0.5s ease;
        }
        .cart-fab:hover { transform: scale(1.05); }
        .cart-count {
            background: white; color: #ff7e5f; border-radius: 50%;
            width: 26px; height: 26px; display: flex; align-items: center;
            justify-content: center; font-size: 13px; font-weight: 900;
        }
        @keyframes bounceIn { 0%{transform:scale(0.5);opacity:0;} 80%{transform:scale(1.1);} 100%{transform:scale(1);opacity:1;} }

        /* ── Cart Drawer ── */
        .cart-overlay {
            position: fixed; inset: 0; background: rgba(0,0,0,0.5);
            z-index: 800; display: none; backdrop-filter: blur(2px);
        }
        .cart-overlay.active { display: block; }
        .cart-drawer {
            position: fixed; top: 0; right: -420px; width: 400px; height: 100vh;
            background: white; z-index: 900; transition: right 0.35s cubic-bezier(0.4,0,0.2,1);
            display: flex; flex-direction: column; box-shadow: -10px 0 40px rgba(0,0,0,0.15);
        }
        .cart-drawer.open { right: 0; }
        @media(max-width:440px){ .cart-drawer { width: 100%; right: -100%; } }

        .cart-drawer-header {
            padding: 25px; border-bottom: 1px solid #f1f5f9;
            display: flex; justify-content: space-between; align-items: center;
        }
        .cart-drawer-header h3 { font-size: 20px; font-weight: 800; color: #0f172a; }
        .btn-close-cart { background: #f1f5f9; border: none; border-radius: 10px; width: 36px; height: 36px; cursor: pointer; font-size: 18px; color: #64748b; }

        .cart-items { flex: 1; overflow-y: auto; padding: 20px; }
        .cart-empty { text-align: center; padding: 40px 20px; color: #94a3b8; }
        .cart-empty i { font-size: 48px; margin-bottom: 15px; }

        .cart-item {
            display: flex; gap: 12px; padding: 12px; background: #f8fafc;
            border-radius: 12px; margin-bottom: 10px; align-items: center;
        }
        .cart-item-img { width: 52px; height: 52px; border-radius: 10px; object-fit: cover; background: #e2e8f0; flex-shrink: 0; display:flex; align-items:center; justify-content:center; overflow:hidden; }
        .cart-item-img img { width:100%; height:100%; object-fit:cover; }
        .cart-item-info { flex: 1; }
        .cart-item-name { font-weight: 700; font-size: 14px; color: #1e293b; }
        .cart-item-price { color: #ff7e5f; font-weight: 700; font-size: 13px; }
        .cart-item-qty { display: flex; align-items: center; gap: 8px; margin-top: 6px; }
        .qty-btn { width: 26px; height: 26px; border-radius: 8px; border: 1.5px solid #e2e8f0; background: white; cursor: pointer; font-size: 14px; font-weight: 700; color: #475569; display:flex; align-items:center; justify-content:center; }
        .qty-btn:hover { background: #f1f5f9; }
        .qty-val { font-weight: 800; font-size: 15px; min-width: 20px; text-align: center; }
        .btn-remove-item { background: none; border: none; color: #ef4444; cursor: pointer; font-size: 16px; padding: 0 4px; }

        .cart-footer {
            padding: 20px; border-top: 1px solid #f1f5f9;
            background: white;
        }
        .cart-total { display: flex; justify-content: space-between; font-size: 18px; font-weight: 900; color: #0f172a; margin-bottom: 15px; }
        .btn-pay-nequi {
            width: 100%; padding: 16px; border: none; border-radius: 14px;
            background: linear-gradient(135deg, #3c0069 0%, #6b21a8 100%);
            color: white; font-size: 16px; font-weight: 800; cursor: pointer;
            display: flex; align-items: center; justify-content: center; gap: 10px;
            transition: opacity 0.2s, transform 0.15s;
        }
        .btn-pay-nequi:hover { opacity: 0.9; transform: translateY(-1px); }

        /* ── Nequi Payment Modal ── */
        .modal-backdrop {
            position: fixed; inset: 0; background: rgba(0,0,0,0.6);
            z-index: 1100; display: none; align-items: center; justify-content: center;
            backdrop-filter: blur(4px);
        }
        .modal-backdrop.active { display: flex; }
        .modal-nequi {
            background: white; border-radius: 28px; width: 90%; max-width: 440px;
            overflow: hidden; animation: slideUp 0.4s cubic-bezier(0.175,0.885,0.32,1.275);
        }
        @keyframes slideUp { from{opacity:0;transform:translateY(40px)} to{opacity:1;transform:translateY(0)} }

        .modal-nequi-top {
            background: linear-gradient(135deg, #3c0069 0%, #6b21a8 100%);
            padding: 35px 30px; text-align: center; color: white;
        }
        .nequi-logo { font-size: 42px; margin-bottom: 10px; }
        .modal-nequi-top h2 { font-size: 22px; font-weight: 900; margin-bottom: 5px; }
        .modal-nequi-top p { opacity: 0.8; font-size: 14px; }

        .modal-nequi-body { padding: 30px; }

        .nequi-step {
            display: flex; gap: 15px; align-items: flex-start; margin-bottom: 22px;
        }
        .nequi-step-num {
            width: 32px; height: 32px; background: linear-gradient(135deg, #3c0069, #6b21a8);
            color: white; border-radius: 50%; display: flex; align-items: center;
            justify-content: center; font-weight: 900; font-size: 14px; flex-shrink: 0;
        }
        .nequi-step-text { flex: 1; }
        .nequi-step-title { font-weight: 800; color: #1e293b; margin-bottom: 3px; font-size: 14px; }
        .nequi-step-desc { font-size: 13px; color: #64748b; line-height: 1.5; }

        .nequi-highlight {
            background: linear-gradient(135deg, #f5f3ff, #ede9fe);
            border-radius: 16px; padding: 20px; margin: 20px 0;
            border: 2px solid #c4b5fd; text-align: center;
        }
        .nequi-highlight .label { font-size: 11px; text-transform: uppercase; font-weight: 700; color: #6d28d9; margin-bottom: 5px; }
        .nequi-highlight .number { font-size: 28px; font-weight: 900; letter-spacing: 3px; color: #3c0069; }
        .nequi-highlight .holder { font-size: 13px; color: #7c3aed; margin-top: 4px; }

        .nequi-total-box {
            background: #fff7ed; border-radius: 12px; padding: 15px;
            display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;
        }
        .nequi-total-box .label { font-size: 13px; color: #92400e; font-weight: 600; }
        .nequi-total-box .amount { font-size: 24px; font-weight: 900; color: #c05621; }

        .modal-nequi-actions { display: flex; gap: 10px; padding: 0 30px 30px; }
        .btn-modal-copy {
            flex: 1; padding: 14px; border: none; border-radius: 12px;
            background: linear-gradient(135deg, #3c0069, #6b21a8);
            color: white; font-weight: 800; cursor: pointer; font-size: 14px;
            display: flex; align-items: center; justify-content: center; gap: 8px;
        }
        .btn-modal-wsp {
            flex: 1; padding: 14px; border: none; border-radius: 12px;
            background: #25d366; color: white; font-weight: 800; cursor: pointer; font-size: 14px;
            display: flex; align-items: center; justify-content: center; gap: 8px; text-decoration: none;
        }
        .btn-modal-close {
            width: 100%; padding: 14px; border: 1.5px solid #e2e8f0; border-radius: 12px;
            background: white; color: #64748b; font-weight: 700; cursor: pointer; margin: 0 30px 20px;
            font-size: 14px;
        }
    </style>
</head>
<body class="<?php echo $themeClass; ?>">
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <header class="header">
            <div class="header-left">
                <h1 class="page-title"><?php echo htmlspecialchars($tienda['nombre_local']); ?></h1>
                <div class="breadcrumb">
                    <a href="aliados.php" style="color:#64748b; text-decoration:none;"><i class="fas fa-arrow-left"></i> Tiendas</a>
                    <i class="fas fa-chevron-right" style="font-size:11px; color:#cbd5e1; margin:0 6px;"></i>
                    <span><?php echo htmlspecialchars($tienda['nombre_local']); ?></span>
                </div>
            </div>
        </header>

        <div class="content-wrapper">

            <!-- ── Store Header ── -->
            <div class="store-header">
                <?php 
                    $tiendaFotoPath = $tienda['foto_local'] ?? '';
                    // Todas las fotos de aliados (tiendas y veterinarias) se almacenan en uploads/aliados/
                    
                    // Eliminar cualquier slash inicial y BASE_URL para procesar uniformemente
                    $tiendaFotoPath = ltrim($tiendaFotoPath, '/');
                    $tiendaFotoPath = str_replace('RUGAL-OFF/', '', $tiendaFotoPath);
                    
                    // Eliminar prefijos incorrectos si existen
                    $tiendaFotoPath = str_replace('uploads/perfil/', '', $tiendaFotoPath);
                    
                    // Asegurar que tenga la ruta correcta - todos están en uploads/aliados/
                    if (strpos($tiendaFotoPath, 'uploads/aliados/') !== 0) {
                        $tiendaFotoPath = 'uploads/aliados/' . $tiendaFotoPath;
                    }
                ?>
                <div class="store-logo">
                    <?php if (!empty($tiendaFotoPath)): ?>
                        <img src="<?php echo BASE_URL . '/' . htmlspecialchars($tiendaFotoPath); ?>" alt="<?php echo htmlspecialchars($tienda['nombre_local']); ?>">
                    <?php else: ?>
                        <i class="fas fa-store"></i>
                    <?php endif; ?>
                </div>
                <div class="store-info">
                    <div class="status-badge <?php echo $class_actual; ?>">
                        <i class="fas <?php echo $estado_actual == 'open' ? 'fa-door-open' : 'fa-door-closed'; ?>"></i>
                        <?php echo $label_actual; ?>
                    </div>
                    <div class="store-name"><?php echo htmlspecialchars($tienda['nombre_local']); ?></div>
                    <?php if (!empty($tienda['tipo_alimento'])): ?>
                        <div style="font-size:13px; color:#ff7e5f; font-weight:700; margin-bottom:8px;">
                            <i class="fas fa-tag"></i> <?php echo htmlspecialchars($tienda['tipo_alimento']); ?>
                        </div>
                    <?php endif; ?>
                    <p class="store-desc"><?php echo nl2br(htmlspecialchars($tienda['descripcion'] ?? 'Tienda especializada en productos para mascotas.')); ?></p>
                    <?php if ($telefonoLimpio): ?>
                        <a href="https://wa.me/57<?php echo $telefonoLimpio; ?>?text=Hola%20<?php echo urlencode($tienda['nombre_local']); ?>,%20vi%20tu%20tienda%20en%20RUGAL%20y%20quiero%20hacer%20un%20pedido" target="_blank" class="btn-link btn-wsp" style="display:inline-flex; width:auto; padding:10px 20px;">
                            <i class="fab fa-whatsapp"></i> Contactar por WhatsApp
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ── Main Layout ── -->
            <div class="store-layout">

                <!-- Products Column -->
                <div>
                    <div class="section-header">
                        <i class="fas fa-shopping-bag"></i>
                        Catálogo de Productos
                        <span style="font-size:14px; font-weight:500; color:#94a3b8;">(<?php echo count($productos); ?> productos)</span>
                    </div>

                    <?php if (empty($productos)): ?>
                        <div style="text-align:center; padding:60px 20px; color:#94a3b8; background:white; border-radius:20px;">
                            <i class="fas fa-box-open" style="font-size:56px; margin-bottom:20px; display:block;"></i>
                            <h3>Sin productos disponibles</h3>
                            <p>Esta tienda aún no tiene productos publicados.</p>
                        </div>
                    <?php else: ?>
                        <div class="products-grid">
                            <?php foreach ($productos as $prod): ?>
                                <div class="product-card">
                                    <div class="prod-img-wrap">
                                        <?php if (!empty($prod['imagen'])): ?>
                                            <img src="<?php echo BASE_URL . '/tienda/' . htmlspecialchars($prod['imagen']); ?>" alt="<?php echo htmlspecialchars($prod['nombre']); ?>">
                                        <?php else: ?>
                                            <i class="fas fa-box"></i>
                                        <?php endif; ?>
                                        <?php if ($prod['destacado']): ?>
                                            <span class="prod-badge-destacado"><i class="fas fa-star"></i> Destacado</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="prod-body">
                                        <div class="prod-cat"><?php echo htmlspecialchars($prod['categoria']); ?></div>
                                        <div class="prod-name"><?php echo htmlspecialchars($prod['nombre']); ?></div>
                                        <?php if (!empty($prod['descripcion'])): ?>
                                            <div style="font-size:12px; color:#64748b; margin-bottom:8px; line-height:1.4;"><?php echo htmlspecialchars(mb_substr($prod['descripcion'],0,70)); ?><?php if(mb_strlen($prod['descripcion'])>70) echo '...'; ?></div>
                                        <?php endif; ?>
                                        <div class="prod-price">$<?php echo number_format((float)$prod['precio'], 0, ',', '.'); ?></div>
                                        <?php if (isset($prod['stock'])): ?>
                                            <div class="prod-stock">
                                                <?php if ($prod['stock'] > 0): ?>
                                                    <i class="fas fa-check-circle" style="color:#10b981;"></i> <?php echo $prod['stock']; ?> en stock
                                                <?php else: ?>
                                                    <i class="fas fa-times-circle" style="color:#ef4444;"></i> Sin stock
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                        <button class="btn-add-cart"
                                            <?php if (isset($prod['stock']) && $prod['stock'] <= 0) echo 'disabled'; ?>
                                            onclick="addToCart(
                                                <?php echo $prod['id']; ?>,
                                                '<?php echo addslashes(htmlspecialchars($prod['nombre'])); ?>',
                                                <?php echo (float)$prod['precio']; ?>,
                                                '<?php echo !empty($prod['imagen']) ? BASE_URL . '/tienda/' . addslashes($prod['imagen']) : ''; ?>'
                                            )">
                                            <?php if (isset($prod['stock']) && $prod['stock'] <= 0): ?>
                                                <i class="fas fa-ban"></i> Sin Stock
                                            <?php else: ?>
                                                <i class="fas fa-cart-plus"></i> Agregar al carrito
                                            <?php endif; ?>
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Sidebar -->
                <div class="store-sidebar">

                    <!-- Nequi Card (primero si tiene número) -->
                    <?php if ($nequiNumero): ?>
                    <div class="nequi-card" style="margin-bottom:20px;">
                        <div class="info-card-title">
                            <i class="fas fa-mobile-alt"></i> Pago por Nequi
                        </div>
                        <div style="font-size:13px; opacity:0.85; margin-bottom:8px;">Transfiere directamente al número:</div>
                        <div class="nequi-number"><?php echo htmlspecialchars($nequiNumero); ?></div>
                        <div class="nequi-titular"><i class="fas fa-user"></i> <?php echo htmlspecialchars($nequiTitular); ?></div>
                        <button class="btn-copy-nequi" onclick="copyNequi('<?php echo addslashes($nequiNumero); ?>')">
                            <i class="far fa-copy"></i> Copiar número
                        </button>
                    </div>
                    <?php endif; ?>

                    <!-- Info Card -->
                    <div class="info-card">
                        <div class="info-card-title"><i class="fas fa-info-circle"></i> Información</div>

                        <?php if (!empty($tienda['direccion'])): ?>
                        <div class="info-row">
                            <div class="info-icon"><i class="fas fa-map-marker-alt"></i></div>
                            <div>
                                <div class="info-label">Dirección</div>
                                <div class="info-value" id="addressText"><?php echo htmlspecialchars($tienda['direccion']); ?></div>
                                <div style="display:flex; gap:8px; margin-top:8px; flex-wrap:wrap;">
                                    <button class="btn-link" style="padding:6px 12px; font-size:12px; width:auto; margin-top:0;" onclick="copyAddress()">
                                        <i class="far fa-copy"></i> Copiar
                                    </button>
                                    <a href="https://www.google.com/maps/search/?api=1&query=<?php echo urlencode($tienda['direccion']); ?>" target="_blank" class="btn-link" style="padding:6px 12px; font-size:12px; width:auto; margin-top:0;">
                                        <i class="fas fa-directions"></i> Maps
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if ($telefonoLimpio): ?>
                        <div class="info-row">
                            <div class="info-icon"><i class="fas fa-phone"></i></div>
                            <div style="flex:1">
                                <div class="info-label">Teléfono</div>
                                <div class="info-value"><?php echo htmlspecialchars($tienda['telefono']); ?></div>
                                <a href="https://wa.me/57<?php echo $telefonoLimpio; ?>?text=Hola%20<?php echo urlencode($tienda['nombre_local']); ?>,%20vi%20tu%20tienda%20en%20RUGAL" target="_blank" class="btn-link btn-wsp">
                                    <i class="fab fa-whatsapp"></i> WhatsApp
                                </a>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Horarios -->
                        <?php if (!empty($horarioJson)): ?>
                        <div class="info-row" style="margin-top:5px;">
                            <div class="info-icon"><i class="fas fa-clock"></i></div>
                            <div style="flex:1">
                                <div class="info-label">Horarios</div>
                                <?php
                                $dias_orden = ['Lunes','Martes','Miércoles','Jueves','Viernes','Sábado','Domingo'];
                                foreach ($dias_orden as $dia):
                                    $h = $horarioJson[$dia] ?? null;
                                    $hoy = $statusInfo['dia_espanol'] ?? '';
                                    $isToday = ($dia === $hoy);
                                ?>
                                <div class="schedule-row <?php echo $isToday ? 'today' : ''; ?>">
                                    <span><?php echo $dia; ?></span>
                                    <span>
                                        <?php if ($h && ($h['abierto'] ?? '0') == '1'): ?>
                                            <?php echo htmlspecialchars($h['apertura']); ?> – <?php echo htmlspecialchars($h['cierre']); ?>
                                        <?php else: ?>
                                            <span style="color:#ef4444;">Cerrado</span>
                                        <?php endif; ?>
                                    </span>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Cart FAB ── -->
    <button class="cart-fab" id="cartFab" onclick="openCart()">
        <i class="fas fa-shopping-cart"></i>
        Mi Carrito
        <span class="cart-count" id="cartCount">0</span>
    </button>

    <!-- ── Cart Overlay ── -->
    <div class="cart-overlay" id="cartOverlay" onclick="closeCart()"></div>

    <!-- ── Cart Drawer ── -->
    <div class="cart-drawer" id="cartDrawer">
        <div class="cart-drawer-header">
            <h3><i class="fas fa-shopping-cart" style="color:#ff7e5f;"></i> Mi Carrito</h3>
            <button class="btn-close-cart" onclick="closeCart()">×</button>
        </div>
        <div class="cart-items" id="cartItems">
            <div class="cart-empty">
                <i class="fas fa-shopping-cart"></i>
                <p>Tu carrito está vacío.<br>Agrega productos para continuar.</p>
            </div>
        </div>
        <div class="cart-footer">
            <div class="cart-total">
                <span>Total</span>
                <span id="cartTotal">$0</span>
            </div>
            <?php if ($nequiNumero): ?>
                <button class="btn-pay-nequi" onclick="openNequiModal()">
                    <i class="fas fa-mobile-alt"></i> Pagar con Nequi
                </button>
            <?php elseif ($telefonoLimpio): ?>
                <a href="https://wa.me/57<?php echo $telefonoLimpio; ?>?text=Hola%20<?php echo urlencode($tienda['nombre_local']); ?>,%20quiero%20hacer%20un%20pedido%20desde%20RUGAL" target="_blank" class="btn-pay-nequi" style="text-decoration:none;">
                    <i class="fab fa-whatsapp"></i> Pedir por WhatsApp
                </a>
            <?php else: ?>
                <button class="btn-pay-nequi" style="background:#94a3b8; cursor:not-allowed;">
                    Contacta la tienda para pagar
                </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- ── Nequi Payment Modal ── -->
    <div class="modal-backdrop" id="nequiModal">
        <div class="modal-nequi">
            <div class="modal-nequi-top">
                <div class="nequi-logo">📱</div>
                <h2>Pagar con Nequi</h2>
                <p>Transfiere el total a este número desde tu app Nequi</p>
            </div>
            <div class="modal-nequi-body">
                <!-- Steps -->
                <div class="nequi-step">
                    <div class="nequi-step-num">1</div>
                    <div class="nequi-step-text">
                        <div class="nequi-step-title">Abre tu app Nequi</div>
                        <div class="nequi-step-desc">Selecciona "Enviar dinero" y elige "A otro Nequi"</div>
                    </div>
                </div>
                <div class="nequi-step">
                    <div class="nequi-step-num">2</div>
                    <div class="nequi-step-text">
                        <div class="nequi-step-title">Ingresa el número de la tienda</div>
                        <div class="nequi-step-desc">Copia el número y pégalo en tu app Nequi</div>
                    </div>
                </div>
                <div class="nequi-highlight">
                    <div class="label">Número Nequi</div>
                    <div class="number" id="modalNequiNum"><?php echo htmlspecialchars($nequiNumero); ?></div>
                    <div class="holder"><?php echo htmlspecialchars($nequiTitular); ?></div>
                </div>
                <div class="nequi-total-box">
                    <span class="label"><i class="fas fa-receipt"></i> Total a pagar</span>
                    <span class="amount" id="modalTotal">$0</span>
                </div>
                <div class="nequi-step">
                    <div class="nequi-step-num">3</div>
                    <div class="nequi-step-text">
                        <div class="nequi-step-title">Envía el comprobante</div>
                        <div class="nequi-step-desc">Toma una captura de la transferencia y envíala por WhatsApp a la tienda para confirmar tu pedido</div>
                    </div>
                </div>
            </div>
            <div class="modal-nequi-actions">
                <button class="btn-modal-copy" onclick="copyNequi('<?php echo addslashes($nequiNumero); ?>')">
                    <i class="far fa-copy"></i> Copiar número
                </button>
                <?php if ($telefonoLimpio): ?>
                <a class="btn-modal-wsp" id="btnWspConfirm" href="#" target="_blank">
                    <i class="fab fa-whatsapp"></i> Confirmar pedido
                </a>
                <?php endif; ?>
            </div>
            <div style="padding: 0 30px 25px;">
                <button class="btn-modal-close" onclick="closeNequiModal()">Cerrar</button>
            </div>
        </div>
    </div>

    <script>
        // ━━ Cart State ━━
        let cart = [];
        const TIENDA_NAME = <?php echo json_encode($tienda['nombre_local']); ?>;
        const NEQUI_NUM   = <?php echo json_encode($nequiNumero); ?>;
        const WSP_NUM     = <?php echo json_encode('57' . $telefonoLimpio); ?>;

        function addToCart(id, name, price, img) {
            const existing = cart.find(i => i.id === id);
            if (existing) {
                existing.qty++;
            } else {
                cart.push({ id, name, price, img, qty: 1 });
            }
            renderCart();
            openCart();
        }

        function changeQty(id, delta) {
            const item = cart.find(i => i.id === id);
            if (!item) return;
            item.qty += delta;
            if (item.qty <= 0) cart = cart.filter(i => i.id !== id);
            renderCart();
        }

        function removeItem(id) {
            cart = cart.filter(i => i.id !== id);
            renderCart();
        }

        function getTotal() {
            return cart.reduce((s, i) => s + i.price * i.qty, 0);
        }

        function fmtPrice(n) {
            return '$' + n.toLocaleString('es-CO');
        }

        function renderCart() {
            const totalItems = cart.reduce((s, i) => s + i.qty, 0);
            document.getElementById('cartCount').textContent = totalItems;
            document.getElementById('cartFab').style.display = totalItems > 0 ? 'flex' : 'none';
            document.getElementById('cartTotal').textContent = fmtPrice(getTotal());

            const container = document.getElementById('cartItems');
            if (cart.length === 0) {
                container.innerHTML = `
                    <div class="cart-empty">
                        <i class="fas fa-shopping-cart"></i>
                        <p>Tu carrito está vacío.<br>Agrega productos para continuar.</p>
                    </div>`;
                return;
            }

            container.innerHTML = cart.map(item => `
                <div class="cart-item">
                    <div class="cart-item-img">
                        ${item.img ? `<img src="${item.img}" alt="${item.name}">` : '<i class="fas fa-box" style="color:#cbd5e1;"></i>'}
                    </div>
                    <div class="cart-item-info">
                        <div class="cart-item-name">${item.name}</div>
                        <div class="cart-item-price">${fmtPrice(item.price * item.qty)}</div>
                        <div class="cart-item-qty">
                            <button class="qty-btn" onclick="changeQty(${item.id}, -1)">−</button>
                            <span class="qty-val">${item.qty}</span>
                            <button class="qty-btn" onclick="changeQty(${item.id}, 1)">+</button>
                            <button class="btn-remove-item" onclick="removeItem(${item.id})"><i class="fas fa-trash"></i></button>
                        </div>
                    </div>
                </div>
            `).join('');
        }

        // ━━ Cart Drawer ━━
        function openCart() {
            document.getElementById('cartDrawer').classList.add('open');
            document.getElementById('cartOverlay').classList.add('active');
        }
        function closeCart() {
            document.getElementById('cartDrawer').classList.remove('open');
            document.getElementById('cartOverlay').classList.remove('active');
        }

        // ━━ Nequi Modal ━━
        function openNequiModal() {
            if (cart.length === 0) { alert('Agrega productos al carrito primero.'); return; }
            closeCart();

            // Build order summary for WhatsApp
            const lines = cart.map(i => `• ${i.name} x${i.qty} = ${fmtPrice(i.price * i.qty)}`).join('%0A');
            const total = fmtPrice(getTotal());
            const wsp = `https://wa.me/${WSP_NUM}?text=Hola%20${encodeURIComponent(TIENDA_NAME)},%20hice%20un%20pago%20por%20Nequi%20por%20mi%20pedido:%0A${lines}%0A*Total:%20${encodeURIComponent(total)}*%0AAdjunto%20el%20comprobante%20de%20pago.`;

            document.getElementById('modalTotal').textContent = total;
            const btnWsp = document.getElementById('btnWspConfirm');
            if (btnWsp) btnWsp.href = wsp;

            document.getElementById('nequiModal').classList.add('active');
        }
        function closeNequiModal() {
            document.getElementById('nequiModal').classList.remove('active');
        }
        document.getElementById('nequiModal').addEventListener('click', function(e) {
            if (e.target === this) closeNequiModal();
        });

        // ━━ Utilities ━━
        function copyNequi(num) {
            navigator.clipboard.writeText(num).then(() => {
                // Visual feedback
                const btns = document.querySelectorAll('.btn-copy-nequi, .btn-modal-copy');
                btns.forEach(b => {
                    const orig = b.innerHTML;
                    b.innerHTML = '<i class="fas fa-check"></i> ¡Copiado!';
                    setTimeout(() => b.innerHTML = orig, 2000);
                });
            });
        }

        function copyAddress() {
            const addr = document.getElementById('addressText');
            if (addr) navigator.clipboard.writeText(addr.innerText).then(() => alert('¡Dirección copiada!'));
        }
    </script>
</body>
</html>
