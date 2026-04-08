<?php
require_once 'db.php';
require_once 'puntos-functions.php';
require_once 'premium-functions.php';

// Verificar sesión
if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit;
}

$userId = $_SESSION['user_id'];

// Obtener información del usuario
$user = getUsuario($userId);
$isPremium = esPremium($userId);
$puntosInfo = obtenerPuntosUsuario($userId);

// 1. Obtener recompensas disponibles (QUE EL USUARIO NO HAYA CANJEADO AÚN)
// Límite de 1 vez por usuario
// También obtenemos la imagen del producto vinculado si existe
$stmt = $pdo->prepare("
    SELECT r.*, 
           (CASE WHEN r.es_gratis = 1 THEN 0 ELSE r.precio_oferta END) as precio_final,
           CASE 
               WHEN r.producto_tabla = 'productos_veterinaria' THEN pv.imagen
               WHEN r.producto_tabla = 'productos_tienda' THEN pt.imagen
               ELSE NULL
           END as imagen
    FROM recompensas r
    LEFT JOIN canjes c ON r.id = c.recompensa_id AND c.user_id = ?
    LEFT JOIN productos_veterinaria pv ON r.producto_id = pv.id AND r.producto_tabla = 'productos_veterinaria'
    LEFT JOIN productos_tienda pt ON r.producto_id = pt.id AND r.producto_tabla = 'productos_tienda'
    WHERE r.activa = 1 
    AND c.id IS NULL
    ORDER BY r.puntos_requeridos ASC
");
$stmt->execute([$userId]);
$recompensas = $stmt->fetchAll();

// 2. Obtener mapa de todos los aliados activos para referencias rápidas
$stmt = $pdo->query("SELECT id, nombre_local, tipo, direccion FROM aliados WHERE activo = 1");
$allAliadosRaw = $stmt->fetchAll();
$aliadosMap = [];
foreach ($allAliadosRaw as $a) $aliadosMap[$a['id']] = $a;

// 3. Procesar visualización de cada recompensa
foreach ($recompensas as &$r) {
    // 4. Determinar aliados participantes para el canje
    $r['participantes'] = [];
    if ($r['alcance_tipo'] === 'global') {
        foreach ($aliadosMap as $id => $a) {
            $r['participantes'][] = ['id' => $id, 'nombre' => $a['nombre_local'], 'tipo' => $a['tipo'], 'direccion' => $a['direccion']];
        }
        $r['partner_display'] = "Disponible con todos nuestros aliados";
        $r['is_global'] = true;
    } else {
        $r['is_global'] = false;
        if ($r['alcance_tipo'] === 'tipo_aliado') {
        $tipoStr = $r['alcance_valor'];
        foreach ($aliadosMap as $id => $a) {
            if ($a['tipo'] === $tipoStr) {
                $r['participantes'][] = ['id' => $id, 'nombre' => $a['nombre_local'], 'tipo' => $a['tipo'], 'direccion' => $a['direccion']];
            }
        }
        $r['partner_display'] = "Válido en todas las " . ucfirst($tipoStr) . 's';
        } elseif ($r['alcance_tipo'] === 'especificos') {
            $ids = explode(',', $r['alcance_valor']);
            $nombres = [];
            foreach ($ids as $id) {
                if (isset($aliadosMap[$id])) {
                    $r['participantes'][] = ['id' => $id, 'nombre' => $aliadosMap[$id]['nombre_local'], 'tipo' => $aliadosMap[$id]['tipo'], 'direccion' => $aliadosMap[$id]['direccion']];
                    $nombres[] = $aliadosMap[$id]['nombre_local'];
                }
            }
            
            $count = count($nombres);
            if ($count > 3) {
                $r['partner_display'] = implode(', ', array_slice($nombres, 0, 2)) . " y " . ($count - 2) . " más...";
            } else {
                $r['partner_display'] = implode(', ', $nombres);
            }
            if (empty($nombres)) $r['partner_display'] = "Aliados seleccionados";
        }
    }
    
    $r['puede_canjear'] = $puntosInfo['puntos'] >= $r['puntos_requeridos'];
}
unset($r); // Romper referencia

// Ordenar: Disponibles primero
usort($recompensas, function($a, $b) {
    if ($a['puede_canjear'] !== $b['puede_canjear']) {
        return $b['puede_canjear'] ? 1 : -1;
    }
    return $a['puntos_requeridos'] - $b['puntos_requeridos'];
});

// Obtener canjes recientes
$canjes = obtenerCanjesUsuario($userId);
$canjesPendientes = array_filter($canjes, fn($c) => $c['estado'] === 'pendiente');

// Listado de TODOS los aliados para el modal global
$stmt = $pdo->query("SELECT nombre_local FROM aliados WHERE activo = 1");
$allAliados = $stmt->fetchAll(PDO::FETCH_COLUMN);
$allNamesString = implode(", ", $allAliados);

// CSS Inyectado
$extra_styles = "
    .recompensas-header {
        background: var(--primary-grad);
        color: white;
        padding: 40px;
        border-radius: 20px;
        margin-bottom: 30px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        position: relative;
        overflow: hidden;
    }
    .recompensas-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 25px;
    }
    .partner-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        background: rgba(30, 41, 59, 0.08); /* Increased background slightly */
        color: #475569; /* Darker color for better contrast */
        padding: 5px 12px;
        border-radius: 10px;
        font-size: 12px;
        font-weight: 700;
        margin-bottom: 10px;
        border: 1px solid rgba(0,0,0,0.05);
    }
    .partner-badge.global {
        background: rgba(16, 185, 129, 0.15);
        color: #059669; /* Stronger green */
        border-color: rgba(16, 185, 129, 0.3);
    }
    .recompensa-card-premium {
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .recompensa-card-premium.removing {
        transform: scale(0.9) translateY(20px);
        opacity: 0;
        pointer-events: none;
    }
    .ticket-modal {
        background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
        color: white;
        border: 1px solid rgba(255,255,255,0.1);
        max-width: 500px;
        width: 90%;
    }
    .ticket-code {
        font-family: 'Courier New', monospace;
        font-size: 32px;
        font-weight: bold;
        letter-spacing: 4px;
        background: rgba(255,255,255,0.1);
        padding: 15px;
        border-radius: 10px;
        border: 2px dashed rgba(255,255,255,0.3);
        margin: 20px 0;
        text-align: center;
        color: #4ade80;
    }
    /* Estilo para puntos actualizados */
    .puntos-update {
        animation: pulsePoints 0.6s ease;
    }
    @keyframes pulsePoints {
        0% { transform: scale(1); }
        50% { transform: scale(1.2); color: #ffd700; }
        100% { transform: scale(1); }
    }
    
    /* Nuevos estilos para beneficios */
    .price-comparison {
        margin: 8px 0;
        display: flex;
        align-items: center;
        gap: 8px;
        font-family: 'Inter', sans-serif;
    }
    .price-old {
        color: #94a3b8;
        text-decoration: line-through;
        font-size: 14px;
    }
    .price-new {
        color: #000000ff;
        font-weight: 800;
        font-size: 18px;
    }
    .discount-badge {
        background: #fef2f2;
        color: #ef4444;
        padding: 2px 8px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 800;
        border: 1px solid #fecaca;
    }
    .gratis-badge {
        background: #f0fdf4;
        color: #10b981;
        padding: 4px 12px;
        border-radius: 8px;
        font-weight: 900;
        font-size: 14px;
        border: 1px solid #bbf7d0;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    /* PREMIUM LOCK STYLES */
    .recompensa-card-premium.locked {
        position: relative;
        cursor: not-allowed !important;
        opacity: 0.8;
    }
    .recompensa-card-premium.locked .img-header {
        background: #1e1b4b !important;
    }
    .premium-lock-overlay {
        position: absolute;
        top: 15px;
        right: 15px;
        background: linear-gradient(135deg, #4338ca 0%, #6366f1 100%);
        color: white;
        padding: 6px 15px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 800;
        display: flex;
        align-items: center;
        gap: 6px;
        box-shadow: 0 4px 15px rgba(67, 56, 202, 0.5);
        z-index: 10;
        border: 1px solid rgba(255,255,255,0.2);
    }
    .locked-message {
        background: #eef2ff;
        color: #4338ca;
        padding: 10px;
        border-radius: 10px;
        font-size: 13px;
        font-weight: 600;
        margin-top: 10px;
        text-align: center;
        border: 1px solid #c7d2fe;
    }
";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recompensas Premium - RUGAL</title>
    <?php include 'pwa-head.php'; ?>
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="dashboard-extra.css">
    <link rel="stylesheet" href="css/responsive.css">
    <!-- styles.css removed to prevent sidebar conflicts -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style><?php echo $extra_styles; ?></style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    
    <div class="main-content">
        <header class="header">
            <div class="header-left">
                <h1 class="page-title">Centro de Canjes</h1>
            </div>
            <div class="header-right">
                <button class="btn-primary" onclick="window.location.href='mis-canjes.php'">
                    <i class="fas fa-ticket-alt"></i> Mis Cupones
                </button>
            </div>
        </header>
        
        <div class="content-wrapper">
            <!-- Cabecera de Catálogo -->
            <div class="card" style="margin-bottom: 25px; background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); color: white; border: none;">
                <div style="padding: 30px; text-align: center;">
                    <h2 style="font-size: 28px; font-weight: 800; margin-bottom: 10px;">
                        <i class="fas fa-礼物 mr-2"></i> Catálogo de Recompensas
                    </h2>
                    <p style="opacity: 0.9; max-width: 600px; margin: 0 auto; margin-bottom: 20px;">
                        Descubre beneficios exclusivos para ti y tu mascota. 
                        <strong>¡Los premios marcados como GRATIS pueden ser canjeados por todos!</strong>
                    </p>
                    
                    <div class="puntos-disponibles glass-card" style="padding: 15px 25px; width: fit-content; margin: 0 auto;">
                        <div style="text-transform: uppercase; font-size: 12px; color: rgba(255,255,255,0.8);">Tienes</div>
                        <div class="puntos-numero" id="user-points-display" style="font-size: 36px;">
                            <i class="fas fa-coins" style="color: #ffd700;"></i> <?php echo number_format($puntosInfo['puntos']); ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="recompensas-grid">
                <?php foreach ($recompensas as $i => $r): ?>
                        <?php 
                            $delay = ($i * 0.1) . 's';
                            $isLocked = ($r['tipo_acceso'] === 'premium' && !$isPremium);
                            $canAfford = $r['puede_canjear'] && !$isLocked;
                            $opacity = $canAfford ? '1' : ($isLocked ? '0.9' : '0.7');
                            
                            // Icono
                        $icon = 'fa-gift';
                        if($r['tipo'] == 'descuento') $icon = 'fa-tags';
                        if($r['tipo'] == 'servicio') $icon = 'fa-hand-holding-heart';
                    ?>
                    <div id="reward-card-<?php echo $r['id']; ?>" class="recompensa-card-premium animate-up <?php echo $isLocked ? 'locked' : ''; ?>" style="animation-delay: <?php echo $delay; ?>; opacity: <?php echo $opacity; ?>;">
                        <?php if($isLocked): ?>
                            <div class="premium-lock-overlay">
                                <i class="fas fa-crown"></i> PREMIUM
                            </div>
                        <?php endif; ?>

                        <div class="img-header" style="background: <?php echo $canAfford ? 'var(--primary-grad)' : ($isLocked ? '#1e1b4b' : '#64748b'); ?>;">
                            <?php if (!empty($r['imagen'])): ?>
                                <?php 
                                    // La imagen ya viene con la ruta completa desde la DB (ej: uploads/productos_vet/prod_xxx.jpeg)
                                    $imgPath = $r['imagen'];
                                ?>
                                <img src="<?php echo htmlspecialchars($imgPath); ?>" style="width: 100%; height: 100%; object-fit: cover; position: absolute; top: 0; left: 0; opacity: 0.4;" onerror="this.style.display='none'">
                            <?php else: ?>
                                <i class="fas <?php echo $icon; ?>" style="font-size: 48px; opacity: 0.5;"></i>
                            <?php endif; ?>
                            <div class="price-tag"><?php echo $r['puntos_requeridos']; ?> PTS</div>
                            <?php if($r['stock'] > -1): ?>
                                <div style="position:absolute; top:10px; left:10px; background:rgba(0,0,0,0.6); color:white; padding:2px 8px; border-radius:10px; font-size:11px;">
                                    <i class="fas fa-box"></i> Stock: <?php echo $r['stock']; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="recompensa-body" style="padding: 20px;">
                            <div class="partner-badge <?php echo $r['is_global'] ? 'global' : ''; ?>">
                                <i class="fas <?php echo $r['is_global'] ? 'fa-globe' : 'fa-store'; ?>"></i> 
                                <?php echo htmlspecialchars($r['partner_display']); ?>
                            </div>
                            
                            <?php if(!empty($r['fecha_limite'])): ?>
                                <div class="partner-badge" style="background: #fee2e2; color: #991b1b; border-color: #fca5a5;">
                                    <i class="fas fa-clock"></i> Expira: <?php echo date('d M, H:i', strtotime($r['fecha_limite'])); ?>
                                </div>
                            <?php endif; ?>
                            
                            <h3 class="recompensa-titulo">
                                <?php echo htmlspecialchars($r['titulo']); ?>
                                
                                <?php if ($r['tipo_acceso'] === 'premium'): ?>
                                    <span class="badge-premium" style="background: #1e1b4b; color: #FDB931; padding: 2px 8px; border-radius: 10px; font-size: 10px; font-weight: 800; margin-left: 5px; vertical-align: middle; border: 1px solid #FDB931;">
                                        <i class="fas fa-crown"></i> PREMIUM
                                    </span>
                                <?php else: ?>
                                    <span class="badge-free" style="background: #10b981; color: white; padding: 2px 8px; border-radius: 10px; font-size: 10px; font-weight: 800; margin-left: 5px; vertical-align: middle;">
                                        <i class="fas fa-check"></i> GRATIS
                                    </span>
                                <?php endif; ?>
                            </h3>
                            
                            <!-- DESPLIEGUE DE BENEFICIOS -->
                            <?php if($r['es_gratis']): ?>
                                <div style="margin: 10px 0;">
                                    <span class="gratis-badge">¡Completamente Gratis!</span>
                                </div>
                            <?php elseif(!empty($r['precio_original']) || !empty($r['precio_oferta'])): ?>
                                <div class="price-comparison">
                                    <?php if(!empty($r['precio_original'])): ?>
                                        <span class="price-old">$<?php echo number_format($r['precio_original']); ?></span>
                                    <?php endif; ?>
                                    
                                    <?php if(!empty($r['precio_oferta'])): ?>
                                        <span class="price-new">$<?php echo number_format($r['precio_oferta']); ?></span>
                                    <?php endif; ?>
                                    
                                    <?php if(!empty($r['porcentaje_descuento'])): ?>
                                        <span class="discount-badge">-<?php echo $r['porcentaje_descuento']; ?>%</span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <p class="recompensa-descripcion"><?php echo htmlspecialchars($r['descripcion']); ?></p>
                            
                            <div class="recompensa-footer">
                                <?php if($isLocked): ?>
                                    <button class="btn-premium" style="width: 100%; background: linear-gradient(135deg, #1e1b4b 0%, #4338ca 100%);"
                                            onclick="window.location.href='upgrade-premium.php'">
                                        <i class="fas fa-unlock"></i> Desbloquear con Premium
                                    </button>
                                <?php else: ?>
                                    <button class="btn-secondary" style="width: 100%; margin-bottom: 8px; background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0;"
                                            onclick='verSedes(<?php echo json_encode($r["participantes"]); ?>, "<?php echo addslashes($r["titulo"]); ?>")'>
                                        <i class="fas fa-map-marker-alt"></i> ¿Dónde reclamar?
                                    </button>
                                    <button class="btn-premium" style="width: 100%;"
                                            onclick="confirmarCompra(<?php echo htmlspecialchars(json_encode($r)); ?>)"
                                            <?php echo !$canAfford ? 'disabled' : ''; ?>>
                                        <?php echo $canAfford ? 'Adquirir Recompensa' : 'Faltan Puntos'; ?>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <?php if (empty($recompensas)): ?>
                <div class="empty-state">
                    <h3>No hay recompensas disponibles</h3>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- El modal de selección de aliado se ha movido a mis-canjes.php para el flujo de activación diferida -->

    <!-- Modal Éxito Compra -->
    <div id="compraModal" class="modal-overlay">
        <div class="modal-content animate-up" style="max-width: 450px; text-align: center;">
            <div class="modal-header" style="justify-content: center;">
                <h3 style="color: var(--text-dark);">¡Recompensa Adquirida!</h3>
            </div>
            <div class="modal-body">
                <div style="width: 80px; height: 80px; background: #f0fdf4; color: #10b981; border-radius: 40px; display: flex; align-items: center; justify-content: center; font-size: 32px; margin: 0 auto 20px;">
                    <i class="fas fa-check"></i>
                </div>
                <p>Has canjeado tus puntos con éxito.</p>
                <p style="font-weight: 600; color: #64748b; margin-top: 10px;">
                    Ve a <span style="color: var(--primary-color);">"Mis Canjes"</span> para activarla cuando estés en el local de tu preferencia.
                </p>
            </div>
            <div class="modal-footer" style="justify-content: center;">
                <button class="btn-primary" onclick="window.location.href='mis-canjes.php'">Ir a Mis Canjes</button>
                <button class="btn-secondary" onclick="document.getElementById('compraModal').classList.remove('active')">Seguir Viendo</button>
            </div>
        </div>
    </div>

    <script>
        let currentReward = null;

        function verSedes(participantes, titulo) {
            document.getElementById('sedesRewardTitle').innerText = titulo;
            const list = document.getElementById('sedesList');
            list.innerHTML = '';
            
            if(participantes.length === 0) {
                list.innerHTML = '<p>No hay sedes específicas asignadas.</p>';
            } else {
                participantes.forEach(p => {
                    const div = document.createElement('div');
                    div.style.cssText = 'padding: 12px; background: #f8fafc; border-radius: 10px; border: 1px solid #e2e8f0;';
                    const mapsLink = p.direccion ? `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(p.direccion)}` : '#';
                    
                    div.innerHTML = `
                        <div style="font-weight: 700; color: #1e293b;">${p.nombre}</div>
                        <div style="font-size: 12px; color: #64748b; margin-top: 2px;">${p.tipo.charAt(0).toUpperCase() + p.tipo.slice(1)}</div>
                        ${p.direccion ? `
                            <a href="${mapsLink}" target="_blank" style="display: inline-flex; align-items: center; gap: 5px; margin-top: 5px; color: #3b82f6; text-decoration: none; font-size: 12px; font-weight: 600;">
                                <i class="fas fa-location-arrow"></i> ${p.direccion}
                            </a>` : ''}
                    `;
                    list.appendChild(div);
                });
            }
            document.getElementById('sedesModal').classList.add('active');
        }

        function confirmarCompra(reward) {
            currentReward = reward;
            
            // Confirmación mejorada
            const confirmMsg = `🎁 CONFIRMAR CANJE\n\n` +
                `Premio: ${reward.titulo}\n` +
                `Costo: ${reward.puntos_requeridos} puntos\n\n` +
                `Después de canjear, ve a "Mis Canjes" para activar tu cupón.\n\n` +
                `¿Confirmar canje?`;
            
            if (!confirm(confirmMsg)) return;
            
            fetch('canjear-recompensa.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `recompensa_id=${reward.id}`
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    // Actualizar Puntos Dinámicamente SIN RECARGAR
                    const pointsDisplay = document.getElementById('user-points-display');
                    const rewardPoints = currentReward.puntos_requeridos;
                    
                    // Extraer número actual de puntos
                    const pointsText = pointsDisplay.innerText.trim();
                    const currentPoints = parseInt(pointsText.replace(/[^0-9]/g, ''));
                    const newPoints = currentPoints - rewardPoints;
                    
                    // Actualizar display con animación
                    pointsDisplay.innerHTML = `<i class="fas fa-coins" style="color: #ffd700;"></i> ${newPoints.toLocaleString()}`;
                    pointsDisplay.classList.add('puntos-update');
                    setTimeout(() => pointsDisplay.classList.remove('puntos-update'), 600);
                    
                    // Remover Tarjeta con Animación Suave
                    const card = document.getElementById(`reward-card-${reward.id}`);
                    if (card) {
                        card.classList.add('removing');
                        setTimeout(() => {
                            card.remove();
                            
                            // Verificar si quedan recompensas
                            const grid = document.querySelector('.recompensas-grid');
                            if (grid && grid.children.length === 0) {
                                grid.innerHTML = `
                                    <div class="empty-state" style="grid-column: 1/-1;">
                                        <i class="fas fa-gift"></i>
                                        <h3>¡Has canjeado todas las recompensas disponibles!</h3>
                                        <p>Vuelve pronto para ver nuevas ofertas</p>
                                    </div>
                                `;
                            }
                        }, 400);
                    }

                    // Mostrar Modal de Éxito
                    document.getElementById('compraModal').classList.add('active');
                } else {
                    alert('❌ Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('❌ Error de conexión. Por favor intenta de nuevo.');
            });
        }
    </script>
</body>
</html>
