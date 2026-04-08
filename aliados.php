<?php
require_once 'db.php';
require_once 'includes/check-auth.php';
require_once 'includes/horarios_functions.php';
$userId = $_SESSION['user_id'];
$activeTab = $_GET['tipo'] ?? 'todos';
$search = trim($_GET['q'] ?? '');

// Fetch Allies (solo activos)
$sql = "SELECT a.*, u.ultimo_login as owner_last_login 
        FROM aliados a 
        JOIN usuarios u ON a.usuario_id = u.id
        WHERE a.activo = 1";
$params = [];

if ($activeTab === 'veterinaria') {
    $sql .= " AND a.tipo = 'veterinaria'";
} elseif ($activeTab === 'tienda') {
    $sql .= " AND a.tipo = 'tienda'";
}
if ($search) {
    $sql .= " AND (a.nombre_local LIKE ? OR a.descripcion LIKE ? OR a.direccion LIKE ?)";
    $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%";
}
$sql .= " ORDER BY a.calificacion DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$aliados = $stmt->fetchAll();

// Obtener productos destacados de cada aliado
$aliadosConProductos = [];
foreach ($aliados as $aliado) {
    $aliado['productos_destacados'] = [];
    
    if ($aliado['tipo'] === 'tienda') {
        $stmtProd = $pdo->prepare("
            SELECT id, nombre, precio, imagen 
            FROM productos_tienda 
            WHERE tienda_id = ? AND activo = 1 AND destacado = 1 
            ORDER BY nombre ASC LIMIT 4
        ");
        $stmtProd->execute([$aliado['id']]);
        $aliado['productos_destacados'] = $stmtProd->fetchAll();
    } elseif ($aliado['tipo'] === 'veterinaria') {
        $stmtProd = $pdo->prepare("
            SELECT id, nombre, precio, imagen 
            FROM productos_veterinaria 
            WHERE veterinaria_id = ? AND activo = 1 
            ORDER BY nombre ASC LIMIT 4
        ");
        $stmtProd->execute([$aliado['id']]);
        $aliado['productos_destacados'] = $stmtProd->fetchAll();
    }
    
    // Parse fotos_verificacion
    $fotos = [];
    if (!empty($aliado['fotos_verificacion'])) {
        $decoded = json_decode($aliado['fotos_verificacion'], true);
        if (is_array($decoded)) $fotos = $decoded;
    }
    $aliado['fotos_array'] = $fotos;
    
    $aliadosConProductos[] = $aliado;
}

$aliados = $aliadosConProductos;

// Helper: online status
function getOnlineStatus($lastLogin) {
    if (!$lastLogin) return ['label' => 'Sin conexión', 'color' => '#94a3b8', 'dot' => '#94a3b8'];
    $diff = time() - strtotime($lastLogin);
    if ($diff < 300)      return ['label' => 'En línea', 'color' => '#10b981', 'dot' => '#10b981'];
    if ($diff < 3600)     return ['label' => 'Hace ' . round($diff/60) . ' min', 'color' => '#f59e0b', 'dot' => '#f59e0b'];
    if ($diff < 86400)    return ['label' => 'Hoy, ' . date('H:i', strtotime($lastLogin)), 'color' => '#64748b', 'dot' => '#64748b'];
    if ($diff < 172800)   return ['label' => 'Ayer', 'color' => '#94a3b8', 'dot' => '#94a3b8'];
    return ['label' => date('d/m/Y', strtotime($lastLogin)), 'color' => '#94a3b8', 'dot' => '#94a3b8'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aliados RUGAL</title>
    <?php include 'pwa-head.php'; ?>
    <link rel="stylesheet" href="css/common-dashboard.css">
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="css/themes.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* ===== FILTERS & SEARCH ===== */
        .filters-row {
            display: flex; gap: 12px; margin-bottom: 28px;
            align-items: center; flex-wrap: wrap;
        }
        .filter-btn {
            padding: 9px 20px; border-radius: 20px; text-decoration: none;
            color: var(--p-text-muted); font-weight: 600; font-size: 14px;
            background: var(--p-bg-card); transition: all 0.3s;
            box-shadow: var(--shadow-sm); border: 1.5px solid var(--p-border);
            display: flex; align-items: center; gap: 6px;
        }
        .filter-btn.active { background: var(--p-gradient); color: white; border-color: transparent; }
        .search-bar {
            flex: 1; min-width: 200px; max-width: 320px;
            display: flex; align-items: center;
            background: var(--p-bg-card); border: 1.5px solid var(--p-border);
            border-radius: 20px; padding: 0 16px; gap: 8px;
            box-shadow: var(--shadow-sm);
        }
        .search-bar input {
            flex: 1; border: none; background: transparent; font-size: 14px;
            color: var(--p-text-main); outline: none; padding: 9px 0;
        }
        .search-bar i { color: var(--p-text-muted); }

        /* ===== GRID ===== */
        .allies-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(330px, 1fr));
            gap: 24px;
        }

        /* ===== CARD ===== */
        .ally-card {
            background: var(--p-bg-card); border-radius: 20px; overflow: hidden;
            box-shadow: var(--shadow-md); transition: transform 0.3s, box-shadow 0.3s;
            cursor: pointer; text-decoration: none; color: inherit; display: flex;
            flex-direction: column; border: 1px solid var(--p-border);
        }
        .ally-card:hover { transform: translateY(-6px); box-shadow: 0 16px 48px rgba(102,126,234,0.15); }

        /* Image carousel */
        .ally-image-wrap {
            height: 200px; position: relative; overflow: hidden;
            background: var(--p-bg-main); flex-shrink: 0;
        }
        .ally-image-slides { display: flex; height: 100%; transition: transform 0.4s ease; }
        .ally-image-slide { min-width: 100%; height: 100%; }
        .ally-image-slide img { width: 100%; height: 100%; object-fit: cover; }
        .ally-image-placeholder {
            width: 100%; height: 100%; display: flex; align-items: center; 
            justify-content: center; font-size: 56px; color: #cbd5e1;
            background: linear-gradient(135deg, #f1f5f9, #e2e8f0);
        }
        .slide-dots {
            position: absolute; bottom: 10px; left: 50%; transform: translateX(-50%);
            display: flex; gap: 5px;
        }
        .slide-dot {
            width: 7px; height: 7px; border-radius: 50%;
            background: rgba(255,255,255,0.5); cursor: pointer; transition: all 0.3s;
        }
        .slide-dot.active { background: white; width: 18px; border-radius: 4px; }
        .slide-arrow {
            position: absolute; top: 50%; transform: translateY(-50%);
            background: rgba(0,0,0,0.35); color: white; border: none;
            width: 30px; height: 30px; border-radius: 50%; cursor: pointer;
            font-size: 12px; display: flex; align-items: center; justify-content: center;
            opacity: 0; transition: opacity 0.3s;
        }
        .ally-image-wrap:hover .slide-arrow { opacity: 1; }
        .slide-arrow-prev { left: 10px; }
        .slide-arrow-next { right: 10px; }

        /* Badges over image */
        .badge-type {
            position: absolute; top: 14px; right: 14px;
            padding: 5px 12px; border-radius: 12px; font-size: 12px;
            font-weight: 700; color: white; text-transform: uppercase; letter-spacing: 0.5px;
        }
        .badge-vet { background: linear-gradient(135deg, #00b09b, #96c93d); }
        .badge-tienda { background: linear-gradient(135deg, #ff7e5f, #feb47b); }

        .badge-open {
            position: absolute; top: 14px; left: 14px;
            padding: 4px 10px; border-radius: 10px;
            font-size: 11px; font-weight: 700;
            background: rgba(15,23,42,0.65); backdrop-filter: blur(4px);
            color: white; display: flex; align-items: center; gap: 5px;
        }
        .dot-open { width: 7px; height: 7px; border-radius: 50%; display: inline-block; }

        /* Card body */
        .ally-body { padding: 18px 20px; flex: 1; display: flex; flex-direction: column; }

        .ally-header-row { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 6px; }
        .ally-name { font-size: 17px; font-weight: 800; color: var(--p-text-main); line-height: 1.2; }

        .ally-rating { color: #f59e0b; font-size: 13px; white-space: nowrap; }

        .ally-desc {
            font-size: 13.5px; color: var(--p-text-muted); line-height: 1.55;
            margin-bottom: 14px;
        }
        .ally-desc.collapsed {
            display: -webkit-box; -webkit-line-clamp: 2;
            -webkit-box-orient: vertical; overflow: hidden;
        }
        .toggle-desc {
            background: none; border: none; color: #667eea; font-size: 12.5px;
            font-weight: 600; cursor: pointer; padding: 0; margin-bottom: 14px;
        }

        /* Tags */
        .ally-tags { display: flex; gap: 6px; flex-wrap: wrap; margin-bottom: 14px; }
        .tag {
            padding: 5px 12px; border-radius: 12px; font-size: 11px; font-weight: 700;
            background: rgba(102,126,234,0.1); color: #667eea;
            display: inline-flex; align-items: center; gap: 5px;
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        .tag:hover {
            transform: translateY(-2px) scale(1.05);
            background: rgba(102,126,234,0.2);
            box-shadow: 0 4px 10px rgba(102,126,234,0.2);
        }
        .tag.green { background: rgba(16,185,129,0.1); color: #059669; }
        .tag.green:hover { background: rgba(16,185,129,0.2); box-shadow: 0 4px 10px rgba(16,185,129,0.2); }
        .tag.orange { background: rgba(245,158,11,0.1); color: #d97706; }
        .tag.orange:hover { background: rgba(245,158,11,0.2); box-shadow: 0 4px 10px rgba(245,158,11,0.2); }

        /* Footer info */
        .ally-meta { margin-top: auto; border-top: 1px solid var(--p-border); padding-top: 12px; }
        .ally-meta-row { display: flex; align-items: center; gap: 7px; font-size: 12.5px; color: var(--p-text-muted); margin-bottom: 6px; }
        .ally-meta-row i { width: 14px; flex-shrink: 0; }

        /* Online status */
        .online-status {
            display: flex; align-items: center; gap: 6px;
            font-size: 12px; font-weight: 600;
        }
        .online-dot {
            width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0;
            animation: pulse-dot 1.5s infinite;
        }
        @keyframes pulse-dot {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        .ally-cta {
            display: flex; align-items: center; justify-content: space-between;
            margin-top: 14px;
        }
        .btn-ver {
            padding: 8px 18px; border-radius: 10px; font-size: 13px; font-weight: 700;
            background: linear-gradient(135deg, #667eea, #764ba2); color: white;
            text-decoration: none; transition: all 0.3s; border: none; cursor: pointer;
        }
        .btn-ver:hover { transform: translateY(-1px); box-shadow: 0 6px 16px rgba(102,126,234,0.3); }

        /* Featured products mini */
        .mini-products { display: flex; gap: 6px; margin-bottom: 10px; }
        .mini-product {
            width: 44px; height: 44px; border-radius: 8px; overflow: hidden;
            background: var(--p-bg-main); flex-shrink: 0; position: relative;
            border: 1px solid var(--p-border);
        }
        .mini-product img { width: 100%; height: 100%; object-fit: cover; }
        .mini-product-placeholder { width:100%; height:100%; display:flex; align-items:center; justify-content:center; color:var(--p-text-muted); font-size:16px; }
        .mini-product-more { font-size: 11px; color: var(--p-text-muted); font-weight: 600; align-self: center; }

        /* Empty state */
        .empty-state { text-align: center; padding: 60px 20px; color: var(--p-text-muted); }
        .empty-state i { font-size: 48px; margin-bottom: 16px; opacity: 0.4; }

        @media (max-width: 640px) {
            .filters-row { flex-direction: column; align-items: flex-start; }
            .search-bar { max-width: 100%; width: 100%; }
            .allies-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body class="<?php echo $themeClass; ?>">
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <header class="header">
            <h1 class="page-title">🤝 Explorar Aliados</h1>
        </header>
        
        <div class="content-wrapper">
            <!-- Filtros + Búsqueda -->
            <div class="filters-row">
                <a href="?tipo=todos" class="filter-btn <?php echo $activeTab == 'todos' ? 'active' : ''; ?>">
                    <i class="fas fa-th"></i> Todos
                </a>
                <a href="?tipo=veterinaria" class="filter-btn <?php echo $activeTab == 'veterinaria' ? 'active' : ''; ?>">
                    <i class="fas fa-hospital"></i> Veterinarias
                </a>
                <a href="?tipo=tienda" class="filter-btn <?php echo $activeTab == 'tienda' ? 'active' : ''; ?>">
                    <i class="fas fa-store"></i> Tiendas
                </a>
                <form class="search-bar" method="GET">
                    <input type="hidden" name="tipo" value="<?php echo htmlspecialchars($activeTab); ?>">
                    <i class="fas fa-search"></i>
                    <input type="text" name="q" value="<?php echo htmlspecialchars($search); ?>" placeholder="Buscar aliado, servicio, dirección...">
                </form>
            </div>

            <?php if (empty($aliados)): ?>
            <div class="empty-state">
                <i class="fas fa-handshake"></i>
                <h3>No se encontraron aliados</h3>
                <p>Intenta cambiar los filtros o la búsqueda</p>
            </div>
            <?php else: ?>
            <div class="allies-grid">
                <?php foreach ($aliados as $idx => $aliado): 
                    $statusInfo = getAllyCurrentStatus($aliado['horario'] ?? '');
                    $badgeClass = $statusInfo['badge_class'] ?? 'status-closed';
                    $statusLabel = $statusInfo['label'] ?? 'Cerrado';
                    $isOpen = ($statusInfo['status'] ?? 'closed') === 'open';
                    $onlineSt = getOnlineStatus($aliado['owner_last_login'] ?? null);
                    $cardId = 'ally-' . $aliado['id'];
                    
                    // Fotos: principal + verificacion
                    $mainFoto = null;
                    $mainFoto = buildImgUrl($aliado['foto_local'] ?? '');
                    $fotosArr = $aliado['fotos_array'];
                    
                    // All slides: main foto first, then verification photos
                    $allSlides = [];
                    if ($mainFoto) $allSlides[] = $mainFoto;
                    foreach ($fotosArr as $f) { if ($f !== $mainFoto) $allSlides[] = $f; }
                    
                    // Tags from services
                    $tags = [];
                    if (!empty($aliado['servicios'])) {
                        $decodedServs = json_decode($aliado['servicios'], true);
                        if (json_last_error() === JSON_ERROR_NONE && is_array($decodedServs)) {
                            // Formato Nuevo JSON
                            $tags = array_slice($decodedServs, 0, 3);
                        } else {
                            // Formato Antiguo CSV
                            $rawTags = explode(',', $aliado['servicios']);
                            foreach (array_slice($rawTags, 0, 3) as $t) {
                                $t = trim($t);
                                if ($t) $tags[] = ['name' => $t, 'icon' => 'fas fa-star'];
                            }
                        }
                    }
                    if (!empty($aliado['tipo_alimento'])) {
                        $rawAlim = explode(',', $aliado['tipo_alimento']);
                        foreach (array_slice($rawAlim, 0, 3) as $t) {
                            $t = trim($t);
                            if ($t) $tags[] = ['name' => $t, 'icon' => 'fas fa-bone'];
                        }
                    }
                    
                    $profileLink = $aliado['tipo'] === 'tienda' ? 'perfil-tienda.php' : 'perfil-aliado.php';
                ?>
                <div class="ally-card" id="<?php echo $cardId; ?>">
                    <!-- IMAGE / CAROUSEL -->
                    <div class="ally-image-wrap">
                        <div class="ally-image-slides" id="slides-<?php echo $aliado['id']; ?>">
                            <?php if (!empty($allSlides)): ?>
                                <?php foreach ($allSlides as $slide): ?>
                                <div class="ally-image-slide">
                                    <img src="<?php echo htmlspecialchars($slide); ?>" 
                                         alt="<?php echo htmlspecialchars($aliado['nombre_local']); ?>"
                                         onerror="this.parentElement.innerHTML='<div class=\'ally-image-placeholder\'><i class=\'fas fa-<?php echo $aliado['tipo'] === 'veterinaria' ? 'hospital' : 'store'; ?>\'></i></div>'">
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="ally-image-slide">
                                    <div class="ally-image-placeholder">
                                        <i class="fas fa-<?php echo $aliado['tipo'] === 'veterinaria' ? 'hospital' : 'store'; ?>"></i>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <?php if (count($allSlides) > 1): ?>
                        <button class="slide-arrow slide-arrow-prev" onclick="slideCard(<?php echo $aliado['id']; ?>, -1, event)">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <button class="slide-arrow slide-arrow-next" onclick="slideCard(<?php echo $aliado['id']; ?>, 1, event)">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                        <div class="slide-dots" id="dots-<?php echo $aliado['id']; ?>">
                            <?php for ($s = 0; $s < count($allSlides); $s++): ?>
                            <div class="slide-dot <?php echo $s === 0 ? 'active' : ''; ?>" onclick="goToSlide(<?php echo $aliado['id']; ?>, <?php echo $s; ?>, event)"></div>
                            <?php endfor; ?>
                        </div>
                        <?php endif; ?>

                        <!-- Horario badge -->
                        <div class="badge-open">
                            <span class="dot-open" style="background:<?php echo $isOpen ? '#10b981' : '#ef4444'; ?>;"></span>
                            <?php echo $statusLabel; ?>
                        </div>

                        <!-- Tipo badge -->
                        <span class="badge-type <?php echo $aliado['tipo'] === 'veterinaria' ? 'badge-vet' : 'badge-tienda'; ?>">
                            <?php echo ucfirst($aliado['tipo']); ?>
                        </span>
                    </div>

                    <!-- BODY -->
                    <div class="ally-body">
                        <div class="ally-header-row">
                            <div class="ally-name"><?php echo htmlspecialchars($aliado['nombre_local']); ?></div>
                            <div class="ally-rating">
                                <i class="fas fa-star"></i> <?php echo number_format($aliado['calificacion'] ?? 0, 1); ?>
                                <span style="opacity:0.5;font-size:11px;"> (<?php echo intval($aliado['total_calificaciones'] ?? 0); ?>)</span>
                            </div>
                        </div>

                        <?php if (!empty($aliado['descripcion'])): ?>
                        <p class="ally-desc collapsed" id="desc-<?php echo $aliado['id']; ?>">
                            <?php echo htmlspecialchars($aliado['descripcion']); ?>
                        </p>
                        <button class="toggle-desc" onclick="toggleDesc(<?php echo $aliado['id']; ?>, this)" data-expanded="0">
                            <i class="fas fa-chevron-down"></i> Ver más
                        </button>
                        <?php endif; ?>

                        <!-- Tags de servicios/productos -->
                        <?php if (!empty($tags)): ?>
                        <div class="ally-tags">
                            <?php foreach ($tags as $tagObj): 
                                $tName = is_array($tagObj) ? $tagObj['name'] : $tagObj;
                                $tIcon = is_array($tagObj) ? ($tagObj['icon'] ?? 'fas fa-star') : 'fas fa-star';
                            ?>
                            <span class="tag <?php echo $aliado['tipo'] === 'veterinaria' ? 'green' : 'orange'; ?>">
                                <i class="<?php echo htmlspecialchars($tIcon); ?>"></i> <?php echo htmlspecialchars($tName); ?>
                            </span>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>

                        <!-- Mini productos -->
                        <?php if (!empty($aliado['productos_destacados'])): ?>
                        <div class="mini-products">
                            <?php foreach (array_slice($aliado['productos_destacados'], 0, 3) as $prod):
                                $imgPath = buildImgUrl($prod['imagen'] ?? '');
                             ?>
                            <div class="mini-product" title="<?php echo htmlspecialchars($prod['nombre']); ?> — $<?php echo number_format($prod['precio'], 0, ',', '.'); ?>">
                                <?php if ($imgPath): ?>
                                    <img src="<?php echo htmlspecialchars($imgPath); ?>" alt="">
                                <?php else: ?>
                                    <div class="mini-product-placeholder"><i class="fas fa-box"></i></div>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                            <?php if (count($aliado['productos_destacados']) > 3): ?>
                            <div class="mini-product-more">+<?php echo count($aliado['productos_destacados']) - 3; ?> más</div>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>

                        <!-- Meta info -->
                        <div class="ally-meta">
                            <?php if (!empty($aliado['direccion'])): ?>
                            <div class="ally-meta-row">
                                <i class="fas fa-map-marker-alt" style="color:#667eea;"></i>
                                <?php echo htmlspecialchars($aliado['direccion']); ?>
                                <?php if (!empty($aliado['google_maps_url'])): ?>
                                <a href="<?php echo htmlspecialchars($aliado['google_maps_url']); ?>" target="_blank" 
                                   onclick="event.stopPropagation()"
                                   style="margin-left:auto;font-size:11px;color:#667eea;font-weight:600;text-decoration:none;">
                                    <i class="fas fa-external-link-alt"></i> Ver mapa
                                </a>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>

                            <div class="ally-meta-row" style="justify-content:space-between;">
                                <!-- Online status -->
                                <div class="online-status">
                                    <span class="online-dot" style="background:<?php echo $onlineSt['color']; ?>;"></span>
                                    <span style="color:<?php echo $onlineSt['color']; ?>;"><?php echo $onlineSt['label']; ?></span>
                                </div>

                                <!-- Fotos verificadas -->
                                <?php if (!empty($aliado['fotos_array'])): ?>
                                <div style="font-size:11px;color:#10b981;font-weight:700;display:flex;align-items:center;gap:4px;">
                                    <i class="fas fa-shield-alt"></i> Verificado
                                </div>
                                <?php endif; ?>
                            </div>

                            <div class="ally-cta">
                                <a href="<?php echo $profileLink; ?>?id=<?php echo $aliado['id']; ?>" class="btn-ver">
                                    <i class="fas fa-eye"></i> Ver Perfil
                                </a>
                                <?php if (!empty($aliado['google_maps_url'])): ?>
                                <a href="<?php echo htmlspecialchars($aliado['google_maps_url']); ?>" target="_blank"
                                   onclick="event.stopPropagation()"
                                   style="font-size:13px;color:#64748b;font-weight:600;text-decoration:none;display:flex;align-items:center;gap:5px;">
                                    <i class="fas fa-map"></i> Cómo llegar
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
    // Carousel state per card
    const carouselState = {};

    function slideCard(id, dir, e) {
        e.preventDefault(); e.stopPropagation();
        const slides = document.getElementById('slides-' + id);
        const dots = document.getElementById('dots-' + id);
        if (!slides) return;
        const total = slides.querySelectorAll('.ally-image-slide').length;
        let cur = carouselState[id] || 0;
        cur = (cur + dir + total) % total;
        carouselState[id] = cur;
        slides.style.transform = `translateX(-${cur * 100}%)`;
        if (dots) {
            dots.querySelectorAll('.slide-dot').forEach((d, i) => d.classList.toggle('active', i === cur));
        }
    }

    function goToSlide(id, idx, e) {
        e.preventDefault(); e.stopPropagation();
        const slides = document.getElementById('slides-' + id);
        const dots = document.getElementById('dots-' + id);
        if (!slides) return;
        carouselState[id] = idx;
        slides.style.transform = `translateX(-${idx * 100}%)`;
        if (dots) {
            dots.querySelectorAll('.slide-dot').forEach((d, i) => d.classList.toggle('active', i === idx));
        }
    }

    function toggleDesc(id, btn) {
        const desc = document.getElementById('desc-' + id);
        const expanded = btn.getAttribute('data-expanded') === '1';
        if (expanded) {
            desc.classList.add('collapsed');
            btn.innerHTML = '<i class="fas fa-chevron-down"></i> Ver más';
            btn.setAttribute('data-expanded', '0');
        } else {
            desc.classList.remove('collapsed');
            btn.innerHTML = '<i class="fas fa-chevron-up"></i> Ver menos';
            btn.setAttribute('data-expanded', '1');
        }
    }
    </script>
</body>
</html>
