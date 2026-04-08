<?php
require_once 'db.php';
require_once 'config.php';
require_once 'includes/check-auth.php';
$userId = $_SESSION['user_id'];

$aliadoId = $_GET['id'] ?? 0;

$stmt = $pdo->prepare("
    SELECT a.*, u.telefono 
    FROM aliados a 
    JOIN usuarios u ON a.usuario_id = u.id 
    WHERE a.id = ? AND a.activo = 1
");
$stmt->execute([$aliadoId]);
$aliado = $stmt->fetch();

if (!$aliado) {
    header("Location: aliados.php");
    exit;
}

require_once 'includes/horarios_functions.php';

$statusInfo = getAllyCurrentStatus($aliado['horario']);
$estado_actual = $statusInfo['status'] ?? 'closed';
$label_actual = $statusInfo['label'] ?? 'Cerrado';
$class_actual = $statusInfo['badge_class'] ?? 'status-closed';

$rawHorario = $aliado['horario'] ?? '[]';
$cleanHorario = preg_replace('/[[:cntrl:]]/', '', $rawHorario);
$horarioJson = json_decode($cleanHorario, true);

if ($aliado['tipo'] === 'veterinaria') {
    $stmt = $pdo->prepare("SELECT * FROM productos_veterinaria WHERE veterinaria_id = ? AND activo = 1");
    $stmt->execute([$aliado['id']]);
} else {
    $stmt = $pdo->prepare("SELECT * FROM productos_tienda WHERE tienda_id = ? AND activo = 1");
    $stmt->execute([$aliado['id']]);
}
$productos = $stmt->fetchAll();

$servicios = [];
if ($aliado['tipo'] === 'veterinaria') {
    $stmt = $pdo->prepare("SELECT * FROM servicios_veterinaria WHERE veterinaria_id = ? AND activo = 1");
    $stmt->execute([$aliado['id']]);
    $servicios = $stmt->fetchAll();
}

$nequiNumero = trim($aliado['cuenta_banco'] ?? '');
$nequiTitular = trim($aliado['titular_cuenta'] ?? $aliado['nombre_local']);
$telefonoLimpio = preg_replace('/[^0-9]/', '', $aliado['telefono'] ?? '');

// Procesar Tags/Servicios de la columna aliados
$allyTags = [];
if (!empty($aliado['servicios'])) {
    $decodedServs = json_decode($aliado['servicios'], true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decodedServs)) {
        $allyTags = $decodedServs;
    } else {
        $rawTags = explode(',', $aliado['servicios']);
        foreach ($rawTags as $t) {
            $t = trim($t);
            if ($t) $allyTags[] = ['name' => $t, 'icon' => 'fas fa-star'];
        }
    }
}
if (!empty($aliado['tipo_alimento'])) {
    $rawAlim = explode(',', $aliado['tipo_alimento']);
    foreach ($rawAlim as $t) {
        $t = trim($t);
        if ($t) $allyTags[] = ['name' => $t, 'icon' => 'fas fa-bone'];
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($aliado['nombre_local']); ?> - RUGAL</title>
    <link rel="stylesheet" href="css/common-dashboard.css">
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="css/themes.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .profile-header { background: white; border-radius: 20px; padding: 30px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); margin-bottom: 30px; display: flex; gap: 30px; align-items: center; }
        /* Image carousel for profile header */
        .profile-image-wrap { width: 200px; height: 200px; border-radius: 15px; position: relative; overflow: hidden; flex-shrink: 0; background: #f8fafc; box-shadow: 0 4px 10px rgba(0,0,0,0.1); transition: transform 0.4s ease; }
        .profile-image-wrap:hover { transform: scale(1.03); }
        .profile-image-slides { display: flex; height: 100%; transition: transform 0.4s ease; }
        .profile-image-slide { min-width: 100%; height: 100%; }
        .profile-image-slide img { width: 100%; height: 100%; object-fit: cover; }
        .slide-arrow-pf { position: absolute; top: 50%; transform: translateY(-50%); background: rgba(0,0,0,0.4); color: white; border: none; width: 28px; height: 28px; border-radius: 50%; cursor: pointer; font-size: 12px; display: flex; align-items: center; justify-content: center; opacity: 0; transition: opacity 0.3s; z-index: 10; }
        .profile-image-wrap:hover .slide-arrow-pf { opacity: 1; }
        .slide-arrow-pf.prev { left: 8px; }
        .slide-arrow-pf.next { right: 8px; }
        .slide-dots-pf { position: absolute; bottom: 10px; left: 50%; transform: translateX(-50%); display: flex; gap: 6px; z-index: 10; }
        .slide-dot-pf { width: 8px; height: 8px; border-radius: 50%; background: rgba(255,255,255,0.5); cursor: pointer; transition: all 0.3s; }
        .slide-dot-pf.active { background: white; width: 16px; border-radius: 4px; }
        
        /* Status badge overlay on image */
        .status-badge-overlay {
            position: absolute; top: 10px; right: 10px; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 800; text-transform: uppercase; z-index: 10; background: rgba(255,255,255,0.9); backdrop-filter: blur(4px); box-shadow: 0 2px 5px rgba(0,0,0,0.1); display: flex; align-items: center; gap: 5px;
        }
        .status-indicator { width: 8px; height: 8px; border-radius: 50%; }
        .status-open-indicator { background: #10b981; animation: pulseGreen 2s infinite; }
        .status-closed-indicator { background: #ef4444; }
        @keyframes pulseGreen { 0% { box-shadow: 0 0 0 0 rgba(16,185,129,0.7); } 70% { box-shadow: 0 0 0 6px rgba(16,185,129,0); } 100% { box-shadow: 0 0 0 0 rgba(16,185,129,0); } }
        .profile-info { flex: 1; display: flex; flex-direction: column; justify-content: center; }
        .profile-title { font-size: 34px; font-weight: 800; margin-bottom: 8px; display: flex; align-items: center; gap: 15px; }
        
        .name-gradient-vet { background: linear-gradient(135deg, #00b09b 0%, #96c93d 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .name-gradient-tienda { background: linear-gradient(135deg, #f97316 0%, #ef4444 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        
        .type-badge { background: #e2e8f0; font-size: 13px; padding: 4px 12px; border-radius: 20px; color: #475569; text-transform: uppercase; font-weight: 800; letter-spacing: 0.5px; }
        .rating-box { display: inline-flex; align-items: center; gap: 5px; background: #fffbeb; color: #b45309; padding: 6px 14px; border-radius: 10px; font-weight: 800; font-size: 15px; }
        
        .stats-row { display: flex; gap: 20px; color: #64748b; font-size: 14px; font-weight: 600; margin-top: 10px; margin-bottom: 15px; align-items: center; }
        .stats-row i { color: #94a3b8; }
        
        .sections-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 30px; }
        @media (max-width: 900px) { .sections-grid { grid-template-columns: 1fr; } .side-column { order: -1; } .profile-header { flex-direction: column; text-align: center; padding: 25px; } .profile-image-wrap { width: 160px; height: 160px; margin: 0 auto; } .profile-title { justify-content: center; font-size: 26px; flex-wrap: wrap; } .btn-appointment { width: 100%; justify-content: center; } .stats-row { justify-content: center; } }
        .section-title { font-size: 20px; font-weight: 700; margin-bottom: 20px; color: #1e293b; border-left: 4px solid #667eea; padding-left: 15px; }
        .products-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 20px; }
        .product-card {
            background: white;
            border-radius: 18px;
            overflow: hidden;
            box-shadow: 0 2px 12px rgba(0,0,0,0.07);
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
        }
        .product-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 16px 40px rgba(102,126,234,0.18);
        }
        .prod-img-wrap {
            width: 100%;
            height: 180px;
            overflow: hidden;
            position: relative;
            background: #f8fafc;
        }
        .prod-img-wrap img {
            width: 100%; height: 100%; object-fit: cover;
            transition: transform 0.4s ease;
        }
        .product-card:hover .prod-img-wrap img { transform: scale(1.05); }
        .prod-stock-badge {
            position: absolute; top: 10px; right: 10px;
            padding: 3px 10px; border-radius: 20px;
            font-size: 11px; font-weight: 700;
        }
        .prod-body { padding: 14px; flex: 1; display: flex; flex-direction: column; }
        .prod-title { font-weight: 700; font-size: 15px; color: #0f172a; margin-bottom: 6px; }
        .prod-price { font-size: 18px; font-weight: 800; color: #667eea; margin-bottom: 12px; }
        .btn-add-cart {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white; border: none; border-radius: 10px;
            padding: 10px; font-weight: 700; cursor: pointer;
            width: 100%; margin-top: auto;
            transition: opacity 0.2s;
        }
        .btn-add-cart:hover { opacity: 0.85; transform: translateY(-2px); }
        .btn-add-cart:disabled { background: #e2e8f0; color: #94a3b8; cursor: not-allowed; transform: none; }
        .service-card-pf { min-height: 180px; padding: 20px; justify-content: space-between; border-top: 4px solid #00b09b; }
        .service-card-pf .prod-body { padding: 0; }
        .btn-appointment { background: linear-gradient(135deg, #00b09b 0%, #96c93d 100%); color: white; padding: 15px 30px; border: none; border-radius: 50px; font-size: 18px; font-weight: bold; cursor: pointer; display: inline-flex; align-items: center; gap: 10px; transition: transform 0.2s, background 0.2s; animation: pulseApptBtn 2.5s infinite; }
        .btn-appointment:hover { transform: translateY(-3px); }
        @keyframes pulseApptBtn { 0% { box-shadow: 0 0 0 0 rgba(0,176,155,0.6); } 70% { box-shadow: 0 0 0 12px rgba(0,176,155,0); } 100% { box-shadow: 0 0 0 0 rgba(0,176,155,0); } }
        .info-card { background: white; border-radius: 20px; padding: 25px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); position: sticky; top: 20px; }
        .info-row { display: flex; gap: 15px; margin-bottom: 15px; color: #475569; position: relative; }
        .info-icon { width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; background: #f1f5f9; border-radius: 8px; color: #667eea; flex-shrink: 0; }
        .btn-interactive { display: flex; align-items: center; justify-content: center; gap: 10px; width: 100%; padding: 12px; border-radius: 12px; border: 1px solid #e2e8f0; background: white; color: #475569; font-weight: 600; text-decoration: none; transition: all 0.2s; margin-top: 10px; cursor: pointer; }
        .btn-interactive:hover { background: #f8fafc; border-color: #cbd5e1; transform: translateY(-2px); box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
        .btn-whatsapp { background: #25d366; color: white; border: none; }
        .btn-whatsapp:hover { background: #128c7e; transform: translateY(-2px); box-shadow: 0 4px 15px rgba(37,211,102,0.3); }

        /* Tag Badges styles */
        .tag-badge {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 6px 14px; border-radius: 12px; font-size: 13px; font-weight: 700;
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            cursor: default;
        }
        .tag-badge:hover { transform: translateY(-2px) scale(1.05); }
        .tag-green { background: rgba(16,185,129,0.1); color: #059669; }
        .tag-green:hover { background: rgba(16,185,129,0.2); box-shadow: 0 4px 10px rgba(16,185,129,0.2); }
        .tag-orange { background: rgba(245,158,11,0.1); color: #d97706; }
        .tag-orange:hover { background: rgba(245,158,11,0.2); box-shadow: 0 4px 10px rgba(245,158,11,0.2); }
        .schedule-item { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #f1f5f9; font-size: 14px; }
        .schedule-item.today { background: #eff6ff; border-radius: 6px; padding: 8px 10px; border-bottom: none; font-weight: 700; color: #1e40af; }
        .cart-fab { position: fixed; bottom: 30px; right: 30px; z-index: 500; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 50px; padding: 16px 28px; font-size: 16px; font-weight: 800; cursor: pointer; box-shadow: 0 8px 30px rgba(102,126,234,0.5); display: none; align-items: center; gap: 12px; transition: transform 0.2s; animation: bounceIn 0.5s ease; }
        .cart-fab:hover { transform: scale(1.05); }
        .cart-count { background: white; color: #667eea; border-radius: 50%; width: 26px; height: 26px; display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 900; }
        @keyframes bounceIn { 0%{transform:scale(0.5);opacity:0;} 80%{transform:scale(1.1);} 100%{transform:scale(1);opacity:1;} }
        .cart-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 800; display: none; backdrop-filter: blur(2px); }
        .cart-overlay.active { display: block; }
        .cart-drawer { position: fixed; top: 0; right: -420px; width: 400px; height: 100vh; background: white; z-index: 900; transition: right 0.35s cubic-bezier(0.4,0,0.2,1); display: flex; flex-direction: column; box-shadow: -10px 0 40px rgba(0,0,0,0.15); }
        .cart-drawer.open { right: 0; }
        @media(max-width:440px){ .cart-drawer { width: 100%; right: -100%; } }
        .cart-drawer-header { padding: 25px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; }
        .cart-drawer-header h3 { font-size: 20px; font-weight: 800; color: #0f172a; }
        .btn-close-cart { background: #f1f5f9; border: none; border-radius: 10px; width: 36px; height: 36px; cursor: pointer; font-size: 18px; color: #64748b; }
        .cart-items { flex: 1; overflow-y: auto; padding: 20px; }
        .cart-empty { text-align: center; padding: 40px 20px; color: #94a3b8; }
        .cart-empty i { font-size: 48px; margin-bottom: 15px; }
        .cart-item { display: flex; gap: 12px; padding: 12px; background: #f8fafc; border-radius: 12px; margin-bottom: 10px; align-items: center; }
        .cart-item-img { width: 52px; height: 52px; border-radius: 10px; object-fit: cover; background: #e2e8f0; flex-shrink: 0; display:flex; align-items:center; justify-content:center; overflow:hidden; }
        .cart-item-img img { width:100%; height:100%; object-fit:cover; }
        .cart-item-info { flex: 1; }
        .cart-item-name { font-weight: 700; font-size: 14px; color: #1e293b; }
        .cart-item-price { color: #667eea; font-weight: 700; font-size: 13px; }
        .cart-item-qty { display: flex; align-items: center; gap: 8px; margin-top: 6px; }
        .qty-btn { width: 26px; height: 26px; border-radius: 8px; border: 1.5px solid #e2e8f0; background: white; cursor: pointer; font-size: 14px; font-weight: 700; color: #475569; display:flex; align-items:center; justify-content:center; }
        .qty-btn:hover { background: #f1f5f9; }
        .qty-val { font-weight: 800; font-size: 15px; min-width: 20px; text-align: center; }
        .btn-remove-item { background: none; border: none; color: #ef4444; cursor: pointer; font-size: 16px; padding: 0 4px; }
        .cart-footer { padding: 20px; border-top: 1px solid #f1f5f9; background: white; }
        .cart-total { display: flex; justify-content: space-between; font-size: 18px; font-weight: 900; color: #0f172a; margin-bottom: 15px; }
        .btn-pay-nequi { width: 100%; padding: 16px; border: none; border-radius: 14px; background: linear-gradient(135deg, #3c0069 0%, #6b21a8 100%); color: white; font-size: 16px; font-weight: 800; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 10px; transition: opacity 0.2s, transform 0.15s; }
        .btn-pay-nequi:hover { opacity: 0.9; transform: translateY(-1px); }
        .modal-backdrop { position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 1100; display: none; align-items: center; justify-content: center; backdrop-filter: blur(4px); }
        .modal-backdrop.active { display: flex; }
        .modal-nequi { background: white; border-radius: 28px; width: 90%; max-width: 440px; overflow: hidden; animation: slideUp 0.4s cubic-bezier(0.175,0.885,0.32,1.275); }
        @keyframes slideUp { from{opacity:0;transform:translateY(40px)} to{opacity:1;transform:translateY(0)} }
        .modal-nequi-top { background: linear-gradient(135deg, #3c0069 0%, #6b21a8 100%); padding: 35px 30px; text-align: center; color: white; }
        .nequi-logo { font-size: 42px; margin-bottom: 10px; }
        .modal-nequi-top h2 { font-size: 22px; font-weight: 900; margin-bottom: 5px; }
        .modal-nequi-top p { opacity: 0.8; font-size: 14px; }
        .modal-nequi-body { padding: 30px; }
        .nequi-step { display: flex; gap: 15px; align-items: flex-start; margin-bottom: 22px; }
        .nequi-step-num { width: 32px; height: 32px; background: linear-gradient(135deg, #3c0069, #6b21a8); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 900; font-size: 14px; flex-shrink: 0; }
        .nequi-step-text { flex: 1; }
        .nequi-step-title { font-weight: 800; color: #1e293b; margin-bottom: 3px; font-size: 14px; }
        .nequi-step-desc { font-size: 13px; color: #64748b; line-height: 1.5; }
        .nequi-highlight { background: linear-gradient(135deg, #f5f3ff, #ede9fe); border-radius: 16px; padding: 20px; margin: 20px 0; border: 2px solid #c4b5fd; text-align: center; }
        .nequi-highlight .label { font-size: 11px; text-transform: uppercase; font-weight: 700; color: #6d28d9; margin-bottom: 5px; }
        .nequi-highlight .number { font-size: 28px; font-weight: 900; letter-spacing: 3px; color: #3c0069; }
        .nequi-highlight .holder { font-size: 13px; color: #7c3aed; margin-top: 4px; }
        .nequi-total-box { background: #fff7ed; border-radius: 12px; padding: 15px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .nequi-total-box .label { font-size: 13px; color: #92400e; font-weight: 600; }
        .nequi-total-box .amount { font-size: 24px; font-weight: 900; color: #c05621; }
        .modal-nequi-actions { display: flex; gap: 10px; padding: 0 30px 30px; }
        .btn-modal-copy { flex: 1; padding: 14px; border: none; border-radius: 12px; background: linear-gradient(135deg, #3c0069, #6b21a8); color: white; font-weight: 800; cursor: pointer; font-size: 14px; display: flex; align-items: center; justify-content: center; gap: 8px; }
        .btn-modal-wsp { flex: 1; padding: 14px; border: none; border-radius: 12px; background: #25d366; color: white; font-weight: 800; cursor: pointer; font-size: 14px; display: flex; align-items: center; justify-content: center; gap: 8px; text-decoration: none; }
        .btn-modal-close { width: 100%; padding: 14px; border: 1.5px solid #e2e8f0; border-radius: 12px; background: white; color: #64748b; font-weight: 700; cursor: pointer; margin: 0 30px 20px; font-size: 14px; }
        .nequi-card { background: linear-gradient(135deg, #3c0069 0%, #6b21a8 100%); border-radius: 20px; padding: 25px; color: white; margin-bottom: 20px; }
        .nequi-card .info-card-title { color: white; }
        .nequi-number { font-size: 22px; font-weight: 900; letter-spacing: 2px; margin: 12px 0; }
        .nequi-titular { font-size: 13px; opacity: 0.8; }
        .btn-copy-nequi { display: flex; align-items: center; justify-content: center; gap: 8px; width: 100%; padding: 12px; border-radius: 12px; background: rgba(255,255,255,0.2); color: white; border: 1.5px solid rgba(255,255,255,0.4); font-weight: 700; cursor: pointer; margin-top: 15px; font-size: 14px; transition: background 0.2s; }
        .btn-copy-nequi:hover { background: rgba(255,255,255,0.3); }
    </style>
</head>
<body class="<?php echo $themeClass; ?>">
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <header class="header">
            <h1 class="page-title">Perfil de Aliado</h1>
            <a href="aliados.php" style="color:#64748b; text-decoration:none;"><i class="fas fa-arrow-left"></i> Volver</a>
        </header>

        <div class="content-wrapper">
            <?php 
                $mainFoto = !empty($aliado['foto_local']) ? buildImgUrl($aliado['foto_local']) : '';

                $fotosVerif = [];
                if (!empty($aliado['fotos_verificacion'])) {
                    $decoded = json_decode($aliado['fotos_verificacion'], true);
                    if (is_array($decoded)) {
                        foreach ($decoded as $f) {
                            $url = buildImgUrl($f);
                            if ($url && $url !== $mainFoto) $fotosVerif[] = $url;
                        }
                    }
                }

                // Slides: foto principal primero, luego verificacion (máx 2 adicionales)
                $allSlides = [];
                if ($mainFoto) $allSlides[] = $mainFoto;
                foreach (array_slice($fotosVerif, 0, 2) as $fv) $allSlides[] = $fv;
            ?>
            <div class="profile-header">
                <div class="profile-image-wrap">
                    <div class="status-badge-overlay" style="color: <?php echo $estado_actual == 'open' ? '#166534' : '#991b1b'; ?>;">
                        <div class="status-indicator <?php echo $estado_actual == 'open' ? 'status-open-indicator' : 'status-closed-indicator'; ?>"></div>
                        <?php echo $estado_actual == 'open' ? 'Abierto' : 'Cerrado'; ?>
                    </div>
                    <div class="profile-image-slides" id="pf-slides">
                        <?php if (!empty($allSlides)): ?>
                            <?php foreach ($allSlides as $slide): ?>
                            <div class="profile-image-slide">
                                <img src="<?php echo htmlspecialchars($slide); ?>" onerror="this.src=''; this.parentElement.style.background='#f1f5f9'; this.style.display='none';">
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="profile-image-slide">
                                <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:40px;color:#cbd5e1;background:#f1f5f9;">
                                    <i class="fas fa-<?php echo $aliado['tipo'] === 'veterinaria' ? 'hospital' : 'store'; ?>"></i>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php if (count($allSlides) > 1): ?>
                    <button class="slide-arrow-pf prev" onclick="slidePf(-1)"><i class="fas fa-chevron-left"></i></button>
                    <button class="slide-arrow-pf next" onclick="slidePf(1)"><i class="fas fa-chevron-right"></i></button>
                    <div class="slide-dots-pf" id="pf-dots">
                        <?php for ($s = 0; $s < count($allSlides); $s++): ?>
                        <div class="slide-dot-pf <?php echo $s === 0 ? 'active' : ''; ?>" onclick="goToSlidePf(<?php echo $s; ?>)"></div>
                        <?php endfor; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="profile-info">
                    <div class="profile-title">
                        <span class="<?php echo $aliado['tipo'] === 'veterinaria' ? 'name-gradient-vet' : 'name-gradient-tienda'; ?>">
                            <?php echo htmlspecialchars($aliado['nombre_local']); ?>
                        </span>
                        <span class="type-badge"><?php echo ucfirst($aliado['tipo']); ?></span>
                    </div>
                    <div style="display:flex; align-items:center; gap:15px; margin-bottom:5px; flex-wrap:wrap;">
                        <div class="rating-box">
                            <i class="fas fa-star"></i> <?php echo number_format($aliado['calificacion'], 1); ?>
                            <span style="font-weight:600; color:#92400e; font-size:13px; margin-left:4px;">(<?php echo $aliado['total_calificaciones']; ?> Opiniones)</span>
                        </div>
                    </div>
                    <?php 
                        $createdYear = date('Y', strtotime($aliado['created_at']));
                        $yearsActive = date('Y') - $createdYear;
                        $yearsText = $yearsActive > 0 ? "$yearsActive años con nosotros" : "Nuevo Aliado";
                        $prodCount = count($productos ?? []);
                        $servCount = count($servicios ?? []);
                    ?>
                    <div class="stats-row">
                        <?php if ($aliado['tipo'] === 'tienda' || $prodCount > 0): ?>
                            <span><i class="fas fa-box"></i> <?php echo $prodCount; ?> Productos</span> <span style="color:#cbd5e1;">|</span>
                        <?php endif; ?>
                        <?php if ($aliado['tipo'] === 'veterinaria'): ?>
                            <span><i class="fas fa-stethoscope"></i> <?php echo $servCount; ?> Servicios</span> <span style="color:#cbd5e1;">|</span>
                        <?php endif; ?>
                        <span><i class="fas fa-certificate"></i> <?php echo $yearsText; ?></span>
                    </div>
                    <p style="color:#64748b; line-height:1.6; max-width:800px; margin-bottom:15px;">
                        <?php echo nl2br(htmlspecialchars($aliado['descripcion'])); ?>
                    </p>
                    <?php if (!empty($allyTags)): ?>
                        <div style="display:flex; gap:8px; flex-wrap:wrap; margin-bottom:20px;">
                            <?php foreach ($allyTags as $tagObj): 
                                $tName = is_array($tagObj) ? $tagObj['name'] : $tagObj;
                                $tIcon = is_array($tagObj) ? ($tagObj['icon'] ?? 'fas fa-star') : 'fas fa-star';
                            ?>
                            <div class="tag-badge <?php echo $aliado['tipo'] === 'veterinaria' ? 'tag-green' : 'tag-orange'; ?>">
                                <i class="<?php echo htmlspecialchars($tIcon); ?>"></i>
                                <span><?php echo htmlspecialchars($tName); ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($aliado['tipo'] === 'veterinaria' && $aliado['acepta_citas']): ?>
                        <div style="margin-top: 15px;">
                            <button class="btn-appointment" onclick="agendarCita(<?php echo $aliado['id']; ?>)" title="Agenda tu cita en línea">
                                <i class="fas fa-calendar-check"></i> Agendar Cita
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="sections-grid">
                <div class="main-column">
                    <?php if (!empty($servicios)): ?>
                        <div class="section-title">Servicios Veterinarios</div>
                        <div class="products-grid">
                            <?php foreach ($servicios as $servicio): ?>
                                <div class="product-card service-card-pf">
                                    <?php 
                                    $sName = strtolower($servicio['nombre']);
                                    $sIcon = 'fa-paw';
                                    if (str_contains($sName, 'consulta')) $sIcon = 'fa-stethoscope';
                                    elseif (str_contains($sName, 'vacun')) $sIcon = 'fa-syringe';
                                    elseif (str_contains($sName, 'cirugía') || str_contains($sName, 'cirugia')) $sIcon = 'fa-cut';
                                    elseif (str_contains($sName, 'baño') || str_contains($sName, 'bano') || str_contains($sName, 'grooming')) $sIcon = 'fa-shower';
                                    elseif (str_contains($sName, 'dental')) $sIcon = 'fa-tooth';
                                    ?>
                                    <div class="prod-body">
                                        <div style="text-align:center; margin-bottom:15px; color:#00b09b;">
                                            <i class="fas <?php echo $sIcon; ?>" style="font-size:40px;"></i>
                                        </div>
                                        <h3 class="prod-title" style="text-align:center;"><?php echo htmlspecialchars($servicio['nombre']); ?></h3>
                                        <div class="prod-price" style="text-align:center;">$<?php echo number_format($servicio['precio'], 0); ?></div>
                                        <p style="font-size:13px; color:#64748b; margin-bottom:15px; text-align:center;">
                                            <?php echo htmlspecialchars($servicio['descripcion']); ?>
                                        </p>
                                        <div style="margin-top:auto; text-align:center;">
                                            <span style="background:#f8fafc; padding:6px 14px; border-radius:20px; font-size:13px; font-weight:700; color:#64748b; border: 1px solid #e2e8f0;">
                                                <i class="fas fa-clock" style="color:#00b09b;"></i> <?php echo $servicio['duracion_minutos']; ?> min
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div style="margin-bottom: 40px;"></div>
                    <?php endif; ?>
                    
                    <div class="section-title">Productos Disponibles</div>
                    <div class="products-grid">
                        <?php foreach ($productos as $producto): ?>
                            <div class="product-card">
                                <div class="prod-img-wrap">
                                    <?php 
                                    $imgSrc = buildImgUrl($producto['imagen'] ?? '');
                                    ?>
                                    <?php if (!empty($producto['imagen']) && $producto['imagen'] !== '[]'): ?>
                                        <img src="<?php echo htmlspecialchars($imgSrc); ?>" alt="<?php echo htmlspecialchars($producto['nombre']); ?>" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                        <div class="no-image" style="display:none; width:100%; height:100%; align-items:center; justify-content:center; font-size:36px; color:#cbd5e1;"><i class="fas fa-box"></i></div>
                                    <?php else: ?>
                                        <div style="width:100%; height:100%; display:flex; align-items:center; justify-content:center; font-size:36px; color:#cbd5e1;"><i class="fas fa-box"></i></div>
                                    <?php endif; ?>

                                    <?php if (isset($producto['stock'])): ?>
                                        <?php if ($producto['stock'] > 0): ?>
                                            <div class="prod-stock-badge" style="background:#dcfce7; color:#166534;"><i class="fas fa-check"></i> <?php echo $producto['stock']; ?></div>
                                        <?php else: ?>
                                            <div class="prod-stock-badge" style="background:#fee2e2; color:#991b1b;"><i class="fas fa-times"></i> Agotado</div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                                <div class="prod-body">
                                    <div class="prod-title"><?php echo htmlspecialchars($producto['nombre']); ?></div>
                                    <div class="prod-price">$<?php echo number_format($producto['precio'], 0); ?></div>
                                    <button class="btn-add-cart"
                                        <?php if (isset($producto['stock']) && $producto['stock'] <= 0) echo 'disabled'; ?>
                                        onclick="addToCart(<?php echo $producto['id']; ?>, '<?php echo addslashes(htmlspecialchars($producto['nombre'])); ?>', <?php echo (float)$producto['precio']; ?>, '<?php echo (!empty($producto['imagen']) && $producto['imagen'] !== '[]') ? htmlspecialchars($imgSrc) : ''; ?>')">
                                        <?php if (isset($producto['stock']) && $producto['stock'] <= 0): ?>
                                            Sin Stock
                                        <?php else: ?>
                                            Agregar al carrito
                                        <?php endif; ?>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <?php if (empty($productos)): ?>
                            <p style="color:#94a3b8; font-style:italic;">Este aliado no tiene productos visibles por el momento.</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="side-column">
                    <?php if ($nequiNumero): ?>
                    <div class="nequi-card">
                        <div class="info-card-title"><i class="fas fa-mobile-alt"></i> Pago por Nequi</div>
                        <div style="font-size:13px; opacity:0.85; margin-bottom:8px;">Transfiere directamente al número:</div>
                        <div class="nequi-number"><?php echo htmlspecialchars($nequiNumero); ?></div>
                        <div class="nequi-titular"><i class="fas fa-user"></i> <?php echo htmlspecialchars($nequiTitular); ?></div>
                        <button class="btn-copy-nequi" onclick="copyNequi('<?php echo addslashes($nequiNumero); ?>')">
                            <i class="far fa-copy"></i> Copiar número
                        </button>
                    </div>
                    <?php endif; ?>

                    <div class="info-card">
                        <h3 style="margin-bottom:20px; font-weight:700; display:flex; align-items:center; gap:10px;">
                            <i class="fas fa-info-circle" style="color:#667eea;"></i> Información de Contacto
                        </h3>
                        <div class="info-row">
                            <div class="info-icon"><i class="fas fa-map-marker-alt"></i></div>
                            <div>
                                <span style="font-weight:600; display:block; font-size:12px; text-transform:uppercase; color:#94a3b8; margin-bottom:2px;">Ubicación</span>
                                <span id="addressText"><?php echo htmlspecialchars($aliado['direccion'] ?: 'No registrada'); ?></span>
                                <div style="display:flex; gap:10px; margin-top:8px;">
                                    <button class="btn-interactive" style="font-size:12px; padding:6px 12px; margin-top:0;" onclick="copyAddress()">
                                        <i class="far fa-copy"></i> Copiar
                                    </button>
                                    <a href="https://www.google.com/maps/search/?api=1&query=<?php echo urlencode($aliado['direccion'] . ' ' . $aliado['nombre_local']); ?>" target="_blank" class="btn-interactive" style="font-size:12px; padding:6px 12px; margin-top:0;">
                                        <i class="fas fa-directions"></i> Ir a Maps
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php if ($aliado['telefono']): ?>
                        <div class="info-row" style="margin-top:20px;">
                            <div class="info-icon"><i class="fas fa-phone"></i></div>
                            <div>
                                <span style="font-weight:600; display:block; font-size:12px; text-transform:uppercase; color:#94a3b8; margin-bottom:2px;">Teléfono</span>
                                <?php echo htmlspecialchars($aliado['telefono']); ?>
                                <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $aliado['telefono']); ?>?text=Hola%20<?php echo urlencode($aliado['nombre_local']); ?>" target="_blank" class="btn-interactive btn-whatsapp">
                                    <i class="fab fa-whatsapp"></i> Chat por WhatsApp
                                </a>
                            </div>
                        </div>
                        <?php endif; ?>
                        <div class="info-row" style="margin-top:25px;">
                            <div class="info-icon"><i class="fas fa-clock"></i></div>
                            <div style="width: 100%;">
                                <span style="font-weight:600; display:block; font-size:12px; text-transform:uppercase; color:#94a3b8; margin-bottom:8px;">Horario Semanal</span>
                                <?php if ($horarioJson && count($horarioJson) > 0): ?>
                                    <div class="schedule-list">
                                        <?php 
                                        $dias_orden = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
                                        foreach ($dias_orden as $dia): 
                                            $datos = $horarioJson[$dia] ?? null;
                                        ?>
                                            <div class="schedule-item <?php echo $dia == $statusInfo['dia_espanol'] ?? '' ? 'today' : ''; ?>">
                                                <span><?php echo $dia; ?></span>
                                                <span>
                                                    <?php if ($datos && ($datos['abierto'] ?? '0') == '1'): ?>
                                                        <?php echo $datos['apertura']; ?> - <?php echo $datos['cierre']; ?>
                                                    <?php else: ?>
                                                        <span style="color:#ef4444;">Cerrado</span>
                                                    <?php endif; ?>
                                                </span>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <p style="font-size:14px; color:#64748b;">No hay horario registrado.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php if ($aliado['tipo'] === 'veterinaria' && $aliado['precio_consulta']): ?>
                        <div class="info-row" style="margin-top:25px; padding:20px; background:linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); border-radius:15px; border:1px solid #e2e8f0;">
                            <div>
                                <span style="font-weight:600; display:block; font-size:12px; text-transform:uppercase; color:#64748b; margin-bottom:5px;">Costo Consulta General</span>
                                <span style="font-size:24px; font-weight:900; color:#0f172a;">$<?php echo number_format($aliado['precio_consulta'], 0); ?></span>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Cart FAB -->
    <button class="cart-fab" id="cartFab" onclick="openCart()">
        <i class="fas fa-shopping-cart"></i>
        Mi Carrito
        <span class="cart-count" id="cartCount">0</span>
    </button>

    <!-- Cart Overlay -->
    <div class="cart-overlay" id="cartOverlay" onclick="closeCart()"></div>

    <!-- Cart Drawer -->
    <div class="cart-drawer" id="cartDrawer">
        <div class="cart-drawer-header">
            <h3><i class="fas fa-shopping-cart" style="color:#667eea;"></i> Mi Carrito</h3>
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
                <a href="https://wa.me/57<?php echo $telefonoLimpio; ?>?text=Hola%20<?php echo urlencode($aliado['nombre_local']); ?>,%20quiero%20hacer%20un%20pedido%20desde%20RUGAL" target="_blank" class="btn-pay-nequi" style="text-decoration:none;">
                    <i class="fab fa-whatsapp"></i> Pedir por WhatsApp
                </a>
            <?php else: ?>
                <button class="btn-pay-nequi" style="background:#94a3b8; cursor:not-allowed;">
                    Contacta para pagar
                </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Nequi Modal -->
    <div class="modal-backdrop" id="nequiModal">
        <div class="modal-nequi">
            <div class="modal-nequi-top">
                <div class="nequi-logo">📱</div>
                <h2>Pagar con Nequi</h2>
                <p>Transfiere el total a este número desde tu app Nequi</p>
            </div>
            <div class="modal-nequi-body">
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
                        <div class="nequi-step-title">Ingresa el número</div>
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
                        <div class="nequi-step-desc">Toma una captura y envíala por WhatsApp para confirmar tu pedido</div>
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
        let cart = [];
        const ALIADO_NAME = <?php echo json_encode($aliado['nombre_local']); ?>;
        const NEQUI_NUM = <?php echo json_encode($nequiNumero); ?>;
        const WSP_NUM = <?php echo json_encode('57' . $telefonoLimpio); ?>;

        function addToCart(id, name, price, img) {
            const existing = cart.find(i => i.id === id);
            if (existing) { existing.qty++; } else { cart.push({ id, name, price, img, qty: 1 }); }
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

        function removeItem(id) { cart = cart.filter(i => i.id !== id); renderCart(); }
        function getTotal() { return cart.reduce((s, i) => s + i.price * i.qty, 0); }
        function fmtPrice(n) { return '$' + n.toLocaleString('es-CO'); }

        function renderCart() {
            const totalItems = cart.reduce((s, i) => s + i.qty, 0);
            document.getElementById('cartCount').textContent = totalItems;
            document.getElementById('cartFab').style.display = totalItems > 0 ? 'flex' : 'none';
            document.getElementById('cartTotal').textContent = fmtPrice(getTotal());
            const container = document.getElementById('cartItems');
            if (cart.length === 0) {
                container.innerHTML = '<div class="cart-empty"><i class="fas fa-shopping-cart"></i><p>Tu carrito está vacío.<br>Agrega productos para continuar.</p></div>';
                return;
            }
            container.innerHTML = cart.map(item => `
                <div class="cart-item">
                    <div class="cart-item-img">${item.img ? '<img src="' + item.img + '" alt="' + item.name + '">' : '<i class="fas fa-box" style="color:#cbd5e1;"></i>'}</div>
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
                </div>`).join('');
        }

        function openCart() { document.getElementById('cartDrawer').classList.add('open'); document.getElementById('cartOverlay').classList.add('active'); }
        function closeCart() { document.getElementById('cartDrawer').classList.remove('open'); document.getElementById('cartOverlay').classList.remove('active'); }

        function openNequiModal() {
            if (cart.length === 0) { alert('Agrega productos al carrito primero.'); return; }
            closeCart();
            const lines = cart.map(i => '• ' + i.name + ' x' + i.qty + ' = ' + fmtPrice(i.price * i.qty)).join('%0A');
            const total = fmtPrice(getTotal());
            const wsp = 'https://wa.me/' + WSP_NUM + '?text=Hola%20' + encodeURIComponent(ALIADO_NAME) + ',%20hice%20un%20pago%20por%20Nequi%20por%20mi%20pedido:%0A' + lines + '%0A*Total:%20' + encodeURIComponent(total) + '*%0AAdjunto%20el%20comprobante%20de%20pago.';
            document.getElementById('modalTotal').textContent = total;
            const btnWsp = document.getElementById('btnWspConfirm');
            if (btnWsp) btnWsp.href = wsp;
            document.getElementById('nequiModal').classList.add('active');
        }
        function closeNequiModal() { document.getElementById('nequiModal').classList.remove('active'); }
        document.getElementById('nequiModal').addEventListener('click', function(e) { if (e.target === this) closeNequiModal(); });

        function agendarCita(id) { window.location.href = 'agendar-cita.php?vet_id=' + id; }
        
        function copyNequi(num) {
            navigator.clipboard.writeText(num).then(() => {
                const btns = document.querySelectorAll('.btn-copy-nequi, .btn-modal-copy');
                btns.forEach(b => { const orig = b.innerHTML; b.innerHTML = '<i class="fas fa-check"></i> ¡Copiado!'; setTimeout(() => b.innerHTML = orig, 2000); });
            });
        }

        function copyAddress() {
            const address = document.getElementById('addressText').innerText;
            navigator.clipboard.writeText(address).then(() => { alert('¡Dirección copiada al portapapeles!'); });
        }

        let curPfSlide = 0;
        function slidePf(dir) {
            const slides = document.getElementById('pf-slides');
            const dots = document.getElementById('pf-dots');
            if (!slides) return;
            const total = slides.querySelectorAll('.profile-image-slide').length;
            curPfSlide = (curPfSlide + dir + total) % total;
            slides.style.transform = `translateX(-${curPfSlide * 100}%)`;
            if (dots) {
                dots.querySelectorAll('.slide-dot-pf').forEach((d, i) => d.classList.toggle('active', i === curPfSlide));
            }
        }

        function goToSlidePf(idx) {
            const slides = document.getElementById('pf-slides');
            const dots = document.getElementById('pf-dots');
            if (!slides) return;
            curPfSlide = idx;
            slides.style.transform = `translateX(-${idx * 100}%)`;
            if (dots) {
                dots.querySelectorAll('.slide-dot-pf').forEach((d, i) => d.classList.toggle('active', i === idx));
            }
        }
    </script>
</body>
</html>
